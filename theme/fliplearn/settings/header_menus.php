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
 * @copyright 2015 Jeremy Hopkins (Coventry University)
 * @copyright 2015 Fernando Acedo (3-bits.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

global $PAGE;

$temp = new admin_settingpage('theme_fliplearn_menus', get_string('menusettings', 'theme_fliplearn'));

$temp->add(new admin_setting_heading('theme_fliplearn_menus', get_string('menusheading', 'theme_fliplearn'),
format_text(get_string('menustitledesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

// Settings for top header menus.
$temp->add(new admin_setting_heading('theme_fliplearn_menus_visibility',
get_string('menusheadingvisibility', 'theme_fliplearn'),
format_text(get_string('menusheadingvisibilitydesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

$name = 'theme_fliplearn/enablemenus';
$title = get_string('enablemenus', 'theme_fliplearn');
$description = get_string('enablemenusdesc', 'theme_fliplearn');
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/menuslinkright';
$title = get_string('menuslinkright', 'theme_fliplearn');
$description = get_string('menuslinkrightdesc', 'theme_fliplearn');
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/disablemenuscoursepages';
$title = get_string('disablemenuscoursepages', 'theme_fliplearn');
$description = get_string('disablemenuscoursepagesdesc', 'theme_fliplearn');
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/menusession';
$title = get_string('menusession', 'theme_fliplearn');
$description = get_string('menusessiondesc', 'theme_fliplearn');
$setting = new admin_setting_configcheckbox($name, $title, $description, true, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/menusessionttl';
$title = get_string('menusessionttl', 'theme_fliplearn');
$description = get_string('menusessionttldesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, '30', PARAM_INT);
$temp->add($setting);

$name = 'theme_fliplearn/menuuseroverride';
$title = get_string('menuuseroverride', 'theme_fliplearn');
$description = get_string('menuuseroverridedesc', 'theme_fliplearn');
$default = false;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/menuoverrideprofilefield';
$title = get_string('menuoverrideprofilefield', 'theme_fliplearn');
$description = get_string('menuoverrideprofilefielddesc', 'theme_fliplearn');
$default = get_string('menuoverrideprofilefielddefault', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW);
$temp->add($setting);

// Number of menus.
$name = 'theme_fliplearn/topmenuscount';
$title = get_string('topmenuscount', 'theme_fliplearn');
$description = get_string('topmenuscountdesc', 'theme_fliplearn');
$default = THEME_ADAPTABLE_DEFAULT_TOPMENUSCOUNT;
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices1to12);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// If we don't have a menuscount yet, default to the preset.
$topmenuscount = get_config('theme_fliplearn', 'topmenuscount');
if (!$topmenuscount) {
    $topmenuscount = THEME_ADAPTABLE_DEFAULT_TOPMENUSCOUNT;
}

for ($topmenusindex = 1; $topmenusindex <= $topmenuscount; $topmenusindex ++) {
    $temp->add(new admin_setting_heading('theme_fliplearn_menus' . $topmenusindex,
    get_string('newmenuheading', 'theme_fliplearn') . $topmenusindex,
    format_text(get_string('menusdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

    $name = 'theme_fliplearn/newmenu' . $topmenusindex . 'title';
    $title = get_string('newmenutitle', 'theme_fliplearn');
    $description = get_string('newmenutitledesc', 'theme_fliplearn');
    $default = get_string('newmenutitledefault', 'theme_fliplearn') . ' ' . $topmenusindex;
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW);
    $temp->add($setting);

    $name = 'theme_fliplearn/newmenu' . $topmenusindex;
    $title = get_string('newmenu', 'theme_fliplearn') . $topmenusindex;
    $description = get_string('newmenudesc', 'theme_fliplearn');
    $setting = new admin_setting_configtextarea($name, $title, $description, '', PARAM_RAW, '50', '10');
    $temp->add($setting);

    $name = 'theme_fliplearn/newmenu' . $topmenusindex . 'requirelogin';
    $title = get_string('newmenurequirelogin', 'theme_fliplearn');
    $description = get_string('newmenurequirelogindesc', 'theme_fliplearn');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/newmenu' . $topmenusindex . 'field';
    $title = get_string('newmenufield', 'theme_fliplearn');
    $description = get_string('newmenufielddesc', 'theme_fliplearn');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_RAW);
    $temp->add($setting);
}

$ADMIN->add('theme_fliplearn', $temp);

