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
 * This page prints a summary of a flipquiz attempt before it is submitted.
 *
 * @package   mod_flipquiz
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

$attemptid = required_param('attempt', PARAM_INT); // The attempt to summarise.
$cmid = optional_param('cmid', null, PARAM_INT);

$PAGE->set_url('/mod/flipquiz/summary.php', array('attempt' => $attemptid));

$attemptobj = flipquiz_create_attempt_handling_errors($attemptid, $cmid);

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());

// Check that this attempt belongs to this user.
if ($attemptobj->get_userid() != $USER->id) {
    if ($attemptobj->has_capability('mod/flipquiz:viewreports')) {
        redirect($attemptobj->review_url(null));
    } else {
        throw new moodle_flipquiz_exception($attemptobj->get_flipquizobj(), 'notyourattempt');
    }
}

// Check capabilites.
if (!$attemptobj->is_preview_user()) {
    $attemptobj->require_capability('mod/flipquiz:attempt');
}

if ($attemptobj->is_preview_user()) {
    navigation_node::override_active_url($attemptobj->start_attempt_url());
}

// Check access.
$accessmanager = $attemptobj->get_access_manager(time());
$accessmanager->setup_attempt_page($PAGE);
$output = $PAGE->get_renderer('mod_flipquiz');
$messages = $accessmanager->prevent_access();
if (!$attemptobj->is_preview_user() && $messages) {
    print_error('attempterror', 'flipquiz', $attemptobj->view_url(),
            $output->access_messages($messages));
}
if ($accessmanager->is_preflight_check_required($attemptobj->get_attemptid())) {
    redirect($attemptobj->start_attempt_url(null));
}

$displayoptions = $attemptobj->get_display_options(false);

// If the attempt is now overdue, or abandoned, deal with that.
$attemptobj->handle_if_time_expired(time(), true);

// If the attempt is already closed, redirect them to the review page.
if ($attemptobj->is_finished()) {
    redirect($attemptobj->review_url());
}

// Arrange for the navigation to be displayed.
if (empty($attemptobj->get_flipquiz()->showblocks)) {
    $PAGE->blocks->show_only_fake_blocks();
}

$navbc = $attemptobj->get_navigation_panel($output, 'flipquiz_attempt_nav_panel', -1);
$regions = $PAGE->blocks->get_regions();
$PAGE->blocks->add_fake_block($navbc, reset($regions));

$PAGE->navbar->add(get_string('summaryofattempt', 'flipquiz'));
$PAGE->set_title($attemptobj->get_flipquiz_name());
$PAGE->set_heading($attemptobj->get_course()->fullname);

// Display the page.
echo $output->summary_page($attemptobj, $displayoptions);

// Log this page view.
$attemptobj->fire_attempt_summary_viewed_event();
