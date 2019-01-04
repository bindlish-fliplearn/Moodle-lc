<?php 
require_once('../config.php');
global $SESSION, $USER, $DB, $CFG; 
if($USER->id!='' && BL_URL != ''){
	$user = $DB->get_record_sql('SELECT uuid FROM {guru_user_mapping} WHERE user_id = ?', array($USER->id));
	if($user){
			$responseArray = array('uuid'=>$user->uuid,'BL_URL'=>BL_URL,error=>'');
			echo json_encode($responseArray);
	}else{
		$responseArray = array(error=>'User not found.');
		echo json_encode($responseArray);	
	}
}
else{
	$responseArray = array(error=>'User not found.');
	echo json_encode($responseArray);
}
?>