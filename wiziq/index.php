<?php

// This file is part of Wiziq - http://www.wiziq.com/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_wiziq
 * @copyright  www.wiziq.com 
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
set_time_limit(0);
define('WIZIQ_MAX_TABLE_SIZE', 10);
define('WIZIQ_RECORDING_AVAILABLE', 1);
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/tablelib.php');
global $DB;
#--------parameter needed---------
$id = required_param('id', PARAM_INT);   // course
$download = optional_param('download', '', PARAM_ALPHANUMEXT);
$paging = optional_param('paging', '', PARAM_INT);
//confirm_sesskey();

#------setting paging as cookie in order to have paging when page number is changed-------
if (!empty($paging)) {
    setcookie('wiziq_managecookie', $paging, time() + (86400 * 7 * 365));
    $class_per_page = $paging;
}
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_course_login($course);
$coursecontext = context_course::instance($course->id);

$PAGE->set_url('/mod/wiziq/index.php', array('id' => $id, 'sesskey' => sesskey()));
$pagetitle = new stdClass();
$pagetitle->name = get_string('manage_classes', 'wiziq');
$PAGE->set_title(format_string($pagetitle->name));
$PAGE->set_heading(format_string(get_string('wiziq_classes', 'wiziq')));
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($coursecontext);
$PAGE->requires->js('/mod/wiziq/js/jquery-latest.js');
$PAGE->requires->js('/mod/wiziq/js/jquery.tablesorter.js');
$course_number = $course->id;

