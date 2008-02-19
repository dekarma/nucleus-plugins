<?php
 
/*
 * 0.1 - Initial release
 * 0.2 - fix case 1 blog# bug
 * 0.5 - fix time zone offset
 * 0.6 - latest posts offset (to allow showing recent posts that is not on main page)
 *     - add header/footer formating
 * 0.7 - fix not displaying head/foot format bug
 * 0.8 - use sql_query
 */
 
 
class NP_LatestPosts extends NucleusPlugin {
    function getName() { return 'LatestPosts'; }
    function getAuthor()  { return 'Curtis A. Weyant, mod by Edmond Hui (admun)'; }
    function getURL() {   return 'http://wakka.xiffy.nl/latestposts'; }
    function getVersion() {   return '0.8'; }
    function getMinNucleusVersion() { return 200; }
 
    function supportsFeature($f) {
      switch ( $f ) {
        case 'SqlTablePrefix':
          return true;
      }
      return false;
    }
 
    function getDescription() { return 'Show a list with the latest updated items.'; }
 
    function install() {
      $this->createOption('max','Maximum number of entries to display (0 for all)','text','10');
      $this->createOption('off','Number of entries display to offset','text','0');
      $this->createOption('dateformat','Date format (as passed to PHP date() function)','text','Y-m-d H:i:s');
      $this->createOption('headformat','Header format','text','<ul>');
      $this->createOption('itemformat','Item format (as HTML or other snippet) -- use [blog], [author], [date], [title] and [url]','text','<li><strong>[blog]</strong>: <a href="[url]">[title]</a> (by [author]) - [date]</li>');
      $this->createOption('footformat','Footer format','text','</ul>');
      $this->createOption('type','Type of list to create:','select','1','1: Latest entries from current (or supplied) blog|1|2: Latest entries from all blogs|2|3: Latest entry from each blog|3');
    }
 
   function doSkinVar($skinType,$max='',$blogid='') {
     global $blog;
 
     $type = $this->getOption('type');
 
     if ( $blogid == '' ) {
       $blogid = $blog->blogid;
     }
     else if ( !is_numeric($blogid) ) {
       $blogid = getBlogIDFromName($blog);
       $type = 1;
     }
 
 
     if ( $max == '' ) { $max = $this->getOption('max'); }
 
     $off = $this->getOption('off');
 
      if ( $max != 0 ) {
        $qlimit = "LIMIT $max OFFSET $off";
      }
 
      $table = sql_table('item');
      $qwhere = "WHERE idraft=0 AND itime<=NOW()+" . $blog->getTimeOffset()*3600;
      $bqwhere = $qwhere." AND iblog=$blogid";
 
      $qorder = "ORDER BY itime DESC";
 
      switch ( $type ) {
 
        # Show latest entries from all blogs
        case 2:
          $query = "SELECT inumber,iblog,iauthor,ititle,itime FROM $table $qwhere $qorder $qlimit";
          $this->_printLineFromQuery($query);
          break;
 
        # Show latest entry for each blog
        case 3:
 
          $server_info = mysql_get_server_info();
          $info = substr($server_info,0,strpos($server_info,"-"));
 
 
          if ( settype($info,'float') < 4.1 ) {
             $res = sql_query("SELECT bnumber FROM ".sql_table('blog'));
 
             while ( $row = mysql_fetch_assoc($res) ) {
               $this->_printLineFromQuery("SELECT inumber,iblog,iauthor,ititle,itime FROM $table $qwhere AND iblog=$row[bnumber] ORDER BY itime DESC LIMIT 1");
             }
          }
          else {
            $query = "SELECT inumber,iblog,iauthor,ititle,itime FROM $table AS i1 WHERE idraft=0 ".
                     "AND itime=(SELECT MAX(i2.itime) FROM $table AS i2 WHERE i1.iblog=i2.iblog) $qorder";
            $this->_printLineFromQuery($query);
          }
          break;
 
        # Show latest entries from current (or supplied) blog
        case 1:
        default:
          $query = "SELECT inumber,iblog,iauthor,ititle,itime FROM $table $qwhere AND iblog=$blogid $qorder $qlimit";
          $this->_printLineFromQuery($query);
          break;
 
      }
  }
 
 
 
  function _printLineFromQuery ( $query ) {
      $search = array('[blog]','[author]','[date]','[title]','[url]');
 
      $res = sql_query($query);
 
      echo $this->getOption('headformat');
 
      while( $row = mysql_fetch_assoc($res) ) {
 
        $replace['blog'] = getBlogNameFromID($row['iblog']);
 
        $member = new MEMBER();
        $member->readFromID($row['iauthor']);
        $replace['author'] = $member->getDisplayName();
 
        $replace['date'] = date($this->getOption('dateformat'),strtotime($row['itime']));
 
        $replace['title'] = $row['ititle'];
        $replace['url'] = createItemLink($row['inumber']);
 
        print str_replace($search,$replace,$this->getOption('itemformat'))."\n";
     }
 
      echo $this->getOption('footformat');
   }
}
?>
