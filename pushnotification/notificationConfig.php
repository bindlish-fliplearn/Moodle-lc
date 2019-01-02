<?php 
require_once('../config.php');
global $SESSION, $USER, $DB, $CFG; 
if($USER->id!='' && UMS_URL != ''){
	$responseArray = array('uuid'=>$USER->id,'UMS_URL'=>UMS_URL,error=>'');
	echo json_encode($responseArray);
}
else{
	$responseArray = array(error=>'User not found.');
	echo json_encode($responseArray);
}
?>