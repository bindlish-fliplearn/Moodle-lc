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
 * Library of functions for the flipquiz module.
 *
 * This contains functions that are called also from outside the flipquiz module
 * Functions that are only called by the flipquiz module itself are in {@link locallib.php}
 *
 * @package    mod_flipquiz
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->dirroot . '/calendar/lib.php');


/**#@+
 * Option controlling what options are offered on the flipquiz settings form.
 */
define('FLIPQUIZ_MAX_ATTEMPT_OPTION', 10);
define('FLIPQUIZ_MAX_QPP_OPTION', 50);
define('FLIPQUIZ_MAX_DECIMAL_OPTION', 5);
define('FLIPQUIZ_MAX_Q_DECIMAL_OPTION', 7);
/**#@-*/

/**#@+
 * Options determining how the grades from individual attempts are combined to give
 * the overall grade for a user
 */
define('FLIPQUIZ_GRADEHIGHEST', '1');
define('FLIPQUIZ_GRADEAVERAGE', '2');
define('FLIPQUIZ_ATTEMPTFIRST', '3');
define('FLIPQUIZ_ATTEMPTLAST',  '4');
/**#@-*/

/**
 * @var int If start and end date for the flipquiz are more than this many seconds apart
 * they will be represented by two separate events in the calendar
 */
define('FLIPQUIZ_MAX_EVENT_LENGTH', 5*24*60*60); // 5 days.

/**#@+
 * Options for navigation method within flipquizzes.
 */
define('FLIPQUIZ_NAVMETHOD_FREE', 'free');
define('FLIPQUIZ_NAVMETHOD_SEQ',  'sequential');
/**#@-*/

/**
 * Event types.
 */
define('FLIPQUIZ_EVENT_TYPE_OPEN', 'open');
define('FLIPQUIZ_EVENT_TYPE_CLOSE', 'close');

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $flipquiz the data that came from the form.
 * @return mixed the id of the new instance on success,
 *          false or a string error message on failure.
 */
function flipquiz_add_instance($flipquiz) {
    global $DB;
    $cmid = $flipquiz->coursemodule;

    // Process the options from the form.
    $flipquiz->created = time();
    $result = flipquiz_process_options($flipquiz);
    if ($result && is_string($result)) {
        return $result;
    }

    // Try to store it in the database.
    $flipquiz->id = $DB->insert_record('flipquiz', $flipquiz);

    // Create the first section for this flipquiz.
    $DB->insert_record('flipquiz_sections', array('flipquizid' => $flipquiz->id,
            'firstslot' => 1, 'heading' => '', 'shufflequestions' => 0));

    // Do the processing required after an add or an update.
    flipquiz_after_add_or_update($flipquiz);

    return $flipquiz->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $flipquiz the data that came from the form.
 * @return mixed true on success, false or a string error message on failure.
 */
function flipquiz_update_instance($flipquiz, $mform) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

    // Process the options from the form.
    $result = flipquiz_process_options($flipquiz);
    if ($result && is_string($result)) {
        return $result;
    }

    // Get the current value, so we can see what changed.
    $oldflipquiz = $DB->get_record('flipquiz', array('id' => $flipquiz->instance));

    // We need two values from the existing DB record that are not in the form,
    // in some of the function calls below.
    $flipquiz->sumgrades = $oldflipquiz->sumgrades;
    $flipquiz->grade     = $oldflipquiz->grade;

    // Update the database.
    $flipquiz->id = $flipquiz->instance;
    $DB->update_record('flipquiz', $flipquiz);

    // Do the processing required after an add or an update.
    flipquiz_after_add_or_update($flipquiz);

    if ($oldflipquiz->grademethod != $flipquiz->grademethod) {
        flipquiz_update_all_final_grades($flipquiz);
        flipquiz_update_grades($flipquiz);
    }

    $flipquizdateschanged = $oldflipquiz->timelimit   != $flipquiz->timelimit
                     || $oldflipquiz->timeclose   != $flipquiz->timeclose
                     || $oldflipquiz->graceperiod != $flipquiz->graceperiod;
    if ($flipquizdateschanged) {
        flipquiz_update_open_attempts(array('flipquizid' => $flipquiz->id));
    }

    // Delete any previous preview attempts.
    flipquiz_delete_previews($flipquiz);

    // Repaginate, if asked to.
    if (!empty($flipquiz->repaginatenow)) {
        flipquiz_repaginate_questions($flipquiz->id, $flipquiz->questionsperpage);
    }

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id the id of the flipquiz to delete.
 * @return bool success or failure.
 */
function flipquiz_delete_instance($id) {
    global $DB;

    $flipquiz = $DB->get_record('flipquiz', array('id' => $id), '*', MUST_EXIST);

    flipquiz_delete_all_attempts($flipquiz);
    flipquiz_delete_all_overrides($flipquiz);

    // Look for random questions that may no longer be used when this flipquiz is gone.
    $sql = "SELECT q.id
              FROM {flipquiz_slots} slot
              JOIN {question} q ON q.id = slot.questionid
             WHERE slot.flipquizid = ? AND q.qtype = ?";
    $questionids = $DB->get_fieldset_sql($sql, array($flipquiz->id, 'random'));

    // We need to do the following deletes before we try and delete randoms, otherwise they would still be 'in use'.
    $flipquizslots = $DB->get_fieldset_select('flipquiz_slots', 'id', 'flipquizid = ?', array($flipquiz->id));
    $DB->delete_records_list('flipquiz_slot_tags', 'slotid', $flipquizslots);
    $DB->delete_records('flipquiz_slots', array('flipquizid' => $flipquiz->id));
    $DB->delete_records('flipquiz_sections', array('flipquizid' => $flipquiz->id));

    foreach ($questionids as $questionid) {
        question_delete_question($questionid);
    }

    $DB->delete_records('flipquiz_feedback', array('flipquizid' => $flipquiz->id));

    flipquiz_access_manager::delete_settings($flipquiz);

    $events = $DB->get_records('event', array('modulename' => 'flipquiz', 'instance' => $flipquiz->id));
    foreach ($events as $event) {
        $event = calendar_event::load($event);
        $event->delete();
    }

    flipquiz_grade_item_delete($flipquiz);
    $DB->delete_records('flipquiz', array('id' => $flipquiz->id));

    return true;
}

/**
 * Deletes a flipquiz override from the database and clears any corresponding calendar events
 *
 * @param object $flipquiz The flipquiz object.
 * @param int $overrideid The id of the override being deleted
 * @param bool $log Whether to trigger logs.
 * @return bool true on success
 */
function flipquiz_delete_override($flipquiz, $overrideid, $log = true) {
    global $DB;

    if (!isset($flipquiz->cmid)) {
        $cm = get_coursemodule_from_instance('flipquiz', $flipquiz->id, $flipquiz->course);
        $flipquiz->cmid = $cm->id;
    }

    $override = $DB->get_record('flipquiz_overrides', array('id' => $overrideid), '*', MUST_EXIST);

    // Delete the events.
    if (isset($override->groupid)) {
        // Create the search array for a group override.
        $eventsearcharray = array('modulename' => 'flipquiz',
            'instance' => $flipquiz->id, 'groupid' => (int)$override->groupid);
    } else {
        // Create the search array for a user override.
        $eventsearcharray = array('modulename' => 'flipquiz',
            'instance' => $flipquiz->id, 'userid' => (int)$override->userid);
    }
    $events = $DB->get_records('event', $eventsearcharray);
    foreach ($events as $event) {
        $eventold = calendar_event::load($event);
        $eventold->delete();
    }

    $DB->delete_records('flipquiz_overrides', array('id' => $overrideid));

    if ($log) {
        // Set the common parameters for one of the events we will be triggering.
        $params = array(
            'objectid' => $override->id,
            'context' => context_module::instance($flipquiz->cmid),
            'other' => array(
                'flipquizid' => $override->flipquiz
            )
        );
        // Determine which override deleted event to fire.
        if (!empty($override->userid)) {
            $params['relateduserid'] = $override->userid;
            $event = \mod_flipquiz\event\user_override_deleted::create($params);
        } else {
            $params['other']['groupid'] = $override->groupid;
            $event = \mod_flipquiz\event\group_override_deleted::create($params);
        }

        // Trigger the override deleted event.
        $event->add_record_snapshot('flipquiz_overrides', $override);
        $event->trigger();
    }

    return true;
}

/**
 * Deletes all flipquiz overrides from the database and clears any corresponding calendar events
 *
 * @param object $flipquiz The flipquiz object.
 * @param bool $log Whether to trigger logs.
 */
function flipquiz_delete_all_overrides($flipquiz, $log = true) {
    global $DB;

    $overrides = $DB->get_records('flipquiz_overrides', array('flipquiz' => $flipquiz->id), 'id');
    foreach ($overrides as $override) {
        flipquiz_delete_override($flipquiz, $override->id, $log);
    }
}

/**
 * Updates a flipquiz object with override information for a user.
 *
 * Algorithm:  For each flipquiz setting, if there is a matching user-specific override,
 *   then use that otherwise, if there are group-specific overrides, return the most
 *   lenient combination of them.  If neither applies, leave the flipquiz setting unchanged.
 *
 *   Special case: if there is more than one password that applies to the user, then
 *   flipquiz->extrapasswords will contain an array of strings giving the remaining
 *   passwords.
 *
 * @param object $flipquiz The flipquiz object.
 * @param int $userid The userid.
 * @return object $flipquiz The updated flipquiz object.
 */
function flipquiz_update_effective_access($flipquiz, $userid) {
    global $DB;

    // Check for user override.
    $override = $DB->get_record('flipquiz_overrides', array('flipquiz' => $flipquiz->id, 'userid' => $userid));

    if (!$override) {
        $override = new stdClass();
        $override->timeopen = null;
        $override->timeclose = null;
        $override->timelimit = null;
        $override->attempts = null;
        $override->password = null;
    }

    // Check for group overrides.
    $groupings = groups_get_user_groups($flipquiz->course, $userid);

    if (!empty($groupings[0])) {
        // Select all overrides that apply to the User's groups.
        list($extra, $params) = $DB->get_in_or_equal(array_values($groupings[0]));
        $sql = "SELECT * FROM {flipquiz_overrides}
                WHERE groupid $extra AND flipquiz = ?";
        $params[] = $flipquiz->id;
        $records = $DB->get_records_sql($sql, $params);

        // Combine the overrides.
        $opens = array();
        $closes = array();
        $limits = array();
        $attempts = array();
        $passwords = array();

        foreach ($records as $gpoverride) {
            if (isset($gpoverride->timeopen)) {
                $opens[] = $gpoverride->timeopen;
            }
            if (isset($gpoverride->timeclose)) {
                $closes[] = $gpoverride->timeclose;
            }
            if (isset($gpoverride->timelimit)) {
                $limits[] = $gpoverride->timelimit;
            }
            if (isset($gpoverride->attempts)) {
                $attempts[] = $gpoverride->attempts;
            }
            if (isset($gpoverride->password)) {
                $passwords[] = $gpoverride->password;
            }
        }
        // If there is a user override for a setting, ignore the group override.
        if (is_null($override->timeopen) && count($opens)) {
            $override->timeopen = min($opens);
        }
        if (is_null($override->timeclose) && count($closes)) {
            if (in_array(0, $closes)) {
                $override->timeclose = 0;
            } else {
                $override->timeclose = max($closes);
            }
        }
        if (is_null($override->timelimit) && count($limits)) {
            if (in_array(0, $limits)) {
                $override->timelimit = 0;
            } else {
                $override->timelimit = max($limits);
            }
        }
        if (is_null($override->attempts) && count($attempts)) {
            if (in_array(0, $attempts)) {
                $override->attempts = 0;
            } else {
                $override->attempts = max($attempts);
            }
        }
        if (is_null($override->password) && count($passwords)) {
            $override->password = array_shift($passwords);
            if (count($passwords)) {
                $override->extrapasswords = $passwords;
            }
        }

    }

    // Merge with flipquiz defaults.
    $keys = array('timeopen', 'timeclose', 'timelimit', 'attempts', 'password', 'extrapasswords');
    foreach ($keys as $key) {
        if (isset($override->{$key})) {
            $flipquiz->{$key} = $override->{$key};
        }
    }

    return $flipquiz;
}

/**
 * Delete all the attempts belonging to a flipquiz.
 *
 * @param object $flipquiz The flipquiz object.
 */
function flipquiz_delete_all_attempts($flipquiz) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');
    question_engine::delete_questions_usage_by_activities(new qubaids_for_flipquiz($flipquiz->id));
    $DB->delete_records('flipquiz_attempts', array('flipquiz' => $flipquiz->id));
    $DB->delete_records('flipquiz_grades', array('flipquiz' => $flipquiz->id));
}

