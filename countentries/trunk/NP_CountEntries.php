<?php
/*
  History:
    1.4 - use sql_query
    1.3 - no future items are counted
    1.2 - fix multiple-blog counting bug
    1.1 - use sql_table, added supportsFeature
    1.0 - Initial release
*/
 
class NP_CountEntries extends NucleusPlugin {
   function getEventList() { return array(); }
   function getName() { return 'Count Entries'; }
   function getAuthor()  { return 'Rodrigo Moraes | Edmond Hui (admun)'; }
   function getURL()  { return 'http://www.tipos.com.br/'; }
   function getVersion() { return '1.4'; }
   function getDescription() { return  'This plugin shows the number of entries in your blog.'; }
   function supportsFeature($what) {
      switch($what) {
         case 'SqlTablePrefix': return 1;
         default: return 0;
      }
   }
 
   function doSkinVar($skinType) {
      global $blog;
 
      $blogid= $blog->getID();
      $timeNow = $blog->getCorrectTime();
 
      $query = sql_query("SELECT count(*) as entries FROM ". sql_table('item') ." WHERE iblog=".$blogid." AND itime<=". mysqldate($timeNow)." AND idraft=0");
      $row = mysql_fetch_object($query);
      $entries = $row->entries;
      echo $entries;
   }
}
 
if (!function_exists('sql_table')) {
   function sql_table($name) {
      return 'nucleus_' . $name;
   }
}
 
?>
