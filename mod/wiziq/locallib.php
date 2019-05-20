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
 * Internal library of functions for module wiziq
 *
 * All the wiziq specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_wiziq
 * @copyright  www.wiziq.com 
 * @author     dinkar@wiziq.com 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
define('WIZIQ_DEFAULT_PAGESIZE', 20);
/**
 * Makes a call to api for scheduling class after authentication from authbase file.
 *
 * @param string $wiziq_secretacesskey secret access key generated during wiziq download.
 * @param string $wiziq_access_key the access key generated during wiziq download.
 * @param string $wiziq_webserviceurl url to ping for xml return for scheduling class.
 * @param string $title title of the class scheduled.
 * @param int $presenter_id the id of the presenter who will be present for the class.
 * @param string $presenter_name name of the presenter.
 * @param int $wiziq_datetime the time at which the class is scheduled.
 * @param string $wiziqtimezone the timezone for which class is scheduled.
 * @param int $class_duration duration in minutes for scheduling class.
 * @param string $vc_language the language in which the class will be launched.
 * @param string $recording wheteher recording is opted or not.
 * @param integer $courseid id of the course for which the class is scheduled.
 * @param string $intro description of the class scheduled.
 * @param string $attribnode the attribute is ok then the class is scheduled.
 * @param integer $wiziqclass_id class_id returned that is stored in wiziq table.
 * @param string $errormsg error message in case there is some error in scheduling class.
 * @param string $view_recording_url recording link for viewing the class.
 */
function wiziq_scheduleclass($wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $title, $presenter_id, $presenter_name, $wiziq_datetime, $wiziqtimezone, $class_duration, $vc_language, $recording, $courseid, $intro, &$attribnode, &$wiziqclass_id, &$errormsg, &$view_recording_url , &$presenter_url) {
    global $CFG, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl , $USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "create";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["course_id"] = $courseid;
    $requestparameters["title"] = $title; //Required
    $requestparameters["description"] = $intro;
    $requestparameters["presenter_id"] = $presenter_id;
    $requestparameters["presenter_name"] = $presenter_name;
    $requestparameters["start_time"] = $wiziq_datetime;
    $requestparameters["duration"] = $class_duration; //optional
    $requestparameters["time_zone"] = $wiziqtimezone; //"Asia/Kolkata"; //optional
    $requestparameters["create_recording"] = $recording; //optional
    $requestparameters["return_url"] = ""; //optional
    $requestparameters["status_ping_url"] = ""; //optional
    $requestparameters["language_culture_name"] = $vc_language;
    $requestparameters["app_version"] = $CFG->release;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=create', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);
       // echo "<pre>";
       // print_r($objdom);
        $attribnode = (string) $objdom->attributes();
        if ($attribnode == "ok") {
            $class_detaial = $objdom->create->class_details;
            $wiziqclass_id = (string) $class_detaial->class_id;
            $view_recording_url = (string) $class_detaial->recording_url;
            $presenter_url = (string) $class_detaial->presenter_list->presenter->presenter_url;
            $params = array(
                'objectid' => $wiziqclass_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => ''
                )
            );
            $event = \mod_wiziq\event\wiziq_classadd::create($params);
            $event->trigger();
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $error_code = (string)$objdom->error->attributes()->$code;
            $error_msg = (string)$objdom->error->attributes()->$att; //can be used while debug
            $errormsg = $error_code." ".$error_msg;
            $params = array(
                'objectid' => $courseid,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classadd::create($params);
            $event->trigger();
        }//end if
    } catch (Exception $e) {
        // in case no xml is returned
        $errormsg = $e->getMessage() . "<br/>" . get_string('errorinservice', 'wiziq');
        $params = array(
                'objectid' => $courseid,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
        );
        $event = \mod_wiziq\event\wiziq_classadd::create($params);
        $event->trigger();
    }
}

//end function

/**
 * This function generates teachers list that is displayed for 
 * admin if he wants to schedule class for another teacher in wiziq mod_form.
 *
 * @param integer $courseid id of the course for which class is scheduled.
 *
 * @return string the teacherlist created.
 */
function wiziq_getteacherdetail($courseid) {
    global $CFG, $DB , $USER;
      $sql  = "SELECT u.id, u.username FROM {course} c ";
    $sql .= "JOIN {context} ct ON c.id = ct.instanceid ";
    $sql .= "JOIN {role_assignments} ra ON ra.contextid = ct.id ";
    $sql .= "JOIN {user} u ON u.id = ra.userid ";
    $sql .= "JOIN {role} r ON r.id = ra.roleid ";
    $sql .= "WHERE (archetype ='editingteacher' OR name ='teacher') AND c.id = $courseid";
    $teacherlist = $DB->get_records_sql($sql);
    return $teacherlist;
}

/**
 * Generates the teachers list(in wiziq mod_form) that is displayed for 
 * administrator if he wants to schedule class for another teacher.
 *
 * @return array $vclang the virtual classroom language list.
 */
function wiziq_languagexml() {
    global $CFG;
    $wiziq_vc_language = $CFG->wiziq_vc_language;
    $xmlpathtoping = $wiziq_vc_language;
    if (function_exists('curl_init')) {
        try {
            $ch = curl_init("$xmlpathtoping");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            $languagerromsg = get_string('error_in_languagexml', 'wiziq');
            print_error($languagerromsg);
            return false;
        }
        if (!empty($data)) {
            $simxmlelet = new SimpleXmlElement($data, LIBXML_NOCDATA);
            $vclang = array();
            foreach ($simxmlelet->virtual_classroom->languages->language as $value) {
                $vclang[(string) $value->language_culture_name] = (string) $value->display_name;
            }
            return $vclang;
        } else {
            // an error happened
            print_error('error_in_langread', 'wiziq');
            return false;
        }
    } else {
        print_error('error_in_curl', 'wiziq');
        return false; // just in case
    }
}

/**
 * Generates the list of timezones in wiziq mod_form.
 *
 * @return array $vctimezone the virtual classroom timezones list.
 */
function wiziq_timezone() {
    //TODO:keep this in setting.php
    global $CFG;
    $wiziq_timezone = $CFG->wiziq_timezone;
    $xmlpathtoping = $wiziq_timezone; //$xmlPath
    if (function_exists('curl_init')) {
        try {
            $ch = curl_init("$xmlpathtoping");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            print_error('error_in_timezonexml', 'wiziq');
            return false;
        }
        if (!empty($data)) {
            $simxmlelet = new SimpleXmlElement($data, LIBXML_NOCDATA);
            $vctimezone = array();
            $vctimezone['select'] = '[select]';
            foreach ($simxmlelet->time_zone as $value) {
                $vctimezone[(string) $value] = (string) $value;
            }
            return $vctimezone;
        } else {
            // an error happened
            print_error('error_in_timeread', 'wiziq');
            return false;
        }
    } else {
        print_error('error_in_curl', 'wiziq');
        return false; // just in case
    }
}

/**
 * Generates the class time from unixtimestamp according to particular timezone 
 * selected by the user while scheduling class.
 *
 * @param int $timestamp the unix timestamp
 * @param string $timezonerequired the timezone for which class is scheduled
 * 
 * @return integer $wiziq_class_time the virtual classroom time.
 */

function wiziq_converttime($timestamp, $timezonerequired) {
  
    $date = date('Y-m-d H:i:s', $timestamp);
    $date1 = new DateTime($date, new DateTimeZone($timezonerequired) );
    $wiziq_class_time = $date;
    return $wiziq_class_time;
    
    /*
    $system_timezone = date_default_timezone_get("Asia/Kolkata");
    $st = $timestamp;
    date_default_timezone_set($timezonerequired);
    $wiziq_class_time = date('Y-m-d H:i:s', $st);
    date_default_timezone_set($system_timezone);
    return $wiziq_class_time;
     * */
     
}

/**
 * Generates the data for class that is launched and which 
 * could be joined by attendees.
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param integer $class_id the class id for which class details will be genarated.
 * @param integer $presenter_id the id of the presenter who will be present for the class.
 * @param string $presenter_name name of the presenter.
 * @param string $presenter_url url generated for the presenter to launch the class.
 * @param int $start_time the start time for the class.
 * @param string $time_zone the timezone for the class scheduled.
 * @param string $create_recording if recording is opted then true otherwise it is false.
 * @param string $status status of the class if it is upcoming, completed or expired. 
 * @param string $language_culture_name language in which class is scheduled.
 * @param int $duration duration in minutes.
 * @param string $recording_url recording link for viewing the recorded class.
 */
function wiziq_get_data($courseid, $class_id, $class_master_id, &$presenter_id, &$presenter_name, &$presenter_url, &$start_time, &$time_zone, &$create_recording, &$status, &$language_culture_name, &$duration, &$recording_url) {
    global $CFG , $USER;


    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "get_data";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    if ($class_id == 0) {
        $requestparameters["class_master_id"] = $class_master_id;        
    } else {
        $requestparameters["class_id"] = $class_id;        
    }
     $requestparameters["columns"] = "presenter_id, presenter_name,presenter_url, start_time,
        time_zone, create_recording, status, language_culture_name, duration, recording_url";
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=get_data', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOWARNING);
      
        $attribnode = $objdom->attributes();
        if ($attribnode == "ok") {

            $get_data = $objdom->get_data->record_list->record; 
            $presenter_id = (string) $get_data->presenter_id;
            $presenter_name = (string) $get_data->presenter_name;
            $presenter_url = (string) $get_data->presenter_url;
            $start_time = (string) $get_data->start_time;
            $time_zone = (string) $get_data->time_zone;
            $create_recording = (string) $get_data->create_recording;
            $status = $get_data->status;
            if ((isset($status))) {
                $status = (string) $get_data->status;
            } else {
                $status = get_string('deletefromwiziq', 'wiziq');
            }
            $language_culture_name = (string) $get_data->language_culture_name;
            $duration = (string) $get_data->duration;
            $recording_url = (string) $get_data->recording_url;
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            //can be used while debug;
            $errorcode = (string)$objdom->error->attributes()->$code;
            $errormsg = 'code'.' '.$errorcode.' '.(string)$objdom->error->attributes()->$att;
            $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classdetail::create($params);
            $event->trigger();
         //   echo $OUTPUT->notification($e->getMessage()."<br/>".$errormsg);
         
        }
        } catch (Exception $e) {
        $errormsg = get_string('errorinservice', 'wiziq');// in case no xml is returned
        $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
            );
        $event = \mod_wiziq\event\wiziq_classdetail::create($params);
        $event->trigger();
        echo $OUTPUT->notification($e->getMessage()."<br/>".$errormsg);
    }
}

