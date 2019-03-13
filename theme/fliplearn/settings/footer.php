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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

$temp = new admin_settingpage('theme_fliplearn_footer', get_string('footersettings', 'theme_fliplearn'));
$temp->add(new admin_setting_heading('theme_fliplearn_footer', get_string('footersettingsheading', 'theme_fliplearn'),
    format_text(get_string('footerdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

// Show moodle docs link.
$name = 'theme_fliplearn/moodledocs';
$title = get_string('moodledocs', 'theme_fliplearn');
$description = get_string('moodledocsdesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/footerblocksplacement';
$title = get_string('footerblocksplacement', 'theme_fliplearn');
$description = get_string('footerblocksplacementdesc', 'theme_fliplearn');
$choices = array(
    1 => get_string('footerblocksplacement1', 'theme_fliplearn'),
    2 => get_string('footerblocksplacement2', 'theme_fliplearn'),
    3 => get_string('footerblocksplacement3', 'theme_fliplearn'),
);
$setting = new admin_setting_configselect($name, $title, $description, 1, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Show Footer blocks.
$name = 'theme_fliplearn/showfooterblocks';
$title = get_string('showfooterblocks', 'theme_fliplearn');
$description = get_string('showfooterblocksdesc', 'theme_fliplearn');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$temp->add($setting);

$totalblocks = 0;
$imgpath = $CFG->wwwroot.'/theme/fliplearn/pix/layout-builder/';
$imgblder = '';
for ($i = 1; $i <= 3; $i++) {
    $name = 'theme_fliplearn/footerlayoutrow' . $i;
    $title = get_string('footerlayoutrow', 'theme_fliplearn');
    $description = get_string('footerlayoutrowdesc', 'theme_fliplearn');
    $default = $marketingfooterbuilderdefaults[$i - 1];
    $choices = $bootstrap12;
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $settingname = 'footerlayoutrow' . $i;

    if (!isset($PAGE->theme->settings->$settingname)) {
        $PAGE->theme->settings->$settingname = '0-0-0-0';
    }

    if ($PAGE->theme->settings->$settingname != '0-0-0-0') {
        $imgblder .= '<img src="' . $imgpath . $PAGE->theme->settings->$settingname . '.png' . '" style="padding-top: 5px">';
    }

    $vals = explode('-', $PAGE->theme->settings->$settingname);
    foreach ($vals as $val) {
        if ($val > 0) {
            $totalblocks ++;
        }
    }
}

$temp->add(new admin_setting_heading('theme_fliplearn_footerlayoutcheck', get_string('layoutcheck', 'theme_fliplearn'),
    format_text(get_string('layoutcheckdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

$temp->add(new admin_setting_heading('theme_fliplearn_footerlayoutbuilder', '', $imgblder));

$blkcontmsg = get_string('layoutaddcontentdesc1', 'theme_fliplearn');
$blkcontmsg .= $totalblocks;
$blkcontmsg .= get_string('layoutaddcontentdesc2', 'theme_fliplearn');

$temp->add(new admin_setting_heading('theme_fliplearn_footerlayoutaddcontent', get_string('layoutaddcontent', 'theme_fliplearn'),
    format_text($blkcontmsg, FORMAT_MARKDOWN)));

for ($i = 1; $i <= $totalblocks; $i++) {
    $name = 'theme_fliplearn/footer' . $i . 'header';
    $title = get_string('footerheader', 'theme_fliplearn') . $i;
    $description = get_string('footerdesc', 'theme_fliplearn') . $i;
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    $name = 'theme_fliplearn/footer' . $i . 'content';
    $title = get_string('footercontent', 'theme_fliplearn') . $i;
    $description = get_string('footercontentdesc', 'theme_fliplearn') . $i;
    $default = '';
    $setting = new fliplearn_setting_confightmleditor($name, $title, $description, $default);
    $temp->add($setting);
}

// Social icons.
$name = 'theme_fliplearn/hidefootersocial';
$title = get_string('hidefootersocial', 'theme_fliplearn');
$description = get_string('hidefootersocialdesc', 'theme_fliplearn');
$radchoices = array(
    0 => get_string('hide', 'theme_fliplearn'),
    1 => get_string('show', 'theme_fliplearn'),
);
$setting = new admin_setting_configselect($name, $title, $description, 1, $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Footnote.
$name = 'theme_fliplearn/footnote';
$title = get_string('footnote', 'theme_fliplearn');
$description = get_string('footnotedesc', 'theme_fliplearn');
$default = '';
$setting = new fliplearn_setting_confightmleditor($name, $title, $description, $default);
$temp->add($setting);


$ADMIN->add('theme_fliplearn', $temp);
