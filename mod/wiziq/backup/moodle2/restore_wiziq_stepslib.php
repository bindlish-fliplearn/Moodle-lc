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

/**
 * Define all the restore steps.
 * 
 * @copyright  www.wiziq.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_wiziq_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure for restoring wiziq.
     */
    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('wiziq', '/activity/wiziq');
        $paths[] = new restore_path_element('event', '/activity/wiziq/event');
        if ($userinfo) {
            $paths[] = new restore_path_element('content',
                                                '/activity/wiziq/usercontents/usercontent');
        }
        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }
    /**
     * Processing wiziq classes.
     *
     * @param string $data  
     */
    protected function process_wiziq($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the wiziq record
        $newitemid = $DB->insert_record('wiziq', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }
    /**
     * Processing events related to wiziq.
     *
     * @param string $data  
     */
    protected function process_event($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->instance = $this->get_new_parentid('wiziq');

        $newitemid = $DB->insert_record('event', $data);
        $this->set_mapping('event', $oldid, $newitemid);
    }
    /**
     * Processing content related to wiziq.
     *
     * @param string $data  
     */
    protected function process_content($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->uploadtime = $this->apply_date_offset($data->uploadtime);

        // insert the wiziq_content record
        $recordexist = $DB->record_exists('wiziq_content', array('course' => $this->get_courseid(),
                                                                 'wcid' => $data->wcid));
        if (!$recordexist) {
            $newitemid = $DB->insert_record('wiziq_content', $data);
        }
    }
    /**
     * Executing activities.
     */
    protected function after_execute() {
        // Add wiziq related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_wiziq', 'intro', null);
    }
}