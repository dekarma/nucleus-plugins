<?php

/* This is a mod of Nucleus core code and is published under the same license */
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

/* Lets a user determine order to show blog items
 */

/* Usage:
 * See the ordered/help.html file for most current usage.
 * There are three skinvars.
 * To replace the blog  or otherblog skinvar use this form:
 * &lt;%Ordered('blog', show, templatename, amount, category, blogname)%&gt;
 * where
 * show is either 'ordered' or 'unordered' or 'all' and indicates which to show. blank shows just the ordered items.
 * templatename is the name of template to use to display items. To force use of this template for all categories, follow by "(strict)", like this templatename(strict)
 * amount is an integer indicating how many items to show on page. can have offset like blog skinvar
 * category is category name to show (leave blank to show current category).
 *   Use 'cat_ord' to show only items from ordered categories. 'cat_unord' to exclude items from ordered categories.
 * blogname is the shortname of blog to show (leave blank to show current blog)
 *
 * To replace the categorylist skinvar use this form:
 * &lt;%Ordered('categorylist', show, templatename, blogname)%&gt;
 * where
 * show is either 'ordered' or 'unordered' or 'all' and indicates which to show. blank shows just the ordered items.
 * templatename is the name of template to use to display items
 * blogname is the shortname of blog to show (leave blank to show current blog)
 *
 * To set the order for nextlink and prevlink, use this skinvar in the head section above first call to nextlink or prevlink. Th form is:
 * &lt;%Ordered('setnavigation', show, amount, setcat)%&gt;
 * where
 * show is either 'ordered' or 'unordered' or 'all' and indicates which to show. Should match your use of blog form of skinvar. blank shows just the ordered items.
 * amount is an integer indicating how many items to show on page. can have offset like blog skinvar
 * setcat is yes or no and indicates whether the catid variable should be set according to category of item, even if not set in uri
 */

/* To Do:
  *
  * Make admin page forms handle all at once instead of one form per line
  * Use the blog offset when getting random items, check if use it elsewhere and be sure to make that correct as well.
  */

/* History:
 *
 * 1.36 - 02/26/2010 -
 *  * allow a show parameter of 'none' to permit use of some of the features like sorting by author, and category-specific templates for items without overhead of ordered query
 * 1.35 - 10/14/2009 -
 *  * allow listing of items under each category in categorylist. See help file for syntax.
 * 1.34.01 - 08/06/2009 -
 *  * fix bug for PHP4 using get_class() that kept blog and categorylist from showing anything
 * 1.34 - 06/11/2009 -
 *  * fix bug where call to member function on non-object errors displayed on error and member pages
 * 1.33 - 02/28/2009 -
 *  * fix bug where blogs created after installation of NP_Ordered do not get inserted into plug_ordered_bloglist
 *  * add reversename, short, reverseshort to special sort orders for bloglist type.
 *  * add parameter format to allow min and max order values to be specified for blog, categorylist, and bloglist
 * 1.32 - 02/18/2009 - 
 *  * fix bug where items created by blog team members not getting order set and causing errors in display (thanks senderf)
 * 1.31 - 01/12/2009 - 
 *  * adjust bloglist skinvar parameters to allow for better control over how items are displayed under each blog
 * 1.30 - 12/30/2008 - 
 *  * add bloglist ordering
 *  * add special views ordering for blog variable to order by number of views in NP_Views
 * 1.29.02 - 09/15/2008 -
 *  * add PreCategoryListItem event to showCategoryList() method. This is a new event coming in a future release of Nucleus
 * 1.29.01 - 09/05/2008 -
 *  * fix item form where specified item only displays if on its own blog or category. Should really display everywhere now.
 * 1.28 - 09/03/2008 -
 *  * fix so doesn't use sscanf() function. Some PHP installs block it for security reason.
 * 1.27 - 08/29/2008 -
 *  * fix so blog admins can set order on Add Item.
 * 1.26 - 08/08/2008 -
 *  * add special value for category parameter of blog-type skinvar. Use %ALL% to show items from all blogs, regardless of the category being set by URL
 * 1.25.01 - 01/14/2008 -
 *  * fix bug where blogname parameter in blog-type not working.
 * 1.25 - 11/19/2007 -
 *  * add random key to item type of skinvar
 *  * add random order option to blog type of skinvar
 *  * add random order option to categorylist type of skinvar
 * 1.24 - 09/20/2007 -
 *  * fix _generateForm() method to output valid HTML for 3.3 release
 *  * add ability to specify the item to show when using the item type
 * 1.23 - 05/22/2007 -
 *  * add option to show param (for blog and setnavigation skinvars) to allow for custom sorting for unordered items. by time and title (documented) and author, authorname, and category (undocumented).
 *  * add option to show param (for categorylist skinvar) to allow for custom sorting for unordered categories. by name and desc (description).
 * 1.22 - 05/10/2007 -
 *  * set admin page and doAction to use ticket system for compliance with v3.3
 * 1.21 - 02/21/2007 -
 *  * fix in ordered/index.php, to be explicit about action parameter location (use $thispage). Was failing in certain environments.
 * 1.20 - 12/01/2006 -
 *  * added support for setting template to be used for item detail page per category. Requires use of new item form of Ordered skinvar
 * 1.10 - 11/16/2006 -
 *  * added catiscurrent as template var in category List fields. (Useful for putting a class param in the links for cats that can be different is listing the current cat.
 *	* Added templatemode to override special cat template if wanted.
 *	* Added setnavigation form of skinvar to set next and prev item for item pages. Put in head section, above any call to nextlink or prevlink
 *	* Fixed how handle offset in blog form of skinvar.
 *  * Add API function getQueryResult().
 * 1.00 - 11/10/2006 - initial release
 */

class NP_Ordered extends NucleusPlugin {

    var $amountfound;
    var $showWhat = 1;
	var $templatemode = '';
	var $getresult = 0;
	var $useitemtemplate = 0;
	var $respectCategory = 1;
	var $showItem = 0;
	var $low = 0;
	var $high = 0;

	// name of plugin
	function getName() {
		return 'Ordered';
	}

	// author of plugin
	function getAuthor()  {
		return 'Frank Truscott';
	}

	// an URL to the author's website
	function getURL()
	{
		return "http://revcetera.com/ftruscot";
	}

	// version of the plugin
	function getVersion() {
		return '1.36';
	}

	// a description to be shown on the installed plugins listing
	function getDescription() {
		return 'Lets you determine the order blog items are displayed on index pages, or categories are listed';
	}

    function supportsFeature($what)	{
		switch($what) {
		case 'SqlTablePrefix':
			return 1;
		case 'HelpPage':
			return 1;
		default:
			return 0;
		}
	}

