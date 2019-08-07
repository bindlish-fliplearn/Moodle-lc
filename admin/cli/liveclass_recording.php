<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script allows you to reset any local user password.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');      // cli only functions
require_once($CFG->libdir . '/fliplearnlib.php');      // cli only functions
require_once($CFG->libdir . '/../mod/braincert/locallib.php'); // braincert Lib
require_once($CFG->libdir . '/../mod/wiziq/locallib.php'); // wiziq Lib
require_once $CFG->libdir . '/../local/aws/aws-autoloader.php';

$sqsClient = \Aws\Sqs\SqsClient::factory(array(
    'version' => 'latest',
    'region' => $CFG->amazon_region,
    'credentials' => array(
      'key' => $CFG->amazon_key,
      'secret' => $CFG->amazon_secret
  )));

// Define the input options.
$longparams = array(
  'help' => false,
);

$shortparams = array(
  'h' => 'help',
);

// now get cli options
list($options, $unrecognized) = cli_get_params($longparams, $shortparams);

if ($unrecognized) {
  $unrecognized = implode("\n  ", $unrecognized);
  cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
  $help = "Migrate liveclass recording video url in moodle database

There are no security checks here because anybody who is able to
execute this file may execute any PHP too.

Options:
-h, --help                    Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/liveclass_recording.php
";

  echo $help;
  die;
}

$getCourseModuleSql = "SELECT cm.*,m.name as modulename,m.id as moduleid FROM {modules} as m join {course_modules} as cm on m.id=cm.module WHERE m.name in ('wiziq','braincert') ORDER BY id DESC LIMIT 15";
$courseResult = $DB->get_records_sql($getCourseModuleSql);

foreach ($courseResult as $activity) {
  $modulename = '{' . $activity->modulename . '}';
  $classDetails = "SELECT m.*,glr.id FROM {$modulename} as m LEFT JOIN {guru_liveclass_recording} as glr on glr.class_id = m.class_id WHERE m.id = $activity->instance AND  glr.id is NULL";
  $class = $DB->get_record_sql($classDetails);

  $download_recording_link = "";
  $courseid = $class->course;
  $class_id = $class->class_id;
  $folderPath = $CFG->liveclass_path . 'Resourse/' . $activity->id;
  $resArray = [];
  cli_heading("Course Id is:- " . $courseid . " Class Id is:- " . $class_id);
  if ($activity->modulename == "wiziq") {
    wiziq_downloadrecording($courseid, $class_id, $download_recording_link, $errormsg, $abcdd);
    cli_heading('Live class download url:- ' . $download_recording_link);
    if (!empty($download_recording_link)) {

      if (!file_exists($folderPath)) {
        mkdir($folderPath);
      }
      $localFileName = $folderPath . '/' . $activity->id . '.mp4';
      $localOutputFileName = $folderPath . '/output.mp4';
      exec("wget '" . $download_recording_link . "' -P $folderPath -O $localFileName");
      exec("wget '" . $download_recording_link . "' -P $folderPath -O $localOutputFileName");
      $smileFile = $folderPath . '/playlist.smil';
      createSmilFile($smileFile, $localFileName);
      $smileFile = 'Resourse/' . $activity->id . '/playlist.smil';
      $resArray['cm_id'] = $activity->id;
      $resArray['class_id'] = $class_id;
      $resArray['size'] = filesize($localFileName);
      $resArray['record_path'] = $localFileName;
      $resArray['record_url'] = getWowzaUrl($smileFile, $CFG->liveclass_bucket);
      $resArray['platform'] = $activity->modulename;
      print_r($resArray);
      createLiveClassMapping($resArray);
      $fileSQS = 'Resourse/' . $activity->id . '/' . $activity->id . '.mp4';
      $request = array('cm_id' => $activity->id, 'file' => $fileSQS);
      sendInQueue($sqsClient, $request);
    }
  } else {
    $data['task'] = 'getclassrecording';
    $data['class_id'] = $class_id;
    $resp = braincert_get_curl_info($data);
    if (!empty($resp)) {
      foreach ($resp as $res) {
        if (isset($res['classroom_id']) && !empty($res['classroom_id'])) {
          $download_recording_link = $res['record_path'];
          cli_heading('Live class download url:- ' . $download_recording_link);
          if (!empty($download_recording_link)) {
            if (!file_exists($folderPath)) {
              mkdir($folderPath);
            }
            $onlylink = explode("?", $download_recording_link);
            $ext = 'mp4';
            if (isset($onlylink[0]) && !empty($onlylink[0])) {
              $filename = end(explode("/", $onlylink[0]));
              $ext = end(explode(".", $filename));
            }
            $localFileName = $folderPath . '/output.' . $ext;
            exec("wget '" . $download_recording_link . "' -P $folderPath -O $localFileName");
            $smileFile = $folderPath . '/playlist.smil';
            createSmilFile($smileFile, $localFileName);
            $smileFile = 'Resourse/' . $activity->id . '/playlist.smil';
            $resArray['cm_id'] = $activity->id;
            $resArray['class_id'] = $class_id;
            $resArray['size'] = filesize($localFileName);
            $resArray['record_path'] = $localFileName;
            $resArray['record_url'] = getWowzaUrl($smileFile, $CFG->liveclass_bucket);
            $resArray['platform'] = $activity->modulename;
            createLiveClassMapping($resArray);
            $fileSQS = 'Resourse/' . $activity->id . '/' . $activity->id . '.' . $ext;
            $request = array('cm_id' => $activity->id, 'file' => $fileSQS);
            sendInQueue($sqsClient, $request);
          }
        }
      }
    }
  }
}

function createLiveClassMapping($res) {
  global $DB;
  if (isset($res['cm_id']) && isset($res['class_id']) && isset($res['record_url'])) {
    $res['record_url'] = urlencode($res['record_url']);
    try {
      if (!$checkVideo = $DB->get_records('guru_liveclass_recording', array('cm_id' => $res['cm_id']))) {
        $reminderCreated = "INSERT INTO {guru_liveclass_recording} SET cm_id='{$res['cm_id']}', class_id='{$res['class_id']}', file_size='{$res['size']}', record_path='{$res['record_path']}', record_url='{$res['record_url']}', platform='{$res['platform']}'";
        $DB->execute($reminderCreated);
        cli_heading('Insert Query of recording video:- ' . $reminderCreated);
      } else {
        $reminderCreated = "UPDATE {guru_liveclass_recording} SET record_path=\"{$res['record_path']}\", class_id=\"{$res['class_id']}\", record_url=\"{$res['record_url']}\" where cm_id={$res['cm_id']}";
        $DB->execute($reminderCreated);
        cli_heading('Update Query of recording video:- ' . $reminderCreated);
      }
    } catch (\Exception $e) {
      cli_heading('Error While Insert.' . $e->getMessage());
    }
  }
}

function sendInQueue($sqsClient, $request) {
  global $CFG;
  $json = json_encode($request);
  $sqsClient->sendMessage(array(
    'QueueUrl' => $CFG->amazon_sqs_url,
    'MessageBody' => $json,
    'MessageGroupId' => 'prod'
  ));
}

cli_heading('Live class download and upload successfully');

exit(0); // 0 means success.
