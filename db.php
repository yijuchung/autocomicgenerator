<?php session_start();?>
<?php
include("class_mysql.php");

/*
// input
username -> which is fetched from session
filehandle -> handler of books (can be multiple)

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

if( $filehandle == "" )
{
	$json['errorCode'] = 205001;
	echo json_encode($json);
	exit;
}

$fharray = array();
$fharray = explode( ",",$filehandle  );
//print_r($fharray);
$i = count($fharray);
$j = 0;
while( $j != $i )
{
	if( $fharray[$j] == null )
		break;
	$sql = "DELETE FROM `book` WHERE `book`.`filehandle` = ".$fharray[$j];

	if( !$che = mysql_query($sql) )
	{
		$json['errorCode'] = 1000;
		echo json_encode($json);
		exit;
	}

	@unlink($book_dir.$fharray[$j]);
	$j++;
}

$json['filehandle'] = $filehandle;
$json['errorCode'] = 0;
echo json_encode($json);
?>