<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * Here request send to api for updating the class details
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("wiziqconf.php");
require_once("locallib.php");
//--------------getting the users xnl-----------------
wiziq_UserAccountDetails(&$maxdur,&$maxuser,&$presenterentry,&$privatechat,&$recordingcredit,&$concurrsession,&$creditpending,&$subscription_url,&$buynow_url,&$Package_info_message,&$pricing_url);

$ini = ini_set("soap.wsdl_cache_enabled", 0);
require_once("../../config.php");
require_once("lib.php");
require_once($CFG->dirroot."/course/lib.php");
require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once ($CFG->dirroot.'/lib/blocklib.php');
require_once ($CFG->dirroot.'/lib/moodlelib.php');
require_once("locallib.php");
$id=$_REQUEST['eventid'];

$wiziq=get_record("wiziq","id",$id);
$sesscode=$wiziq->insescod;
$timezone=wiziq_GetUserTimezone();
//---------------logic for date time string for updation of class---------------

$dur=$_REQUEST['duration'];
$name=$_REQUEST['name'];
$date=$_REQUEST['date'];
$time=$_REQUEST['time'];
$datetime=wiziq_DateTimeString($date,$time,&$mm1,&$hh1,&$year,&$month,&$day);
$wdate=make_timestamp($year, $month, $day, $hh1, $mm1);
$recordingtype="";
$usr = $USER->username;
$audio=$_REQUEST['audio'];
 if($audio=="Video")
 {
 $wtype="Audio and Video";
 }
 if($audio=="Audio")
 {
 $wtype="Audio";
 }
if(!empty($_REQUEST['chkRecording']))
 $recordingtype=$_REQUEST['chkRecording'];
 if($recordingtype=="yes")
 {
     $value="1";
 }
 else
 {
     $value="0";
 }
 if ($CFG->forcetimezone != 99)
 {
     $CountryNameTZ=$CFG->forcetimezone;
 } 
 else
 $CountryNameTZ=$USER->timezone;
// sending the request to api for updating the class			
if(!empty($_REQUEST['old']) && $_REQUEST['old']=="oldclass")
  {
         $person = array(
                'CustomerKey'=>$customer_key,
		'SessionCode'=>$sesscode,
		'RecodingReplay'=>$recordingtype
                       );
  }
  else
  {
        $person = array(
		'CustomerKey'=>$customer_key,
		'SessionCode'=>$sesscode,
		'EventName' => $name,
	 	'DateTime' => $datetime,
                'TimeZone' => $timezone,
                'Duration' => $dur,
		'RecodingReplay'=>$recordingtype,
		'CountryNameTZ'=>$CountryNameTZ,
		'audio' => $audio
		      );
  }

$result=wiziq_do_post_request($WebServiceUrl.'moodle/class/modify',http_build_query($person, '', '&'));
	
  	 $objDOM =wiziq_ReadXML($result);
	 $Status=$objDOM->getElementsByTagName("Status");
	 $Status=$Status->item(0)->nodeValue;
	 $message=$objDOM->getElementsByTagName("message");
	 if(!empty($message->item(0)->nodeValue))
	 $message=$message->item(0)->nodeValue;
if($Status=="True") // if it is updated then updating the db
{
    $moduleid=wiziq_ModuleID();
 $result="select id from ".$CFG->prefix."course_modules where instance=".$id." and module=".$moduleid;
 $r=get_record_sql($result);
 $cmid=$r->id;

 $str="Class updated successfully";
 if(!empty($_REQUEST['old']) && $_REQUEST['old']=="oldclass")
 {
	$param=array('statusrecording'=>$value,'id'=>$id);
	$query=(object)$param;
	$result=update_record('wiziq',$query); 
 }
 else
 {
	$param=array('statusrecording'=>$value,'name'=>$name,'wtime'=>$time,'wdur'=>$dur,'wdate'=>$wdate,'wtype'=>$wtype,'timezone'=>$timezone,'id'=>$id);
	$query=(object)$param;
	$result=update_record('wiziq',$query);

	$eventquery="select id from ".$CFG->prefix."event where instance=".$id." and name like '%mod/wiziq/icon.gif%'";
	$eventresult=get_record_sql($eventquery);

	$param1=array('name'=>'<img height="16" width="16" src="'.$CFG->wwwroot.'/mod/wiziq/icon.gif" style="vertical-align: middle;"/>'.' '.$name,'timestart'=>$wdate,'timeduration'=>$dur*60,'id'=>$eventresult->id);
	
	$query1=(object)$param1;
	$result1=update_record('event',$query1);
 }
 redirect("view.php?id=$cmid&type=$value&str=$str");
 }
 else // if error occured
 {
 ?>

 <br /><br /><br />
    <p align="center" ><font face="Arial, Helvetica, sans-serif" color="#000000" size="5"><strong><img src="icon.gif" hspace="10" height="16" width="16" border="0" alt="" />Error In Updating WiZiQ Live Class</strong></font></p>
    <?php
	
	print_header("WizIQ class", "");
    
	print_simple_box_start('center', '100%');

    
    echo '<strong><center><font color="red">'.$message.'</font></center></strong><br><br>';
    echo'<a href="javascript:history.go(-1)"><p align="center">Click Here To Go Back</p></a>';
    print_simple_box_end();
    print_footer();   
}
	?>