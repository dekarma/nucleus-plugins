<?php
/*
  NP_Podcast - provide support for podcasting in Nucleus

  Usage:
    1) install the plugin
    2) modify the feeds/rss20 template and add <%Podcast%> inside the item tag after
       <pubDate>...</pubDate>
    3) upload the mp3 and use the skinvar <%Podcast(filename|text)%>. For offshore mp3 file that
       stored elsewhere, put in the URL directly

  Known issue:
    - only one podcast in per post is assumed

  v0.1
    - Initialize release
  v0.2 Nov 4, 2004
    - <%Podcast%> skinvar
  v0.3 Apr 14, 2005
    - add supportsFeature
    - support to torrent and mp3
    - audio.weblogs.com ping
    - able to point enclosure offshore
  v0.4 May 6, 2005
    - fix ping...
    - option to enable/disable ping
  v0.5 Sep 22, 2005
    - file name error bug fix (thanks to Andy)

*/ 

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table'))
{
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_Podcast extends NucleusPlugin { 

  var $authorid;

  function getEventList() { return array('PreItem', 'PreAddItem', 'PostAddItem'); }
  function getName() { return 'Podcast'; } 
  function getAuthor() { return 'Edmond Hui (admun)'; } 
  function getURL() { return; } 
  function getVersion() { return '0.5'; } 
  function getDescription() { return 'This plugin provides podcasting support in Nucleus via a new <%Podcast%> template var'; }
  // Note: I never run this plugin on 2.0 and have no idea whether it
  //       wil work on <2.5. A user can simply chnage it to return
  //       '200' and see if it works (likely will). I will gladly
  //       change the min version to 2.0 and add the sql_table fix
  //       upon such report. 8)
  function getMinNucleusVersion() { return '250'; } 

  function supportsFeature($what) {
    switch($what)
    {
      case 'SqlTablePrefix':
        return 1;
      default:
        return 0;
    }
  }

  function install() {
    $this->createOption('ping','Enable audio.weblogs.com ping','yesno','yes');
  }

  // This function generates the actual URL to the podcast
  function event_PreItem($data) {
    global $item;
    $item = &$data["item"];
    $this->authorid = $item->authorid;
    if (strstr($item->body . " " . $item->more, "<%Podcast(")) {
      $item->body = preg_replace_callback("#<\%Podcast\((.*?)\|(.*?)\)%\>#", array(&$this, 'replaceCallback'), $item->body);
      $item->mmore = preg_replace_callback("#<\%Podcast\((.*?)\|(.*?)\)%\>#", array(&$this, 'replaceCallback'), $item->more);
    }
  }

  function replaceCallback($matches) {
    global $CONF;
    $file = $matches[1];
    if ($matches[2] == '') {
      $text = $matches[1];
    } else {
      $text = $matches[2];
    }
    // not strstr...
    if (strstr($file, "http:"))
    {
      return "<div class=\"podcast\"><a href=\"" . $file . "\">" . $text . "</a></div>";
    } else {
      return "<div class=\"podcast\"><a href=\"" . $CONF['MediaURL'] . $this->authorid . "/" .  $file . "\">" . $text . "</a></div>";
    }


  }

  // This function generates the enclosure in RSS feed
  function doTemplateVar(&$item) {
    global $DIR_MEDIA, $CONF;

    // see if there is a podcast file here
    if (strstr($item->body." ".$item->more, "<div class=\"podcast\"")) {

      if (strstr($item->body." ".$item->more, ".mp3")) {
        $search = "/http:\/\/.*?\.mp3/i";
	$type = "audio/mpeg";
      } else {
        $search = "/http:\/\/.*?\.torrent/i";
	$type = "application/x-bittorrent";
      }
    
      $mem = MEMBER::createFromName($item->author);
      $id = $mem->getId();
      preg_match($search, $item->body." ".$item->more, $result);
      $mfile = explode("/", $result[0]);
      $file = $DIR_MEDIA . $id . '/' . $mfile[sizeof($mfile)-1];
      $size = filesize($file);

      $url = $result[0];
      echo "<enclosure url=\"$url\" length=\"$size\" type=\"$type\"/>";
    }
  } 
  
  function event_PreAddItem($data) {
    $this->myBlogId    = $data['blog']->blogid;
    $this->draft = "no";
    $this->podcast = false;

    if (strstr($data['more'] . " " . $data['body'], "<%Podcast(")) {
      $this->podcast = true;
    }

    if ($data['draft'] == '1') {
      $this->draft = "yes";
    }
  }
  
  function event_PostAddItem($data) {

    if ($this->draft == "no" && $this->podcast == true && $this->getOption('ping') == "yes")
    {
      $b = new BLOG($this->myBlogId);

      if (!class_exists('xmlrpcmsg')) {
        global $DIR_LIBS;
        include($DIR_LIBS . 'xmlrpc.inc.php');
      }

      $message = new xmlrpcmsg(
                     'weblogUpdates.ping', array(
                     new xmlrpcval($b->getName(),'string'),
                     new xmlrpcval($b->getURL(),'string'),
                     ));

      $c = new xmlrpc_client('/RPC2', 'audiorpc.weblogs.com', 80);

      //$c->setDebug(1);

      $r = $c->send($message,15); // 15 seconds timeout...
    }
  }
}
?>
