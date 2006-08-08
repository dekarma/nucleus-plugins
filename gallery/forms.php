<?php


function addAlbumForm() {
	global $CONF;
	
	if($CONF['URLMode'] == 'pathinfo') $action = 'gallery'; else $action = 'action.php';
	
	?>
	<h1><?php echo __NPG_FORM_ADDALBUM; ?></h1>
	<form method="post" action="<?php echo $action; ?>"><div>
		<input type="hidden" name="action" value="plugin" />
		<input type="hidden" name="name" value="gallery" />
		<input type="hidden" name="type" value="addAlbum" />
		
		<?php addAlbumFormFields(); ?>
	</div></form>
	
	<?php
}

function addAlbumFormFields() {
	?>
		<table>
		<tr><td><label for="atitle"><?php echo __NPG_FORM_ALBUM_TITLE; ?>:</label></td>
		<td><input type="text" name="title" id="atitle" size="20" /></td></tr>
		<tr><td><label for="atitle"><?php echo 'public album'; ?>:</label></td>
		<td><input type="radio" <?php if($data->publicalbum) echo 'checked ';?> name="publicalbum" id="publicalbum_f" value="1" /><?php echo 'yes'; ?>
		<input type="radio" <?php if(!$data->publicalbum) echo 'checked ';?> name="publicalbum" id="publicalbum_f" value="0" /><?php echo 'no'; ?></td></tr>
		<tr><td><label for="adesc"><?php echo __NPG_FORM_ALBUM_DESC; ?>:</label></td>
		<td><input type="text" name="desc" id="adesc" size="80" /></td></tr>
		<tr><td colspan="2"><input type="submit" value="<?php echo __NPG_FORM_SUBMITALBUM; ?>"></td></tr>
		</table>
	<?php
}

function deleteAlbum($id) {
	global $gmember, $galleryaction;
	


	if(!$galleryaction) return false;
	echo '<h1>'.__NPG_FORM_DELETE_ALBUM.'</h1>';
	echo __NPG_FORM_REALLY_DELETE_ALBUM.'<br/>';

	echo '<form><input type="button" value="'.__NPG_FORM_CANCEL.'" onclick="window.location.href=\''.$galleryaction.'\'"/></form>';
	
	echo __NPG_FORM_DELETE_OR_MOVE.'<br/>';
	?>
	<form method="post" action="<?php echo $galleryaction; ?>"><div>
		<input type="hidden" name="action" value="finaldeletealbum" />
		<input type="hidden" name="id" value="<?php echo $id; ?>" />
		
		<select name="deleteoption">
		<option value="-1" selected><?php echo __NPG_FORM_DELETE_PICTURES; ?>
		<?php
			$allowed_albums = $gmember->getallowedalbums();
			if($allowed_albums) { 
				$j=0;
				while($allowed_albums[$j]) {
					echo '<option value="'.$allowed_albums[$j]->albumid.'">Move pictures to '.$allowed_albums[$j]->albumname;
					$j++;
				}
			}
		?>
		</select>
		<input type="submit" value="<?php echo __NPG_FORM_DELETE; ?>" />
	</div></form>
	<?php

}


