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

/**************************************************************
 *     Special thanks to Jani (jiippana)                                                            *
 **************************************************************/
class NP_LDAPAuth extends NucleusPlugin {

	function getName() { return 'LDAPAuth'; }

	function getAuthor()  {	return 'Frank Truscott';	}

	function getURL()   { return 'http://revcetera.com/ftruscot';	}

	function getVersion() {	return '0.6'; }

	function getDescription() {
		return 'Permits authentication against an LDAP server.';
	}
	
	function getMinNucleusVersion() { return 340; }

	function supportsFeature($what)	{
		switch($what) {
		case 'SqlTablePrefix':
			return 1;
		/*case 'HelpPage':
			return 1;*/
		default:
			return 0;
		}
	}

	function getTableList() { return array(); }
	function getEventList() { return array('CustomLogin'); }
	
	function install() {
		global $CONF;

// Need to make some options
		$this->createOption('save_options', 'Save Options on uninstall?', 'yesno','yes');
		$this->createOption('enable_ldapauth', 'Enable LDAP Auth', 'yesno','no');
		$this->createOption('host_1', 'Host, including port and protocol, to LDAP server', 'text','ldap://ldap.example.com:389');
		$this->createOption('search_dn_1', 'DN of read-capable account to search for user DN', 'text','cn=nucleusreader,cn=users,dc=example,dc=com');
		$this->createOption('search_pwd_1', 'password used by the search account', 'text', '');
		$this->createOption('base_dn_1', 'Base DN where searches should begin: ', 'text', 'dc=example,dc=com');
		$this->createOption('user_objectclass_1', 'name of ldap object class for user entries:', 'text', '*');
		$this->createOption('custom_filter_1', 'Advanced. Leave blank if not sure. LDAP query used to find user DN. Use %LOGIN% for user-entered login name. e.g. (&(objectClass=person)(uid=%LOGIN%)):', 'textarea', '');
		$this->createOption('ldap_proto_1', 'LDAP Protocol version to use: ', 'text', '3');
		$this->createOption('username_attr_1', 'name of ldap attribute storing the user login name:', 'text', 'uid');
		$this->createOption('display_attr_1', 'LDAP attribute to use as Nucleus display name: ', 'text', 'uid');
		$this->createOption('realname_attr_1', 'LDAP attribute to use as Nucleus real name: ', 'text', 'cn');
		$this->createOption('email_attr_1', 'LDAP attribute to use as Nucleus email address: ', 'text', 'mail');
		$this->createOption('team_to_join', 'Comma separated list of blogids to which user should be added as team member: ', 'text', '0');
		$this->createOption('allow_admin', 'Are Nucleus admin users permitted to login using LDAP: ', 'yesno', 'no');
		
		$ot_result = sql_query("SHOW TABLES LIKE '%".sql_table('plug_ldapauth_save_options')."%'");
		if ($ot_result) {
			$so_query = "SELECT * FROM ".sql_table('plug_ldapauth_save_options');
			$savedopt = mysql_fetch_object(mysql_query($so_query));
			$this->setOption('enable_ldapauth',$savedopt->enable_ldapauth);
			$this->setOption('host_1',$savedopt->host_1);
			$this->setOption('search_dn_1',$savedopt->search_dn_1);
			$this->setOption('search_pwd_1',$savedopt->search_pwd_1);
			$this->setOption('base_dn_1',$savedopt->base_dn_1);
			$this->setOption('user_objectclass_1',$savedopt->user_objectclass_1);
			$this->setOption('custom_filter_1',$savedopt->custom_filter_1);
			$this->setOption('ldap_proto_1',$savedopt->ldap_proto_1);
			$this->setOption('username_attr_1',$savedopt->username_attr_1);
			$this->setOption('display_attr_1',$savedopt->display_attr_1);
			$this->setOption('realname_attr_1',$savedopt->realname_attr_1);
			$this->setOption('email_attr_1',$savedopt->email_attr_1);
			$this->setOption('team_to_join',$savedopt->team_to_join);
			$this->setOption('allow_admin',$savedopt->allow_admin);
			sql_query('DROP TABLE IF EXISTS '.sql_table('plug_ldapauth_save_options'));
		}

	}
	
