<?php
/* NP_SiteList plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, based on work by Wouter Demuynck
 * http://www.iai.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 */

/*
	Version history:
	- 1.4.0:
        * handle situation where no url is submitted (just http://, or even null)
		* fixes bug in Admin page where non-admin could see delete all link for suspended sites.
		Could not run action, so not major bug.
		* redo conditional in install() for restoring options. Some users had problems with it.
        * fix bug where submit form action was to web root, not nucleus root.
        * add use of spamcheck API to reduce spam
        * add ability to use NP_Captcha
        * add ability to restrict submission to members.
	- 1.3.3: (2006-10-21)
        * fixes bug in skinvar concerning sort order when not random
		* adds a stringStripTags() function if it doesn't exist (pre Nucleus 3.22)
	- 1.3.2: (2006-08-29)
        * fixes a bug where first exempt site does not show up in admin interface
		* adds a ticket system to discourage direct submissions without loading the
		submission form (anti-spam). Just copy new files over existing 1.2 or 1.3 installs. See
		included help file for more info on upgrading from versions previous to 1.2
	- 1.3.1: (2006-08-17)
		* fixes minor bug in how Verify This Page action works when exempted sites
		are present. Just copy new files over existing 1.2 or 1.3 installs. See
		included help file for more info on upgrading from versions previous to 1.2
	- 1.3: (2006-06-03)
		* security improvements in how user input is handled and more careful about
		how includes are done.
		* Continued code cleanup.
		* Added execution timing function to time set processing functions.
		* used set_time_limit(0) to override max_execution_time setting in php.ini
		to allow verifying of large site sets. will only work when safe_mode is off.
		* Fixed bug that was verifying exempted sites when using verifychecked action.
		* Use of ob_flush() and flush() to update output to browser after each site
		in a set is verified.
		* Set mbstring.func_overload="2" to disable gzip encoding in the HTTP_REQUEST
		objects (in pear library) Also disabled gzip encoding in Request.php
		(pear/HTTP). This seems to solve a problem for certain sites that hang
		during verification and cause process to fail.
		* Internationalized the plugin. Only english file available.
		* Made to work for all PHP versions >= 4.0.6
		* no uninstall/reinstall needed for upgrade from version 1.2
	- 1.2: (2006-05-17)
		* added code to save plugin options during uninstall if user sets option
		to not delete sitelist data on uninstall.
		* added parameter to form type of skinvar to allow overriding input box size.
		* Added page size option for handling large lists in the admin area.
		* Some other cleaning of admin area presentation.
		* Requires uninstall and reinstall to upgrade.
	- 1.1 (2006-05-05): No Public Release.All included in 1.2.
		* added support for sitelist browser.
		* now verification checks for existence of frame buster code and sets a
		table field	that will exclude it from the SiteList Browser sets.
		* Requires uninstall and reinstall.
		* some minor bug fixes to the sleepsec function.
	- 1.0.1 (2006-04-28):
		* made so to use &lt;?php instead of &lt;? for better compatibility with
		all php installations.
	- 1.0 (2006-04-07): extensive modifications to add site verification
		among the new features added are the following
		* added preg-based site verification
		* added admin area to manage links
		* added 'suspended' status and extended db table
		* added admin link edit feature
		* added option to save data table on uninstall
		* no longer require sites-thanks.php
		* sites added by admin are auto-approved
		* added skinvar parameters to control how lists look
		  can now get # of approved sites, limit # shown,
		  spcify html tag to enclose elements, and turn off
		  the management links for site admins from displayed
		  SiteList
		* see the help.html file for more information on use
		  of the plugin.
	- 0.1 (2002-08-16): initial version
*/

// need some pear libraries for verification
// make sure nobody messing with DIR_PLUGINS
checkVars(array('$DIR_PLUGINS'));
// if php version < 4.3.0, can't use set_include_path
if (function_exists('set_include_path')) {
	set_include_path($DIR_PLUGINS . 'sitelist/pear' . PATH_SEPARATOR . get_include_path());
}
else {
	$newpath = $DIR_PLUGINS . 'sitelist/pear'. PATH_SEPARATOR . ini_get('include_path');
	ini_set('include_path', $newpath);
}

// plugin needs to work on Nucleus versions &=2.0 as well
if (!function_exists('sql_table'))
{
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}
// for nucleus prior to 3.22
if (!function_exists('stringStripTags'))
{
	function stringStripTags ($string) {
		$string = preg_replace("/<del[^>]*>.+<\/del[^>]*>/isU", '', $string);
		$string = preg_replace("/<script[^>]*>.+<\/script[^>]*>/isU", '', $string);
		$string = preg_replace("/<style[^>]*>.+<\/style[^>]*>/isU", '', $string);
		$string = str_replace('>', '> ', $string);
		$string = str_replace('<', ' <', $string);
		$string = strip_tags($string);

		$string = preg_replace("/\s+/", " ", $string);
		$string = trim($string);
		return $string;
	}
}

class NP_SiteList extends NucleusPlugin {

//Class-wide variables
	// name of database table without prefix
	var $tablename = 'plug_sitelist';
	var $maxsusp = 5; // max number of suspends before site deleted
	var $maxdesc = 48; // max length of a site description
	var $sleepsec = 0; // time in seconds processing will pause for every 10 sites when verifying all checked, unchecked or suspended
	var $allowedProtocols = array("http","https"); // protocols that will be allowed in sitelist links
	var $a_blockedExtensions = array(".exe",".bat",".vbs"); // page or domain extensions that can not be linked to. Do not put .com here

/* These will affect verifyURL() method, and control how sites are fetched for
*  verification. Only change these parameters if needed and you know what they do.
*  All parameters should be set to null (i.e. $phost = null;) to disable the
*  feature. Timed out sites verify as false, as will all sites if proxy host or
*  port are incorrect.
*/
		var $phost = null; // (null) string - FQDN or IP of proxy
		var $pport = null; // (null) string - tcp port of proxy, eg 8080, 8000, 80
		var $tout = 8; // (8) number in seconds to wait for connect
		var $rtout = array(8,500); // (array(8,500) number - in seconds and milliseconds to wait for connect
		var $allowredir = true; // (true) boolean - follow redirects when fetcing
		var $maxredirs = 3; // (3) integer - max # of redirects to follow
// end of class-wide variables

