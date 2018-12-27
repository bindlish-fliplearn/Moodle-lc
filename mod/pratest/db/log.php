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
 * Definition of log events for the pratest module.
 *
 * @package    mod_pratest
 * @category   log
 * @copyright  2010 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'pratest', 'action'=>'add', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'update', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'view', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'report', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'attempt', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'submit', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'review', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'editquestions', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'preview', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'start attempt', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'close attempt', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'continue attempt', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'edit override', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'delete override', 'mtable'=>'pratest', 'field'=>'name'),
    array('module'=>'pratest', 'action'=>'view summary', 'mtable'=>'pratest', 'field'=>'name'),
);