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
 * The mod_pratest group override updated event.
 *
 * @package    mod_pratest
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_pratest\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_pratest group override updated event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int pratestid: the id of the pratest.
 *      - int groupid: the id of the group.
 * }
 *
 * @package    mod_pratest
 * @since      Moodle 2.7
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_override_updated extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'pratest_overrides';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventoverrideupdated', 'mod_pratest');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' updated the override with id '$this->objectid' for the pratest with " .
            "course module id '$this->contextinstanceid' for the group with id '{$this->other['groupid']}'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/pratest/overrideedit.php', array('id' => $this->objectid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'pratest', 'edit override', 'overrideedit.php?id=' . $this->objectid, $this->other['pratestid'],
            $this->contextinstanceid);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['pratestid'])) {
            throw new \coding_exception('The \'pratestid\' value must be set in other.');
        }

        if (!isset($this->other['groupid'])) {
            throw new \coding_exception('The \'groupid\' value must be set in other.');
        }
    }

    public static function get_objectid_mapping() {
        return array('db' => 'pratest_overrides', 'restore' => 'pratest_override');
    }

    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['pratestid'] = array('db' => 'pratest', 'restore' => 'pratest');
        $othermapped['groupid'] = array('db' => 'groups', 'restore' => 'group');

        return $othermapped;
    }
}
