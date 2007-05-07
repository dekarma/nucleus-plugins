<?
/*
 * Plugin for Nucleus CMS (http://plugins.nucleuscms.org/)
 * Copyright (C) 2005 Jeroen Budts (The Nucleus Group)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * see http://nucleuscms.org/license.txt for the full license
 */

/**
 * A plugin for Nucleus CMS to show a list of most commented items.
 * @license  http://www.gnu.org/licenses/gpl.txt
 * @author   Jeroen Budts (TeRanEX)
 * @tutorial http://wiki.budts.be/nucleus:plugins:most_commented
 * @version  1.0
 */
class NP_MostCommented extends NucleusPlugin {

	function getName() 			{ return 'Most Commented';    }
	function getAuthor()  		{ return 'TeRanEX, mod by Edmond Hui (admun)'; }
	function getURL() 			{ return 'http://wiki.budts.be/nucleus:plugins:most_commented'; }
	function getVersion() 		{ return '1.1a'; }
	function getDescription() 	{ return 'Shows a list of items with the most comments'; }

	function supportsFeature($what) {
		switch($what) {
		case 'SqlTablePrefix':
			return 1;
		default:
			return 0;
		}
	}

	function install() {
		$this->createOption('header','Header formatting','textarea','<ol>');
		$this->createOption('link','Link formatting','textarea','<li><a href="%l">%p</a> [%c comments]</li>');
		$this->createOption('footer','Footer formatting','textarea','</ol>');
	}


	function doSkinVar($skinType, $numOfPostsToShow) {
		global $blog;
		if ($numOfPostsToShow <= 0) {
			$numOfPostsToShow = 10;
		}

		$q = 	"SELECT i.inumber id, count(c.cnumber) num_of_comments, i.ititle title ".
				"FROM ".sql_table('comment')." c, ".sql_table('item')." i ".
				"WHERE c.cblog='" . $blog->blogid ."' AND c.citem = i.inumber ".
				"GROUP BY i.inumber, i.ititle ".
				"ORDER BY num_of_comments DESC ".
				"LIMIT 0, ".intval($numOfPostsToShow);

		$res = sql_query($q);

		echo($this->getOption('header')) ;
		$link_templ = $this->getOption('link');

		while($row = sql_fetch_array($res)) {
			$out = str_replace("%l", createItemLink($row[id]), $link_templ);
			$out = str_replace("%p", $row['title'], $out);
			$out = str_replace("%c", $row['num_of_comments'], $out);
			echo($out);
		}

		echo($this->getOption('footer')) ;

	}
}
?>
