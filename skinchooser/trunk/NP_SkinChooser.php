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
*/

class NP_SkinChooser extends NucleusPlugin {
	function getName() {	return 'SkinChooser'; 	}
	function getAuthor()  { return 'Frank Truscott'; 	}
	function getURL() { return 'http://www.iai.com/'; }
	function getVersion() {	return '0.2'; }
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
        $this->createOption('accesslevel', 'Who should have access to SkinChooser admin', 'select', 'Site Admins', 'Site Admins|8|Blog Admins|4|Team Members|2|All Logged-In Users|1');

        // Create tables needed
        $query = "CREATE TABLE IF NOT EXISTS `".sql_table('plug_skinchooser')."` (
                  `skinid` int(11) NOT NULL,
                  `skinname` varchar(250) default NULL,
                  PRIMARY KEY  (`skinid`),
                  KEY `skinname` (`skinname`)
                  ) TYPE=MyISAM PACK_KEYS=0;";
        sql_query($query);

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
    	global $member;
    	if (!($member->isLoggedIn())) return;
    	array_push($data['options'],
      		array('title' => 'SkinChooser',
        	'url' => $this->getAdminURL(),
        	'tooltip' => 'Admin SkinChooser'));
  	}

	function event_InitSkinParse(&$data) {
        global $blogid;
        $newskinid = intval(cookieVar('nucleus_skinchooser_skin_'.$blogid));
		if ($newskinid > 0 && array_key_exists($newskinid,$this->getAvailableSkins())) {
			//doError($newskinid);
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

	function doSkinVar($skinType) {
        global $CONF,$skinid,$blogid;

        $skin_array = $this->getAvailableSkins();
        echo '<form method="post" action="'.$CONF['ActionURL'].'">'."\n";
        echo "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n";        echo "<input type=\"hidden\" name=\"name\" value=\"SkinChooser\" />\n";        echo "<input type=\"hidden\" name=\"type\" value=\"set_cookie\" />\n";
        echo "<input type=\"hidden\" name=\"bid\" value=\"$blogid\" />\n";
        echo '<select name="sid">'."\n";
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
        echo '<input type="submit" value="Set" class="formbutton" /></form>'."\n";        echo "</form>\n";
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
        $query = "SELECT sdnumber, sdname FROM ".sql_table('skin_desc')." WHERE sdname NOT LIKE '%feeds%' AND sdname NOT LIKE '%xml%'";
        $result = sql_query($query);
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $r[$row['sdnumber']] = $row['sdname'];
            }
        }
        return $r;
    }

    function getAvailableSkins() {
        $r = array();
        $query = "SELECT skinid, skinname FROM ".sql_table('plug_skinchooser');
        $result = sql_query($query);
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $r[$row['skinid']] = $row['skinname'];
            }
        }
        return $r;
    }

    function setAvailableSkins($valuearr = '') {
        if (!is_array($valuearr)) return;
        if (!$this->siRights()) return;
        sql_query("DELETE FROM ".sql_table('plug_skinchooser')." WHERE skinid > 0");

        foreach ($valuearr as $value) {
            $value = intval($value);
            if ($value > 0) {
                $skinname = addslashes(quickQuery("SELECT sdname as result FROM ".sql_table('skin_desc')." WHERE sdnumber=$value"));
                $query = "INSERT INTO ".sql_table('plug_skinchooser')." (skinid,skinname) VALUES($value,'$skinname')";
                sql_query($query);
            }
        }
    }

    function siIsAdmin() {
        global $member;
        if ($member->isAdmin()) return 8;
        else return 0;
    }

    function siIsBlogAdmin() {
        global $member;
		$query = 'SELECT tadmin FROM '.sql_table('team').' WHERE'
		       . ' tmember='. $member->getID();
		$res = sql_query($query);
		if (mysql_num_rows($res) == 0)
			return 0;
		else
			return 4;
	}

	function siIsTeamMember() {
        global $member;
		$query = 'SELECT * FROM '.sql_table('team').' WHERE'
		       . ' tmember='. $member->getID();
		$res = sql_query($query);
		if (mysql_num_rows($res) == 0)
			return 0;
		else
			return 2;
	}

    function siRights() {
        global $member;
        $r = false;
        if ($member->isLoggedIn() && $member->canLogin()) $admin = 1;
        $admin = $admin + intval($this->siIsAdmin()) + intval($this->siIsBlogAdmin()) + intval($this->siIsTeamMember());
        $minaccess = intval($this->getOption('accesslevel'));
        if (!$minaccess || $minaccess == 0) $minaccess = 8;

        if ($admin >= $minaccess) return true;
        else return false;
    }

    function get_formatted_microtime() {
        list($usec, $sec) = explode(' ', microtime());
        return $usec + $sec;
    }

    function _generateKey() {
		// generates a random key		srand((double)microtime()*1000000);		$key = md5(uniqid(rand(), true));
		return $key;
	}


}

?>
