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
 * Helper functions for the hwork reports.
 *
 * @package   mod_hwork
 * @copyright 2008 Jamie Pratt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/hwork/lib.php');
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
function hwork_report_index_by_keys($datum, $keys, $keysunique = true) {
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
            $datumkeyed[$datakey] = hwork_report_index_by_keys($datakeyed, $keys, $keysunique);
        }
    }
    return $datumkeyed;
}

function hwork_report_unindex($datum) {
    if (!$datum) {
        return $datum;
    }
    $datumunkeyed = array();
    foreach ($datum as $value) {
        if (is_array($value)) {
            $datumunkeyed = array_merge($datumunkeyed, hwork_report_unindex($value));
        } else {
            $datumunkeyed[] = $value;
        }
    }
    return $datumunkeyed;
}

/**
 * Are there any questions in this hwork?
 * @param int $hworkid the hwork id.
 */
function hwork_has_questions($hworkid) {
    global $DB;
    return $DB->record_exists('hwork_slots', array('hworkid' => $hworkid));
}

/**
 * Get the slots of real questions (not descriptions) in this hwork, in order.
 * @param object $hwork the hwork.
 * @return array of slot => $question object with fields
 *      ->slot, ->id, ->maxmark, ->number, ->length.
 */
function hwork_report_get_significant_questions($hwork) {
    global $DB;

    $qsbyslot = $DB->get_records_sql("
            SELECT slot.slot,
                   q.id,
                   q.qtype,
                   q.length,
                   slot.maxmark

              FROM {question} q
              JOIN {hwork_slots} slot ON slot.questionid = q.id

             WHERE slot.hworkid = ?
               AND q.length > 0

          ORDER BY slot.slot", array($hwork->id));

    $number = 1;
    foreach ($qsbyslot as $question) {
        $question->number = $number;
        $number += $question->length;
        $question->type = $question->qtype;
    }

    return $qsbyslot;
}

/**
 * @param object $hwork the hwork settings.
 * @return bool whether, for this hwork, it is possible to filter attempts to show
 *      only those that gave the final grade.
 */
function hwork_report_can_filter_only_graded($hwork) {
    return $hwork->attempts != 1 && $hwork->grademethod != QUIZ_GRADEAVERAGE;
}

/**
 * This is a wrapper for {@link hwork_report_grade_method_sql} that takes the whole hwork object instead of just the grading method
 * as a param. See definition for {@link hwork_report_grade_method_sql} below.
 *
 * @param object $hwork
 * @param string $hworkattemptsalias sql alias for 'hwork_attempts' table
 * @return string sql to test if this is an attempt that will contribute towards the grade of the user
 */
function hwork_report_qm_filter_select($hwork, $hworkattemptsalias = 'hworka') {
    if ($hwork->attempts == 1) {
        // This hwork only allows one attempt.
        return '';
    }
    return hwork_report_grade_method_sql($hwork->grademethod, $hworkattemptsalias);
}

/**
 * Given a hwork grading method return sql to test if this is an
 * attempt that will be contribute towards the grade of the user. Or return an
 * empty string if the grading method is QUIZ_GRADEAVERAGE and thus all attempts
 * contribute to final grade.
 *
 * @param string $grademethod hwork grading method.
 * @param string $hworkattemptsalias sql alias for 'hwork_attempts' table
 * @return string sql to test if this is an attempt that will contribute towards the graded of the user
 */
function hwork_report_grade_method_sql($grademethod, $hworkattemptsalias = 'hworka') {
    switch ($grademethod) {
        case QUIZ_GRADEHIGHEST :
            return "($hworkattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {hwork_attempts} qa2
                            WHERE qa2.hwork = $hworkattemptsalias.hwork AND
                                qa2.userid = $hworkattemptsalias.userid AND
                                 qa2.state = 'finished' AND (
                COALESCE(qa2.sumgrades, 0) > COALESCE($hworkattemptsalias.sumgrades, 0) OR
               (COALESCE(qa2.sumgrades, 0) = COALESCE($hworkattemptsalias.sumgrades, 0) AND qa2.attempt < $hworkattemptsalias.attempt)
                                )))";

        case QUIZ_GRADEAVERAGE :
            return '';

        case QUIZ_ATTEMPTFIRST :
            return "($hworkattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {hwork_attempts} qa2
                            WHERE qa2.hwork = $hworkattemptsalias.hwork AND
                                qa2.userid = $hworkattemptsalias.userid AND
                                 qa2.state = 'finished' AND
                               qa2.attempt < $hworkattemptsalias.attempt))";

        case QUIZ_ATTEMPTLAST :
            return "($hworkattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {hwork_attempts} qa2
                            WHERE qa2.hwork = $hworkattemptsalias.hwork AND
                                qa2.userid = $hworkattemptsalias.userid AND
                                 qa2.state = 'finished' AND
                               qa2.attempt > $hworkattemptsalias.attempt))";
    }
}

/**
 * Get the number of students whose score was in a particular band for this hwork.
 * @param number $bandwidth the width of each band.
 * @param int $bands the number of bands
 * @param int $hworkid the hwork id.
 * @param \core\dml\sql_join $usersjoins (joins, wheres, params) to get enrolled users
 * @return array band number => number of users with scores in that band.
 */
