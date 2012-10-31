<?php
/* NP_BadBehavior plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, http://revcetera.com/ftruscot
 *
 * License information: LGPLv3
 * http://www.gnu.org/licenses/
 *
 */

/* uses Bad Behavior scripts by MichaelHampton,MarkJaquith,FirasDurri,AndySkelton
 *
 * License: LGPLv3
 * 
 * Bad Behavior - detects and blocks unwanted Web accesses
 * Copyright (C) 2005,2006,2007,2008,2009,2010,2011,2012 Michael Hampton
 * 
 * Bad Behavior is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Lesser General Public License as published by the Free
 * Software Foundation; either version 3 of the License, or (at your option) any
 * later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
 * Please report any problems to bad . bots AT ioerror DOT us
 * http://bad-behavior.ioerror.us/
 */

/*
    Version history:
     * v 2.2.11 - updates to new 2.2.11 version of BadBehavior scripts, identifies new bot types.  fixes whitelisting issue.
     * v 2.2.07 - updates to new 2.2.7 version of BadBehavior scripts, identifies new bot types.  License updated to LGPLv3 for compliance with badbehavior scripts
     * v 2.2.03 - updates to new 2.2.3 version of BadBehavior scripts, implement new bb2_read_whitelist() function for future enabling of maintaining whitelist in admin
     * v 2.2.02 - updates to new 2.2.2 version of BadBehavior scripts, fix bug in reading of settings
     * v 2.2.01 - updates to new 2.2.1 versions of BadBehavior scripts
****************************************************************************************************************************
*************IMPORTANT UPGRADE CONSIDERATIONS NEEDED TO GO TO 2.2.x*************************************************
*****Requires Nucleus CMS 3.50 or newer. **************************************************************************************
*****No need to uninstall old version, but please remove all old files from the nucleus/plugins/badbehavior/ folder before uploading the new****
****************************************************************************************************************************
    * v 1.14 - updates badbehavior scripts to 2.0.43 
    * v 1.13 - updates badbehavior scripts to 2.0.41 
    * v 1.12 - updates badbehavior scripts to 2.0.39 
    * v 1.11 - updates badbehavior scripts to 2.0.38
    * v 1.10  - updates badbehavior scripts to 2.0.30
    * v 1.02  - adds global variable $np_bb_off to allow external php programs that include nucleus pages to not run badbehavior
    * v 1.01  - adds link in logs to ip whois lookup at ip-lookup.net 
		fixes display of errors when not sufficient privileges
    * v 1.00 - Requires total and complete uninstall of previous versions, including the include statement in the config.php file
		Use PostAuthentication event instead of requiring an include statement be written to the config.php file (include in config.php no longer supprted).
		Add admin db table to store some config options available in the admin area.
		Add option to disable from the plugin options.
		Fix access rights option.
		update to v2.0.25 of bad-behavior scripts
****************************************************************************************************************************
*************IMPORTANT UPGRADE CONSIDERATIONS NEEDED TO GO TO 1.00*************************************************
*****Requires total and complete uninstall of previous versions, including the include statement in the config.php file************************
****************************************************************************************************************************
    * v 0.26 - update to v2.0.24 to stay current. Some security enhancements. Optional use of http:BL Honey Pot Project data. Need key from http:BL
    * v 0.25 - update to v2.0.20 to stay current. Some security enhancements
    * v 0.24 - update to v2.0.13 to stay current. Some security enhancements
    * v 0.23 - update to v2.0.11 for blacklist fix.
    * v 0.22 - fix bug on log tab of admin page. Use tickets to make v 3.3 happy. Add skinvar (thanks admun).
    * v 0.21 - fix bug in logs on initial page load. Scrub input a bit more in log queries.
    * v 0.2 - add Logs tab to admin page
    * v 0.1b - initial beta release.
*/

class NP_BadBehavior extends NucleusPlugin {

	var $minRights = 8;
	//var $bbconf = array('strict'=>false,'verbose'=>false,'httpbl_key'=>'');
	var $bbconf = array(
		'log_table' => 'bad_behavior',
		'display_stats' => true,
		'strict' => false,
		'verbose' => false,
		'logging' => true,
		'httpbl_key' => '',
		'httpbl_threat' => '25',
		'httpbl_maxage' => '30',
		'offsite_forms' => false,		
		'reverse_proxy' => false,
		'reverse_proxy_header' => "X-Forwarded-For"
	);

