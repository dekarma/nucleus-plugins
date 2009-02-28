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
	$plugname = "NP_Ordered";

	include($strRel . 'config.php');
	if ($member->isLoggedIn() && $member->canLogin()) $admin = 1;
	else doError('You\'re not logged in.');

	include($DIR_LIBS . 'PLUGINADMIN.php');

	global $CONF,$manager;
    // $manager->checkTicket();
	$action_url = $CONF['ActionURL'];
	$thispage = $CONF['PluginURL'] . "ordered/index.php";
	$adminpage = $CONF['AdminURL'];
	$bshow = intval(requestVar('bshow'));
	$showlist = trim(strtolower(requestVar('showlist')));
	if (!in_array($showlist, array('items','cats','blogs'))) $showlist = 'items';
	$toplink = '<p class="center"><a href="'.$thispage.'?showlist='.$showlist.'&amp;bshow='.$bshow.'#sitop" alt="Return to Top of Page">-top-</a></p>'."\n";

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
	// create the admin area page
	$oPluginAdmin = new PluginAdmin('Ordered');
	sendContentType('text/html','admin-Ordered',_CHARSET);
	$oPluginAdmin->start($newhead);

	$plugin =& $oPluginAdmin->plugin;
	$sipid = $plugin->getID();

/**************************************
 *	   Edit Options Link			*
 **************************************/
	echo "\n<div>\n";
	echo '<a name="sitop"></a>'."\n";
	echo '<a class="buttonlink" href="'.$adminpage.'?action=pluginoptions&amp;plugid='.$sipid.'">Edit NP_Ordered Options</a>'."\n";
	echo "</div>\n";

/**************************************
 *	   Header	        			  *
 **************************************/
	$helplink = ' <a href="'.$adminpage.'?action=pluginhelp&amp;plugid='.$sipid.'"><img src="'.$CONF['PluginURL'].'ordered/help.jpg" alt="help" title="help" /></a>';
	echo '<h2 style="padding-top:10px;">NP_Ordered'.$helplink.'</h2>'."\n";

/**************************************
 *      Blog Selector Form            *
 **************************************/
	if ($showlist != 'blogs') {
		echo '<div class="center">'."\n";
		echo '<form name="ordChooser" method="post" action="'.$thispage.'">'."\n";
		echo '<h3>Blog to Manage: &nbsp;&nbsp;';
		echo '<select name="bshow" onChange="document.ordChooser.submit()">'."\n";
		echo '<option value="0" '.($bshow == '0' ? ' selected>' :'>').'Select a Blog</option>';
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
		echo '</select><noscript><input type="submit" value="Go" class="formbutton" /></noscript></form>'."\n";
		echo '</h3></div>'."\n";
	}

/**************************************
 *	   function chooser links	   *
 **************************************/
	echo '<div class="npordered">'."\n";
	echo "<div>\n";
	echo '<ul class="navlist">'."\n";
	if ($member->isAdmin()) {
		echo ' <li><a class="'.($showlist == 'blogs' ? 'current' : '').'" href="'.$thispage.'?showlist=blogs&amp">Manage Blogs</a></li> '."\n";
	}
	echo ' <li><a class="'.($showlist == 'items' ? 'current' : '').'" href="'.$thispage.'?showlist=items&amp;bshow='.$bshow.'">Manage Items</a></li> '."\n";
	echo ' <li><a class="'.($showlist == 'cats' ? 'current' : '').'" href="'.$thispage.'?showlist=cats&amp;bshow='.$bshow.'">Manage Categories</a></li>'."\n";
	echo " </ul></div>\n";

