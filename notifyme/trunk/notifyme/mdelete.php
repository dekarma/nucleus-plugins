<?php
   /*
      v0.1 - initial version
      v0.2 - fixed user/admin check
   */
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
?>
   Deleting subscriptions...<br />
<?
        $low = 0;
        $high = 0;
	$raw_input_items = split("&", $_SERVER["QUERY_STRING"]);
        foreach ($raw_input_items as $input_item) {
                $item = split("=", $input_item);
                if ($item[0] == "low")
                {
                   $low = $item[1];
                   continue;
                }

                if ($item[0] == "high")
                {
                   $high = $item[1];
                   continue;
                }

                if ($low !=0 && $high !=0 && $low < $high)
                {
                        $query = "DELETE FROM " . sql_table('plugin_notifyaddress') . " WHERE id >=" . $low . " AND id <= " .  $high;
                        mysql_query($query);
                        echo "subscription " . $low . " to " . $high . " deleted<br />";
                        break;
                }


                if ($item[0] == "id")
                {
                        $query = "DELETE FROM " . sql_table('plugin_notifyaddress') . " WHERE id=" . $item[1];
                        mysql_query($query);
                        echo "subscription " . $item[1] . " deleted<br /><br />";
                }
        }

        echo "<a href=\"". $CONF['PluginURL'] . "notifyme/delete.php\">Return to Mass delete function</a>";

        $oPluginAdmin->end();
?>
