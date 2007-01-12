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

Acknowledgement:
Version 2 was written by Frank Truscott based on the version 1 code by Tim Broddin
and modified by Edmund Hui. Much of the code in version 2 is new, but the core
structure relies on the original code. Tim Broddin originally listed
http://www.fuckhedz.com/ as the URL returned by the getURL() method. I have
changed the URL displayed, since version 2 is a considerable modification of the
original, and since I find that domain name offensive.

Distribution:
This plugin is distributed in a zip file containing the following files:
NP_Profile.php (this file)
profile\default.jpg (an image used as default when none specified by member)
profile\english.php (constants used to display plugin in English)
profile\example.txt (the Example Code displayed in plugin admin area)
profile\examplecss.txt (the example CSS code displayed in the plugin admin area)
profile\help.html (the plugin help page)
profile\help.jpg (icon used for link to help page from plugin admin area)
profile\index.php (the plugin admin area code)
profile\editprofile.css (a css file to set style of edit profile page, editprofile.php) new 2.1
profile\editprofile.php (a page to edit member data, has formatting options) new 2.1

Usage:
* Define/customize fields in plugin admin area
* In your skin (member,index,item,archive,archivelist) or template -- use:
	  &lt;%Profile(field)%&gt;
	See help file for more options.
* When on its own member page, a member can edit his profile

History:
  v1.0a - Initial release
  v1.1 - use sql_table(), add supportsFeature
  v2.00.02b - beta of v2
    * various bug fixes and code cleaning
	* Adds support for more skin vars (item,index,archive, archivelist, in addition to member)
	* Adds support for a template var
	* Moves custom field management to the admin gui, data stored in db tables, not plugin file
	* Adds ability to require a field or disable a field
  v2.01 - first release of version 2 adds the following to the beta version
	* improved the handling of files (cleaned code and added support for non-image extentions)
	* Internationalized the plugin (English only, so far, but hope to get translations for future versions)
	* Added a password validation function to check password length and complexity
	* Added support for list type (checkbox)
	* Added support for showas skinvar parameter to more field types, gives more display format options
	* Added support for show skinvar parameter for all field types (was just for file) to force display of data not form field
	* Limited display of the profile form to case where member requests it though a link (editlink)
	* Improved date formatting options
	* Improved redirection upon form submittal, including success messages.
  v2.02 - 2nd release of version 2 adds the following to the 2.01 version
	* Allows line breaks in textarea fields (not in nucleus notes field). OK
	* Allows textareas greater than 256 characters (adds torder field to plugin_profile table, plus other code changes)OK
		OK, max size is 3500 characters, rest truncated
	* Deletes member's data when member deleted. OK.
	* Deletes values from plugin_profile for deleted fields. OK.
	* Changes name of field for data in plugin_profile when field is renamed. OK.
	* Explicitly create tables with MyISAM engine for better performance, instead of the system's default engine.
		Converts existing tables to MyISAM if not already. OK
  v2.03 - 3rd release of version 2 adds the following to the 2.02 version
    * XHTML compliance in links (replaced alt attribute with  title attribute). Thanks, bakaelite!
  v2.04 - 4th release of version 2 adds the following to the 2.03 version
    * Forces chmod of uploaded files to 644. Thanks, wessite!
    * Allows additional protocols for type url (set in Options field of field def). semi-colon separated list.
    * Allows textarea to allow tags as decided in field def (Options field).string of angle-braketed tags to allow. Like &lt;b&gt;&lt;img&gt;.
    * fix bug in how short textarea values are handled in editting (was losing last two characters of field).
  v2.05 - 5th release of version 2 adds the following to the 2.04 version
	* adds support for comment template variable
  v2.1 -- 6th release of version 2 adds the following to the 2.05 version
	* adds knowledge of NP_Friends with privacylevel concept.
	* adds an editprofile page for editing profiles off the member details skin part.
	* Adds to special field types, submitbutton and editprofile.
	* Adds some field configuration settings:
		Default (set the default value of a choice field),
		Public (set whether field is viewable to all users despite privacylevel setting)
	* Adds getAvatar() method to make it easier for other plugins to retrieve the avatar
    * Adds ability to get the current logged in member's profile data using %ME% for fourth parameter of skinvar

To do:
* Offer some validation options for fields, i.e. isEmail, isURL, isList
* Make the Example Code in admin area dynamic based on enabled fields and field order (future parameter)

*/


class NP_Profile extends NucleusPlugin {

	var $default;
	var $nufields = array('mail','nick','realname','url','notes','password'); // fields stored in nucleus_member
	var $nutypes = array('date','dropdown','file','list','mail','number','password','radio','text','textarea','url'); // supported field types
	var $req_emp = array();
	var $showEmail = 0;
    var $allowedProtocols = array("http","https"); // protocols that will be allowed in sitelist links
	var $restrictView = 0;

	function getName() { return 'Profile Plugin'; }

	function getAuthor()  {	return 'Tim Broddin | Edmond Hui (admun) | Frank Truscott';	}

	function getURL()   { return 'http://www.iai.com/';	}

	function getVersion() {	return '2.1'; }

	function getDescription() {
		return 'Gives each member a customisable profile';
	}

	function getMinNucleusVersion() { return 322; }

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

	function getTableList() { return array(sql_table('plugin_profile'), sql_table('plugin_profile_fields'), sql_table('plugin_profile_types'), sql_table('plugin_profile_config')); }
	function getEventList() { return array('QuickMenu','PostDeleteMember'); }

	function install() {
		global $CONF;

// Need to make some options
		$this->createOption('quickmenu', _PROFILE_OPT_QUICKMENU, 'yesno', 'yes');
		$this->createOption('del_uninstall_data', _PROFILE_OPT_DEL_UNINSTALL_DATA, 'yesno','no');
		$this->createOption('del_uninstall_fields', _PROFILE_OPT_DEL_UNINSTALL_FIELDS, 'yesno','no');
		$this->createOption('req_emp_start', _PROFILE_OPT_REQ_EMP_START, 'text','<i>');
		$this->createOption('req_emp_end', _PROFILE_OPT_REQ_EMP_END, 'text','*</i>');
		$this->createOption('default_image', _PROFILE_OPT_DEFAULT_IMAGE, 'text',$CONF['PluginURL'].'profile/default.jpg');
		$this->createOption('email_public', _PROFILE_OPT_EMAIL_PUBLIC, 'select','1',_PROFILE_OPT_SELECT_ALL.'|2|'._PROFILE_OPT_SELECT_MEMBERS.'|1|'._PROFILE_OPT_SELECT_NOBODY.'|0');
		$this->createOption('pwd_min_length', _PROFILE_OPT_PWD_MIN_LENGTH, 'text','0');
		$this->createOption('pwd_complexity', _PROFILE_OPT_PWD_COMPLEXITY, 'select','0',_PROFILE_OPT_SELECT_OFF_COMP.'|0|'._PROFILE_OPT_SELECT_ONE_COMP.'|1|'._PROFILE_OPT_SELECT_TWO_COMP.'|2|'._PROFILE_OPT_SELECT_THREE_COMP.'|3|'._PROFILE_OPT_SELECT_FOUR_COMP.'|4');
		$this->createOption('CSS2URL',_PROFILE_OPTIONS_CSS2URL,'text',$this->getAdminURL()."editprofile.css");

// create needed tables
		sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plugin_profile').
					" ( `memberid` int(11),
					  `field` varchar(255),
					  `value` varchar(255),
					  `torder` tinyint(2) NOT NULL default '0',
					  KEY `member` (`memberid`),
					  KEY `field` (`field`)) ENGINE=MyISAM");

		sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plugin_profile_fields').
					" ( `fname` varchar(255),
					  `flabel` varchar(255),
					  `ftype` ENUM('date','dropdown','file','list','mail','number','password','radio','text','textarea','url'),
					  `required` tinyint(2) NOT NULL default '0',
					  `enabled` tinyint(2) NOT NULL default '1',
					  `flength` int(11) NOT NULL default '0',
					  `fsize` int(11) NOT NULL default '0',
					  `fformat` varchar(255),
					  `fwidth` int(11) NOT NULL default '0',
					  `fheight` int(11) NOT NULL default '0',
					  `ffilesize` int(11) NOT NULL default '0',
					  `ffiletype` varchar(255),
					  `foptions` text,
					  `fvalidate` varchar(255),
					  `forder` int(11) NOT NULL default '0',
                      `fdefault` varchar(255),
                      `fpublic` tinyint(2) NOT NULL default '0',
					  PRIMARY KEY (`fname`)) ENGINE=MyISAM");

		sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plugin_profile_types').
					" ( `type` ENUM('date','dropdown','file','list','mail','number','password','radio','text','textarea','url'),
					  `flength` int(11) NOT NULL default '0',
					  `fsize` int(11) NOT NULL default '0',
					  `fformat` varchar(255),
					  `fwidth` int(11) NOT NULL default '0',
					  `fheight` int(11) NOT NULL default '0',
					  `ffilesize` int(11) NOT NULL default '0',
					  `ffiletype` varchar(255),
					  `foptions` text,
					  `fvalidate` varchar(255),
					  PRIMARY KEY (`type`)) ENGINE=MyISAM");

        sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plugin_profile_config').
					" ( `csetting` varchar(255),
					  `cvalue` text,
					  PRIMARY KEY (`csetting`)) ENGINE=MyISAM");

