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
 * Implementaton of the hworkaccess_password plugin.
 *
 * @package    hworkaccess
 * @subpackage password
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/hwork/accessrule/accessrulebase.php');


/**
 * A rule implementing the password check.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hworkaccess_password extends hwork_access_rule_base {

    public static function make(hwork $hworkobj, $timenow, $canignoretimelimits) {
        if (empty($hworkobj->get_hwork()->password)) {
            return null;
        }

        return new self($hworkobj, $timenow);
    }

    public function description() {
        return get_string('requirepasswordmessage', 'hworkaccess_password');
    }

    public function is_preflight_check_required($attemptid) {
        global $SESSION;
        return empty($SESSION->passwordcheckedhworkzes[$this->hwork->id]);
    }

    public function add_preflight_check_form_fields(mod_hwork_preflight_check_form $hworkform,
            MoodleQuickForm $mform, $attemptid) {

        $mform->addElement('header', 'passwordheader', get_string('password'));
        $mform->addElement('static', 'passwordmessage', '',
                get_string('requirepasswordmessage', 'hworkaccess_password'));

        // Don't use the 'proper' field name of 'password' since that get's
        // Firefox's password auto-complete over-excited.
        $mform->addElement('password', 'hworkpassword',
                get_string('hworkpassword', 'hworkaccess_password'), array('autofocus' => 'true'));
    }

    public function validate_preflight_check($data, $files, $errors, $attemptid) {

        $enteredpassword = $data['hworkpassword'];
        if (strcmp($this->hwork->password, $enteredpassword) === 0) {
            return $errors; // Password is OK.

        } else if (isset($this->hwork->extrapasswords)) {
            // Group overrides may have additional passwords.
            foreach ($this->hwork->extrapasswords as $password) {
                if (strcmp($password, $enteredpassword) === 0) {
                    return $errors; // Password is OK.
                }
            }
        }

        $errors['hworkpassword'] = get_string('passworderror', 'hworkaccess_password');
        return $errors;
    }

    public function notify_preflight_check_passed($attemptid) {
        global $SESSION;
        $SESSION->passwordcheckedhworkzes[$this->hwork->id] = true;
    }

    public function current_attempt_finished() {
        global $SESSION;
        // Clear the flag in the session that says that the user has already
        // entered the password for this hwork.
        if (!empty($SESSION->passwordcheckedhworkzes[$this->hwork->id])) {
            unset($SESSION->passwordcheckedhworkzes[$this->hwork->id]);
        }
    }
}
