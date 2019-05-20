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
 * English strings for wiziq
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_wiziq
 * @copyright  www.wiziq.com 
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'WizIQ Live Class';
$string['pluginname'] = 'wiziq';
$string['modulenameplural'] = 'Wiziq Classes';
$string['modulename_help'] = 'The wiziq module enables you to schedule a class. You can schedule online classes, give high impact presentations and watch class recordings with a single click from inside of Moodle';
$string['wiziqfieldset'] = 'Custom example fieldset';
$string['wiziqname'] = 'Title';
$string['vc_class_timezone'] = 'Timezone';
$string['wiziq_duration'] = 'Duration (in Minutes)';
$string['wiziqname_help'] = 'Enter the class title';
$string['wiziq'] = 'wiziq';
$string['generalconfig'] = 'General configuration';
$string['presenter_id'] = 'Select teacher';
$string['scheduleforother'] = 'Schedule for other';
$string['webserviceurl'] = 'Webservice URL';
$string['webserviceurl_desc'] = 'This web service is used to interact with WizIQ server for scheduling classes.';
$string['access_key'] = 'Access Key';
$string['access_key_desc'] = '<p style="color:red">Do not have WizIQ key. Get these <a href="https://www.wiziq.com/api/" target="_blank">here</a>.</p> This is required to authenticate user. We strongly recommend that you don\'t share or change these keys';
$string['secretacesskey'] = 'Secret Access Key';
$string['secretacesskey_desc'] = '<p style="color:red">Do not have WizIQ key. Get these <a href="https://www.wiziq.com/api/" target="_blank">here</a>.</p> This is required to authenticate user. We strongly recommend that you don\'t share or change these keys';
$string['vc_language_xml'] = 'Language xml';
$string['vc_language_xml_desc'] = 'This allows you to choose from various supported languages of the virtual classroom. We strongly recommend that you don’t change this.';
$string['timezone_xml'] = 'Wiziq TimeZone xml';
$string['presenter_required'] = 'Presenter required';
$string['timezone_xml_desc'] = 'Description about Wiziq TimeZone xml';
$string['explaingeneralconfig'] = 'API Credentials:- Required for authentication';
$string['discription'] = 'Description About Class';
$string['setting_discription'] =  '<p>WiZiQ is an online learning and teaching platform that connects educators and students through its virtual classroom technology. WizIQ empowers anyone to deliver live classes, share presentations, PDFs, virtual whiteboards, voice, video, and even their desktops over the Internet, in real time, with only a web browser - No downloads or cumbersome client software required. Hand off audio-video, whiteboard, and desktop sharing controls to users to increase engagement or respond to questions posed via integrated chat.</p>

<p>WizIQ Virtual Classroom integrates with Moodle to create new capabilities for synchronous learning - all from within the LMS environment. Teachers can schedule and launch live sessions from within Moodle, via WizIQ virtual classroom. Scheduled sessions get listed automatically on the Moodle course calendar. Students can easily join their teachers’ live classroom sessions and learn in real-time.</p>

<p>Visit http://www.wiziq.com for information on Enterprise support</p>';
$string['pluginadministration'] = 'wiziq administration';

$string['wiziqdatetimesetting'] = 'Set Timing of the Class.';
$string['wiziq_datetime'] = 'Date and time.';
$string['schedule_for_now'] = 'Schedule for right now';
$string['schedule_for_now_help'] = 'Check it if you want to schedule class for current time';
$string['classtype'] = 'Permanent Class';
$string['classtype_help'] = 'A permanent class does not end unless you choose to end it . It can be display at any time.';
$string['wiziqclasssettings'] = 'Setting of Wiziq Class.';
$string['duration'] = 'Duration of class.';
$string['duration_error'] = 'Can only be number';
$string['vc_language'] = 'Virtualclass language';
$string['audio'] = 'Audio';
$string['writing'] = 'Writing';
$string['record'] = 'Yes';
$string['dontrecord'] = 'No';
$string['recording_option'] = 'Record this class';
$string['select_class_type'] = 'Date and Time';
$string['schedule_class_type'] = 'Time-based Class';
$string['perma_class_type'] = 'Permanent Class';
$string['recordingtype'] = 'Recording Option';
$string['duration_number'] = 'Must be a valid number';
$string['duration_req'] = 'Must enter a number';
$string['scheduleforself'] = 'Schedule for Self';
$string['scheduleforself_help'] = 'Admin can update class to be schedule for himself';
$string['wiziq_content_webservice'] = 'Content Webservice URL';
$string['wiziq_content_webservice_desc'] = 'This is used to upload content in the virtual classroom. We strongly recommend that you don’t change this.';
$string['wiziq:addinstance'] = 'Add a new wiziq. Only for Moodle 2.3';
$string['wiziq_recur_datetime_req'] = 'Start Time Required';
$string['wiziq_recur_class_repeat_req'] = 'Class Repeat Frequency Required';
$string['class_final_req'] = 'Class Schedule Required';

