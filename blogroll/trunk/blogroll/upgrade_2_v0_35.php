<?php
  $strRel = '../../../';
  include($strRel . 'config.php');

  $query = "CREATE TABLE IF NOT EXISTS ". sql_table(`plug_blogroll_tags`). " (`tag` VARCHAR(64), `id` INT) TYPE=MYISAM;";
  $rows = mysql_query($query);

  $query = "ALTER TABLE ". sql_table('plug_blogroll_links'). " ADD comment VARCHAR(1024) NOT NULL;";
  $rows = mysql_query($query);

  echo "<h2>NP_Blogroll database upgrade completed, you can now remove this file</h2>";
?>
