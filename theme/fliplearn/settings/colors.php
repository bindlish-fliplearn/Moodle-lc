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
 * @copyright  2015-2016 Jeremy Hopkins (Coventry University)
 * @copyright  2015-2016 Fernando Acedo (3-bits.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

    // Colors section.
    $temp = new admin_settingpage('theme_fliplearn_color', get_string('colorsettings', 'theme_fliplearn'));
    $temp->add(new admin_setting_heading('theme_fliplearn_color', get_string('colorsettingsheading', 'theme_fliplearn'),
                   format_text(get_string('colordesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

    // Main colors heading.
    $name = 'theme_fliplearn/settingsmaincolors';
    $heading = get_string('settingsmaincolors', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Site main color.
    $name = 'theme_fliplearn/maincolor';
    $title = get_string('maincolor', 'theme_fliplearn');
    $description = get_string('maincolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#3A454b', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Site background color.
    $name = 'theme_fliplearn/backcolor';
    $title = get_string('backcolor', 'theme_fliplearn');
    $description = get_string('backcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFF', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Main region background color.
    $name = 'theme_fliplearn/regionmaincolor';
    $title = get_string('regionmaincolor', 'theme_fliplearn');
    $description = get_string('regionmaincolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFF', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Link color.
    $name = 'theme_fliplearn/linkcolor';
    $title = get_string('linkcolor', 'theme_fliplearn');
    $description = get_string('linkcolordesc', 'theme_fliplearn');
    $default = '#51666C';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $temp->add($setting);

    // Link hover color.
    $name = 'theme_fliplearn/linkhover';
    $title = get_string('linkhover', 'theme_fliplearn');
    $description = get_string('linkhoverdesc', 'theme_fliplearn');
    $default = '#009688';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Selection text color.
    $name = 'theme_fliplearn/selectiontext';
    $title = get_string('selectiontext', 'theme_fliplearn');
    $description = get_string('selectiontextdesc', 'theme_fliplearn');
    $default = '#000000';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Selection background color.
    $name = 'theme_fliplearn/selectionbackground';
    $title = get_string('selectionbackground', 'theme_fliplearn');
    $description = get_string('selectionbackgrounddesc', 'theme_fliplearn');
    $default = '#00B3A1';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Header colors heading.
    $name = 'theme_fliplearn/settingsheadercolors';
    $heading = get_string('settingsheadercolors', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Loading bar color.
    $name = 'theme_fliplearn/loadingcolor';
    $title = get_string('loadingcolor', 'theme_fliplearn');
    $description = get_string('loadingcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#00B3A1', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Top header message badge background color.
    $name = 'theme_fliplearn/msgbadgecolor';
    $title = get_string('msgbadgecolor', 'theme_fliplearn');
    $description = get_string('msgbadgecolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#E53935', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Messages main chat window background colour.
    $name = 'theme_fliplearn/messagingbackgroundcolor';
    $title = get_string('messagingbackgroundcolor', 'theme_fliplearn');
    $description = get_string('messagingbackgroundcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFFFFF', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Top header background color.
    $name = 'theme_fliplearn/headerbkcolor';
    $title = get_string('headerbkcolor', 'theme_fliplearn');
    $description = get_string('headerbkcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#00796B', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Top header text color.
    $name = 'theme_fliplearn/headertextcolor';
    $title = get_string('headertextcolor', 'theme_fliplearn');
    $description = get_string('headertextcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Bottom header background color.
    $name = 'theme_fliplearn/headerbkcolor2';
    $title = get_string('headerbkcolor2', 'theme_fliplearn');
    $description = get_string('headerbkcolor2desc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#009688', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Bottom header text color.
    $name = 'theme_fliplearn/headertextcolor2';
    $title = get_string('headertextcolor2', 'theme_fliplearn');
    $description = get_string('headertextcolor2desc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Menu colors heading.
    $name = 'theme_fliplearn/settingsmenucolors';
    $heading = get_string('settingsmenucolors', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Main menu background color.
    $name = 'theme_fliplearn/menubkcolor';
    $title = get_string('menubkcolor', 'theme_fliplearn');
    $description = get_string('menubkcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Main menu text color.
    $name = 'theme_fliplearn/menufontcolor';
    $title = get_string('menufontcolor', 'theme_fliplearn');
    $description = get_string('menufontcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#222222', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Main menu hover color.
    $name = 'theme_fliplearn/menuhovercolor';
    $title = get_string('menuhovercolor', 'theme_fliplearn');
    $description = get_string('menuhovercolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#00B3A1', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Main menu bottom border color.
    $name = 'theme_fliplearn/menubordercolor';
    $title = get_string('menubordercolor', 'theme_fliplearn');
    $description = get_string('menubordercolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#00B3A1', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Mobile Menu colors heading.
    $name = 'theme_fliplearn/settingsmobilemenucolors';
    $heading = get_string('settingsmobilemenucolors', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Mobile menu background color.
    $name = 'theme_fliplearn/mobilemenubkcolor';
    $title = get_string('mobilemenubkcolor', 'theme_fliplearn');
    $description = get_string('mobilemenubkcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#F9F9F9', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Mobile menu text color.
    $name = 'theme_fliplearn/mobilemenufontcolor';
    $title = get_string('mobilemenufontcolor', 'theme_fliplearn');
    $description = get_string('mobilemenufontcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#000000', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);


    // Market blocks colors heading.
    $name = 'theme_fliplearn/settingsmarketingcolors';
    $heading = get_string('settingsmarketingcolors', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Market blocks border color.
    $name = 'theme_fliplearn/marketblockbordercolor';
    $title = get_string('marketblockbordercolor', 'theme_fliplearn');
    $description = get_string('marketblockbordercolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#e8eaeb', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Market blocks background color.
    $name = 'theme_fliplearn/marketblocksbackgroundcolor';
    $title = get_string('marketblocksbackgroundcolor', 'theme_fliplearn');
    $description = get_string('marketblocksbackgroundcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, 'transparent', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);


    // Overlay tiles colors heading.
    $name = 'theme_fliplearn/settingsoverlaycolors';
    $heading = get_string('settingsoverlaycolors', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    $name = 'theme_fliplearn/rendereroverlaycolor';
    $title = get_string('rendereroverlaycolor', 'theme_fliplearn');
    $description = get_string('rendereroverlaycolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#3A454b', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/rendereroverlayfontcolor';
    $title = get_string('rendereroverlayfontcolor', 'theme_fliplearn');
    $description = get_string('rendereroverlayfontcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFF', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/tilesbordercolor';
    $title = get_string('tilesbordercolor', 'theme_fliplearn');
    $description = get_string('tilesbordercolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#3A454b', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/covbkcolor';
    $title = get_string('covbkcolor', 'theme_fliplearn');
    $description = get_string('covbkcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#3A454b', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/covfontcolor';
    $title = get_string('covfontcolor', 'theme_fliplearn');
    $description = get_string('covfontcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/dividingline';
    $title = get_string('dividingline', 'theme_fliplearn');
    $description = get_string('dividinglinedesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/dividingline2';
    $title = get_string('dividingline2', 'theme_fliplearn');
    $description = get_string('dividingline2desc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Breadcrumb colors heading.
    $name = 'theme_fliplearn/settingsbreadcrumbcolors';
    $heading = get_string('settingsbreadcrumbcolors', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Breadcrumb background color.
    $name = 'theme_fliplearn/breadcrumb';
    $title = get_string('breadcrumb', 'theme_fliplearn');
    $description = get_string('breadcrumbdesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#f5f5f5', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Breadcrumb text color.
    $name = 'theme_fliplearn/breadcrumbtextcolor';
    $title = get_string('breadcrumbtextcolor', 'theme_fliplearn');
    $description = get_string('breadcrumbtextcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#444444', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);


    // Messages pop-up colors heading.
    $name = 'theme_fliplearn/settingsmessagescolors';
    $heading = get_string('settingsmessagescolors', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Messages pop-up background color.
    $name = 'theme_fliplearn/messagepopupbackground';
    $title = get_string('messagepopupbackground', 'theme_fliplearn');
    $description = get_string('messagepopupbackgrounddesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#fff000', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Messages pop-up text color.
    $name = 'theme_fliplearn/messagepopupcolor';
    $title = get_string('messagepopupcolor', 'theme_fliplearn');
    $description = get_string('messagepopupcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#333333', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Footer colors heading.
    $name = 'theme_fliplearn/settingsfootercolors';
    $heading = get_string('settingsfootercolors', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    $name = 'theme_fliplearn/footerbkcolor';
    $title = get_string('footerbkcolor', 'theme_fliplearn');
    $description = get_string('footerbkcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#424242', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/footertextcolor';
    $title = get_string('footertextcolor', 'theme_fliplearn');
    $description = get_string('footertextcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/footertextcolor2';
    $title = get_string('footertextcolor2', 'theme_fliplearn');
    $description = get_string('footertextcolor2desc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/footerlinkcolor';
    $title = get_string('footerlinkcolor', 'theme_fliplearn');
    $description = get_string('footerlinkcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Forum colors.
    $name = 'theme_fliplearn/settingsforumheading';
    $heading = get_string('settingsforumheading', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    $name = 'theme_fliplearn/forumheaderbackgroundcolor';
    $title = get_string('forumheaderbackgroundcolor', 'theme_fliplearn');
    $description = get_string('forumheaderbackgroundcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/forumbodybackgroundcolor';
    $title = get_string('forumbodybackgroundcolor', 'theme_fliplearn');
    $description = get_string('forumbodybackgroundcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $ADMIN->add('theme_fliplearn', $temp);
