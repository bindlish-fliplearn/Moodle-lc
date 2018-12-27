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
 * Administration settings definitions for the pratest module.
 *
 * @package   mod_pratest
 * @copyright 2010 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pratest/lib.php');

// First get a list of pratest reports with there own settings pages. If there none,
// we use a simpler overall menu structure.
$reports = core_component::get_plugin_list_with_file('pratest', 'settings.php', false);
$reportsbyname = array();
foreach ($reports as $report => $reportdir) {
    $strreportname = get_string($report . 'report', 'pratest_'.$report);
    $reportsbyname[$strreportname] = $report;
}
core_collator::ksort($reportsbyname);

// First get a list of pratest reports with there own settings pages. If there none,
// we use a simpler overall menu structure.
$rules = core_component::get_plugin_list_with_file('pratestaccess', 'settings.php', false);
$rulesbyname = array();
foreach ($rules as $rule => $ruledir) {
    $strrulename = get_string('pluginname', 'pratestaccess_' . $rule);
    $rulesbyname[$strrulename] = $rule;
}
core_collator::ksort($rulesbyname);

// Create the pratest settings page.
if (empty($reportsbyname) && empty($rulesbyname)) {
    $pagetitle = get_string('modulename', 'pratest');
} else {
    $pagetitle = get_string('generalsettings', 'admin');
}
$pratestsettings = new admin_settingpage('modsettingpratest', $pagetitle, 'moodle/site:config');

