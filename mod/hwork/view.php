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
 * This page is the entry page into the hwork UI. Displays information about the
 * hwork to students and teachers, and lets students see their previous attempts.
 *
 * @package   mod_hwork
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/mod/hwork/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/format/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or ...
$q = optional_param('q',  0, PARAM_INT);  // Quiz ID.

if ($id) {
    if (!$cm = get_coursemodule_from_id('hwork', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
} else {
    if (!$hwork = $DB->get_record('hwork', array('id' => $q))) {
        print_error('invalidhworkid', 'hwork');
    }
    if (!$course = $DB->get_record('course', array('id' => $hwork->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("hwork", $hwork->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

// Check login and get context.
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/hwork:view', $context);

// Cache some other capabilities we use several times.
$canattempt = has_capability('mod/hwork:attempt', $context);
$canreviewmine = has_capability('mod/hwork:reviewmyattempts', $context);
$canpreview = has_capability('mod/hwork:preview', $context);

// Create an object to manage all the other (non-roles) access rules.
$timenow = time();
$hworkobj = hwork::create($cm->instance, $USER->id);
$accessmanager = new hwork_access_manager($hworkobj, $timenow,
        has_capability('mod/hwork:ignoretimelimits', $context, null, false));
$hwork = $hworkobj->get_hwork();

// Trigger course_module_viewed event and completion.
hwork_view($hwork, $course, $cm, $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/hwork/view.php', array('id' => $cm->id));

// Create view object which collects all the information the renderer will need.
$viewobj = new mod_hwork_view_object();
$viewobj->accessmanager = $accessmanager;
$viewobj->canreviewmine = $canreviewmine || $canpreview;

// Get this user's attempts.
$attempts = hwork_get_user_attempts($hwork->id, $USER->id, 'finished', true);
$lastfinishedattempt = end($attempts);
$unfinished = false;
$unfinishedattemptid = null;
if ($unfinishedattempt = hwork_get_user_attempt_unfinished($hwork->id, $USER->id)) {
    $attempts[] = $unfinishedattempt;

    // If the attempt is now overdue, deal with that - and pass isonline = false.
    // We want the student notified in this case.
    $hworkobj->create_attempt_object($unfinishedattempt)->handle_if_time_expired(time(), false);

    $unfinished = $unfinishedattempt->state == hwork_attempt::IN_PROGRESS ||
            $unfinishedattempt->state == hwork_attempt::OVERDUE;
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
    $viewobj->attemptobjs[] = new hwork_attempt($attempt, $hwork, $cm, $course, false);
}

// Work out the final grade, checking whether it was overridden in the gradebook.
if (!$canpreview) {
    $mygrade = hwork_get_best_grade($hwork, $USER->id);
} else if ($lastfinishedattempt) {
    // Users who can preview the hwork don't get a proper grade, so work out a
    // plausible value to display instead, so the page looks right.
    $mygrade = hwork_rescale_grade($lastfinishedattempt->sumgrades, $hwork, false);
} else {
    $mygrade = null;
}

$mygradeoverridden = false;
$gradebookfeedback = '';

$grading_info = grade_get_grades($course->id, 'mod', 'hwork', $hwork->id, $USER->id);
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

$title = $course->shortname . ': ' . format_string($hwork->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$output = $PAGE->get_renderer('mod_hwork');

// Print table with existing attempts.
if ($attempts) {
    // Work out which columns we need, taking account what data is available in each attempt.
    list($someoptions, $alloptions) = hwork_get_combined_reviewoptions($hwork, $attempts);

    $viewobj->attemptcolumn  = $hwork->attempts != 1;

    $viewobj->gradecolumn    = $someoptions->marks >= question_display_options::MARK_AND_MAX &&
            hwork_has_grades($hwork);
    $viewobj->markcolumn     = $viewobj->gradecolumn && ($hwork->grade != $hwork->sumgrades);
    $viewobj->overallstats   = $lastfinishedattempt && $alloptions->marks >= question_display_options::MARK_AND_MAX;

    $viewobj->feedbackcolumn = hwork_has_feedback($hwork) && $alloptions->overallfeedback;
}

$viewobj->timenow = $timenow;
$viewobj->numattempts = $numattempts;
$viewobj->mygrade = $mygrade;
$viewobj->moreattempts = $unfinished ||
        !$accessmanager->is_finished($numattempts, $lastfinishedattempt);
$viewobj->mygradeoverridden = $mygradeoverridden;
$viewobj->gradebookfeedback = $gradebookfeedback;
$viewobj->lastfinishedattempt = $lastfinishedattempt;
$viewobj->canedit = has_capability('mod/hwork:manage', $context);
$viewobj->editurl = new moodle_url('/mod/hwork/edit.php', array('cmid' => $cm->id));
$viewobj->backtocourseurl = new moodle_url('/course/view.php', array('id' => $course->id));
$viewobj->startattempturl = $hworkobj->start_attempt_url();

if ($accessmanager->is_preflight_check_required($unfinishedattemptid)) {
    $viewobj->preflightcheckform = $accessmanager->get_preflight_check_form(
            $viewobj->startattempturl, $unfinishedattemptid);
}
$viewobj->popuprequired = $accessmanager->attempt_must_be_in_popup();
$viewobj->popupoptions = $accessmanager->get_popup_options();

// Display information about this hwork.
$viewobj->infomessages = $viewobj->accessmanager->describe_rules();
if ($hwork->attempts != 1) {
    $viewobj->infomessages[] = get_string('gradingmethod', 'hwork',
            hwork_get_grading_option_name($hwork->grademethod));
}

// Determine wheter a start attempt button should be displayed.
$viewobj->hworkhasquestions = $hworkobj->has_questions();
$viewobj->preventmessages = array();
if (!$viewobj->hworkhasquestions) {
    $viewobj->buttontext = '';

} else {
    if ($unfinished) {
        if ($canattempt) {
            $viewobj->buttontext = get_string('continueattempthwork', 'hwork');
        } else if ($canpreview) {
            $viewobj->buttontext = get_string('continuepreview', 'hwork');
        }

    } else {
        if ($canattempt) {
            $viewobj->preventmessages = $viewobj->accessmanager->prevent_new_attempt(
                    $viewobj->numattempts, $viewobj->lastfinishedattempt);
            if ($viewobj->preventmessages) {
                $viewobj->buttontext = '';
            } else if ($viewobj->numattempts == 0) {
                $viewobj->buttontext = get_string('attempthworknow', 'hwork');
            } else {
                $viewobj->buttontext = get_string('reattempthwork', 'hwork');
            }

        } else if ($canpreview) {
            $viewobj->buttontext = get_string('previewhworknow', 'hwork');
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
    // Guests can't do a hwork, so offer them a choice of logging in or going back.
    echo $output->view_page_guest($course, $hwork, $cm, $context, $viewobj->infomessages);
} else if (!isguestuser() && !($canattempt || $canpreview
          || $viewobj->canreviewmine)) {
    // If they are not enrolled in this course in a good enough role, tell them to enrol.
    echo $output->view_page_notenrolled($course, $hwork, $cm, $context, $viewobj->infomessages);
} else {
    echo $output->view_page($course, $hwork, $cm, $context, $viewobj);
}

echo $OUTPUT->footer();
