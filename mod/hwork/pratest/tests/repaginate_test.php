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
 * Unit tests for the {@link \mod_pratest\repaginate} class.
 * @package   mod_pratest
 * @category  test
 * @copyright 2014 The Open Univsersity
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/pratest/locallib.php');
require_once($CFG->dirroot . '/mod/pratest/classes/repaginate.php');


/**
 * Testable subclass, giving access to the protected methods of {@link \mod_pratest\repaginate}
 * @copyright 2014 The Open Univsersity
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pratest_repaginate_testable extends \mod_pratest\repaginate {

    public function __construct($pratestid = 0, $slots = null) {
        return parent::__construct($pratestid, $slots);
    }
    public function get_this_slot($slots, $slotnumber) {
        return parent::get_this_slot($slots, $slotnumber);
    }
    public function get_slots_by_slotid($slots = null) {
        return parent::get_slots_by_slotid($slots);
    }
    public function get_slots_by_slot_number($slots = null) {
        return parent::get_slots_by_slot_number($slots);
    }
    public function repaginate_this_slot($slot, $newpagenumber) {
        return parent::repaginate_this_slot($slot, $newpagenumber);
    }
    public function repaginate_next_slot($nextslotnumber, $type) {
        return parent::repaginate_next_slot($nextslotnumber, $type);
    }
}

/**
 * Test for some parts of the repaginate class.
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pratest_repaginate_test extends advanced_testcase {

    /** @var array stores the slots. */
    private $pratestslots;
    /** @var mod_pratest_repaginate_testable the object being tested. */
    private $repaginate = null;

    public function setUp() {
        $this->set_pratest_slots($this->get_pratest_object()->get_slots());
        $this->repaginate = new mod_pratest_repaginate_testable(0, $this->pratestslots);
    }

    public function tearDown() {
        $this->repaginate = null;
    }

    /**
     * Create a pratest, add five questions to the pratest
     * which are all on one page and return the pratest object.
     */
    private function get_pratest_object() {
        global $SITE;
        $this->resetAfterTest(true);

        // Make a pratest.
        $pratestgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pratest');

        $pratest = $pratestgenerator->create_instance(array(
                'course' => $SITE->id, 'questionsperpage' => 0, 'grade' => 100.0, 'sumgrades' => 2));
        $cm = get_coursemodule_from_instance('pratest', $pratest->id, $SITE->id);

        // Create five questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $shortanswer = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        $numerical = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        $essay = $questiongenerator->create_question('essay', null, array('category' => $cat->id));
        $truefalse = $questiongenerator->create_question('truefalse', null, array('category' => $cat->id));
        $match = $questiongenerator->create_question('match', null, array('category' => $cat->id));

        // Add them to the pratest.
        pratest_add_pratest_question($shortanswer->id, $pratest);
        pratest_add_pratest_question($numerical->id, $pratest);
        pratest_add_pratest_question($essay->id, $pratest);
        pratest_add_pratest_question($truefalse->id, $pratest);
        pratest_add_pratest_question($match->id, $pratest);

        // Return the pratest object.
        $pratestobj = new pratest($pratest, $cm, $SITE);
        return \mod_pratest\structure::create_for_pratest($pratestobj);
    }

    /**
     * Set the pratest slots
     * @param string $slots
     */
    private function set_pratest_slots($slots = null) {
        if (!$slots) {
            $this->pratestslots = $this->get_pratest_object()->get_slots();
        } else {
            $this->pratestslots = $slots;
        }
    }

    /**
     * Test the get_this_slot() method
     */
    public function test_get_this_slot() {
        $this->set_pratest_slots();
        $actual = array();
        $expected = $this->repaginate->get_slots_by_slot_number();
        $this->assertEquals($expected, $actual);

        $slotsbyno = $this->repaginate->get_slots_by_slot_number($this->pratestslots);
        $slotnumber = 5;
        $thisslot = $this->repaginate->get_this_slot($this->pratestslots, $slotnumber);
        $this->assertEquals($slotsbyno[$slotnumber], $thisslot);
    }

    public function test_get_slots_by_slotnumber() {
        $this->set_pratest_slots();
        $expected = array();
        $actual = $this->repaginate->get_slots_by_slot_number();
        $this->assertEquals($expected, $actual);

        foreach ($this->pratestslots as $slot) {
            $expected[$slot->slot] = $slot;
        }
        $actual = $this->repaginate->get_slots_by_slot_number($this->pratestslots);
        $this->assertEquals($expected, $actual);
    }

    public function test_get_slots_by_slotid() {
        $this->set_pratest_slots();
        $actual = $this->repaginate->get_slots_by_slotid();
        $this->assertEquals(array(), $actual);

        $slotsbyno = $this->repaginate->get_slots_by_slot_number($this->pratestslots);
        $actual = $this->repaginate->get_slots_by_slotid($slotsbyno);
        $this->assertEquals($this->pratestslots, $actual);
    }

    public function test_repaginate_n_questions_per_page() {
        $this->set_pratest_slots();

        // Expect 2 questions per page.
        $expected = array();
        foreach ($this->pratestslots as $slot) {
            // Page 1 contains Slots 1 and 2.
            if ($slot->slot >= 1 && $slot->slot <= 2) {
                $slot->page = 1;
            }
            // Page 2 contains slots 3 and 4.
            if ($slot->slot >= 3 && $slot->slot <= 4) {
                $slot->page = 2;
            }
            // Page 3 contains slots 5.
            if ($slot->slot >= 5 && $slot->slot <= 6) {
                $slot->page = 3;
            }
            $expected[$slot->id] = $slot;
        }
        $actual = $this->repaginate->repaginate_n_question_per_page($this->pratestslots, 2);
        $this->assertEquals($expected, $actual);

        // Expect 3 questions per page.
        $expected = array();
        foreach ($this->pratestslots as $slot) {
            // Page 1 contains Slots 1, 2 and 3.
            if ($slot->slot >= 1 && $slot->slot <= 3) {
                $slot->page = 1;
            }
            // Page 2 contains slots 4 and 5.
            if ($slot->slot >= 4 && $slot->slot <= 6) {
                $slot->page = 2;
            }
            $expected[$slot->id] = $slot;
        }
        $actual = $this->repaginate->repaginate_n_question_per_page($this->pratestslots, 3);
        $this->assertEquals($expected, $actual);

        // Expect 5 questions per page.
        $expected = array();
        foreach ($this->pratestslots as $slot) {
            // Page 1 contains Slots 1, 2, 3, 4 and 5.
            if ($slot->slot > 0 && $slot->slot < 6) {
                $slot->page = 1;
            }
            // Page 2 contains slots 6, 7, 8, 9 and 10.
            if ($slot->slot > 5 && $slot->slot < 11) {
                $slot->page = 2;
            }
            $expected[$slot->id] = $slot;
        }
        $actual = $this->repaginate->repaginate_n_question_per_page($this->pratestslots, 5);
        $this->assertEquals($expected, $actual);

        // Expect 10 questions per page.
        $expected = array();
        foreach ($this->pratestslots as $slot) {
            // Page 1 contains Slots 1 to 10.
            if ($slot->slot >= 1 && $slot->slot <= 10) {
                $slot->page = 1;
            }
            // Page 2 contains slots 11 to 20.
            if ($slot->slot >= 11 && $slot->slot <= 20) {
                $slot->page = 2;
            }
            $expected[$slot->id] = $slot;
        }
        $actual = $this->repaginate->repaginate_n_question_per_page($this->pratestslots, 10);
        $this->assertEquals($expected, $actual);

        // Expect 1 questions per page.
        $expected = array();
        $page = 1;
        foreach ($this->pratestslots as $slot) {
            $slot->page = $page++;
            $expected[$slot->id] = $slot;
        }
        $actual = $this->repaginate->repaginate_n_question_per_page($this->pratestslots, 1);
        $this->assertEquals($expected, $actual);
    }

    public function test_repaginate_this_slot() {
        $this->set_pratest_slots();
        $slotsbyslotno = $this->repaginate->get_slots_by_slot_number($this->pratestslots);
        $slotnumber = 3;
        $newpagenumber = 2;
        $thisslot = $slotsbyslotno[3];
        $thisslot->page = $newpagenumber;
        $expected = $thisslot;
        $actual = $this->repaginate->repaginate_this_slot($slotsbyslotno[3], $newpagenumber);
        $this->assertEquals($expected, $actual);
    }

    public function test_repaginate_the_rest() {
        $this->set_pratest_slots();
        $slotfrom = 1;
        $type = \mod_pratest\repaginate::LINK;
        $expected = array();
        foreach ($this->pratestslots as $slot) {
            if ($slot->slot > $slotfrom) {
                $slot->page = $slot->page - 1;
                $expected[$slot->id] = $slot;
            }
        }
        $actual = $this->repaginate->repaginate_the_rest($this->pratestslots, $slotfrom, $type, false);
        $this->assertEquals($expected, $actual);

        $slotfrom = 2;
        $newslots = array();
        foreach ($this->pratestslots as $s) {
            if ($s->slot === $slotfrom) {
                $s->page = $s->page - 1;
            }
            $newslots[$s->id] = $s;
        }

        $type = \mod_pratest\repaginate::UNLINK;
        $expected = array();
        foreach ($this->pratestslots as $slot) {
            if ($slot->slot > ($slotfrom - 1)) {
                $slot->page = $slot->page - 1;
                $expected[$slot->id] = $slot;
            }
        }
        $actual = $this->repaginate->repaginate_the_rest($newslots, $slotfrom, $type, false);
        $this->assertEquals($expected, $actual);
    }

}
