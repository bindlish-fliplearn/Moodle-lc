<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * Here all the content uploaded shown with hierarchy of folders
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
 require_once("lib.php");
 require_once($CFG->dirroot .'/course/lib.php');
 require_once($CFG->dirroot .'/lib/blocklib.php');
require_once($CFG->dirroot.'/calendar/lib.php');
require_once ($CFG->dirroot.'/lib/moodlelib.php');
include("contentPaging.php");
require_once("wiziqconf.php");
require_once("cryptastic.php");
require_once("locallib.php");
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
	$courseid=$course;
	
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

   
	 // If a course has been supplied in the URL, change the filters to show that one
    if($urlcourse > 0 && record_exists('course', 'id', $urlcourse)) {
        //require_login($urlcourse, false);

        if($urlcourse == SITEID) {
            // If coming from the site page, show all courses
            $SESSION->cal_courses_shown = calendar_get_default_courses(true);
            calendar_set_referring_course(0);
        }
        else {
			
            // Otherwise show just this one
            $SESSION->cal_courses_shown = $urlcourse;
            calendar_set_referring_course($SESSION->cal_courses_shown);
        }
    }
	 require_login($course, false);
	$navlinks[] = $calendar_navlink;
	$navlinks[] = array('name' => 'WiZiQ Content', 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);
	
	print_header($SITE->shortname.':'.$strwiziqs,$strwiziqs,$navigation, "","", true,"","");
	print_simple_box_start('center', '', '', 5, 'generalbox', "");
	
	?>
<link href="main.css" rel="stylesheet" type="text/css">
<script language="javascript" src="wiziq.js" type="text/javascript"></script>
<form action="managecontent.php" method="post">
<table><tr>
<td width="180px" align="left" valign="top">
<?php
include("sideblock.php");
?>
</td>
<td width="800px">

    <table cellspacing="3" cellpadding="3" border="0" width="100%" style="margin-left: auto; margin-right:100px; font-size:14px;font-family:Arial,Verdana,Helvetica,sans-serif; ">
    <tr><td>
    <input type="hidden" name="refreshCount" id="refreshCount" value=""/>
    
    <table class="files" border="0" cellpadding="2" cellspacing="2" width="660" height="100%;" style="border-bottom:solid 1px #ddd; margin-bottom:10px">
    <tr><td valign="top" align="left" style="font-size:14px; font-weight:bold">Manage Content</td></tr>
    <tr><td valign="top" height="15px"></td></tr>
    <tr>
    <th scope="col" class="header name" align="left" width="330" style="padding-left:10px; font-size:12px">Name</th>
    <th scope="col" class="header size" align="left" valign="top" width="180" style="font-size:12px;" >Status
     <?php
	 
	 $url=curPageURL(); 
	  $urlparam=split("&",$url);
	  $size=sizeof($urlparam);
	  //echo "r=".$urlparam[$size-1]=="refresh=1";
	 //print_r($mn);
$abs= $_SERVER["REQUEST_URI"];
$index=strpos($abs,"?");
if(empty($index))
{
?>
     	<a href="<?php echo curPageURL()."?refresh=1"; ?>" id="hrefRefresh"><img src="images/refresh.jpg" alt="Refresh" title="Refresh" align="top"/></a>
	 <?php 
}
	else if($urlparam[$size-1]=="refresh=1" ) {?>
     	<a href="<?php echo curPageURL(); ?>" id="hrefRefresh"><img src="images/refresh.jpg" alt="Refresh" title="Refresh" align="top"/></a>
	 <?php } else { ?>
     	<a href="<?php echo curPageURL()."&refresh=1"; ?>" id="hrefRefresh"><img src="images/refresh.jpg" alt="Refresh" title="Refresh" align="top"/></a><?php }?>
    </th><th scope="col" width="150" class="header commands" style="font-size:12px">Action</th>
    </tr>
    
<?php
//$_request=array();

//echo curPageURL();
if(!empty($_REQUEST['q']))
{
 parse_str(urldecode(decrypt($_REQUEST['q'])),$_request);

}

//---------------logic to show the subfolders in parent folder upto level 3-----------------
$sublevel=1; // initializing the first level of folders hierarchy
$delstr="";
$currenttotal="";
$offset="";
$cids="";
if(!empty($_REQUEST['currenttotal']))
$currenttotal=$_REQUEST['currenttotal'];

