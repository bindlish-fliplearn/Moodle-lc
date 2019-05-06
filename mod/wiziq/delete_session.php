<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * Here request is send to api for cancellation of class
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$timestamp=strtotime(now);
require_once("../../config.php");
require_once("lib.php");
require_once($CFG->dirroot."/course/lib.php");
require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once ($CFG->dirroot.'/lib/blocklib.php');
require_once("wiziqconf.php");
require_once("locallib.php");
//-------------------reading the users xml------------------
wiziq_UserAccountDetails(&$maxdur,&$maxuser,&$presenterentry,&$privatechat,&$recordingcredit,&$concurrsession,&$creditpending,&$subscription_url,&$buynow_url,&$Package_info_message,&$pricing_url);
$course = optional_param('course', 0, PARAM_INT);
$urlcourse = optional_param('course', 0, PARAM_INT);
if(!$site = get_site())
{
  redirect($CFG->wwwroot.'/'.$CFG->admin.'/index.php');
}
$strwiziq  = get_string("WiZiQ", "wiziq");
$strwiziqs = get_string("modulenameplural", "wiziq");
$navlinks = array();
$calendar_navlink = array('name' => $strwiziqs,'','type' => 'misc');
if($urlcourse > 0 && record_exists('course', 'id', $urlcourse)) {
//require_login($urlcourse, false);
if($urlcourse == SITEID) {
// If coming from the site page, show all courses
$SESSION->cal_courses_shown = calendar_get_default_courses(true);
calendar_set_referring_course(0);
}
else {
// Otherwise show just this one
$SESSION->cal_courses_shown = $urlcourse;
calendar_set_referring_course($SESSION->cal_courses_shown);
     }
}
if ($SESSION->cal_course_referer != SITEID &&
($shortname = get_field('course', 'shortname', 'id', $SESSION->cal_course_referer)) !== false) {
// If we know about the referring course, show a return link and ALSO require login!
require_login();
if (!empty($course)) {
$course = get_record('course', 'id', $SESSION->cal_course_referer); // Useful to have around
}
    }
require_login($course, false);
$navlinks[] = $calendar_navlink;
$navlinks[] = array('name' => 'Delete WiZiQ Class', 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);
print_header($course->shortname.':'.$strwiziqs,$course->fullname,$navigation,"","", true,"","");
print_simple_box_start('center', '', '', 5, 'generalbox', "");
$aid="";
$insid="";
$eid="";
$courseid="";
$flag="";
if(!empty($_REQUEST['aid']))
$aid=$_REQUEST['aid'];
if(!empty($_REQUEST['insid']))
$insid=$_REQUEST['insid'];
if(!empty($_REQUEST['eid']))
$eid=$_REQUEST['eid'];
$courseid=$urlcourse;
if(!empty($_REQUEST['type']))
$type=$_REQUEST['type'];
else
$type=0;
if(!empty($_REQUEST['flag']))
$flag=$_REQUEST['flag'];
$sessionkey=$USER->sesskey;

$r=get_records_sql("select *  from ".$CFG->prefix."wiziq where id=$aid");

 $pinsescod=$r->insescod;
 $peventname=$r->name;
 $purl=$r->url;
 $pattendeeurl=$r->attendeeurl;
 $precordingurl=$r->recordingurl;
 $previewurl=$r->reviewurl;
 $times=$r->wdate;
 $wtime=$r->wtime;
 
if($flag!=1) //first time post to delete the content
{
include_once("confirm_delete.php");
}
$wdate=calendar_day_representation($times, $now = false, $usecommonwords = true);
$date=str_replace("-","/",$wdate);
$dattime= $date." ".$wtime;
$dattime=strtoupper($dattime);
if($flag==1) //second time post after confirmation
{
	// sending request to api to delete class
$person = array(
        'CustomerKey'=>$customer_key,
        'lnSesCod' => $pinsescod
               );

 $result=wiziq_do_post_request($WebServiceUrl.'moodle/class/delete',http_build_query($person, '', '&'));
 $objDOM =wiziq_ReadXML($result);
 $Deleted=$objDOM->getElementsByTagName("Status");
 $Deleted=$Deleted->item(0)->nodeValue;
 $message=$objDOM->getElementsByTagName("message");
 $message=$message->item(0)->nodeValue;
 if($Deleted=="true")
 {

 }
if( $type=='0' && $Deleted==true) // if deleted
{
        delete_records('wiziq', 'id',$aid);
	delete_records('event', 'id',$eid);
	delete_records('course_modules', 'id',$insid);
  
  echo '<p align="center" ><font face="Arial, Helvetica, sans-serif" color="#000000" size="3"><strong><img src="icon.gif" hspace="10" height="16" width="16" border="0" alt="" />We Are Processing Your Request. Please Wait............</strong></font></p>';
  redirect("welcome_delete.php?id=".$courseid);
}
else //if error occured
{
?>
<table width="70%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center" > <br /><br /><br />
    <p align="center" ><font face="Arial, Helvetica, sans-serif" color="#0066CC" size="5"><strong>The Class can not be deleted....</strong></font></p> </td>
  </tr>
  <tr>
  <td ><?php echo '<strong>Message:</strong> ' .$message; ?></td>
  </tr>
  <tr>
    <td align="center"> <input type="button" class="txtbox" name="Cancel" value="Go to class list" onClick="javascript:location.href='wiziq_list.php?course=<?php echo $courseid;?>'"> </td>
  </tr>
</table>
<?php
}
}
print_footer();
?>








