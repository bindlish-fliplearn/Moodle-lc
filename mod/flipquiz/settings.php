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
 * Administration settings definitions for the flipquiz module.
 *
 * @package   mod_flipquiz
 * @copyright 2010 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/flipquiz/lib.php');

// First get a list of flipquiz reports with there own settings pages. If there none,
// we use a simpler overall menu structure.
$reports = core_component::get_plugin_list_with_file('flipquiz', 'settings.php', false);
$reportsbyname = array();
foreach ($reports as $report => $reportdir) {
    $strreportname = get_string($report . 'report', 'flipquiz_'.$report);
    $reportsbyname[$strreportname] = $report;
}
core_collator::ksort($reportsbyname);

// First get a list of flipquiz reports with there own settings pages. If there none,
// we use a simpler overall menu structure.
$rules = core_component::get_plugin_list_with_file('flipquizaccess', 'settings.php', false);
$rulesbyname = array();
foreach ($rules as $rule => $ruledir) {
    $strrulename = get_string('pluginname', 'flipquizaccess_' . $rule);
    $rulesbyname[$strrulename] = $rule;
}
core_collator::ksort($rulesbyname);

// Create the flipquiz settings page.
if (empty($reportsbyname) && empty($rulesbyname)) {
    $pagetitle = get_string('modulename', 'flipquiz');
} else {
    $pagetitle = get_string('generalsettings', 'admin');
}
$flipquizsettings = new admin_settingpage('modsettingflipquiz', $pagetitle, 'moodle/site:config');

