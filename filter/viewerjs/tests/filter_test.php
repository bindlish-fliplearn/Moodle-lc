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
 * Test cases
 *
 * @package    filter_viewerjs
 * @copyright  2015 Abir Viqar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/viewerjs/filter.php');

class filter_viewerjs_filter_testcase extends advanced_testcase {

    public function test_filter_local_file() {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        filter_manager::reset_caches();
        filter_set_global_state('viewerjs', TEXTFILTER_ON);

        $course1 = $this->getDataGenerator()->create_course();
        $coursecontext1 = context_course::instance($course1->id);

        $filename = 'pluginfile.php/312/mod_page/content/7/frog.pdf';
        $html = '<a href= "'.$CFG->wwwroot.'/'.$filename.'">Link</a>';
        $transformedUrl =  $CFG->wwwroot.'/filter/viewerjs/lib/viewerjs#../../../../'.$filename;
        $this->assertContains($transformedUrl, format_text($html, FORMAT_HTML, array('context' => $coursecontext1)), 'transformed local pdf');
    }

    public function test_filter_unsupported_local_file() {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        filter_manager::reset_caches();
        filter_set_global_state('viewerjs', TEXTFILTER_ON);

        $course1 = $this->getDataGenerator()->create_course();
        $coursecontext1 = context_course::instance($course1->id);

        $filename = 'pluginfile.php/312/mod_page/content/7/frog.ppt';
        $html = '<a href= "'.$CFG->wwwroot.'/'.$filename.'">Link</a>';
        $transformedUrl =  $CFG->wwwroot.'/'.$filename;
        $this->assertContains($transformedUrl, format_text($html, FORMAT_HTML, array('context' => $coursecontext1)), 'did not transform unsupported local ppt');
    }

    public function test_filter_external_file() {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        filter_manager::reset_caches();
        filter_set_global_state('viewerjs', TEXTFILTER_ON);

        $course1 = $this->getDataGenerator()->create_course();
        $coursecontext1 = context_course::instance($course1->id);

        $url = 'http://www.example.org/frog.ppt';
        $html = '<a href= "'.$url.'">Link</a>';
        $this->assertContains($url, format_text($html, FORMAT_HTML, array('context' => $coursecontext1)), 'did not transform external url');

        $url = 'http://www.example.org/frog.pdf';
        $html = '<a href= "'.$url.'">Link</a>';
        $this->assertContains($url, format_text($html, FORMAT_HTML, array('context' => $coursecontext1)), 'did not transform external url even though it had a supported extension');
    }
}
