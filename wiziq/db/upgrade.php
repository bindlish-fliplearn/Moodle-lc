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
 * This file keeps track of upgrades to the wiziq module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_wiziq
 * @copyright  www.wiziq.com 
 * @author     kirandeep@authorgen.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute wiziq upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_wiziq_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
    if ($oldversion < 2007040100) {
        // Define field course to be added to wiziq
        $table = new xmldb_table('wiziq');

        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10',
                                XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '2', 'id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('insescod', XMLDB_TYPE_INTEGER, '10',
                                XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'wtype');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10',
                                   XMLDB_UNSIGNED, null, null, null, 'course');
            $dbman->change_field_type($table, $field);  //change field type
        } else {
            $field = new xmldb_field('insescod');
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10',
                                   XMLDB_UNSIGNED, null, null, null, 'course');
            $dbman->add_field($table, $field);
        }

        // Define field class_id to be added to wiziq
        $field = new xmldb_field('class_id', XMLDB_TYPE_INTEGER, '10',
                                XMLDB_UNSIGNED, null, null, null,
            'insescod');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('name');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_CHAR, '255', null,
                                   XMLDB_NOTNULL, null, null, 'class_id');
            $dbman->change_field_type($table, $field);  //change field type
        }

        // Define field intro to be added to wiziq
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'medium',
                                 null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field introformat to be added to wiziq
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4',
                                 XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'intro');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('wdate', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'wdur');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10',
                                   XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'introformat');
            $dbman->change_field_type($table, $field);  //change field type
            // rename_field field wdate
            $dbman->rename_field($table, $field, 'wiziq_datetime');
        }

        $field = new xmldb_field('timezone', XMLDB_TYPE_CHAR, '100', null,
                                 XMLDB_NOTNULL, null, null, 'statusrecording');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED,
                                   XMLDB_NOTNULL, null, '0', 'wiziq_datetime');
            $dbman->change_field_type($table, $field);  //change field type
            // rename_field field timezone
            $dbman->rename_field($table, $field, 'class_timezone');
        }

        // Define field timecreated to be added to wiziq
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10',
                                 XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'class_timezone');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timemodified to be added to wiziq
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10',
                                 XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('wdur', XMLDB_TYPE_CHAR, '255', null, null, null, null,
            'wtime');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                   XMLDB_NOTNULL, null, '30', 'timemodified');
            $dbman->change_field_type($table, $field);  //change field type
            // rename_field field wdur
            $dbman->rename_field($table, $field, 'duration');
        }

        $field = new xmldb_field('langculturename', XMLDB_TYPE_CHAR, '250', null, null, null, null,
            'oldclasses');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_CHAR, '255', null,
                                   XMLDB_NOTNULL, null, null, 'duration');
            $dbman->change_field_type($table, $field);  //change field type
            // rename_field field langculturename
            $dbman->rename_field($table, $field, 'vc_language');
        }

        $field = new xmldb_field('statusrecording', XMLDB_TYPE_INTEGER, '1',
                                 XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null,
            'insescod');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10',
                                   XMLDB_UNSIGNED, null, null, '0', 'vc_language');
            $dbman->change_field_type($table, $field);  //change field type
            // rename_field field statusrecording
            $dbman->rename_field($table, $field, 'recording');
        }

        // Define field presenter_id to be added to wiziq
        $field = new xmldb_field('presenter_id', XMLDB_TYPE_INTEGER, '10',
                                 XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'recording');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field lasteditorid to be added to wiziq
        $table = new xmldb_table('wiziq');
        $field = new xmldb_field('lasteditorid', XMLDB_TYPE_INTEGER, '10',
                                 XMLDB_UNSIGNED, null, null, null,
            'presenter_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field_presenterid = new xmldb_field('presenter_id');
        $table_wiziq_attendee = new xmldb_table('wiziq_attendee_info');
        if ($dbman->field_exists($table, $field_presenterid)) {
            $table_exist_attandee = $dbman->table_exists($table_wiziq_attendee);
            $fiels_exist_pid = $dbman->field_exists($table, $field);
            if ($table_exist_attandee && $fiels_exist_pid) {
                $sql = "SELECT e.id,e.userid,e.insescod FROM {wiziq_attendee_info} e ";
                $sql .= "INNER JOIN {wiziq} c WHERE c.insescod=e.insescod";
                $rs = $DB->get_records_sql($sql);
                foreach ($rs as $res) {
                    $dataobject = new stdClass();
                    $dataobject->id= $res->id;
                    $dataobject->presenter_id = $res->userid;
                    $dataobject->lasteditorid = $res->userid;
                    $dataobject->insescod = $res->insescod;
                    $DB->update_record('wiziq', $dataobject);
                }
            }
        }

        // Define field class_status to be added to wiziq
        $field = new xmldb_field('class_status', XMLDB_TYPE_CHAR, '255', null, null, null, null,
            'lasteditorid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field recording_link to be added to wiziq
        $field = new xmldb_field('recording_link', XMLDB_TYPE_TEXT, null, null, null, null, null,
            'class_status');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('recordingurl', XMLDB_TYPE_CHAR, '255', null, null, null, null,
            'attendeeurl');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_TEXT, null, null, null, null, null, 'recording_link');
            $dbman->change_field_type($table, $field);  //change field type
            // rename_field field recordingurl
            $dbman->rename_field($table, $field, 'view_recording_link');
        }

        $field_recording_link_status = new xmldb_field('recording_link_status');
        $field = new xmldb_field('recording_link_staus');
        $field_rec_exist = $dbman->field_exists($table, $field_recording_link_status);
        $field_rec_status_exist = $dbman->field_exists($table, $field);
        if (!$field_rec_exist && !$field_rec_status_exist) {
            // Define field recording_link_status to be added to wiziq
            $field_recording_link_status = new xmldb_field('recording_link_status',
                                                XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                                null, null, '0', 'view_recording_link');
            if (!$dbman->field_exists($table, $field_recording_link_status)) {
                $dbman->add_field($table, $field_recording_link_status);
            }
        }
        $field = new xmldb_field('recording_link_staus', XMLDB_TYPE_INTEGER, '10',
                                 XMLDB_UNSIGNED, null, null, '0',
            'view_recording_link');
        if ($dbman->field_exists($table, $field)) {
            if (!$dbman->field_exists($table, $field_recording_link_status)) {
                $dbman->rename_field($table, $field, 'recording_link_status');
            }
        }
        
        // Define field class_master_id to be added to wiziq
        $field = new xmldb_field('class_master_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0',
            'recording_link_status');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Define field presenter_url to be added to wiziq
        $field = new xmldb_field('presenter_url', XMLDB_TYPE_TEXT, null, null, null, null, null,
            'class_master_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Define field common_perma_attendee_url to be added to wiziq
        $field = new xmldb_field('common_perma_attendee_url', XMLDB_TYPE_TEXT, null, null, null, null, null,
            'presenter_url');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define field attendee_limit to be added to wiziq
        $field = new xmldb_field('attendee_limit', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0',
            'common_perma_attendee_url');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field wiziq_recur_class_repeat_type to be added to wiziq
        $field = new xmldb_field('wiziq_recur_class_repeat_type', XMLDB_TYPE_CHAR, '250', null, null, null, null,
            'attendee_limit');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Define field class_schedule to be added to wiziq
        $field = new xmldb_field('class_schedule', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0',
            'wiziq_recur_class_repeat_type');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
          // Define field class_occurrence to be added to wiziq
        $field = new xmldb_field('class_occurrence', XMLDB_TYPE_CHAR, '250', null, null, null, null,
            'class_schedule');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
         // Define field assesstimefinish to be added to wiziq
        $field = new xmldb_field('assesstimefinish', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0',
            'class_occurrence');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
          // Define field specific_week to be added to wiziq
        $field = new xmldb_field('specific_week', XMLDB_TYPE_CHAR, '250', null, null, null, null,
            'assesstimefinish');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
          // Define field days_of_week to be added to wiziq
        $field = new xmldb_field('days_of_week', XMLDB_TYPE_CHAR, '250', null, null, null, null,
            'specific_week');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
          // Define field select_monthly_repeat_type to be added to wiziq
        $field = new xmldb_field('select_monthly_repeat_type', XMLDB_TYPE_CHAR, '250', null, null, null, null,
            'days_of_week');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
          // Define field monthly_date to be added to wiziq
        $field = new xmldb_field('monthly_date', XMLDB_TYPE_CHAR, '250', null, null, null, null,
            'select_monthly_repeat_type');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
          // Define field download_count to be added to wiziq
        $field = new xmldb_field('download_count', XMLDB_TYPE_CHAR, '250', null, null, null, null,
            'monthly_date');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
          // Define field recording_count to be added to wiziq
        $field = new xmldb_field('recording_count', XMLDB_TYPE_CHAR, '250', null, null, null, null,
            'download_count');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        //deleting unused fields for wiziq old table
        $table = new xmldb_table('wiziq');
        $field = new xmldb_field('langdisplayname', XMLDB_TYPE_CHAR, '250',
                                 null, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('oldclasses', XMLDB_TYPE_INTEGER, '1',
                                 XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('wtype', XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('reviewurl', XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('url', XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('attendeeurl', XMLDB_TYPE_CHAR, '255',
                                 null, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('wtime', XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        //updating event table entries for wiziq
        $table = new xmldb_table('event');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('description');
            if ($dbman->field_exists($table, $field)) {
                $records = $DB->get_records_select('event', 'description LIKE  "%mod/wiziq%"',
                        array('description'), '', 'id,name');
                if ($records) {
                    foreach ($records as $record) {
                        $res = new stdClass();
                        $res->id = $record->id;
                        $res->modulename = 'wiziq';
                        $res->description = '';
                        $string = $record->name;
                        $newname = preg_replace('/<img height(.*)> /', '', $string);
                        $res->name = $newname;
                        $DB->update_record('event', $res);
                    }
                }
            }
            $sql = "SELECT e.id,e.instance, e.courseid, e.timestart, e.timemodified ";
            $sql .= "FROM {event} e INNER JOIN {wiziq} c ";
            $sql .= "WHERE e.instance=c.id AND e.modulename='wiziq'";
            $recordselect = $DB->get_records_sql($sql);
            if ($recordselect) {
                foreach ($recordselect as $record) {
                    $res = new stdClass();
                    $res->id = $record->instance;
                    $res->course = $record->courseid;
                    $res->timecreated = $record->timestart;
                    $res->timemodified = $record->timemodified;
                    $DB->update_record('wiziq', $res);
                }
            }
        }

        // Define index course (not unique) to be added to wiziq
        $table = new xmldb_table('wiziq');
        $index = new xmldb_index('courseindex', XMLDB_INDEX_NOTUNIQUE, array('course'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        //upgrade for wiziq_content
        $table = new xmldb_table('wiziq_content');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'wiziq_content_old');
        }
        $table_content_old = new xmldb_table('wiziq_content_old');
        $field = new xmldb_field('description');
        if ($dbman->field_exists($table_content_old, $field)) {
            $dbman->drop_field($table_content_old, $field);
        }
        $field = new xmldb_field('icon');
        if ($dbman->field_exists($table_content_old, $field)) {
            $dbman->drop_field($table_content_old, $field);
        }
        // no need to keep the record that are delete from the moodle.
        $field = new xmldb_field('isdeleted');
        if ($dbman->field_exists($table_content_old, $field)) {
            $DB->delete_records('wiziq_content_old', array('isdeleted' => '1'));
        }
        $field = new xmldb_field('isdeleted');
        if ($dbman->field_exists($table_content_old, $field)) {
            $dbman->drop_field($table_content_old, $field);
        }

        $table_content_new = new xmldb_table('wiziq_content');
        $table_content_new->add_field('id', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table_content_new->add_field('course', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, null, null, '1', 'id');
        $table_content_new->add_field('wiziqid', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, null, null, '0', 'course');
        $table_content_new->add_field('type', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'wiziqid');
        $table_content_new->add_field('name', XMLDB_TYPE_CHAR, '255',
                null, null, null, null, 'type');
        $table_content_new->add_field('title', XMLDB_TYPE_CHAR, '255',
                null, null, null, null, 'name');
        $table_content_new->add_field('parentid', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, null, null, null, 'title');
        $table_content_new->add_field('prevparentid', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, null, null, null, 'parentid');
        $table_content_new->add_field('path', XMLDB_TYPE_CHAR, '255', null,
                XMLDB_NOTNULL, null, null, 'prevparentid');
        $table_content_new->add_field('userid', XMLDB_TYPE_INTEGER, '10', null,
                null, null, null, 'path');
        $table_content_new->add_field('uploadtime', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, null, 'userid');
        $table_content_new->add_field('contentid', XMLDB_TYPE_INTEGER, '10', null,
                null, null, null, 'uploadtime');
        $table_content_new->add_field('old_content_id', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, null, null, null, 'contentid');
        $table_content_new->add_field('cid_change_status', XMLDB_TYPE_INTEGER, '1',
                XMLDB_UNSIGNED, null, null, null, 'old_content_id');
        $table_content_new->add_field('status', XMLDB_TYPE_INTEGER, '10', null,
                null, null, null, 'cid_change_status');
        $table_content_new->add_field('wcid', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, null, null, null, 'status');

        // Adding keys to table chat_messages_current
        $table_content_new->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table_content_new)) {
            $dbman->create_table($table_content_new);
        }
        $first_record = $DB->get_records('wiziq_content_old', array('id' => '1' ,
                                          'name' => 'My Content'),
                                          null, 'id');
        if (!$first_record) {
            $uploadtime = time();
            $wcid = '1'.$uploadtime;
            $res = new stdClass();
            $res->course = '0';
            $res->wiziqid = '0';
            $res->type = '1';
            $res->name = 'My Content';
            $res->title = '';
            $res->parentid = '0';
            $res->prevparentid = null;
            $res->path = '';
            $res->userid = '0';
            $res->uploadtime = $uploadtime;
            $res->contentid = '0';
            $res->old_content_id = null;
            $res->cid_change_status = '1';
            $res->status = '0';
            $res->wcid = $wcid;
            $DB->insert_record('wiziq_content', $res);

            $contents = $DB->get_records('wiziq_content_old');
            if ($contents) {
                foreach ($contents as $content) {
                    $res = new stdClass();
                    $res->course = '1';
                    $res->wiziqid = '0';
                    $res->type = $content->type;
                    $res->name = $content->name;
                    $res->title = $content->title;
                    $res->parentid = $content->parentid;
                    if ($res->parentid != 0 || $res->parentid != null) {
                        $parent_name_record = $DB->get_record_select('wiziq_content_old',
                                                "id=$res->parentid");
                        if ($parent_name_record !=null && $parent_name_record->id > 1) {
                            $parent_name = $parent_name_record->name;
                            $path = $parent_name;
                            $grandparent_record = $DB->get_record_select('wiziq_content_old',
                                                    "id = $parent_name_record->parentid");
                            if ($grandparent_record !=null && $grandparent_record->id >1) {
                                $grandparent_name = $grandparent_record->name;
                                $path = $grandparent_name.'/'.$path;
                                $last_parent_record = $DB->get_record_select('wiziq_content_old',
                                "id = $grandparent_record->parentid");
                                if ($last_parent_record !=null && $last_parent_record->id >1) {
                                    $last_parent_name = $last_parent_record->name;
                                    $path = $last_parent_name.'/'.$path;
                                }
                            }
                        } else {
                            $path = '';
                        }
                        if ($parent_name_record !=null && $parent_name_record->parentid != null) {
                            $res->prevparentid = $parent_name_record->parentid;
                        } else {
                            $res->prevparentid = '0';
                        }
                    }
                    $res->path = $path;
                    $res->userid = $content->userid;
                    $res->uploadtime = $content->uploaddatetime;
                    $res->contentid = $content->contentid;
                    $res->old_content_id = $content->contentid;
                    $res->cid_change_status = '2'; // initiall stage
                    $res->status = $content->status;
                    $res->wcid = "1".$content->uploadtime;
                    $DB->insert_record('wiziq_content', $res);
                }
            }
        }
        upgrade_mod_savepoint(true, 2007040100, 'wiziq');
    }
    $table = new xmldb_table('wiziqlive');
    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
    $table = new xmldb_table('wiziq_attendee_info');
    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
    $table = new xmldb_table('wiziq_content_old');
    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }

    if ($oldversion < 2013112900) {
        // Define field course to be added to wiziq
        $table = new xmldb_table('wiziq');

        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10',
                                XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '2', 'id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('insescod', XMLDB_TYPE_INTEGER, '10',
                                XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'wtype');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10',
                                   XMLDB_UNSIGNED, null, null, null, 'course');
            $dbman->change_field_type($table, $field);  //change field type
        } else {
            $field = new xmldb_field('insescod');
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10',
                                   XMLDB_UNSIGNED, null, null, null, 'course');
            $dbman->add_field($table, $field);
        }

        // Define field class_id to be added to wiziq
        $field = new xmldb_field('class_id', XMLDB_TYPE_INTEGER, '10',
                                XMLDB_UNSIGNED, null, null, null,
            'insescod');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field_recording_link_status = new xmldb_field('recording_link_status');
        $field = new xmldb_field('recording_link_staus');
        $field_rec_exist = $dbman->field_exists($table, $field_recording_link_status);
        $field_rec_status_exist = $dbman->field_exists($table, $field);
        if (!$field_rec_exist && !$field_rec_status_exist) {
            // Define field recording_link_status to be added to wiziq
            $field_recording_link_status = new xmldb_field('recording_link_status',
                                                XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                                null, null, '0', 'view_recording_link');
            if (!$dbman->field_exists($table, $field_recording_link_status)) {
                $dbman->add_field($table, $field_recording_link_status);
            }
        }
        $field = new xmldb_field('recording_link_staus', XMLDB_TYPE_INTEGER, '10',
                                 XMLDB_UNSIGNED, null, null, '0',
            'view_recording_link');
        if ($dbman->field_exists($table, $field)) {
            if (!$dbman->field_exists($table, $field_recording_link_status)) {
                $dbman->rename_field($table, $field, 'recording_link_status');
            }
        }
        
          // Define field class_master_id to be added to wiziq
        $field = new xmldb_field('class_master_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0',
            'recording_link_status');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index course (not unique) to be added to wiziq
        $table = new xmldb_table('wiziq');
        $index = new xmldb_index('courseindex', XMLDB_INDEX_NOTUNIQUE, array('course'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        //upgrade for wiziq_content
        $table = new xmldb_table('wiziq_content');
        if ($dbman->table_exists($table)) {
		
            $field = new xmldb_field('old_content_id', XMLDB_TYPE_INTEGER, '10',
                                      XMLDB_UNSIGNED, null, null, null, 'contentid');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $field = new xmldb_field('cid_change_status', XMLDB_TYPE_INTEGER, '1',
                                      XMLDB_UNSIGNED, null, null, null, 'old_content_id');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            
        }
        upgrade_mod_savepoint(true, 2013112900, 'wiziq');
    }


    return true;
}
