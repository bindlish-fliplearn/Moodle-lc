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
 * Add the discount for paid class.
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->libdir.'/formslib.php');
require_once('locallib.php');
require_once($CFG->dirroot.'/mod/braincert/classes/adddiscount_form.php');

$bcid    = required_param('bcid', PARAM_INT);   // Virtual Class ID.
$did     = optional_param('did', 0, PARAM_INT); // Discount ID.
$action  = optional_param('action', 'edit', PARAM_TEXT);

$PAGE->set_url('/mod/braincert/adddiscount.php', array('bcid' => $bcid));

$braincertrec = $DB->get_record('braincert', array('class_id' => $bcid));
if (!$course = $DB->get_record('course', array('id' => $braincertrec->course))) {
    print_error('invalidcourseid');
}

require_login($course);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('pluginname', 'braincert'));
$adddiscount = get_string('adddiscount', 'braincert');
$PAGE->navbar->add($adddiscount);

if ($action == 'delete') {
    $data['task']      = 'removediscount';
    $data['discountid'] = $did;
    $removediscount = braincert_get_curl_info($data);
    if ($removediscount['status'] == "ok") {
        echo get_string('removedsuccess', 'braincert');
        redirect(new moodle_url('/mod/braincert/adddiscount.php?bcid='.$bcid));
    } else {
        echo $removediscount['error'];
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addclassdiscount', 'braincert'));

$dislistdata['task']     = 'listdiscount';
$dislistdata['class_id'] = $bcid;
$discountlists = braincert_get_curl_info($dislistdata);


$mform = new adddiscount_form($CFG->wwwroot.'/mod/braincert/adddiscount.php?bcid='.$bcid);

if ($classdiscount = $mform->get_data()) {

    $discountstartdate = date('Y-m-d', $classdiscount->start_date);
    $classdiscountstartdate = new DateTime($discountstartdate, new DateTimeZone($braincertrec->default_timezone));
    $discountstartdatetimestamp = $classdiscountstartdate->getTimestamp();

    $discountenddate = date('Y-m-d', $classdiscount->end_date);
    $classdiscountenddate = new DateTime($discountenddate, new DateTimeZone($braincertrec->default_timezone));
    $discountenddatetimestamp = $classdiscountenddate->getTimestamp();

    $data['task']           = 'addSpecials';
    $data['class_id']  = $bcid;
    $data['discount']       = $classdiscount->discount;
    $data['start_date']     = date('Y-m-d', $discountstartdatetimestamp);
    $data['discount_type']  = $classdiscount->discount_type;
    if ($classdiscount->is_never_expire == 0) {
        $data['end_date']   = date('Y-m-d', $discountenddatetimestamp);
    }
    if ($classdiscount->is_use_discount_code == 1) {
        $data['discount_code']  = $classdiscount->discount_code;
        $data['discount_limit'] = $classdiscount->discount_limit;
    }
    if ($classdiscount->did > 0) {
        $data['discountid']     = $classdiscount->did;
    }

    $getdiscount = braincert_get_curl_info($data);
    if ($getdiscount['status'] == "ok") {
        if ($getdiscount['method'] == "updateDiscount") {
            echo get_string('discountupdated', 'braincert');
        } else if ($getdiscount['method'] == "addDiscount") {
            echo get_string('discountadded', 'braincert');
        }
    } else {
        echo $getdiscount['error'];
    }
    $mform->display();
} else {
    $mform->display();
}

$dislistdata['task']     = 'listdiscount';
$dislistdata['class_id'] = $bcid;
$discountlists = braincert_get_curl_info($dislistdata);

$table = new html_table();
$table->head = array ();
$table->head[] = get_string('discountid', 'braincert');
$table->head[] = get_string('amountofdiscount', 'braincert');
$table->head[] = get_string('discountcode', 'braincert');
$table->head[] = get_string('discounttype', 'braincert');
$table->head[] = get_string('start_date', 'braincert');
$table->head[] = get_string('end_date', 'braincert');
$table->head[] = get_string('actions', 'braincert');

if (!empty($discountlists)) {
    if (isset($discountlists['Discount'])) {
        echo $discountlists['Discount'];
    } else if (isset($discountlists['status']) && ($discountlists['status'] == 'error')) {
        echo $discountlists['error'];
    } else {
        foreach ($discountlists as $discountlist) {
            $row = array ();
            $row[] = $discountlist['id'];
            $row[] = $discountlist['special_price'];
            $row[] = $discountlist['discount_code'];
            if ($discountlist['discount_type'] == "fixed_amount") {
                $row[] = get_string('fixedamount', 'braincert');
            } else {
                $row[] = get_string('percentage', 'braincert');
            }
            $row[] = date("F d, Y", strtotime($discountlist['start_date']));
            if (strtotime($discountlist['end_date']) > 0) {
                $row[] = date("F d, Y", strtotime($discountlist['end_date']));
            } else {
                $row[] = get_string('unlimited', 'braincert');
            }
            $row[] = '<a href="'.$CFG->wwwroot.'/mod/braincert/adddiscount.php?action=edit&bcid='
                     .$bcid.'&did='.$discountlist['id'].'" value="edit-'.$discountlist['id'].'">Edit</a>'
                     .' '.'<a href="'.$CFG->wwwroot.'/mod/braincert/adddiscount.php?action=delete&bcid='
                     .$bcid.'&did='.$discountlist['id'].'" value="delete-'.$discountlist['id'].'">Delete</a>';
            $table->data[] = $row;
        }
    }
}


if (!empty($table)) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow display-table'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();