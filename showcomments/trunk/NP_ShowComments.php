<?php

// This is a mod of Nucleus core code and is published under the same license
/*
  * Nucleus: PHP/MySQL Weblog CMS (http://nucleuscms.org/)
  * Copyright (C) 2002-2005 The Nucleus Group
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * (see nucleus/documentation/index.html#license for more info)
 */
/**
 * @license http://nucleuscms.org/license.txt GNU General Public License
 * @copyright Copyright (C) 2002-2005 The Nucleus Group
  */

/* Lets a user determine how many comments to show, what order to show them, and
 * whether or not results should be paged

/* Usage: Only works on Item Pages. Replaces 'comments' skinvar.
 * &lt;%ShowComments(templatename, NumToShow, SortOrder, Page)%&gt;
 * where
 * templatename is the name of template to use to display comments
 * NumToShow is an integer indicating how many comments to show (per page if page=yes)
 *    Use -1 to indicate that all comments should be shown (or leave paramter blank).
 * SortOrder is the order to display comments - asc, desc, or random
 *    Default is asc and shows first comment at top, desc shows the most recent comments first.
 *    random show comments in random order and disables any paging
 * Page indicates whether results should be shown paged with NumToShow per page. yes or no.
 *    default is no. If NumToShow is -1 and page is yes, 20 comments will be shown per page
 */

class NP_ShowComments extends NucleusPlugin {

	// name of plugin
	function getName() {
		return 'ShowComments';
	}

	// author of plugin
	function getAuthor()  {
		return 'Frank Truscott';
	}

	// an URL to the author's website
	function getURL()
	{
		return "http://revcetera.com/ftruscot/";
	}

	// version of the plugin
	function getVersion() {
		return '1.30';
	}

	// a description to be shown on the installed plugins listing
	function getDescription() {
		return 'Lets you determine how many comments to show on item page, what order to show them in and provides pagination of results';
	}

    function supportsFeature($what){
        switch($what){
        case 'SqlTablePrefix':
            return 1;
        default:
            return 0;
        }
    }
	
	function doTemplateVar(&$item,$template,$NumToShow = -1,$sord = 'asc', $pages = 'no') {
		$args = func_get_args();
		$iid = $item->itemid;
		$new_args = array('template',$template,$NumToShow,$sord,$pages,$iid);
		call_user_func_array(array(&$this,'doSkinVar'),$new_args);
	}

	function doSkinVar($skinType,$template,$NumToShow = -1,$sord = 'asc', $pages = 'no', $iid = 0) {

		if (!is_numeric($NumToShow)) $NumToShow = -1;
		if (strtolower($pages) == 'yes') {
			$pages = 1;
			if (requestVar('scbegin') == '') $start = 1;
			else {
				$start = intval(requestVar('scbegin'));
				// if ($start == 0) $start = 1;
			}
			if ($NumToShow < 1) $NumToShow = 20;
		}
		else {
			$pages = 0;
			$start = 1;
		}
		switch (strtolower($sord)) {
			case 'desc':
				$sord = 'DESC';
				break;
			case 'random':
				$sord = 'RAND()';
				$pages = 0;
				break;
			default:
				$sord = 'ASC';
				break;
		}
		$iid = intval($iid);
        if ($skinType == 'item' || ($skinType == 'template' && $iid > 0)) {
            // this is mostly the parse_comments() function of Nucleus' ACTIONS class (in SKIN.php)
            global $itemid, $manager, $highlight;
			if ($iid < 1) $iid = $itemid;
            $itemblogid = getBlogIDFromItemID($iid);
            $ib =& $manager->getBlog($itemblogid);
            $itemblog = $ib;
            $template =& $manager->getTemplate($template);

            // create parser object & action handler
            $actions =& new ITEMACTIONS($itemblog);
            $parser =& new PARSER($actions->getDefinedActions(),$actions);
            $actions->setTemplate($template);
            $actions->setParser($parser);
			if (!$manager->existsItem($iid,0,0) ) {
				return "";
			}
            //$item1 =& new ITEM($iid);
			$item1 = ITEM::getitem($iid, 0, 0);
            $actions->setCurrentItem($item1);
            $comments =& new COMMENTS($iid);
            $comments->setItemActions($actions);
            $this->_showComments($comments,$template, -1, $NumToShow, $start, $pages, $sord, 1, $highlight);
        }
	}

// this is mostly the show_comments() function of Nucleus' COMMENTS class (in COMMENTS.php)
    function _showComments(&$comments,$template, $maxToShow = -1, $NumToShow = -1, $start = 1, $pages = 0, $sord = 'ASC',$showNone = 1, $highlight = '') {
		global $CONF, $manager;

		// create parser object & action handler
		$actions =& new COMMENTACTIONS($comments);
		$parser =& new PARSER($actions->getDefinedActions(),$actions);
		$actions->setTemplate($template);
		$actions->setParser($parser);

		if ($start == 0 && $NumToShow > 0) {
			$totalcomments = intval(quickQuery('SELECT COUNT(*) as result FROM ' . sql_table('comment') . ' WHERE citem=' . $comments->itemid));

			switch ($sord) {
				case 'ASC':
					if ($totalcomments <= $NumToShow) $start = 1;
					else {
						$lastpage = intval($totalcomments / $NumToShow);
						$start = $lastpage * $NumToShow;
						if ($totalcomments == $start) $start = $start - $NumToShow;
						$start = $start + 1;
					}
				break;
				case 'DESC':
					$start = 1;
				break;
				default:
					$start = 1;
				break;
			}
		}

		if ($maxToShow == 0) {
			$comments->commentcount = $comments->amountComments();
		} else {
			$query =  'SELECT c.citem as itemid, c.cnumber as commentid, c.cbody as body, c.cuser as user, c.cmail as userid, c.cmember as memberid, c.ctime, c.chost as host, c.cip as ip, c.cblog as blogid'
				   . ' FROM '.sql_table('comment').' as c'
				   . ' WHERE c.citem=' . $comments->itemid;
			if ($sord == 'RAND()') $query .= ' ORDER BY RAND()';
            else $query .= ' ORDER BY c.ctime '.$sord;
            if ($NumToShow >= 0) $query .= ' LIMIT '.intval($start - 1).','.$NumToShow;

			$commres = sql_query($query);
			$comments->commentcount = mysql_num_rows($commres);
		}

		// if no result was found
		if ($comments->commentcount == 0) {
			// note: when no reactions, COMMENTS_HEADER and COMMENTS_FOOTER are _NOT_ used
			if ($showNone) $parser->parse($template['COMMENTS_NONE']);
			return 0;
		}

		// if too many comments to show
		if (($maxToShow != -1) && ($comments->commentcount > $maxToShow)) {
			$parser->parse($template['COMMENTS_TOOMUCH']);
			return 0;
		}

		$parser->parse($template['COMMENTS_HEADER']);

        if ($pages && ($comments->commentcount < $comments->amountComments())) {
            $this->_pagelinks($start,$NumToShow,$comments->amountComments());
        }

		while ( $comment = mysql_fetch_assoc($commres) ) {
			$comment['timestamp'] = strtotime($comment['ctime']);
			$actions->setCurrentComment($comment);
			$actions->setHighlight($highlight);
			$manager->notify('PreComment', array('comment' => &$comment));
			$parser->parse($template['COMMENTS_BODY']);
			$manager->notify('PostComment', array('comment' => &$comment));
		}

        if ($pages && ($comments->commentcount < $comments->amountComments())) {
            $this->_pagelinks($start,$NumToShow,$comments->amountComments());
        }

		$parser->parse($template['COMMENTS_FOOTER']);

		mysql_free_result($commres);

		return $comments->commentcount;
	}

