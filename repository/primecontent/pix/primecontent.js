var d = new Date();
var month = d.getMonth() + 1;

var currentDate = d.getFullYear() + '/' +
        (month < 10 ? '0' : '') + month + '/' +
        (d.getDate() < 10 ? '0' : '') + (d.getDate());

function getClassList() {
    var classSubjectList = JSON.parse(window.localStorage.getItem('classSubject'));
    if(classSubjectList == null) {
        $.ajax({
            type: "GET",
            url: primeUrl + "/v1/class?boardCode=cbse",
            success: function (data) {
                var objRes = JSON.parse(data);
                var classList = {};
                $("#primecontent_class").html("");
                $("#primecontent_class").append("<option value=''>Select Class</option>");
                $.each(objRes.response, function (classIndex, classValue) {
                    var subjectList = {};
                    $.each(classValue.subjects, function (subjectIndex, subjectValue) {
                        subjectList[subjectIndex] = {
                            subjectName: subjectValue.subjectName,
                            subjectCode: subjectValue.subjectCode
                        };
                    });
                    classList[classValue.classCode] = {
                        classCode: classValue.classCode,
                        className: classValue.className,
                        classId: classValue.classId,
                        subjectList: JSON.stringify(subjectList)
                    };
                    $("#primecontent_class").append("<option value='" + classValue.classCode + "'>" + classValue.className + "</option>");
                });
                window.localStorage.setItem('classSubjectDate', currentDate);
                window.localStorage.setItem('classSubject', JSON.stringify(classList));
            }
        });
    } else {
        $("#primecontent_class").html("");
        $("#primecontent_class").append("<option value=''>Select Class</option>");
        $.each(classSubjectList, function (classIndex, classValue) {
            $("#primecontent_class").append("<option value='" + classValue.classCode + "'>" + classValue.className + "</option>");
        });
    }
}

//Call Class Subject APi.
getClassList();

//On class Change
$('document,body').on('change', '#primecontent_class', function () {
    var classSubjectList = JSON.parse(window.localStorage.getItem('classSubject'));
    var classId = $(this).val();
    var subjectList = JSON.parse(classSubjectList[classId].subjectList);
    $("#primecontent_subject").html("");
    $("#primecontent_subject").append("<option value=''>Select subject</option>");
    $.each(subjectList, function (subjectIndex, subjectValue) {
        $("#primecontent_subject").append("<option value='" + subjectValue.subjectCode + "'>" + subjectValue.subjectName + "</option>");
    });
});

//On class subject form submit
$('document,body').on('click', '#classSubjectButton', function () {
    var primecontent_class = $('#primecontent_class').val();
    var primecontent_subject = $('#primecontent_subject').val();
    showLoading();
    $.ajax({
        type: "GET",
        url: baseUrl + "/repository/primecontent/bookChapter.php?sesskey=" + sesskey + "&itemid=" + itemid + "&client_id=" + client_id + "&subjectId=" + primecontent_subject,
        success: function (chapterTopicsHtml) {
            $("#search_class_subject").html(chapterTopicsHtml);
        }
    });
});

function showLoading() {
    $('#search_class_subject').html('<div class="fp-content-center"><img class="icon " alt="" src="'+baseUrl+'/theme/image.php?theme=adaptable&amp;component=core&amp;rev=1549869974&amp;image=i%2Floading_small"></div>');
}


function getChapter(chapterId) {
    $('#loding_'+chapterId).show();
    var html = "";
    var headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded',
        'loginId': 'vinay2.admin',
        'profileCode': '5800667696',
        'sessionToken': '5geO3FuaOCa4QIxkSeZ6ssTT5',
        '3dSupport': 1,
        'platform': 'web'
    };
    $.ajax({
        type: "GET",
        headers: headers,
        url: primeUrl + "/v1/chapterTopics?chapterId=" + chapterId + "&ncertEbookEnable=1",
        success: function (chapterTopics) {
            $('#loding_'+chapterId).hide();
            var chapterTopic = JSON.parse(chapterTopics);
            if (chapterTopic.response != "") {
                $.each(chapterTopic.response.topics, function (topicId, topicValue) {
                    html += "<div id='topic_id_" + topicValue.topicId + "'>";
                    if(topicValue.tag == null) {
                      html += "<input type='hidden' id='tag_" + topicValue.topicId + "' value='' />";  
                    } else {
                      html += "<input type='hidden' id='tag_" + topicValue.topicId + "' value='"+topicValue.tag.tagKey+"' />";  
                    }
                    html += "<div class='topic_label' onClick='getTopic(" + topicValue.topicId + ")'>" + topicValue.topicName + "</div>";
                    html += "</div>";
                });
            }
            $("#chapter_id_" + chapterId + "").html(html);
        },
    });
}

