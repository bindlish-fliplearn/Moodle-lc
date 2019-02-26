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
 * Version details
 *
 * @package    theme_fliplearn
 * @copyright  2015 Jeremy Hopkins (Coventry University)
 * @copyright  2015 Fernando Acedo (3-bits.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die;
    // Header heading.
    $temp = new admin_settingpage('theme_fliplearn_header', get_string('headersettings', 'theme_fliplearn'));
    $temp->add(new admin_setting_heading('theme_fliplearn_header', get_string('headersettingsheading', 'theme_fliplearn'),
    format_text(get_string('headerdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

    // Header image.
    $name = 'theme_fliplearn/headerbgimage';
    $title = get_string('headerbgimage', 'theme_fliplearn');
    $description = get_string('headerbgimagedesc', 'theme_fliplearn');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'headerbgimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Enable front page login form in header.
    $name = 'theme_fliplearn/frontpagelogin';
    $title = get_string('frontpagelogin', 'theme_fliplearn');
    $description = get_string('frontpagelogindesc', 'theme_fliplearn');
    $default = true;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Select type of login.
    $name = 'theme_fliplearn/displaylogin';
    $title = get_string('displaylogin', 'theme_fliplearn');
    $description = get_string('displaylogindesc', 'theme_fliplearn');
    $choices = array(
        'button' => get_string('displayloginbutton', 'theme_fliplearn'),
        'box' => get_string('displayloginbox', 'theme_fliplearn'),
        'no' => get_string('displayloginno', 'theme_fliplearn')
    );
    $setting = new admin_setting_configselect($name, $title, $description, 'button', $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Enable messaging menu in header.
    $name = 'theme_fliplearn/enablemessagemenu';
    $title = get_string('enablemessagemenu', 'theme_fliplearn');
    $description = get_string('enablemessagemenudesc', 'theme_fliplearn');
    $default = true;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Filter admin messages.
    $name = 'theme_fliplearn/filteradminmessages';
    $title = get_string('filteradminmessages', 'theme_fliplearn');
    $description = get_string('filteradminmessagesdesc', 'theme_fliplearn');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Logo.
    $name = 'theme_fliplearn/logo';
    $title = get_string('logo', 'theme_fliplearn');
    $description = get_string('logodesc', 'theme_fliplearn');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Page Header Height.
    $name = 'theme_fliplearn/pageheaderheight';
    $title = get_string('pageheaderheight', 'theme_fliplearn');
    $description = get_string('pageheaderheightdesc', 'theme_fliplearn');
    $setting = new admin_setting_configtext($name, $title, $description, '72px');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Course page header title.
    $name = 'theme_fliplearn/coursepageheaderhidesitetitle';
    $title = get_string('coursepageheaderhidesitetitle', 'theme_fliplearn');
    $description = get_string('coursepageheaderhidesitetitledesc', 'theme_fliplearn');
    $setting = new admin_setting_configcheckbox($name, $title, $description, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Favicon file setting.
    $name = 'theme_fliplearn/favicon';
    $title = get_string('favicon', 'theme_fliplearn');
    $description = get_string('favicondesc', 'theme_fliplearn');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Display Course title in the header.
    $name = 'theme_fliplearn/sitetitle';
    $title = get_string('sitetitle', 'theme_fliplearn');
    $description = get_string('sitetitledesc', 'theme_fliplearn');
    $radchoices = array(
        'disabled' => get_string('sitetitleoff', 'theme_fliplearn'),
        'default' => get_string('sitetitledefault', 'theme_fliplearn'),
        'custom' => get_string('sitetitlecustom', 'theme_fliplearn')
    );
    $setting = new admin_setting_configselect($name, $title, $description, 'default', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Site title.
    $name = 'theme_fliplearn/sitetitletext';
    $title = get_string('sitetitletext', 'theme_fliplearn');
    $description = get_string('sitetitletextdesc', 'theme_fliplearn');
    $default = '';
    $setting = new fliplearn_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Display Course title in the header.
    $name = 'theme_fliplearn/enableheading';
    $title = get_string('enableheading', 'theme_fliplearn');
    $description = get_string('enableheadingdesc', 'theme_fliplearn');
    $radchoices = array(
        'fullname' => get_string('breadcrumbtitlefullname', 'theme_fliplearn'),
        'shortname' => get_string('breadcrumbtitleshortname', 'theme_fliplearn'),
        'off' => get_string('hide'),
    );
    $setting = new admin_setting_configselect($name, $title, $description, 'fullname', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Site Title Padding Top.
    $name = 'theme_fliplearn/sitetitlepaddingtop';
    $title = get_string('sitetitlepaddingtop', 'theme_fliplearn');
    $description = get_string('sitetitlepaddingtopdesc', 'theme_fliplearn');
    $setting = new admin_setting_configtext($name, $title, $description, '0px');
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $from0to20px);
    $temp->add($setting);

    // Site Title Padding Left.
    $name = 'theme_fliplearn/sitetitlepaddingleft';
    $title = get_string('sitetitlepaddingleft', 'theme_fliplearn');
    $description = get_string('sitetitlepaddingleftdesc', 'theme_fliplearn');
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $from0to20px);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Site Title Maximum Width.
    $name = 'theme_fliplearn/sitetitlemaxwidth';
    $title = get_string('sitetitlemaxwidth', 'theme_fliplearn');
    $description = get_string('sitetitlemaxwidthdesc', 'theme_fliplearn');
    $setting = new admin_setting_configselect($name, $title, $description, '50%', $from35to80percent);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Course Title Maximum Width.
    $name = 'theme_fliplearn/coursetitlemaxwidth';
    $title = get_string('coursetitlemaxwidth', 'theme_fliplearn');
    $description = get_string('coursetitlemaxwidthdesc', 'theme_fliplearn');
    $setting = new admin_setting_configtext($name, $title, $description, '20', PARAM_INT);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Breadcrumb home.
    $name = 'theme_fliplearn/breadcrumbhome';
    $title = get_string('breadcrumbhome', 'theme_fliplearn');
    $description = get_string('breadcrumbhomedesc', 'theme_fliplearn');
    $radchoices = array(
        'text' => get_string('breadcrumbhometext', 'theme_fliplearn'),
        'icon' => get_string('breadcrumbhomeicon', 'theme_fliplearn')
    );
    $setting = new admin_setting_configselect($name, $title, $description, 'icon', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Breadcrumb separator.
    $name = 'theme_fliplearn/breadcrumbseparator';
    $title = get_string('breadcrumbseparator', 'theme_fliplearn');
    $description = get_string('breadcrumbseparatordesc', 'theme_fliplearn');
    $setting = new admin_setting_configtext($name, $title, $description, 'angle-right');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Choose to display search box or social icons.
    $name = 'theme_fliplearn/socialorsearch';
    $title = get_string('socialorsearch', 'theme_fliplearn');
    $description = get_string('socialorsearchdesc', 'theme_fliplearn');
    $radchoices = array(
        'none' => get_string('socialorsearchnone', 'theme_fliplearn'),
        'social' => get_string('socialorsearchsocial', 'theme_fliplearn'),
        'search' => get_string('socialorsearchsearch', 'theme_fliplearn')
    );
    $setting = new admin_setting_configselect($name, $title, $description, 'search', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Search box padding.
    $name = 'theme_fliplearn/searchboxpadding';
    $title = get_string('searchboxpadding', 'theme_fliplearn');
    $description = get_string('searchboxpaddingdesc', 'theme_fliplearn');
    $setting = new admin_setting_configtext($name, $title, $description, '15px 0px 0px 0px');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Enable save / cancel overlay at top of page.
    $name = 'theme_fliplearn/enablesavecanceloverlay';
    $title = get_string('enablesavecanceloverlay', 'theme_fliplearn');
    $description = get_string('enablesavecanceloverlaydesc', 'theme_fliplearn');
    $default = true;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $ADMIN->add('theme_fliplearn', $temp);
