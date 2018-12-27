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
 * Privacy Subsystem implementation for mod_pratest.
 *
 * @package    mod_pratest
 * @category   privacy
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pratest\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\transform;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pratest/lib.php');
require_once($CFG->dirroot . '/mod/pratest/locallib.php');

/**
 * Privacy Subsystem implementation for mod_pratest.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin_provider interface.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   collection  $items  The collection to add metadata to.
     * @return  collection  The array of metadata
     */
    public static function get_metadata(collection $items) : collection {
        // The table 'pratest' stores a record for each pratest.
        // It does not contain user personal data, but data is returned from it for contextual requirements.

        // The table 'pratest_attempts' stores a record of each pratest attempt.
        // It contains a userid which links to the user making the attempt and contains information about that attempt.
        $items->add_database_table('pratest_attempts', [
                'attempt'               => 'privacy:metadata:pratest_attempts:attempt',
                'currentpage'           => 'privacy:metadata:pratest_attempts:currentpage',
                'preview'               => 'privacy:metadata:pratest_attempts:preview',
                'state'                 => 'privacy:metadata:pratest_attempts:state',
                'timestart'             => 'privacy:metadata:pratest_attempts:timestart',
                'timefinish'            => 'privacy:metadata:pratest_attempts:timefinish',
                'timemodified'          => 'privacy:metadata:pratest_attempts:timemodified',
                'timemodifiedoffline'   => 'privacy:metadata:pratest_attempts:timemodifiedoffline',
                'timecheckstate'        => 'privacy:metadata:pratest_attempts:timecheckstate',
                'sumgrades'             => 'privacy:metadata:pratest_attempts:sumgrades',
            ], 'privacy:metadata:pratest_attempts');

        // The table 'pratest_feedback' contains the feedback responses which will be shown to users depending upon the
        // grade they achieve in the pratest.
        // It does not identify the user who wrote the feedback item so cannot be returned directly and is not
        // described, but relevant feedback items will be included with the pratest export for a user who has a grade.

        // The table 'pratest_grades' contains the current grade for each pratest/user combination.
        $items->add_database_table('pratest_grades', [
                'pratest'                  => 'privacy:metadata:pratest_grades:pratest',
                'userid'                => 'privacy:metadata:pratest_grades:userid',
                'grade'                 => 'privacy:metadata:pratest_grades:grade',
                'timemodified'          => 'privacy:metadata:pratest_grades:timemodified',
            ], 'privacy:metadata:pratest_grades');

        // The table 'pratest_overrides' contains any user or group overrides for users.
        // It should be included where data exists for a user.
        $items->add_database_table('pratest_overrides', [
                'pratest'                  => 'privacy:metadata:pratest_overrides:pratest',
                'userid'                => 'privacy:metadata:pratest_overrides:userid',
                'timeopen'              => 'privacy:metadata:pratest_overrides:timeopen',
                'timeclose'             => 'privacy:metadata:pratest_overrides:timeclose',
                'timelimit'             => 'privacy:metadata:pratest_overrides:timelimit',
            ], 'privacy:metadata:pratest_overrides');

        // These define the structure of the pratest.

        // The table 'pratest_sections' contains data about the structure of a pratest.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'pratest_slots' contains data about the structure of a pratest.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'pratest_reports' does not contain any user identifying data and does not need a mapping.

        // The table 'pratest_statistics' contains abstract statistics about question usage and cannot be mapped to any
        // specific user.
        // It does not contain any user identifying data and does not need a mapping.

        // The pratest links to the 'core_question' subsystem for all question functionality.
        $items->add_subsystem_link('core_question', [], 'privacy:metadata:core_question');

        // The pratest has two subplugins..
        $items->add_plugintype_link('pratest', [], 'privacy:metadata:pratest');
        $items->add_plugintype_link('pratestaccess', [], 'privacy:metadata:pratestaccess');

        // Although the pratest supports the core_completion API and defines custom completion items, these will be
        // noted by the manager as all activity modules are capable of supporting this functionality.

        return $items;
    }

    /**
     * Get the list of contexts where the specified user has attempted a pratest, or been involved with manual marking
     * and/or grading of a pratest.
     *
     * @param   int             $userid The user to search.
     * @return  contextlist     $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $resultset = new contextlist();

        // Users who attempted the pratest.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {pratest} q ON q.id = cm.instance
                  JOIN {pratest_attempts} qa ON qa.pratest = q.id
                 WHERE qa.userid = :userid AND qa.preview = 0";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'pratest', 'userid' => $userid];
        $resultset->add_from_sql($sql, $params);

        // Users with pratest overrides.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {pratest} q ON q.id = cm.instance
                  JOIN {pratest_overrides} qo ON qo.pratest = q.id
                 WHERE qo.userid = :userid";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'pratest', 'userid' => $userid];
        $resultset->add_from_sql($sql, $params);

        // Get the SQL used to link indirect question usages for the user.
        // This includes where a user is the manual marker on a question attempt.
        $qubaid = \core_question\privacy\provider::get_related_question_usages_for_user('rel', 'mod_pratest', 'qa.uniqueid', $userid);

        // Select the context of any pratest attempt where a user has an attempt, plus the related usages.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {pratest} q ON q.id = cm.instance
                  JOIN {pratest_attempts} qa ON qa.pratest = q.id
            " . $qubaid->from . "
            WHERE " . $qubaid->where() . " AND qa.preview = 0";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'pratest'] + $qubaid->from_where_params();
        $resultset->add_from_sql($sql, $params);

        return $resultset;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'cmid'    => $context->instanceid,
            'modname' => 'pratest',
        ];

        // Users who attempted the pratest.
        $sql = "SELECT qa.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {pratest} q ON q.id = cm.instance
                  JOIN {pratest_attempts} qa ON qa.pratest = q.id
                 WHERE cm.id = :cmid AND qa.preview = 0";
        $userlist->add_from_sql('userid', $sql, $params);

        // Users with pratest overrides.
        $sql = "SELECT qo.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {pratest} q ON q.id = cm.instance
                  JOIN {pratest_overrides} qo ON qo.pratest = q.id
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Question usages in context.
        // This includes where a user is the manual marker on a question attempt.
        $sql = "SELECT qa.uniqueid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {pratest} q ON q.id = cm.instance
                  JOIN {pratest_attempts} qa ON qa.pratest = q.id
                 WHERE cm.id = :cmid AND qa.preview = 0";
        \core_question\privacy\provider::get_users_in_context_from_sql($userlist, 'qn', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT
                    q.*,
                    qg.id AS hasgrade,
                    qg.grade AS bestgrade,
                    qg.timemodified AS grademodified,
                    qo.id AS hasoverride,
                    qo.timeopen AS override_timeopen,
                    qo.timeclose AS override_timeclose,
                    qo.timelimit AS override_timelimit,
                    c.id AS contextid,
                    cm.id AS cmid
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {pratest} q ON q.id = cm.instance
             LEFT JOIN {pratest_overrides} qo ON qo.pratest = q.id AND qo.userid = :qouserid
             LEFT JOIN {pratest_grades} qg ON qg.pratest = q.id AND qg.userid = :qguserid
                 WHERE c.id {$contextsql}";

        $params = [
            'contextlevel'      => CONTEXT_MODULE,
            'modname'           => 'pratest',
            'qguserid'          => $userid,
            'qouserid'          => $userid,
        ];
        $params += $contextparams;

        // Fetch the individual pratestzes.
        $pratestzes = $DB->get_recordset_sql($sql, $params);
        foreach ($pratestzes as $pratest) {
            list($course, $cm) = get_course_and_cm_from_cmid($pratest->cmid, 'pratest');
            $pratestobj = new \pratest($pratest, $cm, $course);
            $context = $pratestobj->get_context();

            $pratestdata = \core_privacy\local\request\helper::get_context_data($context, $contextlist->get_user());
            \core_privacy\local\request\helper::export_context_files($context, $contextlist->get_user());

            if (!empty($pratestdata->timeopen)) {
                $pratestdata->timeopen = transform::datetime($pratest->timeopen);
            }
            if (!empty($pratestdata->timeclose)) {
                $pratestdata->timeclose = transform::datetime($pratest->timeclose);
            }
            if (!empty($pratestdata->timelimit)) {
                $pratestdata->timelimit = $pratest->timelimit;
            }

            if (!empty($pratest->hasoverride)) {
                $pratestdata->override = (object) [];

                if (!empty($pratestdata->override_override_timeopen)) {
                    $pratestdata->override->timeopen = transform::datetime($pratest->override_timeopen);
                }
                if (!empty($pratestdata->override_timeclose)) {
                    $pratestdata->override->timeclose = transform::datetime($pratest->override_timeclose);
                }
                if (!empty($pratestdata->override_timelimit)) {
                    $pratestdata->override->timelimit = $pratest->override_timelimit;
                }
            }

            $pratestdata->accessdata = (object) [];

            $components = \core_component::get_plugin_list('pratestaccess');
            $exportparams = [
                    $pratestobj,
                    $user,
                ];
            foreach (array_keys($components) as $component) {
                $classname = manager::get_provider_classname_for_component("pratestaccess_$component");
                if (class_exists($classname) && is_subclass_of($classname, pratestaccess_provider::class)) {
                    $result = component_class_callback($classname, 'export_pratestaccess_user_data', $exportparams);
                    if (count((array) $result)) {
                        $pratestdata->accessdata->$component = $result;
                    }
                }
            }

            if (empty((array) $pratestdata->accessdata)) {
                unset($pratestdata->accessdata);
            }

            writer::with_context($context)
                ->export_data([], $pratestdata);
        }
        $pratestzes->close();

        // Store all pratest attempt data.
        static::export_pratest_attempts($contextlist);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context                 $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only pratest module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('pratest', $context->instanceid);
        if (!$cm) {
            // Only pratest module will be handled.
            return;
        }

        $pratestobj = \pratest::create($cm->instance);
        $pratest = $pratestobj->get_pratest();

        // Handle the 'pratestaccess' subplugin.
        manager::plugintype_class_callback(
                'pratestaccess',
                pratestaccess_provider::class,
                'delete_subplugin_data_for_all_users_in_context',
                [$pratestobj]
            );

        // Delete all overrides - do not log.
        pratest_delete_all_overrides($pratest, false);

        // This will delete all question attempts, pratest attempts, and pratest grades for this pratest.
        pratest_delete_all_attempts($pratest);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
            // Only pratest module will be handled.
                continue;
            }

            $cm = get_coursemodule_from_id('pratest', $context->instanceid);
            if (!$cm) {
                // Only pratest module will be handled.
                continue;
            }

            // Fetch the details of the data to be removed.
            $pratestobj = \pratest::create($cm->instance);
            $pratest = $pratestobj->get_pratest();
            $user = $contextlist->get_user();

            // Handle the 'pratestaccess' pratestaccess.
            manager::plugintype_class_callback(
                    'pratestaccess',
                    pratestaccess_provider::class,
                    'delete_pratestaccess_data_for_user',
                    [$pratestobj, $user]
                );

            // Remove overrides for this user.
            $overrides = $DB->get_records('pratest_overrides' , [
                'pratest' => $pratestobj->get_pratestid(),
                'userid' => $user->id,
            ]);

            foreach ($overrides as $override) {
                pratest_delete_override($pratest, $override->id, false);
            }

            // This will delete all question attempts, pratest attempts, and pratest grades for this pratest.
            pratest_delete_user_attempts($pratestobj, $user);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only pratest module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('pratest', $context->instanceid);
        if (!$cm) {
            // Only pratest module will be handled.
            return;
        }

        $pratestobj = \pratest::create($cm->instance);
        $pratest = $pratestobj->get_pratest();

        $userids = $userlist->get_userids();

        // Handle the 'pratestaccess' pratestaccess.
        manager::plugintype_class_callback(
                'pratestaccess',
                pratestaccess_user_provider::class,
                'delete_pratestaccess_data_for_users',
                [$userlist]
        );

        foreach ($userids as $userid) {
            // Remove overrides for this user.
            $overrides = $DB->get_records('pratest_overrides' , [
                'pratest' => $pratestobj->get_pratestid(),
                'userid' => $userid,
            ]);

            foreach ($overrides as $override) {
                pratest_delete_override($pratest, $override->id, false);
            }

            // This will delete all question attempts, pratest attempts, and pratest grades for this user in the given pratest.
            pratest_delete_user_attempts($pratestobj, (object)['id' => $userid]);
        }
    }

    /**
     * Store all pratest attempts for the contextlist.
     *
     * @param   approved_contextlist    $contextlist
     */
    protected static function export_pratest_attempts(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $qubaid = \core_question\privacy\provider::get_related_question_usages_for_user('rel', 'mod_pratest', 'qa.uniqueid', $userid);

        $sql = "SELECT
                    c.id AS contextid,
                    cm.id AS cmid,
                    qa.*
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'pratest'
                  JOIN {pratest} q ON q.id = cm.instance
                  JOIN {pratest_attempts} qa ON qa.pratest = q.id
            " . $qubaid->from. "
            WHERE (
                qa.userid = :qauserid OR
                " . $qubaid->where() . "
            ) AND qa.preview = 0
        ";

        $params = array_merge(
                [
                    'contextlevel'      => CONTEXT_MODULE,
                    'qauserid'          => $userid,
                ],
                $qubaid->from_where_params()
            );

        $attempts = $DB->get_recordset_sql($sql, $params);
        foreach ($attempts as $attempt) {
            $pratest = $DB->get_record('pratest', ['id' => $attempt->pratest]);
            $context = \context_module::instance($attempt->cmid);
            $attemptsubcontext = helper::get_pratest_attempt_subcontext($attempt, $contextlist->get_user());
            $options = pratest_get_review_options($pratest, $attempt, $context);

            if ($attempt->userid == $userid) {
                // This attempt was made by the user.
                // They 'own' all data on it.
                // Store the question usage data.
                \core_question\privacy\provider::export_question_usage($userid,
                        $context,
                        $attemptsubcontext,
                        $attempt->uniqueid,
                        $options,
                        true
                    );

                // Store the pratest attempt data.
                $data = (object) [
                    'state' => \pratest_attempt::state_name($attempt->state),
                ];

                if (!empty($attempt->timestart)) {
                    $data->timestart = transform::datetime($attempt->timestart);
                }
                if (!empty($attempt->timefinish)) {
                    $data->timefinish = transform::datetime($attempt->timefinish);
                }
                if (!empty($attempt->timemodified)) {
                    $data->timemodified = transform::datetime($attempt->timemodified);
                }
                if (!empty($attempt->timemodifiedoffline)) {
                    $data->timemodifiedoffline = transform::datetime($attempt->timemodifiedoffline);
                }
                if (!empty($attempt->timecheckstate)) {
                    $data->timecheckstate = transform::datetime($attempt->timecheckstate);
                }

                if ($options->marks == \question_display_options::MARK_AND_MAX) {
                    $grade = pratest_rescale_grade($attempt->sumgrades, $pratest, false);
                    $data->grade = (object) [
                            'grade' => pratest_format_grade($pratest, $grade),
                            'feedback' => pratest_feedback_for_grade($grade, $pratest, $context),
                        ];
                }

                writer::with_context($context)
                    ->export_data($attemptsubcontext, $data);
            } else {
                // This attempt was made by another user.
                // The current user may have marked part of the pratest attempt.
                \core_question\privacy\provider::export_question_usage(
                        $userid,
                        $context,
                        $attemptsubcontext,
                        $attempt->uniqueid,
                        $options,
                        false
                    );
            }
        }
        $attempts->close();
    }
}
