<?php
/********************************************************************************
NP_Pager: a standartization attempt for paged results
................................................................................
This plugin provides an extremely flexible API for plugins that need to display
paginated results like lists of items or comments, inside the admin area or not.

Copyright (C) 2005 The Nucleus Group
http://nucleuscms.org

Version 0.2, 04 December 2005

This plugin implements the PEAR::Pager library by the Pear team:
http://pear.php.net/package/Pager

********************************************************************************
LICENSE
................................................................................

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

********************************************************************************/

set_include_path(get_include_path() . PATH_SEPARATOR . $DIR_PLUGINS . 'pear');

class NP_Pager extends NucleusPlugin {
	var $pager_options;

	function getName() { return 'Pager'; }
	function getAuthor()  { return 'Rodrigo Moraes'; }
	function getURL() {	return 'http://tipos.com.br'; }
	function getVersion() {	return '0.2'; }
	function getMinNucleusVersion() { return '322'; }
	function getDescription() { return 'Provides an extremely flexible API for plugins that need to display paginated results like lists of items or comments, inside the admin area or not. It implements the PEAR::Pager library - see http://pear.php.net/package/Pager.'; }
	function getEventList() { return array(); }
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	/**
	 * Initialization: sets default Pager options
	 */
	function init() {
		$this->_setOptions();
	}

	/**
	 * Sets default Pager options
	 * Options can be overriden using the method setOptions($options)
	 *
	 * @see setOptions()
	 * @access private
	 */
	function _setOptions() {
		$this->pager_options = array(
			//'itemData' => $myData,
			'totalItems' => 150,
			'perPage' => 1,
			'delta' => 3,
			'append' => true,
			'separator' => '',
			'clearIfVoid' => false,
			'urlVar' => 'page',
			'altPrev' => 'previous page',
			'altNext' => 'next page',
			'altPage' => 'page',
			'altFirst' => 'first page',
			'altLast' => 'last page',
			'firstLinkTitle' => 'first page',
			'lastLinkTitle' => 'last page',
			'firstPagePre' => '',
			'firstPagePost' => '',
			'lastPagePre' => '',
			'lastPagePost' => '',
			'prevImg' => '&laquo; previous page',
			'nextImg' => 'next page &raquo;',
			'prevLinkTitle' => 'previous page',
			'nextLinkTitle' => 'next page',
			'spacesBeforeSeparator' => 0,
			'spacesAfterSeparator' => 0,
			'useSessions' => false,
			'closeSession' => true,
			'mode'  => 'Sliding',
			//'mode'  => 'Jumping',
			'sessionVar' => 'q',
			'showPagination' => true, // new option
			'curPageSpanPre' => '<span class="current">',
			'curPageSpanPost' => '</span>'
		);
	}

	/**
	 * Overrides default Pager options
	 *
	 * @param array $options options that will be changed
	 * @see _setOptions()
	 * @access public
	 */
	function setOptions($options) {
		$this->pager_options = array_merge($this->pager_options, $options);
	}

	/**
	 * Helper method - Rewrites the query into a "SELECT COUNT(*)" query.
	 * Provided with the PEAR::Pager package
	 *
	 * @param string $sql query
	 * @return string rewritten query OR false if the query can't be rewritten
	 * @access private
	 */
	function _rewriteCountQuery($sql) {
		if (preg_match('/^\s*SELECT\s+\bDISTINCT\b/is', $sql) || preg_match('/\s+GROUP\s+BY\s+/is', $sql)) {
			return false;
		}
		$queryCount = preg_replace('/(?:.*)\bFROM\b\s+/Uims', 'SELECT COUNT(*) FROM ', $sql, 1);
		list($queryCount, ) = preg_split('/\s+ORDER\s+BY\s+/is', $queryCount);
		list($queryCount, ) = preg_split('/\bLIMIT\b/is', $queryCount);
		return trim($queryCount);
	}

	/**
	 * Helper method - Counts records given a SELECT query (trying to transform it)
	 * Based on the wrapper examples provided with the PEAR::Pager package
	 *
	 * @param string $sql query
	 * @access private
	 */
	function _countRecords($sql) {
		//  be smart and try to guess the total number of records
		if ($countQuery = $this->_rewriteCountQuery($query)) {
			$res = sql_query($countQuery);
			if($row = mysql_fetch_row($res)) {
				$totalItems = $row[0];
			} else {
				return null;
			}
		} else {
			$res = sql_query($query);
			if (!$res) {
				return null;
			}
			$totalItems = (int)mysql_num_rows($res);
			//mysql_free_result($res);
		}
		$this->pager_options['totalItems'] = $totalItems;
	}

	/**
	 * Returns the paged data, links and info
	 * Based on the wrapper examples provided with the PEAR::Pager package
	 *
	 * @param string $sql db query
	 * @param string $fetch_method MySQL fetch method - mysql_fetch_row or mysql_fetch_array
	 * @param boolean $disabled Disable pagination (get all results)
	 * @return array with links and paged data
	 * @access public
	 */
	function getPagedResults($sql, $fetch_method = 'mysql_fetch_array', $disabled = false) {
		if (empty($this->pager_options['totalItems'])) {
			$this->_countRecords($sql);
		}

		require_once 'Pager/Pager.php';
		$pager = Pager::factory($this->pager_options);

		$page = array();
		$page['totalItems'] = $this->pager_options['totalItems'];
		$page['links'] = $pager->links;
		$page['page_numbers'] = array(
			'current' => $pager->getCurrentPageID(),
			'total'   => $pager->numPages()
		);

		list($page['from'], $page['to']) = $pager->getOffsetByPageId();

		$res = ($disabled)
			? sql_query($sql . " LIMIT 0, $totalItems")
			: sql_query($sql . " LIMIT " . ($page['from']-1) . ", " . $this->pager_options['perPage']);

		if (!$res) {
			return null;
		}

		$page['data'] = array();
		if ($res) {
			if(	$fetch_method != 'mysql_fetch_row' &&
				$fetch_method != 'mysql_fetch_array' &&
				$fetch_method != 'mysql_fetch_object' &&
				$fetch_method != 'mysql_fetch_assoc') {
					$fetch_method = 'mysql_fetch_array';
			}
			while ($row = $fetch_method($res)) {
				$page['data'][] = $row;
			}
			//mysql_free_result($res);
		}

		if ($disabled) {
			$page['links'] = '';
			$page['page_numbers'] = array(
				'current' => 1,
				'total'   => 1
			);
		}

		return $page;
	}

	/**
	 * Returns a HTML select field to change the amount of results per page
	 *
	 * @param array $options select values for the amount per page
	 * @param string $submit_button text for the submit button
	 * @return string HTML select field
	 * @access public
	 */
	function getSelectPerPage($options = array(), $submit_button = 'ok') {
		if(count($options) == 0) {
			$options = array(5, 10, 15, 25, 50, 75, 100);
		}

		$per_page = $this->pager_options['perPage'];

		if(!in_array($per_page, $options)) {
			$options[] = $per_page;
		}

		asort($options);

		$url = $_SERVER['PHP_SELF'];

		$select = '<form action="'.$url.'" method="GET">';
		$select .= '<select name="'.$this->pager_options['sessionVar'].'">';
		foreach($options as $option) {
			$selected = $option == $per_page ? ' selected' : '';
			$select .= '<option value="'.$option.'"'.$selected.'>'.$option.'</option>';
		}
		$select .= '</select>&nbsp;';
		$select .= '<input type="submit" value="'.$submit_button.'" />';
		$select .= '</form>';
		return $select;
	}
}
?>
