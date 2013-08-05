<?php
include('FaceRestClient.php');

/*
// input
handle -> face's handler

// output
img_dace -> every img's number of faces
*/

$cache_dir = "C:\\AppServ\\www\\proxy\\cache\\";

$handle = $_GET['handle'];
$filename = $cache_dir."face_".$handle.".txt";
$facename = $cache_dir."fdone_".$handle.".txt";

if( !file_exists( $facename ) )
{
	$fp=@fopen($filename,"r");
	$img_url = array();
	$i = 0;

	while(!feof($fp))
	{
		$img_url[$i] = fgets( $fp );
		$i++;
	}
	
	$face = new FaceRestClient("cbc77651201df1219a857bd95a106b69", "b6f771cc37072b89cf2cdb7d66a0931f");
	$img_face = array();
	
	for( $l = 0 ; $l < $i ; $l++ )
	{
		$result = $face->faces_detect("http://".$img_url[$l]);
		$img_face[$l] = count($result->{'photos'}[0]->{'tags'});
	}
	
	fclose($fp);
	$jj = json_encode($img_face);
	$fp2=@fopen($facename,"w");
	
	if( !fwrite($fp2,$jj) )
	{
		$json['errorCode'] = 20403;
		echo json_encode($json);
		return false;
	}
	
	fclose($fp2);
}else
{
	$fp=@fopen($facename,"r");
	$jj = fgets($fp);
	fclose($fp);
}

echo $jj;
flush();
?>