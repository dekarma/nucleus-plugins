<?php
/*
   History:
     v0.1 - initial release
     v0.2 - remove extra <br/> on edit when linebreak-br conversion turn on
     v0.2a - uses sql_query
     v0.3 - fix backup failure
     v0.3a - fix missing ; in edit item

   Known Issue:
     - This plugin make a query to the database on add/edit
     - Bookmarklet Item preview does not work with HTMLized post
*/
class NP_BROverride extends NucleusPlugin {

	function getName() { return 'BROverride'; }
	function getAuthor()  { return 'Edmond Hui (admun)'; }
	function getURL() { return 'http://forum.nucleuscms.org/viewtopic.php?t=3974'; }
	function getVersion() { return 'v0.3a'; }
	function getDescription() {
		return 'This plugin provides automatic linebreak-to-br conversion with a per item override. This is useful for making a HTMLized post that contains ol, ul, li and etc';
	}

	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getEventList() {
		return array('AddItemFormExtras', 'PreAddItem', 'PostAddItem', 'EditItemFormExtras', 'PreUpdateItem', 'PrepareItemForEdit', 'PostDeleteItem');
	}

	function getTableList() {
		return array(sql_table('plugin_br_override'));
	}

	function install() {
		$query="CREATE TABLE IF NOT EXISTS ".sql_table('plugin_br_override')." (itemid int(11) NOT NULL)";
		sql_query($query);
	}

	function unInstall() {
		sql_query('DROP TABLE ' . sql_table('plugin_br_override'));
	}

	/*******************************************************************
		Add post action
	*******************************************************************/
	function event_AddItemFormExtras($data) {
	?>
		<h3>Auto linebreak-BR conversion</h3>
		<p>
		<input type="checkbox" name="br_flag" <?php if (true) echo "checked=\"checked\""; ?>/>Convert linebreak to &lt;br&gt;
		</p>
	<?php
	}

	function event_PreAddItem($data) {
		$flag = requestVar('br_flag');
		if ($flag) {
			$data['body'] = addBreaks($data['body']);
			$data['more'] = addBreaks($data['more']);
			$this->add_breaks_added = true;
		}
		else {
			$this->add_breaks_added = false;
		}
	}

	function event_PostAddItem($data) {
		if ($this->add_breaks_added == true) {
			$query = "INSERT INTO ".sql_table('plugin_br_override')." VALUES ('".$data['itemid']."')";
			sql_query($query);
		}
	}

	/*******************************************************************
		Edit post action
	*******************************************************************/
	function event_PrepareItemForEdit($data) {
		$query = "SELECT * FROM ".sql_table('plugin_br_override')." WHERE itemid='".$data['item']['itemid']."'";
		$res = sql_query($query);
		if (mysql_num_rows($res) > 0) {
			$data['item']['body'] = removeBreaks($data['item']['body']);
			$data['item']['more'] = removeBreaks($data['item']['more']);
			$this->edit_br_mode = true;
		}
		else {
			$this->edit_br_mode = false;
		}
	}

	function event_EditItemFormExtras($data) {
	?>
		<h3>Auto linebreak-BR conversion</h3>
		<p>
		<input type="checkbox" name="br_flag" 
	<?php
		if ($this->edit_br_mode == true) {
			echo " checked=\"checked\"";
		}
	?>
		/>Convert linebreak to &lt;br&gt; (will also remove extra existing &lt;br&gt;)
		</p>
	<?php
	}

	function event_PreUpdateItem($data) {

		// This is not the best way to do it, but I can't use edit_br_mode for some reason..... should re-visit
		// here.
		$query = "SELECT * FROM ".sql_table('plugin_br_override')." WHERE itemid='".$data['itemid']."'";
		$res = sql_query($query);
		if (mysql_num_rows($res) == 0) {
			$br_ed = true;
		}
		else {
			$br_ed = false;
		}

		$flag = requestVar('br_flag');
		if ($flag) {
			// replace extra <br />
			$data['body'] = eregi_replace("<br ?/>","",$data['body']);
			$data['more'] = eregi_replace("<br ?/>","",$data['more']);
			$data['body'] = addBreaks($data['body']);
			$data['more'] = addBreaks($data['more']);

			if ($br_ed) {
				$query = "INSERT INTO ".sql_table('plugin_br_override')." VALUES ('".$data['itemid']."')";
				sql_query($query);
			}
		}
		else {
			if (!$br_ed) {
				$query = "DELETE FROM ".sql_table('plugin_br_override')." WHERE itemid='".$data['itemid']."'";
				sql_query($query);
			}
		}

	}

	/*******************************************************************
		Delete post action
	*******************************************************************/
	function event_PostDeleteItem($data) {
		$query = "DELETE FROM ".sql_table('plugin_br_override')." WHERE itemid='".$data['itemid']."'";
		sql_query($query);
	}

}

?>
