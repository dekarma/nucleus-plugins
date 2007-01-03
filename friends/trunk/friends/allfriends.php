<?php
// all friends page

include('../../../config.php');

$memberid = intRequestVar('memberid');
if (!MEMBER::existsID($memberid)) doError(_ERROR_NOSUCHMEMBER);
$memberinfo = MEMBER::createFromID($memberid);
$displayname = $memberinfo->getDisplayName();

if ($manager->pluginInstalled('NP_Friends')) {
    $plugin =& $manager->getPlugin('NP_Friends');
	$cssURL = $plugin->getOption('CSS2URL');
}
else $cssURL = '';

$blogid = intRequestVar('blogid');
if (!$blogid) $blogid = $CONF['DefaultBlog'];

$b =& $manager->getBlog($blogid);
$blog = $b;	// references can't be placed in global variables?
if (!$blog->isValid) {
	$blogid = $CONF['DefaultBlog'];
	$b =& $manager->getBlog($blogid);
	$blog = $b;	// references can't be placed in global variables?
}

$returnURL = $blog->getURL()."?memberid=$memberid";
$friendURL = $blog->getURL()."?memberid=";

$currentlevel = 0;
// now load the NP_Profile plugin object if installedif ($manager->pluginInstalled('NP_Profile')) {
    $profplug =& $manager->getPlugin('NP_Profile');}if ($member->isLoggedIn()) {
	$mid = $member->getID();	$currentlevel = 1;}else $mid = 0;

if ($mid == $memberid) $edit = 1;
else $edit = 0;if (isset($profplug)) {    $privlevel = intval($profplug->getValue($memberid,'privacylevel'));}else $privlevel = 0;if ($plugin->isFriend($memberid, $mid) == 1) $currentlevel = 2;

if ($currentlevel < $privlevel) {
	echo _FRIENDS_ALL_PRIVATE;
	exit;
}

