<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<title>Nucleus: Current Media Plugin</title>
	<link type="text/css" rel="stylesheet" href="popup.css" />
</head>
<body>
<div id="content">

<?php

if (isset($HTTP_GET_VARS['type'])) {
	$type = $HTTP_GET_VARS['type'];
} else {
	echo 'ERROR: type of search not specified.' . "\n";
	exit(0);
}

// Set page number based on URL, or 1 by default
$pagenum = (isset($HTTP_GET_VARS['page']) ) ? $HTTP_GET_VARS['page'] : 1;

/*
if (isset($HTTP_GET_VARS['page'])) {
	$pagenum = $HTTP_GET_VARS['page'];
} else {
	$pagenum = 1;
}
*/

if (isset($HTTP_POST_VARS['SearchKeyword']) ) {
	$SearchKeyword = $HTTP_POST_VARS['SearchKeyword'];
}
else if (isset($HTTP_GET_VARS['SearchKeyword']) ) {
	$SearchKeyword = $HTTP_GET_VARS['SearchKeyword'];
}

if (isset($HTTP_GET_VARS['mode'])) {
	$SearchMode = $HTTP_GET_VARS['mode'];
} else {
	switch($type) {
		case 0:
			$SearchMode = 'dvd';
			break;
		case 1:
			$SearchMode = 'books';
			break;
		case 2:
			$SearchMode = 'music';
			break;
		case 3:
			$SearchMode = 'vhs';
			break;
		case 4:
			$SearchMode = 'videogames';
			break;
	}
}

// Get the locale (determines which amazon site to search)
$locale = $HTTP_GET_VARS['locale'];

if ($type == 0) { $actionword = 'Watching'; $highlight0 = 'style="border: 1px dashed #000000;"'; }
if ($type == 1) { $actionword = 'Reading'; $highlight1 = 'style="border: 1px dashed #000000;"'; }
if ($type == 2) { $actionword = 'Listening'; $highlight2 = 'style="border: 1px dashed #000000;"'; }
if ($type == 3) { $actionword = 'Watching'; $highlight3 = 'style="border: 1px dashed #000000;"'; }
if ($type == 4) { $actionword = 'Playing'; $highlight4 = 'style="border: 1px dashed #000000;"'; }

echo '<h2>Currently ' . $actionword . '</h2>' . "\n";
echo '<a href="?type=0&locale=' . $locale . '" ' . $highlight0 . '>DVD</a> | ';
echo '<a href="?type=1&locale=' . $locale . '" ' . $highlight1 . '>Books</a> | ';
echo '<a href="?type=2&locale=' . $locale . '" ' . $highlight2 . '>Music</a> | ';
echo '<a href="?type=3&locale=' . $locale . '" ' . $highlight3 . '>VHS</a> | ' . "\n";
echo '<a href="?type=4&locale=', $locale, '" ', $highlight4, '>Video Games</a> <br />', "\n";

displaySearchForm($SearchKeyword);

if ($SearchKeyword) {
	displaySearchResults($SearchKeyword, $SearchMode);
}

/****************************************************************
This function is used to display the search form in the popup
window
*****************************************************************/
function displaySearchForm($SearchKeyword) {
	global $SearchMode;

	echo '<form name="SearchForm" method="post" action="' . $PHP_SELF . '">' . "\n";
	echo '<input type="hidden" name="SearchMode" value="$SearchMode">' . "\n";
	echo '<input type="text" size="20" name="SearchKeyword" value="' . $SearchKeyword . '">' . "\n";
	echo '<input type="submit" value="Search">' . "\n";
	echo '</form>' . "\n";
}

