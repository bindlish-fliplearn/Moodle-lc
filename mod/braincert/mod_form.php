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
 * The main braincert configuration form.
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('BRAINCERT_MINIMUM_DURATION', 30);
define('BRAINCERT_MAXIMUM_DURATION', 600);
define('BRAINCERT_MAXIMUM_ATTENDEES', 300);

require_once($CFG->dirroot.'/course/moodleform_mod.php');
$PAGE->requires->css('/mod/braincert/css/styles.css', true);

/**
 * class braincert mod form
 * @copyright Dualcube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_braincert_mod_form extends moodleform_mod {
    /**
     * Define add discount form
     */
    public function definition() {
        global $PAGE, $CFG;

        if ($CFG->version >= 2016120500) {
            $PAGE->force_settings_menu();
        }

        $bctimezoneoptions = array(
            '28' => '(GMT) Greenwich Mean Time : Dublin, Edinburgh, Lisbon, London',
            '30' => '(GMT) Monrovia, Reykjavik',
            '72' => '(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna',
            '53' => '(GMT+01:00) Brussels, Copenhagen, Madrid, Paris',
            '14' => '(GMT+01:00) Sarajevo, Skopje, Warsaw, Zagreb',
            '71' => '(GMT+01:00) West Central Africa',
            '83' => '(GMT+02:00) Amman',
            '84' => '(GMT+02:00) Beirut',
            '24' => '(GMT+02:00) Cairo',
            '61' => '(GMT+02:00) Harare, Pretoria',
            '27' => '(GMT+02:00) Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius',
            '35' => '(GMT+02:00) Jerusalem',
            '21' => '(GMT+02:00) Minsk',
            '86' => '(GMT+02:00) Windhoek',
            '31' => '(GMT+03:00) Athens, Istanbul, Minsk',
            '2'  => '(GMT+03:00) Baghdad',
            '49' => '(GMT+03:00) Kuwait, Riyadh',
            '54' => '(GMT+03:00) Moscow, St. Petersburg, Volgograd',
            '19' => '(GMT+03:00) Nairobi',
            '87' => '(GMT+03:00) Tbilisi',
            '34' => '(GMT+03:30) Tehran',
            '1'  => '(GMT+04:00) Abu Dhabi, Muscat',
            '88' => '(GMT+04:00) Baku',
            '9'  => '(GMT+04:00) Baku, Tbilisi, Yerevan',
            '89' => '(GMT+04:00) Port Louis',
            '47' => '(GMT+04:30) Kabul',
            '25' => '(GMT+05:00) Ekaterinburg',
            '90' => '(GMT+05:00) Islamabad, Karachi',
            '73' => '(GMT+05:00) Islamabad, Karachi, Tashkent',
            '33' => '(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi',
            '62' => '(GMT+05:30) Sri Jayawardenepura',
            '91' => '(GMT+05:45) Kathmandu',
            '42' => '(GMT+06:00) Almaty, Novosibirsk',
            '12' => '(GMT+06:00) Astana, Dhaka',
            '41' => '(GMT+06:30) Rangoon',
            '59' => '(GMT+07:00) Bangkok, Hanoi, Jakarta',
            '50' => '(GMT+07:00) Krasnoyarsk',
            '17' => '(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi',
            '46' => '(GMT+08:00) Irkutsk, Ulaan Bataar',
            '60' => '(GMT+08:00) Kuala Lumpur, Singapore',
            '70' => '(GMT+08:00) Perth',
            '63' => '(GMT+08:00) Taipei',
            '65' => '(GMT+09:00) Osaka, Sapporo, Tokyo',
            '77' => '(GMT+09:00) Seoul',
            '75' => '(GMT+09:00) Yakutsk',
            '10' => '(GMT+09:30) Adelaide',
            '4'  => '(GMT+09:30) Darwin',
            '20' => '(GMT+10:00) Brisbane',
            '5'  => '(GMT+10:00) Canberra, Melbourne, Sydney',
            '74' => '(GMT+10:00) Guam, Port Moresby',
            '64' => '(GMT+10:00) Hobart',
            '69' => '(GMT+10:00) Vladivostok',
            '15' => '(GMT+11:00) Magadan, Solomon Is., New Caledonia',
            '44' => '(GMT+12:00) Auckland, Wellington',
            '26' => '(GMT+12:00) Fiji, Kamchatka, Marshall Is.',
            '6'  => '(GMT-01:00) Azores',
            '8'  => '(GMT-01:00) Cape Verde Is.',
            '39' => '(GMT-02:00) Mid-Atlantic',
            '22' => '(GMT-03:00) Brasilia',
            '94' => '(GMT-03:00) Buenos Aires',
            '55' => '(GMT-03:00) Buenos Aires, Georgetown',
            '29' => '(GMT-03:00) Greenland',
            '95' => '(GMT-03:00) Montevideo',
            '45' => '(GMT-03:30) Newfoundland',
            '3'  => '(GMT-04:00) Atlantic Time (Canada)',
            '57' => '(GMT-04:00) Georgetown, La Paz, San Juan',
            '96' => '(GMT-04:00) Manaus',
            '51' => '(GMT-04:00) Santiago',
            '76' => '(GMT-04:30) Caracas',
            '56' => '(GMT-05:00) Bogota, Lima, Quito',
            '23' => '(GMT-05:00) Eastern Time (US & Canada)',
            '67' => '(GMT-05:00) Indiana (East)',
            '11' => '(GMT-06:00) Central America',
            '16' => '(GMT-06:00) Central Time (US & Canada)',
            '37' => '(GMT-06:00) Guadalajara, Mexico City, Monterrey',
            '7'  => '(GMT-06:00) Saskatchewan',
            '68' => '(GMT-07:00) Arizona',
            '38' => '(GMT-07:00) Chihuahua, La Paz, Mazatlan',
            '40' => '(GMT-07:00) Mountain Time (US & Canada)',
            '52' => '(GMT-08:00) Pacific Time (US & Canada)',
            '104' => '(GMT-08:00) Tijuana, Baja California',
            '48' => '(GMT-09:00) Alaska',
            '32' => '(GMT-10:00) Hawaii',
            '58' => '(GMT-11:00) Midway Island, Samoa',
            '18' => '(GMT-12:00) International Date Line West',
            '105' => '(GMT-4:00) Eastern Daylight Time (US & Canada)',
            '13' => '(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague'
        );
        $bcregionoptions = array(
            '1' => 'US East (Dallas, TX)',
            '2' => 'US West (Los Angeles, CA)',
            '3' => 'US East (New York)',
            '4' => 'Europe (Frankfurt, Germany)',
            '5' => 'Europe (London)',
            '6' => 'Asia Pacific (Bangalore, India)',
            '7' => 'Asia Pacific (Singapore)',
            '8' => 'US East (Miami, FL)',
            '9' => 'Europe (Milan, Italy)',
            '10' => 'Asia Pacific (Tokyo, Japan)',
            '11' => 'Middle East (Dubai, UAE)',
            '12' => 'Australia (Sydney)',
            '13' => 'Europe (Paris, France)',
            '14' => 'Asia Pacific (Beijing, China)'
        );
        $bclangoptions = array(
            '1'  => 'Arabic',
            '2'  => 'Bosnian',
            '3'  => 'Bulgarian',
            '4'  => 'Catalan',
            '5'  => 'Chinese-simplified',
            '6'  => 'Chinese-traditional',
            '7'  => 'Croatian',
            '8'  => 'Czech',
            '9'  => 'Danish',
            '10' => 'Dutch',
            '11' => 'English',
            '12' => 'Estonian',
            '13' => 'Finnish',
            '14' => 'French',
            '15' => 'German',
            '16' => 'Greek',
            '17' => 'Haitian-creole',
            '18' => 'Hebrew',
            '19' => 'Hindi',
            '20' => 'Hmong-daw',
            '21' => 'Hungarian',
            '22' => 'Indonesian',
            '23' => 'Italian',
            '24' => 'Japanese',
            '25' => 'Kiswahili',
            '26' => 'Klingon',
            '27' => 'Korean',
            '28' => 'Lithuanian',
            '29' => 'Malayalam',
            '30' => 'Malay',
            '31' => 'Maltese',
            '32' => 'Norwegian-bokma',
            '33' => 'Persian',
            '34' => 'Polish',
            '35' => 'Portuguese',
            '36' => 'Romanian',
            '37' => 'Russian',
            '38' => 'Serbian',
            '39' => 'Slovak',
            '40' => 'Slovenian',
            '41' => 'Spanish',
            '42' => 'Swedish',
            '43' => 'Tamil',
            '44' => 'Telugu',
            '45' => 'Thai',
            '46' => 'Turkish',
            '47' => 'Ukrainian',
            '48' => 'Urdu',
            '49' => 'Vietnamese',
            '50' => 'Welsh'
        );
        $bcrepeatoptions = array(
            '1' => 'Daily (all 7 days)',
            '2' => '6 Days(Mon-Sat)',
            '3' => '5 Days(Mon-Fri)',
            '4' => 'Weekly',
            '5' => 'Once every month',
            '6' => 'On selected days'
        );
        $bcweekdaysoptions = array(
            '1' => 'Sunday',
            '2' => 'Monday',
            '3' => 'Tuesday',
            '4' => 'Wednesday',
            '5' => 'Thursday',
            '6' => 'Friday',
            '7' => 'Saturday'
        );
        $bccurrencyoptions = array(
            'aud'  => 'AUD',
            'cad'  => 'CAD',
            'eur'  => 'EUR',
            'gbp'  => 'GBP',
            'nzd'  => 'NZD',
            'usd'  => 'USD'
        );
        $bctimeoptions = array(
            '12:00am' => '12:00AM',
            '12:30am' => '12:30AM',
            '1:00am'  => '1:00AM',
            '1:30am'  => '1:30AM',
            '2:00am'  => '2:00AM',
            '2:30am'  => '2:30AM',
            '3:00am'  => '3:00AM',
            '3:30am'  => '3:30AM',
            '4:00am'  => '4:00AM',
            '4:30am'  => '4:30AM',
            '5:00am'  => '5:00AM',
            '5:30am'  => '5:30AM',
            '6:00am'  => '6:00AM',
            '6:30am'  => '6:30AM',
            '7:00am'  => '7:00AM',
            '7:30am'  => '7:30AM',
            '8:00am'  => '8:00AM',
            '8:30am'  => '8:30AM',
            '9:00am'  => '9:00AM',
            '9:30am'  => '9:30AM',
            '10:00am' => '10:00AM',
            '10:30am' => '10:30AM',
            '11:00am' => '11:00AM',
            '11:30am' => '11:30AM',
            '12:00pm' => '12:00PM',
            '12:30pm' => '12:30PM',
            '1:00pm'  => '1:00PM',
            '1:30pm'  => '1:30PM',
            '2:00pm'  => '2:00PM',
            '2:30pm'  => '2:30PM',
            '3:00pm'  => '3:00PM',
            '3:30pm'  => '3:30PM',
            '4:00pm'  => '4:00PM',
            '4:30pm'  => '4:30PM',
            '5:00pm'  => '5:00PM',
            '5:30pm'  => '5:30PM',
            '6:00pm'  => '6:00PM',
            '6:30pm'  => '6:30PM',
            '7:00pm'  => '7:00PM',
            '7:30pm'  => '7:30PM',
            '8:00pm'  => '8:00PM',
            '8:30pm'  => '8:30PM',
            '9:00pm'  => '9:00PM',
            '9:30pm'  => '9:30PM',
            '10:00pm' => '10:00PM',
            '10:30pm' => '10:30PM',
            '11:00pm' => '11:00PM',
            '11:30pm' => '11:30PM'
        );
        $dtoption = array(
            'startyear' => 1970,
            'stopyear'  => 2020,
            'timezone'  => 99
        );

        $mform = $this->_form;
        // Adding the "general" fieldset.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('title', 'braincert'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_ALPHANUMEXT);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'title', 'braincert');

        $mform->addElement('hidden', 'lasteditorid', "");
        $mform->setType('lasteditorid', PARAM_INT);

        if ($CFG->version >= 2015051100) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor(true, get_string('description', 'braincert'));
        }

        // Adding the required braincert settings, spreeading all them into this fieldset.
        // or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('header', 'braincertdatetimesetting', get_string('braincertdatetimesetting', 'braincert'));

        $mform->addElement('select', 'braincert_timezone', get_string('bc_timezone', 'braincert'), $bctimezoneoptions);
        $mform->addHelpButton('braincert_timezone', 'bc_timezone', 'braincert');
        $mform->addRule('braincert_timezone', get_string('timezone_required', 'braincert'), 'required', null, 'client', true);
        $mform->setDefault('braincert_timezone', 28);

        $mform->addElement('date_selector', 'start_date', get_string('start_date', 'braincert'), $dtoption);
        $mform->addHelpButton('start_date', 'start_date', 'braincert');

        $mform->addElement('select', 'start_time', get_string('bc_starttime', 'braincert'), $bctimeoptions);
        $mform->addHelpButton('start_time', 'bc_starttime', 'braincert');

        $mform->addElement('select', 'end_time', get_string('bc_endtime', 'braincert'), $bctimeoptions);
        $mform->addHelpButton('end_time', 'bc_endtime', 'braincert');

        // Adding the rest of braincert settings.
        $mform->addElement('header', 'braincertclasssettings', get_string('braincertclasssettings', 'braincert'));

        $mform->addElement('select', 'is_region', get_string('setregion', 'braincert'), $bcregionoptions);
        $mform->addHelpButton('is_region', 'setregion', 'braincert');
        $mform->addRule('is_region', get_string('region_required', 'braincert'), 'required', null, 'client', true);
        // For Recurring Class.
        $checkrecurring = array();
        $checkrecurring[] = $mform->createElement('radio', 'is_recurring', '', get_string('yes', 'braincert'), 1);
        $checkrecurring[] = $mform->createElement('radio', 'is_recurring', '', get_string('no', 'braincert'), 0);
        $mform->addGroup($checkrecurring, 'recurring_class', get_string('recurring_class', 'braincert'), array(' '), false);
        $mform->addHelpButton('recurring_class', 'recurring_class', 'braincert');
        $mform->setDefault('is_recurring', 0);

        $mform->addElement('select', 'class_repeats', get_string('repeat_class', 'braincert'), $bcrepeatoptions);
        $mform->disabledIf('class_repeats', 'is_recurring', 'checked', 0);

        $mform->addElement('text', 'end_classes_count', get_string('end_classes', 'braincert'), array('size' => '10'));
        $mform->setType('end_classes_count', PARAM_INT);
        $mform->disabledIf('end_classes_count', 'is_recurring', 'checked', 0);
        $mform->setDefault('end_classes_count', 10);
        $mform->addRule('end_classes_count', get_string('max_number', 'braincert'), 'numeric', null, 'client');

        $mform->addElement('select', 'weekdays', get_string('weekday', 'braincert'), $bcweekdaysoptions);
        $mform->disabledIf('weekdays', 'class_repeats', 'neq', 6);
        $mform->getElement('weekdays')->setMultiple(true);

        // Change Langanguage.
        $allowtochangelang = array();
        $allowtochangelang[] = $mform->createElement('radio', 'change_language', '', get_string('yes', 'braincert'), 1);
        $allowtochangelang[] = $mform->createElement('radio', 'change_language', '', get_string('no', 'braincert'), 0);
        $mform->addGroup($allowtochangelang, 'allow_to_change_lang', get_string('change_language', 'braincert'), array(' '), false);
        $mform->addHelpButton('allow_to_change_lang', 'change_language', 'braincert');
        $mform->setDefault('change_language', 1);

        $mform->addElement('select', 'bc_interface_language', get_string('set_language', 'braincert'), $bclangoptions);
        $mform->addHelpButton('bc_interface_language', 'set_language', 'braincert');
        $mform->disabledIf('bc_interface_language', 'change_language', 'checked', 1);
        $mform->setDefault('bc_interface_language', 11);

        // Class Record Types.
        $recordclass = array();
        $recordclass[] = $mform->createElement('radio', 'record_type', '', get_string('no', 'braincert'), 0);
        $recordclass[] = $mform->createElement('radio', 'record_type', '', get_string('record_manually', 'braincert'), 1);
        $recordclass[] = $mform->createElement('radio', 'record_type', '', get_string('record_automatically', 'braincert'), 2);
        $recordclass[] = $mform->createElement('radio', 'record_type', '', get_string('record_disable_rec_btn', 'braincert'), 3);
        $mform->addGroup($recordclass, 'record_class', get_string('record_class', 'braincert'), array(' '), false);
        $mform->addHelpButton('record_class', 'record_class', 'braincert');
        $mform->setDefault('record_type', 0);

        // Video Delivery.
        $videodelivery = array();
        $videodelivery[] = $mform->createElement('radio', 'isvideo', '', get_string('singlevideofile', 'braincert'), 1);
        $videodelivery[] = $mform->createElement('radio', 'isvideo', '', get_string('multiplevideofile', 'braincert'), 0);
        $mform->addGroup($videodelivery, 'videodelivery_group', get_string('isvideo', 'braincert'), array(' '), false);
        $mform->addHelpButton('videodelivery_group', 'videodelivery_group', 'braincert');
        $mform->setDefault('isvideo', 1);

        $classroomtype = array();
        $classroomtype[] = $mform->createElement('radio', 'classroomtype', '', get_string('classroom_type_zero', 'braincert'), 0);
        $classroomtype[] = $mform->createElement('radio', 'classroomtype', '', get_string('classroom_type_one', 'braincert'), 1);
        $classroomtype[] = $mform->createElement('radio', 'classroomtype', '', get_string('classroom_type_two', 'braincert'), 2);
        $mform->addGroup($classroomtype, 'classroom_type', get_string('classroom_type', 'braincert'), array(' '), false);
        $mform->addHelpButton('classroom_type', 'classroom_type', 'braincert');
        $mform->setDefault('classroomtype', 0);

        $iscorporate = array();
        $iscorporate[] = $mform->createElement('radio', 'is_corporate', '', get_string('yes', 'braincert'), 1);
        $iscorporate[] = $mform->createElement('radio', 'is_corporate', '', get_string('no', 'braincert'), 0);
        $mform->addGroup($iscorporate, 'enable_webcam_microphone', get_string('is_corporate', 'braincert'), array(' '), false);
        $mform->addHelpButton('enable_webcam_microphone', 'is_corporate', 'braincert');
        $mform->setDefault('is_corporate', 0);

        $isscreenshare = array();
        $isscreenshare[] = $mform->createElement('radio', 'screen_sharing', '', get_string('yes', 'braincert'), 1);
        $isscreenshare[] = $mform->createElement('radio', 'screen_sharing', '', get_string('no', 'braincert'), 0);
        $mform->addGroup($isscreenshare, 'enable_screen_sharing', get_string('screen_sharing', 'braincert'), array(' '), false);
        $mform->addHelpButton('enable_screen_sharing', 'screen_sharing', 'braincert');
        $mform->setDefault('screen_sharing', 1);

        $isprivatechat = array();
        $isprivatechat[] = $mform->createElement('radio', 'private_chat', '', get_string('yes', 'braincert'), 0);
        $isprivatechat[] = $mform->createElement('radio', 'private_chat', '', get_string('no', 'braincert'), 1);
        $mform->addGroup($isprivatechat, 'enable_private_chat', get_string('private_chat', 'braincert'), array(' '), false);
        $mform->setDefault('private_chat', 1);
        $mform->addHelpButton('enable_private_chat', 'private_chat', 'braincert');

        $classtype = array();
        $classtype[] = $mform->createElement('radio', 'class_type', '', get_string('free', 'braincert'), 0);
        $classtype[] = $mform->createElement('radio', 'class_type', '', get_string('paid', 'braincert'), 1);
        $mform->addGroup($classtype, 'type_of_class', get_string('class_type', 'braincert'), array(' '), false);
        $mform->addHelpButton('type_of_class', 'class_type', 'braincert');
        $mform->setDefault('class_type', 0);

        $mform->addElement('select', 'currency', get_string('currency', 'braincert'), $bccurrencyoptions);
        $mform->disabledIf('currency', 'class_type', 'checked', 0);

        $mform->addElement('text', 'maxattendees', get_string('max_attendees', 'braincert'));
        $mform->setType('maxattendees', PARAM_INT);
        $mform->addRule('maxattendees', get_string('max_number', 'braincert'), 'numeric', null, 'client');
        $mform->setDefault('maxattendees', 25);
        $mform->addHelpButton('maxattendees', 'max_attendees', 'braincert');

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

    }

    /**
     * Validates the data input from various input elements.
     *
     * @param string $data
     * @param string $files
     *
     * @return string $errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $defaulttimezone = array(
            '1'    => 'Asia/Dubai',
            '2'    => 'Asia/Baghdad',
            '3'    => 'Canada/Atlantic',
            '4'    => 'Australia/Darwin',
            '5'    => 'Australia/Canberra',
            '6'    => 'Atlantic/Azores',
            '7'    => 'Canada/Saskatchewan',
            '8'    => 'Atlantic/Cape_Verde',
            '9'    => 'Asia/Baku',
            '10'   => 'Australia/Adelaide',
            '11'   => 'America/Belize',
            '12'   => 'Asia/Dhaka',
            '13'   => 'Europe/Belgrade',
            '14'   => 'Europe/Sarajevo',
            '15'   => 'Pacific/Guadalcanal',
            '16'   => 'America/Chicago',
            '17'   => 'Asia/Hong_Kong',
            '18'   => 'Etc/GMT-12',
            '19'   => 'Africa/Addis_Ababa',
            '20'   => 'Australia/Brisbane',
            '21'   => 'Europe/Bucharest',
            '22'   => 'America/Sao_Paulo',
            '23'   => 'America/New_York',
            '24'   => 'Africa/Cairo',
            '25'   => 'Asia/Yekaterinburg',
            '26'   => 'Pacific/Fiji',
            '27'   => 'Europe/Helsinki',
            '28'   => 'Europe/London',
            '29'   => 'America/Godthab',
            '30'   => 'Africa/Abidjan',
            '31'   => 'Europe/Minsk',
            '32'   => 'Pacific/Honolulu',
            '33'   => 'Asia/Kolkata',
            '34'   => 'Asia/Tehran',
            '35'   => 'Asia/Jerusalem',
            '37'   => 'America/Cancun',
            '38'   => 'America/Chihuahua',
            '39'   => 'America/Noronha',
            '40'   => 'America/Denver',
            '41'   => 'Asia/Rangoon',
            '42'   => 'Asia/Novosibirsk',
            '44'   => 'Pacific/Auckland',
            '45'   => 'Canada/Newfoundland',
            '46'   => 'Asia/Irkutsk',
            '47'   => 'Asia/Kabul',
            '48'   => 'America/Anchorage',
            '49'   => 'Asia/Riyadh',
            '50'   => 'Asia/Krasnoyarsk',
            '51'   => 'America/Santiago',
            '52'   => 'America/Los_Angeles',
            '53'   => 'Europe/Brussels',
            '54'   => 'Europe/Moscow',
            '55'   => 'America/Argentina/Buenos_Aires',
            '56'   => 'America/Bogota',
            '57'   => 'America/La_Paz',
            '58'   => 'Pacific/Samoa',
            '59'   => 'Asia/Bangkok',
            '60'   => 'Asia/Kuala_Lumpur',
            '61'   => 'Africa/Blantyre',
            '62'   => 'Asia/Colombo',
            '63'   => 'Asia/Taipei',
            '64'   => 'Australia/Hobart',
            '65'   => 'Asia/Tokyo',
            '67'   => 'America/Indiana/Indianapolis',
            '68'   => 'America/Phoenix',
            '69'   => 'Asia/Vladivostok',
            '70'   => 'Australia/Perth',
            '71'   => 'Africa/Algiers',
            '72'   => 'Europe/Amsterdam',
            '73'   => 'Asia/Karachi',
            '74'   => 'Pacific/Guam',
            '75'   => 'Asia/Yakutsk',
            '76'   => 'America/Caracas',
            '77'   => 'Asia/Seoul',
            '83'   => 'Asia/Amman',
            '84'   => 'Asia/Beirut',
            '86'   => 'Africa/Windhoek',
            '87'   => 'Asia/Tbilisi',
            '88'   => 'Asia/Baku',
            '89'   => 'Indian/Mauritius',
            '90'   => 'Asia/Karachi',
            '91'   => 'Asia/Kathmandu',
            '94'   => 'America/Argentina/Buenos_Aires',
            '95'   => 'America/Montevideo',
            '96'   => 'America/Manaus',
            '104'  => 'America/Tijuana',
            '105'  => 'America/New_York'
        );

        $timezone = $data['braincert_timezone'];  // Timezone.

        $now = new DateTime();
        $now->setTimezone(new DateTimeZone($defaulttimezone[$timezone]));
        $currenttime = $now->getTimestamp();

        $strttime11 = date('H:i:s', strtotime($data['start_time']));
        $endtime11 = date('H:i:s', strtotime($data['end_time']));
        $startdate = date('Y-m-d', $data['start_date']);
        if ($endtime11 < $strttime11) {
            $enddate = date("Y-m-d", strtotime("+1 day", strtotime(date('Y-m-d', $data['start_date']))));
        } else {
            $enddate = date('Y-m-d', $data['start_date']);
        }

        $enddatetime = new DateTime($enddate.' '.$data['end_time'], new DateTimeZone($defaulttimezone[$timezone]));
        $enddatetimestamp = $enddatetime->getTimestamp();

        if ($currenttime > $enddatetimestamp) {
            $errors['start_date'] = get_string('wrongtime', 'braincert');
        }

        $starttime = new DateTime($startdate.' '.$strttime11);
        $endtime = new DateTime($enddate.' '.$endtime11);
        $interval = $starttime->diff($endtime);
        $durationinmin = ($interval->h * 60) + $interval->i;

        if ((BRAINCERT_MAXIMUM_DURATION < $durationinmin) || (BRAINCERT_MINIMUM_DURATION > $durationinmin)) {
            $errors['end_time'] = get_string('wrongduration', 'braincert');
        }

        if ($data['end_classes_count'] <= 2) {
            $errors['end_classes_count'] = get_string('wrongclasscount', 'braincert');
        }

        return $errors;
    }
}
