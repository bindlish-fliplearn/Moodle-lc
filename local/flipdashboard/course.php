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
 * Local plugin "flipdashboard" - Enrolment page
 *
 * @package   local_flipdashboard
 * @copyright 2017 Soon Systems GmbH on behalf of Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);

$context = context_system::instance();

if (!empty($id)) {
    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    if (!$course) {
      print_error('invalidcourseid');
    }
    $context = context_course::instance($course->id, MUST_EXIST);

    $PAGE->set_context($context);
    $PAGE->set_url('/local/flipdashboard/course.php', array('id' => $id));
    $PAGE->set_title("$course->shortname: ".get_string('pluginname', 'local_flipdashboard'));
    $PAGE->set_heading($course->fullname);

    require_login($course);
}

$braincertClasses = $DB->get_records('braincert', array('course' => $id));
if (!$braincertClasses) {
  print_error('No Class Existest in this course.');
}

$sql = "SELECT mra.userid
        FROM {context} As mc 
        INNER JOIN {role_assignments} AS mra 
        ON mc.id = mra.contextid 
        WHERE mra.userid 
        iN(SELECT ue.userid FROM mdl_enrol 
        AS me INNER JOIN mdl_user_enrolments 
        AS ue ON me.id = ue.enrolid 
        WHERE me.courseid = $id AND ue.status = 0)";
$result = $DB->get_records_sql($sql);

$userCount = count($result);
$PAGE->set_pagelayout('incourse');

$PAGE->navbar->add(get_string('pluginname', 'local_flipdashboard'));
$attendancereport = get_string('attendancereport', 'local_flipdashboard');
$PAGE->navbar->add($attendancereport);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('classattendees', 'local_flipdashboard'));

$table = new html_table();
$table->head = array ();
$table->head[] = get_string('sno', 'local_flipdashboard');
$table->head[] = get_string('name', 'local_flipdashboard');
$table->head[] = get_string('startdate', 'local_flipdashboard');
$table->head[] = get_string('timein', 'local_flipdashboard');
$table->head[] = get_string('timeout', 'local_flipdashboard');
$table->head[] = get_string('totaluser', 'local_flipdashboard');
$table->head[] = get_string('recording', 'local_flipdashboard');
$i = 1;
foreach ($braincertClasses as $classes) {
    $row = array ();
    $url = $CFG->wwwroot.'/mod/braincert/recording.php?action=viewrecording&bcid='.$classes->class_id;
    $stdurl = $CFG->wwwroot.'/local/flipdashboard/course_student.php?bcid='.$classes->class_id.'&courseid='.$classes->course;
    $row[] = $i;
    $row[] = $classes->name;
    $row[] = date('d-M-Y', $classes->start_date);
    $row[] = $classes->start_time;
    $row[] = $classes->end_time;
    $row[] = "<a href='$stdurl' target='_blank'>".countAttendance($classes->class_id, $classes->course).'/'.$userCount."</a>";
    $row[] = "<a href='$url' target='_blank'>View</a>";
    $table->data[] = $row;
    $i++;
}

if (!empty($table)) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow display-table attendeestable'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();