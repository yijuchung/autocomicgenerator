<?php @session_start();?>
<?php
set_time_limit(0);
require '../facebook/facebook.php';
include("class_mysql.php");
include("./proxy/class_proxy.php");
/*
// input
url -> album or page or single picture's url
(facebook)
code -> login to facebook's token

// output
album_title -> album or page's title
album_des -> album or page's description
handle -> unique handler for specific page ( for face detection )
imageCount -> number of images

data -> array of image's meta data
//// data
img_hei -> height
img_wid -> width
rel_url -> img's real url
img_url -> localized img's url
thb_url -> thumb url
img_alt -> img's alt
img_name -> img's name in the album
img_des -> img's description
img_exif -> img's exif
*/

// use proxy or not
$enable_proxy = true;
//error_reporting(E_ALL); 

if($_SESSION['username'] == null )
{
	$json['errorCode'] = 20000;
	echo json_encode($json);
	exit;
}

$username = $_SESSION['username'];

// the time used to decide refresh the cache or not
$time_html = 6000;
$time_img = 6000;

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

// translate object to array
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
	
// translate relative url to absolute url
function format_url($srcurl, $baseurl)
{    
  $srcinfo = parse_url($srcurl);  
  if(isset($srcinfo['scheme'])) {  
    return $srcurl;  
  }  
  $baseinfo = parse_url($baseurl);  
  $url = $baseinfo['scheme'].'://'.$baseinfo['host'];  
  if(substr($srcinfo['path'], 0, 1) == '/') {  
    $path = $srcinfo['path'];  
  }else{  
    $path = dirname($baseinfo['path']).'/'.$srcinfo['path'];  
  }  
  $rst = array();  
  $path_array = explode('/', $path);  
  if(!$path_array[0]) {  
    $rst[] = '';  
  }  
  foreach ($path_array AS $key => $dir) {  
    if ($dir == '..') {  
      if (end($rst) == '..') {  
        $rst[] = '..';  
      }elseif(!array_pop($rst)) {  
        $rst[] = '..';  
      }  
    }elseif($dir && $dir != '.') {  
      $rst[] = $dir;  
    }  
   }  
  if(!end($path_array)) {  
    $rst[] = '';  
  }  
  $url .= implode('/', $rst);  
  return str_replace('\\', '/', $url); 
}

// fetch only first html
function fetch_html( $url, $ref="" )
{
	$cache_dir = "C:\\AppServ\\www\\proxy\\cache\\";
	$hurl = MD5($url);
	$dmod = ($hurl%10)."\\";
	$filename = $cache_dir.$dmod."html_".$hurl;
	
	// check cache
	if( !file_exists($filename) )
	{
		if ( !@mkdir($cache_dir.$dmod,0777,true) ){
			// dir alreay exist
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
			exit(1);
		}

		while (!feof($fp))
		{
            $content .= fgets($fp, 1024);
        }
		fclose($fp);
		
		return $content;
	}
}

