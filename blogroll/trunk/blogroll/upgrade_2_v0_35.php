<?php
  $strRel = '../../../';
  include($strRel . 'config.php');

  $query = "CREATE TABLE IF NOT EXISTS ". sql_table('plug_blogroll_tags'). " (`tag` VARCHAR(64), `id` INT) TYPE=MYISAM;";
  $rows = mysql_query($query);

  $query = "ALTER TABLE ". sql_table('plug_blogroll_links'). " ADD comment VARCHAR(1024) NOT NULL;";
  $rows = mysql_query($query);

  $query = "ALTER TABLE ".sql_table('plug_blogroll_links')." CHANGE `title` `desc` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL";
  $rows = mysql_query($query);

  // ED$ The plugin option still need to change since some formatting has
  // changed. It ends up user need to uninstall/re-install.... maybe we
  // should reset the formatting here....

  echo "<h2>NP_Blogroll database upgrade completed, you can now remove this file</h2>";
?>
