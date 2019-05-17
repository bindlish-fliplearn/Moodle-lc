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
 * Invite by E-mail form for upcoming class.
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");

$bcid = required_param('bcid', PARAM_INT);   // Virtual Class ID.

$groupids = optional_param_array('groups', null, PARAM_RAW);


$PAGE->set_url('/mod/braincert/inviteusergroup.php', array('bcid' => $bcid, 'sesskey' => sesskey()));

$action = new moodle_url('/mod/braincert/inviteusergroup.php', ['bcid' => $bcid, 'sesskey' => sesskey()]);

$braincertrec = $DB->get_record('braincert', array('class_id' => $bcid));
if (!$course = $DB->get_record('course', array('id' => $braincertrec->course))) {
    print_error('invalidcourseid');
}

require_login($course);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('pluginname', 'braincert'));
$inviteusergroup = get_string('inviteusergroup', 'braincert');
$PAGE->navbar->add($inviteusergroup);

$PAGE->requires->css('/mod/braincert/css/styles.css', true);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('inviteuserofselectedgroup', 'braincert'));

global $DB, $CFG, $USER;

$getgroups = $DB->get_records('groups', array('courseid' => $braincertrec->course));



if ($groupids) {
    require_sesskey();
    $getbody = $DB->get_record('braincert_manage_template', array('bcid' => $bcid));
    list($insql, $params) = $DB->get_in_or_equal($groupids, SQL_PARAMS_NAMED, 'groupid', false);
    $sql = "SELECT * FROM {groups_members} WHERE groupid {$insql}";
    $groupusers = $DB->get_records_sql($sql, $params);
    $module = $DB->get_record('modules', array('name' => 'braincert'));
    $cm = $DB->get_record('course_modules', array('instance' => $braincertrec->id,
                          'course' => $COURSE->id, 'module' => $module->id));
    $starttime = new DateTime($braincertrec->start_time);
    $endtime = new DateTime($braincertrec->end_time);
    $interval = $starttime->diff($endtime);
    $durationinmin = ($interval->h * 60) + $interval->i;
    $find = array('{owner_name}', '{class_name}', '{class_date_time}', '{class_time_zone}', '{class_duration}', '{class_join_url}');
    $replace = array($USER->firstname.' '.$USER->lastname, $braincertrec->name,
                     date('d-m-Y', $braincertrec->start_date), $braincertrec->default_timezone,
                     $durationinmin, $CFG->wwwroot.'/mod/braincert/view.php?id='.$cm->id);
    $getbody->emailmessage = str_replace($find, $replace, $getbody->emailmessage);
    foreach ($groupusers as $groupuserskey => $groupusersval) {
        if ($emailuserrec = $DB->get_record('user', array('id' => $groupusersval->userid))) {
            $emailuser = $emailuserrec;
            $mailresults = email_to_user($emailuser, $USER, $getbody->emailsubject, $getbody->emailmessage, $getbody->emailmessage);
            if ($mailresults == 1) {
                echo get_string('emailsent', 'braincert');
            } else {
                echo get_string('emailnotsent', 'braincert')." ".$emailuserrec->email."<br>";
            }
        }
    }
}

if (!empty($getgroups)) {
?>
    <form action="<?php echo $action; ?>" method="post">
      <ul>
        <?php
        foreach ($getgroups as $getgroupskey => $getgroupsval) {
            echo "<li><input type='checkbox' name='groups[]' value='".$getgroupsval->id."' >".$getgroupsval->name."</li>";
        }
        ?>
      <div class="submitdiv">
        <input type="submit" name="submit" value="Send Email">
      </div>
      </ul>
    </form>
<?php
} else {
    echo '<div class="alert alert-danger">'.get_string('nogroups', 'braincert').'</div>';
}
echo $OUTPUT->footer();