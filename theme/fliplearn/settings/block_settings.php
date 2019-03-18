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

    $temp = new admin_settingpage('theme_fliplearn_blocks', get_string('blocksettings', 'theme_fliplearn'));

    // Colours.
    $name = 'theme_fliplearn/settingscolors';
    $heading = get_string('settingscolors', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockbackgroundcolor';
    $title = get_string('blockbackgroundcolor', 'theme_fliplearn');
    $description = get_string('blockbackgroundcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFFFFF', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockheaderbackgroundcolor';
    $title = get_string('blockheaderbackgroundcolor', 'theme_fliplearn');
    $description = get_string('blockheaderbackgroundcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFFFFF', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockbordercolor';
    $title = get_string('blockbordercolor', 'theme_fliplearn');
    $description = get_string('blockbordercolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#59585D', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockregionbackgroundcolor';
    $title = get_string('blockregionbackground', 'theme_fliplearn');
    $description = get_string('blockregionbackgrounddesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, 'transparent', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Borders.
    $name = 'theme_fliplearn/settingsborders';
    $heading = get_string('settingsborders', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockheaderbordertopstyle';
    $title = get_string('blockheaderbordertopstyle', 'theme_fliplearn');
    $description = get_string('blockheaderbordertopstyledesc', 'theme_fliplearn');
    $radchoices = $borderstyles;
    $setting = new admin_setting_configselect($name, $title, $description, 'dashed', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockheadertopradius';
    $title = get_string('blockheadertopradius', 'theme_fliplearn');
    $description = get_string('blockheadertopradiusdesc', 'theme_fliplearn');
    $radchoices = $from0to20px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockheaderbottomradius';
    $title = get_string('blockheaderbottomradius', 'theme_fliplearn');
    $description = get_string('blockheaderbottomradiusdesc', 'theme_fliplearn');
    $radchoices = $from0to20px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockheaderbordertop';
    $title = get_string('blockheaderbordertop', 'theme_fliplearn');
    $description = get_string('blockheaderbordertopdesc', 'theme_fliplearn');
    $radchoices = $from0to6px;
    $setting = new admin_setting_configselect($name, $title, $description, '1px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockheaderborderleft';
    $title = get_string('blockheaderborderleft', 'theme_fliplearn');
    $description = get_string('blockheaderborderleftdesc', 'theme_fliplearn');
    $radchoices = $from0to6px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockheaderborderright';
    $title = get_string('blockheaderborderright', 'theme_fliplearn');
    $description = get_string('blockheaderborderrightdesc', 'theme_fliplearn');
    $radchoices = $from0to6px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockheaderborderbottom';
    $title = get_string('blockheaderborderbottom', 'theme_fliplearn');
    $description = get_string('blockheaderborderbottomdesc', 'theme_fliplearn');
    $radchoices = $from0to6px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockmainbordertopstyle';
    $title = get_string('blockmainbordertopstyle', 'theme_fliplearn');
    $description = get_string('blockmainbordertopstyledesc', 'theme_fliplearn');
    $radchoices = $borderstyles;
    $setting = new admin_setting_configselect($name, $title, $description, 'none', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockmaintopradius';
    $title = get_string('blockmaintopradius', 'theme_fliplearn');
    $description = get_string('blockmaintopradiusdesc', 'theme_fliplearn');
    $radchoices = $from0to20px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockmainbottomradius';
    $title = get_string('blockmainbottomradius', 'theme_fliplearn');
    $description = get_string('blockmainbottomradiusdesc', 'theme_fliplearn');
    $radchoices = $from0to20px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockmainbordertop';
    $title = get_string('blockmainbordertop', 'theme_fliplearn');
    $description = get_string('blockmainbordertopdesc', 'theme_fliplearn');
    $radchoices = $from0to6px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockmainborderleft';
    $title = get_string('blockmainborderleft', 'theme_fliplearn');
    $description = get_string('blockmainborderleftdesc', 'theme_fliplearn');
    $radchoices = $from0to6px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockmainborderright';
    $title = get_string('blockmainborderright', 'theme_fliplearn');
    $description = get_string('blockmainborderrightdesc', 'theme_fliplearn');
    $radchoices = $from0to6px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/blockmainborderbottom';
    $title = get_string('blockmainborderbottom', 'theme_fliplearn');
    $description = get_string('blockmainborderbottomdesc', 'theme_fliplearn');
    $radchoices = $from0to6px;
    $setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Fonts heading.
    $name = 'theme_fliplearn/settingsfonts';
    $heading = get_string('settingsfonts', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Block Header Font size.
    $name = 'theme_fliplearn/fontblockheadersize';
    $title = get_string('fontblockheadersize', 'theme_fliplearn');
    $description = get_string('fontblockheadersizedesc', 'theme_fliplearn');
    $default = '22px';
    $choices = $standardfontsize;
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Block Header Font weight.
    $name = 'theme_fliplearn/fontblockheaderweight';
    $title = get_string('fontblockheaderweight', 'theme_fliplearn');
    $description = get_string('fontblockheaderweightdesc', 'theme_fliplearn');
    $setting = new admin_setting_configselect($name, $title, $description, 400, $from100to900);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Block Header Font color.
    $name = 'theme_fliplearn/fontblockheadercolor';
    $title = get_string('fontblockheadercolor', 'theme_fliplearn');
    $description = get_string('fontblockheadercolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#3A454b', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Icons heading.
    $name = 'theme_fliplearn/settingsblockicons';
    $heading = get_string('settingsblockicons', 'theme_fliplearn');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Add icon to the title.
    $name = 'theme_fliplearn/blockicons';
    $title = get_string('blockicons', 'theme_fliplearn');
    $description = get_string('blockiconsdesc', 'theme_fliplearn');
    $default = true;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Block Header Icon size.
    $name = 'theme_fliplearn/blockiconsheadersize';
    $title = get_string('blockiconsheadersize', 'theme_fliplearn');
    $description = get_string('blockiconsheadersizedesc', 'theme_fliplearn');
    $default = '20px';
    $choices = $standardfontsize;
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $ADMIN->add('theme_fliplearn', $temp);