if(!empty($_REQUEST['offset']))
$offset=$_REQUEST['offset'];

$subfolder="0|Content";
if(!empty($_request['s']))
{

$subfolder=$_request['s'];
 $arrayfolder=explode(",",$subfolder);
 //print_r($arrayfolder);
$sublevel=sizeof($arrayfolder)-1;
for($i=0;$i<sizeof($arrayfolder);$i++)
 {
	 
if(!empty($_subfolder))
	 $_subfolder=$_subfolder.",".$arrayfolder[$i];
else
$_subfolder=$arrayfolder[$i];

	$arraystring=explode("|",$arrayfolder[$i]); 
 $delstr='id='.$arraystring[0].'&s='.$_subfolder;	

//echo '<a href="managecontent.php?id='.$arraystring[0].'&s='.$_subfolder.'">'.$arraystring[1].'</a>'.'>>';
if($i<sizeof($arrayfolder)-1)
{

	$msg='id='.$arraystring[0].'&s='.$_subfolder;
	$str =urlencode(encrypt(urlencode($msg)));

	$alink='<a href="managecontent.php?q='.$str.'&course='.$urlcourse.'">'.$arraystring[1].'</a>';
$alink=$alink.'>>';
}
else
$alink=$arraystring[1];
echo $alink;
 }
}
$delstr=urlencode(encrypt(urlencode($delstr)));
/*if(!empty($_REQUEST['c']))
{
$sublevel=$_REQUEST['c']+1;	
}*/
//echo "id in request".$_request['id'];
if(!empty($_request['id']))
{
$id=$_request['id'];
}
else 
$id=0;
if($id==0)
{
$_SESSION['folderSubLevel']=1;	
}

//--------------------------end----------------------------

//---------------------create folder logic----------------------

if(!empty($_POST['btnCreateFolder']) && $_POST['btnCreateFolder'])
{
    $foldername=$_REQUEST['txtFolder'];
    $folderquery="select * from ".$CFG->prefix."wiziq_content where name='".$foldername."' and isdeleted=0 and userid=".$USER->id;
    $folderResult=count_records_sql($folderquery);
    if($folderResult>0)
    {
	$errorMsg="This Folder Name is already in use";
    }
    else
    {
        $wiziq->name=$foldername;
	$wiziq->title="folder";
	$wiziq->description="";
        $wiziq->type=1;
        $wiziq->uploaddatetime=time();
        $wiziq->userid=$USER->id;
        $filepath="";//'<a href="managecontent.php?parentid='.$parentid.'">'.$_REQUEST['parentfoldername'].'</a>/';
        $wiziq->filepath=$filepath;
        $wiziq->parentid=$id;
        insert_record("wiziq_content", $wiziq);
    }
}
 $limit=10; //setting the limit to show content per page
if($CFG->dbtype=="mysql")
{
$query="select * from ".$CFG->prefix."wiziq_content where userid=".$USER->id." and parentid=".$id." and isDeleted=0 order by parentid, filepath, name";
}
else if($CFG->dbtype=="mssql_n" || $CFG->dbtype=="sqlsrv")
{
 $query="Select * from (SELECT ROW_NUMBER() OVER (ORDER BY name)AS Row, * from ".$CFG->prefix."wiziq_content where userid=".$USER->id." and parentid=".$id." and isDeleted=0) as logas";
}
$query=paging_1($query,"","0%",$courseid);

//-------------------------- REFRESH CODE ---------------------------------
if(!empty($_REQUEST['refresh'])&&$_REQUEST['refresh']==1)
{
    $refreshQuery="select * from ".$CFG->prefix."wiziq_content where userid=".$USER->id." and parentid=".$id." and isDeleted=0 and status=1 and type=2";
    $resultRefresh=get_records_sql($refreshQuery);
    if(!empty($resultRefresh))
    {
        $countID=0;
        foreach($resultRefresh as $refreshItem)
        {
            $cids=$cids.",".$refreshItem->contentid;
            $contentTableID[$countID]=$refreshItem->id;
            $countID++;
        }
        $cids=trim($cids,",");
        //reading the content info which is uploaded
        $content = file_get_contents($contentUpload.'?method=contentconversionstatus&cids='.$cids.'');
        $objDOM=wiziq_ReadXML($content);
        $contentTable= $objDOM->getElementsByTagName("content");
        $length =$contentTable->length;
        foreach( $contentTable as $value )
        {
            $conid = $value->getElementsByTagName("id");
            $conid= $conid->item(0)->nodeValue;
            $stat = $value->getElementsByTagName("stat");
            $stat= $stat->item(0)->nodeValue;
            $contentIdToUpdate=$contentTableID[$count];
            $paramUpdate=array('id'=>$contentIdToUpdate,'status'=>$stat);
            echo update_record('wiziq_content', $paramUpdate, $bulk=false);
            $count++;
        }

    }
    //print_r($statusXMLarray);
}

