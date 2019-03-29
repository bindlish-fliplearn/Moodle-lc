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
 * Library of functions used by the hwork module.
 *
 * This contains functions that are called from within the hwork module only
 * Functions that are also called by core Moodle are in {@link lib.php}
 * This script also loads the code in {@link questionlib.php} which holds
 * the module-indpendent code for handling questions and which in turn
 * initialises all the questiontype classes.
 *
 * @package    mod_hwork
 * @copyright  1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/hwork/lib.php');
require_once($CFG->dirroot . '/mod/hwork/accessmanager.php');
require_once($CFG->dirroot . '/mod/hwork/accessmanager_form.php');
require_once($CFG->dirroot . '/mod/hwork/renderer.php');
require_once($CFG->dirroot . '/mod/hwork/attemptlib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/questionlib.php');


/**
 * @var int We show the countdown timer if there is less than this amount of time left before the
 * the hwork close date. (1 hour)
 */
define('QUIZ_SHOW_TIME_BEFORE_DEADLINE', '3600');

/**
 * @var int If there are fewer than this many seconds left when the student submits
 * a page of the hwork, then do not take them to the next page of the hwork. Instead
 * close the hwork immediately.
 */
define('QUIZ_MIN_TIME_TO_CONTINUE', '2');

/**
 * @var int We show no image when user selects No image from dropdown menu in hwork settings.
 */
define('QUIZ_SHOWIMAGE_NONE', 0);

/**
 * @var int We show small image when user selects small image from dropdown menu in hwork settings.
 */
define('QUIZ_SHOWIMAGE_SMALL', 1);

/**
 * @var int We show Large image when user selects Large image from dropdown menu in hwork settings.
 */
define('QUIZ_SHOWIMAGE_LARGE', 2);


// Functions related to attempts ///////////////////////////////////////////////

/**
 * Creates an object to represent a new attempt at a hwork
 *
 * Creates an attempt object to represent an attempt at the hwork by the current
 * user starting at the current time. The ->id field is not set. The object is
 * NOT written to the database.
 *
 * @param object $hworkobj the hwork object to create an attempt for.
 * @param int $attemptnumber the sequence number for the attempt.
 * @param object $lastattempt the previous attempt by this user, if any. Only needed
 *         if $attemptnumber > 1 and $hwork->attemptonlast is true.
 * @param int $timenow the time the attempt was started at.
 * @param bool $ispreview whether this new attempt is a preview.
 * @param int $userid  the id of the user attempting this hwork.
 *
 * @return object the newly created attempt object.
 */
function hwork_create_attempt(hwork $hworkobj, $attemptnumber, $lastattempt, $timenow, $ispreview = false, $userid = null) {
    global $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $hwork = $hworkobj->get_hwork();
    if ($hwork->sumgrades < 0.000005 && $hwork->grade > 0.000005) {
        throw new moodle_exception('cannotstartgradesmismatch', 'hwork',
                new moodle_url('/mod/hwork/view.php', array('q' => $hwork->id)),
                    array('grade' => hwork_format_grade($hwork, $hwork->grade)));
    }

    if ($attemptnumber == 1 || !$hwork->attemptonlast) {
        // We are not building on last attempt so create a new attempt.
        $attempt = new stdClass();
        $attempt->hwork = $hwork->id;
        $attempt->userid = $userid;
        $attempt->preview = 0;
        $attempt->layout = '';
    } else {
        // Build on last attempt.
        if (empty($lastattempt)) {
            print_error('cannotfindprevattempt', 'hwork');
        }
        $attempt = $lastattempt;
    }

    $attempt->attempt = $attemptnumber;
    $attempt->timestart = $timenow;
    $attempt->timefinish = 0;
    $attempt->timemodified = $timenow;
    $attempt->timemodifiedoffline = 0;
    $attempt->state = hwork_attempt::IN_PROGRESS;
    $attempt->currentpage = 0;
    $attempt->sumgrades = null;

    // If this is a preview, mark it as such.
    if ($ispreview) {
        $attempt->preview = 1;
    }

    $timeclose = $hworkobj->get_access_manager($timenow)->get_end_time($attempt);
    if ($timeclose === false || $ispreview) {
        $attempt->timecheckstate = null;
    } else {
        $attempt->timecheckstate = $timeclose;
    }

    return $attempt;
}
/**
 * Start a normal, new, hwork attempt.
 *
 * @param hwork      $hworkobj            the hwork object to start an attempt for.
 * @param question_usage_by_activity $quba
 * @param object    $attempt
 * @param integer   $attemptnumber      starting from 1
 * @param integer   $timenow            the attempt start time
 * @param array     $questionids        slot number => question id. Used for random questions, to force the choice
 *                                        of a particular actual question. Intended for testing purposes only.
 * @param array     $forcedvariantsbyslot slot number => variant. Used for questions with variants,
 *                                          to force the choice of a particular variant. Intended for testing
 *                                          purposes only.
 * @throws moodle_exception
 * @return object   modified attempt object
 */
function hwork_start_new_attempt($hworkobj, $quba, $attempt, $attemptnumber, $timenow,
                                $questionids = array(), $forcedvariantsbyslot = array()) {

    // Usages for this user's previous hwork attempts.
    $qubaids = new \mod_hwork\question\qubaids_for_users_attempts(
            $hworkobj->get_hworkid(), $attempt->userid);

    // Fully load all the questions in this hwork.
    $hworkobj->preload_questions();
    $hworkobj->load_questions();

    // First load all the non-random questions.
    $randomfound = false;
    $slot = 0;
    $questions = array();
    $maxmark = array();
    $page = array();
    foreach ($hworkobj->get_questions() as $questiondata) {
        $slot += 1;
        $maxmark[$slot] = $questiondata->maxmark;
        $page[$slot] = $questiondata->page;
        if ($questiondata->qtype == 'random') {
            $randomfound = true;
            continue;
        }
        if (!$hworkobj->get_hwork()->shuffleanswers) {
            $questiondata->options->shuffleanswers = false;
        }
        $questions[$slot] = question_bank::make_question($questiondata);
    }

    // Then find a question to go in place of each random question.
    if ($randomfound) {
        $slot = 0;
        $usedquestionids = array();
        foreach ($questions as $question) {
            if (isset($usedquestions[$question->id])) {
                $usedquestionids[$question->id] += 1;
            } else {
                $usedquestionids[$question->id] = 1;
            }
        }
        $randomloader = new \core_question\bank\random_question_loader($qubaids, $usedquestionids);

        foreach ($hworkobj->get_questions() as $questiondata) {
            $slot += 1;
            if ($questiondata->qtype != 'random') {
                continue;
            }

            $tagids = hwork_retrieve_slot_tag_ids($questiondata->slotid);

            // Deal with fixed random choices for testing.
            if (isset($questionids[$quba->next_slot_number()])) {
                if ($randomloader->is_question_available($questiondata->category,
                        (bool) $questiondata->questiontext, $questionids[$quba->next_slot_number()], $tagids)) {
                    $questions[$slot] = question_bank::load_question(
                            $questionids[$quba->next_slot_number()], $hworkobj->get_hwork()->shuffleanswers);
                    continue;
                } else {
                    throw new coding_exception('Forced question id not available.');
                }
            }

            // Normal case, pick one at random.
            $questionid = $randomloader->get_next_question_id($questiondata->randomfromcategory,
                    $questiondata->randomincludingsubcategories, $tagids);
            if ($questionid === null) {
                throw new moodle_exception('notenoughrandomquestions', 'hwork',
                                           $hworkobj->view_url(), $questiondata);
            }

            $questions[$slot] = question_bank::load_question($questionid,
                    $hworkobj->get_hwork()->shuffleanswers);
        }
    }

    // Finally add them all to the usage.
    ksort($questions);
    foreach ($questions as $slot => $question) {
        $newslot = $quba->add_question($question, $maxmark[$slot]);
        if ($newslot != $slot) {
            throw new coding_exception('Slot numbers have got confused.');
        }
    }

    // Start all the questions.
    $variantstrategy = new core_question\engine\variants\least_used_strategy($quba, $qubaids);

    if (!empty($forcedvariantsbyslot)) {
        $forcedvariantsbyseed = question_variant_forced_choices_selection_strategy::prepare_forced_choices_array(
            $forcedvariantsbyslot, $quba);
        $variantstrategy = new question_variant_forced_choices_selection_strategy(
            $forcedvariantsbyseed, $variantstrategy);
    }

    $quba->start_all_questions($variantstrategy, $timenow);

    // Work out the attempt layout.
    $sections = $hworkobj->get_sections();
    foreach ($sections as $i => $section) {
        if (isset($sections[$i + 1])) {
            $sections[$i]->lastslot = $sections[$i + 1]->firstslot - 1;
        } else {
            $sections[$i]->lastslot = count($questions);
        }
    }

    $layout = array();
    foreach ($sections as $section) {
        if ($section->shufflequestions) {
            $questionsinthissection = array();
            for ($slot = $section->firstslot; $slot <= $section->lastslot; $slot += 1) {
                $questionsinthissection[] = $slot;
            }
            shuffle($questionsinthissection);
            $questionsonthispage = 0;
            foreach ($questionsinthissection as $slot) {
                if ($questionsonthispage && $questionsonthispage == $hworkobj->get_hwork()->questionsperpage) {
                    $layout[] = 0;
                    $questionsonthispage = 0;
                }
                $layout[] = $slot;
                $questionsonthispage += 1;
            }

        } else {
            $currentpage = $page[$section->firstslot];
            for ($slot = $section->firstslot; $slot <= $section->lastslot; $slot += 1) {
                if ($currentpage !== null && $page[$slot] != $currentpage) {
                    $layout[] = 0;
                }
                $layout[] = $slot;
                $currentpage = $page[$slot];
            }
        }

        // Each section ends with a page break.
        $layout[] = 0;
    }
    $attempt->layout = implode(',', $layout);

    return $attempt;
}

/**
 * Start a subsequent new attempt, in each attempt builds on last mode.
 *
 * @param question_usage_by_activity    $quba         this question usage
 * @param object                        $attempt      this attempt
 * @param object                        $lastattempt  last attempt
 * @return object                       modified attempt object
 *
 */
function hwork_start_attempt_built_on_last($quba, $attempt, $lastattempt) {
    $oldquba = question_engine::load_questions_usage_by_activity($lastattempt->uniqueid);

    $oldnumberstonew = array();
    foreach ($oldquba->get_attempt_iterator() as $oldslot => $oldqa) {
        $newslot = $quba->add_question($oldqa->get_question(), $oldqa->get_max_mark());

        $quba->start_question_based_on($newslot, $oldqa);

        $oldnumberstonew[$oldslot] = $newslot;
    }

    // Update attempt layout.
    $newlayout = array();
    foreach (explode(',', $lastattempt->layout) as $oldslot) {
        if ($oldslot != 0) {
            $newlayout[] = $oldnumberstonew[$oldslot];
        } else {
            $newlayout[] = 0;
        }
    }
    $attempt->layout = implode(',', $newlayout);
    return $attempt;
}

/**
 * The save started question usage and hwork attempt in db and log the started attempt.
 *
 * @param hwork                       $hworkobj
 * @param question_usage_by_activity $quba
 * @param object                     $attempt
 * @return object                    attempt object with uniqueid and id set.
 */
