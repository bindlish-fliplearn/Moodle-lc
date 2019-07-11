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
require_once($CFG->dirroot . "/mod/braincert/locallib.php");

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
    return new external_function_parameters(
      array(
      'userDetails' => new external_multiple_structure(
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

  public static function flip_user_mapping($parms) {
    global $DB;
    $resp = array();
    $params = self::validate_parameters(self::flip_user_mapping_parameters(), array('userDetails' => $parms));
    $userDetails = $params['userDetails'][0];

    $userObj = new stdClass();
    $userObj->user_id = $userDetails['user_id'];
    $userObj->uuid = $userDetails['uuid'];
    $userObj->login_id = $userDetails['login_id'];
    $userObj->firstname = $userDetails['firstname'];
    $userObj->lastname = $userDetails['lastname'];
    $userObj->email = $userDetails['email'];
    $userObj->role = $userDetails['role'];
    $userObj->is_enrolled = $userDetails['is_enrolled'];
    $userObj->ayid = $userDetails['ayid'];
    $userObj->school_code = $userDetails['school_code'];
    $id = $DB->insert_record('guru_user_mapping', $userObj, $returnid = true, $bulk = false);
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
    if ($record) {
      $responseArray = ((array) $record);
      return ['result' => $responseArray, 'status' => 'true'];
      // return (array)$record; 
    } else {
      return ['result' => array("id" => '', "user_id" => "", "uuid" => "", "login_id" => "", "firstname" => "", "lastname" => "", "email" => "", "role" => "", "is_enrolled" => "", "school_code" => "", "ayid" => ""), "status" => "false"];
    }
  }

  /**
   * Returns description of method result value
   * @return external_description
   */
  public static function get_user_by_uuid_returns() {

    return new external_single_structure(
      array(
      "result" => new external_single_structure(
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
      'status' => new external_value(PARAM_TEXT, 'id of user'),
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
    if ($record) {
      $responseArray = ((array) $record);
      return ['result' => $responseArray, 'status' => 'true'];
      // return (array)$record; 
    } else {
      return ['result' => array("id" => '', "name" => "", "shortname" => "", "description" => ""), 'status' => 'false'];
    }
  }

  /**
   * Returns description of method result value
   * @return external_description
   */
  public static function get_role_details_by_shortname_returns() {
    return new external_single_structure(
      array(
      "result" => new external_single_structure(
        array(
        'id' => new external_value(PARAM_TEXT, 'id of role'),
        'name' => new external_value(PARAM_TEXT, 'role name'),
        'shortname' => new external_value(PARAM_TEXT, 'short name  '),
        'description' => new external_value(PARAM_TEXT, 'role description')
        )
      ),
      'status' => new external_value(PARAM_TEXT, 'status')
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
  public static function get_calendar_action_completed_events_by_timesort($timesortfrom = 0, $timesortto = null, $aftereventid = 0, $limitnum = 20) {
    global $CFG, $PAGE, $USER;
    require_once($CFG->dirroot . '/calendar/lib.php');
    $user = null;
    $params = self::validate_parameters(
        self::get_calendar_action_completed_events_by_timesort_parameters(), [
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
        $params['timesortfrom'], $params['timesortto'], $params['aftereventid'], $params['limitnum']
    );

    $exportercache = new events_related_objects_cache($events);
    $exporter = new events_exporter($events, ['cache' => $exportercache]);
    $returnArray = [];
    $data = $exporter->export($renderer);
    foreach ($events as $key => $value) {
      $data->events[$key]->completionstate = $value->completionstate;
      $data->events[$key]->completionexpected = $value->completionexpected;
      $data->events[$key]->timemodified = date('j M', (int) $value->timemodified);
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
    global $DB,$CFG;
    //REQUIRED
    self::validate_parameters(
      self::update_completionexpected_by_id_parameters(), array(
      'courseId' => $courseId,
      'assignDate' => $assignDate,
      'uuid' => $uuid,
      'activityId' => $activityId
      )
    );
    $date = strtotime($assignDate);
    $updateRecord = false;
    if (!empty($activityId)) {
      foreach ($activityId as $activity) {
        $courseModuleRaw = $DB->get_record('course_modules', array('id' => $activity['instanceId']));
        $instanceId = $courseModuleRaw->instance;
        $name = addslashes($activity['name']);
        $userEvent = "INSERT INTO {event} SET name='{$name}', description='<div class=no-overflow><p>{$name}</div>', format='1', courseid='$courseId', userid='{$uuid}', modulename='{$activity['module']}', instance='{$instanceId}', type='1', eventtype='expectcompletionon', visible='1', sequence='1', timestart='$date', timesort='$date'";
        $DB->execute($userEvent);
        $insertBlock = "INSERT INTO {block_recent_activity} (action,timecreated,courseid,cmid,userid) VALUES('1','$date','$courseId','{$activity['instanceId']}','$uuid')";
        $DB->execute($insertBlock);
        $updateModules = "UPDATE {course_modules} SET completionexpected = $date  WHERE id = '{$activity['instanceId']}'";
        $DB->execute($updateModules);
        $updateResource = "UPDATE {resource} SET revision = '2'  WHERE id = '{$instanceId}'";
        $updateRecord = $DB->execute($updateResource);
        if($CFG->wwwroot != 'https://guru.fliplearn.com') {
          self::sendNotification($activity, $date, $courseId, $uuid);
        }
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

  public static function sendNotification($event, $assignDate, $courseId, $uuid) {
    global $DB, $CFG;
    $objectid = $event['instanceId'];
    $tableName = '{' . $event['module'] . '}';
    $userSql = "SELECT completionexpected,intro FROM {course_modules} as cm
                           JOIN $tableName as mn on cm.instance = mn.id
                          WHERE cm.id =?";
    $courseRes = $DB->get_record_sql($userSql, array($objectid));
    $completionexpected = $courseRes->completionexpected;


    $currDate = date("Y-m-d");
    $currDateStr = strtotime($currDate);
    $updateDateStr = strtotime(date("Y-m-d", $completionexpected));


    if ($completionexpected != 0 && $currDateStr <= $updateDateStr) {
      $dueDate = date("Y-m-d", $completionexpected);
      $eventDate = date("Y-m-d\TH:i:s.511\Z", $assignDate);
      $eventType = $CFG->GURU_ANNOUNCEMENT;

      $domainName = str_replace("https://", "", $CFG->wwwroot);
      if (in_array($domainName, $CFG->domainList)) {
        $messageTitle = $event['name'];
        $messageText = $courseRes->intro;
      } else {
        $messageTitle = 'Homework assigned:' . $event['name'];
        $messageText = 'Due by ' . $dueDate;
      }
      $userid = $uuid;
      $contextlevel = $CFG->CONTEXT_LEVEL;
      $send_notification = $CFG->SEND_NOTIFICATION;

      $sql = "SELECT mra.userid,gum.uuid as uuid,
              gum.school_code AS school_code 
              FROM {context} As mc 
              INNER JOIN {role_assignments} AS mra 
              ON mc.id = mra.contextid 
              INNER JOIN  {guru_user_mapping} AS  gum 
              ON gum.user_id = mra.userid
              WHERE mra.userid != ?
              AND mc.instanceid = ? 
              AND mc.contextlevel = ?
              AND mra.userid 
              iN(SELECT ue.userid FROM mdl_enrol 
              AS me INNER JOIN mdl_user_enrolments 
              AS ue ON me.id = ue.enrolid 
              WHERE me.courseid = $courseId AND ue.status = 0)";
      $result = $DB->get_records_sql($sql, array($userid, $courseId, $contextlevel));
      $school_code = '';
      $uuidList = array();
      foreach ($result as $value) {
        $school_code = $value->school_code;
        $uuid = $value->uuid;
        array_push($uuidList, $uuid);
      }
      $modulename = $event['module'];
      $clickUrl = $CFG->wwwroot . "/mod/$modulename/view.php?id=" . $objectid . '&forceview=1';


      if (count($uuidList) > 0 && $send_notification == true) {
        $serializeRequest = array('senderUuid' => 1234,
          'schoolCode' => $school_code,
          'messageTitle' => $messageTitle,
          'messageText' => strip_tags($messageText),
          'uuidList' => $uuidList,
          'smsEnabled' => true,
          'emailEnabled' => true,
          'domainName' => $domainName,
          'clickUrl' => $clickUrl
        );
        $serializeRequest = json_encode($serializeRequest);
        $request = array(
          'eventType' => $eventType,
          'eventDate' => $eventDate,
          'payload' => $serializeRequest
        );
        $data_string = json_encode($request);
        $result = self::curlPost($data_string, $CFG->COMMUNICATION_API_URL);
        $responseData = json_decode($result);
        //print_r($responseData);die;
        if ($responseData->error != null) {
          echo $responseData->error;
        }
      }
    }
  }

  public static function curlPost($data_string, $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); //timeout in seconds
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data_string))
    );

    $result = curl_exec($ch);
    $error = curl_errno($ch);
    $responseArray = array('error' => null, 'data' => '');
    if ($result) {
      $responseArray['data'] = $result;
      return json_encode($responseArray);
    } else {
      $responseArray['error'] = $error;
      return json_encode($responseArray);
    }
  }

  /**
   * Returns description of method parameters
   * @return external_function_parameters
   */
  public static function add_update_ptm_parameters() {
    return new external_function_parameters(
      array(
      'ptmId' => new external_value(PARAM_TEXT, 'This is homework course id.'),
      'userId' => new external_value(PARAM_TEXT, 'This is homework assign date.'),
      'teacherId' => new external_value(PARAM_TEXT, 'This is homework assign date.'),
      'ptmDate' => new external_value(PARAM_TEXT, 'This is homework assign date.'),
      'teacherRemark' => new external_value(PARAM_TEXT, 'This is homework assign date.'),
      'parentFeedback' => new external_value(PARAM_TEXT, 'This is homework assign date.')
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
  public static function add_update_ptm($ptmId, $userId, $teacherId, $ptmDate, $teacherRemark, $parentFeedback) {
    global $DB;
    //REQUIRED
    $return = false;
    self::validate_parameters(
      self::add_update_ptm_parameters(), array(
      'ptmId' => $ptmId,
      'userId' => $userId,
      'teacherId' => $teacherId,
      'ptmDate' => $ptmDate,
      'teacherRemark' => $teacherRemark,
      'parentFeedback' => $parentFeedback
      )
    );
    if(empty($ptmId)) {
      $insertRecord = [];
      $insertRecord['user_id'] = $userId;
      $insertRecord['teacher_id'] = $teacherId;
      $insertRecord['ptm_date'] = $ptmDate;
      $insertRecord['teacher_remark'] = $teacherRemark;
      $insertRecord['parent_feedback'] = $parentFeedback;
      $DB->insert_record('guru_user_ptm', $insertRecord);
      $return = true;
    } else {
      $ptmRecord = $DB->get_record('guru_user_ptm', array('id' => $ptmId));
      if(!empty($ptmRecord)) {
        $ptmRecord->ptm_date = $ptmDate;
        $ptmRecord->teacher_remark = $teacherRemark;
        $ptmRecord->parent_feedback = $parentFeedback;
        $DB->update_record('guru_user_ptm', $ptmRecord);
        $return = true;
      }
    }
    if ($return) {
      return ['status' => 'true'];
    } else {
      return ['status' => 'false'];
    }
  }

  /**
   * Returns description of method result value
   * @return external_description
   */
  public static function add_update_ptm_returns() {
    return new external_single_structure(
      array(
      'status' => new external_value(PARAM_TEXT, 'status')
      )
    );
  }

    /**
   * Returns description of method parameters
   * @return external_function_parameters
   */
  public static function guru_vedio_view_parameters() {
    return new external_function_parameters(
      array(
      'view_time' => new external_value(PARAM_NUMBER, 'This is view time.'),
      'duration' => new external_value(PARAM_NUMBER, 'This is vedio duration.'),
      'context_id' => new external_value(PARAM_INT, 'This is context id .'),
      'title' => new external_value(PARAM_TEXT, 'This is vedio title  .'),
      'file' => new external_value(PARAM_TEXT, 'This is play vedio file  .'),
      )
    );
  }

  /**
   * Returns welcome message
   * @return string welcome message
   */
  public static function guru_vedio_view($view_time, $duration, $context_id, $title, $file) {
    global $DB;
    global $USER;
    $userid = $_COOKIE['currentLoginUser'];
    //REQUIRED
    self::validate_parameters(
      self::guru_vedio_view_parameters(), array(
      'view_time' => $view_time,
      'duration' => $duration,
      'context_id' => $context_id,
      'title' => $title,
      'file'=>$file
      )
    );
      $getCoursesql = "SELECT cm.course as course FROM {context} as c 
              JOIN {course_modules} as cm 
              on cm.id = c.instanceid where c.id = $context_id";
      $courseResult = $DB->get_record_sql($getCoursesql);
      $courseid = $courseResult->course;
      $insertRecord = [];
      $insertRecord['view_time'] = $view_time;
      $insertRecord['duration'] = $duration;
      $insertRecord['context_id'] = $context_id;
      $insertRecord['title'] = $title;
      $insertRecord['file'] = $file;
      $insertRecord['course_id'] = $courseid;
      $insertRecord['user_id'] = $userid;

      $checkTimeSql = "SELECT view_time from {guru_video_view} 
                      WHERE context_id = $context_id 
                      AND user_id = $userid";

      $timeResult = $DB->get_record_sql($checkTimeSql);
      if($timeResult){
          $old_view_time = $timeResult->view_time;
          if($old_view_time < $view_time){
            $updateSql = "UPDATE {guru_video_view} SET view_time = $view_time WHERE context_id = $context_id AND user_id = $userid ";
            $DB->execute($updateSql);
            return ['status' => 'true'];
          }else {
             return ['status' => 'true'];
          }
      }else{
        $DB->insert_record('guru_video_view', $insertRecord);
        return ['status' => 'true'];
      }
  }
   /**
   * Returns description of method result value
   * @return external_description
   */
  public static function guru_vedio_view_returns() {
    return new external_single_structure(
      array(
      'status' => new external_value(PARAM_TEXT, 'status')
      )
    );
  }
  
  /**
   * Returns description of method parameters
   * @return external_function_parameters
   */
  public static function add_reminder_live_class_parameters() {
    return new external_function_parameters(
      array(
      'live_class_id' => new external_value(PARAM_TEXT, 'This is homework course id.'),
      'user_id' => new external_value(PARAM_TEXT, 'This is homework assign date.'),
      'class_time' => new external_value(PARAM_TEXT, 'This is homework assign date.')
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
  public static function add_reminder_live_class($live_class_id, $user_id, $class_time) {
    global $DB;
    //REQUIRED
    self::validate_parameters(
      self::add_reminder_live_class_parameters(), array(
      'live_class_id' => $live_class_id,
      'user_id' => $user_id,
      'class_time' => $class_time
      )
    );
    $date = time();
    $reminderCreated = false;
    $userSql = "SELECT u.id as id,u.firstname as firstname from {user} u join {guru_user_mapping} um on u.id=um.user_id where um.uuid=$user_id";
    $userObj = $DB->get_record_sql($userSql);
    $checkRemider = "SELECT id from {guru_reminder} where user_id='{$userObj->id}' AND class_id='{$live_class_id}'";
    $remiderObj = $DB->get_record_sql($checkRemider);
    if (empty($remiderObj) && !empty($live_class_id) && !empty($userObj) && !empty($class_time)) {
      $reminderCreated = "INSERT INTO {guru_reminder} SET class_id='{$live_class_id}', user_id='{$userObj->id}', class_time='{$class_time}',timecreated='$date'";
      $DB->execute($reminderCreated);
    }
    if ($reminderCreated) {
      return ['status' => 'true'];
    } else {
      return ['status' => 'false'];
    }
  }

  /**
   * Returns description of method result value
   * @return external_description
   */
  public static function add_reminder_live_class_returns() {
    return new external_single_structure(
      array(
      'status' => new external_value(PARAM_TEXT, 'status')
      )
    );
  }
  
  /**
   * Returns description of method parameters
   * @return external_function_parameters
   */
  public static function get_live_classes_parameters() {
    return new external_function_parameters(
      array(
      'course_id' => new external_value(PARAM_TEXT, 'User course id.'),
      'user_id' => new external_value(PARAM_TEXT, 'User id.'),
      'class_id' => new external_value(PARAM_TEXT, 'Class id.'),
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
  public static function get_live_classes($course_id, $user_id, $class_id) {
    global $DB, $CFG;
    $return = false;
    //REQUIRED
    self::validate_parameters(
      self::get_live_classes_parameters(), array(
      'course_id' => $course_id,
      'user_id' => $user_id,
      'class_id' => $class_id
      )
    );
    $userSql = "SELECT u.id as id,u.firstname as firstname from {user} u join {guru_user_mapping} um on u.id=um.user_id where um.uuid=$user_id";
    $userObj = $DB->get_record_sql($userSql);
    if (empty($course_id)) {
      $coursies = enrol_get_all_users_courses($userObj->id);
      foreach ($coursies as $course) {
        $courseId[] = $course->id;
      }
      $course = implode(",", $courseId);
    } else {
      $course = $course_id;
    }
      $where = "";
      if (!empty($class_id)) {
        $where = "AND class_id=$class_id";
      }
      $now = time();
      $mod_date = strtotime("+15 days",$now);
      if (!empty($course)) {
        $getCourseModuleSql = "SELECT cm.*,m.name as modulename,m.id as moduleid FROM {modules} as m join {course_modules} as cm on m.id=cm.module WHERE m.name in ('braincert','wiziq') AND cm.course in ($course) ORDER BY id DESC";
        $courseResult = $DB->get_records_sql($getCourseModuleSql);
        $response = [];
        foreach ($courseResult as $activity) {
          $resp = [];
          $modulename = '{' . $activity->modulename . '}';
          $instance = $activity->instance;
          $whereTime = "";
          $classDetailsSql = "";
          if($activity->modulename == "wiziq") {
            $whereTime = " AND UNIX_TIMESTAMP(DATE_ADD(from_unixtime(m.wiziq_datetime), INTERVAL m.duration MINUTE)) BETWEEN $now AND $mod_date";
            $classDetailsSql = "SELECT m.*,gr.id as remiderid, UNIX_TIMESTAMP(DATE_ADD(from_unixtime(m.wiziq_datetime), INTERVAL m.duration MINUTE)) as enddateTime from $modulename m left join {guru_reminder} gr on m.class_id=gr.class_id WHERE m.id=$instance $whereTime $where LIMIT 15";
          } else {
            $whereTime = " AND UNIX_TIMESTAMP(STR_TO_DATE(concat(DATE_FORMAT(FROM_UNIXTIME(m.start_date), '%Y-%m-%d '), m.end_time), '%Y-%m-%d %h:%i%p')) BETWEEN $now AND $mod_date";
            $classDetailsSql = "SELECT m.*,gr.id as remiderid, UNIX_TIMESTAMP(STR_TO_DATE(concat(DATE_FORMAT(FROM_UNIXTIME(m.start_date), '%Y-%m-%d '), m.end_time), '%Y-%m-%d %h:%i%p')) from $modulename m left join {guru_reminder} gr on m.class_id=gr.class_id WHERE m.id=$instance $whereTime $where LIMIT 15";
          }
          $classResult = $DB->get_record_sql($classDetailsSql);
          $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
          $context = get_context_instance(CONTEXT_COURSE, $activity->course);
          $teachers = get_role_users($role->id, $context);
          if (!empty($classResult)) {
            $isteacher = 0;
            $teachersDetails = [];
            if (!empty($teachers)) {
              foreach ($teachers as $teacher) {
                $teacherD['id'] = $teacher->id;
                $teacherD['name'] = $teacher->firstname;
                $teacherD['picture'] = $CFG->wwwroot . '/user/pix.php/' . $teacher->id . '/f1.jpg';
                $teachersDetails[] = $teacherD;
                if ($userObj->id == $teacher->id) {
                  $isteacher = 1;
                }
              }
            } else {
              $teacherD['id'] = "";
              $teacherD['name'] = "";
              $teacherD['picture'] = "";
              $teachersDetails[] = $teacherD;
            }
            $resp['courseid'] = $classResult->course;
            $resp['classid'] = $classResult->class_id;
            $resp['teachers'] = $teachersDetails;
            $resp['addreminder'] = (!empty($classResult->remiderid)?'true':'false');
            
            if ($activity->modulename == "wiziq") {
              $resp['title'] = $classResult->name;
              $resp['starton'] = date('h:m A, d M', $classResult->wiziq_datetime);
              $resp['startin'] = $classResult->wiziq_datetime;
              $resp['duration'] = $classResult->duration;
              $resp['joinurl'] = $classResult->presenter_url;
            } else {
              $item = array();
              $item['userid'] = $userObj->id;
              $item['username'] = $userObj->firstname;
              $item['classname'] = $classResult->name;
              $item['isteacher'] = $isteacher;
              $item['classid'] = $classResult->class_id;
              $getlaunchurl = braincert_get_launch_url($item);
              $launchurl = "";
              if ($getlaunchurl['status'] == "ok") {
                $launchurl = $getlaunchurl['launchurl'];
              }
              $resp['title'] = $classResult->name;
              $resp['starton'] = date('h:m A, d M', $classResult->start_date);
              $resp['startin'] = $classResult->start_date;
              $to_time = strtotime(date('y-m-d').$classResult->start_time);
              $from_time = strtotime(date('y-m-d').$classResult->end_time);
              $resp['duration'] = round(abs($to_time - $from_time) / 60,2);
              $resp['joinurl'] = $launchurl;
            }
          $response[$resp['startin']] = $resp;
          }
        }
        $return = true;
      }
    ksort($response);
    array_values($response);
    if ($return) {
      return ['status' => 'true', 'liveclass' => $response];
    } else {
      return ['status' => 'false', 'liveclass' => array()];
    }
  }
  
  /**
   * Returns description of method result value
   * @return external_description
   */
  public static function get_live_classes_returns() {
    return new external_single_structure(
      array(
      'status' => new external_value(PARAM_TEXT, 'status'),
      'liveclass' => new external_multiple_structure(new external_single_structure(array(
      'courseid' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
      'addreminder' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
      'classid' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
        'teachers' => new external_multiple_structure(new external_single_structure(array(
        'id' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
        'name' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
        'picture' => new external_value(PARAM_TEXT, 'This is homework cm id.')
        ))),
      'title' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
      'duration' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
      'starton' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
      'startin' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
      'joinurl' => new external_value(PARAM_TEXT, 'This is homework cm id.'),
      )))
      )
    );
  }
}
