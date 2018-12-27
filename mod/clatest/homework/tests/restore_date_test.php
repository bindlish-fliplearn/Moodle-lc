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
 * Restore date tests.
 *
 * @package    mod_homework
 * @copyright  2017 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Restore date tests.
 *
 * @package    mod_homework
 * @copyright  2017 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_homework_restore_date_testcase extends restore_date_testcase
{

    /**
     * Test restore dates.
     */
    public function test_restore_dates() {
        global $DB, $USER;

        // Create homework data.
        $record = ['timeopen' => 100, 'timeclose' => 100, 'timemodified' => 100, 'tiemcreated' => 100, 'questionsperpage' => 0,
            'grade' => 100.0, 'sumgrades' => 2];
        list($course, $homework) = $this->create_course_and_module('homework', $record);

        // Create questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        // Add to the homework.
        homework_add_homework_question($saq->id, $homework);

        // Create an attempt.
        $timestamp = 100;
        $homeworkobj = homework::create($homework->id);
        $attempt = homework_create_attempt($homeworkobj, 1, false, $timestamp, false);
        $quba = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj->get_context());
        $quba->set_preferred_behaviour($homeworkobj->get_homework()->preferredbehaviour);
        homework_start_new_attempt($homeworkobj, $quba, $attempt, 1, $timestamp);
        homework_attempt_save_started($homeworkobj, $quba, $attempt);

        // Quiz grade.
        $grade = new stdClass();
        $grade->homework = $homework->id;
        $grade->userid = $USER->id;
        $grade->grade = 8.9;
        $grade->timemodified = $timestamp;
        $grade->id = $DB->insert_record('homework_grades', $grade);

        // User override.
        $override = (object)[
            'homework' => $homework->id,
            'groupid' => 0,
            'userid' => $USER->id,
            'sortorder' => 1,
            'timeopen' => 100,
            'timeclose' => 200
        ];
        $DB->insert_record('homework_overrides', $override);

        // Set time fields to a constant for easy validation.
        $DB->set_field('homework_attempts', 'timefinish', $timestamp);

        // Do backup and restore.
        $newcourseid = $this->backup_and_restore($course);
        $newhomework = $DB->get_record('homework', ['course' => $newcourseid]);

        $this->assertFieldsNotRolledForward($homework, $newhomework, ['timecreated', 'timemodified']);
        $props = ['timeclose', 'timeopen'];
        $this->assertFieldsRolledForward($homework, $newhomework, $props);

        $newattempt = $DB->get_record('homework_attempts', ['homework' => $newhomework->id]);
        $newoverride = $DB->get_record('homework_overrides', ['homework' => $newhomework->id]);
        $newgrade = $DB->get_record('homework_grades', ['homework' => $newhomework->id]);

        // Attempt time checks.
        $diff = $this->get_diff();
        $this->assertEquals($timestamp, $newattempt->timemodified);
        $this->assertEquals($timestamp, $newattempt->timefinish);
        $this->assertEquals($timestamp, $newattempt->timestart);
        $this->assertEquals($timestamp + $diff, $newattempt->timecheckstate); // Should this be rolled?

        // Quiz override time checks.
        $diff = $this->get_diff();
        $this->assertEquals($override->timeopen + $diff, $newoverride->timeopen);
        $this->assertEquals($override->timeclose + $diff, $newoverride->timeclose);

        // Quiz grade time checks.
        $this->assertEquals($grade->timemodified, $newgrade->timemodified);
    }
}
