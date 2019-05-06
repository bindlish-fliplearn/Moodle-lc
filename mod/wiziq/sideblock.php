<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * WiZiQ side block for navigation to wiziq pages
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
require_once("locallib.php");
require_login();
if(!empty($_REQUEST['course']))
$courseID= $_REQUEST['course'];
else if(!empty($courseid))
$courseID=$courseid;
else if(!empty($course->id))
$courseID=$course->id;

// finding role of logged in user in particular course
 $role=wiziq_GetUserRole($courseID);
if($role==1 || $role==2 || $role==3 )
{
?>
 <link type="text/css" rel="stylesheet" href="main.css">
 <div class="block_wiziqlive sideblock" id="inst15" style="width:150px; float:left; ">
 <div class="header">
 <div class="title" ><h2 style="font-family:Arial, Helvetica, sans-serif"><img src="icon.gif" align="absbottom"/>&nbsp;WiZiQ Live Classes</h2></div></div>
 <div class="content" style="height:90px">
 <span class="listdv"><a href="auto.php?id=<?php echo $courseID;?>&section=0&sesskey=<?php echo $USER->sesskey;?>&add=wiziq">Schedule a Class</a></span>
 <span class="listdv"><a href="wiziq_list.php?course=<?php echo $courseID;?>">Manage Classes</a></span>
 <span class="listdv" style="border-bottom:none"><a href="managecontent.php?course=<?php echo $courseID;?>">Manage Content</a></span>
 </div>
 </div>
<?php
}
?>
