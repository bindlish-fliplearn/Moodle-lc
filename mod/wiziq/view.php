<script type="text/javascript" language="javascript" src="http://org.wiziq.com/Common/JS/ModalPopup.js"></script>
<script language="javascript" src="http://org.wiziq.com/Common/JS/jquery.js" type="text/javascript"></script>
<script language="javascript" src="wiziq.js" type="text/javascript"></script>
 <link href="http://org.wiziq.com/Common/CSS/ModalPopup.css" rel="stylesheet" type="text/css" />
  <link href="http://org.wiziq.com/Common/CSS/thickbox.css" rel="stylesheet" type="text/css" />
<link href="main.css" rel="stylesheet" type="text/css">
<div id="divmodal"  class="modalWindow" style="display: none; width: 500px;">
    <div id="dvMod1" class="uploadingdiv" style="height:200px">
       <div id="close1" style="text-align:right;"><a id="A1" href="javascript:PopupClose();" >Close</a></div>
          <iframe id="ifrmDownload" width="470px" height="190px" frameborder="0" scrolling="no" style="font-family:Arial; font-size:12px; color:#444" ></iframe>

    </div>
 </div>
<img id="modalBackground" class="modalBackground" width="100%" style="display: none; z-index: 3; left: -6px; top: 120px; height: 94%;" alt=""  />
<?php

// $Id: view.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * This page prints a particular instance of wizq
 *
 * @wiziq
 * @version $Id: view.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
 * @package wizq
 **/

