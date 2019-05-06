<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>
<body>
<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ’s web based virtual classroom equipped with real-time collaboration tools 
 * Here control returned after uploading the content.
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once("lib.php");
require_once($CFG->dirroot."/course/lib.php");
require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once ($CFG->dirroot.'/lib/blocklib.php');
require_once ($CFG->dirroot.'/lib/moodlelib.php');
require_once("wiziqconf.php");
require_once("cryptastic.php");
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
	$titlecourse=urldecode($_REQUEST["t"]);
$arrayTC=explode("|",$titlecourse);
$title=$arrayTC[0];
 $course=$arrayTC[1];
	$urlcourse =$course; 
	$cal_y = optional_param('cal_y');
    $cal_m = optional_param('cal_m');
    $cal_d = optional_param('cal_d');
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
	$calendar_navlink = array('name' => $strwiziqs,
                          '',
                          'type' => 'misc');

    $day = intval($now['mday']);
    $mon = intval($now['mon']);
    $yr = intval($now['year']);
	
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
	 require_login($course, false);
	
	$navlinks[] = $calendar_navlink;
	$navlinks[] = array('name' => 'WiZiQ Content', 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);
	
	print_header($SITE->shortname.':'.$strwiziqs,$strwiziqs,$navigation, $wiziq->name,"", true,"",user_login_string($site));
	print_simple_box_start('center', '', '', 5, 'generalbox', $module->name);

echo '<p align="center" ><font face="Arial, Helvetica, sans-serif" color="#000000" size="3"><strong><img src="icon.gif" hspace="10" height="16" width="16" border="0" alt="" />We are uploading your content..... </strong></font></p>';	



if(!empty($_COOKIE['query']))
{
 
 parse_str(urldecode(decrypt($_COOKIE['query'])),$_request);

}

 $id=$_request['id'];
 $s=$_request['s'];
 if(!empty($s))
 {
$subfolder=$_request['s'];
 $arrayfolder=explode(",",$subfolder);
 $sublevel=sizeof($arrayfolder)-1;
for($i=1;$i<sizeof($arrayfolder);$i++)
 {
	$arraystring=explode("|",$arrayfolder[$i]); 
	if($i<sizeof($arrayfolder)-1)
{
	$a=$arraystring[1];
$alink.=$a.'\\';
}
else
$alink.=$arraystring[1];

 }
 
 }

  
	  $title=$title;
	  $contentid=$_REQUEST['cid'];
	
	  if(empty($alink))
	  {
		$alink="Content";  
	  }
	  $fileName=$_REQUEST['file_name'];
	  
	   $fileExtension=strtolower(substr($fileName,strrpos($fileName,".")+1));
	  if($fileExtension=="ppt" || $fileExtension=="pptx" || $fileExtension=="pps" || $fileExtension=="ppsx")
	  $image="ppt.gif";
	  else if($fileExtension=="pdf" )
	   $image="pdf.gif";
	   else if($fileExtension=="swf" || $fileExtension=="flv" )
	   $image="flash.gif";
	   else if($fileExtension=="doc" || $fileExtension=="docx" || $fileExtension=="rtf" || $fileExtension=="jnt" )
	   $image="word.gif";
	   else if($fileExtension=="mp3" || $fileExtension=="wav" || $fileExtension=="wma" )
	   $image="audio.gif";
	   else if($fileExtension=="wmv" || $fileExtension=="mov" || $fileExtension=="mpeg" || $fileExtension=="avi")
	   $image="video.gif";
	   else if($fileExtension=="xls" || $fileExtension=="xlsx")
	   $image="excel.gif";
	   else
	   $image="other.gif";
		  $wiziq->name=$fileName;
	  
	  $wiziq->title=$title;
	  //$wiziq->description=$_COOKIE['Desc'];
	  $wiziq->type="2";
	  $wiziq->uploaddatetime=time();
	  $wiziq->userid=$USER->id;	
	  $wiziq->icon=$image;
	  $wiziq->filepath=$alink;
	  $wiziq->parentid=$id;
	  $wiziq->contentid=$contentid;
	 //-------------------------inserting the record in content table------------------
	  $returnid=insert_record("wiziq_content", $wiziq) or die("cannot insert value in table wiziq_content");
	  
	 $returnid; 
	  
	  if(!empty($returnid))
	  redirect("managecontent.php?q=".urlencode($_COOKIE['query'])."&course=".$urlcourse);
      
     //}
    //}
  

?>   	
	
