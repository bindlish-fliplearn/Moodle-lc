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
 * Helper functions for the flipquiz reports.
 *
 * @package   mod_flipquiz
 * @copyright 2008 Jamie Pratt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/flipquiz/lib.php');
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
function flipquiz_report_index_by_keys($datum, $keys, $keysunique = true) {
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
            $datumkeyed[$datakey] = flipquiz_report_index_by_keys($datakeyed, $keys, $keysunique);
        }
    }
    return $datumkeyed;
}

function flipquiz_report_unindex($datum) {
    if (!$datum) {
        return $datum;
    }
    $datumunkeyed = array();
    foreach ($datum as $value) {
        if (is_array($value)) {
            $datumunkeyed = array_merge($datumunkeyed, flipquiz_report_unindex($value));
        } else {
            $datumunkeyed[] = $value;
        }
    }
    return $datumunkeyed;
}

/**
 * Are there any questions in this flipquiz?
 * @param int $flipquizid the flipquiz id.
 */
function flipquiz_has_questions($flipquizid) {
    global $DB;
    return $DB->record_exists('flipquiz_slots', array('flipquizid' => $flipquizid));
}

/**
 * Get the slots of real questions (not descriptions) in this flipquiz, in order.
 * @param object $flipquiz the flipquiz.
 * @return array of slot => $question object with fields
 *      ->slot, ->id, ->maxmark, ->number, ->length.
 */
function flipquiz_report_get_significant_questions($flipquiz) {
    global $DB;

    $qsbyslot = $DB->get_records_sql("
            SELECT slot.slot,
                   q.id,
                   q.qtype,
                   q.length,
                   slot.maxmark

              FROM {question} q
              JOIN {flipquiz_slots} slot ON slot.questionid = q.id

             WHERE slot.flipquizid = ?
               AND q.length > 0

          ORDER BY slot.slot", array($flipquiz->id));

    $number = 1;
    foreach ($qsbyslot as $question) {
        $question->number = $number;
        $number += $question->length;
        $question->type = $question->qtype;
    }

    return $qsbyslot;
}

/**
 * @param object $flipquiz the flipquiz settings.
 * @return bool whether, for this flipquiz, it is possible to filter attempts to show
 *      only those that gave the final grade.
 */
function flipquiz_report_can_filter_only_graded($flipquiz) {
    return $flipquiz->attempts != 1 && $flipquiz->grademethod != FLIPQUIZ_GRADEAVERAGE;
}

/**
 * This is a wrapper for {@link flipquiz_report_grade_method_sql} that takes the whole flipquiz object instead of just the grading method
 * as a param. See definition for {@link flipquiz_report_grade_method_sql} below.
 *
 * @param object $flipquiz
 * @param string $flipquizattemptsalias sql alias for 'flipquiz_attempts' table
 * @return string sql to test if this is an attempt that will contribute towards the grade of the user
 */
function flipquiz_report_qm_filter_select($flipquiz, $flipquizattemptsalias = 'flipquiza') {
    if ($flipquiz->attempts == 1) {
        // This flipquiz only allows one attempt.
        return '';
    }
    return flipquiz_report_grade_method_sql($flipquiz->grademethod, $flipquizattemptsalias);
}

/**
 * Given a flipquiz grading method return sql to test if this is an
 * attempt that will be contribute towards the grade of the user. Or return an
 * empty string if the grading method is FLIPQUIZ_GRADEAVERAGE and thus all attempts
 * contribute to final grade.
 *
 * @param string $grademethod flipquiz grading method.
 * @param string $flipquizattemptsalias sql alias for 'flipquiz_attempts' table
 * @return string sql to test if this is an attempt that will contribute towards the graded of the user
 */
function flipquiz_report_grade_method_sql($grademethod, $flipquizattemptsalias = 'flipquiza') {
    switch ($grademethod) {
        case FLIPQUIZ_GRADEHIGHEST :
            return "($flipquizattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {flipquiz_attempts} qa2
                            WHERE qa2.flipquiz = $flipquizattemptsalias.flipquiz AND
                                qa2.userid = $flipquizattemptsalias.userid AND
                                 qa2.state = 'finished' AND (
                COALESCE(qa2.sumgrades, 0) > COALESCE($flipquizattemptsalias.sumgrades, 0) OR
               (COALESCE(qa2.sumgrades, 0) = COALESCE($flipquizattemptsalias.sumgrades, 0) AND qa2.attempt < $flipquizattemptsalias.attempt)
                                )))";

        case FLIPQUIZ_GRADEAVERAGE :
            return '';

        case FLIPQUIZ_ATTEMPTFIRST :
            return "($flipquizattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {flipquiz_attempts} qa2
                            WHERE qa2.flipquiz = $flipquizattemptsalias.flipquiz AND
                                qa2.userid = $flipquizattemptsalias.userid AND
                                 qa2.state = 'finished' AND
                               qa2.attempt < $flipquizattemptsalias.attempt))";

        case FLIPQUIZ_ATTEMPTLAST :
            return "($flipquizattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {flipquiz_attempts} qa2
                            WHERE qa2.flipquiz = $flipquizattemptsalias.flipquiz AND
                                qa2.userid = $flipquizattemptsalias.userid AND
                                 qa2.state = 'finished' AND
                               qa2.attempt > $flipquizattemptsalias.attempt))";
    }
}

