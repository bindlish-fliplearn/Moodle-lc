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
 * @package    localflipapi
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_flipapi_external extends external_api {

  /**
   * Returns description of method parameters
   * @return external_function_parameters
   */
  public static function get_flip_user_parameters() {
    return new external_function_parameters(
      array('uuid' => new external_value(PARAM_TEXT, 'This is fliplearn uuid.'))
    );
  }

  /**
   * Returns welcome message
   * @return string welcome message
   */
  public static function get_flip_user($paramuuid) {
    global $DB;
    $resp = array();
    //REQUIRED
    $params = self::validate_parameters(self::get_flip_user_parameters(), array('uuid' => $paramuuid));
    $uuids = explode("|", $params['uuid']);
    foreach ($uuids as $uuid) {
      if (!empty($uuid)) {
        $sql = "SELECT * FROM {guru_user_mapping} WHERE uuid = ?";
        $record = $DB->get_record_sql($sql, array($uuid));
        if (!empty($record)) {
          $resp[] = $uuid;
        }
      }
    }
    return ['uuid' => $resp];
  }

  /**
   * Returns description of method result value
   * @return external_description
   */
  public static function get_flip_user_returns() {
    return new external_single_structure(
      array(
      'uuid' => new external_multiple_structure(
        new external_value(PARAM_TEXT, 'new category name')
      )
      )
    );
  }

    public static function create_user_parameters() {
        return new external_function_parameters(
            array(
                'user' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'user_id' => new external_value(PARAM_INT, 'id of user'),
                            'uuid' => new external_value(PARAM_INT, 'user uuid '),
                            'login_id' => new external_value(PARAM_TEXT, 'user login id'),
                            'firstname' => new external_value(PARAM_TEXT, 'user first name'),
                            'lastname' => new external_value(PARAM_TEXT, 'user last name'),
                            'email' => new external_value(PARAM_TEXT, 'user email'),
                            'role' => new external_value(PARAM_TEXT, 'user role'),
                            'is_enrolled' => new external_value(PARAM_TEXT, 'user enrolled'),
                             'school_code' => new external_value(PARAM_TEXT, 'user school code'),
                              'ayid' => new external_value(PARAM_TEXT, 'user ayid'),
                        )
                    )
                )
            )
        );
  }
  public static function flip_user_mapping($parms){
      global $DB;
      $resp = array();
  }

}
