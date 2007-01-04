<?phpclass NP_Friends extends NucleusPlugin {// classwide variables or properties (set in init() method)    var $showRealName = 0; // whether to show real name of member    var $showAvatar = 1; // whether to show avatar if NP_Profile is installed
	var $friendlevels = array();	function getName() { return 'Friends'; }	function getAuthor()  { return 'Wesley Luyten and Frank Truscott'; }	function getURL() {   return 'http://wessite.sin.khk.be/'; }	function getVersion() {   return '1.0.b2.02'; }	function getDescription() {
		global $manager;
		$warning = '';
		if ($manager->pluginInstalled('NP_Profile')) {
            $plugin =& $manager->getPlugin('NP_Profile');
			if (version_compare("2.1",$plugin->getVersion())) $warning = "*** Works best with NP_Profile 2.1+ ***";        }
		else $warning = "*** Works best with NP_Profile 2.1+ ***   ";
		return $warning.'Add Friends in your member page';
	}
    //function getPluginDep() { return array('NP_Profile'); }
	function supportsFeature($feature) {		switch($feature) {			case 'SqlTablePrefix': return 1;			default: return 0;		}	}

	function getTableList() { return array(sql_table('plugin_friends')); }
	function getEventList() { return array('PostDeleteMember'); }	function install() {
		global $manager;		$this->createOption('nrfriends',_FRIENDS_OPTIONS_NRFRIENDS,'text','6');		$this->createOption('showavatar',_FRIENDS_OPTIONS_SHOWAVATAR,'yesno','yes');		$this->createOption('del_uninstall_data', _FRIENDS_OPTIONS_UNINSTALL, 'yesno','no');        $this->createOption('option1',_FRIENDS_OPTIONS_OPTION1,'yesno','yes');
		$this->createOption('CSS2URL',_FRIENDS_OPTIONS_CSS2URL,'text',$this->getAdminURL()."allfriends.css");
// I think we should look at making an option that acts like a template for formating the output of friend lists. This can be later on.		// let's create some mysql tables
// Note that I haven't made use of the invitetime column anywhere, but it would be useful later to expire invitations
// also I do nothing to allow the setting of the orders using the friendorder column. Though the quesries are written to
// sort by it. I think it may be better to be a rating like value, set between 1 and 10 with maybe 10 being the highest
// (or best) friend. This will affect a quesry or two once we decide, but no big deal		sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plugin_friends').					" ( `memberid` int(11),					  `friendid` int(11),					  `invitekey` varchar(40),					  `friendorder` int(11),					  `invitetime` datetime NOT NULL default '0000-00-00 00:00:00',					  KEY `memberid` (`memberid`)) ENGINE=MyISAM");
        if ($manager->pluginInstalled('NP_Profile')) {
            $plugin =& $manager->getPlugin('NP_Profile');            $valuearray = array(
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
								'fvalidate'=>'',                                'forder'=>0
								);
			if ($plugin->fieldExists('privacylevel')) {
				// do nothing. don't want to recreate an existing field.
			}
			else {
				$plugin->addFieldDef('privacylevel', '', $valuearray);
			}        }	}	function unInstall() {		if ($this->getOption('del_uninstall_data') == 'yes')	{			sql_query('DROP TABLE '.sql_table('plugin_friends'));		}	}

// need to write a postmemberdelete event to get rid of data for members who are deleted
// see np_profile for example    function init() {
		// include language file for this plugin
        $language = ereg_replace( '[\\|/]', '', getLanguageName());
        if (file_exists($this->getDirectory().$language.'.php'))
            include_once($this->getDirectory().$language.'.php');
        else
            include_once($this->getDirectory().'english.php');

// this code is run every time the plugin is loaded.
        if ($this->getOption('option1') == 'no' ) $this->showRealName = 1;        else $this->showRealName = 0;
        if ($this->getOption('showavatar') == 'yes' ) $this->showAvatar = 1;        else $this->showAvatar = 0;

		$this->friendlevels[0] = _FRIENDS_LEVEL_0;
		$this->friendlevels[1] = _FRIENDS_LEVEL_1;
		$this->friendlevels[2] = _FRIENDS_LEVEL_2;    }

	function event_PostDeleteMember(&$data) {
		$this->_deleteMemberData($data['member']->id);
	}	function doAction($actionType) {        global $member, $CONF, $manager;
        $you = intRequestVar("mid");        $friendid = intRequestVar("fid");
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
        switch ($actionType) {        case 'addfriend':
			if (!$member->isLoggedIn()) doError(_NOTLOGGEDIN);
            if ($member->isAdmin() || $member->getID() == $you) {
				if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);
				$key = $this->_generateKey();
                $this->addFriend($you,$friendid,$friendorder,$key);
				if ($this->isFriend($you,$friendid) > 0) break;
                if ($member->getID() == $you) {                    $tomem = Member::createFromID($friendid);                    $toMail = $tomem->getEmail();
                    $fromMail = $member->getEmail();                    $youName = $member->getDisplayName();                    $yourealname = $member->getRealName();                    $title = 'A message from '.$youName;                    $sitename = $fblog->getName();                    $siteurl = $fblog->getURL();                    $addurl = $CONF['ActionURL']."?action=plugin&name=Friends&type=activate&mid=$you&fid=$friendid&key=$key";                    $message = "Hi, I am $youname from the $sitename site. My real name is $yourealname.";
                    $message .= " I would like to add you to my friends list. See <a href=\"$siteurl?memberid=$you\">my profile here</a>.";
                    $message .= " If you would like to add me to your friend list, click this link: <a href=\"$addurl\">$addurl</a>";
					@mail($toMail, $title, $message, 'From: '. $fromMail);                }            }        break;        case 'deletefriend':
			if (!$member->isLoggedIn()) doError('_NOTLOGGEDIN');
            if ($member->isAdmin() || $member->getID() == $you) {
				if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);                $this->deleteFriend($you,$friendid);            }        break;
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
		break;        default:            doError(_BADACTION);        break;        }
