<?php

/*

Admin area for NP_ImageBadge

*/

	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../';

	include($strRel . 'config.php');
	if (!$member->isLoggedIn())
		doError('You\'re not logged in.');

	include($DIR_LIBS . 'PLUGINADMIN.php');
	
	// some functions
	
	function ImageBadgeAdd($image, $link) {
		sql_query("INSERT INTO " . sql_table('plug_imagebadge') . "(image, link) VALUES('" . $image . "', '" . $link . "' )");
	}
	
	function ImageBadgeDelete($ids) {
		sql_query("DELETE FROM " . sql_table('plug_imagebadge') . " WHERE id IN (" . $ids . ")");
	}
	
	// checks
	
	/*
	Empty links are filtered out in doSkinVar,
	other wise, blank pages open on click on image.
	*/
	
	// if form for new image is posted
	if($_POST['action'] == 'ibnew') {
		// if the image field is not empty
		if($_POST['newimage'] != '') {
			ImageBadgeAdd($_POST['newimage'], $_POST['newlink']);
		}
		else {
			$message = "Error: Enter URL to image";
		}
	}	
	// if form to delete is posted and checkbox(es) was checked
	if($_POST['action'] == 'ibdel' && $_POST['delete']) {
		// take array with id's, implode with comma for use in query
		$imploded = implode(',', $_POST['delete']);
		ImageBadgeDelete($imploded);
		$message = "Records ".$imploded." deleted";
	}
	// if form to delete is posted but no checkboxes were checked
	if($_POST['action'] == 'ibdel' && !$_POST['delete']) {
		$message = "Error: Select entries to delete";
	}

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('ImageBadge');
	// add styles to the <HEAD>
	$oPluginAdmin->start('<style type="text/css">#newimage,#newlink{width:100%;}.IBThumb{width:100px;height:100px;}td.submit{text-align:right;}</style>');

	// page title
	echo '<h2>ImageBadge Administration</h2>';
	
	// error output
	if($message) { echo "<p><strong>"; echo $message; echo "</strong></p>"; }
		
	// form to enter new image+link
	echo '<h3>Add entry</h3>';
	echo '<form action="' . $oPluginAdmin->plugin->getAdminURL() . '" method="POST">';
	echo '<input type="hidden" name="action" value="ibnew" />';
	$manager->addTicketHidden();
	echo '<table>';
	echo '<tr><td>';
	echo '<label for="newimage">URL to image: </label>';
	echo '</td><td>';
	echo '<input type="text" name="newimage" id="newimage" value="" />';
	echo '</td></tr><tr><td>';
	echo '<label for="newlink">URL for link: (optional)</label>';
	echo '</td><td>';
	echo '<input type="text" name="newlink" id="newlink" value="" />';
	echo '</td></tr>';
	echo '<tr><td colspan="2" class="submit">';
	echo '<input type="submit" value="submit" />';
	echo '</td></tr></table>';
	echo '</form>';
	
	// generate table from all entries in the database
	// embedded form with selectboxes, user can select multiple images to delete at once
	echo '<h3>Current entries</h3>';
	echo '<form action="' . $oPluginAdmin->plugin->getAdminURL() . '" method="POST">';
	echo '<input type="hidden" name="action" value="ibdel" />';
	$manager->addTicketHidden();
	echo '<table>';
	echo '<tr><th>Image</th><th>URLs</th></tr>';
	echo '<tr><td colspan="2" class="submit"><input type="submit" value="submit" /></td></tr>';
	// do query to get all entries, loop
	$result = sql_query("SELECT * FROM ".sql_table("plug_imagebadge"));
	if($result) {
		while($row = mysql_fetch_assoc($result)) {
			echo '<tr>';
  				echo '<td rowspan="3"><img class="IBThumb" src="'.$row['image'].'" alt="This image is used in the badge" /></td>';
  				echo '<td><a href="'.$row['image'].'" rel="external">'.$row['image'].'</a></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td><a href="'.$row['link'].'" rel="external">'.$row['link'].'</a></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td><input type="checkbox" name="delete[]" value="'.$row['id'].'" />Delete</td>';
			echo '</tr>';
		}
	}
	else {
		echo '<tr><td colspan="2"><strong>No records found!</strong></td></tr>';
	}
	echo '<tr><td colspan="2" class="submit"><input type="submit" value="submit" /></td></tr>';
	echo '</table>';
	echo '</form>';
	
	// debug line to see what is taken from the database
	//echo '<pre>'; print_r(mysql_fetch_object(mysql_query("SELECT * FROM nucleus_plug_imagebadge"))); echo '</pre>';
	
	$oPluginAdmin->end();

?>