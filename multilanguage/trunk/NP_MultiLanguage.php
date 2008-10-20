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
*/

class NP_MultiLanguage extends NucleusPlugin {

	var $currentLanguage = '';

	function getName()
		{ return 'MultiLanguage'; }

	function getAuthor()
		{ return 'Frank Truscott (ftruscot)'; }

	function getURL()
		{ return 'http://revcetera.com/ftruscot'; }

	function getVersion()
		{ return '1.01'; }

	function getDescription()
		{ return 'This plugin allows you to have a multi-language site. Requires NP_Text and a skin that is multi-language capable.'; }

	function getTableList()
		{ return array(sql_table('plugin_multilanguage','plugin_multilanguage_languages','plugin_multilanguage_templates','plugin_multilanguage_categories')); }

	function getEventList()
		{ return array('PreItem','QuickMenu','PostDeleteItem','PreCategoryListItem','PostDeleteCategory','PreSendContentType','PreTemplateRead','EditItemFormExtras'); }

	function getMinNucleusVersion()
		{ return 330; }

	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			case 'HelpPage':
				return 1;
			default:
				return 0;
		}
	}

	function install() {
		$this->createOption('quickmenu', _MULTILANGUAGE_OPT_QUICKMENU, 'yesno', 'yes');
		$this->createOption('del_uninstall_data', _MULTILANGUAGE_OPT_DEL_UNINSTALL_DATA, 'yesno','no');

		sql_query("CREATE TABLE IF NOT EXISTS `" . sql_table('plugin_multilanguage') . "` (
					mlitemid int(11) NOT NULL,
					mllangid int(11) NOT NULL,
					mlauthorid int(11) NOT NULL,
					mltitle varchar(160) default NULL,
					mlbody text NOT NULL,
					mlmore text,
					KEY `ml_itemid` (`mlitemid`),
					UNIQUE KEY `ml_iid_lid` (`mlitemid`,`mllangid`))
				TYPE=MyISAM;");

		sql_query("CREATE TABLE IF NOT EXISTS `" . sql_table('plugin_multilanguage_categories') . "` (
					mlcatid int(11) NOT NULL,
					mllangid int(11) NOT NULL,
					mlcatname varchar(200) default NULL,
					mlcatdesc varchar(200) default NULL,
					KEY `ml_catid` (`mlcatid`),
					UNIQUE KEY `ml_cid_lid` (`mlcatid`,`mllangid`))
				TYPE=MyISAM;");

		sql_query("CREATE TABLE IF NOT EXISTS `" . sql_table('plugin_multilanguage_languages') . "` (
					mllangid int(11) NOT NULL auto_increment,
					mllanguage varchar(128) NOT NULL default '',
					mllangname varchar(200) default NULL,
					mlflag varchar(128) NOT NULL default '',
					mlcharset varchar(32) NOT NULL default '',
					mlnative tinyint NOT NULL default 0,
					KEY `ml_langid` (`mllangid`),
					KEY `ml_language` (`mllanguage`))
				TYPE=MyISAM;");
		
		sql_query("CREATE TABLE IF NOT EXISTS `" . sql_table('plugin_multilanguage_templates') . "` (
					mltempid int(11) NOT NULL,
					mllangid int(11) NOT NULL,
					mltempname varchar(20) NOT NULL default '',
					KEY `ml_tempid` (`mltempid`),
					KEY `ml_langid` (`mllangid`),
					UNIQUE KEY `ml_tid_lid` (`mltempid`,`mllangid`))
				TYPE=MyISAM;");
				
		$this->upgrade();
	}
	
	function upgrade() {
		// upgrade to 0.7
		if (mysql_num_rows(sql_query("SHOW COLUMNS FROM ".sql_table('plugin_multilanguage_languages')." LIKE '%mlnative%'")) == 0) {
			sql_query("ALTER TABLE ".sql_table('plugin_multilanguage_languages')." ADD `mlnative` tinyint NOT NULL default 0 AFTER `mlcharset`");
	  		sql_query("UPDATE ".sql_table('plugin_multilanguage_languages')." SET mlnative = 0");
		}
	}

	function unInstall() {
		// if requested, delete the data table containing member profile field data
		if ($this->getOption('del_uninstall_data') == 'yes')	{
			sql_query('DROP TABLE '.sql_table('plugin_multilanguage'));
			sql_query('DROP TABLE '.sql_table('plugin_multilanguage_categories'));
			sql_query('DROP TABLE '.sql_table('plugin_multilanguage_languages'));
			sql_query('DROP TABLE '.sql_table('plugin_multilanguage_templates'));
		}
	}

	function init() {
		// include language file for this plugin
        $language = ereg_replace( '[\\|/]', '', getLanguageName());
        if (file_exists($this->getDirectory().$language.'.php'))
            include_once($this->getDirectory().$language.'.php');
        else
            include_once($this->getDirectory().'english.php');

// determine the language of this page if skin is being parsed
		$this->currentLanguage = intval(cookieVar('NP_MultiLanguage'));
	}

	function hasAdminArea() { return 1; }

	function event_QuickMenu(&$data) {
    	// only show when option enabled
    	if ($this->getOption('quickmenu') != 'yes') return;
    	global $member;
    	if (!($member->isLoggedIn())) return;
    	array_push($data['options'],
      		array('title' => 'MultiLanguage',
        	'url' => $this->getAdminURL(),
        	'tooltip' => _MULTILANGUAGE_ADMIN_TOOLTIP));
  	}

	function event_PreSendContentType(&$data) {
		$lid = intval(cookieVar('NP_MultiLanguage'));
		if ($lid > 0) {
			$newcharset = $this->getLanguageCharset($lid);
			if ($newcharset != '') {
				$data['charset'] = $newcharset;
			}
		}
	}

	function event_PostDeleteItem(&$data) {
		$thisitem = intval($data['itemid']);
		sql_query("DELETE FROM ".sql_table('plugin_multilanguage')." WHERE mlitemid=$thisitem");
	}

	function event_PreItem(&$data) {
		$thisitem = &$data["item"];
//print_r($thisitem);
		if ($this->currentLanguage && $this->itemExists($thisitem->itemid,$this->currentLanguage)) {
			$row = mysql_fetch_assoc($this->getItemDef($thisitem->itemid,$this->currentLanguage));
			if (trim($row['mlbody']) != '') $thisitem->body = $row['mlbody'];
			if (trim($row['mlmore']) != '') $thisitem->more = $row['mlmore'];
			if (trim($row['mltitle']) != '') $thisitem->title = $row['mltitle'];
		}
		$thiscatid = getCatIDFromName($thisitem->category);
		if ($this->currentLanguage && $this->categoryExists($thiscatid,$this->currentLanguage)) {
			$crow = mysql_fetch_assoc($this->getCategoryDef($thiscatid,$this->currentLanguage));
			if (trim($crow['mlcatname']) != '') $thisitem->category = $crow['mlcatname'];
		}
	}

	function event_PostDeleteCategory(&$data) {
		$thiscat = intval($data['catid']);
		sql_query("DELETE FROM ".sql_table('plugin_multilanguage_categories')." WHERE mlcatid=$thiscat");
	}

	function event_PreCategoryListItem(&$data) {
		if ($this->currentLanguage && $this->categoryExists($data['listitem']['catid'],$this->currentLanguage)) {
			$row = mysql_fetch_assoc($this->getCategoryDef($data['listitem']['catid'],$this->currentLanguage));
			if (trim($row['mlcatname']) != '') $data['listitem']['catname'] = $row['mlcatname'];
			if (trim($row['mlcatdesc']) != '') $data['listitem']['catdesc'] = $row['mlcatdesc'];
		}
	}
	
	function event_PreTemplateRead(&$data) {
		$tid = TEMPLATE::getIdFromName($data['template']);
		if ($this->currentLanguage && $this->templateExists($tid,$this->currentLanguage)) {
			$row = mysql_fetch_assoc($this->getTemplateDef($tid,$this->currentLanguage));
			$validTemp = quickQuery("SELECT COUNT(*) as result FROM ".sql_table('template_desc')." WHERE tdname='".addslashes(trim($row['mltempname']))."'");
//echo "SELECT * FROM ".sql_table('template_desc')." WHERE tdname='".addslashes(trim($row['mltempname']))."'";
//echo $validTemp;
			//if (trim($row['mltempname']) != '') $data['template'] = $row['mltempname'];
			if ($validTemp) $data['template'] = $row['mltempname'];
		}
	}
	
	function event_EditItemFormExtras(&$params) {
		global $member, $itemid, $CONF;

        echo "<h3>NP_MultiLanguage</h3>\n";
		$blogid = getBlogIDFromItemID($itemid);
		$native = '';
		$translated = array();
		$untranslated = array();
		
		$lresult = $this->getLanguages();
		while ($lrow = mysql_fetch_assoc($lresult))
		{
			if ($lrow['mlnative']) {
				$native = '<p>'._MULTILANGUAGE_EDIT_ITEM_EXTRAS_NATIVE_YES.' '.$lrow['mllangname'].' <img src="'.$lrow['mlflag'].'" alt=" " /></p>';
			}
			elseif ($this->itemExists($itemid,$lrow['mllangid'])) {
				$translated[] = '<a href="'.$CONF['PluginURL'].'multilanguage/index.php?showlist=edititem&amp;iid='.$itemid.'&amp;lid='.$lrow['mllangid'].'&amp;bshow='.$blogid.'&amp;safe=true">'.$lrow['mllangname'].' <img src="'.$lrow['mlflag'].'" alt=" " /></a>';
			}
			else {
				$untranslated[] = '<a href="'.$CONF['PluginURL'].'multilanguage/index.php?showlist=edititem&amp;iid='.$itemid.'&amp;lid='.$lrow['mllangid'].'&amp;bshow='.$blogid.'&amp;safe=true">'.$lrow['mllangname'].' <img src="'.$lrow['mlflag'].'" alt=" " /></a>';
			}
		}

		if ($native != '') echo $native;
		else echo "<p>"._MULTILANGUAGE_EDIT_ITEM_EXTRAS_NATIVE_NO."</p>";
		if (count($translated)) {
			echo "<p>"._MULTILANGUAGE_EDIT_ITEM_EXTRAS_TRANSLATED_YES." : |";
			foreach ($translated as $t) {
				echo " $t |";
			}
			echo "</p>";
		}
		if (count($untranslated)) {
			echo "<p>"._MULTILANGUAGE_EDIT_ITEM_EXTRAS_TRANSLATED_NO." : |";
			foreach ($untranslated as $u) {
				echo " $u |";
			}
			echo "</p>";
		}
        echo "\n";
	}

	function doSkinVar($skinType,$param1) {
		global $CONF;
		switch ($param1) {
		case 'dropdown':
			$result = $this->getLanguages();
			$lid = intval(cookieVar('NP_MultiLanguage'));
			if (mysql_num_rows($result) > 1) {
				echo '<form name="mlChooser" method="post" action="'.$CONF['ActionURL'].'">'."\n";
				echo "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n";
				echo "<input type=\"hidden\" name=\"name\" value=\"MultiLanguage\" />\n";
				echo "<input type=\"hidden\" name=\"type\" value=\"set_cookie\" />\n";
				echo '<select name="lid" onChange="document.mlChooser.submit()">'."\n";
				while ($row = mysql_fetch_assoc($result)) {

					echo '<option style="background:#ffffff url('.$row['mlflag'].') no-repeat right 0px" value="'.$row['mllangid'].'"';
					if ($lid == $row['mllangid'])
						echo " selected='selected'";
					echo '>'.$row['mllangname'].'</option>';
				}
				echo "</select>";
				echo '<noscript><input type="submit" value="'._MULTILANGUAGE_SET.'" class="formbutton" /></noscript></form>'."\n";
				echo "</form>\n";
			}

		break;
		case 'list':
			$result = $this->getLanguages();
			$lid = intval(cookieVar('NP_MultiLanguage'));
			if (mysql_num_rows($result) > 1) {
				echo '<ul class="mllistul">';
				while ($row = mysql_fetch_assoc($result)) {

					echo '<li class="mllistli"><a href="'.$CONF['ActionURL'].'?action=plugin&amp;name=MultiLanguage&amp;type=set_cookie&amp;lid='.$row['mllangid'].'" title="'.$row['mllangname'].'">';
					echo '<img src="'.$row['mlflag'].'" alt="'.$row['mllangname'].'" /></a></li>';
					echo "\n";
				}
				echo "</ul>";
			}
		break;
		default:
			//do nothing
		break;
		}

	}

	function doAction($actionType) {
		global $member,$manager,$CONF;

		$destURL = '';

		switch($actionType) {
		case 'additem':
//doError('Got ya');
			$mlitemid = intval(postVar('mlitemid'));
			$mllangid = intval(postVar('mllangid'));
			$mlauthorid = intval(postVar('mlauthorid'));
			$mltitle = postVar('mltitle');
			$mlbody = postVar('mlbody');
			$mlmore = postVar('mlmore');
			$bid = intval(postVar('blogid'));
			if ((!$member->teamRights($bid)) || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			$blog =& $manager->getBlog(getBlogIDFromItemID($mlitemid));
			if ($blog->convertBreaks()) {
				$mlbody = addBreaks($mlbody);
				$mlmore = addBreaks($mlmore);
			}
			if ($this->addTranslatedItem($mlitemid,$mllangid,$mlauthorid,$mltitle,$mlbody,$mlmore)) {
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=items&bshow=$bid&safe=true&status=1";
			}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=items&bshow=$bid&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));
		break;
		case 'itemupdate':
			$mlitemid = intval(postVar('mlitemid'));
			$mllangid = intval(postVar('mllangid'));
			$mlauthorid = intval(postVar('mlauthorid'));
			$mltitle = postVar('mltitle');
			$mlbody = postVar('mlbody');
			$mlmore = postVar('mlmore');
			$bid = intval(postVar('blogid'));
			if ((!$member->teamRights($bid)) || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			$blog =& $manager->getBlog(getBlogIDFromItemID($mlitemid));
			if ($blog->convertBreaks()) {
				$mlbody = addBreaks($mlbody);
				$mlmore = addBreaks($mlmore);
			}
			if ($this->updateTranslatedItem($mlitemid,$mllangid,$mlauthorid,$mltitle,$mlbody,$mlmore)) {
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=items&bshow=$bid&safe=true&status=2";
			}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=items&bshow=$bid&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));
		break;
		case 'deleteitem':
			$mlitemid = intPostVar('mlitemid');
			$bid = getBlogIDFromItemID($mlitemid);
			$mllangid = intval(postVar('mllangid'));
			if ((!$member->teamRights($bid)) || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			if ($this->deleteTranslatedItem($mlitemid,$mllangid)) {
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=items&safe=true&status=3";
			}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=items&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));

		break;
		case 'addcategory':
			$mlcatid = intPostVar('mlcatid');
			$bid = getBlogIDFromCatID($mlcatid);
			$mlcatname = trim(postVar('mlcatname'));
			$mlcatdesc = trim(postVar('mlcatdesc'));
			$mllangid = intval(postVar('mllangid'));
			if ((!$member->isBlogAdmin($bid) && !$member->isAdmin()) || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			if ($this->addTranslatedCategory($mlcatid,$mllangid,$mlcatname,$mlcatdesc)) {
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=categories&bshow=$bid&safe=true&status=1";
			}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=categories&bshow=$bid&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));
		break;
		case 'updatecategory':
			$mlcatid = intPostVar('mlcatid');
			$bid = getBlogIDFromCatID($mlcatid);
			$mlcatname = trim(postVar('mlcatname'));
			$mlcatdesc = trim(postVar('mlcatdesc'));
			$mllangid = intval(postVar('mllangid'));
			if ((!$member->isBlogAdmin($bid) && !$member->isAdmin()) || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			if ($this->updateTranslatedCategory($mlcatid,$mllangid,$mlcatname,$mlcatdesc)) {
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=categories&bshow=$bid&safe=true&status=2";
			}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=categories&bshow=$bid&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));
		break;
		case 'deletecategory':
			$mlcatid = intPostVar('mlcatid');
			$bid = getBlogIDFromCatID($mlcatid);
			$mllangid = intval(postVar('mllangid'));
			if ((!$member->isBlogAdmin($bid) && !$member->isAdmin()) || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			if ($this->deleteTranslatedCategory($mlcatid,$mllangid)) {
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=categories&bshow=$bid&safe=true&status=3";
			}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=categories&bshow=$bidh&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));

		break;
		case 'addlanguage':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			$mllanguage = addslashes(postVar('mllanguage'));
			$mllangname = addslashes(postVar('mllangname'));
			$mlflag = addslashes(postVar('mlflag'));
			$mlcharset = addslashes(postVar('mlcharset'));
			$mlnative = intval(postVar('mlnative'));
			if (!quickQuery("SELECT COUNT(*) FROM ".sql_table('plugin_multilanguage_languages')." WHERE mllanguage='$mllanguage'")) {
				sql_query("INSERT INTO ".sql_table('plugin_multilanguage_languages')." VALUES('','$mllanguage','$mllangname','$mlflag','$mlcharset',$mlnative)");
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=languages&safe=true&status=1";
				}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=languages&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));
		break;
		case 'updatelanguage':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			$mllangid = intval(postVar('mllangid'));
			$mllanguage = addslashes(postVar('mllanguage'));
			$mllangname = addslashes(postVar('mllangname'));
			$mlflag = addslashes(postVar('mlflag'));
			$mlcharset = addslashes(postVar('mlcharset'));
			$mlnative = intval(postVar('mlnative'));
			if ($this->languageExists($mllangid)) {
				sql_query("UPDATE ".sql_table('plugin_multilanguage_languages')." SET mllanguage='$mllanguage', mllangname='$mllangname', mlflag='$mlflag', mlcharset='$mlcharset', mlnative=$mlnative WHERE mllangid=$mllangid");
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=languages&safe=true&status=2";
				}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=languages&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));
		break;
		case 'deletelanguage':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			$mllangid = intval(postVar('mllangid'));
			if ($this->languageExists($mllangid)) {
				sql_query("DELETE FROM ".sql_table('plugin_multilanguage_languages')." WHERE mllangid=$mllangid");
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=languages&safe=true&status=3";
			}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=languages&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));
		break;
		case 'addtemplate':
			$mltempid = intPostVar('mltempid');
			$mltempname = trim(postVar('mltempname'));
			$mllangid = intval(postVar('mllangid'));
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			if ($this->addTranslatedTemplate($mltempid,$mllangid,$mltempname)) {
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=templates&safe=true&status=1";
			}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=templates&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));
		break;
		case 'updatetemplate':
			$mltempid = intPostVar('mltempid');
			$mltempname = trim(postVar('mltempname'));
			$mllangid = intval(postVar('mllangid'));
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			if ($this->updateTranslatedTemplate($mltempid,$mllangid,$mltempname)) {
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=templates&safe=true&status=2";
			}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=templates&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));
		break;
		case 'deletetemplate':
			$mltempid = intPostVar('mltempid');
			$mllangid = intval(postVar('mllangid'));
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_MULTILANGUAGE_ACTION_DENY);
			if ($this->deleteTranslatedTemplate($mltempid,$mllangid)) {
				$destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=templates&safe=true&status=3";
			}
			else $destURL = $CONF['PluginURL'] . "multilanguage/index.php?showlist=templates&safe=true&status=0";
			header('Location: ' . $manager->addTicketToUrl($destURL));

		break;
		case 'set_cookie':
            $lid = intRequestVar('lid');
            if ($lid > 0) {
                setcookie("NP_MultiLanguage", $lid, time() + 60*60*24*365,$CONF['CookiePath'],$CONF['CookieDomain'],$CONF['CookieSecure']);
				setcookie('NP_Text', $this->getLanguageFromId($lid), time()+60*60*24*90);
			}
			$destURL = serverVar('HTTP_REFERER');
			redirect($destURL);
		break;
		default:
			doError(_MULTILANGUAGE_ACTION_UNKNOWN);
		break;
		}
	}

