function createObject()
{
	var request_type;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		request_type = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		request_type = new XMLHttpRequest();
	}
	return request_type;
}

var http = createObject();

var nocache = 0;

function login()
{
	// Optional: Show a waiting message in the layer with ID ajax_response
	document.getElementById('login_response').innerHTML = "Loading..."
	// Required: verify that all fileds is not empty. Use encodeURI() to solve some issues about character encoding.
	var us = encodeURI(document.getElementById('usLogin').value);
	var psw = encodeURI(document.getElementById('pswLogin').value);
	// Set te random number to add to URL request
	nocache = Math.random();
	// Pass the login variables like URL variable
	http.open('get', 'login.php?us='+us+'&psw='+psw+'&nocache = '+nocache);
	http.onreadystatechange = loginReply;
	http.send(null);
}

function loginReply()
{
	if(http.readyState == 4)
	{ 
		var response = http.responseText;
		if(response == 0)
		{
		// if login fails
			document.getElementById('login_response').innerHTML = 'Login failed! Verify user and password';
			// else if login is ok show a message: "Welcome + the user name".
		} else
		{
			document.getElementById('login_response').innerHTML = 'Welcome'+response;
		}
	}
}