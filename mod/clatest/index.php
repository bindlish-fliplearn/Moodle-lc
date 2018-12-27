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
 * This script lists all the instances of clatest in a particular course
 *
 * @package    mod_clatest
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
require_once("locallib.php");

$id = required_param('id', PARAM_INT);
$PAGE->set_url('/mod/clatest/index.php', array('id'=>$id));
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}
$coursecontext = context_course::instance($id);
require_login($course);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => $coursecontext
);
$event = \mod_clatest\event\course_module_instance_list_viewed::create($params);
$event->trigger();

// Print the header.
$strclatestzes = get_string("modulenameplural", "clatest");
$PAGE->navbar->add($strclatestzes);
$PAGE->set_title($strclatestzes);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strclatestzes, 2);

// Get all the appropriate data.
if (!$clatestzes = get_all_instances_in_course("clatest", $course)) {
    notice(get_string('thereareno', 'moodle', $strclatestzes), "../../course/view.php?id=$course->id");
    die;
}

// Check if we need the feedback header.
$showfeedback = false;
foreach ($clatestzes as $clatest) {
    if (clatest_has_feedback($clatest)) {
        $showfeedback=true;
    }
    if ($showfeedback) {
        break;
    }
}

// Configure table for displaying the list of instances.
$headings = array(get_string('name'));
$align = array('left');

array_push($headings, get_string('clatestcloses', 'clatest'));
array_push($align, 'left');

if (course_format_uses_sections($course->format)) {
    array_unshift($headings, get_string('sectionname', 'format_'.$course->format));
} else {
    array_unshift($headings, '');
}
array_unshift($align, 'center');

$showing = '';

if (has_capability('mod/clatest:viewreports', $coursecontext)) {
    array_push($headings, get_string('attempts', 'clatest'));
    array_push($align, 'left');
    $showing = 'stats';

} else if (has_any_capability(array('mod/clatest:reviewmyattempts', 'mod/clatest:attempt'),
        $coursecontext)) {
    array_push($headings, get_string('grade', 'clatest'));
    array_push($align, 'left');
    if ($showfeedback) {
        array_push($headings, get_string('feedback', 'clatest'));
        array_push($align, 'left');
    }
    $showing = 'grades';

    $grades = $DB->get_records_sql_menu('
            SELECT qg.clatest, qg.grade
            FROM {clatest_grades} qg
            JOIN {clatest} q ON q.id = qg.clatest
            WHERE q.course = ? AND qg.userid = ?',
            array($course->id, $USER->id));
}

$table = new html_table();
$table->head = $headings;
$table->align = $align;

// Populate the table with the list of instances.
$currentsection = '';
// Get all closing dates.
$timeclosedates = clatest_get_user_timeclose($course->id);
foreach ($clatestzes as $clatest) {
    $cm = get_coursemodule_from_instance('clatest', $clatest->id);
    $context = context_module::instance($cm->id);
    $data = array();

    // Section number if necessary.
    $strsection = '';
    if ($clatest->section != $currentsection) {
        if ($clatest->section) {
            $strsection = $clatest->section;
            $strsection = get_section_name($course, $clatest->section);
        }
        if ($currentsection) {
            $learningtable->data[] = 'hr';
        }
        $currentsection = $clatest->section;
    }
    $data[] = $strsection;

    // Link to the instance.
    $class = '';
    if (!$clatest->visible) {
        $class = ' class="dimmed"';
    }
    $data[] = "<a$class href=\"view.php?id=$clatest->coursemodule\">" .
            format_string($clatest->name, true) . '</a>';

    // Close date.
    if (($timeclosedates[$clatest->id]->usertimeclose != 0)) {
        $data[] = userdate($timeclosedates[$clatest->id]->usertimeclose);
    } else {
        $data[] = get_string('noclose', 'clatest');
    }

    if ($showing == 'stats') {
        // The $clatest objects returned by get_all_instances_in_course have the necessary $cm
        // fields set to make the following call work.
        $data[] = clatest_attempt_summary_link_to_reports($clatest, $cm, $context);

    } else if ($showing == 'grades') {
        // Grade and feedback.
        $attempts = clatest_get_user_attempts($clatest->id, $USER->id, 'all');
        list($someoptions, $alloptions) = clatest_get_combined_reviewoptions(
                $clatest, $attempts);

        $grade = '';
        $feedback = '';
        if ($clatest->grade && array_key_exists($clatest->id, $grades)) {
            if ($alloptions->marks >= question_display_options::MARK_AND_MAX) {
                $a = new stdClass();
                $a->grade = clatest_format_grade($clatest, $grades[$clatest->id]);
                $a->maxgrade = clatest_format_grade($clatest, $clatest->grade);
                $grade = get_string('outofshort', 'clatest', $a);
            }
            if ($alloptions->overallfeedback) {
                $feedback = clatest_feedback_for_grade($grades[$clatest->id], $clatest, $context);
            }
        }
        $data[] = $grade;
        if ($showfeedback) {
            $data[] = $feedback;
        }
    }

    $table->data[] = $data;
} // End of loop over clatest instances.

// Display the table.
echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
