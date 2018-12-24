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
 * Definition of log events for the flipquiz module.
 *
 * @package    mod_flipquiz
 * @category   log
 * @copyright  2010 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'flipquiz', 'action'=>'add', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'update', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'view', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'report', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'attempt', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'submit', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'review', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'editquestions', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'preview', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'start attempt', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'close attempt', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'continue attempt', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'edit override', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'delete override', 'mtable'=>'flipquiz', 'field'=>'name'),
    array('module'=>'flipquiz', 'action'=>'view summary', 'mtable'=>'flipquiz', 'field'=>'name'),
);