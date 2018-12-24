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
 * Library of functions used by the flipquiz module.
 *
 * This contains functions that are called from within the flipquiz module only
 * Functions that are also called by core Moodle are in {@link lib.php}
 * This script also loads the code in {@link questionlib.php} which holds
 * the module-indpendent code for handling questions and which in turn
 * initialises all the questiontype classes.
 *
 * @package    mod_flipquiz
 * @copyright  1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/flipquiz/lib.php');
require_once($CFG->dirroot . '/mod/flipquiz/accessmanager.php');
require_once($CFG->dirroot . '/mod/flipquiz/accessmanager_form.php');
require_once($CFG->dirroot . '/mod/flipquiz/renderer.php');
require_once($CFG->dirroot . '/mod/flipquiz/attemptlib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/questionlib.php');


/**
 * @var int We show the countdown timer if there is less than this amount of time left before the
 * the flipquiz close date. (1 hour)
 */
define('FLIPQUIZ_SHOW_TIME_BEFORE_DEADLINE', '3600');

/**
 * @var int If there are fewer than this many seconds left when the student submits
 * a page of the flipquiz, then do not take them to the next page of the flipquiz. Instead
 * close the flipquiz immediately.
 */
define('FLIPQUIZ_MIN_TIME_TO_CONTINUE', '2');

/**
 * @var int We show no image when user selects No image from dropdown menu in flipquiz settings.
 */
define('FLIPQUIZ_SHOWIMAGE_NONE', 0);

/**
 * @var int We show small image when user selects small image from dropdown menu in flipquiz settings.
 */
define('FLIPQUIZ_SHOWIMAGE_SMALL', 1);

/**
 * @var int We show Large image when user selects Large image from dropdown menu in flipquiz settings.
 */
define('FLIPQUIZ_SHOWIMAGE_LARGE', 2);


// Functions related to attempts ///////////////////////////////////////////////

/**
 * Creates an object to represent a new attempt at a flipquiz
 *
 * Creates an attempt object to represent an attempt at the flipquiz by the current
 * user starting at the current time. The ->id field is not set. The object is
 * NOT written to the database.
 *
 * @param object $flipquizobj the flipquiz object to create an attempt for.
 * @param int $attemptnumber the sequence number for the attempt.
 * @param object $lastattempt the previous attempt by this user, if any. Only needed
 *         if $attemptnumber > 1 and $flipquiz->attemptonlast is true.
 * @param int $timenow the time the attempt was started at.
 * @param bool $ispreview whether this new attempt is a preview.
 * @param int $userid  the id of the user attempting this flipquiz.
 *
 * @return object the newly created attempt object.
 */
function flipquiz_create_attempt(flipquiz $flipquizobj, $attemptnumber, $lastattempt, $timenow, $ispreview = false, $userid = null) {
    global $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $flipquiz = $flipquizobj->get_flipquiz();
    if ($flipquiz->sumgrades < 0.000005 && $flipquiz->grade > 0.000005) {
        throw new moodle_exception('cannotstartgradesmismatch', 'flipquiz',
                new moodle_url('/mod/flipquiz/view.php', array('q' => $flipquiz->id)),
                    array('grade' => flipquiz_format_grade($flipquiz, $flipquiz->grade)));
    }

    if ($attemptnumber == 1 || !$flipquiz->attemptonlast) {
        // We are not building on last attempt so create a new attempt.
        $attempt = new stdClass();
        $attempt->flipquiz = $flipquiz->id;
        $attempt->userid = $userid;
        $attempt->preview = 0;
        $attempt->layout = '';
    } else {
        // Build on last attempt.
        if (empty($lastattempt)) {
            print_error('cannotfindprevattempt', 'flipquiz');
        }
        $attempt = $lastattempt;
    }

    $attempt->attempt = $attemptnumber;
    $attempt->timestart = $timenow;
    $attempt->timefinish = 0;
    $attempt->timemodified = $timenow;
    $attempt->timemodifiedoffline = 0;
    $attempt->state = flipquiz_attempt::IN_PROGRESS;
    $attempt->currentpage = 0;
    $attempt->sumgrades = null;

    // If this is a preview, mark it as such.
    if ($ispreview) {
        $attempt->preview = 1;
    }

    $timeclose = $flipquizobj->get_access_manager($timenow)->get_end_time($attempt);
    if ($timeclose === false || $ispreview) {
        $attempt->timecheckstate = null;
    } else {
        $attempt->timecheckstate = $timeclose;
    }

    return $attempt;
}
/**
 * Start a normal, new, flipquiz attempt.
 *
 * @param flipquiz      $flipquizobj            the flipquiz object to start an attempt for.
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
function flipquiz_start_new_attempt($flipquizobj, $quba, $attempt, $attemptnumber, $timenow,
                                $questionids = array(), $forcedvariantsbyslot = array()) {

    // Usages for this user's previous flipquiz attempts.
    $qubaids = new \mod_flipquiz\question\qubaids_for_users_attempts(
            $flipquizobj->get_flipquizid(), $attempt->userid);

    // Fully load all the questions in this flipquiz.
    $flipquizobj->preload_questions();
    $flipquizobj->load_questions();

    // First load all the non-random questions.
    $randomfound = false;
    $slot = 0;
    $questions = array();
    $maxmark = array();
    $page = array();
    foreach ($flipquizobj->get_questions() as $questiondata) {
        $slot += 1;
        $maxmark[$slot] = $questiondata->maxmark;
        $page[$slot] = $questiondata->page;
        if ($questiondata->qtype == 'random') {
            $randomfound = true;
            continue;
        }
        if (!$flipquizobj->get_flipquiz()->shuffleanswers) {
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

        foreach ($flipquizobj->get_questions() as $questiondata) {
            $slot += 1;
            if ($questiondata->qtype != 'random') {
                continue;
            }

            $tagids = flipquiz_retrieve_slot_tag_ids($questiondata->slotid);

            // Deal with fixed random choices for testing.
            if (isset($questionids[$quba->next_slot_number()])) {
                if ($randomloader->is_question_available($questiondata->category,
                        (bool) $questiondata->questiontext, $questionids[$quba->next_slot_number()], $tagids)) {
                    $questions[$slot] = question_bank::load_question(
                            $questionids[$quba->next_slot_number()], $flipquizobj->get_flipquiz()->shuffleanswers);
                    continue;
                } else {
                    throw new coding_exception('Forced question id not available.');
                }
            }

            // Normal case, pick one at random.
            $questionid = $randomloader->get_next_question_id($questiondata->randomfromcategory,
                    $questiondata->randomincludingsubcategories, $tagids);
            if ($questionid === null) {
                throw new moodle_exception('notenoughrandomquestions', 'flipquiz',
                                           $flipquizobj->view_url(), $questiondata);
            }

            $questions[$slot] = question_bank::load_question($questionid,
                    $flipquizobj->get_flipquiz()->shuffleanswers);
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
    $sections = $flipquizobj->get_sections();
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
                if ($questionsonthispage && $questionsonthispage == $flipquizobj->get_flipquiz()->questionsperpage) {
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
function flipquiz_start_attempt_built_on_last($quba, $attempt, $lastattempt) {
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
 * The save started question usage and flipquiz attempt in db and log the started attempt.
 *
 * @param flipquiz                       $flipquizobj
 * @param question_usage_by_activity $quba
 * @param object                     $attempt
 * @return object                    attempt object with uniqueid and id set.
 */
function flipquiz_attempt_save_started($flipquizobj, $quba, $attempt) {
    global $DB;
    // Save the attempt in the database.
    question_engine::save_questions_usage_by_activity($quba);
    $attempt->uniqueid = $quba->get_id();
    $attempt->id = $DB->insert_record('flipquiz_attempts', $attempt);

    // Params used by the events below.
    $params = array(
        'objectid' => $attempt->id,
        'relateduserid' => $attempt->userid,
        'courseid' => $flipquizobj->get_courseid(),
        'context' => $flipquizobj->get_context()
    );
    // Decide which event we are using.
    if ($attempt->preview) {
        $params['other'] = array(
            'flipquizid' => $flipquizobj->get_flipquizid()
        );
        $event = \mod_flipquiz\event\attempt_preview_started::create($params);
    } else {
        $event = \mod_flipquiz\event\attempt_started::create($params);

    }

    // Trigger the event.
    $event->add_record_snapshot('flipquiz', $flipquizobj->get_flipquiz());
    $event->add_record_snapshot('flipquiz_attempts', $attempt);
    $event->trigger();

    return $attempt;
}

/**
 * Returns an unfinished attempt (if there is one) for the given
 * user on the given flipquiz. This function does not return preview attempts.
 *
 * @param int $flipquizid the id of the flipquiz.
 * @param int $userid the id of the user.
 *
 * @return mixed the unfinished attempt if there is one, false if not.
 */
function flipquiz_get_user_attempt_unfinished($flipquizid, $userid) {
    $attempts = flipquiz_get_user_attempts($flipquizid, $userid, 'unfinished', true);
    if ($attempts) {
        return array_shift($attempts);
    } else {
        return false;
    }
}

/**
 * Delete a flipquiz attempt.
 * @param mixed $attempt an integer attempt id or an attempt object
 *      (row of the flipquiz_attempts table).
 * @param object $flipquiz the flipquiz object.
 */