/// (Replace wizq with the name of your module)
 if(!empty($_REQUEST['str']))
 $str=$_REQUEST['str'];
 if(!empty($_REQUEST['date']))
 $date=$_REQUEST['date'];

    	require_once("../../config.php");
    	require_once("lib.php");
      	require_once($CFG->dirroot.'/course/lib.php');
    	require_once($CFG->dirroot.'/calendar/lib.php');
	require_once("wiziqconf.php");
	require_once("RoleView.php");
	require_once("locallib.php");
	wiziq_UserAccountDetails(&$maxdur,&$maxuser,&$presenterentry,&$privatechat,&$recordingcredit,&$concurrsession,&$creditpending,&$subscription_url,&$buynow_url,&$Package_info_message,&$pricing_url);
        $view = optional_param('view', 'upcoming', PARAM_ALPHA);
        $day  = optional_param('cal_d', 0, PARAM_INT);
        $mon  = optional_param('cal_m', 0, PARAM_INT);
        $yr   = optional_param('cal_y', 0, PARAM_INT);
        $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
        $a  = optional_param('a', 0, PARAM_INT);  // wizq ID
	$instance = optional_param('instance', 0, PARAM_INT);
	if($instance!=0)
	{
	   $moduleid=wiziq_ModuleID();
           $r=get_record_sql("select id from ".$CFG->prefix."course_modules where module=".$moduleid." and instance=".$instance);
           $id=$r->id;
	}
	if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        } 
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
        if (! $wiziq = get_record("wiziq", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } 

    require_login($course->id);

    add_to_log($course->id, "wiziq", "view", "view.php?id=$cm->id", "$wiziq->id");
    // Initialize the session variables
    calendar_session_vars();

    if (empty($USER->id) or isguest()) {
        $defaultcourses = calendar_get_default_courses();
        calendar_set_filters($courses, $groups, $users, $defaultcourses, $defaultcourses);

    } else {
        calendar_set_filters($courses, $groups, $users);
    }

    // Let's see if we are supposed to provide a referring course link
    // but NOT for the "main page" course
    if ($SESSION->cal_course_referer != SITEID &&
       ($shortname = get_field('course', 'shortname', 'id', $SESSION->cal_course_referer)) !== false) {
        // If we know about the referring course, show a return link and ALSO require login!
        require_login();
        //$nav = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$SESSION->cal_course_referer.'">'.$shortname.'</a> -> '.$nav;
        if (empty($course)) {
            $course = get_record('course', 'id', $SESSION->cal_course_referer); // Useful to have around
        }
    }

    /// Print the page header

    $strwiziqs = get_string("modulenameplural", "wiziq");
    $strwiziq  = get_string("WiZiQ", "wiziq");
    $navlinks = array();
    $calendar_navlink = array('name' => $strwiziqs,'link' =>'','type' => 'misc');
    $navlinks[] = $calendar_navlink;
    $navlinks[] = array('name' => $wiziq->name, 'link' => "", 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    print_header($course->shortname.":".$wiziq->name, $course->fullname,$navigation,"", "", true,"","");
    echo calendar_overlib_html();
    // Layout the whole page as three big columns.
    echo '<table id="calendar" style="height:100%;">';
    echo '<tr>';

    // START: Main column

    /// Print the main part of the pageecho $user;
 echo '<td class="maincalendar">';
    echo '<div class="heightcontainer">';
  
$usr = $USER->username;
$email = $USER->email;
$times=$wiziq->wdate;
$timezone=$wiziq->timezone;
$wtime=calendar_time_representation($times);
$wdate= wiziq_DateOfClass($times);
$timecheck=wiziq_DateTimeCheck($times);
$timezone=wiziq_GetUserTimezone();

$f=$wiziq->statusrecording;
if($f==1)
{
	$status="Yes";
}
if($f==0)
{
	$status="No";
}
$role=wiziq_GetUserRole($course->id);
$insescod=$wiziq->insescod;
$eventid=$wiziq->id;

//checking if admin allow attendee or student to record class

$rs=get_record_sql("select eventtype,userid from ".$CFG->prefix."event where instance=".$wiziq->id." and name like '%mod/wiziq/icon.gif%'");
$eventtype=$rs->eventtype;
$_eventType=wiziq_EventTypeString($eventtype);

$eventuserid=$rs->userid;


$f=$wiziq->statusrecording;
if($USER->id==1)
{
$role='6';
}

echo '<table width="100%">';
if($role==1 || $role==2 || $role==3 )
{
echo '<tr><td valign="top" align="left">
<span style="margin-top:-0px; float:left">';include("sideblock.php");
echo '</span>
</td>';
}
echo '<td align="left" style="width:650px">';
$classRoleView=new ClassView_Role();
$classRoleView->_className=$wiziq->name;
$classRoleView->_classType=$_eventType;
$classRoleView->_classDate=$wdate;
$classRoleView->_classTime=$wtime;
$classRoleView->_classTimeZone=$timezone;
$classRoleView->_classDuration=$wiziq->wdur;
$classRoleView->_classAudioVideo=$wiziq->wtype;
$classRoleView->_classStatus=$status;
$classRoleView->_classPresenterLink=$wiziq->url;
$classRoleView->_classAttendeeLink=$wiziq->attendeeurl;
$classRoleView->_classRecordingLink=$wiziq->recordingurl;
$classRoleView->_eventUserID=$eventuserid;
$classRoleView->_roleID=$role;
$classRoleView->_timeCheck=$timecheck;
$classRoleView->_sessionCode=$insescod;
$classRoleView->_eventID=$eventid;
$classRoleView->_ID=$id;
$classRoleView->_courseID=$COURSE->id;
$classRoleView->_userID=$USER->id;

if($eventtype=='site')
{
switch($role)
{
case('6'):// Role 6 is for guest
{
echo '<div>';
$classRoleView->StudentRole();
echo '</div>';
break;
}

case('4'): // non-editing teacher
{
echo '<div>';
$classRoleView->StudentRole();
echo '</div>';
break;
}

case('5'):// Role 5 is for student
{
echo '<div>';
$classRoleView->StudentRole();
echo '</div>';
break;
}

case('2'):// Role 2 is fcourse creator
{
echo '<div>';
$classRoleView->TeacherRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;
}
case('3'): // Role 5 is for Teacher
{
echo '<div>';
$classRoleView->TeacherRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;
}
case('1'):
{
echo '<div>';
$classRoleView->AdminRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;
}
}
}
else if($eventtype=='course')
{
switch($role)
{
case('6'):// Role 6 is for guest
{
echo '<div>';
$classRoleView->StudentRole();
echo '</div>';
break;
}

case('4'): // non-editing teacher
{
echo '<div>';
$classRoleView->StudentRole();
echo '</div>';
break;
}

case('5'):// Role 5 is for student
{
echo '<div>';
$classRoleView->StudentRole();
echo '</div>';
break;
}

 case('2'):// Role 2 is fcourse creator
{
echo '<div>';
$classRoleView->TeacherRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;

}
case('3'): // Role 5 is for Teacher
{
echo '<div>';
$classRoleView->TeacherRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;
}
case('1'):
{
echo '<div>';
$classRoleView->AdminRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;
}
}
}
else if($eventtype=='group')
{
$grpary[]=array();
$grpflag=1;
$grpquery=get_records_sql("select groupid,userid from ".$CFG->prefix."groups_members where groupid in(select groupid from ".$CFG->prefix."event where instance=".$wiziq->id.")");
$i=1;
foreach($groupsrs as $grpresult )
{
	$grpary[$i]=$grpresult->userid;
	$i++;
}

	  foreach($grpary as $grpuserid)
		  {

if(($grpuserid==$USER->id || $USER->id==2 || $eventuserid==$USER->id )&& $grpflag==1 )
{
	$grpflag=0;
	switch($role)
{
case('6'):// Role 6 is for guest
{
echo '<div>';
$classRoleView->StudentRole();
echo '</div>';
break;
}

case('4'): // non-editing teacher
{
echo '<div>';
$classRoleView->StudentRole();
echo '</div>';
break;
}

case('5'):// Role 5 is for student
{
echo '<div>';
$classRoleView->StudentRole();
echo '</div>';
break;
}

 case('2'):// Role 2 is fcourse creator
{
echo '<div>';
$classRoleView->TeacherRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;

}
case('3'): // Role 5 is for Teacher
{
echo '<div>';
$classRoleView->TeacherRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;
}
case('1'):
{
echo '<div>';
$classRoleView->AdminRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;
}
}
}

}
if($grpflag==1 )
{
?>
<div><strong><center><font color="red"><p>You are not authorized to view this class.</p></font></center></strong><br><br>
<a href="javascript:history.go(-1)"><p align="center">Click Here To Go Back</p></a></div>
<?php
}
}
else if($eventtype=='user')
{

if($USER->id==$eventuserid || $USER->id==2)
{
switch($role)
{
case('6'):// Role 6 is for guest, Role 4 is for non-editing teacher,Role 5 is for student
case('4'):
case('5'):
{
echo '<div>';
$classRoleView->StudentRole();
echo '</div>';
break;
}

case('2'):// Role 2 is course creator, Role 3 is for teacher
case('3'):
{
echo '<div>';
$classRoleView->TeacherRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;
}
case('1'):
{
echo '<div>';
$classRoleView->AdminRole();
$classRoleView->iFrameLoad();
echo '</div>';
break;
}
}
}
else
{
?>
<br /><br />
<div><strong><center><font color="red"><p>You are not authorized to view this class.</p></font></center></strong>
<a href="javascript:history.go(-1)"><p align="center">Click Here To Go Back</p></a></div>
<?php
}

}

echo '</td>
</tr>
</table>';
 echo '</div>';
    echo '</td>';

    // START: Last column (3-month display)
echo '<td class="sidecalendar">';
    echo '<div class="header">'.get_string('monthlyview', 'calendar').'</div>';

    list($prevmon, $prevyr) = calendar_sub_month($mon, $yr);
    list($nextmon, $nextyr) = calendar_add_month($mon, $yr);
    $getvars = 'id='.$course->id.'&amp;cal_d='.$day.'&amp;cal_m='.$mon.'&amp;cal_y='.$yr; // For filtering

   echo '<div class="filters">';
    echo calendar_filter_controls($view, $getvars);
    echo '</div>';

    echo '<div class="minicalendarblock">';
    echo calendar_top_controls('display', array('id' => $course->id, 'm' => $prevmon, 'y' => $prevyr));
    echo calendar_get_mini($courses, $groups, $users, $prevmon, $prevyr);
   echo '</div><div class="minicalendarblock">';
    echo calendar_top_controls('display', array('id' => $course->id, 'm' => $mon, 'y' => $yr));
    echo calendar_get_mini($courses, $groups, $users, $mon, $yr);
   echo '</div><div class="minicalendarblock">';
    echo calendar_top_controls('display', array('id' => $course->id, 'm' => $nextmon, 'y' => $nextyr));
    echo calendar_get_mini($courses, $groups, $users, $nextmon, $nextyr);
    echo '</div>';

    echo '</td>';

    echo '</tr></table>';
	
/// Finish the page
    print_footer($course);

	

?>
