<?php

// This file is part of Wiziq - http://www.wiziq.com/
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
 * Library of interface functions and constants for module wiziq
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the wiziq specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_wiziq
 * @copyright  www.wiziq.com 
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
define('WIZIQ_TOMINUTES', 60);
define('WIZIQ_DURATION_TO_SECONDS', 60);
global $CFG;
require_once($CFG->dirroot . '/calendar/lib.php');
require_once('locallib.php');

/**
 * Defines the features that are supported by wiziq.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function wiziq_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS: return true;
        case FEATURE_GROUPINGS: return true;
        case FEATURE_GROUPMEMBERSONLY: return true;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE: return false;
        case FEATURE_GRADE_OUTCOMES: return false;
        case FEATURE_BACKUP_MOODLE2: return true;

        default: return null;
    }
}

/**
 * Saves a new instance of the wiziq into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $wiziq An object from the form in mod_form.php
 * 
 * @return int The id of the newly inserted wiziq record
 */
function wiziq_add_instance(stdClass $wiziq, mod_wiziq_mod_form $mform = null) {



    global $CFG, $DB, $USER;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
$coursecontext = context_course::instance($wiziq->course);

    
    $isadmin = get_admins();
   
    

    $wiziq->timecreated = time();

    if (property_exists($wiziq, 'schedule_for_now')) {
        if ($wiziq->schedule_for_now == true) {
            $wiziq->wiziq_datetime = $wiziq->timenow;
        }
    }



    if (property_exists($wiziq, 'scheduleforother')) {
        if ($wiziq->scheduleforother == true) {
            $userid = $wiziq->presenter_id;
            $userfirstname = $DB->get_field_select('user', 'firstname', 'id=' . $userid);
            $usersecondname = $DB->get_field_select('user', 'lastname', 'id=' . $userid);
            $username = $userfirstname . " " . $usersecondname;
            $email = $DB->get_field_select('user', 'email', 'id=' . $userid);
        }
    } else {
        $userid = $USER->id;
        $userfirstname = $USER->firstname;
        $usersecondname = $USER->lastname;
        $username = $userfirstname . " " . $usersecondname;
        $wiziq->presenter_id = $userid;
        $email = $USER->email;
    }
    if (0 != ($wiziq->groupingid)) {
        $eventtype = 'group';
    } else if (1 == $wiziq->course) {
        $eventtype = 'site';
    } else {
        $eventtype = 'course';
    }
    if (property_exists($wiziq, 'recording')) {
        if (1 == $wiziq->recording) {
            $recording = "true";
        } else {
            $recording = "false";
        }
    }

    if (property_exists($wiziq, 'create_recording')) {
        if (1 == $wiziq->create_recording) {
            $create_recording = "true";
        } else {
            $create_recording = "false";
        }
    }

  $emailnotification = $DB->get_record_sql('SELECT * FROM {config} WHERE name = ?', array('wiziq_emailsetting'));
  $eailnotify = $emailnotification->value;

    #-----Schedule class
    if ($wiziq->class_type == 1) {

        $class_duration = $wiziq->duration;
        $title = $wiziq->name;
        $presenter_id = $userid;
        $presenter_name = $username;
        $wiziq_datetime = wiziq_converttime($wiziq->wiziq_datetime, $wiziq->wiziq_timezone);
        $vc_language = $wiziq->vc_language;
        $courseid = $wiziq->course;
        $intro = $wiziq->intro;
        $wiziqtimezone = $wiziq->wiziq_timezone;
        $wiziqclass_id = "";
        $errormsg = "";
        $attribnode = "";

        
        wiziq_scheduleclass($wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $title, $presenter_id, $presenter_name, $wiziq_datetime, $wiziqtimezone, $class_duration, $vc_language, $recording, $courseid, $intro, $attribnode, $wiziqclass_id, $errormsg, $view_recording_url, $presenter_url);

        if ($attribnode == "ok") {

            $wiziq->class_id = $wiziqclass_id;
            $wiziq->class_status = "upcoming";
            $wiziq->class_timezone = $wiziqtimezone;
            $wiziq->recording_link = "";
            $wiziq->view_recording_link = $view_recording_url;
            $wiziq->recording_link_status = "0";
            $wiziq->class_master_id = '';
            $wiziq->presenter_url = $presenter_url;
            $wiziq->common_perma_attendee_url = '';
            $wiziq->attendee_limit = '0';
            $returnid = $DB->insert_record('wiziq', $wiziq);
          
        if($eailnotify == 1)
            {
              // send email
             $emails = get_email($wiziq->course, $presenter_id);
             $class_date = date("l, F d y h:i:s", strtotime($wiziq_datetime));
             $class_datetime = $class_date ." ". $wiziqtimezone ;
         
             $classlink1 =$CFG->wwwroot."/mod/wiziq/view.php?id=". $wiziq->coursemodule;
            foreach ($emails['user'] as $email) { //for user
                
                $txt = "Hi ".$email->firstname." , ";
                $txt .= "You have been invited to attend a live class. Refer to the details of the class below.";
                $txt .= "<h3>Class Details:</h3>";
                $txt .="<b>Instructor: </b>" .$emails['teacher']->email ;
$txt .="<br>";
                $txt .="<b>Title: </b>". $title;
$txt .="<br>";
                $txt .="<b>Date & Time:</b> ". $class_datetime;
$txt .="<br>";
                $txt .="<b><a href='$classlink1'> class link </a></b>";
$txt .="<br>";
                $txt .="<p>You will need a headset and a microphone for audio interaction.</p>";
$txt .="<br>";
                $txt .="Sincerely,";
$txt .="<br>";
                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                 $subject = "An invitation to join a live, online class";
                 $finalmessgae = html_to_text($txt);
                email_to_user($email, $USER, $subject, $finalmessgae);
                //mail($email->email, $subject, $txt, $headers);
            }
            $teacher_email = $emails['teacher']; //for teacher
                $txt = "Hi ".$teacher_email->email.", ";
                $txt .= "You have been invited to attend a live class. Refer to the details of the class below.";
                $txt .= "<h3>Class Details:</h3>";
                 $txt .="<b>Instructor: </b>" .$emails['teacher']->email ;
$txt .="<br>";
                $txt .="<b>Title: </b>". $title;
$txt .="<br>";
                $txt .="<b>Date & Time:</b> ". $class_datetime;
$txt .="<br>";

                $txt .="<b><a href='$classlink1'>  class link </a></b>";
$txt .="<br>";
                $txt .="<p>You will need a headset and a microphone for audio interaction.</p>";
$txt .="<br>";
                $txt .="Sincerely,";
$txt .="<br>";
                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                $subject = "An invitation to join a live, online class";
                $finalmessgae = html_to_text($txt);
                email_to_user($teacher_email, $USER, $subject, $finalmessgae);
           // mail($teacher_email, $subject, $txt, $headers);   #--end email
            }
            
            
            
            $event = new stdClass();
            $event->name = format_string($wiziq->name);
            $event->description = format_module_intro('wiziq', $wiziq, $wiziq->coursemodule);
            $event->courseid = $wiziq->course;
            $event->groupid = $wiziq->groupingid;
            $event->userid = $userid;
            $event->modulename = 'wiziq';
            $event->instance = $returnid;
            $event->eventtype = $eventtype;
            $event->timestart = $wiziq->wiziq_datetime;
            $event->timeduration = ($wiziq->duration)*WIZIQ_DURATION_TO_SECONDS;
            calendar_event::create($event);
            return $returnid;
        } else {
             $params = array(
            'objectid' => $wiziq->timecreated,
            'relateduserid' => $USER->id,
            'courseid' => $wiziq->course,
            'context' => $coursecontext,
            'other' => array(
            'error' => $errormsg
            )
        );
        $eventee = \mod_wiziq\event\wiziq_classadd::create($params);
        $eventee->trigger();
            print_error($errormsg);
        }
    }
    #-----Permanent Class-----
    elseif ($wiziq->class_type == 0) {
        $title = $wiziq->name;
        $presenter_id = $userid;
        $presenter_name = $username;
        $vc_language = $wiziq->vc_language;
        $courseid = $wiziq->course;
        $wiziqclass_id = "";
        $attendee_limit = optional_param('attendee_limit',null,PARAM_INT);
        $errormsg = "";
        $attribnode = "";
        $view_recording_url = "0";
        $wiziq_datetime = time();
        $wiziqtimezone = "0";
        $class_duration = "0";
        $intro = "0";

        wiziq_CreatePerma($wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $title, $presenter_id, $presenter_name, $vc_language, $create_recording, $attendee_limit, $courseid, $attribnode, $wiziqclass_id, $errormsg, $class_master_id, $common_perma_attendee_url, $view_recording_url, $wiziq_datetime, $wiziqtimezone, $class_duration, $intro);


        if ($attribnode == "ok") {
           
            $wiziq->class_id = $wiziqclass_id;
            $wiziq->class_status = "upcoming";
            $wiziq->class_timezone = "";
            $wiziq->recording_link = "";
            $wiziq->wiziq_datetime = "";
            $wiziq->duration = "";
            $wiziq->view_recording_link = '';
            $wiziq->recording_link_status = "0";
            $wiziq->class_master_id = $class_master_id;
            $wiziq->presenter_url = $view_recording_url;
            $wiziq->common_perma_attendee_url = $common_perma_attendee_url;
            $wiziq->attendee_limit = $attendee_limit;
            $wiziq->insescod = -1;


            $returnid = $DB->insert_record('wiziq', $wiziq);

            
            if($eailnotify == 1)
            {
              // send email
             $emails = get_email($wiziq->course, $presenter_id);
             $class_date = date("l, F d y h:i:s", strtotime($wiziq_datetime));
             $class_datetime = $class_date ." ". $wiziqtimezone ;
              $classlink1 =$CFG->wwwroot."/mod/wiziq/view.php?id=". $wiziq->coursemodule;
            foreach ($emails['user'] as $email) { //for user
                $txt = "Hi ".$email->firstname.", ";
                $txt .= "You have been invited to attend a live class. Refer to the details of the class below.";
                $txt .= "<h3>Class Details:</h3>";
                $txt .="Instructor: " .$emails['teacher']->email;                 
$txt .="<br>";
                $txt .="<b>Title: </b>". $title;
$txt .="<br>";
                $txt .="<b>Date & Time:</b> ".$class_datetime;
$txt .="<br>";
                $txt .="<b><a href='$classlink1'>class link</a></b>";
$txt .="<br>";
                $txt .="<p>You will need a headset and a microphone for audio interaction.</p>";
$txt .="<br>";
                $txt .="Sincerely,";
$txt .="<br>";
                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                 $subject = "An invitation to join a live, online class";
                 $finalmessgae = html_to_text($txt);
                 email_to_user($email, $USER, $subject, $finalmessgae);
            }
            $teacher_email = $emails['teacher']; //for teacher
            $txt = "Hi ".$teacher_email->email.", ";
                $txt .= "You have been invited to attend a live class. Refer to the details of the class below.";
                $txt .= "<h3>Class Details:</h3>";
                $txt .="Instructor: " .$emails['teacher']->email;
$txt .="<br>";
                $txt .="<b>Title: </b>". $title;
$txt .="<br>";
                $txt .="<b>Date & Time:</b> ".$class_datetime;
$txt .="<br>";
                $txt .="<b><a href='$classlink1'>class link</a></b>";
$txt .="<br>";
                $txt .="<p>You will need a headset and a microphone for audio interaction.</p>";
$txt .="<br>";
                $txt .="Sincerely,";
$txt .="<br>";
                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                 $subject = "An invitation to join a live, online class";
                $finalmessgae = html_to_text($txt);
                email_to_user($teacher_email, $USER, $subject, $finalmessgae);
            //mail($teacher_email, $subject, $txt, $headers); #--end email
            }
            
            $event = new stdClass();
            $event->name = format_string($wiziq->name);
            $event->description = format_module_intro('wiziq', $wiziq, $wiziq->coursemodule);
            $event->courseid = $wiziq->course;
            $event->groupid = $wiziq->groupingid;
            $event->userid = $userid;
            $event->modulename = 'wiziq';
            $event->instance = $returnid;
            $event->eventtype = $eventtype;
            // $event->timestart   = $wiziq->wiziq_datetime;
            $event->timestart = $wiziq_datetime;
            $event->timeduration = ($wiziq->duration)*WIZIQ_DURATION_TO_SECONDS;
            calendar_event::create($event);
            return $returnid;
        } else {
            $params = array(
            'objectid' => $wiziq->timecreated,
            'relateduserid' => $USER->id,
            'courseid' => $wiziq->course,
            'context' => $coursecontext,
            'other' => array(
            'error' => $errormsg
            )
        );
        $eventee = \mod_wiziq\event\wiziq_classadd::create($params);
        $eventee->trigger();
            print_error($errormsg);
        }
    }
    #-----Recurring Class-----
    else {
        $title = $wiziq->name;
        $start_time = wiziq_converttime($wiziq->wiziq_datetime, $wiziq->wiziq_timezone);
        $class_repeat_type = $wiziq->wiziq_recur_class_repeat_type;
        $class_occurrence = $wiziq->class_occurrence;
        $class_end_date = wiziq_converttime($wiziq->assesstimefinish, $wiziq->wiziq_timezone);
        $language_culture_name = $wiziq->vc_language;
        $courseid = $wiziq->course;
        $intro = $wiziq->intro;
        $presenter_id = $userid;
        $presenter_name = $username;
        $attribnode = '';
        $wiziqmasterclass_id = '';
        $wiziqclass_id = '';
        $errormsg = '';
        $time_zone = $wiziq->wiziq_timezone;
        $duration = $wiziq->duration;
        $days_of_week = $wiziq->days_of_week;
        $specific_week = $wiziq->specific_week;
        $monthly_date = $wiziq->monthly_date;
        $class_schedule = $wiziq->class_schedule;
        $select_monthly_repeat_type = $wiziq->select_monthly_repeat_type;

        wiziq_create_recuring($select_monthly_repeat_type, $class_schedule, $monthly_date, $days_of_week, $specific_week, $wiz_start_time, $wiziq_presenter_link, $time_zone, $duration, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $title, $start_time, $class_repeat_type, $class_occurrence, $class_end_date, $language_culture_name, $courseid, $intro, $presenter_id, $presenter_name, $recording, $attribnode, $wiziqmasterclass_id, $wiziqclass_id, $errormsg, $view_recording_url);
        if ($attribnode == "ok") {
           
            $i = 0;
            $count = count($wiziqclass_id['0']);
            foreach ($wiziqclass_id['0'] as $wiziqclass_id) {

                $wiziq->class_id = (string) $wiziqclass_id;
                $wiziq->class_status = "upcoming";
                $wiziq->class_timezone = $wiziq->wiziq_timezone;
                $wiziq->recording_link = "";
                $wiziq->view_recording_link = (string) $view_recording_url['0'][$i];
                $wiziq->recording_link_status = "0";
                $wiziq->class_master_id = (string) $wiziqmasterclass_id;
                $wiziq->presenter_url = (string) $wiziq_presenter_link[$i];
                $wiziq->common_perma_attendee_url = '';
                $wiziq->attendee_limit = '0';
                $wiziq->duration = $wiziq->duration;
                $wiziq->days_of_week = $days_of_week;
                $wiziq->specific_week = $specific_week;
                $wiziq->wiziq_datetime = strtotime(date("Y-m-d h:i:s", strtotime($wiz_start_time[$i])));
                $wiziq->wiziq_recur_class_repeat_type = $wiziq->wiziq_recur_class_repeat_type;
                $wiziq->class_schedule = $wiziq->class_schedule;
                $wiziq->class_occurrence = $wiziq->class_occurrence;
                $wiziq->assesstimefinish = $wiziq->assesstimefinish;
                $wiziq->select_monthly_repeat_type = $wiziq->select_monthly_repeat_type;
                $returnid = $DB->insert_record('wiziq', $wiziq, $returnid = true, $bulk = true);

                
                if($eailnotify == 1)
            {
                 // send email
             $emails = get_email($wiziq->course, $wiziq->presenter_id);
             $class_date = date("l, F d y h:i:s", strtotime($wiziq_datetime));
             $class_datetime = $class_date ." ". $wiziqtimezone ;
              $classlink1 =$CFG->wwwroot."/mod/wiziq/view.php?id=". $wiziq->coursemodule;
            foreach ($emails['user'] as $email) { //for user
                  $txt = "Hi ".$email->firstname.", ";
                $txt .= "You have been invited to attend a live class. Refer to the details of the class below.";
                $txt .= "<h3>Class Details:</h3>";
                $txt .="Instructor: " .$emails['teacher']->email;
$txt .="<br>";
                $txt .="<b>Title: </b>". $title;
$txt .="<br>";
                $txt .="<b>Date & Time:</b> ".$class_datetime;
$txt .="<br>";
                $txt .="<b><a href='$classlink1'>class link</a></b>";
$txt .="<br>";
                $txt .="<p>You will need a headset and a microphone for audio interaction.</p>";
$txt .="<br>";
                $txt .="Sincerely,";
$txt .="<br>";
                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                 $subject = "An invitation to join a live, online class";
                $finalmessgae = html_to_text($txt);
                 email_to_user($email, $USER, $subject, $finalmessgae);
            }
            $teacher_email = $emails['teacher']; //for teacher
           $txt = "Hi ".$teacher_email->email.", ";
                $txt .= "You have been invited to attend a live class. Refer to the details of the class below.";
                $txt .= "<h3><a href='$classlink1'>class link</a></h3>";
                $txt .="Instructor: " .$emails['teacher']->email;
$txt .="<br>";
                $txt .="<b>Title: </b>". $title;
$txt .="<br>";
                $txt .="<b>Date & Time:</b> ".$class_datetime;
$txt .="<br>";
                $txt .="<b>class link</b>";
$txt .="<br>";
                $txt .="<p>You will need a headset and a microphone for audio interaction.</p>";
$txt .="<br>";
                $txt .="Sincerely,";
$txt .="<br>";
                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                 $subject = "An invitation to join a live, online class";
                 $finalmessgae = html_to_text($txt);
                 email_to_user($teacher_email, $USER, $subject, $finalmessgae);
                // mail($teacher_email, $subject, $txt, $headers); #---end email
            }
            
                $event = new stdClass();
                $event->name = format_string($wiziq->name);
                $event->description = format_module_intro('wiziq', $wiziq, $wiziq->coursemodule);
                $event->courseid = $wiziq->course;
                $event->groupid = $wiziq->groupingid;
                $event->userid = $userid;
                $event->modulename = 'wiziq';
                $event->instance = $returnid;
                $event->eventtype = $eventtype;
                $event->timestart = $wiziq->wiziq_datetime;
                $event->timeduration = ($wiziq->duration)*WIZIQ_DURATION_TO_SECONDS;
                calendar_event::create($event);
                if ($i < $count - 1) {
                    $cm = new stdClass();
                    $cm->course = $wiziq->course;
                    $cm->module = $wiziq->module;
                    $cm->instance = $returnid;
                    $cm->added = $wiziq->timecreated;
                    $cm->section = $DB->get_field('course_sections', 'id', array('course' => $wiziq->course, 'section' => $wiziq->section));
                    $cmid = $DB->insert_record('course_modules', $cm, $cmid = true, $bulk = true);

                    $section = new stdClass();
                    $section_id = $DB->get_field('course_sections', 'id', array('course' => $wiziq->course, 'section' => $wiziq->section));
                    $sequence = $DB->get_field('course_sections', 'sequence', array('id' => $section_id));

                    $section->id = $section_id;
                    if (!empty($sequence)) {
                        $section_sequence = $sequence . ',' . $cmid;
                        $section->sequence = $section_sequence;
                        $secid = $DB->update_record('course_sections', $section, $secid = true, $bulk = true);
                    } else {
                        $section->sequence = $cmid;
                        $secid = $DB->update_record('course_sections', $section, $secid = true, $bulk = true);
                    }
                }
                $i++;
            }
            return $returnid;
        } else {
             $params = array(
            'objectid' => $wiziq->timecreated,
            'relateduserid' => $USER->id,
            'courseid' => $wiziq->course,
            'context' => $coursecontext,
            'other' => array(
            'error' => $errormsg
            )
        );
        $eventee = \mod_wiziq\event\wiziq_classadd::create($params);
        $eventee->trigger();
            print_error($errormsg);
        }
    }
}

