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
 * Quiz attempt overdue handling tests
 *
 * @package    mod_homework
 * @category   phpunit
 * @copyright  2012 Matt Petro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/group/lib.php');

/**
 * Unit tests for homework attempt overdue handling
 *
 * @package    mod_homework
 * @category   phpunit
 * @copyright  2012 Matt Petro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_homework_attempt_overdue_testcase extends advanced_testcase {
    /**
     * Test the functions homework_update_open_attempts() and get_list_of_overdue_attempts()
     */
    public function test_bulk_update_functions() {
        global $DB,$CFG;

        require_once($CFG->dirroot.'/mod/homework/cronlib.php');

        $this->resetAfterTest();

        $this->setAdminUser();

        // Setup course, user and groups

        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);
        $this->assertTrue(enrol_try_internal_enrol($course->id, $user1->id, $studentrole->id));
        $group1 = $this->getDataGenerator()->create_group(array('courseid'=>$course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid'=>$course->id));
        $group3 = $this->getDataGenerator()->create_group(array('courseid'=>$course->id));
        $this->assertTrue(groups_add_member($group1, $user1));
        $this->assertTrue(groups_add_member($group2, $user1));

        $uniqueid = 0;
        $usertimes = array();

        $homework_generator = $this->getDataGenerator()->get_plugin_generator('mod_homework');

        // Basic homework settings

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1200, 'timelimit'=>600, 'message'=>'Test1A');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>1800));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1200, 'timelimit'=>1800, 'message'=>'Test1B');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>0));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1200, 'timelimit'=>0, 'message'=>'Test1C');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>0, 'timelimit'=>600));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>0, 'timelimit'=>600, 'message'=>'Test1D');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>0, 'timelimit'=>0));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>0, 'timelimit'=>0, 'message'=>'Test1E');

        // Group overrides

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>0));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>null));
        $usertimes[$attemptid] = array('timeclose'=>1300, 'timelimit'=>0, 'message'=>'Test2A');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>0));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1100, 'timelimit'=>null));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1100, 'timelimit'=>0, 'message'=>'Test2B');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>0, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>null, 'timelimit'=>700));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>0, 'timelimit'=>700, 'message'=>'Test2C');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>0, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>null, 'timelimit'=>500));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>0, 'timelimit'=>500, 'message'=>'Test2D');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>0, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>null, 'timelimit'=>0));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>0, 'timelimit'=>0, '', 'message'=>'Test2E');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>500));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1300, 'timelimit'=>500, '', 'message'=>'Test2F');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1300, 'timelimit'=>500, '', 'message'=>'Test2G');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group3->id, 'timeclose'=>1300, 'timelimit'=>500)); // user not in group
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1200, 'timelimit'=>600, '', 'message'=>'Test2H');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1200, 'timelimit'=>600, '', 'message'=>'Test2I');

        // Multiple group overrides

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>501));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group2->id, 'timeclose'=>1301, 'timelimit'=>500));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1301, 'timelimit'=>501, '', 'message'=>'Test3A');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1301, 'timelimit'=>501, '', 'message'=>'Test3B');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1301, 'timelimit'=>500));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group2->id, 'timeclose'=>1300, 'timelimit'=>501));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1301, 'timelimit'=>501, '', 'message'=>'Test3C');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1301, 'timelimit'=>501, '', 'message'=>'Test3D');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1301, 'timelimit'=>500));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group2->id, 'timeclose'=>1300, 'timelimit'=>501));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group3->id, 'timeclose'=>1500, 'timelimit'=>1000)); // user not in group
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1301, 'timelimit'=>501, '', 'message'=>'Test3E');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1301, 'timelimit'=>501, '', 'message'=>'Test3F');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>500));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group2->id, 'timeclose'=>null, 'timelimit'=>501));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1300, 'timelimit'=>501, '', 'message'=>'Test3G');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1300, 'timelimit'=>501, '', 'message'=>'Test3H');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>500));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group2->id, 'timeclose'=>1301, 'timelimit'=>null));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1301, 'timelimit'=>500, '', 'message'=>'Test3I');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1301, 'timelimit'=>500, '', 'message'=>'Test3J');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>500));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group2->id, 'timeclose'=>1301, 'timelimit'=>0));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1301, 'timelimit'=>0, '', 'message'=>'Test3K');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1301, 'timelimit'=>0, '', 'message'=>'Test3L');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>500));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group2->id, 'timeclose'=>0, 'timelimit'=>501));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>0, 'timelimit'=>501, '', 'message'=>'Test3M');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>0, 'timelimit'=>501, '', 'message'=>'Test3N');

        // User overrides

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>700));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'userid'=>$user1->id, 'timeclose'=>1201, 'timelimit'=>601));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1201, 'timelimit'=>601, '', 'message'=>'Test4A');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1201, 'timelimit'=>601, '', 'message'=>'Test4B');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>700));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'userid'=>$user1->id, 'timeclose'=>0, 'timelimit'=>601));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>0, 'timelimit'=>601, '', 'message'=>'Test4C');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>0, 'timelimit'=>601, '', 'message'=>'Test4D');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>700));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'userid'=>$user1->id, 'timeclose'=>1201, 'timelimit'=>0));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1201, 'timelimit'=>0, '', 'message'=>'Test4E');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1201, 'timelimit'=>0, '', 'message'=>'Test4F');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>700));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'userid'=>$user1->id, 'timeclose'=>null, 'timelimit'=>601));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1300, 'timelimit'=>601, '', 'message'=>'Test4G');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1300, 'timelimit'=>601, '', 'message'=>'Test4H');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>null, 'timelimit'=>700));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'userid'=>$user1->id, 'timeclose'=>null, 'timelimit'=>601));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1200, 'timelimit'=>601, '', 'message'=>'Test4I');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1200, 'timelimit'=>601, '', 'message'=>'Test4J');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>700));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'userid'=>$user1->id, 'timeclose'=>1201, 'timelimit'=>null));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1201, 'timelimit'=>700, '', 'message'=>'Test4K');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1201, 'timelimit'=>700, '', 'message'=>'Test4L');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>null));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'userid'=>$user1->id, 'timeclose'=>1201, 'timelimit'=>null));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1201, 'timelimit'=>600, '', 'message'=>'Test4M');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1201, 'timelimit'=>600, '', 'message'=>'Test4N');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>700));
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'userid'=>0, 'timeclose'=>1201, 'timelimit'=>601)); // not user
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1300, 'timelimit'=>700, '', 'message'=>'Test4O');
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>1000, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++, 'attempt'=>1));
        $usertimes[$attemptid] = array('timeclose'=>1300, 'timelimit'=>700, '', 'message'=>'Test4P');

        // Attempt state overdue

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>600, 'overduehandling'=>'graceperiod', 'graceperiod'=>250));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'overdue', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>1200, 'timelimit'=>600, '', 'message'=>'Test5A');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>0, 'timelimit'=>600, 'overduehandling'=>'graceperiod', 'graceperiod'=>250));
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'overdue', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));
        $usertimes[$attemptid] = array('timeclose'=>0, 'timelimit'=>600, '', 'message'=>'Test5B');

        //
        // Test homework_update_open_attempts()
        //

        homework_update_open_attempts(array('courseid'=>$course->id));
        foreach ($usertimes as $attemptid=>$times) {
            $attempt = $DB->get_record('homework_attempts', array('id'=>$attemptid));
            $this->assertTrue(false !== $attempt, $times['message']);

            if ($attempt->state == 'overdue') {
                $graceperiod = $DB->get_field('homework', 'graceperiod', array('id'=>$attempt->homework));
            } else {
                $graceperiod = 0;
            }
            if ($times['timeclose'] > 0 and $times['timelimit'] > 0) {
                $this->assertEquals(min($times['timeclose'], $attempt->timestart + $times['timelimit']) + $graceperiod, $attempt->timecheckstate, $times['message']);
            } else if ($times['timeclose'] > 0) {
                $this->assertEquals($times['timeclose'] + $graceperiod, $attempt->timecheckstate <= $times['timeclose'], $times['message']);
            } else if ($times['timelimit'] > 0) {
                $this->assertEquals($attempt->timestart + $times['timelimit'] + $graceperiod, $attempt->timecheckstate, $times['message']);
            } else {
                $this->assertNull($attempt->timecheckstate, $times['message']);
            }
        }

        //
        // Test get_list_of_overdue_attempts()
        //

        $overduehander = new mod_homework_overdue_attempt_updater();

        $attempts = $overduehander->get_list_of_overdue_attempts(100000); // way in the future
        $count = 0;
        foreach ($attempts as $attempt) {
            $this->assertTrue(isset($usertimes[$attempt->id]));
            $times = $usertimes[$attempt->id];
            $this->assertEquals($times['timeclose'], $attempt->usertimeclose, $times['message']);
            $this->assertEquals($times['timelimit'], $attempt->usertimelimit, $times['message']);
            $count++;

        }
        $attempts->close();
        $this->assertEquals($DB->count_records_select('homework_attempts', 'timecheckstate IS NOT NULL'), $count);

        $attempts = $overduehander->get_list_of_overdue_attempts(0); // before all attempts
        $count = 0;
        foreach ($attempts as $attempt) {
            $count++;
        }
        $attempts->close();
        $this->assertEquals(0, $count);

    }

    /**
     * Test the group event handlers
     */
    public function test_group_event_handlers() {
        global $DB,$CFG;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Setup course, user and groups

        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);
        $this->assertTrue(enrol_try_internal_enrol($course->id, $user1->id, $studentrole->id));
        $group1 = $this->getDataGenerator()->create_group(array('courseid'=>$course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid'=>$course->id));
        $this->assertTrue(groups_add_member($group1, $user1));
        $this->assertTrue(groups_add_member($group2, $user1));

        $uniqueid = 0;

        $homework_generator = $this->getDataGenerator()->get_plugin_generator('mod_homework');

        $homework = $homework_generator->create_instance(array('course'=>$course->id, 'timeclose'=>1200, 'timelimit'=>0));

        // add a group1 override
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group1->id, 'timeclose'=>1300, 'timelimit'=>null));

        // add an attempt
        $attemptid = $DB->insert_record('homework_attempts', array('homework'=>$homework->id, 'userid'=>$user1->id, 'state'=>'inprogress', 'timestart'=>100, 'timecheckstate'=>0, 'layout'=>'', 'uniqueid'=>$uniqueid++));

        // update timecheckstate
        homework_update_open_attempts(array('homeworkid'=>$homework->id));
        $this->assertEquals(1300, $DB->get_field('homework_attempts', 'timecheckstate', array('id'=>$attemptid)));

        // remove from group
        $this->assertTrue(groups_remove_member($group1, $user1));
        $this->assertEquals(1200, $DB->get_field('homework_attempts', 'timecheckstate', array('id'=>$attemptid)));

        // add back to group
        $this->assertTrue(groups_add_member($group1, $user1));
        $this->assertEquals(1300, $DB->get_field('homework_attempts', 'timecheckstate', array('id'=>$attemptid)));

        // delete group
        groups_delete_group($group1);
        $this->assertEquals(1200, $DB->get_field('homework_attempts', 'timecheckstate', array('id'=>$attemptid)));
        $this->assertEquals(0, $DB->count_records('homework_overrides', array('homework'=>$homework->id)));

        // add a group2 override
        $DB->insert_record('homework_overrides', array('homework'=>$homework->id, 'groupid'=>$group2->id, 'timeclose'=>1400, 'timelimit'=>null));
        homework_update_open_attempts(array('homeworkid'=>$homework->id));
        $this->assertEquals(1400, $DB->get_field('homework_attempts', 'timecheckstate', array('id'=>$attemptid)));

        // delete user1 from all groups
        groups_delete_group_members($course->id, $user1->id);
        $this->assertEquals(1200, $DB->get_field('homework_attempts', 'timecheckstate', array('id'=>$attemptid)));

        // add back to group2
        $this->assertTrue(groups_add_member($group2, $user1));
        $this->assertEquals(1400, $DB->get_field('homework_attempts', 'timecheckstate', array('id'=>$attemptid)));

        // delete everyone from all groups
        groups_delete_group_members($course->id);
        $this->assertEquals(1200, $DB->get_field('homework_attempts', 'timecheckstate', array('id'=>$attemptid)));
    }

    /**
     * Test the functions homework_create_attempt_handling_errors
     */
    public function test_homework_create_attempt_handling_errors() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Make a homework.
        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $homeworkgenerator = $this->getDataGenerator()->get_plugin_generator('mod_homework');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $homework = $homeworkgenerator->create_instance(array('course' => $course->id, 'questionsperpage' => 0, 'grade' => 100.0,
            'sumgrades' => 2));
        // Create questions.
        $cat = $questiongenerator->create_question_category();
        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        // Add them to the homework.
        homework_add_homework_question($saq->id, $homework);
        homework_add_homework_question($numq->id, $homework);
        $homeworkobj = homework::create($homework->id, $user1->id);
        $quba = question_engine::make_questions_usage_by_activity('mod_homework', $homeworkobj->get_context());
        $quba->set_preferred_behaviour($homeworkobj->get_homework()->preferredbehaviour);
        $timenow = time();
        // Create an attempt.
        $attempt = homework_create_attempt($homeworkobj, 1, false, $timenow, false, $user1->id);
        homework_start_new_attempt($homeworkobj, $quba, $attempt, 1, $timenow);
        homework_attempt_save_started($homeworkobj, $quba, $attempt);
        $result = homework_create_attempt_handling_errors($attempt->id, $homework->cmid);
        $this->assertEquals($result->get_attemptid(), $attempt->id);
        try {
            $result = homework_create_attempt_handling_errors($attempt->id, 9999);
            $this->fail('Exception expected due to invalid course module id.');
        } catch (moodle_exception $e) {
            $this->assertEquals('invalidcoursemodule', $e->errorcode);
        }
        try {
            homework_create_attempt_handling_errors(9999, $result->get_cmid());
            $this->fail('Exception expected due to homework content change.');
        } catch (moodle_exception $e) {
            $this->assertEquals('attempterrorcontentchange', $e->errorcode);
        }
        try {
            homework_create_attempt_handling_errors(9999);
            $this->fail('Exception expected due to invalid homework attempt id.');
        } catch (moodle_exception $e) {
            $this->assertEquals('attempterrorinvalid', $e->errorcode);
        }
        // Set up as normal user without permission to view preview.
        $this->setUser($student->id);
        try {
            homework_create_attempt_handling_errors(9999, $result->get_cmid());
            $this->fail('Exception expected due to homework content change for user without permission.');
        } catch (moodle_exception $e) {
            $this->assertEquals('attempterrorcontentchangeforuser', $e->errorcode);
        }
        try {
            homework_create_attempt_handling_errors($attempt->id, 9999);
            $this->fail('Exception expected due to invalid course module id for user without permission.');
        } catch (moodle_exception $e) {
            $this->assertEquals('invalidcoursemodule', $e->errorcode);
        }
    }
}
