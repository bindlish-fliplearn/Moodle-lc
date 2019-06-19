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
  $userid = $USER->id;
  $courseid = $_GET['courseid'];
  if(!$courseid){
    $courseid = 148;
  }
  require_login();
  $coursies = enrol_get_all_users_courses($id);
  $PAGE->set_url('/local/teacherdashboard/account.php');
  $PAGE->set_title("$USER->firstname $USER->lastname: " . get_string('accountheading', 'local_teacherdashboard'));
  $PAGE->set_heading("$USER->firstname $USER->lastname: " . get_string('accountheading', 'local_teacherdashboard'));
  $PAGE->set_pagelayout('incourse');
  $PAGE->navbar->add(get_string('accountheading', 'local_teacherdashboard'));
  echo $OUTPUT->header();
  echo $OUTPUT->heading("Teacher Dashboard", 4);

  echo html_writer::start_tag('div', array('class' => 'no-overflow'));
  echo html_writer::end_tag('a');
  echo html_writer::end_tag('div');

  $heads = getUserProfileHead();
  $teachertable = new html_table();
  $teachertable->head = array ();
  $teachertable->head[] = 'Name';
  $teachertable->head[] = 'Program';
  $teachertable->head[] = 'Attendance';
  foreach ($heads as $key => $value) {
    $teachertable->head[] = $value->name;
  }
  $teachertable->head[] = 'Guru Test Score';
  global $DB;
   $getSql = "SELECT DISTINCT ue.userid, u.firstname
          FROM mdl_enrol 
          AS me INNER JOIN mdl_user_enrolments 
          AS ue ON me.id = ue.enrolid 
          INNER JOIN mdl_user as u
          ON u.id = ue.userid
          INNER JOIN mdl_role_assignments as ra 
          ON ra.userid = u.id
          WHERE me.courseid = $courseid 
          AND ue.status = 0 AND ra.roleid = 5";
  $teacherrecord = $DB->get_records_sql($getSql);
  if(!empty($teacherrecord)) {
    foreach($teacherrecord as $data) {
        $id = $data->userid;
        $coursies = enrol_get_all_users_courses($id);
        $courseIds = [];
        $courseName = [];
        foreach ($coursies as $course) {
          $programName = $course->shortname . ', ';
          $courseIds[] = $course->id;
          $courseName[] = $course->fullname;
        }
        $programName = implode(', ',$courseName);
        $score = 0;
        $attendance = '';
        $score = getCourseScore($courseIds,$id);
        $userInfo = getUserProfile($id);
        $attendance =  getAttendence($id,$courseIds);

        
        $returnurl = new moodle_url('/local/studentdashboard/account.php', array('id'=>$id));
        $row   = array();
        $row[] = "<a href = '".$returnurl."' target = '_blank'>".$data->firstname."</a>";
        $row[] = $programName;
        $row[] = $attendance;
        foreach ($userInfo as $infokey => $userValue) {
           $row[] = $userValue->data ;
        }
        $row[] = $score;
        $teachertable->data[] = $row;
    }
  } else {
    $row = array();
    $row[] = "";
    $row[] = "";
    $row[] = "No Record Found.";
    foreach ($heads as $hkey => $hvalue) {
       $row[] = "";
    }
    $row[] = "";
    $teachertable->data[] = $row;
  }
  if (!empty($teachertable)) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow display-table attendeestable'));
    echo html_writer::table($teachertable);
    echo html_writer::end_tag('div');
  }
  echo $OUTPUT->footer();
