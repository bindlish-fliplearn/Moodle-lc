<?php

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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    localflipapi
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// We defined the web service functions to install.
$functions = array(
  'local_flipapi_get_flip_user' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'get_flip_user',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'Return fliplearn user details.',
    'type' => 'read',
  ),
  'local_flipapi_flip_user_mapping' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'flip_user_mapping',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'Return fliplearn user mapping status.',
    'type' => 'read',
  ),
  'local_flipapi_get_user_by_uuid' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'get_user_by_uuid',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'Return fliplearn user details by uuid.',
    'type' => 'read',
  ),
  'local_flipapi_get_role_details_by_shortname' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'get_role_details_by_shortname',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'Return role details by shortname.',
    'type' => 'read',
  ),
  'local_calendar_get_action_completed_events_by_timesort' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'get_calendar_action_completed_events_by_timesort',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'Return role details by shortname.',
    'type' => 'read',
    'ajax' => true,
  ),
  'local_flipapi_upadte_completionexpected_by_id' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'update_completionexpected_by_id',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'Bulk update activity completion expected date.',
    'type' => 'update',
  ),
  'local_flipapi_add_update_ptm' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'add_update_ptm',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'Add Update ptm.',
    'type' => 'update',
  ),
   'local_flipapi_guru_vedio_view' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'guru_vedio_view',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'View guru vedio.',
    'type' => 'update',
     ),
  'local_flipapi_get_live_classes' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'get_live_classes',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'Get Live classes for user by class id and user id.',
    'type' => 'read',
  ),
  'local_flipapi_add_reminder_live_class' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'add_reminder_live_class',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'Get Live classes for user by class id and user id.',
    'type' => 'read',
  ),
  'local_flipapi_add_activity_rating' => array(
    'classname' => 'local_flipapi_external',
    'methodname' => 'add_activity_rating',
    'classpath' => 'local/flipapi/externallib.php',
    'description' => 'Get Live classes for user by class id and user id.',
    'type' => 'write',
  )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
//$services = array(
//  'webservice_api' => array(
//    'functions' => array('local_flipapi_hello_world'),
//    'restrictedusers' => 0,
//    'enabled' => 1,
//  )
//);