/**
 * Delete all the attempts belonging to a user in a particular flipquiz.
 *
 * @param object $flipquiz The flipquiz object.
 * @param object $user The user object.
 */
function flipquiz_delete_user_attempts($flipquiz, $user) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');
    question_engine::delete_questions_usage_by_activities(new qubaids_for_flipquiz_user($flipquiz->get_flipquizid(), $user->id));
    $params = [
        'flipquiz' => $flipquiz->get_flipquizid(),
        'userid' => $user->id,
    ];
    $DB->delete_records('flipquiz_attempts', $params);
    $DB->delete_records('flipquiz_grades', $params);
}

/**
 * Get the best current grade for a particular user in a flipquiz.
 *
 * @param object $flipquiz the flipquiz settings.
 * @param int $userid the id of the user.
 * @return float the user's current grade for this flipquiz, or null if this user does
 * not have a grade on this flipquiz.
 */
function flipquiz_get_best_grade($flipquiz, $userid) {
    global $DB;
    $grade = $DB->get_field('flipquiz_grades', 'grade',
            array('flipquiz' => $flipquiz->id, 'userid' => $userid));

    // Need to detect errors/no result, without catching 0 grades.
    if ($grade === false) {
        return null;
    }

    return $grade + 0; // Convert to number.
}

/**
 * Is this a graded flipquiz? If this method returns true, you can assume that
 * $flipquiz->grade and $flipquiz->sumgrades are non-zero (for example, if you want to
 * divide by them).
 *
 * @param object $flipquiz a row from the flipquiz table.
 * @return bool whether this is a graded flipquiz.
 */
function flipquiz_has_grades($flipquiz) {
    return $flipquiz->grade >= 0.000005 && $flipquiz->sumgrades >= 0.000005;
}

/**
 * Does this flipquiz allow multiple tries?
 *
 * @return bool
 */
function flipquiz_allows_multiple_tries($flipquiz) {
    $bt = question_engine::get_behaviour_type($flipquiz->preferredbehaviour);
    return $bt->allows_multiple_submitted_responses();
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $flipquiz
 * @return object|null
 */
function flipquiz_user_outline($course, $user, $mod, $flipquiz) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $grades = grade_get_grades($course->id, 'mod', 'flipquiz', $flipquiz->id, $user->id);

    if (empty($grades->items[0]->grades)) {
        return null;
    } else {
        $grade = reset($grades->items[0]->grades);
    }

    $result = new stdClass();
    // If the user can't see hidden grades, don't return that information.
    $gitem = grade_item::fetch(array('id' => $grades->items[0]->id));
    if (!$gitem->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
        $result->info = get_string('grade') . ': ' . $grade->str_long_grade;
    } else {
        $result->info = get_string('grade') . ': ' . get_string('hidden', 'grades');
    }

    // Datesubmitted == time created. dategraded == time modified or time overridden
    // if grade was last modified by the user themselves use date graded. Otherwise use
    // date submitted.
    // TODO: move this copied & pasted code somewhere in the grades API. See MDL-26704.
    if ($grade->usermodified == $user->id || empty($grade->datesubmitted)) {
        $result->time = $grade->dategraded;
    } else {
        $result->time = $grade->datesubmitted;
    }

    return $result;
}

/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $flipquiz
 * @return bool
 */
function flipquiz_user_complete($course, $user, $mod, $flipquiz) {
    global $DB, $CFG, $OUTPUT;
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

    $grades = grade_get_grades($course->id, 'mod', 'flipquiz', $flipquiz->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        // If the user can't see hidden grades, don't return that information.
        $gitem = grade_item::fetch(array('id' => $grades->items[0]->id));
        if (!$gitem->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            echo $OUTPUT->container(get_string('grade').': '.$grade->str_long_grade);
            if ($grade->str_feedback) {
                echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
            }
        } else {
            echo $OUTPUT->container(get_string('grade') . ': ' . get_string('hidden', 'grades'));
            if ($grade->str_feedback) {
                echo $OUTPUT->container(get_string('feedback').': '.get_string('hidden', 'grades'));
            }
        }
    }

    if ($attempts = $DB->get_records('flipquiz_attempts',
            array('userid' => $user->id, 'flipquiz' => $flipquiz->id), 'attempt')) {
        foreach ($attempts as $attempt) {
            echo get_string('attempt', 'flipquiz', $attempt->attempt) . ': ';
            if ($attempt->state != flipquiz_attempt::FINISHED) {
                echo flipquiz_attempt_state_name($attempt->state);
            } else {
                if (!isset($gitem)) {
                    if (!empty($grades->items[0]->grades)) {
                        $gitem = grade_item::fetch(array('id' => $grades->items[0]->id));
                    } else {
                        $gitem = new stdClass();
                        $gitem->hidden = true;
                    }
                }
                if (!$gitem->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
                    echo flipquiz_format_grade($flipquiz, $attempt->sumgrades) . '/' . flipquiz_format_grade($flipquiz, $flipquiz->sumgrades);
                } else {
                    echo get_string('hidden', 'grades');
                }
            }
            echo ' - '.userdate($attempt->timemodified).'<br />';
        }
    } else {
        print_string('noattempts', 'flipquiz');
    }

    return true;
}

/**
 * Quiz periodic clean-up tasks.
 */
function flipquiz_cron() {
    global $CFG;

    require_once($CFG->dirroot . '/mod/flipquiz/cronlib.php');
    mtrace('');

    $timenow = time();
    $overduehander = new mod_flipquiz_overdue_attempt_updater();

    $processto = $timenow - get_config('flipquiz', 'graceperiodmin');

    mtrace('  Looking for flipquiz overdue flipquiz attempts...');

    list($count, $flipquizcount) = $overduehander->update_overdue_attempts($timenow, $processto);

    mtrace('  Considered ' . $count . ' attempts in ' . $flipquizcount . ' flipquizzes.');

    // Run cron for our sub-plugin types.
    cron_execute_plugin_type('flipquiz', 'flipquiz reports');
    cron_execute_plugin_type('flipquizaccess', 'flipquiz access rules');

    return true;
}

/**
 * @param int|array $flipquizids A flipquiz ID, or an array of flipquiz IDs.
 * @param int $userid the userid.
 * @param string $status 'all', 'finished' or 'unfinished' to control
 * @param bool $includepreviews
 * @return an array of all the user's attempts at this flipquiz. Returns an empty
 *      array if there are none.
 */
function flipquiz_get_user_attempts($flipquizids, $userid, $status = 'finished', $includepreviews = false) {
    global $DB, $CFG;
    // TODO MDL-33071 it is very annoying to have to included all of locallib.php
    // just to get the flipquiz_attempt::FINISHED constants, but I will try to sort
    // that out properly for Moodle 2.4. For now, I will just do a quick fix for
    // MDL-33048.
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

    $params = array();
    switch ($status) {
        case 'all':
            $statuscondition = '';
            break;

        case 'finished':
            $statuscondition = ' AND state IN (:state1, :state2)';
            $params['state1'] = flipquiz_attempt::FINISHED;
            $params['state2'] = flipquiz_attempt::ABANDONED;
            break;

        case 'unfinished':
            $statuscondition = ' AND state IN (:state1, :state2)';
            $params['state1'] = flipquiz_attempt::IN_PROGRESS;
            $params['state2'] = flipquiz_attempt::OVERDUE;
            break;
    }

    $flipquizids = (array) $flipquizids;
    list($insql, $inparams) = $DB->get_in_or_equal($flipquizids, SQL_PARAMS_NAMED);
    $params += $inparams;
    $params['userid'] = $userid;

    $previewclause = '';
    if (!$includepreviews) {
        $previewclause = ' AND preview = 0';
    }

    return $DB->get_records_select('flipquiz_attempts',
            "flipquiz $insql AND userid = :userid" . $previewclause . $statuscondition,
            $params, 'flipquiz, attempt ASC');
}

