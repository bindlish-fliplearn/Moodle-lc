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
 * @package   theme_fliplearn
 * @copyright 2015-2016 Jeremy Hopkins (Coventry University)
 * @copyright 2015-2016 Fernando Acedo (3-bits.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

// Alert Section.
$temp = new admin_settingpage('theme_fliplearn_frontpage_alert', get_string('frontpagealertsettings', 'theme_fliplearn'));
$temp->add(new admin_setting_heading('theme_fliplearn_alert', get_string('alertsettingsheading', 'theme_fliplearn'),
format_text(get_string('alertdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

// Alert General Settings Heading.
$name = 'theme_fliplearn/settingsalertgeneral';
$heading = get_string('alertsettingsgeneral', 'theme_fliplearn');
$setting = new admin_setting_heading($name, $heading, '');
$temp->add($setting);

// Enable or disable alerts.
$name = 'theme_fliplearn/enablealerts';
$title = get_string('enablealerts', 'theme_fliplearn');
$description = get_string('enablealertsdesc', 'theme_fliplearn');
$default = false;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Disable alert in course pages.
$name = 'theme_fliplearn/enablealertcoursepages';
$title = get_string('enablealertcoursepages', 'theme_fliplearn');
$description = get_string('enablealertcoursepagesdesc', 'theme_fliplearn');
$default = false;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Alert hidden course.
$name = 'theme_fliplearn/alerthiddencourse';
$title = get_string('alerthiddencourse', 'theme_fliplearn');
$description = get_string('alerthiddencoursedesc', 'theme_fliplearn');
$default = 'warning';
$choices = array(
'disabled' => get_string('alertdisabled', 'theme_fliplearn'),
'info' => get_string('alertinfo', 'theme_fliplearn'),
'warning' => get_string('alertwarning', 'theme_fliplearn'),
'success' => get_string('alertannounce', 'theme_fliplearn'));
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Strip Tags.
$name = 'theme_fliplearn/enablealertstriptags';
$title = get_string('enablealertstriptags', 'theme_fliplearn');
$description = get_string('enablealertstriptagsdesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Number of Alerts.
$name = 'theme_fliplearn/alertcount';
$title = get_string('alertcount', 'theme_fliplearn');
$description = get_string('alertcountdesc', 'theme_fliplearn');
$default = THEME_ADAPTABLE_DEFAULT_ALERTCOUNT;
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices1to12);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$alertcount = get_config('theme_fliplearn', 'alertcount');
// If we don't have an an alertcount yet, default to the preset.
if (!$alertcount) {
    $alertcount = THEME_ADAPTABLE_DEFAULT_ALERTCOUNT;
}

for ($alertindex = 1; $alertindex <= $alertcount; $alertindex++) {
    // Alert Box Heading 1.
    $name = 'theme_fliplearn/settingsalertbox' . $alertindex;
    $heading = get_string('alertsettings', 'theme_fliplearn', $alertindex);
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Enable Alert 1.
    $name = 'theme_fliplearn/enablealert' . $alertindex;
    $title = get_string('enablealert', 'theme_fliplearn', $alertindex);
    $description = get_string('enablealertdesc', 'theme_fliplearn', $alertindex);
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Key.
    $name = 'theme_fliplearn/alertkey' . $alertindex;
    $title = get_string('alertkeyvalue', 'theme_fliplearn');
    $description = get_string('alertkeyvalue_details', 'theme_fliplearn');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_RAW);
    $temp->add($setting);

    // Alert Text 1.
    $name = 'theme_fliplearn/alerttext' . $alertindex;
    $title = get_string('alerttext', 'theme_fliplearn');
    $description = get_string('alerttextdesc', 'theme_fliplearn');
    $default = '';
    $setting = new fliplearn_setting_confightmleditor($name, $title, $description, $default);
    $temp->add($setting);

    // Alert Type 1.
    $name = 'theme_fliplearn/alerttype' . $alertindex;
    $title = get_string('alerttype', 'theme_fliplearn');
    $description = get_string('alerttypedesc', 'theme_fliplearn');
    $default = 'info';
    $choices = array(
    'info' => get_string('alertinfo', 'theme_fliplearn'),
    'warning' => get_string('alertwarning', 'theme_fliplearn'),
    'success' => get_string('alertannounce', 'theme_fliplearn'));
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Access.
    $name = 'theme_fliplearn/alertaccess' . $alertindex;
    $title = get_string('alertaccess', 'theme_fliplearn');
    $description = get_string('alertaccessdesc', 'theme_fliplearn');
    $default = 'global';
    $choices = array(
    'global' => get_string('alertaccessglobal', 'theme_fliplearn'),
    'user' => get_string('alertaccessusers', 'theme_fliplearn'),
    'admin' => get_string('alertaccessadmins', 'theme_fliplearn'),
    'profile' => get_string('alertaccessprofile', 'theme_fliplearn'));
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/alertprofilefield' . $alertindex;
    $title = get_string('alertprofilefield', 'theme_fliplearn');
    $description = get_string('alertprofilefielddesc', 'theme_fliplearn');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_RAW);
    $temp->add($setting);
}

// Colours.
// Alert Course Settings Heading.
$name = 'theme_fliplearn/settingsalertcolors';
$heading = get_string('settingscolors', 'theme_fliplearn');
$setting = new admin_setting_heading($name, $heading, '');
$temp->add($setting);

// Alert info colours.
$name = 'theme_fliplearn/alertcolorinfo';
$title = get_string('alertcolorinfo', 'theme_fliplearn');
$description = get_string('alertcolorinfodesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#3a87ad', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/alertbackgroundcolorinfo';
$title = get_string('alertbackgroundcolorinfo', 'theme_fliplearn');
$description = get_string('alertbackgroundcolorinfodesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#d9edf7', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/alertbordercolorinfo';
$title = get_string('alertbordercolorinfo', 'theme_fliplearn');
$description = get_string('alertbordercolorinfodesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#bce8f1', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/alerticoninfo';
$title = get_string('alerticoninfo', 'theme_fliplearn');
$description = get_string('alerticoninfodesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, 'info-circle');
$temp->add($setting);

// Alert success colours.
$name = 'theme_fliplearn/alertcolorsuccess';
$title = get_string('alertcolorsuccess', 'theme_fliplearn');
$description = get_string('alertcolorsuccessdesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#468847', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/alertbackgroundcolorsuccess';
$title = get_string('alertbackgroundcolorsuccess', 'theme_fliplearn');
$description = get_string('alertbackgroundcolorsuccessdesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#dff0d8', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/alertbordercolorsuccess';
$title = get_string('alertbordercolorsuccess', 'theme_fliplearn');
$description = get_string('alertbordercolorsuccessdesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#d6e9c6', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/alerticonsuccess';
$title = get_string('alerticonsuccess', 'theme_fliplearn');
$description = get_string('alerticonsuccessdesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, 'bullhorn');
$temp->add($setting);

// Alert warning colours.
$name = 'theme_fliplearn/alertcolorwarning';
$title = get_string('alertcolorwarning', 'theme_fliplearn');
$description = get_string('alertcolorwarningdesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#8a6d3b', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/alertbackgroundcolorwarning';
$title = get_string('alertbackgroundcolorwarning', 'theme_fliplearn');
$description = get_string('alertbackgroundcolorwarningdesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#fcf8e3', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/alertbordercolorwarning';
$title = get_string('alertbordercolorwarning', 'theme_fliplearn');
$description = get_string('alertbordercolorwarningdesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#fbeed5', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/alerticonwarning';
$title = get_string('alerticonwarning', 'theme_fliplearn');
$description = get_string('alerticonwarningdesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, 'exclamation-triangle');
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$ADMIN->add('theme_fliplearn', $temp);
