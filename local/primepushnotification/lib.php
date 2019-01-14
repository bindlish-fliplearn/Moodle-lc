<?php
/*Post data using curl request 
*/
function curlPost($data_string, $url){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($ch, CURLOPT_TIMEOUT, 3); //timeout in seconds
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen($data_string))
			);
			
			$result = curl_exec($ch);
			$error = curl_errno($ch);
			$responseArray = array('error'=>null,'data'=>'');
			if($result){
			   $responseArray['data']= $result;
			   return  json_encode($responseArray);
			}else{
				 $responseArray['error']= $error;
				 return json_encode($responseArray);
			}
}
?>