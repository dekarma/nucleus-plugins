<?php
/* Admin area of NP_SiteList plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, based on work by Wouter Demuynck
 * http://www.iai.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 */

	// if your 'plugin' directory is not in the default location, edit this
    // variable to point to your site directory (where config.php is)
	$strRel = '../../../';
	$plugname = "NP_SiteList";

	include($strRel . 'config.php');
	include('slfunctions.php');
	if (!$member->isLoggedIn())
		doError("You are not logged in.");

	include($DIR_LIBS . 'PLUGINADMIN.php');

	global $CONF,$manager;
	$showlist = trim(strtolower(requestVar('showlist')));
	$safe = trim(strtolower(requestVar('safe')));
	$siteurl = requestVar('surl');
	$sitedesc = requestVar('sdesc');
	$nshow = trim(strtolower(requestVar('nshow')));
	$pg = requestVar('pg');
	$slquery = requestVar('slquery');
    $slquery = preg_replace('|[^a-z0-9]|i', ' ', $slquery);
	if ($pg == '') $pg = 1;
	$action_url = $CONF['ActionURL'];
	$thispage = $CONF['PluginURL'] . "sitelist/index.php";
	$testerpage = $CONF['PluginURL'] . "sitelist/sltest.php";
	$adminpage = $CONF['AdminURL'];
	$admin = $member->isAdmin();
	$linkstyle = 'border:outset 1px;padding:2px;background-color:#dddddd;';

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('SiteList');
	$oPluginAdmin->start();

    $sitelist =& $oPluginAdmin->plugin;
	//$sitelist =& $manager->getPlugin('NP_SiteList');
	$slpid = $sitelist->getID();

	$verify = 1;
	$cvalue = $sitelist->getOption('Cond01');
	if ($cvalue == NULL || !$admin) $verify = 0;

    $pagetext = _SITELIST_PAGE;
    $oftext = _SITELIST_OF;

/**************************************
 *       Edit Options Link            *
 **************************************/
	echo '<div style="float:left;padding-right:20px;">'."\n";
	if ($verify) echo '<a style="'.$linkstyle.'" href="'.$adminpage.'?action=pluginoptions&amp;plugid='.$slpid.'">'._SITELIST_ADMIN_OPTIONS.'</a>'."\n";
	echo '</div>'."\n";

	if ($nshow == NULL) $nshow = $sitelist->getOption('PageSize');

/**************************************
 *       PageSize Selector Form       *
 **************************************/
	echo '<form  style="float:right;padding-right:20px;" method="post" action="">'."\n";
	echo '<input type="hidden" name="pg" value="1" />'."\n";
	echo _SITELIST_ADMIN_PER_PAGE;
	echo '<select name="nshow">'."\n";
	$disparray = array('All','10','25','50','100','500');
	$i = 0;
	while ($data = $disparray[$i])
	{
		$menu .= '<option value="'.$data.'"';
		$menu .= ($data == $nshow ? ' selected>' :'>');
		$menu .= $data.'</option>';
		$i++;
	}
	echo $menu."\n";
	echo '</select><input type="submit" value="'._SITELIST_SET.'" class="formbutton" /></form>'."\n";
	echo '<br />'."\n";
	if ($nshow == 'All' || $nshow == '') $nshow = 999999;

/**************************************
 *       SiteList Header              *
 **************************************/
	if ($verify) $helplink = ' <a href="'.$adminpage.'?action=pluginhelp&amp;plugid='.$slpid.'"><img src="'.$CONF['PluginURL'].'sitelist/help.jpg" alt="help" title="help" /></a>';
	echo '<h2 style="padding-top:10px;">SiteList'.$helplink.'</h2>'."\n";

