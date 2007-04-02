<?php
   /* Based on a Nucleus Plugin called NP_LightBox by Seventoes
	* NP_ThickBox is a simple plugin to add LightBox javascript functionality to
	* Nucleus blogs. NP_ThickBox will automatically create a thumbnail of the
	* image, and when clicked, will show the full size image in LightBox style.
	*
	* thickbox.js is a script written by Cody Lindley
	* http://codylindley.com/Javascript/266/thickbox-update
    */

   /* ==========================================================================================
	* ThickBox for Nucleus
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
class NP_ThickBox extends NucleusPlugin {

	function getName() {return 'ThickBox';}
	function getAuthor()  {return 'Frank Truscott, based on work by Seventoes';}
	function getURL(){return 'http://www.iai.com/';}
	function getVersion() {return '1.22';}
	function getDescription() {
		return 'Simple plugin to enable ThickBox on Nucleus Blogs';
	}
	function getEventList() { return array('PreItem'); }


	function install() {
		global $CONF, $DIR_MEDIA;
		$this->createOption('imageURL','URL to your image folder (Always have trailing /)', 'text', $CONF['MediaURL']);
        $this->createOption('imagePath','Absolute filesytem path to your image folder (Always have trailing /)', 'text', $DIR_MEDIA);
        $this->createOption('rootDir','URL to your thickbox root directory (Always have trailing /)', 'text', $this->getAdminURL());
		$this->createOption('defaultCaption','Text to be displayed if no caption is provided (set to "TB_imageName" to display the image\'s filename)','text','TB_imageName');
		$this->createOption('thumbnails','Do you want to display thumbnails? If not, the caption will be displayed.','yesno','yes');
		$this->createOption('maxSize','What is the maximum size for thumbnails? The largest side of the image will be reduced down to this size in pixels.', 'text', '150');
		$this->createOption('repPopup', 'Replace the default nucleus <%popup()%> behavior with thickbox?', 'yesno', 'no');
		$this->createOption('repNormal', 'Replace normal image links with thickbox?', 'yesno', 'no');
	}

	function doSkinVar($skinType) {
        if ($this->getOption('rootDir')) $tb_root = $this->getOption('rootDir');
        else $tb_root = $this->getAdminURL();
        echo '<script type="text/javascript">'."\n";
        echo 'var TB_ROOT_DIR = "'.$tb_root.'"';
        echo '</script>'."\n";
		echo '<link rel="stylesheet" href="'.$tb_root.'css/thickbox.css" type="text/css" media="screen" />'."\n";
		echo '<script type="text/javascript" src="'.$tb_root.'js/jquery.js"></script>'."\n";
		echo '<script type="text/javascript" src="'.$tb_root.'js/thickbox.js"></script>'."\n";
	}

	function parse($matches) {
        $media_dir = $this->getOption('imagePath');
        $media_url = $this->getOption('imageURL');
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

		$sections = explode("|", $matches[1]);
		if (substr($sections[0],0,1) == "/") $sections[0] = substr($sections[0],1);
		if (substr($sections[0],-1) == "/") $sections[0] = substr($sections[0],0,strlen($sections[0]) -1);
		$imagename = $sections[0];
        if (is_dir($media_dir.$imagename)) {
            $dh  = opendir($media_dir.$imagename);
            while (false !== ($filename = readdir($dh))) {
				if (!is_dir($media_dir.$imagename."/".$filename)) {
					$images[] = "$imagename/$filename";
				}
            }
			if ($sections[2] == '') $sections[2] = 'fred'.substr($imagename,-4);
		}
        else {
            $images = array($imagename);
        }
        $r = '';
        foreach ($images as $image) {
            if ($this->getOption('defaultCaption') == "TB_imageName") {
                $imgName = end(explode("/",$image));
                $caption = ($sections[1])? $sections[1] : $imgName;
            }
            else {
                $caption = ($sections[1])? $sections[1] : $this->getOption('defaultCaption');
            }

            $reltext = ($sections[2] ? ''.$sections[2].'' : '');
            if ((strpos(strtolower($image),strtolower($this->getOption('imageURL'))) !== false) || (strpos($image,"http://") !== false)) {
                $r .= '<a href="'.$image.'" class="thickbox" rel="'.$reltext.'" title="'.$caption.'">';
                if ($this->getOption('thumbnails') == 'yes') {
                    $r .='<img src="'.$this->getAdminURL().'thumbnail.php?image='.$image.'&size='.$this->getOption('maxSize').'" alt="'.$caption.'" border="0">';
                } else {
                    $r .= $caption;
                }
                $r .= "</a>\n";
            }
            else {
                $r .= '<a href="'.$this->getOption('imageURL').$image.'" class="thickbox" rel="'.$reltext.'" title="'.$caption.'">';
                if ($this->getOption('thumbnails') == 'yes') {
                    $r .= '<img src="'.$this->getAdminURL().'thumbnail.php?path='.$thumb_path.'&image='.$image.'&size='.$this->getOption('maxSize').'" alt="'.$caption.'" border="0">';
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
		$r = "<%ThickBox(".$sections[0]."|".end($sections).")%>";
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
		$r = "<%ThickBox(".$matches[1].")%>";
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

			$this->currentItem->$part = str_replace(array("!~~","~~!"),array("<%ThickBox(",")%>"),$this->currentItem->$part);
            $this->currentItem->$part = preg_replace_callback("#<\%ThickBox\((.*?)\)%\>#", array(&$this, 'parse'), $this->currentItem->$part);
		} //foreach ($parts as $part)
	}
}
?>