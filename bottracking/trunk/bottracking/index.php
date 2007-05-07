<?php

// if your 'plugin' directory is not in the default location,
// edit this variable to point to your site directory
// (where config.php is)
$strRel = '../../../';

include($strRel . 'config.php');

if (!$member->isLoggedIn()) doError('You\'re not logged in.');

include($DIR_LIBS . 'PLUGINADMIN.php');

$table_name = sql_table("plug_bottracking");

// create the admin area page
$oPluginAdmin = new PluginAdmin('BotTracking');
$oPluginAdmin->start();

global $CONF;

echo "<h2>BotTracking</h2>";
echo "<ul>";
echo "<li><a href=\"".$oPluginAdmin->plugin->getAdminURL()."?action=showvisit\">Show most 200 recent Bots visits</a></li>";
echo "<li><a href=\"".$oPluginAdmin->plugin->getAdminURL()."?action=showubots\">Show Bot statistics</a></li>";
echo "<li><a href=\"".$oPluginAdmin->plugin->getAdminURL()."?action=cleanoldstats\">Remove old statistics (over 3 month old)</a></li>";
echo "<li><a href=\"".$CONF['ActionURL']."?action=plugin&name=BotTracking&type=count\">Update subscription count</a></li>";
echo "<li><a href=\"index.php?action=pluginoptions&amp;plugid=".$oPluginAdmin->plugin->plugid."\">Edit options</a></li>";
echo "</ul>";
?>
<hr/>
<?php
$action = $_GET['action'];

if ($action == "showvisit") {
  $query = "SELECT * FROM ". $table_name . " ORDER BY last DESC LIMIT 0,200";
  $result = sql_query($query);

  echo "<table>";
  echo "<tr><th>Bots</th><th>Time Visit</th><th>URL</th><th>Hostname</th></tr>";

  while($row = mysql_fetch_object($result)) {
    echo "<tr><td>" . $row->bots . "</td><td>". $row->last . "</td><td>" . $row->url . "</td><td>" . $row->hostname."</td></tr>";
  }

  echo "</table>";
}
else if ($action == "showubots") {
//  select DISTINCT url,agent FROM nucleus_plug_bottracking WHERE url LIKE "%xml\-rss2\.php%"
  $query = "SELECT DISTINCT bots FROM ". $table_name;
  $result = sql_query($query);

  echo "<table>";
  echo "<tr><th>Bots</th><th>Visits</th><th> #Subscriber (as reported in past 24 hours)</th></tr>";

  while($row = mysql_fetch_object($result)) {
    $sub = 0;
    $result1 = sql_query("SELECT COUNT(*) FROM " . $table_name . " WHERE bots = \"" . $row->bots . "\"");
    $count = mysql_result($result1,0);

    $result2 = sql_query("SELECT DISTINCT bots,agent,url FROM " . $table_name . " WHERE bots = \"" . $row->bots . "\" AND DATE_SUB(CURDATE(),INTERVAL 1 DAY) <= last ");
    $matches = array();
    $subs = array();
    while ($row2 = mysql_fetch_object($result2)) {
      preg_match("/\d.subscriber/", $row2->agent, $matches);
      if ($matches[0] != "") {
        $subs = explode(" ", $matches[0]);
        $sub += $subs[0];
      }
    }

    echo "<tr><td><a href=\"" . $oPluginAdmin->plugin->getAdminURL() . "?action=showbot&bot=" . $row->bots .
         "\" title=\"Click to see all visits from this robot\">" . $row->bots . "</a></td><td>" . $count . 
         "</td><td>" . $sub . "</td></tr>";
  }

  echo "</table>";
} else if ($action == "showbot") {
  $bot = $_GET['bot'];

  $query = "SELECT * FROM ". $table_name . " WHERE bots = \"" . $bot . "\" ORDER BY last DESC";
  $result = sql_query($query);

  echo "<table>";
  echo "<tr><th>Bots</th><th>Agent</th><th>Time Visit</th><th>URL</th><th>Hostname</th></tr>";

  while($row = mysql_fetch_object($result)) {
    echo "<tr><td>" . $row->bots . "</td><td>" . $row->agent . "</td><td>". $row->last . "</td><td>" . $row->url . "</td><td>" . $row->hostname."</td></tr>";
  }

  echo "</table>";

} else if ($action == "cleanoldstats") {
  sql_query("DELETE FROM ". $table_name . " WHERE DATE_SUB(CURDATE(),INTERVAL 90 DAY) >= last;");
  echo "Old statistics removed";
}

$oPluginAdmin->end();
?>