	function unInstall() {
		// if requested, delete the data table
		if ($this->getOption('save_options') == 'yes')	{
			//save options
			sql_query("CREATE TABLE IF NOT EXISTS ".sql_table('plug_ldapauth_save_options').
				" (enable_ldapauth varchar(256) NOT NULL,".
				" host_1 varchar(256) NOT NULL,".
				" search_dn_1 varchar(256) NOT NULL,".
				" search_pwd_1 varchar(256) NOT NULL,".
				" base_dn_1 varchar(256) NOT NULL,".
				" user_objectclass_1 varchar(256) NOT NULL,".
				" custom_filter_1 text NOT NULL,".
				" ldap_proto_1 varchar(256) NOT NULL,".
				" username_attr_1 varchar(256),".
				" display_attr_1 varchar(256),".
				" realname_attr_1 varchar(256),".
				" email_attr_1 varchar(256),".
				" team_to_join varchar(256) NOT NULL,".
				" allow_admin varchar(256) NOT NULL".
				" ) TYPE=MyISAM;");

			sql_query("INSERT INTO ".sql_table('plug_ldapauth_save_options')
				." (enable_ldapauth, host_1, search_dn_1, search_pwd_1, base_dn_1, "
				."user_objectclass_1, custom_filter_1, ldap_proto_1, username_attr_1, "
				."display_attr_1, realname_attr_1, email_attr_1, team_to_join, allow_admin)"
				." VALUES ('".addslashes($this->getOption('enable_ldapauth'))."'"
							 .", '".addslashes($this->getOption('host_1'))."'"
							 .", '".addslashes($this->getOption('search_dn_1'))."'"
							 .", '".addslashes($this->getOption('search_pwd_1'))."'"
							 .", '".addslashes($this->getOption('base_dn_1'))."'"
							 .", '".addslashes($this->getOption('user_objectclass_1'))."'"
							 .", '".addslashes($this->getOption('custom_filter_1'))."'"
							 .", '".addslashes($this->getOption('ldap_proto_1'))."'"
							 .", '".addslashes($this->getOption('username_attr_1'))."'"
							 .", '".addslashes($this->getOption('display_attr_1'))."'"
							 .", '".addslashes($this->getOption('realname_attr_1'))."'"
							 .", '".addslashes($this->getOption('email_attr_1'))."'"
							 .", '".addslashes($this->getOption('team_to_join'))."'"
							 .", '".addslashes($this->getOption('allow_admin'))."')"
				  );
		}
	}
	
	function init() {
				
		$this->enable_ldapauth = $this->getOption('enable_ldapauth');
		$this->host = array($this->getOption('host_1'));
		$this->search_dn = array($this->getOption('search_dn_1'));
		$this->search_pwd = array($this->getOption('search_pwd_1'));
		$this->base_dn = array($this->getOption('base_dn_1'));
		$this->user_objectclass = array(strtolower($this->getOption('user_objectclass_1')));
		$this->custom_filter = array($this->getOption('custom_filter_1'));
		$this->ldap_proto = array($this->getOption('ldap_proto_1'));
		$this->username_attr = array(strtolower($this->getOption('username_attr_1')));
		$this->display_attr = array(strtolower($this->getOption('display_attr_1')));
		$this->realname_attr = array(strtolower($this->getOption('realname_attr_1')));
		$this->email_attr = array(strtolower($this->getOption('email_attr_1')));
		$this->team_to_join = array($this->getOption('team_to_join'));
		$this->allow_admin = $this->getOption('allow_admin');
	}
	/*function hasAdminArea() { return 1; }*/

