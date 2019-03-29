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
 * @package    mod_hwork
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/hwork/backup/moodle2/restore_hwork_stepslib.php');


/**
 * hwork restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 *
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_hwork_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Quiz only has one structure step.
        $this->add_step(new restore_hwork_activity_structure_step('hwork_structure', 'hwork.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('hwork', array('intro'), 'hwork');
        $contents[] = new restore_decode_content('hwork_feedback',
                array('feedbacktext'), 'hwork_feedback');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('QUIZVIEWBYID',
                '/mod/hwork/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('QUIZVIEWBYQ',
                '/mod/hwork/view.php?q=$1', 'hwork');
        $rules[] = new restore_decode_rule('QUIZINDEX',
                '/mod/hwork/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * hwork logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('hwork', 'add',
                'view.php?id={course_module}', '{hwork}');
        $rules[] = new restore_log_rule('hwork', 'update',
                'view.php?id={course_module}', '{hwork}');
        $rules[] = new restore_log_rule('hwork', 'view',
                'view.php?id={course_module}', '{hwork}');
        $rules[] = new restore_log_rule('hwork', 'preview',
                'view.php?id={course_module}', '{hwork}');
        $rules[] = new restore_log_rule('hwork', 'report',
                'report.php?id={course_module}', '{hwork}');
        $rules[] = new restore_log_rule('hwork', 'editquestions',
                'view.php?id={course_module}', '{hwork}');
        $rules[] = new restore_log_rule('hwork', 'delete attempt',
                'report.php?id={course_module}', '[oldattempt]');
        $rules[] = new restore_log_rule('hwork', 'edit override',
                'overrideedit.php?id={hwork_override}', '{hwork}');
        $rules[] = new restore_log_rule('hwork', 'delete override',
                'overrides.php.php?cmid={course_module}', '{hwork}');
        $rules[] = new restore_log_rule('hwork', 'addcategory',
                'view.php?id={course_module}', '{question_category}');
        $rules[] = new restore_log_rule('hwork', 'view summary',
                'summary.php?attempt={hwork_attempt}', '{hwork}');
        $rules[] = new restore_log_rule('hwork', 'manualgrade',
                'comment.php?attempt={hwork_attempt}&question={question}', '{hwork}');
        $rules[] = new restore_log_rule('hwork', 'manualgrading',
                'report.php?mode=grading&q={hwork}', '{hwork}');
        // All the ones calling to review.php have two rules to handle both old and new urls
        // in any case they are always converted to new urls on restore.
        // TODO: In Moodle 2.x (x >= 5) kill the old rules.
        // Note we are using the 'hwork_attempt' mapping because that is the
        // one containing the hwork_attempt->ids old an new for hwork-attempt.
        $rules[] = new restore_log_rule('hwork', 'attempt',
                'review.php?id={course_module}&attempt={hwork_attempt}', '{hwork}',
                null, null, 'review.php?attempt={hwork_attempt}');
        $rules[] = new restore_log_rule('hwork', 'attempt',
                'review.php?attempt={hwork_attempt}', '{hwork}',
                null, null, 'review.php?attempt={hwork_attempt}');
        // Old an new for hwork-submit.
        $rules[] = new restore_log_rule('hwork', 'submit',
                'review.php?id={course_module}&attempt={hwork_attempt}', '{hwork}',
                null, null, 'review.php?attempt={hwork_attempt}');
        $rules[] = new restore_log_rule('hwork', 'submit',
                'review.php?attempt={hwork_attempt}', '{hwork}');
        // Old an new for hwork-review.
        $rules[] = new restore_log_rule('hwork', 'review',
                'review.php?id={course_module}&attempt={hwork_attempt}', '{hwork}',
                null, null, 'review.php?attempt={hwork_attempt}');
        $rules[] = new restore_log_rule('hwork', 'review',
                'review.php?attempt={hwork_attempt}', '{hwork}');
        // Old an new for hwork-start attemp.
        $rules[] = new restore_log_rule('hwork', 'start attempt',
                'review.php?id={course_module}&attempt={hwork_attempt}', '{hwork}',
                null, null, 'review.php?attempt={hwork_attempt}');
        $rules[] = new restore_log_rule('hwork', 'start attempt',
                'review.php?attempt={hwork_attempt}', '{hwork}');
        // Old an new for hwork-close attemp.
        $rules[] = new restore_log_rule('hwork', 'close attempt',
                'review.php?id={course_module}&attempt={hwork_attempt}', '{hwork}',
                null, null, 'review.php?attempt={hwork_attempt}');
        $rules[] = new restore_log_rule('hwork', 'close attempt',
                'review.php?attempt={hwork_attempt}', '{hwork}');
        // Old an new for hwork-continue attempt.
        $rules[] = new restore_log_rule('hwork', 'continue attempt',
                'review.php?id={course_module}&attempt={hwork_attempt}', '{hwork}',
                null, null, 'review.php?attempt={hwork_attempt}');
        $rules[] = new restore_log_rule('hwork', 'continue attempt',
                'review.php?attempt={hwork_attempt}', '{hwork}');
        // Old an new for hwork-continue attemp.
        $rules[] = new restore_log_rule('hwork', 'continue attemp',
                'review.php?id={course_module}&attempt={hwork_attempt}', '{hwork}',
                null, 'continue attempt', 'review.php?attempt={hwork_attempt}');
        $rules[] = new restore_log_rule('hwork', 'continue attemp',
                'review.php?attempt={hwork_attempt}', '{hwork}',
                null, 'continue attempt');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('hwork', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