    function install() {
		$this->createOption('quickmenu', 'Show Admin Area in quick menu?', 'yesno', 'yes');
        $this->createOption('del_uninstall', 'Delete NP_Ordered data tables on uninstall?', 'yesno','no');
// create and populate table for item order
        sql_query("CREATE TABLE IF NOT EXISTS ".sql_table('plug_ordered_blog')." (`oitemid` int(11) NOT NULL, `onumber` int(11) NOT NULL default '0', PRIMARY KEY(`oitemid`), UNIQUE KEY `oitemid` (`oitemid`)) TYPE=MyISAM;");

        $oarr = array();
        $ores = sql_query("SELECT * FROM ".sql_table('plug_ordered_blog'));
        while ($item = mysql_fetch_object($ores)) {
            $oarr[$item->oitemid] = $item->onumber;
        }
        $ires = sql_query("SELECT inumber FROM ".sql_table('item'));
        while ($item = mysql_fetch_object($ires)) {
            if (!in_array($item->inumber, array_keys($oarr))) {
                sql_query("INSERT INTO ".sql_table('plug_ordered_blog')." VALUES($item->inumber,'0')");
            }
        }
// create and populate table for catgegory order
		sql_query("CREATE TABLE IF NOT EXISTS ".sql_table('plug_ordered_cat')." (`ocatid` int(11) NOT NULL, `onumber` int(11) NOT NULL default '0', `otemplate` varchar(20) NOT NULL default '', `oitemplate` varchar(20) NOT NULL default '', `omainpage` tinyint(2) NOT NULL default '1', PRIMARY KEY(`ocatid`), UNIQUE KEY `ocatid` (`ocatid`)) TYPE=MyISAM;");

		if (mysql_num_rows(sql_query("SHOW COLUMNS FROM ".sql_table('plug_ordered_cat')." LIKE '%oitemplate%'")) == 0) {
			sql_query("ALTER TABLE ".sql_table('plug_ordered_cat')." ADD `oitemplate` varchar(20) NOT NULL default '' AFTER `otemplate`");
	  		sql_query("UPDATE ".sql_table('plug_ordered_cat')." SET oitemplate = ''");
		}
		$oarr = array();
        $ores = sql_query("SELECT * FROM ".sql_table('plug_ordered_cat'));
        while ($item = mysql_fetch_object($ores)) {
            $oarr[$item->ocatid] = $item->onumber;
        }
        $ires = sql_query("SELECT catid FROM ".sql_table('category'));
        while ($item = mysql_fetch_object($ires)) {
            if (!in_array($item->catid, array_keys($oarr))) {
                sql_query("INSERT INTO ".sql_table('plug_ordered_cat')." VALUES($item->catid,'0','','','1')");
            }
        }
		
// create and populate table for bloglist order
        sql_query("CREATE TABLE IF NOT EXISTS ".sql_table('plug_ordered_bloglist')." (`oblogid` int(11) NOT NULL, `onumber` int(11) NOT NULL default '0', PRIMARY KEY(`oblogid`), UNIQUE KEY `oblogid` (`oblogid`)) TYPE=MyISAM;");

        $oarr = array();
        $ores = sql_query("SELECT * FROM ".sql_table('plug_ordered_bloglist'));
        while ($item = mysql_fetch_object($ores)) {
            $oarr[$item->oblogid] = $item->onumber;
        }
        $ires = sql_query("SELECT bnumber FROM ".sql_table('blog'));
        while ($item = mysql_fetch_object($ires)) {
            if (!in_array($item->bnumber, array_keys($oarr))) {
                sql_query("INSERT INTO ".sql_table('plug_ordered_bloglist')." VALUES($item->bnumber,'0')");
            }
        }
    }

    function unInstall() {
		if ($this->getOption('del_uninstall') == 'yes')	{
			sql_query('DROP TABLE '.sql_table('plug_ordered_blog'));
			sql_query('DROP TABLE '.sql_table('plug_ordered_cat'));
			sql_query('DROP TABLE '.sql_table('plug_ordered_bloglist'));
		}
    }

	function getEventList() { return array('QuickMenu','PostAddItem','PreUpdateItem','PreDeleteItem','AddItemFormExtras','EditItemFormExtras','PostAddCategory','PreDeleteCategory','PostDeleteBlog','PostAddBlog'); }

	function getTableList() { return array(sql_table('plug_ordered_blog'),sql_table('plug_ordered_cat'),sql_table('plug_ordered_bloglist')); }

	function hasAdminArea() { return 1; }

	function event_QuickMenu(&$data) {
    	// only show when option enabled
    	if ($this->getOption('quickmenu') != 'yes') return;
    	global $member;
    	if (!($member->isLoggedIn())) return;
    	array_push($data['options'],
      		array('title' => 'Ordered',
        	'url' => $this->getAdminURL(),
        	'tooltip' => 'Manage Item/Cat Order'));
  	}

    function event_PostAddItem(&$params) {
		global $member;
		if ($member->blogAdminRights(getBlogIDFromItemID($params['itemid']))) {
			sql_query("INSERT INTO ".sql_table('plug_ordered_blog')." VALUES('".intval($params['itemid'])."','".intval(postVar('plug_ob_order'))."')");
		}
		else {			
			sql_query("INSERT INTO ".sql_table('plug_ordered_blog')." VALUES('".intval($params['itemid'])."','0')");
		}
    }

    function event_PreUpdateItem(&$params) {
		global $member;
		if ($member->blogAdminRights(getBlogIDFromItemID($params['itemid']))) {
			sql_query("UPDATE ".sql_table('plug_ordered_blog')." SET onumber='".intval(postVar('plug_ob_order'))."' WHERE oitemid='".intval($params['itemid'])."'");
		}
	}

    function event_PreDeleteItem(&$params) {
        sql_query("DELETE FROM ".sql_table('plug_ordered_blog')." WHERE oitemid='".intval($params['itemid'])."'");
    }

    function event_PostAddCategory(&$params) {
        sql_query("INSERT INTO ".sql_table('plug_ordered_cat')." VALUES('".intval($params['catid'])."','0','','','1')");
    }

    function event_PreDeleteCategory(&$params) {
        sql_query("DELETE FROM ".sql_table('plug_ordered_cat')." WHERE ocatid='".intval($params['catid'])."'");
    }
	
	function event_PostAddBlog(&$params) {
		$b =& $params['blog'];
        sql_query("INSERT INTO ".sql_table('plug_ordered_bloglist')." VALUES('".intval($b->blogid)."','0')");
    }
	
	function event_PostDeleteBlog(&$params) {
        sql_query("DELETE FROM ".sql_table('plug_ordered_bloglist')." WHERE oblogid='".intval($params['blogid'])."'");
    }

