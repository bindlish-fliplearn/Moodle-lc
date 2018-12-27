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
 * Administration settings definitions for the clatest module.
 *
 * @package   mod_clatest
 * @copyright 2010 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/clatest/lib.php');

// First get a list of clatest reports with there own settings pages. If there none,
// we use a simpler overall menu structure.
$reports = core_component::get_plugin_list_with_file('clatest', 'settings.php', false);
$reportsbyname = array();
foreach ($reports as $report => $reportdir) {
    $strreportname = get_string($report . 'report', 'clatest_'.$report);
    $reportsbyname[$strreportname] = $report;
}
core_collator::ksort($reportsbyname);

// First get a list of clatest reports with there own settings pages. If there none,
// we use a simpler overall menu structure.
$rules = core_component::get_plugin_list_with_file('clatestaccess', 'settings.php', false);
$rulesbyname = array();
foreach ($rules as $rule => $ruledir) {
    $strrulename = get_string('pluginname', 'clatestaccess_' . $rule);
    $rulesbyname[$strrulename] = $rule;
}
core_collator::ksort($rulesbyname);

// Create the clatest settings page.
if (empty($reportsbyname) && empty($rulesbyname)) {
    $pagetitle = get_string('modulename', 'clatest');
} else {
    $pagetitle = get_string('generalsettings', 'admin');
}
$clatestsettings = new admin_settingpage('modsettingclatest', $pagetitle, 'moodle/site:config');