if ($ADMIN->fulltree) {
    // Introductory explanation that all the settings are defaults for the add pratest form.
    $pratestsettings->add(new admin_setting_heading('pratestintro', '', get_string('configintro', 'pratest')));

    // Time limit.
    $pratestsettings->add(new admin_setting_configduration_with_advanced('pratest/timelimit',
            get_string('timelimit', 'pratest'), get_string('configtimelimitsec', 'pratest'),
            array('value' => '0', 'adv' => false), 60));

    // What to do with overdue attempts.
    $pratestsettings->add(new mod_pratest_admin_setting_overduehandling('pratest/overduehandling',
            get_string('overduehandling', 'pratest'), get_string('overduehandling_desc', 'pratest'),
            array('value' => 'autosubmit', 'adv' => false), null));

    // Grace period time.
    $pratestsettings->add(new admin_setting_configduration_with_advanced('pratest/graceperiod',
            get_string('graceperiod', 'pratest'), get_string('graceperiod_desc', 'pratest'),
            array('value' => '86400', 'adv' => false)));

    // Minimum grace period used behind the scenes.
    $pratestsettings->add(new admin_setting_configduration('pratest/graceperiodmin',
            get_string('graceperiodmin', 'pratest'), get_string('graceperiodmin_desc', 'pratest'),
            60, 1));

    // Number of attempts.
    $options = array(get_string('unlimited'));
    for ($i = 1; $i <= QUIZ_MAX_ATTEMPT_OPTION; $i++) {
        $options[$i] = $i;
    }
    $pratestsettings->add(new admin_setting_configselect_with_advanced('pratest/attempts',
            get_string('attemptsallowed', 'pratest'), get_string('configattemptsallowed', 'pratest'),
            array('value' => 0, 'adv' => false), $options));

    // Grading method.
    $pratestsettings->add(new mod_pratest_admin_setting_grademethod('pratest/grademethod',
            get_string('grademethod', 'pratest'), get_string('configgrademethod', 'pratest'),
            array('value' => QUIZ_GRADEHIGHEST, 'adv' => false), null));

    // Maximum grade.
    $pratestsettings->add(new admin_setting_configtext('pratest/maximumgrade',
            get_string('maximumgrade'), get_string('configmaximumgrade', 'pratest'), 10, PARAM_INT));

    // Questions per page.
    $perpage = array();
    $perpage[0] = get_string('never');
    $perpage[1] = get_string('aftereachquestion', 'pratest');
    for ($i = 2; $i <= QUIZ_MAX_QPP_OPTION; ++$i) {
        $perpage[$i] = get_string('afternquestions', 'pratest', $i);
    }
    $pratestsettings->add(new admin_setting_configselect_with_advanced('pratest/questionsperpage',
            get_string('newpageevery', 'pratest'), get_string('confignewpageevery', 'pratest'),
            array('value' => 1, 'adv' => false), $perpage));

    // Navigation method.
    $pratestsettings->add(new admin_setting_configselect_with_advanced('pratest/navmethod',
            get_string('navmethod', 'pratest'), get_string('confignavmethod', 'pratest'),
            array('value' => QUIZ_NAVMETHOD_FREE, 'adv' => true), pratest_get_navigation_options()));

    // Shuffle within questions.
    $pratestsettings->add(new admin_setting_configcheckbox_with_advanced('pratest/shuffleanswers',
            get_string('shufflewithin', 'pratest'), get_string('configshufflewithin', 'pratest'),
            array('value' => 1, 'adv' => false)));

    // Preferred behaviour.
    $pratestsettings->add(new admin_setting_question_behaviour('pratest/preferredbehaviour',
            get_string('howquestionsbehave', 'question'), get_string('howquestionsbehave_desc', 'pratest'),
            'deferredfeedback'));

    // Can redo completed questions.
    $pratestsettings->add(new admin_setting_configselect_with_advanced('pratest/canredoquestions',
            get_string('canredoquestions', 'pratest'), get_string('canredoquestions_desc', 'pratest'),
            array('value' => 0, 'adv' => true),
            array(0 => get_string('no'), 1 => get_string('canredoquestionsyes', 'pratest'))));

    // Each attempt builds on last.
    $pratestsettings->add(new admin_setting_configcheckbox_with_advanced('pratest/attemptonlast',
            get_string('eachattemptbuildsonthelast', 'pratest'),
            get_string('configeachattemptbuildsonthelast', 'pratest'),
            array('value' => 0, 'adv' => true)));

    // Review options.
    $pratestsettings->add(new admin_setting_heading('reviewheading',
            get_string('reviewoptionsheading', 'pratest'), ''));
    foreach (mod_pratest_admin_review_setting::fields() as $field => $name) {
        $default = mod_pratest_admin_review_setting::all_on();
        $forceduring = null;
        if ($field == 'attempt') {
            $forceduring = true;
        } else if ($field == 'overallfeedback') {
            $default = $default ^ mod_pratest_admin_review_setting::DURING;
            $forceduring = false;
        }
        $pratestsettings->add(new mod_pratest_admin_review_setting('pratest/review' . $field,
                $name, '', $default, $forceduring));
    }

    // Show the user's picture.
    $pratestsettings->add(new mod_pratest_admin_setting_user_image('pratest/showuserpicture',
            get_string('showuserpicture', 'pratest'), get_string('configshowuserpicture', 'pratest'),
            array('value' => 0, 'adv' => false), null));

    // Decimal places for overall grades.
    $options = array();
    for ($i = 0; $i <= QUIZ_MAX_DECIMAL_OPTION; $i++) {
        $options[$i] = $i;
    }
    $pratestsettings->add(new admin_setting_configselect_with_advanced('pratest/decimalpoints',
            get_string('decimalplaces', 'pratest'), get_string('configdecimalplaces', 'pratest'),
            array('value' => 2, 'adv' => false), $options));

    // Decimal places for question grades.
    $options = array(-1 => get_string('sameasoverall', 'pratest'));
    for ($i = 0; $i <= QUIZ_MAX_Q_DECIMAL_OPTION; $i++) {
        $options[$i] = $i;
    }
    $pratestsettings->add(new admin_setting_configselect_with_advanced('pratest/questiondecimalpoints',
            get_string('decimalplacesquestion', 'pratest'),
            get_string('configdecimalplacesquestion', 'pratest'),
            array('value' => -1, 'adv' => true), $options));

    // Show blocks during pratest attempts.
    $pratestsettings->add(new admin_setting_configcheckbox_with_advanced('pratest/showblocks',
            get_string('showblocks', 'pratest'), get_string('configshowblocks', 'pratest'),
            array('value' => 0, 'adv' => true)));

    // Password.
    $pratestsettings->add(new admin_setting_configtext_with_advanced('pratest/password',
            get_string('requirepassword', 'pratest'), get_string('configrequirepassword', 'pratest'),
            array('value' => '', 'adv' => false), PARAM_TEXT));

    // IP restrictions.
    $pratestsettings->add(new admin_setting_configtext_with_advanced('pratest/subnet',
            get_string('requiresubnet', 'pratest'), get_string('configrequiresubnet', 'pratest'),
            array('value' => '', 'adv' => true), PARAM_TEXT));

    // Enforced delay between attempts.
    $pratestsettings->add(new admin_setting_configduration_with_advanced('pratest/delay1',
            get_string('delay1st2nd', 'pratest'), get_string('configdelay1st2nd', 'pratest'),
            array('value' => 0, 'adv' => true), 60));
    $pratestsettings->add(new admin_setting_configduration_with_advanced('pratest/delay2',
            get_string('delaylater', 'pratest'), get_string('configdelaylater', 'pratest'),
            array('value' => 0, 'adv' => true), 60));

    // Browser security.
    $pratestsettings->add(new mod_pratest_admin_setting_browsersecurity('pratest/browsersecurity',
            get_string('showinsecurepopup', 'pratest'), get_string('configpopup', 'pratest'),
            array('value' => '-', 'adv' => true), null));

    $pratestsettings->add(new admin_setting_configtext('pratest/initialnumfeedbacks',
            get_string('initialnumfeedbacks', 'pratest'), get_string('initialnumfeedbacks_desc', 'pratest'),
            2, PARAM_INT, 5));

    // Allow user to specify if setting outcomes is an advanced setting.
    if (!empty($CFG->enableoutcomes)) {
        $pratestsettings->add(new admin_setting_configcheckbox('pratest/outcomes_adv',
            get_string('outcomesadvanced', 'pratest'), get_string('configoutcomesadvanced', 'pratest'),
            '0'));
    }

    // Autosave frequency.
    $pratestsettings->add(new admin_setting_configduration('pratest/autosaveperiod',
            get_string('autosaveperiod', 'pratest'), get_string('autosaveperiod_desc', 'pratest'), 60, 1));
}

