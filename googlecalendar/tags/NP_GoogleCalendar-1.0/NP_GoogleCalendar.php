<?php

/* NP_GoogleCalendar
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * by Joel Pan
 * http://www.ketsugi.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 *
 * Changelog:
 * 1.0		13-06-2006
	*		Added help file
 * 0.02		09-06-2006
 *		Added support to display links to Google Calendar event pages
 *		Added support for caching of calendar xml files
  * 0.01		27-04-2006
 *		Initial beta release
 */

class NP_GoogleCalendar extends NucleusPlugin {
  
	function getName()  { return 'Google Calendar'; }
	function getAuthor() { return 'Joel Pan'; }
	function getURL()     { return 'http://ketsugi.com/'; }
	function getVersion() { return '1.0'; }
	function getDescription() { return 'Displays upcoming events from a Google Calendar file.'; }
	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix': return 1;
			case 'HelpPage': return 1;
			default: return 0;
		}
	}
	function getMinNucleusVersion() { return 322; }
	function getMinNucleusPatchLevel() { return 0; }

	function install() {
		//Create plugin options
		$this->createBlogOption('numberOfItems','Number of items to display:','text','5','datatype=numerical');
		$this->createBlogOption('timeout','Time interval (in minutes) for checking calendar file:','text','60','datatype=numerical');
		$this->createBlogOption('gcalHeaderTpl','Header template:','textarea','<div id="GoogleCalendar"><h2>Google Calendar</h2><ul>');
		$this->createBlogOption('gcalItemTpl','Item template:','textarea','<li><a href="<%link%>" class="googlecalendar"><span class="date"><%date%></span> <span class="name" title="<%description%>"><%name%></span></a></li>');
		$this->createBlogOption('gcalFooterTpl','Footer template:','textarea','</ul></div>');
		$this->createBlogOption('dateFormatTpl','Date format:','text','j M g:ia');
	}
	
	function doSkinVar($skinType, $calURL) {
		//Get the ID of the currently-displayed blog
		global $blog;
		$blogid = $blog->blogid;
		
		//Include the parser file
		require_once('googlecalendar/xmlparser.php');
		
		//If time interval has passed, refresh page; otherwise use file from cache
		$calURL = $this->checkSource($calURL, $this->getBlogOption($blogid,'timeout'));
		
		//Create new GoogleCalendar class and acquire array of calendar items
		$gcal = new GoogleCalendar($calURL);
		$calendar = $gcal->getCalendar();

		//Generate the list output
		//Get current timestamp
		$time = time();

		foreach ($calendar as $date => $item) {
			//Don't display outdated events
			if ($date > $time && $i++ < $this->getBlogOption($blogid,'numberOfItems')) {
				//Add blog's time offset
				$offset = $blog->settings['btimeoffset'] * 60 * 60;
				$date += $offset;
				$itemVars['date'] = date($this->getBlogOption($blogid,'dateFormatTpl'),$date);
				$itemVars['name'] = $item['title'];
				$itemVars['link'] = $item['link'];
				$itemVars['description'] = $item['desc'];
				$listOutput .= TEMPLATE::FILL($this->getBlogOption($blogid,'gcalItemTpl'),$itemVars);
			}
		}
		
		//Print the output (check for errors first!)
		if ($gcal->getError() == "") {
			echo $this->getBlogOption($blogid,'gcalHeaderTpl');
			echo $listOutput;
			echo $this->getBlogOption($blogid,'gcalFooterTpl');
		}
		else {
			echo $gcal->getError();
		}
	}

	function checkSource($calURL, $timeout) {
		global $DIR_PLUGINS;		
		$base = $DIR_PLUGINS;
		
		//Make sure the cache directory is writable
		clearstatcache();
		if (!is_writable($base.'googlecalendar/cache')) {
			die('Please make sure the Google Calendar cache directory is writable.');
		}
		
		//MD5 the URL to get a useable file name
		$cachefile = $base.'googlecalendar/cache/'.md5($calURL).'.xml';
		
		//Get current time
		$currentTime = time();
		
		//Convert the timeout interval to seconds
		$timeout *= 60;
		
		//If the file is not in cache, or the accesstime is too old, save a copy
		if (!file_exists($cachefile) or $currentTime - fileatime($cachefile) > $timeout) {
			copy($calURL, $cachefile);
		}
		
		return $cachefile;
	}
}
?>