if ($ADMIN->fulltree) {
    // Introductory explanation that all the settings are defaults for the add clatest form.
    $clatestsettings->add(new admin_setting_heading('clatestintro', '', get_string('configintro', 'clatest')));

    // Time limit.
    $clatestsettings->add(new admin_setting_configduration_with_advanced('clatest/timelimit',
            get_string('timelimit', 'clatest'), get_string('configtimelimitsec', 'clatest'),
            array('value' => '0', 'adv' => false), 60));

    // What to do with overdue attempts.
    $clatestsettings->add(new mod_clatest_admin_setting_overduehandling('clatest/overduehandling',
            get_string('overduehandling', 'clatest'), get_string('overduehandling_desc', 'clatest'),
            array('value' => 'autosubmit', 'adv' => false), null));

    // Grace period time.
    $clatestsettings->add(new admin_setting_configduration_with_advanced('clatest/graceperiod',
            get_string('graceperiod', 'clatest'), get_string('graceperiod_desc', 'clatest'),
            array('value' => '86400', 'adv' => false)));

    // Minimum grace period used behind the scenes.
    $clatestsettings->add(new admin_setting_configduration('clatest/graceperiodmin',
            get_string('graceperiodmin', 'clatest'), get_string('graceperiodmin_desc', 'clatest'),
            60, 1));

    // Number of attempts.
    $options = array(get_string('unlimited'));
    for ($i = 1; $i <= QUIZ_MAX_ATTEMPT_OPTION; $i++) {
        $options[$i] = $i;
    }
    $clatestsettings->add(new admin_setting_configselect_with_advanced('clatest/attempts',
            get_string('attemptsallowed', 'clatest'), get_string('configattemptsallowed', 'clatest'),
            array('value' => 0, 'adv' => false), $options));

    // Grading method.
    $clatestsettings->add(new mod_clatest_admin_setting_grademethod('clatest/grademethod',
            get_string('grademethod', 'clatest'), get_string('configgrademethod', 'clatest'),
            array('value' => QUIZ_GRADEHIGHEST, 'adv' => false), null));

    // Maximum grade.
    $clatestsettings->add(new admin_setting_configtext('clatest/maximumgrade',
            get_string('maximumgrade'), get_string('configmaximumgrade', 'clatest'), 10, PARAM_INT));

    // Questions per page.
    $perpage = array();
    $perpage[0] = get_string('never');
    $perpage[1] = get_string('aftereachquestion', 'clatest');
    for ($i = 2; $i <= QUIZ_MAX_QPP_OPTION; ++$i) {
        $perpage[$i] = get_string('afternquestions', 'clatest', $i);
    }
    $clatestsettings->add(new admin_setting_configselect_with_advanced('clatest/questionsperpage',
            get_string('newpageevery', 'clatest'), get_string('confignewpageevery', 'clatest'),
            array('value' => 1, 'adv' => false), $perpage));

    // Navigation method.
    $clatestsettings->add(new admin_setting_configselect_with_advanced('clatest/navmethod',
            get_string('navmethod', 'clatest'), get_string('confignavmethod', 'clatest'),
            array('value' => QUIZ_NAVMETHOD_FREE, 'adv' => true), clatest_get_navigation_options()));

    // Shuffle within questions.
    $clatestsettings->add(new admin_setting_configcheckbox_with_advanced('clatest/shuffleanswers',
            get_string('shufflewithin', 'clatest'), get_string('configshufflewithin', 'clatest'),
            array('value' => 1, 'adv' => false)));

    // Preferred behaviour.
    $clatestsettings->add(new admin_setting_question_behaviour('clatest/preferredbehaviour',
            get_string('howquestionsbehave', 'question'), get_string('howquestionsbehave_desc', 'clatest'),
            'deferredfeedback'));

    // Can redo completed questions.
    $clatestsettings->add(new admin_setting_configselect_with_advanced('clatest/canredoquestions',
            get_string('canredoquestions', 'clatest'), get_string('canredoquestions_desc', 'clatest'),
            array('value' => 0, 'adv' => true),
            array(0 => get_string('no'), 1 => get_string('canredoquestionsyes', 'clatest'))));

    // Each attempt builds on last.
    $clatestsettings->add(new admin_setting_configcheckbox_with_advanced('clatest/attemptonlast',
            get_string('eachattemptbuildsonthelast', 'clatest'),
            get_string('configeachattemptbuildsonthelast', 'clatest'),
            array('value' => 0, 'adv' => true)));

    // Review options.
    $clatestsettings->add(new admin_setting_heading('reviewheading',
            get_string('reviewoptionsheading', 'clatest'), ''));
    foreach (mod_clatest_admin_review_setting::fields() as $field => $name) {
        $default = mod_clatest_admin_review_setting::all_on();
        $forceduring = null;
        if ($field == 'attempt') {
            $forceduring = true;
        } else if ($field == 'overallfeedback') {
            $default = $default ^ mod_clatest_admin_review_setting::DURING;
            $forceduring = false;
        }
        $clatestsettings->add(new mod_clatest_admin_review_setting('clatest/review' . $field,
                $name, '', $default, $forceduring));
    }

    // Show the user's picture.
    $clatestsettings->add(new mod_clatest_admin_setting_user_image('clatest/showuserpicture',
            get_string('showuserpicture', 'clatest'), get_string('configshowuserpicture', 'clatest'),
            array('value' => 0, 'adv' => false), null));

    // Decimal places for overall grades.
    $options = array();
    for ($i = 0; $i <= QUIZ_MAX_DECIMAL_OPTION; $i++) {
        $options[$i] = $i;
    }
    $clatestsettings->add(new admin_setting_configselect_with_advanced('clatest/decimalpoints',
            get_string('decimalplaces', 'clatest'), get_string('configdecimalplaces', 'clatest'),
            array('value' => 2, 'adv' => false), $options));

    // Decimal places for question grades.
    $options = array(-1 => get_string('sameasoverall', 'clatest'));
    for ($i = 0; $i <= QUIZ_MAX_Q_DECIMAL_OPTION; $i++) {
        $options[$i] = $i;
    }
    $clatestsettings->add(new admin_setting_configselect_with_advanced('clatest/questiondecimalpoints',
            get_string('decimalplacesquestion', 'clatest'),
            get_string('configdecimalplacesquestion', 'clatest'),
            array('value' => -1, 'adv' => true), $options));

    // Show blocks during clatest attempts.
    $clatestsettings->add(new admin_setting_configcheckbox_with_advanced('clatest/showblocks',
            get_string('showblocks', 'clatest'), get_string('configshowblocks', 'clatest'),
            array('value' => 0, 'adv' => true)));

    // Password.
    $clatestsettings->add(new admin_setting_configtext_with_advanced('clatest/password',
            get_string('requirepassword', 'clatest'), get_string('configrequirepassword', 'clatest'),
            array('value' => '', 'adv' => false), PARAM_TEXT));

    // IP restrictions.
    $clatestsettings->add(new admin_setting_configtext_with_advanced('clatest/subnet',
            get_string('requiresubnet', 'clatest'), get_string('configrequiresubnet', 'clatest'),
            array('value' => '', 'adv' => true), PARAM_TEXT));

    // Enforced delay between attempts.
    $clatestsettings->add(new admin_setting_configduration_with_advanced('clatest/delay1',
            get_string('delay1st2nd', 'clatest'), get_string('configdelay1st2nd', 'clatest'),
            array('value' => 0, 'adv' => true), 60));
    $clatestsettings->add(new admin_setting_configduration_with_advanced('clatest/delay2',
            get_string('delaylater', 'clatest'), get_string('configdelaylater', 'clatest'),
            array('value' => 0, 'adv' => true), 60));

    // Browser security.
    $clatestsettings->add(new mod_clatest_admin_setting_browsersecurity('clatest/browsersecurity',
            get_string('showinsecurepopup', 'clatest'), get_string('configpopup', 'clatest'),
            array('value' => '-', 'adv' => true), null));

    $clatestsettings->add(new admin_setting_configtext('clatest/initialnumfeedbacks',
            get_string('initialnumfeedbacks', 'clatest'), get_string('initialnumfeedbacks_desc', 'clatest'),
            2, PARAM_INT, 5));

    // Allow user to specify if setting outcomes is an advanced setting.
    if (!empty($CFG->enableoutcomes)) {
        $clatestsettings->add(new admin_setting_configcheckbox('clatest/outcomes_adv',
            get_string('outcomesadvanced', 'clatest'), get_string('configoutcomesadvanced', 'clatest'),
            '0'));
    }

    // Autosave frequency.
    $clatestsettings->add(new admin_setting_configduration('clatest/autosaveperiod',
            get_string('autosaveperiod', 'clatest'), get_string('autosaveperiod_desc', 'clatest'), 60, 1));
}

