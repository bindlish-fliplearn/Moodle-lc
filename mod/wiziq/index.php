<style type="text/css">
.ulink{text-decoration:underline; font-weight:bold; font-size:12px}
.ulink:hover{text-decoration:none;font-weight:bold;font-size:12px}
.dv100{width:100%; float:left}
</style>
<?php /*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools
 * Index page for WiZiQ showing clsses scheduled
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

$id = optional_param('id',"", PARAM_INT);   // course
$courseid = optional_param('course',"", PARAM_INT);   // course
if(!empty($courseid))
{
    $id=$courseid;
}
    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "wiziq", "view all", "index.php?id=$course->id", "");


/// Get all required stringswiziq

    $strwiziqs = get_string("modulenameplural", "wiziq");
    $strwiziq  = get_string("WiZiQ", "wiziq");

    $navlinks = array();
    $calendar_navlink = array('name' => $strwiziqs,'link' =>'','type' => 'misc');
    $navlinks[] = $calendar_navlink;
    $navlinks[] = array('name' => "WiZiQ Classes", 'link' => "", 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    print_header($course->shortname.":".$strwiziqs, $course->fullname,$navigation,"", "", true,"","");
	echo "<br />";
include("sideblock.php");
/// ----------------------------------Get all the appropriate data--------------------------
  $limit=10;
//$todaydate=usergetdate(time());

//$timestamp= make_timestamp($todaydate['year'], $todaydate['mon'], $todaydate['wday'], $todaydate['hours'], $todaydate['minutes']);
/// Print the list of instances (your module will probably extend this)
 //$query="(SELECT * FROM ".$CFG->prefix."wiziq order by insescod DESC)" ;
$moduleid=wiziq_ModuleID();
if($CFG->dbtype=="mysql")
{
$query="SELECT * FROM ".$CFG->prefix."wiziq where id in (select distinct e.instance from ".$CFG->prefix."event e,".$CFG->prefix."course_modules cm WHERE e.instance=cm.instance AND cm.course=".$course->id." AND cm.module=".$moduleid." AND e.name like '%mod/wiziq/icon.gif%') ORDER BY insescod DESC ";
}
else if($CFG->dbtype=="mssql_n" || $CFG->dbtype=="sqlsrv")
  {
 $query="Select * from (SELECT ROW_NUMBER() OVER (ORDER BY insescod DESC)AS Row, * FROM ".$CFG->prefix."wiziq where id in (select distinct e.instance from ".$CFG->prefix."event e,".$CFG->prefix."course_modules cm WHERE e.instance=cm.instance AND cm.course=".$course->id." AND cm.module=".$moduleid." AND e.name like '%mod/wiziq/icon.gif%')) as Logas";
  }
$query=paging_1($query,"","0%",$id);
$result=get_records_sql($query);
echo '<table border="0" cellpadding="5px" cellspacing="5px" width="800px" bordercolor="#efefef" align="right">
<th align="left" height="30px" style="background-color:#efefef;">WiZiQ Classes</th>';
foreach($result as $rn)
	{
 $wdate= wiziq_DateOfClass($rn->wdate);
echo '<tr style="border-bottom:solid 1px #efefef"><td style="font-size:12px; "><a href="view.php?instance='.$rn->id.'" class="ulink" ><strong>'.$rn->name.'</strong></a></br>'.$wdate.'-'.$rn->wtime." ". ($rn->timezone).'</td></tr>';
}
 echo '<tr><td>';
$str="";
paging_2($str,"0%",$id); // printing the footer of paging

echo '</td></tr>';
echo '</table>';


/// Finish the page
    print_footer($course);?>
