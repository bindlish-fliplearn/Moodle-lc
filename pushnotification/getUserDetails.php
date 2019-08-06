<?php 
require_once('../config.php');
global $SESSION, $USER, $DB, $CFG; 
if($USER){
		echo json_encode($USER);
}
else{
	$responseArray = array(error=>'User not found.');
	echo json_encode($responseArray);
}
?>