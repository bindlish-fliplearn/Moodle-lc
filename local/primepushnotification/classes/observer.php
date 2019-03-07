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
require_once($CFG->dirroot . '/local/primepushnotification/lib.php');
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
                            AND mc.contextlevel = ?
                            AND mra.userid 
                            iN(SELECT ue.userid FROM mdl_enrol 
                            AS me INNER JOIN mdl_user_enrolments 
                            AS ue ON me.id = ue.enrolid 
                            WHERE me.courseid = $courseId AND ue.status = 0)";
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
                         $result = curlPost($data_string, COMMUNICATION_API_URL);
                         $responseData = json_decode($result);
                         if($responseData->error !=null){
                         	echo $responseData->error;
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
    public static function attempt_question(\mod_quiz\event\attempt_submitted $event) {
                global $DB, $CFG;
                $quiz = $event->get_record_snapshot('quiz_attempts', $event->objectid);

                $cmid = $event->contextinstanceid;
                $reviewUrl = $CFG->wwwroot . '/mod/quiz/review.php?attempt=' .$quiz->id. '&cmid=' .$cmid;
                $quizId = $quiz->quiz;
                $totalSql = "SELECT count('qs.id') 
                              as totalQuestion FROM  {quiz_slots} as qs 
                              where qs.quizid = ?";

                $totalres = $DB->get_record_sql($totalSql, array($quizId));
                $totalQuestion = $totalres->totalquestion;

                $user_id = $event->userid;
                $userSql = "SELECT uuid FROM {guru_user_mapping} 
                            WHERE user_id =?";
                
                $userRes = $DB->get_record_sql($userSql, array($user_id));
                $uuid = $userRes->uuid;

                $attemptSQl = "SELECT max(iqa.uniqueid) AS uniqueid FROM {quiz_attempts} AS iqa WHERE iqa.userid = ? AND iqa.quiz = ? AND iqa.state = ?"; 
                $attRes = $DB->get_record_sql($attemptSQl, array($user_id,$quizId,'finished'));
                 $uniqueid = $attRes->uniqueid;

                $quizAttemptSql = "SELECT (qa.timefinish-qa.timestart) 
                                    AS timetaken, sum(case 
                                    when qas.state='gradedwrong' 
                                    then 1 else 0 end) as wrongAns,
                                    sum(case when qas.state ='gradedright' 
                                    then 1 else 0 end) as rightAns 
                                    FROM {quiz_attempts} as qa 
                                    JOIN {question_attempts} as qua 
                                    on qua.questionusageid = qa.uniqueid 
                                    join {question_attempt_steps} As qas 
                                    ON qas.questionattemptid = qua.id 
                                    WHERE  qua.questionusageid = ? 
                                    AND qas.sequencenumber = ? 
                                    AND  qa.userid = ?  
                                    AND qa.quiz = ? 
                                    AND qua.responsesummary != ?";

                $quizRes = $DB->get_record_sql($quizAttemptSql, array($uniqueid,2,$user_id,$quizId,'null'));

                if($quizRes){
                          $rightAns = $quizRes->rightans;
                          $wrongAns = $quizRes->wrongans;
                          $attemptedQuestions = $rightAns+$wrongAns;
                          $timeTaken = $quizRes->timetaken;
                          $serverurl = PRIME_URL.'/quiz/updateUserAssessmentLevel';
                          $params = array('uuid'=>$uuid, 'quizId'=> $cmid,
                          'totalQuestions'=>$totalQuestion,
                          'attemptedQuestions'=>$attemptedQuestions,
                          'correctAnswers'=>$rightAns,'wrongAnswers'=> $wrongAns,
                          'timeTaken'=>$timeTaken,
                          'reviewUrl'=>$reviewUrl);
                          $data_string = json_encode($params);
                          // $myfile = fopen($CFG->dirroot . '/local/primepushnotification/classes/log.text', "w") or die("Unable to open file!");
                          
                          // fwrite($myfile, $data_string);
                          $result = curlPost($data_string, $serverurl);
                          $responseData = json_decode($result);
                          $outPutdata = json_decode($responseData->data);
                          $attemptId = $outPutdata->attemptId;
                          setcookie("attemptId",$attemptId);
                         return $responseData; 
                }
  }
}