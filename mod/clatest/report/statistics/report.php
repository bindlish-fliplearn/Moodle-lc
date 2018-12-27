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
 * Quiz statistics report class.
 *
 * @package   clatest_statistics
 * @copyright 2014 Open University
 * @author    James Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/clatest/report/statistics/statistics_form.php');
require_once($CFG->dirroot . '/mod/clatest/report/statistics/statistics_table.php');
require_once($CFG->dirroot . '/mod/clatest/report/statistics/statistics_question_table.php');
require_once($CFG->dirroot . '/mod/clatest/report/statistics/statisticslib.php');
/**
 * The clatest statistics report provides summary information about each question in
 * a clatest, compared to the whole clatest. It also provides a drill-down to more
 * detailed information about each question.
 *
 * @copyright 2008 Jamie Pratt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clatest_statistics_report extends clatest_default_report {

    /** @var context_module context of this clatest.*/
    protected $context;

    /** @var clatest_statistics_table instance of table class used for main questions stats table. */
    protected $table;

    /** @var \core\progress\base|null $progress Handles progress reporting or not. */
    protected $progress = null;

    /**
     * Display the report.
     */
    public function display($clatest, $cm, $course) {
        global $OUTPUT, $DB;

        raise_memory_limit(MEMORY_HUGE);

        $this->context = context_module::instance($cm->id);

        if (!clatest_has_questions($clatest->id)) {
            $this->print_header_and_tabs($cm, $course, $clatest, 'statistics');
            echo clatest_no_questions_message($clatest, $cm, $this->context);
            return true;
        }

        // Work out the display options.
        $download = optional_param('download', '', PARAM_ALPHA);
        $everything = optional_param('everything', 0, PARAM_BOOL);
        $recalculate = optional_param('recalculate', 0, PARAM_BOOL);
        // A qid paramter indicates we should display the detailed analysis of a sub question.
        $qid = optional_param('qid', 0, PARAM_INT);
        $slot = optional_param('slot', 0, PARAM_INT);
        $variantno = optional_param('variant', null, PARAM_INT);
        $whichattempts = optional_param('whichattempts', $clatest->grademethod, PARAM_INT);
        $whichtries = optional_param('whichtries', question_attempt::LAST_TRY, PARAM_ALPHA);

        $pageoptions = array();
        $pageoptions['id'] = $cm->id;
        $pageoptions['mode'] = 'statistics';

        $reporturl = new moodle_url('/mod/clatest/report.php', $pageoptions);

        $mform = new clatest_statistics_settings_form($reporturl, compact('clatest'));

        $mform->set_data(array('whichattempts' => $whichattempts, 'whichtries' => $whichtries));

        if ($whichattempts != $clatest->grademethod) {
            $reporturl->param('whichattempts', $whichattempts);
        }

        if ($whichtries != question_attempt::LAST_TRY) {
            $reporturl->param('whichtries', $whichtries);
        }

        // Find out current groups mode.
        $currentgroup = $this->get_current_group($cm, $course, $this->context);
        $nostudentsingroup = false; // True if a group is selected and there is no one in it.
        if (empty($currentgroup)) {
            $currentgroup = 0;
            $groupstudentsjoins = new \core\dml\sql_join();

        } else if ($currentgroup == self::NO_GROUPS_ALLOWED) {
            $groupstudentsjoins = new \core\dml\sql_join();
            $nostudentsingroup = true;

        } else {
            // All users who can attempt clatestzes and who are in the currently selected group.
            $groupstudentsjoins = get_enrolled_with_capabilities_join($this->context, '',
                    array('mod/clatest:reviewmyattempts', 'mod/clatest:attempt'), $currentgroup);
            if (!empty($groupstudentsjoins->joins)) {
                $sql = "SELECT DISTINCT u.id
                    FROM {user} u
                    {$groupstudentsjoins->joins}
                    WHERE {$groupstudentsjoins->wheres}";
                if (!$DB->record_exists_sql($sql, $groupstudentsjoins->params)) {
                    $nostudentsingroup = true;
                }
            }
        }

        $qubaids = clatest_statistics_qubaids_condition($clatest->id, $groupstudentsjoins, $whichattempts);

        // If recalculate was requested, handle that.
        if ($recalculate && confirm_sesskey()) {
            $this->clear_cached_data($qubaids);
            redirect($reporturl);
        }

        // Set up the main table.
        $this->table = new clatest_statistics_table();
        if ($everything) {
            $report = get_string('completestatsfilename', 'clatest_statistics');
        } else {
            $report = get_string('questionstatsfilename', 'clatest_statistics');
        }
        $courseshortname = format_string($course->shortname, true,
                array('context' => context_course::instance($course->id)));
        $filename = clatest_report_download_filename($report, $courseshortname, $clatest->name);
        $this->table->is_downloading($download, $filename,
                get_string('clateststructureanalysis', 'clatest_statistics'));
        $questions = $this->load_and_initialise_questions_for_calculations($clatest);

        // Print the page header stuff (if not downloading.
        if (!$this->table->is_downloading()) {
            $this->print_header_and_tabs($cm, $course, $clatest, 'statistics');
        }

        if (!$nostudentsingroup) {
            // Get the data to be displayed.
            $progress = $this->get_progress_trace_instance();
            list($clateststats, $questionstats) =
                $this->get_all_stats_and_analysis($clatest, $whichattempts, $whichtries, $groupstudentsjoins, $questions, $progress);
        } else {
            // Or create empty stats containers.
            $clateststats = new \clatest_statistics\calculated($whichattempts);
            $questionstats = new \core_question\statistics\questions\all_calculated_for_qubaid_condition();
        }

        // Set up the table.
        $this->table->statistics_setup($clatest, $cm->id, $reporturl, $clateststats->s());

        // Print the rest of the page header stuff (if not downloading.
        if (!$this->table->is_downloading()) {

            if (groups_get_activity_groupmode($cm)) {
                groups_print_activity_menu($cm, $reporturl->out());
                if ($currentgroup && $nostudentsingroup) {
                    $OUTPUT->notification(get_string('nostudentsingroup', 'clatest_statistics'));
                }
            }

            if (!$this->table->is_downloading() && $clateststats->s() == 0) {
                echo $OUTPUT->notification(get_string('nogradedattempts', 'clatest_statistics'));
            }

            foreach ($questionstats->any_error_messages() as $errormessage) {
                echo $OUTPUT->notification($errormessage);
            }

            // Print display options form.
            $mform->display();
        }

        if ($everything) { // Implies is downloading.
            // Overall report, then the analysis of each question.
            $clatestinfo = $clateststats->get_formatted_clatest_info_data($course, $cm, $clatest);
            $this->download_clatest_info_table($clatestinfo);

            if ($clateststats->s()) {
                $this->output_clatest_structure_analysis_table($questionstats);

                if ($this->table->is_downloading() == 'html' && $clateststats->s() != 0) {
                    $this->output_statistics_graph($clatest->id, $qubaids);
                }

                $this->output_all_question_response_analysis($qubaids, $questions, $questionstats, $reporturl, $whichtries);
            }

            $this->table->export_class_instance()->finish_document();

        } else if ($qid) {
            // Report on an individual sub-question indexed questionid.
            if (!$questionstats->has_subq($qid, $variantno)) {
                print_error('questiondoesnotexist', 'question');
            }

            $this->output_individual_question_data($clatest, $questionstats->for_subq($qid, $variantno));
            $this->output_individual_question_response_analysis($questionstats->for_subq($qid, $variantno)->question,
                                                                $variantno,
                                                                $questionstats->for_subq($qid, $variantno)->s,
                                                                $reporturl,
                                                                $qubaids,
                                                                $whichtries);
            // Back to overview link.
            echo $OUTPUT->box('<a href="' . $reporturl->out() . '">' .
                              get_string('backtoclatestreport', 'clatest_statistics') . '</a>',
                              'boxaligncenter generalbox boxwidthnormal mdl-align');
        } else if ($slot) {
            // Report on an individual question indexed by position.
            if (!isset($questions[$slot])) {
                print_error('questiondoesnotexist', 'question');
            }

            if ($variantno === null &&
                                ($questionstats->for_slot($slot)->get_sub_question_ids()
                                || $questionstats->for_slot($slot)->get_variants())) {
                if (!$this->table->is_downloading()) {
                    $number = $questionstats->for_slot($slot)->question->number;
                    echo $OUTPUT->heading(get_string('slotstructureanalysis', 'clatest_statistics', $number), 3);
                }
                $this->table->define_baseurl(new moodle_url($reporturl, array('slot' => $slot)));
                $this->table->format_and_add_array_of_rows($questionstats->structure_analysis_for_one_slot($slot));
            } else {
                $this->output_individual_question_data($clatest, $questionstats->for_slot($slot, $variantno));
                $this->output_individual_question_response_analysis($questions[$slot],
                                                                    $variantno,
                                                                    $questionstats->for_slot($slot, $variantno)->s,
                                                                    $reporturl,
                                                                    $qubaids,
                                                                    $whichtries);
            }
            if (!$this->table->is_downloading()) {
                // Back to overview link.
                echo $OUTPUT->box('<a href="' . $reporturl->out() . '">' .
                        get_string('backtoclatestreport', 'clatest_statistics') . '</a>',
                        'backtomainstats boxaligncenter generalbox boxwidthnormal mdl-align');
            } else {
                $this->table->finish_output();
            }

        } else if ($this->table->is_downloading()) {
            // Downloading overview report.
            $clatestinfo = $clateststats->get_formatted_clatest_info_data($course, $cm, $clatest);
            $this->download_clatest_info_table($clatestinfo);
            if ($clateststats->s()) {
                $this->output_clatest_structure_analysis_table($questionstats);
            }
            $this->table->export_class_instance()->finish_document();

        } else {
            // On-screen display of overview report.
            echo $OUTPUT->heading(get_string('clatestinformation', 'clatest_statistics'), 3);
            echo $this->output_caching_info($clateststats->timemodified, $clatest->id, $groupstudentsjoins, $whichattempts, $reporturl);
            echo $this->everything_download_options($reporturl);
            $clatestinfo = $clateststats->get_formatted_clatest_info_data($course, $cm, $clatest);
            echo $this->output_clatest_info_table($clatestinfo);
            if ($clateststats->s()) {
                echo $OUTPUT->heading(get_string('clateststructureanalysis', 'clatest_statistics'), 3);
                $this->output_clatest_structure_analysis_table($questionstats);
                $this->output_statistics_graph($clatest, $qubaids);
            }
        }

        return true;
    }

    /**
     * Display the statistical and introductory information about a question.
     * Only called when not downloading.
     *
     * @param object                                         $clatest         the clatest settings.
     * @param \core_question\statistics\questions\calculated $questionstat the question to report on.
     */
    protected function output_individual_question_data($clatest, $questionstat) {
        global $OUTPUT;

        // On-screen display. Show a summary of the question's place in the clatest,
        // and the question statistics.
        $datumfromtable = $this->table->format_row($questionstat);

        // Set up the question info table.
        $questioninfotable = new html_table();
        $questioninfotable->align = array('center', 'center');
        $questioninfotable->width = '60%';
        $questioninfotable->attributes['class'] = 'generaltable titlesleft';

        $questioninfotable->data = array();
        $questioninfotable->data[] = array(get_string('modulename', 'clatest'), $clatest->name);
        $questioninfotable->data[] = array(get_string('questionname', 'clatest_statistics'),
                $questionstat->question->name.'&nbsp;'.$datumfromtable['actions']);

        if ($questionstat->variant !== null) {
            $questioninfotable->data[] = array(get_string('variant', 'clatest_statistics'), $questionstat->variant);

        }
        $questioninfotable->data[] = array(get_string('questiontype', 'clatest_statistics'),
                $datumfromtable['icon'] . '&nbsp;' .
                question_bank::get_qtype($questionstat->question->qtype, false)->menu_name() . '&nbsp;' .
                $datumfromtable['icon']);
        $questioninfotable->data[] = array(get_string('positions', 'clatest_statistics'),
                $questionstat->positions);

        // Set up the question statistics table.
        $questionstatstable = new html_table();
        $questionstatstable->align = array('center', 'center');
        $questionstatstable->width = '60%';
        $questionstatstable->attributes['class'] = 'generaltable titlesleft';

        unset($datumfromtable['number']);
        unset($datumfromtable['icon']);
        $actions = $datumfromtable['actions'];
        unset($datumfromtable['actions']);
        unset($datumfromtable['name']);
        $labels = array(
            's' => get_string('attempts', 'clatest_statistics'),
            'facility' => get_string('facility', 'clatest_statistics'),
            'sd' => get_string('standarddeviationq', 'clatest_statistics'),
            'random_guess_score' => get_string('random_guess_score', 'clatest_statistics'),
            'intended_weight' => get_string('intended_weight', 'clatest_statistics'),
            'effective_weight' => get_string('effective_weight', 'clatest_statistics'),
            'discrimination_index' => get_string('discrimination_index', 'clatest_statistics'),
            'discriminative_efficiency' =>
                                get_string('discriminative_efficiency', 'clatest_statistics')
        );
        foreach ($datumfromtable as $item => $value) {
            $questionstatstable->data[] = array($labels[$item], $value);
        }

        // Display the various bits.
        echo $OUTPUT->heading(get_string('questioninformation', 'clatest_statistics'), 3);
        echo html_writer::table($questioninfotable);
        echo $this->render_question_text($questionstat->question);
        echo $OUTPUT->heading(get_string('questionstatistics', 'clatest_statistics'), 3);
        echo html_writer::table($questionstatstable);
    }

    /**
     * Output question text in a box with urls appropriate for a preview of the question.
     *
     * @param object $question question data.
     * @return string HTML of question text, ready for display.
     */
    protected function render_question_text($question) {
        global $OUTPUT;

        $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $this->context->id, 'clatest_statistics');

        return $OUTPUT->box(format_text($text, $question->questiontextformat,
                array('noclean' => true, 'para' => false, 'overflowdiv' => true)),
                'questiontext boxaligncenter generalbox boxwidthnormal mdl-align');
    }

    /**
     * Display the response analysis for a question.
     *
     * @param object           $question  the question to report on.
     * @param int|null         $variantno the variant
     * @param int              $s
     * @param moodle_url       $reporturl the URL to redisplay this report.
     * @param qubaid_condition $qubaids
     * @param string           $whichtries
     */
    protected function output_individual_question_response_analysis($question, $variantno, $s, $reporturl, $qubaids,
                                                                    $whichtries = question_attempt::LAST_TRY) {
        global $OUTPUT;

        if (!question_bank::get_qtype($question->qtype, false)->can_analyse_responses()) {
            return;
        }

        $qtable = new clatest_statistics_question_table($question->id);
        $exportclass = $this->table->export_class_instance();
        $qtable->export_class_instance($exportclass);
        if (!$this->table->is_downloading()) {
            // Output an appropriate title.
            echo $OUTPUT->heading(get_string('analysisofresponses', 'clatest_statistics'), 3);

        } else {
            // Work out an appropriate title.
            $a = clone($question);
            $a->variant = $variantno;

            if (!empty($question->number) && !is_null($variantno)) {
                $questiontabletitle = get_string('analysisnovariant', 'clatest_statistics', $a);
            } else if (!empty($question->number)) {
                $questiontabletitle = get_string('analysisno', 'clatest_statistics', $a);
            } else if (!is_null($variantno)) {
                $questiontabletitle = get_string('analysisvariant', 'clatest_statistics', $a);
            } else {
                $questiontabletitle = get_string('analysisnameonly', 'clatest_statistics', $a);
            }

            if ($this->table->is_downloading() == 'html') {
                $questiontabletitle = get_string('analysisofresponsesfor', 'clatest_statistics', $questiontabletitle);
            }

            // Set up the table.
            $exportclass->start_table($questiontabletitle);

            if ($this->table->is_downloading() == 'html') {
                echo $this->render_question_text($question);
            }
        }

        $responesanalyser = new \core_question\statistics\responses\analyser($question, $whichtries);
        $responseanalysis = $responesanalyser->load_cached($qubaids, $whichtries);

        $qtable->question_setup($reporturl, $question, $s, $responseanalysis);
        if ($this->table->is_downloading()) {
            $exportclass->output_headers($qtable->headers);
        }

        // Where no variant no is specified the variant no is actually one.
        if ($variantno === null) {
            $variantno = 1;
        }
        foreach ($responseanalysis->get_subpart_ids($variantno) as $partid) {
            $subpart = $responseanalysis->get_analysis_for_subpart($variantno, $partid);
            foreach ($subpart->get_response_class_ids() as $responseclassid) {
                $responseclass = $subpart->get_response_class($responseclassid);
                $tabledata = $responseclass->data_for_question_response_table($subpart->has_multiple_response_classes(), $partid);
                foreach ($tabledata as $row) {
                    $qtable->add_data_keyed($qtable->format_row($row));
                }
            }
        }

        $qtable->finish_output(!$this->table->is_downloading());
    }

    /**
     * Output the table that lists all the questions in the clatest with their statistics.
     *
     * @param \core_question\statistics\questions\all_calculated_for_qubaid_condition $questionstats the stats for all questions in
     *                                                                                               the clatest including subqs and
     *                                                                                               variants.
     */
    protected function output_clatest_structure_analysis_table($questionstats) {
        $tooutput = array();
        $limitvariants = !$this->table->is_downloading();
        foreach ($questionstats->get_all_slots() as $slot) {
            // Output the data for these question statistics.
            $tooutput = array_merge($tooutput, $questionstats->structure_analysis_for_one_slot($slot, $limitvariants));
        }
        $this->table->format_and_add_array_of_rows($tooutput);
    }

    /**
     * Return HTML for table of overall clatest statistics.
     *
     * @param array $clatestinfo as returned by {@link get_formatted_clatest_info_data()}.
     * @return string the HTML.
     */
    protected function output_clatest_info_table($clatestinfo) {

        $clatestinfotable = new html_table();
        $clatestinfotable->align = array('center', 'center');
        $clatestinfotable->width = '60%';
        $clatestinfotable->attributes['class'] = 'generaltable titlesleft';
        $clatestinfotable->data = array();

        foreach ($clatestinfo as $heading => $value) {
             $clatestinfotable->data[] = array($heading, $value);
        }

        return html_writer::table($clatestinfotable);
    }

    /**
     * Download the table of overall clatest statistics.
     *
     * @param array $clatestinfo as returned by {@link get_formatted_clatest_info_data()}.
     */
    protected function download_clatest_info_table($clatestinfo) {
        global $OUTPUT;

        // HTML download is a special case.
        if ($this->table->is_downloading() == 'html') {
            echo $OUTPUT->heading(get_string('clatestinformation', 'clatest_statistics'), 3);
            echo $this->output_clatest_info_table($clatestinfo);
            return;
        }

        // Reformat the data ready for output.
        $headers = array();
        $row = array();
        foreach ($clatestinfo as $heading => $value) {
            $headers[] = $heading;
            $row[] = $value;
        }

        // Do the output.
        $exportclass = $this->table->export_class_instance();
        $exportclass->start_table(get_string('clatestinformation', 'clatest_statistics'));
        $exportclass->output_headers($headers);
        $exportclass->add_data($row);
        $exportclass->finish_table();
    }

    /**
     * Output the HTML needed to show the statistics graph.
     *
     * @param int|object $clatestorid The clatest, or its ID.
     * @param qubaid_condition $qubaids the question usages whose responses to analyse.
     * @param string $whichattempts Which attempts constant.
     */
    protected function output_statistics_graph($clatestorid, $qubaids) {
        global $DB, $PAGE;

        $clatest = $clatestorid;
        if (!is_object($clatest)) {
            $clatest = $DB->get_record('clatest', array('id' => $clatestorid), '*', MUST_EXIST);
        }

        // Load the rest of the required data.
        $questions = clatest_report_get_significant_questions($clatest);

        // Only load main question not sub questions.
        $questionstatistics = $DB->get_records_select('question_statistics',
                'hashcode = ? AND slot IS NOT NULL AND variant IS NULL',
            [$qubaids->get_hash_code()]);

        // Configure what to display.
        $fieldstoplot = [
            'facility' => get_string('facility', 'clatest_statistics'),
            'discriminativeefficiency' => get_string('discriminative_efficiency', 'clatest_statistics')
        ];
        $fieldstoplotfactor = ['facility' => 100, 'discriminativeefficiency' => 1];

        // Prepare the arrays to hold the data.
        $xdata = [];
        foreach (array_keys($fieldstoplot) as $fieldtoplot) {
            $ydata[$fieldtoplot] = [];
        }

        // Fill in the data for each question.
        foreach ($questionstatistics as $questionstatistic) {
            $number = $questions[$questionstatistic->slot]->number;
            $xdata[$number] = $number;

            foreach ($fieldstoplot as $fieldtoplot => $notused) {
                $value = $questionstatistic->$fieldtoplot;
                if (is_null($value)) {
                    $value = 0;
                }
                $value *= $fieldstoplotfactor[$fieldtoplot];
                $ydata[$fieldtoplot][$number] = number_format($value, 2);
            }
        }

        // Create the chart.
        sort($xdata);
        $chart = new \core\chart_bar();
        $chart->get_xaxis(0, true)->set_label(get_string('position', 'clatest_statistics'));
        $chart->set_labels(array_values($xdata));

        foreach ($fieldstoplot as $fieldtoplot => $notused) {
            ksort($ydata[$fieldtoplot]);
            $series = new \core\chart_series($fieldstoplot[$fieldtoplot], array_values($ydata[$fieldtoplot]));
            $chart->add_series($series);
        }

        // Find max.
        $max = 0;
        foreach ($fieldstoplot as $fieldtoplot => $notused) {
            $max = max($max, max($ydata[$fieldtoplot]));
        }

        // Set Y properties.
        $yaxis = $chart->get_yaxis(0, true);
        $yaxis->set_stepsize(10);
        $yaxis->set_label('%');

        $output = $PAGE->get_renderer('mod_clatest');
        $graphname = get_string('statisticsreportgraph', 'clatest_statistics');
        echo $output->chart($chart, $graphname);
    }

    /**
     * Get the clatest and question statistics, either by loading the cached results,
     * or by recomputing them.
     *
     * @param object $clatest               the clatest settings.
     * @param string $whichattempts      which attempts to use, represented internally as one of the constants as used in
     *                                   $clatest->grademethod ie.
     *                                   QUIZ_GRADEAVERAGE, QUIZ_GRADEHIGHEST, QUIZ_ATTEMPTLAST or QUIZ_ATTEMPTFIRST
     *                                   we calculate stats based on which attempts would affect the grade for each student.
     * @param string $whichtries         which tries to analyse for response analysis. Will be one of
     *                                   question_attempt::FIRST_TRY, LAST_TRY or ALL_TRIES.
     * @param \core\dml\sql_join $groupstudentsjoins Contains joins, wheres, params for students in this group.
     * @param array  $questions          full question data.
     * @param \core\progress\base|null   $progress
     * @return array with 2 elements:    - $clateststats The statistics for overall attempt scores.
     *                                   - $questionstats \core_question\statistics\questions\all_calculated_for_qubaid_condition
     */
    public function get_all_stats_and_analysis(
            $clatest, $whichattempts, $whichtries, \core\dml\sql_join $groupstudentsjoins, $questions, $progress = null) {

        if ($progress === null) {
            $progress = new \core\progress\none();
        }

        $qubaids = clatest_statistics_qubaids_condition($clatest->id, $groupstudentsjoins, $whichattempts);

        $qcalc = new \core_question\statistics\questions\calculator($questions, $progress);

        $clatestcalc = new \clatest_statistics\calculator($progress);

        $progress->start_progress('', 3);
        if ($clatestcalc->get_last_calculated_time($qubaids) === false) {

            // Recalculate now.
            $questionstats = $qcalc->calculate($qubaids);
            $progress->progress(1);

            $clateststats = $clatestcalc->calculate($clatest->id, $whichattempts, $groupstudentsjoins, count($questions),
                                              $qcalc->get_sum_of_mark_variance());
            $progress->progress(2);
        } else {
            $clateststats = $clatestcalc->get_cached($qubaids);
            $progress->progress(1);
            $questionstats = $qcalc->get_cached($qubaids);
            $progress->progress(2);
        }

        if ($clateststats->s()) {
            $subquestions = $questionstats->get_sub_questions();
            $this->analyse_responses_for_all_questions_and_subquestions($questions,
                                                                        $subquestions,
                                                                        $qubaids,
                                                                        $whichtries,
                                                                        $progress);
        }
        $progress->progress(3);
        $progress->end_progress();

        return array($clateststats, $questionstats);
    }

    /**
     * Appropriate instance depending if we want html output for the user or not.
     *
     * @return \core\progress\base child of \core\progress\base to handle the display (or not) of task progress.
     */
    protected function get_progress_trace_instance() {
        if ($this->progress === null) {
            if (!$this->table->is_downloading()) {
                $this->progress = new \core\progress\display_if_slow(get_string('calculatingallstats', 'clatest_statistics'));
                $this->progress->set_display_names();
            } else {
                $this->progress = new \core\progress\none();
            }
        }
        return $this->progress;
    }

    /**
     * Analyse responses for all questions and sub questions in this clatest.
     *
     * @param object[] $questions as returned by self::load_and_initialise_questions_for_calculations
     * @param object[] $subquestions full question objects.
     * @param qubaid_condition $qubaids the question usages whose responses to analyse.
     * @param string $whichtries which tries to analyse \question_attempt::FIRST_TRY, LAST_TRY or ALL_TRIES.
     * @param null|\core\progress\base $progress Used to indicate progress of task.
     */
    protected function analyse_responses_for_all_questions_and_subquestions($questions, $subquestions, $qubaids,
                                                                            $whichtries, $progress = null) {
        if ($progress === null) {
            $progress = new \core\progress\none();
        }

        // Starting response analysis tasks.
        $progress->start_progress('', count($questions) + count($subquestions));

        $done = $this->analyse_responses_for_questions($questions, $qubaids, $whichtries, $progress);

        $this->analyse_responses_for_questions($subquestions, $qubaids, $whichtries, $progress, $done);

        // Finished all response analysis tasks.
        $progress->end_progress();
    }

    /**
     * Analyse responses for an array of questions or sub questions.
     *
     * @param object[] $questions  as returned by self::load_and_initialise_questions_for_calculations.
     * @param qubaid_condition $qubaids the question usages whose responses to analyse.
     * @param string $whichtries which tries to analyse \question_attempt::FIRST_TRY, LAST_TRY or ALL_TRIES.
     * @param null|\core\progress\base $progress Used to indicate progress of task.
     * @param int[] $done array keys are ids of questions that have been analysed before calling method.
     * @return array array keys are ids of questions that were analysed after this method call.
     */
    protected function analyse_responses_for_questions($questions, $qubaids, $whichtries, $progress = null, $done = array()) {
        $countquestions = count($questions);
        if (!$countquestions) {
            return array();
        }
        if ($progress === null) {
            $progress = new \core\progress\none();
        }
        $progress->start_progress('', $countquestions, $countquestions);
        foreach ($questions as $question) {
            $progress->increment_progress();
            if (question_bank::get_qtype($question->qtype, false)->can_analyse_responses()  && !isset($done[$question->id])) {
                $responesstats = new \core_question\statistics\responses\analyser($question, $whichtries);
                if ($responesstats->get_last_analysed_time($qubaids, $whichtries) === false) {
                    $responesstats->calculate($qubaids, $whichtries);
                }
            }
            $done[$question->id] = 1;
        }
        $progress->end_progress();
        return $done;
    }

    /**
     * Return a little form for the user to request to download the full report, including clatest stats and response analysis for
     * all questions and sub-questions.
     *
     * @param moodle_url $reporturl the base URL of the report.
     * @return string HTML.
     */
    protected function everything_download_options(moodle_url $reporturl) {
        global $OUTPUT;
        return $OUTPUT->download_dataformat_selector(get_string('downloadeverything', 'clatest_statistics'),
            $reporturl->out_omit_querystring(), 'download', $reporturl->params() + array('everything' => 1));
    }

    /**
     * Return HTML for a message that says when the stats were last calculated and a 'recalculate now' button.
     *
     * @param int    $lastcachetime  the time the stats were last cached.
     * @param int    $clatestid         the clatest id.
     * @param array  $groupstudentsjoins (joins, wheres, params) for students in the group or empty array if groups not used.
     * @param string $whichattempts which attempts to use, represented internally as one of the constants as used in
     *                                   $clatest->grademethod ie.
     *                                   QUIZ_GRADEAVERAGE, QUIZ_GRADEHIGHEST, QUIZ_ATTEMPTLAST or QUIZ_ATTEMPTFIRST
     *                                   we calculate stats based on which attempts would affect the grade for each student.
     * @param moodle_url $reporturl url for this report
     * @return string HTML.
     */
    protected function output_caching_info($lastcachetime, $clatestid, $groupstudentsjoins, $whichattempts, $reporturl) {
        global $DB, $OUTPUT;

        if (empty($lastcachetime)) {
            return '';
        }

        // Find the number of attempts since the cached statistics were computed.
        list($fromqa, $whereqa, $qaparams) = clatest_statistics_attempts_sql($clatestid, $groupstudentsjoins, $whichattempts, true);
        $count = $DB->count_records_sql("
                SELECT COUNT(1)
                FROM $fromqa
                WHERE $whereqa
                AND clatesta.timefinish > {$lastcachetime}", $qaparams);

        if (!$count) {
            $count = 0;
        }

        // Generate the output.
        $a = new stdClass();
        $a->lastcalculated = format_time(time() - $lastcachetime);
        $a->count = $count;

        $recalcualteurl = new moodle_url($reporturl,
                array('recalculate' => 1, 'sesskey' => sesskey()));
        $output = '';
        $output .= $OUTPUT->box_start(
                'boxaligncenter generalbox boxwidthnormal mdl-align', 'cachingnotice');
        $output .= get_string('lastcalculated', 'clatest_statistics', $a);
        $output .= $OUTPUT->single_button($recalcualteurl,
                get_string('recalculatenow', 'clatest_statistics'));
        $output .= $OUTPUT->box_end(true);

        return $output;
    }

    /**
     * Clear the cached data for a particular report configuration. This will trigger a re-computation the next time the report
     * is displayed.
     *
     * @param $qubaids qubaid_condition
     */
    protected function clear_cached_data($qubaids) {
        global $DB;
        $DB->delete_records('clatest_statistics', array('hashcode' => $qubaids->get_hash_code()));
        $DB->delete_records('question_statistics', array('hashcode' => $qubaids->get_hash_code()));
        $DB->delete_records('question_response_analysis', array('hashcode' => $qubaids->get_hash_code()));
    }

    /**
     * Load the questions in this clatest and add some properties to the objects needed in the reports.
     *
     * @param object $clatest the clatest.
     * @return array of questions for this clatest.
     */
    public function load_and_initialise_questions_for_calculations($clatest) {
        // Load the questions.
        $questions = clatest_report_get_significant_questions($clatest);
        $questionids = array();
        foreach ($questions as $question) {
            $questionids[] = $question->id;
        }
        $fullquestions = question_load_questions($questionids);
        foreach ($questions as $qno => $question) {
            $q = $fullquestions[$question->id];
            $q->maxmark = $question->maxmark;
            $q->slot = $qno;
            $q->number = $question->number;
            $questions[$qno] = $q;
        }
        return $questions;
    }

    /**
     * Output all response analysis for all questions, sub-questions and variants. For download in a number of formats.
     *
     * @param $qubaids
     * @param $questions
     * @param $questionstats
     * @param $reporturl
     * @param $whichtries string
     */
    protected function output_all_question_response_analysis($qubaids,
                                                             $questions,
                                                             $questionstats,
                                                             $reporturl,
                                                             $whichtries = question_attempt::LAST_TRY) {
        foreach ($questions as $slot => $question) {
            if (question_bank::get_qtype(
                $question->qtype, false)->can_analyse_responses()
            ) {
                if ($questionstats->for_slot($slot)->get_variants()) {
                    foreach ($questionstats->for_slot($slot)->get_variants() as $variantno) {
                        $this->output_individual_question_response_analysis($question,
                                                                            $variantno,
                                                                            $questionstats->for_slot($slot, $variantno)->s,
                                                                            $reporturl,
                                                                            $qubaids,
                                                                            $whichtries);
                    }
                } else {
                    $this->output_individual_question_response_analysis($question,
                                                                        null,
                                                                        $questionstats->for_slot($slot)->s,
                                                                        $reporturl,
                                                                        $qubaids,
                                                                        $whichtries);
                }
            } else if ($subqids = $questionstats->for_slot($slot)->get_sub_question_ids()) {
                foreach ($subqids as $subqid) {
                    if ($variants = $questionstats->for_subq($subqid)->get_variants()) {
                        foreach ($variants as $variantno) {
                            $this->output_individual_question_response_analysis(
                                $questionstats->for_subq($subqid, $variantno)->question,
                                $variantno,
                                $questionstats->for_subq($subqid, $variantno)->s,
                                $reporturl,
                                $qubaids,
                                $whichtries);
                        }
                    } else {
                        $this->output_individual_question_response_analysis(
                            $questionstats->for_subq($subqid)->question,
                            null,
                            $questionstats->for_subq($subqid)->s,
                            $reporturl,
                            $qubaids,
                            $whichtries);

                    }
                }
            }
        }
    }
}
