<?php 
include("./class_proxy.php");
//$url = $_GET['url'];
//$url = urlencode($url);
$headers[] = 'Content-type: application/x-www-form-urlencoded;charset=utf8';
//$headers[] = "Pragma: no-cache";
//$headers[] = "via: HTTP/1.1 140.109.22.252";
$url = "http://www.wretch.cc/album/show.php?i=luss&b=11&f=1567747374&p=0";
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL,$url); // set url to post to 
curl_setopt($ch, CURLOPT_COOKIEFILE, "C:\\AppServ\\www\\proxy\\cookie\\bitz.txt");
curl_setopt($ch, CURLOPT_COOKIEJAR, "C:\\AppServ\\www\\proxy\\cookie\\bitz.txt");


curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_user.":".$proxy_pass);
//curl_setopt($ch, CURLOPT_FAILONERROR, 1); 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects 
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable 
curl_setopt($ch, CURLOPT_TIMEOUT, 0); // times out after Ns 
//curl_setopt($ch, CURLOPT_POST, 1); // set POST method 
//curl_setopt($ch, CURLOPT_POSTFIELDS, "u=".$url); // add POST fields 
//curl_setopt($ch, CURLOPT_FAILONERROR, 0); 
//curl_setopt($ch, CURLOPT_VERBOSE, 1); 
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
curl_setopt($ch, CURLOPT_REFERER, 	"http://www.wretch.cc/album");
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 

$max = count($proxy_pool);

$p = 0;
while($p < $max)
{
	$temp_proxy = $proxy_pool[$p];
	curl_setopt($ch, CURLOPT_PROXY, $temp_proxy.":".$proxy_port);
	$result = curl_exec($ch); // run the whole process 
	
	if (!$fp=@fopen($p.".txt","w"))
	{
	}

	if (!fwrite($fp,$result)) {
	}

	fclose($fp);
	
	if( $result == null )
	{
		echo $p." fail<br>";
	}else
		echo $p." work<br>";
		
	$p++;
}
curl_close($ch); 
?> 