<?php

/**
 * @file
 * fliplearnlib.php
 * This file is use to create lib function.
 * @author Kewal Kanojia <kewal@incaendo.com>
 * @copyright (c) 2016, Fliplearn.
 */

/**
 * Function createSmilFile
 * This function is used to create smile for video file.
 * @author Kewal Kanojia <rkewal@incaendo.com>
 */
function createSmilFile($fileName, $path) {
  $smilfile = fopen($fileName, "w") or die("Unable to open file!");
  $videos = "";
  $checkVideoBitRate = shell_exec("ffprobe -v error -select_streams v:0 -show_entries stream=bit_rate -of default=noprint_wrappers=1 $path");
  $videoBitRate = (int) end(explode("=", $checkVideoBitRate));
  $videos .= "<video src='output.mp4' system-bitrate='$videoBitRate' />\n\t";
  $txt = "<?xml version='1.0' encoding='UTF-8'?>
    <smil>
      <head></head>
      <body>
        <switch>
          $videos
        </switch>
      </body>
    </smil>";
  if (!empty($videos)) {
    fwrite($smilfile, $txt);
    fclose($smilfile);
  }
}

/**
 * Function getWowzaUrl
 * This function is used to create wowza url of video.
 * @author Kewal Kanojia <rkewal@incaendo.com>
 */
function getWowzaUrl($url, $bucketName) {
  global $CFG;
  $bucket = $bucketName . '/';
  $assetUrl = str_replace('http://', '', $bucket) . $url;
  //$clientIP = "121.243.101.18"; // "202.54.232.162";
  $wowzaSecureTokenStartTime = "wowzatokenstarttime=" . (time() - (1 * 24 * 60 * 60));
  $wowzaSecureTokenEndTime = "wowzatokenendtime=" . (time() + ($CFG->wowza_token_end_days * 24 * 60 * 60) ); // Expiry one day
  //$wowzaKey = "efd23a7fc2737496";
  $wowzaKey = $CFG->wowza_key;
  $pathtomediafile = $CFG->path_to_media_file . $assetUrl;
  $strToHash = $pathtomediafile . "?" . $wowzaKey . "&" . $wowzaSecureTokenEndTime . "&" . $wowzaSecureTokenStartTime;
//  echo "Converting...".$strToHash."\n";
  $hashstr = hash('sha256', $strToHash, true);
//  echo "Converted sha256: ".$hashstr."\n";
  $wowzatokenhash = strtr(base64_encode($hashstr), '+/', '-_');
//  echo "Converted Base64: ".$wowzatokenhash."\n";
  $finalURL = $CFG->wowza_cdn_url . $pathtomediafile . "/playlist.m3u8?" . $wowzaSecureTokenStartTime . "&" . $wowzaSecureTokenEndTime . "&" . "wowzatokenhash=" . $wowzatokenhash;
  return $finalURL;
}