/**
 * Gets details for the attendee for the class. 
 *
 * @param integer $class_id the class id for which class attendees will be added.
 * @param integer $attendee_id the id of the nndee for the class
 * @param string $attendee_url url generated for the user to attend the class.
 */
function wiziq_get_data_attendee($class_id, $attendee_id, &$attendee_url , $courseid) {
    global $CFG ,$USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $context = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "get_data";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["class_id"] = $class_id;
    $requestparameters["attendee_id"] = $attendee_id;
    $requestparameters["columns"] = "attendee_url";
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=get_data', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = (string) $objdom->attributes();
        if ($attribnode == "ok") {
            $get_data = $objdom->get_data->record_list->record;
            $attendee_url = (string) $get_data->attendee_url;
        }//end if
    } catch (Exception $e) {
        $errormsg = get_string('errorinservice', 'wiziq');// in case no xml is returned
        $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $context,
                'other' => array(
                    'error' => $errormsg
                )
            );
        $event = \mod_wiziq\event\wiziq_classdetail::create($params);
        $event->trigger();
        echo $OUTPUT->notification($e->getMessage()."<br/>".$errormsg);
    }
}

/**
 * Gets details for the class from wiziq_api. 
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param array $classids the class id for which class attendees will be added.
 */
function wiziq_get_data_manage($courseid, $classids , &$classtatus ,&$presenter_url1 , &$presenter_id , &$presenter_name , &$recording_url , &$create_recording) {
    
 
    global $CFG, $DB, $USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
      $context = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "get_data";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $multiple_class_id = implode(',', $classids);
    
    $requestparameters["page_size"] = WIZIQ_DEFAULT_PAGESIZE;
    if($multiple_class_id !='')
        {
         $requestparameters["multiple_class_id"] = $multiple_class_id;
        } else { 
         $requestparameters["multiple_class_id"] = $classids;
        }
    $requestparameters["columns"] = "class_id ,presenter_id, presenter_name,presenter_url, status, recording_url , create_recording";

    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=get_data', $requestparameters);
        libxml_use_internal_errors(true);
        $xmldata = new SimpleXmlElement($xmlreturn, LIBXML_NOCDATA);
   
        $attribnode = (string) $xmldata->attributes();
        if ($attribnode == "ok") {
            $get_data = $xmldata->get_data->record_list;
            //print_r($get_data);
            foreach ($get_data->record as $record) {              
                if (isset($record->status)) {                    
                    $classtatus = (string) $record->status;
                    $presenter_url1 = (string) $record->presenter_url;
                    $classids1 = (string) $record->class_id;
                    $presenter_id = (string) $record->presenter_id;
                    $presenter_name  = (string) $record->presenter_name;
                    $recording_url = (string) $record->recording_url;
                    $create_recording = (string) $record->create_recording;
                    $class_id_from_xml[(string) $record->class_id] = (string) $record->status;
                } else {
                    $status = get_string('deletefromwiziq', 'wiziq');
                    $class_id_from_xml[(string) $record->class_id] = $status;
                }
            }

          
               
            foreach ($classids as $key => $value) {
              
                /*
                 * we use isset for performance knowing the the
                 * value for particular key will never be null
                 */
                if (isset($class_id_from_xml[$value])) {
                    $updates = new stdClass(); //just enough data for updating the submission
                    $updates->id = $key;
                    $updates->class_status = $class_id_from_xml[$value];
                    $DB->update_record('wiziq', $updates);
                }
            }
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $error_code = (string)$xmldata->error->attributes()->$code;
            $error_msg = (string)$xmldata->error->attributes()->$att; //can be used while debug
            $error = $error_code." ".$error_msg;
            $errormsg = get_string('errorinservice', 'wiziq');// in case no xml is returned
            
            $params = array(
                'objectid' => $courseid,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $context,
                'other' => array(
                    'error' => $errormsg,
                    'sesskey' => sesskey()
                )
            );
            $event = \mod_wiziq\event\wiziq_classlisting::create($params);
            $event->trigger();
            print_error('1', '', '', $error);
        }
    } catch (Exception $e) {
        if (property_exists($e, 'errorcode')) {
            if ($e->errorcode == '1') {
                echo $OUTPUT->notification($e->a.' '.'Check your Wiziq Settings');
            }
        } else {
            $errormsg = get_string('errorinservice', 'wiziq');
             $params = array(
                'objectid' => $courseid,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $context,
                'other' => array(
                    'error' => $errormsg,
                    'sesskey' => sesskey()
                )
            );
            $event = \mod_wiziq\event\wiziq_classlisting::create($params);
            $event->trigger();
            echo $OUTPUT->notification($e->getMessage(). '<br />'. $errormsg);
        }
    }
}

/**
 * Adds attendee for class. 
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param int $class_id the class id for which class attendees will be added.
 * @param int $attendee_id the id of the attendee who will attend the class.
 * @param string $attendee_screen_name screen name for the user attending the class.
 * @param string $language_culture_name language name in which class is scheduled.
 * @param string $attendee_url url generated for the user to attend the class.
 * @param string $errormsg error message if attendee is not added.
 */
function wiziq_addattendee($courseid, $class_id, $attendee_id, $attendee_screen_name, $language_culture_name, &$attendee_url, &$errormsg) {
    global $CFG,  $USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $context = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $xmlattendee = "<attendee_list>
    <attendee>
    <attendee_id><![CDATA[$attendee_id]]></attendee_id>
    <screen_name><![CDATA[$attendee_screen_name]]></screen_name>
    <language_culture_name><![CDATA[$language_culture_name]]></language_culture_name>
    </attendee>
    </attendee_list>";
    $method = "add_attendees";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["class_id"] = $class_id; //required
    $requestparameters["attendee_list"] = $xmlattendee;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=add_attendees', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = (string) $objdom->attributes();
        if ($attribnode == "ok") {
            $add_attendeexml = $objdom->add_attendees;
            $class_id = (string) $add_attendeexml->class_id;
            $attendeelist = $add_attendeexml->attendee_list->attendee;
            foreach ($attendeelist as $attendee) {
                $attendee_id = (string) $attendee->attendee_id;
                $attendee_url = (string) $attendee->attendee_url;
            }
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
           $error_code = (string)$objdom->error->attributes()->$code;
            $error_msg = (string)$objdom->error->attributes()->$att; //can be used while debug
            $errormsg = $error_code." ".$error_msg;
            $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $context,
                'other' => array(
                    'error' => $errormsg,
                )
            );
            $event = \mod_wiziq\event\wiziq_classdetail::create($params);
            $event->trigger();
        }
    } catch (Exception $e) {
        $errormsg = get_string('errorinservice', 'wiziq');// in case no xml is returned
        $params = array(
            'objectid' => $class_id,
            'relateduserid' => $USER->id,
            'courseid' => $courseid,
            'context' => $context,
            'other' => array(
                'error' => $errormsg,
            )
        );
        $event = \mod_wiziq\event\wiziq_classdetail::create($params);
        $event->trigger();
        print_error($e->getMessage()."<br/>".$errormsg);
    }
}

//end function

/**
 * Deletes the class from wiziq.
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param integer $class_id the class id for the class to be deleted.
 */
function wiziq_delete_class($courseid, $class_id) {
    global $CFG, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
     $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "cancel";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["class_id"] = $class_id;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=cancel', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = (string) $objdom->attributes();
        if ($attribnode == "ok") {
            $cancel = (string) $objdom->cancel->attributes();
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
              $error_code = (string)$objdom->error->attributes()->$code;
            $error_msg = (string)$objdom->error->attributes()->$att; //can be used while debug
            $errormsg = $error_code." ".$error_msg;
            $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classdelete::create($params);
            $event->trigger();
        }
    } catch (Exception $e) {
        echo $e->getMessage();
        $errormsg = get_string('errorinservice', 'wiziq');
         $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
        );
        $event = \mod_wiziq\event\wiziq_classdelete::create($params);
        $event->trigger();
    }
}

//end function

/**
 * Updates the class at wiziq.
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param string $wiziq_secretacesskey secret access key generated during wiziq download.
 * @param string $wiziq_access_key the access key generated during wiziq download.
 * @param string $wiziq_webserviceurl url to ping for xml return for scheduling class.
 * @param integer $class_id class_id for which class need to be updated.
 * @param string $title title of the class scheduled.
 * @param integer $presenter_id the id of the presenter who will be present for the class.
 * @param string $presenter_name name of the presenter.
 * @param integer $wiziq_datetime the time at which the class is scheduled.
 * @param string $wiziqtimezone the timezone for which class is scheduled.
 * @param integer $class_duration duration in minutes for scheduling class.
 * @param string $vc_language the language in which the class will be launched.
 * @param string $recording wheteher recording is opted or not.
 * @param string $intro description of the class scheduled.
 * @param string $attribnode the attribute is ok then the class is scheduled.
 * @param string $errormsg error message in case there is some error in scheduling class.
 */
