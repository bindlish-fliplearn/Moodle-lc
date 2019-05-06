<!--/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * This page is for editing the class details
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */-->
<script language="javascript" src="wiziq.js" type="text/javascript"></script>
<link href="main.css" rel="stylesheet" type="text/css">
<?php
if(!empty($_REQUEST['str']))
$str=$_REQUEST['str'];
if(!empty($_REQUEST['date']))
$date=$_REQUEST['date'];
$eventtype=$_REQUEST['eventtype'];
$type=$_REQUEST['type'];
if($type=="yes"||$type==1)
{
     $flag=1;
}
if($type=="no"||$type==0)
{
     $flag=0;
}

    require_once("../../config.php");
    require_once("lib.php");
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->dirroot.'/calendar/lib.php');
    require_once("wiziqconf.php");
    require_once("locallib.php");
    wiziq_UserAccountDetails(&$maxdur,&$maxuser,&$presenterentry,&$privatechat,&$recordingcredit,&$concurrsession,&$creditpending,&$subscription_url,&$buynow_url,&$Package_info_message,&$pricing_url);
    $view = optional_param('view', 'upcoming', PARAM_ALPHA);
    $day  = optional_param('cal_d', 0, PARAM_INT);
    $mon  = optional_param('cal_m', 0, PARAM_INT);
    $yr   = optional_param('cal_y', 0, PARAM_INT);
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // wizq ID

    $urlcourse = optional_param('course', 0, PARAM_INT);
     $id=$_REQUEST['id'];
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
 	//Initialize the session variables
    calendar_session_vars();

    //add_to_log($course->id, "course", "view", "view.php?id=$course->id", "$course->id");
    
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
        $nav = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$SESSION->cal_course_referer.'">'.$shortname.'</a> -> '.$nav;
        if (empty($course)) {
            $course = get_record('course', 'id', $SESSION->cal_course_referer); // Useful to have around
        }
    }

    $strcalendar = get_string('calendar', 'calendar');
    $prefsbutton = calendar_preferences_button();

