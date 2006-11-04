<?php

class NP_SubmitSystem extends NucleusPlugin {

	var $dbtable = null;
	var $dbtablelog = null;

	function getName() {
		return 'SubmitSystem';
	}
	function getAuthor() {
		return 'Stas Verberkt (Legolas)';
	}
	function getURL() {
		return 'http://www.legolasweb.nl/';
	}
	function getVersion() {
		return '2.0.2';
	}
	function getDescription() {
		return 'Submit stuff...';
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
		$this->dbtable = sql_table('plugin_submitsystem');
		$this->dbtablelog = sql_table('plugin_submitsystem_log');
	}

	function install() {
		sql_query('
			CREATE TABLE ' . $this->dbtable . ' (
			  ss_id int(5) unsigned NOT NULL auto_increment,
			  ss_blogid int(5) unsigned NOT NULL default 1,
			  ss_title varchar(255) NOT NULL default \'\',
			  ss_body text NOT NULL,
			  ss_poster_name varchar(255) NOT NULL default \'\',
			  ss_poster_email varchar(255) NOT NULL default \'\',
			  ss_poster_website varchar(255) NOT NULL default \'\',
			  ss_poster_ip varchar(255) NOT NULL default \'\',
			  ss_date timestamp NULL default NULL,
			  ss_extrafields varchar(255) NOT NULL default \'\',
			  ss_files varchar(255) NOT NULL default \'\',
			  PRIMARY KEY  (ss_id)
			) TYPE=MyISAM AUTO_INCREMENT=1;
        	');

		sql_query('
			CREATE TABLE ' . $this->dbtablelog . ' (
			  ssl_id int(5) unsigned NOT NULL auto_increment,
			  ssl_times_allowed bigint(19) unsigned NOT NULL default 1,
			  ssl_times_denied bigint(19) unsigned NOT NULL default 1,
			  ssl_poster_ip varchar(255) NOT NULL default \'\',
			  PRIMARY KEY  (ssl_id)
			) TYPE=MyISAM AUTO_INCREMENT=1;
        	');

		$defaultskin = '<%parsedinclude(head.inc)%>' . "\n"
			. '' . "\n"
			. '<!-- page header -->' . "\n"
			. '<%parsedinclude(header.inc)%>' . "\n"
			. '' . "\n"
			. '<!-- page content -->' . "\n"
			. '<div id="container">' . "\n"
			. '<div class="content">' . "\n"
			. '<%SubmitSystemMain%>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n"
			. '' . "\n"
			. '<!-- page menu -->' . "\n"
			. '<h2 class="hidden">Sidebar</h2>' . "\n"
			. '<div id="sidebarcontainer">' . "\n"
			. '<%parsedinclude(sidebar.inc)%>' . "\n"
			. '</div>' . "\n"
			. '' . "\n"
			. '<!-- page footer -->' . "\n"
			. '<%parsedinclude(footer.inc)%>';

		$this->createOption('skin', 'Skin, must contain "<%SubmitSystemMain%>"', 'textarea', $defaultskin);
		$this->createOption('waittime', 'Number of seconds an user has to wait before he/she can submit again (0 = unlimited)', 'text', 300, 'numerical=true');
		$this->createOption('fileupload', 'Allow file upload?', 'yesno', 'no');
		$this->createOption('filesize', 'Max file size', 'text', 1048576, 'numerical=true');
		$this->createOption('filecount', 'Max number of files (can\'t be bigger than 5)', 'text', 3, 'numerical=true');
		$this->createOption('filetypes', 'Allowed filetypes (extensions seperated by |, enter "*" for anything)', 'text', 'txt|doc|png|jpg');
		$this->createOption('fileprefix', 'File prefix', 'text', 'submitfile_');
		$this->createOption('moderatemode', 'Which moderator mode should be used?', 'select', 'team', 'Team members|team|Team administrators|teamadmin|Global administrators|admin');
		$this->createOption('log', 'Log times allowed/denied per IP?', 'yesno', 'no');
		$this->createOption('excludeblogs', 'Enter the shortnames of the blogs to be excluded of the submitform (seperated by |)', 'text', '');
		$this->createOption('extrafields', 'Enter the names of extra fields (no spaces or specialcharacters) followed by [\'Description to be displayed\'], more fields are seperated by |. Example: "extra1[\'Some extra field\']|extra2[\'anotherfield\']"', 'text', '');
		$this->createOption('captcha', 'Use captcha check (GD2 needed)?', 'yesno', 'no');
		$this->createOption('emailnotification', 'Send an email to the admin mail when something gets submitted?', 'yesno', 'no');
		$this->createOption('previewmode', 'Show editable preview before allowing?', 'yesno', 'yes');
	}
	
	function unInstall() {
		sql_query('DROP TABLE IF EXISTS ' . $this->dbtable . ';');
		sql_query('DROP TABLE IF EXISTS ' . $this->dbtablelog . ';');
	}

	function getEventList() {
		return array('QuickMenu');
	}
	
	function hasAdminArea() {
		return 1;
	}
	
	function event_QuickMenu(&$data) {
		global $member;

		array_push($data['options'], array('title' => 'Submit System', 'url' => $this->getAdminURL(), 'tooltip' => 'System to submit posts'));
	}
}

?>