/*************************************
 *      display the items by order   *
 *************************************/
	if ($showlist == 'items') {
		echo '<div class="center">'."\n";
		echo "<table>\n";
		echo "<tr class=\"h\">\n";
		echo "<th>Item ID</th><th>Title</th><th>Category</th><th>Order</th><th></th></tr>\n";
		$ires = sql_query("SELECT i.inumber as itemid, o.onumber as myorder, i.ititle as title, c.cname as catname FROM ".sql_table('item')." as i, ".sql_table('plug_ordered_blog')." as o, ".sql_table('category')." as c WHERE i.inumber = o.oitemid AND i.iblog = $bshow AND i.icat = c.catid AND o.onumber>0 ORDER BY o.onumber ASC");
		while ($items = mysql_fetch_assoc($ires)) {
			obeditform($items,$bshow);
		}
		$ires = sql_query("SELECT i.inumber as itemid, o.onumber as myorder, i.ititle as title, c.cname as catname FROM ".sql_table('item')." as i, ".sql_table('plug_ordered_blog')." as o, ".sql_table('category')." as c WHERE i.inumber = o.oitemid AND i.iblog = $bshow AND i.icat = c.catid AND o.onumber=0 ORDER BY i.itime DESC");
		while ($items = mysql_fetch_assoc($ires)) {
			obeditform($items,$bshow);
		}
	}
/*************************************
 *      list the cats by order       *
 *************************************/
	if ($showlist == 'cats') {
		echo '<div class="center">'."\n";
		echo "<table>\n";
		echo "<tr class=\"h\">\n";
		echo "<th>Category ID</th><th>Name</th><th>Desc</th><th>Order</th><th>Index Template</th><th>Item Template</th><th>Show on Main Page?</th><th></th></tr>\n";
		$ires = sql_query("SELECT c.catid as catid, o.onumber as myorder, c.cdesc as cdesc, c.cname as catname, o.otemplate as template, o.oitemplate as itemplate,o.omainpage as mainpage FROM ".sql_table('category')." as c, ".sql_table('plug_ordered_cat')." as o WHERE c.catid = o.ocatid AND c.cblog = $bshow AND o.onumber>0 ORDER BY o.onumber ASC");
		while ($items = mysql_fetch_assoc($ires)) {
			oceditform($items,$bshow);
		}
		$ires = sql_query("SELECT c.catid as catid, o.onumber as myorder, c.cdesc as cdesc, c.cname as catname, o.otemplate as template, o.oitemplate as itemplate, o.omainpage as mainpage FROM ".sql_table('category')." as c, ".sql_table('plug_ordered_cat')." as o WHERE c.catid = o.ocatid AND c.cblog = $bshow AND o.onumber=0 ORDER BY c.cname ASC");
		while ($items = mysql_fetch_assoc($ires)) {
			oceditform($items,$bshow);
		}

	}

