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
  v2.11 -- 7th release of version 2 adds the following to 2.1 version
    * fix bug in getAvatar for default image retrieval
    * change CREATE TABLE queries to use TYPE=MyISAM instead of ENGINE=MyISAM for compatibility with mysql < 4.1
    * add configurability to display format for most field types
    * fixes bug in redirect after profile edit for certain php configurations.
  v2.12 -- 8th release of version 2 adds the following to 2.11 version
    * fixes bug in redirect for some fancy url schemes when using editlink
	* adds date() like formatting to date fields
  v2.13 -- 9th release of version 2 adds the following to 2.12 version
    * more fixes to bug in redirect for some fancy url schemes when using editlink
	* adds charset info to editprofile.php page (thanks, Shi)
    * adds %VALUE%, %FIELD%, %MEMBER%, %ID% variables to format.
    * permits site admins to edit all user profiles, except passwords. (thanks, Shi)
  v2.14 -- 10th release of version 2 adds the following to 2.13 version
    * fix getAvatar function (add global $CONF;)
  v2.15 -- 11th release of version 2 adds the following to 2.14 version
    * allow for use on custom skinparts (or at least doesn't forbid its use)
    * allow longer, textarea fields (config using file size field in field/type definition)
    * allow param4 as %CAT%. matches username to category desc
    * allow param4 as %BLOG%. matches username to blog shortname
    * fix bug in textarea fields where space at begin or end of chunk are lost.
    * when using custom format, nothing is displayed if requested value is null.
  v2.16 -- 12th release of version 2 adds the following to 2.15 version
    * allow custom formatting of core nucleus member fields (mail, url, nick, realname, notes)
    * add format option to handle case where value is null (ie show this if value is null).
    * fix bug where you couldn't blank out a previously entered date field.
    * add option for deny message when user can't view email address
	* add formatting options to mail type to allow custom formatting of actual address. 4 new format vars
	  for mail types: %ADDRESS% (full address), %USERNAME% (part of address to left of @), %TLD% (Top-Level Domain, part right of last .),
	  and %SITENAME% (the middle part of the address, after the @ and before the last .)
    * add formatting option to mail type to allow customized @ and . replacements in mail address. %ADDRESS(R)%. see help for format.
    * add ticket functions to comply with nucleuscms v3.3 JP
  v2.17 -- 13th release of version 2 adds the following to 2.16 version
    * add option to edit profile from admin area member page
  v2.18 -- 14th release of version 2 adds the following to 2.17 version
    * fix the form on admin area member page
    * now can work in all skin types including error and search
    * use $CONF['ActionURL'] to set form action url
    * adds closeform to special field types, and moves all hidden fields to the startform special field.
    * fix bug on entry form where date fields displaying extra input fields when date value is blank.
  v2.19 -- 15th release of version 2 adds the following to 2.18 version
    * modify PostRegister event and doAction method to recognize more generic registration methods beyond creataccount.html (testing for NP_NewAccount)
    * modify output of radio fields to use labels for xhtml compliance
    * some syntax improvement to help.html for xhtml compliance
  v2.19.01 -- 16th Release of version 2 adds the following to version 2.19
    * fix headers already sent errors when user registering from createaccount.php in version 3.3x
    * add tab on admin page for sample createaccount.php file for adding Profile fields to registration.
  v2.20 -- 17th Release of version 2 adds the following to version 2.19.01
    * keep lastUpdated field (date) - thanks david_again
    * Make skinvar for memberlist using template to call in different fields from profile - thanks pheser
	* Add memberlistpager skinvar for paging memberlist.
    * rename fvalidate column to fformatnull.
    * caching all profile values for a given member to lessen number of db queries per page.
	  *  caching brought the number of queries for my test member page, displaying 18 profile fields from 233 queries to 19 queries
  v2.21 --18th Release of version 2 adds the following to version 2.20
    * fix: add plugin_profile_templates table to getTableList() method for backup
    * fix: double form open code on editprofile page
    * fix: formatting near top of editprofile page
    * add: PostProfileUpdate event for wessite (allows external plugin to subscribe to perform actions each time profile is updated by user)
    * add: editprofileheader config value to allow custom text to be displayed above the form on the editprofile page. (thanks pheser)
  v2.22 -- 19th release of version 2 adds following to version 2.21
    * fix: logout after updating profile in certain circumstances (thanks wessite)
    * add: enhance memberlist to allow sorting by memberid (can use to show newest members)
    * add: enhance memberlist to all displaying only members on given blog team
  v2.22.01 -fix release of 2.22
	* fix errors in init() method when installing a fresh install.
  v2.22.02 -fix release of 2.22.01
	* fix errors in how lastupdated field stored.
  v2.23 -- 20th release of version 2 adds the following to v 2.22.02
          * add API events for other plugins to add data variables to memberlist templates
  v2.23.01 -- fix release of v 2.23
          * fix bug where not normalizing forder and fpublic as integers causing mysql errors in certain situations
  v2.24 -- 21st release of version 2 adds the following to 2.23.01
          * add doIf() method to allow using profile fields as conditionals. if(Profile,field(ME),>=value)
		     (ME) after field name forces the check on field value for logged in user, omit to allow regular selction (memberinfo, authorid, member)
		     >= before value indicates operation to use, valid are =,<,>,<=,>=,!= with default being =
			 if field is isme, then checks if current user is browsing own profile
			 if field is iseditmode, then checks to see if in edit mode and on member details page
         * add regfieldlist template type to allow other field lists to be used for registrations other than at createaccount.php file
		 in registration code, change the type from createaccount.php to name of your regfieldlist in the calls to RegistrationFormExtraFields event
		 * memberlist orderby field can now be fieldname(value)|sort to list only members with fieldname==value
		 * memberlist orderby field can now be of form fieldname(value)|sort|fieldname2|sort2, 
			but fieldname2 is only valid if mail,nick,realname,memberid,url,notes
			fieldname2|sort2 set second sort key/order
  v2.24.01
	* fix registration bug where admin getting logged out when create member accounts
  v2.25 -- 22nd release of version 2 adds the following to 2.24.01
	* add myprofile special field to display links to current member's profile on any pages.
  v2.26 -- 23rd release of version 2 adds the following to 2.25
	* add resizeimage option to fields of type file to resize images to given width/height
  v2.27 -- 24th release of version 2 adds the following to 2.26
	* enhance memberlist feature with special memberlevel field type for sorting member list by level/points according to NP_MemberLevel
	* make NOT blog option to list all members not on given blog team, i.e !blogname
		
*
[FUTURE]

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
    var $allowedProtocols = array("http","https"); // protocols that will be allowed in url fields
	var $restrictView = 0;
    var $specialfields = array('startform','endform','status','editlink','submitbutton','editprofile','closeform','memberlist','memberlistpager','myprofile');
	var $profile_types = array();
	var $profile_fields = array();
	var $profile_values = array();
	var $member_values = array();
	//var $profile_templates = array();
	var $template_types = array('memberlist','regfieldlist');
	var $mllist_count = array(0,0,0);

	function getName() { return 'Profile Plugin'; }

	function getAuthor()  {	return 'Tim Broddin | Edmond Hui (admun) | Frank Truscott';	}

	function getURL()   { return 'http://revcetera.com/ftruscot';	}

	function getVersion() {	return '2.27'; }

	function getDescription() {
        if (!$this->_optionExists('email_public_deny') && $this->_optionExists('email_public')) {
            $this->createOption('email_public_deny', _PROFILE_OPT_EMAIL_PUBLIC_DENY, 'text',_PROFILE_OPT_EMAIL_PUBLIC_DENY_TEXT);
        }
		return 'Gives each member a customisable profile.';
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

	function getTableList() { return array(sql_table('plugin_profile'), sql_table('plugin_profile_fields'), sql_table('plugin_profile_types'), sql_table('plugin_profile_config'),sql_table('plugin_profile_templates')); }
	function getEventList() { return array('QuickMenu','PostDeleteMember','MemberSettingsFormExtras','PostRegister','RegistrationFormExtraFields'); }

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
        $this->createOption('email_public_deny', _PROFILE_OPT_EMAIL_PUBLIC_DENY, 'text',_PROFILE_OPT_EMAIL_PUBLIC_DENY_TEXT);

// create needed tables
		sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plugin_profile').
					" ( `memberid` int(11),
					  `field` varchar(255),
					  `value` varchar(255),
					  `torder` tinyint(2) NOT NULL default '0',
					  KEY `member` (`memberid`),
					  KEY `field` (`field`)) TYPE=MyISAM");

		sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plugin_profile_fields').
					" ( `fname` varchar(255) NOT NULL,
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
					  `fformatnull` varchar(255),
					  `forder` int(11) NOT NULL default '0',
                      `fdefault` varchar(255),
                      `fpublic` tinyint(2) NOT NULL default '0',
					  PRIMARY KEY (`fname`)) TYPE=MyISAM");

		sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plugin_profile_types').
					" ( `type` ENUM('date','dropdown','file','list','mail','number','password','radio','text','textarea','url') NOT NULL,
					  `flength` int(11) NOT NULL default '0',
					  `fsize` int(11) NOT NULL default '0',
					  `fformat` varchar(255),
					  `fwidth` int(11) NOT NULL default '0',
					  `fheight` int(11) NOT NULL default '0',
					  `ffilesize` int(11) NOT NULL default '0',
					  `ffiletype` varchar(255),
					  `foptions` text,
					  `fformatnull` varchar(255),
					  PRIMARY KEY (`type`)) TYPE=MyISAM");

        sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plugin_profile_config').
					" ( `csetting` varchar(255) NOT NULL,
					  `cvalue` text,
					  PRIMARY KEY (`csetting`)) TYPE=MyISAM");

		sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plugin_profile_templates').
					" ( `tname` varchar(255) NOT NULL,
					  `ttype` varchar(255) NOT NULL,
					  `tbody` text,
					  PRIMARY KEY (`tname`),
					  UNIQUE KEY `tname` (`tname`)  ) TYPE=MyISAM");

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

// this is to update tables from v 2.1x to 2.20
		if ($this->getDbVersion() < 220) {
			$pres = sql_query("SHOW COLUMNS FROM ".sql_table('plugin_profile_fields')." LIKE 'fformatnull'");
			if (!mysql_num_rows($pres)) {
				sql_query("ALTER TABLE ".sql_table('plugin_profile_fields')." CHANGE `fvalidate` `fformatnull` varchar(255)");
			}
			$pres = sql_query("SHOW COLUMNS FROM ".sql_table('plugin_profile_types')." LIKE 'fformatnull'");
			if (!mysql_num_rows($pres)) {
				sql_query("ALTER TABLE ".sql_table('plugin_profile_types')." CHANGE `fvalidate` `fformatnull` varchar(255)");
			}
			// change format of date storage
			$pres = sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE field IN(SELECT fname FROM "
					.sql_table('plugin_profile_fields')." WHERE ftype='date')");
			if (mysql_num_rows($pres)) {
				while($row = mysql_fetch_assoc($pres)) {
					$date = $row['value'];
					$membid = $row['memberid'];
					$field = $row['field'];
					if (strpos($date,'-') === False) {
						$date = $this->_mySubstr($date,0,2).'-'.$this->_mySubstr($date,2,2).'-'.$this->_mySubstr($date,4,4);
					}
					$datearr = explode('-',$date);
					$day = $datearr[0];
					$month = $datearr[1];
					$year = $datearr[2];
					if (strlen($month) == 1) $month = "0".$month;
					if (strlen($day) == 1) $day = "0".$day;
					$newdate = "$year-$month-$day";
					if (strlen($day) <= 2 && strlen($year) == 4)
						sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$newdate' WHERE memberid=$membid AND field='$field'");
				}
			}
			$this->setDbVersion(220);
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
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
				  'fformatnull'=>'',
				  'forder'=>0,
                  'fdefault'=>'0',
                  'fpublic'=>1),
			array('fname'=>'lastupdated',
				  'flabel'=>_PROFILE_LABEL_LASTUPDATED,
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
				  'fformatnull'=>'',
				  'forder'=>0,
                  'fdefault'=>'',
                  'fpublic'=>0)
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
					.$value['fformatnull']."','"
                    .$value['forder']."','"
                    .$value['fdefault']."','"
					.$value['fpublic']."')");
			}
		}
