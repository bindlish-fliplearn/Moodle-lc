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
 * primecontent class
 * class for communication with Wikimedia Commons API
 *
 * @author Dongsheng Cai <dongsheng@moodle.com>, Raul Kern <raunator@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
define('PRIMECONTENT_THUMB_SIZE', 120);

class primecontent {

  private $_conn = null;
  private $_param = array();

  public function __construct($url = '') {
    if (empty($url)) {
      $this->api = 'https://commons.wikimedia.org/w/api.php';
    } else {
      $this->api = $url;
    }
    $this->_param['format'] = 'php';
    $this->_param['redirects'] = true;
    $this->_conn = new curl(array('cache' => true, 'debug' => false));
  }

  public function displaySearchForm() {
    global $DB;
    //Extract course & section form URL
    $classId = "";
//    $subjectId = "";
    
    $curl = new curl(array('cache' => false, 'debug' => false));
    $classURL = PRIME_URL . "/v1/class?boardCode=cbse";
    $content3 = $curl->get($classURL, '');
    $result3 = json_decode($content3);
    $keyword = new stdClass();
    $keyword->label = get_string('keyword', 'repository_primecontent') . ': ';
    $keyword->id = 'primecontent_class';
    $keyword->type = 'select';
    $keyword->name = 'primecontent_class';
    foreach ($result3->response as $result) {
      if (!empty($result->classId)) {
        if ($classId == $result->classId) {
          $classList[] = array(
            'value' => $result->classId,
            'label' => $result->className,
            'selected' => true
          );
        } else {
          $classList[] = array(
            'value' => $result->classId,
            'label' => $result->className
          );
        }
      }
    }
    $selectClassList[] = array(
      'value' => '',
      'label' => 'Select class'
    );
    $keyword->options = array_merge($selectClassList, $classList);

    //Will use when filepicker.js issue will resolve.
//    foreach ($result3->response as $result) {
//     foreach ($result->subjects as $subject) {
//      if($subjectId == $subject->subjectId) {
//          $subjectList[$result->classCode][] = array(
//            'value' => $subject->subjectId,
//            'label' => $subject->subjectName,
//            'selected' => true
//          );
//        } else {
//          $subjectList[$result->classCode][] = array(
//            'value' => $subject->subjectId,
//            'label' => $subject->subjectName
//          );
//        }
//      }
//    }
    $selectSubjectList[] = array(
      'value' => '',
      'label' => 'Select subject'
    );
    $classSubject = array(
      'label' => get_string('keyword', 'repository_primecontent') . ': ',
      'type' => 'select',
      'name' => 'primecontent_subject',
      'id' => 'primecontent_subject',
      'options' => $selectSubjectList,
      'value' => '',
    );
    $form = array();
    $form['login'] = array($keyword, (object) $classSubject);
    $form['nologin'] = true;
    $form['norefresh'] = true;
    $form['nosearch'] = true;
    $form['allowcaching'] = false; // indicates that login form can NOT    
    return $form;
  }
  
  public function getCourseMapping() {
    $query = array();
    $classId = "";
    $subjectId = "";
    $current_link = $_SERVER[HTTP_REFERER];
    $parts = parse_url($current_link);
    parse_str($parts['query'], $query);
    if (!empty($query)) {
      $courseId = $query['course'];
      $course_map = $DB->get_record('guru_prime_mapping', array('course_id' => $courseId), '*');
      if (!empty($course_map)) {
        $classId = $course_map->class_level_id;
        $subjectId = $course_map->subject_id;
      }
    }
    return array('classId' => $classId, 'subjectId' => $subjectId);
  }

