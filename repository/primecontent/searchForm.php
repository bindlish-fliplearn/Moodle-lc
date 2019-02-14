<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/../repository/lib.php');
?>
<style>
    .file-picker .fp-content {
        background: #fff;
        clear: none;
        overflow: auto;
        height: 452px;
    }
    .fp-login-form {
        height: 100%;
        width: 100%;
        display: table;
    }
    .fp-content-center {
        height: 100%;
        width: 100%;
        display: table-cell;
        vertical-align: middle;
    }
    .fp-formset {
        max-width: 500px;
        padding: 10px;
    }
    .form-horizontal .control-group {
        margin-bottom: 20px;
    }
    .book_label {
        background-color: orange;
        padding: 5px 10px;
        color: #FFFFFF;
        font-weight: bold;
        border-radius: 5px;
    }
    .chapter_label {
        padding: 5px 10px;
        border-bottom: #c6d8e4 1px solid;
        cursor: pointer;
    }
    .chapter_label:hover {
        background-color: #c6d8e4;
    }
    .topic_label {
        padding: 5px 30px;
        cursor: pointer;
        border: 1px #FFFFFF solid;
        border-radius: 5px;
    }
    .topic_label:hover {
        border: 1px rgb(250, 151, 33) solid;
    }
    .resource_box {
        width: 45%;
        float: left;
        min-height: 82px;
        border: #c6d8e4 1px solid;
        border-radius: 5px;
        padding: 5px;
        margin: 5px;
    }
    .resource_img {
        width: 80px;
        height: 80px;
        overflow: hidden;
        float: left;
        background: rgb(250, 151, 33);
    }
    .resource_label {
        float: left;
        line-height: 80px;
        padding: 0px 10px;
        width: 62%;
        max-height: 80px;
        overflow: hidden;
    }
</style>

<div id="search_class_subject">
    <div class="fp-login-form" id="yui_3_17_2_1_1549621888968_2766">
        <div class="fp-content-center">
            <div class="fp-formset">
                <div class="fp-login-select control-group clearfix" id="yui_3_17_2_1_1549621888968_2775">
                    <label class="control-label" for="primecontent_class">Search for:</label>
                    <div class="controls">
                        <select id="primecontent_class" name="primecontent_class">
                            <option value="">Select class</option>
                            <option value="57">Pre Nursery</option>
                            <option value="56">Nursery</option>
                            <option value="55">KG</option>
                            <option value="43">Class 1</option>
                            <option value="44">Class 2</option>
                            <option value="45">Class 3</option>
                            <option value="46">Class 4</option>
                            <option value="47">Class 5</option>
                            <option value="48">Class 6</option>
                            <option value="49">Class 7</option>
                            <option value="50">Class 8</option>
                            <option value="51">Class 9</option>
                            <option value="52">Class 10</option>
                            <option value="53">Class 11</option>
                            <option value="54">Class 12</option>
                        </select>
                    </div>
                </div>
                <div class="fp-login-select control-group clearfix" id="yui_3_17_2_1_1549621888968_2795">
                    <label class="control-label" for="primecontent_subject">Search for:</label>
                    <div class="controls">
                        <select id="primecontent_subject" name="primecontent_subject">
                            <option value="">Select subject</option>
                        </select>
                    </div>
                </div>
            </div>
            <p><button class="fp-login-submit btn-primary btn" id="classSubjectButton">Submit</button></p>
        </div>
    </div>
