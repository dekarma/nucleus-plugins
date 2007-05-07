<?php
  require_once($DIR_PLUGINS."php-delicious/php-delicious.inc.php");

  function _listTagLinks ($tag, $owner) {
    if (function_exists('mb_convert_encoding')) {
      $tag = mb_convert_encoding($tag, _CHARSET, _CHARSET);
      $tag = rawurldecode($tag);
    }
    else {
      // This will not work for UTF-8 tag....  . not something 
      // we can fix unless we bundle mb_convert_encoding()
      $tag = urlencode($tag);
    }

    $query = sql_query("SELECT * FROM `".sql_table('plug_blogroll_tags')."` AS t, `".sql_table('plug_blogroll_links')."` AS l WHERE t.tag=\"".$tag."\" AND t.id=l.id AND l.owner=".$owner);
    echo "<h3 style=\"padding-left: 0px\">Manage links in tag $tag</h3>";
    echo '<table><thead><tr><th>ID</th><th>URL/Title</th><th>Description</th><th>Comment</th><th>Tag</th><th>Date Created</th><th>Last clicked</th><th>Counter</th><th>Action</th></tr></thead><tbody>';
    while ($link = mysql_fetch_assoc($query)) {
      if (strlen($link['url']) > 18) { $url = substr($link['url'],0,10).'...'; }
      else { $url = $link['url']; }
      $result = sql_query("SELECT `tag` FROM `".sql_table('plug_blogroll_tags')."` WHERE `id`=".$link['id']);
      $tags = "";
      while ($t = mysql_fetch_object($result)) {
        $tags .= "<a href=\"" . $_SERVER['PHP_SELF']. "?page=managetag&tag=" . $t->tag . "\">" . $t->tag . "</a> ";
      }
      echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
      echo '<td>'.$link['id'].'</td><td><a href="'.$link['url'].'" title="'.$link['url'].'"target="_blank"><code>'.$url.'</code></a><br
      />'.$link['text'].'</td><td>'.$link['desc'].'</td><td>'.$link['comment'].'</td><td>'.$tags.'</td><td>'._formatDate($link['created']).'</td><td>';
      if ($link['counter'] == 0) { echo '&nbsp;'; }
      else { echo _formatDate($link['clicked']); }
      echo '</td><td>'.$link['counter'].'</td><td>';
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managetag&tag=$tag&action=changegroup&id=".$link['id']."\" title=\"Move this link to another group\">change group</a><br />";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managetag&tag=$tag$group&action=editlink&id=".$link['id']."\" title=\"Edit this link\">edit</a><br />";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managetag&tag=$tag$group&action=dellink&id=".$link['id']."&groupid=".$link['group']."\" title=\"Delete this link\">delete</a>";
      echo '</td></tr>';
    }
    echo '</tbody></table>';
  }

  function _listTags($owner) {
    $result = sql_query("SELECT DISTINCT t.tag FROM ".sql_table('plug_blogroll_groups')." AS g, ".sql_table('plug_blogroll_links')." AS l, ".sql_table('plug_blogroll_tags')." AS t WHERE g.owner=".$owner." AND g.id=l.group and t.id=l.id ORDER BY t.tag");
    echo "<h3 style=\"padding-left: 0px\">Manage Tags</h3>";
    while ($row = mysql_fetch_object($result)) {
       echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managetag&tag=".$row->tag."\">".$row->tag."</a> ";
    }

  }
	
  if ($_GET['action'] == "deltagconf" && $_GET['page'] == "managetag") {
    echo "<h3 style=\"padding-left: 0px\">Delete tag</h3>";
        echo "<p>Do you really want to delete tag " . $_GET['tag'] . "?</p>";
        echo "<form name=\"deltag\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."?page=managetag"."&tag=".$_GET['tag']."\">
        <input type=\"hidden\" name=\"action\" value=\"deltag\" />
        <input type=\"submit\" name=\"Submit\" value=\"Confirm Deletion\" />
        </form>";
  }

  elseif ($_POST['action'] == "deltag") {
    if (function_exists('mb_convert_encoding')) {
      $tag = mb_convert_encoding(str_replace(' ','+',$_GET['tag']), _CHARSET, _CHARSET);
      $tag = rawurldecode($tag);
    }
    else {
      // This will not work for UTF-8 tag....  . not something 
      // we can fix unless we bundle mb_convert_encoding()
      $tag = urlencode(str_replace(' ','+',$_GET['tag']));
    }

    sql_query("DELETE FROM " . sql_table('plug_blogroll_tags') . " WHERE tag='" . $tag . "'");

    // humm how do I delete a tag from del.icio.us??
    $plug = new PluginAdmin('Blogroll');
    if ($plug->plugin->getOption('DelIcioUs') == "yes") {
      $user = $plug->plugin->getOption('DeliciousUser');
      $password = $plug->plugin->getOption('DeliciousPassword');

      if ($user != '' && $password !='') {
              $oPhpDelicious = new PhpDelicious($user, $password);
              $oPhpDelicious->RenameTag($tag, 'deleted');;
      }
    }

    echo "Tag deleted";
  }

  elseif ($_POST['action'] == "rentag") {
    if (function_exists('mb_convert_encoding')) {
      $tag = mb_convert_encoding(str_replace(' ','+',$_GET['tag']), _CHARSET, _CHARSET);
      $tag = rawurldecode($tag);
      $tagto = mb_convert_encoding(str_replace(' ','+',$_POST['tagto']), _CHARSET, _CHARSET);
      $tagto = rawurldecode($tagto);
    }
    else {
      // This will not work for UTF-8 tag....  . not something 
      // we can fix unless we bundle mb_convert_encoding()
      $tag = urlencode(str_replace(' ','+',$_GET['tag']));
      $tagto = urlencode(str_replace(' ','+',$_POST['tagto']));
    }

    sql_query("UPDATE " .  sql_table('plug_blogroll_tags') . " SET tag='" . $tagto . "' WHERE tag='" . $tag . "'");

    // remove duplicate tag for a link, if they exist
    sql_query("DELETE FROM " . sql_table('plug_blogroll_tags') . " WHERE tag='" . $tagto . "' LIMIT 1");

    $plug = new PluginAdmin('Blogroll');
    if ($plug->plugin->getOption('DelIcioUs') == "yes") {
      $user = $plug->plugin->getOption('DeliciousUser');
      $password = $plug->plugin->getOption('DeliciousPassword');

      if ($user != '' && $password !='') {
              $oPhpDelicious = new PhpDelicious($user, $password);
              $oPhpDelicious->RenameTag($tag, $tagto);;
      }
    }

    echo "Tag " . $tag . " renames to " . $tagto . "<br/>";
  }

  elseif ($_POST['action'] != "" && $_GET['page'] == "managetag") {
    // cover editlink result for managetag
  }

  elseif ($_GET['action'] != "") {
    // cover editlink and editlink result
  }

  elseif ($_GET['page'] == "") {
    _listTags($memberid);
  }

  elseif ($_GET['page'] == "managetag") {
    _listTagLinks(urlencode($_GET['tag']), $memberid);
    echo "<h3 style=\"padding-left: 0px\">Rename this tag</h3>";
    echo "<form name=\"rentag\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."?page=managetag&tag=".$_GET['tag']."\">";
    echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'><td>&nbsp</td><td>
    Rename this tag to <input name=\"tagto\" type=\"text\" id=\"tag\" value =\"\" size=\"20\"><br/>
    <input type=\"hidden\" name=\"action\" value=\"rentag\" />
    <input type=\"submit\" name=\"Submit\" value=\"Rename\"></td></tr>";
    echo "</form>";
    echo "<h3 style=\"padding-left: 0px\">Delete this tag</h3>";
    echo "<a href=\"".$_SERVER['PHP_SELF']."?page=managetag&action=deltagconf&tag=".$_GET['tag']."\">Delete this tag</a>";
  }

?>
