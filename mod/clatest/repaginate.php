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
 * Rest endpoint for ajax editing for paging operations on the clatest structure.
 *
 * @package   mod_clatest
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/clatest/locallib.php');

$clatestid = required_param('clatestid', PARAM_INT);
$slotnumber = required_param('slot', PARAM_INT);
$repagtype = required_param('repag', PARAM_INT);

require_sesskey();
$clatestobj = clatest::create($clatestid);
require_login($clatestobj->get_course(), false, $clatestobj->get_cm());
require_capability('mod/clatest:manage', $clatestobj->get_context());
if (clatest_has_attempts($clatestid)) {
    $reportlink = clatest_attempt_summary_link_to_reports($clatestobj->get_clatest(),
                    $clatestobj->get_cm(), $clatestobj->get_context());
    throw new \moodle_exception('cannoteditafterattempts', 'clatest',
            new moodle_url('/mod/clatest/edit.php', array('cmid' => $clatestobj->get_cmid())), $reportlink);
}

$slotnumber++;
$repage = new \mod_clatest\repaginate($clatestid);
$repage->repaginate_slots($slotnumber, $repagtype);

$structure = $clatestobj->get_structure();
$slots = $structure->refresh_page_numbers_and_update_db();

redirect(new moodle_url('edit.php', array('cmid' => $clatestobj->get_cmid())));
