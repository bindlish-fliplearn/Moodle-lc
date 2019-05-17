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
 * View and manage recording of virtual class.
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once('locallib.php');

$bcid = required_param('bcid', PARAM_INT);   // Virtual Class ID.
$action = required_param('action', PARAM_TEXT);
$task = optional_param('task', '', PARAM_TEXT);
$rid = optional_param('rid', 0, PARAM_INT);


$PAGE->set_url('/mod/braincert/recording.php', array('bcid' => $bcid, 'action' => $action));

$braincertrec = $DB->get_record('braincert', array('class_id' => $bcid));
if (!$course = $DB->get_record('course', array('id' => $braincertrec->course))) {
    print_error('invalidcourseid');
}

require_login($course);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('pluginname', 'braincert'));
$viewclassrecording = get_string('viewclassrecording', 'braincert');
$PAGE->navbar->add($viewclassrecording);

$PAGE->requires->css('/mod/braincert/css/styles.css', true);
if ($CFG->version < 2017051500) {
?>
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<?php
}
$PAGE->requires->js('/mod/braincert/js/jquery.min.js', true);
$PAGE->requires->js('/mod/braincert/js/video.js', true);
if ($task == 'changestatusrecording') {
    $data['task'] = 'changestatusrecording';
    $data['rid']  = $rid;
    $getstatusinfo = braincert_get_curl_info($data);
    if ($getstatusinfo['status'] == "ok") {
        redirect(new moodle_url('/mod/braincert/recording.php?bcid='.$bcid.'&action='.$action));
    }
}
if ($task == 'removeclassrecording') {
    $data['task'] = 'removeclassrecording';
    $data['rid']  = $rid;
    $getremoveinfo = braincert_get_curl_info($data);
    if ($getremoveinfo['status'] == "ok") {
        redirect(new moodle_url('/mod/braincert/recording.php?bcid='.$bcid.'&action='.$action));
    }
}

echo $OUTPUT->header();
if ($action == 'managerecording') {
    echo $OUTPUT->heading(get_string('recordinglist', 'braincert'));
} else {
    echo $OUTPUT->heading(get_string('viewrecording', 'braincert'));
}

$data['task']     = 'getclassrecording';
$data['class_id'] = $bcid;

$getrecordinglist = braincert_get_curl_info($data);

if (isset($getrecordinglist['Recording']) && ($getrecordinglist['Recording'] == 'No video recording available')) {
    echo '<div class="alert alert-danger"><strong>'.$getrecordinglist['Recording'].'</strong></div>';
} else {
    if ($action == 'managerecording') {
        $table = new html_table();
        $table->head = array ();
        $table->head[] = 'Record ID';
        $table->head[] = 'File Name';
        $table->head[] = 'Date Created';
        $table->head[] = 'Actions';
        foreach ($getrecordinglist as $recordinglist) {
            $row = array ();
            if ($recordinglist['id']) {
                $row[] = $recordinglist['id'];
                if (!empty($recordinglist['fname'])) {
                    $row[] = $recordinglist['fname'];
                } else {
                    $row[] = $recordinglist['name'];
                }
                $row[] = $recordinglist['date_recorded'];
                if ($recordinglist['status'] == 0) {
                    $fa = "fa-circle-o";
                } else {
                    $fa = "fa-check";
                }
                $row[] = '<a download href="'.$recordinglist['record_url'].'"><i class="fa fa-download" aria-hidden="true"></i></a>
                <a href="'.$CFG->wwwroot.'/mod/braincert/recording.php?bcid='.$bcid.
                '&action='.$action.'&task=changestatusrecording&rid='.$recordinglist['id'].'">
                <i class="fa '.$fa.'" aria-hidden="true"></i></a>
                <a href="'.$CFG->wwwroot.'/mod/braincert/recording.php?bcid='.$bcid.
                '&action='.$action.'&task=removeclassrecording&rid='.$recordinglist['id'].'">
                <i class="fa fa-trash-o" aria-hidden="true"></i></a>';
            }
            $table->data[] = $row;
        }
        if (!empty($table)) {
            echo html_writer::start_tag('div', array('class' => 'no-overflow display-table'));
            echo html_writer::table($table);
            echo html_writer::end_tag('div');
        }
    } else {
?>
    <div class="video-area">
      <div class="video-list-area">
        <h4><?php echo get_string('recordingslist', 'braincert'); ?> : 
        <select name="videourl" id="videourl">
            <?php foreach ($getrecordinglist as $i => $recordinglist) { ?>
            <option value="<?php echo $recordinglist['record_path']?>">
                <?php echo $recordinglist['fname'] ? $recordinglist['fname'] : get_string('recording', 'braincert').' - '.$i; ?>
            </option>
            <?php } ?>    
        </select>
        </h4>
      </div>
      <video id="recording-video"
        class="video-js vjs-default-skin"
        controls
        width="800" height="350">
      </video>
    </div>

    <script type="text/javascript">
      $(document).ready(function () {
        var videourl = jQuery('#videourl').val();
        var player = videojs('recording-video', {
        controls: true,
        sources: [{src: videourl, type: 'video/mp4'}],
        techOrder: ['youtube', 'html5']
        });
        $('#videourl').on('change', function () {
        var videourl = jQuery('#videourl').val();
        var sources = [{"type": "video/mp4", "src": videourl}];
        player.pause();
        player.src(sources);
        player.load();
        player.play();
        });
      });
    </script>
<?php
    }
}

echo $OUTPUT->footer();