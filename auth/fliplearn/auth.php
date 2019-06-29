<?php

/**
 * @package auth_fliplearn
 * @author Kapil Kumar <kapilk.inc@fliplearn.com>
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/login/lib.php');

require_once($CFG->libdir . '/classes/oauth2/user_field_mapping.php');
require_once($CFG->dirroot . '/auth/oauth2/classes/api.php');

/**
 * OpenID Connect Authentication Plugin.
 */
class auth_plugin_fliplearn extends \auth_plugin_base {

  /** @var string Authentication plugin type - the same as db field. */
  public $authtype = 'fliplearn';

  /** @var object Plugin config. */
  public $config;

  /**
   * Constructor.
   */
  public function __construct($forceloginflow = null) {
    
  }
  
  public function user_login($username, $password) {
    
  }
  
  public function update_picture($user) {
    
  }
  
  public function print_confirm_required($emailconfirm, $message) {
    
  }
  
  public function set_static_user_picture($picture) {
    
  }

  public function complete_login(core\oauth2\client $client, $redirecturl, $cmid, $attemptId) {
    global $CFG, $SESSION, $PAGE;

   $userMappingData = $userinfo = $this->get_userinfo($client);
   $uuid = $userMappingData['uuid'];
   $userMappingSql = "SELECT user_id as id FROM {guru_user_mapping}
                            WHERE uuid =?";
   $moodleuser = $DB->get_record_sql($userMappingSql, array($uuid));
   $issuer = $client->get_issuer();
    \auth_oauth2\api::link_login($userinfo, $issuer, $moodleuser->id, true);
    if (!$userinfo) {
      // Trigger login failed event.
      $failurereason = AUTH_LOGIN_NOUSER;
      $event = \core\event\user_login_failed::create(['other' => ['username' => 'unknown',
            'reason' => $failurereason]]);
      $event->trigger();

      $errormsg = get_string('loginerror_nouserinfo', 'auth_oauth2');
      $SESSION->loginerrormsg = $errormsg;
      $client->log_out();
      redirect(new moodle_url('/login/index.php'));
    }
    if (empty($userinfo['username']) || empty($userinfo['email'])) {
      // Trigger login failed event.
      $failurereason = AUTH_LOGIN_NOUSER;
      $event = \core\event\user_login_failed::create(['other' => ['username' => 'unknown',
            'reason' => $failurereason]]);
      $event->trigger();

      $errormsg = get_string('loginerror_userincomplete', 'auth_oauth2');
      $SESSION->loginerrormsg = $errormsg;
      $client->log_out();
      redirect(new moodle_url('/login/index.php'));
    }

    $userinfo['username'] = trim(core_text::strtolower($userinfo['username']));
    $oauthemail = $userinfo['email'];

    // Once we get here we have the user info from oauth.
    $userwasmapped = false;

    // Clean and remember the picture / lang.
    if (!empty($userinfo['picture'])) {
      $this->set_static_user_picture($userinfo['picture']);
      unset($userinfo['picture']);
    }

    if (!empty($userinfo['lang'])) {
      $userinfo['lang'] = str_replace('-', '_', trim(core_text::strtolower($userinfo['lang'])));
      if (!get_string_manager()->translation_exists($userinfo['lang'], false)) {
        unset($userinfo['lang']);
      }
    }

    // First we try and find a defined mapping.
    $linkedlogin = \auth_oauth2\api::match_username_to_user($userinfo['username'], $client->get_issuer());

    if (!empty($linkedlogin) && empty($linkedlogin->get('confirmtoken'))) {
      $mappeduser = get_complete_user_data('id', $linkedlogin->get('userid'));

      if ($mappeduser && $mappeduser->suspended) {
        $failurereason = AUTH_LOGIN_SUSPENDED;
        $event = \core\event\user_login_failed::create([
            'userid' => $mappeduser->id,
            'other' => [
              'username' => $userinfo['username'],
              'reason' => $failurereason
            ]
        ]);
        $event->trigger();
        $SESSION->loginerrormsg = get_string('invalidlogin');
        $client->log_out();
        redirect(new moodle_url('/login/index.php'));
      } else if ($mappeduser && $mappeduser->confirmed) {
        $userinfo = (array) $mappeduser;
        $userwasmapped = true;
      } else {
        // Trigger login failed event.
        $failurereason = AUTH_LOGIN_UNAUTHORISED;
        $event = \core\event\user_login_failed::create(['other' => ['username' => $userinfo['username'],
              'reason' => $failurereason]]);
        $event->trigger();

        $errormsg = get_string('confirmationpending', 'auth_oauth2');
        $SESSION->loginerrormsg = $errormsg;
        $client->log_out();
        redirect(new moodle_url('/login/index.php'));
      }
    } else if (!empty($linkedlogin)) {
      // Trigger login failed event.
      $failurereason = AUTH_LOGIN_UNAUTHORISED;
      $event = \core\event\user_login_failed::create(['other' => ['username' => $userinfo['username'],
            'reason' => $failurereason]]);
      $event->trigger();

      $errormsg = get_string('confirmationpending', 'auth_oauth2');
      $SESSION->loginerrormsg = $errormsg;
      $client->log_out();
      redirect(new moodle_url('/login/index.php'));
    }

    $issuer = $client->get_issuer();
//        if (!$issuer->is_valid_login_domain($oauthemail)) {
//            // Trigger login failed event.
//            $failurereason = AUTH_LOGIN_UNAUTHORISED;
//            $event = \core\event\user_login_failed::create(['other' => ['username' => $userinfo['username'],
//                                                                        'reason' => $failurereason]]);
//            $event->trigger();
//
//            $errormsg = get_string('notloggedindebug', 'auth_oauth2', get_string('loginerror_invaliddomain', 'auth_oauth2'));
//            $SESSION->loginerrormsg = $errormsg;
//            $client->log_out();
//            redirect(new moodle_url('/login/index.php'));
//        }

    if (!$userwasmapped) {
      // No defined mapping - we need to see if there is an existing account with the same email.

      $moodleuser = \core_user::get_user_by_email($userinfo['email']);
      if (!empty($moodleuser)) {
        if ($issuer->get('requireconfirmation')) {
          $PAGE->set_url('/auth/oauth2/confirm-link-login.php');
          $PAGE->set_context(context_system::instance());

          \auth_oauth2\api::send_confirm_link_login_email($userinfo, $issuer, $moodleuser->id);
          // Request to link to existing account.
          $emailconfirm = get_string('emailconfirmlink', 'auth_oauth2');
          $message = get_string('emailconfirmlinksent', 'auth_oauth2', $moodleuser->email);
//          $this->print_confirm_required($emailconfirm, $message);
          exit();
        } else {
          \auth_oauth2\api::link_login($userinfo, $issuer, $moodleuser->id, true);
          $userinfo = get_complete_user_data('id', $moodleuser->id);
          // No redirect, we will complete this login.
        }
      } else {
        // This is a new account.
        $exists = \core_user::get_user_by_username($userinfo['username']);
        // Creating a new user?
        if ($exists) {
          // Trigger login failed event.
          $failurereason = AUTH_LOGIN_FAILED;
          $event = \core\event\user_login_failed::create(['other' => ['username' => $userinfo['username'],
                'reason' => $failurereason]]);
          $event->trigger();

          // The username exists but the emails don't match. Refuse to continue.
          $errormsg = get_string('accountexists', 'auth_oauth2');
          $SESSION->loginerrormsg = $errormsg;
          $client->log_out();
          redirect(new moodle_url('/login/index.php'));
        }

//                if (email_is_not_allowed($userinfo['email'])) {
//                    // Trigger login failed event.
//                    $failurereason = AUTH_LOGIN_FAILED;
//                    $event = \core\event\user_login_failed::create(['other' => ['username' => $userinfo['username'],
//                                                                                'reason' => $failurereason]]);
//                    $event->trigger();
//                    // The username exists but the emails don't match. Refuse to continue.
//                    $reason = get_string('loginerror_invaliddomain', 'auth_oauth2');
//                    $errormsg = get_string('notloggedindebug', 'auth_oauth2', $reason);
//                    $SESSION->loginerrormsg = $errormsg;
//                    $client->log_out();
//                    redirect(new moodle_url('/login/index.php'));
//                }

        if (!empty($CFG->authpreventaccountcreation)) {
          // Trigger login failed event.
          $failurereason = AUTH_LOGIN_UNAUTHORISED;
          $event = \core\event\user_login_failed::create(['other' => ['username' => $userinfo['username'],
                'reason' => $failurereason]]);
          $event->trigger();
          // The username does not exist and settings prevent creating new accounts.
          $reason = get_string('loginerror_cannotcreateaccounts', 'auth_oauth2');
          $errormsg = get_string('notloggedindebug', 'auth_oauth2', $reason);
          $SESSION->loginerrormsg = $errormsg;
          $client->log_out();
          redirect(new moodle_url('/login/index.php'));
        }

        if ($issuer->get('requireconfirmation')) {
          $PAGE->set_url('/auth/oauth2/confirm-account.php');
          $PAGE->set_context(context_system::instance());

          // Create a new (unconfirmed account) and send an email to confirm it.
          $user = \auth_oauth2\api::send_confirm_account_email($userinfo, $issuer);

//          $this->update_picture($user);
          $emailconfirm = get_string('emailconfirm');
          $message = get_string('emailconfirmsent', '', $userinfo['email']);
          $this->print_confirm_required($emailconfirm, $message);
          exit();
        } else {
          // Create a new confirmed account.
          $newuser = \auth_oauth2\api::create_new_confirmed_account($userinfo, $issuer);
                    
          // Save UUID of user.
          /*if (!empty($userinfo['uuid'])) {
            $field = $DB->get_record_sql('SELECT * FROM {user_info_field} WHERE name = ?', 
                         array('UUID'));          
            if (!empty($field) && isset($field->id)) {
              $record = new stdClass();
              $record->fieldid = $field->id;
              $record->userid = $newuser->id;
              $record->data = $userinfo['uuid'];
              $record->dataformat = '0';
              $DB->insert_record('user_info_data', $record);
            }
          }*/
          
          $userinfo = get_complete_user_data('id', $newuser->id);

          // No redirect, we will complete this login.
        }
      }
    }

    global $DB;
    $uuid = $userMappingData['uuid'];
    $userMappingSql = "SELECT user_id FROM {guru_user_mapping} 
                            WHERE uuid =?";
    $userMapping = $DB->get_record_sql($userMappingSql, array($uuid));
    if (empty($userMapping)) {
      $email = $userMappingData['flipuser_email']?$userMappingData['flipuser_email']:$userMappingData['flipuser_uuid'].'@fliplearn.com';
      $userObj = new stdClass();
      $userObj->user_id = $userinfo->id;
      $userObj->uuid  = $uuid;
      $userObj->firstname = $userMappingData['flipuser_name'];
      $userObj->email = $email;
      $userObj->school_code = '';
      $userObj->role = '';
      $userObj->is_enrolled = 0; 
      $userObj->ayid = 0;
      $id =  $DB->insert_record('guru_user_mapping',$userObj , $returnid=true, $bulk=false) ;
    }

    // We used to call authenticate_user - but that won't work if the current user has a different default authentication
    // method. Since we now ALWAYS link a login - if we get to here we can directly allow the user in.
    $user = (object) $userinfo;
    complete_user_login($user);

    if (!empty($cmid)) {
      global $DB;
      $courseSql = "SELECT course FROM {course_modules} 
                            WHERE id =?";
      $courseRes = $DB->get_record_sql($courseSql, array($cmid));
      $courseId = $courseRes->course;
      $userId = $user->id;
      $roleId = 5;
      
      //Check if user is enrolled in the course. If not, enrol it in the given course
      if(!enrol_try_internal_enrol($courseId, $userId, $roleId, time())) {
        // There's a problem.
        throw new moodle_exception('unabletoenrolerrormessage', 'langsourcefile');
      }
      
      if (empty($attemptId)) {
        redirect(new moodle_url('/mod/quiz/view.php?id=' . $cmid));
      } else {
        redirect(new moodle_url('/mod/quiz/review.php?attempt=' . $attemptId . '&cmid=' . $cmid));
      }
    } else {
      redirect($redirecturl);
    }
  }

