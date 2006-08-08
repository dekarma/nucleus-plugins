<?php
// include all classes and config data 
include('../../../config.php');
if (!$member->isLoggedIn()) doError('You\'re not logged in.');

if ($_POST['action'] == "" && $_GET['action'] == "") {
  include($DIR_LIBS . 'PLUGINADMIN.php');

  // create the admin area page
  $oPluginAdmin = new PluginAdmin('Blogroll');
  $oPluginAdmin->start();
?>
<h2>Blogroll</h2>
<ul>
<li><a href="index.php?action=pluginoptions&amp;plugid=<? echo $oPluginAdmin->plugin->plugid; ?>">Edit options</a></li>
<li><a href="<? echo $oPluginAdmin->plugin->getAdminURL(); ?>">Manage groups</a></li>
<li><a href="<? echo $oPluginAdmin->plugin->getAdminURL(); ?>bookmarklet.php">Bookmarklet</a></li>
</ul>
<h3 style=\"padding-left: 0px\">Bookmarklet</h3>
<p>Use this bookmark to add a link to your blogroll: <a href="javascript:location.href='<? echo $oPluginAdmin->plugin->getAdminURL(); ?>bookmarklet.php?action=bmaddlink&url='+encodeURIComponent(location.href)+'&text='+document.title">Blogroll it!</a></p>
<?
  $oPluginAdmin->end();
}
else {
  include('groups.php');
  include('links.php');

  global $member;
  $memberid = $member->id;
  $url = $_POST['url'] == "" ? $_GET['url'] : $_POST['url'];
  $text = $_POST['text'] == "" ? stripslashes($_GET['text']) : $_POST['text'];
  $desc = $_POST['desc'] == "" ? stripslashes($_GET['desc']) : $_POST['desc'];
  $counter = $_POST['counter'] == "" ? 0 : $_POST['counter'];
  $groupid = $_POST['group'];
  
  if ($_POST['action'] == "bmaddlink") {
    if ($groupid == "") $error = "Please choose a group to add the link to.";
  	else {
  	  $error = _addLink($memberid, $groupid, $url, $text, $title, $counter);
  		if ($error[0]) {
  		  header("Location: $url");
  			exit();
  		}
  		else $error = $error[1];
  	}
  }
      
  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
  echo '<html xmlns="http://www.w3.org/1999/xhtml">';
  echo '<head><title>Add link to Blogroll</title>';
  echo '<link rel="stylesheet" type="text/css" href="../../styles/bookmarklet.css" />';
  echo '<link rel="stylesheet" type="text/css" href="../../styles/addedit.css" />';
  echo '</head><body>';
  echo '<h1>Add link to Blogroll</h1>';
  echo $error;
  echo "<form name=\"add\" method=\"post\" action=\"bookmarklet.php\">";
  echo "<table><tbody>";
  echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
  echo "<td>URL</td><td><input name=\"url\" type=\"text\" id=\"url\" value=\"$url\" size=\"50\" maxlength=\"255\"></td></tr>";
  echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
  echo "<td>Text</td><td><input name=\"text\" type=\"text\" id=\"text\" value=\"$text\" size=\"50\" maxlength=\"255\"> (optional)</td></tr>";
  echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
  echo "<td>Description</td><td><input name=\"title\" type=\"text\" id=\"title\" value=\"$title\" size=\"50\" maxlength=\"255\"> (optional)</tr></td>";
  echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
  echo "<td>Counter</td><td><input name=\"counter\" type=\"text\" id=\"counter\" value=\"$counter\" value=\"0\" size=\"5\" maxlength=\"10\"></td></tr>";
  echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
  echo "<td>Group</td><td><select name=\"group\">";
  echo "<option value=\"\" selected=\"\">Choose a group</option>";
  foreach (_getGroups($memberid) as $group) {
    echo "<option value=\"".$group['id']."\"";
  	if ($group['id'] == $groupid) echo " selected=\"\"";
  	echo ">".$group['name']."</option>";
  }
  echo "</select></td></tr>";
  echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'><td>&nbsp;</td><td>";
  echo "<input type=\"submit\" name=\"Submit\" value=\"Add this link\" /></td></tbody></table>";
  echo "<input type=\"hidden\" name=\"action\" value=\"bmaddlink\" /></form>";
}
?>