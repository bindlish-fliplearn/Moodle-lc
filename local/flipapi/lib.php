<?php
function jwplayerInitialize($option){
	   global $CFG;
	   $jwplayerKey = $option['JWPLAYER_KEY'];
	   $path = urldecode($option['path']);
	 	$jwplayer = "<div class='player_div' id='player_div'>
            <div id='player'></div>
        </div>
        <script src= $CFG->wwwroot/repository/primecontent/pix/jwplayer-8.7.3/jwplayer.js></script>
        <script type='text/javascript'>
        jwplayer.key =  '$jwplayerKey';
            var path = '$path';
            var playerInstance = jwplayer('player');
                        playerInstance.setup({
                            width: '620',
                            height: '430',
                            bufferlength: '1',
                            controlbar: 'none',
                            stretching: 'uniform',
                            autostart: 'true',
                            primary: 'flash',
                            hlshtml: true,
                            file: path,
                            defaultBandwidthEstimate : 240000
                        });
        </script>";
		return $jwplayer;
} 
function getRatingBox($instanceId){
		global $USER,$DB,$CFG;
		$userId = $USER->id;
		$rating = '';
		 $avgsql = "SELECT sum(rating) as totalRating, count(id) as totalRecord FROM {guru_activity_rating} WHERE cm_id= $instanceId";
        $avgrecord = $DB->get_record_sql($avgsql);
        $avgrating  	= 0 ;
        $totalRecord 	= 0;
		$avgrating  	= 0 ;
        $totalRecord 	= 0;
        if(!empty($avgrecord)){
          $totalRating = $avgrecord->totalrating;
          $totalRecord = $avgrecord->totalrecord;
          if($totalRating > 0){
              $avgrating  = round(($totalRating/$totalRecord), 2);
          }
        }
        if($CFG->AVG_RATING <= $avgrating && $CFG->MAX_USER <= $totalRecord){
            $avgrating  = 'Avg Rating:'.$avgrating;  
        }else {
           $avgrating = ''; 
        }

        $startCount = 0;
        $sql = "SELECT * FROM {guru_activity_rating} WHERE user_id = $userId AND cm_id= $instanceId";
        $record = $DB->get_record_sql($sql);
        if(!empty($record)){
                $startCount = $record->rating;
                $feedback = $record->feedback;
        }

	   for ($i=1; $i <=5 ; $i++) { 

	   	 if($startCount >= $i){
                $rating .="<span class='fa fa-star' onclick = addReminder($i,$instanceId,$userId,'true') id =rating_$i></span>";
            }else{

            	  $rating .="<span class='fa fa-star-o' onclick = addReminder($i,$instanceId,$userId,'true') id =rating_$i></span>";
            }
        }
        $rRatingDiv = "<div class='star'> $rating <input type='hidden' value = $startCount id='starcount' ></div>";
        $lRatingDiv = "<div class='avg' > $avgrating</div><div class='success ratingSuccess' id='ratingSuccess'></div>";
        $ratingDiv = "<div class='ratingarea'> $lRatingDiv  $rRatingDiv</div>";
        $textArea = "<div class='commentHide' id = 'textareaboxlive'><textarea placeholder = '(Optional feedback about the video lesson)' id ='feedbackliveClasspopup' name = 'feedback' rows='4' cols='59'></textarea></div>";
        $lnote = "<div class = 'feedbacknote' ><span>Note : This feedback will remain anonymous</span></div>";
        $rsubmitButton = "<div class='submitButton liveClassStar'><button type = submit  value = Submit onclick = submitFeedback($instanceId,$userId,'false')>Submit</button></div>";
        $mainbuttonDiv = "<div class = 'mainButton'>$lnote $rsubmitButton</div>";
        $commentMainDiv = "<div id = 'checkboxDiv'><div id = 'optionlivedivlist'></div><div id ='commentBox' class = 'commentHide'>$textArea $mainbuttonDiv </div>";
        $successMSG = "<div class = 'commentHide' id = 'successMsg'>Feedback successfully submitted ! Happy Learning </div></div>";
        $mainDiv = "<div class ='rating liveClassWizIq'>$ratingDiv $commentMainDiv </div>";
        return $mainDiv.$successMSG;
}

?>