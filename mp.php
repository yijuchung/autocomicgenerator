<?php session_start();?>
<?php
include("class_mysql.php");

/*
// input
username -> which is fetched from session
pid -> picture's unique id
title -> picture's title (optional)
tag -> picture's tag (optional)

// output
pid -> same as input
*/

if($_SESSION['username'] == null )
{
	$json['errorCode'] = 20501;
	echo json_encode($json);
	exit;
}

$pic_dir = "C:\\AppServ\\www\\proxy\\picture\\";

if( !isset($_GET['pid']) )
{
	$json['errorCode'] = 401001;
	echo json_encode($json);
	exit;
}

if( !isset($_GET['title']) || !isset($_GET['tag']) )
{
	$json['errorCode'] = 401002;
	echo json_encode($json);
	exit;
}

$pid = $_GET['pid'];

if( isset($_GET['title']) )
{
	$title = $_GET['title'];
	$sql = "UPDATE  `project`.`picture` SET  `title` =  '".$title."' WHERE  `picture`.`pid` ='".$pid."'";
}
	
if( isset($_GET['tag']) )
{
	$tag = $_GET['tag'];
	$sql = "UPDATE  `project`.`picture` SET  `tag` =  '".$tag."' WHERE  `picture`.`pid` ='".$pid."'";
}

if( !$che = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}

$json['pid'] = $pid;
$json['errorCode'] = 0;
echo json_encode($json);
?>