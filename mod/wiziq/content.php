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
 * English strings for wiziq
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
define('WIZIQ_TYPE_FOLDER', 1);
define('WIZIQ_TYPE_FILE', 2);
define('WIZIQ_DELETE', "delete");
define('WIZIQ_INPROGRESS', 1);
define('WIZIQ_AVAILABLE', 2);
define('WIZIQ_FAIL', 3);
define('WIZIQ_ROOTFOLDER', 1);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/lib/outputcomponents.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->libdir.'/tablelib.php');
require_once(dirname(__FILE__).'/lib.php');
global $USER, $CFG;

#--------parameter needed---------
$id = optional_param('id', '', PARAM_INT);   // course

$pnodeid = optional_param('parentid', 1, PARAM_INT);
$folderid =$pnodeid;
$paging = optional_param('paging', '', PARAM_INT);
confirm_sesskey();
#------setting paging as cookie in order to have paging when page number is changed-------
if (!empty($paging)) {
    setcookie('wiziq_managecookie', $paging, time()+(86400 * 7*365));
    $content_per_page = $paging;
}

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$folder_exists = $DB->get_record('wiziq_content', array('id' => $folderid), '*', MUST_EXIST);
require_course_login($course);
//add_to_log($course->id, 'wiziq', 'view all', 'index.php?id='.$course->id, '');
$coursecontext = context_course::instance($course->id);
$url = new moodle_url('/mod/wiziq/content.php', array('id'=>$id, 'sesskey' => sesskey()));
$PAGE->set_url($url);
$pagetitle = new stdClass();
$pagetitle->name = get_string('manage_content', 'wiziq');
$PAGE->set_title(format_string($pagetitle->name));
$PAGE->set_heading(format_string(get_string('wiziq_content', 'wiziq')));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('incourse');
#---------------------- table setup here--------------------------
$refresh_txt = get_string('refresh_page', 'wiziq');
$table = new flexible_table('wixiq_content');
$table->define_columns(array('name', 'status', 'delete'));
$statusicon = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/refresh.png" alt='.$refresh_txt.'/>';
$stausimage_first = '<a href="javascript:location.reload(true)"';
$stausimage_second = ' title="'.$refresh_txt.'">'.$statusicon.'</a>';
$stausimage = $stausimage_first.$stausimage_second;
$status = 'Status'." ".$stausimage;
$nameheding = get_string('nameheading', 'wiziq');
$deleteheading = get_string('deleteheading', 'wiziq');
$table->define_headers(array($nameheding, $status, $deleteheading));
$table->column_style_all('text-align', 'left');
$table->column_style('name', 'width', 'auto');
$table->column_style('status', 'text-align', 'center');
$table->column_style('delete', 'text-align', 'center');
$table->column_style('status', 'width', '180px');
$table->column_style('delete', 'width', '180px');
$table->define_baseurl($PAGE->url);
$table->is_downloadable(false);
$table->sortable(false);
$table->pageable(true);
echo $OUTPUT->header();
#--------------------------tabs for navigation-----------
$schedulenewwiziqclass = new moodle_url("$CFG->wwwroot/course/modedit.php",
        array('add' => 'wiziq', 'type' => '', 'course' => $course->id,
              'section' => '0', 'return' => '0'));
$navigationtabsmanage = new moodle_url("$CFG->wwwroot/mod/wiziq/index.php",
        array('id' =>  $course->id, 'sesskey' => sesskey()));
$navigationtabscontent = new moodle_url("$CFG->wwwroot/mod/wiziq/content.php",
        array('id' => $course->id, 'sesskey' => sesskey()));

$tabs =array();
$row = array();

$row[] = new tabobject('wiziq_sch_class', $schedulenewwiziqclass,
        get_string('schedule_class', 'wiziq'));
$row[] = new tabobject('wizq_mange_class', $navigationtabsmanage,
        get_string('manage_classes', 'wiziq'));
$row[] = new tabobject('wizq_mange_content', $navigationtabscontent,
        get_string('manage_content', 'wiziq'));

$tabs[]=$row;
print_tabs($tabs);
#--------------------paging logic----------------------------------
if (isset($_COOKIE['wiziq_managecookie']) && empty($paging)) {
           $wiziq_managecookie = $_COOKIE['wiziq_managecookie'];
           $selected = $wiziq_managecookie;
           $content_per_page = $wiziq_managecookie;
} else if (!(isset($_COOKIE['wiziq_managecookie'])) && empty($paging)) {
    $content_per_page = WIZIQ_MAX_TABLE_SIZE;
    $selected = "";
} else {
    $content_per_page = $paging;
    $selected = $paging;
}
$params = array('pnodeid' => $pnodeid, 'courseid' => $course->id, 'userid' => $USER->id);
$contentlist = $DB->get_records_select('wiziq_content',
        'parentid = :pnodeid AND ( course = :courseid OR course = "1" ) AND userid = :userid',
        $params, 'id DESC');
