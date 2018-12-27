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
 * Helper functions for the homework reports.
 *
 * @package   mod_homework
 * @copyright 2008 Jamie Pratt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/homework/lib.php');
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
function homework_report_index_by_keys($datum, $keys, $keysunique = true) {
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
            $datumkeyed[$datakey] = homework_report_index_by_keys($datakeyed, $keys, $keysunique);
        }
    }
    return $datumkeyed;
}

function homework_report_unindex($datum) {
    if (!$datum) {
        return $datum;
    }
    $datumunkeyed = array();
    foreach ($datum as $value) {
        if (is_array($value)) {
            $datumunkeyed = array_merge($datumunkeyed, homework_report_unindex($value));
        } else {
            $datumunkeyed[] = $value;
        }
    }
    return $datumunkeyed;
}

/**
 * Are there any questions in this homework?
 * @param int $homeworkid the homework id.
 */
function homework_has_questions($homeworkid) {
    global $DB;
    return $DB->record_exists('homework_slots', array('homeworkid' => $homeworkid));
}

/**
 * Get the slots of real questions (not descriptions) in this homework, in order.
 * @param object $homework the homework.
 * @return array of slot => $question object with fields
 *      ->slot, ->id, ->maxmark, ->number, ->length.
 */
function homework_report_get_significant_questions($homework) {
    global $DB;

    $qsbyslot = $DB->get_records_sql("
            SELECT slot.slot,
                   q.id,
                   q.qtype,
                   q.length,
                   slot.maxmark

              FROM {question} q
              JOIN {homework_slots} slot ON slot.questionid = q.id

             WHERE slot.homeworkid = ?
               AND q.length > 0

          ORDER BY slot.slot", array($homework->id));

    $number = 1;
    foreach ($qsbyslot as $question) {
        $question->number = $number;
        $number += $question->length;
        $question->type = $question->qtype;
    }

    return $qsbyslot;
}

/**
 * @param object $homework the homework settings.
 * @return bool whether, for this homework, it is possible to filter attempts to show
 *      only those that gave the final grade.
 */
function homework_report_can_filter_only_graded($homework) {
    return $homework->attempts != 1 && $homework->grademethod != QUIZ_GRADEAVERAGE;
}

/**
 * This is a wrapper for {@link homework_report_grade_method_sql} that takes the whole homework object instead of just the grading method
 * as a param. See definition for {@link homework_report_grade_method_sql} below.
 *
 * @param object $homework
 * @param string $homeworkattemptsalias sql alias for 'homework_attempts' table
 * @return string sql to test if this is an attempt that will contribute towards the grade of the user
 */
function homework_report_qm_filter_select($homework, $homeworkattemptsalias = 'homeworka') {
    if ($homework->attempts == 1) {
        // This homework only allows one attempt.
        return '';
    }
    return homework_report_grade_method_sql($homework->grademethod, $homeworkattemptsalias);
}

/**
 * Given a homework grading method return sql to test if this is an
 * attempt that will be contribute towards the grade of the user. Or return an
 * empty string if the grading method is QUIZ_GRADEAVERAGE and thus all attempts
 * contribute to final grade.
 *
 * @param string $grademethod homework grading method.
 * @param string $homeworkattemptsalias sql alias for 'homework_attempts' table
 * @return string sql to test if this is an attempt that will contribute towards the graded of the user
 */
function homework_report_grade_method_sql($grademethod, $homeworkattemptsalias = 'homeworka') {
    switch ($grademethod) {
        case QUIZ_GRADEHIGHEST :
            return "($homeworkattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {homework_attempts} qa2
                            WHERE qa2.homework = $homeworkattemptsalias.homework AND
                                qa2.userid = $homeworkattemptsalias.userid AND
                                 qa2.state = 'finished' AND (
                COALESCE(qa2.sumgrades, 0) > COALESCE($homeworkattemptsalias.sumgrades, 0) OR
               (COALESCE(qa2.sumgrades, 0) = COALESCE($homeworkattemptsalias.sumgrades, 0) AND qa2.attempt < $homeworkattemptsalias.attempt)
                                )))";

        case QUIZ_GRADEAVERAGE :
            return '';

        case QUIZ_ATTEMPTFIRST :
            return "($homeworkattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {homework_attempts} qa2
                            WHERE qa2.homework = $homeworkattemptsalias.homework AND
                                qa2.userid = $homeworkattemptsalias.userid AND
                                 qa2.state = 'finished' AND
                               qa2.attempt < $homeworkattemptsalias.attempt))";

        case QUIZ_ATTEMPTLAST :
            return "($homeworkattemptsalias.state = 'finished' AND NOT EXISTS (
                           SELECT 1 FROM {homework_attempts} qa2
                            WHERE qa2.homework = $homeworkattemptsalias.homework AND
                                qa2.userid = $homeworkattemptsalias.userid AND
                                 qa2.state = 'finished' AND
                               qa2.attempt > $homeworkattemptsalias.attempt))";
    }
}

/**
 * Get the number of students whose score was in a particular band for this homework.
 * @param number $bandwidth the width of each band.
 * @param int $bands the number of bands
 * @param int $homeworkid the homework id.
 * @param \core\dml\sql_join $usersjoins (joins, wheres, params) to get enrolled users
 * @return array band number => number of users with scores in that band.
 */
