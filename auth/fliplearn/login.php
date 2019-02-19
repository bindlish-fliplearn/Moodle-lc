<?php

require_once('../../config.php');
require_once('auth.php');

$issuerid = required_param('id', PARAM_INT);
$wantsurl = new moodle_url(optional_param('wantsurl', '', PARAM_URL));
$cmid = optional_param('cmid', '', PARAM_INT);

require_sesskey();

if (!\auth_oauth2\api::is_enabled()) {
  throw new \moodle_exception('notenabled', 'auth_oauth2');
}

$issuer = new \core\oauth2\issuer($issuerid);

$returnparams = ['wantsurl' => $wantsurl, 'sesskey' => sesskey(), 'id' => $issuerid];
$returnurl = new moodle_url('/auth/fliplearn/login.php', $returnparams);

$client = \core\oauth2\api::get_user_oauth_client($issuer, $returnurl);

if ($client) {
  $auth = new \auth_plugin_fliplearn();
  $auth->complete_login($client, $wantsurl, $cmid);
} else {
  throw new moodle_exception('Could not get an OAuth client.');
}

