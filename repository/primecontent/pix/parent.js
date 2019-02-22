
function hidePopup() {
    $('.moodle-dialogue-lightbox').hide();
    $('.moodle-dialogue').addClass("moodle-dialogue-hidden");
}

function addFile() {
    var html = "";
    html += '<div class="fp-iconview">';
        html += '<div class="fp-file fp-hascontextmenu">';
            html += '<a href="#">';
                html += '<div style="position:relative;">';
                html += '<div class="fp-thumbnail" style="width: 110px; height: 110px;"><img title="Assessing Energy Consumption and Energy Conservation.f4v" alt="Assessing Energy Consumption and Energy Conservation.f4v" src="https://stgmoodlelc.fliplearn.com/theme/image.php?theme=adaptable&amp;component=core&amp;rev=1550218592&amp;image=f%2Fflash-80" style="max-width: 90px; max-height: 90px;"></div>';
                html += '<div class="fp-reficons1"></div>';
                html += '<div class="fp-reficons2"></div>';
                html += '</div>';
                html += '<div class="fp-filename-field">';
                    html += '<div class="fp-filename" style="width: 112px;">Assessing Energy Consumption and Energy Conservation.f4v</div>';
                html += '</div>'
            html += '</a>';
        html += '<a class="fp-contextmenu" href="#">';
            html += '<img class="icon " alt="▶" title="▶" src="https://stgmoodlelc.fliplearn.com/theme/image.php?theme=adaptable&amp;component=core&amp;rev=1550218592&amp;image=i%2Fmenu">';
        html += '</a></div>';
    html += '</div>'
    $('.fm-empty-container').hide();
    $('.fp-content').show();
    $('.fp-content').html(html);
}