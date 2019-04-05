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
 * This script lists all the instances of hwork in a particular course
 *
 * @package    mod_hwork
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
require_once("locallib.php");

$id = required_param('id', PARAM_INT);
$PAGE->set_url('/mod/hwork/index.php', array('id'=>$id));
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}
$coursecontext = context_course::instance($id);
require_login($course);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => $coursecontext
);
$event = \mod_hwork\event\course_module_instance_list_viewed::create($params);
$event->trigger();

// Print the header.
$strhworkzes = get_string("modulenameplural", "hwork");
$PAGE->navbar->add($strhworkzes);
$PAGE->set_title($strhworkzes);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strhworkzes, 2);

// Get all the appropriate data.
if (!$hworkzes = get_all_instances_in_course("hwork", $course)) {
    notice(get_string('thereareno', 'moodle', $strhworkzes), "../../course/view.php?id=$course->id");
    die;
}

// Check if we need the feedback header.
$showfeedback = false;
foreach ($hworkzes as $hwork) {
    if (hwork_has_feedback($hwork)) {
        $showfeedback=true;
    }
    if ($showfeedback) {
        break;
    }
}

// Configure table for displaying the list of instances.
$headings = array(get_string('name'));
$align = array('left');

array_push($headings, get_string('hworkcloses', 'hwork'));
array_push($align, 'left');

if (course_format_uses_sections($course->format)) {
    array_unshift($headings, get_string('sectionname', 'format_'.$course->format));
} else {
    array_unshift($headings, '');
}
array_unshift($align, 'center');

$showing = '';

if (has_capability('mod/hwork:viewreports', $coursecontext)) {
    array_push($headings, get_string('attempts', 'hwork'));
    array_push($align, 'left');
    $showing = 'stats';

} else if (has_any_capability(array('mod/hwork:reviewmyattempts', 'mod/hwork:attempt'),
        $coursecontext)) {
    array_push($headings, get_string('grade', 'hwork'));
    array_push($align, 'left');
    if ($showfeedback) {
        array_push($headings, get_string('feedback', 'hwork'));
        array_push($align, 'left');
    }
    $showing = 'grades';

    $grades = $DB->get_records_sql_menu('
            SELECT qg.hwork, qg.grade
            FROM {hwork_grades} qg
            JOIN {hwork} q ON q.id = qg.hwork
            WHERE q.course = ? AND qg.userid = ?',
            array($course->id, $USER->id));
}

$table = new html_table();
$table->head = $headings;
$table->align = $align;

// Populate the table with the list of instances.
$currentsection = '';
// Get all closing dates.
$timeclosedates = hwork_get_user_timeclose($course->id);
foreach ($hworkzes as $hwork) {
    $cm = get_coursemodule_from_instance('hwork', $hwork->id);
    $context = context_module::instance($cm->id);
    $data = array();

    // Section number if necessary.
    $strsection = '';
    if ($hwork->section != $currentsection) {
        if ($hwork->section) {
            $strsection = $hwork->section;
            $strsection = get_section_name($course, $hwork->section);
        }
        if ($currentsection) {
            $learningtable->data[] = 'hr';
        }
        $currentsection = $hwork->section;
    }
    $data[] = $strsection;

    // Link to the instance.
    $class = '';
    if (!$hwork->visible) {
        $class = ' class="dimmed"';
    }
    $data[] = "<a$class href=\"view.php?id=$hwork->coursemodule\">" .
            format_string($hwork->name, true) . '</a>';

    // Close date.
    if (($timeclosedates[$hwork->id]->usertimeclose != 0)) {
        $data[] = userdate($timeclosedates[$hwork->id]->usertimeclose);
    } else {
        $data[] = get_string('noclose', 'hwork');
    }

    if ($showing == 'stats') {
        // The $hwork objects returned by get_all_instances_in_course have the necessary $cm
        // fields set to make the following call work.
        $data[] = hwork_attempt_summary_link_to_reports($hwork, $cm, $context);

    } else if ($showing == 'grades') {
        // Grade and feedback.
        $attempts = hwork_get_user_attempts($hwork->id, $USER->id, 'all');
        list($someoptions, $alloptions) = hwork_get_combined_reviewoptions(
                $hwork, $attempts);

        $grade = '';
        $feedback = '';
        if ($hwork->grade && array_key_exists($hwork->id, $grades)) {
            if ($alloptions->marks >= question_display_options::MARK_AND_MAX) {
                $a = new stdClass();
                $a->grade = hwork_format_grade($hwork, $grades[$hwork->id]);
                $a->maxgrade = hwork_format_grade($hwork, $hwork->grade);
                $grade = get_string('outofshort', 'hwork', $a);
            }
            if ($alloptions->overallfeedback) {
                $feedback = hwork_feedback_for_grade($grades[$hwork->id], $hwork, $context);
            }
        }
        $data[] = $grade;
        if ($showfeedback) {
            $data[] = $feedback;
        }
    }

    $table->data[] = $data;
} // End of loop over hwork instances.

// Display the table.
echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
