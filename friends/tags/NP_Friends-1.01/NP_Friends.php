<?php
/*
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

Acknowledgement:
The initial version of NP_Friends is a collaborative effort between Wesley
Luyten (wessite) and Frank Truscott (ftruscot).

Distribution:
This plugin is distributed in a zip file containing the following files:
NP_Friends.php (this file)
friends\allfriends.css (a css file to set style of all friends page, allfriends.php)
friends\allfriends.php (a page to display all friends of a member)
friends\english.php (constants used to display plugin in English)
friends\headerallfriends.jpg (header image used on allfriends page)
friends\login.php (a page to force login during friends activation process, if needed)
friends\online.gif (image used to display online status of the friend, if NP_Online is installed)
friends\reply-16x16.gif (icon used for return link from allfriends to the member page)

History:
  v1.0 - Initial release
  v1.01 - Bug fix. uri error when friend does not have avatar

*/

	var $friendlevels = array(); // store the verbage for the three friend levels
		global $manager;
		$warning = '';
		if ($manager->pluginInstalled('NP_Profile')) {
            $plugin =& $manager->getPlugin('NP_Profile');
			if (version_compare("2.1",$plugin->getVersion())) $warning = "*** Works best with NP_Profile 2.1+ ***";
		else $warning = "*** Works best with NP_Profile 2.1+ ***   ";
		return $warning.'Add Friends in your member page';
	}
    //function getPluginDep() { return array('NP_Profile'); }


	function getTableList() { return array(sql_table('plugin_friends')); }
	function getEventList() { return array('PostDeleteMember'); }
		global $manager;
		$this->createOption('CSS2URL',_FRIENDS_OPTIONS_CSS2URL,'text',$this->getAdminURL()."allfriends.css");
		$this->createOption('actmail_subject',_FRIENDS_OPTIONS_ACTMAIL_SUBJECT,'text','An invitation from <%fromname%> of <%sitename%>');
        $this->createOption('actmail_body',_FRIENDS_OPTIONS_ACTMAIL_BODY,'textarea','Hi <%toname%>, I am <%fromname%> from the <%sitename%> site. My real name is <%fromrealname%>. I would like to add you to my friends list. See <a href="<%fromurl%>">my profile here</a>. If you would like to add me to your friend list, click this link: <a href="<%activateurl%>"><%activateurl%></a>');
// I think we should look at making an option that acts like a template for formating the output of friend lists. This can be later on.
        if ($manager->pluginInstalled('NP_Profile')) {
            $plugin =& $manager->getPlugin('NP_Profile');
								'fname'=>strtolower('privacylevel'),
								'flabel'=>'Profile Privacy Level',
								'ftype'=>'radio',
								'required'=>1,
								'enabled'=>1,
								'flength'=>0,
								'fsize'=>0,
								'fformat'=>'',
								'fwidth'=>0,
								'fheight'=>0,
								'ffilesize'=>0,
								'ffiletype'=>'',
								'foptions'=>'All Users|0;Members Only|1;Friends Only|2',
								'fvalidate'=>'',
								);
			if ($plugin->fieldExists('privacylevel')) {
				// do nothing. don't want to recreate an existing field.
			}
			else {
				$plugin->addFieldDef('privacylevel', '', $valuearray);
			}
		// include language file for this plugin
        $language = ereg_replace( '[\\|/]', '', getLanguageName());
        if (file_exists($this->getDirectory().$language.'.php'))
            include_once($this->getDirectory().$language.'.php');
        else
            include_once($this->getDirectory().'english.php');

