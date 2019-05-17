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
 * Payments.
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->libdir.'/formslib.php');
require_once('locallib.php');

$bcid = required_param('bcid', PARAM_INT);  // Virtual Class ID.

$PAGE->set_url('/mod/braincert/payments.php', array('bcid' => $bcid));

$braincertrec = $DB->get_record('braincert', array('class_id' => $bcid));
if (!$course = $DB->get_record('course', array('id' => $braincertrec->course))) {
    print_error('invalidcourseid');
}

require_login($course);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('pluginname', 'braincert'));
$payments = get_string('payments', 'braincert');
$PAGE->navbar->add($payments);

echo $OUTPUT->header();
echo $OUTPUT->heading($payments);

global $DB;

$getpayments = $DB->get_records('virtualclassroom_purchase', array('class_id' => $bcid));
if ($getpayments) {

    $table = new html_table();
    $table->head = array ();
    $table->head[] = 'Payment id';
    $table->head[] = 'Class id';
    $table->head[] = 'Amount';
    $table->head[] = 'Payer Name';
    $table->head[] = 'Payment mode';
    $table->head[] = 'Payment Date';

    foreach ($getpayments as $getpaymentskey => $getpaymentsval) {
        $row = array ();
        $row[] = $getpaymentsval->id;
        $row[] = $getpaymentsval->class_id;
        $row[] = $getpaymentsval->mc_gross;
        $row[] = $DB->get_record('user',
                 array('id' => $getpaymentsval->payer_id))->firstname. " ".
                 $DB->get_record('user', array('id' => $getpaymentsval->payer_id))->lastname;
        $row[] = $getpaymentsval->payment_mode;
        $row[] = $getpaymentsval->date_purchased;
        $table->data[] = $row;
    }

    if (!empty($table)) {
        echo html_writer::start_tag('div', array('class' => 'no-overflow display-table'));
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
    }

} else {
    echo "<div class='alert alert-warning'>
		        <strong>".get_string('nopayment', 'braincert')."</strong>
		      </div>";

}

echo $OUTPUT->footer();