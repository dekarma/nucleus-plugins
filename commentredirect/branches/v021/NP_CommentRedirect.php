<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 **/

/**
 * Plugin class to redirect to the comment after it is posted.
 *
 * @license http://nucleuscms.org/license.txt GNU General Public License
 * @version $Revision$
 **/
class NP_CommentRedirect extends NucleusPlugin {

	/**
	 * Get the plugin name
	 * @return string
	 **/
	function getName() {
		return 'Comment Redirect';
	}

	/**
	 * Get the plugin version
	 * @return string
	 **/
	function getVersion() {
		return '0.21';
	}

	/**
	 * Get plugin author(s)
	 * @return string
	 **/
	function getAuthor() {
		return 'gRegor Morrill, modified by ketsugi';
	}

	/**
	 * Get the plugin URL
	 * @return string
	 **/
	function getURL() {
		return 'http://www.gregorlove.com';
	}

	/**
	 * Return the plugin description
	 * @return string
	 **/
	function getDescription() {
		return 'This plugin allows you to redirect after posting a comment, directly to the comment.';
	}

	/**
	 * Get the events this plugin describes to
	 * @return array
	 **/
	function getEventList() {
		return array('PostAddComment');
	}

	/**
	 * Get the minimum Nucleus version required for this plugin
	 * @return int
	 **/
	function getMinNucleusVersion() {
		return 200;
	}

	/**
	 * Find out which features are supported by this plugin
	 * @return int
	 **/
	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	/**
	 * Create plugin options
	 * 1) anchorprefix: anchor prefix text for comment links
	 **/
	function install() {
		$this->createOption('anchorprefix', 'Anchor prefix:', 'text', 'c');
	}

	/**
	 * This event hook is used to handle redirecting after a comment is posted
	 * @param $data array
	 **/
	function event_PostAddComment($data) {
		global $blog, $CONF, $HTTP_SERVER_VARS;

		$uri = (isset($_SERVER['REQUEST_URI']) ) ? $_SERVER['REQUEST_URI'] : $HTTP_SERVER_VARS['REQUEST_URI'];
		$url = rtrim($CONF['IndexURL'], '/') . $uri;
		$pos = strpos($url, $CONF['ActionURL']);

		// begin if: request uri is not the action URL, so redirect
		if ($pos === false) {
			$anchor = $this->getOption('anchorprefix') . $data['commentid'];
			$url .=  "#$anchor";

			redirect($url);
		} // end if

	}

}

?>