<?php session_start();?>
<?php
include("class_mysql.php");

/*
// input
username -> which is fetched from session
filehandle -> handler for books
filename -> book's name
desc -> book's description
data -> in array "FILES" (POST)

// output
filehandle -> prob new one or updated one
*/

if($_SESSION['username'] == null )
{
	$json['errorCode'] = 20501;
	echo json_encode($json);
	exit;
}

$book_dir = "C:\\AppServ\\www\\proxy\\book\\";
$filehandle = intval($_GET['filehandle']);
$filename = urldecode($_GET['filename']);
$desc = urldecode($_GET['desc']);
$data = $_FILES['data']['tmp_name'];
$datasize = $_FILES['data']['size'];

if( $data == "" )
{
	$json['errorCode'] = 205004;
	echo json_encode($json);
	exit;
}

if( $filename == "" )
{
	$json['errorCode'] = 205002;
	echo json_encode($json);
	exit;
}

if( $filehandle == null )
	$sql = "INSERT INTO  `project`.`book` (`filehandle` , `author` ,`filename` ,`desc` ,`createtime` ,`modifiedtime` ,`mode`, `pages`) VALUES (NULL ,'".$_SESSION['username']."' , '".$filename."',  '".$desc."', 
			CURRENT_TIMESTAMP , NOW( ) ,  '', '');";
else
	$sql = "UPDATE  `project`.`book` SET  `filename` =  '".$filename."',`desc` =  '".$desc."',`modifiedtime` = NOW( ) WHERE  `book`.`filehandle` ='".$filehandle."'";

if( !$che = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}
$id = mysql_insert_id();
if( $id != 0 )
	$json['filehandle'] = $id;
else
	$json['filehandle'] = $filehandle;
$json['errorCode'] = 0;
echo json_encode($json);

// save the book's content to the file
if ( !@mkdir($book_dir,0777,true) ){
			//echo "mkdir wrong";
}
$fp2 = fopen( $data,"rb" );
$content = fread( $fp2, $datasize );
fclose($fp2);

if( $id != 0 )
	$fp = fopen( $book_dir.$id,"wb" );
else
	$fp = fopen( $book_dir.$filehandle,"wb" );

if( !fwrite( $fp , $content ) )
{
	$json['errorCode'] = 20502;
	echo json_encode($json);
	exit;
}
fclose( $fp );
?>