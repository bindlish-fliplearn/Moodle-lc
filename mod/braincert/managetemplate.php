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
 * Manage e-mail template.
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/braincert/classes/managetemplate_form.php');

GLOBAL $USER;

$bcid = required_param('bcid', PARAM_INT);   // Virtual Class ID.

$PAGE->set_url('/mod/braincert/managetemplate.php', array('bcid' => $bcid));

$braincertrec = $DB->get_record('braincert', array('class_id' => $bcid));
if (!$course = $DB->get_record('course', array('id' => $braincertrec->course))) {
    print_error('invalidcourseid');
}

require_login($course);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('pluginname', 'braincert'));
$managetemplate = get_string('managetemplate', 'braincert');
$PAGE->navbar->add($managetemplate);

$PAGE->requires->css('/mod/braincert/css/styles.css', true);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managetemplate', 'braincert'));

$mform = new managetemplate_form($CFG->wwwroot.'/mod/braincert/managetemplate.php?bcid='.$bcid);

if ($manageemailtemplate = $mform->get_data()) {

    $emailmessage = $manageemailtemplate->emailmessage['text'];
    $emailsubject = $manageemailtemplate->emailsubject;
    $checkrecord = $DB->get_record('braincert_manage_template', array('bcid' => $bcid));
    if ($checkrecord) {
        $record = new stdClass();
        $record->id = $checkrecord->id;
        $record->bcid = $bcid;
        $record->emailsubject = $emailsubject;
        $record->emailmessage = $emailmessage;
        $DB->update_record('braincert_manage_template', $record);
    } else {
        $record = new stdClass();
        $record->bcid = $bcid;
        $record->emailsubject = $emailsubject;
        $record->emailmessage = $emailmessage;
        $DB->insert_record('braincert_manage_template', $record);
    }

    $mform->display();
} else {
    $mform->display();
}

echo $OUTPUT->footer();