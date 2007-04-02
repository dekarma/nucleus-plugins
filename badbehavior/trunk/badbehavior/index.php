<?php
/* Admin area of NP_BadBehavior plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, http://www.iai.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 */

	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../';
	$plugname = "NP_BadBehavior";

	include($strRel . 'config.php');
	if ($member->isLoggedIn() && $member->canLogin()) $admin = 1;
	else doError('You\'re not logged in.');

	include($DIR_LIBS . 'PLUGINADMIN.php');

	global $CONF,$manager;
	$action_url = $CONF['ActionURL'];
	$thispage = $CONF['PluginURL'] . "badbehavior/index.php";
	$adminpage = $CONF['AdminURL'];
	$thisquerystring = serverVar('QUERY_STRING');
	$toplink = '<p class="center"><a href="'.$thispage.'?'.$thisquerystring.'#sitop" alt="Return to Top of Page">-top-</a></p>'."\n";
	$showlist = strtolower(trim(requestVar('showlist')));
	if (!in_array($showlist, array('stats','admin'))) $showlist = 'stats';

$newhead = '
<style>
.navlist
{
padding: 3px 0;
margin-left: 0;
border-bottom: 1px solid #778;
font: bold 12px Verdana, sans-serif;
}

.navlist li
{
list-style: none;
margin: 0;
display: inline;
}

.navlist li a
{
padding: 3px 0.5em;
margin-left: 3px;
border: 1px solid #778;
border-bottom: none;
background: #DDE;
text-decoration: none;
}

.navlist li a:link { color: #448; }
.navlist li a:visited { color: #667; }

.navlist li a:hover
{
color: #000;
background: #AAE;
border-color: #227;
}

.navlist li a.current
{
background: white;
border-bottom: 1px solid white;
}

a.buttonlink {
border:outset 1px;
padding:2px;
background-color:#DDE;
text-decoration:none;
}

.badbehavior
{
padding: 3px 0;
margin-left: 0;
border-bottom: 1px solid #778;
}

.badbehavior table {border-collapse: collapse;}
.badbehavior .center {text-align: center;}
.badbehavior .center table { margin-left: auto; margin-right: auto; text-align: left;}
.badbehavior .center th { text-align: center !important; }
.badbehavior td, .badbehavior th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
.badbehavior h1 {font-size: 150%; text-align:left;}
.badbehavior h2 {font-size: 125%;}
.badbehavior .p {text-align: left;}
.badbehavior .e {background-color: #ccccff; font-weight: bold; color: #000000;}
.badbehavior .h {background-color: #9999cc; font-weight: bold; color: #000000;}
.badbehavior .v {background-color: #cccccc; color: #000000;}
.badbehavior .vr {background-color: #cccccc; text-align: right; color: #000000;}
.badbehavior hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>';
	// create the admin area page
	$oPluginAdmin = new PluginAdmin('BadBehavior');
	$oPluginAdmin->start($newhead);

	$plugin =& $oPluginAdmin->plugin;
	$sipid = $plugin->getID();

	$admin = intval($plugin->siIsAdmin()) + intval($plugin->siIsBlogAdmin()) + intval($plugin->siIsTeamMember());
	$minaccess = intval($plugin->getOption('accesslevel'));
	if (!$minaccess || $minaccess == 0) $minaccess = 8;

	if (!($admin >= $minaccess)) doError("You do not have sufficient privileges.");

    // make sure bad behavior is loaded
    if (!defined('BB2_CORE')) {
        echo "loading necessary bad behavior libraries...";
        /*
        global $DIR_NUCLEUS;
        $homepath = str_replace('\\','/',$DIR_NUCLEUS);
        $hparr = explode('/', rtrim($DIR_NUCLEUS,'/'));
        $adn = array_pop($hparr);
        $homepath = implode('/',$hparr);
        */
        global $DIR_PLUGINS;
        $homepath = $DIR_PLUGINS.'/badbehavior/';
        require_once($homepath.'/bad-behavior-nucleuscms.php');
        echo " OK. Completed <br />\n";
    }

/**************************************
 *	   Edit Options Link			*
 **************************************/
	echo "\n<div>\n";
	echo '<a name="sitop"></a>'."\n";
	echo '<a class="buttonlink" href="'.$adminpage.'?action=pluginoptions&amp;plugid='.$sipid.'">Edit BadBehavior Options</a>'."\n";
	echo "</div>\n";

/**************************************
 *	   Header	        			  *
 **************************************/
	//$helplink = ' <a href="'.$adminpage.'?action=pluginhelp&amp;plugid='.$sipid.'"><img src="'.$CONF['PluginURL'].'badbehavior/help.jpg" alt="help" title="help" /></a>';
	//echo '<h2 style="padding-top:10px;">BadBehavior'.$helplink.'</h2>'."\n";

/**************************************
 *	   function chooser links	   *
 **************************************/
	echo '<div class="badbehavior">'."\n";
	echo "<div>\n";
	echo '<ul class="navlist">'."\n";
	echo ' <li><a class="'.($showlist == 'nucleus' ? 'current' : '').'" href="'.$thispage.'?showlist=stats&amp;safe=true">Stats</a></li> '."\n";
	echo ' <li><a class="'.($showlist == 'php' ? 'current' : '').'" href="'.$thispage.'?showlist=admin&amp;safe=true">Admin</a></li>'."\n";
	echo " </ul></div>\n";
/**************************************
 *	 stats            				  *
 **************************************/
	if ($showlist == "stats" || $showlist == NULL)
	{
		bb2_insert_stats(true);
	} //end show nucleus
/**************************************
 *	 admin                            *
 **************************************/
	if ($showlist == "admin")
	{
        echo "No admin functions are yet available.";
		//require_once(BB2_CWD . "/bad-behavior/admin.inc.php");
	} //end php

	echo "</div>\n"; // end badbehavior
	$oPluginAdmin->end();

?>