  /**
   * Fetch the user info from the user info endpoint and map all
   * the fields back into moodle fields.
   *
   * @return array|false Moodle user fields for the logged in user (or false if request failed)
   */
  public function get_userinfo($client) {
    global $DB;
    $oauth2code = required_param('oauth2code', PARAM_RAW);
    $url = $client->get_issuer()->get_endpoint_url('userinfo');
    $client->setHeader('Authorization: Bearer ' . $oauth2code);
    $response = $client->get($url);
    if (!$response) {
      return false;
    }
    $userinfo = new stdClass();
    try {
      $userinfo = json_decode($response);
    } catch (\Exception $e) {
      return false;
    }
    
    $userEmail = $userinfo->email;
    $userinfo->email = $userinfo->uuid.'@fliplearn.com';
//    $map = $this->get_userinfo_mapping($client);
    $map = [
      "email" => "email",
      "mobile" => "phone1",
      "name" => "firstname",
      "uuid" => "uuid",
    ];

    $user = new stdClass();
    foreach ($map as $openidproperty => $moodleproperty) {
      // We support nested objects via a-b-c syntax.
      $getfunc = function($obj, $prop) use (&$getfunc) {
        $proplist = explode('-', $prop, 2);
        if (empty($proplist[0]) || empty($obj->{$proplist[0]})) {
          return false;
        }
        $obj = $obj->{$proplist[0]};

        if (count($proplist) > 1) {
          return $getfunc($obj, $proplist[1]);
        }
        return $obj;
      };

      $resolved = $getfunc($userinfo, $openidproperty);
      if (!empty($resolved)) {
        $user->$moodleproperty = $resolved;
      }
    }

    if (empty($user->username) && !empty($user->email)) {
      $user->username = $user->email;
    }

    if (isset($userinfo->uuid) && !empty($userinfo->uuid)) {
      $userMapping = '';
      $mappedUsername = '';
      $uuid = $userinfo->uuid;
      $userMapping = $DB->get_record('guru_user_mapping', array('uuid' => $uuid), 'user_id');
      if (!empty($userMapping->user_id)) {
        $mappedUsername = $DB->get_record('user', array('id' => $userMapping->user_id), 'username');
        if (!empty($mappedUsername->username)) {
          $user->username = $mappedUsername->username;
        }
      }
    }

    $firstname = 'null';
    $lastname = 'null';
    if (!empty($userinfo->name)) {
      $arr = explode(" ",$userinfo->name);
      if (count($arr) > 1) {
        $firstname = $arr[0];
        $lastname = $arr[1];
      } else {
        $firstname = $arr[0];
      }
    }

    $name   = $userinfo->name?$userinfo->name:'';
    $email  = $userEmail?$userEmail:'';
    $uuid   = $userinfo->uuid?$userinfo->uuid:'';

    $user = (array) $user;
    $user['flipuser_name'] = $name;
    $user['flipuser_email'] = $email;
    $user['flipuser_uuid'] == $uuid;
    $user['firstname'] = $firstname;
    $user['lastname'] = $lastname;
    return $user;
  }

  private function getIssuer($issuerid) {
    return $issuer = new \core\oauth2\issuer($issuerid);
  }

  protected function get_userinfo_mapping($client) {
    $issuerid = required_param('id', PARAM_INT);
    $issuer = $this->getIssuer($issuerid);
    $fields = core\oauth2\user_field_mapping::get_records(['issuerid' => $issuer]);
    $map = [];
    foreach ($fields as $field) {
      $map[$field->get('externalfield')] = $field->get('internalfield');
    }
    return $map;
  }

}