/**
 * Updates an instance of the wiziq in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $wiziq An object from the form in mod_form.php
 * 
 * @return boolean Success/Fail
 */
function wiziq_update_instance($wiziq) {


    global $CFG, $DB, $USER;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $class_id = $wiziq->class_id;
    $wiziq->lasteditorid = $USER->id;
     $coursecontext = context_course::instance($wiziq->course);
     
    $isadmin = get_admins();
    if (property_exists($wiziq, 'insescod')) {
        $session = $wiziq->insescod;
    }


    if (!isset($class_id)) {

        wiziq_get_data_by_sessioncode($wiziq->course, $session, $class_id, $wiziq->id, $presenter_id, $presenter_name, $presenter_url, $start_time, $time_zone, $create_recording, $status, $language_culture_name, $duration, $recording_url);
    } else {

        wiziq_get_data($wiziq->course, $class_id, $class_master_id, $presenter_id, $presenter_name, $presenter_url, $start_time, $time_zone, $create_recording, $status, $language_culture_name, $duration, $recording_url);
    }

    $class_status = ltrim(rtrim($status));
    if (($class_status) != 'expired') {

        $wiziq->timemodified = time();
        $wiziq->id = $wiziq->instance;


        if ($wiziq->class_type != 1) {
            if (!$class_master_id = $DB->get_field('wiziq', 'class_master_id', array('id' => $wiziq->id))) {
                return false;
            }
        } else {
            if (!$class_id = $DB->get_field('wiziq', 'class_id', array('id' => $wiziq->id))) {
                return false;
            }
        }


        if (property_exists($wiziq, 'schedule_for_now')) {
            if ($wiziq->schedule_for_now == true) {
                $wiziq->wiziq_datetime = $wiziq->timenow;
            }
        }
        if (property_exists($wiziq, 'scheduleforother')) {
            if ($wiziq->scheduleforother == true) {
                $userid = $wiziq->presenter_id;
                $userfirstname = $DB->get_field_select('user', 'firstname', 'id=' . $userid);
                $usersecondname = $DB->get_field_select('user', 'lastname', 'id=' . $userid);
                $username = $userfirstname . " " . $usersecondname;
            }
        } else if (property_exists($wiziq, 'scheduleforself')) {
            if ($wiziq->scheduleforself == true) {
                $userid = $USER->id;
                $userfirstname = $DB->get_field_select('user', 'firstname', 'id=' . $userid);
                $usersecondname = $DB->get_field_select('user', 'lastname', 'id=' . $userid);
                $username = $userfirstname . " " . $usersecondname;
            }
        } else {
            $userid = $DB->get_field('wiziq', 'presenter_id', array('id' => $wiziq->id));
            $userfirstname = $DB->get_field_select('user', 'firstname', 'id=' . $userid);
            $usersecondname = $DB->get_field_select('user', 'lastname', 'id=' . $userid);
            $username = $userfirstname . " " . $usersecondname;
            $wiziq->presenter_id = $userid;
        }


        if (0 != ($wiziq->groupingid)) {
            $eventtype = 'group';
        } else if (1 == $wiziq->course) {
            $eventtype = 'site';
        } else {
            $eventtype = 'course';
        }
        if (1 == $wiziq->recording) {
            $recording = "true";
        } else {
            $recording = "false";
        }

          $emailnotification = $DB->get_record_sql('SELECT * FROM {config} WHERE name = ?', array('wiziq_emailsetting'));
  $eailnotify = $emailnotification->value;
        
        if ($wiziq->class_type == 0) {
            $alldata = $DB->get_record_sql('SELECT * FROM {wiziq} WHERE id = ?', array($wiziq->id));
          
            $class_master_id = $alldata->class_master_id;



            $title = $wiziq->name;
            $presenter_id = $alldata->presenter_id;
            $presenter_name = $username;
            $vc_language = $wiziq->language_culture_name;
            $courseid = $wiziq->course;
            $presenter_url = $alldata->presenter_url;
            $common_perma_attendee_url = $alldata->common_perma_attendee_url;
            $insescod = $alldata->insescod;
            $vc_language = $alldata->vc_language;
            $recording = $alldata->recording;
            $attendee_limit = $wiziq->attendee_limit;
            $wiziqclass_id = "";
            $errormsg = "";
            $attribnode = "";
            $view_recording_url = "0";
            $wiziq_datetime = time();
            $wiziqtimezone = "0";
            $class_duration = "0";
            $intro = "0";


            wiziq_modifypermaclass($wiziq->course, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $title, $presenter_id, $presenter_name, $vc_language, $attendee_limit, $create_recording, $courseid, $attribnode, $wiziqclass_id, $errormsg, $class_master_id, $common_perma_attendee_url, $view_recording_url, $wiziq_datetime, $wiziqtimezone, $class_duration, $intro);


            if ($attribnode == "ok") {

                if($eailnotify == 1)
            {
                // send email
                $emails = get_email($wiziq->course, $presenter_id);
                $class_date = date("l, F d y h:i:s", strtotime($wiziq_datetime));
                $class_datetime = $class_date ." ". $wiziqtimezone ;
                $classlink1 =$CFG->wwwroot."/mod/wiziq/view.php?id=". $wiziq->coursemodule;
                foreach ($emails['user'] as $email) { //for user
                $txt = "Hi ".$email->firstname.", <br>";
                 if($wiziq_datetimeold != strtotime($wiziq_datetime))
                            {
                               $txt .="<p>" . $isadmin[2]->firstname ." has rescheduled the class on " .$title ." to " .$class_datetime . "You may use the same class link to enter the classroom at its changed date and time. </p>" ;
                               $txt .="<a href='$classlink1'>Enter the class</a> <br>";
                               $txt .="Regards, <br>";
                               $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                               $subject = $title . "class rescheduled";
               } else {               
                               $txt .= "<p>" . $isadmin[2]->firstname ." has updated the class on " .$title ." to " .$class_datetime . "You may use the same class link to enter the classroom at its changed date and time. </p>" ;
                               $txt .="<a href='$classlink1'>Enter the class</a> <br>";
                               $txt .="Regards, <br>";
                               $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                               $subject = $isadmin[2]->firstname ." ".$isadmin[2]->lastname . " has updated class";
                }
                 $finalmessgae = html_to_text($txt);
                 email_to_user($email, $USER, $subject, $finalmessgae);
                 
                }
                $teacher_email = $emails['teacher']; //for teacher
                $txt = "Hi ".$teacher_email->email.", <br>";
                   if($wiziq_datetimeold != strtotime($wiziq_datetime))
                          {
                                $txt .= "<p>" .$isadmin[2]->firstname ." has rescheduled the class on " .$title ." to " .$class_datetime . "You may use the same class link to enter the classroom at its changed date and time. <p>" ;
                               $txt .="<a href='$classlink1'>Enter the class</a> <br>";
                                $txt .="Regards, <br>";
                                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                                $subject = $title .  "class rescheduled";
               } else{
                                $txt .= "<p>" . $isadmin[2]->firstname ." has updated the class on " .$title ." to " .$class_datetime . "You may use the same class link to enter the classroom at its changed date and time. </p>" ;
                                 $txt .="<a href='$classlink1'>Enter the class</a> <br>";
                                $txt .="Regards, <br>";
                                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                                $subject = $isadmin[2]->firstname ." ".$isadmin[2]->lastname . " has updated class";                   
               }
                 $finalmessgae = html_to_text($txt);
                 email_to_user($teacher_email, $USER, $subject, $finalmessgae);
            }
            
                # You may have to add extra stuff in here #

                $wiziq->class_id = $wiziqclass_id;
                $wiziq->class_status = "upcoming";
                $wiziq->class_timezone = "";
                $wiziq->recording_link = "";
                $wiziq->wiziq_datetime = "";
                $wiziq->duration = "";
                $wiziq->view_recording_link = '';
                $wiziq->recording_link_status = "0";
                $wiziq->class_master_id = $class_master_id;
                $wiziq->presenter_url = $view_recording_url;
                $wiziq->common_perma_attendee_url = $common_perma_attendee_url;
                $wiziq->insescod = -1;
                $wiziq->introformat = '0';
                $wiziq->title = $title;
                $wiziq->attendee_limit = $attendee_limit;
                $wiziq->presenter_url = $presenter_url;

                $DB->update_record('wiziq', $wiziq);
                $event = new stdClass();
                $event->id = $DB->get_field('event', 'id', array('modulename' => 'wiziq', 'instance' => $wiziq->id));

                if ($event->id) {

                    $event->name = format_string($wiziq->name);
                    $event->description = format_module_intro('wiziq', $wiziq, $wiziq->coursemodule);
                    $event->courseid = $wiziq->course;
                    $event->groupid = $wiziq->groupingid;
                    $event->userid = $userid;
                    $event->modulename = 'wiziq';
                    $event->eventtype = $eventtype;
                    $event->timestart = $wiziq->wiziq_datetime;
                    $event->timeduration = ($wiziq->duration)*WIZIQ_DURATION_TO_SECONDS;
                    $calendarevent = calendar_event::load($event->id);
                    $calendarevent->update($event);
                    return true;
                } else {
                    print_error($errormsg);
                }
            }
        } else {

            $alldata = $DB->get_record_sql('SELECT * FROM {wiziq} WHERE id = ?', array($wiziq->id));
          
             $wiziq_datetimeold = $alldata->wiziq_datetime;
             $wiziq_timezoneold = $alldata->class_timezone;
             
             
            $class_duration = $wiziq->duration;
            $title = $wiziq->name;
            $presenter_id = $userid;
            $presenter_name = $username;
            $wiziq_datetime = wiziq_converttime($wiziq->wiziq_datetime, $wiziq->wiziq_timezone);
            $vc_language = $wiziq->vc_language;
            $intro = $wiziq->intro;
            $wiziqtimezone = $wiziq->wiziq_timezone;
            $wiziqclass_id = "";
            $errormsg = "";
            $attribnode = "";

                         
            wiziq_modifyclass($wiziq->course, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $class_id, $title, $presenter_id, $presenter_name, $wiziq_datetime, $wiziqtimezone, $class_duration, $vc_language, $recording, $intro, $attribnode, $wiziqclass_id, $errormsg);

            if ($attribnode == "ok") {
                if($eailnotify == 1)
            {
                // send email
                $emails = get_email($wiziq->course, $presenter_id);
                $class_date = date("l, F d y h:i:s", strtotime($wiziq_datetime));
                $class_datetime = $class_date ." ". $wiziqtimezone ;
                $classlink1 =$CFG->wwwroot."/mod/wiziq/view.php?id=". $wiziq->coursemodule;
                foreach ($emails['user'] as $email) { //for user
                 
                     $txt = "Hi ".$email->firstname.", <br>";
                         if($wiziq_datetimeold != strtotime($wiziq_datetime))
                            {
                               $txt .="<p>" . $isadmin[2]->firstname ." has rescheduled the class on " .$title ." to " .$class_datetime . "You may use the same class link to enter the classroom at its changed date and time. </p>" ;
                               $txt .="<a href='$classlink1'>Enter the class</a> <br>";
                               $txt .="Regards, <br>";
                               $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                               $subject = $title . "class rescheduled";
               } else {               
                               $txt .= "<p>" . $isadmin[2]->firstname ." has updated the class on " .$title ." to " .$class_datetime . "You may use the same class link to enter the classroom at its changed date and time. </p>" ;
                               $txt .="<a href='$classlink1'>Enter the class</a> <br>";
                               $txt .="Regards, <br>";
                               $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                               $subject = $isadmin[2]->firstname ." ".$isadmin[2]->lastname . " has updated class";
                }
                 $finalmessgae = html_to_text($txt);
                 email_to_user($email, $USER, $subject, $finalmessgae);
                }
                $teacher_email = $emails['teacher']; //for teacher
                 $txt = "Hi ".$teacher_email->email.", <br>";
                        if($wiziq_datetimeold != strtotime($wiziq_datetime))
                          {
                                $txt .= "<p>" .$isadmin[2]->firstname ." has rescheduled the class on " .$title ." to " .$class_datetime . "You may use the same class link to enter the classroom at its changed date and time. <p>" ;
                                $txt .="<a href='$classlink1'>Enter the class</a> <br>";
                                $txt .="Regards, <br>";
                                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                                $subject = $title .  "class rescheduled";
               } else{
                                $txt .= "<p>" . $isadmin[2]->firstname ." has updated the class on " .$title ." to " .$class_datetime . "You may use the same class link to enter the classroom at its changed date and time. </p>" ;
                                $txt .="<a href='$classlink1'>Enter the class</a> <br>";
                                $txt .="Regards, <br>";
                                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                                $subject = $isadmin[2]->firstname ." ".$isadmin[2]->lastname . " has updated class";                   
               }
                 $finalmessgae = html_to_text($txt);
                 email_to_user($teacher_email, $USER, $subject, $finalmessgae);
            }
                # You may have to add extra stuff in here #
                $wiziq->class_status = "upcoming";
                $wiziq->recording_link = "";
                $wiziq->recording_link_status = "0";
                $wiziq->view_recording_link = $recording_url;
                $wiziq->class_timezone = $wiziq->wiziq_timezone;


                $DB->update_record('wiziq', $wiziq);
                $event = new stdClass();
                $event->id = $DB->get_field('event', 'id', array('modulename' => 'wiziq', 'instance' => $wiziq->id));

                if ($event->id) {

                    $event->name = format_string($wiziq->name);
                    $event->description = format_module_intro('wiziq', $wiziq, $wiziq->coursemodule);
                    $event->courseid = $wiziq->course;
                    $event->groupid = $wiziq->groupingid;
                    $event->userid = $userid;
                    $event->modulename = 'wiziq';
                    $event->eventtype = $eventtype;
                    $event->timestart = $wiziq->wiziq_datetime;
                    $event->timeduration = ($wiziq->duration)*WIZIQ_DURATION_TO_SECONDS;
                    $calendarevent = calendar_event::load($event->id);
                    $calendarevent->update($event);
                    return true;
                } else {
                    print_error($errormsg);
                }
            }
        }
    } else {
        print_error("error in case of expired class");
    }
}

