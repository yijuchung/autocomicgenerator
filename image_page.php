<?php session_start();?>
<?php
set_time_limit(500);

if($_SESSION['username'] == null )
{
	$json['errorCode'] = 20000;
	echo json_encode($json);
	exit;
}
$time_html = 600;
$time_img = 600;
	
function fetch_html( $url, $ref="" )
{
	$cache_dir = "C:\\AppServ\\www\\proxy\\cache\\";
	$hurl = MD5($url);
	$dmod = ($hurl%10)."\\";
	$filename = $cache_dir.$dmod."html_".$hurl;
	if( !file_exists($filename) )
	{
		if ( !mkdir($cache_dir.$dmod,0777,true) ){	
		}
	
		return GetFromUrl( $url,$filename,$ref );
	}else
	{
		$file_ts = filemtime($filename);
		if( (time() - $file_ts) > $time_html )
			return GetFromUrl( $url,$filename,$ref );
		
		if (!$fp=@fopen($filename,"r")) {
			$json['errorCode'] = 20101;
			echo json_encode($json);
			return false;
		}

		while (!feof($fp))
		{
            $content .= fgets($fp, 1024);
        }
		fclose($fp);
		
		return $content;
	}
}

function GetFromUrl($url, $filename , $ref="",$ip="")
{
	$headers[] = 'Content-type: application/x-www-form-urlencoded;charset=utf8';
	$headers[] = "Pragma: no-cache";
	
	if( ip=="" )
		$ip = rand(1,254).".".rand(1,254).".".rand(1,254).".".rand(1,254);

	$headers[] = "x-forwarded-for: ".$ip;
	$headers[] = "via: 140.109.22.252";
	$ch = curl_init();
	$cookie = "ck_".MD5($url).".txt";
	curl_setopt($ch, CURLOPT_COOKIEFILE, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	$content = curl_exec($ch);
	
	if( $ref !="" )
		curl_setopt($ch, CURLOPT_REFERER, $ref);
        
    if (!$fp=@fopen($filename,"w")) {
	    $json['errorCode'] = 20100;
        echo json_encode($json);
        return false;
    }
    
    if (!@fwrite($fp,$content)) {
        fclose($fp);
		$json['errorCode'] = 20100;
        echo json_encode($json);
        return false;
    }
    fclose($fp);
	curl_close($ch);
	return $content;
}

$cache_dir = "C:\\AppServ\\www\\proxy\\cache\\";

$jsondata = array();
$url = $_GET['url'];

$url = preg_replace('/%([0-9a-f]{2})/ie', 'chr(hexdec($1))', $url );

$content = fetch_html( $url,$r );

$imgmatch = array();
$imgurls = array();

preg_match_all("/<img\ssrc=\"http:\/\/(.[^\"]*)\".[^\>]*>/", $content, $imgmatch);
$imgurls = array_merge($imgurls, $imgmatch[1]);
$fakeip = rand(1,254).".".rand(1,254).".".rand(1,254).".".rand(1,254);
$headers[] = "x-forwarded-for: ".$fakeip;
$headers[] = "via: 140.109.22.252";
$ch = curl_init();
$cookie = "ck_".MD5($url).".txt";
curl_setopt($ch, CURLOPT_COOKIEFILE, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
curl_setopt($ch, CURLOPT_COOKIEJAR, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

$j = 0;
foreach($imgurls as $val)
{
	$iurl = MD5($val);
	$dmod = ($iurl%10)."/";
	$filename = $cache_dir.$dmod."img_".$iurl.".jpg";
	$imgurl = "cache/".$dmod."img_".$iurl.".jpg";
	
	if( file_exists($filename) )
	{
		$jsondata[$j]['img_url'] = $imgurl;
		$jsondata[$j]['rel_url'] = $val;
		$size = getimagesize($filename);
		$jsondata[$j]['img_hei'] = $size[1];
		$jsondata[$j]['img_wid'] = $size[0];
				
		
		$exif = @read_exif_data($jsondata[$j]['img_url'],'EXIF');
		if( $exif != false )
		{
			unset($exif['FileName']);
			unset($exif['SectionsFound']);
			unset($exif['MimeType']);
			unset($exif['FileType']);
			unset($exif['FileDateTime']);
		}else
		{
			$exif = null;
		}
		$jsondata[$j]['img_exif'] = $exif;
		$j++;
		continue;
	}
	curl_setopt($ch, CURLOPT_URL, $val);	
	$content = curl_exec($ch);
    
	if (!$fp=@fopen($filename,"wb")) {
	
		if ( !@mkdir($cache_dir.$dmod,0777,true) ){
		}
		
        $fp=@fopen($filename,"wb");
    }
    
    if (!@fwrite($fp,$content)) {
		$json['errorCode'] = 20200;
        echo json_encode($json);
        fclose($fp);
        return false;
    }
    fclose($fp);
	$size = getimagesize($filename);
	$jsondata[$j]['img_hei'] = $size[1];
	$jsondata[$j]['img_wid'] = $size[0];
	$jsondata[$j]['img_url'] = $imgurl;
	$jsondata[$j]['rel_url'] = $val;
			
	$exif = @read_exif_data($jsondata[$j]['img_url'],'EXIF');
	if( $exif != false )
	{
		unset($exif['FileName']);
		unset($exif['SectionsFound']);
		unset($exif['MimeType']);
		unset($exif['FileType']);
		unset($exif['FileDateTime']);
	}else
	{
		$exif = null;
	}
	$jsondata[$j]['img_exif'] = $exif;

	$j++;	
}

curl_close($ch);

function cmptime($a, $b)
{
	if( $a['img_exif'] == null )
		$aa = 0;
	else
		$aa = $a['img_exif']['DateTime'];
	
	if( $b['img_exif'] == null )
		$bb = 0;
	else
		$bb = $b['img_exif']['DateTime'];
	
    if ($aa == $bb)
	{
        return 0;
    }
    return ($aa < $bb) ? -1 : 1;
}

if( !uasort($jsondata, 'cmptime') )
{
		$json['errorCode'] = 20400;
		echo json_encode($json);
		return false;
}

$www=count($jsondata);
if($www==0)
{
	$json['errorCode'] = 20500;
	echo json_encode($json);
	return false;	
}

$handle = MD5($url);
$fp=@fopen($cache_dir."face_".$handle.".txt","w");
if ( !fwrite($fp,$jsondata[0]['rel_url']) )
{
	$json['errorCode'] = 20402;
	echo json_encode($json);
	return false;
}
for( $k = 1 ; $k < $www ; $k++ )
{
	fwrite($fp,"\n".$jsondata[$k]['rel_url']);
}

fclose($fp);


function are_duplicates($file1, $file2)
{
	$image1_src = @imagecreatefromjpeg($file1);
	$image2_src = @imagecreatefromjpeg($file2);

	list($image1_width, $image1_height) = getimagesize($file1);
	list($image2_width, $image2_height) = getimagesize($file2);
	$image1_small = imagecreatetruecolor(20, 20);
	$image2_small = imagecreatetruecolor(20, 20);
	@imagecopyresampled($image1_small, $image1_src, 0, 0, 0, 0, 
	20, 20, $image1_width, $image1_height);
	@imagecopyresampled($image2_small, $image2_src, 0, 0, 0, 0, 
	20, 20, $image2_width, $image2_height);
	
	imagefilter($image1_small, IMG_FILTER_GRAYSCALE);
	imagefilter($image2_small, IMG_FILTER_GRAYSCALE);
	
	for ($x = 0; $x < 20; $x++) {
		for ($y = 0; $y < 20; $y++) {
			$image1_color = imagecolorsforindex($image1_small, 
			imagecolorat($image1_small, $x, $y));
			$image2_color = imagecolorsforindex($image2_small, 
			imagecolorat($image2_small, $x, $y));
			$difference +=  abs($image1_color['red'] - $image2_color['red']);
		}
	}
	$difference  = $difference/256;
	if ($difference <= 30) {
		return true;
	} else {
		return false;
	}
}

for( $k = 0 ; $k < $www ; $k++ )
{
	if( $k+1 == $www )
	{
		$jsondata[$k]['img_dup'] = false;
		break;
	}
	
	$jsondata[$k]['img_dup'] = are_duplicates( $jsondata[$k]['img_url'],$jsondata[$k+1]['img_url'] );
}

$json['errorCode'] = 0;
$json['handle'] = $handle;
$json['imageCount'] = $j;
$json['data'] = array();
$json['data'] = array_merge( $json['data'],$jsondata );

echo json_encode($json);
flush();
?>