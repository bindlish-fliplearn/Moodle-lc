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
 * Library of functions used by the clatest module.
 *
 * This contains functions that are called from within the clatest module only
 * Functions that are also called by core Moodle are in {@link lib.php}
 * This script also loads the code in {@link questionlib.php} which holds
 * the module-indpendent code for handling questions and which in turn
 * initialises all the questiontype classes.
 *
 * @package    mod_clatest
 * @copyright  1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/clatest/lib.php');
require_once($CFG->dirroot . '/mod/clatest/accessmanager.php');
require_once($CFG->dirroot . '/mod/clatest/accessmanager_form.php');
require_once($CFG->dirroot . '/mod/clatest/renderer.php');
require_once($CFG->dirroot . '/mod/clatest/attemptlib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/questionlib.php');


/**
 * @var int We show the countdown timer if there is less than this amount of time left before the
 * the clatest close date. (1 hour)
 */
define('QUIZ_SHOW_TIME_BEFORE_DEADLINE', '3600');

/**
 * @var int If there are fewer than this many seconds left when the student submits
 * a page of the clatest, then do not take them to the next page of the clatest. Instead
 * close the clatest immediately.
 */
define('QUIZ_MIN_TIME_TO_CONTINUE', '2');

/**
 * @var int We show no image when user selects No image from dropdown menu in clatest settings.
 */
define('QUIZ_SHOWIMAGE_NONE', 0);

/**
 * @var int We show small image when user selects small image from dropdown menu in clatest settings.
 */
define('QUIZ_SHOWIMAGE_SMALL', 1);

/**
 * @var int We show Large image when user selects Large image from dropdown menu in clatest settings.
 */
define('QUIZ_SHOWIMAGE_LARGE', 2);


// Functions related to attempts ///////////////////////////////////////////////

/**
 * Creates an object to represent a new attempt at a clatest
 *
 * Creates an attempt object to represent an attempt at the clatest by the current
 * user starting at the current time. The ->id field is not set. The object is
 * NOT written to the database.
 *
 * @param object $clatestobj the clatest object to create an attempt for.
 * @param int $attemptnumber the sequence number for the attempt.
 * @param object $lastattempt the previous attempt by this user, if any. Only needed
 *         if $attemptnumber > 1 and $clatest->attemptonlast is true.
 * @param int $timenow the time the attempt was started at.
 * @param bool $ispreview whether this new attempt is a preview.
 * @param int $userid  the id of the user attempting this clatest.
 *
 * @return object the newly created attempt object.
 */
function clatest_create_attempt(clatest $clatestobj, $attemptnumber, $lastattempt, $timenow, $ispreview = false, $userid = null) {
    global $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $clatest = $clatestobj->get_clatest();
    if ($clatest->sumgrades < 0.000005 && $clatest->grade > 0.000005) {
        throw new moodle_exception('cannotstartgradesmismatch', 'clatest',
                new moodle_url('/mod/clatest/view.php', array('q' => $clatest->id)),
                    array('grade' => clatest_format_grade($clatest, $clatest->grade)));
    }

    if ($attemptnumber == 1 || !$clatest->attemptonlast) {
        // We are not building on last attempt so create a new attempt.
        $attempt = new stdClass();
        $attempt->clatest = $clatest->id;
        $attempt->userid = $userid;
        $attempt->preview = 0;
        $attempt->layout = '';
    } else {
        // Build on last attempt.
        if (empty($lastattempt)) {
            print_error('cannotfindprevattempt', 'clatest');
        }
        $attempt = $lastattempt;
    }

    $attempt->attempt = $attemptnumber;
    $attempt->timestart = $timenow;
    $attempt->timefinish = 0;
    $attempt->timemodified = $timenow;
    $attempt->timemodifiedoffline = 0;
    $attempt->state = clatest_attempt::IN_PROGRESS;
    $attempt->currentpage = 0;
    $attempt->sumgrades = null;

    // If this is a preview, mark it as such.
    if ($ispreview) {
        $attempt->preview = 1;
    }

    $timeclose = $clatestobj->get_access_manager($timenow)->get_end_time($attempt);
    if ($timeclose === false || $ispreview) {
        $attempt->timecheckstate = null;
    } else {
        $attempt->timecheckstate = $timeclose;
    }

    return $attempt;
}
/**
 * Start a normal, new, clatest attempt.
 *
 * @param clatest      $clatestobj            the clatest object to start an attempt for.
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
function clatest_start_new_attempt($clatestobj, $quba, $attempt, $attemptnumber, $timenow,
                                $questionids = array(), $forcedvariantsbyslot = array()) {

    // Usages for this user's previous clatest attempts.
    $qubaids = new \mod_clatest\question\qubaids_for_users_attempts(
            $clatestobj->get_clatestid(), $attempt->userid);

    // Fully load all the questions in this clatest.
    $clatestobj->preload_questions();
    $clatestobj->load_questions();

    // First load all the non-random questions.
    $randomfound = false;
    $slot = 0;
    $questions = array();
    $maxmark = array();
    $page = array();
    foreach ($clatestobj->get_questions() as $questiondata) {
        $slot += 1;
        $maxmark[$slot] = $questiondata->maxmark;
        $page[$slot] = $questiondata->page;
        if ($questiondata->qtype == 'random') {
            $randomfound = true;
            continue;
        }
        if (!$clatestobj->get_clatest()->shuffleanswers) {
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

        foreach ($clatestobj->get_questions() as $questiondata) {
            $slot += 1;
            if ($questiondata->qtype != 'random') {
                continue;
            }

            $tagids = clatest_retrieve_slot_tag_ids($questiondata->slotid);

            // Deal with fixed random choices for testing.
            if (isset($questionids[$quba->next_slot_number()])) {
                if ($randomloader->is_question_available($questiondata->category,
                        (bool) $questiondata->questiontext, $questionids[$quba->next_slot_number()], $tagids)) {
                    $questions[$slot] = question_bank::load_question(
                            $questionids[$quba->next_slot_number()], $clatestobj->get_clatest()->shuffleanswers);
                    continue;
                } else {
                    throw new coding_exception('Forced question id not available.');
                }
            }

            // Normal case, pick one at random.
            $questionid = $randomloader->get_next_question_id($questiondata->randomfromcategory,
                    $questiondata->randomincludingsubcategories, $tagids);
            if ($questionid === null) {
                throw new moodle_exception('notenoughrandomquestions', 'clatest',
                                           $clatestobj->view_url(), $questiondata);
            }

            $questions[$slot] = question_bank::load_question($questionid,
                    $clatestobj->get_clatest()->shuffleanswers);
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
    $sections = $clatestobj->get_sections();
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
                if ($questionsonthispage && $questionsonthispage == $clatestobj->get_clatest()->questionsperpage) {
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
function clatest_start_attempt_built_on_last($quba, $attempt, $lastattempt) {
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
 * The save started question usage and clatest attempt in db and log the started attempt.
 *
 * @param clatest                       $clatestobj
 * @param question_usage_by_activity $quba
 * @param object                     $attempt
 * @return object                    attempt object with uniqueid and id set.
 */
function clatest_attempt_save_started($clatestobj, $quba, $attempt) {
    global $DB;
    // Save the attempt in the database.
    question_engine::save_questions_usage_by_activity($quba);
    $attempt->uniqueid = $quba->get_id();
    $attempt->id = $DB->insert_record('clatest_attempts', $attempt);

    // Params used by the events below.
    $params = array(
        'objectid' => $attempt->id,
        'relateduserid' => $attempt->userid,
        'courseid' => $clatestobj->get_courseid(),
        'context' => $clatestobj->get_context()
    );
    // Decide which event we are using.
    if ($attempt->preview) {
        $params['other'] = array(
            'clatestid' => $clatestobj->get_clatestid()
        );
        $event = \mod_clatest\event\attempt_preview_started::create($params);
    } else {
        $event = \mod_clatest\event\attempt_started::create($params);

    }

    // Trigger the event.
    $event->add_record_snapshot('clatest', $clatestobj->get_clatest());
    $event->add_record_snapshot('clatest_attempts', $attempt);
    $event->trigger();

    return $attempt;
}

/**
 * Returns an unfinished attempt (if there is one) for the given
 * user on the given clatest. This function does not return preview attempts.
 *
 * @param int $clatestid the id of the clatest.
 * @param int $userid the id of the user.
 *
 * @return mixed the unfinished attempt if there is one, false if not.
 */
function clatest_get_user_attempt_unfinished($clatestid, $userid) {
    $attempts = clatest_get_user_attempts($clatestid, $userid, 'unfinished', true);
    if ($attempts) {
        return array_shift($attempts);
    } else {
        return false;
    }
}

/**
 * Delete a clatest attempt.
 * @param mixed $attempt an integer attempt id or an attempt object
 *      (row of the clatest_attempts table).
 * @param object $clatest the clatest object.
 */