/**************************************
 *       function chooser links       *
 **************************************/
	echo '<div style="float:left;padding-bottom:10px;">'."\n";
	echo ' <a style="'.$linkstyle.'" href="'.$thispage.'?showlist=unchecked&amp;safe=true&amp;nshow='.$nshow.'">'._SITELIST_ADMIN_UNCHECKED."</a> . \n";
	echo ' <a style="'.$linkstyle.'" href="'.$thispage.'?showlist=checked&amp;safe=true&amp;nshow='.$nshow.'">'._SITELIST_ADMIN_CHECKED."</a> . \n";
	echo ' <a style="'.$linkstyle.'" href="'.$thispage.'?showlist=suspended&amp;safe=true&amp;nshow='.$nshow.'">'._SITELIST_ADMIN_SUSPENDED."</a> . \n";
	echo ' <a style="'.$linkstyle.'" href="'.$thispage.'?showlist=addurl&amp;safe=true&amp;nshow='.$nshow.'">'._SITELIST_ADMIN_ADDURL."</a>\n";
	echo ' </div>'."\n";
	echo ' <div style="float:right;padding-bottom:10px;">'."\n";
	slsearchform($slquery);
	echo '</form>'."\n";
	echo ' </div>'."\n";
	echo '<br /><br />'."\n";

/**************************************
 *     UnChecked Sites                *
 **************************************/
	if ($showlist == "unchecked" || $showlist == NULL)
	{
	$ucsites = $sitelist->getUnChecked(0);
	$rows = mysql_num_rows($ucsites);

	if ($rows > $nshow) {
		if ($rows % $nshow == 0) $npages = intval($rows / $nshow);
		else $npages = intval(($rows / $nshow) + 1);
		echo "<h3>"._SITELIST_ADMIN_UC_HEAD." [$rows] \n";
		echo '<a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.($pg > 1 ? $pg-1:1).'">&lt;&lt;</a> '."\n";
		echo "<small>$pagetext $pg $oftext $npages</small>\n";
		echo ' <a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.($pg < $npages ? $pg+1:$npages).'">&gt;&gt;</a> '."\n";
		echo "</h3>\n";
		slpagebar($thispage,$showlist,$nshow,$npages,$pg);
		$offset = (($pg * $nshow) - $nshow) + 1;
		$ucsites = $sitelist->getUnChecked(0,$offset,$nshow);
		echo "<div>\n";
		if ($verify) echo ' <a style="'.$linkstyle.'" href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=verifyunchecked&amp;nshow='.$nshow.'&amp;offset='.$offset.'">'._SITELIST_ADMIN_VERIFY_PAGE."</a> . \n";
	}
	else echo "<h3>"._SITELIST_ADMIN_UC_HEAD." [$rows] </h3><div>\n";

	if ($rows != 0) {
		if ($verify) echo ' <a style="'.$linkstyle.'" href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=verifyunchecked">'._SITELIST_ADMIN_UC_VERIFY_ALL."</a>\n";
	}
	else echo _SITELIST_ADMIN_UC_NOSITES."\n";
	echo "</div>\n";
	echo "<ul>\n";
	while ($site = mysql_fetch_object($ucsites)) {
        $site->ourl = $site->url;
		$site->url = $sitelist->prepURL($site->url);
        $site->title = $sitelist->prepDesc($site->title,$site->url);
		sllisturl($site,$admin,$verify,$nshow,$showlist,$thispage,$action_url,$testerpage);
	}
	echo "</ul>\n";
	} //end show unchecked
