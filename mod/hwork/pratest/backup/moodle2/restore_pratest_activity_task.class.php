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
 * @package    mod_pratest
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pratest/backup/moodle2/restore_pratest_stepslib.php');


/**
 * pratest restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 *
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_pratest_activity_task extends restore_activity_task {

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
        $this->add_step(new restore_pratest_activity_structure_step('pratest_structure', 'pratest.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('pratest', array('intro'), 'pratest');
        $contents[] = new restore_decode_content('pratest_feedback',
                array('feedbacktext'), 'pratest_feedback');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('QUIZVIEWBYID',
                '/mod/pratest/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('QUIZVIEWBYQ',
                '/mod/pratest/view.php?q=$1', 'pratest');
        $rules[] = new restore_decode_rule('QUIZINDEX',
                '/mod/pratest/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * pratest logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('pratest', 'add',
                'view.php?id={course_module}', '{pratest}');
        $rules[] = new restore_log_rule('pratest', 'update',
                'view.php?id={course_module}', '{pratest}');
        $rules[] = new restore_log_rule('pratest', 'view',
                'view.php?id={course_module}', '{pratest}');
        $rules[] = new restore_log_rule('pratest', 'preview',
                'view.php?id={course_module}', '{pratest}');
        $rules[] = new restore_log_rule('pratest', 'report',
                'report.php?id={course_module}', '{pratest}');
        $rules[] = new restore_log_rule('pratest', 'editquestions',
                'view.php?id={course_module}', '{pratest}');
        $rules[] = new restore_log_rule('pratest', 'delete attempt',
                'report.php?id={course_module}', '[oldattempt]');
        $rules[] = new restore_log_rule('pratest', 'edit override',
                'overrideedit.php?id={pratest_override}', '{pratest}');
        $rules[] = new restore_log_rule('pratest', 'delete override',
                'overrides.php.php?cmid={course_module}', '{pratest}');
        $rules[] = new restore_log_rule('pratest', 'addcategory',
                'view.php?id={course_module}', '{question_category}');
        $rules[] = new restore_log_rule('pratest', 'view summary',
                'summary.php?attempt={pratest_attempt}', '{pratest}');
        $rules[] = new restore_log_rule('pratest', 'manualgrade',
                'comment.php?attempt={pratest_attempt}&question={question}', '{pratest}');
        $rules[] = new restore_log_rule('pratest', 'manualgrading',
                'report.php?mode=grading&q={pratest}', '{pratest}');
        // All the ones calling to review.php have two rules to handle both old and new urls
        // in any case they are always converted to new urls on restore.
        // TODO: In Moodle 2.x (x >= 5) kill the old rules.
        // Note we are using the 'pratest_attempt' mapping because that is the
        // one containing the pratest_attempt->ids old an new for pratest-attempt.
        $rules[] = new restore_log_rule('pratest', 'attempt',
                'review.php?id={course_module}&attempt={pratest_attempt}', '{pratest}',
                null, null, 'review.php?attempt={pratest_attempt}');
        $rules[] = new restore_log_rule('pratest', 'attempt',
                'review.php?attempt={pratest_attempt}', '{pratest}',
                null, null, 'review.php?attempt={pratest_attempt}');
        // Old an new for pratest-submit.
        $rules[] = new restore_log_rule('pratest', 'submit',
                'review.php?id={course_module}&attempt={pratest_attempt}', '{pratest}',
                null, null, 'review.php?attempt={pratest_attempt}');
        $rules[] = new restore_log_rule('pratest', 'submit',
                'review.php?attempt={pratest_attempt}', '{pratest}');
        // Old an new for pratest-review.
        $rules[] = new restore_log_rule('pratest', 'review',
                'review.php?id={course_module}&attempt={pratest_attempt}', '{pratest}',
                null, null, 'review.php?attempt={pratest_attempt}');
        $rules[] = new restore_log_rule('pratest', 'review',
                'review.php?attempt={pratest_attempt}', '{pratest}');
        // Old an new for pratest-start attemp.
        $rules[] = new restore_log_rule('pratest', 'start attempt',
                'review.php?id={course_module}&attempt={pratest_attempt}', '{pratest}',
                null, null, 'review.php?attempt={pratest_attempt}');
        $rules[] = new restore_log_rule('pratest', 'start attempt',
                'review.php?attempt={pratest_attempt}', '{pratest}');
        // Old an new for pratest-close attemp.
        $rules[] = new restore_log_rule('pratest', 'close attempt',
                'review.php?id={course_module}&attempt={pratest_attempt}', '{pratest}',
                null, null, 'review.php?attempt={pratest_attempt}');
        $rules[] = new restore_log_rule('pratest', 'close attempt',
                'review.php?attempt={pratest_attempt}', '{pratest}');
        // Old an new for pratest-continue attempt.
        $rules[] = new restore_log_rule('pratest', 'continue attempt',
                'review.php?id={course_module}&attempt={pratest_attempt}', '{pratest}',
                null, null, 'review.php?attempt={pratest_attempt}');
        $rules[] = new restore_log_rule('pratest', 'continue attempt',
                'review.php?attempt={pratest_attempt}', '{pratest}');
        // Old an new for pratest-continue attemp.
        $rules[] = new restore_log_rule('pratest', 'continue attemp',
                'review.php?id={course_module}&attempt={pratest_attempt}', '{pratest}',
                null, 'continue attempt', 'review.php?attempt={pratest_attempt}');
        $rules[] = new restore_log_rule('pratest', 'continue attemp',
                'review.php?attempt={pratest_attempt}', '{pratest}',
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

        $rules[] = new restore_log_rule('pratest', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
