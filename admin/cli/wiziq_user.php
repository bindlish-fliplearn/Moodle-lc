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
require_once($CFG->libdir . '/../mod/wiziq/locallib.php'); // wiziq Lib

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
  $help = "Migrate wiziq user in moodle database

There are no security checks here because anybody who is able to
execute this file may execute any PHP too.

Options:
-h, --help                    Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/wiziq_user.php
";

  echo $help;
  die;
}

if (!$wiziqClass = $DB->get_records_sql("select * from {wiziq}")) {
  cli_error("Can not find classes");
}
$diffMin = "20";
foreach ($wiziqClass as $class) {
  $insertRecord = [];  
  $id = $class->course;
  $class_id = $class->class_id;
  wiziq_getattendancereport($id, $class_id, $id, $errormsg,
        $attendancexmlch_dur, $attendancexmlch_attlist);
  print_r($errormsg);
//  cli_heading("Api response for class id:- " . $class->class_id . ' Response is :- ' . json_encode($resp));
//    if (!empty($resp)) {
//      foreach ($resp as $res) {
//        if (isset($res['classId']) && !empty($res['classId'])) {
//          $wiziqClass = $DB->get_record('guru_wiziq_user', array('class_id' => $res['classId'], 'user_id' => $res['userId']));
//          if (!$wiziqClass) {
//            $insertRecord['class_id'] = $res['classId'];
//            $insertRecord['user_id'] = $res['userId'];
//            $insertRecord['duration'] = $res['duration'];
//            $insertRecord['percentage'] = $res['percentage'];
//            $insertRecord['attendance'] = $res['attendance'];
//            $insertRecord['is_teacher'] = $res['isTeacher'];
//            $insertRecord['session'] = json_encode($res['session']);
//            $respQuery = $DB->insert_record('guru_wiziq_user', $insertRecord);
//            if ($respQuery) {
//              cli_heading("Insert record successfully.");
//            }
//          } else {
//            $wiziqClass->duration = $res['duration'];
//            $wiziqClass->percentage = $res['percentage'];
//            $wiziqClass->attendance = $res['attendance'];
//            $wiziqClass->is_teacher = $res['isTeacher'];
//            $wiziqClass->session = json_encode($res['session']);
//            $respQuery = $DB->update_record('guru_wiziq_user', $wiziqClass);
//            cli_heading("Update record successfully.");
//          }
//        } else {
//          cli_heading("Api response don't have class id:- " . $class->class_id);
//        }
//      }
//    } else {
//      cli_error("Can not find class:- " . json_encode($resp));
//    }
}

echo "Braincert user migrated\n";

exit(0); // 0 means success.
