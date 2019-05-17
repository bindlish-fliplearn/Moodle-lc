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
 * This page is the entry page into the online class
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('lib.php');
require_once('locallib.php');

GLOBAL $DB, $CFG, $USER;

$id = required_param('id', PARAM_INT);    // Course Module ID.
$braincertid = optional_param('bcid', 0, PARAM_INT);  // Braincert ID.
$all = optional_param('all', 1, PARAM_INT); // Cancel class details.
$bcid = optional_param('bcid', 0, PARAM_INT); // Virtual Class ID.
$task = optional_param('task', '', PARAM_RAW);
$classid = optional_param('class_id', '', PARAM_RAW);
$amount = optional_param('amount', '', PARAM_RAW);
$paymentmode = optional_param('payment_mode', '', PARAM_RAW);


if ($id) {
    if (!$cm = get_coursemodule_from_id('braincert', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }
    if (!$braincert = $DB->get_record("braincert", array("id" => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
} else {
    if (!$braincert = $DB->get_record("braincert", array("id" => $braincertid))) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id" => $braincert->course)) ) {
        print_error('coursemisconf');
    }
    if (!$cm = get_coursemodule_from_instance("braincert", $braincert->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/braincert/view.php', array('id' => $cm->id, 'bcid' => $braincertid));
$url = $CFG->wwwroot.'/mod/braincert/view.php?id='.$cm->id;
$baseurl = $CFG->mod_braincert_baseurl;
$PAGE->set_title(format_string($braincert->name));
$pagetitle = get_string('braincert_class', 'braincert');
$pagetitlename = $pagetitle . " " . $braincert->name;
$PAGE->set_heading(format_string($pagetitlename));
$PAGE->set_context($context);

$PAGE->requires->css('/mod/braincert/css/styles.css', true);
if ($CFG->version < 2017051500) {
    $PAGE->requires->css('https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', true);
}

$PAGE->requires->js('/mod/braincert/js/jquery.min.js', true);
$PAGE->requires->js('/mod/braincert/js/classsettings.js', true);
$PAGE->requires->js('/mod/braincert/js/video.js', true);
if ($bcid > 0) {
    $getremovestatus = braincert_cancel_class($bcid, $all);
    if ($getremovestatus['status'] == "ok") {
        echo get_string('braincert_class_removed', 'braincert');
        redirect(new moodle_url('/mod/braincert/view.php?id='.$id));
    } else {
        echo $getremovestatus['error'];
    }
}

if ($task == "returnpayment") {
    $record = new stdClass();
    $record->class_id = $classid;
    $record->mc_gross = $amount;
    $record->payer_id = $USER->id;
    $record->payment_mode = $paymentmode;
    $record->date_purchased = date('Y-m-d H:i:s', time());
    $insert = $DB->insert_record('virtualclassroom_purchase', $record);
    redirect($url);
}



echo $OUTPUT->header();

$braincertclass = $DB->get_record('braincert', array('id' => $cm->instance));

$contextid = context_course::instance($braincertclass->course);
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

$getplan = braincert_get_plan();
$paymentinfo = braincert_get_payment_info();
$getclassdetail = braincert_get_class($braincertclass->class_id);
$pricelist = braincert_get_price_list($braincertclass->class_id);

if ($getclassdetail["ispaid"] == 1 && !$isteacher) {
    $getuserpaymentdetails = $DB->get_record('virtualclassroom_purchase',
    array('class_id' => $braincertclass->class_id, 'payer_id' => $USER->id));
} else {
    $getuserpaymentdetails = false;
}




if (!empty($braincertclass)) {
    $currencysymbol = '';
    $currencycode = '';
    $duration = $getclassdetail['duration'] / 60;

    if ($getclassdetail['status'] == 'Past') {
        $class = "bc-alert bc-alert-danger";
    } else if ($getclassdetail['status'] == 'Live') {
        $class = "bc-alert bc-alert-success";
    } else if ($getclassdetail['status'] == 'Upcoming') {
        $class = "bc-alert bc-alert-warning";
    }

    if (strtoupper($getclassdetail['currency']) == "GBP") {
        $currencysymbol = "£";
    } else if (strtoupper($getclassdetail['currency']) == "CAD") {
        $currencysymbol = "$";
    } else if (strtoupper($getclassdetail['currency']) == "AUD") {
        $currencysymbol = "$";
    } else if (strtoupper($getclassdetail['currency']) == "EUR") {
        $currencysymbol = "€";
    } else if (strtoupper($getclassdetail['currency']) == "INR") {
        $currencysymbol = "₹";
    } else {
        $currencysymbol = "$";
    }

    $currencycode = strtoupper($getclassdetail['currency']);
    if ($getclassdetail["ispaid"] == 1) {
        if (isset($pricelist['Price']) && $pricelist['Price'] == "No Price in this Class" && $isteacher) {
    ?>
    <div id="modal-content-buying" class="modal pricedescription">
        <div class="modal-content" style="overflow: hidden;">
            <span><b><?php echo $pricelist['Price']; ?></b></span>
            <span class="close">&times;</span>
        </div>
    </div>
    <?php
        } else {
        ?>
            <div id="modal-content-buying" class="modal pricedescription initialhide">
            <div class="modal-content" style="overflow: hidden;">
                <span><b><?php echo get_string('buyingoption', 'braincert'); ?></b></span>
                <span class="close">&times;</span>
                <div class="card_error" style="display: none;
                color: #a94442;background-color: #f2dede;
                border-color: #ebccd1;border-radius: 5px;
                margin-bottom: 10px;padding: 8px;">
                </div>
                <table class="table table-bordered" id="cartcontainer">
                    <thead class="alert alert-info">
                        <tr class="success">
                            <th style="width: 40px;">#</th>
                            <th><?php echo get_string('price', 'braincert'); ?> ($)</th>
                            <th><?php echo get_string('duration', 'braincert'); ?></th>
                            <th><?php echo get_string('accesstype', 'braincert'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $xx = 0;
                        if (!isset($pricelist['Price'])) {
                            foreach ($pricelist as $i => $value) {
                                $price = $value['scheme_price'];
                                $optionid = $value['id'];
                                $subprice = $price;
                                $subpricebeforecoupondiscount = $price;
                                $chkprice = '<span id="displayprice'.$xx.'">'.
                                $currencysymbol.' '.number_format($price, 2).'</span>';
                                $duration = ($value['lifetime'] == '1') ? "Unlimited" : $value['scheme_days']
                                .($value['scheme_days'] > 1 ? " days" : " day");
                                $dur = ($value['lifetime'] == '1') ? 9999 : $value['scheme_days'];
                                $times = ($value['times'] == 0) ? "Unlimited" : $value['numbertimes']
                                .($value['numbertimes'] > 1 ? " times" : " time");
                                $tms = ($value['times'] == 0) ? -1 : $value['numbertimes'];
                        ?>      
                                <tr class="warning">
                                    <td>
                                    <input type="hidden" id="subpricebeforecoupondiscount<?php echo $xx;?>"
                                    value="<?php echo $subpricebeforecoupondiscount; ?>" />
                                    <input type="hidden" id="originalprice<?php echo $xx;?>" value="<?php echo  $price; ?>" />
                                    <input type="radio" name="pricescheme" id="pricescheme<?php echo $xx;?>"
                                    value="<?php echo $subprice; ?>" duration="<?php echo $dur; ?>"
                                    times="<?php echo $tms; ?>" option_id="<?php echo $optionid; ?>"/></td>
                                    <td><?php echo $chkprice; ?></td>
                                    <td><?php echo $duration; ?></td>
                                    <td><?php echo $times; ?></td>
                                </tr>
                                <?php
                                if ($xx == 0) {
                                ?> 
                                    <script>
                                    jQuery(document).ready(function () {
                                        jQuery("#pricescheme<?php echo $xx;?>").trigger("click");
                                    });
                                    </script>
                                <?php
                                }
                                $xx++;
                            }
                        }
                        ?>
                    </tbody>
                </table>
                <div id="paymentcontainer">
                    <?php
                    if ($paymentinfo['type'] == '1') { ?>
                        <div class="row">
                            <div class="span5">
                                <fieldset>
                                    <p style="display:none" class="alert payment-message"></p>
                                    <input type="hidden" name="access_token" id="access_token"
                                     value="<?php echo $paymentinfo['access_token']; ?>">
                                    <input type="hidden" name="item_number" id="item_number" value="">
                                    <div class="control-group">
                                        <label style="width: 140px; padding-top: 5px; float: left; text-align: right;">
                                        <?php echo get_string('cardholdername', 'braincert'); ?>
                                        </label>
                                        <div style="margin-left: 160px;">
                                            <input type="text" tabindex="4" class="required" name="full_name" id="full_name">
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label style="width: 140px; padding-top: 5px; float: left; text-align: right;">
                                        <?php echo get_string('cardnumber', 'braincert'); ?>
                                        &nbsp;&amp;&nbsp;
                                        <?php echo get_string('ccv', 'braincert'); ?></label>
                                        <div style="margin-left: 160px;">
                                           <input type="text" tabindex="5" name="card-number"
                                            class="card-number stripe-sensitive required" autocomplete="off"
                                            style="width: 130px;" maxlength="16">
                                           <input type="text" tabindex="6" name="card-cvc"
                                            class="card-cvc stripe-sensitive required"
                                            autocomplete="off" style="width: 50px;" maxlength="16">
                                            <i class="icon-lock"></i>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label style="width: 140px; padding-top: 5px; float: left; text-align: right;">
                                        <?php echo get_string('expiration_date', 'braincert'); ?>
                                        </label>
                                        <div style="margin-left: 160px;">
                                            <select tabindex="7" class="card-expiry-month stripe-sensitive required"
                                             style="width: 60px;">
                                                <?php
                                                for ($expdates = 1; $expdates <= 12; $expdates++) {
                                                ?>
                                                    <option value="<?php echo $expdates; ?>"><?php echo $expdates; ?></option>
                                                <?php
                                                } ?>
                                            </select>
                                            <span> / </span>
                                            <select tabindex="8" name="card-expiry-year"
                                             class="card-expiry-year stripe-sensitive required" style="width: 80px;">
                                                <?php
                                                for ($expdatep = 2013; $expdatep <= 2023; $expdatep++) {
                                                ?>
                                                    <option value="<?php echo $expdatep; ?>"><?php echo $expdatep; ?></option>
                                                <?php
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="span3 helptext">
                                <p><?php echo get_string('securely', 'braincert'); ?>
                                 <a href="https://stripe.com" target="_blank"
                                  class="stripe"><?php echo get_string('stripe', 'braincert'); ?></a>.
                                </p>
                                <p>
                                 <img alt="<?php echo get_string('usessecurely', 'braincert'); ?>"
                                  src="https://drpyjw32lhcoa.cloudfront.net/9d61ecb/img/lock.png">
                                  <img alt="<?php echo get_string('acceptvisa', 'braincert'); ?>"
                                   src="https://drpyjw32lhcoa.cloudfront.net/9d61ecb/img/cards.png">
                                </p>
                            </div>
                        </div>
                    <?php
                    } else { ?>
                        <img src="<?php echo $CFG->wwwroot; ?>/mod/braincert/images/secured-by-paypal.jpg"/> 
                    <?php
                    } ?>
                </div>
                <input type="hidden" name="class_final_amount" id="class_final_amount" value="">
                <input type="hidden" name="class_price_id" id="class_price_id" value="">
                <h5 style="float: left;font-size: 20px;line-height: 35px;margin: 0;">
                    <?php echo get_string('subtotal', 'braincert'); ?>:&nbsp;&nbsp;
                </h5>
                <div style="float: left;margin-top:8px;font-color:blue;" id="subvalue"></div>
                <div id="btncontainer" style="float: right;">
                    <?php  if (!isset($pricelist['Price'])) { ?>
                    <button id="btnCheckout" class="btn btn-primary"><?php echo get_string('buyclass', 'braincert'); ?></button>
                    <?php  } ?>
                </div>
                <div id="txtprocessing" style="display:none;float: right;"><?php echo get_string('processing', 'braincert'); ?>
                </div>  
                <p></p>  
            </div>
    </div>
        <?php
        }
        ?>
        
        <script src="https://www.paypalobjects.com/js/external/dg.js" type="text/javascript"></script>
        <?php
        if (strpos($baseurl, 'braincert.org') !== false) {
            $paypalurl = 'https://www.sandbox.paypal.com/webapps/adaptivepayment/flow/pay';
        } else {
            $paypalurl = 'https://www.paypal.com/webapps/adaptivepayment/flow/pay';
        }
        ?>
        <form action="<?php echo $paypalurl;?>" target="PPDGFrame" class="standard" >
        <input type="image" id="submitBtn" value="Pay with PayPal" style="display: none;">
        <input id="type" type="hidden" name="expType" value="lightbox">
        <input id="paykey" type="hidden" name="paykey" value="">
        </form>
        <script type="text/javascript" charset="utf-8">
        var embeddedPPFlow = new PAYPAL.apps.DGFlow({trigger: 'submitBtn'});
        if (window != top) {  
        top.location.replace(document.location); 
        }  
        embeddedPPFlow = top.embeddedPPFlow || top.opener.top.embeddedPPFlow;
        embeddedPPFlow.closeFlow();
        </script>
        <style type="text/css">
         .ui-widget.ui-widget-content{
          position: fixed !important;
          top:5% !important;;
         }
        </style>
    <?php
    }
    if ($getclassdetail) {
    ?>
    <div class="class_list">
        <?php if ($isteacher == 1) { ?>
      <div class="span6 drop_fr_icon">
        <div class="dropdown">
          <a class="dropbtn" id="dropbtn" href="javascript:void(0);"
          onclick="dropdownmenu(<?php echo $getclassdetail['id']; ?>)">
          <i class="fa fa-cog" aria-hidden="true"></i><b class="caret"></b>
          </a>
          <div id="dropdown-<?php echo $getclassdetail['id']; ?>" class="dropdown-content">
          <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/attendance_report.php?bcid=<?php echo $getclassdetail['id']; ?>">
           <i class="fa fa-users" aria-hidden="true"></i> <?php echo get_string('attendancereport', 'braincert'); ?>
          </a>
             <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/view.php?id=<?php echo $id; ?>
&bcid=<?php echo $getclassdetail['id']; ?>"
           onclick="return confirm('<?php echo get_string("areyousure", "braincert"); ?>')">
            <i class="fa fa-minus-circle" aria-hidden="true"></i>
                <?php echo get_string('cancelclass', 'braincert'); ?>
          </a>
        <?php if ($braincert->is_recurring == 1) { ?>
        <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/view.php?id=<?php echo $id; ?>
&all=2&bcid=<?php echo $getclassdetail['id']; ?>"
         onclick="return confirm('<?php echo get_string("areyousureall", "braincert"); ?>')">
          <i class="fa fa-minus-circle" aria-hidden="true"></i>
            <?php echo get_string('cancelclassall', 'braincert'); ?>
        </a>
        <?php } ?>
       <hr>
       <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/inviteemail.php?bcid=<?php echo $getclassdetail['id']; ?>">
       <i class="fa fa-envelope" aria-hidden="true"></i> <?php echo get_string('inviteemail', 'braincert'); ?>
       </a>
        <?php if ($cm->groupmode != 0) { ?>
       <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/inviteusergroup.php?bcid=<?php echo $getclassdetail['id']; ?>">
       <i class="fa fa-envelope" aria-hidden="true"></i> <?php echo get_string('inviteusergroup', 'braincert'); ?>
       </a>
        <?php } ?>
       
        <?php if ($getclassdetail['ispaid'] == 1) { ?>
        <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/addpricingscheme.php?bcid=<?php echo $getclassdetail['id']; ?>">
        <i class="fa fa-shopping-cart" aria-hidden="true"></i> <?php echo get_string('shoppingcart', 'braincert'); ?>
        </a>
        <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/adddiscount.php?bcid=<?php echo $getclassdetail['id']; ?>">
        <i class="fa fa-ticket" aria-hidden="true"></i> <?php echo get_string('discounts', 'braincert'); ?>
        </a>
        <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/payments.php?bcid=<?php echo $getclassdetail['id']; ?>">
        <i class="fa fa-cc-paypal" aria-hidden="true"></i> <?php echo get_string('payments', 'braincert'); ?>
        </a>
        <?php } ?>
       <hr>
       <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/recording.php?action=viewrecording
&bcid=<?php echo $getclassdetail['id']; ?>">
       <i class="fa fa-play-circle-o" aria-hidden="true"></i> <?php echo get_string('viewclassrecording', 'braincert'); ?>
       </a>
       <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/recording.php?action=managerecording
&bcid=<?php echo $getclassdetail['id']; ?>">
       <i class="fa fa-play-circle-o" aria-hidden="true"></i> <?php echo get_string('managerecording', 'braincert'); ?>
       </a>
       <a href="<?php echo $CFG->wwwroot; ?>/mod/braincert/managetemplate.php?bcid=<?php echo $getclassdetail['id']; ?>">
       <i class="fa fa-envelope" aria-hidden="true"></i> <?php echo get_string('managetemplate', 'braincert'); ?>
       </a>
     </div>
    </div>
    </div>
    <?php } ?>
  </div>
    
    <div class="class_div cl_list">
        <h4><i class="fa fa-bullhorn" aria-hidden="true"></i>
        <strong class="class-heading"><?php echo $braincertclass->name; ?></strong>
        <span class="<?php echo $class; ?>">
            <?php
            if ($getclassdetail['isCancel'] == 0) {
                echo $getclassdetail['status'];
            }
            if ($getclassdetail['isCancel'] == 2) {
                echo get_string('canceled', 'braincert');
            }
            if ($getclassdetail['isCancel'] == 1) {
                if (!isset($getclassdetail['class_next_date'])) {
                    echo get_string('canceled', 'braincert');
                } else {
                    date_default_timezone_set($getclassdetail['timezone_country']);
                    $date1 = date_create(date('Y-m-d', time()));
                    $date2 = date_create($getclassdetail['canceled_date']);
                    $diff = date_diff($date1, $date2);
                    if ($diff->d > 0) {
                        echo $getclassdetail['status'];
                    } else {
                        echo get_string('canceled', 'braincert');
                    }
                }
            }
            ?>
        </span>
        </h4>
        
        
        <div class="course_info">
            <p><i class="fa fa-calendar" aria-hidden="true"></i>
                <?php date_default_timezone_set($getclassdetail['timezone_country']);
                echo $getclassdetail['date']; ?>
            </p>
            <p><i class="fa fa-clock-o" aria-hidden="true"></i>
                <?php echo $getclassdetail['start_time']." - ".$getclassdetail['end_time']."
                 (".$duration." ".get_string('minutes', 'braincert').")"; ?></p>
            <p><i class="fa fa-globe" aria-hidden="true"></i>
                <?php echo "Time Zone: ".$getclassdetail['timezone_label']; ?></p>
        </div>
        <?php
        if (($getclassdetail['ispaid'] == 1)
            && ($getclassdetail['status'] != 'Past')
            && ($isteacher == 0)
            && !$getuserpaymentdetails) {
            $getbraincertgroup = $DB->get_records('groupings_groups', array('groupingid' => $cm->groupingid));
            if ($getbraincertgroup) {
                foreach ($getbraincertgroup as $getbraincertgroupkey => $getbraincertgroupval) {
                    $getgroupmembers = $DB->get_records('groups_members', array('groupid' => $getbraincertgroupval->groupid,
                    'userid' => $USER->id));
                    if ($getgroupmembers) {
        ?>
            <button class="btn btn-danger btn-sm" onclick="buyingbtn(<?php echo $getclassdetail['id']; ?>)"
            id="buy-btn"><h4><i class="fa fa-shopping-cart" aria-hidden="true"></i>
                <?php echo get_string('buy', 'braincert'); ?></h4></button>
        <?php
                    }
                }
            } else {
        ?>
            <button class="btn btn-danger btn-sm" onclick="buyingbtn(<?php echo $getclassdetail['id']; ?>)"
             id="buy-btn"><h4><i class="fa fa-shopping-cart" aria-hidden="true"></i>
            <?php echo get_string('buy', 'braincert'); ?></h4></button>
        <?php
            }
        }

        if ($getclassdetail['status'] == 'Live') {
            if ($getclassdetail['ispaid'] == 0) {
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
                        if ($cm->groupmode != 1) {
        ?>
             <a target="_blank" class="btn btn-primary" id="launch-btn"
              href="<?php echo $launchurl ?>" return false;><?php echo get_string('launch', 'braincert'); ?></a>
        <?php
                        } else {
                            $getbraincertgroup = $DB->get_records('groupings_groups', array('groupingid' => $cm->groupingid));
                            foreach ($getbraincertgroup as $getbraincertgroupkey => $getbraincertgroupval) {
                                $getgroupmembers = $DB->get_records('groups_members',
                                array('groupid' => $getbraincertgroupval->groupid,
                                'userid' => $USER->id));
                                if ($getgroupmembers) { ?>
                                <a target="_blank" class="btn btn-primary" id="launch-btn"
                                 href="<?php echo $launchurl ?>" return false;><?php echo get_string('launch', 'braincert'); ?></a>
        <?php
                                }
                            }
                        }
                    }
                } else if ($getlaunchurl['status'] == "error") {
                    echo "<strong>".$getlaunchurl["error"]."</strong>";
                }
            } else if ($getclassdetail['ispaid'] == 1) {
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
                        if ($getuserpaymentdetails) {
                            if ($cm->groupmode != 1) {
        ?>
              <a target="_blank" class="btn btn-primary" id="launch-btn"
               href="<?php echo $launchurl ?>" return false;><?php echo get_string('launch', 'braincert'); ?></a>
        <?php
                            } else {
                                $getbraincertgroup = $DB->get_records('groupings_groups', array('groupingid' => $cm->groupingid));
                                foreach ($getbraincertgroup as $getbraincertgroupkey => $getbraincertgroupval) {
                                    $getgroupmembers = $DB->get_records('groups_members',
                                    array('groupid' => $getbraincertgroupval->groupid, 'userid' => $USER->id));
                                    if ($getgroupmembers) { ?>
                  <a target="_blank" class="btn btn-primary" id="launch-btn"
                   href="<?php echo $launchurl ?>" return false;><?php echo get_string('launch', 'braincert'); ?></a>
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
        } else if ($isteacher && $getclassdetail['status'] != 'Past') {
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
              href="<?php echo $launchurl; ?>" return false;><?php echo get_string('launch', 'braincert'); ?></a>
        <?php
                } else {
                    if ($cm->groupmode != 1) { ?>
              <a target="_blank" class="btn btn-primary" id="launch-btn"
               href="<?php echo $launchurl ?>" return false;><?php echo get_string('launch', 'braincert'); ?></a>
        <?php
                    } else {
                        $getbraincertgroup = $DB->get_records('groupings_groups', array('groupingid' => $cm->groupingid));
                        foreach ($getbraincertgroup as $getbraincertgroupkey => $getbraincertgroupval) {
                            $getgroupmembers = $DB->get_records('groups_members',
                            array('groupid' => $getbraincertgroupval->groupid, 'userid' => $USER->id));
                            if ($getgroupmembers) {
        ?>
                        <a target="_blank" class="btn btn-primary" id="launch-btn"
                         href="<?php echo $launchurl ?>" return false;><?php echo get_string('launch', 'braincert'); ?></a>
        <?php
                            }
                        }
                    }
                }
            } else if ($getlaunchurl['status'] == "error") {
                echo "<strong>".$getlaunchurl["error"]."</strong>";
            }
        }
        ?>
    </div>
<?php
    }
}


if (!empty($braincertclass)) {
    $getrecordinglist = braincert_get_class_recording($braincertclass->class_id);
    if (!isset($getrecordinglist['Recording']) && isset($getrecordinglist['status']) && ($getrecordinglist['status'] != 'error')) {
        if ($isteacher == 1) {
            $table = new html_table();
            $table->head = array ();
            $table->head[] = '#';
            $table->head[] = 'Name';
            $table->head[] = 'Date/Time';
            $table->head[] = 'Action';
            $i = 1;
            foreach ($getrecordinglist as $recordinglist) {
                if ($recordinglist['status'] == 1) {
                    $row = array ();
                    $row[] = $i;
                    if (!empty($recordinglist['fname'])) {
                        $row[] = $recordinglist['fname'];
                    } else {
                        $row[] = $recordinglist['name'];
                    }
                    $row[] = $recordinglist['date_recorded'];
                    $row[] = '<a href="javascript:void(0)" data-rpath="'.$recordinglist['record_path'].
                    '" class="viewrecording">'.get_string('viewclassrecording', 'braincert').'</a>';

                    $table->data[] = $row;

                    $i++;
                }
            }

            if (!empty($table->data)) {
                echo html_writer::start_tag('div', array('class' => 'no-overflow display-table'));
                echo html_writer::table($table);
                echo html_writer::end_tag('div');
            } ?>

            <video id="recording-video" class="video-js vjs-default-skin"
                controls
                width="800" height="350">
            </video>
        <?php
        }
    }
}
?>

<script type="text/javascript">
    jQuery(document).ready(function () {

        jQuery("#recording-video").hide();
        jQuery("#page-mod-braincert-view").find(".viewrecording").click(function(){
            jQuery("#recording-video").show();

            var videourl = jQuery(this).data("rpath");
            var sources = [{"type": "video/mp4", "src": videourl}];
            var player = videojs('recording-video', {
                controls: true,
                sources: sources,
                techOrder: ['youtube', 'html5']
            });
            player.pause();
            player.src(sources);
            player.load();
            player.play();
        });

        jQuery(".close").click(function(event) {
            jQuery(".modal").hide();
        });

        jQuery("#btnCheckout").click(function (event) {

            var plan_commission = '<?php echo $getplan['commission'];?>';
            if(plan_commission==0){
                jQuery('#paypal_form_one_time').submit();
                return false;
            }

            jQuery("#btncontainer").css('display','none');
            jQuery("#txtprocessing").css('display','');

            var orgamount = jQuery("#class_final_amount").val();
            var class_id = '<?php echo $braincertclass->class_id;?>';
            var price_id = jQuery("#class_price_id").val();
            var cancelUrl = '<?php echo $url; ?>';
            var returnUrl = '<?php echo $url; ?>&task=returnpayment&class_id='+class_id+'&amount='+orgamount+'&payment_mode=paypal';

            var card_holder_name = jQuery(".full_name").val();
            var card_number = jQuery(".card-number").val();
            var card_cvc = jQuery(".card-cvc").val();
            var card_expiry_month = jQuery(".card-expiry-month").val();
            var card_expiry_year = jQuery(".card-expiry-year").val();
            var student_email = '<?php echo $USER->email; ?>';

            jQuery.ajax({
                url: "<?php echo $url; ?>&task=class_checkout",
                type: "POST",
                data: {class_id:class_id,price_id:price_id,
                       cancelUrl:cancelUrl,returnUrl:returnUrl,
                       card_holder_name:card_holder_name,card_number:card_number,
                       card_cvc:card_cvc,card_expiry_month:card_expiry_month,
                       card_expiry_year:card_expiry_year,student_email:student_email},
                success: function(result) {
                    var obj = jQuery.parseJSON(result);

                    if (obj.status=="error") {
                        jQuery(".card_error").show().html(obj.error);
                    }
                    if (obj.status=="ok") {
                        jQuery(".card_error").hide();
                        if (obj.payKey) {
                            jQuery("#paykey").val(obj.payKey);
                            jQuery("#submitBtn").trigger('click');
                            jQuery('#modal-content-buying').hide();
                        } else {
                            if (obj.charge_id) {
                                var url = "<?php echo $url;?>"+
                                "&task=returnpayment"+
                                "&class_id="+class_id+"&amount="+orgamount+"&payment_mode=stripe";
                                window.top.location.href = url;
                            }
                        } 
                    }
                    jQuery("#btncontainer").css('display','block');
                    jQuery("#txtprocessing").css('display','none');
                }
            });
        });

        jQuery('input[name=pricescheme]').click(function (event) {
            var selval = jQuery(this).val();
            jQuery('#subvalue').text("<?php echo $currencysymbol; ?>" + selval);
            var _amnt = returnMoney(selval);
            var _option_id=jQuery(this).attr('option_id');

            jQuery("#class_final_amount").val(_amnt);
            jQuery("#one_time_amount").val(_amnt);
            var class_id = '<?php echo $braincertclass->class_id;?>';
            var returnUrl_one_time = '<?php echo $url; ?>'+
            '&task=returnpayment&class_id='+class_id+'&amount='+_amnt+'&payment_mode=paypal';
            jQuery("#return_url").val(returnUrl_one_time);
            var base_url_api = '<?php if (strpos($baseurl, 'braincert.org') !== false) {
                echo "https://www.braincert.org/";
} else {
                echo "https://www.braincert.com/";
} ?>';
            var ipnurl = base_url_api+'index.php?'+
            'option=com_classroomengine&view=classdetails&task=returnpaypalapi&Id'+
            '='+class_id+'&student_email=<?php echo $USER->email; ?>&item_number='+_option_id;
            jQuery(".one_time_notify_url").val(ipnurl);
            jQuery("#class_price_id").val(_option_id);
        });
    });

    function returnMoney(number) {
        var nStr = '' + Math.round(parseFloat(number) * 100) / 100;
        var x = nStr.split('.');
        var x1 = x[0];
        var x2 = x.length > 1 ? '.' + x[1] : '.00';
        var rgx = /(\d+)(\d{3})/;
        
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        
        return x1 + x2;
    }

    function buyingbtn(classid) {
        jQuery("#modal-content-buying").show();
        jQuery("#pricescheme0").trigger("click");
    }
</script>

<form action="https://www.<?php echo strpos($baseurl, 'braincert.org') !== false ? 'sandbox.' : ''; ?>paypal.com/cgi-bin/webscr"
 method="post" class="paypal-form" target="_top" id="paypal_form_one_time">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="amount" id="one_time_amount" value="">
<input type="hidden" name="business" value="<?php echo $paymentinfo['paypal_id']; ?>">
<input type="hidden" name="item_name" value="<?php echo $braincertclass->name; ?>">
<input type="hidden" name="currency_code" value="<?php echo $currencycode; ?>">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="rm" value="1">
<input type="hidden" name="custom" value="">
<input type="hidden" name="return" id="return_url" value="">
<input type="hidden" name="cancel_return" value="<?php echo $url; ?>">
<input type="hidden" name="notify_url" class="one_time_notify_url" value="">
</form>

<div class="allclassdiv">
<a href="<?php echo $CFG->wwwroot.'/mod/braincert/index.php?id='.$course->id; ?>">
<?php echo get_string('viewallclass', 'braincert'); ?>
</a>
</div>
<?php

echo $OUTPUT->footer();