// send user back where he came from, If a direct request send to member's own page	$desturl = serverVar('HTTP_REFERER');
	$desturl = str_replace(array('&confirm=1','&confirm=0'),'',$desturl);    if ($desturl == '' || $desturl == '-' || $actionType == 'activate') $desturl = $fblog->getURL()."?memberid=$you";	redirect($desturl);	exit;	}	function doSkinVar($skinType,$arg) {
        global $member, $memberinfo, $CONF, $manager, $blog;        if ($skinType == 'member') {            $currentlevel = 0;
// now load the NP_Profile plugin object if installed            if ($manager->pluginInstalled('NP_Profile')) {
                $plugin =& $manager->getPlugin('NP_Profile');            }            if ($member->isLoggedIn()) {                $you = $member->getID();                $currentlevel = 1;            }            else $you = 0;            $currentid = $memberinfo->getID();            if (isset($plugin)) {                $privlevel = intval($plugin->getValue($currentid,'privacylevel'));            }            else $privlevel = 0;            if ($this->isFriend($currentid, $you) == 1) $currentlevel = 2;

			$blogid = $blog->getID();
            switch($arg) {
            case 'count':                echo intval(mysql_num_rows(sql_query("SELECT memberid FROM ".sql_table('plugin_friends')." WHERE memberid='$currentid' AND invitekey='active'")));            break;            case 'show':
                if ($currentlevel >= $privlevel) {
// get the number of friends to show option set in plugin options
// we will probably need to come up with a way to browse through all the friends                    $numberOfMembers = $this->getOption('nrfriends');
// now set the query to get the list. This one assumes an ordering or 1 being the best and going down from there.
// we might want to tweak this if the friendorder becomes a rating of 1-10.
// would need this to be set by some friend admin page the user could manage					$query = "SELECT m.mname as fname, m.mrealname as frealname, f.friendid as friendid, f.friendorder as friendorder, f.memberid ";
					$query .= "FROM ".sql_table('member')." as m, ".sql_table('plugin_friends')." as f ";
					$query .= "WHERE f.memberid = '$currentid' AND m.mnumber = f.friendid AND f.invitekey='active' ";
					$query .= "ORDER BY f.friendorder DESC, f.invitetime ASC LIMIT 0,$numberOfMembers";

					$newmembers = sql_query($query);
					$totalfriends = mysql_num_rows($newmembers);
					if ($this->showAvatar) {
						echo "<table>\n";
						$j = 0;
					}
					else echo "<ul>\n";
                    while($row = mysql_fetch_object($newmembers)) {
                        $friendid  = $row->friendid;
                        $tomema = MEMBER::createFromId($friendid);
                        if ($this->showRealName) {                            $name2show = $tomema->getRealName();                        }                        else {                            $name2show = $tomema->getDisplayName();                        }
						$link = createMemberLink($friendid);
                        if ($this->showAvatar) {                            if (isset($plugin)) {
								$variable = $plugin->getAvatar($friendid);
                            }                            else $variable = '';                        }                        else $variable = '';                        if ($variable == ''){                            echo "<li><a href=\"".$link."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\">$name2show</a></li>\n";                        }                        else {
							if ($j == 0) echo "<tr>\n";
							echo "<td>";
                            if (substr($variable,0,7) == 'http://') {
                                echo "<a href=\"".$link."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\"><img src=\"$variable\" height=\"80px\" width=\"80px\" alt=\"$name2show\"></a><br />";
								echo "<a href=\"".$link."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\">$name2show</a>\n";
                            }                            else {
								echo "<a href=\"".$link."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\"><img src=\"".$CONF['MediaURL']."$variable\" height=\"80px\" width=\"80px\" alt=\"$name2show\"></a><br />";
								echo "<a href=\"".$link."\" title=\""._FRIENDS_ALL_VIEW_PROFILE." $name2show\">$name2show</a>\n";
							}
							echo "</td>\n";
							if ($j == 2) {
								echo "</tr>\n";
								$j = 0;
							}
							else $j = $j + 1;
                        }                    }
					if ($this->showAvatar) {
						if ($j == 1) echo "<td></td>\n<td></td>\n</tr>\n</table>\n";
						elseif ($j == 2) echo "<td></td>\n</tr>\n</table>\n";
						else echo "</table>\n";
					}
					else echo "</ul>\n";
					echo "<br /><a href=\"".$this->getAdminURL()."allfriends.php?memberid=$currentid&blogid=$blogid\" title=\""._FRIENDS_VIEW_ALL."\">"._FRIENDS_VIEW_ALL." ($totalfriends)</a>\n";
                } // end if currentlevel >= privlevel            break;            case 'add':
                if ($this->isFriend($you, $currentid) == 1) $friend = 1;
				elseif ($this->isFriend($you, $currentid) == -1) $friend = -1;                else $friend = 0;
// only display something to logged in members, who aren't viewing their own page                if ($member->isLoggedIn() && $you != $currentid) {
// if viewer is not a friend, show text and button to to invite
					if ($this->isFriend($currentid,$you) == -1) {
						$ikey = mysql_result(sql_query("SELECT invitekey FROM ".sql_table('plugin_friends')." WHERE memberid=$currentid AND friendid=$you"),0,'invitekey');
						echo _FRIENDS_ACTIVATE_PRE_NAME." ".$memberinfo->getDisplayName()." "._FRIENDS_ACTIVATE_POST_NAME."\n";                        echo "<form method=\"post\" action=\"".$CONF['ActionURL']."\" >\n";                        echo "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n";                        echo "<input type=\"hidden\" name=\"name\" value=\"Friends\" />\n";                        echo "<input type=\"hidden\" name=\"type\" value=\"activate\" />\n";                        echo "<input type=\"hidden\" name=\"mid\" value=\"$currentid\" />\n";                        echo "<input type=\"hidden\" name=\"fid\" value=\"$you\" />\n";
						echo "<input type=\"hidden\" name=\"key\" value=\"$ikey\" />\n";
						echo "<input type=\"hidden\" name=\"bid\" value=\"$blogid\" />\n";
						$manager->addTicketHidden();                        echo "<input class=\"formbutton\" type=\"submit\" value=\"".ucfirst(_FRIENDS_ACCEPT)."\" />\n";                        echo "</form>\n";
					}                    elseif ($friend == 0) {                        echo _FRIENDS_INVITE_PRE_NAME." ".$memberinfo->getDisplayName()." "._FRIENDS_INVITE_POST_NAME."\n";                        echo "<form method=\"post\" action=\"".$CONF['ActionURL']."\" >\n";                        echo "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n";                        echo "<input type=\"hidden\" name=\"name\" value=\"Friends\" />\n";                        echo "<input type=\"hidden\" name=\"type\" value=\"addfriend\" />\n";                        echo "<input type=\"hidden\" name=\"fid\" value=\"$currentid\" />\n";                        echo "<input type=\"hidden\" name=\"mid\" value=\"$you\" />\n";
						echo "<input type=\"hidden\" name=\"bid\" value=\"$blogid\" />\n";
						$manager->addTicketHidden();                        echo "<input class=\"formbutton\" type=\"submit\" value=\"".ucfirst(_FRIENDS_INVITE)."\" />\n";                        echo "</form>\n";                    }
// else if the viewer has already invited the viewed member to be a friend, show text indicating that and a button to revoke the invitation
					elseif ($friend == -1) {                        echo _FRIENDS_INVITED_PRE_NAME." ".$memberinfo->getDisplayName()." "._FRIENDS_INVITED_POST_NAME."\n";                        echo "<form method=\"post\" action=\"".$CONF['ActionURL']."\" >\n";                        echo "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n";                        echo "<input type=\"hidden\" name=\"name\" value=\"Friends\" />\n";                        echo "<input type=\"hidden\" name=\"type\" value=\"deletefriend\" />\n";                        echo "<input type=\"hidden\" name=\"fid\" value=\"$currentid\" />\n";                        echo "<input type=\"hidden\" name=\"mid\" value=\"$you\" />\n";
						echo "<input type=\"hidden\" name=\"bid\" value=\"$blogid\" />\n";
						$manager->addTicketHidden();                        echo "<input class=\"formbutton\" type=\"submit\" value=\"".ucfirst(_FRIENDS_REVOKE)."\" />\n";                        echo "</form>\n";                    }                    else {
// else if viewer is a friend, then show text indicating that and a button to end the friendship                        echo _FRIENDS_ISFRIEND_PRE_NAME." ".$memberinfo->getDisplayName()." "._FRIENDS_ISFRIEND_POST_NAME."\n";                        echo "<form method=\"post\" action=\"".$CONF['ActionURL']."\" >\n";                        echo "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n";                        echo "<input type=\"hidden\" name=\"name\" value=\"Friends\" />\n";                        echo "<input type=\"hidden\" name=\"type\" value=\"deletefriend\" />\n";                        echo "<input type=\"hidden\" name=\"fid\" value=\"$currentid\" />\n";                        echo "<input type=\"hidden\" name=\"mid\" value=\"$you\" />\n";
						echo "<input type=\"hidden\" name=\"bid\" value=\"$blogid\" />\n";
						$manager->addTicketHidden();                        echo "<input class=\"formbutton\" type=\"submit\" value=\"".ucfirst(_FRIENDS_DELETE)."\" />\n";                        echo "</form>\n";                    }                }            break;            default:
// if no valid arg parameter is used, just echo nothing                echo '';            break;            }// switch($arg)        } // end skinType == 'member'	} // doSkinVar    /* helper functions */
	function _generateKey() {
		// generates a random key		srand((double)microtime()*1000000);		$key = md5(uniqid(rand(), true));
		return $key;
	}

	function _cleanUpExpiredInvites()
	{
		// remove invites older than 30 days
		$oldTime = time() - (30 * 24 * 60 * 60);
		$query = 'DELETE FROM ' . sql_table('plugin_friends'). ' WHERE invitetime < \'' . date('Y-m-d H:i:s',$oldTime) .'\'';
		sql_query($query);
	}
    function addFriend($mid = 0, $fid = 0, $forder = 0, $key = '') {        global $member;        $mid = intval($mid);        $fid = intval($fid);        $forder = intval($forder);        if ($member->isAdmin() || $member->getID() == $mid) {            if ($mid > 0 && $fid > 0) {
				if ($this->isFriend($fid,$mid) == -1){
					sql_query("INSERT INTO " . sql_table('plugin_friends') . " (memberid, friendid, invitekey, friendorder, invitetime) VALUES ('$mid','$fid','active','0','".date('Y-m-d H:i:s',time())."')");
					sql_query("UPDATE " . sql_table('plugin_friends') . " SET invitekey='active' WHERE memberid='$fid' AND friendid='$mid' AND invitekey!='active'");
				}
				elseif ($this->isFriend($mid,$fid) == 0) {					sql_query("INSERT INTO " . sql_table('plugin_friends') . " (memberid, friendid, invitekey, friendorder, invitetime) VALUES ('$mid','$fid','".addslashes($key)."','$forder','".date('Y-m-d H:i:s',time())."')");				}
			}        }    }

	function updateFriend($mid = 0, $fid = 0, $forder = 0) {        global $member;        $mid = intval($mid);        $fid = intval($fid);        $forder = intval($forder);
		if ($forder > 2) $forder = 2;        if ($member->isAdmin() || $member->getID() == $mid) {            if ($mid > 0 && $fid > 0) {
				if ($this->isFriend($mid,$fid) != 0) {					sql_query("UPDATE " . sql_table('plugin_friends') . " SET friendorder=$forder WHERE memberid='$mid' AND friendid='$fid'");				}
			}        }    }    function deleteFriend($mid = 0, $fid = 0) {        global $member;        $mid = intval($mid);        $fid = intval($fid);        if ($member->isAdmin() || $member->getID() == $mid) {
            if ($mid > 0 && $fid > 0) {                sql_query("DELETE FROM " . sql_table('plugin_friends') . " WHERE memberid='$mid' AND friendid='$fid'");
                sql_query("DELETE FROM " . sql_table('plugin_friends') . " WHERE memberid='$fid' AND friendid='$mid'");            }        }    }

	function activateFriend($mid = 0, $fid = 0, $key = '') {        global $member;		if (trim($key) != '') {
			// clean out expired invites first
			$this->_cleanUpExpiredInvites();
			$mid = intval($mid);			$fid = intval($fid);			if ($member->isAdmin() || ($member->getID() == $fid)) {
				$res = sql_query("SELECT invitekey, friendorder FROM ".sql_table('plugin_friends')." WHERE memberid='$mid' AND friendid='$fid'");
				if (mysql_num_rows($res) > 0) {
					$actarray = mysql_fetch_assoc($res);
					$tkey = $actarray['invitekey'];
					$forder = $actarray['friendorder'];
					if ($tkey == $key) {
						if ($mid > 0 && $fid > 0) {
							sql_query("INSERT INTO " . sql_table('plugin_friends') . " (memberid, friendid, invitekey, friendorder, invitetime) VALUES ('$fid','$mid','active','0','".date('Y-m-d H:i:s',time())."')");
							sql_query("UPDATE " . sql_table('plugin_friends') . " SET invitekey='active' WHERE memberid='$mid' AND friendid='$fid' AND invitekey='".addslashes($key)."'");						}
					}
				}			}
		}    }    function isFriend($mid = 0, $fid = 0) {
// deternine if fid is a friend of mid.
// 1 means is a friend
// 0 means is not a friend
// -1 means a friend awaiting activation        $mid = intval($mid);        $fid = intval($fid);
		if ($mid == 0) return 0; // non-loggedin users are noone's friend        if ($mid == $fid && $mid > 0) return 1; // you are always your own friend        $res = sql_query("SELECT invitekey FROM ".sql_table('plugin_friends')." WHERE memberid='$mid' AND friendid='$fid'" );        if (mysql_num_rows($res) == 0) return 0;        elseif (mysql_result($res,0,'invitekey') == 'active') return 1;
		else return -1;    }

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
		$you = intRequestVar("mid");        $friendid = intRequestVar("fid");
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
}// class NP_Friends?>