/**
 * Return grade for given user or all users.
 *
 * @param int $flipquizid id of flipquiz
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none. These are raw grades. They should
 * be processed with flipquiz_format_grade for display.
 */
function flipquiz_get_user_grades($flipquiz, $userid = 0) {
    global $CFG, $DB;

    $params = array($flipquiz->id);
    $usertest = '';
    if ($userid) {
        $params[] = $userid;
        $usertest = 'AND u.id = ?';
    }
    return $DB->get_records_sql("
            SELECT
                u.id,
                u.id AS userid,
                qg.grade AS rawgrade,
                qg.timemodified AS dategraded,
                MAX(qa.timefinish) AS datesubmitted

            FROM {user} u
            JOIN {flipquiz_grades} qg ON u.id = qg.userid
            JOIN {flipquiz_attempts} qa ON qa.flipquiz = qg.flipquiz AND qa.userid = u.id

            WHERE qg.flipquiz = ?
            $usertest
            GROUP BY u.id, qg.grade, qg.timemodified", $params);
}

/**
 * Round a grade to to the correct number of decimal places, and format it for display.
 *
 * @param object $flipquiz The flipquiz table row, only $flipquiz->decimalpoints is used.
 * @param float $grade The grade to round.
 * @return float
 */
function flipquiz_format_grade($flipquiz, $grade) {
    if (is_null($grade)) {
        return get_string('notyetgraded', 'flipquiz');
    }
    return format_float($grade, $flipquiz->decimalpoints);
}

/**
 * Determine the correct number of decimal places required to format a grade.
 *
 * @param object $flipquiz The flipquiz table row, only $flipquiz->decimalpoints is used.
 * @return integer
 */
function flipquiz_get_grade_format($flipquiz) {
    if (empty($flipquiz->questiondecimalpoints)) {
        $flipquiz->questiondecimalpoints = -1;
    }

    if ($flipquiz->questiondecimalpoints == -1) {
        return $flipquiz->decimalpoints;
    }

    return $flipquiz->questiondecimalpoints;
}

/**
 * Round a grade to the correct number of decimal places, and format it for display.
 *
 * @param object $flipquiz The flipquiz table row, only $flipquiz->decimalpoints is used.
 * @param float $grade The grade to round.
 * @return float
 */
function flipquiz_format_question_grade($flipquiz, $grade) {
    return format_float($grade, flipquiz_get_grade_format($flipquiz));
}

/**
 * Update grades in central gradebook
 *
 * @category grade
 * @param object $flipquiz the flipquiz settings.
 * @param int $userid specific user only, 0 means all users.
 * @param bool $nullifnone If a single user is specified and $nullifnone is true a grade item with a null rawgrade will be inserted
 */
function flipquiz_update_grades($flipquiz, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    if ($flipquiz->grade == 0) {
        flipquiz_grade_item_update($flipquiz);

    } else if ($grades = flipquiz_get_user_grades($flipquiz, $userid)) {
        flipquiz_grade_item_update($flipquiz, $grades);

    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        flipquiz_grade_item_update($flipquiz, $grade);

    } else {
        flipquiz_grade_item_update($flipquiz);
    }
}

/**
 * Create or update the grade item for given flipquiz
 *
 * @category grade
 * @param object $flipquiz object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function flipquiz_grade_item_update($flipquiz, $grades = null) {
    global $CFG, $OUTPUT;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');
    require_once($CFG->libdir . '/gradelib.php');

    if (array_key_exists('cmidnumber', $flipquiz)) { // May not be always present.
        $params = array('itemname' => $flipquiz->name, 'idnumber' => $flipquiz->cmidnumber);
    } else {
        $params = array('itemname' => $flipquiz->name);
    }

    if ($flipquiz->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $flipquiz->grade;
        $params['grademin']  = 0;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    // What this is trying to do:
    // 1. If the flipquiz is set to not show grades while the flipquiz is still open,
    //    and is set to show grades after the flipquiz is closed, then create the
    //    grade_item with a show-after date that is the flipquiz close date.
    // 2. If the flipquiz is set to not show grades at either of those times,
    //    create the grade_item as hidden.
    // 3. If the flipquiz is set to show grades, create the grade_item visible.
    $openreviewoptions = mod_flipquiz_display_options::make_from_flipquiz($flipquiz,
            mod_flipquiz_display_options::LATER_WHILE_OPEN);
    $closedreviewoptions = mod_flipquiz_display_options::make_from_flipquiz($flipquiz,
            mod_flipquiz_display_options::AFTER_CLOSE);
    if ($openreviewoptions->marks < question_display_options::MARK_AND_MAX &&
            $closedreviewoptions->marks < question_display_options::MARK_AND_MAX) {
        $params['hidden'] = 1;

    } else if ($openreviewoptions->marks < question_display_options::MARK_AND_MAX &&
            $closedreviewoptions->marks >= question_display_options::MARK_AND_MAX) {
        if ($flipquiz->timeclose) {
            $params['hidden'] = $flipquiz->timeclose;
        } else {
            $params['hidden'] = 1;
        }

    } else {
        // Either
        // a) both open and closed enabled
        // b) open enabled, closed disabled - we can not "hide after",
        //    grades are kept visible even after closing.
        $params['hidden'] = 0;
    }

    if (!$params['hidden']) {
        // If the grade item is not hidden by the flipquiz logic, then we need to
        // hide it if the flipquiz is hidden from students.
        if (property_exists($flipquiz, 'visible')) {
            // Saving the flipquiz form, and cm not yet updated in the database.
            $params['hidden'] = !$flipquiz->visible;
        } else {
            $cm = get_coursemodule_from_instance('flipquiz', $flipquiz->id);
            $params['hidden'] = !$cm->visible;
        }
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    $gradebook_grades = grade_get_grades($flipquiz->course, 'mod', 'flipquiz', $flipquiz->id);
    if (!empty($gradebook_grades->items)) {
        $grade_item = $gradebook_grades->items[0];
        if ($grade_item->locked) {
            // NOTE: this is an extremely nasty hack! It is not a bug if this confirmation fails badly. --skodak.
            $confirm_regrade = optional_param('confirm_regrade', 0, PARAM_INT);
            if (!$confirm_regrade) {
                if (!AJAX_SCRIPT) {
                    $message = get_string('gradeitemislocked', 'grades');
                    $back_link = $CFG->wwwroot . '/mod/flipquiz/report.php?q=' . $flipquiz->id .
                            '&amp;mode=overview';
                    $regrade_link = qualified_me() . '&amp;confirm_regrade=1';
                    echo $OUTPUT->box_start('generalbox', 'notice');
                    echo '<p>'. $message .'</p>';
                    echo $OUTPUT->container_start('buttons');
                    echo $OUTPUT->single_button($regrade_link, get_string('regradeanyway', 'grades'));
                    echo $OUTPUT->single_button($back_link,  get_string('cancel'));
                    echo $OUTPUT->container_end();
                    echo $OUTPUT->box_end();
                }
                return GRADE_UPDATE_ITEM_LOCKED;
            }
        }
    }

    return grade_update('mod/flipquiz', $flipquiz->course, 'mod', 'flipquiz', $flipquiz->id, 0, $grades, $params);
}

/**
 * Delete grade item for given flipquiz
 *
 * @category grade
 * @param object $flipquiz object
 * @return object flipquiz
 */
function flipquiz_grade_item_delete($flipquiz) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/flipquiz', $flipquiz->course, 'mod', 'flipquiz', $flipquiz->id, 0,
            null, array('deleted' => 1));
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every flipquiz event in the site is checked, else
 * only flipquiz events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param int $courseid
 * @param int|stdClass $instance Quiz module instance or ID.
 * @param int|stdClass $cm Course module object or ID (not used in this module).
 * @return bool
 */
function flipquiz_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB;

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('flipquiz', array('id' => $instance), '*', MUST_EXIST);
        }
        flipquiz_update_events($instance);
        return true;
    }

    if ($courseid == 0) {
        if (!$flipquizzes = $DB->get_records('flipquiz')) {
            return true;
        }
    } else {
        if (!$flipquizzes = $DB->get_records('flipquiz', array('course' => $courseid))) {
            return true;
        }
    }

    foreach ($flipquizzes as $flipquiz) {
        flipquiz_update_events($flipquiz);
    }

    return true;
}

/**
 * Returns all flipquiz graded users since a given time for specified flipquiz
 */
