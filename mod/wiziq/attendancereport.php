<?php
// This file is part of Wiziq - http://www.wiziq.com/
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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_wiziq
 * @copyright  www.wiziq.com
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('WIZIQ_MAX_TABLE_SIZE', 10);
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/tablelib.php');

// parameter needed
$id = required_param('id', PARAM_INT);   // course
$class_id = required_param('classid', PARAM_INT);   // wiziq_class_id
$downloadattendence = optional_param('download', '', PARAM_ALPHANUMEXT);
$paging = optional_param('paging', '', PARAM_INT);
confirm_sesskey();
#------setting paging as cookie in order to have paging when page number is changed-------
if (!empty($paging)) {
    setcookie('wiziq_managecookie', $paging, time()+(86400 * 7*365));
    $attendance_per_page = $paging;
}
$sesskey = sesskey();
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$wiziqclass = $DB->get_record('wiziq',
        array('class_id' => $class_id, 'course' => $id), '*', IGNORE_MULTIPLE);
require_course_login($course);

$coursecontext = context_course::instance($course->id);
$noerror = get_string('noerror','wiziq');
$params = array(
    'objectid' => $class_id,
    'relateduserid' => $USER->id,
    'courseid' => $id,
    'context' => $coursecontext,
    'other' => array(
        'sesskey' => $sesskey,
        'error' => $noerror
    )
);
$event = \mod_wiziq\event\wiziq_classattendance::create($params);
$event->trigger();

$PAGE->set_url('/mod/wiziq/attendancereport.php',
        array('id' => $id, 'classid'=>$class_id, 'sesskey' => sesskey()));
$pagetitle = new stdClass();
$pagetitle->name =  get_string('attendance_report', 'wiziq');
$PAGE->set_title(format_string($pagetitle->name));
$wiziq_attendancereport = get_string('wiziq_attendancereport', 'wiziq');
$att_rep_title = $wiziq_attendancereport." ".$wiziqclass->name;
$PAGE->set_heading(format_string($att_rep_title));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('incourse');
$course_number = $course->id;
wiziq_getattendancereport($id, $class_id, $id, $errormsg,
        $attendancexmlch_dur, $attendancexmlch_attlist);
#------- Creation of table starts------
$table = new flexible_table('attendenreport');
$table->define_columns(array('name', 'entry_time', 'exit_time',
    'attended_minutes'));
$table->column_style_all('text-align', 'left');
$table->define_headers(array(get_string('attendee_name', 'wiziq'),
    get_string('entry_time', 'wiziq'), get_string('exit_time', 'wiziq'),
    get_string('attended_minutes', 'wiziq'),
   ));
$table->define_baseurl($PAGE->url);
$table->is_downloadable(true);
$table->download_buttons();
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->sortable(false);
$table->pageable(true);
if (isset($_COOKIE['wiziq_managecookie']) && empty($paging)) {
           $wiziq_managecookie = $_COOKIE['wiziq_managecookie'];
           $selected = $wiziq_managecookie;
           $attendance_per_page = $wiziq_managecookie;
} else if (!(isset($_COOKIE['wiziq_managecookie'])) && empty($paging)) {
    $attendance_per_page = WIZIQ_MAX_TABLE_SIZE;
    $selected = "";
} else {
    $attendance_per_page = $paging;
    $selected = $paging;
}
$attendelist = $attendancexmlch_attlist->attendee;
$total_attendence_record = count($attendelist);

$table->pagesize($attendance_per_page, $total_attendence_record);

#-----naming of the table download file----------------
$wiziq_attendence_file = get_string('attendence_file', 'wiziq');
$wiziq_attendence_file_heading = $wiziq_attendence_file." ".$course_number;
$attendance_class = get_string('wiziq_attendence_file', 'wiziq');
$wiziq_atendnc_filename = $attendance_class.$course_number;
$table->is_downloading($downloadattendence, $wiziq_atendnc_filename,
        $wiziq_attendence_file_heading);
if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    $schedulenewwiziqclass = new moodle_url("$CFG->wwwroot/course/modedit.php",
            array('add' => 'wiziq', 'type' => '', 'course' => $course->id,
                'section' => '0', 'return' => '0'));
    $navigationtabsmanage = new moodle_url("$CFG->wwwroot/mod/wiziq/index.php",
            array('id' =>  $course->id, 'sesskey' => sesskey()));
    $navigationtabscontent = new moodle_url("$CFG->wwwroot/mod/wiziq/content.php",
            array('id' => $course->id, 'sesskey' => sesskey()));
    $tabs =array();
    $row = array();
    $row[] = new tabobject('wiziq_sch_class', $schedulenewwiziqclass,
            get_string('schedule_class', 'wiziq'));
    $row[] = new tabobject('wizq_mange_class', $navigationtabsmanage,
            get_string('manage_classes', 'wiziq'));
    $row[] = new tabobject('wizq_mange_content', $navigationtabscontent,
            get_string('manage_content', 'wiziq'));
    $tabs[]=$row;
    print_tabs($tabs);

    $paging_option = new single_select($PAGE->url, "paging",
            array('5'=>'5', '10'=>'10', '15'=>'15', '20'=>'20'), $selected);

    $paging_option->label = get_string('per_page_classes', 'wiziq');
    echo $OUTPUT->render($paging_option);
}
$table->setup();

foreach ($attendelist as $value) {
    $name = (string)$value->screen_name;
    $entry_time = (string)$value->entry_time;
    $actual_entry_time = wiziq_attendance_time($entry_time, $wiziqclass->id);
    $exit_time = (string)$value->exit_time;
    $actual_exit_time = wiziq_attendance_time($exit_time, $wiziqclass->id);
    $attended_minutes = (string)$value->attended_minutes." "."Minutes";
    $table->add_data(array($name, $actual_entry_time, $actual_exit_time, $attended_minutes));
}
$table->setup();
  $table->finish_output();
echo $OUTPUT->footer();