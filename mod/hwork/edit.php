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
 * Page to edit hworkzes
 *
 * This page generally has two columns:
 * The right column lists all available questions in a chosen category and
 * allows them to be edited or more to be added. This column is only there if
 * the hwork does not already have student attempts
 * The left column lists all questions that have been added to the current hwork.
 * The lecturer can add questions from the right hand list to the hwork or remove them
 *
 * The script also processes a number of actions:
 * Actions affecting a hwork:
 * up and down  Changes the order of questions and page breaks
 * addquestion  Adds a single question to the hwork
 * add          Adds several selected questions to the hwork
 * addrandom    Adds a certain number of random questions to the hwork
 * repaginate   Re-paginates the hwork
 * delete       Removes a question from the hwork
 * savechanges  Saves the order and grades for questions in the hwork
 *
 * @package    mod_hwork
 * @copyright  1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/hwork/locallib.php');
require_once($CFG->dirroot . '/mod/hwork/addrandomform.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/category_class.php');

// These params are only passed from page request to request while we stay on
// this page otherwise they would go in question_edit_setup.
$scrollpos = optional_param('scrollpos', '', PARAM_INT);

list($thispageurl, $contexts, $cmid, $cm, $hwork, $pagevars) =
        question_edit_setup('editq', '/mod/hwork/edit.php', true);

$defaultcategoryobj = question_make_default_categories($contexts->all());
$defaultcategory = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;

$hworkhasattempts = hwork_has_attempts($hwork->id);

$PAGE->set_url($thispageurl);

// Get the course object and related bits.
$course = $DB->get_record('course', array('id' => $hwork->course), '*', MUST_EXIST);
$hworkobj = new hwork($hwork, $cm, $course);
$structure = $hworkobj->get_structure();

// You need mod/hwork:manage in addition to question capabilities to access this page.
require_capability('mod/hwork:manage', $contexts->lowest());

// Log this visit.
$params = array(
    'courseid' => $course->id,
    'context' => $contexts->lowest(),
    'other' => array(
        'hworkid' => $hwork->id
    )
);
$event = \mod_hwork\event\edit_page_viewed::create($params);
$event->trigger();

// Process commands ============================================================.

// Get the list of question ids had their check-boxes ticked.
$selectedslots = array();
$params = (array) data_submitted();
foreach ($params as $key => $value) {
    if (preg_match('!^s([0-9]+)$!', $key, $matches)) {
        $selectedslots[] = $matches[1];
    }
}

$afteractionurl = new moodle_url($thispageurl);
if ($scrollpos) {
    $afteractionurl->param('scrollpos', $scrollpos);
}

if (optional_param('repaginate', false, PARAM_BOOL) && confirm_sesskey()) {
    // Re-paginate the hwork.
    $structure->check_can_be_edited();
    $questionsperpage = optional_param('questionsperpage', $hwork->questionsperpage, PARAM_INT);
    hwork_repaginate_questions($hwork->id, $questionsperpage );
    hwork_delete_previews($hwork);
    redirect($afteractionurl);
}

if (($addquestion = optional_param('addquestion', 0, PARAM_INT)) && confirm_sesskey()) {
    // Add a single question to the current hwork.
    $structure->check_can_be_edited();
    hwork_require_question_use($addquestion);
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    hwork_add_hwork_question($addquestion, $hwork, $addonpage);
    hwork_delete_previews($hwork);
    hwork_update_sumgrades($hwork);
    $thispageurl->param('lastchanged', $addquestion);
    redirect($afteractionurl);
}

if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $structure->check_can_be_edited();
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    // Add selected questions to the current hwork.
    $rawdata = (array) data_submitted();
    foreach ($rawdata as $key => $value) { // Parse input for question ids.
        if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
            $key = $matches[1];
            hwork_require_question_use($key);
            hwork_add_hwork_question($key, $hwork, $addonpage);
        }
    }
    hwork_delete_previews($hwork);
    hwork_update_sumgrades($hwork);
    redirect($afteractionurl);
}

if ($addsectionatpage = optional_param('addsectionatpage', false, PARAM_INT)) {
    // Add a section to the hwork.
    $structure->check_can_be_edited();
    $structure->add_section_heading($addsectionatpage);
    hwork_delete_previews($hwork);
    redirect($afteractionurl);
}

if ((optional_param('addrandom', false, PARAM_BOOL)) && confirm_sesskey()) {
    // Add random questions to the hwork.
    $structure->check_can_be_edited();
    $recurse = optional_param('recurse', 0, PARAM_BOOL);
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    $categoryid = required_param('categoryid', PARAM_INT);
    $randomcount = required_param('randomcount', PARAM_INT);
    hwork_add_random_questions($hwork, $addonpage, $categoryid, $randomcount, $recurse);

    hwork_delete_previews($hwork);
    hwork_update_sumgrades($hwork);
    redirect($afteractionurl);
}

