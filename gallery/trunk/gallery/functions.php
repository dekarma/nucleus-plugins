<?php
//support functions for NP_gallery

function generateLink($type,$vars = 'date') {
	global $manager,$CONF;

	$base = 'action.php?action=plugin&amp;name=gallery&amp;type=';
	switch($type) {
		case 'list':
			$extra['sort'] = $vars;
			$link = NP_Gallery::makelink('list',$extra);
			break;
		case 'addAlbumF':
		case 'finaldeletepicture': 
			$link = NP_Gallery::makelink($type);
			break;
		case 'editAlbumF': 
			$link = $CONF['PluginURL'].'gallery/index.php?action=album&amp;id='.$vars;
			break;
		case 'item': 
		case 'album': 
			$extra['sort'] = $vars;
			$link = NP_Gallery::makelink('album',$extra);
		case 'editPictureF':
		case 'deletePictureF':
			$extra['id'] = $vars;
			$link = NP_Gallery::makelink($type,$extra);
			break;
		case 'addPictF': $link = $CONF['PluginURL'].'gallery/add_picture.php?type=firststage&amp;id='.$vars;
			break;
		case 'batchaddPictF': $link = $CONF['PluginURL'].'gallery/add_picture.php?type=firststage';
			break;
		default: //$link = $base.$type;
			break;
	}
	return $link;
}


function resizeImage($orig_filename, $target_w, $target_h, $target_filename) {
	global $NPG_CONF, $DIR_NUCLEUS;
	
	$abs_dir = substr($DIR_NUCLEUS,0,strlen($DIR_NUCLEUS) - 8);
	
	if(!$NPG_CONF) {
		$NPG_CONF = getNPGConfig();
		echo 'NPG_CONF not defined in resizeImage<br />';
	}
	
	if($NPG_CONF['graphics_library'] == 'gd') {

		$src_image = imagecreatefromjpeg($abs_dir.$orig_filename);
		
		$old_x=imageSX($src_image);
		$old_y=imageSY($src_image);
		
		//return original image if original image is smaller than resized dimensions
		if ($old_x <= $target_w && $old_y <= $target_h) return $orig_filename;
		
		//resize
		if ($old_x > $old_y) {
			$thumb_w=$target_w;
			$thumb_h=$old_y*($target_w/$old_x);
			if($thumb_h > $target_h) {
				$thumb_w=$old_x*($target_h/$old_y);
				$thumb_h=$target_h;
			}
		}
		if ($old_x < $old_y) {
			$thumb_w=$old_x*($target_h/$old_y);
			$thumb_h=$target_h;
			if($thumb_w > $target_w) {
				$thumb_w=$target_w;
				$thumb_h=$old_y*($target_w/$old_x);
			}
		}
		if ($old_x == $old_y) {
			if($target_w > $target_h) {
				$thumb_w=$old_x*($target_w/$old_y);
				$thumb_h=$target_h;
			} else  {
				$thumb_w=$target_w;
				$thumb_h=$old_y*($target_h/$old_x);
			}
		} 

		$dst_image=ImageCreateTrueColor($thumb_w,$thumb_h);
		imagecopyresampled($dst_image,$src_image,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y); 
		
		if(!imagejpeg($dst_image,$abs_dir.$target_filename,90)) return NULL;
		
		imagedestroy($dst_image);
		imagedestroy($src_image);
	
		return $target_filename;
		
	} elseif ($NPG_CONF['graphics_library'] == 'im') {
		
		//code modified from coppermine photo gallery -- only the non-widows portion was tested, the windows portion was added to the coppermine code so that imagemagick would work even if installed in c:/program files
		$imgFile = escapeshellarg($abs_dir.$orig_filename);
		$output = array();
		$target_file_esc = escapeshellarg($abs_dir.$target_filename);
		
		if (eregi("win",$_ENV['OS'])) {
		$imgFile = str_replace("'","\"" ,$imgFile );
			 $cmd = "\"".str_replace("\\","/", $NPG_CONF['im_path'])."convert\" -quality {$NPG_CONF['im_quality']} {$NPG_CONF['im_options']} -resize {$target_w}x{$target_h} ".str_replace("\\","/" ,$imgFile )." ".str_replace("\\","/" ,$target_file_esc );
			 exec ("\"$cmd\"", $output, $retval);
		} else {
			$cmd = "{$NPG_CONF['im_path']}convert -quality {$NPG_CONF['im_quality']} {$NPG_CONF['im_options']} -resize {$target_w}x{$target_h} $imgFile $target_file_esc";
			exec ($cmd, $output, $retval);
		}
		
		//todo: check for errors
		return $target_filename;

	}
	else return false;
}


