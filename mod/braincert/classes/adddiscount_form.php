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
 * Discount form for paid class.
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * class add discount form
 * @copyright Dualcube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adddiscount_form extends moodleform {
    /**
     * Define add discount form
     */
    public function definition() {
        global $CFG, $DB, $braincertrec, $bcid, $did, $action, $discountlists;
        $isusecode        = 0;
        $defaultlimit     = '';
        $defaultcode      = '';
        $defaulttype      = 0;
        $defaultdiscount  = '';
        $defaultstartdate = time();
        $defaultenddate   = time();
        $isexpire         = 0;

        if ($action == 'edit') {
            if (!empty($discountlists)) {
                if (!isset($discountlists['Discount'])) {
                    foreach ($discountlists as $discountlist) {
                        if (isset($discountlist['id'])) {
                            if ($discountlist['id'] == $did) {
                                $isusecode = $discountlist['is_use_discount_code'];
                                if ($discountlist['is_use_discount_code'] == 1) {
                                    $defaultlimit  = $discountlist['discount_limit'];
                                    $defaultcode   = $discountlist['discount_code'];
                                }
                                if ($discountlist['discount_type'] == 'percentage') {
                                    $defaulttype   = 1;
                                } else {
                                    $defaulttype   = 0;
                                }
                                $defaultdiscount   = $discountlist['special_price'];
                                $defaultstartdate  = strtotime($discountlist['start_date']);
                                $isexpire      = $discountlist['is_never_expire'];
                                if ($discountlist['is_never_expire'] == 0) {
                                    $defaultenddate = strtotime($discountlist['end_date']);
                                }
                            }
                        }
                    }
                } else if (isset($discountlists['status']) && ($discountlists['status'] == 'error')) {
                    echo $discountlists['error'];
                }
            }
        }

        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('hidden', 'did', $did);
        $mform->setType('did', PARAM_INT);

        $mform->addElement('advcheckbox', 'is_use_discount_code', get_string('usediscountcode', 'braincert'), '',
                array('group' => 1), array(0, 1));
        $mform->setDefault('is_use_discount_code', $isusecode);

        $mform->addElement('text', 'discount_limit', get_string('discountlimit', 'braincert'));
        $mform->setType('discount_limit', PARAM_INT);
        $mform->addRule('discount_limit', '', 'numeric', null, 'client');
        $mform->addHelpButton('discount_limit', 'discountlimit', 'braincert');
        $mform->disabledIf('discount_limit', 'is_use_discount_code', 'notchecked');
        $mform->setDefault('discount_limit', $defaultlimit);

        $mform->addElement('text', 'discount_code', get_string('discountcode', 'braincert'));
        $mform->setType('discount_code', PARAM_RAW);
        $mform->addHelpButton('discount_code', 'discountcode', 'braincert');
        $mform->disabledIf('discount_code', 'is_use_discount_code', 'notchecked');
        $mform->setDefault('discount_code', $defaultcode);

        if (!empty($braincertrec->currency)) {
            $currency = strtoupper($braincertrec->currency);
        } else {
            $currency = 'USD';
        }
        $discounttypeoptions = array(
         '0' => '$ '.$currency,
         '1' => '% percentage');
        $mform->addElement('select', 'discount_type', get_string('discounttype', 'braincert'), $discounttypeoptions);
        $mform->addRule('discount_type', null, 'required', null, 'client');
        $mform->addHelpButton('discount_type', 'discounttype', 'braincert');
        $mform->setDefault('discount_type', $defaulttype);

        $mform->addElement('text', 'discount', get_string('amountofdiscount', 'braincert'));
        $mform->setType('discount', PARAM_INT);
        $mform->addRule('discount', '', 'numeric', null, 'client');
        $mform->addRule('discount', null, 'required', null, 'client');
        $mform->addHelpButton('discount', 'amountofdiscount', 'braincert');
        $mform->setDefault('discount', $defaultdiscount);

        $dtoption = array(
            'startyear' => 1970,
            'stopyear'  => 2020,
            'timezone'  => $braincertrec->braincert_timezone
        );
        $mform->addElement('date_selector', 'start_date', get_string('dis_startdate', 'braincert'), $dtoption);
        $mform->addHelpButton('start_date', 'dis_startdate', 'braincert');
        $mform->addRule('start_date', null, 'required', null, 'client');
        $mform->setDefault('start_date', $defaultstartdate);

        $mform->addElement('advcheckbox', 'is_never_expire', get_string('neverexpire', 'braincert'),
                '', array('group' => 1), array(0, 1));
        $mform->setDefault('is_never_expire', $isexpire);

        $mform->addElement('date_selector', 'end_date', get_string('end_date', 'braincert'), $dtoption);
        $mform->disabledIf('end_date', 'is_never_expire', 'checked');
        $mform->setDefault('end_date', $defaultenddate);

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
        $errors = parent::validation($data, $files);
        $currentdate = strtotime(date("Y-m-d"));
        if (isset($data['did']) && ($data['is_never_expire'] == 0)) {
            if (($data['end_date'] < $currentdate) || ($data['end_date'] <= $data['start_date'])) {
                $errors['end_date'] = get_string('wrongenddate', 'braincert');
            }
        } else {
            if ($data['start_date'] < $currentdate) {
                $errors['start_date'] = get_string('wrongstartdate', 'braincert');
                if ($data['end_date'] <= $data['start_date']) {
                    $errors['end_date'] = get_string('wrongenddate', 'braincert');
                }
            }
        }
        return $errors;
    }
}