/// Print the page header
    $strwiziqs = get_string("modulenameplural", "wiziq");
    $strwiziq  = get_string("WiZiQ", "wiziq");

   $navlinks = array();
    $calendar_navlink = array('name' => $strwiziqs,'link' =>'','type' => 'misc');
    $navlinks[] = $calendar_navlink;
    $navlinks[] = array('name' => $wiziq->name, 'link' => "", 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    print_header($course->shortname.":".$wiziq->name, $course->fullname,
                 $navigation,"", "", true,"","");

    echo calendar_overlib_html();
     // Layout the whole page as three big columns.
    echo '<table id="calendar" style="height:100%;">';
    echo '<tr>';

    // START: Main column

    /// Print the main part of the pageecho $user;
  echo '<td class="maincalendar">';
  echo '<div class="heightcontainer">';
  $todaydate=usergetdate(time());
  
  if($todaydate['hours']>12)
  {
  $hours=intval($todaydate['hours'])-12;
  $ampm="PM";
  }
  else
  {
  $hours=$todaydate['hours'];
  $ampm="AM";
  }
  $DatetimeUser=$todaydate['mon']."/".$todaydate['mday']."/".$todaydate['year']." ".$hours.":".$todaydate['minutes']." ".$ampm ;
  echo '<input type="hidden" id="MoodleDateTime" value="'.$DatetimeUser.'" />';
  echo '<input type="hidden" id="statusrecording" value="'.$wiziq->statusrecording.'"/><input type="hidden" id="maxDuration" value="'.$maxdur.'"/>';
  $usr = $USER->username;
  $email = $USER->email;
  $times=$wiziq->wdate;
  $timezone=$wiziq->timezone;
  $id=$USER->id;

  $wtime=calendar_time_representation($times);
  //--------converting the date time in moodle timezone----------
  $wdate= wiziq_DateOfClass($times);
  //----------------------getting the timezone of user currently logged in-----------------
  $timezone=wiziq_GetUserTimezone();
  //-------------------------Finding the roleid of user in current course----------
  $role=wiziq_GetUserRole($course->id);
  $insescod=$wiziq->insescod;
  $eventid=$wiziq->id;
  //checking if admin allow attendee or student to record class
  if($flag==1)
  {
	$status="checked";
  }
  else if($flag==0)
  {
	$status="unchecked";
  }
  if ($flag==null)
  {
	$f=$wiziq->statusrecording;
	if($f==1)
	{
		$status="checked";
	}
	if($f==0)
	{
		$status="unchecked";
	}
  }

  echo '<table width="100%">
  <tr><td valign="top" align="left" >
  <span style="margin-top:-0px; float:left">';
  include("sideblock.php");
  echo '</span></td><td>';
  if($role=='3' || $role=='2') // Role 3 is for Teacher
  {
    echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <form name="view"  action="checkupdate.php">
    <tr >
    <td><table width="100%" border="0" cellspacing="0" cellpadding="10">
	<tr><td colspan="2" valign="top" align="left" style="font-weight:bold">Edit WiZiQ Live Class</td></tr>
        <tr>
        <td width="20%" align="right" valign="middle" class="m_12b585858">Type of Event: </td>
        <td colspan="2" align="left" valign="middle" class="m_12b">'.$eventtype.'</td>
        </tr>
     <tr>
         <td width="20%" align="right" valign="middle" class="m_12b585858"><strong>Title</strong>:</td>
        <td  colspan="2" align="left" valign="middle" class="m_12b"><input name="name" type="text" class="m_textinput" id="name" onblur="validate_mod_wiki_mod_form_name(this)" onchange="validate_mod_wiki_mod_form_name(this)"  value="'.$wiziq->name.'" style="width:225px"/><div id="id_name"></div></td>
        </tr>
              <tr>
                <td width="20%" align="right" valign="middle" class="m_12b585858"><strong>Date</strong>:</td>
        <td colspan="2" align="left" valign="middle" class="m_12b"><input name="date" type="text" class="m_textinput" id="date" value="'.$wdate.'" readonly="true" style="width:225px"/><a href="javascript:var cal2 = new calendar2(document.view.date);cal2.popup();">&nbsp;<img src="cal.gif" width="16" height="16" border="0" alt="Click Here to Pick up the date" /></a><div id="id_date"></div></td>
              </tr>
              <tr>
                <td align="right" valign="top" class="m_12b585858"  style="padding-top:17px"><strong>Time</strong>:</td>
       <td colspan="2" align="left" valign="middle" class="m_12b"><div>e.g. 6:30am or 4 PM</div><input name="time" class="m_textinput" type="text" onblur="IsValidTime(this)" onchange="IsValidTime(this)" id="time" value="'.$wtime.'" style="width:225px"/><div id="id_time"></div></td>
              </tr>

              <tr>
                <td align="right" valign="top" class="m_12b585858"  style="padding-top:17px"><strong>Duration</strong>:</td>
        <td colspan="2" align="left" valign="middle" class="m_12b"><div><span style="font-size:10px">(max. '.$maxdur.' mins )</span></div><input name="duration" class="m_textinput" type="text" onblur="validate_mod_wiki_mod_form_duration(this)" onchange="validate_mod_wiki_mod_form_duration(this)" id="duration" value="'.$wiziq->wdur.'" maxlength="3" style="width:225px"/><div id="id_duration"></div>
       </td>
              </tr>
			   <tr>
			   <td width="20%" align="right" valign="middle" class="m_12b585858"><strong>Timezone</strong>:</td>
        <td colspan="2" align="left" valign="middle" class="m_12b">'.$timezone.'</td>
			 </tr>
              <tr>
                <td width="20%" align="right" valign="middle" class="m_12b585858"><strong>Type</strong>:</td>
        <td colspan="2" align="left" valign="middle" class="m_12b585858"><input name="audio" type="radio" id="video" value="Video" />
		    Audio and Video <input name="audio" id="audio" type="radio" value="Audio" checked="checked"/>
		    Audio</td><td><script language="javascript" type="text/javascript">
		var chk = "'.$wiziq->wtype.'";
		if(chk == "Audio")
		{
			document.getElementById("audio").checked = "true";
		}
		else
		{
				document.getElementById("video").checked = "true";
		}

		</script></td></td>
              </tr>
              <tr>
                <td width="20%" align="right" valign="middle" class="m_12b585858"><strong>Record this class</strong>:</td>
			<td colspan="2" align="left" valign="middle" class="m_12b585858">
			<input id="rdtypeyes" name="chkRecording" type="radio" value="yes" />Yes
					  <input id="rdtypeno" name="chkRecording" type="radio" value="no" />No
					  </td>
              </tr>
      <tr><td width="20%"></td>
	  <td  colspan="2" align="left" valign="middle" ><input type="submit" name="update" value="Update" id="Update" onclick="return validate_mod_wiki_mod_form(this.form)"/><input type="hidden" value="'.$eventid.'" name="eventid"/>
	  <a href="javascript:history.go(-1)" ><span class="ulink" style="margin-left:13px">Cancel</span></a></td>
	  </tr>

    </table></td>
  </tr>
    <tr><td>
	   <script language="javascript" type="text/javascript">
		var chk = "'.$status.'";
		if(chk == "unchecked")
		{
			document.getElementById("rdtypeno").checked = "true";
		}
		else
		{
				document.getElementById("rdtypeyes").checked = "true";
		}
		</script>

	  </td></tr>
   </form>
  </table>';
 ?>
  <div style="width:550px; float:left;">
   <iframe  src="package_message.php" id="remote_iframe_1" name="remote_iframe_1" style="border:0;padding:0;margin:0;width:520px;height:125px;overflow:auto" frameborder=0 scrolling="no" onload=" " ></iframe>
                         </div>
                         <?php

  }

  if($role=='1') // for admin
  {
     echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">
     <form name="view" id="form1" action="checkupdate.php">
     <tr >
     <td><table width="100%" border="0" cellspacing="0" cellpadding="10">

      <tr><td colspan="2" valign="top" align="left" style="font-weight:bold">Edit WiZiQ Live Class</td></tr>
        <tr>
        <td width="20%" align="right" valign="middle" class="m_12b585858">Type of Event: </td>
        <td colspan="2" align="left" valign="middle" class="m_12b">'.$eventtype.'</td>
        </tr>
      <tr>
         <td width="20%" align="right" valign="middle" class="m_12b585858"><span style="font-weight:bold; font-size:14px">*</span>Title:</td>
        <td  colspan="2" align="left" valign="middle" class="m_12b"><input name="name" type="text" class="m_textinput" id="name"  onblur="validate_mod_wiki_mod_form_name(this)" onchange="validate_mod_wiki_mod_form_name(this)"  value="'.$wiziq->name.'" style="width:225px" /><div id="id_name"></div>

		</td>

		</tr>
              <tr>
                <td width="20%" align="right" valign="middle" class="m_12b585858"><span style="font-weight:bold; font-size:14px">*</span>Date:</td>
        <td colspan="2" align="left" valign="middle" class="m_12b"><input name="date" type="text" class="m_textinput" id="date"  value="'.$wdate.'" readonly="true" style="width:225px"/><a href="javascript:var cal2 = new calendar2(document.view.date);cal2.popup();">&nbsp;<img src="cal.gif" width="16" height="16" border="0" alt="Click Here to Pick up the date" /></a><div id="id_date"></div></td>
              </tr>
              <tr>
                <td width="20%"  align="right" valign="top" class="m_12b585858"  style="padding-top:17px"><span style="font-weight:bold; font-size:14px">*</span>Time:</td>
        <td colspan="2" align="left" valign="middle" class="m_12b"><div>e.g. 6:30am or 4 PM</div><input name="time" class="m_textinput" type="text" onblur="IsValidTime(this)" onchange="IsValidTime(this)" id="time" value="'.$wtime.'" style="width:225px"/><div id="id_time"> </div></td>
              </tr>

              <tr>
                <td width="20%"  align="right" valign="top" class="m_12b585858"  style="padding-top:17px"><span style="font-weight:bold; font-size:14px">*</span>Duration:</td>
        <td colspan="2" align="left" valign="middle" class="m_12b"><div><span style="font-size:10px">(max. '.$maxdur.' mins )</span></div><input name="duration" class="m_textinput" type="text" onblur="validate_mod_wiki_mod_form_duration(this)" onchange="validate_mod_wiki_mod_form_duration(this)" id="duration" value="'.$wiziq->wdur.'" maxlength="3" style="width:225px"/><div id="id_duration"></div>
       </td>
              </tr>
			   <tr>
			   <td width="20%" align="right" valign="middle" class="m_12b585858"><strong>Timezone</strong>:</td>
        <td colspan="2" align="left" valign="middle" class="m_12b">'.$timezone.'</td>
			 </tr>
              <tr>
                <td width="20%" align="right" valign="middle" class="m_12b585858"><strong>Type</strong>:</td>
        <td colspan="2" align="left" valign="middle" class="m_12b"><input name="audio" type="radio" id="video" value="Video" />
		    Audio and Video <input name="audio" id="audio" type="radio" value="Audio" checked="checked"/>
		    Audio</td><td><script language="javascript" type="text/javascript">
		var chk = "'.$wiziq->wtype.'";
		if(chk == "Audio")
		{
			document.getElementById("audio").checked = "true";
		}
		else
		{
				document.getElementById("video").checked = "true";
		}

		</script></td></td>
              </tr>
              <tr>
                <td width="20%" align="right" valign="middle" class="m_12b585858"><strong>Record this class</strong>:</td>
			<td colspan="2" align="left" valign="middle" class="m_12b">

				<input id="rdtypeyes" name="chkRecording" type="radio" value="yes" />Yes
				<input id="rdtypeno" name="chkRecording" type="radio" value="no" />No

			</td>
              </tr>
            <tr><td width="20%"  ></td>
	  <td  colspan="2" align="left" valign="middle" ><input type="submit" name="update" value="Update" id="Update" onclick="return validate_mod_wiki_mod_form(this.form);"/><input type="hidden" value="'.$eventid.'" name="eventid"/>
	  <a href="javascript:history.go(-1)" ><span class="ulink" style="margin-left:13px">Cancel</span></a></td>
	  </tr>
    </table></td>
  </tr>
  <tr><td>
	   <script language="javascript" type="text/javascript">
		var chk = "'.$status.'";
		if(chk == "unchecked")
		{
			document.getElementById("rdtypeno").checked = "true";
		}
		else
		{
				document.getElementById("rdtypeyes").checked = "true";
		}

		</script>

	  </td></tr>


  </form>

</table>';
?>
<div style="width:550px; float:left;">
   <iframe  src="package_message.php" id="remote_iframe_1" name="remote_iframe_1" style="border:0;padding:0;margin:0;width:520px;height:125px;overflow:auto" frameborder=0 scrolling="no" onload=" " ></iframe>
                         </div>
                         <?php

}
echo '</td>
</tr>
</table>';
 echo '</div>';
    echo '</td>';

    // END: Main column
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
