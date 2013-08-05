<?php
require("class_mysql.php");

/*
// input
comic -> comic's handler
page -> the number of pages 
*/

$comic = $_GET['comic'];
$page = $_GET['page'];
$sql = "SELECT  `filename` ,  `desc` FROM  `project`.`book` WHERE  `filehandle` =".$comic;

if( !$result = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}

$row = mysql_fetch_row($result);

$title = $row[1];
if( $title == null )
	$title = $row[0];

// translate the relative url to absolute url
function curPageURL()
{
	$pageURL = 'http';
	if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
	$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
	$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

$time = time();
$url = curPageURL();
?>
<head>
<title><?php echo $title; ?></title>
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Content-type" content="text/html; charset=utf-8" /> 
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
<meta http-equiv="Cache-Control" content="post-check=0, pre-check=0" /> 
<meta http-equiv="Cache-Control" content="max-age=0" />
<meta http-equiv="ETags" content="If-None-Match" />
<meta property="og:title" content="<?php echo $title; ?>" />
<meta property="og:type" content="album" />
<meta property="og:url" content="<?php echo $url; ?>" />
<meta property="og:image" content="http://comics.iis.sinica.edu.tw/p/comic/<?php echo $comic."_1.jpg"; ?>" />
<meta property="og:site_name" content="http://comics.iis.sinica.edu.tw/" />
<meta property="fb:app_id" content="112072712195971" />
</head>

<iframe src="http://www.facebook.com/plugins/like.php?href=<?php echo urlencode($url); ?>&layout=button_count&show_faces=true&width=450&action=like&colorscheme=light&height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:21px;" allowTransparency="true"></iframe>
<a href="http://www.facebook.com/sharer.php?u=<?php echo urlencode($url); ?>&t=<?php echo $title; ?>" target="blank">[推到Facebook]</a>
<a href="javascript: void(window.open('http://twitter.com/home/?status='.concat(encodeURIComponent(document.title)) .concat(' ') .concat(encodeURIComponent(location.href))));">[推到Twitter]</a>
<a href="javascript: void(window.open('http://www.plurk.com/?qualifier=shares&status=' .concat(encodeURIComponent(location.href)) .concat(' ') .concat('&#40;') .concat(encodeURIComponent(document.title)) .concat('&#41;')));">[推到Plurk]</a>
<br>
<?php

for( $i = 1;$i<=$page; $i++ )
{

?>

<img src="./comic/<?php echo $comic."_".$i.".jpg"; ?>" />
<br>

<?php

}

?>
<div id="fb-root"></div><script src="http://connect.facebook.net/en_US/all.js#appId=112072712195971&amp;xfbml=1"></script><fb:comments href="<?php echo $url; ?>" num_posts="2" width="500"></fb:comments>