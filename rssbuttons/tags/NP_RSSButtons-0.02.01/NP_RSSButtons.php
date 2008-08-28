<?php
/** NP_RSSButtons
* 
* This will add buttons to major RSS and other site aggregation programs
*
*
* Verion History
* 0.2 added alt atribute to images for XHMTL compliance and use &amp; in urls where appropriate
* 0.1 initial try
*/

  class NP_RSSButtons extends NucleusPlugin {

  function getName() { return 'RSSButtons';  }
  function getAuthor() { return 'Frank Truscott'; }
  function getURL() { return 'http://www.iai.com'; }
  function getVersion() { return '0.2.01';  }
  function getDescription() { return 'Returns button links to RSS feeds for major news readers'; }
  function supportsFeature($what) {
    switch($what) {
      case 'SqlTablePrefix': return 1;
      default: return 0;
    }
  }

  function install() {
    //Create options

    $this->createBlogOption("rssbaseurl", "Path to directory containing xml-rss2.php. Should end with slash. Null means use site url", "text", '');
  }

     // skinvar plugin can have a blogname as second parameter
   function doSkinVar($skinType,$feedname = 'rss') {
      global $manager, $blog, $CONF,$blogid,$catid;
 
      $burl = $blog->getURL();
      $bname = $blog->getName();
      $imagedir = $CONF['PluginURL']."rssbuttons/";

      if ( !isset($feedname) || $feedname == NULL )
      {  $feedname = 'rss'; }
   
      $blogrssbase = $this->getBlogOption($blogid,'rssbaseurl');
      if ( isset($blogrssbase) && $blogrssbase == NULL )
      {  $blogrssbase = $CONF['IndexURL']; }
      
      $rss_path = "xml-rss2.php?blogid=".$blogid;
      if ( $catid )
      {  $rss_path = $rss_path."&amp;catid=".$catid; }
      $rss_url = $blogrssbase.$rss_path;
      
      if ( $feedname == 'rss' )
      {  echo "<a href=\"".$rss_url."\" title=\"Subscribe to my feed\"><img src=\"".$imagedir."feed-icon.gif\" alt=\"RSS\" style=\"border:0\"/></a>";
      return 1;
      }
      
      if ( $feedname == 'xml' )
      {  echo "<a href=\"".$rss_url."\" title=\"Subscribe to my feed\"><img src=\"".$imagedir."xml.gif\" alt=\"XML\" style=\"border:0\"/></a>";
      }
      
      if ( $feedname == 'google' )
      {  echo "<a href=\"http://fusion.google.com/add?feedurl=".$rss_url."\" title=\"Subscribe to my feed\"><img src=\"".$imagedir."google-all.gif\" alt=\"Google\" style=\"border:0\"/></a>";
      }
      
      if ( $feedname == 'delicious' )
      {  echo "<a href=\"http://del.icio.us/post?url=".$burl."&amp;title=".$bname."\" title=\"Subscribe to my feed\"><img src=\"".$imagedir."delicious.gif\" alt=\"del.icio.us\" style=\"border:0\"/></a>";
      }
      
      if ( $feedname == 'yahoo' )
      {  echo "<a href=\"http://add.my.yahoo.com/rss?url=".$rss_url."\" title=\"Subscribe to my feed\"><img src=\"".$imagedir."yahoo.gif\" alt=\"My Yahoo\" style=\"border:0\"/></a>";
      }

      if ( $feedname == 'bloglines' )
      {  echo "<a href=\"http://www.bloglines.com/sub/".$rss_url."\" title=\"Subscribe with bloglines\"><img src=\"".$imagedir."bloglines.gif\" alt=\"Bloglines\" style=\"border:0\"/></a>";
      }

      if ( $feedname == 'newsgator' )
      {  echo "<a href=\"http://www.newsgator.com/ngs/subscriber/subext.aspx?url=".$rss_url."\" title=\"Subscribe in NewsGator online\"><img src=\"".$imagedir."newsgator.gif\" alt=\"NewsGator\" style=\"border:0\"/></a>";
      }

      if ( $feedname == 'msn' )
      {  echo "<a href=\"http://my.msn.com/addtomymsn.armx?id=rss&amp;ut=".$rss_url."\" title=\"Add to my msn\"><img src=\"".$imagedir."msn.gif\" alt=\"MyMSN\" style=\"border:0\"/></a>";
      }

      if ( $feedname == 'feedster' )
      {  echo "<a href=\"http://www.feedster.com/myfeedster.php?action=addrss&amp;rssurl=".$rss_url."&amp;confirm=no\" title=\"Add to feedster\"><img src=\"".$imagedir."addmyfeedster.gif\" alt=\"feedster\" style=\"border:0\"/></a>";
      }

      if ( $feedname == 'aol' )
      {  echo "<a href=\"http://feeds.my.aol.com/add.jsp?url=".$rss_url."\" title=\"Add to my aol\"><img src=\"".$imagedir."aol.gif\" alt=\"myAOL\" style=\"border:0\"/></a>";
      }

      if ( $feedname == 'furl' )
      {  echo "<a href=\"http://www.furl.net/storeIt.jsp?u=".$burl."\" title=\"Furl ".$bname."\"><img src=\"".$imagedir."furl.gif\" alt=\"Furl\" style=\"border:0\"/></a>";
      }

      if ( $feedname == 'rojo' )
      {  echo "<a href=\"http://www.rojo.com/add-subscription?resource=".$rss_url."\" title=\"Subscribe in rojo\"><img src=\"".$imagedir."rojo.gif\" alt=\"RoJo\" style=\"border:0\"/></a>";
      }

    }
}
?>
