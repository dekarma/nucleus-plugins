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


class NP_LoginRedirect extends NucleusPlugin {

	function getName() { return 'Login Redirect'; }

	function getAuthor()  {	return 'Frank Truscott';	}

	function getURL()   { return 'http://www.iai.com/';	}

	function getVersion() {	return '0.2'; }

	function getDescription() {
		return 'Redirect user to member profile page after successful login';
	}

	function getMinNucleusVersion() { return 320; }

	function supportsFeature($what)	{
		switch($what) {
		case 'SqlTablePrefix':
			return 1;
		default:
			return 0;
		}
	}
	function getEventList() { return array('LoginSuccess'); }

	function install() {
// Need to make some options
		$this->createOption('redirect_enabled', 'Should login redirect be enabled?', 'yesno', 'yes');
    }

	function event_LoginSuccess(&$data) {
        if ($this->getOption('redirect_enabled') == 'yes') {
            global $shared;
            $errormessage = '';
            ACTIONLOG::add(INFO, "Login successful for ".$data['member']->displayname." (sharedpc=$shared)");
            $url = createMemberLink($data['member']->id);
            header("Location: $url");
            exit;
        }
	}

}
?>
