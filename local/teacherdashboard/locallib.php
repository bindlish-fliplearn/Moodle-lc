<?php 
function getCourseScore($courseIds){
   global $DB;
  $courseIdsString = implode(',', $courseIds);

  $sql = "select (sum(t2.quizPercent)/count(t2.id)) as totalQuizPer 
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
        inner join mdl_question_attempts as qua on qua.questionusageid = qa.uniqueid and qa.attempt = 1 and qa.userid = 2
        inner join mdl_question_attempt_steps As qas ON qas.questionattemptid = qua.id
        where qua.responsesummary != 'null' group by qa.quiz) as t1  on qs.quizid = t1.quiz group by t1.quiz) as qa1 on q.id = qa1.quiz
        where q.course in ($courseIdsString)) as t2 group by t2.userid";
  $teacherrecord = $DB->get_record_sql($sql);
  if($teacherrecord){
    return $teacherrecord->totalQuizPer;
  }else {
    return 0;
  }
}
?>