function allowedTemplateTags($template) {
	switch ($template) {
		case 'LIST_HEADER':
		case 'LIST_FOOTER':
			$tags='Allowed tags: breadcrumb, sortbytitle, sortbydescription, sortbyowner, sortbymodified, '
					.'sortbynumber, addalbumlink, addpicturelink. Allowed condition(if) tags: canaddalbum, canaddpicture';
			break;
		case 'LIST_BODY':
			$tags='Allowed tags: albumlink, description, ownername, modified(date format), numberofimages';
			break;
		case 'ALBUM_HEADER':
		case 'ALBUM_FOOTER':
			$tags='Allowed tags: breadcrumb, editalbumlink, addpicturelink. Allowed condition(if) tags: caneditalbum, canaddpicture';
			break;
		case 'ALBUM_BODY':
			$tags='Allowed tags: picturelink, thumbnail, picturetitle, centeredtopmargin(height,offset), pictureviews';
			break;
		case 'ITEM_HEADER':
		case 'ITEM_FOOTER':
		case 'ITEM_BODY':
			$tags='Allowed tags: breadcrumb, nextlink, previouslink, fullsizelink, width, height, intermediatepicture, owner, date(format), editpicturelink, deletepicturelink, tooltips, id. Allowed condition(if) tags: caneditpicture';
			break;
		default:
			break;
	}
	return $tags;
}
function getNPGConfig() {
	$result = mysql_query('select * from '.sql_table('plug_gallery_config') );
	if($result) {
		while ($row = mysql_fetch_assoc($result)) {
			$npg_config[$row['oname']] = $row['ovalue'];
		}
	}
	return $npg_config;
}

function setNPGoption($oname, $ovalue) {
	$result = mysql_query("select * from ".sql_table('plug_gallery_config')." where oname='$oname'" );
	if(@ mysql_num_rows($result)) {
		sql_query("update ".sql_table('plug_gallery_config')." set ovalue='$ovalue' where oname='$oname'");
	} else {
		sql_query("insert into ".sql_table('plug_gallery_config')." values ('$oname', '$ovalue' )");
	}
}

function database_cleanup() {
	//check numberofimages for each album
	$result = mysql_query("select count(*) as noi, albumid from ".sql_table('plug_gallery_picture')." group by albumid" );
	if($result) {
		while ($row = mysql_fetch_assoc($result)) {
			$result2 = mysql_query("select numberofimages from ".sql_table('plug_gallery_album')." where albumid = ".$row['albumid']);
			$row2 = mysql_fetch_assoc($result2);
			if($row2['numberofimages'] <> $row['noi']) {
				sql_query("update ".sql_table('plug_gallery_album')." set numberofimages={$row['noi']} where albumid = ".$row['albumid']);
			}
		}
	}
	
	//if picture is not in database, either give choice for deleting it or adding it to the database
	
}

