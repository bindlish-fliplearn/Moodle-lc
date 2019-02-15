<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/../repository/lib.php');

//$source = "https://dwq2qrehawfaj.cloudfront.net/EOL/Contents/2014050700179120/thumbnail.jpg?resourceId=8658@@MP4@@1@@VDOEN";
$sourcekey = sha1($source . repository::get_secret_key() . sesskey());
$topicId = optional_param('topicId', '', PARAM_RAW);
$curl = new curl(array('cache' => false, 'debug' => false));
$url = PRIME_URL . "/v1/topic?ncertEbookEnable=1&boardCode=CBSE&topicId=32783"; // . $topicId;
$header = array(
  'Accept: application/json',
  'Content-Type: application/x-www-form-urlencoded',
  'loginId: vinay2.admin',
  'profileCode: 5800667696',
  'sessionToken: 5geO3FuaOCa4QIxkSeZ6ssTT5',
  '3dSupport: 1',
  'platform: web'
);
$curl->setHeader($header);
$content3 = $curl->get($url, '');
$result3 = json_decode($content3, true);
?>
<?php foreach ($result3['response']['resources'] as $resources) { ?>
  <div id='resource_id_<?php echo $resources['resourceId'] ?>' class='resource_box'>
      <img src="<?php echo $resources['thumbNail'] ?>" class='resource_img'>
      <?php
      $source = $resources['thumbNail'] . "?resourceId=" . $resources['resourceId'] . "@@MP4@@1@@VDOEN";
      $sourcekey = sha1($source . repository::get_secret_key() . sesskey());
      ?>
      <input type='hidden' id='resource_source_<?php echo $resources['resourceId'] ?>' value='<?php echo $source ?>'>
      <input type='hidden' id='resource_sourcekey_<?php echo $resources['resourceId'] ?>' value='<?php echo $sourcekey ?>'>
      <input type='hidden' id='resource_title_<?php echo $resources['resourceId'] ?>' value='<?php echo $resources['title'] ?>'>
      <div class='resource_label' onClick='downloadFile(<?php echo $resources['resourceId'] ?>)'><?php echo $resources['title'] ?></div>
  </div>
<?php
}