function wiziq_modifyclass($courseid, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $class_id, $title, $presenter_id, $presenter_name, $wiziq_datetime, $wiziqtimezone, $class_duration, $vc_language, $recording, $intro, &$attribnode, &$errormsg) {
    global $CFG, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
     $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "modify";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["class_id"] = $class_id;
    $requestparameters["title"] = $title;
    $requestparameters["description"] = $intro;
    $requestparameters["presenter_id"] = $presenter_id;
    $requestparameters["presenter_name"] = $presenter_name;
    $requestparameters["start_time"] = $wiziq_datetime;
    $requestparameters["duration"] = $class_duration;
    $requestparameters["time_zone"] = $wiziqtimezone;
    $requestparameters["create_recording"] = $recording;
    $requestparameters["return_url"] = "";
    $requestparameters["status_ping_url"] = "";
    $requestparameters["language_culture_name"] = $vc_language;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=modify', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = (string) $objdom->attributes();
        if ($attribnode == "ok") {
            $modify = (string) $objdom->modify;
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
             $error_code = (string)$objdom->error->attributes()->$code;
            $error_msg = (string)$objdom->error->attributes()->$att; //can be used while debug
            $errormsg = $error_code." ".$error_msg;
            $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classupdate::create($params);
            $event->trigger();
        }
    } catch (Exception $e) {
        $errormsg = get_string('errorinservice', 'wiziq');
        $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classupdate::create($params);
            $event->trigger();
        print_error($errormsg);
    }
}

//end function

/**
 * Gets details for the class from wiziq_api for perma class. 
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param array $classids the class id for which class attendees will be added.
 */
function wiziq_get_data_manageperma($courseid, $wiziq_classmasterid_array, &$wiziq_classidperma) {

   
    global $CFG, $DB, $wiziq_classidperma , $USER , $OUTPUT;

    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "view_schedule";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);

  
      foreach ($wiziq_classmasterid_array as $classmstrid) {
        
        $requestparameters["page_size"] = WIZIQ_DEFAULT_PAGESIZE;
        $requestparameters["class_master_id"] = $classmstrid;

        $wiziq_httprequest = new wiziq_httprequest();
        try {
            $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                    $wiziq_webserviceurl . '?method=view_schedule', $requestparameters);

            libxml_use_internal_errors(true);
            $xmldata = new SimpleXmlElement($xmlreturn, LIBXML_NOCDATA);

            $attribnode = (string) $xmldata->attributes();
            if ($attribnode == "ok") {
                $get_data = $xmldata->view_schedule->recurring_list->class_details;
                $wiziq_classidperma[] = '';
                foreach ($get_data as $record) {
                    $wiziq_classidperma[] = $record->class_id;
                    if (isset($record->class_status)) {
                        $class_id_from_xml[(string) $record->class_id] = (string) $record->class_status;
                    } else {
                        $status = get_string('deletefromwiziq', 'wiziq');
                        $class_id_from_xml[(string) $record->class_id] = $status;
                    }
                }
                $key = array_search($classmstrid, $wiziq_classmasterid_array);
                $keyy = $key . ",";
                $value = $classmstrid . ",";
                $key1 = array(rtrim($keyy, ','));
                $value1 = array(rtrim($value, ','));
                $mergeclassid = array_combine($key1, $value1);


                foreach ($mergeclassid as $key => $value) {

                    /*
                     * we use isset for performance knowing the the
                     * value for particular key will never be null
                     */
                    if (isset($value)) {
                        $updates = new stdClass(); //just enough data for updating the submission
                        $updates->id = $key;
                        $updates->class_status = (string) $record->class_status;
                        $DB->update_record('wiziq', $updates);
                    }
                }
            } else if ($attribnode == "fail") {
                $att = 'msg';
                $code = 'code';
                $error_code = (string)$xmldata->error->attributes()->$code;
                $error_msg = (string)$xmldata->error->attributes()->$att; //can be used while debug
                $error = $error_code." ".$error_msg;
                $errormsg = get_string('errorinservice', 'wiziq');// in case no xml is returned
            
                $params = array(
                'objectid' => $courseid,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg,
                    'sesskey' => sesskey()
                )
            );
            $event = \mod_wiziq\event\wiziq_classlisting::create($params);
            $event->trigger();
                //print_error('1', '', '', $error);
            }
        } catch (Exception $e) {
            if (property_exists($e, 'errorcode')) {
                if ($e->errorcode == '1') {
                    echo $OUTPUT->notification($e->a.' '.'Check your Wiziq Settings');
                }
            } else {
                $errormsg = get_string('errorinservice', 'wiziq');
                $params = array(
                'objectid' => $courseid,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg,
                    'sesskey' => sesskey()
                )
            );
            $event = \mod_wiziq\event\wiziq_classlisting::create($params);
            $event->trigger();              
                    echo $OUTPUT->notification($e->getMessage(). '<br />'. $errormsg);
            }
        }
 
    }
  
}

/**
 * Gets details for the class from wiziq_api for perma class single view. 
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param array $classids the class id for which class attendees will be added.
 */
function wiziq_get_data_managepermaview($courseid, $wiziq_classmasterid_array, &$wiziq_classidperma1 , &$classstatus , &$presenter_url ,&$presenter_id, &$recording_url) {


    //print_r($wiziq_classmasterid_array);
    global $CFG, $DB, $wiziq_classidperma1, $wiziq_recordlink , $USER ,$OUTPUT;

    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "view_schedule";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    // echo $classmstrid;
    $requestparameters["page_size"] = WIZIQ_DEFAULT_PAGESIZE;
    $requestparameters["class_master_id"] = $wiziq_classmasterid_array;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=view_schedule', $requestparameters);

        libxml_use_internal_errors(true);
        $xmldata = new SimpleXmlElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = (string) $xmldata->attributes();
        if ($attribnode == "ok") {
            $get_data = $xmldata->view_schedule->recurring_list->class_details;
          
            foreach ($get_data as $record) {
                $wiziq_classidperma1[] = $record->class_id;

                $wiziq_recordlink[] = $record->recording_url;
                if (isset($record->class_status)) {
                    $classstatus  = (string) $record->class_status;
                    $presenter_url  =(string) $record->presenter_list->presenter->presenter_url;
                    $presenter_id = (string) $record->presenter_list->presenter->presenter_id;  
                    $recording_url = (string) $record->recording_url;
                    $class_id_from_xml[(string) $record->class_id] = (string) $record->class_status;
                } else {
                    $status = get_string('deletefromwiziq', 'wiziq');
                    $class_id_from_xml[(string) $record->class_id] = $status;
                }
            }
                       
            $key = array_search($classmstrid, $wiziq_classmasterid_array);
            $keyy = $key . ",";
            $value = $classmstrid . ",";
            $key1 = array(rtrim($keyy, ','));
            $value1 = array(rtrim($value, ','));
            $mergeclassid = array_combine($key1, $value1);

            foreach ($mergeclassid as $key => $value) {

                /*
                 * we use isset for performance knowing the the
                 * value for particular key will never be null
                 */
                if (isset($value)) {
                    $updates = new stdClass(); //just enough data for updating the submission
                    $updates->id = $key;
                    $updates->class_status = (string) $record->class_status;
                    $DB->update_record('wiziq', $updates);
                }
            }

            //print_r($wiziq_classidperma1);
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $error_code = (string) $xmldata->error->attributes()->$code;
            $error_msg = (string) $xmldata->error->attributes()->$att; //can be used while debug
            $error = $error_code . " " . $error_msg;            
            // print_error('1', '', '', $error);
        }
    } catch (Exception $e) {
        if (property_exists($e, 'errorcode')) {
            if ($e->errorcode == '1') {
                echo $OUTPUT->notification($e->a.' '.'Check your Wiziq Settings');
            }
        } else {
            $errormsg = get_string('errorinservice', 'wiziq');           
                    echo $OUTPUT->notification($e->getMessage(). '<br />'. $errormsg);
        }
    }
}

/**
 * Generates the download recording link after completion of class. 
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param integer $class_id the class id for which recording link will be generated.
 * @param string $download_recording_link download recording link for the class.
 * @param string $errormsg Error description to be shown to user.
 *        
 */
