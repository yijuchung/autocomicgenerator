<?php
include("class_mysql.php");

/*
// input
id -> username
pw -> password
pw2 -> password check

// output
string
*/

$id = $_POST['id'];
$pw = $_POST['pw'];
$pw2 = $_POST['pw2'];

if($id != null && $pw != null && $pw2 != null && $pw == $pw2)
{
	$pwm = MD5($pw2);
	$sql = "SELECT * FROM  `member` WHERE `username` = '".$id."'";
	$result = mysql_query($sql);
	$row = mysql_fetch_row($result);

	if( $row[0] != null )
	{
		echo "error !! (username already exist)";
		exit(0);
	}
	
	$sql = "insert into member (username, password) values ('$id', '$pwm')";
	if(mysql_query($sql))
	{
			echo "success !!";
	}
	else
	{
			echo "error !! (database error)";
	}
}

?>