function getTopic(topicId) {
    var tag = "";
    tag = $('#tag_'+topicId).val();
    showLoading();
    $.ajax({
        type: "GET",
        url: baseUrl + "/repository/primecontent/resoures.php?sesskey=" + sesskey + "&itemid=" + itemid + "&client_id=" + client_id + "&topicId=" + topicId + "&tagKey="+tag,
        success: function (chapterTopics) {
            $("#search_class_subject").html(chapterTopics);
        },
    });
}

function downloadFile(resourceId) {
//    showLoading();
    var source = $('#resource_source_' + resourceId).val();
    var sourcekey = $('#resource_sourcekey_' + resourceId).val();
    var title = $('#resource_title_' + resourceId).val();
    var download = {
        repo_id: "8",
        env: "filemanager",
        sourcekey: sourcekey,
        source: source,
        title: title + ".f4v",
        author: "Admin User",
        itemid: itemid,
        client_id: client_id,
        sesskey: sesskey,
        license: "cc-sa",
        savepath: "/",
        ctx_id: ctx_id,
        areamaxbytes: -1,
        maxbytes: -1,
    };
    $.ajax({
        type: "POST",
        data: download,
        url: baseUrl + "/repository/repository_ajax.php?action=download",
        success: function (data) {
            draftFile();
        }
    });
}

function draftFile() {
//    showLoading();
    var download = {
        itemid: itemid,
        client_id: client_id,
        sesskey: sesskey,
        filepath: '/'
    };
    $.ajax({
        type: "POST",
        data: download,
        url: baseUrl + "/repository/draftfiles_ajax.php?action=list",
        success: function (draftResp) {
            var title = draftResp.list[0].filename;
            var icon = draftResp.list[0].thumbnail;
            parent.hidePopup();
            parent.addFile(title, icon);
        }
    });
}

function preview(resourceId) {
    $('#player_div').show();
    $('#resource_div').hide();
    $('#add_resource').attr('onClick', 'downloadFile('+resourceId+');');
    var cType = $('#resource_cType_'+resourceId).val();
    var headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded',
        'loginId': 'vinay2.admin',
        'profileCode': '5800667696',
        'sessionToken': '5geO3FuaOCa4QIxkSeZ6ssTT5',
        '3dSupport': 1,
        'platform': 'web'
    };
    $.ajax({
        type: "GET",
        headers:headers,
        url: primeUrl + "/resource/url?product=prime&tagKey=null&ncertEbookEnable=1&resourceId="+resourceId,
        success: function (data) {
            var resourse = JSON.parse(data);
            console.log(resourse);
            $('.moodle-dialogue-lightbox').hide();
            $('.moodle-dialogue').hide();
            console.log(cType);
            if(cType == "MP4" || cType == "AVI" || cType == "FLV" || cType == "3d" || cType == "VDOEN") {
            var playerInstance = jwplayer("player");
                playerInstance.setup({
                    width: '620',
                    height: '430',
                    bufferlength: '1',
                    controlbar: 'none',
                    stretching: 'uniform',
                    autostart: 'true',
                    primary: 'flash',
                    hlshtml: true,
                    file: resourse.response.cdnPath,
                    defaultBandwidthEstimate : 240000
                });
            } else {
                var iframe = "<iframe height='430' width='620' src='"+resourse.response.cdnPath+"'></iframe>";
                $('#player').html(iframe);
            }
        }
    });
}

function closePopup() {
    $('#resource_div').show();
    $('#player_div').hide();
    $('#player').html('');
}