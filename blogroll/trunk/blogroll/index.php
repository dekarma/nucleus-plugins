<?php
// if your 'plugin' directory is not in the default location,
// edit this variable to point to your site directory
// (where config.php is)
$strRel = '../../../';

include($strRel . 'config.php');

if ($_GET['n'] == "") {

  if (!$member->isLoggedIn()) doError('You\'re not logged in.');
  //$manager->checkTicket();

  include($DIR_LIBS . 'PLUGINADMIN.php');

  // create the admin area page
  $oPluginAdmin = new PluginAdmin('Blogroll');
  $oPluginAdmin->start();

  function _formatDate($date) {
    $date = strtotime($date);
    return(date("d M Y",$date).'<br />'.date("H:i:s",$date));
  }

  echo "<h2>Blogroll</h2>";
  echo "<ul>";
  if ($member->isAdmin() == true) {
    echo "<li><a href=\"index.php?action=pluginoptions&amp;plugid=".$oPluginAdmin->plugin->plugid."\">Edit options</a></li>";
  }
  echo "<li><a href=\"".$oPluginAdmin->plugin->getAdminURL()."\">Blogroll Management Main Menu</a></li>";
	echo "<li><a href=\"".$oPluginAdmin->plugin->getAdminURL()."bookmarklet.php\">Bookmarklet</a></li>";
  echo "</ul>";

  global $member;
  $memberid = $member->id;
  
  if ($_POST['action'] != "") echo "<h3 style=\"padding-left: 0px\">Result</h3>";
  include("groups.php");
  include("tags.php");
  include("links.php");

  $oPluginAdmin->end();
}

else {
  $query = sql_query('SELECT `url`,`counter` FROM `'.sql_table('plug_blogroll_links').'` WHERE `id`='.$_GET['n']);
  $result = mysql_fetch_assoc($query);
  $robot = false;
  $agent = $_SERVER['HTTP_USER_AGENT'];
  foreach (file('robotagents.txt') as $bot) {
    if (substr_count($agent,substr($bot,0,-1)) != 0) { $robot = true; }
  }
  if (!$robot) { $query = sql_query('UPDATE `'.sql_table('plug_blogroll_links').'` SET `clicked`=NOW(), `counter`='.++$result['counter'].' WHERE `id`='.$_GET['n']); }
  header('Location: '.$result['url']);
}
?>
