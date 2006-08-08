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
			'À' => 'A',		'Â' => 'A',		'Ä' => 'A',
			'Æ' => 'AE', 	'È' => 'E',		'Ê' => 'E',
			'Ì' => 'I',		'Î' => 'I',		'Ð' => 'D',
			'Ò' => 'O',		'Ô' => 'O',		'Ö' => 'O',
			'Ø' => 'O',		'Ú' => 'U',		'Ü' => 'U',
			'à' => 'a',		'â' => 'a',		'ä' => 'a',
			'æ' => 'ae',	'è' => 'e',		'ê' => 'e',
			'ì' => 'i',		'î' => 'i',		'ð' => 'o',
			'ò' => 'o',		'ô' => 'o',		'ö' => 'o',
			'ø' => 'o',		'ú' => 'u',		'ü' => 'u',
			'Á' => 'A',		'Ã' => 'A',		'Å' => 'A',
			'Ç' => 'C',		'É' => 'E',		'Ë' => 'E',
			'Í' => 'I',		'Ï' => 'I',		'Ñ' => 'N',
			'Ó' => 'O',		'Õ' => 'O',		'Ù' => 'U',
			'Û' => 'U',		'Ý' => 'Y',		'ß' => 'B',
			'á' => 'a',		'ã' => 'a',		'å' => 'a',
			'ç' => 'c',		'é' => 'e',		'ë' => 'e',
			'í' => 'i',		'ï' => 'i',		'ñ' => 'n',
			'ó' => 'o',		'õ' => 'o',		'ù' => 'u',
			'û' => 'u',		'ý' => 'y',		'ÿ' => 'y',
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
