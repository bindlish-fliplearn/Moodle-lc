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
 * Administration settings definitions for the hwork module.
 *
 * @package   mod_hwork
 * @copyright 2010 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/hwork/lib.php');

// First get a list of hwork reports with there own settings pages. If there none,
// we use a simpler overall menu structure.
$reports = core_component::get_plugin_list_with_file('hwork', 'settings.php', false);
$reportsbyname = array();
foreach ($reports as $report => $reportdir) {
    $strreportname = get_string($report . 'report', 'hwork_'.$report);
    $reportsbyname[$strreportname] = $report;
}
core_collator::ksort($reportsbyname);

// First get a list of hwork reports with there own settings pages. If there none,
// we use a simpler overall menu structure.
$rules = core_component::get_plugin_list_with_file('hworkaccess', 'settings.php', false);
$rulesbyname = array();
foreach ($rules as $rule => $ruledir) {
    $strrulename = get_string('pluginname', 'hworkaccess_' . $rule);
    $rulesbyname[$strrulename] = $rule;
}
core_collator::ksort($rulesbyname);

// Create the hwork settings page.
if (empty($reportsbyname) && empty($rulesbyname)) {
    $pagetitle = get_string('modulename', 'hwork');
} else {
    $pagetitle = get_string('generalsettings', 'admin');
}
$hworksettings = new admin_settingpage('modsettinghwork', $pagetitle, 'moodle/site:config');

