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
 * @package    mod_clatest
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\writer;
use mod_clatest\privacy\provider;
use mod_clatest\privacy\helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/tests/privacy_helper.php');

/**
 * Privacy provider tests class.
 *
 * @package    mod_clatest
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_clatest_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

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
     * Test for provider::get_contexts_for_userid() when there is no clatest attempt at all.
     */
    public function test_get_contexts_for_userid_no_attempt_with_override() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Make a clatest with an override.
        $this->setUser();
        $clatest = $this->create_test_clatest($course);
        $DB->insert_record('clatest_overrides', [
            'clatest' => $clatest->id,
            'userid' => $user->id,
            'timeclose' => 1300,
            'timelimit' => null,
        ]);

        $cm = get_coursemodule_from_instance('clatest', $clatest->id);
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
            'mod_clatest',
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
            'mod_clatest',
            []
        );

        provider::delete_data_for_user($approvedcontextlist);
        $this->assertDebuggingNotCalled();
    }

    /**
     * Export + Delete clatest data for a user who has made a single attempt.
     */
    public function test_user_with_data() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        // Make a clatest with an override.
        $this->setUser();
        $clatest = $this->create_test_clatest($course);
        $DB->insert_record('clatest_overrides', [
                'clatest' => $clatest->id,
                'userid' => $user->id,
                'timeclose' => 1300,
                'timelimit' => null,
            ]);

        // Run as the user and make an attempt on the clatest.
        list($clatestobj, $quba, $attemptobj) = $this->attempt_clatest($clatest, $user);
        $this->attempt_clatest($clatest, $otheruser);
        $context = $clatestobj->get_context();

        // Fetch the contexts - only one context should be returned.
        $this->setUser();
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context, $contextlist->current());

        // Perform the export and check the data.
        $this->setUser($user);
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($user->id),
            'mod_clatest',
            $contextlist->get_contextids()
        );
        provider::export_user_data($approvedcontextlist);

        // Ensure that the clatest data was exported correctly.
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $clatestdata = $writer->get_data([]);
        $this->assertEquals($clatestobj->get_clatest_name(), $clatestdata->name);

        // Every module has an intro.
        $this->assertTrue(isset($clatestdata->intro));

        // Fetch the attempt data.
        $attempt = $attemptobj->get_attempt();
        $attemptsubcontext = [
            get_string('attempts', 'mod_clatest'),
            $attempt->attempt,
        ];
        $attemptdata = writer::with_context($context)->get_data($attemptsubcontext);

        $attempt = $attemptobj->get_attempt();
        $this->assertTrue(isset($attemptdata->state));
        $this->assertEquals(\clatest_attempt::state_name($attemptobj->get_state()), $attemptdata->state);
        $this->assertTrue(isset($attemptdata->timestart));
        $this->assertTrue(isset($attemptdata->timefinish));
        $this->assertTrue(isset($attemptdata->timemodified));
        $this->assertFalse(isset($attemptdata->timemodifiedoffline));
        $this->assertFalse(isset($attemptdata->timecheckstate));

        $this->assertTrue(isset($attemptdata->grade));
        $this->assertEquals(100.00, $attemptdata->grade->grade);

        // Check that the exported question attempts are correct.
        $attemptsubcontext = helper::get_clatest_attempt_subcontext($attemptobj->get_attempt(), $user);
        $this->assert_question_attempt_exported(
            $context,
            $attemptsubcontext,
            \question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid()),
            clatest_get_review_options($clatest, $attemptobj->get_attempt(), $context),
            $user
        );

        // Delete the data and check it is removed.
        $this->setUser();
        provider::delete_data_for_user($approvedcontextlist);
        $this->expectException(\dml_missing_record_exception::class);
        \clatest_attempt::create($attemptobj->get_clatestid());
    }

    /**
     * Export + Delete clatest data for a user who has made a single attempt.
     */
    public function test_user_with_preview() {
        global $DB;
        $this->resetAfterTest(true);

        // Make a clatest.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $clatestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_clatest');

        $clatest = $clatestgenerator->create_instance([
                'course' => $course->id,
                'questionsperpage' => 0,
                'grade' => 100.0,
                'sumgrades' => 2,
            ]);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        clatest_add_clatest_question($saq->id, $clatest);
        $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        clatest_add_clatest_question($numq->id, $clatest);

        // Run as the user and make an attempt on the clatest.
        $this->setUser($user);
        $starttime = time();
        $clatestobj = clatest::create($clatest->id, $user->id);
        $context = $clatestobj->get_context();

        $quba = question_engine::make_questions_usage_by_activity('mod_clatest', $clatestobj->get_context());
        $quba->set_preferred_behaviour($clatestobj->get_clatest()->preferredbehaviour);

        // Start the attempt.
        $attempt = clatest_create_attempt($clatestobj, 1, false, $starttime, true, $user->id);
        clatest_start_new_attempt($clatestobj, $quba, $attempt, 1, $starttime);
        clatest_attempt_save_started($clatestobj, $quba, $attempt);

        // Answer the questions.
        $attemptobj = clatest_attempt::create($attempt->id);

        $tosubmit = [
            1 => ['answer' => 'frog'],
            2 => ['answer' => '3.14'],
        ];

        $attemptobj->process_submitted_actions($starttime, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = clatest_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($starttime, false);

        // Fetch the contexts - no context should be returned.
        $this->setUser();
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(0, $contextlist);
    }

    /**
     * Export + Delete clatest data for a user who has made a single attempt.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        // Make a clatest with an override.
        $this->setUser();
        $clatest = $this->create_test_clatest($course);
        $DB->insert_record('clatest_overrides', [
                'clatest' => $clatest->id,
                'userid' => $user->id,
                'timeclose' => 1300,
                'timelimit' => null,
            ]);

        // Run as the user and make an attempt on the clatest.
        list($clatestobj, $quba, $attemptobj) = $this->attempt_clatest($clatest, $user);
        list($clatestobj, $quba, $attemptobj) = $this->attempt_clatest($clatest, $otheruser);

        // Create another clatest and questions, and repeat the data insertion.
        $this->setUser();
        $otherclatest = $this->create_test_clatest($course);
        $DB->insert_record('clatest_overrides', [
                'clatest' => $otherclatest->id,
                'userid' => $user->id,
                'timeclose' => 1300,
                'timelimit' => null,
            ]);

        // Run as the user and make an attempt on the clatest.
        list($otherclatestobj, $otherquba, $otherattemptobj) = $this->attempt_clatest($otherclatest, $user);
        list($otherclatestobj, $otherquba, $otherattemptobj) = $this->attempt_clatest($otherclatest, $otheruser);

        // Delete all data for all users in the context under test.
        $this->setUser();
        $context = $clatestobj->get_context();
        provider::delete_data_for_all_users_in_context($context);

        // The clatest attempt should have been deleted from this clatest.
        $this->assertCount(0, $DB->get_records('clatest_attempts', ['clatest' => $clatestobj->get_clatestid()]));
        $this->assertCount(0, $DB->get_records('clatest_overrides', ['clatest' => $clatestobj->get_clatestid()]));
        $this->assertCount(0, $DB->get_records('question_attempts', ['questionusageid' => $quba->get_id()]));

        // But not for the other clatest.
        $this->assertNotCount(0, $DB->get_records('clatest_attempts', ['clatest' => $otherclatestobj->get_clatestid()]));
        $this->assertNotCount(0, $DB->get_records('clatest_overrides', ['clatest' => $otherclatestobj->get_clatestid()]));
        $this->assertNotCount(0, $DB->get_records('question_attempts', ['questionusageid' => $otherquba->get_id()]));
    }

    /**
     * Export + Delete clatest data for a user who has made a single attempt.
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
            'mod_clatest',
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
            'mod_clatest',
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
     * Create a test clatest for the specified course.
     *
     * @param   \stdClass $course
     * @return  array
     */
    protected function create_test_clatest($course) {
        global $DB;

        $clatestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_clatest');

        $clatest = $clatestgenerator->create_instance([
                'course' => $course->id,
                'questionsperpage' => 0,
                'grade' => 100.0,
                'sumgrades' => 2,
            ]);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        clatest_add_clatest_question($saq->id, $clatest);
        $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        clatest_add_clatest_question($numq->id, $clatest);

        return $clatest;
    }

    /**
     * Answer questions for a clatest + user.
     *
     * @param   \stdClass   $clatest
     * @param   \stdClass   $user
     * @return  array
     */
    protected function attempt_clatest($clatest, $user) {
        $this->setUser($user);

        $starttime = time();
        $clatestobj = clatest::create($clatest->id, $user->id);
        $context = $clatestobj->get_context();

        $quba = question_engine::make_questions_usage_by_activity('mod_clatest', $clatestobj->get_context());
        $quba->set_preferred_behaviour($clatestobj->get_clatest()->preferredbehaviour);

        // Start the attempt.
        $attempt = clatest_create_attempt($clatestobj, 1, false, $starttime, false, $user->id);
        clatest_start_new_attempt($clatestobj, $quba, $attempt, 1, $starttime);
        clatest_attempt_save_started($clatestobj, $quba, $attempt);

        // Answer the questions.
        $attemptobj = clatest_attempt::create($attempt->id);

        $tosubmit = [
            1 => ['answer' => 'frog'],
            2 => ['answer' => '3.14'],
        ];

        $attemptobj->process_submitted_actions($starttime, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = clatest_attempt::create($attempt->id);
        $attemptobj->process_finish($starttime, false);

        $this->setUser();

        return [$clatestobj, $quba, $attemptobj];
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

        // Make a clatest.
        $this->setUser();
        $clatest = $this->create_test_clatest($course);

        // Create an override for user1.
        $DB->insert_record('clatest_overrides', [
            'clatest' => $clatest->id,
            'userid' => $user->id,
            'timeclose' => 1300,
            'timelimit' => null,
        ]);

        // Make an attempt on the clatest as user2.
        list($clatestobj, $quba, $attemptobj) = $this->attempt_clatest($clatest, $anotheruser);
        $context = $clatestobj->get_context();

        // Fetch users - user1 and user2 should be returned.
        $userlist = new \core_privacy\local\request\userlist($context, 'mod_clatest');
        \mod_clatest\privacy\provider::get_users_in_context($userlist);
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

        // Make a clatest in each course.
        $clatest1 = $this->create_test_clatest($course1);
        $clatest2 = $this->create_test_clatest($course2);

        // Attempt clatest1 as user1 and user2.
        list($clatest1obj) = $this->attempt_clatest($clatest1, $user1);
        $this->attempt_clatest($clatest1, $user2);

        // Create an override in clatest1 for user3.
        $DB->insert_record('clatest_overrides', [
            'clatest' => $clatest1->id,
            'userid' => $user3->id,
            'timeclose' => 1300,
            'timelimit' => null,
        ]);

        // Attempt clatest2 as user1.
        $this->attempt_clatest($clatest2, $user1);

        // Delete the data for user1 and user3 in course1 and check it is removed.
        $clatest1context = $clatest1obj->get_context();
        $approveduserlist = new \core_privacy\local\request\approved_userlist($clatest1context, 'mod_clatest',
                [$user1->id, $user3->id]);
        provider::delete_data_for_users($approveduserlist);

        // Only the attempt of user2 should be remained in clatest1.
        $this->assertEquals(
                [$user2->id],
                $DB->get_fieldset_select('clatest_attempts', 'userid', 'clatest = ?', [$clatest1->id])
        );

        // The attempt that user1 made in clatest2 should be remained.
        $this->assertEquals(
                [$user1->id],
                $DB->get_fieldset_select('clatest_attempts', 'userid', 'clatest = ?', [$clatest2->id])
        );

        // The clatest override in clatest1 that we had for user3 should be deleted.
        $this->assertEquals(
                [],
                $DB->get_fieldset_select('clatest_overrides', 'userid', 'clatest = ?', [$clatest1->id])
        );
    }
}