    /**
     * Add a keywords entry field to the add item page or bookmarklet.
     *
     * @params an associative array containing 'blog' which is a reference to
     *      the blog object.
     */
    function event_AddItemFormExtras(&$params)
    {
        $this->_generateForm('add');
    }
    /**
     * Adds a keywords entry field to the edit item page or bookmarklet.
     *
     * @param array $params An associative array of <ul>
     *  <li><b>&blog</b>- reference to a BLOG object. </li>
     *  <li><b>variables</b>- an associative array containing all sorts of
     *        information on the item being edited: 'itemid', 'draft', ... </li>
     *  <li><b>itemid</b>- shortcut to the itemID</li>
     * </ul>
     */
    function event_EditItemFormExtras(&$params)
    {
        //echo '<pre>';print_r($params);echo '</pre>';
        $itemid = $params['itemid'];
        $myres = sql_query("SELECT onumber FROM ".sql_table('plug_ordered_blog')." WHERE oitemid='$itemid'");
        if (mysql_num_rows($myres)) 
			$currentorder = intval(mysql_result($myres,0));
		else 
			$currentorder = 0;
        $this->_generateForm($currentorder);
    }

	function doSkinVar($skinType,$kind = 'blog', $show = '',$template = '',$amount = '',$category = '', $blogname = '') {
        global $manager, $blog, $startpos;

		switch ($kind) {
		case 'blog':
			if (!intval($amount)) $amount = 10;
            list($showwhat,$specialorder) = explode("(",$show);
			$specialorder = str_replace(")","",$specialorder);
            $show = $showwhat;
			$this->low = 0;
			$this->high = 0;
			if (strpos($specialorder, '|') !== false) {
				$special = explode('|',$specialorder);
				$specialorder = $special[0];
				$low = intval($special[1]);
				if (isset($special[2])) $high = intval($special[2]);
				else $high = 0;
				$this->low = $low;
				if ($high > $low) $this->high = $high;
			}

            if ($specialorder) {
                $theorder_array = explode("-",$specialorder);
                $theorder_array[0] = strtolower($theorder_array[0]);
                switch ($theorder_array[0]) {
                    case 'title':
                        $theorder = $theorder_array[0];
                    break;
                    case 'author':
                        $theorder = $theorder_array[0];
                    break;
                    case 'authorname':
                        $theorder = $theorder_array[0];
                    break;
                    case 'category':
                        $theorder = $theorder_array[0];
                    break;
					case 'views':
						if ($manager->pluginInstalled('NP_Views')) {
							$theorder = $theorder_array[0];
						}
						else {
							$theorder = 'itime';
						}
					break;
                    default:
                        $theorder = 'itime';
                    break;
                }
                switch (strtoupper($theorder_array[1])) {
                    case 'ASC':
                        $theorder .= " ASC";
                    break;
					case 'RANDOM':
						$theorder = " RAND()";
					break;
                    default:
                        $theorder .= " DESC";
                    break;
                }
            }
            else {
                $theorder = "itime DESC";
            }

//			list($limit, $offset) = sscanf($amount, '%d(%d)');
			list($limit, $offset) = explode("(",str_replace(")","",$amount));			
			if (!is_numeric($limit)) $limit = 10;
			if (!is_numeric($offset)) $offset = '';
			list($template,$tmode) = explode("(",$template);
			$this->templatemode = str_replace(")","",$tmode);
			if ($blogname != '') {
				$b =& $manager->getBlog(getBlogIDFromName($blogname));
				$btype = 'otherblog';
				$useSP = 0;
			}
			else {
				$b =& $blog;
				$btype = 'blog';
				$useSP = 1;
			}
		
			//if (!is_object($b) || strtoupper(get_class($b)) != 'BLOG') return "";
			if (!is_object($b) || !(strtoupper(get_class($b)) == 'BLOG' || is_subclass_of($b,'blog'))) return "";

			if (strtolower($show) == 'unordered') $this->setshowWhat(0);
			elseif (strtolower($show) == 'all') $this->setshowWhat(2);
			elseif (strtolower($show) == 'none') $this->setshowWhat(3);
			else $this->setshowWhat(1);

			$this->_setBlogCategory($b, $category);
            if ($this->getresult) {
                $oquery = $this->_getBlogQuery($b,$extraQuery,$limit,$offset,$startpos,$theorder);
                $oresult = sql_query($oquery);
                $this->getresult = 0;
                return $oresult;
            }
            else {
                $this->_preBlogContent($btype,$b);
                if ($useSP) {
                    $this->amountfound = $this->readLog($b,$template, $limit, $offset, $startpos, $theorder);
                }
                else {
                    $this->amountfound = $this->readLog($b,$template, $limit, $offset, $startpos, $theorder);
                }
                $this->_postBlogContent($btype,$b);
            }
			break;
		case 'categorylist':
			$itemamount = intval($blogname);
			$itemtemplate = $category;

			if ($amount != '') {
				$blogname = $amount;
			}
			else {
				$blogname = '';
			}

            list($showwhat,$specialorder) = explode("(",$show);
			$specialorder = str_replace(")","",$specialorder);
            $show = $showwhat;
			$this->low = 0;
			$this->high = 0;
			if (strpos($specialorder, '|') !== false) {
				$special = explode('|',$specialorder);
				$specialorder = $special[0];
				$low = intval($special[1]);
				if (isset($special[2])) $high = intval($special[2]);
				else $high = 0;
				$this->low = $low;
				if ($high > $low) $this->high = $high;
			}

            if ($specialorder) {
                $theorder_array = explode("-",$specialorder);
                if (!in_array(strtolower($theorder_array[0]),array('name','desc'))) $theorder_array[0] = 'name';
                $theorder = strtolower($theorder_array[0]);
                switch (strtoupper($theorder_array[1])) {
                    case 'DESC':
                        $theorder .= " DESC";
                    break;
					case 'RANDOM':
						$theorder = " RAND()";
					break;
                    default:
                        $theorder .= " ASC";
                    break;
                }
            }
            else {
                $theorder = "name ASC";
            }

			if (strtolower($show) == 'unordered') $this->setshowWhat(0);
			elseif (strtolower($show) == 'all') $this->setshowWhat(2);
			elseif (strtolower($show) == 'none') $this->setshowWhat(3);
			else $this->setshowWhat(1);

			if ($blogname == '') {
				$b =& $blog;
			} else {
				$b =& $manager->getBlog(getBlogIDFromName($blogname));
			}
			
			if (!is_object($b) || !(strtoupper(get_class($b)) == 'BLOG' || is_subclass_of($b,'blog'))) return "";
				
            if ($this->getresult) {
                $oquery = $this->_getCatQuery($b,$theorder);
                $oresult = sql_query($oquery);
                $this->getresult = 0;
                return $oresult;
            }
            else {
				
                $this->_preBlogContent('categorylist',$b);
                $this->showCategoryList($b,$template,$theorder,$itemtemplate,$itemamount);
                $this->_postBlogContent('categorylist',$b);
            }
			break;
		case 'item':
			global $itemid, $highlight;

			if (strtolower($template) == 'random') {
				$iid = $this->getRandomItem($amount);
				$this->respectCategory = 0;
				$this->showItem = 1;
			}			
			elseif (intval($template) > 0) {
				$iid = intval($template);
				$this->respectCategory = 0;				
				$this->showItem = 1;
			}
			else {
				$iid = $itemid;
				$this->respectCategory = 1;
				$this->showItem = 1;
			}

			list($template,$tmode) = explode("(",$show);
			$this->templatemode = str_replace(")","",$tmode);
			// $b =& $blog;
			$b =& $manager->getBlog(getBlogIDFromItemID($iid));

			if (!is_object($blog) || get_class($blog) != 'BLOG') 
				$blg =& $b;
			else
				$blg =& $blog;

			$this->useitemtemplate = 1;
            $this->setshowWhat(3);

			$this->_setBlogCategory($blg, '');	// need this to select default category
			$this->_preBlogContent('item',$blg);
			$r = $this->_showOneitem($b,$iid, $template, $highlight);
			if ($r == 0)
				echo _ERROR_NOSUCHITEM;
			$this->_postBlogContent('item',$blg);
			$this->showItem = 0;
			$this->respectCategory = 1;
			break;
		case 'setnavigation':
			if ($skinType != 'item') break;
			global $itemidprev,$itemtitleprev,$itemidnext,$itemtitlenext,$itemid,$catid;
			$setcat = $amount;

            list($showwhat,$specialorder) = explode("(",$show);
			$specialorder = str_replace(")","",$specialorder);
            $show = $showwhat;
			$this->low = 0;
			$this->high = 0;
			if (strpos($specialorder, '|') !== false) {
				$special = explode('|',$specialorder);
				$specialorder = $special[0];
				$low = intval($special[1]);
				if (isset($special[2])) $high = intval($special[2]);
				else $high = 0;
				$this->low = $low;
				if ($high > $low) $this->high = $high;
			}

            if ($specialorder) {
                $theorder_array = explode("-",$specialorder);
                switch ($theorder_array[0]) {
                    case 'title':
                        $theorder = $theorder_array[0];
                    break;
                    case 'author':
                        $theorder = $theorder_array[0];
                    break;
                    case 'authorname':
                        $theorder = $theorder_array[0];
                    break;
                    case 'category':
                        $theorder = $theorder_array[0];
                    break;
					case 'views':
						if ($manager->pluginInstalled('NP_Views')) {
							$theorder = $theorder_array[0];
						}
						else {
							$theorder = 'itime';
						}
					break;
                    default:
                        $theorder = 'itime';
                    break;
                }
                switch (strtoupper($theorder_array[1])) {
                    case 'ASC':
                        $theorder .= " ASC";
                    break;
					case 'RANDOM':
						$theorder = " RAND()";
					break;
                    default:
                        $theorder .= " DESC";
                    break;
                }
            }
            else {
                $theorder = "itime DESC";
            }

			if (!intval($template)) $amount = 10;
			else $amount = $template;
//			list($limit, $offset) = sscanf($amount, '%d(%d)');
			list($limit, $offset) = explode("(",str_replace(")","",$amount));
			if (!is_numeric($limit)) $limit = 10;
			if (!is_numeric($offset)) $offset = '';
			$b =& $manager->getBlog(getBlogIDFromItemID($itemid));
			
			if (!is_object($b) || get_class($b) != 'BLOG') return "";

			$useSP = 1;
			if (strtolower($setcat) == 'yes') {
				if (intval($itemid) && $manager->existsItem(intval($itemid),0,0)) {
					$iobj =& $manager->getItem(intval($itemid),0,0);
					$catid = intval($iobj['catid']);
					$b->setSelectedCategory($catid);
				}
			}

			if (strtolower($show) == 'unordered') $this->setshowWhat(0);
			elseif (strtolower($show) == 'all') $this->setshowWhat(2);
			elseif (strtolower($show) == 'none') $this->setshowWhat(3);
			else $this->setshowWhat(1);
			$query = $this->_getBlogQuery($b,$extraQuery,$limit,$offset,$startpos,$theorder);
			$res = sql_query($query);
			$idarr = array();
			$titarr = array();
			$i = 0;
			$curritemloc = 0;
			while ($row = mysql_fetch_object($res)) {
				$idarr[$i] = $row->itemid;
				$titarr[$i] = $row->title;
				if ($itemid == $row->itemid) $curritemloc = $i;
				$i = $i + 1;
			}
			$itemidprev = 0;
			$itemtitleprev = '';
			$itemidnext = 0;
			$itemtitlenext = '';
			if ($curritemloc > 0) {
				$itemidprev = $idarr[$curritemloc - 1];
				$itemtitleprev = $titarr[$curritemloc - 1];
			}
			if ($curritemloc < ($i - 1)) {
				$itemidnext = $idarr[$curritemloc + 1];
				$itemtitlenext = $titarr[$curritemloc + 1];
			}
			break;
		case 'bloglist':
			list($showwhat,$specialorder) = explode("(",$show);
			$specialorder = str_replace(")","",$specialorder);
            $show = $showwhat;
			if (strtolower($show) == 'unordered') $this->setshowWhat(0);
			elseif (strtolower($show) == 'all') $this->setshowWhat(2);
			elseif (strtolower($show) == 'none') $this->setshowWhat(3);
			else $this->setshowWhat(1);
			$this->low = 0;
			$this->high = 0;
			if (strpos($specialorder, '|') !== false) {
				$special = explode('|',$specialorder);
				$specialorder = $special[0];
				$low = intval($special[1]);
				if (isset($special[2])) $high = intval($special[2]);
				else $high = 0;
				$this->low = $low;
				if ($high > $low) $this->high = $high;
			}

			switch ($specialorder) {
				case 'oldestblog':
					$sortby = 'bnumber ASC';
				break;
				case 'newestblog':
					$sortby = 'bnumber DESC';
				break;
				case 'lastentry':
					$sortby = 'lastentry ASC';
				break;
				case 'itemcount':
					$sortby = 'itemcount DESC';
				break;
				case 'random':
					$sortby = 'RAND()';
				break;
				case 'reversename':
					$sortby = 'bname DESC';
				break;
				case 'short':
					$sortby = 'bshortname ASC';
				break;
				case 'reverseshort':
					$sortby = 'bshortname DESC';
				break;
				default:					
					$sortby = 'bname ASC';
				break;
			}

			$blogamount = intval($amount);
			$amount = intval($blogname);
			$itemtemplate = $category;
			
//if ($blogamount > 0) $sortby .= " LIMIT $blogamount";
//echo "blogamount: $blogamount <br />";
//echo "amount: $amount <br />";
			$this->showBlogList($template,'blogname',$sortby,$itemtemplate,$amount,$blogamount);
			break;
		default:
			echo "Incorect usage of &lt;%Ordered(...)%&gt;. The first parameters must be either 'blog' or 'categorylist'.<br />";
			break;
		}

	}

