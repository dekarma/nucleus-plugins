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
	function getVersion() {return '1.0';}
	function getDescription() {
		return 'Simple plugin to enable GreyBox on Nucleus Blogs';
	}
	function getEventList() { return array('PreItem'); }


	function install() {
		global $CONF, $DIR_MEDIA;
		$this->createOption('imageURL','URL to your image folder (Always have trailing /)', 'text', $CONF['MediaURL']);
        $this->createOption('imagePath','Absolute filesytem path to your image folder (Always have trailing /)', 'text', $DIR_MEDIA);
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
        else $gbdata = implode(', ',$params);

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
        $media_dir = $this->getOption('imagePath');
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
                $kind = 'image';
            }
        }
        else {
            $images = array($imagename);
        }
        $r = '';
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
                    $r .='<img src="'.$this->getAdminURL().'thumbnail.php?image='.$image.'&size='.$this->getOption('maxSize').'" border="0">';
                } else {
                    $r .= $caption;
                }
                $r .= "</a>\n";
            }
            else {
                $r .= '<a href="'.$this->getOption('imageURL').$image.'" rel="'.$reltext.'" title="'.$caption.'">';
                if ($this->getOption('thumbnails') == 'yes' && strtolower(substr($kind,0,4)) != 'page') {
                    $r .= '<img src="'.$this->getAdminURL().'thumbnail.php?path='.$this->getOption('imageURL').'&image='.$image.'&size='.$this->getOption('maxSize').'" border="0">';
                } else {
                    $r .= $caption;
                }
                $r .= "</a>\n";
            }
        }
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
	        	$target = '/<a(?:\s+?)href=([^>]+)>(<img(?:\s+?)src=[^>]+>)/';
				if (preg_match($target, $this->currentItem->$part)) {
					preg_match($target, $this->currentItem->$part, $match);
					$match[1] = str_replace("\"","",$match[1]);
					if (strpos($match[1],"media/") == 0) {
						$match[1] = $this->str_replace_once("media/", "", $match[1]);
					}
					if (strpos($text,"/media/") == 0) {
						$match[1] = $this->str_replace_once("/media/", "", $match[1]);
					}
					$rep = $this->parse($match);
					$this->currentItem->$part = preg_replace($target,$rep,$this->currentItem->$part);
				}
			}
			//nucleus <%popup()%> code replacement
			if ($this->getOption('repPopup') == "yes") {
				$target = '/<%popup\((.+?)\)%>/';
				if (preg_match($target, $this->currentItem->$part)) {
					preg_match($target, $this->currentItem->$part, $match);
					$count = 0;
					foreach($match as $text) {
						if($count > 0) {
							$string = explode("|",$match[$count]);
							$image = $data['item']->authorid."/".$string[0];
							$caption = end($string);
							$rep = $this->parse($image."|".$caption);
							$this->currentItem->$part = preg_replace($target,$rep,$this->currentItem->$part);
						}
						$count++;
					}
				}
			}
			$this->currentItem->$part = str_replace(array("!~~","~~!"),array("<%GreyBox(",")%>"),$this->currentItem->$part);
            $this->currentItem->$part = preg_replace_callback("#<\%GreyBox\((.*?)\)%\>#", array(&$this, 'parse'), $this->currentItem->$part);
		} //foreach ($parts as $part)
	}
}
?>