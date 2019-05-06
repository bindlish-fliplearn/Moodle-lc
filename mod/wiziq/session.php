<br /><br /><br /><br />

<div style="display:block" id="div123">

<p align="center" ><font face="Arial, Helvetica, sans-serif" color="#000000" size="3"><strong><img src="icon.gif" hspace="10" height="16" width="16" border="0" alt="" />We Are Processing Your Request. Please Wait............</strong></font></p></div>
<?php
	/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ’s web based virtual classroom equipped with real-time collaboration tools 
 * Session scheduled here by calling api and records inserted in database
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
require_once ($CFG->dirroot.'/lib/moodlelib.php');
require_once("locallib.php");
	require_once("wiziqconf.php");
	//------------------------- reading the xml file of user---------------------

	wiziq_UserAccountDetails(&$maxdur,&$maxuser,&$presenterentry,&$privatechat,&$recordingcredit,&$concurrsession,&$creditpending,&$subscription_url,&$buynow_url,&$Package_info_message,&$pricing_url);

       $name=$_POST['name'];
       if(!empty($_POST['date']))
       $date=$_POST['date'];
	   else 
	   $date="";
	   if(!empty($_POST['time']))
	   $time=$_POST['time'];
	   else
	   $time="";
       $duration=intval($_POST['duration']); 
       $audio=$_POST['audio'];
	   if($audio=="Video")
	   {
	   	   $waudio="Audio and Video";
	   }
	   if($audio=="Audio")
	   {
	   	   $waudio="Audio";
	   }
	   $type=$_REQUEST['type'];
       if($type=="yes")
	   {
		   $recordingtype="yes"; 
	   }
	   else 
	   {
		   $recordingtype="no";
	   }
                   
	// check timezone of user
	$timezone=wiziq_GetUserTimezone();
	

	//------------------initializing the parameters send to api---------------
	$mm=$_REQUEST['duration'];
	$maxduration=$mm-$maxdur;
	if($presenterentry=="1")
	{
		$entry="true";
	}
    else if($presenterentry=="0")
	{
        $entry="false";
	}
    if ($CFG->forcetimezone != 99)
    {
       $CountryNameTZ=$CFG->forcetimezone;
    } 
    else
       $CountryNameTZ=$USER->timezone;
 
    $ScheduleNow=$_REQUEST['chkNow'];
    
//----------------------sending the request to api of wiziq for scheduling-------------------
  if($ScheduleNow=="checked") //if schedule now is checked
  {
	  $person = array(
	    'TimeZone' => $timezone,
		'CountryNameTZ'=>$CountryNameTZ,
	); 
	 $result=wiziq_do_post_request($WebServiceUrl.'moodle/class/schedulenow',http_build_query($person, '', '&')); 
	 $objDOM=wiziq_ReadXML($result);
	 $DateNow=$objDOM->getElementsByTagName("DateNow");
	 $DateNow=$DateNow->item(0)->nodeValue;
	 $TimeNow=$objDOM->getElementsByTagName("TimeNow");
	 $TimeNow=$TimeNow->item(0)->nodeValue;
	 $ErrorMessage=$objDOM->getElementsByTagName("message");
	 if(!empty($ErrorMessage->item(0)->nodeValue))
	 $ErrorMessage=$ErrorMessage->item(0)->nodeValue; 
     $datetime=wiziq_DateTimeString($DateNow,$TimeNow,&$mm1,&$hh1,$year,$month,$day);     
	

  }
  else
  {
    //-----------------------making the date time string-----------------------
	$datetime=wiziq_DateTimeString($date,$time,&$mm1,&$hh1,&$year,&$month,&$day); 	
  }
  if(empty($ScheduleNow) || (!empty($DateNow) && !empty($TimeNow)) ) //if class scheduled 
  {
  
  $person = array(
		'CustomerKey'=>$customer_key,
		'EventName' => $_REQUEST['name'],
	 	'DateTime' => $datetime,
	    'TimeZone' => $timezone,
	    'Duration' => $_REQUEST['duration'],
	    'UserCode' => $USER->id,
	    'UserName'=>$USER->username,
		'audio' => $audio,							
		'CountryNameTZ'=>$CountryNameTZ,
		'RecodingReplay'=>$recordingtype,
	);
	 $result=wiziq_do_post_request($WebServiceUrl.'moodle/class/schedule',http_build_query($person, '', '&'));

     $objDOM=wiziq_ReadXML($result);
	 $Code=$objDOM->getElementsByTagName("SessionCode");
	 $SessionCode=$Code->item(0)->nodeValue;
	 $PresenterUrl=$objDOM->getElementsByTagName("PresenterUrl");
	 $PresenterUrl=$PresenterUrl->item(0)->nodeValue;
	 $RecordingUrl=$objDOM->getElementsByTagName("RecordingUrl");
	 $RecordingUrl=$RecordingUrl->item(0)->nodeValue;
	 $CommonAttendeeUrl=$objDOM->getElementsByTagName("CommonAttendeeUrl");
	 $CommonAttendeeUrl=$CommonAttendeeUrl->item(0)->nodeValue;
	 $ReviewSessionUrl=$objDOM->getElementsByTagName("ReviewSessionUrl");
	 $ReviewSessionUrl=$ReviewSessionUrl->item(0)->nodeValue;
	 $AttendeeUrls=$objDOM->getElementsByTagName("AttendeeUrls");
	 $AttendeeUrls=$AttendeeUrls->item(0)->nodeValue;
	 $ErrorMessage=$objDOM->getElementsByTagName("message");
	 if(!empty($ErrorMessage->item(0)->nodeValue))
	 $ErrorMessage=$ErrorMessage->item(0)->nodeValue;
  }
	//exit;