	function doAction($type) {
		global $member,$CONF,$manager;

		$onumber = intval(postVar('onumber'));
		$bid = intval(postVar('bid'));
		if ($type == 'modorderc') {
			$otemplate = trim(postVar('otemplate'));
			$oitemplate = trim(postVar('oitemplate'));
			$cid = intval(postVar('ocatid'));
			$omp = intval(postVar('omp'));
		}
		else {
			$iid = intval(postVar('oitemid'));
		}
		if (!$member->blogAdminRights($bid)) {
			doError("You do not have access to this function");
		}
        if (!$manager->checkTicket()) doError("Invalid Ticket");

		switch ($type) {
			case 'modorderi':
				sql_query("UPDATE ".sql_table('plug_ordered_blog')." SET onumber='$onumber' WHERE oitemid='$iid'");
				$destURL = $CONF['PluginURL'] . "ordered/index.php?showlist=items&bshow=$bid";
				header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
			case 'modorderc':
				sql_query("UPDATE ".sql_table('plug_ordered_cat')." SET onumber='$onumber', omainpage='$omp', otemplate='".addslashes($otemplate)."', oitemplate='".addslashes($oitemplate)."' WHERE ocatid='$cid'");
				$destURL = $CONF['PluginURL'] . "ordered/index.php?showlist=cats&bshow=$bid";
				header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
			case 'modorderb':
				sql_query("UPDATE ".sql_table('plug_ordered_bloglist')." SET onumber='$onumber' WHERE oblogid='$bid'");
				$destURL = $CONF['PluginURL'] . "ordered/index.php?showlist=blogs";
				header('Location: ' . $manager->addTicketToUrl($destURL));
			break;
			default:
				doError("Not a valid action");
			break;
		}

	}

