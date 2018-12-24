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
 * Implementaton of the flipquizaccess_timelimit plugin.
 *
 * @package    flipquizaccess
 * @subpackage timelimit
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/flipquiz/accessrule/accessrulebase.php');


/**
 * A rule representing the time limit. It does not actually restrict access, but we use this
 * class to encapsulate some of the relevant code.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flipquizaccess_timelimit extends flipquiz_access_rule_base {

    public static function make(flipquiz $flipquizobj, $timenow, $canignoretimelimits) {

        if (empty($flipquizobj->get_flipquiz()->timelimit) || $canignoretimelimits) {
            return null;
        }

        return new self($flipquizobj, $timenow);
    }

    public function description() {
        return get_string('flipquiztimelimit', 'flipquizaccess_timelimit',
                format_time($this->flipquiz->timelimit));
    }

    public function end_time($attempt) {
        return $attempt->timestart + $this->flipquiz->timelimit;
    }

    public function time_left_display($attempt, $timenow) {
        // If this is a teacher preview after the time limit expires, don't show the time_left
        $endtime = $this->end_time($attempt);
        if ($attempt->preview && $timenow > $endtime) {
            return false;
        }
        return $endtime - $timenow;
    }

    public function is_preflight_check_required($attemptid) {
        // Warning only required if the attempt is not already started.
        return $attemptid === null;
    }

    public function add_preflight_check_form_fields(mod_flipquiz_preflight_check_form $flipquizform,
            MoodleQuickForm $mform, $attemptid) {
        $mform->addElement('header', 'honestycheckheader',
                get_string('confirmstartheader', 'flipquizaccess_timelimit'));
        $mform->addElement('static', 'honestycheckmessage', '',
                get_string('confirmstart', 'flipquizaccess_timelimit', format_time($this->flipquiz->timelimit)));
    }
}