// this code is run every time the plugin is loaded.
        if ($this->getOption('option1') == 'no' ) $this->showRealName = 1;


		$this->friendlevels[0] = _FRIENDS_LEVEL_0;
		$this->friendlevels[1] = _FRIENDS_LEVEL_1;
		$this->friendlevels[2] = _FRIENDS_LEVEL_2;

	function event_PostDeleteMember(&$data) {
		$this->_deleteMemberData($data['member']->id);
	}
        $you = intRequestVar("mid");
		$bid = intRequestVar("bid");
		$friendorder = intRequestVar("forder");
		if ($friendorder > 2) $friendorder = 2;

		if (!$bid) $bid = $CONF['DefaultBlog'];
		$b =& $manager->getBlog($bid);
		$fblog = $b;	// references can't be placed in global variables?
		if (!$fblog->isValid) {
			$bid = $CONF['DefaultBlog'];
			$b =& $manager->getBlog($bid);
			$fblog = $b;	// references can't be placed in global variables?
		}

			if (!$member->isLoggedIn()) doError(_NOTLOGGEDIN);
            if ($member->isAdmin() || $member->getID() == $you) {
				if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);
				$key = $this->_generateKey();
                $this->addFriend($you,$friendid,$friendorder,$key);
				if ($this->isFriend($you,$friendid) > 0) break;
                if ($member->getID() == $you) {
                    $frommail = $member->getEmail();
                    $toname = $tomem->getDisplayName();
                    $youurl = $siteurl."?memberid=$you";
                    $tagarray = array('<%fromname%>','<%fromrealname%>','<%fromurl%>','<%sitename%>','<%siteurl%>','<%activateurl%>','<%toname%>');
                    $valuearray = array($youname,$yourealname,$youurl,$sitename,$siteurl,$addurl,$toname);
                    $title = str_replace($tagarray,$valuearray,$this->getOption('actmail_subject'));
                    $message = str_replace($tagarray,$valuearray,$this->getOption('actmail_body'));
					@mail($tomail, $title, $message, 'From: '. $frommail);
			if (!$member->isLoggedIn()) doError('_NOTLOGGEDIN');
            if ($member->isAdmin() || $member->getID() == $you) {
				if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);
		case 'activate':
			$key = requestVar('key');
			if (!$member->isLoggedIn()) {
				$this->_loginAndPassThrough('activate');
				exit;
			}
			if ($member->getID() == $friendid) {
				$query = "SELECT memberid FROM ".sql_table('plugin_friends')." WHERE memberid=$you AND friendid=$friendid AND invitekey='".addslashes($key)."'";
				if (mysql_num_rows(sql_query($query))) {
					$this->activateFriend($you,$friendid,$key);
				}
				else doError(_FRIENDS_ERROR_INVALID_KEY);
			}
			else doError(_FRIENDS_ERROR_WRONG_USER);
        break;
		case 'updatefriend':
			if (!$member->isLoggedIn()) doError(_NOTLOGGEDIN);
            if ($member->isAdmin() || $member->getID() == $you) {
				if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);
				$this->updateFriend($you,$friendid,$friendorder);
			}
		break;