function flipquiz_get_recent_mod_activity(&$activities, &$index, $timestart,
        $courseid, $cmid, $userid = 0, $groupid = 0) {
    global $CFG, $USER, $DB;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

    $course = get_course($courseid);
    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];
    $flipquiz = $DB->get_record('flipquiz', array('id' => $cm->instance));

    if ($userid) {
        $userselect = "AND u.id = :userid";
        $params['userid'] = $userid;
    } else {
        $userselect = '';
    }

    if ($groupid) {
        $groupselect = 'AND gm.groupid = :groupid';
        $groupjoin   = 'JOIN {groups_members} gm ON  gm.userid=u.id';
        $params['groupid'] = $groupid;
    } else {
        $groupselect = '';
        $groupjoin   = '';
    }

    $params['timestart'] = $timestart;
    $params['flipquizid'] = $flipquiz->id;

    $ufields = user_picture::fields('u', null, 'useridagain');
    if (!$attempts = $DB->get_records_sql("
              SELECT qa.*,
                     {$ufields}
                FROM {flipquiz_attempts} qa
                     JOIN {user} u ON u.id = qa.userid
                     $groupjoin
               WHERE qa.timefinish > :timestart
                 AND qa.flipquiz = :flipquizid
                 AND qa.preview = 0
                     $userselect
                     $groupselect
            ORDER BY qa.timefinish ASC", $params)) {
        return;
    }

    $context         = context_module::instance($cm->id);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $context);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $context);
    $grader          = has_capability('mod/flipquiz:viewreports', $context);
    $groupmode       = groups_get_activity_groupmode($cm, $course);

    $usersgroups = null;
    $aname = format_string($cm->name, true);
    foreach ($attempts as $attempt) {
        if ($attempt->userid != $USER->id) {
            if (!$grader) {
                // Grade permission required.
                continue;
            }

            if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
                $usersgroups = groups_get_all_groups($course->id,
                        $attempt->userid, $cm->groupingid);
                $usersgroups = array_keys($usersgroups);
                if (!array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid))) {
                    continue;
                }
            }
        }

        $options = flipquiz_get_review_options($flipquiz, $attempt, $context);

        $tmpactivity = new stdClass();

        $tmpactivity->type       = 'flipquiz';
        $tmpactivity->cmid       = $cm->id;
        $tmpactivity->name       = $aname;
        $tmpactivity->sectionnum = $cm->sectionnum;
        $tmpactivity->timestamp  = $attempt->timefinish;

        $tmpactivity->content = new stdClass();
        $tmpactivity->content->attemptid = $attempt->id;
        $tmpactivity->content->attempt   = $attempt->attempt;
        if (flipquiz_has_grades($flipquiz) && $options->marks >= question_display_options::MARK_AND_MAX) {
            $tmpactivity->content->sumgrades = flipquiz_format_grade($flipquiz, $attempt->sumgrades);
            $tmpactivity->content->maxgrade  = flipquiz_format_grade($flipquiz, $flipquiz->sumgrades);
        } else {
            $tmpactivity->content->sumgrades = null;
            $tmpactivity->content->maxgrade  = null;
        }

        $tmpactivity->user = user_picture::unalias($attempt, null, 'useridagain');
        $tmpactivity->user->fullname  = fullname($tmpactivity->user, $viewfullnames);

        $activities[$index++] = $tmpactivity;
    }
}

function flipquiz_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="forum-recent">';

    echo '<tr><td class="userpicture" valign="top">';
    echo $OUTPUT->user_picture($activity->user, array('courseid' => $courseid));
    echo '</td><td>';

    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo $OUTPUT->image_icon('icon', $modname, $activity->type);
        echo '<a href="' . $CFG->wwwroot . '/mod/flipquiz/view.php?id=' .
                $activity->cmid . '">' . $activity->name . '</a>';
        echo '</div>';
    }

    echo '<div class="grade">';
    echo  get_string('attempt', 'flipquiz', $activity->content->attempt);
    if (isset($activity->content->maxgrade)) {
        $grades = $activity->content->sumgrades . ' / ' . $activity->content->maxgrade;
        echo ': (<a href="' . $CFG->wwwroot . '/mod/flipquiz/review.php?attempt=' .
                $activity->content->attemptid . '">' . $grades . '</a>)';
    }
    echo '</div>';

    echo '<div class="user">';
    echo '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $activity->user->id .
            '&amp;course=' . $courseid . '">' . $activity->user->fullname .
            '</a> - ' . userdate($activity->timestamp);
    echo '</div>';

    echo '</td></tr></table>';

    return;
}

/**
 * Pre-process the flipquiz options form data, making any necessary adjustments.
 * Called by add/update instance in this file.
 *
 * @param object $flipquiz The variables set on the form.
 */
function flipquiz_process_options($flipquiz) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');
    require_once($CFG->libdir . '/questionlib.php');

    $flipquiz->timemodified = time();

    // Quiz name.
    if (!empty($flipquiz->name)) {
        $flipquiz->name = trim($flipquiz->name);
    }

    // Password field - different in form to stop browsers that remember passwords
    // getting confused.
    $flipquiz->password = $flipquiz->flipquizpassword;
    unset($flipquiz->flipquizpassword);

    // Quiz feedback.
    if (isset($flipquiz->feedbacktext)) {
        // Clean up the boundary text.
        for ($i = 0; $i < count($flipquiz->feedbacktext); $i += 1) {
            if (empty($flipquiz->feedbacktext[$i]['text'])) {
                $flipquiz->feedbacktext[$i]['text'] = '';
            } else {
                $flipquiz->feedbacktext[$i]['text'] = trim($flipquiz->feedbacktext[$i]['text']);
            }
        }

        // Check the boundary value is a number or a percentage, and in range.
        $i = 0;
        while (!empty($flipquiz->feedbackboundaries[$i])) {
            $boundary = trim($flipquiz->feedbackboundaries[$i]);
            if (!is_numeric($boundary)) {
                if (strlen($boundary) > 0 && $boundary[strlen($boundary) - 1] == '%') {
                    $boundary = trim(substr($boundary, 0, -1));
                    if (is_numeric($boundary)) {
                        $boundary = $boundary * $flipquiz->grade / 100.0;
                    } else {
                        return get_string('feedbackerrorboundaryformat', 'flipquiz', $i + 1);
                    }
                }
            }
            if ($boundary <= 0 || $boundary >= $flipquiz->grade) {
                return get_string('feedbackerrorboundaryoutofrange', 'flipquiz', $i + 1);
            }
            if ($i > 0 && $boundary >= $flipquiz->feedbackboundaries[$i - 1]) {
                return get_string('feedbackerrororder', 'flipquiz', $i + 1);
            }
            $flipquiz->feedbackboundaries[$i] = $boundary;
            $i += 1;
        }
        $numboundaries = $i;

        // Check there is nothing in the remaining unused fields.
        if (!empty($flipquiz->feedbackboundaries)) {
            for ($i = $numboundaries; $i < count($flipquiz->feedbackboundaries); $i += 1) {
                if (!empty($flipquiz->feedbackboundaries[$i]) &&
                        trim($flipquiz->feedbackboundaries[$i]) != '') {
                    return get_string('feedbackerrorjunkinboundary', 'flipquiz', $i + 1);
                }
            }
        }
        for ($i = $numboundaries + 1; $i < count($flipquiz->feedbacktext); $i += 1) {
            if (!empty($flipquiz->feedbacktext[$i]['text']) &&
                    trim($flipquiz->feedbacktext[$i]['text']) != '') {
                return get_string('feedbackerrorjunkinfeedback', 'flipquiz', $i + 1);
            }
        }
        // Needs to be bigger than $flipquiz->grade because of '<' test in flipquiz_feedback_for_grade().
        $flipquiz->feedbackboundaries[-1] = $flipquiz->grade + 1;
        $flipquiz->feedbackboundaries[$numboundaries] = 0;
        $flipquiz->feedbackboundarycount = $numboundaries;
    } else {
        $flipquiz->feedbackboundarycount = -1;
    }

    // Combing the individual settings into the review columns.
    $flipquiz->reviewattempt = flipquiz_review_option_form_to_db($flipquiz, 'attempt');
    $flipquiz->reviewcorrectness = flipquiz_review_option_form_to_db($flipquiz, 'correctness');
    $flipquiz->reviewmarks = flipquiz_review_option_form_to_db($flipquiz, 'marks');
    $flipquiz->reviewspecificfeedback = flipquiz_review_option_form_to_db($flipquiz, 'specificfeedback');
    $flipquiz->reviewgeneralfeedback = flipquiz_review_option_form_to_db($flipquiz, 'generalfeedback');
    $flipquiz->reviewrightanswer = flipquiz_review_option_form_to_db($flipquiz, 'rightanswer');
    $flipquiz->reviewoverallfeedback = flipquiz_review_option_form_to_db($flipquiz, 'overallfeedback');
    $flipquiz->reviewattempt |= mod_flipquiz_display_options::DURING;
    $flipquiz->reviewoverallfeedback &= ~mod_flipquiz_display_options::DURING;
}

/**
 * Helper function for {@link flipquiz_process_options()}.
 * @param object $fromform the sumbitted form date.
 * @param string $field one of the review option field names.
 */
function flipquiz_review_option_form_to_db($fromform, $field) {
    static $times = array(
        'during' => mod_flipquiz_display_options::DURING,
        'immediately' => mod_flipquiz_display_options::IMMEDIATELY_AFTER,
        'open' => mod_flipquiz_display_options::LATER_WHILE_OPEN,
        'closed' => mod_flipquiz_display_options::AFTER_CLOSE,
    );

    $review = 0;
    foreach ($times as $whenname => $when) {
        $fieldname = $field . $whenname;
        if (isset($fromform->$fieldname)) {
            $review |= $when;
            unset($fromform->$fieldname);
        }
    }

    return $review;
}

/**
 * This function is called at the end of flipquiz_add_instance
 * and flipquiz_update_instance, to do the common processing.
 *
 * @param object $flipquiz the flipquiz object.
 */
function flipquiz_after_add_or_update($flipquiz) {
    global $DB;
    $cmid = $flipquiz->coursemodule;

    // We need to use context now, so we need to make sure all needed info is already in db.
    $DB->set_field('course_modules', 'instance', $flipquiz->id, array('id'=>$cmid));
    $context = context_module::instance($cmid);

    // Save the feedback.
    $DB->delete_records('flipquiz_feedback', array('flipquizid' => $flipquiz->id));

    for ($i = 0; $i <= $flipquiz->feedbackboundarycount; $i++) {
        $feedback = new stdClass();
        $feedback->flipquizid = $flipquiz->id;
        $feedback->feedbacktext = $flipquiz->feedbacktext[$i]['text'];
        $feedback->feedbacktextformat = $flipquiz->feedbacktext[$i]['format'];
        $feedback->mingrade = $flipquiz->feedbackboundaries[$i];
        $feedback->maxgrade = $flipquiz->feedbackboundaries[$i - 1];
        $feedback->id = $DB->insert_record('flipquiz_feedback', $feedback);
        $feedbacktext = file_save_draft_area_files((int)$flipquiz->feedbacktext[$i]['itemid'],
                $context->id, 'mod_flipquiz', 'feedback', $feedback->id,
                array('subdirs' => false, 'maxfiles' => -1, 'maxbytes' => 0),
                $flipquiz->feedbacktext[$i]['text']);
        $DB->set_field('flipquiz_feedback', 'feedbacktext', $feedbacktext,
                array('id' => $feedback->id));
    }

    // Store any settings belonging to the access rules.
    flipquiz_access_manager::save_settings($flipquiz);

    // Update the events relating to this flipquiz.
    flipquiz_update_events($flipquiz);
    $completionexpected = (!empty($flipquiz->completionexpected)) ? $flipquiz->completionexpected : null;
    \core_completion\api::update_completion_date_event($flipquiz->coursemodule, 'flipquiz', $flipquiz->id, $completionexpected);

    // Update related grade item.
    flipquiz_grade_item_update($flipquiz);
}