if ($ADMIN->fulltree) {
    // Introductory explanation that all the settings are defaults for the add hwork form.
    $hworksettings->add(new admin_setting_heading('hworkintro', '', get_string('configintro', 'hwork')));

    // Time limit.
    $hworksettings->add(new admin_setting_configduration_with_advanced('hwork/timelimit',
            get_string('timelimit', 'hwork'), get_string('configtimelimitsec', 'hwork'),
            array('value' => '0', 'adv' => false), 60));

    // What to do with overdue attempts.
    $hworksettings->add(new mod_hwork_admin_setting_overduehandling('hwork/overduehandling',
            get_string('overduehandling', 'hwork'), get_string('overduehandling_desc', 'hwork'),
            array('value' => 'autosubmit', 'adv' => false), null));

    // Grace period time.
    $hworksettings->add(new admin_setting_configduration_with_advanced('hwork/graceperiod',
            get_string('graceperiod', 'hwork'), get_string('graceperiod_desc', 'hwork'),
            array('value' => '86400', 'adv' => false)));

    // Minimum grace period used behind the scenes.
    $hworksettings->add(new admin_setting_configduration('hwork/graceperiodmin',
            get_string('graceperiodmin', 'hwork'), get_string('graceperiodmin_desc', 'hwork'),
            60, 1));

    // Number of attempts.
    $options = array(get_string('unlimited'));
    for ($i = 1; $i <= QUIZ_MAX_ATTEMPT_OPTION; $i++) {
        $options[$i] = $i;
    }
    $hworksettings->add(new admin_setting_configselect_with_advanced('hwork/attempts',
            get_string('attemptsallowed', 'hwork'), get_string('configattemptsallowed', 'hwork'),
            array('value' => 0, 'adv' => false), $options));

    // Grading method.
    $hworksettings->add(new mod_hwork_admin_setting_grademethod('hwork/grademethod',
            get_string('grademethod', 'hwork'), get_string('configgrademethod', 'hwork'),
            array('value' => QUIZ_GRADEHIGHEST, 'adv' => false), null));

    // Maximum grade.
    $hworksettings->add(new admin_setting_configtext('hwork/maximumgrade',
            get_string('maximumgrade'), get_string('configmaximumgrade', 'hwork'), 10, PARAM_INT));

    // Questions per page.
    $perpage = array();
    $perpage[0] = get_string('never');
    $perpage[1] = get_string('aftereachquestion', 'hwork');
    for ($i = 2; $i <= QUIZ_MAX_QPP_OPTION; ++$i) {
        $perpage[$i] = get_string('afternquestions', 'hwork', $i);
    }
    $hworksettings->add(new admin_setting_configselect_with_advanced('hwork/questionsperpage',
            get_string('newpageevery', 'hwork'), get_string('confignewpageevery', 'hwork'),
            array('value' => 1, 'adv' => false), $perpage));

    // Navigation method.
    $hworksettings->add(new admin_setting_configselect_with_advanced('hwork/navmethod',
            get_string('navmethod', 'hwork'), get_string('confignavmethod', 'hwork'),
            array('value' => QUIZ_NAVMETHOD_FREE, 'adv' => true), hwork_get_navigation_options()));

    // Shuffle within questions.
    $hworksettings->add(new admin_setting_configcheckbox_with_advanced('hwork/shuffleanswers',
            get_string('shufflewithin', 'hwork'), get_string('configshufflewithin', 'hwork'),
            array('value' => 1, 'adv' => false)));

    // Preferred behaviour.
    $hworksettings->add(new admin_setting_question_behaviour('hwork/preferredbehaviour',
            get_string('howquestionsbehave', 'question'), get_string('howquestionsbehave_desc', 'hwork'),
            'deferredfeedback'));

    // Can redo completed questions.
    $hworksettings->add(new admin_setting_configselect_with_advanced('hwork/canredoquestions',
            get_string('canredoquestions', 'hwork'), get_string('canredoquestions_desc', 'hwork'),
            array('value' => 0, 'adv' => true),
            array(0 => get_string('no'), 1 => get_string('canredoquestionsyes', 'hwork'))));

    // Each attempt builds on last.
    $hworksettings->add(new admin_setting_configcheckbox_with_advanced('hwork/attemptonlast',
            get_string('eachattemptbuildsonthelast', 'hwork'),
            get_string('configeachattemptbuildsonthelast', 'hwork'),
            array('value' => 0, 'adv' => true)));

    // Review options.
    $hworksettings->add(new admin_setting_heading('reviewheading',
            get_string('reviewoptionsheading', 'hwork'), ''));
    foreach (mod_hwork_admin_review_setting::fields() as $field => $name) {
        $default = mod_hwork_admin_review_setting::all_on();
        $forceduring = null;
        if ($field == 'attempt') {
            $forceduring = true;
        } else if ($field == 'overallfeedback') {
            $default = $default ^ mod_hwork_admin_review_setting::DURING;
            $forceduring = false;
        }
        $hworksettings->add(new mod_hwork_admin_review_setting('hwork/review' . $field,
                $name, '', $default, $forceduring));
    }

    // Show the user's picture.
    $hworksettings->add(new mod_hwork_admin_setting_user_image('hwork/showuserpicture',
            get_string('showuserpicture', 'hwork'), get_string('configshowuserpicture', 'hwork'),
            array('value' => 0, 'adv' => false), null));

    // Decimal places for overall grades.
    $options = array();
    for ($i = 0; $i <= QUIZ_MAX_DECIMAL_OPTION; $i++) {
        $options[$i] = $i;
    }
    $hworksettings->add(new admin_setting_configselect_with_advanced('hwork/decimalpoints',
            get_string('decimalplaces', 'hwork'), get_string('configdecimalplaces', 'hwork'),
            array('value' => 2, 'adv' => false), $options));

    // Decimal places for question grades.
    $options = array(-1 => get_string('sameasoverall', 'hwork'));
    for ($i = 0; $i <= QUIZ_MAX_Q_DECIMAL_OPTION; $i++) {
        $options[$i] = $i;
    }
    $hworksettings->add(new admin_setting_configselect_with_advanced('hwork/questiondecimalpoints',
            get_string('decimalplacesquestion', 'hwork'),
            get_string('configdecimalplacesquestion', 'hwork'),
            array('value' => -1, 'adv' => true), $options));

    // Show blocks during hwork attempts.
    $hworksettings->add(new admin_setting_configcheckbox_with_advanced('hwork/showblocks',
            get_string('showblocks', 'hwork'), get_string('configshowblocks', 'hwork'),
            array('value' => 0, 'adv' => true)));

    // Password.
    $hworksettings->add(new admin_setting_configtext_with_advanced('hwork/password',
            get_string('requirepassword', 'hwork'), get_string('configrequirepassword', 'hwork'),
            array('value' => '', 'adv' => false), PARAM_TEXT));

    // IP restrictions.
    $hworksettings->add(new admin_setting_configtext_with_advanced('hwork/subnet',
            get_string('requiresubnet', 'hwork'), get_string('configrequiresubnet', 'hwork'),
            array('value' => '', 'adv' => true), PARAM_TEXT));

    // Enforced delay between attempts.
    $hworksettings->add(new admin_setting_configduration_with_advanced('hwork/delay1',
            get_string('delay1st2nd', 'hwork'), get_string('configdelay1st2nd', 'hwork'),
            array('value' => 0, 'adv' => true), 60));
    $hworksettings->add(new admin_setting_configduration_with_advanced('hwork/delay2',
            get_string('delaylater', 'hwork'), get_string('configdelaylater', 'hwork'),
            array('value' => 0, 'adv' => true), 60));

    // Browser security.
    $hworksettings->add(new mod_hwork_admin_setting_browsersecurity('hwork/browsersecurity',
            get_string('showinsecurepopup', 'hwork'), get_string('configpopup', 'hwork'),
            array('value' => '-', 'adv' => true), null));

    $hworksettings->add(new admin_setting_configtext('hwork/initialnumfeedbacks',
            get_string('initialnumfeedbacks', 'hwork'), get_string('initialnumfeedbacks_desc', 'hwork'),
            2, PARAM_INT, 5));

    // Allow user to specify if setting outcomes is an advanced setting.
    if (!empty($CFG->enableoutcomes)) {
        $hworksettings->add(new admin_setting_configcheckbox('hwork/outcomes_adv',
            get_string('outcomesadvanced', 'hwork'), get_string('configoutcomesadvanced', 'hwork'),
            '0'));
    }

    // Autosave frequency.
    $hworksettings->add(new admin_setting_configduration('hwork/autosaveperiod',
            get_string('autosaveperiod', 'hwork'), get_string('autosaveperiod_desc', 'hwork'), 60, 1));
}