	function getName() {	return 'BadBehavior'; 	}
	function getAuthor()  { return 'Frank Truscott'; 	}
	function getURL() { return 'http://revcetera.com/ftruscot/'; }
	function getVersion() {	return '2.2.11'; }
	function getMinNucleusVersion() { return 350; }
	function getDescription() {
		return 'Give admin area for bad behavior spam fighting script';
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

	function getEventList() { return array('QuickMenu','PostAuthentication'); }
	function getTableList() { return array(sql_table('bad_behavior'),sql_table('bad_behavior_admin')); }

	function install() {
		$this->createOption('quickmenu', 'Show Admin Area in quick menu?', 'yesno', 'yes');
        $this->createOption('accesslevel', 'Who should have access to BadBehavior admin', 'select', 'Site Admins', 'Site Admins|8|Blog Admins|4|Team Members|2|All Logged-In Users|1');
		$this->createOption('bb_enabled', 'Enable Bad Behavior?', 'yesno', 'yes');
		$this->createOption('del_uninstall', 'Delete NP_BadBehavior data tables on uninstall?', 'yesno','no');

		$this->init_tables();
    }

	function init_tables() {
		$query = "CREATE TABLE IF NOT EXISTS `".sql_table('bad_behavior_admin')."` (";
		$query .= "`name` varchar(20) NOT NULL default '', ";
		$query .= "`value` varchar(128) default NULL, ";
		$query .= "PRIMARY KEY  (`name`)";
		$query .= ") TYPE=MyISAM;";
		sql_query($query);

		if (!quickQuery("SELECT COUNT(*) as result FROM ".sql_table('bad_behavior_admin'))) {
			$query = "INSERT INTO ".sql_table('bad_behavior_admin')." VALUES ";
			$j = 0;
			$this->bbconf['log_table'] = sql_table($this->bbconf['log_table']);
			foreach ($this->bbconf as $key=>$value) {
				$query .= ($j == 0 ? '' : ', ')."('".sql_real_escape_string($key)."','".sql_real_escape_string($value)."')";
				$j = $j + 1;
			}
			sql_query($query);
		}
	}

	function unInstall() {
		if ($this->getOption('del_uninstall') == 'yes')	{
			sql_query('DROP TABLE '.sql_table('bad_behavior_admin'));
			sql_query('DROP TABLE '.sql_table('bad_behavior'));
		}
	}

	function init() {
		$minaccess = intval($this->getOption('accesslevel'));
		if ($minaccess < 1) $this->minRights = 8;
		else $this->minRights = $minaccess;
		// override this feature
		//$this->minRights = 8;

		if (!sql_num_rows(sql_query("SHOW TABLES LIKE '".sql_table('bad_behavior_admin')."'"))) {
			$this->init_tables();
		}
	}

	function hasAdminArea() { return 1; }

	function event_QuickMenu(&$data) {
    	// only show when option enabled
    	if ($this->getOption('quickmenu') != 'yes') return;
    	global $member,$manager;
    	if (!($this->siRights() >= $this->minRights)) return;
    	array_push($data['options'],
      		array('title' => 'BadBehavior',
        	'url' => $this->getAdminURL(),
        	'tooltip' => 'Check BadBehavior'));
  	}

	function event_PostAuthentication(&$data) {
		global $DIR_PLUGINS,$CONF,$np_bb_off;
		if (isset($np_bb_off) && intval($np_bb_off)) return;
		if ($this->getOption('bb_enabled') == 'yes') {
			if (!defined('BB2_CORE')) {
				includephp($DIR_PLUGINS.'badbehavior/bad-behavior-nucleuscms.php');
        	}			
		}
	}
	
	function doSkinVar($skinType) {
        $blocked = sql_num_rows(sql_query("SELECT id FROM " . sql_table('bad_behavior') . " WHERE `key` NOT LIKE '00000000'"));
        echo $blocked;
    }

	function doAction($type) {
	}

	/*
	 * Helper methods
	 */
	function siRights() {
		global $member;
		return $this->siIsAdmin() + $this->siIsBlogAdmin() + $this->siIsTeamMember() + $this->siIsLoggedIn();
	}

    function siIsAdmin() {
        global $member;
        if ($member->isAdmin()) return 8;
        else return 0;
    }

    function siIsBlogAdmin() {
        global $member;

		if (count($member->getAdminBlogs()) < 1)
			return 0;
		else
			return 4;
	}

	function siIsTeamMember() {
        global $member;
		$query = 'SELECT * FROM '.sql_table('team').' WHERE'
		       . ' tmember='. $member->getID();
		$res = sql_query($query);
		if (sql_num_rows($res) == 0)
			return 0;
		else
			return 2;
	}

	function siIsLoggedIn() {
        global $member;
        if ($member->isLoggedIn()) return 1;
        else return 0;
    }


    function get_formatted_microtime() {
        list($usec, $sec) = explode(' ', microtime());
        return $usec + $sec;
    }

}

?>