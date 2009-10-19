<?php
/* This is the Admin Area page for the NP_MailForm Plugin.
License:
This software is published under the same license as NucleusCMS, namely
the GNU General Public License. See http://www.gnu.org/licenses/gpl.html for
details about the conditions of this license.

In general, this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free
Software Foundation; either version 2 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

 */
	// if your 'plugin' directory is not in the default location, edit this
    // variable to point to your site directory (where config.php is)
	$strRel = '../../../';
	$plugname = "NP_ThickBox";
	$shortplugname = str_replace('NP_','',$plugname);

	include($strRel . 'config.php');
	if (!$member->isAdmin())
		doError("You cannot access this area.");

	include($DIR_LIBS . 'PLUGINADMIN.php');

	global $CONF,$manager,$DIR_MEDIA;
	
	//$manager->checkTicket();
	$thispage = $CONF['PluginURL'] . strtolower($shortplugname)."/index.php";
	$newhead = '';

	// create the admin area page
	$oPluginAdmin = new PluginAdmin($shortplugname);
	$oPluginAdmin->start($newhead);

	$plugin =& $oPluginAdmin->plugin;
	$pid = $plugin->getID();
	$media_path = $plugin->getOption('imagePath');
	$cache_path = $media_path."thumb_cache";

	echo "<div>\n";
	echo "<h3>$plugname Thumbnail Cache Actions</h3>\n";

	if (is_writable($cache_path)) {
		$cache_path .= "/";
		if (postVar('cache_action') == 'clearall' && $manager->checkTicket()) {
			if (is_dir($cache_path)) {
				if ($dh = opendir($cache_path)) {
					while (($file = readdir($dh)) !== false) {
						if (is_file($cache_path.$file)) 
							unlink($cache_path.$file);
					}
					closedir($dh);
					echo 'Your thumbnail cache has been cleared <br />';
				}
				else 'Your thumbnail cache could not be cleared <br />';
			}
		}
		else {
			echo '<form method="post" action="'.$thispage.'">'."\n";
			$manager->addTicketHidden();
			echo '<input type="hidden" name="cache_action" value="clearall" />'."\n";
			echo '<input type="submit" value="Clear Cache" class="formbutton" />'."\n";
			echo "</form>\n";
		}
	}
	else {
		echo "Your media folder must be writable to enable thumbnail caching.";
	}
	
// close page
	echo "</div>\n";
	$oPluginAdmin->end();


?>