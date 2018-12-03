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

define('WIKIMEDIA_THUMBS_PER_PAGE', 24);
define('WIKIMEDIA_FILE_NS', 6);
define('WIKIMEDIA_IMAGE_SIDE_LENGTH', 1024);
define('WIKIMEDIA_THUMB_SIZE', 120);

class primecontent {
    private $_conn  = null;
    private $_param = array();

    public function __construct($url = '') {
        if (empty($url)) {
            $this->api = 'https://commons.wikimedia.org/w/api.php';
        } else {
            $this->api = $url;
        }
        $this->_param['format'] = 'php';
        $this->_param['redirects'] = true;
        $this->_conn = new curl(array('cache'=>true, 'debug'=>false));
    }
    public function login($user, $pass) {
        $this->_param['action']   = 'login';
        $this->_param['lgname']   = $user;
        $this->_param['lgpassword'] = $pass;
        $content = $this->_conn->post($this->api, $this->_param);
        $result = unserialize($content);
        if (!empty($result['result']['sessionid'])) {
            $this->userid = $result['result']['lguserid'];
            $this->username = $result['result']['lgusername'];
            $this->token = $result['result']['lgtoken'];
            return true;
        } else {
            return false;
        }
    }
    public function logout() {
        $this->_param['action']   = 'logout';
        $content = $this->_conn->post($this->api, $this->_param);
        return;
    }
    public function get_image_url($titles) {
        $image_urls = array();
        $this->_param['action'] = 'query';
        if (is_array($titles)) {
            foreach ($titles as $title) {
                $this->_param['titles'] .= ('|'.urldecode($title));
            }
        } else {
            $this->_param['titles'] = urldecode($titles);
        }
        $this->_param['prop']   = 'imageinfo';
        $this->_param['iiprop'] = 'url';
        $content = $this->_conn->post($this->api, $this->_param);
        $result = unserialize($content);
        foreach ($result['query']['pages'] as $page) {
            if (!empty($page['imageinfo'][0]['url'])) {
                $image_urls[] = $page['imageinfo'][0]['url'];
            }
        }
        return $image_urls;
    }
    public function get_images_by_page($title) {
        $image_urls = array();
        $this->_param['action'] = 'query';
        $this->_param['generator'] = 'images';
        $this->_param['titles'] = urldecode($title);
        $this->_param['prop']   = 'images|info|imageinfo';
        $this->_param['iiprop'] = 'url';
        $content = $this->_conn->post($this->api, $this->_param);
        $result = unserialize($content);
        if (!empty($result['query']['pages'])) {
            foreach ($result['query']['pages'] as $page) {
                $image_urls[$page['title']] = $page['imageinfo'][0]['url'];
            }
        }
        return $image_urls;
    }
    /**
     * Generate thumbnail URL from image URL.
     *
     * @param string $image_url
     * @param int $orig_width
     * @param int $orig_height
     * @param int $thumb_width
     * @param bool $force When true, forces the generation of a thumb URL.
     * @global object OUTPUT
     * @return string
     */
    public function get_thumb_url($image_url, $orig_width, $orig_height, $thumb_width = 75, $force = false) {
        global $OUTPUT;

        if (!$force && $orig_width <= $thumb_width && $orig_height <= $thumb_width) {
            return $image_url;
        } else {
            $thumb_url = '';
            $commons_main_dir = 'https://upload.wikimedia.org/wikipedia/commons/';
            if ($image_url) {
                $short_path = str_replace($commons_main_dir, '', $image_url);
                $extension = strtolower(pathinfo($short_path, PATHINFO_EXTENSION));
                if (strcmp($extension, 'gif') == 0) {  //no thumb for gifs
                    return $OUTPUT->image_url(file_extension_icon('.gif', $thumb_width))->out(false);
                }
                $dir_parts = explode('/', $short_path);
                $file_name = end($dir_parts);
                if ($orig_height > $orig_width) {
                    $thumb_width = round($thumb_width * $orig_width/$orig_height);
                }
                $thumb_url = $commons_main_dir . 'thumb/' . implode('/', $dir_parts) . '/'. $thumb_width .'px-' . $file_name;
                if (strcmp($extension, 'svg') == 0) {  //png thumb for svg-s
                    $thumb_url .= '.png';
                }
            }
            return $thumb_url;
        }
    }