function hwork_attempt_save_started($hworkobj, $quba, $attempt) {
    global $DB;
    // Save the attempt in the database.
    question_engine::save_questions_usage_by_activity($quba);
    $attempt->uniqueid = $quba->get_id();
    $attempt->id = $DB->insert_record('hwork_attempts', $attempt);

    // Params used by the events below.
    $params = array(
        'objectid' => $attempt->id,
        'relateduserid' => $attempt->userid,
        'courseid' => $hworkobj->get_courseid(),
        'context' => $hworkobj->get_context()
    );
    // Decide which event we are using.
    if ($attempt->preview) {
        $params['other'] = array(
            'hworkid' => $hworkobj->get_hworkid()
        );
        $event = \mod_hwork\event\attempt_preview_started::create($params);
    } else {
        $event = \mod_hwork\event\attempt_started::create($params);

    }

    // Trigger the event.
    $event->add_record_snapshot('hwork', $hworkobj->get_hwork());
    $event->add_record_snapshot('hwork_attempts', $attempt);
    $event->trigger();

    return $attempt;
}

/**
 * Returns an unfinished attempt (if there is one) for the given
 * user on the given hwork. This function does not return preview attempts.
 *
 * @param int $hworkid the id of the hwork.
 * @param int $userid the id of the user.
 *
 * @return mixed the unfinished attempt if there is one, false if not.
 */
function hwork_get_user_attempt_unfinished($hworkid, $userid) {
    $attempts = hwork_get_user_attempts($hworkid, $userid, 'unfinished', true);
    if ($attempts) {
        return array_shift($attempts);
    } else {
        return false;
    }
}

/**
 * Delete a hwork attempt.
 * @param mixed $attempt an integer attempt id or an attempt object
 *      (row of the hwork_attempts table).
 * @param object $hwork the hwork object.
 */
function hwork_delete_attempt($attempt, $hwork) {
    global $DB;
    if (is_numeric($attempt)) {
        if (!$attempt = $DB->get_record('hwork_attempts', array('id' => $attempt))) {
            return;
        }
    }

    if ($attempt->hwork != $hwork->id) {
        debugging("Trying to delete attempt $attempt->id which belongs to hwork $attempt->hwork " .
                "but was passed hwork $hwork->id.");
        return;
    }

    if (!isset($hwork->cmid)) {
        $cm = get_coursemodule_from_instance('hwork', $hwork->id, $hwork->course);
        $hwork->cmid = $cm->id;
    }

    question_engine::delete_questions_usage_by_activity($attempt->uniqueid);
    $DB->delete_records('hwork_attempts', array('id' => $attempt->id));

    // Log the deletion of the attempt if not a preview.
    if (!$attempt->preview) {
        $params = array(
            'objectid' => $attempt->id,
            'relateduserid' => $attempt->userid,
            'context' => context_module::instance($hwork->cmid),
            'other' => array(
                'hworkid' => $hwork->id
            )
        );
        $event = \mod_hwork\event\attempt_deleted::create($params);
        $event->add_record_snapshot('hwork_attempts', $attempt);
        $event->trigger();
    }

    // Search hwork_attempts for other instances by this user.
    // If none, then delete record for this hwork, this user from hwork_grades
    // else recalculate best grade.
    $userid = $attempt->userid;
    if (!$DB->record_exists('hwork_attempts', array('userid' => $userid, 'hwork' => $hwork->id))) {
        $DB->delete_records('hwork_grades', array('userid' => $userid, 'hwork' => $hwork->id));
    } else {
        hwork_save_best_grade($hwork, $userid);
    }

    hwork_update_grades($hwork, $userid);
}

/**
 * Delete all the preview attempts at a hwork, or possibly all the attempts belonging
 * to one user.
 * @param object $hwork the hwork object.
 * @param int $userid (optional) if given, only delete the previews belonging to this user.
 */
function hwork_delete_previews($hwork, $userid = null) {
    global $DB;
    $conditions = array('hwork' => $hwork->id, 'preview' => 1);
    if (!empty($userid)) {
        $conditions['userid'] = $userid;
    }
    $previewattempts = $DB->get_records('hwork_attempts', $conditions);
    foreach ($previewattempts as $attempt) {
        hwork_delete_attempt($attempt, $hwork);
    }
}

/**
 * @param int $hworkid The hwork id.
 * @return bool whether this hwork has any (non-preview) attempts.
 */
function hwork_has_attempts($hworkid) {
    global $DB;
    return $DB->record_exists('hwork_attempts', array('hwork' => $hworkid, 'preview' => 0));
}

// Functions to do with hwork layout and pages //////////////////////////////////

/**
 * Repaginate the questions in a hwork
 * @param int $hworkid the id of the hwork to repaginate.
 * @param int $slotsperpage number of items to put on each page. 0 means unlimited.
 */
function hwork_repaginate_questions($hworkid, $slotsperpage) {
    global $DB;
    $trans = $DB->start_delegated_transaction();

    $sections = $DB->get_records('hwork_sections', array('hworkid' => $hworkid), 'firstslot ASC');
    $firstslots = array();
    foreach ($sections as $section) {
        if ((int)$section->firstslot === 1) {
            continue;
        }
        $firstslots[] = $section->firstslot;
    }

    $slots = $DB->get_records('hwork_slots', array('hworkid' => $hworkid),
            'slot');
    $currentpage = 1;
    $slotsonthispage = 0;
    foreach ($slots as $slot) {
        if (($firstslots && in_array($slot->slot, $firstslots)) ||
            ($slotsonthispage && $slotsonthispage == $slotsperpage)) {
            $currentpage += 1;
            $slotsonthispage = 0;
        }
        if ($slot->page != $currentpage) {
            $DB->set_field('hwork_slots', 'page', $currentpage, array('id' => $slot->id));
        }
        $slotsonthispage += 1;
    }

    $trans->allow_commit();
}

// Functions to do with hwork grades ////////////////////////////////////////////

/**
 * Convert the raw grade stored in $attempt into a grade out of the maximum
 * grade for this hwork.
 *
 * @param float $rawgrade the unadjusted grade, fof example $attempt->sumgrades
 * @param object $hwork the hwork object. Only the fields grade, sumgrades and decimalpoints are used.
 * @param bool|string $format whether to format the results for display
 *      or 'question' to format a question grade (different number of decimal places.
 * @return float|string the rescaled grade, or null/the lang string 'notyetgraded'
 *      if the $grade is null.
 */
function hwork_rescale_grade($rawgrade, $hwork, $format = true) {
    if (is_null($rawgrade)) {
        $grade = null;
    } else if ($hwork->sumgrades >= 0.000005) {
        $grade = $rawgrade * $hwork->grade / $hwork->sumgrades;
    } else {
        $grade = 0;
    }
    if ($format === 'question') {
        $grade = hwork_format_question_grade($hwork, $grade);
    } else if ($format) {
        $grade = hwork_format_grade($hwork, $grade);
    }
    return $grade;
}

/**
 * Get the feedback object for this grade on this hwork.
 *
 * @param float $grade a grade on this hwork.
 * @param object $hwork the hwork settings.
 * @return false|stdClass the record object or false if there is not feedback for the given grade
 * @since  Moodle 3.1
 */
function hwork_feedback_record_for_grade($grade, $hwork) {
    global $DB;

    // With CBM etc, it is possible to get -ve grades, which would then not match
    // any feedback. Therefore, we replace -ve grades with 0.
    $grade = max($grade, 0);

    $feedback = $DB->get_record_select('hwork_feedback',
            'hworkid = ? AND mingrade <= ? AND ? < maxgrade', array($hwork->id, $grade, $grade));

    return $feedback;
}

/**
 * Get the feedback text that should be show to a student who
 * got this grade on this hwork. The feedback is processed ready for diplay.
 *
 * @param float $grade a grade on this hwork.
 * @param object $hwork the hwork settings.
 * @param object $context the hwork context.
 * @return string the comment that corresponds to this grade (empty string if there is not one.
 */
function hwork_feedback_for_grade($grade, $hwork, $context) {

    if (is_null($grade)) {
        return '';
    }

    $feedback = hwork_feedback_record_for_grade($grade, $hwork);

    if (empty($feedback->feedbacktext)) {
        return '';
    }

    // Clean the text, ready for display.
    $formatoptions = new stdClass();
    $formatoptions->noclean = true;
    $feedbacktext = file_rewrite_pluginfile_urls($feedback->feedbacktext, 'pluginfile.php',
            $context->id, 'mod_hwork', 'feedback', $feedback->id);
    $feedbacktext = format_text($feedbacktext, $feedback->feedbacktextformat, $formatoptions);

    return $feedbacktext;
}

/**
 * @param object $hwork the hwork database row.
 * @return bool Whether this hwork has any non-blank feedback text.
 */
function hwork_has_feedback($hwork) {
    global $DB;
    static $cache = array();
    if (!array_key_exists($hwork->id, $cache)) {
        $cache[$hwork->id] = hwork_has_grades($hwork) &&
                $DB->record_exists_select('hwork_feedback', "hworkid = ? AND " .
                    $DB->sql_isnotempty('hwork_feedback', 'feedbacktext', false, true),
                array($hwork->id));
    }
    return $cache[$hwork->id];
}

/**
 * Update the sumgrades field of the hwork. This needs to be called whenever
 * the grading structure of the hwork is changed. For example if a question is
 * added or removed, or a question weight is changed.
 *
 * You should call {@link hwork_delete_previews()} before you call this function.
 *
 * @param object $hwork a hwork.
 */
function hwork_update_sumgrades($hwork) {
    global $DB;

    $sql = 'UPDATE {hwork}
            SET sumgrades = COALESCE((
                SELECT SUM(maxmark)
                FROM {hwork_slots}
                WHERE hworkid = {hwork}.id
            ), 0)
            WHERE id = ?';
    $DB->execute($sql, array($hwork->id));
    $hwork->sumgrades = $DB->get_field('hwork', 'sumgrades', array('id' => $hwork->id));

    if ($hwork->sumgrades < 0.000005 && hwork_has_attempts($hwork->id)) {
        // If the hwork has been attempted, and the sumgrades has been
        // set to 0, then we must also set the maximum possible grade to 0, or
        // we will get a divide by zero error.
        hwork_set_grade(0, $hwork);
    }
}

/**
 * Update the sumgrades field of the attempts at a hwork.
 *
 * @param object $hwork a hwork.
 */
function hwork_update_all_attempt_sumgrades($hwork) {
    global $DB;
    $dm = new question_engine_data_mapper();
    $timenow = time();

    $sql = "UPDATE {hwork_attempts}
            SET
                timemodified = :timenow,
                sumgrades = (
                    {$dm->sum_usage_marks_subquery('uniqueid')}
                )
            WHERE hwork = :hworkid AND state = :finishedstate";
    $DB->execute($sql, array('timenow' => $timenow, 'hworkid' => $hwork->id,
            'finishedstate' => hwork_attempt::FINISHED));
}

/**
 * The hwork grade is the maximum that student's results are marked out of. When it
 * changes, the corresponding data in hwork_grades and hwork_feedback needs to be
 * rescaled. After calling this function, you probably need to call
 * hwork_update_all_attempt_sumgrades, hwork_update_all_final_grades and
 * hwork_update_grades.
 *
 * @param float $newgrade the new maximum grade for the hwork.
 * @param object $hwork the hwork we are updating. Passed by reference so its
 *      grade field can be updated too.
 * @return bool indicating success or failure.
 */
