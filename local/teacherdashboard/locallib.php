  <?php

  function getCourseScore($courseIds,$userid){
     global $DB; 
    $courseIdsString = implode(',', $courseIds);

    $sql = "select ROUND((sum(t2.quizPercent)/count(t2.id)),2) as totalQuizPer 
          from (SELECT q.id, q.course, q.name, qa1.*
          FROM mdl_quiz as q
          inner join (select t1.*, count(qs.id) as totalQue,  ((t1.rightAns/count(qs.id))*100) as quizPercent 
          from mdl_quiz_slots as qs
          inner join (SELECT qa.userid, qa.uniqueid, qa.quiz, qua.questionusageid, (qa.timefinish-qa.timestart)
          AS timetaken, sum(case
          when qas.state='gradedwrong'
          then 1 else 0 end) as wrongAns,
          sum(case when qas.state ='gradedright'
          then 1 else 0 end) as rightAns
          FROM mdl_quiz_attempts as qa
          inner join mdl_question_attempts as qua on qua.questionusageid = qa.uniqueid and qa.attempt = 1 and qa.userid = $userid
          inner join mdl_question_attempt_steps As qas ON qas.questionattemptid = qua.id
          where qua.responsesummary != 'null' group by qa.quiz) as t1  on qs.quizid = t1.quiz group by t1.quiz) as qa1 on q.id = qa1.quiz
          where q.course in ($courseIdsString)) as t2 group by t2.userid";
    $teacherrecord = $DB->get_record_sql($sql);
    if($teacherrecord){
      return $teacherrecord->totalquizper;
    }else {
      return 0;
    }
  }
  function getUserProfile($userid){
    global $DB; 
      $userInfoSql = "SELECT uif.shortname,uif.name, uid.data 
                      FROM {user_info_field} AS uif 
                      LEFT JOIN {user_info_data} AS uid
                      ON uif.id= uid.fieldid AND uid.userid = $userid";
        $userInfo = $DB->get_records_sql($userInfoSql);
      if($userInfo){
        return $userInfo;
      }else {
        return 0;
      }
  }
  function getUserProfileHead(){
      global $DB;
       $userInfoSql = "SELECT name,shortname 
                      FROM {user_info_field}";
        $userInfo = $DB->get_records_sql($userInfoSql);
      if($userInfo){
        return $userInfo;
      }else {
        return array();
      }
  }
  function getAttendence($userid,$courseIds){
     global $DB;
         $courseIdsString = implode(',', $courseIds);
    $attendancesql = "SELECT count(b.id) as totalClass, 
                  count(gbu.user_id) as totalAttendClass, 
                  ROUND((count(gbu.user_id)/count(b.id)*100),2) as attendanceper 
            FROM {braincert} as b
            Left JOIN {guru_braincert_user} as gbu 
            on b.class_id=gbu.class_id and gbu.user_id=$userid
            WHERE b.course in ($courseIdsString)";
      $attendanceInfo = $DB->get_record_sql($attendancesql);
    if($attendanceInfo){
         if($attendanceInfo->attendanceper == ''){
            return 0;
         }
         else{
            return $attendanceInfo->attendanceper;
          }
      }else {
        return 0;
      }

  }
  ?>