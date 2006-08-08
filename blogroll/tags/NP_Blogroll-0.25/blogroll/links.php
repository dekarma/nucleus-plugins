<?php
  function _changeGroup($id, $newgroup) {
    //Determine the next order number for the link in the new group
    $result = mysql_fetch_assoc(sql_query("SELECT MAX(`order`) AS `order` FROM `".sql_table('plug_blogroll_links')."` WHERE `group`=$newgroup"));
    $order = $result['order'];
    $neworder = $order+1;

    //Update link order
    $result = mysql_fetch_assoc(sql_query("SELECT `group`,`order` FROM `".sql_table('plug_blogroll_links')."` WHERE `id`=$id"));
    $oldgroup = $result['group'];
    sql_query("UPDATE `".sql_table('plug_blogroll_links')."` SET `order`=(`order`-1) WHERE `order`>".$result['order']." AND `group`=$oldgroup");
    
    //Change link's group
    $query = sql_query("UPDATE `".sql_table('plug_blogroll_links')."` SET `group`=$newgroup, `order`=$neworder WHERE `id`=$id");
    $result = mysql_fetch_assoc(sql_query("SELECT `name` FROM `".sql_table('plug_blogroll_groups')."` WHERE `id`=$newgroup"));
    if ($query) return(array(TRUE,"<p>Link has been moved to the group \"".$result['name']."\""));
  }
  
  function _addLink($owner, $group, $url, $text, $title, $counter) {
    $query = sql_query("SELECT `id` FROM `".sql_table('plug_blogroll_links')."` WHERE `url`=\"$url\"");
    $result = mysql_fetch_assoc($query);
    if ($result['id'] != "") return(array(FALSE,"<p>The link <code>$url</code> is a duplicate link."));
    else {
      //Determine the next order number for the new link
      $query = sql_query("SELECT MAX(`order`) AS `order` FROM `".sql_table('plug_blogroll_links')."` WHERE `group`=$group");
      $result = mysql_fetch_assoc($query);
      $order = ++$result['order'];

      //Add the link to the database
      $query = sql_query("INSERT INTO `".sql_table('plug_blogroll_links')."` VALUES (\"\",\"$order\",\"$owner\",\"$group\",\"$url\",\"$text\",\"$title\",NOW(),NOW(),\"$counter\")");
      $query = sql_query("SELECT `id` FROM `".sql_table('plug_blogroll_links')."` WHERE `url`=\"$url\"");
      $result = mysql_fetch_assoc($query);
      return(array(TRUE,"<p>Link successfully added. Call it using <code>&lt;%Blogroll(link,".$result['id'].")%&gt;</code></p>"));
    }
  }

  function _delLink($id) {
    //Update link order
    $query = sql_query("SELECT `order` FROM `".sql_table('plug_blogroll_links')."` WHERE `id`=$id");
    $result = mysql_fetch_assoc($query);
    $order = $result['order'];
    sql_query("UPDATE `".sql_table('plug_blogroll_links')."` SET `order`=(`order`-1) WHERE `order`>$order AND `group`=".$_GET['groupid']);
    //Delete link
    $query = sql_query("DELETE FROM `".sql_table('plug_blogroll_links')."` WHERE `id`=$id");
    if ($query) return(array(TRUE,"<p>Link successfully deleted.</p>"));
  }

  function _editLink($id, $url, $text, $title, $counter) {
    $query = sql_query("UPDATE `".sql_table('plug_blogroll_links')."` SET `url`=\"$url\", `text`=\"$text\", `title`=\"$title\", `counter`=$counter WHERE `id`=$id");
    if ($query) {
      return(array(TRUE,"<p>Link successfully edited.</p>"));
    }
  }
  
  function _changeOrder($id, $direction) {
    $query = sql_query("SELECT `order`, `group` FROM `".sql_table('plug_blogroll_links')."` WHERE `id`=$id");
    $result = mysql_fetch_assoc($query);
    $oldOrder = $result['order'];

    switch ($direction) {
      case "up":
        if ($oldOrder == 1) {
          return(array(FALSE,"<p>You can't move that link any higher.</p>"));
          return;
        }
        $newOrder = $oldOrder-1; break;
      case "down":
        $newOrder = $oldOrder+1; break;
    }
    
    $query = sql_query("SELECT `id` FROM `".sql_table('plug_blogroll_links')."` WHERE `group`=".$result['group']." AND `order`=$newOrder");
    $result = mysql_fetch_assoc($query);
    $oldId = $result['id'];
    
    if ($oldId == "") {
      return(array(FALSE,"<p>You can't move that link any lower.</p>"));
      return;
    }
    
    sql_query("UPDATE `".sql_table('plug_blogroll_links')."` SET `order`=$newOrder WHERE `id`=$id");
    sql_query("UPDATE `".sql_table('plug_blogroll_links')."` SET `order`=$oldOrder WHERE `id`=$oldId");
    return(array(TRUE,"<p>Link order changed.</p>"));
  }

  function _makeLinkForm($type, $id) {
    switch ($type) {
      case "changegroup":
        echo "<h3 style=\"padding-left: 0px\">Change group</h3>";
        echo "<form name=\"changegroup\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=".$_GET['groupid']."\"><input type=\"hidden\" name=\"id\" value=\"$id\" /><input type=\"hidden\" name=\"action\" value=\"chggroup\" />";
        $result = mysql_fetch_assoc(sql_query("SELECT `url`,`owner`,`text`,`group` FROM `".sql_table('plug_blogroll_links')."` WHERE `id`=$id"));
        $name = $result['text'] == '' ? $result['url'] : $result ['text'];
        $query = sql_query("SELECT `id`,`name` FROM `".sql_table('plug_blogroll_groups')."` WHERE `owner`=".$result['owner']." ORDER BY `name`");
        echo "<table><tbody><tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'><td>Move ".$name." to which group?</td><td>";
        echo "<select name=\"newgroup\" />";
        while ($group = mysql_fetch_assoc($query)) {
          echo "<option value=\"".$group['id']."\"";
          if ($group['id'] == $result['group']) echo " SELECTED=\"\"";
          echo ">".$group['name']."</option>";
        }
        echo "</select></td></tr><tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'><td>&nbsp</td><td><input type=\"submit\" name=\"Submit\" value=\"Change group\"><input type=\"hidden\" name=\"action\" value=\"changegroup\" /></td></tr></table></form>";
        break;
      case "del":
        echo "<h3 style=\"padding-left: 0px\">Delete link</h3>";
        echo "<p>Do you really want to delete this link?</p>";
        echo "<form name=\"delete\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=".$_GET['groupid']."\"><input type=\"hidden\" name=\"id\" value=\"$id\" /><input type=\"hidden\" name=\"action\" value=\"dellink\" /><input type=\"submit\" name=\"Submit\" value=\"Confirm Deletion\" /></form>";
				break;
      case "add":
      case "edit":
        echo "<h3 style=\"padding-left: 0px\">";
        if ($type == "add") {
          echo "Add new link</h3>";
          echo "<form name=\"add\"";
          $url = 'http://';
          $text = '';
          $title = '';
          $counter = 0;
        }
        else {
          echo "Edit link</h3>";
          echo "<form name=\"edit\"";
          $query = sql_query('SELECT `url`, `text`, `title`, `counter` FROM `'.sql_table('plug_blogroll_links').'` WHERE `id`='.$id);
          $result = mysql_fetch_assoc($query);
          $url = $result['url'];
          $text = stripslashes($result['text']);
          $title = stripslashes($result['title']);
          $counter = $result['counter'];
        }
        echo " method=\"post\" action=\"\">";
        echo "<table><tbody>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>URL</td><td><input name=\"url\" type=\"text\" id=\"url\" value=\"$url\" size=\"50\" maxlength=\"255\"></td></tr>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>Text</td><td><input name=\"text\" type=\"text\" id=\"text\" value=\"$text\" size=\"50\" maxlength=\"255\"> (optional)</td></tr>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>Description</td><td><input name=\"title\" type=\"text\" id=\"title\" value=\"$title\" size=\"50\" maxlength=\"255\"> (optional)</tr></td>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>Counter</td><td><input name=\"counter\" type=\"text\" id=\"counter\" value=\"$counter\" value=\"0\" size=\"5\" maxlength=\"10\"></td></tr>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'><td>&nbsp;</td><td>";
        echo "<input type=\"submit\" name=\"Submit\" value=\"";
        if ($type == "edit") echo "Edit";
        else echo "Add";
        echo " this link\" /></td></tbody></table>";
        if ($type == "edit") echo "<input type=\"hidden\" name=\"id\" value=\"".$_GET['id']."\" /><input type=\"hidden\" name=\"action\" value=\"editlink\" />";
        else echo "<input type=\"hidden\" name=\"group\" value=\"".$_GET['groupid']."\" /><input type=\"hidden\" name=\"action\" value=\"addlink\" />";
        echo "</form>";
        break;
      }
  }
  function _listLinks ($group, $owner) {
    $query = sql_query("SELECT * FROM `".sql_table('plug_blogroll_links')."` WHERE `group`=$group AND `owner`=$owner ORDER BY `order`");
    echo "<h3 style=\"padding-left: 0px\">Manage links</h3>";
    echo '<table><thead><tr><th>ID</th><th>URL/Text</th><th>Description</th><th>Date Created</th><th>Last clicked</th><th>Counter</th><th>Action</th></tr></thead><tbody>';
    while ($link = mysql_fetch_assoc($query)) {
      if (strlen($link['url']) > 35) { $url = substr($link['url'],0,12).'...'.substr($link['url'],-12); }
      else { $url = $link['url']; }
      echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
      echo '<td>'.$link['id'].'</td><td><a href="'.$link['url'].'" title="'.$link['url'].'"target="_blank"><code>'.$url.'</code></a><br />'.$link['text'].'</td><td>'.$link['title'].'</td><td>'._formatDate($link['created']).'</td><td>';
      if ($link['counter'] == 0) { echo '&nbsp;'; }
      else { echo _formatDate($link['clicked']); }
      echo '</td><td>'.$link['counter'].'</td><td>';
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=$group&action=changegroup&id=".$link['id']."\" title=\"Move this link to another group\">change group</a><br />";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=$group&action=moveup&id=".$link['id']."\" title=\"Move this link up\">move up</a><br />";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=$group&action=movedown&id=".$link['id']."\" title=\"Move this link down\">move down</a><br />";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=$group&action=editlink&id=".$link['id']."\" title=\"Edit this link\">edit</a><br />";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=$group&action=dellink&id=".$link['id']."\" title=\"Delete this link\">delete</a>";
      echo '</td></tr>';
    }
    echo '</tbody></table>';
  }
  
  if ($_POST['action'] != "") {
    switch ($_POST['action']) {
      case "changegroup": 
			  $error = _changeGroup($_POST['id'], $_POST['newgroup']);
				echo $error[1];
				_listLinks($_POST['newgroup'], $memberid);
				_makeLinkForm("add", ""); 
				break;
      case "addlink": 
			  $error = _addLink($memberid, $_POST['group'], $_POST['url'], $_POST['text'], $_POST['title'], $_POST['counter']);
				echo $error[1];
				_listLinks($_GET['groupid'], $memberid);
				_makeLinkForm("add", "");
				break;
      case "dellink": 
			  $error = _delLink($_POST['id']);
				echo $error[1];
				_listLinks($_GET['groupid'], $memberid);
				_makeLinkForm("add", "");
				break;
      case "editlink":
			  $error = _editLink($_POST['id'], $_POST['url'], $_POST['text'], $_POST['title'], $_POST['counter']);
				echo $error[1];
				_listLinks($_GET['groupid'], $memberid);
				_makeLinkForm("add", "");
  			break;
    }
  }

  elseif ($_GET['action'] != "") {
    switch ($_GET['action']) {
      case "changegroup": _makeLinkForm("changegroup", $_GET['id']); break;
      case "moveup": _changeOrder($_GET['id'], "up"); _listLinks($_GET['groupid'], $memberid); _makeLinkForm("add", ""); break;
      case "movedown": _changeOrder($_GET['id'], "down"); _listLinks($_GET['groupid'], $memberid); _makeLinkForm("add", ""); break;
      case "dellink": _makeLinkForm("del", $_GET['id']); break;
      case "editlink": _makeLinkForm("edit", $_GET['id']); break;
    }
  }
  
  elseif ($_GET['page'] == "managelinks") {
    _listLinks($_GET['groupid'], $memberid);
    _makeLinkForm("add", "");
  }

?>
