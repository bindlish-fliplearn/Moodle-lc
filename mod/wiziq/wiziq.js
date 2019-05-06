/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools
 * This page is container of scheduling page
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//open modal popup for download recording
function PopUp(code)
{
    document.getElementById("divmodal").style.display="block";
    document.getElementById("divmodal").style.visibility="visible";
    document.getElementById("ifrmDownload").src = "downloadrec.php?SessionCode="+code+"&amp;keepThis=true&TB_iframe=true&height=250&width=400";
    PopupShow('divmodal','modalBackground');
    return true;
}
//open virtual class room in new window
function openDetails(Url)
{   
	
	var scheight=screen.height;
	var scwidth=screen.width;
	var w=window.open(Url, null, "left=0,top=0,resize=0, height="+scheight+", width="+scwidth);
	w.focus();
	return false;
}
//if no recording credit, disable the radio button
function DisableRecordClass()
{
	if(document.getElementById("statusrecording")!=null)
	{
	var status=document.getElementById("statusrecording").value;
	if(status==0)
	{
	document.getElementById("rdtypeyes").disabled="disabled";
	document.getElementById("rdtypeno").disabled="disabled";
	}
	}
	else
	{ 
	document.getElementById("yes").disabled="disabled";
	document.getElementById("no").disabled="disabled";	
	}
}
/***************Calendar Script for WiZiQ*****************/
// if two digit year input dates after this year considered 20 century.
var NUM_CENTYEAR = 30;
// is time input control required by default
var BUL_TIMECOMPONENT = false;
// are year scrolling buttons required by default
var BUL_YEARSCROLL = true;
var calendars = [];
var RE_NUM = /^\-?\d+$/;
function calendar2(obj_target)
{
	// assigning methods
	this.gen_date = cal_gen_date2;
	this.gen_time = cal_gen_time2;
	this.gen_tsmp = cal_gen_tsmp2;
	this.prs_date = cal_prs_date2;
	this.prs_time = cal_prs_time2;
	this.prs_tsmp = cal_prs_tsmp2;
	this.popup    = cal_popup2;
	// validate input parameters
	if (!obj_target)
		return cal_error("Error calling the calendar: no target control specified");
	if (obj_target.value == null)
		return cal_error("Error calling the calendar: parameter specified is not valid target control");
	this.target = obj_target;
	this.time_comp = BUL_TIMECOMPONENT;
	this.year_scroll = BUL_YEARSCROLL;
	// register in global collections
	this.id = calendars.length;
	calendars[this.id] = this;
}
function cal_popup2 (str_datetime)
{
	if (str_datetime)
        {
            this.dt_current = this.prs_tsmp(str_datetime);
	}
	else
        {
            this.dt_current = this.prs_tsmp(this.target.value);
            this.dt_selected = this.dt_current;
	}
	if (!this.dt_current)
            return;
	var obj_calwindow = window.open(
            'calendar.html?datetime=' + this.dt_current.valueOf()+ '&id=' + this.id,
            'Calendar', 'width=230,height='+(this.time_comp ? 315 : 250)+
            ',status=no,resizable=no,top=200,left=200,dependent=yes,alwaysRaised=yes'
	);
	obj_calwindow.opener = window;
	obj_calwindow.focus();
}
// timestamp generating function
function cal_gen_tsmp2 (dt_datetime)
{
	return(this.gen_date(dt_datetime) + ' ' + this.gen_time(dt_datetime));
}
// date generating function
function cal_gen_date2 (dt_datetime)
{
	return (
		(dt_datetime.getMonth() < 9 ? '0' : '') + (dt_datetime.getMonth() + 1) + "/"
		+ (dt_datetime.getDate() < 10 ? '0' : '') + dt_datetime.getDate() + "/"
		+ dt_datetime.getFullYear()
	);
}
// time generating function
function cal_gen_time2 (dt_datetime)
{
	return (
		(dt_datetime.getHours() < 10 ? '0' : '') + dt_datetime.getHours() + ":"
		+ (dt_datetime.getMinutes() < 10 ? '0' : '') + (dt_datetime.getMinutes()) + ":"
		+ (dt_datetime.getSeconds() < 10 ? '0' : '') + (dt_datetime.getSeconds())
	);
}

