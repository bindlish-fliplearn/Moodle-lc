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
 * Internal library of functions for module braincert
 *
 * All the braincert specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Braincert Curl Request.
 *
 * @param array $data
 * @return array
 */
function braincert_get_curl_info($data) {
    global $DB, $CFG;

    $key = $CFG->mod_braincert_apikey;
    $baseurl = $CFG->mod_braincert_baseurl;

    $urlfirstpart = $baseurl."/".$data['task']."?apikey=".$key;

    if ($data['task'] == 'schedule') {
        $initurl = $urlfirstpart."&title=".$data['title']."&timezone=".$data['timezone']."&start_time=".$data['start_time']
                   ."&end_time=".$data['end_time']."&date=".$data['date']."&isVideo=".$data['isvideo']."&ispaid=".$data['ispaid']
                   ."&is_recurring=".$data['is_recurring']."&seat_attendees=".$data['seat_attendees']."&record=".$data['record']
                   ."&isBoard=".$data['isBoard']."&isLang=".$data['isLang']."&isRegion=".$data['isRegion']."&isCorporate="
                   .$data['isCorporate']."&isScreenshare=".$data['isScreenshare']."&isPrivateChat=".$data['isPrivateChat'];

        if (($data['ispaid'] == 1) && isset($data['currency'])) {
            $initurl = $initurl."&currency=".$data['currency'];
        }
        if (($data['is_recurring'] == 1) && isset($data['repeat'])) {
            $initurl = $initurl."&repeat=".$data['repeat']."&end_classes_count=".$data['end_classes_count'];
            if ($data['repeat'] == 6) {
                $initurl = $initurl."&weekdays=".$data['weekdays'];
            }
        }
        if (isset($data['cid'])) {
            $initurl = $initurl."&cid=".$data['cid'];
        }
    } else if ($data['task'] == 'getclasslaunch') {
        $initurl = $urlfirstpart."&class_id=".$data['class_id']."&userId=".$data['userId']."&userName="
                   .urlencode($data['userName'])."&isTeacher=".$data['isTeacher']."&courseName="
                   .$data['courseName']."&lessonName=".$data['lessonName'];
    } else if ($data['task'] == 'listclass') {
        $initurl = $urlfirstpart;
    } else if ($data['task'] == 'removeclass') {
        $initurl = $urlfirstpart."&cid=".$data['cid'];
    } else if ($data['task'] == 'cancelclass') {
        $initurl = $urlfirstpart."&class_id=".$data['class_id']."&isCancel=".$data['isCancel'];
    } else if ($data['task'] == 'addSchemes') {
        $initurl = $urlfirstpart."&class_id=".$data['class_id']."&price=".$data['price']."&scheme_days="
                   .$data['scheme_days']."&times=".$data['times'];
        if (isset($data['numbertimes'])) {
            $initurl = $initurl."&numbertimes=".$data['numbertimes'];
        }
        if (isset($data['id'])) {
            $initurl = $initurl."&id=".$data['id'];
        }
    } else if ($data['task'] == 'removeprice') {
        $initurl = $urlfirstpart."&id=".$data['id'];
    } else if (($data['task'] == 'listSchemes') || ($data['task'] == 'listdiscount')) {
        $initurl = $urlfirstpart."&class_id=".$data['class_id'];
    } else if ($data['task'] == 'addSpecials') {
        $initurl = $urlfirstpart."&class_id=".$data['class_id']."&discount=".$data['discount']
                   ."&start_date=".$data['start_date']."&discount_type=".$data['discount_type'];
        if (isset($data['end_date'])) {
            $initurl = $initurl."&end_date=".$data['end_date'];
        }
        if (isset($data['discount_code']) && isset($data['discount_limit'])) {
            $initurl = $initurl."&discount_code=".$data['discount_code']."&discount_limit=".$data['discount_limit'];
        }
        if (isset($data['discountid'])) {
            $initurl = $initurl."&discountid=".$data['discountid'];
        }
    } else if ($data['task'] == 'removediscount') {
        $initurl = $urlfirstpart."&discountid=".$data['discountid'];
    } else if ($data['task'] == 'getclassreport') {
        $initurl = $urlfirstpart."&classId=".$data['classId'];
    } else if ($data['task'] == 'getclassrecording') {
        $initurl = $urlfirstpart."&class_id=".$data['class_id'];
    } else if (($data['task'] == 'changestatusrecording') || ($data['task'] == 'removeclassrecording')) {
        $initurl = $urlfirstpart."&id=".$data['rid'];
    } else {
        $initurl = $urlfirstpart;
    }
//    echo $initurl; die;

    if (($data['task'] == 'getPaymentInfo') || ($data['task'] == 'getplan')) {
        $ch = curl_init($baseurl);
    } else {
        $ch = curl_init($initurl);
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

    if ($data['task'] == 'getPaymentInfo') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'task=getPaymentInfo&apikey='.$key);
    } else if ($data['task'] == 'getplan') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'task=getplan&apikey='.$key);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);

    $finalresult = json_decode($result, true);
    return $finalresult;
}

