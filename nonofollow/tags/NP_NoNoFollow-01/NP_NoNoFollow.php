<?php

       /* ==========================================================================================
	* NoNoFollow for Nucleus CMS
        * Copyright 2005, Niels Leenheer
	* ==========================================================================================
	* This program is free software and open source software; you can redistribute
	* it and/or modify it under the terms of the GNU General Public License as
	* published by the Free Software Foundation; either version 2 of the License,
	* or (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful, but WITHOUT
	* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
	* FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
	* more details.
	*
	* You should have received a copy of the GNU General Public License along
	* with this program; if not, write to the Free Software Foundation, Inc.,
	* 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
	* http://www.gnu.org/licenses/gpl.html
	* ==========================================================================================
	*/

class NP_NoNoFollow extends NucleusPlugin {
	function getName()        { return 'NoNoFollow'; }
	function getAuthor()  	  { return 'Niels Leenheer'; }
	function getURL()  	  { return 'http://www.rakaz.nl'; }
	function getVersion() 	  { return '0.1'; }
	function getDescription() { return 'Removes the Nofollow value from the rel attribute of links inside a comment';	}
	
	function supportsFeature($what) {
		switch($what) {
		    case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	
	function getEventList() {
		return array('PreComment');
	}

	function event_PreComment(&$data) {
		$data['comment']['body'] = preg_replace("/(\<a)([^\>]*)?(\s*rel=[\"|']nofollow[\"|'])([^\>]*)?(\>)/imsU", '\\1\\2\\4\\5', $data['comment']['body']);
	}
}

?>