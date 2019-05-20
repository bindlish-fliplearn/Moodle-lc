<?php
// This file is part of Wiziq - http://www.wiziq.com/
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
 * Definition of log events
 *
 * NOTE: this is an example how to insert log event during installation/update.
 * It is not really essential to know about it, but these logs were created as example
 * in the previous 1.9 wiziq.
 *
 * @package    mod_wiziq
 * @copyright  www.wiziq.com 
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
// Because it exists (must)
require_once($CFG->dirroot . '/mod/wiziq/backup/moodle2/restore_wiziq_stepslib.php');

/**
 * This provides all the settings and steps to perform one complete restore of the activity.
 * 
 * @copyright  www.wiziq.com 
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_wiziq_activity_task extends restore_activity_task {
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }
    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // wiziq only has one structure step
        $this->add_step(new restore_wiziq_activity_structure_step('wiziq_structure', 'wiziq.xml'));
    }
    /**
     * Define (add) contents for this activity.
     * 
     * @return stirng $contents.
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('wiziq', array('intro'), 'wiziq');

        return $contents;
    }
    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('WIZIQVIEWBYID', '/mod/wiziq/view.php?id=$1',
                                           'course_module');
        $rules[] = new restore_decode_rule('WIZIQINDEX', '/mod/wiziq/index.php?id=$1',
                                           'course');
        $rules[] = new restore_decode_rule('WIZIQCONTENT', '/mod/wiziq/content.php?id=$1',
                                           'course');

        return $rules;
    }
    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * wiziq logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();
        $rules[] = new restore_log_rule('wiziq', 'add', 'view.php?id={course_module}', '{wiziq}');
        $rules[] = new restore_log_rule('wiziq', 'update',
                                        'view.php?id={course_module}', '{wiziq}');
        $rules[] = new restore_log_rule('wiziq', 'view', 'view.php?id={course_module}', '{wiziq}');
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
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        // Fix old wrong uses (missing extension)
        $rules[] = new restore_log_rule('wiziq', 'view all', 'index?id={course}', null,
                                        null, null, 'index.php?id={course}');
        $rules[] = new restore_log_rule('wiziq', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}