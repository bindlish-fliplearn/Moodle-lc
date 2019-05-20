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
 * wiziq module admin settings and defaults
 *
 * @package    mod
 * @subpackage wiziq
 * @copyright  www.wiziq.com
 * @author     wiziq
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('wiziq_method_heading', get_string('generalconfig', 'wiziq'),
                       get_string('explaingeneralconfig', 'wiziq')));
 
    $settings->add(new admin_setting_configtext('wiziq_webserviceurl', get_string('webserviceurl', 'wiziq'),
                       get_string('webserviceurl_desc', 'wiziq'), 'http://classapi.wiziqxt.com/apimanager.ashx', PARAM_URL));
 
   $settings->add(new admin_setting_configtext('wiziq_content_webservice',
  get_string('wiziq_content_webservice', 'wiziq'),
            get_string('wiziq_content_webservice_desc', 'wiziq'),
  'http://contentapi.wiziqxt.com/RestService.ashx', PARAM_URL));
    $settings->add(new admin_setting_configtext('wiziq_access_key', get_string('access_key', 'wiziq'),
                       get_string('access_key_desc', 'wiziq'), '', PARAM_TEXT));
 
    $settings->add(new admin_setting_configtext('wiziq_secretacesskey', get_string('secretacesskey', 'wiziq'),
                       get_string('secretacesskey_desc', 'wiziq'), '', PARAM_TEXT));
 
    $settings->add(new admin_setting_configtext('wiziq_vc_language',
   get_string('vc_language_xml', 'wiziq'),
            get_string('vc_language_xml_desc', 'wiziq'),
   'http://classapi.wiziqxt.com/vc-language.xml', PARAM_URL));
 
 $settings->add(new admin_setting_configtext('wiziq_timezone',
    get_string('timezone_xml', 'wiziq'),
          get_string('timezone_xml_desc', 'wiziq'),
 'http://classapi.wiziqxt.com/tz.xml', PARAM_URL));
 
  $settings->add(new admin_setting_configcheckbox('wiziq_emailsetting',
        get_string('wiziq_emailsetting', 'wiziq'), get_string('wiziq_emailsetting', 'wiziq'), false));
 
   $str = '<center><img src="'.$CFG->wwwroot.'/mod/wiziq/pix/wiziq-logo.gif"/></center><br />';
    $settings->add(new admin_setting_heading('wiziq_logo', '', $str));
    $settings->add(new admin_setting_heading('wiziq_desc', 'Build. No : M33WZQ1482278400', get_string('setting_discription', 'wiziq')));
}

//$PAGE->requires->js('/mod/wiziq/js/jquerymain.js');

$PAGE->requires->jquery();
//$PAGE->requires->js_init_call('M.mod_wiziq.init');
?>

