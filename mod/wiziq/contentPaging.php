<?php
/*
 * wiziq.com Module
 * WiZiQ's Live Class modules enable Moodle users to use WiZiQ�s web based virtual classroom equipped with real-time collaboration tools 
 * Here paging is created for content uploaded
 */
 /**
 * @package mod
 * @subpackage wiziq
 * @author preeti chauhan(preetic@wiziq.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
//----------------to make query with limit of records shown---------------

function paging_1($sql,$vary="record",$width="575",$courseid)
{
    global $limit,$offset,$currenttotal,$showed,$last,$align,$CFG;
    if(!empty($_REQUEST['offset']))
	$offset=$_REQUEST['offset'];
    else
        $offset=0;
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
    else if($CFG->dbtype=="mssql_n" || $CFG->dbtype=="sqlsrv")
    {
        $uplimit=$offset+$limit;
        $sql.=" WHERE Row between ".($offset+1)." and ".$uplimit;
    }
    return $sql;
}
//----------------------creating the paging footer-----------------
function paging_2($str,$width,$strcreate,$course)
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
		print "<a href='$PHP_SELF?offset=$last&currenttotal=$currenttotal&q=".$strcreate."&course=".$course."' class='pagingtextlink'>Previous</a>&nbsp;&nbsp;&nbsp;&nbsp;";
		//print "</td>";
		#---------------------------------------
//		echo "jaijai".$align;
		if(isset($align))
		{
			//print "<td align='center' class='bodytext'>
			print "<span class='astro'>Pic:</span>&nbsp;&nbsp; ";
		}
		else
		{
			//print "<td align='center' class='text'>
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
					print "<a href='$PHP_SELF?offset=$pageoff&currenttotal=$currenttotal&q=".$strcreate."&course=".$course."' style='font-size:12px' class='pagingtextlink'>$i</a>&nbsp;";
				}
				if($m=="29")
				{
					$m="0";
					print "<br>";
					
					//echo "m=".$m;
				}
				$m++;
			}
			//print "</td>";
				#---------------------------------------
		print "&nbsp;&nbsp;&nbsp;&nbsp;<a href='$PHP_SELF?offset=$showed&currenttotal=$currenttotal&q=".$strcreate."&course=".$course."' class='pagingtextlink' style='font-size:12px'>";
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
