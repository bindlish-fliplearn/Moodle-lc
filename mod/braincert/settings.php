<?php
// This file is part of Wiziq - http://www.wiziq.com/
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
 * Defines site settings for the braincert activity
 *
 * @package    mod_braincert
 * @author BrainCert <support@braincert.com>
 * @copyright  BrainCert (https://www.braincert.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('braincert_heading',
      get_string('generalconfig', 'braincert'), get_string('explaingeneralconfig', 'braincert')));

    $settings->add(new admin_setting_configtext('mod_braincert_apikey',
      get_string('apikey', 'braincert'), get_string('configapikey', 'braincert'), '', PARAM_RAW, 50));

    $settings->add(new admin_setting_configtext('mod_braincert_baseurl',
      get_string('baseurl', 'braincert'), get_string('configbaseurl', 'braincert'), 'https://api.braincert.com/v2', PARAM_RAW, 50));
}
