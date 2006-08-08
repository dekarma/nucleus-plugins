<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * see http://nucleuscms.org/license.txt for the full license
 *
 * NP_UrlController - A Nucleus CMS Plugin (http://plugins.nucleuscms.org/)
 * Copyright (C) 2006 Rodrigo Moraes & The Nucleus Group
 *
 * NP_UrlController generates and parses URL's in the following formats:
 * - Nucleus Regular URL's (error 301 + redirection)
 * http://www.site.com/index.php?blogid=1
 * etc.
 *
 * - Nucleus Fancy URL's (error 301 + redirection)
 * http://www.site.com/blog/1
 * etc.
 *
 * - New URL's, consisting of:
 * 0 PARTS
 * site index
 * http://www.site.com
 *
 * 1 PART
 * blog index
 * http://www.site.com/blogname/
 *
 * 2 PARTS
 * item
 * http://www.site.com/blogname/this-is-an-item/
 * duplicated tem: http://www.site.com/blogname/this-is-an-item_2/
 * untitled item: http://www.site.com/blogname/post-21/
 * archivelist
 * http://www.site.com/blogname/archive/
 * member
 * http://www.site.com/members/username/
 * search
 * http://www.site.com/blogname/search?q=search_query
 *
 * 3 PARTS
 * category
 * http://www.site.com/blogname/category/categoryname
 * archive - year
 * http://www.site.com/blogname/archive/2005/ - not done yet
 *
 * 4 PARTS
 * archive - month
 * http://www.site.com/blogname/archive/2005/12/
 *
 * 5 PARTS
 * archive - day
 * http://www.site.com/blogname/archive/2005/12/03/
 *
 * @license  http://www.gnu.org/licenses/gpl.txt
 * @author   Rodrigo Moraes (moraes)
 * @version  0.1
 */
class NP_UrlController extends NucleusPlugin
{
	/**
	 * @var array $urlparts The requested URL divided into parts
	 * Ex: 	http://site.com/blogname/this-is-an-item/
	 * 		$urlparts[0] = blogname;
	 * 		$urlparts[1] = this-is-an-item;
	 */
	var $urlparts;

	/**
	 * @var array Old style url keys
	 */
	var $oldurls;

	/**
	 * @var array $blognames List of blog short names and correspondent ID's
	 * Stored serialized in a text file to save database resources
	 * Ex:	serialize(array('blogname' => #blog_id#, 'blogname2' => #blog_id2#));
	 */
	var $blognames;

	/**
	 * @var string $table SQL table name to store item fancy titles and correspondent ID's
	 */
	var $table;

	/**
	 * @var string $tmp_title Temporary title used between Pre/PostAddIte
	 */
	var $tmp_title;

	function getName()  { return 'UrlController'; }
	function getAuthor() { return 'Rodrigo Moraes (based on NP_ReplaceURL by Wouter Demuynck)'; }
	function getURL()     { return 'http://tipos.com.br/'; }
	function getVersion() { return '0.1'; }
	function getMinNucleusVersion() { return 322; }
	function getMinNucleusPatchLevel() { return 0; }
	function getTableList() {  return array( sql_table('plug_urls') ); }
	function getDescription() {	return 'Creates fancy urls for communities focused on users and the site content.';	}

	function supportsFeature($what)
	{
		switch ($what)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getEventList()
	{
		return array('ParseURL', 'GenerateURL', 'PreAddItem','PostAddItem', 'PreUpdateItem', 'PreSendContentType');
	}

	/**
	 * When the plugin is installed, create a mappings table and fill it for all
	 * existing items.
	 */
	function install()
	{
		// create mapping table
		sql_query(
			  'CREATE TABLE IF NOT EXISTS ' . $this->table
			. ' ('
			. '  url varchar(70) NOT NULL,'
			. '  urlid int(11) NOT NULL,'
			. '  itemid int(11) NOT NULL,'
			. '  PRIMARY KEY (url, urlid),'
			. '  INDEX (itemid)'
			. ' ) TYPE = innodb;'
		);

		// create mappings for all existing items
		$r = sql_query('SELECT ititle as title, inumber as itemid FROM ' . sql_table('item') . ' ORDER BY itime ASC');
		while ($o = mysql_fetch_object($r))
		{
			$this->addToMapping($this->buildFriendlyUrl($o->title, $o->itemid), $o->itemid);
		}
	}

	/**
	 * When uninstalling the plugin, remove all URL mappings from the database
	 */
	function uninstall()
	{
		sql_query('DROP TABLE IF EXISTS ' . $this->table);
	}

	/**
	 * Initializes plugin variables
	 */
	function init()
	{
		global $manager, $CONF, $DIR_PLUGINS;
		// set the url parts, the heart of the plugin ;-)
		$this->urlparts = explode("/", requestVar('url'));

		// set the database name
		$this->table = sql_table('plug_urls');

		// sets an array of blog shortnames
		$this->setBlogNames();

		// initialize cache array for item urls
		$manager->cachedInfo['itemurls'] = array();

		// set protected words for blog and/or item names
		$this->oldurls = array($CONF['ItemKey'], $CONF['ArchivesKey'], $CONF['ArchiveKey'], $CONF['BlogKey'], 'blogid', $CONF['CategoryKey'], 'catid', $CONF['MemberKey']);
	}

	/**
	 * Sets an array of blog shortnames to be used in the first part of the URL
	 */
	function setBlogNames()
	{
		// cached array implementation
		// $path = $DIR_PLUGINS . 'blogadmin/configs/fancydata.inc.php';
		// $this->blognames = unserialize(file_get_contents($path));

		// database query implementation
		$q = "SELECT bnumber, bshortname FROM " . sql_table('blog') . " ORDER BY bnumber ASC";
		$res = mysql_query($q);

		$this->blognames = array();
		while($blogdata = mysql_fetch_array($res))
		{
			$this->blognames[$blogdata['bshortname']] = $blogdata['bnumber'];
		}
	}

	/**
	 * Returns protected words for blog names
	 * @return array List of protected words
	 */
	function getBlogProtectedWords()
	{
		global $CONF;
		return $this->oldurls;
	}

	/**
	 * Returns protected words for item titles
	 * @return array List of protected words
	 */
	function getItemProtectedWords()
	{
		global $CONF;
		return array($CONF['ArchiveKey']);
	}

	/**
	 * Parses a requested URL
	 * @var array $data URL Manipulation data
	 */
	function event_ParseURL(&$data)
	{
		// url already parsed or no url parts (homepage) - http://site.com
		if($data['completed'] || empty($this->urlparts)) {
			return;
		}

		// set the default blog to be built in case something goes wrong
		global $blogid, $CONF;
		$blogid = $CONF['DefaultBlog'];

		// reserved words for part 0
		switch($this->urlparts[0])
		{
			// members: http://site.com/members/membershortname
			case $CONF['MemberKey']:
				$this->buildMemberPage();
				break;
			// build a blog
			default:
				$this->buildBlog();
		}

		// once the conditions above are processed, consider the URL parsed
		$data['completed'] = true;
		return;
	}

	/**
	 * Returns a blog ID based on urlparts[0]
	 * @return int Blog ID
	 */
	function findBlog()
	{
		if(isset($this->blognames[$this->urlparts[0]]))
		{
			return $this->blognames[$this->urlparts[0]];
		}
		return 0;
	}

	/**
	 * Starts building a blog...
	 */
	function buildBlog()
	{
		global $blogid;

		// part 0 indicates which blog is requested
		$blogid_temp = $this->findBlog();

		// @todo: show error for no valid blog
		if($blogid_temp == 0)
		{
			return;
		}

		// from now, a valid blog is selected...
		$blogid = $blogid_temp;

		// 1 part = blog index - http://site.com/blogname
		if(count($this->urlparts) == 1)
		{
			return;
		}
		else
		{
			// archivelist or archives
			if($this->urlparts[1] == $CONF['ArchiveKey'])
			{
				$this->buildArchivePage();
				return;
			}
			else
			{
				// last option: build an item
				$this->buildItemPage();
			}
		}
	}

	/**
	 * Builds an archivelist or archive page
	 * Archives can be for month or day - http://site.com/shortname/archive/2005/12[/03]
	 * @var int $blogid Blog ID correspondent to urlparts[0]
	 * @todo check for invalid years/months/days requests?
	 */
	function buildArchivePage()
	{
		if(count($this->urlparts) == 2)
		{
			global $archivelist, $blogid;
			$archivelist = $blogid;
			return;
		}
		else
		{
			global $archive;
			// archives for month
			if(count($this->urlparts) > 3)
			{
				$archive = intval($this->urlparts[2]) . '-' . intval($this->urlparts[3]);
			}

			// archives for day
			if(count($this->urlparts) == 5)
			{
				$archive .= '-' . intval($this->urlparts[4]);
			}
		}
		// @todo
		// validate years
		// $years = range(1969, date('Y'));
	}

	/**
	 * Builds a member page
	 * @todo check for invalid requests
	 */
	function buildMemberPage()
	{
		if(isset($this->urlparts[1]))
		{
			global $memberid;
			$mem = new MEMBER;
			$mem->readFromName($this->urlparts[1]);
			$memberid = $mem->getID();
		}
	}

	/**
	 * Builds a item page
	 * @todo check for invalid requests
	 */
	function buildItemPage()
	{
		// discover if this is a duplicated fancy url: the number after _ is its id
		$fancy_id = substr(strrchr($this->urlparts[1], '_'), 1);

		// no id found? so set the default id: 1
		if($fancy_id === false)
		{
			$fancy_id = 1;
			$fancy_url = $this->urlparts[1];
		}
		else
		{
			// if the fancy has an id, it has to be separated from the fancy url
			// ex: my-beautiful-fancy-url_15 => fancy_url: my-beautiful-fancy-url id: 15
			$pos = strripos($this->urlparts[1], '_' . $fancy_id);
			$fancy_url = substr($this->urlparts[1], 0, $pos);
		}

		$itemid_temp = $this->findItem($fancy_url, $fancy_id);
		if($itemid_temp > 0)
		{
			global $itemid;
			$itemid = $itemid_temp;
		}
	}

	/**
	 * Adds the given (urlpart, itemid) pair to the mappings table
	 */
	function addToMapping($urldata, $itemid)
	{
		// hmmm. haven't these checkings been done before?
		$url = addslashes($urldata['url']);
		$urlid = intval($urldata['urlid']);
		$itemid = intval($itemid);

		// [note] INSERT IGNORE is used since an update might attempt to re-add the same (urlpath,itemid) pair,
		//        resulting in a duplicate key
		$q = 'INSERT IGNORE INTO ' . $this->table . ' (url, urlid, itemid) values (\'' . $url . '\', ' . $urlid . ', ' . $itemid . ')';
		sql_query($q);
	}

	/**
	 * Builds a friendly (an unique) url for a item page
	 *
	 * @return array Fancy title and the url id (greater than 1 in case the same title already exists)
	 */
	function buildFriendlyUrl($title, $itemid)
	{
		global $DIR_PLUGINS;

		if(!empty($title))
		{
			$title = strip_tags($title);
			$title = str_replace('8220', '', $title);
			$title = str_replace('8221', '', $title);

			// includes the string manipulation class
			require_once $DIR_PLUGINS . 'urlcontroller/UrlControllerStrings.php';
			$title = UrlControllerStrings::convertSpecialChars($title);
			$title = UrlControllerStrings::replaceRepeatedChars($title, '--', '-');

			$title = trim($title, "\x00..\x2F");
			$title = substr($title, 0, 70);
		}

		if(empty($title))
		{
			$title = 'post-' . $itemid;
		}

		$result = sql_query('SELECT urlid FROM ' . $this->table . ' WHERE url = \'' . $title . '\' AND itemid != ' . $itemid . ' ORDER BY urlid DESC LIMIT 0,1');
		$urlid = 1;
		if (mysql_num_rows($result) > 0)
		{
			while ($o = mysql_fetch_object($result))
			{
				$urlid = $o->urlid + 1;
			}
		}
		// protected words for titles ('archive', by example)
		// this condition will be valid if this is the first ocurrence of the title,
		// so set it to 2. next time it will be find in the query above.
		elseif(in_array($title, $this->getItemProtectedWords()))
		{
			$urlid = 2;
		}

		return array('url' => $title, 'urlid' => $urlid);
	}

	/**
	 * Finds the item ID corresponding to a given URL part
	 */
	function findItem($url, $urlid=1)
	{
		$url = addslashes(trim($url));
		$q = 'SELECT itemid as result FROM ' . $this->table . ' WHERE url=\'' . $url . '\' AND urlid = ' . $urlid . ' LIMIT 1';
		$itemid = quickQuery($q);
		return $itemid;
	}

	/**
	 * Generate URLs for all skin parts
	 */
	function event_GenerateURL(&$data)
	{
		// if another plugin already generated the URL
		if ($data['completed']) {
			return;
		}

		global $CONF;
		$baseurl = $CONF['Self'] . '/';
		$params = $data['params'];

		switch ($data['type'])
		{
			case 'blog':
				$blogname = array_search($params['blogid'], $this->blognames);
				$baseurl .= $blogname;
				$data['url'] = addLinkParams($baseurl, $params['extra']);
				break;
			case 'archivelist':
				$blogname = array_search($params['blogid'], $this->blognames);
				$baseurl .= $blogname . '/' . $CONF['ArchiveKey'];
				$data['url'] = addLinkParams($baseurl, $params['extra']);
				break;
			case 'archive':
				$blogname = array_search($params['blogid'], $this->blognames);
				$archive = str_replace('-', '/', $params['archive']);
				$baseurl .= $blogname . '/' . $CONF['ArchiveKey'] . '/' . $archive;
				$data['url'] = addLinkParams($baseurl, $params['extra']);
				break;
			case 'item':
				global $manager;
				$item = $manager->getItem($params['itemid'], false, false);
				$blogname = array_search($item['blogid'], $this->blognames);
				$title = $this->findItemUrlById($params['itemid']);
				$baseurl .= $blogname . '/' . $title;
				$data['url'] = addLinkParams($baseurl, $params['extra']);
				break;
			case 'member':
				global $manager;
				// line below will work only on Nucleus 3.3
				// $mem =& $manager->getMember($params['memberid']);
				$mem = new MEMBER;
				$mem->readFromID(intval($params['memberid']));
				$baseurl .= $CONF['MemberKey'] . '/' . $mem->getDisplayName();
				$data['url'] = addLinkParams($baseurl, $params['extra']);
				break;
			default:
				$data['url'] = addLinkParams($baseurl, $params['extra']);
		}
		$data['completed'] = true;
		return;
	}

	/**
	 * Returns a item url (cached using the manager or from the database)
	 * @param int $itemid
	 */
	function findItemUrlById($itemid)
	{
		global $manager;
		// checks if a cached url for the item already exists
		if(!isset($manager->cachedInfo['itemurls'][$itemid]))
		{
			$url = '';
			$r = sql_query('SELECT url, urlid FROM ' . $this->table . ' WHERE itemid = ' . $itemid);
			$o = mysql_fetch_object($r);
			if($o)
			{
				$url = $o->url;
				// only shows the urlid if it is greater than 1
				// ex: site.com/blogname/my-title_2 is not the first item using this title
				if($o->urlid > 1)
				{
					$url .= '_' . $o->urlid;
				}

			}
			// set the cached url
			$manager->cachedInfo['itemurls'][$itemid] = $url;
		}
		// done!
		return $manager->cachedInfo['itemurls'][$itemid];
	}

	/**
	 * not used
	 */
	function doUrlError($msg='') {
		doError($msg);
		return;
	}

	/**
	 *
	 */
	function event_PreAddItem(&$data)
	{
		$this->tmp_title = $data['title'];
	}

	/**
	 *
	 */
	function event_PostAddItem(&$data)
	{
		$this->addToMapping($this->buildFriendlyUrl($this->tmp_title, intval($data['itemid'])), intval($data['itemid']));
	}

	/**
	 *
	 */
	function event_PreUpdateItem(&$data)
	{
		$this->addToMapping($this->buildFriendlyUrl(intval($data['title']), intval($data['itemid'])), intval($data['itemid']));
	}

	/**
	 * Sends an error 301 message to the browser and redirect old style URL's to the new format
	 * Old links won't break and it is Google friendly
	 */
	function event_PreSendContentType()
	{
		global $CONF;

		// avoid redirection inside the admin area
		if($CONF['UsingAdminArea'] == 1)
		{
			return;
		}

		$this->checkOldRequests();

		if(!in_array($this->urlparts[0], $this->oldurls))
		{
			return;
		}
		else
		{
			$this->redirectOldRequests();
		}
	}

	/**
	 * Check for Nucleus Regular URL's requests - http://www.site.com/index.php?blogid=1
	 * Sets appropriate urlparts if they're found
	 */
	function checkOldRequests()
	{
		global $CONF;
		if(intRequestVar('itemid'))
		{
			$this->urlparts = array($CONF['ItemKey'], intRequestVar('itemid'));
		}
		// @todo: fix this
		elseif(requestVar('archive'))
		{
			$blogpart = array();
			if(requestVar('blogid'))
			{
				$blogpart = array($CONF['BlogKey'], requestVar('blogid'));
			}
			$archivepart = array($CONF['ArchiveKey'], requestVar('archive'));
			$this->urlparts = array_merge($blogpart, $archivepart);
		}
		elseif(requestVar('archivelist'))
		{
			$this->urlparts = array($CONF['ArchivesKey'], requestVar('archivelist'));
		}
		elseif(requestVar('memberid'))
		{
			$this->urlparts = array($CONF['MemberKey'], requestVar('memberid'));
		}
		elseif(requestVar('blogid'))
		{
			// show regular index page
			global $startpos;
			$this->urlparts = array($CONF['BlogKey'], requestVar('blogid'));
		}
	}

	/**
	 * Old url is requested. Redirection begins...
	 */
	function redirectOldRequests()
	{
		global $CONF;
		$location = '';

		foreach($this->urlparts as $key => $part)
		{
			switch($this->urlparts[$key])
			{
				// item/1 (itemid)
				case $CONF['ItemKey']:
					if(isset($this->urlparts[$key+1]))
					{
						$location = createItemLink(intval($this->urlparts[$key+1]));
					}
					break;
				// archives/1 (blogid)
				case $CONF['ArchivesKey']:
					if(isset($this->urlparts[$key+1]))
					{
						$location = createArchiveListLink(intval($this->urlparts[$key+1]));
					}
					break;
				// two possibilities: archive/yyyy-mm or archive/1/yyyy-mm (with blogid)
				case $CONF['ArchiveKey']:
					$i = 1;
					if((isset($this->urlparts[$key+1])) && (!strstr($this->urlparts[$key+1], '-')))
					{
						$blog_id = intval($this->urlparts[$key+1]);
						$i++;
					}
					if(isset($this->urlparts[$key+$i]))
					{
						$location = createArchiveLink(intval($blog_id), $this->urlparts[$key+$i]);
					}
					break;
				// blog/1 or blogid/1
				case $CONF['BlogKey']:
				case 'blogid':
					if(isset($this->urlparts[$key+1]))
					{
						$location = createBlogidLink(intval($this->urlparts[$key+1]));
					}
					break;
				// category/1 or catid/1 (catid)
				case $CONF['CategoryKey']:
				case 'catid':
					if(isset($this->urlparts[$key+1]))
					{
						$location = createCategoryLink(intval($this->urlparts[$key+1]));
					}
					break;
				// member/1 (memberid)
				case $CONF['MemberKey']:
					if(isset($this->urlparts[$key+1]))
					{
						$location = createMemberLink(intval($this->urlparts[$key+1]));
					}
					break;
			}
		}

		if(empty($location))
		{
			// bad request
			header("HTTP/1.1 404 Not Found");
		}
		else
		{
			// send a 301 error - http://www.checkupdown.com/status/E301.html (thanks, roel! :-D)
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: $location");
		}
		exit();
	}
}
?>