</div>
<?php
$source = "https://dwq2qrehawfaj.cloudfront.net/EOL/Contents/2014050700179120/thumbnail.jpg?resourceId=8658@@MP4@@1@@VDOEN";
$sourcekey = sha1($source . repository::get_secret_key() . sesskey());
?>
<script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/lib/jquery/jquery-3.2.1.min.js"></script>
<script>
  $('document,body').on('change', '#primecontent_class', function () {
      var classSubjectList = JSON.parse(window.localStorage.getItem('classSubject'));
      var classId = $(this).val();
      var subjectList = JSON.parse(classSubjectList[classId]);
      $("#primecontent_subject").html("");
      $("#primecontent_subject").append("<option value=''>All Subject</option>");
      $.each(subjectList, function (subjectIndex, subjectValue) {
          $("#primecontent_subject").append("<option value='" + subjectValue.subjectCode + "'>" + subjectValue.subjectName + "</option>");
      });
  });

  $('document,body').on('click', '#classSubjectButton', function () {
      var primecontent_class = $('#primecontent_class').val();
      var primecontent_subject = $('#primecontent_subject').val();
      console.log(primecontent_class + "<==>" + primecontent_subject);
      showLoading();
      createBookChaperListing(primecontent_class, primecontent_subject);
  });

  function showLoading() {
//      $('#search_class_subject').html('<div class="fp-content-center"><img class="icon " alt="" src="http://localhost/flip-moodle-lc/theme/image.php?theme=adaptable&amp;component=core&amp;rev=1549869974&amp;image=i%2Floading_small"></div>');
  }



  function createBookChaperListing(primecontent_class, primecontent_subject) {
    console.log(primecontent_class);
      var html = "";
      var headers = {
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded',
          'loginId': 'vinay2.admin',
          'profileCode': '5800667696',
          'sessionToken': 'u8DQJeoTzc7iVSzkPWjFg8LSC',
          '3dSupport': 1,
          'platform': 'web'
      };

      $.ajax({
          type: "GET",
          headers: headers,
          url: "<?php echo PRIME_URL; ?>/v1/booksChapters?subjectId=" + primecontent_subject + "&ncertEbookEnable=1&topicList=false",
          success: function (bookChapters) {
              var bookChapter = JSON.parse(bookChapters);
              $.each(bookChapter.response, function (bookIndex, bookValue) {
                  html += "<div id='book_id_" + bookValue.bookId + "'>";
                  html += "<div class='book_label'>" + bookValue.bookName + "</div>";
                  $.each(bookValue.chapters, function (chapterIndex, chapterValue) {
                      html += "<div class='chapter_label' onClick='getChapter(" + chapterValue.chapterId + ")'>" + chapterValue.chapterName + "</div>";
                      html += "<div id='chapter_id_" + chapterValue.chapterId + "'></div>"
                  });
                  html += "</div>";
              });
              $('#search_class_subject').html(html);
          }
      });
  }

  function getChapter(chapterId) {
      var html = "";
      var headers = {
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded',
          'loginId': 'vinay2.admin',
          'profileCode': '5800667696',
          'sessionToken': 'u8DQJeoTzc7iVSzkPWjFg8LSC',
          '3dSupport': 1,
          'platform': 'web'
      };
      $.ajax({
          type: "GET",
          headers: headers,
          url: "<?php echo PRIME_URL; ?>/v1/chapterTopics?chapterId=" + chapterId + "&ncertEbookEnable=1",
          success: function (chapterTopics) {
              var chapterTopic = JSON.parse(chapterTopics);
              $.each(chapterTopic.response.topics, function (topicId, topicValue) {
                  html += "<div id='topic_id_" + topicValue.topicId + "'>";
                  html += "<div class='topic_label' onClick='getTopic(" + topicValue.topicId + ")'>" + topicValue.topicName + "</div>";
                  html += "</div>";
              });
              $("#chapter_id_" + chapterId + "").html(html);
          },
      });
  }
  
  function getTopic(topicId) {
      showLoading();
      var html = "";
      var headers = {
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded',
          'loginId': 'vinay2.admin',
          'profileCode': '5800667696',
          'sessionToken': 'u8DQJeoTzc7iVSzkPWjFg8LSC',
          '3dSupport': 1,
          'platform': 'web'
      };
      $.ajax({
          type: "GET",
          headers: headers,
          url: "<?php echo PRIME_URL; ?>/v1/topic?topicId=" + topicId + "&ncertEbookEnable=1&boardCode=CBSE",
          success: function (chapterTopics) {
              var chapterTopic = JSON.parse(chapterTopics);
              console.log(chapterTopic);
              $.each(chapterTopic.response.resources, function (resourcesId, resourcesValue) {
                  html += "<div id='resource_id_" + resourcesValue.resourceId + "' class='resource_box'>";
                  html += "<img src=" + resourcesValue.thumbNail + " class='resource_img'>";
                  html += "<input type='hidden' id='resource_source_" + resourcesValue.resourceId+"' value='" + resourcesValue.thumbnailUrl + "?resourceId=" + resourcesValue.resourceId + "@@MP4@@1@@VDOEN'>";
                  html += "<input type='hidden' id='resource_title_" + resourcesValue.resourceId+"' value='" + resourcesValue.title + "'>";
                  html += "<div class='resource_label' onClick='downloadFile()'>" + resourcesValue.title + "</div>";
                  html += "</div>";
              });
              $("#search_class_subject").html(html);
          },
      });
  }
  
  function downloadFile(resourceId) {
  showLoading();
  var source = $('resource_source_'+resourceId).val();
  var title = $('resource_title_'+resourceId).val();
    var download = {
            repo_id: "8",
            env: "filemanager",
            sourcekey: "<?php echo $sourcekey; ?>",
            source: source,
            title: title+".f4v",
            author: "Admin User",
            itemid: "<?php echo $_REQUEST['itemid']; ?>",
            client_id: "<?php echo $_REQUEST['client_id']; ?>",
            sesskey: "<?php echo $_REQUEST['sesskey']; ?>",
            license: "cc-sa",
            savepath: "/",
            ctx_id:"15050",
            areamaxbytes:-1,
            maxbytes:-1,
            
        }
      $.ajax({
          type: "POST",
          data: download,
          url: "<?php echo $CFG->wwwroot; ?>/repository/repository_ajax.php?action=download",
          success: function (date) {
              console.log(date);
              draftFile();
          },
      });
  }
  
  function draftFile() {
  showLoading();
    var download = {
            itemid: "<?php echo $_REQUEST['itemid']; ?>",
            client_id: "<?php echo $_REQUEST['client_id']; ?>",
            sesskey: "<?php echo $_REQUEST['sesskey']; ?>"
        }
      $.ajax({
          type: "POST",
          data: download,
          url: "<?php echo $CFG->wwwroot; ?>/repository/draftfiles_ajax.php?action=list",
          success: function (date) {
              console.log(date);
          },
      });
  }

//  }, 1000);

</script>
