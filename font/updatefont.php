<?php
include 'ttfInfo.class.php';
ini_set( 'memory_limit', '1024M' );

$ttfInfo = new ttfInfo;
$ttfInfo->setFontsDir('c:/font/');
$ttfInfo->readFontsDir();

$i = 0;	
$json = array();

foreach($ttfInfo->array AS $key => $var)
{
	$json[$i] = array();
	
	$pi = pathinfo($key);
	
	$json[$i]['Filename'] = $pi['filename'];
	
	
	if( file_exists("./".MD5($key).".7z") )
		$json[$i]['Url'] = "http://comics.iis.sinica.edu.tw/p/font/".MD5($key).".7z";
	
	$json[$i]["DisplayNames"] = array();
	
	$j = 0;
	foreach ($var AS $key => $data)
	{
		if( count($data) < 2 )
			continue;
		else{
			$data[1] = iconv("UCS-2BE","UTF-8",$data[1]);
			$data[2] = iconv("UCS-2BE","UTF-8",$data[2]);
			//$data[1] = asc2bin($data[1]);
			$json[$i]["DisplayNames"][$j] = array();
			$json[$i]["DisplayNames"][$j]['FontType'] = $data[2];
			$json[$i]["DisplayNames"][$j]["locale"] = $key;
			$json[$i]["DisplayNames"][$j]["FamilyName"] = $data[1];
			//$json[$i]["DisplayNames"][$j]["sid"] = $data["sid"];
			$j++;
			
			if( $key == 1033 )
				$json[$i]["UniFamilyName"] = $data[1];
		}
	}
	$i++;
}

$jsondata['errorCode'] = 0;
$jsondata['fontCount'] = $i;
$jsondata['Fonts'] = array();
$jsondata['Fonts'] = array_merge( $jsondata['Fonts'],$json );
//echo json_encode($jsondata);
//print_r($json);

$fp = fopen("fontinfo","wb");

fwrite($fp, json_encode($jsondata) );

fclose($fp);

$lookuptable = array();

foreach($json AS $var)
{
	$key = $var['UniFamilyName'];
	//$pi = pathinfo($var['Filename']);
	//$lookuptable[$key]['Filename'] = $pi['filename'];
	//$lookuptable[$key] = array();
	//$lookuptable[$var['UniFamilyName']] = $var[''];
	foreach ($var['DisplayNames'] AS $data)
	{
		if( $data['locale'] == 1033 )
		{
			$ff = $data['FontType'];
			$lookuptable[$key]['FontType'][$ff] = $var['Filename'];
		}
	}
}

$fp = fopen("fonttable","wb");

fwrite($fp, json_encode($lookuptable) );

fclose($fp);

//print_r($lookuptable);
//$jsondata['errorCode'] = 0;
//$jsondata['fontCount'] = $i;
//$jsondata['Fonts'] = array();
//$jsondata['Fonts'] = array_merge( $jsondata['Fonts'],$json );
//echo json_encode($jsondata);
?>