$total_content_record = count($contentlist);
$table->pagesize($content_per_page, $total_content_record);
$paging_option = new single_select($PAGE->url, "paging",
            array('5' => '5', '10' => '10', '15' => '15', '20' => '20'), $selected);
$paging_option->label = get_string('per_page_content', 'wiziq');


$wiziqcoursecontext = context_course::instance($course->id);
if (has_capability('mod/wiziq:wiziq_content_upload', $wiziqcoursecontext)) {
    #------------------------------------paging option setup-------------------
    echo $OUTPUT->render($paging_option);
    echo '<br />';
    $folderrecord = $DB->get_record('wiziq_content',
            array('id' => $folderid, 'type' => WIZIQ_TYPE_FOLDER));
    if (!empty($folderrecord)) {
        $cnode = html_writer::link( new moodle_url('/mod/wiziq/content.php',
            array('id' => $id, 'parentid' => $folderrecord->id,
                  'sesskey' => sesskey())), $folderrecord->name);
        $firstlevel = link_arrow_right($cnode);
        $folderpath = "";
        $pnode = $DB->get_record('wiziq_content', array('id' => $folderrecord->parentid));
        if (!empty($pnode)) {
            $secondfolder = html_writer::link(new moodle_url('/mod/wiziq/content.php',
                    array('id' => $id, 'parentid' => $pnode->id,
                          'sesskey' => sesskey())), $pnode->name);
            $secondlevel = link_arrow_right($secondfolder);
            $gpnode = $DB->get_record('wiziq_content', array('id' => $pnode->parentid));
            $folderpath = $folderrecord->name;
        } else {
            $secondlevel = "";
        }
        if (!empty($gpnode)) {
            $thirdfolder = html_writer::link(new moodle_url('/mod/wiziq/content.php',
                    array('id' => $id, 'parentid' => $gpnode->id,
                          'sesskey' => sesskey())), $gpnode->name);
            $thirdlevel = link_arrow_right($thirdfolder);
            $pgpnode = $DB->get_record('wiziq_content',
                    array('id' => $gpnode->parentid));
            $folderpath = $pnode->name."/".$folderpath;
        } else {
            $thirdlevel = "";
        }
        if (!empty($pgpnode)) {
            $lastfolder = html_writer::link(new moodle_url('/mod/wiziq/content.php',
                    array('id' => $id, 'parentid' => $pgpnode->id,
                          'sesskey' => sesskey())), $pgpnode->name);
            $lastlevel = link_arrow_right($lastfolder);
            $folderpath = $gpnode->name."/".$folderpath;
        } else {
            $lastlevel = "";
        }
        $bred = array();
            $bred[] = $lastlevel;
            $bred[] = $thirdlevel;
            $bred[] = $secondlevel;
            $bred[] = $firstlevel;
        foreach ($bred as $bredcrum) {
            echo $bredcrum;
        }
    } else {
        $parent_not_fould = get_string('parent_not_fould', 'wiziq');
        print_error($parent_not_fould, '', $PAGE->url);
    }
    #------------------------------------table setup--------------------------
    $table->setup();
    $params = array('pnodeid' => $pnodeid, 'courseid' => $course->id, 'userid' => $USER->id);
    $table_data = $DB->get_records_select('wiziq_content',
        'parentid = :pnodeid AND ( course = :courseid OR course = "1" ) AND userid = :userid',
        $params, 'id DESC');
    $starting_index = $table->get_page_start();
    #------slicing the array depending upon the page size choosen by the user------
    $slice = array_slice($table_data, $starting_index, $content_per_page);

    $unconvtfileroot = $DB->record_exists('wiziq_content',
            array('parentid' => $folderid, 'type' => WIZIQ_TYPE_FILE,
                  'status' => WIZIQ_INPROGRESS));
    $oldparams = array('pnodeid' => $folderid, 'courseid' => $course->id, 'userid' => $USER->id);
    $oldcontentlist = $DB->get_records_select('wiziq_content',
            'parentid = :pnodeid AND ( course = :courseid OR course = "1" ) AND userid = :userid',
        $oldparams, 'id DESC');
    foreach ($slice as $clist) {
        if ($clist->cid_change_status == '2' && $clist->type == '2') {
            $clist_array[$clist->id] = $clist->old_content_id;
        }
    }
    if (isset($clist_array)) {
        $clist_array_clean = array_filter($clist_array);
        wiziq_get_contentid_update($id, $clist_array_clean);
    }
    if ($unconvtfileroot) {
        $folderdetail= $DB->get_record('wiziq_content',
            array('id' => $folderid, 'type' => WIZIQ_TYPE_FOLDER));
        wiziq_get_contentstatus($folderdetail->path, $folderdetail->name, $course->id);
    }
    #-------------------entring data to table-----------------

    if (!empty($slice)) {
        foreach ($slice as $value) {
            if ($value->type == WIZIQ_TYPE_FOLDER) {
                $nameonly = $value->name;
                $image = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/files.png" />';
                $name = $image.$nameonly;
                $name = html_writer::link( new moodle_url('/mod/wiziq/content.php',
                array('id'=>$course->id, 'parentid' => $value->id, 'sesskey' => sesskey())), $name);
                $status = "";
                wiziq_authentication($value->id, $timekey, $hash);
                $delete = get_string('content_delete', 'wiziq');
                $delete = new moodle_url('/mod/wiziq/contentsubmit.php',
                array('course' => $course->id, 'action' => WIZIQ_DELETE, 'deleteid' => $value->id,
                    'sesskey' => sesskey(), 'parentid' => $pnodeid,
                    'timekey' => $timekey, 'hash' => $hash));

                $deleteicon = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/delete.png" />';
                $deleteconfirmmsg = get_string('deleteconfirmcontent', 'wiziq');
                $deleteconfirmmsgname = $deleteconfirmmsg." ".$nameonly;
                $deleteconfirm = new confirm_action($deleteconfirmmsgname);
                $delete_wiziq_content = new action_link($delete, $deleteicon,
                        $deleteconfirm, array());
                $deletecontent = $OUTPUT->render($delete_wiziq_content);
            } else if ($value->type == WIZIQ_TYPE_FILE) {
                $nameonly = $value->title;
                $ext = pathinfo($value->name, PATHINFO_EXTENSION);
                $ext = strtolower($ext);
                if ($ext == 'swf' || $ext == 'flv') {
                    $image = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/flash.png" />';
                } else if ($ext == 'doc' || $ext == 'docx' || $ext == 'rtf') {
                    $image = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/docx.png" />';
                } else if ($ext == 'xls' || $ext == 'xlsx') {
                    $image = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/xlsx.png" />';
                } else if ($ext == 'pdf') {
                    $image = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/pdf.png" />';
                } else if ($ext == 'ppt' || $ext == 'pptx' || $ext == 'pps' || $ext == 'ppsx') {
                    $image = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/pptx.png" />';
                } else if ($ext == 'mp4' || $ext == 'mov' || $ext == 'avi' || $ext == 'mpeg' || $ext == 'wmv') {
                    $image = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/avi.png" />';
                } else if ($ext == 'wav' || $ext == 'wma' || $ext == 'mp3') {
                    $image = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/audio.png" />';
                }
                $name = $image." ".$nameonly;
                $contstatus = (int)$value->status;
                if ($contstatus == WIZIQ_INPROGRESS) {
                    $inprogress = get_string('inprogress', 'wiziq');
                    $status = $inprogress;
                } else if ($contstatus == WIZIQ_AVAILABLE) {
                    $available = get_string('available', 'wiziq');
                    $status = $available;
                } else if ($contstatus == WIZIQ_FAIL) {
                    $fail = get_string('contentfail', 'wiziq');
                    $status = $fail;
                } else {
                    $notknown = get_string('notknown', 'wiziq');
                    $status = $notknown;
                }
                //html_writer::link
                wiziq_authentication($value->id, $timekey, $hash);
                $delete = get_string('content_delete', 'wiziq');
                $delete = new moodle_url('/mod/wiziq/contentsubmit.php',
                array('course' => $course->id, 'action' => WIZIQ_DELETE,
                      'deleteid' => $value->id, 'sesskey' => sesskey(),
                      'parentid' => $pnodeid, 'timekey' => $timekey, 'hash' => $hash));
                $deleteicon = '<img src="'.$CFG->wwwroot.'/mod/wiziq/pix/delete.png" />';
                $deleteconfirmmsg = get_string('deleteconfirmcontent', 'wiziq');
                $deleteconfirmmsgname = $deleteconfirmmsg." ".$nameonly;
                $deleteconfirm = new confirm_action($deleteconfirmmsgname);
                $delete_wiziq_content = new action_link($delete, $deleteicon,
                                                        $deleteconfirm, array());
                $deletecontent = $OUTPUT->render($delete_wiziq_content);
            }
             $table->add_data(array($name, $status, $deletecontent));
        }
    } else {
        $name = '';
        $status = 'Nothing Inside';
        $delete = '';
        $table->add_data(array($name, $status, $delete));
    }

    #------------------------creating form-------------------------------------------
    if (empty($pgpnode)) {
        $folder = html_writer::label('Folder Name', 'foldername', true);
        $folder .= " " . html_writer::tag('input', '', array('id' => 'parentid',
            'type' => 'hidden', 'name' => 'parentid', 'value' => $pnodeid));
        $folder .= " " . html_writer::tag('input', '', array('id' => 'folderpath',
            'type' => 'hidden', 'name' => 'folderpath', 'value' => $folderpath));
        $folder .=  " " . html_writer::tag('input', '', array('id' => 'foldername',
            'type' => 'text', 'name' => 'foldername', 'value' => ''));
        $folder .= " " . html_writer::tag('input', '', array('id' => 'sesskey',
            'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
        $folder .= " " . html_writer::tag('input', '', array('id' => 'course',
            'type' => 'hidden', 'name' => 'course', 'value' => $id));
        $folder .=  " " . html_writer::tag('input', '', array('id' => 'foldersubmit',
            'type' => 'submit', 'name' => 'foldersubmit',
            'value' => 'Create Folder', 'onclick' => 'return check_foldername(foldername)'));
        $folder .= " ". html_writer::link('#', 'Upload File', array('id' => 'filelink',
            'onclick' => 'toggle_visibility("folderspan", "filespan", "foldername", "")'));
        $folderspan = html_writer::tag('span', $folder, array('id' => 'folderspan',
            'style' => 'display:inline'));
    } else {
        $folder = html_writer::link('#', 'Upload File', array('id' => 'filelink',
            'onclick' => 'toggle_visibility("folderspan", "filespan", "foldername", "")'));
        $folderspan = html_writer::tag('span', $folder, array('id' => 'folderspan',
            'style' => 'display:none'));
    }

    $file = html_writer::label('File Title', 'filetitle', true);
    $file .= " ". html_writer::tag('input', '', array('id' => 'parentid',
        'type' => 'hidden', 'name' => 'parentid', 'value' => $pnodeid));
    $file .= " ". html_writer::tag('input', '', array('id' => 'folderpath',
        'type' => 'hidden', 'name' => 'folderpath', 'value' => $folderpath));
    $file .= " " . html_writer::tag('input', '', array('id' => 'sesskey',
        'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    $file .= " " . html_writer::tag('input', '', array('id' => 'course',
        'type' => 'hidden', 'name' => 'course', 'value' => $id));
    $file .= " ". html_writer::tag('input', '', array('id' => 'filetitle',
        'type' => 'text', 'name' => 'filetitle', 'style' => 'height:18px',
        'value' => ''));
    $file .= " ". html_writer::tag('input', '', array('id' => 'uploadingfile',
        'type' => 'file', 'name' => 'uploadingfile', 'value' => ''));
    $file .=  " " . html_writer::tag('input', '', array('id' => 'filesubmit',
        'type' => 'submit',
        'name' => 'filesubmit', 'value' => 'Upload File',
        'onclick' => 'return check_filename(uploadingfile, filetitle)'));
    if (empty($pgpnode)) {
        $file .= " ". html_writer::link('#', 'Create Folder', array('id' => 'folderlink',
        'onclick' => 'toggle_visibility("filespan", "folderspan", "filetitle", "uploadingfile")'));
        $filespan = html_writer::tag('span', $file, array('id' => 'filespan',
        'style' => 'display:none'));
    } else {
        $file .= " ". " Cannot Create Folder at this level";
        $filespan = html_writer::tag('span', $file, array('id' => 'filespan',
            'style' => 'display:inline'));
    }

    $finalformdiv = $folderspan.$filespan;

    $form = html_writer::tag('form', $finalformdiv, array('id' => 'wiziq_contentform',
        'enctype' => 'multipart/form-data', 'action' => 'contentsubmit.php',
        'method' => 'post'));

    echo '<br />';
    echo $form;
    #------------------js for form------------------------------------------------
    $PAGE->requires->js('/mod/wiziq/js/javascript.js');
    $table->finish_output();
    echo $OUTPUT->box(get_string('allowed_content', 'wiziq'), '', 'notice');
    $str = '<center><img src="'.$CFG->wwwroot.'/mod/wiziq/pix/content.gif" /></center><br />';
    echo $OUTPUT->box($str);
} else {
    $nocapability = get_string('nocapability', 'wiziq');
    $url = new moodle_url('/mod/wiziq/index.php',
                        array('id' => $id));
            print_error($nocapability, '', $url);
}
echo $OUTPUT->footer();