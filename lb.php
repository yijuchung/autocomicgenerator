<?php session_start();?>
<?php
require("class_mysql.php");

/*
// input
username -> which is fetched from session
filehandle -> handler of books

// output
data -> book's content
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

$fp = @fopen( $book_dir.$filehandle,"r" );

function removeBOM($str = '')
{
   if (substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
       $str = substr($str, 3);
   }
   return $str;
}

if( !$data = @fread($fp, @filesize($book_dir.$filehandle)) )
{
	$json['errorCode'] = 20502;
	echo json_encode($json);
	exit;
}
fclose( $fp );

$data = removeBOM($data);
$json['errorCode'] = 0;
$json['data'] = $data;
echo json_encode($json);

?>