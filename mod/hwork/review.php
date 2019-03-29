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
 * This page prints a review of a particular hwork attempt
 *
 * It is used either by the student whose attempts this is, after the attempt,
 * or by a teacher reviewing another's attempt during or afterwards.
 *
 * @package   mod_hwork
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/hwork/locallib.php');
require_once($CFG->dirroot . '/mod/hwork/report/reportlib.php');

$attemptid = required_param('attempt', PARAM_INT);
$page      = optional_param('page', 0, PARAM_INT);
$showall   = optional_param('showall', null, PARAM_BOOL);
$cmid      = optional_param('cmid', null, PARAM_INT);

$url = new moodle_url('/mod/hwork/review.php', array('attempt'=>$attemptid));
if ($page !== 0) {
    $url->param('page', $page);
} else if ($showall) {
    $url->param('showall', $showall);
}
$PAGE->set_url($url);

$attemptobj = hwork_create_attempt_handling_errors($attemptid, $cmid);
$page = $attemptobj->force_page_number_into_range($page);

// Now we can validate the params better, re-genrate the page URL.
if ($showall === null) {
    $showall = $page == 0 && $attemptobj->get_default_show_all('review');
}
$PAGE->set_url($attemptobj->review_url(null, $page, $showall));

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
$attemptobj->check_review_capability();

// Create an object to manage all the other (non-roles) access rules.
$accessmanager = $attemptobj->get_access_manager(time());
$accessmanager->setup_attempt_page($PAGE);

$options = $attemptobj->get_display_options(true);

// Check permissions - warning there is similar code in reviewquestion.php and
// hwork_attempt::check_file_access. If you change on, change them all.
if ($attemptobj->is_own_attempt()) {
    if (!$attemptobj->is_finished()) {
        redirect($attemptobj->attempt_url(null, $page));

    } else if (!$options->attempt) {
        $accessmanager->back_to_view_page($PAGE->get_renderer('mod_hwork'),
                $attemptobj->cannot_review_message());
    }

} else if (!$attemptobj->is_review_allowed()) {
    throw new moodle_hwork_exception($attemptobj->get_hworkobj(), 'noreviewattempt');
}

// Load the questions and states needed by this page.
if ($showall) {
    $questionids = $attemptobj->get_slots();
} else {
    $questionids = $attemptobj->get_slots($page);
}

// Save the flag states, if they are being changed.
if ($options->flags == question_display_options::EDITABLE && optional_param('savingflags', false,
        PARAM_BOOL)) {
    require_sesskey();
    $attemptobj->save_question_flags();
    redirect($attemptobj->review_url(null, $page, $showall));
}

// Work out appropriate title and whether blocks should be shown.
if ($attemptobj->is_own_preview()) {
    $strreviewtitle = get_string('reviewofpreview', 'hwork');
    navigation_node::override_active_url($attemptobj->start_attempt_url());

} else {
    $strreviewtitle = get_string('reviewofattempt', 'hwork', $attemptobj->get_attempt_number());
    if (empty($attemptobj->get_hwork()->showblocks) && !$attemptobj->is_preview_user()) {
        $PAGE->blocks->show_only_fake_blocks();
    }
}

// Set up the page header.
$headtags = $attemptobj->get_html_head_contributions($page, $showall);
$PAGE->set_title($attemptobj->get_hwork_name());
$PAGE->set_heading($attemptobj->get_course()->fullname);

// Summary table start. ============================================================================

// Work out some time-related things.
$attempt = $attemptobj->get_attempt();
$hwork = $attemptobj->get_hwork();
$overtime = 0;

if ($attempt->state == hwork_attempt::FINISHED) {
    if ($timetaken = ($attempt->timefinish - $attempt->timestart)) {
        if ($hwork->timelimit && $timetaken > ($hwork->timelimit + 60)) {
            $overtime = $timetaken - $hwork->timelimit;
            $overtime = format_time($overtime);
        }
        $timetaken = format_time($timetaken);
    } else {
        $timetaken = "-";
    }
} else {
    $timetaken = get_string('unfinished', 'hwork');
}

// Prepare summary informat about the whole attempt.
$summarydata = array();
if (!$attemptobj->get_hwork()->showuserpicture && $attemptobj->get_userid() != $USER->id) {
    // If showuserpicture is true, the picture is shown elsewhere, so don't repeat it.
    $student = $DB->get_record('user', array('id' => $attemptobj->get_userid()));
    $userpicture = new user_picture($student);
    $userpicture->courseid = $attemptobj->get_courseid();
    $summarydata['user'] = array(
        'title'   => $userpicture,
        'content' => new action_link(new moodle_url('/user/view.php', array(
                                'id' => $student->id, 'course' => $attemptobj->get_courseid())),
                          fullname($student, true)),
    );
}