function hwork_set_grade($newgrade, $hwork) {
    global $DB;
    // This is potentially expensive, so only do it if necessary.
    if (abs($hwork->grade - $newgrade) < 1e-7) {
        // Nothing to do.
        return true;
    }

    $oldgrade = $hwork->grade;
    $hwork->grade = $newgrade;

    // Use a transaction, so that on those databases that support it, this is safer.
    $transaction = $DB->start_delegated_transaction();

    // Update the hwork table.
    $DB->set_field('hwork', 'grade', $newgrade, array('id' => $hwork->instance));

    if ($oldgrade < 1) {
        // If the old grade was zero, we cannot rescale, we have to recompute.
        // We also recompute if the old grade was too small to avoid underflow problems.
        hwork_update_all_final_grades($hwork);

    } else {
        // We can rescale the grades efficiently.
        $timemodified = time();
        $DB->execute("
                UPDATE {hwork_grades}
                SET grade = ? * grade, timemodified = ?
                WHERE hwork = ?
        ", array($newgrade/$oldgrade, $timemodified, $hwork->id));
    }

    if ($oldgrade > 1e-7) {
        // Update the hwork_feedback table.
        $factor = $newgrade/$oldgrade;
        $DB->execute("
                UPDATE {hwork_feedback}
                SET mingrade = ? * mingrade, maxgrade = ? * maxgrade
                WHERE hworkid = ?
        ", array($factor, $factor, $hwork->id));
    }

    // Update grade item and send all grades to gradebook.
    hwork_grade_item_update($hwork);
    hwork_update_grades($hwork);

    $transaction->allow_commit();
    return true;
}

/**
 * Save the overall grade for a user at a hwork in the hwork_grades table
 *
 * @param object $hwork The hwork for which the best grade is to be calculated and then saved.
 * @param int $userid The userid to calculate the grade for. Defaults to the current user.
 * @param array $attempts The attempts of this user. Useful if you are
 * looping through many users. Attempts can be fetched in one master query to
 * avoid repeated querying.
 * @return bool Indicates success or failure.
 */
function hwork_save_best_grade($hwork, $userid = null, $attempts = array()) {
    global $DB, $OUTPUT, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    if (!$attempts) {
        // Get all the attempts made by the user.
        $attempts = hwork_get_user_attempts($hwork->id, $userid);
    }

    // Calculate the best grade.
    $bestgrade = hwork_calculate_best_grade($hwork, $attempts);
    $bestgrade = hwork_rescale_grade($bestgrade, $hwork, false);

    // Save the best grade in the database.
    if (is_null($bestgrade)) {
        $DB->delete_records('hwork_grades', array('hwork' => $hwork->id, 'userid' => $userid));

    } else if ($grade = $DB->get_record('hwork_grades',
            array('hwork' => $hwork->id, 'userid' => $userid))) {
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->update_record('hwork_grades', $grade);

    } else {
        $grade = new stdClass();
        $grade->hwork = $hwork->id;
        $grade->userid = $userid;
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->insert_record('hwork_grades', $grade);
    }

    hwork_update_grades($hwork, $userid);
}

/**
 * Calculate the overall grade for a hwork given a number of attempts by a particular user.
 *
 * @param object $hwork    the hwork settings object.
 * @param array $attempts an array of all the user's attempts at this hwork in order.
 * @return float          the overall grade
 */
function hwork_calculate_best_grade($hwork, $attempts) {

    switch ($hwork->grademethod) {

        case QUIZ_ATTEMPTFIRST:
            $firstattempt = reset($attempts);
            return $firstattempt->sumgrades;

        case QUIZ_ATTEMPTLAST:
            $lastattempt = end($attempts);
            return $lastattempt->sumgrades;

        case QUIZ_GRADEAVERAGE:
            $sum = 0;
            $count = 0;
            foreach ($attempts as $attempt) {
                if (!is_null($attempt->sumgrades)) {
                    $sum += $attempt->sumgrades;
                    $count++;
                }
            }
            if ($count == 0) {
                return null;
            }
            return $sum / $count;

        case QUIZ_GRADEHIGHEST:
        default:
            $max = null;
            foreach ($attempts as $attempt) {
                if ($attempt->sumgrades > $max) {
                    $max = $attempt->sumgrades;
                }
            }
            return $max;
    }
}

/**
 * Update the final grade at this hwork for all students.
 *
 * This function is equivalent to calling hwork_save_best_grade for all
 * users, but much more efficient.
 *
 * @param object $hwork the hwork settings.
 */
function hwork_update_all_final_grades($hwork) {
    global $DB;

    if (!$hwork->sumgrades) {
        return;
    }

    $param = array('ihworkid' => $hwork->id, 'istatefinished' => hwork_attempt::FINISHED);
    $firstlastattemptjoin = "JOIN (
            SELECT
                ihworka.userid,
                MIN(attempt) AS firstattempt,
                MAX(attempt) AS lastattempt

            FROM {hwork_attempts} ihworka

            WHERE
                ihworka.state = :istatefinished AND
                ihworka.preview = 0 AND
                ihworka.hwork = :ihworkid

            GROUP BY ihworka.userid
        ) first_last_attempts ON first_last_attempts.userid = hworka.userid";

    switch ($hwork->grademethod) {
        case QUIZ_ATTEMPTFIRST:
            // Because of the where clause, there will only be one row, but we
            // must still use an aggregate function.
            $select = 'MAX(hworka.sumgrades)';
            $join = $firstlastattemptjoin;
            $where = 'hworka.attempt = first_last_attempts.firstattempt AND';
            break;

        case QUIZ_ATTEMPTLAST:
            // Because of the where clause, there will only be one row, but we
            // must still use an aggregate function.
            $select = 'MAX(hworka.sumgrades)';
            $join = $firstlastattemptjoin;
            $where = 'hworka.attempt = first_last_attempts.lastattempt AND';
            break;

        case QUIZ_GRADEAVERAGE:
            $select = 'AVG(hworka.sumgrades)';
            $join = '';
            $where = '';
            break;

        default:
        case QUIZ_GRADEHIGHEST:
            $select = 'MAX(hworka.sumgrades)';
            $join = '';
            $where = '';
            break;
    }

    if ($hwork->sumgrades >= 0.000005) {
        $finalgrade = $select . ' * ' . ($hwork->grade / $hwork->sumgrades);
    } else {
        $finalgrade = '0';
    }
    $param['hworkid'] = $hwork->id;
    $param['hworkid2'] = $hwork->id;
    $param['hworkid3'] = $hwork->id;
    $param['hworkid4'] = $hwork->id;
    $param['statefinished'] = hwork_attempt::FINISHED;
    $param['statefinished2'] = hwork_attempt::FINISHED;
    $finalgradesubquery = "
            SELECT hworka.userid, $finalgrade AS newgrade
            FROM {hwork_attempts} hworka
            $join
            WHERE
                $where
                hworka.state = :statefinished AND
                hworka.preview = 0 AND
                hworka.hwork = :hworkid3
            GROUP BY hworka.userid";

    $changedgrades = $DB->get_records_sql("
            SELECT users.userid, qg.id, qg.grade, newgrades.newgrade

            FROM (
                SELECT userid
                FROM {hwork_grades} qg
                WHERE hwork = :hworkid
            UNION
                SELECT DISTINCT userid
                FROM {hwork_attempts} hworka2
                WHERE
                    hworka2.state = :statefinished2 AND
                    hworka2.preview = 0 AND
                    hworka2.hwork = :hworkid2
            ) users

            LEFT JOIN {hwork_grades} qg ON qg.userid = users.userid AND qg.hwork = :hworkid4

            LEFT JOIN (
                $finalgradesubquery
            ) newgrades ON newgrades.userid = users.userid

            WHERE
                ABS(newgrades.newgrade - qg.grade) > 0.000005 OR
                ((newgrades.newgrade IS NULL OR qg.grade IS NULL) AND NOT
                          (newgrades.newgrade IS NULL AND qg.grade IS NULL))",
                // The mess on the previous line is detecting where the value is
                // NULL in one column, and NOT NULL in the other, but SQL does
                // not have an XOR operator, and MS SQL server can't cope with
                // (newgrades.newgrade IS NULL) <> (qg.grade IS NULL).
            $param);

    $timenow = time();
    $todelete = array();
    foreach ($changedgrades as $changedgrade) {

        if (is_null($changedgrade->newgrade)) {
            $todelete[] = $changedgrade->userid;

        } else if (is_null($changedgrade->grade)) {
            $toinsert = new stdClass();
            $toinsert->hwork = $hwork->id;
            $toinsert->userid = $changedgrade->userid;
            $toinsert->timemodified = $timenow;
            $toinsert->grade = $changedgrade->newgrade;
            $DB->insert_record('hwork_grades', $toinsert);

        } else {
            $toupdate = new stdClass();
            $toupdate->id = $changedgrade->id;
            $toupdate->grade = $changedgrade->newgrade;
            $toupdate->timemodified = $timenow;
            $DB->update_record('hwork_grades', $toupdate);
        }
    }

    if (!empty($todelete)) {
        list($test, $params) = $DB->get_in_or_equal($todelete);
        $DB->delete_records_select('hwork_grades', 'hwork = ? AND userid ' . $test,
                array_merge(array($hwork->id), $params));
    }
}

/**
 * Efficiently update check state time on all open attempts
 *
 * @param array $conditions optional restrictions on which attempts to update
 *                    Allowed conditions:
 *                      courseid => (array|int) attempts in given course(s)
 *                      userid   => (array|int) attempts for given user(s)
 *                      hworkid   => (array|int) attempts in given hwork(s)
 *                      groupid  => (array|int) hworkzes with some override for given group(s)
 *
 */
function hwork_update_open_attempts(array $conditions) {
    global $DB;

    foreach ($conditions as &$value) {
        if (!is_array($value)) {
            $value = array($value);
        }
    }

    $params = array();
    $wheres = array("hworka.state IN ('inprogress', 'overdue')");
    $iwheres = array("ihworka.state IN ('inprogress', 'overdue')");

    if (isset($conditions['courseid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['courseid'], SQL_PARAMS_NAMED, 'cid');
        $params = array_merge($params, $inparams);
        $wheres[] = "hworka.hwork IN (SELECT q.id FROM {hwork} q WHERE q.course $incond)";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['courseid'], SQL_PARAMS_NAMED, 'icid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "ihworka.hwork IN (SELECT q.id FROM {hwork} q WHERE q.course $incond)";
    }

    if (isset($conditions['userid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['userid'], SQL_PARAMS_NAMED, 'uid');
        $params = array_merge($params, $inparams);
        $wheres[] = "hworka.userid $incond";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['userid'], SQL_PARAMS_NAMED, 'iuid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "ihworka.userid $incond";
    }

    if (isset($conditions['hworkid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['hworkid'], SQL_PARAMS_NAMED, 'qid');
        $params = array_merge($params, $inparams);
        $wheres[] = "hworka.hwork $incond";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['hworkid'], SQL_PARAMS_NAMED, 'iqid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "ihworka.hwork $incond";
    }

    if (isset($conditions['groupid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['groupid'], SQL_PARAMS_NAMED, 'gid');
        $params = array_merge($params, $inparams);
        $wheres[] = "hworka.hwork IN (SELECT qo.hwork FROM {hwork_overrides} qo WHERE qo.groupid $incond)";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['groupid'], SQL_PARAMS_NAMED, 'igid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "ihworka.hwork IN (SELECT qo.hwork FROM {hwork_overrides} qo WHERE qo.groupid $incond)";
    }

    // SQL to compute timeclose and timelimit for each attempt:
    $hworkausersql = hwork_get_attempt_usertime_sql(
            implode("\n                AND ", $iwheres));

    // SQL to compute the new timecheckstate
    $timecheckstatesql = "
          CASE WHEN hworkauser.usertimelimit = 0 AND hworkauser.usertimeclose = 0 THEN NULL
               WHEN hworkauser.usertimelimit = 0 THEN hworkauser.usertimeclose
               WHEN hworkauser.usertimeclose = 0 THEN hworka.timestart + hworkauser.usertimelimit
               WHEN hworka.timestart + hworkauser.usertimelimit < hworkauser.usertimeclose THEN hworka.timestart + hworkauser.usertimelimit
               ELSE hworkauser.usertimeclose END +
          CASE WHEN hworka.state = 'overdue' THEN hwork.graceperiod ELSE 0 END";

    // SQL to select which attempts to process
    $attemptselect = implode("\n                         AND ", $wheres);

   /*
    * Each database handles updates with inner joins differently:
    *  - mysql does not allow a FROM clause
    *  - postgres and mssql allow FROM but handle table aliases differently
    *  - oracle requires a subquery
    *
    * Different code for each database.
    */

    $dbfamily = $DB->get_dbfamily();
    if ($dbfamily == 'mysql') {
        $updatesql = "UPDATE {hwork_attempts} hworka
                        JOIN {hwork} hwork ON hwork.id = hworka.hwork
                        JOIN ( $hworkausersql ) hworkauser ON hworkauser.id = hworka.id
                         SET hworka.timecheckstate = $timecheckstatesql
                       WHERE $attemptselect";
    } else if ($dbfamily == 'postgres') {
        $updatesql = "UPDATE {hwork_attempts} hworka
                         SET timecheckstate = $timecheckstatesql
                        FROM {hwork} hwork, ( $hworkausersql ) hworkauser
                       WHERE hwork.id = hworka.hwork
                         AND hworkauser.id = hworka.id
                         AND $attemptselect";
    } else if ($dbfamily == 'mssql') {
        $updatesql = "UPDATE hworka
                         SET timecheckstate = $timecheckstatesql
                        FROM {hwork_attempts} hworka
                        JOIN {hwork} hwork ON hwork.id = hworka.hwork
                        JOIN ( $hworkausersql ) hworkauser ON hworkauser.id = hworka.id
                       WHERE $attemptselect";
    } else {
        // oracle, sqlite and others
        $updatesql = "UPDATE {hwork_attempts} hworka
                         SET timecheckstate = (
                           SELECT $timecheckstatesql
                             FROM {hwork} hwork, ( $hworkausersql ) hworkauser
                            WHERE hwork.id = hworka.hwork
                              AND hworkauser.id = hworka.id
                         )
                         WHERE $attemptselect";
    }

    $DB->execute($updatesql, $params);
}

/**
 * Returns SQL to compute timeclose and timelimit for every attempt, taking into account user and group overrides.
 * The query used herein is very similar to the one in function hwork_get_user_timeclose, so, in case you
 * would change either one of them, make sure to apply your changes to both.
 *
 * @param string $redundantwhereclauses extra where clauses to add to the subquery
 *      for performance. These can use the table alias ihworka for the hwork attempts table.
 * @return string SQL select with columns attempt.id, usertimeclose, usertimelimit.
 */
function hwork_get_attempt_usertime_sql($redundantwhereclauses = '') {
    if ($redundantwhereclauses) {
        $redundantwhereclauses = 'WHERE ' . $redundantwhereclauses;
    }
    // The multiple qgo JOINS are necessary because we want timeclose/timelimit = 0 (unlimited) to supercede
    // any other group override
    $hworkausersql = "
          SELECT ihworka.id,
           COALESCE(MAX(quo.timeclose), MAX(qgo1.timeclose), MAX(qgo2.timeclose), ihwork.timeclose) AS usertimeclose,
           COALESCE(MAX(quo.timelimit), MAX(qgo3.timelimit), MAX(qgo4.timelimit), ihwork.timelimit) AS usertimelimit

           FROM {hwork_attempts} ihworka
           JOIN {hwork} ihwork ON ihwork.id = ihworka.hwork
      LEFT JOIN {hwork_overrides} quo ON quo.hwork = ihworka.hwork AND quo.userid = ihworka.userid
      LEFT JOIN {groups_members} gm ON gm.userid = ihworka.userid
      LEFT JOIN {hwork_overrides} qgo1 ON qgo1.hwork = ihworka.hwork AND qgo1.groupid = gm.groupid AND qgo1.timeclose = 0
      LEFT JOIN {hwork_overrides} qgo2 ON qgo2.hwork = ihworka.hwork AND qgo2.groupid = gm.groupid AND qgo2.timeclose > 0
      LEFT JOIN {hwork_overrides} qgo3 ON qgo3.hwork = ihworka.hwork AND qgo3.groupid = gm.groupid AND qgo3.timelimit = 0
      LEFT JOIN {hwork_overrides} qgo4 ON qgo4.hwork = ihworka.hwork AND qgo4.groupid = gm.groupid AND qgo4.timelimit > 0
          $redundantwhereclauses
       GROUP BY ihworka.id, ihwork.id, ihwork.timeclose, ihwork.timelimit";
    return $hworkausersql;
}

/**
 * Return the attempt with the best grade for a hwork
 *
 * Which attempt is the best depends on $hwork->grademethod. If the grade
 * method is GRADEAVERAGE then this function simply returns the last attempt.
 * @return object         The attempt with the best grade
 * @param object $hwork    The hwork for which the best grade is to be calculated
 * @param array $attempts An array of all the attempts of the user at the hwork
 */
function hwork_calculate_best_attempt($hwork, $attempts) {

    switch ($hwork->grademethod) {

        case QUIZ_ATTEMPTFIRST:
            foreach ($attempts as $attempt) {
                return $attempt;
            }
            break;

        case QUIZ_GRADEAVERAGE: // We need to do something with it.
        case QUIZ_ATTEMPTLAST:
            foreach ($attempts as $attempt) {
                $final = $attempt;
            }
            return $final;

        default:
        case QUIZ_GRADEHIGHEST:
            $max = -1;
            foreach ($attempts as $attempt) {
                if ($attempt->sumgrades > $max) {
                    $max = $attempt->sumgrades;
                    $maxattempt = $attempt;
                }
            }
            return $maxattempt;
    }
}

/**
 * @return array int => lang string the options for calculating the hwork grade
 *      from the individual attempt grades.
 */
function hwork_get_grading_options() {
    return array(
        QUIZ_GRADEHIGHEST => get_string('gradehighest', 'hwork'),
        QUIZ_GRADEAVERAGE => get_string('gradeaverage', 'hwork'),
        QUIZ_ATTEMPTFIRST => get_string('attemptfirst', 'hwork'),
        QUIZ_ATTEMPTLAST  => get_string('attemptlast', 'hwork')
    );
}

/**
 * @param int $option one of the values QUIZ_GRADEHIGHEST, QUIZ_GRADEAVERAGE,
 *      QUIZ_ATTEMPTFIRST or QUIZ_ATTEMPTLAST.
 * @return the lang string for that option.
 */
function hwork_get_grading_option_name($option) {
    $strings = hwork_get_grading_options();
    return $strings[$option];
}

/**
 * @return array string => lang string the options for handling overdue hwork
 *      attempts.
 */
function hwork_get_overdue_handling_options() {
    return array(
        'autosubmit'  => get_string('overduehandlingautosubmit', 'hwork'),
        'graceperiod' => get_string('overduehandlinggraceperiod', 'hwork'),
        'autoabandon' => get_string('overduehandlingautoabandon', 'hwork'),
    );
}

/**
 * Get the choices for what size user picture to show.
 * @return array string => lang string the options for whether to display the user's picture.
 */
function hwork_get_user_image_options() {
    return array(
        QUIZ_SHOWIMAGE_NONE  => get_string('shownoimage', 'hwork'),
        QUIZ_SHOWIMAGE_SMALL => get_string('showsmallimage', 'hwork'),
        QUIZ_SHOWIMAGE_LARGE => get_string('showlargeimage', 'hwork'),
    );
}

/**
 * Return an user's timeclose for all hworkzes in a course, hereby taking into account group and user overrides.
 *
 * @param int $courseid the course id.
 * @return object An object with of all hworkids and close unixdates in this course, taking into account the most lenient
 * overrides, if existing and 0 if no close date is set.
 */
function hwork_get_user_timeclose($courseid) {
    global $DB, $USER;

    // For teacher and manager/admins return timeclose.
    if (has_capability('moodle/course:update', context_course::instance($courseid))) {
        $sql = "SELECT hwork.id, hwork.timeclose AS usertimeclose
                  FROM {hwork} hwork
                 WHERE hwork.course = :courseid";

        $results = $DB->get_records_sql($sql, array('courseid' => $courseid));
        return $results;
    }

    $sql = "SELECT q.id,
  COALESCE(v.userclose, v.groupclose, q.timeclose, 0) AS usertimeclose
  FROM (
      SELECT hwork.id as hworkid,
             MAX(quo.timeclose) AS userclose, MAX(qgo.timeclose) AS groupclose
       FROM {hwork} hwork
  LEFT JOIN {hwork_overrides} quo on hwork.id = quo.hwork AND quo.userid = :userid
  LEFT JOIN {groups_members} gm ON gm.userid = :useringroupid
  LEFT JOIN {hwork_overrides} qgo on hwork.id = qgo.hwork AND qgo.groupid = gm.groupid
      WHERE hwork.course = :courseid
   GROUP BY hwork.id) v
       JOIN {hwork} q ON q.id = v.hworkid";

    $results = $DB->get_records_sql($sql, array('userid' => $USER->id, 'useringroupid' => $USER->id, 'courseid' => $courseid));
    return $results;

}

/**
 * Get the choices to offer for the 'Questions per page' option.
 * @return array int => string.
 */
function hwork_questions_per_page_options() {
    $pageoptions = array();
    $pageoptions[0] = get_string('neverallononepage', 'hwork');
    $pageoptions[1] = get_string('everyquestion', 'hwork');
    for ($i = 2; $i <= QUIZ_MAX_QPP_OPTION; ++$i) {
        $pageoptions[$i] = get_string('everynquestions', 'hwork', $i);
    }
    return $pageoptions;
}

/**
 * Get the human-readable name for a hwork attempt state.
 * @param string $state one of the state constants like {@link hwork_attempt::IN_PROGRESS}.
 * @return string The lang string to describe that state.
 */
function hwork_attempt_state_name($state) {
    switch ($state) {
        case hwork_attempt::IN_PROGRESS:
            return get_string('stateinprogress', 'hwork');
        case hwork_attempt::OVERDUE:
            return get_string('stateoverdue', 'hwork');
        case hwork_attempt::FINISHED:
            return get_string('statefinished', 'hwork');
        case hwork_attempt::ABANDONED:
            return get_string('stateabandoned', 'hwork');
        default:
            throw new coding_exception('Unknown hwork attempt state.');
    }
}

// Other hwork functions ////////////////////////////////////////////////////////

/**
 * @param object $hwork the hwork.
 * @param int $cmid the course_module object for this hwork.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @param int $variant which question variant to preview (optional).
 * @return string html for a number of icons linked to action pages for a
 * question - preview and edit / view icons depending on user capabilities.
 */
function hwork_question_action_icons($hwork, $cmid, $question, $returnurl, $variant = null) {
    $html = hwork_question_preview_button($hwork, $question, false, $variant) . ' ' .
            hwork_question_edit_button($cmid, $question, $returnurl);
    return $html;
}

/**
 * @param int $cmid the course_module.id for this hwork.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @param string $contentbeforeicon some HTML content to be added inside the link, before the icon.
 * @return the HTML for an edit icon, view icon, or nothing for a question
 *      (depending on permissions).
 */
function hwork_question_edit_button($cmid, $question, $returnurl, $contentaftericon = '') {
    global $CFG, $OUTPUT;

    // Minor efficiency saving. Only get strings once, even if there are a lot of icons on one page.
    static $stredit = null;
    static $strview = null;
    if ($stredit === null) {
        $stredit = get_string('edit');
        $strview = get_string('view');
    }

    // What sort of icon should we show?
    $action = '';
    if (!empty($question->id) &&
            (question_has_capability_on($question, 'edit') ||
                    question_has_capability_on($question, 'move'))) {
        $action = $stredit;
        $icon = 't/edit';
    } else if (!empty($question->id) &&
            question_has_capability_on($question, 'view')) {
        $action = $strview;
        $icon = 'i/info';
    }

    // Build the icon.
    if ($action) {
        if ($returnurl instanceof moodle_url) {
            $returnurl = $returnurl->out_as_local_url(false);
        }
        $questionparams = array('returnurl' => $returnurl, 'cmid' => $cmid, 'id' => $question->id);
        $questionurl = new moodle_url("$CFG->wwwroot/question/question.php", $questionparams);
        return '<a title="' . $action . '" href="' . $questionurl->out() . '" class="questioneditbutton">' .
                $OUTPUT->pix_icon($icon, $action) . $contentaftericon .
                '</a>';
    } else if ($contentaftericon) {
        return '<span class="questioneditbutton">' . $contentaftericon . '</span>';
    } else {
        return '';
    }
}

/**
 * @param object $hwork the hwork settings
 * @param object $question the question
 * @param int $variant which question variant to preview (optional).
 * @return moodle_url to preview this question with the options from this hwork.
 */
function hwork_question_preview_url($hwork, $question, $variant = null) {
    // Get the appropriate display options.
    $displayoptions = mod_hwork_display_options::make_from_hwork($hwork,
            mod_hwork_display_options::DURING);

    $maxmark = null;
    if (isset($question->maxmark)) {
        $maxmark = $question->maxmark;
    }

    // Work out the correcte preview URL.
    return question_preview_url($question->id, $hwork->preferredbehaviour,
            $maxmark, $displayoptions, $variant);
}

/**
 * @param object $hwork the hwork settings
 * @param object $question the question
 * @param bool $label if true, show the preview question label after the icon
 * @param int $variant which question variant to preview (optional).
 * @return the HTML for a preview question icon.
 */
function hwork_question_preview_button($hwork, $question, $label = false, $variant = null) {
    global $PAGE;
    if (!question_has_capability_on($question, 'use')) {
        return '';
    }

    return $PAGE->get_renderer('mod_hwork', 'edit')->question_preview_icon($hwork, $question, $label, $variant);
}

/**
 * @param object $attempt the attempt.
 * @param object $context the hwork context.
 * @return int whether flags should be shown/editable to the current user for this attempt.
 */
function hwork_get_flag_option($attempt, $context) {
    global $USER;
    if (!has_capability('moodle/question:flag', $context)) {
        return question_display_options::HIDDEN;
    } else if ($attempt->userid == $USER->id) {
        return question_display_options::EDITABLE;
    } else {
        return question_display_options::VISIBLE;
    }
}

/**
 * Work out what state this hwork attempt is in - in the sense used by
 * hwork_get_review_options, not in the sense of $attempt->state.
 * @param object $hwork the hwork settings
 * @param object $attempt the hwork_attempt database row.
 * @return int one of the mod_hwork_display_options::DURING,
 *      IMMEDIATELY_AFTER, LATER_WHILE_OPEN or AFTER_CLOSE constants.
 */
function hwork_attempt_state($hwork, $attempt) {
    if ($attempt->state == hwork_attempt::IN_PROGRESS) {
        return mod_hwork_display_options::DURING;
    } else if ($hwork->timeclose && time() >= $hwork->timeclose) {
        return mod_hwork_display_options::AFTER_CLOSE;
    } else if (time() < $attempt->timefinish + 120) {
        return mod_hwork_display_options::IMMEDIATELY_AFTER;
    } else {
        return mod_hwork_display_options::LATER_WHILE_OPEN;
    }
}

/**
 * The the appropraite mod_hwork_display_options object for this attempt at this
 * hwork right now.
 *
 * @param object $hwork the hwork instance.
 * @param object $attempt the attempt in question.
 * @param $context the hwork context.
 *
 * @return mod_hwork_display_options
 */
function hwork_get_review_options($hwork, $attempt, $context) {
    $options = mod_hwork_display_options::make_from_hwork($hwork, hwork_attempt_state($hwork, $attempt));

    $options->readonly = true;
    $options->flags = hwork_get_flag_option($attempt, $context);
    if (!empty($attempt->id)) {
        $options->questionreviewlink = new moodle_url('/mod/hwork/reviewquestion.php',
                array('attempt' => $attempt->id));
    }

    // Show a link to the comment box only for closed attempts.
    if (!empty($attempt->id) && $attempt->state == hwork_attempt::FINISHED && !$attempt->preview &&
            !is_null($context) && has_capability('mod/hwork:grade', $context)) {
        $options->manualcomment = question_display_options::VISIBLE;
        $options->manualcommentlink = new moodle_url('/mod/hwork/comment.php',
                array('attempt' => $attempt->id));
    }

    if (!is_null($context) && !$attempt->preview &&
            has_capability('mod/hwork:viewreports', $context) &&
            has_capability('moodle/grade:viewhidden', $context)) {
        // People who can see reports and hidden grades should be shown everything,
        // except during preview when teachers want to see what students see.
        $options->attempt = question_display_options::VISIBLE;
        $options->correctness = question_display_options::VISIBLE;
        $options->marks = question_display_options::MARK_AND_MAX;
        $options->feedback = question_display_options::VISIBLE;
        $options->numpartscorrect = question_display_options::VISIBLE;
        $options->manualcomment = question_display_options::VISIBLE;
        $options->generalfeedback = question_display_options::VISIBLE;
        $options->rightanswer = question_display_options::VISIBLE;
        $options->overallfeedback = question_display_options::VISIBLE;
        $options->history = question_display_options::VISIBLE;

    }

    return $options;
}

/**
 * Combines the review options from a number of different hwork attempts.
 * Returns an array of two ojects, so the suggested way of calling this
 * funciton is:
 * list($someoptions, $alloptions) = hwork_get_combined_reviewoptions(...)
 *
 * @param object $hwork the hwork instance.
 * @param array $attempts an array of attempt objects.
 *
 * @return array of two options objects, one showing which options are true for
 *          at least one of the attempts, the other showing which options are true
 *          for all attempts.
 */
function hwork_get_combined_reviewoptions($hwork, $attempts) {
    $fields = array('feedback', 'generalfeedback', 'rightanswer', 'overallfeedback');
    $someoptions = new stdClass();
    $alloptions = new stdClass();
    foreach ($fields as $field) {
        $someoptions->$field = false;
        $alloptions->$field = true;
    }
    $someoptions->marks = question_display_options::HIDDEN;
    $alloptions->marks = question_display_options::MARK_AND_MAX;

    // This shouldn't happen, but we need to prevent reveal information.
    if (empty($attempts)) {
        return array($someoptions, $someoptions);
    }

    foreach ($attempts as $attempt) {
        $attemptoptions = mod_hwork_display_options::make_from_hwork($hwork,
                hwork_attempt_state($hwork, $attempt));
        foreach ($fields as $field) {
            $someoptions->$field = $someoptions->$field || $attemptoptions->$field;
            $alloptions->$field = $alloptions->$field && $attemptoptions->$field;
        }
        $someoptions->marks = max($someoptions->marks, $attemptoptions->marks);
        $alloptions->marks = min($alloptions->marks, $attemptoptions->marks);
    }
    return array($someoptions, $alloptions);
}

// Functions for sending notification messages /////////////////////////////////

/**
 * Sends a confirmation message to the student confirming that the attempt was processed.
 *
 * @param object $a lots of useful information that can be used in the message
 *      subject and body.
 *
 * @return int|false as for {@link message_send()}.
 */
function hwork_send_confirmation($recipient, $a) {

    // Add information about the recipient to $a.
    // Don't do idnumber. we want idnumber to be the submitter's idnumber.
    $a->username     = fullname($recipient);
    $a->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->courseid          = $a->courseid;
    $eventdata->component         = 'mod_hwork';
    $eventdata->name              = 'confirmation';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = core_user::get_noreply_user();
    $eventdata->userto            = $recipient;
    $eventdata->subject           = get_string('emailconfirmsubject', 'hwork', $a);
    $eventdata->fullmessage       = get_string('emailconfirmbody', 'hwork', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = get_string('emailconfirmsmall', 'hwork', $a);
    $eventdata->contexturl        = $a->hworkurl;
    $eventdata->contexturlname    = $a->hworkname;

    // ... and send it.
    return message_send($eventdata);
}

/**
 * Sends notification messages to the interested parties that assign the role capability
 *
 * @param object $recipient user object of the intended recipient
 * @param object $a associative array of replaceable fields for the templates
 *
 * @return int|false as for {@link message_send()}.
 */
function hwork_send_notification($recipient, $submitter, $a) {

    // Recipient info for template.
    $a->useridnumber = $recipient->idnumber;
    $a->username     = fullname($recipient);
    $a->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->courseid          = $a->courseid;
    $eventdata->component         = 'mod_hwork';
    $eventdata->name              = 'submission';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = $submitter;
    $eventdata->userto            = $recipient;
    $eventdata->subject           = get_string('emailnotifysubject', 'hwork', $a);
    $eventdata->fullmessage       = get_string('emailnotifybody', 'hwork', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = get_string('emailnotifysmall', 'hwork', $a);
    $eventdata->contexturl        = $a->hworkreviewurl;
    $eventdata->contexturlname    = $a->hworkname;

    // ... and send it.
    return message_send($eventdata);
}

/**
 * Send all the requried messages when a hwork attempt is submitted.
 *
 * @param object $course the course
 * @param object $hwork the hwork
 * @param object $attempt this attempt just finished
 * @param object $context the hwork context
 * @param object $cm the coursemodule for this hwork
 *
 * @return bool true if all necessary messages were sent successfully, else false.
 */
function hwork_send_notification_messages($course, $hwork, $attempt, $context, $cm) {
    global $CFG, $DB;

    // Do nothing if required objects not present.
    if (empty($course) or empty($hwork) or empty($attempt) or empty($context)) {
        throw new coding_exception('$course, $hwork, $attempt, $context and $cm must all be set.');
    }

    $submitter = $DB->get_record('user', array('id' => $attempt->userid), '*', MUST_EXIST);

    // Check for confirmation required.
    $sendconfirm = false;
    $notifyexcludeusers = '';
    if (has_capability('mod/hwork:emailconfirmsubmission', $context, $submitter, false)) {
        $notifyexcludeusers = $submitter->id;
        $sendconfirm = true;
    }

    // Check for notifications required.
    $notifyfields = 'u.id, u.username, u.idnumber, u.email, u.emailstop, u.lang,
            u.timezone, u.mailformat, u.maildisplay, u.auth, u.suspended, u.deleted, ';
    $notifyfields .= get_all_user_name_fields(true, 'u');
    $groups = groups_get_all_groups($course->id, $submitter->id, $cm->groupingid);
    if (is_array($groups) && count($groups) > 0) {
        $groups = array_keys($groups);
    } else if (groups_get_activity_groupmode($cm, $course) != NOGROUPS) {
        // If the user is not in a group, and the hwork is set to group mode,
        // then set $groups to a non-existant id so that only users with
        // 'moodle/site:accessallgroups' get notified.
        $groups = -1;
    } else {
        $groups = '';
    }
    $userstonotify = get_users_by_capability($context, 'mod/hwork:emailnotifysubmission',
            $notifyfields, '', '', '', $groups, $notifyexcludeusers, false, false, true);

    if (empty($userstonotify) && !$sendconfirm) {
        return true; // Nothing to do.
    }

    $a = new stdClass();
    // Course info.
    $a->courseid        = $course->id;
    $a->coursename      = $course->fullname;
    $a->courseshortname = $course->shortname;
    // Quiz info.
    $a->hworkname        = $hwork->name;
    $a->hworkreporturl   = $CFG->wwwroot . '/mod/hwork/report.php?id=' . $cm->id;
    $a->hworkreportlink  = '<a href="' . $a->hworkreporturl . '">' .
            format_string($hwork->name) . ' report</a>';
    $a->hworkurl         = $CFG->wwwroot . '/mod/hwork/view.php?id=' . $cm->id;
    $a->hworklink        = '<a href="' . $a->hworkurl . '">' . format_string($hwork->name) . '</a>';
    // Attempt info.
    $a->submissiontime  = userdate($attempt->timefinish);
    $a->timetaken       = format_time($attempt->timefinish - $attempt->timestart);
    $a->hworkreviewurl   = $CFG->wwwroot . '/mod/hwork/review.php?attempt=' . $attempt->id;
    $a->hworkreviewlink  = '<a href="' . $a->hworkreviewurl . '">' .
            format_string($hwork->name) . ' review</a>';
    // Student who sat the hwork info.
    $a->studentidnumber = $submitter->idnumber;
    $a->studentname     = fullname($submitter);
    $a->studentusername = $submitter->username;

    $allok = true;

    // Send notifications if required.
    if (!empty($userstonotify)) {
        foreach ($userstonotify as $recipient) {
            $allok = $allok && hwork_send_notification($recipient, $submitter, $a);
        }
    }

    // Send confirmation if required. We send the student confirmation last, so
    // that if message sending is being intermittently buggy, which means we send
    // some but not all messages, and then try again later, then teachers may get
    // duplicate messages, but the student will always get exactly one.
    if ($sendconfirm) {
        $allok = $allok && hwork_send_confirmation($submitter, $a);
    }

    return $allok;
}

/**
 * Send the notification message when a hwork attempt becomes overdue.
 *
 * @param hwork_attempt $attemptobj all the data about the hwork attempt.
 */
function hwork_send_overdue_message($attemptobj) {
    global $CFG, $DB;

    $submitter = $DB->get_record('user', array('id' => $attemptobj->get_userid()), '*', MUST_EXIST);

    if (!$attemptobj->has_capability('mod/hwork:emailwarnoverdue', $submitter->id, false)) {
        return; // Message not required.
    }

    if (!$attemptobj->has_response_to_at_least_one_graded_question()) {
        return; // Message not required.
    }

    // Prepare lots of useful information that admins might want to include in
    // the email message.
    $hworkname = format_string($attemptobj->get_hwork_name());

    $deadlines = array();
    if ($attemptobj->get_hwork()->timelimit) {
        $deadlines[] = $attemptobj->get_attempt()->timestart + $attemptobj->get_hwork()->timelimit;
    }
    if ($attemptobj->get_hwork()->timeclose) {
        $deadlines[] = $attemptobj->get_hwork()->timeclose;
    }
    $duedate = min($deadlines);
    $graceend = $duedate + $attemptobj->get_hwork()->graceperiod;

    $a = new stdClass();
    // Course info.
    $a->courseid           = $attemptobj->get_course()->id;
    $a->coursename         = format_string($attemptobj->get_course()->fullname);
    $a->courseshortname    = format_string($attemptobj->get_course()->shortname);
    // Quiz info.
    $a->hworkname           = $hworkname;
    $a->hworkurl            = $attemptobj->view_url();
    $a->hworklink           = '<a href="' . $a->hworkurl . '">' . $hworkname . '</a>';
    // Attempt info.
    $a->attemptduedate     = userdate($duedate);
    $a->attemptgraceend    = userdate($graceend);
    $a->attemptsummaryurl  = $attemptobj->summary_url()->out(false);
    $a->attemptsummarylink = '<a href="' . $a->attemptsummaryurl . '">' . $hworkname . ' review</a>';
    // Student's info.
    $a->studentidnumber    = $submitter->idnumber;
    $a->studentname        = fullname($submitter);
    $a->studentusername    = $submitter->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->courseid          = $a->courseid;
    $eventdata->component         = 'mod_hwork';
    $eventdata->name              = 'attempt_overdue';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = core_user::get_noreply_user();
    $eventdata->userto            = $submitter;
    $eventdata->subject           = get_string('emailoverduesubject', 'hwork', $a);
    $eventdata->fullmessage       = get_string('emailoverduebody', 'hwork', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = get_string('emailoverduesmall', 'hwork', $a);
    $eventdata->contexturl        = $a->hworkurl;
    $eventdata->contexturlname    = $a->hworkname;

    // Send the message.
    return message_send($eventdata);
}

/**
 * Handle the hwork_attempt_submitted event.
 *
 * This sends the confirmation and notification messages, if required.
 *
 * @param object $event the event object.
 */
function hwork_attempt_submitted_handler($event) {
    global $DB;

    $course  = $DB->get_record('course', array('id' => $event->courseid));
    $attempt = $event->get_record_snapshot('hwork_attempts', $event->objectid);
    $hwork    = $event->get_record_snapshot('hwork', $attempt->hwork);
    $cm      = get_coursemodule_from_id('hwork', $event->get_context()->instanceid, $event->courseid);

    if (!($course && $hwork && $cm && $attempt)) {
        // Something has been deleted since the event was raised. Therefore, the
        // event is no longer relevant.
        return true;
    }

    // Update completion state.
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && ($hwork->completionattemptsexhausted || $hwork->completionpass)) {
        $completion->update_state($cm, COMPLETION_COMPLETE, $event->userid);
    }
    return hwork_send_notification_messages($course, $hwork, $attempt,
            context_module::instance($cm->id), $cm);
}

/**
 * Handle groups_member_added event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_hwork\group_observers::group_member_added()}.
 */
function hwork_groups_member_added_handler($event) {
    debugging('hwork_groups_member_added_handler() is deprecated, please use ' .
        '\mod_hwork\group_observers::group_member_added() instead.', DEBUG_DEVELOPER);
    hwork_update_open_attempts(array('userid'=>$event->userid, 'groupid'=>$event->groupid));
}

/**
 * Handle groups_member_removed event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_hwork\group_observers::group_member_removed()}.
 */
function hwork_groups_member_removed_handler($event) {
    debugging('hwork_groups_member_removed_handler() is deprecated, please use ' .
        '\mod_hwork\group_observers::group_member_removed() instead.', DEBUG_DEVELOPER);
    hwork_update_open_attempts(array('userid'=>$event->userid, 'groupid'=>$event->groupid));
}

/**
 * Handle groups_group_deleted event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_hwork\group_observers::group_deleted()}.
 */
function hwork_groups_group_deleted_handler($event) {
    global $DB;
    debugging('hwork_groups_group_deleted_handler() is deprecated, please use ' .
        '\mod_hwork\group_observers::group_deleted() instead.', DEBUG_DEVELOPER);
    hwork_process_group_deleted_in_course($event->courseid);
}

/**
 * Logic to happen when a/some group(s) has/have been deleted in a course.
 *
 * @param int $courseid The course ID.
 * @return void
 */
function hwork_process_group_deleted_in_course($courseid) {
    global $DB;

    // It would be nice if we got the groupid that was deleted.
    // Instead, we just update all hworkzes with orphaned group overrides.
    $sql = "SELECT o.id, o.hwork
              FROM {hwork_overrides} o
              JOIN {hwork} hwork ON hwork.id = o.hwork
         LEFT JOIN {groups} grp ON grp.id = o.groupid
             WHERE hwork.course = :courseid
               AND o.groupid IS NOT NULL
               AND grp.id IS NULL";
    $params = array('courseid' => $courseid);
    $records = $DB->get_records_sql_menu($sql, $params);
    if (!$records) {
        return; // Nothing to do.
    }
    $DB->delete_records_list('hwork_overrides', 'id', array_keys($records));
    hwork_update_open_attempts(array('hworkid' => array_unique(array_values($records))));
}

/**
 * Handle groups_members_removed event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_hwork\group_observers::group_member_removed()}.
 */
function hwork_groups_members_removed_handler($event) {
    debugging('hwork_groups_members_removed_handler() is deprecated, please use ' .
        '\mod_hwork\group_observers::group_member_removed() instead.', DEBUG_DEVELOPER);
    if ($event->userid == 0) {
        hwork_update_open_attempts(array('courseid'=>$event->courseid));
    } else {
        hwork_update_open_attempts(array('courseid'=>$event->courseid, 'userid'=>$event->userid));
    }
}

/**
 * Get the information about the standard hwork JavaScript module.
 * @return array a standard jsmodule structure.
 */
function hwork_get_js_module() {
    global $PAGE;

    return array(
        'name' => 'mod_hwork',
        'fullpath' => '/mod/hwork/module.js',
        'requires' => array('base', 'dom', 'event-delegate', 'event-key',
                'core_question_engine', 'moodle-core-formchangechecker'),
        'strings' => array(
            array('cancel', 'moodle'),
            array('flagged', 'question'),
            array('functiondisabledbysecuremode', 'hwork'),
            array('startattempt', 'hwork'),
            array('timesup', 'hwork'),
            array('changesmadereallygoaway', 'moodle'),
        ),
    );
}


/**
 * An extension of question_display_options that includes the extra options used
 * by the hwork.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_hwork_display_options extends question_display_options {
    /**#@+
     * @var integer bits used to indicate various times in relation to a
     * hwork attempt.
     */
    const DURING =            0x10000;
    const IMMEDIATELY_AFTER = 0x01000;
    const LATER_WHILE_OPEN =  0x00100;
    const AFTER_CLOSE =       0x00010;
    /**#@-*/

    /**
     * @var boolean if this is false, then the student is not allowed to review
     * anything about the attempt.
     */
    public $attempt = true;

    /**
     * @var boolean if this is false, then the student is not allowed to review
     * anything about the attempt.
     */
    public $overallfeedback = self::VISIBLE;

    /**
     * Set up the various options from the hwork settings, and a time constant.
     * @param object $hwork the hwork settings.
     * @param int $one of the {@link DURING}, {@link IMMEDIATELY_AFTER},
     * {@link LATER_WHILE_OPEN} or {@link AFTER_CLOSE} constants.
     * @return mod_hwork_display_options set up appropriately.
     */
    public static function make_from_hwork($hwork, $when) {
        $options = new self();

        $options->attempt = self::extract($hwork->reviewattempt, $when, true, false);
        $options->correctness = self::extract($hwork->reviewcorrectness, $when);
        $options->marks = self::extract($hwork->reviewmarks, $when,
                self::MARK_AND_MAX, self::MAX_ONLY);
        $options->feedback = self::extract($hwork->reviewspecificfeedback, $when);
        $options->generalfeedback = self::extract($hwork->reviewgeneralfeedback, $when);
        $options->rightanswer = self::extract($hwork->reviewrightanswer, $when);
        $options->overallfeedback = self::extract($hwork->reviewoverallfeedback, $when);

        $options->numpartscorrect = $options->feedback;
        $options->manualcomment = $options->feedback;

        if ($hwork->questiondecimalpoints != -1) {
            $options->markdp = $hwork->questiondecimalpoints;
        } else {
            $options->markdp = $hwork->decimalpoints;
        }

        return $options;
    }

    protected static function extract($bitmask, $bit,
            $whenset = self::VISIBLE, $whennotset = self::HIDDEN) {
        if ($bitmask & $bit) {
            return $whenset;
        } else {
            return $whennotset;
        }
    }
}

/**
 * A {@link qubaid_condition} for finding all the question usages belonging to
 * a particular hwork.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qubaids_for_hwork extends qubaid_join {
    public function __construct($hworkid, $includepreviews = true, $onlyfinished = false) {
        $where = 'hworka.hwork = :hworkahwork';
        $params = array('hworkahwork' => $hworkid);

        if (!$includepreviews) {
            $where .= ' AND preview = 0';
        }

        if ($onlyfinished) {
            $where .= ' AND state = :statefinished';
            $params['statefinished'] = hwork_attempt::FINISHED;
        }

        parent::__construct('{hwork_attempts} hworka', 'hworka.uniqueid', $where, $params);
    }
}

/**
 * A {@link qubaid_condition} for finding all the question usages belonging to a particular user and hwork combination.
 *
 * @copyright  2018 Andrew Nicols <andrwe@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qubaids_for_hwork_user extends qubaid_join {
    /**
     * Constructor for this qubaid.
     *
     * @param   int     $hworkid The hwork to search.
     * @param   int     $userid The user to filter on
     * @param   bool    $includepreviews Whether to include preview attempts
     * @param   bool    $onlyfinished Whether to only include finished attempts or not
     */
    public function __construct($hworkid, $userid, $includepreviews = true, $onlyfinished = false) {
        $where = 'hworka.hwork = :hworkahwork AND hworka.userid = :hworkauserid';
        $params = [
            'hworkahwork' => $hworkid,
            'hworkauserid' => $userid,
        ];

        if (!$includepreviews) {
            $where .= ' AND preview = 0';
        }

        if ($onlyfinished) {
            $where .= ' AND state = :statefinished';
            $params['statefinished'] = hwork_attempt::FINISHED;
        }

        parent::__construct('{hwork_attempts} hworka', 'hworka.uniqueid', $where, $params);
    }
}

/**
 * Creates a textual representation of a question for display.
 *
 * @param object $question A question object from the database questions table
 * @param bool $showicon If true, show the question's icon with the question. False by default.
 * @param bool $showquestiontext If true (default), show question text after question name.
 *       If false, show only question name.
 * @return string
 */
function hwork_question_tostring($question, $showicon = false, $showquestiontext = true) {
    $result = '';

    $name = shorten_text(format_string($question->name), 200);
    if ($showicon) {
        $name .= print_question_icon($question) . ' ' . $name;
    }
    $result .= html_writer::span($name, 'questionname');

    if ($showquestiontext) {
        $questiontext = question_utils::to_plain_text($question->questiontext,
                $question->questiontextformat, array('noclean' => true, 'para' => false));
        $questiontext = shorten_text($questiontext, 200);
        if ($questiontext) {
            $result .= ' ' . html_writer::span(s($questiontext), 'questiontext');
        }
    }

    return $result;
}

/**
 * Verify that the question exists, and the user has permission to use it.
 * Does not return. Throws an exception if the question cannot be used.
 * @param int $questionid The id of the question.
 */
function hwork_require_question_use($questionid) {
    global $DB;
    $question = $DB->get_record('question', array('id' => $questionid), '*', MUST_EXIST);
    question_require_capability_on($question, 'use');
}

/**
 * Verify that the question exists, and the user has permission to use it.
 * @param object $hwork the hwork settings.
 * @param int $slot which question in the hwork to test.
 * @return bool whether the user can use this question.
 */
function hwork_has_question_use($hwork, $slot) {
    global $DB;
    $question = $DB->get_record_sql("
            SELECT q.*
              FROM {hwork_slots} slot
              JOIN {question} q ON q.id = slot.questionid
             WHERE slot.hworkid = ? AND slot.slot = ?", array($hwork->id, $slot));
    if (!$question) {
        return false;
    }
    return question_has_capability_on($question, 'use');
}

/**
 * Add a question to a hwork
 *
 * Adds a question to a hwork by updating $hwork as well as the
 * hwork and hwork_slots tables. It also adds a page break if required.
 * @param int $questionid The id of the question to be added
 * @param object $hwork The extended hwork object as used by edit.php
 *      This is updated by this function
 * @param int $page Which page in hwork to add the question on. If 0 (default),
 *      add at the end
 * @param float $maxmark The maximum mark to set for this question. (Optional,
 *      defaults to question.defaultmark.
 * @return bool false if the question was already in the hwork
 */
function hwork_add_hwork_question($questionid, $hwork, $page = 0, $maxmark = null) {
    global $DB;

    // Make sue the question is not of the "random" type.
    $questiontype = $DB->get_field('question', 'qtype', array('id' => $questionid));
    if ($questiontype == 'random') {
        throw new coding_exception(
                'Adding "random" questions via hwork_add_hwork_question() is deprecated. Please use hwork_add_random_questions().'
        );
    }

    $slots = $DB->get_records('hwork_slots', array('hworkid' => $hwork->id),
            'slot', 'questionid, slot, page, id');
    if (array_key_exists($questionid, $slots)) {
        return false;
    }

    $trans = $DB->start_delegated_transaction();

    $maxpage = 1;
    $numonlastpage = 0;
    foreach ($slots as $slot) {
        if ($slot->page > $maxpage) {
            $maxpage = $slot->page;
            $numonlastpage = 1;
        } else {
            $numonlastpage += 1;
        }
    }

    // Add the new question instance.
    $slot = new stdClass();
    $slot->hworkid = $hwork->id;
    $slot->questionid = $questionid;

    if ($maxmark !== null) {
        $slot->maxmark = $maxmark;
    } else {
        $slot->maxmark = $DB->get_field('question', 'defaultmark', array('id' => $questionid));
    }

    if (is_int($page) && $page >= 1) {
        // Adding on a given page.
        $lastslotbefore = 0;
        foreach (array_reverse($slots) as $otherslot) {
            if ($otherslot->page > $page) {
                $DB->set_field('hwork_slots', 'slot', $otherslot->slot + 1, array('id' => $otherslot->id));
            } else {
                $lastslotbefore = $otherslot->slot;
                break;
            }
        }
        $slot->slot = $lastslotbefore + 1;
        $slot->page = min($page, $maxpage + 1);

        hwork_update_section_firstslots($hwork->id, 1, max($lastslotbefore, 1));

    } else {
        $lastslot = end($slots);
        if ($lastslot) {
            $slot->slot = $lastslot->slot + 1;
        } else {
            $slot->slot = 1;
        }
        if ($hwork->questionsperpage && $numonlastpage >= $hwork->questionsperpage) {
            $slot->page = $maxpage + 1;
        } else {
            $slot->page = $maxpage;
        }
    }

    $DB->insert_record('hwork_slots', $slot);
    $trans->allow_commit();
}

/**
 * Move all the section headings in a certain slot range by a certain offset.
 *
 * @param int $hworkid the id of a hwork
 * @param int $direction amount to adjust section heading positions. Normally +1 or -1.
 * @param int $afterslot adjust headings that start after this slot.
 * @param int|null $beforeslot optionally, only adjust headings before this slot.
 */
function hwork_update_section_firstslots($hworkid, $direction, $afterslot, $beforeslot = null) {
    global $DB;
    $where = 'hworkid = ? AND firstslot > ?';
    $params = [$direction, $hworkid, $afterslot];
    if ($beforeslot) {
        $where .= ' AND firstslot < ?';
        $params[] = $beforeslot;
    }
    $firstslotschanges = $DB->get_records_select_menu('hwork_sections',
            $where, $params, '', 'firstslot, firstslot + ?');
    update_field_with_unique_index('hwork_sections', 'firstslot', $firstslotschanges, ['hworkid' => $hworkid]);
}

/**
 * Add a random question to the hwork at a given point.
 * @param stdClass $hwork the hwork settings.
 * @param int $addonpage the page on which to add the question.
 * @param int $categoryid the question category to add the question from.
 * @param int $number the number of random questions to add.
 * @param bool $includesubcategories whether to include questoins from subcategories.
 * @param int[] $tagids Array of tagids. The question that will be picked randomly should be tagged with all these tags.
 */
function hwork_add_random_questions($hwork, $addonpage, $categoryid, $number,
        $includesubcategories, $tagids = []) {
    global $DB;

    $category = $DB->get_record('question_categories', array('id' => $categoryid));
    if (!$category) {
        print_error('invalidcategoryid', 'error');
    }

    $catcontext = context::instance_by_id($category->contextid);
    require_capability('moodle/question:useall', $catcontext);

    $tags = \core_tag_tag::get_bulk($tagids, 'id, name');
    $tagstrings = [];
    foreach ($tags as $tag) {
        $tagstrings[] = "{$tag->id},{$tag->name}";
    }

    // Find existing random questions in this category that are
    // not used by any hwork.
    $existingquestions = $DB->get_records_sql(
        "SELECT q.id, q.qtype FROM {question} q
        WHERE qtype = 'random'
            AND category = ?
            AND " . $DB->sql_compare_text('questiontext') . " = ?
            AND NOT EXISTS (
                    SELECT *
                      FROM {hwork_slots}
                     WHERE questionid = q.id)
        ORDER BY id", array($category->id, $includesubcategories ? '1' : '0'));

    for ($i = 0; $i < $number; $i++) {
        // Take as many of orphaned "random" questions as needed.
        if (!$question = array_shift($existingquestions)) {
            $form = new stdClass();
            $form->category = $category->id . ',' . $category->contextid;
            $form->includesubcategories = $includesubcategories;
            $form->fromtags = $tagstrings;
            $form->defaultmark = 1;
            $form->hidden = 1;
            $form->stamp = make_unique_id_code(); // Set the unique code (not to be changed).
            $question = new stdClass();
            $question->qtype = 'random';
            $question = question_bank::get_qtype('random')->save_question($question, $form);
            if (!isset($question->id)) {
                print_error('cannotinsertrandomquestion', 'hwork');
            }
        }

        $randomslotdata = new stdClass();
        $randomslotdata->hworkid = $hwork->id;
        $randomslotdata->questionid = $question->id;
        $randomslotdata->questioncategoryid = $categoryid;
        $randomslotdata->includingsubcategories = $includesubcategories ? 1 : 0;
        $randomslotdata->maxmark = 1;

        $randomslot = new \mod_hwork\local\structure\slot_random($randomslotdata);
        $randomslot->set_hwork($hwork);
        $randomslot->set_tags($tags);
        $randomslot->insert($addonpage);
    }
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $hwork       hwork object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.1
 */
function hwork_view($hwork, $course, $cm, $context) {

    $params = array(
        'objectid' => $hwork->id,
        'context' => $context
    );

    $event = \mod_hwork\event\course_module_viewed::create($params);
    $event->add_record_snapshot('hwork', $hwork);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Validate permissions for creating a new attempt and start a new preview attempt if required.
 *
 * @param  hwork $hworkobj hwork object
 * @param  hwork_access_manager $accessmanager hwork access manager
 * @param  bool $forcenew whether was required to start a new preview attempt
 * @param  int $page page to jump to in the attempt
 * @param  bool $redirect whether to redirect or throw exceptions (for web or ws usage)
 * @return array an array containing the attempt information, access error messages and the page to jump to in the attempt
 * @throws moodle_hwork_exception
 * @since Moodle 3.1
 */
function hwork_validate_new_attempt(hwork $hworkobj, hwork_access_manager $accessmanager, $forcenew, $page, $redirect) {
    global $DB, $USER;
    $timenow = time();

    if ($hworkobj->is_preview_user() && $forcenew) {
        $accessmanager->current_attempt_finished();
    }

    // Check capabilities.
    if (!$hworkobj->is_preview_user()) {
        $hworkobj->require_capability('mod/hwork:attempt');
    }

    // Check to see if a new preview was requested.
    if ($hworkobj->is_preview_user() && $forcenew) {
        // To force the creation of a new preview, we mark the current attempt (if any)
        // as finished. It will then automatically be deleted below.
        $DB->set_field('hwork_attempts', 'state', hwork_attempt::FINISHED,
                array('hwork' => $hworkobj->get_hworkid(), 'userid' => $USER->id));
    }

    // Look for an existing attempt.
    $attempts = hwork_get_user_attempts($hworkobj->get_hworkid(), $USER->id, 'all', true);
    $lastattempt = end($attempts);

    $attemptnumber = null;
    // If an in-progress attempt exists, check password then redirect to it.
    if ($lastattempt && ($lastattempt->state == hwork_attempt::IN_PROGRESS ||
            $lastattempt->state == hwork_attempt::OVERDUE)) {
        $currentattemptid = $lastattempt->id;
        $messages = $accessmanager->prevent_access();

        // If the attempt is now overdue, deal with that.
        $hworkobj->create_attempt_object($lastattempt)->handle_if_time_expired($timenow, true);

        // And, if the attempt is now no longer in progress, redirect to the appropriate place.
        if ($lastattempt->state == hwork_attempt::ABANDONED || $lastattempt->state == hwork_attempt::FINISHED) {
            if ($redirect) {
                redirect($hworkobj->review_url($lastattempt->id));
            } else {
                throw new moodle_hwork_exception($hworkobj, 'attemptalreadyclosed');
            }
        }

        // If the page number was not explicitly in the URL, go to the current page.
        if ($page == -1) {
            $page = $lastattempt->currentpage;
        }

    } else {
        while ($lastattempt && $lastattempt->preview) {
            $lastattempt = array_pop($attempts);
        }

        // Get number for the next or unfinished attempt.
        if ($lastattempt) {
            $attemptnumber = $lastattempt->attempt + 1;
        } else {
            $lastattempt = false;
            $attemptnumber = 1;
        }
        $currentattemptid = null;

        $messages = $accessmanager->prevent_access() +
            $accessmanager->prevent_new_attempt(count($attempts), $lastattempt);

        if ($page == -1) {
            $page = 0;
        }
    }
    return array($currentattemptid, $attemptnumber, $lastattempt, $messages, $page);
}

/**
 * Prepare and start a new attempt deleting the previous preview attempts.
 *
 * @param  hwork $hworkobj hwork object
 * @param  int $attemptnumber the attempt number
 * @param  object $lastattempt last attempt object
 * @param bool $offlineattempt whether is an offline attempt or not
 * @return object the new attempt
 * @since  Moodle 3.1
 */
function hwork_prepare_and_start_new_attempt(hwork $hworkobj, $attemptnumber, $lastattempt, $offlineattempt = false) {
    global $DB, $USER;

    // Delete any previous preview attempts belonging to this user.
    hwork_delete_previews($hworkobj->get_hwork(), $USER->id);

    $quba = question_engine::make_questions_usage_by_activity('mod_hwork', $hworkobj->get_context());
    $quba->set_preferred_behaviour($hworkobj->get_hwork()->preferredbehaviour);

    // Create the new attempt and initialize the question sessions
    $timenow = time(); // Update time now, in case the server is running really slowly.
    $attempt = hwork_create_attempt($hworkobj, $attemptnumber, $lastattempt, $timenow, $hworkobj->is_preview_user());

    if (!($hworkobj->get_hwork()->attemptonlast && $lastattempt)) {
        $attempt = hwork_start_new_attempt($hworkobj, $quba, $attempt, $attemptnumber, $timenow);
    } else {
        $attempt = hwork_start_attempt_built_on_last($quba, $attempt, $lastattempt);
    }

    $transaction = $DB->start_delegated_transaction();

    // Init the timemodifiedoffline for offline attempts.
    if ($offlineattempt) {
        $attempt->timemodifiedoffline = $attempt->timemodified;
    }
    $attempt = hwork_attempt_save_started($hworkobj, $quba, $attempt);

    $transaction->allow_commit();

    return $attempt;
}

/**
 * Check if the given calendar_event is either a user or group override
 * event for hwork.
 *
 * @param calendar_event $event The calendar event to check
 * @return bool
 */
function hwork_is_overriden_calendar_event(\calendar_event $event) {
    global $DB;

    if (!isset($event->modulename)) {
        return false;
    }

    if ($event->modulename != 'hwork') {
        return false;
    }

    if (!isset($event->instance)) {
        return false;
    }

    if (!isset($event->userid) && !isset($event->groupid)) {
        return false;
    }

    $overrideparams = [
        'hwork' => $event->instance
    ];

    if (isset($event->groupid)) {
        $overrideparams['groupid'] = $event->groupid;
    } else if (isset($event->userid)) {
        $overrideparams['userid'] = $event->userid;
    }

    return $DB->record_exists('hwork_overrides', $overrideparams);
}

/**
 * Retrieves tag information for the given list of hwork slot ids.
 * Currently the only slots that have tags are random question slots.
 *
 * Example:
 * If we have 3 slots with id 1, 2, and 3. The first slot has two tags, the second
 * has one tag, and the third has zero tags. The return structure will look like:
 * [
 *      1 => [
 *          { ...tag data... },
 *          { ...tag data... },
 *      ],
 *      2 => [
 *          { ...tag data... }
 *      ],
 *      3 => []
 * ]
 *
 * @param int[] $slotids The list of id for the hwork slots.
 * @return array[] List of hwork_slot_tags records indexed by slot id.
 */
function hwork_retrieve_tags_for_slot_ids($slotids) {
    global $DB;

    if (empty($slotids)) {
        return [];
    }

    $slottags = $DB->get_records_list('hwork_slot_tags', 'slotid', $slotids);
    $tagsbyid = core_tag_tag::get_bulk(array_filter(array_column($slottags, 'tagid')), 'id, name');
    $tagsbyname = false; // It will be loaded later if required.
    $emptytagids = array_reduce($slotids, function($carry, $slotid) {
        $carry[$slotid] = [];
        return $carry;
    }, []);

    return array_reduce(
        $slottags,
        function($carry, $slottag) use ($slottags, $tagsbyid, $tagsbyname) {
            if (isset($tagsbyid[$slottag->tagid])) {
                // Make sure that we're returning the most updated tag name.
                $slottag->tagname = $tagsbyid[$slottag->tagid]->name;
            } else {
                if ($tagsbyname === false) {
                    // We were hoping that this query could be avoided, but life
                    // showed its other side to us!
                    $tagcollid = core_tag_area::get_collection('core', 'question');
                    $tagsbyname = core_tag_tag::get_by_name_bulk(
                        $tagcollid,
                        array_column($slottags, 'tagname'),
                        'id, name'
                    );
                }
                if (isset($tagsbyname[$slottag->tagname])) {
                    // Make sure that we're returning the current tag id that matches
                    // the given tag name.
                    $slottag->tagid = $tagsbyname[$slottag->tagname]->id;
                } else {
                    // The tag does not exist anymore (neither the tag id nor the tag name
                    // matches an existing tag).
                    // We still need to include this row in the result as some callers might
                    // be interested in these rows. An example is the editing forms that still
                    // need to display tag names even if they don't exist anymore.
                    $slottag->tagid = null;
                }
            }

            $carry[$slottag->slotid][] = $slottag;
            return $carry;
        },
        $emptytagids
    );
}

/**
 * Retrieves tag information for the given hwork slot.
 * A hwork slot have some tags if and only if it is representing a random question by tags.
 *
 * @param int $slotid The id of the hwork slot.
 * @return stdClass[] List of hwork_slot_tags records.
 */
function hwork_retrieve_slot_tags($slotid) {
    $slottags = hwork_retrieve_tags_for_slot_ids([$slotid]);
    return $slottags[$slotid];
}

/**
 * Retrieves tag ids for the given hwork slot.
 * A hwork slot have some tags if and only if it is representing a random question by tags.
 *
 * @param int $slotid The id of the hwork slot.
 * @return int[]
 */
function hwork_retrieve_slot_tag_ids($slotid) {
    $tags = hwork_retrieve_slot_tags($slotid);

    // Only work with tags that exist.
    return array_filter(array_column($tags, 'tagid'));
}

/**
 * Get hwork attempt and handling error.
 *
 * @param int $attemptid the id of the current attempt.
 * @param int|null $cmid the course_module id for this hwork.
 * @return hwork_attempt $attemptobj all the data about the hwork attempt.
 * @throws moodle_exception
 */
function hwork_create_attempt_handling_errors($attemptid, $cmid = null) {
    try {
        $attempobj = hwork_attempt::create($attemptid);
    } catch (moodle_exception $e) {
        if (!empty($cmid)) {
            list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'hwork');
            $continuelink = new moodle_url('/mod/hwork/view.php', array('id' => $cmid));
            $context = context_module::instance($cm->id);
            if (has_capability('mod/hwork:preview', $context)) {
                throw new moodle_exception('attempterrorcontentchange', 'hwork', $continuelink);
            } else {
                throw new moodle_exception('attempterrorcontentchangeforuser', 'hwork', $continuelink);
            }
        } else {
            throw new moodle_exception('attempterrorinvalid', 'hwork');
        }
    }
    if (!empty($cmid) && $attempobj->get_cmid() != $cmid) {
        throw new moodle_exception('invalidcoursemodule');
    } else {
        return $attempobj;
    }
}