// send user back where he came from, If a direct request send to member's own page
	$desturl = str_replace(array('&confirm=1','&confirm=0'),'',$desturl);
        global $member, $memberinfo, $CONF, $manager, $blog;
			$privlevel = $this->getPrivacyLevel($currentid);

			$blogid = $blog->getID();
            switch($arg) {
            case 'count':
                if ($currentlevel >= $privlevel) {
					$query .= "FROM ".sql_table('member')." as m, ".sql_table('plugin_friends')." as f ";
					$query .= "WHERE f.memberid = '$currentid' AND m.mnumber = f.friendid AND f.invitekey='active' ";
					$query .= "ORDER BY f.friendorder DESC, f.invitetime ASC LIMIT 0,$numberOfMembers";

					$newmembers = sql_query($query);
					$totalfriends = intval(mysql_num_rows(sql_query("SELECT memberid FROM ".sql_table('plugin_friends')." WHERE memberid='$currentid' AND invitekey='active'")));
					if ($this->showAvatar) {
						echo "<table class=\"friendtable\">\n";
						$j = 0;
					}
					else echo "<ul class=\"friendlist\">\n";
                    while($row = mysql_fetch_object($newmembers)) {
                        $friendid  = $row->friendid;
                        $tomema = MEMBER::createFromId($friendid);
                        if ($this->showRealName) {
						$link = createMemberLink($friendid);
                        if ($this->showAvatar) {
                            $variable = $this->getAvatar($friendid);
							if ($j == 0) echo "<tr>\n";
							echo "<td class=\"friendcell\">";
                            if ($this->isOnline($friendid)) {echo "<img src=\"".$CONF['PluginURL']."friends/online.gif\" alt=\"online\" class=\"onlineimg\"><div class=\"avataronline\">\n";}
                            if (substr($variable,0,7) == 'http://') {
                                echo "<a href=\"".$link."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\"><img src=\"$variable\" alt=\"$name2show\" class=\"avatarimg\"></a><br />";
								echo "<a href=\"".$link."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\">$name2show</a>\n";
                            }
								echo "<a href=\"".$link."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\"><img src=\"".$CONF['MediaURL']."$variable\" alt=\"$name2show\" class=\"avatarimg\"></a><br />";
								echo "<a href=\"".$link."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\">$name2show</a>\n";
							}
							echo "</div></td>\n";
							if ($j == 2) {
								echo "</tr>\n";
								$j = 0;
							}
							else $j = $j + 1;
                        }
					if ($this->showAvatar) {
						if ($j == 1) echo "<td class=\"friendcell\"></td>\n<td class=\"friendcell\"></td>\n</tr>\n</table>\n";
						elseif ($j == 2) echo "<td class=\"friendcell\"></td>\n</tr>\n</table>\n";
						else echo "</table>\n";
					}
					else echo "</ul>\n";
					echo "<br /><a href=\"".$this->getAdminURL()."allfriends.php?memberid=$currentid&blogid=$blogid\" title=\""._FRIENDS_VIEW_ALL."\">"._FRIENDS_VIEW_ALL." ($totalfriends)</a>\n";
                } // end if currentlevel >= privlevel
                if ($this->isFriend($you, $currentid) == 1) $friend = 1;
				elseif ($this->isFriend($you, $currentid) == -1) $friend = -1;
// only display something to logged in members, who aren't viewing their own page
// if viewer is not a friend, show text and button to invite
					if ($this->isFriend($currentid,$you) == -1) {
						$ikey = mysql_result(sql_query("SELECT invitekey FROM ".sql_table('plugin_friends')." WHERE memberid=$currentid AND friendid=$you"),0,'invitekey');
						echo _FRIENDS_ACTIVATE_PRE_NAME." ".$memberinfo->getDisplayName()." "._FRIENDS_ACTIVATE_POST_NAME."\n";
						echo "<input type=\"hidden\" name=\"key\" value=\"$ikey\" />\n";
						echo "<input type=\"hidden\" name=\"bid\" value=\"$blogid\" />\n";
						$manager->addTicketHidden();
					}
						echo "<input type=\"hidden\" name=\"bid\" value=\"$blogid\" />\n";
						$manager->addTicketHidden();
// else if the viewer has already invited the viewed member to be a friend, show text indicating that and a button to revoke the invitation
					elseif ($friend == -1) {
						echo "<input type=\"hidden\" name=\"bid\" value=\"$blogid\" />\n";
						$manager->addTicketHidden();
// else if viewer is a friend, then show text indicating that and a button to end the friendship
						echo "<input type=\"hidden\" name=\"bid\" value=\"$blogid\" />\n";
						$manager->addTicketHidden();
// if no valid arg parameter is used, just echo nothing
	function _generateKey() {
		// generates a random key
		return $key;
	}

	function _cleanUpExpiredInvites()
	{
		// remove invites older than 30 days
		$oldTime = time() - (30 * 24 * 60 * 60);
		$query = 'DELETE FROM ' . sql_table('plugin_friends'). ' WHERE invitetime < \'' . date('Y-m-d H:i:s',$oldTime) .'\'';
		sql_query($query);
	}

				if ($this->isFriend($fid,$mid) == -1){
					sql_query("INSERT INTO " . sql_table('plugin_friends') . " (memberid, friendid, invitekey, friendorder, invitetime) VALUES ('$mid','$fid','active','0','".date('Y-m-d H:i:s',time())."')");
					sql_query("UPDATE " . sql_table('plugin_friends') . " SET invitekey='active' WHERE memberid='$fid' AND friendid='$mid' AND invitekey!='active'");
				}
				elseif ($this->isFriend($mid,$fid) == 0) {
			}

	function updateFriend($mid = 0, $fid = 0, $forder = 0) {
		if ($forder > 2) $forder = 2;
				if ($this->isFriend($mid,$fid) != 0) {
			}
            if ($mid > 0 && $fid > 0) {
                sql_query("DELETE FROM " . sql_table('plugin_friends') . " WHERE memberid='$fid' AND friendid='$mid'");

	function activateFriend($mid = 0, $fid = 0, $key = '') {
			// clean out expired invites first
			$this->_cleanUpExpiredInvites();

				$res = sql_query("SELECT invitekey, friendorder FROM ".sql_table('plugin_friends')." WHERE memberid='$mid' AND friendid='$fid'");
				if (mysql_num_rows($res) > 0) {
					$actarray = mysql_fetch_assoc($res);
					$tkey = $actarray['invitekey'];
					$forder = $actarray['friendorder'];
					if ($tkey == $key) {
						if ($mid > 0 && $fid > 0) {
							sql_query("INSERT INTO " . sql_table('plugin_friends') . " (memberid, friendid, invitekey, friendorder, invitetime) VALUES ('$fid','$mid','active','0','".date('Y-m-d H:i:s',time())."')");
							sql_query("UPDATE " . sql_table('plugin_friends') . " SET invitekey='active' WHERE memberid='$mid' AND friendid='$fid' AND invitekey='".addslashes($key)."'");
					}
				}
		}
// deternine if fid is a friend of mid.
// 1 means is a friend
// 0 means is not a friend
// -1 means a friend awaiting activation
		if ($mid == 0) return 0; // non-loggedin users are noone's friend
		else return -1;

	function _deleteMemberData($mid = 0){
		global $member;
		$mid = intval($mid);
		if ($member->isAdmin() || $member->getID() == $mid) {
			$pquery = "DELETE FROM ".sql_table('plugin_friends')." WHERE memberid='$mid' OR friendid='$mid'";
			sql_query($pquery);
		}
	}

	function _loginAndPassThrough($nextaction = '') {
// this is modified from nucleus/bookmarklet.php to force a non-loggedin user to login before processing the activation
// hopefully it works.
		global $CONF;
		$you = intRequestVar("mid");
		$key = requestVar('key');
		$action_url = $CONF['PluginURL'].'friends/login.php';

	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Nucleus</title>
		<?php $this->lf_style(); ?>
	</head>
	<body>
	<h1><?php echo _LOGIN_PLEASE?></h1>

	<form method="post" action="<?php echo $action_url ?>">
	<p>
		<input name="action" value="login" type="hidden" />
		<input name="nextaction" value="<?php echo  htmlspecialchars($nextaction) ?>" type="hidden" />
		<input name="mid" value="<?php echo  htmlspecialchars($you) ?>" type="hidden" />
		<input name="fid" value="<?php echo  htmlspecialchars($friendid) ?>" type="hidden" />
		<input name="key" value="<?php echo  htmlspecialchars($key) ?>" type="hidden" />
		<?php echo _LOGINFORM_NAME?>:
		<br /><input name="login" />
		<br /><?php echo _LOGINFORM_PWD?>:
		<br /><input name="password" type="password" />
		<br /><br />
		<br /><input type="submit" value="<?php echo _LOGIN?>" />
	</p>
	</form>
	<p><a href="/" onclick="window.close();"><?php echo _POPUP_CLOSE?></a></p>
	</body>
	</html>
	<?php
	}
// to steal styles from the bookmarklet css page for the login form
	function lf_style() {
		echo '<link rel="stylesheet" type="text/css" href="nucleus/styles/bookmarklet.css" />';
		echo '<link rel="stylesheet" type="text/css" href="nucleus/styles/addedit.css" />';
	}

	function getAvatar($fid) {
		global $manager, $CONF;
		$fid = intval($fid);
		if ($manager->pluginInstalled('NP_Profile')) {
			$plugin =& $manager->getPlugin('NP_Profile');
		if (isset($plugin)) {
			if (version_compare("2.11",$plugin->getVersion())) {
				$variable = $plugin->getValue($fid,'avatar');
                if ($variable == '') {
                    $variable = $plugin->default['file']['default'];
                }
                else {
                    $variable = $CONF['MediaURL'].$variable;
                }
				return $variable;
			}
			else {
				return $plugin->getAvatar($fid);
			}
		}
		else return '';
	}

	function getPrivacyLevel($cid) {
		global $manager;
		if ($manager->pluginInstalled('NP_Profile')) {
			$plugin =& $manager->getPlugin('NP_Profile');
		if (isset($plugin)) {
		else $privlevel = 0;
		return $privlevel;
	}

	function isOnline($fid) {
		global $manager;
		if ($manager->pluginInstalled('NP_Online')) {
			$plugin =& $manager->getPlugin('NP_Online');
		if (isset($plugin)) {
			$fid = intval($fid);
			$to = intval($plugin->getOption('timeout'));
			if ($to == 0) $to = 360;
			$timestamp = time();
			$timeout = $timestamp - $to;
			$today = mktime(0, 0, 0, date("n", $timestamp), date("j", $timestamp),  date("Y", $timestamp));
			$query = "SELECT DISTINCT member FROM ".sql_table('plug_online')." WHERE member=$fid AND timestamp>$timeout";
			return intval(mysql_num_rows(sql_query($query)));
		}
		else return 0;
	}
