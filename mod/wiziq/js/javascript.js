function ClearForm() {
    document.MyForm.reset();
}
function toggle_visibility(id, id2, id3, id4) {
	var e = document.getElementById(id);
	var e2 = document.getElementById(id2);
	var e3 = document.getElementById(id3);
    var e4 = document.getElementById(id4);
    if (e.style.display == "inline") {
		e.style.display = "none";
		e2.style.display = "inline";e3.value = "";
		if(e4 != null) {e4.value = "";}
	}
}
function check_foldername(id) {
	var text_val = id.value;
    if (text_val == "") {
		alert("Please enter a folder name");
		id.focus();return false;
	}
    var iChars = "^*|,\":<>[]{}`\';()@&$#%"; var ind = text_val.charAt(0);
    if (ind == " ") {
		alert ("First charater cannot be a space");return false;
	}
    for (var i = 0; i < text_val.length; i++) {
		if (iChars.indexOf(text_val.charAt(i)) != -1) {
			alert ("Folder name contains illegal characters");id.focus();return false;
		}
	}
}
function check_filename(id1, id2) {
	var file_val = id1.value;
    if (file_val == "") {
		alert("Please select a file");id1.focus();return false;
	}
    var fileName=file_val;var ext=fileName.substring(fileName.lastIndexOf(".") + 1);
    var exten = ext.toLowerCase();
    if(exten == "doc" || exten == "docx" || exten == "pdf" || exten == "xls"
    || exten == "xlsx" || exten == "ppt" || exten == "pptx" || exten == "pps"
    || exten == "ppsx" || exten == "swf" || exten == "flv" || exten == "mp4"
    || exten == "mov" || exten == "avi" || exten == "mpeg" || exten == "wmv"
    || exten == "wav" || exten == "wma" || exten == "mp3"  || exten == "rtf") {
		return true;
	} else {
		alert("Upload allowed content type only");id1.focus();return false;
	}
	var txt_val = id2.value;var iChars = "*|,\":<>[]{}`\';()@&$#%";
    for (var i = 0; i < txt_val.length; i++){
		if(iChars.indexOf(txt_val.charAt(i)) != -1) {
			alert ("File title contains illegal characters");id2.focus();return false;
		}
	}
}