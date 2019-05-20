function download_view(id, type, username) {
    var fullDate = new Date();
   
    var twoDigitMonth = ((fullDate.getMonth().length+1) === 1)? (fullDate.getMonth()+1) : '' + (fullDate.getMonth()+1);
    var twoDigitDat = (fullDate.getDate()<10 ? '0' : '') + fullDate.getDate();
    /*var twoDigitMin = (fullDate.getMinutes()<10 ? '0' : '') + fullDate.getMinutes();
    var twoDigitHrs = (fullDate.getHours()<10 ? '0' : '') + fullDate.getHours();
    var twoDigitSec = (fullDate.getSeconds()<10 ? '0' : '') + fullDate.getSeconds();*/
    var newdate = twoDigitMonth + "/" + twoDigitDat + "/" + fullDate.getFullYear() + " " + fullDate.toLocaleTimeString();
     //alert(newdate);
    $.ajax({
        type: "POST",
        url: "download_view.php",
        data: {id: id, type: type ,  name:username,'newdate':newdate },
        async: false,
        success: function(result) {
            location.reload();
        }
    })
}


function perma_download(position, value, id, type, data, classid) {
  
    var fullDate = new Date();   
    var twoDigitMonth = ((fullDate.getMonth().length+1) === 1)? (fullDate.getMonth()+1) : '0' + (fullDate.getMonth()+1);
    var newdate = fullDate.getDate() + "/" + twoDigitMonth + "/" + fullDate.getFullYear();
  
    var count = (value.match(/,/g) || []).length;
    var new_entry_concat = '';
    if (position > count) {
        if (count == 0) {
            var new_entry = '0,'.repeat(position - count);
            new_entry_concat = value + '' + new_entry + '' + 1;
        } else {
            var new_entry = ',0'.repeat(position - count);
            var remain_string = new_entry.substr(0, new_entry.length - 1);
            new_entry_concat = value + '' + remain_string + '' + 1;
        }
    } else if (position == count) {
        var remain_string = value.substr(0, value.length - 1);
        var lastChar = value.substr(value.length - 1);
        var lastinc = parseInt(lastChar) + 1;
        new_entry_concat = remain_string + '' + lastinc;
    } else if (position < count) {
        var char_position = 2 * position + 1;
        var res = value.charAt(char_position - 1);
        var lastinc = parseInt(res) + 1;
        new_entry_concat = value.substring(0, char_position - 1) + lastinc + value.substring(char_position);
    }

    $.ajax({
        type: "POST",
        url: "download_view.php",
        data: {id: id, value: new_entry_concat, type: 'perma_download', name: data , 'newdate':newdate },
        async: false,
        success: function(result) {
            location.reload();
        }
    })
    
       
    
}

function perma_view(position, value, id , data, classid) {
    
    var fullDate = new Date();   
    var twoDigitMonth = ((fullDate.getMonth().length+1) === 1)? (fullDate.getMonth()+1) : '0' + (fullDate.getMonth()+1);
    var newdate = fullDate.getDate() + "/" + twoDigitMonth + "/" + fullDate.getFullYear();
    
    var count = (value.match(/,/g) || []).length;
    var new_entry_concat = '';
    if (position > count) {
        if (count == 0) {
            var new_entry = '0,'.repeat(position - count);
            new_entry_concat = value + '' + new_entry + '' + 1;
        } else {
            var new_entry = ',0'.repeat(position - count);
            var remain_string = new_entry.substr(0, new_entry.length - 1);
            new_entry_concat = value + '' + remain_string + '' + 1;
        }
    } else if (position == count) {
        var remain_string = value.substr(0, value.length - 1);
        var lastChar = value.substr(value.length - 1);
        var lastinc = parseInt(lastChar) + 1;
        new_entry_concat = remain_string + '' + lastinc;
    } else if (position < count) {
        var char_position = 2 * position + 1;
        var res = value.charAt(char_position - 1);
        var lastinc = parseInt(res) + 1;
        new_entry_concat = value.substring(0, char_position - 1) + lastinc + value.substring(char_position);
    }

    $.ajax({
        type: "POST",
        url: "download_view.php",
        data: {id: id, value: new_entry_concat, type: 'record_view' , name:data, 'newdate':newdate},
        async: false,
        success: function(result) {
            location.reload();
        }
    })
}
