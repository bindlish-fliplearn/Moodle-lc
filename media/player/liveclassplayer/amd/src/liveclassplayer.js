// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JW Player module.
 *
 * @module     media_liveclassplayer/liveclassplayer
 * @package    media_liveclassplayer
 * @copyright  2017 Ruslan Kabalin, Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['liveclassplayer', 'jquery', 'core/config', 'core/yui', 'core/log', 'module'], function(liveclassplayer, $, mdlconfig, Y, log, module) {

    // Private functions and variables.
    /** @var {int} logcontext Moodle page context id. */
    var logcontext = null;
     var sendRequest = 1;
    /**
     * Event logging. Called when player event is triggered.
     *
     * @method logevent
     * @private
     * @param {Object[]} event JW Player event.
     */
    var logevent = function(event) {
        var playerinstance = this;
        var config = {
            method: 'POST',
            data:  {
                'sesskey' : mdlconfig.sesskey,
                'event': JSON.stringify(event),
                'id': logcontext,
                'title': playerinstance.getPlaylistItem().title,
                'file': playerinstance.getPlaylistItem().file,
                'position': playerinstance.getPosition(),
                'bitrate': playerinstance.getCurrentQuality().bitrate,
            },
            on: {
                failure: function(o) {
                    log.error(o);
                }
            }
        };
    
        setInterval(function(){ 
            sendRequest = 1;
             playerinstance.on('time', function (response) {
                   console.log('response',response);
                   var currentTime = response.currentTime;
                   var duration = response.duration;
                   var contextId = logcontext;
                    var url = window.location;
                    var path = url.host;
                    var update = {
                            view_time: currentTime,
                            duration: duration,
                            context_id: contextId,
                            title: playerinstance.getPlaylistItem().title,
                            file:playerinstance.getPlaylistItem().file
                        };
                    if(url.host == "localhost") {
                        path = url.host + "/flip_moodle";
                    }
                    if(sendRequest==1){
                        sendRequest = 0;
                        $.ajax({
                            type: "POST",
                            data: update,
                            url: url.protocol+'//'+path+"/webservice/rest/server.php?wstoken=6257f654f905c94b0d0f90fce5b9af31&wsfunction=local_flipapi_guru_vedio_view&moodlewsrestformat=json",
                            success: function (data) {
                                console.log(data);
                               
                            }
                        });
                    }
                });
         }, 30000);

        if (event.type == "play") {
            // For play events wait a short time before setting position so it picks up new position after seeks.
            setTimeout(function(){config.data.position = playerinstance.getPosition();}, 10);
        }

        if (event.type == "levelsChanged") {
            // Pass information of quality levels for quality level events.
            config.data.qualitylevel = JSON.stringify(playerinstance.getQualityLevels());
        }
        if (event.type == "audioTrackChanged") {
            // Pass information of audio tracks for audio track events.
            config.data.audiotracks = JSON.stringify(playerinstance.getAudioTracks());
        }
        if (event.type == "captionsChanged") {
            // Pass information of captions for caption events.
            config.data.captions = JSON.stringify(playerinstance.getCaptionsList());
        }

        // log.debug(config.data);
        Y.io(mdlconfig.wwwroot + '/media/player/liveclassplayer/eventlogger.php', config);
    };
    /**
     * Error logging. Called when player error event is triggered.
     *
     * @method logevent
     * @private
     * @param {Object[]} event JW Player event.
     */
    var logerror = function(event) {
        var errormsg = this.getPlaylistItem().title + ' ' + event.type + ': ' + event.message;
        log.error(errormsg);
    };

    return {
        /**
         * Setup player instance.
         *
         * @method init
         * @param {Object[]} playersetup JW Player setup parameters.
         * @return {void}
         */
        setupPlayer: function (playersetup) {
            // Unfortunately other loaded parts of JW player assume that liveclassplayer
            // lives in window.liveclassplayer, so we need this hack.
            window.liveclassplayer = window.liveclassplayer || liveclassplayer;
            if (module.config().licensekey) {
                window.liveclassplayer.key = module.config().licensekey;
            }

            logcontext = playersetup.logcontext;
            if (!$('#' + playersetup.playerid).length) {
                return;
            }
            var playerinstance = liveclassplayer(playersetup.playerid);
            playerinstance.setup(playersetup.setupdata);

            // Add download button if required.
            if (typeof(playersetup.downloadbtn) !== 'undefined') {
                playerinstance.addButton(playersetup.downloadbtn.img, playersetup.downloadbtn.tttext, function() {
                    // Grab the file that's currently playing.
                    window.open(playerinstance.getPlaylistItem().file + '?forcedownload=true');
                }, "download");
            }

            // Track errors and log them in browser console.
            playerinstance.on('setupError', logerror);
            playerinstance.on('error', logerror);

            // Track required events and log them in Moodle.
            playersetup.logevents.forEach(function (eventname) {
                playerinstance.on(eventname, logevent);
            });
        }
    };
});