// timestamp parsing function
function cal_prs_tsmp2 (str_datetime)
{
	// if no parameter specified return current timestamp
	if (!str_datetime)
		return (new Date());
	// if positive integer treat as milliseconds from epoch
	if (RE_NUM.exec(str_datetime))
		return new Date(str_datetime);
	// else treat as date in string format
	var arr_datetime = str_datetime.split(' ');
	return this.prs_time(arr_datetime[1], this.prs_date(arr_datetime[0]));
}
// date parsing function
function cal_prs_date2 (str_date)
{
	var arr_date = str_date.split('/');
	if (arr_date.length != 3) return alert ("Invalid date format: '" + str_date + "'.\nFormat accepted is dd-mm-yyyy.");
	if (!arr_date[1]) return alert ("Invalid date format: '" + str_date + "'.\nNo day of month value can be found.");
	if (!RE_NUM.exec(arr_date[1])) return alert ("Invalid day of month value: '" + arr_date[1] + "'.\nAllowed values are unsigned integers.");
	if (!arr_date[0]) return alert ("Invalid date format: '" + str_date + "'.\nNo month value can be found.");
	if (!RE_NUM.exec(arr_date[0])) return alert ("Invalid month value: '" + arr_date[0] + "'.\nAllowed values are unsigned integers.");
	if (!arr_date[2]) return alert ("Invalid date format: '" + str_date + "'.\nNo year value can be found.");
	if (!RE_NUM.exec(arr_date[2])) return alert ("Invalid year value: \'" + arr_date[2] + "\'.\nAllowed values are unsigned integers.");
	var dt_date = new Date();
	dt_date.setDate(1);
	if (arr_date[0] < 1 || arr_date[0] > 12) return alert ("Invalid month value: '" + arr_date[0] + "'.\nAllowed range is 01-12.");
	dt_date.setMonth(arr_date[0]-1);
	if (arr_date[2] < 100) arr_date[2] = Number(arr_date[2]) + (arr_date[2] < NUM_CENTYEAR ? 2000 : 1900);
	dt_date.setFullYear(arr_date[2]);
	var dt_numdays = new Date(arr_date[2], arr_date[0], 0);
	dt_date.setDate(arr_date[1]);
	if (dt_date.getMonth() != (arr_date[0]-1)) return alert ("Invalid day of month value: '" + arr_date[1] + "'.\nAllowed range is 01-"+dt_numdays.getDate()+".");
	return (dt_date)
}
// time parsing function
function cal_prs_time2 (str_time, dt_date)
{
	if (!dt_date) return null;
	var arr_time = String(str_time ? str_time : '').split(':');
	if (!arr_time[0]) dt_date.setHours(0);
	else if (RE_NUM.exec(arr_time[0])) 
	if (arr_time[0] < 24) dt_date.setHours(arr_time[0]);
	else return cal_error ("Invalid hours value: '" + arr_time[0] + "'.\nAllowed range is 00-23.");
	else return cal_error ("Invalid hours value: '" + arr_time[0] + "'.\nAllowed values are unsigned integers.");
	if (!arr_time[1]) dt_date.setMinutes(0);
	else if (RE_NUM.exec(arr_time[1]))
	if (arr_time[1] < 60) dt_date.setMinutes(arr_time[1]);
	else return cal_error ("Invalid minutes value: '" + arr_time[1] + "'.\nAllowed range is 00-59.");
	else return cal_error ("Invalid minutes value: '" + arr_time[1] + "'.\nAllowed values are unsigned integers.");
	if (!arr_time[2]) dt_date.setSeconds(0);
	else if (RE_NUM.exec(arr_time[2]))
	if (arr_time[2] < 60) dt_date.setSeconds(arr_time[2]);
	else return cal_error ("Invalid seconds value: '" + arr_time[2] + "'.\nAllowed range is 00-59.");
	else return cal_error ("Invalid seconds value: '" + arr_time[2] + "'.\nAllowed values are unsigned integers.");
	dt_date.setMilliseconds(0);
	return dt_date;
}
function cal_error (str_message)
{
	alert (str_message);
	return null;
}
	//var cal2 = new calendar2(document.forms["form"].elements["id_date"]);
	//	cal2.year_scroll = false;
	//cal2.time_comp = false;
        // Declaring valid date character, minimum year and maximum year
