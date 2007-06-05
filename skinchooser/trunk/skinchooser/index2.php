<?php
/* Admin area of NP_Ordered plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, http://www.iai.com
 *
 * License information:
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 *
 */

	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../';
	$plugname = "NP_SkinChooser";

	include($strRel . 'config.php');

    // Send out Content-type
	sendContentType('text/html', 'admin-skinchooser', _CHARSET);

	if ($member->isLoggedIn() && $member->canLogin()) $admin = 1;
	else doError('You\'re not logged in.');

	include($DIR_LIBS . 'PLUGINADMIN.php');

	global $CONF,$manager;
    // $manager->checkTicket();
	//$action_url = $CONF['ActionURL'];
	$adminpage = $CONF['AdminURL'];
	$thispage = $CONF['PluginURL'] . "skinchooser/index.php";
	$bshow = intval(requestVar('bshow'));
	//$showlist = trim(strtolower(requestVar('showlist')));
	//if (!in_array($showlist, array('items','cats'))) $showlist = 'items';
	$toplink = '<p class="center"><a href="'.$thispage.'?showlist='.$showlist.'&amp;bshow='.$bshow.'#sitop" alt="Return to Top of Page">-top-</a></p>'."\n";
/*
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

.systeminfo
{
padding: 3px 0;
margin-left: 0;
border-bottom: 1px solid #778;
}

.npordered table {border-collapse: collapse;}
.npordered .center {text-align: center;}
.npordered .center table { margin-left: auto; margin-right: auto; text-align: left;}
.npordered .center th { text-align: center !important; }
.npordered td, .npordered th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
.npordered h1 {font-size: 150%; text-align:left;}
.npordered h2 {font-size: 125%;}
.npordered .p {text-align: left;}
.npordered .e {background-color: #ccccff; font-weight: bold; color: #000000;}
.npordered .h {background-color: #9999cc; font-weight: bold; color: #000000;}
.npordered .v {background-color: #cccccc; color: #000000;}
.npordered .vr {background-color: #cccccc; text-align: right; color: #000000;}
.npordered hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>';
*/
	// create the admin area page
	$oPluginAdmin = new PluginAdmin('SkinChooser');
	$oPluginAdmin->start($newhead);

	$plugin =& $oPluginAdmin->plugin;
	$sipid = $plugin->getID();

/**************************************
 *	   Edit Options Link			*
 **************************************/
	echo "\n<div>\n";
	echo '<a name="sitop"></a>'."\n";
	echo '<a class="buttonlink" href="'.$adminpage.'?action=pluginoptions&amp;plugid='.$sipid.'">Edit NP_SkinChooser Options</a>'."\n";
	echo "</div>\n";

/**************************************
 *	   Header	        			  *
 **************************************/
	//$helplink = ' <a href="'.$adminpage.'?action=pluginhelp&amp;plugid='.$sipid.'"><img src="'.$CONF['PluginURL'].'ordered/help.jpg" alt="help" title="help" /></a>';
	echo '<h2 style="padding-top:10px;">NP_SkinChoser'.$helplink.'</h2>'."\n";

/**************************************
 *      Blog Selector Form            *
 **************************************/
	echo '<div class="center">'."\n";
	echo '<form method="post" action="'.$thispage.'">'."\n";
	echo '<h3>Blog to Manage: &nbsp;&nbsp;';
	echo '<select name="bshow">'."\n";
	echo '<option value="0" '.($bshow == '0' ? ' selected>' :'>').'All</option>';
	$bres = sql_query("SELECT bnumber,bshortname FROM ".sql_table('blog'));
	while ($data = mysql_fetch_assoc($bres))
	{
		if ($member->blogAdminRights(intval($data['bnumber']))) {
			$menu .= '<option value="'.$data['bnumber'].'"';
			$menu .= ($data['bnumber'] == $bshow ? ' selected>' :'>');
			$menu .= $data['bshortname'].'</option>';
		}
	}
	echo $menu."\n";
	echo '</select><input type="submit" value="Go" class="formbutton" /></form>'."\n";
	echo '</h3></div>'."\n";

/**************************************
 *	   function chooser links	   *
 **************************************/
    if ($plugin->siRights()) {
        if (postVar('action') == 'update' && $manager->checkTicket()) {
            $valuearr = postVar('sid');
            if (!is_array($valuearr)) $valuearr = array($valuearr);
            $plugin->setAvailableSkins($valuearr);
        }
        $allskins = $plugin->getAllSkins();
        $selectedskins = $plugin->getAvailableSkins();
        if (count($allskins) > 1) {
			echo "<div>\n";
			echo "<h2>Skin Chooser</h2>\n";
			echo "<p>Select the skins you want to be available to the chooser.</p>\n";
            echo '<form method="post" action="'.$thispage.'">'."\n";
            echo '<input type="hidden" name="action" value="update" />'."\n";
            $manager->addTicketHidden;
			echo "<table>\n";
            $menu = '';
            foreach ($allskins as $key=>$value) {
                if (array_key_exists($key,$selectedskins)) {
                    $menu .= '<tr><td><input name="sid[]" type="checkbox" checked="checked" value="'.$key.'">'.$value."</input></td></tr>\n";
                }
                else{
                    $menu .= '<tr><td><input name="sid[]" type="checkbox" value="'.$key.'">'.$value."</input></td></tr>\n";
                }
            }
            echo $menu;
			echo "</table>\n";
            echo '<input type="submit" value="Update" class="formbutton" /></form>'."\n";
			echo "</div>\n";
        }
    }
    else {
        echo "You cannot access this page.";
    }

    $oPluginAdmin->end();

?>
