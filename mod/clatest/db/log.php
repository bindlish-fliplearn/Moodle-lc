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
 * Definition of log events for the clatest module.
 *
 * @package    mod_clatest
 * @category   log
 * @copyright  2010 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'clatest', 'action'=>'add', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'update', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'view', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'report', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'attempt', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'submit', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'review', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'editquestions', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'preview', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'start attempt', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'close attempt', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'continue attempt', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'edit override', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'delete override', 'mtable'=>'clatest', 'field'=>'name'),
    array('module'=>'clatest', 'action'=>'view summary', 'mtable'=>'clatest', 'field'=>'name'),
);