/**
 * Get the number of students whose score was in a particular band for this flipquiz.
 * @param number $bandwidth the width of each band.
 * @param int $bands the number of bands
 * @param int $flipquizid the flipquiz id.
 * @param \core\dml\sql_join $usersjoins (joins, wheres, params) to get enrolled users
 * @return array band number => number of users with scores in that band.
 */
function flipquiz_report_grade_bands($bandwidth, $bands, $flipquizid, \core\dml\sql_join $usersjoins = null) {
    global $DB;
    if (!is_int($bands)) {
        debugging('$bands passed to flipquiz_report_grade_bands must be an integer. (' .
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
      FROM {flipquiz_grades} qg
    $userjoin
    WHERE $usertest AND qg.flipquiz = :flipquizid
) subquery

GROUP BY
    band

ORDER BY
    band";

    $params['flipquizid'] = $flipquizid;
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

function flipquiz_report_highlighting_grading_method($flipquiz, $qmsubselect, $qmfilter) {
    if ($flipquiz->attempts == 1) {
        return '<p>' . get_string('onlyoneattemptallowed', 'flipquiz_overview') . '</p>';

    } else if (!$qmsubselect) {
        return '<p>' . get_string('allattemptscontributetograde', 'flipquiz_overview') . '</p>';

    } else if ($qmfilter) {
        return '<p>' . get_string('showinggraded', 'flipquiz_overview') . '</p>';

    } else {
        return '<p>' . get_string('showinggradedandungraded', 'flipquiz_overview',
                '<span class="gradedattempt">' . flipquiz_get_grading_option_name($flipquiz->grademethod) .
                '</span>') . '</p>';
    }
}

/**
 * Get the feedback text for a grade on this flipquiz. The feedback is
 * processed ready for display.
 *
 * @param float $grade a grade on this flipquiz.
 * @param int $flipquizid the id of the flipquiz object.
 * @return string the comment that corresponds to this grade (empty string if there is not one.
 */
function flipquiz_report_feedback_for_grade($grade, $flipquizid, $context) {
    global $DB;

    static $feedbackcache = array();

    if (!isset($feedbackcache[$flipquizid])) {
        $feedbackcache[$flipquizid] = $DB->get_records('flipquiz_feedback', array('flipquizid' => $flipquizid));
    }

    // With CBM etc, it is possible to get -ve grades, which would then not match
    // any feedback. Therefore, we replace -ve grades with 0.
    $grade = max($grade, 0);

    $feedbacks = $feedbackcache[$flipquizid];
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
            $context->id, 'mod_flipquiz', 'feedback', $feedbackid);
    $feedbacktext = format_text($feedbacktext, $feedbacktextformat, $formatoptions);

    return $feedbacktext;
}

/**
 * Format a number as a percentage out of $flipquiz->sumgrades
 * @param number $rawgrade the mark to format.
 * @param object $flipquiz the flipquiz settings
 * @param bool $round whether to round the results ot $flipquiz->decimalpoints.
 */
function flipquiz_report_scale_summarks_as_percentage($rawmark, $flipquiz, $round = true) {
    if ($flipquiz->sumgrades == 0) {
        return '';
    }
    if (!is_numeric($rawmark)) {
        return $rawmark;
    }

    $mark = $rawmark * 100 / $flipquiz->sumgrades;
    if ($round) {
        $mark = flipquiz_format_grade($flipquiz, $mark);
    }
    return $mark . '%';
}

/**
 * Returns an array of reports to which the current user has access to.
 * @return array reports are ordered as they should be for display in tabs.
 */
function flipquiz_report_list($context) {
    global $DB;
    static $reportlist = null;
    if (!is_null($reportlist)) {
        return $reportlist;
    }

    $reports = $DB->get_records('flipquiz_reports', null, 'displayorder DESC', 'name, capability');
    $reportdirs = core_component::get_plugin_list('flipquiz');

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
            $capability = 'mod/flipquiz:viewreports';
        }
        if (has_capability($capability, $context)) {
            $reportlist[] = $name;
        }
    }
    return $reportlist;
}

