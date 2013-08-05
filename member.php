<?php session_start();?>
<html>
<head>
  <title>PHP-Based Ajax Proxy - 2010/10/07</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript">

function GetImageMetadata()
{
	var url = encodeURIComponent(document.getElementById('url').value);
	var index = encodeURI(document.getElementById('startIndex').value);
	document.getElementById('error').innerHTML = "Send to "+url;
	document.getElementById('sid').innerHTML = "<?php echo session_id(); ?>";
	
	if (url.length==0)
	{ 
		document.getElementById("error").innerHTML = "url is blank";
		document.getElementById("imageCount").innerHTML = "0";
		return;
	}
	
	if (window.XMLHttpRequest)
	{
		xmlhttp=new XMLHttpRequest();
	}
	else
	{
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
		document.getElementById('jj').innerHTML = xmlhttp.responseText;
		var data = eval('(' + xmlhttp.responseText + ')');
		document.getElementById('error').innerHTML = data.errorCode;
		document.getElementById('imageCount').innerHTML = data.imageCount;
		}
	}
	xmlhttp.open("GET","album.php?url="+url+"&index="+index,true);
	xmlhttp.send();
}

function GetImage()
{
	var imgUrl = encodeURIComponent(document.getElementById('imgUrl').value);
	var wid = encodeURI(document.getElementById('maxWidth').value);
	var hei = encodeURI(document.getElementById('maxHeight').value);
	
	document.getElementById('error2').innerHTML = "Loading...";
	document.getElementById('sid2').innerHTML = "<?php echo $_SESSION['username']; ?>";
	
	if (imgUrl.length==0)
	{ 
		document.getElementById("error2").innerHTML = "url is blank";
		return;
	}
	
	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
		document.getElementById('ji').innerHTML = xmlhttp.responseText;
		//var data = eval('(' + xmlhttp.responseText + ')');
		document.getElementById('error2').innerHTML = "Done!!";
		document.getElementById("imgID").src = "cache/"+xmlhttp.responseText;
		}
	}
	xmlhttp.open("GET","image_cache.php?ttl=300&url="+imgUrl+"&wid="+wid+"&hei="+hei,true);
	xmlhttp.send();
}

function GetImageFromPage()
{
	var Url = encodeURIComponent(document.getElementById('Url').value);
	
	document.getElementById('error3').innerHTML = "Loading...";
	document.getElementById('sid3').innerHTML = "<?php echo $_SESSION['username']; ?>";
	
	if (Url.length==0)
	{ 
		document.getElementById("error3").innerHTML = "url is blank";
		return;
	}
	
	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
		document.getElementById('kk').innerHTML = xmlhttp.responseText;
		//var data = eval('(' + xmlhttp.responseText + ')');
		document.getElementById('error3').innerHTML = "Done!!";
		}
	}
	xmlhttp.open("GET","image_page.php?url="+Url,true);
	xmlhttp.send();
}
</script>
</head>
<body>
<?php
include("class_mysql.php");
//echo $_SESSION['username'];

if($_SESSION['username'] != null )
{
		/*
        $sql = "SELECT * FROM member";
        $result = mysql_query($sql);
        while($row = mysql_fetch_row($result))
        {
                 echo "序號 $row[0] - 帳號 $row[1]<br>";
        }
		echo "<br>";
		*/
?>
		
<h3>GetImageMetadata : ( sessionID is added )</h3>
<form name="form" method="post" action="javascript:GetImageMetadata()">
Url:<input type="text" name="url" id="url" value="" /> <br>
startIndex:<input type="text" name="startIndex" id="startIndex" value="" /> <br>
modifiedDate:<input type="text" name="modifiedDate" id="modifiedDate" value="" /> <br>
<input type="submit" name="button" value="Request" />
</form>
<p>json: <span id="jj"></span></p>
<p>errorCode: <span id="error"></span></p>
<p>imageCount: <span id="imageCount"></span></p>
<p>session: <span id="sid"></span></p>

<h3>GetImage : ( sessionID is added )</h3>
<form name="form2" method="post" action="javascript:GetImage()">
imgUrl:<input type="text" name="imgUrl" id="imgUrl" value="" /> <br>
maxWidth:<input type="text" name="maxWidth" id="maxWidth" value="" /> <br>
maxHeight:<input type="text" name="maxHeight" id="maxHeight" value="" /> <br>
<input type="submit" name="button" value="imgRequest" />
</form>
<p>json: <span id="ji"></span></p>
<p>errorCode: <span id="error2"></span></p>
<p>session: <span id="sid2"></span></p>
<p>photo: <img id="imgID"></img></p>

<h3>GetImageFromPage : ( sessionID is added )</h3>
<form name="form3" method="post" action="javascript:GetImageFromPage()">
Url:<input type="text" name="PageUrl" id="PageUrl" value="" /> <br>
<input type="submit" name="button" value="PageRequest" />
</form>
<p>json: <span id="kk"></span></p>
<p>errorCode: <span id="error3"></span></p>
<p>session: <span id="sid3"></span></p>
		
		<?php
}
else
{
        $json['errorCode'] = 20501;
        echo json_encode($json);
        echo '<meta http-equiv=REFRESH CONTENT=2;url=client.php>';
}
?>
</body>
</html>