function homework_report_grade_bands($bandwidth, $bands, $homeworkid, \core\dml\sql_join $usersjoins = null) {
    global $DB;
    if (!is_int($bands)) {
        debugging('$bands passed to homework_report_grade_bands must be an integer. (' .
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
      FROM {homework_grades} qg
    $userjoin
    WHERE $usertest AND qg.homework = :homeworkid
) subquery

GROUP BY
    band

ORDER BY
    band";

    $params['homeworkid'] = $homeworkid;
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

function homework_report_highlighting_grading_method($homework, $qmsubselect, $qmfilter) {
    if ($homework->attempts == 1) {
        return '<p>' . get_string('onlyoneattemptallowed', 'homework_overview') . '</p>';

    } else if (!$qmsubselect) {
        return '<p>' . get_string('allattemptscontributetograde', 'homework_overview') . '</p>';

    } else if ($qmfilter) {
        return '<p>' . get_string('showinggraded', 'homework_overview') . '</p>';

    } else {
        return '<p>' . get_string('showinggradedandungraded', 'homework_overview',
                '<span class="gradedattempt">' . homework_get_grading_option_name($homework->grademethod) .
                '</span>') . '</p>';
    }
}

/**
 * Get the feedback text for a grade on this homework. The feedback is
 * processed ready for display.
 *
 * @param float $grade a grade on this homework.
 * @param int $homeworkid the id of the homework object.
 * @return string the comment that corresponds to this grade (empty string if there is not one.
 */
function homework_report_feedback_for_grade($grade, $homeworkid, $context) {
    global $DB;

    static $feedbackcache = array();

    if (!isset($feedbackcache[$homeworkid])) {
        $feedbackcache[$homeworkid] = $DB->get_records('homework_feedback', array('homeworkid' => $homeworkid));
    }

    // With CBM etc, it is possible to get -ve grades, which would then not match
    // any feedback. Therefore, we replace -ve grades with 0.
    $grade = max($grade, 0);

    $feedbacks = $feedbackcache[$homeworkid];
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
            $context->id, 'mod_homework', 'feedback', $feedbackid);
    $feedbacktext = format_text($feedbacktext, $feedbacktextformat, $formatoptions);

    return $feedbacktext;
}

/**
 * Format a number as a percentage out of $homework->sumgrades
 * @param number $rawgrade the mark to format.
 * @param object $homework the homework settings
 * @param bool $round whether to round the results ot $homework->decimalpoints.
 */
function homework_report_scale_summarks_as_percentage($rawmark, $homework, $round = true) {
    if ($homework->sumgrades == 0) {
        return '';
    }
    if (!is_numeric($rawmark)) {
        return $rawmark;
    }

    $mark = $rawmark * 100 / $homework->sumgrades;
    if ($round) {
        $mark = homework_format_grade($homework, $mark);
    }
    return $mark . '%';
}

/**
 * Returns an array of reports to which the current user has access to.
 * @return array reports are ordered as they should be for display in tabs.
 */
function homework_report_list($context) {
    global $DB;
    static $reportlist = null;
    if (!is_null($reportlist)) {
        return $reportlist;
    }

    $reports = $DB->get_records('homework_reports', null, 'displayorder DESC', 'name, capability');
    $reportdirs = core_component::get_plugin_list('homework');

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
            $capability = 'mod/homework:viewreports';
        }
        if (has_capability($capability, $context)) {
            $reportlist[] = $name;
        }
    }
    return $reportlist;
}

/**
 * Create a filename for use when downloading data from a homework report. It is
 * expected that this will be passed to flexible_table::is_downloading, which
 * cleans the filename of bad characters and adds the file extension.
 * @param string $report the type of report.
 * @param string $courseshortname the course shortname.
 * @param string $homeworkname the homework name.
 * @return string the filename.
 */
function homework_report_download_filename($report, $courseshortname, $homeworkname) {
    return $courseshortname . '-' . format_string($homeworkname, true) . '-' . $report;
}

/**
 * Get the default report for the current user.
 * @param object $context the homework context.
 */
function homework_report_default_report($context) {
    $reports = homework_report_list($context);
    return reset($reports);
}

/**
 * Generate a message saying that this homework has no questions, with a button to
 * go to the edit page, if the user has the right capability.
 * @param object $homework the homework settings.
 * @param object $cm the course_module object.
 * @param object $context the homework context.
 * @return string HTML to output.
 */
function homework_no_questions_message($homework, $cm, $context) {
    global $OUTPUT;

    $output = '';
    $output .= $OUTPUT->notification(get_string('noquestions', 'homework'));
    if (has_capability('mod/homework:manage', $context)) {
        $output .= $OUTPUT->single_button(new moodle_url('/mod/homework/edit.php',
        array('cmid' => $cm->id)), get_string('edithomework', 'homework'), 'get');
    }

    return $output;
}

/**
 * Should the grades be displayed in this report. That depends on the homework
 * display options, and whether the homework is graded.
 * @param object $homework the homework settings.
 * @param context $context the homework context.
 * @return bool
 */
function homework_report_should_show_grades($homework, context $context) {
    if ($homework->timeclose && time() > $homework->timeclose) {
        $when = mod_homework_display_options::AFTER_CLOSE;
    } else {
        $when = mod_homework_display_options::LATER_WHILE_OPEN;
    }
    $reviewoptions = mod_homework_display_options::make_from_homework($homework, $when);

    return homework_has_grades($homework) &&
            ($reviewoptions->marks >= question_display_options::MARK_AND_MAX ||
            has_capability('moodle/grade:viewhidden', $context));
}