function flipquiz_delete_attempt($attempt, $flipquiz) {
    global $DB;
    if (is_numeric($attempt)) {
        if (!$attempt = $DB->get_record('flipquiz_attempts', array('id' => $attempt))) {
            return;
        }
    }

    if ($attempt->flipquiz != $flipquiz->id) {
        debugging("Trying to delete attempt $attempt->id which belongs to flipquiz $attempt->flipquiz " .
                "but was passed flipquiz $flipquiz->id.");
        return;
    }

    if (!isset($flipquiz->cmid)) {
        $cm = get_coursemodule_from_instance('flipquiz', $flipquiz->id, $flipquiz->course);
        $flipquiz->cmid = $cm->id;
    }

    question_engine::delete_questions_usage_by_activity($attempt->uniqueid);
    $DB->delete_records('flipquiz_attempts', array('id' => $attempt->id));

    // Log the deletion of the attempt if not a preview.
    if (!$attempt->preview) {
        $params = array(
            'objectid' => $attempt->id,
            'relateduserid' => $attempt->userid,
            'context' => context_module::instance($flipquiz->cmid),
            'other' => array(
                'flipquizid' => $flipquiz->id
            )
        );
        $event = \mod_flipquiz\event\attempt_deleted::create($params);
        $event->add_record_snapshot('flipquiz_attempts', $attempt);
        $event->trigger();
    }

    // Search flipquiz_attempts for other instances by this user.
    // If none, then delete record for this flipquiz, this user from flipquiz_grades
    // else recalculate best grade.
    $userid = $attempt->userid;
    if (!$DB->record_exists('flipquiz_attempts', array('userid' => $userid, 'flipquiz' => $flipquiz->id))) {
        $DB->delete_records('flipquiz_grades', array('userid' => $userid, 'flipquiz' => $flipquiz->id));
    } else {
        flipquiz_save_best_grade($flipquiz, $userid);
    }

    flipquiz_update_grades($flipquiz, $userid);
}

/**
 * Delete all the preview attempts at a flipquiz, or possibly all the attempts belonging
 * to one user.
 * @param object $flipquiz the flipquiz object.
 * @param int $userid (optional) if given, only delete the previews belonging to this user.
 */
function flipquiz_delete_previews($flipquiz, $userid = null) {
    global $DB;
    $conditions = array('flipquiz' => $flipquiz->id, 'preview' => 1);
    if (!empty($userid)) {
        $conditions['userid'] = $userid;
    }
    $previewattempts = $DB->get_records('flipquiz_attempts', $conditions);
    foreach ($previewattempts as $attempt) {
        flipquiz_delete_attempt($attempt, $flipquiz);
    }
}

/**
 * @param int $flipquizid The flipquiz id.
 * @return bool whether this flipquiz has any (non-preview) attempts.
 */
function flipquiz_has_attempts($flipquizid) {
    global $DB;
    return $DB->record_exists('flipquiz_attempts', array('flipquiz' => $flipquizid, 'preview' => 0));
}

// Functions to do with flipquiz layout and pages //////////////////////////////////

/**
 * Repaginate the questions in a flipquiz
 * @param int $flipquizid the id of the flipquiz to repaginate.
 * @param int $slotsperpage number of items to put on each page. 0 means unlimited.
 */
function flipquiz_repaginate_questions($flipquizid, $slotsperpage) {
    global $DB;
    $trans = $DB->start_delegated_transaction();

    $sections = $DB->get_records('flipquiz_sections', array('flipquizid' => $flipquizid), 'firstslot ASC');
    $firstslots = array();
    foreach ($sections as $section) {
        if ((int)$section->firstslot === 1) {
            continue;
        }
        $firstslots[] = $section->firstslot;
    }

    $slots = $DB->get_records('flipquiz_slots', array('flipquizid' => $flipquizid),
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
            $DB->set_field('flipquiz_slots', 'page', $currentpage, array('id' => $slot->id));
        }
        $slotsonthispage += 1;
    }

    $trans->allow_commit();
}

// Functions to do with flipquiz grades ////////////////////////////////////////////

/**
 * Convert the raw grade stored in $attempt into a grade out of the maximum
 * grade for this flipquiz.
 *
 * @param float $rawgrade the unadjusted grade, fof example $attempt->sumgrades
 * @param object $flipquiz the flipquiz object. Only the fields grade, sumgrades and decimalpoints are used.
 * @param bool|string $format whether to format the results for display
 *      or 'question' to format a question grade (different number of decimal places.
 * @return float|string the rescaled grade, or null/the lang string 'notyetgraded'
 *      if the $grade is null.
 */
function flipquiz_rescale_grade($rawgrade, $flipquiz, $format = true) {
    if (is_null($rawgrade)) {
        $grade = null;
    } else if ($flipquiz->sumgrades >= 0.000005) {
        $grade = $rawgrade * $flipquiz->grade / $flipquiz->sumgrades;
    } else {
        $grade = 0;
    }
    if ($format === 'question') {
        $grade = flipquiz_format_question_grade($flipquiz, $grade);
    } else if ($format) {
        $grade = flipquiz_format_grade($flipquiz, $grade);
    }
    return $grade;
}

/**
 * Get the feedback object for this grade on this flipquiz.
 *
 * @param float $grade a grade on this flipquiz.
 * @param object $flipquiz the flipquiz settings.
 * @return false|stdClass the record object or false if there is not feedback for the given grade
 * @since  Moodle 3.1
 */
function flipquiz_feedback_record_for_grade($grade, $flipquiz) {
    global $DB;

    // With CBM etc, it is possible to get -ve grades, which would then not match
    // any feedback. Therefore, we replace -ve grades with 0.
    $grade = max($grade, 0);

    $feedback = $DB->get_record_select('flipquiz_feedback',
            'flipquizid = ? AND mingrade <= ? AND ? < maxgrade', array($flipquiz->id, $grade, $grade));

    return $feedback;
}

/**
 * Get the feedback text that should be show to a student who
 * got this grade on this flipquiz. The feedback is processed ready for diplay.
 *
 * @param float $grade a grade on this flipquiz.
 * @param object $flipquiz the flipquiz settings.
 * @param object $context the flipquiz context.
 * @return string the comment that corresponds to this grade (empty string if there is not one.
 */
function flipquiz_feedback_for_grade($grade, $flipquiz, $context) {

    if (is_null($grade)) {
        return '';
    }

    $feedback = flipquiz_feedback_record_for_grade($grade, $flipquiz);

    if (empty($feedback->feedbacktext)) {
        return '';
    }

    // Clean the text, ready for display.
    $formatoptions = new stdClass();
    $formatoptions->noclean = true;
    $feedbacktext = file_rewrite_pluginfile_urls($feedback->feedbacktext, 'pluginfile.php',
            $context->id, 'mod_flipquiz', 'feedback', $feedback->id);
    $feedbacktext = format_text($feedbacktext, $feedback->feedbacktextformat, $formatoptions);

    return $feedbacktext;
}

/**
 * @param object $flipquiz the flipquiz database row.
 * @return bool Whether this flipquiz has any non-blank feedback text.
 */
function flipquiz_has_feedback($flipquiz) {
    global $DB;
    static $cache = array();
    if (!array_key_exists($flipquiz->id, $cache)) {
        $cache[$flipquiz->id] = flipquiz_has_grades($flipquiz) &&
                $DB->record_exists_select('flipquiz_feedback', "flipquizid = ? AND " .
                    $DB->sql_isnotempty('flipquiz_feedback', 'feedbacktext', false, true),
                array($flipquiz->id));
    }
    return $cache[$flipquiz->id];
}

/**
 * Update the sumgrades field of the flipquiz. This needs to be called whenever
 * the grading structure of the flipquiz is changed. For example if a question is
 * added or removed, or a question weight is changed.
 *
 * You should call {@link flipquiz_delete_previews()} before you call this function.
 *
 * @param object $flipquiz a flipquiz.
 */
function flipquiz_update_sumgrades($flipquiz) {
    global $DB;

    $sql = 'UPDATE {flipquiz}
            SET sumgrades = COALESCE((
                SELECT SUM(maxmark)
                FROM {flipquiz_slots}
                WHERE flipquizid = {flipquiz}.id
            ), 0)
            WHERE id = ?';
    $DB->execute($sql, array($flipquiz->id));
    $flipquiz->sumgrades = $DB->get_field('flipquiz', 'sumgrades', array('id' => $flipquiz->id));

    if ($flipquiz->sumgrades < 0.000005 && flipquiz_has_attempts($flipquiz->id)) {
        // If the flipquiz has been attempted, and the sumgrades has been
        // set to 0, then we must also set the maximum possible grade to 0, or
        // we will get a divide by zero error.
        flipquiz_set_grade(0, $flipquiz);
    }
}

/**
 * Update the sumgrades field of the attempts at a flipquiz.
 *
 * @param object $flipquiz a flipquiz.
 */
function flipquiz_update_all_attempt_sumgrades($flipquiz) {
    global $DB;
    $dm = new question_engine_data_mapper();
    $timenow = time();

    $sql = "UPDATE {flipquiz_attempts}
            SET
                timemodified = :timenow,
                sumgrades = (
                    {$dm->sum_usage_marks_subquery('uniqueid')}
                )
            WHERE flipquiz = :flipquizid AND state = :finishedstate";
    $DB->execute($sql, array('timenow' => $timenow, 'flipquizid' => $flipquiz->id,
            'finishedstate' => flipquiz_attempt::FINISHED));
}

/**
 * The flipquiz grade is the maximum that student's results are marked out of. When it
 * changes, the corresponding data in flipquiz_grades and flipquiz_feedback needs to be
 * rescaled. After calling this function, you probably need to call
 * flipquiz_update_all_attempt_sumgrades, flipquiz_update_all_final_grades and
 * flipquiz_update_grades.
 *
 * @param float $newgrade the new maximum grade for the flipquiz.
 * @param object $flipquiz the flipquiz we are updating. Passed by reference so its
 *      grade field can be updated too.
 * @return bool indicating success or failure.
 */
