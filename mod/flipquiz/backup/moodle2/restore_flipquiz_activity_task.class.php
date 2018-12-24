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
 * @package    mod_flipquiz
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/flipquiz/backup/moodle2/restore_flipquiz_stepslib.php');


/**
 * flipquiz restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 *
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_flipquiz_activity_task extends restore_activity_task {

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
        $this->add_step(new restore_flipquiz_activity_structure_step('flipquiz_structure', 'flipquiz.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('flipquiz', array('intro'), 'flipquiz');
        $contents[] = new restore_decode_content('flipquiz_feedback',
                array('feedbacktext'), 'flipquiz_feedback');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('FLIPQUIZVIEWBYID',
                '/mod/flipquiz/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('FLIPQUIZVIEWBYQ',
                '/mod/flipquiz/view.php?q=$1', 'flipquiz');
        $rules[] = new restore_decode_rule('FLIPQUIZINDEX',
                '/mod/flipquiz/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * flipquiz logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('flipquiz', 'add',
                'view.php?id={course_module}', '{flipquiz}');
        $rules[] = new restore_log_rule('flipquiz', 'update',
                'view.php?id={course_module}', '{flipquiz}');
        $rules[] = new restore_log_rule('flipquiz', 'view',
                'view.php?id={course_module}', '{flipquiz}');
        $rules[] = new restore_log_rule('flipquiz', 'preview',
                'view.php?id={course_module}', '{flipquiz}');
        $rules[] = new restore_log_rule('flipquiz', 'report',
                'report.php?id={course_module}', '{flipquiz}');
        $rules[] = new restore_log_rule('flipquiz', 'editquestions',
                'view.php?id={course_module}', '{flipquiz}');
        $rules[] = new restore_log_rule('flipquiz', 'delete attempt',
                'report.php?id={course_module}', '[oldattempt]');
        $rules[] = new restore_log_rule('flipquiz', 'edit override',
                'overrideedit.php?id={flipquiz_override}', '{flipquiz}');
        $rules[] = new restore_log_rule('flipquiz', 'delete override',
                'overrides.php.php?cmid={course_module}', '{flipquiz}');
        $rules[] = new restore_log_rule('flipquiz', 'addcategory',
                'view.php?id={course_module}', '{question_category}');
        $rules[] = new restore_log_rule('flipquiz', 'view summary',
                'summary.php?attempt={flipquiz_attempt}', '{flipquiz}');
        $rules[] = new restore_log_rule('flipquiz', 'manualgrade',
                'comment.php?attempt={flipquiz_attempt}&question={question}', '{flipquiz}');
        $rules[] = new restore_log_rule('flipquiz', 'manualgrading',
                'report.php?mode=grading&q={flipquiz}', '{flipquiz}');
        // All the ones calling to review.php have two rules to handle both old and new urls
        // in any case they are always converted to new urls on restore.
        // TODO: In Moodle 2.x (x >= 5) kill the old rules.
        // Note we are using the 'flipquiz_attempt' mapping because that is the
        // one containing the flipquiz_attempt->ids old an new for flipquiz-attempt.
        $rules[] = new restore_log_rule('flipquiz', 'attempt',
                'review.php?id={course_module}&attempt={flipquiz_attempt}', '{flipquiz}',
                null, null, 'review.php?attempt={flipquiz_attempt}');
        $rules[] = new restore_log_rule('flipquiz', 'attempt',
                'review.php?attempt={flipquiz_attempt}', '{flipquiz}',
                null, null, 'review.php?attempt={flipquiz_attempt}');
        // Old an new for flipquiz-submit.
        $rules[] = new restore_log_rule('flipquiz', 'submit',
                'review.php?id={course_module}&attempt={flipquiz_attempt}', '{flipquiz}',
                null, null, 'review.php?attempt={flipquiz_attempt}');
        $rules[] = new restore_log_rule('flipquiz', 'submit',
                'review.php?attempt={flipquiz_attempt}', '{flipquiz}');
        // Old an new for flipquiz-review.
        $rules[] = new restore_log_rule('flipquiz', 'review',
                'review.php?id={course_module}&attempt={flipquiz_attempt}', '{flipquiz}',
                null, null, 'review.php?attempt={flipquiz_attempt}');
        $rules[] = new restore_log_rule('flipquiz', 'review',
                'review.php?attempt={flipquiz_attempt}', '{flipquiz}');
        // Old an new for flipquiz-start attemp.
        $rules[] = new restore_log_rule('flipquiz', 'start attempt',
                'review.php?id={course_module}&attempt={flipquiz_attempt}', '{flipquiz}',
                null, null, 'review.php?attempt={flipquiz_attempt}');
        $rules[] = new restore_log_rule('flipquiz', 'start attempt',
                'review.php?attempt={flipquiz_attempt}', '{flipquiz}');
        // Old an new for flipquiz-close attemp.
        $rules[] = new restore_log_rule('flipquiz', 'close attempt',
                'review.php?id={course_module}&attempt={flipquiz_attempt}', '{flipquiz}',
                null, null, 'review.php?attempt={flipquiz_attempt}');
        $rules[] = new restore_log_rule('flipquiz', 'close attempt',
                'review.php?attempt={flipquiz_attempt}', '{flipquiz}');
        // Old an new for flipquiz-continue attempt.
        $rules[] = new restore_log_rule('flipquiz', 'continue attempt',
                'review.php?id={course_module}&attempt={flipquiz_attempt}', '{flipquiz}',
                null, null, 'review.php?attempt={flipquiz_attempt}');
        $rules[] = new restore_log_rule('flipquiz', 'continue attempt',
                'review.php?attempt={flipquiz_attempt}', '{flipquiz}');
        // Old an new for flipquiz-continue attemp.
        $rules[] = new restore_log_rule('flipquiz', 'continue attemp',
                'review.php?id={course_module}&attempt={flipquiz_attempt}', '{flipquiz}',
                null, 'continue attempt', 'review.php?attempt={flipquiz_attempt}');
        $rules[] = new restore_log_rule('flipquiz', 'continue attemp',
                'review.php?attempt={flipquiz_attempt}', '{flipquiz}',
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

        $rules[] = new restore_log_rule('flipquiz', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
