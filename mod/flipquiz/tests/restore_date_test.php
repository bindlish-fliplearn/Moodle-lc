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
 * @package    mod_flipquiz
 * @copyright  2017 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Restore date tests.
 *
 * @package    mod_flipquiz
 * @copyright  2017 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_flipquiz_restore_date_testcase extends restore_date_testcase
{

    /**
     * Test restore dates.
     */
    public function test_restore_dates() {
        global $DB, $USER;

        // Create flipquiz data.
        $record = ['timeopen' => 100, 'timeclose' => 100, 'timemodified' => 100, 'tiemcreated' => 100, 'questionsperpage' => 0,
            'grade' => 100.0, 'sumgrades' => 2];
        list($course, $flipquiz) = $this->create_course_and_module('flipquiz', $record);

        // Create questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        // Add to the flipquiz.
        flipquiz_add_flipquiz_question($saq->id, $flipquiz);

        // Create an attempt.
        $timestamp = 100;
        $flipquizobj = flipquiz::create($flipquiz->id);
        $attempt = flipquiz_create_attempt($flipquizobj, 1, false, $timestamp, false);
        $quba = question_engine::make_questions_usage_by_activity('mod_flipquiz', $flipquizobj->get_context());
        $quba->set_preferred_behaviour($flipquizobj->get_flipquiz()->preferredbehaviour);
        flipquiz_start_new_attempt($flipquizobj, $quba, $attempt, 1, $timestamp);
        flipquiz_attempt_save_started($flipquizobj, $quba, $attempt);

        // Quiz grade.
        $grade = new stdClass();
        $grade->flipquiz = $flipquiz->id;
        $grade->userid = $USER->id;
        $grade->grade = 8.9;
        $grade->timemodified = $timestamp;
        $grade->id = $DB->insert_record('flipquiz_grades', $grade);

        // User override.
        $override = (object)[
            'flipquiz' => $flipquiz->id,
            'groupid' => 0,
            'userid' => $USER->id,
            'sortorder' => 1,
            'timeopen' => 100,
            'timeclose' => 200
        ];
        $DB->insert_record('flipquiz_overrides', $override);

        // Set time fields to a constant for easy validation.
        $DB->set_field('flipquiz_attempts', 'timefinish', $timestamp);

        // Do backup and restore.
        $newcourseid = $this->backup_and_restore($course);
        $newflipquiz = $DB->get_record('flipquiz', ['course' => $newcourseid]);

        $this->assertFieldsNotRolledForward($flipquiz, $newflipquiz, ['timecreated', 'timemodified']);
        $props = ['timeclose', 'timeopen'];
        $this->assertFieldsRolledForward($flipquiz, $newflipquiz, $props);

        $newattempt = $DB->get_record('flipquiz_attempts', ['flipquiz' => $newflipquiz->id]);
        $newoverride = $DB->get_record('flipquiz_overrides', ['flipquiz' => $newflipquiz->id]);
        $newgrade = $DB->get_record('flipquiz_grades', ['flipquiz' => $newflipquiz->id]);

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
