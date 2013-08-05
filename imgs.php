<?php session_start();?>
<?php
include("class_mysql.php");

/*
// input
username -> which is fetched from session
imgurls -> img's url

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


//$_SESSION['username'] = "zoo";

$sql = "SELECT * FROM `project`.`member` WHERE `username` = '".$_SESSION['username']."'";
if( !$result = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}

$pic_dir = "./picture/";

function GetImg($url,$uid,$filename)
{
	$pic_dir = "./picture/";
	
	//echo $url."<br>";
	
	//$headers[] = 'Content-type: application/x-www-form-urlencoded;charset=utf8';
	//$headers[] = "Pragma: no-cache";
	
	if(file_exists($dir))
		return filesize($dir);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, trim($url));
	//curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	$content = curl_exec($ch);
	curl_close($ch);
	
	@mkdir($pic_dir.$uid,0777,true);
	    
    if (!$fp=fopen($filename,"wb")) {
	    //$json['errorCode'] = 20100;
        //echo json_encode($json);
        //return false;
    }
    
    if (!fwrite($fp,$content)) {
        //fclose($fp);
		//$json['errorCode'] = 20100;
        //echo json_encode($json);
        //return false;
    }
	
    fclose($fp);
	
	return filesize($filename);
}

$row = mysql_fetch_row($result);


$owner = $row[0];

$space_limit = $row[4];
$space_free = $row[5];
$space_used = $row[6];

$imgs = $_POST['imgurls'];
//echo $imgs."<br>";
$img = explode("\r",$imgs);

//print_r($img);

foreach( $img as $var )
{
	$filename = $pic_dir.$owner."/".MD5($var).substr($var,-4,4);
	$url = "picture/".$owner."/".MD5($var).substr($var,-4,4);
	$imgsize = GetImg($var,$owner,$filename);
	
	if(substr($var,-3,3) == "gif")
	{
		$gif = imagecreatefromgif($filename);
		unlink($filename);
		
		$filename = $pic_dir.$owner."/".MD5($var).".png";
		$url = "picture/".$owner."/".MD5($var).".png";
		imagepng($gif, $filename);
	}

	if( ($space_used + $imgsize/1024) > $space_limit )
	{
		$json['errorCode'] = 40000;
		echo json_encode($json);
		exit;
	}

	if( $owner == "" )
	{
		$json['errorCode'] = 205005;
		echo json_encode($json);
		exit;
	}
	
	$sql = "INSERT INTO  `project`.`picture` (`pid` ,`owner` ,`url` ,`title` ,`tag` ,`createtime`,`size`) VALUES (NULL ,  '".$owner."',  '".$url."',  '".$title."',  '".$tag."', CURRENT_TIMESTAMP,'".$imgsize."');";

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
	
	$space_used = $space_used+$imgsize/1024;
	$space_free = $space_free-$imgsize/1024;

	$sql = "UPDATE  `project`.`member` SET  `space_used` =  '".$space_used."',`space_free` =  '".$space_free."' WHERE  `member`.`no` =".$owner;

	if( !$che = mysql_query($sql) )
	{
		$json['errorCode'] = 1000;
		echo json_encode($json);
		exit;
	}
	
	if( isset($json['pid']) )
		$json['pid'] = $json['pid'].",".$pid;
	else
		$json['pid'] = $pid;
	$json['url'][] = $url;
}

//$json['pid'] = $pid;
$json['errorCode'] = 0;
$json['free_space'] = $space_free;
$json['time'] = $row[0];
echo json_encode($json);
?>