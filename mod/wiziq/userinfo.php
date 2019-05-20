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

#--------parameter needed---------



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

if(optional_param('type',null,PARAM_ALPHANUMEXT) == 'downlod')
{
$PAGE->set_url('/mod/wiziq/userinfo.php',
        array('id' => $id, 'classid'=>$class_id, 'type'=> 'downlod' ,'sesskey' => sesskey()));
} else {
   $PAGE->set_url('/mod/wiziq/userinfo.php',
        array('id' => $id, 'classid'=>$class_id, 'type'=> 'record' ,'sesskey' => sesskey())); 
}
$pagetitle = new stdClass();
$pagetitle->name =  get_string('download_pagetitle', 'wiziq');
$PAGE->set_title(format_string($pagetitle->name));
$wiziq_userrecords = get_string('wiziq_headtag', 'wiziq');
$att_rep_title = $wiziq_userrecords." => ".$wiziqclass->name;
$PAGE->set_heading(format_string($att_rep_title));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('incourse');
$course_number = $course->id;

#------- Creation of table starts------
$table = new flexible_table('userinfo');
$table->define_columns(array('name', 'time'));
$table->column_style_all('text-align', 'left');
$table->define_headers(array(get_string('dusername', 'wiziq'),
    get_string('dtime', 'wiziq')));
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
$wiziq_downlaod_file = get_string('download_file', 'wiziq');
$wiziq_downlaod_file_heading = $wiziq_downlaod_file." ".$course_number;
$download_class = get_string('wiziq_download_file', 'wiziq');
$wiziq_downlaod_filename = $download_class.$course_number;
$table->is_downloading($downloadattendence, $wiziq_downlaod_filename,
        $wiziq_downlaod_file_heading);
if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    $paging_option = new single_select($PAGE->url, "paging",
            array('5'=>'5', '10'=>'10', '15'=>'15', '20'=>'20'), $selected);
    $paging_option->label = get_string('per_page_classes', 'wiziq');
    echo $OUTPUT->render($paging_option);
}
$cContext = context_course::instance($COURSE->id);
$isStudent = current(get_user_roles($cContext, $USER->id))->shortname=='student'? true : false;

if ((is_siteadmin()) || ($presenter_id == $USER->id) || $isStudent !=1) {
$table->setup();

$classid = optional_param('classid',null,PARAM_INT);
$userid = $DB->get_record('wiziq' , array('class_id' => $classid));

if(optional_param('type',null,PARAM_ALPHANUMEXT) == 'downlod')
{
$userinfo = $DB->get_records('download_details' , array('class_id' => $userid->id));
}
else{
$userinfo = $DB->get_records('recording_details' , array('class_id' => $userid->id));
}

foreach ($userinfo as $value) {
    $name = (string)$value->username;
    $entry_time = (string)$value->time;
    $table->add_data(array($name, $entry_time));
}

$table->setup();
} else {
    
    echo "<h3>You are not authourized to access this page. </h3>";
}
$table->finish_output();
echo $OUTPUT->footer();