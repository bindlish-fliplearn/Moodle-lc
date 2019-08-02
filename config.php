<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';

$CFG->dbhost    = 'stgumsdb.fliplearn.com';

//$CFG->dbhost    = 'prodedgedbm1.fliplearn.com';
//$CFG->dbname    = 'flip_guru';
//$CFG->dbuser    = 'gauravs';
//$CFG->dbpass    = 'Gauravs$$18';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'stg_moodle_lc';
$CFG->dbuser    = 'root';
$CFG->dbpass    = 'redhat';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->customscripts = '/var/www/html/flip-moodleguru/customscript';
$CFG->wwwroot   = 'http://localhost/flip_moodle';
$CFG->dataroot  = '/var/www/html/moodledata_lc';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

// for send notification 

$CFG->GURU_ANNOUNCEMENT     = 'GURU_ANNOUNCEMENT';
$CFG->CONTEXT_LEVEL     = 50;
$CFG->SEND_NOTIFICATION     = true;
$CFG->COMMUNICATION_API_URL = 'http://stgeventapi.fliplearn.com:8084/event/';
$CFG->DOMAIN_NAME     = 'stgmoodlelc.fliplearn.com';
$CFG->BASE_URL     = 'https://stgmoodlelc.fliplearn.com';


$CFG->amazon_sqs_url = 'https://sqs.ap-south-1.amazonaws.com/095031386487/liveClassVideoTranscoding.fifo';
$CFG->amazon_key = "AKIAJX7FLZOOUR3Y4GPQ";
$CFG->amazon_secret = "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";
$CFG->amazon_region = "ap-south-1";
$CFG->liveclass_path = '/var/www/html/s3_liveclass/';
$CFG->wowza_key = '4fd684a604849e77dfJ3dflHkjds45549SC8O';
$CFG->wowza_token_end_days = 365;
$CFG->wowza_cdn_url = 'https://d12qsoed6q4q41.cloudfront.net/';
$CFG->path_to_media_file = 'fliplearnaes/_definst_/s3/';
$CFG->liveclass_bucket = 'fliplearnjeee';

// end here 




define('PRIME_URL', 'https://stgptoc.fliplearn.com');
define('USER_LOGIN', 'quizdash.admin');
define('SESSION_TOKEN', 'mPqDPNLvEVjpWrHyLDKo7W4US');

define('UMS_URL', 'http://stgums.fliplearn.com');
define('BL_URL', 'http://stgbl.fliplearn.com');
define('UNSUBSCRIBE_MSG', 'You are not authorized to view this content. Please purchase license to view this content.');
define('API_FAIL_MSG', 'Content cannot be played right now. Please try again later.');
define('USER_MAPPING_MISSING_MSG', 'User UUID Mapping not found.');
define('PUBLIC_ACCESS_URL', 'https://stgmoodlelcdata.fliplearn.com');

require_once(__DIR__ . '/lib/setup.php');

define('GURU_ANNOUNCEMENT','GURU_ANNOUNCEMENT');
define('CONTEXT_LEVEL',50);
define('SEND_NOTIFICATION',true);
define('COMMUNICATION_API_URL','http://stgeventapi.fliplearn.com:8084/event/');
define('DOMAIN_NAME','stgmoodlelc.fliplearn.com');
define('BASE_URL','https://stgmoodlelc.fliplearn.com');
define('PQUIZ_URL','https://dev5pquiz.fliplearn.com');
define('JWPLAYER_KEY', 'gvD6tOcIdFv2QesR3M1VVucjXUCn7hMBtiAtoQXmP8b86K71');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
