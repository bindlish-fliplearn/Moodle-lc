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
 * This page is the entry page into the pratest UI. Displays information about the
 * pratest to students and teachers, and lets students see their previous attempts.
 *
 * @package   mod_pratest
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/mod/pratest/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/format/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or ...
$q = optional_param('q',  0, PARAM_INT);  // Quiz ID.

if ($id) {
    if (!$cm = get_coursemodule_from_id('pratest', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
} else {
    if (!$pratest = $DB->get_record('pratest', array('id' => $q))) {
        print_error('invalidpratestid', 'pratest');
    }
    if (!$course = $DB->get_record('course', array('id' => $pratest->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("pratest", $pratest->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

// Check login and get context.
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pratest:view', $context);

// Cache some other capabilities we use several times.
$canattempt = has_capability('mod/pratest:attempt', $context);
$canreviewmine = has_capability('mod/pratest:reviewmyattempts', $context);
$canpreview = has_capability('mod/pratest:preview', $context);

// Create an object to manage all the other (non-roles) access rules.
$timenow = time();
$pratestobj = pratest::create($cm->instance, $USER->id);
$accessmanager = new pratest_access_manager($pratestobj, $timenow,
        has_capability('mod/pratest:ignoretimelimits', $context, null, false));
$pratest = $pratestobj->get_pratest();

// Trigger course_module_viewed event and completion.
pratest_view($pratest, $course, $cm, $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/pratest/view.php', array('id' => $cm->id));

// Create view object which collects all the information the renderer will need.
$viewobj = new mod_pratest_view_object();
$viewobj->accessmanager = $accessmanager;
$viewobj->canreviewmine = $canreviewmine || $canpreview;

// Get this user's attempts.
$attempts = pratest_get_user_attempts($pratest->id, $USER->id, 'finished', true);
$lastfinishedattempt = end($attempts);
$unfinished = false;
$unfinishedattemptid = null;
if ($unfinishedattempt = pratest_get_user_attempt_unfinished($pratest->id, $USER->id)) {
    $attempts[] = $unfinishedattempt;

    // If the attempt is now overdue, deal with that - and pass isonline = false.
    // We want the student notified in this case.
    $pratestobj->create_attempt_object($unfinishedattempt)->handle_if_time_expired(time(), false);

    $unfinished = $unfinishedattempt->state == pratest_attempt::IN_PROGRESS ||
            $unfinishedattempt->state == pratest_attempt::OVERDUE;
    if (!$unfinished) {
        $lastfinishedattempt = $unfinishedattempt;
    }
    $unfinishedattemptid = $unfinishedattempt->id;
    $unfinishedattempt = null; // To make it clear we do not use this again.
}
$numattempts = count($attempts);

$viewobj->attempts = $attempts;
$viewobj->attemptobjs = array();
foreach ($attempts as $attempt) {
    $viewobj->attemptobjs[] = new pratest_attempt($attempt, $pratest, $cm, $course, false);
}

// Work out the final grade, checking whether it was overridden in the gradebook.
if (!$canpreview) {
    $mygrade = pratest_get_best_grade($pratest, $USER->id);
} else if ($lastfinishedattempt) {
    // Users who can preview the pratest don't get a proper grade, so work out a
    // plausible value to display instead, so the page looks right.
    $mygrade = pratest_rescale_grade($lastfinishedattempt->sumgrades, $pratest, false);
} else {
    $mygrade = null;
}

$mygradeoverridden = false;
$gradebookfeedback = '';

$grading_info = grade_get_grades($course->id, 'mod', 'pratest', $pratest->id, $USER->id);
if (!empty($grading_info->items)) {
    $item = $grading_info->items[0];
    if (isset($item->grades[$USER->id])) {
        $grade = $item->grades[$USER->id];

        if ($grade->overridden) {
            $mygrade = $grade->grade + 0; // Convert to number.
            $mygradeoverridden = true;
        }
        if (!empty($grade->str_feedback)) {
            $gradebookfeedback = $grade->str_feedback;
        }
    }
}

$title = $course->shortname . ': ' . format_string($pratest->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$output = $PAGE->get_renderer('mod_pratest');

// Print table with existing attempts.
if ($attempts) {
    // Work out which columns we need, taking account what data is available in each attempt.
    list($someoptions, $alloptions) = pratest_get_combined_reviewoptions($pratest, $attempts);

    $viewobj->attemptcolumn  = $pratest->attempts != 1;

    $viewobj->gradecolumn    = $someoptions->marks >= question_display_options::MARK_AND_MAX &&
            pratest_has_grades($pratest);
    $viewobj->markcolumn     = $viewobj->gradecolumn && ($pratest->grade != $pratest->sumgrades);
    $viewobj->overallstats   = $lastfinishedattempt && $alloptions->marks >= question_display_options::MARK_AND_MAX;

    $viewobj->feedbackcolumn = pratest_has_feedback($pratest) && $alloptions->overallfeedback;
}

$viewobj->timenow = $timenow;
$viewobj->numattempts = $numattempts;
$viewobj->mygrade = $mygrade;
$viewobj->moreattempts = $unfinished ||
        !$accessmanager->is_finished($numattempts, $lastfinishedattempt);
$viewobj->mygradeoverridden = $mygradeoverridden;
$viewobj->gradebookfeedback = $gradebookfeedback;
$viewobj->lastfinishedattempt = $lastfinishedattempt;
$viewobj->canedit = has_capability('mod/pratest:manage', $context);
$viewobj->editurl = new moodle_url('/mod/pratest/edit.php', array('cmid' => $cm->id));
$viewobj->backtocourseurl = new moodle_url('/course/view.php', array('id' => $course->id));
$viewobj->startattempturl = $pratestobj->start_attempt_url();

if ($accessmanager->is_preflight_check_required($unfinishedattemptid)) {
    $viewobj->preflightcheckform = $accessmanager->get_preflight_check_form(
            $viewobj->startattempturl, $unfinishedattemptid);
}
$viewobj->popuprequired = $accessmanager->attempt_must_be_in_popup();
$viewobj->popupoptions = $accessmanager->get_popup_options();

// Display information about this pratest.
$viewobj->infomessages = $viewobj->accessmanager->describe_rules();
if ($pratest->attempts != 1) {
    $viewobj->infomessages[] = get_string('gradingmethod', 'pratest',
            pratest_get_grading_option_name($pratest->grademethod));
}

// Determine wheter a start attempt button should be displayed.
$viewobj->pratesthasquestions = $pratestobj->has_questions();
$viewobj->preventmessages = array();
if (!$viewobj->pratesthasquestions) {
    $viewobj->buttontext = '';

} else {
    if ($unfinished) {
        if ($canattempt) {
            $viewobj->buttontext = get_string('continueattemptpratest', 'pratest');
        } else if ($canpreview) {
            $viewobj->buttontext = get_string('continuepreview', 'pratest');
        }

    } else {
        if ($canattempt) {
            $viewobj->preventmessages = $viewobj->accessmanager->prevent_new_attempt(
                    $viewobj->numattempts, $viewobj->lastfinishedattempt);
            if ($viewobj->preventmessages) {
                $viewobj->buttontext = '';
            } else if ($viewobj->numattempts == 0) {
                $viewobj->buttontext = get_string('attemptpratestnow', 'pratest');
            } else {
                $viewobj->buttontext = get_string('reattemptpratest', 'pratest');
            }

        } else if ($canpreview) {
            $viewobj->buttontext = get_string('previewpratestnow', 'pratest');
        }
    }

    // If, so far, we think a button should be printed, so check if they will be
    // allowed to access it.
    if ($viewobj->buttontext) {
        if (!$viewobj->moreattempts) {
            $viewobj->buttontext = '';
        } else if ($canattempt
                && $viewobj->preventmessages = $viewobj->accessmanager->prevent_access()) {
            $viewobj->buttontext = '';
        }
    }
}

$viewobj->showbacktocourse = ($viewobj->buttontext === '' &&
        course_get_format($course)->has_view_page());

echo $OUTPUT->header();

if (isguestuser()) {
    // Guests can't do a pratest, so offer them a choice of logging in or going back.
    echo $output->view_page_guest($course, $pratest, $cm, $context, $viewobj->infomessages);
} else if (!isguestuser() && !($canattempt || $canpreview
          || $viewobj->canreviewmine)) {
    // If they are not enrolled in this course in a good enough role, tell them to enrol.
    echo $output->view_page_notenrolled($course, $pratest, $cm, $context, $viewobj->infomessages);
} else {
    echo $output->view_page($course, $pratest, $cm, $context, $viewobj);
}

echo $OUTPUT->footer();
