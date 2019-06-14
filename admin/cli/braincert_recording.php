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
  $help = "Migrate braincert recording video url in moodle database

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

if (!$braincertClass = $DB->get_records('braincert')) {
  cli_error("Can not find classes");
}
foreach ($braincertClass as $class) {
  $insertRecord = [];
  $data['task'] = 'getclassrecording';
  $data['class_id'] = $class->class_id;
  $resp = braincert_get_curl_info($data);
  cli_heading("Api response for class id:- " . $class->class_id . ' Response is :- ' . json_encode($resp));
  if (!empty($resp)) {
    foreach ($resp as $res) {
      if (isset($res['classroom_id']) && !empty($res['classroom_id'])) {
        $braincertClass = $DB->get_records('guru_braincert_recording', array('class_id' => $res['classroom_id'], 'user_id' => $res['userId']));
        if (!$braincertClass) {
          $insertRecord['class_id'] = $res['classroom_id'];
          $insertRecord['user_id'] = $res['user_id'];
          $insertRecord['name'] = $res['name'];
          $insertRecord['date_recorded'] = date( 'Y-m-d H:i:s', strtotime($res['date_recorded']));
          $insertRecord['size'] = $res['size'];
          $insertRecord['is_public'] = $res['is_public'];
          $insertRecord['allow_download'] = $res['allow_download'];
          $insertRecord['privatekey'] = $res['privatekey'];
          $insertRecord['record_path'] = $res['record_path'];
          $insertRecord['record_url'] = $res['record_url'];
          $respQuery = $DB->insert_record('guru_braincert_recording', $insertRecord);
          if ($respQuery) {
            cli_heading("Insert record successfully.");
          }
        } else {
            $braincertClass['date_recorded'] = date( 'Y-m-d H:i:s', strtotime($res['date_recorded']));
            $braincertClass['size'] = $res['size'];
            $braincertClass['is_public'] = $res['is_public'];
            $insertRecord['allow_download'] = $res['allow_download'];
            $insertRecord['privatekey'] = $res['privatekey'];
            $insertRecord['record_path'] = $res['record_path'];
            $insertRecord['record_url'] = $res['record_url'];
            cli_heading("Update record successfully.");
        }
      } else {
        cli_heading("Api response don't have class id:- ". $class->class_id);
      }
    }
  } else {
    cli_error("Can not find class:- " . json_encode($resp));
  }
}

echo "Braincert user migrated\n";

exit(0); // 0 means success.
