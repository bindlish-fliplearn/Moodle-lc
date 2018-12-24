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
 * This file defines the flipquiz grades table.
 *
 * @package   flipquiz_overview
 * @copyright 2008 Jamie Pratt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/flipquiz/report/attemptsreport_table.php');


/**
 * This is a table subclass for displaying the flipquiz grades report.
 *
 * @copyright 2008 Jamie Pratt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flipquiz_overview_table extends flipquiz_attempts_report_table {

    protected $regradedqs = array();

    /**
     * Constructor
     * @param object $flipquiz
     * @param context $context
     * @param string $qmsubselect
     * @param flipquiz_overview_options $options
     * @param \core\dml\sql_join $groupstudentsjoins
     * @param \core\dml\sql_join $studentsjoins
     * @param array $questions
     * @param moodle_url $reporturl
     */
    public function __construct($flipquiz, $context, $qmsubselect,
            flipquiz_overview_options $options, \core\dml\sql_join $groupstudentsjoins,
            \core\dml\sql_join $studentsjoins, $questions, $reporturl) {
        parent::__construct('mod-flipquiz-report-overview-report', $flipquiz , $context,
                $qmsubselect, $options, $groupstudentsjoins, $studentsjoins, $questions, $reporturl);
    }

    public function build_table() {
        global $DB;

        if (!$this->rawdata) {
            return;
        }

        $this->strtimeformat = str_replace(',', ' ', get_string('strftimedatetime'));
        parent::build_table();

        // End of adding the data from attempts. Now add averages at bottom.
        $this->add_separator();

        if (!empty($this->groupstudentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                    {$this->groupstudentsjoins->joins}
                     WHERE {$this->groupstudentsjoins->wheres}";
            $groupstudents = $DB->get_records_sql($sql, $this->groupstudentsjoins->params);
            if ($groupstudents) {
                $this->add_average_row(get_string('groupavg', 'grades'), $this->groupstudentsjoins);
            }
        }

        if (!empty($this->studentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                    {$this->studentsjoins->joins}
                     WHERE {$this->studentsjoins->wheres}";
            $students = $DB->get_records_sql($sql, $this->studentsjoins->params);
            if ($students) {
                $this->add_average_row(get_string('overallaverage', 'grades'), $this->studentsjoins);
            }
        }
    }

    /**
     * Calculate the average overall and question scores for a set of attempts at the flipquiz.
     *
     * @param string $label the title ot use for this row.
     * @param \core\dml\sql_join $usersjoins to indicate a set of users.
     * @return array of table cells that make up the average row.
     */
    public function compute_average_row($label, \core\dml\sql_join $usersjoins) {
        global $DB;

        list($fields, $from, $where, $params) = $this->base_sql($usersjoins);
        $record = $DB->get_record_sql("
                SELECT AVG(flipquizaouter.sumgrades) AS grade, COUNT(flipquizaouter.sumgrades) AS numaveraged
                  FROM {flipquiz_attempts} flipquizaouter
                  JOIN (
                       SELECT DISTINCT flipquiza.id
                         FROM $from
                        WHERE $where
                       ) relevant_attempt_ids ON flipquizaouter.id = relevant_attempt_ids.id
                ", $params);
        $record->grade = flipquiz_rescale_grade($record->grade, $this->flipquiz, false);
        if ($this->is_downloading()) {
            $namekey = 'lastname';
        } else {
            $namekey = 'fullname';
        }
        $averagerow = array(
            $namekey       => $label,
            'sumgrades'    => $this->format_average($record),
            'feedbacktext' => strip_tags(flipquiz_report_feedback_for_grade(
                                         $record->grade, $this->flipquiz->id, $this->context))
        );

        if ($this->options->slotmarks) {
            $dm = new question_engine_data_mapper();
            $qubaids = new qubaid_join("{flipquiz_attempts} flipquizaouter
                  JOIN (
                       SELECT DISTINCT flipquiza.id
                         FROM $from
                        WHERE $where
                       ) relevant_attempt_ids ON flipquizaouter.id = relevant_attempt_ids.id",
                    'flipquizaouter.uniqueid', '1 = 1', $params);
            $avggradebyq = $dm->load_average_marks($qubaids, array_keys($this->questions));

            $averagerow += $this->format_average_grade_for_questions($avggradebyq);
        }

        return $averagerow;
    }

    /**
     * Add an average grade row for a set of users.
     *
     * @param string $label the title ot use for this row.
     * @param \core\dml\sql_join $usersjoins (joins, wheres, params) for the users to average over.
     */
    protected function add_average_row($label, \core\dml\sql_join $usersjoins) {
        $averagerow = $this->compute_average_row($label, $usersjoins);
        $this->add_data_keyed($averagerow);
    }

    /**
     * Helper userd by {@link add_average_row()}.
     * @param array $gradeaverages the raw grades.
     * @return array the (partial) row of data.
     */
    protected function format_average_grade_for_questions($gradeaverages) {
        $row = array();

        if (!$gradeaverages) {
            $gradeaverages = array();
        }

        foreach ($this->questions as $question) {
            if (isset($gradeaverages[$question->slot]) && $question->maxmark > 0) {
                $record = $gradeaverages[$question->slot];
                $record->grade = flipquiz_rescale_grade(
                        $record->averagefraction * $question->maxmark, $this->flipquiz, false);

            } else {
                $record = new stdClass();
                $record->grade = null;
                $record->numaveraged = 0;
            }

            $row['qsgrade' . $question->slot] = $this->format_average($record, true);
        }

        return $row;
    }

    /**
     * Format an entry in an average row.
     * @param object $record with fields grade and numaveraged.
     * @param bool $question true if this is a question score, false if it is an overall score.
     * @return string HTML fragment for an average score (with number of things included in the average).
     */
    protected function format_average($record, $question = false) {
        if (is_null($record->grade)) {
            $average = '-';
        } else if ($question) {
            $average = flipquiz_format_question_grade($this->flipquiz, $record->grade);
        } else {
            $average = flipquiz_format_grade($this->flipquiz, $record->grade);
        }

        if ($this->download) {
            return $average;
        } else if (is_null($record->numaveraged) || $record->numaveraged == 0) {
            return html_writer::tag('span', html_writer::tag('span',
                    $average, array('class' => 'average')), array('class' => 'avgcell'));
        } else {
            return html_writer::tag('span', html_writer::tag('span',
                    $average, array('class' => 'average')) . ' ' . html_writer::tag('span',
                    '(' . $record->numaveraged . ')', array('class' => 'count')),
                    array('class' => 'avgcell'));
        }
    }

    protected function submit_buttons() {
        if (has_capability('mod/flipquiz:regrade', $this->context)) {
            echo '<input type="submit" class="btn btn-secondary m-r-1" name="regrade" value="' .
                    get_string('regradeselected', 'flipquiz_overview') . '"/>';
        }
        parent::submit_buttons();
    }

    public function col_sumgrades($attempt) {
        if ($attempt->state != flipquiz_attempt::FINISHED) {
            return '-';
        }

        $grade = flipquiz_rescale_grade($attempt->sumgrades, $this->flipquiz);
        if ($this->is_downloading()) {
            return $grade;
        }

        if (isset($this->regradedqs[$attempt->usageid])) {
            $newsumgrade = 0;
            $oldsumgrade = 0;
            foreach ($this->questions as $question) {
                if (isset($this->regradedqs[$attempt->usageid][$question->slot])) {
                    $newsumgrade += $this->regradedqs[$attempt->usageid]
                            [$question->slot]->newfraction * $question->maxmark;
                    $oldsumgrade += $this->regradedqs[$attempt->usageid]
                            [$question->slot]->oldfraction * $question->maxmark;
                } else {
                    $newsumgrade += $this->lateststeps[$attempt->usageid]
                            [$question->slot]->fraction * $question->maxmark;
                    $oldsumgrade += $this->lateststeps[$attempt->usageid]
                            [$question->slot]->fraction * $question->maxmark;
                }
            }
            $newsumgrade = flipquiz_rescale_grade($newsumgrade, $this->flipquiz);
            $oldsumgrade = flipquiz_rescale_grade($oldsumgrade, $this->flipquiz);
            $grade = html_writer::tag('del', $oldsumgrade) . '/' .
                    html_writer::empty_tag('br') . $newsumgrade;
        }
        return html_writer::link(new moodle_url('/mod/flipquiz/review.php',
                array('attempt' => $attempt->attempt)), $grade,
                array('title' => get_string('reviewattempt', 'flipquiz')));
    }

    /**
     * @param string $colname the name of the column.
     * @param object $attempt the row of data - see the SQL in display() in
     * mod/flipquiz/report/overview/report.php to see what fields are present,
     * and what they are called.
     * @return string the contents of the cell.
     */
    public function other_cols($colname, $attempt) {
        if (!preg_match('/^qsgrade(\d+)$/', $colname, $matches)) {
            return null;
        }
        $slot = $matches[1];

        $question = $this->questions[$slot];
        if (!isset($this->lateststeps[$attempt->usageid][$slot])) {
            return '-';
        }

        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        $state = question_state::get($stepdata->state);

        if ($question->maxmark == 0) {
            $grade = '-';
        } else if (is_null($stepdata->fraction)) {
            if ($state == question_state::$needsgrading) {
                $grade = get_string('requiresgrading', 'question');
            } else {
                $grade = '-';
            }
        } else {
            $grade = flipquiz_rescale_grade(
                    $stepdata->fraction * $question->maxmark, $this->flipquiz, 'question');
        }

        if ($this->is_downloading()) {
            return $grade;
        }

        if (isset($this->regradedqs[$attempt->usageid][$slot])) {
            $gradefromdb = $grade;
            $newgrade = flipquiz_rescale_grade(
                    $this->regradedqs[$attempt->usageid][$slot]->newfraction * $question->maxmark,
                    $this->flipquiz, 'question');
            $oldgrade = flipquiz_rescale_grade(
                    $this->regradedqs[$attempt->usageid][$slot]->oldfraction * $question->maxmark,
                    $this->flipquiz, 'question');

            $grade = html_writer::tag('del', $oldgrade) . '/' .
                    html_writer::empty_tag('br') . $newgrade;
        }

        return $this->make_review_link($grade, $attempt, $slot);
    }

    public function col_regraded($attempt) {
        if ($attempt->regraded == '') {
            return '';
        } else if ($attempt->regraded == 0) {
            return get_string('needed', 'flipquiz_overview');
        } else if ($attempt->regraded == 1) {
            return get_string('done', 'flipquiz_overview');
        }
    }

    protected function update_sql_after_count($fields, $from, $where, $params) {
        $fields .= ", COALESCE((
                                SELECT MAX(qqr.regraded)
                                  FROM {flipquiz_overview_regrades} qqr
                                 WHERE qqr.questionusageid = flipquiza.uniqueid
                          ), -1) AS regraded";
        if ($this->options->onlyregraded) {
            $where .= " AND COALESCE((
                                    SELECT MAX(qqr.regraded)
                                      FROM {flipquiz_overview_regrades} qqr
                                     WHERE qqr.questionusageid = flipquiza.uniqueid
                                ), -1) <> -1";
        }
        return [$fields, $from, $where, $params];
    }

    protected function requires_latest_steps_loaded() {
        return $this->options->slotmarks;
    }

    protected function is_latest_step_column($column) {
        if (preg_match('/^qsgrade([0-9]+)/', $column, $matches)) {
            return $matches[1];
        }
        return false;
    }

    protected function get_required_latest_state_fields($slot, $alias) {
        return "$alias.fraction * $alias.maxmark AS qsgrade$slot";
    }

    public function query_db($pagesize, $useinitialsbar = true) {
        parent::query_db($pagesize, $useinitialsbar);

        if ($this->options->slotmarks && has_capability('mod/flipquiz:regrade', $this->context)) {
            $this->regradedqs = $this->get_regraded_questions();
        }
    }

    /**
     * Get all the questions in all the attempts being displayed that need regrading.
     * @return array A two dimensional array $questionusageid => $slot => $regradeinfo.
     */
    protected function get_regraded_questions() {
        global $DB;

        $qubaids = $this->get_qubaids_condition();
        $regradedqs = $DB->get_records_select('flipquiz_overview_regrades',
                'questionusageid ' . $qubaids->usage_id_in(), $qubaids->usage_id_in_params());
        return flipquiz_report_index_by_keys($regradedqs, array('questionusageid', 'slot'));
    }
}
