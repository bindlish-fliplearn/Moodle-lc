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
 * @package    mod_homework
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/homework/backup/moodle2/restore_homework_stepslib.php');


/**
 * homework restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 *
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_homework_activity_task extends restore_activity_task {

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
        $this->add_step(new restore_homework_activity_structure_step('homework_structure', 'homework.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('homework', array('intro'), 'homework');
        $contents[] = new restore_decode_content('homework_feedback',
                array('feedbacktext'), 'homework_feedback');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('QUIZVIEWBYID',
                '/mod/homework/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('QUIZVIEWBYQ',
                '/mod/homework/view.php?q=$1', 'homework');
        $rules[] = new restore_decode_rule('QUIZINDEX',
                '/mod/homework/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * homework logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('homework', 'add',
                'view.php?id={course_module}', '{homework}');
        $rules[] = new restore_log_rule('homework', 'update',
                'view.php?id={course_module}', '{homework}');
        $rules[] = new restore_log_rule('homework', 'view',
                'view.php?id={course_module}', '{homework}');
        $rules[] = new restore_log_rule('homework', 'preview',
                'view.php?id={course_module}', '{homework}');
        $rules[] = new restore_log_rule('homework', 'report',
                'report.php?id={course_module}', '{homework}');
        $rules[] = new restore_log_rule('homework', 'editquestions',
                'view.php?id={course_module}', '{homework}');
        $rules[] = new restore_log_rule('homework', 'delete attempt',
                'report.php?id={course_module}', '[oldattempt]');
        $rules[] = new restore_log_rule('homework', 'edit override',
                'overrideedit.php?id={homework_override}', '{homework}');
        $rules[] = new restore_log_rule('homework', 'delete override',
                'overrides.php.php?cmid={course_module}', '{homework}');
        $rules[] = new restore_log_rule('homework', 'addcategory',
                'view.php?id={course_module}', '{question_category}');
        $rules[] = new restore_log_rule('homework', 'view summary',
                'summary.php?attempt={homework_attempt}', '{homework}');
        $rules[] = new restore_log_rule('homework', 'manualgrade',
                'comment.php?attempt={homework_attempt}&question={question}', '{homework}');
        $rules[] = new restore_log_rule('homework', 'manualgrading',
                'report.php?mode=grading&q={homework}', '{homework}');
        // All the ones calling to review.php have two rules to handle both old and new urls
        // in any case they are always converted to new urls on restore.
        // TODO: In Moodle 2.x (x >= 5) kill the old rules.
        // Note we are using the 'homework_attempt' mapping because that is the
        // one containing the homework_attempt->ids old an new for homework-attempt.
        $rules[] = new restore_log_rule('homework', 'attempt',
                'review.php?id={course_module}&attempt={homework_attempt}', '{homework}',
                null, null, 'review.php?attempt={homework_attempt}');
        $rules[] = new restore_log_rule('homework', 'attempt',
                'review.php?attempt={homework_attempt}', '{homework}',
                null, null, 'review.php?attempt={homework_attempt}');
        // Old an new for homework-submit.
        $rules[] = new restore_log_rule('homework', 'submit',
                'review.php?id={course_module}&attempt={homework_attempt}', '{homework}',
                null, null, 'review.php?attempt={homework_attempt}');
        $rules[] = new restore_log_rule('homework', 'submit',
                'review.php?attempt={homework_attempt}', '{homework}');
        // Old an new for homework-review.
        $rules[] = new restore_log_rule('homework', 'review',
                'review.php?id={course_module}&attempt={homework_attempt}', '{homework}',
                null, null, 'review.php?attempt={homework_attempt}');
        $rules[] = new restore_log_rule('homework', 'review',
                'review.php?attempt={homework_attempt}', '{homework}');
        // Old an new for homework-start attemp.
        $rules[] = new restore_log_rule('homework', 'start attempt',
                'review.php?id={course_module}&attempt={homework_attempt}', '{homework}',
                null, null, 'review.php?attempt={homework_attempt}');
        $rules[] = new restore_log_rule('homework', 'start attempt',
                'review.php?attempt={homework_attempt}', '{homework}');
        // Old an new for homework-close attemp.
        $rules[] = new restore_log_rule('homework', 'close attempt',
                'review.php?id={course_module}&attempt={homework_attempt}', '{homework}',
                null, null, 'review.php?attempt={homework_attempt}');
        $rules[] = new restore_log_rule('homework', 'close attempt',
                'review.php?attempt={homework_attempt}', '{homework}');
        // Old an new for homework-continue attempt.
        $rules[] = new restore_log_rule('homework', 'continue attempt',
                'review.php?id={course_module}&attempt={homework_attempt}', '{homework}',
                null, null, 'review.php?attempt={homework_attempt}');
        $rules[] = new restore_log_rule('homework', 'continue attempt',
                'review.php?attempt={homework_attempt}', '{homework}');
        // Old an new for homework-continue attemp.
        $rules[] = new restore_log_rule('homework', 'continue attemp',
                'review.php?id={course_module}&attempt={homework_attempt}', '{homework}',
                null, 'continue attempt', 'review.php?attempt={homework_attempt}');
        $rules[] = new restore_log_rule('homework', 'continue attemp',
                'review.php?attempt={homework_attempt}', '{homework}',
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

        $rules[] = new restore_log_rule('homework', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
