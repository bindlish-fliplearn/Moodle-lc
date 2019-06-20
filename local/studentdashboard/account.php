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
 * Local plugin "flipdashboard" - Enrolment page
 *
 * @package   local_flipdashboard
 * @copyright 2017 Soon Systems GmbH on behalf of Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('locallib.php');
require_once('../../course/lib.php');

$hideForStu = array("studentAspirations","parentAspirations","studentInterest", "teacherRemark");
global $USER;
$userId = optional_param('id', null, PARAM_INT);
if (empty($userId)) {  
  $userId = $USER->id;
  $id = $userId;
} else {
  $id = $userId;
}

$checkUserSql = "SELECT count(id) as count FROM {user} WHERE id=?";
$userCount = $DB->get_record_sql($checkUserSql, array($userId));

if ($userCount->count == 0) {
  print_error("User id not valid.");
}

require_login();

$coursies = enrol_get_all_users_courses($id);

$userSql = "SELECT uif.shortname,uif.name, uid.data
         FROM {user_info_field} AS uif 
         LEFT JOIN {user_info_data} AS uid 
         ON uid.fieldid = uif.id AND uid.userid = $id";
$userDetails = $DB->get_records_sql($userSql);


$courseId = [];
$courseName = [];
foreach ($coursies as $course) {
  $programName .= $course->shortname . ', ';
  $courseId[] = $course->id;
  $courseName[] = $course->fullname;
}

$programName = rtrim($programName, ', ');

$PAGE->set_url('/local/sudenttdashboard/account.php');
if(empty($userId)) {
  $PAGE->set_title("$userDetails->firstname $USER->lastname: " . get_string('accountheading', 'local_studentdashboard'));
  $PAGE->set_heading("$USER->firstname $USER->lastname: " . get_string('accountheading', 'local_studentdashboard'));
} else {
  $userNameSql = "SELECT u.firstname,u.lastname
         FROM {user} AS u
         WHERE u.id = $userId";
  $userName = $DB->get_record_sql($userNameSql);
  $PAGE->set_title("$userName->firstname $userName->lastname: " . get_string('accountheading', 'local_studentdashboard'));
  $PAGE->set_heading("$userName->firstname $userName->lastname: " . get_string('accountheading', 'local_studentdashboard'));
}

$PAGE->set_pagelayout('incourse');


$PAGE->navbar->add(get_string('accountheading', 'local_studentdashboard'));

echo $OUTPUT->header();

echo $OUTPUT->heading("$USER->firstname $USER->lastname: " . get_string('accountheading', 'local_studentdashboard'));
echo $OUTPUT->heading("Profile Summary", 4);
$studenttable = new html_table();
$studenttable->data[] = ['Name', $USER->firstname . ' ' . $USER->lastname];
$studenttable->data[] = ['Program', $programName];
foreach ($userDetails as $userData) {
  if(user_has_role_assignment($USER->id,5) && in_array($userData->shortname, $hideForStu)) {
    $studenttable->data[] = [$userData->name, $userData->data];
  } else {
    $studenttable->data[] = [$userData->name, $userData->data];
  }
}

if (!empty($studenttable)) {
  echo html_writer::start_tag('div', array('class' => 'no-overflow display-table attendeestable'));
  echo html_writer::table($studenttable);
  echo html_writer::end_tag('div');
}

echo $OUTPUT->heading("PTM Remarks", 4);

echo html_writer::start_tag('div', array('class' => 'no-overflow'));
echo html_writer::start_tag('a', array('class' => 'btn btn-success', 'src' => '#0', 'onClick' => "showPtmPopup('', $id, $USER->id );"));
echo "Add PTM Remark";
echo html_writer::end_tag('a');
echo html_writer::end_tag('div');


$ptmtable = new html_table();
$ptmtable->head = array();
$ptmtable->head[] = 'Date';
$ptmtable->head[] = 'Teacher Remark for Student/Parent';
$ptmtable->head[] = 'Parent Feedback';
if(user_has_role_assignment($USER->id,5)) {
  $ptmtable->head[] = 'Action';
}

$ptmRecord = $DB->get_records('guru_user_ptm', array('user_id' => $id, 'teacher_id' => $USER->id));
if (!empty($ptmRecord)) {
  foreach ($ptmRecord as $remark) {
    $row = array();
    $row[] = $remark->ptm_date;
    $row[] = $remark->teacher_remark;
    $row[] = $remark->parent_feedback;
    if(user_has_role_assignment($USER->id,5)) {
    $row[] = "<a href='#0' onclick='showPtmPopup(\"$remark->id\", \"$remark->user_id\", \"$USER->id\", \"$remark->ptm_date\", \"$remark->teacher_remark\", \"$remark->parent_feedback\")'>Edit</a>"; 
    }
    $ptmtable->data[] = $row;
  }
} else {
  $row = array();
  $row[] = "";
  $row[] = "No Record Found.";
  $row[] = "";
  $ptmtable->data[] = $row;
}

if (!empty($ptmtable)) {
  echo html_writer::start_tag('div', array('class' => 'no-overflow display-table attendeestable'));
  echo html_writer::table($ptmtable);
  echo html_writer::end_tag('div');
}

