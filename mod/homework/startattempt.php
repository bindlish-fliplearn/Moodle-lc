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
 * This script deals with starting a new attempt at a homework.
 *
 * Normally, it will end up redirecting to attempt.php - unless a password form is displayed.
 *
 * This code used to be at the top of attempt.php, if you are looking for CVS history.
 *
 * @package   mod_homework
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/homework/locallib.php');

// Get submitted parameters.
$id = required_param('cmid', PARAM_INT); // Course module id
$forcenew = optional_param('forcenew', false, PARAM_BOOL); // Used to force a new preview
$page = optional_param('page', -1, PARAM_INT); // Page to jump to in the attempt.

if (!$cm = get_coursemodule_from_id('homework', $id)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error("coursemisconf");
}

$homeworkobj = homework::create($cm->instance, $USER->id);
// This script should only ever be posted to, so set page URL to the view page.
$PAGE->set_url($homeworkobj->view_url());

// Check login and sesskey.
require_login($homeworkobj->get_course(), false, $homeworkobj->get_cm());
require_sesskey();
$PAGE->set_heading($homeworkobj->get_course()->fullname);

// If no questions have been set up yet redirect to edit.php or display an error.
if (!$homeworkobj->has_questions()) {
    if ($homeworkobj->has_capability('mod/homework:manage')) {
        redirect($homeworkobj->edit_url());
    } else {
        print_error('cannotstartnoquestions', 'homework', $homeworkobj->view_url());
    }
}

// Create an object to manage all the other (non-roles) access rules.
$timenow = time();
$accessmanager = $homeworkobj->get_access_manager($timenow);

// Validate permissions for creating a new attempt and start a new preview attempt if required.
list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
    homework_validate_new_attempt($homeworkobj, $accessmanager, $forcenew, $page, true);

// Check access.
if (!$homeworkobj->is_preview_user() && $messages) {
    $output = $PAGE->get_renderer('mod_homework');
    print_error('attempterror', 'homework', $homeworkobj->view_url(),
            $output->access_messages($messages));
}

if ($accessmanager->is_preflight_check_required($currentattemptid)) {
    // Need to do some checks before allowing the user to continue.
    $mform = $accessmanager->get_preflight_check_form(
            $homeworkobj->start_attempt_url($page), $currentattemptid);

    if ($mform->is_cancelled()) {
        $accessmanager->back_to_view_page($PAGE->get_renderer('mod_homework'));

    } else if (!$mform->get_data()) {

        // Form not submitted successfully, re-display it and stop.
        $PAGE->set_url($homeworkobj->start_attempt_url($page));
        $PAGE->set_title($homeworkobj->get_homework_name());
        $accessmanager->setup_attempt_page($PAGE);
        $output = $PAGE->get_renderer('mod_homework');
        if (empty($homeworkobj->get_homework()->showblocks)) {
            $PAGE->blocks->show_only_fake_blocks();
        }

        echo $output->start_attempt_page($homeworkobj, $mform);
        die();
    }

    // Pre-flight check passed.
    $accessmanager->notify_preflight_check_passed($currentattemptid);
}
if ($currentattemptid) {
    if ($lastattempt->state == homework_attempt::OVERDUE) {
        redirect($homeworkobj->summary_url($lastattempt->id));
    } else {
        redirect($homeworkobj->attempt_url($currentattemptid, $page));
    }
}

$attempt = homework_prepare_and_start_new_attempt($homeworkobj, $attemptnumber, $lastattempt);

// Redirect to the attempt page.
redirect($homeworkobj->attempt_url($attempt->id, $page));
