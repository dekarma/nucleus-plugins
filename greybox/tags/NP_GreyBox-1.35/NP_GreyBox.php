<?php
   /* Based on a Nucleus Plugin called NP_LightBox by Seventoes
	* NP_GreyBox is a simple plugin to add LightBox javascript functionality to
	* Nucleus blogs. NP_GreyBox will automatically create a thumbnail of the
	* image, and when clicked, will show the full size image in LightBox style.
	*
	* greybox.js is a script written by Amir Salihefendic
	* http://orangoo.com/labs/GreyBox/
    */

   /* ==========================================================================================
	* GreyBox for Nucleus
	*
	* Copyright 2005 by Frank Truscott
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
class NP_GreyBox extends NucleusPlugin {

	function getName() {return 'GreyBox';}
	function getAuthor()  {return 'Frank Truscott, based on work by Seventoes';}
	function getURL(){return 'http://www.iai.com/';}
	function getVersion() {return '1.35';}
	function getDescription() {
		return 'Simple plugin to enable GreyBox on Nucleus Blogs';
	}
	function getEventList() { return array('PreItem'); }

    function supportsFeature($feature) {		switch($feature) {			case 'SqlTablePrefix': return 1;			default: return 0;		}	}

	function install() {
		global $CONF, $DIR_MEDIA;
		$this->createOption('imageURL','URL to your image folder (Always have trailing /)', 'text', $CONF['MediaURL']);
        $this->createOption('imagePath','Absolute filesytem path to your image folder (Always have trailing /)', 'text', $DIR_MEDIA);
        $this->createOption('relPath','Relative filesytem path to your image folder (Always have trailing /)', 'text', '../../../media/');
        $this->createOption('whichPath', 'Which path should be used by thumbnail.php to find image files?','select', 'URL', 'URL|URL|Full|Full|Relative|Relative');
        $this->createOption('rootDir','URL to your greybox root directory (Always have trailing /)', 'text', $this->getAdminURL()."greybox/");
		$this->createOption('defaultCaption','Text to be displayed if no caption is provided (set to "TB_imageName" to display the image\'s filename)','text','GB_imageName');
		$this->createOption('thumbnails','Do you want to display thumbnails? If not, the caption will be displayed.','yesno','yes');
		$this->createOption('maxSize','What is the maximum size for thumbnails? The largest side of the image will be reduced down to this size in pixels.', 'text', '150');
		$this->createOption('repPopup', 'Replace the default nucleus <%popup()%> behavior with greybox?', 'yesno', 'no');
		$this->createOption('repNormal', 'Replace normal image links with greybox?', 'yesno', 'no');
	}

	function doSkinVar() {
        $params = func_get_args();
        $skinType = array_shift($params);
        $arrsize = count($params);

        if ($arrsize == 0 || trim($params[0]) == '') $gbdata = '';
        elseif ($arrsize == 1) $gbdata = $params[0];
        else $gbdata = implode('|',$params);

        if ($gbdata == '') {
            if ($this->getOption('rootDir')) $gb_root = $this->getOption('rootDir');
            else $gb_root = $this->getAdminURL()."greybox/";
            echo '<script type="text/javascript">'."\n";
            echo 'var GB_ROOT_DIR = "'.$gb_root.'"';
            echo '</script>'."\n";
            echo '<link rel="stylesheet" href="'.$gb_root.'gb_styles.css" type="text/css" />'."\n";
            echo '<script type="text/javascript" src="'.$gb_root.'AJS.js"></script>'."\n";
            echo '<script type="text/javascript" src="'.$gb_root.'AJS_fx.js"></script>'."\n";
            echo '<script type="text/javascript" src="'.$gb_root.'gb_scripts.js"></script>'."\n";
        }
        else {
            $args = array(&$this,$gbdata);
            echo $this->parse($args);
        }
    }

	function parse($matches) {
        $whichpath = $this->getOption('whichPath');
        switch ($whichpath) {
            case 'Full':
                $thumb_path = $this->getOption('imagePath');
            break;
            case 'Relative':
                $thumb_path = $this->getOption('relPath');
            break;
            default:
                $thumb_path = $this->getOption('imageURL');
            break;
        }
        $media_dir = $this->getOption('imagePath');
		$media_url = $this->getOption('imageURL');
        $media_reldir = $this->getOption('relPath');
/*
        if (!ini_get("allow_url_fopen")) {
            $thumb_path = $media_dir;
        }
        elseif (strpos(strtolower(php_uname('s')),'windows') !== false) {
            if (!function_exists('version_compare') || version_compare(PHP_VERSION, "4.3.0", "<")) {
                $thumb_path = $media_dir;
            }
            else $thumb_path = $media_url;
        }
        else $thumb_path = $media_url;
*/

		$sections = explode("|", $matches[1]);

		if (substr($sections[0],0,1) == "/") $sections[0] = substr($sections[0],1);
		if (substr($sections[0],-1) == "/") $sections[0] = substr($sections[0],0,strlen($sections[0]) -1);
		$imagename = $sections[0];
        $kind = $sections[3];
        $width = ($sections[4] ? $sections[4] : 600);
        $height = ($sections[5] ? $sections[5] : 400);
        if (intval($width) == 0) $width = 600;
        if (intval($height) == 0) $height = 400;

        if (strtolower(substr($kind,0,4)) != 'page') {
            if (is_dir($media_dir.$imagename)) {
                $dh  = opendir($media_dir.$imagename);
                while (false !== ($filename = readdir($dh))) {
                    if (!is_dir($media_dir.$imagename."/".$filename)) {
                        $images[] = "$imagename/$filename";
                    }
                }
                if ($sections[2] == '') $sections[2] = 'fred'.substr($imagename,-4);
                $kind = 'imageset';
            }
            else {
                $images = array($imagename);
                if (trim($kind) == '') $kind = 'image';
            }
        }
        else {
            $images = array($imagename);
        }
        $r = '';
        natcasesort($images);
        foreach ($images as $image) {
            $imgName = end(explode("/",$image));
            if ($this->getOption('defaultCaption') == "GB_imageName") {
                $caption = ($sections[1])? $sections[1] : $imgName;
            }
            else {
                if (strtolower(substr($kind,0,4)) != 'page') {
                    $caption = ($sections[1])? $sections[1] : $imgName;
                }
                else $caption = ($sections[1])? $sections[1] : $this->getOption('defaultCaption');
            }

            switch ($kind) {
                case 'image':
                    $reltext = "gb_image[]";
                    break;
                case 'imageset':
                    $reltext = "gb_imageset[".$sections[2]."]";
                    break;
                case 'page':
                    $reltext = "gb_page[".intval($width).",".intval($height)."]";
                    break;
                case 'pagefs':
                case 'page_fs':
                    $reltext = "gb_page_fs[]";
                    break;
                case 'pageset':
                    if ($sections[2] == '') $sections[2] = 'fred'.substr($imagename,-4);
                    $reltext = "gb_pageset[".$sections[2]."]";
                    break;
                default:
                    $reltext = "gb_image[]";
                    break;
            }
            if ((strpos(strtolower($image),strtolower($this->getOption('imageURL'))) !== false) || (strpos($image,"http://") !== false)) {
                $r .= '<a href="'.$image.'" rel="'.$reltext.'" title="'.$caption.'">';
                if ($this->getOption('thumbnails') == 'yes' && strtolower(substr($kind,0,4)) != 'page') {
                    $r .='<img src="'.$this->getAdminURL().'thumbnail.php?image='.$image.'&amp;size='.$this->getOption('maxSize').'" alt="'.$caption.'" border="0" />';
                } else {
                    $r .= $caption;
                }
                $r .= "</a>\n";
            }
            else {
                $r .= '<a href="'.$this->getOption('imageURL').$image.'" rel="'.$reltext.'" title="'.$caption.'">';
                if ($this->getOption('thumbnails') == 'yes' && strtolower(substr($kind,0,4)) != 'page') {
                    $r .= '<img src="'.$this->getAdminURL().'thumbnail.php?path='.$thumb_path.'&amp;image='.$image.'&amp;size='.$this->getOption('maxSize').'" alt="'.$caption.'" border="0" />';
                } else {
                    $r .= $caption;
                }
                $r .= "</a>\n";
            }
        }
		return $r;
	}

	function parse_popup($matches) {
		$media_dir = $this->getOption('imagePath');
		$sections = explode("|", $matches[1]);
		if (!is_file($media_dir.$sections[0])) {
			$sections[0] = $this->currentItem->authorid."/".$sections[0];
		}
		$r = "<%GreyBox(".$sections[0]."|".end($sections).")%>";
		return $r;
	}

	function parse_image($matches) {
		$matches[1] = str_replace("\"","",$matches[1]);
		if (strpos($matches[1],"media/") === 0) {
			$matches[1] = $this->str_replace_once("media/", "", $matches[1]);
		}
		if (strpos($text,"/media/") === 0) {
			$matches[1] = $this->str_replace_once("/media/", "", $matches[1]);
		}
		$matches[1] = trim($matches[1]);
		$r = "<%GreyBox(".$matches[1].")%>";
		return $r;
	}

	function str_replace_once($needle, $replace, $haystack) {
	   // Looks for the first occurence of $needle in $haystack
	   // and replaces it with $replace.
	   $pos = strpos($haystack, $needle);
	   if ($pos === false) {
	       // Nothing found
	       return $haystack;
	   }
	   return substr_replace($haystack, $replace, $pos, strlen($needle));
	}

	function event_PreItem($data) {
        $this->currentItem = &$data["item"];
		$parts=array('body','more');

		foreach ($parts as $part) {
			$match = array();
			$rep = '';
			//normal image link replacement
			if ($this->getOption('repNormal') == "yes") {
	        	$target = '/<a(?:\s+?)href="([^>"]+)([^>]+)>(<img(?:\s+?)src=[^>]+>)/';
				$this->currentItem->$part = preg_replace_callback($target, array(&$this, 'parse_image'), $this->currentItem->$part);
			}
			//nucleus popup() code replacement
			if ($this->getOption('repPopup') == "yes") {
				$this->currentItem->$part = preg_replace_callback("#<\%popup\((.*?)\)%\>#", array(&$this, 'parse_popup'), $this->currentItem->$part);
			}

			$this->currentItem->$part = str_replace(array("!~~","~~!"),array("<%GreyBox(",")%>"),$this->currentItem->$part);
            $this->currentItem->$part = preg_replace_callback("#<\%GreyBox\((.*?)\)%\>#", array(&$this, 'parse'), $this->currentItem->$part);
		} //foreach ($parts as $part)
	}
}
?>