<?php session_start();?>
<?php
include("class_mysql.php");

/*
// input
username -> which is fetched from session
pid -> picture's unique id (can be multiple)

// output
pid -> same as input
free_space -> user's free space
time -> delete time
*/
/*
if($_SESSION['username'] == null )
{
	$json['errorCode'] = 20501;
	echo json_encode($json);
	exit;
}
*/

$_SESSION['username'] = "zoo";

$sql = "SELECT * FROM `project`.`member` WHERE `username` = '".$_SESSION['username']."'";

if( !$result = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}

$row = mysql_fetch_row($result);
$owner = $row[0];
$space_limit = $row[4];
$space_free = $row[5];
$space_used = $row[6];

$base_dir = "C:\\AppServ\\www\\proxy";

if( !isset($_GET['pid']) )
{
	$json['errorCode'] = 401001;
	echo json_encode($json);
	exit;
}

$pid = $_GET['pid'];

$fharray = array();
$fharray = explode(",",$pid);

$i = count($fharray);
$j = 0;
$free_size = 0;
while( $j != $i )
{
	$sql = "SELECT * FROM `picture` WHERE `picture`.`pid` = ".$fharray[$j];
	if( !$result = mysql_query($sql) )
	{
		$json['errorCode'] = 1000;
		echo json_encode($json);
		exit;
	}

	$row = mysql_fetch_row($result);
	$url = $row[2];
	$free_size += $row[6];
	
	$sql = "DELETE FROM `picture` WHERE  `picture`.`pid` = ".$fharray[$j];

	if( !$che = mysql_query($sql) )
	{
		$json['errorCode'] = 1000;
		echo json_encode($json);
		exit;
	}

	if($url == null)
		break;

		$url = ltrim($url,".");
	@unlink($base_dir.$url);
	$j++;
}

$free_size_updated = ($space_free+$free_size/1024);
$sql = "UPDATE  `project`.`member` SET  `space_used` =  '".($space_used-$free_size/1024)."',`space_free` =  '".$free_size_updated."' WHERE  `member`.`no` =".$owner;

if( !$che = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}

$json['pid'] = $pid;
$json['free_space'] = $free_size_updated;
$json['errorCode'] = 0;
$json['time'] = date("Y-m-d H:i:s");
echo json_encode($json);
?>