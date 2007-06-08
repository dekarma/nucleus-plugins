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
	$adminpage = $CONF['AdminURL'];
	$thispage = $CONF['PluginURL'] . "skinchooser/index.php";
	$bid = intval(requestVar('bid'));
	$toplink = '<p class="center"><a href="'.$thispage.'?showlist='.$showlist.'&amp;bid='.$bid.'#sitop" alt="Return to Top of Page">-top-</a></p>'."\n";

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
	echo '<h2 style="padding-top:10px;">NP_SkinChooser'.$helplink.'</h2>'."\n";

/**************************************
 *      Blog Selector Form            *
 **************************************/
    if ($plugin->scIsBlogAdmin()) {
        echo '<div class="center">'."\n";
        echo '<form name="scBlogChooser" method="post" action="'.$thispage.'">'."\n";
        echo '<h3>Blog to Manage: &nbsp;&nbsp;';
        echo '<select name="bid" onChange="document.scBlogChooser.submit()">'."\n";
        if ($member->isAdmin())	echo '<option value="0" '.($bid == '0' ? ' selected>' :'>').'All</option>';
        $bres = sql_query("SELECT bnumber,bshortname FROM ".sql_table('blog'));
        while ($data = mysql_fetch_assoc($bres))
        {
            $numbs = 0;
            if ($member->blogAdminRights(intval($data['bnumber']))) {
                if ($bid == 0 && $numbs == 0 && !$member->isAdmin()) {
                    $bid = $data['bnumber'];
                    $numbs = 1;
                }
                $menu .= '<option value="'.$data['bnumber'].'"';
                $menu .= ($data['bnumber'] == $bid ? ' selected>' :'>');
                $menu .= $data['bshortname'].'</option>';

            }
        }
        echo $menu."\n";
        echo '</select><noscript><input type="submit" value="Go" class="formbutton" /></noscript></form>'."\n";
        echo '</h3></div>'."\n";
    }
    else {
        echo "You do not permission to administer this plugin.<br />\n";
    }

/**************************************
 *	   the work                       *
 **************************************/
    if ($plugin->scRights($bid)) {
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
            $plugin->setConfigSettings($valuearr,$bid);
        }
        if ($bid < 1) $allskins = $plugin->getAllSkins();
        else $allskins = $plugin->getAvailableSkins(0);
        $selectedskins = $plugin->getAvailableSkins($bid);
        if (count($selectedskins) === 0) $selectedskins = $allskins;
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
            $manager->addTicketHidden();
            echo '<input type="submit" value="Update Config" class="formbutton" />'."\n";
            echo "<table>\n";
            echo "<tr><td>Do you want to disable SkinChooser for this blog?</td><td>\n";
            echo '<input type="radio" name="disabled" value="1"'.($disabled > 0 ? ' checked="checked"' : '').'> Yes</input>' . "\n";
            echo '<input type="radio" name="disabled" value="0"'.($disabled < 1 ? ' checked="checked"' : '').'> No</input>' . "\n";
            echo "</td></tr>\n";
            echo "<tr><td>Do you want to serve random skins to new users?</td><td>\n";
            echo '<input type="radio" name="random" value="1"'.($random > 0 ? ' checked="checked"' : '').'> Yes</input>' . "\n";
            echo '<input type="radio" name="random" value="0"'.($random < 1 ? ' checked="checked"' : '').'> No</input>' . "\n";
            echo "</td></tr>\n";
            echo "</table>\n";
            echo '<input type="submit" value="Update Config" class="formbutton" /></form>'."\n";

            if (count($allskins) > 1) {
                echo "<p>Select the skins you want to be available to the chooser.</p>\n";
                if ($bid == 0) echo "<p>For blog 'All' you are selecting skins that are or are not available to the individual blogs. All SkinChooser-ready skins should be enabled here. Individual blog owners can choose to disable any unwanted skin for his own blog.</p>\n";
                echo '<form method="post" action="'.$thispage.'">'."\n";
                echo '<input type="hidden" name="action" value="update" />'."\n";
                echo '<input type="hidden" name="bid" value="'.$bid.'" />'."\n";
                $manager->addTicketHidden();
                echo '<input type="submit" value="Update Skins" class="formbutton" />'."\n";
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
                echo '<input type="submit" value="Update Skins" class="formbutton" /></form>'."\n";

            }
            echo "</div>\n";
        }
    }
    else {
        echo "You cannot access this page.<br />\n";
    }

    $oPluginAdmin->end();

?>
