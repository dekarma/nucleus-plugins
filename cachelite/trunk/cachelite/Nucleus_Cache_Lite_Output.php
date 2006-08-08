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

require_once 'Cache/Lite.php';

/**
 * This class extends PEAR::Cache_Lite_Output to add some methods used by Nucleus' CacheLite plugin
 */
class Nucleus_Cache_Lite_Output extends Cache_Lite
{
	/**
	* Constructor - from PEAR::Cache_Lite_Output
	*
	* $options is an assoc. To have a look at availables options,
	* see the constructor of the Cache_Lite class in 'Cache_Lite.php'
	*
	* @param array $options options
	* @access public
	*/
	function Cache_Lite_Output($options)
	{
		$this->Cache_Lite($options);
	}

	/**
	 * Start the cache
	 *
	 * @param string $id cache id
	 * @param string $group name of the cache group
	 * @param boolean $doNotTestCacheValidity if set to true, the cache validity won't be tested
	 * @return mixed (boolean true if the cache is hit (false else) || $data if data is cached)
	 * @access public
	 */
	function startBuffer($id, $group = 'default', $doNotTestCacheValidity = false)
	{
		$data = $this->get($id, $group, $doNotTestCacheValidity);
		if ($data !== false) {
			return $data;
		} else {
			ob_start();
			ob_implicit_flush(false);
			return false;
		}
	}

	/**
	 * Stop the cache and returns the buffer
	 * @return string $data Buffered data
	 * @access public
	 */
	function getBuffer()
	{
		$data = ob_get_contents();
		ob_end_clean();
		$this->save($data, $this->_id, $this->_group);
		return $data;
	}

	/**
	 * Check and return the cache if it is saved
	 *
	 * @param string $id cache id
	 * @param string $group name of the cache group
	 * @param boolean $doNotTestCacheValidity if set to true, the cache validity won't be tested
	 * @return mixed (boolean true if the cache is hit (false else) || $data if data is cached)
	 */
	function check($id, $group = 'default', $doNotTestCacheValidity = false)
	{
		$data = $this->get($id, $group, $doNotTestCacheValidity);
		if ($data !== false) {
			return $data;
		} else {
			return false;
		}
	}
}

?>