/**
 * This function updates the events associated to the flipquiz.
 * If $override is non-zero, then it updates only the events
 * associated with the specified override.
 *
 * @uses FLIPQUIZ_MAX_EVENT_LENGTH
 * @param object $flipquiz the flipquiz object.
 * @param object optional $override limit to a specific override
 */
function flipquiz_update_events($flipquiz, $override = null) {
    global $DB;

    // Load the old events relating to this flipquiz.
    $conds = array('modulename'=>'flipquiz',
                   'instance'=>$flipquiz->id);
    if (!empty($override)) {
        // Only load events for this override.
        if (isset($override->userid)) {
            $conds['userid'] = $override->userid;
        } else {
            $conds['groupid'] = $override->groupid;
        }
    }
    $oldevents = $DB->get_records('event', $conds, 'id ASC');

    // Now make a to-do list of all that needs to be updated.
    if (empty($override)) {
        // We are updating the primary settings for the flipquiz, so we need to add all the overrides.
        $overrides = $DB->get_records('flipquiz_overrides', array('flipquiz' => $flipquiz->id), 'id ASC');
        // It is necessary to add an empty stdClass to the beginning of the array as the $oldevents
        // list contains the original (non-override) event for the module. If this is not included
        // the logic below will end up updating the wrong row when we try to reconcile this $overrides
        // list against the $oldevents list.
        array_unshift($overrides, new stdClass());
    } else {
        // Just do the one override.
        $overrides = array($override);
    }

    // Get group override priorities.
    $grouppriorities = flipquiz_get_group_override_priorities($flipquiz->id);

    foreach ($overrides as $current) {
        $groupid   = isset($current->groupid)?  $current->groupid : 0;
        $userid    = isset($current->userid)? $current->userid : 0;
        $timeopen  = isset($current->timeopen)?  $current->timeopen : $flipquiz->timeopen;
        $timeclose = isset($current->timeclose)? $current->timeclose : $flipquiz->timeclose;

        // Only add open/close events for an override if they differ from the flipquiz default.
        $addopen  = empty($current->id) || !empty($current->timeopen);
        $addclose = empty($current->id) || !empty($current->timeclose);

        if (!empty($flipquiz->coursemodule)) {
            $cmid = $flipquiz->coursemodule;
        } else {
            $cmid = get_coursemodule_from_instance('flipquiz', $flipquiz->id, $flipquiz->course)->id;
        }

        $event = new stdClass();
        $event->type = !$timeclose ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
        $event->description = format_module_intro('flipquiz', $flipquiz, $cmid);
        // Events module won't show user events when the courseid is nonzero.
        $event->courseid    = ($userid) ? 0 : $flipquiz->course;
        $event->groupid     = $groupid;
        $event->userid      = $userid;
        $event->modulename  = 'flipquiz';
        $event->instance    = $flipquiz->id;
        $event->timestart   = $timeopen;
        $event->timeduration = max($timeclose - $timeopen, 0);
        $event->timesort    = $timeopen;
        $event->visible     = instance_is_visible('flipquiz', $flipquiz);
        $event->eventtype   = FLIPQUIZ_EVENT_TYPE_OPEN;
        $event->priority    = null;

        // Determine the event name and priority.
        if ($groupid) {
            // Group override event.
            $params = new stdClass();
            $params->flipquiz = $flipquiz->name;
            $params->group = groups_get_group_name($groupid);
            if ($params->group === false) {
                // Group doesn't exist, just skip it.
                continue;
            }
            $eventname = get_string('overridegroupeventname', 'flipquiz', $params);
            // Set group override priority.
            if ($grouppriorities !== null) {
                $openpriorities = $grouppriorities['open'];
                if (isset($openpriorities[$timeopen])) {
                    $event->priority = $openpriorities[$timeopen];
                }
            }
        } else if ($userid) {
            // User override event.
            $params = new stdClass();
            $params->flipquiz = $flipquiz->name;
            $eventname = get_string('overrideusereventname', 'flipquiz', $params);
            // Set user override priority.
            $event->priority = CALENDAR_EVENT_USER_OVERRIDE_PRIORITY;
        } else {
            // The parent event.
            $eventname = $flipquiz->name;
        }

        if ($addopen or $addclose) {
            // Separate start and end events.
            $event->timeduration  = 0;
            if ($timeopen && $addopen) {
                if ($oldevent = array_shift($oldevents)) {
                    $event->id = $oldevent->id;
                } else {
                    unset($event->id);
                }
                $event->name = get_string('flipquizeventopens', 'flipquiz', $eventname);
                // The method calendar_event::create will reuse a db record if the id field is set.
                calendar_event::create($event, false);
            }
            if ($timeclose && $addclose) {
                if ($oldevent = array_shift($oldevents)) {
                    $event->id = $oldevent->id;
                } else {
                    unset($event->id);
                }
                $event->type      = CALENDAR_EVENT_TYPE_ACTION;
                $event->name      = get_string('flipquizeventcloses', 'flipquiz', $eventname);
                $event->timestart = $timeclose;
                $event->timesort  = $timeclose;
                $event->eventtype = FLIPQUIZ_EVENT_TYPE_CLOSE;
                if ($groupid && $grouppriorities !== null) {
                    $closepriorities = $grouppriorities['close'];
                    if (isset($closepriorities[$timeclose])) {
                        $event->priority = $closepriorities[$timeclose];
                    }
                }
                calendar_event::create($event, false);
            }
        }
    }

    // Delete any leftover events.
    foreach ($oldevents as $badevent) {
        $badevent = calendar_event::load($badevent);
        $badevent->delete();
    }
}

/**
 * Calculates the priorities of timeopen and timeclose values for group overrides for a flipquiz.
 *
 * @param int $flipquizid The flipquiz ID.
 * @return array|null Array of group override priorities for open and close times. Null if there are no group overrides.
 */