function clatest_delete_attempt($attempt, $clatest) {
    global $DB;
    if (is_numeric($attempt)) {
        if (!$attempt = $DB->get_record('clatest_attempts', array('id' => $attempt))) {
            return;
        }
    }

    if ($attempt->clatest != $clatest->id) {
        debugging("Trying to delete attempt $attempt->id which belongs to clatest $attempt->clatest " .
                "but was passed clatest $clatest->id.");
        return;
    }

    if (!isset($clatest->cmid)) {
        $cm = get_coursemodule_from_instance('clatest', $clatest->id, $clatest->course);
        $clatest->cmid = $cm->id;
    }

    question_engine::delete_questions_usage_by_activity($attempt->uniqueid);
    $DB->delete_records('clatest_attempts', array('id' => $attempt->id));

    // Log the deletion of the attempt if not a preview.
    if (!$attempt->preview) {
        $params = array(
            'objectid' => $attempt->id,
            'relateduserid' => $attempt->userid,
            'context' => context_module::instance($clatest->cmid),
            'other' => array(
                'clatestid' => $clatest->id
            )
        );
        $event = \mod_clatest\event\attempt_deleted::create($params);
        $event->add_record_snapshot('clatest_attempts', $attempt);
        $event->trigger();
    }

    // Search clatest_attempts for other instances by this user.
    // If none, then delete record for this clatest, this user from clatest_grades
    // else recalculate best grade.
    $userid = $attempt->userid;
    if (!$DB->record_exists('clatest_attempts', array('userid' => $userid, 'clatest' => $clatest->id))) {
        $DB->delete_records('clatest_grades', array('userid' => $userid, 'clatest' => $clatest->id));
    } else {
        clatest_save_best_grade($clatest, $userid);
    }

    clatest_update_grades($clatest, $userid);
}

/**
 * Delete all the preview attempts at a clatest, or possibly all the attempts belonging
 * to one user.
 * @param object $clatest the clatest object.
 * @param int $userid (optional) if given, only delete the previews belonging to this user.
 */
function clatest_delete_previews($clatest, $userid = null) {
    global $DB;
    $conditions = array('clatest' => $clatest->id, 'preview' => 1);
    if (!empty($userid)) {
        $conditions['userid'] = $userid;
    }
    $previewattempts = $DB->get_records('clatest_attempts', $conditions);
    foreach ($previewattempts as $attempt) {
        clatest_delete_attempt($attempt, $clatest);
    }
}

/**
 * @param int $clatestid The clatest id.
 * @return bool whether this clatest has any (non-preview) attempts.
 */
function clatest_has_attempts($clatestid) {
    global $DB;
    return $DB->record_exists('clatest_attempts', array('clatest' => $clatestid, 'preview' => 0));
}

// Functions to do with clatest layout and pages //////////////////////////////////

/**
 * Repaginate the questions in a clatest
 * @param int $clatestid the id of the clatest to repaginate.
 * @param int $slotsperpage number of items to put on each page. 0 means unlimited.
 */
function clatest_repaginate_questions($clatestid, $slotsperpage) {
    global $DB;
    $trans = $DB->start_delegated_transaction();

    $sections = $DB->get_records('clatest_sections', array('clatestid' => $clatestid), 'firstslot ASC');
    $firstslots = array();
    foreach ($sections as $section) {
        if ((int)$section->firstslot === 1) {
            continue;
        }
        $firstslots[] = $section->firstslot;
    }

    $slots = $DB->get_records('clatest_slots', array('clatestid' => $clatestid),
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
            $DB->set_field('clatest_slots', 'page', $currentpage, array('id' => $slot->id));
        }
        $slotsonthispage += 1;
    }

    $trans->allow_commit();
}

// Functions to do with clatest grades ////////////////////////////////////////////

/**
 * Convert the raw grade stored in $attempt into a grade out of the maximum
 * grade for this clatest.
 *
 * @param float $rawgrade the unadjusted grade, fof example $attempt->sumgrades
 * @param object $clatest the clatest object. Only the fields grade, sumgrades and decimalpoints are used.
 * @param bool|string $format whether to format the results for display
 *      or 'question' to format a question grade (different number of decimal places.
 * @return float|string the rescaled grade, or null/the lang string 'notyetgraded'
 *      if the $grade is null.
 */
function clatest_rescale_grade($rawgrade, $clatest, $format = true) {
    if (is_null($rawgrade)) {
        $grade = null;
    } else if ($clatest->sumgrades >= 0.000005) {
        $grade = $rawgrade * $clatest->grade / $clatest->sumgrades;
    } else {
        $grade = 0;
    }
    if ($format === 'question') {
        $grade = clatest_format_question_grade($clatest, $grade);
    } else if ($format) {
        $grade = clatest_format_grade($clatest, $grade);
    }
    return $grade;
}

/**
 * Get the feedback object for this grade on this clatest.
 *
 * @param float $grade a grade on this clatest.
 * @param object $clatest the clatest settings.
 * @return false|stdClass the record object or false if there is not feedback for the given grade
 * @since  Moodle 3.1
 */
function clatest_feedback_record_for_grade($grade, $clatest) {
    global $DB;

    // With CBM etc, it is possible to get -ve grades, which would then not match
    // any feedback. Therefore, we replace -ve grades with 0.
    $grade = max($grade, 0);

    $feedback = $DB->get_record_select('clatest_feedback',
            'clatestid = ? AND mingrade <= ? AND ? < maxgrade', array($clatest->id, $grade, $grade));

    return $feedback;
}

/**
 * Get the feedback text that should be show to a student who
 * got this grade on this clatest. The feedback is processed ready for diplay.
 *
 * @param float $grade a grade on this clatest.
 * @param object $clatest the clatest settings.
 * @param object $context the clatest context.
 * @return string the comment that corresponds to this grade (empty string if there is not one.
 */
function clatest_feedback_for_grade($grade, $clatest, $context) {

    if (is_null($grade)) {
        return '';
    }

    $feedback = clatest_feedback_record_for_grade($grade, $clatest);

    if (empty($feedback->feedbacktext)) {
        return '';
    }

    // Clean the text, ready for display.
    $formatoptions = new stdClass();
    $formatoptions->noclean = true;
    $feedbacktext = file_rewrite_pluginfile_urls($feedback->feedbacktext, 'pluginfile.php',
            $context->id, 'mod_clatest', 'feedback', $feedback->id);
    $feedbacktext = format_text($feedbacktext, $feedback->feedbacktextformat, $formatoptions);

    return $feedbacktext;
}

/**
 * @param object $clatest the clatest database row.
 * @return bool Whether this clatest has any non-blank feedback text.
 */
function clatest_has_feedback($clatest) {
    global $DB;
    static $cache = array();
    if (!array_key_exists($clatest->id, $cache)) {
        $cache[$clatest->id] = clatest_has_grades($clatest) &&
                $DB->record_exists_select('clatest_feedback', "clatestid = ? AND " .
                    $DB->sql_isnotempty('clatest_feedback', 'feedbacktext', false, true),
                array($clatest->id));
    }
    return $cache[$clatest->id];
}

/**
 * Update the sumgrades field of the clatest. This needs to be called whenever
 * the grading structure of the clatest is changed. For example if a question is
 * added or removed, or a question weight is changed.
 *
 * You should call {@link clatest_delete_previews()} before you call this function.
 *
 * @param object $clatest a clatest.
 */
function clatest_update_sumgrades($clatest) {
    global $DB;

    $sql = 'UPDATE {clatest}
            SET sumgrades = COALESCE((
                SELECT SUM(maxmark)
                FROM {clatest_slots}
                WHERE clatestid = {clatest}.id
            ), 0)
            WHERE id = ?';
    $DB->execute($sql, array($clatest->id));
    $clatest->sumgrades = $DB->get_field('clatest', 'sumgrades', array('id' => $clatest->id));

    if ($clatest->sumgrades < 0.000005 && clatest_has_attempts($clatest->id)) {
        // If the clatest has been attempted, and the sumgrades has been
        // set to 0, then we must also set the maximum possible grade to 0, or
        // we will get a divide by zero error.
        clatest_set_grade(0, $clatest);
    }
}

/**
 * Update the sumgrades field of the attempts at a clatest.
 *
 * @param object $clatest a clatest.
 */
function clatest_update_all_attempt_sumgrades($clatest) {
    global $DB;
    $dm = new question_engine_data_mapper();
    $timenow = time();

    $sql = "UPDATE {clatest_attempts}
            SET
                timemodified = :timenow,
                sumgrades = (
                    {$dm->sum_usage_marks_subquery('uniqueid')}
                )
            WHERE clatest = :clatestid AND state = :finishedstate";
    $DB->execute($sql, array('timenow' => $timenow, 'clatestid' => $clatest->id,
            'finishedstate' => clatest_attempt::FINISHED));
}

/**
 * The clatest grade is the maximum that student's results are marked out of. When it
 * changes, the corresponding data in clatest_grades and clatest_feedback needs to be
 * rescaled. After calling this function, you probably need to call
 * clatest_update_all_attempt_sumgrades, clatest_update_all_final_grades and
 * clatest_update_grades.
 *
 * @param float $newgrade the new maximum grade for the clatest.
 * @param object $clatest the clatest we are updating. Passed by reference so its
 *      grade field can be updated too.
 * @return bool indicating success or failure.
 */
