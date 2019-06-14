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
require_once($CFG->libdir . '/../mod/braincert/locallib.php'); // braincert Lib
// Define the input options.
$longparams = array(
  'help' => false,
  'min' => false
);

$shortparams = array(
  'h' => 'help',
  'm' => 'min'
);

// now get cli options
list($options, $unrecognized) = cli_get_params($longparams, $shortparams);

if ($unrecognized) {
  $unrecognized = implode("\n  ", $unrecognized);
  cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
  $help = "Notify braincert class user video url in moodle database

There are no security checks here because anybody who is able to
execute this file may execute any PHP too.

Options:
-h, --help                    Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/braincert_recording.php
";

  echo $help;
  die;
}

if ($options['min'] == '') {
  cli_heading('Please enter minutes before class for notification.');
  $prompt = "min (minutes)";
  $diffMin = cli_input($prompt);
} else {
  $diffMin = $options['min'];
}

if (!$braincertClass = $DB->get_records_sql("select * from {braincert} where DATE(FROM_UNIXTIME(start_date)) = DATE(NOW())")) {
  cli_error("Can not find classes");
}
date_default_timezone_set('Asia/Kolkata');   //India time (GMT+5:30)
foreach ($braincertClass as $class) {
  $courseId = $class->course;
  $todayClass = date('y-m-d') . ' ' . $class->start_time;
  $classTime = date('y-m-d h:i:s A', strtotime($todayClass));
  cli_heading("Today class time is " . $classTime . ' for this class id:- ' . $class->class_id);
  $notificationTime = date("y-m-d h:i:s A", strtotime("-$diffMin minutes", strtotime($classTime)));
  cli_heading("Notification sending time is " . $notificationTime . ' for this class id:- ' . $class->class_id);
  $now = date("y-m-d h:i:s A", time());
  if (strtotime($notificationTime) < strtotime($now)) {
    $braincertClassNotify = $DB->get_records('guru_braincert_notification', array('course_id' => $courseId, 'class_id' => $class->class_id, 'min' => $diffMin));
    if (!$braincertClassNotify) {
      $contextlevel = $CFG->CONTEXT_LEVEL;
      $send_notification = $CFG->SEND_NOTIFICATION;
      $sql = "SELECT mra.userid, gum.uuid as uuid,
            gum.school_code AS school_code, cm.id as contextid
            FROM {context} As mc 
            INNER JOIN {role_assignments} AS mra 
            ON mc.id = mra.contextid 
            INNER JOIN  {guru_user_mapping} AS  gum 
            ON gum.user_id = mra.userid
            INNER JOIN {course_modules} AS cm
            on cm.instance = ? AND cm.course = ?
            WHERE mc.instanceid = ? 
            AND mc.contextlevel = ?
            AND mra.userid 
            iN(SELECT ue.userid FROM mdl_enrol 
            AS me INNER JOIN mdl_user_enrolments 
            AS ue ON me.id = ue.enrolid 
            WHERE me.courseid = $courseId AND ue.status = 0)";
      $result = $DB->get_records_sql($sql, array($class->id, $courseId, $courseId, $contextlevel));
      $school_code = '';
      $uuidList = array();
      $objectid = "";
      foreach ($result as $value) {
        $school_code = $value->school_code;
        $uuid = $value->uuid;
        $objectid = $value->contextid;
        array_push($uuidList, $uuid);
      }
      $messageTitle = $class->name;
      $messageText = $class->intro;
      $domainName = str_replace("http://", "", $CFG->wwwroot);
      $clickUrl = $CFG->wwwroot . "/mod/braincert/view.php?id=" . $objectid . '&forceview=1';
      if (count($uuidList) > 0 && $send_notification == true) {
        $serializeRequest = array('senderUuid' => 1234,
          'schoolCode' => $school_code,
          'messageTitle' => $messageTitle,
          'messageText' => strip_tags($messageTitle),
          'uuidList' => $uuidList,
          'smsEnabled' => true,
          'emailEnabled' => true,
          'domainName' => $domainName,
          'clickUrl' => $clickUrl
        );
        $serializeRequestJson = json_encode($serializeRequest);
        $request = array(
          'eventType' => $CFG->GURU_ANNOUNCEMENT,
          'eventDate' => date("Y-m-d\TH:i:s.511\Z", strtotime(date('y-m-d'))),
          'payload' => $serializeRequestJson
        );
        $data_string = json_encode($request);
        $result = curlPost($data_string, $CFG->COMMUNICATION_API_URL);
        $responseData = json_decode($result);
        if ($responseData->error != null) {
          cli_heading("Error while sending notification. " . json_encode($responseData));
        } else {
          $insertRecord['course_id'] = $courseId;
          $insertRecord['class_id'] = $class->class_id;
          $insertRecord['class_time'] = $classTime;
          $insertRecord['event_request'] = $data_string;
          $insertRecord['event_response'] = json_encode($responseData);
          $insertRecord['min'] = $diffMin;
          $respQuery = $DB->insert_record('guru_braincert_notification', $insertRecord);
          echo "Braincert notification send\n";
        }
      }
    }
  } else {
    cli_heading("Notification already sent.");
  }
  echo PHP_EOL;
  die;
}

function curlPost($data_string, $url) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string))
  );

  $result = curl_exec($ch);
  $error = curl_errno($ch);
  $responseArray = array('error' => null, 'data' => '');

  if ($result) {
    $responseArray['data'] = $result;
    return json_encode($responseArray);
  } else {
    $responseArray['error'] = $error;
    return json_encode($responseArray);
  }
}

exit(0); // 0 means success.
