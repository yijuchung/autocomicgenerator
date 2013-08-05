<?php
$fontname = $_GET['fontname'];
$fsize = $_GET['fontsize'];
$bold = $_GET['bold'];
$italic = $_GET['italic'];
$text = $_GET['text'];
$text = preg_replace('/%([0-9a-f]{2})/ie', 'chr(hexdec($1))', $text );

function objectToArray( $object )
{
	if( !is_object( $object ) && !is_array( $object ) )
	{
		return $object;
	}
	if( is_object( $object ) )
	{
		$object = get_object_vars( $object );
	}
	return array_map( 'objectToArray', $object );
}

if( file_exists("./font/fonttable") )
{
	$fp = fopen("./font/fonttable","r");

	$jsontable = fread($fp, filesize("./font/fonttable") );

	fclose($fp);
}else
{
	$json['errorCode'] = 30202;
	echo json_encode($json);
	exit;
}

$table = array();
$table = json_decode($jsontable,true);
$table = objectToArray($table);

//print_r($table);

if( $fontname == null )
{
	$json['errorCode'] = 30000;
	echo json_encode($json);
	exit(1);
}

if( $fsize == null )
{
	$json['errorCode'] = 30001;
	echo json_encode($json);
	exit(1);
}

if( $bold == null )
{
	$json['errorCode'] = 30002;
	echo json_encode($json);
	exit(1);
}

if( $italic == null )
{
	$json['errorCode'] = 30003;
	echo json_encode($json);
	exit(1);
}

if( $text == null )
{
	$json['errorCode'] = 30004;
	echo json_encode($json);
	exit(1);
}

//print_r($table);a
$matrix = 0;

if( array_key_exists($fontname,$table) )
{
	switch($bold.$italic)
	{
		case 00:
		break;
		
		case 01:
			if( !isset($table[$fontname]['FontType']['Italic']) )
			{
				$italic = 0;
				$matrix = 1;
			}		
		break;
		case 11:
			if( !isset($table[$fontname]['FontType']['Bold Italic']) )
			{
				$italic = 0;
				$matrix = 1;
			}
		break;
		case 10:		
		break;
	}
}else
{
	$json['errorCode'] = 30203;
	echo json_encode($json);
	exit(1);
}


//--------------parse string----------------------

function arrayspilt ($jstring)
{
  if (mb_strlen ($jstring, 'UTF-8') == 0)
    return array();
 
  $ret  = array ();
  $alen = strlen ($jstring);
  $char = '';
  for ($i = 0; $i < $alen; $i++) {
    $char .= $jstring[$i];
    if (mb_check_encoding ($char, 'UTF-8')) {
      array_push ($ret, $char);
      $char = '';
    }
  }
 
  return $ret;
}

//echo md5($fontname)."<br>";

if( file_exists("./font/".md5($fontname)."_cmap") )
{
	$fp = fopen("./font/".md5($fontname)."_cmap","r");

	$cmstring = fread($fp, filesize("./font/".md5($fontname)."_cmap") );

	fclose($fp);
}else
{
	$json['errorCode'] = 30204;
	echo json_encode($json);
	exit;
}
/*
ob_start();
$cc = new Car();
$yyy = $cc->font($fontname,$text,$bold,$italic,$fsize,$matrix);

$ooo = ob_get_contents();
ob_end_clean();

echo $ooo;
echo "=============================";
//$reo = explode(" ", $ooo);
*/
//print_r($reo);

//echo "start:".$starttime = microtime(true);

$cmap = array();
$cmap = json_decode($cmstring,true);

//print_r($cmap);

$ta = arrayspilt($text);

$tempmaxy = 0;
$car = new Car();

//echo $fontname;

foreach($ta as $key => $char)
{
	//echo $key."/".$char."<br>";
	$unicode = base_convert(bin2hex(iconv("utf-8", "UCS-2BE", $char)), 16, 10);
	//echo $unicode."<br>";
	
	foreach($cmap AS $var)
	{
		//echo $var['start']."/".$var['end']."<br>";
		
		if( $var['start'] > $unicode )
		{
			ob_start();
			$tempmaxy = $car->font("MingLiU",$char,$bold,$italic,$fsize,$matrix);
			$output .= ob_get_contents();
			ob_end_clean();
			//$output .= "==".$char."==";
			//$log .= "N".$char.$startx." ";
			break;
		}
		if( $unicode >= $var['start'] )
		{
			if($unicode <= $var['end'])
			{
				ob_start();
				$tempmaxy = $car->font($fontname,$char,$bold,$italic,$fsize,$matrix);
				$output .= ob_get_contents();
				ob_end_clean();
				//$output .= "==".$char."==";
				//$log .= "Y".$char.$startx." ";
				break;
			}//else
			//{
				//ob_start();
				//$tempmaxy = $car->font("MingLiU",$char,$bold,$italic,$fsize,$matrix);
				//$output .= ob_get_contents();
				//ob_end_clean();
				//$log .= "N".$char.$startx." ";
				//break;
			//}
		}
	}
}

//ob_end_clean();

//echo $output;
$tempmaxy = -$tempmaxy;

$po = explode(" ", $output);

$ii = 0;

//var_dump($po);
//print_r($po);
//echo $temp = ord($po[$ii]);

while(true)
{
	$temp = ord($po[$ii]);
	if( $temp == ord("M") )
	{
			$raw = explode(",", substr($po[$ii],1));
			$raw[1] += $tempmaxy;
			$result .= "M".$raw[0].",".$raw[1]." ";
	}else if( $temp == ord("L") )
	{
			$raw = explode(",", substr($po[$ii],1));
			$raw[1] += $tempmaxy;
			$result .= "L".$raw[0].",".$raw[1]." ";
	}else if( $temp == ord("C") )
	{
			$raw = explode(",", substr($po[$ii],1));
			$raw[1] += $tempmaxy;
			$raw[3] += $tempmaxy;
			$raw[5] += $tempmaxy;
			$result .= "C".$raw[0].",".$raw[1].",".$raw[2].",".$raw[3].",".$raw[4].",".$raw[5]." ";
	}else if( $temp == ord("z") )
	{
			$result .= "z ";
	}
	$ii++;
	
	if( $po[$ii] == null )
		break;
}
//echo $result;
//echo $log;

//echo "end:".$endtime = microtime(true);

//echo "time:".($endtime-$starttime)."<br>";

if( $output == null )
{
	$json['errorCode'] = 30100;
	echo json_encode($json);
	exit(1);
}

$json['errorCode'] = 0;

$json['data'] = $result;
echo json_encode($json);
?>
