<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/../repository/lib.php');

$subjectId = optional_param('subjectId', '', PARAM_RAW);
$curl = new curl(array('cache' => false, 'debug' => false));
$url = PRIME_URL . "/v1/booksChapters?ncertEbookEnable=1&topicList=false&subjectId=" . $subjectId;
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
<?php foreach ($result3['response'] as $books) { ?>
  <div id='book_id_<?php echo $books['bookId']; ?>'>
      <div class='book_label'><?php echo $books['bookName']; ?></div>
      <?php foreach ($books['chapters'] as $chapters) { ?>
        <div class='chapter_label' onClick='getChapter(<?php echo $chapters['chapterId']; ?>)'>
          <?php echo $chapters['chapterName']; ?>
          <img class="icon" style="float:right; display: none" id="loding_<?php echo $chapters['chapterId']; ?>" alt="" src="<?php echo $CFG->wwwroot; ?>/theme/image.php?theme=adaptable&amp;component=core&amp;rev=1549869974&amp;image=i%2Floading_small">
        </div>
        <div id='chapter_id_<?php echo $chapters['chapterCode']; ?>'></div>
      <?php } ?>
  </div>
<?php
}