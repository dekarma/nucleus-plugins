<?php
/*
	history:
		0.9 initial version (2002-12-10)
    /respect wouter demuynck, michel honig
		1.0 add return-path when sending mail (2005-09-13, admun)
		1.1 add better email rendering + template (2005-09-16, admun)
*/

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')) {
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_MailToAFriend extends NucleusPlugin {

	function getName() { return 'Mail to a friend'; }
	function getAuthor() { return 'Appie Verschoor, mod Edmond Hui (admun)'; }
	function getURL() { return 'http://xiffy.nl/weblog/'; }
	function getVersion() { return '1.7'; }
	function getDescription() { return 'Template var to add a link to a friend link or button. Use &lt;%MailToAFriend(Email to a friend OR &lt;img src=....&gt;)%&gt; inside your item template'; }
	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function install() {
		$this->createOption('defaultLook', 'If no argument given, how does the link look?', 'text', '<strong>[Mail to a friend]</strong>');
		$this->createOption('defaultMessage', 'What should be the initial message to send?', 'text', 'Look what I\'ve found at http://xiffy.nl/weblog/ ');
                $this->createOption('template', 'Which template should be used to display an item?', 'text', 'default');
                $this->createOption('defaultTitle', 'Email title template', 'text', 'Found on: %##BLOGNAME##% - %##TITLE##%');
	}
	
	function doTemplateVar(&$item, $look) {
		global $manager, $blog, $CONF, $DIR_PLUGINS;
		if ($blogName) {
			$b =& $manager->getBlog(getBlogIDFromName($params[2]));
		} else if ($blog) {
			$b =& $blog;
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		if (!$b) { echo 'Wrong'; }
		if ($look == '') {
			$look = $this->getOption('defaultLook');
		}
		$adminURL = $this->getAdminURL();
		$curItem = $item->itemid;
		
		echo '<a href="' . $adminURL . 'mailfriend.php?itemid=' . $curItem . '" onclick="window.open(this.href, \'popupwindow\', \'width=640, height=480, scrollbars, resizable\'); return false; ">' . $look . '</a>';
	}

}

?>
