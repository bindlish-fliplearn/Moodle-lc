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
 * Privacy Subsystem implementation for mod_flipquiz.
 *
 * @package    mod_flipquiz
 * @category   privacy
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_flipquiz\privacy;

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

require_once($CFG->dirroot . '/mod/flipquiz/lib.php');
require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

/**
 * Privacy Subsystem implementation for mod_flipquiz.
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
        // The table 'flipquiz' stores a record for each flipquiz.
        // It does not contain user personal data, but data is returned from it for contextual requirements.

        // The table 'flipquiz_attempts' stores a record of each flipquiz attempt.
        // It contains a userid which links to the user making the attempt and contains information about that attempt.
        $items->add_database_table('flipquiz_attempts', [
                'attempt'               => 'privacy:metadata:flipquiz_attempts:attempt',
                'currentpage'           => 'privacy:metadata:flipquiz_attempts:currentpage',
                'preview'               => 'privacy:metadata:flipquiz_attempts:preview',
                'state'                 => 'privacy:metadata:flipquiz_attempts:state',
                'timestart'             => 'privacy:metadata:flipquiz_attempts:timestart',
                'timefinish'            => 'privacy:metadata:flipquiz_attempts:timefinish',
                'timemodified'          => 'privacy:metadata:flipquiz_attempts:timemodified',
                'timemodifiedoffline'   => 'privacy:metadata:flipquiz_attempts:timemodifiedoffline',
                'timecheckstate'        => 'privacy:metadata:flipquiz_attempts:timecheckstate',
                'sumgrades'             => 'privacy:metadata:flipquiz_attempts:sumgrades',
            ], 'privacy:metadata:flipquiz_attempts');

        // The table 'flipquiz_feedback' contains the feedback responses which will be shown to users depending upon the
        // grade they achieve in the flipquiz.
        // It does not identify the user who wrote the feedback item so cannot be returned directly and is not
        // described, but relevant feedback items will be included with the flipquiz export for a user who has a grade.

        // The table 'flipquiz_grades' contains the current grade for each flipquiz/user combination.
        $items->add_database_table('flipquiz_grades', [
                'flipquiz'                  => 'privacy:metadata:flipquiz_grades:flipquiz',
                'userid'                => 'privacy:metadata:flipquiz_grades:userid',
                'grade'                 => 'privacy:metadata:flipquiz_grades:grade',
                'timemodified'          => 'privacy:metadata:flipquiz_grades:timemodified',
            ], 'privacy:metadata:flipquiz_grades');

        // The table 'flipquiz_overrides' contains any user or group overrides for users.
        // It should be included where data exists for a user.
        $items->add_database_table('flipquiz_overrides', [
                'flipquiz'                  => 'privacy:metadata:flipquiz_overrides:flipquiz',
                'userid'                => 'privacy:metadata:flipquiz_overrides:userid',
                'timeopen'              => 'privacy:metadata:flipquiz_overrides:timeopen',
                'timeclose'             => 'privacy:metadata:flipquiz_overrides:timeclose',
                'timelimit'             => 'privacy:metadata:flipquiz_overrides:timelimit',
            ], 'privacy:metadata:flipquiz_overrides');

        // These define the structure of the flipquiz.

        // The table 'flipquiz_sections' contains data about the structure of a flipquiz.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'flipquiz_slots' contains data about the structure of a flipquiz.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'flipquiz_reports' does not contain any user identifying data and does not need a mapping.

        // The table 'flipquiz_statistics' contains abstract statistics about question usage and cannot be mapped to any
        // specific user.
        // It does not contain any user identifying data and does not need a mapping.

        // The flipquiz links to the 'core_question' subsystem for all question functionality.
        $items->add_subsystem_link('core_question', [], 'privacy:metadata:core_question');

        // The flipquiz has two subplugins..
        $items->add_plugintype_link('flipquiz', [], 'privacy:metadata:flipquiz');
        $items->add_plugintype_link('flipquizaccess', [], 'privacy:metadata:flipquizaccess');

        // Although the flipquiz supports the core_completion API and defines custom completion items, these will be
        // noted by the manager as all activity modules are capable of supporting this functionality.

        return $items;
    }

    /**
     * Get the list of contexts where the specified user has attempted a flipquiz, or been involved with manual marking
     * and/or grading of a flipquiz.
     *
     * @param   int             $userid The user to search.
     * @return  contextlist     $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $resultset = new contextlist();

        // Users who attempted the flipquiz.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {flipquiz} q ON q.id = cm.instance
                  JOIN {flipquiz_attempts} qa ON qa.flipquiz = q.id
                 WHERE qa.userid = :userid AND qa.preview = 0";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'flipquiz', 'userid' => $userid];
        $resultset->add_from_sql($sql, $params);

        // Users with flipquiz overrides.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {flipquiz} q ON q.id = cm.instance
                  JOIN {flipquiz_overrides} qo ON qo.flipquiz = q.id
                 WHERE qo.userid = :userid";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'flipquiz', 'userid' => $userid];
        $resultset->add_from_sql($sql, $params);

        // Get the SQL used to link indirect question usages for the user.
        // This includes where a user is the manual marker on a question attempt.
        $qubaid = \core_question\privacy\provider::get_related_question_usages_for_user('rel', 'mod_flipquiz', 'qa.uniqueid', $userid);

        // Select the context of any flipquiz attempt where a user has an attempt, plus the related usages.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {flipquiz} q ON q.id = cm.instance
                  JOIN {flipquiz_attempts} qa ON qa.flipquiz = q.id
            " . $qubaid->from . "
            WHERE " . $qubaid->where() . " AND qa.preview = 0";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'flipquiz'] + $qubaid->from_where_params();
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
            'modname' => 'flipquiz',
        ];

        // Users who attempted the flipquiz.
        $sql = "SELECT qa.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {flipquiz} q ON q.id = cm.instance
                  JOIN {flipquiz_attempts} qa ON qa.flipquiz = q.id
                 WHERE cm.id = :cmid AND qa.preview = 0";
        $userlist->add_from_sql('userid', $sql, $params);

        // Users with flipquiz overrides.
        $sql = "SELECT qo.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {flipquiz} q ON q.id = cm.instance
                  JOIN {flipquiz_overrides} qo ON qo.flipquiz = q.id
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Question usages in context.
        // This includes where a user is the manual marker on a question attempt.
        $sql = "SELECT qa.uniqueid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {flipquiz} q ON q.id = cm.instance
                  JOIN {flipquiz_attempts} qa ON qa.flipquiz = q.id
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
            INNER JOIN {flipquiz} q ON q.id = cm.instance
             LEFT JOIN {flipquiz_overrides} qo ON qo.flipquiz = q.id AND qo.userid = :qouserid
             LEFT JOIN {flipquiz_grades} qg ON qg.flipquiz = q.id AND qg.userid = :qguserid
                 WHERE c.id {$contextsql}";

        $params = [
            'contextlevel'      => CONTEXT_MODULE,
            'modname'           => 'flipquiz',
            'qguserid'          => $userid,
            'qouserid'          => $userid,
        ];
        $params += $contextparams;

        // Fetch the individual flipquizzes.
        $flipquizzes = $DB->get_recordset_sql($sql, $params);
        foreach ($flipquizzes as $flipquiz) {
            list($course, $cm) = get_course_and_cm_from_cmid($flipquiz->cmid, 'flipquiz');
            $flipquizobj = new \flipquiz($flipquiz, $cm, $course);
            $context = $flipquizobj->get_context();

            $flipquizdata = \core_privacy\local\request\helper::get_context_data($context, $contextlist->get_user());
            \core_privacy\local\request\helper::export_context_files($context, $contextlist->get_user());

            if (!empty($flipquizdata->timeopen)) {
                $flipquizdata->timeopen = transform::datetime($flipquiz->timeopen);
            }
            if (!empty($flipquizdata->timeclose)) {
                $flipquizdata->timeclose = transform::datetime($flipquiz->timeclose);
            }
            if (!empty($flipquizdata->timelimit)) {
                $flipquizdata->timelimit = $flipquiz->timelimit;
            }

            if (!empty($flipquiz->hasoverride)) {
                $flipquizdata->override = (object) [];

                if (!empty($flipquizdata->override_override_timeopen)) {
                    $flipquizdata->override->timeopen = transform::datetime($flipquiz->override_timeopen);
                }
                if (!empty($flipquizdata->override_timeclose)) {
                    $flipquizdata->override->timeclose = transform::datetime($flipquiz->override_timeclose);
                }
                if (!empty($flipquizdata->override_timelimit)) {
                    $flipquizdata->override->timelimit = $flipquiz->override_timelimit;
                }
            }

            $flipquizdata->accessdata = (object) [];

            $components = \core_component::get_plugin_list('flipquizaccess');
            $exportparams = [
                    $flipquizobj,
                    $user,
                ];
            foreach (array_keys($components) as $component) {
                $classname = manager::get_provider_classname_for_component("flipquizaccess_$component");
                if (class_exists($classname) && is_subclass_of($classname, flipquizaccess_provider::class)) {
                    $result = component_class_callback($classname, 'export_flipquizaccess_user_data', $exportparams);
                    if (count((array) $result)) {
                        $flipquizdata->accessdata->$component = $result;
                    }
                }
            }

            if (empty((array) $flipquizdata->accessdata)) {
                unset($flipquizdata->accessdata);
            }

            writer::with_context($context)
                ->export_data([], $flipquizdata);
        }
        $flipquizzes->close();

        // Store all flipquiz attempt data.
        static::export_flipquiz_attempts($contextlist);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context                 $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only flipquiz module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('flipquiz', $context->instanceid);
        if (!$cm) {
            // Only flipquiz module will be handled.
            return;
        }

        $flipquizobj = \flipquiz::create($cm->instance);
        $flipquiz = $flipquizobj->get_flipquiz();

        // Handle the 'flipquizaccess' subplugin.
        manager::plugintype_class_callback(
                'flipquizaccess',
                flipquizaccess_provider::class,
                'delete_subplugin_data_for_all_users_in_context',
                [$flipquizobj]
            );

        // Delete all overrides - do not log.
        flipquiz_delete_all_overrides($flipquiz, false);

        // This will delete all question attempts, flipquiz attempts, and flipquiz grades for this flipquiz.
        flipquiz_delete_all_attempts($flipquiz);
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
            // Only flipquiz module will be handled.
                continue;
            }

            $cm = get_coursemodule_from_id('flipquiz', $context->instanceid);
            if (!$cm) {
                // Only flipquiz module will be handled.
                continue;
            }

            // Fetch the details of the data to be removed.
            $flipquizobj = \flipquiz::create($cm->instance);
            $flipquiz = $flipquizobj->get_flipquiz();
            $user = $contextlist->get_user();

            // Handle the 'flipquizaccess' flipquizaccess.
            manager::plugintype_class_callback(
                    'flipquizaccess',
                    flipquizaccess_provider::class,
                    'delete_flipquizaccess_data_for_user',
                    [$flipquizobj, $user]
                );

            // Remove overrides for this user.
            $overrides = $DB->get_records('flipquiz_overrides' , [
                'flipquiz' => $flipquizobj->get_flipquizid(),
                'userid' => $user->id,
            ]);

            foreach ($overrides as $override) {
                flipquiz_delete_override($flipquiz, $override->id, false);
            }

            // This will delete all question attempts, flipquiz attempts, and flipquiz grades for this flipquiz.
            flipquiz_delete_user_attempts($flipquizobj, $user);
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
            // Only flipquiz module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('flipquiz', $context->instanceid);
        if (!$cm) {
            // Only flipquiz module will be handled.
            return;
        }

        $flipquizobj = \flipquiz::create($cm->instance);
        $flipquiz = $flipquizobj->get_flipquiz();

        $userids = $userlist->get_userids();

        // Handle the 'flipquizaccess' flipquizaccess.
        manager::plugintype_class_callback(
                'flipquizaccess',
                flipquizaccess_user_provider::class,
                'delete_flipquizaccess_data_for_users',
                [$userlist]
        );

        foreach ($userids as $userid) {
            // Remove overrides for this user.
            $overrides = $DB->get_records('flipquiz_overrides' , [
                'flipquiz' => $flipquizobj->get_flipquizid(),
                'userid' => $userid,
            ]);

            foreach ($overrides as $override) {
                flipquiz_delete_override($flipquiz, $override->id, false);
            }

            // This will delete all question attempts, flipquiz attempts, and flipquiz grades for this user in the given flipquiz.
            flipquiz_delete_user_attempts($flipquizobj, (object)['id' => $userid]);
        }
    }

    /**
     * Store all flipquiz attempts for the contextlist.
     *
     * @param   approved_contextlist    $contextlist
     */
    protected static function export_flipquiz_attempts(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $qubaid = \core_question\privacy\provider::get_related_question_usages_for_user('rel', 'mod_flipquiz', 'qa.uniqueid', $userid);

        $sql = "SELECT
                    c.id AS contextid,
                    cm.id AS cmid,
                    qa.*
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'flipquiz'
                  JOIN {flipquiz} q ON q.id = cm.instance
                  JOIN {flipquiz_attempts} qa ON qa.flipquiz = q.id
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
            $flipquiz = $DB->get_record('flipquiz', ['id' => $attempt->flipquiz]);
            $context = \context_module::instance($attempt->cmid);
            $attemptsubcontext = helper::get_flipquiz_attempt_subcontext($attempt, $contextlist->get_user());
            $options = flipquiz_get_review_options($flipquiz, $attempt, $context);

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

                // Store the flipquiz attempt data.
                $data = (object) [
                    'state' => \flipquiz_attempt::state_name($attempt->state),
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
                    $grade = flipquiz_rescale_grade($attempt->sumgrades, $flipquiz, false);
                    $data->grade = (object) [
                            'grade' => flipquiz_format_grade($flipquiz, $grade),
                            'feedback' => flipquiz_feedback_for_grade($grade, $flipquiz, $context),
                        ];
                }

                writer::with_context($context)
                    ->export_data($attemptsubcontext, $data);
            } else {
                // This attempt was made by another user.
                // The current user may have marked part of the flipquiz attempt.
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
