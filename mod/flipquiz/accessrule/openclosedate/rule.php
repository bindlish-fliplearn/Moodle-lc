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
 * Implementaton of the flipquizaccess_openclosedate plugin.
 *
 * @package    flipquizaccess
 * @subpackage openclosedate
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/flipquiz/accessrule/accessrulebase.php');


/**
 * A rule enforcing open and close dates.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flipquizaccess_openclosedate extends flipquiz_access_rule_base {

    public static function make(flipquiz $flipquizobj, $timenow, $canignoretimelimits) {
        // This rule is always used, even if the flipquiz has no open or close date.
        return new self($flipquizobj, $timenow);
    }

    public function description() {
        $result = array();
        if ($this->timenow < $this->flipquiz->timeopen) {
            $result[] = get_string('flipquiznotavailable', 'flipquizaccess_openclosedate',
                    userdate($this->flipquiz->timeopen));
            if ($this->flipquiz->timeclose) {
                $result[] = get_string('flipquizcloseson', 'flipquiz', userdate($this->flipquiz->timeclose));
            }

        } else if ($this->flipquiz->timeclose && $this->timenow > $this->flipquiz->timeclose) {
            $result[] = get_string('flipquizclosed', 'flipquiz', userdate($this->flipquiz->timeclose));

        } else {
            if ($this->flipquiz->timeopen) {
                $result[] = get_string('flipquizopenedon', 'flipquiz', userdate($this->flipquiz->timeopen));
            }
            if ($this->flipquiz->timeclose) {
                $result[] = get_string('flipquizcloseson', 'flipquiz', userdate($this->flipquiz->timeclose));
            }
        }

        return $result;
    }

    public function prevent_access() {
        $message = get_string('notavailable', 'flipquizaccess_openclosedate');

        if ($this->timenow < $this->flipquiz->timeopen) {
            return $message;
        }

        if (!$this->flipquiz->timeclose) {
            return false;
        }

        if ($this->timenow <= $this->flipquiz->timeclose) {
            return false;
        }

        if ($this->flipquiz->overduehandling != 'graceperiod') {
            return $message;
        }

        if ($this->timenow <= $this->flipquiz->timeclose + $this->flipquiz->graceperiod) {
            return false;
        }

        return $message;
    }

    public function is_finished($numprevattempts, $lastattempt) {
        return $this->flipquiz->timeclose && $this->timenow > $this->flipquiz->timeclose;
    }

    public function end_time($attempt) {
        if ($this->flipquiz->timeclose) {
            return $this->flipquiz->timeclose;
        }
        return false;
    }

    public function time_left_display($attempt, $timenow) {
        // If this is a teacher preview after the close date, do not show
        // the time.
        if ($attempt->preview && $timenow > $this->flipquiz->timeclose) {
            return false;
        }
        // Otherwise, return to the time left until the close date, providing that is
        // less than FLIPQUIZ_SHOW_TIME_BEFORE_DEADLINE.
        $endtime = $this->end_time($attempt);
        if ($endtime !== false && $timenow > $endtime - FLIPQUIZ_SHOW_TIME_BEFORE_DEADLINE) {
            return $endtime - $timenow;
        }
        return false;
    }
}
