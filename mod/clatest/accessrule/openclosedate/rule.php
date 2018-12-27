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
 * Implementaton of the clatestaccess_openclosedate plugin.
 *
 * @package    clatestaccess
 * @subpackage openclosedate
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/clatest/accessrule/accessrulebase.php');


/**
 * A rule enforcing open and close dates.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clatestaccess_openclosedate extends clatest_access_rule_base {

    public static function make(clatest $clatestobj, $timenow, $canignoretimelimits) {
        // This rule is always used, even if the clatest has no open or close date.
        return new self($clatestobj, $timenow);
    }

    public function description() {
        $result = array();
        if ($this->timenow < $this->clatest->timeopen) {
            $result[] = get_string('clatestnotavailable', 'clatestaccess_openclosedate',
                    userdate($this->clatest->timeopen));
            if ($this->clatest->timeclose) {
                $result[] = get_string('clatestcloseson', 'clatest', userdate($this->clatest->timeclose));
            }

        } else if ($this->clatest->timeclose && $this->timenow > $this->clatest->timeclose) {
            $result[] = get_string('clatestclosed', 'clatest', userdate($this->clatest->timeclose));

        } else {
            if ($this->clatest->timeopen) {
                $result[] = get_string('clatestopenedon', 'clatest', userdate($this->clatest->timeopen));
            }
            if ($this->clatest->timeclose) {
                $result[] = get_string('clatestcloseson', 'clatest', userdate($this->clatest->timeclose));
            }
        }

        return $result;
    }

    public function prevent_access() {
        $message = get_string('notavailable', 'clatestaccess_openclosedate');

        if ($this->timenow < $this->clatest->timeopen) {
            return $message;
        }

        if (!$this->clatest->timeclose) {
            return false;
        }

        if ($this->timenow <= $this->clatest->timeclose) {
            return false;
        }

        if ($this->clatest->overduehandling != 'graceperiod') {
            return $message;
        }

        if ($this->timenow <= $this->clatest->timeclose + $this->clatest->graceperiod) {
            return false;
        }

        return $message;
    }

    public function is_finished($numprevattempts, $lastattempt) {
        return $this->clatest->timeclose && $this->timenow > $this->clatest->timeclose;
    }

    public function end_time($attempt) {
        if ($this->clatest->timeclose) {
            return $this->clatest->timeclose;
        }
        return false;
    }

    public function time_left_display($attempt, $timenow) {
        // If this is a teacher preview after the close date, do not show
        // the time.
        if ($attempt->preview && $timenow > $this->clatest->timeclose) {
            return false;
        }
        // Otherwise, return to the time left until the close date, providing that is
        // less than QUIZ_SHOW_TIME_BEFORE_DEADLINE.
        $endtime = $this->end_time($attempt);
        if ($endtime !== false && $timenow > $endtime - QUIZ_SHOW_TIME_BEFORE_DEADLINE) {
            return $endtime - $timenow;
        }
        return false;
    }
}
