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
 * This page is the entry page into the clatest UI. Displays information about the
 * clatest to students and teachers, and lets students see their previous attempts.
 *
 * @package   mod_clatest
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/mod/clatest/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/format/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or ...
$q = optional_param('q',  0, PARAM_INT);  // Quiz ID.

if ($id) {
    if (!$cm = get_coursemodule_from_id('clatest', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
} else {
    if (!$clatest = $DB->get_record('clatest', array('id' => $q))) {
        print_error('invalidclatestid', 'clatest');
    }
    if (!$course = $DB->get_record('course', array('id' => $clatest->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("clatest", $clatest->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

// Check login and get context.
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/clatest:view', $context);

// Cache some other capabilities we use several times.
$canattempt = has_capability('mod/clatest:attempt', $context);
$canreviewmine = has_capability('mod/clatest:reviewmyattempts', $context);
$canpreview = has_capability('mod/clatest:preview', $context);

// Create an object to manage all the other (non-roles) access rules.
$timenow = time();
$clatestobj = clatest::create($cm->instance, $USER->id);
$accessmanager = new clatest_access_manager($clatestobj, $timenow,
        has_capability('mod/clatest:ignoretimelimits', $context, null, false));
$clatest = $clatestobj->get_clatest();

// Trigger course_module_viewed event and completion.
clatest_view($clatest, $course, $cm, $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/clatest/view.php', array('id' => $cm->id));

// Create view object which collects all the information the renderer will need.
$viewobj = new mod_clatest_view_object();
$viewobj->accessmanager = $accessmanager;
$viewobj->canreviewmine = $canreviewmine || $canpreview;

// Get this user's attempts.
$attempts = clatest_get_user_attempts($clatest->id, $USER->id, 'finished', true);
$lastfinishedattempt = end($attempts);
$unfinished = false;
$unfinishedattemptid = null;
if ($unfinishedattempt = clatest_get_user_attempt_unfinished($clatest->id, $USER->id)) {
    $attempts[] = $unfinishedattempt;

    // If the attempt is now overdue, deal with that - and pass isonline = false.
    // We want the student notified in this case.
    $clatestobj->create_attempt_object($unfinishedattempt)->handle_if_time_expired(time(), false);

    $unfinished = $unfinishedattempt->state == clatest_attempt::IN_PROGRESS ||
            $unfinishedattempt->state == clatest_attempt::OVERDUE;
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
    $viewobj->attemptobjs[] = new clatest_attempt($attempt, $clatest, $cm, $course, false);
}

// Work out the final grade, checking whether it was overridden in the gradebook.
if (!$canpreview) {
    $mygrade = clatest_get_best_grade($clatest, $USER->id);
} else if ($lastfinishedattempt) {
    // Users who can preview the clatest don't get a proper grade, so work out a
    // plausible value to display instead, so the page looks right.
    $mygrade = clatest_rescale_grade($lastfinishedattempt->sumgrades, $clatest, false);
} else {
    $mygrade = null;
}

$mygradeoverridden = false;
$gradebookfeedback = '';

$grading_info = grade_get_grades($course->id, 'mod', 'clatest', $clatest->id, $USER->id);
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

$title = $course->shortname . ': ' . format_string($clatest->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$output = $PAGE->get_renderer('mod_clatest');

// Print table with existing attempts.
if ($attempts) {
    // Work out which columns we need, taking account what data is available in each attempt.
    list($someoptions, $alloptions) = clatest_get_combined_reviewoptions($clatest, $attempts);

    $viewobj->attemptcolumn  = $clatest->attempts != 1;

    $viewobj->gradecolumn    = $someoptions->marks >= question_display_options::MARK_AND_MAX &&
            clatest_has_grades($clatest);
    $viewobj->markcolumn     = $viewobj->gradecolumn && ($clatest->grade != $clatest->sumgrades);
    $viewobj->overallstats   = $lastfinishedattempt && $alloptions->marks >= question_display_options::MARK_AND_MAX;

    $viewobj->feedbackcolumn = clatest_has_feedback($clatest) && $alloptions->overallfeedback;
}

$viewobj->timenow = $timenow;
$viewobj->numattempts = $numattempts;
$viewobj->mygrade = $mygrade;
$viewobj->moreattempts = $unfinished ||
        !$accessmanager->is_finished($numattempts, $lastfinishedattempt);
$viewobj->mygradeoverridden = $mygradeoverridden;
$viewobj->gradebookfeedback = $gradebookfeedback;
$viewobj->lastfinishedattempt = $lastfinishedattempt;
$viewobj->canedit = has_capability('mod/clatest:manage', $context);
$viewobj->editurl = new moodle_url('/mod/clatest/edit.php', array('cmid' => $cm->id));
$viewobj->backtocourseurl = new moodle_url('/course/view.php', array('id' => $course->id));
$viewobj->startattempturl = $clatestobj->start_attempt_url();

if ($accessmanager->is_preflight_check_required($unfinishedattemptid)) {
    $viewobj->preflightcheckform = $accessmanager->get_preflight_check_form(
            $viewobj->startattempturl, $unfinishedattemptid);
}
$viewobj->popuprequired = $accessmanager->attempt_must_be_in_popup();
$viewobj->popupoptions = $accessmanager->get_popup_options();

// Display information about this clatest.
$viewobj->infomessages = $viewobj->accessmanager->describe_rules();
if ($clatest->attempts != 1) {
    $viewobj->infomessages[] = get_string('gradingmethod', 'clatest',
            clatest_get_grading_option_name($clatest->grademethod));
}

// Determine wheter a start attempt button should be displayed.
$viewobj->clatesthasquestions = $clatestobj->has_questions();
$viewobj->preventmessages = array();
if (!$viewobj->clatesthasquestions) {
    $viewobj->buttontext = '';

} else {
    if ($unfinished) {
        if ($canattempt) {
            $viewobj->buttontext = get_string('continueattemptclatest', 'clatest');
        } else if ($canpreview) {
            $viewobj->buttontext = get_string('continuepreview', 'clatest');
        }

    } else {
        if ($canattempt) {
            $viewobj->preventmessages = $viewobj->accessmanager->prevent_new_attempt(
                    $viewobj->numattempts, $viewobj->lastfinishedattempt);
            if ($viewobj->preventmessages) {
                $viewobj->buttontext = '';
            } else if ($viewobj->numattempts == 0) {
                $viewobj->buttontext = get_string('attemptclatestnow', 'clatest');
            } else {
                $viewobj->buttontext = get_string('reattemptclatest', 'clatest');
            }

        } else if ($canpreview) {
            $viewobj->buttontext = get_string('previewclatestnow', 'clatest');
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
    // Guests can't do a clatest, so offer them a choice of logging in or going back.
    echo $output->view_page_guest($course, $clatest, $cm, $context, $viewobj->infomessages);
} else if (!isguestuser() && !($canattempt || $canpreview
          || $viewobj->canreviewmine)) {
    // If they are not enrolled in this course in a good enough role, tell them to enrol.
    echo $output->view_page_notenrolled($course, $clatest, $cm, $context, $viewobj->infomessages);
} else {
    echo $output->view_page($course, $clatest, $cm, $context, $viewobj);
}

echo $OUTPUT->footer();