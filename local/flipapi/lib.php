<?php
function jwplayerInitialize($option){
	   global $CFG;
	   $jwplayerKey = $option['JWPLAYER_KEY'];
	   $path = $option['path'];
	 	$jwplayer = "<div class='player_div' id='player_div'>
            <div id='player'></div>
        </div>
        <script src= $CFG->wwwroot/repository/primecontent/pix/jwplayer-8.7.3/jwplayer.js></script>
        <script type='text/javascript'>
        jwplayer.key =  '$jwplayerKey';
            var path =  '$path';
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
?>