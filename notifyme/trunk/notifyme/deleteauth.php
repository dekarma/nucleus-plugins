<?php
	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../'; 

	include($strRel . 'config.php');
        if ($blogid) {$isblogadmin = $member->isBlogAdmin($blogid);}
        else $isblogadmin = 0;

        if (!($member->isAdmin() || $isblogadmin)) {
                $oPluginAdmin = new PluginAdmin('Blacklist');
                $pbl_config = array();
                $oPluginAdmin->start();
                echo "<p>"._ERROR_DISALLOWED."</p>";
                $oPluginAdmin->end();
                exit;
        }

	include($DIR_LIBS . 'PLUGINADMIN.php');

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('NotifyMe');
	$oPluginAdmin->start();

	echo '<h2>NotifyMe Authenication Delete (All) Function</h2>';

	$query = "DELETE FROM " . sql_table('plugin_notifyaddress') . " WHERE validate >1";
        $rows = mysql_query($query);

        echo "Done<br/><br/>";

        echo "<a href=\"./plugins/notifyme/\">Return to Notification Management</a>";

	$oPluginAdmin->end();
?>