/**************************************
 *     Checked Sites                  *
 **************************************/
	if ($showlist == "checked")
	{
	$csites = $sitelist->getChecked(0);
	$rows = mysql_num_rows($csites);

	if ($rows > $nshow) {
		if ($rows % $nshow == 0) $npages = intval($rows / $nshow);
		else $npages = intval(($rows / $nshow) + 1);
		echo "<h3>"._SITELIST_ADMIN_C_HEAD." [$rows] \n";
		echo '<a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.($pg > 1 ? $pg-1:1).'">&lt;&lt;</a> '."\n";
		echo "<small>$pagetext $pg $oftext $npages</small>\n";
		echo ' <a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.($pg < $npages ? $pg+1:$npages).'">&gt;&gt;</a> '."\n";
		echo "</h3>\n";
		slpagebar($thispage,$showlist,$nshow,$npages,$pg);
		$offset = (($pg * $nshow) - $nshow) + 1;
		$csites = $sitelist->getChecked(0,$offset,$nshow);
		echo "<div>\n";
		if ($verify) echo ' <a style="'.$linkstyle.'" href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=verifychecked&amp;nshow='.$nshow.'&amp;offset='.$offset.'">'._SITELIST_ADMIN_VERIFY_PAGE."</a> . \n";
	}
	else echo "<h3>"._SITELIST_ADMIN_C_HEAD." [$rows] </h3><div>\n";

	if ($rows != 0) {
		if ($verify) echo ' <a style="'.$linkstyle.'" href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=verifychecked">'._SITELIST_ADMIN_C_VERIFY_ALL."</a>\n";
	}
	else echo _SITELIST_ADMIN_C_NOSITES."\n";
	echo "</div>\n";
	echo "<ul>\n";
	while ($site = mysql_fetch_object($csites)) {
        $site->ourl = $site->url;
		$site->url = $sitelist->prepURL($site->url);
        $site->title = $sitelist->prepDesc($site->title,$site->url);
		sllisturl($site,$admin,$verify,$nshow,$showlist,$thispage,$action_url,$testerpage);
	}
	echo "</ul>\n";
	if ($rows > $nshow) {
		slpagebar($thispage,$showlist,$nshow,$npages,$pg);
	}
	} //end show checked

/**************************************
 *     Suspended Sites                *
 **************************************/
        if ($showlist == "suspended")
        {
 	$ssites = $sitelist->getSuspended();
 	$rows = mysql_num_rows($ssites);

	if ($rows > $nshow) {
		if ($rows % $nshow == 0) $npages = intval($rows / $nshow);
		else $npages = intval(($rows / $nshow) + 1);
		echo "<h3>"._SITELIST_ADMIN_S_HEAD." [$rows] \n";
		echo '<a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=false&amp;nshow='.$nshow.'&amp;pg='.($pg > 1 ? $pg-1:1).'">&lt;&lt;</a> '."\n";
		echo "<small>$pagetext $pg $oftext $npages</small>\n";
		echo ' <a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=false&amp;nshow='.$nshow.'&amp;pg='.($pg < $npages ? $pg+1:$npages).'">&gt;&gt;</a> '."\n";
		echo "</h3>\n";
		slpagebar($thispage,$showlist,$nshow,$npages,$pg);
		$offset = (($pg * $nshow) - $nshow) + 1;
		$ssites = $sitelist->getSuspended(0,$offset,$nshow);
		echo "<div>\n";
		if ($verify) echo ' <a style="'.$linkstyle.'" href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=deletesuspended&amp;nshow='.$nshow.'&amp;offset='.$offset.'">'._SITELIST_ADMIN_DELETE_PAGE."</a> . \n";
		if ($verify) echo ' <a style="'.$linkstyle.'" href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=verifysuspended&amp;nshow='.$nshow.'&amp;offset='.$offset.'">'._SITELIST_ADMIN_VERIFY_PAGE."</a> . \n";
	}
	else echo "<h3>"._SITELIST_ADMIN_S_HEAD." [$rows] </h3><div>\n";
	if ($safe == 'true') {
		if ($verify) echo ' <a style="'.$linkstyle.'" href="'.$thispage.'?showlist='.$showlist.'&amp;safe=false&amp;nshow='.$nshow.'">'._SITELIST_ADMIN_S_DELETE_ALL."</a> . \n";
		if ($verify) echo ' <a style="'.$linkstyle.'" href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=verifysuspended">'._SITELIST_ADMIN_S_VERIFY_ALL."</a>\n";
	}
	else {
		echo ' <p><ul style="font-weight:bold;"><li style="color:#fe0000;">'._SITELIST_ADMIN_S_DELETE_ALL_WARN."</li></ul></p>\n";
		echo ' <a style="'.$linkstyle.'" href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=deletesuspended">['._SITELIST_ADMIN_S_DELETE_ALL_CONT."]</a> . \n";
		echo ' <a style="'.$linkstyle.'" href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.$pg.'">['._SITELIST_CANCEL."]</a>\n";
	}

	echo "</div>\n";
 	echo "<p>"._SITELIST_ADMIN_S_NOTE."</p>\n";
	echo "<ul>\n";
	while ($site = mysql_fetch_object($ssites)) {
        $site->ourl = $site->url;
		$site->title = $sitelist->prepDesc($site->title,$site->url);
		$site->url = $sitelist->prepURL($site->url);
		sllisturl($site,$admin,$verify,$nshow,$showlist,$thispage,$action_url,$testerpage);
	}
	echo "</ul>\n";
	if ($rows > $nshow) {
		slpagebar($thispage,$showlist,$nshow,$npages,$pg);
	}

        } //end show suspended