function wiziq_downloadrecording($courseid, $class_id, &$download_recording_link, &$errormsg, $abcdd) {

    global $CFG, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl ,$USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $context = context_course::instance($courseid);  
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "download_recording";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);

    if ($class_id == 0) {

        foreach ($abcdd as $classidget) {
            $requestparameters["class_id"] = $classidget;
            $curos = strtolower($_SERVER['HTTP_USER_AGENT']);
            if (strstr($curos, "win")) {
                $requestparameters["recording_format"] = "exe";
            } else {
                $requestparameters["recording_format"] = "zip";
            }

            $wiziq_httprequest = new wiziq_httprequest();
            try {
                $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                        $wiziq_webserviceurl . '?method=download_recording', $requestparameters);
                libxml_use_internal_errors(true);
                $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);
                //   echo "<pre>";
                // print_r($objdom);
                $attribnode = (string) $objdom->attributes();
                if ($attribnode == "ok") {
                    $download_recording = $objdom->download_recording;
                    $download_status = (string) $download_recording->download_status;
                    // if ($download_status == "true") {
                    $rec_statusnode = (string) $download_recording->attributes();
                    if ($rec_statusnode == "true") {
                        $status_xml_path[] = (string) $download_recording->status_xml_path;

                        wiziq_download_recording($courseid, $class_id, $status_xml_path, $download_rec_link_path, $errormsgdown);

                        if (!empty($download_rec_link_path)) {
                            $download_recording_link[] = $download_rec_link_path;
                        } else {
                           $errormsg = get_string('recnotcreatedyet', 'wiziq');  
                            $params = array(
                                'objectid' => $class_id,
                                'relateduserid' => $USER->id,
                                'courseid' => $courseid,
                                'context' => $context,
                                'other' => array(
                                    'error' => $errormsg
                                )
                            );
                            $event = \mod_wiziq\event\wiziq_classdetail::create($params);
                            $event->trigger();
                        }
                    } else {
                        $download_recording_link = null;
                    }
                    //  }
                } else if ($attribnode == "fail") {
                    $att = 'msg';
                    $code = 'code';
                    $error_code = (string)$objdom->error->attributes()->$code;
                            $error_msg = (string)$objdom->error->attributes()->$att; //can be used while debug
                            $errormsg = $error_code." ".$error_msg;
                            $params = array(
                                'objectid' => $class_id,
                                'relateduserid' => $USER->id,
                                'courseid' => $courseid,
                                'context' => $context,
                                'other' => array(
                                    'error' => $errormsg
                                )
                            );
                            $event = \mod_wiziq\event\wiziq_classdetail::create($params);
                            $event->trigger();
                }
            } catch (Exception $e) {
               $errormsg = $e->getMessage().'<br />'.get_string('errorinservice', 'wiziq');
              $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $context,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classdetail::create($params);
            $event->trigger();
            }
        }
    } else {

        $requestparameters["class_id"] = $class_id;
        $curos = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (strstr($curos, "win")) {
            $requestparameters["recording_format"] = "exe";
        } else {
            $requestparameters["recording_format"] = "zip";
        }

        $wiziq_httprequest = new wiziq_httprequest();
        try {
            $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                    $wiziq_webserviceurl . '?method=download_recording', $requestparameters);
            libxml_use_internal_errors(true);
            $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);

            $attribnode = (string) $objdom->attributes();
            if ($attribnode == "ok") {
                $download_recording = $objdom->download_recording;
                $download_status = (string) $download_recording->download_status;
                if ($download_status == "true") {
                    $rec_statusnode = (string) $download_recording->attributes();
                    if ($rec_statusnode == "true") {
                        $status_xml_path = (string) $download_recording->status_xml_path;
                        wiziq_download_recording($courseid, $class_id, $status_xml_path, $download_rec_link_path, $errormsgdown);
                        if (!empty($download_rec_link_path)) {
                            $download_recording_link = $download_rec_link_path;
                        } else {
                            $errormsg = get_string('recnotcreatedyet', 'wiziq');                            
                            $params = array(
                                'objectid' => $class_id,
                                'relateduserid' => $USER->id,
                                'courseid' => $courseid,
                                'context' => $context,
                                'other' => array(
                                    'error' => $errormsg
                                )
                            );
                            $event = \mod_wiziq\event\wiziq_classdetail::create($params);
                            $event->trigger();
                        }
                    } else {
                        $download_recording_link = null;
                    }
                }
            } else if ($attribnode == "fail") {
                $att = 'msg';
                $code = 'code';
                $error_code = (string)$objdom->error->attributes()->$code;
                            $error_msg = (string)$objdom->error->attributes()->$att; //can be used while debug
                            $errormsg = $error_code." ".$error_msg;
                            $params = array(
                                'objectid' => $class_id,
                                'relateduserid' => $USER->id,
                                'courseid' => $courseid,
                                'context' => $context,
                                'other' => array(
                                    'error' => $errormsg
                                )
                            );
                            $event = \mod_wiziq\event\wiziq_classdetail::create($params);
                            $event->trigger();
            }
        } catch (Exception $e) {
            $errormsg = $e->getMessage().'<br />'.get_string('errorinservice', 'wiziq');
              $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $context,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classdetail::create($params);
            $event->trigger();
        }
    }
}

//end function

/**
 * Generates the download recording link after completion of class. 
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param string $status_xml_path the xml path from which download recording link is extracted.
 * @param string $download_rec_link_path returns the download recording link to
 * @param string $errormsgdown Error description to be shown to user. 
 *        
 */
function wiziq_download_recording($courseid, $class_id, $status_xml_path, &$download_rec_link_path, &$errormsgdown) {


    if ($class_id == 0) {

        $xmlpathtoping1 = $status_xml_path;

        foreach ($xmlpathtoping1 as $xmlpathtoping) {

            if (function_exists('curl_init')) {
                try {
                    $ch = curl_init("$xmlpathtoping");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    $data = curl_exec($ch);
                    curl_close($ch);
                } catch (Exception $e) {
                    print_error('error_in_downloadrec', 'wiziq');
                    return false;
                }
                try {
                    libxml_use_internal_errors(true);
                    $simxmlelet = new SimpleXmlElement($data, LIBXML_NOCDATA);

                    $wiziq_download_status = (string) $simxmlelet->download_recording->download_status;
                    rtrim($wiziq_download_status);
                    ltrim($wiziq_download_status);

                    if ($wiziq_download_status == 'true') {

                        $download_rec_link = $simxmlelet->download_recording;
                        $download_rec_link_path = (string) $download_rec_link->recording_download_path;
                    } else {
                        $download_rec_link_path = null;
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                    $errormsgdown = get_string('error_in_downloadrec', 'wiziq');
                }
            } else {
                $errormsgdown = get_string('error_in_curl', 'wiziq');
            }
        }
    } else {

        $xmlpathtoping = $status_xml_path;
        if (function_exists('curl_init')) {
            try {
                $ch = curl_init("$xmlpathtoping");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $data = curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                print_error('error_in_downloadrec', 'wiziq');
                return false;
            }
            try {
                libxml_use_internal_errors(true);
                $simxmlelet = new SimpleXmlElement($data, LIBXML_NOCDATA);
                $wiziq_download_status = (string) $simxmlelet->download_recording->download_status;
                rtrim($wiziq_download_status);
                ltrim($wiziq_download_status);
                if ($wiziq_download_status == 'true') {

                    $download_rec_link = $simxmlelet->download_recording;
                    $download_rec_link_path = (string) $download_rec_link->recording_download_path;
                } else {
                    $download_rec_link_path = null;
                }
            } catch (Exception $e) {
                echo $e->getMessage();
                $errormsgdown = get_string('error_in_downloadrec', 'wiziq');
            }
        } else {
            $errormsgdown = get_string('error_in_curl', 'wiziq');
        }
    }
}

/**
 * Generates the attendance report after completion of class giving details of
 * attendee list and duration of class. 
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param integer $class_id id of the class for which attenadnce report is generated.
 * @param integer $id id of the course.
 * @param string $errormsg error message if there is any error in generating the attendance report.
 * will be used in next version
 * @param string $attendancexmlch_dur total duration of class.
 * @param string $attendancexmlch_attlist attendee's details for attendance report.
 */
function wiziq_getattendancereport($courseid, $class_id, $id, &$errormsg, &$attendancexmlch_dur, &$attendancexmlch_attlist) {

    global $CFG, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl , $USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $coursecontext = context_course::instance($courseid);  
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "get_attendance_report";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["class_id"] = $class_id;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=get_attendance_report', $requestparameters);

        libxml_use_internal_errors(true);
        $attendancexml = new SimpleXMLElement($xmlreturn);
        $attendancexml_status = $attendancexml->attributes();
        if ($attendancexml_status == 'ok') {
            $attendancexmlch = $attendancexml->get_attendance_report;
            $attendancexmlch_status = $attendancexmlch->attributes();
            if ($attendancexmlch_status == 'true') {
                $attendancexmlch_dur = $attendancexmlch->class_duration;
                $attendancexmlch_attlist = $attendancexmlch->attendee_list;
            }
        } else if ($attendancexml_status == "fail") {
            $att = 'msg';
            $attribute = (string) $attendancexml->error->attributes()->$att;
            if ($attribute == 'No record found.') {
                $attribute = '<b>We haven’t found any attendee for this class.</b>';
            } else if ($attribute == 'Attendance report will be available soon.') {
                $attribute = '<b>We are processing the information for the class. Attendance report will be available soon.</b>';
            }
            $url = new moodle_url('/mod/wiziq/index.php', array('id' => $id));
            
            $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'sesskey' => sesskey(),
                    'error' => $attribute
                )
            );
            $event = \mod_wiziq\event\wiziq_classattendance::create($params);
            $event->trigger();
           
            print_error('1', '', '', $attribute);
        }
    } catch (Exception $e) {
        if (property_exists($e, 'errorcode')) {
            print_error($e->a);
        } else {
            $errormsg = get_string('errorinservice', 'wiziq');
            $params = array(
                'objectid' => $class_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'sesskey' => sesskey(),
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classattendance::create($params);
            $event->trigger();
            print_error($errormsg);
        }
    }
}

/**
 * Creates a folder for content uploading for wiziq.
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param string $folderpath folder path if another folder is contained within a folder.
 * @param string $foldername name of the folder created.
 * 
 * @return boolean $createfolderxml_status if true then the content is uploaded.
 */
function wiziq_create_folder($courseid, $folderpath, $foldername) {
    global $CFG, $USER, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_content_webservice;
    require_once("authbase.php");
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_content_webservice = $CFG->wiziq_content_webservice;
    $coursecontext = context_course::instance($courseid);
    $requestparameters = array();
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "create_folder";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["presenter_id"] = $USER->id;
    if (!empty($folderpath)) {
        $folderpath = $folderpath . "/" . $foldername;
        $requestparameters["folder_path"] = $folderpath;
    } else {
        $requestparameters["folder_path"] = $foldername;
    }
    $requestparameters["presenter_name"] = $USER->firstname . " " . $USER->lastname;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_content_webservice . '?method=create_folder', $requestparameters);
        libxml_use_internal_errors(true);
        $createfolderxml = new SimpleXMLElement($xmlreturn);
        $createfolderxml_status = $createfolderxml->attributes();
        if ($createfolderxml_status == 'ok') {
            $createfolderxmlch = $createfolderxml->create_folder;
            $att = 'status';
            $createfolderxml_status = (string) $createfolderxmlch->attributes()->$att;
            if ($createfolderxml_status == 'true') {
                $paramslog = array(
                    'objectid' => $courseid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => ''
                    )
                );
                $event = \mod_wiziq\event\wiziq_foldercreate::create($paramslog);
                $event->trigger();
                return $createfolderxml_status;
            } else {
                $unable_to_create = get_string('unable_to_create', 'wiziq');
                print_error('1', '', '', $unable_to_create);
            }
        } else {
            $unable_to_create = get_string('unable_to_create', 'wiziq');
            print_error('1', '', '', $unable_to_create);
        }
    } catch (Exception $e) {
        if (property_exists($e, 'errorcode')) {
            print_error($e->a);
        } else {
            $errormsg = get_string('errorinservice', 'wiziq');
           $paramslog = array(
                    'objectid' => $courseid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => $errormsg
                    )
            );
            $event = \mod_wiziq\event\wiziq_foldercreate::create($paramslog);
            $event->trigger();
            print_error($errormsg);
        }
    }
}

