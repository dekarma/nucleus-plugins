<?php
//NP_gallery admin page

	$strRel = '../../../';

	include($strRel . 'config.php'); //nucleus config
	include('config.php'); //gallery config
		
	if (!$member->isLoggedIn())	doError(_NOTLOGGEDIN);

	include($DIR_LIBS . 'PLUGINADMIN.php');
	
	global $galleryaction, $CONF, $NPG_CONF,$gmember,$member;
	$galleryaction=$CONF['PluginURL']."gallery/index.php";

	//create extra header info for admin page
	$gallery_header = '<style type="text/css">

	#tabmenu {
		color: #000;
		border-bottom: 1px solid black;
		margin: 12px 0px 0px 0px;
		padding: 0px;
		z-index: 1;
		padding-left: 10px }
		
	#tabmenu li {
		display: inline;
		overflow: hidden;
		list-style-type: none; }	
		
	#tabmenu a, a.active {
		color: black;
		background: white;
		font: bold 1em/1em;
		border: 1px solid black;
		-moz-border-radius-topleft: 5px;
		-moz-border-radius-topright: 5px;
		padding: 5px 5px 0px 5px;
		margin: 0px;
		text-decoration: none; }
		
	#tabmenu a.active {
		background: white;
		border-bottom: 1px solid white; }
		
	#tabmenu a:hover {
		color: #596d9d;
		background: #F6F6F6; }	
		
	#tabmenu a:visited {
		color: #596d9d; }
		
	#tabmenu a.active:hover {
		background: white;
		color: #596d9d; }
	
	#admin_content {font: 0.9em/1.3em "bitstream vera sans", verdana, sans-serif;
		text-align: justify;
		background: white;
		padding: 20px;	
		border-top: none;
		z-index: 2;	}
		
	.half {
		width: 50%;
	}
	
	form p {
		clear: left;
		margin: 0;
		padding: 0;
		padding-top: 5px;
	}
	form p label {
		float: left;
		width: 300px;
		font: bold 1.0em Arial, Helvetica, sans-serif;
	}
	
	fieldset {
		border: 1px dotted #61B5CF;
		margin-top: 16px;
		padding: 10px;
	}
	legend {
		font: 1.2em Arial, Helvetica, sans-serif;
	}
	
	td.left {
		width: 230px;
	}
	
	</style>
	
	<SCRIPT>
	function openTarget (form, features, windowName) {
		if (!windowName)
		windowName = \'formTarget\' + (new Date().getTime());
		form.target = windowName;
		open (\'\', windowName, features);
	}
</SCRIPT>'
		
	;
	
	// create the admin area page
	$oPluginAdmin = new PluginAdmin('gallery');
	$oPluginAdmin->start($gallery_header);

	echo '<h2>'.__NPG_ADMIN_TITLE.'</h2>';


	$action = requestVar('action');
	if(!$action) $action = 'albumlist';
	$admin = new NPG_ADMIN();
	$admin->action($action);


	$oPluginAdmin->end();


?>