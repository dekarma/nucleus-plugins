<?php

class NP_SkinUploader extends NucleusPlugin {

	function getName() {
		return 'SkinUploader';
	}
	function getAuthor() {
		return 'Stas Verberkt (Legolas)';
	}
	function getURL() {
		return 'http://www.legolasweb.nl/';
	}
	function getVersion() {
		return '1.0.2';
	}
	function getDescription() {
		return 'Why need ftp? Upload zipped skins easy =)';
	}

	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function init() {
	}

	function install() {
	}
	
	function unInstall() {
	}

	function getEventList() {
		return array('QuickMenu');
	}
	
	function hasAdminArea() {
		return 1;
	}
	
	function event_QuickMenu(&$data) {
		global $member;

		if ($member->isLoggedIn() && $member->isAdmin()) {
			array_push($data['options'], array('title' => 'Skin Uploader', 'url' => $this->getAdminURL(), 'tooltip' => 'Upload a skin!'));
		}
	}
}

?>