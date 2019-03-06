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

//$CFG->dbhost    = 'stgumsdb.fliplearn.com';
//$CFG->dbname    = 'stg_moodle_lc';
//$CFG->dbuser    = 'root';
//$CFG->dbpass    = 'flip@159$$';

$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->customscripts = '/var/www/html/flip-moodleguru/customscript';

$CFG->wwwroot   = 'http://localhost/flip-moodle-lc';
$CFG->dataroot  = '/var/www/moodledata_lc';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

define('PRIME_URL', 'https://stgptoc.fliplearn.com');
define('USER_LOGIN', 'quizdash.admin');
define('SESSION_TOKEN', 'mPqDPNLvEVjpWrHyLDKo7W4US');

define('UMS_URL', 'http://stgums.fliplearn.com');
define('BL_URL', 'http://stgbl.fliplearn.com');
define('UNSUBSCRIBE_MSG', 'You are not authorized to view this content. Please purchase license to view this content.');
define('API_FAIL_MSG', 'Content cannot be played right now. Please try again later.');
define('USER_MAPPING_MISSING_MSG', 'User UUID Mapping not found.');
define('PUBLIC_ACCESS_URL', 'https://stgmoodlelcdata.fliplearn.com');
define('JWPLAYER_KEY', 'gvD6tOcIdFv2QesR3M1VVucjXUCn7hMBtiAtoQXmP8b86K71');

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
