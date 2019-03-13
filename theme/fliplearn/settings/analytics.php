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
 * @copyright  2015 David Bezemer <info@davidbezemer.nl>, www.davidbezemer.nl
 * @copyright  2016 COMETE (Paris Ouest University)
 * @author     David Bezemer <info@davidbezemer.nl>, Bas Brands <bmbrands@gmail.com>, Gavin Henrick <gavin@lts.ie>, COMETE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

// Analytics section.
$temp = new admin_settingpage('theme_fliplearn_analytics', get_string('analyticssettings', 'theme_fliplearn'));
$temp->add(new admin_setting_heading('theme_fliplearn_analytics', get_string('analyticssettingsheading', 'theme_fliplearn'),
           format_text(get_string('analyticssettingsdesc', 'theme_fliplearn'), FORMAT_MARKDOWN)));


// Google Analytics Section.
$name = 'theme_fliplearn/googleanalyticssettings';
$heading = get_string('googleanalyticssettings', 'theme_fliplearn');
$setting = new admin_setting_heading($name, $heading, '');
$temp->add($setting);

// Enable Google analytics.
$name = 'theme_fliplearn/enableanalytics';
$title = get_string('enableanalytics', 'theme_fliplearn');
$description = get_string('enableanalyticsdesc', 'theme_fliplearn');
$default = false;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Anonymize Google analytics.
$name = 'theme_fliplearn/anonymizega';
$title = get_string('anonymizega', 'theme_fliplearn');
$description = get_string('anonymizegadesc', 'theme_fliplearn');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// Number of Analytics entries.
$name = 'theme_fliplearn/analyticscount';
$title = get_string('analyticscount', 'theme_fliplearn');
$description = get_string('analyticscountdesc', 'theme_fliplearn');
$default = THEME_ADAPTABLE_DEFAULT_ANALYTICSCOUNT;
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices1to12);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

// If we don't have an analyticscount yet, default to the preset.
$analyticscount = get_config('theme_fliplearn', 'analyticscount');
if (!$analyticscount) {
    $alertcount = THEME_ADAPTABLE_DEFAULT_ANALYTICSCOUNT;
}

for ($analyticsindex = 1; $analyticsindex <= $analyticscount; $analyticsindex ++) {
    // Alert Text 1.
    $name = 'theme_fliplearn/analyticstext' . $analyticsindex;
    $title = get_string('analyticstext', 'theme_fliplearn');
    $description = get_string('analyticstextdesc', 'theme_fliplearn');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW);
    $temp->add($setting);

    $name = 'theme_fliplearn/analyticsprofilefield' . $analyticsindex;
    $title = get_string('analyticsprofilefield', 'theme_fliplearn');
    $description = get_string('analyticsprofilefielddesc', 'theme_fliplearn');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_RAW);
    $temp->add($setting);
}


// Piwik Analytics Section.
$name = 'theme_fliplearn/piwiksettings';
$heading = get_string('piwiksettings', 'theme_fliplearn');
$setting = new admin_setting_heading($name, $heading, '');
$temp->add($setting);


// Enable Piwik analytics.
$name = 'theme_fliplearn/piwikenabled';
$title = get_string('piwikenabled', 'theme_fliplearn');
$description = get_string('piwikenableddesc', 'theme_fliplearn');
$default = false;
$temp->add(new admin_setting_configcheckbox($name, $title, $description, $default, true, false));

// Piwik site ID.
$name = 'theme_fliplearn/piwiksiteid';
$title = get_string('piwiksiteid', 'theme_fliplearn');
$description = get_string('piwiksiteiddesc', 'theme_fliplearn');
$default = '1';
$temp->add(new admin_setting_configtext($name, $title, $description, $default));

// Piwik image track.
$name = 'theme_fliplearn/piwikimagetrack';
$title = get_string('piwikimagetrack', 'theme_fliplearn');
$description = get_string('piwikimagetrackdesc', 'theme_fliplearn');
$default = true;
$temp->add(new admin_setting_configcheckbox($name, $title, $description, $default, true, false));

// Piwik site URL.
$name = 'theme_fliplearn/piwiksiteurl';
$title = get_string('piwiksiteurl', 'theme_fliplearn');
$description = get_string('piwiksiteurldesc', 'theme_fliplearn');
$default = '';
$temp->add(new admin_setting_configtext($name, $title, $description, $default));

// Enable Piwik admins tracking.
$name = 'theme_fliplearn/piwiktrackadmin';
$title = get_string('piwiktrackadmin', 'theme_fliplearn');
$description = get_string('piwiktrackadmindesc', 'theme_fliplearn');
$default = false;
$temp->add(new admin_setting_configcheckbox($name, $title, $description, $default, true, false));


$ADMIN->add('theme_fliplearn', $temp);
