<?php

class NP_Gravatar extends NucleusPlugin {

   /* ==========================================================================================
	* Nucleus Gravatar Plugin 0.8
	*
	* Copyright 2004-2007 by Niels Leenheer
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
	*
    * 0.8   - Added gravatar class to the image
    * 0.7   - Added support for Nucleus 3.3
	* 0.6   - Added the ability to specify a different size as a parameter of 
	*         comment template variable
	* 0.5   - Added option to change the gravatar URL from the plugin options
	* 0.4   - Changed URL from www.gravatar.com to wide.gravatar.com
	* 0.3   - Fixed bug: Output is now XHTML compliant
	* 0.2   - Fixed bug: Border was not set correctly
	* 0.1   - Initial concept
	*/


	function getName() {
		return 'Gravatar';
	}

	function getAuthor()  {
		return 'Niels Leenheer';
	}

	function getURL() {
		return 'http://www.rakaz.nl/extra/plugins';
	}

	function getVersion() {
		return '0.7';
	}

	function getDescription() {
		return 'Show gravatars next to each comment. For more information about '.
			   'gravatars see www.gravatar.com.';
	}
	
	function supportsFeature($feature) {
    	switch($feature) {
	        case 'SqlTablePrefix':
	        	return 1;
	        default:
	    		return 0;
		}
	}


	var $gravatarDefault;
	var $gravatarSize;
	var $gravatarRating;
	var $gravatarBorder;
	var $gravatarURL;

	function init() {
		$this->gravatarDefault = $this->getOption('gravatarDefault');
		$this->gravatarSize = $this->getOption('gravatarSize');
		$this->gravatarRating = $this->getOption('gravatarRating');
		$this->gravatarBorder = $this->getOption('gravatarBorder');
		$this->gravatarURL = $this->getOption('gravatarURL');
	}	
	
	function install() {
    	$this->createOption('gravatarDefault','Default image (URL)','text','gravatar.gif');
    	$this->createOption('gravatarSize','Gravatar size','select','32','16 x 16|16|20 x 20|20|24 x 24|24|32 x 32|32|40 x 40|40|64 x 64|64|80 x 80|80');
    	$this->createOption('gravatarRating','Gravatar rating','select','R','G|G|PG|PG|R|R|X|X');
    	$this->createOption('gravatarBorder','Gravatar border','text','#000000');
    	$this->createOption('gravatarURL','Gravatar server','select','wide.gravatar.com','Default|wide.gravatar.com|Alternative|www.gravatar.com');
	}

	function doTemplateCommentsVar(&$item, &$comment, $size = null) {

		$output = '';
		$email  = '';
		
		if ($size == null)
			$size = $this->gravatarSize;
		
		if ($comment['memberid'] > 0)
		{
			$member = new MEMBER();
			$member->readFromID($comment['memberid']);
			
			if ($member->email != '')
				$email = $member->email;
		}
		else
		{
			if (getNucleusVersion() >= 330)
				$email = $comment['email'];
			else
				if (!(strpos($comment['userlinkraw'], 'mailto:') === false))
					$email = $comment['userid'];
		}
		
		if ($email == '')
		{
			if ($this->gravatarDefault != '')
			{
				$output .= "<img src='".$this->gravatarDefault;
				$output .= "' width='".$size."' height='".$size."' alt='".$comment['user']."' class='gravatar' />";
			}
		}
		else
		{
			$output .= "<img src='http://".$this->gravatarURL."/avatar.php";
			$output .= "?gravatar_id=".md5($email);
			$output .= "&amp;rating=".$this->gravatarRating;
			$output .= "&amp;size=".$size;
			
			if ($this->gravatarDefault != '')
				$output .= "&amp;default=".urlencode($this->gravatarDefault);
				
			if ($this->gravatarBorder != '')
				$output .= "&amp;border=".$this->gravatarBorder;
	
			$output .= "' width='".$size."' height='".$size."' alt='".htmlentities($comment['user'])."' class='gravatar' />";
		}
		
		echo $output;
	}
  }

?>