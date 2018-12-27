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
 * Library code used by pratest cron.
 *
 * @package   mod_pratest
 * @copyright 2012 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pratest/locallib.php');


/**
 * This class holds all the code for automatically updating all attempts that have
 * gone over their time limit.
 *
 * @copyright 2012 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pratest_overdue_attempt_updater {

    /**
     * Do the processing required.
     * @param int $timenow the time to consider as 'now' during the processing.
     * @param int $processto only process attempt with timecheckstate longer ago than this.
     * @return array with two elements, the number of attempt considered, and how many different pratestzes that was.
     */
    public function update_overdue_attempts($timenow, $processto) {
        global $DB;

        $attemptstoprocess = $this->get_list_of_overdue_attempts($processto);

        $course = null;
        $pratest = null;
        $cm = null;

        $count = 0;
        $pratestcount = 0;
        foreach ($attemptstoprocess as $attempt) {
            try {

                // If we have moved on to a different pratest, fetch the new data.
                if (!$pratest || $attempt->pratest != $pratest->id) {
                    $pratest = $DB->get_record('pratest', array('id' => $attempt->pratest), '*', MUST_EXIST);
                    $cm = get_coursemodule_from_instance('pratest', $attempt->pratest);
                    $pratestcount += 1;
                }

                // If we have moved on to a different course, fetch the new data.
                if (!$course || $course->id != $pratest->course) {
                    $course = $DB->get_record('course', array('id' => $pratest->course), '*', MUST_EXIST);
                }

                // Make a specialised version of the pratest settings, with the relevant overrides.
                $pratestforuser = clone($pratest);
                $pratestforuser->timeclose = $attempt->usertimeclose;
                $pratestforuser->timelimit = $attempt->usertimelimit;

                // Trigger any transitions that are required.
                $attemptobj = new pratest_attempt($attempt, $pratestforuser, $cm, $course);
                $attemptobj->handle_if_time_expired($timenow, false);
                $count += 1;

            } catch (moodle_exception $e) {
                // If an error occurs while processing one attempt, don't let that kill cron.
                mtrace("Error while processing attempt {$attempt->id} at {$attempt->pratest} pratest:");
                mtrace($e->getMessage());
                mtrace($e->getTraceAsString());
                // Close down any currently open transactions, otherwise one error
                // will stop following DB changes from being committed.
                $DB->force_transaction_rollback();
            }
        }

        $attemptstoprocess->close();
        return array($count, $pratestcount);
    }

    /**
     * @return moodle_recordset of pratest_attempts that need to be processed because time has
     *     passed. The array is sorted by courseid then pratestid.
     */
    public function get_list_of_overdue_attempts($processto) {
        global $DB;


        // SQL to compute timeclose and timelimit for each attempt:
        $pratestausersql = pratest_get_attempt_usertime_sql(
                "ipratesta.state IN ('inprogress', 'overdue') AND ipratesta.timecheckstate <= :iprocessto");

        // This query should have all the pratest_attempts columns.
        return $DB->get_recordset_sql("
         SELECT pratesta.*,
                pratestauser.usertimeclose,
                pratestauser.usertimelimit

           FROM {pratest_attempts} pratesta
           JOIN {pratest} pratest ON pratest.id = pratesta.pratest
           JOIN ( $pratestausersql ) pratestauser ON pratestauser.id = pratesta.id

          WHERE pratesta.state IN ('inprogress', 'overdue')
            AND pratesta.timecheckstate <= :processto
       ORDER BY pratest.course, pratesta.pratest",

                array('processto' => $processto, 'iprocessto' => $processto));
    }
}
