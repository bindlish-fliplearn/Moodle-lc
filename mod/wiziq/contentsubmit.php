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
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
define('WIZIQ_TYPE_FOLDER', 1);
define('WIZIQ_TYPE_FILE', 2);
define('WIZIQ_DELETE', "delete");
define('WIZIQ_KEY', "key");
define('WIZIQ_INPROGRESS', 1);
#--------------------parameters-------------------------------
$id = required_param('course', PARAM_INT);// course id
$parentid = optional_param('parentid', '', PARAM_INT);
$foldersubmit = optional_param('foldersubmit', '', PARAM_ALPHAEXT);
$folderpath = optional_param('folderpath', '', PARAM_PATH);
$foldername = optional_param('foldername', '', PARAM_ALPHANUMEXT);
$filesubmit = optional_param('filesubmit', '', PARAM_ALPHAEXT);
$file = $_FILES;
$filetitle = optional_param('filetitle', '', PARAM_FILE);
$action = optional_param('action', '', PARAM_ALPHA);
$deleteid = optional_param('deleteid', '', PARAM_INT);
$timekey = optional_param('timekey', '', PARAM_INT);
$hash = optional_param('hash', '', PARAM_ALPHANUMEXT);
$key = WIZIQ_KEY;
confirm_sesskey();
$sesskey = sesskey();
#----------parameter validation--------------------------------------------
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_course_login($course);
$coursecontext = context_course::instance($course->id);
$noerror = get_string('noerror','wiziq');
$paramslog = array(
    'objectid' => $parentid,
    'relateduserid' => $USER->id,
    'courseid' => $id,
    'context' => $coursecontext,
    'other' => array(
        'sesskey' => sesskey(),
        'error' => $noerror
    )
);
$event = \mod_wiziq\event\wiziq_content::create($paramslog);
$event->trigger();

$url = new moodle_url('/mod/wiziq/contentsubmit.php', array('id'=>$id));
#----------setting page layout--------------------------------------------
$PAGE->set_url($url);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('incourse');

#------------------create folder logic-----------------------------------
if ((!empty($foldersubmit)) && (!empty($foldername))) {
    $foldernameexist = $DB->record_exists('wiziq_content',
            array('name' => $foldername, 'userid' => $USER->id,
                  'parentid' =>$parentid, 'course' => $course->id));
    if (!$foldernameexist) {
        $createfolder = wiziq_create_folder($id, $folderpath, $foldername);
        if ($createfolder == 'true') {
            $preparentid = $DB->get_field('wiziq_content', 'parentid', array('id' => $parentid));
            $folder = new stdClass();
            $folder->course = $id;
            $folder->wiziq = "";
            $folder->type = WIZIQ_TYPE_FOLDER;
            $folder->name = format_string($foldername);
            $folder->title = format_string($foldername);
            $folder->parentid = $parentid;
            $folder->prevparentid  = $preparentid;
            $folder->path = $folderpath;
            $folder->userid = $USER->id;
            $folder->uploadtime = time();
            $folder->contentid = '';
            $folder->status = '';
            $wcid = $id.WIZIQ_TYPE_FOLDER.$USER->id.time();
            $folder->wcid = $wcid;
            $DB->insert_record('wiziq_content', $folder);
            redirect("content.php?id=$id&parentid=$parentid&sesskey=$sesskey");
        } else {
            $error_in_folder = get_string('errorcrtingfolder', 'wiziq');
            $url = new moodle_url('/mod/wiziq/content.php',
                        array('id' => $id, 'parentid' => $parentid, 'sesskey' => sesskey()));
            print_error($error_in_folder, '', $url);
        }
    } else {
        $foldernamestring = get_string('foldernamestring', 'wiziq');
        $folder_alrdy_exist = get_string('folder_alrdy_exist', 'wiziq');
        $folderpresent = $foldernamestring." ".$foldername." ".$folder_alrdy_exist;
        $url = new moodle_url('/mod/wiziq/content.php',
                    array('id' => $id, 'parentid' => $parentid, 'sesskey' => sesskey()));
        print_error($folderpresent, '', $url);
    }
    #---------------file creation logic---------------------------------------
} else if ((!empty($filesubmit)) && (!empty($file))) {
    $result = wiziq_content_upload($id, $filetitle, $file, $folderpath);
    $contentid = (string)$result->content_id;
    if (!empty($contentid)) {
        $preparentid = $DB->get_field('wiziq_content', 'parentid', array('id' => $parentid));
        $uploadedfile = new stdClass();
        $uploadedfile->course = $id;
        $uploadedfile->wiziq = "";
        $uploadedfile->type = WIZIQ_TYPE_FILE;
        $uploadedfile->name = format_string($file['uploadingfile']['name']);
        if (!empty($filetitle)) {
            $uploadedfile->title = format_string($filetitle);
        } else {
            $uploadedfile->title = format_string($file['uploadingfile']['name']);
        }
        $uploadedfile->parentid = $parentid;
        $uploadedfile->prevparentid  = $preparentid;
        $uploadedfile->path = $folderpath;
        $uploadedfile->userid = $USER->id;
        $uploadedfile->uploadtime = time();
        $uploadedfile->contentid = $contentid;
        $uploadedfile->status = WIZIQ_INPROGRESS;
        $wcid = $id.WIZIQ_TYPE_FILE.$USER->id.time();
        $uploadedfile->wcid = $wcid;
        $DB->insert_record('wiziq_content', $uploadedfile);
        redirect("content.php?id=$id&parentid=$parentid&sesskey=$sesskey");
    } else {
        $error_in_fileupload = get_string('errorinfileupload', 'wiziq');
        $url = new moodle_url('/mod/wiziq/content.php',
                    array('id' => $id, 'parentid' => $parentid, 'sesskey' => sesskey()));
        print_error($error_in_fileupload, '', $url);
    }
    #--------------------delete file/folder logic-----------------------------
} else if ($action == WIZIQ_DELETE) {
    $decryptedhash = wiziq_decrypt_hash($hash, $key);
    if (($deleteid + $timekey) == $decryptedhash) {
        $content = $DB->get_record('wiziq_content', array('id' => $deleteid), '*', MUST_EXIST);
        $empty_folder = $DB->get_records('wiziq_content', array('parentid' => $deleteid));
        $parent_for_deleted = $content->parentid;
        //$content->userid == $USER->id for extra check
        if (empty($empty_folder) && ($content->type == WIZIQ_TYPE_FOLDER)
                && ($content->userid == $USER->id)) {
            $DB->delete_records('wiziq_content', array('id' => $deleteid));
            wiziq_delete_folder($id, $content->name, $content->path);
        } else if (($content->type == WIZIQ_TYPE_FILE) && ($content->userid == $USER->id)) {
            wiziq_content_delete($id, $content->contentid);
            $DB->delete_records('wiziq_content', array('id' => $deleteid));
            redirect("content.php?id=$id&parentid=$parent_for_deleted&sesskey=$sesskey");
        } else {
            $url = new moodle_url('/mod/wiziq/content.php',
                    array('id' => $id, 'parentid' => $parentid, 'sesskey' => sesskey()));
            $error_msg = get_string('subcontenterror', 'wiziq');
            print_error($error_msg, '', $url);
        }
    } else {
        $url = new moodle_url('/mod/wiziq/content.php',
                array('id' => $id, 'parentid' => $parentid, 'sesskey' => sesskey()));
        $error_msg_data = get_string('datatempered', 'wiziq');
        print_error($error_msg_data, '', $url);
    }
}
redirect("content.php?id=$id&parentid=$parentid&sesskey=$sesskey");