    /**
     * Search for images and return photos array.
     *
     * @param string $keyword
     * @param int $page
     * @param array $params additional query params
     * @return array
     */
    public function search_images($keyword, $page = 0, $params = array()) {
        global $OUTPUT;
        $files_array = array();
        $this->_param['action'] = 'query';
        $this->_param['generator'] = 'search';
        $this->_param['gsrsearch'] = $keyword;
        $this->_param['gsrnamespace'] = WIKIMEDIA_FILE_NS;
        $this->_param['gsrlimit'] = WIKIMEDIA_THUMBS_PER_PAGE;
        $this->_param['gsroffset'] = $page * WIKIMEDIA_THUMBS_PER_PAGE;
        $this->_param['prop']   = 'imageinfo';
        $this->_param['iiprop'] = 'url|dimensions|mime|timestamp|size|user';
        $this->_param += $params;
        $this->_param += array('iiurlwidth' => WIKIMEDIA_IMAGE_SIDE_LENGTH,
            'iiurlheight' => WIKIMEDIA_IMAGE_SIDE_LENGTH);
        //didn't work with POST
        $content = $this->_conn->get($this->api, $this->_param);
        $result = unserialize($content);
        if (!empty($result['query']['pages'])) {
            foreach ($result['query']['pages'] as $page) {
                $title = $page['title'];
                $file_type = $page['imageinfo'][0]['mime'];
                $image_types = array('image/jpeg', 'image/png', 'image/gif', 'image/svg+xml');
                if (in_array($file_type, $image_types)) {  //is image
                    $extension = pathinfo($title, PATHINFO_EXTENSION);
                    $issvg = strcmp($extension, 'svg') == 0;

                    // Get PNG equivalent to SVG files.
                    if ($issvg) {
                        $title .= '.png';
                    }

                    // The thumbnail (max size requested) is smaller than the original size, we will use the thumbnail.
                    if ($page['imageinfo'][0]['thumbwidth'] < $page['imageinfo'][0]['width']) {
                        $attrs = array(
                            //upload scaled down image
                            'source' => $page['imageinfo'][0]['thumburl'],
                            'image_width' => $page['imageinfo'][0]['thumbwidth'],
                            'image_height' => $page['imageinfo'][0]['thumbheight']
                        );
                        if ($attrs['image_width'] <= WIKIMEDIA_THUMB_SIZE && $attrs['image_height'] <= WIKIMEDIA_THUMB_SIZE) {
                            $attrs['realthumbnail'] = $attrs['source'];
                        }
                        if ($attrs['image_width'] <= 24 && $attrs['image_height'] <= 24) {
                            $attrs['realicon'] = $attrs['source'];
                        }

                    // We use the original file.
                    } else {
                        $attrs = array(
                            //upload full size image
                            'image_width' => $page['imageinfo'][0]['width'],
                            'image_height' => $page['imageinfo'][0]['height'],
                            'size' => $page['imageinfo'][0]['size']
                        );

                        // We cannot use the source when the file is SVG.
                        if ($issvg) {
                            // So we generate a PNG thumbnail of the file at its original size.
                            $attrs['source'] = $this->get_thumb_url($page['imageinfo'][0]['url'], $page['imageinfo'][0]['width'],
                                $page['imageinfo'][0]['height'], $page['imageinfo'][0]['width'], true);
                        } else {
                            $attrs['source'] = $page['imageinfo'][0]['url'];
                        }
                    }
                    $attrs += array(
                        'realthumbnail' => $this->get_thumb_url($page['imageinfo'][0]['url'], $page['imageinfo'][0]['width'], $page['imageinfo'][0]['height'], WIKIMEDIA_THUMB_SIZE),
                        'realicon' => $this->get_thumb_url($page['imageinfo'][0]['url'], $page['imageinfo'][0]['width'], $page['imageinfo'][0]['height'], 24),
                        'author' => $page['imageinfo'][0]['user'],
                        'datemodified' => strtotime($page['imageinfo'][0]['timestamp']),
                        );
                } else {  // other file types
                    $attrs = array('source' => $page['imageinfo'][0]['url']);
                }
                $files_array[] = array(
                    'title'=>substr($title, 5),         //chop off 'File:'
                    'thumbnail' => $OUTPUT->image_url(file_extension_icon(substr($title, 5), WIKIMEDIA_THUMB_SIZE))->out(false),
                    'thumbnail_width' => WIKIMEDIA_THUMB_SIZE,
                    'thumbnail_height' => WIKIMEDIA_THUMB_SIZE,
                    'license' => 'cc-sa',
                    // the accessible url of the file
                    'url'=>$page['imageinfo'][0]['descriptionurl']
                ) + $attrs;
            }
        }
        return $files_array;
    }
    
