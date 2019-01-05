<?php 
require_once('../config.php');
global $SESSION, $USER, $DB, $CFG; 
if($_POST['sessionToken'] != '' && $_POST['uuid'] != ''){
	$SESSION->sessionToken = $_POST['sessionToken'];
	$SESSION->uuid = $_POST['uuid'];
}
?>