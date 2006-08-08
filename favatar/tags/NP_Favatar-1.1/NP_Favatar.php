<?php
/* NP_Favatar
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * Copyright © 2005 Joel Pan
 * http://ketsugi.com
 *
 * License information for this plugin:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 * Uses code from Favatar plugin for WordPress
 * http://dev.wp-plugins.org/wiki/favatars
 * Author: Jeff Minard
 * Author URI: http://thecodepro.com/ 
 *
 * Changelog:
 * 1.1    13-01-2006
 *    Bug fixed: plugin tries to reinsert an entry into the SQL table instead of updating it
 *    getTableList() function added so plugin's SQL table is included in Nucleus backups
 * 1.0		21-10-2005
 *		Initial release
 */
 
 class NP_Favatar extends NucleusPlugin {

  function getName() { return 'Favatar'; }
  function getAuthor() { return 'Joel Pan'; }
  function getURL() { return 'http://wakka.xiffy.nl/favatar'; }
  function getVersion() { return '1.1'; }
  function getDescription() { return 'Displays website favicons in comments.'; }
  function getTableList() { return array ( sql_table('plug_favatar') ); }
  function supportsFeature($what) {
    switch($what) {
      case 'SqlTablePrefix': return 1;
      default: return 0;
    }
  }
  
  function install() {
    $querystr = "CREATE TABLE IF NOT EXISTS `".sql_table('plug_favatar')."` ( `url` VARCHAR( 100 ) PRIMARY KEY, `icon` VARCHAR( 255 ) NOT NULL, `modified` TIMESTAMP NOT NULL)";
    sql_query($querystr);
    $this->createOption('template','Favatar template','textarea','<img src="<%favatar%>" class="favatar" />');
    $this->createOption("refreshtime", "How many days before Favatar should refresh the favicon URL?", "text", "60", "datatype=numeric");
    $this->createOption("del_uninstall", "Delete favicon data on uninstall?", "yesno","no");
    
    //Populate members' favicons
    $query = 'SELECT DISTINCT `murl` FROM `'.sql_table('member').'` WHERE `murl` != "" AND `murl` != "http://"';
    $result = sql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
      $this->_insertURL($row['murl']);
    }
    
    //Populate favicons from recent comments
    $query = 'SELECT DISTINCT `cmail` FROM `'.sql_table('comment').'` WHERE `cmail` != "" AND `cmail` != "http://"';
    $result = sql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
      $this->_insertURL($row['cmail']);
    }
    
  }
  
  function uninstall() {
    if ($this->getOption('del_uninstall') == "yes") {
      sql_query("DROP TABLE `".sql_table('plug_favatar')."`");
    }
  }  
  
  function getEventList() { return array('PostAddComment'); }
  
  function doTemplateCommentsVar (&$item, &$comment) {
    $url = $comment['userid'];
	  if (substr($url,0,7) == "http://") {
	    $tplVars['favatar'] = $this->_insertURL($url);
	    if ($tplVars['favatar']) echo TEMPLATE::fill($this->getOption('template'), $tplVars);
	  }
  }
  
	function event_PostAddComment (&$data) {
	  $url = $data['comment']['userid'];
	  if (substr($url,0,7) == "http://") $this->_insertURL($url);
	}

  function _getFavicon($url) {
    // start by fetching the contents of the URL they left...
    if( $html = @file_get_contents($url) ) {
      if (preg_match('/<link[^>]+rel="(?:shortcut )?icon"[^>]+?href="([^"]+?)"/si', $html, $matches)) {
        // Attempt to grab a favicon link from their webpage url
        $linkUrl = html_entity_decode($matches[1]);
        if (substr($linkUrl, 0, 1) == '/') {
          $urlParts = parse_url($url);
          $faviconURL = $urlParts['scheme'].'://'.$urlParts['host'].$linkUrl;
        }
        else if (substr($linkUrl, 0, 7) == 'http://') {
          $faviconURL = $linkUrl;
        }
        else if (substr($url, -1, 1) == '/') {
          $faviconURL = $url.$linkUrl;
        }
        else {
          $faviconURL = $url.'/'.$linkUrl;
        }
      }
      else {
        // If unsuccessful, attempt to "guess" the favicon location
        $urlParts = parse_url($url);
        $faviconURL = $urlParts['scheme'].'://'.$urlParts['host'].'/favicon.ico';
      }
      // Run a test to see if what we have attempted to get actually exists.
      if( $faviconURL_exists = $this->_validateURL($faviconURL) ) return $faviconURL;
    } 
    // Finally, if we haven't 'returned' yet then there is nothing to see here.
    return false;
  } 

  function _validateURL($link) {
    $url_parts = @parse_url( $link );
    if ( empty( $url_parts["host"] ) ) return false;
    if ( !empty( $url_parts["path"] ) ) {
      $documentpath = $url_parts["path"];
    }
    else {
      $documentpath = "/";
    }
    if ( !empty( $url_parts["query"] ) ) $documentpath .= "?" . $url_parts["query"];
    $host = $url_parts["host"];
    $port = $url_parts["port"];
    if ( empty($port) ) $port = "80";
    $socket = @fsockopen( $host, $port, $errno, $errstr, 30 );
    if ( !$socket ) return false;
    fwrite ($socket, "HEAD ".$documentpath." HTTP/1.0\r\nHost: $host\r\n\r\n");
    $http_response = fgets( $socket, 22 );
    $responses = "/(200 OK)|(30[0-9] Moved)/";
    if ( preg_match($responses, $http_response) ) {
      fclose($socket);
      return true;
    }
    else {
      return false;
    }
  }
  
  function _insertURL($url) {
    $url = rtrim($url,'/');
    $result = sql_query('SELECT `icon`,UNIX_TIMESTAMP(`modified`) AS `modified` FROM `'.sql_table('plug_favatar').'` WHERE `url` = "'.$url.'"');
    if (mysql_num_rows($result) == 0) {
      $icon = $this->_getFavicon($url);
      $query = 'INSERT INTO `'.sql_table('plug_favatar').'` VALUES("'.$url.'","'.$icon.'",NOW())';
      sql_query($query);
      return($icon);
    }
    else {
      $row = mysql_fetch_assoc($result);
      $now = time();
      $offset = $this->getOption("refreshtime") * 24 * 60 * 60;
      if ($now - $row['modified'] > $offset) {
        $icon = $this->_getFavicon($url);
        $query = 'UPDATE `'.sql_table('plug_favatar').'` SET `icon`="'.$icon.'", `modified`=NOW() WHERE `url`="'.$url.'"';
        sql_query($query);
        return($icon);
      }
      else return($row['icon']);
    }
    return(false);
  }
  
}
?>