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
 * This page allows the teacher to enter a manual grade for a particular question.
 * This page is expected to only be used in a popup window.
 *
 * @package   mod_flipquiz
 * @copyright gustav delius 2006
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');

$attemptid = required_param('attempt', PARAM_INT);
$slot = required_param('slot', PARAM_INT); // The question number in the attempt.
$cmid = optional_param('cmid', null, PARAM_INT);

$PAGE->set_url('/mod/flipquiz/comment.php', array('attempt' => $attemptid, 'slot' => $slot));

$attemptobj = flipquiz_create_attempt_handling_errors($attemptid, $cmid);
$student = $DB->get_record('user', array('id' => $attemptobj->get_userid()));

// Can only grade finished attempts.
if (!$attemptobj->is_finished()) {
    print_error('attemptclosed', 'flipquiz');
}

// Check login and permissions.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
$attemptobj->require_capability('mod/flipquiz:grade');

// Print the page header.
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('manualgradequestion', 'flipquiz', array(
        'question' => format_string($attemptobj->get_question_name($slot)),
        'flipquiz' => format_string($attemptobj->get_flipquiz_name()), 'user' => fullname($student))));
$PAGE->set_heading($attemptobj->get_course()->fullname);
$output = $PAGE->get_renderer('mod_flipquiz');
echo $output->header();

// Prepare summary information about this question attempt.
$summarydata = array();

// Student name.
$userpicture = new user_picture($student);
$userpicture->courseid = $attemptobj->get_courseid();
$summarydata['user'] = array(
    'title'   => $userpicture,
    'content' => new action_link(new moodle_url('/user/view.php', array(
            'id' => $student->id, 'course' => $attemptobj->get_courseid())),
            fullname($student, true)),
);

// Quiz name.
$summarydata['flipquizname'] = array(
    'title'   => get_string('modulename', 'flipquiz'),
    'content' => format_string($attemptobj->get_flipquiz_name()),
);

// Question name.
$summarydata['questionname'] = array(
    'title'   => get_string('question', 'flipquiz'),
    'content' => $attemptobj->get_question_name($slot),
);

// Process any data that was submitted.
if (data_submitted() && confirm_sesskey()) {
    if (optional_param('submit', false, PARAM_BOOL) && question_engine::is_manual_grade_in_range($attemptobj->get_uniqueid(), $slot)) {
        $transaction = $DB->start_delegated_transaction();
        $attemptobj->process_submitted_actions(time());
        $transaction->allow_commit();

        // Log this action.
        $params = array(
            'objectid' => $attemptobj->get_question_attempt($slot)->get_question()->id,
            'courseid' => $attemptobj->get_courseid(),
            'context' => context_module::instance($attemptobj->get_cmid()),
            'other' => array(
                'flipquizid' => $attemptobj->get_flipquizid(),
                'attemptid' => $attemptobj->get_attemptid(),
                'slot' => $slot
            )
        );
        $event = \mod_flipquiz\event\question_manually_graded::create($params);
        $event->trigger();

        echo $output->notification(get_string('changessaved'), 'notifysuccess');
        close_window(2, true);
        die;
    }
}

// Print flipquiz information.
echo $output->review_summary_table($summarydata, 0);

// Print the comment form.
echo '<form method="post" class="mform" id="manualgradingform" action="' .
        $CFG->wwwroot . '/mod/flipquiz/comment.php">';
echo $attemptobj->render_question_for_commenting($slot);
?>
<div>
    <input type="hidden" name="attempt" value="<?php echo $attemptobj->get_attemptid(); ?>" />
    <input type="hidden" name="slot" value="<?php echo $slot; ?>" />
    <input type="hidden" name="slots" value="<?php echo $slot; ?>" />
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />
</div>
<fieldset class="hidden">
    <div>
        <div class="fitem fitem_actionbuttons fitem_fsubmit">
            <fieldset class="felement fsubmit">
                <input id="id_submitbutton" type="submit" name="submit" class="btn btn-primary" value="<?php
                        print_string('save', 'flipquiz'); ?>"/>
            </fieldset>
        </div>
    </div>
</fieldset>
<?php
echo '</form>';
$PAGE->requires->js_init_call('M.mod_flipquiz.init_comment_popup', null, false, flipquiz_get_js_module());

// End of the page.
echo $output->footer();
