var wstoken = '6257f654f905c94b0d0f90fce5b9af31';
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
        var now = new Date();
        var day = ("0" + now.getDate()).slice(-2);
        var month = ("0" + (now.getMonth() + 1)).slice(-2);
        var today = now.getFullYear()+"-"+(month)+"-"+(day) ;

        var html = "";
        html += "<div class='assignedPopup' style='width: 100%; height: 100%; background-color: rgba(0, 0, 0, .6);  position: fixed; z-index: 9999; top: 0px;'>";
        html += "<div class='modalBoxSelectDue'>";
        html += "<a href='#' style='float: right; padding: 10px;' onclick='hideAssignmentPopup()'> close </a>";
        html += "<h3 style='padding: 20px 30px;'>Select Due Date</h3>";
        html += '<input type="date" min="'+today+'" name="assignDate" class="assignDate" style="margin: 0px 20px 0px 30px;"> <i class="fa-calendar fa fa-fw"></i><br><br>';
        html += '<input type="submit" style="margin-left:28px;" onClick="assignHomework();" value="Assign Homework">';
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
        var assignDate = $('.assignDate').val();
        var update = {
            courseId: getUrlParameter('id'),
            uuid: $('#uuid').val(),
            assignDate: assignDate,
            activityId: activity
        };
        console.log(update);
        if(assignDate != '' && typeof assignDate !== undefined){
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
        } else {
            alert('Please select assign date.');
        } 
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

    function showPtmPopup(ptmId="", userId, teacherId, ptmdate, teacherremark="", parentFeed="") {

       var teacherremark = teacherremark.replace(/~/g, "'");
       var parentFeed = parentFeed.replace(/~/g, "'");
        var html = "";
        html += "<input type='hidden' class='ptmId' name='ptmId' value='"+ptmId+"'>"; 
        html += "<input type='hidden' class='userId' name='userId' value='"+userId+"'>"; 
        html += "<input type='hidden' class='teacherId' name='teacherId' value='"+teacherId+"'>"; 
        html += "<div class='ptmPopup' style='width: 100%; height: 100%; background-color: rgba(0, 0, 0, .6);  position: fixed; z-index: 9999; top: 0px;'>";
        html += "<div style='width: 40%; height: auto; background-color: white; position: absolute;left: 30%; top:20%;'>";
        html += "<a href='#0' style='float: right; padding: 10px;' onclick='hideptmPopup()'> close </a>";
        html += "<h3 style='padding: 10px 20px 0px; border-bottom: solid 1px #CCC'>PTM Remark</h3><div class='row' style='padding: 0px 20px;'>";
        html += "<div class='span5 ptm-error' style='text-align:center;  margin-bottom: 20px; color: red;'></div>";
        html += '<div class="span2">Date:</div> <div class="span3"><input type="date" name="ptmdate" class="ptmdate" value="'+ptmdate+'"></div>';
        html += '<div class="span2">Teacher Remark:</div> <div class="span3"><textarea class="teacherremark" name="teacherremark">'+teacherremark+'</textarea></div>';
        html += '<div class="span2">Parent Feedback:</div> <div class="span3"><textarea class="parentFeed" name="parentFeed">'+parentFeed+'</textarea></div>';
        html += '<div class="span2">&nbsp;</div><div class="span3"><input type="submit" onClick="submitPTM();" value="Add PTM"></div>';
        html += "</div></div>";
        html += "</div>";
        $('body').append(html);
    }

    function hideptmPopup() {
        $('.ptmPopup').remove();
    }

    function submitPTM() {
        if(!$('.ptmdate').val()) {
            $('.ptm-error').html("Please Enter ptm date.");
            return false;
        } if(!$('.teacherremark').val()) {
            $('.ptm-error').html("Please Enter teacher remark.");
            return false;
        } if(!$('.parentFeed').val()) {
            $('.ptm-error').html("Please Enter parent feed.");
            return false;
        }

        var request = {
            ptmId: $('.ptmId').val(),
            userId: $('.userId').val(),
            teacherId: $('.teacherId').val(),
            ptmDate: $('.ptmdate').val(),
            teacherRemark: $('.teacherremark').val(),
            parentFeedback: $('.parentFeed').val()
        };
        console.log(request);
        var url = window.location;
        var path = url.host;
        if(url.host == "localhost") {
            path = url.host + "/flip_moodle";
        }
        $.ajax({
            type: "POST",
            data: request,
            url: url.protocol+'//'+path+"/webservice/rest/server.php?wstoken=6257f654f905c94b0d0f90fce5b9af31&wsfunction=local_flipapi_add_update_ptm&moodlewsrestformat=json",
            success: function (data) {
                console.log(data);
                url.reload();
            }
        });   
    }
    function addReminder(rating,contextId, userId, ){
        $('#feedback').val('');
        $('#successMsg').removeClass('commentShow');      
        $('#successMsg').addClass('commentHide'); 

        var addRating = "fa-star";
        var totalStar = 5;
        for (let i = 1; i <= totalStar; i++) { 
            if(i <= rating){
                $('#rating_'+i).removeClass('fa-star-o');
                $('#rating_'+i).addClass('fa-star');
            }else{
                $('#rating_'+i).removeClass('fa-star');
                $('#rating_'+i).addClass('fa-star-o');
            }
        }
        $('#starcount').val(rating);
        if(!$('#commentBox').hasClass('commentShow')){
             $('#commentBox').removeClass('commentHide');   
                $('#commentBox').addClass('commentShow');   
        }
        var request = {
            user_id:userId,
            cm_id: contextId,
            rating: rating,
            feedback: '',
       
        };
        var url = window.location;
        var path = url.host;
        if(url.host == "localhost") {
            path = url.host + "/flip_moodle";
        }
        $.ajax({
            type: "POST",
            data: request,
            url: url.protocol+'//'+path+"/webservice/rest/server.php?wstoken="+wstoken+"&wsfunction=local_flipapi_add_activity_rating&moodlewsrestformat=json",
            success: function (data) {
               if(data.status == 'true'){
                 getRating(contextId);
               }
            }
        });


    }
    function addFeedback(contextId,userId){
        var feedback =  document.getElementById("feedback").value;
        if(feedback != ''){
            var rating =  $('#starcount').val();
            var request = {
                user_id:userId,
                cm_id: contextId,
                rating: rating,
                feedback: feedback,
           
            };
            var url = window.location;
            var path = url.host;
            if(url.host == "localhost") {
                path = url.host + "/flip_moodle";
            }
            $.ajax({
                type: "POST",
                data: request,
                url: url.protocol+'//'+path+"/webservice/rest/server.php?wstoken="+wstoken+"&wsfunction=local_flipapi_add_activity_rating&moodlewsrestformat=json",
                success: function (data) {
                if(data.status == 'true'){
                    var succMsg = "<div class='success'>Feedback successfully submitted ! Happy Learning.</div>"
                    $('#successMsg').html(succMsg);
                    $('#commentBox').addClass('commentHide');
                    $('#commentBox').removeClass('commentShow');      
                    $('#successMsg').removeClass('commentHide');      
                    $('#successMsg').addClass('commentShow'); 
                }
                }
            }); 
        }else{
                var errMsg = "<div class='success texterrormessage'>Please add feedback.</div>"
                $('#successMsg').html(errMsg);
                $('#successMsg').removeClass('commentHide');      
                $('#successMsg').addClass('commentShow');  
        }
    }
    function getRating(cm_id){
            var request = {
                cm_id: cm_id,           
            };
            console.log(request);
            var url = window.location;
            var path = url.host;
            if(url.host == "localhost") {
                path = url.host + "/flip_moodle";
            }
            $.ajax({
                type: "POST",
                data: request,
                url: url.protocol+'//'+path+"/webservice/rest/server.php?wstoken="+wstoken+"&wsfunction=local_flipapi_get_average_rating&moodlewsrestformat=json",
                success: function (data) {
                   if(data.status == 'true'){
                    var avgrating =   'Avg Rating: '+data.avgrating;
                    $('.avg').html(avgrating);
                   }
                }
            }); 
    }
