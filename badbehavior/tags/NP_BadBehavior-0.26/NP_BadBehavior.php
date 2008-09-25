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

	function getName() {	return 'BadBehavior'; 	}
	function getAuthor()  { return 'Frank Truscott'; 	}
	function getURL() { return 'http://www.iai.com/'; }
	function getVersion() {	return '0.26'; }
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

	function getEventList() { return array('QuickMenu'); }

	function install() {
		$this->createOption('quickmenu', 'Show Admin Area in quick menu?', 'yesno', 'yes');
        $this->createOption('accesslevel', 'Who should have access to BadBehavior admin', 'select', 'Site Admins', 'Site Admins|8|Blog Admins|4|Team Members|2|All Logged-In Users|1');
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
      		array('title' => 'BadBehavior',
        	'url' => $this->getAdminURL(),
        	'tooltip' => 'Check BadBehavior'));
  	}

	function doSkinVar($skinType) {
        $blocked = mysql_num_rows(sql_query("SELECT id FROM " . sql_table('bad_behavior') . " WHERE `key` NOT LIKE '00000000'"));
        echo $blocked;
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

    function get_formatted_microtime() {
        list($usec, $sec) = explode(' ', microtime());
        return $usec + $sec;
    }

}

?>