	function event_CustomLogin(&$data) {
		//login,password,success,allowlocal
		if ($this->enable_ldapauth == 'yes') {
			$lgn =& $data['login'];
			$pwd =& $data['password'];
			//$data['allowlocal'] = 0;
			// here could do anything to check another system (db, through LDAP, etc...) for valid username and password.
			// if valid, could then update nucleus member profile from the store, or maybe create a member if one doesn't exist yet
			// 
			/*
			if ($pwd == 'fred') {
				$data['success'] = 1;
			}
			*/
			$objfilter = "(objectclass=".$this->user_objectclass[0].")";
			$ldapconn = ldap_connect($this->host[0]);
			if ($ldapconn === false) {
				ACTIONLOG::add(INFO, 'Failed to connect to LDAP server: ' . $this->host[0]);
				ldap_unbind($ldapconn);
				return;
			}
			if ( ! ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_proto[0]) ) {
				ACTIONLOG::add(INFO, 'Failed to set protocol version to: ' . $this->ldap_proto[0] );
			}

			// many php related forums suggest using line below for MS Active Directory (AD) 2003 Server
			if (! ldap_set_option($ldapconn, LDAP_OPT_REFERARLS, 0) ) {
				// ACTIONLOG::add(INFO, 'Failed to set opt referrals to 0.');
			}

			$bd = ldap_bind($ldapconn, $this->search_dn[0], $this->search_pwd[0]);

			if ($bd === false) {
				ACTIONLOG::add(INFO, 'Failed to Bind to LDAP server as : ' . $this->search_dn[0]);
				ldap_unbind($ldapconn);
				return;
			}
			$dn = $this->base_dn[0];
			$attrs = array($this->username_attr[0]);
			$filter = "(".$this->username_attr[0]."=".$lgn.")";
			$sfilter = "(&$objfilter$filter)";
			if (!trim($this->custom_filter[0]) == '') {
				$sfilter = trim(str_replace(array('%LOGIN%','%login%'),array($lgn,$lgn),$this->custom_filter[0]));
				$sfilter = urlencode($sfilter);
				$sfilter = trim(str_replace(array("%0D%0A"),array(''),$sfilter));
				$sfilter = urldecode($sfilter);
			}

			$search = ldap_search($ldapconn, $dn, $sfilter, $attrs);

			if ($search === false) {
				ACTIONLOG::add(INFO, 'Failed to find LDAP user with '.$this->username_attr[0].' of : ' . $lgn);
				ldap_unbind($ldapconn);
				return;
			}

			$entries = ldap_get_entries($ldapconn, $search);

			if ($entries["count"] == 1) {
				$userdn = $entries[0]['dn'];
			} 
			else {
				ldap_unbind($ldapconn);
				return;				
			}

			ldap_unbind($ldapconn);

			$ldapconn = ldap_connect($this->host[0]);
			if ($ldapconn === false) {
				ACTIONLOG::add(INFO, 'Failed to connect to LDAP server: ' . $this->host[0]);
				ldap_unbind($ldapconn);
				return;
			}
			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_proto[0]);

			// many php related forums suggest using line below for MS Active Directory (AD) 2003 Server
			if (! ldap_set_option($ldapconn2, LDAP_OPT_REFERARLS, 0) ) {
				// ACTIONLOG::add(INFO, 'Failed to set opt referrals to 0.');
			}

			$bd = ldap_bind($ldapconn, $userdn, $pwd);
			if ($bd === false) {
				ACTIONLOG::add(INFO, 'Failed to Bind to LDAP server as : ' . $userdn);
				ldap_unbind($ldapconn);
				return;
			}

			$attrs = array($this->username_attr[0],$this->display_attr[0],$this->realname_attr[0],$this->email_attr[0]);
			$search = ldap_read($ldapconn, $userdn, $objfilter, $attrs);

			if ($search === false) {
				ACTIONLOG::add(INFO, 'Failed to find required LDAP attributes for : ' . $userdn);
				ldap_unbind($ldapconn);
				return;
			}

			$entries = ldap_get_entries($ldapconn, $search);

			if ($entries["count"] == 1) {
				$userdn = $entries[0]['dn'][0];
				$displayname = $entries[0][$this->display_attr[0]][0];
				$realname = $entries[0][$this->realname_attr[0]][0];
				$emailaddress = $entries[0][$this->email_attr[0]][0];
			}
			else {
				ldap_unbind($ldapconn);
				return;
			}
			ldap_unbind($ldapconn);
			
