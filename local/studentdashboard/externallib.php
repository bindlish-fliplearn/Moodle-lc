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
   * @package    localstudentapi
   * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
   * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
   */
  require_once($CFG->libdir . "/externallib.php");

  class local_studentapi_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_student_user_parameters() {
      return new external_function_parameters(
        array('uuid' => new external_value(PARAM_TEXT, 'This is studentlearn uuid.'))
      );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function get_student_user($paramuuid) {
      global $DB;
      $resp = array();
      //REQUIRED
      $params = self::validate_parameters(self::get_student_user_parameters(), array('uuid' => $paramuuid));
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
    public static function get_student_user_returns() {
      return new external_single_structure(
        array(
        'uuid' => new external_multiple_structure(
          new external_value(PARAM_TEXT, 'new category name')
        )
        )
      );
    }

    public static function student_user_mapping_parameters() {
         return  new external_function_parameters(
                      array(
                      'userDetails'=> new external_multiple_structure(
                          new external_single_structure(
                            array(
                                'user_id' => new external_value(PARAM_TEXT, 'id of user'),
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
                        ))
                      )
                      );
    }
    public static function student_user_mapping($parms){
        global $DB;
        $resp = array();
         $params = self::validate_parameters(self::student_user_mapping_parameters(), array('userDetails'=>$parms));
        $userDetails = $params['userDetails'][0];

        $userObj = new stdClass();
        $userObj->user_id = $userDetails['user_id'];
        $userObj->uuid  = $userDetails['uuid'];
        $userObj->login_id  = $userDetails['login_id'];
        $userObj->firstname = $userDetails['firstname'];
        $userObj->lastname = $userDetails['lastname'];
        $userObj->email = $userDetails['email'];
        $userObj->role = $userDetails['role'];
        $userObj->is_enrolled = $userDetails['is_enrolled']; 
        $userObj->ayid = $userDetails['ayid'];
        $userObj->school_code = $userDetails['school_code'];
        $id =  $DB->insert_record('guru_user_mapping',$userObj , $returnid=true, $bulk=false) ;
        $resp[] = $id;
        return ['user_mapping_id' => $resp];
    }

      /**
     * Returns description of method result value
     * @return external_description
     */
    public static function student_user_mapping_returns() {
      return new external_single_structure(
        array(
        'user_mapping_id' => new external_multiple_structure(
          new external_value(PARAM_TEXT, 'new category name')
        )
        )
      );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_user_by_uuid_parameters() {
      return new external_function_parameters(
        array('uuid' => new external_value(PARAM_TEXT, 'This is studentlearn uuid.'))
      );
    }

    /**
    * get user details by uuid 
    */
      /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function get_user_by_uuid($paramuuid) {
      global $DB;
      $resp = array();
      //REQUIRED
      $params = self::validate_parameters(self::get_user_by_uuid_parameters(), array('uuid' => $paramuuid));
        $sql = "SELECT * FROM {guru_user_mapping} WHERE uuid = ?";
          $record = $DB->get_record_sql($sql, array($paramuuid));
          if($record){
              $responseArray = ((array)$record);
              return  ['result' => $responseArray,'status'=>'true'];
               // return (array)$record; 
          }else{
                return ['result' => array("id"=>'',"user_id"=>"","uuid"=>"","login_id"=>"","firstname"=>"","lastname"=>"","email"=>"","role"=>"","is_enrolled"=>"","school_code"=>"","ayid"=>""), "status"=>"false"]; 
          }
    }

   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_user_by_uuid_returns() {

       return  new external_single_structure(
                array(
                  "result"=>new external_single_structure(
                      array(
                          'id' => new external_value(PARAM_TEXT, 'id of user'),
                          'user_id' => new external_value(PARAM_TEXT, 'id of user'),
                          'uuid' => new external_value(PARAM_TEXT, 'user uuid '),
                          'login_id' => new external_value(PARAM_TEXT, 'user login id'),
                          'firstname' => new external_value(PARAM_TEXT, 'user first name'),
                          'lastname' => new external_value(PARAM_TEXT, 'user last name'),
                          'email' => new external_value(PARAM_TEXT, 'user email'),
                          'role' => new external_value(PARAM_TEXT, 'user role'),
                          'is_enrolled' => new external_value(PARAM_TEXT, 'user enrolled'),
                          'school_code' => new external_value(PARAM_TEXT, 'user school code'),
                          'ayid' => new external_value(PARAM_TEXT, 'user ayid'),
                          )
                  ),
                  'status'=>new external_value(PARAM_TEXT, 'id of user'),
                )
            );
        }

        /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_role_details_by_shortname_parameters() {
      return new external_function_parameters(
        array('shortname' => new external_value(PARAM_TEXT, 'This is studentlearn role   shortname.'))
      );
    }

      /**
    * get user details by uuid 
    */
      /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function get_role_details_by_shortname($shortname) {
      global $DB;
      $resp = array();
      //REQUIRED
      $params = self::validate_parameters(self::get_role_details_by_shortname_parameters(), array('shortname' => $shortname));
        $sql = "SELECT id,name,shortname,description FROM {role} WHERE shortname = ?";
          $record = $DB->get_record_sql($sql, array($shortname));
          if($record){
              $responseArray = ((array)$record);
              return  ['result' => $responseArray,'status'=>'true'];
               // return (array)$record; 
          }else{
                return ['result' => array("id"=>'',"name"=>"","shortname"=>"","description"=>""),'status'=>'false']; 
          }
    }
   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_role_details_by_shortname_returns() {
       return  new external_single_structure(
                array(
                  "result"=>new external_single_structure(
                      array(
                          'id' => new external_value(PARAM_TEXT, 'id of role'),
                          'name' => new external_value(PARAM_TEXT, 'role name'),
                          'shortname' => new external_value(PARAM_TEXT, 'short name  '),
                          'description' => new external_value(PARAM_TEXT, 'role description')
                          )
                  ),
                  'status'=>new external_value(PARAM_TEXT, 'status')
                )
            );
        }
  }