    function getQueryResult($kind = 'blog', $show = '',$amount = '',$category = '', $blogname = '') {
        global $type;
        $template = 'dummy';
        if (trim(strtolower($kind)) == 'setnavigation') doError("Not a permitted action for this method - setnavigation");
        $this->getresult = 1;
        $oresult = $this->doSkinVar($type,$kind,$show,$template,$amount,$category,$blogname);
        return $oresult;
    }
/******************************************************
 *          Protected Methods                         *
 ******************************************************/
    function _generateForm($keywordstring='')
    {
		global $member, $itemid;

        echo "<h3>NP_Ordered</h3>\n";
		if ($keywordstring == 'add') {
			$blogid = intRequestVar('blogid');
			$keywordstring = '';
		}
		else {
			$blogid = getBlogIDFromItemID($itemid);
		}

		if ($member->blogAdminRights($blogid)) {
			printf('<p>Item Order: <input name="plug_ob_order" type="text" size="60" maxlength="256" value="%s" /></p>', $keywordstring);
		}
		else {
			printf('<p>Item Order: %s</p>', $keywordstring);
		}
        echo "\n";
    }

    function setshowWhat($value) {
        if (intval($value) == 0) $this->showWhat = 0;
        else $this->showWhat = intval($value);
    }
    function getshowWhat() {
        return $this->showWhat;
    }

// these next three functions are directly taken from the SKIN class of NucleusCMS, SKIN.php
    function _setBlogCategory(&$blog, $catname = '') {
		global $catid;
		if ($catname == '%ALL%')
			$blog->setSelectedCategory(0);
		elseif ($catname != '')
			$blog->setSelectedCategoryByName($catname);
		else
			$blog->setSelectedCategory($catid);
	}

	function _preBlogContent($type, &$blog) {
		global $manager;
		$manager->notify('PreBlogContent',array('blog' => &$blog, 'type' => $type));
	}

	function _postBlogContent($type, &$blog) {
		global $manager;
		$manager->notify('PostBlogContent',array('blog' => &$blog, 'type' => $type));
	}
// this is a slight mod of readLog method of NucleusCMS class BLOG, BLOG.php
    function readLog(&$b, $template, $amountEntries, $offset = 0, $startpos = 0, $theorder = 'itime DESC') {
		return $this->readLogAmount($b, $template,$amountEntries,'','',1,1,$offset, $startpos, $theorder);
	}
// this is a slight mod of readLogAmount method of NucleusCMS class BLOG, BLOG.php
    function readLogAmount(&$b, $template, $amountEntries, $extraQuery, $highlight, $comments, $dateheads, $offset = 0, $startpos = 0, $theorder = 'itime DESC') {
		$query = $this->_getBlogQuery($b,$extraQuery,$amountEntries,$offset, $startpos, $theorder);
		return $this->showUsingQuery($b,$template, $query, $highlight, $comments, $dateheads);
	}
// this gets the query for the blog form of the skinvar
	function _getBlogQuery(&$b,$extraQuery,$amountEntries,$offset = 0, $startpos = 0, $theorder = 'itime DESC') {
		if ($this->getshowWhat() == 2) {
			$this->setshowWhat(1);
			$query = '(';
			$query .= $this->getSqlBlog($b, $extraQuery, '', $theorder);
			$query .= ') UNION (';
			$this->setshowWhat(0);
			$query .= $this->getSqlBlog($b, $extraQuery, '', $theorder);
			//$query .= ') ORDER BY mysortcol ASC, myorder ASC, itime DESC';
            $query .= ') ORDER BY mysortcol ASC, myorder ASC, '.$theorder.', itime DESC';
			$this->setshowWhat(2);
		}
		else $query = $this->getSqlBlog($b, $extraQuery, '', $theorder);

		if ($amountEntries > 0) {
		        // $offset zou moeten worden:
		        // (($startpos / $amountentries) + 1) * $offset ... later testen ...

		       $query .= ' LIMIT ' . intval($startpos + $offset).',' . intval($amountEntries);
		}

		return $query;
	}
// this is a slight mod of getSqlBlog method of NucleusCMS class BLOG, BLOG.php
    function getSqlBlog(&$b, $extraQuery, $mode = '', $theorder = 'itime DESC')
	{
		if ($mode == '') {
			$query = 'SELECT i.inumber as itemid, i.ititle as title, i.ibody as body, m.mname as author, m.mrealname as authorname, i.itime as itime, i.imore as more, m.mnumber as authorid, m.memail as authormail, m.murl as authorurl, c.cname as category, i.icat as catid, i.iclosed as closed, oc.otemplate as otemplate, oc.oitemplate as oitemplate, oc.onumber as ocnumber, i.ititle as ititle';
			if ($this->getshowWhat() != 3) 
				$query .= ", o.onumber as myorder";
			if (strpos($theorder,'views') === 0) {
				$useviews = 1;
				$query .= ', v.views as views';
			}
			else {
				$useviews = 0;
			}
			if ($this->getshowWhat() == 1 ) {
				$query .= ', 1 as mysortcol';
			}
			elseif ($this->getshowWhat() != 3 ) {
				$query .= ', 2 as mysortcol';
			}
			else
				$query .= ', 3 as mysortcol';
		}
		else
			$query = 'SELECT COUNT(*) as result ';
		$query .= ' FROM '.sql_table('item').' as i, '.sql_table('member').' as m, '.sql_table('category').' as c, '.sql_table('plug_ordered_cat').' as oc';
		if ($this->getshowWhat() != 3 )
			$query .= ', '.sql_table('plug_ordered_blog').' as o';
		if ($useviews) $query .= ', '.sql_table('plugin_views').' as v';
		$query .= ' WHERE i.iblog='.$b->blogid;
		$query .= ' and i.iauthor=m.mnumber';
		$query .= ' and i.icat=c.catid';
		$query .= ' and i.icat=oc.ocatid';
		if ($this->getshowWhat() != 3 )
			$query .= ' and i.inumber=o.oitemid';
		$query .= ' and i.idraft=0';	// exclude drafts
					// don't show future items
		$query .= ' and i.itime<=' . mysqldate($b->getCorrectTime());
		if ($useviews) $query .= ' and i.inumber=v.id';

		if ($b->getSelectedCategory() && $this->respectCategory)
			$query .= ' and i.icat=' . $b->getSelectedCategory() . ' ';
		elseif (!$this->showItem) $query .= ' and oc.omainpage=1 ';

        if ($this->showWhat == 0 ) {
            $query .= ' and o.onumber=0 ';
        }
        elseif ($this->getshowWhat() != 3 ) {
            $query .= ' and o.onumber>'.$this->low.' ';
			if ($this->high > $this->low)
				$query .= ' and o.onumber<='.$this->high.' ';
        }

		$query .= $extraQuery;

        if ($this->showWhat == 0 ) {
            //$query .= ' ORDER BY i.'.$theorder;
            $query .= ' ORDER BY '.$theorder.', itime DESC';
        }
		elseif ($this->getshowWhat() == 3 ) {
			$query .= ' ORDER BY '.$theorder.', itime DESC';
		}
        else {
            $query .= ' ORDER BY o.onumber ASC';
        }


		return $query;
	}

