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
 * Unit tests for the {@link \mod_flipquiz\local\structure\slot_random} class.
 *
 * @package    mod_flipquiz
 * @category   test
 * @copyright  2018 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_flipquiz_local_structure_slot_random_test
 * Class for tests related to the {@link \mod_flipquiz\local\structure\slot_random} class.
 *
 * @copyright  2018 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_flipquiz_local_structure_slot_random_test extends advanced_testcase {
    /**
     * Constructor test.
     */
    public function test_constructor() {
        global $SITE;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a flipquiz.
        $flipquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_flipquiz');
        $flipquiz = $flipquizgenerator->create_instance(array('course' => $SITE->id, 'questionsperpage' => 3, 'grade' => 100.0));

        // Create a question category in the system context.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();

        // Create a random question without adding it to a flipquiz.
        // We don't want to use flipquiz_add_random_questions because that itself, instantiates an object from the slot_random class.
        $form = new stdClass();
        $form->category = $category->id . ',' . $category->contextid;
        $form->includesubcategories = true;
        $form->fromtags = [];
        $form->defaultmark = 1;
        $form->hidden = 1;
        $form->stamp = make_unique_id_code();
        $question = new stdClass();
        $question->qtype = 'random';
        $question = question_bank::get_qtype('random')->save_question($question, $form);

        $randomslotdata = new stdClass();
        $randomslotdata->flipquizid = $flipquiz->id;
        $randomslotdata->questionid = $question->id;
        $randomslotdata->questioncategoryid = $category->id;
        $randomslotdata->includingsubcategories = 1;
        $randomslotdata->maxmark = 1;

        $randomslot = new \mod_flipquiz\local\structure\slot_random($randomslotdata);

        $rc = new ReflectionClass('\mod_flipquiz\local\structure\slot_random');
        $rcp = $rc->getProperty('record');
        $rcp->setAccessible(true);
        $record = $rcp->getValue($randomslot);

        $this->assertEquals($flipquiz->id, $record->flipquizid);
        $this->assertEquals($question->id, $record->questionid);
        $this->assertEquals($category->id, $record->questioncategoryid);
        $this->assertEquals(1, $record->includingsubcategories);
        $this->assertEquals(1, $record->maxmark);
    }

    public function test_get_flipquiz_flipquiz() {
        global $SITE, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a flipquiz.
        $flipquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_flipquiz');
        $flipquiz = $flipquizgenerator->create_instance(array('course' => $SITE->id, 'questionsperpage' => 3, 'grade' => 100.0));

        // Create a question category in the system context.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();

        flipquiz_add_random_questions($flipquiz, 0, $category->id, 1, false);

        // Get the random question's id. It is at the first slot.
        $questionid = $DB->get_field('flipquiz_slots', 'questionid', array('flipquizid' => $flipquiz->id, 'slot' => 1));

        $randomslotdata = new stdClass();
        $randomslotdata->flipquizid = $flipquiz->id;
        $randomslotdata->questionid = $questionid;
        $randomslotdata->questioncategoryid = $category->id;
        $randomslotdata->includingsubcategories = 1;
        $randomslotdata->maxmark = 1;

        $randomslot = new \mod_flipquiz\local\structure\slot_random($randomslotdata);

        // The create_instance had injected an additional cmid propery to the flipquiz. Let's remove that.
        unset($flipquiz->cmid);

        $this->assertEquals($flipquiz, $randomslot->get_flipquiz());
    }

    public function test_set_flipquiz() {
        global $SITE, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a flipquiz.
        $flipquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_flipquiz');
        $flipquiz = $flipquizgenerator->create_instance(array('course' => $SITE->id, 'questionsperpage' => 3, 'grade' => 100.0));

        // Create a question category in the system context.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();

        flipquiz_add_random_questions($flipquiz, 0, $category->id, 1, false);

        // Get the random question's id. It is at the first slot.
        $questionid = $DB->get_field('flipquiz_slots', 'questionid', array('flipquizid' => $flipquiz->id, 'slot' => 1));

        $randomslotdata = new stdClass();
        $randomslotdata->flipquizid = $flipquiz->id;
        $randomslotdata->questionid = $questionid;
        $randomslotdata->questioncategoryid = $category->id;
        $randomslotdata->includingsubcategories = 1;
        $randomslotdata->maxmark = 1;

        $randomslot = new \mod_flipquiz\local\structure\slot_random($randomslotdata);

        // The create_instance had injected an additional cmid propery to the flipquiz. Let's remove that.
        unset($flipquiz->cmid);

        $randomslot->set_flipquiz($flipquiz);

        $rc = new ReflectionClass('\mod_flipquiz\local\structure\slot_random');
        $rcp = $rc->getProperty('flipquiz');
        $rcp->setAccessible(true);
        $flipquizpropery = $rcp->getValue($randomslot);

        $this->assertEquals($flipquiz, $flipquizpropery);
    }

    private function setup_for_test_tags($tagnames) {
        global $SITE, $DB;

        // Create a flipquiz.
        $flipquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_flipquiz');
        $flipquiz = $flipquizgenerator->create_instance(array('course' => $SITE->id, 'questionsperpage' => 3, 'grade' => 100.0));

        // Create a question category in the system context.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();

        flipquiz_add_random_questions($flipquiz, 0, $category->id, 1, false);

        // Get the random question's id. It is at the first slot.
        $questionid = $DB->get_field('flipquiz_slots', 'questionid', array('flipquizid' => $flipquiz->id, 'slot' => 1));

        $randomslotdata = new stdClass();
        $randomslotdata->flipquizid = $flipquiz->id;
        $randomslotdata->questionid = $questionid;
        $randomslotdata->questioncategoryid = $category->id;
        $randomslotdata->includingsubcategories = 1;
        $randomslotdata->maxmark = 1;

        $randomslot = new \mod_flipquiz\local\structure\slot_random($randomslotdata);

        // Create tags.
        foreach ($tagnames as $tagname) {
            $tagrecord = array(
                'isstandard' => 1,
                'flag' => 0,
                'rawname' => $tagname,
                'description' => $tagname . ' desc'
            );
            $tags[$tagname] = $this->getDataGenerator()->create_tag($tagrecord);
        }

        return array($randomslot, $tags);
    }

    public function test_set_tags() {
        $this->resetAfterTest();
        $this->setAdminUser();

        list($randomslot, $tags) = $this->setup_for_test_tags(['foo', 'bar']);
        $randomslot->set_tags([$tags['foo'], $tags['bar']]);

        $rc = new ReflectionClass('\mod_flipquiz\local\structure\slot_random');
        $rcp = $rc->getProperty('tags');
        $rcp->setAccessible(true);
        $tagspropery = $rcp->getValue($randomslot);

        $this->assertEquals([
            $tags['foo']->id => $tags['foo'],
            $tags['bar']->id => $tags['bar'],
        ], $tagspropery);
    }

    public function test_set_tags_twice() {
        $this->resetAfterTest();
        $this->setAdminUser();

        list($randomslot, $tags) = $this->setup_for_test_tags(['foo', 'bar', 'baz']);

        // Set tags for the first time.
        $randomslot->set_tags([$tags['foo'], $tags['bar']]);
        // Now set the tags again.
        $randomslot->set_tags([$tags['baz']]);

        $rc = new ReflectionClass('\mod_flipquiz\local\structure\slot_random');
        $rcp = $rc->getProperty('tags');
        $rcp->setAccessible(true);
        $tagspropery = $rcp->getValue($randomslot);

        $this->assertEquals([
            $tags['baz']->id => $tags['baz'],
        ], $tagspropery);
    }

    public function test_set_tags_duplicates() {
        $this->resetAfterTest();
        $this->setAdminUser();

        list($randomslot, $tags) = $this->setup_for_test_tags(['foo', 'bar', 'baz']);

        $randomslot->set_tags([$tags['foo'], $tags['bar'], $tags['foo']]);

        $rc = new ReflectionClass('\mod_flipquiz\local\structure\slot_random');
        $rcp = $rc->getProperty('tags');
        $rcp->setAccessible(true);
        $tagspropery = $rcp->getValue($randomslot);

        $this->assertEquals([
            $tags['foo']->id => $tags['foo'],
            $tags['bar']->id => $tags['bar'],
        ], $tagspropery);
    }

    public function test_set_tags_by_id() {
        $this->resetAfterTest();
        $this->setAdminUser();

        list($randomslot, $tags) = $this->setup_for_test_tags(['foo', 'bar', 'baz']);

        $randomslot->set_tags_by_id([$tags['foo']->id, $tags['bar']->id]);

        $rc = new ReflectionClass('\mod_flipquiz\local\structure\slot_random');
        $rcp = $rc->getProperty('tags');
        $rcp->setAccessible(true);
        $tagspropery = $rcp->getValue($randomslot);

        // The set_tags_by_id function only retrieves id and name fields of the tag object.
        $this->assertCount(2, $tagspropery);
        $this->assertArrayHasKey($tags['foo']->id, $tagspropery);
        $this->assertArrayHasKey($tags['bar']->id, $tagspropery);
        $this->assertEquals(
                (object)['id' => $tags['foo']->id, 'name' => $tags['foo']->name],
                $tagspropery[$tags['foo']->id]->to_object()
        );
        $this->assertEquals(
                (object)['id' => $tags['bar']->id, 'name' => $tags['bar']->name],
                $tagspropery[$tags['bar']->id]->to_object()
        );
    }

    public function test_set_tags_by_id_twice() {
        $this->resetAfterTest();
        $this->setAdminUser();

        list($randomslot, $tags) = $this->setup_for_test_tags(['foo', 'bar', 'baz']);

        // Set tags for the first time.
        $randomslot->set_tags_by_id([$tags['foo']->id, $tags['bar']->id]);
        // Now set the tags again.
        $randomslot->set_tags_by_id([$tags['baz']->id]);

        $rc = new ReflectionClass('\mod_flipquiz\local\structure\slot_random');
        $rcp = $rc->getProperty('tags');
        $rcp->setAccessible(true);
        $tagspropery = $rcp->getValue($randomslot);

        // The set_tags_by_id function only retrieves id and name fields of the tag object.
        $this->assertCount(1, $tagspropery);
        $this->assertArrayHasKey($tags['baz']->id, $tagspropery);
        $this->assertEquals(
                (object)['id' => $tags['baz']->id, 'name' => $tags['baz']->name],
                $tagspropery[$tags['baz']->id]->to_object()
        );
    }

    public function test_set_tags_by_id_duplicates() {
        $this->resetAfterTest();
        $this->setAdminUser();

        list($randomslot, $tags) = $this->setup_for_test_tags(['foo', 'bar', 'baz']);

        $randomslot->set_tags_by_id([$tags['foo']->id, $tags['bar']->id], $tags['foo']->id);

        $rc = new ReflectionClass('\mod_flipquiz\local\structure\slot_random');
        $rcp = $rc->getProperty('tags');
        $rcp->setAccessible(true);
        $tagspropery = $rcp->getValue($randomslot);

        // The set_tags_by_id function only retrieves id and name fields of the tag object.
        $this->assertCount(2, $tagspropery);
        $this->assertArrayHasKey($tags['foo']->id, $tagspropery);
        $this->assertArrayHasKey($tags['bar']->id, $tagspropery);
        $this->assertEquals(
                (object)['id' => $tags['foo']->id, 'name' => $tags['foo']->name],
                $tagspropery[$tags['foo']->id]->to_object()
        );
        $this->assertEquals(
                (object)['id' => $tags['bar']->id, 'name' => $tags['bar']->name],
                $tagspropery[$tags['bar']->id]->to_object()
        );
    }

    public function test_insert() {
        global $SITE, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a flipquiz.
        $flipquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_flipquiz');
        $flipquiz = $flipquizgenerator->create_instance(array('course' => $SITE->id, 'questionsperpage' => 3, 'grade' => 100.0));

        // Create a question category in the system context.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();

        // Create a random question without adding it to a flipquiz.
        $form = new stdClass();
        $form->category = $category->id . ',' . $category->contextid;
        $form->includesubcategories = true;
        $form->fromtags = [];
        $form->defaultmark = 1;
        $form->hidden = 1;
        $form->stamp = make_unique_id_code();
        $question = new stdClass();
        $question->qtype = 'random';
        $question = question_bank::get_qtype('random')->save_question($question, $form);

        // Prepare 2 tags.
        $tagrecord = array(
            'isstandard' => 1,
            'flag' => 0,
            'rawname' => 'foo',
            'description' => 'foo desc'
        );
        $footag = $this->getDataGenerator()->create_tag($tagrecord);
        $tagrecord = array(
            'isstandard' => 1,
            'flag' => 0,
            'rawname' => 'bar',
            'description' => 'bar desc'
        );
        $bartag = $this->getDataGenerator()->create_tag($tagrecord);

        $randomslotdata = new stdClass();
        $randomslotdata->flipquizid = $flipquiz->id;
        $randomslotdata->questionid = $question->id;
        $randomslotdata->questioncategoryid = $category->id;
        $randomslotdata->includingsubcategories = 1;
        $randomslotdata->maxmark = 1;

        // Insert the random question to the flipquiz.
        $randomslot = new \mod_flipquiz\local\structure\slot_random($randomslotdata);
        $randomslot->set_tags([$footag, $bartag]);
        $randomslot->insert(1); // Put the question on the first page of the flipquiz.

        // Get the random question's flipquiz_slot. It is at the first slot.
        $flipquizslot = $DB->get_record('flipquiz_slots', array('flipquizid' => $flipquiz->id, 'slot' => 1));
        // Get the random question's tags from flipquiz_slot_tags. It is at the first slot.
        $flipquizslottags = $DB->get_records('flipquiz_slot_tags', array('slotid' => $flipquizslot->id));

        $this->assertEquals($question->id, $flipquizslot->questionid);
        $this->assertEquals($category->id, $flipquizslot->questioncategoryid);
        $this->assertEquals(1, $flipquizslot->includingsubcategories);
        $this->assertEquals(1, $flipquizslot->maxmark);

        $this->assertCount(2, $flipquizslottags);
        $this->assertEquals(
                [
                    ['tagid' => $footag->id, 'tagname' => $footag->name],
                    ['tagid' => $bartag->id, 'tagname' => $bartag->name]
                ],
                array_map(function($slottag) {
                    return ['tagid' => $slottag->tagid, 'tagname' => $slottag->tagname];
                }, $flipquizslottags),
                '', 0.0, 10, true);
    }
}