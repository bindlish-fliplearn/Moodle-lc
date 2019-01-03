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
 * Tests for the flipquiz overview report.
 *
 * @package   flipquiz_overview
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');
require_once($CFG->dirroot . '/mod/flipquiz/report/reportlib.php');
require_once($CFG->dirroot . '/mod/flipquiz/report/default.php');
require_once($CFG->dirroot . '/mod/flipquiz/report/overview/report.php');


/**
 * Tests for the flipquiz overview report.
 *
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flipquiz_overview_report_testcase extends advanced_testcase {

    /**
     * Data provider for test_report_sql.
     *
     * @return array the data for the test sub-cases.
     */
    public function report_sql_cases() {
        return [[null], ['csv']]; // Only need to test on or off, not all download types.
    }

    /**
     * Test how the report queries the database.
     *
     * @param bool $isdownloading a download type, or null.
     * @dataProvider report_sql_cases
     */
    public function test_report_sql($isdownloading) {
        global $DB;
        $this->resetAfterTest(true);

        // Create a course and a flipquiz.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $flipquizgenerator = $generator->get_plugin_generator('mod_flipquiz');
        $flipquiz = $flipquizgenerator->create_instance(array('course' => $course->id,
                'grademethod' => FLIPQUIZ_GRADEHIGHEST, 'grade' => 100.0, 'sumgrades' => 10.0,
                'attempts' => 10));

        // Add one question.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $q = $questiongenerator->create_question('essay', 'plain', ['category' => $cat->id]);
        flipquiz_add_flipquiz_question($q->id, $flipquiz, 0 , 10);

        // Create some students and enrol them in the course.
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $student3 = $generator->create_user();
        $generator->enrol_user($student1->id, $course->id);
        $generator->enrol_user($student2->id, $course->id);
        $generator->enrol_user($student3->id, $course->id);
        // This line is not really necessary for the test asserts below,
        // but what it does is add an extra user row returned by
        // get_enrolled_with_capabilities_join because of a second enrolment.
        // The extra row returned used to make $table->query_db complain
        // about duplicate records. So this is really a test that an extra
        // student enrolment does not cause duplicate records in this query.
        $generator->enrol_user($student2->id, $course->id, null, 'self');

        // The test data.
        $timestamp = 1234567890;
        $attempts = array(
            array($flipquiz, $student1, 1, 0.0,  flipquiz_attempt::FINISHED),
            array($flipquiz, $student1, 2, 5.0,  flipquiz_attempt::FINISHED),
            array($flipquiz, $student1, 3, 8.0,  flipquiz_attempt::FINISHED),
            array($flipquiz, $student1, 4, null, flipquiz_attempt::ABANDONED),
            array($flipquiz, $student1, 5, null, flipquiz_attempt::IN_PROGRESS),
            array($flipquiz, $student2, 1, null, flipquiz_attempt::ABANDONED),
            array($flipquiz, $student2, 2, null, flipquiz_attempt::ABANDONED),
            array($flipquiz, $student2, 3, 7.0,  flipquiz_attempt::FINISHED),
            array($flipquiz, $student2, 4, null, flipquiz_attempt::ABANDONED),
            array($flipquiz, $student2, 5, null, flipquiz_attempt::ABANDONED),
        );

        // Load it in to flipquiz attempts table.
        foreach ($attempts as $attemptdata) {
            list($flipquiz, $student, $attemptnumber, $sumgrades, $state) = $attemptdata;
            $timestart = $timestamp + $attemptnumber * 3600;

            $flipquizobj = flipquiz::create($flipquiz->id, $student->id);
            $quba = question_engine::make_questions_usage_by_activity('mod_flipquiz', $flipquizobj->get_context());
            $quba->set_preferred_behaviour($flipquizobj->get_flipquiz()->preferredbehaviour);

            // Create the new attempt and initialize the question sessions.
            $attempt = flipquiz_create_attempt($flipquizobj, $attemptnumber, null, $timestart, false, $student->id);

            $attempt = flipquiz_start_new_attempt($flipquizobj, $quba, $attempt, $attemptnumber, $timestamp);
            $attempt = flipquiz_attempt_save_started($flipquizobj, $quba, $attempt);

            // Process some responses from the student.
            $attemptobj = flipquiz_attempt::create($attempt->id);
            switch ($state) {
                case flipquiz_attempt::ABANDONED:
                    $attemptobj->process_abandon($timestart + 300, false);
                    break;

                case flipquiz_attempt::IN_PROGRESS:
                    // Do nothing.
                    break;

                case flipquiz_attempt::FINISHED:
                    // Save answer and finish attempt.
                    $attemptobj->process_submitted_actions($timestart + 300, false, [
                            1 => ['answer' => 'My essay by ' . $student->firstname, 'answerformat' => FORMAT_PLAIN]]);
                    $attemptobj->process_finish($timestart + 600, false);

                    // Manually grade it.
                    $quba = $attemptobj->get_question_usage();
                    $quba->get_question_attempt(1)->manual_grade(
                            'Comment', $sumgrades, FORMAT_HTML, $timestart + 1200);
                    question_engine::save_questions_usage_by_activity($quba);
                    $update = new stdClass();
                    $update->id = $attemptobj->get_attemptid();
                    $update->timemodified = $timestart + 1200;
                    $update->sumgrades = $quba->get_total_mark();
                    $DB->update_record('flipquiz_attempts', $update);
                    flipquiz_save_best_grade($attemptobj->get_flipquiz(), $student->id);
                    break;
            }
        }

        // Actually getting the SQL to run is quite hard. Do a minimal set up of
        // some objects.
        $context = context_module::instance($flipquiz->cmid);
        $cm = get_coursemodule_from_id('flipquiz', $flipquiz->cmid);
        $qmsubselect = flipquiz_report_qm_filter_select($flipquiz);
        $studentsjoins = get_enrolled_with_capabilities_join($context);
        $empty = new \core\dml\sql_join();

        // Set the options.
        $reportoptions = new flipquiz_overview_options('overview', $flipquiz, $cm, null);
        $reportoptions->attempts = flipquiz_attempts_report::ENROLLED_ALL;
        $reportoptions->onlygraded = true;
        $reportoptions->states = array(flipquiz_attempt::IN_PROGRESS, flipquiz_attempt::OVERDUE, flipquiz_attempt::FINISHED);

        // Now do a minimal set-up of the table class.
        $q->slot = 1;
        $q->maxmark = 10;
        $table = new flipquiz_overview_table($flipquiz, $context, $qmsubselect, $reportoptions,
                $empty, $studentsjoins, array(1 => $q), null);
        $table->download = $isdownloading; // Cannot call the is_downloading API, because it gives errors.
        $table->define_columns(array('fullname'));
        $table->sortable(true, 'uniqueid');
        $table->define_baseurl(new moodle_url('/mod/flipquiz/report.php'));
        $table->setup();

        // Run the query.
        $table->setup_sql_queries($studentsjoins);
        $table->query_db(30, false);

        // Should be 4 rows, matching count($table->rawdata) tested below.
        // The count is only done if not downloading.
        if (!$isdownloading) {
            $this->assertEquals(4, $table->totalrows);
        }

        // Verify what was returned: Student 1's best and in progress attempts.
        // Student 2's finshed attempt, and Student 3 with no attempt.
        // The array key is {student id}#{attempt number}.
        $this->assertEquals(4, count($table->rawdata));
        $this->assertArrayHasKey($student1->id . '#3', $table->rawdata);
        $this->assertEquals(1, $table->rawdata[$student1->id . '#3']->gradedattempt);
        $this->assertArrayHasKey($student1->id . '#3', $table->rawdata);
        $this->assertEquals(0, $table->rawdata[$student1->id . '#5']->gradedattempt);
        $this->assertArrayHasKey($student2->id . '#3', $table->rawdata);
        $this->assertEquals(1, $table->rawdata[$student2->id . '#3']->gradedattempt);
        $this->assertArrayHasKey($student3->id . '#0', $table->rawdata);
        $this->assertEquals(0, $table->rawdata[$student3->id . '#0']->gradedattempt);

        // Check the calculation of averages.
        $averagerow = $table->compute_average_row('overallaverage', $studentsjoins);
        $this->assertContains('75.00', $averagerow['sumgrades']);
        $this->assertContains('75.00', $averagerow['qsgrade1']);
        if (!$isdownloading) {
            $this->assertContains('(2)', $averagerow['sumgrades']);
            $this->assertContains('(2)', $averagerow['qsgrade1']);
        }

        // Ensure that filtering by initial does not break it.
        // This involves setting a private properly of the base class, which is
        // only really possible using reflection :-(.
        $reflectionobject = new ReflectionObject($table);
        while ($parent = $reflectionobject->getParentClass()) {
            $reflectionobject = $parent;
        }
        $prefsproperty = $reflectionobject->getProperty('prefs');
        $prefsproperty->setAccessible(true);
        $prefs = $prefsproperty->getValue($table);
        $prefs['i_first'] = 'A';
        $prefsproperty->setValue($table, $prefs);

        list($fields, $from, $where, $params) = $table->base_sql($studentsjoins);
        $table->set_count_sql("SELECT COUNT(1) FROM (SELECT $fields FROM $from WHERE $where) temp WHERE 1 = 1", $params);
        $table->set_sql($fields, $from, $where, $params);
        $table->query_db(30, false);
        // Just verify that this does not cause a fatal error.
    }

    /**
     * Bands provider.
     * @return array
     */
    public function get_bands_count_and_width_provider() {
        return [
            [10, [20, .5]],
            [20, [20, 1]],
            [30, [15, 2]],
            // TODO MDL-55068 Handle bands better when grade is 50.
            // [50, [10, 5]],
            [100, [20, 5]],
            [200, [20, 10]],
        ];
    }

    /**
     * Test bands.
     *
     * @dataProvider get_bands_count_and_width_provider
     * @param int $grade grade
     * @param array $expected
     */
    public function test_get_bands_count_and_width($grade, $expected) {
        $this->resetAfterTest(true);
        $flipquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_flipquiz');
        $flipquiz = $flipquizgenerator->create_instance(['course' => SITEID, 'grade' => $grade]);
        $this->assertEquals($expected, flipquiz_overview_report::get_bands_count_and_width($flipquiz));
    }

}