function hwork_report_grade_bands($bandwidth, $bands, $hworkid, \core\dml\sql_join $usersjoins = null) {
    global $DB;
    if (!is_int($bands)) {
        debugging('$bands passed to hwork_report_grade_bands must be an integer. (' .
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
      FROM {hwork_grades} qg
    $userjoin
    WHERE $usertest AND qg.hwork = :hworkid
) subquery

GROUP BY
    band

ORDER BY
    band";

    $params['hworkid'] = $hworkid;
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

function hwork_report_highlighting_grading_method($hwork, $qmsubselect, $qmfilter) {
    if ($hwork->attempts == 1) {
        return '<p>' . get_string('onlyoneattemptallowed', 'hwork_overview') . '</p>';

    } else if (!$qmsubselect) {
        return '<p>' . get_string('allattemptscontributetograde', 'hwork_overview') . '</p>';

    } else if ($qmfilter) {
        return '<p>' . get_string('showinggraded', 'hwork_overview') . '</p>';

    } else {
        return '<p>' . get_string('showinggradedandungraded', 'hwork_overview',
                '<span class="gradedattempt">' . hwork_get_grading_option_name($hwork->grademethod) .
                '</span>') . '</p>';
    }
}

/**
 * Get the feedback text for a grade on this hwork. The feedback is
 * processed ready for display.
 *
 * @param float $grade a grade on this hwork.
 * @param int $hworkid the id of the hwork object.
 * @return string the comment that corresponds to this grade (empty string if there is not one.
 */
function hwork_report_feedback_for_grade($grade, $hworkid, $context) {
    global $DB;

    static $feedbackcache = array();

    if (!isset($feedbackcache[$hworkid])) {
        $feedbackcache[$hworkid] = $DB->get_records('hwork_feedback', array('hworkid' => $hworkid));
    }

    // With CBM etc, it is possible to get -ve grades, which would then not match
    // any feedback. Therefore, we replace -ve grades with 0.
    $grade = max($grade, 0);

    $feedbacks = $feedbackcache[$hworkid];
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
            $context->id, 'mod_hwork', 'feedback', $feedbackid);
    $feedbacktext = format_text($feedbacktext, $feedbacktextformat, $formatoptions);

    return $feedbacktext;
}

/**
 * Format a number as a percentage out of $hwork->sumgrades
 * @param number $rawgrade the mark to format.
 * @param object $hwork the hwork settings
 * @param bool $round whether to round the results ot $hwork->decimalpoints.
 */
function hwork_report_scale_summarks_as_percentage($rawmark, $hwork, $round = true) {
    if ($hwork->sumgrades == 0) {
        return '';
    }
    if (!is_numeric($rawmark)) {
        return $rawmark;
    }

    $mark = $rawmark * 100 / $hwork->sumgrades;
    if ($round) {
        $mark = hwork_format_grade($hwork, $mark);
    }
    return $mark . '%';
}

/**
 * Returns an array of reports to which the current user has access to.
 * @return array reports are ordered as they should be for display in tabs.
 */
function hwork_report_list($context) {
    global $DB;
    static $reportlist = null;
    if (!is_null($reportlist)) {
        return $reportlist;
    }

    $reports = $DB->get_records('hwork_reports', null, 'displayorder DESC', 'name, capability');
    $reportdirs = core_component::get_plugin_list('hwork');

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
            $capability = 'mod/hwork:viewreports';
        }
        if (has_capability($capability, $context)) {
            $reportlist[] = $name;
        }
    }
    return $reportlist;
}

/**
 * Create a filename for use when downloading data from a hwork report. It is
 * expected that this will be passed to flexible_table::is_downloading, which
 * cleans the filename of bad characters and adds the file extension.
 * @param string $report the type of report.
 * @param string $courseshortname the course shortname.
 * @param string $hworkname the hwork name.
 * @return string the filename.
 */
function hwork_report_download_filename($report, $courseshortname, $hworkname) {
    return $courseshortname . '-' . format_string($hworkname, true) . '-' . $report;
}

/**
 * Get the default report for the current user.
 * @param object $context the hwork context.
 */
function hwork_report_default_report($context) {
    $reports = hwork_report_list($context);
    return reset($reports);
}

/**
 * Generate a message saying that this hwork has no questions, with a button to
 * go to the edit page, if the user has the right capability.
 * @param object $hwork the hwork settings.
 * @param object $cm the course_module object.
 * @param object $context the hwork context.
 * @return string HTML to output.
 */
function hwork_no_questions_message($hwork, $cm, $context) {
    global $OUTPUT;

    $output = '';
    $output .= $OUTPUT->notification(get_string('noquestions', 'hwork'));
    if (has_capability('mod/hwork:manage', $context)) {
        $output .= $OUTPUT->single_button(new moodle_url('/mod/hwork/edit.php',
        array('cmid' => $cm->id)), get_string('edithwork', 'hwork'), 'get');
    }

    return $output;
}

/**
 * Should the grades be displayed in this report. That depends on the hwork
 * display options, and whether the hwork is graded.
 * @param object $hwork the hwork settings.
 * @param context $context the hwork context.
 * @return bool
 */
function hwork_report_should_show_grades($hwork, context $context) {
    if ($hwork->timeclose && time() > $hwork->timeclose) {
        $when = mod_hwork_display_options::AFTER_CLOSE;
    } else {
        $when = mod_hwork_display_options::LATER_WHILE_OPEN;
    }
    $reviewoptions = mod_hwork_display_options::make_from_hwork($hwork, $when);

    return hwork_has_grades($hwork) &&
            ($reviewoptions->marks >= question_display_options::MARK_AND_MAX ||
            has_capability('moodle/grade:viewhidden', $context));
}
