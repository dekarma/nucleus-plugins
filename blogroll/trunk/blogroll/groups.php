<?php
  function _addGroup($owner, $name, $desc) {
    $query = sql_query("SELECT `id` FROM `".sql_table('plug_blogroll_groups')."` WHERE `name`=\"$name\"");
    $result = mysql_fetch_assoc($query);
    if ($result['id'] != "") echo "<p>A group by that name already exists.</p>";
    else {
      //Add the group to the database
      $query = sql_query("INSERT INTO `".sql_table('plug_blogroll_groups')."` VALUES (NULL,\"$owner\",\"$name\",\"$desc\")");
      if ($query) echo "<p>Group successfully added. Call it using <code>&lt;%Blogroll(group,$name)%&gt;</code></p>";
    }
  }
  
  function _delGroup($id) {
    // Delete group
    $query1 = sql_query("DELETE FROM `".sql_table('plug_blogroll_groups')."` WHERE `id`=$id");
		// Delete all links from group
		$query2 = sql_query("DELETE FROM `".sql_table('plug_blogroll_links')."` WHERE `group`=$id");
    if ($query1 && $query2) echo "<p>Group successfully deleted.</p>";
		else echo "<p>There was an error deleting the group.</p>";
  }
  
  function _editGroup($id, $name, $desc) {
    $query = sql_query("UPDATE `".sql_table('plug_blogroll_groups')."` SET `name`=\"$name\", `desc`=\"$desc\" WHERE `id`=$id");
    if ($query) {
      echo "<p>Link successfully edited.</p>";
    }
  }
  
  function _makeGroupForm($type, $id) {
    if ($type == "del") {
      echo "<h3 style=\"padding-left: 0px\">Delete group</h3>";
      echo "<p>Do you really want to delete this group? Deleting this group will also delete <b>all links</b> in that group.</p>";
      echo "<form name=\"delete\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\"><input type=\"hidden\" name=\"id\" value=\"$id\" /><input type=\"hidden\" name=\"action\" value=\"delgroup\" /><input type=\"submit\" name=\"Submit\" value=\"Confirm Deletion\" /></form>";
    }
    elseif ($type == "add" || $type == "edit") {
      if ($type == "add") {
        echo '<h3 style="padding-left: 0px">Add new group</h3>';
        echo '<form name="edit" method="post" action="'.$_SERVER['PHP_SELF'].'">';
        $url = 'http://';
        $text = '';
        $title = '';
        $counter = 0;
      }
      else {
        echo '<h3 style="padding-left: 0px">Edit group</h3>';
        echo '<form name="edit" method="post" action="'.$_SERVER['PHP_SELF'].'">';
        $query = sql_query('SELECT `name`, `desc` FROM `'.sql_table('plug_blogroll_groups').'` WHERE `id`='.$id);
        $result = mysql_fetch_assoc($query);
        $name = $result['name'];
        $desc = $result['desc'];
      }
      echo "<table><tbody>";
      echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
      echo "<td>Name</td><td><input name=\"name\" type=\"text\" id=\"name\" value=\"$name\" size=\"30\" maxlength=\"30\"></td></tr>";
      echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
      echo "<td>Description</td><td><input name=\"desc\" type=\"text\" id=\"desc\" value=\"$desc\" size=\"50\" maxlength=\"255\"> (optional)</td></tr>";
      echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'><td>&nbsp;</td><td>";
      echo "<input type=\"submit\" name=\"Submit\" value=\"";
      if ($type == "edit") echo "Edit";
      else echo "Add";
      echo " this group\" /></td></tbody></table>";
      if ($type == "edit") echo "<input type=\"hidden\" name=\"id\" value=\"".$_GET['id']."\" /><input type=\"hidden\" name=\"action\" value=\"editgroup\" />";
      else echo "<input type=\"hidden\" name=\"action\" value=\"addgroup\" />";
      echo "</form>";
    }
  }
  
  function _listGroups($owner) {
    echo "<h3 style=\"padding-left: 0px\">Manage groups</h3>";
    echo "<table><thead><tr><th>Name</th><th>Description</th><th>Num of Links</th><th>Action</th></tr></thead><tbody>";
    foreach (_getGroups($owner) as $group) {
      echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'><td>".$group['name']."</td><td>".$group['desc']."</td><td>".$group['count']."</td><td>";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=".$group['id']."\" title=\"Manage the links in this group\">manage links</a>";
      echo "<br /><a href=\"".$_SERVER['PHP_SELF']."?action=editgroup&id=".$group['id']."\" title=\"Edit this group\">edit</a>";
      echo "<br /><a href=\"".$_SERVER['PHP_SELF']."?action=delgroup&id=".$group['id']."\" title=\"Delete this group\">delete</a>";
      echo "</td></tr>";
    }
    echo "</tbody></table>";
  }
	
  function _getGroups($owner) {
    $output = array();
    $groups = sql_query("SELECT * FROM `".sql_table('plug_blogroll_groups')."` WHERE `owner`=\"$owner\" ORDER BY `name`");
    while ($group = mysql_fetch_assoc($groups)) {
      $query = sql_query("SELECT COUNT(`id`) AS `count` FROM `".sql_table('plug_blogroll_links')."` WHERE `group`=".$group['id']);
      $count = mysql_fetch_assoc($query);
      $group = array_merge($group, $count);
      array_push($output, $group);
    }

    return($output);
  }
  
  if ($_POST['action'] != "") {
    switch ($_POST['action']) {
      case "addgroup": _addGroup($memberid, $_POST['name'], $_POST['desc']); break;
      case "delgroup": _delGroup($_POST['id']); _listGroups($memberid); _makeGroupForm("add", ""); break;
      case "editgroup": _editGroup($_POST['id'], $_POST['name'], $_POST['desc']); _listGroups($memberid); _makeGroupForm("add", ""); break;
    }
  }

  elseif ($_GET['action'] != "") {
    switch ($_GET['action']) {
      case "delgroup": _makeGroupForm("del", $_GET['id']); break;
      case "editgroup": _makeGroupForm("edit", $_GET['id']); break;
    }
  }
  
  elseif ($_GET['page'] != "managelinks" && $_GET['page'] != "managetag") {
    _listGroups($memberid);
    _makeGroupForm("add", "");
  }
?>