	function getName() {	return 'Site List'; 	}
	function getAuthor()  { return 'Wouter Demuynck, Frank Truscott'; 	}
	function getURL() { return 'http://nucleuscms.org/'; }
	function getVersion() {	return '1.4.0'; }
	function getDescription() {
		$nucleusmin = "320";
		if (getNucleusVersion() < $nucleusmin)
			$nucleuswarning = "***This plugin is not supported on this version of NucleusCMS. Please upgrade to a version higher than ".($nucleusmin / 100).".***";
		$phpmin = "4.0.6";
		if (!function_exists('version_compare'))
			$phpwarning = "***Your PHP version ".PHP_VERSION." may be less than the required $phpmin.***";
		elseif (version_compare(PHP_VERSION, $phpmin, "<"))
			$phpwarning = "***Your PHP version ".PHP_VERSION." is less than the required $phpmin.***";
		return "$nucleuswarning $phpwarning List can be shown using &lt;%SiteList%&gt;, Users can add links (&lt;%SiteList(form)%&gt;) which appear on the list after approval by the site admin (site admin can also delete sites from the list).";
	}

	function supportsFeature($what)	{
		switch($what) {
		case 'SqlTablePrefix':
			return 1;
		case 'HelpPage':
			return 1;
		default:
			return 0;
		}
	}

	function getEventList() { return array('QuickMenu'); }

	function getTableList() { return array(sql_table($this->tablename)); }

	function install() {
		sql_query("CREATE TABLE IF NOT EXISTS ".sql_table($this->tablename)." (url varchar(255) NOT NULL, title varchar(128) NOT NULL, alt varchar(128), checked tinyint(2) NOT NULL default '0', suspended tinyint(2) not null default '0', PRIMARY KEY(url)) TYPE=MyISAM;");

		$hassusp = false;
		$query = sql_query("SHOW COLUMNS FROM ".sql_table($this->tablename));
		while ($column = mysql_fetch_assoc($query)) {
	  		if ($column['Field'] == 'suspended') $hassusp = true;
		}
		if (!$hassusp) {
	  		sql_query("ALTER TABLE ".sql_table($this->tablename)." ADD suspended tinyint(2) NOT NULL default '0' AFTER checked");
	  		sql_query("UPDATE ".sql_table($this->tablename)." SET suspended = '0'");
		}

		$hasbrow = false;
			$query = sql_query("SHOW COLUMNS FROM ".sql_table($this->tablename));
			while ($column = mysql_fetch_assoc($query)) {
	  		if ($column['Field'] == 'browser') $hasbrow = true;
			}
		if (!$hasbrow) {
	  		sql_query("ALTER TABLE ".sql_table($this->tablename)." ADD browser tinyint(2) NOT NULL default '1' AFTER suspended");
	  		sql_query("UPDATE ".sql_table($this->tablename)." SET browser = '1'");
			}

		$this->createOption('quickmenu', 'Show Admin Area in quick menu?', 'yesno', 'yes');
		$this->createOption('del_uninstall', 'Delete SiteList data table on uninstall?', 'yesno','no');
		$this->createOption('def_nshow', 'Default number of sites to show in skinvar. (Can be overridden by skinvar parameters):', 'text', '20');
		$this->createOption('def_litag', 'Default html tag to enclose site links. e.g li, dd, br. (Can be overridden by skinvar parameters):', 'text', 'li');
		$this->createOption('def_sman', 'Default setting for show management links in skinvar. (Can be overridden by skinvar parameters):', 'yesno', 'no');
		$this->createOption('Mail','Notify on new additions','yesno','no');
		$this->createOption('MailTo','Send notifications to this address:','text','');
		$this->createOption('MailFrom','Send notifications from this address:','text','');
		$this->createOption('Cond01','Pregex condition (or simple string) to verify against (blank disables verification):','text','');
		$this->createOption('Cond02','Pregex condition (or simple string) to verify against (blank is OK):','text','');
		$this->createOption('LogicOp', 'Logic Operator to apply on conditions','select', 'OR', 'OR|OR|AND|AND|AND!|AND!|!AND!|!AND!|OR!|OR!|!OR!|!OR!');
		$this->createOption('AutoVerify','Apply verification to submitted URLs?','yesno','no');
		$this->createOption('ThanksText','Thank You Text (including any html tags) to display above add form when user submits a site.','textarea','<p style="color:#ff2222;background-color:#eeeeee;">Thanks for your submission. Your site will be reviewed and added to the list as soon as possible.</p>');
		$this->createOption('PageSize','Number of sites to show on single page in SiteList Admin Area','select','All','All|All|10|10|25|25|50|50|100|100|500|500');
        $this->createOption('spamcheck', 'Enable use of Spamcheck API?', 'yesno', 'no');
        $this->createOption('captcha', 'Enable use of NP_Captcha? (NP_Captcha must be installed.)', 'yesno', 'no');
        $this->createOption('members_only', 'Restrict site submission to only members?', 'yesno', 'no');

		$ot_result = sql_query("SHOW TABLES LIKE '%".sql_table($this_tablename.'_options')."%'");
		if ($ot_result) {
			$so_query = "SELECT * FROM ".sql_table($this->tablename.'_options');
			$savedopt = mysql_fetch_object(mysql_query($so_query));
			$this->setOption('quickmenu',$savedopt->quickmenu);
			$this->setOption('del_uninstall',$savedopt->del_uninstall);
			$this->setOption('def_nshow',$savedopt->def_nshow);
			$this->setOption('def_litag',$savedopt->def_litag);
			$this->setOption('def_sman',$savedopt->def_sman);
			$this->setOption('Mail',$savedopt->Mail);
			$this->setOption('MailTo',$savedopt->MailTo);
			$this->setOption('MailFrom',$savedopt->MailFrom);
			$this->setOption('Cond01',$savedopt->Cond01);
			$this->setOption('Cond02',$savedopt->Cond02);
			$this->setOption('LogicOp',$savedopt->LogicOp);
			$this->setOption('AutoVerify',$savedopt->AutoVerify);
			$this->setOption('ThanksText',$savedopt->ThanksText);
			$this->setOption('PageSize',$savedopt->PageSize);
            $this->setOption('spamcheck',$savedopt->spamcheck);
            $this->setOption('captcha',$savedopt->captcha);
            $this->setOption('members_only',$savedopt->members_only);
			sql_query('DROP TABLE IF EXISTS '.sql_table($this->tablename.'_options'));
		}
	}

