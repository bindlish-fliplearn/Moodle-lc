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
 * Helper functions for the clatest reports.
 *
 * @package   mod_clatest
 * @copyright 2008 Jamie Pratt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/clatest/lib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Takes an array of objects and constructs a multidimensional array keyed by
 * the keys it finds on the object.
 * @param array $datum an array of objects with properties on the object
 * including the keys passed as the next param.
 * @param array $keys Array of strings with the names of the properties on the
 * objects in datum that you want to index the multidimensional array by.
 * @param bool $keysunique If there is not only one object for each
 * combination of keys you are using you should set $keysunique to true.
 * Otherwise all the object will be added to a zero based array. So the array
 * returned will have count($keys) + 1 indexs.
 * @return array multidimensional array properly indexed.
 */
function clatest_report_index_by_keys($datum, $keys, $keysunique = true) {
    if (!$datum) {
        return array();
    }
    $key = array_shift($keys);
    $datumkeyed = array();
    foreach ($datum as $data) {
        if ($keys || !$keysunique) {
            $datumkeyed[$data->{$key}][]= $data;
        } else {
            $datumkeyed[$data->{$key}]= $data;
        }
    }
    if ($keys) {
        foreach ($datumkeyed as $datakey => $datakeyed) {
            $datumkeyed[$datakey] = clatest_report_index_by_keys($datakeyed, $keys, $keysunique);
        }
    }
    return $datumkeyed;
}

function clatest_report_unindex($datum) {
    if (!$datum) {
        return $datum;
    }
    $datumunkeyed = array();
    foreach ($datum as $value) {
        if (is_array($value)) {
            $datumunkeyed = array_merge($datumunkeyed, clatest_report_unindex($value));
        } else {
            $datumunkeyed[] = $value;
        }
    }
    return $datumunkeyed;
}

/**
 * Are there any questions in this clatest?
 * @param int $clatestid the clatest id.
 */
function clatest_has_questions($clatestid) {
    global $DB;
    return $DB->record_exists('clatest_slots', array('clatestid' => $clatestid));
}

/**
 * Get the slots of real questions (not descriptions) in this clatest, in order.
 * @param object $clatest the clatest.
 * @return array of slot => $question object with fields
 *      ->slot, ->id, ->maxmark, ->number, ->length.
 */
function clatest_report_get_significant_questions($clatest) {
    global $DB;

    $qsbyslot = $DB->get_records_sql("
            SELECT slot.slot,
                   q.id,
                   q.qtype,
                   q.length,
                   slot.maxmark

              FROM {question} q
              JOIN {clatest_slots} slot ON slot.questionid = q.id

             WHERE slot.clatestid = ?
               AND q.length > 0

          ORDER BY slot.slot", array($clatest->id));

    $number = 1;
    foreach ($qsbyslot as $question) {
        $question->number = $number;
        $number += $question->length;
        $question->type = $question->qtype;
    }

    return $qsbyslot;
}

/**
 * @param object $clatest the clatest settings.
 * @return bool whether, for this clatest, it is possible to filter attempts to show
 *      only those that gave the final grade.
 */
function clatest_report_can_filter_only_graded($clatest) {
    return $clatest->attempts != 1 && $clatest->grademethod != QUIZ_GRADEAVERAGE;
}

/**
 * This is a wrapper for {@link clatest_report_grade_method_sql} that takes the whole clatest object instead of just the grading method
 * as a param. See definition for {@link clatest_report_grade_method_sql} below.
 *
 * @param object $clatest
 * @param string $clatestattemptsalias sql alias for 'clatest_attempts' table
 * @return string sql to test if this is an attempt that will contribute towards the grade of the user
 */
function clatest_report_qm_filter_select($clatest, $clatestattemptsalias = 'clatesta') {
    if ($clatest->attempts == 1) {
        // This clatest only allows one attempt.
        return '';
    }
    return clatest_report_grade_method_sql($clatest->grademethod, $clatestattemptsalias);
}

/**
 * Given a clatest grading method return sql to test if this is an
 * attempt that will be contribute towards the grade of the user. Or return an
 * empty string if the grading method is QUIZ_GRADEAVERAGE and thus all attempts
 * contribute to final grade.
 *
 * @param string $grademethod clatest grading method.
 * @param string $clatestattemptsalias sql alias for 'clatest_attempts' table
 * @return string sql to test if this is an attempt that will contribute towards the graded of the user
 */
function clatest_report_grade_method_sql($grademethod, $clatestattemptsalias = 'clatesta') {
    switch ($grademethod) {
        case QUIZ_GRADEHIGHEST :
            return "($clatestattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {clatest_attempts} qa2
                            WHERE qa2.clatest = $clatestattemptsalias.clatest AND
                                qa2.userid = $clatestattemptsalias.userid AND
                                 qa2.state = 'finished' AND (
                COALESCE(qa2.sumgrades, 0) > COALESCE($clatestattemptsalias.sumgrades, 0) OR
               (COALESCE(qa2.sumgrades, 0) = COALESCE($clatestattemptsalias.sumgrades, 0) AND qa2.attempt < $clatestattemptsalias.attempt)
                                )))";

        case QUIZ_GRADEAVERAGE :
            return '';

        case QUIZ_ATTEMPTFIRST :
            return "($clatestattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {clatest_attempts} qa2
                            WHERE qa2.clatest = $clatestattemptsalias.clatest AND
                                qa2.userid = $clatestattemptsalias.userid AND
                                 qa2.state = 'finished' AND
                               qa2.attempt < $clatestattemptsalias.attempt))";

        case QUIZ_ATTEMPTLAST :
            return "($clatestattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {clatest_attempts} qa2
                            WHERE qa2.clatest = $clatestattemptsalias.clatest AND
                                qa2.userid = $clatestattemptsalias.userid AND
                                 qa2.state = 'finished' AND
                               qa2.attempt > $clatestattemptsalias.attempt))";
    }
}

