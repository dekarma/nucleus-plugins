<?php

// if your 'plugin' directory is not in the default location,
// edit this variable to point to your site directory
// (where config.php is)
$strRel = '../../../';

include($strRel . 'config.php');

if (!$member->isLoggedIn()) doError('You\'re not logged in.');

include($DIR_LIBS . 'PLUGINADMIN.php');

$table_name = sql_table("plug_dl_count");

global $CONF;

// create the admin area page
$oPluginAdmin = new PluginAdmin('DlCounter');
$oPluginAdmin->start();

echo "<h2>DlCounter</h2>";
?>
<hr/>
<?php
  $sort = RequestVar('sort');
  if ($sort == '') $sort = "count";
  $query = "SELECT * FROM ". $table_name;
  if ($sort == "count")
    $query .= " ORDER BY count DESC";
  else
    $query .= " ORDER BY file";

  $result = sql_query($query);

  $sortURL = $CONF['PluginURL']."dlcounter/index.php";

  echo "<table>";
  echo "<tr><th><a href=\"".$sortURL."?sort=file\">File</a></th><th><a href=\"".$sortURL."?sort=count\">Download Count</a></th></tr>";

  while($row = mysql_fetch_object($result)) {
    echo "<tr><td>" . $row->file . "</td><td>". $row->count . "</td></tr>";
  }

  echo "</table>";

$oPluginAdmin->end();
?>
