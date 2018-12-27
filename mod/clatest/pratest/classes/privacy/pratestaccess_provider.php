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
 * The pratestaccess_provider interface provides the expected interface for all 'pratestaccess' pratestaccesss.
 *
 * @package    mod_pratest
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_pratest\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;

/**
 * The pratestaccess_provider interface provides the expected interface for all 'pratestaccess' pratestaccesss.
 *
 * @package    mod_pratest
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface pratestaccess_provider extends \core_privacy\local\request\plugin\subplugin_provider {

    /**
     * Export all user data for the specified user, for the specified pratest.
     *
     * @param   \pratest           $pratest The pratest being exported
     * @param   \stdClass       $user The user to export data for
     * @return  \stdClass       The data to be exported for this access rule.
     */
    public static function export_pratestaccess_user_data(\pratest $pratest, \stdClass $user) : \stdClass;

    /**
     * Delete all data for all users in the specified pratest.
     *
     * @param   \pratest           $pratest The pratest being deleted
     */
    public static function delete_pratestaccess_data_for_all_users_in_context(\pratest $pratest);

    /**
     * Delete all user data for the specified user, in the specified pratest.
     *
     * @param   \pratest           $pratest The pratest being deleted
     * @param   \stdClass       $user The user to export data for
     */
    public static function delete_pratestaccess_data_for_user(\pratest $pratest, \stdClass $user);
}