/*************************************
 *      display the blogs by order   *
 *************************************/
	if ($showlist == 'blogs') {
		echo '<div class="center">'."\n";
		echo "<table>\n";
		echo "<tr class=\"h\">\n";
		/* following is to fix data after bug preventing new blogs from getting ordered */
		$query = "SELECT bnumber as blogid FROM ".sql_table('blog')." WHERE bnumber NOT IN (SELECT oblogid FROM ".sql_table('plug_ordered_bloglist').")";
		$ires = sql_query($query);
		if (mysql_num_rows($ires)) {
			while ($items = mysql_fetch_assoc($ires)) {
				sql_query("INSERT INTO ".sql_table('plug_ordered_bloglist')." VALUES (".intval($items['blogid']).",0)");
			}
		}
		/* end data fixing code */
		echo "<th>Blog ID</th><th>Blog Name</th><th>Order</th><th></th></tr>\n";
		$ires = sql_query("SELECT b.bnumber as bnumber, o.onumber as myorder, b.bname as bname FROM ".sql_table('blog')." as b, ".sql_table('plug_ordered_bloglist')." as o WHERE b.bnumber = o.oblogid AND o.onumber>0 ORDER BY o.onumber ASC");
		while ($items = mysql_fetch_assoc($ires)) {
			obleditform($items);
		}
		$ires = sql_query("SELECT b.bnumber as bnumber, o.onumber as myorder, b.bname as bname FROM ".sql_table('blog')." as b, ".sql_table('plug_ordered_bloglist')." as o WHERE b.bnumber = o.oblogid AND o.onumber=0 ORDER BY b.bname ASC");
		while ($items = mysql_fetch_assoc($ires)) {
			obleditform($items);
		}
	}
	
	echo "</table>\n";
	echo $toplink;
	echo "</div>\n"; // end
	echo "</div>\n"; // end
	$oPluginAdmin->end();

	function obeditform($items,$bid) {
        global $CONF, $manager;
        $action_url = $CONF['ActionURL'];
		echo '<tr><form method="post" action="'.$action_url.'">'."\n";
        echo '<input type="hidden" name="action" value="plugin" />'."\n";
        echo '<input type="hidden" name="name" value="Ordered" />'."\n";
        echo '<input type="hidden" name="type" value="modorderi" />'."\n";
        echo '<input type="hidden" name="oitemid" value="'.$items['itemid'].'" />'."\n";
		echo '<input type="hidden" name="bid" value="'.$bid.'" />'."\n";
        $manager->addTicketHidden();
        echo '<td>'.$items['itemid']."</td>\n";
		echo '<td>'.$items['title']."</td>\n";
		echo '<td>'.$items['catname']."</td>\n";
        echo '<td><input name="onumber" value="'.$items['myorder'].'" />'."</td>\n";
		echo '<td><input type="submit" value="Set" />'."</td>\n";
		echo "</form></tr>\n";
	}

	function oceditform($items,$bid) {
        global $CONF, $manager;
        $action_url = $CONF['ActionURL'];
		$omp = intval($items['mainpage']);
		echo '<tr><form method="post" action="'.$action_url.'">'."\n";
        echo '<input type="hidden" name="action" value="plugin" />'."\n";
        echo '<input type="hidden" name="name" value="Ordered" />'."\n";
        echo '<input type="hidden" name="type" value="modorderc" />'."\n";
        echo '<input type="hidden" name="ocatid" value="'.$items['catid'].'" />'."\n";
		echo '<input type="hidden" name="bid" value="'.$bid.'" />'."\n";
        $manager->addTicketHidden();
        echo '<td>'.$items['catid']."</td>\n";
		echo '<td>'.$items['catname']."</td>\n";
		echo '<td>'.$items['cdesc']."</td>\n";
        echo '<td><input name="onumber" value="'.$items['myorder'].'" />'."</td>\n";
		echo '<td><input name="otemplate" value="'.$items['template'].'" />'."</td>\n";
		echo '<td><input name="oitemplate" value="'.$items['itemplate'].'" />'."</td>\n";
		echo '<td><input type="radio" name="omp" value="1"'.($omp == 1 ? ' checked="checked"' : '').'> yes </input>';
		echo '<input type="radio" name="omp" value="0"'.($omp == 0 ? ' checked="checked"' : '').'> no </input></td>'."\n";
		echo '<td><input type="submit" value="Set" />'."</td>\n";
		echo "</form></tr>\n";
	}
	
	function obleditform($items) {
        global $CONF, $manager;
        $action_url = $CONF['ActionURL'];
		echo '<tr><form method="post" action="'.$action_url.'">'."\n";
        echo '<input type="hidden" name="action" value="plugin" />'."\n";
        echo '<input type="hidden" name="name" value="Ordered" />'."\n";
        echo '<input type="hidden" name="type" value="modorderb" />'."\n";
        echo '<input type="hidden" name="oblogid" value="'.$items['bnumber'].'" />'."\n";
		echo '<input type="hidden" name="bid" value="'.$items['bnumber'].'" />'."\n";
        $manager->addTicketHidden();
        echo '<td>'.$items['bnumber']."</td>\n";
		echo '<td>'.$items['bname']."</td>\n";
        echo '<td><input name="onumber" value="'.$items['myorder'].'" />'."</td>\n";
		echo '<td><input type="submit" value="Set" />'."</td>\n";
		echo "</form></tr>\n";
	}

?>
