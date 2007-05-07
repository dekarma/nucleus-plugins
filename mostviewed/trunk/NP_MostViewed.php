<?php
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
 * A plugin for Nucleus CMS to show a list of most viewed items.
 *
 * This is a completely rewritten version of NP_MostViewed, which was
 * originally written by Rodrigo Moraes and Edmond Hui.
 * I rewrote the plugin because the original used n+1 queries to generate
 * the list. This version uses one single query.
 *
 * @license  http://www.gnu.org/licenses/gpl.txt
 * @author   Jeroen Budts (TeRanEX)
 * @tutorial http://wiki.budts.be/nucleus:plugins:most_commented
 * @version  1.0
 *
 * admun TODO:
 * - display elapsed since last visit
 */
class NP_MostViewed extends NucleusPlugin {

	function getName() 		{ return 'MostViewed';    }
	function getAuthor()  		{ return 'v1.x: Rodrigo Moraes / Edmond Hui ; v2.0: Jeroen Budts (TeRanEX), mode by Edmond Hui (admun)'; }
	function getURL()		{ return 'http://wiki.budts.be/nucleus:plugins:mostviewed'; }
	function getVersion() 		{ return '2.4'; }
	function getDescription() {
		return 'This plugin displays the most viewed items. Rewritten by TeRanEX for a major speed improvement :)';
	}

	function supportsFeature($what) {
		switch($what) {
		case 'SqlTablePrefix':
			return 1;
		default:
			return 0;
		}
	}

	function getPluginDep() {
		return array('NP_Views');
	}

	function install() {
		$this->createOption('header','Header formatting','textarea','<ol>');
		$this->createOption('link','Link formatting','textarea','<li><a href="%l">%p</a> [viewed %v times]</li>');
		$this->createOption('footer','Footer formatting','textarea','</ol>');
	}

	function doSkinVar($skinType,$numOfPostsToShow='',$blogName='') {
		if ($numOfPostsToShow  == '') {
			$numOfPostsToShow = 10;
		}

                $blogId = getBlogIDFromName($blogName);
                $byBlog = "";
                if ($blogId != "")
                {
                  $byBlog = " AND i.iblog=" . intval($blogId) . " ";
                }

		$q = 	"SELECT i.inumber id, v.views views, i.ititle title ".
				"FROM ".sql_table('plugin_views')." v, ".sql_table('item')." i ".
				"WHERE v.id = i.inumber ". $byBlog .
				"ORDER BY views DESC ".
				"LIMIT 0,".intval($numOfPostsToShow);

		$res = sql_query($q);

		echo($this->getOption('header')) ;
                $link_templ = $this->getOption('link');

                $out = '';
		while($row = mysql_fetch_array($res)) {
                        $outtemp = '';
			$outtemp = str_replace("%l", createItemLink($row[id]), $link_templ);
			$outtemp = str_replace("%p", $row['title'], $outtemp);
			$outtemp = str_replace("%v", $row['views'], $outtemp);
                        $out = $out . " " . $outtemp;
		}

                echo $out;
		echo($this->getOption('footer')) ;
	}
}
?>
