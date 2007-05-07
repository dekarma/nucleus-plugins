<?php

	/* History:
		v0.0 - initial version
		v0.1 - detect config.php access problem (admun)
		v0.2 - fixed link to item with // in fancyURL mode
		v0.3 - fixed non-member login (ported from B:cms)
		v0.4 - added batch deny action
                v0.5 - fixed item url
	*/

	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../'; 

	// check it the config.php is there....
	if (!is_readable($strRel. 'config.php')) {
		echo 'Fatal Error: Unable to access config.php... please make sure $strRel is pointing to the right path (see commentcontrol/index.php)';
		exit();
	}
	
	include($strRel . 'config.php');
	if (!$member->isLoggedIn())
		doError('You\'re not logged in.');
		
	include($DIR_LIBS . 'PLUGINADMIN.php');

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('CommentControl');
	$oPluginAdmin->start();

	// get list of comments to approve
	$aList = $oPluginAdmin->plugin->_getPendingInfo();
	echo '<h2>Comment Control</h2>';

	// Delete all pendings if being asked
	$action=$_GET[action];
	if ($action == "deleteall")
	{
                if (!$member->isAdmin())
                        doError('You do not have permission to perform delete all pending messages action (Admin only).');
		$query = "DELETE FROM " . $oPluginAdmin->plugin->table_pending . ";";
		sql_query($query);
		echo "All pending message deleted";
		$oPluginAdmin->end();
		return;
	}

	if (count($aList) > 0)
	{
		// Show action to delete all pending
		if ($action != "deleteall")
		{
			?>
			<ul>
			<?php
			echo "<li><a href=\"" . $CONF['PluginURL'] . "commentcontrol/" ."?action=deleteall\">Deny all pending messages</a></li>\n";
			?>
			</ul>
			<?php
		}

		echo '<p>Below are the comments that are awaiting approval.</p>';

		echo '<table><tr>';
			echo '<th>Item Title &amp; Comment</th>';
			echo '<th>User</th>';
			echo '<th>Actions</th>';
		
		foreach ($aList as $aPendingInfo)
		{
			$urlallow	= $CONF['ActionURL'] . '?action=plugin&name=CommentControl&type=allowadmin&id=' . $aPendingInfo['id'];
			$urldeny	= $CONF['ActionURL'] . '?action=plugin&name=CommentControl&type=denyadmin&id=' . $aPendingInfo['id'];
		
			echo '</tr><tr>';		
			echo '<td>';

                        echo '<a href="',$CONF['IndexURL'],createItemLink($aPendingInfo['itemid']),'"><strong>' , htmlspecialchars(shorten($aPendingInfo['itemtitle'],100,'...')), '</strong></a><br />'; 

			echo $aPendingInfo['comment'];
			echo '</td>';
			echo '<td>';
				echo htmlspecialchars(shorten($aPendingInfo['user'],50,'...'));
			echo '</td>';
			echo '<td>';
				echo '<a href="'.htmlspecialchars($urlallow).'">allow</a>';
				echo '<br />';
				echo '<a href="'.htmlspecialchars($urldeny).'">deny</a>';
			echo '</td>';
		}
		
		echo '</tr></table>';
		
	} else {
		echo '<p>There are currently no comments awaiting approval.</p>';
	}
	
	$oPluginAdmin->end();
	
?>
