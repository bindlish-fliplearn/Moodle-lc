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
 * This script lists all the instances of homework in a particular course
 *
 * @package    mod_homework
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
require_once("locallib.php");

$id = required_param('id', PARAM_INT);
$PAGE->set_url('/mod/homework/index.php', array('id'=>$id));
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}
$coursecontext = context_course::instance($id);
require_login($course);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => $coursecontext
);
$event = \mod_homework\event\course_module_instance_list_viewed::create($params);
$event->trigger();

// Print the header.
$strhomeworkzes = get_string("modulenameplural", "homework");
$PAGE->navbar->add($strhomeworkzes);
$PAGE->set_title($strhomeworkzes);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strhomeworkzes, 2);

// Get all the appropriate data.
if (!$homeworkzes = get_all_instances_in_course("homework", $course)) {
    notice(get_string('thereareno', 'moodle', $strhomeworkzes), "../../course/view.php?id=$course->id");
    die;
}

// Check if we need the feedback header.
$showfeedback = false;
foreach ($homeworkzes as $homework) {
    if (homework_has_feedback($homework)) {
        $showfeedback=true;
    }
    if ($showfeedback) {
        break;
    }
}

// Configure table for displaying the list of instances.
$headings = array(get_string('name'));
$align = array('left');

array_push($headings, get_string('homeworkcloses', 'homework'));
array_push($align, 'left');

if (course_format_uses_sections($course->format)) {
    array_unshift($headings, get_string('sectionname', 'format_'.$course->format));
} else {
    array_unshift($headings, '');
}
array_unshift($align, 'center');

$showing = '';

if (has_capability('mod/homework:viewreports', $coursecontext)) {
    array_push($headings, get_string('attempts', 'homework'));
    array_push($align, 'left');
    $showing = 'stats';

} else if (has_any_capability(array('mod/homework:reviewmyattempts', 'mod/homework:attempt'),
        $coursecontext)) {
    array_push($headings, get_string('grade', 'homework'));
    array_push($align, 'left');
    if ($showfeedback) {
        array_push($headings, get_string('feedback', 'homework'));
        array_push($align, 'left');
    }
    $showing = 'grades';

    $grades = $DB->get_records_sql_menu('
            SELECT qg.homework, qg.grade
            FROM {homework_grades} qg
            JOIN {homework} q ON q.id = qg.homework
            WHERE q.course = ? AND qg.userid = ?',
            array($course->id, $USER->id));
}

$table = new html_table();
$table->head = $headings;
$table->align = $align;

// Populate the table with the list of instances.
$currentsection = '';
// Get all closing dates.
$timeclosedates = homework_get_user_timeclose($course->id);
foreach ($homeworkzes as $homework) {
    $cm = get_coursemodule_from_instance('homework', $homework->id);
    $context = context_module::instance($cm->id);
    $data = array();

    // Section number if necessary.
    $strsection = '';
    if ($homework->section != $currentsection) {
        if ($homework->section) {
            $strsection = $homework->section;
            $strsection = get_section_name($course, $homework->section);
        }
        if ($currentsection) {
            $learningtable->data[] = 'hr';
        }
        $currentsection = $homework->section;
    }
    $data[] = $strsection;

    // Link to the instance.
    $class = '';
    if (!$homework->visible) {
        $class = ' class="dimmed"';
    }
    $data[] = "<a$class href=\"view.php?id=$homework->coursemodule\">" .
            format_string($homework->name, true) . '</a>';

    // Close date.
    if (($timeclosedates[$homework->id]->usertimeclose != 0)) {
        $data[] = userdate($timeclosedates[$homework->id]->usertimeclose);
    } else {
        $data[] = get_string('noclose', 'homework');
    }

    if ($showing == 'stats') {
        // The $homework objects returned by get_all_instances_in_course have the necessary $cm
        // fields set to make the following call work.
        $data[] = homework_attempt_summary_link_to_reports($homework, $cm, $context);

    } else if ($showing == 'grades') {
        // Grade and feedback.
        $attempts = homework_get_user_attempts($homework->id, $USER->id, 'all');
        list($someoptions, $alloptions) = homework_get_combined_reviewoptions(
                $homework, $attempts);

        $grade = '';
        $feedback = '';
        if ($homework->grade && array_key_exists($homework->id, $grades)) {
            if ($alloptions->marks >= question_display_options::MARK_AND_MAX) {
                $a = new stdClass();
                $a->grade = homework_format_grade($homework, $grades[$homework->id]);
                $a->maxgrade = homework_format_grade($homework, $homework->grade);
                $grade = get_string('outofshort', 'homework', $a);
            }
            if ($alloptions->overallfeedback) {
                $feedback = homework_feedback_for_grade($grades[$homework->id], $homework, $context);
            }
        }
        $data[] = $grade;
        if ($showfeedback) {
            $data[] = $feedback;
        }
    }

    $table->data[] = $data;
} // End of loop over homework instances.

// Display the table.
echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