/**
 * Removes an instance of the wiziq from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function wiziq_delete_instance($id) {


    global $DB;
 
    $isadmin = get_admins();
    if (!$wiziq = $DB->get_record('wiziq', array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #
    if (!$events = $DB->get_records('event', array('modulename' => 'wiziq', 'instance' => $wiziq->id))) {
        return false;
    }

  $emailnotification = $DB->get_record_sql('SELECT * FROM {config} WHERE name = ?', array('wiziq_emailsetting'));
          $eailnotify = $emailnotification->value;

    foreach ($events as $event) {
        $event = calendar_event::load($event);
        $event->delete();
    }

    if (!$DB->delete_records('wiziq', array('id' => $wiziq->id))) {
        return false;
    }
    if ($wiziq->insescod == -1) {

        if (!isset($wiziq->class_master_id)) {
            wiziq_get_data_by_sessioncode_delete($wiziq->id, $wiziq->course, $wiziq->insescod, $class_id);
            if (isset($class_master_id)) {
                $wiziq->class_master_id = $class_master_id;
            }
        }
        wiziq_delete_permaclass($wiziq->course, $wiziq->class_master_id);
       if($eailnotify == 1)
        {
        // send email
        $emails = get_email($wiziq->course, $wiziq->presenter_id);
        $class_date = date("l, F d y h:i:s", strtotime($wiziq_datetime));
        $class_datetime = $class_date ." ". $wiziqtimezone ;
        foreach ($emails['user'] as $email) { //for user
                $txt = $isadmin[2]->firstname ." has deleted the class on ". $wiziq->name ." scheduled for " .$class_datetime;
                $txt .="<br>";
                $txt .="Regards, <br>";
                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                $subject = $wiziq->name . " class Deleted" ;
                $finalmessgae = html_to_text($txt);
                email_to_user($email, $USER, $subject, $finalmessgae);
        }
        $teacher_email = $emails['teacher']; //for teacher
                $txt = $isadmin[2]->firstname ." has deleted the class on ". $wiziq->name ." scheduled for " .$class_datetime;
                $txt .="<br>";
                $txt .="Regards, <br>";
                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                $subject = $wiziq->name . " class Deleted" ;
                $finalmessgae = html_to_text($txt);
                email_to_user($teacher_email, $USER, $subject, $finalmessgae);
    }} else {

        if (!isset($wiziq->class_id)) {
            wiziq_get_data_by_sessioncode_delete($wiziq->id, $wiziq->course, $wiziq->insescod, $class_id);
            if (isset($class_id)) {
                $wiziq->class_id = $class_id;
            }
        }
        wiziq_delete_class($wiziq->course, $wiziq->class_id);
        if($eailnotify == 1)
        {        
// send email
        $emails = get_email($wiziq->course, $wiziq->presenter_id);
        $class_date = date("l, F d y h:i:s", strtotime($wiziq_datetime));
             $class_datetime = $class_date ." ". $wiziqtimezone ;
        foreach ($emails['user'] as $email) { //for user
                $txt = $isadmin[2]->firstname ." has deleted the class on ". $wiziq->name ." scheduled for " .$class_datetime;
                $txt .="<br>";
                $txt .="Regards, <br>";
                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                $subject = $wiziq->name . " class Deleted" ;
                $finalmessgae = html_to_text($txt);
                email_to_user($email, $USER, $subject, $finalmessgae);
        }
        $teacher_email = $emails['teacher']; //for teacher
                $txt = $isadmin[2]->firstname ." has deleted the class on ".  $wiziq->name ." scheduled for " .$class_datetime;
                $txt .="<br>";
                $txt .="Regards, <br>";
                $txt .= $isadmin[2]->firstname ." ".$isadmin[2]->lastname;
                 $subject = $wiziq->name . " class Deleted" ;
                 $finalmessgae = html_to_text($txt);
                email_to_user($teacher_email, $USER, $subject, $finalmessgae);
    }}



    return true;
}

/**
 * Removes an instance of the wiziq from the course when course is deleted
 *
 * Called by moodle itself to delete the activities regarding the
 * wiziq in the course.
 *
 * @param int $course Id of the module instance
 * @param string $feedback feedback of the process.
 * @return boolean Success/Failure
 */
/* function wiziq_delete_course($course, $feedback = true) {
    return true;
}*/

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 * */
function wiziq_cron() {
    return true;
}
