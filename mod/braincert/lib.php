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
 * Library of interface functions and constants for module braincert
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('locallib.php');
global $defaulttimezone;
$defaulttimezone = array(
    '1'    => 'Asia/Dubai',
    '2'    => 'Asia/Baghdad',
    '3'    => 'Canada/Atlantic',
    '4'    => 'Australia/Darwin',
    '5'    => 'Australia/Canberra',
    '6'    => 'Atlantic/Azores',
    '7'    => 'Canada/Saskatchewan',
    '8'    => 'Atlantic/Cape_Verde',
    '9'    => 'Asia/Baku',
    '10'   => 'Australia/Adelaide',
    '11'   => 'America/Belize',
    '12'   => 'Asia/Dhaka',
    '13'   => 'Europe/Belgrade',
    '14'   => 'Europe/Sarajevo',
    '15'   => 'Pacific/Guadalcanal',
    '16'   => 'America/Chicago',
    '17'   => 'Asia/Hong_Kong',
    '18'   => 'Etc/GMT-12',
    '19'   => 'Africa/Addis_Ababa',
    '20'   => 'Australia/Brisbane',
    '21'   => 'Europe/Bucharest',
    '22'   => 'America/Sao_Paulo',
    '23'   => 'America/New_York',
    '24'   => 'Africa/Cairo',
    '25'   => 'Asia/Yekaterinburg',
    '26'   => 'Pacific/Fiji',
    '27'   => 'Europe/Helsinki',
    '28'   => 'Europe/London',
    '29'   => 'America/Godthab',
    '30'   => 'Africa/Abidjan',
    '31'   => 'Europe/Minsk',
    '32'   => 'Pacific/Honolulu',
    '33'   => 'Asia/Kolkata',
    '34'   => 'Asia/Tehran',
    '35'   => 'Asia/Jerusalem',
    '37'   => 'America/Cancun',
    '38'   => 'America/Chihuahua',
    '39'   => 'America/Noronha',
    '40'   => 'America/Denver',
    '41'   => 'Asia/Rangoon',
    '42'   => 'Asia/Novosibirsk',
    '44'   => 'Pacific/Auckland',
    '45'   => 'Canada/Newfoundland',
    '46'   => 'Asia/Irkutsk',
    '47'   => 'Asia/Kabul',
    '48'   => 'America/Anchorage',
    '49'   => 'Asia/Riyadh',
    '50'   => 'Asia/Krasnoyarsk',
    '51'   => 'America/Santiago',
    '52'   => 'America/Los_Angeles',
    '53'   => 'Europe/Brussels',
    '54'   => 'Europe/Moscow',
    '55'   => 'America/Argentina/Buenos_Aires',
    '56'   => 'America/Bogota',
    '57'   => 'America/La_Paz',
    '58'   => 'Pacific/Samoa',
    '59'   => 'Asia/Bangkok',
    '60'   => 'Asia/Kuala_Lumpur',
    '61'   => 'Africa/Blantyre',
    '62'   => 'Asia/Colombo',
    '63'   => 'Asia/Taipei',
    '64'   => 'Australia/Hobart',
    '65'   => 'Asia/Tokyo',
    '67'   => 'America/Indiana/Indianapolis',
    '68'   => 'America/Phoenix',
    '69'   => 'Asia/Vladivostok',
    '70'   => 'Australia/Perth',
    '71'   => 'Africa/Algiers',
    '72'   => 'Europe/Amsterdam',
    '73'   => 'Asia/Karachi',
    '74'   => 'Pacific/Guam',
    '75'   => 'Asia/Yakutsk',
    '76'   => 'America/Caracas',
    '77'   => 'Asia/Seoul',
    '83'   => 'Asia/Amman',
    '84'   => 'Asia/Beirut',
    '86'   => 'Africa/Windhoek',
    '87'   => 'Asia/Tbilisi',
    '88'   => 'Asia/Baku',
    '89'   => 'Indian/Mauritius',
    '90'   => 'Asia/Karachi',
    '91'   => 'Asia/Kathmandu',
    '94'   => 'America/Argentina/Buenos_Aires',
    '95'   => 'America/Montevideo',
    '96'   => 'America/Manaus',
    '104'  => 'America/Tijuana',
    '105'  => 'America/New_York'
);
/**
 * Defines the features that are supported by braincert.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function braincert_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
          return true;
        case FEATURE_GROUPINGS:
          return true;
        case FEATURE_GROUPMEMBERSONLY:
          return true;
        case FEATURE_MOD_INTRO:
          return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
          return true;
        case FEATURE_COMPLETION_HAS_RULES:
          return true;
        case FEATURE_GRADE_HAS_GRADE:
          return true;
        case FEATURE_GRADE_OUTCOMES:
          return true;
        case FEATURE_BACKUP_MOODLE2:
          return true;
        default:
          return null;
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $braincert
 * @return bool|int
 */