	function unInstall() {
		if ($this->getOption('del_uninstall') == 'yes')	{
			sql_query('DROP TABLE '.sql_table($this->tablename));
			sql_query('DROP TABLE IF EXISTS '.sql_table($this->tablename.'_options'));
		}
		else {
			//save options
			sql_query("CREATE TABLE IF NOT EXISTS ".sql_table($this->tablename.'_options').
				" (quickmenu varchar(128) NOT NULL,".
				" del_uninstall varchar(128) NOT NULL,".
				" def_nshow varchar(128) NOT NULL,".
				" def_litag varchar(128) NOT NULL,".
				" def_sman varchar(128) NOT NULL,".
				" Mail varchar(128) NOT NULL,".
				" MailTo varchar(256),".
				" MailFrom varchar(256),".
				" Cond01 varchar(256),".
				" Cond02 varchar(256),".
				" LogicOp varchar(128) NOT NULL,".
				" AutoVerify varchar(128) NOT NULL,".
				" ThanksText varchar(256),".
				" PageSize varchar(256),".
                " spamcheck varchar(128) NOT NULL,".
                " captcha varchar(128) NOT NULL,".
                " members_only varchar(128) NOT NULL".
				" ) TYPE=MyISAM;");

			sql_query("INSERT INTO ".sql_table($this->tablename.'_options')
				." (quickmenu, del_uninstall, def_nshow, def_litag, def_sman, Mail, MailTo, MailFrom, Cond01, Cond02, LogicOp, Autoverify, ThanksText, PageSize, spamcheck, captcha, members_only)"
				." VALUES ('".addslashes($this->getOption('quickmenu'))."'"
							 .", '".addslashes($this->getOption('del_uninstall'))."'"
							 .", '".addslashes($this->getOption('def_nshow'))."'"
							 .", '".addslashes($this->getOption('def_litag'))."'"
							 .", '".addslashes($this->getOption('def_sman'))."'"
							 .", '".addslashes($this->getOption('Mail'))."'"
							 .", '".addslashes($this->getOption('MailTo'))."'"
							 .", '".addslashes($this->getOption('MailFrom'))."'"
							 .", '".addslashes($this->getOption('Cond01'))."'"
							 .", '".addslashes($this->getOption('Cond02'))."'"
							 .", '".addslashes($this->getOption('LogicOp'))."'"
							 .", '".addslashes($this->getOption('AutoVerify'))."'"
							 .", '".addslashes($this->getOption('ThanksText'))."'"
							 .", '".addslashes($this->getOption('PageSize'))."'"
                             .", '".addslashes($this->getOption('spamcheck'))."'"
                             .", '".addslashes($this->getOption('captcha'))."'"
                             .", '".addslashes($this->getOption('members_only'))."')"
				  );
		}
	}

