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
global $USER;
$id = $USER->id;
require_login();

$coursies = enrol_get_all_users_courses($id);

$schoolCity = "";
$class10Marks = "";
$class11Marks = "";
$studentAspirations = "";
$parentAspirations = "";
$studentInterest = "";
$teacherRemark = "";
if (isset($USER->profile['schoolCity']) && !empty($USER->profile['schoolCity'])) {
  $schoolCity = $USER->profile['schoolCity'];
} if (isset($USER->profile['class10Marks']) && !empty($USER->profile['class10Marks'])) {
  $class10Marks = $USER->profile['class10Marks'];
} if (isset($USER->profile['class11Marks']) && !empty($USER->profile['class11Marks'])) {
  $class11Marks = $USER->profile['class11Marks'];
} if (isset($USER->profile['studentAspirations']) && !empty($USER->profile['studentAspirations'])) {
  $studentAspirations = $USER->profile['studentAspirations'];
} if (isset($USER->profile['parentAspirations']) && !empty($USER->profile['parentAspirations'])) {
  $parentAspirations = $USER->profile['parentAspirations'];
} if (isset($USER->profile['studentInterest']) && !empty($USER->profile['studentInterest'])) {
  $studentInterest = $USER->profile['studentInterest'];
} if (isset($USER->profile['teacherRemark']) && !empty($USER->profile['teacherRemark'])) {
  $teacherRemark = $USER->profile['teacherRemark'];
}


$courseId = [];
$courseName = [];
foreach ($coursies as $course) {
  $programName .= $course->shortname . ', ';
  $courseId[] = $course->id;
  $courseName[] = $course->fullname;
}

$programName = rtrim($programName, ', ');

$PAGE->set_url('/local/sudenttdashboard/account.php');
$PAGE->set_title("$USER->firstname $USER->lastname: " . get_string('accountheading', 'local_studentdashboard'));
$PAGE->set_heading("$USER->firstname $USER->lastname: " . get_string('accountheading', 'local_studentdashboard'));

$PAGE->set_pagelayout('incourse');


$PAGE->navbar->add(get_string('accountheading', 'local_studentdashboard'));

echo $OUTPUT->header();
echo $OUTPUT->heading("$USER->firstname $USER->lastname: " . get_string('accountheading', 'local_studentdashboard'));

echo $OUTPUT->heading("Profile Summary", 4);

$studenttable = new html_table();
$studenttable->data[] = ['Name', $USER->firstname . ' ' . $USER->lastname];
$studenttable->data[] = ['School/City', $schoolCity];
$studenttable->data[] = ['Program', $programName];
$studenttable->data[] = ['Class 10 Marks', $class10Marks];
$studenttable->data[] = ['Class 11 Marks', $class11Marks];
$studenttable->data[] = ['Student Aspirations', $studentAspirations];
$studenttable->data[] = ['Parent Aspirations', $parentAspirations];
$studenttable->data[] = ['Student Interest and Activities', $studentInterest];
$studenttable->data[] = ['Teacher Remark', $teacherRemark];

if (!empty($studenttable)) {
  echo html_writer::start_tag('div', array('class' => 'no-overflow display-table attendeestable'));
  echo html_writer::table($studenttable);
  echo html_writer::end_tag('div');
}

echo $OUTPUT->heading();
echo $OUTPUT->heading("PTM Remarks", 4);

echo html_writer::start_tag('div', array('class' => 'no-overflow'));
echo html_writer::start_tag('a', array('class' => 'btn btn-success', 'src' => '#0', 'onClick' => "showPtmPopup('1  ',$id,'10');"));
echo "Add PTM Remark";
echo html_writer::end_tag('a');
echo html_writer::end_tag('div');


$ptmtable = new html_table();
$ptmtable->head = array();
$ptmtable->head[] = 'Date';
$ptmtable->head[] = 'Teacher Remark for Student/Parent';
$ptmtable->head[] = 'Parent Feedback';

$ptmRecord = $DB->get_records('guru_user_ptm', array('user_id' => $id));
if (!empty($ptmRecord)) {
  foreach ($ptmRecord as $remark) {
    $row = array();
    $row[] = $remark->ptm_date;
    $row[] = $remark->teacher_remark;
    $row[] = $remark->parent_feedback;
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

echo $OUTPUT->heading();
echo $OUTPUT->heading("Test Performance", 4);

$testTable = new html_table();
$testTable->head = array();
$testTable->head[] = 'Test';
foreach ($courseName as $name) {
  $testTable->head[] = $name;
}

$course = implode(",", $courseId);
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
        $quizRow[] = round($quiz->quizpercent).'%';
      } else {
        $quizRow[] = 'No Attempted';
      }
    } else {
      $quizRow[] = 'No Attempted';
    }
  }
  $testTable->data[] = $quizRow;
}

if (!empty($testTable)) {
  echo html_writer::start_tag('div', array('class' => 'no-overflow display-table attendeestable'));
  echo html_writer::table($testTable);
  echo html_writer::end_tag('div');
}


echo $OUTPUT->heading();
echo $OUTPUT->heading("Attendance", 4);

$attendance = new html_table();
$attendance->head = array();
$attendance->head[] = 'Month';
$attendance->head[] = 'Live Classes';
$attendance->head[] = 'Online Lectures';

if (!empty($attendance)) {
  echo html_writer::start_tag('div', array('class' => 'no-overflow display-table attendeestable'));
  echo html_writer::table($attendance);
  echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
