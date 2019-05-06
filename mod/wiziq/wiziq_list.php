<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * Here all classes are shownt in list form with details and control to manage them like delete, editing, viewrecoring, download recording, attendance report.
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ 
?>
<script type='text/javascript'>window.onerror = handleError; function handleError(){return true;}</script>
<script type="text/javascript" language="javascript" src="http://org.wiziq.com/Common/JS/ModalPopup.js"></script>
<script language="javascript" src="http://org.wiziq.com/Common/JS/jquery.js" type="text/javascript"></script>
<script language="javascript" src="wiziq.js" type="text/javascript"></script>

<style  type="text/css">
.uploadingdiv{border:solid 10px #ccc; background-color:#fff; padding:10px;}
</style>
<link href="http://org.wiziq.com/Common/CSS/ModalPopup.css" rel="stylesheet" type="text/css" />
<link href="http://org.wiziq.com/Common/CSS/thickbox.css" rel="stylesheet" type="text/css" />

<div id="divmodal"  class="modalWindow" style="display: none; width: 500px;">
<div id="dvMod1" class="uploadingdiv" style="height:200px">
   

<div id="close123" style=" text-align:right"><a id="A1" href="javascript:PopupClose();" >Close</a></div>
<iframe id="ifrmDownload" width="470px" height="190px" frameborder="0" scrolling="no" style="font-family:Arial; font-size:12px; color:#444" ></iframe>
</div>
</div>
<img id="modalBackground" class="modalBackground" width="100%" style="display: none; z-index: 3; left: -6px; top: 120px; height: 94%;" alt=""  />
<?php
require_once("../../config.php");
require_once("lib.php");
include("paging.php");
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->dirroot .'/lib/blocklib.php');
require_once($CFG->dirroot.'/calendar/lib.php');
require_once ($CFG->dirroot.'/lib/moodlelib.php');
require_once("wiziqconf.php");
require_once("locallib.php");
//-------------------------Reading the xml file of user---------------------

