<?php
	/*
	    History
	    v0.1 (v0.17) - place holder version
	    v0.2 (v0.18) - initial release
	    v0.3 (v0.20) - add sorting
            v0.4 (v0.51) - add link to mass delete function
            v0.5 (v0.52) - add unauth user list/remove
            v0.6 (v0.60) - fixed user/admin check
	    v0.7 (v0.66) - fixed blog/item url on table
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

	echo '<h2>NotifyMe Subscription Management</h2>';

        $query = "SELECT count(*) as total FROM ".sql_table('plugin_notifyaddress')." WHERE validate>1";
        $res = mysql_query($query);
        $row = mysql_fetch_object($res);

        echo "There are " . $row->total . " unauthenicate subscribers [<a href=\"". $CONF['PluginURL'] . "notifyme/deleteauth.php" . "\">delete all unauth subscribers]</a><p />";

        echo "<a href=\"". $CONF['PluginURL'] . "notifyme/delete.php\">Click here to Mass delete subscrption</a>";

	$offset = intRequestVar('offset');
	$sorting = "id";
	$sortby = getVar('sort');
	if ($sortby == "sub")
	{
	  $sorting = "id";
	} else if ($sortby == "email")
	{
	  $sorting = "email";
	} else if ($sortby == "blog")
	{
	  $sorting = "blogId";
	} else if ($sortby == "item")
	{
	  $sorting = "itemid";
	}
	global $CONF;

	$notifyURL = $CONF['PluginURL']."notifyme/index.php";
	$query = "SELECT id,email,blogID,itemid FROM " .  sql_table('plugin_notifyaddress') . " WHERE validate=1 ORDER BY " . $sorting;
        $rows = mysql_query($query);
        echo "<table>\n";
        echo "<tr><th><a href=\"" . $notifyURL . "?sort=sub\">Subscription ID</a></th><th><a href =\"" . $notifyURL .  "?sort=email\">Email</a></th><th><a href =\"" .  $notifyURL . "?sort=blog\">Blog ID</a></th><th><a href =\"" .  $notifyURL . "?sort=item\">Item ID</a></th><th>Actions</th></tr>";

	while($row = mysql_fetch_object($rows)) {
		echo "<tr>";
		echo "<td>" . $row->id . "</td>";
		echo "<td><a href=\"mailto:" . $row->email . "\">".$row->email."</td>";
		echo "<td>" . "<a href=\"" . createBlogidLink($row->blogID) . "\">" . getBlogNameFromID($row->blogID) . "</a></td>";
		if ($row->itemid == 0) {
			echo "<td>all</td>";
		} else {
			$blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
			$CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());
			echo "<td><a href=\"" . createItemLink($row->itemid) . "\">" . $row->itemid . "</a></td>";
		}
		$delurl = $CONF['ActionURL'] . '?action=plugin&name=NotifyMe&type=form&emailaddress=' . $row->email
			. '&redirecturl=' . $CONF['AdminURL'] . 'plugins/notifyme&currBlogId=' . $row->blogID;
		if ($row->itemid != 0) {
			$delurl = $delurl . '&itemid=' . $row->itemid;
		}

		echo "<td>" . "<a href=\"" . $delurl . "\">Delete</a>" . "</td>";
		echo "</tr>";
	}

	echo "</table>\n";

	$oPluginAdmin->end();
?>
