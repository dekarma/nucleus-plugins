<?php
/* NP_WordCount
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * © Joel Pan
 * http://www.ketsugi.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 * Word Count code taken from http://www.nbrandt.com/scripts_word_count.php
 *
 * Changelog:
 * 1.0    23-01-2006
 *    No changes from previous release
 * 0.11		05-04-2005
 *		Bug fix preventing word count from functioning properly when no parameters are supplied
 * 0.10		05-04-2005
 *		Initial release
*/
 
class NP_Wordcount extends NucleusPlugin {
 
	function getName() { return 'Word Count';	}
	function getAuthor() { return 'Joel Pan'; }
	function getURL() { return 'http://wakka.xiffy.nl/WordCount'; }
	function getVersion() { return '1.0';	}
	function getDescription() { return 'Use <%WordCount%> in an template to display the word count for that particular item.'; }
	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	
	function install() {
		$this->createOption('words_singular','One Word','text','word');
		$this->createOption('words_plural','Two (or more) Words','text','words');
		$this->createOption('part','Default setting: Count words in which part of the entry?','select','full','Body|body|Extended|more|Both|full');
		$this->createOption('strip','Default setting: Strip HTML tags before counting words?','yesno','yes');
		$this->createOption('commas','Default setting: Insert commas into numeric word count?','yesno','yes');
	}
	
	function doTemplateVar (&$item, $part = '', $strip = '', $commas = '') {
		//Initialise default settings
		if ($part == '') { $part = $this->getOption('part'); }
		if ($strip == '') { $strip = $this->getOption('strip'); }
		if ($commas == '') { $commas = $this->getOption('commas'); }
 
		switch ($part) {
		case 'body':
			$text = $item->body;
		case 'more':
			$text = $item->more;
			break;
		case 'full':
			$text = $item->body.$item->more;
			break;
		}
 
		//Strip tags if necessary
		if ($strip == 'yes') { $text = strip_tags($text); }
 
		$word_count = $this->count_words($text);
		
		//Output number		
		if ($commas == 'yes') { echo $this->insert_commas($word_count).' '; }
		else { echo $word_count.' '; }
		
		//Output word
		if ($word_count == 1) { echo $this->getOption('words_singular'); }
		else { echo $this->getOption('words_plural'); }
	}
	
	function count_words ($text) {
		if (phpversion() < '4.3.0')	{
		  $string = eregi_replace(" +", " ", $text);
  		$string = explode(" ", $text);
			while (list(, $word) = each ($text)) {
      	if (eregi("[0-9A-Za-zÀ-ÖØ-öø-ÿ]", $word)) { $word_count++; }
    	}
		}
		else { $word_count = str_word_count($text); }
		return ($word_count);
	}
 
	function insert_commas ($number) {
		$number = strrev($number);
		for ($i = 0; $i < strlen($number) / 3; $i++) {
				$comma_number = ','.strrev(substr($number,$i * 3,3)).$comma_number;
		}
		$comma_number = substr($comma_number,1);
		return($comma_number);
	}
}
?>