function editAlbumForm($id) {
	global $gmember,$galleryaction;
	
	if(!$galleryaction) {
		echo 'galleryaction variable not set<br/>';
		return;
	}
	
	echo '<a href="'.$galleryaction.'">'.__NPG_FORM_RETURN_ADMIN.'</a><br/><br/>';
	
	$data = ALBUM::get_data($id);
	?>
	<h3><?php echo __NPG_FORM_MODIFY_ALBUM; ?></h3>
	<form method="post" action="<?php echo $galleryaction; ?>"><div>
		<input type="hidden" name="action" value="editalbumtitle" />
		<input type="hidden" name="id" value="<?php echo $id; ?>" />
		
		<table><tr><td>
		<label for="atitle"><?php echo __NPG_FORM_ALBUM_TITLE; ?>: </label>
		</td><td>
		<input type="text" name="title" id="atitle" value = "<?php echo stripslashes($data->title); ?>" size="20" />
		</td></tr><tr><td>
		<label for="adesc"><?php echo __NPG_FORM_ALBUM_DESC; ?>: </label>
		</td><td>
		<input type="text" name="desc" id="adesc" value = "<?php echo stripslashes($data->description); ?>" size="60" /><br />
		</td></tr><tr><td>
		<label for="commentsallowed_f"><?php echo __NPG_FORM_COMMENTSALLOWED; ?></label>
		</td><td>
		<input type="radio" <?php if($data->commentsallowed) echo 'checked ';?> name="commentsallowed" id="commentsallowed_f" value="1" /><?php echo __NPG_FORM_YES; ?>
		<input type="radio" <?php if(!$data->commentsallowed) echo 'checked ';?> name="commentsallowed" id="commentsallowed_f" value="0" /><?php echo __NPG_FORM_NO; ?>
		</td></tr>
		<tr><td>
		<label for="publicalbum_f"><?php echo 'publicalbum'; ?></label>
		</td><td>
		<input type="radio" <?php if($data->publicalbum) echo 'checked ';?> name="publicalbum" id="publicalbum_f" value="1" /><?php echo __NPG_FORM_YES; ?>
		<input type="radio" <?php if(!$data->publicalbum) echo 'checked ';?> name="publicalbum" id="publicalbum_f" value="0" /><?php echo __NPG_FORM_NO; ?>
		</td></tr><tr><td>
		<label for="thumbnail_f"><?php echo __NPG_FORM_ALBUM_THUMBNAIL; ?></label>
		</td><td>
		<select name="thumbnail" id="thumbnail_f">
		<?php
		$pics = ALBUM::get_pictures($id);
		$k=0;
		while($pics[$k]) {
			echo '<option value="'.$pics[$k]->thumb_filename.'" ';
			if ($pics[$k]->thumb_filename == $data->thumbnail) echo ' selected ';
			echo '>'.$pics[$k]->title;
			$k++;
		}
		?>
		</select>
		</td></tr></table>
		<br/><input type="submit" value="Submit" />
	</div></form>
	<br/>
	<h3><?php echo __NPG_FORM_CURRENT_ALBUM_TEAM; ?></h3>
	<form method="post" action="<?php echo $galleryaction; ?>"><div>
		<input type="hidden" name="action" value="editalbumteam" />
		<input type="hidden" name="id" value="<?php echo $id; ?>" />
		
		<table>
		<thead><tr><th><?php echo __NPG_FORM_NAME; ?></th><th><?php echo __NPG_FORM_ALBUM_ADMIN; ?></th><th colspan='2'><?php echo __NPG_FORM_ACTIONS; ?></th></thead>
		<tbody>
		<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>
		<td><?php echo $data->name.' ('.__NPG_FORM_OWNER.')'; ?></td>
		<td>Yes</td>
		<td colspan='2' ><?php echo __NPG_FORM_NO_OWNER_ACTIONS; ?></td>
		</tr>
		<?php
		$team = ALBUM::get_team($id);
		if($team) {
			$j=0;
			while($team[$j]) {
				?><tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'><?php
				echo '<td>'.$team[$j]->mname.'</td>';
				if($team[$j]->tadmin) echo '<td>'.__NPG_FORM_YES.'</td>'; else echo '<td>'.__NPG_FORM_NO.'</td>';
				echo '<td><a href="'.$galleryaction.'?action=deltmember&amp;mid='.$team[$j]->tmemberid.'&amp;aid='.$id.'">'.__NPG_FORM_DELETE.'</a></td>';
				echo '<td><a href="'.$galleryaction.'?action=toggleadmin&amp;mid='.$team[$j]->tmemberid.'&amp;aid='.$id.'">'.__NPG_FORM_TOGGLE_ADMIN.'</a></td></tr>';
				$j++;
			}
		}
		
		?>
		</tbody></table>
	</div></form>
	<br/>
	<h3><?php echo __NPG_FORM_ADDTEAMMEMBER; ?></h3>
	<form method="post" action="<?php echo $galleryaction; ?>"><div>
		<input type="hidden" name="action" value="addalbumteam" />
		<input type="hidden" name="id" value="<?php echo $id; ?>" />
		
		<table><tr>
		<td><?php echo __NPG_FORM_CHOOSEMEMBER; ?>:</td>
		<td>
		<?php
		//this query lists the members that are not already part of the team, not the admins(they already have permissions) and are not the owner of the album
		$result = mysql_query('select mname, mnumber from '.sql_table('member').' left join '.sql_table('plug_gallery_album_team').' on mnumber=tmemberid and talbumid='.$id.' where mnumber <> '.$data->ownerid.' and madmin=0 and tmemberid is null');
		if($result) {
			$num_rows = mysql_num_rows($result);
			if($num_rows) {
				echo '<select name="tmember">';
				while($m = mysql_fetch_object($result)) echo '<option value="'.$m->mnumber.'">'.$m->mname;
				echo '</select>';
			}
		}
		?>
		</td></tr>
		<tr><td><?php echo __NPG_FORM_ADMIN_PRIV; ?> </td>
		<td><input type="radio" name="admin" value="1"  id="admin1" />
		<label for="admin1"><?php echo __NPG_FORM_YES; ?></label>
		<input type="radio" name="admin" value="0" checked='checked' id="admin0" />
		<label for="admin0"><?php echo __NPG_FORM_NO; ?></label></td></tr>
		
		<tr><td><?php echo __NPG_FORM_ADDTOTEAM; ?></td>
		<td><input type='submit' value='<?php echo __NPG_FORM_ADDTOTEAM; ?>' /></td></tr></table>
		
	</div></form>
	<?php
	
}