if ($ADMIN->fulltree) {
    // Introductory explanation that all the settings are defaults for the add flipquiz form.
    $flipquizsettings->add(new admin_setting_heading('flipquizintro', '', get_string('configintro', 'flipquiz')));

    // Time limit.
    $flipquizsettings->add(new admin_setting_configduration_with_advanced('flipquiz/timelimit',
            get_string('timelimit', 'flipquiz'), get_string('configtimelimitsec', 'flipquiz'),
            array('value' => '0', 'adv' => false), 60));

    // What to do with overdue attempts.
    $flipquizsettings->add(new mod_flipquiz_admin_setting_overduehandling('flipquiz/overduehandling',
            get_string('overduehandling', 'flipquiz'), get_string('overduehandling_desc', 'flipquiz'),
            array('value' => 'autosubmit', 'adv' => false), null));

    // Grace period time.
    $flipquizsettings->add(new admin_setting_configduration_with_advanced('flipquiz/graceperiod',
            get_string('graceperiod', 'flipquiz'), get_string('graceperiod_desc', 'flipquiz'),
            array('value' => '86400', 'adv' => false)));

    // Minimum grace period used behind the scenes.
    $flipquizsettings->add(new admin_setting_configduration('flipquiz/graceperiodmin',
            get_string('graceperiodmin', 'flipquiz'), get_string('graceperiodmin_desc', 'flipquiz'),
            60, 1));

    // Number of attempts.
    $options = array(get_string('unlimited'));
    for ($i = 1; $i <= FLIPQUIZ_MAX_ATTEMPT_OPTION; $i++) {
        $options[$i] = $i;
    }
    $flipquizsettings->add(new admin_setting_configselect_with_advanced('flipquiz/attempts',
            get_string('attemptsallowed', 'flipquiz'), get_string('configattemptsallowed', 'flipquiz'),
            array('value' => 0, 'adv' => false), $options));

    // Grading method.
    $flipquizsettings->add(new mod_flipquiz_admin_setting_grademethod('flipquiz/grademethod',
            get_string('grademethod', 'flipquiz'), get_string('configgrademethod', 'flipquiz'),
            array('value' => FLIPQUIZ_GRADEHIGHEST, 'adv' => false), null));

    // Maximum grade.
    $flipquizsettings->add(new admin_setting_configtext('flipquiz/maximumgrade',
            get_string('maximumgrade'), get_string('configmaximumgrade', 'flipquiz'), 10, PARAM_INT));

    // Questions per page.
    $perpage = array();
    $perpage[0] = get_string('never');
    $perpage[1] = get_string('aftereachquestion', 'flipquiz');
    for ($i = 2; $i <= FLIPQUIZ_MAX_QPP_OPTION; ++$i) {
        $perpage[$i] = get_string('afternquestions', 'flipquiz', $i);
    }
    $flipquizsettings->add(new admin_setting_configselect_with_advanced('flipquiz/questionsperpage',
            get_string('newpageevery', 'flipquiz'), get_string('confignewpageevery', 'flipquiz'),
            array('value' => 1, 'adv' => false), $perpage));

    // Navigation method.
    $flipquizsettings->add(new admin_setting_configselect_with_advanced('flipquiz/navmethod',
            get_string('navmethod', 'flipquiz'), get_string('confignavmethod', 'flipquiz'),
            array('value' => FLIPQUIZ_NAVMETHOD_FREE, 'adv' => true), flipquiz_get_navigation_options()));

    // Shuffle within questions.
    $flipquizsettings->add(new admin_setting_configcheckbox_with_advanced('flipquiz/shuffleanswers',
            get_string('shufflewithin', 'flipquiz'), get_string('configshufflewithin', 'flipquiz'),
            array('value' => 1, 'adv' => false)));

    // Preferred behaviour.
    $flipquizsettings->add(new admin_setting_question_behaviour('flipquiz/preferredbehaviour',
            get_string('howquestionsbehave', 'question'), get_string('howquestionsbehave_desc', 'flipquiz'),
            'deferredfeedback'));

    // Can redo completed questions.
    $flipquizsettings->add(new admin_setting_configselect_with_advanced('flipquiz/canredoquestions',
            get_string('canredoquestions', 'flipquiz'), get_string('canredoquestions_desc', 'flipquiz'),
            array('value' => 0, 'adv' => true),
            array(0 => get_string('no'), 1 => get_string('canredoquestionsyes', 'flipquiz'))));

    // Each attempt builds on last.
    $flipquizsettings->add(new admin_setting_configcheckbox_with_advanced('flipquiz/attemptonlast',
            get_string('eachattemptbuildsonthelast', 'flipquiz'),
            get_string('configeachattemptbuildsonthelast', 'flipquiz'),
            array('value' => 0, 'adv' => true)));

    // Review options.
    $flipquizsettings->add(new admin_setting_heading('reviewheading',
            get_string('reviewoptionsheading', 'flipquiz'), ''));
    foreach (mod_flipquiz_admin_review_setting::fields() as $field => $name) {
        $default = mod_flipquiz_admin_review_setting::all_on();
        $forceduring = null;
        if ($field == 'attempt') {
            $forceduring = true;
        } else if ($field == 'overallfeedback') {
            $default = $default ^ mod_flipquiz_admin_review_setting::DURING;
            $forceduring = false;
        }
        $flipquizsettings->add(new mod_flipquiz_admin_review_setting('flipquiz/review' . $field,
                $name, '', $default, $forceduring));
    }

    // Show the user's picture.
    $flipquizsettings->add(new mod_flipquiz_admin_setting_user_image('flipquiz/showuserpicture',
            get_string('showuserpicture', 'flipquiz'), get_string('configshowuserpicture', 'flipquiz'),
            array('value' => 0, 'adv' => false), null));

    // Decimal places for overall grades.
    $options = array();
    for ($i = 0; $i <= FLIPQUIZ_MAX_DECIMAL_OPTION; $i++) {
        $options[$i] = $i;
    }
    $flipquizsettings->add(new admin_setting_configselect_with_advanced('flipquiz/decimalpoints',
            get_string('decimalplaces', 'flipquiz'), get_string('configdecimalplaces', 'flipquiz'),
            array('value' => 2, 'adv' => false), $options));

    // Decimal places for question grades.
    $options = array(-1 => get_string('sameasoverall', 'flipquiz'));
    for ($i = 0; $i <= FLIPQUIZ_MAX_Q_DECIMAL_OPTION; $i++) {
        $options[$i] = $i;
    }
    $flipquizsettings->add(new admin_setting_configselect_with_advanced('flipquiz/questiondecimalpoints',
            get_string('decimalplacesquestion', 'flipquiz'),
            get_string('configdecimalplacesquestion', 'flipquiz'),
            array('value' => -1, 'adv' => true), $options));

    // Show blocks during flipquiz attempts.
    $flipquizsettings->add(new admin_setting_configcheckbox_with_advanced('flipquiz/showblocks',
            get_string('showblocks', 'flipquiz'), get_string('configshowblocks', 'flipquiz'),
            array('value' => 0, 'adv' => true)));

    // Password.
    $flipquizsettings->add(new admin_setting_configtext_with_advanced('flipquiz/password',
            get_string('requirepassword', 'flipquiz'), get_string('configrequirepassword', 'flipquiz'),
            array('value' => '', 'adv' => false), PARAM_TEXT));

    // IP restrictions.
    $flipquizsettings->add(new admin_setting_configtext_with_advanced('flipquiz/subnet',
            get_string('requiresubnet', 'flipquiz'), get_string('configrequiresubnet', 'flipquiz'),
            array('value' => '', 'adv' => true), PARAM_TEXT));

    // Enforced delay between attempts.
    $flipquizsettings->add(new admin_setting_configduration_with_advanced('flipquiz/delay1',
            get_string('delay1st2nd', 'flipquiz'), get_string('configdelay1st2nd', 'flipquiz'),
            array('value' => 0, 'adv' => true), 60));
    $flipquizsettings->add(new admin_setting_configduration_with_advanced('flipquiz/delay2',
            get_string('delaylater', 'flipquiz'), get_string('configdelaylater', 'flipquiz'),
            array('value' => 0, 'adv' => true), 60));

    // Browser security.
    $flipquizsettings->add(new mod_flipquiz_admin_setting_browsersecurity('flipquiz/browsersecurity',
            get_string('showinsecurepopup', 'flipquiz'), get_string('configpopup', 'flipquiz'),
            array('value' => '-', 'adv' => true), null));

    $flipquizsettings->add(new admin_setting_configtext('flipquiz/initialnumfeedbacks',
            get_string('initialnumfeedbacks', 'flipquiz'), get_string('initialnumfeedbacks_desc', 'flipquiz'),
            2, PARAM_INT, 5));

    // Allow user to specify if setting outcomes is an advanced setting.
    if (!empty($CFG->enableoutcomes)) {
        $flipquizsettings->add(new admin_setting_configcheckbox('flipquiz/outcomes_adv',
            get_string('outcomesadvanced', 'flipquiz'), get_string('configoutcomesadvanced', 'flipquiz'),
            '0'));
    }

    // Autosave frequency.
    $flipquizsettings->add(new admin_setting_configduration('flipquiz/autosaveperiod',
            get_string('autosaveperiod', 'flipquiz'), get_string('autosaveperiod_desc', 'flipquiz'), 60, 1));
}

