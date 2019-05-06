<body>

<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ’s web based virtual classroom equipped with real-time collaboration tools 
 * Given for adding attendees while entering in the class
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
		require_once("../../config.php");
    	require_once("lib.php");
      	require_once($CFG->dirroot.'/course/lib.php');
    	require_once($CFG->dirroot.'/calendar/lib.php');
		require_once("wiziqconf.php");
		require_once("locallib.php");
		//---------------reading the users xml---------------------------
		wiziq_UserAccountDetails($maxdur,$maxuser,$prsenterentry,$privatechat,$recordingcredit,$concurrsession,$creditpending,$subscription_url,$buynow_url,$Package_info_message,$pricing_url);
//------------------logic to check if user is presenter or not for secure login--------------
$SessionCode=$_REQUEST['SessionCode'];
$rs=get_record_sql("select * from ".$CFG->prefix."wiziq_attendee_info where userid=".$USER->id." and insescod=".$SessionCode);

$presenterURL=$rs->attendeeurl;
$screenName=$rs->username;
if(empty($presenterURL))
{
	$screenName = $USER->username;
	$person=array(
				 
				  'CustomerKey'=>$customer_key,
				  'AttendeeListXML'=>'<AddAttendeeToSession><SessionCode>'.$SessionCode.'</SessionCode><Attendee> <ID>'.$USER->id.'</ID><ScreenName>'.$screenName.'</ScreenName></Attendee></AddAttendeeToSession>'
				  );
	// sending request to add attendee in class when he just enter in the class

 $attendeURL=wiziq_do_post_request($WebServiceUrl.'moodle/class/addattendees',http_build_query($person, '', '&'));

$objDOM=wiziq_ReadXML($attendeURL);
$status = $objDOM->getElementsByTagName("status");
$status= $status->item(0)->nodeValue;
$message = $objDOM->getElementsByTagName("message");
$message= $message->item(0)->nodeValue;
if($status=="true")
{
$gchild=$objDOM->getElementsByTagName("Attendee");
$presenterURL=$gchild->item(0)->getAttribute('Url');	
}
}
if($presenterURL!="" || !empty($presenterURL))
{
	echo '<script language="javascript" type="text/javascript">
	window.location = \''.$presenterURL.'\';
	</script>';
}
else // if error occured
{
	$strwiziq  = get_string("WiZiQ", "wiziq");
	$strwiziqs = get_string("modulenameplural", "wiziq");
	$navlinks = array();
	$navlinks[] = array('name' => 'WiZiQ Classes', 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);
	
	print_header($SITE->shortname.':'.$strwiziqs,$strwiziqs,$navigation, "","", true,"","");
	print_simple_box_start('center', '', '', 5, 'generalbox', $module->name);
?>
    <br /><br /><br />
    <p align="center" ><font face="Arial, Helvetica, sans-serif" color="#000000" size="5"><strong><img src="icon.gif" hspace="10" height="16" width="16" border="0" alt="" />Error In Entering WiZiQ Live Class</strong></font></p>
    <?php
	
	print_header("WizIQ class", "");
    
	print_simple_box_start('center', '100%');

    
    echo '<strong><center><font color="red">'.$message.'</font></center></strong><br><br>';
//echo'<a href="javascript:history.go(-1)"><p align="center">Click Here To Go Back</p></a>';
    print_simple_box_end();
    print_footer($course);  	
}
?>

</body>