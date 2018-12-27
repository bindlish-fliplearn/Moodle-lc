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
 * This file defines the homework overview report class.
 *
 * @package   homework_overview
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/homework/report/attemptsreport.php');
require_once($CFG->dirroot . '/mod/homework/report/overview/overview_options.php');
require_once($CFG->dirroot . '/mod/homework/report/overview/overview_form.php');
require_once($CFG->dirroot . '/mod/homework/report/overview/overview_table.php');


/**
 * Quiz report subclass for the overview (grades) report.
 *
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class homework_filp_overview_report extends homework_attempts_report {

    public function display($homework, $cm, $course) {
        global $DB, $OUTPUT, $PAGE;

        list($currentgroup, $studentsjoins, $groupstudentsjoins, $allowedjoins) = $this->init(
                'overview', 'homework_overview_settings_form', $homework, $cm, $course);

        $options = new homework_overview_options('overview', $homework, $cm, $course);

        if ($fromform = $this->form->get_data()) {
            $options->process_settings_from_form($fromform);

        } else {
            $options->process_settings_from_params();
        }

        $this->form->set_data($options->get_initial_form_data());

        // Load the required questions.
        $questions = homework_report_get_significant_questions($homework);

        // Prepare for downloading, if applicable.
        $courseshortname = format_string($course->shortname, true,
                array('context' => context_course::instance($course->id)));
        $table = new homework_overview_table($homework, $this->context, $this->qmsubselect,
                $options, $groupstudentsjoins, $studentsjoins, $questions, $options->get_url());
//        $filename = homework_report_download_filename(get_string('overviewfilename', 'homework_overview'),
//                $courseshortname, $homework->name);
//        $table->is_downloading($options->download, $filename,
//                $courseshortname . ' ' . format_string($homework->name, true));
//        if ($table->is_downloading()) {
//            raise_memory_limit(MEMORY_EXTRA);
//        }

        $this->hasgroupstudents = false;
        if (!empty($groupstudentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                    $groupstudentsjoins->joins
                     WHERE $groupstudentsjoins->wheres";
            $this->hasgroupstudents = $DB->record_exists_sql($sql, $groupstudentsjoins->params);
        }
        $hasstudents = false;
        if (!empty($studentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                    FROM {user} u
                    $studentsjoins->joins
                    WHERE $studentsjoins->wheres";
            $hasstudents = $DB->record_exists_sql($sql, $studentsjoins->params);
        }
        if ($options->attempts == self::ALL_WITH) {
            // This option is only available to users who can access all groups in
            // groups mode, so setting allowed to empty (which means all homework attempts
            // are accessible, is not a security porblem.
            $allowedjoins = new \core\dml\sql_join();
        }

        $this->course = $course; // Hack to make this available in process_actions.
        $this->process_actions($homework, $cm, $currentgroup, $groupstudentsjoins, $allowedjoins, $options->get_url());

        $hasquestions = homework_has_questions($homework->id);

        // Start output.
        if (!$table->is_downloading()) {
            // Only print headers if not asked to download data.
            $this->print_standard_header_and_messages($cm, $course, $homework,
                    $options, $currentgroup, $hasquestions, $hasstudents);

            // Print the display options.
            $this->form->display();
        }

        $hasstudents = $hasstudents && (!$currentgroup || $this->hasgroupstudents);
        if ($hasquestions && ($hasstudents || $options->attempts == self::ALL_WITH)) {
            // Construct the SQL.
            $table->setup_sql_queries($allowedjoins);

            if (!$table->is_downloading()) {
                // Output the regrade buttons.
                if (has_capability('mod/homework:regrade', $this->context)) {
                    $regradesneeded = $this->count_question_attempts_needing_regrade(
                            $homework, $groupstudentsjoins);
//                    if ($currentgroup) {
//                        $a= new stdClass();
//                        $a->groupname = groups_get_group_name($currentgroup);
//                        $a->coursestudents = get_string('participants');
//                        $a->countregradeneeded = $regradesneeded;
//                        $regradealldrydolabel =
//                                get_string('regradealldrydogroup', 'homework_overview', $a);
//                        $regradealldrylabel =
//                                get_string('regradealldrygroup', 'homework_overview', $a);
//                        $regradealllabel =
//                                get_string('regradeallgroup', 'homework_overview', $a);
//                    } else {
//                        $regradealldrydolabel =
//                                get_string('regradealldrydo', 'homework_overview', $regradesneeded);
//                        $regradealldrylabel =
//                                get_string('regradealldry', 'homework_overview');
//                        $regradealllabel =
//                                get_string('regradeall', 'homework_overview');
//                    }
//                    $displayurl = new moodle_url($options->get_url(), array('sesskey' => sesskey()));
//                    echo '<div class="mdl-align">';
//                    echo '<form action="'.$displayurl->out_omit_querystring().'">';
//                    echo '<div>';
//                    echo html_writer::input_hidden_params($displayurl);
//                    echo '<input type="submit" class="btn btn-secondary" name="regradeall" value="'.$regradealllabel.'"/>';
//                    echo '<input type="submit" class="btn btn-secondary m-l-1" name="regradealldry" value="' .
//                            $regradealldrylabel . '"/>';
//                    if ($regradesneeded) {
//                        echo '<input type="submit" class="btn btn-secondary m-l-1" name="regradealldrydo" value="' .
//                                $regradealldrydolabel . '"/>';
//                    }
//                    echo '</div>';
//                    echo '</form>';
//                    echo '</div>';
                }
                // Print information on the grading method.
                if ($strattempthighlight = homework_report_highlighting_grading_method(
                        $homework, $this->qmsubselect, $options->onlygraded)) {
                    echo '<div class="homeworkattemptcounts">' . $strattempthighlight . '</div>';
                }
            }

            // Define table columns.
            $columns = array();
            $headers = array();

            if (!$table->is_downloading() && $options->checkboxcolumn) {
                $columns[] = 'checkbox';
                $headers[] = null;
            }

            $this->add_user_columns($table, $columns, $headers);
//            $this->add_state_column($columns, $headers);
//            $this->add_time_columns($columns, $headers);

            $this->add_grade_columns($homework, $options->usercanseegrades, $columns, $headers, false);

            if (!$table->is_downloading() && has_capability('mod/homework:regrade', $this->context) &&
                    $this->has_regraded_questions($table->sql->from, $table->sql->where, $table->sql->params)) {
                $columns[] = 'regraded';
                $headers[] = get_string('regrade', 'homework_overview');
            }

            if ($options->slotmarks) {
                foreach ($questions as $slot => $question) {
                    // Ignore questions of zero length.
                    $columns[] = 'qsgrade' . $slot;
                    $header = get_string('qbrief', 'homework', $question->number);
                    if (!$table->is_downloading()) {
                        $header .= '<br />';
                    } else {
                        $header .= ' ';
                    }
                    $header .= '/' . homework_rescale_grade($question->maxmark, $homework, 'question');
                    $headers[] = $header;
                }
            }

            $this->set_up_table_columns($table, $columns, $headers, $this->get_base_url(), $options, false);
            $table->set_attribute('class', 'generaltable generalbox grades');

            $table->out($options->pagesize, true);
        }

        if (!$table->is_downloading() && $options->usercanseegrades) {
            $output = $PAGE->get_renderer('mod_homework');
            list($bands, $bandwidth) = self::get_bands_count_and_width($homework);
            $labels = self::get_bands_labels($bands, $bandwidth, $homework);

            if ($currentgroup && $this->hasgroupstudents) {
                $sql = "SELECT qg.id
                          FROM {homework_grades} qg
                          JOIN {user} u on u.id = qg.userid
                        {$groupstudentsjoins->joins}
                          WHERE qg.homework = $homework->id AND {$groupstudentsjoins->wheres}";
                if ($DB->record_exists_sql($sql, $groupstudentsjoins->params)) {
                    $data = homework_report_grade_bands($bandwidth, $bands, $homework->id, $groupstudentsjoins);
                    $chart = self::get_chart($labels, $data);
                    $graphname = get_string('overviewreportgraphgroup', 'homework_overview', groups_get_group_name($currentgroup));
                    //echo $output->chart($chart, $graphname);
                }
            }

            if ($DB->record_exists('homework_grades', array('homework'=> $homework->id))) {
                $data = homework_report_grade_bands($bandwidth, $bands, $homework->id, new \core\dml\sql_join());
                $chart = self::get_chart($labels, $data);
                $graphname = get_string('overviewreportgraph', 'homework_overview');
                //echo $output->chart($chart, $graphname);
            }
        }
        return true;
    }

    /**
     * Extends parent function processing any submitted actions.
     *
     * @param object $homework
     * @param object $cm
     * @param int $currentgroup
     * @param \core\dml\sql_join $groupstudentsjoins (joins, wheres, params)
     * @param \core\dml\sql_join $allowedjoins (joins, wheres, params)
     * @param moodle_url $redirecturl
     */
    protected function process_actions($homework, $cm, $currentgroup, \core\dml\sql_join $groupstudentsjoins,
            \core\dml\sql_join $allowedjoins, $redirecturl) {
        parent::process_actions($homework, $cm, $currentgroup, $groupstudentsjoins, $allowedjoins, $redirecturl);

        if (empty($currentgroup) || $this->hasgroupstudents) {
            if (optional_param('regrade', 0, PARAM_BOOL) && confirm_sesskey()) {
                if ($attemptids = optional_param_array('attemptid', array(), PARAM_INT)) {
                    $this->start_regrade($homework, $cm);
                    $this->regrade_attempts($homework, false, $groupstudentsjoins, $attemptids);
                    $this->finish_regrade($redirecturl);
                }
            }
        }

        if (optional_param('regradeall', 0, PARAM_BOOL) && confirm_sesskey()) {
            $this->start_regrade($homework, $cm);
            $this->regrade_attempts($homework, false, $groupstudentsjoins);
            $this->finish_regrade($redirecturl);

        } else if (optional_param('regradealldry', 0, PARAM_BOOL) && confirm_sesskey()) {
            $this->start_regrade($homework, $cm);
            $this->regrade_attempts($homework, true, $groupstudentsjoins);
            $this->finish_regrade($redirecturl);

        } else if (optional_param('regradealldrydo', 0, PARAM_BOOL) && confirm_sesskey()) {
            $this->start_regrade($homework, $cm);
            $this->regrade_attempts_needing_it($homework, $groupstudentsjoins);
            $this->finish_regrade($redirecturl);
        }
    }

    /**
     * Check necessary capabilities, and start the display of the regrade progress page.
     * @param object $homework the homework settings.
     * @param object $cm the cm object for the homework.
     */
    protected function start_regrade($homework, $cm) {
        require_capability('mod/homework:regrade', $this->context);
        $this->print_header_and_tabs($cm, $this->course, $homework, $this->mode);
    }

    /**
     * Finish displaying the regrade progress page.
     * @param moodle_url $nexturl where to send the user after the regrade.
     * @uses exit. This method never returns.
     */
    protected function finish_regrade($nexturl) {
        global $OUTPUT;
        \core\notification::success(get_string('regradecomplete', 'homework_overview'));
        echo $OUTPUT->continue_button($nexturl);
        echo $OUTPUT->footer();
        die();
    }

    /**
     * Unlock the session and allow the regrading process to run in the background.
     */
    protected function unlock_session() {
        \core\session\manager::write_close();
        ignore_user_abort(true);
    }

    /**
     * Regrade a particular homework attempt. Either for real ($dryrun = false), or
     * as a pretend regrade to see which fractions would change. The outcome is
     * stored in the homework_overview_regrades table.
     *
     * Note, $attempt is not upgraded in the database. The caller needs to do that.
     * However, $attempt->sumgrades is updated, if this is not a dry run.
     *
     * @param object $attempt the homework attempt to regrade.
     * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     * @param array $slots if null, regrade all questions, otherwise, just regrade
     *      the quetsions with those slots.
     */
    protected function regrade_attempt($attempt, $dryrun = false, $slots = null) {
        global $DB;
        // Need more time for a homework with many questions.
        core_php_time_limit::raise(300);

        $transaction = $DB->start_delegated_transaction();

        $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);

        if (is_null($slots)) {
            $slots = $quba->get_slots();
        }

        $finished = $attempt->state == homework_attempt::FINISHED;
        foreach ($slots as $slot) {
            $qqr = new stdClass();
            $qqr->oldfraction = $quba->get_question_fraction($slot);

            $quba->regrade_question($slot, $finished);

            $qqr->newfraction = $quba->get_question_fraction($slot);

            if (abs($qqr->oldfraction - $qqr->newfraction) > 1e-7) {
                $qqr->questionusageid = $quba->get_id();
                $qqr->slot = $slot;
                $qqr->regraded = empty($dryrun);
                $qqr->timemodified = time();
                $DB->insert_record('homework_overview_regrades', $qqr, false);
            }
        }

        if (!$dryrun) {
            question_engine::save_questions_usage_by_activity($quba);
        }

        $transaction->allow_commit();

        // Really, PHP should not need this hint, but without this, we just run out of memory.
        $quba = null;
        $transaction = null;
        gc_collect_cycles();
    }

    /**
     * Regrade attempts for this homework, exactly which attempts are regraded is
     * controlled by the parameters.
     * @param object $homework the homework settings.
     * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     * @param \core\dml\sql_join|array $groupstudentsjoins empty for all attempts, otherwise regrade attempts
     * for these users.
     * @param array $attemptids blank for all attempts, otherwise only regrade
     * attempts whose id is in this list.
     */
    protected function regrade_attempts($homework, $dryrun = false,
            \core\dml\sql_join$groupstudentsjoins = null, $attemptids = array()) {
        global $DB;
        $this->unlock_session();

        $sql = "SELECT homeworka.*
                  FROM {homework_attempts} homeworka";
        $where = "homework = :qid AND preview = 0";
        $params = array('qid' => $homework->id);

        if ($this->hasgroupstudents && !empty($groupstudentsjoins->joins)) {
            $sql .= "\nJOIN {user} u ON u.id = homeworka.userid
                    {$groupstudentsjoins->joins}";
            $where .= " AND {$groupstudentsjoins->wheres}";
            $params += $groupstudentsjoins->params;
        }

        if ($attemptids) {
            $aids = join(',', $attemptids);
            $where .= " AND homeworka.id IN ({$aids})";
        }

        $sql .= "\nWHERE {$where}";
        $attempts = $DB->get_records_sql($sql, $params);
        if (!$attempts) {
            return;
        }

        $this->clear_regrade_table($homework, $groupstudentsjoins);

        $progressbar = new progress_bar('homework_overview_regrade', 500, true);
        $a = array(
            'count' => count($attempts),
            'done'  => 0,
        );
        foreach ($attempts as $attempt) {
            $this->regrade_attempt($attempt, $dryrun);
            $a['done']++;
            $progressbar->update($a['done'], $a['count'],
                    get_string('regradingattemptxofy', 'homework_overview', $a));
        }

        if (!$dryrun) {
            $this->update_overall_grades($homework);
        }
    }

    /**
     * Regrade those questions in those attempts that are marked as needing regrading
     * in the homework_overview_regrades table.
     * @param object $homework the homework settings.
     * @param \core\dml\sql_join $groupstudentsjoins empty for all attempts, otherwise regrade attempts
     * for these users.
     */
    protected function regrade_attempts_needing_it($homework, \core\dml\sql_join $groupstudentsjoins) {
        global $DB;
        $this->unlock_session();

        $join = '{homework_overview_regrades} qqr ON qqr.questionusageid = homeworka.uniqueid';
        $where = "homeworka.homework = :qid AND homeworka.preview = 0 AND qqr.regraded = 0";
        $params = array('qid' => $homework->id);

        // Fetch all attempts that need regrading.
        if ($this->hasgroupstudents && !empty($groupstudentsjoins->joins)) {
            $join .= "\nJOIN {user} u ON u.id = homeworka.userid
                    {$groupstudentsjoins->joins}";
            $where .= " AND {$groupstudentsjoins->wheres}";
            $params += $groupstudentsjoins->params;
        }

        $toregrade = $DB->get_recordset_sql("
                SELECT homeworka.uniqueid, qqr.slot
                  FROM {homework_attempts} homeworka
                  JOIN $join
                 WHERE $where", $params);

        $attemptquestions = array();
        foreach ($toregrade as $row) {
            $attemptquestions[$row->uniqueid][] = $row->slot;
        }
        $toregrade->close();

        if (!$attemptquestions) {
            return;
        }

        $attempts = $DB->get_records_list('homework_attempts', 'uniqueid',
                array_keys($attemptquestions));

        $this->clear_regrade_table($homework, $groupstudentsjoins);

        $progressbar = new progress_bar('homework_overview_regrade', 500, true);
        $a = array(
            'count' => count($attempts),
            'done'  => 0,
        );
        foreach ($attempts as $attempt) {
            $this->regrade_attempt($attempt, false, $attemptquestions[$attempt->uniqueid]);
            $a['done']++;
            $progressbar->update($a['done'], $a['count'],
                    get_string('regradingattemptxofy', 'homework_overview', $a));
        }

        $this->update_overall_grades($homework);
    }

    /**
     * Count the number of attempts in need of a regrade.
     * @param object $homework the homework settings.
     * @param \core\dml\sql_join $groupstudentsjoins (joins, wheres, params) If this is given, only data relating
     * to these users is cleared.
     */
    protected function count_question_attempts_needing_regrade($homework, \core\dml\sql_join $groupstudentsjoins) {
        global $DB;

        $userjoin = '';
        $usertest = '';
        $params = array();
        if ($this->hasgroupstudents) {
            $userjoin = "JOIN {user} u ON u.id = homeworka.userid
                    {$groupstudentsjoins->joins}";
            $usertest = "{$groupstudentsjoins->wheres} AND u.id = homeworka.userid AND ";
            $params = $groupstudentsjoins->params;
        }

        $params['chomework'] = $homework->id;
        $sql = "SELECT COUNT(DISTINCT homeworka.id)
                  FROM {homework_attempts} homeworka
                  JOIN {homework_overview_regrades} qqr ON homeworka.uniqueid = qqr.questionusageid
                $userjoin
                 WHERE
                      $usertest
                      homeworka.homework = :chomework AND
                      homeworka.preview = 0 AND
                      qqr.regraded = 0";
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Are there any pending regrades in the table we are going to show?
     * @param string $from tables used by the main query.
     * @param string $where where clause used by the main query.
     * @param array $params required by the SQL.
     * @return bool whether there are pending regrades.
     */
    protected function has_regraded_questions($from, $where, $params) {
        global $DB;
        return $DB->record_exists_sql("
                SELECT 1
                  FROM {$from}
                  JOIN {homework_overview_regrades} qor ON qor.questionusageid = homeworka.uniqueid
                 WHERE {$where}", $params);
    }

    /**
     * Remove all information about pending/complete regrades from the database.
     * @param object $homework the homework settings.
     * @param \core\dml\sql_join $groupstudentsjoins (joins, wheres, params). If this is given, only data relating
     * to these users is cleared.
     */
    protected function clear_regrade_table($homework, \core\dml\sql_join $groupstudentsjoins) {
        global $DB;

        // Fetch all attempts that need regrading.
        $select = "questionusageid IN (
                    SELECT uniqueid
                      FROM {homework_attempts} homeworka";
        $where = "WHERE homeworka.homework = :qid";
        $params = array('qid' => $homework->id);
        if ($this->hasgroupstudents && !empty($groupstudentsjoins->joins)) {
            $select .= "\nJOIN {user} u ON u.id = homeworka.userid
                    {$groupstudentsjoins->joins}";
            $where .= " AND {$groupstudentsjoins->wheres}";
            $params += $groupstudentsjoins->params;
        }
        $select .= "\n$where)";

        $DB->delete_records_select('homework_overview_regrades', $select, $params);
    }

    /**
     * Update the final grades for all attempts. This method is used following
     * a regrade.
     * @param object $homework the homework settings.
     * @param array $userids only update scores for these userids.
     * @param array $attemptids attemptids only update scores for these attempt ids.
     */
    protected function update_overall_grades($homework) {
        homework_update_all_attempt_sumgrades($homework);
        homework_update_all_final_grades($homework);
        homework_update_grades($homework);
    }

    /**
     * Get the bands configuration for the homework.
     *
     * This returns the configuration for having between 11 and 20 bars in
     * a chart based on the maximum grade to be given on a homework. The width of
     * a band is the number of grade points it encapsulates.
     *
     * @param object $homework The homework object.
     * @return array Contains the number of bands, and their width.
     */
    public static function get_bands_count_and_width($homework) {
        $bands = $homework->grade;
        while ($bands > 20 || $bands <= 10) {
            if ($bands > 50) {
                $bands /= 5;
            } else if ($bands > 20) {
                $bands /= 2;
            }
            if ($bands < 4) {
                $bands *= 5;
            } else if ($bands <= 10) {
                $bands *= 2;
            }
        }
        // See MDL-34589. Using doubles as array keys causes problems in PHP 5.4, hence the explicit cast to int.
        $bands = (int) ceil($bands);
        return [$bands, $homework->grade / $bands];
    }

    /**
     * Get the bands labels.
     *
     * @param int $bands The number of bands.
     * @param int $bandwidth The band width.
     * @param object $homework The homework object.
     * @return string[] The labels.
     */
    public static function get_bands_labels($bands, $bandwidth, $homework) {
        $bandlabels = [];
        for ($i = 1; $i <= $bands; $i++) {
            $bandlabels[] = homework_format_grade($homework, ($i - 1) * $bandwidth) . ' - ' . homework_format_grade($homework, $i * $bandwidth);
        }
        return $bandlabels;
    }

    /**
     * Get a chart.
     *
     * @param string[] $labels Chart labels.
     * @param int[] $data The data.
     * @return \core\chart_base
     */
    protected static function get_chart($labels, $data) {
        $chart = new \core\chart_bar();
        $chart->set_labels($labels);
        $chart->get_xaxis(0, true)->set_label(get_string('grade'));

        $yaxis = $chart->get_yaxis(0, true);
        $yaxis->set_label(get_string('participants'));
        $yaxis->set_stepsize(max(1, round(max($data) / 10)));

        $series = new \core\chart_series(get_string('participants'), $data);
        $chart->add_series($series);
        return $chart;
    }
}