echo $OUTPUT->heading("Test Performance", 4);

$testTable = new html_table();
$testTable->head = array();
$testTable->head[] = 'Test';
foreach ($courseName as $name) {
  $testTable->head[] = $name;
}

$course = implode(",", $courseId);
if (!empty($course)) {
  $quizAttempt = "SELECT q.id, q.course, q.name, qa1.*
FROM {quiz} as q
left join (select t1.*, count(qs.id) as totalQue,  ((t1.rightAns/count(qs.id))*100) as quizPercent 
from {quiz_slots} as qs
inner join (SELECT qa.userid, qa.uniqueid, qa.quiz, qua.questionusageid, (qa.timefinish-qa.timestart)
   AS timetaken, sum(case
   when qas.state='gradedwrong'
   then 1 else 0 end) as wrongAns,
   sum(case when qas.state ='gradedright'
   then 1 else 0 end) as rightAns
FROM {quiz_attempts} as qa
inner join {question_attempts} as qua on qua.questionusageid = qa.uniqueid and qa.attempt = 1 and qa.userid = $id
inner join {question_attempt_steps} As qas ON qas.questionattemptid = qua.id
where qua.responsesummary != 'null' group by qa.quiz) as t1  on qs.quizid = t1.quiz group by t1.quiz) as qa1 on q.id = qa1.quiz
where q.course in ($course)";

  $quizAttemptRecord = $DB->get_records_sql($quizAttempt);

  foreach ($quizAttemptRecord as $quiz) {
    $quizRow = array();
    $quizRow[] = $quiz->name;
    foreach ($courseId as $cid) {
      if ($quiz->course == $cid) {
        if ($quiz->quizpercent) {
          $quizRow[] = round($quiz->quizpercent) . '%';
        } else {
          $quizRow[] = 'Not Attempted';
        }
      } else {
        $quizRow[] = 'Not Attempted';
      }
    }
    $testTable->data[] = $quizRow;
  }
} else {
  $row = array();
  $row[] = "No Record Found.";
  $testTable->data[] = $row;
}

if (!empty($testTable)) {
  echo html_writer::start_tag('div', array('class' => 'no-overflow display-table attendeestable'));
  echo html_writer::table($testTable);
  echo html_writer::end_tag('div');
}


echo $OUTPUT->heading("Attendance", 4);

$attendance = new html_table();
$attendance->head = array();
$attendance->head[] = 'Month';
$attendance->head[] = 'Live Classes';
$attendance->head[] = 'Online Lectures';

if (!empty($course)) {
  $classCountSql = "SELECT count(b.id) as classCount, DATE_FORMAT(DATE(FROM_UNIXTIME(b.start_date)), '%Y-%m') as month
                    FROM `{braincert}` as b
                    WHERE b.course in ($course)
                    GROUP BY DATE_FORMAT(DATE(FROM_UNIXTIME(b.start_date)), '%Y-%m')";
  $classCountRecord = $DB->get_records_sql($classCountSql);

  $userCountSql = "SELECT count(b.id) as classacount, DATE_FORMAT(DATE(FROM_UNIXTIME(b.start_date)), '%Y-%m') as month
                  FROM `{braincert}` as b
                  JOIN {guru_braincert_user} as gbu on b.class_id=gbu.class_id
                  WHERE gbu.user_id=$id and b.course in ($course)
                  GROUP BY DATE_FORMAT(DATE(FROM_UNIXTIME(b.start_date)), '%Y-%m')";
  $userClassCountRecord = $DB->get_records_sql($userCountSql);
  
  $liveClassCountSql = "SELECT sum(glm.duration) as lectureSum
                    FROM `{guru_liveclass_mapping}` as glm
                    WHERE glm.course_id in ($course)";
  $liveClassCountRecord = $DB->get_record_sql($liveClassCountSql);
  
  $liveClassCountUserSql = "SELECT sum(gvv.view_time) as classacount, DATE_FORMAT(DATE(FROM_UNIXTIME(gvv.start_date)), '%Y-%m') as month
                  FROM `{guru_video_view}` as gvv
                  WHERE gvv.user_id=$id and gvv.course_id in ($course)
                  GROUP BY DATE_FORMAT(DATE(FROM_UNIXTIME(gvv.create_date)), '%Y-%m')";
  $liveClassCountUserRecord = $DB->get_record_sql($liveClassCountSql);
  $classLecture = number_format(($liveClassCountRecord->lecturesum/60)/24,2);
  if (!empty($classCountRecord)) {
    foreach ($classCountRecord as $classRecord) {
      foreach ($userClassCountRecord as $userRecord) {
        $attendanceCount = 0;
        if ($classRecord->month == $userRecord->month) {
          $attendanceCount = $userRecord->classacount;
        }
      }
      $row = array();
      $row[] = $classRecord->month;
      $row[] = $attendanceCount . '/' . $classRecord->classcount;
      $row[] = $attendanceCount . '/' . $classLecture." hours";
      $attendance->data[] = $row;
    }
  }
}

if (!empty($attendance)) {
  echo html_writer::start_tag('div', array('class' => 'no-overflow display-table attendeestable'));
  echo html_writer::table($attendance);
  echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
