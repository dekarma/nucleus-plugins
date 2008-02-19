<?php
  require_once($DIR_PLUGINS."php-delicious/php-delicious.inc.php");

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
  
  function _addLink($owner, $group, $url, $text, $desc, $comment, $tag, $counter, $xfn) {
    $query = sql_query("SELECT `id` FROM `".sql_table('plug_blogroll_links')."` WHERE `url`=\"$url\"");
    $result = mysql_fetch_assoc($query);
    $id = $result['id'];
    if ($result['id'] != "") return(array(FALSE,"<p>The link <code>$url</code> is a duplicate link. (id $id)"));
    else {
      //Determine the next order number for the new link
      $query = sql_query("SELECT MAX(`order`) AS `order` FROM `".sql_table('plug_blogroll_links')."` WHERE `group`=$group");
      $result = mysql_fetch_assoc($query);
      $order = ++$result['order'];

      //Add the link to the database
      $query = sql_query("INSERT INTO `".sql_table('plug_blogroll_links')."` VALUES (NULL,\"$order\",\"$owner\",\"$group\",\"$url\",\"".htmlspecialchars($text)."\",\"".htmlspecialchars($desc)."\",NOW(),NOW(),\"$counter\",\"".htmlspecialchars($comment)."\",\"".htmlspecialchars($xfn)."\")"); 
      $query = sql_query("SELECT `id` FROM `".sql_table('plug_blogroll_links')."` WHERE `url`=\"$url\"");
      $result = mysql_fetch_assoc($query);
      $id = mysql_insert_id();;

      // Add tag(s) to the database
      $tags = explode(" ", $tag);
      foreach ($tags as $t) {
        if ($t !="") {
          sql_query("INSERT INTO `".sql_table('plug_blogroll_tags')."` VALUE (\"$t\",\"$id\")");
        }
      }

      // add link to del.icio.us 
      $plug = new PluginAdmin('Blogroll');
      if ($plug->plugin->getOption('DelIcioUs') == "yes") {
        $user = $plug->plugin->getOption('DeliciousUser');
        $password = $plug->plugin->getOption('DeliciousPassword');

        if ($desc == "") {
          $d = $comment;
        }
        else {
          $d = $desc;
        }

        if ($user != '' && $password !='') {
                $oPhpDelicious = new PhpDelicious($user, $password);
                $oPhpDelicious->AddPost($url, $text, $d, $tags);
        }
      }

      return(array(TRUE,"<p>Link successfully added. Call it using <code>&lt;%Blogroll(link,".$result['id'].")%&gt;</code></p>"));
    }
  }

  function _delLink($id) {
    //Update link order
    $query = sql_query("SELECT `order`,`url` FROM `".sql_table('plug_blogroll_links')."` WHERE `id`=$id");
    $result = mysql_fetch_assoc($query);
    $order = $result['order'];
    sql_query("UPDATE `".sql_table('plug_blogroll_links')."` SET `order`=(`order`-1) WHERE `order`>$order AND `group`=".$_GET['groupid']);
    //Delete link
    $query = sql_query("DELETE FROM `".sql_table('plug_blogroll_links')."` WHERE `id`=$id");
    if ($query) {
      $query = sql_query("DELETE FROM `".sql_table('plug_blogroll_tags')."` WHERE `id`=$id");
      if ($query) {
        // delete link to del.icio.us 
        $plug = new PluginAdmin('Blogroll');
        if ($plug->plugin->getOption('DelIcioUs') == "yes") {
          $user = $plug->plugin->getOption('DeliciousUser');
          $password = $plug->plugin->getOption('DeliciousPassword');

          if ($user != '' && $password !='') {
                  $oPhpDelicious = new PhpDelicious($user, $password);
                  $oPhpDelicious->DeletePost($result['url']);
          }
        }

        return(array(TRUE,"<p>Link successfully deleted.</p>"));
      }

    }
  }

  function _editLink($id, $url, $text, $desc, $comment, $tag, $counter, $xfn) {
    $query = sql_query("UPDATE `".sql_table('plug_blogroll_links')."` SET `url`=\"$url\", `text`=\"".htmlspecialchars($text)."\", `desc`=\"".htmlspecialchars($desc)."\", `comment`=\"".htmlspecialchars($comment)."\", `counter`=$counter, `xfn`=\"$xfn\" WHERE `id`=$id");
    if ($query) {
      sql_query("DELETE FROM ".sql_table('plug_blogroll_tags')." WHERE `id`=".$id);
      $tags = explode(" ", $tag);
      foreach ($tags as $t) {
        if ($t != "") {
          sql_query("INSERT INTO `".sql_table('plug_blogroll_tags')."` VALUE (\"$t\",\"$id\")");
        }
      }

      if ($query) {
        // update link to del.icio.us 
        $plug = new PluginAdmin('Blogroll');
        if ($plug->plugin->getOption('DelIcioUs') == "yes") {
          $user = $plug->plugin->getOption('DeliciousUser');
          $password = $plug->plugin->getOption('DeliciousPassword');

          if ($desc == "") {
            $d = $comment;
          }
          else {
            $d = $desc;
          }

          if ($user != '' && $password !='') {
                  $oPhpDelicious = new PhpDelicious($user, $password);
                  $oPhpDelicious->AddPost($url, $text, $d, $tags);
          }
        }
        return(array(TRUE,"<p>Link successfully edited.</p>"));
      }
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
        echo "<form name=\"delete\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."?page=".$_GET['page']."&tag=".$_GET['tag']."&groupid=".$_GET['groupid']."\"><input type=\"hidden\" name=\"id\" value=\"$id\" /><input type=\"hidden\" name=\"action\" value=\"dellink\" /><input type=\"submit\" name=\"Submit\" value=\"Confirm Deletion\" /></form>";
        break;
      case "add":
      case "edit":
         global $CONF;
         $tag_array = Array();

         $query = sql_query("SELECT DISTINCT tag FROM " . sql_table('plug_blogroll_tags'));
         while ($row = mysql_fetch_object($query)) {
            if ($row->tag == "") continue;
            $tag_array[] = $row->tag;
         }

         $compl_tags = '';

         foreach ($tag_array as $tag ) {
            $compl_tags = $compl_tags ? $compl_tags . ',' . '"'.$tag.'"' : '"'.$tag.'"';
         }

         echo '<script type="text/javascript">var collection = new Array('.$compl_tags.');</script><script type="text/javascript" src="'.$CONF['AdminURL'].'plugins/blogroll/actb.js"></script><script type="text/javascript" src="'.$CONF['AdminURL'].'plugins/blogroll/common.js"></script><script type="text/javascript" src="'.$CONF['AdminURL'].'plugins/blogroll/xfn_creator.js"></script><style> #tat_table { width:250px; } </style> ';
        echo "<h3 style=\"padding-left: 0px\">";
        if ($type == "add") {
          echo "Add new link</h3>";
          echo "<form name=\"add\"";
          $url = 'http://';
          $text = '';
          $desc = '';
          $counter = 0;
          $comment = '';
          $tag = urlencode($_GET['tag']);
        }
        else {
          echo "Edit link</h3>";
          echo "<form name=\"edit\"";
          $query = sql_query('SELECT * FROM `'.sql_table('plug_blogroll_links').'` WHERE `id`='.$id);
          $result = mysql_fetch_assoc($query);
          $url = $result['url'];
          $text = $result['text'];
          $desc = $result['desc'];
          $comment = $result['comment'];
          $counter = $result['counter'];
	  $xfn = $result['xfn'];

          // grab and construct tag.....
          $tag = '';
          $result = sql_query('SELECT `tag` FROM `'.sql_table('plug_blogroll_tags').'` WHERE `id`='.$id);
          while ($t = mysql_fetch_object($result)) {
            $tag .= $t->tag . " ";
          }

        }
        echo " method=\"post\" action=\"\">";
        echo "<table><tbody>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>URL</td><td><input name=\"url\" type=\"text\" id=\"url\" value=\"$url\" size=\"50\" maxlength=\"255\"></td></tr>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>Title</td><td><input name=\"text\" type=\"text\" id=\"text\" value=\"$text\" size=\"50\" maxlength=\"255\"> (optional)</td></tr>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>Description</td><td><input name=\"desc\" type=\"text\" id=\"desc\" value=\"$desc\" size=\"50\" maxlength=\"255\"> (optional)</tr></td>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>Comment</td><td><input name=\"comment\" type=\"text\" id=\"comment\" value=\"$comment\" size=\"50\" maxlength=\"1024\"> (optional)</tr></td>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>Tag</td><td><input name=\"tag\" type=\"text\" id=\"tag\" value=\"$tag\" size=\"50\" maxlength=\"255\" autocomplete=\"off\"> (optional)</tr></td><script>actb(document.getElementById('tag'), collection)</script>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>XFN (see <a href=\"http://gmpg.org/xfn/11\">here</a>)</td><td><input name=\"xfn\" type=\"text\" id=\"xfn\" value=\"$xfn\" size=\"50\" maxlength=\"255\" autocomplete=\"off\"> (optional)<br/>";
	?><div id="xfnR"> </div><?php
	include("xfn_creator.inc");
	echo "</tr></td>";
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
    $linkToList = 10;
    $listGet = $linkToList +1;
    $zeroTagLink = 0;

    $page = intRequestVar('offset');

    if ($page == 0) {
      $offset = 0;
    } else {
      $offset = $page*$linkToList;
    }

    $query = sql_query("SELECT `desc` FROM `".sql_table('plug_blogroll_groups')."` WHERE `id`=$group");
    $result = mysql_fetch_assoc($query);
    $groupname = $result['desc'];

    $query = sql_query("SELECT * FROM `".sql_table('plug_blogroll_links')."` WHERE `group`=$group AND `owner`=$owner ORDER BY `order` LIMIT $offset,$listGet");

    $rowcount = mysql_num_rows($query);

    echo "<h3 style=\"padding-left: 0px\">Manage links &gt; $groupname</h3>";

    if ($page > 0) {
      $p = $page -1;
      $prelink = $_SERVER['PHP_SELF']."?page=managelinks&groupid=".$group."&offset=".$p;
      echo " <a href=\"".$prelink."\">[prev]</a>";
    }

    if ($rowcount > $linkToList) {
      $p = $page +1;
      $nextlink = $_SERVER['PHP_SELF']."?page=managelinks&groupid=".$group."&offset=".$p;
      echo " <a href=\"".$nextlink."\">[next]</a>";
    }

    echo '<table><thead><tr><th>ID</th><th>URL/Title/Description</th><th>Comment</th><th>Tag</th><th>Created/Clicked</th><th>Counter</th><th>Action</th></tr></thead><tbody>';
    for ($i = 0; $i < $linkToList; $i++) {
      if (!($link = mysql_fetch_assoc($query))) break;
      if (strlen($link['url']) > 18) { $url = substr($link['url'],0,10).'...'; }
      else { $url = $link['url']; }
      $result = sql_query("SELECT `tag` FROM `".sql_table('plug_blogroll_tags')."` WHERE `id`=".$link['id']);
      $tag = "";
      if (mysql_num_rows($result) == 0) {
        $zeroTagLink++;
        $tag = "none";
      }

      while ($t = mysql_fetch_object($result)) {
        $tag .= "<a href=\"" . $_SERVER['PHP_SELF']. "?page=managetag&tag=" . $t->tag . "\">" . $t->tag . "</a> ";
      }
      echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
      echo '<td>'.$link['id'].'</td><td><a href="'.$link['url'].'" title="'.$link['url'].'"target="_blank"><code>'.$url.'</code></a><br/>'
      .$link['text'].'<br/><br/>'.$link['desc'].'</td><td>'.$link['comment'].'</td><td>'.$tag.'</td><td>'._formatDate($link['created']);

      if ($link['counter'] > 0) {
        echo " (last clicked "._formatDate($link['clicked']).")"; 
      }

      echo '</td><td>'.$link['counter'].'</td><td>';

      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=$group&action=changegroup&id=".$link['id']."\" title=\"Move this link to another group\">change group</a><br />";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=$group&action=moveup&id=".$link['id']."&offset=".$page."\" title=\"Move this link up\">move up</a><br />";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=$group&action=movedown&id=".$link['id']."&offset=".$page."\" title=\"Move this link down\">move down</a><br />";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=$group&action=editlink&id=".$link['id']."&offset=".$page."\" title=\"Edit this link\">edit</a><br />";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managelinks&groupid=$group&action=dellink&id=".$link['id']."\" title=\"Delete this link\">delete</a>";
      echo '</td></tr>';
    }
    echo '</tbody></table>';
    echo "Number of links with no tag: " . $zeroTagLink . "<br />";

    if ($page > 0) {
      $p = $page -1;
      $prelink = $_SERVER['PHP_SELF']."?page=managelinks&groupid=".$group."&offset=".$p;
      echo " <a href=\"".$prelink."\">[prev]</a>";
    }

    if ($rowcount > $linkToList) {
      $p = $page +1;
      $nextlink = $_SERVER['PHP_SELF']."?page=managelinks&groupid=".$group."&offset=".$p;
      echo " <a href=\"".$nextlink."\">[next]</a>";
    }
  }
  
  if ($_POST['action'] != "") {
    switch ($_POST['action']) {
      case "changegroup": 
			  $error = _changeGroup($_POST['id'], $_POST['newgroup']);
				  echo $error[1];
                                if ($_GET['page'] != "managetag") {
				  _listLinks($_POST['newgroup'], $memberid);
				  _makeLinkForm("add", ""); 
                                } else {
                                  _listTagLinks(urlencode($_GET['tag']), $memberid);
                                }
				break;
      case "addlink": 
			  $error = _addLink($memberid, $_POST['group'], $_POST['url'], $_POST['text'], $_POST['desc'], $_POST['comment'], $_POST['tag'], $_POST['counter'], $_POST['xfn']);
                                  echo $error[1];
                                if ($_GET['page'] != "managetag") {
				  _listLinks($_GET['groupid'], $memberid);
				  _makeLinkForm("add", "");
                                } else {
                                  _listTagLinks(urlencode($_GET['tag']), $memberid);
                                }
				break;
      case "dellink": 
			  $error = _delLink($_POST['id']);
                                  echo $error[1];
                                if ($_GET['page'] != "managetag") {
				  _listLinks($_GET['groupid'], $memberid);
				  _makeLinkForm("add", "");
                                } else {
                                  _listTagLinks(urlencode($_GET['tag']), $memberid);
                                }
				break;
      case "editlink":
			  $error = _editLink($_POST['id'], $_POST['url'], $_POST['text'], $_POST['desc'], $_POST['comment'], $_POST['tag'], $_POST['counter'], $_POST['xfn']);

			        if ($_GET['redirect'] != '') {
				  header('Location: ' . $_GET['redirect']);
				}

				echo $error[1];
                                if ($_GET['page'] != "managetag") {
			  	  _listLinks($_GET['groupid'], $memberid);
				  _makeLinkForm("add", "");
                                } else {
                                  _listTagLinks(urlencode($_GET['tag']), $memberid);
                                }
  			        break;
    }
  }

  elseif ($_GET['action'] != "") {
    switch ($_GET['action']) {
      case "changegroup": 
        _makeLinkForm("changegroup", $_GET['id']); 
        break;
      case "moveup": 
        _changeOrder($_GET['id'], "up"); 
	_listLinks($_GET['groupid'], $memberid);
        _makeLinkForm("add", ""); 
        break;
      case "movedown": 
        _changeOrder($_GET['id'], "down"); 
	_listLinks($_GET['groupid'], $memberid);
        _makeLinkForm("add", ""); 
        break;
      case "dellink":
        _makeLinkForm("del", $_GET['id']); 
        break;
      case "editlink": 
        _makeLinkForm("edit", $_GET['id']);
        break;
    }
  }
  
  elseif ($_GET['page'] == "managelinks") {
    _listLinks($_GET['groupid'], $memberid);
    _makeLinkForm("add", "");
  }

  /*
  Add link need group... can't really work like this
  elseif ($_GET['page'] == "managetag") {
      _makeLinkForm("add", "");
    }
    */
?>
