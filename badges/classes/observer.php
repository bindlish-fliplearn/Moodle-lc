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
   * Local stuff for category enrolment plugin.
   *
   * @package    core_badges
   * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
   * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
   */

  defined('MOODLE_INTERNAL') || die();


  /**
   * Event observer for badges.
   */
  class core_badges_observer {
      /**
       * Triggered when 'course_module_completion_updated' event is triggered.
       *
       * @param \core\event\course_module_completion_updated $event
       */
      public static function course_module_criteria_review(\core\event\course_module_completion_updated $event) {
          global $DB, $CFG;

          if (!empty($CFG->enablebadges)) {
              require_once($CFG->dirroot.'/lib/badgeslib.php');

              $eventdata = $event->get_record_snapshot('course_modules_completion', $event->objectid);
              $userid = $event->relateduserid;
              $mod = $event->contextinstanceid;

              if ($eventdata->completionstate == COMPLETION_COMPLETE
                  || $eventdata->completionstate == COMPLETION_COMPLETE_PASS
                  || $eventdata->completionstate == COMPLETION_COMPLETE_FAIL) {
                  // Need to take into account that there can be more than one badge with the same activity in its criteria.
                  if ($rs = $DB->get_records('badge_criteria_param', array('name' => 'module_' . $mod, 'value' => $mod))) {
                      foreach ($rs as $r) {
                          $bid = $DB->get_field('badge_criteria', 'badgeid', array('id' => $r->critid), MUST_EXIST);
                          $badge = new badge($bid);
                          if (!$badge->is_active() || $badge->is_issued($userid)) {
                              continue;
                          }

                          if ($badge->criteria[BADGE_CRITERIA_TYPE_ACTIVITY]->review($userid)) {
                              $badge->criteria[BADGE_CRITERIA_TYPE_ACTIVITY]->mark_complete($userid);

                              if ($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]->review($userid)) {
                                  $badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]->mark_complete($userid);
                                  $badge->issue($userid);
                              }
                          }
                      }
                  }
              }
          }
      }

      /**
       * Triggered when 'course_completed' event is triggered.
       *
       * @param \core\event\course_completed $event
       */
      public static function course_criteria_review(\core\event\course_completed $event) {
          global $DB, $CFG;

          if (!empty($CFG->enablebadges)) {
              require_once($CFG->dirroot.'/lib/badgeslib.php');

              $eventdata = $event->get_record_snapshot('course_completions', $event->objectid);
              $userid = $event->relateduserid;
              $courseid = $event->courseid;

              // Need to take into account that course can be a part of course_completion and courseset_completion criteria.
              if ($rs = $DB->get_records('badge_criteria_param', array('name' => 'course_' . $courseid, 'value' => $courseid))) {
                  foreach ($rs as $r) {
                      $crit = $DB->get_record('badge_criteria', array('id' => $r->critid), 'badgeid, criteriatype', MUST_EXIST);
                      $badge = new badge($crit->badgeid);
                      if (!$badge->is_active() || $badge->is_issued($userid)) {
                          continue;
                      }

                      if ($badge->criteria[$crit->criteriatype]->review($userid)) {
                          $badge->criteria[$crit->criteriatype]->mark_complete($userid);

                          if ($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]->review($userid)) {
                              $badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]->mark_complete($userid);
                              $badge->issue($userid);
                          }
                      }
                  }
              }
          }
      }

      /**
       * Triggered when 'badge_awarded' event happens.
       *
       * @param \core\event\badge_awarded $event event generated when a badge is awarded.
       */
      public static function badge_criteria_review(\core\event\badge_awarded $event) {
          global $DB, $CFG;

          if (!empty($CFG->enablebadges)) {
              require_once($CFG->dirroot.'/lib/badgeslib.php');
              $userid = $event->relateduserid;

              if ($rs = $DB->get_records('badge_criteria', array('criteriatype' => BADGE_CRITERIA_TYPE_BADGE))) {
                  foreach ($rs as $r) {
                      $badge = new badge($r->badgeid);
                      if (!$badge->is_active() || $badge->is_issued($userid)) {
                          continue;
                      }

                      if ($badge->criteria[BADGE_CRITERIA_TYPE_BADGE]->review($userid)) {
                          $badge->criteria[BADGE_CRITERIA_TYPE_BADGE]->mark_complete($userid);

                          if ($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]->review($userid)) {
                              $badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]->mark_complete($userid);
                              $badge->issue($userid);
                          }
                      }
                  }
              }
          }
      }
      /**
       * Triggered when 'user_updated' event happens.
       *
       * @param \core\event\user_updated $event event generated when user profile is updated.
       */
      public static function profile_criteria_review(\core\event\user_updated $event) {
          global $DB, $CFG;

          if (!empty($CFG->enablebadges)) {
              require_once($CFG->dirroot.'/lib/badgeslib.php');
              $userid = $event->objectid;

              if ($rs = $DB->get_records('badge_criteria', array('criteriatype' => BADGE_CRITERIA_TYPE_PROFILE))) {
                  foreach ($rs as $r) {
                      $badge = new badge($r->badgeid);
                      if (!$badge->is_active() || $badge->is_issued($userid)) {
                          continue;
                      }

                      if ($badge->criteria[BADGE_CRITERIA_TYPE_PROFILE]->review($userid)) {
                          $badge->criteria[BADGE_CRITERIA_TYPE_PROFILE]->mark_complete($userid);

                          if ($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]->review($userid)) {
                              $badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]->mark_complete($userid);
                              $badge->issue($userid);
                          }
                      }
                  }
              }
          }
      }

      /**
       * Triggered when the 'cohort_member_added' event happens.
       *
       * @param \core\event\cohort_member_added $event generated when a user is added to a cohort
       */
      public static function cohort_criteria_review(\core\event\cohort_member_added $event) {
          global $DB, $CFG;

          if (!empty($CFG->enablebadges)) {
              require_once($CFG->dirroot.'/lib/badgeslib.php');
              $cohortid = $event->objectid;
              $userid = $event->relateduserid;

              // Get relevant badges.
              $badgesql = "SELECT badgeid
                  FROM {badge_criteria_param} cp
                  JOIN {badge_criteria} c ON cp.critid = c.id
                  WHERE c.criteriatype = ?
                  AND cp.name = ?";
              $badges = $DB->get_records_sql($badgesql, array(BADGE_CRITERIA_TYPE_COHORT, "cohort_{$cohortid}"));
              if (empty($badges)) {
                  return;
              }

              foreach ($badges as $b) {
                  $badge = new badge($b->badgeid);
                  if (!$badge->is_active()) {
                      continue;
                  }
                  if ($badge->is_issued($userid)) {
                      continue;
                  }

                  if ($badge->criteria[BADGE_CRITERIA_TYPE_COHORT]->review($userid)) {
                      $badge->criteria[BADGE_CRITERIA_TYPE_COHORT]->mark_complete($userid);

                      if ($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]->review($userid)) {
                          $badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]->mark_complete($userid);
                          $badge->issue($userid);
                      }
                  }
              }
          }
      }
      
      /**
       * Triggered when 'course_completed' event is triggered.
       *
       * @param \core\event\course_completed $event
       */
      public static function course_created_push_notification(\core\event\course_created $event) {
       /*echo "<pre>"; print_r($event); //die;
        //Check prime license & session Token
        global $SESSION, $USER, $DB, $CFG;
        //print_r($USER->id);
        
        $userInfo = $DB->get_record('guru_user_mapping', array('user_id' => $USER->id), '*');
        
        $conn2 = new curl(array('cache'=>true, 'debug'=>false));
        $api_path2 = UMS_URL . "/autologinByUuid/$userInfo->uuid";    
        $content2 = $conn2->get($api_path2,'');
        $result2 = json_decode($content2);
        
        if (isset($result2->data->sessionToken)) {
            $SESSION->sessionToken = $result2->data->sessionToken;
        } else {
            return API_FAIL_MSG;
        }
                      
        $api_path = UMS_URL . "/isLoginTokenValidForUserByUuid";
  //      $params = array('uuid' => $userInfo->uuid,
  //                      'sessionToken' => $SESSION->sessionToken
  //                  );
        
        
        $serializeRequest = array('senderUuid'=>1234,
                                          'schoolCode'=>$school_code,
                                          'messageTitle'=>$messageTitle,
                                          'messageText'=>strip_tags($messageText),
                                          'uuidList'=>$uuidList,
                                          'smsEnabled'=>false,
                                          'emailEnabled'=>false,
                                          'domainName'=>'stgmoodlelc.fliplearn.com'
                                      );
                          $serializeRequest =  json_encode($serializeRequest);
                          $request =  array(
                            'eventType' => $eventType,
                            'eventDate' => $eventDate,
                            'payload' => $serializeRequest
                          ); 
                          $params = json_encode($request);
                        
                          
        $params_json = json_encode($params);
        $conn->setHeader(array(
            'Content-Type: application/json',
            'Connection: keep-alive',
            'Cache-Control: no-cache'));
        $content = $conn->post($api_path,$params_json);
        $result = json_decode($content);
        if (isset($result->status)) {
            $tokenValid = $result->status;
        }*/
      }
      
      public static function course_module_completion_updated_push_notification(\core\event\course_module_completion_updated $event) {
        die("course_module_completion_updated_push_notification");
      }
      
      public static function course_completed_push_notification(\core\event\course_completed $event) {
        die("course_completed_push_notification");
      }
      
       public static function post_created_push_notification(\core\event\course_updated $event) {
        die("post_created_push_notification");
      }
      
      public static function discussion_created_push_notification(\mod_forum\event\discussion_created $event) {
            global $DB, $CFG;
            $discussionsData = $event->get_record_snapshot('forum_discussions', $event->objectid);
            $eventDate = date("Y-m-d\TH:i:s.511\Z", $discussionsData->timemodified);
            $eventType = GURU_ANNOUNCEMENT;
            $messageTitle = $discussionsData->subject;
            $messageText = $discussionsData->message;
            $courseId = $discussionsData->course;
            $userid = $discussionsData->userid;
            $postId  = $discussionsData->firstpost;
            $contextlevel = CONTEXT_LEVEL;
            $check = $DB->get_record_sql('SELECT COUNT(id) AS count FROM {guru_notification_send} WHERE post_id = ?', array($postId));
            if($check->count < 1){
                    $sql = "SELECT mra.userid,gum.uuid as uuid,
                            gum.school_code AS school_code 
                            FROM {context} As mc 
                            INNER JOIN {role_assignments} AS mra 
                            ON mc.id = mra.contextid 
                            INNER JOIN  {guru_user_mapping} AS  gum 
                            ON gum.user_id = mra.userid
                            WHERE mra.userid != ?
                            AND mc.instanceid = ? 
                            AND mc.contextlevel = ? ";
                    $result = $DB->get_records_sql($sql , array($userid,$courseId,$contextlevel));

                    $school_code = '';
                    $uuidList = array();
                    foreach($result as $value) {
                          $school_code = $value->school_code;
                          $uuid = $value->uuid;
                          array_push($uuidList, $uuid);
                    }

                    if(count($uuidList)>0){
                          $serializeRequest = array('senderUuid'=>1234,
                                              'schoolCode'=>$school_code,
                                              'messageTitle'=>$messageTitle,
                                              'messageText'=>strip_tags($messageText),
                                              'uuidList'=>$uuidList,
                                              'smsEnabled'=>false,
                                              'emailEnabled'=>false,
                                              'domainName'=>DOMAIN_NAME
                                              );

                          $serializeRequest =  json_encode($serializeRequest);
                          $request =  array(
                                            'eventType' => $eventType,
                                            'eventDate' => $eventDate,
                                            'payload' => $serializeRequest
                                            ); 
                          $data_string = json_encode($request);
                          $ch = curl_init(COMMUNICATION_API_URL);
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
                         // print_r($result);die;
                          $err = curl_errno($ch);
                          if ($err) {
                            print_r($err);
                            //$this->doError(curl_errno($ch), curl_error($ch));
                          }   
                          try {
                              $notificationObj = new stdClass();
                              $notificationObj->course_id = $courseId;
                              $notificationObj->post_id = $postId;
                              $notificationObj->user_id = $userid;
                              $id =  $DB->insert_record('guru_notification_send',$notificationObj , $returnid=true, $bulk=false) ;
                              if($id){
                                return true;
                              }
                          }
                          catch(Exception $e) {
                              echo 'Message: ' .$e->getMessage();
                          }
                    }
            }
      }
      public static function course_section_created_push_notification(\core\event\course_section_created $event) {
       // die("course_section_created_push_notification");
      }
      
  }
