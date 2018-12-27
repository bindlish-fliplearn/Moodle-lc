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
 * This script lists all the instances of pratest in a particular course
 *
 * @package    mod_pratest
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
require_once("locallib.php");

$id = required_param('id', PARAM_INT);
$PAGE->set_url('/mod/pratest/index.php', array('id'=>$id));
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}
$coursecontext = context_course::instance($id);
require_login($course);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => $coursecontext
);
$event = \mod_pratest\event\course_module_instance_list_viewed::create($params);
$event->trigger();

// Print the header.
$strpratestzes = get_string("modulenameplural", "pratest");
$PAGE->navbar->add($strpratestzes);
$PAGE->set_title($strpratestzes);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strpratestzes, 2);

// Get all the appropriate data.
if (!$pratestzes = get_all_instances_in_course("pratest", $course)) {
    notice(get_string('thereareno', 'moodle', $strpratestzes), "../../course/view.php?id=$course->id");
    die;
}

// Check if we need the feedback header.
$showfeedback = false;
foreach ($pratestzes as $pratest) {
    if (pratest_has_feedback($pratest)) {
        $showfeedback=true;
    }
    if ($showfeedback) {
        break;
    }
}

// Configure table for displaying the list of instances.
$headings = array(get_string('name'));
$align = array('left');

array_push($headings, get_string('pratestcloses', 'pratest'));
array_push($align, 'left');

if (course_format_uses_sections($course->format)) {
    array_unshift($headings, get_string('sectionname', 'format_'.$course->format));
} else {
    array_unshift($headings, '');
}
array_unshift($align, 'center');

$showing = '';

if (has_capability('mod/pratest:viewreports', $coursecontext)) {
    array_push($headings, get_string('attempts', 'pratest'));
    array_push($align, 'left');
    $showing = 'stats';

} else if (has_any_capability(array('mod/pratest:reviewmyattempts', 'mod/pratest:attempt'),
        $coursecontext)) {
    array_push($headings, get_string('grade', 'pratest'));
    array_push($align, 'left');
    if ($showfeedback) {
        array_push($headings, get_string('feedback', 'pratest'));
        array_push($align, 'left');
    }
    $showing = 'grades';

    $grades = $DB->get_records_sql_menu('
            SELECT qg.pratest, qg.grade
            FROM {pratest_grades} qg
            JOIN {pratest} q ON q.id = qg.pratest
            WHERE q.course = ? AND qg.userid = ?',
            array($course->id, $USER->id));
}

$table = new html_table();
$table->head = $headings;
$table->align = $align;

// Populate the table with the list of instances.
$currentsection = '';
// Get all closing dates.
$timeclosedates = pratest_get_user_timeclose($course->id);
foreach ($pratestzes as $pratest) {
    $cm = get_coursemodule_from_instance('pratest', $pratest->id);
    $context = context_module::instance($cm->id);
    $data = array();

    // Section number if necessary.
    $strsection = '';
    if ($pratest->section != $currentsection) {
        if ($pratest->section) {
            $strsection = $pratest->section;
            $strsection = get_section_name($course, $pratest->section);
        }
        if ($currentsection) {
            $learningtable->data[] = 'hr';
        }
        $currentsection = $pratest->section;
    }
    $data[] = $strsection;

    // Link to the instance.
    $class = '';
    if (!$pratest->visible) {
        $class = ' class="dimmed"';
    }
    $data[] = "<a$class href=\"view.php?id=$pratest->coursemodule\">" .
            format_string($pratest->name, true) . '</a>';

    // Close date.
    if (($timeclosedates[$pratest->id]->usertimeclose != 0)) {
        $data[] = userdate($timeclosedates[$pratest->id]->usertimeclose);
    } else {
        $data[] = get_string('noclose', 'pratest');
    }

    if ($showing == 'stats') {
        // The $pratest objects returned by get_all_instances_in_course have the necessary $cm
        // fields set to make the following call work.
        $data[] = pratest_attempt_summary_link_to_reports($pratest, $cm, $context);

    } else if ($showing == 'grades') {
        // Grade and feedback.
        $attempts = pratest_get_user_attempts($pratest->id, $USER->id, 'all');
        list($someoptions, $alloptions) = pratest_get_combined_reviewoptions(
                $pratest, $attempts);

        $grade = '';
        $feedback = '';
        if ($pratest->grade && array_key_exists($pratest->id, $grades)) {
            if ($alloptions->marks >= question_display_options::MARK_AND_MAX) {
                $a = new stdClass();
                $a->grade = pratest_format_grade($pratest, $grades[$pratest->id]);
                $a->maxgrade = pratest_format_grade($pratest, $pratest->grade);
                $grade = get_string('outofshort', 'pratest', $a);
            }
            if ($alloptions->overallfeedback) {
                $feedback = pratest_feedback_for_grade($grades[$pratest->id], $pratest, $context);
            }
        }
        $data[] = $grade;
        if ($showfeedback) {
            $data[] = $feedback;
        }
    }

    $table->data[] = $data;
} // End of loop over pratest instances.

// Display the table.
echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