	function showUsingQuery(&$b, $templateName, $query, $highlight = '', $comments = 0, $dateheads = 1) {
		global $CONF, $manager;

		$lastVisit = cookieVar($CONF['CookiePrefix'] .'lastVisit');
		if ($lastVisit != 0)
			$lastVisit = $b->getCorrectTime($lastVisit);

		// set templatename as global variable (so plugins can access it)
		global $currentTemplateName;
		$currentTemplateName = $templateName;
		$origtemplate = $templateName;
		$currtemplate = $templateName;

		$template =& $manager->getTemplate($templateName);

		// create parser object & action handler
		$actions =& new ITEMACTIONS($b);
		$parser =& new PARSER($actions->getDefinedActions(),$actions);
		$actions->setTemplate($template);
		$actions->setHighlight($highlight);
		$actions->setLastVisit($lastVisit);
		$actions->setParser($parser);
		$actions->setShowComments($comments);

		// execute query
//echo "<hr />$query <hr />";
		$items = sql_query($query);

		// loop over all items
		while ($item = mysql_fetch_object($items)) {

			// reset template if needed
			if (strtolower(trim($this->templatemode)) != 'strict') {
				if ($this->useitemtemplate) {
					$temptype = 'oitemplate';
				}
				else $temptype = 'otemplate';
				if ($item->$temptype == '') {
					if ($currtemplate != $origtemplate) {
						$template =& $manager->getTemplate($origtemplate);
						$actions->setTemplate($template);
						$currtemplate = $origtemplate;
						$currentTemplateName = $origtemplate;
					}
				}
				else {
					if ($currtemplate != $item->$temptype) {
						$template =& $manager->getTemplate($item->$temptype);
						$actions->setTemplate($template);
						$currtemplate = $item->$temptype;
						$currentTemplateName = $item->$temptype;
					}
				}
			}

			$item->timestamp = strtotime($item->itime);	// string timestamp -> unix timestamp

			// action handler needs to know the item we're handling
			$actions->setCurrentItem($item);

			// add date header if needed
			if ($dateheads) {
				$new_date = date('dFY',$item->timestamp);
				if ($new_date != $old_date) {
					// unless this is the first time, write date footer
					$timestamp = $item->timestamp;
					if ($old_date != 0) {
						$oldTS = strtotime($old_date);
						$manager->notify('PreDateFoot',array('blog' => &$b, 'timestamp' => $oldTS));
						$tmp_footer = strftime($template['DATE_FOOTER'], $oldTS);
						$parser->parse($tmp_footer);
						$manager->notify('PostDateFoot',array('blog' => &$b, 'timestamp' => $oldTS));
					}
					$manager->notify('PreDateHead',array('blog' => &$b, 'timestamp' => $timestamp));
					// note, to use templatvars in the dateheader, the %-characters need to be doubled in
					// order to be preserved by strftime
					$tmp_header = strftime($template['DATE_HEADER'],$timestamp);
					$parser->parse($tmp_header);
					$manager->notify('PostDateHead',array('blog' => &$b, 'timestamp' => $timestamp));
				}
				$old_date = $new_date;
			}

			// parse item
			$parser->parse($template['ITEM_HEADER']);
			$manager->notify('PreItem', array('blog' => &$b, 'item' => &$item));
			$parser->parse($template['ITEM']);
			$manager->notify('PostItem', array('blog' => &$b, 'item' => &$item));
			$parser->parse($template['ITEM_FOOTER']);

		}

		$numrows = mysql_num_rows($items);

		// add another date footer if there was at least one item
		if (($numrows > 0) && $dateheads) {
			$manager->notify('PreDateFoot',array('blog' => &$b, 'timestamp' => strtotime($old_date)));
			$parser->parse($template['DATE_FOOTER']);
			$manager->notify('PostDateFoot',array('blog' => &$b, 'timestamp' => strtotime($old_date)));
		}

		mysql_free_result($items);	// free memory

		return $numrows;

	}