/**
 * Uploads content for wiziq.
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param string $filetitle title of the file uploaded.
 * @param array $file temporary array for getting details of file uploaded. 
 * @param string $folderpath folder path generated.
 * 
 * @return string $content_details
 */
function wiziq_content_upload($courseid, $filetitle, $file, $folderpath) {
    global $CFG, $USER, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_content_webservice;
    require_once("authbase.php");
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_content_webservice = $CFG->wiziq_content_webservice;
    $coursecontext = context_course::instance($courseid);
    $requestparameters = array();
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "upload";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    if (!empty($filetitle)) {
        $requestparameters["title"] = $filetitle;
    } else {
        $filename = array();
        $filename = explode(".", $file['uploadingfile']['name']);
        $requestparameters["title"] = $filename['0'];
    }
    $requestparameters["presenter_id"] = $USER->id;
    if (!empty($folderpath)) {
        $requestparameters["folder_path"] = $folderpath;
    }
    $requestparameters["presenter_name"] = $USER->firstname . " " . $USER->lastname;
    $content = file_get_contents($_FILES['uploadingfile']['tmp_name']);
    $filefieldname = (array_keys($_FILES));
    $delimiter = '-------------' . uniqid();
    $filefields = array(
        'file1' => array(
            'name' => $_FILES['uploadingfile']['name'],
            'type' => $_FILES['uploadingfile']['type'],
            'content' => $content),
    );
    $data = '';
    foreach ($requestparameters as $name => $value) {
        $data .= "--" . $delimiter . "\r\n";
        $data .= 'Content-Disposition: form-data; name="' . $name . '";' . "\r\n\r\n";
        // note: double endline
        $data .= $value . "\r\n";
    }
    foreach ($filefields as $name => $file) {
        $data .= "--" . $delimiter . "\r\n";
        // "filename" attribute is not essential; server-side scripts may use it
        $data .= 'Content-Disposition: form-data; name="' . $filefieldname['0'] . '";' .
                ' filename="' . $file['name'] . '"' . "\r\n";
        // this is, again, informative only; good practice to include though
        $data .= 'Content-Type: ' . $file['type'] . "\r\n";
        // this endline must be here to indicate end of headers
        $data .= "\r\n";
        // the file itself (note: there's no encoding of any kind)
        $data .= $file['content'];
    }
    $data .= "\r\n" . "--" . $delimiter . "--\r\n";
    $str = $data;
    // set up cURL
    $ch = curl_init($wiziq_content_webservice . "?method=upload");
    curl_setopt_array($ch, array(
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array(// we need to send these two headers
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($str)
        ),
        CURLOPT_POSTFIELDS => $data,
    ));
    $ress = curl_exec($ch);
    curl_close($ch);
    try {
        libxml_use_internal_errors(true);
        $contentupload = new SimpleXMLElement($ress);
        $contentupload_rsp = $contentupload->attributes();
        if ($contentupload_rsp == "ok") {
            $contentupload_status = $contentupload->upload->attributes();
            if ($contentupload_status == "true") {
                $content_details = $contentupload->upload->content_details;
                $contentid = (string)$content_details->content_id;
                $paramslog = array(
                    'objectid' => $contentid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => ''
                    )
                );
                $event = \mod_wiziq\event\wiziq_contentadd::create($paramslog);
                $event->trigger();
                return $content_details;
            } else {
                $uploaderror = get_string('uploaderror', 'wiziq');
                $paramslog = array(
                    'objectid' => $courseid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => $uploaderror
                    )
                );
                $event = \mod_wiziq\event\wiziq_content::create($paramslog);
                $event->trigger();
                print_error('1', '', '', $uploaderror); //just in case
            }
        } else if ($contentupload_rsp == "fail") {
            $att = 'msg';
            $attribute = (string) $contentupload->error->attributes()->$att;
            $paramslog = array(
                    'objectid' => $courseid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => $attribute
                    )
                );
                $event = \mod_wiziq\event\wiziq_content::create($paramslog);
                $event->trigger();
            print_error('1', '', '', $attribute);
        }
    } catch (Exception $e) {
        if (property_exists($e, 'errorcode')) {
            print_error($e->a);
        } else {
            $errormsg = get_string('errorinservice', 'wiziq');
             $paramslog = array(
                    'objectid' => $courseid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => $errormsg
                    )
            );
            $event = \mod_wiziq\event\wiziq_content::create($paramslog);
            $event->trigger();
            print_error($errormsg);
        }
    }
}

/**
 * Deletes content for a particular content id.
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param integer $contentid id of content
 * 
 * @return boolean $deletexmlch_status status is true if content is deleted.
 */
function wiziq_content_delete($courseid, $contentid) {
    global $CFG, $USER, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_content_webservice;
    require_once("authbase.php");
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_content_webservice = $CFG->wiziq_content_webservice;
     $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "delete";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["content_id"] = $contentid; //Required
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_content_webservice . '?method=delete', $requestparameters);
        libxml_use_internal_errors(true);
        $deletexml = new SimpleXMLElement($xmlreturn);
        $deletexml_status = $deletexml->attributes();
        if ($deletexml_status == 'ok') {
            $deletexmlch = $deletexml->delete;
            $att = 'status';
            $deletexmlch_status = (string) $deletexmlch->attributes()->$att;
            if ($deletexmlch_status == 'true') {
                 $paramslog = array(
                    'objectid' => $contentid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => ''
                    )
                );
                $event = \mod_wiziq\event\wiziq_contentdelete::create($paramslog);
                $event->trigger();
                return $deletexmlch_status;
            } else {
                $unable_to_delete = get_string('unable_to_delete', 'wiziq');
                 $paramslog = array(
                    'objectid' => $contentid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => $unable_to_delete
                    )
                );
                $event = \mod_wiziq\event\wiziq_content::create($paramslog);
                $event->trigger();
            }
        } else if ($deletexml_status == "fail") {
            $att = 'msg';
            $attribute = (string) $deletexml->error->attributes()->$att;
            $paramslog = array(
                    'objectid' => $contentid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => $attribute
                    )
                );
                $event = \mod_wiziq\event\wiziq_content::create($paramslog);
                $event->trigger();
        }
    } catch (Exception $e) {
        $errormsg = get_string('errorinservice', 'wiziq');
        $paramslog = array(
                    'objectid' => $contentid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => $errormsg
                    )
            );
            $event = \mod_wiziq\event\wiziq_content::create($paramslog);
            $event->trigger();
    }
}

/**
 * Deletes folder for wiziq
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param string $foldername name of the folder which needs to be deleted.
 * @param string $folderpath path of the folder.
 * 
 * @return boolean $deletefolderxmlch_status status is true if folder is deleted.
 */
function wiziq_delete_folder($courseid, $foldername, $folderpath) {
    global $CFG, $USER, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_content_webservice;
    require_once("authbase.php");
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_content_webservice = $CFG->wiziq_content_webservice;
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "delete_folder";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["presenter_id"] = $USER->id;
    if (!empty($folderpath)) {
        $folderpath = $folderpath . "/" . $foldername;
        $requestparameters["folder_path"] = $folderpath;
    } else {
        $requestparameters["folder_path"] = $foldername;
    }
    $requestparameters["presenter_name"] = $USER->firstname . " " . $USER->lastname;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_content_webservice . '?method=delete_folder', $requestparameters);
        libxml_use_internal_errors(true);
        $deletefolderxml = new SimpleXMLElement($xmlreturn);
        $deletefolderxml_status = $deletefolderxml->attributes();
        if ($deletefolderxml_status == 'ok') {
            $deletefolderxmlch = $deletefolderxml->delete_folder;
            $att = 'status';
            $deletexml_status = (string) $deletefolderxmlch->attributes()->$att;
        } else if ($deletexml_status == "fail") {
            $att = 'msg';
            $attribute = (string) $deletefolderxmlch->error->attributes()->$att;
        }
    } catch (Exception $e) {
        $errormsg = get_string('errorinservice', 'wiziq');
    }
}

/**
 * Gets the content status if it is inprogress,failed or available.
 *
 * @param string $folderpath path of the folder.
 * @param string $foldername name of the folder.
 * @param integer $courseid id of the course.
 */
