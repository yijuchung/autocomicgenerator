<!-- Include Database connections info. -->
<?php include("class_mysql.php"); ?>

<!-- Verify if user exists for login -->
<?php

/*
// input
us -> username
psw -> password

// output
string
*/

if(isset($_GET['us']) && isset($_GET['psw']))
{
	$us = $_GET['us'];
	$psw = $_GET['psw'];

	if($_SESSION['username'] != null)
	{   
			$sql = "SELECT * FROM member";
			$result = mysql_query($sql);
			while($row = mysql_fetch_row($result))
			{
					 echo "$row[0] - 名字(帳號)：$row[1]<br>";
			}
	}
	else
	{
			echo '您無權限觀看此頁面!';
			echo '<meta http-equiv=REFRESH CONTENT=2;url=client.php>';
	}
}
?>