#========================================error Msgs
$string['wrongtime'] = 'Cannot schedule class for past time';
$string['wrongduration'] = 'Duration should be between 30 minutes to 300 minutes';

$string['namerequired'] = 'Please add class title';
$string['errormsg'] = 'this is a schedule time error';
$string['error_in_update'] = 'There was error while updating Your class.<br />Please Try Again.';
$string['error_in_curl'] = 'Please enable curl extention in php.ini file.';
$string['error_in_langread'] = 'Unable to read Language Xml.';
$string['error_in_timeread'] = 'Unable to read Timezone Xml.';
$string['error_in_downloadrec'] = 'There is some error in downloading the Recording.';
$string['error_in_languagexml'] = 'Check your Settings. Unable to read Language Xml';
$string['error_in_timezonexml'] = 'Check your Settings. Unable to read Timezone Xml';
$string['deletefromwiziq'] = 'Deleted from WizIQ';
$string['errorinservice'] = 'Unable to send request to Wiziq API check Wiziq Settings. Data shown not valid';
$string['errormsg_session_missing'] = 'Session Code missing Contact WizIQ support about this class';
$string['unable_to_get_url'] = 'Url missing';
$string['parent_not_fould'] = 'Parent folder not found';
$string['recnotcreatedyet'] = 'Download Recording not available yet';
#==================================================================help
$string['duration_help'] = 'Duration of the class should be in minutes. Minimum duration is 30 minutes and maximum is 300 minutes. You can extend duration of the class from with-in the virtual class-room';
$string['vc_language_help'] = 'By default language in virtual class-room is En-US, you can change language by selecting language from dropdown menu';
$string['scheduleforother_help'] = 'By default class schedules for admin. By mark check in checkbox, you can schedule the class for your teachers aswell by selecting teachers from dropdown menu';
$string['wiziq_datetime_help'] = 'Select the date and time for class. You can-not schedule class for past time. Don not add day-light saving time to this time';
$string['recordingtype_help'] = 'By default class scheduled is recorded class, if you do not wants to record class then select "No" option provided';
$string['vc_class_timezone_help'] = 'Select the time-zone for which you want to schedule the class';
$string['wiziq_recur_class_repeat_type_help'] = 'Repeat type of class. Values can be from 1 to 5. 1 for daily(All 7 days), 2 for 6 days(Mon-Sat), 3 for 5 days(Mon-Fri), 4 for Weekly, 5 for Once every month';
$string['class_schedule_help'] = 'Number of recurring classes to schedule. Required only when class_end_date parameter is not given.';
$string['class_occurrence_help'] = 'Number of recurring classes to schedule. Required only when class_end_date parameter is not given.';
$string['assesstimefinish_help'] = 'End date of recurring series. Required only when class_occurrence parameter is not given.';
$string['specific_week_help'] = 'Number of weeks after which class repeats .';
$string['days_of_week_help'] = 'Days of week to schedule class ';
$string['monthly_date_help'] = 'Monthly date to schedule class  ';
#==========================view table
$string['classviewdetail'] = 'Details of class';
$string['presenter_name'] = 'Teacher ';
$string['teacher_you'] = 'You ';
$string['wiziq_start_time'] = 'Timing of Class ';
$string['join_class'] = '<b>Join Class</b>';
$string['wiziq_class_timezone'] = 'Time-Zone ';
$string['status_of_class'] = 'Class Status ';
$string['language_name'] = 'Language in ClassRoom ';
$string['recording_value'] = 'Recording opted ';
$string['new_attendee'] = '<b>You are viewing the class for First Time</b>';
$string['create_recording_true'] = 'Yes';
$string['create_recording_false'] = 'No';
$string['update_class'] = '<b>Edit Class</b>';
$string['delete_class'] = '<b>Delete Class</b>';
$string['schedule_class'] = 'Schedule Class';
$string['manage_classes'] = 'Manage Classes';
$string['manage_content'] = 'Manage Content';
$string['fetchdata_upgarde'] = 'Fetch Data Upgrade';
$string['launch_class'] = '<b>Launch Class</b>';
$string['recmsg'] = '<b>Recording will be available in sometime</b>';
$string['creatingrecording'] = 'Creating Recording';
$string['classwithoutrec'] = 'Recording Not Opted';
$string['viewclassnotheld'] = 'Class not held';
$string['classnotheld'] = '';
$string['timezone_required'] = 'Timezone required';
$string['wiziq_start_time_class'] = 'Start Time';
$string['class_repeat_type'] = 'Select when class repeats';
$string['class_occurrence'] = 'Class occurrence';
$string['class_end_date'] = 'Class end date';
$string['class_schedule'] = 'Class schedule';
$string['recurring_class_type'] = 'Recurring Class';
$string['wiziqrecurringclasssettings'] = 'Wiziq Recurring Class Settings';
$string['specific_week'] = 'Specific Week';
$string['days_of_week'] = 'Days Of Week';
$string['select_monthly_repeat_type'] = 'Select monthly repeat type';
$string['monthly_date'] = 'Monthly date';
#=========================manage class page=============
$string['week'] = 'Week';
$string['name'] = 'Class title';
$string['date_time'] = 'Date-Time';
$string['presenter'] = 'Presenter';
$string['status'] = 'Status';
$string['manage'] = 'Manage Class';
$string['links'] = 'Links';
$string['manage_classes_file'] = 'List Of Class For Course';
$string['wiziq_classes_file'] = 'wiziq_listing_for_course';
$string['download_recording'] = '<b>Download-Recording</b>';
$string['no_download_recording'] = '<b>Creating-Recording</b>';
$string['creating_dnld_rcd'] = 'Recording In Progress';
$string['recording_link'] = 'Download Recording';
$string['viewrec'] = 'View Recording';
$string['class_id'] = 'Class ID';
$string['per_page_classes'] = 'Classes Per Page ';
$string['refresh_page'] = 'Click here to get latest status';
$string['attendance_report'] = '<b>Attendance Report</b>';
$string['view_recording'] = '<b>View-Recording</b>';
$string['nowiziqs'] = 'No Wiziq class has been created in this course';
$string['attendencereport'] = '<b>Attendance Report</b>';
$string['attendee_name'] = 'Attendee Name';
$string['entry_time'] = 'Entry Time';
$string['exit_time'] = 'Exit Time';
$string['attended_minutes'] = 'Attended Time';
$string['attendence_file'] = 'Attendence List For Class';
$string['wiziq_attendence_file'] = 'wiziq_attendence_for_class';
$string['editconfirm'] = 'Are you sure to edit class';
$string['deleteconfirm'] = 'Are you sure to delete class';
$string['deleteconfirmcontent'] = 'Are you sure to delete';
$string['nocapability'] = 'Don\'t have capability';
$string['wiziq:view_attendance_report'] = 'Capability to view attendance report';
$string['wiziq:wiziq_download_rec'] = 'Capability to download class recording';
$string['wiziq:wiziq_view_rec'] = 'Capability to view class recording';
$string['wiziq:wiziq_content_upload'] = 'Capability to upload content';
$string['per_page_content'] = 'Content Per Page';
$string['allowed_content'] = '<b>Allowed Content Type</b>';
$string['uploaderror'] = 'Error in uploading Content';
$string['content_delete'] = 'Delete';
$string['subcontenterror'] = 'Delete inner content first';
$string['datatempered'] = 'Data changed';
$string['unable_to_delete'] = 'Problem in deleting content';
$string['unable_to_create'] = 'Problem in creating folder';
$string['no_delete_xml'] = 'No xml returned on deleting content';
$string['errorcrtingfolder'] = 'Error in creating folder';
$string['errorinfileupload'] = 'Error in Uploading File';
$string['folder_alrdy_exist'] = 'already exist at this level';
$string['foldernamestring'] = 'Folder name';
$string['error_in_fileext'] = 'Upload allowed file type';
$string['inprogress'] = 'Inprogress';
$string['available'] = 'Available';
$string['contentfail'] = 'Failed';
$string['notknown'] = 'Not known';
$string['nameheading'] = 'Name';
$string['deleteheading'] = 'Delete';
$string['wiziq_content'] = 'Wiziq Content';
$string['wiziq_classes'] = 'Wiziq Classes';
$string['wiziq_class'] = 'Wiziq Class';
$string['wiziq_attendancereport'] = 'Wiziq attendance report for ';


$string['perma_class'] = 'Perma Class';
$string['presenter_email'] = 'Presenter Email';
$string['dusername'] = 'Username';
$string['dtime'] = 'Time';
$string['download_pagetitle'] = 'Download User Details';
$string['wiziq_headtag'] = 'User Records';
$string['download_file'] = 'User List For Class download';
$string['wiziq_download_file'] = 'wiziq_users_for_class';
$string['wiziq_emailsetting'] = 'Enable Email Notification';