/**
 * Get the number of students whose score was in a particular band for this clatest.
 * @param number $bandwidth the width of each band.
 * @param int $bands the number of bands
 * @param int $clatestid the clatest id.
 * @param \core\dml\sql_join $usersjoins (joins, wheres, params) to get enrolled users
 * @return array band number => number of users with scores in that band.
 */
function clatest_report_grade_bands($bandwidth, $bands, $clatestid, \core\dml\sql_join $usersjoins = null) {
    global $DB;
    if (!is_int($bands)) {
        debugging('$bands passed to clatest_report_grade_bands must be an integer. (' .
                gettype($bands) . ' passed.)', DEBUG_DEVELOPER);
        $bands = (int) $bands;
    }

    if ($usersjoins && !empty($usersjoins->joins)) {
        $userjoin = "JOIN {user} u ON u.id = qg.userid
                {$usersjoins->joins}";
        $usertest = $usersjoins->wheres;
        $params = $usersjoins->params;
    } else {
        $userjoin = '';
        $usertest = '1=1';
        $params = array();
    }
    $sql = "
SELECT band, COUNT(1)

FROM (
    SELECT FLOOR(qg.grade / :bandwidth) AS band
      FROM {clatest_grades} qg
    $userjoin
    WHERE $usertest AND qg.clatest = :clatestid
) subquery

GROUP BY
    band

ORDER BY
    band";

    $params['clatestid'] = $clatestid;
    $params['bandwidth'] = $bandwidth;

    $data = $DB->get_records_sql_menu($sql, $params);

    // We need to create array elements with values 0 at indexes where there is no element.
    $data = $data + array_fill(0, $bands + 1, 0);
    ksort($data);

    // Place the maximum (perfect grade) into the last band i.e. make last
    // band for example 9 <= g <=10 (where 10 is the perfect grade) rather than
    // just 9 <= g <10.
    $data[$bands - 1] += $data[$bands];
    unset($data[$bands]);

    return $data;
}

function clatest_report_highlighting_grading_method($clatest, $qmsubselect, $qmfilter) {
    if ($clatest->attempts == 1) {
        return '<p>' . get_string('onlyoneattemptallowed', 'clatest_overview') . '</p>';

    } else if (!$qmsubselect) {
        return '<p>' . get_string('allattemptscontributetograde', 'clatest_overview') . '</p>';

    } else if ($qmfilter) {
        return '<p>' . get_string('showinggraded', 'clatest_overview') . '</p>';

    } else {
        return '<p>' . get_string('showinggradedandungraded', 'clatest_overview',
                '<span class="gradedattempt">' . clatest_get_grading_option_name($clatest->grademethod) .
                '</span>') . '</p>';
    }
}

/**
 * Get the feedback text for a grade on this clatest. The feedback is
 * processed ready for display.
 *
 * @param float $grade a grade on this clatest.
 * @param int $clatestid the id of the clatest object.
 * @return string the comment that corresponds to this grade (empty string if there is not one.
 */