function wiziq_get_contentstatus($folderpath, $foldername, $courseid) {
    global $CFG, $USER, $DB, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_content_webservice;
    require_once("authbase.php");
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_content_webservice = $CFG->wiziq_content_webservice;
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "list";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["presenter_id"] = $USER->id;
    if ((!empty($folderpath)) && (!empty($foldername))) {
        $requestparameters["folder_path"] = $folderpath . "/" . $foldername;
    } else if ((!empty($foldername)) && empty($folderpath) && ($foldername != 'My Content')) {
        $requestparameters["folder_path"] = $foldername;
    }
    $requestparameters["page_size"] = WIZIQ_DEFAULT_PAGESIZE;
    $requestparameters["presenter_name"] = $USER->firstname . " " . $USER->lastname;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_content_webservice . '?method=list', $requestparameters);
        libxml_use_internal_errors(true);
        $contentlistxml = new SimpleXMLElement($xmlreturn);
        $contentlistxml_status = $contentlistxml->attributes();
        if ($contentlistxml_status == 'ok') {
            $recordlist = $contentlistxml->list->record_list;
            foreach ($recordlist->children() as $value) {
                $status = (string) $value->status;
                if ($status == 'failed') {
                    $statusvalue = 3;
                } else if ($status == 'available') {
                    $statusvalue = 2;
                } else if ($status == 'inprogress') {
                    $statusvalue = 1;
                }
                $value_content_id = (string) $value->content_id;
                $conid = $DB->get_records('wiziq_content', array('contentid' => $value_content_id));
                if (!empty($conid)) {
                    foreach ($conid as $value) {
                        $updates = new stdClass();
                        $updates->id = $value->id;
                        $updates->status = $statusvalue; //status will be same of same contetn
                        $DB->update_record('wiziq_content', $updates);
                    }
                }
            }
        }
    } catch (Exception $e) {
       echo $OUTPUT->notification($e->getMessage());
    }
}

/**
 * This authenticates for the content that is uploaded.
 *
 * @param integer $id
 * @param string $timekey
 * @param string $hash
 */
function wiziq_authentication(&$id, &$timekey, &$hash) {
    $timekey = time();
    $key = "key";
    $data = $id + $timekey;
    $hash = wiziq_encrypt_hash($data, $key);
}

/**
 * This encrypts the content that is uploaded,function is called from
 * wiziq_authentication function.
 *
 * @param string $str
 * @param string $key
 *
 * @return string $hashedvalue
 */
function wiziq_encrypt_hash($str, $key) {
    $block = mcrypt_get_block_size('des', 'ecb');
    $pad = $block - (strlen($str) % $block);
    $str .= str_repeat(chr($pad), $pad);
    $encodestring = base64_encode(mcrypt_encrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB));
    $hashedvalue = urlencode($encodestring);
    return $hashedvalue;
}

/**
 * This decrypts the content that is uploaded
 *
 * @param string $strh
 * @param string $key
 *
 * @return string $var4
 */
function wiziq_decrypt_hash($strh, $key) {
    $plus = preg_match("/\+/i", $strh);
    if ($plus) {
        $str1 = $strh;
    } else {
        $str1 = urldecode($strh);
    }
    $str = base64_decode($str1);
    $str2 = mcrypt_decrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);
    $block = mcrypt_get_block_size('des', 'ecb');
    $pad = ord($str2[($len = strlen($str2)) - 1]);
    $strlen = strlen($str2);
    $var2 = substr($str2, 0, strlen($str2) - $pad);
    $var4 = $var2;
    return $var4;
}

/**
 * Gets the class attendance time in particular timezone selected by user.
 * 
 * @param integer $entry_time entry time for particular attendee or presenter.
 * @param integer $wiziqclassid id of class.
 * 
 * @return integer $attendatetime attendee time for the class.
 */
function wiziq_attendance_time($entry_time, $wiziqclassid) {
    global $DB;
    // echo $entry_time;10:43
    $strings = explode(' ', $entry_time);
    $actualtimezone = date_default_timezone_get(); 
    $class_time_zone = $DB->get_field('wiziq', 'class_timezone', array('id' => $wiziqclassid));
    $hourtimechange = $strings['1'] . $strings['2']; //10:46:00am
    $datevalue = date('H:i:s', strtotime($hourtimechange));//10:46:00
    $datetimevalue = $strings['0'] . " " . $datevalue; 
    $date = date_parse_from_format('m/d/Y h:i:s', $datetimevalue);
    date_default_timezone_set($class_time_zone);
    $timestamp = mktime($date['hour'], $date['minute'], $date['second'], $date['month'], $date['day'], $date['year']);
      //date_default_timezone_set($class_time_zone);
    $attendatetime = date("m/d/Y h:i:s A", $timestamp);
    date_default_timezone_set($actualtimezone);
    return $attendatetime;
}

/**
 * Generates the data for class scheduled by soap api. 
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param integer $sessioncode the sessioncode of old classes scheduled by soap api.
 * @param integer $class_id the class id for which class details will be genarated.
 * @param integer $wiziq_id ths id of wiziq activity in the moodle wiziq table.
 * @param integer $presenter_id the id of the presenter who will be present for the class.
 * @param string $presenter_name name of the presenter.
 * @param string $presenter_url url generated for the presenter to launch the class.
 * @param int $start_time the start time for the class.
 * @param string $time_zone the timezone for the class scheduled.
 * @param string $create_recording if recording is opted then true otherwise it is false.
 * @param string $status status of the class if it is upcoming, completed or expired. 
 * @param string $language_culture_name language in which class is scheduled.
 * @param int $duration duration in minutes.
 * @param string $recording_url recording link for viewing the recorded class.
 */
function wiziq_get_data_by_sessioncode($courseid, $sessioncode, &$class_id, $wiziq_id, &$presenter_id, &$presenter_name, &$presenter_url, &$start_time, &$time_zone, &$create_recording, &$status, &$language_culture_name, &$duration, &$recording_url) {
    global $CFG, $DB ,$USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
      $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "get_data_by_sessionCodes";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["multiple_session_code"] = $sessioncode;
    $requestparameters["columns"] = "class_id,presenter_id,presenter_name,presenter_url,start_time,
        time_zone, create_recording, status, language_culture_name, duration, recording_url";
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=get_data_by_sessionCodes', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = $objdom->attributes();
        if ($attribnode == "ok") {
            $record_list = $objdom->get_data_by_sessionCodes->record_list->record;
            $class_id = (string) $record_list->class_id;
            $presenter_id = (string) $record_list->presenter_id;
            $presenter_name = (string) $record_list->presenter_name;
            $presenter_url = (string) $record_list->presenter_url;
            $start_time = (string) $record_list->start_time;
            $time_zone = (string) $record_list->time_zone;
            $create_recording = (string) $record_list->create_recording;
            $statustag = $record_list->status;
            if (isset($statustag)) {
                $status = (string) $record_list->status;
            } else {
                $status = get_string('deletefromwiziq', 'wiziq');
            }
            $language_culture_name = (string) $record_list->language_culture_name;
            $duration = (string) $record_list->duration;
            $recording_url = (string) $record_list->recording_url;
            $updates = new stdClass();
            $updates->id = $wiziq_id;
            $updates->class_id = $class_id;
            $updates->class_timezone = $time_zone;
            $updates->class_status = $status;
            $DB->update_record('wiziq', $updates);
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $errorcode = (string) $objdom->error->attributes()->$code;
            $error_msg = (string) $objdom->error->attributes()->$att; //can be used while debug
            if ($errorcode == 1013) {
                $errormsg = $errorcode . " " . get_string('errormsg_session_missing', 'wiziq');
            }
            $errormsg = $errorcode." ".$error_msg;
            $paramslog = array(
                    'objectid' => $sessioncode,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'error' => $errormsg
                    )
            );
            $event = \mod_wiziq\event\wiziq_sessiondetail::create($paramslog);
            $event->trigger();
            print_error('1', '', '', $errormsg);
        }
    } catch (Exception $e) {
        if (property_exists($e, 'errorcode')) {
            echo $OUTPUT->notification($e->a);
        } else {
            $errormsg = get_string('errorinservice', 'wiziq');
           $paramslog = array(
                    'objectid' => $sessioncode,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'error' => $errormsg
                    )
            );
            $event = \mod_wiziq\event\wiziq_sessiondetail::create($paramslog);
            $event->trigger();
            echo $OUTPUT->notification($errormsg);
        }
    }
}

/**
 * Generates the data for class secheduled by soap api for index page. 
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param array $sessioncodes the sessioncode of old classes scheduled by soap api.
 */
function wiziq_get_data_by_sessioncode_manage($courseid, $sessioncodes) {
    global $CFG, $DB ,$USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "get_data_by_sessionCodes";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["multiple_session_code"] = implode(',', $sessioncodes);
    $requestparameters["page_size"] = WIZIQ_DEFAULT_PAGESIZE;
    $requestparameters["columns"] = "session_code, class_id, time_zone, status";
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=get_data_by_sessionCodes', $requestparameters);
        libxml_use_internal_errors(true);
        $xmldata = new SimpleXmlElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = (string) $xmldata->attributes();
        if ($attribnode == "ok") {
            $get_data = $xmldata->get_data_by_sessionCodes->record_list;
            foreach ($get_data->record as $record) {
                if (isset($record->status)) {
                    $status = (string) $record->status;
                } else {
                    $status = get_string('deletefromwiziq', 'wiziq');
                }
                $sessions_xml = (string) $record->session_code;
                $class_id_from_xml[$sessions_xml] = array('status' => $status,
                    'time_zone' => (string) $record->time_zone,
                    'class_id' => (string) $record->class_id
                );
            }
            foreach ($sessioncodes as $key => $value) {
                /*
                 * we use isset for performance knowing the the
                 * value for particular key will never be null
                 */
                if (isset($class_id_from_xml[$value])) {
                    $updates = new stdClass(); //just enough data for updating the submission
                    $updates->id = $key;
                    $updates->class_id = $class_id_from_xml[$value]['class_id'];
                    $updates->class_timezone = $class_id_from_xml[$value]['time_zone'];
                    $updates->class_status = $class_id_from_xml[$value]['status'];
                    $DB->update_record('wiziq', $updates);
                }
            }
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $error_code = (string)$xmldata->error->attributes()->$code;
            $error_msg = (string)$xmldata->error->attributes()->$att; //can be used while debug
            $error = $error_code." ".$error_msg;
            $paramslog = array(
                    'objectid' => $courseid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'error' => $error
                    )
            );
            $event = \mod_wiziq\event\wiziq_classlisting::create($paramslog);
            $event->trigger();
            print_error('1', '', '', $error);
        }
    } catch (Exception $e) {
        if (property_exists($e, 'errorcode')) {
            echo $OUTPUT->notification($e->a);
        } else {
            $errormsg = get_string('errorinservice', 'wiziq');
            $paramslog = array(
                    'objectid' => $courseid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $coursecontext,
                    'other' => array(
                        'error' => $errormsg
                    )
            );
            $event = \mod_wiziq\event\wiziq_classlisting::create($paramslog);
            $event->trigger();
            echo $OUTPUT->notification($errormsg);
        }
    }
}

