<?php
/* NP_SystemInfo plugin
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
    - 1.13 (2010-10-19): 
	* fix access control for blog admins, add some js into table select form (1.13.a.01)
	*Add reports tab and some sample reports (1.13.a.02)
    - 1.12 (2007-05-10): fix mysql data lookup bug when field not selected
    - 1.11 (): added NOT LIKE to data lookup options
    - 1.1 (2006-08-31): added display of plugin event subscriptions, added find
	feature for php/configuration settings and php/loaded modules.
    - 1.0 (2006-06-07): initial release (Frank Truscott)
*/

class NP_SystemInfo extends NucleusPlugin {

	function getName() {	return 'SystemInfo'; 	}
	function getAuthor()  { return 'Frank Truscott'; 	}
	function getURL() { return 'http://www.iai.com/'; }
	function getVersion() {	return '1.13'; }
	function getDescription() {
		return 'Plugin to give ready access to system information for use in troubleshooting';
	}

	function supportsFeature($what)
	{
		switch($what)
		{
		case 'SqlTablePrefix':
			return 1;
		case 'HelpPage':
			return 1;
		default:
			return 0;
		}
	}

	function getEventList() { return array('QuickMenu'); }

	function install() {
		$this->createOption('quickmenu', 'Show Admin Area in quick menu?', 'yesno', 'yes');
        $this->createOption('accesslevel', 'Who should have access to SystemInfo', 'select', 'Site Admins', 'Site Admins|8|Blog Admins|4|Team Members|2|All Logged-In Users|1');
	}

	function unInstall() {
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
      		array('title' => 'SystemInfo',
        	'url' => $this->getAdminURL(),
        	'tooltip' => 'Check System Info'));
  	}

	function doSkinVar($skinType) {
	}

	function doAction($type) {
	}

	/*
	 * Helper methods
	 */
    function siIsAdmin() {
        global $member;
        if ($member->isAdmin()) return 8;
        else return 0;
    }

    function siIsBlogAdmin() {
        global $member;
		$query = 'SELECT tadmin FROM '.sql_table('team').' WHERE'
		       . ' tmember='. $member->getID().' AND tadmin > 0';
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

    function get_formatted_microtime() {
        list($usec, $sec) = explode(' ', microtime());
        return $usec + $sec;
    }

}

?>