function editPictureForm($id) {
	global $gmember,$manager;
	
	//todo:add delete picture link, add move to different album link
	$data = PICTURE::get_data($id);
	if($data->pictureid) {
		?>
		<h1><?php echo __NPG_FORM_EDITPICTURE; ?></h1>
		<form method="post" action="action.php"><div>
			<input type="hidden" name="action" value="plugin" />
			<input type="hidden" name="name" value="gallery" />
			<input type="hidden" name="type" value="editPicture" />
			<input type="hidden" name="id" value="<?php echo $id; ?>" />
			
			<table><tr><td><?php echo __NPG_FORM_PICTURETITLE; ?></td>
			<td><input type="text" name="ptitle" value="<?php echo $data->title; ?>" size="40" /></td></tr>
			<tr><td><?php echo __NPG_FORM_PICTUREDESCRIPTION; ?></td>
			<td><input type="text" name="pdesc" value="<?php echo $data->description; ?>" size="70" /></td></tr>
			<tr><td>keywords(seperate with ','): </td>
			<td><input type="text" name="keywords" value="<?php echo $data->keywords; ?>" size="70" /></td></tr>

			<?php
			
			$allowed_albums = $gmember->getallowedalbums();
			if($allowed_albums[1]) { //if more than 1 allowed album display Move Album option
				echo '<tr><td>'.__NPG_FORM_MOVETOALBUM.': </td><td><select name="aid">';
				$j=0;
				while($allowed_albums[$j]) {
					echo '<option value="'.$allowed_albums[$j]->albumid.'"';
					if($allowed_albums[$j]->albumid == $data->albumid) echo 'selected';
					echo '>';
					echo $allowed_albums[$j]->albumname;
					$j++;
				}
				echo '</select></td></tr></table>';
			}
			$manager->notify('NPgEditPictureFormExtras',array('pictureid'=>$id,'title'=>$data->title,'description'=>$data->description));
			
			?>
			<br/><input type="submit" value="<?php echo __NPG_FORM_SUBMIT_CHANGES; ?>"></table>
		</div></form>
		<?php
	}
	else echo __NPG_FORM_NOPICTOEDIT;
	
}

function deletePictureForm($id) {
	$data = PICTURE::get_data($id);
	if($data->pictureid) {
		echo '<img src="'.$data->thumb_filename.'" /><br/>';
		echo __NPG_FORM_REALLYDELETE.'<br/>';
		echo '<form><input type="button" value="'.__NPG_FORM_CANCEL.'" onclick="history.back()"/></form>';
		
		echo '<br/>';
		echo __NPG_FORM_DELETEPICTURETEXT;
		echo '<form method="post" action="action.php">';
		echo '<input type="hidden" name="action" value="plugin" />';
		echo '<input type="hidden" name="name" value="gallery" />';
		echo '<input type="hidden" name="type" value="finaldeletepicture" />';
		echo '<input type="hidden" name="id" value="'.$id.'" />';
		echo __NPG_FORM_DELETEPROMOTOO;
		echo __NPG_FORM_YES.':<input type="radio" checked name="delpromo" id="promo" value="yes" />';
		echo ' '.__NPG_FORM_YES.':<input type="radio" name="delpromo" id="promo" value="no" />';
		echo '<br/><input type="submit" value="'.__NPG_FORM_DELETE.'"/></form>';
	}
	else echo __NPG_FORM_NOPICTTODELETE;
}

