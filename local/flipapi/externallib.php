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
  use \local_flipapi\api as local_api;
  use \core_calendar\external\events_exporter;
  use \core_calendar\external\events_related_objects_cache;


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

    public static function flip_user_mapping_parameters() {
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
    public static function flip_user_mapping($parms){
        global $DB;
        $resp = array();
         $params = self::validate_parameters(self::flip_user_mapping_parameters(), array('userDetails'=>$parms));
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
    public static function flip_user_mapping_returns() {
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
        array('uuid' => new external_value(PARAM_TEXT, 'This is fliplearn uuid.'))
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
        array('shortname' => new external_value(PARAM_TEXT, 'This is fliplearn role   shortname.'))
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

    /**
     * Returns description of method parameters.
     *
     * @since Moodle 3.3
     * @return external_function_parameters
     */
    public static function get_calendar_action_completed_events_by_timesort_parameters() {
        return new external_function_parameters(
            array(
                'timesortfrom' => new external_value(PARAM_INT, 'Time sort from', VALUE_DEFAULT, 0),
                'timesortto' => new external_value(PARAM_INT, 'Time sort to', VALUE_DEFAULT, null),
                'aftereventid' => new external_value(PARAM_INT, 'The last seen event id', VALUE_DEFAULT, 0),
                'limitnum' => new external_value(PARAM_INT, 'Limit number', VALUE_DEFAULT, 20)
            )
        );
    }


         /**
     * Get calendar action events based on the timesort value.
     *
     * @since Moodle 3.3
     * @param null|int $timesortfrom Events after this time (inclusive)
     * @param null|int $timesortto Events before this time (inclusive)
     * @param null|int $aftereventid Get events with ids greater than this one
     * @param int $limitnum Limit the number of results to this value
     * @return array
     */
    public static function get_calendar_action_completed_events_by_timesort($timesortfrom = 0, $timesortto = null,
                                                       $aftereventid = 0, $limitnum = 20) {
        global $CFG, $PAGE, $USER;
        require_once($CFG->dirroot . '/calendar/lib.php');
        $user = null;
        $params = self::validate_parameters(
            self::get_calendar_action_completed_events_by_timesort_parameters(),
            [
                'timesortfrom' => $timesortfrom,
                'timesortto' => $timesortto,
                'aftereventid' => $aftereventid,
                'limitnum' => $limitnum,
            ]
        );
        $context = \context_user::instance($USER->id);
        self::validate_context($context);

        if (empty($params['aftereventid'])) {
            $params['aftereventid'] = null;
        }

        $renderer = $PAGE->get_renderer('core_calendar');
        $events = local_api::get_action_events_by_timesort(
            $params['timesortfrom'],
            $params['timesortto'],
            $params['aftereventid'],
            $params['limitnum']
        );

        $exportercache = new events_related_objects_cache($events);
        $exporter = new events_exporter($events, ['cache' => $exportercache]);
        $returnArray = [];
        $data = $exporter->export($renderer);
          foreach ($events as $key => $value) {
              $data->events[$key]->completionstate = $value->completionstate;
              $data->events[$key]->completionexpected = $value->completionexpected;
              $data->events[$key]->timemodified = date('M j G:i', (int) $value->timemodified);

          }
          $array = json_decode(json_encode($data), true);
          return $array;
    }

      /**
     * Returns description of method result value.
     *
     * @since Moodle 3.3
     * @return external_description
     */
    public static function get_calendar_action_completed_events_by_timesort_returns() {
    }
   
        /**
   * Returns description of method parameters
   * @return external_function_parameters
   */
  public static function update_completionexpected_by_id_parameters() {
    return new external_function_parameters(
        array(
          'courseId' => new external_value(PARAM_TEXT, 'This is homework course id.'),
          'assignDate' => new external_value(PARAM_TEXT, 'This is homework assign date.'),
          'uuid' => new external_value(PARAM_TEXT, 'This is homework assign date.'),
          'activityId' => new external_multiple_structure(new external_single_structure(array(
            'instanceId' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
            'module' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
            'name' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
          )))
          )
      );
  }

  /**
   * get user details by uuid 
   */

  /**
   * Returns welcome message
   * @return string welcome message
   */
  public static function update_completionexpected_by_id($courseId, $assignDate, $uuid, $activityId) {
    global $DB;
    //REQUIRED
    self::validate_parameters(
            self::update_completionexpected_by_id_parameters(),
            array(
                'courseId' => $courseId,
                'assignDate' => $assignDate,
                'activityId' => $activityId
            )
        );
    $date = strtotime($assignDate);
    $updateRecord = false;
    if(!empty($activityId)) {
      foreach($activityId as $activity) {
        $courseModuleRaw = $DB->get_record  ('course_modules', array('id' => $activity['instanceId']));
        $instanceId = $courseModuleRaw->instance;
          $name = addslashes($activity['name']);
          $userEvent = "INSERT INTO {event} SET name='{$name}', description='<div class=no-overflow><p>{$name}</div>', format='1', courseid='$courseId', userid='{$uuid}', modulename='{$activity['module']}', instance='{$instanceId}', type='1', eventtype='expectcompletionon', visible='1', sequence='1', timestart='$date', timesort='$date'";
          $DB->execute($userEvent);
        $insertBlock = "INSERT INTO {block_recent_activity} (action,timecreated,courseid,cmid,userid) VALUES('1','$date','$courseId','{$activity['instanceId']}','{$row->id}')";
        $DB->execute($insertBlock);
        $updateModules = "UPDATE {course_modules} SET completionexpected = $date  WHERE id = '{$activity['instanceId']}'";
        $DB->execute($updateModules);
        $updateResource = "UPDATE {resource} SET revision = '2'  WHERE id = '{$instanceId}'";
        $DB->execute($updateResource);
      }
    }
    $cacherev = time();
    $courseSql = "UPDATE {course} SET cacherev = (CASE WHEN cacherev IS NULL THEN $cacherev WHEN cacherev < $cacherev THEN $cacherev WHEN cacherev > $cacherev + 3600 THEN $cacherev ELSE cacherev + 1 END) WHERE id = '$courseId'";
    $DB->execute($courseSql);
    if ($updateRecord) {
      return ['status' => 'true'];
    } else {
      return ['status' => 'false'];
    }
  }

  /**
   * Returns description of method result value
   * @return external_description
   */
  public static function update_completionexpected_by_id_returns() {
    return new external_single_structure(
      array(
      'status' => new external_value(PARAM_TEXT, 'status')
      )
    );
  }

}


