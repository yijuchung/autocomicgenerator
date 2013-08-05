<html>
<head>
  <title>PHP-Based Ajax Proxy</title>
  
<script type="text/javascript">

function showPic(str)
{
	if (str.length==0)
	{ 
		document.getElementById("imgID").src = "";
		document.getElementById("textID").innerHTML = "";
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
			document.getElementById("imgID").src = "cache/"+xmlhttp.responseText;
			document.getElementById("textID").innerHTML = xmlhttp.responseText;
		}
	}
	xmlhttp.open("GET","image_cache.php?ttl=300&url="+str,true);
	xmlhttp.send();
}
</script>
</head>
<body>

<h3>貼上任何圖片位址 : </h3>
<form action=""> 
photo address: <input type="text" id="txt1" onkeyup="showPic(this.value)" />
</form>
<p>photo: <img id="imgID"></img></p>
<p>log: <span id="textID"></span></p>

<form name="form" method="post" action="check.php">
帳號：<input type="text" name="id" /> <br>
密碼：<input type="password" name="pw" /> <br>
<input type="submit" name="button" value="登入" />
</form>

</body>
</html>

