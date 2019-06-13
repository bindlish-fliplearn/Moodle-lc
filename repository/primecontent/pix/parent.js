
function hidePopup() {
    $('.moodle-dialogue-lightbox').hide();  
    $('.moodle-dialogue').addClass("moodle-dialogue-hidden");
    $('#page-mod-resource-mod').removeClass("lockscroll");
}

function addFile(title, icon) {
    var html = "";
    html += '<div class="fp-iconview">';
        html += '<div class="fp-file fp-hascontextmenu">';
            html += '<a href="#">';
                html += '<div style="position:relative;">';
                html += '<div class="fp-thumbnail" style="width: 110px; height: 110px;"><img title="'+title+'" alt="'+title+'" src="'+icon+'" style="max-width: 90px; max-height: 90px;"></div>';
                html += '<div class="fp-reficons1"></div>';
                html += '<div class="fp-reficons2"></div>';
                html += '</div>';
                html += '<div class="fp-filename-field">';
                    html += '<div class="fp-filename" style="width: 112px;">'+title+'</div>';
                html += '</div>'
            html += '</a>';
        html += '<a class="fp-contextmenu" href="#">';
            html += '<img class="icon " alt="▶" title="▶" src="'+icon+'">';
        html += '</a></div>';
    html += '</div>'
    $('.fm-empty-container').hide();
    $('.fp-content').show();
    $('.fp-content').html(html);
}
function getAttemptedId(attemptId) {
        window.webkit.messageHandlers.attemptId.postMessage(attemptId);
    }
setTimeout(function(){
    $(document).ready(function(){
        jQuery(document).on("click", 'a.mod_quiz-next-nav', function(event) {
                try{
                    getAttemptedId("");
                }catch(e){
                    try{
                        JSReceiver.sendCallbackToApp("");
                    }catch(ex){
                            try{
                                window.parent.postMessage("", '*');
                            }catch(err){
                                }
                    }
                }
            });
    });
}, 1000); 

var allcookies = document.cookie;
cookiearray = allcookies.split(';');
for(var i=0; i<cookiearray.length; i++) {
        name = cookiearray[i].split('=')[0];
        value = cookiearray[i].split('=')[1];
        if(name.trim() == 'attemptId'){
                try{
                    getAttemptedId(value);
                }catch(e){
                     try{
                        JSReceiver.sendCallbackToApp(value);
                    }catch(ex){
                         try{
                                window.parent.postMessage(value, '*');
                            }catch(err){
                                }
                    }
                }
            delete_cookie('attemptId');
        }
}
function delete_cookie(name) {
    document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
};

function showAssignmentPopup() {
    var html = "";
    html += "<div class='assignedPopup' style='width: 100%; height: 100%; background-color: rgba(0, 0, 0, .6);  position: fixed; z-index: 9999; top: 0px;'>";
    html += "<div style='width: 320px; height: 200px; background-color: white; position: absolute;left: 30%; top:20%;'>";
    html += "<a href='#' style='float: right; padding: 10px;' onclick='hideAssignmentPopup()'> close </a>";
    html += "<h3 style='padding: 20px 30px;'>Select Due Date</h3>";
    html += '<input type="date" name="assignDate" class="assignDate" style="margin: 0px 20px 0px 30px;"> <i class="fa-calendar fa fa-fw"></i><br><br>';
    html += '<input type="submit" style="float: right; margin-right: 20px;" onClick="assignHomework();" value="Assign Homework">';
    html += "</div>";
    html += "</div>";
    $('body').append(html);
}

function hideAssignmentPopup() {
    $('.assignedPopup').remove();
}

function assignHomework() {
    var activity = [];
    $.each($("input[name='homework[]']:checked"), function(){            
        var actid = $(this).val();
        var title = $('#course_title_'+actid).val();
        var module = $('#course_module_'+actid).val();
        activity.push({instanceId: actid, name: title, module: module});
    });
    var update = {
        courseId: getUrlParameter('id'),
        uuid: $('#uuid').val(),
        assignDate: $('.assignDate').val(),
        activityId: activity
    };
    console.log(update);
    var url = window.location;
    var path = url.host;
    if(url.host == "localhost") {
        path = url.host + "/flip-moodle-lc";
    }
    $.ajax({
        type: "POST",
        data: update,
        url: url.protocol+'//'+path+"/webservice/rest/server.php?wstoken=6257f654f905c94b0d0f90fce5b9af31&wsfunction=local_flipapi_upadte_completionexpected_by_id&moodlewsrestformat=json",
        success: function (data) {
            console.log(data);
            url.reload();
        }
    });   
}

function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};

setTimeout(function(){
    $(document).ready(function(){
        $('.assigned').click(function(){
            var count_checked = $("[name='homework[]']:checked").length; // count the checked rows
            if(count_checked == 0) 
            {
                $('.assignedButton').remove();
            }
            if(count_checked == 1) {
                if ($('div').find('.assignedButton').length == 0) {
                    $('.navbar-inner').append('<div class="pull-right breadcrumb-button assignedButton"><a href="#0" onclick="showAssignmentPopup()" class="btn btn-success"> Assign Homework</a></div>');
                }
            }
        })
    });
}, 1000); 