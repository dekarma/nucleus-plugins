<?php
/* NP_SkinChooser plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, http://www.iai.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 */

/* This plugin displays all kinds of information about your Nucleus CMS
 * installation, including data about PHP, MySQL, and Apache. This data
 * can be used in troubleshooting and determining your server capabilities.
 * It is intended to work on Nucleus CMS v3.2 or higher, but may work on
 * earlier versions. The latest Nucleus version is always recommended.
 * It requires PHP v 4.0.6 or higher. It has only been tested using
 * MySQL version 4.1.16 and higher, but should theoretically work on all
 * MySQL versions supported by Nucleus CMS 3.2+.
 */

/*
    Version history:
    * v 0.1b - initial beta release.
    * v 1.0 - 2nd release
      * set cookies per blog, for multi-blog sites that don't share skins
      * allow per blog skin lists with master list and options set for all blogs
      * option to choose random skin
    * v 1.01 - 3rd release
      * fix bug where feed skin overwritten by skin set in cookie
*/

class NP_SkinChooser extends NucleusPlugin {
	function getName() {	return 'SkinChooser'; 	}
	function getAuthor()  { return 'Frank Truscott'; 	}
	function getURL() { return 'http://www.iai.com/'; }
	function getVersion() {	return '1.01'; }
	function getDescription() {
		return 'Let guests choose skins.';
	}

	function supportsFeature($what)
	{
		switch($what)
		{
		case 'SqlTablePrefix':
			return 1;
		default:
			return 0;
		}
	}

	function getEventList() { return array('QuickMenu','InitSkinParse'); }

	function install() {
        $this->createOption("del_uninstall", "Delete tables on uninstall?", "yesno", "no");
		$this->createOption('quickmenu', 'Show Admin Area in quick menu?', 'yesno', 'yes');

        // Create tables needed
        $query = "CREATE TABLE IF NOT EXISTS `".sql_table('plug_skinchooser')."` (
                  `skinid` int(11) NOT NULL,
                  `skinname` varchar(250) default NULL,
                  `blogid` int(11) NOT NULL,
                  KEY `blogid` (`blogid`)
                  ) TYPE=MyISAM PACK_KEYS=0;";
        sql_query($query);

