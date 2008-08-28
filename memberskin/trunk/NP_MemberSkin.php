<?php
/* NP_MemberSkin plugin
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
    * v 1.0 - initial release.
*/

class NP_MemberSkin extends NucleusPlugin {
	function getName() {	return 'MemberSkin'; 	}
	function getAuthor()  { return 'Frank Truscott'; 	}
	function getURL() { return 'http://revcetera.com/'; }
	function getVersion() {	return '1.00'; }
	function getDescription() {
		return 'Use single skin for all member pages';
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

	function getEventList() { return array('InitSkinParse'); }

	function install() {
		global $CONF;
		$baseskin = SKIN::getNameFromId($CONF['BaseSkin']);
        $this->createOption("memberskin_name", "Name of skin for member pages", "select", $baseskin, $this->_getSkinList());
		$this->createOption("memberskin_enabled", "Should MemberSkin be enabled?", "yesno", "no");
    }

	function init() {
		if ($this->getOption('memberskin_name') != '') {
			sql_query("UPDATE ".sql_table('plugin_option_desc')." SET oextra='".$this->_getSkinList()."' WHERE oname='memberskin_name'");
		}
	}

	function event_InitSkinParse(&$data) {
		if($data['type'] == 'member' && $this->getOption('memberskin_enabled') == 'yes') {

			global $skinid;
			$newskinname = $this->getOption('memberskin_name');
			$newskin = SKIN::createFromName($newskinname);
			if ($newskin->isValid) {
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
	
	function _getSkinList() {
		$skinlist = '';
		$i = 0;
		$result = sql_query("SELECT sdname FROM ".sql_table('skin_description')." WHERE sdname NOT LIKE 'feeds%' AND sdname NOT LIKE 'xml%'");
		while ($skinname = mysql_fetch_object($result)) {
			$skinlist .= ($i > 0 ? "|" : "").$skinname->sdname."|".$skinname->sdname;
			$i += 1;
		}
		return $skinlist;
	}

}

?>
