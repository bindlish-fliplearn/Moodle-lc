<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * Here Attendance report is generated about attendees in a session
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 require_once("../../config.php");
 require_once("lib.php");
 include("paging.php");
 require_once($CFG->dirroot .'/course/lib.php');
 require_once($CFG->dirroot .'/lib/blocklib.php');
 require_once($CFG->dirroot.'/calendar/lib.php');
 require_once ($CFG->dirroot.'/lib/moodlelib.php');
 require_once("wiziqconf.php");
 require_once("locallib.php");
 $courseid=$_REQUEST['courseid'];
 $SessionCode=$_REQUEST['SessionCode'];
 $course = $courseid;
 $urlcourse = $course;
 if(!$site = get_site())
 {
    redirect($CFG->wwwroot.'/'.$CFG->admin.'/index.php');
 }
 $strwiziq  = get_string("WiZiQ", "wiziq");
 $strwiziqs = get_string("modulenameplural", "wiziq");
 $navlinks = array();
 $calendar_navlink = array('name' => $strwiziqs,'','type' => 'misc');
 if($urlcourse > 0 && record_exists('course', 'id', $urlcourse))
 {
 //require_login($urlcourse, false);
 if($urlcourse == SITEID)
 {
 // If coming from the site page, show all courses
 $SESSION->cal_courses_shown = calendar_get_default_courses(true);
 calendar_set_referring_course(0);
 }
 else
 {
 // Otherwise show just this one
 $SESSION->cal_courses_shown = $urlcourse;
 calendar_set_referring_course($SESSION->cal_courses_shown);
 }
 }
 if ($SESSION->cal_course_referer != SITEID &&
       ($shortname = get_field('course', 'shortname', 'id', $SESSION->cal_course_referer)) !== false) {
        // If we know about the referring course, show a return link and ALSO require login!
        require_login();
        if (!empty($courseid)) {
            $course = get_record('course', 'id', $SESSION->cal_course_referer); // Useful to have around
        }
    } require_login($course, false);
 $navlinks[] = $calendar_navlink;
 $navlinks[] = array('name' => 'WiZiQ Class', 'link' => null, 'type' => 'misc');
 $navigation = build_navigation($navlinks);
 print_header($course->shortname.':'.$strwiziqs,$course->fullname,$navigation,"","", true,"","");
 // getting the info about attendess and class
 function do_post_request($url, $data, $optional_headers = null)
 {
    $params = array('http' => array(
                  'method' => 'POST',
                  'content' => $data
               ));
     if ($optional_headers !== null) {
        $params['http']['header'] = $optional_headers;
     }
     $ctx = stream_context_create($params);
     $fp = @fopen($url, 'rb', false, $ctx);
     if (!$fp) {
        throw new Exception("Problem with $url, $php_errormsg");
     }
     $response = @stream_get_contents($fp);
     if ($response === false) {
        throw new Exception("Problem reading data from $url, $php_errormsg");
     }
     return $response;
  }
$person = array(
    'CustomerKey'=>$customer_key,
    'lnSesCod'=>$SessionCode//'25161',
               );
$result=wiziq_do_post_request($WebServiceUrl.'moodle/class/attendancereport',http_build_query($person, '', '&'));
$objDOM=wiziq_ReadXML($result);
$AttendanceReport = $objDOM->getElementsByTagName("AttendanceReport");
$SessionCode=$AttendanceReport->item(0)->getAttribute('SessionCode');
$status = $objDOM->getElementsByTagName("status");
$status = $status->item(0)->nodeValue;
if($status=="true")
{
?>
<br/>
<p style="font-size:14px; font-weight:bold; padding:0px; margin:0px; margin-bottom:20px">Attendance Report</p>
<table cellpadding="5px" cellspacing="5px" align="center" border="1" width="100%" bordercolor="#efefef">
<tr style="background-color:#efefef;">
<td align="left" style="font-size:12px; font-weight:bold">Attendee Name</td>
<td align="left" style="font-size:12px; font-weight:bold">Class Entry Time</td>
<td align="left" style="font-size:12px; font-weight:bold">Class Exit Time</td>
<td align="left" style="font-size:12px; font-weight:bold">Attended Class Duration</td>
<td align="left" style="font-size:12px; font-weight:bold">Actual Class Duration (in mins)</td>
</tr>
<?php
$SessionMinutesConsumed = $objDOM->getElementsByTagName("SessionMinutesConsumed");
$SessionMinutesConsumed = $SessionMinutesConsumed->item(0)->nodeValue;
$Attendance = $objDOM->getElementsByTagName("Attendance");
foreach( $Attendance as $value )
{
$ScreenName = $value->getElementsByTagName("ScreenName");
$ScreenName = $ScreenName->item(0)->nodeValue;
$IsPresenter = $value->getElementsByTagName("IsPresenter");
$IsPresenter = $IsPresenter->item(0)->nodeValue;
$StartPingTime = $value->getElementsByTagName("StartPingTime");
$StartPingTime = $StartPingTime->item(0)->nodeValue;
$EndPingTime = $value->getElementsByTagName("EndPingTime");
$EndPingTime = $EndPingTime->item(0)->nodeValue;
$AttendeeMinutesConsumed = $value->getElementsByTagName("AttendeeMinutesConsumed");
$AttendeeMinutesConsumed = $AttendeeMinutesConsumed->item(0)->nodeValue;
?>
<tr>
<td align="left" style="font-size:12px"><?php echo $ScreenName; ?></td>
<td align="left" style="font-size:12px"><?php echo $StartPingTime; ?></td>
<td align="left" style="font-size:12px"><?php echo $EndPingTime; ?></td>
<td align="left" style="font-size:12px"><?php echo $AttendeeMinutesConsumed; ?></td>
<td align="left" style="font-size:12px"><?php echo $SessionMinutesConsumed; ?></td>
</tr>
  <?php
  }
?>
<tr>
    <td align="right" colspan="6"> <input type="button" class="txtbox" name="Cancel" value="Go Back to WiZiQ Classes" onClick="javascript:location.href='wiziq_list.php?course=<?php  echo $courseid;?>'"> </td>
  </tr>
</table>
<?php
}
else //if error occured
{
?>
    <br /><br /><br />
    <p align="center" ><font face="Arial, Helvetica, sans-serif" color="#000000" size="5"><strong><img src="icon.gif" hspace="10" height="16" width="16" border="0" alt="" />Error In Attendance Report of WiZiQ Live Class</strong></font></p>
    <?php
	//echo '<strong>Message:</strong> ' .$ErrorMessage;
	$ErrorMessage="Attendance Report can not generated.";
	print_simple_box_start('center', '100%');


    echo '<strong><center><font color="red">'.$ErrorMessage.'</font></center></strong><br><br>';
echo'<a href="javascript:history.go(-1)"><p align="center">Click Here To Go Back</p></a>';
    print_simple_box_end();
}
print_footer();
?>