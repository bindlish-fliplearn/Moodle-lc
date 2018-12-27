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
 * Administration settings definitions for the homework module.
 *
 * @package   mod_homework
 * @copyright 2010 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/homework/lib.php');

// First get a list of homework reports with there own settings pages. If there none,
// we use a simpler overall menu structure.
$reports = core_component::get_plugin_list_with_file('homework', 'settings.php', false);
$reportsbyname = array();
foreach ($reports as $report => $reportdir) {
    $strreportname = get_string($report . 'report', 'homework_'.$report);
    $reportsbyname[$strreportname] = $report;
}
core_collator::ksort($reportsbyname);

// First get a list of homework reports with there own settings pages. If there none,
// we use a simpler overall menu structure.
$rules = core_component::get_plugin_list_with_file('homeworkaccess', 'settings.php', false);
$rulesbyname = array();
foreach ($rules as $rule => $ruledir) {
    $strrulename = get_string('pluginname', 'homeworkaccess_' . $rule);
    $rulesbyname[$strrulename] = $rule;
}
core_collator::ksort($rulesbyname);

// Create the homework settings page.
if (empty($reportsbyname) && empty($rulesbyname)) {
    $pagetitle = get_string('modulename', 'homework');
} else {
    $pagetitle = get_string('generalsettings', 'admin');
}
$homeworksettings = new admin_settingpage('modsettinghomework', $pagetitle, 'moodle/site:config');

if ($ADMIN->fulltree) {
    // Introductory explanation that all the settings are defaults for the add homework form.
    $homeworksettings->add(new admin_setting_heading('homeworkintro', '', get_string('configintro', 'homework')));

    // Time limit.
    $homeworksettings->add(new admin_setting_configduration_with_advanced('homework/timelimit',
            get_string('timelimit', 'homework'), get_string('configtimelimitsec', 'homework'),
            array('value' => '0', 'adv' => false), 60));

    // What to do with overdue attempts.
    $homeworksettings->add(new mod_homework_admin_setting_overduehandling('homework/overduehandling',
            get_string('overduehandling', 'homework'), get_string('overduehandling_desc', 'homework'),
            array('value' => 'autosubmit', 'adv' => false), null));

    // Grace period time.
    $homeworksettings->add(new admin_setting_configduration_with_advanced('homework/graceperiod',
            get_string('graceperiod', 'homework'), get_string('graceperiod_desc', 'homework'),
            array('value' => '86400', 'adv' => false)));

    // Minimum grace period used behind the scenes.
    $homeworksettings->add(new admin_setting_configduration('homework/graceperiodmin',
            get_string('graceperiodmin', 'homework'), get_string('graceperiodmin_desc', 'homework'),
            60, 1));

    // Number of attempts.
    $options = array(get_string('unlimited'));
    for ($i = 1; $i <= QUIZ_MAX_ATTEMPT_OPTION; $i++) {
        $options[$i] = $i;
    }
    $homeworksettings->add(new admin_setting_configselect_with_advanced('homework/attempts',
            get_string('attemptsallowed', 'homework'), get_string('configattemptsallowed', 'homework'),
            array('value' => 0, 'adv' => false), $options));

    // Grading method.
    $homeworksettings->add(new mod_homework_admin_setting_grademethod('homework/grademethod',
            get_string('grademethod', 'homework'), get_string('configgrademethod', 'homework'),
            array('value' => QUIZ_GRADEHIGHEST, 'adv' => false), null));

    // Maximum grade.
    $homeworksettings->add(new admin_setting_configtext('homework/maximumgrade',
            get_string('maximumgrade'), get_string('configmaximumgrade', 'homework'), 10, PARAM_INT));

    // Questions per page.
    $perpage = array();
    $perpage[0] = get_string('never');
    $perpage[1] = get_string('aftereachquestion', 'homework');
    for ($i = 2; $i <= QUIZ_MAX_QPP_OPTION; ++$i) {
        $perpage[$i] = get_string('afternquestions', 'homework', $i);
    }
    $homeworksettings->add(new admin_setting_configselect_with_advanced('homework/questionsperpage',
            get_string('newpageevery', 'homework'), get_string('confignewpageevery', 'homework'),
            array('value' => 1, 'adv' => false), $perpage));

    // Navigation method.
    $homeworksettings->add(new admin_setting_configselect_with_advanced('homework/navmethod',
            get_string('navmethod', 'homework'), get_string('confignavmethod', 'homework'),
            array('value' => QUIZ_NAVMETHOD_FREE, 'adv' => true), homework_get_navigation_options()));

    // Shuffle within questions.
    $homeworksettings->add(new admin_setting_configcheckbox_with_advanced('homework/shuffleanswers',
            get_string('shufflewithin', 'homework'), get_string('configshufflewithin', 'homework'),
            array('value' => 1, 'adv' => false)));

    // Preferred behaviour.
    $homeworksettings->add(new admin_setting_question_behaviour('homework/preferredbehaviour',
            get_string('howquestionsbehave', 'question'), get_string('howquestionsbehave_desc', 'homework'),
            'deferredfeedback'));

    // Can redo completed questions.
    $homeworksettings->add(new admin_setting_configselect_with_advanced('homework/canredoquestions',
            get_string('canredoquestions', 'homework'), get_string('canredoquestions_desc', 'homework'),
            array('value' => 0, 'adv' => true),
            array(0 => get_string('no'), 1 => get_string('canredoquestionsyes', 'homework'))));

    // Each attempt builds on last.
    $homeworksettings->add(new admin_setting_configcheckbox_with_advanced('homework/attemptonlast',
            get_string('eachattemptbuildsonthelast', 'homework'),
            get_string('configeachattemptbuildsonthelast', 'homework'),
            array('value' => 0, 'adv' => true)));

    // Review options.
    $homeworksettings->add(new admin_setting_heading('reviewheading',
            get_string('reviewoptionsheading', 'homework'), ''));
    foreach (mod_homework_admin_review_setting::fields() as $field => $name) {
        $default = mod_homework_admin_review_setting::all_on();
        $forceduring = null;
        if ($field == 'attempt') {
            $forceduring = true;
        } else if ($field == 'overallfeedback') {
            $default = $default ^ mod_homework_admin_review_setting::DURING;
            $forceduring = false;
        }
        $homeworksettings->add(new mod_homework_admin_review_setting('homework/review' . $field,
                $name, '', $default, $forceduring));
    }

    // Show the user's picture.
    $homeworksettings->add(new mod_homework_admin_setting_user_image('homework/showuserpicture',
            get_string('showuserpicture', 'homework'), get_string('configshowuserpicture', 'homework'),
            array('value' => 0, 'adv' => false), null));

    // Decimal places for overall grades.
    $options = array();
    for ($i = 0; $i <= QUIZ_MAX_DECIMAL_OPTION; $i++) {
        $options[$i] = $i;
    }
    $homeworksettings->add(new admin_setting_configselect_with_advanced('homework/decimalpoints',
            get_string('decimalplaces', 'homework'), get_string('configdecimalplaces', 'homework'),
            array('value' => 2, 'adv' => false), $options));

    // Decimal places for question grades.
    $options = array(-1 => get_string('sameasoverall', 'homework'));
    for ($i = 0; $i <= QUIZ_MAX_Q_DECIMAL_OPTION; $i++) {
        $options[$i] = $i;
    }
    $homeworksettings->add(new admin_setting_configselect_with_advanced('homework/questiondecimalpoints',
            get_string('decimalplacesquestion', 'homework'),
            get_string('configdecimalplacesquestion', 'homework'),
            array('value' => -1, 'adv' => true), $options));

    // Show blocks during homework attempts.
    $homeworksettings->add(new admin_setting_configcheckbox_with_advanced('homework/showblocks',
            get_string('showblocks', 'homework'), get_string('configshowblocks', 'homework'),
            array('value' => 0, 'adv' => true)));

    // Password.
    $homeworksettings->add(new admin_setting_configtext_with_advanced('homework/password',
            get_string('requirepassword', 'homework'), get_string('configrequirepassword', 'homework'),
            array('value' => '', 'adv' => false), PARAM_TEXT));

    // IP restrictions.
    $homeworksettings->add(new admin_setting_configtext_with_advanced('homework/subnet',
            get_string('requiresubnet', 'homework'), get_string('configrequiresubnet', 'homework'),
            array('value' => '', 'adv' => true), PARAM_TEXT));

    // Enforced delay between attempts.
    $homeworksettings->add(new admin_setting_configduration_with_advanced('homework/delay1',
            get_string('delay1st2nd', 'homework'), get_string('configdelay1st2nd', 'homework'),
            array('value' => 0, 'adv' => true), 60));
    $homeworksettings->add(new admin_setting_configduration_with_advanced('homework/delay2',
            get_string('delaylater', 'homework'), get_string('configdelaylater', 'homework'),
            array('value' => 0, 'adv' => true), 60));

    // Browser security.
    $homeworksettings->add(new mod_homework_admin_setting_browsersecurity('homework/browsersecurity',
            get_string('showinsecurepopup', 'homework'), get_string('configpopup', 'homework'),
            array('value' => '-', 'adv' => true), null));

    $homeworksettings->add(new admin_setting_configtext('homework/initialnumfeedbacks',
            get_string('initialnumfeedbacks', 'homework'), get_string('initialnumfeedbacks_desc', 'homework'),
            2, PARAM_INT, 5));

    // Allow user to specify if setting outcomes is an advanced setting.
    if (!empty($CFG->enableoutcomes)) {
        $homeworksettings->add(new admin_setting_configcheckbox('homework/outcomes_adv',
            get_string('outcomesadvanced', 'homework'), get_string('configoutcomesadvanced', 'homework'),
            '0'));
    }

    // Autosave frequency.
    $homeworksettings->add(new admin_setting_configduration('homework/autosaveperiod',
            get_string('autosaveperiod', 'homework'), get_string('autosaveperiod_desc', 'homework'), 60, 1));
}