#-----this function get all the data regarding the wiziq class, including cm id's----
if (!$wiziqs = get_all_instances_in_course('wiziq', $course)) {
    notice(get_string('nowiziqs', 'wiziq'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$total_wiziq_records = count($wiziqs);
$noerror = get_string('noerror','wiziq');
$params = array(
    'objectid' => $total_wiziq_records,
    'relateduserid' => $USER->id,
    'courseid' => $course->id,
    'context' => $coursecontext,
    'other' => array(
        'error' => '',
        'sesskey' => sesskey()
    )
);
$event = \mod_wiziq\event\wiziq_classlisting::create($params);
$event->trigger();

#------- Creation of table starts------
$table = new flexible_table('manageclasses');
$refresh_txt = get_string('refresh_page', 'wiziq');
$statusicon = '<img src="' . $CFG->wwwroot . '/mod/wiziq/pix/refresh.png" alt=' . $refresh_txt . '/>';
$stausimage = '<a href="javascript:location.reload(true)"';
$stausimage .= ' title="' . $refresh_txt . '">' . $statusicon . '</a>';
$statusheading = 'Status' . " " . $stausimage;


$classtitlesort = get_string('name', 'wiziq');
$classdatesort = get_string('date_time', 'wiziq');
$classpresenteresort = get_string('presenter', 'wiziq');


if ((is_siteadmin()) || ($wiziqs[0]->presenter_id == $USER->id)) {
    $table->define_columns(array('name', 'date_time', 'presenter',
        'status', 'manage', 'dnldrec', 'viewrec', 'attendance_report'));
} else {
    $table->define_columns(array('name', 'date_time', 'presenter',
        'status', 'dnldrec', 'viewrec'));
}
$table->column_style_all('text-align', 'left');
if ((is_siteadmin()) || ($wiziqs[0]->presenter_id == $USER->id)) {
    $table->define_headers(array($classtitlesort,
        $classdatesort, $classpresenteresort,
        $statusheading, get_string('manage', 'wiziq'),
        get_string('recording_link', 'wiziq'), get_string('viewrec', 'wiziq'),
        get_string('attendance_report', 'wiziq')));
} else {
    $table->define_headers(array($classtitlesort,
          $classdatesort, $classpresenteresort,
        $statusheading,
        get_string('recording_link', 'wiziq'), get_string('viewrec', 'wiziq')));
}
$table->define_baseurl($PAGE->url);
$table->is_downloadable(true);
$table->download_buttons();
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->sortable(false);
$table->pageable(true);
if (isset($_COOKIE['wiziq_managecookie']) && empty($paging)) {
    $wiziq_managecookie = $_COOKIE['wiziq_managecookie'];
    $selected = $wiziq_managecookie;
    $class_per_page = $wiziq_managecookie;
} else if (!(isset($_COOKIE['wiziq_managecookie'])) && empty($paging)) {
    $class_per_page = WIZIQ_MAX_TABLE_SIZE;
    $selected = "";
} else {
    $class_per_page = $paging;
    $selected = $paging;
}
$table->pagesize($class_per_page, $total_wiziq_records);

#-----naming of the table download file----------------
$wiziq_manage_classes_file = get_string('manage_classes_file', 'wiziq');
$wiziq_file_heading = $wiziq_manage_classes_file . " " . $course_number;
$manage_classes = get_string('wiziq_classes_file', 'wiziq');
$wiziq_mc_filename = $manage_classes . $course_number;
$table->is_downloading($download, $wiziq_mc_filename, $wiziq_file_heading);

#--required here so that the $OUTPUT and the html renders only when the page is not downloading--
if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    $schedulenewwiziqclass = new moodle_url("$CFG->wwwroot/course/modedit.php", array('add' => 'wiziq', 'type' => '', 'course' => $course->id,
        'section' => '0', 'return' => '0'));
    $navigationtabsmanage = new moodle_url("$CFG->wwwroot/mod/wiziq/index.php", array('id' => $course->id, 'sesskey' => sesskey()));
    $navigationtabscontent = new moodle_url("$CFG->wwwroot/mod/wiziq/content.php", array('id' => $course->id, 'sesskey' => sesskey()));

    $tabs = array();
    $row = array();


$cContext = context_course::instance($COURSE->id);
$isStudent = current(get_user_roles($cContext, $USER->id))->shortname=='student'? true : false;



    if ((is_siteadmin()) || ($wiziqs[0]->presenter_id == $USER->id) || $isStudent !=1) {
        $row[] = new tabobject('wiziq_sch_class', $schedulenewwiziqclass, get_string('schedule_class', 'wiziq'));
    }
    $row[] = new tabobject('wizq_mange_class', $navigationtabsmanage, get_string('manage_classes', 'wiziq'));
    if ((is_siteadmin()) || ($wiziqs[0]->presenter_id == $USER->id) || $isStudent !=1) {
        $row[] = new tabobject('wizq_mange_content', $navigationtabscontent, get_string('manage_content', 'wiziq'));
    }
    $tabs[] = $row;
    print_tabs($tabs);
    $paging_option = new single_select($PAGE->url, "paging", array('5' => '5', '10' => '10', '15' => '15', '20' => '20' , '50' => '50' , '100' => '100'), $selected);

    $paging_option->label = get_string('per_page_classes', 'wiziq');
    echo $OUTPUT->render($paging_option);
}

#------ setting up the table-----
$table->setup();
#--- sorting array to get the newest record first-------
rsort($wiziqs);
$starting_index = $table->get_page_start();
#------slicing the array depending upon the page size choosen by the user------
$slice = array_slice($wiziqs, $starting_index, $class_per_page);

#------ data entry and calling API part-------
$download_recording = get_string('download_recording', 'wiziq');
$view_recording = get_string('view_recording', 'wiziq');
#-----to be used when status will give value Inprogress Via API
$no_download_recording = get_string('no_download_recording', 'wiziq');
$creating_dnld_rcd = get_string('creating_dnld_rcd', 'wiziq');

foreach ($slice as $wiziq) {
    $wiziq_status = $wiziq->class_status;
    $wiziq_datetime = $wiziq->wiziq_datetime;


    if (isset($wiziq->class_master_id)) {
        $wiziq_classmasterid_array[$wiziq->id] = $wiziq->class_master_id;
    }

    if (isset($wiziq->class_id)) {
        $wiziq_classid_array[$wiziq->id] = $wiziq->class_id;
    } else {
        $wiziq_sesscode_array[$wiziq->id] = $wiziq->insescod;
    }
}

if (!empty($wiziq_classmasterid_array)) {
    $wiziq_classmasterid_array = array_filter($wiziq_classmasterid_array);
    wiziq_get_data_manageperma($id, $wiziq_classmasterid_array, $wiziq_classidperma);
    $abcdd = $wiziq_classidperma;
}

if (!empty($wiziq_classid_array)) {
    $wiziq_classid_array = array_filter($wiziq_classid_array);
    wiziq_get_data_manage($id, $wiziq_classid_array , $classtatus ,$presenter_url1, $presenter_id , $presenter_name ,$recording_url ,$create_recording);
}
if (!empty($wiziq_sesscode_array)) {

    $wiziq_sesscode_array = array_filter($wiziq_sesscode_array); // remove null and -ve values
    wiziq_get_data_by_sessioncode_manage($id, $wiziq_sesscode_array);
}

foreach ($slice as $wiziq) {
    if ($wiziq->insescod == -1) {
        $permaclass[] = $wiziq;
    } else {
        $scheduleclass[] = $wiziq;
    }
}

foreach ($permaclass as $wiziq) {
    $userid = $wiziq->presenter_id;
    $userfirstname = $DB->get_field_select('user', 'firstname', 'id=' . $userid);
    $usersecondname = $DB->get_field_select('user', 'lastname', 'id=' . $userid);
    $presenter_name = $userfirstname . " " . $usersecondname;
    #------  if recording is opted for------
    $wiziqmodulecontext = context_module::instance($wiziq->coursemodule);
   
   
     $newwiziq = $DB->get_record('wiziq', array('id' => $wiziq->id));  
     
    // $newwiziq = $DB->get_record_sql('SELECT * FROM {wiziq} WHERE id ='.$wiziq->id.  ' ORDER BY insescod  DESC' );

    if (!isset($newwiziq->class_id)) {
        $title = $newwiziq->name;
        $start_time = '';
    } else {
        $title = html_writer::link(new moodle_url('/mod/wiziq/view.php', array('id' => $wiziq->coursemodule)), format_string($newwiziq->name, true));
        $start_time = "--";
    }


    $wiziq_completed = ($newwiziq->class_status == 'completed');
    $wiziq_notcompleted = ($newwiziq->recording_link_status == WIZIQ_RECORDING_AVAILABLE);


    if (isset($newwiziq->class_id)) {

        if ($wiziq_completed && $wiziq_notcompleted) {

            wiziq_downloadrecording($id, $newwiziq->class_id, $download_recording_link, $errormsg, $abcdd);

            if ($download_recording_link != null) {
                $updates = new stdClass(); //just enough data for updating the submission
                $updates->id = $wiziq->id;
                $updates->recording_link_status = WIZIQ_RECORDING_AVAILABLE;
                // $updates->recording_link = $download_recording_link;
                $updates->recording_link = $newwiziq->recording_link;
                $DB->update_record('wiziq', $updates);


                if (has_capability('mod/wiziq:wiziq_download_rec', $wiziqmodulecontext)) {

                    $dnld_rec = html_writer::link(new moodle_url('/mod/wiziq/view.php', array('id' => $wiziq->coursemodule)), $download_recording);
//  $dnld_rec = html_writer::link( new moodle_url($download_recording_linknew),
                    //         $download_recording);
                } else {
                    $dnld_rec = get_string('nocapability', 'wiziq');
                }
                if (has_capability('mod/wiziq:wiziq_view_rec', $wiziqmodulecontext)) {


                    //$viewrec_url = new moodle_url($newwiziq->view_recording_link);
                    /* $viewrec_url =  new moodle_url($newwiziq->view_recording_link);
                      $action = new popup_action('click', $viewrec_url, "view_recording",
                      array('height' => 786, 'width' => 1024));
                      $view_recording_link = $OUTPUT->action_link($viewrec_url, $view_recording,
                      $action, array('title' => get_string('modulename', 'wiziq'))); */

                    $view_recording_link = html_writer::link(new moodle_url('/mod/wiziq/view.php', array('id' => $wiziq->coursemodule)), $view_recording);
                } else {
                    $view_recording_link = get_string('nocapability', 'wiziq');
                }
            } else if ($newwiziq->class_status == 'expired') {
                $dnld_rec = get_string('classnotheld', 'wiziq');
                $view_recording_link = get_string('classnotheld', 'wiziq');
            } else if ($errormsg != null) {
                $dnld_rec = $errormsg;
                if (has_capability('mod/wiziq:wiziq_view_rec', $wiziqmodulecontext)) {
                    $viewrec_url = new moodle_url($newwiziq->view_recording_link);
                    $action = new popup_action('click', $viewrec_url, "view_recording", array('height' => 786, 'width' => 1024));
                    $view_recording_link = $OUTPUT->action_link($viewrec_url, $view_recording, $action, array('title' => get_string('modulename', 'wiziq')));
                } else {
                    $view_recording_link = get_string('nocapability', 'wiziq');
                }
            } else {
                $dnld_rec = "";
                $view_recording_link = "";
            }
        } else if (($newwiziq->recording != 1)) {
            $dnld_rec = "";
            $view_recording_link = get_string('classwithoutrec', 'wiziq');
        } else {
            if (has_capability('mod/wiziq:wiziq_download_rec', $wiziqmodulecontext)) {
                if ($newwiziq->recording_link != "") {
                    $dnld_rec = html_writer::link(new moodle_url($newwiziq->recording_link), $download_recording);
                } else {
                    $dnld_rec = "";
                }
            } else {
                $dnld_rec = get_string('nocapability', 'wiziq');
            }
            if (has_capability('mod/wiziq:wiziq_view_rec', $wiziqmodulecontext)) {
                if ($newwiziq->recording_link != "") {
                    $viewrec_url = new moodle_url($newwiziq->view_recording_link);
                    $action = new popup_action('click', $viewrec_url, "view_recording", array('height' => 786, 'width' => 1024));
                    $view_recording_link = $OUTPUT->action_link($viewrec_url, $view_recording, $action, array('title' => get_string('modulename', 'wiziq')));
                } else {
                    $view_recording_link = "";
                }
            } else {
                $view_recording_link = get_string('nocapability', 'wiziq');
            }
        }
    } else {
        $dnld_rec = '';
        $view_recording_link = get_string('errormsg_session_missing', 'wiziq');
        $newwiziq->class_status = '';
    }



    $editclass = new moodle_url("$CFG->wwwroot/course/mod.php", array('update' => $wiziq->coursemodule, 'return' => true, 'sesskey' => sesskey()));

    $editicon = '<img src="' . $CFG->wwwroot . '/mod/wiziq/pix/edit.png" />';
    $editconfirmmsg = get_string('editconfirm', 'wiziq');
    $editconfirm = new confirm_action($editconfirmmsg);
    $edit_wiziq = new action_link($editclass, $editicon, $editconfirm, array());

    $deleteclass = new moodle_url("$CFG->wwwroot/course/mod.php", array('delete' => $wiziq->coursemodule, 'return' => true, 'sesskey' => sesskey()));
    $deleteicon = '<img src="' . $CFG->wwwroot . '/mod/wiziq/pix/delete.png" />';
    $deleteconfirmmsg = get_string('deleteconfirm', 'wiziq');
    $deleteconfirm = new confirm_action($deleteconfirmmsg);
    $delete_wiziq = new action_link($deleteclass, $deleteicon, $deleteconfirm, array());
    $wiziqdeletedclass = get_string('deletefromwiziq', 'wiziq');
    if (($newwiziq->class_status != "expired") && ($newwiziq->class_status != "completed") && ($newwiziq->class_status != $wiziqdeletedclass && isset($newwiziq->class_id))) {
        $manageclass = $OUTPUT->render($edit_wiziq) . "  " . $OUTPUT->render($delete_wiziq);
    } else {
        $manageclass = $OUTPUT->render($delete_wiziq);
    }
    $delfromwiz = get_string('deletefromwiziq', 'wiziq');
    $wiziq_expired = ($newwiziq->class_status != 'expired');
    $wiziq_deletedformwiziq = ($newwiziq->class_status != $delfromwiz);
    $wiziq_upcoming = ($newwiziq->class_status != 'upcoming');
    if (isset($newwiziq->class_id)) {
        if ($wiziq_expired && $wiziq_upcoming && $wiziq_deletedformwiziq) {
            if (has_capability('mod/wiziq:view_attendance_report', $wiziqmodulecontext)) {
                $attendencereport = html_writer::link(
                                new moodle_url("$CFG->wwwroot/mod/wiziq/attendancereport.php", array('id' => $id, 'classid' => $newwiziq->class_id, 'sesskey' => sesskey())), get_string('attendencereport', 'wiziq'));
            } else {
                $attendencereport = get_string('nocapability', 'wiziq');
            }
        } else {
            $attendencereport = get_string('classnotheld', 'wiziq');
        }
    } else {
        $attendencereport = '';
    }
    if ((is_siteadmin()) || ($wiziqs[0]->presenter_id == $USER->id)) {
        $table->add_data(array($title, $start_time, $presenter_name,
            $newwiziq->class_status, $manageclass,
            $dnld_rec, $view_recording_link, $attendencereport));
    } else {
        $table->add_data(array($title, $start_time, $presenter_name,
            $newwiziq->class_status,
            $dnld_rec, $view_recording_link));
    }
}

foreach ($scheduleclass as $wiziq) {


    $userid = $wiziq->presenter_id;
    $userfirstname = $DB->get_field_select('user', 'firstname', 'id=' . $userid);
    $usersecondname = $DB->get_field_select('user', 'lastname', 'id=' . $userid);
    $presenter_name = $userfirstname . " " . $usersecondname;
    #------  if recording is opted for------
    $wiziqmodulecontext = context_module::instance($wiziq->coursemodule);
    $newwiziq = $DB->get_record('wiziq', array('id' => $wiziq->id)); 
   wiziq_get_data($COURSE->id, $newwiziq->class_id, $newwiziq->class_master_id, $presenter_id, $presenter_name, $presenter_url, $start_time, $time_zone, $create_recording, $status, $language_culture_name, $duration, $recording_url);
        
    if (!isset($newwiziq->class_id)) {
        $title = $newwiziq->name;
        $start_time = '';
    } else {
        $title = html_writer::link(new moodle_url('/mod/wiziq/view.php', array('id' => $wiziq->coursemodule)), format_string($newwiziq->name, true));
        $start_time = date("m/d/Y h:i:s A", strtotime($start_time)); 
    }
    $wiziq_completed = ($newwiziq->class_status == 'completed');

    $wiziq_notcompleted = ($newwiziq->recording_link_status == WIZIQ_RECORDING_AVAILABLE);
    if (isset($newwiziq->class_id)) {
        if ($wiziq_completed && $wiziq_notcompleted) {

            wiziq_downloadrecording($id, $newwiziq->class_id, $download_recording_link, $errormsg, $abcdd);

            if ($download_recording_link != null) {
                $updates = new stdClass(); //just enough data for updating the submission
                $updates->id = $wiziq->id;
                $updates->recording_link_status = WIZIQ_RECORDING_AVAILABLE;
                $updates->recording_link = $download_recording_link;
                $DB->update_record('wiziq', $updates);
                if (has_capability('mod/wiziq:wiziq_download_rec', $wiziqmodulecontext)) {
                    $dnld_rec = html_writer::link(new moodle_url($download_recording_link), $download_recording);
                } else {
                    $dnld_rec = get_string('nocapability', 'wiziq');
                }
                if (has_capability('mod/wiziq:wiziq_view_rec', $wiziqmodulecontext)) {
                    $viewrec_url = new moodle_url($newwiziq->view_recording_link);
                    $action = new popup_action('click', $viewrec_url, "view_recording", array('height' => 786, 'width' => 1024));
                    $view_recording_link = $OUTPUT->action_link($viewrec_url, $view_recording, $action, array('title' => get_string('modulename', 'wiziq')));
                } else {
                    $view_recording_link = get_string('nocapability', 'wiziq');
                }
            } else if ($newwiziq->class_status == 'expired') {
                $dnld_rec = get_string('classnotheld', 'wiziq');
                $view_recording_link = get_string('classnotheld', 'wiziq');
            } else if ($errormsg != null) {
                $dnld_rec = $errormsg;
                if (has_capability('mod/wiziq:wiziq_view_rec', $wiziqmodulecontext)) {
                    $viewrec_url = new moodle_url($newwiziq->view_recording_link);
                    $action = new popup_action('click', $viewrec_url, "view_recording", array('height' => 786, 'width' => 1024));
                    $view_recording_link = $OUTPUT->action_link($viewrec_url, $view_recording, $action, array('title' => get_string('modulename', 'wiziq')));
                } else {
                    $view_recording_link = get_string('nocapability', 'wiziq');
                }
            } else {
                $dnld_rec = "";
                $view_recording_link = "";
            }
        } else if (($newwiziq->recording != 1)) {
            $dnld_rec = "";
            $view_recording_link = get_string('classwithoutrec', 'wiziq');
        } else {
            if (has_capability('mod/wiziq:wiziq_download_rec', $wiziqmodulecontext)) {
                if ($newwiziq->recording_link != "") {
                    $dnld_rec = html_writer::link(new moodle_url($newwiziq->recording_link), $download_recording);
                } else {
                    $dnld_rec = "";
                }
            } else {
                $dnld_rec = get_string('nocapability', 'wiziq');
            }
            if (has_capability('mod/wiziq:wiziq_view_rec', $wiziqmodulecontext)) {
                if ($newwiziq->recording_link != "") {
                    $viewrec_url = new moodle_url($newwiziq->view_recording_link);
                    $action = new popup_action('click', $viewrec_url, "view_recording", array('height' => 786, 'width' => 1024));
                    $view_recording_link = $OUTPUT->action_link($viewrec_url, $view_recording, $action, array('title' => get_string('modulename', 'wiziq')));
                } else {
                    $view_recording_link = "";
                }
            } else {
                $view_recording_link = get_string('nocapability', 'wiziq');
            }
        }
    } else {
        $dnld_rec = '';
        $view_recording_link = get_string('errormsg_session_missing', 'wiziq');
        $newwiziq->class_status = '';
    }

    $editclass = new moodle_url("$CFG->wwwroot/course/mod.php", array('update' => $wiziq->coursemodule, 'return' => true, 'sesskey' => sesskey()));

    $editicon = '<img src="' . $CFG->wwwroot . '/mod/wiziq/pix/edit.png" />';
    $editconfirmmsg = get_string('editconfirm', 'wiziq');
    $editconfirm = new confirm_action($editconfirmmsg);
    $edit_wiziq = new action_link($editclass, $editicon, $editconfirm, array());

    $deleteclass = new moodle_url("$CFG->wwwroot/course/mod.php", array('delete' => $wiziq->coursemodule, 'return' => true, 'sesskey' => sesskey()));
    $deleteicon = '<img src="' . $CFG->wwwroot . '/mod/wiziq/pix/delete.png" />';
    $deleteconfirmmsg = get_string('deleteconfirm', 'wiziq');
    $deleteconfirm = new confirm_action($deleteconfirmmsg);
    $delete_wiziq = new action_link($deleteclass, $deleteicon, $deleteconfirm, array());
    $wiziqdeletedclass = get_string('deletefromwiziq', 'wiziq');
    if (($newwiziq->class_status != "expired") && ($newwiziq->class_status != "completed") && ($newwiziq->class_status != $wiziqdeletedclass && isset($newwiziq->class_id))) {
        $manageclass = $OUTPUT->render($edit_wiziq) . "  " . $OUTPUT->render($delete_wiziq);
    } else {
        $manageclass = $OUTPUT->render($delete_wiziq);
    }
    $delfromwiz = get_string('deletefromwiziq', 'wiziq');
    $wiziq_expired = ($newwiziq->class_status != 'expired');
    $wiziq_deletedformwiziq = ($newwiziq->class_status != $delfromwiz);
    $wiziq_upcoming = ($newwiziq->class_status != 'upcoming');
    if (isset($newwiziq->class_id)) {
        if ($wiziq_expired && $wiziq_upcoming && $wiziq_deletedformwiziq) {
            if (has_capability('mod/wiziq:view_attendance_report', $wiziqmodulecontext)) {
                $attendencereport = html_writer::link(
                                new moodle_url("$CFG->wwwroot/mod/wiziq/attendancereport.php", array('id' => $id, 'classid' => $newwiziq->class_id, 'sesskey' => sesskey())), get_string('attendencereport', 'wiziq'));
            } else {
                $attendencereport = get_string('nocapability', 'wiziq');
            }
        } else {
            $attendencereport = get_string('classnotheld', 'wiziq');
        }
    } else {
        $attendencereport = '';
    }
    if ((is_siteadmin()) || ($wiziqs[0]->presenter_id == $USER->id)) {
        $table->add_data(array($title, $start_time, $presenter_name,
            $newwiziq->class_status, $manageclass,
            $dnld_rec, $view_recording_link, $attendencereport));
    } else {
        $table->add_data(array($title, $start_time, $presenter_name,
            $newwiziq->class_status,
            $dnld_rec, $view_recording_link));
    }
}
$table->setup();
$table->finish_output();
echo $OUTPUT->footer();

?>
<style>
    
table.flexible {
    font-size: 9pt;
    margin: 10px 0 15px;
    text-align: left;
    width: 100%;
}

table.flexible thead tr .header {
    background-image: url("pix/bg.gif");
    background-position: right center;
    background-repeat: no-repeat;
    cursor: pointer;
}
table.flexible tbody td {

    vertical-align: top;
}

table.flexible thead tr .headerSortUp {
    background-image: url("pix/asc.gif");
}
table.flexible thead tr .headerSortDown {
    background-image: url("pix/desc.gif");
}


    </style>

<script type="text/javascript">
	
	
$(function() {
		$("table").tablesorter({debug: true})
		$("a.append").click(appendData);
		
		
	});
	
	var lastStudent = 23;
	var limit = 500;
	
	function appendData() {
		
		var tdTagStart = '<td>';
		var tdTagEnd = '</td>';
		//var sex = ['male','female'];
		//var major = ['Mathematics','Languages'];
		
		
		for(var i = 0; i < limit; i++) { 
			var rnd = i % 2;
			var row = '<tr>';	
						
			row += tdTagStart +  randomNumber() + tdTagEnd;
			row += tdTagStart +  randomNumber() + tdTagEnd;
			row += tdTagStart +  randomNumber() + tdTagEnd;
			row += tdTagStart +  randomNumber() + tdTagEnd;
			
			row += '</tr>';
			
			$("table/tbody:first").append(row);
			
		};
		
		
		$("table").trigger('update');
		return false;
	}
	
	function randomNumber() {
		return Math.floor(Math.random()*101)
	}
	
	</script>
