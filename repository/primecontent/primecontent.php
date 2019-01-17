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

  public function primContentLogin($keyword = '', $primecontent_subject = null) {
    global $DB, $USER, $SESSION;
    $query = array();
    $files_array = array();
    $search = 1;
    $boardCode = '';
    $classLevelId = '';
    $subjectId = '';
    $subjectCode = '';
    $chapterId = '';
    $courseId = '';
    $current_link = $_SERVER[HTTP_REFERER];
    $parts = parse_url($current_link);
    parse_str($parts['query'], $query);

    //Get User details
    $userInfo = $DB->get_record('guru_user_mapping', array('user_id' => $USER->id), '*');

    //Extract course & section form URL
    if (!empty($query)) {
      $courseId = $query['course'];
      $section = $query['section'];
    } else {
      $files_array[] = 'Some error Occured';
      return $files_array;
    }

    //Get course & chapter mapping
    $course_map = $DB->get_record('guru_prime_mapping', array('course_id' => $courseId), '*');
    if (!empty($course_map)) {
      $boardCode = $course_map->board;
      $classLevelId = $course_map->class_level_id;
      $subjectCode = $course_map->subject;
      $subjectId = $course_map->subject_id;

      // $courseSection = $DB->get_record('course_sections', array('course'=>$courseId, 'section'=>$section), '*');
      // Course Section mapping to guru_chapter_mapping
      // if (!empty($courseSection)) {
      // $sectionData = $DB->get_record('guru_chapter_mapping', array('section_id'=>$courseSection->id), '*');
      // $chapterId = !empty($sectionData) ? $sectionData->chapter_id : '';
      // }
    }

    if (empty($keyword)) {
      if(!empty($primecontent_subject)) {
        $subjectId = $primecontent_subject;
      }
      if (!empty($subjectId)) {
        $options = 'subjectId=' . $subjectId;
        if (!empty($chapterId)) {
          $options = $options . 'chapterId=' . $chapterId;
        }
        $api_path = PRIME_URL . "/v1/getAllResource?$options";
        // $api_path = "https://dev3ptoc.fliplearn.com/v1/getAllResource?subjectId=218";
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
          if (!empty($files_array)) {
            return $files_array;
          }
        }
      }
      if ($search) {
        return $this->displaySearchForm();
      }
      return $files_array;
    } else {
      $params = 'searchKey=' . $keyword;
      if (!empty($boardCode)) {
        $params .= '&boardCode=' . $boardCode;
      }
      if (!empty($classLevelId)) {
        $params .= '&classLevelId=' . $classLevelId;
      }
      if (!empty($subjectCode)) {
        $params .= '&subjectCode=' . $subjectCode;
      }
      if (empty($boardCode) || empty($classLevelId) || empty($subjectCode)) {
        $params .= '&allContent=true';
      }
      // $api_path = "https://stgptoc.fliplearn.com/v1/content/result?boardCode=cbse&classLevelId=9&subjectCode=Mathematics&searchKey=$keyword&ncertEbookEnable=1";
      $api_path = PRIME_URL . "/v1/content/result?$params";
      $this->_conn->setHeader(array(
        'loginId: ' . $userInfo->login_id,
        'sessionToken: ' . $SESSION->sessionToken,
        'platform: web',
        '3dSupport: 1',
        'Connection: keep-alive',
        'Cache-Control: no-cache'));
      $content = $this->_conn->get($api_path, '');
      $result = json_decode($content);

      if (isset($result->response) && !empty($result->response)) {
        foreach ($result->response as $page) {
          $thumbnail = '';
          $title = $page->topicName;
          $title .= '.f4v';

          $isMedia = ($page->isMedia) ? 1 : 0;
          if (strpos($page->thumbnail, '?'))
            $thumbnail = $page->thumbnail . '&resourceId=' . $page->resourceId . '@@' . $page->mediaType . '@@' . $isMedia . '@@' . $page->cType;
          else
            $thumbnail = $page->thumbnail . '?resourceId=' . $page->resourceId . '@@' . $page->mediaType . '@@' . $isMedia . '@@' . $page->cType;
          $files_array[] = array(
            'title' => $title, //chop off 'File:'
            'thumbnail' => $page->thumbnail,
            'thumbnail_width' => PRIMECONTENT_THUMB_SIZE,
            'thumbnail_height' => PRIMECONTENT_THUMB_SIZE,
            'license' => 'cc-sa',
            'icon' => $page->thumbnail,
            // the accessible url of the file
            // 'url'=>'https://media.fliplearn.com/fliplearnaes/_definst_/s3/b2ccontents/EOL/Contents/2014050700182179/2014050700182179.smil/playlist.m3u8?wowzatokenstarttime=1540787744&wowzatokenendtime=1543466144&wowzatokenhash=JhvSOfXy3_LPejvWRBmeDi_7FnZ39_sS_q3YL5jiRn4=',
            'source' => $thumbnail,
          );
        }
      }
    }
    return $files_array;
  }

  private function displaySearchForm() {
    $conn3 = new curl(array('cache' => false, 'debug' => false));
    $api_path3 = PRIME_URL . "/v1/class?boardCode=cbse";
    $content3 = $conn3->get($api_path3, '');
    $result3 = json_decode($content3);
    $keyword = new stdClass();
    $keyword->label = get_string('keyword', 'repository_primecontent') . ': ';
    $keyword->id = 'input_text_keyword';
    $keyword->type = 'select';
    $keyword->name = 'primecontent_subject';
    $keyword->label = 'Select Class';
    foreach ($result3->response as $result) {
      $classList[] = array(
        'value' => $result->classCode,
        'label' => $result->className
      );
    }
    $keyword->options = $classList;
//                print_r($this->options); die;
                if ($this->options['ajax']) {
    $form = array();
    $form['login'] = array($keyword);
    $form['nologin'] = true;
    $form['norefresh'] = true;
    $form['nosearch'] = true;
    $form['allowcaching'] = false; // indicates that login form can NOT
    // be cached in filepicker.js (maxwidth and maxheight are dynamic)
    return $form;
                }

    /* else {
      echo <<<EOD
      <table>
      <tr>
      <td>{$keyword->label}</td><td><input name="{$keyword->name}" type="text" /></td>
      </tr>
      </table>
      <input type="submit" />
      EOD;
      } */
  }

}