// Now, depending on whether any reports have their own settings page, add
// the clatest setting page to the appropriate place in the tree.
if (empty($reportsbyname) && empty($rulesbyname)) {
    $ADMIN->add('modsettings', $clatestsettings);
} else {
    $ADMIN->add('modsettings', new admin_category('modsettingsclatestcat',
            get_string('modulename', 'clatest'), $module->is_enabled() === false));
    $ADMIN->add('modsettingsclatestcat', $clatestsettings);

    // Add settings pages for the clatest report subplugins.
    foreach ($reportsbyname as $strreportname => $report) {
        $reportname = $report;

        $settings = new admin_settingpage('modsettingsclatestcat'.$reportname,
                $strreportname, 'moodle/site:config', $module->is_enabled() === false);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/clatest/report/$reportname/settings.php");
        }
        if (!empty($settings)) {
            $ADMIN->add('modsettingsclatestcat', $settings);
        }
    }

    // Add settings pages for the clatest access rule subplugins.
    foreach ($rulesbyname as $strrulename => $rule) {
        $settings = new admin_settingpage('modsettingsclatestcat' . $rule,
                $strrulename, 'moodle/site:config', $module->is_enabled() === false);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/clatest/accessrule/$rule/settings.php");
        }
        if (!empty($settings)) {
            $ADMIN->add('modsettingsclatestcat', $settings);
        }
    }
}

$settings = null; // We do not want standard settings link.