function addPictureForm($albumid = 0, $num_files = 0) {
	global $NPG_CONF,$CONF;
	
	if(!$num_files) {
		if($NPG_CONF['batch_add_num']) $num_files = $NPG_CONF['batch_add_num'];
		else $num_files = 10;
	}
	
	?>
	<h1><?php echo __NPG_FORM_UPLOADFILEFORM; ?></h1>
	<form enctype="multipart/form-data" method="post" action="<?php echo $CONF['PluginURL'].'gallery/add_picture.php'; ?>"><div>
	<input type="hidden" name="type" value="secondstage" />
	<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
	
	<table>
	<?php
	if($albumid) echo '<input type="hidden" name="id" value="'.$albumid.'" />';
	
	for($i=0; $i<$num_files; $i++) {
		$j = $i+1;
		echo '<tr><td>'.__NPG_FORM_PICTURELABLE.' '.$j.'</td><td><input type="file" name="uploadpicture'.$i.'"></td></tr>';
	}
	?>
	</table>
	<input type="submit" value="<?php echo __NPG_FORM_SUBMITFILES; ?>">
	</div></form>
	<?php
}

function addpictureformjupload($albumid = 0, $num_files = 0) {
	global $NPG_CONF,$CONF;
	$exist_temp_table = mysql_query('SELECT 1 FROM '.$NPG_CONF['temp_table'].' LIMIT 0');
	if ($exist_temp_table) sql_query('drop table '. $NPG_CONF['temp_table']);

	?>
	<html>
<!--

 Author: $Author: mhaller $

 Id: $Id: JUpload.html,v 1.1 2004/02/05 08:59:40 mhaller Exp $

 Version: $Revision: 1.1 $

 Date: $Date: 2004/02/05 08:59:40 $

-->
<head>
<title>JUpload - multiple file upload with resuming</title>
<meta name="Author" content="Mike Haller">
<meta name="Publisher" content="Haller Systemservice">
<meta name="Copyright" content="Mike Haller">
<meta name="Keywords" content="jupload, multiple, java, upload, http, html, applet, embed, object, input, type, file, submit, add, remove, queue, rfc 1867, application/x-www-form-urlencoded, POST METHOD, swing, awt, j2se, transfer, files, requests, webserver, apache, asp, jsp, php4, php5, php, multipart, content-disposition, form-data, boundary, attachment, mime headers, transmission, enctype, remote data, browser, internet explorer, mozilla, opera, fileuploader, batch upload, file selection dialog, resuming, resume, continue">
<meta name="Description" content="JUpload is a java applet for uploading multiple files to the webserver using RFC1867 post method. It features a status display showing current transfer rate.">
<meta name="Page-topic" content="HTTP file upload with resuming using post or put method featuring https and proxy">
<meta name="Audience" content="Advanced">
<meta name="Content-language" content="EN">
<meta name="Page-type" content="Software-Download">
<meta name="Robots" content="INDEX,FOLLOW">

</head>
<body>
 <br>
 <applet 
  code="JUpload.startup"
  archive="/nucleus/jupload.jar"
  width="500"
  height="300"
  mayscript="mayscript"
  name="JUpload"
  alt="JUpload by www.jupload.biz">
 <!-- Java Plug-In Options -->
 <param name="progressbar" value="true">
 <param name="boxmessage" value="Loading JUpload Applet ...">
 <!-- Target links -->
 <param name="actionURL" value="/nucleus/nucleus/plugins/gallery/juploadaccept.php">
 <!PARAM NAME="maxTotalRequestSize" VALUE="4">
 <!--  <param name="preselectedFiles" value="c:\test.pdf"> -->
<!-- IF YOU HAVE PROBLEMS, CHANGE THIS TO TRUE BEFORE CONTACTING SUPPORT -->
<param name="debug" value="true">
 Your browser does not support applets. Or you have disabled applet in your options.

 To use this applet, please install the newest version of Sun's java. You can get it from <a href="http://www.java.com/">java.com</a>
 </applet>
<a href="<?php echo $CONF['PluginURL'] ?>gallery/add_picture.php?type=massupload&id=<?php echo $albumid ?>">Next step

</a></body>

<?php

}
	
