<?php session_start();?>
<?php
include("class_mysql.php");

/*
// input
username -> which is fetched from session
filehandle -> handler for books
filename -> name of books (optional)
desc -> description of books (optional)

// output
filehandle -> same as input
*/

if($_SESSION['username'] == null )
{
	$json['errorCode'] = 20501;
	echo json_encode($json);
	exit;
}

$book_dir = "C:\\AppServ\\www\\proxy\\book\\";
$filehandle = $_GET['filehandle'];

if( !isset($_GET['filename']) && !isset($_GET['desc']) )
{
	$json['errorCode'] = 20500;
	echo json_encode($json);
	exit;
}

if( isset($_GET['filename']) )
	$filename = $_GET['filename'];
if( isset($_GET['desc']) )
	$desc = $_GET['desc'];

if( $filehandle == "" )
{
	$json['errorCode'] = 205001;
	echo json_encode($json);
	exit;
}

if( isset($filename) )
{
	$sql = "UPDATE  `project`.`book` SET  `filename` =  '".$filename."',`modifiedtime` = NOW( ) WHERE  `book`.`filehandle` ='".$filehandle."'";
	if( !$che = mysql_query($sql) )
	{
		$json['errorCode'] = 1000;
		echo json_encode($json);
		exit;
	}
}

if( isset($desc) )
{
	$sql = "UPDATE  `project`.`book` SET  `desc` =  '".$desc."',`modifiedtime` = NOW( ) WHERE  `book`.`filehandle` ='".$filehandle."'";
	if( !$che = mysql_query($sql) )
	{
		$json['errorCode'] = 1000;
		echo json_encode($json);
		exit;
	}
}

$json['filehandle'] = $filehandle;
$json['errorCode'] = 0;
echo json_encode($json);
?>