<?php session_start();?>
<?php
require("class_mysql.php");

/*
// input
username -> which is fetched from session

// output
used_space -> used space
free_space -> free space
time -> query time
pic -> array
////pic
pid -> picture's id
url -> picture's relative url
title -> picture's title
tag -> picture's tag
*/

if($_SESSION['username'] == null )
{
	$json['errorCode'] = 20501;
	echo json_encode($json);
	exit;
}

$sql = "SELECT * FROM member";
if( !$result = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}

$row = mysql_fetch_row($result);
$pic_dir = "C:\\AppServ\\www\\proxy\\picture\\";
$used_space = $row[6];
$free_space = $row[5];
$owner = $row[0];

if( $owner == "" )
{
	$json['errorCode'] = 205005;
	echo json_encode($json);
	exit;
}

$sql = "SELECT  `pid` ,  `url` ,  `title` ,  `tag` FROM  `picture` WHERE  `owner` = ".$owner;

if( !$result = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}

$i = 0;
$pic = array();
while($row = mysql_fetch_row($result))
{
    $pic[$i]['pid'] =  intval($row[0]);
	$pic[$i]['url'] = $row[1];
	$pic[$i]['title'] = $row[2];
	$pic[$i]['tag'] = $row[3];
	//$pic[$i]['time'] = date("Y-m-d H:i:s");
	$i++;
}

$json['errorCode'] = 0;
$json['used_space'] = intval($used_space);
$json['free_space'] = intval($free_space);
$json['time'] = date("Y-m-d H:i:s");
$json['pic'] = array();
$json['pic'] = array_merge($json['pic'], $pic);
echo json_encode($json);
	?>