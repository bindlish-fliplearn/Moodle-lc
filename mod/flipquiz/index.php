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
 * This script lists all the instances of flipquiz in a particular course
 *
 * @package    mod_flipquiz
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
require_once("locallib.php");

$id = required_param('id', PARAM_INT);
$PAGE->set_url('/mod/flipquiz/index.php', array('id'=>$id));
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}
$coursecontext = context_course::instance($id);
require_login($course);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => $coursecontext
);
$event = \mod_flipquiz\event\course_module_instance_list_viewed::create($params);
$event->trigger();

// Print the header.
$strflipquizzes = get_string("modulenameplural", "flipquiz");
$PAGE->navbar->add($strflipquizzes);
$PAGE->set_title($strflipquizzes);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strflipquizzes, 2);

// Get all the appropriate data.
if (!$flipquizzes = get_all_instances_in_course("flipquiz", $course)) {
    notice(get_string('thereareno', 'moodle', $strflipquizzes), "../../course/view.php?id=$course->id");
    die;
}

// Check if we need the feedback header.
$showfeedback = false;
foreach ($flipquizzes as $flipquiz) {
    if (flipquiz_has_feedback($flipquiz)) {
        $showfeedback=true;
    }
    if ($showfeedback) {
        break;
    }
}

// Configure table for displaying the list of instances.
$headings = array(get_string('name'));
$align = array('left');

array_push($headings, get_string('flipquizcloses', 'flipquiz'));
array_push($align, 'left');

if (course_format_uses_sections($course->format)) {
    array_unshift($headings, get_string('sectionname', 'format_'.$course->format));
} else {
    array_unshift($headings, '');
}
array_unshift($align, 'center');

$showing = '';

if (has_capability('mod/flipquiz:viewreports', $coursecontext)) {
    array_push($headings, get_string('attempts', 'flipquiz'));
    array_push($align, 'left');
    $showing = 'stats';

} else if (has_any_capability(array('mod/flipquiz:reviewmyattempts', 'mod/flipquiz:attempt'),
        $coursecontext)) {
    array_push($headings, get_string('grade', 'flipquiz'));
    array_push($align, 'left');
    if ($showfeedback) {
        array_push($headings, get_string('feedback', 'flipquiz'));
        array_push($align, 'left');
    }
    $showing = 'grades';

    $grades = $DB->get_records_sql_menu('
            SELECT qg.flipquiz, qg.grade
            FROM {flipquiz_grades} qg
            JOIN {flipquiz} q ON q.id = qg.flipquiz
            WHERE q.course = ? AND qg.userid = ?',
            array($course->id, $USER->id));
}

$table = new html_table();
$table->head = $headings;
$table->align = $align;

// Populate the table with the list of instances.
$currentsection = '';
// Get all closing dates.
$timeclosedates = flipquiz_get_user_timeclose($course->id);
foreach ($flipquizzes as $flipquiz) {
    $cm = get_coursemodule_from_instance('flipquiz', $flipquiz->id);
    $context = context_module::instance($cm->id);
    $data = array();

    // Section number if necessary.
    $strsection = '';
    if ($flipquiz->section != $currentsection) {
        if ($flipquiz->section) {
            $strsection = $flipquiz->section;
            $strsection = get_section_name($course, $flipquiz->section);
        }
        if ($currentsection) {
            $learningtable->data[] = 'hr';
        }
        $currentsection = $flipquiz->section;
    }
    $data[] = $strsection;

    // Link to the instance.
    $class = '';
    if (!$flipquiz->visible) {
        $class = ' class="dimmed"';
    }
    $data[] = "<a$class href=\"view.php?id=$flipquiz->coursemodule\">" .
            format_string($flipquiz->name, true) . '</a>';

    // Close date.
    if (($timeclosedates[$flipquiz->id]->usertimeclose != 0)) {
        $data[] = userdate($timeclosedates[$flipquiz->id]->usertimeclose);
    } else {
        $data[] = get_string('noclose', 'flipquiz');
    }

    if ($showing == 'stats') {
        // The $flipquiz objects returned by get_all_instances_in_course have the necessary $cm
        // fields set to make the following call work.
        $data[] = flipquiz_attempt_summary_link_to_reports($flipquiz, $cm, $context);

    } else if ($showing == 'grades') {
        // Grade and feedback.
        $attempts = flipquiz_get_user_attempts($flipquiz->id, $USER->id, 'all');
        list($someoptions, $alloptions) = flipquiz_get_combined_reviewoptions(
                $flipquiz, $attempts);

        $grade = '';
        $feedback = '';
        if ($flipquiz->grade && array_key_exists($flipquiz->id, $grades)) {
            if ($alloptions->marks >= question_display_options::MARK_AND_MAX) {
                $a = new stdClass();
                $a->grade = flipquiz_format_grade($flipquiz, $grades[$flipquiz->id]);
                $a->maxgrade = flipquiz_format_grade($flipquiz, $flipquiz->grade);
                $grade = get_string('outofshort', 'flipquiz', $a);
            }
            if ($alloptions->overallfeedback) {
                $feedback = flipquiz_feedback_for_grade($grades[$flipquiz->id], $flipquiz, $context);
            }
        }
        $data[] = $grade;
        if ($showfeedback) {
            $data[] = $feedback;
        }
    }

    $table->data[] = $data;
} // End of loop over flipquiz instances.

// Display the table.
echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
