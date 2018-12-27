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
 * Implementaton of the pratestaccess_openclosedate plugin.
 *
 * @package    pratestaccess
 * @subpackage openclosedate
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pratest/accessrule/accessrulebase.php');


/**
 * A rule enforcing open and close dates.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pratestaccess_openclosedate extends pratest_access_rule_base {

    public static function make(pratest $pratestobj, $timenow, $canignoretimelimits) {
        // This rule is always used, even if the pratest has no open or close date.
        return new self($pratestobj, $timenow);
    }

    public function description() {
        $result = array();
        if ($this->timenow < $this->pratest->timeopen) {
            $result[] = get_string('pratestnotavailable', 'pratestaccess_openclosedate',
                    userdate($this->pratest->timeopen));
            if ($this->pratest->timeclose) {
                $result[] = get_string('pratestcloseson', 'pratest', userdate($this->pratest->timeclose));
            }

        } else if ($this->pratest->timeclose && $this->timenow > $this->pratest->timeclose) {
            $result[] = get_string('pratestclosed', 'pratest', userdate($this->pratest->timeclose));

        } else {
            if ($this->pratest->timeopen) {
                $result[] = get_string('pratestopenedon', 'pratest', userdate($this->pratest->timeopen));
            }
            if ($this->pratest->timeclose) {
                $result[] = get_string('pratestcloseson', 'pratest', userdate($this->pratest->timeclose));
            }
        }

        return $result;
    }

    public function prevent_access() {
        $message = get_string('notavailable', 'pratestaccess_openclosedate');

        if ($this->timenow < $this->pratest->timeopen) {
            return $message;
        }

        if (!$this->pratest->timeclose) {
            return false;
        }

        if ($this->timenow <= $this->pratest->timeclose) {
            return false;
        }

        if ($this->pratest->overduehandling != 'graceperiod') {
            return $message;
        }

        if ($this->timenow <= $this->pratest->timeclose + $this->pratest->graceperiod) {
            return false;
        }

        return $message;
    }

    public function is_finished($numprevattempts, $lastattempt) {
        return $this->pratest->timeclose && $this->timenow > $this->pratest->timeclose;
    }

    public function end_time($attempt) {
        if ($this->pratest->timeclose) {
            return $this->pratest->timeclose;
        }
        return false;
    }

    public function time_left_display($attempt, $timenow) {
        // If this is a teacher preview after the close date, do not show
        // the time.
        if ($attempt->preview && $timenow > $this->pratest->timeclose) {
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
