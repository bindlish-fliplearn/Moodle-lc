<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/../repository/lib.php');

//$source = "https://dwq2qrehawfaj.cloudfront.net/EOL/Contents/2014050700179120/thumbnail.jpg?resourceId=8658@@MP4@@1@@VDOEN";
$sourcekey = sha1($source . repository::get_secret_key() . sesskey());
$topicId = optional_param('topicId', '', PARAM_RAW);
$tagKey = optional_param('tagKey', '', PARAM_RAW);
$curl = new curl(array('cache' => false, 'debug' => false));
$urlTag = "";
if(!empty($tagKey)) {
  $urlTag = "&tagKey=".$tagKey;
}
$url = PRIME_URL . "/v1/topic?ncertEbookEnable=1&boardCode=CBSE&topicId=".$topicId.$urlTag;
$header = array(
  'Accept: application/json',
  'Content-Type: application/x-www-form-urlencoded',
  'loginId: '.$SESSION->loginId,
  'sessionToken:'.$SESSION->sessionToken,
  '3dSupport: 1',
  'platform: web'
);
$curl->setHeader($header);
$content3 = $curl->get($url, '');
$result3 = json_decode($content3, true);
?>
<div class="resource_div" id="resource_div">
<?php foreach ($result3['response']['resources'] as $resources) { ?>
  <div id='resource_id_<?php echo $resources['resourceId'] ?>' class='resource_box'>
      <img src="<?php echo $resources['thumbNail'] ?>" class='resource_img'>
      <?php
      $mediaType = $resources['mediaType'];
      $isMedia = $resources['isMedia'];
      $cType = $resources['cType'];
      if(strpos($resources['thumbNail'], '?')) {
        $source = $resources['thumbNail'] . "&resourceId=" . $resources['resourceId'] . "@@".$mediaType."@@".$isMedia."@@".$cType;
      } else {
        $source = $resources['thumbNail'] . "?resourceId=" . $resources['resourceId'] . "@@".$mediaType."@@".$isMedia."@@".$cType;
      }
      
      $sourcekey = sha1($source . repository::get_secret_key() . sesskey());
      ?>
      <input type='hidden' id='resource_source_<?php echo $resources['resourceId'] ?>' value='<?php echo $source ?>'>
      <input type='hidden' id='resource_sourcekey_<?php echo $resources['resourceId'] ?>' value='<?php echo $sourcekey ?>'>
      <input type='hidden' id='resource_title_<?php echo $resources['resourceId'] ?>' value='<?php echo $resources['title'] ?>'>
      <input type='hidden' id='resource_cType_<?php echo $resources['resourceId'] ?>' value='<?php echo $resources['cType'] ?>'>
      <div class='resource_label' onClick='downloadFile(<?php echo $resources['resourceId'] ?>)'><?php echo $resources['title'] ?></div>
      <a href="javascript:void(0);" onclick="preview(<?php echo $resources['resourceId'] ?>);">preview</a>
  </div>
<?php } ?>
</div>
<div class="player_div" id="player_div">
    <a href="javascript:void(0);" onClick="closePopup();">Back</a>
    <a href="javascript:void(0);" id="add_resource">Add Resource</a>
    <div id='player'></div>
</div>
<script src="<?php echo $CFG->wwwroot; ?>/repository/primecontent/pix/jwplayer-8.7.3/jwplayer.js"></script>
<script type="text/javascript">
jwplayer.key = "<?php echo JWPLAYER_KEY; ?>";
</script>