/**
 * Get the class_id for class to be deleted which is scheduled by soap api.
 *
 * @param interger $wiziq_id the id of wiziq record in the wiziq table.
 * @param integer $courseid the course id from where the class to be deleted
 * @param integer $sessioncode the sessioncode of old classes scheduled by soap api.
 * @param integer $class_id the class id for which class details will be genarated.
 */
function wiziq_get_data_by_sessioncode_delete($wiziq_id, $courseid, $sessioncode, &$class_id) {
    global $CFG, $DB ,$USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "get_data_by_sessionCodes";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["multiple_session_code"] = $sessioncode;
    $requestparameters["columns"] = "class_id";
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=get_data_by_sessionCodes', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = $objdom->attributes();
        if ($attribnode == "ok") {
            $record = $objdom->get_data_by_sessionCodes->record_list->record;
            $class_id = (string) $record->class_id;
            $updates = new stdClass();
            $updates->id = $wiziq_id;
            $updates->class_id = $class_id;
            $DB->update_record('wiziq', $updates);
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $error_code = (string) $objdom->error->attributes()->$code;
            $error_msg = (string) $objdom->error->attributes()->$att; //can be used while debug
            $errormsg = $error_code . " " . $error_msg;
        }
    } catch (Exception $e) {
        echo $e->getMessage();
        $errormsg = get_string('errorinservice', 'wiziq');
    }
}

/**
 * Get the old_content_id array of content and fetch new content id .
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param array $clist_array the old_content_id of old content uploaded by soap api.
 */
function wiziq_get_contentid_update($courseid, $clist_array) {
    global $CFG, $USER, $DB, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_content_webservice;
    require_once("authbase.php");
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_content_webservice = $CFG->wiziq_content_webservice;
     $context = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "listContentIds";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $clist_list = implode(',', $clist_array);
    $requestparameters["page_size"] = WIZIQ_DEFAULT_PAGESIZE;
    $requestparameters["multiple_content_id"] = $clist_list;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_content_webservice . '?method=listContentIds', $requestparameters);
        libxml_use_internal_errors(true);
        $xmldata = new SimpleXmlElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = (string) $xmldata->attributes();
        if ($attribnode == "ok") {
            $get_data = $xmldata->listContentIds->record_list;
            foreach ($get_data->record as $record) {
                $content_id = (string) $record->content_id;
                $wzq_content_id = (string) $record->wzq_content_id;
                $content_ids[$content_id] = $wzq_content_id;
            }
            foreach ($clist_array as $key => $value) {
                /*
                 * we use isset for performance knowing the the
                 * value for particular key will never be null
                 */
                if (isset($content_ids[$value])) {
                    $updates = new stdClass(); //just enough data for updating the submission
                    $updates->id = $key;
                    $updates->contentid = $content_ids[$value];
                    $updates->cid_change_status = '1';
                    $DB->update_record('wiziq_content', $updates);
                }
            }
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $error_code = (string)$xmldata->error->attributes()->$code;
            $error_msg = (string)$xmldata->error->attributes()->$att;
            $paramslog = array(
                    'objectid' => $courseid,
                    'relateduserid' => $USER->id,
                    'courseid' => $courseid,
                    'context' => $context,
                    'other' => array(
                        'sesskey' => sesskey(),
                        'error' => $errormsg
                    )
            );
            $event = \mod_wiziq\event\wiziq_content::create($paramslog);
            $event->trigger();
            $error = $error_code." ".$error_msg;
            print_error('1', '', '', $error);
        }
    } catch (Exception $e) {
        if (property_exists($e, 'errorcode')) {
            echo $OUTPUT->notification($e->a);
        } else {
            $errormsg = get_string('errorinservice', 'wiziq');
           
            echo $OUTPUT->notification($e->getMessage() . '<br />' . $errormsg);
        }
    }
}

/**
 * 
 * 
 * Create Perma Class 
 * 
 * **** */
function wiziq_createPerma($wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $title, $presenter_id, $presenter_name, $vc_language, $create_recording, $attendee_limit, $courseid, &$attribnode, $wiziqclass_id, &$errormsg, &$class_master_id, &$common_perma_attendee_url, &$view_recording_url, $wiziq_datetime, $wiziqtimezone, $class_duration, $intro) {

    global $CFG, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl ,$USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "create_perma_class";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);

  
    $requestparameters['title'] = $title;
    // $requestparameters['presenter_email'] = $_POST['presenter_email'];                
    $requestparameters["presenter_id"] = $presenter_id;
    $requestparameters["presenter_name"] = $presenter_name;
    $requestparameters["course_id"] = $courseid;
    // Optional parameters            
    $requestparameters["attendee_limit"] = $attendee_limit;
    $requestparameters["presenter_default_controls"] = optional_param('presenter_default_controls',null,PARAM_ALPHAEXT);
    $requestparameters["attendee_default_controls"] = optional_param('attendee_default_controls',null,PARAM_ALPHAEXT);
    if (optional_param('recording',null,PARAM_ALPHANUMEXT) == 1) {
        $requestparameters["create_recording"] = 'true';
    } else {
        $requestparameters["create_recording"] = 'false';
    }

    $requestparameters["return_url"] = '';
    $requestparameters["status_ping_url"] = '';
    $requestparameters["language_culture_name"] = $vc_language;


    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=create_perma_class', $requestparameters);

        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);



        $attribnode = (string) $objdom->attributes();

        if ($attribnode == "ok") {
            // print_r($objdom);
            $class_detaial = $objdom->create_perma_class->perma_class_details;
            $common_perma_attendee_url = (string) $class_detaial->common_perma_attendee_url;
            $class_master_id = (string) $class_detaial->class_master_id;
            $view_recording_url = (string) $class_detaial->presenter->presenter_url;
            $params = array(
                'objectid' => $wiziqclass_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => ''
                )
            );
            $event = \mod_wiziq\event\wiziq_classadd::create($params);
            $event->trigger();
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $error_code = (string) $objdom->error->attributes()->$code;
            $error_msg = (string) $objdom->error->attributes()->$att; //can be used while debug
             if($error_code == '1093'){
            $errormsg = "Permanent class feature is not supported";
           } else {
                 $errormsg = $error_code . " " . $error_msg;
           }
           
           $params = array(
                'objectid' => $courseid,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classadd::create($params);
            $event->trigger();
        }//end if
    } catch (Exception $e) {

        // in case no xml is returned
        $errormsg = $e->getMessage() . "<br/>" . get_string('errorinservice', 'wiziq');
         $params = array(
                'objectid' => $courseid,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
        );
        $event = \mod_wiziq\event\wiziq_classadd::create($params);
        $event->trigger();
    }
}

//end function

/**
 * Adds attendee for permanent class . 
 *
 * @param integer $courseid the course id in which the class is scheduled.
 * @param int $class_id the class id for which class attendees will be added.
 * @param int $attendee_id the id of the attendee who will attend the class.
 * @param string $attendee_screen_name screen name for the user attending the class.
 * @param string $language_culture_name language name in which class is scheduled.
 * @param string $attendee_url url generated for the user to attend the class.
 * @param string $errormsg error message if attendee is not added.
 */
function wiziq_addattendeeperma($courseid, $class_master_id, $attendee_id, $attendee_screen_name, $language_culture_name, $perma_class, &$attendee_url, &$errormsg) {
    global $CFG , $DB , $USER;

    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $context = context_course::instance($courseid);
    require_once("authbase.php");

    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $xmlattendee = "<attendee_list>
    <attendee>
    <attendee_id><![CDATA[$attendee_id]]></attendee_id>
    <screen_name><![CDATA[$attendee_screen_name]]></screen_name>
    <language_culture_name><![CDATA[$language_culture_name]]></language_culture_name>
    </attendee>
    </attendee_list>";
    $method = "add_attendees";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);


    $requestparameters["perma_class"] = "true"; //required
    $requestparameters["class_master_id"] = $class_master_id; //required    
    $requestparameters["attendee_list"] = $xmlattendee;
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=add_attendees', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);

        $attribnode = (string) $objdom->attributes();
        if ($attribnode == "ok") {
            $add_attendeexml = $objdom->add_attendees;
            $class_id = (string) $add_attendeexml->class_master_id;
            $attendeelist = $add_attendeexml->attendee_list->attendee;
            foreach ($attendeelist as $attendee) {
                $attendee_id = (string) $attendee->attendee_id;
                $attendee_url = (string) $attendee->attendee_url;
            }
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $error_code = (string) $objdom->error->attributes()->$code;
            $error_msg = (string) $objdom->error->attributes()->$att; //can be used while debug
            $errormsg = $error_code . " " . $error_msg;
            $params = array(
                'objectid' => $class_master_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $context,
                'other' => array(
                    'error' => $errormsg,
                )
            );
            $event = \mod_wiziq\event\wiziq_classdetail::create($params);
            $event->trigger();
        }
    } catch (Exception $e) {
        $errormsg = get_string('errorinservice', 'wiziq'); // in case no xml is returned
        $params = array(
            'objectid' => $class_master_id,
            'relateduserid' => $USER->id,
            'courseid' => $courseid,
            'context' => $context,
            'other' => array(
                'error' => $errormsg,
            )
        );
        $event = \mod_wiziq\event\wiziq_classdetail::create($params);
        $event->trigger();
        print_error($e->getMessage() . "<br/>" . $errormsg);
    }
}

