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
 * Page to edit pratestzes
 *
 * This page generally has two columns:
 * The right column lists all available questions in a chosen category and
 * allows them to be edited or more to be added. This column is only there if
 * the pratest does not already have student attempts
 * The left column lists all questions that have been added to the current pratest.
 * The lecturer can add questions from the right hand list to the pratest or remove them
 *
 * The script also processes a number of actions:
 * Actions affecting a pratest:
 * up and down  Changes the order of questions and page breaks
 * addquestion  Adds a single question to the pratest
 * add          Adds several selected questions to the pratest
 * addrandom    Adds a certain number of random questions to the pratest
 * repaginate   Re-paginates the pratest
 * delete       Removes a question from the pratest
 * savechanges  Saves the order and grades for questions in the pratest
 *
 * @package    mod_pratest
 * @copyright  1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/pratest/locallib.php');
require_once($CFG->dirroot . '/mod/pratest/addrandomform.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/category_class.php');

// These params are only passed from page request to request while we stay on
// this page otherwise they would go in question_edit_setup.
$scrollpos = optional_param('scrollpos', '', PARAM_INT);

list($thispageurl, $contexts, $cmid, $cm, $pratest, $pagevars) =
        question_edit_setup('editq', '/mod/pratest/edit.php', true);

$defaultcategoryobj = question_make_default_categories($contexts->all());
$defaultcategory = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;

$pratesthasattempts = pratest_has_attempts($pratest->id);

$PAGE->set_url($thispageurl);

// Get the course object and related bits.
$course = $DB->get_record('course', array('id' => $pratest->course), '*', MUST_EXIST);
$pratestobj = new pratest($pratest, $cm, $course);
$structure = $pratestobj->get_structure();

// You need mod/pratest:manage in addition to question capabilities to access this page.
require_capability('mod/pratest:manage', $contexts->lowest());

// Log this visit.
$params = array(
    'courseid' => $course->id,
    'context' => $contexts->lowest(),
    'other' => array(
        'pratestid' => $pratest->id
    )
);
$event = \mod_pratest\event\edit_page_viewed::create($params);
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
    // Re-paginate the pratest.
    $structure->check_can_be_edited();
    $questionsperpage = optional_param('questionsperpage', $pratest->questionsperpage, PARAM_INT);
    pratest_repaginate_questions($pratest->id, $questionsperpage );
    pratest_delete_previews($pratest);
    redirect($afteractionurl);
}

if (($addquestion = optional_param('addquestion', 0, PARAM_INT)) && confirm_sesskey()) {
    // Add a single question to the current pratest.
    $structure->check_can_be_edited();
    pratest_require_question_use($addquestion);
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    pratest_add_pratest_question($addquestion, $pratest, $addonpage);
    pratest_delete_previews($pratest);
    pratest_update_sumgrades($pratest);
    $thispageurl->param('lastchanged', $addquestion);
    redirect($afteractionurl);
}

if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $structure->check_can_be_edited();
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    // Add selected questions to the current pratest.
    $rawdata = (array) data_submitted();
    foreach ($rawdata as $key => $value) { // Parse input for question ids.
        if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
            $key = $matches[1];
            pratest_require_question_use($key);
            pratest_add_pratest_question($key, $pratest, $addonpage);
        }
    }
    pratest_delete_previews($pratest);
    pratest_update_sumgrades($pratest);
    redirect($afteractionurl);
}

if ($addsectionatpage = optional_param('addsectionatpage', false, PARAM_INT)) {
    // Add a section to the pratest.
    $structure->check_can_be_edited();
    $structure->add_section_heading($addsectionatpage);
    pratest_delete_previews($pratest);
    redirect($afteractionurl);
}

if ((optional_param('addrandom', false, PARAM_BOOL)) && confirm_sesskey()) {
    // Add random questions to the pratest.
    $structure->check_can_be_edited();
    $recurse = optional_param('recurse', 0, PARAM_BOOL);
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    $categoryid = required_param('categoryid', PARAM_INT);
    $randomcount = required_param('randomcount', PARAM_INT);
    pratest_add_random_questions($pratest, $addonpage, $categoryid, $randomcount, $recurse);

    pratest_delete_previews($pratest);
    pratest_update_sumgrades($pratest);
    redirect($afteractionurl);
}

if (optional_param('savechanges', false, PARAM_BOOL) && confirm_sesskey()) {

    // If rescaling is required save the new maximum.
    $maxgrade = unformat_float(optional_param('maxgrade', '', PARAM_RAW_TRIMMED), true);
    if (is_float($maxgrade) && $maxgrade >= 0) {
        pratest_set_grade($maxgrade, $pratest);
        pratest_update_all_final_grades($pratest);
        pratest_update_grades($pratest, 0, true);
    }

    redirect($afteractionurl);
}

// Get the question bank view.
$questionbank = new mod_pratest\question\bank\custom_view($contexts, $thispageurl, $course, $cm, $pratest);
$questionbank->set_pratest_has_attempts($pratesthasattempts);
$questionbank->process_actions($thispageurl, $cm);

// End of process commands =====================================================.

$PAGE->set_pagelayout('incourse');
$PAGE->set_pagetype('mod-pratest-edit');

$output = $PAGE->get_renderer('mod_pratest', 'edit');

$PAGE->set_title(get_string('editingpratestx', 'pratest', format_string($pratest->name)));
$PAGE->set_heading($course->fullname);
$node = $PAGE->settingsnav->find('mod_pratest_edit', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}
echo $OUTPUT->header();

// Initialise the JavaScript.
$pratesteditconfig = new stdClass();
$pratesteditconfig->url = $thispageurl->out(true, array('qbanktool' => '0'));
$pratesteditconfig->dialoglisteners = array();
$numberoflisteners = $DB->get_field_sql("
    SELECT COALESCE(MAX(page), 1)
      FROM {pratest_slots}
     WHERE pratestid = ?", array($pratest->id));

for ($pageiter = 1; $pageiter <= $numberoflisteners; $pageiter++) {
    $pratesteditconfig->dialoglisteners[] = 'addrandomdialoglaunch_' . $pageiter;
}

$PAGE->requires->data_for_js('pratest_edit_config', $pratesteditconfig);
$PAGE->requires->js('/question/qengine.js');

// Questions wrapper start.
echo html_writer::start_tag('div', array('class' => 'mod-pratest-edit-content'));

echo $output->edit_page($pratestobj, $structure, $contexts, $thispageurl, $pagevars);

echo '<ul class="menu  align-tr-tr" id="action-menu-2-menu" data-rel="menu-content" aria-labelledby="action-menu-toggle-2" role="menu" data-align="tr-tr" data-constraint=".mod-quiz-edit-content" aria-hidden="false" style="top: -110px;">

<li role="presentation" id="yui_3_17_2_1_1545799467910_546">    <a href="http://localhost/flip-moodle-lc/mod/pratest/edit.php?cmid=38391" class="cm-edit-action questionbank menu-action add-menu" data-header="Add from the question bank at the end" data-action="questionbank" data-addonpage="0" role="menuitem" aria-labelledby="actionmenuaction-8" id="yui_3_17_2_1_1545799467910_545"><img class="icon " alt="from question bank" title="from question bank" src="http://localhost/flip-moodle-lc/theme/image.php?theme=adaptable&amp;component=core&amp;rev=1545655717&amp;image=t%2Fadd"><span class="menu-action-text" id="actionmenuaction-8">from question bank</span></a>
</li> </ul>';

// Questions wrapper end.
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
