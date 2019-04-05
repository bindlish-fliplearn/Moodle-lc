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
 * Quiz module external functions tests.
 *
 * @package    mod_pratest
 * @category   external
 * @copyright  2016 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Silly class to access mod_pratest_external internal methods.
 *
 * @package mod_pratest
 * @copyright 2016 Juan Leyva <juan@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since  Moodle 3.1
 */
class testable_mod_pratest_external extends mod_pratest_external {

    /**
     * Public accessor.
     *
     * @param  array $params Array of parameters including the attemptid and preflight data
     * @param  bool $checkaccessrules whether to check the pratest access rules or not
     * @param  bool $failifoverdue whether to return error if the attempt is overdue
     * @return  array containing the attempt object and access messages
     */
    public static function validate_attempt($params, $checkaccessrules = true, $failifoverdue = true) {
        return parent::validate_attempt($params, $checkaccessrules, $failifoverdue);
    }

    /**
     * Public accessor.
     *
     * @param  array $params Array of parameters including the attemptid
     * @return  array containing the attempt object and display options
     */
    public static function validate_attempt_review($params) {
        return parent::validate_attempt_review($params);
    }
}

/**
 * Quiz module external functions tests
 *
 * @package    mod_pratest
 * @category   external
 * @copyright  2016 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class mod_pratest_external_testcase extends externallib_advanced_testcase {

    /**
     * Set up for every test
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Setup test data.
        $this->course = $this->getDataGenerator()->create_course();
        $this->pratest = $this->getDataGenerator()->create_module('pratest', array('course' => $this->course->id));
        $this->context = context_module::instance($this->pratest->cmid);
        $this->cm = get_coursemodule_from_instance('pratest', $this->pratest->id);

        // Create users.
        $this->student = self::getDataGenerator()->create_user();
        $this->teacher = self::getDataGenerator()->create_user();

        // Users enrolments.
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $this->studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $this->teacherrole->id, 'manual');
    }

    /**
     * Create a pratest with questions including a started or finished attempt optionally
     *
     * @param  boolean $startattempt whether to start a new attempt
     * @param  boolean $finishattempt whether to finish the new attempt
     * @param  string $behaviour the pratest preferredbehaviour, defaults to 'deferredfeedback'.
     * @return array array containing the pratest, context and the attempt
     */
    private function create_pratest_with_questions($startattempt = false, $finishattempt = false, $behaviour = 'deferredfeedback') {

        // Create a new pratest with attempts.
        $pratestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pratest');
        $data = array('course' => $this->course->id,
                      'sumgrades' => 2,
                      'preferredbehaviour' => $behaviour);
        $pratest = $pratestgenerator->create_instance($data);
        $context = context_module::instance($pratest->cmid);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        pratest_add_pratest_question($question->id, $pratest);
        $question = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        pratest_add_pratest_question($question->id, $pratest);

        $pratestobj = pratest::create($pratest->id, $this->student->id);

        // Set grade to pass.
        $item = grade_item::fetch(array('courseid' => $this->course->id, 'itemtype' => 'mod',
                                        'itemmodule' => 'pratest', 'iteminstance' => $pratest->id, 'outcomeid' => null));
        $item->gradepass = 80;
        $item->update();

        if ($startattempt or $finishattempt) {
            // Now, do one attempt.
            $quba = question_engine::make_questions_usage_by_activity('mod_pratest', $pratestobj->get_context());
            $quba->set_preferred_behaviour($pratestobj->get_pratest()->preferredbehaviour);

            $timenow = time();
            $attempt = pratest_create_attempt($pratestobj, 1, false, $timenow, false, $this->student->id);
            pratest_start_new_attempt($pratestobj, $quba, $attempt, 1, $timenow);
            pratest_attempt_save_started($pratestobj, $quba, $attempt);
            $attemptobj = pratest_attempt::create($attempt->id);

            if ($finishattempt) {
                // Process some responses from the student.
                $tosubmit = array(1 => array('answer' => '3.14'));
                $attemptobj->process_submitted_actions(time(), false, $tosubmit);

                // Finish the attempt.
                $attemptobj->process_finish(time(), false);
            }
            return array($pratest, $context, $pratestobj, $attempt, $attemptobj, $quba);
        } else {
            return array($pratest, $context, $pratestobj);
        }

    }

    /*
     * Test get pratestzes by courses
     */
    public function test_mod_pratest_get_pratestzes_by_courses() {
        global $DB;

        // Create additional course.
        $course2 = self::getDataGenerator()->create_course();

        // Second pratest.
        $record = new stdClass();
        $record->course = $course2->id;
        $pratest2 = self::getDataGenerator()->create_module('pratest', $record);

        // Execute real Moodle enrolment as we'll call unenrol() method on the instance later.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course2->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance2 = $courseenrolinstance;
                break;
            }
        }
        $enrol->enrol_user($instance2, $this->student->id, $this->studentrole->id);

        self::setUser($this->student);

        $returndescription = mod_pratest_external::get_pratestzes_by_courses_returns();

        // Create what we expect to be returned when querying the two courses.
        // First for the student user.
        $allusersfields = array('id', 'coursemodule', 'course', 'name', 'intro', 'introformat', 'introfiles', 'timeopen',
                                'timeclose', 'grademethod', 'section', 'visible', 'groupmode', 'groupingid',
                                'attempts', 'timelimit', 'grademethod', 'decimalpoints', 'questiondecimalpoints', 'sumgrades',
                                'grade', 'preferredbehaviour', 'hasfeedback');
        $userswithaccessfields = array('attemptonlast', 'reviewattempt', 'reviewcorrectness', 'reviewmarks',
                                        'reviewspecificfeedback', 'reviewgeneralfeedback', 'reviewrightanswer',
                                        'reviewoverallfeedback', 'questionsperpage', 'navmethod',
                                        'browsersecurity', 'delay1', 'delay2', 'showuserpicture', 'showblocks',
                                        'completionattemptsexhausted', 'completionpass', 'autosaveperiod', 'hasquestions',
                                        'overduehandling', 'graceperiod', 'canredoquestions', 'allowofflineattempts');
        $managerfields = array('shuffleanswers', 'timecreated', 'timemodified', 'password', 'subnet');

        // Add expected coursemodule and other data.
        $pratest1 = $this->pratest;
        $pratest1->coursemodule = $pratest1->cmid;
        $pratest1->introformat = 1;
        $pratest1->section = 0;
        $pratest1->visible = true;
        $pratest1->groupmode = 0;
        $pratest1->groupingid = 0;
        $pratest1->hasquestions = 0;
        $pratest1->hasfeedback = 0;
        $pratest1->autosaveperiod = get_config('pratest', 'autosaveperiod');
        $pratest1->introfiles = [];

        $pratest2->coursemodule = $pratest2->cmid;
        $pratest2->introformat = 1;
        $pratest2->section = 0;
        $pratest2->visible = true;
        $pratest2->groupmode = 0;
        $pratest2->groupingid = 0;
        $pratest2->hasquestions = 0;
        $pratest2->hasfeedback = 0;
        $pratest2->autosaveperiod = get_config('pratest', 'autosaveperiod');
        $pratest2->introfiles = [];

        foreach (array_merge($allusersfields, $userswithaccessfields) as $field) {
            $expected1[$field] = $pratest1->{$field};
            $expected2[$field] = $pratest2->{$field};
        }

        $expectedpratestzes = array($expected2, $expected1);

        // Call the external function passing course ids.
        $result = mod_pratest_external::get_pratestzes_by_courses(array($course2->id, $this->course->id));
        $result = external_api::clean_returnvalue($returndescription, $result);

        $this->assertEquals($expectedpratestzes, $result['pratestzes']);
        $this->assertCount(0, $result['warnings']);

        // Call the external function without passing course id.
        $result = mod_pratest_external::get_pratestzes_by_courses();
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertEquals($expectedpratestzes, $result['pratestzes']);
        $this->assertCount(0, $result['warnings']);

        // Unenrol user from second course and alter expected pratestzes.
        $enrol->unenrol_user($instance2, $this->student->id);
        array_shift($expectedpratestzes);

        // Call the external function without passing course id.
        $result = mod_pratest_external::get_pratestzes_by_courses();
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertEquals($expectedpratestzes, $result['pratestzes']);

        // Call for the second course we unenrolled the user from, expected warning.
        $result = mod_pratest_external::get_pratestzes_by_courses(array($course2->id));
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals('1', $result['warnings'][0]['warningcode']);
        $this->assertEquals($course2->id, $result['warnings'][0]['itemid']);

        // Now, try as a teacher for getting all the additional fields.
        self::setUser($this->teacher);

        foreach ($managerfields as $field) {
            $expectedpratestzes[0][$field] = $pratest1->{$field};
        }

        $result = mod_pratest_external::get_pratestzes_by_courses();
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertEquals($expectedpratestzes, $result['pratestzes']);

        // Admin also should get all the information.
        self::setAdminUser();

        $result = mod_pratest_external::get_pratestzes_by_courses(array($this->course->id));
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertEquals($expectedpratestzes, $result['pratestzes']);

        // Now, prevent access.
        $enrol->enrol_user($instance2, $this->student->id);

        self::setUser($this->student);

        $pratest2->timeclose = time() - DAYSECS;
        $DB->update_record('pratest', $pratest2);

        $result = mod_pratest_external::get_pratestzes_by_courses();
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertCount(2, $result['pratestzes']);
        // We only see a limited set of fields.
        $this->assertCount(4, $result['pratestzes'][0]);
        $this->assertEquals($pratest2->id, $result['pratestzes'][0]['id']);
        $this->assertEquals($pratest2->coursemodule, $result['pratestzes'][0]['coursemodule']);
        $this->assertEquals($pratest2->course, $result['pratestzes'][0]['course']);
        $this->assertEquals($pratest2->name, $result['pratestzes'][0]['name']);
        $this->assertEquals($pratest2->course, $result['pratestzes'][0]['course']);

        $this->assertFalse(isset($result['pratestzes'][0]['timelimit']));

    }

    /**
     * Test test_view_pratest
     */
    public function test_view_pratest() {
        global $DB;

        // Test invalid instance id.
        try {
            mod_pratest_external::view_pratest(0);
            $this->fail('Exception expected due to invalid mod_pratest instance id.');
        } catch (moodle_exception $e) {
            $this->assertEquals('invalidrecord', $e->errorcode);
        }

        // Test not-enrolled user.
        $usernotenrolled = self::getDataGenerator()->create_user();
        $this->setUser($usernotenrolled);
        try {
            mod_pratest_external::view_pratest($this->pratest->id);
            $this->fail('Exception expected due to not enrolled user.');
        } catch (moodle_exception $e) {
            $this->assertEquals('requireloginerror', $e->errorcode);
        }

        // Test user with full capabilities.
        $this->setUser($this->student);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $result = mod_pratest_external::view_pratest($this->pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::view_pratest_returns(), $result);
        $this->assertTrue($result['status']);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pratest\event\course_module_viewed', $event);
        $this->assertEquals($this->context, $event->get_context());
        $moodlepratest = new \moodle_url('/mod/pratest/view.php', array('id' => $this->cm->id));
        $this->assertEquals($moodlepratest, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

        // Test user with no capabilities.
        // We need a explicit prohibit since this capability is only defined in authenticated user and guest roles.
        assign_capability('mod/pratest:view', CAP_PROHIBIT, $this->studentrole->id, $this->context->id);
        // Empty all the caches that may be affected  by this change.
        accesslib_clear_all_caches_for_unit_testing();
        course_modinfo::clear_instance_cache();

        try {
            mod_pratest_external::view_pratest($this->pratest->id);
            $this->fail('Exception expected due to missing capability.');
        } catch (moodle_exception $e) {
            $this->assertEquals('requireloginerror', $e->errorcode);
        }

    }

    /**
     * Test get_user_attempts
     */
    public function test_get_user_attempts() {

        // Create a pratest with one attempt finished.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj) = $this->create_pratest_with_questions(true, true);

        $this->setUser($this->student);
        $result = mod_pratest_external::get_user_attempts($pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_attempts_returns(), $result);

        $this->assertCount(1, $result['attempts']);
        $this->assertEquals($attempt->id, $result['attempts'][0]['id']);
        $this->assertEquals($pratest->id, $result['attempts'][0]['pratest']);
        $this->assertEquals($this->student->id, $result['attempts'][0]['userid']);
        $this->assertEquals(1, $result['attempts'][0]['attempt']);

        // Test filters. Only finished.
        $result = mod_pratest_external::get_user_attempts($pratest->id, 0, 'finished', false);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_attempts_returns(), $result);

        $this->assertCount(1, $result['attempts']);
        $this->assertEquals($attempt->id, $result['attempts'][0]['id']);

        // Test filters. All attempts.
        $result = mod_pratest_external::get_user_attempts($pratest->id, 0, 'all', false);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_attempts_returns(), $result);

        $this->assertCount(1, $result['attempts']);
        $this->assertEquals($attempt->id, $result['attempts'][0]['id']);

        // Test filters. Unfinished.
        $result = mod_pratest_external::get_user_attempts($pratest->id, 0, 'unfinished', false);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_attempts_returns(), $result);

        $this->assertCount(0, $result['attempts']);

        // Start a new attempt, but not finish it.
        $timenow = time();
        $attempt = pratest_create_attempt($pratestobj, 2, false, $timenow, false, $this->student->id);
        $quba = question_engine::make_questions_usage_by_activity('mod_pratest', $pratestobj->get_context());
        $quba->set_preferred_behaviour($pratestobj->get_pratest()->preferredbehaviour);

        pratest_start_new_attempt($pratestobj, $quba, $attempt, 1, $timenow);
        pratest_attempt_save_started($pratestobj, $quba, $attempt);

        // Test filters. All attempts.
        $result = mod_pratest_external::get_user_attempts($pratest->id, 0, 'all', false);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_attempts_returns(), $result);

        $this->assertCount(2, $result['attempts']);

        // Test filters. Unfinished.
        $result = mod_pratest_external::get_user_attempts($pratest->id, 0, 'unfinished', false);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_attempts_returns(), $result);

        $this->assertCount(1, $result['attempts']);

        // Test manager can see user attempts.
        $this->setUser($this->teacher);
        $result = mod_pratest_external::get_user_attempts($pratest->id, $this->student->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_attempts_returns(), $result);

        $this->assertCount(1, $result['attempts']);
        $this->assertEquals($this->student->id, $result['attempts'][0]['userid']);

        $result = mod_pratest_external::get_user_attempts($pratest->id, $this->student->id, 'all');
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_attempts_returns(), $result);

        $this->assertCount(2, $result['attempts']);
        $this->assertEquals($this->student->id, $result['attempts'][0]['userid']);

        // Invalid parameters.
        try {
            mod_pratest_external::get_user_attempts($pratest->id, $this->student->id, 'INVALID_PARAMETER');
            $this->fail('Exception expected due to missing capability.');
        } catch (invalid_parameter_exception $e) {
            $this->assertEquals('invalidparameter', $e->errorcode);
        }
    }

    /**
     * Test get_user_best_grade
     */
    public function test_get_user_best_grade() {
        global $DB;

        $this->setUser($this->student);

        $result = mod_pratest_external::get_user_best_grade($this->pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_best_grade_returns(), $result);

        // No grades yet.
        $this->assertFalse($result['hasgrade']);
        $this->assertTrue(!isset($result['grade']));

        $grade = new stdClass();
        $grade->pratest = $this->pratest->id;
        $grade->userid = $this->student->id;
        $grade->grade = 8.9;
        $grade->timemodified = time();
        $grade->id = $DB->insert_record('pratest_grades', $grade);

        $result = mod_pratest_external::get_user_best_grade($this->pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_best_grade_returns(), $result);

        // Now I have grades.
        $this->assertTrue($result['hasgrade']);
        $this->assertEquals(8.9, $result['grade']);

        // We should not see other users grades.
        $anotherstudent = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($anotherstudent->id, $this->course->id, $this->studentrole->id, 'manual');

        try {
            mod_pratest_external::get_user_best_grade($this->pratest->id, $anotherstudent->id);
            $this->fail('Exception expected due to missing capability.');
        } catch (required_capability_exception $e) {
            $this->assertEquals('nopermissions', $e->errorcode);
        }

        // Teacher must be able to see student grades.
        $this->setUser($this->teacher);

        $result = mod_pratest_external::get_user_best_grade($this->pratest->id, $this->student->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_user_best_grade_returns(), $result);

        $this->assertTrue($result['hasgrade']);
        $this->assertEquals(8.9, $result['grade']);

        // Invalid user.
        try {
            mod_pratest_external::get_user_best_grade($this->pratest->id, -1);
            $this->fail('Exception expected due to missing capability.');
        } catch (dml_missing_record_exception $e) {
            $this->assertEquals('invaliduser', $e->errorcode);
        }

        // Remove the created data.
        $DB->delete_records('pratest_grades', array('id' => $grade->id));

    }
    /**
     * Test get_combined_review_options.
     * This is a basic test, this is already tested in mod_pratest_display_options_testcase.
     */
    public function test_get_combined_review_options() {
        global $DB;

        // Create a new pratest with attempts.
        $pratestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pratest');
        $data = array('course' => $this->course->id,
                      'sumgrades' => 1);
        $pratest = $pratestgenerator->create_instance($data);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        pratest_add_pratest_question($question->id, $pratest);

        $pratestobj = pratest::create($pratest->id, $this->student->id);

        // Set grade to pass.
        $item = grade_item::fetch(array('courseid' => $this->course->id, 'itemtype' => 'mod',
                                        'itemmodule' => 'pratest', 'iteminstance' => $pratest->id, 'outcomeid' => null));
        $item->gradepass = 80;
        $item->update();

        // Start the passing attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_pratest', $pratestobj->get_context());
        $quba->set_preferred_behaviour($pratestobj->get_pratest()->preferredbehaviour);

        $timenow = time();
        $attempt = pratest_create_attempt($pratestobj, 1, false, $timenow, false, $this->student->id);
        pratest_start_new_attempt($pratestobj, $quba, $attempt, 1, $timenow);
        pratest_attempt_save_started($pratestobj, $quba, $attempt);

        $this->setUser($this->student);

        $result = mod_pratest_external::get_combined_review_options($pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_combined_review_options_returns(), $result);

        // Expected values.
        $expected = array(
            "someoptions" => array(
                array("name" => "feedback", "value" => 1),
                array("name" => "generalfeedback", "value" => 1),
                array("name" => "rightanswer", "value" => 1),
                array("name" => "overallfeedback", "value" => 0),
                array("name" => "marks", "value" => 2),
            ),
            "alloptions" => array(
                array("name" => "feedback", "value" => 1),
                array("name" => "generalfeedback", "value" => 1),
                array("name" => "rightanswer", "value" => 1),
                array("name" => "overallfeedback", "value" => 0),
                array("name" => "marks", "value" => 2),
            ),
            "warnings" => [],
        );

        $this->assertEquals($expected, $result);

        // Now, finish the attempt.
        $attemptobj = pratest_attempt::create($attempt->id);
        $attemptobj->process_finish($timenow, false);

        $expected = array(
            "someoptions" => array(
                array("name" => "feedback", "value" => 1),
                array("name" => "generalfeedback", "value" => 1),
                array("name" => "rightanswer", "value" => 1),
                array("name" => "overallfeedback", "value" => 1),
                array("name" => "marks", "value" => 2),
            ),
            "alloptions" => array(
                array("name" => "feedback", "value" => 1),
                array("name" => "generalfeedback", "value" => 1),
                array("name" => "rightanswer", "value" => 1),
                array("name" => "overallfeedback", "value" => 1),
                array("name" => "marks", "value" => 2),
            ),
            "warnings" => [],
        );

        // We should see now the overall feedback.
        $result = mod_pratest_external::get_combined_review_options($pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_combined_review_options_returns(), $result);
        $this->assertEquals($expected, $result);

        // Start a new attempt, but not finish it.
        $timenow = time();
        $attempt = pratest_create_attempt($pratestobj, 2, false, $timenow, false, $this->student->id);
        $quba = question_engine::make_questions_usage_by_activity('mod_pratest', $pratestobj->get_context());
        $quba->set_preferred_behaviour($pratestobj->get_pratest()->preferredbehaviour);
        pratest_start_new_attempt($pratestobj, $quba, $attempt, 1, $timenow);
        pratest_attempt_save_started($pratestobj, $quba, $attempt);

        $expected = array(
            "someoptions" => array(
                array("name" => "feedback", "value" => 1),
                array("name" => "generalfeedback", "value" => 1),
                array("name" => "rightanswer", "value" => 1),
                array("name" => "overallfeedback", "value" => 1),
                array("name" => "marks", "value" => 2),
            ),
            "alloptions" => array(
                array("name" => "feedback", "value" => 1),
                array("name" => "generalfeedback", "value" => 1),
                array("name" => "rightanswer", "value" => 1),
                array("name" => "overallfeedback", "value" => 0),
                array("name" => "marks", "value" => 2),
            ),
            "warnings" => [],
        );

        $result = mod_pratest_external::get_combined_review_options($pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_combined_review_options_returns(), $result);
        $this->assertEquals($expected, $result);

        // Teacher, for see student options.
        $this->setUser($this->teacher);

        $result = mod_pratest_external::get_combined_review_options($pratest->id, $this->student->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_combined_review_options_returns(), $result);

        $this->assertEquals($expected, $result);

        // Invalid user.
        try {
            mod_pratest_external::get_combined_review_options($pratest->id, -1);
            $this->fail('Exception expected due to missing capability.');
        } catch (dml_missing_record_exception $e) {
            $this->assertEquals('invaliduser', $e->errorcode);
        }
    }

    /**
     * Test start_attempt
     */
    public function test_start_attempt() {
        global $DB;

        // Create a new pratest with questions.
        list($pratest, $context, $pratestobj) = $this->create_pratest_with_questions();

        $this->setUser($this->student);

        // Try to open attempt in closed pratest.
        $pratest->timeopen = time() - WEEKSECS;
        $pratest->timeclose = time() - DAYSECS;
        $DB->update_record('pratest', $pratest);
        $result = mod_pratest_external::start_attempt($pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::start_attempt_returns(), $result);

        $this->assertEquals([], $result['attempt']);
        $this->assertCount(1, $result['warnings']);

        // Now with a password.
        $pratest->timeopen = 0;
        $pratest->timeclose = 0;
        $pratest->password = 'abc';
        $DB->update_record('pratest', $pratest);

        try {
            mod_pratest_external::start_attempt($pratest->id, array(array("name" => "pratestpassword", "value" => 'bad')));
            $this->fail('Exception expected due to invalid passwod.');
        } catch (moodle_exception $e) {
            $this->assertEquals(get_string('passworderror', 'pratestaccess_password'), $e->errorcode);
        }

        // Now, try everything correct.
        $result = mod_pratest_external::start_attempt($pratest->id, array(array("name" => "pratestpassword", "value" => 'abc')));
        $result = external_api::clean_returnvalue(mod_pratest_external::start_attempt_returns(), $result);

        $this->assertEquals(1, $result['attempt']['attempt']);
        $this->assertEquals($this->student->id, $result['attempt']['userid']);
        $this->assertEquals($pratest->id, $result['attempt']['pratest']);
        $this->assertCount(0, $result['warnings']);
        $attemptid = $result['attempt']['id'];

        // We are good, try to start a new attempt now.

        try {
            mod_pratest_external::start_attempt($pratest->id, array(array("name" => "pratestpassword", "value" => 'abc')));
            $this->fail('Exception expected due to attempt not finished.');
        } catch (moodle_pratest_exception $e) {
            $this->assertEquals('attemptstillinprogress', $e->errorcode);
        }

        // Finish the started attempt.

        // Process some responses from the student.
        $timenow = time();
        $attemptobj = pratest_attempt::create($attemptid);
        $tosubmit = array(1 => array('answer' => '3.14'));
        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = pratest_attempt::create($attemptid);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        // We should be able to start a new attempt.
        $result = mod_pratest_external::start_attempt($pratest->id, array(array("name" => "pratestpassword", "value" => 'abc')));
        $result = external_api::clean_returnvalue(mod_pratest_external::start_attempt_returns(), $result);

        $this->assertEquals(2, $result['attempt']['attempt']);
        $this->assertEquals($this->student->id, $result['attempt']['userid']);
        $this->assertEquals($pratest->id, $result['attempt']['pratest']);
        $this->assertCount(0, $result['warnings']);

        // Test user with no capabilities.
        // We need a explicit prohibit since this capability is only defined in authenticated user and guest roles.
        assign_capability('mod/pratest:attempt', CAP_PROHIBIT, $this->studentrole->id, $context->id);
        // Empty all the caches that may be affected  by this change.
        accesslib_clear_all_caches_for_unit_testing();
        course_modinfo::clear_instance_cache();

        try {
            mod_pratest_external::start_attempt($pratest->id);
            $this->fail('Exception expected due to missing capability.');
        } catch (required_capability_exception $e) {
            $this->assertEquals('nopermissions', $e->errorcode);
        }

    }

    /**
     * Test validate_attempt
     */
    public function test_validate_attempt() {
        global $DB;

        // Create a new pratest with one attempt started.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj) = $this->create_pratest_with_questions(true);

        $this->setUser($this->student);

        // Invalid attempt.
        try {
            $params = array('attemptid' => -1, 'page' => 0);
            testable_mod_pratest_external::validate_attempt($params);
            $this->fail('Exception expected due to invalid attempt id.');
        } catch (dml_missing_record_exception $e) {
            $this->assertEquals('invalidrecord', $e->errorcode);
        }

        // Test OK case.
        $params = array('attemptid' => $attempt->id, 'page' => 0);
        $result = testable_mod_pratest_external::validate_attempt($params);
        $this->assertEquals($attempt->id, $result[0]->get_attempt()->id);
        $this->assertEquals([], $result[1]);

        // Test with preflight data.
        $pratest->password = 'abc';
        $DB->update_record('pratest', $pratest);

        try {
            $params = array('attemptid' => $attempt->id, 'page' => 0,
                            'preflightdata' => array(array("name" => "pratestpassword", "value" => 'bad')));
            testable_mod_pratest_external::validate_attempt($params);
            $this->fail('Exception expected due to invalid passwod.');
        } catch (moodle_exception $e) {
            $this->assertEquals(get_string('passworderror', 'pratestaccess_password'), $e->errorcode);
        }

        // Now, try everything correct.
        $params['preflightdata'][0]['value'] = 'abc';
        $result = testable_mod_pratest_external::validate_attempt($params);
        $this->assertEquals($attempt->id, $result[0]->get_attempt()->id);
        $this->assertEquals([], $result[1]);

        // Page out of range.
        $DB->update_record('pratest', $pratest);
        $params['page'] = 4;
        try {
            testable_mod_pratest_external::validate_attempt($params);
            $this->fail('Exception expected due to page out of range.');
        } catch (moodle_pratest_exception $e) {
            $this->assertEquals('Invalid page number', $e->errorcode);
        }

        $params['page'] = 0;
        // Try to open attempt in closed pratest.
        $pratest->timeopen = time() - WEEKSECS;
        $pratest->timeclose = time() - DAYSECS;
        $DB->update_record('pratest', $pratest);

        // This should work, ommit access rules.
        testable_mod_pratest_external::validate_attempt($params, false);

        // Get a generic error because prior to checking the dates the attempt is closed.
        try {
            testable_mod_pratest_external::validate_attempt($params);
            $this->fail('Exception expected due to passed dates.');
        } catch (moodle_pratest_exception $e) {
            $this->assertEquals('attempterror', $e->errorcode);
        }

        // Finish the attempt.
        $attemptobj = pratest_attempt::create($attempt->id);
        $attemptobj->process_finish(time(), false);

        try {
            testable_mod_pratest_external::validate_attempt($params, false);
            $this->fail('Exception expected due to attempt finished.');
        } catch (moodle_pratest_exception $e) {
            $this->assertEquals('attemptalreadyclosed', $e->errorcode);
        }

        // Test user with no capabilities.
        // We need a explicit prohibit since this capability is only defined in authenticated user and guest roles.
        assign_capability('mod/pratest:attempt', CAP_PROHIBIT, $this->studentrole->id, $context->id);
        // Empty all the caches that may be affected  by this change.
        accesslib_clear_all_caches_for_unit_testing();
        course_modinfo::clear_instance_cache();

        try {
            testable_mod_pratest_external::validate_attempt($params);
            $this->fail('Exception expected due to missing permissions.');
        } catch (required_capability_exception $e) {
            $this->assertEquals('nopermissions', $e->errorcode);
        }

        // Now try with a different user.
        $this->setUser($this->teacher);

        $params['page'] = 0;
        try {
            testable_mod_pratest_external::validate_attempt($params);
            $this->fail('Exception expected due to not your attempt.');
        } catch (moodle_pratest_exception $e) {
            $this->assertEquals('notyourattempt', $e->errorcode);
        }
    }

    /**
     * Test get_attempt_data
     */
    public function test_get_attempt_data() {
        global $DB;

        $timenow = time();
        // Create a new pratest with one attempt started.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj) = $this->create_pratest_with_questions(true);

        // Set correctness mask so questions state can be fetched only after finishing the attempt.
        $DB->set_field('pratest', 'reviewcorrectness', mod_pratest_display_options::IMMEDIATELY_AFTER, array('id' => $pratest->id));

        $pratestobj = $attemptobj->get_pratestobj();
        $pratestobj->preload_questions();
        $pratestobj->load_questions();
        $questions = $pratestobj->get_questions();

        $this->setUser($this->student);

        // We receive one question per page.
        $result = mod_pratest_external::get_attempt_data($attempt->id, 0);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_data_returns(), $result);

        $this->assertEquals($attempt, (object) $result['attempt']);
        $this->assertEquals(1, $result['nextpage']);
        $this->assertCount(0, $result['messages']);
        $this->assertCount(1, $result['questions']);
        $this->assertEquals(1, $result['questions'][0]['slot']);
        $this->assertEquals(1, $result['questions'][0]['number']);
        $this->assertEquals('numerical', $result['questions'][0]['type']);
        $this->assertArrayNotHasKey('state', $result['questions'][0]);  // We don't receive the state yet.
        $this->assertEquals(get_string('notyetanswered', 'question'), $result['questions'][0]['status']);
        $this->assertFalse($result['questions'][0]['flagged']);
        $this->assertEquals(0, $result['questions'][0]['page']);
        $this->assertEmpty($result['questions'][0]['mark']);
        $this->assertEquals(1, $result['questions'][0]['maxmark']);
        $this->assertEquals(1, $result['questions'][0]['sequencecheck']);
        $this->assertGreaterThanOrEqual($timenow, $result['questions'][0]['lastactiontime']);
        $this->assertEquals(false, $result['questions'][0]['hasautosavedstep']);

        // Now try the last page.
        $result = mod_pratest_external::get_attempt_data($attempt->id, 1);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_data_returns(), $result);

        $this->assertEquals($attempt, (object) $result['attempt']);
        $this->assertEquals(-1, $result['nextpage']);
        $this->assertCount(0, $result['messages']);
        $this->assertCount(1, $result['questions']);
        $this->assertEquals(2, $result['questions'][0]['slot']);
        $this->assertEquals(2, $result['questions'][0]['number']);
        $this->assertEquals('numerical', $result['questions'][0]['type']);
        $this->assertArrayNotHasKey('state', $result['questions'][0]);  // We don't receive the state yet.
        $this->assertEquals(get_string('notyetanswered', 'question'), $result['questions'][0]['status']);
        $this->assertFalse($result['questions'][0]['flagged']);
        $this->assertEquals(1, $result['questions'][0]['page']);
        $this->assertEquals(1, $result['questions'][0]['sequencecheck']);
        $this->assertGreaterThanOrEqual($timenow, $result['questions'][0]['lastactiontime']);
        $this->assertEquals(false, $result['questions'][0]['hasautosavedstep']);

        // Finish previous attempt.
        $attemptobj->process_finish(time(), false);

        // Now we should receive the question state.
        $result = mod_pratest_external::get_attempt_review($attempt->id, 1);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_review_returns(), $result);
        $this->assertEquals('gaveup', $result['questions'][0]['state']);

        // Change setting and expect two pages.
        $pratest->questionsperpage = 4;
        $DB->update_record('pratest', $pratest);
        pratest_repaginate_questions($pratest->id, $pratest->questionsperpage);

        // Start with new attempt with the new layout.
        $quba = question_engine::make_questions_usage_by_activity('mod_pratest', $pratestobj->get_context());
        $quba->set_preferred_behaviour($pratestobj->get_pratest()->preferredbehaviour);

        $timenow = time();
        $attempt = pratest_create_attempt($pratestobj, 2, false, $timenow, false, $this->student->id);
        pratest_start_new_attempt($pratestobj, $quba, $attempt, 1, $timenow);
        pratest_attempt_save_started($pratestobj, $quba, $attempt);

        // We receive two questions per page.
        $result = mod_pratest_external::get_attempt_data($attempt->id, 0);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_data_returns(), $result);
        $this->assertCount(2, $result['questions']);
        $this->assertEquals(-1, $result['nextpage']);

        // Check questions looks good.
        $found = 0;
        foreach ($questions as $question) {
            foreach ($result['questions'] as $rquestion) {
                if ($rquestion['slot'] == $question->slot) {
                    $this->assertTrue(strpos($rquestion['html'], "qid=$question->id") !== false);
                    $found++;
                }
            }
        }
        $this->assertEquals(2, $found);

    }

    /**
     * Test get_attempt_data with blocked questions.
     * @since 3.2
     */
    public function test_get_attempt_data_with_blocked_questions() {
        global $DB;

        // Create a new pratest with one attempt started and using immediatefeedback.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj) = $this->create_pratest_with_questions(
                true, false, 'immediatefeedback');

        $pratestobj = $attemptobj->get_pratestobj();

        // Make second question blocked by the first one.
        $structure = $pratestobj->get_structure();
        $slots = $structure->get_slots();
        $structure->update_question_dependency(end($slots)->id, true);

        $pratestobj->preload_questions();
        $pratestobj->load_questions();
        $questions = $pratestobj->get_questions();

        $this->setUser($this->student);

        // We receive one question per page.
        $result = mod_pratest_external::get_attempt_data($attempt->id, 0);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_data_returns(), $result);

        $this->assertEquals($attempt, (object) $result['attempt']);
        $this->assertCount(1, $result['questions']);
        $this->assertEquals(1, $result['questions'][0]['slot']);
        $this->assertEquals(1, $result['questions'][0]['number']);
        $this->assertEquals(false, $result['questions'][0]['blockedbyprevious']);

        // Now try the last page.
        $result = mod_pratest_external::get_attempt_data($attempt->id, 1);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_data_returns(), $result);

        $this->assertEquals($attempt, (object) $result['attempt']);
        $this->assertCount(1, $result['questions']);
        $this->assertEquals(2, $result['questions'][0]['slot']);
        $this->assertEquals(2, $result['questions'][0]['number']);
        $this->assertEquals(true, $result['questions'][0]['blockedbyprevious']);
    }

    /**
     * Test get_attempt_summary
     */
    public function test_get_attempt_summary() {

        $timenow = time();
        // Create a new pratest with one attempt started.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj) = $this->create_pratest_with_questions(true);

        $this->setUser($this->student);
        $result = mod_pratest_external::get_attempt_summary($attempt->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_summary_returns(), $result);

        // Check the state, flagged and mark data is correct.
        $this->assertEquals('todo', $result['questions'][0]['state']);
        $this->assertEquals('todo', $result['questions'][1]['state']);
        $this->assertEquals(1, $result['questions'][0]['number']);
        $this->assertEquals(2, $result['questions'][1]['number']);
        $this->assertFalse($result['questions'][0]['flagged']);
        $this->assertFalse($result['questions'][1]['flagged']);
        $this->assertEmpty($result['questions'][0]['mark']);
        $this->assertEmpty($result['questions'][1]['mark']);
        $this->assertEquals(1, $result['questions'][0]['sequencecheck']);
        $this->assertEquals(1, $result['questions'][1]['sequencecheck']);
        $this->assertGreaterThanOrEqual($timenow, $result['questions'][0]['lastactiontime']);
        $this->assertGreaterThanOrEqual($timenow, $result['questions'][1]['lastactiontime']);
        $this->assertEquals(false, $result['questions'][0]['hasautosavedstep']);
        $this->assertEquals(false, $result['questions'][1]['hasautosavedstep']);

        // Submit a response for the first question.
        $tosubmit = array(1 => array('answer' => '3.14'));
        $attemptobj->process_submitted_actions(time(), false, $tosubmit);
        $result = mod_pratest_external::get_attempt_summary($attempt->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_summary_returns(), $result);

        // Check it's marked as completed only the first one.
        $this->assertEquals('complete', $result['questions'][0]['state']);
        $this->assertEquals('todo', $result['questions'][1]['state']);
        $this->assertEquals(1, $result['questions'][0]['number']);
        $this->assertEquals(2, $result['questions'][1]['number']);
        $this->assertFalse($result['questions'][0]['flagged']);
        $this->assertFalse($result['questions'][1]['flagged']);
        $this->assertEmpty($result['questions'][0]['mark']);
        $this->assertEmpty($result['questions'][1]['mark']);
        $this->assertEquals(2, $result['questions'][0]['sequencecheck']);
        $this->assertEquals(1, $result['questions'][1]['sequencecheck']);
        $this->assertGreaterThanOrEqual($timenow, $result['questions'][0]['lastactiontime']);
        $this->assertGreaterThanOrEqual($timenow, $result['questions'][1]['lastactiontime']);
        $this->assertEquals(false, $result['questions'][0]['hasautosavedstep']);
        $this->assertEquals(false, $result['questions'][1]['hasautosavedstep']);

    }

    /**
     * Test save_attempt
     */
    public function test_save_attempt() {

        $timenow = time();
        // Create a new pratest with one attempt started.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj, $quba) = $this->create_pratest_with_questions(true);

        // Response for slot 1.
        $prefix = $quba->get_field_prefix(1);
        $data = array(
            array('name' => 'slots', 'value' => 1),
            array('name' => $prefix . ':sequencecheck',
                    'value' => $attemptobj->get_question_attempt(1)->get_sequence_check_count()),
            array('name' => $prefix . 'answer', 'value' => 1),
        );

        $this->setUser($this->student);

        $result = mod_pratest_external::save_attempt($attempt->id, $data);
        $result = external_api::clean_returnvalue(mod_pratest_external::save_attempt_returns(), $result);
        $this->assertTrue($result['status']);

        // Now, get the summary.
        $result = mod_pratest_external::get_attempt_summary($attempt->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_summary_returns(), $result);

        // Check it's marked as completed only the first one.
        $this->assertEquals('complete', $result['questions'][0]['state']);
        $this->assertEquals('todo', $result['questions'][1]['state']);
        $this->assertEquals(1, $result['questions'][0]['number']);
        $this->assertEquals(2, $result['questions'][1]['number']);
        $this->assertFalse($result['questions'][0]['flagged']);
        $this->assertFalse($result['questions'][1]['flagged']);
        $this->assertEmpty($result['questions'][0]['mark']);
        $this->assertEmpty($result['questions'][1]['mark']);
        $this->assertEquals(1, $result['questions'][0]['sequencecheck']);
        $this->assertEquals(1, $result['questions'][1]['sequencecheck']);
        $this->assertGreaterThanOrEqual($timenow, $result['questions'][0]['lastactiontime']);
        $this->assertGreaterThanOrEqual($timenow, $result['questions'][1]['lastactiontime']);
        $this->assertEquals(true, $result['questions'][0]['hasautosavedstep']);
        $this->assertEquals(false, $result['questions'][1]['hasautosavedstep']);

        // Now, second slot.
        $prefix = $quba->get_field_prefix(2);
        $data = array(
            array('name' => 'slots', 'value' => 2),
            array('name' => $prefix . ':sequencecheck',
                    'value' => $attemptobj->get_question_attempt(1)->get_sequence_check_count()),
            array('name' => $prefix . 'answer', 'value' => 1),
        );

        $result = mod_pratest_external::save_attempt($attempt->id, $data);
        $result = external_api::clean_returnvalue(mod_pratest_external::save_attempt_returns(), $result);
        $this->assertTrue($result['status']);

        // Now, get the summary.
        $result = mod_pratest_external::get_attempt_summary($attempt->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_summary_returns(), $result);

        // Check it's marked as completed only the first one.
        $this->assertEquals('complete', $result['questions'][0]['state']);
        $this->assertEquals(1, $result['questions'][0]['sequencecheck']);
        $this->assertEquals('complete', $result['questions'][1]['state']);
        $this->assertEquals(1, $result['questions'][1]['sequencecheck']);

    }

    /**
     * Test process_attempt
     */
    public function test_process_attempt() {
        global $DB;

        $timenow = time();
        // Create a new pratest with two questions and one attempt started.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj, $quba) = $this->create_pratest_with_questions(true);

        // Response for slot 1.
        $prefix = $quba->get_field_prefix(1);
        $data = array(
            array('name' => 'slots', 'value' => 1),
            array('name' => $prefix . ':sequencecheck',
                    'value' => $attemptobj->get_question_attempt(1)->get_sequence_check_count()),
            array('name' => $prefix . 'answer', 'value' => 1),
        );

        $this->setUser($this->student);

        $result = mod_pratest_external::process_attempt($attempt->id, $data);
        $result = external_api::clean_returnvalue(mod_pratest_external::process_attempt_returns(), $result);
        $this->assertEquals(pratest_attempt::IN_PROGRESS, $result['state']);

        // Now, get the summary.
        $result = mod_pratest_external::get_attempt_summary($attempt->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_summary_returns(), $result);

        // Check it's marked as completed only the first one.
        $this->assertEquals('complete', $result['questions'][0]['state']);
        $this->assertEquals('todo', $result['questions'][1]['state']);
        $this->assertEquals(1, $result['questions'][0]['number']);
        $this->assertEquals(2, $result['questions'][1]['number']);
        $this->assertFalse($result['questions'][0]['flagged']);
        $this->assertFalse($result['questions'][1]['flagged']);
        $this->assertEmpty($result['questions'][0]['mark']);
        $this->assertEmpty($result['questions'][1]['mark']);
        $this->assertEquals(2, $result['questions'][0]['sequencecheck']);
        $this->assertEquals(2, $result['questions'][0]['sequencecheck']);
        $this->assertGreaterThanOrEqual($timenow, $result['questions'][0]['lastactiontime']);
        $this->assertGreaterThanOrEqual($timenow, $result['questions'][0]['lastactiontime']);
        $this->assertEquals(false, $result['questions'][0]['hasautosavedstep']);
        $this->assertEquals(false, $result['questions'][0]['hasautosavedstep']);

        // Now, second slot.
        $prefix = $quba->get_field_prefix(2);
        $data = array(
            array('name' => 'slots', 'value' => 2),
            array('name' => $prefix . ':sequencecheck',
                    'value' => $attemptobj->get_question_attempt(1)->get_sequence_check_count()),
            array('name' => $prefix . 'answer', 'value' => 1),
            array('name' => $prefix . ':flagged', 'value' => 1),
        );

        $result = mod_pratest_external::process_attempt($attempt->id, $data);
        $result = external_api::clean_returnvalue(mod_pratest_external::process_attempt_returns(), $result);
        $this->assertEquals(pratest_attempt::IN_PROGRESS, $result['state']);

        // Now, get the summary.
        $result = mod_pratest_external::get_attempt_summary($attempt->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_summary_returns(), $result);

        // Check it's marked as completed only the first one.
        $this->assertEquals('complete', $result['questions'][0]['state']);
        $this->assertEquals('complete', $result['questions'][1]['state']);
        $this->assertFalse($result['questions'][0]['flagged']);
        $this->assertTrue($result['questions'][1]['flagged']);

        // Finish the attempt.
        $result = mod_pratest_external::process_attempt($attempt->id, array(), true);
        $result = external_api::clean_returnvalue(mod_pratest_external::process_attempt_returns(), $result);
        $this->assertEquals(pratest_attempt::FINISHED, $result['state']);

        // Start new attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_pratest', $pratestobj->get_context());
        $quba->set_preferred_behaviour($pratestobj->get_pratest()->preferredbehaviour);

        $timenow = time();
        $attempt = pratest_create_attempt($pratestobj, 2, false, $timenow, false, $this->student->id);
        pratest_start_new_attempt($pratestobj, $quba, $attempt, 2, $timenow);
        pratest_attempt_save_started($pratestobj, $quba, $attempt);

        // Force grace period, attempt going to overdue.
        $pratest->timeclose = $timenow - 10;
        $pratest->graceperiod = 60;
        $pratest->overduehandling = 'graceperiod';
        $DB->update_record('pratest', $pratest);

        $result = mod_pratest_external::process_attempt($attempt->id, array());
        $result = external_api::clean_returnvalue(mod_pratest_external::process_attempt_returns(), $result);
        $this->assertEquals(pratest_attempt::OVERDUE, $result['state']);

        // New attempt.
        $timenow = time();
        $quba = question_engine::make_questions_usage_by_activity('mod_pratest', $pratestobj->get_context());
        $quba->set_preferred_behaviour($pratestobj->get_pratest()->preferredbehaviour);
        $attempt = pratest_create_attempt($pratestobj, 3, 2, $timenow, false, $this->student->id);
        pratest_start_new_attempt($pratestobj, $quba, $attempt, 3, $timenow);
        pratest_attempt_save_started($pratestobj, $quba, $attempt);

        // Force abandon.
        $pratest->timeclose = $timenow - HOURSECS;
        $DB->update_record('pratest', $pratest);

        $result = mod_pratest_external::process_attempt($attempt->id, array());
        $result = external_api::clean_returnvalue(mod_pratest_external::process_attempt_returns(), $result);
        $this->assertEquals(pratest_attempt::ABANDONED, $result['state']);

    }

    /**
     * Test validate_attempt_review
     */
    public function test_validate_attempt_review() {
        global $DB;

        // Create a new pratest with one attempt started.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj) = $this->create_pratest_with_questions(true);

        $this->setUser($this->student);

        // Invalid attempt, invalid id.
        try {
            $params = array('attemptid' => -1);
            testable_mod_pratest_external::validate_attempt_review($params);
            $this->fail('Exception expected due invalid id.');
        } catch (dml_missing_record_exception $e) {
            $this->assertEquals('invalidrecord', $e->errorcode);
        }

        // Invalid attempt, not closed.
        try {
            $params = array('attemptid' => $attempt->id);
            testable_mod_pratest_external::validate_attempt_review($params);
            $this->fail('Exception expected due not closed attempt.');
        } catch (moodle_pratest_exception $e) {
            $this->assertEquals('attemptclosed', $e->errorcode);
        }

        // Test ok case (finished attempt).
        list($pratest, $context, $pratestobj, $attempt, $attemptobj) = $this->create_pratest_with_questions(true, true);

        $params = array('attemptid' => $attempt->id);
        testable_mod_pratest_external::validate_attempt_review($params);

        // Teacher should be able to view the review of one student's attempt.
        $this->setUser($this->teacher);
        testable_mod_pratest_external::validate_attempt_review($params);

        // We should not see other students attempts.
        $anotherstudent = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($anotherstudent->id, $this->course->id, $this->studentrole->id, 'manual');

        $this->setUser($anotherstudent);
        try {
            $params = array('attemptid' => $attempt->id);
            testable_mod_pratest_external::validate_attempt_review($params);
            $this->fail('Exception expected due missing permissions.');
        } catch (moodle_pratest_exception $e) {
            $this->assertEquals('noreviewattempt', $e->errorcode);
        }
    }


    /**
     * Test get_attempt_review
     */
    public function test_get_attempt_review() {
        global $DB;

        // Create a new pratest with two questions and one attempt finished.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj, $quba) = $this->create_pratest_with_questions(true, true);

        // Add feedback to the pratest.
        $feedback = new stdClass();
        $feedback->pratestid = $pratest->id;
        $feedback->feedbacktext = 'Feedback text 1';
        $feedback->feedbacktextformat = 1;
        $feedback->mingrade = 49;
        $feedback->maxgrade = 100;
        $feedback->id = $DB->insert_record('pratest_feedback', $feedback);

        $feedback->feedbacktext = 'Feedback text 2';
        $feedback->feedbacktextformat = 1;
        $feedback->mingrade = 30;
        $feedback->maxgrade = 48;
        $feedback->id = $DB->insert_record('pratest_feedback', $feedback);

        $result = mod_pratest_external::get_attempt_review($attempt->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_review_returns(), $result);

        // Two questions, one completed and correct, the other gave up.
        $this->assertEquals(50, $result['grade']);
        $this->assertEquals(1, $result['attempt']['attempt']);
        $this->assertEquals('finished', $result['attempt']['state']);
        $this->assertEquals(1, $result['attempt']['sumgrades']);
        $this->assertCount(2, $result['questions']);
        $this->assertEquals('gradedright', $result['questions'][0]['state']);
        $this->assertEquals(1, $result['questions'][0]['slot']);
        $this->assertEquals('gaveup', $result['questions'][1]['state']);
        $this->assertEquals(2, $result['questions'][1]['slot']);

        $this->assertCount(1, $result['additionaldata']);
        $this->assertEquals('feedback', $result['additionaldata'][0]['id']);
        $this->assertEquals('Feedback', $result['additionaldata'][0]['title']);
        $this->assertEquals('Feedback text 1', $result['additionaldata'][0]['content']);

        // Only first page.
        $result = mod_pratest_external::get_attempt_review($attempt->id, 0);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_review_returns(), $result);

        $this->assertEquals(50, $result['grade']);
        $this->assertEquals(1, $result['attempt']['attempt']);
        $this->assertEquals('finished', $result['attempt']['state']);
        $this->assertEquals(1, $result['attempt']['sumgrades']);
        $this->assertCount(1, $result['questions']);
        $this->assertEquals('gradedright', $result['questions'][0]['state']);
        $this->assertEquals(1, $result['questions'][0]['slot']);

         $this->assertCount(1, $result['additionaldata']);
        $this->assertEquals('feedback', $result['additionaldata'][0]['id']);
        $this->assertEquals('Feedback', $result['additionaldata'][0]['title']);
        $this->assertEquals('Feedback text 1', $result['additionaldata'][0]['content']);

    }

    /**
     * Test test_view_attempt
     */
    public function test_view_attempt() {
        global $DB;

        // Create a new pratest with two questions and one attempt started.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj, $quba) = $this->create_pratest_with_questions(true, false);

        // Test user with full capabilities.
        $this->setUser($this->student);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $result = mod_pratest_external::view_attempt($attempt->id, 0);
        $result = external_api::clean_returnvalue(mod_pratest_external::view_attempt_returns(), $result);
        $this->assertTrue($result['status']);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pratest\event\attempt_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

        // Now, force the pratest with QUIZ_NAVMETHOD_SEQ (sequencial) navigation method.
        $DB->set_field('pratest', 'navmethod', QUIZ_NAVMETHOD_SEQ, array('id' => $pratest->id));
        // Quiz requiring preflightdata.
        $DB->set_field('pratest', 'password', 'abcdef', array('id' => $pratest->id));
        $preflightdata = array(array("name" => "pratestpassword", "value" => 'abcdef'));

        // See next page.
        $result = mod_pratest_external::view_attempt($attempt->id, 1, $preflightdata);
        $result = external_api::clean_returnvalue(mod_pratest_external::view_attempt_returns(), $result);
        $this->assertTrue($result['status']);

        $events = $sink->get_events();
        $this->assertCount(2, $events);

        // Try to go to previous page.
        try {
            mod_pratest_external::view_attempt($attempt->id, 0);
            $this->fail('Exception expected due to try to see a previous page.');
        } catch (moodle_pratest_exception $e) {
            $this->assertEquals('Out of sequence access', $e->errorcode);
        }

    }

    /**
     * Test test_view_attempt_summary
     */
    public function test_view_attempt_summary() {
        global $DB;

        // Create a new pratest with two questions and one attempt started.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj, $quba) = $this->create_pratest_with_questions(true, false);

        // Test user with full capabilities.
        $this->setUser($this->student);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $result = mod_pratest_external::view_attempt_summary($attempt->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::view_attempt_summary_returns(), $result);
        $this->assertTrue($result['status']);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pratest\event\attempt_summary_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $moodlepratest = new \moodle_url('/mod/pratest/summary.php', array('attempt' => $attempt->id));
        $this->assertEquals($moodlepratest, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

        // Quiz requiring preflightdata.
        $DB->set_field('pratest', 'password', 'abcdef', array('id' => $pratest->id));
        $preflightdata = array(array("name" => "pratestpassword", "value" => 'abcdef'));

        $result = mod_pratest_external::view_attempt_summary($attempt->id, $preflightdata);
        $result = external_api::clean_returnvalue(mod_pratest_external::view_attempt_summary_returns(), $result);
        $this->assertTrue($result['status']);

    }

    /**
     * Test test_view_attempt_summary
     */
    public function test_view_attempt_review() {
        global $DB;

        // Create a new pratest with two questions and one attempt finished.
        list($pratest, $context, $pratestobj, $attempt, $attemptobj, $quba) = $this->create_pratest_with_questions(true, true);

        // Test user with full capabilities.
        $this->setUser($this->student);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $result = mod_pratest_external::view_attempt_review($attempt->id, 0);
        $result = external_api::clean_returnvalue(mod_pratest_external::view_attempt_review_returns(), $result);
        $this->assertTrue($result['status']);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pratest\event\attempt_reviewed', $event);
        $this->assertEquals($context, $event->get_context());
        $moodlepratest = new \moodle_url('/mod/pratest/review.php', array('attempt' => $attempt->id));
        $this->assertEquals($moodlepratest, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

    }

    /**
     * Test get_pratest_feedback_for_grade
     */
    public function test_get_pratest_feedback_for_grade() {
        global $DB;

        // Add feedback to the pratest.
        $feedback = new stdClass();
        $feedback->pratestid = $this->pratest->id;
        $feedback->feedbacktext = 'Feedback text 1';
        $feedback->feedbacktextformat = 1;
        $feedback->mingrade = 49;
        $feedback->maxgrade = 100;
        $feedback->id = $DB->insert_record('pratest_feedback', $feedback);
        // Add a fake inline image to the feedback text.
        $filename = 'shouldbeanimage.jpg';
        $filerecordinline = array(
            'contextid' => $this->context->id,
            'component' => 'mod_pratest',
            'filearea'  => 'feedback',
            'itemid'    => $feedback->id,
            'filepath'  => '/',
            'filename'  => $filename,
        );
        $fs = get_file_storage();
        $fs->create_file_from_string($filerecordinline, 'image contents (not really)');

        $feedback->feedbacktext = 'Feedback text 2';
        $feedback->feedbacktextformat = 1;
        $feedback->mingrade = 30;
        $feedback->maxgrade = 49;
        $feedback->id = $DB->insert_record('pratest_feedback', $feedback);

        $result = mod_pratest_external::get_pratest_feedback_for_grade($this->pratest->id, 50);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_pratest_feedback_for_grade_returns(), $result);
        $this->assertEquals('Feedback text 1', $result['feedbacktext']);
        $this->assertEquals($filename, $result['feedbackinlinefiles'][0]['filename']);
        $this->assertEquals(FORMAT_HTML, $result['feedbacktextformat']);

        $result = mod_pratest_external::get_pratest_feedback_for_grade($this->pratest->id, 30);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_pratest_feedback_for_grade_returns(), $result);
        $this->assertEquals('Feedback text 2', $result['feedbacktext']);
        $this->assertEquals(FORMAT_HTML, $result['feedbacktextformat']);

        $result = mod_pratest_external::get_pratest_feedback_for_grade($this->pratest->id, 10);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_pratest_feedback_for_grade_returns(), $result);
        $this->assertEquals('', $result['feedbacktext']);
        $this->assertEquals(FORMAT_MOODLE, $result['feedbacktextformat']);
    }

    /**
     * Test get_pratest_access_information
     */
    public function test_get_pratest_access_information() {
        global $DB;

        // Create a new pratest.
        $pratestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pratest');
        $data = array('course' => $this->course->id);
        $pratest = $pratestgenerator->create_instance($data);

        $this->setUser($this->student);

        // Default restrictions (none).
        $result = mod_pratest_external::get_pratest_access_information($pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_pratest_access_information_returns(), $result);

        $expected = array(
            'canattempt' => true,
            'canmanage' => false,
            'canpreview' => false,
            'canreviewmyattempts' => true,
            'canviewreports' => false,
            'accessrules' => [],
            // This rule is always used, even if the pratest has no open or close date.
            'activerulenames' => ['pratestaccess_openclosedate'],
            'preventaccessreasons' => [],
            'warnings' => []
        );

        $this->assertEquals($expected, $result);

        // Now teacher, different privileges.
        $this->setUser($this->teacher);
        $result = mod_pratest_external::get_pratest_access_information($pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_pratest_access_information_returns(), $result);

        $expected['canmanage'] = true;
        $expected['canpreview'] = true;
        $expected['canviewreports'] = true;
        $expected['canattempt'] = false;
        $expected['canreviewmyattempts'] = false;

        $this->assertEquals($expected, $result);

        $this->setUser($this->student);
        // Now add some restrictions.
        $pratest->timeopen = time() + DAYSECS;
        $pratest->timeclose = time() + WEEKSECS;
        $pratest->password = '123456';
        $DB->update_record('pratest', $pratest);

        $result = mod_pratest_external::get_pratest_access_information($pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_pratest_access_information_returns(), $result);

        // Access limited by time and password.
        $this->assertCount(3, $result['accessrules']);
        // Two rule names, password and open/close date.
        $this->assertCount(2, $result['activerulenames']);
        $this->assertCount(1, $result['preventaccessreasons']);

    }

    /**
     * Test get_attempt_access_information
     */
    public function test_get_attempt_access_information() {
        global $DB;

        $this->setAdminUser();

        // Create a new pratest with attempts.
        $pratestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pratest');
        $data = array('course' => $this->course->id,
                      'sumgrades' => 2);
        $pratest = $pratestgenerator->create_instance($data);

        // Create some questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        pratest_add_pratest_question($question->id, $pratest);

        $question = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        pratest_add_pratest_question($question->id, $pratest);

        // Add new question types in the category (for the random one).
        $question = $questiongenerator->create_question('truefalse', null, array('category' => $cat->id));
        $question = $questiongenerator->create_question('essay', null, array('category' => $cat->id));

        pratest_add_random_questions($pratest, 0, $cat->id, 1, false);

        $pratestobj = pratest::create($pratest->id, $this->student->id);

        // Set grade to pass.
        $item = grade_item::fetch(array('courseid' => $this->course->id, 'itemtype' => 'mod',
                                        'itemmodule' => 'pratest', 'iteminstance' => $pratest->id, 'outcomeid' => null));
        $item->gradepass = 80;
        $item->update();

        $this->setUser($this->student);

        // Default restrictions (none).
        $result = mod_pratest_external::get_attempt_access_information($pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_access_information_returns(), $result);

        $expected = array(
            'isfinished' => false,
            'preventnewattemptreasons' => [],
            'warnings' => []
        );

        $this->assertEquals($expected, $result);

        // Limited attempts.
        $pratest->attempts = 1;
        $DB->update_record('pratest', $pratest);

        // Now, do one attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_pratest', $pratestobj->get_context());
        $quba->set_preferred_behaviour($pratestobj->get_pratest()->preferredbehaviour);

        $timenow = time();
        $attempt = pratest_create_attempt($pratestobj, 1, false, $timenow, false, $this->student->id);
        pratest_start_new_attempt($pratestobj, $quba, $attempt, 1, $timenow);
        pratest_attempt_save_started($pratestobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = pratest_attempt::create($attempt->id);
        $tosubmit = array(1 => array('answer' => '3.14'));
        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = pratest_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        // Can we start a new attempt? We shall not!
        $result = mod_pratest_external::get_attempt_access_information($pratest->id, $attempt->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_attempt_access_information_returns(), $result);

        // Now new attemps allowed.
        $this->assertCount(1, $result['preventnewattemptreasons']);
        $this->assertFalse($result['ispreflightcheckrequired']);
        $this->assertEquals(get_string('nomoreattempts', 'pratest'), $result['preventnewattemptreasons'][0]);

    }

    /**
     * Test get_pratest_required_qtypes
     */
    public function test_get_pratest_required_qtypes() {
        $this->setAdminUser();

        // Create a new pratest.
        $pratestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pratest');
        $data = array('course' => $this->course->id);
        $pratest = $pratestgenerator->create_instance($data);

        // Create some questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        pratest_add_pratest_question($question->id, $pratest);

        $question = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        pratest_add_pratest_question($question->id, $pratest);

        // Add new question types in the category (for the random one).
        $question = $questiongenerator->create_question('truefalse', null, array('category' => $cat->id));
        $question = $questiongenerator->create_question('essay', null, array('category' => $cat->id));

        pratest_add_random_questions($pratest, 0, $cat->id, 1, false);

        $this->setUser($this->student);

        $result = mod_pratest_external::get_pratest_required_qtypes($pratest->id);
        $result = external_api::clean_returnvalue(mod_pratest_external::get_pratest_required_qtypes_returns(), $result);

        $expected = array(
            'questiontypes' => ['essay', 'numerical', 'random', 'shortanswer', 'truefalse'],
            'warnings' => []
        );

        $this->assertEquals($expected, $result);

    }
}
