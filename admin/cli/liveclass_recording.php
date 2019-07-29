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
//
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

$getCourseModuleSql = "SELECT cm.*,m.name as modulename,m.id as moduleid FROM {modules} as m join {course_modules} as cm on m.id=cm.module WHERE m.name in ('wiziq','braincert') ORDER BY id DESC";
$courseResult = $DB->get_records_sql($getCourseModuleSql);

foreach ($courseResult as $activity) {
  $modulename = '{' . $activity->modulename . '}';
  $classDetails = "SELECT m.* FROM {$modulename} as m LEFT JOIN {guru_liveclass_recording} as glr on glr.class_id = m.class_id WHERE m.id = $activity->instance ";
  $class = $DB->get_record_sql($classDetails);

  $download_recording_link = "";
  $courseid = $class->course;
  $class_id = $class->class_id;
  $folderPath = $CFG->liveclass_path . $activity->id;
  cli_heading("Course Id is:- " . $courseid . " Class Id is:- " . $class_id);
  if ($activity->modulename == "wiziq") {
    wiziq_downloadrecording($courseid, $class_id, $download_recording_link, $errormsg, $abcdd);
    cli_heading('Live class download url:- ' . $download_recording_link);
    if (!empty($download_recording_link)) {

      if (!file_exists($folderPath)) {
        mkdir($folderPath);
      }
      $localFileName = $folderPath . '/output.mp4';
      exec("wget '" . $download_recording_link . "' -P $folderPath -O $localFileName");
      $smileFile = $folderPath . '/playlist.smil';
      createSmilFile($smileFile, $localFileName);
      $res['class_id'] = $class_id;
      $res['size'] = filesize($localFileName);
      $res['record_path'] = $localFileName;
      $res['record_url'] = getWowzaUrl($smileFile, $CFG->liveclass_bucket);
      $res['platform'] = $activity->modulename;
      createLiveClassMapping($res);
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
            $res['class_id'] = $class_id;
            $res['size'] = filesize($localFileName);
            $res['record_path'] = $localFileName;
            $res['record_url'] = getWowzaUrl($smileFile, $CFG->liveclass_bucket);
            $res['platform'] = $activity->modulename;
            createLiveClassMapping($res);
          }
        }
      }
    }
  }
}

function createLiveClassMapping($res) {
  global $DB;
  $insertRecord['class_id'] = $res['class_id'];
  $insertRecord['size'] = $res['size'];
  $insertRecord['record_path'] = $res['record_path'];
  $insertRecord['record_url'] = $res['record_url'];
  $insertRecord['platform'] = $res['platform'];
  $DB->insert_record('guru_liveclass_recording', $insertRecord);
}

cli_heading('Live class download and upload successfully');

exit(0); // 0 means success.
