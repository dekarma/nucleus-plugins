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


class NP_LogoutRedirect extends NucleusPlugin {

	function getName() { return 'Logout Redirect'; }

	function getAuthor()  {	return 'Frank Truscott';	}

	function getURL()   { return 'http://www.iai.com/sandbox';	}

	function getVersion() {	return '0.1'; }

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
	function getEventList() { return array('Logout'); }

	function install() {
// Need to make some options
		$this->createOption('redirect_enabled', 'Should logout redirect be enabled?', 'yesno', 'yes');
		$this->createOption('redirect_url', 'URL to which members should be redirected upon logging out.', 'text', 'http://www.nucleuscms.org');
    }

	function event_Logout(&$data) {
        if ($this->getOption('redirect_enabled') == 'yes') {
            $url = $this->getOption('redirect_url');
            header("Location: $url");
            exit;
        }
	}

}
?>