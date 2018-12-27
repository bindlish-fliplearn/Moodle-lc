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
 * Privacy Subsystem implementation for mod_clatest.
 *
 * @package    mod_clatest
 * @category   privacy
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_clatest\privacy;

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

require_once($CFG->dirroot . '/mod/clatest/lib.php');
require_once($CFG->dirroot . '/mod/clatest/locallib.php');

/**
 * Privacy Subsystem implementation for mod_clatest.
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
        // The table 'clatest' stores a record for each clatest.
        // It does not contain user personal data, but data is returned from it for contextual requirements.

        // The table 'clatest_attempts' stores a record of each clatest attempt.
        // It contains a userid which links to the user making the attempt and contains information about that attempt.
        $items->add_database_table('clatest_attempts', [
                'attempt'               => 'privacy:metadata:clatest_attempts:attempt',
                'currentpage'           => 'privacy:metadata:clatest_attempts:currentpage',
                'preview'               => 'privacy:metadata:clatest_attempts:preview',
                'state'                 => 'privacy:metadata:clatest_attempts:state',
                'timestart'             => 'privacy:metadata:clatest_attempts:timestart',
                'timefinish'            => 'privacy:metadata:clatest_attempts:timefinish',
                'timemodified'          => 'privacy:metadata:clatest_attempts:timemodified',
                'timemodifiedoffline'   => 'privacy:metadata:clatest_attempts:timemodifiedoffline',
                'timecheckstate'        => 'privacy:metadata:clatest_attempts:timecheckstate',
                'sumgrades'             => 'privacy:metadata:clatest_attempts:sumgrades',
            ], 'privacy:metadata:clatest_attempts');

        // The table 'clatest_feedback' contains the feedback responses which will be shown to users depending upon the
        // grade they achieve in the clatest.
        // It does not identify the user who wrote the feedback item so cannot be returned directly and is not
        // described, but relevant feedback items will be included with the clatest export for a user who has a grade.

        // The table 'clatest_grades' contains the current grade for each clatest/user combination.
        $items->add_database_table('clatest_grades', [
                'clatest'                  => 'privacy:metadata:clatest_grades:clatest',
                'userid'                => 'privacy:metadata:clatest_grades:userid',
                'grade'                 => 'privacy:metadata:clatest_grades:grade',
                'timemodified'          => 'privacy:metadata:clatest_grades:timemodified',
            ], 'privacy:metadata:clatest_grades');

        // The table 'clatest_overrides' contains any user or group overrides for users.
        // It should be included where data exists for a user.
        $items->add_database_table('clatest_overrides', [
                'clatest'                  => 'privacy:metadata:clatest_overrides:clatest',
                'userid'                => 'privacy:metadata:clatest_overrides:userid',
                'timeopen'              => 'privacy:metadata:clatest_overrides:timeopen',
                'timeclose'             => 'privacy:metadata:clatest_overrides:timeclose',
                'timelimit'             => 'privacy:metadata:clatest_overrides:timelimit',
            ], 'privacy:metadata:clatest_overrides');

        // These define the structure of the clatest.

        // The table 'clatest_sections' contains data about the structure of a clatest.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'clatest_slots' contains data about the structure of a clatest.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'clatest_reports' does not contain any user identifying data and does not need a mapping.

        // The table 'clatest_statistics' contains abstract statistics about question usage and cannot be mapped to any
        // specific user.
        // It does not contain any user identifying data and does not need a mapping.

        // The clatest links to the 'core_question' subsystem for all question functionality.
        $items->add_subsystem_link('core_question', [], 'privacy:metadata:core_question');

        // The clatest has two subplugins..
        $items->add_plugintype_link('clatest', [], 'privacy:metadata:clatest');
        $items->add_plugintype_link('clatestaccess', [], 'privacy:metadata:clatestaccess');

        // Although the clatest supports the core_completion API and defines custom completion items, these will be
        // noted by the manager as all activity modules are capable of supporting this functionality.

        return $items;
    }

    /**
     * Get the list of contexts where the specified user has attempted a clatest, or been involved with manual marking
     * and/or grading of a clatest.
     *
     * @param   int             $userid The user to search.
     * @return  contextlist     $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $resultset = new contextlist();

        // Users who attempted the clatest.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {clatest} q ON q.id = cm.instance
                  JOIN {clatest_attempts} qa ON qa.clatest = q.id
                 WHERE qa.userid = :userid AND qa.preview = 0";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'clatest', 'userid' => $userid];
        $resultset->add_from_sql($sql, $params);

        // Users with clatest overrides.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {clatest} q ON q.id = cm.instance
                  JOIN {clatest_overrides} qo ON qo.clatest = q.id
                 WHERE qo.userid = :userid";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'clatest', 'userid' => $userid];
        $resultset->add_from_sql($sql, $params);

        // Get the SQL used to link indirect question usages for the user.
        // This includes where a user is the manual marker on a question attempt.
        $qubaid = \core_question\privacy\provider::get_related_question_usages_for_user('rel', 'mod_clatest', 'qa.uniqueid', $userid);

        // Select the context of any clatest attempt where a user has an attempt, plus the related usages.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {clatest} q ON q.id = cm.instance
                  JOIN {clatest_attempts} qa ON qa.clatest = q.id
            " . $qubaid->from . "
            WHERE " . $qubaid->where() . " AND qa.preview = 0";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'clatest'] + $qubaid->from_where_params();
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
            'modname' => 'clatest',
        ];

        // Users who attempted the clatest.
        $sql = "SELECT qa.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {clatest} q ON q.id = cm.instance
                  JOIN {clatest_attempts} qa ON qa.clatest = q.id
                 WHERE cm.id = :cmid AND qa.preview = 0";
        $userlist->add_from_sql('userid', $sql, $params);

        // Users with clatest overrides.
        $sql = "SELECT qo.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {clatest} q ON q.id = cm.instance
                  JOIN {clatest_overrides} qo ON qo.clatest = q.id
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Question usages in context.
        // This includes where a user is the manual marker on a question attempt.
        $sql = "SELECT qa.uniqueid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {clatest} q ON q.id = cm.instance
                  JOIN {clatest_attempts} qa ON qa.clatest = q.id
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
            INNER JOIN {clatest} q ON q.id = cm.instance
             LEFT JOIN {clatest_overrides} qo ON qo.clatest = q.id AND qo.userid = :qouserid
             LEFT JOIN {clatest_grades} qg ON qg.clatest = q.id AND qg.userid = :qguserid
                 WHERE c.id {$contextsql}";

        $params = [
            'contextlevel'      => CONTEXT_MODULE,
            'modname'           => 'clatest',
            'qguserid'          => $userid,
            'qouserid'          => $userid,
        ];
        $params += $contextparams;

        // Fetch the individual clatestzes.
        $clatestzes = $DB->get_recordset_sql($sql, $params);
        foreach ($clatestzes as $clatest) {
            list($course, $cm) = get_course_and_cm_from_cmid($clatest->cmid, 'clatest');
            $clatestobj = new \clatest($clatest, $cm, $course);
            $context = $clatestobj->get_context();

            $clatestdata = \core_privacy\local\request\helper::get_context_data($context, $contextlist->get_user());
            \core_privacy\local\request\helper::export_context_files($context, $contextlist->get_user());

            if (!empty($clatestdata->timeopen)) {
                $clatestdata->timeopen = transform::datetime($clatest->timeopen);
            }
            if (!empty($clatestdata->timeclose)) {
                $clatestdata->timeclose = transform::datetime($clatest->timeclose);
            }
            if (!empty($clatestdata->timelimit)) {
                $clatestdata->timelimit = $clatest->timelimit;
            }

            if (!empty($clatest->hasoverride)) {
                $clatestdata->override = (object) [];

                if (!empty($clatestdata->override_override_timeopen)) {
                    $clatestdata->override->timeopen = transform::datetime($clatest->override_timeopen);
                }
                if (!empty($clatestdata->override_timeclose)) {
                    $clatestdata->override->timeclose = transform::datetime($clatest->override_timeclose);
                }
                if (!empty($clatestdata->override_timelimit)) {
                    $clatestdata->override->timelimit = $clatest->override_timelimit;
                }
            }

            $clatestdata->accessdata = (object) [];

            $components = \core_component::get_plugin_list('clatestaccess');
            $exportparams = [
                    $clatestobj,
                    $user,
                ];
            foreach (array_keys($components) as $component) {
                $classname = manager::get_provider_classname_for_component("clatestaccess_$component");
                if (class_exists($classname) && is_subclass_of($classname, clatestaccess_provider::class)) {
                    $result = component_class_callback($classname, 'export_clatestaccess_user_data', $exportparams);
                    if (count((array) $result)) {
                        $clatestdata->accessdata->$component = $result;
                    }
                }
            }

            if (empty((array) $clatestdata->accessdata)) {
                unset($clatestdata->accessdata);
            }

            writer::with_context($context)
                ->export_data([], $clatestdata);
        }
        $clatestzes->close();

        // Store all clatest attempt data.
        static::export_clatest_attempts($contextlist);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context                 $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only clatest module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('clatest', $context->instanceid);
        if (!$cm) {
            // Only clatest module will be handled.
            return;
        }

        $clatestobj = \clatest::create($cm->instance);
        $clatest = $clatestobj->get_clatest();

        // Handle the 'clatestaccess' subplugin.
        manager::plugintype_class_callback(
                'clatestaccess',
                clatestaccess_provider::class,
                'delete_subplugin_data_for_all_users_in_context',
                [$clatestobj]
            );

        // Delete all overrides - do not log.
        clatest_delete_all_overrides($clatest, false);

        // This will delete all question attempts, clatest attempts, and clatest grades for this clatest.
        clatest_delete_all_attempts($clatest);
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
            // Only clatest module will be handled.
                continue;
            }

            $cm = get_coursemodule_from_id('clatest', $context->instanceid);
            if (!$cm) {
                // Only clatest module will be handled.
                continue;
            }

            // Fetch the details of the data to be removed.
            $clatestobj = \clatest::create($cm->instance);
            $clatest = $clatestobj->get_clatest();
            $user = $contextlist->get_user();

            // Handle the 'clatestaccess' clatestaccess.
            manager::plugintype_class_callback(
                    'clatestaccess',
                    clatestaccess_provider::class,
                    'delete_clatestaccess_data_for_user',
                    [$clatestobj, $user]
                );

            // Remove overrides for this user.
            $overrides = $DB->get_records('clatest_overrides' , [
                'clatest' => $clatestobj->get_clatestid(),
                'userid' => $user->id,
            ]);

            foreach ($overrides as $override) {
                clatest_delete_override($clatest, $override->id, false);
            }

            // This will delete all question attempts, clatest attempts, and clatest grades for this clatest.
            clatest_delete_user_attempts($clatestobj, $user);
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
            // Only clatest module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('clatest', $context->instanceid);
        if (!$cm) {
            // Only clatest module will be handled.
            return;
        }

        $clatestobj = \clatest::create($cm->instance);
        $clatest = $clatestobj->get_clatest();

        $userids = $userlist->get_userids();

        // Handle the 'clatestaccess' clatestaccess.
        manager::plugintype_class_callback(
                'clatestaccess',
                clatestaccess_user_provider::class,
                'delete_clatestaccess_data_for_users',
                [$userlist]
        );

        foreach ($userids as $userid) {
            // Remove overrides for this user.
            $overrides = $DB->get_records('clatest_overrides' , [
                'clatest' => $clatestobj->get_clatestid(),
                'userid' => $userid,
            ]);

            foreach ($overrides as $override) {
                clatest_delete_override($clatest, $override->id, false);
            }

            // This will delete all question attempts, clatest attempts, and clatest grades for this user in the given clatest.
            clatest_delete_user_attempts($clatestobj, (object)['id' => $userid]);
        }
    }

    /**
     * Store all clatest attempts for the contextlist.
     *
     * @param   approved_contextlist    $contextlist
     */
    protected static function export_clatest_attempts(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $qubaid = \core_question\privacy\provider::get_related_question_usages_for_user('rel', 'mod_clatest', 'qa.uniqueid', $userid);

        $sql = "SELECT
                    c.id AS contextid,
                    cm.id AS cmid,
                    qa.*
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'clatest'
                  JOIN {clatest} q ON q.id = cm.instance
                  JOIN {clatest_attempts} qa ON qa.clatest = q.id
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
            $clatest = $DB->get_record('clatest', ['id' => $attempt->clatest]);
            $context = \context_module::instance($attempt->cmid);
            $attemptsubcontext = helper::get_clatest_attempt_subcontext($attempt, $contextlist->get_user());
            $options = clatest_get_review_options($clatest, $attempt, $context);

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

                // Store the clatest attempt data.
                $data = (object) [
                    'state' => \clatest_attempt::state_name($attempt->state),
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
                    $grade = clatest_rescale_grade($attempt->sumgrades, $clatest, false);
                    $data->grade = (object) [
                            'grade' => clatest_format_grade($clatest, $grade),
                            'feedback' => clatest_feedback_for_grade($grade, $clatest, $context),
                        ];
                }

                writer::with_context($context)
                    ->export_data($attemptsubcontext, $data);
            } else {
                // This attempt was made by another user.
                // The current user may have marked part of the clatest attempt.
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
