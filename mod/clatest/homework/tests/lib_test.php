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
 * Unit tests for (some of) mod/homework/locallib.php.
 *
 * @package    mod_homework
 * @category   test
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/homework/lib.php');

/**
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class mod_homework_lib_testcase extends advanced_testcase {
    public function test_homework_has_grades() {
        $homework = new stdClass();
        $homework->grade = '100.0000';
        $homework->sumgrades = '100.0000';
        $this->assertTrue(homework_has_grades($homework));
        $homework->sumgrades = '0.0000';
        $this->assertFalse(homework_has_grades($homework));
        $homework->grade = '0.0000';
        $this->assertFalse(homework_has_grades($homework));
        $homework->sumgrades = '100.0000';
        $this->assertFalse(homework_has_grades($homework));
    }

    public function test_homework_format_grade() {
        $homework = new stdClass();
        $homework->decimalpoints = 2;
        $this->assertEquals(homework_format_grade($homework, 0.12345678), format_float(0.12, 2));
        $this->assertEquals(homework_format_grade($homework, 0), format_float(0, 2));
        $this->assertEquals(homework_format_grade($homework, 1.000000000000), format_float(1, 2));
        $homework->decimalpoints = 0;
        $this->assertEquals(homework_format_grade($homework, 0.12345678), '0');
    }

    public function test_homework_get_grade_format() {
        $homework = new stdClass();
        $homework->decimalpoints = 2;
        $this->assertEquals(homework_get_grade_format($homework), 2);
        $this->assertEquals($homework->questiondecimalpoints, -1);
        $homework->questiondecimalpoints = 2;
        $this->assertEquals(homework_get_grade_format($homework), 2);
        $homework->decimalpoints = 3;
        $homework->questiondecimalpoints = -1;
        $this->assertEquals(homework_get_grade_format($homework), 3);
        $homework->questiondecimalpoints = 4;
        $this->assertEquals(homework_get_grade_format($homework), 4);
    }

    public function test_homework_format_question_grade() {
        $homework = new stdClass();
        $homework->decimalpoints = 2;
        $homework->questiondecimalpoints = 2;
        $this->assertEquals(homework_format_question_grade($homework, 0.12345678), format_float(0.12, 2));
        $this->assertEquals(homework_format_question_grade($homework, 0), format_float(0, 2));
        $this->assertEquals(homework_format_question_grade($homework, 1.000000000000), format_float(1, 2));
        $homework->decimalpoints = 3;
        $homework->questiondecimalpoints = -1;
        $this->assertEquals(homework_format_question_grade($homework, 0.12345678), format_float(0.123, 3));
        $this->assertEquals(homework_format_question_grade($homework, 0), format_float(0, 3));
        $this->assertEquals(homework_format_question_grade($homework, 1.000000000000), format_float(1, 3));
        $homework->questiondecimalpoints = 4;
        $this->assertEquals(homework_format_question_grade($homework, 0.12345678), format_float(0.1235, 4));
        $this->assertEquals(homework_format_question_grade($homework, 0), format_float(0, 4));
        $this->assertEquals(homework_format_question_grade($homework, 1.000000000000), format_float(1, 4));
    }

    /**
     * Test deleting a homework instance.
     */
    public function test_homework_delete_instance() {
        global $SITE, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Setup a homework with 1 standard and 1 random question.
        $homeworkgenerator = $this->getDataGenerator()->get_plugin_generator('mod_homework');
        $homework = $homeworkgenerator->create_instance(array('course' => $SITE->id, 'questionsperpage' => 3, 'grade' => 100.0));

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $standardq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));

        homework_add_homework_question($standardq->id, $homework);
        homework_add_random_questions($homework, 0, $cat->id, 1, false);

        // Get the random question.
        $randomq = $DB->get_record('question', array('qtype' => 'random'));

        homework_delete_instance($homework->id);

        // Check that the random question was deleted.
        $count = $DB->count_records('question', array('id' => $randomq->id));
        $this->assertEquals(0, $count);
        // Check that the standard question was not deleted.
        $count = $DB->count_records('question', array('id' => $standardq->id));
        $this->assertEquals(1, $count);

        // Check that all the slots were removed.
        $count = $DB->count_records('homework_slots', array('homeworkid' => $homework->id));
        $this->assertEquals(0, $count);

        // Check that the homework was removed.
        $count = $DB->count_records('homework', array('id' => $homework->id));
        $this->assertEquals(0, $count);
    }

    /**
     * Test checking the completion state of a homework.
     */
    public function test_homework_get_completion_state() {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        $CFG->enablecompletion = true;

        // Create a course and student.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $passstudent = $this->getDataGenerator()->create_user();
        $failstudent = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);

        // Enrol students.
        $this->assertTrue($this->getDataGenerator()->enrol_user($passstudent->id, $course->id, $studentrole->id));
        $this->assertTrue($this->getDataGenerator()->enrol_user($failstudent->id, $course->id, $studentrole->id));

        // Make a scale and an outcome.
        $scale = $this->getDataGenerator()->create_scale();
        $data = array('courseid' => $course->id,
                      'fullname' => 'Team work',
                      'shortname' => 'Team work',
                      'scaleid' => $scale->id);
        $outcome = $this->getDataGenerator()->create_grade_outcome($data);

        // Make a homework with the outcome on.
        $homeworkgenerator = $this->getDataGenerator()->get_plugin_generator('mod_homework');
        $data = array('course' => $course->id,
                      'outcome_'.$outcome->id => 1,
                      'grade' => 100.0,
                      'questionsperpage' => 0,
                      'sumgrades' => 1,
                      'completion' => COMPLETION_TRACKING_AUTOMATIC,
                      'completionpass' => 1);
        $homework = $homeworkgenerator->create_instance($data);
        $cm = get_coursemodule_from_id('homework', $homework->cmid);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        homework_add_homework_question($question->id, $homework);

        $homeworkobj = homework::create($homework->id, $passstudent->id);

        // Set grade to pass.
        $item = grade_item::fetch(array('courseid' => $course->id, 'itemtype' => 'mod',
                                        'itemmodule' => 'homework', 'iteminstance' => $homework->id, 'outcomeid' => null));
        $item->gradepass = 80;
        $item->update();

        // Start the passing attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj->get_context());
        $quba->set_preferred_behaviour($homeworkobj->get_homework()->preferredbehaviour);

        $timenow = time();
        $attempt = homework_create_attempt($homeworkobj, 1, false, $timenow, false, $passstudent->id);
        homework_start_new_attempt($homeworkobj, $quba, $attempt, 1, $timenow);
        homework_attempt_save_started($homeworkobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = homework_attempt::create($attempt->id);
        $tosubmit = array(1 => array('answer' => '3.14'));
        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = homework_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        // Start the failing attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj->get_context());
        $quba->set_preferred_behaviour($homeworkobj->get_homework()->preferredbehaviour);

        $timenow = time();
        $attempt = homework_create_attempt($homeworkobj, 1, false, $timenow, false, $failstudent->id);
        homework_start_new_attempt($homeworkobj, $quba, $attempt, 1, $timenow);
        homework_attempt_save_started($homeworkobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = homework_attempt::create($attempt->id);
        $tosubmit = array(1 => array('answer' => '0'));
        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = homework_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        // Check the results.
        $this->assertTrue(homework_get_completion_state($course, $cm, $passstudent->id, 'return'));
        $this->assertFalse(homework_get_completion_state($course, $cm, $failstudent->id, 'return'));
    }

    public function test_homework_get_user_attempts() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $homeworkgen = $dg->get_plugin_generator('mod_homework');
        $course = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();
        $role = $DB->get_record('role', ['shortname' => 'student']);

        $dg->enrol_user($u1->id, $course->id, $role->id);
        $dg->enrol_user($u2->id, $course->id, $role->id);
        $dg->enrol_user($u3->id, $course->id, $role->id);
        $dg->enrol_user($u4->id, $course->id, $role->id);

        $homework1 = $homeworkgen->create_instance(['course' => $course->id, 'sumgrades' => 2]);
        $homework2 = $homeworkgen->create_instance(['course' => $course->id, 'sumgrades' => 2]);

        // Questions.
        $questgen = $dg->get_plugin_generator('core_question');
        $homeworkcat = $questgen->create_question_category();
        $question = $questgen->create_question('numerical', null, ['category' => $homeworkcat->id]);
        homework_add_homework_question($question->id, $homework1);
        homework_add_homework_question($question->id, $homework2);

        $homeworkobj1a = homework::create($homework1->id, $u1->id);
        $homeworkobj1b = homework::create($homework1->id, $u2->id);
        $homeworkobj1c = homework::create($homework1->id, $u3->id);
        $homeworkobj1d = homework::create($homework1->id, $u4->id);
        $homeworkobj2a = homework::create($homework2->id, $u1->id);

        // Set attempts.
        $quba1a = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj1a->get_context());
        $quba1a->set_preferred_behaviour($homeworkobj1a->get_homework()->preferredbehaviour);
        $quba1b = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj1b->get_context());
        $quba1b->set_preferred_behaviour($homeworkobj1b->get_homework()->preferredbehaviour);
        $quba1c = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj1c->get_context());
        $quba1c->set_preferred_behaviour($homeworkobj1c->get_homework()->preferredbehaviour);
        $quba1d = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj1d->get_context());
        $quba1d->set_preferred_behaviour($homeworkobj1d->get_homework()->preferredbehaviour);
        $quba2a = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj2a->get_context());
        $quba2a->set_preferred_behaviour($homeworkobj2a->get_homework()->preferredbehaviour);

        $timenow = time();

        // User 1 passes homework 1.
        $attempt = homework_create_attempt($homeworkobj1a, 1, false, $timenow, false, $u1->id);
        homework_start_new_attempt($homeworkobj1a, $quba1a, $attempt, 1, $timenow);
        homework_attempt_save_started($homeworkobj1a, $quba1a, $attempt);
        $attemptobj = homework_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        // User 2 goes overdue in homework 1.
        $attempt = homework_create_attempt($homeworkobj1b, 1, false, $timenow, false, $u2->id);
        homework_start_new_attempt($homeworkobj1b, $quba1b, $attempt, 1, $timenow);
        homework_attempt_save_started($homeworkobj1b, $quba1b, $attempt);
        $attemptobj = homework_attempt::create($attempt->id);
        $attemptobj->process_going_overdue($timenow, true);

        // User 3 does not finish homework 1.
        $attempt = homework_create_attempt($homeworkobj1c, 1, false, $timenow, false, $u3->id);
        homework_start_new_attempt($homeworkobj1c, $quba1c, $attempt, 1, $timenow);
        homework_attempt_save_started($homeworkobj1c, $quba1c, $attempt);

        // User 4 abandons the homework 1.
        $attempt = homework_create_attempt($homeworkobj1d, 1, false, $timenow, false, $u4->id);
        homework_start_new_attempt($homeworkobj1d, $quba1d, $attempt, 1, $timenow);
        homework_attempt_save_started($homeworkobj1d, $quba1d, $attempt);
        $attemptobj = homework_attempt::create($attempt->id);
        $attemptobj->process_abandon($timenow, true);

        // User 1 attempts the homework three times (abandon, finish, in progress).
        $quba2a = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj2a->get_context());
        $quba2a->set_preferred_behaviour($homeworkobj2a->get_homework()->preferredbehaviour);

        $attempt = homework_create_attempt($homeworkobj2a, 1, false, $timenow, false, $u1->id);
        homework_start_new_attempt($homeworkobj2a, $quba2a, $attempt, 1, $timenow);
        homework_attempt_save_started($homeworkobj2a, $quba2a, $attempt);
        $attemptobj = homework_attempt::create($attempt->id);
        $attemptobj->process_abandon($timenow, true);

        $quba2a = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj2a->get_context());
        $quba2a->set_preferred_behaviour($homeworkobj2a->get_homework()->preferredbehaviour);

        $attempt = homework_create_attempt($homeworkobj2a, 2, false, $timenow, false, $u1->id);
        homework_start_new_attempt($homeworkobj2a, $quba2a, $attempt, 2, $timenow);
        homework_attempt_save_started($homeworkobj2a, $quba2a, $attempt);
        $attemptobj = homework_attempt::create($attempt->id);
        $attemptobj->process_finish($timenow, false);

        $quba2a = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj2a->get_context());
        $quba2a->set_preferred_behaviour($homeworkobj2a->get_homework()->preferredbehaviour);

        $attempt = homework_create_attempt($homeworkobj2a, 3, false, $timenow, false, $u1->id);
        homework_start_new_attempt($homeworkobj2a, $quba2a, $attempt, 3, $timenow);
        homework_attempt_save_started($homeworkobj2a, $quba2a, $attempt);

        // Check for user 1.
        $attempts = homework_get_user_attempts($homework1->id, $u1->id, 'all');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::FINISHED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($homework1->id, $attempt->homework);

        $attempts = homework_get_user_attempts($homework1->id, $u1->id, 'finished');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::FINISHED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($homework1->id, $attempt->homework);

        $attempts = homework_get_user_attempts($homework1->id, $u1->id, 'unfinished');
        $this->assertCount(0, $attempts);

        // Check for user 2.
        $attempts = homework_get_user_attempts($homework1->id, $u2->id, 'all');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::OVERDUE, $attempt->state);
        $this->assertEquals($u2->id, $attempt->userid);
        $this->assertEquals($homework1->id, $attempt->homework);

        $attempts = homework_get_user_attempts($homework1->id, $u2->id, 'finished');
        $this->assertCount(0, $attempts);

        $attempts = homework_get_user_attempts($homework1->id, $u2->id, 'unfinished');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::OVERDUE, $attempt->state);
        $this->assertEquals($u2->id, $attempt->userid);
        $this->assertEquals($homework1->id, $attempt->homework);

        // Check for user 3.
        $attempts = homework_get_user_attempts($homework1->id, $u3->id, 'all');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::IN_PROGRESS, $attempt->state);
        $this->assertEquals($u3->id, $attempt->userid);
        $this->assertEquals($homework1->id, $attempt->homework);

        $attempts = homework_get_user_attempts($homework1->id, $u3->id, 'finished');
        $this->assertCount(0, $attempts);

        $attempts = homework_get_user_attempts($homework1->id, $u3->id, 'unfinished');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::IN_PROGRESS, $attempt->state);
        $this->assertEquals($u3->id, $attempt->userid);
        $this->assertEquals($homework1->id, $attempt->homework);

        // Check for user 4.
        $attempts = homework_get_user_attempts($homework1->id, $u4->id, 'all');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::ABANDONED, $attempt->state);
        $this->assertEquals($u4->id, $attempt->userid);
        $this->assertEquals($homework1->id, $attempt->homework);

        $attempts = homework_get_user_attempts($homework1->id, $u4->id, 'finished');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::ABANDONED, $attempt->state);
        $this->assertEquals($u4->id, $attempt->userid);
        $this->assertEquals($homework1->id, $attempt->homework);

        $attempts = homework_get_user_attempts($homework1->id, $u4->id, 'unfinished');
        $this->assertCount(0, $attempts);

        // Multiple attempts for user 1 in homework 2.
        $attempts = homework_get_user_attempts($homework2->id, $u1->id, 'all');
        $this->assertCount(3, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::ABANDONED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($homework2->id, $attempt->homework);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::FINISHED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($homework2->id, $attempt->homework);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::IN_PROGRESS, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($homework2->id, $attempt->homework);

        $attempts = homework_get_user_attempts($homework2->id, $u1->id, 'finished');
        $this->assertCount(2, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::ABANDONED, $attempt->state);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::FINISHED, $attempt->state);

        $attempts = homework_get_user_attempts($homework2->id, $u1->id, 'unfinished');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);

        // Multiple homework attempts fetched at once.
        $attempts = homework_get_user_attempts([$homework1->id, $homework2->id], $u1->id, 'all');
        $this->assertCount(4, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::FINISHED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($homework1->id, $attempt->homework);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::ABANDONED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($homework2->id, $attempt->homework);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::FINISHED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($homework2->id, $attempt->homework);
        $attempt = array_shift($attempts);
        $this->assertEquals(homework_attempt::IN_PROGRESS, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($homework2->id, $attempt->homework);
    }

    /**
     * Test for homework_get_group_override_priorities().
     */
    public function test_homework_get_group_override_priorities() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $homeworkgen = $dg->get_plugin_generator('mod_homework');
        $course = $dg->create_course();

        $homework = $homeworkgen->create_instance(['course' => $course->id, 'sumgrades' => 2]);

        $this->assertNull(homework_get_group_override_priorities($homework->id));

        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        $now = 100;
        $override1 = (object)[
            'homework' => $homework->id,
            'groupid' => $group1->id,
            'timeopen' => $now,
            'timeclose' => $now + 20
        ];
        $DB->insert_record('homework_overrides', $override1);

        $override2 = (object)[
            'homework' => $homework->id,
            'groupid' => $group2->id,
            'timeopen' => $now - 10,
            'timeclose' => $now + 10
        ];
        $DB->insert_record('homework_overrides', $override2);

        $priorities = homework_get_group_override_priorities($homework->id);
        $this->assertNotEmpty($priorities);

        $openpriorities = $priorities['open'];
        // Override 2's time open has higher priority since it is sooner than override 1's.
        $this->assertEquals(2, $openpriorities[$override1->timeopen]);
        $this->assertEquals(1, $openpriorities[$override2->timeopen]);

        $closepriorities = $priorities['close'];
        // Override 1's time close has higher priority since it is later than override 2's.
        $this->assertEquals(1, $closepriorities[$override1->timeclose]);
        $this->assertEquals(2, $closepriorities[$override2->timeclose]);
    }

    public function test_homework_core_calendar_provide_event_action_open() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a homework.
        $homework = $this->getDataGenerator()->create_module('homework', array('course' => $course->id,
            'timeopen' => time() - DAYSECS, 'timeclose' => time() + DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $homework->id, QUIZ_EVENT_TYPE_OPEN);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_homework_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('attempthomeworknow', 'homework'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_homework_core_calendar_provide_event_action_closed() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a homework.
        $homework = $this->getDataGenerator()->create_module('homework', array('course' => $course->id,
            'timeclose' => time() - DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $homework->id, QUIZ_EVENT_TYPE_CLOSE);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Confirm the result was null.
        $this->assertNull(mod_homework_core_calendar_provide_event_action($event, $factory));
    }

    public function test_homework_core_calendar_provide_event_action_open_in_future() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a homework.
        $homework = $this->getDataGenerator()->create_module('homework', array('course' => $course->id,
            'timeopen' => time() + DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $homework->id, QUIZ_EVENT_TYPE_CLOSE);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_homework_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('attempthomeworknow', 'homework'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertFalse($actionevent->is_actionable());
    }

    public function test_homework_core_calendar_provide_event_action_no_capability() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a student.
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Enrol student.
        $this->assertTrue($this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id));

        // Create a homework.
        $homework = $this->getDataGenerator()->create_module('homework', array('course' => $course->id));

        // Remove the permission to attempt or review the homework for the student role.
        $coursecontext = context_course::instance($course->id);
        assign_capability('mod/homework:reviewmyattempts', CAP_PROHIBIT, $studentrole->id, $coursecontext);
        assign_capability('mod/homework:attempt', CAP_PROHIBIT, $studentrole->id, $coursecontext);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $homework->id, QUIZ_EVENT_TYPE_OPEN);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Set current user to the student.
        $this->setUser($student);

        // Confirm null is returned.
        $this->assertNull(mod_homework_core_calendar_provide_event_action($event, $factory));
    }

    public function test_homework_core_calendar_provide_event_action_already_finished() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a student.
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Enrol student.
        $this->assertTrue($this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id));

        // Create a homework.
        $homework = $this->getDataGenerator()->create_module('homework', array('course' => $course->id,
            'sumgrades' => 1));

        // Add a question to the homework.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        homework_add_homework_question($question->id, $homework);

        // Get the homework object.
        $homeworkobj = homework::create($homework->id, $student->id);

        // Create an attempt for the student in the homework.
        $timenow = time();
        $attempt = homework_create_attempt($homeworkobj, 1, false, $timenow, false, $student->id);
        $quba = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj->get_context());
        $quba->set_preferred_behaviour($homeworkobj->get_homework()->preferredbehaviour);
        homework_start_new_attempt($homeworkobj, $quba, $attempt, 1, $timenow);
        homework_attempt_save_started($homeworkobj, $quba, $attempt);

        // Finish the attempt.
        $attemptobj = homework_attempt::create($attempt->id);
        $attemptobj->process_finish($timenow, false);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $homework->id, QUIZ_EVENT_TYPE_OPEN);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Set current user to the student.
        $this->setUser($student);

        // Confirm null is returned.
        $this->assertNull(mod_homework_core_calendar_provide_event_action($event, $factory));
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid
     * @param int $instanceid The homework id.
     * @param string $eventtype The event type. eg. QUIZ_EVENT_TYPE_OPEN.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype) {
        $event = new stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = 'homework';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return calendar_event::create($event);
    }

    /**
     * Test the callback responsible for returning the completion rule descriptions.
     * This function should work given either an instance of the module (cm_info), such as when checking the active rules,
     * or if passed a stdClass of similar structure, such as when checking the the default completion settings for a mod type.
     */
    public function test_mod_homework_completion_get_active_rule_descriptions() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Two activities, both with automatic completion. One has the 'completionsubmit' rule, one doesn't.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 2]);
        $homework1 = $this->getDataGenerator()->create_module('homework', [
            'course' => $course->id,
            'completion' => 2,
            'completionattemptsexhausted' => 1,
            'completionpass' => 1
        ]);
        $homework2 = $this->getDataGenerator()->create_module('homework', [
            'course' => $course->id,
            'completion' => 2,
            'completionattemptsexhausted' => 0,
            'completionpass' => 0
        ]);
        $cm1 = cm_info::create(get_coursemodule_from_instance('homework', $homework1->id));
        $cm2 = cm_info::create(get_coursemodule_from_instance('homework', $homework2->id));

        // Data for the stdClass input type.
        // This type of input would occur when checking the default completion rules for an activity type, where we don't have
        // any access to cm_info, rather the input is a stdClass containing completion and customdata attributes, just like cm_info.
        $moddefaults = new stdClass();
        $moddefaults->customdata = ['customcompletionrules' => [
            'completionattemptsexhausted' => 1,
            'completionpass' => 1
        ]];
        $moddefaults->completion = 2;

        $activeruledescriptions = [
            get_string('completionattemptsexhausteddesc', 'homework'),
            get_string('completionpassdesc', 'homework'),
        ];
        $this->assertEquals(mod_homework_get_completion_active_rule_descriptions($cm1), $activeruledescriptions);
        $this->assertEquals(mod_homework_get_completion_active_rule_descriptions($cm2), []);
        $this->assertEquals(mod_homework_get_completion_active_rule_descriptions($moddefaults), $activeruledescriptions);
        $this->assertEquals(mod_homework_get_completion_active_rule_descriptions(new stdClass()), []);
    }

    /**
     * A user who does not have capabilities to add events to the calendar should be able to create a homework.
     */
    public function test_creation_with_no_calendar_capabilities() {
        $this->resetAfterTest();
        $course = self::getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $user = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $roleid = self::getDataGenerator()->create_role();
        self::getDataGenerator()->role_assign($roleid, $user->id, $context->id);
        assign_capability('moodle/calendar:manageentries', CAP_PROHIBIT, $roleid, $context, true);
        $generator = self::getDataGenerator()->get_plugin_generator('mod_homework');
        // Create an instance as a user without the calendar capabilities.
        $this->setUser($user);
        $time = time();
        $params = array(
            'course' => $course->id,
            'timeopen' => $time + 200,
            'timeclose' => $time + 2000,
        );
        $generator->create_instance($params);
    }
}
