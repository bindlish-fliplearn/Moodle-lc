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
 * Privacy provider tests.
 *
 * @package    mod_flipquiz
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\writer;
use mod_flipquiz\privacy\provider;
use mod_flipquiz\privacy\helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/tests/privacy_helper.php');

/**
 * Privacy provider tests class.
 *
 * @package    mod_flipquiz
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_flipquiz_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

    use core_question_privacy_helper;

    /**
     * Test that a user who has no data gets no contexts
     */
    public function test_get_contexts_for_userid_no_data() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $contextlist = provider::get_contexts_for_userid($USER->id);
        $this->assertEmpty($contextlist);
    }

    /**
     * Test for provider::get_contexts_for_userid() when there is no flipquiz attempt at all.
     */
    public function test_get_contexts_for_userid_no_attempt_with_override() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Make a flipquiz with an override.
        $this->setUser();
        $flipquiz = $this->create_test_flipquiz($course);
        $DB->insert_record('flipquiz_overrides', [
            'flipquiz' => $flipquiz->id,
            'userid' => $user->id,
            'timeclose' => 1300,
            'timelimit' => null,
        ]);

        $cm = get_coursemodule_from_instance('flipquiz', $flipquiz->id);
        $context = \context_module::instance($cm->id);

        // Fetch the contexts - only one context should be returned.
        $this->setUser();
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context, $contextlist->current());
    }

    /**
     * The export function should handle an empty contextlist properly.
     */
    public function test_export_user_data_no_data() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($USER->id),
            'mod_flipquiz',
            []
        );

        provider::export_user_data($approvedcontextlist);
        $this->assertDebuggingNotCalled();

        // No data should have been exported.
        $writer = \core_privacy\local\request\writer::with_context(\context_system::instance());
        $this->assertFalse($writer->has_any_data_in_any_context());
    }

    /**
     * The delete function should handle an empty contextlist properly.
     */
    public function test_delete_data_for_user_no_data() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($USER->id),
            'mod_flipquiz',
            []
        );

        provider::delete_data_for_user($approvedcontextlist);
        $this->assertDebuggingNotCalled();
    }

    /**
     * Export + Delete flipquiz data for a user who has made a single attempt.
     */
    public function test_user_with_data() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        // Make a flipquiz with an override.
        $this->setUser();
        $flipquiz = $this->create_test_flipquiz($course);
        $DB->insert_record('flipquiz_overrides', [
                'flipquiz' => $flipquiz->id,
                'userid' => $user->id,
                'timeclose' => 1300,
                'timelimit' => null,
            ]);

        // Run as the user and make an attempt on the flipquiz.
        list($flipquizobj, $quba, $attemptobj) = $this->attempt_flipquiz($flipquiz, $user);
        $this->attempt_flipquiz($flipquiz, $otheruser);
        $context = $flipquizobj->get_context();

        // Fetch the contexts - only one context should be returned.
        $this->setUser();
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context, $contextlist->current());

        // Perform the export and check the data.
        $this->setUser($user);
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($user->id),
            'mod_flipquiz',
            $contextlist->get_contextids()
        );
        provider::export_user_data($approvedcontextlist);

        // Ensure that the flipquiz data was exported correctly.
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $flipquizdata = $writer->get_data([]);
        $this->assertEquals($flipquizobj->get_flipquiz_name(), $flipquizdata->name);

        // Every module has an intro.
        $this->assertTrue(isset($flipquizdata->intro));

        // Fetch the attempt data.
        $attempt = $attemptobj->get_attempt();
        $attemptsubcontext = [
            get_string('attempts', 'mod_flipquiz'),
            $attempt->attempt,
        ];
        $attemptdata = writer::with_context($context)->get_data($attemptsubcontext);

        $attempt = $attemptobj->get_attempt();
        $this->assertTrue(isset($attemptdata->state));
        $this->assertEquals(\flipquiz_attempt::state_name($attemptobj->get_state()), $attemptdata->state);
        $this->assertTrue(isset($attemptdata->timestart));
        $this->assertTrue(isset($attemptdata->timefinish));
        $this->assertTrue(isset($attemptdata->timemodified));
        $this->assertFalse(isset($attemptdata->timemodifiedoffline));
        $this->assertFalse(isset($attemptdata->timecheckstate));

        $this->assertTrue(isset($attemptdata->grade));
        $this->assertEquals(100.00, $attemptdata->grade->grade);

        // Check that the exported question attempts are correct.
        $attemptsubcontext = helper::get_flipquiz_attempt_subcontext($attemptobj->get_attempt(), $user);
        $this->assert_question_attempt_exported(
            $context,
            $attemptsubcontext,
            \question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid()),
            flipquiz_get_review_options($flipquiz, $attemptobj->get_attempt(), $context),
            $user
        );

        // Delete the data and check it is removed.
        $this->setUser();
        provider::delete_data_for_user($approvedcontextlist);
        $this->expectException(\dml_missing_record_exception::class);
        \flipquiz_attempt::create($attemptobj->get_flipquizid());
    }

    /**
     * Export + Delete flipquiz data for a user who has made a single attempt.
     */
    public function test_user_with_preview() {
        global $DB;
        $this->resetAfterTest(true);

        // Make a flipquiz.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $flipquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_flipquiz');

        $flipquiz = $flipquizgenerator->create_instance([
                'course' => $course->id,
                'questionsperpage' => 0,
                'grade' => 100.0,
                'sumgrades' => 2,
            ]);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        flipquiz_add_flipquiz_question($saq->id, $flipquiz);
        $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        flipquiz_add_flipquiz_question($numq->id, $flipquiz);

        // Run as the user and make an attempt on the flipquiz.
        $this->setUser($user);
        $starttime = time();
        $flipquizobj = flipquiz::create($flipquiz->id, $user->id);
        $context = $flipquizobj->get_context();

        $quba = question_engine::make_questions_usage_by_activity('mod_flipquiz', $flipquizobj->get_context());
        $quba->set_preferred_behaviour($flipquizobj->get_flipquiz()->preferredbehaviour);

        // Start the attempt.
        $attempt = flipquiz_create_attempt($flipquizobj, 1, false, $starttime, true, $user->id);
        flipquiz_start_new_attempt($flipquizobj, $quba, $attempt, 1, $starttime);
        flipquiz_attempt_save_started($flipquizobj, $quba, $attempt);

        // Answer the questions.
        $attemptobj = flipquiz_attempt::create($attempt->id);

        $tosubmit = [
            1 => ['answer' => 'frog'],
            2 => ['answer' => '3.14'],
        ];

        $attemptobj->process_submitted_actions($starttime, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = flipquiz_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($starttime, false);

        // Fetch the contexts - no context should be returned.
        $this->setUser();
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(0, $contextlist);
    }

    /**
     * Export + Delete flipquiz data for a user who has made a single attempt.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        // Make a flipquiz with an override.
        $this->setUser();
        $flipquiz = $this->create_test_flipquiz($course);
        $DB->insert_record('flipquiz_overrides', [
                'flipquiz' => $flipquiz->id,
                'userid' => $user->id,
                'timeclose' => 1300,
                'timelimit' => null,
            ]);

        // Run as the user and make an attempt on the flipquiz.
        list($flipquizobj, $quba, $attemptobj) = $this->attempt_flipquiz($flipquiz, $user);
        list($flipquizobj, $quba, $attemptobj) = $this->attempt_flipquiz($flipquiz, $otheruser);

        // Create another flipquiz and questions, and repeat the data insertion.
        $this->setUser();
        $otherflipquiz = $this->create_test_flipquiz($course);
        $DB->insert_record('flipquiz_overrides', [
                'flipquiz' => $otherflipquiz->id,
                'userid' => $user->id,
                'timeclose' => 1300,
                'timelimit' => null,
            ]);

        // Run as the user and make an attempt on the flipquiz.
        list($otherflipquizobj, $otherquba, $otherattemptobj) = $this->attempt_flipquiz($otherflipquiz, $user);
        list($otherflipquizobj, $otherquba, $otherattemptobj) = $this->attempt_flipquiz($otherflipquiz, $otheruser);

        // Delete all data for all users in the context under test.
        $this->setUser();
        $context = $flipquizobj->get_context();
        provider::delete_data_for_all_users_in_context($context);

        // The flipquiz attempt should have been deleted from this flipquiz.
        $this->assertCount(0, $DB->get_records('flipquiz_attempts', ['flipquiz' => $flipquizobj->get_flipquizid()]));
        $this->assertCount(0, $DB->get_records('flipquiz_overrides', ['flipquiz' => $flipquizobj->get_flipquizid()]));
        $this->assertCount(0, $DB->get_records('question_attempts', ['questionusageid' => $quba->get_id()]));

        // But not for the other flipquiz.
        $this->assertNotCount(0, $DB->get_records('flipquiz_attempts', ['flipquiz' => $otherflipquizobj->get_flipquizid()]));
        $this->assertNotCount(0, $DB->get_records('flipquiz_overrides', ['flipquiz' => $otherflipquizobj->get_flipquizid()]));
        $this->assertNotCount(0, $DB->get_records('question_attempts', ['questionusageid' => $otherquba->get_id()]));
    }

    /**
     * Export + Delete flipquiz data for a user who has made a single attempt.
     */
    public function test_wrong_context() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Make a choice.
        $this->setUser();
        $plugingenerator = $this->getDataGenerator()->get_plugin_generator('mod_choice');
        $choice = $plugingenerator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('choice', $choice->id);
        $context = \context_module::instance($cm->id);

        // Fetch the contexts - no context should be returned.
        $this->setUser();
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(0, $contextlist);

        // Perform the export and check the data.
        $this->setUser($user);
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($user->id),
            'mod_flipquiz',
            [$context->id]
        );
        provider::export_user_data($approvedcontextlist);

        // Ensure that nothing was exported.
        $writer = writer::with_context($context);
        $this->assertFalse($writer->has_any_data_in_any_context());

        $this->setUser();

        $dbwrites = $DB->perf_get_writes();

        // Perform a deletion with the approved contextlist containing an incorrect context.
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($user->id),
            'mod_flipquiz',
            [$context->id]
        );
        provider::delete_data_for_user($approvedcontextlist);
        $this->assertEquals($dbwrites, $DB->perf_get_writes());
        $this->assertDebuggingNotCalled();

        // Perform a deletion of all data in the context.
        provider::delete_data_for_all_users_in_context($context);
        $this->assertEquals($dbwrites, $DB->perf_get_writes());
        $this->assertDebuggingNotCalled();
    }

    /**
     * Create a test flipquiz for the specified course.
     *
     * @param   \stdClass $course
     * @return  array
     */
    protected function create_test_flipquiz($course) {
        global $DB;

        $flipquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_flipquiz');

        $flipquiz = $flipquizgenerator->create_instance([
                'course' => $course->id,
                'questionsperpage' => 0,
                'grade' => 100.0,
                'sumgrades' => 2,
            ]);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        flipquiz_add_flipquiz_question($saq->id, $flipquiz);
        $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        flipquiz_add_flipquiz_question($numq->id, $flipquiz);

        return $flipquiz;
    }

    /**
     * Answer questions for a flipquiz + user.
     *
     * @param   \stdClass   $flipquiz
     * @param   \stdClass   $user
     * @return  array
     */
    protected function attempt_flipquiz($flipquiz, $user) {
        $this->setUser($user);

        $starttime = time();
        $flipquizobj = flipquiz::create($flipquiz->id, $user->id);
        $context = $flipquizobj->get_context();

        $quba = question_engine::make_questions_usage_by_activity('mod_flipquiz', $flipquizobj->get_context());
        $quba->set_preferred_behaviour($flipquizobj->get_flipquiz()->preferredbehaviour);

        // Start the attempt.
        $attempt = flipquiz_create_attempt($flipquizobj, 1, false, $starttime, false, $user->id);
        flipquiz_start_new_attempt($flipquizobj, $quba, $attempt, 1, $starttime);
        flipquiz_attempt_save_started($flipquizobj, $quba, $attempt);

        // Answer the questions.
        $attemptobj = flipquiz_attempt::create($attempt->id);

        $tosubmit = [
            1 => ['answer' => 'frog'],
            2 => ['answer' => '3.14'],
        ];

        $attemptobj->process_submitted_actions($starttime, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = flipquiz_attempt::create($attempt->id);
        $attemptobj->process_finish($starttime, false);

        $this->setUser();

        return [$flipquizobj, $quba, $attemptobj];
    }

    /**
     * Test for provider::get_users_in_context().
     */
    public function test_get_users_in_context() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $anotheruser = $this->getDataGenerator()->create_user();
        $extrauser = $this->getDataGenerator()->create_user();

        // Make a flipquiz.
        $this->setUser();
        $flipquiz = $this->create_test_flipquiz($course);

        // Create an override for user1.
        $DB->insert_record('flipquiz_overrides', [
            'flipquiz' => $flipquiz->id,
            'userid' => $user->id,
            'timeclose' => 1300,
            'timelimit' => null,
        ]);

        // Make an attempt on the flipquiz as user2.
        list($flipquizobj, $quba, $attemptobj) = $this->attempt_flipquiz($flipquiz, $anotheruser);
        $context = $flipquizobj->get_context();

        // Fetch users - user1 and user2 should be returned.
        $userlist = new \core_privacy\local\request\userlist($context, 'mod_flipquiz');
        \mod_flipquiz\privacy\provider::get_users_in_context($userlist);
        $this->assertEquals(
                [$user->id, $anotheruser->id],
                $userlist->get_userids(),
                '', 0.0, 10, true);
    }

    /**
     * Test for provider::delete_data_for_users().
     */
    public function test_delete_data_for_users() {
        global $DB;
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Make a flipquiz in each course.
        $flipquiz1 = $this->create_test_flipquiz($course1);
        $flipquiz2 = $this->create_test_flipquiz($course2);

        // Attempt flipquiz1 as user1 and user2.
        list($flipquiz1obj) = $this->attempt_flipquiz($flipquiz1, $user1);
        $this->attempt_flipquiz($flipquiz1, $user2);

        // Create an override in flipquiz1 for user3.
        $DB->insert_record('flipquiz_overrides', [
            'flipquiz' => $flipquiz1->id,
            'userid' => $user3->id,
            'timeclose' => 1300,
            'timelimit' => null,
        ]);

        // Attempt flipquiz2 as user1.
        $this->attempt_flipquiz($flipquiz2, $user1);

        // Delete the data for user1 and user3 in course1 and check it is removed.
        $flipquiz1context = $flipquiz1obj->get_context();
        $approveduserlist = new \core_privacy\local\request\approved_userlist($flipquiz1context, 'mod_flipquiz',
                [$user1->id, $user3->id]);
        provider::delete_data_for_users($approveduserlist);

        // Only the attempt of user2 should be remained in flipquiz1.
        $this->assertEquals(
                [$user2->id],
                $DB->get_fieldset_select('flipquiz_attempts', 'userid', 'flipquiz = ?', [$flipquiz1->id])
        );

        // The attempt that user1 made in flipquiz2 should be remained.
        $this->assertEquals(
                [$user1->id],
                $DB->get_fieldset_select('flipquiz_attempts', 'userid', 'flipquiz = ?', [$flipquiz2->id])
        );

        // The flipquiz override in flipquiz1 that we had for user3 should be deleted.
        $this->assertEquals(
                [],
                $DB->get_fieldset_select('flipquiz_overrides', 'userid', 'flipquiz = ?', [$flipquiz1->id])
        );
    }
}
