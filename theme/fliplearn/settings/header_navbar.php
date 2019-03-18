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

// Header Navbar.
$temp = new admin_settingpage('theme_fliplearn_navbar', get_string('navbarsettings', 'theme_fliplearn'));
$temp->add(new admin_setting_heading('theme_fliplearn_navbar', get_string('navbarsettingsheading', 'theme_fliplearn'),
format_text(get_string('navbardesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));


// Sticky Navbar at the top. See issue #278.
$name = 'theme_fliplearn/stickynavbar';
$title = get_string('stickynavbar', 'theme_fliplearn');
$description = get_string('stickynavbardesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Enable/Disable menu items.
$name = 'theme_fliplearn/enablehome';
$title = get_string('home');
$description = get_string('enablehomedesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/enablehomeredirect';
$title = get_string('enablehomeredirect', 'theme_fliplearn');
$description = get_string('enablehomeredirectdesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/enablemyhome';
$title = get_string('myhome');
$description = get_string('enablemyhomedesc', 'theme_fliplearn', get_string('myhome'));
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/enableevents';
$title = get_string('events', 'theme_fliplearn');
$description = get_string('enableeventsdesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/enablethiscourse';
$title = get_string('thiscourse', 'theme_fliplearn');
$description = get_string('enablethiscoursedesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/enablezoom';
$title = get_string('enablezoom', 'theme_fliplearn');
$description = get_string('enablezoomdesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/enableshowhideblocks';
$title = get_string('enableshowhideblocks', 'theme_fliplearn');
$description = get_string('enableshowhideblocksdesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/enablenavbarwhenloggedout';
$title = get_string('enablenavbarwhenloggedout', 'theme_fliplearn');
$description = get_string('enablenavbarwhenloggedoutdesc', 'theme_fliplearn');
$default = false;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Navbar styling.
$temp->add(new admin_setting_heading('theme_fliplearn_navbar_styling_heading',
        get_string('headernavbarstylingheading', 'theme_fliplearn'),
        format_text(get_string('headernavbarstylingheadingdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

$name = 'theme_fliplearn/navbardisplayicons';
$title = get_string('navbardisplayicons', 'theme_fliplearn');
$description = get_string('navbardisplayiconsdesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/navbardisplaysubmenuarrow';
$title = get_string('navbardisplaysubmenuarrow', 'theme_fliplearn');
$description = get_string('navbardisplaysubmenuarrowdesc', 'theme_fliplearn');
$default = false;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Dropdown border radius.
$name = 'theme_fliplearn/navbardropdownborderradius';
$title = get_string('navbardropdownborderradius', 'theme_fliplearn');
$description = get_string('navbardropdownborderradiusdesc', 'theme_fliplearn');
$setting = new admin_setting_configselect($name, $title, $description, '0px', $from0to20px);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Dropdown Menu Item Link hover colour.
$name = 'theme_fliplearn/navbardropdownhovercolor';
$title = get_string('navbardropdownhovercolor', 'theme_fliplearn');
$description = get_string('navbardropdownhovercolordesc', 'theme_fliplearn');
$default = '#EEE';
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Dropdown transition time.
$name = 'theme_fliplearn/navbardropdowntransitiontime';
$title = get_string('navbardropdowntransitiontime', 'theme_fliplearn');
$description = get_string('navbardropdowntransitiontimedesc', 'theme_fliplearn');
$setting = new admin_setting_configselect($name, $title, $description, '0.2s', $from0to1second);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// My courses section.
$temp->add(new admin_setting_heading('theme_fliplearn_mycourses_heading',
        get_string('headernavbarmycoursesheading', 'theme_fliplearn'),
        format_text(get_string('headernavbarmycoursesheadingdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

$name = 'theme_fliplearn/enablemysites';
$title = get_string('mysites', 'theme_fliplearn');
$description = get_string('enablemysitesdesc', 'theme_fliplearn');
$choices = array(
    'excludehidden' => get_string('mysitesexclude', 'theme_fliplearn'),
    'includehidden' => get_string('mysitesinclude', 'theme_fliplearn'),
    'disabled' => get_string('mysitesdisabled', 'theme_fliplearn'),
);
$setting->set_updatedcallback('theme_reset_all_caches');
$setting = new admin_setting_configselect($name, $title, $description, 'excludehidden', $choices);
$temp->add($setting);

// Custom profile field value for restricting access to my courses menu.
$name = 'theme_fliplearn/enablemysitesrestriction';
$title = get_string('enablemysitesrestriction', 'theme_fliplearn');
$description = get_string('enablemysitesrestrictiondesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_RAW);
$temp->add($setting);

$name = 'theme_fliplearn/mycoursesmenulimit';
$title = get_string('mycoursesmenulimit', 'theme_fliplearn');
$description = get_string('mycoursesmenulimitdesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, '20', PARAM_INT);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/mysitesmaxlength';
$title = get_string('mysitesmaxlength', 'theme_fliplearn');
$description = get_string('mysitesmaxlengthdesc', 'theme_fliplearn');
$setting = new admin_setting_configselect($name, $title, $description, '30', $from20to40);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/mysitessortoverride';
$title = get_string('mysitessortoverride', 'theme_fliplearn');
$description = get_string('mysitessortoverridedesc', 'theme_fliplearn');
$choices = array(
    'off' => get_string('mysitessortoverrideoff', 'theme_fliplearn'),
    'strings' => get_string('mysitessortoverridestrings', 'theme_fliplearn'),
    'profilefields' => get_string('mysitessortoverrideprofilefields', 'theme_fliplearn'),
    'profilefieldscohort' => get_string('mysitessortoverrideprofilefieldscohort', 'theme_fliplearn')
);
$setting = new admin_setting_configselect($name, $title, $description, 'off', $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/mysitessortoverridefield';
$title = get_string('mysitessortoverridefield', 'theme_fliplearn');
$description = get_string('mysitessortoverridefielddesc', 'theme_fliplearn');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW);
$temp->add($setting);

$name = 'theme_fliplearn/mysitesmenudisplay';
$title = get_string('mysitesmenudisplay', 'theme_fliplearn');
$description = get_string('mysitesmenudisplaydesc', 'theme_fliplearn');
$displaychoices = array(
        'shortcodenohover' => get_string('mysitesmenudisplayshortcodenohover', 'theme_fliplearn'),
        'shortcodehover' => get_string('mysitesmenudisplayshortcodefullnameonhover', 'theme_fliplearn'),
        'fullnamenohover' => get_string('mysitesmenudisplayfullnamenohover', 'theme_fliplearn'),
        'fullnamehover' => get_string('mysitesmenudisplayfullnamefullnameonhover', 'theme_fliplearn')

);
$setting->set_updatedcallback('theme_reset_all_caches');
$setting = new admin_setting_configselect($name, $title, $description, 'shortcodehover', $displaychoices);
$temp->add($setting);

// This course section.
$temp->add(new admin_setting_heading('theme_fliplearn_thiscourse_heading',
        get_string('headernavbarthiscourseheading', 'theme_fliplearn'),
        format_text(get_string('headernavbarthiscourseheadingdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

// Display participants.
$name = 'theme_fliplearn/displayparticipants';
$title = get_string('displayparticipants', 'theme_fliplearn');
$description = get_string('displayparticipantsdesc', 'theme_fliplearn');
$radchoices = array(
    0 => get_string('hide', 'theme_fliplearn'),
    1 => get_string('show', 'theme_fliplearn'),
);
$setting = new admin_setting_configselect($name, $title, $description, 1, $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Display Grades.
$name = 'theme_fliplearn/displaygrades';
$title = get_string('displaygrades', 'theme_fliplearn');
$description = get_string('displaygradesdesc', 'theme_fliplearn');
$radchoices = array(
    0 => get_string('hide', 'theme_fliplearn'),
    1 => get_string('show', 'theme_fliplearn'),
);
$setting = new admin_setting_configselect($name, $title, $description, 1, $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);


// Help section.
$temp->add(new admin_setting_heading('theme_fliplearn_help_heading',
        get_string('headernavbarhelpheading', 'theme_fliplearn'),
        format_text(get_string('headernavbarhelpheadingdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

// Enable help link.
$name = 'theme_fliplearn/enablehelp';
$title = get_string('enablehelp', 'theme_fliplearn');
$description = get_string('enablehelpdesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$temp->add($setting);

$name = 'theme_fliplearn/helpprofilefield';
$title = get_string('helpprofilefield', 'theme_fliplearn');
$description = get_string('helpprofilefielddesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_RAW);
$temp->add($setting);

$name = 'theme_fliplearn/enablehelp2';
$title = get_string('enablehelp', 'theme_fliplearn');
$description = get_string('enablehelpdesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$temp->add($setting);

$name = 'theme_fliplearn/helpprofilefield2';
$title = get_string('helpprofilefield', 'theme_fliplearn');
$description = get_string('helpprofilefielddesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_RAW);
$temp->add($setting);

$name = 'theme_fliplearn/helptarget';
$title = get_string('helptarget', 'theme_fliplearn');
$description = get_string('helptargetdesc', 'theme_fliplearn');
$choices = array(
    '_blank' => get_string('targetnewwindow', 'theme_fliplearn'),
    '_self' => get_string('targetsamewindow', 'theme_fliplearn'),
);
$setting = new admin_setting_configselect($name, $title, $description, '_blank', $choices);
$temp->add($setting);


// Create page.
$ADMIN->add('theme_fliplearn', $temp);
