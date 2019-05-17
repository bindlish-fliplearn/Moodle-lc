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

// This file is part of backup.
require_once($CFG->dirroot . '/mod/braincert/backup/moodle2/backup_braincert_stepslib.php');
require_once($CFG->dirroot . '/mod/braincert/backup/moodle2/backup_braincert_settingslib.php');


/**
 * class backup_braincert_activity_task
 * @copyright Dualcube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_braincert_activity_task extends backup_activity_task {
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
        $this->add_step(new backup_braincert_activity_structure_step('braincert_structure', 'braincert.xml'));
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

        // Link to the list of braincert.
        $search = "/(".$base."\/mod\/braincert\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@BRAINCERTINDEX*$2@$', $content);

        // Link to braincert view by moduleid.
        $search = "/(".$base."\/mod\/braincert\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@BRAINCERTVIEWBYID*$2@$', $content);

        // Link to braincert content by moduleid.
        $search = "/(".$base."\/mod\/braincert\/content.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@BRAINCERTCONTENT*$2@$', $content);
        return $content;
    }
}