function clatest_report_feedback_for_grade($grade, $clatestid, $context) {
    global $DB;

    static $feedbackcache = array();

    if (!isset($feedbackcache[$clatestid])) {
        $feedbackcache[$clatestid] = $DB->get_records('clatest_feedback', array('clatestid' => $clatestid));
    }

    // With CBM etc, it is possible to get -ve grades, which would then not match
    // any feedback. Therefore, we replace -ve grades with 0.
    $grade = max($grade, 0);

    $feedbacks = $feedbackcache[$clatestid];
    $feedbackid = 0;
    $feedbacktext = '';
    $feedbacktextformat = FORMAT_MOODLE;
    foreach ($feedbacks as $feedback) {
        if ($feedback->mingrade <= $grade && $grade < $feedback->maxgrade) {
            $feedbackid = $feedback->id;
            $feedbacktext = $feedback->feedbacktext;
            $feedbacktextformat = $feedback->feedbacktextformat;
            break;
        }
    }

    // Clean the text, ready for display.
    $formatoptions = new stdClass();
    $formatoptions->noclean = true;
    $feedbacktext = file_rewrite_pluginfile_urls($feedbacktext, 'pluginfile.php',
            $context->id, 'mod_clatest', 'feedback', $feedbackid);
    $feedbacktext = format_text($feedbacktext, $feedbacktextformat, $formatoptions);

    return $feedbacktext;
}

/**
 * Format a number as a percentage out of $clatest->sumgrades
 * @param number $rawgrade the mark to format.
 * @param object $clatest the clatest settings
 * @param bool $round whether to round the results ot $clatest->decimalpoints.
 */
function clatest_report_scale_summarks_as_percentage($rawmark, $clatest, $round = true) {
    if ($clatest->sumgrades == 0) {
        return '';
    }
    if (!is_numeric($rawmark)) {
        return $rawmark;
    }

    $mark = $rawmark * 100 / $clatest->sumgrades;
    if ($round) {
        $mark = clatest_format_grade($clatest, $mark);
    }
    return $mark . '%';
}

/**
 * Returns an array of reports to which the current user has access to.
 * @return array reports are ordered as they should be for display in tabs.
 */
function clatest_report_list($context) {
    global $DB;
    static $reportlist = null;
    if (!is_null($reportlist)) {
        return $reportlist;
    }

    $reports = $DB->get_records('clatest_reports', null, 'displayorder DESC', 'name, capability');
    $reportdirs = core_component::get_plugin_list('clatest');

    // Order the reports tab in descending order of displayorder.
    $reportcaps = array();
    foreach ($reports as $key => $report) {
        if (array_key_exists($report->name, $reportdirs)) {
            $reportcaps[$report->name] = $report->capability;
        }
    }

    // Add any other reports, which are on disc but not in the DB, on the end.
    foreach ($reportdirs as $reportname => $notused) {
        if (!isset($reportcaps[$reportname])) {
            $reportcaps[$reportname] = null;
        }
    }
    $reportlist = array();
    foreach ($reportcaps as $name => $capability) {
        if (empty($capability)) {
            $capability = 'mod/clatest:viewreports';
        }
        if (has_capability($capability, $context)) {
            $reportlist[] = $name;
        }
    }
    return $reportlist;
}

/**
 * Create a filename for use when downloading data from a clatest report. It is
 * expected that this will be passed to flexible_table::is_downloading, which
 * cleans the filename of bad characters and adds the file extension.
 * @param string $report the type of report.
 * @param string $courseshortname the course shortname.
 * @param string $clatestname the clatest name.
 * @return string the filename.
 */
function clatest_report_download_filename($report, $courseshortname, $clatestname) {
    return $courseshortname . '-' . format_string($clatestname, true) . '-' . $report;
}

/**
 * Get the default report for the current user.
 * @param object $context the clatest context.
 */
function clatest_report_default_report($context) {
    $reports = clatest_report_list($context);
    return reset($reports);
}

/**
 * Generate a message saying that this clatest has no questions, with a button to
 * go to the edit page, if the user has the right capability.
 * @param object $clatest the clatest settings.
 * @param object $cm the course_module object.
 * @param object $context the clatest context.
 * @return string HTML to output.
 */
function clatest_no_questions_message($clatest, $cm, $context) {
    global $OUTPUT;

    $output = '';
    $output .= $OUTPUT->notification(get_string('noquestions', 'clatest'));
    if (has_capability('mod/clatest:manage', $context)) {
        $output .= $OUTPUT->single_button(new moodle_url('/mod/clatest/edit.php',
        array('cmid' => $cm->id)), get_string('editclatest', 'clatest'), 'get');
    }

    return $output;
}

/**
 * Should the grades be displayed in this report. That depends on the clatest
 * display options, and whether the clatest is graded.
 * @param object $clatest the clatest settings.
 * @param context $context the clatest context.
 * @return bool
 */
function clatest_report_should_show_grades($clatest, context $context) {
    if ($clatest->timeclose && time() > $clatest->timeclose) {
        $when = mod_clatest_display_options::AFTER_CLOSE;
    } else {
        $when = mod_clatest_display_options::LATER_WHILE_OPEN;
    }
    $reviewoptions = mod_clatest_display_options::make_from_clatest($clatest, $when);

    return clatest_has_grades($clatest) &&
            ($reviewoptions->marks >= question_display_options::MARK_AND_MAX ||
            has_capability('moodle/grade:viewhidden', $context));
}