// Now, depending on whether any reports have their own settings page, add
// the pratest setting page to the appropriate place in the tree.
if (empty($reportsbyname) && empty($rulesbyname)) {
    $ADMIN->add('modsettings', $pratestsettings);
} else {
    $ADMIN->add('modsettings', new admin_category('modsettingspratestcat',
            get_string('modulename', 'pratest'), $module->is_enabled() === false));
    $ADMIN->add('modsettingspratestcat', $pratestsettings);

    // Add settings pages for the pratest report subplugins.
    foreach ($reportsbyname as $strreportname => $report) {
        $reportname = $report;

        $settings = new admin_settingpage('modsettingspratestcat'.$reportname,
                $strreportname, 'moodle/site:config', $module->is_enabled() === false);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/pratest/report/$reportname/settings.php");
        }
        if (!empty($settings)) {
            $ADMIN->add('modsettingspratestcat', $settings);
        }
    }

    // Add settings pages for the pratest access rule subplugins.
    foreach ($rulesbyname as $strrulename => $rule) {
        $settings = new admin_settingpage('modsettingspratestcat' . $rule,
                $strrulename, 'moodle/site:config', $module->is_enabled() === false);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/pratest/accessrule/$rule/settings.php");
        }
        if (!empty($settings)) {
            $ADMIN->add('modsettingspratestcat', $settings);
        }
    }
}

$settings = null; // We do not want standard settings link.
