<?php
// if your 'plugin' directory is not in the default location,
// edit this variable to point to your site directory
// (where config.php is)
$strRel = '../../../';

include($strRel . 'config.php');

if (!$member->isLoggedIn()) doError('You\'re not logged in.');

include($DIR_LIBS . 'PLUGINADMIN.php');

// create the admin area page
$oPluginAdmin = new PluginAdmin('Acronym');
$oPluginAdmin->start();

echo "<h2>Acronym</h2>";
echo "<ul>";
echo "<li><a href=\"index.php?action=pluginoptions&amp;plugid=".$oPluginAdmin->plugin->plugid."\">Edit options</a></li>";
echo "</ul>";

if ($_POST['action'] != "") echo "<h3 style=\"padding-left: 0px\">Result</h3>";
include('acronyms.php');
$oPluginAdmin->end();
?>
