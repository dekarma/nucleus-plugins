<?php
	/*
	    History
	    v0.1 (v0.17) - place holder version
	    v0.2 (v0.18) - initial release
	    v0.3 (v0.20) - add sorting
            v0.4 (v0.51) - add link to mass delete function
            v0.5 (v0.52) - show subscription id
            v0.6 (v0.60) - fixed user/admin check
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

	echo '<h2>NotifyMe Mass Delete Function</h2>';

	$query = "SELECT id,email,blogID,itemid FROM " . sql_table('plugin_notifyaddress') . " WHERE validate=1 ORDER by id";
        $rows = mysql_query($query);
?>

<form ACTION="<? echo $CONF['PluginURL']; ?>notifyme/mdelete.php">
 Input range of subscription to delete:<br />
   from <input type="text" NAME="low" SIZE=6 VALUE="0"> to <input type="text" NAME="high" SIZE=6 VALUE="0"><br />
   <br />
 Or selected subscriptions to delete:<br />
<?
	while($row = mysql_fetch_object($rows)) {
                if ($row->itemid == 0)
                {
                   $item = "all";
                }
                else
                {
                   $item = $row->itemid;
                }

                $label = $row->email . " (subscription=" . $row->id . ", itemid=<a href=\"" . $CONF['IndexURL'] .
                createItemLink($row->itemid) . "\">" . $item . "</a>, blog=" . getBlogNameFromID($row->blogID) . ")";
                echo "<input type=\"checkbox\" name=\"id\" value=\"" . $row->id . "\">" . $label . "<br />";
	}
?>
 <input type="submit" name="delete" value="Delete Selected Subscriptons">
 </form>

<?
	$oPluginAdmin->end();
?>
