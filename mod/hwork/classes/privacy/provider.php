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
 * Privacy Subsystem implementation for mod_hwork.
 *
 * @package    mod_hwork
 * @category   privacy
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_hwork\privacy;

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

require_once($CFG->dirroot . '/mod/hwork/lib.php');
require_once($CFG->dirroot . '/mod/hwork/locallib.php');

/**
 * Privacy Subsystem implementation for mod_hwork.
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
        // The table 'hwork' stores a record for each hwork.
        // It does not contain user personal data, but data is returned from it for contextual requirements.

        // The table 'hwork_attempts' stores a record of each hwork attempt.
        // It contains a userid which links to the user making the attempt and contains information about that attempt.
        $items->add_database_table('hwork_attempts', [
                'attempt'               => 'privacy:metadata:hwork_attempts:attempt',
                'currentpage'           => 'privacy:metadata:hwork_attempts:currentpage',
                'preview'               => 'privacy:metadata:hwork_attempts:preview',
                'state'                 => 'privacy:metadata:hwork_attempts:state',
                'timestart'             => 'privacy:metadata:hwork_attempts:timestart',
                'timefinish'            => 'privacy:metadata:hwork_attempts:timefinish',
                'timemodified'          => 'privacy:metadata:hwork_attempts:timemodified',
                'timemodifiedoffline'   => 'privacy:metadata:hwork_attempts:timemodifiedoffline',
                'timecheckstate'        => 'privacy:metadata:hwork_attempts:timecheckstate',
                'sumgrades'             => 'privacy:metadata:hwork_attempts:sumgrades',
            ], 'privacy:metadata:hwork_attempts');

        // The table 'hwork_feedback' contains the feedback responses which will be shown to users depending upon the
        // grade they achieve in the hwork.
        // It does not identify the user who wrote the feedback item so cannot be returned directly and is not
        // described, but relevant feedback items will be included with the hwork export for a user who has a grade.

        // The table 'hwork_grades' contains the current grade for each hwork/user combination.
        $items->add_database_table('hwork_grades', [
                'hwork'                  => 'privacy:metadata:hwork_grades:hwork',
                'userid'                => 'privacy:metadata:hwork_grades:userid',
                'grade'                 => 'privacy:metadata:hwork_grades:grade',
                'timemodified'          => 'privacy:metadata:hwork_grades:timemodified',
            ], 'privacy:metadata:hwork_grades');

        // The table 'hwork_overrides' contains any user or group overrides for users.
        // It should be included where data exists for a user.
        $items->add_database_table('hwork_overrides', [
                'hwork'                  => 'privacy:metadata:hwork_overrides:hwork',
                'userid'                => 'privacy:metadata:hwork_overrides:userid',
                'timeopen'              => 'privacy:metadata:hwork_overrides:timeopen',
                'timeclose'             => 'privacy:metadata:hwork_overrides:timeclose',
                'timelimit'             => 'privacy:metadata:hwork_overrides:timelimit',
            ], 'privacy:metadata:hwork_overrides');

        // These define the structure of the hwork.

        // The table 'hwork_sections' contains data about the structure of a hwork.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'hwork_slots' contains data about the structure of a hwork.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'hwork_reports' does not contain any user identifying data and does not need a mapping.

        // The table 'hwork_statistics' contains abstract statistics about question usage and cannot be mapped to any
        // specific user.
        // It does not contain any user identifying data and does not need a mapping.

        // The hwork links to the 'core_question' subsystem for all question functionality.
        $items->add_subsystem_link('core_question', [], 'privacy:metadata:core_question');

        // The hwork has two subplugins..
        $items->add_plugintype_link('hwork', [], 'privacy:metadata:hwork');
        $items->add_plugintype_link('hworkaccess', [], 'privacy:metadata:hworkaccess');

        // Although the hwork supports the core_completion API and defines custom completion items, these will be
        // noted by the manager as all activity modules are capable of supporting this functionality.

        return $items;
    }

    /**
     * Get the list of contexts where the specified user has attempted a hwork, or been involved with manual marking
     * and/or grading of a hwork.
     *
     * @param   int             $userid The user to search.
     * @return  contextlist     $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $resultset = new contextlist();

        // Users who attempted the hwork.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {hwork} q ON q.id = cm.instance
                  JOIN {hwork_attempts} qa ON qa.hwork = q.id
                 WHERE qa.userid = :userid AND qa.preview = 0";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'hwork', 'userid' => $userid];
        $resultset->add_from_sql($sql, $params);

        // Users with hwork overrides.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {hwork} q ON q.id = cm.instance
                  JOIN {hwork_overrides} qo ON qo.hwork = q.id
                 WHERE qo.userid = :userid";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'hwork', 'userid' => $userid];
        $resultset->add_from_sql($sql, $params);

        // Get the SQL used to link indirect question usages for the user.
        // This includes where a user is the manual marker on a question attempt.
        $qubaid = \core_question\privacy\provider::get_related_question_usages_for_user('rel', 'mod_hwork', 'qa.uniqueid', $userid);

        // Select the context of any hwork attempt where a user has an attempt, plus the related usages.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {hwork} q ON q.id = cm.instance
                  JOIN {hwork_attempts} qa ON qa.hwork = q.id
            " . $qubaid->from . "
            WHERE " . $qubaid->where() . " AND qa.preview = 0";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'hwork'] + $qubaid->from_where_params();
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
            'modname' => 'hwork',
        ];

        // Users who attempted the hwork.
        $sql = "SELECT qa.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {hwork} q ON q.id = cm.instance
                  JOIN {hwork_attempts} qa ON qa.hwork = q.id
                 WHERE cm.id = :cmid AND qa.preview = 0";
        $userlist->add_from_sql('userid', $sql, $params);

        // Users with hwork overrides.
        $sql = "SELECT qo.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {hwork} q ON q.id = cm.instance
                  JOIN {hwork_overrides} qo ON qo.hwork = q.id
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Question usages in context.
        // This includes where a user is the manual marker on a question attempt.
        $sql = "SELECT qa.uniqueid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {hwork} q ON q.id = cm.instance
                  JOIN {hwork_attempts} qa ON qa.hwork = q.id
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
            INNER JOIN {hwork} q ON q.id = cm.instance
             LEFT JOIN {hwork_overrides} qo ON qo.hwork = q.id AND qo.userid = :qouserid
             LEFT JOIN {hwork_grades} qg ON qg.hwork = q.id AND qg.userid = :qguserid
                 WHERE c.id {$contextsql}";

        $params = [
            'contextlevel'      => CONTEXT_MODULE,
            'modname'           => 'hwork',
            'qguserid'          => $userid,
            'qouserid'          => $userid,
        ];
        $params += $contextparams;

        // Fetch the individual hworkzes.
        $hworkzes = $DB->get_recordset_sql($sql, $params);
        foreach ($hworkzes as $hwork) {
            list($course, $cm) = get_course_and_cm_from_cmid($hwork->cmid, 'hwork');
            $hworkobj = new \hwork($hwork, $cm, $course);
            $context = $hworkobj->get_context();

            $hworkdata = \core_privacy\local\request\helper::get_context_data($context, $contextlist->get_user());
            \core_privacy\local\request\helper::export_context_files($context, $contextlist->get_user());

            if (!empty($hworkdata->timeopen)) {
                $hworkdata->timeopen = transform::datetime($hwork->timeopen);
            }
            if (!empty($hworkdata->timeclose)) {
                $hworkdata->timeclose = transform::datetime($hwork->timeclose);
            }
            if (!empty($hworkdata->timelimit)) {
                $hworkdata->timelimit = $hwork->timelimit;
            }

            if (!empty($hwork->hasoverride)) {
                $hworkdata->override = (object) [];

                if (!empty($hworkdata->override_override_timeopen)) {
                    $hworkdata->override->timeopen = transform::datetime($hwork->override_timeopen);
                }
                if (!empty($hworkdata->override_timeclose)) {
                    $hworkdata->override->timeclose = transform::datetime($hwork->override_timeclose);
                }
                if (!empty($hworkdata->override_timelimit)) {
                    $hworkdata->override->timelimit = $hwork->override_timelimit;
                }
            }

            $hworkdata->accessdata = (object) [];

            $components = \core_component::get_plugin_list('hworkaccess');
            $exportparams = [
                    $hworkobj,
                    $user,
                ];
            foreach (array_keys($components) as $component) {
                $classname = manager::get_provider_classname_for_component("hworkaccess_$component");
                if (class_exists($classname) && is_subclass_of($classname, hworkaccess_provider::class)) {
                    $result = component_class_callback($classname, 'export_hworkaccess_user_data', $exportparams);
                    if (count((array) $result)) {
                        $hworkdata->accessdata->$component = $result;
                    }
                }
            }

            if (empty((array) $hworkdata->accessdata)) {
                unset($hworkdata->accessdata);
            }

            writer::with_context($context)
                ->export_data([], $hworkdata);
        }
        $hworkzes->close();

        // Store all hwork attempt data.
        static::export_hwork_attempts($contextlist);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context                 $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only hwork module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('hwork', $context->instanceid);
        if (!$cm) {
            // Only hwork module will be handled.
            return;
        }

        $hworkobj = \hwork::create($cm->instance);
        $hwork = $hworkobj->get_hwork();

        // Handle the 'hworkaccess' subplugin.
        manager::plugintype_class_callback(
                'hworkaccess',
                hworkaccess_provider::class,
                'delete_subplugin_data_for_all_users_in_context',
                [$hworkobj]
            );

        // Delete all overrides - do not log.
        hwork_delete_all_overrides($hwork, false);

        // This will delete all question attempts, hwork attempts, and hwork grades for this hwork.
        hwork_delete_all_attempts($hwork);
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
            // Only hwork module will be handled.
                continue;
            }

            $cm = get_coursemodule_from_id('hwork', $context->instanceid);
            if (!$cm) {
                // Only hwork module will be handled.
                continue;
            }

            // Fetch the details of the data to be removed.
            $hworkobj = \hwork::create($cm->instance);
            $hwork = $hworkobj->get_hwork();
            $user = $contextlist->get_user();

            // Handle the 'hworkaccess' hworkaccess.
            manager::plugintype_class_callback(
                    'hworkaccess',
                    hworkaccess_provider::class,
                    'delete_hworkaccess_data_for_user',
                    [$hworkobj, $user]
                );

            // Remove overrides for this user.
            $overrides = $DB->get_records('hwork_overrides' , [
                'hwork' => $hworkobj->get_hworkid(),
                'userid' => $user->id,
            ]);

            foreach ($overrides as $override) {
                hwork_delete_override($hwork, $override->id, false);
            }

            // This will delete all question attempts, hwork attempts, and hwork grades for this hwork.
            hwork_delete_user_attempts($hworkobj, $user);
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
            // Only hwork module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('hwork', $context->instanceid);
        if (!$cm) {
            // Only hwork module will be handled.
            return;
        }

        $hworkobj = \hwork::create($cm->instance);
        $hwork = $hworkobj->get_hwork();

        $userids = $userlist->get_userids();

        // Handle the 'hworkaccess' hworkaccess.
        manager::plugintype_class_callback(
                'hworkaccess',
                hworkaccess_user_provider::class,
                'delete_hworkaccess_data_for_users',
                [$userlist]
        );

        foreach ($userids as $userid) {
            // Remove overrides for this user.
            $overrides = $DB->get_records('hwork_overrides' , [
                'hwork' => $hworkobj->get_hworkid(),
                'userid' => $userid,
            ]);

            foreach ($overrides as $override) {
                hwork_delete_override($hwork, $override->id, false);
            }

            // This will delete all question attempts, hwork attempts, and hwork grades for this user in the given hwork.
            hwork_delete_user_attempts($hworkobj, (object)['id' => $userid]);
        }
    }

    /**
     * Store all hwork attempts for the contextlist.
     *
     * @param   approved_contextlist    $contextlist
     */
    protected static function export_hwork_attempts(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $qubaid = \core_question\privacy\provider::get_related_question_usages_for_user('rel', 'mod_hwork', 'qa.uniqueid', $userid);

        $sql = "SELECT
                    c.id AS contextid,
                    cm.id AS cmid,
                    qa.*
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'hwork'
                  JOIN {hwork} q ON q.id = cm.instance
                  JOIN {hwork_attempts} qa ON qa.hwork = q.id
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
            $hwork = $DB->get_record('hwork', ['id' => $attempt->hwork]);
            $context = \context_module::instance($attempt->cmid);
            $attemptsubcontext = helper::get_hwork_attempt_subcontext($attempt, $contextlist->get_user());
            $options = hwork_get_review_options($hwork, $attempt, $context);

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

                // Store the hwork attempt data.
                $data = (object) [
                    'state' => \hwork_attempt::state_name($attempt->state),
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
                    $grade = hwork_rescale_grade($attempt->sumgrades, $hwork, false);
                    $data->grade = (object) [
                            'grade' => hwork_format_grade($hwork, $grade),
                            'feedback' => hwork_feedback_for_grade($grade, $hwork, $context),
                        ];
                }

                writer::with_context($context)
                    ->export_data($attemptsubcontext, $data);
            } else {
                // This attempt was made by another user.
                // The current user may have marked part of the hwork attempt.
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