/**************************************
 *     Add URL form                   *
 **************************************/
	if ($showlist == "addurl" || $showlist == "modurl")
	{
	if ($siteurl == NULL) $siteurl = 'http://';
	if ($sitedesc == NULL) $sitedesc = 'Site Description';
	echo "<h3>"._SITELIST_ADMIN_AU_HEAD."</h3>\n";
	$sitelist->showAddForm(50,htmlentities($siteurl),$sitedesc,$showlist);
	} //end addurl unchecked

/**************************************
 *     search results                 *
 **************************************/
	if ($showlist == 'search') {

        $slquery = preg_replace('|[^a-z0-9]|i', ' ', $slquery);
		$sites = $sitelist->getSearchResults($slquery);
		$rows = mysql_num_rows($sites);
		if ($rows > $nshow) {
			if ($rows % $nshow == 0) $npages = intval($rows / $nshow);
			else $npages = intval(($rows / $nshow) + 1);
			echo "<h3>"._SITELIST_ADMIN_SR_HEAD." [$rows] \n";
			echo '<a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.($pg > 1 ? $pg-1:1).'">&lt;&lt;</a> ';
			echo "<small>$pagetext $pg $oftext $npages</small>\n";
			echo ' <a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.($pg < $npages ? $pg+1:$npages).'">&gt;&gt;</a> ';
			echo "</h3>\n";
			slpagebar($thispage,$showlist,$nshow,$npages,$pg,$slquery);
			$offset = (($pg * $nshow) - $nshow) + 1;
			$sites = $sitelist->getSearchResults($slquery,$offset,$nshow);
			echo "<div>\n";
			if ($verify) echo ' <a style="'.$linkstyle.'" href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=verifychecked&amp;nshow='.$nshow.'&amp;offset='.$offset.'">'._SITELIST_ADMIN_VERIFY_PAGE.'</a> . '."\n";
		}
		else echo '<h3>Search Results ['.$rows.']</h3><div>'."\n";
		if ($rows != 0) {
			//if ($verify) echo ' <a style="'.$linkstyle.'" href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=verifychecked">Verify All Checked Sites</a>'."\n";
		}
		else echo _SITELIST_ADMIN_SR_NOSITES.' "'.$slquery.'"'."\n";
		echo "</div>\n";


		echo "<ul>\n";
		while ($site = mysql_fetch_object($sites)) {
			$site->ourl = $site->url;
            $site->url = $sitelist->prepURL($site->url);
            $site->title = $sitelist->prepDesc($site->title,$site->url);
			sllisturl($site,$admin,$verify,$nshow,$showlist,$thispage,$action_url,$testerpage);
		}
		echo "</ul>\n";
		if ($rows > $nshow) {
			slpagebar($thispage,$showlist,$nshow,$npages,$pg,$slquery);
		}

	}

    $oPluginAdmin->end();

?>