// fill in the plugin_profile_types table
		$types = array(
			array('type'=>'text','flength'=>255,'fsize'=>40,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fformatnull'=>''),
			array('type'=>'textarea','flength'=>8,'fsize'=>35,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>5000,'ffiletype'=>'','foptions'=>'','fformatnull'=>''),
			array('type'=>'mail','flength'=>60,'fsize'=>25,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fformatnull'=>''),
			array('type'=>'file','flength'=>255,'fsize'=>25,'fformat'=>'','fwidth'=>64,'fheight'=>64,'ffilesize'=>50000,'ffiletype'=>'jpg;gif;png;jpeg','foptions'=>'','fformatnull'=>''),
			array('type'=>'list','flength'=>255,'fsize'=>40,'fformat'=>'ul-profilelist','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fformatnull'=>''),
			array('type'=>'password','flength'=>25,'fsize'=>25,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fformatnull'=>''),
			array('type'=>'dropdown','flength'=>255,'fsize'=>1,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fformatnull'=>''),
			array('type'=>'date','flength'=>25,'fsize'=>25,'fformat'=>'D-M-Y','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fformatnull'=>''),
			array('type'=>'url','flength'=>255,'fsize'=>40,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fformatnull'=>''),
			array('type'=>'number','flength'=>25,'fsize'=>25,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fformatnull'=>''),
			array('type'=>'radio','flength'=>255,'fsize'=>25,'fformat'=>'','fwidth'=>0,'fheight'=>0,'ffilesize'=>0,'ffiletype'=>'','foptions'=>'','fformatnull'=>''),
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
					.$value['fformatnull']."')");
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
submitbutton
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
submitbutton
mail
icq
msn
url
endform
[/1]
[2]
startform
[h3]Bio
submitbutton
bio
resume
endform
[/2]
[3]
startform
[h3]Interests
submitbutton
favoritesite
hobbies
secret
notes
endform
[/3]
[4]
password
[/4]
# In the future you will be able to add forms for NP_Profile extension plugins.
# The means employed to specify and retrieve the code for these forms is not yet determined.
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

		$templates = array(
			array('tname'=>'default',
				'ttype'=>'memberlist',
				'tbody'=>'<li><a href="%memberlink%" title="%nick%"><img src="%avatar%" alt="%nick%" style="width:40px"/> %nick%</a></li>')
		);
		foreach ($templates as $value) {
			if (mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile_templates')." WHERE tname='".$value['tname']."'")) == 0) {
				sql_query("INSERT INTO ". sql_table('plugin_profile_templates')
					." VALUES ('".$value['tname']."','"
					.$value['ttype']."','"
					.$value['tbody']."')");
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

		if (mysql_num_rows(sql_query("SHOW TABLES LIKE '%".sql_table('plugin_profile_types')."%'"))) {
			$query = "SELECT * FROM ".sql_table('plugin_profile_types');
			$res = sql_query($query);
			if ($res) {
				while ($row = mysql_fetch_assoc($res)) {
					$typearr = array();
					foreach ($row as $key=>$value) {
						$typearr[$key] = $value;
					}
					$this->profile_types[$row['type']] = $typearr;
				}
			}

			$query = "SELECT * FROM ".sql_table('plugin_profile_fields');
			$res = sql_query($query);
			if ($res) {
				while ($row = mysql_fetch_assoc($res)) {
					$typearr = array();
					foreach ($row as $key=>$value) {
						$typearr[$key] = $value;
					}
					$this->profile_fields[$row['fname']] = $typearr;
				}
			}
		}
		//print_r($this->profile_fields);
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

    function event_PostRegister(&$data) {
        global $_POST,$memberid,$nucleus,$member;	
		if (is_object($member) && intval($member->getID()) > 0 && $member->isAdmin()) {
			$thismember = $data['member'];
			$memberid = $thismember->id;
		}
		else {
			$member = $data['member'];
			$memberid = $member->id;
		}
        
		$nucversion = preg_replace('/[^0-9]/','',$nucleus['version']);
        if (intval($nucversion) < 330 ) $this->doAction('update',1,0);
		else $this->doAction('update',1,1);
    }

    function event_MemberSettingsFormExtras(&$data) {
        //global $CONF, $memberid, $member, $memberinfo;
        $memberinfo =& $data['member'];
        $configlist = str_replace(' ','',$this->getConfigValue('membersettings'));
        $fieldsarr = array_diff(explode(',',$configlist),$this->nufields,$this->specialfields,array(''));
        if (count($fieldsarr) < 1) {
            $fieldsarr = $this->getEnabledFields();
        }
        if (count($fieldsarr) > 0) {
            echo "<h4>NP_Profile</h4>\n";
            $this->doSkinVar('adminmember','startform','','',$memberinfo->getID());
            echo "<table>\n";
            echo '<tr><th colspan="2">Edit Extended Profile</th>'."</tr>\n";
            echo "<tr><td>Edit Extended Profile:</td><td>";
            $this->doSkinVar('adminmember','submitbutton','','',$memberinfo->getID());
            echo "</td></tr>\n";
            foreach ($fieldsarr as $field) {
                if (!in_array($field,$this->nufields)) {
                    echo "<tr><td>";
                    $this->doSkinVar('adminmember',$field,'label','',$memberinfo->getID());
                    echo "</td><td>";
                    $this->doSkinVar('adminmember',$field,'','',$memberinfo->getID());
                    echo "</td></tr>\n";
                }
            }

            echo "<tr><td>Edit Extended Profile:</td><td>";
            $this->doSkinVar('adminmember','submitbutton','','',$memberinfo->getID());
            echo "</td></tr>\n";
            echo "</table>\n";
            $this->doSkinVar('adminmember','closeform','','',$memberinfo->getID());
        }
    }
	
	function doIf($key = '', $value = '') {
		global $CONF, $memberid, $member, $memberinfo, $itemid;
		$result = false;
		$ops1 = array('=','>','<');
		$ops2 = array('>=','<=','!=');
		$op = '=';
		$key = trim($key);
		list($key,$showwho) = explode("(",$key);
		$showwho = strtoupper(str_replace(")","",$showwho));
		$v1 = substr($value,0,1);
		$v2 = substr($value,0,2);
		if (in_array($v2,$ops2)) {
			$op = $v2;
			$value = substr($value,2);
		}
		if (in_array($v1,$ops1)) {
			$op = $v1;
			$value = substr($value,1);
		}
		
		$pmid = 0;
		if ($showwho == 'ME') {
			$pmid = intval($member->getID());
		}
		elseif (isset($memberinfo) && $memberinfo->getID() > 0) {
			$pmid = intval($memberinfo->getID());
		}
		elseif (isset($itemid) && $itemid > 0) {
			$pmid = intval($this->_getAuthorFromItemId($itemid));
		}
		elseif (isset($member) && $member->getID() > 0) {
			$pmid = intval($member->getID());
		}		
		$pmid = intval($pmid);
		if ($pmid < 1) return false;
		
		if (strtolower($key) == 'isme') {
			if($member->getId() > 0 && $member->getId() == $memberinfo->getId()) return true;
			else return false;
		}
		
		if (strtolower($key) == 'iseditmode') {
			$isEdit = false;
			if ((requestVar('edit') == 1) && (isset($memberinfo) && $memberinfo->getID() > 0) && ($member->id == $pmid || $member->isAdmin())) {
				$isEdit = true;
			}
			if ($pmid == 999999999) $isEdit = true;
			return $isEdit;
		}
		
		$val = $this->getValue($pmid,$key);
		switch ($op) {
			case '>':
				$result = ($val > $value);
			break;
			case '<':
				$result = ($val < $value);
			break;
			case '>=':
				$result = ($val >= $value);
			break;
			case '<=':
				$result = ($val <= $value);
			break;
			case '!=':
				$result = ($val != $value);
			break;
			default:
				$result = ($val == $value);
			break;
		}
		return $result;
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
		global $CONF, $memberid, $member, $memberinfo;

		$rawparam4 = $param4;
		if (trim(strtolower($skinType)) == "returnvalue") $returnValue = 1;
		else $returnValue = 0;

		if ($this->_mySubstr($skinType,0,8) == 'template') {
			$tiid = intval(str_replace(array('template','(',')'), array('','',''),$skinType));
			$skinType = 'template';
		}

        $formfieldprefix = '';
		//if (in_array($skinType, array('member','archive','archivelist','item','index','template','comment'))) {
        //if (!in_array($skinType, array('error','search'))) {
        if (!in_array($skinType, array())) {
			if (in_array($param1,$this->specialfields) || $this->getFieldAttribute($param1,'enabled')) {
				$pmid = 0;
                $forceEdit = 0;
                if ($skinType == 'adminmember') {
                    $skinType = 'member';
                    $formfieldprefix = 'plug_profile_';
                    $memberinfo = MEMBER::createFromId(intval($param4));
					if ($param4 == 999999999) $pmid = 999999999;
                    else $pmid = $memberinfo->getID();
                    $forceEdit = 1;
                }

				if ($skinType == 'member' && $param4 != 999999999) {
					$pmid = $memberinfo->getID();
				}

                if (strtoupper($param4) == '%ME%') {
                    if ($member->getID() > 0) {
                        $pmid = $member->getID();
                    }
                    else {
                        return;
                    }
                }

                if (strtoupper($param4) == '%CAT%') {
                    global $catid;
                    $cdesc = quickQuery('SELECT cdesc as result FROM '.sql_table('category').' WHERE catid='.intval($catid));
                    $pmid = $this->_getMemberIdFromName($cdesc);

                    if (!$pmid) return;
                }

                if (strtoupper($param4) == '%BLOG%') {
                    global $blog,$blogid;
                    //$bshort = quickQuery('SELECT bshort as result FROM '.sql_table('blog').' WHERE bnumber='.intval($blogid));
                    $bshort = $blog->bshortname;
                    $pmid = $this->_getMemberIdFromName($bshort);

                    if (!$pmid) return;
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
						if (($param2 != 'label') && (!in_array($param1,$this->specialfields)) ) {
							return;
						}
					}
				}
				$pmid = intval($pmid);

                $isEdit = false;
                if ((requestVar('edit') == 1 || $forceEdit == 1) && $skinType == 'member' && ($member->id == $pmid || $member->isAdmin())) {
                    $isEdit = true;
                }
				if ($pmid == 999999999) $isEdit = true;

				if ($param2 == 'label') {
					$isreq = (bool)$this->getFieldAttribute($param1,'required');
					$bstyle = '';
					$estyle = '';
					if ($isEdit && $isreq) {
						$bstyle = $this->req_emp['start'];
						$estyle = $this->req_emp['end'];
					}
					echo $bstyle.$this->getFieldAttribute($param1,'flabel').$estyle;
				}
				else {

					$this->restrictView = $this->restrictViewer();
                    $formfieldname = $formfieldprefix.$param1;
					if ($pmid > 0 && $pmid != 999999999) {
						if (!array_key_exists($pmid,$this->member_values)) {
							// fill the member_values variable for this member
							$result = sql_query("SELECT * FROM ".sql_table('member')." WHERE mnumber=$pmid");
							while ($memvals = mysql_fetch_assoc($result)) {
								foreach ($memvals as $key=>$value) {
									$this->member_values[$pmid][$key] = $value;
								}
							}
						}
					}
					switch($param1) {
					case 'password':
						if ($isEdit) {
							$size = $this->getFieldAttribute($param1,'fsize');
							$maxlength = $this->getFieldAttribute($param1,'flength');
							echo '<h2>'._PROFILE_SV_CHANGE_PASSWORD.'</h2>';
							echo '<form enctype="multipart/form-data" name="passwordform" action="' . $CONF['ActionURL'] . '" method="post">' . "\n";
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
						if ($isEdit) {
							echo '<form enctype="multipart/form-data" name="profileform" action="' . $CONF['ActionURL'] . '" method="post">' . "\n";
                            echo '<input type="hidden" name="action" value="plugin" />';
							echo '<input type="hidden" name="name" value="Profile" />';
							echo '<input type="hidden" name="type" value="update" />';
							echo '<input type="hidden" name="memberid" value="' . $pmid . '" />' . "\n";
						}
						break;
					case 'endform':
						if ($isEdit) {
							echo '<input type="submit" name="submit" value="'._PROFILE_SUBMIT.'" />' . "\n";
							echo "</form>\n";
						}
						break;
                    case 'closeform':
						if ($isEdit) {
							echo "</form>\n";
						}
						break;
                    case 'submitbutton':
						if (isEdit) {
							echo '<input type="submit" name="submit" value="'._PROFILE_SUBMIT.'" />' . "\n";
						}
						break;
					case 'status':
						if ($skinType == 'member' && ($member->id == $pmid || $member->isAdmin())) {
							if (getVar('status') == 1) {
								echo _PROFILE_SV_STATUS_UPDATED;
							}
						}
						break;
					case 'editlink':
						if ($skinType == 'member' && ($member->id == $pmid || $member->isAdmin())) {
							if ($isEdit) {
                                $editlink = createMemberLink($pmid, '');
                                echo '<form enctype="multipart/form-data" name="editform" action="' . $editlink . '" method="post">' . "\n";
								echo '<input type="hidden" name="edit" value="0" />' . "\n";
                                echo '<input class="profileeditlink" type="submit" name="submit" value="'._PROFILE_SV_EDITLINK_FORM.'" />' . "\n";
                                echo "</form>\n";
							}
							else {
                                $editlink = createMemberLink($pmid, '');
                                echo '<form enctype="multipart/form-data" name="editform" action="' . $editlink . '" method="post">' . "\n";
								echo '<input type="hidden" name="edit" value="1" />' . "\n";
                                echo '<input class="profileeditlink" type="submit" name="submit" value="'._PROFILE_SV_EDITLINK_EDIT.'" />' . "\n";
                                echo "</form>\n";
							}
						}
						break;
					case 'editprofile':
						global $blog;
						$blogid = $blog->getID();
						if ($skinType == 'member' && ($member->id == $pmid || $member->isAdmin())) {
							if ($isEdit) {
								//$editlink = $CONF['PluginURL']."profile/editprofile.php?edit=1";
								//echo '<a class="profileeditlink" href="'.$editlink.'">'._PROFILE_SV_EDITLINK_FORM.'</a>';
							}
							else {
								$editlink = $CONF['PluginURL']."profile/editprofile.php?edit=1&blogid=$blogid&memberid=$pmid";
								echo '<a class="profileeditlink" href="'.$editlink.'">'._PROFILE_SV_EDITLINK_EDIT.'</a>';
							}
						}
						break;
					case 'memberlist':
						$this->displayMemberList($param2,intval($param3),$rawparam4,'');
						break;
					case 'memberlistpager':
						$this->displayMemberListPager();
						break;
/* start of future code for allowing editprofile form to be displayed on special skin part
					case 'editspecial':
						global $blog;
						$blogid = $blog->getID();
						if ($skinType == 'member' && ($member->id == $pmid || $member->isAdmin())) {
							if ($isEdit) {
								//$editlink = $CONF['PluginURL']."profile/editprofile.php?edit=1";
								//echo '<a class="profileeditlink" href="'.$editlink.'">'._PROFILE_SV_EDITLINK_FORM.'</a>';
							}
							else {
								$editlink = $CONF['PluginURL']."profile/editprofile.php?edit=1&blogid=$blogid&memberid=$pmid";
								echo '<a class="profileeditlink" href="'.$editlink.'">'._PROFILE_SV_EDITLINK_EDIT.'</a>';
							}
						}
						break;
*/
					case 'myprofile': 
						//$link = '';
						if ($member->isLoggedIn()) {
							$editlink = createMemberLink($member->getID());
							if (strtolower($param2) == 'editlink') {
                                echo '<form enctype="multipart/form-data" name="editform" action="' . $editlink . '" method="post">' . "\n";
								echo '<input type="hidden" name="edit" value="1" />' . "\n";
                                echo '<input class="profileeditlink" type="submit" name="submit" value="'._PROFILE_SV_EDITLINK_EDIT.'" />' . "\n";
                                echo "</form>\n";
							}
							elseif (strtolower($param2) == 'editprofile') {
								global $blog;
								$blogid = $blog->getID();
								$editlink = $CONF['PluginURL']."profile/editprofile.php?edit=1&blogid=$blogid&memberid=".$member->getID();
								echo '<a class="profileeditlink" href="'.$editlink.'">'._PROFILE_SV_EDITLINK_EDIT.'</a>';
							}
							else {
								echo '<a href="'.$editlink.'" title="'._PROFILE_SV_VIEW_MYPROFILE.'">'._PROFILE_SV_VIEW_MYPROFILE.'</a>';
							}							
						}
						//echo $link;
						break;
					case 'mail':
						if ($this->restrictView && !$this->getFieldAttribute($param1,'fpublic')) break;
						//$result = sql_query("SELECT memail FROM ".sql_table(member)." WHERE mnumber=" . $pmid);
						//$value = mysql_result($result,'memail');
						$value = $this->member_values[$pmid]['memail'];
						$size = $this->getFieldAttribute($param1,'fsize');
						$maxlength = $this->getFieldAttribute($param1,'flength');
						if ($param2 != 'show' && $isEdit) {
							echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
						}
						else {
							$safe_add_arr = $this->safeAddress($value);
							$safe_add = $safe_add_arr['address'];
                            if ($value == '') {
                                $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                $label = $this->getFieldAttribute($param1,'flabel');
                                $safe_add = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%','%ADDRESS%','%USERNAME%','%SITENAME%','%TLD%','%ADDRESS(R)%'), array($value,$label,$value,$param1,$pname,$pmid,$safe_add,$safe_add_arr['username'],$safe_add_arr['sitename'],$safe_add_arr['tld'],$safe_add_arr['address_r']), $formatnull);
                            }
							elseif ($param3 == 'raw') {
								$fstart = '';
								$fend = '';
							}
                            elseif ($param3 == 'link') {
                                $fstart = '<a href="mailto:';
                                $fend = '" title="Member '.$pmid.'">'.$safe_add.'</a>';
                            }
							else {
                                $formatarr = explode("###",$this->getFieldAttribute($param1,'fformat'));
                                $format = $formatarr[0];
                                if (trim($format) !== '' && $value !== '') {
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $at_rep = ' [at] ';
                                    $dot_rep = ' [dot] ';
                                    if (trim($formatarr[1]) != '') {
                                        if (preg_match_all( "#\{(.*?)\}\{(.*?)\}#", trim($formatarr[1]), $rep_matches)) {
                                            $at_rep = $rep_matches[1][0];
                                            $dot_rep = $rep_matches[2][0];
                                        }
                                    }
                                    $safe_add_arr['address_r'] = str_replace(array('@','&#64;','.','&#46;'), array($at_rep,$at_rep,$dot_rep,$dot_rep),$safe_add_arr['address']);
                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%','%ADDRESS%','%USERNAME%','%SITENAME%','%TLD%','%ADDRESS(R)%'), array($value,$label,$value,$param1,$pname,$pmid,$safe_add,$safe_add_arr['username'],$safe_add_arr['sitename'],$safe_add_arr['tld'],$safe_add_arr['address_r']), $format);
                                    $safe_add = $fvalue;
                                    $fstart = '';
                                    $fend = '';
                                }
                                else {
								$fstart = '<a href="mailto:';
								$fend = '" title="Member '.$pmid.'">'.$safe_add.'</a>';
							}
                            }
							$safe_add = $fstart.$safe_add.$fend;
							if ($this->showEmail > 1) {
								$value = $safe_add;
							}
							elseif ($this->showEmail == 1 && $member->isLoggedIn()) {
								$value = $safe_add;
							}
							else {
								$value =  $this->getOption('email_public_deny');
							}
							if (!$returnValue) echo $value;
							else return $value;
						}
						break;
					case 'nick':
						//$result = sql_query("SELECT mname FROM ".sql_table(member)." WHERE mnumber=" . $pmid);
						//$value = mysql_result($result,'mname');
						$value = $this->member_values[$pmid]['mname'];
						$size = $this->getFieldAttribute($param1,'fsize');
						$maxlength = $this->getFieldAttribute($param1,'flength');
						if ($param2 != 'show' && $isEdit) {
							echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
						}
						else {
							if ($param3 == 'raw') {
                            }
                            else {
                                $format = $this->getFieldAttribute($param1,'fformat');
                                if (trim($format) !== '' && $value !== '') {
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $format);
                                    $value = $fvalue;
                                }
                            }
							if (!$returnValue) echo $value;
							else return $value;
						}
						break;
					case 'realname':
						if ($this->restrictView && !$this->getFieldAttribute($param1,'fpublic')) break;
						//$result = sql_query("SELECT mrealname FROM ".sql_table(member)." WHERE mnumber=" . $pmid);
						//$value = mysql_result($result,'mrealname');
						$value = $this->member_values[$pmid]['mrealname'];
						$size = $this->getFieldAttribute($param1,'fsize');
						$maxlength = $this->getFieldAttribute($param1,'flength');
						if ($param2 != 'show' && $isEdit) {
							echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
						}
						else {
							if ($value == '') {
                                $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                $label = $this->getFieldAttribute($param1,'flabel');
                                $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $formatnull);
                                $value = $fvalue;
                            }
                            elseif ($param3 == 'raw') {
                            }
                            else {
                                $format = $this->getFieldAttribute($param1,'fformat');
                                if (trim($format) !== '' && $value !== '') {
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $format);
                                    $value = $fvalue;
                                }
                            }
							if (!$returnValue) echo $value;
							else return $value;
						}
						break;
					case 'url':
						if ($this->restrictView && !$this->getFieldAttribute($param1,'fpublic')) break;
						//$result = sql_query("SELECT murl FROM ".sql_table(member)." WHERE mnumber=" . $pmid);
						//$value = mysql_result($result,'murl');
						$value = $this->member_values[$pmid]['murl'];
						$size = $this->getFieldAttribute($param1,'fsize');
						$maxlength = $this->getFieldAttribute($param1,'flength');
						if ($param2 != 'show' && $isEdit) {
							echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
						}
						else {
							if ($value == '') {
                                $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                $label = $this->getFieldAttribute($param1,'flabel');
                                $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $formatnull);
                                $value = $fvalue;
                            }
                            elseif ($param3 == 'raw') {
								$fstart = '';
								$fend = '';
							}
                            elseif ($param3 == 'link') {
                                $fstart = '<a href="';
                                $fend = '" title="'.$param1.'" >'.$value.'</a>';
                            }
                            elseif ($value !== '')  {
                                $format = $this->getFieldAttribute($param1,'fformat');
                                if (trim($format) !== '') {
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $format);
                                    $value = $fvalue;
                                    $fstart = '';
                                    $fend = '';
                                }
							else {
								$fstart = '<a href="';
								$fend = '" title="'.$param1.'" >'.$value.'</a>';
							}
                            }
							$value = $fstart.$value.$fend;
							if (!$returnValue) echo $value. "\n";
							else return $value;
						}
						break;
					case 'notes':
						if ($this->restrictView && !$this->getFieldAttribute($param1,'fpublic')) break;
						//$result = sql_query("SELECT mnotes FROM ".sql_table(member)." WHERE mnumber=" . $pmid);
						//$value = mysql_result($result,'mnotes');
						$value = $this->member_values[$pmid]['mnotes'];
						$rows = $this->getFieldAttribute($param1,'flength');
						$cols = $this->getFieldAttribute($param1,'fsize');
						if ($param2 != 'show' && $isEdit) {
							echo '<textarea name="' . $param1 . '" cols="' . $cols . '" rows="' . $rows . '">' . $value . '</textarea>' . "\n";
						}
						else {
							if ($value == '') {
                                $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                $label = $this->getFieldAttribute($param1,'flabel');
                                $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $formatnull);
                                $value = $fvalue;
                            }
                            elseif ($param3 == 'raw') {
                                //echo $value;
							}
                            elseif ($value !== '') {
                                $format = $this->getFieldAttribute($param1,'fformat');
                                if (trim($format) !== '') {
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $format);
                                    $value = $fvalue;
                                }
								else {
                                    $value = '<textarea readonly="readonly" cols="' . $cols . '" rows="' . $rows . '">' . $this->_br2nl($value) . '</textarea>' . "\n";
								}
							}
                            if (!$returnValue) echo $value;
							else return $value;
						}
						break;
					default:
						if ($this->restrictView && !$this->getFieldAttribute($param1,'fpublic')) break;
                        //$pobj = mysql_fetch_object(sql_query('SELECT mname as result FROM '.sql_table(member).' WHERE mnumber=' . $pmid));
                        //$pname = $pobj->result;
						$pname = $this->member_values[$pmid]['mname'];
						$type = $this->getFieldAttribute($param1,'ftype');
						switch($type) {
						case 'text':
							$value = $this->getValue($pmid,$param1);
							$maxlength = $this->getFieldAttribute($param1,'flength');
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
							}
							else {
                                if ($value == '') {
                                    $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $formatnull);
                                    $value = $fvalue;
                                }
                                elseif ($param3 == 'raw') {
                                }
                                else {
                                    $format = $this->getFieldAttribute($param1,'fformat');
                                    if (trim($format) !== '' && $value !== '') {
                                        $label = $this->getFieldAttribute($param1,'flabel');
                                        $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $format);
                                        $value = $fvalue;
                                    }
                                }
                                if (!$returnValue) echo $value;
								else return $value;
							}
							break;
						case 'number':
							$value = $this->getValue($pmid,$param1);
							$maxlength = $this->getFieldAttribute($param1,'flength');
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
							}
							else {
                                if ($value == '') {
                                    $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $formatnull);
                                    $value = $fvalue;
                                }
                                elseif ($param3 == 'raw') {
                                }
                                else {
                                    $format = $this->getFieldAttribute($param1,'fformat');
                                    if (trim($format) !== '' && $value !== '') {
                                        $farr = explode('-',$format);
                                        $value = number_format($value,intval($farr[0]),$farr[1],$farr[2]);
                                    }
                                }
								if (!$returnValue) echo $value;
								else return $value;
							}
							break;
						case 'url':
							$value = $this->getValue($pmid,$param1);
							$maxlength = $this->getFieldAttribute($param1,'flength');
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
							}
							else {
								if ($value == '') {
                                    $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $formatnull);
                                    $value = $fvalue;
                                }
                                elseif ($param3 == 'raw') {
									$fstart = '';
									$fend = '';
								}
                                elseif ($param3 == 'link') {
                                    $fstart = '<a href="';
									$fend = '" title="'.$param1.'" >'.$value.'</a>';
                                }
								elseif ($value !== '')  {
                                    $format = $this->getFieldAttribute($param1,'fformat');
                                    if (trim($format) !== '') {
                                        $label = $this->getFieldAttribute($param1,'flabel');
                                        $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $format);
                                        $value = $fvalue;
                                        $fstart = '';
                                        $fend = '';
                                    }
                                    else {
                                        $fstart = '<a href="';
                                        $fend = '" title="'.$param1.'" >'.$value.'</a>';
                                    }
								}
								$value = $fstart.$value.$fend . "\n";
								if (!$returnValue) echo $value. "\n";
								else return $value;
							}
							break;
						case 'textarea':
							$value = str_replace("::"," ",$this->getValue($pmid,$param1));
							$cols = $this->getFieldAttribute($param1,'fsize');
							$rows = $this->getFieldAttribute($param1,'flength');
							if ($param2 != 'show' && $isEdit) {
								echo '<textarea name="' . $param1 . '" cols="' . $cols . '" rows="' . $rows . '">' . $this->_br2nl($value) . '</textarea>' . "\n";
							}
							else {
								if ($value == '') {
                                    $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $formatnull);
                                    $value = $fvalue;
                                }
                                elseif ($param3 == 'raw') {
									//echo $value;
								}
                                elseif ($value !== '') {
                                    $format = $this->getFieldAttribute($param1,'fformat');
                                    if (trim($format) !== '') {
                                        $label = $this->getFieldAttribute($param1,'flabel');
                                        $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $format);
                                        $value = $fvalue;
                                    }
                                    else {
                                        $value = '<textarea readonly="readonly" cols="' . $cols . '" rows="' . $rows . '">' . $this->_br2nl($value) . '</textarea>' . "\n";
                                    }
                                }
                                if (!$returnValue) echo $value;
								else return $value;
							}
							break;
						case 'mail':
							$value = $this->getValue($pmid,$param1);
							$maxlength = $this->getFieldAttribute($param1,'flength');
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="text" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $value . '"/>' . "\n";
							}
							else {
								$safe_add_arr = $this->safeAddress($value);
								$safe_add = $safe_add_arr['address'];
								if ($value == '') {
                                    $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $safe_add = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%','%ADDRESS%','%USERNAME%','%SITENAME%','%TLD%','%ADDRESS(R)%'), array($value,$label,$value,$param1,$pname,$pmid,$safe_add,$safe_add_arr['username'],$safe_add_arr['sitename'],$safe_add_arr['tld'],$safe_add_arr['address_r']), $formatnull);
								}
                                elseif ($param3 == 'raw') {
									$fstart = '';
									$fend = '';
								}
                                elseif ($param3 == 'link') {
                                    $fstart = '<a href="mailto:';
									$fend = '" title="Member '.$pmid.'">'.$safe_add.'</a>';
                                }
								else {
                                    $formatarr = explode("###",$this->getFieldAttribute($param1,'fformat'));
                                    $format = $formatarr[0];
                                    if (trim($format) !== '' && $value !== '') {
                                        $label = $this->getFieldAttribute($param1,'flabel');
                                        $at_rep = ' [at] ';
                                        $dot_rep = ' [dot] ';
                                        if (trim($formatarr[1]) != '') {
                                            if (preg_match_all( "#\{(.*?)\}\{(.*?)\}#", trim($formatarr[1]), $rep_matches)) {
                                                $at_rep = $rep_matches[1][0];
                                                $dot_rep = $rep_matches[2][0];
                                            }
                                        }
                                        $safe_add_arr['address_r'] = str_replace(array('@','&#64;','.','&#46;'), array($at_rep,$at_rep,$dot_rep,$dot_rep),$safe_add_arr['address']);
                                        $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%','%ADDRESS%','%USERNAME%','%SITENAME%','%TLD%','%ADDRESS(R)%'), array($value,$label,$value,$param1,$pname,$pmid,$safe_add,$safe_add_arr['username'],$safe_add_arr['sitename'],$safe_add_arr['tld'],$safe_add_arr['address_r']), $format);
										$safe_add = $fvalue;
                                        $fstart = '';
                                        $fend = '';
                                    }
                                    else {
                                        $fstart = '<a href="mailto:';
                                        $fend = '" title="Member '.$pmid.'">'.$safe_add.'</a>';
                                    }
								}
								$safe_add = $fstart.$safe_add.$fend;
								if ($this->showEmail > 1) {
									$value = $safe_add;
								}
								elseif ($this->showEmail == 1 && $member->isLoggedIn()) {
									$value = $safe_add;
								}
								else {
									$value = $this->getOption('email_public_deny');
								}
								if (!$returnValue) echo $value;
								else return $value;
							}
							break;
						case 'file':
							$value = $this->getValue($pmid,$param1);
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="file" size="' . $size . '" />' . "\n";
							}
							else {
                                $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                if (strlen($value) >= 3) {
									$value = $CONF['MediaURL'] . $value;
								}
                                elseif (trim($formatnull) != '') {
                                    $label = $this->getFieldAttribute($param1,'flabel');
                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $formatnull);
                                    $value = $fvalue;
                                }
								else {
									$value = $this->default['file']['default'];
								}
								if ($param3 == 'image') {
									$fstart = '<img src="';
									$fend = '" alt="'.$param1.'" />';
								}
								elseif ($param3 == 'raw') {
									$fstart = '';
									$fend = '';
								}
								elseif ($param3 == 'link') {
									$fstart = '<a href="';
									$fend = '" title="'.$param1.'" >'.$param1.'</a>';
								}
                                else {
                                    $format = $this->getFieldAttribute($param1,'fformat');
                                    if (trim($format) !== '' && $value !== '') {
                                        $label = $this->getFieldAttribute($param1,'flabel');
                                        $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $format);
                                        $value = $fvalue;
                                        $fstart = '';
                                        $fend = '';
                                    }
                                    else {
                                        $fstart = '<a href="';
                                        $fend = '" title="'.$param1.'" >'.$param1.'</a>';
                                    }
                                }
								$value = $fstart.$value.$fend;
								if (!$returnValue) echo $value. "\n";
								else return $value;
							}
							break;
						case 'password':
							$maxlength = $this->getFieldAttribute($param1,'flength');
							$size = $this->getFieldAttribute($param1,'fsize');
							if ($param2 != 'show' && $isEdit) {
								echo '<input name="' . $param1 . '" type="password" maxlength="' . $maxlength . '" size="' . $size . '" />' . "\n";
							}
							else {
								$value = '********';
								if (!$returnValue) echo $value;
								else return $value;
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
							if ($param2 != 'show' && $isEdit) {
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
                                        if ($param3 == 'raw') {
                                            //change nothing
                                        }
                                        else {
                                            $format = $this->getFieldAttribute($param1,'fformat');
                                            if (trim($format) !== '' && $value !== '') {
                                                $label = $this->getFieldAttribute($param1,'flabel');
                                                $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($opt[0],$label,$opt[1],$param1,$pname,$pmid), $format);
                                                $opt[0] = $fvalue;
                                                $fstart = $estart;
                                                $fend = $eend;
                                            }
                                            else {
                                                //change nothing
                                            }
                                        }

										if (!$returnValue) echo trim($opt[0]) . "\n";
										else return trim($opt[0]);
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
							if ($param2 != 'show' && $isEdit) {
								foreach ($rawoptions as $ropt) {
									$opt = explode("|", $ropt);
									if (count($opt) == 1) $opt[1] = trim($opt[0]);
									if (trim($opt[1]) == $value) {
										echo '<input type="radio" name="' . $param1 . '" value="' . $value . '" checked="checked" id="' . $param1 . trim($opt[1]) . '" /> <label for="' . $param1 . trim($opt[1]) . '">' . trim($opt[0]) . '</label>'. "\n";
									}
									else {
										echo '<input type="radio" name="' . $param1 . '" value="' . trim($opt[1]) . '" id="' . $param1 . trim($opt[1]) . '" /> <label for="' . $param1 . trim($opt[1]) . '">' . trim($opt[0]) . '</label>'. "\n";
									}
								}
							}
							else {
								foreach ($rawoptions as $ropt) {
									$opt = explode("|", $ropt);
									if (count($opt) == 1) $opt[1] = trim($opt[0]);
									if (trim($opt[1]) == $value) {
                                        if ($param3 == 'raw') {
                                            //change nothing
                                        }
                                        else {
                                            $format = $this->getFieldAttribute($param1,'fformat');
                                            if (trim($format) !== '' && $value !== '') {
                                                $label = $this->getFieldAttribute($param1,'flabel');
                                                $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($opt[0],$label,$opt[1],$param1,$pname,$pmid), $format);
                                                $opt[0] = $fvalue;
                                                $fstart = $estart;
                                                $fend = $eend;
                                            }
                                            else {
                                                //change nothing
                                            }
                                        }
										if (!$returnValue) echo trim($opt[0]) . "\n";
										else return trim($opt[0]);
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
							if ($param2 != 'show' && $isEdit) {
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

								if (!$returnValue) echo "$liststart\n";
								if ($numopts) {
									$v = 0;
									foreach ($rawoptions as $ropt) {
										$opt = explode("|", $ropt);
										if (count($opt) == 1) $opt[1] = trim($opt[0]);
										if (in_array(trim($opt[1]),$valuearr)) {
											$v = $v + 1;
											if ($param3 == 'link') {
												$fstart = $estart.'<a href="';
												$fend = '" title="'.$param1.'" >'.trim($opt[1]).'</a>'.$eend;
											}
											elseif ($param3 == 'raw') {
												$fstart = $estart;
												$fend = $eend;
											}
                                            else {
                                                $format = $formatarr[2];
                                                if (trim($format) !== '' && $opt[1] !== '') {
                                                    $label = $this->getFieldAttribute($param1,'flabel');
                                                    $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($opt[0],$label,$opt[1],$param1,$pname,$pmid), $format);
                                                    $opt[0] = $fvalue;
                                                    $fstart = $estart;
                                                    $fend = $eend;
                                                }
                                                else {
                                                    $fstart = $estart;
                                                    $fend = $eend;
                                                }
                                            }
											if (!$returnValue) echo $fstart.trim($opt).$fend . "\n";
											else {
												if ($v > 1) return ", ".trim($opt);
												else return trim($opt);
											}
										}
									}
								}
								else {
									$v = 0;
									foreach ($valuearr as $opt) {
										$v = $v + 1;
										if ($param3 == 'link') {
											$fstart = $estart.'<a href="';
											$fend = '" title="'.$param1.'" >'.trim($opt).'</a>'.$eend;
										}
										elseif ($param3 == 'raw') {
                                            $fstart = $estart;
                                            $fend = $eend;
                                        }
                                        else {
                                            $format = $formatarr[2];
                                            if (trim($format) !== '' && $opt !== '') {
                                                $label = $this->getFieldAttribute($param1,'flabel');
                                                $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($opt,$label,$opt,$param1,$pname,$pmid), $format);
                                                $opt = $fvalue;
                                                $fstart = $estart;
                                                $fend = $eend;
                                            }
                                            else {
                                                $fstart = $estart;
                                                $fend = $eend;
                                            }
                                        }
										if (!$returnValue) echo $fstart.trim($opt).$fend . "\n";
										else {
											if ($v > 1) return ", ".trim($opt);
											else return trim($opt);
										}
									}

								}
								if (!$returnValue) echo "$listend\n";
							}
							break;
						case 'date':
							$format = $this->getFieldAttribute($param1,'fformat');
							$formatarr = explode('?',$format);
							$seps = trim(str_replace(array('D','M','Y'),'',strtoupper($formatarr[0])));
							$formatarr[0] = str_replace(array($seps{0},$seps{1}),'',$formatarr[0]);
							$date = $this->getValue($pmid,$param1);
							if (strpos($date,'-') === False) {
								$date = $this->_mySubstr($date,0,2).'-'.$this->_mySubstr($date,2,2).'-'.$this->_mySubstr($date,4,4);
							}
							$datearr = explode('-',$date);
							if ($date == '' || $date == '--') {
                                $value = '';
                                $formatnull = $this->getFieldAttribute($param1,'fformatnull');
                                $label = $this->getFieldAttribute($param1,'flabel');
                                $fvalue = str_replace(array('%DATA%','%LABEL%','%VALUE%','%FIELD%','%MEMBER%','%ID%'), array($value,$label,$value,$param1,$pname,$pmid), $formatnull);
                                $value = $fvalue;
                                $format = $formatarr[0];
                            }
							else {
								$year = $datearr[0];
								$month = $datearr[1];
								$day = $datearr[2];
                                if ($day == '') $day = date('d');
                                if ($month == '') $month = date('m');
                                if ($year == '') $year = date('Y');
								if ($formatarr[1]) {
									$value = date($formatarr[1], mktime(0,0,0,$month,$day,$year));
                                    $format = $formatarr[0];
                                    if (!in_array($format,array('DMY','MDY','YMD','YDM'))) $format = 'MDY';
								}
								else {
									$format = $formatarr[0];
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
                                            $format = 'MDY';
											break;
									}
								}
							}
							if ($param2 != 'show' && $isEdit) {
								$minput = _PROFILE_MM.' <input name="' . $param1 . '[]" type="text" maxlength="2" size="2" value="' . $month . '"/> ' . "\n";
								$dinput = _PROFILE_DD.' <input name="' . $param1 . '[]" type="text" maxlength="2" size="2" value="' . $day . '"/> ' . "\n";
								$yinput = _PROFILE_YYYY.' <input name="' . $param1 . '[]" type="text" maxlength="4" size="4" value="' . $year . '"/>' . "\n";
                                $dinput = str_replace(array('M','D','Y'),array($minput,$dinput,$yinput),$format);
                                echo $dinput;
                            }
							else {
								if (!$returnValue) echo $value;
								else return $value;
							}
							break;
						} // end switch for for field types
					} // end switch $param1
				} // end else part of if param2 is 'label'
			} // end if field is enabled
		} // end if skintype is one of supported
	} // end doSkinVar()

	function doAction($actionType,$registering = 0,$noredirect = 0) {
		global $CONF, $_POST, $_FILES, $member, $DIR_MEDIA, $HTTP_REFERER, $manager;
		$key = array_keys($_POST);
        if (!$actionType) {
            $actiontype = postVar('type');
        }
        else {
            $actiontype = $actionType;
        }
		$destURL = '';

		switch($actiontype) {
		case 'updatefield':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_PROFILE_ACTION_DENY);
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
								'fformatnull'=>postVar('fformatnull'),
                                'forder'=>intPostVar('forder'),
                                'fdefault'=>postVar('fdefault'),
                                'fpublic'=>intPostVar('fpublic')
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
			header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
		case 'addfield':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_PROFILE_ACTION_DENY);
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
								'fformatnull'=>postVar('fformatnull'),
                                'forder'=>intPostVar('forder'),
                                'fdefault'=>postVar('fdefault'),
                                'fpublic'=>intPostVar('fpublic')
								);
			if ($this->fieldExists($fname)) {
				doError("$fname - "._PROFILE_ACTION_DUPLICATE_FIELD);
			}
			else {
				$this->addFieldDef($fname, '', $valuearray);
			}
			header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
		case 'deletefield':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_PROFILE_ACTION_DENY);
			$destURL = $CONF['PluginURL'] . "profile/index.php?showlist=fields&safe=true&status=3";
			$fname = postVar('fname');
			if ($fname == '') doError(_PROFILE_ACTION_NO_FIELD);
			if (!$this->fieldExists($fname)) {
				doError("$fname - "._PROFILE_ACTION_NOT_FIELD);
			}
			else {
				$this->delFieldDef($fname);
			}
			header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
		case 'updateconfig':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_PROFILE_ACTION_DENY);
			$destURL = $CONF['PluginURL'] . "profile/index.php?showlist=config&safe=true&status=2";

            $cfield = trim(postVar('configtype'));
			$epvalue = postVar($cfield);
			$this->updateConfig($epvalue,$cfield);

			header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
		case 'updatetemplate':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_PROFILE_ACTION_DENY);
			$odtemplate = postVar('otname');
			$tname = postVar('tname');
			if ($tname == '') doError(_PROFILE_ACTION_NO_TEMPLATE);
			if (!isValidDisplayName($tname)) {
				doError(_ERROR_BADNAME);
			}
			$valuearray = array(
								'tname'=>strtolower(postVar('tname')),
								'ttype'=>postVar('ttype'),
								'tbody'=>postVar('tbody')
								);
			if (strtolower($odtemplate) == strtolower($tname)) {
				$this->updateTemplateDef($tname, $valuearray);
			}
			else if ($this->templateExists($odtemplate)) {
				$this->addTemplateDef($tname, $odtemplate, $valuearray);
				$this->delTemplateDef($odtemplate);
			}
			else {
				$this->addTemplateDef($tname, '', $valuearray);
			}
			$destURL = $CONF['PluginURL'] . "profile/index.php?showlist=templates&safe=true&status=2";
			header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
		case 'addtemplate':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_PROFILE_ACTION_DENY);
			$destURL = $CONF['PluginURL'] . "profile/index.php?showlist=templates&safe=true&status=1";
			$tname = postVar('tname');
			if ($tname == '') doError(_PROFILE_ACTION_NO_TEMPLATE);
			if (!isValidDisplayName($tname)) {
				doError(_ERROR_BADNAME);
			}
			$valuearray = array(
								'tname'=>strtolower(postVar('tname')),
								'ttype'=>postVar('ttype'),
								'tbody'=>postVar('tbody')
								);
			if ($this->templateExists($tname)) {
				doError("$tname - "._PROFILE_ACTION_DUPLICATE_TEMPLATE);
			}
			else {
				$this->addTemplateDef($tname, '', $valuearray);
			}
			header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
		case 'deletetemplate':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_PROFILE_ACTION_DENY);
			$destURL = $CONF['PluginURL'] . "profile/index.php?showlist=templates&safe=true&status=3";
			$tname = postVar('tname');
			if ($tname == '') doError(_PROFILE_ACTION_NO_TEMPLATE);
			if (!$this->templateExists($tname)) {
				doError("$tname - "._PROFILE_ACTION_NOT_FIELD);
			}
			else {
				$this->delTemplateDef($tname);
			}
			header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
		case 'updatetype':
			if (!$member->isAdmin() || !$manager->checkTicket()) doError(_PROFILE_ACTION_DENY);
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
								'fformatnull'=>postVar('fformatnull')
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
			header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
		case 'update':
			/* Actions for members go here (type='update')
			 * Check if the POST is done by the right member*/
			$destURL = serverVar('HTTP_REFERER');

            global $memberid;

            //if (postVar('action') != 'createaccount') $memberid = intPostVar('memberid');
            if (!$registering) $memberid = intPostVar('memberid');

			if (intval($member->id) > 0 && ($member->id == $memberid || $member->isAdmin())) {
				//$memberid = $member->id;
                /* following code removed to fix bug where user apparently being logged out after updating in certain circumstances */
				/*
				if ($member->id != $memberid && $member->isAdmin()) {
					$memberobj = MEMBER::createFromId($memberid);
				}
				else {
					$memberobj =& $member;
				}
				*/
				// below is replacement code
				$memberobj = MEMBER::createFromId($memberid);

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
						if (is_array($value)) {
							$valuearr = $value;
							$value = trim($valuearr[0]);
						}
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
									if ($value != $memberobj->displayname) {
										if (!isValidDisplayName($value)) {
											doError(_ERROR_BADNAME);
										}
										else if ($memberobj->isNameProtected($value)) {
											doError(_ERROR_NICKNAMEINUSE);
										}
										else {
											$memberobj->setDisplayName($value);
											$memberobj->write();
										}
									}
									break;
								case 'mail':
									if ($value != $memberobj->email) {
										$value = stringStripTags($value);
										if (!isValidMailAddress($value)) {
											doError(_ERROR_BADMAILADDRESS);
										}
										else {
											$memberobj->setEmail($value);
											$memberobj->write();
										}
									}
									break;
								case 'realname':
									if ($value != $memberobj->realname) {
										$value = stringStripTags($value);
										$memberobj->setRealName($value);
										$memberobj->write();
									}
									break;
								case 'url':
									if ($value != $memberobj->url) {
										$value = stringStripTags($value);
                                        $value = $this->prepURL($value);
										if (($this->validUrl($value)) || ($value == '')) {
											$memberobj->setURL($value);
											$memberobj->write();
										}
										else {
											doError(_PROFILE_ACTION_BAD_URL);
										}
									}
									break;
								case 'notes':
									if ($value != $this->notes) {
										$value = stringStripTags($value);
										$memberobj->setNotes($value);
										$memberobj->write();
									}
									break;
								case 'password':
                                    if ($registering) $value = '';
									if ($value != '') {
										if ($memberobj->checkPassword($opass)) {
											if ($value == $vpass) {
												if ($this->_validate_passwd($value,$this->pwd_min_length, $this->pwd_complexity)) {
													$memberobj->setPassword($value);
													$memberobj->write();
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
									$value = $this->_mySubstr($value,0,$this->getFieldAttribute($field,'flength'));
									if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
										sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$value' WHERE field='".addslashes($field)."' AND memberid=$memberid");
									}
									else {
										sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$value','0')");
									}
									break;
								case 'number':
									$value = $this->_mySubstr($value,0,$this->getFieldAttribute($field,'flength'));
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
									$value = $this->_mySubstr($value,0,$this->getFieldAttribute($field,'flength'));
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
                                        $cvalue = $this->_mySubstr(trim(chunk_split($value,250,'::')),0,-2);
                                    }
                                    else $cvalue = trim($value);
									$cvaluearr = explode('::',$cvalue);
                                    $chunk_limit = intval($this->getFieldAttribute($field,'ffilesize'));
                                    if ($chunk_limit) $chunk_limit = intval($chunk_limit / 250);
                                    else $chunk_limit = 20;
                                    if ($chunk_limit < 1) $chunk_limit = 1;
									$t = 0;
									foreach ($cvaluearr as $tord=>$val) {
                                        if ($this->_mySubstr($val,-1,1) == ' ') {
                                            $val = rtrim($val," ")."::";
                                        }
                                        if ($this->_mySubstr($val,0,1) == ' ') {
                                            $val = "::".ltrim($val," ");
                                        }
                                        // edit number in next line to determine max length in bytes of textarea fields (n x 250)
										if ($tord > $chunk_limit) break;
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
									$value = $this->_mySubstr($value,0,$this->getFieldAttribute($field,'flength'));
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
									$formatarr = explode('?',strtoupper($this->getFieldAttribute($field,'fformat')));
                                    $format = $formatarr[0];
									$seps = trim(str_replace(array('D','M','Y'),'',$format));
									$format = str_replace(array($seps{0},$seps{1}),'',$format);
                                    $datearr = $valuearr;
									if (trim($datearr[0].$datearr[1].$datearr[2]) != '') {
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
                                        if ($day == '') $day = date('d');
                                        if ($month == '') $month = date('m');
                                        if ($year == '') $year = date('Y');

										if (($year > 1000) && ($year <= (date("Y") + 200)) && ($month > 0) && ($month < 13) && ($day > 0) && ($day < 32)) {
											if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
												if (strlen($month) == 1) $month = "0".$month;
												if (strlen($day) == 1) $day = "0".$day;
												sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$year-$month-$day' WHERE field='".addslashes($field)."' AND memberid=$memberid");
											}
											else {
												sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'".addslashes($field)."','$year-$month-$day','0')");
											}
										}
										else {
											if ($field != 'lastupdated') {
												doError(_PROFILE_ACTION_BAD_DATE." : $format, "._PROFILE_ACTION_BAD_DATE_HELP);
											}
										}
									}
                                    else {
                                        if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='".addslashes($field)."'")) > 0) {
                                            sql_query("UPDATE ".sql_table('plugin_profile')." SET value='' WHERE field='".addslashes($field)."' AND memberid=$memberid");
                                        }
                                        else {
                                            sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'$field','','0')");
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
					$foptions = strtolower(trim($this->getFieldAttribute($field,'foptions')));
					if (strpos($foptions,'resizeimage') !== false)
						$resizeit = true;
					else
						$resizeit = false;
					$filesize = $_FILES[$field]['size'];
					$type = $_FILES[$field]['type'];
					$name = $_FILES[$field]['name'];
					$tmp_name = $_FILES[$field]['tmp_name'];
					$extention = $this->showExtention($name);
					if (strpos($type,'image') !== false)
						$isimage = true;
					else {
						$isimage = false;
						$resizeit = false;
					}


					if (intval($filesize) > 1 && trim($tmp_name) != '') {

						//Check size
						$max_filesize = intval($this->getFieldAttribute($field,'ffilesize'));
						if ($max_filesize == 0) $max_filesize = intval($CONF['MaxUploadSize']);

						if ($filesize > $max_filesize && !$resizeit) {
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

						if ($isimage) {
							$size = getimagesize($tmp_name);
							$width = $size[0];
							$height = $size[1];
							$maxwidth = $this->getFieldAttribute($field,'fwidth');
							$maxheight = $this->getFieldAttribute($field,'fheight');
							if (($maxwidth < $width || $maxheight < $height) && !$resizeit) {
								doError(_PROFILE_ACTION_BAD_FILE_IMGSIZE .": $maxwidth * $maxheight pixels. ". _PROFILE_ACTION_BAD_FILE_IMGSIZE_YOU." $width * $height pixels.");
							}
						}

						if ($resizeit) {
							$newname = $memberobj->id . ".$field.jpg";
							$newThumb = $this->CroppedThumbnail($tmp_name,$maxwidth,$maxheight,$extention);
							imagejpeg($newThumb,$DIR_MEDIA.$newname,85);
							@unlink($tmp_name);
							// chmod uploaded file
							$oldumask = umask(0000);
							@chmod($DIR_MEDIA.$newname, 0644);
							umask($oldumask);
						}
						else {
							// Copy the file
							$newname = $memberobj->id . ".$field.$extention";
							copy ($tmp_name, $DIR_MEDIA.$newname) or doError(_PROFILE_ACTION_BAD_FILE_COPY);
							@unlink($tmp_name);
							// chmod uploaded file
							$oldumask = umask(0000);
							@chmod($DIR_MEDIA.$newname, 0644);
							umask($oldumask);
						}
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
				$destURL = serverVar('HTTP_REFERER');
                $pgparts = explode('?',$destURL);
				$paramarr = explode('&',$pgparts[1]);
				$newparams = '';
				foreach ($paramarr as $p) {
					if (strpos($p,"status=") === false && strpos($p,"edit=") === false && strpos($p,"ticket=") === false && trim($p) !== '') {
						$newparams .= "$p&";
					}
				}
				$newparams .= "status=1";
				$destURL = $pgparts[0].'?'.$newparams;
				// stamp the lastUpdated field
				$day = date('d');
				$month = date('m');
				$year = date('Y');
				if(mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid AND field='lastupdated'")) > 0) {
					sql_query("UPDATE ".sql_table('plugin_profile')." SET value='$year-$month-$day' WHERE field='lastupdated' AND memberid=$memberid");
				}
				else {
					sql_query("INSERT INTO ".sql_table('plugin_profile')." VALUES($memberid,'lastupdated','$year-$month-$day','0')");
				}
			} // end if postvar('memberid') == $member->id

			/* call event for other plugins to update something based on changes made here */
			unset($this->profile_values[$memberobj->getID()]);
			$nick = $this->getValue($memberobj->getID(),'nick');
			$manager->notify(
				'PostProfileUpdate',
				array(
					'member' => &$memberobj,
					'profile' => $this->profile_values[$memberobj->getID()]
				)
			);
			/* end code to call PostProfileUpdate event */
			if (!$noredirect) header("Location: " . $destURL);
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

	// get the template defs for a given type (or all for $templatename == '') as mysql result
	function getTemplateDef($templatename = '') {
		$templatename = addslashes($templatename);
		if ($templatename == '') $where = ' ORDER BY ttype ASC,tname ASC';
		else $where = " WHERE tname='$templatename'";
		$pres = sql_query("SELECT * FROM ".sql_table('plugin_profile_templates').$where);
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
				//$fieldname = $fieldname;
				$where = " WHERE fname='".addslashes($fieldname)."'";
				$pquery = "UPDATE ".sql_table('plugin_profile_fields')." SET ";
				$i = 0;
				foreach ($valuearray as $key=>$value) {
					if ($key != 'fname') {
						$pquery .= ($i == 0 ? '' : ', ')."$key='".addslashes($value)."'";
						$i += 1;
						//$this->profile_fields[$fieldname][$key] = $value;
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

	// update the template defs for a given template
	function updateTemplateDef($templatename = '', $valuearray = array()) {
		if ($templatename == '') {
			doError(_PROFILE_ACTION_NO_TEMPLATE);
		}
		else {
			$existing = $this->templateExists($templatename);
			if ($existing) {
				$where = " WHERE tname='".addslashes($templatename)."'";
				$pquery = "UPDATE ".sql_table('plugin_profile_templates')." SET ";
				$i = 0;
				foreach ($valuearray as $key=>$value) {
					if ($key != 'tname') {
						$pquery .= ($i == 0 ? '' : ', ')."$key='".addslashes($value)."'";
						$i += 1;
						//$this->profile_fields[$fieldname][$key] = $value;
					}
				}
				$pquery .= $where;
				sql_query($pquery);

			}
		}
	}

	// add a template def for a new template
	function addTemplateDef($templatename = '', $oldtemplatename = '', $valuearray = array()) {
		if ($templatename == '') {
			doError(_PROFILE_ACTION_NO_TEMPLATE);
		}
		else {
			$existing = $this->templateExists($templatename);
			if (!$existing) {
				$pquery = "INSERT INTO ".sql_table('plugin_profile_templates')." ";
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
			}
		}
	}

	// delete a template def
	function delTemplateDef($templatename = '') {
		if ($templatename == '') {
			doError(_PROFILE_ACTION_NO_TEMPLATE);
		}
		else {
			$existing = $this->templateExists($templatename);
			if ($existing) {
				$templatename = addslashes($templatename);
				$where = " WHERE tname='$templatename'";
				$pquery = "DELETE FROM ".sql_table('plugin_profile_templates');
				$pquery .= $where;
				sql_query($pquery);
			}
		}
	}


	//update form config
	function updateConfig($value,$field) {
		$value = addslashes($value);
        $field = addslashes($field);
		sql_query("DELETE IGNORE FROM ".sql_table('plugin_profile_config')." WHERE csetting='$field'");
		sql_query("INSERT INTO ".sql_table('plugin_profile_config')." VALUES('$field','$value')");
	}

	function fieldExists($fieldname = '') {
		if ($fieldname == '') {
			return 0;
		}
		else {
			if (array_key_exists($fieldname,$this->profile_fields)) return 1;
			return mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile_fields')." WHERE fname='".addslashes($fieldname)."'"));
		}
	}

	function templateExists($templatename = '') {
		if ($templatename == '') {
			return 0;
		}
		else {
			//if (array_key_exists($templatename,$this->profile_templates)) return 1;
			return mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile_templates')." WHERE tname='".addslashes($templatename)."'"));
		}
	}


	function typeExists($typename = '') {
		if ($typename == '') {
			return 0;
		}
		else {
			if (array_key_exists($typename,$this->profile_types)) return 1;
			else return mysql_num_rows(sql_query("SELECT * FROM ".sql_table('plugin_profile_types')." WHERE type='".addslashes($typename)."'"));
		}
	}

	function _deleteMemberData($mid = 0){
        $mid = intval($mid);
		$pquery = "DELETE FROM ".sql_table('plugin_profile')." WHERE memberid='$mid'";
		sql_query($pquery);
	}

	function getValue($memberid, $field) {
        $memberid = intval($memberid);
		if (!array_key_exists($memberid,$this->profile_values)) {
			// fill the profile_values variable for this member
			$result = sql_query("SELECT field,value,torder FROM ".sql_table('plugin_profile')." WHERE memberid=$memberid ORDER BY field ASC, torder ASC");
			while ($valobj = mysql_fetch_object($result)) {
				if ($valobj->torder < 1) {
					$this->profile_values[$memberid][$valobj->field] = $valobj->value;
				}
				else {
					$this->profile_values[$memberid][$valobj->field] .= $valobj->value;
				}
			}
		}
		if (!array_key_exists($memberid,$this->profile_values)) $this->profile_values[$memberid] = array();

		$value = $this->profile_values[$memberid][$field];
		if ($value == '' && $memberid == 999999999) {
			$value = postVar($field);
			$this->profile_values[$memberid][$field] = $value;
		}
		return $value;
	}

	function getAvatar($memberid) {
        global $CONF;
		$variable = $this->getValue($memberid,'avatar');
        if ($variable == '') {
            $variable = $this->default['file']['default'];
        }
        else {
            $variable = $CONF['MediaURL'].$variable;
        }
        return $variable;
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
		if (array_key_exists($field,$this->profile_fields)) {
			$result = $this->profile_fields[$field][$attribute];
			$field_type = $this->profile_fields[$field]['ftype'];
		}
		else {
			$query = "SELECT ".$attribute.", ftype FROM ".sql_table('plugin_profile_fields')." WHERE fname='".addslashes($field)."'";
			$resa = sql_query($query);
			if (mysql_num_rows($resa) == 0) return '';
			$res = mysql_fetch_assoc($resa);
			$result = $res[$attribute];
			$field_type = $res['ftype'];
		}
		if (in_array($result, array('','0')) && !in_array($attribute, array('fname','flabel','ftype','required','enabled','fdefault','fpublic'))) {
			if (array_key_exists($field_type,$this->profile_types)) {
				$result = $this->profile_types[$field_type][$attribute];
			}
			else {
				$query = "SELECT ".$attribute." FROM ".sql_table('plugin_profile_types')." WHERE type='".$field_type."'";
				$res = sql_query($query);
				$result = mysql_result($res,0);
			}
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
		$result_array = array();

		for ($i = 0; $i < strlen($emailAddress); $i++) {
			$c = $this->_mySubstr($emailAddress, $i, 1);
			if ($c == "@") {
				$userName = $ent;
				$ent = "";
			}
			else {
				$ent .= "&#" . ord($c) . ";";
			}
		}

		$domainName = $ent;
		$result_array['username'] = $userName;
		$result_array['domainname'] = $domainName;
		$domain_arr = explode("&#46;",$domainName);
		$result_array['tld'] = array_pop($domain_arr);
		$result_array['sitename'] = implode("&#46;",$domain_arr);

		$endResult = "$userName@$domainName";

		if ($isItSafe) {
			$result_array['address'] = htmlentities($endResult);
		}
		else {
			$result_array['address'] = $endResult;
		}
		return $result_array;
	}

	function validUrl($url, $custProtocols = '') {
        $cprots = explode(';',str_replace(',',';',$custProtocols));
        $cprots = array_merge($cprots,$this->allowedProtocols);
        if ( in_array($this->_mySubstr($url, 0, intval(strpos($url,'://'))), $cprots) ) {
			return true;
		}
	}

    function prepURL($url) {
        if (trim($url) == '') return '';
		if (strpos($url,'://') === false) $url = 'http://'.$url;
		if (in_array($this->_mySubstr($url,-1,1),array('&','?'))) $url = $this->_mySubstr($url,0,-1);
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
		$text = trim(str_replace(array("\n "), array("\n"), $text));
		return $text;
		//return str_replace("<br />", "\n", $text);
	}

	function restrictViewer() {
		global $member, $memberinfo, $manager;
        if ($member->isAdmin()) return 0;
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
            }
        }
	}

	function _mySubstr($str = '',$start = 0, $len = 1) {
		if (function_exists('mb_substr')) {
			return mb_substr($str,$start,$len);
		}
		else {
			return substr($str,$start,$len);
		}
	}

    function _optionExists($optionname) {
        $query = "SELECT oid as result FROM " . sql_table('plugin_option_desc') . " WHERE opid=" . intval($this->plugid)." AND oname='".addslashes($optionname)."'";
        return intval(quickQuery($query));
    }

    function getEnabledFields() {
		$enabledfields = array();
		if (!empty($this->profile_fields)) {
			foreach ($this->profile_fields as $key=>$value) {
				if ($value['enabled'] == 1) $enabledfields[] = $key;
			}
		}
		else {
			$query = "SELECT `fname` FROM ".sql_table('plugin_profile_fields')." WHERE `enabled`=1 ORDER BY `flabel` ASC";
			$result = sql_query($query);
			if (mysql_num_rows($result) > 0 ) {
				while ($row = mysql_fetch_assoc($result)) {
					$enabledfields[] = $row['fname'];
				}
			}
		}
        return $enabledfields;
    }

	function event_RegistrationFormExtraFields(&$data) {
		$fieldlist = '';
		if ($data['type'] != 'createaccount.php') {
			$fieldlist = quickQuery("SELECT tbody as result FROM ".sql_table('plugin_profile_templates')." WHERE ttype='regfieldlist' AND tname='".addslashes(trim($data['type']))."'");
		}
		if (trim($fieldlist) == '') 
				$fieldlist = $this->getConfigValue('registration');
		//$field_array = explode(',',$this->getConfigValue('registration'));
		$field_array = explode(',',$fieldlist);
        foreach ($field_array as $rfield) {
            $rfield = trim($rfield);
            if (!in_array($rfield,array_merge($this->nufields,$this->specialfields)) && $this->getFieldAttribute($rfield,'enabled')) {
                echo $data['prelabel']."\n";
				echo $this->getFieldAttribute($rfield,'flabel').":\n";
                echo $data['postlabel']."\n";
				echo $data['prefield']."\n";
                $this->doSkinVar('adminmember',$rfield,'','',999999999);
                echo $data['postfield']."\n";
            }
        }
	}

	function displayMemberList($templatename='',$amount=0,$orderby='nick|ASC') {
		global $manager;

		$templatebody = quickQuery("SELECT tbody as result FROM ".sql_table('plugin_profile_templates')." WHERE ttype='memberlist' AND tname='".addslashes(trim($templatename))."'");
		if ($templatebody == '') return;
		
		$special_orders = array('memberid','memberlevel');

		$blogteam = '';
		$not = false;
		if (strpos($orderby,';')) {
			$obarr = explode(';',trim($orderby));
			$short = trim($obarr[1]);
			if (substr($short,0,1) == '!') {
				$not = true;
				$short = substr($short,1);
			}
			$orderby = $obarr[0];
			if (trim($short) != '') {
				if (strtolower($short) == 'anyblog') {
					if ($not) $blogteam = " m.mnumber NOT IN (SELECT tmember FROM ".sql_table('team').")";
					else $blogteam = " m.mnumber IN (SELECT tmember FROM ".sql_table('team').")";
				}
				else {$btid = intval(getBlogIdFromName(trim($short)));
					if ($btid > 0) {
						if ($not) $blogteam = " m.mnumber NOT IN (SELECT tmember FROM ".sql_table('team')." WHERE tblog=$btid)";
						else $blogteam = " m.mnumber IN (SELECT tmember FROM ".sql_table('team')." WHERE tblog=$btid)";
					}
				}
			}
		}

		$amount = intval($amount);
		$currentPage = intPostVar('profile_ml_page');
		if ($currentPage < 1) $currentPage = 1;
		if ($amount > 1) {
			if ($currentPage == 1) $offset = 0;
			else $offset = ($currentPage - 1) * $amount;
			$limit = " LIMIT $offset,$amount";
		}
		$ordarr = explode('|',trim($orderby));

		if (strtoupper($ordarr[0]) == 'RANDOM') {
			$ordarr[0] = '';
			$ordarr[1] = 'RANDOM';
		}

		if (strtoupper($ordarr[0]) == 'NEWEST') {
			$ordarr[0] = 'mnumber';
			$ordarr[1] = 'DESC';
		}
		
		$val ='';
		if ($ordarr[0] != '') {
			list($ordarr[0],$val) = explode("(",$ordarr[0]);
			$val = addslashes(strtoupper(str_replace(")","",$val)));
			
			if($this->fieldExists(trim($ordarr[0])) || in_array($ordarr[0],$special_orders)) {
				$ordarr[0] = str_replace(array('mail','nick','realname','url','notes','password','memberid'),array('memail','mname','mrealname','murl','mnotes','mpassword','mnumber'),$ordarr[0]);
			}
			else return;
		}
		switch (strtoupper($ordarr[1])) {
			case 'DESC':
                $theorder = "DESC";
            break;
			case 'RANDOM':
				$theorder = "RAND()";
			break;
            default:
                $theorder = "ASC";
                break;
		}
		if (isset($ordarr[3])) {
			switch (strtoupper($ordarr[3])) {
				case 'DESC':
					$theorder2 = "DESC";
				break;
				case 'RANDOM':
					$theorder2 = "RAND()";
				break;
				default:
					$theorder2 = "ASC";
					break;
			}
		}
		if (isset($ordarr[2])) {
			if($this->fieldExists(trim($ordarr[2])) || in_array($ordarr[2],$special_orders)) {
				$ordarr[2] = str_replace(array('mail','nick','realname','url','notes','password','memberid'),array('m.memail','m.mname','m.mrealname','m.murl','m.mnotes','m.mpassword','m.mnumber'),trim($ordarr[2]));
				if (!in_array($ordarr[2],array('m.memail','m.mname','m.mrealname','m.murl','m.mnotes','m.mpassword','m.mnumber','')))
					$ordarr[2] = '';
			}
			else $ordarr[2] = '';			
		}
		
		if ($ordarr[0] == 'memberlevel') {
			global $manager;
			if (!$manager->pluginInstalled('NP_MemberLevel')) 
				return;
			else 
				$levelplug = $manager->getPlugin('NP_MemberLevel');
			$query = "SELECT m.mnumber as mid FROM ".sql_table('member')." as m";
			if ($blogteam != '') $query .= " WHERE $blogteam";
			$result = sql_query($query);
			$membs = array();
			while ($row = mysql_fetch_assoc($result)) {
				$mid = intval($row['mid']);
				$level = $levelplug->getLevel($mid);				
				if (!isset($val) || $val == '' || strtolower($level['name']) == strtolower($val)) {
					$mvalues = array();
					$mvalues['ml_level'] = $level['name'];
					$mvalues['ml_points'] = $level['points'];
					$mvalues['memberlink'] = createMemberLink($mid);
					$mvalues['memberid'] = $mid;
					foreach ($this->profile_fields as $key=>$value) {
						if ($key != 'password') {
							$mvalues[$key] = $this->doSkinVar('returnValue',$key,'show','raw',$mid);
						}
					}
					$membs[(1000000000 + $level['points']).'.'.(100000000 + $mid)] = $mvalues;
				}
			}
			krsort($membs);
			
			$this->mllist_count[0] = $amount;
			//$this->mllist_count[1] = count($membs);
			$this->mllist_count[2] = 0;
			$currentPage = intPostVar('profile_ml_page');
			if ($currentPage < 1) $currentPage = 1;
			if ($amount > 1) {
				if ($currentPage == 1) $offset = 0;
				else $offset = ($currentPage - 1) * $amount;
			}
			if ((count($membs) - $offset) > $amount) $this->mllist_count[1] = $amount;
			else $this->mllist_count[1] = 0;
			$k = 0;
			$j = 0;
			foreach ($membs as $key=>$value) {			
				if ($k >= $offset) {
					$mid = $value['memberid'];
					$mvalues = $value;
					/* call event for other plugins to add variables to memberlist template */
					$manager->notify(
						'PreProfileMemberListItem',
						array(
							'memberid' => $mid,
							'listitem' => &$mvalues
						)
					);
					/* end code to call PostProfileUpdate event */

					$fromarr = array();
					$toarr = array();
					foreach ($mvalues as $key=>$value) {
						$fromarr[] = '%'.$key.'%';
						$toarr[] = $value;
					}
					
					echo str_replace($fromarr,$toarr,$templatebody);
					$j = $j + 1;
				}
				$k = $k + 1;
				if ($j >= $amount) return;
			}
			return;
		}
		
		$query = "SELECT m.mnumber as mid FROM ".sql_table('member')." as m";

		if (in_array($ordarr[0],array('memail','mname','mrealname','murl','mnotes','mpassword','mnumber',''))) {
			if ($blogteam != '') $query .= " WHERE $blogteam";
			if ($theorder == "RAND()") $query .= " ORDER BY $theorder";
			else $query .= " ORDER BY m.".$ordarr[0]." $theorder";
		}
		else {
			$query .= ", ".sql_table('plugin_profile')." as p WHERE";
			if ($blogteam != '') $query .= " $blogteam AND ";
			$query .= " m.mnumber=p.memberid AND p.field='".$ordarr[0]."' AND ".($val ? "p.value='".$val."'" : "p.value<>''");
			if ($theorder == "RAND()") $query .= " ORDER BY $theorder";
			else $query .= " ORDER BY p.value $theorder";
			if (isset($ordarr[2]) && $ordarr[2] != '') $query .= ", ".$ordarr[2]." $theorder2";
		}
		$query .= $limit;
		$result = sql_query($query);

		$this->mllist_count[0] = $amount;
		$this->mllist_count[1] = mysql_num_rows($result);
		if (strtoupper($ordarr[1]) == 'RANDOM') $this->mllist_count[2] = 1;
		else $this->mllist_count[2] = 0;
		while ($row = mysql_fetch_assoc($result)) {
			$mid = intval($row['mid']);
			$mvalues = array();
			$mvalues['memberlink'] = createMemberLink($mid);
			$mvalues['memberid'] = $mid;
			foreach ($this->profile_fields as $key=>$value) {
				if ($key != 'password') {
					$mvalues[$key] = $this->doSkinVar('returnValue',$key,'show','raw',$mid);
				}
			}
			
			/* call event for other plugins to add variables to memberlist template */
			$manager->notify(
				'PreProfileMemberListItem',
				array(
					'memberid' => $mid,
					'listitem' => &$mvalues
				)
			);
			/* end code to call PostProfileUpdate event */

			$fromarr = array();
			$toarr = array();
			foreach ($mvalues as $key=>$value) {
				$fromarr[] = '%'.$key.'%';
				$toarr[] = $value;
			}
			
			echo str_replace($fromarr,$toarr,$templatebody);
		}
	}

	function displayMemberListPager() {
		$currentPage = intPostVar('profile_ml_page');
		if ($currentPage < 1) $currentPage = 1;
		if ($this->mllist_count[0] > 0 && $this->mllist_count[2] < 1 ) {
			echo '<div class="pagerform">'."\n";
			if ($currentPage > 1) {
				echo '<form enctype="multipart/form-data" name="profilepagerprev" action="" method="post" style="display:inline">' . "\n";
				echo '<input type="hidden" name="profile_ml_page" value="'.($currentPage - 1).'" />';
				echo '<input type="submit" name="prev" value="'._PROFILE_PREV.'" />' . "\n";
				echo "</form>\n";
				//echo "&nbsp;";
			}
			if ($this->mllist_count[1] == $this->mllist_count[0]) {
				echo '<form enctype="multipart/form-data" name="profilepagernext" action="" method="post" style="display:inline">' . "\n";
				echo '<input type="hidden" name="profile_ml_page" value="'.($currentPage + 1).'" />';
				echo '<input type="submit" name="next" value="'._PROFILE_NEXT.'" />' . "\n";
				echo "</form>\n";
			}
			echo "</div>\n";
		}
	}

	function getDbVersion() {
		return intval(quickQuery("SELECT cvalue as result FROM ".sql_table('plugin_profile_config')." WHERE csetting='dbversion'"));
	}

	function setDbVersion($version){
		$version = intval($version);
		if (!$version) return;
		else {
			sql_query("DELETE FROM ".sql_table('plugin_profile_config')." WHERE csetting='dbversion'");
			sql_query("INSERT INTO ".sql_table('plugin_profile_config')." VALUES('dbversion','$version')");
		}
	}
	
	function CroppedThumbnail($imgSrc,$thumbnail_width,$thumbnail_height,$ext = '') { //$imgSrc is a FILE - Returns an image resource.
		//getting the image dimensions 
		list($width_orig, $height_orig) = getimagesize($imgSrc); 		
		$ext = strtolower($ext);
		if ($ext == 'jpg' || $ext == 'jpeg') {
			$myImage = @imagecreatefromjpeg($imgSrc);
			$imgtype = 'jpg';
		} else if ($ext == 'png') {
			$myImage = @imagecreatefrompng($imgSrc);
			$imgtype = 'png';
		# Only if your version of GD includes GIF support
		} else if ($ext == 'gif') {
			if (function_exists('imagecreatefromgif')) {
				$myImage = @imagecreatefromgif($imgSrc);
				$imgtype = 'gif';
			}
		} else if ($ext == 'bmp') {
			$myImage = @imagecreatefromwbmp($imgSrc);
			$imgtype = 'bmp';
		}
		
		//$myImage = imagecreatefromjpeg($imgSrc);
		$ratio_orig = $width_orig/$height_orig;
	   
		if ($thumbnail_width/$thumbnail_height > $ratio_orig) {
		   $new_height = $thumbnail_width/$ratio_orig;
		   $new_width = $thumbnail_width;
		} else {
		   $new_width = $thumbnail_height*$ratio_orig;
		   $new_height = $thumbnail_height;
		}
	   
		$x_mid = $new_width/2;  //horizontal middle
		$y_mid = $new_height/2; //vertical middle
	   
		$process = imagecreatetruecolor(round($new_width), round($new_height));
	   
		imagecopyresampled($process, $myImage, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);
		$thumb = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
		imagecopyresampled($thumb, $process, 0, 0, ($x_mid-($thumbnail_width/2)), ($y_mid-($thumbnail_height/2)), $thumbnail_width, $thumbnail_height, $thumbnail_width, $thumbnail_height);

		imagedestroy($process);
		imagedestroy($myImage);
		return $thumb;
	}
}
?>