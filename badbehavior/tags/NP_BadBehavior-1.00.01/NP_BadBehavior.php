<?php
/* NP_BadBehavior plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, http://www.iai.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 */

/* uses Bad Behavior scripts by MichaelHampton,MarkJaquith,FirasDurri,AndySkelton
 *
 * see http://www.bad-behavior.ioerror.us/
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
	);

	function getName() {	return 'BadBehavior'; 	}
	function getAuthor()  { return 'Frank Truscott'; 	}
	function getURL() { return 'http://www.iai.com/'; }
	function getVersion() {	return '1.00'; }
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

	function getEventList() { return array('QuickMenu',/*'PreSendContentType',*/'PostAuthentication'); }
	function getTableList() { return array(sql_table('bad_behavior'),sql_table('bad_behavior_admin')); }

	function install() {
		$this->createOption('quickmenu', 'Show Admin Area in quick menu?', 'yesno', 'yes');
        $this->createOption('accesslevel', 'Who should have access to BadBehavior admin', 'select', 'Site Admins', 'Site Admins|8|Blog Admins|4|Team Members|2|All Logged-In Users|1');
		$this->createOption('bb_enabled', 'Enable Bad Behavior?', 'yesno', 'no');
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
				$query .= ($j == 0 ? '' : ', ')."('".addslashes($key)."','".addslashes($value)."')";
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

		if (!mysql_num_rows(sql_query("SHOW TABLES LIKE '".sql_table('bad_behavior_admin')."'"))) {
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
		global $DIR_PLUGINS,$CONF;
		if ($this->getOption('bb_enabled') == 'yes') {
			if (!defined('BB2_CORE')) {
				includephp($DIR_PLUGINS.'badbehavior/bad-behavior-nucleuscms.php');
        	}			
		}
	}
/*
	function event_PreSendContentType(&$data) {

		global $DIR_PLUGINS;
		//global $nuc_strict,$nuc_verbose,$nuc_httpbl_key,$BBCONF;
		if ($this->getOption('bb_enabled') == 'yes') {
			//$nuc_strict = $this->bbconf['strict'];
			//$nuc_verbose = $this->bbconf['verbose'];
			//$nuc_httpbl_key = $this->bbconf['httpbl_key'];
			//$BBCONF = $this->getConfig();

			includephp($DIR_PLUGINS.'badbehavior/bad-behavior-nucleuscms.php');
		}
	}
*/
	
	function doSkinVar($skinType) {
        $blocked = mysql_num_rows(sql_query("SELECT id FROM " . sql_table('bad_behavior') . " WHERE `key` NOT LIKE '00000000'"));
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
/*		$query = 'SELECT tadmin FROM '.sql_table('team').' WHERE'
		       . ' tmember='. $member->getID();
		$res = sql_query($query);

		if (mysql_num_rows($res) == 0)
*/
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
		if (mysql_num_rows($res) == 0)
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