if (optional_param('savechanges', false, PARAM_BOOL) && confirm_sesskey()) {

    // If rescaling is required save the new maximum.
    $maxgrade = unformat_float(optional_param('maxgrade', '', PARAM_RAW_TRIMMED), true);
    if (is_float($maxgrade) && $maxgrade >= 0) {
        hwork_set_grade($maxgrade, $hwork);
        hwork_update_all_final_grades($hwork);
        hwork_update_grades($hwork, 0, true);
    }

    redirect($afteractionurl);
}

// Get the question bank view.
$questionbank = new mod_hwork\question\bank\custom_view($contexts, $thispageurl, $course, $cm, $hwork);
$questionbank->set_hwork_has_attempts($hworkhasattempts);
$questionbank->process_actions($thispageurl, $cm);

// End of process commands =====================================================.

$PAGE->set_pagelayout('incourse');
$PAGE->set_pagetype('mod-hwork-edit');

$output = $PAGE->get_renderer('mod_hwork', 'edit');

$PAGE->set_title(get_string('editinghworkx', 'hwork', format_string($hwork->name)));
$PAGE->set_heading($course->fullname);
$node = $PAGE->settingsnav->find('mod_hwork_edit', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}
echo $OUTPUT->header();

// Initialise the JavaScript.
$hworkeditconfig = new stdClass();
$hworkeditconfig->url = $thispageurl->out(true, array('qbanktool' => '0'));
$hworkeditconfig->dialoglisteners = array();
$numberoflisteners = $DB->get_field_sql("
    SELECT COALESCE(MAX(page), 1)
      FROM {hwork_slots}
     WHERE hworkid = ?", array($hwork->id));

for ($pageiter = 1; $pageiter <= $numberoflisteners; $pageiter++) {
    $hworkeditconfig->dialoglisteners[] = 'addrandomdialoglaunch_' . $pageiter;
}

$PAGE->requires->data_for_js('hwork_edit_config', $hworkeditconfig);
$PAGE->requires->js('/question/qengine.js');

// Questions wrapper start.
echo html_writer::start_tag('div', array('class' => 'mod-hwork-edit-content'));

$url = $CFG->wwwroot;
echo '<h4>Add Questions</h4>';
echo '<ul class="menu  align-tr-tr" id="action-menu-2-menu" data-rel="menu-content" aria-labelledby="action-menu-toggle-2" role="menu" data-align="tr-tr" data-constraint=".mod-quiz-edit-content" aria-hidden="false" style="top: -110px;">
<li role="presentation" id="yui_3_17_2_1_1554189599282_534">    <a href="'.$url.'/question/question.php?courseid='.$hwork->course.'&sesskey='.sesskey().'&qtype=multichoice&returnurl=/mod/quiz/edit.php?cmid='.$cmid.'&addonpage=0&cmid='.$cmid.'&category='.$defaultcategory.'&addonpage=0&appendqnumstring=addquestion" class="cm-edit-action addquestion menu-action add-menu" data-action="addquestion" role="menuitem" aria-labelledby="actionmenuaction-7" id="yui_3_17_2_1_1554189599282_533"><img class="icon " alt="a new question" title="a new question" src="'.$url.'/theme/image.php?theme=adaptable&amp;component=core&amp;rev=1553860198&amp;image=t%2Fadd"><span class="menu-action-text" id="actionmenuaction-7">a new question</span></a>
</li>
<li role="presentation"><a href="'.$url.'/mod/quiz/edit.php?cmid='.$cmid.'" class="cm-edit-action questionbank menu-action add-menu" data-header="Add from the question bank at the end" data-action="questionbank" data-addonpage="0" role="menuitem" aria-labelledby="actionmenuaction-8"><img class="icon " alt="a new question" title="a new question" src="'.$url.'/theme/image.php?theme=adaptable&amp;component=core&amp;rev=1553860198&amp;image=t%2Fadd"><span class="menu-action-text" id="actionmenuaction-8">from question bank</span></a>
</li>
<li role="presentation" id="yui_3_17_2_1_1553863666090_551">    <a href="'.$url.'/mod/quiz/addrandom.php?returnurl='.$url.'%2Fmod%2Fquiz%2Fedit.php%3Fcmid%3D139926%26amp%3Bdata-addonpage%3D0&amp;cmid='.$cmid.'&amp;appendqnumstring=addarandomquestion" class="cm-edit-action addarandomquestion menu-action add-menu" data-header="Add a random question at the end" data-addonpage="0" data-action="addarandomquestion" role="menuitem" aria-labelledby="actionmenuaction-9" id="yui_3_17_2_1_1553863666090_550"><img class="icon " alt="a random question" title="a random question" src="'.$url.'/theme/image.php?theme=adaptable&amp;component=core&amp;rev=1553860198&amp;image=t%2Fadd"><span class="menu-action-text" id="actionmenuaction-9">a random question</span></a>
</li>
</ul>';
echo $output->edit_page($hworkobj, $structure, $contexts, $thispageurl, $pagevars);

// Questions wrapper end.
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
