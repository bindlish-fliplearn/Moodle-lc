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
 * Definition of log events for the homework module.
 *
 * @package    mod_homework
 * @category   log
 * @copyright  2010 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'homework', 'action'=>'add', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'update', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'view', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'report', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'attempt', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'submit', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'review', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'editquestions', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'preview', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'start attempt', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'close attempt', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'continue attempt', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'edit override', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'delete override', 'mtable'=>'homework', 'field'=>'name'),
    array('module'=>'homework', 'action'=>'view summary', 'mtable'=>'homework', 'field'=>'name'),
);