// Now, depending on whether any reports have their own settings page, add
// the hwork setting page to the appropriate place in the tree.
if (empty($reportsbyname) && empty($rulesbyname)) {
    $ADMIN->add('modsettings', $hworksettings);
} else {
    $ADMIN->add('modsettings', new admin_category('modsettingshworkcat',
            get_string('modulename', 'hwork'), $module->is_enabled() === false));
    $ADMIN->add('modsettingshworkcat', $hworksettings);

    // Add settings pages for the hwork report subplugins.
    foreach ($reportsbyname as $strreportname => $report) {
        $reportname = $report;

        $settings = new admin_settingpage('modsettingshworkcat'.$reportname,
                $strreportname, 'moodle/site:config', $module->is_enabled() === false);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/hwork/report/$reportname/settings.php");
        }
        if (!empty($settings)) {
            $ADMIN->add('modsettingshworkcat', $settings);
        }
    }

    // Add settings pages for the hwork access rule subplugins.
    foreach ($rulesbyname as $strrulename => $rule) {
        $settings = new admin_settingpage('modsettingshworkcat' . $rule,
                $strrulename, 'moodle/site:config', $module->is_enabled() === false);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/hwork/accessrule/$rule/settings.php");
        }
        if (!empty($settings)) {
            $ADMIN->add('modsettingshworkcat', $settings);
        }
    }
}

$settings = null; // We do not want standard settings link.
