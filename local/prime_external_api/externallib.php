<?php

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
 * External Web Service Template
 *
 * @package    localprime_external_api
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_prime_external_api_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function hello_world_parameters() {
        return new external_function_parameters(
                array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '),'id' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '))
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function hello_world($welcomemessage = 'Hello world, ') {
        global $USER;
        print_r($_POST);
        print_r($_GET);die;
        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::hello_world_parameters(),
                array('welcomemessage' => $welcomemessage));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }
        global $DB, $CFG;
         $result = $DB->get_records('user');
                return $result;

           
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function hello_world_returns() {
        return new external_multiple_structure(new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'group record id'),
                    'username' => new external_value(PARAM_TEXT, 'group record username'),
                    'firstname' => new external_value(PARAM_TEXT, 'group record firstname'),
                    'lastname' => new external_value(PARAM_TEXT, 'group record lastname'),
                    'email' => new external_value(PARAM_TEXT, 'group record email'),
                    'description' => new external_value(PARAM_TEXT, 'group record description')
            )));
    }
    public static function get_all_user_parameters() {
        return new external_function_parameters(
                array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '))
        );
    }
    public static function get_all_user(){
         global $DB, $CFG;
        $result = $DB->get_records('user');
        return $result;
    }
    public static function get_all_user_returns() {
        return new external_multiple_structure(new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'group record id'),
                    'username' => new external_value(PARAM_TEXT, 'group record username'),
                    'firstname' => new external_value(PARAM_TEXT, 'group record firstname'),
                    'lastname' => new external_value(PARAM_TEXT, 'group record lastname'),
                    'email' => new external_value(PARAM_TEXT, 'group record email'),
                    'description' => new external_value(PARAM_TEXT, 'group record description')
            )));
    }



}
