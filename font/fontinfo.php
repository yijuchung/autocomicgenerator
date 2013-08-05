<?php
if( file_exists("fontinfo") )
{
	$fp = fopen("fontinfo","rb");

	$jsondata = fread($fp, filesize("fontinfo") );

	fclose($fp);
}else
{
	$json['errorCode'] = 30200;
	echo json_encode($json);
	exit;
}

echo $jsondata;
?>