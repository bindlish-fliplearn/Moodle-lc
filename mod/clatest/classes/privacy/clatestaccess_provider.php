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
 * The clatestaccess_provider interface provides the expected interface for all 'clatestaccess' clatestaccesss.
 *
 * @package    mod_clatest
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_clatest\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;

/**
 * The clatestaccess_provider interface provides the expected interface for all 'clatestaccess' clatestaccesss.
 *
 * @package    mod_clatest
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface clatestaccess_provider extends \core_privacy\local\request\plugin\subplugin_provider {

    /**
     * Export all user data for the specified user, for the specified clatest.
     *
     * @param   \clatest           $clatest The clatest being exported
     * @param   \stdClass       $user The user to export data for
     * @return  \stdClass       The data to be exported for this access rule.
     */
    public static function export_clatestaccess_user_data(\clatest $clatest, \stdClass $user) : \stdClass;

    /**
     * Delete all data for all users in the specified clatest.
     *
     * @param   \clatest           $clatest The clatest being deleted
     */
    public static function delete_clatestaccess_data_for_all_users_in_context(\clatest $clatest);

    /**
     * Delete all user data for the specified user, in the specified clatest.
     *
     * @param   \clatest           $clatest The clatest being deleted
     * @param   \stdClass       $user The user to export data for
     */
    public static function delete_clatestaccess_data_for_user(\clatest $clatest, \stdClass $user);
}