// This is to update tables for users of v 2.00.02b, from the beta tables to the released tables:
		$hasord = false;
		$query = sql_query("SHOW COLUMNS FROM ".sql_table('plugin_profile_fields'));
		while ($column = mysql_fetch_assoc($query)) {
	  		if ($column['Field'] == 'forder') $hasord = true;
		}
		if (!$hasord) {
			// then user upgrading from beta release so fix the tables
	  		sql_query("ALTER TABLE ".sql_table('plugin_profile_fields')." ADD forder int(11) NOT NULL default '0' AFTER fvalidate");
	  		sql_query("UPDATE ".sql_table('plugin_profile_fields')." SET forder = '0'");
			// also make type values sort alphabetically
			sql_query("ALTER TABLE ". sql_table('plugin_profile_types')." CHANGE `type` `type` ENUM('date','dropdown','file','list','mail','number','password','radio','text','textarea','url')");
			sql_query("ALTER TABLE ". sql_table('plugin_profile_fields')." CHANGE `ftype` `ftype` ENUM('date','dropdown','file','list','mail','number','password','radio','text','textarea','url')");
		}

// This is to update tables v 2.02: (includes explicitly making the table engine MyISAM)
		$pres = sql_query("SHOW TABLE STATUS LIKE '".sql_table('plugin_profile')."%'");
		while ($ptable = mysql_fetch_assoc($pres)) {
			if (strtolower($ptable['Engine']) != 'myisam') {
				sql_query("ALTER TABLE ".$ptable['Name']." ENGINE = MyISAM");
			}
		}

		$hasord = false;
		$query = sql_query("SHOW COLUMNS FROM ".sql_table('plugin_profile'));
		while ($column = mysql_fetch_assoc($query)) {
	  		if ($column['Field'] == 'torder') $hasord = true;
		}
		if (!$hasord) {
			// then user upgrading from version before 2.02
	  		sql_query("ALTER TABLE ".sql_table('plugin_profile')." ADD torder tinyint(2) NOT NULL default '0' AFTER value");
	  		sql_query("UPDATE ".sql_table('plugin_profile')." SET torder = '0'");
		}

// This is to update tables from v 2.02 to v 2.1:
        $pres = sql_query("SHOW COLUMNS FROM ".sql_table('plugin_profile_fields')." LIKE 'fdefault'");
        if (!mysql_num_rows($pres)) {
            sql_query("ALTER TABLE ".sql_table('plugin_profile_fields')." ADD fdefault varchar(255) AFTER forder");
        }
        $pres = sql_query("SHOW COLUMNS FROM ".sql_table('plugin_profile_fields')." LIKE 'fpublic'");
        if (!mysql_num_rows($pres)) {
            sql_query("ALTER TABLE ".sql_table('plugin_profile_fields')." ADD fpublic tinyint(2) NOT NULL default '0' AFTER fdefault");
            sql_query("UPDATE ".sql_table('plugin_profile_fields')." SET fpublic=0");
            sql_query("UPDATE ".sql_table('plugin_profile_fields')." SET fpublic=1 WHERE fname='nick' OR fname='url' OR fname='notes' OR fname='avatar'");
        }

// Fill the tables with default values if needed

