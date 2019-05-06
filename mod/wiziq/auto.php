<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ’s web based virtual classroom equipped with real-time collaboration tools 
 * Basic page for having WiZiQ plugin in moodle. WiZiQ starts from here.
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once("lib.php");
require_once('wiziqconf.php');
$error1=$_POST['error'];
$fqdn=$CFG->wwwroot ; 
$id = intval($USER->id);    
$usr = $USER->username;
$email = $USER->email;


$aid=$_REQUEST['aid'];
$eid=$_REQUEST['eid'];

$id=$_REQUEST['id'];
$sesskey=$_REQUEST['sesskey'];
$add=$_REQUEST['add'];
	



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

    if (isset($SESSION->modform)) {   // Variables are stored in the session
    $mod = $SESSION->modform;
        unset($SESSION->modform);
    } else {
		
		
        $mod = (object)$_POST;
    }

    if (!empty($course) and confirm_sesskey()) { // add, delete or update form submitted

        if (empty($mod->coursemodule)) { //add
            if (! $course = get_record("course", "id", $mod->course)) {
               // error("This course doesn't exist");
			
            redirect($CFG->wwwroot);
            }
            $mod->instance = '';
            $mod->coursemodule = '';
        } else { //delete and update
		
		       if (! $cm = get_record("course_modules", "id", $mod->coursemodule)) {
               error("This course module doesn't exist");
           
            }

            if (! $course = get_record("course", "id", $cm->course)) {
                //error("This course doesn't exist");
				
            redirect($CFG->wwwroot);
            }
            $mod->instance = $cm->instance;
            $mod->coursemodule = $cm->id;
        }

        require_login($course->id); // needed to setup proper $COURSE
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        require_capability('moodle/course:manageactivities', $context);

        $mod->course = $course->id;
        $mod->modulename = clean_param($mod->modulename, PARAM_SAFEDIR);  // For safety
                
        rebuild_course_cache($course->id);

        if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
            unset($SESSION->returnpage);
            redirect($return);
        } else {
            redirect("view.php?id=$course->id#section-$sectionreturn");
        }
        exit;
    }

     if (!empty($add) and confirm_sesskey()) {

        $id = required_param('id',PARAM_INT);
        $section = required_param('section',PARAM_INT);

        if (! $course = get_record("course", "id", $id)) {
       //     error("This course doesn't exist");
       redirect($CFG->wwwroot);
        }

        if (! $module = get_record("modules", "name", $add)) {
            error("This module type doesn't exist");
        }

        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        require_capability('moodle/course:manageactivities', $context);

        if (!course_allowed_module($course,$module->id)) {
            error("This module has been disabled for this particular course");
        }

        require_login($course->id); // needed to setup proper $COURSE

        $form->section    = $section;         // The section number itself
        $form->course     = $course->id;
        $form->module     = $module->id;
        $form->modulename = $module->name;
        $form->instance   = "";
        $form->coursemodule = "";
        $form->mode       = "add";
        $form->sesskey    = !empty($USER->id) ? $USER->sesskey : '';
        if (!empty($type)) {
            $form->type = $type;
        }

        $sectionname    = get_string("name$course->format");
        $fullmodulename = get_string("modulename", $module->name);

        if ($form->section && $course->format != 'site') {
            $heading->what = $fullmodulename;
            $heading->to   = "$sectionname $form->section";
            $pageheading = get_string("addinganewto", "moodle", $heading);
        } 
        $strnav = '';

        $CFG->pagepath = 'mod/'.$module->name;
        if (!empty($type)) {
            $CFG->pagepath .= '/' . $type;
        }
        else {
            $CFG->pagepath .= '/mod';
        }

    } else {
        error("No action was specfied");
    }

    require_login($course->id); // needed to setup proper $COURSE
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('moodle/course:manageactivities', $context);
	
    $strmodulenameplural = get_string("modulenameplural", $module->name);


    unset($SESSION->modform); // Clear any old ones that may be hanging around.

        $icon = '<img class="icon" src="'.$CFG->modpixpath.'/'.$module->name.'/icon.gif" alt="'.get_string('modulename',$module->name).'"/>';

		redirect("event.php?action=new&course=$course->id&sesskey=$form->sesskey&section=$section&id=$id&add=$add&modulename=$form->modulename&mode=$form->mode&instance=$form->instance&module=$form->module");
?>
