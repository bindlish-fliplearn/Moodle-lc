<?php

// This file is part of Wiziq - http://www.wiziq.com/
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
 * The main wiziq configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_wiziq
 * @copyright  www.wiziq.com
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
define('WIZIQ_ALLOWED_DIFFRENCE', 300);
define('WIZIQ_MINIMUM_DURATION', 30);
define('WIZIQ_MAXIMUM_DURATION', 300);
global $CFG;
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once('locallib.php');
require_once('lib.php');
require_once($CFG->dirroot . '/lib/dml/moodle_database.php');

$PAGE->requires->jquery();
//$PAGE->requires->js_init_call('M.mod_wiziq.init');
$PAGE->requires->js("/mod/wiziq/js/custom.js");
/**
 * The main wiziq configuration class.
 * 
 * Module instance settings form. This class inherits the moodleform_mod class to 
 * create the moodle form for wiziq.
 * @copyright  www.wiziq.com
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wiziq_mod_form extends moodleform_mod {

    /**
     * Defines the structure for wiziq mod_form.
     */
    public function definition() {
        /* @var $COURSE type */
        global $CFG, $OUTPUT, $COURSE, $USER, $DB;
        $schedulenewwiziqclass = html_writer::link(
                        new moodle_url("$CFG->wwwroot/course/modedit.php", array('add' => 'wiziq', 'type' => '', 'course' => $COURSE->id,
                    'section' => '0', 'return' => '0')), get_string('schedule_class', 'wiziq'));
        $navigation_tabs_manage = html_writer::link(
                        new moodle_url("$CFG->wwwroot/mod/wiziq/index.php", array('id' => $COURSE->id, 'sesskey' => sesskey())), get_string('manage_classes', 'wiziq'));
        $navigation_tabs_content = html_writer::link(
                        new moodle_url("$CFG->wwwroot/mod/wiziq/content.php", array('id' => $COURSE->id, 'sesskey' => sesskey())), get_string('manage_content', 'wiziq'));
        $table_html_p1 = '<table>' . '<tr><th>' . $schedulenewwiziqclass . '</a></th><th>|</th>';
        $table_html_p2 = '<th>' . $navigation_tabs_manage . '</th><th>|</th>';
        $table_html_p3 = '<th>' . $navigation_tabs_content . '</th>';
        $table_html = $table_html_p1 . $table_html_p2 . $table_html_p3;
        $update = optional_param('update',null,PARAM_INT);
        $allrecord = $DB->get_record('course_modules', array('id' => $update));
        $allrecord1 = $DB->get_record('wiziq', array('id' => $allrecord->instance));
        
        

        /*         * *************Check case for edit section START ***************** */


        
        
        if (isset($update)) {

            if ($allrecord1->insescod == -1) {
                ?>               
                <script type="text/javascript">
                    $(document).ready(function() { // permanent class selected
                        $('#id_class_type_0').prop('checked', true);
                        $('#id_class_type_1').attr('disabled', true);
                        $('#id_class_type_2').attr('disabled', true);
                        $("#id_wiziqdatetimesetting").hide();
                    });
                </script>
            <?php } elseif (($allrecord1->class_id != 0) && ($allrecord1->class_master_id == 0)) { ?>              
                <script type="text/javascript">
                    $(document).ready(function() { // schedule class selected
                        $('#id_class_type_1').attr('checked', true);
                        $('#id_class_type_0').attr('disabled', true);
                        $('#id_class_type_2').attr('disabled', true);
                        $("#id_wiziqdatetimesetting").show();
                    });
                </script>
            <?php } elseif (($allrecord1->class_id != 0) && ($allrecord1->class_master_id != 0)) { ?>
                <script type="text/javascript">
                    $(document).ready(function() { // recurring class selected
                        $("#id_class_type_2").prop("checked", true);
                        $('#id_class_type_0').attr('disabled', true);
                        $('#id_class_type_1').attr('disabled', true);
                        $("#id_wiziqrecurringclasssettings").show();
                        $("#id_wiziqrecurringclasssettings").removeClass('collapsed');
                        $('#id_wiziqrecurringclasssettings').attr("disabled", true);
                        $("#fitem_id_schedule_for_now").hide();
                        if ($("#id_wiziq_recur_class_repeat_type").val() == '4') {
                            $("#fitem_id_specific_week").show();
                            $("#fitem_id_days_of_week").show();
                        }
                        if ($("#id_wiziq_recur_class_repeat_type").val() == '5') {
                            $("#fitem_id_select_monthly_repeat_type").show();
                            $("#fitem_id_monthly_date").show();
                        }
                    });
                </script>
                <?php

            }
            if ($allrecord1->class_schedule == '4') { // end date is selected
                ?>
                <script type="text/javascript">
                    $(document).ready(function() {
                        $("#id_class_schedule_4").prop("checked", true);
                        $('#id_class_schedule_3').attr('disabled', true);
                        $('#fitem_id_class_occurrence').hide();
                        $("#fitem_id_assesstimefinish").show();

                    });
                </script>
                <?php

            }
        }

        /*         * *************Check case for edit section END ***************** */


        $mform = $this->_form;
        $mform->addElement('html', $table_html);
        $mform->addElement('html', '</td></tr></table>');
        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));
        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('wiziqname', 'wiziq'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_ALPHANUMEXT);
        }
        $mform->addElement('hidden', 'class_id', "");
        $mform->setType('class_id', PARAM_INT);
        $mform->addElement('hidden', 'lasteditorid', "");
        $mform->setType('lasteditorid', PARAM_INT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'wiziqname', 'wiziq');

        $this->standard_intro_elements();

        $classtype = array();
        $classtype[] = $mform->createElement('radio', 'class_type', '', get_string('schedule_class_type', 'wiziq'), 1);
        //$classtype[] = $mform->createElement('radio', 'class_type', '', get_string('perma_class_type', 'wiziq'), 0);
        $classtype[] = $mform->createElement('radio', 'class_type', '', get_string('recurring_class_type', 'wiziq'), 2);
        $mform->setDefault('class_type', 1);
        $mform->addGroup($classtype, 'classtype', get_string('select_class_type', 'wiziq'), array(' '), false);
		//$mform->addGroup($classtype);					   
        //$mform->addHelpButton('classtype', 'classtype', 'wiziq');
        //-------------------------------------------------------------------------------
        // Adding the rest of wiziq settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic
        $mform->addElement('header', 'wiziqdatetimesetting', get_string('wiziqdatetimesetting', 'wiziq'));
        $vctime = wiziq_timezone();
        $wiziq_timezone_select = $mform->addElement('select', 'wiziq_timezone', get_string('vc_class_timezone', 'wiziq'), $vctime);
        $mform->setDefault('wiziq_timezone',usertimezone());
        if (isset($_COOKIE['wiziq_vctimezone'])) {
            $wiziq_vc_timezone_cookie = $_COOKIE['wiziq_vctimezone'];
            $wiziq_timezone_select->setSelected($wiziq_vc_timezone_cookie);
        }
        $mform->addHelpButton('wiziq_timezone', 'vc_class_timezone', 'wiziq');
        $mform->addRule('wiziq_timezone', get_string('timezone_required', 'wiziq'), 'required', null, 'client', true);
        $mform->addElement('checkbox', 'schedule_for_now', get_string('schedule_for_now', 'wiziq'));
        $mform->setDefault('schedule_for_now', false);
        $mform->addHelpButton('schedule_for_now', 'schedule_for_now', 'wiziq');
        $mform->addElement('hidden', 'timenow', time());
        $mform->setType('timenow', PARAM_INT);
        $dtoption = array(
            'startyear' => 1970,
            'stopyear' => 2020,
            'timezone' => 99,
            'applydst' => true,
            'step' => 1,
            'optional' => false
        );
        $mform->addelement('date_time_selector', 'wiziq_datetime', get_string('wiziq_datetime', 'wiziq'), $dtoption);
        $mform->addHelpButton('wiziq_datetime', 'wiziq_datetime', 'wiziq');
        $mform->disabledIf('wiziq_datetime', 'schedule_for_now', 'checked');
        $mform->addElement('text', 'duration', get_string('wiziq_duration', 'wiziq'));
        $mform->setType('duration', PARAM_INT);
        $mform->addRule('duration', get_string('duration_req', 'wiziq'), 'required', null, 'client', true);
        $mform->addRule('duration', get_string('duration_number', 'wiziq'), 'numeric', null, 'client');
        $mform->setDefault('duration', 30);
        $mform->addHelpButton('duration', 'duration', 'wiziq');
        // Adding the standard "intro" and "introformat" fields

        $mform->addElement('header', 'wiziqclasssettings', get_string('wiziqclasssettings', 'wiziq'));
        $vclang = wiziq_languagexml();
        $wiziq_language_select = $mform->addElement('select', 'vc_language', get_string('vc_language', 'wiziq'), $vclang);
        if (isset($_COOKIE['wiziq_vclanguage'])) {
            $wiziq_vc_cookie = $_COOKIE['wiziq_vclanguage'];
            $wiziq_language_select->setSelected($wiziq_vc_cookie);
        }
        $mform->addHelpButton('vc_language', 'vc_language', 'wiziq');
        $recordingtype = array();
        $recordingtype[] = $mform->createElement('radio', 'recording', '', get_string('record', 'wiziq'), 1);
        $recordingtype[] = $mform->createElement('radio', 'recording', '', get_string('dontrecord', 'wiziq'), 0);
        $mform->setDefault('recording', 1);
        $mform->addGroup($recordingtype, 'recordingtype', get_string('recording_option', 'wiziq'), array(' '), false);
        $mform->addHelpButton('recordingtype', 'recordingtype', 'wiziq');


        
            $vctime = wiziq_timezone();
        $courseid = $COURSE->id;
        if (is_siteadmin($USER->id)) {
            $teacherdetail = wiziq_getteacherdetail($courseid);
            if (!empty($teacherdetail)) {
                $mform->addElement('checkbox', 'scheduleforother', get_string('scheduleforother', 'wiziq'));
                $mform->setDefault('scheduleforother', false);
                $mform->addHelpButton('scheduleforother', 'scheduleforother', 'wiziq');
                $teacher = array();
                $teacher['select'] = '[select]';
                foreach ($teacherdetail as $value) {
                    $teacher[$value->id] = $value->username;
                }
                $mform->addElement('select', 'presenter_id', get_string('presenter_id', 'wiziq'), $teacher);
                $mform->disabledIf('presenter_id', 'scheduleforother', 'notchecked');
            }
        }
        //-----------------------------------------------------------------
        #----- Recurring -----

        $mform->addElement('header', 'wiziqrecurringclasssettings', get_string('wiziqrecurringclasssettings', 'wiziq'));
        $class_repeat_type = array(
            '1' => 'Daily (all 7 Days)',
            '2' => '6 Days(Mon-Sat)',
            '3' => '5 Days (Mon-Fri)',
            '4' => 'Weekly',
            '5' => 'Once every month'
        );
        $mform->addelement('select', 'wiziq_recur_class_repeat_type', get_string('class_repeat_type', 'wiziq'), $class_repeat_type); // class_repeat_type
        $mform->addHelpButton('wiziq_recur_class_repeat_type', 'wiziq_recur_class_repeat_type', 'wiziq');

        $mform->addElement('text', 'specific_week', get_string('specific_week', 'wiziq'), array('size' => '20')); //specific week
        $mform->addHelpButton('specific_week', 'specific_week', 'wiziq');

        $days_of_week = array(
            'monday' => 'monday',
            'tuesday' => 'tuesday',
            'wednesday' => 'wednesday',
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            'sunday' => 'sunday',
        );
        $mform->addelement('select', 'days_of_week', get_string('days_of_week', 'wiziq'), $days_of_week); //days_of_week
        $mform->addHelpButton('days_of_week', 'days_of_week', 'wiziq');

        $select_monthly_repeat_type = array(
            'bydate' => 'By Date',
            'byday' => 'By Day'
        );
        $mform->addelement('select', 'select_monthly_repeat_type', get_string('select_monthly_repeat_type', 'wiziq'), $select_monthly_repeat_type); //repeat_type

        function addOrdinalNumberSuffix($num) {
            if (!in_array(($num % 100), array(11, 12, 13))) {
                switch ($num % 10) {
                    // Handle 1st, 2nd, 3rd
                    case 1: return $num . 'st';
                    case 2: return $num . 'nd';
                    case 3: return $num . 'rd';
                }
            }
            return $num . 'th';
        }

        for ($i = 1; $i <= 31; $i++) {
            $monthly_date[addOrdinalNumberSuffix($i)] = addOrdinalNumberSuffix($i);
        }

        $mform->addelement('select', 'monthly_date', get_string('monthly_date', 'wiziq'), $monthly_date); //monthly_repeat
        $mform->addHelpButton('monthly_date', 'monthly_date', 'wiziq');

        $class_schedule = array();
        $class_schedule[] = $mform->createElement('radio', 'class_schedule', '', get_string('class_occurrence', 'wiziq'), 3);
        $class_schedule[] = $mform->createElement('radio', 'class_schedule', '', get_string('class_end_date', 'wiziq'), 4);
        $mform->setDefault('class_schedule', 3);
        $mform->addGroup($class_schedule, 'class_schedule', get_string('class_schedule', 'wiziq'), array(' '), false); //class_schedule
        $mform->addHelpButton('class_schedule', 'class_schedule', 'wiziq');

        $mform->addElement('text', 'class_occurrence', get_string('class_occurrence', 'wiziq'), array('size' => '20'));
        $mform->addHelpButton('class_occurrence', 'class_occurrence', 'wiziq');

        $mform->addElement('date_selector', 'assesstimefinish', get_string('class_end_date', 'wiziq')); // end date
        $mform->addHelpButton('assesstimefinish', 'assesstimefinish', 'wiziq');

        $mform->addRule('class_final', get_string('class_final_req', 'wiziq'), 'required', null, 'client', true);

        // end of recurring

    

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
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

        if (optional_param('class_type',null,PARAM_INT) == 0 || $data['class_type'] != 1) {

            if (empty($data['name'])) {
                $errors['name'] = get_string('namerequired', 'wiziq');
            }
        } else {

            if (empty($data['name'])) {
                $errors['name'] = get_string('namerequired', 'wiziq');
            }
            if ($data['wiziq_timezone'] == 'select') {
                $errors['wiziq_timezone'] = get_string('timezone_required', 'wiziq');
            }
            if (array_key_exists('presenter_id', $data)) {
                if ($data['presenter_id'] == 'select') {
                    $errors['presenter_id'] = get_string('presenter_required', 'wiziq');
                }
            }
            if (array_key_exists('schedule_for_now', $data)) {
                if ($data['schedule_for_now'] == true) {
                    $data['wiziq_datetime'] = $data['timenow'];
                }
            }
            if ($data['wiziq_datetime'] < $data['timenow']) {
                $errors['wiziq_datetime'] = get_string('wrongtime', 'wiziq');
            }
            $wiziq_duration_maxcheck = WIZIQ_MAXIMUM_DURATION < $data['duration'];
            $wiziq_duration_mincheck = $data['duration'] < WIZIQ_MINIMUM_DURATION;
            if ($wiziq_duration_maxcheck || $wiziq_duration_mincheck) {
                $errors['duration'] = get_string('wrongduration', 'wiziq');
            }
            $vc_languagecookie = $data['vc_language'];
            setcookie('wiziq_vclanguage', $vc_languagecookie, time() + (86400 * 365)); //86400  = `1 day
            $vc_timezonecookie = $data['wiziq_timezone'];
            setcookie('wiziq_vctimezone', $vc_timezonecookie, time() + (86400 * 365)); //86400  = 1 day
            return $errors;
        }
  
    
            }

}
