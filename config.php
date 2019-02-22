<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'stg_moodle_lc';
$CFG->dbuser    = 'root';
$CFG->dbpass    = 'root';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->customscripts = '/var/www/html/flip-moodleguru/customscript';
$CFG->wwwroot   = 'http://localhost/flip_moodlelc';
$CFG->dataroot  = '/var/www/html/moodledata_lc';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');

define('GURU_ANNOUNCEMENT','GURU_ANNOUNCEMENT');
define('CONTEXT_LEVEL',50);
define('SEND_NOTIFICATION',true);
define('COMMUNICATION_API_URL','http://stgeventapi.fliplearn.com:8084/event/');
define('DOMAIN_NAME','stgmoodlelc.fliplearn.com');
define('BASE_URL','https://stgmoodlelc.fliplearn.com');
define('PQUIZ_URL','https://dev5pquiz.fliplearn.com');


// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
