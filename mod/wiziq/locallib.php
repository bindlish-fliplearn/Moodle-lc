<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * Given for having the WiZiQ functions
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /*Function for TimeZone display of logged in user */
 function wiziq_GetUserTimezone()
 {  
		 global $CFG,$USER;
			//$timezones = get_list_of_timezones();
				
		 if ($CFG->forcetimezone != 99)
			$tmzone=$CFG->forcetimezone;
		 else 
			$tmzone=$USER->timezone;
				

		 if(!is_numeric($tmzone))
		 {
			if ($CFG->forcetimezone != 99)
				$timezone=$CFG->forcetimezone;
			else
				$timezone=$USER->timezone;
		 }
		 else
		 {
                     if($tmzone==99)
                     {
                        $timezone="GMT+12:00";

                     }
                     else
                     {
			// check timezone
			if(substr($tmzone,0,1)=="-")
			{
			    $TZ=substr(substr($tmzone,1),0,strrpos($tmzone, ".")-1);
			   if($TZ<10)
			    $timezone="GMT-"."0".$TZ.substr($tmzone,strrpos($tmzone, "."));
			   else
			    $timezone="GMT-".$TZ.substr($tmzone,strrpos($tmzone, "."));
			}
			else
			{
			    $TZ=substr($tmzone,0,strrpos($tmzone, ".")); 
			   if($TZ<10)
			    $timezone="GMT+"."0".$TZ.substr($tmzone,strrpos($tmzone, "."));
			   else
			    $timezone="GMT+".$TZ.substr($tmzone,strrpos($tmzone, "."));
			}
		  
                         $indexof=strrpos($timezone, ".");
                         if($indexof>0)
                         {
                            $timezone=str_replace(".",":",$timezone);
                            $timezone=$timezone."0";
                        }
                    }
                 }
		 //echo $apiTimeZone=$timezone;
		 //echo $suffix=str_replace("5","3",substr($timezone,strrpos($timezone, ":")+1));
		 //echo $prefix=substr($timezone,0,strrpos($timezone, ":")+1);
		 //echo $TimeZoneToShow=$prefix.$suffix;
		 return $timezone;
 }

 function wiziq_GetUserRole($courseid)
 {
	    global $CFG,$USER,$DB;
		if($USER->id==2)
		{
			$role=1;	
		}
		else if($USER->id==1)
		{
			$role=6;
		}
		else
		{
			$query="select ra.id,ra.roleid from ".$CFG->prefix."context,".$CFG->prefix."role_assignments ra where ".$CFG->prefix."context.id=ra.contextid and ra.userid=".$USER->id." and (".$CFG->prefix."context.instanceid=".$courseid ." or ".$CFG->prefix."context.instanceid=". 0 .")";
			$query1=get_records_sql($query);
			
			$i=0;
			foreach($query1 as $rows)
			{
			$resultant[$i]= $rows->roleid;
			$i++;
			}
			
			sort($resultant);
			$role= $resultant[0]; 
		}
		return $role;
 }
 
 function wiziq_Header($courseid)
 {
	
	return "<span style='float:left; width:80px;margin-left:20px;  border-right:solid 1px #ddd;font-size:12px;font-family:Arial, Helvetica, sans-serif;'><img src='pix/icon.gif' align='absbottom'/>&nbsp;WiZiQ</span> <span style='float:left; width:120px;margin-left:20px;  border-right:solid 1px #ddd;font-size:12px '><a href='event.php?course=$courseid&section=0&add=wiziq'>Schedule a Class</a></span><span style='float:left; width:120px;margin-left:20px;  border-right:solid 1px #ddd;font-size:12px '> <a href='wiziq_list.php?course=$courseid'>Manage Classes</a></span><span style='float:left; width:120px;margin-left:20px; font-size:12px' > <a href='managecontent.php?course=$courseid'>Manage Content</a></span>";
 }
 
 function wiziq_UserAccountDetails(&$maxdur,&$maxuser,&$presenterentry,&$privatechat,&$recordingcredit,&$concurrsession,&$creditpending,&$subscription_url,&$buynow_url,&$Package_info_message,&$pricing_url)
 {
	 include("wiziqconf.php");
	 
	$content = file_get_contents($ConfigFile);
	if ($content !== false) 
	{
	   // do something with the content
	   //echo "file is read".$content;
	} 
	else 
	{
	   // an error happened
	   echo "Message: XML file is not read";
	}
	$objDOM=wiziq_ReadXML($content);
    $MaxDurationPerSession = $objDOM->getElementsByTagName("MaxDurationPerSession");
	$MaxUsersPerSession = $objDOM->getElementsByTagName("MaxUsersPerSession");
	$PresenterEntryBeforeTime = $objDOM->getElementsByTagName("PresenterEntryBeforeTime");
	$PrivateChat = $objDOM->getElementsByTagName("PrivateChat");
	$RecordingCreditLimit = $objDOM->getElementsByTagName("RecordingCreditLimit");
	$ConcurrentSessions = $objDOM->getElementsByTagName("ConcurrentSessions");	
	$RecordingCreditPending=$objDOM->getElementsByTagName("RecordingCreditPending");
    $subscription_url=$objDOM->getElementsByTagName("subscription_url");
    $buynow_url=$objDOM->getElementsByTagName("buynow_url");
    $Package_info_message=$objDOM->getElementsByTagName("Package_info_message");
    $pricing_url=$objDOM->getElementsByTagName("pricing_url");
    
	$maxdur=$MaxDurationPerSession->item(0)->nodeValue;
	$maxuser=$MaxUsersPerSession->item(0)->nodeValue;
	$presenterentry=$PresenterEntryBeforeTime->item(0)->nodeValue;
	$privatechat=$PrivateChat->item(0)->nodeValue;
	$recordingcredit=$RecordingCreditLimit->item(0)->nodeValue;
	$concurrsession=$ConcurrentSessions->item(0)->nodeValue;
	$creditpending=$RecordingCreditPending->item(0)->nodeValue;
    $subscription_url=$subscription_url->item(0)->nodeValue;
    $buynow_url=$buynow_url->item(0)->nodeValue;
    $Package_info_message=$Package_info_message->item(0)->nodeValue;
    $pricing_url=$pricing_url->item(0)->nodeValue; 
 }
 
 function wiziq_do_post_request($url, $data, $optional_headers = null)
  {
	$params = array('http' => array(
                  'method' => 'POST',
                  'content' => $data
               ));
    if ($optional_headers !== null) 
	{
        $params['http']['header'] = $optional_headers;
    }
    $ctx = stream_context_create($params);
    $fp = @fopen($url, 'rb', false, $ctx);
    if (!$fp) 
	{
        throw new Exception("Problem with $url, $php_errormsg");
    }
    $response = @stream_get_contents($fp);
    if ($response === false) 
	{
        throw new Exception("Problem reading data from $url, $php_errormsg");
    }
	 //print_r($response);
     return $response;
  }
  
  function wiziq_ReadXML($XMLToRead)
  {
	try
	{
	  $objDOM = new DOMDocument();
	  $objDOM->loadXML($XMLToRead);
	  return $objDOM; 
	}
	catch(Exception $e)
	{
	  echo $e->getMessage();
	}
	 
  }

  function wiziq_DateTimeString($date,$time,&$mm1,&$hh1,&$year,&$month,&$day)
  {
	list($month, $day, $year)=split('[/.-]', $date);
	$indexof=strrpos($time, ":");
 	if($indexof>0)
 	{
   		 $mm=intval(substr($time,$indexof+1,2));
 	}
 	else
		 $mm="00";
 	$hh=intval(substr($time,0,2));
 	$ampm=substr($time,-2);
	$ampm=strtolower($ampm);
    //checking ends here
	$hh1=intval($hh);
	if($ampm=="pm") 
	{
	  if($hh1<12)
	  {
		$hh1=$hh1+12;
	  }
	}
	if($ampm=="am")
    {
	  if($hh1==12)
	  {
	    $hh1=00;
	  }
    }				
  
    $hh2=$hh1+12;
	$mm1=intval($mm);
	$xyz=$date." ".$time;
    //final date time string
	return strtoupper($xyz);					   		  
  }
  
  function wiziq_DateOfClass($times)
  {
	 $udate=usergetdate($times);
	 $m=$udate['mon'];
	 $y=$udate['year'];
	 $d=$udate['mday'];
	 $wdate=$m."/".$d."/".$y;
	 return $wdate;
  }
  
  function wiziq_DateTimeCheck($times)
  {
		$timecheck=0;
		$todaydate=usergetdate(time());
		$udate=usergetdate($times);
		if($udate['year'] < $todaydate['year'])
		{
		 	 $timecheck=1; 
		}
		else if($udate['year'] == $todaydate['year'])
		{
			 if( $udate['yday'] < $todaydate['yday']) 
			 {
			  	 $timecheck=1; 
			 }
			 else if( $udate['mon'] < $todaydate['mon'] && $udate['yday'] <= $todaydate['yday'])
			 {
			  	 $timecheck=1;
			 }
			 else if($udate['hours'] < $todaydate['hours'] && $udate['mon'] <= $todaydate['mon'] && $udate['yday'] <= $todaydate['yday'])
			 {
			 	 $timecheck=1; 
			 }
			 else if( $udate['minutes'] < $todaydate['minutes'] && $udate['hours'] <= $todaydate['hours'] && $udate['mon'] <= $todaydate['mon'] && $udate['yday'] <= $todaydate['yday'])
			 {
			 	 $timecheck=1; 
			 }
		} 
		return $timecheck;
  }
  
  function wiziq_EventTypeString($eventtype)
  {
		if($eventtype=="user")
			$_eventType="User Event";
	
		else if($eventtype=="site")
			$_eventType="Site Event";
	
		else if($eventtype=="course")
			$_eventType="Course Event";
	
		else if($eventtype=="group")
			$_eventType="Group Event";
			
		return $_eventType;
  }
  
  function wiziq_CurrentDayTimeForCalendar()
  {
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
	  $DatetimeUser=$todaydate['mon']."/".$todaydate['mday']."/".$todaydate['year']." ".$hours.":".$todaydate['minutes']." ".$ampm ;      return $DatetimeUser; 
  }
  
  function wiziq_ModuleID()
  { 
      global $CFG;
	  $modquery="SELECT id FROM ".$CFG->prefix."modules where name='wiziq'"; // getting module id
	  $modresult=get_record_sql($modquery);
	  $moduleid=$modresult->id; 
	  return $moduleid;
  }
  
  function curPageURL() 
  {
  	  $pageURL = 'http';
 	  if (!empty($_SERVER["HTTPS"])&&($_SERVER["HTTPS"] == "on")) {$pageURL .= "s";}
    	$pageURL .= "://";
  	  if ($_SERVER["SERVER_PORT"] != "80") 
  	  {
    	$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
  	  } 
  	  else 
  	  {
    	$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
  	  }
  	  return $pageURL;
  }
  function wiziq_classesQuery($wizid)
  {
  	  global $CFG,$USER;
          $moduleid=wiziq_ModuleID();
	  $query1="select e.id as eid,e.eventtype as eventtype,cm.id as cmid,e.instance as einst,cm.instance as cminst,e.userid as eventuserid from ".$CFG->prefix."event e,".$CFG->prefix."course_modules cm WHERE cm.instance=e.instance AND e.instance=".$wizid." AND e.name LIKE '%mod/wiziq/icon.gif%' AND cm.module=".$moduleid;
          $result1=get_record_sql($query1);
          return $result1;
  }
  function wiziq_classesManageView($sessiontype,$r,$role,$result1,$courseid)
  { 
	  global $CFG,$USER,$DB,$OUTPUT,$sessionStatus;
	  $code=$r->insescod;
	  $recordingurl=$r->recordingurl;
	  $wizid=$r->id;
	  $name=$r->name;
	  $times=$r->wdate;
	  $type=$r->statusrecording;
	  $courseid=$courseid;
	  $id=$r->id;
	  $eventtype=$result1->eventtype;
	  $_eventType=wiziq_EventTypeString($eventtype);
	  $eid=$result1->eid;
	  $instance=$result1->einst;
	  $einst=$result1->einst;
	  $eventuserid=$result1->eventuserid;
	  $wdate=wiziq_DateOfClass($times);
	  $timecheck=wiziq_DateTimeCheck($times);
	  $wtime=calendar_time_representation($times); // converting the time in users timezone format
	  $cmid=$result1->cmid;
	  echo "<tr height='30px'>
            <td style='padding-left:10px; font-size:12px;border:solid 1px #efefef'><a href='view.php?id=$cmid&type=$type'>$name</a></td>
            <td style='padding-left:10px; font-size:12px;border:solid 1px #efefef'>$wdate, $wtime</td>
            <td style='padding-left:10px; font-size:12px;border:solid 1px #efefef'> ";
            $i=1;
            foreach($sessiontype as $code1)
            {
              $j=1;
              if(current($code1)==$code)
              {
                $sessionStatus=$sessiontype[$i][$j+1];
                if($sessiontype[$i][$j+1]==="D")
                echo "Done";
		else if($sessiontype[$i][$j+1]==="E")
		echo "Expired";
		else if($sessiontype[$i][$j+1]==="S")
		echo "Scheduled";
              }
              $i++;
            }
         echo "</td><td align='center' style='border:solid 1px #efefef'>";
	  if($sessionStatus==='S' && ($eventuserid==$USER->id  || $role==1 ))
	  {  
	    echo "<a href='edit_view.php?id=".$cmid."&type=".$type."&eventtype=".$_eventType."'><img
                  src='".$CFG->pixpath."/t/edit.gif' alt='".get_string('tt_editevent', 'calendar')."'
                 title='".get_string('tt_editevent', 'calendar')." '/></a> &nbsp;<a href='delete_session.php?id=".$courseid."&section=0&sesskey=".$USER->sesskey."&add=wiziq&aid=".$id."&eid=".$eid."&type=0&inst=".$cmid."&course=".$courseid." '><img
                  src='".$CFG->pixpath."/t/delete.gif' alt='".get_string('tt_deleteevent', 'calendar')."'
                 title='".get_string('tt_deleteevent', 'calendar')."'/></a>";
	  }
	 echo "</td><td width='40%' style='border:solid 1px #efefef'><table cellspacing='10px' cellpadding='10px' width='100%'><tr>
                 <td style='padding-left:10px; font-size:12px' width='30%'>";
	 if(($eventuserid==$USER->id  || $role==1 ))
	 {
		$i=1;
		foreach($sessiontype as $code1)
		{
		$j=1;
		if(current($code1)==$code)
		{
		   if($sessiontype[$i][$j+2]==1)
		   {
                    echo '<a onclick="return openDetails(\''.$CFG->wwwroot.'/mod/wiziq/viewrecording.php?s='.recordingEncrypt($code,"Auth@Moo(*)").'\');" href="javascript:void(0);" class="ulink">View Recording</a>';
                    }

		}
  		$i++;
                }
        }
	 echo "</td><td style='padding-left:5px; font-size:12px' width='37%'>";
	 if(($eventuserid==$USER->id  || $role==1 ))
	 {
		 $i=1;
		  foreach($sessiontype as $code1)
		  {
                    $j=1;
                      if(current($code1)==$code)
                      {
                        if($sessiontype[$i][$j+4]==2)
			echo '<a onclick="return PopUp(\''.$code.'\');" href="javascript:void(0);" class="ulink">Download Recording</a>';
                      }
                    $i++;
		  }
	 }
	 echo "</td><td style='font-size:12px' width='35%'>";
         if(($eventuserid==$USER->id  || $role==1 ))
	 {
		 $i=1;
		  foreach($sessiontype as $code1)
		  {
			 $j=1;
                         if(current($code1)==$code)
			 {
			   if($sessiontype[$i][$j+3]==1)
			   echo '<a  href="attendancereport.php?courseid='.$courseid.'&SessionCode='.$code.'" class="ulink">Attendance Report</a>';
			 }
			 $i++;
		  }
          }
	 echo "</td></tr></table></td>
        </tr>";  
  }
  function wiziq_getLanguage()
  {
      include("wiziqconf.php");

	$content = file_get_contents($LanguageXml);
	if ($content !== false)
	{
	   // do something with the content
	   //echo "file is read".$content;
	}
	else
	{
	   // an error happened
	   echo "Message: XML file is not read";
	}
	$objDOM=wiziq_ReadXML($content);
        $language = $objDOM->getElementsByTagName("language");
        for ($i=0; $i<$language->length; $i++)
        {
        $langarray[$i]=$language->item($i)->nodeValue;
        }
        return $langarray;
  }

// encrypt function for view recording link
function recordingEncrypt($string, $key) {
$result = '';
for($i=0; $i<strlen($string); $i++) {
$char = substr($string, $i, 1);
$keychar = substr($key, ($i % strlen($key))-1, 1);
$char = chr(ord($char)+ord($keychar));
$result.=$char;
}

return base64_encode($result);
}
?>