function addTempPictureForm($albumid = 0) {
	global $NPG_CONF, $gmember,$manager,$CONF,$NP_BASE_DIR;
	
	$NPG_CONF = getNPGConfig();
	$table_name = $NPG_CONF['temp_table'];
	
	$promo_allowed = false;
	if($NPG_CONF['blog_cat'] <> 0) $promo_allowed=true;
	$NPG_CONF['promo_allowed'] = $promo_allowed;
	
	//form proper
	echo '<h1>'.__NPG_FORM_UPLOADFILEFORM.'</h1>';
	echo '<form method="post" action="'.$CONF['PluginURL'].'gallery/add_picture.php'.'"><div>';
	echo '<input type="hidden" name="type" value="addpictures" />';
	echo '<input type="hidden" name="promopost" value="'.$promo_allowed.'" />';

	$result = mysql_query("select * from $table_name");
	if($result) $num_rows = mysql_num_rows($result);
	
	if($num_rows) {
		echo '<table>';
		$i=0;
		$setorpromo = $NPG_CONF['setorpromo'];
		while($row = mysql_fetch_assoc($result) ) {
			if ( $row['error'] == '') {
				echo '<input type="hidden" name="tid'.$i.'" value="'. $row['tempid'] .'" />';
				echo '<input type="hidden" name="filename'.$i.'" value="'. $row['filename'] .'" />';
				echo '<input type="hidden" name="thumbfilename'.$i.'" value="'. $row['thumbfilename'] .'" />';
				echo '<input type="hidden" name="intfilename'.$i.'" value="'. $row['intfilename'] .'" />';
				
				echo '<tr><td><img src="'.$CONF['IndexURL'].$row['thumbfilename'].'"></td><td>';
				echo '<table><tr><td>'.__NPG_FORM_TITLE.'</td>';
				echo '<td><input type="text" name="title'.$i.'" value="'.$row['title'].'"></td></tr>';
				echo '<tr><td>'.__NPG_FORM_DESC.'</td>';
				echo '<td><input type="text" name="description'.$i.'" value="'.$row['description'].'"></td></tr>';
				if($promo_allowed && $setorpromo=='promo') echo '<tr><td>'.__NPG_FORM_PROMOTE.'</td><td> Yes<input type="radio" name="promote'.$i.'" value="yes"> No<input type="radio" checked name="promote'.$i.'" value="no"></td></tr>';
				if($setorpromo=='sets'){echo '<tr><td>keywords (seperate with ','):</td><td> <input type="text" name="keywords'.$i.'" value=""></td></tr>';}
				if(!$albumid) {
					echo '<tr><td>'.__NPG_FORM_ADDTOALBUM.'</td><td><select name="album'.$i.'">';
					$allowed_albums = $gmember->getallowedalbums();
					$j=0;
					while($allowed_albums[$j]) {
						echo '<option value="'.$allowed_albums[$j]->albumid.'">'.$allowed_albums[$j]->albumname;
						$j++;
					}
				}
				else echo '<input type="hidden" name="album'.$i.'" value="'.$albumid.'">';
				echo '</select></td></tr></table>';
				
				$manager->notify('NPgAddPictureFormExtras',array('i'=>$i,'ttid'=>$row['tempid'], 'filename' =>$row['filename'],'thumbfilename'=>$row['thumbfilename'],'intfilename'=>$row['intfilename'],'title'=>$row['title'],'description'=>$row['description'],'albumid'=>$albumid ));
				$i++;
			}
			else echo '<br>'.$row['filename'].' '.__NPG_FORM_NOADD.': '.$row['error'].'<br/><br/>';
			
		}
	}
	else echo __NPG_FORM_NOPICTSTOADD.'<br/>';
	if($i == 0) echo __NPG_FORM_NOPICTSTOADD.'</td></tr></table><br/></div></form>'; 
	else {echo '</td></tr></table><br/>';
			if($setorpromo=='sets'){
				echo '<input type="hidden" name="promote0" value="yes">'.
				'Enter keywords to promote to your blog(serperate with and):<input type="text" name="promokeywords" value=" " />';
			}
			echo '<input type="submit" value="'.__NPG_FORM_SUBMITFILES.'"></div></form>';
	}

}



?>
