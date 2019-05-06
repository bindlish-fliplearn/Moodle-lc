<?php

/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ’s web based virtual classroom equipped with real-time collaboration tools 
 * After deleting the class control comes here.
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
$course = optional_param('id', 0, PARAM_INT);
    
    $urlcourse = optional_param('id', 0, PARAM_INT);
if(!$site = get_site()) {
        redirect($CFG->wwwroot.'/'.$CFG->admin.'/index.php');
    }
$strwiziq  = get_string("WiZiQ", "wiziq");
$strwiziqs = get_string("modulenameplural", "wiziq");
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
$navlinks[] = array('name' => 'WiZiQ Class', 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);
		
	print_header($site->shortname.':'.$strwiziqs,$strwiziqs,$navigation, $wiziq->name,"", true,"",user_login_string($site));
		
	$courseid= $_REQUEST['id'];
		
		
		
?>
<br /><br /><br />
<table width="70%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center"><font face="Arial, Helvetica, sans-serif" color="#0066CC" size="5"><strong> The Class has been deleted....</strong></font></td>
  </tr><tr></tr>
  <tr>
    <td align="center">
    <a href="auto.php?id=<?php echo $courseid;?>&section=0&sesskey=<?php echo $USER->sesskey;?>&add=wiziq"><strong> Click to add new class </strong></a></td>
  </tr>
  <tr>
    <td align="center"> <input type="button" class="txtbox" name="Cancel" value="Go to class list" onClick="javascript:location.href='wiziq_list.php?course=<?php  echo $courseid;?>'"> </td>
  </tr>
</table>
<?php
print_footer();
?>