/****************************************************************
This function is used to parse the XML results of the amazon 
search, provide links to select the media, and links to multiple
search page results (10 per page)
*****************************************************************/
function displaySearchResults($SearchKeyword, $SearchMode) {
	global $pagenum, $type, $locale;

	// Use 'heavy' XML if it's a movie, in order to get the 'starring' info
	if ($type == 0 || $type == 3) {
		$xmltype = 'heavy';
		$word = 'starring';
	}
	else {
		$xmltype = 'lite';
		$word = 'by';
	}

	// Construct the XML feed
	switch($locale) {
		case 'de':
			$xmlfeed = 'http://xml-eu.amazon.com/onca/xml3?locale=de&t=nucleuscms-20&dev-t=D2EI3BZXGO7R6R';
			break;
		default:
			$xmlfeed = 'http://xml.amazon.com/onca/xml3?t=nucleuscms-20&dev-t=D2EI3BZXGO7R6R';
			break;
	}

	//$xmlfeed = 'http://xml.amazon.com/onca/xml3?t=nucleuscms-20&dev-t=D2EI3BZXGO7R6R';
	$xmlfeed.= '&KeywordSearch=' . urlencode($SearchKeyword);
	$xmlfeed.= '&mode=' . $SearchMode;
	$xmlfeed.= '&sort=+pmrank&offer=All';
	$xmlfeed.= '&type=' . $xmltype;
	$xmlfeed.= '&page=' . $pagenum;
	$xmlfeed.= '&f=xml';

	// Uncomment the next line if you need a link to the XML feed for debugging purposes
	// echo "<a href=\"$xmlfeed\">xml feed</a> \n";

	$data = @implode("", file($xmlfeed));

	$parser = xml_parser_create(); 
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
	xml_parse_into_struct($parser, $data, $d_ar, $i_ar);
	xml_parser_free($parser);

	echo '<script src="popuphandler.js" type="text/javascript"></script>' . "\n";
	echo '<table border="0" cellspacing="0" cellpadding="3">';

	$x = 0; // item number
	
	echo '<form name="selectCurrent" action="#" method="post">', "\n";

	for ($i=0; $i < count($i_ar['Details']); $i++) {
		if ($d_ar[$i_ar['Details'][$i]]['type'] == 'open') {
			for ($j = $i_ar['Details'][$i]; $j < $i_ar['Details'][$i+1]; $j++) {

				// Assign media data to variables for output
				if ($d_ar[$j]['tag'] == 'ProductName') {
					$ProductName = $d_ar[$j]['value'];
				}
				elseif ($d_ar[$j]['tag'] == 'Asin') {
					$Asin = $d_ar[$j]['value'];
				}
				elseif ($d_ar[$j]['tag'] == 'ImageUrlSmall') {
					$ImageUrlSmall = $d_ar[$j]['value'];
				}
				elseif($d_ar[$j]['tag'] == 'Author') {
					$Author[] = $d_ar[$j]['value'];
				}
				elseif($d_ar[$j]['tag'] == 'Artist') {
					$Author[] = $d_ar[$j]['value'];
				}
				elseif($d_ar[$j]['tag'] == 'Actor') {
					$Author[] = $d_ar[$j]['value'];
				}
				elseif($d_ar[$j]['tag'] == 'Manufacturer' && $type == 4) {
					$Author[] = $d_ar[$j]['value'];
				}
			}

			// Separate Author array with commas
			if (!empty($Author) ) {
				foreach($Author as $auth) {
					$AuthorList .= $auth. ', ';
				}
			}

			// Remove the last two characters (space and comma)
			$AuthorList = (isset($AuthorList) ) ? substr($AuthorList, 0, -2) : NULL;

			// Construct the media items output
			$output = "<tr> \n";
			$output.= "<td> <img src=\"$ImageUrlSmall\" /> </td> \n";
			$output.= "<td valign=\"top\"> <b>$ProductName</b> <br /> \n";
			$output.= "$word $AuthorList <br /> \n";
			$output.= "<a href=\"javascript:updateParent($x, $type)\">select this</a> \n";
			$output.= "<input type=\"hidden\" name=\"asin$x\" value=\"$Asin\"> \n";
			$output.= "<input type=\"hidden\" name=\"title$x\" value=\"$ProductName\"> \n";
			$output.= "<input type=\"hidden\" name=\"by$x\" value=\"$AuthorList\"> \n";
			$output.= "<input type=\"hidden\" name=\"image$x\" value=\"$ImageUrlSmall\"> \n";
			$output.= "</td> \n";
			$output.= "</tr> \n";
			echo $output;

			$x++;
			$AuthorList = NULL;
			$Author = NULL;
		}
	}

	echo '</table>';
	echo '</form>';

	$TotalPages = (int) $d_ar[$i_ar['TotalPages'][0]]['value'];
	if ($TotalPages > 10) {
		$TotalPages = 10;
	}

	// Create links to other search page results (if more than 1 page)
	if($TotalPages > 1){
		echo '<b>Page:</b> ';
		for($k = 1; $k <= $TotalPages; $k++) {
			if($k == $pagenum) {
				echo "<b>" .$k. "</b> ";
			} else {
				echo '<b><a href="?type=' . $type . '&SearchKeyword=' . urlencode($SearchKeyword) . '&mode=' . $SearchMode . '&locale=' . $locale . '&page=' .$k. '">' .$k. '</a></b> ';
			}
		
			if($TotalPages != $k) {
				echo '| ';
			}
		}
	}
}

?>

</div>
</body>
</html>