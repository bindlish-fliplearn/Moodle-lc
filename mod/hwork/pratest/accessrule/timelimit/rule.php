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
 * Implementaton of the pratestaccess_timelimit plugin.
 *
 * @package    pratestaccess
 * @subpackage timelimit
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pratest/accessrule/accessrulebase.php');


/**
 * A rule representing the time limit. It does not actually restrict access, but we use this
 * class to encapsulate some of the relevant code.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pratestaccess_timelimit extends pratest_access_rule_base {

    public static function make(pratest $pratestobj, $timenow, $canignoretimelimits) {

        if (empty($pratestobj->get_pratest()->timelimit) || $canignoretimelimits) {
            return null;
        }

        return new self($pratestobj, $timenow);
    }

    public function description() {
        return get_string('pratesttimelimit', 'pratestaccess_timelimit',
                format_time($this->pratest->timelimit));
    }

    public function end_time($attempt) {
        return $attempt->timestart + $this->pratest->timelimit;
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

    public function add_preflight_check_form_fields(mod_pratest_preflight_check_form $pratestform,
            MoodleQuickForm $mform, $attemptid) {
        $mform->addElement('header', 'honestycheckheader',
                get_string('confirmstartheader', 'pratestaccess_timelimit'));
        $mform->addElement('static', 'honestycheckmessage', '',
                get_string('confirmstart', 'pratestaccess_timelimit', format_time($this->pratest->timelimit)));
    }
}
