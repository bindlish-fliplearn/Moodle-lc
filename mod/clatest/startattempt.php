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
 * This script deals with starting a new attempt at a clatest.
 *
 * Normally, it will end up redirecting to attempt.php - unless a password form is displayed.
 *
 * This code used to be at the top of attempt.php, if you are looking for CVS history.
 *
 * @package   mod_clatest
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/clatest/locallib.php');

// Get submitted parameters.
$id = required_param('cmid', PARAM_INT); // Course module id
$forcenew = optional_param('forcenew', false, PARAM_BOOL); // Used to force a new preview
$page = optional_param('page', -1, PARAM_INT); // Page to jump to in the attempt.

if (!$cm = get_coursemodule_from_id('clatest', $id)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error("coursemisconf");
}

$clatestobj = clatest::create($cm->instance, $USER->id);
// This script should only ever be posted to, so set page URL to the view page.
$PAGE->set_url($clatestobj->view_url());

// Check login and sesskey.
require_login($clatestobj->get_course(), false, $clatestobj->get_cm());
require_sesskey();
$PAGE->set_heading($clatestobj->get_course()->fullname);

// If no questions have been set up yet redirect to edit.php or display an error.
if (!$clatestobj->has_questions()) {
    if ($clatestobj->has_capability('mod/clatest:manage')) {
        redirect($clatestobj->edit_url());
    } else {
        print_error('cannotstartnoquestions', 'clatest', $clatestobj->view_url());
    }
}

// Create an object to manage all the other (non-roles) access rules.
$timenow = time();
$accessmanager = $clatestobj->get_access_manager($timenow);

// Validate permissions for creating a new attempt and start a new preview attempt if required.
list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
    clatest_validate_new_attempt($clatestobj, $accessmanager, $forcenew, $page, true);

// Check access.
if (!$clatestobj->is_preview_user() && $messages) {
    $output = $PAGE->get_renderer('mod_clatest');
    print_error('attempterror', 'clatest', $clatestobj->view_url(),
            $output->access_messages($messages));
}

if ($accessmanager->is_preflight_check_required($currentattemptid)) {
    // Need to do some checks before allowing the user to continue.
    $mform = $accessmanager->get_preflight_check_form(
            $clatestobj->start_attempt_url($page), $currentattemptid);

    if ($mform->is_cancelled()) {
        $accessmanager->back_to_view_page($PAGE->get_renderer('mod_clatest'));

    } else if (!$mform->get_data()) {

        // Form not submitted successfully, re-display it and stop.
        $PAGE->set_url($clatestobj->start_attempt_url($page));
        $PAGE->set_title($clatestobj->get_clatest_name());
        $accessmanager->setup_attempt_page($PAGE);
        $output = $PAGE->get_renderer('mod_clatest');
        if (empty($clatestobj->get_clatest()->showblocks)) {
            $PAGE->blocks->show_only_fake_blocks();
        }

        echo $output->start_attempt_page($clatestobj, $mform);
        die();
    }

    // Pre-flight check passed.
    $accessmanager->notify_preflight_check_passed($currentattemptid);
}
if ($currentattemptid) {
    if ($lastattempt->state == clatest_attempt::OVERDUE) {
        redirect($clatestobj->summary_url($lastattempt->id));
    } else {
        redirect($clatestobj->attempt_url($currentattemptid, $page));
    }
}

$attempt = clatest_prepare_and_start_new_attempt($clatestobj, $attemptnumber, $lastattempt);

// Redirect to the attempt page.
redirect($clatestobj->attempt_url($attempt->id, $page));