function clatest_set_grade($newgrade, $clatest) {
    global $DB;
    // This is potentially expensive, so only do it if necessary.
    if (abs($clatest->grade - $newgrade) < 1e-7) {
        // Nothing to do.
        return true;
    }

    $oldgrade = $clatest->grade;
    $clatest->grade = $newgrade;

    // Use a transaction, so that on those databases that support it, this is safer.
    $transaction = $DB->start_delegated_transaction();

    // Update the clatest table.
    $DB->set_field('clatest', 'grade', $newgrade, array('id' => $clatest->instance));

    if ($oldgrade < 1) {
        // If the old grade was zero, we cannot rescale, we have to recompute.
        // We also recompute if the old grade was too small to avoid underflow problems.
        clatest_update_all_final_grades($clatest);

    } else {
        // We can rescale the grades efficiently.
        $timemodified = time();
        $DB->execute("
                UPDATE {clatest_grades}
                SET grade = ? * grade, timemodified = ?
                WHERE clatest = ?
        ", array($newgrade/$oldgrade, $timemodified, $clatest->id));
    }

    if ($oldgrade > 1e-7) {
        // Update the clatest_feedback table.
        $factor = $newgrade/$oldgrade;
        $DB->execute("
                UPDATE {clatest_feedback}
                SET mingrade = ? * mingrade, maxgrade = ? * maxgrade
                WHERE clatestid = ?
        ", array($factor, $factor, $clatest->id));
    }

    // Update grade item and send all grades to gradebook.
    clatest_grade_item_update($clatest);
    clatest_update_grades($clatest);

    $transaction->allow_commit();
    return true;
}

/**
 * Save the overall grade for a user at a clatest in the clatest_grades table
 *
 * @param object $clatest The clatest for which the best grade is to be calculated and then saved.
 * @param int $userid The userid to calculate the grade for. Defaults to the current user.
 * @param array $attempts The attempts of this user. Useful if you are
 * looping through many users. Attempts can be fetched in one master query to
 * avoid repeated querying.
 * @return bool Indicates success or failure.
 */
function clatest_save_best_grade($clatest, $userid = null, $attempts = array()) {
    global $DB, $OUTPUT, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    if (!$attempts) {
        // Get all the attempts made by the user.
        $attempts = clatest_get_user_attempts($clatest->id, $userid);
    }

    // Calculate the best grade.
    $bestgrade = clatest_calculate_best_grade($clatest, $attempts);
    $bestgrade = clatest_rescale_grade($bestgrade, $clatest, false);

    // Save the best grade in the database.
    if (is_null($bestgrade)) {
        $DB->delete_records('clatest_grades', array('clatest' => $clatest->id, 'userid' => $userid));

    } else if ($grade = $DB->get_record('clatest_grades',
            array('clatest' => $clatest->id, 'userid' => $userid))) {
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->update_record('clatest_grades', $grade);

    } else {
        $grade = new stdClass();
        $grade->clatest = $clatest->id;
        $grade->userid = $userid;
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->insert_record('clatest_grades', $grade);
    }

    clatest_update_grades($clatest, $userid);
}

/**
 * Calculate the overall grade for a clatest given a number of attempts by a particular user.
 *
 * @param object $clatest    the clatest settings object.
 * @param array $attempts an array of all the user's attempts at this clatest in order.
 * @return float          the overall grade
 */
function clatest_calculate_best_grade($clatest, $attempts) {

    switch ($clatest->grademethod) {

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
 * Update the final grade at this clatest for all students.
 *
 * This function is equivalent to calling clatest_save_best_grade for all
 * users, but much more efficient.
 *
 * @param object $clatest the clatest settings.
 */
function clatest_update_all_final_grades($clatest) {
    global $DB;

    if (!$clatest->sumgrades) {
        return;
    }

    $param = array('iclatestid' => $clatest->id, 'istatefinished' => clatest_attempt::FINISHED);
    $firstlastattemptjoin = "JOIN (
            SELECT
                iclatesta.userid,
                MIN(attempt) AS firstattempt,
                MAX(attempt) AS lastattempt

            FROM {clatest_attempts} iclatesta

            WHERE
                iclatesta.state = :istatefinished AND
                iclatesta.preview = 0 AND
                iclatesta.clatest = :iclatestid

            GROUP BY iclatesta.userid
        ) first_last_attempts ON first_last_attempts.userid = clatesta.userid";

    switch ($clatest->grademethod) {
        case QUIZ_ATTEMPTFIRST:
            // Because of the where clause, there will only be one row, but we
            // must still use an aggregate function.
            $select = 'MAX(clatesta.sumgrades)';
            $join = $firstlastattemptjoin;
            $where = 'clatesta.attempt = first_last_attempts.firstattempt AND';
            break;

        case QUIZ_ATTEMPTLAST:
            // Because of the where clause, there will only be one row, but we
            // must still use an aggregate function.
            $select = 'MAX(clatesta.sumgrades)';
            $join = $firstlastattemptjoin;
            $where = 'clatesta.attempt = first_last_attempts.lastattempt AND';
            break;

        case QUIZ_GRADEAVERAGE:
            $select = 'AVG(clatesta.sumgrades)';
            $join = '';
            $where = '';
            break;

        default:
        case QUIZ_GRADEHIGHEST:
            $select = 'MAX(clatesta.sumgrades)';
            $join = '';
            $where = '';
            break;
    }

    if ($clatest->sumgrades >= 0.000005) {
        $finalgrade = $select . ' * ' . ($clatest->grade / $clatest->sumgrades);
    } else {
        $finalgrade = '0';
    }
    $param['clatestid'] = $clatest->id;
    $param['clatestid2'] = $clatest->id;
    $param['clatestid3'] = $clatest->id;
    $param['clatestid4'] = $clatest->id;
    $param['statefinished'] = clatest_attempt::FINISHED;
    $param['statefinished2'] = clatest_attempt::FINISHED;
    $finalgradesubquery = "
            SELECT clatesta.userid, $finalgrade AS newgrade
            FROM {clatest_attempts} clatesta
            $join
            WHERE
                $where
                clatesta.state = :statefinished AND
                clatesta.preview = 0 AND
                clatesta.clatest = :clatestid3
            GROUP BY clatesta.userid";

    $changedgrades = $DB->get_records_sql("
            SELECT users.userid, qg.id, qg.grade, newgrades.newgrade

            FROM (
                SELECT userid
                FROM {clatest_grades} qg
                WHERE clatest = :clatestid
            UNION
                SELECT DISTINCT userid
                FROM {clatest_attempts} clatesta2
                WHERE
                    clatesta2.state = :statefinished2 AND
                    clatesta2.preview = 0 AND
                    clatesta2.clatest = :clatestid2
            ) users

            LEFT JOIN {clatest_grades} qg ON qg.userid = users.userid AND qg.clatest = :clatestid4

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
            $toinsert->clatest = $clatest->id;
            $toinsert->userid = $changedgrade->userid;
            $toinsert->timemodified = $timenow;
            $toinsert->grade = $changedgrade->newgrade;
            $DB->insert_record('clatest_grades', $toinsert);

        } else {
            $toupdate = new stdClass();
            $toupdate->id = $changedgrade->id;
            $toupdate->grade = $changedgrade->newgrade;
            $toupdate->timemodified = $timenow;
            $DB->update_record('clatest_grades', $toupdate);
        }
    }

    if (!empty($todelete)) {
        list($test, $params) = $DB->get_in_or_equal($todelete);
        $DB->delete_records_select('clatest_grades', 'clatest = ? AND userid ' . $test,
                array_merge(array($clatest->id), $params));
    }
}

/**
 * Efficiently update check state time on all open attempts
 *
 * @param array $conditions optional restrictions on which attempts to update
 *                    Allowed conditions:
 *                      courseid => (array|int) attempts in given course(s)
 *                      userid   => (array|int) attempts for given user(s)
 *                      clatestid   => (array|int) attempts in given clatest(s)
 *                      groupid  => (array|int) clatestzes with some override for given group(s)
 *
 */
function clatest_update_open_attempts(array $conditions) {
    global $DB;

    foreach ($conditions as &$value) {
        if (!is_array($value)) {
            $value = array($value);
        }
    }

    $params = array();
    $wheres = array("clatesta.state IN ('inprogress', 'overdue')");
    $iwheres = array("iclatesta.state IN ('inprogress', 'overdue')");

    if (isset($conditions['courseid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['courseid'], SQL_PARAMS_NAMED, 'cid');
        $params = array_merge($params, $inparams);
        $wheres[] = "clatesta.clatest IN (SELECT q.id FROM {clatest} q WHERE q.course $incond)";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['courseid'], SQL_PARAMS_NAMED, 'icid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "iclatesta.clatest IN (SELECT q.id FROM {clatest} q WHERE q.course $incond)";
    }

    if (isset($conditions['userid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['userid'], SQL_PARAMS_NAMED, 'uid');
        $params = array_merge($params, $inparams);
        $wheres[] = "clatesta.userid $incond";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['userid'], SQL_PARAMS_NAMED, 'iuid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "iclatesta.userid $incond";
    }

    if (isset($conditions['clatestid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['clatestid'], SQL_PARAMS_NAMED, 'qid');
        $params = array_merge($params, $inparams);
        $wheres[] = "clatesta.clatest $incond";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['clatestid'], SQL_PARAMS_NAMED, 'iqid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "iclatesta.clatest $incond";
    }

    if (isset($conditions['groupid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['groupid'], SQL_PARAMS_NAMED, 'gid');
        $params = array_merge($params, $inparams);
        $wheres[] = "clatesta.clatest IN (SELECT qo.clatest FROM {clatest_overrides} qo WHERE qo.groupid $incond)";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['groupid'], SQL_PARAMS_NAMED, 'igid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "iclatesta.clatest IN (SELECT qo.clatest FROM {clatest_overrides} qo WHERE qo.groupid $incond)";
    }

    // SQL to compute timeclose and timelimit for each attempt:
    $clatestausersql = clatest_get_attempt_usertime_sql(
            implode("\n                AND ", $iwheres));

    // SQL to compute the new timecheckstate
    $timecheckstatesql = "
          CASE WHEN clatestauser.usertimelimit = 0 AND clatestauser.usertimeclose = 0 THEN NULL
               WHEN clatestauser.usertimelimit = 0 THEN clatestauser.usertimeclose
               WHEN clatestauser.usertimeclose = 0 THEN clatesta.timestart + clatestauser.usertimelimit
               WHEN clatesta.timestart + clatestauser.usertimelimit < clatestauser.usertimeclose THEN clatesta.timestart + clatestauser.usertimelimit
               ELSE clatestauser.usertimeclose END +
          CASE WHEN clatesta.state = 'overdue' THEN clatest.graceperiod ELSE 0 END";

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
        $updatesql = "UPDATE {clatest_attempts} clatesta
                        JOIN {clatest} clatest ON clatest.id = clatesta.clatest
                        JOIN ( $clatestausersql ) clatestauser ON clatestauser.id = clatesta.id
                         SET clatesta.timecheckstate = $timecheckstatesql
                       WHERE $attemptselect";
    } else if ($dbfamily == 'postgres') {
        $updatesql = "UPDATE {clatest_attempts} clatesta
                         SET timecheckstate = $timecheckstatesql
                        FROM {clatest} clatest, ( $clatestausersql ) clatestauser
                       WHERE clatest.id = clatesta.clatest
                         AND clatestauser.id = clatesta.id
                         AND $attemptselect";
    } else if ($dbfamily == 'mssql') {
        $updatesql = "UPDATE clatesta
                         SET timecheckstate = $timecheckstatesql
                        FROM {clatest_attempts} clatesta
                        JOIN {clatest} clatest ON clatest.id = clatesta.clatest
                        JOIN ( $clatestausersql ) clatestauser ON clatestauser.id = clatesta.id
                       WHERE $attemptselect";
    } else {
        // oracle, sqlite and others
        $updatesql = "UPDATE {clatest_attempts} clatesta
                         SET timecheckstate = (
                           SELECT $timecheckstatesql
                             FROM {clatest} clatest, ( $clatestausersql ) clatestauser
                            WHERE clatest.id = clatesta.clatest
                              AND clatestauser.id = clatesta.id
                         )
                         WHERE $attemptselect";
    }

    $DB->execute($updatesql, $params);
}

/**
 * Returns SQL to compute timeclose and timelimit for every attempt, taking into account user and group overrides.
 * The query used herein is very similar to the one in function clatest_get_user_timeclose, so, in case you
 * would change either one of them, make sure to apply your changes to both.
 *
 * @param string $redundantwhereclauses extra where clauses to add to the subquery
 *      for performance. These can use the table alias iclatesta for the clatest attempts table.
 * @return string SQL select with columns attempt.id, usertimeclose, usertimelimit.
 */
function clatest_get_attempt_usertime_sql($redundantwhereclauses = '') {
    if ($redundantwhereclauses) {
        $redundantwhereclauses = 'WHERE ' . $redundantwhereclauses;
    }
    // The multiple qgo JOINS are necessary because we want timeclose/timelimit = 0 (unlimited) to supercede
    // any other group override
    $clatestausersql = "
          SELECT iclatesta.id,
           COALESCE(MAX(quo.timeclose), MAX(qgo1.timeclose), MAX(qgo2.timeclose), iclatest.timeclose) AS usertimeclose,
           COALESCE(MAX(quo.timelimit), MAX(qgo3.timelimit), MAX(qgo4.timelimit), iclatest.timelimit) AS usertimelimit

           FROM {clatest_attempts} iclatesta
           JOIN {clatest} iclatest ON iclatest.id = iclatesta.clatest
      LEFT JOIN {clatest_overrides} quo ON quo.clatest = iclatesta.clatest AND quo.userid = iclatesta.userid
      LEFT JOIN {groups_members} gm ON gm.userid = iclatesta.userid
      LEFT JOIN {clatest_overrides} qgo1 ON qgo1.clatest = iclatesta.clatest AND qgo1.groupid = gm.groupid AND qgo1.timeclose = 0
      LEFT JOIN {clatest_overrides} qgo2 ON qgo2.clatest = iclatesta.clatest AND qgo2.groupid = gm.groupid AND qgo2.timeclose > 0
      LEFT JOIN {clatest_overrides} qgo3 ON qgo3.clatest = iclatesta.clatest AND qgo3.groupid = gm.groupid AND qgo3.timelimit = 0
      LEFT JOIN {clatest_overrides} qgo4 ON qgo4.clatest = iclatesta.clatest AND qgo4.groupid = gm.groupid AND qgo4.timelimit > 0
          $redundantwhereclauses
       GROUP BY iclatesta.id, iclatest.id, iclatest.timeclose, iclatest.timelimit";
    return $clatestausersql;
}

/**
 * Return the attempt with the best grade for a clatest
 *
 * Which attempt is the best depends on $clatest->grademethod. If the grade
 * method is GRADEAVERAGE then this function simply returns the last attempt.
 * @return object         The attempt with the best grade
 * @param object $clatest    The clatest for which the best grade is to be calculated
 * @param array $attempts An array of all the attempts of the user at the clatest
 */
function clatest_calculate_best_attempt($clatest, $attempts) {

    switch ($clatest->grademethod) {

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
 * @return array int => lang string the options for calculating the clatest grade
 *      from the individual attempt grades.
 */
function clatest_get_grading_options() {
    return array(
        QUIZ_GRADEHIGHEST => get_string('gradehighest', 'clatest'),
        QUIZ_GRADEAVERAGE => get_string('gradeaverage', 'clatest'),
        QUIZ_ATTEMPTFIRST => get_string('attemptfirst', 'clatest'),
        QUIZ_ATTEMPTLAST  => get_string('attemptlast', 'clatest')
    );
}

/**
 * @param int $option one of the values QUIZ_GRADEHIGHEST, QUIZ_GRADEAVERAGE,
 *      QUIZ_ATTEMPTFIRST or QUIZ_ATTEMPTLAST.
 * @return the lang string for that option.
 */
function clatest_get_grading_option_name($option) {
    $strings = clatest_get_grading_options();
    return $strings[$option];
}

/**
 * @return array string => lang string the options for handling overdue clatest
 *      attempts.
 */
function clatest_get_overdue_handling_options() {
    return array(
        'autosubmit'  => get_string('overduehandlingautosubmit', 'clatest'),
        'graceperiod' => get_string('overduehandlinggraceperiod', 'clatest'),
        'autoabandon' => get_string('overduehandlingautoabandon', 'clatest'),
    );
}

/**
 * Get the choices for what size user picture to show.
 * @return array string => lang string the options for whether to display the user's picture.
 */
function clatest_get_user_image_options() {
    return array(
        QUIZ_SHOWIMAGE_NONE  => get_string('shownoimage', 'clatest'),
        QUIZ_SHOWIMAGE_SMALL => get_string('showsmallimage', 'clatest'),
        QUIZ_SHOWIMAGE_LARGE => get_string('showlargeimage', 'clatest'),
    );
}

/**
 * Return an user's timeclose for all clatestzes in a course, hereby taking into account group and user overrides.
 *
 * @param int $courseid the course id.
 * @return object An object with of all clatestids and close unixdates in this course, taking into account the most lenient
 * overrides, if existing and 0 if no close date is set.
 */
function clatest_get_user_timeclose($courseid) {
    global $DB, $USER;

    // For teacher and manager/admins return timeclose.
    if (has_capability('moodle/course:update', context_course::instance($courseid))) {
        $sql = "SELECT clatest.id, clatest.timeclose AS usertimeclose
                  FROM {clatest} clatest
                 WHERE clatest.course = :courseid";

        $results = $DB->get_records_sql($sql, array('courseid' => $courseid));
        return $results;
    }

    $sql = "SELECT q.id,
  COALESCE(v.userclose, v.groupclose, q.timeclose, 0) AS usertimeclose
  FROM (
      SELECT clatest.id as clatestid,
             MAX(quo.timeclose) AS userclose, MAX(qgo.timeclose) AS groupclose
       FROM {clatest} clatest
  LEFT JOIN {clatest_overrides} quo on clatest.id = quo.clatest AND quo.userid = :userid
  LEFT JOIN {groups_members} gm ON gm.userid = :useringroupid
  LEFT JOIN {clatest_overrides} qgo on clatest.id = qgo.clatest AND qgo.groupid = gm.groupid
      WHERE clatest.course = :courseid
   GROUP BY clatest.id) v
       JOIN {clatest} q ON q.id = v.clatestid";

    $results = $DB->get_records_sql($sql, array('userid' => $USER->id, 'useringroupid' => $USER->id, 'courseid' => $courseid));
    return $results;

}

/**
 * Get the choices to offer for the 'Questions per page' option.
 * @return array int => string.
 */
function clatest_questions_per_page_options() {
    $pageoptions = array();
    $pageoptions[0] = get_string('neverallononepage', 'clatest');
    $pageoptions[1] = get_string('everyquestion', 'clatest');
    for ($i = 2; $i <= QUIZ_MAX_QPP_OPTION; ++$i) {
        $pageoptions[$i] = get_string('everynquestions', 'clatest', $i);
    }
    return $pageoptions;
}

/**
 * Get the human-readable name for a clatest attempt state.
 * @param string $state one of the state constants like {@link clatest_attempt::IN_PROGRESS}.
 * @return string The lang string to describe that state.
 */
function clatest_attempt_state_name($state) {
    switch ($state) {
        case clatest_attempt::IN_PROGRESS:
            return get_string('stateinprogress', 'clatest');
        case clatest_attempt::OVERDUE:
            return get_string('stateoverdue', 'clatest');
        case clatest_attempt::FINISHED:
            return get_string('statefinished', 'clatest');
        case clatest_attempt::ABANDONED:
            return get_string('stateabandoned', 'clatest');
        default:
            throw new coding_exception('Unknown clatest attempt state.');
    }
}

// Other clatest functions ////////////////////////////////////////////////////////

/**
 * @param object $clatest the clatest.
 * @param int $cmid the course_module object for this clatest.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @param int $variant which question variant to preview (optional).
 * @return string html for a number of icons linked to action pages for a
 * question - preview and edit / view icons depending on user capabilities.
 */
function clatest_question_action_icons($clatest, $cmid, $question, $returnurl, $variant = null) {
    $html = clatest_question_preview_button($clatest, $question, false, $variant) . ' ' .
            clatest_question_edit_button($cmid, $question, $returnurl);
    return $html;
}

/**
 * @param int $cmid the course_module.id for this clatest.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @param string $contentbeforeicon some HTML content to be added inside the link, before the icon.
 * @return the HTML for an edit icon, view icon, or nothing for a question
 *      (depending on permissions).
 */
function clatest_question_edit_button($cmid, $question, $returnurl, $contentaftericon = '') {
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
 * @param object $clatest the clatest settings
 * @param object $question the question
 * @param int $variant which question variant to preview (optional).
 * @return moodle_url to preview this question with the options from this clatest.
 */
function clatest_question_preview_url($clatest, $question, $variant = null) {
    // Get the appropriate display options.
    $displayoptions = mod_clatest_display_options::make_from_clatest($clatest,
            mod_clatest_display_options::DURING);

    $maxmark = null;
    if (isset($question->maxmark)) {
        $maxmark = $question->maxmark;
    }

    // Work out the correcte preview URL.
    return question_preview_url($question->id, $clatest->preferredbehaviour,
            $maxmark, $displayoptions, $variant);
}

/**
 * @param object $clatest the clatest settings
 * @param object $question the question
 * @param bool $label if true, show the preview question label after the icon
 * @param int $variant which question variant to preview (optional).
 * @return the HTML for a preview question icon.
 */
function clatest_question_preview_button($clatest, $question, $label = false, $variant = null) {
    global $PAGE;
    if (!question_has_capability_on($question, 'use')) {
        return '';
    }

    return $PAGE->get_renderer('mod_clatest', 'edit')->question_preview_icon($clatest, $question, $label, $variant);
}

/**
 * @param object $attempt the attempt.
 * @param object $context the clatest context.
 * @return int whether flags should be shown/editable to the current user for this attempt.
 */
function clatest_get_flag_option($attempt, $context) {
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
 * Work out what state this clatest attempt is in - in the sense used by
 * clatest_get_review_options, not in the sense of $attempt->state.
 * @param object $clatest the clatest settings
 * @param object $attempt the clatest_attempt database row.
 * @return int one of the mod_clatest_display_options::DURING,
 *      IMMEDIATELY_AFTER, LATER_WHILE_OPEN or AFTER_CLOSE constants.
 */
function clatest_attempt_state($clatest, $attempt) {
    if ($attempt->state == clatest_attempt::IN_PROGRESS) {
        return mod_clatest_display_options::DURING;
    } else if ($clatest->timeclose && time() >= $clatest->timeclose) {
        return mod_clatest_display_options::AFTER_CLOSE;
    } else if (time() < $attempt->timefinish + 120) {
        return mod_clatest_display_options::IMMEDIATELY_AFTER;
    } else {
        return mod_clatest_display_options::LATER_WHILE_OPEN;
    }
}

/**
 * The the appropraite mod_clatest_display_options object for this attempt at this
 * clatest right now.
 *
 * @param object $clatest the clatest instance.
 * @param object $attempt the attempt in question.
 * @param $context the clatest context.
 *
 * @return mod_clatest_display_options
 */
function clatest_get_review_options($clatest, $attempt, $context) {
    $options = mod_clatest_display_options::make_from_clatest($clatest, clatest_attempt_state($clatest, $attempt));

    $options->readonly = true;
    $options->flags = clatest_get_flag_option($attempt, $context);
    if (!empty($attempt->id)) {
        $options->questionreviewlink = new moodle_url('/mod/clatest/reviewquestion.php',
                array('attempt' => $attempt->id));
    }

    // Show a link to the comment box only for closed attempts.
    if (!empty($attempt->id) && $attempt->state == clatest_attempt::FINISHED && !$attempt->preview &&
            !is_null($context) && has_capability('mod/clatest:grade', $context)) {
        $options->manualcomment = question_display_options::VISIBLE;
        $options->manualcommentlink = new moodle_url('/mod/clatest/comment.php',
                array('attempt' => $attempt->id));
    }

    if (!is_null($context) && !$attempt->preview &&
            has_capability('mod/clatest:viewreports', $context) &&
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
 * Combines the review options from a number of different clatest attempts.
 * Returns an array of two ojects, so the suggested way of calling this
 * funciton is:
 * list($someoptions, $alloptions) = clatest_get_combined_reviewoptions(...)
 *
 * @param object $clatest the clatest instance.
 * @param array $attempts an array of attempt objects.
 *
 * @return array of two options objects, one showing which options are true for
 *          at least one of the attempts, the other showing which options are true
 *          for all attempts.
 */
function clatest_get_combined_reviewoptions($clatest, $attempts) {
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
        $attemptoptions = mod_clatest_display_options::make_from_clatest($clatest,
                clatest_attempt_state($clatest, $attempt));
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
function clatest_send_confirmation($recipient, $a) {

    // Add information about the recipient to $a.
    // Don't do idnumber. we want idnumber to be the submitter's idnumber.
    $a->username     = fullname($recipient);
    $a->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->courseid          = $a->courseid;
    $eventdata->component         = 'mod_clatest';
    $eventdata->name              = 'confirmation';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = core_user::get_noreply_user();
    $eventdata->userto            = $recipient;
    $eventdata->subject           = get_string('emailconfirmsubject', 'clatest', $a);
    $eventdata->fullmessage       = get_string('emailconfirmbody', 'clatest', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = get_string('emailconfirmsmall', 'clatest', $a);
    $eventdata->contexturl        = $a->clatesturl;
    $eventdata->contexturlname    = $a->clatestname;

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
function clatest_send_notification($recipient, $submitter, $a) {

    // Recipient info for template.
    $a->useridnumber = $recipient->idnumber;
    $a->username     = fullname($recipient);
    $a->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->courseid          = $a->courseid;
    $eventdata->component         = 'mod_clatest';
    $eventdata->name              = 'submission';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = $submitter;
    $eventdata->userto            = $recipient;
    $eventdata->subject           = get_string('emailnotifysubject', 'clatest', $a);
    $eventdata->fullmessage       = get_string('emailnotifybody', 'clatest', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = get_string('emailnotifysmall', 'clatest', $a);
    $eventdata->contexturl        = $a->clatestreviewurl;
    $eventdata->contexturlname    = $a->clatestname;

    // ... and send it.
    return message_send($eventdata);
}

/**
 * Send all the requried messages when a clatest attempt is submitted.
 *
 * @param object $course the course
 * @param object $clatest the clatest
 * @param object $attempt this attempt just finished
 * @param object $context the clatest context
 * @param object $cm the coursemodule for this clatest
 *
 * @return bool true if all necessary messages were sent successfully, else false.
 */
function clatest_send_notification_messages($course, $clatest, $attempt, $context, $cm) {
    global $CFG, $DB;

    // Do nothing if required objects not present.
    if (empty($course) or empty($clatest) or empty($attempt) or empty($context)) {
        throw new coding_exception('$course, $clatest, $attempt, $context and $cm must all be set.');
    }

    $submitter = $DB->get_record('user', array('id' => $attempt->userid), '*', MUST_EXIST);

    // Check for confirmation required.
    $sendconfirm = false;
    $notifyexcludeusers = '';
    if (has_capability('mod/clatest:emailconfirmsubmission', $context, $submitter, false)) {
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
        // If the user is not in a group, and the clatest is set to group mode,
        // then set $groups to a non-existant id so that only users with
        // 'moodle/site:accessallgroups' get notified.
        $groups = -1;
    } else {
        $groups = '';
    }
    $userstonotify = get_users_by_capability($context, 'mod/clatest:emailnotifysubmission',
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
    $a->clatestname        = $clatest->name;
    $a->clatestreporturl   = $CFG->wwwroot . '/mod/clatest/report.php?id=' . $cm->id;
    $a->clatestreportlink  = '<a href="' . $a->clatestreporturl . '">' .
            format_string($clatest->name) . ' report</a>';
    $a->clatesturl         = $CFG->wwwroot . '/mod/clatest/view.php?id=' . $cm->id;
    $a->clatestlink        = '<a href="' . $a->clatesturl . '">' . format_string($clatest->name) . '</a>';
    // Attempt info.
    $a->submissiontime  = userdate($attempt->timefinish);
    $a->timetaken       = format_time($attempt->timefinish - $attempt->timestart);
    $a->clatestreviewurl   = $CFG->wwwroot . '/mod/clatest/review.php?attempt=' . $attempt->id;
    $a->clatestreviewlink  = '<a href="' . $a->clatestreviewurl . '">' .
            format_string($clatest->name) . ' review</a>';
    // Student who sat the clatest info.
    $a->studentidnumber = $submitter->idnumber;
    $a->studentname     = fullname($submitter);
    $a->studentusername = $submitter->username;

    $allok = true;

    // Send notifications if required.
    if (!empty($userstonotify)) {
        foreach ($userstonotify as $recipient) {
            $allok = $allok && clatest_send_notification($recipient, $submitter, $a);
        }
    }

    // Send confirmation if required. We send the student confirmation last, so
    // that if message sending is being intermittently buggy, which means we send
    // some but not all messages, and then try again later, then teachers may get
    // duplicate messages, but the student will always get exactly one.
    if ($sendconfirm) {
        $allok = $allok && clatest_send_confirmation($submitter, $a);
    }

    return $allok;
}

/**
 * Send the notification message when a clatest attempt becomes overdue.
 *
 * @param clatest_attempt $attemptobj all the data about the clatest attempt.
 */
function clatest_send_overdue_message($attemptobj) {
    global $CFG, $DB;

    $submitter = $DB->get_record('user', array('id' => $attemptobj->get_userid()), '*', MUST_EXIST);

    if (!$attemptobj->has_capability('mod/clatest:emailwarnoverdue', $submitter->id, false)) {
        return; // Message not required.
    }

    if (!$attemptobj->has_response_to_at_least_one_graded_question()) {
        return; // Message not required.
    }

    // Prepare lots of useful information that admins might want to include in
    // the email message.
    $clatestname = format_string($attemptobj->get_clatest_name());

    $deadlines = array();
    if ($attemptobj->get_clatest()->timelimit) {
        $deadlines[] = $attemptobj->get_attempt()->timestart + $attemptobj->get_clatest()->timelimit;
    }
    if ($attemptobj->get_clatest()->timeclose) {
        $deadlines[] = $attemptobj->get_clatest()->timeclose;
    }
    $duedate = min($deadlines);
    $graceend = $duedate + $attemptobj->get_clatest()->graceperiod;

    $a = new stdClass();
    // Course info.
    $a->courseid           = $attemptobj->get_course()->id;
    $a->coursename         = format_string($attemptobj->get_course()->fullname);
    $a->courseshortname    = format_string($attemptobj->get_course()->shortname);
    // Quiz info.
    $a->clatestname           = $clatestname;
    $a->clatesturl            = $attemptobj->view_url();
    $a->clatestlink           = '<a href="' . $a->clatesturl . '">' . $clatestname . '</a>';
    // Attempt info.
    $a->attemptduedate     = userdate($duedate);
    $a->attemptgraceend    = userdate($graceend);
    $a->attemptsummaryurl  = $attemptobj->summary_url()->out(false);
    $a->attemptsummarylink = '<a href="' . $a->attemptsummaryurl . '">' . $clatestname . ' review</a>';
    // Student's info.
    $a->studentidnumber    = $submitter->idnumber;
    $a->studentname        = fullname($submitter);
    $a->studentusername    = $submitter->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->courseid          = $a->courseid;
    $eventdata->component         = 'mod_clatest';
    $eventdata->name              = 'attempt_overdue';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = core_user::get_noreply_user();
    $eventdata->userto            = $submitter;
    $eventdata->subject           = get_string('emailoverduesubject', 'clatest', $a);
    $eventdata->fullmessage       = get_string('emailoverduebody', 'clatest', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = get_string('emailoverduesmall', 'clatest', $a);
    $eventdata->contexturl        = $a->clatesturl;
    $eventdata->contexturlname    = $a->clatestname;

    // Send the message.
    return message_send($eventdata);
}

/**
 * Handle the clatest_attempt_submitted event.
 *
 * This sends the confirmation and notification messages, if required.
 *
 * @param object $event the event object.
 */
function clatest_attempt_submitted_handler($event) {
    global $DB;

    $course  = $DB->get_record('course', array('id' => $event->courseid));
    $attempt = $event->get_record_snapshot('clatest_attempts', $event->objectid);
    $clatest    = $event->get_record_snapshot('clatest', $attempt->clatest);
    $cm      = get_coursemodule_from_id('clatest', $event->get_context()->instanceid, $event->courseid);

    if (!($course && $clatest && $cm && $attempt)) {
        // Something has been deleted since the event was raised. Therefore, the
        // event is no longer relevant.
        return true;
    }

    // Update completion state.
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && ($clatest->completionattemptsexhausted || $clatest->completionpass)) {
        $completion->update_state($cm, COMPLETION_COMPLETE, $event->userid);
    }
    return clatest_send_notification_messages($course, $clatest, $attempt,
            context_module::instance($cm->id), $cm);
}

/**
 * Handle groups_member_added event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_clatest\group_observers::group_member_added()}.
 */
function clatest_groups_member_added_handler($event) {
    debugging('clatest_groups_member_added_handler() is deprecated, please use ' .
        '\mod_clatest\group_observers::group_member_added() instead.', DEBUG_DEVELOPER);
    clatest_update_open_attempts(array('userid'=>$event->userid, 'groupid'=>$event->groupid));
}

/**
 * Handle groups_member_removed event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_clatest\group_observers::group_member_removed()}.
 */
function clatest_groups_member_removed_handler($event) {
    debugging('clatest_groups_member_removed_handler() is deprecated, please use ' .
        '\mod_clatest\group_observers::group_member_removed() instead.', DEBUG_DEVELOPER);
    clatest_update_open_attempts(array('userid'=>$event->userid, 'groupid'=>$event->groupid));
}

/**
 * Handle groups_group_deleted event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_clatest\group_observers::group_deleted()}.
 */
function clatest_groups_group_deleted_handler($event) {
    global $DB;
    debugging('clatest_groups_group_deleted_handler() is deprecated, please use ' .
        '\mod_clatest\group_observers::group_deleted() instead.', DEBUG_DEVELOPER);
    clatest_process_group_deleted_in_course($event->courseid);
}

/**
 * Logic to happen when a/some group(s) has/have been deleted in a course.
 *
 * @param int $courseid The course ID.
 * @return void
 */
function clatest_process_group_deleted_in_course($courseid) {
    global $DB;

    // It would be nice if we got the groupid that was deleted.
    // Instead, we just update all clatestzes with orphaned group overrides.
    $sql = "SELECT o.id, o.clatest
              FROM {clatest_overrides} o
              JOIN {clatest} clatest ON clatest.id = o.clatest
         LEFT JOIN {groups} grp ON grp.id = o.groupid
             WHERE clatest.course = :courseid
               AND o.groupid IS NOT NULL
               AND grp.id IS NULL";
    $params = array('courseid' => $courseid);
    $records = $DB->get_records_sql_menu($sql, $params);
    if (!$records) {
        return; // Nothing to do.
    }
    $DB->delete_records_list('clatest_overrides', 'id', array_keys($records));
    clatest_update_open_attempts(array('clatestid' => array_unique(array_values($records))));
}

/**
 * Handle groups_members_removed event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_clatest\group_observers::group_member_removed()}.
 */
function clatest_groups_members_removed_handler($event) {
    debugging('clatest_groups_members_removed_handler() is deprecated, please use ' .
        '\mod_clatest\group_observers::group_member_removed() instead.', DEBUG_DEVELOPER);
    if ($event->userid == 0) {
        clatest_update_open_attempts(array('courseid'=>$event->courseid));
    } else {
        clatest_update_open_attempts(array('courseid'=>$event->courseid, 'userid'=>$event->userid));
    }
}

/**
 * Get the information about the standard clatest JavaScript module.
 * @return array a standard jsmodule structure.
 */
function clatest_get_js_module() {
    global $PAGE;

    return array(
        'name' => 'mod_clatest',
        'fullpath' => '/mod/clatest/module.js',
        'requires' => array('base', 'dom', 'event-delegate', 'event-key',
                'core_question_engine', 'moodle-core-formchangechecker'),
        'strings' => array(
            array('cancel', 'moodle'),
            array('flagged', 'question'),
            array('functiondisabledbysecuremode', 'clatest'),
            array('startattempt', 'clatest'),
            array('timesup', 'clatest'),
            array('changesmadereallygoaway', 'moodle'),
        ),
    );
}


/**
 * An extension of question_display_options that includes the extra options used
 * by the clatest.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_clatest_display_options extends question_display_options {
    /**#@+
     * @var integer bits used to indicate various times in relation to a
     * clatest attempt.
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
     * Set up the various options from the clatest settings, and a time constant.
     * @param object $clatest the clatest settings.
     * @param int $one of the {@link DURING}, {@link IMMEDIATELY_AFTER},
     * {@link LATER_WHILE_OPEN} or {@link AFTER_CLOSE} constants.
     * @return mod_clatest_display_options set up appropriately.
     */
    public static function make_from_clatest($clatest, $when) {
        $options = new self();

        $options->attempt = self::extract($clatest->reviewattempt, $when, true, false);
        $options->correctness = self::extract($clatest->reviewcorrectness, $when);
        $options->marks = self::extract($clatest->reviewmarks, $when,
                self::MARK_AND_MAX, self::MAX_ONLY);
        $options->feedback = self::extract($clatest->reviewspecificfeedback, $when);
        $options->generalfeedback = self::extract($clatest->reviewgeneralfeedback, $when);
        $options->rightanswer = self::extract($clatest->reviewrightanswer, $when);
        $options->overallfeedback = self::extract($clatest->reviewoverallfeedback, $when);

        $options->numpartscorrect = $options->feedback;
        $options->manualcomment = $options->feedback;

        if ($clatest->questiondecimalpoints != -1) {
            $options->markdp = $clatest->questiondecimalpoints;
        } else {
            $options->markdp = $clatest->decimalpoints;
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
 * a particular clatest.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qubaids_for_clatest extends qubaid_join {
    public function __construct($clatestid, $includepreviews = true, $onlyfinished = false) {
        $where = 'clatesta.clatest = :clatestaclatest';
        $params = array('clatestaclatest' => $clatestid);

        if (!$includepreviews) {
            $where .= ' AND preview = 0';
        }

        if ($onlyfinished) {
            $where .= ' AND state = :statefinished';
            $params['statefinished'] = clatest_attempt::FINISHED;
        }

        parent::__construct('{clatest_attempts} clatesta', 'clatesta.uniqueid', $where, $params);
    }
}

/**
 * A {@link qubaid_condition} for finding all the question usages belonging to a particular user and clatest combination.
 *
 * @copyright  2018 Andrew Nicols <andrwe@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qubaids_for_clatest_user extends qubaid_join {
    /**
     * Constructor for this qubaid.
     *
     * @param   int     $clatestid The clatest to search.
     * @param   int     $userid The user to filter on
     * @param   bool    $includepreviews Whether to include preview attempts
     * @param   bool    $onlyfinished Whether to only include finished attempts or not
     */
    public function __construct($clatestid, $userid, $includepreviews = true, $onlyfinished = false) {
        $where = 'clatesta.clatest = :clatestaclatest AND clatesta.userid = :clatestauserid';
        $params = [
            'clatestaclatest' => $clatestid,
            'clatestauserid' => $userid,
        ];

        if (!$includepreviews) {
            $where .= ' AND preview = 0';
        }

        if ($onlyfinished) {
            $where .= ' AND state = :statefinished';
            $params['statefinished'] = clatest_attempt::FINISHED;
        }

        parent::__construct('{clatest_attempts} clatesta', 'clatesta.uniqueid', $where, $params);
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
function clatest_question_tostring($question, $showicon = false, $showquestiontext = true) {
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
function clatest_require_question_use($questionid) {
    global $DB;
    $question = $DB->get_record('question', array('id' => $questionid), '*', MUST_EXIST);
    question_require_capability_on($question, 'use');
}

/**
 * Verify that the question exists, and the user has permission to use it.
 * @param object $clatest the clatest settings.
 * @param int $slot which question in the clatest to test.
 * @return bool whether the user can use this question.
 */
function clatest_has_question_use($clatest, $slot) {
    global $DB;
    $question = $DB->get_record_sql("
            SELECT q.*
              FROM {clatest_slots} slot
              JOIN {question} q ON q.id = slot.questionid
             WHERE slot.clatestid = ? AND slot.slot = ?", array($clatest->id, $slot));
    if (!$question) {
        return false;
    }
    return question_has_capability_on($question, 'use');
}

/**
 * Add a question to a clatest
 *
 * Adds a question to a clatest by updating $clatest as well as the
 * clatest and clatest_slots tables. It also adds a page break if required.
 * @param int $questionid The id of the question to be added
 * @param object $clatest The extended clatest object as used by edit.php
 *      This is updated by this function
 * @param int $page Which page in clatest to add the question on. If 0 (default),
 *      add at the end
 * @param float $maxmark The maximum mark to set for this question. (Optional,
 *      defaults to question.defaultmark.
 * @return bool false if the question was already in the clatest
 */
function clatest_add_clatest_question($questionid, $clatest, $page = 0, $maxmark = null) {
    global $DB;

    // Make sue the question is not of the "random" type.
    $questiontype = $DB->get_field('question', 'qtype', array('id' => $questionid));
    if ($questiontype == 'random') {
        throw new coding_exception(
                'Adding "random" questions via clatest_add_clatest_question() is deprecated. Please use clatest_add_random_questions().'
        );
    }

    $slots = $DB->get_records('clatest_slots', array('clatestid' => $clatest->id),
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
    $slot->clatestid = $clatest->id;
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
                $DB->set_field('clatest_slots', 'slot', $otherslot->slot + 1, array('id' => $otherslot->id));
            } else {
                $lastslotbefore = $otherslot->slot;
                break;
            }
        }
        $slot->slot = $lastslotbefore + 1;
        $slot->page = min($page, $maxpage + 1);

        clatest_update_section_firstslots($clatest->id, 1, max($lastslotbefore, 1));

    } else {
        $lastslot = end($slots);
        if ($lastslot) {
            $slot->slot = $lastslot->slot + 1;
        } else {
            $slot->slot = 1;
        }
        if ($clatest->questionsperpage && $numonlastpage >= $clatest->questionsperpage) {
            $slot->page = $maxpage + 1;
        } else {
            $slot->page = $maxpage;
        }
    }

    $DB->insert_record('clatest_slots', $slot);
    $trans->allow_commit();
}

/**
 * Move all the section headings in a certain slot range by a certain offset.
 *
 * @param int $clatestid the id of a clatest
 * @param int $direction amount to adjust section heading positions. Normally +1 or -1.
 * @param int $afterslot adjust headings that start after this slot.
 * @param int|null $beforeslot optionally, only adjust headings before this slot.
 */
function clatest_update_section_firstslots($clatestid, $direction, $afterslot, $beforeslot = null) {
    global $DB;
    $where = 'clatestid = ? AND firstslot > ?';
    $params = [$direction, $clatestid, $afterslot];
    if ($beforeslot) {
        $where .= ' AND firstslot < ?';
        $params[] = $beforeslot;
    }
    $firstslotschanges = $DB->get_records_select_menu('clatest_sections',
            $where, $params, '', 'firstslot, firstslot + ?');
    update_field_with_unique_index('clatest_sections', 'firstslot', $firstslotschanges, ['clatestid' => $clatestid]);
}

/**
 * Add a random question to the clatest at a given point.
 * @param stdClass $clatest the clatest settings.
 * @param int $addonpage the page on which to add the question.
 * @param int $categoryid the question category to add the question from.
 * @param int $number the number of random questions to add.
 * @param bool $includesubcategories whether to include questoins from subcategories.
 * @param int[] $tagids Array of tagids. The question that will be picked randomly should be tagged with all these tags.
 */
function clatest_add_random_questions($clatest, $addonpage, $categoryid, $number,
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
    // not used by any clatest.
    $existingquestions = $DB->get_records_sql(
        "SELECT q.id, q.qtype FROM {question} q
        WHERE qtype = 'random'
            AND category = ?
            AND " . $DB->sql_compare_text('questiontext') . " = ?
            AND NOT EXISTS (
                    SELECT *
                      FROM {clatest_slots}
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
                print_error('cannotinsertrandomquestion', 'clatest');
            }
        }

        $randomslotdata = new stdClass();
        $randomslotdata->clatestid = $clatest->id;
        $randomslotdata->questionid = $question->id;
        $randomslotdata->questioncategoryid = $categoryid;
        $randomslotdata->includingsubcategories = $includesubcategories ? 1 : 0;
        $randomslotdata->maxmark = 1;

        $randomslot = new \mod_clatest\local\structure\slot_random($randomslotdata);
        $randomslot->set_clatest($clatest);
        $randomslot->set_tags($tags);
        $randomslot->insert($addonpage);
    }
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $clatest       clatest object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.1
 */
function clatest_view($clatest, $course, $cm, $context) {

    $params = array(
        'objectid' => $clatest->id,
        'context' => $context
    );

    $event = \mod_clatest\event\course_module_viewed::create($params);
    $event->add_record_snapshot('clatest', $clatest);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Validate permissions for creating a new attempt and start a new preview attempt if required.
 *
 * @param  clatest $clatestobj clatest object
 * @param  clatest_access_manager $accessmanager clatest access manager
 * @param  bool $forcenew whether was required to start a new preview attempt
 * @param  int $page page to jump to in the attempt
 * @param  bool $redirect whether to redirect or throw exceptions (for web or ws usage)
 * @return array an array containing the attempt information, access error messages and the page to jump to in the attempt
 * @throws moodle_clatest_exception
 * @since Moodle 3.1
 */
function clatest_validate_new_attempt(clatest $clatestobj, clatest_access_manager $accessmanager, $forcenew, $page, $redirect) {
    global $DB, $USER;
    $timenow = time();

    if ($clatestobj->is_preview_user() && $forcenew) {
        $accessmanager->current_attempt_finished();
    }

    // Check capabilities.
    if (!$clatestobj->is_preview_user()) {
        $clatestobj->require_capability('mod/clatest:attempt');
    }

    // Check to see if a new preview was requested.
    if ($clatestobj->is_preview_user() && $forcenew) {
        // To force the creation of a new preview, we mark the current attempt (if any)
        // as finished. It will then automatically be deleted below.
        $DB->set_field('clatest_attempts', 'state', clatest_attempt::FINISHED,
                array('clatest' => $clatestobj->get_clatestid(), 'userid' => $USER->id));
    }

    // Look for an existing attempt.
    $attempts = clatest_get_user_attempts($clatestobj->get_clatestid(), $USER->id, 'all', true);
    $lastattempt = end($attempts);

    $attemptnumber = null;
    // If an in-progress attempt exists, check password then redirect to it.
    if ($lastattempt && ($lastattempt->state == clatest_attempt::IN_PROGRESS ||
            $lastattempt->state == clatest_attempt::OVERDUE)) {
        $currentattemptid = $lastattempt->id;
        $messages = $accessmanager->prevent_access();

        // If the attempt is now overdue, deal with that.
        $clatestobj->create_attempt_object($lastattempt)->handle_if_time_expired($timenow, true);

        // And, if the attempt is now no longer in progress, redirect to the appropriate place.
        if ($lastattempt->state == clatest_attempt::ABANDONED || $lastattempt->state == clatest_attempt::FINISHED) {
            if ($redirect) {
                redirect($clatestobj->review_url($lastattempt->id));
            } else {
                throw new moodle_clatest_exception($clatestobj, 'attemptalreadyclosed');
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
 * @param  clatest $clatestobj clatest object
 * @param  int $attemptnumber the attempt number
 * @param  object $lastattempt last attempt object
 * @param bool $offlineattempt whether is an offline attempt or not
 * @return object the new attempt
 * @since  Moodle 3.1
 */
function clatest_prepare_and_start_new_attempt(clatest $clatestobj, $attemptnumber, $lastattempt, $offlineattempt = false) {
    global $DB, $USER;

    // Delete any previous preview attempts belonging to this user.
    clatest_delete_previews($clatestobj->get_clatest(), $USER->id);

    $quba = question_engine::make_questions_usage_by_activity('mod_clatest', $clatestobj->get_context());
    $quba->set_preferred_behaviour($clatestobj->get_clatest()->preferredbehaviour);

    // Create the new attempt and initialize the question sessions
    $timenow = time(); // Update time now, in case the server is running really slowly.
    $attempt = clatest_create_attempt($clatestobj, $attemptnumber, $lastattempt, $timenow, $clatestobj->is_preview_user());

    if (!($clatestobj->get_clatest()->attemptonlast && $lastattempt)) {
        $attempt = clatest_start_new_attempt($clatestobj, $quba, $attempt, $attemptnumber, $timenow);
    } else {
        $attempt = clatest_start_attempt_built_on_last($quba, $attempt, $lastattempt);
    }

    $transaction = $DB->start_delegated_transaction();

    // Init the timemodifiedoffline for offline attempts.
    if ($offlineattempt) {
        $attempt->timemodifiedoffline = $attempt->timemodified;
    }
    $attempt = clatest_attempt_save_started($clatestobj, $quba, $attempt);

    $transaction->allow_commit();

    return $attempt;
}

/**
 * Check if the given calendar_event is either a user or group override
 * event for clatest.
 *
 * @param calendar_event $event The calendar event to check
 * @return bool
 */
function clatest_is_overriden_calendar_event(\calendar_event $event) {
    global $DB;

    if (!isset($event->modulename)) {
        return false;
    }

    if ($event->modulename != 'clatest') {
        return false;
    }

    if (!isset($event->instance)) {
        return false;
    }

    if (!isset($event->userid) && !isset($event->groupid)) {
        return false;
    }

    $overrideparams = [
        'clatest' => $event->instance
    ];

    if (isset($event->groupid)) {
        $overrideparams['groupid'] = $event->groupid;
    } else if (isset($event->userid)) {
        $overrideparams['userid'] = $event->userid;
    }

    return $DB->record_exists('clatest_overrides', $overrideparams);
}

/**
 * Retrieves tag information for the given list of clatest slot ids.
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
 * @param int[] $slotids The list of id for the clatest slots.
 * @return array[] List of clatest_slot_tags records indexed by slot id.
 */
function clatest_retrieve_tags_for_slot_ids($slotids) {
    global $DB;

    if (empty($slotids)) {
        return [];
    }

    $slottags = $DB->get_records_list('clatest_slot_tags', 'slotid', $slotids);
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
 * Retrieves tag information for the given clatest slot.
 * A clatest slot have some tags if and only if it is representing a random question by tags.
 *
 * @param int $slotid The id of the clatest slot.
 * @return stdClass[] List of clatest_slot_tags records.
 */
function clatest_retrieve_slot_tags($slotid) {
    $slottags = clatest_retrieve_tags_for_slot_ids([$slotid]);
    return $slottags[$slotid];
}

/**
 * Retrieves tag ids for the given clatest slot.
 * A clatest slot have some tags if and only if it is representing a random question by tags.
 *
 * @param int $slotid The id of the clatest slot.
 * @return int[]
 */
function clatest_retrieve_slot_tag_ids($slotid) {
    $tags = clatest_retrieve_slot_tags($slotid);

    // Only work with tags that exist.
    return array_filter(array_column($tags, 'tagid'));
}

/**
 * Get clatest attempt and handling error.
 *
 * @param int $attemptid the id of the current attempt.
 * @param int|null $cmid the course_module id for this clatest.
 * @return clatest_attempt $attemptobj all the data about the clatest attempt.
 * @throws moodle_exception
 */
function clatest_create_attempt_handling_errors($attemptid, $cmid = null) {
    try {
        $attempobj = clatest_attempt::create($attemptid);
    } catch (moodle_exception $e) {
        if (!empty($cmid)) {
            list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'clatest');
            $continuelink = new moodle_url('/mod/clatest/view.php', array('id' => $cmid));
            $context = context_module::instance($cm->id);
            if (has_capability('mod/clatest:preview', $context)) {
                throw new moodle_exception('attempterrorcontentchange', 'clatest', $continuelink);
            } else {
                throw new moodle_exception('attempterrorcontentchangeforuser', 'clatest', $continuelink);
            }
        } else {
            throw new moodle_exception('attempterrorinvalid', 'clatest');
        }
    }
    if (!empty($cmid) && $attempobj->get_cmid() != $cmid) {
        throw new moodle_exception('invalidcoursemodule');
    } else {
        return $attempobj;
    }
}
