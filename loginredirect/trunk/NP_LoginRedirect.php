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
 
class NP_LoginRedirect extends NucleusPlugin
{
	function getName()              { return 'Login Redirect';}
	function getAuthor()            { return 'Frank Truscott, yama';}
	function getURL()               { return 'http://wakka.xiffy.nl/loginredirect';}
	function getVersion()           { return '0.40';}
	function getDescription()       { return 'Redirect user to member profile page after successful login';}
	function getMinNucleusVersion() { return 320;}
	function supportsFeature($w)    { return ($w == 'SqlTablePrefix') ? 1 : 0;}
	function getEventList()         { return array('LoginSuccess');}
 
	function install()
	{
		// Need to make some options
		$this->createOption('redirect_enabled', 'Should login redirect be enabled?', 'yesno', 'yes');
		$this->createOption('redirect_dest',    'destination', 'select', 'member_page', 'member profile page|member_page|site top|site_top|custom|custom');
		$this->createOption('redirect_cust',    'Custom destination (full url): (variables see wiki) ', 'text', '');
	}
 
	function event_LoginSuccess(&$data)
	{
		if ($this->getOption('redirect_enabled') == 'yes')
		{
			global $shared, $CONF, $manager;
			$errormessage = '';
			ACTIONLOG::add(INFO, "Login successful for ".$data['member']->displayname." (sharedpc=$shared)");
			if ($CONF['MemberKey'] == '')   $CONF['MemberKey'] = 'member';
			switch ($this->getOption('redirect_dest'))
			{
			case 'member_page':
				$url = createMemberLink($data['member']->id);
				break;
			case 'site_top':
				$url = $CONF['IndexURL'];
				break;
			case 'custom':
				$custom = trim($this->getOption('redirect_cust'));
				$var_arr = array();
				$var_arr['%nickname%'] = $this->myUrlEncode($data['member']->getDisplayName());
				$var_arr['%url%'] = $data['member']->getURL();
				if ($manager->pluginInstalled('NP_Profile')) {
					$profile =& $manager->getPlugin('NP_Profile');
					$fieldres = $profile->getFieldDef();
					$ignorefields = array('url','password','mail','notes','birthdate');
					$ignoretypes = array('date','file','list','mail','password','textarea');
					while ($row = mysql_fetch_assoc($fieldres)) {
						$field = $row['fname'];
						if (!in_array($row['ftype'],$ignoretypes) && !in_array($row['fname'],$ignorefields)) {
							$var_arr['%'.$field.'%'] = $this->myUrlEncode($profile->getValue($data['member']->id,$field));
						}
					}					
				}
				$url = trim(str_replace(array_keys($var_arr),$var_arr,$custom));
				if (!$url || strpos($url,'://') === false) 
					$url = $url = $CONF['IndexURL'];
				
				break;
			default:
				$url = $CONF['IndexURL'];
			}
			header("Location: $url");
			exit;
		}
	}
	
	function myUrlEncode($string) {
		$entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
		$replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
		return str_replace($entities, $replacements, urlencode($string));
	}
}
?>