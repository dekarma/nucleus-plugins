<?php
  $strRel = '../../../';
  include($strRel . 'config.php');

  // to drop column: alter table nucleus_plugin_notifyaddress drop validate;
  $query = "ALTER TABLE nucleus_plugin_notifyaddress ADD validate int DEFAULT 1 NOT NULL;";
  $rows = mysql_query($query);

//  $query = "UPDATE nucleus_plugin_notifyaddress SET validate='1';";
 // $rows = mysql_query($query);

  echo "<h2>NotifyMe database upgrade completed, you can now remove this file</h2>";
?>