if ($attemptobj->has_capability('mod/hwork:viewreports')) {
    $attemptlist = $attemptobj->links_to_other_attempts($attemptobj->review_url(null, $page,
            $showall));
    if ($attemptlist) {
        $summarydata['attemptlist'] = array(
            'title'   => get_string('attempts', 'hwork'),
            'content' => $attemptlist,
        );
    }
}

// Timing information.
$summarydata['startedon'] = array(
    'title'   => get_string('startedon', 'hwork'),
    'content' => userdate($attempt->timestart),
);

$summarydata['state'] = array(
    'title'   => get_string('attemptstate', 'hwork'),
    'content' => hwork_attempt::state_name($attempt->state),
);

if ($attempt->state == hwork_attempt::FINISHED) {
    $summarydata['completedon'] = array(
        'title'   => get_string('completedon', 'hwork'),
        'content' => userdate($attempt->timefinish),
    );
    $summarydata['timetaken'] = array(
        'title'   => get_string('timetaken', 'hwork'),
        'content' => $timetaken,
    );
}

if (!empty($overtime)) {
    $summarydata['overdue'] = array(
        'title'   => get_string('overdue', 'hwork'),
        'content' => $overtime,
    );
}

// Show marks (if the user is allowed to see marks at the moment).
$grade = hwork_rescale_grade($attempt->sumgrades, $hwork, false);
if ($options->marks >= question_display_options::MARK_AND_MAX && hwork_has_grades($hwork)) {

    if ($attempt->state != hwork_attempt::FINISHED) {
        // Cannot display grade.

    } else if (is_null($grade)) {
        $summarydata['grade'] = array(
            'title'   => get_string('grade', 'hwork'),
            'content' => hwork_format_grade($hwork, $grade),
        );

    } else {
        // Show raw marks only if they are different from the grade (like on the view page).
        if ($hwork->grade != $hwork->sumgrades) {
            $a = new stdClass();
            $a->grade = hwork_format_grade($hwork, $attempt->sumgrades);
            $a->maxgrade = hwork_format_grade($hwork, $hwork->sumgrades);
            $summarydata['marks'] = array(
                'title'   => get_string('marks', 'hwork'),
                'content' => get_string('outofshort', 'hwork', $a),
            );
        }

        // Now the scaled grade.
        $a = new stdClass();
        $a->grade = html_writer::tag('b', hwork_format_grade($hwork, $grade));
        $a->maxgrade = hwork_format_grade($hwork, $hwork->grade);
        if ($hwork->grade != 100) {
            $a->percent = html_writer::tag('b', format_float(
                    $attempt->sumgrades * 100 / $hwork->sumgrades, 0));
            $formattedgrade = get_string('outofpercent', 'hwork', $a);
        } else {
            $formattedgrade = get_string('outof', 'hwork', $a);
        }
        $summarydata['grade'] = array(
            'title'   => get_string('grade', 'hwork'),
            'content' => $formattedgrade,
        );
    }
}

// Any additional summary data from the behaviour.
$summarydata = array_merge($summarydata, $attemptobj->get_additional_summary_data($options));

// Feedback if there is any, and the user is allowed to see it now.
$feedback = $attemptobj->get_overall_feedback($grade);
if ($options->overallfeedback && $feedback) {
    $summarydata['feedback'] = array(
        'title'   => get_string('feedback', 'hwork'),
        'content' => $feedback,
    );
}

// Summary table end. ==============================================================================

if ($showall) {
    $slots = $attemptobj->get_slots();
    $lastpage = true;
} else {
    $slots = $attemptobj->get_slots($page);
    $lastpage = $attemptobj->is_last_page($page);
}

$output = $PAGE->get_renderer('mod_hwork');

// Arrange for the navigation to be displayed.
$navbc = $attemptobj->get_navigation_panel($output, 'hwork_review_nav_panel', $page, $showall);
$regions = $PAGE->blocks->get_regions();
$PAGE->blocks->add_fake_block($navbc, reset($regions));

echo $output->review_page($attemptobj, $slots, $page, $showall, $lastpage, $options, $summarydata);

// Trigger an event for this review.
$attemptobj->fire_attempt_reviewed_event();