if($SessionCode !=-1)
{	
	$event=$_REQUEST['name'];
	$presenterurl=$PresenterUrl;
	$recodingurl=$RecordingUrl;
	$reviewurl=$ReviewSessionUrl;
	$attendeeurl=$CommonAttendeeUrl;
	
	$insescod=$SessionCode;

	require_login();
    $sectionreturn = optional_param('sr', '', PARAM_INT);
    $add           = optional_param('add','', PARAM_ALPHA);
	$modulename    = optional_param('modulename','', PARAM_ALPHA);
	$mode    	   = optional_param('mode','', PARAM_ALPHA);
    $type          = optional_param('type', '', PARAM_ALPHA);
    $indent        = optional_param('indent', 0, PARAM_INT);
    $update        = optional_param('update', 0, PARAM_INT);
    $hide          = optional_param('hide', 0, PARAM_INT);
    $show          = optional_param('show', 0, PARAM_INT);
    $copy          = optional_param('copy', 0, PARAM_INT);
    $moveto        = optional_param('moveto', 0, PARAM_INT);
    $movetosection = optional_param('movetosection', 0, PARAM_INT);
    $delete        = optional_param('delete', 0, PARAM_INT);
    $course1       = optional_param('course', 0, PARAM_INT);
    $groupmode     = optional_param('groupmode', -1, PARAM_INT);
    $duplicate     = optional_param('duplicate', 0, PARAM_INT);
    $cancel        = optional_param('cancel', 0, PARAM_BOOL);
    $cancelcopy    = optional_param('cancelcopy', 0, PARAM_BOOL);
	$eventtype	   = optional_param('eventtype','', PARAM_ALPHA);	
	
	$groupid=explode(",",$_REQUEST['groupid']);
	$courseid=$_REQUEST['courseid'];
	$userid=$_REQUEST['userid'];	
   
  if (isset($SESSION->modform)) {   // Variables are stored in the session
  //echo "1st if";
        $mod = $SESSION->modform;
   
        unset($SESSION->modform);
    } else {
        $mod = (object)$_POST;
    }
    if (!empty($course1) and confirm_sesskey()) 
	{    // add, delete or update form submitted
        if (empty($mod->coursemodule)) 
		{ //add
            if (! $course = get_record("course", "id", $mod->course)) 
			{
                error("This course doesn't exist");
            }
            $mod->instance = '';
            $mod->coursemodule = '';
        } 
		else 
		{ 
			if (! $cm = get_record("course_modules", "id", $mod->coursemodule))
			{
                error("This course module doesn't exist");
            }
            if (! $course = get_record("course", "id", $cm->course))
			{
                error("This course doesn't exist");
            }
            $mod->instance = $cm->instance;
            $mod->coursemodule = $cm->id;
        }

        require_login($course->id); // needed to setup proper $COURSE
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        require_capability('moodle/course:manageactivities', $context);
        $mod->course = $course->id;
        $mod->modulename = clean_param($mod->modulename, PARAM_SAFEDIR);  // For safety
        $addinstancefunction    = $mod->modulename."_add_instance";
        $mode="add";
        switch ($mode) {
            case "add":
                if (!course_allowed_module($course,$modulename))
				{
                    error("This module ($mod->modulename) has been disabled for this particular course");
                }
                if (!isset($mod->name) || trim($mod->name) == '') 
				{
                    $mod->name = get_string("modulename", $modulename);
                }
//--------------------Adding wiziq event--------------------
				$obj2->name=$event;
				$obj2->url=$presenterurl;
				$obj2->attendeeurl=$attendeeurl;
				$obj2->recordingurl=$recodingurl;
				$obj2->reviewurl=$reviewurl;
				$obj2->wtime=$time;
				$obj2->wdur=$duration;
				$obj2->wdate=make_timestamp($year, $month, $day, $hh1, $mm1);
				$obj2->wtype=$waudio;
				$obj2->insescod=$insescod;
				$type=$_REQUEST['type'];
				 if($type=="yes")
				 {
					 $value=1;
				 }
				 else
				 {
					 $value=0;
				 }
				
				$obj2->statusrecording=$value;
				$obj2->timezone=$timezone;
				$obj2->oldclasses='';
				$return = $addinstancefunction($obj2);
               
//-----------------------adding information in wiziq attende info table---------------------------      
				$obj->username=$USER->username;
				$obj->attendeeurl=$presenterurl;
				$obj->insescod=$insescod;
				$obj->userid=$USER->id;
			    $result=wiziq_add_attendeeinfo($obj);

///----------------Adding information in event table-------------------        	   
			if($eventtype=="site")
			{
				$form1->courseid=SITEID;
				$form1->groupid=0;
				$form1->name='<img height="16" width="16" src="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/icon.gif" style="vertical-align: middle;"/>'." ".$name;
				
				
				$form1->description='<input type="text" value="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/view.php" onfocus="this.select();"><br><a href="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/view.php?instance='.$return.'" >View Class details</a>';
				$form1->userid=intval($USER->id);
				$form1->modulename="";//"wiziq";
				$form1->instance=$return;
				$form1->timestart=make_timestamp($year, $month, $day, $hh1, $mm1);
				$form1->timeduration=$_REQUEST['duration']*60;
				$form1->eventtype=$eventtype;
				$form1->format=1;
				$form1->visible=1;
				$eventid = wiziq_add_event($form1);  
			} 
			if($eventtype=='course')
			{
				
						$form1->courseid=$COURSE->id;
						$form1->groupid=0;
						$form1->name='<img height="16" width="16" src="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/icon.gif" style="vertical-align: middle;"/>'." ".$name;
						$form1->description='<input type="text" value="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/view.php" onfocus="this.select();"><br><a href="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/view.php?instance='.$return.'" >View Class details</a>';
						$form1->userid=intval($USER->id);
						$form1->modulename="";//"wiziq";
						$form1->instance=$return;
						$form1->timestart=make_timestamp($year, $month, $day, $hh1, $mm1);
						$form1->timeduration=$_REQUEST['duration']*60;
						$form1->eventtype=$eventtype;
						$form1->format=1;
						$form1->visible=1;
						$eventid = wiziq_add_event($form1); 
						
			}
			if($eventtype=='user')
			{
	
				
						$form1->courseid=0;
						$form1->groupid=0;
						$form1->name='<img height="16" width="16" src="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/icon.gif" style="vertical-align: middle;"/>'." ".$name;
						$form1->description='<input type="text" value="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/view.php" onfocus="this.select();"><br><a href="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/view.php?instance='.$return.'" >View Class details</a>';
						$form1->userid=$USER->id;
						$form1->modulename="";//"wiziq";
						$form1->instance=$return;
						$form1->timestart=make_timestamp($year, $month, $day, $hh1, $mm1);
						$form1->timeduration=$_REQUEST['duration']*60;
						$form1->eventtype=$eventtype;
						$form1->format=1;
						$form1->visible=1;
						$eventid = wiziq_add_event($form1); 
						
					
			}
			if($eventtype=='group')
			{
				foreach($groupid as $value)
				{
					if($value!="")
					{
					$form1->groupid=$value;
					$form1->courseid=$COURSE->id;
					$form1->name='<img height="16" width="16" src="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/icon.gif" style="vertical-align: middle;"/>'." ".$name;
					$form1->description='<input type="text" value="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/view.php" onfocus="this.select();"><br><a href="'.$CFG->wwwroot.'/mod/'.$mod->modulename.'/view.php?instance='.$return.'" >View Class details</a>';
					$form1->userid=intval($USER->id);
					$form1->modulename="";//"wiziq";
					$form1->instance=$return;
					$form1->timestart=make_timestamp($year, $month, $day, $hh1, $mm1);
					$form1->timeduration=$_REQUEST['duration']*60;
					$form1->eventtype=$eventtype;
					$form1->format=1;
					$form1->visible=1;
					$eventid = wiziq_add_event($form1);  
					}
				}
			}

                if (!$return)
				{
                    error("Could not add a new instance of $mod->modulename", "view.php?id=$course->id");
                }

                $mod->instance = $return;
                if (! $mod->coursemodule = add_course_module($mod) )
				{
                    error("Could not add a new course module");
                }
                if (! $sectionid = add_mod_to_section($mod) ) 
				{
                    error("Could not add the new course module to that section");
                }
                if (! set_field("course_modules", "section", $sectionid, "id", $mod->coursemodule)) 
				{
                    error("Could not update the course module with the correct section");
                }
                if (!isset($mod->visible)) 
				{   // We get the section's visible field status
                    $mod->visible = get_field("course_sections","visible","id",$sectionid);
                }
                // make sure visibility is set correctly (in particular in calendar)
                set_coursemodule_visible($mod->coursemodule, $mod->visible);
                if (isset($mod->redirect)) 
				{
                    $SESSION->returnpage = $mod->redirecturl;
                } 
				else 
				{
                    $SESSION->returnpage = "$CFG->wwwroot/mod/$mod->modulename/view.php?instance=$return&type=$value";
                }

                add_to_log($course->id, "course", "add mod",
                           "../mod/$mod->modulename/view.php?id=$mod->coursemodule",
                           "$mod->modulename $mod->instance");
                add_to_log($course->id, $mod->modulename, "add",
                           "view.php?id=$mod->coursemodule",
                           "$mod->instance", $mod->coursemodule);
                break;
            
            default:
                error("No mode defined");

        }

        rebuild_course_cache($course->id);
    }
	
	if (isset($return))
	{
		 $SESSION->returnpage = "$CFG->wwwroot/mod/$mod->modulename/view.php?id=$mod->coursemodule";
		 redirect("$CFG->wwwroot/mod/$mod->modulename/view.php?id=$mod->coursemodule");
	}
    else 
	{
         error("No action was specfied");
    }

 }
 
 else{ // if error occured
 $flag="none";

echo ' <script language="javascript" type="text/javascript">
		var chk =  "'.$flag.'" ;
		if(chk == "none")
		{
			document.getElementById("div123").style.display="none";
		}
				
		</script>;  ';
 

//    print_header("WizIQ class", "Error In Scheduling WizIQ Live Class");
	
	?>
    <br /><br /><br />
    <p align="center" ><font face="Arial, Helvetica, sans-serif" color="#000000" size="5"><strong><img src="icon.gif" hspace="10" height="16" width="16" border="0" alt="" />Error In Scheduling WiZiQ Live Class</strong></font></p>
    <?php
		
	print_header("WizIQ class", "");
    
	print_simple_box_start('center', '100%');

    
    echo '<strong><center><font color="red">'.$ErrorMessage.'</font></center></strong><br><br>';
echo'<a href="javascript:history.go(-1)"><p align="center">Click Here To Go Back</p></a>';
    print_simple_box_end();
    print_footer();   
  //  echo("in error this is wat have to check");
 }
?>