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

class CacheParser extends PARSER
{
	/**
	 * @param int $nocache defines if the skin piece will be parsed and cached
	 * 0 = parsed and cached
	 * 1 = parsed on every page request (used for non-cached pieces)
	 */
	var $nocache;

	/**
	 * @param string $parser_type defines the parser type (skin_cache or page_cache)
	 */
	var $parser_type;

	/**
	 * Parses the given contents and outputs it
	 * @param string $contents the skin contents
	 * @return string $output the pre-parsed skin
	 */
	function parse(&$contents)
	{
		$this->nocache = 0;
		$pieces = preg_split('/'.$this->delim.'/',$contents);
		$maxidx = sizeof($pieces);

		for ($idx = 0;$idx<$maxidx;$idx++) {
			echo $pieces[$idx];
			$idx++;
			$this->doAction($pieces[$idx]);
		}
	}

	function setActions($actions) {
		$this->actions = $actions;
	}

	/**
	 * Parses <%nocache()%> skinvars, turning output buffering on or off
	 * @param string $type skinvar parameter - 'start'/'begin' or 'stop'/'end'
	 */
	function parse_nocache($type)
	{
		switch($type)
		{
			case 'start':
				$this->nocache = 1;
				ob_start();
				if($this->parser_type == 'skin_cache')
				{
					echo '<%nocache('.$type.')%>';
				}
				break;
			default:
				$this->nocache = 0;
				if($this->parser_type == 'skin_cache')
				{
					echo '<%nocache('.$type.')%>';
				}
				ob_end_flush();
		}
	}

	/**
	  * handle an action
	  */
	function doAction($action) {
		global $manager;

		if (!$action) return;

		// split into action name + arguments
		if (strstr($action,'(')) {
			$paramStartPos = strpos($action, '(');
			$params = substr($action, $paramStartPos + 1, strlen($action) - $paramStartPos - 2);
			$action = substr($action, 0, $paramStartPos);
			$params = explode ($this->pdelim, $params);

			// trim parameters
			$params = array_map('trim',$params);
		} else {
			// no parameters
			$params = array();
		}

		$actionlc = strtolower($action);

		if($this->parser_type != 'skin_cache')
		{
			// skip execution of skinvars while inside an if condition which hides this part of the page
			if (!$this->handler->if_currentlevel && ($actionlc != 'else') && ($actionlc != 'elseif') && ($actionlc != 'endif') && ($actionlc != 'ifnot') && ($actionlc != 'elseifnot') && (substr($actionlc,0,2) != 'if'))
			{
				return;
			}
		}

		if( (in_array($actionlc, $this->actions) || $this->norestrictions ) && ($this->nocache == 0 || ($actionlc == 'nocache' && $params[0] == 'stop')) )
		{
			if($actionlc == 'nocache')
			{
				if(empty($params))
				{
					return;
				}
				$this->parse_nocache($params[0]);
			}
			else
			{
				call_user_func_array(array(&$this->handler,'parse_' . $actionlc), $params);
			}
		} else {
			// redirect to plugin action if possible
			if (in_array('plugin', $this->actions) && $manager->pluginInstalled('NP_'.$action))
			{
				$this->doAction('plugin('.$action.$this->pdelim.implode($this->pdelim,$params).')');
			}
			else
			{
				echo '<%' , $action , '(', implode($this->pdelim, $params), ')%>';
			}
		}

	}

}
?>
