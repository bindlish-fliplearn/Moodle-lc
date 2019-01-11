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
 * Email signup notification event observers.
 *
 * @package    local_notifyemailsignup
 * @author     Iñaki Arenaza
 * @copyright  2017 Iñaki Arenaza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Email signup notification event observers.
 *
 * @package    local_primepushnotification
 * @author     Gaurav Kumar
 * @copyright  2017 Iñaki Arenaza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_primepushnotification_observer {
    /**
     * Event processor - discussion created
     *
     * @param \mod_forum\event\discussion_created
     * @return bool
     */
    public static function primepushnotification(\mod_forum\event\discussion_created $event) {
      global $DB, $CFG;
      require_once($CFG->dirroot . '/local/primepushnotification/lib.php');
            $discussionsData = $event->get_record_snapshot('forum_discussions', $event->objectid);
            $eventDate = date("Y-m-d\TH:i:s.511\Z", $discussionsData->timemodified);
            $eventType = GURU_ANNOUNCEMENT;
            $messageTitle = $discussionsData->subject;
            $messageText = $discussionsData->message;
            $courseId = $discussionsData->course;
            $userid = $discussionsData->userid;
            $postId  = $discussionsData->firstpost;
            $discussionId = $event->objectid;
            $contextlevel = CONTEXT_LEVEL;
            $send_notification = SEND_NOTIFICATION;
            $check = $DB->get_record_sql('SELECT COUNT(id) AS count FROM {guru_notification_send} WHERE post_id = ?', array($postId));
            if($check->count < 1 && $send_notification == true){
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
                  $clickUrl = BASE_URL.'/mod/forum/discuss.php?d='.$discussionId;

                    if(count($uuidList)>0){
                          $serializeRequest = array('senderUuid'=>1234,
                                              'schoolCode'=>$school_code,
                                              'messageTitle'=>$messageTitle,
                                              'messageText'=>strip_tags($messageText),
                                              'uuidList'=>$uuidList,
                                              'smsEnabled'=>false,
                                              'emailEnabled'=>false,
                                              'domainName'=>DOMAIN_NAME,
                                              'clickUrl'=>$clickUrl
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
                          
                          $err = curl_errno($ch);
                          if ($err) {
                          	echo  $err;
                           // $this->doError(curl_errno($ch), curl_error($ch));
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
}
