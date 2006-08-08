<?php
class GoogleCalendar {
	
	var $parser; //XML Parser
	var $error;  //Error message
	var $calendar; //Array of calendar items
	var $currentDate; //Timestamp of current date
	var $currentTag; //String of current tag name
	var $currentItem; //Array of current item's properties
	
	//PHP4 Constructor
	function GoogleCalendar($url) {
	
		$this->parser = xml_parser_create();
		// use case-folding so we are sure to find the tag in $map_array
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, true);
		// tell PHP to find element handlers within $this object
		xml_set_object($this->parser, $this);
		// set the start and end element handlers
		xml_set_element_handler($this->parser, "tagStart", "tagEnd");
		// set the CDATA handler
		xml_set_character_data_handler($this->parser, "cdata");
		if (!($fp = fopen($url, "r"))) {
   		$this->error =  "Unable to find Google Calendar XML file.";
		}	
		
		while ($data = fread($fp, 4096)) {
   		if (!xml_parse($this->parser, $data, feof($fp))) {
      	$this->error = sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($this->parser)), xml_get_current_line_number($this->parser));
   		}
		}			
	}
	
	//PHP5 Constructor (calls PHP4 constructor)
	function __constructor($url) {
		$this->GoogleCalendar($url);
	}	
	
	function tagStart ($parser, $tag, $attributes) {
		$this->currentTag = $tag;
		if ($tag == 'ENTRY') {
			//Reset $currentItem and $currentDate
			$this->currentItem = "";
			$this->currentDate = 0;
		}
	}	
	
	function tagEnd ($parser, $tag) {
		if ($tag == 'ENTRY') {			
			//Add current item to calendar
			$this->calendar[$this->currentDate] = $this->currentItem;
		}
		//Reset $currentTag
		$this->currentTag = "";
	}
	
	function cdata($parser, $cdata) {
		$cdata = htmlentities($cdata);
		switch ($this->currentTag) {
			case 'PUBLISHED':
				$this->currentDate = $this->parseDate($cdata);
				break;
			case 'TITLE':
				$this->currentItem['title'] .= $cdata;
				break;
			case 'SUMMARY':				
				$this->currentItem['desc'] .= $cdata;
				break;
		}
	}
	
	function parseDate($date) {
		if (version_compare(phpversion(),'5') > 0) {
			return strtotime($date);
		}
		else {
			//PHP4's strtotime() function can't read microseconds, so let's parse it manually
			
			//First let's separate the date and time
			$date = explode('T',$date);
			$time = $date[1];
			$date = $date[0];
			
			//Now let's parse the date
			$date = explode('-',$date);
			
			//And now the time
			$time = explode(':',$time);
			
			//And now let's generate the timestamp
			return gmmktime($time[0],$time[1],0,$date[1],$date[2],$date[0]);			
		}
	}
	
	function getCalendar() {
		ksort($this->calendar);
		return $this->calendar;
	}
	
	function getError() {
		return $this->error;
	}

			
}


/* DEBUGGING PURPOSES ONLY */
/*
$gcal = new GoogleCalendar("http://www.google.com/calendar/feeds/toastyou@gmail.com/private-c83c4fb2ea1a83337549af4354c4756d/basic?gsessionid=4sJTneNwApg");
//$gcal = new GoogleCalendar("/home/ketsugi/www/gcal.xml");
$calendar = $gcal->getCalendar();
$error = $gcal->getError();

echo $error;

print_r($calendar);
*/
?>