// fill the plugin_profile_fields table
		$fields = array(
			array('fname'=>'password',
				  'flabel'=>_PROFILE_LABEL_PASSWORD,
				  'ftype'=>'password',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'notes',
				  'flabel'=>_PROFILE_LABEL_NOTES,
				  'ftype'=>'textarea',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>1),
			array('fname'=>'url',
				  'flabel'=>_PROFILE_LABEL_URL,
				  'ftype'=>'url',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>1),
			array('fname'=>'realname',
				  'flabel'=>_PROFILE_LABEL_REALNAME,
				  'ftype'=>'text',
				  'required'=>1,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'nick',
				  'flabel'=>_PROFILE_LABEL_NICK,
				  'ftype'=>'text',
				  'required'=>1,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>1),
			array('fname'=>'mail',
				  'flabel'=>_PROFILE_LABEL_MAIL,
				  'ftype'=>'mail',
				  'required'=>1,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'msn',
				  'flabel'=>_PROFILE_LABEL_MSN,
				  'ftype'=>'text',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'sex',
				  'flabel'=>_PROFILE_LABEL_SEX,
				  'ftype'=>'radio',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>_PROFILE_MALE.'|m;'._PROFILE_FEMALE.'|f',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'birthdate',
				  'flabel'=>_PROFILE_LABEL_BIRTHDATE,
				  'ftype'=>'date',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>12,
				  'fsize'=>12,
				  'fformat'=>'D-M-Y',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'avatar',
				  'flabel'=>_PROFILE_LABEL_AVATAR,
				  'ftype'=>'file',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>64,
				  'fheight'=>64,
				  'ffilesize'=>15000,
				  'ffiletype'=>'jpg;gif;png;jpeg',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>1),
			array('fname'=>'location',
				  'flabel'=>_PROFILE_LABEL_LOCATION,
				  'ftype'=>'text',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'hobbies',
				  'flabel'=>_PROFILE_LABEL_HOBBIES,
				  'ftype'=>'text',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'secret',
				  'flabel'=>_PROFILE_LABEL_SECRET,
				  'ftype'=>'password',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'icq',
				  'flabel'=>_PROFILE_LABEL_ICQ,
				  'ftype'=>'number',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'favoritesite',
				  'flabel'=>_PROFILE_LABEL_FAVORITESITE,
				  'ftype'=>'url',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'bio',
				  'flabel'=>_PROFILE_LABEL_BIO,
				  'ftype'=>'textarea',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
			array('fname'=>'resume',
				  'flabel'=>_PROFILE_LABEL_RESUME,
				  'ftype'=>'url',
				  'required'=>0,
				  'enabled'=>1,
				  'flength'=>0,
				  'fsize'=>0,
				  'fformat'=>'',
				  'fwidth'=>0,
				  'fheight'=>0,
				  'ffilesize'=>0,
				  'ffiletype'=>'',
				  'foptions'=>'',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0),
            array('fname'=>'privacylevel',
				  'flabel'=>_PROFILE_LABEL_PRIVACYLEVEL,
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
				  'foptions'=>_PROFILE_PRIVACYLEVEL_0.'|0;'._PROFILE_PRIVACYLEVEL_1.'|1;'._PROFILE_PRIVACYLEVEL_2.'|2',
				  'fvalidate'=>'',
				  'forder'=>0,
                  'fdefault'=>'0',
                  'fpublic'=>1)
		);
		foreach ($fields as $value) {
			if (mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile_fields')." WHERE fname='".$value['fname']."'")) == 0) {
				sql_query("INSERT INTO ". sql_table('plugin_profile_fields')
					." VALUES ('".$value['fname']."','"
					.$value['flabel']."','"
					.$value['ftype']."','"
					.$value['required']."','"
					.$value['enabled']."','"
					.$value['flength']."','"
					.$value['fsize']."','"
					.$value['fformat']."','"
					.$value['fwidth']."','"
					.$value['fheight']."','"
					.$value['ffilesize']."','"
					.$value['ffiletype']."','"
					.$value['foptions']."','"
					.$value['fvalidate']."','"
                    .$value['forder']."','"
                    .$value['fdefault']."','"
					.$value['fpublic']."')");
			}
		}
// fill in the plugin_profile_types table
		$types = array(
			array('type'=>'text','flength'=>255,'fsize'=>40,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fvalidate'=>''),
			array('type'=>'textarea','flength'=>8,'fsize'=>35,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fvalidate'=>''),
			array('type'=>'mail','flength'=>60,'fsize'=>25,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fvalidate'=>''),
			array('type'=>'file','flength'=>255,'fsize'=>25,'fformat'=>'','fwidth'=>64,'fheight'=>64,'ffilesize'=>50000,'ffiletype'=>'jpg;gif;png;jpeg','foptions'=>'','fvalidate'=>''),
			array('type'=>'list','flength'=>255,'fsize'=>40,'fformat'=>'ul-profilelist','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fvalidate'=>''),
			array('type'=>'password','flength'=>25,'fsize'=>25,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fvalidate'=>''),
			array('type'=>'dropdown','flength'=>255,'fsize'=>1,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fvalidate'=>''),
			array('type'=>'date','flength'=>25,'fsize'=>25,'fformat'=>'D-M-Y','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fvalidate'=>''),
			array('type'=>'url','flength'=>255,'fsize'=>40,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fvalidate'=>''),
			array('type'=>'number','flength'=>25,'fsize'=>25,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fvalidate'=>''),
			array('type'=>'radio','flength'=>255,'fsize'=>25,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fvalidate'=>''),
		);
		foreach ($types as $value) {
			if (mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile_types')." WHERE type='".$value['type']."'")) == 0) {
				sql_query("INSERT INTO ". sql_table('plugin_profile_types')
					." VALUES ('".$value['type']."','"
					.$value['flength']."','"
					.$value['fsize']."','"
					.$value['fformat']."','"
					.$value['fwidth']."','"
					.$value['fheight']."','"
					.$value['ffilesize']."','"
					.$value['ffiletype']."','"
					.$value['foptions']."','"
					.$value['fvalidate']."')");
			}
		}
// fill in plugin_profile_config table
        //$editprofilevalue = "";
		/*
        $lines = file($DIR_PLUGINS.'profile/editprofile.cfg.sample');
        foreach ($lines as $line) {
            $editprofilevalue .= $line;
        }
		*/
		$editprofilevalue = "# This configures the format of the editprofile page. See help.html for formatting options.
# First set tabs and labels. (tab0 must always be for NP_Profile) only tabs 0-9 allowed
[t]
[t0]Personal
[t1]Contact
[t2]Bio
[t3]Interests
[t4]Password
[/t]
# Now give format of tab 0
[0]
startform
[h3]Personal
nick
realname
sex
birthdate
location
avatar
bio
[h3]Privacy
privacylevel
endform
[/0]
[1]
startform
[h3]Contact
mail
icq
msn
url
endform
[/1]
[2]
startform
[h3]Bio
bio
resume
endform
[/2]
[3]
startform
[h3]Interests
favoritesite
hobbies
secret
notes
endform
[/3]
[4]
[h3]Password
password
[/4]
# In the future you will be able to add forms for NP_Profile extension plugins.
# The means employed to specify and retirieve the code for these forms is not yout determined.
# Under consideration are requiring the extension plugin to have a method called editProfileForm()
# which outputs a complete form for modifying its settings. Or by calling skinvars that create the form
# while assuming a skintype of member";
        $configs = array(
			array('csetting'=>'editprofile','cvalue'=>$editprofilevalue)
		);
        foreach ($configs as $value) {
			if (mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile_config')." WHERE csetting='".$value['csetting']."'")) == 0) {
				sql_query("INSERT INTO ". sql_table('plugin_profile_config')
					." VALUES ('".$value['csetting']."','"
					.$value['cvalue']."')");
			}
		}
	}

	function unInstall() {
		// if requested, delete the data table containing member profile field data
		if ($this->getOption('del_uninstall_data') == 'yes')	{
			sql_query('DROP TABLE '.sql_table('plugin_profile'));
		}
		// if requested, delete the field and type definition tables
		if ($this->getOption('del_uninstall_fields') == 'yes')	{
			sql_query('DROP TABLE IF EXISTS '.sql_table('plugin_profile_fields'));
			sql_query('DROP TABLE IF EXISTS '.sql_table('plugin_profile_types'));
            sql_query('DROP TABLE IF EXISTS '.sql_table('plugin_profile_config'));
		}
	}

 	function init() {
		// include language file for this plugin
        $language = ereg_replace( '[\\|/]', '', getLanguageName());
        if (file_exists($this->getDirectory().$language.'.php'))
            include_once($this->getDirectory().$language.'.php');
        else
            include_once($this->getDirectory().'english.php');

		// set some variables/properties available to entire class
		$this->default['file']['default'] = $this->getOption('default_image');
		$this->req_emp['start'] = $this->getOption('req_emp_start');
		$this->req_emp['end'] = $this->getOption('req_emp_end');
		$this->showEmail = $this->getOption('email_public');
		$this->pwd_min_length = intval($this->getOption('pwd_min_length'));
		$this->pwd_complexity = intval($this->getOption('pwd_complexity'));
	}

	function hasAdminArea() { return 1; }

	function event_QuickMenu(&$data) {
    	// only show when option enabled
    	if ($this->getOption('quickmenu') != 'yes') return;
    	global $member;
    	if (!($member->isLoggedIn())) return;
    	array_push($data['options'],
      		array('title' => 'Profile',
        	'url' => $this->getAdminURL(),
        	'tooltip' => _PROFILE_ADMIN_TOOLTIP));
  	}

	function event_PostDeleteMember(&$data) {
		$this->_deleteMemberData($data['member']->id);
	}

	function doTemplateVar(&$item) {
		$args = func_get_args();
		array_shift($args);
		array_unshift($args, 'template('.$item->itemid.')');
		call_user_func_array(array(&$this,'doSkinVar'),$args);
	}

	function doTemplateCommentsVar(&$item, &$comment) {
		$args = func_get_args();
		array_shift($args);
        array_shift($args);
		array_unshift($args, 'comment');
		if (intval($comment['memberid']) > 0) {
			$args[4] = intval($comment['memberid']);
			call_user_func_array(array(&$this,'doSkinVar'),$args);
		}
	}

   function doSkinVar($skinType,$param1,$param2='',$param3 = '',$param4 = '') {
		global $_GET, $CONF, $memberid, $membername, $member;

		$isEdit = false;
		if (getVar('edit') == 1) {
			$isEdit = true;
		}
		if (substr($skinType,0,8) == 'template') {
			$tiid = intval(str_replace(array('template','(',')'), array('','',''),$skinType));
			$skinType = 'template';
		}

		if (in_array($skinType, array('member','archive','archivelist','item','index','template','comment'))) {
			if (in_array($param1, array('startform','endform','status','editlink','submitbutton','editprofile')) || $this->getFieldAttribute($param1,'enabled')) {
				$pmid = $memberid;

                if (strtoupper($param4) == '%ME%') {
                    if ($member->getID() > 0) {
                        $pmid = $member->getID();
                    }
                    else {
                        return;
                    }
                }

				if (intval($pmid) < 1) {
					if (!is_numeric($param4)) {
                        $param4 = $this->_getMemberIdFromName($param4);
					}
					if (intval($param4) > 0) {
						$pmid = intval($param4);
					}
					elseif ($skinType == 'item') {
						global $itemid;
						if (intval($itemid) > 0 ) {
							$pmid = $this->_getAuthorFromItemId($itemid);
						}
					}
					elseif ($skinType == 'template') {
						$pmid = $this->_getAuthorFromItemId($tiid);
					}
					else {
						if (!$param2 == 'label') {
							return;
						}
					}
				}
				$pmid = intval($pmid);

				if ($param2 == 'label') {
					$isreq = (bool)$this->getFieldAttribute($param1,'required');
					$bstyle = '';
					$estyle = '';
					if ($skinType == 'member' && $member->id == $pmid && $isreq) {
						$bstyle = $this->req_emp['start'];
						$estyle = $this->req_emp['end'];
					}
					echo $bstyle.$this->getFieldAttribute($param1,'flabel').$estyle;
				}
				else {
					$this->restrictView = $this->restrictViewer();
					switch($param1) {
					case 'password':
						if ($skinType == 'member' && $member->id == $pmid && $isEdit) {
							$size = $this->getFieldAttribute($param1,'fsize');
							$maxlength = $this->getFieldAttribute($param1,'flength');
							echo '<h2>'._PROFILE_SV_CHANGE_PASSWORD.'</h2>';
							echo '<form enctype="multipart/form-data" name="passwordform" action="' . $CONF['IndexURL'] . 'action.php" method="post">' . "\n";
							echo "<table>\n";
							echo "<tr><td>"._PROFILE_SV_OLD_PASSWORD."</td><td>";
							echo '<input name="oldpassword" type="password" maxlength="' . $maxlength . '" size="' . $size . '" />' . "\n";
							echo "</td></tr>\n<tr><td>"._PROFILE_SV_NEW_PASSWORD."</td><td>";
							echo '<input name="' . $param1 . '" type="password" maxlength="' . $maxlength . '" size="' . $size . '" />' . "\n";
							echo "</td></tr>\n<tr><td>"._PROFILE_SV_VERIFY_PASSWORD."</td><td>";
							echo '<input name="verifypassword" type="password" maxlength="' . $maxlength . '" size="' . $size . '" />' . "\n";
							echo "</td></tr>\n</table>\n";
							echo '<input type="hidden" name="action" value="plugin" />';
							echo '<input type="hidden" name="name" value="Profile" />';
							echo '<input type="hidden" name="type" value="update" />';
							echo '<input type="hidden" name="memberid" value="' . $member->id . '" />' . "\n";
							echo '<input type="submit" name="submit" value="'._PROFILE_SUBMIT.'" />' . "\n";
							echo "</form>\n";
						}
						break;
					case 'startform':
						if ($skinType == 'member' && $member->id == $pmid && $isEdit) {
							echo '<form enctype="multipart/form-data" name="profileform" action="' . $CONF['IndexURL'] . 'action.php" method="post">' . "\n";
						}
						break;
					case 'endform':
						if ($skinType == 'member' && $member->id == $pmid && $isEdit) {
							echo '<input type="hidden" name="action" value="plugin" />';
							echo '<input type="hidden" name="name" value="Profile" />';
							echo '<input type="hidden" name="type" value="update" />';
							echo '<input type="hidden" name="memberid" value="' . $member->id . '" />' . "\n";
							echo '<input type="submit" name="submit" value="'._PROFILE_SUBMIT.'" />' . "\n";
							echo "</form>\n";
						}
						break;
                    case 'submitbutton':
						if ($skinType == 'member' && $member->id == $pmid && $isEdit) {
							echo '<input type="submit" name="submit" value="'._PROFILE_SUBMIT.'" />' . "\n";
						}
						break;
					case 'status':
						if ($skinType == 'member' && $member->id == $pmid) {
							if (getVar('status') == 1) {
								echo _PROFILE_SV_STATUS_UPDATED;
							}
						}
						break;
					case 'editlink':
						if ($skinType == 'member' && $member->id == $pmid) {
							if ($isEdit) {
								$rstring = serverVar('REQUEST_URI');
								if (strpos($rstring, '?') !== false ) {
									$rstringarr = explode('?',$rstring);
									$sstring = $rstringarr[0];
									$qstringarr = explode('&',$rstringarr[1]);
								}
								else {
									$sstring = $rstring;
									$qstringarr = array('');
								}
								$qstring = '';
								$k = 0;
								foreach ($qstringarr as $param) {
									if ($param != '' && strpos($param,'status') === false && strpos($param,'edit') === false) {
										$qstring = ($k == 0 ? '?' : '&').$param;
										$k = $k + 1;
									}
								}
								$editlink = "http://".serverVar('SERVER_NAME').$sstring.$qstring;
								echo '<a class="profileeditlink" href="'.$editlink.'">'._PROFILE_SV_EDITLINK_FORM.'</a>';
							}
							else {
								$rstring = serverVar('REQUEST_URI');
								if (strpos($rstring, '?') !== false ) {
									$rstringarr = explode('?',$rstring);
									$sstring = $rstringarr[0];
									$qstringarr = explode('&',$rstringarr[1]);
								}
								else {
									$sstring = $rstring;
									$qstringarr = array('');
								}
								$qstring = '';
								$k = 0;
								foreach ($qstringarr as $param) {
									if ($param != '' && strpos($param,'status') === false && strpos($param,'edit') === false) {
										$qstring = ($k == 0 ? '?' : '&').$param;
										$k = $k + 1;
									}
								}
								if ($qstring{0} == '?') $eparam = '&edit=1';
								else $eparam = '?edit=1';
								$editlink = "http://".serverVar('SERVER_NAME').$sstring.$qstring.$eparam;
								echo '<a class="profileeditlink" href="'.$editlink.'">'._PROFILE_SV_EDITLINK_EDIT.'</a>';
							}
						}
						break;
					case 'editprofile':
						global $blog;
						$blogid = $blog->getID();
						if ($skinType == 'member' && $member->id == $pmid) {
							if ($isEdit) {
								//$editlink = $CONF['PluginURL']."profile/editprofile.php?edit=1";
								//echo '<a class="profileeditlink" href="'.$editlink.'">'._PROFILE_SV_EDITLINK_FORM.'</a>';
							}
							else {
								$editlink = $CONF['PluginURL']."profile/editprofile.php?edit=1&blogid=$blogid";
								echo '<a class="profileeditlink" href="'.$editlink.'">'._PROFILE_SV_EDITLINK_EDIT.'</a>';
							}
						}
						break;
					case 'mail':
						if ($this->restrictView && !$this->getFieldAttribute($param1,'fpublic')) break;
						$result = sql_query("SELECT memail FROM ".sql_table(member)." WHERE mnumber=" . $pmid);
						$value = mysql_result($result,'memail');
						$size = $this->getFieldAttribute($param1,'fsize');
						$maxlength = $this->getFieldAttribute($param1,'flength');
						if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
							echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
						}
						else {
							$safe_add = $this->safeAddress($value);
							if ($param3 == 'raw') {
								$fstart = '';
								$fend = '';
							}
							else {
								$fstart = '<a href="mailto:';
								$fend = '" title="Member '.$pmid.'">'.$safe_add.'</a>';
							}
							$safe_add = $fstart.$safe_add.$fend;
							if ($this->showEmail > 1) {
								echo $safe_add;
							}
							elseif ($this->showEmail == 1 && $member->isLoggedIn()) {
								echo $safe_add;
							}
							else {
								// show nothing
							}
						}
						break;
					case 'nick':
						$result = sql_query("SELECT mname FROM ".sql_table(member)." WHERE mnumber=" . $pmid);
						$value = mysql_result($result,'mname');
						$size = $this->getFieldAttribute($param1,'fsize');
						$maxlength = $this->getFieldAttribute($param1,'flength');
						if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
							echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
						}
						else {
							echo $value;
						}
						break;
					case 'realname':
						if ($this->restrictView && !$this->getFieldAttribute($param1,'fpublic')) break;
						$result = sql_query("SELECT mrealname FROM ".sql_table(member)." WHERE mnumber=" . $pmid);
						$value = mysql_result($result,'mrealname');
						$size = $this->getFieldAttribute($param1,'fsize');
						$maxlength = $this->getFieldAttribute($param1,'flength');
						if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
							echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
						}
						else {
							echo $value;
						}
						break;
					case 'url':
						if ($this->restrictView && !$this->getFieldAttribute($param1,'fpublic')) break;
						$result = sql_query("SELECT murl FROM ".sql_table(member)." WHERE mnumber=" . $pmid);
						$value = mysql_result($result,'murl');
						$size = $this->getFieldAttribute($param1,'fsize');
						$maxlength = $this->getFieldAttribute($param1,'flength');
						if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
							echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
						}
						else {
							if ($param3 == 'raw') {
								$fstart = '';
								$fend = '';
							}
							else {
								$fstart = '<a href="';
								$fend = '" title="'.$param1.'" >'.$value.'</a>';
							}
							echo $fstart.$value.$fend . "\n";
						}
						break;
					case 'notes':
						if ($this->restrictView && !$this->getFieldAttribute($param1,'fpublic')) break;
						$result = sql_query("SELECT mnotes FROM ".sql_table(member)." WHERE mnumber=" . $pmid);
						$value = mysql_result($result,'mnotes');
						$rows = $this->getFieldAttribute($param1,'flength');
						$cols = $this->getFieldAttribute($param1,'fsize');
						if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
							echo '<textarea name="' . $param1 . '" cols="' . $cols . '" rows="' . $rows . '">' . $value . '</textarea>' . "\n";
						}
						else {
							if ($param3 == 'raw') {
								echo $value;
							}
							else {
								echo '<textarea readonly="readonly" cols="' . $cols . '" rows="' . $rows . '">' . $value . '</textarea>' . "\n";
							}
						}
						break;
					default:
						if ($this->restrictView && !$this->getFieldAttribute($param1,'fpublic')) break;
						$type = $this->getFieldAttribute($param1,'ftype');
						switch($type) {
						case 'text':
							$value = $this->getValue($pmid,$param1);
							$maxlength = $this->getFieldAttribute($param1,'flength');
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
							}
							else {
								echo $value;
							}
							break;
						case 'number':
							$value = $this->getValue($pmid,$param1);
							$maxlength = $this->getFieldAttribute($param1,'flength');
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
							}
							else {
								echo $value;
							}
							break;
						case 'url':
							$value = $this->getValue($pmid,$param1);
							$maxlength = $this->getFieldAttribute($param1,'flength');
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
							}
							else {
								if ($param3 == 'raw') {
									$fstart = '';
									$fend = '';
								}
								else {
									$fstart = '<a href="';
									$fend = '" title="'.$param1.'" >'.$value.'</a>';
								}
								echo $fstart.$value.$fend . "\n";
							}
							break;
						case 'textarea':
							$value = $this->getValue($pmid,$param1);
							$cols = $this->getFieldAttribute($param1,'fsize');
							$rows = $this->getFieldAttribute($param1,'flength');
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								echo '<textarea name="' . $param1 . '" cols="' . $cols . '" rows="' . $rows . '">' . $this->_br2nl($value) . '</textarea>' . "\n";
							}
							else {
								if ($param3 == 'raw') {
									echo $value;
								}
								else {
									echo '<textarea readonly="readonly" cols="' . $cols . '" rows="' . $rows . '">' . $this->_br2nl($value) . '</textarea>' . "\n";
								}
							}
							break;
						case 'mail':
							$value = $this->getValue($pmid,$param1);
							$maxlength = $this->getFieldAttribute($param1,'flength');
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
							}
							else {
								$safe_add = $this->safeAddress($value);
								if ($param3 == 'raw') {
									$fstart = '';
									$fend = '';
								}
								else {
									$fstart = '<a href="mailto:';
									$fend = '" title="Member '.$pmid.'">'.$safe_add.'</a>';
								}
								$safe_add = $fstart.$safe_add.$fend;
								if ($this->showEmail > 1) {
									echo $safe_add;
								}
								elseif ($this->showEmail == 1 && $member->isLoggedIn()) {
									echo $safe_add;
								}
								else {
									// show nothing
								}
							}
							break;
						case 'file':
							$value = $this->getValue($pmid,$param1);
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="file" size="' . $size . '" />' . "\n";
							}
							else {
								if ($param3 == 'image') {
									$fstart = '<img src="';
									$fend = '" alt="'.$param1.'" />';
								}
								elseif ($param3 == 'raw') {
									$fstart = '';
									$fend = '';
								}
								else {
									$fstart = '<a href="';
									$fend = '" title="'.$param1.'" >'.$param1.'</a>';
								}
								if (strlen($value) >= 3) {
									echo $fstart.$CONF['MediaURL'] . $value.$fend . "\n";
								}
								else {
									echo $fstart.$this->default['file']['default'].$fend ."\n";
								}
							}
							break;
						case 'password':
							$maxlength = $this->getFieldAttribute($param1,'flength');
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="password" maxlength="' . $maxlength . '" size="' . $size . '" />' . "\n";
							}
							else {
								echo '********';
							}
							break;
						case 'dropdown':
							$value = $this->getValue($pmid,$param1);
                            if ($value == '') {
                                $defvalue = trim($this->getFieldAttribute($param1,'fdefault'));
                                $value = $defvalue;
                            }
							$size = $this->getFieldAttribute($param1,'fsize');
							$rawoptions = explode(";", $this->getFieldAttribute($param1,'foptions'));
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								if ($size) {
									echo '<select name="' . $param1 . '" size="' . $size . '">' . "\n";
								}
								else {
									echo '<select name="' . $param1 . '">' . "\n";
								}
								foreach ($rawoptions as $ropt) {
									$opt = explode("|", $ropt);
									if (count($opt) == 1) $opt[1] = trim($opt[0]);
									if (trim($opt[1]) == $value) {
										echo '<option value="' . $value . '" selected="selected">' . trim($opt[0]) . '</option>' . "\n";
									}
									else {
										echo '<option value="' . trim($opt[1]) . '">' . trim($opt[0]) . '</option>' . "\n";
									}
								}
								echo '</select>' . "\n";
							}
							else {
								foreach ($rawoptions as $ropt) {
									$opt = explode("|", $ropt);
									if (count($opt) == 1) $opt[1] = trim($opt[0]);
									if (trim($opt[1]) == $value) {
										echo trim($opt[0]) . "\n";
									}
								}
							}
							break;
						case 'radio':
							$value = $this->getValue($pmid,$param1);
                            if ($value == '') {
                                $defvalue = trim($this->getFieldAttribute($param1,'fdefault'));
                                $value = $defvalue;
                            }
							$rawoptions = explode(";", $this->getFieldAttribute($param1,'foptions'));
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								foreach ($rawoptions as $ropt) {
									$opt = explode("|", $ropt);
									if (count($opt) == 1) $opt[1] = trim($opt[0]);
									if (trim($opt[1]) == $value) {
										echo '<input type="radio" name="' . $param1 . '" value="' . $value . '" checked="checked"> ' . trim($opt[0]) . '</input>' . "\n";
									}
									else {
										echo '<input type="radio" name="' . $param1 . '" value="' . trim($opt[1]) . '"> ' . trim($opt[0]) . '</input>' . "\n";
									}
								}
							}
							else {
								foreach ($rawoptions as $ropt) {
									$opt = explode("|", $ropt);
									if (count($opt) == 1) $opt[1] = trim($opt[0]);
									if (trim($opt[1]) == $value) {
										echo trim($opt[0]) . "\n";
									}
								}
							}
							break;
						case 'list':
							$value = $this->getValue($pmid,$param1);
                            if ($value == '') {
                                $defvalue = trim($this->getFieldAttribute($param1,'fdefault'));
                                $value = $defvalue;
                            }
							$valuearr = explode(";", $value);
							$rawoptions = explode(";", $this->getFieldAttribute($param1,'foptions'));
							$numopts = count($rawoptions);
							if ($numopts == 1 && trim($rawoptions[0]) == '') $numopts = 0;
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								if ($numopts) {
									echo "<table style=\"background-color:transparent\">\n";
									$j = 0;
									foreach ($rawoptions as $ropt) {
										$j = $j + 1;
										$opt = explode("|", $ropt);
										if (count($opt) == 1) $opt[1] = trim($opt[0]);
										if ($j == 1) echo "<tr>\n";
										if (in_array(trim($opt[1]),$valuearr)) {
											echo '<td><input type="checkbox" name="' . $param1 . '[]" value="' . trim($opt[1]) . '" checked="checked"> ' . trim($opt[0]) . '</input></td>' . "\n";
										}
										else {
											echo '<td><input type="checkbox" name="' . $param1 . '[]" value="' . trim($opt[1]) . '"> ' . trim($opt[0]) . '</input></td>' . "\n";
										}
										if ($j == 3) {
											echo "</tr>\n";
											$j = 0;
										}
									}
									switch ($j) {
									case 2:
										echo "<td></td></tr>\n";
										break;
									case 1:
										echo "<td></td><td></td></tr>\n";
										break;
									default:
									}
									echo "</table>\n";
								}
								else {
									$maxlength = $this->getFieldAttribute($param1,'flength');
									$size = $this->getFieldAttribute($param1,'fsize');
									echo '<input name="' . $param1 . '[]" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
								}
							}
							else {
								$formatarr = explode("-", $this->getFieldAttribute($param1,'fformat'));
								if (count($formatarr) > 1) $listclass = ' class="'.trim($formatarr[1]).'"';
								else $listclass = '';
								$formatarr[0] = trim(strtolower($formatarr[0]));
								switch ($formatarr[0]) {
								case 'ol':
									$liststart = '<ol'.$listclass.'>';
									$listend = '</ol>';
									$estart = '<li>';
									$eend = '</li>';
									break;
								case 'dl':
									$liststart = '<dl'.$listclass.'>';
									$listend = '</dl>';
									$estart = '<dd>';
									$eend = '</dd>';
									break;
								case 'ul':
									$liststart = '<ul'.$listclass.'>';
									$listend = '</ul>';
									$estart = '<li>';
									$eend = '</li>';
									break;
								default:
									$liststart = '';
									$listend = '';
									$estart = $formatarr[0];
									if (count($formatarr) > 1) $eend = $formatarr[1];
									else $eend = '';
									break;
								}

								echo "$liststart\n";
								if ($numopts) {
									foreach ($rawoptions as $ropt) {
										$opt = explode("|", $ropt);
										if (count($opt) == 1) $opt[1] = trim($opt[0]);
										if (in_array(trim($opt[1]),$valuearr)) {
											if ($param3 == 'link') {
												$fstart = $estart.'<a href="';
												$fend = '" title="'.$param1.'" >'.trim($opt[1]).'</a>'.$eend;
											}
											else {
												$fstart = $estart;
												$fend = $eend;
											}
											echo $fstart.trim($opt[0]).$fend . "\n";
										}
									}
								}
								else {
									foreach ($valuearr as $opt) {
										if ($param3 == 'link') {
											$fstart = $estart.'<a href="';
											$fend = '" title="'.$param1.'" >'.trim($opt).'</a>'.$eend;
										}
										else {
											$fstart = $estart;
											$fend = $eend;
										}
										echo $fstart.trim($opt).$fend . "\n";
									}

								}
								echo "$listend\n";
							}
							break;
						case 'date':
							$format = strtoupper($this->getFieldAttribute($param1,'fformat'));
							$seps = trim(str_replace(array('D','M','Y'),'',$format));
							$format = str_replace(array($seps{0},$seps{1}),'',$format);
							$date = $this->getValue($pmid,$param1);
							if (strpos($date,'-') === False) {
								$date = substr($date,0,2).'-'.substr($date,2,2).'-'.substr($date,4,4);
							}
							$datearr = explode('-',$date);
							if ($date == '' || $date == '--') $value = '';
							else {
								$day = $datearr[0];
								$month = $datearr[1];
								$year = $datearr[2];
									switch($format) {
									case 'DMY':
										$value = $day.$seps{0}.$month.$seps{1}.$year;
										break;
									case 'MDY':
										$value = $month.$seps{0}.$day.$seps{1}.$year;
										break;
									case 'YMD':
										$value = $year.$seps{0}.$month.$seps{1}.$day;
										break;
									case 'YDM':
										$value = $year.$seps{0}.$day.$seps{1}.$month;
										break;
									default:
										$value = $month.$seps{0}.$day.$seps{1}.$year;
										break;
									}
							}
							if ($skinType == 'member' && $member->id == $pmid && $param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="text" maxlength="10" size="10" value="' . $value . '"/>' . "\n";
							}
							else {
								echo $value;
							}
							break;
						} // end switch for for field types
					} // end switch $param1
				} // end else part of if param2 is 'label'
			} // end if field is enabled
		} // end if skintype is one of supported
	} // end doSkinVar()

	function doAction($actionType) {
		global $CONF, $_POST, $_FILES, $member, $DIR_MEDIA, $HTTP_REFERER;
		$key = array_keys($_POST);
		$actiontype = postVar('type');

		switch($actiontype) {
		case 'updatefield':
			if (!$member->isAdmin()) doError(_PROFILE_ACTION_DENY);
			$ofname = postVar('ofname');
			$fname = postVar('fname');
			if ($fname == '') doError(_PROFILE_ACTION_NO_FIELD);
			if (!isValidDisplayName($fname)) {
				doError(_ERROR_BADNAME);
			}
			$valuearray = array(
								'fname'=>strtolower(postVar('fname')),
								'flabel'=>postVar('flabel'),
								'ftype'=>postVar('ftype'),
								'required'=>intPostVar('required'),
								'enabled'=>intPostVar('enabled'),
								'flength'=>intPostVar('flength'),
								'fsize'=>intPostVar('fsize'),
								'fformat'=>postVar('fformat'),
								'fwidth'=>intPostVar('fwidth'),
								'fheight'=>intPostVar('fheight'),
								'ffilesize'=>intPostVar('ffilesize'),
								'ffiletype'=>str_replace(',',';',postVar('ffiletype')),
								'foptions'=>postVar('foptions'),
								'fvalidate'=>postVar('fvalidate'),
                                'forder'=>postVar('forder'),
                                'fdefault'=>postVar('fdefault'),
                                'fpublic'=>postVar('fpublic')
								);
			if (strtolower($ofname) == strtolower($fname)) {
				$this->updateFieldDef($fname, $valuearray);
			}
			else if ($this->fieldExists($ofname)) {
				$this->addFieldDef($fname, $ofname, $valuearray);
				$this->delFieldDef($ofname);
			}
			else {
				$this->addFieldDef($fname, '', $valuearray);
			}
			$destURL = $CONF['PluginURL'] . "profile/index.php?showlist=fields&safe=true&status=2";
			header('Location: ' . $destURL);
			break;
		case 'addfield':
			if (!$member->isAdmin()) doError(_PROFILE_ACTION_DENY);
			$destURL = $CONF['PluginURL'] . "profile/index.php?showlist=fields&safe=true&status=1";
			$fname = postVar('fname');
			if ($fname == '') doError(_PROFILE_ACTION_NO_FIELD);
			if (!isValidDisplayName($fname)) {
				doError(_ERROR_BADNAME);
			}
			$valuearray = array(
								'fname'=>strtolower(postVar('fname')),
								'flabel'=>postVar('flabel'),
								'ftype'=>postVar('ftype'),
								'required'=>intPostVar('required'),
								'enabled'=>intPostVar('enabled'),
								'flength'=>intPostVar('flength'),
								'fsize'=>intPostVar('fsize'),
								'fformat'=>postVar('fformat'),
								'fwidth'=>intPostVar('fwidth'),
								'fheight'=>intPostVar('fheight'),
								'ffilesize'=>intPostVar('ffilesize'),
								'ffiletype'=>str_replace(',',';',postVar('ffiletype')),
								'foptions'=>postVar('foptions'),
								'fvalidate'=>postVar('fvalidate'),
                                'forder'=>postVar('forder'),
                                'fdefault'=>postVar('fdefault'),
                                'fpublic'=>postVar('fpublic')
								);
			if ($this->fieldExists($fname)) {
				doError("$fname - "._PROFILE_ACTION_DUPLICATE_FIELD);
			}
			else {
				$this->addFieldDef($fname, '', $valuearray);
			}
			header('Location: ' . $destURL);
			break;
		case 'deletefield':
			if (!$member->isAdmin()) doError(_PROFILE_ACTION_DENY);
			$destURL = $CONF['PluginURL'] . "profile/index.php?showlist=fields&safe=true&status=3";
			$fname = postVar('fname');
			if ($fname == '') doError(_PROFILE_ACTION_NO_FIELD);
			if (!$this->fieldExists($fname)) {
				doError("$fname - "._PROFILE_ACTION_NOT_FIELD);
			}
			else {
				$this->delFieldDef($fname);
			}
			header('Location: ' . $destURL);
			break;
		case 'updateconfig':
			if (!$member->isAdmin()) doError(_PROFILE_ACTION_DENY);
			$destURL = $CONF['PluginURL'] . "profile/index.php?showlist=config&safe=true&status=2";
			$epvalue = postVar('editprofile');
			$this->updateConfig($epvalue);

			header('Location: ' . $destURL);
			break;
		case 'updatetype':
			if (!$member->isAdmin()) doError(_PROFILE_ACTION_DENY);
			$otype = postVar('odtype');
			$type = postVar('dtype');
			if ($type == '') doError(_PROFILE_ACTION_NO_TYPE);
			$valuearray = array(
								'type'=>postVar('dtype'),
								'flength'=>intPostVar('flength'),
								'fsize'=>intPostVar('fsize'),
								'fformat'=>postVar('fformat'),
								'fwidth'=>intPostVar('fwidth'),
								'fheight'=>intPostVar('fheight'),
								'ffilesize'=>intPostVar('ffilesize'),
								'ffiletype'=>str_replace(',',';',postVar('ffiletype')),
								'foptions'=>postVar('foptions'),
								'fvalidate'=>postVar('fvalidate')
								);
			if ($otype == $type) {
				$this->updateTypeDef($type, $valuearray);
			}
			else if ($this->typeExists($otype)) {
				$this->addTypeDef($type, $valuearray);
				$this->delTypeDef($otype);
			}
			else {
				$this->addTypeDef($type, $valuearray);
			}
			$destURL = $CONF['PluginURL'] . "profile/index.php?showlist=types&dtype=$type&safe=true&status=2";
			header('Location: ' . $destURL);
			break;
		case 'update':
			/* Actions for members go here (type='update')*/
			// Check if the POST is done by the right member
			$destURL = serverVar('HTTP_REFERER');
			if ($member->id == postVar('memberid')) {
				$memberid = $member->id;
				$vpass = postVar('verifypassword');
				$opass = postVar('oldpassword');

				// first make sure all required fields are set
				$ismissing = 0;
				$missing = array();
				// Loop through all POST vars
				for ($i=0;$i<count($key);$i++) {
					// Process all vars except action, type, name, submit, memberid
					if (($key[$i] != 'action') && ($key[$i] != 'type') && ($key[$i] != 'name') && ($key[$i] != 'submit') && ($key[$i] != 'memberid')) {
						$field = $key[$i];
						$value = postVar($key[$i]);
						if ($this->getFieldAttribute($field,'required') && $value == '') {
							$missing[$ismissing] = $this->getFieldAttribute($field,'flabel');
							$ismissing += 1;
						}
					}
				}
				if ($ismissing) {
					$missingerror = _PROFILE_ACTION_REQ_FIELDS."<br />\n<ul>\n";
					foreach ($missing as $mvalue) {
						$missingerror .=  "<li>$mvalue</li>\n";
					}
					doError($missingerror);
				}

				// Loop through all POST vars
				for ($i=0;$i<count($key);$i++) {
					// Process all vars except action, type, name, submit, memberid
					if (($key[$i] != 'action') && ($key[$i] != 'type') && ($key[$i] != 'name') && ($key[$i] != 'submit') && ($key[$i] != 'memberid')) {
						$field = $key[$i];
						$value = postVar($key[$i]);
						if (is_array($value)) {
							$valuearr = $value;
							$value = trim($valuearr[0]);
						}
						if ($this->getFieldAttribute($field,'enabled')) {
							// First the 'special', not user-made fields
							if (in_array($field, $this->nufields)) {
								switch($field) {
								case 'nick':
									if ($value != $member->displayname) {
										if (!isValidDisplayName($value)) {
											doError(_ERROR_BADNAME);
										}
										else if ($member->isNameProtected($value)) {
											doError(_ERROR_NICKNAMEINUSE);
										}
										else {
											$member->setDisplayName($value);
											$member->write();
										}
									}
									break;
								case 'mail':
									if ($value != $member->email) {
										$value = stringStripTags($value);
										if (!isValidMailAddress($value)) {
											doError(_ERROR_BADMAILADDRESS);
										}
										else {
											$member->setEmail($value);
											$member->write();
										}
									}
									break;
								case 'realname':
									if ($value != $member->realname) {
										$value = stringStripTags($value);
										$member->setRealName($value);
										$member->write();
									}
									break;
								case 'url':
									if ($value != $member->url) {
										$value = stringStripTags($value);
                                        $value = $this->prepURL($value);
										if (($this->validUrl($value)) || ($value == '')) {
											$member->setURL($value);
											$member->write();
										}
										else {
											doError(_PROFILE_ACTION_BAD_URL);
										}
									}
									break;
								case 'notes':
									if ($value != $this->notes) {
										$value = stringStripTags($value);
										$member->setNotes($value);
										$member->write();
									}
									break;
								case 'password':
									if ($value != '') {
										if ($member->checkPassword($opass)) {
											if ($value == $vpass) {
												if ($this->_validate_passwd($value,$this->pwd_min_length, $this->pwd_complexity)) {
													$member->setPassword($value);
													$member->write();
												}
												else {
													$pnverror = _PROFILE_ACTION_BAD_PWD_VALID."<br />";
													$pnverror .= "<ul><li>$this->pwd_min_length - "._PROFILE_ACTION_BAD_PWD_ML."</li>\n";
													$pnverror .= "<li>$this->pwd_complexity - "._PROFILE_ACTION_BAD_PWD_COMP."</li></ul>\n";
													doError($pnverror);
												}
											}
											else doError(_PROFILE_ACTION_BAD_PWD_MATCH);
										}
										else doError(_PROFILE_ACTION_BAD_PWD);
									}
									break;
								case 'verifypassword':
									break;
								case 'oldpassword':
									break;
								}
							} // end if part of if field is a special nucleus field (nufield)
							// Oops, we didn't find a thing :| Time to look for the right type:
							else {
								$type = $this->getFieldAttribute($field,'ftype');
								if ($type == 'textarea') {
									$value = nl2br($value);
                                    $allowedTags = strtolower($this->getFieldAttribute($field,'foptions'));
									$value = $this->_myStringStripTags($value,'<br>'.$allowedTags);
								}
								else {
									$value = stringStripTags($value);
								}
								$value = addslashes($value);

								switch($type) {
								case 'text':
									$value = substr($value,0,$this->getFieldAttribute($field,'flength'));
									if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
										sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$value' WHERE field='".addslashes($field)."' AND memberid=$memberid");
									}
									else {
										sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$value','0')");
									}
									break;
								case 'number':
									$value = substr($value,0,$this->getFieldAttribute($field,'flength'));
									if (is_numeric($value)) {
										if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
											sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$value' WHERE field='".addslashes($field)."' AND memberid=$memberid");
										}
										else {
											sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$value','0')");
										}
									}
									else {
										if (!$value == '') doError(_PROFILE_ACTION_BAD_NUM);
									}
									break;
								case 'url':
									$value = substr($value,0,$this->getFieldAttribute($field,'flength'));
                                    $value = $this->prepURL($value);
                                    $custProt = $this->getFieldAttribute($field,'foptions');
									if ($this->validUrl($value,$custProt) || $value == '') {
										if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
											sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$value' WHERE field='".addslashes($field)."' AND memberid=$memberid");
										}
										else {
											sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$value','0')");
										}
									}
									else {
										doError(_PROFILE_ACTION_BAD_URL);
									}
									break;
								case 'textarea':
                                    if (strlen($value) > 250) {
                                        $cvalue = substr(trim(chunk_split($value,250,'::')),0,-2);
                                    }
                                    else $cvalue = trim($value);
									$cvaluearr = explode('::',$cvalue);
									$t = 0;
									foreach ($cvaluearr as $tord=>$val) {
										if ($tord > 13) break;
										if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."' AND torder=$tord")) > 0) {
											sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$val' WHERE field='".addslashes($field)."' AND memberid=$memberid AND torder=$tord");
										}
										else {
											sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$val','$tord')");
										}
										$t = $tord;
									}
									sql_query("DELETE FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."' AND torder>$t");
									break;
								case 'mail':
									if (preg_match("/^([a-zA-Z0-9])+([.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-]+)+[a-zA-Z0-9_-]$/",$value) ){
										if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
											sql_query("UPDATE ".sql_table('plugin_profile') ." SET value='$value' WHERE field='".addslashes($field)."' AND memberid=$memberid");
										}
										else {
											sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$value','0')");
										}
									}
									else {
										doError(_ERROR_BADMAILADDRESS);
									}
									break;
								case 'password':
									$value = substr($value,0,$this->getFieldAttribute($field,'flength'));
									if ($value != '') {
										if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
											sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$value' WHERE field='".addslashes($field)."' AND memberid=$memberid");
										}
										else {
											sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$value','0')");
										}
									}
									break;
								case 'dropdown':
									if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
										sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$value' WHERE field='".addslashes($field)."' AND memberid=$memberid");
									}
									else {
										sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$value','0')");
									}
									break;
								case 'radio':
									if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
										sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$value' WHERE field='".addslashes($field)."' AND memberid=$memberid");
									}
									else {
										sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$value','0')");
									}
									break;
								case 'list':
									$value = '';
									$j = 0;
									$valuearr = str_replace(',',';',$valuearr);
									foreach ($valuearr as $val) {
										$value .= ($j == 0 ? '' : ';').$val;
										$j = $j + 1;
									}
									$value = addslashes($value);
									if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
										sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$value' WHERE field='".addslashes($field)."' AND memberid=$memberid");
									}
									else {
										sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$value','0')");
									}
									break;
								case 'date':
									$format = strtoupper($this->getFieldAttribute($field,'fformat'));
									$seps = trim(str_replace(array('D','M','Y'),'',$format));
									$format = str_replace(array($seps{0},$seps{1}),'',$format);
									$value = preg_replace('|[^0-9]|','-',$value);
									$datearr = explode('-',$value);
									if (trim($value) != '') {
										switch($format) {
										case 'DMY':
											$day = $datearr[0];
											$month = $datearr[1];
											$year = $datearr[2];
											break;
										case 'MDY':
											$day = $datearr[1];
											$month = $datearr[0];
											$year = $datearr[2];
											break;
										case 'YMD':
											$day = $datearr[2];
											$month = $datearr[1];
											$year = $datearr[0];
											break;
										case 'YDM':
											$day = $datearr[1];
											$month = $datearr[2];
											$year = $datearr[0];
											break;
										default:
											$day = $datearr[1];
											$month = $datearr[0];
											$year = $datearr[2];
											break;
										}


										if (($year > 1850) && ($year <= date("Y")) && ($month > 0) && ($month < 13) && ($day > 0) && ($day < 32)) {
											if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
												sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$day-$month-$year' WHERE field='".addslashes($field)."' AND memberid=$memberid");
											}
											else {
												sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'$field','$day-$month-$year','0')");
											}
										}
										else {
											doError(_PROFILE_ACTION_BAD_DATE." : $format, "._PROFILE_ACTION_BAD_DATE_HELP);
										}
									}
									break;
								} // End innerswitch
							} // end else not a nufield
						} // end if $key not among list of other post vars
					} // end if field is enabled
				} // end for loop through post vars

				// Now, let's handle the (possible) file uploads
				$file = array_keys($_FILES);

				for($i=0;$i<count($file);$i++) {
					$field = $file[$i];
					if (!$this->getFieldAttribute($field,'enabled')) {
						doError(_PROFILE_ACTION_BAD_FILE_FIELD);
					}
					$filesize = $_FILES[$field]['size'];
					$type = $_FILES[$field]['type'];
					$name = $_FILES[$field]['name'];
					$tmp_name = $_FILES[$field]['tmp_name'];
					$extention = $this->showExtention($name);


					if (intval($filesize) > 1 && trim($tmp_name) != '') {

						//Check size
						$max_filesize = intval($this->getFieldAttribute($field,'ffilesize'));
						if ($max_filesize == 0) $max_filesize = intval($CONF['MaxUploadSize']);

						if ($filesize > $max_filesize) {
							doError(_PROFILE_ACTION_BAD_FILE_SIZE . $this->getFieldAttribute($field,'ffilesize')/1024 . ' kB');
						}

						// Check Type
						$allowed_types = explode(';',str_replace(' ','',$this->getFieldAttribute($field,'ffiletype')));
						if (count($allowed_types) == 1 && trim($allowed_types[0]) == '') $allowed_types = explode(',',str_replace(' ','',$CONF['AllowedTypes']));

						$sw_allowed = 0;

						if (in_array($extention,$allowed_types)) {
								$sw_allowed = 1;
						}

						if ($sw_allowed != 1) {
							doError(_PROFILE_ACTION_BAD_FILE_TYPE.": ".implode(' ',$allowed_types));
						}

						// Check size if upload is an image

						if (strpos($type,'image') !== false) {
							$size = getimagesize($tmp_name);
							$width = $size[0];
							$height = $size[1];
							$maxwidth = $this->getFieldAttribute($field,'fwidth');
							$maxheight = $this->getFieldAttribute($field,'fheight');
							if ($maxwidth < $width || $maxheight < $height) {
								doError(_PROFILE_ACTION_BAD_FILE_IMGSIZE .": $maxwidth * $maxheight pixels. ". _PROFILE_ACTION_BAD_FILE_IMGSIZE_YOU." $width * $height pixels.");
							}
						}

						// Copy the file
						$newname = $member->id . ".$field.$extention";
						copy ($tmp_name, $DIR_MEDIA.$newname) or doError(_PROFILE_ACTION_BAD_FILE_COPY);
                        // chmod uploaded file
                        $oldumask = umask(0000);
                        @chmod($DIR_MEDIA.$newname, 0644);
                        umask($oldumask);
                        //prep for database write
						$newname = addslashes($newname);
						$field = addslashes($field);

						// Add file location to db
						if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid='".addslashes($memberid)."' AND field='".addslashes($field)."'")) > 0) {
							sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$newname' WHERE field='".addslashes($field)."' AND memberid='".addslashes($memberid)."'");
						}
						else {
							sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'$field','$newname','0')");
						}
					} // end if filesize <1
				} // end for loop through files
			} // end if postvar('memberid') == $member->id

			$destURL = serverVar('HTTP_REFERER');
			$pgparts = explode('?',$destURL);
			$paramarr = explode('&',$pgparts[1]);
			$newparams = '';
			foreach ($paramarr as $p) {
				if (strpos($p,"status=") === false && strpos($p,"edit=") === false) {
					$newparams .= "$p&";
				}
			}
			$newparams .= "status=1";
			//$destURL = str_replace(array("?status=1","&status=1","?edit=1","&edit=1"),'',$destURL);