// Now, depending on whether any reports have their own settings page, add
// the flipquiz setting page to the appropriate place in the tree.
if (empty($reportsbyname) && empty($rulesbyname)) {
    $ADMIN->add('modsettings', $flipquizsettings);
} else {
    $ADMIN->add('modsettings', new admin_category('modsettingsflipquizcat',
            get_string('modulename', 'flipquiz'), $module->is_enabled() === false));
    $ADMIN->add('modsettingsflipquizcat', $flipquizsettings);

    // Add settings pages for the flipquiz report subplugins.
    foreach ($reportsbyname as $strreportname => $report) {
        $reportname = $report;

        $settings = new admin_settingpage('modsettingsflipquizcat'.$reportname,
                $strreportname, 'moodle/site:config', $module->is_enabled() === false);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/flipquiz/report/$reportname/settings.php");
        }
        if (!empty($settings)) {
            $ADMIN->add('modsettingsflipquizcat', $settings);
        }
    }

    // Add settings pages for the flipquiz access rule subplugins.
    foreach ($rulesbyname as $strrulename => $rule) {
        $settings = new admin_settingpage('modsettingsflipquizcat' . $rule,
                $strrulename, 'moodle/site:config', $module->is_enabled() === false);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/flipquiz/accessrule/$rule/settings.php");
        }
        if (!empty($settings)) {
            $ADMIN->add('modsettingsflipquizcat', $settings);
        }
    }
}

$settings = null; // We do not want standard settings link.
