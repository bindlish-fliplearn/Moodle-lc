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
 * Tool for quick renaming of many question categories.
 *
 * @package    local_quickrenamequestioncategories
 * @copyright  2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class
 *
 * @package    local_quickrenamequestioncategories
 * @copyright  2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_quickrenamequestioncategories_question_category_renamer {

    /**
     * Rename categories in multiple context.
     *
     * @param array $categorynames
     */
    public function rename_categories($categorynames) {
        foreach ($categorynames as $contextid => $categorynamesarray) {
            $context = context::instance_by_id($contextid);
            require_capability('moodle/question:managecategory', $context);
            $this->rename_categories_in_context($categorynamesarray, $contextid);
        }
    }

    /**
     * Rename categories in one context.
     *
     * @param array $categorynames
     */
    private function rename_categories_in_context($categorynames) {
        global $DB;

        foreach ($categorynames as $categoryid => $newcategoryname) {
            $category = $DB->get_record('question_categories', array('id' => $categoryid), 'id, name', MUST_EXIST);
            $category->name = $newcategoryname;
            $DB->update_record('question_categories', $category);
        }
    }

}