wiziq_UserAccountDetails($maxdur,$maxuser,$prsenterentry,$privatechat,$recordingcredit,$concurrsession,$creditpending,$subscription_url,$buynow_url,$Package_info_message,$pricing_url);
//----------------setting the limit of no of records shown per page------------
$limit=10; 
 if($_REQUEST['course']<>"")
 {
     $courseid=$_REQUEST['course'];
 }
 else
 {
     $courseid=$_REQUEST['id'];
 }
 require_login();

    $sectionreturn = optional_param('sr', '', PARAM_INT);
    $add           = optional_param('add','', PARAM_ALPHA);
    $type          = optional_param('type', '', PARAM_ALPHA);
    $indent        = optional_param('indent', 0, PARAM_INT);
    $update        = optional_param('update', 0, PARAM_INT);
    $hide          = optional_param('hide', 0, PARAM_INT);
    $show          = optional_param('show', 0, PARAM_INT);
    $copy          = optional_param('copy', 0, PARAM_INT);
    $moveto        = optional_param('moveto', 0, PARAM_INT);
    $movetosection = optional_param('movetosection', 0, PARAM_INT);
    $delete        = optional_param('delete', 0, PARAM_INT);
    $course        = optional_param('course', 0, PARAM_INT);
    $groupmode     = optional_param('groupmode', -1, PARAM_INT);
    $duplicate     = optional_param('duplicate', 0, PARAM_INT);
    $cancel        = optional_param('cancel', 0, PARAM_BOOL);
    $cancelcopy    = optional_param('cancelcopy', 0, PARAM_BOOL);
    $urlcourse = optional_param('course', 0, PARAM_INT);
    if (empty($SITE)) {
        redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
    }

    // Bounds for block widths
    // more flexible for theme designers taken from theme config.php
    $lmin = (empty($THEME->block_l_min_width)) ? 100 : $THEME->block_l_min_width;
    $lmax = (empty($THEME->block_l_max_width)) ? 210 : $THEME->block_l_max_width;
    $rmin = (empty($THEME->block_r_min_width)) ? 100 : $THEME->block_r_min_width;
    $rmax = (empty($THEME->block_r_max_width)) ? 210 : $THEME->block_r_max_width;

    define('BLOCK_L_MIN_WIDTH', $lmin);
    define('BLOCK_L_MAX_WIDTH', $lmax);
    define('BLOCK_R_MIN_WIDTH', $rmin);
    define('BLOCK_R_MAX_WIDTH', $rmax);
    $strwiziq  = get_string("WiZiQ", "wiziq");
    $strwiziqs = get_string("modulenameplural", "wiziq");
    calendar_session_vars();
    $navlinks = array();
    $calendar_navlink = array('name' => $strwiziqs,'','type' => 'misc');

    if($urlcourse > 0 && record_exists('course', 'id', $urlcourse)) 
    {
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
    
        // If we know about the referring course, show a return link and ALSO require login!
        require_login();
        //$nav = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$SESSION->cal_course_referer.'">'.$shortname.'</a> -> '.$nav;
        if (!empty($course)) {
          
            $course = get_record('course', 'id', $course); // Useful to have around
        }
        
    require_login($course, false);
    $navlinks[] = $calendar_navlink;
    $navlinks[] = array('name' => 'WiZiQ Classes', 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    print_header($course->shortname.':'.$strwiziqs,$course->fullname,$navigation,"","", true,"","");
    print_simple_box_start('center', '', '', 5, 'generalbox', '');
    //------------------Getting the timezone of user currently logged in----------
    $timezone=wiziq_GetUserTimezone();
    $moduleid=wiziq_ModuleID();
    if($CFG->dbtype=="mysql")
    {
        $query="SELECT * FROM ".$CFG->prefix."wiziq where id in (select distinct e.instance from ".$CFG->prefix."event e,".$CFG->prefix."course_modules cm WHERE e.instance=cm.instance AND cm.course=".$courseid." AND cm.module=".$moduleid." AND e.name like '%mod/wiziq/icon.gif%') ORDER BY insescod DESC ";
    }
    else if($CFG->dbtype=="mssql_n" || $CFG->dbtype=="sqlsrv")
    {
        $query="Select * from (SELECT ROW_NUMBER() OVER (ORDER BY insescod DESC)AS Row,* FROM ".$CFG->prefix."wiziq where id in (select distinct e.instance from ".$CFG->prefix."event e,".$CFG->prefix."course_modules cm WHERE e.instance=cm.instance AND cm.course=".$courseid." AND cm.module=".$moduleid." AND e.name like '%mod/wiziq/icon.gif%')) as Logas";
    }
    $query=paging_1($query,"","0%",$courseid);
    $result=get_records_sql($query);
    $szXMLNode="";
    $sessiontype=array();
    $sessioncodeArray=array();
    foreach($result as $rn)
	{
            $code=$rn->insescod;
            $szXMLNode=$szXMLNode."<table><sessioncode>".$code."</sessioncode></table>";
        }
    $person = array(
		'CustomerKey'=>$customer_key,
                'szXMLNode'=>'<newdataset>'.$szXMLNode.'</newdataset>',
		 );
    $resultanttt=wiziq_do_post_request($WebServiceUrl.'moodle/class/GetSessionsStatus',http_build_query($person, '', '&'));
    $objDOM=wiziq_ReadXML($resultanttt);
    $Table= $objDOM->getElementsByTagName("table");
    $length =$Table->length;
    $i=1;
    foreach( $Table as $value )
    {
	$j=1;
        $test = $value->getElementsByTagName("sessioncode");
        $SessionCode= $test->item(0)->nodeValue;
        $sessiontype[$i][$j]=$SessionCode;
        $sessioncodeArray[$i]=$SessionCode;
        $test1 = $value->getElementsByTagName("type");
        $type= $test1->item(0)->nodeValue;
        $sessiontype[$i][$j+1]=$type;
        $test2=$value->getElementsByTagName("status");
        $status=$test2->item(0)->nodeValue;
        $sessiontype[$i][$j+2]=$status;
        $test3=$value->getElementsByTagName("isaglivesummarygenerated");
        $IsaGLiveSummaryGenerated=$test3->item(0)->nodeValue;
        $sessiontype[$i][$j+3]=$IsaGLiveSummaryGenerated;
        $test4=$value->getElementsByTagName("movestatus");
        $MoveStatus=$test4->item(0)->nodeValue;
        $sessiontype[$i][$j+4]=$MoveStatus;
        $i++;
    }
    $_SESSION['SessionCode']=$sessioncodeArray;
    //print_r($sessioncodeArray);

?>
<table><tr><td width="180px" align="left" valign="top">
<?php
include("sideblock.php");
?>
</td><td width="800px">
<table border="0" cellpadding="5px" cellspacing="5px" width="100%" >
<tr><td height="30">
<strong>WiZiQ Classes</strong>
<font size="1px">
<p align="right">*Class Date & Time is shown in your Time Zone (<?php echo $timezone ?>)</p>
</font></td></tr>
<tr>
<td align="center" >
<table width="100%" border="1" cellpadding="5px"   cellspacing="5px" align="center" bordercolor="#efefef" >
<tr height="30px" style="background-color:#efefef;">
<td align="left" style="font-size:14px;padding-left:10px;"><strong>Class Name </strong></td>
<td  align="left" style="padding-left:10px;font-size:14px"><strong>Date & Time </strong></td>
<td  align="left" style="padding-left:10px;font-size:14px"><strong>Status</strong></td>
<td align="left" style="padding-left:0px;font-size:14px"><strong>Manage</strong></td>
<td align="left" style="padding-left:10px;font-size:14px"><strong>Actions</strong></td>
</tr>
<?php
if($CFG->dbtype=="mysql")
    {
        $query="SELECT * FROM ".$CFG->prefix."wiziq where id in (select distinct e.instance from ".$CFG->prefix."event e,".$CFG->prefix."course_modules cm WHERE e.instance=cm.instance AND cm.course=".$courseid." AND cm.module=".$moduleid." AND e.name like '%mod/wiziq/icon.gif%') ORDER BY insescod DESC ";
    }
    else if($CFG->dbtype=="mssql_n" || $CFG->dbtype=="sqlsrv")
    {
        $query="Select * from (SELECT ROW_NUMBER() OVER (ORDER BY insescod DESC)AS Row,* FROM ".$CFG->prefix."wiziq where id in (select distinct e.instance from ".$CFG->prefix."event e,".$CFG->prefix."course_modules cm WHERE e.instance=cm.instance AND cm.course=".$courseid." AND cm.module=".$moduleid." AND e.name like '%mod/wiziq/icon.gif%')) as Logas";
    }
$query=paging_1($query,"","0%",$courseid);
$result=get_records_sql($query);
foreach($result as $r)
{
    $result1=wiziq_classesQuery($r->id);
    $eventtype=$result1->eventtype;
    //---------------------------user events shown only for users authorized to see---------------------
    if($eventtype=="user")
    {
	wiziq_classesManageView($sessiontype,$r,$role,$result1,$courseid);
    }
    else if($eventtype=="group") //----------------------group events only for groups allowed to see------------
    {
	$grpflag=1;
        $grpquery=get_records_sql("select groupid,userid from ".$CFG->prefix."groups_members where groupid in(select groupid from ".$CFG->prefix."event where instance=".$r->id.")");
        $while=1;
        foreach($grpquery as $grpresult )
	{
            $grpary[$while]=$grpresult->userid;
            $while++;
	}
	foreach($grpary as $grpuserid)
	{
            if(($grpuserid==$USER->id || $role==1 || $eventuserid==$USER->id )&& $grpflag==1 )
            {
                $grpflag=0;
                wiziq_classesManageView($sessiontype,$r,$role,$result1,$courseid);
            }
        }
    }
    //----------------------site events and course events for users allowed to see---------------
else if($eventtype!="group" && $eventtype!="user" ) 
{
    wiziq_classesManageView($sessiontype,$r,$role,$result1,$courseid);
}
//-------------------------end------------------------------------
}
?>
<tr>
<td colspan="5" align="right"><input type="button" class="txtbox" name="Cancel" value="Go Back" onClick="javascript:location.href='<?php echo $CFG->wwwroot .'/index.php' ?>'"></td></tr><tr><td colspan="5" align="right">
<?php
$str="";
paging_2($str,"0%",$courseid);
?>
</td></tr></table>
</td></tr></table>
</td></tr></table>
<?php 
print_footer();
?>
