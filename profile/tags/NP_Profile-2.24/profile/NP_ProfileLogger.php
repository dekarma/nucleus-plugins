<?php

class NP_ProfileLogger extends NucleusPlugin {

	// name of plugin
	function getName() {
		return 'ProfileLogger';
	}

	// author of plugin
	function getAuthor()  {
		return 'Frank Truscott';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL()
	{
		return 'http://www.iai.com/sandbox/';
	}

	// version of the plugin
	function getVersion() {
		return '1.0';
	}

	// a description to be shown on the installed plugins listing
	function getDescription() {
		return 'Logs the updating of profiles. A sample plugin to demonstrate the use of PostProfileUpdate event.';
	}
	
	function getEventList() { return array('PostProfileUpdate'); }
	
	function install() {
		$this->createOption("pl_enabled", "Enable Profile Logger?", 'yesno','yes');
	}

	function event_PostProfileUpdate(&$data) {
		if ($this->getOption('pl_enabled') == 'yes') {
			global $member;
			$memobj = $data['member'];
			$nick = $memobj->getDisplayName();
			$mid = $memobj->getID();
			$updater = $member->getDisplayName();
			$profarr = $data['profile'];
			$logtext = "Profile for member, $nick, updated by $updater. ";
			$logtext .= "[id]=> ".$memobj->getID()." ";
			$logtext .= "[nick]=> $nick ";
			$logtext .= "[realname]=> ".$memobj->getRealName()." ";
			$logtext .= "[notes]=> ".$memobj->getNotes()." ";
			$logtext .= "[url]=> ".$memobj->getURL()." ";
			$logtext .= "[mail]=> ".$memobj->getEmail()." ";
			foreach ($profarr as $key=>$value) {
				$logtext .= "[$key]=> $value ";
			}
			ACTIONLOG::add(INFO, $logtext);
		}
	}

}
?>