        $query = "CREATE TABLE IF NOT EXISTS `".sql_table('plug_skinchooser_config')."` (
                  `blogid` int(11) NOT NULL,
                  `configname` varchar(250) default NULL,
                  `configvalue` varchar(250) default NULL,
                  KEY `blogid` (`blogid`)
                  ) TYPE=MyISAM PACK_KEYS=0;";
        sql_query($query);

        // this is for upgrading from the beta release
        $hasblogid = mysql_num_rows(sql_query("SHOW COLUMNS FROM `".sql_table('plug_skinchooser')."` LIKE 'blogid'"));
        if (!$hasblogid) {
            sql_query("ALTER TABLE ".sql_table('plug_skinchooser')." ADD `blogid` int(11) NOT NULL default '0' AFTER `skinname`");
            sql_query("ALTER TABLE ".sql_table('plug_skinchooser')." DROP PRIMARY KEY");
            sql_query("ALTER TABLE ".sql_table('plug_skinchooser')." DROP KEY `skinname`");
            sql_query("ALTER TABLE ".sql_table('plug_skinchooser')." Add KEY `blogid` (`blogid`)");
	  		sql_query("UPDATE ".sql_table('plug_skinchooser')." SET `blogid` = '0'");
        }
        if ($this->getOption('accesslevel') != '') {
            $this->deleteOption('accesslevel');
        }
    }

	function unInstall() {
        if ($this->getOption('del_uninstall') == "yes") {
			sql_query('DROP TABLE ' .sql_table('plug_skinchooser'));
		}
	}

	function init() {
	}

	function hasAdminArea() { return 1; }

	function event_QuickMenu(&$data) {
    	// only show when option enabled
    	if ($this->getOption('quickmenu') != 'yes') return;
    	if (!$this->scIsBlogAdmin()) return;
    	array_push($data['options'],
      		array('title' => 'SkinChooser',
        	'url' => $this->getAdminURL(),
        	'tooltip' => 'Admin SkinChooser'));
  	}

	function event_InitSkinParse(&$data) {
        global $blogid;

        $blogid = intval($blogid);
        $configarr = $this->getConfigSettings($blogid);
        $siteconfigarr = $this->getConfigSettings(0);
        if (intval($siteconfigarr['disabled']) > 0 || intval($configarr['disabled']) > 0 || array_key_exists($data['skin']->id,$this->getFeedSkins())) {
            // do nothing;
        }
        else {
            $newskinid = intval(cookieVar('nucleus_skinchooser_skin_'.$blogid));
            // 999999999 is skinid of random
            $avail_skins = $this->getAvailableSkins($blogid);
            if (count($avail_skins) === 0) $avail_skins = $this->getAvailableSkins(0);
            $use_random = 0;
            if ($newskinid == 0 && intval($siteconfigarr['random']) > 0 && intval($configarr['random']) > 0) {
                $use_random = 1;
            }

            if (($newskinid == 0 && $use_random > 0) || ($newskinid == 999999999 && array_key_exists(999999999,$avail_skins))) {
                srand((float) microtime() * 10000000);
                $rand_key = array_rand($avail_skins);
                $newskinid = $rand_key;
            }

            if ($newskinid > 0 && array_key_exists($newskinid,$avail_skins) && $newskinid != 999999999) {
                global $skinid;
                $newskinname = SKIN::getNameFromId($newskinid);
                $newskin = SKIN::createFromName($newskinname);
                $data['skin']->id = $newskin->id;
                $data['skin']->name = $newskin->name;
                $data['skin']->description= $newskin->description;
                $data['skin']->contentType = $newskin->contentType;
                $data['skin']->includeMode = $newskin->includeMode;
                $data['skin']->includePrefix = $newskin->includePrefix;
                $skinid = $newskin->id;
            }
        }
	}

	function doSkinVar($skinType) {
        global $CONF,$skinid,$blogid;

        $blogid = intval($blogid);
        $configarr = $this->getConfigSettings($blogid);
        $siteconfigarr = $this->getConfigSettings(0);
        if (!(intval($siteconfigarr['disabled']) > 0 && intval($configarr['disabled']) > 0)) {
            $skin_array = $this->getAvailableSkins($blogid);
            if (count($skin_array) === 0) $skin_array = $this->getAvailableSkins(0);
            echo '<form name="scChooser" method="post" action="'.$CONF['ActionURL'].'">'."\n";
            echo "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n";            echo "<input type=\"hidden\" name=\"name\" value=\"SkinChooser\" />\n";            echo "<input type=\"hidden\" name=\"type\" value=\"set_cookie\" />\n";
            echo "<input type=\"hidden\" name=\"bid\" value=\"$blogid\" />\n";
            echo '<select name="sid" onChange="document.scChooser.submit()">'."\n";
            $menu = '';
            foreach ($skin_array as $key=>$value) {
                if ($key == $skinid) {
                    $menu .= '<option value="'.$key.'" selected="selected">'.$value."</option>\n";
                }
                else {
                    $menu .= '<option value="'.$key.'">'.$value."</option>\n";
                }
            }
            $menu .= "</select>\n";
            echo $menu;
            echo '<noscript><input type="submit" value="Set" class="formbutton" /></noscript></form>'."\n";            echo "</form>\n";
        }
        else {
            //do nothing;
        }
	}

	function doAction($type) {
        global $CONF;
        $desturl = serverVar('HTTP_REFERER');
        switch ($type) {
            case 'set_cookie':
                $sid = intPostVar('sid');
                $bid = intPostVar('bid');
                if ($sid > 0) {
                    setcookie("nucleus_skinchooser_skin_$bid", $sid, time() + 60*60*24*365,$CONF['CookiePath'],$CONF['CookieDomain'],$CONF['CookieSecure']);
                }
            break;
            default:
                doError("No Such action: $type");
        }
        redirect($desturl);
	}

	/*
	 * Helper methods
	 */
    function getAllSkins() {
        $r = array();
        $query = "SELECT sdnumber, sdname FROM ".sql_table('skin_desc')." WHERE sdname NOT LIKE '%feeds%' AND sdname NOT LIKE '%xml%' ORDER BY `sdname` ASC";
        $result = sql_query($query);
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $r[$row['sdnumber']] = $row['sdname'];
            }
        }
        $r[999999999] = 'random skin';
        return $r;
    }

    function getAvailableSkins($bid = 0) {
        $bid = intval($bid);
        $r = array();
        $query = "SELECT `skinid`, `skinname` FROM `".sql_table('plug_skinchooser')."` WHERE `blogid`=$bid ORDER BY `skinname` ASC";
//echo "query= $query <br />";
        $result = sql_query($query);
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $r[$row['skinid']] = $row['skinname'];
            }
        }
        return $r;
    }

    function setAvailableSkins($valuearr = '', $bid = '') {
        if (!is_array($valuearr)) return;
        if ($bid === '') return;
        $bid = intval($bid);
        if ( !$this->scRights($bid) ) return;
        sql_query("DELETE FROM `".sql_table('plug_skinchooser')."` WHERE `skinid` > 0 AND `blogid`=$bid");

        foreach ($valuearr as $value) {
            $value = intval($value);
            if ($value > 0) {
                if ($value == 999999999) {
                    $skinname = 'random skin';
                }
                else {
                    $skinname = addslashes(quickQuery("SELECT `sdname` as result FROM `".sql_table('skin_desc')."` WHERE `sdnumber`=$value"));
                }
                $query = "INSERT INTO `".sql_table('plug_skinchooser')."` (`skinid`,`skinname`,`blogid`) VALUES($value,'$skinname',$bid)";
                sql_query($query);
            }
        }
    }

    function getConfigSettings($bid = 0) {
        $bid = intval($bid);
        $r = array();
        $result = sql_query("SELECT `configvalue`, `configname` FROM `".sql_table('plug_skinchooser_config')."` WHERE `blogid`=$bid");
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $r[$row['configname']] = $row['configvalue'];
            }
        }
        return $r;
    }

    function setConfigSettings($valuearr = '', $bid) {
        if (!is_array($valuearr)) return;
        if ($bid === '') return;
        $bid = intval($bid);
        if ( !$this->scRights($bid) ) return;

        sql_query("DELETE FROM `".sql_table('plug_skinchooser_config')."` WHERE `blogid`=$bid");

        foreach ($valuearr as $key=>$value) {
            $key = addslashes($key);
            $value = addslashes($value);

            $query = "INSERT INTO `".sql_table('plug_skinchooser_config')."` (`blogid`,`configname`,`configvalue`) VALUES($bid,'$key','$value')";
            sql_query($query);
        }
    }

    function scRights($bid) {
        global $member;
        $bid = intval($bid);
        if (!$member->isLoggedIn() || !$member->canLogin()) return false;
        if (($bid < 1 && $member->isAdmin()) || ($bid > 0 && $member->blogAdminRights($bid))) return true;
        else return false;
    }

    function scIsBlogAdmin() {
        global $member;
		$query = "SELECT tadmin FROM ".sql_table('team')." WHERE"
		       . " tmember=". $member->getID() ." AND tadmin > 0";
		$res = sql_query($query);
		if (mysql_num_rows($res) < 1)
			return false;
		else
			return true;
	}

    function get_formatted_microtime() {
        list($usec, $sec) = explode(' ', microtime());
        return $usec + $sec;
    }

    function _generateKey() {
		// generates a random key		srand((double)microtime()*1000000);		$key = md5(uniqid(rand(), true));
		return $key;
	}

    function getFeedSkins() {
        $r = array();
        $query = "SELECT sdnumber, sdname FROM ".sql_table('skin_desc')." WHERE sdname LIKE '%feeds%' OR sdname LIKE '%xml%' ORDER BY `sdname` ASC";
        $result = sql_query($query);
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $r[$row['sdnumber']] = $row['sdname'];
            }
        }
        return $r;
    }
}

?>
