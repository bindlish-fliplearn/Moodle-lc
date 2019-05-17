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
 * Add the discount for paid class.
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * class add pricing scheme form
 * @copyright Dualcube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class addpricingscheme_form extends moodleform {
    /**
     * Define add discount form
     */
    public function definition() {
        global $CFG, $DB, $bcid, $action, $pid, $pricelists;

        $defaultprice      = '';
        $defaultschemeday   = '';
        $defaultaccesstype  = 0;
        $defaultnumbertimes = '';
        if ($action == 'edit') {
            if (!empty($pricelists)) {
                if (!isset($pricelists['Price'])) {
                    foreach ($pricelists as $pricelist) {
                        if (isset($pricelist['id'])) {
                            if ($pricelist['id'] == $pid) {
                                $defaultprice      = $pricelist['scheme_price'];
                                $defaultschemeday   = $pricelist['scheme_days'];
                                $defaultaccesstype  = $pricelist['times'];
                                $defaultnumbertimes = $pricelist['numbertimes'];
                            }
                        }
                    }
                } else if (isset($pricelists['status']) && ($pricelists['status'] == 'error')) {
                    echo $pricelists['error'];
                }
            }
        }

        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement('hidden', 'pid', $pid);
        $mform->setType('pid', PARAM_INT);

        $mform->addElement('text', 'price', get_string('price', 'braincert'));
        $mform->setType('price', PARAM_INT);
        $mform->addRule('price', null, 'required', null, 'client');
        $mform->addRule('price', '', 'numeric', null, 'client');
        $mform->setDefault('price', $defaultprice);

        $mform->addElement('text', 'schemedays', get_string('schemedays', 'braincert'));
        $mform->setType('schemedays', PARAM_INT);
        $mform->addRule('schemedays', null, 'required', null, 'client');
        $mform->addRule('schemedays', '', 'numeric', null, 'client');
        $mform->setDefault('schemedays', $defaultschemeday);

        $accesstype = array();
        $accesstype[] = $mform->createElement('radio', 'accesstype', '', get_string('unlimited', 'braincert'), 0);
        $accesstype[] = $mform->createElement('radio', 'accesstype', '', get_string('limited', 'braincert'), 1);
        $mform->addGroup($accesstype, 'access_type', get_string('accesstype', 'braincert'), array(' '), false);
        $mform->setDefault('accesstype', $defaultaccesstype);

        $mform->addElement('text', 'numbertimes', get_string('numbertimes', 'braincert'));
        $mform->setType('numbertimes', PARAM_INT);
        $mform->addRule('numbertimes', '', 'numeric', null, 'client');
        $mform->disabledIf('numbertimes', 'accesstype', 'checked', 0);
        $mform->setDefault('numbertimes', $defaultnumbertimes);

        $this->add_action_buttons();
    }

    /**
     * validation check
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        return array();
    }
}