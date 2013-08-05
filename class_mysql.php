<?php
$db_server = "localhost";
$db_name = "project";
$db_user = "root";
$db_passwd = "1234";

if(!@mysql_connect($db_server, $db_user, $db_passwd))
{
        $json['errorCode'] = 1001;
        echo json_encode($json);
}

mysql_query("SET NAMES utf8");

if(!@mysql_select_db($db_name))
{
        $json['errorCode'] = 1001;
        echo json_encode($json);
}
?> 