function braincert_add_instance($braincert) {
    global $DB, $CFG, $defaulttimezone;

    $timezone = $defaulttimezone[33];

    $startdate = date('Y-m-d', $braincert->start_date);
    $startdatetime = new DateTime($startdate.' '.$braincert->start_time, new DateTimeZone($timezone));
    $startdatetimestamp = $startdatetime->getTimestamp();

    $data['task']           = 'schedule';
    $data['title']          = urlencode($braincert->name);
    $data['timezone']       = $braincert->braincert_timezone;
    $data['date']           = date('Y-m-d', $startdatetimestamp);
    $data['start_time']     = strtoupper($braincert->start_time);
    $data['end_time']       = strtoupper($braincert->end_time);
    $data['ispaid']         = $braincert->class_type;
    if (($braincert->class_type == 1) && isset($braincert->currency)) {
        $data['currency']   = $braincert->currency;
    }
    $data['is_recurring']   = $braincert->is_recurring;
    if (($braincert->is_recurring == 1) && isset($braincert->class_repeats)) {
        $data['repeat']     = $braincert->class_repeats;
        $data['end_classes_count'] = $braincert->end_classes_count;
        if (($braincert->class_repeats == 6) && isset($braincert->weekdays)) {
            $data['weekdays']   = implode(",", $braincert->weekdays);
        }
    }
    $data['isBoard']        = $braincert->classroomtype;
    if ($braincert->change_language == 0) {
        $data['isLang']     = $braincert->bc_interface_language;
    } else {
        $data['isLang']     = 0;
    }
    $data['isRegion']       = $braincert->is_region;
    $data['isvideo']       = $braincert->isvideo;
    $data['isCorporate']    = $braincert->is_corporate;
    $data['isScreenshare']  = $braincert->screen_sharing;
    $data['isPrivateChat']  = $braincert->private_chat;
    $data['seat_attendees'] = $braincert->maxattendees;
    $data['record']         = $braincert->record_type;

    $getschedule = braincert_get_curl_info($data);
    if ($getschedule['status'] == "ok") {
        $braincertclass = new stdClass();
        $braincertclass->course                = $braincert->course;
        $braincertclass->name                  = $braincert->name;
        $braincertclass->class_id              = $getschedule['class_id'];
        $braincertclass->intro                 = $braincert->intro;
        $braincertclass->introformat           = $braincert->introformat;
        $braincertclass->braincert_timezone    = $braincert->braincert_timezone;
        $braincertclass->default_timezone      = $timezone;
        $braincertclass->start_date            = $startdatetimestamp;
        $braincertclass->start_time            = $braincert->start_time;
        $braincertclass->end_time              = $braincert->end_time;
        $braincertclass->is_region             = $braincert->is_region;
        $braincertclass->is_recurring          = $braincert->is_recurring;
        if (isset($braincert->class_repeats)) {
            $braincertclass->class_repeats     = $braincert->class_repeats;
        }
        if (isset($braincert->weekdays)) {
            $braincertclass->weekdays          = implode(",", $braincert->weekdays);
        }
        $braincertclass->end_classes_count     = $braincert->end_classes_count;
        $braincertclass->change_language       = $braincert->change_language;
        if ($braincert->change_language == 0) {
            $braincertclass->bc_interface_language = $braincert->bc_interface_language;
        } else {
            $braincertclass->bc_interface_language = 0;
        }
        $braincertclass->record_type           = $braincert->record_type;
        $braincertclass->classroomtype         = $braincert->classroomtype;
        $braincertclass->is_corporate          = $braincert->is_corporate;
        $braincertclass->isvideo               = $braincert->isvideo;
        $braincertclass->screen_sharing        = $braincert->screen_sharing;
        $braincertclass->private_chat          = $braincert->private_chat;
        $braincertclass->class_type            = $braincert->class_type;
        if (isset($braincert->currency)) {
            $braincertclass->currency          = $braincert->currency;
        }
        $braincertclass->maxattendees          = $braincert->maxattendees;
        $braincertclass->groupingid            = $braincert->groupingid;
        $braincertclass->timemodified          = time();

        $bcid = $DB->insert_record("braincert", $braincertclass);

        if ($CFG->version >= 2017051500) {
            $completiontimeexpected = !empty($braincert->completionexpected) ? $braincert->completionexpected : null;
            \core_completion\api::update_completion_date_event($braincert->coursemodule,
            'braincert', $bcid, $completiontimeexpected);
        }
        return $bcid;
    } else {
        return $getschedule['error'];
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $braincert
 * @return bool
 */
function braincert_update_instance($braincert) {
    global $DB, $CFG, $defaulttimezone;

    $timezone = $defaulttimezone[$braincert->braincert_timezone];
    $braincertclass = $DB->get_record('braincert', array('id' => $braincert->instance), '*', MUST_EXIST);
    $classid = $braincertclass->class_id;

    $classdata['task']   = 'listclass';
    $classdata['search'] = preg_replace('/\s+/', '', $braincertclass->name);
    $getclassdetails = braincert_get_curl_info($classdata);

    $startdate = date('Y-m-d', $braincert->start_date);
    $startdatetime = new DateTime($startdate.' '.$braincert->start_time, new DateTimeZone($timezone));
    $startdatetimestamp = $startdatetime->getTimestamp();

    foreach ($getclassdetails['classes'] as $getclassdetail) {
        if ($getclassdetail['id'] == $braincertclass->class_id) {
            if ($getclassdetail['status'] == 'Upcoming') {
                $data['task']           = 'schedule';
                $data['cid']            = $getclassdetail['id'];
                $data['title']          = preg_replace('/\s+/', '', $braincert->name);
                $data['timezone']       = $braincert->braincert_timezone;
                $data['date']           = date('Y-m-d', $startdatetimestamp);
                $data['start_time']     = strtoupper($braincert->start_time);
                $data['end_time']       = strtoupper($braincert->end_time);
                $data['ispaid']         = $braincert->class_type;
                if (($braincert->class_type == 1) && isset($braincert->currency)) {
                    $data['currency']   = $braincert->currency;
                }
                $data['is_recurring']   = $braincert->is_recurring;
                if (($braincert->is_recurring == 1) && isset($braincert->class_repeats)) {
                    $data['repeat']     = $braincert->class_repeats;
                    $data['end_classes_count'] = $braincert->end_classes_count;
                    if (($braincert->class_repeats == 6) && isset($braincert->weekdays)) {
                        $data['weekdays']   = implode(",", $braincert->weekdays);
                    }
                }
                $data['isBoard']        = $braincert->classroomtype;
                if ($braincert->change_language == 0) {
                    $data['isLang']     = $braincert->bc_interface_language;
                } else {
                    $data['isLang']     = 0;
                }
                $data['isRegion']       = $braincert->is_region;
                $data['isvideo']        = $braincert->isvideo;
                $data['isCorporate']    = $braincert->is_corporate;
                $data['isScreenshare']  = $braincert->screen_sharing;
                $data['isPrivateChat']  = $braincert->private_chat;
                $data['seat_attendees'] = $braincert->maxattendees;
                $data['record']         = $braincert->record_type;

                $getschedule = braincert_get_curl_info($data);
            }
            if (isset($getschedule['status']) && ($getschedule['status'] == "ok") && ($getschedule['method'] == "updateclass")) {
                $classid = $getschedule['class_id'];

                $braincertclass = new stdClass();
                $braincertclass->id                    = $braincert->instance;
                $braincertclass->course                = $braincertclass->course;
                $braincertclass->name                  = $braincert->name;
                $braincertclass->class_id              = $classid;
                $braincertclass->intro                 = $braincert->intro;
                $braincertclass->introformat           = $braincert->introformat;
                $braincertclass->braincert_timezone    = $braincert->braincert_timezone;
                $braincertclass->default_timezone      = $timezone;
                $braincertclass->start_date            = $startdatetimestamp;
                $braincertclass->start_time            = $braincert->start_time;
                $braincertclass->end_time              = $braincert->end_time;
                $braincertclass->is_region             = $braincert->is_region;
                $braincertclass->is_recurring          = $braincert->is_recurring;
                if (isset($braincert->class_repeats)) {
                    $braincertclass->class_repeats     = $braincert->class_repeats;
                } else {
                    $braincertclass->class_repeats     = $braincertclass->class_repeats;
                }
                if (isset($braincert->weekdays)) {
                    if ($braincert->class_repeats == 6) {
                        $braincertclass->weekdays      = implode(",", $braincert->weekdays);
                    } else {
                        $braincertclass->weekdays      = '';
                    }
                } else {
                    $braincertclass->weekdays          = $braincertclass->weekdays;
                }

                $braincertclass->end_classes_count     = $braincert->end_classes_count;

                $braincertclass->change_language       = $braincert->change_language;

                if ($braincert->change_language == 0) {
                    $braincertclass->bc_interface_language = $braincert->bc_interface_language;
                } else {
                    $braincertclass->bc_interface_language = $braincertclass->bc_interface_language;
                }
                $braincertclass->record_type           = $braincert->record_type;
                $braincertclass->classroomtype         = $braincert->classroomtype;
                $braincertclass->is_corporate          = $braincert->is_corporate;
                $braincertclass->isvideo               = $braincert->isvideo;
                $braincertclass->screen_sharing        = $braincert->screen_sharing;
                $braincertclass->private_chat          = $braincert->private_chat;
                $braincertclass->class_type            = $braincert->class_type;
                if (isset($braincert->currency)) {
                    $braincertclass->currency          = $braincert->currency;
                } else {
                    $braincertclass->currency          = $braincertclass->currency;
                }
                $braincertclass->maxattendees          = $braincert->maxattendees;
                $braincertclass->groupingid            = $braincert->groupingid;
                $braincertclass->timemodified          = time();

                if ($CFG->version >= 2017051500) {
                    $completiontimeexpected = !empty($braincert->completionexpected) ? $braincert->completionexpected : null;
                    \core_completion\api::update_completion_date_event($braincert->coursemodule,
                    'braincert', $braincertclass->id, $completiontimeexpected);
                }
                return $DB->update_record("braincert", $braincertclass);
            } else {
                return $getclassdetail['status']." schedule class can not be update.";
            }
        }
    }
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool
 */
function braincert_delete_instance($id) {
    global $DB;

    if (! $braincert = $DB->get_record("braincert", array("id" => $id))) {
        return false;
    }
    $result = true;

    if (isset($braincert->class_id)) {
        $removedata['task']  = 'removeclass';
        $removedata['cid']   = $braincert->class_id;

        $getremovestatus = braincert_get_curl_info($removedata);
        if ($getremovestatus['status'] == "ok") {
            echo get_string('braincert_class_removed', 'braincert');
        }
    }

    if ($CFG->version >= 2017051500) {
        $cm = get_coursemodule_from_instance('braincert', $id);
        \core_completion\api::update_completion_date_event($cm->id, 'braincert', $braincert->id, null);
    }
    if (! $DB->delete_records("braincert", array("id" => $braincert->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function braincert_get_coursemodule_info($coursemodule) {
    global $DB;

    if ($braincert = $DB->get_record('braincert', array('id' => $coursemodule->instance), 'id, name, intro, introformat')) {
        if (empty($braincert->name)) {
            // Braincert name missing, fix it.
            $braincert->name = "braincert{$braincert->id}";
            $DB->set_field('braincert', 'name', $braincert->name, array('id' => $braincert->id));
        }
        $info = new cached_cm_info();
        // No filtering hre because this info is cached and filtered later.
        $info->content = format_module_intro('braincert', $braincert, $coursemodule->id, false);
        $info->name  = $braincert->name;
        return $info;
    } else {
        return null;
    }
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function braincert_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}