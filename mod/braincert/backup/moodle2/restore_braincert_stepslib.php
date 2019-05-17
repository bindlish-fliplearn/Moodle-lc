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
 * This page is the entry page into the online class
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * class restore_braincert_activity_structure_step
 * @copyright Dualcube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_braincert_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure for restoring braincert.
     */
    protected function define_structure() {
        $paths = array();
        $paths[] = new restore_path_element('braincert', '/activity/braincert');
        return $this->prepare_activity_structure($paths);
    }
    /**
     * Processing braincert classes.
     *
     * @param string $data
     */
    protected function process_braincert($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the braincert record.
        $newitemid = $DB->insert_record('braincert', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Processing events related to braincert.
     *
     * @param string $data
     */

    /**
     * Processing content related to braincert.
     *
     * @param string $data
     */

    /**
     * Executing activities.
     */
    protected function after_execute() {
        // Add braincert related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_braincert', 'intro', null);
    }
}