    public function primContentLogin($keyword = '') {
        //$this->_param['action']   = 'login';
        // $param['loginId']   = $loginId;
        // $param['password'] = $pass;
        // $param['platform'] = "web";
        // $data["login"] = $param;

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
            // Course Section mapping to guru_chapter_mapping
            // if (!empty($courseSection)) {
                // $sectionData = $DB->get_record('guru_chapter_mapping', array('section_id'=>$courseSection->id), '*');
                // $chapterId = !empty($sectionData) ? $sectionData->chapter_id : '';
            // }
        }

        if (empty($keyword)) {
            if (!empty($subjectId)) {
                $options = 'subjectId=' . $subjectId;
                if (!empty($chapterId)) {
                    $options = $options . 'chapterId=' . $chapterId;
                }
                $api_path = PRIME_URL . "/v1/getAllResource?$options";
                // $api_path = "https://dev3ptoc.fliplearn.com/v1/getAllResource?subjectId=218";
                $content = $this->_conn->get($api_path,'');
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
                                                $title .= '.mp4';
                                                
                                                if (strpos($page->thumbnail, '?'))
                                                    $thumbnail = $page->thumbnail . '&resourceId=' . $page->id;
                                                else
                                                    $thumbnail = $page->thumbnail . '?resourceId=' . $page->id;
                                                $files_array[] = array(
                                                    'title'=>$title,
                                                    'thumbnail' => $page->thumbnail,
                                                    'thumbnail_width' => WIKIMEDIA_THUMB_SIZE,
                                                    'thumbnail_height' => WIKIMEDIA_THUMB_SIZE,
                                                    'license' => 'cc-sa',
                                                    'source' => $thumbnail,
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                // print_r($files_array);die('qwe');
                    if (!empty($files_array)) {
                        return $files_array;
                    }
                }
            } 
            if ($search) {
                $keyword = new stdClass();
                $keyword->label = get_string('keyword', 'repository_primecontent').': ';
                $keyword->id    = 'input_text_keyword';
                $keyword->type  = 'text';
                $keyword->name  = 'primecontent_keyword';
                $keyword->value = '';
                if ($this->options['ajax']) {
                    $form = array();
                    $form['login'] = array($keyword);
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
            return;
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
                    'loginId: ' . USER_LOGIN,
                    'sessionToken: ' . SESSION_TOKEN,
                    'platform: web',
                    '3dSupport: 1',
                    'Connection: keep-alive',
                    'Cache-Control: no-cache'));
            $content = $this->_conn->get($api_path,'');
            $result = json_decode($content);

            if (isset($result->response) && !empty($result->response)) {
                foreach ($result->response as $page) {
                    $thumbnail = '';
                    $title = $page->topicName;
                    $title .= '.mp4';
                    
                    if (strpos($page->thumbnail, '?'))
                        $thumbnail = $page->thumbnail . '&resourceId=' . $page->resourceLanguages[0]->resourceId;
                    else
                        $thumbnail = $page->thumbnail . '?resourceId=' . $page->resourceLanguages[0]->resourceId;
                    $files_array[] = array(
                        'title'=>$title,         //chop off 'File:'
                        'thumbnail' => $page->thumbnail,
                        'thumbnail_width' => WIKIMEDIA_THUMB_SIZE,
                        'thumbnail_height' => WIKIMEDIA_THUMB_SIZE,
                        'license' => 'cc-sa',
                        // the accessible url of the file
                        // 'url'=>'https://media.fliplearn.com/fliplearnaes/_definst_/s3/b2ccontents/EOL/Contents/2014050700182179/2014050700182179.smil/playlist.m3u8?wowzatokenstarttime=1540787744&wowzatokenendtime=1543466144&wowzatokenhash=JhvSOfXy3_LPejvWRBmeDi_7FnZ39_sS_q3YL5jiRn4=',
                        'source' => $thumbnail,
                        // 'resource_id' => $page->resourceLanguages[0]->resourceId,
                        // 'url' => $page->resourceLanguages[0]->resourceId
                    );
                }
            }
        }
        return $files_array;
    }

}
