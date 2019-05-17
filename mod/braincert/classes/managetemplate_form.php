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
 * Manage e-mail template form.
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * class add managetemplate_form
 * @copyright Dualcube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class managetemplate_form extends moodleform {
    /**
     * Define add discount form
     */
    public function definition() {

        global $DB, $bcid;
        $getbody = $DB->get_record('braincert_manage_template', array('bcid' => $bcid));
        if ($getbody) {
            $s = $getbody->emailsubject;
            $m = $getbody->emailmessage;
        } else {
            $s = get_string('liveclassinvitationsubject', 'braincert');
            $m = get_string('liveclassinvitationmessage', 'braincert');
        }
        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('text', 'emailsubject', get_string('emailsubject', 'braincert'));
        $mform->setType('emailsubject', PARAM_RAW);
        $mform->setDefault('emailsubject', $s);
        $mform->addElement('editor', 'emailmessage', get_string('emailmessage', 'braincert'))->setValue(array('text' => $m));
        $mform->setType('emailmessage', PARAM_RAW);
        $this->add_action_buttons();
    }
}