var dtCh= "/";
var minYear=1900;
var maxYear=2100;
function isInteger(s)
{
    var i;
    for (i = 0; i < s.length; i++)
    {
    // Check that current character is number.
    var c = s.charAt(i);
    if (((c < "0") || (c > "9"))) return false;
    }
    // All characters are numbers.
    return true;
}
function stripCharsInBag(s, bag)
{
    var i;
    var returnString = "";
    // If character is not in bag, append to returnString.
    for (i = 0; i < s.length; i++)
    {
        var c = s.charAt(i);
        if (bag.indexOf(c) == -1) returnString += c;
    }
    return returnString;
}
function daysInFebruary (year)
{
    // February has 29 days in any year evenly divisible by four,
    // EXCEPT for centurial years which are not also divisible by 400.
    return (((year % 4 == 0) && ( (!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28 );
}
function DaysArray(n)
{
	for (var i = 1; i <= n; i++) {
		this[i] = 31
		if (i==4 || i==6 || i==9 || i==11) {this[i] = 30}
		if (i==2) {this[i] = 29}
   } 
   return this
}
function isDate(dtStr)
{
	var daysInMonth = DaysArray(12)
	var pos1=dtStr.indexOf(dtCh)
	var pos2=dtStr.indexOf(dtCh,pos1+1)
	var strMonth=dtStr.substring(0,pos1)
	var strDay=dtStr.substring(pos1+1,pos2)
	var strYear=dtStr.substring(pos2+1)
	strYr=strYear
	if (strDay.charAt(0)=="0" && strDay.length>1) strDay=strDay.substring(1)
	if (strMonth.charAt(0)=="0" && strMonth.length>1) strMonth=strMonth.substring(1)
	for (var i = 1; i <= 3; i++) {
		if (strYr.charAt(0)=="0" && strYr.length>1) strYr=strYr.substring(1)
	}
	month=parseInt(strMonth)
	day=parseInt(strDay)
	year=parseInt(strYr)
	if (pos1==-1 || pos2==-1){
		alert("The date format should be : mm/dd/yyyy")
		return false
	}
	if (strMonth.length<1 || month<1 || month>12){
		alert("Please enter a valid month")
		return false
	}
	if (strDay.length<1 || day<1 || day>31 || (month==2 && day>daysInFebruary(year)) || day > daysInMonth[month]){
		alert("Please enter a valid day")
		return false
	}
	if (strYear.length != 4 || year==0 || year<minYear || year>maxYear){
		alert("Please enter a valid 4 digit year between "+minYear+" and "+maxYear)
		return false
	}
	if (dtStr.indexOf(dtCh,pos2+1)!=-1 || isInteger(stripCharsInBag(dtStr, dtCh))==false){
		alert("Please enter a valid date")
		return false
	}
return true
}
/**************END Claendar Script********************/
String.prototype.cutspace = function()
	{
            return this.replace(/(^\s*)|(\s*$)/g, "");
	}
//validating Date
function ValidateForm(element)
{
	var dt=element;
	var timeUser=document.getElementById("time").value;
	if(timeUser!="")
	{
            timeUser=timeUser.cutspace();
            var Length=timeUser.length;
            var ampm=timeUser.substring((Length-2));
            ampm=ampm.toUpperCase();
            var Time=timeUser.substring(0,(Length-2))
            Time=Time.cutspace();
            var index=Time.indexOf(":");
            var hh="";
            if(index>0)
            {
                hh=Time.substring(0,index);
		if(hh<10)
		hh="0"+parseInt(hh)+Time.substring(index);
		else
		hh=parseInt(hh)+Time.substring(index);
            }
            else
            {
		if(hh<10)
		hh="0"+Time+":00"	
		else
		hh=Time+":00"	
            }
            var UserDateTime=element.value+" "+hh+" "+ampm;
            var MoodleDateTime=document.getElementById("MoodleDateTime").value;
            var Date1 = new Date(MoodleDateTime);
            var Date2 = new Date(UserDateTime);
             if (Date2 < Date1)
             {
            	return false;
             }
	}
	if (isDate(dt.value)==false)
	{
		dt.focus()
		return false
	}
 }
//Validating Time
function IsValidTime(time)
{
	var _qfMsg=''; 
	var timeStr=time.value;
        if(time.disabled=="")
        {
            if(timeStr=="")
            {
                _qfMsg ='Please enter the time ';
		return qf_errorHandler(time, _qfMsg);	
            }
            else if(timeStr != '')
            {
		var timePat = /^((([0-1][0-2]|[1-9]|[0-0][1-9]):([0-5][0-9]))|([0-1][0-2]|[1-9]))((am|pm)|(AM|PM)|( AM| PM)|( am| pm))$/;
		var matchArray = timeStr.match(timePat);
                if (matchArray == null)
                {
                    _qfMsg ='Please enter the correct format of time ';
                    return qf_errorHandler(time, _qfMsg);
                }
                else
                {
                    clearMessage("id_error_time");
		    return true;	
                }
            }
        }
        else
        {
            return true;
        }
}
//Generating Error message
function qf_errorHandler(element, _qfMsg)
{
  div = element.parentNode;
  if (_qfMsg != "") 
  {
  var errorSpan = document.getElementById("id_error_"+element.name);
  if (!errorSpan)
  {
     errorSpan = document.createElement("span");
     errorSpan.id = "id_error_"+element.name;
     errorSpan.className = "perror";
     // element.parentNode.insertBefore(errorSpan, element.parentNode.firstChild);
     element.parentNode.appendChild(errorSpan);
  }
  while (errorSpan.firstChild) {
  errorSpan.removeChild(errorSpan.firstChild);
    }
  errorSpan.appendChild(document.createTextNode(_qfMsg));
  errorSpan.appendChild(document.createElement("br"));
  return false;
  } 
  else
  {
    var errorSpan = document.getElementById("id_error_"+element.name);
    if (errorSpan) {
    errorSpan.parentNode.removeChild(errorSpan);
    }
    if (div.className.substr(div.className.length - 6, 6) == "error") {
    div.className = div.className.substr(0, div.className.length - 6);
    }
    else if (div.className == "error") {
    div.className = "";
    }
    return true;
  }
}
//validating Class name
function validate_mod_wiki_mod_form_name(element)
{
    var _qfMsg="";
    var value = element.value;
    if (value == "")
    {
       	_qfMsg ="Please enter class title";
 	return qf_errorHandler(element, _qfMsg);
    }
    else if(value != "")
    {
	clearMessage("id_error_name");
  	return true;
    }
}
//clearing the error message after error removed
function clearMessage(elementID)
{
    if(document.getElementById(elementID))
    document.getElementById(elementID).innerHTML="";
}
//validating duration of class
function validate_mod_wiki_mod_form_duration(element)
{
    var maxdur=document.getElementById("maxDuration").value;
    var _qfMsg="";
    //value=document.form.duration.value;
    var value=element.value;
    if (value == "") {
    _qfMsg ="Please enter duration";
    return qf_errorHandler(element, _qfMsg);
    }
    else if(value != "")
    {
	if (isNaN(value))
	{
            _qfMsg="Please enter duration in numeric only";
            return qf_errorHandler(element, _qfMsg);
        }
	else
	{
            if(value<30 || value >parseInt(maxdur))
            {
		_qfMsg ="Please enter duration between 30 and "+maxdur;
		 return qf_errorHandler(element, _qfMsg);
            }
            else
            {
		clearMessage("id_error_duration");
	  	return true;
            }
	}
    }
}
//validating date
function validate_mod_wiki_mod_form_date(element)
{
    var _qfMsg='';
    var emailID=element;
    if(element.disabled=="")
    {
	if (emailID.value=="")
	{
            _qfMsg ='Please enter the date ';
            return qf_errorHandler(element, _qfMsg);
	}
	else if(emailID.value != '')
        {
            if (ValidateForm(emailID)==false)
            {
		_qfMsg ='Date/time cannot be less than the current date/time ';
		 return qf_errorHandler(element, _qfMsg);		
            }
            else
            {
                clearMessage("id_error_date");
		return true;
            }
        }
    }
    else
    {
        return true;
    }
}
//validating form
function validate_mod_wiki_mod_form(form)
{
    var isNameValid = validate_mod_wiki_mod_form_name(form.elements["name"]);
    var isDateValid = validate_mod_wiki_mod_form_date(form.elements["date"]);
    var isTimeValid = IsValidTime(form.elements["time"]);
    var isDurationValid = validate_mod_wiki_mod_form_duration(form.elements["duration"]);
    //alert(isNameValid +"="+ isDateValid +"="+ isTimeValid +"="+ isDurationValid);
    return (isNameValid && isDateValid && isTimeValid && isDurationValid);
}
function validate_mod_wiki_mod_form_mode()
{
    var isNameValid = validate_mod_wiki_mod_form_name(document.getElementById("name"));
    var isDateValid = validate_mod_wiki_mod_form_date(document.getElementById("date"));
    var isTimeValid = IsValidTime(document.getElementById("time"));
    var isDurationValid = validate_mod_wiki_mod_form_duration(document.getElementById("duration"));
    //var isGroupSelected=GroupSelected();
    return (isNameValid && isDateValid && isTimeValid && isDurationValid );
}
//if schedule now is checked
function chkScheduleNow(element)
{
    if(element.checked==true)
    {
        document.getElementById("date").disabled="disabled";
	document.getElementById("time").disabled="disabled";
	document.getElementById("rowDate").style.display = "none";
	document.getElementById("rowTime").style.display = "none";

    }
    else if(element.checked==false)
    {
	document.getElementById("date").disabled="";
	document.getElementById("time").disabled="";
	document.getElementById("rowDate").style.display = "";
	document.getElementById("rowTime").style.display = "";
    }
}
function GroupEnable(selected)
{
        if(selected.options[selected.selectedIndex].value=="group")
	{
            document.getElementById("Groups").disabled="";
	}
        else
	document.getElementById("Groups").disabled="disabled";
}
function GroupSelected()
{
	var selected=document.getElementById("eventType");
	var IsGroupSelected=false;
	if(selected.options[selected.selectedIndex].value=="group")
	{
            var i;
            if(document.getElementById("Groups").options[document.getElementById("Groups").selectedIndex].value!=-1)
            {
               IsGroupSelected=true;
               clearMessage("id_error_Groups");
               return IsGroupSelected;
            }
            if(IsGroupSelected==false)
            {
		_qfMsg ="Please select the group";
		 return qf_errorHandler(document.getElementById("Groups"), _qfMsg);	
            }
	}
	else
	{
            clearMessage("id_error_Groups");
            return true;
	}
}
function setValue(value)
{
//alert(value);	
if(value=="Enter Class")
{
    //document.view.action="add_attende.php?eventid=3&id=3&SessionCode=";
    var sess=document.getElementById("SessionCode").value;
    window.open("add_attende.php?SessionCode="+sess);
    return false;
}
return false;
}
//submitting form in managecontent
function submitForm(id,courseid)
{
    //alert(id+','+s);
    if(document.getElementById('txtFolder').value=="")
    {
	document.getElementById('errorMsg').innerHTML="Enter folder name";
	return false;
    }
    var iChars = "!@#$%^&*()+=-[]\\\';,./{}|\":<>?";
    for (var i = 0; i < document.getElementById('txtFolder').value.length; i++)
    {
       if (iChars.indexOf(document.getElementById('txtFolder').value.charAt(i)) != -1)
       {
          document.getElementById('errorMsg').innerHTML="Special characters are not allowed.";
          return false;
       }
    }
    var form = document.forms[0];
    var action = form.action;
    action=action+'?q='+id+'&course='+courseid;
    form.action =action;
    return true;
}
//refresh button click
function refreshlink()
{
    var currentPageUrl=location.href;
    document.getElementById('refreshCount').value="1";
    document.getElementById('hrefRefresh').href=currentPageUrl;
}
//uploading the content
function SubmitUpload(btn)
{
    var btnID=btn;
    var check=CallSubmit();
    if(check==true)
    {
	var iChars = "!@#$%^&*()+=-[]\\\';,./{}|\":<>?";
        for (var i = 0; i < document.getElementById('txtTitle').value.length; i++)
	{
            if (iChars.indexOf(document.getElementById('txtTitle').value.charAt(i)) != -1)
            {
		document.getElementById('errorMsg').innerHTML="Special characters are not allowed.";
		return false;
            }
        }
	//var filePath=document.getElementById('fileupload').value;
   	//var fileName=filePath.substr(filePath.lastIndexOf('\\')+1);
	//document.cookie="title=" +title;
	//document.cookie="Desc=" +document.getElementById("txtDesc").value;
        var ts = new Date().getTime();
        //alert("iframe"+document.getElementById('filename').value);
        document.getElementById('upProgressID').value =ts;
        //alert(document.getElementById('upProgressID').value);
        up_UpdateFormAction(btnID);
        //document.forms["form1"].submit();
	document.form1.submit();
	return true;
    }
    else
    return false;
}
//validating the file uploaded
function CallSubmit()
{
    if(document.getElementById('fileupload').value=="")
    {
	document.getElementById('errorMsg').innerHTML="Choose the file";
	return false;
    }
    else
    {
	var check=checkExtension();
	if(check==0)
	{
            document.getElementById('errorMsg').innerHTML="File type not supported";
            return false;
	}
	return true;
    }
    return true;
}
//checking the extension of uploaded file
function checkExtension()
{
   // for mac/linux, else assume windows
   var fileTypes     = new Array('.ppt','.pptx','.jnt','.rtf','.pps','.pdf','.swf','.doc','.xls','.xlsx','.docx','.ppsx','.flv','.mp3','.wmv','.wav','.wma','.mov','.avi','.mpeg'); // valid filetypes
   var fileName      = document.getElementById('fileupload').value; // current value
   var extension     = fileName.substr(fileName.lastIndexOf('.'), fileName.length);
   var valid = 0;
   for(var i in fileTypes)
   {
     if(fileTypes[i].toUpperCase() == extension.toUpperCase())
     {
        valid = 1;
        break;
     }
   }
   return valid;
}
//var rootUrl="http://192.168.17.57/aGLiveContentAPI/contentmanager.ashx";
//var rootUrl="http://192.168.17.231/aGLiveContentAPI/contentmanager.ashx";
var sessioncode=16326;//26046;
function up_UpdateFormAction(btnID)
{//document.getElementById('upProgressID').value='1281613863469';
    var rootUrl="<?php echo $contentUpload; ?>";
    var form = document.forms[0];
    var action = form.action;
    var re = new RegExp('&?UploadID=[^&]*');
    if (action.match(re)) action = action.replace(re, '');
    var delim;
    if (action.indexOf('?') == action.length-1)
    {
         delim = '';
    }
    else
    {
        delim = '?';
        if (action.indexOf('?') > -1) delim = '&';
    }
    var filename=document.getElementById('fileupload').value;
    var fileupload=filename.substr(filename.lastIndexOf('\\')+1);
    var title=document.getElementById("txtTitle").value;
    if(title=='')
    title=fileupload;
    var folderid=document.getElementById('folderid').value;
    btnID.disabled="disabled";
    form.action = document.getElementById("contentUpload").value+'?method=upload&filename='+fileupload+'&UploadID=' + document.getElementById('upProgressID').value+'&m=o&sessioncode=16326&uc='+document.getElementById("Usercode").value+'&p='+folderid+'&k='+document.getElementById("CustomerKey").value+'&nexturl='+document.getElementById("NextUrl").value+'?t='+title+'|'+document.getElementById("courseid").value;
    //alert(form.action);
}
//validating the type of event selected
function Validate()
{
    if(document.getElementById("type_group").checked==true)
    {
	var IsGroupSelected=false;
	var i;
	for(i = 0; i <document.getElementById("groupid").options.length; i++)
	{
            if(document.getElementById("groupid").options[i].selected==true)
            {
		IsGroupSelected=true;
            	break;
            }
	}
	if(IsGroupSelected)
	{
            document.form1.action="event.php?section="+document.getElementById('section').value+"&sesskey="+document.getElementById('sesskey').value+"&add="+document.getElementById('add').value+"&id="+document.getElementById('id').value+"&module="+document.getElementById('module').value+"&mode="+document.getElementById('mode').value+"&instance="+document.getElementById('instance').value+"&eventtype="+document.getElementById('type').value+"&course="+document.getElementById('course').value;
            return true;
	}
	return false;
     }
     else
     {
	document.form1.action="event.php?section="+document.getElementById('section').value+"&sesskey="+document.getElementById('sesskey').value+"&add="+document.getElementById('add').value+"&id="+document.getElementById('id').value+"&module="+document.getElementById('module').value+"&mode="+document.getElementById('mode').value+"&instance="+document.getElementById('instance').value+"&eventtype="+document.getElementById('type').value+"&course="+document.getElementById('course').value;
	return true;
     }
}
//showing the group multiple
function showMe (it, radio)
{
    var sel = (radio.checked);
    document.getElementById(it).style.display = "block";
    if(it=='sc')
    {
       hideMe('sg',this);
    }
    else if(it=='su')
    {
       hideMe('sg',this);
    }
    else if(it!='sg' && it!='su' && it!='sc')
    {
       hideMe('sg',this);
    }
}
// hiding the group block
function hideMe (it,radio)
{
    document.getElementById(it).style.display = "none";
}