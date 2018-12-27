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
 * @package    mod_clatest
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Structure step to restore one clatest activity
 *
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_clatest_activity_structure_step extends restore_questions_activity_structure_step {

    /**
     * @var bool tracks whether the clatest contains at least one section. Before
     * Moodle 2.9 clatest sections did not exist, so if the file being restored
     * did not contain any, we need to create one in {@link after_execute()}.
     */
    protected $sectioncreated = false;

    /**
     * @var bool when restoring old clatestzes (2.8 or before) this records the
     * shufflequestionsoption clatest option which has moved to the clatest_sections table.
     */
    protected $legacyshufflequestionsoption = false;

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $clatest = new restore_path_element('clatest', '/activity/clatest');
        $paths[] = $clatest;

        // A chance for access subplugings to set up their clatest data.
        $this->add_subplugin_structure('clatestaccess', $clatest);

        $paths[] = new restore_path_element('clatest_question_instance',
                '/activity/clatest/question_instances/question_instance');
        $paths[] = new restore_path_element('clatest_slot_tags',
                '/activity/clatest/question_instances/question_instance/tags/tag');
        $paths[] = new restore_path_element('clatest_section', '/activity/clatest/sections/section');
        $paths[] = new restore_path_element('clatest_feedback', '/activity/clatest/feedbacks/feedback');
        $paths[] = new restore_path_element('clatest_override', '/activity/clatest/overrides/override');

        if ($userinfo) {
            $paths[] = new restore_path_element('clatest_grade', '/activity/clatest/grades/grade');

            if ($this->task->get_old_moduleversion() > 2011010100) {
                // Restoring from a version 2.1 dev or later.
                // Process the new-style attempt data.
                $clatestattempt = new restore_path_element('clatest_attempt',
                        '/activity/clatest/attempts/attempt');
                $paths[] = $clatestattempt;

                // Add states and sessions.
                $this->add_question_usages($clatestattempt, $paths);

                // A chance for access subplugings to set up their attempt data.
                $this->add_subplugin_structure('clatestaccess', $clatestattempt);

            } else {
                // Restoring from a version 2.0.x+ or earlier.
                // Upgrade the legacy attempt data.
                $clatestattempt = new restore_path_element('clatest_attempt_legacy',
                        '/activity/clatest/attempts/attempt',
                        true);
                $paths[] = $clatestattempt;
                $this->add_legacy_question_attempt_data($clatestattempt, $paths);
            }
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_clatest($data) {
        global $CFG, $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.

        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        if (property_exists($data, 'questions')) {
            // Needed by {@link process_clatest_attempt_legacy}, in which case it will be present.
            $this->oldclatestlayout = $data->questions;
        }

        // The setting clatest->attempts can come both in data->attempts and
        // data->attempts_number, handle both. MDL-26229.
        if (isset($data->attempts_number)) {
            $data->attempts = $data->attempts_number;
            unset($data->attempts_number);
        }

        // The old optionflags and penaltyscheme from 2.0 need to be mapped to
        // the new preferredbehaviour. See MDL-20636.
        if (!isset($data->preferredbehaviour)) {
            if (empty($data->optionflags)) {
                $data->preferredbehaviour = 'deferredfeedback';
            } else if (empty($data->penaltyscheme)) {
                $data->preferredbehaviour = 'adaptivenopenalty';
            } else {
                $data->preferredbehaviour = 'adaptive';
            }
            unset($data->optionflags);
            unset($data->penaltyscheme);
        }

        // The old review column from 2.0 need to be split into the seven new
        // review columns. See MDL-20636.
        if (isset($data->review)) {
            require_once($CFG->dirroot . '/mod/clatest/locallib.php');

            if (!defined('QUIZ_OLD_IMMEDIATELY')) {
                define('QUIZ_OLD_IMMEDIATELY', 0x3c003f);
                define('QUIZ_OLD_OPEN',        0x3c00fc0);
                define('QUIZ_OLD_CLOSED',      0x3c03f000);

                define('QUIZ_OLD_RESPONSES',        1*0x1041);
                define('QUIZ_OLD_SCORES',           2*0x1041);
                define('QUIZ_OLD_FEEDBACK',         4*0x1041);
                define('QUIZ_OLD_ANSWERS',          8*0x1041);
                define('QUIZ_OLD_SOLUTIONS',       16*0x1041);
                define('QUIZ_OLD_GENERALFEEDBACK', 32*0x1041);
                define('QUIZ_OLD_OVERALLFEEDBACK',  1*0x4440000);
            }

            $oldreview = $data->review;

            $data->reviewattempt =
                    mod_clatest_display_options::DURING |
                    ($oldreview & QUIZ_OLD_IMMEDIATELY & QUIZ_OLD_RESPONSES ?
                            mod_clatest_display_options::IMMEDIATELY_AFTER : 0) |
                    ($oldreview & QUIZ_OLD_OPEN & QUIZ_OLD_RESPONSES ?
                            mod_clatest_display_options::LATER_WHILE_OPEN : 0) |
                    ($oldreview & QUIZ_OLD_CLOSED & QUIZ_OLD_RESPONSES ?
                            mod_clatest_display_options::AFTER_CLOSE : 0);

            $data->reviewcorrectness =
                    mod_clatest_display_options::DURING |
                    ($oldreview & QUIZ_OLD_IMMEDIATELY & QUIZ_OLD_SCORES ?
                            mod_clatest_display_options::IMMEDIATELY_AFTER : 0) |
                    ($oldreview & QUIZ_OLD_OPEN & QUIZ_OLD_SCORES ?
                            mod_clatest_display_options::LATER_WHILE_OPEN : 0) |
                    ($oldreview & QUIZ_OLD_CLOSED & QUIZ_OLD_SCORES ?
                            mod_clatest_display_options::AFTER_CLOSE : 0);

            $data->reviewmarks =
                    mod_clatest_display_options::DURING |
                    ($oldreview & QUIZ_OLD_IMMEDIATELY & QUIZ_OLD_SCORES ?
                            mod_clatest_display_options::IMMEDIATELY_AFTER : 0) |
                    ($oldreview & QUIZ_OLD_OPEN & QUIZ_OLD_SCORES ?
                            mod_clatest_display_options::LATER_WHILE_OPEN : 0) |
                    ($oldreview & QUIZ_OLD_CLOSED & QUIZ_OLD_SCORES ?
                            mod_clatest_display_options::AFTER_CLOSE : 0);

            $data->reviewspecificfeedback =
                    ($oldreview & QUIZ_OLD_IMMEDIATELY & QUIZ_OLD_FEEDBACK ?
                            mod_clatest_display_options::DURING : 0) |
                    ($oldreview & QUIZ_OLD_IMMEDIATELY & QUIZ_OLD_FEEDBACK ?
                            mod_clatest_display_options::IMMEDIATELY_AFTER : 0) |
                    ($oldreview & QUIZ_OLD_OPEN & QUIZ_OLD_FEEDBACK ?
                            mod_clatest_display_options::LATER_WHILE_OPEN : 0) |
                    ($oldreview & QUIZ_OLD_CLOSED & QUIZ_OLD_FEEDBACK ?
                            mod_clatest_display_options::AFTER_CLOSE : 0);

            $data->reviewgeneralfeedback =
                    ($oldreview & QUIZ_OLD_IMMEDIATELY & QUIZ_OLD_GENERALFEEDBACK ?
                            mod_clatest_display_options::DURING : 0) |
                    ($oldreview & QUIZ_OLD_IMMEDIATELY & QUIZ_OLD_GENERALFEEDBACK ?
                            mod_clatest_display_options::IMMEDIATELY_AFTER : 0) |
                    ($oldreview & QUIZ_OLD_OPEN & QUIZ_OLD_GENERALFEEDBACK ?
                            mod_clatest_display_options::LATER_WHILE_OPEN : 0) |
                    ($oldreview & QUIZ_OLD_CLOSED & QUIZ_OLD_GENERALFEEDBACK ?
                            mod_clatest_display_options::AFTER_CLOSE : 0);

            $data->reviewrightanswer =
                    ($oldreview & QUIZ_OLD_IMMEDIATELY & QUIZ_OLD_ANSWERS ?
                            mod_clatest_display_options::DURING : 0) |
                    ($oldreview & QUIZ_OLD_IMMEDIATELY & QUIZ_OLD_ANSWERS ?
                            mod_clatest_display_options::IMMEDIATELY_AFTER : 0) |
                    ($oldreview & QUIZ_OLD_OPEN & QUIZ_OLD_ANSWERS ?
                            mod_clatest_display_options::LATER_WHILE_OPEN : 0) |
                    ($oldreview & QUIZ_OLD_CLOSED & QUIZ_OLD_ANSWERS ?
                            mod_clatest_display_options::AFTER_CLOSE : 0);

            $data->reviewoverallfeedback =
                    0 |
                    ($oldreview & QUIZ_OLD_IMMEDIATELY & QUIZ_OLD_OVERALLFEEDBACK ?
                            mod_clatest_display_options::IMMEDIATELY_AFTER : 0) |
                    ($oldreview & QUIZ_OLD_OPEN & QUIZ_OLD_OVERALLFEEDBACK ?
                            mod_clatest_display_options::LATER_WHILE_OPEN : 0) |
                    ($oldreview & QUIZ_OLD_CLOSED & QUIZ_OLD_OVERALLFEEDBACK ?
                            mod_clatest_display_options::AFTER_CLOSE : 0);
        }

        // The old popup column from from <= 2.1 need to be mapped to
        // the new browsersecurity. See MDL-29627.
        if (!isset($data->browsersecurity)) {
            if (empty($data->popup)) {
                $data->browsersecurity = '-';
            } else if ($data->popup == 1) {
                $data->browsersecurity = 'securewindow';
            } else if ($data->popup == 2) {
                $data->browsersecurity = 'safebrowser';
            } else {
                $data->preferredbehaviour = '-';
            }
            unset($data->popup);
        }

        if (!isset($data->overduehandling)) {
            $data->overduehandling = get_config('clatest', 'overduehandling');
        }

        // Old shufflequestions setting is now stored in clatest sections,
        // so save it here if necessary so it is available when we need it.
        $this->legacyshufflequestionsoption = !empty($data->shufflequestions);

        // Insert the clatest record.
        $newitemid = $DB->insert_record('clatest', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_clatest_question_instance($data) {
        global $CFG, $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Backwards compatibility for old field names (MDL-43670).
        if (!isset($data->questionid) && isset($data->question)) {
            $data->questionid = $data->question;
        }
        if (!isset($data->maxmark) && isset($data->grade)) {
            $data->maxmark = $data->grade;
        }

        if (!property_exists($data, 'slot')) {
            $page = 1;
            $slot = 1;
            foreach (explode(',', $this->oldclatestlayout) as $item) {
                if ($item == 0) {
                    $page += 1;
                    continue;
                }
                if ($item == $data->questionid) {
                    $data->slot = $slot;
                    $data->page = $page;
                    break;
                }
                $slot += 1;
            }
        }

        if (!property_exists($data, 'slot')) {
            // There was a question_instance in the backup file for a question
            // that was not acutally in the clatest. Drop it.
            $this->log('question ' . $data->questionid . ' was associated with clatest ' .
                    $this->get_new_parentid('clatest') . ' but not actually used. ' .
                    'The instance has been ignored.', backup::LOG_INFO);
            return;
        }

        $data->clatestid = $this->get_new_parentid('clatest');
        $questionmapping = $this->get_mapping('question', $data->questionid);
        $data->questionid = $questionmapping ? $questionmapping->newitemid : false;

        if (isset($data->questioncategoryid)) {
            $data->questioncategoryid = $this->get_mappingid('question_category', $data->questioncategoryid);
        } else if ($questionmapping && $questionmapping->info->qtype == 'random') {
            // Backward compatibility for backups created using Moodle 3.4 or earlier.
            $data->questioncategoryid = $this->get_mappingid('question_category', $questionmapping->parentitemid);
            $data->includingsubcategories = $questionmapping->info->questiontext ? 1 : 0;
        }

        $newitemid = $DB->insert_record('clatest_slots', $data);
        // Add mapping, restore of slot tags (for random questions) need it.
        $this->set_mapping('clatest_question_instance', $oldid, $newitemid);
    }

    /**
     * Process a clatest_slot_tags restore
     *
     * @param stdClass|array $data The clatest_slot_tags data
     */
    protected function process_clatest_slot_tags($data) {
        global $DB;

        $data = (object)$data;

        $data->slotid = $this->get_new_parentid('clatest_question_instance');
        if ($this->task->is_samesite() && $tag = core_tag_tag::get($data->tagid, 'id, name')) {
            $data->tagname = $tag->name;
        } else if ($tag = core_tag_tag::get_by_name(0, $data->tagname, 'id, name')) {
            $data->tagid = $tag->id;
        } else {
            $data->tagid = null;
            $data->tagname = $tag->name;
        }

        $DB->insert_record('clatest_slot_tags', $data);
    }

    protected function process_clatest_section($data) {
        global $DB;

        $data = (object) $data;
        $data->clatestid = $this->get_new_parentid('clatest');
        $newitemid = $DB->insert_record('clatest_sections', $data);
        $this->sectioncreated = true;
    }

    protected function process_clatest_feedback($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->clatestid = $this->get_new_parentid('clatest');

        $newitemid = $DB->insert_record('clatest_feedback', $data);
        $this->set_mapping('clatest_feedback', $oldid, $newitemid, true); // Has related files.
    }

    protected function process_clatest_override($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Based on userinfo, we'll restore user overides or no.
        $userinfo = $this->get_setting_value('userinfo');

        // Skip user overrides if we are not restoring userinfo.
        if (!$userinfo && !is_null($data->userid)) {
            return;
        }

        $data->clatest = $this->get_new_parentid('clatest');

        if ($data->userid !== null) {
            $data->userid = $this->get_mappingid('user', $data->userid);
        }

        if ($data->groupid !== null) {
            $data->groupid = $this->get_mappingid('group', $data->groupid);
        }

        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        $newitemid = $DB->insert_record('clatest_overrides', $data);

        // Add mapping, restore of logs needs it.
        $this->set_mapping('clatest_override', $oldid, $newitemid);
    }

    protected function process_clatest_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->clatest = $this->get_new_parentid('clatest');

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->grade = $data->gradeval;

        $DB->insert_record('clatest_grades', $data);
    }

    protected function process_clatest_attempt($data) {
        $data = (object)$data;

        $data->clatest = $this->get_new_parentid('clatest');
        $data->attempt = $data->attemptnum;

        $data->userid = $this->get_mappingid('user', $data->userid);

        if (!empty($data->timecheckstate)) {
            $data->timecheckstate = $this->apply_date_offset($data->timecheckstate);
        } else {
            $data->timecheckstate = 0;
        }

        // Deals with up-grading pre-2.3 back-ups to 2.3+.
        if (!isset($data->state)) {
            if ($data->timefinish > 0) {
                $data->state = 'finished';
            } else {
                $data->state = 'inprogress';
            }
        }

        // The data is actually inserted into the database later in inform_new_usage_id.
        $this->currentclatestattempt = clone($data);
    }

    protected function process_clatest_attempt_legacy($data) {
        global $DB;

        $this->process_clatest_attempt($data);

        $clatest = $DB->get_record('clatest', array('id' => $this->get_new_parentid('clatest')));
        $clatest->oldquestions = $this->oldclatestlayout;
        $this->process_legacy_clatest_attempt_data($data, $clatest);
    }

    protected function inform_new_usage_id($newusageid) {
        global $DB;

        $data = $this->currentclatestattempt;

        $oldid = $data->id;
        $data->uniqueid = $newusageid;

        $newitemid = $DB->insert_record('clatest_attempts', $data);

        // Save clatest_attempt->id mapping, because logs use it.
        $this->set_mapping('clatest_attempt', $oldid, $newitemid, false);
    }

    protected function after_execute() {
        global $DB;

        parent::after_execute();
        // Add clatest related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_clatest', 'intro', null);
        // Add feedback related files, matching by itemname = 'clatest_feedback'.
        $this->add_related_files('mod_clatest', 'feedback', 'clatest_feedback');

        if (!$this->sectioncreated) {
            $DB->insert_record('clatest_sections', array(
                    'clatestid' => $this->get_new_parentid('clatest'),
                    'firstslot' => 1, 'heading' => '',
                    'shufflequestions' => $this->legacyshufflequestionsoption));
        }
    }
}
