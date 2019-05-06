<?php
setcookie("query", $_REQUEST['q']);
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ’s web based virtual classroom equipped with real-time collaboration tools 
 * This page is for uploading file content
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
	require_once("../../config.php");
	require_once("lib.php");
	require_once($CFG->dirroot."/course/lib.php");
	require_once($CFG->dirroot.'/calendar/lib.php');
	require_once($CFG->dirroot.'/mod/forum/lib.php');
	require_once ($CFG->dirroot.'/lib/blocklib.php');
	require_once ($CFG->dirroot.'/lib/moodlelib.php');
	require_once("wiziqconf.php");
	require_once("cryptastic.php");
	require_login();

    $sectionreturn = optional_param('sr', '', PARAM_INT);
    $add           = optional_param('add','', PARAM_ALPHA);
    $type          = optional_param('type', '', PARAM_ALPHA);
    $indent        = optional_param('indent', 0, PARAM_INT);
    $update        = optional_param('update', 0, PARAM_INT);
    $hide          = optional_param('hide', 0, PARAM_INT);
    $show          = optional_param('show', 0, PARAM_INT);
    $copy          = optional_param('copy', 0, PARAM_INT);
    $moveto        = optional_param('moveto', 0, PARAM_INT);
    $movetosection = optional_param('movetosection', 0, PARAM_INT);
    $delete        = optional_param('delete', 0, PARAM_INT);
    $course        = optional_param('course', 0, PARAM_INT);
    $groupmode     = optional_param('groupmode', -1, PARAM_INT);
    $duplicate     = optional_param('duplicate', 0, PARAM_INT);
    $cancel        = optional_param('cancel', 0, PARAM_BOOL);
    $cancelcopy    = optional_param('cancelcopy', 0, PARAM_BOOL);
    $eventid = optional_param('id', 0, PARAM_INT);
    $eventtype = optional_param('type', 'select', PARAM_ALPHA);
    $urlcourse = optional_param('course', 0, PARAM_INT);
    $cal_y = optional_param('cal_y');
    $cal_m = optional_param('cal_m');
    $cal_d = optional_param('cal_d');
	
    if(isguest()) {
        // Guests cannot do anything with events
        redirect(CALENDAR_URL.'view.php?view=upcoming&amp;course='.$urlcourse);
    }

    $focus = '';

    if(!$site = get_site()) {
        redirect($CFG->wwwroot.'/'.$CFG->admin.'/index.php');
    }
    if (empty($SITE)) {
        redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
    }


    // Bounds for block widths
    // more flexible for theme designers taken from theme config.php
    $lmin = (empty($THEME->block_l_min_width)) ? 100 : $THEME->block_l_min_width;
    $lmax = (empty($THEME->block_l_max_width)) ? 210 : $THEME->block_l_max_width;
    $rmin = (empty($THEME->block_r_min_width)) ? 100 : $THEME->block_r_min_width;
    $rmax = (empty($THEME->block_r_max_width)) ? 210 : $THEME->block_r_max_width;

    define('BLOCK_L_MIN_WIDTH', $lmin);
    define('BLOCK_L_MAX_WIDTH', $lmax);
    define('BLOCK_R_MIN_WIDTH', $rmin);
    define('BLOCK_R_MAX_WIDTH', $rmax);
	$strwiziq  = get_string("WiZiQ", "wiziq");
	$strwiziqs = get_string("modulenameplural", "wiziq");
	calendar_session_vars();
	$navlinks = array();
	$calendar_navlink = array('name' => $strwiziqs,
                          '',
                          'type' => 'misc');
	if ($SESSION->cal_course_referer != SITEID &&
       ($shortname = get_field('course', 'shortname', 'id', $SESSION->cal_course_referer)) !== false)
        {
        // If we know about the referring course, show a return link and ALSO require login!
        require_login();
        //$nav = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$SESSION->cal_course_referer.'">'.$shortname.'</a> -> '.$nav;
        if (!empty($course)) {
            $course = get_record('course', 'id', $SESSION->cal_course_referer); // Useful to have around
        }
        }
	 require_login($course, false);
	$navlinks[] = $calendar_navlink;
	$navlinks[] = array('name' => 'Upload File', 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);
	
	print_header($course->shortname.':'.$strwiziqs,$course->fullname,$navigation,"","", true,"","");
	print_simple_box_start('center', '', '', 5, 'generalbox', "");
	?>