function flipquiz_get_group_override_priorities($flipquizid) {
    global $DB;

    // Fetch group overrides.
    $where = 'flipquiz = :flipquiz AND groupid IS NOT NULL';
    $params = ['flipquiz' => $flipquizid];
    $overrides = $DB->get_records_select('flipquiz_overrides', $where, $params, '', 'id, timeopen, timeclose');
    if (!$overrides) {
        return null;
    }

    $grouptimeopen = [];
    $grouptimeclose = [];
    foreach ($overrides as $override) {
        if ($override->timeopen !== null && !in_array($override->timeopen, $grouptimeopen)) {
            $grouptimeopen[] = $override->timeopen;
        }
        if ($override->timeclose !== null && !in_array($override->timeclose, $grouptimeclose)) {
            $grouptimeclose[] = $override->timeclose;
        }
    }

    // Sort open times in ascending manner. The earlier open time gets higher priority.
    sort($grouptimeopen);
    // Set priorities.
    $opengrouppriorities = [];
    $openpriority = 1;
    foreach ($grouptimeopen as $timeopen) {
        $opengrouppriorities[$timeopen] = $openpriority++;
    }

    // Sort close times in descending manner. The later close time gets higher priority.
    rsort($grouptimeclose);
    // Set priorities.
    $closegrouppriorities = [];
    $closepriority = 1;
    foreach ($grouptimeclose as $timeclose) {
        $closegrouppriorities[$timeclose] = $closepriority++;
    }

    return [
        'open' => $opengrouppriorities,
        'close' => $closegrouppriorities
    ];
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function flipquiz_get_view_actions() {
    return array('view', 'view all', 'report', 'review');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function flipquiz_get_post_actions() {
    return array('attempt', 'close attempt', 'preview', 'editquestions',
            'delete attempt', 'manualgrade');
}

/**
 * @param array $questionids of question ids.
 * @return bool whether any of these questions are used by any instance of this module.
 */
function flipquiz_questions_in_use($questionids) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    list($test, $params) = $DB->get_in_or_equal($questionids);
    return $DB->record_exists_select('flipquiz_slots',
            'questionid ' . $test, $params) || question_engine::questions_in_use(
            $questionids, new qubaid_join('{flipquiz_attempts} flipquiza',
            'flipquiza.uniqueid', 'flipquiza.preview = 0'));
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the flipquiz.
 *
 * @param $mform the course reset form that is being built.
 */
function flipquiz_reset_course_form_definition($mform) {
    $mform->addElement('header', 'flipquizheader', get_string('modulenameplural', 'flipquiz'));
    $mform->addElement('advcheckbox', 'reset_flipquiz_attempts',
            get_string('removeallflipquizattempts', 'flipquiz'));
    $mform->addElement('advcheckbox', 'reset_flipquiz_user_overrides',
            get_string('removealluseroverrides', 'flipquiz'));
    $mform->addElement('advcheckbox', 'reset_flipquiz_group_overrides',
            get_string('removeallgroupoverrides', 'flipquiz'));
}

/**
 * Course reset form defaults.
 * @return array the defaults.
 */
function flipquiz_reset_course_form_defaults($course) {
    return array('reset_flipquiz_attempts' => 1,
                 'reset_flipquiz_group_overrides' => 1,
                 'reset_flipquiz_user_overrides' => 1);
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid
 * @param string optional type
 */
function flipquiz_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $flipquizzes = $DB->get_records_sql("
            SELECT q.*, cm.idnumber as cmidnumber, q.course as courseid
            FROM {modules} m
            JOIN {course_modules} cm ON m.id = cm.module
            JOIN {flipquiz} q ON cm.instance = q.id
            WHERE m.name = 'flipquiz' AND cm.course = ?", array($courseid));

    foreach ($flipquizzes as $flipquiz) {
        flipquiz_grade_item_update($flipquiz, 'reset');
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * flipquiz attempts for course $data->courseid, if $data->reset_flipquiz_attempts is
 * set and true.
 *
 * Also, move the flipquiz open and close dates, if the course start date is changing.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function flipquiz_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/questionlib.php');

    $componentstr = get_string('modulenameplural', 'flipquiz');
    $status = array();

    // Delete attempts.
    if (!empty($data->reset_flipquiz_attempts)) {
        question_engine::delete_questions_usage_by_activities(new qubaid_join(
                '{flipquiz_attempts} flipquiza JOIN {flipquiz} flipquiz ON flipquiza.flipquiz = flipquiz.id',
                'flipquiza.uniqueid', 'flipquiz.course = :flipquizcourseid',
                array('flipquizcourseid' => $data->courseid)));

        $DB->delete_records_select('flipquiz_attempts',
                'flipquiz IN (SELECT id FROM {flipquiz} WHERE course = ?)', array($data->courseid));
        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('attemptsdeleted', 'flipquiz'),
            'error' => false);

        // Remove all grades from gradebook.
        $DB->delete_records_select('flipquiz_grades',
                'flipquiz IN (SELECT id FROM {flipquiz} WHERE course = ?)', array($data->courseid));
        if (empty($data->reset_gradebook_grades)) {
            flipquiz_reset_gradebook($data->courseid);
        }
        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('gradesdeleted', 'flipquiz'),
            'error' => false);
    }

    // Remove user overrides.
    if (!empty($data->reset_flipquiz_user_overrides)) {
        $DB->delete_records_select('flipquiz_overrides',
                'flipquiz IN (SELECT id FROM {flipquiz} WHERE course = ?) AND userid IS NOT NULL', array($data->courseid));
        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('useroverridesdeleted', 'flipquiz'),
            'error' => false);
    }
    // Remove group overrides.
    if (!empty($data->reset_flipquiz_group_overrides)) {
        $DB->delete_records_select('flipquiz_overrides',
                'flipquiz IN (SELECT id FROM {flipquiz} WHERE course = ?) AND groupid IS NOT NULL', array($data->courseid));
        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('groupoverridesdeleted', 'flipquiz'),
            'error' => false);
    }

    // Updating dates - shift may be negative too.
    if ($data->timeshift) {
        $DB->execute("UPDATE {flipquiz_overrides}
                         SET timeopen = timeopen + ?
                       WHERE flipquiz IN (SELECT id FROM {flipquiz} WHERE course = ?)
                         AND timeopen <> 0", array($data->timeshift, $data->courseid));
        $DB->execute("UPDATE {flipquiz_overrides}
                         SET timeclose = timeclose + ?
                       WHERE flipquiz IN (SELECT id FROM {flipquiz} WHERE course = ?)
                         AND timeclose <> 0", array($data->timeshift, $data->courseid));

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        shift_course_mod_dates('flipquiz', array('timeopen', 'timeclose'),
                $data->timeshift, $data->courseid);

        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('openclosedatesupdated', 'flipquiz'),
            'error' => false);
    }

    return $status;
}

/**
 * Prints flipquiz summaries on MyMoodle Page
 *
 * @deprecated since 3.3
 * @todo The final deprecation of this function will take place in Moodle 3.7 - see MDL-57487.
 * @param array $courses
 * @param array $htmlarray
 */
function flipquiz_print_overview($courses, &$htmlarray) {
    global $USER, $CFG;

    debugging('The function flipquiz_print_overview() is now deprecated.', DEBUG_DEVELOPER);

    // These next 6 Lines are constant in all modules (just change module name).
    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$flipquizzes = get_all_instances_in_courses('flipquiz', $courses)) {
        return;
    }

    // Get the flipquizzes attempts.
    $attemptsinfo = [];
    $flipquizids = [];
    foreach ($flipquizzes as $flipquiz) {
        $flipquizids[] = $flipquiz->id;
        $attemptsinfo[$flipquiz->id] = ['count' => 0, 'hasfinished' => false];
    }
    $attempts = flipquiz_get_user_attempts($flipquizids, $USER->id);
    foreach ($attempts as $attempt) {
        $attemptsinfo[$attempt->flipquiz]['count']++;
        $attemptsinfo[$attempt->flipquiz]['hasfinished'] = true;
    }
    unset($attempts);

    // Fetch some language strings outside the main loop.
    $strflipquiz = get_string('modulename', 'flipquiz');
    $strnoattempts = get_string('noattempts', 'flipquiz');

    // We want to list flipquizzes that are currently available, and which have a close date.
    // This is the same as what the lesson does, and the dabate is in MDL-10568.
    $now = time();
    foreach ($flipquizzes as $flipquiz) {
        if ($flipquiz->timeclose >= $now && $flipquiz->timeopen < $now) {
            $str = '';

            // Now provide more information depending on the uers's role.
            $context = context_module::instance($flipquiz->coursemodule);
            if (has_capability('mod/flipquiz:viewreports', $context)) {
                // For teacher-like people, show a summary of the number of student attempts.
                // The $flipquiz objects returned by get_all_instances_in_course have the necessary $cm
                // fields set to make the following call work.
                $str .= '<div class="info">' . flipquiz_num_attempt_summary($flipquiz, $flipquiz, true) . '</div>';

            } else if (has_any_capability(array('mod/flipquiz:reviewmyattempts', 'mod/flipquiz:attempt'), $context)) { // Student
                // For student-like people, tell them how many attempts they have made.

                if (isset($USER->id)) {
                    if ($attemptsinfo[$flipquiz->id]['hasfinished']) {
                        // The student's last attempt is finished.
                        continue;
                    }

                    if ($attemptsinfo[$flipquiz->id]['count'] > 0) {
                        $str .= '<div class="info">' .
                            get_string('numattemptsmade', 'flipquiz', $attemptsinfo[$flipquiz->id]['count']) . '</div>';
                    } else {
                        $str .= '<div class="info">' . $strnoattempts . '</div>';
                    }

                } else {
                    $str .= '<div class="info">' . $strnoattempts . '</div>';
                }

            } else {
                // For ayone else, there is no point listing this flipquiz, so stop processing.
                continue;
            }

            // Give a link to the flipquiz, and the deadline.
            $html = '<div class="flipquiz overview">' .
                    '<div class="name">' . $strflipquiz . ': <a ' .
                    ($flipquiz->visible ? '' : ' class="dimmed"') .
                    ' href="' . $CFG->wwwroot . '/mod/flipquiz/view.php?id=' .
                    $flipquiz->coursemodule . '">' .
                    $flipquiz->name . '</a></div>';
            $html .= '<div class="info">' . get_string('flipquizcloseson', 'flipquiz',
                    userdate($flipquiz->timeclose)) . '</div>';
            $html .= $str;
            $html .= '</div>';
            if (empty($htmlarray[$flipquiz->course]['flipquiz'])) {
                $htmlarray[$flipquiz->course]['flipquiz'] = $html;
            } else {
                $htmlarray[$flipquiz->course]['flipquiz'] .= $html;
            }
        }
    }
}

/**
 * Return a textual summary of the number of attempts that have been made at a particular flipquiz,
 * returns '' if no attempts have been made yet, unless $returnzero is passed as true.
 *
 * @param object $flipquiz the flipquiz object. Only $flipquiz->id is used at the moment.
 * @param object $cm the cm object. Only $cm->course, $cm->groupmode and
 *      $cm->groupingid fields are used at the moment.
 * @param bool $returnzero if false (default), when no attempts have been
 *      made '' is returned instead of 'Attempts: 0'.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *         (e.g. a report) pass it in here. Default 0 which means no current group.
 * @return string a string like "Attempts: 123", "Attemtps 123 (45 from your groups)" or
 *          "Attemtps 123 (45 from this group)".
 */
function flipquiz_num_attempt_summary($flipquiz, $cm, $returnzero = false, $currentgroup = 0) {
    global $DB, $USER;
    $numattempts = $DB->count_records('flipquiz_attempts', array('flipquiz'=> $flipquiz->id, 'preview'=>0));
    if ($numattempts || $returnzero) {
        if (groups_get_activity_groupmode($cm)) {
            $a = new stdClass();
            $a->total = $numattempts;
            if ($currentgroup) {
                $a->group = $DB->count_records_sql('SELECT COUNT(DISTINCT qa.id) FROM ' .
                        '{flipquiz_attempts} qa JOIN ' .
                        '{groups_members} gm ON qa.userid = gm.userid ' .
                        'WHERE flipquiz = ? AND preview = 0 AND groupid = ?',
                        array($flipquiz->id, $currentgroup));
                return get_string('attemptsnumthisgroup', 'flipquiz', $a);
            } else if ($groups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid)) {
                list($usql, $params) = $DB->get_in_or_equal(array_keys($groups));
                $a->group = $DB->count_records_sql('SELECT COUNT(DISTINCT qa.id) FROM ' .
                        '{flipquiz_attempts} qa JOIN ' .
                        '{groups_members} gm ON qa.userid = gm.userid ' .
                        'WHERE flipquiz = ? AND preview = 0 AND ' .
                        "groupid $usql", array_merge(array($flipquiz->id), $params));
                return get_string('attemptsnumyourgroups', 'flipquiz', $a);
            }
        }
        return get_string('attemptsnum', 'flipquiz', $numattempts);
    }
    return '';
}

/**
 * Returns the same as {@link flipquiz_num_attempt_summary()} but wrapped in a link
 * to the flipquiz reports.
 *
 * @param object $flipquiz the flipquiz object. Only $flipquiz->id is used at the moment.
 * @param object $cm the cm object. Only $cm->course, $cm->groupmode and
 *      $cm->groupingid fields are used at the moment.
 * @param object $context the flipquiz context.
 * @param bool $returnzero if false (default), when no attempts have been made
 *      '' is returned instead of 'Attempts: 0'.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *         (e.g. a report) pass it in here. Default 0 which means no current group.
 * @return string HTML fragment for the link.
 */
