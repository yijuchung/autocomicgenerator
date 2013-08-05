<?php
/*
// input
url -> any url

// output
html's content
*/
	$url = urldecode($_GET['url']);
	$headers[] = 'Content-type: application/x-www-form-urlencoded;charset=utf8';
	$headers[] = "Pragma: no-cache";

	if( ip=="" )
		$ip = rand(1,254).".".rand(1,254).".".rand(1,254).".".rand(1,254);

	$headers[] = "x-forwarded-for: ".$ip;
	$headers[] = "via: HTTP/1.1 140.109.22.252";
	$ch = curl_init();
	$cookie = "ck_".MD5($url).".txt";
	curl_setopt($ch, CURLOPT_COOKIEFILE, "./cookie/".$cookie);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "./cookie/".$cookie);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	echo $content = curl_exec($ch);
?>