	function _showOneitem(&$b, $itemid, $template, $highlight) {
		$extraQuery = ' and inumber=' . intval($itemid);

		return $this->readLogAmount($b, $template, 1, $extraQuery, $highlight, 0, 0);
	}


/*
 * functions for kind=categorylist
 */
	// mod of showCategoryList() method of Nucleus BLOG class.
	/**
	  * Shows the list of categories using a given template
	  */
	function showCategoryList(&$b, $template, $theorder = 'name ASC', $itemplate = '', $amount = 0) {
		global $CONF, $manager;

		// determine arguments next to catids
		// I guess this can be done in a better way, but it works
		global $archive, $archivelist;

		$linkparams = array();
		if ($archive) {
			$blogurl = createArchiveLink($b->getID(), $archive, '');
			$linkparams['blogid'] = $b->getID();
			$linkparams['archive'] = $archive;
		} else if ($archivelist) {
			$blogurl = createArchiveListLink($b->getID(), '');
			$linkparams['archivelist'] = $archivelist;
		} else {
			$blogurl = createBlogidLink($b->getID(), '');
			$linkparams['blogid'] = $b->getID();
		}

		//$blogurl = $b->getURL() . $qargs;
		$blogurl = createBlogLink($b->getURL(), $linkparams);
		
		list($itemplate,$listtags) = explode("(",$itemplate);
		$listtags = str_replace(")","",$listtags);
		list($pretag,$posttag) = explode('|',$listtags);

		$template =& $manager->getTemplate($template);

		echo TEMPLATE::fill($template['CATLIST_HEADER'],
							array(
								'blogid' => $b->getID(),
								'blogurl' => $blogurl,
								'self' => $CONF['Self']
							));

		$query = $this->_getCatQuery($b,$theorder);

		$res = sql_query($query);

		while ($data = mysql_fetch_assoc($res)) {
			$data['blogid'] = $b->getID();
			$data['blogurl'] = $blogurl;
			$data['catlink'] = createLink(
								'category',
								array(
									'catid' => $data['catid'],
									'name' => $data['catname'],
									'extra' => $linkparams
								)
							   );
			$data['self'] = $CONF['Self'];
			if ($b->getSelectedCategory()) {
				if ($b->getSelectedCategory() == $data['catid']) {
					$data['catiscurrent'] = 'yes';
				}
				else {
					$data['catiscurrent'] = 'no';
				}
			}
			else {
				global $itemid;
				if (intval($itemid) && $manager->existsItem(intval($itemid),0,0)) {
					$iobj =& $manager->getItem(intval($itemid),0,0);
					$cid = $iobj['catid'];
					if ($cid == $data['catid']) {
						$data['catiscurrent'] = 'yes';
					}
					else {
						$data['catiscurrent'] = 'no';
					}
				}
			}

			$manager->notify(
				'PreCategoryListItem',
				array(
					'listitem' => &$data
				)
			);
			
			$temp = TEMPLATE::fill($template['CATLIST_LISTITEM'],$data);
			echo strftime($temp,$current->itime);
/* ******* */			
			if ($amount && $itemplate != '') {
				//$b =& $manager->getBlog($list['blogid']);
//echo "<br />";
//print_r($b);
//echo "<br />";
				//$amountfound = $b->readLog($itemplate, $amount, '', '');
				$this->_setBlogCategory($b, $data['catname']);
				$csw = $this->getShowWhat();
				$this->setShowWhat(2);
				echo $pretag;
				$amountfound = $this->readLog($b,$itemplate, $amount, '', '', "itime DESC");
				echo $posttag;
				$this->setShowWhat($csw);
//echo "<br />amountfound: $amountfound";
			}

		}

		mysql_free_result($res);

		echo TEMPLATE::fill($template['CATLIST_FOOTER'],
							array(
								'blogid' => $b->getID(),
								'blogurl' => $blogurl,
								'self' => $CONF['Self']
							));
	}

    function _getCatQuery(&$b, $theorder = 'name ASC') {
        if ($this->getshowWhat() == 2) {
			$this->setshowWhat(1);
			$query = '(';
			$query .= $this->getSqlCat($b,$theorder);
			$query .= ') UNION (';
			$this->setshowWhat(0);
			$query .= $this->getSqlCat($b,$theorder);
			if ($theorder == " RAND()") $query .= ') ORDER BY '.$theorder;
			else $query .= ') ORDER BY mysortcol ASC, myorder ASC, cat'.$theorder;
			$this->setshowWhat(2);
		}
		else $query = $this->getSqlCat($b,$theorder);
//echo $query;
        return $query;
    }

	function getSqlCat(&$b, $theorder = 'name ASC') {
		$query = 'SELECT c.catid, c.cdesc as catdesc, c.cname as catname, o.onumber as myorder, o.otemplate as mytemplate, o.oitemplate as myitemtemplate, o.omainpage as myshowonmainpage';
		if ($this->getshowWhat() == 1 ) {
			$query .= ', 1 as mysortcol';
		}
		elseif ($this->getshowWhat() != 3 ) {
			$query .= ', 2 as mysortcol';
		}
		else {
			$query .= ', 3 as mysortcol';
		}
		$query .= ' FROM '.sql_table('category').' as c, '.sql_table('plug_ordered_cat').' as o WHERE c.catid=o.ocatid AND c.cblog=' . $b->getID();

        if ($this->showWhat == 0 ) {
			$query .= ' AND o.onumber=0';
			if ($theorder == " RAND()") $query .= ' ORDER BY'.$theorder;
            else $query .= ' ORDER BY c.c'.$theorder;
        }
		elseif ($this->showWhat == 3 ) {
			if ($theorder == " RAND()") $query .= ' ORDER BY'.$theorder;
            else $query .= ' ORDER BY c.c'.$theorder;
		}
        else {
			$query .= ' AND o.onumber>'.$this->low;			
			if ($this->high > $this->low)
				$query .= ' AND o.onumber<='.$this->high;
            $query .= ' ORDER BY o.onumber ASC';
        }
		return $query;
	}
	
/* Functions for bloglist */
	function showBlogList($template, $bnametype = 'blogname', $sortby = 'lastentry DESC', $itemplate = '', $amount = 0, $blogamount = 0) {
		global $CONF, $manager, $DB_NAME;

		if (strpos($template,'feeds') !== false) $isRSS = true;
		else $isRSS = false;
		
		list($itemplate,$listtags) = explode("(",$itemplate);
		$listtags = str_replace(")","",$listtags);
		list($pretag,$posttag) = explode('|',$listtags);
		if ($itemplate == '') $itemplate = $template;
		$template =& $manager->getTemplate($template);

		echo TEMPLATE::fill((isset($template['BLOGLIST_HEADER']) ? $template['BLOGLIST_HEADER'] : null),
							array(
								'sitename' => $CONF['SiteName'],
								'siteurl' => $CONF['IndexURL']
							));

		$query = $this->_getBlogListQuery($sortby,$blogamount);
		$res = sql_query($query);

		if (mysql_num_rows($res) < 1) {
			echo "";
			return;
		}

		while ($data = mysql_fetch_assoc($res)) {

			$list = array();

//			$list['bloglink'] = createLink('blog', array('blogid' => $data['bnumber']));
			$list['bloglink'] = createBlogidLink($data['bnumber']);

			$list['blogdesc'] = $data['bdesc'];

			if ($bnametype=='shortname') {
				$list['blogname'] = $data['bshortname'];
			}
			else { // all other cases
				$list['blogname'] = $data['bname'];
			}

			$list['blogid'] = $data['bnumber'];
			$list['blogurl'] = $data['burl'];
			$hoursago = intval($data['lastentry'] / 3600);
			if ($hoursago >= 24) {
				$list['lastentry'] = intval($hoursago / 24);
				if ($list['lastentry'] == 1) $list['hoursago'] = "day ago";
				else $list['hoursago'] = "days ago";
			}
			elseif ($hoursago == 1) {
				$list['lastentry'] = $hoursago;
				$list['hoursago'] = "hour ago";
			}
			else {
				$list['lastentry'] = $hoursago;
				$list['hoursago'] = "hours ago";
			}

			$list['itemcount'] = $data['itemcount'];
			
			$manager->notify(
				'PreBlogListItem',
				array(
					'listitem' => &$list
				)
			);

			echo TEMPLATE::fill((isset($template['BLOGLIST_LISTITEM']) ? $template['BLOGLIST_LISTITEM'] : null), $list);
//echo "<br />amount: $amount";
			if ($amount) {
				$b =& $manager->getBlog($list['blogid']);
//echo "<br />";
//print_r($b);
//echo "<br />";
				//$amountfound = $b->readLog($itemplate, $amount, '', '');
				$this->setShowWhat(2);
				echo $pretag;
				$amountfound = $this->readLog($b,$itemplate, $amount, '', '', "itime DESC");
				echo $posttag;
//echo "<br />amountfound: $amountfound";
			}
		}

		mysql_free_result($res);

		echo TEMPLATE::fill((isset($template['BLOGLIST_FOOTER']) ? $template['BLOGLIST_FOOTER'] : null),
							array(
								'sitename' => $CONF['SiteName'],
								'siteurl' => $CONF['IndexURL']
							));

	}
	
