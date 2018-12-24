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
 * Library code used by flipquiz cron.
 *
 * @package   mod_flipquiz
 * @copyright 2012 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/flipquiz/locallib.php');


/**
 * This class holds all the code for automatically updating all attempts that have
 * gone over their time limit.
 *
 * @copyright 2012 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_flipquiz_overdue_attempt_updater {

    /**
     * Do the processing required.
     * @param int $timenow the time to consider as 'now' during the processing.
     * @param int $processto only process attempt with timecheckstate longer ago than this.
     * @return array with two elements, the number of attempt considered, and how many different flipquizzes that was.
     */
    public function update_overdue_attempts($timenow, $processto) {
        global $DB;

        $attemptstoprocess = $this->get_list_of_overdue_attempts($processto);

        $course = null;
        $flipquiz = null;
        $cm = null;

        $count = 0;
        $flipquizcount = 0;
        foreach ($attemptstoprocess as $attempt) {
            try {

                // If we have moved on to a different flipquiz, fetch the new data.
                if (!$flipquiz || $attempt->flipquiz != $flipquiz->id) {
                    $flipquiz = $DB->get_record('flipquiz', array('id' => $attempt->flipquiz), '*', MUST_EXIST);
                    $cm = get_coursemodule_from_instance('flipquiz', $attempt->flipquiz);
                    $flipquizcount += 1;
                }

                // If we have moved on to a different course, fetch the new data.
                if (!$course || $course->id != $flipquiz->course) {
                    $course = $DB->get_record('course', array('id' => $flipquiz->course), '*', MUST_EXIST);
                }

                // Make a specialised version of the flipquiz settings, with the relevant overrides.
                $flipquizforuser = clone($flipquiz);
                $flipquizforuser->timeclose = $attempt->usertimeclose;
                $flipquizforuser->timelimit = $attempt->usertimelimit;

                // Trigger any transitions that are required.
                $attemptobj = new flipquiz_attempt($attempt, $flipquizforuser, $cm, $course);
                $attemptobj->handle_if_time_expired($timenow, false);
                $count += 1;

            } catch (moodle_exception $e) {
                // If an error occurs while processing one attempt, don't let that kill cron.
                mtrace("Error while processing attempt {$attempt->id} at {$attempt->flipquiz} flipquiz:");
                mtrace($e->getMessage());
                mtrace($e->getTraceAsString());
                // Close down any currently open transactions, otherwise one error
                // will stop following DB changes from being committed.
                $DB->force_transaction_rollback();
            }
        }

        $attemptstoprocess->close();
        return array($count, $flipquizcount);
    }

    /**
     * @return moodle_recordset of flipquiz_attempts that need to be processed because time has
     *     passed. The array is sorted by courseid then flipquizid.
     */
    public function get_list_of_overdue_attempts($processto) {
        global $DB;


        // SQL to compute timeclose and timelimit for each attempt:
        $flipquizausersql = flipquiz_get_attempt_usertime_sql(
                "iflipquiza.state IN ('inprogress', 'overdue') AND iflipquiza.timecheckstate <= :iprocessto");

        // This query should have all the flipquiz_attempts columns.
        return $DB->get_recordset_sql("
         SELECT flipquiza.*,
                flipquizauser.usertimeclose,
                flipquizauser.usertimelimit

           FROM {flipquiz_attempts} flipquiza
           JOIN {flipquiz} flipquiz ON flipquiz.id = flipquiza.flipquiz
           JOIN ( $flipquizausersql ) flipquizauser ON flipquizauser.id = flipquiza.id

          WHERE flipquiza.state IN ('inprogress', 'overdue')
            AND flipquiza.timecheckstate <= :processto
       ORDER BY flipquiz.course, flipquiza.flipquiz",

                array('processto' => $processto, 'iprocessto' => $processto));
    }
}