<link href="main.css" rel="stylesheet" type="text/css">
<script language="javascript" src="wiziq.js" type="text/javascript"></script>
<form id="form1" name="form1" method="post" enctype="multipart/form-data" >
<table><tr><td width="180px" align="left" valign="top">
<?php
include("sideblock.php");
?>
</td><td width="800px">

<table cellspacing="2" cellpadding="2" border="0" width="640" style=" margin-right: auto; margin-left: auto;">
 <tr>
 <td valign="top" align="left">
    <table align="left">
     <tr><td  colspan="2" valign="top" align="left" width="50%" style="font-weight:bold;">Upload Content</td>
     <td></td>
     </tr>
     <tr>
 <td style="height:10px"></td>
 <td style="height:10px"><div style="color:red" id="errorMsg"></div> </td>
 </tr>
 <tr>
 <td valign="top" align="left" style="font-weight:bold; font-size:12px; margin-left:30px; float:left"> 
<label for="file" style="width:65px; float:left">Upload File:</label></td>
<td valign="top" align="left"><input type='hidden' name='upProgressID' id='upProgressID' />

        <input type="file" id="fileupload"  name="fileupload" size='40'/>
</td>
</tr>
<tr>
<td style="height:20px"></td>
</tr>
<tr>
<td align="right" style="font-weight:bold;font-size:12px"><label>Title:</label></td>
<td><input type="text" id="txtTitle" name="txtTitle" maxlength="50" style='width:300px'/></td>

</tr><tr>
<td style="height:20px"></td>
</tr>
<tr>
<td>&nbsp;</td>
<td>
<input type="submit" value="Upload" name="btnupload" id="btnupload" onClick="return SubmitUpload(this);" />
<a href="javascript:history.go(-1)" ><span class="ulink" style="margin-left:13px">Cancel</span></a>
</td>
</tr>   
</table>
</td>
<td align="center">
<div style="font-size:12px; float:left; margin-left:40px; font-weight:bold; margin-top:35px">Supported file formats and Sizes</div>
<div style="color:#818181;font-size:11px;float:left; margin-left:40px;">
<img src="<?php echo $CFG->wwwroot; ?>/mod/wiziq/images/fileformat.gif" />
</div>
</td>
</tr>
<?php ///------------------Logic for decrypting the encrypted url-------------------
if(!empty($_REQUEST['q']))
{
    parse_str(urldecode(decrypt($_REQUEST['q'])),$_request);
}
$id=$_request['id'];
$s=$_request['s'];
$alink="";
if(!empty($s))
{
    $subfolder=$_request['s'];
    $arrayfolder=explode(",",$subfolder);
    $sublevel=sizeof($arrayfolder)-1;
    for($i=1;$i<sizeof($arrayfolder);$i++)
    {
	$arraystring=explode("|",$arrayfolder[$i]); 
	if($i<sizeof($arrayfolder)-1)
        {
            $a=$arraystring[1];
            $alink.=$a.'\\';
        }
        else
            $alink.=$arraystring[1];
    }
}
echo '<input type="hidden" id="folderid" name="folderid" value="'.$alink.'" />
    <input type="hidden" id="contentUpload" name="contentUpload" value="'.$contentUpload.'" />
    <input type="hidden" id="contentUpload" name="contentUpload" value="'.$contentUpload.'" />
    <input type="hidden" id="Usercode" name="Usercode" value="'.$USER->id.'" />
    <input type="hidden" id="CustomerKey" name="CustomerKey" value="'.$customer_key.'" />
    <input type="hidden" id="courseid" name="courseid" value="'.$urlcourse.'" />
    <input type="hidden" id="NextUrl" name="NextUrl" value="'.$CFG->wwwroot.'/mod/wiziq/uploadreturn.php" />';
?>
</table>
</td></tr></table>
</form>
<?php
print_footer();	
?>