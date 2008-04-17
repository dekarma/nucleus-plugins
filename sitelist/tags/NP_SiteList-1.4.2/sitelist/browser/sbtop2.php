<?php
$setlimit = 25;
echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Site Browser</title>
<style type="text/css">
body {
	background: #FFF;
	color: #444;
	margin: 0;
	padding: 0;
	border-bottom: 2px solid #CCC;
}
h1 {
	font-family: verdana, sans-serif;
	font-size:18px;
	height: 30px;
	margin: 5px 0 0 10px;
	padding: 0;
	position: absolute;
	top: 0px;
	left: 4px;
	width: 460px;
}
h1 a:link, h1 a:visited {
	text-decoration:none;
	height: 20px;
	color:#197D9E;
}
h1 a:hover, h1 a:active {
	text-decoration:underline;
}
form {
	margin: 0 0 0 160px;
	padding: 0;
}
form fieldset {
	border: 0;
	height: 30px;
	margin: 0;
	padding: 5px 10px 0 0;
	text-align: right;
	vertical-align: middle;
}
form fieldset img, form fieldset select, form fieldset a {
	font-family: verdana, sans-serif;
	font-size: 11px;
	vertical-align: middle;
}
form fieldset img {
	cursor: hand;
	cursor: pointer;
}
form fieldset a {
	/*background: url(img/down.gif) no-repeat;*/
	background-position: right;
	color: #444;
	display: block;
	float: right;
	height: 20px;
	margin: 0 0 0 20px;
	padding: 4px 25px 0 0;
}
</style>
</head>';
echo "
<body onload=\"if (document.getElementById('sites').options[document.getElementById('sites').options.selectedIndex].value == '') { document.getElementById('sites').options.selectedIndex = 0; }\">

<h1>Site Browser </h1>
";
echo "
<form>

	<fieldset>
		<a href=\"#\" onclick=\"history.go(0)\">[New Set]</a>&nbsp;
		<img src=\"img/left.gif\" alt=\"previous site\" title=\"previous site\" onclick=\"
			if(document.getElementById('sites').selectedIndex != 0) {
				var showSite = document.getElementById('sites').selectedIndex - 1;
				if (document.getElementById('sites').options[showSite]) {
					document.getElementById('sites').options.selectedIndex = showSite;
					top.navigate(document.getElementById('sites').options[showSite].value);
				}
			}
			else {
				var myLength = document.getElementById('sites').length - 1;
				document.getElementById('sites').options.selectedIndex = myLength;
				top.navigate(document.getElementById('sites').options[myLength].value);
			}\" />
		<select id=\"sites\" onchange=\"top.navigate(this.options[this.options.selectedIndex].value);\">
<option value=\"\">---> Choose a Site <---</option>";
include 'config.php';
$conn = mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD);
	if (!$conn) {
	   die('Could not connect: ' . mysql_error());
	}
mysql_select_db($MYSQL_DATABASE);
$query = "SELECT url,title FROM ".sql_table(plug_sitelist)." WHERE checked>0 AND browser>0 ORDER BY RAND() LIMIT ".$setlimit;
//echo $query;
$result = mysql_query($query);
while ($site = mysql_fetch_object($result)) {
echo '<option value="'.$site->url.'">'.substr($site->title,0,32).' ('.$site->url.')</option>';
}
mysql_close($conn);
echo "
</select>		

<img src=\"img/right.gif\" alt=\"next site\" title=\"next site\" onclick=\"
			var showSite = document.getElementById('sites').selectedIndex + 1;
			if (document.getElementById('sites').options[showSite]) {
				top.navigate(document.getElementById('sites').options[showSite].value); 			
				document.getElementById('sites').options.selectedIndex = showSite;
			}
			else {
				top.navigate('');
				document.getElementById('sites').options.selectedIndex = 0;
			}\" />
	</fieldset>
</form>
</body>
</html>";

