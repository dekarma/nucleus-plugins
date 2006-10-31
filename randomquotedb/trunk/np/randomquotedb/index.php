<?php

	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../';

	include($strRel . 'config.php');
	if (!$member->isLoggedIn())
		doError('You\'re not logged in.');

	include($DIR_LIBS . 'PLUGINADMIN.php');

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('RandomQuoteDB');
	$oPluginAdmin->start();

	// Get the actual URL as we want to be universal
	// and set the path.
	// (If anyone does know a better way to do this, drop me a line. I will try to integrate it. Really.)
	$url = $HTTP_SERVER_VARS["PHP_SELF"];
	$pattern = "/index.php/i";
	$replacement = "";
	$url =  preg_replace($pattern, $replacement, $url);

	print '<h2>Random Quote DB</h2>';

	print '<p><B>Add a quote</B></p>';
	echo "<a href=\"$url\" onclick='window.open(\"$url/rqdb_popup.php?mode=add\", \"moo\", \"toolbar=no,scrollbars=yes,resizable=yes,width=500,height=500\");'>Click here</a>";
	echo '<p><B>Edit a quote</B></p>';
	echo "<a href=\"$url\" onclick='window.open(\"$url/rqdb_popup.php?mode=chg\", \"moo\", \"toolbar=no,scrollbars=yes,resizable=yes,width=500,height=500\");'>Click here</a>";
	echo '<p><B>Delete a quote</B></p>';
	echo "<a href=\"$url\" onclick='window.open(\"$url/rqdb_popup.php?mode=del\", \"moo\", \"toolbar=no,scrollbars=yes,resizable=yes,width=500,height=500\");'>Click here</a>";

	$oPluginAdmin->end();

?>