//end function



/*
 * 
 * 
 * Modify Perma Class  
 * 
 */

function wiziq_modifypermaclass($courseid, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $title, $presenter_id, $presenter_name, $perma_class, $vc_language, $create_recording, $attendee_limit, &$attribnode, $wiziqclass_id, &$errormsg, &$class_master_id, &$common_perma_attendee_url, &$view_recording_url, $wiziq_datetime, $wiziqtimezone, $class_duration) {
    global $CFG, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl , $USER;


    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
     $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "modify";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters['perma_class'] = "true";
    $requestparameters["class_master_id"] = "$class_master_id";
    $requestparameters["title"] = "$title";
    $perma_class = $requestparameters['perma_class'];
    // $requestparameters['presenter_email'] = $_POST['presenter_email'];                
    $requestparameters["presenter_id"] = $presenter_id;
    $requestparameters["presenter_name"] = $presenter_name;
    // Optional parameters            
    $requestparameters["attendee_limit"] = $attendee_limit;
  
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=modify', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);

        $attribnode = (string) $objdom->attributes();
        if ($attribnode == "ok") {
            $modify = (string) $objdom->modify;
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $error_code = (string) $objdom->error->attributes()->$code;
            $error_msg = (string) $objdom->error->attributes()->$att; //can be used while debug
            $errormsg = $error_code . " " . $error_msg;
           $params = array(
                'objectid' => $class_master_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classupdate::create($params);
            $event->trigger();
        }
    } catch (Exception $e) {
        $errormsg = get_string('errorinservice', 'wiziq');
         $params = array(
                'objectid' => $class_master_id,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classupdate::create($params);
            $event->trigger();
            print_error($errormsg);
        print_error($errormsg);
    }
}

//end function


/*
 * 
 * 
 *  Wiziq Cancle Perma Class Method
 * 
 */

function wiziq_delete_permaclass($courseid, $class_master_id, $permaclass) {


    global $CFG, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl , $USER;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    // $wiziq_access_key = "eMmJoNlEPoY=";
    //$wiziq_secretacesskey = "23GQMUxQ/QMBiqYIWtogNg==";
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "cancel";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["class_master_id"] = $class_master_id;
    $requestparameters['perma_class'] = "true";
    $permaclass = $requestparameters['perma_class'];
    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=cancel', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);

        $attribnode = (string) $objdom->attributes();

        if ($attribnode == "ok") {
            $cancel = (string) $objdom->cancel->attributes();
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $code = 'code';
            $error_code = (string) $objdom->error->attributes()->$code;
            $error_msg = (string) $objdom->error->attributes()->$att; //can be used while debug
            $errormsg = $error_code . " " . $error_msg;
        }
    } catch (Exception $e) {
        $e->getMessage();
        $errormsg = get_string('errorinservice', 'wiziq');
    }
}

//end function

/*
 * Create Recurring Class
 */

function wiziq_create_recuring($select_monthly_repeat_type, $class_schedule, $monthly_date, $days_of_week, $specific_week, &$wiz_start_time, &$wiziq_presenter_link, $time_zone, $duration, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $title, $start_time, $class_repeat_type, $class_occurrence, $class_end_date, $language_culture_name, $courseid, $intro, $presenter_id, $presenter_name, $recording, &$attribnode, &$wiziqmasterclass_id, &$wiziqclass_id, &$errormsg, &$view_recording_url) {

    global $CFG, $wiziq_secretacesskey, $wiziq_access_key, $wiziq_webserviceurl, $USER;
    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    $coursecontext = context_course::instance($courseid);
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "create_recurring";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["course_id"] = $courseid;
    $requestparameters["title"] = $title; //Required
    $requestparameters["description"] = $intro;
    $requestparameters["presenter_id"] = $presenter_id; // Required
    $requestparameters["presenter_name"] = $presenter_name; //Required
    $requestparameters["start_time"] = date("m/d/Y H:i:s", strtotime($start_time)); //Required

    $requestparameters["class_repeat_type"] = $class_repeat_type; //Required
    // week condition
    if ($class_repeat_type == '4') {
        $requestparameters["days_of_week"] = $days_of_week; //Required
        $requestparameters["specific_week"] = $specific_week; //Required
    }
    // once every month condition by date
    if (($class_repeat_type == '5') && ($select_monthly_repeat_type == 'bydate')) {
        $requestparameters["monthly_date"] = $monthly_date; //Required
        $requestparameters["rdo_by_date"] = 'true'; //Required
    }
    // once every month condition by day
    if (($class_repeat_type == '5') && ($select_monthly_repeat_type == 'byday')) {
        $requestparameters["monthly_date"] = $monthly_date; //Required
        $requestparameters["rdo_by_date"] = 'true'; //Required
        $requestparameters["days_of_week"] = $days_of_week; //Required
    }
    // class schedule condition for occurance and end date
    if ($class_schedule == '4') {
        $requestparameters["class_end_date"] = date("m-d-Y", strtotime($class_end_date)); //Required
    } else {
        $requestparameters["class_occurrence"] = $class_occurrence; //Required
    }
    $requestparameters["app_version"] = $CFG->release; //optional
    $requestparameters["language_culture_name"] = $language_culture_name; //optional
    $requestparameters["create_recording"] = $recording; //optional
    $requestparameters["return_url"] = ""; //optional
    $requestparameters["status_ping_url"] = ""; //optional
    $requestparameters["time_zone"] = $time_zone; //optional
    $requestparameters["duration"] = $duration; //optional

    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=create_recurring', $requestparameters);
        libxml_use_internal_errors(true);
        $objdom = new SimpleXMLElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = (string) $objdom->attributes();
        if ($attribnode == "ok") {
            $class_detaial = $objdom->create_recurring->recurring_class_details;
            $wiziqmasterclass_id = (string) $class_detaial->class_master_id;
            wiziq_view_recur_class($courseid, $wiziqmasterclass_id, $wiziq_classidmaster, $wiziq_recordlink, $wiziq_presenter_link, $wiz_start_time);
            $wiziqclass_id[] = $wiziq_classidmaster;
            $view_recording_url[] = $wiziq_recordlink;
            $wiziq_presenter_link[] = $wiziq_presenter_link;
            $wiz_start_time[] = $wiz_start_time;
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $errormsg = (string) $objdom->error->attributes()->$att; //can be used while debug
            $params = array(
                'objectid' => $courseid,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
            );
            $event = \mod_wiziq\event\wiziq_classadd::create($params);
            $event->trigger();
        }//end if
    } catch (Exception $e) {
        $e->get_message();
        $errormsg = get_string('errorinservice', 'wiziq');
        $params = array(
                'objectid' => $courseid,
                'relateduserid' => $USER->id,
                'courseid' => $courseid,
                'context' => $coursecontext,
                'other' => array(
                    'error' => $errormsg
                )
        );
        $event = \mod_wiziq\event\wiziq_classadd::create($params);
        $event->trigger();
    }
}

//end function

/*
 *  Recurring View Schedule 
 */

function wiziq_view_recur_class($courseid, $wiziq_classmasterid_array, &$wiziq_classidmaster, &$wiziq_recordlink, &$wiziq_presenter_link, &$wiz_start_time) {
    global $CFG , $USER;

    $wiziq_secretacesskey = $CFG->wiziq_secretacesskey;
    $wiziq_access_key = $CFG->wiziq_access_key;
    $wiziq_webserviceurl = $CFG->wiziq_webserviceurl;
    require_once("authbase.php");
    $wiziq_authbase = new wiziq_authbase($wiziq_secretacesskey, $wiziq_access_key);
    $method = "view_schedule";
    $requestparameters["signature"] = $wiziq_authbase->wiziq_generatesignature($method, $requestparameters);
    $requestparameters["page_size"] = WIZIQ_DEFAULT_PAGESIZE;
    $requestparameters["class_master_id"] = $wiziq_classmasterid_array; //Required

    $wiziq_httprequest = new wiziq_httprequest();
    try {
        $xmlreturn = $wiziq_httprequest->wiziq_do_post_request(
                $wiziq_webserviceurl . '?method=view_schedule', $requestparameters);

        libxml_use_internal_errors(true);
        $xmldata = new SimpleXmlElement($xmlreturn, LIBXML_NOCDATA);
        $attribnode = (string) $xmldata->attributes();
        if ($attribnode == "ok") {
            $get_data = $xmldata->view_schedule->recurring_list->class_details;
            foreach ($get_data as $record) {
                $wiziq_classidmaster[] = $record->class_id;
                $wiziq_recordlink[] = $record->recording_url;
                $wiziq_presenter_link[] = $record->presenter_list->presenter->presenter_url;
                $wiz_start_time[] = $record->start_time;
            }
        } else if ($attribnode == "fail") {
            $att = 'msg';
            $errormsg = (string) $xmldata->error->attributes()->$att; //can be used while debug
        }
    } catch (Exception $e) {
        $e->get_message();
        $errormsg = get_string('errorinservice', 'wiziq');
    }
}

// end function

/*
 * get email function
 */

function get_email($courseid, $presenter_id) {
    global $CFG, $DB;
   
 $sql = "SELECT user2.email,user2.firstname FROM {course} AS course ";
    $sql .= "JOIN {enrol} AS en ON en.courseid = course.id ";
    $sql .= "JOIN {user_enrolments} AS ue ON ue.enrolid = en.id ";
    $sql .= "JOIN {user} AS user2 ON ue.userid = user2.id ";
    $sql .= "WHERE  course.id = ?";
    $email_list = $DB->get_records_sql($sql , array($courseid));
    $presenter_email = $DB->get_record('user', array('id' => $presenter_id), 'email', MUST_EXIST);
    return array('user' => $email_list, 'teacher' => $presenter_email);
}

// end function