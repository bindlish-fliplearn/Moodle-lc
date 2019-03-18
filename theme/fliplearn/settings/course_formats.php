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
 * @copyright  2015-2017 Fernando Acedo (3-bits.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

// Course Formats.
$temp = new admin_settingpage('theme_fliplearn_course', get_string('coursesettings', 'theme_fliplearn'));
$temp->add(new admin_setting_heading('theme_fliplearn_course', get_string('coursesettingsheading', 'theme_fliplearn'),
    format_text(get_string('coursesettingsdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

// Course page, wide layout by moving sidebar to bottom.
$temp->add(new admin_setting_heading('coursepagesidebarinfooterenabledsection',
        get_string('coursepagesidebarinfooterenabledsection', 'theme_fliplearn'),
        format_text(get_string('coursepagesidebarinfooterenabledsectiondesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

$name = 'theme_fliplearn/coursepagesidebarinfooterenabled';
$title = get_string('coursepagesidebarinfooterenabled', 'theme_fliplearn');
$description = get_string('coursepagesidebarinfooterenableddesc', 'theme_fliplearn');
$setting = new admin_setting_configcheckbox($name, $title, $description, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Show Your progress string in the top of the course.
$name = 'theme_fliplearn/showyourprogress';
$title = get_string('showyourprogress', 'theme_fliplearn');
$description = get_string('showyourprogressdesc', 'theme_fliplearn');
$radchoices = array(
    'none' => get_string('hide', 'theme_fliplearn'),
    ''     => get_string('show', 'theme_fliplearn'),
);
$setting = new admin_setting_configselect($name, $title, $description, '', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course page top slider block region enabled.
$temp->add(new admin_setting_heading('theme_fliplearn_newsslider_heading',
        get_string('coursepagenewssliderblockregionheading', 'theme_fliplearn'),
        format_text(get_string('coursepagenewssliderblockregionheadingdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));
$name = 'theme_fliplearn/coursepageblocksliderenabled';
$title = get_string('coursepageblocksliderenabled', 'theme_fliplearn');
$description = get_string('coursepageblocksliderenableddesc', 'theme_fliplearn');
$setting = new admin_setting_configcheckbox($name, $title, $description, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course block layout settings.
get_string('coursepageblockregionsettings', 'theme_fliplearn');

$temp->add(new admin_setting_heading('theme_fliplearn_heading', get_string('coursepageblocklayoutbuilder', 'theme_fliplearn'),
        format_text(get_string('coursepageblocklayoutbuilderdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));

// Course page top / bottom block regions enabled.
$name = 'theme_fliplearn/coursepageblocksenabled';
$title = get_string('coursepageblocksenabled', 'theme_fliplearn');
$description = get_string('coursepageblocksenableddesc', 'theme_fliplearn');
$setting = new admin_setting_configcheckbox($name, $title, $description, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Heading for adding space between settings.
$temp->add(new admin_setting_heading('temp1', '', "<br>"));

// Course page top block region builder.
$noregions = 4; // Number of block regions defined in config.php.
$totalblocks = 0;
$imgpath = $CFG->wwwroot.'/theme/fliplearn/pix/layout-builder/';
$imgblder = '';

$name = 'theme_fliplearn/coursepageblocklayoutlayouttoprow1';
$title = get_string('coursepageblocklayoutlayouttoprow', 'theme_fliplearn');
$description = get_string('coursepageblocklayoutlayouttoprowdesc', 'theme_fliplearn');
$default = $bootstrap12defaults[0];
$choices = $bootstrap12;
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$settingname = 'coursepageblocklayoutlayouttoprow1';

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

$temp->add(new admin_setting_heading('layout_heading1', '', "<h4>" . get_string('layoutcheck', 'theme_fliplearn') . "</h4>"));

$checkcountcolor = '#00695C';
if ($totalblocks > $noregions) {
    $mktcountcolor = '#D7542A';
}
$mktcountmsg = '<span style="color: ' . $checkcountcolor . '; margin-bottom: 20px;">';
$mktcountmsg .= get_string('layoutcount1', 'theme_fliplearn') . '<strong>' . $noregions . '</strong>';
$mktcountmsg .= get_string('layoutcount2', 'theme_fliplearn') . '<strong>' . $totalblocks . '/' . $noregions . '</strong></span>.';

$temp->add(new admin_setting_heading('theme_fliplearn_courselayouttopblockscount', '', $mktcountmsg));

$temp->add(new admin_setting_heading('theme_fliplearn_courselayouttopbuilder', '', $imgblder . "<br><br><br><br>"));

// Course page bottom  block region builder.
$noregions = 4; // Number of block regions defined in config.php.
$totalblocks = 0;
$imgpath = $CFG->wwwroot.'/theme/fliplearn/pix/layout-builder/';
$imgblder = '';

$name = 'theme_fliplearn/coursepageblocklayoutlayoutbottomrow2';
$title = get_string('coursepageblocklayoutlayoutbottomrow', 'theme_fliplearn');
$description = get_string('coursepageblocklayoutlayoutbottomrowdesc', 'theme_fliplearn');
$default = $bootstrap12defaults[0];
$choices = $bootstrap12;
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$settingname = 'coursepageblocklayoutlayoutbottomrow2';

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

$temp->add(new admin_setting_heading('layout_heading2', '', "<h4>" . get_string('layoutcheck', 'theme_fliplearn') . "</h4>"));

$checkcountcolor = '#00695C';
if ($totalblocks > $noregions) {
    $mktcountcolor = '#D7542A';
}
$mktcountmsg = '<span style="color: ' . $checkcountcolor . '">';
$mktcountmsg .= get_string('layoutcount1', 'theme_fliplearn') . '<strong>' . $noregions . '</strong>';
$mktcountmsg .= get_string('layoutcount2', 'theme_fliplearn') . '<strong>' . $totalblocks . '/' . $noregions . '</strong></span>.';

$temp->add(new admin_setting_heading('theme_fliplearn_courselayoutbottomblockscount', '', $mktcountmsg));

$temp->add(new admin_setting_heading('theme_fliplearn_courselayoutbottombuilder', '', $imgblder . "<br><br>"));

// Current course section background color.
$name = 'theme_fliplearn/coursesectionbgcolor';
$title = get_string('coursesectionbgcolor', 'theme_fliplearn');
$description = get_string('coursesectionbgcolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFFFFF', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Topics / Weeks course format heading.
$name = 'theme_fliplearn/settingstopicsweeks';
$heading = get_string('settingstopicsweeks', 'theme_fliplearn');
$setting = new admin_setting_heading($name, $heading, '');
$temp->add($setting);

// Course section heading background color.
$name = 'theme_fliplearn/coursesectionheaderbg';
$title = get_string('coursesectionheaderbg', 'theme_fliplearn');
$description = get_string('coursesectionheaderbgdesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFFFFF', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course section heading text color.
$name = 'theme_fliplearn/sectionheadingcolor';
$title = get_string('sectionheadingcolor', 'theme_fliplearn');
$description = get_string('sectionheadingcolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#3A454b', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Current course section header background color.
$name = 'theme_fliplearn/currentcolor';
$title = get_string('currentcolor', 'theme_fliplearn');
$description = get_string('currentcolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#d2f2ef', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course section header border bottom style.
$name = 'theme_fliplearn/coursesectionheaderborderstyle';
$title = get_string('coursesectionheaderborderstyle', 'theme_fliplearn');
$description = get_string('coursesectionheaderborderstyledesc', 'theme_fliplearn');
$radchoices = $borderstyles;
$setting = new admin_setting_configselect($name, $title, $description, 'none', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course section header border bottom color.
$name = 'theme_fliplearn/coursesectionheaderbordercolor';
$title = get_string('coursesectionheaderbordercolor', 'theme_fliplearn');
$description = get_string('coursesectionheaderbordercolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#F3F3F3', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course section header border bottom width.
$name = 'theme_fliplearn/coursesectionheaderborderwidth';
$title = get_string('coursesectionheaderborderwidth', 'theme_fliplearn');
$description = get_string('coursesectionheaderborderwidthdesc', 'theme_fliplearn');
$radchoices = $from0to6px;;
$setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course section border radius.
$name = 'theme_fliplearn/coursesectionheaderborderradiustop';
$title = get_string('coursesectionheaderborderradiustop', 'theme_fliplearn');
$description = get_string('coursesectionheaderborderradiustopdesc', 'theme_fliplearn');
$radchoices = $from0to50px;
$setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course section border radius.
$name = 'theme_fliplearn/coursesectionheaderborderradiusbottom';
$title = get_string('coursesectionheaderborderradiusbottom', 'theme_fliplearn');
$description = get_string('coursesectionheaderborderradiusbottomdesc', 'theme_fliplearn');
$radchoices = $from0to50px;
$setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course section border style.
$name = 'theme_fliplearn/coursesectionborderstyle';
$title = get_string('coursesectionborderstyle', 'theme_fliplearn');
$description = get_string('coursesectionborderstyledesc', 'theme_fliplearn');
$radchoices = $borderstyles;
$setting = new admin_setting_configselect($name, $title, $description, 'solid', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course section border width.
$name = 'theme_fliplearn/coursesectionborderwidth';
$title = get_string('coursesectionborderwidth', 'theme_fliplearn');
$description = get_string('coursesectionborderwidthdesc', 'theme_fliplearn');
$radchoices = $from0to6px;;
$setting = new admin_setting_configselect($name, $title, $description, '1px', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course section border color.
$name = 'theme_fliplearn/coursesectionbordercolor';
$title = get_string('coursesectionbordercolor', 'theme_fliplearn');
$description = get_string('coursesectionbordercolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#e8eaeb', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course section border radius.
$name = 'theme_fliplearn/coursesectionborderradius';
$title = get_string('coursesectionborderradius', 'theme_fliplearn');
$description = get_string('coursesectionborderradiusdesc', 'theme_fliplearn');
$radchoices = $from0to50px;
$setting = new admin_setting_configselect($name, $title, $description, '0px', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Activity display colours.

// Course Activity section heading.
$name = 'theme_fliplearn/coursesectionactivitycolors';
$heading = get_string('coursesectionactivitycolors', 'theme_fliplearn');
$setting = new admin_setting_heading($name, $heading, '');
$temp->add($setting);

// Use Adaptable icons.
$name = 'theme_fliplearn/coursesectionactivityusefliplearnicons';
$title = get_string('coursesectionactivityusefliplearnicons', 'theme_fliplearn');
$description = get_string('coursesectionactivityusefliplearniconsdesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Default icon size.
$name = 'theme_fliplearn/coursesectionactivityiconsize';
$title = get_string('coursesectionactivityiconsize', 'theme_fliplearn');
$description = get_string('coursesectionactivityiconsizedesc', 'theme_fliplearn');
$setting = new admin_setting_configtext($name, $title, $description, '24px');
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course Activity section bottom border width.
// This border was originally used all around an activity but changed to just the bottom.
$name = 'theme_fliplearn/coursesectionactivityborderwidth';
$title = get_string('coursesectionactivityborderwidth', 'theme_fliplearn');
$description = get_string('coursesectionactivityborderwidthdesc', 'theme_fliplearn');
$widthchoices = $from0to6px;
$setting = new admin_setting_configselect($name, $title, $description, '2px', $widthchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course Activity section bottom border style.
$name = 'theme_fliplearn/coursesectionactivityborderstyle';
$title = get_string('coursesectionactivityborderstyle', 'theme_fliplearn');
$description = get_string('coursesectionactivityborderstyledesc', 'theme_fliplearn');
$radchoices = $borderstyles;
$setting = new admin_setting_configselect($name, $title, $description, 'dashed', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course Activity section bottom border colour.
$name = 'theme_fliplearn/coursesectionactivitybordercolor';
$title = get_string('coursesectionactivitybordercolor', 'theme_fliplearn');
$description = get_string('coursesectionactivitybordercolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#eeeeee', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course Activity section left border width.  Controls width of all left borders.
$name = 'theme_fliplearn/coursesectionactivityleftborderwidth';
$title = get_string('coursesectionactivityleftborderwidth', 'theme_fliplearn');
$description = get_string('coursesectionactivityleftborderwidthdesc', 'theme_fliplearn');
$widthchoices = $from0to6px;
$setting = new admin_setting_configselect($name, $title, $description, '3px', $widthchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Assign Activity display colours.
$name = 'theme_fliplearn/coursesectionactivityassignleftbordercolor';
$title = get_string('coursesectionactivityassignleftbordercolor', 'theme_fliplearn');
$description = get_string('coursesectionactivityassignleftbordercolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#0066cc', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Assign Activity background colour.
$name = 'theme_fliplearn/coursesectionactivityassignbgcolor';
$title = get_string('coursesectionactivityassignbgcolor', 'theme_fliplearn');
$description = get_string('coursesectionactivityassignbgcolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFFFFF', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Forum Activity display colours.
$name = 'theme_fliplearn/coursesectionactivityforumleftbordercolor';
$title = get_string('coursesectionactivityforumleftbordercolor', 'theme_fliplearn');
$description = get_string('coursesectionactivityforumleftbordercolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#990099', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Forum Activity background colour.
$name = 'theme_fliplearn/coursesectionactivityforumbgcolor';
$title = get_string('coursesectionactivityforumbgcolor', 'theme_fliplearn');
$description = get_string('coursesectionactivityforumbgcolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFFFFF', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Quiz Activity display colours.
$name = 'theme_fliplearn/coursesectionactivityquizleftbordercolor';
$title = get_string('coursesectionactivityquizleftbordercolor', 'theme_fliplearn');
$description = get_string('coursesectionactivityquizleftbordercolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#FF3333', $previewconfig);
$temp->add($setting);

// Quiz Activity background colour.
$name = 'theme_fliplearn/coursesectionactivityquizbgcolor';
$title = get_string('coursesectionactivityquizbgcolor', 'theme_fliplearn');
$description = get_string('coursesectionactivityquizbgcolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#FFFFFF', $previewconfig);
$temp->add($setting);

// Top and bottom margin spacing between activities.
$name = 'theme_fliplearn/coursesectionactivitymargintop';
$title = get_string('coursesectionactivitymargintop', 'theme_fliplearn');
$description = get_string('coursesectionactivitymargintopdesc', 'theme_fliplearn');
$widthchoices = $from0to12px;
$setting = new admin_setting_configselect($name, $title, $description, '2px', $widthchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/coursesectionactivitymarginbottom';
$title = get_string('coursesectionactivitymarginbottom', 'theme_fliplearn');
$description = get_string('coursesectionactivitymarginbottomdesc', 'theme_fliplearn');
$widthchoices = $from0to12px;
$setting = new admin_setting_configselect($name, $title, $description, '2px', $widthchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// SocialWall course format heading.
$name = 'theme_fliplearn/socialwall';
$heading = get_string('socialwall', 'theme_fliplearn');
$setting = new admin_setting_heading($name, $heading, '');
$temp->add($setting);

// Socialwall background color.
$name = 'theme_fliplearn/socialwallbackgroundcolor';
$title = get_string('socialwallbackgroundcolor', 'theme_fliplearn');
$description = get_string('socialwallbackgroundcolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Social Wall section border color.
$name = 'theme_fliplearn/socialwallbordercolor';
$title = get_string('socialwallbordercolor', 'theme_fliplearn');
$description = get_string('socialwallbordercolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#B9B9B9', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Social Wall section border style.
$name = 'theme_fliplearn/socialwallbordertopstyle';
$title = get_string('socialwallbordertopstyle', 'theme_fliplearn');
$description = get_string('socialwallbordertopstyledesc', 'theme_fliplearn');
$radchoices = $borderstyles;
$setting = new admin_setting_configselect($name, $title, $description, 'solid', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Social Wall section border width.
$name = 'theme_fliplearn/socialwallborderwidth';
$title = get_string('socialwallborderwidth', 'theme_fliplearn');
$description = get_string('socialwallborderwidthdesc', 'theme_fliplearn');
$radchoices = $from0to12px;
$setting = new admin_setting_configselect($name, $title, $description, '2px', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Social Wall section border radius.
$name = 'theme_fliplearn/socialwallsectionradius';
$title = get_string('socialwallsectionradius', 'theme_fliplearn');
$description = get_string('socialwallsectionradiusdesc', 'theme_fliplearn');
$radchoices = $from0to12px;
$setting = new admin_setting_configselect($name, $title, $description, '6px', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Social Wall action link color.
$name = 'theme_fliplearn/socialwallactionlinkcolor';
$title = get_string('socialwallactionlinkcolor', 'theme_fliplearn');
$description = get_string('socialwallactionlinkcolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#51666C', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Social Wall hover link color.
$name = 'theme_fliplearn/socialwallactionlinkhovercolor';
$title = get_string('socialwallactionlinkhovercolor', 'theme_fliplearn');
$description = get_string('socialwallactionlinkhovercolordesc', 'theme_fliplearn');
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#009688', $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Course Activity Further Information section heading.
$name = 'theme_fliplearn/coursesectionactivityfurtherinformation';
$heading = get_string('coursesectionactivityfurtherinformation', 'theme_fliplearn');
$setting = new admin_setting_heading($name, $heading, '');
$temp->add($setting);

$name = 'theme_fliplearn/coursesectionactivityfurtherinformationassign';
$title = get_string('coursesectionactivityfurtherinformationassign', 'theme_fliplearn');
$description = get_string('coursesectionactivityfurtherinformationassigndesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/coursesectionactivityfurtherinformationquiz';
$title = get_string('coursesectionactivityfurtherinformationquiz', 'theme_fliplearn');
$description = get_string('coursesectionactivityfurtherinformationquizdesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/coursesectionactivityfurtherinformationchoice';
$title = get_string('coursesectionactivityfurtherinformationchoice', 'theme_fliplearn');
$description = get_string('coursesectionactivityfurtherinformationchoicedesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/coursesectionactivityfurtherinformationfeedback';
$title = get_string('coursesectionactivityfurtherinformationfeedback', 'theme_fliplearn');
$description = get_string('coursesectionactivityfurtherinformationfeedbackdesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/coursesectionactivityfurtherinformationlesson';
$title = get_string('coursesectionactivityfurtherinformationlesson', 'theme_fliplearn');
$description = get_string('coursesectionactivityfurtherinformationlessondesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/coursesectionactivityfurtherinformationdata';
$title = get_string('coursesectionactivityfurtherinformationdata', 'theme_fliplearn');
$description = get_string('coursesectionactivityfurtherinformationdatadesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$ADMIN->add('theme_fliplearn', $temp);
