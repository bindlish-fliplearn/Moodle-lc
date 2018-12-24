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
 * The flipquizaccess_provider interface provides the expected interface for all 'flipquizaccess' flipquizaccesss.
 *
 * @package    mod_flipquiz
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_flipquiz\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;

/**
 * The flipquizaccess_provider interface provides the expected interface for all 'flipquizaccess' flipquizaccesss.
 *
 * @package    mod_flipquiz
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface flipquizaccess_provider extends \core_privacy\local\request\plugin\subplugin_provider {

    /**
     * Export all user data for the specified user, for the specified flipquiz.
     *
     * @param   \flipquiz           $flipquiz The flipquiz being exported
     * @param   \stdClass       $user The user to export data for
     * @return  \stdClass       The data to be exported for this access rule.
     */
    public static function export_flipquizaccess_user_data(\flipquiz $flipquiz, \stdClass $user) : \stdClass;

    /**
     * Delete all data for all users in the specified flipquiz.
     *
     * @param   \flipquiz           $flipquiz The flipquiz being deleted
     */
    public static function delete_flipquizaccess_data_for_all_users_in_context(\flipquiz $flipquiz);

    /**
     * Delete all user data for the specified user, in the specified flipquiz.
     *
     * @param   \flipquiz           $flipquiz The flipquiz being deleted
     * @param   \stdClass       $user The user to export data for
     */
    public static function delete_flipquizaccess_data_for_user(\flipquiz $flipquiz, \stdClass $user);
}
