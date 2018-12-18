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
        $client = new primecontent;
        $list = array();
        $list['page'] = (int)$page;
        if ($list['page'] < 1) {
            $list['page'] = 1;
        }
        $primecontent_keyword = optional_param('primecontent_keyword', '', PARAM_RAW);
        // $primecontent_maxheight = optional_param('primecontent_maxheight', '', PARAM_RAW);
        
        $data = $client->primContentLogin($primecontent_keyword);
        // $decoded_data = json_decode($data, true);
        $list['list'] = $data;
        // $list['list'] = $client->search_images($this->keyword, $list['page'] - 1,
        //         array('iiurlwidth' => $this->get_maxwidth(),
        //             'iiurlheight' => $this->get_maxheight()));
        $list['nologin'] = true;
        // $list['norefresh'] = true;
        // $list['nosearch'] = true;
        // if (!empty($list['list'])) {
        //     $list['pages'] = -1; // means we don't know exactly how many pages there are but we can always jump to the next page
        // } else if ($list['page'] > 1) {
        //     $list['pages'] = $list['page']; // no images available on this page, this is the last page
        // } else {
        //     $list['pages'] = 0; // no paging
        // }
        return $list;
    }
   // login
    public function check_login() {
        global $SESSION, $USER, $DB;
        if (isset($SESSION->isPrimeUser) && !empty($SESSION->isPrimeUser)) {
            return $SESSION->isPrimeUser;
        } else {
            $userInfo = $DB->get_record('guru_user_mapping', array('user_id' => $USER->id), '*');
            if (isset($userInfo->uuid) && !empty($userInfo->uuid)) {
                $tokenValid = false;
                $conn = new curl(array('cache'=>true, 'debug'=>false));
                if (isset($SESSION->sessionToken) && !empty($SESSION->sessionToken)) {
                    $api_path = UMS_URL . "/isLoginTokenValidForUserByUuid";
                    // $api_path = "http://stgums.fliplearn.com/isLoginTokenValidForUserByUuid";    
                    $params = array('uuid' => $userInfo->uuid,
                                    'sessionToken' => $SESSION->sessionToken
                                );
                    $params_json = json_encode($params);
                    $conn->setHeader(array(
                        'Content-Type: application/json',
                        'Connection: keep-alive',
                        'Cache-Control: no-cache'));
                    $content = $conn->post($api_path,$params_json);
                    $result = json_decode($content);
                    if (isset($result->status)) {
                        $tokenValid = $result->status;
                    }
                } 
                if (!$tokenValid) {
                    $conn2 = new curl(array('cache'=>true, 'debug'=>false));
                    $api_path2 = UMS_URL . "/autologinByUuid/$userInfo->uuid";    
                    $content2 = $conn2->get($api_path2,'');
                    $result2 = json_decode($content2);
                    if (isset($result2->data->sessionToken)) {
                        $SESSION->sessionToken = $result2->data->sessionToken;
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }
            $conn3 = new curl(array('cache'=>true, 'debug'=>false));
            $api_path3 = BL_URL . "/user/checkUserLicence/$userInfo->uuid?product=prime";
            $content3 = $conn3->get($api_path3,'');
            $result3 = json_decode($content3);
            if (isset($result3->response)) {
                foreach ($result3->response as $key => $value) {
                    if (isset($value->status)) {
                        $SESSION->isPrimeUser = $value->status;
                    }
                }
            } else {
                return false;
            }
        }

        $this->keyword = optional_param('primecontent_keyword', '', PARAM_RAW);
        if (empty($this->keyword)) {
            $this->keyword = optional_param('s', '', PARAM_RAW);
        }
        $sess_keyword = 'primecontent_'.$this->id.'_keyword';
        if (empty($this->keyword) && optional_param('page', '', PARAM_RAW)) {
            // This is the request of another page for the last search, retrieve the cached keyword.
            if (isset($SESSION->{$sess_keyword})) {
                $this->keyword = $SESSION->{$sess_keyword};
            }
        } else if (!empty($this->keyword)) {
            // Save the search keyword in the session so we can retrieve it later.
            $SESSION->{$sess_keyword} = $this->keyword;
        }
        global $DB;
        $query = array();
        $files_array = array();
        $search = 1;
        $boardCode = '';
        $classLevelId = '';
        $subjectId = '';
        $subjectCode = '';
        $chapterId = '';
        $current_link = $_SERVER[HTTP_REFERER];
        $parts = parse_url($current_link);
        parse_str($parts['query'], $query);

        //Extract course & section form URL
        if (!empty($query)) {
            $courseId = $query['course'];
            $section = $query['section'];
        } else {
            $files_array[] = 'Some error Occured';
            return $files_array;
        }

        //Get course & chapter mapping
        $course_map = $DB->get_record('guru_prime_mapping', array('course_id'=>$courseId), '*');
        if (!empty($course_map)) {
            $boardCode = $course_map->board;
            $classLevelId = $course_map->class_level_id;
            $subjectCode = $course_map->subject;
            $subjectId = $course_map->subject_id; 
            
            // $courseSection = $DB->get_record('course_sections', array('course'=>$courseId, 'section'=>$section), '*');
            // if (!empty($courseSection)) {
                // $sectionData = $DB->get_record('guru_chapter_mapping', array('section_id'=>$courseSection->id), '*');
                // $chapterId = !empty($sectionData) ? $sectionData->chapter_id : '';
            // }
        }
        if (!empty($subjectId)) {
            return TRUE;
            // return !empty($this->keyword);
        } else {
            return !empty($this->keyword);
        }
    }
    // if check_login returns false,
    // this function will be called to print a login form.
    public function print_login() {
        global $SESSION;
        if (isset($SESSION->isPrimeUser)) {
            // print_r($SESSION);die('sff');
            if (!$SESSION->isPrimeUser) {
                $keyObj = new stdClass();
                $keyObj->label = "You are not subscribed to Prime.";
                // $keyObj->id    = 'input_text_keyword';
                // $keyObj->type  = 'text';
                // $keyObj->name  = 'primecontent_keyword';
                // $keyObj->value = '';
                $msg = array();
                $msg['login'] = array($keyObj);
                print_error(UNSUBSCRIBE_MSG);
                return;
            }
        } else {    
            print_error(UNSUBSCRIBE_MSG);
            return ;
        }
        $keyword = optional_param('primecontent_keyword', '', PARAM_RAW);
        if (!empty($keyword)) {
            $client = new primecontent;
            $data = $client->primContentLogin();
            $list['list'] = $data;
            $list['nologin'] = true;
            return $list;
        }

       //  $data = $client->primContentLogin($primecontent_keyword);
       $keyword = new stdClass();
        $keyword->label = get_string('keyword', 'repository_primecontent').': ';
        $keyword->id    = 'input_text_keyword';
        $keyword->type  = 'text';
        $keyword->name  = 'primecontent_keyword';
        $keyword->value = '';

        if ($this->options['ajax']) {
            $form = array();
            $form['login'] = array($keyword);
            //$form['login'] = array($keyword, (object)$maxwidth, (object)$maxheight);
            $form['nologin'] = true;
            $form['norefresh'] = true;
            $form['nosearch'] = true;
            $form['allowcaching'] = false; // indicates that login form can NOT
            // be cached in filepicker.js (maxwidth and maxheight are dynamic)
            return $form;
        } else {
            echo <<<EOD
<table>
<tr>
<td>{$keyword->label}</td><td><input name="{$keyword->name}" type="text" /></td>
</tr>
</table>
<input type="submit" />
EOD;
        }
    }
    //search
    // if this plugin support global search, if this function return
    // true, search function will be called when global searching working
    public function global_search() {
        return false;
    }
    public function search($search_text, $page = 0) {
        global $SESSION;
        if (isset($SESSION->isPrimeUser)) {
            // print_r($SESSION);die('sff');
            if (!$SESSION->isPrimeUser) {
                $keyObj = new stdClass();
                $keyObj->label = "You are not subscribed to Prime.";
                // $keyObj->id    = 'input_text_keyword';
                // $keyObj->type  = 'text';
                // $keyObj->name  = 'primecontent_keyword';
                // $keyObj->value = '';
                $msg = array();
                $msg['login'] = array($keyObj);
                print_error(UNSUBSCRIBE_MSG);
                return;
            }
        } else {    
            print_error(UNSUBSCRIBE_MSG);
            return ;
        }
        $client = new primecontent;
        $search_result = array();
        $search_result['list'] = $client->primContentLogin($search_text);
        $search_result['nologin'] = true;
        return $search_result;
    }

    public function supported_returntypes() {
        return (FILE_INTERNAL | FILE_EXTERNAL);
    }

    public function supported_filetypes() {
        return '*';
        //return array('image/gif', 'image/jpeg', 'image/png');
        // return array('web_image');
    }
}