function flipquiz_attempt_summary_link_to_reports($flipquiz, $cm, $context, $returnzero = false,
        $currentgroup = 0) {
    global $CFG;
    $summary = flipquiz_num_attempt_summary($flipquiz, $cm, $returnzero, $currentgroup);
    if (!$summary) {
        return '';
    }

    require_once($CFG->dirroot . '/mod/flipquiz/report/reportlib.php');
    $url = new moodle_url('/mod/flipquiz/report.php', array(
            'id' => $cm->id, 'mode' => flipquiz_report_default_report($context)));
    return html_writer::link($url, $summary);
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool True if flipquiz supports feature
 */
function flipquiz_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                    return true;
        case FEATURE_GROUPINGS:                 return true;
        case FEATURE_MOD_INTRO:                 return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:   return true;
        case FEATURE_COMPLETION_HAS_RULES:      return true;
        case FEATURE_GRADE_HAS_GRADE:           return true;
        case FEATURE_GRADE_OUTCOMES:            return true;
        case FEATURE_BACKUP_MOODLE2:            return true;
        case FEATURE_SHOW_DESCRIPTION:          return true;
        case FEATURE_CONTROLS_GRADE_VISIBILITY: return true;
        case FEATURE_USES_QUESTIONS:            return true;

        default: return null;
    }
}

/**
 * @return array all other caps used in module
 */
function flipquiz_get_extra_capabilities() {
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    $caps = question_get_all_capabilities();
    $caps[] = 'moodle/site:accessallgroups';
    return $caps;
}

/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param settings_navigation $settings
 * @param navigation_node $flipquiznode
 * @return void
 */
function flipquiz_extend_settings_navigation($settings, $flipquiznode) {
    global $PAGE, $CFG;

    // Require {@link questionlib.php}
    // Included here as we only ever want to include this file if we really need to.
    require_once($CFG->libdir . '/questionlib.php');

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $flipquiznode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    if (has_capability('mod/flipquiz:manageoverrides', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/flipquiz/overrides.php', array('cmid'=>$PAGE->cm->id));
        $node = navigation_node::create(get_string('groupoverrides', 'flipquiz'),
                new moodle_url($url, array('mode'=>'group')),
                navigation_node::TYPE_SETTING, null, 'mod_flipquiz_groupoverrides');
        $flipquiznode->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('useroverrides', 'flipquiz'),
                new moodle_url($url, array('mode'=>'user')),
                navigation_node::TYPE_SETTING, null, 'mod_flipquiz_useroverrides');
        $flipquiznode->add_node($node, $beforekey);
    }

    if (has_capability('mod/flipquiz:manage', $PAGE->cm->context)) {
        $node = navigation_node::create(get_string('editflipquiz', 'flipquiz'),
                new moodle_url('/mod/flipquiz/edit.php', array('cmid'=>$PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_flipquiz_edit',
                new pix_icon('t/edit', ''));
        $flipquiznode->add_node($node, $beforekey);
    }

    if (has_capability('mod/flipquiz:preview', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/flipquiz/startattempt.php',
                array('cmid'=>$PAGE->cm->id, 'sesskey'=>sesskey()));
        $node = navigation_node::create(get_string('preview', 'flipquiz'), $url,
                navigation_node::TYPE_SETTING, null, 'mod_flipquiz_preview',
                new pix_icon('i/preview', ''));
        $flipquiznode->add_node($node, $beforekey);
    }

    if (has_any_capability(array('mod/flipquiz:viewreports', 'mod/flipquiz:grade'), $PAGE->cm->context)) {
        require_once($CFG->dirroot . '/mod/flipquiz/report/reportlib.php');
        $reportlist = flipquiz_report_list($PAGE->cm->context);

        $url = new moodle_url('/mod/flipquiz/report.php',
                array('id' => $PAGE->cm->id, 'mode' => reset($reportlist)));
        $reportnode = $flipquiznode->add_node(navigation_node::create(get_string('results', 'flipquiz'), $url,
                navigation_node::TYPE_SETTING,
                null, null, new pix_icon('i/report', '')), $beforekey);

        foreach ($reportlist as $report) {
            $url = new moodle_url('/mod/flipquiz/report.php',
                    array('id' => $PAGE->cm->id, 'mode' => $report));
            $reportnode->add_node(navigation_node::create(get_string($report, 'flipquiz_'.$report), $url,
                    navigation_node::TYPE_SETTING,
                    null, 'flipquiz_report_' . $report, new pix_icon('i/item', '')));
        }
    }

    question_extend_settings_navigation($flipquiznode, $PAGE->cm->context)->trim_if_empty();
}

/**
 * Serves the flipquiz files.
 *
 * @package  mod_flipquiz
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function flipquiz_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    if (!$flipquiz = $DB->get_record('flipquiz', array('id'=>$cm->instance))) {
        return false;
    }

    // The 'intro' area is served by pluginfile.php.
    $fileareas = array('feedback');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $feedbackid = (int)array_shift($args);
    if (!$feedback = $DB->get_record('flipquiz_feedback', array('id'=>$feedbackid))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_flipquiz/$filearea/$feedbackid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, true, $options);
}

/**
 * Called via pluginfile.php -> question_pluginfile to serve files belonging to
 * a question in a question_attempt when that attempt is a flipquiz attempt.
 *
 * @package  mod_flipquiz
 * @category files
 * @param stdClass $course course settings object
 * @param stdClass $context context object
 * @param string $component the name of the component we are serving files for.
 * @param string $filearea the name of the file area.
 * @param int $qubaid the attempt usage id.
 * @param int $slot the id of a question in this flipquiz attempt.
 * @param array $args the remaining bits of the file path.
 * @param bool $forcedownload whether the user must be forced to download the file.
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function flipquiz_question_pluginfile($course, $context, $component,
        $filearea, $qubaid, $slot, $args, $forcedownload, array $options=array()) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

    $attemptobj = flipquiz_attempt::create_from_usage_id($qubaid);
    require_login($attemptobj->get_course(), false, $attemptobj->get_cm());

    if ($attemptobj->is_own_attempt() && !$attemptobj->is_finished()) {
        // In the middle of an attempt.
        if (!$attemptobj->is_preview_user()) {
            $attemptobj->require_capability('mod/flipquiz:attempt');
        }
        $isreviewing = false;

    } else {
        // Reviewing an attempt.
        $attemptobj->check_review_capability();
        $isreviewing = true;
    }

    if (!$attemptobj->check_file_access($slot, $isreviewing, $context->id,
            $component, $filearea, $args, $forcedownload)) {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/$component/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function flipquiz_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array(
        'mod-flipquiz-*'       => get_string('page-mod-flipquiz-x', 'flipquiz'),
        'mod-flipquiz-view'    => get_string('page-mod-flipquiz-view', 'flipquiz'),
        'mod-flipquiz-attempt' => get_string('page-mod-flipquiz-attempt', 'flipquiz'),
        'mod-flipquiz-summary' => get_string('page-mod-flipquiz-summary', 'flipquiz'),
        'mod-flipquiz-review'  => get_string('page-mod-flipquiz-review', 'flipquiz'),
        'mod-flipquiz-edit'    => get_string('page-mod-flipquiz-edit', 'flipquiz'),
        'mod-flipquiz-report'  => get_string('page-mod-flipquiz-report', 'flipquiz'),
    );
    return $module_pagetype;
}

/**
 * @return the options for flipquiz navigation.
 */
function flipquiz_get_navigation_options() {
    return array(
        FLIPQUIZ_NAVMETHOD_FREE => get_string('navmethod_free', 'flipquiz'),
        FLIPQUIZ_NAVMETHOD_SEQ  => get_string('navmethod_seq', 'flipquiz')
    );
}

/**
 * Obtains the automatic completion state for this flipquiz on any conditions
 * in flipquiz settings, such as if all attempts are used or a certain grade is achieved.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function flipquiz_get_completion_state($course, $cm, $userid, $type) {
    global $DB;
    global $CFG;

    $flipquiz = $DB->get_record('flipquiz', array('id' => $cm->instance), '*', MUST_EXIST);
    if (!$flipquiz->completionattemptsexhausted && !$flipquiz->completionpass) {
        return $type;
    }

    // Check if the user has used up all attempts.
    if ($flipquiz->completionattemptsexhausted) {
        $attempts = flipquiz_get_user_attempts($flipquiz->id, $userid, 'finished', true);
        if ($attempts) {
            $lastfinishedattempt = end($attempts);
            $context = context_module::instance($cm->id);
            $flipquizobj = flipquiz::create($flipquiz->id, $userid);
            $accessmanager = new flipquiz_access_manager($flipquizobj, time(),
                    has_capability('mod/flipquiz:ignoretimelimits', $context, $userid, false));
            if ($accessmanager->is_finished(count($attempts), $lastfinishedattempt)) {
                return true;
            }
        }
    }

    // Check for passing grade.
    if ($flipquiz->completionpass) {
        require_once($CFG->libdir . '/gradelib.php');
        $item = grade_item::fetch(array('courseid' => $course->id, 'itemtype' => 'mod',
                'itemmodule' => 'flipquiz', 'iteminstance' => $cm->instance, 'outcomeid' => null));
        if ($item) {
            $grades = grade_grade::fetch_users_grades($item, array($userid), false);
            if (!empty($grades[$userid])) {
                return $grades[$userid]->is_passed($item);
            }
        }
    }
    return false;
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function flipquiz_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER, $CFG;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

    $updates = course_check_module_updates_since($cm, $from, array(), $filter);

    // Check if questions were updated.
    $updates->questions = (object) array('updated' => false);
    $flipquizobj = flipquiz::create($cm->instance, $USER->id);
    $flipquizobj->preload_questions();
    $flipquizobj->load_questions();
    $questionids = array_keys($flipquizobj->get_questions());
    if (!empty($questionids)) {
        list($questionsql, $params) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
        $select = 'id ' . $questionsql . ' AND (timemodified > :time1 OR timecreated > :time2)';
        $params['time1'] = $from;
        $params['time2'] = $from;
        $questions = $DB->get_records_select('question', $select, $params, '', 'id');
        if (!empty($questions)) {
            $updates->questions->updated = true;
            $updates->questions->itemids = array_keys($questions);
        }
    }

    // Check for new attempts or grades.
    $updates->attempts = (object) array('updated' => false);
    $updates->grades = (object) array('updated' => false);
    $select = 'flipquiz = ? AND userid = ? AND timemodified > ?';
    $params = array($cm->instance, $USER->id, $from);

    $attempts = $DB->get_records_select('flipquiz_attempts', $select, $params, '', 'id');
    if (!empty($attempts)) {
        $updates->attempts->updated = true;
        $updates->attempts->itemids = array_keys($attempts);
    }
    $grades = $DB->get_records_select('flipquiz_grades', $select, $params, '', 'id');
    if (!empty($grades)) {
        $updates->grades->updated = true;
        $updates->grades->itemids = array_keys($grades);
    }

    // Now, teachers should see other students updates.
    if (has_capability('mod/flipquiz:viewreports', $cm->context)) {
        $select = 'flipquiz = ? AND timemodified > ?';
        $params = array($cm->instance, $from);

        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->userattempts = (object) array('updated' => false);
        $attempts = $DB->get_records_select('flipquiz_attempts', $select, $params, '', 'id');
        if (!empty($attempts)) {
            $updates->userattempts->updated = true;
            $updates->userattempts->itemids = array_keys($attempts);
        }

        $updates->usergrades = (object) array('updated' => false);
        $grades = $DB->get_records_select('flipquiz_grades', $select, $params, '', 'id');
        if (!empty($grades)) {
            $updates->usergrades->updated = true;
            $updates->usergrades->itemids = array_keys($grades);
        }
    }
    return $updates;
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_flipquiz_get_fontawesome_icon_map() {
    return [
        'mod_flipquiz:navflagged' => 'fa-flag',
    ];
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_flipquiz_core_calendar_provide_event_action(calendar_event $event,
                                                     \core_calendar\action_factory $factory) {
    global $CFG, $USER;

    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

    $cm = get_fast_modinfo($event->courseid)->instances['flipquiz'][$event->instance];
    $flipquizobj = flipquiz::create($cm->instance, $USER->id);
    $flipquiz = $flipquizobj->get_flipquiz();

    // Check they have capabilities allowing them to view the flipquiz.
    if (!has_any_capability(array('mod/flipquiz:reviewmyattempts', 'mod/flipquiz:attempt'), $flipquizobj->get_context())) {
        return null;
    }

    flipquiz_update_effective_access($flipquiz, $USER->id);

    // Check if flipquiz is closed, if so don't display it.
    if (!empty($flipquiz->timeclose) && $flipquiz->timeclose <= time()) {
        return null;
    }

    $attempts = flipquiz_get_user_attempts($flipquizobj->get_flipquizid(), $USER->id);
    if (!empty($attempts)) {
        // The student's last attempt is finished.
        return null;
    }

    $name = get_string('attemptflipquiznow', 'flipquiz');
    $url = new \moodle_url('/mod/flipquiz/view.php', [
        'id' => $cm->id
    ]);
    $itemcount = 1;
    $actionable = true;

    // Check if the flipquiz is not currently actionable.
    if (!empty($flipquiz->timeopen) && $flipquiz->timeopen > time()) {
        $actionable = false;
    }

    return $factory->create_instance(
        $name,
        $url,
        $itemcount,
        $actionable
    );
}

/**
 * Add a get_coursemodule_info function in case any flipquiz type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function flipquiz_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionattemptsexhausted, completionpass';
    if (!$flipquiz = $DB->get_record('flipquiz', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $flipquiz->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('flipquiz', $flipquiz, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionattemptsexhausted'] = $flipquiz->completionattemptsexhausted;
        $result->customdata['customcompletionrules']['completionpass'] = $flipquiz->completionpass;
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_flipquiz_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionattemptsexhausted':
                if (empty($val)) {
                    continue;
                }
                $descriptions[] = get_string('completionattemptsexhausteddesc', 'flipquiz');
                break;
            case 'completionpass':
                if (empty($val)) {
                    continue;
                }
                $descriptions[] = get_string('completionpassdesc', 'flipquiz', format_time($val));
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * Returns the min and max values for the timestart property of a flipquiz
 * activity event.
 *
 * The min and max values will be the timeopen and timeclose properties
 * of the flipquiz, respectively, if they are set.
 *
 * If either value isn't set then null will be returned instead to
 * indicate that there is no cutoff for that value.
 *
 * If the vent has no valid timestart range then [false, false] will
 * be returned. This is the case for overriden events.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The date must be after this date'],
 *     [1506741172, 'The date must be before this date']
 * ]
 *
 * @throws \moodle_exception
 * @param \calendar_event $event The calendar event to get the time range for
 * @param stdClass $flipquiz The module instance to get the range from
 * @return array
 */
function mod_flipquiz_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $flipquiz) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

    // Overrides do not have a valid timestart range.
    if (flipquiz_is_overriden_calendar_event($event)) {
        return [false, false];
    }

    $mindate = null;
    $maxdate = null;

    if ($event->eventtype == FLIPQUIZ_EVENT_TYPE_OPEN) {
        if (!empty($flipquiz->timeclose)) {
            $maxdate = [
                $flipquiz->timeclose,
                get_string('openafterclose', 'flipquiz')
            ];
        }
    } else if ($event->eventtype == FLIPQUIZ_EVENT_TYPE_CLOSE) {
        if (!empty($flipquiz->timeopen)) {
            $mindate = [
                $flipquiz->timeopen,
                get_string('closebeforeopen', 'flipquiz')
            ];
        }
    }

    return [$mindate, $maxdate];
}

