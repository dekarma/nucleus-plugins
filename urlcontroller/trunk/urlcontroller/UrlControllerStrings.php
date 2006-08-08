<?php
/**
 *	String manipulation
 */
class UrlControllerStrings
{
	/**
	 * Returns an array with special chars and correspondent replacements
	 * @return array Characters map
	 */
	function getSpecialCharsReplacements()
	{
		$chars = array(
			'�' => 'A',		'�' => 'A',		'�' => 'A',
			'�' => 'AE', 	'�' => 'E',		'�' => 'E',
			'�' => 'I',		'�' => 'I',		'�' => 'D',
			'�' => 'O',		'�' => 'O',		'�' => 'O',
			'�' => 'O',		'�' => 'U',		'�' => 'U',
			'�' => 'a',		'�' => 'a',		'�' => 'a',
			'�' => 'ae',	'�' => 'e',		'�' => 'e',
			'�' => 'i',		'�' => 'i',		'�' => 'o',
			'�' => 'o',		'�' => 'o',		'�' => 'o',
			'�' => 'o',		'�' => 'u',		'�' => 'u',
			'�' => 'A',		'�' => 'A',		'�' => 'A',
			'�' => 'C',		'�' => 'E',		'�' => 'E',
			'�' => 'I',		'�' => 'I',		'�' => 'N',
			'�' => 'O',		'�' => 'O',		'�' => 'U',
			'�' => 'U',		'�' => 'Y',		'�' => 'B',
			'�' => 'a',		'�' => 'a',		'�' => 'a',
			'�' => 'c',		'�' => 'e',		'�' => 'e',
			'�' => 'i',		'�' => 'i',		'�' => 'n',
			'�' => 'o',		'�' => 'o',		'�' => 'u',
			'�' => 'u',		'�' => 'y',		'�' => 'y',
			' ' => '-',		'/' => '-');
			return $chars;
	}

	/**
	 * Converts special characters
	 */
	function convertSpecialChars($string, $replacements=array(), $tolower=true)
	{
		$chars = UrlControllerStrings::getSpecialCharsReplacements();
		if(is_array($replacements) && !empty($replacements))
		{
			$chars = array_merge($chars, $replacements);
		}
		$string = str_replace(array_keys($chars), $chars, $string);
		// allows only characters from 'A' to 'Z', 'a' to 'z', '0' to '9' and '-'
		$string = ereg_replace('[^A-Za-z0-9-]', '', $string);
		if($tolower)
		{
			$string = strtolower($string);
		}
		return $string;
	}

	/**
	 * Replaces repeated chars inside a string: str-----ing becomes str-ing
	 */
	function replaceRepeatedChars($string, $char, $replacement)
	{
		$pos = strpos($string, $char);
		if ($pos !== false) {
			$string = str_replace($char, $replacement, $string);
			$string = UrlControllerStrings::replaceRepeatedChars($string, $char, $replacement);
		}
		return $string;
	}
}
?>