/******************************************************************************
 * Methods for internal use                                                   *
 ******************************************************************************/

	function getLanguageCharset($lid) {
		$lid = intval($lid);
		$query = "SELECT mlcharset as result FROM ".sql_table('plugin_multilanguage_languages')." WHERE mllangid=$lid";
		$result = trim(quickQuery($query));
		if (strlen($result) < 2) return '';
		else return $result;
	}

	function getLanguages() {
		$query = "SELECT * FROM ".sql_table('plugin_multilanguage_languages');
		$result = sql_query($query);
		return $result;
	}

	function getLanguageDef($lid) {
		$lid = intval($lid);
		$query = "SELECT * FROM ".sql_table('plugin_multilanguage_languages')." WHERE mllangid=$lid";
		$result = sql_query($query);
		return $result;
	}

	function languageExists($lid) {
		$lid = intval($lid);
		$query = "SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage_languages')." WHERE mllangid=$lid";
		$result = quickQuery($query);
		return $result;
	}

	function getLanguageFromId($lid) {
		$lid = intval($lid);
		return quickQuery("SELECT mllanguage as result FROM ".sql_table('plugin_multilanguage_languages')." WHERE mllangid=$lid");
	}
	
	function getFlagFromLanguageId($lid) {
		$lid = intval($lid);
		return quickQuery("SELECT mlflag as result FROM ".sql_table('plugin_multilanguage_languages')." WHERE mllangid=$lid");
	}

	function getItems() {
		$query = "SELECT * FROM ".sql_table('plugin_multilanguage'." ORDER BY mlitemid DESC");
		$result = sql_query($query);
		return $result;
	}

	function getItemDef($iid,$lid) {
		$iid = intval($iid);
		$lid = intval($lid);
		$query = "SELECT * FROM ".sql_table('plugin_multilanguage')." WHERE mlitemid=$iid AND mllangid=$lid";
		$result = sql_query($query);
		return $result;
	}

	function itemExists($iid,$lid) {
		$iid = intval($iid);
		$lid = intval($lid);
		$query = "SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage')." WHERE mlitemid=$iid AND mllangid=$lid";
		$result = quickQuery($query);
		return $result;
	}

	function deleteTranslatedItem($iid,$lid) {
		$iid = intval($iid);
		$lid = intval($lid);
		if (quickQuery("SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage')." WHERE mlitemid=$iid AND mllangid=$lid")) {
			sql_query("DELETE FROM ".sql_table('plugin_multilanguage')." WHERE mlitemid=$iid AND mllangid=$lid");
			return 1;
		}
		else return 0;
	}

	function getCategories() {
		$query = "SELECT * FROM ".sql_table('plugin_multilanguage_categories'." ORDER BY mlcatid ASC");
		$result = sql_query($query);
		return $result;
	}

	function getCategoryDef($cid,$lid) {
		$cid = intval($cid);
		$lid = intval($lid);
		$query = "SELECT * FROM ".sql_table('plugin_multilanguage_categories')." WHERE mlcatid=$cid AND mllangid=$lid";
		$result = sql_query($query);
		return $result;
	}

	function categoryExists($cid,$lid) {
		$cid = intval($cid);
		$lid = intval($lid);
		$query = "SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage_categories')." WHERE mlcatid=$cid AND mllangid=$lid";
		$result = quickQuery($query);
		return $result;
	}

	function deleteTranslatedCategory($cid,$lid) {
		$cid = intval($cid);
		$lid = intval($lid);
		if (quickQuery("SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage_categories')." WHERE mlcatid=$cid AND mllangid=$lid")) {
			sql_query("DELETE FROM ".sql_table('plugin_multilanguage_categories')." WHERE mlcatid=$cid AND mllangid=$lid");
			return 1;
		}
		else return 0;
	}

	function addTranslatedCategory($cid,$lid,$cname,$cdesc) {
		$cid = intval($cid);
		$lid = intval($lid);
		$cname = addslashes($cname);
		$cdesc = addslashes($cdesc);
		if ($cid > 0 && $lid >0 && !quickQuery("SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage_categories')." WHERE mlcatid=$cid AND mllangid=$lid")) {
			sql_query("INSERT INTO ".sql_table('plugin_multilanguage_categories')." (mlcatid,mllangid,mlcatname,mlcatdesc) VALUES ($cid,$lid,'$cname','$cdesc')");
			return 1;
		}
		else return 0;
	}

	function updateTranslatedCategory($cid,$lid,$cname,$cdesc) {
		$cid = intval($cid);
		$lid = intval($lid);
		$cname = addslashes($cname);
		$cdesc = addslashes($cdesc);
		if (quickQuery("SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage_categories')." WHERE mlcatid=$cid AND mllangid=$lid")) {
			sql_query("UPDATE ".sql_table('plugin_multilanguage_categories')." SET mlcatname='$cname', mlcatdesc='$cdesc' WHERE mlcatid=$cid AND mllangid=$lid");
			return 1;
		}
		else return 0;
	}

	function addTranslatedItem($iid,$lid,$aid,$title,$body,$more) {
		$iid = intval($iid);
		$lid = intval($lid);
		$aid = intval($aid);
		$title = addslashes($title);
		$body = addslashes($body);
		$more = addslashes($more);
		if ($iid > 0 && $lid >0 && !quickQuery("SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage')." WHERE mlitemid=$iid AND mllangid=$lid")) {
			sql_query("INSERT INTO ".sql_table('plugin_multilanguage')." (mlitemid,mllangid,mlauthorid,mltitle,mlbody,mlmore) VALUES ($iid,$lid,$aid,'$title','$body','$more')");
			return 1;
		}
		else return 0;
	}

	function updateTranslatedItem($iid,$lid,$aid,$title,$body,$more) {
		$iid = intval($iid);
		$lid = intval($lid);
		$aid = intval($aid);
		$title = addslashes($title);
		$body = addslashes($body);
		$more = addslashes($more);
		if (quickQuery("SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage')." WHERE mlitemid=$iid AND mllangid=$lid")) {
			sql_query("UPDATE ".sql_table('plugin_multilanguage')." SET mlauthorid=$aid, mltitle='$title',mlbody='$body',mlmore='$more' WHERE mlitemid=$iid AND mllangid=$lid");
			return 1;
		}
		else return 0;
	}
	
	function getTemplates() {
		$query = "SELECT * FROM ".sql_table('plugin_multilanguage_templates'." ORDER BY mltempid ASC");
		$result = sql_query($query);
		return $result;
	}

	function getTemplateDef($tid,$lid) {
		$tid = intval($tid);
		$lid = intval($lid);
		$query = "SELECT * FROM ".sql_table('plugin_multilanguage_templates')." WHERE mltempid=$tid AND mllangid=$lid";
		$result = sql_query($query);
		return $result;
	}

	function templateExists($tid,$lid) {
		$tid = intval($tid);
		$lid = intval($lid);
		$query = "SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage_templates')." WHERE mltempid=$tid AND mllangid=$lid";
		$result = quickQuery($query);
		return $result;
	}

	function deleteTranslatedTemplate($tid,$lid) {
		$tid = intval($tid);
		$lid = intval($lid);
		if (quickQuery("SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage_templates')." WHERE mltempid=$tid AND mllangid=$lid")) {
			sql_query("DELETE FROM ".sql_table('plugin_multilanguage_templates')." WHERE mltempid=$tid AND mllangid=$lid");
			return 1;
		}
		else return 0;
	}

	function addTranslatedTemplate($tid,$lid,$tname) {
		$tid = intval($tid);
		$lid = intval($lid);
		$tname = addslashes($tname);
		if ($tid > 0 && $lid >0 && !quickQuery("SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage_templates')." WHERE mltempid=$tid AND mllangid=$lid")) {
			sql_query("INSERT INTO ".sql_table('plugin_multilanguage_templates')." (mltempid,mllangid,mltempname) VALUES ($tid,$lid,'$tname')");
			return 1;
		}
		else return 0;
	}

	function updateTranslatedTemplate($tid,$lid,$tname) {
		$tid = intval($tid);
		$lid = intval($lid);
		$tname = addslashes($tname);
		if (quickQuery("SELECT COUNT(*) as result FROM ".sql_table('plugin_multilanguage_templates')." WHERE mltempid=$tid AND mllangid=$lid")) {
			sql_query("UPDATE ".sql_table('plugin_multilanguage_templates')." SET mltempname='$tname' WHERE mltempid=$tid AND mllangid=$lid");
			return 1;
		}
		else return 0;
	}
/*
	function getLanguageDropdown($fieldname) {
		global $CONF;
		$langlist = '<select name="'.$fieldname.'" tabindex="85">';
				$dirhandle = opendir($DIR_LANG);
				while ($filename = readdir($dirhandle)) {
					if (ereg("^(.*)\.php$",$filename,$matches)) {
						$name = $matches[1];
						echo "<option value='$name'";
						if ($name == $mem->getLanguage())
							echo " selected='selected'";
						echo ">$name</option>";
					}
				}
				closedir($dirhandle);

				?>
				</select>
	}
*/

}
?>