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
 * Unit tests for the pratestaccess_openclosedate plugin.
 *
 * @package    pratestaccess
 * @subpackage openclosedate
 * @category   phpunit
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/pratest/accessrule/openclosedate/rule.php');


/**
 * Unit tests for the pratestaccess_openclosedate plugin.
 *
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pratestaccess_openclosedate_testcase extends basic_testcase {
    public function test_no_dates() {
        $pratest = new stdClass();
        $pratest->timeopen = 0;
        $pratest->timeclose = 0;
        $pratest->overduehandling = 'autosubmit';
        $cm = new stdClass();
        $cm->id = 0;
        $pratestobj = new pratest($pratest, $cm, null);
        $attempt = new stdClass();
        $attempt->preview = 0;

        $rule = new pratestaccess_openclosedate($pratestobj, 10000);
        $this->assertEmpty($rule->description());
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));
        $this->assertFalse($rule->end_time($attempt));
        $this->assertFalse($rule->time_left_display($attempt, 10000));
        $this->assertFalse($rule->time_left_display($attempt, 0));

        $rule = new pratestaccess_openclosedate($pratestobj, 0);
        $this->assertEmpty($rule->description());
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));
        $this->assertFalse($rule->end_time($attempt));
        $this->assertFalse($rule->time_left_display($attempt, 0));
    }

    public function test_start_date() {
        $pratest = new stdClass();
        $pratest->timeopen = 10000;
        $pratest->timeclose = 0;
        $pratest->overduehandling = 'autosubmit';
        $cm = new stdClass();
        $cm->id = 0;
        $pratestobj = new pratest($pratest, $cm, null);
        $attempt = new stdClass();
        $attempt->preview = 0;

        $rule = new pratestaccess_openclosedate($pratestobj, 9999);
        $this->assertEquals($rule->description(),
            array(get_string('pratestnotavailable', 'pratestaccess_openclosedate', userdate(10000))));
        $this->assertEquals($rule->prevent_access(),
            get_string('notavailable', 'pratestaccess_openclosedate'));
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));
        $this->assertFalse($rule->end_time($attempt));
        $this->assertFalse($rule->time_left_display($attempt, 0));

        $rule = new pratestaccess_openclosedate($pratestobj, 10000);
        $this->assertEquals($rule->description(),
            array(get_string('pratestopenedon', 'pratest', userdate(10000))));
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));
        $this->assertFalse($rule->end_time($attempt));
        $this->assertFalse($rule->time_left_display($attempt, 0));
    }

    public function test_close_date() {
        $pratest = new stdClass();
        $pratest->timeopen = 0;
        $pratest->timeclose = 20000;
        $pratest->overduehandling = 'autosubmit';
        $cm = new stdClass();
        $cm->id = 0;
        $pratestobj = new pratest($pratest, $cm, null);
        $attempt = new stdClass();
        $attempt->preview = 0;

        $rule = new pratestaccess_openclosedate($pratestobj, 20000);
        $this->assertEquals($rule->description(),
            array(get_string('pratestcloseson', 'pratest', userdate(20000))));
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));

        $this->assertEquals($rule->end_time($attempt), 20000);
        $this->assertFalse($rule->time_left_display($attempt, 20000 - QUIZ_SHOW_TIME_BEFORE_DEADLINE));
        $this->assertEquals($rule->time_left_display($attempt, 19900), 100);
        $this->assertEquals($rule->time_left_display($attempt, 20000), 0);
        $this->assertEquals($rule->time_left_display($attempt, 20100), -100);

        $rule = new pratestaccess_openclosedate($pratestobj, 20001);
        $this->assertEquals($rule->description(),
            array(get_string('pratestclosed', 'pratest', userdate(20000))));
        $this->assertEquals($rule->prevent_access(),
            get_string('notavailable', 'pratestaccess_openclosedate'));
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertTrue($rule->is_finished(0, $attempt));
        $this->assertEquals($rule->end_time($attempt), 20000);
        $this->assertFalse($rule->time_left_display($attempt, 20000 - QUIZ_SHOW_TIME_BEFORE_DEADLINE));
        $this->assertEquals($rule->time_left_display($attempt, 19900), 100);
        $this->assertEquals($rule->time_left_display($attempt, 20000), 0);
        $this->assertEquals($rule->time_left_display($attempt, 20100), -100);
    }

    public function test_both_dates() {
        $pratest = new stdClass();
        $pratest->timeopen = 10000;
        $pratest->timeclose = 20000;
        $pratest->overduehandling = 'autosubmit';
        $cm = new stdClass();
        $cm->id = 0;
        $pratestobj = new pratest($pratest, $cm, null);
        $attempt = new stdClass();
        $attempt->preview = 0;

        $rule = new pratestaccess_openclosedate($pratestobj, 9999);
        $this->assertEquals($rule->description(),
            array(get_string('pratestnotavailable', 'pratestaccess_openclosedate', userdate(10000)),
                    get_string('pratestcloseson', 'pratest', userdate(20000))));
        $this->assertEquals($rule->prevent_access(),
            get_string('notavailable', 'pratestaccess_openclosedate'));
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));

        $rule = new pratestaccess_openclosedate($pratestobj, 10000);
        $this->assertEquals($rule->description(),
            array(get_string('pratestopenedon', 'pratest', userdate(10000)),
                get_string('pratestcloseson', 'pratest', userdate(20000))));
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));

        $rule = new pratestaccess_openclosedate($pratestobj, 20000);
        $this->assertEquals($rule->description(),
            array(get_string('pratestopenedon', 'pratest', userdate(10000)),
                get_string('pratestcloseson', 'pratest', userdate(20000))));
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));

        $rule = new pratestaccess_openclosedate($pratestobj, 20001);
        $this->assertEquals($rule->description(),
            array(get_string('pratestclosed', 'pratest', userdate(20000))));
        $this->assertEquals($rule->prevent_access(),
            get_string('notavailable', 'pratestaccess_openclosedate'));
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertTrue($rule->is_finished(0, $attempt));

        $this->assertEquals($rule->end_time($attempt), 20000);
        $this->assertFalse($rule->time_left_display($attempt, 20000 - QUIZ_SHOW_TIME_BEFORE_DEADLINE));
        $this->assertEquals($rule->time_left_display($attempt, 19900), 100);
        $this->assertEquals($rule->time_left_display($attempt, 20000), 0);
        $this->assertEquals($rule->time_left_display($attempt, 20100), -100);
    }

    public function test_close_date_with_overdue() {
        $pratest = new stdClass();
        $pratest->timeopen = 0;
        $pratest->timeclose = 20000;
        $pratest->overduehandling = 'graceperiod';
        $pratest->graceperiod = 1000;
        $cm = new stdClass();
        $cm->id = 0;
        $pratestobj = new pratest($pratest, $cm, null);
        $attempt = new stdClass();
        $attempt->preview = 0;

        $rule = new pratestaccess_openclosedate($pratestobj, 20000);
        $this->assertFalse($rule->prevent_access());

        $rule = new pratestaccess_openclosedate($pratestobj, 20001);
        $this->assertFalse($rule->prevent_access());

        $rule = new pratestaccess_openclosedate($pratestobj, 21000);
        $this->assertFalse($rule->prevent_access());

        $rule = new pratestaccess_openclosedate($pratestobj, 21001);
        $this->assertEquals($rule->prevent_access(),
                get_string('notavailable', 'pratestaccess_openclosedate'));
    }
}
