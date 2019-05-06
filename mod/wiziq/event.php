<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * This page is container of scheduling page 
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
    require_once('../../config.php');
    require_once($CFG->dirroot.'/calendar/lib.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->dirroot.'/mod/forum/lib.php');
    require_once("lib.php");
    require_once("wiziqconf.php");
    require_login();
    $id            = required_param('id', PARAM_INT);
    $section       = required_param('section', PARAM_INT);
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

    $action = required_param('action', PARAM_ALPHA);
    $eventid = optional_param('id', 0, PARAM_INT);
    $eventtype = optional_param('type', 'select', PARAM_ALPHA);
    $urlcourse = optional_param('course', 0, PARAM_INT);
    $cal_y = optional_param('cal_y');
    $cal_m = optional_param('cal_m');
    $cal_d = optional_param('cal_d');
	
    if(isguest()) {
        // Guests cannot do anything with events
        redirect(CALENDAR_URL.'view.php?view=upcoming&amp;course='.$urlcourse);
    }

    $focus = '';

    if(!$site = get_site()) {
        redirect($CFG->wwwroot.'/'.$CFG->admin.'/index.php');
    }
	$strwiziq  = get_string("WiZiQ", "wiziq");
	$strwiziqs = get_string("modulenameplural", "wiziq");
    calendar_session_vars();

    $now = usergetdate(time());
    $navlinks = array();
    $calendar_navlink = array('name' => $strwiziqs,
                          'link' =>'',
                          'type' => 'misc');

    $day = intval($now['mday']);
    $mon = intval($now['mon']);
    $yr = intval($now['year']);

    if ($usehtmleditor = can_use_richtext_editor()) {
        $defaultformat = FORMAT_HTML;
    } else {
        $defaultformat = FORMAT_MOODLE;
    }

    // If a course has been supplied in the URL, change the filters to show that one
    if($urlcourse > 0 && record_exists('course', 'id', $urlcourse)) {
        require_login($urlcourse, false);

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
    $navlinks[] = array('name' => 'New WiZiQ Live Class', 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    $title="New WiZiQ Live Class";
print_header($site->shortname.':'.$strwiziqs.':'.$title,$strwiziqs,$navigation, "","", true,"",user_login_string($site));
    echo calendar_overlib_html();

    echo '<table id="calendar">';
    echo '<tr><td class="maincalendar">';

    switch($action) {

        case 'new':
            
            calendar_get_allowed_types($allowed);
            if(!$allowed->groups && !$allowed->courses && !$allowed->site) {
                // Take the shortcut
                $eventtype = 'user';
            }

            $header = '';

            switch($eventtype) {
                case 'user':
				$userid=$_REQUEST['userid'];
				    $form->name = '';
                    $form->description = '';
                    $form->courseid = 0;
                    $form->groupid = 0;
                    $form->userid = $USER->id;
                    $form->modulename = '';
                    $form->eventtype = '';
                    $form->instance = 0;
                    $form->timeduration = 0;
                    $form->duration = 0;
                    $form->repeat = 0;
                    $form->repeats = '';
                    $form->minutes = '';
                    $form->type = 'user';
                    $header = get_string('typeuser', 'calendar');
                break;
                case 'group':
			$groupid=$_REQUEST['groupid'];
                        $form->name = '';
                        $form->description = '';
                        $form->courseid = '';
                        $form->groupid = $groupid;
                        $form->userid = $USER->id;
                        $form->modulename = '';
                        $form->eventtype = '';
                        $form->instance = 0;
                        $form->timeduration = 0;
                        $form->duration = 0;
                        $form->repeat = 0;
                        $form->repeats = '';
                        $form->minutes = '';
                        $form->type = 'group';
                        $header = get_string('typegroup', 'calendar');
                    
                break;
                case 'course':
				$courseid=$_REQUEST['courseid'];
				
                        $form->name = '';
                        $form->description = '';
                        $form->courseid = $courseid;
                        $form->groupid = 0;
                        $form->userid = $USER->id;
                        $form->modulename = '';
                        $form->eventtype = '';
                        $form->instance = 0;
                        $form->timeduration = 0;
                        $form->duration = 0;
                        $form->repeat = 0;
                        $form->repeats = '';
                        $form->minutes = '';
                        $form->type = 'course';
                        $header = get_string('typecourse', 'calendar');
                   
                break;
                case 'site':
                    $form->name = '';
                    $form->description = '';
                    $form->courseid = SITEID;
                    $form->groupid = 0;
                    $form->userid = $USER->id;
                    $form->modulename = '';
                    $form->eventtype = '';
                    $form->instance = 0;
                    $form->timeduration = 0;
                    $form->duration = 0;
                    $form->repeat = 0;
                    $form->repeats = '';
                    $form->minutes = '';
                    $form->type = 'site';
                    $header = get_string('typesite', 'calendar');
                break;
                case 'select':
                break;
                default:
                    error('Unsupported event type');
            }

            $form->format = $defaultformat;
            if(!empty($header)) {
              
            }
			
            if($eventtype == "select") {
				
                echo '<div id="selecteventtype">';
                include('select_event.html');
                echo '</div>';
            }
            else {
				echo '<link href="'.$CFG->wwwroot.'/mod/wiziq/main.css" rel="stylesheet" type="text/css" />

<script language="JavaScript" type="text/javascript" src="wiziq.js"></script>';
                include('mode1.html');
				
				
            }

        break;
    }
    echo '</td>';

    // START: Last column (3-month display)

    $defaultcourses = calendar_get_default_courses();
    calendar_set_filters($courses, $groups, $users, $defaultcourses, $defaultcourses);
    
    // when adding an event you can not be a guest, so I think it's reasonalbe to ignore defaultcourses
    // MDL-10353
    calendar_set_filters($courses, $groups, $users);
    list($prevmon, $prevyr) = calendar_sub_month($mon, $yr);
    list($nextmon, $nextyr) = calendar_add_month($mon, $yr);

    echo '<td class="sidecalendar">';
    echo '<div class="sideblock">';
    echo '<div class="header">'.get_string('monthlyview', 'calendar').'</div>';
    echo '<div class="minicalendarblock minicalendartop">';
    echo calendar_top_controls('display', array('id' => $urlcourse, 'm' => $prevmon, 'y' => $prevyr));
    echo calendar_get_mini($courses, $groups, $users, $prevmon, $prevyr);
    echo '</div><div class="minicalendarblock">';
    echo calendar_top_controls('display', array('id' => $urlcourse, 'm' => $mon, 'y' => $yr));
    echo calendar_get_mini($courses, $groups, $users, $mon, $yr);
    echo '</div><div class="minicalendarblock">';
    echo calendar_top_controls('display', array('id' => $urlcourse, 'm' => $nextmon, 'y' => $nextyr));
    echo calendar_get_mini($courses, $groups, $users, $nextmon, $nextyr);
    echo '</div>';
    echo '</div>';
    echo '</td>';
    echo '</tr></table>';

    print_footer();
?>
