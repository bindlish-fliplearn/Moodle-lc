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
 * Unit tests for the clatestaccess_openclosedate plugin.
 *
 * @package    clatestaccess
 * @subpackage openclosedate
 * @category   phpunit
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/clatest/accessrule/openclosedate/rule.php');


/**
 * Unit tests for the clatestaccess_openclosedate plugin.
 *
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clatestaccess_openclosedate_testcase extends basic_testcase {
    public function test_no_dates() {
        $clatest = new stdClass();
        $clatest->timeopen = 0;
        $clatest->timeclose = 0;
        $clatest->overduehandling = 'autosubmit';
        $cm = new stdClass();
        $cm->id = 0;
        $clatestobj = new clatest($clatest, $cm, null);
        $attempt = new stdClass();
        $attempt->preview = 0;

        $rule = new clatestaccess_openclosedate($clatestobj, 10000);
        $this->assertEmpty($rule->description());
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));
        $this->assertFalse($rule->end_time($attempt));
        $this->assertFalse($rule->time_left_display($attempt, 10000));
        $this->assertFalse($rule->time_left_display($attempt, 0));

        $rule = new clatestaccess_openclosedate($clatestobj, 0);
        $this->assertEmpty($rule->description());
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));
        $this->assertFalse($rule->end_time($attempt));
        $this->assertFalse($rule->time_left_display($attempt, 0));
    }

    public function test_start_date() {
        $clatest = new stdClass();
        $clatest->timeopen = 10000;
        $clatest->timeclose = 0;
        $clatest->overduehandling = 'autosubmit';
        $cm = new stdClass();
        $cm->id = 0;
        $clatestobj = new clatest($clatest, $cm, null);
        $attempt = new stdClass();
        $attempt->preview = 0;

        $rule = new clatestaccess_openclosedate($clatestobj, 9999);
        $this->assertEquals($rule->description(),
            array(get_string('clatestnotavailable', 'clatestaccess_openclosedate', userdate(10000))));
        $this->assertEquals($rule->prevent_access(),
            get_string('notavailable', 'clatestaccess_openclosedate'));
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));
        $this->assertFalse($rule->end_time($attempt));
        $this->assertFalse($rule->time_left_display($attempt, 0));

        $rule = new clatestaccess_openclosedate($clatestobj, 10000);
        $this->assertEquals($rule->description(),
            array(get_string('clatestopenedon', 'clatest', userdate(10000))));
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));
        $this->assertFalse($rule->end_time($attempt));
        $this->assertFalse($rule->time_left_display($attempt, 0));
    }

    public function test_close_date() {
        $clatest = new stdClass();
        $clatest->timeopen = 0;
        $clatest->timeclose = 20000;
        $clatest->overduehandling = 'autosubmit';
        $cm = new stdClass();
        $cm->id = 0;
        $clatestobj = new clatest($clatest, $cm, null);
        $attempt = new stdClass();
        $attempt->preview = 0;

        $rule = new clatestaccess_openclosedate($clatestobj, 20000);
        $this->assertEquals($rule->description(),
            array(get_string('clatestcloseson', 'clatest', userdate(20000))));
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));

        $this->assertEquals($rule->end_time($attempt), 20000);
        $this->assertFalse($rule->time_left_display($attempt, 20000 - QUIZ_SHOW_TIME_BEFORE_DEADLINE));
        $this->assertEquals($rule->time_left_display($attempt, 19900), 100);
        $this->assertEquals($rule->time_left_display($attempt, 20000), 0);
        $this->assertEquals($rule->time_left_display($attempt, 20100), -100);

        $rule = new clatestaccess_openclosedate($clatestobj, 20001);
        $this->assertEquals($rule->description(),
            array(get_string('clatestclosed', 'clatest', userdate(20000))));
        $this->assertEquals($rule->prevent_access(),
            get_string('notavailable', 'clatestaccess_openclosedate'));
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertTrue($rule->is_finished(0, $attempt));
        $this->assertEquals($rule->end_time($attempt), 20000);
        $this->assertFalse($rule->time_left_display($attempt, 20000 - QUIZ_SHOW_TIME_BEFORE_DEADLINE));
        $this->assertEquals($rule->time_left_display($attempt, 19900), 100);
        $this->assertEquals($rule->time_left_display($attempt, 20000), 0);
        $this->assertEquals($rule->time_left_display($attempt, 20100), -100);
    }

    public function test_both_dates() {
        $clatest = new stdClass();
        $clatest->timeopen = 10000;
        $clatest->timeclose = 20000;
        $clatest->overduehandling = 'autosubmit';
        $cm = new stdClass();
        $cm->id = 0;
        $clatestobj = new clatest($clatest, $cm, null);
        $attempt = new stdClass();
        $attempt->preview = 0;

        $rule = new clatestaccess_openclosedate($clatestobj, 9999);
        $this->assertEquals($rule->description(),
            array(get_string('clatestnotavailable', 'clatestaccess_openclosedate', userdate(10000)),
                    get_string('clatestcloseson', 'clatest', userdate(20000))));
        $this->assertEquals($rule->prevent_access(),
            get_string('notavailable', 'clatestaccess_openclosedate'));
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));

        $rule = new clatestaccess_openclosedate($clatestobj, 10000);
        $this->assertEquals($rule->description(),
            array(get_string('clatestopenedon', 'clatest', userdate(10000)),
                get_string('clatestcloseson', 'clatest', userdate(20000))));
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));

        $rule = new clatestaccess_openclosedate($clatestobj, 20000);
        $this->assertEquals($rule->description(),
            array(get_string('clatestopenedon', 'clatest', userdate(10000)),
                get_string('clatestcloseson', 'clatest', userdate(20000))));
        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));

        $rule = new clatestaccess_openclosedate($clatestobj, 20001);
        $this->assertEquals($rule->description(),
            array(get_string('clatestclosed', 'clatest', userdate(20000))));
        $this->assertEquals($rule->prevent_access(),
            get_string('notavailable', 'clatestaccess_openclosedate'));
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertTrue($rule->is_finished(0, $attempt));

        $this->assertEquals($rule->end_time($attempt), 20000);
        $this->assertFalse($rule->time_left_display($attempt, 20000 - QUIZ_SHOW_TIME_BEFORE_DEADLINE));
        $this->assertEquals($rule->time_left_display($attempt, 19900), 100);
        $this->assertEquals($rule->time_left_display($attempt, 20000), 0);
        $this->assertEquals($rule->time_left_display($attempt, 20100), -100);
    }

    public function test_close_date_with_overdue() {
        $clatest = new stdClass();
        $clatest->timeopen = 0;
        $clatest->timeclose = 20000;
        $clatest->overduehandling = 'graceperiod';
        $clatest->graceperiod = 1000;
        $cm = new stdClass();
        $cm->id = 0;
        $clatestobj = new clatest($clatest, $cm, null);
        $attempt = new stdClass();
        $attempt->preview = 0;

        $rule = new clatestaccess_openclosedate($clatestobj, 20000);
        $this->assertFalse($rule->prevent_access());

        $rule = new clatestaccess_openclosedate($clatestobj, 20001);
        $this->assertFalse($rule->prevent_access());

        $rule = new clatestaccess_openclosedate($clatestobj, 21000);
        $this->assertFalse($rule->prevent_access());

        $rule = new clatestaccess_openclosedate($clatestobj, 21001);
        $this->assertEquals($rule->prevent_access(),
                get_string('notavailable', 'clatestaccess_openclosedate'));
    }
}
