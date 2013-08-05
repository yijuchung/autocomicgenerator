<?php session_start();?>
<?php
include("class_mysql.php");

if($_SESSION['username'] == null )
{
	$json['errorCode'] = 20501;
	echo json_encode($json);
	exit;
}

/*
// input
username -> which is fetched from session

// output
book -> array
//// book
filehandle -> book's handler
author -> same as username
filename -> book's name
filedesc -> book's description
createtime -> book's create time
modifiedtime -> last update time of book
*/

$book_dir = "C:\\AppServ\\www\\proxy\\book\\";

$book = array();
$i = 0;
$sql = "SELECT * FROM  `book` WHERE `author` = '".$_SESSION['username']."'";

if( !$result = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}

while($row = mysql_fetch_row($result))
{
    $book[$i]['filehandle'] =  intval($row[0]);
	$book[$i]['author'] = $row[1];
	$book[$i]['filename'] = $row[2];
	$book[$i]['filedesc'] = $row[3];
	$book[$i]['createtime'] = $row[4];
	$book[$i]['modifiedtime'] = $row[5];
	$i++;
}

$json['errorCode'] = 0;
$json['book'] = array();
$json['book'] = array_merge($json['book'], $book);
echo json_encode($json);
?>