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
 * View Attendance Report of virtual class.
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");

$bcid = required_param('bcid', PARAM_INT);   // Virtual Class ID.

$PAGE->set_url('/mod/braincert/attendance_report.php', array('bcid' => $bcid));

$braincertrec = $DB->get_record('braincert', array('class_id' => $bcid));
if (!$course = $DB->get_record('course', array('id' => $braincertrec->course))) {
    print_error('invalidcourseid');
}

require_login($course);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('pluginname', 'braincert'));
$attendancereport = get_string('attendancereport', 'braincert');
$PAGE->navbar->add($attendancereport);

$PAGE->requires->css('/mod/braincert/css/styles.css', true);
if ($CFG->version < 2017051500) {
?>
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<?php
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('classattendees', 'braincert'));

$data['task'] = 'listclass';
$getclasslists = braincert_get_curl_info($data);

foreach ($getclasslists['classes'] as $getclasslist) {
    if ($getclasslist['id'] == $bcid) {
        $classdurationmin = round($getclasslist['duration'] / 60);
        $count = round( $classdurationmin / 5);
    }
}

$data['task']    = 'getclassreport';
$data['classId'] = $bcid;

$getclassattendees = braincert_get_curl_info($data);

if (isset($getclassattendees['status']) && ($getclassattendees['status'] == "error")) {
    echo '<div class="alert alert-danger"><strong>'.$getclassattendees['error'].'</strong></div>';
} else if (isset($getclassattendees['Report'])) {
    echo '<div class="alert alert-danger"><strong>'.$getclassattendees['Report'].'</strong></div>';
} else {
    if (isset($getclassattendees['0']['classId'])) {
        if (count($getclassattendees) > 0) {
        ?>
            <div id="container" style="width: 100%;">
              <canvas id="myChart" width="400" height="400"></canvas>
            </div>
        <?php
        }
        $table = new html_table();
        $table->head = array ();
        $table->head[] = get_string('id', 'braincert');
        $table->head[] = get_string('name', 'braincert');
        $table->head[] = get_string('duration', 'braincert');
        $table->head[] = get_string('timein', 'braincert');
        $table->head[] = get_string('timeout', 'braincert');'Time out';
        $table->head[] = get_string('attendence', 'braincert');
        $i = 1;
        $grapharrayteacher = array();
        foreach ($getclassattendees as $classattendee) {
            if ($classattendee['userId'] != 0) {
                $userrec = $DB->get_record('user', array('id' => $classattendee['userId']));
            }
            $spenttime = strtotime($classattendee['duration']) - strtotime('TODAY');
            $grapharrayteacher[$i] = new stdClass();
            $grapharrayteacher[$i]->spenttime = intval($spenttime / 60);
            $grapharrayteacher[$i]->email = $userrec->email;
            $row = array ();
            $row[] = $i;
            $row[] = $userrec->username."<br>(".$userrec->email.")";
            $row[] = $classattendee['duration']."(".$classattendee['percentage'].")";
            $timein = '';
            $timeout = '';
            foreach ($classattendee['session'] as $time) {
                $timein .= "<i class='fa fa-calendar' aria-hidden='true'></i> ".$time['time_in']."<br>";
                $timeout .= "<i class='fa fa-calendar' aria-hidden='true'></i> ".$time['time_out']."<br>";
            }
            $row[] = $timein;
            $row[] = $timeout;
            $row[] = $classattendee['attendance'];
            $table->data[] = $row;
            $i++;
        }

        if (!empty($table)) {
            echo html_writer::start_tag('div', array('class' => 'no-overflow display-table attendeestable'));
            echo html_writer::table($table);
            echo html_writer::end_tag('div');
        }
    } else {
        echo '<div class="alert alert-danger"><strong>'.get_string('norecordfound', 'braincert').'</strong></div>';
    }
}
?>
<script src="<?php echo $CFG->wwwroot; ?>/mod/braincert/js/chart.bundle.js"></script>
<style>
canvas {
    -moz-user-select: none;
    -webkit-user-select: none;
    -ms-user-select: none;
}
</style>
<?php
$colorarray = array("#ce0704", "#0315ab", "#7d2020",
                    "#8e116b", "#43118e", "#114d8e",
                    "#118e79", "#118e17", "#568e11",
                    "#8e5e11", "#420807", "#9bb995", "#3a1b00");
?>
<script>
var ctx = document.getElementById("myChart").getContext('2d');
var barChartData = {
  labels: [
    <?php
    for ($i = 1; $i < $count; $i++) {
    ?>
      '<?php echo $i * 5;?>-<?php echo $i * 5 + 5;?>'
            <?php if ($i != $count) {
            ?>,<?php
}
    }?>],
  datasets: [
    <?php
    $m = 0;
    foreach ($grapharrayteacher as $key => $value) {
        $spenttime = floor($value->spenttime / 5);
    ?>
    {
    label: "<?php echo $value->email;?>",
    backgroundColor: '<?php echo $colorarray[$key]?>',
    borderColor: '<?php echo $colorarray[$key]?>',
    borderWidth: 1,
    data: [
        <?php for ($i = 1; $i < $count; $i++) {
            echo ($i == $spenttime) ? $value->spenttime : '""';
            if ($i != $count) {
            ?>,<?php
            }
}?>]
      },
        <?php
        $m = $m + 25;
    }
        ?>
  ]
};
var myChart = new Chart(ctx, {
    type: 'bar',
    data: barChartData,

    options: {
        width:500,
        height:300,
        scaleShowGridLines: false,
        showScale: false,
        maintainAspectRatio: this.maintainAspectRatio,
        barShowStroke: false,
        responsive: true,
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero:true
                }
            }]
        }
    }
});
</script>
<?php
echo $OUTPUT->footer();