function rethumb($id=0) {
	global $DIR_NUCLEUS,$NPG_CONF;
	
	$abs_dir = $DIR_NUCLEUS.'../';
	$abs_dir = substr($DIR_NUCLEUS,0,strlen($DIR_NUCLEUS) - 8);
	
	//redo the thumbnails and intermediate images
	if($id) $album = ' where albumid='.$id;
	$query = 'select * from '.sql_table('plug_gallery_picture').$album;
	$result = sql_query($query);

	echo 'Resizing images . . . ';
	$timestart = microtime();
	while($row=mysql_fetch_object($result)) {
		//check if file exists:
		
		if(is_file($abs_dir.$row->filename)) {
			//make new thumbnail
			if($new_thumb = resizeImage($row->filename, $NPG_CONF['thumbwidth'], $NPG_CONF['thumbheight'], $row->thumb_filename)) {
				sql_query('update '.sql_table('plug_gallery_picture').' set thumb_filename=\''.$new_thumb.'\' where pictureid='.$row->pictureid);
			}
			else echo '<br/>file: '.$abs_dir.$row->thumb_filename.' could not be resized<br/>';
			//make new intermediate picture
			if($new_thumb = resizeImage($row->filename, $NPG_CONF['maxwidth'], $NPG_CONF['maxheight'], $row->int_filename)) {
				sql_query('update '.sql_table('plug_gallery_picture').' set int_filename=\''.$new_thumb.'\' where pictureid='.$row->pictureid);

			}
			else echo '<br/>file: '.$abs_dir.$row->int_filename.' could not be resized<br/>';
		} else echo '<br/>file: '.$abs_dir.$row->filename.' does not exist -- no action taken<br/>';
	}
	echo 'Done<br/>';
	$timeend = microtime();
	$diff = number_format(((substr($timeend,0,9)) + (substr($timeend,-10)) - (substr($timestart,0,9)) - (substr($timestart,-10))),4);
	echo "Execution time: $diff s <br/>";
}
function GDisPresent() {
	if(function_exists('ImageCreateTrueColor')) return true;
}

function IMisPresent() {
	global $NPG_CONF;
	
	$cmd = "{$NPG_CONF['im_path']}convert -version";
	exec ($cmd, $output, $retval);
	if($retval == 0) return true; 
	return false;
}

function getIMversion() {
	global $NPG_CONF;
	
	$cmd = "{$NPG_CONF['im_path']}convert -version";
	exec ($cmd, $output, $retval);
	if($retval == 0) {
		$pieces = explode(" ", $output[0]);
		$imversion = $pieces[2];
		return $imversion;
	}
	return false;
}

function checkgalleryconfig() {
	global $NP_BASE_DIR,$NPG_CONF;
	
	$status = array();
	
	if((GDispresent() && $NPG_CONF['graphics_library'] == 'gd') || (IMisPresent() && $NPG_CONF['graphics_library'] == 'im')) {
		$status['configured'] = true;
	} else {
		$status['message'] = 'Graphics engine not configured!<br/>';
	}
	
	//check for presence of NPGallery skin
	$res = sql_query('select sdname, scontent from '.sql_table('skin_desc').', '.sql_table('skin').' where sdesc=sdnumber and stype="index" and sdname="NPGallery" LIMIT 1');
	if(!$res) {
		$status['message'] .= 'mysql error checking for NPGallery skin: '.mysql_error().'<br/>';
	}
	else if(!mysql_num_rows($res)) {
		$status['message'] .= 'NPGallery skin was not found<br/>';
	}
	else {
		$row = mysql_fetch_object($res);
		$haystack = stripslashes($row->scontent);
		$s = stristr($haystack,'<%gallery');
		if(!$s) {
			$status['message'] .= '<%gallery%> tag not found in NPGallery skin<br/>';
		}
	}
	
	//check for directory and directory permissions
	$mediadir = $NP_BASE_DIR.$NPG_CONF['galleryDir'];
	if (!@is_dir($mediadir)) {
		$error = 'Gallery directory not found<br/>';
		$oldumask = umask(0000);
		if (!@mkdir($mediadir, 0777)) {
			$error = 'Cannot create gallery directory<br/>';
		}
		else {
			$error = NULL;
			umask($oldumask);				
		}
		$status['message'] .= $error;
	}
	else {
		if (!is_writeable($mediadir)) 
			$status['message'] = 'Gallery directory: '.$mediadir.' not writable';
	}
	
	if($status['message']) $status['configured'] = false; else $status['configured'] = true;
	
	return $status;
	
}

function converttimestamp($d) {
	if(strlen($d) > 14) list($year, $month, $day, $hour, $minute, $second) = sscanf($d, "%4u-%2u-%2u %2u:%2u:%2u");
			else list($year, $month, $day, $hour, $minute, $second) = sscanf($d, "%4u%2u%2u%2u%2u%2u");
	$rd = mktime(intval($hour), intval($minute), intval($second), intval($month), intval($day), intval($year));
	return $rd;
}

?>
