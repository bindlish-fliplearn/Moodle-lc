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
 * 
 */

// Because it exists (must)
require_once($CFG->dirroot . '/mod/wiziq/backup/moodle2/backup_wiziq_stepslib.php');
// Because it exists (optional)
require_once($CFG->dirroot . '/mod/wiziq/backup/moodle2/backup_wiziq_settingslib.php');

/**
 * class used to backup activity related to wiziq.
 * 
 * wiziq backup task that provides all the settings and steps
 * to perform one complete backup of the activity.
 * 
 * @copyright  www.wiziq.com 
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_wiziq_activity_task extends backup_activity_task {
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
        $this->add_step(new backup_wiziq_activity_structure_step('wiziq_structure', 'wiziq.xml'));
    }
    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     * 
     * @param string $content
     */
    static public function encode_content_links($content) {
         global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of wiziq
        $search="/(".$base."\/mod\/wiziq\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@WIZIQINDEX*$2@$', $content);

        // Link to wiziq view by moduleid
        $search="/(".$base."\/mod\/wiziq\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@WIZIQVIEWBYID*$2@$', $content);

        // Link to wiziq content by moduleid
        $search="/(".$base."\/mod\/wiziq\/content.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@WIZIQCONTENT*$2@$', $content);
        return $content;
    }
}