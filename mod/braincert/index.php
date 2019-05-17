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
 * The braincert activity
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("lib.php");
require_once('locallib.php');

$id   = required_param('id', PARAM_INT);   // Course ID.
$bcid = optional_param('bcid', 0, PARAM_INT); // Virtual Class ID.
$all = optional_param('all', 1, PARAM_INT); // Cancel class details.

$PAGE->set_url('/mod/braincert/index.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_login($course);
$PAGE->set_pagelayout('incourse');
$pluginname = get_string('pluginname', 'braincert');
$PAGE->navbar->add($pluginname);
$PAGE->set_heading($course->fullname);

$PAGE->requires->css('/mod/braincert/css/styles.css', true);
if ($CFG->version < 2017051500) {
?>
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<?php
}
$PAGE->requires->js('/mod/braincert/js/jquery.min.js', true);
$PAGE->requires->js('/mod/braincert/js/classsettings.js', true);
?>

<?php

if ($bcid > 0) {
    $getremovestatus = braincert_cancel_class($bcid, $all);
    if ($getremovestatus['status'] == "ok") {
        echo "Class Removed Successfully.";
        redirect(new moodle_url('/mod/braincert/index.php?id='.$id));
    } else {
        echo $getremovestatus['error'];
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('braincert_class', 'braincert'));

$contextid = context_course::instance($id);
$roles = get_user_roles($contextid, $USER->id);
$admins = get_admins();
$isadmin = false;
foreach ($admins as $admin) {
    if ($USER->id == $admin->id) {
        $isadmin = true;
        break;
    }
}
$isteacher = 0;
if ($isadmin) {
    $isteacher = 1;
} else {
    foreach ($roles as $role) {
        if (($role->shortname == 'editingteacher') || ($role->shortname == 'teacher')) {
            $isteacher = 1;
            break;
        }
    }
}

$braincertclasses = $DB->get_records('braincert');
$allclassid = array();
foreach ($braincertclasses as $class) {
    array_push($allclassid, $class->class_id);
}

if ($CFG->version >= 2016120500) {
    $courseclasses = $DB->get_records_sql("SELECT bct.* FROM {braincert} bct,
    {course_modules} cm, {modules} m WHERE m.name = ? AND cm.module = m.id
    AND cm.deletioninprogress = ? AND cm.visible = ? AND cm.course = ?
    AND bct.id = cm.instance", array('braincert', 0, 1, $id));
} else {
    $courseclasses = $DB->get_records_sql("SELECT bct.* FROM {braincert} bct,
    {course_modules} cm, {modules} m WHERE m.name = ? AND cm.module = m.id
    AND cm.visible = ? AND cm.course = ?
    AND bct.id = cm.instance", array('braincert', 1, $id));
}
$thiscourseclassid = array();
foreach ($courseclasses as $class) {
    array_push($thiscourseclassid, $class->class_id);
}

$getclasslists = braincert_get_class_list();
foreach ($thiscourseclassid as $getclasslist) {
    $getclasslist = braincert_get_class($getclasslist);
    if ($getclasslist) {
        $module = $DB->get_record('modules', array('name' => 'braincert'));
        $braincertrec = $DB->get_record('braincert', array('class_id' => $getclasslist['id'], 'course' => $id));
        $cm = $DB->get_record('course_modules', array('instance' => $braincertrec->id, 'course' => $id, 'module' => $module->id));
        if ($getclasslist["ispaid"] == 1 && !$isteacher) {
            $getuserpaymentdetails = $DB->get_record('virtualclassroom_purchase',
                                     array('class_id' => $braincertrec->class_id, 'payer_id' => $USER->id));
        } else {
            $getuserpaymentdetails = false;
        }
        $duration = $getclasslist['duration'] / 60;
        if ($getclasslist['status'] == 'Past') {
            $class = "bc-alert bc-alert-danger";
        } else if ($getclasslist['status'] == 'Live') {
            $class = "bc-alert bc-alert-success";
        } else if ($getclasslist['status'] == 'Upcoming') {
            $class = "bc-alert bc-alert-warning";
        }
        ?>
        <div class="row">
          <div class="class_list">
            <?php if ($isteacher == 1) { ?>
                <div class="span6 drop_fr_icon">
                    <div class="dropdown">
                        <a class="dropbtn" id="dropbtn" href="javascript:void(0);"
                          onclick="dropdownmenu(<?php echo $getclasslist['id']; ?>)">
                            <i class="fa fa-cog" aria-hidden="true"></i><b class="caret"></b>
                        </a>
                        <div id="dropdown-<?php echo $getclasslist['id']; ?>" class="dropdown-content">
                            <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/attendance_report.php?bcid=
<?php echo $getclasslist['id']; ?>">
<i class="fa fa-users" aria-hidden="true"></i> <?php echo get_string('attendancereport', 'braincert'); ?>
                            </a>
                            <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/index.php?id=
<?php echo $id; ?>&bcid=<?php echo $getclasslist['id']; ?>"
onclick="return confirm('<?php echo get_string("areyousure", "braincert"); ?>')">
<i class="fa fa-minus-circle" aria-hidden="true"></i> <?php echo get_string('cancelclass', 'braincert'); ?>
                            </a>
                            <?php if ($braincertrec->is_recurring == 1) { ?>
<a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/index.php?id=<?php echo $id; ?>
&all=2&bcid=<?php echo $getclasslist['id']; ?>" onclick="return confirm('<?php echo get_string("areyousureall", "braincert"); ?>')">
<i class="fa fa-minus-circle" aria-hidden="true"></i> <?php echo get_string('cancelclassall', 'braincert'); ?>
                                </a>
                            <?php } ?>
                            <hr>
<a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/inviteemail.php?bcid=<?php echo $getclasslist['id']; ?>">
<i class="fa fa-envelope" aria-hidden="true"></i> <?php echo get_string('inviteemail', 'braincert'); ?>
                            </a>
                            <?php if ($cm->groupmode != 0) { ?>
<a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/inviteusergroup.php?bcid=<?php echo $getclasslist['id']; ?>">
<i class="fa fa-envelope" aria-hidden="true"></i> <?php echo get_string('inviteusergroup', 'braincert'); ?>
                                </a>
                            <?php } ?>
                            <?php if ($getclasslist['ispaid'] == 1) { ?>
<a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/addpricingscheme.php?bcid=<?php echo $getclasslist['id']; ?>">
<i class="fa fa-shopping-cart" aria-hidden="true"></i> <?php echo get_string('shoppingcart', 'braincert'); ?>
                                </a>
<a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/adddiscount.php?bcid=<?php echo $getclasslist['id']; ?>">
<i class="fa fa-ticket" aria-hidden="true"></i> <?php echo get_string('discounts', 'braincert'); ?>
                                </a>
<a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/payments.php?bcid=<?php echo $getclasslist['id']; ?>">
<i class="fa fa-cc-paypal" aria-hidden="true"></i> <?php echo get_string('payments', 'braincert'); ?>
                                </a>
                            <?php } ?>
                            <hr>
<a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/recording.php?action=viewrecording&bcid=<?php echo $getclasslist['id']; ?>">
<i class="fa fa-play-circle-o" aria-hidden="true"></i> <?php echo get_string('viewclassrecording', 'braincert'); ?>
                            </a>
<a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/recording.php?action=managerecording&bcid=<?php echo $getclasslist['id']; ?>">
<i class="fa fa-play-circle-o" aria-hidden="true"></i> <?php echo get_string('managerecording', 'braincert'); ?>
                            </a>
<a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/managetemplate.php?bcid=<?php echo $getclasslist['id']; ?>">
<i class="fa fa-envelope" aria-hidden="true"></i> <?php echo get_string('managetemplate', 'braincert'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php } ?>
       
          <div class="class_div cl_list span6">
              <h4>
                 <i class="fa fa-bullhorn" aria-hidden="true"></i>
                 <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/view.php?id=<?php echo $cm->id; ?>">
                     <strong class="class-heading"><?php echo $braincertrec->name; ?></strong>
                 </a>
                 <span class="<?php echo $class; ?>">
                        <?php
                        if ($getclasslist['isCancel'] == 0) {
                            echo $getclasslist['status'];
                        }
                        if ($getclasslist['isCancel'] == 2) {
                            echo get_string('canceled', 'braincert');
                        }
                        if ($getclasslist['isCancel'] == 1) {
                            if (!isset($getclasslist['class_next_date'])) {
                                echo get_string('canceled', 'braincert');
                            } else {
                                date_default_timezone_set($getclasslist['timezone_country']);
                                $date1 = date_create(date('Y-m-d', time()));
                                $date2 = date_create($getclasslist['canceled_date']);
                                $diff = date_diff($date1, $date2);
                                if ($diff->d > 0) {
                                    echo $getclasslist['status'];
                                } else {
                                    echo get_string('canceled', 'braincert');
                                }
                            }
                        }
                        ?>
                 </span>
              </h4> 
              <div class="course_info">
                  <p>
                  <i class="fa fa-calendar" aria-hidden="true"></i>
                    <?php
                    date_default_timezone_set($getclasslist['timezone_country']);
                    echo $getclasslist['date'];
                    ?>
                  </p>
                  <p>
                  <i class="fa fa-clock-o" aria-hidden="true"></i>
                    <?php echo $getclasslist['start_time']." - ".$getclasslist['end_time']." (".$duration." Minutes)"; ?>
                  </p>
                  <p>
                  <i class="fa fa-globe" aria-hidden="true"></i>
                    <?php echo "Time Zone: ".$getclasslist['timezone_label']; ?>
                  </p>
              </div>
                <?php
                if (($getclasslist['ispaid'] == 1) &&
                    ($getclasslist['status'] != 'Past') &&
                    ($isteacher == 0) && !$getuserpaymentdetails) {
                    $getbraincertgroup = $DB->get_records('groupings_groups', array('groupingid' => $cm->groupingid));
                    if ($getbraincertgroup) {
                        foreach ($getbraincertgroup as $getbraincertgroupkey => $getbraincertgroupval) {
                            $getgroupmembers = $DB->get_records('groups_members', array('groupid' => $getbraincertgroupval->groupid,
                            'userid' => $USER->id));
                            if ($getgroupmembers) {
                ?>
                                <a target="_blank" class="btn btn-primary" id="buy-btn"
                                href="<?php echo $CFG->wwwroot."/mod/braincert/view.php?id=".$cm->id; ?>" return false;>
                                    <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                                    <?php echo get_string('buy', 'braincert'); ?>
                                </a>
                            <?php
                            }
                        }
                    } else {
                ?>
                        <a target="_blank" class="btn btn-primary" id="buy-btn"
                        href="<?php echo $CFG->wwwroot."/mod/braincert/view.php?id=".$cm->id; ?>" return false;>
                        <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                        <?php echo get_string('buy', 'braincert'); ?></a>
                <?php
                    }
                }
                if ($getclasslist['status'] == 'Live') {
                    $braincertclass = $DB->get_record('braincert', array('course' => $id, 'class_id' => $getclasslist['id']));
                    if ($getclasslist['ispaid'] == 0) {
                        if (!empty($braincertclass)) {
                            $item = array();
                            $item['userid']    = $USER->id;
                            $item['username']  = $USER->firstname;
                            $item['classname'] = $braincertclass->name;
                            $item['isteacher'] = $isteacher;
                            $item['classid']   = $braincertclass->class_id;
                            $getlaunchurl = braincert_get_launch_url($item);
                            if ($getlaunchurl['status'] == "ok") {
                                $launchurl = $getlaunchurl['launchurl'];
                                if ($isadmin || $isteacher) {
                ?>
                                  <a target="_blank" class="btn btn-primary" id="launch-btn"
                                  href="<?php echo $launchurl ?>" return false;><?php echo get_string('launch', 'braincert'); ?></a>
                <?php
                                } else {
                                    if ($cm->groupmode != 1) { ?>
                                      <a target="_blank" class="btn btn-primary" id="launch-btn"
                                      href="<?php echo $launchurl ?>" return false;>
                                        <?php echo get_string('launch', 'braincert'); ?></a>
                <?php
                                    } else {
                                        $getbraincertgroup = $DB->get_records('groupings_groups',
                                        array('groupingid' => $cm->groupingid));
                                        foreach ($getbraincertgroup as $getbraincertgroupkey => $getbraincertgroupval) {
                                            $getgroupmembers = $DB->get_records('groups_members',
                                            array('groupid' => $getbraincertgroupval->groupid, 'userid' => $USER->id));
                                            if ($getgroupmembers) {
                ?>              
                                              <a target="_blank" class="btn btn-primary" id="launch-btn"
                                              href="<?php echo $launchurl ?>" return false;>
                                                <?php echo get_string('launch', 'braincert'); ?></a>
                <?php
                                            }
                                        }
                                    }
                                }
                            } else if ($getlaunchurl['status'] == "error") {
                                echo "<strong>".$getlaunchurl["error"]."</strong>";
                            }
                        }
                    } else if ($getclasslist['ispaid'] == 1) {
                        if (!empty($braincertclass)) {
                            $item = array();
                            $item['userid']    = $USER->id;
                            $item['username']  = $USER->firstname;
                            $item['classname'] = $braincertclass->name;
                            $item['isteacher'] = $isteacher;
                            $item['classid']   = $braincertclass->class_id;
                            $getlaunchurl = braincert_get_launch_url($item);
                            if ($getlaunchurl['status'] == "ok") {
                                $launchurl = $getlaunchurl['launchurl'];
                                if ($isadmin || $isteacher) { ?>
                                  <a target="_blank" class="btn btn-primary" id="launch-btn"
                                  href="<?php echo $launchurl ?>" return false;><?php echo get_string('launch', 'braincert'); ?></a>
                        <?php
                                } else {
                                    if ($getuserpaymentdetails) {
                                        if ($cm->groupmode != 1) { ?>
                                          <a target="_blank" class="btn btn-primary" id="launch-btn"
                                          href="<?php echo $launchurl ?>" return false;>
                                            <?php echo get_string('launch', 'braincert'); ?></a>
                                <?php
                                        } else {
                                                $getbraincertgroup = $DB->get_records('groupings_groups',
                                                array('groupingid' => $cm->groupingid));
                                            foreach ($getbraincertgroup as $getbraincertgroupkey => $getbraincertgroupval) {
                                                $getgroupmembers = $DB->get_records('groups_members',
                                                array('groupid' => $getbraincertgroupval->groupid, 'userid' => $USER->id));
                                                if ($getgroupmembers) { ?>
                                                    <a target="_blank" class="btn btn-primary" id="launch-btn"
                                                    href="<?php echo $launchurl ?>" return false;>
                                                        <?php echo get_string('launch', 'braincert'); ?></a>
                                            <?php
                                                }
                                            }
                                        }
                                    }
                                }
                            } else if ($getlaunchurl['status'] == "error") {
                                echo "<strong>".$getlaunchurl["error"]."</strong>";
                            }
                        }
                    }
                } else if ($isteacher && $getclasslist['status'] != 'Past') {
                    $braincertclass = $DB->get_record('braincert', array('course' => $id, 'class_id' => $getclasslist['id']));
                    if (!empty($braincertclass)) {
                        $data['task']       = 'getclasslaunch';
                        $data['userId']     = $USER->id;
                        $data['userName']   = $USER->firstname;
                        $data['lessonName'] = preg_replace('/\s+/', '', $braincertclass->name);
                        $data['courseName'] = preg_replace('/\s+/', '', $braincertclass->name);
                        $data['isTeacher']  = $isteacher;
                        $data['class_id']   = $braincertclass->class_id;
                        $getlaunchurl = braincert_get_curl_info($data);
                        if ($getlaunchurl['status'] == "ok") {
                            $launchurl = $getlaunchurl['launchurl'];
                            if ($isadmin || $isteacher) { ?>
                                <a target="_blank" class="btn btn-primary" id="launch-btn"
                                href="<?php echo $launchurl ?>" return false;><?php echo get_string('launch', 'braincert'); ?></a>
                        <?php
                            } else {
                                if ($cm->groupmode != 1) { ?>
                                    <a target="_blank" class="btn btn-primary" id="launch-btn"
                                    href="<?php echo $launchurl ?>" return false;>
                                        <?php echo get_string('launch', 'braincert'); ?></a>
                            <?php
                                } else {
                                    $getbraincertgroup = $DB->get_records('groupings_groups',
                                    array('groupingid' => $cm->groupingid));
                                    foreach ($getbraincertgroup as $getbraincertgroupkey => $getbraincertgroupval) {
                                        $getgroupmembers = $DB->get_records('groups_members',
                                        array('groupid' => $getbraincertgroupval->groupid, 'userid' => $USER->id));
                                        if ($getgroupmembers) { ?>
                                            <a target="_blank" class="btn btn-primary" id="launch-btn"
                                            href="<?php echo $launchurl ?>" return false;>
                                                <?php echo get_string('launch', 'braincert'); ?></a>
                                    <?php
                                        }
                                    }
                                }
                            }
                        } else if ($getlaunchurl['status'] == "error") {
                            echo "<strong>".$getlaunchurl["error"]."</strong>";
                        }
                    }
                }
                ?>
          </div>
        </div>
    </div>
<?php
    }
}

echo $OUTPUT->footer();
