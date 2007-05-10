<?php
/* NP_SkinChooser */

	$strRel = '../../../';
	include($strRel . 'config.php');
	include($DIR_LIBS . 'PLUGINADMIN.php');

	// Send out Content-type
	sendContentType('text/html', 'admin-skinchooser', _CHARSET);

	if (!($member->isLoggedIn()))
		doError('You do not have admin rights for any blogs.');

    global $CONF,$manager;

    $thispage = $CONF['PluginURL'] . "skinchooser/index.php";

	$oPluginAdmin = new PluginAdmin('SkinChooser');
    $oPluginAdmin->start($newhead);
    $plugin =& $oPluginAdmin->plugin;
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
        echo "You do not access to this page.";
    }

    $oPluginAdmin->end();

?>