/**
 * Create a filename for use when downloading data from a flipquiz report. It is
 * expected that this will be passed to flexible_table::is_downloading, which
 * cleans the filename of bad characters and adds the file extension.
 * @param string $report the type of report.
 * @param string $courseshortname the course shortname.
 * @param string $flipquizname the flipquiz name.
 * @return string the filename.
 */
function flipquiz_report_download_filename($report, $courseshortname, $flipquizname) {
    return $courseshortname . '-' . format_string($flipquizname, true) . '-' . $report;
}

/**
 * Get the default report for the current user.
 * @param object $context the flipquiz context.
 */
function flipquiz_report_default_report($context) {
    $reports = flipquiz_report_list($context);
    return reset($reports);
}

/**
 * Generate a message saying that this flipquiz has no questions, with a button to
 * go to the edit page, if the user has the right capability.
 * @param object $flipquiz the flipquiz settings.
 * @param object $cm the course_module object.
 * @param object $context the flipquiz context.
 * @return string HTML to output.
 */
function flipquiz_no_questions_message($flipquiz, $cm, $context) {
    global $OUTPUT;

    $output = '';
    $output .= $OUTPUT->notification(get_string('noquestions', 'flipquiz'));
    if (has_capability('mod/flipquiz:manage', $context)) {
        $output .= $OUTPUT->single_button(new moodle_url('/mod/flipquiz/edit.php',
        array('cmid' => $cm->id)), get_string('editflipquiz', 'flipquiz'), 'get');
    }

    return $output;
}

/**
 * Should the grades be displayed in this report. That depends on the flipquiz
 * display options, and whether the flipquiz is graded.
 * @param object $flipquiz the flipquiz settings.
 * @param context $context the flipquiz context.
 * @return bool
 */
function flipquiz_report_should_show_grades($flipquiz, context $context) {
    if ($flipquiz->timeclose && time() > $flipquiz->timeclose) {
        $when = mod_flipquiz_display_options::AFTER_CLOSE;
    } else {
        $when = mod_flipquiz_display_options::LATER_WHILE_OPEN;
    }
    $reviewoptions = mod_flipquiz_display_options::make_from_flipquiz($flipquiz, $when);

    return flipquiz_has_grades($flipquiz) &&
            ($reviewoptions->marks >= question_display_options::MARK_AND_MAX ||
            has_capability('moodle/grade:viewhidden', $context));
}