function flipquiz_set_grade($newgrade, $flipquiz) {
    global $DB;
    // This is potentially expensive, so only do it if necessary.
    if (abs($flipquiz->grade - $newgrade) < 1e-7) {
        // Nothing to do.
        return true;
    }

    $oldgrade = $flipquiz->grade;
    $flipquiz->grade = $newgrade;

    // Use a transaction, so that on those databases that support it, this is safer.
    $transaction = $DB->start_delegated_transaction();

    // Update the flipquiz table.
    $DB->set_field('flipquiz', 'grade', $newgrade, array('id' => $flipquiz->instance));

    if ($oldgrade < 1) {
        // If the old grade was zero, we cannot rescale, we have to recompute.
        // We also recompute if the old grade was too small to avoid underflow problems.
        flipquiz_update_all_final_grades($flipquiz);

    } else {
        // We can rescale the grades efficiently.
        $timemodified = time();
        $DB->execute("
                UPDATE {flipquiz_grades}
                SET grade = ? * grade, timemodified = ?
                WHERE flipquiz = ?
        ", array($newgrade/$oldgrade, $timemodified, $flipquiz->id));
    }

    if ($oldgrade > 1e-7) {
        // Update the flipquiz_feedback table.
        $factor = $newgrade/$oldgrade;
        $DB->execute("
                UPDATE {flipquiz_feedback}
                SET mingrade = ? * mingrade, maxgrade = ? * maxgrade
                WHERE flipquizid = ?
        ", array($factor, $factor, $flipquiz->id));
    }

    // Update grade item and send all grades to gradebook.
    flipquiz_grade_item_update($flipquiz);
    flipquiz_update_grades($flipquiz);

    $transaction->allow_commit();
    return true;
}

/**
 * Save the overall grade for a user at a flipquiz in the flipquiz_grades table
 *
 * @param object $flipquiz The flipquiz for which the best grade is to be calculated and then saved.
 * @param int $userid The userid to calculate the grade for. Defaults to the current user.
 * @param array $attempts The attempts of this user. Useful if you are
 * looping through many users. Attempts can be fetched in one master query to
 * avoid repeated querying.
 * @return bool Indicates success or failure.
 */
function flipquiz_save_best_grade($flipquiz, $userid = null, $attempts = array()) {
    global $DB, $OUTPUT, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    if (!$attempts) {
        // Get all the attempts made by the user.
        $attempts = flipquiz_get_user_attempts($flipquiz->id, $userid);
    }

    // Calculate the best grade.
    $bestgrade = flipquiz_calculate_best_grade($flipquiz, $attempts);
    $bestgrade = flipquiz_rescale_grade($bestgrade, $flipquiz, false);

    // Save the best grade in the database.
    if (is_null($bestgrade)) {
        $DB->delete_records('flipquiz_grades', array('flipquiz' => $flipquiz->id, 'userid' => $userid));

    } else if ($grade = $DB->get_record('flipquiz_grades',
            array('flipquiz' => $flipquiz->id, 'userid' => $userid))) {
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->update_record('flipquiz_grades', $grade);

    } else {
        $grade = new stdClass();
        $grade->flipquiz = $flipquiz->id;
        $grade->userid = $userid;
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->insert_record('flipquiz_grades', $grade);
    }

    flipquiz_update_grades($flipquiz, $userid);
}

/**
 * Calculate the overall grade for a flipquiz given a number of attempts by a particular user.
 *
 * @param object $flipquiz    the flipquiz settings object.
 * @param array $attempts an array of all the user's attempts at this flipquiz in order.
 * @return float          the overall grade
 */
function flipquiz_calculate_best_grade($flipquiz, $attempts) {

    switch ($flipquiz->grademethod) {

        case FLIPQUIZ_ATTEMPTFIRST:
            $firstattempt = reset($attempts);
            return $firstattempt->sumgrades;

        case FLIPQUIZ_ATTEMPTLAST:
            $lastattempt = end($attempts);
            return $lastattempt->sumgrades;

        case FLIPQUIZ_GRADEAVERAGE:
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

        case FLIPQUIZ_GRADEHIGHEST:
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
 * Update the final grade at this flipquiz for all students.
 *
 * This function is equivalent to calling flipquiz_save_best_grade for all
 * users, but much more efficient.
 *
 * @param object $flipquiz the flipquiz settings.
 */
function flipquiz_update_all_final_grades($flipquiz) {
    global $DB;

    if (!$flipquiz->sumgrades) {
        return;
    }

    $param = array('iflipquizid' => $flipquiz->id, 'istatefinished' => flipquiz_attempt::FINISHED);
    $firstlastattemptjoin = "JOIN (
            SELECT
                iflipquiza.userid,
                MIN(attempt) AS firstattempt,
                MAX(attempt) AS lastattempt

            FROM {flipquiz_attempts} iflipquiza

            WHERE
                iflipquiza.state = :istatefinished AND
                iflipquiza.preview = 0 AND
                iflipquiza.flipquiz = :iflipquizid

            GROUP BY iflipquiza.userid
        ) first_last_attempts ON first_last_attempts.userid = flipquiza.userid";

    switch ($flipquiz->grademethod) {
        case FLIPQUIZ_ATTEMPTFIRST:
            // Because of the where clause, there will only be one row, but we
            // must still use an aggregate function.
            $select = 'MAX(flipquiza.sumgrades)';
            $join = $firstlastattemptjoin;
            $where = 'flipquiza.attempt = first_last_attempts.firstattempt AND';
            break;

        case FLIPQUIZ_ATTEMPTLAST:
            // Because of the where clause, there will only be one row, but we
            // must still use an aggregate function.
            $select = 'MAX(flipquiza.sumgrades)';
            $join = $firstlastattemptjoin;
            $where = 'flipquiza.attempt = first_last_attempts.lastattempt AND';
            break;

        case FLIPQUIZ_GRADEAVERAGE:
            $select = 'AVG(flipquiza.sumgrades)';
            $join = '';
            $where = '';
            break;

        default:
        case FLIPQUIZ_GRADEHIGHEST:
            $select = 'MAX(flipquiza.sumgrades)';
            $join = '';
            $where = '';
            break;
    }

    if ($flipquiz->sumgrades >= 0.000005) {
        $finalgrade = $select . ' * ' . ($flipquiz->grade / $flipquiz->sumgrades);
    } else {
        $finalgrade = '0';
    }
    $param['flipquizid'] = $flipquiz->id;
    $param['flipquizid2'] = $flipquiz->id;
    $param['flipquizid3'] = $flipquiz->id;
    $param['flipquizid4'] = $flipquiz->id;
    $param['statefinished'] = flipquiz_attempt::FINISHED;
    $param['statefinished2'] = flipquiz_attempt::FINISHED;
    $finalgradesubquery = "
            SELECT flipquiza.userid, $finalgrade AS newgrade
            FROM {flipquiz_attempts} flipquiza
            $join
            WHERE
                $where
                flipquiza.state = :statefinished AND
                flipquiza.preview = 0 AND
                flipquiza.flipquiz = :flipquizid3
            GROUP BY flipquiza.userid";

    $changedgrades = $DB->get_records_sql("
            SELECT users.userid, qg.id, qg.grade, newgrades.newgrade

            FROM (
                SELECT userid
                FROM {flipquiz_grades} qg
                WHERE flipquiz = :flipquizid
            UNION
                SELECT DISTINCT userid
                FROM {flipquiz_attempts} flipquiza2
                WHERE
                    flipquiza2.state = :statefinished2 AND
                    flipquiza2.preview = 0 AND
                    flipquiza2.flipquiz = :flipquizid2
            ) users

            LEFT JOIN {flipquiz_grades} qg ON qg.userid = users.userid AND qg.flipquiz = :flipquizid4

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
            $toinsert->flipquiz = $flipquiz->id;
            $toinsert->userid = $changedgrade->userid;
            $toinsert->timemodified = $timenow;
            $toinsert->grade = $changedgrade->newgrade;
            $DB->insert_record('flipquiz_grades', $toinsert);

        } else {
            $toupdate = new stdClass();
            $toupdate->id = $changedgrade->id;
            $toupdate->grade = $changedgrade->newgrade;
            $toupdate->timemodified = $timenow;
            $DB->update_record('flipquiz_grades', $toupdate);
        }
    }

    if (!empty($todelete)) {
        list($test, $params) = $DB->get_in_or_equal($todelete);
        $DB->delete_records_select('flipquiz_grades', 'flipquiz = ? AND userid ' . $test,
                array_merge(array($flipquiz->id), $params));
    }
}

/**
 * Efficiently update check state time on all open attempts
 *
 * @param array $conditions optional restrictions on which attempts to update
 *                    Allowed conditions:
 *                      courseid => (array|int) attempts in given course(s)
 *                      userid   => (array|int) attempts for given user(s)
 *                      flipquizid   => (array|int) attempts in given flipquiz(s)
 *                      groupid  => (array|int) flipquizzes with some override for given group(s)
 *
 */
function flipquiz_update_open_attempts(array $conditions) {
    global $DB;

    foreach ($conditions as &$value) {
        if (!is_array($value)) {
            $value = array($value);
        }
    }

    $params = array();
    $wheres = array("flipquiza.state IN ('inprogress', 'overdue')");
    $iwheres = array("iflipquiza.state IN ('inprogress', 'overdue')");

    if (isset($conditions['courseid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['courseid'], SQL_PARAMS_NAMED, 'cid');
        $params = array_merge($params, $inparams);
        $wheres[] = "flipquiza.flipquiz IN (SELECT q.id FROM {flipquiz} q WHERE q.course $incond)";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['courseid'], SQL_PARAMS_NAMED, 'icid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "iflipquiza.flipquiz IN (SELECT q.id FROM {flipquiz} q WHERE q.course $incond)";
    }

    if (isset($conditions['userid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['userid'], SQL_PARAMS_NAMED, 'uid');
        $params = array_merge($params, $inparams);
        $wheres[] = "flipquiza.userid $incond";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['userid'], SQL_PARAMS_NAMED, 'iuid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "iflipquiza.userid $incond";
    }

    if (isset($conditions['flipquizid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['flipquizid'], SQL_PARAMS_NAMED, 'qid');
        $params = array_merge($params, $inparams);
        $wheres[] = "flipquiza.flipquiz $incond";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['flipquizid'], SQL_PARAMS_NAMED, 'iqid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "iflipquiza.flipquiz $incond";
    }

    if (isset($conditions['groupid'])) {
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['groupid'], SQL_PARAMS_NAMED, 'gid');
        $params = array_merge($params, $inparams);
        $wheres[] = "flipquiza.flipquiz IN (SELECT qo.flipquiz FROM {flipquiz_overrides} qo WHERE qo.groupid $incond)";
        list ($incond, $inparams) = $DB->get_in_or_equal($conditions['groupid'], SQL_PARAMS_NAMED, 'igid');
        $params = array_merge($params, $inparams);
        $iwheres[] = "iflipquiza.flipquiz IN (SELECT qo.flipquiz FROM {flipquiz_overrides} qo WHERE qo.groupid $incond)";
    }

    // SQL to compute timeclose and timelimit for each attempt:
    $flipquizausersql = flipquiz_get_attempt_usertime_sql(
            implode("\n                AND ", $iwheres));

    // SQL to compute the new timecheckstate
    $timecheckstatesql = "
          CASE WHEN flipquizauser.usertimelimit = 0 AND flipquizauser.usertimeclose = 0 THEN NULL
               WHEN flipquizauser.usertimelimit = 0 THEN flipquizauser.usertimeclose
               WHEN flipquizauser.usertimeclose = 0 THEN flipquiza.timestart + flipquizauser.usertimelimit
               WHEN flipquiza.timestart + flipquizauser.usertimelimit < flipquizauser.usertimeclose THEN flipquiza.timestart + flipquizauser.usertimelimit
               ELSE flipquizauser.usertimeclose END +
          CASE WHEN flipquiza.state = 'overdue' THEN flipquiz.graceperiod ELSE 0 END";

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
        $updatesql = "UPDATE {flipquiz_attempts} flipquiza
                        JOIN {flipquiz} flipquiz ON flipquiz.id = flipquiza.flipquiz
                        JOIN ( $flipquizausersql ) flipquizauser ON flipquizauser.id = flipquiza.id
                         SET flipquiza.timecheckstate = $timecheckstatesql
                       WHERE $attemptselect";
    } else if ($dbfamily == 'postgres') {
        $updatesql = "UPDATE {flipquiz_attempts} flipquiza
                         SET timecheckstate = $timecheckstatesql
                        FROM {flipquiz} flipquiz, ( $flipquizausersql ) flipquizauser
                       WHERE flipquiz.id = flipquiza.flipquiz
                         AND flipquizauser.id = flipquiza.id
                         AND $attemptselect";
    } else if ($dbfamily == 'mssql') {
        $updatesql = "UPDATE flipquiza
                         SET timecheckstate = $timecheckstatesql
                        FROM {flipquiz_attempts} flipquiza
                        JOIN {flipquiz} flipquiz ON flipquiz.id = flipquiza.flipquiz
                        JOIN ( $flipquizausersql ) flipquizauser ON flipquizauser.id = flipquiza.id
                       WHERE $attemptselect";
    } else {
        // oracle, sqlite and others
        $updatesql = "UPDATE {flipquiz_attempts} flipquiza
                         SET timecheckstate = (
                           SELECT $timecheckstatesql
                             FROM {flipquiz} flipquiz, ( $flipquizausersql ) flipquizauser
                            WHERE flipquiz.id = flipquiza.flipquiz
                              AND flipquizauser.id = flipquiza.id
                         )
                         WHERE $attemptselect";
    }

    $DB->execute($updatesql, $params);
}

/**
 * Returns SQL to compute timeclose and timelimit for every attempt, taking into account user and group overrides.
 * The query used herein is very similar to the one in function flipquiz_get_user_timeclose, so, in case you
 * would change either one of them, make sure to apply your changes to both.
 *
 * @param string $redundantwhereclauses extra where clauses to add to the subquery
 *      for performance. These can use the table alias iflipquiza for the flipquiz attempts table.
 * @return string SQL select with columns attempt.id, usertimeclose, usertimelimit.
 */
function flipquiz_get_attempt_usertime_sql($redundantwhereclauses = '') {
    if ($redundantwhereclauses) {
        $redundantwhereclauses = 'WHERE ' . $redundantwhereclauses;
    }
    // The multiple qgo JOINS are necessary because we want timeclose/timelimit = 0 (unlimited) to supercede
    // any other group override
    $flipquizausersql = "
          SELECT iflipquiza.id,
           COALESCE(MAX(quo.timeclose), MAX(qgo1.timeclose), MAX(qgo2.timeclose), iflipquiz.timeclose) AS usertimeclose,
           COALESCE(MAX(quo.timelimit), MAX(qgo3.timelimit), MAX(qgo4.timelimit), iflipquiz.timelimit) AS usertimelimit

           FROM {flipquiz_attempts} iflipquiza
           JOIN {flipquiz} iflipquiz ON iflipquiz.id = iflipquiza.flipquiz
      LEFT JOIN {flipquiz_overrides} quo ON quo.flipquiz = iflipquiza.flipquiz AND quo.userid = iflipquiza.userid
      LEFT JOIN {groups_members} gm ON gm.userid = iflipquiza.userid
      LEFT JOIN {flipquiz_overrides} qgo1 ON qgo1.flipquiz = iflipquiza.flipquiz AND qgo1.groupid = gm.groupid AND qgo1.timeclose = 0
      LEFT JOIN {flipquiz_overrides} qgo2 ON qgo2.flipquiz = iflipquiza.flipquiz AND qgo2.groupid = gm.groupid AND qgo2.timeclose > 0
      LEFT JOIN {flipquiz_overrides} qgo3 ON qgo3.flipquiz = iflipquiza.flipquiz AND qgo3.groupid = gm.groupid AND qgo3.timelimit = 0
      LEFT JOIN {flipquiz_overrides} qgo4 ON qgo4.flipquiz = iflipquiza.flipquiz AND qgo4.groupid = gm.groupid AND qgo4.timelimit > 0
          $redundantwhereclauses
       GROUP BY iflipquiza.id, iflipquiz.id, iflipquiz.timeclose, iflipquiz.timelimit";
    return $flipquizausersql;
}

/**
 * Return the attempt with the best grade for a flipquiz
 *
 * Which attempt is the best depends on $flipquiz->grademethod. If the grade
 * method is GRADEAVERAGE then this function simply returns the last attempt.
 * @return object         The attempt with the best grade
 * @param object $flipquiz    The flipquiz for which the best grade is to be calculated
 * @param array $attempts An array of all the attempts of the user at the flipquiz
 */
function flipquiz_calculate_best_attempt($flipquiz, $attempts) {

    switch ($flipquiz->grademethod) {

        case FLIPQUIZ_ATTEMPTFIRST:
            foreach ($attempts as $attempt) {
                return $attempt;
            }
            break;

        case FLIPQUIZ_GRADEAVERAGE: // We need to do something with it.
        case FLIPQUIZ_ATTEMPTLAST:
            foreach ($attempts as $attempt) {
                $final = $attempt;
            }
            return $final;

        default:
        case FLIPQUIZ_GRADEHIGHEST:
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
 * @return array int => lang string the options for calculating the flipquiz grade
 *      from the individual attempt grades.
 */
function flipquiz_get_grading_options() {
    return array(
        FLIPQUIZ_GRADEHIGHEST => get_string('gradehighest', 'flipquiz'),
        FLIPQUIZ_GRADEAVERAGE => get_string('gradeaverage', 'flipquiz'),
        FLIPQUIZ_ATTEMPTFIRST => get_string('attemptfirst', 'flipquiz'),
        FLIPQUIZ_ATTEMPTLAST  => get_string('attemptlast', 'flipquiz')
    );
}

/**
 * @param int $option one of the values FLIPQUIZ_GRADEHIGHEST, FLIPQUIZ_GRADEAVERAGE,
 *      FLIPQUIZ_ATTEMPTFIRST or FLIPQUIZ_ATTEMPTLAST.
 * @return the lang string for that option.
 */
function flipquiz_get_grading_option_name($option) {
    $strings = flipquiz_get_grading_options();
    return $strings[$option];
}

/**
 * @return array string => lang string the options for handling overdue flipquiz
 *      attempts.
 */
function flipquiz_get_overdue_handling_options() {
    return array(
        'autosubmit'  => get_string('overduehandlingautosubmit', 'flipquiz'),
        'graceperiod' => get_string('overduehandlinggraceperiod', 'flipquiz'),
        'autoabandon' => get_string('overduehandlingautoabandon', 'flipquiz'),
    );
}

/**
 * Get the choices for what size user picture to show.
 * @return array string => lang string the options for whether to display the user's picture.
 */
function flipquiz_get_user_image_options() {
    return array(
        FLIPQUIZ_SHOWIMAGE_NONE  => get_string('shownoimage', 'flipquiz'),
        FLIPQUIZ_SHOWIMAGE_SMALL => get_string('showsmallimage', 'flipquiz'),
        FLIPQUIZ_SHOWIMAGE_LARGE => get_string('showlargeimage', 'flipquiz'),
    );
}

/**
 * Return an user's timeclose for all flipquizzes in a course, hereby taking into account group and user overrides.
 *
 * @param int $courseid the course id.
 * @return object An object with of all flipquizids and close unixdates in this course, taking into account the most lenient
 * overrides, if existing and 0 if no close date is set.
 */
function flipquiz_get_user_timeclose($courseid) {
    global $DB, $USER;

    // For teacher and manager/admins return timeclose.
    if (has_capability('moodle/course:update', context_course::instance($courseid))) {
        $sql = "SELECT flipquiz.id, flipquiz.timeclose AS usertimeclose
                  FROM {flipquiz} flipquiz
                 WHERE flipquiz.course = :courseid";

        $results = $DB->get_records_sql($sql, array('courseid' => $courseid));
        return $results;
    }

    $sql = "SELECT q.id,
  COALESCE(v.userclose, v.groupclose, q.timeclose, 0) AS usertimeclose
  FROM (
      SELECT flipquiz.id as flipquizid,
             MAX(quo.timeclose) AS userclose, MAX(qgo.timeclose) AS groupclose
       FROM {flipquiz} flipquiz
  LEFT JOIN {flipquiz_overrides} quo on flipquiz.id = quo.flipquiz AND quo.userid = :userid
  LEFT JOIN {groups_members} gm ON gm.userid = :useringroupid
  LEFT JOIN {flipquiz_overrides} qgo on flipquiz.id = qgo.flipquiz AND qgo.groupid = gm.groupid
      WHERE flipquiz.course = :courseid
   GROUP BY flipquiz.id) v
       JOIN {flipquiz} q ON q.id = v.flipquizid";

    $results = $DB->get_records_sql($sql, array('userid' => $USER->id, 'useringroupid' => $USER->id, 'courseid' => $courseid));
    return $results;

}

/**
 * Get the choices to offer for the 'Questions per page' option.
 * @return array int => string.
 */
function flipquiz_questions_per_page_options() {
    $pageoptions = array();
    $pageoptions[0] = get_string('neverallononepage', 'flipquiz');
    $pageoptions[1] = get_string('everyquestion', 'flipquiz');
    for ($i = 2; $i <= FLIPQUIZ_MAX_QPP_OPTION; ++$i) {
        $pageoptions[$i] = get_string('everynquestions', 'flipquiz', $i);
    }
    return $pageoptions;
}

/**
 * Get the human-readable name for a flipquiz attempt state.
 * @param string $state one of the state constants like {@link flipquiz_attempt::IN_PROGRESS}.
 * @return string The lang string to describe that state.
 */
function flipquiz_attempt_state_name($state) {
    switch ($state) {
        case flipquiz_attempt::IN_PROGRESS:
            return get_string('stateinprogress', 'flipquiz');
        case flipquiz_attempt::OVERDUE:
            return get_string('stateoverdue', 'flipquiz');
        case flipquiz_attempt::FINISHED:
            return get_string('statefinished', 'flipquiz');
        case flipquiz_attempt::ABANDONED:
            return get_string('stateabandoned', 'flipquiz');
        default:
            throw new coding_exception('Unknown flipquiz attempt state.');
    }
}

// Other flipquiz functions ////////////////////////////////////////////////////////

/**
 * @param object $flipquiz the flipquiz.
 * @param int $cmid the course_module object for this flipquiz.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @param int $variant which question variant to preview (optional).
 * @return string html for a number of icons linked to action pages for a
 * question - preview and edit / view icons depending on user capabilities.
 */
function flipquiz_question_action_icons($flipquiz, $cmid, $question, $returnurl, $variant = null) {
    $html = flipquiz_question_preview_button($flipquiz, $question, false, $variant) . ' ' .
            flipquiz_question_edit_button($cmid, $question, $returnurl);
    return $html;
}

/**
 * @param int $cmid the course_module.id for this flipquiz.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @param string $contentbeforeicon some HTML content to be added inside the link, before the icon.
 * @return the HTML for an edit icon, view icon, or nothing for a question
 *      (depending on permissions).
 */
function flipquiz_question_edit_button($cmid, $question, $returnurl, $contentaftericon = '') {
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
 * @param object $flipquiz the flipquiz settings
 * @param object $question the question
 * @param int $variant which question variant to preview (optional).
 * @return moodle_url to preview this question with the options from this flipquiz.
 */
function flipquiz_question_preview_url($flipquiz, $question, $variant = null) {
    // Get the appropriate display options.
    $displayoptions = mod_flipquiz_display_options::make_from_flipquiz($flipquiz,
            mod_flipquiz_display_options::DURING);

    $maxmark = null;
    if (isset($question->maxmark)) {
        $maxmark = $question->maxmark;
    }

    // Work out the correcte preview URL.
    return question_preview_url($question->id, $flipquiz->preferredbehaviour,
            $maxmark, $displayoptions, $variant);
}

/**
 * @param object $flipquiz the flipquiz settings
 * @param object $question the question
 * @param bool $label if true, show the preview question label after the icon
 * @param int $variant which question variant to preview (optional).
 * @return the HTML for a preview question icon.
 */
function flipquiz_question_preview_button($flipquiz, $question, $label = false, $variant = null) {
    global $PAGE;
    if (!question_has_capability_on($question, 'use')) {
        return '';
    }

    return $PAGE->get_renderer('mod_flipquiz', 'edit')->question_preview_icon($flipquiz, $question, $label, $variant);
}

/**
 * @param object $attempt the attempt.
 * @param object $context the flipquiz context.
 * @return int whether flags should be shown/editable to the current user for this attempt.
 */
function flipquiz_get_flag_option($attempt, $context) {
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
 * Work out what state this flipquiz attempt is in - in the sense used by
 * flipquiz_get_review_options, not in the sense of $attempt->state.
 * @param object $flipquiz the flipquiz settings
 * @param object $attempt the flipquiz_attempt database row.
 * @return int one of the mod_flipquiz_display_options::DURING,
 *      IMMEDIATELY_AFTER, LATER_WHILE_OPEN or AFTER_CLOSE constants.
 */
function flipquiz_attempt_state($flipquiz, $attempt) {
    if ($attempt->state == flipquiz_attempt::IN_PROGRESS) {
        return mod_flipquiz_display_options::DURING;
    } else if ($flipquiz->timeclose && time() >= $flipquiz->timeclose) {
        return mod_flipquiz_display_options::AFTER_CLOSE;
    } else if (time() < $attempt->timefinish + 120) {
        return mod_flipquiz_display_options::IMMEDIATELY_AFTER;
    } else {
        return mod_flipquiz_display_options::LATER_WHILE_OPEN;
    }
}

/**
 * The the appropraite mod_flipquiz_display_options object for this attempt at this
 * flipquiz right now.
 *
 * @param object $flipquiz the flipquiz instance.
 * @param object $attempt the attempt in question.
 * @param $context the flipquiz context.
 *
 * @return mod_flipquiz_display_options
 */
function flipquiz_get_review_options($flipquiz, $attempt, $context) {
    $options = mod_flipquiz_display_options::make_from_flipquiz($flipquiz, flipquiz_attempt_state($flipquiz, $attempt));

    $options->readonly = true;
    $options->flags = flipquiz_get_flag_option($attempt, $context);
    if (!empty($attempt->id)) {
        $options->questionreviewlink = new moodle_url('/mod/flipquiz/reviewquestion.php',
                array('attempt' => $attempt->id));
    }

    // Show a link to the comment box only for closed attempts.
    if (!empty($attempt->id) && $attempt->state == flipquiz_attempt::FINISHED && !$attempt->preview &&
            !is_null($context) && has_capability('mod/flipquiz:grade', $context)) {
        $options->manualcomment = question_display_options::VISIBLE;
        $options->manualcommentlink = new moodle_url('/mod/flipquiz/comment.php',
                array('attempt' => $attempt->id));
    }

    if (!is_null($context) && !$attempt->preview &&
            has_capability('mod/flipquiz:viewreports', $context) &&
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
 * Combines the review options from a number of different flipquiz attempts.
 * Returns an array of two ojects, so the suggested way of calling this
 * funciton is:
 * list($someoptions, $alloptions) = flipquiz_get_combined_reviewoptions(...)
 *
 * @param object $flipquiz the flipquiz instance.
 * @param array $attempts an array of attempt objects.
 *
 * @return array of two options objects, one showing which options are true for
 *          at least one of the attempts, the other showing which options are true
 *          for all attempts.
 */
function flipquiz_get_combined_reviewoptions($flipquiz, $attempts) {
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
        $attemptoptions = mod_flipquiz_display_options::make_from_flipquiz($flipquiz,
                flipquiz_attempt_state($flipquiz, $attempt));
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
function flipquiz_send_confirmation($recipient, $a) {

    // Add information about the recipient to $a.
    // Don't do idnumber. we want idnumber to be the submitter's idnumber.
    $a->username     = fullname($recipient);
    $a->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->courseid          = $a->courseid;
    $eventdata->component         = 'mod_flipquiz';
    $eventdata->name              = 'confirmation';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = core_user::get_noreply_user();
    $eventdata->userto            = $recipient;
    $eventdata->subject           = get_string('emailconfirmsubject', 'flipquiz', $a);
    $eventdata->fullmessage       = get_string('emailconfirmbody', 'flipquiz', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = get_string('emailconfirmsmall', 'flipquiz', $a);
    $eventdata->contexturl        = $a->flipquizurl;
    $eventdata->contexturlname    = $a->flipquizname;

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
function flipquiz_send_notification($recipient, $submitter, $a) {

    // Recipient info for template.
    $a->useridnumber = $recipient->idnumber;
    $a->username     = fullname($recipient);
    $a->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->courseid          = $a->courseid;
    $eventdata->component         = 'mod_flipquiz';
    $eventdata->name              = 'submission';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = $submitter;
    $eventdata->userto            = $recipient;
    $eventdata->subject           = get_string('emailnotifysubject', 'flipquiz', $a);
    $eventdata->fullmessage       = get_string('emailnotifybody', 'flipquiz', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = get_string('emailnotifysmall', 'flipquiz', $a);
    $eventdata->contexturl        = $a->flipquizreviewurl;
    $eventdata->contexturlname    = $a->flipquizname;

    // ... and send it.
    return message_send($eventdata);
}

/**
 * Send all the requried messages when a flipquiz attempt is submitted.
 *
 * @param object $course the course
 * @param object $flipquiz the flipquiz
 * @param object $attempt this attempt just finished
 * @param object $context the flipquiz context
 * @param object $cm the coursemodule for this flipquiz
 *
 * @return bool true if all necessary messages were sent successfully, else false.
 */
function flipquiz_send_notification_messages($course, $flipquiz, $attempt, $context, $cm) {
    global $CFG, $DB;

    // Do nothing if required objects not present.
    if (empty($course) or empty($flipquiz) or empty($attempt) or empty($context)) {
        throw new coding_exception('$course, $flipquiz, $attempt, $context and $cm must all be set.');
    }

    $submitter = $DB->get_record('user', array('id' => $attempt->userid), '*', MUST_EXIST);

    // Check for confirmation required.
    $sendconfirm = false;
    $notifyexcludeusers = '';
    if (has_capability('mod/flipquiz:emailconfirmsubmission', $context, $submitter, false)) {
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
        // If the user is not in a group, and the flipquiz is set to group mode,
        // then set $groups to a non-existant id so that only users with
        // 'moodle/site:accessallgroups' get notified.
        $groups = -1;
    } else {
        $groups = '';
    }
    $userstonotify = get_users_by_capability($context, 'mod/flipquiz:emailnotifysubmission',
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
    $a->flipquizname        = $flipquiz->name;
    $a->flipquizreporturl   = $CFG->wwwroot . '/mod/flipquiz/report.php?id=' . $cm->id;
    $a->flipquizreportlink  = '<a href="' . $a->flipquizreporturl . '">' .
            format_string($flipquiz->name) . ' report</a>';
    $a->flipquizurl         = $CFG->wwwroot . '/mod/flipquiz/view.php?id=' . $cm->id;
    $a->flipquizlink        = '<a href="' . $a->flipquizurl . '">' . format_string($flipquiz->name) . '</a>';
    // Attempt info.
    $a->submissiontime  = userdate($attempt->timefinish);
    $a->timetaken       = format_time($attempt->timefinish - $attempt->timestart);
    $a->flipquizreviewurl   = $CFG->wwwroot . '/mod/flipquiz/review.php?attempt=' . $attempt->id;
    $a->flipquizreviewlink  = '<a href="' . $a->flipquizreviewurl . '">' .
            format_string($flipquiz->name) . ' review</a>';
    // Student who sat the flipquiz info.
    $a->studentidnumber = $submitter->idnumber;
    $a->studentname     = fullname($submitter);
    $a->studentusername = $submitter->username;

    $allok = true;

    // Send notifications if required.
    if (!empty($userstonotify)) {
        foreach ($userstonotify as $recipient) {
            $allok = $allok && flipquiz_send_notification($recipient, $submitter, $a);
        }
    }

    // Send confirmation if required. We send the student confirmation last, so
    // that if message sending is being intermittently buggy, which means we send
    // some but not all messages, and then try again later, then teachers may get
    // duplicate messages, but the student will always get exactly one.
    if ($sendconfirm) {
        $allok = $allok && flipquiz_send_confirmation($submitter, $a);
    }

    return $allok;
}

/**
 * Send the notification message when a flipquiz attempt becomes overdue.
 *
 * @param flipquiz_attempt $attemptobj all the data about the flipquiz attempt.
 */
function flipquiz_send_overdue_message($attemptobj) {
    global $CFG, $DB;

    $submitter = $DB->get_record('user', array('id' => $attemptobj->get_userid()), '*', MUST_EXIST);

    if (!$attemptobj->has_capability('mod/flipquiz:emailwarnoverdue', $submitter->id, false)) {
        return; // Message not required.
    }

    if (!$attemptobj->has_response_to_at_least_one_graded_question()) {
        return; // Message not required.
    }

    // Prepare lots of useful information that admins might want to include in
    // the email message.
    $flipquizname = format_string($attemptobj->get_flipquiz_name());

    $deadlines = array();
    if ($attemptobj->get_flipquiz()->timelimit) {
        $deadlines[] = $attemptobj->get_attempt()->timestart + $attemptobj->get_flipquiz()->timelimit;
    }
    if ($attemptobj->get_flipquiz()->timeclose) {
        $deadlines[] = $attemptobj->get_flipquiz()->timeclose;
    }
    $duedate = min($deadlines);
    $graceend = $duedate + $attemptobj->get_flipquiz()->graceperiod;

    $a = new stdClass();
    // Course info.
    $a->courseid           = $attemptobj->get_course()->id;
    $a->coursename         = format_string($attemptobj->get_course()->fullname);
    $a->courseshortname    = format_string($attemptobj->get_course()->shortname);
    // Quiz info.
    $a->flipquizname           = $flipquizname;
    $a->flipquizurl            = $attemptobj->view_url();
    $a->flipquizlink           = '<a href="' . $a->flipquizurl . '">' . $flipquizname . '</a>';
    // Attempt info.
    $a->attemptduedate     = userdate($duedate);
    $a->attemptgraceend    = userdate($graceend);
    $a->attemptsummaryurl  = $attemptobj->summary_url()->out(false);
    $a->attemptsummarylink = '<a href="' . $a->attemptsummaryurl . '">' . $flipquizname . ' review</a>';
    // Student's info.
    $a->studentidnumber    = $submitter->idnumber;
    $a->studentname        = fullname($submitter);
    $a->studentusername    = $submitter->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->courseid          = $a->courseid;
    $eventdata->component         = 'mod_flipquiz';
    $eventdata->name              = 'attempt_overdue';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = core_user::get_noreply_user();
    $eventdata->userto            = $submitter;
    $eventdata->subject           = get_string('emailoverduesubject', 'flipquiz', $a);
    $eventdata->fullmessage       = get_string('emailoverduebody', 'flipquiz', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = get_string('emailoverduesmall', 'flipquiz', $a);
    $eventdata->contexturl        = $a->flipquizurl;
    $eventdata->contexturlname    = $a->flipquizname;

    // Send the message.
    return message_send($eventdata);
}

/**
 * Handle the flipquiz_attempt_submitted event.
 *
 * This sends the confirmation and notification messages, if required.
 *
 * @param object $event the event object.
 */
function flipquiz_attempt_submitted_handler($event) {
    global $DB;

    $course  = $DB->get_record('course', array('id' => $event->courseid));
    $attempt = $event->get_record_snapshot('flipquiz_attempts', $event->objectid);
    $flipquiz    = $event->get_record_snapshot('flipquiz', $attempt->flipquiz);
    $cm      = get_coursemodule_from_id('flipquiz', $event->get_context()->instanceid, $event->courseid);

    if (!($course && $flipquiz && $cm && $attempt)) {
        // Something has been deleted since the event was raised. Therefore, the
        // event is no longer relevant.
        return true;
    }

    // Update completion state.
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && ($flipquiz->completionattemptsexhausted || $flipquiz->completionpass)) {
        $completion->update_state($cm, COMPLETION_COMPLETE, $event->userid);
    }
    return flipquiz_send_notification_messages($course, $flipquiz, $attempt,
            context_module::instance($cm->id), $cm);
}

/**
 * Handle groups_member_added event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_flipquiz\group_observers::group_member_added()}.
 */
function flipquiz_groups_member_added_handler($event) {
    debugging('flipquiz_groups_member_added_handler() is deprecated, please use ' .
        '\mod_flipquiz\group_observers::group_member_added() instead.', DEBUG_DEVELOPER);
    flipquiz_update_open_attempts(array('userid'=>$event->userid, 'groupid'=>$event->groupid));
}

/**
 * Handle groups_member_removed event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_flipquiz\group_observers::group_member_removed()}.
 */
function flipquiz_groups_member_removed_handler($event) {
    debugging('flipquiz_groups_member_removed_handler() is deprecated, please use ' .
        '\mod_flipquiz\group_observers::group_member_removed() instead.', DEBUG_DEVELOPER);
    flipquiz_update_open_attempts(array('userid'=>$event->userid, 'groupid'=>$event->groupid));
}

/**
 * Handle groups_group_deleted event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_flipquiz\group_observers::group_deleted()}.
 */
function flipquiz_groups_group_deleted_handler($event) {
    global $DB;
    debugging('flipquiz_groups_group_deleted_handler() is deprecated, please use ' .
        '\mod_flipquiz\group_observers::group_deleted() instead.', DEBUG_DEVELOPER);
    flipquiz_process_group_deleted_in_course($event->courseid);
}

/**
 * Logic to happen when a/some group(s) has/have been deleted in a course.
 *
 * @param int $courseid The course ID.
 * @return void
 */
function flipquiz_process_group_deleted_in_course($courseid) {
    global $DB;

    // It would be nice if we got the groupid that was deleted.
    // Instead, we just update all flipquizzes with orphaned group overrides.
    $sql = "SELECT o.id, o.flipquiz
              FROM {flipquiz_overrides} o
              JOIN {flipquiz} flipquiz ON flipquiz.id = o.flipquiz
         LEFT JOIN {groups} grp ON grp.id = o.groupid
             WHERE flipquiz.course = :courseid
               AND o.groupid IS NOT NULL
               AND grp.id IS NULL";
    $params = array('courseid' => $courseid);
    $records = $DB->get_records_sql_menu($sql, $params);
    if (!$records) {
        return; // Nothing to do.
    }
    $DB->delete_records_list('flipquiz_overrides', 'id', array_keys($records));
    flipquiz_update_open_attempts(array('flipquizid' => array_unique(array_values($records))));
}

/**
 * Handle groups_members_removed event
 *
 * @param object $event the event object.
 * @deprecated since 2.6, see {@link \mod_flipquiz\group_observers::group_member_removed()}.
 */
function flipquiz_groups_members_removed_handler($event) {
    debugging('flipquiz_groups_members_removed_handler() is deprecated, please use ' .
        '\mod_flipquiz\group_observers::group_member_removed() instead.', DEBUG_DEVELOPER);
    if ($event->userid == 0) {
        flipquiz_update_open_attempts(array('courseid'=>$event->courseid));
    } else {
        flipquiz_update_open_attempts(array('courseid'=>$event->courseid, 'userid'=>$event->userid));
    }
}

/**
 * Get the information about the standard flipquiz JavaScript module.
 * @return array a standard jsmodule structure.
 */
function flipquiz_get_js_module() {
    global $PAGE;

    return array(
        'name' => 'mod_flipquiz',
        'fullpath' => '/mod/flipquiz/module.js',
        'requires' => array('base', 'dom', 'event-delegate', 'event-key',
                'core_question_engine', 'moodle-core-formchangechecker'),
        'strings' => array(
            array('cancel', 'moodle'),
            array('flagged', 'question'),
            array('functiondisabledbysecuremode', 'flipquiz'),
            array('startattempt', 'flipquiz'),
            array('timesup', 'flipquiz'),
            array('changesmadereallygoaway', 'moodle'),
        ),
    );
}


/**
 * An extension of question_display_options that includes the extra options used
 * by the flipquiz.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_flipquiz_display_options extends question_display_options {
    /**#@+
     * @var integer bits used to indicate various times in relation to a
     * flipquiz attempt.
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
     * Set up the various options from the flipquiz settings, and a time constant.
     * @param object $flipquiz the flipquiz settings.
     * @param int $one of the {@link DURING}, {@link IMMEDIATELY_AFTER},
     * {@link LATER_WHILE_OPEN} or {@link AFTER_CLOSE} constants.
     * @return mod_flipquiz_display_options set up appropriately.
     */
    public static function make_from_flipquiz($flipquiz, $when) {
        $options = new self();

        $options->attempt = self::extract($flipquiz->reviewattempt, $when, true, false);
        $options->correctness = self::extract($flipquiz->reviewcorrectness, $when);
        $options->marks = self::extract($flipquiz->reviewmarks, $when,
                self::MARK_AND_MAX, self::MAX_ONLY);
        $options->feedback = self::extract($flipquiz->reviewspecificfeedback, $when);
        $options->generalfeedback = self::extract($flipquiz->reviewgeneralfeedback, $when);
        $options->rightanswer = self::extract($flipquiz->reviewrightanswer, $when);
        $options->overallfeedback = self::extract($flipquiz->reviewoverallfeedback, $when);

        $options->numpartscorrect = $options->feedback;
        $options->manualcomment = $options->feedback;

        if ($flipquiz->questiondecimalpoints != -1) {
            $options->markdp = $flipquiz->questiondecimalpoints;
        } else {
            $options->markdp = $flipquiz->decimalpoints;
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
 * a particular flipquiz.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qubaids_for_flipquiz extends qubaid_join {
    public function __construct($flipquizid, $includepreviews = true, $onlyfinished = false) {
        $where = 'flipquiza.flipquiz = :flipquizaflipquiz';
        $params = array('flipquizaflipquiz' => $flipquizid);

        if (!$includepreviews) {
            $where .= ' AND preview = 0';
        }

        if ($onlyfinished) {
            $where .= ' AND state = :statefinished';
            $params['statefinished'] = flipquiz_attempt::FINISHED;
        }

        parent::__construct('{flipquiz_attempts} flipquiza', 'flipquiza.uniqueid', $where, $params);
    }
}

/**
 * A {@link qubaid_condition} for finding all the question usages belonging to a particular user and flipquiz combination.
 *
 * @copyright  2018 Andrew Nicols <andrwe@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qubaids_for_flipquiz_user extends qubaid_join {
    /**
     * Constructor for this qubaid.
     *
     * @param   int     $flipquizid The flipquiz to search.
     * @param   int     $userid The user to filter on
     * @param   bool    $includepreviews Whether to include preview attempts
     * @param   bool    $onlyfinished Whether to only include finished attempts or not
     */
    public function __construct($flipquizid, $userid, $includepreviews = true, $onlyfinished = false) {
        $where = 'flipquiza.flipquiz = :flipquizaflipquiz AND flipquiza.userid = :flipquizauserid';
        $params = [
            'flipquizaflipquiz' => $flipquizid,
            'flipquizauserid' => $userid,
        ];

        if (!$includepreviews) {
            $where .= ' AND preview = 0';
        }

        if ($onlyfinished) {
            $where .= ' AND state = :statefinished';
            $params['statefinished'] = flipquiz_attempt::FINISHED;
        }

        parent::__construct('{flipquiz_attempts} flipquiza', 'flipquiza.uniqueid', $where, $params);
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
function flipquiz_question_tostring($question, $showicon = false, $showquestiontext = true) {
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
function flipquiz_require_question_use($questionid) {
    global $DB;
    $question = $DB->get_record('question', array('id' => $questionid), '*', MUST_EXIST);
    question_require_capability_on($question, 'use');
}

/**
 * Verify that the question exists, and the user has permission to use it.
 * @param object $flipquiz the flipquiz settings.
 * @param int $slot which question in the flipquiz to test.
 * @return bool whether the user can use this question.
 */
function flipquiz_has_question_use($flipquiz, $slot) {
    global $DB;
    $question = $DB->get_record_sql("
            SELECT q.*
              FROM {flipquiz_slots} slot
              JOIN {question} q ON q.id = slot.questionid
             WHERE slot.flipquizid = ? AND slot.slot = ?", array($flipquiz->id, $slot));
    if (!$question) {
        return false;
    }
    return question_has_capability_on($question, 'use');
}

/**
 * Add a question to a flipquiz
 *
 * Adds a question to a flipquiz by updating $flipquiz as well as the
 * flipquiz and flipquiz_slots tables. It also adds a page break if required.
 * @param int $questionid The id of the question to be added
 * @param object $flipquiz The extended flipquiz object as used by edit.php
 *      This is updated by this function
 * @param int $page Which page in flipquiz to add the question on. If 0 (default),
 *      add at the end
 * @param float $maxmark The maximum mark to set for this question. (Optional,
 *      defaults to question.defaultmark.
 * @return bool false if the question was already in the flipquiz
 */
function flipquiz_add_flipquiz_question($questionid, $flipquiz, $page = 0, $maxmark = null) {
    global $DB;

    // Make sue the question is not of the "random" type.
    $questiontype = $DB->get_field('question', 'qtype', array('id' => $questionid));
    if ($questiontype == 'random') {
        throw new coding_exception(
                'Adding "random" questions via flipquiz_add_flipquiz_question() is deprecated. Please use flipquiz_add_random_questions().'
        );
    }

    $slots = $DB->get_records('flipquiz_slots', array('flipquizid' => $flipquiz->id),
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
    $slot->flipquizid = $flipquiz->id;
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
                $DB->set_field('flipquiz_slots', 'slot', $otherslot->slot + 1, array('id' => $otherslot->id));
            } else {
                $lastslotbefore = $otherslot->slot;
                break;
            }
        }
        $slot->slot = $lastslotbefore + 1;
        $slot->page = min($page, $maxpage + 1);

        flipquiz_update_section_firstslots($flipquiz->id, 1, max($lastslotbefore, 1));

    } else {
        $lastslot = end($slots);
        if ($lastslot) {
            $slot->slot = $lastslot->slot + 1;
        } else {
            $slot->slot = 1;
        }
        if ($flipquiz->questionsperpage && $numonlastpage >= $flipquiz->questionsperpage) {
            $slot->page = $maxpage + 1;
        } else {
            $slot->page = $maxpage;
        }
    }

    $DB->insert_record('flipquiz_slots', $slot);
    $trans->allow_commit();
}

/**
 * Move all the section headings in a certain slot range by a certain offset.
 *
 * @param int $flipquizid the id of a flipquiz
 * @param int $direction amount to adjust section heading positions. Normally +1 or -1.
 * @param int $afterslot adjust headings that start after this slot.
 * @param int|null $beforeslot optionally, only adjust headings before this slot.
 */
function flipquiz_update_section_firstslots($flipquizid, $direction, $afterslot, $beforeslot = null) {
    global $DB;
    $where = 'flipquizid = ? AND firstslot > ?';
    $params = [$direction, $flipquizid, $afterslot];
    if ($beforeslot) {
        $where .= ' AND firstslot < ?';
        $params[] = $beforeslot;
    }
    $firstslotschanges = $DB->get_records_select_menu('flipquiz_sections',
            $where, $params, '', 'firstslot, firstslot + ?');
    update_field_with_unique_index('flipquiz_sections', 'firstslot', $firstslotschanges, ['flipquizid' => $flipquizid]);
}

/**
 * Add a random question to the flipquiz at a given point.
 * @param stdClass $flipquiz the flipquiz settings.
 * @param int $addonpage the page on which to add the question.
 * @param int $categoryid the question category to add the question from.
 * @param int $number the number of random questions to add.
 * @param bool $includesubcategories whether to include questoins from subcategories.
 * @param int[] $tagids Array of tagids. The question that will be picked randomly should be tagged with all these tags.
 */
function flipquiz_add_random_questions($flipquiz, $addonpage, $categoryid, $number,
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
    // not used by any flipquiz.
    $existingquestions = $DB->get_records_sql(
        "SELECT q.id, q.qtype FROM {question} q
        WHERE qtype = 'random'
            AND category = ?
            AND " . $DB->sql_compare_text('questiontext') . " = ?
            AND NOT EXISTS (
                    SELECT *
                      FROM {flipquiz_slots}
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
                print_error('cannotinsertrandomquestion', 'flipquiz');
            }
        }

        $randomslotdata = new stdClass();
        $randomslotdata->flipquizid = $flipquiz->id;
        $randomslotdata->questionid = $question->id;
        $randomslotdata->questioncategoryid = $categoryid;
        $randomslotdata->includingsubcategories = $includesubcategories ? 1 : 0;
        $randomslotdata->maxmark = 1;

        $randomslot = new \mod_flipquiz\local\structure\slot_random($randomslotdata);
        $randomslot->set_flipquiz($flipquiz);
        $randomslot->set_tags($tags);
        $randomslot->insert($addonpage);
    }
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $flipquiz       flipquiz object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.1
 */
function flipquiz_view($flipquiz, $course, $cm, $context) {

    $params = array(
        'objectid' => $flipquiz->id,
        'context' => $context
    );

    $event = \mod_flipquiz\event\course_module_viewed::create($params);
    $event->add_record_snapshot('flipquiz', $flipquiz);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Validate permissions for creating a new attempt and start a new preview attempt if required.
 *
 * @param  flipquiz $flipquizobj flipquiz object
 * @param  flipquiz_access_manager $accessmanager flipquiz access manager
 * @param  bool $forcenew whether was required to start a new preview attempt
 * @param  int $page page to jump to in the attempt
 * @param  bool $redirect whether to redirect or throw exceptions (for web or ws usage)
 * @return array an array containing the attempt information, access error messages and the page to jump to in the attempt
 * @throws moodle_flipquiz_exception
 * @since Moodle 3.1
 */
function flipquiz_validate_new_attempt(flipquiz $flipquizobj, flipquiz_access_manager $accessmanager, $forcenew, $page, $redirect) {
    global $DB, $USER;
    $timenow = time();

    if ($flipquizobj->is_preview_user() && $forcenew) {
        $accessmanager->current_attempt_finished();
    }

    // Check capabilities.
    if (!$flipquizobj->is_preview_user()) {
        $flipquizobj->require_capability('mod/flipquiz:attempt');
    }

    // Check to see if a new preview was requested.
    if ($flipquizobj->is_preview_user() && $forcenew) {
        // To force the creation of a new preview, we mark the current attempt (if any)
        // as finished. It will then automatically be deleted below.
        $DB->set_field('flipquiz_attempts', 'state', flipquiz_attempt::FINISHED,
                array('flipquiz' => $flipquizobj->get_flipquizid(), 'userid' => $USER->id));
    }

    // Look for an existing attempt.
    $attempts = flipquiz_get_user_attempts($flipquizobj->get_flipquizid(), $USER->id, 'all', true);
    $lastattempt = end($attempts);

    $attemptnumber = null;
    // If an in-progress attempt exists, check password then redirect to it.
    if ($lastattempt && ($lastattempt->state == flipquiz_attempt::IN_PROGRESS ||
            $lastattempt->state == flipquiz_attempt::OVERDUE)) {
        $currentattemptid = $lastattempt->id;
        $messages = $accessmanager->prevent_access();

        // If the attempt is now overdue, deal with that.
        $flipquizobj->create_attempt_object($lastattempt)->handle_if_time_expired($timenow, true);

        // And, if the attempt is now no longer in progress, redirect to the appropriate place.
        if ($lastattempt->state == flipquiz_attempt::ABANDONED || $lastattempt->state == flipquiz_attempt::FINISHED) {
            if ($redirect) {
                redirect($flipquizobj->review_url($lastattempt->id));
            } else {
                throw new moodle_flipquiz_exception($flipquizobj, 'attemptalreadyclosed');
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
 * @param  flipquiz $flipquizobj flipquiz object
 * @param  int $attemptnumber the attempt number
 * @param  object $lastattempt last attempt object
 * @param bool $offlineattempt whether is an offline attempt or not
 * @return object the new attempt
 * @since  Moodle 3.1
 */
function flipquiz_prepare_and_start_new_attempt(flipquiz $flipquizobj, $attemptnumber, $lastattempt, $offlineattempt = false) {
    global $DB, $USER;

    // Delete any previous preview attempts belonging to this user.
    flipquiz_delete_previews($flipquizobj->get_flipquiz(), $USER->id);

    $quba = question_engine::make_questions_usage_by_activity('mod_flipquiz', $flipquizobj->get_context());
    $quba->set_preferred_behaviour($flipquizobj->get_flipquiz()->preferredbehaviour);

    // Create the new attempt and initialize the question sessions
    $timenow = time(); // Update time now, in case the server is running really slowly.
    $attempt = flipquiz_create_attempt($flipquizobj, $attemptnumber, $lastattempt, $timenow, $flipquizobj->is_preview_user());

    if (!($flipquizobj->get_flipquiz()->attemptonlast && $lastattempt)) {
        $attempt = flipquiz_start_new_attempt($flipquizobj, $quba, $attempt, $attemptnumber, $timenow);
    } else {
        $attempt = flipquiz_start_attempt_built_on_last($quba, $attempt, $lastattempt);
    }

    $transaction = $DB->start_delegated_transaction();

    // Init the timemodifiedoffline for offline attempts.
    if ($offlineattempt) {
        $attempt->timemodifiedoffline = $attempt->timemodified;
    }
    $attempt = flipquiz_attempt_save_started($flipquizobj, $quba, $attempt);

    $transaction->allow_commit();

    return $attempt;
}

/**
 * Check if the given calendar_event is either a user or group override
 * event for flipquiz.
 *
 * @param calendar_event $event The calendar event to check
 * @return bool
 */
function flipquiz_is_overriden_calendar_event(\calendar_event $event) {
    global $DB;

    if (!isset($event->modulename)) {
        return false;
    }

    if ($event->modulename != 'flipquiz') {
        return false;
    }

    if (!isset($event->instance)) {
        return false;
    }

    if (!isset($event->userid) && !isset($event->groupid)) {
        return false;
    }

    $overrideparams = [
        'flipquiz' => $event->instance
    ];

    if (isset($event->groupid)) {
        $overrideparams['groupid'] = $event->groupid;
    } else if (isset($event->userid)) {
        $overrideparams['userid'] = $event->userid;
    }

    return $DB->record_exists('flipquiz_overrides', $overrideparams);
}

/**
 * Retrieves tag information for the given list of flipquiz slot ids.
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
 * @param int[] $slotids The list of id for the flipquiz slots.
 * @return array[] List of flipquiz_slot_tags records indexed by slot id.
 */
function flipquiz_retrieve_tags_for_slot_ids($slotids) {
    global $DB;

    if (empty($slotids)) {
        return [];
    }

    $slottags = $DB->get_records_list('flipquiz_slot_tags', 'slotid', $slotids);
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
 * Retrieves tag information for the given flipquiz slot.
 * A flipquiz slot have some tags if and only if it is representing a random question by tags.
 *
 * @param int $slotid The id of the flipquiz slot.
 * @return stdClass[] List of flipquiz_slot_tags records.
 */
function flipquiz_retrieve_slot_tags($slotid) {
    $slottags = flipquiz_retrieve_tags_for_slot_ids([$slotid]);
    return $slottags[$slotid];
}

/**
 * Retrieves tag ids for the given flipquiz slot.
 * A flipquiz slot have some tags if and only if it is representing a random question by tags.
 *
 * @param int $slotid The id of the flipquiz slot.
 * @return int[]
 */
function flipquiz_retrieve_slot_tag_ids($slotid) {
    $tags = flipquiz_retrieve_slot_tags($slotid);

    // Only work with tags that exist.
    return array_filter(array_column($tags, 'tagid'));
}

/**
 * Get flipquiz attempt and handling error.
 *
 * @param int $attemptid the id of the current attempt.
 * @param int|null $cmid the course_module id for this flipquiz.
 * @return flipquiz_attempt $attemptobj all the data about the flipquiz attempt.
 * @throws moodle_exception
 */
function flipquiz_create_attempt_handling_errors($attemptid, $cmid = null) {
    try {
        $attempobj = flipquiz_attempt::create($attemptid);
    } catch (moodle_exception $e) {
        if (!empty($cmid)) {
            list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'flipquiz');
            $continuelink = new moodle_url('/mod/flipquiz/view.php', array('id' => $cmid));
            $context = context_module::instance($cm->id);
            if (has_capability('mod/flipquiz:preview', $context)) {
                throw new moodle_exception('attempterrorcontentchange', 'flipquiz', $continuelink);
            } else {
                throw new moodle_exception('attempterrorcontentchangeforuser', 'flipquiz', $continuelink);
            }
        } else {
            throw new moodle_exception('attempterrorinvalid', 'flipquiz');
        }
    }
    if (!empty($cmid) && $attempobj->get_cmid() != $cmid) {
        throw new moodle_exception('invalidcoursemodule');
    } else {
        return $attempobj;
    }
}