	function _getBlogListQuery($theorder = 'bname ASC', $blogamount = 0) {
		$blogamount = intval($blogamount);
        if ($this->getshowWhat() == 2) {
			$this->setshowWhat(1);
			$query = '(';
			$query .= $this->getSqlBlogList($theorder,$blogamount);
			$query .= ') UNION (';
			$this->setshowWhat(0);
			$query .= $this->getSqlBlogList($theorder,$blogamount);
			if ($theorder == " RAND()") $query .= ') ORDER BY '.$theorder;
			else $query .= ') ORDER BY mysortcol ASC, myorder ASC, '.$theorder;
			$this->setshowWhat(2);
		}
		else $query = $this->getSqlbloglist($theorder,$blogamount);
		
		if ($blogamount > 0) $query .= " LIMIT $blogamount";
//echo $query;

        return $query;
    }

	function getSqlBlogList($theorder = 'bname ASC', $blogamount = 0) {
		$query = 'SELECT b.bnumber AS bnumber, b.bname AS bname, b.bshortname AS shortname, b.bdesc AS bdesc, b.burl AS burl, o.onumber as myorder, ';
		$query .= "(SELECT (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(itime)) FROM ".sql_table('item')." WHERE iblog=b.bnumber ORDER BY itime DESC LIMIT 1) AS lastentry, ";
		$query .= "(SELECT COUNT(*) FROM ".sql_table('item')." WHERE iblog=b.bnumber) AS itemcount ";
		
		if ($this->getshowWhat() == 1 ) {
			$query .= ', 1 as mysortcol';
		}
		elseif ($this->getshowWhat() != 3 ) {
			$query .= ', 2 as mysortcol';
		}
		else
			$query .= ', 3 as mysortcol';
		$query .= ' FROM '.sql_table('blog').' as b, '.sql_table('plug_ordered_bloglist').' as o WHERE b.bnumber=o.oblogid';

        if ($this->showWhat == 0 ) {
			$query .= ' AND o.onumber=0';
			if ($theorder == "RAND()") $query .= ' ORDER BY '.$theorder;
            else $query .= ' ORDER BY '.$theorder;
        }
		elseif ($this->showWhat == 3 ) {
			if ($theorder == "RAND()") $query .= ' ORDER BY '.$theorder;
            else $query .= ' ORDER BY '.$theorder;
        }
        else {
			$query .= ' AND o.onumber>'.$this->low;		
			if ($this->high > $this->low)
				$query .= ' AND o.onumber<='.$this->high;
            $query .= ' ORDER BY o.onumber ASC';
        }
		return $query;
	}

/* other functions */
	function getRandomItem($hints = '') {
/* $hints of the form blog|category|time
where blog is -1 for all blogs, 0 for current blog, and #:#:# for only those blogids, or !#:#:# for all but these blogids
and category is -1 for all categories, 0 for current category, and #:#:# for only those catids or !#:#:# for all but these catids
and time is number of days back to grab items (+# will get items newer than # days and -# will get items older than # days) and 0 means no time limit
*/
		$hintsarr = explode('|',$hints);
		// get blog restriction statement
		switch (trim($hintsarr[0])) {
			case "-1":
				$blogwhere = "iblog > 0";
			break;
			case "0":
			case "":
				global $blogid;
				$blogwhere = "iblog=".intval($blogid);
			break;
			default:
				if (substr(trim($hintsarr[0]),0,1) == '!') $inkind = "iblog NOT IN ";
				else $inkind = "iblog IN ";
				$values = "(".str_replace(array('!',':',';',' '),array('',',',',',''),$hintsarr[0]).")";
				$blogwhere = $inkind.$values;
			break;
		}

		// get category restriction statement
		switch (trim($hintsarr[1])) {
			case "-1":
				$catwhere = "icat > 0";
			break;
			case "0":
			case "":
				global $catid;
				$catwhere = "icat=".intval($blogid);
			break;
			default:
				if (substr(trim($hintsarr[1]),0,1) == '!') $inkind = "icat NOT IN ";
				else $inkind = "icat IN ";
				$values = "(".str_replace(array('!',':',';',' '),array('',',',',',''),$hintsarr[1]).")";
				$catwhere = $inkind.$values;
			break;
		}

		// get time restriction statement
		switch (trim($hintsarr[2])) {
			case "0":
			case "":
				$timewhere = "itime <= NOW()";
			break;
			default:
				if (substr(trim($hintsarr[2]),0,1) == '-') $compare = " =< ";
				else $compare = " >= ";
				$value = intval(str_replace(array('!',':',';',' ','+','-'),array('',',',',','','',''),$hintsarr[2]));
				$timewhere = "itime <= NOW() AND itime $compare DATE_SUB(NOW(),INTERVAL $value DAY)";
			break;
		}

		$query = "SELECT inumber AS result FROM ".sql_table("item")." WHERE idraft=0 AND $blogwhere AND $catwhere AND $timewhere ORDER BY RAND() LIMIT 1";
		//echo $query;
		return quickQuery($query);
	}
}
?>