    function _pagelinks($start = 1,$NumToShow = 20,$NumComments) {
        if ($NumComments <= $NumToShow) return '';

        $host = serverVar('HTTP_HOST');
        $script = serverVar('PHP_SELF');
        $qstring = serverVar('QUERY_STRING');
        $qstringarr = explode('&', $qstring);
        $nqstring = '';
        foreach ($qstringarr as $value) {
            if (strpos($value,'scbegin') === false) {
                $nqstring .= $value.'&';
            }
        }
        $thispage = "http://".$host.$script.'?'.$nqstring;

        if ($NumComments % $NumToShow == 0) $npages = intval($NumComments / $NumToShow);
		else $npages = intval(($NumComments / $NumToShow) + 1);

        if ($start % $NumToShow < 1) $pg = 1;
		else $pg = intval(($start / $NumToShow) + 1);

        echo '<div class="commentpager">'."\n";
		echo '<small>';
		echo "Page $pg of $npages |";
        $prevpage = (($pg - 2) * $NumToShow) + 1;
		echo ' <a href="'.$thispage.'scbegin='.($pg > 1 ? $prevpage : 1).'#c" title="Previous Page">&lt;&lt;</a> '."\n";
		echo ' | <a href="'.$thispage.'scbegin=1#c" title="First Page">First</a> |'."\n";

		if ($npages <= 11) {
			$i = 1;
			$end = $npages;
		}
		else {
			if ($pg <= 6) {
				$i = 1;
				$end = 11;
			}
			elseif ($pg >= $npages - 5) {
				$i = $npages - 11;
				$end = $npages;
			}
			else {
				$i = $pg - 5;
				$end = $pg + 5;
			}
		}

		if ($i > 1) echo ' ... ';
		while ($i <= $end) {
            $ipage = (($i - 1) * $NumToShow) + 1;
			echo '<a href="'.$thispage.'scbegin='.$ipage.'#c" title="Page '.$i.'">'.($i == $pg ? '<big><span style="text-decoration:underline;color:#ff0000">'.$i.'</span></big>' : $i).'</a> - '."\n";
			$i++;
		}
		if ($i - 1 < $npages) echo ' ... ';
        $lastpage = (($npages - 1) * $NumToShow) + 1;
		echo ' | <a href="'.$thispage.'scbegin='.$lastpage.'#c" title="Last Page">Last</a> |'."\n";
        $nextpage = ($pg * $NumToShow) + 1;
		echo ' <a href="'.$thispage.'scbegin='.($pg < $npages ? $nextpage : $lastpage).'#c" title="Next Page">&gt;&gt;</a> |'."\n";
		echo '</small></div><br />'."\n";

    }
}
?>