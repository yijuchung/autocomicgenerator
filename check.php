<?php 
$dd = session_id();
if(empty($dd))
{
	session_start();
	$dd = session_id();
}
/*
// input
id -> username
pw -> password

// output
user -> username
sessionid -> session id
*/
include("class_mysql.php");



if($_POST['id'] != null)
	$id = $_POST['id'];

if($_POST['pw'] != null)
	$pw = $_POST['pw'];

$sql = "SELECT * FROM member where username = '".$id."'";
$result = mysql_query($sql);
$row = mysql_fetch_row($result);
$pwm = MD5($pw);

if($id != null && $pw != null && $row[1] == $id && $pwm == $row[2])
{
		$_SESSION['username'] = $id;
        $json['user'] = $_SESSION['username'];
		$json['sessionid'] = $dd;
		$json['errorCode'] = 0;
        echo json_encode($json);
}
else
{
		$json['errorCode'] = 20501;
        echo json_encode($json);
}
?>