$result=get_records_sql($query);
$totalContents=count_records_sql($query);
if(!empty($totalContents))
{
    foreach($result as $contentArray)
	{
	   if($contentArray->type==2) //file
            {
            ?>
            <tr class="folder"><td align="left" width="330" class="name" style="white-space: nowrap;padding:0 0 10px 10px"><?php echo "<img src=\"images/".$contentArray->icon."\" /> ".$contentArray->title; ?>
            <?php
        }
        else if($contentArray->type==1) //folder
        {
            ?>
            <tr class="folder">
            <td align="left" class="name" width="330" style="white-space: nowrap;padding:0 0 10px 10px"><?php
            $msgtable='id='.$contentArray->id.'&s='.$subfolder.','.$contentArray->id.'|'.$contentArray->name;
            $strtable =urlencode(encrypt(urlencode($msgtable)));
            echo "<img src=\" ".$CFG->pixpath."/f/folder.gif\"  />"." <a href=\"managecontent.php?q=".$strtable."&course=".$urlcourse."\"  >". $contentArray->name."</a>";
        }
        ?></td><td width="180px" valign="top" style="padding:0 0 10px 10px"><?php
            if($contentArray->type==2) //file
             {
                  if($contentArray->status==3)
                    echo 'Not Available';
                  else if ($contentArray->status==2)
                    echo 'Available';
                  else
                    echo 'InProgress';
             }
        ?></td><td class="commands" width="140px" align="center" style="font-size:12px;padding:0 0 10px 10px"><?php
             if($contentArray->type==2)
              { ?>
                 <a href="deleteobject.php?<?php echo "id=".$contentArray->id."&contentid=".$contentArray->contentid."&q=".$delstr."&offset=".$offset."&currenttotal=".$currenttotal."&course=".$urlcourse; ?>" id="hrefDelete" class="ulink" ><span class="ulink">Delete</span></a>
                 <?php
              }
              else if($contentArray->type==1)
              { ?>
                 <a href="deleteobject.php?<?php echo "folderid=".$contentArray->id."&q=".$delstr."&offset=".$offset."&currenttotal=".$currenttotal."&course=".$urlcourse; ?>" id="hrefDelete" class="ulink"><span class="ulink">Delete</span></a>
                 <?php
              } ?>
              </td></tr>
        <?php
    }
}
else
{
?>
<tr><td colspan="3"><center>No files in this folder</center><br /><a href="javascript:history.go(-1)"><p align="center">Click Here To Go Back</p></a></td></tr>
<?php
}
?>
</table>
<table cellspacing="2" cellpadding="2" border="0" width="640">
<tr><td style="font-size:12px">
<?php
$createid='id='.$id.'&s='.$subfolder;
$strcreate =urlencode(encrypt(urlencode($createid)));
if($sublevel<=2)
{
?>
<div style="color:red" id="errorMsg"><?php if(!empty($errorMsg)) { echo $errorMsg; } ?></div>     
<input type="text" id="txtFolder" name="txtFolder" maxlength="20"/> &nbsp; &nbsp; &nbsp;<input type="submit" id="btnCreateFolder" name="btnCreateFolder" value="Make Folder" onclick="return submitForm('<?php echo $strcreate; ?>','<?php echo $urlcourse; ?>');"/>&nbsp; &nbsp; &nbsp; 
<?php
}
?>
<a href="file.php?q=<?php echo $strcreate; ?>&course=<?php echo $urlcourse; ?>">Upload File</a>
</td>
<td>
<?php
$str=""; // footer of paging
paging_2($str,"0%",$strcreate,$courseid);?>
</td></tr>
</table>
</td></tr>
</table>
</td>
</tr>
</table>
</form>
<?php
print_footer();
?>