/*
			if (strpos($destURL,'?') === false)
				$destURL = $destURL.'?status=1';
			else
				$destURL = $destURL.'&status=1';
*/
			$destURL = $pgparts[0].'?'.$newparams;
			header("Location:" . $destURL);
			break;
		default:
			doError(_PROFILE_ACTION_UNKNOWN);
		} // end actiontype switch
	} // end function

// some functions for admin area
	// get the field defs for a given field (or all for $fieldname == '') as mysql result
	function getFieldDef($fieldname = '') {
		$fieldname = addslashes($fieldname);
		if ($fieldname == '') $where = ' ORDER BY fname ASC';
		else $where = " WHERE fname='$fieldname'";
		$pres = sql_query("SELECT * FROM ".sql_table('plugin_profile_fields').$where);
		return $pres;
	}

	// get the field type defs for a given type (or all for $typename == '') as mysql result
	function getTypeDef($typename = '') {
		$typename = addslashes($typename);
		if ($typename == '') $where = ' ORDER BY type ASC';
		else $where = " WHERE type='$typename'";
		$pres = sql_query("SELECT * FROM ".sql_table('plugin_profile_types').$where);
		return $pres;
	}

	// update the field defs for a given field
	function updateFieldDef($fieldname = '', $valuearray = array()) {
		if ($fieldname == '') {
			doError(_PROFILE_ACTION_NO_FIELD);
		}
		else {
			$existing = $this->fieldExists($fieldname);
			if ($existing) {
				$fieldname = addslashes($fieldname);
				$where = " WHERE fname='$fieldname'";
				$pquery = "UPDATE ".sql_table('plugin_profile_fields')." SET ";
				$i = 0;
				foreach ($valuearray as $key=>$value) {
					if ($key != 'fname') {
						$pquery .= ($i == 0 ? '' : ', ')."$key='".addslashes($value)."'";
						$i += 1;
					}
				}
				$pquery .= $where;
				sql_query($pquery);
			}
		}
	}

	// add a field def for a new field
	function addFieldDef($fieldname = '', $oldfieldname = '', $valuearray = array()) {
		if ($fieldname == '') {
			doError(_PROFILE_ACTION_NO_FIELD);
		}
		else {
			$existing = $this->fieldExists($fieldname);
			if (!$existing) {
				$pquery = "INSERT INTO ".sql_table('plugin_profile_fields')." ";
				$i = 0;
				$fs = '';
				$vs = '';
				foreach ($valuearray as $key=>$value) {
					$fs .= ($i == 0 ? '' : ', ')."$key";
					$vs .= ($i == 0 ? '' : ', ')."'".addslashes($value)."'";
					$i += 1;
				}
				$pquery .= "($fs) VALUES($vs)";
				sql_query($pquery);
				// now we need to update member profile data
				if ($oldfieldname != '') {
					$pquery = "UPDATE ".sql_table('plugin_profile')." SET field='".addslashes($fieldname)."' WHERE field='".addslashes($oldfieldname)."'";
					sql_query($pquery);
				}
			}
		}
	}

	// delete a field def
	function delFieldDef($fieldname = '') {
		if ($fieldname == '') {
			doError(_PROFILE_ACTION_NO_FIELD);
		}
		else {
			$existing = $this->fieldExists($fieldname);
			if ($existing) {
				$fieldname = addslashes($fieldname);
				$where = " WHERE fname='$fieldname'";
				$pquery = "DELETE FROM ".sql_table('plugin_profile_fields');
				$pquery .= $where;
				sql_query($pquery);
				// these line should delete the user data for the deleted field maybe conditional based on option
				$pquery = "DELETE FROM ".sql_table('plugin_profile')." WHERE field='$fieldname'";
				sql_query($pquery);
			}
		}
	}

	// update the type defs for a given type
	function updateTypeDef($typename = '', $valuearray = array()) {
		if ($typename == '') {
			doError(_PROFILE_ACTION_NO_TYPE);
		}
		else {
			$existing = $this->typeExists($typename);
			if ($existing) {
				$typename = addslashes($typename);
				$where = " WHERE type='$typename'";
				$pquery = "UPDATE ".sql_table('plugin_profile_types')." SET ";
				$i = 0;
				foreach ($valuearray as $key=>$value) {
					if ($key != 'type') {
						$pquery .= ($i == 0 ? '' : ', ')."$key='".addslashes($value)."'";
						$i += 1;
					}
				}
				$pquery .= $where;
				sql_query($pquery);
			}
		}
	}
	//update form config
	function updateConfig($value) {
		$value = addslashes($value);
		sql_query("DELETE FROM ".sql_table('plugin_profile_config')." WHERE csetting='editprofile'");
		sql_query("INSERT INTO ".sql_table('plugin_profile_config')." VALUES('editprofile','$value')");
	}

	function fieldExists($fieldname = '') {
		if ($fieldname == '') {
			return 0;
		}
		else {
			return mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile_fields')." WHERE fname='".addslashes($fieldname)."'"));
		}
	}


	function typeExists($typename = '') {
		if ($typename == '') {
			return 0;
		}
		else {
			return mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile_types')." WHERE type='".addslashes($typename)."'"));
		}
	}

	function _deleteMemberData($mid = 0){
		$pquery = "DELETE FROM ".sql_table('plugin_profile')." WHERE memberid='$mid'";
		sql_query($pquery);
	}

	function getValue($memberid, $field) {
		$result = sql_query("SELECT value FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."' ORDER BY torder ASC");
		$value = '';
		if (mysql_num_rows($result) > 0) {
			while ($valobj = mysql_fetch_object($result)) {
				$value .= $valobj->value;
			}
		}
		return $value;
	}

	function getAvatar($memberid) {
		$variable = $this->getValue($memberid,'avatar');
        if ($variable == '') $variable = $this->default['file']['default'];
		return $CONF['MediaURL'].$variable;
	}

    function getConfigValue($field) {
		$result = sql_query("SELECT cvalue FROM ".sql_table('plugin_profile_config')." WHERE csetting='".addslashes($field)."'");
		$value = '';
		if (mysql_num_rows($result) > 0) {
			$valobj = mysql_fetch_object($result);
			$value = $valobj->cvalue;
		}
		return $value;
	}

	function getFieldAttribute($field,$attribute) {
		$query = "SELECT ".$attribute.", ftype FROM ".sql_table('plugin_profile_fields')." WHERE fname='".$field."'";
		$resa = sql_query($query);
		if (mysql_num_rows($resa) == 0) return '';
		$res = mysql_fetch_assoc($resa);
		$result = $res[$attribute];
		$field_type = $res['ftype'];
		if (in_array($result, array('','0')) && !in_array($attribute, array('fname','flabel','ftype','required','enabled','fdefault','fpublic'))) {
			$query = "SELECT ".$attribute." FROM ".sql_table('plugin_profile_types')." WHERE type='".$field_type."'";
			$res = sql_query($query);
			$result = mysql_result($res,0);
		}
		return $result;
	}

	 function safeAddress($emailAddress, $theTitle='Mail user', $xhtml=1, $isItSafe=0) {
		// Version 1.5 - by Dan Benjamin - http://www.hivelogic.com/
		// set $isItSafe = 1 to get escaped HTML, 0 for normal HTML
		// set $xhtml = 1 if you want your page to be valid for XHTML 1.x
		// Modified by Tim Broddin to better suit his needs
		// and again by ftruscot to change the $endresult to not be link

		$ent = "";
		$userName = "";
		$domainName = "";

		for ($i = 0; $i < strlen($emailAddress); $i++) {
			$c = substr($emailAddress, $i, 1);
			if ($c == "@") {
				$userName = $ent;
				$ent = "";
			}
			else {
				$ent .= "&#" . ord($c) . ";";
			}
		}

		$domainName = $ent;

		if ($xhtml == 1) {
			//$endResult = "<a href=\"mailto:$userName@$domainName\" title=\"$theTitle\">$userName@$domainName</a>";
			$endResult = "$userName@$domainName";
		}
		else {
			//$endResult = "<a href=\"mailto:$userName@$domainName\" title=\"$theTitle\">$userName@$domainName</a>";
			$endResult = "$userName@$domainName";
		}
		if ($isItSafe) {
			return(htmlentities($endResult));
		}
		else {
			return($endResult);
		}
	}

	function validUrl($url, $custProtocols = '') {
        $cprots = explode(';',str_replace(',',';',$custProtocols));
        $cprots = array_merge($cprots,$this->allowedProtocols);
        if ( in_array(substr($url, 0, intval(strpos($url,'://'))), $cprots) ) {
			return true;
		}
	}

    function prepURL($url) {
        if (trim($url) == '') return '';
		if (strpos($url,'://') === false) $url = 'http://'.$url;
		if (in_array(substr($url,-1,1),array('&','?'))) $url = substr($url,0,-1);
		$url = preg_replace('|[^a-z0-9-~+_.?#=&;,/:@%]|i', '', $url);
		return $url;
	}

	function showExtention($filename) {
		$ext = explode(".", $filename);
		$extention = $ext[sizeof($ext)-1];
		return $extention;
	}

	function my_array_combine($keys, $values) {
		if (! function_exists('array_combine')) {
			foreach($keys as $key) $out[$key] = array_shift($values);
			return $out;
		}
		else {
			return array_combine($keys, $values);
		}
	}

	function _getAuthorFromItemId($iid) {
		global $manager;
		if ($manager->existsItem(intval($iid),0,0)) {
			$query = 'SELECT iauthor FROM '.sql_table('item').' WHERE inumber=' . intval($iid);
			$res = sql_query($query);
			$arr = mysql_fetch_assoc($res);
			return intval($arr['iauthor']);
		}
	}

	function _getMemberIdFromName($mname) {
		global $member;
		if ($member->isNameProtected($mname)) {
			$result = sql_query("SELECT mnumber FROM ".sql_table(member)." WHERE mname='" . addslashes($mname) . "'");
			$mid = mysql_result($result,'mnumber');
			return intval($mid);
		}
		else return 0;
	}

	function _validate_passwd($passwd,$minlength = 6,$complexity = 0) {
		$minlength = intval($minlength);
		$complexity = intval($complexity);
		if (strlen($passwd) < $minlength) return false;

		$ucchars = "[A-Z]";
		$lcchars = "[a-z]";
		$numchars = "[0-9]";
		$ochars = "[-~!@#$%^&*()_+=,.<>?:;|]";
		$chartypes = array($ucchars, $lcchars, $numchars, $ochars);
		$tot = array(0,0,0,0);
		$i = 0;
		foreach ($chartypes as $value) {
			$tot[$i] = preg_match("/".$value."/", $passwd);
			$i = $i + 1;
		}

		if (array_sum($tot) >= $complexity) return true;
		else return false;
	}

	// my modification of stringStripTags that will allow tags to be excluded
	function _myStringStripTags ($string,$except) {
		$string = preg_replace("/<del[^>]*>.+<\/del[^>]*>/isU", '', $string);
		$string = preg_replace("/<script[^>]*>.+<\/script[^>]*>/isU", '', $string);
		$string = preg_replace("/<style[^>]*>.+<\/style[^>]*>/isU", '', $string);
		//$string = str_replace('>', '> ', $string);
		//$string = str_replace('<', ' <', $string);
		$string = strip_tags($string,$except);

		$string = preg_replace("/\s+/", " ", $string);
		$string = trim($string);
		return $string;
	}

	// function to change <br /> tags back to newlines for display in textareas
	function _br2nl($text) {
        // this reg expr below is messing with th <b> tag as well!!!
		/*$text = trim(preg_replace('|[<][b][r]?\s*?\/??>|i', "\n", $text));*/
		$text = preg_replace("/<br[^>]*>/isU", "\n", $text);
		$text = trim(str_replace("\n ", "\n", $text));
		return $text;
		//return str_replace("<br />", "\n", $text);
	}

	function restrictViewer() {
		global $member, $memberinfo, $manager;
		if (!$this->getFieldAttribute('privacylevel','enabled')) return 0;
		if (isset($memberinfo)) {
			$privlevel = $this->getValue($memberinfo->getID(),'privacylevel');
		}
		else $privlevel = 0;
		if ($privlevel == '' || $privlevel == 0) return 0;
		if ($privlevel == 1 ) {
			if ($member->isLoggedIn()) return 0;
			else return 1;
		}
		if ($privlevel == 2) {
			if ($manager->pluginInstalled('NP_Friends')) {
				$plug_friends =& $manager->getPlugin('NP_Friends');
				if (isset($plug_friends) && $plug_friends->isFriend($memberinfo->getID(),$member->getID())) return 0;
				else return 1;
			}
			else {
                if ($member->isLoggedIn()) return 0;
                else return 1;
            }        }

	}

}
?>