  public function getPrimContentBySubjectId($primeClass, $primeSubject, $search = null) {
    $files_array = array();
    if (!empty($primeClass)) {
      if(!empty($primeClass)) {
        $options = 'classId='.$primeClass;
      }
      if(!empty($primeSubject)) {
        $options .= '&subjectId=' . $primeSubject;
      }
      if (!empty($search)) {
        $options .= '&searchKey=' . urlencode($search);
      }
      $options .= '&pageSize=100';
      $api_path = PRIME_URL . "/v1/getAllResource?$options";
      $content = $this->_conn->get($api_path, '');
      $result = json_decode($content);
      if (is_array($result->response) && !empty($result->response)) {
        foreach ($result->response as $key1 => $values1) {
          foreach ($values1 as $key2 => $values2) {
            if ($key2 == 'topics') {
              foreach ($values2 as $key3 => $values3) {
                foreach ($values3 as $key4 => $values4) {
                  if ($key4 == 'resources') {
                    foreach ($values4 as $page) {
                      $thumbnail = '';
                      $title = $page->title;
                      $title .= '.f4v';

                      $isMedia = ($page->isMedia) ? 1 : 0;
                      $resourceType = ($page->resourceType) ? $page->resourceType : '';
                      $cType = ($page->cType) ? $page->cType : '';
                      if (strpos($page->thumbnail, '?'))
                        $thumbnail = $page->thumbnail . '&resourceId=' . $page->id . '@@' . $resourceType . '@@' . $isMedia . '@@' . $cType;
                      else
                        $thumbnail = $page->thumbnail . '?resourceId=' . $page->id . '@@' . $resourceType . '@@' . $isMedia . '@@' . $cType;
                      $files_array[] = array(
                        'title' => $title,
                        'thumbnail' => $page->thumbnail,
                        'thumbnail_width' => PRIMECONTENT_THUMB_SIZE,
                        'thumbnail_height' => PRIMECONTENT_THUMB_SIZE,
                        'license' => 'cc-sa',
                        'icon' => $page->thumbnail,
                        'source' => $thumbnail,
                      );
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    return $files_array;
  }

  public function login() {
    global $SESSION, $USER, $DB;
    $respo = FALSE;
    $userInfo = $DB->get_record('guru_user_mapping', array('user_id' => $USER->id), '*');
    if (isset($userInfo->uuid) && !empty($userInfo->uuid)) {
      if (isset($SESSION->uuid) && !empty($SESSION->uuid) && isset($SESSION->sessionToken) && !empty($SESSION->sessionToken)) {
        $uuid = $SESSION->uuid;
        $sessionToken = $SESSION->sessionToken;
        $checkSession = $this->checkSesstionToken($uuid, $sessionToken);
        if (!$checkSession) {
          print_error(UNSUBSCRIBE_MSG);
          return;
        }
        $respo = TRUE;
      } else {
        $conn2 = new curl(array('cache' => true, 'debug' => false));
        $autoLoginURL = UMS_URL . "/autologinByUuid/$userInfo->uuid";
        $autoLoginResp = $conn2->get($autoLoginURL, '');
        $result = json_decode($autoLoginResp);
        if (isset($result->data->sessionToken)) {
          $SESSION->sessionToken = $result->data->sessionToken;
          $SESSION->uuid = $userInfo->uuid;
          $respo = TRUE;
        } else {
          $respo = FALSE;
        }
      }
    }
    return $respo;
  }

  public function checkSesstionToken($uuid, $sessionToken) {
    $tokenValid = false;
    $conn = new curl(array('cache' => true, 'debug' => false));
    $api_path = UMS_URL . "/isLoginTokenValidForUserByUuid";
    $params = array('uuid' => $uuid,
      'sessionToken' => $sessionToken
    );
    $params_json = json_encode($params);
    $conn->setHeader(array(
      'Content-Type: application/json',
      'Connection: keep-alive',
      'Cache-Control: no-cache'));
    $content = $conn->post($api_path, $params_json);
    $result = json_decode($content);
    if (isset($result->status)) {
      $tokenValid = $result->status;
    }
    return $tokenValid;
  }

  public function checkLicence($uuid) {
    global $SESSION;
    $respo = FALSE;
    $conn3 = new curl(array('cache' => true, 'debug' => false));
    $checkLicenceURL = BL_URL . "/user/checkUserLicence/$uuid?product=prime";
    $checkLicenceResp = $conn3->get($checkLicenceURL, '');
    $result = json_decode($checkLicenceResp);
    if (isset($result->response)) {
      foreach ($result->response as $value) {
        if (isset($value->status)) {
          $SESSION->isPrimeUser = $value->status;
          $respo = TRUE;
          break;
        }
      }
    } else {
      $respo = FALSE;
    }
    return $respo;
  }

}
