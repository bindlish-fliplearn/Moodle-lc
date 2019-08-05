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
    function addReminder(rating,contextId, userId,live = ''){

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
             status:1,
       
        };
        var url = window.location;
        var path = url.host;
        if(url.host == "localhost") {
            path = url.host + "/flip_moodle";
        }
        if(live == 'true'){
            var options = [];
            let promiselist = new Promise(function(resolve, reject) {
                getOptionList(resolve, reject, '');
            });
             promiselist.then(function(data){
                if(data.status == 'true'){

                    if(!$('#checkboxDiv').hasClass('commentShow')){
                        $('#checkboxDiv').removeClass('commentHide');   
                        $('#checkboxDiv').addClass('commentShow');   
                    }

                    for(let i=0 ; i<= data.feedback_options.length-1; i++){
                        var feedback = data.feedback_options[i];
                        if(feedback.rating == rating ){
                            options.push(feedback.feedback_option);
                        }
                    }
                       var optionHtml = '';
                       $('#optionlivedivlist').html('');
                        for (var i = 0; i <= options.length - 1; i++) {
                        var text = options[i];

                        var chkid = 'checkboxid_'+i;
                        var input = 'input_'+i;
                        if(i == options.length-1){
                            optionHtml += "<label onclick = otheroption('"+chkid+"','true') class='containerForCheckBox'>"+text+"<input class = 'checkBoxoption' name='foptionpopup' value = "+i+" id = '"+chkid+"' type='checkbox' ><span class='checkmark'></span></label>";
                        }else{ 
                            optionHtml += "<label class='containerForCheckBox'>"+text+"<input class = 'checkBoxoption' id = '"+chkid+"' type='checkbox' name='foptionpopup' value = "+i+" ><span class='checkmark'></span></label>";
                        }
                        optionHtml += "<input type = 'hidden' value = '"+text+"' id = '"+input+"'>";
                    }
                    $('#checkboxDiv').addClass('checkboxDiv');
                $('#optionlivedivlist').append(optionHtml);
                }
             })

        }

        $.ajax({
            type: "POST",
            data: request,
            url: url.protocol+'//'+path+"/webservice/rest/server.php?wstoken="+wstoken+"&wsfunction=local_flipapi_add_activity_rating&moodlewsrestformat=json",
            success: function (data) {
               if(data.status == 'true'){
                $('#ratingSuccess').html('Thank you for rating the video lesson!');

                //setTimeout(function(){ $('#ratingSuccess').html(''); }, 3000);

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
                status:1,
           
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
                var errMsg = "<div class='texterrormessage'>The optional feedback box is empty.</div>"
                $('#successMsg').html(errMsg);
                $('#successMsg').removeClass('commentHide');      
                $('#successMsg').addClass('commentShow');  
        }
    }
    function getRating(resolve, reject, cm_id){
            var request = {
                cm_id: cm_id,           
            };
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
                     resolve(data); 
                   }else{
                        reject(data); 
                   }
                }
            }); 
    }
    setTimeout(function(){
        $(document).ready(function(){
            var url = window.location;
            var pathName = url.pathname;
            var wiziqpath = '/mod/wiziq/view.php';
            var braincertpath = '/mod/braincert/view.php';
            if(url.host == "localhost") {
                wiziqpath == '/flip_moodle/mod/wiziq/view.php';
                braincertpath = '/flip_moodle/mod/braincert/view.php';
            }
            if(pathName != wiziqpath && pathName != braincertpath ){
                showfeedback(); 
            }
        });
    },3000);

    function getLiveclass(resolve, reject, user_id){
         var request = {
                user_id: 28,
                course_id:'',
                class_id:'',           
            };
            var url = window.location;
            var path = url.host;
            if(url.host == "localhost") {
                path = url.host + "/flip_moodle";
            }
            $.ajax({
                type: "POST",
                data: request,
                url: url.protocol+'//'+path+"/webservice/rest/server.php?wstoken="+wstoken+"&wsfunction=local_flipapi_get_live_classes_for_feedback&moodlewsrestformat=json",
                success: function (data) {
                   if(data){
                     resolve(data); 
                   }else{
                        reject(data); 
                   }
                }
            }); 
    }
     var liveclasses = '';
     var classdata = '';
     var current  = 0;
    function showfeedback(){
            var url = window.location;
                var path = url.host;
                if(url.host == "localhost") {
                    path = url.host + "/flip_moodle";
            }
          $.ajax({
                    type: "get",
                    url:  url.protocol+'//'+path+"/pushnotification/getUserDetails.php",
                    success: function(result){
                                var jsonObj =  JSON.parse(result);
                                console.log('jsonObj',jsonObj)
                                 if(jsonObj){
                                    var classdata = [];
                                    let promiselist = new Promise(function(resolve, reject) {
                                            getLiveclass(resolve, reject, jsonObj.id);
                                    });
                                        promiselist.then(function(data){
                                            if(data.status == 'true'){
                                                classdata = data.liveclass;
                                                //var dataObj = JSON.parse(classdata);
                                                liveclasses = classdata;
                                                if(liveclasses.length > 0){
                                                     var studentData = liveclasses[current];
                                                    studentFeedback(current,studentData,jsonObj.id);
                                                }
                                               
                                            }
                                        })
                                   // var classdata = '{"response":{"status":"true","liveclass":[{"courseid":"103","addreminder":"false","classid":"998539","teachers":[{"id":"631","name":"sushobhan","picture":"https:\/\/guru.fliplearn.com\/user\/pix.php\/631\/f1.jpg"},{"id":"635","name":"Sushobhan","picture":"https:\/\/guru.fliplearn.com\/user\/pix.php\/635\/f1.jpg"}],"title":"26th July: Physics XI (5 PM) - Motion in two dimensions","duration":"140","starton":"04:45 PM, 26 Jul","startin":"1564139700","joinurl":""},{"courseid":"59","addreminder":"false","classid":"998736","teachers":[{"id":"625","name":"Mrinmoy","picture":"https:\/\/guru.fliplearn.com\/user\/pix.php\/625\/f1.jpg"}],"title":"27th July: Maths IX - Lines Angles","duration":"90","starton":"03:45 PM, 27 Jul","startin":"1564222500","joinurl":""},{"courseid":"63","addreminder":"false","classid":"998751","teachers":[{"id":"625","name":"Mrinmoy","picture":"https:\/\/guru.fliplearn.com\/user\/pix.php\/625\/f1.jpg"}],"title":"27th July: Maths X - Arithmetic Progression","duration":"90","starton":"04:45 PM, 27 Jul","startin":"1564226100","joinurl":""},{"courseid":"173","addreminder":"false","classid":"998540","teachers":[{"id":"631","name":"sushobhan","picture":"https:\/\/guru.fliplearn.com\/user\/pix.php\/631\/f1.jpg"},{"id":"635","name":"Sushobhan","picture":"https:\/\/guru.fliplearn.com\/user\/pix.php\/635\/f1.jpg"}],"title":"27th July: Physics XI (7.30 PM) - Motion in One Dimension","duration":"180","starton":"07:15 PM, 27 Jul","startin":"1564235100","joinurl":""}]},"error":null,"warning":null}';
                                   
                                 }
                            }
                        });
    }
      function getOptionList(resolve, reject, rating){
            var request = {
                rating: rating,           
            };
            var url = window.location;
            var path = url.host;
            if(url.host == "localhost") {
                path = url.host + "/flip_moodle";
            }
            $.ajax({
                type: "POST",
                data: request,
                url: url.protocol+'//'+path+"/webservice/rest/server.php?wstoken="+wstoken+"&wsfunction=local_flipapi_get_feedback_options&moodlewsrestformat=json",
                success: function (data) {
                   if(data){
                     resolve(data); 
                   }else{
                        reject(data); 
                   }
                }
            }); 
    }
    function addrating(rating,contextId, userId,status){
        if(status == 1){
              if(!$('#feedbackBox').hasClass('commentShow')){
                 $('#feedbackBox').removeClass('commentHide');   
                    $('#feedbackBox').addClass('commentShow');   
            }
            var addRating = "fa-star";
            var totalStar = 5;
            for (let i = 1; i <= totalStar; i++) { 
                if(i <= rating){
                    $('#liveClassRating_'+i).removeClass('fa-star-o');
                    $('#liveClassRating_'+i).addClass('fa-star');
                }else{
                    $('#liveClassRating_'+i).removeClass('fa-star');
                    $('#liveClassRating_'+i).addClass('fa-star-o');
                }
            }
            $('#ratingCount').val(rating);

            var options = [];
            let promiselist = new Promise(function(resolve, reject) {
                getOptionList(resolve, reject, '');
            });

             promiselist.then(function(data){
                if(data.status == 'true'){
                    for(let i=0 ; i<= data.feedback_options.length-1; i++){
                        var feedback = data.feedback_options[i];
                        if(feedback.rating == rating ){
                            options.push(feedback.feedback_option);
                        }
                    }
                       var optionHtml = '';
                       $('#optiondivlist').html('');
                        for (var i = 0; i <= options.length - 1; i++) {
                        var text = options[i];

                        var chkid = 'checkboxid_'+i;
                        var input = 'input_'+i;
                        if(i == options.length-1){
                            optionHtml += "<label onclick = otheroption('"+chkid+"','false') class='containerForCheckBox'>"+text+"<input class = 'checkBoxoption' name='foption' value = "+i+" id = '"+chkid+"' type='checkbox' ><span class='checkmark'></span></label>";
                        }else{ 
                            optionHtml += "<label class='containerForCheckBox'>"+text+"<input class = 'checkBoxoption' id = '"+chkid+"' type='checkbox' name='foption' value = "+i+" ><span class='checkmark'></span></label>";
                        }
                        optionHtml += "<input type = 'hidden' value = '"+text+"' id = '"+input+"'>";
                    }
                $('#optiondivlist').append(optionHtml);
                }
             })
          //  var options = ['Audio-Video was not clear / consistently','Not satisfied with the teacher','Could not understand the interface / platform','The teacher explained most of the things real good', 'I understood most of what was taught in the class','Some other reason'];

            var request = {
                user_id:userId,
                cm_id: contextId,
                rating: rating,
                feedback: '',
                status :status
           
            };
    }
        if(status ==2){
              var request = {
                user_id:userId,
                cm_id: contextId,
                rating: 'null',
                feedback: '', 
                status :status      
            };
        }
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
                if(status==1)
                $('#ratingSuccess').html('Thank you for rating the video lesson!');
                //setTimeout(function(){ $('#ratingSuccess').html(''); }, 3000);

               }
            }
        });

    }
    function otheroption(id,live =''){
      
        if(live == 'true'){
                if($("#"+id).prop('checked') == true){
                $('#textareaboxlive').addClass('commentShow');  
                $('#textareaboxlive').removeClass('commentHide'); 
            }else{
                $('#textareaboxlive').addClass('commentHide');  
                $('#textareaboxlive').removeClass('commentShow'); 
            }
        }else{
                  if($("#"+id).prop('checked') == true){
                $('#textareabox').addClass('commentShow');  
                $('#textareabox').addClass('span12');   
                $('#textareabox').removeClass('commentHide'); 
            }else{
                $('#textareabox').addClass('commentHide');  
                $('#textareabox').removeClass('span12');   
                $('#textareabox').removeClass('commentShow'); 
            }
        }

    }
    function submitFeedback(contextId,userId,popup){
        var optionsdata = [];
        var i = 1;
        var rating =  '';
        if(popup == 'true'){
             rating =  $('#ratingCount').val();
            $.each($("input[name='foption']:checked"), function(){          
            var id = "input_"+$(this).val();
            var textvalue = $('#'+id).val();
            if($(this).val()==5){
                var feedback =  document.getElementById("feedbackliveClass").value;
                textvalue = textvalue+"-"+feedback;
                    optionsdata.push(textvalue);
            }else{
                optionsdata.push(textvalue);
            }
        });
        }else{
                rating =  $('#starcount').val();
                $.each($("input[name='foptionpopup']:checked"), function(){          
                var id = "input_"+$(this).val();
                var textvalue = $('#'+id).val();
                if($(this).val()==5){
                                    var feedback =  document.getElementById("feedbackliveClasspopup").value;
                    textvalue = textvalue+"-"+feedback;
                        optionsdata.push(textvalue);
                }else{
                    optionsdata.push(textvalue);
                }
            });
        }
      
        //var feedbackstring = JSON.stringify(optionsdata);
        var feedbackstring = optionsdata.toString();
        if(feedbackstring != ''){
            var request = {
                user_id:userId,
                cm_id: contextId,
                rating: rating,
                feedback: feedbackstring,
                status:1
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
                    $('#checkboxDiv').addClass('commentHide');
                    $('#checkboxDiv').removeClass('commentShow');      
                    $('#successMsg').removeClass('commentHide');      
                    $('#successMsg').addClass('commentShow'); 
                }
                }
            }); 
        }else{
                var errMsg = "<div class='texterrormessage'>The optional feedback box is empty.</div>"
                $('#successMsg').html(errMsg);
                $('#successMsg').removeClass('commentHide');      
                $('#successMsg').addClass('commentShow');  
        }
    }

    function studentFeedback(index,studentData,userId){
        if(index >0){
            $('#joinLiveClassNew').remove(); 
        }
        var html = "";
        var startCount = 3;
        var rating = '';
        var cm_id = studentData.cmid;
        for (var i=1; i <=5 ; i++) {    
                rating +="<span class='fa fa-star-o' onclick = addrating("+i+","+cm_id+","+userId+",1) id =liveClassRating_"+i+"></span>";
        }
        var profilePic = studentData.teachers[0].picture;
        var teacherName = studentData.teachers[0].name;
        var teacherCount  = '';

        let promise = new Promise(function(resolve, reject) {
            getRating(resolve, reject, 55215);
        });
        var avgRating = "";
        promise.then(function(data){
           var  avg = data.avgrating;
           if (avg >0) {
                 avgRating = 'Avg Rating : '+avg;
           }
        var url = window.location;
            var path = url.host;
            if(url.host == "localhost") {
                path = url.host + "/flip_moodle";
            }
        var classLink = studentData.classlink;
        var success =   '<div class="success" id="ratingSuccess"></div>';
        html += "<div class='modal liveClass' id='joinLiveClassNew' role='dialog' aria-labelledby='myModalLabel'>";
        html += "<div class='modal-dialog modal-sm' role='document'>";
        html += "<div class='modal-content '>";
        html += "<div class='modal-header promotion-head text-center feedbackHead'><h3 class='modal-title fontregular text-color-purple'>Live Class Feedback ! </h3></div>";
        html += "<h3 class='modal-title fontregular text-color-purple text-center'>"+studentData.title+"</h3></div><div class='modal-body head_bottom'>";
        html += "<div class='head_bottom'>";
        html += "<div class='row-fluid'><div class='span6'><p><span class = 'text-grey'>Starts on:</span> "+studentData.starton+"</p></div>";
        html += "<div class='span6 text-right'><p><span class = 'text-grey'>Duration:</span> "+studentData.duration+" Minutes</p></div></div>";
        html += "<div class='row-fluid m-t-28'><div class='span3'><img src="+profilePic+" class='radius10 img-responsive'></div>";
        html += "<div class='span9'><h4>"+teacherName+"</h4><div><a class ='link' href = "+classLink+" >Class Link</a></div></span></div></div>";
        html += "<div class='row-fluid feedbackRating'><div class = 'span6'>"+avgRating+"</div><div class = 'span6 star liveClassStar text-right'> "+rating+" <input type='hidden' value = '' id='ratingCount' ></div></div>"+success+"";
        html += "<div id = 'feedbackBox' class = 'row-fluid commentHide'><div class='row-fluid checkboxDiv' id='checkboxDiv'><div id = 'optiondivlist'></div><div class='commentHide' id = 'textareabox'><textarea placeholder = '(Optional feedback about the video lesson)' id ='feedbackliveClass' name = 'feedback' rows='4' cols='59'></textarea></div><div class='submitButton span12 text-right'><button type = submit  value = Submit onclick = submitFeedback("+cm_id+","+userId+",'true')>Submit</button></div></div>"
        html += "<div class='row-fluid padding'><div class = 'commentHide' id = 'successMsg'>Feedback successfully submitted ! Happy Learning </div></div></div>";
        html += "<div class='row-fluid text-center' ><input type='button' onclick = 'closePopup("+index+","+userId+")' value='Skip'></div>";
        html += "</div></div></div></div>";
        $('body').append(html);
        $( "#joinLiveClassNew" ).trigger("click");
        });
    }
    function closePopup(index,userId){
        $('#joinLiveClassNew').addClass('fade');

        var nextIndex = eval(index+1);
        if(liveclasses.length > nextIndex){
            setTimeout(function(){
                $('#joinLiveClassNew').removeClass('fade');
                studentData = liveclasses[nextIndex];
                studentFeedback(nextIndex,studentData,userId); 
                console.log('studentData 1',studentData);
            },1000)
        }
        var contextId = liveclasses[index].classid;
        if($('#ratingCount').val() < 1){
           addrating('0',contextId, userId,2); 
        }
    }