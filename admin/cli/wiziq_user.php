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

if (!$wiziqClass = $DB->get_records_sql("select w.* from {wiziq} as w LEFT JOIN {guru_wiziq_user} as gwu on w.class_id=gwu.class_id WHERE gwu.class_id is null")) {
  cli_error("Can not find classes");
}
$diffMin = "20";
foreach ($wiziqClass as $class) {
  $insertRecord = [];  
  $id = $class->course;
  $class_id = $class->class_id;
  try {
   wiziq_getattendancereport($id, $class_id, $id, $errormsg,
        $attendancexmlch_dur, $attendancexmlch_attlist); 
  } catch (\Exception $ex) {
    print_r($ex->getMessage());
    continue;
  } 
  cli_heading("Api response for class id:- " . $class->class_id . ' Response is :- ' . json_encode($attendancexmlch_attlist));
    if (empty($errormsg)) {
      foreach ($attendancexmlch_attlist->attendee as $res) {
        if (isset($class->class_id) && !empty($class->class_id)) {
          $user = $DB->get_record('user', array('firstname' => (string)$res->screen_name));
          if(!empty($user)) {
            $wiziqClass = $DB->get_record('guru_wiziq_user', array('class_id' => $class->class_id, 'user_id' => $user->id));
            if (!$wiziqClass) {
              $insertRecord['class_id'] = $class->class_id;
              $insertRecord['user_id'] = $user->id;
              $insertRecord['duration'] = (string)$res->attended_minutes." "."Minutes";
              $insertRecord['percentage'] = @$res['percentage'];
              $insertRecord['attendance'] = @$res['attendance'];
              $insertRecord['is_teacher'] = @$res['isTeacher'];
              $insertRecord['session'] = (string)$res->screen_name.','.wiziq_attendance_time($entry_time, $wiziqclass->id).','.wiziq_attendance_time($exit_time, $wiziqclass->id);
              $respQuery = $DB->insert_record('guru_wiziq_user', $insertRecord);
              if ($respQuery) {
                cli_heading("Insert record successfully.");
              }
            } else {
              $wiziqClass->duration = (string)$res->attended_minutes." "."Minutes";
              $wiziqClass->percentage = @$res['percentage'];
              $wiziqClass->attendance = @$res['attendance'];
              $wiziqClass->is_teacher = @$res['isTeacher'];
              $wiziqClass->session = (string)$res->screen_name.','.wiziq_attendance_time($entry_time, $wiziqclass->id).','.wiziq_attendance_time($exit_time, $wiziqclass->id);
              $respQuery = $DB->update_record('guru_wiziq_user', $wiziqClass);
              cli_heading("Update record successfully.");
            }
          }
        } else {
          cli_heading("Api response don't have class id:- " . $class->class_id);
        }
      }
    } else {
      cli_error("Can not find class:- " . json_encode($resp));
    }
}

echo "Braincert user migrated\n";

exit(0); // 0 means success.