	function init() {
		// include language file for this plugin
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory().$language.'.php'))
			include_once($this->getDirectory().$language.'.php');
		else
			include_once($this->getDirectory().'english.php');
	}

	function hasAdminArea() { return 1; }

	function event_QuickMenu(&$data) {
		// only show when option enabled
		if ($this->getOption('quickmenu') != 'yes') return;
		global $member;
		if (!($member->isLoggedIn())) return;
		array_push($data['options'],
	  		array('title' => 'SiteList',
			'url' => $this->getAdminURL(),
			'tooltip' => 'Manage Sites List'));
  	}

	function doSkinVar($skinType, $what = 'list',$nshow = '',$sort = 'random',$litag = '',$sman = '') {
		global $member;

		if ($what == 'form') {
			$this->showAddForm($nshow);
			return;
		}

		if ($nshow == NULL) $nshow = $this->getOption('def_nshow');
		if ($litag == NULL) $litag = $this->getOption('def_litag');
		if ($sman == NULL) $sman = $this->getOption('def_sman');

		if ($nshow <= 0) $limit = "";
		else $limit = " LIMIT ".$nshow;
		$slitag = '<'.$litag.'>';
		$elitag = '</'.$litag.'>';

		if ($what == 'count') {
			$csites = $this->getChecked();
			echo mysql_num_rows($csites);
			return;
		}

		$admin = $member->isAdmin();

		$sort = strtolower($sort);
		switch ($sort) {
		case "random":
			$sord = 'ASC,RAND()';
			break;
		case "asc":
			$sord = 'ASC';
			break;
		case "desc":
			$sord = 'DESC';
			break;
		default:
			$sord = 'ASC,RAND()';
		}
		if (!$admin || $sman == 'no')
			if ($sord == 'ASC,RAND()') {
				$checked = ' WHERE checked >= 1 ORDER BY checked '.$sord.$limit;
			}
			else {
				$checked = ' WHERE checked >= 1 ORDER BY title '.$sord.$limit;
			}
		else {
			if ($sord == 'ASC,RAND()') {
				$checked = ' ORDER BY checked ASC,RAND()'.$limit;
			}
			else {
				$checked = ' ORDER BY checked ASC, title '.$sord.$limit;
			}
		}

		$query = 'SELECT * FROM '.sql_table($this->tablename).$checked;

		$sites = sql_query($query);

		if ($admin) echo '<a href="'.$this->getAdminURL().'">['._SITELIST_SKIN_MANAGE_LINK.']</a><br />'."\n";

		while ($site = mysql_fetch_object($sites)) {
			$site->ourl = $site->url;
			$site->title = $this->prepDesc($site->title, $site->url);
			$site->url = $this->prepURL($site->url);
			echo $slitag.'<a href="'.htmlentities($site->url).'" ';
			if ($site->alt)
				echo 'title="'.htmlentities($site->alt).'" ';
			echo '>'.$site->title.'</a>'."\n";
			if ($sman == 'yes') {
			   if ($admin) {
				echo ' <a href="action.php?action=plugin&amp;name=SiteList&amp;type=deleteurl&amp;url='.htmlentities($site->ourl).'">['._SITELIST_DELETE."]</a>\n";
				echo ' <a href="action.php?action=plugin&amp;name=SiteList&amp;type=verifyurl&amp;url='.htmlentities($site->url).'&amp;nsusp='.$site->suspended.'">['._SITELIST_VERIFY."]</a>\n";
				if (!$site->suspended) echo ' <a href="action.php?action=plugin&amp;name=SiteList&amp;type=suspendurl&amp;url='.htmlentities($site->ourl).'&amp;nsusp='.$site->suspended.'">['._SITELIST_SUSPEND."]</a>\n";
				if (!$site->checked)
					echo ' <a href="action.php?action=plugin&amp;name=SiteList&amp;type=checked&amp;url='.htmlentities($site->url).'">['._SITELIST_APPROVE."]</a>\n";
			   }
			}
			echo $elitag."\n";
		}
	}

	function doAction($type) {
		global $member;

// Get input variables and set some parameters
		$showlog = 0;
		$autoverify =  trim(strtolower($this->getOption('AutoVerify')));
		$destURL = serverVar('HTTP_REFERER');
		$destURL = str_replace(array("?thanks=1","&thanks=1","&list=1"),array("","","&list=0"),$destURL);
		$offset = requestVar('offset');
		if ($offset == NULL || $offset <= 0) $offset = 1;
		$nshow = requestVar('nshow');
		if ($nshow == NULL || $nshow <= 0) $nshow = 999999;
		$url = trim(requestVar('url'));
		$desc = trim(requestVar('desc'));
		$alt = trim(requestVar('alt'));
		$ourl = trim(requestVar('ourl'));
		$odesc = trim(requestVar('odesc'));
		$oalt = trim(requestVar('oalt'));
		$nsuspraw = requestVar('nsusp');
		if (!is_numeric($nsuspraw)) $nsusp = 0;
		else $nsusp = intval($nsuspraw);

// determine action and perform it
		switch($type) {
			case 'checked':
                if ($url == '' || $url == 'http://') doError(_SITELIST_ERR_003);
				if (!$member->isAdmin())
					return _SITELIST_ACTION_DENY;
				$this->setChecked($url);
				//$destURL = 'sites.php';
				break;
			case 'addurl':
                if ($url == '' || $url == 'http://') doError(_SITELIST_ERR_003);
				if (!$member->isAdmin()) {
                    if ($this->getOption('members_only') == 'yes' && !$member->isLoggedIn()) return _SITELIST_ACTION_DENY;
					if (!$this->checkTicket()) return _SITELIST_ACTION_DENY;
                    if ($this->getOption('spamcheck') == 'yes') {
                        global $manager;
                        $spamcheck = array (
                                   'type' => 'comment',
                                   'body' => $url . ' ' . $desc . ' ' . $ourl. ' ' . $odesc,
                                   'url' => $url,
                                   'data' => $url . ' ' . $desc . ' ' . $ourl. ' ' . $odesc,
                                   'live' => true,
                                   'return' => true
                                 );
                        $manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
                        if (isset($spamcheck['result']) && $spamcheck['result'] == true) {
                            /* this is spam */
                            return _SITELIST_ACTION_DENY;
                        }
                    }
                    if ($this->getOption('captcha') == 'yes' && !$member->isLoggedIn()) {
                        global $manager;
                        $key = postVar('ver_key');
                        $sol = postVar('ver_sol');
                        if ($manager->pluginInstalled('NP_Captcha')) {
                            $npcaptcha =& $manager->getPlugin('NP_Captcha');
                        }
                        if (isset($npcaptcha)) {
                            if (!$npcaptcha->check($key, $sol)) {
                                return _SITELIST_ACTION_DENY;
                            }
                        }
                    }
				}
				if (!$this->addURL($url, $desc, $alt)) break;
				if ($member->isAdmin())
					$this->setChecked($url);
				else {
// this following line will automatically verify sites upon submittal
// for non-admin users. Sites that fail authentication are left as
// unchecked sites, and will need admin review.
					if($autoverify == 'yes') $this->verifyURL($url,-1);
					if (strpos($destURL,'?') === false)
						$destURL = $destURL.'?thanks=1';
					else
						$destURL = $destURL.'&thanks=1';
					//$destURL = 'sites-thanks.php';
				}
				break;
			case 'modurl':
                if ($url == '' || $url == 'http://') doError(_SITELIST_ERR_003);
				if (!$member->isAdmin())
					return _SITELIST_ACTION_DENY;
				if (!$this->modURL($url, $desc, $ourl, $odesc, $alt, $oalt)) break;
				if ($member->isAdmin()) {
					$this->setChecked($url);
					$destURL = $this->getAdminURL().'?showlist=addurl&amp;safe=true';
				}
				//$destURL = 'sites-thanks.php';
				break;
			case 'deleteurl':
				if (!$member->isAdmin())
					return _SITELIST_ACTION_DENY;
				$this->deleteURL($url);
				//$destURL = 'sites.php';
				break;
			case 'exempturl':
                if ($url == '' || $url == 'http://') doError(_SITELIST_ERR_003);
				if (!$member->isAdmin())
					return _SITELIST_ACTION_DENY;
				$this->setExempt($url);
				//$destURL = 'sites.php';
				break;
			case 'suspendurl':
                if ($url == '' || $url == 'http://') doError(_SITELIST_ERR_003);
				if (!$member->isAdmin())
					return _SITELIST_ACTION_DENY;
				$this->setSuspended($url,$nsusp);
				//$destURL = 'sites.php';
				break;
			case 'verifyurl':
                if ($url == '' || $url == 'http://') doError(_SITELIST_ERR_003);
				if (!$member->isAdmin())
					return _SITELIST_ACTION_DENY;
				$this->verifyURL($url,$nsusp);
				//$destURL = 'sites.php';
				break;
			case 'verifychecked':
				if (!$member->isAdmin())
					return _SITELIST_ACTION_DENY;
				$showlog = 1;
				$startset = $this->get_formatted_microtime();
				echo '<html><body><h2>'._SITELIST_ACTION_VERIFY_HEAD."</h2>\n";
				echo '<a href="'.$destURL.'">['._SITELIST_CLOSE."]</a><br /><br />\n";
				$sites = $this->getChecked(0,$offset,$nshow);
				$this->_verifySiteSet($sites);
				$endset = $this->get_formatted_microtime();
				echo '<br /><b>'._SITELIST_ACTION_VERIFY_EXEC.' '.round($endset - $startset, 6).' '._SITELIST_SECONDS.'</b>';
				//$destURL = 'sites.php';
				break;
			case 'verifyunchecked':
				if (!$member->isAdmin())
					return _SITELIST_ACTION_DENY;
				$showlog = 1;
				$startset = $this->get_formatted_microtime();
				echo '<html><body><h2>'._SITELIST_ACTION_VERIFY_HEAD."</h2>\n";
				echo '<a href="'.$destURL.'">['._SITELIST_CLOSE."]</a><br /><br />\n";
				$sites = $this->getUnChecked(0,$offset,$nshow);
				$this->_verifySiteSet($sites);
				$endset = $this->get_formatted_microtime();
				echo '<br /><b>'._SITELIST_ACTION_VERIFY_EXEC.' '.round($endset - $startset, 6).' '._SITELIST_SECONDS.'</b>';
				//$destURL = 'sites.php';
				break;
			case 'verifysuspended':
				if (!$member->isAdmin())
					return _SITELIST_ACTION_DENY;
				$showlog = 1;
				$startset = $this->get_formatted_microtime();
				echo '<html><body><h2>'._SITELIST_ACTION_VERIFY_HEAD."</h2>\n";
				echo '<a href="'.$destURL.'">['._SITELIST_CLOSE."]</a><br /><br />\n";
				$sites = $this->getSuspended(0,$offset,$nshow);
				$this->_verifySiteSet($sites);
				$endset = $this->get_formatted_microtime();
				echo '<br /><b>'._SITELIST_ACTION_VERIFY_EXEC.' '.round($endset - $startset, 6).' '._SITELIST_SECONDS.'</b>';
				//$destURL = 'sites.php';
				break;
			case 'deletesuspended':
				if (!$member->isAdmin())
					return _SITELIST_ACTION_DENY;
				$showlog = 1;
				$startset = $this->get_formatted_microtime();
				echo '<html><body><h2>'._SITELIST_ACTION_DELETE_HEAD."</h2>\n";
				echo '<a href="'.$destURL.'">['._SITELIST_CLOSE."]</a><br /><br />\n";
				$sites = $this->getSuspended(0,$offset,$nshow);
				while ($site = mysql_fetch_object($sites)) {
					$scurl = $site->url;
					$this->deleteURL($scurl);
					echo $scurl.':-:'.$site->title.':-:'.$site->alt.':-:0:-:'.$site->suspended.':-:'.$site->browser.'<br />'."\n";
				}
				$endset = $this->get_formatted_microtime();
				echo '<br /><b>'._SITELIST_ACTION_DELETE_EXEC.' '.round($endset - $startset, 6).' '._SITELIST_SECONDS.'</b>';
				//$destURL = 'sites.php';
				break;
			default:
				return _SITELIST_ACTION_UNKNOWN;
		}

		if ($showlog) {
			echo '<br /> <a href="'.$destURL.'">['._SITELIST_CLOSE."]</a>\n";
			echo '</body></html>'."\n";
		}
		else header('Location: ' . $destURL);
		exit;
	}

	/*
	 * Helper methods
	 */

	function showAddForm($nshow = '',$siteurl = 'http://',$sitedesc = 'Site Description',$type = 'addurl') {
        global $CONF, $member;
        $imgHTML = '';
        $key = '';

        if ($this->getOption('members_only') == 'yes' && !$member->isLoggedIn()) {
            // do nothing
        }
        else {
            if ($this->getOption('captcha') == 'yes') {
                global $manager;
                if ($manager->pluginInstalled('NP_Captcha')) {
                    $npcaptcha =& $manager->getPlugin('NP_Captcha');
                }
                if (isset($npcaptcha) && !$member->isLoggedIn()) {
                    $key = $npcaptcha->generateKey();
                    $imgHtml = $npcaptcha->generateImgHtml($key,160,60);
                }
            }
            if (requestVar('thanks') == '1')
                echo $this->getOption('ThanksText');
            ?>
            <div class="sitelistform">
            <form method="post" action="<?php echo $CONF['ActionURL']; ?>">
                <input type="hidden" name="action" value="plugin" />
                <input type="hidden" name="name" value="SiteList" />
                <input type="hidden" name="type" value="<?php echo $type; ?>" />
                <input type="hidden" name="ourl" value="<?php echo $siteurl; ?>" />
                <input type="hidden" name="odesc" value="<?php echo $sitedesc; ?>" />
                <?php
                    $this->addTicketHidden();
                    echo '<input type="hidden" name="ver_key" value="'.$key.'" />';
                ?>
                <table><tr>
                    <td><label for="siteurl"><?php echo _SITELIST_ADMIN_AU_URL; ?></label></td>
                    <td><input type="text" name="url" id="siteurl" value="<?php echo $siteurl; ?>" size="<?php echo $nshow; ?>" class="formfield" /></td>
                </tr><tr>
                    <td><label for="sitedesc"><?php echo _SITELIST_ADMIN_AU_TITLE; ?></label></td>
                    <td><input type="text" name="desc" id="sitedesc" value="<?php echo $sitedesc; ?>" size="<?php echo $nshow; ?>" class="formfield"/></td>
                </tr>
                <?php
                    if ($key) {
                        echo "<tr>\n";
                        echo '<td colspan="2"><label for="nucleus_cf_verif">'.$imgHtml.'</label>' . "\n";
                        echo '<br />' . "\n";
                        echo '<label for="nucleus_cf_verif">'._SITELIST_ADMIN_AU_CAPTCHA.'</label><input name="ver_sol" size="6" maxlength="6" value="" class="formfield" id="nucleus_cf_verif" />' . "\n";
                        echo "</td></tr>\n";
                    }
                ?>
                <tr>
                    <td></td>
                    <td><input type="submit" value="<?php echo _SITELIST_ADMIN_AU_SUBMIT; ?>" class="formbutton" /></td>
                </tr></table>
            </form></div>
            <?php
        }
	}

	function addURL(&$url, $desc, $alt = '') {
	global $member;

	$desc = $this->prepDesc($desc,$url);
	$url = $this->prepURL($url);
	if (substr($url, 0, 5) == 'Error') doError($url);
	sql_query("INSERT INTO " .sql_table($this->tablename). " (url, title) VALUES ('".addslashes($url)."','".addslashes($desc)."')");

		if ($this->getOption('Mail') == 'yes' && !$member->isAdmin())
			@mail($this->getOption('MailTo'),'New site: ' . $url,'A new site was added (SiteList plugin)','From:'.$this->getOption('MailFrom'));
	return 1;
	}

	function modURL(&$url, $desc, $ourl, $odesc, $alt, $oalt) {
		$success = 0;
		$desc = $this->prepDesc($desc,$url);
		$purl = $this->prepURL($url);
		if ($purl == $ourl && $desc != $odesc) {
			sql_query("UPDATE " .sql_table($this->tablename). " SET title='".$desc."' WHERE url='".addslashes($url)."'");
			$success += 1;
		}
		else {
			$this->deleteURL($ourl);
			if ($this->addURL($url,$desc,$alt)) $success += 2;
		}
		return $success;
	}

	function deleteURL($url) {
		sql_query("DELETE FROM " .sql_table($this->tablename). " WHERE url='".addslashes($url)."'");
	}

	function setChecked($url) {
		sql_query("UPDATE " .sql_table($this->tablename). " SET checked=1 WHERE url='".addslashes($url)."'");
			sql_query("UPDATE " .sql_table($this->tablename). " SET suspended=0 WHERE url='".addslashes($url)."'");
	}

	function setExempt($url) {
		sql_query("UPDATE " .sql_table($this->tablename). " SET checked=2 WHERE url='".addslashes($url)."'");
			sql_query("UPDATE " .sql_table($this->tablename). " SET suspended=0 WHERE url='".addslashes($url)."'");
	}

	function setBrowser($url,$value) {
		sql_query("UPDATE " .sql_table($this->tablename). " SET browser=".$value." WHERE url='".addslashes($url)."'");
	}

	function setSuspended($url,$nsusp = 0) {
		sql_query("UPDATE " .sql_table($this->tablename). " SET checked=0 WHERE url='".addslashes($url)."'");
		$nsusp += 1;
		if ($nsusp >= $this->maxsusp)
		{  $this->deleteURL($url); }
		else
		{  sql_query("UPDATE " .sql_table($this->tablename). " SET suspended=".$nsusp." WHERE url='".addslashes($url)."'"); }
	}

	function getSuspended($sticky = 0,$offset = 1,$numrows = 999999) {
		if (intval($sticky) > 0) $oper = '='.intval($sticky);
		else $oper = '>0';
		$offset = $offset - 1;
		$query = 'SELECT * FROM '.sql_table($this->tablename).' WHERE suspended'.$oper.' ORDER BY suspended ASC, title ASC LIMIT '.$offset.','.$numrows;
		$sites = sql_query($query);
		return $sites;
	}

	function getChecked($sticky = 0,$offset = 1,$numrows = 999999) {
		if (intval($sticky) > 0) $oper = '='.intval($sticky);
		else $oper = '>0';
        $offset = $offset - 1;
		$query = 'SELECT * FROM '.sql_table($this->tablename).' WHERE checked'.$oper.' ORDER BY checked DESC, title ASC LIMIT '.$offset.','.$numrows;
		$sites = sql_query($query);
		return $sites;
	}

	function getUnChecked($sticky = 0,$offset = 1,$numrows = 999999) {
		$offset = $offset - 1;
		$query = 'SELECT * FROM '.sql_table($this->tablename).' WHERE checked<1 and suspended=0 ORDER BY title ASC LIMIT '.$offset.','.$numrows;
		$sites = sql_query($query);
		return $sites;
	}

	function getSearchResults($searchstring = '',$offset = 1,$numrows = 999999) {
		$offset = $offset - 1;
		$searchstring = preg_replace('|[^a-z0-9]|i', ' ', $searchstring);
		$searchstring = str_replace(' ','%',$searchstring);
		$query = 'SELECT * FROM '.sql_table($this->tablename).' WHERE concat(url,title) LIKE "%'.$searchstring.'%" OR concat(title,url) LIKE "%'.$searchstring.'%" ORDER BY title ASC LIMIT '.$offset.','.$numrows;
		$sites = sql_query($query);
		return $sites;
	}

	function verifyURL($url,$nsusp = 0) {
		require_once 'HTTP/Request.php';
		$reqparams = array (
			'proxy_host' => $this->phost,
			'proxy_port' => $this->pport,
			'timeout' => $this->tout,
			'readTimeout' => $this->rtout,
			'allowRedirects' => $this->allowredir,
			'maxRedirects' => $this->maxredirs);

		$req = &new HTTP_Request($url,$reqparams);
		$req->sendRequest();
		$verify = 1;
		if (intval($req->getResponseCode()) < 199 || intval($req->getResponseCode()) > 399) $verify = 0;
		else {
			$webtext = $req->getResponseBody();
			if (!$webtext || strlen($webtext) < 25) $verify = 0;
		}
		if ($verify) {
		$hasframebuster = preg_match('/top.location/i',$webtext);
		if ($hasframebuster) $this->setBrowser($url,0);
		$cond01 = $this->getOption('Cond01');
		$cond02 = $this->getOption('Cond02');
		if ($cond01 == NULL)
		{  return -1; }
		if ($cond02 == NULL)
		{  $cond02 = $cond01; }
// if basic string (no preg format) make into case insensitve search
			if ( $cond01{0} != "/" )
			{ $cond01 = "/".$cond01."/i"; }
			if ( $cond02{0} != "/" )
			{ $cond02 = "/".$cond02."/i"; }
		$hascond01 = preg_match($cond01,$webtext);
		$hascond02 = preg_match($cond02,$webtext);

		switch ($this->getOption('LogicOp')) {
		case "OR":
			$verify = ($hascond01 || $hascond02);
			break;
		case "AND":
			$verify = ($hascond01 && $hascond02);
			break;
		case "AND!":
			$verify = ($hascond01 && !$hascond02);
			break;
		case "!AND!":
			$verify = (!$hascond01 && !$hascond02);
			break;
		case "OR!":
			$verify = ($hascond01 || !$hascond02);
			break;
		case "!OR!":
			$verify = (!$hascond01 || !$hascond02);
			break;
		}
		} // end else for $webtext=false

		if ($verify) {
	   			$this->setChecked($url);
			return 1;
		}
	  		else {
	   			$this->setSuspended($url,$nsusp);
			return 0;
		}
	}

	function _verifySiteSet($sites) {
		// this disables gzip encoding of retrieved sites. Fixes bug.
		ini_set("mbstring.func_overload", "2");
		if (!ini_get('safe_mode')) set_time_limit(0);
		$tot = mysql_num_rows($sites);
		$i = 1;
		$m = 0;
		while ($site = mysql_fetch_object($sites)) {
			$start = $this->get_formatted_microtime();
			echo "$site->url : ";
			$this->_my_ob_flush();
			flush();
			if ($site->checked == 2) $verifies = 2;
			else $verifies = $this->verifyURL($site->url,$site->suspended);
			if ($verifies == -1) {
				echo _SITELIST_VSET_NOVERIFY;
				break;
			}
			$end = $this->get_formatted_microtime();
			$exectotal = round($end - $start, 6);
			if ($verifies == 2) echo strtoupper(_SITELIST_EXEMPT)." --- ";
			elseif ($verifies == 1) echo strtoupper(_SITELIST_PASS)." --- ";
			else echo strtoupper(_SITELIST_FAIL)." --- ";
			echo ($m * 10) + $i." of $tot --- "._SITELIST_COMPLETED." $exectotal "._SITELIST_SECONDS."<br />\n";
			$this->_my_ob_flush();
			flush();

			if ($i == 10 && $this->sleepsec > 0) {
				echo _SITELIST_VSET_AWAKE." $this->sleepsec "._SITELIST_SECONDS." ... <br />\n";
				$this->_my_ob_flush();
				flush();
				sleep($this->sleepsec);
				echo _SITELIST_VSET_AWAKE." ...<br />\n";
				$this->_my_ob_flush();
				flush();
				$i = 1;
				$m += 1;
			}
			$i += 1;
		}
	}

	function prepDesc($desc,$url) {
		$desc = stringStripTags($desc);
		$desc = trim(str_replace('Site Description','',$desc));
		if ($desc == '') $desc = trim(str_replace('http://','',$url));
		if (strlen($desc) > $this->maxdesc) $desc = substr($desc,0,$this->maxdesc);
		return $desc;
	}

	function prepURL($url) {
		$blockedExtensions = $this->_set_blockedExtensions($this->a_blockedExtensions);
		if (strpos($url,'://') === false) $url = 'http://'.$url;
		if (in_array(substr($url,-1,1),array('&','?'))) $url = substr($url,0,-1);
		if ( !in_array(substr($url, 0, intval(strpos($url,'://'))), $this->allowedProtocols) ) return "Error: 001 - "._SITELIST_ERR_001;
		$url = preg_replace($blockedExtensions, "<badext>", $url);
		if (strpos($url,'<badext>')) return "Error: 002 - "._SITELIST_ERR_002;
		$url = preg_replace('|[^a-z0-9-~+_.?#=&;,/:@%]|i', '', $url);
		return $url;
	}
	// get extensions ready as preg expression.
	// Could be done easier in PHP by passing $bext as reference, ie &$bext
	function _set_blockedExtensions($rawext) {
		$pregext = array();
		foreach ($rawext as $bext) {
			$bext = str_replace(".", "", trim($bext));
			$bext = "/[.]$bext([^a-z0-9-_.]|\$)/i";
			$pregext = array_merge($pregext, array($bext));
		}
		reset($pregext);
		return $pregext;
	}

	function get_formatted_microtime() {
		list($usec, $sec) = explode(' ', microtime());
		return $usec + $sec;
	}

	function _my_ob_flush() {
		if (function_exists('ob_flush')) ob_flush();
	}


	/*
		Ticket functions. These are used to make it impossible to simulate certain GET/POST
		requests. tickets are ip specific. Modified version of nucleus cms admin ticket code.
	*/

	var $currentRequestTicket = '';

	/**
	 * GET requests: Adds ticket to URL (URL should NOT be html-encoded!, ticket is added at the end)
	 */
	function addTicketToUrl($url)
	{
		$ticketCode = 'sticket=' . $this->_generateTicket();
		if (strstr($url, '?'))
			return $url . '&' . $ticketCode;
		else
			return $url . '?' . $ticketCode;
	}

	/**
	 * POST requests: Adds ticket as hidden formvar
	 */
	function addTicketHidden()
	{
		$ticket = $this->_generateTicket();

		echo '<input type="hidden" name="sticket" value="', htmlspecialchars($ticket), '" />';
	}

	/**
	 * Checks the ticket that was passed along with the current request
	 */
	function checkTicket()
	{
		global $member;

		// get ticket from request
		$ticket = requestVar('sticket');

		// no ticket -> don't allow
		if ($ticket == '')
			return false;

		// remove expired tickets first
		$this->_cleanUpExpiredTickets();

		// get remote IP (here stored as $memberid)
		$ipparts = explode('.', serverVar("REMOTE_ADDR"));
		$iptot = 1;
		foreach ($ipparts as $value) {
			if (intval($value) != 0) $iptot = $iptot * intval($value);
		}
		if ($iptot < 100000) $iptot += 100000;
		$memberId = $iptot;

		// check if ticket is a valid one
		$query = 'SELECT COUNT(*) as result FROM ' . sql_table('tickets') . ' WHERE member=' . intval($memberId). ' and ticket=\''.addslashes($ticket).'\'';
		if (quickQuery($query) == 1)
		{
			// [in the original implementation, the checked ticket was deleted. This would lead to invalid
			//  tickets when using the browsers back button and clicking another link/form
			//  leaving the keys in the database is not a real problem, since they're member-specific and
			//  only valid for a period of one hour
			// ]
			// sql_query('DELETE FROM '.sql_table('tickets').' WHERE member=' . intval($memberId). ' and ticket=\''.addslashes($ticket).'\'');
			return true;
		} else {
			// not a valid ticket
			return false;
		}

	}

	/**
	 * (internal method) Removes the expired tickets
	 */
	function _cleanUpExpiredTickets()
	{
		// remove tickets older than 1 hour
		$oldTime = time() - 60 * 60;
		$query = 'DELETE FROM ' . sql_table('tickets'). ' WHERE ctime < \'' . date('Y-m-d H:i:s',$oldTime) .'\'';
		sql_query($query);
	}

	/**
	 * (internal method) Generates/returns a ticket (one ticket per page request)
	 */
	function _generateTicket()
	{
		if ($this->currentRequestTicket == '')
		{
			// generate new ticket (only one ticket will be generated per page request)
			// and store in database
			global $member;
			// get remote IP (here stored as $memberid)
			$ipparts = explode('.', serverVar("REMOTE_ADDR"));
			$iptot = 1;
			foreach ($ipparts as $value) {
				if (intval($value) != 0) $iptot = $iptot * intval($value);
			}
			if ($iptot < 100000) $iptot += 100000;
			$memberId = $iptot;

			$ok = false;
			while (!$ok)
			{
				// generate a random token
				srand((double)microtime()*1000000);
				$ticket = md5(uniqid(rand(), true));

				// add in database as non-active
				$query = 'INSERT INTO ' . sql_table('tickets') . ' (ticket, member, ctime) ';
				$query .= 'VALUES (\'' . addslashes($ticket). '\', \'' . intval($memberId). '\', \'' . date('Y-m-d H:i:s',time()) . '\')';
				if (sql_query($query))
					$ok = true;
			}

			$this->currentRequestTicket = $ticket;
		}
		return $this->currentRequestTicket;
	}
}
?>