/**
 * braincert_get_plan.
 *
 * @return array
 */
function braincert_get_plan() {
    $data['task']  = 'getplan';
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_get_class_list.
 *
 * @return array
 */
function braincert_get_class_list() {
    $data['task'] = 'listclass';
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_get_class.
 *
 * @param int $classid
 * @return int
 */
function braincert_get_class($classid) {
    global $DB, $CFG;
    $key = $CFG->mod_braincert_apikey;
    $baseurl = $CFG->mod_braincert_baseurl;
    $ch = curl_init($baseurl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'task=getclass&apikey='.$key.'&class_id='.$classid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    $result = json_decode($result, true);
    if ($result) {
        return $result[0];
    }
}

/**
 * braincert_remove_class.
 *
 * @param int $classid
 * @return array
 */
function braincert_remove_class($classid) {
    $data['task']  = 'removeclass';
    $data['cid']   = $classid;
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_cancel_class.
 *
 * @param int $classid
 * @param int $all
 * @return array
 */
function braincert_cancel_class($classid, $all) {
    $data['task']  = 'cancelclass';
    $data['class_id']   = $classid;
    $data['isCancel']   = $all;
    $result = braincert_get_curl_info($data);
    return $result;
}


/**
 * braincert_get_class_report.
 *
 * @param int $classid
 * @return array
 */
function braincert_get_class_report($classid) {
    $data['task']    = 'getclassreport';
    $data['classId'] = $classid;
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_get_payment_info.
 *
 * @return array
 */
function braincert_get_payment_info() {
    $data['task']  = 'getPaymentInfo';
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_get_launch_url.
 *
 * @param array $item
 * @return array
 */
function braincert_get_launch_url($item) {
    $data['task']       = 'getclasslaunch';
    $data['userId']     = $item['userid'];
    $data['userName']   = $item['username'];
    $data['lessonName'] = preg_replace('/\s+/', '', $item['classname']);
    $data['courseName'] = preg_replace('/\s+/', '', $item['classname']);
    $data['isTeacher']  = $item['isteacher'];
    $data['class_id']   = $item['classid'];

    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_get_class_recording.
 *
 * @param int $classid
 * @return array
 */
function braincert_get_class_recording($classid) {
    $data['task']     = 'getclassrecording';
    $data['class_id'] = $classid;
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_change_status_recording.
 *
 * @param int $rid
 * @return array
 */
function braincert_change_status_recording($rid) {
    $data['task'] = 'changestatusrecording';
    $data['rid']  = $rid;
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_remove_recording.
 *
 * @param int $rid
 * @return array
 */
function braincert_remove_recording($rid) {
    $data['task'] = 'removeclassrecording';
    $data['rid']  = $rid;
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_add_price.
 *
 * @param array $item
 * @return array
 */
function braincert_add_price($item) {
    $data['task']        = 'addSchemes';
    $data['price']       = $item['price'];
    $data['scheme_days'] = $item['schemedays'];
    $data['times']       = $item['times'];
    $data['class_id']    = $item['classid'];
    if (isset($item['numbertimes'])) {
        $data['numbertimes'] = $item['numbertimes'];
    }
    if (isset($item['id'])) {
        $data['id']     = $item['id'];
    }
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_get_price_list.
 *
 * @param int $classid
 * @return array
 */
function braincert_get_price_list($classid) {
    $data['task']     = 'listSchemes';
    $data['class_id'] = $classid;
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_remove_price.
 *
 * @param int $pid
 * @return array
 */
function braincert_remove_price($pid) {
    $data['task'] = 'removeprice';
    $data['id']   = $pid;
    $result       = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_add_discount.
 *
 * @param array $item
 * @return array
 */
function braincert_add_discount($item) {
    $data['task']               = 'addSpecials';
    $data['class_id']           = $item['classid'];
    $data['discount']           = $item['discount'];
    $data['start_date']         = $item['startdate'];
    $data['discount_type']      = $item['dtype'];
    if (isset($item['enddate'])) {
        $data['end_date']       = $item['enddate'];
    }
    if (isset($item['dcode']) && isset($item['dlimit'])) {
        $data['discount_code']  = $item['dcode'];
        $data['discount_limit'] = $item['dlimit'];
    }
    if ($item['did'] > 0) {
        $data['discountid']     = $item['did'];
    }
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_discount_list.
 *
 * @param int $classid
 * @return array
 */
function braincert_discount_list($classid) {
    $data['task']     = 'listdiscount';
    $data['class_id'] = $classid;
    $result = braincert_get_curl_info($data);
    return $result;
}

/**
 * braincert_remove_discount.
 *
 * @param int $did
 * @return array
 */
function braincert_remove_discount($did) {
    $data['task']       = 'removediscount';
    $data['discountid'] = $did;
    $result = braincert_get_curl_info($data);
    return $result;
}