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
 * Unit tests for (some of) mod/pratest/locallib.php.
 *
 * @package    mod_pratest
 * @category   test
 * @copyright  2008 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/pratest/locallib.php');


/**
 * Unit tests for (some of) mod/pratest/locallib.php.
 *
 * @copyright  2008 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pratest_locallib_testcase extends advanced_testcase {

    public function test_pratest_rescale_grade() {
        $pratest = new stdClass();
        $pratest->decimalpoints = 2;
        $pratest->questiondecimalpoints = 3;
        $pratest->grade = 10;
        $pratest->sumgrades = 10;
        $this->assertEquals(pratest_rescale_grade(0.12345678, $pratest, false), 0.12345678);
        $this->assertEquals(pratest_rescale_grade(0.12345678, $pratest, true), format_float(0.12, 2));
        $this->assertEquals(pratest_rescale_grade(0.12345678, $pratest, 'question'),
            format_float(0.123, 3));
        $pratest->sumgrades = 5;
        $this->assertEquals(pratest_rescale_grade(0.12345678, $pratest, false), 0.24691356);
        $this->assertEquals(pratest_rescale_grade(0.12345678, $pratest, true), format_float(0.25, 2));
        $this->assertEquals(pratest_rescale_grade(0.12345678, $pratest, 'question'),
            format_float(0.247, 3));
    }

    public function pratest_attempt_state_data_provider() {
        return [
            [pratest_attempt::IN_PROGRESS, null, null, mod_pratest_display_options::DURING],
            [pratest_attempt::FINISHED, -90, null, mod_pratest_display_options::IMMEDIATELY_AFTER],
            [pratest_attempt::FINISHED, -7200, null, mod_pratest_display_options::LATER_WHILE_OPEN],
            [pratest_attempt::FINISHED, -7200, 3600, mod_pratest_display_options::LATER_WHILE_OPEN],
            [pratest_attempt::FINISHED, -30, 30, mod_pratest_display_options::IMMEDIATELY_AFTER],
            [pratest_attempt::FINISHED, -90, -30, mod_pratest_display_options::AFTER_CLOSE],
            [pratest_attempt::FINISHED, -7200, -3600, mod_pratest_display_options::AFTER_CLOSE],
            [pratest_attempt::FINISHED, -90, -3600, mod_pratest_display_options::AFTER_CLOSE],
            [pratest_attempt::ABANDONED, -10000000, null, mod_pratest_display_options::LATER_WHILE_OPEN],
            [pratest_attempt::ABANDONED, -7200, 3600, mod_pratest_display_options::LATER_WHILE_OPEN],
            [pratest_attempt::ABANDONED, -7200, -3600, mod_pratest_display_options::AFTER_CLOSE],
        ];
    }

    /**
     * @dataProvider pratest_attempt_state_data_provider
     *
     * @param unknown $attemptstate as in the pratest_attempts.state DB column.
     * @param unknown $relativetimefinish time relative to now when the attempt finished, or null for 0.
     * @param unknown $relativetimeclose time relative to now when the pratest closes, or null for 0.
     * @param unknown $expectedstate expected result. One of the mod_pratest_display_options constants/
     */
    public function test_pratest_attempt_state($attemptstate,
            $relativetimefinish, $relativetimeclose, $expectedstate) {

        $attempt = new stdClass();
        $attempt->state = $attemptstate;
        if ($relativetimefinish === null) {
            $attempt->timefinish = 0;
        } else {
            $attempt->timefinish = time() + $relativetimefinish;
        }

        $pratest = new stdClass();
        if ($relativetimeclose === null) {
            $pratest->timeclose = 0;
        } else {
            $pratest->timeclose = time() + $relativetimeclose;
        }

        $this->assertEquals($expectedstate, pratest_attempt_state($pratest, $attempt));
    }

    public function test_pratest_question_tostring() {
        $question = new stdClass();
        $question->qtype = 'multichoice';
        $question->name = 'The question name';
        $question->questiontext = '<p>What sort of <b>inequality</b> is x &lt; y<img alt="?" src="..."></p>';
        $question->questiontextformat = FORMAT_HTML;

        $summary = pratest_question_tostring($question);
        $this->assertEquals('<span class="questionname">The question name</span> ' .
                '<span class="questiontext">What sort of INEQUALITY is x &lt; y[?]' . "\n" . '</span>', $summary);
    }

    /**
     * Test pratest_view
     * @return void
     */
    public function test_pratest_view() {
        global $CFG;

        $CFG->enablecompletion = 1;
        $this->resetAfterTest();

        $this->setAdminUser();
        // Setup test data.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $pratest = $this->getDataGenerator()->create_module('pratest', array('course' => $course->id),
                                                            array('completion' => 2, 'completionview' => 1));
        $context = context_module::instance($pratest->cmid);
        $cm = get_coursemodule_from_instance('pratest', $pratest->id);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        pratest_view($pratest, $course, $cm, $context);

        $events = $sink->get_events();
        // 2 additional events thanks to completion.
        $this->assertCount(3, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pratest\event\course_module_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $moodleurl = new \moodle_url('/mod/pratest/view.php', array('id' => $cm->id));
        $this->assertEquals($moodleurl, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        // Check completion status.
        $completion = new completion_info($course);
        $completiondata = $completion->get_data($cm);
        $this->assertEquals(1, $completiondata->completionstate);
    }

    /**
     * Return false when there are not overrides for this pratest instance.
     */
    public function test_pratest_is_overriden_calendar_event_no_override() {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $pratestgenerator = $generator->get_plugin_generator('mod_pratest');
        $pratest = $pratestgenerator->create_instance(['course' => $course->id]);

        $event = new \calendar_event((object)[
            'modulename' => 'pratest',
            'instance' => $pratest->id,
            'userid' => $user->id
        ]);

        $this->assertFalse(pratest_is_overriden_calendar_event($event));
    }

    /**
     * Return false if the given event isn't an pratest module event.
     */
    public function test_pratest_is_overriden_calendar_event_no_module_event() {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $pratestgenerator = $generator->get_plugin_generator('mod_pratest');
        $pratest = $pratestgenerator->create_instance(['course' => $course->id]);

        $event = new \calendar_event((object)[
            'userid' => $user->id
        ]);

        $this->assertFalse(pratest_is_overriden_calendar_event($event));
    }

    /**
     * Return false if there is overrides for this use but they belong to another pratest
     * instance.
     */
    public function test_pratest_is_overriden_calendar_event_different_pratest_instance() {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $pratestgenerator = $generator->get_plugin_generator('mod_pratest');
        $pratest = $pratestgenerator->create_instance(['course' => $course->id]);
        $pratest2 = $pratestgenerator->create_instance(['course' => $course->id]);

        $event = new \calendar_event((object) [
            'modulename' => 'pratest',
            'instance' => $pratest->id,
            'userid' => $user->id
        ]);

        $record = (object) [
            'pratest' => $pratest2->id,
            'userid' => $user->id
        ];

        $DB->insert_record('pratest_overrides', $record);

        $this->assertFalse(pratest_is_overriden_calendar_event($event));
    }

    /**
     * Return true if there is a user override for this event and pratest instance.
     */
    public function test_pratest_is_overriden_calendar_event_user_override() {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $pratestgenerator = $generator->get_plugin_generator('mod_pratest');
        $pratest = $pratestgenerator->create_instance(['course' => $course->id]);

        $event = new \calendar_event((object) [
            'modulename' => 'pratest',
            'instance' => $pratest->id,
            'userid' => $user->id
        ]);

        $record = (object) [
            'pratest' => $pratest->id,
            'userid' => $user->id
        ];

        $DB->insert_record('pratest_overrides', $record);

        $this->assertTrue(pratest_is_overriden_calendar_event($event));
    }

    /**
     * Return true if there is a group override for the event and pratest instance.
     */
    public function test_pratest_is_overriden_calendar_event_group_override() {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $pratestgenerator = $generator->get_plugin_generator('mod_pratest');
        $pratest = $pratestgenerator->create_instance(['course' => $course->id]);
        $group = $this->getDataGenerator()->create_group(array('courseid' => $pratest->course));
        $groupid = $group->id;
        $userid = $user->id;

        $event = new \calendar_event((object) [
            'modulename' => 'pratest',
            'instance' => $pratest->id,
            'groupid' => $groupid
        ]);

        $record = (object) [
            'pratest' => $pratest->id,
            'groupid' => $groupid
        ];

        $DB->insert_record('pratest_overrides', $record);

        $this->assertTrue(pratest_is_overriden_calendar_event($event));
    }

    /**
     * Test test_pratest_get_user_timeclose().
     */
    public function test_pratest_get_user_timeclose() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $basetimestamp = time(); // The timestamp we will base the enddates on.

        // Create generator, course and pratestzes.
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $pratestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pratest');

        // Both pratestzes close in two hours.
        $pratest1 = $pratestgenerator->create_instance(array('course' => $course->id, 'timeclose' => $basetimestamp + 7200));
        $pratest2 = $pratestgenerator->create_instance(array('course' => $course->id, 'timeclose' => $basetimestamp + 7200));
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        $student1id = $student1->id;
        $student2id = $student2->id;
        $student3id = $student3->id;
        $teacherid = $teacher->id;

        // Users enrolments.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($student1id, $course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student2id, $course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student3id, $course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($teacherid, $course->id, $teacherrole->id, 'manual');

        // Create groups.
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group1id = $group1->id;
        $group2id = $group2->id;
        $this->getDataGenerator()->create_group_member(array('userid' => $student1id, 'groupid' => $group1id));
        $this->getDataGenerator()->create_group_member(array('userid' => $student2id, 'groupid' => $group2id));

        // Group 1 gets an group override for pratest 1 to close in three hours.
        $record1 = (object) [
            'pratest' => $pratest1->id,
            'groupid' => $group1id,
            'timeclose' => $basetimestamp + 10800 // In three hours.
        ];
        $DB->insert_record('pratest_overrides', $record1);

        // Let's test pratest 1 closes in three hours for user student 1 since member of group 1.
        // Quiz 2 closes in two hours.
        $this->setUser($student1id);
        $params = new stdClass();

        $comparearray = array();
        $object = new stdClass();
        $object->id = $pratest1->id;
        $object->usertimeclose = $basetimestamp + 10800; // The overriden timeclose for pratest 1.

        $comparearray[$pratest1->id] = $object;

        $object = new stdClass();
        $object->id = $pratest2->id;
        $object->usertimeclose = $basetimestamp + 7200; // The unchanged timeclose for pratest 2.

        $comparearray[$pratest2->id] = $object;

        $this->assertEquals($comparearray, pratest_get_user_timeclose($course->id));

        // Let's test pratest 1 closes in two hours (the original value) for user student 3 since member of no group.
        $this->setUser($student3id);
        $params = new stdClass();

        $comparearray = array();
        $object = new stdClass();
        $object->id = $pratest1->id;
        $object->usertimeclose = $basetimestamp + 7200; // The original timeclose for pratest 1.

        $comparearray[$pratest1->id] = $object;

        $object = new stdClass();
        $object->id = $pratest2->id;
        $object->usertimeclose = $basetimestamp + 7200; // The original timeclose for pratest 2.

        $comparearray[$pratest2->id] = $object;

        $this->assertEquals($comparearray, pratest_get_user_timeclose($course->id));

        // User 2 gets an user override for pratest 1 to close in four hours.
        $record2 = (object) [
            'pratest' => $pratest1->id,
            'userid' => $student2id,
            'timeclose' => $basetimestamp + 14400 // In four hours.
        ];
        $DB->insert_record('pratest_overrides', $record2);

        // Let's test pratest 1 closes in four hours for user student 2 since personally overriden.
        // Quiz 2 closes in two hours.
        $this->setUser($student2id);

        $comparearray = array();
        $object = new stdClass();
        $object->id = $pratest1->id;
        $object->usertimeclose = $basetimestamp + 14400; // The overriden timeclose for pratest 1.

        $comparearray[$pratest1->id] = $object;

        $object = new stdClass();
        $object->id = $pratest2->id;
        $object->usertimeclose = $basetimestamp + 7200; // The unchanged timeclose for pratest 2.

        $comparearray[$pratest2->id] = $object;

        $this->assertEquals($comparearray, pratest_get_user_timeclose($course->id));

        // Let's test a teacher sees the original times.
        // Quiz 1 and pratest 2 close in two hours.
        $this->setUser($teacherid);

        $comparearray = array();
        $object = new stdClass();
        $object->id = $pratest1->id;
        $object->usertimeclose = $basetimestamp + 7200; // The unchanged timeclose for pratest 1.

        $comparearray[$pratest1->id] = $object;

        $object = new stdClass();
        $object->id = $pratest2->id;
        $object->usertimeclose = $basetimestamp + 7200; // The unchanged timeclose for pratest 2.

        $comparearray[$pratest2->id] = $object;

        $this->assertEquals($comparearray, pratest_get_user_timeclose($course->id));
    }

    /**
     * This function creates a pratest with some standard (non-random) and some random questions.
     * The standard questions are created first and then random questions follow them.
     * So in a pratest with 3 standard question and 2 random question, the first random question is at slot 4.
     *
     * @param int $qnum Number of standard questions that should be created in the pratest.
     * @param int $randomqnum Number of random questions that should be created in the pratest.
     * @param array $questiontags Tags to be used for random questions.
     *      This is an array in the following format:
     *      [
     *          0 => ['foo', 'bar'],
     *          1 => ['baz', 'qux']
     *      ]
     * @param string[] $unusedtags Some additional tags to be created.
     * @return array An array of 2 elements: $pratest and $tagobjects.
     *      $tagobjects is an associative array of all created tag objects with its key being tag names.
     */
    private function setup_pratest_and_tags($qnum, $randomqnum, $questiontags = [], $unusedtags = []) {
        global $SITE;

        $tagobjects = [];

        // Get all the tags that need to be created.
        $alltags = [];
        foreach ($questiontags as $questiontag) {
            $alltags = array_merge($alltags, $questiontag);
        }
        $alltags = array_merge($alltags, $unusedtags);
        $alltags = array_unique($alltags);

        // Create tags.
        foreach ($alltags as $tagname) {
            $tagrecord = array(
                'isstandard' => 1,
                'flag' => 0,
                'rawname' => $tagname,
                'description' => $tagname . ' desc'
            );
            $tagobjects[$tagname] = $this->getDataGenerator()->create_tag($tagrecord);
        }

        // Create a pratest.
        $pratestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pratest');
        $pratest = $pratestgenerator->create_instance(array('course' => $SITE->id, 'questionsperpage' => 3, 'grade' => 100.0));

        // Create a question category in the system context.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        // Setup standard questions.
        for ($i = 0; $i < $qnum; $i++) {
            $question = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
            pratest_add_pratest_question($question->id, $pratest);
        }
        // Setup random questions.
        for ($i = 0; $i < $randomqnum; $i++) {
            // Just create a standard question first, so there would be enough questions to pick a random question from.
            $question = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
            $tagids = [];
            if (!empty($questiontags[$i])) {
                foreach ($questiontags[$i] as $tagname) {
                    $tagids[] = $tagobjects[$tagname]->id;
                }
            }
            pratest_add_random_questions($pratest, 0, $cat->id, 1, false, $tagids);
        }

        return array($pratest, $tagobjects);
    }

    public function test_pratest_retrieve_slot_tags() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        list($pratest, $tags) = $this->setup_pratest_and_tags(1, 1, [['foo', 'bar']], ['baz']);

        // Get the random question's slotid. It is at the second slot.
        $slotid = $DB->get_field('pratest_slots', 'id', array('pratestid' => $pratest->id, 'slot' => 2));
        $slottags = pratest_retrieve_slot_tags($slotid);

        $this->assertEquals(
                [
                    ['tagid' => $tags['foo']->id, 'tagname' => $tags['foo']->name],
                    ['tagid' => $tags['bar']->id, 'tagname' => $tags['bar']->name]
                ],
                array_map(function($slottag) {
                    return ['tagid' => $slottag->tagid, 'tagname' => $slottag->tagname];
                }, $slottags),
                '', 0.0, 10, true);
    }

    public function test_pratest_retrieve_slot_tags_with_removed_tag() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        list($pratest, $tags) = $this->setup_pratest_and_tags(1, 1, [['foo', 'bar']], ['baz']);

        // Get the random question's slotid. It is at the second slot.
        $slotid = $DB->get_field('pratest_slots', 'id', array('pratestid' => $pratest->id, 'slot' => 2));
        $slottags = pratest_retrieve_slot_tags($slotid);

        // Now remove the foo tag and check again.
        core_tag_tag::delete_tags([$tags['foo']->id]);
        $slottags = pratest_retrieve_slot_tags($slotid);

        $this->assertEquals(
                [
                    ['tagid' => null, 'tagname' => $tags['foo']->name],
                    ['tagid' => $tags['bar']->id, 'tagname' => $tags['bar']->name]
                ],
                array_map(function($slottag) {
                    return ['tagid' => $slottag->tagid, 'tagname' => $slottag->tagname];
                }, $slottags),
                '', 0.0, 10, true);
    }

    public function test_pratest_retrieve_slot_tags_for_standard_question() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        list($pratest, $tags) = $this->setup_pratest_and_tags(1, 1, [['foo', 'bar']]);

        // Get the standard question's slotid. It is at the first slot.
        $slotid = $DB->get_field('pratest_slots', 'id', array('pratestid' => $pratest->id, 'slot' => 1));

        // There should be no slot tags for a non-random question.
        $this->assertCount(0, pratest_retrieve_slot_tags($slotid));
    }

    public function test_pratest_retrieve_slot_tag_ids() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        list($pratest, $tags) = $this->setup_pratest_and_tags(1, 1, [['foo', 'bar']], ['baz']);

        // Get the random question's slotid. It is at the second slot.
        $slotid = $DB->get_field('pratest_slots', 'id', array('pratestid' => $pratest->id, 'slot' => 2));
        $tagids = pratest_retrieve_slot_tag_ids($slotid);

        $this->assertEquals([$tags['foo']->id, $tags['bar']->id], $tagids, '', 0.0, 10, true);
    }

    public function test_pratest_retrieve_slot_tag_ids_for_standard_question() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        list($pratest, $tags) = $this->setup_pratest_and_tags(1, 1, [['foo', 'bar']], ['baz']);

        // Get the standard question's slotid. It is at the first slot.
        $slotid = $DB->get_field('pratest_slots', 'id', array('pratestid' => $pratest->id, 'slot' => 1));
        $tagids = pratest_retrieve_slot_tag_ids($slotid);

        $this->assertEquals([], $tagids, '', 0.0, 10, true);
    }

    /**
     * Data provider for the get_random_question_summaries test.
     */
    public function get_pratest_retrieve_tags_for_slot_ids_test_cases() {
        return [
            'no questions' => [
                'questioncount' => 0,
                'randomquestioncount' => 0,
                'randomquestiontags' => [],
                'unusedtags' => [],
                'removeslottagids' => [],
                'expected' => []
            ],
            'only regular questions' => [
                'questioncount' => 2,
                'randomquestioncount' => 0,
                'randomquestiontags' => [],
                'unusedtags' => ['unused1', 'unused2'],
                'removeslottagids' => [],
                'expected' => [
                    1 => [],
                    2 => []
                ]
            ],
            'only random questions 1' => [
                'questioncount' => 0,
                'randomquestioncount' => 2,
                'randomquestiontags' => [
                    0 => ['foo'],
                    1 => []
                ],
                'unusedtags' => ['unused1', 'unused2'],
                'removeslottagids' => [],
                'expected' => [
                    1 => ['foo'],
                    2 => []
                ]
            ],
            'only random questions 2' => [
                'questioncount' => 0,
                'randomquestioncount' => 2,
                'randomquestiontags' => [
                    0 => ['foo', 'bop'],
                    1 => ['bar']
                ],
                'unusedtags' => ['unused1', 'unused2'],
                'removeslottagids' => [],
                'expected' => [
                    1 => ['foo', 'bop'],
                    2 => ['bar']
                ]
            ],
            'only random questions 3' => [
                'questioncount' => 0,
                'randomquestioncount' => 2,
                'randomquestiontags' => [
                    0 => ['foo', 'bop'],
                    1 => ['bar', 'foo']
                ],
                'unusedtags' => ['unused1', 'unused2'],
                'removeslottagids' => [],
                'expected' => [
                    1 => ['foo', 'bop'],
                    2 => ['bar', 'foo']
                ]
            ],
            'combination of questions 1' => [
                'questioncount' => 2,
                'randomquestioncount' => 2,
                'randomquestiontags' => [
                    0 => ['foo'],
                    1 => []
                ],
                'unusedtags' => ['unused1', 'unused2'],
                'removeslottagids' => [],
                'expected' => [
                    1 => [],
                    2 => [],
                    3 => ['foo'],
                    4 => []
                ]
            ],
            'combination of questions 2' => [
                'questioncount' => 2,
                'randomquestioncount' => 2,
                'randomquestiontags' => [
                    0 => ['foo', 'bop'],
                    1 => ['bar']
                ],
                'unusedtags' => ['unused1', 'unused2'],
                'removeslottagids' => [],
                'expected' => [
                    1 => [],
                    2 => [],
                    3 => ['foo', 'bop'],
                    4 => ['bar']
                ]
            ],
            'combination of questions 3' => [
                'questioncount' => 2,
                'randomquestioncount' => 2,
                'randomquestiontags' => [
                    0 => ['foo', 'bop'],
                    1 => ['bar', 'foo']
                ],
                'unusedtags' => ['unused1', 'unused2'],
                'removeslottagids' => [],
                'expected' => [
                    1 => [],
                    2 => [],
                    3 => ['foo', 'bop'],
                    4 => ['bar', 'foo']
                ]
            ],
            'load from name 1' => [
                'questioncount' => 2,
                'randomquestioncount' => 2,
                'randomquestiontags' => [
                    0 => ['foo'],
                    1 => []
                ],
                'unusedtags' => ['unused1', 'unused2'],
                'removeslottagids' => [3],
                'expected' => [
                    1 => [],
                    2 => [],
                    3 => ['foo'],
                    4 => []
                ]
            ],
            'load from name 2' => [
                'questioncount' => 2,
                'randomquestioncount' => 2,
                'randomquestiontags' => [
                    0 => ['foo', 'bop'],
                    1 => ['bar']
                ],
                'unusedtags' => ['unused1', 'unused2'],
                'removeslottagids' => [3],
                'expected' => [
                    1 => [],
                    2 => [],
                    3 => ['foo', 'bop'],
                    4 => ['bar']
                ]
            ],
            'load from name 3' => [
                'questioncount' => 2,
                'randomquestioncount' => 2,
                'randomquestiontags' => [
                    0 => ['foo', 'bop'],
                    1 => ['bar', 'foo']
                ],
                'unusedtags' => ['unused1', 'unused2'],
                'removeslottagids' => [3],
                'expected' => [
                    1 => [],
                    2 => [],
                    3 => ['foo', 'bop'],
                    4 => ['bar', 'foo']
                ]
            ]
        ];
    }

    /**
     * Test the pratest_retrieve_tags_for_slot_ids function with various parameter
     * combinations.
     *
     * @dataProvider get_pratest_retrieve_tags_for_slot_ids_test_cases()
     * @param int $questioncount The number of regular questions to create
     * @param int $randomquestioncount The number of random questions to create
     * @param array $randomquestiontags The tags for the random questions
     * @param string[] $unusedtags Additional tags to create to populate the DB with data
     * @param int[] $removeslottagids Slot numbers to remove tag ids for
     * @param array $expected The expected output of tag names indexed by slot number
     */
    public function test_pratest_retrieve_tags_for_slot_ids_combinations(
        $questioncount,
        $randomquestioncount,
        $randomquestiontags,
        $unusedtags,
        $removeslottagids,
        $expected
    ) {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        list($pratest, $tags) = $this->setup_pratest_and_tags(
            $questioncount,
            $randomquestioncount,
            $randomquestiontags,
            $unusedtags
        );

        $slots = $DB->get_records('pratest_slots', ['pratestid' => $pratest->id]);
        $slotids = [];
        $slotsbynumber = [];
        foreach ($slots as $slot) {
            $slotids[] = $slot->id;
            $slotsbynumber[$slot->slot] = $slot;
        }

        if (!empty($removeslottagids)) {
            // The slots to remove are the slot numbers not the slot id so we need
            // to get the ids for the DB call.
            $idstonull = array_map(function($slot) use ($slotsbynumber) {
                return $slotsbynumber[$slot]->id;
            }, $removeslottagids);
            list($sql, $params) = $DB->get_in_or_equal($idstonull);
            // Null out the tagid column to force the code to look up the tag by name.
            $DB->set_field_select('pratest_slot_tags', 'tagid', null, "slotid {$sql}", $params);
        }

        $slottagsbyslotids = pratest_retrieve_tags_for_slot_ids($slotids);
        // Convert the result into an associative array of slotid => [... tag names..]
        // to make it easier to compare.
        $actual = array_map(function($slottags) {
            $names = array_map(function($slottag) {
                return $slottag->tagname;
            }, $slottags);
            // Make sure the names are sorted for comparison.
            sort($names);
            return $names;
        }, $slottagsbyslotids);

        $formattedexptected = [];
        // The expected values are indexed by slot number rather than id so let
        // convert it to use the id so that we can compare the results.
        foreach ($expected as $slot => $tagnames) {
            sort($tagnames);
            $slotid = $slotsbynumber[$slot]->id;
            $formattedexptected[$slotid] = $tagnames;
        }

        $this->assertEquals($formattedexptected, $actual);
    }
}
