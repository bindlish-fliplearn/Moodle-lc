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
 * Page to edit clatestzes
 *
 * This page generally has two columns:
 * The right column lists all available questions in a chosen category and
 * allows them to be edited or more to be added. This column is only there if
 * the clatest does not already have student attempts
 * The left column lists all questions that have been added to the current clatest.
 * The lecturer can add questions from the right hand list to the clatest or remove them
 *
 * The script also processes a number of actions:
 * Actions affecting a clatest:
 * up and down  Changes the order of questions and page breaks
 * addquestion  Adds a single question to the clatest
 * add          Adds several selected questions to the clatest
 * addrandom    Adds a certain number of random questions to the clatest
 * repaginate   Re-paginates the clatest
 * delete       Removes a question from the clatest
 * savechanges  Saves the order and grades for questions in the clatest
 *
 * @package    mod_clatest
 * @copyright  1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/clatest/locallib.php');
require_once($CFG->dirroot . '/mod/clatest/addrandomform.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/category_class.php');

// These params are only passed from page request to request while we stay on
// this page otherwise they would go in question_edit_setup.
$scrollpos = optional_param('scrollpos', '', PARAM_INT);

list($thispageurl, $contexts, $cmid, $cm, $clatest, $pagevars) =
        question_edit_setup('editq', '/mod/clatest/edit.php', true);

$defaultcategoryobj = question_make_default_categories($contexts->all());
$defaultcategory = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;

$clatesthasattempts = clatest_has_attempts($clatest->id);

$PAGE->set_url($thispageurl);

// Get the course object and related bits.
$course = $DB->get_record('course', array('id' => $clatest->course), '*', MUST_EXIST);
$clatestobj = new clatest($clatest, $cm, $course);
$structure = $clatestobj->get_structure();

// You need mod/clatest:manage in addition to question capabilities to access this page.
require_capability('mod/clatest:manage', $contexts->lowest());

// Log this visit.
$params = array(
    'courseid' => $course->id,
    'context' => $contexts->lowest(),
    'other' => array(
        'clatestid' => $clatest->id
    )
);
$event = \mod_clatest\event\edit_page_viewed::create($params);
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
    // Re-paginate the clatest.
    $structure->check_can_be_edited();
    $questionsperpage = optional_param('questionsperpage', $clatest->questionsperpage, PARAM_INT);
    clatest_repaginate_questions($clatest->id, $questionsperpage );
    clatest_delete_previews($clatest);
    redirect($afteractionurl);
}

if (($addquestion = optional_param('addquestion', 0, PARAM_INT)) && confirm_sesskey()) {
    // Add a single question to the current clatest.
    $structure->check_can_be_edited();
    clatest_require_question_use($addquestion);
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    clatest_add_clatest_question($addquestion, $clatest, $addonpage);
    clatest_delete_previews($clatest);
    clatest_update_sumgrades($clatest);
    $thispageurl->param('lastchanged', $addquestion);
    redirect($afteractionurl);
}

if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $structure->check_can_be_edited();
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    // Add selected questions to the current clatest.
    $rawdata = (array) data_submitted();
    foreach ($rawdata as $key => $value) { // Parse input for question ids.
        if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
            $key = $matches[1];
            clatest_require_question_use($key);
            clatest_add_clatest_question($key, $clatest, $addonpage);
        }
    }
    clatest_delete_previews($clatest);
    clatest_update_sumgrades($clatest);
    redirect($afteractionurl);
}

if ($addsectionatpage = optional_param('addsectionatpage', false, PARAM_INT)) {
    // Add a section to the clatest.
    $structure->check_can_be_edited();
    $structure->add_section_heading($addsectionatpage);
    clatest_delete_previews($clatest);
    redirect($afteractionurl);
}

if ((optional_param('addrandom', false, PARAM_BOOL)) && confirm_sesskey()) {
    // Add random questions to the clatest.
    $structure->check_can_be_edited();
    $recurse = optional_param('recurse', 0, PARAM_BOOL);
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    $categoryid = required_param('categoryid', PARAM_INT);
    $randomcount = required_param('randomcount', PARAM_INT);
    clatest_add_random_questions($clatest, $addonpage, $categoryid, $randomcount, $recurse);

    clatest_delete_previews($clatest);
    clatest_update_sumgrades($clatest);
    redirect($afteractionurl);
}

if (optional_param('savechanges', false, PARAM_BOOL) && confirm_sesskey()) {

    // If rescaling is required save the new maximum.
    $maxgrade = unformat_float(optional_param('maxgrade', '', PARAM_RAW_TRIMMED), true);
    if (is_float($maxgrade) && $maxgrade >= 0) {
        clatest_set_grade($maxgrade, $clatest);
        clatest_update_all_final_grades($clatest);
        clatest_update_grades($clatest, 0, true);
    }

    redirect($afteractionurl);
}

// Get the question bank view.
$questionbank = new mod_clatest\question\bank\custom_view($contexts, $thispageurl, $course, $cm, $clatest);
$questionbank->set_clatest_has_attempts($clatesthasattempts);
$questionbank->process_actions($thispageurl, $cm);

// End of process commands =====================================================.

$PAGE->set_pagelayout('incourse');
$PAGE->set_pagetype('mod-clatest-edit');

$output = $PAGE->get_renderer('mod_clatest', 'edit');

$PAGE->set_title(get_string('editingclatestx', 'clatest', format_string($clatest->name)));
$PAGE->set_heading($course->fullname);
$node = $PAGE->settingsnav->find('mod_clatest_edit', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}
echo $OUTPUT->header();

// Initialise the JavaScript.
$clatesteditconfig = new stdClass();
$clatesteditconfig->url = $thispageurl->out(true, array('qbanktool' => '0'));
$clatesteditconfig->dialoglisteners = array();
$numberoflisteners = $DB->get_field_sql("
    SELECT COALESCE(MAX(page), 1)
      FROM {clatest_slots}
     WHERE clatestid = ?", array($clatest->id));

for ($pageiter = 1; $pageiter <= $numberoflisteners; $pageiter++) {
    $clatesteditconfig->dialoglisteners[] = 'addrandomdialoglaunch_' . $pageiter;
}

$PAGE->requires->data_for_js('clatest_edit_config', $clatesteditconfig);
$PAGE->requires->js('/question/qengine.js');

// Questions wrapper start.
echo html_writer::start_tag('div', array('class' => 'mod-clatest-edit-content'));

echo $output->edit_page($clatestobj, $structure, $contexts, $thispageurl, $pagevars);

echo '<ul class="menu  align-tr-tr" id="action-menu-2-menu" data-rel="menu-content" aria-labelledby="action-menu-toggle-2" role="menu" data-align="tr-tr" data-constraint=".mod-quiz-edit-content" aria-hidden="false" style="top: -110px;">

<li role="presentation" id="yui_3_17_2_1_1545799467910_546">    <a href="http://localhost/flip-moodle-lc/mod/clatest/edit.php?cmid=38391" class="cm-edit-action questionbank menu-action add-menu" data-header="Add from the question bank at the end" data-action="questionbank" data-addonpage="0" role="menuitem" aria-labelledby="actionmenuaction-8" id="yui_3_17_2_1_1545799467910_545"><img class="icon " alt="from question bank" title="from question bank" src="http://localhost/flip-moodle-lc/theme/image.php?theme=adaptable&amp;component=core&amp;rev=1545655717&amp;image=t%2Fadd"><span class="menu-action-text" id="actionmenuaction-8">from question bank</span></a>
</li> </ul>';

// Questions wrapper end.
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
