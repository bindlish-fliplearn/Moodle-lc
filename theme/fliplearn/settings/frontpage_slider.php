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

// Frontpage Slider.
$temp = new admin_settingpage('theme_fliplearn_frontpage_slider', get_string('frontpageslidersettings', 'theme_fliplearn'));

$temp->add(new admin_setting_heading('theme_fliplearn_slideshow', get_string('slideshowsettingsheading', 'theme_fliplearn'),
    format_text(get_string('slideshowdesc', 'theme_fliplearn') .
        get_string('slideroption2snippet', 'theme_fliplearn'), FORMAT_MARKDOWN)));

$name = 'theme_fliplearn/sliderenabled';
$title = get_string('sliderenabled', 'theme_fliplearn');
$description = get_string('sliderenableddesc', 'theme_fliplearn');
$setting = new admin_setting_configcheckbox($name, $title, $description, 0);
$temp->add($setting);

$name = 'theme_fliplearn/sliderfullscreen';
$title = get_string('sliderfullscreen', 'theme_fliplearn');
$description = get_string('sliderfullscreendesc', 'theme_fliplearn');
$setting = new admin_setting_configcheckbox($name, $title, $description, 0);
$temp->add($setting);

$name = 'theme_fliplearn/slidermargintop';
$title = get_string('slidermargintop', 'theme_fliplearn');
$description = get_string('slidermargintopdesc', 'theme_fliplearn');
$radchoices = $from0to20px;
$setting = new admin_setting_configselect($name, $title, $description, '20px', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/slidermarginbottom';
$title = get_string('slidermarginbottom', 'theme_fliplearn');
$description = get_string('slidermarginbottomdesc', 'theme_fliplearn');
$radchoices = $from0to20px;
$setting = new admin_setting_configselect($name, $title, $description, '20px', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_fliplearn/slideroption2';
$title = get_string('slideroption2', 'theme_fliplearn');
$description = get_string('slideroption2desc', 'theme_fliplearn');
$radchoices = $sliderstyles;
$setting = new admin_setting_configselect($name, $title, $description, 'nocaptions', $radchoices);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

if (!isset($PAGE->theme->settings->slideroption2)) {
    $PAGE->theme->settings->slideroption2 = 'slider1';
}

if ($PAGE->theme->settings->slideroption2 == 'slider1') {
    $name = 'theme_fliplearn/sliderh3color';
    $title = get_string('sliderh3color', 'theme_fliplearn');
    $description = get_string('sliderh3colordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/sliderh4color';
    $title = get_string('sliderh4color', 'theme_fliplearn');
    $description = get_string('sliderh4colordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/slidersubmitcolor';
    $title = get_string('slidersubmitcolor', 'theme_fliplearn');
    $description = get_string('slidersubmitcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/slidersubmitbgcolor';
    $title = get_string('slidersubmitbgcolor', 'theme_fliplearn');
    $description = get_string('slidersubmitbgcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#51666C', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);
}

if ($PAGE->theme->settings->slideroption2 == 'slider2') {
    $name = 'theme_fliplearn/slider2h3color';
    $title = get_string('slider2h3color', 'theme_fliplearn');
    $description = get_string('slider2h3colordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/slider2h3bgcolor';
    $title = get_string('slider2h3bgcolor', 'theme_fliplearn');
    $description = get_string('slider2h3bgcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#000000', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/slider2h4color';
    $title = get_string('slider2h4color', 'theme_fliplearn');
    $description = get_string('slider2h4colordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#000000', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/slider2h4bgcolor';
    $title = get_string('slider2h4bgcolor', 'theme_fliplearn');
    $description = get_string('slider2h4bgcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/slideroption2submitcolor';
    $title = get_string('slideroption2submitcolor', 'theme_fliplearn');
    $description = get_string('slideroption2submitcolordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/slideroption2color';
    $title = get_string('slideroption2color', 'theme_fliplearn');
    $description = get_string('slideroption2colordesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#51666C', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/slideroption2a';
    $title = get_string('slideroption2a', 'theme_fliplearn');
    $description = get_string('slideroption2adesc', 'theme_fliplearn');
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#51666C', $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);
}

// Number of Sliders.
$name = 'theme_fliplearn/slidercount';
$title = get_string('slidercount', 'theme_fliplearn');
$description = get_string('slidercountdesc', 'theme_fliplearn');
$default = THEME_ADAPTABLE_DEFAULT_SLIDERCOUNT;
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices1to12);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// If we don't have an slide yet, default to the preset.
$slidercount = get_config('theme_fliplearn', 'slidercount');

if (!$slidercount) {
    $slidercount = THEME_ADAPTABLE_DEFAULT_SLIDERCOUNT;
}

for ($sliderindex = 1; $sliderindex <= $slidercount; $sliderindex++) {
    $fileid = 'p' . $sliderindex;
    $name = 'theme_fliplearn/p' . $sliderindex;
    $title = get_string('sliderimage', 'theme_fliplearn');
    $description = get_string('sliderimagedesc', 'theme_fliplearn');
    $setting = new admin_setting_configstoredfile($name, $title, $description, $fileid);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_fliplearn/p' . $sliderindex . 'url';
    $title = get_string('sliderurl', 'theme_fliplearn');
    $description = get_string('sliderurldesc', 'theme_fliplearn');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
    $temp->add($setting);

    $name = 'theme_fliplearn/p' . $sliderindex . 'cap';
    $title = get_string('slidercaption', 'theme_fliplearn');
    $description = get_string('slidercaptiondesc', 'theme_fliplearn');
    $default = '';
    $setting = new fliplearn_setting_confightmleditor($name, $title, $description, $default);
    $temp->add($setting);
}

$ADMIN->add('theme_fliplearn', $temp);
