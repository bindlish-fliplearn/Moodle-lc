
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
var allcookies = document.cookie;
cookiearray = allcookies.split(';');
for(var i=0; i<cookiearray.length; i++) {
        name = cookiearray[i].split('=')[0];
        value = cookiearray[i].split('=')[1];
        if(name == 'attemptId'){
            JSReceiver.sendCallbackToApp(value);
            delete_cookie('attemptId');
        }
}
setTimeout(function(){
    $(document).ready(function(){
        jQuery(document).on("click", '.mod_quiz-next-nav', function(event) {
                $val = -1
                JSReceiver.sendCallbackToApp($val);
            });
    });
}, 1000); 

function delete_cookie(name) {
    document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
};