/**
 * This function will update the flipquiz module according to the
 * event that has been modified.
 *
 * It will set the timeopen or timeclose value of the flipquiz instance
 * according to the type of event provided.
 *
 * @throws \moodle_exception
 * @param \calendar_event $event A flipquiz activity calendar event
 * @param \stdClass $flipquiz A flipquiz activity instance
 */
function mod_flipquiz_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $flipquiz) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');

    if (!in_array($event->eventtype, [FLIPQUIZ_EVENT_TYPE_OPEN, FLIPQUIZ_EVENT_TYPE_CLOSE])) {
        // This isn't an event that we care about so we can ignore it.
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;
    $closedatechanged = false;

    // Something weird going on. The event is for a different module so
    // we should ignore it.
    if ($modulename != 'flipquiz') {
        return;
    }

    if ($flipquiz->id != $instanceid) {
        // The provided flipquiz instance doesn't match the event so
        // there is nothing to do here.
        return;
    }

    // We don't update the activity if it's an override event that has
    // been modified.
    if (flipquiz_is_overriden_calendar_event($event)) {
        return;
    }

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($event->eventtype == FLIPQUIZ_EVENT_TYPE_OPEN) {
        // If the event is for the flipquiz activity opening then we should
        // set the start time of the flipquiz activity to be the new start
        // time of the event.
        if ($flipquiz->timeopen != $event->timestart) {
            $flipquiz->timeopen = $event->timestart;
            $modified = true;
        }
    } else if ($event->eventtype == FLIPQUIZ_EVENT_TYPE_CLOSE) {
        // If the event is for the flipquiz activity closing then we should
        // set the end time of the flipquiz activity to be the new start
        // time of the event.
        if ($flipquiz->timeclose != $event->timestart) {
            $flipquiz->timeclose = $event->timestart;
            $modified = true;
            $closedatechanged = true;
        }
    }

    if ($modified) {
        $flipquiz->timemodified = time();
        $DB->update_record('flipquiz', $flipquiz);

        if ($closedatechanged) {
            flipquiz_update_open_attempts(array('flipquizid' => $flipquiz->id));
        }

        // Delete any previous preview attempts.
        flipquiz_delete_previews($flipquiz);
        flipquiz_update_events($flipquiz);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}

/**
 * Generates the question bank in a fragment output. This allows
 * the question bank to be displayed in a modal.
 *
 * The only expected argument provided in the $args array is
 * 'querystring'. The value should be the list of parameters
 * URL encoded and used to build the question bank page.
 *
 * The individual list of parameters expected can be found in
 * question_build_edit_resources.
 *
 * @param array $args The fragment arguments.
 * @return string The rendered mform fragment.
 */
function mod_flipquiz_output_fragment_flipquiz_question_bank($args) {
    global $CFG, $DB, $PAGE;
    require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');
    require_once($CFG->dirroot . '/question/editlib.php');

    $querystring = preg_replace('/^\?/', '', $args['querystring']);
    $params = [];
    parse_str($querystring, $params);

    // Build the required resources. The $params are all cleaned as
    // part of this process.
    list($thispageurl, $contexts, $cmid, $cm, $flipquiz, $pagevars) =
            question_build_edit_resources('editq', '/mod/flipquiz/edit.php', $params);

    // Get the course object and related bits.
    $course = $DB->get_record('course', array('id' => $flipquiz->course), '*', MUST_EXIST);
    require_capability('mod/flipquiz:manage', $contexts->lowest());

    // Create flipquiz question bank view.
    $questionbank = new mod_flipquiz\question\bank\custom_view($contexts, $thispageurl, $course, $cm, $flipquiz);
    $questionbank->set_flipquiz_has_attempts(flipquiz_has_attempts($flipquiz->id));

    // Output.
    $renderer = $PAGE->get_renderer('mod_flipquiz', 'edit');
    return $renderer->question_bank_contents($questionbank, $pagevars);
}

/**
 * Generates the add random question in a fragment output. This allows the
 * form to be rendered in javascript, for example inside a modal.
 *
 * The required arguments as keys in the $args array are:
 *      cat {string} The category and category context ids comma separated.
 *      addonpage {int} The page id to add this question to.
 *      returnurl {string} URL to return to after form submission.
 *      cmid {int} The course module id the questions are being added to.
 *
 * @param array $args The fragment arguments.
 * @return string The rendered mform fragment.
 */
function mod_flipquiz_output_fragment_add_random_question_form($args) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/flipquiz/addrandomform.php');

    $contexts = new \question_edit_contexts($args['context']);
    $formoptions = [
        'contexts' => $contexts,
        'cat' => $args['cat']
    ];
    $formdata = [
        'category' => $args['cat'],
        'addonpage' => $args['addonpage'],
        'returnurl' => $args['returnurl'],
        'cmid' => $args['cmid']
    ];

    $form = new flipquiz_add_random_form(
        new \moodle_url('/mod/flipquiz/addrandom.php'),
        $formoptions,
        'post',
        '',
        null,
        true,
        $formdata
    );
    $form->set_data($formdata);

    return $form->render();
}
