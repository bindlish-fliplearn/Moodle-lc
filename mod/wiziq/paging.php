<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * Creating paging for classes list
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
//-----------------------making the query for no of records need to be shown per page--------------
function paging_1($sql,$vary="record",$width="575",$course)
{

    global $limit,$offset,$currenttotal,$showed,$last,$align,$CFG;
    if(!empty ($_REQUEST['offset']))
        $offset=$_REQUEST['offset'];
    else $offset=0;
    $showed=$offset+$limit;
    $last=$offset-$limit;
    $result=get_records_sql($sql);

    $currenttotal=count($result);
    $pages=$currenttotal%$limit;
    if($pages==0)
	$pages=$currenttotal/$limit;
    else
    {
	$pages=$currenttotal/$limit;
	$pages=(int)$pages+1;
    }
    for($i=1;$i<=$pages;$i++)
    {
	$pageoff=($i-1)*$limit;
	if($showed==($i*$limit))
	break;
    }
			
    if($currenttotal>1)$vary.="s";
    if($currenttotal>0)
	echo @$display;
    if($CFG->dbtype=="mysql")
    {
        $sql.="  Limit ".$offset.",$limit  ";
    }
    else if($CFG->dbtype=="mssql_n" )
    {
        $uplimit=$offset+$limit;
        $sql.=" WHERE Row between ".($offset+1)." and ".$uplimit;

    }

    return $sql;

}

//------------------------creating the footer of paging---------------
function paging_2($str,$width,$course)
{
    global $currenttotal,$limit,$offset,$showed,$last,$PHP_SELF,$align;

    if($currenttotal>0)
    {
    #### PAGING STARTS
        print "<table  width='$width' cellpadding='2' cellspacing='0' align='right'>";
	print "<tr>";
	#---------------------------------------
        print "<td width='30%' valign='top' align='right'>";
        if($offset>=$limit)
            print "<a href='$PHP_SELF?offset=$last&currenttotal=$currenttotal&course=".$course."' class='pagingtextlink' style='font-size:12px'>Previous</a>&nbsp;&nbsp;&nbsp;&nbsp;";
	if(isset($align))
	{
            print "<span class='astro'>Pic:</span>&nbsp;&nbsp; ";
	}
	else
	{
            print "<span class='gottopage' style='font-size:12px'>Page:</span>&nbsp;&nbsp; ";
	}
	$pages=$currenttotal%$limit;
	if($pages==0)
            $pages=$currenttotal/$limit;
	else
	{
            $pages=$currenttotal/$limit;
            $pages=(int)$pages+1;
	}		
	$m="0";
	for($i=1;$i<=$pages;$i++)
	{
            $pageoff=($i-1)*$limit;
            if($showed==($i*$limit))
            {
		print "<span class'pagingtext' style='font-size:12px'>$i </span>&nbsp;";
            }
            else
            {
		print "<a href='$PHP_SELF?offset=$pageoff&currenttotal=$currenttotal&course=".$course."' class='pagingtextlink' style='font-size:12px'>$i</a>&nbsp;";
            }
            if($m=="29")
            {
		$m="0";
		print "<br>";
            }
		$m++;
	}
	#---------------------------------------
	print "&nbsp;&nbsp;&nbsp;&nbsp;<a href='$PHP_SELF?offset=$showed&currenttotal=$currenttotal&course=".$course."' class='pagingtextlink' style='font-size:12px'>";
	if($showed<$currenttotal)
            print "Next</a>";
	print "</td>";
	#---------------------------------------
	print "</tr>";
	print "</table><br>";
#### PAGING ENDS
    }

}




?>
