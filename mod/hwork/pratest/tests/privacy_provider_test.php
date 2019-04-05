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
 * @package    mod_pratest
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\writer;
use mod_pratest\privacy\provider;
use mod_pratest\privacy\helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/tests/privacy_helper.php');

/**
 * Privacy provider tests class.
 *
 * @package    mod_pratest
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pratest_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

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
     * Test for provider::get_contexts_for_userid() when there is no pratest attempt at all.
     */
    public function test_get_contexts_for_userid_no_attempt_with_override() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Make a pratest with an override.
        $this->setUser();
        $pratest = $this->create_test_pratest($course);
        $DB->insert_record('pratest_overrides', [
            'pratest' => $pratest->id,
            'userid' => $user->id,
            'timeclose' => 1300,
            'timelimit' => null,
        ]);

        $cm = get_coursemodule_from_instance('pratest', $pratest->id);
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
            'mod_pratest',
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
            'mod_pratest',
            []
        );

        provider::delete_data_for_user($approvedcontextlist);
        $this->assertDebuggingNotCalled();
    }

    /**
     * Export + Delete pratest data for a user who has made a single attempt.
     */
    public function test_user_with_data() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        // Make a pratest with an override.
        $this->setUser();
        $pratest = $this->create_test_pratest($course);
        $DB->insert_record('pratest_overrides', [
                'pratest' => $pratest->id,
                'userid' => $user->id,
                'timeclose' => 1300,
                'timelimit' => null,
            ]);

        // Run as the user and make an attempt on the pratest.
        list($pratestobj, $quba, $attemptobj) = $this->attempt_pratest($pratest, $user);
        $this->attempt_pratest($pratest, $otheruser);
        $context = $pratestobj->get_context();

        // Fetch the contexts - only one context should be returned.
        $this->setUser();
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context, $contextlist->current());

        // Perform the export and check the data.
        $this->setUser($user);
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($user->id),
            'mod_pratest',
            $contextlist->get_contextids()
        );
        provider::export_user_data($approvedcontextlist);

        // Ensure that the pratest data was exported correctly.
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $pratestdata = $writer->get_data([]);
        $this->assertEquals($pratestobj->get_pratest_name(), $pratestdata->name);

        // Every module has an intro.
        $this->assertTrue(isset($pratestdata->intro));

        // Fetch the attempt data.
        $attempt = $attemptobj->get_attempt();
        $attemptsubcontext = [
            get_string('attempts', 'mod_pratest'),
            $attempt->attempt,
        ];
        $attemptdata = writer::with_context($context)->get_data($attemptsubcontext);

        $attempt = $attemptobj->get_attempt();
        $this->assertTrue(isset($attemptdata->state));
        $this->assertEquals(\pratest_attempt::state_name($attemptobj->get_state()), $attemptdata->state);
        $this->assertTrue(isset($attemptdata->timestart));
        $this->assertTrue(isset($attemptdata->timefinish));
        $this->assertTrue(isset($attemptdata->timemodified));
        $this->assertFalse(isset($attemptdata->timemodifiedoffline));
        $this->assertFalse(isset($attemptdata->timecheckstate));

        $this->assertTrue(isset($attemptdata->grade));
        $this->assertEquals(100.00, $attemptdata->grade->grade);

        // Check that the exported question attempts are correct.
        $attemptsubcontext = helper::get_pratest_attempt_subcontext($attemptobj->get_attempt(), $user);
        $this->assert_question_attempt_exported(
            $context,
            $attemptsubcontext,
            \question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid()),
            pratest_get_review_options($pratest, $attemptobj->get_attempt(), $context),
            $user
        );

        // Delete the data and check it is removed.
        $this->setUser();
        provider::delete_data_for_user($approvedcontextlist);
        $this->expectException(\dml_missing_record_exception::class);
        \pratest_attempt::create($attemptobj->get_pratestid());
    }

    /**
     * Export + Delete pratest data for a user who has made a single attempt.
     */
    public function test_user_with_preview() {
        global $DB;
        $this->resetAfterTest(true);

        // Make a pratest.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $pratestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pratest');

        $pratest = $pratestgenerator->create_instance([
                'course' => $course->id,
                'questionsperpage' => 0,
                'grade' => 100.0,
                'sumgrades' => 2,
            ]);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        pratest_add_pratest_question($saq->id, $pratest);
        $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        pratest_add_pratest_question($numq->id, $pratest);

        // Run as the user and make an attempt on the pratest.
        $this->setUser($user);
        $starttime = time();
        $pratestobj = pratest::create($pratest->id, $user->id);
        $context = $pratestobj->get_context();

        $quba = question_engine::make_questions_usage_by_activity('mod_pratest', $pratestobj->get_context());
        $quba->set_preferred_behaviour($pratestobj->get_pratest()->preferredbehaviour);

        // Start the attempt.
        $attempt = pratest_create_attempt($pratestobj, 1, false, $starttime, true, $user->id);
        pratest_start_new_attempt($pratestobj, $quba, $attempt, 1, $starttime);
        pratest_attempt_save_started($pratestobj, $quba, $attempt);

        // Answer the questions.
        $attemptobj = pratest_attempt::create($attempt->id);

        $tosubmit = [
            1 => ['answer' => 'frog'],
            2 => ['answer' => '3.14'],
        ];

        $attemptobj->process_submitted_actions($starttime, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = pratest_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($starttime, false);

        // Fetch the contexts - no context should be returned.
        $this->setUser();
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(0, $contextlist);
    }

    /**
     * Export + Delete pratest data for a user who has made a single attempt.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        // Make a pratest with an override.
        $this->setUser();
        $pratest = $this->create_test_pratest($course);
        $DB->insert_record('pratest_overrides', [
                'pratest' => $pratest->id,
                'userid' => $user->id,
                'timeclose' => 1300,
                'timelimit' => null,
            ]);

        // Run as the user and make an attempt on the pratest.
        list($pratestobj, $quba, $attemptobj) = $this->attempt_pratest($pratest, $user);
        list($pratestobj, $quba, $attemptobj) = $this->attempt_pratest($pratest, $otheruser);

        // Create another pratest and questions, and repeat the data insertion.
        $this->setUser();
        $otherpratest = $this->create_test_pratest($course);
        $DB->insert_record('pratest_overrides', [
                'pratest' => $otherpratest->id,
                'userid' => $user->id,
                'timeclose' => 1300,
                'timelimit' => null,
            ]);

        // Run as the user and make an attempt on the pratest.
        list($otherpratestobj, $otherquba, $otherattemptobj) = $this->attempt_pratest($otherpratest, $user);
        list($otherpratestobj, $otherquba, $otherattemptobj) = $this->attempt_pratest($otherpratest, $otheruser);

        // Delete all data for all users in the context under test.
        $this->setUser();
        $context = $pratestobj->get_context();
        provider::delete_data_for_all_users_in_context($context);

        // The pratest attempt should have been deleted from this pratest.
        $this->assertCount(0, $DB->get_records('pratest_attempts', ['pratest' => $pratestobj->get_pratestid()]));
        $this->assertCount(0, $DB->get_records('pratest_overrides', ['pratest' => $pratestobj->get_pratestid()]));
        $this->assertCount(0, $DB->get_records('question_attempts', ['questionusageid' => $quba->get_id()]));

        // But not for the other pratest.
        $this->assertNotCount(0, $DB->get_records('pratest_attempts', ['pratest' => $otherpratestobj->get_pratestid()]));
        $this->assertNotCount(0, $DB->get_records('pratest_overrides', ['pratest' => $otherpratestobj->get_pratestid()]));
        $this->assertNotCount(0, $DB->get_records('question_attempts', ['questionusageid' => $otherquba->get_id()]));
    }

    /**
     * Export + Delete pratest data for a user who has made a single attempt.
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
            'mod_pratest',
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
            'mod_pratest',
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
     * Create a test pratest for the specified course.
     *
     * @param   \stdClass $course
     * @return  array
     */
    protected function create_test_pratest($course) {
        global $DB;

        $pratestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pratest');

        $pratest = $pratestgenerator->create_instance([
                'course' => $course->id,
                'questionsperpage' => 0,
                'grade' => 100.0,
                'sumgrades' => 2,
            ]);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        pratest_add_pratest_question($saq->id, $pratest);
        $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        pratest_add_pratest_question($numq->id, $pratest);

        return $pratest;
    }

    /**
     * Answer questions for a pratest + user.
     *
     * @param   \stdClass   $pratest
     * @param   \stdClass   $user
     * @return  array
     */
    protected function attempt_pratest($pratest, $user) {
        $this->setUser($user);

        $starttime = time();
        $pratestobj = pratest::create($pratest->id, $user->id);
        $context = $pratestobj->get_context();

        $quba = question_engine::make_questions_usage_by_activity('mod_pratest', $pratestobj->get_context());
        $quba->set_preferred_behaviour($pratestobj->get_pratest()->preferredbehaviour);

        // Start the attempt.
        $attempt = pratest_create_attempt($pratestobj, 1, false, $starttime, false, $user->id);
        pratest_start_new_attempt($pratestobj, $quba, $attempt, 1, $starttime);
        pratest_attempt_save_started($pratestobj, $quba, $attempt);

        // Answer the questions.
        $attemptobj = pratest_attempt::create($attempt->id);

        $tosubmit = [
            1 => ['answer' => 'frog'],
            2 => ['answer' => '3.14'],
        ];

        $attemptobj->process_submitted_actions($starttime, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = pratest_attempt::create($attempt->id);
        $attemptobj->process_finish($starttime, false);

        $this->setUser();

        return [$pratestobj, $quba, $attemptobj];
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

        // Make a pratest.
        $this->setUser();
        $pratest = $this->create_test_pratest($course);

        // Create an override for user1.
        $DB->insert_record('pratest_overrides', [
            'pratest' => $pratest->id,
            'userid' => $user->id,
            'timeclose' => 1300,
            'timelimit' => null,
        ]);

        // Make an attempt on the pratest as user2.
        list($pratestobj, $quba, $attemptobj) = $this->attempt_pratest($pratest, $anotheruser);
        $context = $pratestobj->get_context();

        // Fetch users - user1 and user2 should be returned.
        $userlist = new \core_privacy\local\request\userlist($context, 'mod_pratest');
        \mod_pratest\privacy\provider::get_users_in_context($userlist);
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

        // Make a pratest in each course.
        $pratest1 = $this->create_test_pratest($course1);
        $pratest2 = $this->create_test_pratest($course2);

        // Attempt pratest1 as user1 and user2.
        list($pratest1obj) = $this->attempt_pratest($pratest1, $user1);
        $this->attempt_pratest($pratest1, $user2);

        // Create an override in pratest1 for user3.
        $DB->insert_record('pratest_overrides', [
            'pratest' => $pratest1->id,
            'userid' => $user3->id,
            'timeclose' => 1300,
            'timelimit' => null,
        ]);

        // Attempt pratest2 as user1.
        $this->attempt_pratest($pratest2, $user1);

        // Delete the data for user1 and user3 in course1 and check it is removed.
        $pratest1context = $pratest1obj->get_context();
        $approveduserlist = new \core_privacy\local\request\approved_userlist($pratest1context, 'mod_pratest',
                [$user1->id, $user3->id]);
        provider::delete_data_for_users($approveduserlist);

        // Only the attempt of user2 should be remained in pratest1.
        $this->assertEquals(
                [$user2->id],
                $DB->get_fieldset_select('pratest_attempts', 'userid', 'pratest = ?', [$pratest1->id])
        );

        // The attempt that user1 made in pratest2 should be remained.
        $this->assertEquals(
                [$user1->id],
                $DB->get_fieldset_select('pratest_attempts', 'userid', 'pratest = ?', [$pratest2->id])
        );

        // The pratest override in pratest1 that we had for user3 should be deleted.
        $this->assertEquals(
                [],
                $DB->get_fieldset_select('pratest_overrides', 'userid', 'pratest = ?', [$pratest1->id])
        );
    }
}
