<?php

/* NP_GoogleCalendar
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 *  Joel Pan
 * http://www.ketsugi.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 *
 * Changelog:
 * 0.01		27-04-2006
 *		Initial beta release
*/

class NP_GoogleCalendar extends NucleusPlugin {
  
	function getName()  { return 'Google Calendar'; }
	function getAuthor() { return 'Joel Pan'; }
	function getURL()     { return 'http://ketsugi.com/'; }
	function getVersion() { return '0.01'; }
	function getDescription() { return 'Displays upcoming events from a Google Calendar file.'; }
	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix': return 1;
			default: return 0;
		}
	}
	function getMinNucleusVersion() { return 322; }
	function getMinNucleusPatchLevel() { return 0; }
	//function getEventList() {}
	
	function install() {
		//Create plugin options
		$this->createBlogOption('numberOfItems','Number of items to display:','text','5','datatype=numerical');
		$this->createBlogOption('gcalHeaderTpl','Header template:','textarea','<div id="GoogleCalendar"><h2>Google Calendar</h2><ul>');
		$this->createBlogOption('gcalItemTpl','Item template:','textarea','<li><span class="date"><%date%></span> <span class="name" title="<%description%>"><%name%></span></li>');
		$this->createBlogOption('gcalFooterTpl','Footer template:','textarea','</ul></div>');
		$this->createBlogOption('dateFormatTpl','Date format:','text','j M g:ia');
	}
	
	function doSkinVar($skinType, $calURL) {
		global $blog;
		$blogid = $blog->blogid;
		
		//Include the parser file
		require_once('GoogleCalendar/xmlparser.php');
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
}
?>
