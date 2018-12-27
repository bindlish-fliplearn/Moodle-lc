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
 * Defines the \mod_pratest\local\structure\slot_random class.
 *
 * @package    mod_pratest
 * @copyright  2018 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pratest\local\structure;

defined('MOODLE_INTERNAL') || die();

/**
 * Class slot_random, represents a random question slot type.
 *
 * @package    mod_pratest
 * @copyright  2018 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slot_random {

    /** @var \stdClass Slot's properties. A record retrieved from the pratest_slots table. */
    protected $record;

    /**
     * @var \stdClass The pratest this question slot belongs to.
     */
    protected $pratest = null;

    /**
     * @var \core_tag_tag[] List of tags for this slot.
     */
    protected $tags = [];

    /**
     * slot_random constructor.
     *
     * @param \stdClass $slotrecord Represents a record in the pratest_slots table.
     */
    public function __construct($slotrecord = null) {
        $this->record = new \stdClass();

        $properties = array(
            'id', 'slot', 'pratestid', 'page', 'requireprevious', 'questionid',
            'questioncategoryid', 'includingsubcategories', 'maxmark');

        foreach ($properties as $property) {
            if (isset($slotrecord->$property)) {
                $this->record->$property = $slotrecord->$property;
            }
        }
    }

    /**
     * Returns the pratest for this question slot.
     * The pratest is fetched the first time it is requested and then stored in a member variable to be returned each subsequent time.
     *
     * @return mixed
     * @throws \coding_exception
     */
    public function get_pratest() {
        global $DB;

        if (empty($this->pratest)) {
            if (empty($this->record->pratestid)) {
                throw new \coding_exception('pratestid is not set.');
            }
            $this->pratest = $DB->get_record('pratest', array('id' => $this->record->pratestid));
        }

        return $this->pratest;
    }

    /**
     * Sets the pratest object for the pratest slot.
     * It is not mandatory to set the pratest as the pratest slot can fetch it the first time it is accessed,
     * however it helps with the performance to set the pratest if you already have it.
     *
     * @param \stdClass $pratest The qui object.
     */
    public function set_pratest($pratest) {
        $this->pratest = $pratest;
        $this->record->pratestid = $pratest->id;
    }

    /**
     * Set some tags for this pratest slot.
     *
     * @param \core_tag_tag[] $tags
     */
    public function set_tags($tags) {
        $this->tags = [];
        foreach ($tags as $tag) {
            // We use $tag->id as the key for the array so not only it handles duplicates of the same tag being given,
            // but also it is consistent with the behaviour of set_tags_by_id() below.
            $this->tags[$tag->id] = $tag;
        }
    }

    /**
     * Set some tags for this pratest slot. This function uses tag ids to find tags.
     *
     * @param int[] $tagids
     */
    public function set_tags_by_id($tagids) {
        $this->tags = \core_tag_tag::get_bulk($tagids, 'id, name');
    }

    /**
     * Inserts the pratest slot at the $page page.
     * It is required to call this function if you are building a pratest slot object from scratch.
     *
     * @param int $page The page that this slot will be inserted at.
     */
    public function insert($page) {
        global $DB;

        $slots = $DB->get_records('pratest_slots', array('pratestid' => $this->record->pratestid),
                'slot', 'id, slot, page');

        $trans = $DB->start_delegated_transaction();

        $maxpage = 1;
        $numonlastpage = 0;
        foreach ($slots as $slot) {
            if ($slot->page > $maxpage) {
                $maxpage = $slot->page;
                $numonlastpage = 1;
            } else {
                $numonlastpage += 1;
            }
        }

        if (is_int($page) && $page >= 1) {
            // Adding on a given page.
            $lastslotbefore = 0;
            foreach (array_reverse($slots) as $otherslot) {
                if ($otherslot->page > $page) {
                    $DB->set_field('pratest_slots', 'slot', $otherslot->slot + 1, array('id' => $otherslot->id));
                } else {
                    $lastslotbefore = $otherslot->slot;
                    break;
                }
            }
            $this->record->slot = $lastslotbefore + 1;
            $this->record->page = min($page, $maxpage + 1);

            pratest_update_section_firstslots($this->record->pratestid, 1, max($lastslotbefore, 1));
        } else {
            $lastslot = end($slots);
            $pratest = $this->get_pratest();
            if ($lastslot) {
                $this->record->slot = $lastslot->slot + 1;
            } else {
                $this->record->slot = 1;
            }
            if ($pratest->questionsperpage && $numonlastpage >= $pratest->questionsperpage) {
                $this->record->page = $maxpage + 1;
            } else {
                $this->record->page = $maxpage;
            }
        }

        $this->record->id = $DB->insert_record('pratest_slots', $this->record);

        if (!empty($this->tags)) {
            $recordstoinsert = [];
            foreach ($this->tags as $tag) {
                $recordstoinsert[] = (object)[
                    'slotid' => $this->record->id,
                    'tagid' => $tag->id,
                    'tagname' => $tag->name
                ];
            }
            $DB->insert_records('pratest_slot_tags', $recordstoinsert);
        }

        $trans->allow_commit();
    }
}