// Now, depending on whether any reports have their own settings page, add
// the homework setting page to the appropriate place in the tree.
if (empty($reportsbyname) && empty($rulesbyname)) {
    $ADMIN->add('modsettings', $homeworksettings);
} else {
    $ADMIN->add('modsettings', new admin_category('modsettingshomeworkcat',
            get_string('modulename', 'homework'), $module->is_enabled() === false));
    $ADMIN->add('modsettingshomeworkcat', $homeworksettings);

    // Add settings pages for the homework report subplugins.
    foreach ($reportsbyname as $strreportname => $report) {
        $reportname = $report;

        $settings = new admin_settingpage('modsettingshomeworkcat'.$reportname,
                $strreportname, 'moodle/site:config', $module->is_enabled() === false);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/homework/report/$reportname/settings.php");
        }
        if (!empty($settings)) {
            $ADMIN->add('modsettingshomeworkcat', $settings);
        }
    }

    // Add settings pages for the homework access rule subplugins.
    foreach ($rulesbyname as $strrulename => $rule) {
        $settings = new admin_settingpage('modsettingshomeworkcat' . $rule,
                $strrulename, 'moodle/site:config', $module->is_enabled() === false);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/homework/accessrule/$rule/settings.php");
        }
        if (!empty($settings)) {
            $ADMIN->add('modsettingshomeworkcat', $settings);
        }
    }
}

$settings = null; // We do not want standard settings link.
