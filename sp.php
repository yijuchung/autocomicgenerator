<?php session_start();?>
<?php
include("class_mysql.php");

/*
// input
username -> which is fetched from session
title -> picture's title
tag -> picture's tag
data -> in array "FILES" (POST)

// output
free_space -> this user's free space
time -> picture's upload time
*/

if($_SESSION['username'] == null )
{
	$json['errorCode'] = 20501;
	echo json_encode($json);
	exit;
}
$sql = "SELECT * FROM `project`.`member` WHERE `username` = '".$_SESSION['username']."'";
if( !$result = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}
$row = mysql_fetch_row($result);

$pic_dir = "C:\\AppServ\\www\\proxy\\picture\\";
$title = urldecode($_GET['title']);
$tag = urldecode($_GET['tag']);
$owner = $row[0];

$space_limit = $row[4];
$space_free = $row[5];
$space_used = $row[6];

// change FILES array to be more readable
function fixFilesArray(&$files)
{
    $names = array( 'name' => 1, 'type' => 1, 'tmp_name' => 1, 'error' => 1, 'size' => 1);
    foreach ($files as $key => $part) {
        $key = (string) $key;
        if (isset($names[$key]) && is_array($part)) {
            foreach ($part as $position => $value) {
                $files[$position][$key] = $value;
            }
            unset($files[$key]);
        }
    }
}

fixFilesArray($_FILES['data']);

foreach( $_FILES['data'] as $var )
{
	$filename = $var['name'];
	$data = $var['tmp_name'];
	$datasize = $var['size'];

	if( ($space_used + $datasize/1024) > $space_limit )
	{
		$json['errorCode'] = 40000;
		echo json_encode($json);
		exit;
	}

	if( $filename == "" )
	{
		$json['errorCode'] = 205002;
		echo json_encode($json);
		exit;
	}

	if( $data == "" )
	{
		$json['errorCode'] = 205004;
		echo json_encode($json);
		exit;
	}

	if( $owner == "" )
	{
		$json['errorCode'] = 205005;
		echo json_encode($json);
		exit;
	}
	
	$pi = explode(".",$filename);
	$file = $pi[0];
	$ext = $pi[1];
	
	if($ext == "gif")
	{
		$filename = $file.".png";
	}
	
	$picname = MD5($filename.time());
	$url = "./picture/".$owner."/".$picname;
	
	// gif to png
	if($ext == "gif")
	{
		$gif = imagecreatefromgif($data);
		unlink($data);
		$ret = imagepng($gif, $pic_dir.$owner."/".$picname);
	}else
		move_uploaded_file($data, $pic_dir.$owner."/".$picname);
	
	$sql = "INSERT INTO  `project`.`picture` (`pid` ,`owner` ,`url` ,`title` ,`tag` ,`createtime`,`size`) VALUES (NULL ,  '".$owner."',  '".$url."',  '".$title."',  '".$tag."', CURRENT_TIMESTAMP,'".$datasize."');";

	if( !$che = mysql_query($sql) )
	{
		$json['errorCode'] = 1000;
		echo json_encode($json);
		exit;
	}
	
	$pid = mysql_insert_id();
	$sql = "SELECT  `createtime` FROM  `project`.`picture` WHERE  `pid` =".$pid;
	
	if( !$che = mysql_query($sql) )
	{
		$json['errorCode'] = 1000;
		echo json_encode($json);
		exit;
	}
	$row = mysql_fetch_row($che);
	$time = $row[0];
	
	$space_used = $space_used+$datasize/1024;
	$space_free = $space_free-$datasize/1024;

	$sql = "UPDATE  `project`.`member` SET  `space_used` =  '".$space_used."',`space_free` =  '".$space_free."' WHERE  `member`.`no` =".$owner;

	if( !$che = mysql_query($sql) )
	{
		$json['errorCode'] = 1000;
		echo json_encode($json);
		exit;
	}
	
	if ( !@mkdir($pic_dir.$owner,0777,true) ){
			//echo "mkdir wrong";
	}
	

	$json['pid'] = $json['pid'].",".$pid;
	$json['url'][] = $url;
}

//$json['pid'] = $pid;
$json['errorCode'] = 0;
$json['free_space'] = $space_free;
$json['time'] = $row[0];
echo json_encode($json);
?>