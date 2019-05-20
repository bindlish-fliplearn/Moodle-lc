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
 * Prints a particular instance of wiziq
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_wiziq
 * @copyright  www.wiziq.com 
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_login();


$id = optional_param('id',null,PARAM_INT);
$type = optional_param('type',null,PARAM_ALPHANUMEXT);
$value = optional_param('value',null,PARAM_INT);
$name= optional_param('name',null,PARAM_ALPHANUMEXT);

$date= optional_param('newdate',null,PARAM_TEXT);

$download_count = $DB->get_record('wiziq', array('id' => $id), '*');
if ($type == 'download') {
    $download = new stdClass();
    $download->id = $id;
    $download->download_count = $download_count->download_count + 1;
    $DB->update_record('wiziq', $download);
    
  $$recorddetailsdownloaddetails = new stdClass();
  $downloaddetails->username = "$name";
  $downloaddetails->class_id = $id;
  $downloaddetails->time = "$date";
  $DB->insert_record('download_details', $downloaddetails);
    
} else if ($type == 'recording') {
    $record = new stdClass();
    $record->id = $id;
    $record->recording_count = $download_count->recording_count + 1;
    $DB->update_record('wiziq', $record);
    
  $recorddetails = new stdClass();
  $recorddetails->username = "$name";
  $recorddetails->class_id = $id;
  $recorddetails->time = "$date";
  $DB->insert_record('recording_details', $recorddetails);
  
} else if ($type == 'perma_download') {
    $perma_download = new stdClass();
    $perma_download->id = $id;
    $perma_download->download_count = $value;
    $DB->update_record('wiziq', $perma_download);
    
  $permadownload = new stdClass();
  $permadownload->username = "$name";
  $permadownload->class_id = $id;
  $permadownload->time = "$date";
  $DB->insert_record('download_details', $permadownload);
    
} else if ($type == 'record_view') {
    $view_record = new stdClass();
    $view_record->id = $id;
    $view_record->recording_count = $value;
    $DB->update_record('wiziq', $view_record);
    
  $permarecored = new stdClass();
  $permarecored->username = "$name";
  $permarecored->class_id = $id;
  $permarecored->time = "$date";
  $DB->insert_record('recording_details', $permarecored);
}
?>