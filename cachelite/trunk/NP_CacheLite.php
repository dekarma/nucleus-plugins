<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * see http://nucleuscms.org/license.txt for the full license
 *
 * NP_CacheLite - A Nucleus CMS Plugin (http://plugins.nucleuscms.org/)
 * Copyright (C) 2006 Rodrigo Moraes & The Nucleus Group
 */

set_include_path(get_include_path() . PATH_SEPARATOR . $DIR_PLUGINS . 'pear');

class NP_CacheLite extends NucleusPlugin
{
	var $cache_options;

	var $skin_cache_id;

	var $skin_cache_group;

	var $page_cache_id;

	var $page_cache_group;

	var $blogid;

	var $remove_cache;

	function getName()
	{
		return 'CacheLite';
	}

	function getAuthor()
	{
		return 'Rodrigo Moraes';
	}

	function getURL()
	{
		return 'http://tipos.com.br';
	}

	function getVersion()
	{
		return '0.2';
	}

	function getDescription()
	{
		return 'Implements PEAR::Cache_Lite to cache Nucleus\' skins and pages, making your site much faster to load.';
	}

	function supportsFeature($feature)
	{
		switch($feature)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getEventList()
	{
		return array(
			// skins
			'PreSkinParse',
			'PostSkinParse',
			// items
			'PreAddItem',
			'PreUpdateItem',
			'PreDeleteItem',
			// comments
			'PostAddComment',
			'PrepareCommentForEdit',
			'PreUpdateComment',
			'PreDeleteComment',

			// categories
			'PreAddCategory',
			'PreDeleteCategory',
			// blog
			'PostDeleteBlog',
			// plugin
			'PostPluginOptionsUpdate'
		);
	}

	function init()
	{
		global $DIR_MEDIA;

		$this->cache_options['cacheDir'] = $DIR_MEDIA . '/../cache/';
		$this->cache_options['lifeTime'] = 60 * 60 * 24 * 30;
		$this->cache_options['fileNameProtection'] = false;

		//$this->cleanCacheAll();
	}

	/**
	 * This event replaces $data['contents'] by the cached page, or if there is no cache
	 * it generates a page and sometimes a skin cache to be used later
	 * @param array $data Nucleus skin data
	 */
	function event_PreSkinParse(&$data)
	{
		//return;
		global $DIR_PLUGINS;

		if($data['type'] == 'error' || isset($_REQUEST['results'])) return;

		// Feeds are not cached, should review this (index page and feed share cache file name)
		if($currentSkinName == 'feeds/rss20' ) return;
		if($currentSkinName == 'feeds/atom' ) return;
		if($currentSkinName == 'xml/rsd' ) return;
		// seach results are not cached
		if($data['type'] == 'search' ) return;

		$this->setCacheId($data['type']);

		// includes and starts the cache package
		require_once $DIR_PLUGINS . 'cachelite/Nucleus_Cache_Lite_Output.php';
		$cache_output = new Nucleus_Cache_Lite_Output($this->cache_options);

		// checks if the page is cached -- if not, cache it
		if (!$cached_page = $cache_output->check($this->page_cache_id, $this->page_cache_group))
		{
			require_once $DIR_PLUGINS . 'cachelite/CacheParser.php';
			$handler = new ACTIONS($data['type']);

			// checks if the skin is cached -- if not, cache it
			if (!$cached_skin = $cache_output->startBuffer($data['type'], 'skin' . $data['skin']->id))
			{
				$handler->parse_set('IncludeMode', $data['skin']->includeMode);
				$handler->parse_set('IncludePrefix', $data['skin']->includePrefix);
				$actions = array('parsedinclude', 'include', 'phpinclude', 'nocache', 'skinfile');
				$parser = new CacheParser($actions, $handler);
				$parser->parser_type = 'skin_cache';
				$handler->parser =& $parser;
				$parser->parse($data['contents']);
				$cached_skin = $cache_output->getBuffer();
				$data['contents'] = '';
			}

			// start caching the page
			$cache_output->startBuffer($this->page_cache_id, $this->page_cache_group);
			$actions = SKIN::getAllowedActionsForType($data['type']);
			$actions[] = 'nocache';
			$parser = new CacheParser($actions, $handler);
			$parser->parser_type = 'page_cache';
			$handler->parser =& $parser;
			$parser->parse($cached_skin);
			$cached_page = $cache_output->getBuffer();
		}

		// the cached page is set to be parsed and displayed
		$data['contents'] = $cached_page;
	}

	/**
	 * Sets current blog id
	 * @param int $id
	 */
	function setBlogId($id = '', $id_type = 'blog')
	{
		global $manager, $CONF;

		$this->blog_id = '';

		// find a blog id given an item id
		if($id_type == 'item')
		{
			$item =& $manager->getItem($id, 0, 0);
			if($item)
			{
				$this->blogid = $item['blogid'];
			}
		}

		// sets the given blog id
		elseif(!empty($id))
		{
			$this->blogid = intval($id);
		}

		if(empty($this->blog_id))
		{
			global $blog;
			if($blog)
			{
				$b =& $blog;
			}
			else
			{
				$b =& $manager->getBlog($CONF['DefaultBlog']);
			}
			$this->blog_id = intval($b->getID());
		}
	}

	/**
	 * Sets PEAR::Cache_Lite options depending on the skin type
	 * @param string $skin_type The skin type
	 * @param string $skin_id The skin ID
	 */
	function setCacheId($skin_type)
	{
		global $catid, $member;

		// set cache group
		$this->setBlogId();

		if($catid)
		{
                        $this->page_cache_group = 'cat' . $catid;
                        // Special cases
                        switch($skin_type)
                        {
				case 'index':
					$page = '1';
					if(isset($_REQUEST['page']))
					{
						$page = $_REQUEST['page'];
					}
					elseif(isset($_REQUEST['pagina']))
					{
						$page = $_REQUEST['pagina'];
					}
					$this->page_cache_id = $page;
					break;
				case 'item':
					// ID: itemX -> X = itemid
					global $itemid;
					$this->page_cache_id = 'item' . $itemid;
					break;
				case 'archivelist':
					$this->page_cache_id = 'archivelist';
					break;
				case 'archive':
					global $archive;
					// ID: archiveX -> X = date
					$this->page_cache_id = 'archive' . $archive;
					break;
				case 'member':
					// ID: memberX -> X = memberid
					global $memberid;
					$this->page_cache_id = $memberid;
					break;
				default:
					$this->page_cache_options = null;
			}

		}
		else
		{
			$this->page_cache_group = 'blog' . $this->blog_id;
			switch($skin_type)
			{
				case 'index':
					$this->page_cache_group = 'index' . $this->blog_id;
					$page = '1';
					if(isset($_REQUEST['page']))
					{
						$page = $_REQUEST['page'];
					}
					elseif(isset($_REQUEST['pagina']))
					{
						$page = $_REQUEST['pagina'];
					}
					$this->page_cache_id = $page;
					break;
				case 'item':
					// ID: itemX -> X = itemid
					global $itemid;
					$this->page_cache_id = 'item' . $itemid;
					break;
				case 'archivelist':
					$this->page_cache_id = 'archivelist';
					break;
				case 'archive':
					global $archive;
					// ID: archiveX -> X = date
					$this->page_cache_id = 'archive' . $archive;
					break;
				case 'member':
					// ID: memberX -> X = memberid
					global $memberid;
					$this->page_cache_group = 'member';
					$this->page_cache_id = $memberid;
					break;
				default:
					$this->page_cache_options = null;
			}
		}
		/*
		// not necessary
		if ($member->isLoggedIn())
		{
			$this->page_cache_id .= '_member' . $member->getID();
		}
		*/
	}

	/**************************************************************
	 Item events
	**************************************************************/
	/**
	 *
	 */
	function event_PreAddItem(&$data)
	{
		$this->setItemData($data);
		$this->cleanItemCache();
	}

	/**
	 *
	 */
	function event_PreUpdateItem(&$data)
	{
		global $manager;
		$item = $manager->getItem($data['itemid'], 0, 0);
		// no valid item
		if(!$item)
		{
			return;
		}

		$data['authorid'] = $item['authorid'];
		$data['timestamp'] = $item['timestamp'];

		$this->setItemData($data);
		$this->cleanItemCache();
	}

	/**
	 *
	 */
	function event_PreDeleteItem(&$data)
	{
		global $manager;
		$item = $manager->getItem($data['itemid'], 0, 0);
		// no valid item
		if(!$item)
		{
			return;
		}

		$data['blog'] = $manager->getBlog($item['blogid']);
		$data['draft'] = $item['draft'];
		$data['catid'] = $item['catid'];
		$data['authorid'] = $item['authorid'];
		$data['timestamp'] = $item['timestamp'];

		$this->setItemData($data);
		$this->cleanItemCache();
	}

	/**
	 *
	 */
	function setItemData(&$data)
	{
		// if it is a draft of future post, no cache is cleaned! so we set blogid to 0
		$now = $data['blog']->getCorrectTime();
		if($data['draft'] == 1 || $data['timestamp'] > $now)
		{
			$this->blogid = 0;
			return;
		}

		// the blog id is the cache group id
		$this->blogid = $data['blog']->getID();

		$timestamp = strtotime($data['timestamp']);
		$this->remove_cache = array(
							'itemid'			=> $data['itemid'],
							'catid' 			=> $data['catid'],
							// archive format: 2006-2-1 (no leading zeros for months or days)
							'archive_day' 		=> date('Y-n-j', $timestamp),
							// archive format: 2006-2 (no leading zeros for months or days)
							'archive_month' 	=> date('Y-n', $timestamp),
							// add member in case items are listed in the member page?...
							'authorid' 			=> $data['authorid']
							);
	}

	/**
	 * Cleans cache files related to an item:
	 * item: id 'itemX' - group 'blogX'
	 * blog: id 'index' - group 'blogX'
	 * category: id 'catX' - group 'blogX'
	 * monthly archive: id 'archiveX' - group 'blogX'
	 * daily archive: id 'archiveX' - group 'blogX'
	 * archivelist: id 'archivelist' - group 'blogX'
	 * author's page: id 'X' - group 'member'
	 * site main page: id 'index' - group 'blog[DefaultBlogID]'
	 */
	function cleanItemCache()
	{
		// cache cleaning is not necessary for draft or future item
		if($this->blogid == 0)
		{
			return;
		}

		global $CONF, $archive;
		$group = 'blog' . $this->blogid;

		// remove main index
		$this->cleanCache('index', 'blog' . $CONF['DefaultBlog']);

		// remove member page cache
		$this->cleanCache($this->remove_cache['authorid'], 'member');

		$this->cleanCacheGroup('index'.$this->blogid);

		// remove pages relative to the item
		$removed = array(
						'item' . $this->remove_cache['itemid'],
						'cat' . $this->remove_cache['catid'],
						'archive' . $this->remove_cache['archive_day'],
						'archive' . $this->remove_cache['archive_month'],
						'archivelist'
						);

		$this->cleanCacheByArray($removed, $group);
	}

	/**************************************************************
	 Comment events
	**************************************************************/
	/**
	 *
	 */
	function event_PostAddComment($data)
	{
		$this->setCommentData($data);
		$this->cleanCommentCache();
	}

	/**
	 *
	 */
	function event_PrepareCommentForEdit($data)
	{
		$this->setCommentData($data);
	}

	/**
	 *
	 */
	function event_PreUpdateComment($data)
	{
		$this->cleanCommentCache();
	}

	/**
	 *
	 */
	function event_PreDeleteComment($data)
	{
		$comment = COMMENT::getComment($data['commentid']);
		$itemid = quickQuery('SELECT citem as result FROM ' . sql_table('comment') . ' WHERE cnumber=' . $data['commentid']);

		$data['comment']['memberid'] = $comment['memberid'];
		$data['comment']['timestamp'] = $comment['timestamp'];
		$data['comment']['itemid'] = $itemid;
		$this->setCommentData($data);
		$this->cleanCommentCache();
	}

	/**
	 *
	 */
	function setCommentData($data)
	{
		global $manager;
		$item = $manager->getItem($data['comment']['itemid'], 0, 0);
		if(!$item)
		{
			return;
		}
		// the blog id is the cache group id
		$blog = $manager->getBlog($item['blogid']);
		$this->blogid = $blog->getID();

		//$timestamp = strtotime($data['comment']['timestamp']);
		$this->remove_cache = array(
							'itemid'			=> $data['comment']['itemid'],
							//not implemented yet
							//'catid' 			=> $data['catid'],
							// archive format: 2006-2-1 (no leading zeros for months or days)
							'archive_day' 		=> date('Y-n-j', $data['comment']['timestamp']),
							// archive format: 2006-2 (no leading zeros for months or days)
							'archive_month' 	=> date('Y-n', $data['comment']['timestamp']),
							// add member in case items are listed in the member page?...
							'authorid' 			=> $data['comment']['memberid']
							);
	}

	/**
	 * Cleans cache files related to a comment:
	 * item: id 'itemX' - group 'blogX'
	 * blog: id 'index' - group 'blogX'
	 * monthly archive: id 'archiveX' - group 'blogX'
	 * daily archive: id 'archiveX' - group 'blogX'
	 * author's page: id 'X' - group 'member'
	 * site main page: id 'index' - group 'blog[DefaultBlogID]'
	 */
	function cleanCommentCache()
	{
		// cache cleaning is not necessary for draft or future item
		if($this->blogid == 0)
		{
			return;
		}

		global $CONF, $archive;
		$group = 'blog' . $this->blogid;

		// remove main index
		//$this->cleanCache('index', 'blog' . $CONF['DefaultBlog']);

		// remove member page cache
		$this->cleanCache($this->remove_cache['authorid'], 'member');

		$this->cleanCacheGroup('index' . $CONF['DefaultBlog']);
		$this->cleanCacheGroup('index'.$this->blogid);

		// remove pages relative to the item
		$removed = array(
						'item' . $this->remove_cache['itemid'],
						//not implemented yet
						//'cat' . $this->remove_cache['catid'],
						'archive' . $this->remove_cache['archive_day'],
						'archive' . $this->remove_cache['archive_month'],
						);

		$this->cleanCacheByArray($removed, $group);
	}

	/**************************************************************
	 Clean cache functions
	**************************************************************/
	/**
	 * Cleans a single cache file
	 * @param string $cacheid Cache ID
	 * @param string $group Cache group
	 */
	function cleanCache($cacheid, $group)
	{
		require_once 'Cache/Lite.php';
		$cache = new Cache_Lite($this->cache_options);
		$cache->remove($cacheid, $group);
	}

	/**
	 * Cleans a collection of cache files from the same group
	 * @param array $array Cache ID's to be cleaned
	 * @param string $group Cache group
	 */
	function cleanCacheByArray($array, $group)
	{
		foreach($array as $cacheid)
		{
			$this->cleanCache($cacheid, $group);
		}
	}

	/**
	 * Cleans a cache group
	 * @param string $group Cache group
	 */
	function cleanCacheGroup($group)
	{
		require_once 'Cache/Lite.php';
		$cache = new Cache_Lite($this->cache_options);
		$cache->clean($group);
	}

	/**
	 * Cleans all cache files
	 */
	function cleanCacheAll()
	{
		require_once 'Cache/Lite.php';
		$cache = new Cache_Lite($this->cache_options);
		$cache->clean();
	}
}

?>