			//normalize the displayname
			$displayname = $this->_normalizeDisplayName($displayname);
			//create user and add to teams if needed
			if (!MEMBER::exists($displayname) && isValidMailAddress($emailaddress) && !$this->_emailExists($emailaddress)) {
				if ((MEMBER::create($displayname, $realname, md5($pwd), $emailaddress, '', 0, 1, '')) !== 1) {
					ACTIONLOG::add(INFO, 'Failed to create LDAP member: ' . $displayname);
					return;
				}
				$mem = MEMBER::createFromName($displayname);
				$mid = intval($mem->getID());
				if (intval($this->team_to_join)) {
					$teamlist = explode(',',str_replace(' ','',$this->team_to_join[0]));
					foreach ($teamlist as $value) {
						$value = intval($value);
						if ($value) {
							sql_query("INSERT INTO  ".sql_table('team')." (tblog,tmember) VALUES ($value,$mid)");
						}
					}
				}
			}
			else {
				//maybe catch here and try to find account with same email address and realname, set displayname to this, or generate new displayname
				$mem1 = MEMBER::createFromName($displayname);
				if ($mem1->isAdmin() && $this->allow_admin != 'yes') {
					ACTIONLOG::add(INFO, 'Admin users cannot login to LDAP account: ' . $displayname);
					return; //all admin access must be local unless allowed
				}
				if (strtolower($mem1->getEmail()) != strtolower($emailaddress)) {
					$res = sql_query("SELECT mnumber, mname, madmin FROM ".sql_table('member')." WHERE memail='".addslashes($emailaddress)."'");
					if (intval(mysql_num_rows($res)) == 1) {
						$row = mysql_fetch_object($res);
						$displayname = $row->mname;
						if ($row->madmin && $this->allow_admin != 'yes') {
							ACTIONLOG::add(INFO, 'Admin users cannot login to LDAP account: ' . $displayname);
							return; //all admin access must be local unless allowed
						}
					}
					elseif (!$this->_emailExists($emailaddress)) {
						if (getNucleusVersion() < 350) $displayname = substr($displayname,0,14);
						else $displayname = substr($displayname,0,30);
						$i = 0;
						$cont = true;
						while ($cont) {
							$displayname .= $i;
							if (!MEMBER::exists($displayname)) {
								if ((MEMBER::create($displayname, $realname, md5($pwd), $emailaddress, '', 0, 1, '')) !== 1) {
									ACTIONLOG::add(INFO, 'Failed to create LDAP member: ' . $displayname);
									return;
								}
								$cont = false;
								$mem = MEMBER::createFromName($displayname);
								$mid = intval($mem->getID());
								if (intval($this->team_to_join)) {
									$teamlist = explode(',',str_replace(' ','',$this->team_to_join[0]));
									foreach ($teamlist as $value) {
										$value = intval($value);
										if ($value) {
											sql_query("INSERT INTO  ".sql_table('team')." (tblog,tmember) VALUES ($value,$mid)");
										}
									}
								}
							}
							$i = $i + 1;
						}						
					}
					else {
						ACTIONLOG::add(INFO, 'Failed to create LDAP member: ' . $displayname);
						return;
					}
				}
			}
			$data['login'] = $displayname;
			$data['success'] = 1;

		}
	}
	
	function _normalizeDisplayName($name) {
		$name = trim($name);
		$pattern = '/[^a-zA-Z0-9 ]/';
		$replacement = '';
		$result = preg_replace($pattern,$replacement,$name);
		if (getNucleusVersion() < 350) $result = substr($result,0,16);
		else $result = substr($result,0,32);
		return $result;
	}
	
	function _emailExists($email) {
		$email = trim($email);
		return (quickQuery("SELECT COUNT(*) as result FROM ".sql_table('member')." WHERE memail='".addslashes($email)."'"));
	}
}
?>