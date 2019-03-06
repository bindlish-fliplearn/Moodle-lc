<?php

// This file is part of Moodle - http://moodle.org/
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
 * This plugin is used to access primecontent files
 *
 * @since Moodle 2.0
 * @package    repository_primecontent
 * @copyright  2010 Dongsheng Cai {@link http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/repository/lib.php');
require_once(__DIR__ . '/primecontent.php');

/**
 * repository_primecontent class
 * This is a class used to browse images from primecontent
 *
 * @since Moodle 2.0
 * @package    repository_primecontent
 * @copyright  2009 Dongsheng Cai {@link http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_primecontent extends repository {
  
  public function get_listing($path = '', $page = '') {
    $sessKey =  sesskey();
    $client_id   = optional_param('client_id', '', PARAM_RAW);    // client ID
    $itemid      = optional_param('itemid', '',        PARAM_INT);
    $ctx_id = optional_param('ctx_id', '', PARAM_RAW);
    $pluginserviceurl = new moodle_url('/repository/primecontent/searchForm.php');
    return array(
            'nologin' => true,
            'norefresh' => true,
            'nosearch' => true,
            'object' => array(
                'type' => 'text/html',
                'src' => $pluginserviceurl->out() . "?sesskey=" . $sessKey . "&itemid=" . $itemid . "&client_id=" . $client_id."&ctx_id=".$ctx_id
            )
        );
    global $SESSION;
    
    $list = array();
    $list['page'] = (int) $page;
    if ($list['page'] < 1) {
      $list['page'] = 1;
    }
    $primecontent_class = optional_param('primecontent_class', '', PARAM_RAW);
    $primecontent_subject = optional_param('primecontent_subject', '', PARAM_RAW);
    
    $primecontent = new primecontent;
    $checkLogin = $primecontent->login();
    if($checkLogin) {
      $uuid = $SESSION->uuid;
      $licence = $primecontent->checkLicence($uuid);
      if($licence) {
        if(empty($primecontent_class)) {
          $form = $primecontent->displaySearchForm();
          return $form;
        } else {
          if((!isset($SESSION->classId) && empty($SESSION->classId)) || $SESSION->classId != $primecontent_class) {
            $SESSION->classId = $primecontent_class;
          }
          if((!isset($SESSION->subjectId) && empty($SESSION->subjectId)) || $SESSION->subjectId != $primecontent_subject) {
            $SESSION->subjectId = $primecontent_subject;
          }
          $data = $primecontent->getPrimContentBySubjectId($SESSION->classId, $SESSION->subjectId);
          if(empty($data)) {
            print_error("Content not found.");
            return;
          }
        }
      } else {
        print_error("User licence is not valid.");
        return;
      }
    } else {
      print_error("User session is not valid.");
      return;
    }
    $list['list'] = $data;
    $list['norefresh'] = true;
    $list['nologin'] = true;
    return $list;
  }

  // if check_login returns false,
  // this function will be called to print a login form.
  public function print_login() {
    return true;
  }

  //search
  // if this plugin support global search, if this function return
  // true, search function will be called when global searching working
  public function global_search() {
    return false;
  }

  public function search($search_text, $page = 0) {
    return true;
  }

  public function supported_returntypes() {
    return (FILE_INTERNAL | FILE_EXTERNAL);
  }

  public function supported_filetypes() {
    return '*';
  }

}
