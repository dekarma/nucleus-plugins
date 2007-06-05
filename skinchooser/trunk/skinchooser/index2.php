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
	$bid = intval(requestVar('bid'));
	//$showlist = trim(strtolower(requestVar('showlist')));
	//if (!in_array($showlist, array('items','cats'))) $showlist = 'items';
	$toplink = '<p class="center"><a href="'.$thispage.'?showlist='.$showlist.'&amp;bid='.$bid.'#sitop" alt="Return to Top of Page">-top-</a></p>'."\n";
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
	echo '<select name="bid">'."\n";
	echo '<option value="0" '.($bid == '0' ? ' selected>' :'>').'All</option>';
	$bres = sql_query("SELECT bnumber,bshortname FROM ".sql_table('blog'));
	while ($data = mysql_fetch_assoc($bres))
	{
		if ($member->blogAdminRights(intval($data['bnumber']))) {
			$menu .= '<option value="'.$data['bnumber'].'"';
			$menu .= ($data['bnumber'] == $bid ? ' selected>' :'>');
			$menu .= $data['bshortname'].'</option>';
		}
	}
	echo $menu."\n";
	echo '</select><input type="submit" value="Go" class="formbutton" /></form>'."\n";
	echo '</h3></div>'."\n";

/**************************************
 *	   the work                       *
 **************************************/
    if ($plugin->siRights()) {
        if (postVar('action') == 'update' && $manager->checkTicket()) {
            $valuearr = postVar('sid');
            $bid = intPostVar('bid');
            if (!is_array($valuearr)) $valuearr = array($valuearr);
            $plugin->setAvailableSkins($valuearr,$bid);
        }
        if (postVar('action') == 'updateconfig' && $manager->checkTicket()) {
            $disabled = intPostVar('disabled');
            $random = intPostVar('random');
            $valuearr = array('disabled'=>$disabled, 'random'=>$random);
            $bid = intPostVar('bid');
            //if (!is_array($valuearr)) $valuearr = array($valuearr);
            $plugin->setConfigSettings($valuearr,$bid);
        }
        $allskins = $plugin->getAllSkins();
        $selectedskins = $plugin->getAvailableSkins($bid);
        if ( ($bid == 0 && !$member->isAdmin()) || ($bid > 0 && !$member->blogAdminRights($bid)) ) {
            echo "No actions are available to you!<br />\n";
        }
        else {
            echo "<div>\n";
            echo "<h2>Skin Chooser</h2>\n";

            $configarr = $plugin->getConfigSettings($bid);
            $disabled = intval($configarr['disabled']);
            $random = intval($configarr['random']);
            if ($bid == 0) echo "<p>For blog 'All' these settings are global to all blogs. If you disable a setting here, it is disabled for all blogs. If the setting is enabled here, it can be overridden by any specific blog.</p>\n";
            echo "<p>Configuration Settings.</p>\n";
            echo '<form method="post" action="'.$thispage.'">'."\n";
            echo '<input type="hidden" name="action" value="updateconfig" />'."\n";
            echo '<input type="hidden" name="bid" value="'.$bid.'" />'."\n";
            $manager->addTicketHidden;
            echo "<table>\n";
            echo "<tr><td>Do you want to disable SkinChooser for this blog?</td><td>\n";
            echo '<input type="radio" name="disabled" value="1"'.($disabled > 0 ? ' checked="checked"' : '').'> Yes</input>' . "\n";
            echo '<input type="radio" name="disabled" value="0"'.($disabled < 1 ? ' checked="checked"' : '').'> No</input>' . "\n";
            echo "</td></tr>\n";
            echo "<tr><td>Do you want to serve random skins to new users?</td><td>\n";
            echo '<input type="radio" name="disabled" value="1"'.($random > 0 ? ' checked="checked"' : '').'> Yes</input>' . "\n";
            echo '<input type="radio" name="disabled" value="0"'.($random < 1 ? ' checked="checked"' : '').'> No</input>' . "\n";
            echo "</td></tr>\n";
            echo "</table>\n";
            echo '<input type="submit" value="Update" class="formbutton" /></form>'."\n";

            if (count($allskins) > 1) {
                echo "<p>Select the skins you want to be available to the chooser.</p>\n";
                if ($bid == 0) echo "<p>For blog 'All' you are selecting skins that are or are not available to the individual blogs. All SkinChooser-ready skins should be enabled here. Individual blog owners can choose to disable any unwanted skin for his own blog.</p>\n";
                echo '<form method="post" action="'.$thispage.'">'."\n";
                echo '<input type="hidden" name="action" value="update" />'."\n";
                echo '<input type="hidden" name="bid" value="'.$bid.'" />'."\n";
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

            }
            echo "</div>\n";
        }
    }
    else {
        echo "You cannot access this page.<br />\n";
    }

    $oPluginAdmin->end();

?>
