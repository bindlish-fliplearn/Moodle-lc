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

if ($options['min'] == '' ) {
    cli_heading('Please enter minutes before class for notification.');
    $prompt = "min (minutes)";
    $diffMin = cli_input($prompt);
} else {
    $diffMin = $options['min'];
}

if (!$braincertClass = $DB->get_records_sql("select * from mdl_braincert where DATE(FROM_UNIXTIME(start_date)) = DATE(NOW())")) {
  cli_error("Can not find classes");
}
date_default_timezone_set('Asia/Kolkata');   //India time (GMT+5:30)
foreach ($braincertClass as $class) {
  $courseId = $class->course;
  $todayClass = date('y-m-d') . ' ' . $class->start_time;
  $classTime = date('y-m-d h:i:s A', strtotime($todayClass));
  cli_heading("Today class time is ". $classTime.' for this class id:- '.$class->class_id);
  $syncDataTime = date("y-m-d h:i:s A", strtotime("+$diffMin minutes", strtotime($classTime)));
  cli_heading("Sync data time is ". $syncDataTime.' for this class id:- '.$class->class_id);
  $now = date("y-m-d h:i:s A", time());
  if (!$braincertClassNotify = $DB->get_records('guru_braincert_class', array('course_id' => $courseId, 'class_id' => $class->class_id, 'min' => $diffMin))) {
    if (strtotime($syncDataTime) > strtotime($now)) {
      $data['task'] = 'getclassreport';
      $data['classId'] = $class->class_id;
      $resp = braincert_get_curl_info($data);
      $insertRecord['course_id'] = $courseId;
      $insertRecord['class_id'] = $class->class_id;
      $insertRecord['class_time'] = $resp['duration'];
      $insertRecord['class_response'] = json_encode($resp);
      $insertRecord['user_count'] = count($resp);
      $insertRecord['min'] = $diffMin;
      $respQuery = $DB->insert_record('guru_braincert_class', $insertRecord);
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