// fetch pages per photo including specific ref and ip
function GetFromUrl($url, $filename , $ref="",$ip="")
{
	$headers[] = 'Content-type: application/x-www-form-urlencoded;charset=utf8';
	$headers[] = "Pragma: no-cache";
	
	if( ip=="" )
		$ip = rand(1,254).".".rand(1,254).".".rand(1,254).".".rand(1,254);
		
	$ch = curl_init();
	$cookie = "ck_".MD5($url).".txt";
	curl_setopt($ch, CURLOPT_COOKIEFILE, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	$content = curl_exec($ch);
	
	if( $ref !="" )
		curl_setopt($ch, CURLOPT_REFERER, $ref);
        
    if (!$fp=@fopen($filename,"w")) {
	    $json['errorCode'] = 20100;
        echo json_encode($json);
        exit(1);
    }
    
    if (!@fwrite($fp,$content)) {
        fclose($fp);
		$json['errorCode'] = 20100;
        echo json_encode($json);
        exit(1);
    }
	
    fclose($fp);
	curl_close($ch);
	return $content;
}

// basic configuration
$cache_dir = "C:\\AppServ\\www\\proxy\\cache\\";
$jsondata = array();
$url = $_GET['url'];
$url = ltrim($url);
$url = preg_replace('/%([0-9a-f]{2})/ie', 'chr(hexdec($1))', $url );

$baseurl = parse_url($url, PHP_URL_PATH);
$url_ext = pathinfo($baseurl,PATHINFO_EXTENSION);

// all meta file cache for accelerating the process
$meta_dir = "C:\\AppServ\\www\\proxy\\meta\\";
$murl = MD5($url);
$mmod = ($murl%10)."\\";
$filename = $meta_dir.$mmod."meta_".$murl;

if( file_exists($filename) )
{
	if (!$fp=@fopen($filename,"r")) {
		$json['errorCode'] = 20101;
		echo json_encode($json);
		exit(1);
	}
	while (!feof($fp))
	{
           $content .= fgets($fp, 1024);
    }
	fclose($fp);
	echo $content;
	exit();
}

// for the case that only fetch one photo or image
if( $url_ext == "jpg" || $url_ext == "png" || $url_ext == "gif")
{
	$hurl = MD5($url);
	$dmod = ($hurl%10)."\\";
	$filename = $cache_dir.$dmod."img_".$hurl.".".$url_ext;
	$imgurl = "cache/".$dmod."img_".$hurl.".".$url_ext;
	
	if( !file_exists($filename) )
	{
		if ( !@mkdir($cache_dir.$dmod,0777,true) ){
		}
		GetFromUrl( $url,$filename,$ref );
	}
	
	if($url_ext == "gif")
	{
		$gif = imagecreatefromgif($filename);
		unlink($filename);
		
		$url_ext = "png";
		$filename = $cache_dir.$dmod."img_".$hurl.".".$url_ext;
		$imgurl = "cache/".$dmod."img_".$hurl.".".$url_ext;
		$ret = imagepng($gif, $filename);
	}
	
	$exif = @read_exif_data($filename,'EXIF');
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
	$size = getimagesize($filename);
	
	$json['errorCode'] = 0;
	$json['img_url'] = $imgurl;
	$json['img_hei'] = $size[1];
	$json['img_wid'] = $size[0];
	$json['exif'] = $exif;
	//$json['album_title'] = $album_title;
	//$json['album_des'] = $album_des;
	//$json['handle'] = $handle;
	$json['imageCount'] = 1;
	
	echo json_encode($json);
	exit;
}

// design which album it belongs to
if( preg_match( "/wretch/",$url, $urlmatch ) )
{
	$albumType = 1;
}else if( preg_match( "/flickr/",$url, $urlmatch ) )
{
	$albumType = 2;
	$r = "http://www.flickr.com";
}else if( preg_match( "/pixnet/",$url, $urlmatch ) )
{
	$albumType = 3;
	$r = "http://www.pixnet.net";
}else if( preg_match( "/picasa/",$url, $urlmatch ) )
{
	$albumType = 4;
	$r = "http://picasaweb.google.com/";
}else if( preg_match( "/xuite/",$url, $urlmatch ) )
{
	$albumType = 7;
	$r = "http://photo.xuite.net/";
}else if( preg_match( "/facebook/",$url, $urlmatch ) )
{
	$facebook = new Facebook(array(
		'appId'  => '166506083896',
		'secret' => '13dc1a449fbc62759cb9e29333500db8',
		'cookie' => true,
	));

	$code = $_GET['code'];
	
	if( $code != null )
	{
		// after got permission
		$chh = curl_init();
		$cookie = "fb_".MD5($url).".txt";
		curl_setopt($chh, CURLOPT_COOKIEFILE, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
		curl_setopt($chh, CURLOPT_COOKIEJAR, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
		curl_setopt($chh, CURLOPT_URL, "https://graph.facebook.com/oauth/access_token?client_id=166506083896&client_secret=13dc1a449fbc62759cb9e29333500db8&code=".$code."&redirect_uri=http%3A%2F%2F140.109.22.252%2Fproxy%2Falbum.php%3Furl%3D".urlencode(urlencode($url)));
		curl_setopt($chh, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($chh, CURLOPT_HEADER, 0);
		curl_setopt($chh, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($chh, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($chh, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
		curl_setopt($chh, CURLOPT_CONNECTTIMEOUT, 30);
		$qoo = curl_exec($chh);
		parse_str($qoo);
		
		// update the facebook token for further use (may change depends on the facebook's policy)
		$sql = "UPDATE  `project`.`member` SET  `fbtoken` =  '".$access_token."' WHERE  `member`.`username` ='".$username."'";
		mysql_query($sql);
		
		curl_close($chh);
		echo "Facebook Auth Success!!!!!";
		exit(1);
	}else
	{
		// if facebook is login already
		$session = $facebook->getSession();

		// get the token in the database
		$sql = "SELECT * FROM member where username ='".$username."'";
		$result = mysql_query($sql);
		$row = mysql_fetch_row($result);
		if( $row[3] == null )
		{
			// no token
			$json['errorCode'] = 20102;
			$llllr = "https://graph.facebook.com/oauth/authorize?client_id=166506083896&redirect_uri=http%3A%2F%2F140.109.22.252%2Fproxy%2Falbum.php%3Furl%3D".urlencode(urlencode($url));
			$json['website'] = "Facebook";
			$json['loginUrl'] = $llllr;
			echo json_encode($json);
			exit(1);
		}else
		{
			// token exist
			$access_token=$row[3];
			$albumType = 6;
		}
	}
}else
	$albumType = 5;

if( $albumType != 6)
{
	// if the url doesn't belong to any albums, it may be one page with photos
	$content = fetch_html( $url,$r );
}

// initialization
$useless = array("\t","\n","\r");
$album_title = null;
$album_des = null;
$tburls = array();
$img_name = array();
$img_des = array();
$img_alt = array();
$realurls = array();
$ttmatch = array();

// main process for each albums getting thumbs address
switch( $albumType )
{
	case 1:	//wretch
		preg_match_all("/<a\shref=\"\.\/([^\"]*)\".*<img\ssrc=\"http:\/\/(.*?)\"/", $content, $tbmatch);
		$tburls = array_merge($tburls, $tbmatch[2]);
		$realurls = array_merge($realurls, $tbmatch[1]);
		
		preg_match("/<title>([^<]*)<\/title>/",$content,$atmatch);
		$album_title = $atmatch[1];
		
		preg_match("/meta\sname=\"description\"\scontent=\'([^\']*)\'/",$content,$admatch);
		$album_des = $admatch[1];
		break;
	case 2: //flickr
		preg_match_all("/<img\ssrc=\"http:\/\/([^\"]*)\"[^\>]*alt=\"([^\"]*)\"[^\>]*class=\"pc_img\"{1}/", $content, $tbmatch);
		$tburls = array_merge($tburls, $tbmatch[1]);
		$img_alt = array_merge($img_alt, $tbmatch[2]);
		
		preg_match("/<h1\sid=\"title_div[^\"]*\">([^<]*)<\/h1>/",$content,$atmatch);
		$album_title = trim($atmatch[1]);
		
		preg_match("/<p\sclass=\"vsDescription\"\sid=\"des[^\"]*\">(.*)<\/p>/",$content,$admatch);
		$album_des = html_entity_decode(strip_tags($admatch[1]));
		break;
	case 3: //pixnet
		preg_match_all("/<img\sclass=\"thumb\"\ssrc=\"http:\/\/([^\"]*)\".*alt=\"([^\"]*)\"/", $content, $tbmatch);
		$tburls = array_merge($tburls, $tbmatch[1]);
		$img_alt = array_merge($img_alt, $tbmatch[2]);
		
		preg_match("/meta\sproperty=\"og:description\"\scontent=\"([^\"]*)\"/",$content,$atmatch);
		$album_title = html_entity_decode(strip_tags($atmatch[1]));
		
		preg_match("/meta\sname=\"description\"\scontent=\"([^\"]*)\"/",$content,$admatch);
		$album_des = html_entity_decode(strip_tags($admatch[1]));
		break;
	case 4: //picasa
		preg_match_all("/<img\ssrc=\"http:\/\/(.[^\"]*)\">/", $content, $tbmatch);
		$tburls = array_merge($tburls, $tbmatch[1]);
		preg_match_all("/\"url\":\"([^\"]*)\",\"height\"/", $content, $realmatch);
		$realurls = array_merge($realurls, $realmatch[1]);
		preg_match_all("/\"description\":\"([^\"]*)\"/", $content, $ttmatch);
		$img_des = array_merge($img_des, $ttmatch[1]);	
		preg_match("/meta\sname=\"title\"\scontent=\"([^\"]*)\"/",$content,$atmatch);
		$album_title = html_entity_decode(strip_tags($atmatch[1]));
		preg_match("/meta\sname=\"description\"\scontent=\"([^\"]*)\"/",$content,$admatch);
		$album_des = html_entity_decode($admatch[1]);		
		break;
	case 5:
		$imgmatch = array();
		$imgurls = array();

		preg_match_all("/<img.[^\>]*src=\"(.[^\"]*)\".[^\>]*>/", $content, $imgmatch);
		$imgurls = array_merge($imgurls, $imgmatch[1]);
		preg_match("/<title>([^<]*)<\/title>/",$content,$atmatch);
		$album_title = html_entity_decode(strip_tags($atmatch[1]));		
		break;
	case 6:
		
		// based on the input url, decide the album's id and user's id
		$urla = array();
		$urla = parse_url($url);
		$arg = array();
		parse_str($urla['query'],$arg);
		$uid = $arg['id'];
		$aid = $arg['aid'];
		
		// uid and aid's positions may be different
		$fbalbum1="http://www.facebook.com/album.php?aid=".$aid."&id=".$uid;
		$fbalbum2="http://www.facebook.com/album.php?id=".$uid."&aid=".$aid;
		
		$fql = "https://graph.facebook.com/".$uid."/albums";
		$chh = curl_init();	
		$cookie = "fb_".MD5($url).".txt";
		curl_setopt($chh, CURLOPT_COOKIEFILE, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
		curl_setopt($chh, CURLOPT_COOKIEJAR, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
		curl_setopt($chh, CURLOPT_URL, $fql."?access_token=".$access_token);
		curl_setopt($chh, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($chh, CURLOPT_HEADER, 0);
		curl_setopt($chh, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($chh, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($chh, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
		curl_setopt($chh, CURLOPT_CONNECTTIMEOUT, 30);
		$qoo = curl_exec($chh);
		$fba = json_decode($qoo);
		
		if(array_key_exists("error",$fba))
		{
			// it may be two cases to have this error :
			// 1. the token is overdue
			// 2. the album is not exist
			// so it need to reflush the token in the database and revalid the input url
			$json['errorCode'] = 201032;
			$llllr = "https://graph.facebook.com/oauth/authorize?client_id=166506083896&redirect_uri=http%3A%2F%2F140.109.22.252%2Fproxy%2Falbum.php%3Furl%3D".urlencode(urlencode($url));
			$json['website'] = "Facebook";
			$json['loginUrl'] = $llllr;
			$sql = "UPDATE  `project`.`member` SET  `fbtoken` =  NULL WHERE  `member`.`username` ='".$username."'";
			mysql_query($sql);
			echo json_encode($json);
			exit(1);
		}		
		
		$fba = objectToArray($fba);
		
		if( $fba['data'] == null )
		{
			$sql = "UPDATE  `project`.`member` SET  `fbtoken` =  NULL WHERE  `member`.`username` ='".$username."'";
			mysql_query($sql);
			$json['errorCode'] = 201031;
			echo json_encode($json);
			exit(1);			
		}
		
		$rrr=count($fba['data']);
				
		for( $q = 0 ; $q < $rrr; $q++ )
		{
			if( $fba['data'][$q]['link'] == $fbalbum1 || $fba['data'][$q]['link'] == $fbalbum2 )
			{
				$album_title = $fba['data'][$q]['name'];
				$album_id = $fba['data'][$q]['id'];
				break;
			}
		}
		
		if( $album_id == null )
		{
			$json['errorCode'] = 201031;
			echo json_encode($json);
			exit(1);			
		}
		
		// fetch the photo list
		$fql="https://graph.facebook.com/".$album_id."/photos?limit=200";
		curl_setopt($chh, CURLOPT_URL, $fql."&access_token=".$access_token);
		$qoo = curl_exec($chh);
		curl_close($chh);
		
		$fb = json_decode($qoo);
		$fb  = objectToArray($fb);
		
		$www=count($fb['data']);
		
		if( $www <= 0 )
		{
			$json['errorCode'] = 0;
			$json['imageCount'] = 0;
			echo json_encode($json);
			exit(1);	
		}
		$jsondata = array();
		
		for( $k = 0 ; $k < $www ; $k++ )
		{
			$jsondata[$k] = array();
			$jsondata[$k]['rel_url'] = $fb['data'][$k]['source'];
			$jsondata[$k]['thb_url'] = $fb['data'][$k]['picture'];
			$jsondata[$k]['img_hei'] = $fb['data'][$k]['height'];
			$jsondata[$k]['img_wid'] = $fb['data'][$k]['width'];
			$jsondata[$k]['img_alt'] = $fb['data'][$k]['source'];			
			
			$fakeip = rand(1,254).".".rand(1,254).".".rand(1,254).".".rand(1,254);
			$headers[] = "x-forwarded-for: ".$fakeip;
			$headers[] = "via: HTTP/1.1 140.109.22.252";
			$ch = curl_init();
			$cookie = "ck_".MD5($url).".txt";
			curl_setopt($ch, CURLOPT_COOKIEFILE, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
			curl_setopt($ch, CURLOPT_COOKIEJAR, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_URL, $fb['data'][$k]['source']);
			$content = curl_exec($ch);
		
			$iurl = MD5($fb['data'][$k]['source']);
			$dmod = ($iurl%10)."/";
			$filename = $cache_dir.$dmod."img_".$iurl.".jpg";
			$imgurl = "cache/".$dmod."img_".$iurl.".jpg";
			$jsondata[$k]['img_url'] = $imgurl;
			
			if( file_exists($filename) )
			{
				$file_ts = filemtime($filename);
				if( (time() - $file_ts) <= $time_img )
				{
					$exif = @read_exif_data($jsondata[$k]['img_url'],'EXIF');
					if( $exif != false )					{
						unset($exif['FileName']);
						unset($exif['SectionsFound']);
						unset($exif['MimeType']);
						unset($exif['FileType']);
						unset($exif['FileDateTime']);
					}else
					{
						$exif = null;
						$bexif = false;
					}
					$jsondata[$k]['img_exif'] = $exif;
					continue;
				}
			}
			
			if (!$fp2=@fopen($filename,"wb")) {
				if ( !@mkdir($cache_dir.$dmod,0777,true) ){
				}
				$fp2=@fopen($filename,"wb");
			}
    
			if (!@fwrite($fp2,$content)) {
				$json['errorCode'] = 20200;
				echo json_encode($json);
				fclose($fp2);
				return false;
			}
    		
			$exif = @read_exif_data($jsondata[$k]['img_url'],'EXIF');
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
			$jsondata[$k]['img_exif'] = $exif;
		}
		break;
		
	case 7:
		
		preg_match_all("/<img\ssrc=\"([^\"]*)\"\sonerror/", $content, $tbmatch);
		$tburls = array_merge($tburls, $tbmatch[1]);
		
		preg_match_all("/<p\sclass=\"photo_info_title\"><a\shref[^>]*>([^<]*)<\/a>/", $content, $idmatch);
		$img_des = array_merge($img_des, $idmatch[1]);	
		
		preg_match("/<title>([^<]*)<\/title>/",$content,$atmatch);
		$album_title = html_entity_decode(strip_tags($atmatch[1]));
		preg_match("/meta\sname=\"description\"\scontent=\"([^\"]*)\"/",$content,$admatch);
		$album_des = html_entity_decode($admatch[1]);	
		
		
		break;
}
$fakeip = rand(1,254).".".rand(1,254).".".rand(1,254).".".rand(1,254);
$headers[] = "x-forwarded-for: ".$fakeip;
$headers[] = "via: HTTP/1.1 140.109.22.252";

// fetch the first page in the album
if( albumType < 5 )
{
$ch = curl_init();
$cookie = "ck_".MD5($url).".txt";
curl_setopt($ch, CURLOPT_COOKIEFILE, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
curl_setopt($ch, CURLOPT_COOKIEJAR, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
if( albumType == 1 )
	curl_setopt($ch, CURLOPT_REFERER, "http://www.wretch.cc/album");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

$j = 0;
foreach($tburls as $val)
{
	$iurl = MD5($val);
	$dmod = ($iurl%10)."/";
	$filename = $cache_dir.$dmod."img_".$iurl.".jpg";
	$imgurl = "cache/".$dmod."img_".$iurl.".jpg";
	
	if( file_exists($filename) )
	{
		$jsondata[$j]['thb_url'] = $imgurl;
		$jsondata[$j]['img_alt'] = $img_alt[$j];	
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
	$jsondata[$j]['thb_url'] = $imgurl;
	$jsondata[$j]['img_alt'] = $img_alt[$j];
	$j++;	
}

curl_close($ch);
}

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_COOKIEFILE, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);
curl_setopt($ch2, CURLOPT_COOKIEJAR, "C:\\AppServ\\www\\proxy\\cookie\\".$cookie);

if($enable_proxy)
{
	curl_setopt($ch2, CURLOPT_PROXYUSERPWD, $proxy_user.":".$proxy_pass);
}

curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers); 
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 60);

//$time_start = microtime_float();

// most important part for parsing the real photo's address and save it to the cache
$i = 0;
switch( $albumType )
{
	case 1:	//wretch
		foreach( $realurls as $val )
		{
			$wretch = "http://www.wretch.cc/album/";
			curl_setopt($ch2, CURLOPT_REFERER, $wretch.$val);
			curl_setopt($ch2, CURLOPT_URL, $wretch.$val);
			$html_data = curl_exec($ch2);
			
			$try_num = 1;
			while( $html_data == null )
			{
				if($try_num == 5)
					break;

				if($enable_proxy)
				{
					$temp_proxy = $proxy_pool[rand(0,count($proxy_pool))];
					curl_setopt($ch2, CURLOPT_PROXY, $temp_proxy.":".$proxy_port);
					if($try_num == 3)
					{
						curl_setopt($ch2, CURLOPT_PROXY, "");
						//$jsondata[$i]['status'] = "sinica";
					}
				}
				
				$html_data = curl_exec($ch2);
				$try_num++;
			}

			preg_match("/<img\sid=\'DisplayImage\'\ssrc=\'(.*?)\'/", $html_data, $img);
			preg_match("/<span\sid=\"DisplayTitle\">\s(.*?)\s<\/span>/", $html_data, $title);	
			preg_match("/<span\sid=\"DisplayDesc\">\s(.*?)\s<\/span>/", $html_data, $desc);
			
			$mm = MD5($val);
			$filename = "cache/".($mm%10)."/img_".$mm.".jpg";
			
			if( file_exists($filename) )
			{
				$file_ts = filemtime($filename);
				if( (time() - $file_ts) <= $time_img )
				{
					$size = getimagesize($filename);
					$jsondata[$i]['img_hei'] = $size[1];
					$jsondata[$i]['img_wid'] = $size[0];
					$jsondata[$i]['rel_url'] = $val;
					$jsondata[$i]['img_url'] = $filename;
					$jsondata[$i]['img_alt'] = $img_alt[$i];
					if($title[1] != null)
						$jsondata[$i]['img_name'] = $title[1];
					else
						$jsondata[$i]['img_name'] = null;
					if($desc[1] != null)
						$jsondata[$i]['img_des'] = $desc[1];
					else
						$jsondata[$i]['img_des'] = null;
					$exif = @read_exif_data($jsondata[$i]['img_url'],'EXIF');
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
						$bexif = false;
					}
					$jsondata[$i]['img_exif'] = $exif;
					$i++;
					continue;
				}
			}
			
			if($enable_proxy)
			{
				$temp_proxy = $proxy_pool[rand(0,count($proxy_pool))];
				curl_setopt($ch2, CURLOPT_PROXY, $temp_proxy.":".$proxy_port);
				$jsondata[$i]['status'] = "proxy";
			}else
				$jsondata[$i]['status'] = "sinica";

			curl_setopt($ch2, CURLOPT_URL, $img[1]);
			$rawdata = curl_exec($ch2);
			
			$try_num = 1;
			while( $rawdata == null )
			{
				if($try_num == 5)
					break;

				if($enable_proxy)
				{
					$temp_proxy = $proxy_pool[rand(0,count($proxy_pool))];
					curl_setopt($ch2, CURLOPT_PROXY, $temp_proxy.":".$proxy_port);
					if($try_num == 3)
					{
						curl_setopt($ch2, CURLOPT_PROXY, "");
						$jsondata[$i]['status'] = "sinica";
					}
				}
				
				$rawdata = curl_exec($ch2);
				$try_num++;
			}
			
			if( !file_exists( $filename ) )
			{
				if (!$fp=fopen( $filename ,"wb")) {
					if ( !@mkdir($cache_dir.$dmod,0777,true) ){
					}
					$fp=fopen($filename,"wb");
				}
				
				if (!fwrite($fp,$rawdata)){
						$jsondata[$i]['status'] = "none";
				}
				fclose($fp);
			}
			
			$size = getimagesize($filename);
			$jsondata[$i]['img_hei'] = $size[1];
			$jsondata[$i]['img_wid'] = $size[0];
			$jsondata[$i]['rel_url'] = $val;
			$jsondata[$i]['img_url'] = $filename;
			$jsondata[$i]['img_alt'] = $img_alt[$i];
			if($title[1] != null)
				$jsondata[$i]['img_name'] = $title[1];
			else
				$jsondata[$i]['img_name'] = null;
			if($desc[1] != null)
				$jsondata[$i]['img_des'] = $desc[1];
			else
				$jsondata[$i]['img_des'] = null;
			$exif = @read_exif_data($jsondata[$i]['img_url'],'EXIF');
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
			$jsondata[$i]['img_exif'] = $exif;
			$i++;
		}
		curl_close($ch2);		
		break;
	case 2: //flickr
		curl_setopt($ch2, CURLOPT_REFERER, "http://www.flickr.com");
		
		foreach( $tburls as $val )
		{
			$val = str_replace( "_s","_z", $val);
			$val = str_replace( "_m","_z", $val);
			$iurl = MD5($val);
			$dmod = ($iurl%10)."/";
			$filename = $cache_dir.$dmod."img_".$iurl.".jpg";
			$imgurl = "cache/".$dmod."img_".$iurl.".jpg";
			
			if( file_exists($filename) )
			{
				$file_ts = filemtime($filename);
				if( (time() - $file_ts) <= $time_img )
				{
					$size = getimagesize($filename);
					$jsondata[$i]['img_hei'] = $size[1];
					$jsondata[$i]['img_wid'] = $size[0];
					$jsondata[$i]['rel_url'] = $val;
					$jsondata[$i]['img_url'] = $imgurl;
					$jsondata[$i]['img_alt'] = $img_alt[$i];
					if($title[1] != null)
						$jsondata[$i]['img_name'] = $title[1];
					else
						$jsondata[$i]['img_name'] = null;
					if($desc[1] != null)
						$jsondata[$i]['img_des'] = $desc[1];
					else
						$jsondata[$i]['img_des'] = null;
					$exif = @read_exif_data($jsondata[$i]['img_url'],'EXIF');
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
						$bexif = false;
					}
					$jsondata[$i]['img_exif'] = $exif;
					$i++;
					continue;
				}
			}	
			curl_setopt($ch2, CURLOPT_URL, $val);	
			$content = curl_exec($ch2);
	
			if (!$fp=@fopen($filename,"w")) {
			
				if ( !@mkdir($cache_dir.$dmod,0777,true) ){
				}
		
				$fp=@fopen($filename,"wb");
			}
    
			if (!@fwrite($fp,$content)) {
				fclose($fp);
				$json['errorCode'] = 203200;
				echo json_encode($json);
				return false;
			}
			fclose($fp);
			$size = getimagesize($filename);
			$jsondata[$i]['img_hei'] = $size[1];
			$jsondata[$i]['img_wid'] = $size[0];
			$jsondata[$i]['rel_url'] = $val;
			$jsondata[$i]['img_url'] = $imgurl;
			$exif = @read_exif_data($filename,'EXIF');
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
			$jsondata[$i]['img_exif'] = $exif;
			$i++;
		}
		
		curl_close($ch2);		
		break;
	case 3: //pixnet
		curl_setopt($ch2, CURLOPT_REFERER, $url);
	
		foreach( $tburls as $val )
		{
			$val = str_replace( "_t","", $val);
			$iurl = MD5($val);
			$dmod = ($iurl%10)."/";
			$filename = $cache_dir.$dmod."img_".$iurl.".jpg";
			$imgurl = "cache/".$dmod."img_".$iurl.".jpg";
	
			if( file_exists($filename) )
			{
				$file_ts = filemtime($filename);
				if( (time() - $file_ts) <= $time_img )
				{
					$size = getimagesize($filename);
					$jsondata[$i]['img_hei'] = $size[1];
					$jsondata[$i]['img_wid'] = $size[0];
					$jsondata[$i]['rel_url'] = $val;
					$jsondata[$i]['img_url'] = $imgurl;
					$jsondata[$i]['img_alt'] = $img_alt[$i];
					if($title[1] != null)
						$jsondata[$i]['img_name'] = $title[1];
					else
						$jsondata[$i]['img_name'] = null;
					if($desc[1] != null)
						$jsondata[$i]['img_des'] = $desc[1];
					else
						$jsondata[$i]['img_des'] = null;
					$exif = @read_exif_data($jsondata[$i]['img_url'],'EXIF');
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
						$bexif = false;
					}
					$jsondata[$i]['img_exif'] = $exif;
					$i++;
					continue;
				}
			}
			
			curl_setopt($ch2, CURLOPT_URL, $val);	
			$rawdata = curl_exec($ch2);
					
			if( !file_exists( $filename ) )
			{
				if (!$fp=@fopen( $filename ,"wb")) {
					if ( !@mkdir($cache_dir.$dmod,0777,true) ){
					}
		
					$fp=fopen($filename,"wb");
				}
				if (!fwrite($fp,$rawdata)){
					$json['errorCode'] = 203300;
					echo json_encode($json);
					return false;
				}
				fclose($fp);
			}
			$size = getimagesize($filename);
			$jsondata[$i]['img_hei'] = $size[1];
			$jsondata[$i]['img_wid'] = $size[0];
			$jsondata[$i]['rel_url'] = $val;
			$jsondata[$i]['img_url'] = $imgurl;
			
			$exif = @read_exif_data($jsondata[$i]['img_url'],'EXIF');
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
			$jsondata[$i]['img_exif'] = $exif;
			$i++;
		}
		
		curl_close($ch2);
		break;
	case 4: //picasa
		curl_setopt($ch2, CURLOPT_REFERER, $url);
		
		foreach( $realurls as $val )
		{
			$jsondata[$i]['img_des'] = $img_des[$i];
			$iurl = MD5($val);
			$dmod = ($iurl%10)."/";
			$filename = $cache_dir.$dmod."img_".$iurl.".jpg";
			$imgurl = "cache/".$dmod."img_".$iurl.".jpg";
			
			if( file_exists($filename) )
			{
				$file_ts = filemtime($filename);
				if( (time() - $file_ts) <= $time_img )
				{
					$size = getimagesize($filename);
					$jsondata[$i]['img_hei'] = $size[1];
					$jsondata[$i]['img_wid'] = $size[0];
					$jsondata[$i]['rel_url'] = $val;
					$jsondata[$i]['img_url'] = $imgurl;
					$jsondata[$i]['img_alt'] = $img_alt[$i];
					$exif = @read_exif_data($jsondata[$i]['img_url'],'EXIF');
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
						$bexif = false;
					}
					$jsondata[$i]['img_exif'] = $exif;
					$i++;
					continue;
				}
			}
			
			curl_setopt($ch2, CURLOPT_URL, $val);	
			$rawdata = curl_exec($ch2);
			
			if( !file_exists( $filename ) )
			{
				if (!$fp=@fopen( $filename ,"wb")) {
					if ( !@mkdir($cache_dir.$dmod,0777,true) ){
					}
		
					$fp=fopen($filename,"wb");
				}
				if (!fwrite($fp,$rawdata)){
					$json['errorCode'] = 203300;
					echo json_encode($json);
					return false;
				}
				fclose($fp);
			}
			$size = getimagesize($filename);
			$jsondata[$i]['img_hei'] = $size[1];
			$jsondata[$i]['img_wid'] = $size[0];
			$jsondata[$i]['rel_url'] = $val;
			$jsondata[$i]['img_url'] = $imgurl;
			$exif = @read_exif_data($jsondata[$i]['img_url'],'EXIF');
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
			$jsondata[$i]['img_exif'] = $exif;
			$i++;
		}
		
		curl_close($ch2);
		break;
	case 5:
		$j = 0;
		curl_setopt($ch2, CURLOPT_REFERER, $url);		
		foreach( $imgurls as $val)
		{
			$val = format_url($val,$url);
			$pi = array();
			$pi = pathinfo($val);
			$ext = $pi['extension'];
			
			$iurl = MD5($val);
			$dmod = ($iurl%10)."/";
			$filename = $cache_dir.$dmod."img_".$iurl.".".$ext;
			$imgurl = "cache/".$dmod."img_".$iurl.".".$ext;
			
			if($ext == "gif")
			{
				$filename = $cache_dir.$dmod."img_".$iurl.".png";
				$imgurl = "cache/".$dmod."img_".$iurl.".png";
			}
			
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
			curl_setopt($ch2, CURLOPT_URL, $val);	
			$content = curl_exec($ch2);
			
			if($content ==null || strlen($content)<5000)
			{
				continue;
			}
			
			if($ext == "gif")
			{
				if($content==null)
					continue;
				$img = imagecreatefromstring($content);
				$ret = imagepng($img, $filename);
			}else{
			
			if (!$fp=@fopen($filename,"wb")) {
			
				if ( !@mkdir($cache_dir.$dmod,0777,true) ){
				}
				
				$fp=@fopen($filename,"wb");
			}
			
			if($content ==null)
			{
				fclose($fp);
				continue;
			}
			
			if (!@fwrite($fp,$content)) {
				$json['errorCode'] = 20200;
				echo json_encode($json);
				fclose($fp);
				return false;
			}
			
			fclose($fp);
			}
			
			$size = getimagesize($filename);
			if( $size[1]<50 || $size[0]<50 )
				continue;			
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
		break;
	case 6:
		break;
	case 7:
		$j = 0;
		foreach( $tburls as $val)
		{
			$val = str_replace( "_c","_l", $val);
			$iurl = MD5($val);
			$dmod = ($iurl%10)."/";
			$filename = $cache_dir.$dmod."img_".$iurl.".".$ext;
			$imgurl = "cache/".$dmod."img_".$iurl.".".$ext;
			
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
			curl_setopt($ch2, CURLOPT_URL, $val);	
			$content = curl_exec($ch2);
			
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
		break;
}

//$time_end = microtime_float();

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
		exit(1);
}

$www=count($jsondata);

if( $www <= 0 )
{
		$json['errorCode'] = 0;
		$json['imageCount'] = 0;
		echo json_encode($json);
		exit(1);	
}

// for face detection, generate the photo's list into a file

	$handle = MD5($url);
	$fp=@fopen($cache_dir."face_".$handle.".txt","w");
    if ( !fwrite($fp,$jsondata[0]['rel_url']) )
	{
		$json['errorCode'] = 20402;
		echo json_encode($json);
		exit(1);
	}
	for( $k = 1 ; $k < $www ; $k++ )
	{
		fwrite($fp,"\n".$jsondata[$k]['rel_url']);
	}
	
    fclose($fp);

// basic algorithm for detecting whether the photos are duplicate
function are_duplicates($file1, $file2)
{
		// load in both images and resize them to 16x16 pixels
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
		
        // Compare the pixels of each image and figure out the colour difference between them
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
		
		// change the number for better solution
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
$json['album_title'] = $album_title;
$json['album_des'] = $album_des;
$json['handle'] = $handle;
$json['imageCount'] = $www;
$json['data'] = array();
$json['data'] = array_merge( $json['data'],$jsondata );
//echo "time : ".($time_end - $time_start);
echo json_encode($json);

// generate the meta file to accelerate the process of the same album
$meta_dir = "C:\\AppServ\\www\\proxy\\meta\\";
$murl = MD5($url);
$mmod = ($murl%10)."\\";
$filename = $meta_dir.$mmod."meta_".$murl;

if (!$fp=@fopen($filename,"w")) {
			
	if ( !@mkdir($meta_dir.$mmod,0777,true) ){
	}
		$fp=@fopen($filename,"wb");
}

if (!fwrite($fp,json_encode($json))) {
	fclose($fp);
	$json['errorCode'] = 203200;
	echo json_encode($json);
	return false;
}

fclose($fp);
?>