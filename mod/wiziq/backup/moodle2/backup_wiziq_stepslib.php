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
 * Backup activities required for backup of wiziq classes and content.
 * 
 * @package mod_wiziq
 * @copyright  www.wiziq.com 
 * @author     dinkar@wiziq.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_wiziq_activity_task
 * 
 * @copyright  www.wiziq.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_wiziq_activity_structure_step extends backup_activity_structure_step {
    /**
     * Function describes the structure of a backup file.
     * 
     * @return string 
     */
    protected function define_structure() {
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $wiziq = new backup_nested_element('wiziq', array('id'), array(
            'insescod', 'class_id', 'name', 'intro', 'introformat',
            'wiziq_datetime', 'class_timezone', 'timecreated', 'timemodified', 'duration',
            'vc_language', 'recording', 'presenter_id', 'lasteditorid',
            'class_status', 'recording_link', 'view_recording_link', 'recording_link_status'));
        // Build the tree
        $event = new backup_nested_element('event');
        $event = new backup_nested_element('event', array('id'), array(
            'name', 'description', 'format', 'courseid', 'groupid', 'userid',
            'repeatid', 'modulename', 'instance', 'eventtype', 'timestart',
            'timeduration', 'visible', 'uuid', 'sequence', 'timemodified'));

        $usercontents = new backup_nested_element('usercontents');

        $usercontent = new backup_nested_element('usercontent', array('id'),
                array('course', 'wiziqid', 'type', 'name',
                      'title', 'parentid', 'prevparentid', 'path',
                      'userid', 'uploadtime', 'contentid', 'old_content_id', 'cid_change_status',
                      'status', 'wcid'));

        $wiziq->add_child($event);
        $wiziq->add_child($usercontents);
        $usercontents->add_child($usercontent);
        // Define sources
        $wiziq->set_source_table('wiziq', array('id' => backup::VAR_ACTIVITYID));
        $event->set_source_sql('SELECT * FROM {event} WHERE modulename = "wiziq" AND instance = ?',
                               array(backup::VAR_ACTIVITYID));
        if ($userinfo) {
            $usercontent->set_source_sql('SELECT * FROM {wiziq_content} where course = ?',
                                         array('course' => backup::VAR_COURSEID));
        }
        // Define id annotations
        $usercontent->annotate_ids('user', 'userid');
        // Define file annotations
        $wiziq->annotate_files('mod_wiziq', 'intro', null);
        // Return the root element (wiziq), wrapped into standard activity structure
        return $this->prepare_activity_structure($wiziq);
    }
}