if ($plugin->showRealName) {    $displayname = $memberinfo->getRealName();}else {    $displayname = $memberinfo->getDisplayName();}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<!-- page stylesheet (site colors and layout definitions) -->
<link rel="stylesheet" type="text/css" href="<?php echo $cssURL ?>" />
</head>
<body>
<div id="wrapper">
<div id="header"><a name="top"></a>
<h1><a class="headertitle" href="<?php echo $blog->getURL()?>" accesskey="1"><?php echo $blog->getName()?></a></h1>
</div>
<div id="content">
<?php
if (isset($plugin)) {

    $thispage = $plugin->getAdminURL()."allfriends.php?memberid=$memberid&blogid=$blogid";

	if (intRequestVar('confirm') > 0 ) {
		echo "<div class=\"deleteform\">\n";
		echo _FRIENDS_CONFIRM_PRE_NAME.htmlentities(requestVar('fname'))._FRIENDS_CONFIRM_POST_NAME."<br /><br />\n";
		echo "<form method=\"post\" action=\"".$CONF['ActionURL']."\" >\n";		echo "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n";		echo "<input type=\"hidden\" name=\"name\" value=\"Friends\" />\n";		echo "<input type=\"hidden\" name=\"type\" value=\"deletefriend\" />\n";		echo "<input type=\"hidden\" name=\"fid\" value=\"".intRequestVar('fid')."\" />\n";		echo "<input type=\"hidden\" name=\"mid\" value=\"".intRequestVar('mid')."\" />\n";
		$manager->addTicketHidden();		echo "<input class=\"formbutton\" type=\"submit\" value=\"".ucfirst(_FRIENDS_DELETE)."\" />\n";
		echo "<br /><br /><a class=\"cancelbutton\" href=\"$thispage\" title=\""._FRIENDS_CANCEL."\">"._FRIENDS_CANCEL."</a>\n";		echo "</form>\n";
		echo "</div>\n";
		echo "</div></div></body></html>\n";
		exit;
	}

	if (intRequestVar('activate') > 0 ) {
		$aquery = "SELECT m.mname as fname, m.mrealname as frealname, f.friendid as fid, f.friendorder as forder, f.invitekey as invitekey, f.memberid as mid ";
		$aquery .= "FROM ".sql_table('member')." as m, ".sql_table('plugin_friends')." as f ";
		$aquery .= "WHERE f.friendid = '$memberid' AND m.mnumber = f.memberid AND f.invitekey<>'active' ";
		$aquery .= "ORDER BY f.invitetime ASC";
		$ares = sql_query($aquery);

		echo "<div class=\"friends\">\n";
		echo _FRIENDS_ALL_ACTIVATE_INTRO."<br />\n";
		echo "<table>\n";
		$j = 1;
		while ($row = mysql_fetch_assoc($ares)) {
            $tomema = MEMBER::createFromId($row['fid']);
			if ($plugin->showRealName) {               $name2show = $tomema->getRealName();            }            else {                $name2show = $tomema->getDisplayName();            }
			if ($j == 1) echo "<tr>\n";
			echo "<td class=\"friendcell\">\n";
			if ($plugin->showAvatar) {                if (isset($profplug)) {
                    $variable = $profplug->getValue($row['fid'],'avatar');
                    if ($variable == '') $variable = $profplug->default['file']['default'];
                }                else $variable = '';
				echo "<div class=\"avatar\">\n";
                if (substr($variable,0,7) == 'http://') {
                    echo "<a href=\"$friendURL".$row['fid']."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\"><img src=\"$variable\" height=\"80px\" width=\"80px\" alt=\"$name2show\"></a>";
                }                else {
					echo "<a href=\"$friendURL".$row['fid']."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\"><img src=\"".$CONF['MediaURL']."$variable\" height=\"80px\" width=\"80px\" alt=\"$name2show\"></a>";
				}
				echo "</div>\n";
			}
			echo "<div class=\"friendname\"><a href=\"$friendURL".$row['fid']."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\">$name2show</a></div>\n";

			if (1) {
				echo "<div class=\"updateform\">\n";
				echo "<form method=\"post\" action=\"".$CONF['ActionURL']."\" >\n";                echo "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n";                echo "<input type=\"hidden\" name=\"name\" value=\"Friends\" />\n";                echo "<input type=\"hidden\" name=\"type\" value=\"activate\" />\n";                echo "<input type=\"hidden\" name=\"fid\" value=\"$mid\" />\n";                echo "<input type=\"hidden\" name=\"mid\" value=\"".$row['mid']."\" />\n";
				echo "<input type=\"hidden\" name=\"key\" value=\"".$row['invitekey']."\" />\n";
				echo "<br /><input class=\"formbutton\" type=\"submit\" value=\"".ucfirst(_FRIENDS_ACTIVATE)."\" />\n";                echo "</form>\n";
				echo "</div>\n";
			}

			echo "</td>\n";
			if ($j == 5) {
				echo "</tr>\n";
				$j = 1;
			}
			else $j++;
		}
		if ($j == 1) echo "</table>\n";
		else {
			while ($j <= 5) {
				echo "<td class=\"friendcell\">\n</td>\n";
				$j++;
			}
			echo "</tr>\n";
			echo "</table>\n";
		}
		echo "<div class=\"returnlink\">\n";
		echo "<br /><br /><a href=\"$returnURL\" title=\""._FRIENDS_ALL_RETURN." $displayname\">"._FRIENDS_ALL_RETURN." $displayname</a>\n";
		echo "</div>\n";
		echo "</div></div></body></html>\n";
		exit;
	}

	if ($edit) {
		$aquery = "SELECT f.friendid as fid ";
		$aquery .= "FROM ".sql_table('plugin_friends')." as f ";
		$aquery .= "WHERE f.friendid = '$memberid' AND f.invitekey<>'active' ";
		$aquery .= "ORDER BY f.invitetime ASC";
		$ares = sql_query($aquery);
		if (mysql_num_rows($ares) > 0) {
			echo "<div class=\"showactivate\">\n";
			echo "<br /><a href=\"$thispage&activate=1\" title=\""._FRIENDS_ACTIVATE."\">"._FRIENDS_ALL_PENDING_ACTIVATIONS."</a><br /><br />\n";
			echo "</div>\n";
		}
	}

	$ftotal = mysql_num_rows(sql_query("SELECT friendid FROM ".sql_table('plugin_friends')." WHERE memberid='$memberid'"));
	if ($ftotal > 0 ) {
		$pgsize = 25;
		$pg = intRequestVar('pg');
		if ($pg < 1) $pg = 1;
		$start = ($pg - 1) * 25;
		if ($ftotal % $pgsize == 0) $npages = intval($ftotal / $pgsize);
		else $npages = intval(($ftotal / $pgsize) + 1);

		echo "<div class=\"returnlink\">\n";
		echo "<a href=\"$returnURL\" title=\""._FRIENDS_ALL_RETURN." $displayname\">"._FRIENDS_ALL_RETURN.": $displayname</a>\n";
		echo "</div>\n";

		echo "<div class=\"friendtitle\">\n";
		echo "<h2>"._FRIENDS_ALL_FRIENDS_OF." $displayname ($ftotal)</h2>\n";
		echo "</div>\n";

		echo "<div class=\"pageof\">\n";
		echo _FRIENDS_ALL_VIEWING." $pg "._FRIENDS_OF." $npages \n";
		echo "</div>\n";

		if ($npages > 1) {
			echo "<div class=\"pagelinkbar\">\n";
			echo "<table>\n";
			echo "<tr>\n";
			echo "<td class=\"prevnext\">";
			if ($pg > 1) echo "<a href=\"$thispage&pg=".($pg - 1)."\" title=\""._FRIENDS_PREVIOUS."\">&lt; "._FRIENDS_PREVIOUS."</a>";
			echo "</td>\n";
			echo "<td class=\"pagelinks\">";
			$i = 1;
			while ($i <= $npages) {
				echo "<a href=\"$thispage&pg=$i\" title=\""._FRIENDS_PAGE." $i\" class=\"".($i == $pg ? 'currentpage' : 'notcurrentpage')."\">".($i > 1 ? '-' : '')." $i </a>";
				$i++;
			}
			echo "</td>\n";
			echo "<td class=\"prevnext\">";
			if ($pg < $npages) echo "<a href=\"$thispage&pg=".($pg + 1)."\" title=\""._FRIENDS_NEXT."\">".ucfirst(_FRIENDS_NEXT)." &gt;</a></td>\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "</div>\n";
		}

		//table of friends goes here.
		$pgquery = "SELECT m.mname as fname, m.mrealname as frealname, f.friendid as fid, f.friendorder as forder ";
		$pgquery .= "FROM ".sql_table('member')." as m, ".sql_table('plugin_friends')." as f ";
		$pgquery .= "WHERE f.memberid = '$memberid' AND m.mnumber = f.friendid ";
		$pgquery .= "ORDER BY f.friendorder DESC, f.invitetime ASC LIMIT $start,$pgsize";
		$pgres = sql_query($pgquery);

		echo "<div class=\"friends\">\n";
		echo "<table>\n";
		$j = 1;
		while ($row = mysql_fetch_assoc($pgres)) {
            $tomema = MEMBER::createFromId($row['fid']);
			if ($plugin->showRealName) {               $name2show = $tomema->getRealName();            }            else {                $name2show = $tomema->getDisplayName();            }
			if ($j == 1) echo "<tr>\n";
			echo "<td class=\"friendcell\">\n";
			if ($plugin->showAvatar) {                if (isset($profplug)) {
                    $variable = $profplug->getValue($row['fid'],'avatar');
                    if ($variable == '') $variable = $profplug->default['file']['default'];
                }                else $variable = '';
				echo "<div class=\"avatar\">\n";
                if (substr($variable,0,7) == 'http://') {
                    echo "<a href=\"$friendURL".$row['fid']."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\"><img src=\"$variable\" height=\"80px\" width=\"80px\" alt=\"$name2show\"></a>";
                }                else {
					echo "<a href=\"$friendURL".$row['fid']."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\"><img src=\"".$CONF['MediaURL']."$variable\" height=\"80px\" width=\"80px\" alt=\"$name2show\"></a>";
				}
				echo "</div>\n";
			}
			echo "<div class=\"friendname\"><a href=\"$friendURL".$row['fid']."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\">$name2show</a></div>\n";

			if ($edit) {
				echo "<div class=\"updateform\">\n";
				echo "<form method=\"post\" action=\"".$CONF['ActionURL']."\" >\n";                echo "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n";                echo "<input type=\"hidden\" name=\"name\" value=\"Friends\" />\n";                echo "<input type=\"hidden\" name=\"type\" value=\"updatefriend\" />\n";                echo "<input type=\"hidden\" name=\"fid\" value=\"".$row['fid']."\" />\n";                echo "<input type=\"hidden\" name=\"mid\" value=\"$mid\" />\n";
				$manager->addTicketHidden();
				echo '<select name="forder" size="1">' . "\n";
				foreach ($plugin->friendlevels as $key=>$value) {
					if ($row['forder'] == $key) {
						echo '<option value="' . $key . '" selected="selected">' . $value . '</option>' . "\n";
					}
					else {
						echo '<option value="' . $key . '">' . $value . '</option>' . "\n";
					}
				}
				echo '</select>' . "\n";
				echo "<br /><input class=\"formbutton\" type=\"submit\" value=\"".ucfirst(_FRIENDS_UPDATE)."\" />\n";                echo "</form>\n";
				echo "</div>\n";
// then a br and display update or delete links (if member viewing own). Update is the submit button of rank from. Delete does action.
				echo "<div class=\"deleteform\">\n";
				echo "<form method=\"post\" action=\"$thispage&confirm=1\" >\n";				echo "<input type=\"hidden\" name=\"fid\" value=\"".$row['fid']."\" />\n";
				echo "<input type=\"hidden\" name=\"fname\" value=\"$name2show\" />\n";				echo "<input type=\"hidden\" name=\"mid\" value=\"$mid\" />\n";				echo "<input class=\"formbutton\" type=\"submit\" value=\"".ucfirst(_FRIENDS_DELETE)."\" />\n";				echo "</form>\n";
				echo "</div>\n";
				}
			else {
				echo "<div class=\"friendlevel\">\n";
				echo $plugin->friendlevels[$row['forder']]."\n";
				echo "</div>\n";
			}

			echo "</td>\n";
			if ($j == 5) {
				echo "</tr>\n";
				$j = 1;
			}
			else $j++;
		}
		if ($j == 1) echo "</table>\n";
		else {
			while ($j <= 5) {
				echo "<td class=\"friendcell\">\n</td>\n";
				$j++;
			}
			echo "</tr>\n";
			echo "</table>\n";
		}


		if ($npages > 1) {
			echo "<div class=\"pagelinkbar\">\n";
			echo "<table>\n";
			echo "<tr>\n";
			echo "<td class=\"prevnext\">";
			if ($pg > 1) echo "<a href=\"$thispage&pg=".($pg - 1)."\" title=\""._FRIENDS_PREVIOUS."\">&lt; ".ucfirst(_FRIENDS_PREVIOUS)."</a>";
			echo "</td>\n";
			echo "<td class=\"pagelinks\">";
			$i = 1;
			while ($i <= $npages) {
				echo "<a href=\"$thispage&pg=$i\" title=\""._FRIENDS_PAGE." $i\" class=\"".($i == $pg ? 'currentpage' : 'notcurrentpage')."\">".($i > 1 ? '-' : '')." $i </a>";
				$i++;
			}
			echo "</td>\n";
			echo "<td class=\"prevnext\">";
			if ($pg < $npages) echo "<a href=\"$thispage&pg=".($pg + 1)."\" title=\""._FRIENDS_NEXT."\">".ucfirst(_FRIENDS_NEXT)." &gt;</a></td>\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "</div>\n";
		}

		echo "<div class=\"returnlink\">\n";
		echo "<br /><br /><a href=\"$returnURL\" title=\""._FRIENDS_ALL_RETURN." $displayname\">"._FRIENDS_ALL_RETURN." $displayname</a>\n";
		echo "</div>\n";
	}
	else {
		echo $memberinfo->getDisplayName()." "._FRIENDS_ALL_NO_FRIENDS;
		echo "<br /><br /><div class=\"returnlink\">\n";
		echo "<a href=\"$returnURL\" title=\""._FRIENDS_ALL_RETURN." $displayname\">"._FRIENDS_ALL_RETURN." $displayname</a>\n";
		echo "</div>\n";
	}
}
echo "</div></div></body></html>\n";
?>