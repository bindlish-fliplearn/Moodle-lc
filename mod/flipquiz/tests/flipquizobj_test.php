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
 * Unit tests for the flipquiz class.
 *
 * @package   mod_flipquiz
 * @copyright 2008 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');


/**
 * Unit tests for the flipquiz class
 *
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_flipquiz_class_testcase extends basic_testcase {
    public function test_cannot_review_message() {
        $flipquiz = new stdClass();
        $flipquiz->reviewattempt = 0x10010;
        $flipquiz->timeclose = 0;
        $flipquiz->attempts = 0;

        $cm = new stdClass();
        $cm->id = 123;

        $flipquizobj = new flipquiz($flipquiz, $cm, new stdClass(), false);

        $this->assertEquals('',
            $flipquizobj->cannot_review_message(mod_flipquiz_display_options::DURING));
        $this->assertEquals('',
            $flipquizobj->cannot_review_message(mod_flipquiz_display_options::IMMEDIATELY_AFTER));
        $this->assertEquals(get_string('noreview', 'flipquiz'),
            $flipquizobj->cannot_review_message(mod_flipquiz_display_options::LATER_WHILE_OPEN));
        $this->assertEquals(get_string('noreview', 'flipquiz'),
            $flipquizobj->cannot_review_message(mod_flipquiz_display_options::AFTER_CLOSE));

        $closetime = time() + 10000;
        $flipquiz->timeclose = $closetime;
        $flipquizobj = new flipquiz($flipquiz, $cm, new stdClass(), false);

        $this->assertEquals(get_string('noreviewuntil', 'flipquiz', userdate($closetime)),
            $flipquizobj->cannot_review_message(mod_flipquiz_display_options::LATER_WHILE_OPEN));
    }
}
