<?php

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')) {
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_CurrentMedia extends NucleusPlugin {

	function getName() {
		return 'Current Media';
	}
	function getAuthor() {
		return 'gRegor Morrill';
	}
	function getURL() {
		return 'http://www.gregorlove.com';
	}
	function getVersion() {
		return '0.5';
	}
	function getDescription() {
		return 'This plugin allows you to put a Currently Watching/Listening/Reading/Playing block on weblog posts.';
	}
	function getTableList()	{
		return array( sql_table('plugin_currentmedia') );
	}
	function getEventList() {
		return array('PostAddItem','AddItemFormExtras','EditItemFormExtras','PreUpdateItem','PostDeleteItem');
	}
	function getMinNucleusVersion() {
		return 200;
	}
	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	/****************************************************************
	This function is used to install the CurrentMedia plugin.  It 
	creates a table to store the media information, as well as an 
	option to use your own Amazon Associate ID.  By default this 
	associate ID is for Nucleus, meaning any sales will generate 
	commissions for Nucleus.
	*****************************************************************/
	function install() {
		sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_currentmedia') . '( cm_id int(11) not null, cm_type smallint not null, cm_asin varchar(25) not null, cm_title varchar(255) not null, cm_by varchar(255), cm_track varchar(255), cm_image varchar(255), PRIMARY KEY(cm_id) )');
		$this->createOption('site', 'Amazon site to use for searches:', 'select', 'en', 'Amazon.com|en|Amazon.de|de');
		$this->createOption('currently', 'Translation to use for "Currently":', 'text', 'Currently');
		$this->createOption('listening', 'Translation to use for "Listening":', 'text', 'Listening');
		$this->createOption('watching', 'Translation to use for "Watching":', 'text', 'Watching');
		$this->createOption('reading', 'Translation to use for "Reading":', 'text', 'Reading');
		$this->createOption('playing', 'Translation to use for "Playing":', 'text', 'Playing');
		$this->createOption('by', 'Translation to use for "by":', 'text', 'by');
		$this->createOption('song', 'Translation to use for "Song":', 'text', 'Song');
		$this->createOption('starring', 'Translation to use for "starring":', 'text', 'starring');
		$this->createOption('assocID', '(Optional) Your Amazon Associate ID:', 'text', 'nucleuscms-20');
		$this->createOption('deletetables', 'Delete this plugin\'s table and data when uninstalling?', 'yesno', 'no');
	}

	/****************************************************************
	This function drops the table from the database for this plugin
	if the appropriate plugin option is set to 'yes'.  It is set to
	'no' by default.  Generally, when upgrading this plugin, users
	should set the "delete tables" option to 'no', uninstall it, and 
	re-install the newer version.
	*****************************************************************/
	function unInstall() {
		if ($this->getOption('deletetables') == 'yes') {
			sql_query('DROP TABLE ' . sql_table('plugin_currentmedia') );
		}
	}

	/****************************************************************
	This function is used to display the plugin data when called from
	templates.
	*****************************************************************/
	function doTemplateVar(&$item) {
		$itemid = $item->itemid;
		$site = $this->getOption('site');
		$associd = $this->getOption('assocID');
		$word_currently = $this->getOption('currently');
		$word_song = $this->getOption('song');

		// Create SQL query
		$query = 'SELECT cm_type, cm_asin, cm_title, cm_by, cm_track, cm_image FROM ' . sql_table('plugin_currentmedia') . ' WHERE cm_id = ' . $itemid . '';
		$result = mysql_query($query);

		// If there is media data, retrieve and output it.
		if (mysql_num_rows($result) != 0) {
			$mediadata = $this->_getMediaData($result);

			// Construct Amazon URL
			switch($site) {
				case 'en':
					$URLbase = 'http://www.amazon.com/';
					break;
				case 'de':
					$URLbase = 'http://www.amazon.de/';
					break;
			}

			$mediaurl = $URLbase . 'exec/obidos/ASIN/' . $mediadata['asin'] . '/' . $associd;

			// Construct the media block
			$mediablock = '<table class="cm_table" border="0" cellspacing="0" cellpadding="2">' . "\n";
			$mediablock.= '<tr>' . "\n" . '<td class="cm_image" valign="top">';
			$mediablock.= '<img src="' . $mediadata['image'] . '" alt="' . $mediadata['title'] . '" />';
			$mediablock.= '</td>' . "\n" . '<td class="cm_text" valign="top">';
			$mediablock.= '<b>' . $word_currently . ' ' . $mediadata['mediaword'] . '</b> <br />';
			$mediablock.= '<a href="' . $mediaurl . '">' . $mediadata['title'] . '</a><br />';
			$mediablock.= $mediadata['descword'] . ' ' . $mediadata['by'] . '<br />';
			if ($mediadata['track']) {
				$mediablock.= $word_song . ': ' . $mediadata['track'];
			}
			$mediablock.= '</td>' . "\n" . '</tr>' . "\n" . '</table>' . "\n";

			// Display the media block
			echo $mediablock;
		}
	}

	/****************************************************************
	This hook is used to display the plugin form on the 
	'Add Item' page.
	*****************************************************************/
	function event_AddItemFormExtras($data) {
		$this->_showPluginForm();
	}

	/****************************************************************
	This hook is used to display the plugin form on the
	'Edit Item' page.
	*****************************************************************/
	function event_EditItemFormExtras($data) {
		$this->_showPluginForm('edit', $data['itemid']);
	}

	/****************************************************************
	This hook is used to handle adding the media item from the
	'Add Item' page.  Pretty straightforward stuff.
	*****************************************************************/
	function event_PostAddItem($data) {
		$action = requestVar('cmaction');
		if ($action != 'none')
		{
			$itemid = $data['itemid'];
			$type = requestVar('cmtype');
			$asin = requestVar('cmasin');
			$title = addslashes(requestVar('cmtitle') );
			$by = addslashes(requestVar('cmby') );
			$image = requestVar('cmimage');
			$track = addslashes(requestVar('cmtrack') );

			$query = "INSERT INTO " . sql_table('plugin_currentmedia') . " VALUES ($itemid, $type, '$asin', '$title', '$by', '$track', '$image')";
			mysql_query($query);
		}
	}

	/****************************************************************
	This hook is used to handle updating the media item from the 
	'Edit Item' page.  By default this does nothing, unles the user
	wants to change or delete the media attached to the item.  If
	updating, there are two possibilities: inserting new media (if
	nothing was added previously), or updating pre-existing media
	*****************************************************************/
	function event_PreUpdateItem($data) {
		$itemid = $data['itemid'];
		$action = requestVar('cmaction');
		$type = requestVar('cmtype');
		$asin = requestVar('cmasin');
		$title = addslashes(requestVar('cmtitle') );
		$by = addslashes(requestVar('cmby') );
		$image = requestVar('cmimage');
		$track = addslashes(requestVar('cmtrack') );

		if ($action == 'update')
		{
			$query = "SELECT cm_id FROM " . sql_table('plugin_currentmedia') . " WHERE cm_id = $itemid";
			$result = mysql_query($query);
			if (mysql_num_rows($result) == 1)
			{
				$query = "UPDATE " . sql_table('plugin_currentmedia') . " SET cm_type = $type, cm_asin = '$asin', cm_title = '$title', cm_by = '$by', cm_image = '$image', cm_track = '$track' WHERE cm_id = $itemid";
			}
			else
			{
				$query = "INSERT INTO " . sql_table('plugin_currentmedia') . " VALUES ($itemid, $type, '$asin', '$title', '$by', '$track', '$image')";
			}
			mysql_query($query);
		}
		else if ($action == 'delete')
		{
			$query = "DELETE FROM " . sql_table('plugin_currentmedia') . " WHERE cm_id = $itemid LIMIT 1";
			mysql_query($query);
		}
	}

	/****************************************************************
	This hook is used to handle deleting media data in case the item
	it is attached to is deleted.  Prevents plugin table from having
	extra entries for items that have been deleted.
	*****************************************************************/
	function event_PostDeleteItem($data) {
		$itemid = $data['itemid'];
		$query = "DELETE FROM " . sql_table('plugin_currentmedia') . " WHERE cm_id = $itemid LIMIT 1";
		mysql_query($query);
	}

	/****************************************************************
	This function is used to show the plugin's form on the 'Add Item'
	and 'Edit Item' forms.  Depending whether the user is adding or 
	editing a post - plus whether or not media was previously added -
	is taken into account using javascript and the $nomedia variable.
		Note: zone 0 = menu
			  zone 1 = media html block
	*****************************************************************/
	function _showPluginForm($mode = 'add', $itemid = '') {
		// default assumes no media on the item
		$nomedia = TRUE;

		// get the locale and language options
		$locale = $this->getOption('site');
		$word_currently = $this->getOption('currently');
		$word_listening = $this->getOption('listening');
		$word_watching = $this->getOption('watching');
		$word_reading = $this->getOption('reading');
		$word_playing = $this->getOption('playing');

		// default menu links
		$pluginmenu = '<a href="#" onclick="addItem(); popUp(\'plugins/currentmedia/cm_popup.php?type=0&locale=' . $locale . '\'); return false;">' . $word_watching . '</a> | <a href="#" onclick="addItem(); popUp(\'plugins/currentmedia/cm_popup.php?type=1&locale=' . $locale . '\'); return false;">' . $word_reading . '</a> | <a href="#" onclick="addItem(); popUp(\'plugins/currentmedia/cm_popup.php?type=2&locale=' . $locale . '\'); return false;">' . $word_listening . '</a> | <a href="#" onclick="addItem(); popUp(\'plugins/currentmedia/cm_popup.php?type=4&locale=' . $locale . '\'); return false;">' . $word_playing . '</a>' . "\n";

		// defaults for the two zones
		$zone0 = '<div id="zone0">' . "\n"; // zone 0 will be visible
		$zone1 = '<div id="zone1" style="display: none">' . "\n"; // zone 1 will be hidden

		// set up for the 'Edit Item' form
		if ($mode == 'edit') {
			// different menu links
			$pluginmenu = '<a href="#" onclick="changeItem(); popUp(\'plugins/currentmedia/cm_popup.php?type=0&locale=' . $locale . '\'); return false;">' . $word_watching . '</a> | <a href="#" onclick="changeItem(); popUp(\'plugins/currentmedia/cm_popup.php?type=1&locale=' . $locale . '\'); return false;">' . $word_reading . '</a> | <a href="#" onclick="changeItem(); popUp(\'plugins/currentmedia/cm_popup.php?type=2&locale=' . $locale . '\'); return false;">' . $word_listening . '</a> | <a href="#" onclick="changeItem(); popUp(\'plugins/currentmedia/cm_popup.php?type=4&locale=' . $locale . '\'); return false;">' . $word_playing . '</a>' . "\n";

			// query to see if a media item exists for this post
			$query = "SELECT * FROM " . sql_table('plugin_currentmedia') . " WHERE cm_id ='$itemid'";
			$result = mysql_query($query);

			// if a media item exists for this post
			if (mysql_num_rows($result) == 1)
			{
				$mediadata = $this->_getMediaData($result);					// get the media data
				$zone0 = '<div id="zone0" style="display: none">' . "\n";	// zone 0 will be hidden
				$zone1 = '<div id="zone1">' . "\n";							// zone 1 will be visible
				$nomedia = FALSE;											// we have media, so $nomedia set to false
			}
		}

		// Let's start outputing the plugin form
		echo '<script type="text/javascript" src="plugins/currentmedia/cm.js"> </script>' . "\n\n";
		echo '<h3>Current Media Plugin</h3>' . "\n\n";

		echo $zone0;
		echo $word_currently . ' ' . $pluginmenu;
		echo '</div>' . "\n\n";

		echo $zone1;
		echo '<input type="hidden" id="cmaction" name="cmaction" value="none" />' . "\n";
		echo '<input type="hidden" id="cmtype" name="cmtype" />' . "\n";
		echo '<input type="hidden" id="cmasin" name="cmasin" />' . "\n";
		echo '<input type="hidden" id="cmtitle" name="cmtitle" />' . "\n";
		echo '<input type="hidden" id="cmby" name="cmby" />' . "\n";
		echo '<input type="hidden" id="cmimage" name="cmimage" />' . "\n";

		echo '<span id="htmloutput">' . "\n";
			if (!$nomedia)
			{
				echo '<table border="0" cellspacing="0" cellpadding="2">' . "\n";
				echo '<tr>' . "\n";
				echo '<td valign="top" width="120">' . "\n";
				echo '<img src="' . $mediadata['image'] . '" alt="' . $mediadata['title'] . '" /> <br />' . "\n";
				echo '<a href="#" onclick="hideItem(); return false;">change</a> | <a href="#" onclick="deleteItem(); return false;">delete</a>' . "\n";
				echo '</td>' . "\n";
				echo '<td valign="top">' . "\n";
				echo 'Currently ' . $mediadata['mediaword'] . ': ' . $mediadata['title'] . ' <br />' . "\n";
				echo $mediadata['descword'] . ' ' . $mediadata['by'] . ' <br />' . "\n";
				if ($mediadata['track'])
				{
					echo 'Track Name: ' . $mediadata['track'] . "\n";
				}
				echo '</td>' . "\n";
				echo '</tr>' . "\n";
				echo '</table>' . "\n";
			}
		echo '</span>' . "\n";
		echo '</div>' . "\n\n";
	}

	
	/****************************************************************
	This function is used to put the media data values for a specific
	item into an array and returns the array.
	*****************************************************************/
	function _getMediaData($result) {
		$array['type'] = mysql_result($result, 0, 'cm_type');
		$array['title'] = mysql_result($result, 0, 'cm_title');
		$array['asin'] = mysql_result($result, 0, 'cm_asin');
		$array['by'] = mysql_result($result, 0, 'cm_by');
		$array['track'] = mysql_result($result, 0, 'cm_track');
		$array['image'] = mysql_result($result, 0, 'cm_image');
		$array['descword'] = $this->getOption('by');

		if ($array['type'] == 0 || $array['type'] == 3) { 
			$array['mediaword'] = $this->getOption('watching');
			$array['descword'] = $this->getOption('starring'); 
		}
		else if ($array['type'] == 1) {
			$array['mediaword'] = $this->getOption('reading');
		}
		else if ($array['type'] == 2) {
			$array['mediaword'] = $this->getOption('listening');
		}
		else if ($array['type'] == 4) {
			$array['mediaword'] = $this->getOption('playing');
		}

		return $array;
	}
}
?>