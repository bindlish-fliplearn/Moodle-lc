<?php

require_once(__DIR__ . '/../../config.php');

$error = optional_param('error', '', PARAM_RAW);
if ($error) {
  $message = optional_param('error_description', '', PARAM_RAW);
  if ($message) {
    print_error($message);
  } else {
    print_error($error);
  }
  die();
}

// The authorization code generated by the authorization server.
$code = required_param('code', PARAM_RAW);
// The state parameter we've given (used in moodle as a redirect url).
$state = required_param('state', PARAM_LOCALURL);

$id = required_param('id', PARAM_RAW);

$cmid = optional_param('cmid', '', PARAM_RAW);

$redirecturl = new moodle_url($state);
$params = $redirecturl->params();

global $USER;
if (!isset($USER->sesskey) || empty($USER->sesskey)) {
  $params['sesskey'] = sesskey();
} else {
  $params['sesskey'] = $USER->sesskey;
}

$redirecturl->param('oauth2code', $code);
$redirecturl->param('sesskey', $params['sesskey']);
$redirecturl->param('id', $id);
$redirecturl->param('cmid', $cmid);
//print_r($redirecturl); die;

if (isset($params['sesskey']) and confirm_sesskey($params['sesskey'])) {
  $redirecturl->param('oauth2code', $code);
  redirect($redirecturl);
} else {
  print_error('invalidsesskey');
}