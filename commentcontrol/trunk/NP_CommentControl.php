<?php

/**
 * Versions:
 *   0.6  2007-06-24
 *      - fix use createItemLink()
 *   0.5a 2007-05-07
 *      - use sql_query()
 *   0.5 2005-09-29
 *      - added item author level approval support
 *   0.4 2005-09-16 admun
 *      - added pre-3.3 compatibility
 *   0.3 2005-08-23 Red Dalek
 *      - added support for separate url\email field
 *   0.25b 2005-01-31 admun
 *      - added deleted all pending (from admin menu)
 *   0.25a 2005-01-31 admun
 *      - add global DIR_PLUGINS
 *   0.25 2005-01-23 admun
 *      - added banlist file function @ commentcontrol/banlist.txt (to allow longer member ban list)
 *      - fixed a redirection bug
 *      - fix // in allow/deny re-direction link, FancyURL
 *   0.24 2004-09-23 karma
 *      - only show plugin admin area for admin users

 *   0.23 2004-07-29 admun
 *      - added Template text as as option
 *      - fixed PostAddComment not triggering after comment saved bug
 *      - do not display approval warning if item is closed
 *   0.22 2004-07-22 admun
 *      - fixed warning for member comment control
 *      - fixed admin menu approve/deny re-direction
 *      - added option for RSS link text
 *      - added option for comment pending text
 *      - fixed add comment redirect bug reported by ComposerRyan
 *   0.21 2004-05-24 admun
 *      - merged w/ XE v1.1.1.2
 *      - added pending comment <%CommentControl(pending)%>
 *      - indentation
 *      - approve/deny re-direction properly
 *      - merged in member comment control
 *   0.20 2004-04-30 radek
 *      - added RSS feed of latest 20 pending comments
 *      - link in RSS feed to allow / deny comment
 *      - template var to display text NEW for items with pending comments
 *      - changed needsVerification routine
 *      - <CommentControl(url)> will display, for admins, link to RSS feed
 *      - added patch for non-XE Nucleus editions
 *   0.10   2003-12-29   karma
 *      - added 'admin area' thingieSubscribe for updatess
 *   0.03   2003-12-08   karma
 *      - added code out of the CommentEditLink plugin from Xiffy
 *   0.02   2003-12-07   karma
 *      - some fixes
 *      - option 'Always moderate comments older than x days'
 *   0.01   2003-12-02   karma
 *      - The "Bernard" Edition
 *
 *  admun TODO
 *    - add notification email w/ allow/deny link
 *
 * Note: <%CommentControl(rss)%> is no longer supported and might be removed in the future
 *
 */

class NP_CommentControl extends NucleusPlugin {

   function NP_CommentControl() {
      $this->table_pending = sql_table('plug_cc_pending');
   }

   function getName()    { return 'CommentControl'; }
   function getAuthor()     { return 'karma, mod by Radek Hulaan, Edmond Hui (admun), Red Dalek'; }
   function getURL()     { return 'http://demuynck.org/'; }
   function getVersion()    { return '0.6'; }
   function getDescription() { return 'Tools to handle trolls and comment spam. RSS feed of pending comments now included.'; }

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
      $this->createOption('quickmenu', 'Show in quick menu', 'yesno', 'yes');
      $this->createOption('names', 'Names of users that cannot place comments (separate with commas)', 'text', '');
      $this->createOption('days', 'Always moderate comments older than x days', 'text', '30');
      $this->createOption('text1', 'Comments to be approved', 'text', '<h2>Comments Awaiting Approval</h2>');
      $this->createOption('text2', 'Comment saved, needs to be approved', 'text', '<blockquote id="pending"><p><strong>Your comment was saved. Thank you! We will review it ASAP.</strong></p></blockquote>');
      $this->createOption('text3', 'Comment saved, needs to be approved', 'text', '<blockquote id="pending"><p><strong>Comments must be approved before being published. Thank you!</strong></p></blockquote>');
      $this->createOption('text4', 'Text for RSS link', 'text', 'RSS 2.0 of pending comments');
      $this->createOption('text5', 'Text for no pending comment', 'text', 'no comment pending<br />');
      $this->createOption('text6', 'Text for comment pending', 'text', 'comment(s) pending');
      $this->createOption('text7', 'Text for new comment indicator', 'text', '<span class=\'r\'>[New comment pending!]</span>');
      // create the table that will keep track of notifications
      $query =  'CREATE TABLE '. $this->table_pending. '(';
      $query .= ' id int(11) NOT NULL auto_increment,';      // a unique id
      $query .= ' cbody text NOT NULL,';
      $query .= ' cuser varchar(40) default NULL,';
      $query .= ' cmail varchar(100) default NULL,';
      $query .= ' cemail varchar(100) default NULL,';
      $query .= ' cmember int(11) default NULL,';
      $query .= ' citem int(11) NOT NULL default \'0\',';
      $query .= ' ctime datetime NOT NULL default \'0000-00-00 00:00:00\',';
      $query .= ' chost varchar(60) default NULL,';
      $query .= ' cip varchar(15) NOT NULL default \'\',';
      $query .= ' cblog int(11) NOT NULL default \'0\',';
      $query .= ' PRIMARY KEY  (id)';
      $query .= ') TYPE=MyISAM;';
      sql_query($query);
      // options for RSS
      $this->createOption('blogcode','Character encoding:','text','ISO-8859-1');
      $this->createOption('blogpicture','Picture to display:','text','./skins/base/rsspending-new.png');
      $this->createOption('pendmember','Should blog member\'s comment being controlled?','yesno','no');
   }

   function unInstall() {
      sql_query('DROP TABLE ' . $this->table_pending);
   }

   function getTableList() {
      return array($this->table_pending);
   }

   function getEventList() {
      return array('PreAddComment', 'QuickMenu');
   }
   
   function init() {
      global $DIR_PLUGINS;
      $this->strNames      = strtolower($this->getOption('names'));
      if ($this->strNames == "") {
         $this->strNames = file_get_contents($DIR_PLUGINS.'commentcontrol/banlist.txt');
      } else {
         $this->strNames = $this->strNames . "," . file_get_contents($DIR_PLUGINS.'commentcontrol/banlist.txt');
      }
      $this->strNames = rtrim($this->strNames, "\n");
      $this->aNames = explode(',', $this->strNames);
      $this->iDays = intval($this->getOption('days'));
   }
   
   function hasAdminArea() {
      return 1;
   }
   
   /**
    * Adds an entry to the 'Quick Menu' on the Nucleus administration pages.
    * The entry will link to the commentcontrol admin page
    */
   function event_QuickMenu(&$data) {
      // only show when option enabled
      if ($this->getOption('quickmenu') != 'yes')
         return;

      global $member;
      if (!($member->isLoggedIn() && $member->isAdmin())) return;

      array_push(
            $data['options'],
            array(
               'title' => 'Comment Control',
               'url' => $this->getAdminURL(),
               'tooltip' => 'See the list of comments that have not yet been verified.'
                 )
           );
   }

   function event_PreAddComment(&$data) {
      global $member;

      // logged in members can always post
      if ($member->isLoggedIn())
         $strUserName = $member->getDisplayName();
      else
         $strUserName = $data['comment']['user'];

      $itemid = intval($data['comment']['itemid']);

      if ($this->needsVerification($itemid, $strUserName)) {
         // add to list of comments to aprove
         $comment = $data['comment'];
         $name = addslashes($comment['user']);
         $url = addslashes($comment['userid']);
         $email = addslashes($comment['email']);
         $body = addslashes($comment['body']);
         $host = addslashes($comment['host']);
         $ip = addslashes($comment['ip']);
         $memberid = intval($comment['memberid']);
         $timestamp = date('Y-m-d H:i:s', $comment['timestamp']);
         $itemid = $comment['itemid'];
         $blogid = getBlogIDFromItemID($comment['itemid']);

         $query = 'INSERT INTO '.$this->table_pending.' (CUSER, CMAIL, CEMAIL, CMEMBER, CBODY, CITEM, CTIME, CHOST, CIP, CBLOG) '
               . "VALUES ('$name', '$url', '$email', $memberid, '$body', $itemid, '$timestamp', '$host', '$ip', '$blogid')";

         sql_query($query);         
         
         // redirect when adding comments succeeded
         $url = '';
         if (postVar('url')) {
            $url = postVar('url');
            if (!strstr($url, '?'))
               $url .= '?pending=1';
            else
               $url .= '&pending=1';
         } else {
            $url = createItemLink($itemid);
         }

         header('Expires: 0');
         header('Pragma: no-cache');
         header('Location: '.$url.'#pending');
         exit();
      }
   }
   
   function needsVerification($itemid, $strUserName) {
      global $manager, $member;
      // username in list of evil people
      if ( in_array(strtolower($strUserName), $this->aNames) && strlen($strUserName)>0 ) return true;
      $itemid    = intval($itemid);
      $blogid    = getBlogIDFromItemID($itemid);   

      if ($this->getOption('pendmember') == 'yes')
         $pending = $member->isAdmin();
      else
         $pending = $member->canLogin();

      if ($member->isLoggedIn() && $pending) return false;   
      // item is older than x days
      $timeItem = quickQuery('SELECT UNIX_TIMESTAMP(itime) AS result FROM '.sql_table('item').' WHERE inumber=' . intval($itemid));
      $blog =& $manager->getBlog($blogid);
      $timeNow = $blog->getCorrectTime();
      $timeBoundary = $timeNow - ($this->iDays * 24 * 60 * 60);
      if ($timeItem < $timeBoundary) return true;
      return false;
   }

   function doTemplateVar(&$item) {
      global $member;
      $id=strval(intval($item->itemid));
      if (!($member->isLoggedIn() && $member->canAlterItem($id))) return;
      $query = sql_query('SELECT count(*) as total FROM '.$this->table_pending.' WHERE citem='.$id);
      $row=mysql_fetch_object($query);
      if ($row->total>0) echo $this->getOption('text7');
   }

   function getRSSLink() {
      global $CONF;
      return $CONF['IndexURL'] . 'action.php?action=plugin&amp;name=CommentControl&amp;type=rss';
   }

   // skinvar plugin can have a blogname as second parameter
   function doSkinVar($skinType, $what = '') {
      global $member, $CONF, $itemid, $blog;

      // RSS
      if ($what=='url') {
         if (!$member->isAdmin())
            return;
         else
            echo "<a href='".$this->getRSSLink()."' title='".$this->getOption('text4')."'><img src='".$this->getOption('blogpicture')."' alt='".$this->getOption('text4')."' /></a><br />";
         return;
      }

      // Report total number of pending messages
      if ($what == 'pending' && $skinType != "error") {
         if (!$member->isLoggedIn())  return;

         if ($member->isAdmin()) {
            $query = 'SELECT id FROM ' . $this->table_pending;
         } else {
            $query = 'SELECT a.id FROM ' . $this->table_pending . ' AS a, ' . sql_table('item') . ' AS b WHERE a.citem=b.inumber AND b.iauthor=' . $member->getID();
         }
         $res = sql_query($query);

         if (mysql_num_rows($res) == 0)
            echo $this->getOption('text5');
         else
            echo "<a href=\"" . $blog->getURL() . "/nucleus/plugins/commentcontrol/\">" . mysql_num_rows($res) . " " . $this->getOption('text6') . "</a><br />";
         return;
      }

      if ($skinType != 'item') return;

      // display comments pending approval
      if ($member->isLoggedIn()){
         if ($member->isAdmin()) {
            $query = 'SELECT * FROM ' . $this->table_pending . ' WHERE citem=' . intval($itemid);
         } else {
            $query = 'SELECT * FROM ' . $this->table_pending . ' AS a, ' . sql_table('item') . ' AS b WHERE citem=' .
                     intval($itemid) . ' AND a.citem=b.inumber AND b.iauthor=' . $member->getID();
         }
         $res = sql_query($query);
         $first=true;
         while ($o = mysql_fetch_object($res))
         {
            if ($first){
               echo $this->getOption('text1');
               echo '<ul>';
               $first=false;
            }
            $urlallow = $CONF['ActionURL'] . '?action=plugin&name=CommentControl&type=allow&id=' . intval($o->id);
            $urldeny = $CONF['ActionURL'] . '?action=plugin&name=CommentControl&type=deny&id=' . intval($o->id);
            echo '<li>';
            echo '<a href="'.htmlspecialchars($urlallow).'">[allow]</a>';
            echo '<a href="'.htmlspecialchars($urldeny).'">[deny]</a>';
            echo ' <strong>',htmlspecialchars($o->cuser),'</strong>';
            echo ' ', $o->cbody;
            echo '</li>';
         }
         if (!$first) echo '</ul>';
      }

      // display warning for non-logged users
      if ($what == 'warning') {
         // Do not display warning if the item is closed
         if ($o->iclosed == '1') return;

         if ($this->getOption('pendmember') == 'yes')
            $pending = $member->isAdmin();
         else
            $pending = $member->canLogin();

         if (!$member->isLoggedIn() || !$pending)
         {
            // submit order
            if (intRequestVar('pending') == 1) {
               echo $this->getOption('text2');
               return;
            }
         }
         if ($member->isLoggedIn())
            $strName = $member->getDisplayName();
         else
            $strName = cookieVar('comment_user');      
         // warning
         if ($this->needsVerification($itemid, $strName)) echo $this->getOption('text3');
      }
   }

   function putHeader() {   
      global $CONF;
      header ("Content-type: text/xml");
      echo '<'.'?xml version="1.0" encoding="'.$this->getOption('blogcode').'"?'.'>'."\n";
      echo "<rss version=\"2.0\">\n";
      echo "<channel>\n";
      echo "<title>".$CONF['SiteName']."</title>\n";
      echo "<link>".$CONF['IndexURL']."</link>\n";
      echo "<description>".$CONF['AdminEmail']."</description>\n";
      echo "<language>cs</language>\n";
      echo "<image>\n";
      echo "<url>/nucleus/nucleus.gif</url>\n";
      echo "<title>RSS feed of last 20 pending comments.</title>\n";
      echo "<link>".$this->getOption('bloguri')."</link>\n";
      echo "</image>\n";
      echo "<docs>http://backend.userland.com/rss</docs>\n";
   }

   function encode_xml($data){ return strip_tags(str_replace('</p>',"\n",str_replace('<br />',"\n",$data))); }

   function putComment($comment) {
      global $CONF;
      if ($comment->member > 0) {
         $result = sql_query("SELECT mname AS nick, murl AS link FROM ".sql_table('member')." WHERE mnumber = ".$comment->member);
         $member = mysql_fetch_object($result);
         $authorlink = $member->nick;
         $author = "Comment made by: " .$member->nick;
         $title=$comment->title;
      } else {
         $authorlink = $comment->user;
         if (!empty($comment->link)) $authorlink.=" : ".$comment->link;
         $author = "Comment made by: " .$comment->user;
      }
      echo "<item>\n";
      echo "<title>".$this->encode_xml($comment->title)."</title>\n";
      $link = createItemLink($comment->item);
      echo "<link>".$this->encode_xml($link)."#cmt".strval($comment->commentid)."</link>\n";
      $urlallow = $CONF['ActionURL'] . '?action=plugin&amp;name=CommentControl&amp;type=allow&amp;id=' . intval($comment->item);
      $urldeny = $CONF['ActionURL'] . '?action=plugin&amp;name=CommentControl&amp;type=deny&amp;id=' . intval($comment->item);
      $data = "Article: ".
         $this->encode_xml($comment->title).
         "\n\nComment ".
         $authorlink." :: \n".$this->encode_xml($comment->body).
         "\n\n".
         "Allow: $urlallow \n".
         "Deny: $urldeny \n";
      echo "<description>".$data."</description>\n";
      echo "<pubDate>".strval(date("r",$comment->ct))."</pubDate>\n";
      echo "</item>\n";
   }

   function putEnd() {
      echo "</channel>\n";
      echo "</rss>\n";
   }

   function doAction($actionType) {
      global $CONF, $member;
      if (!$member->isLoggedIn()) return 'Sorry. not allowed';

      if ($actionType == 'rss'){
         $this->putHeader();
         $result = sql_query('select c.id as commentid, UNIX_TIMESTAMP(c.ctime) as ct, c.cbody as body, c.cuser as user, c.cmember as member, i.ititle as title, i.inumber as item from '.$this->table_pending .' c, '.sql_table('item').' i where i.inumber=c.citem');
         while ($row = mysql_fetch_object($result)) $this->putComment($row);
         $this->putEnd();
      }

      // These are common between allow and deny action.
      $id = requestVar('id');
      // get data from pending table
      $query = 'SELECT * FROM ' .$this->table_pending. ' WHERE id=' . intval($id);
      $res = sql_query($query);
      $itemid = -1;
      while ($o = mysql_fetch_object($res))
      {
         $name      = addslashes($o->cuser);
         $itemid      = intval($o->citem);
         $blogid      = intval($o->cblog);
         $ip      = addslashes($o->cip);
         $host      = addslashes($o->chost);
         $timestamp   = addslashes($o->ctime);
         $body      = addslashes($o->cbody);
         $memberid   = intval($o->cmember);
         $url      = addslashes($o->cmail);
         $email      = addslashes($o->cemail);
      }

      if (!$member->isAdmin()) {
         $query = 'SELECT * FROM ' . sql_table('item') . ' WHERE inumber=' . $itemid . ' AND iauthor=' . $member->getID(); 
         $res = sql_query($query);
         if (mysql_num_rows($res) == 0) {
            return 'Sorry. You do not have permission to moderate this comment';
         }
      }

      if ($actionType == 'allow' || $actionType == 'allowadmin')
      {
         // add data to comments table
         if (getNucleusVersion() >= 330)
         {
            $query = 'INSERT INTO '.sql_table('comment').' (CUSER, CMAIL, CEMAIL, CMEMBER, CBODY, CITEM, CTIME, CHOST, CIP, CBLOG) '
               . "VALUES ('$name', '$url', '$email', $memberid, '$body', $itemid, '$timestamp', '$host', '$ip', '$blogid')";
         } else {
            $query = 'INSERT INTO '.sql_table('comment').' (CUSER, CMAIL, CMEMBER, CBODY, CITEM, CTIME, CHOST, CIP, CBLOG) '
               . "VALUES ('$name', '$url', $memberid, '$body', $itemid, '$timestamp', '$host', '$ip', '$blogid')";
         }
         sql_query($query);

         // need to trigger PostAddComment here....
         $query = 'SELECT * FROM ' . sql_table('comment') . ' WHERE ctime=\'' . $timestamp .  '\'';
         $res= sql_query($query);
         $commentid = mysql_fetch_object($res);
         $commentid = $commentid->cnumber;

         $comment = array(
               'user' => $name,
               'userid' => $url,
               'email' => $email,
               'body' => $body,
               'host' => $host,
               'ip' => $ip,
               'memberid' => $memberid,
               'timestamp' => $timestamp,
               'itemid' => $itemid,
               'blogid' => $blogid
               );

         global $manager;
         $manager->notify('PostAddComment',array('comment' => &$comment, 'commentid' => &$commentid));

         // ItemURL seem to be seted in the event, that breaks re-direction
         $CONF['ItemURL'] = '';

         // delete data in pending table
         $query = 'DELETE FROM ' . $this->table_pending . ' WHERE id=' . intval($id);
         sql_query($query);

         if ($itemid != -1)
         {
            if ($actionType == 'allow')
               $url = createItemLink($itemid);
            else
               $url = $CONF['IndexURL'].'nucleus/plugins/commentcontrol/';
            header('Location: ' . $url);         
            exit();
         } else {
            echo 'allowed';
         }
      }

      if ($actionType == 'deny' || $actionType == 'denyadmin')
      {
         // delete data in pending table
         $query = 'DELETE FROM ' . $this->table_pending . ' WHERE id=' . intval($id);
         sql_query($query);      
         if ($itemid != -1)
         {
            if ($actionType == 'deny')
               $url = createItemLink($itemid);
            else
               $url = $CONF['IndexURL'].'nucleus/plugins/commentcontrol/';
            header('Location: ' . $url);         
            exit();
         } else {
            echo 'denied';
         }
      }
   }

   function doTemplateCommentsVar(&$item, &$comments, $strLinkText) {
      global $member, $manager, $CONF;
      if (!($member->isLoggedIn() && $member->isAdmin())) return;
      $commentId = intval($comments['commentid']);
      if ($member->canAlterComment($commentId))
         echo "[<span class='smaller'><a onclick=\"window.open(this.href, 'popupeditwindow', 'width=720,height=560,scrollbars,resizable'); return false;\" href=\"".$CONF['AdminURL']."?action=commentedit&amp;commentid=".$commentId."\">",$strLinkText,"</a></span>]";

   }       

   /**
    * @returns
    *      array(
    *         array(
    *            'comment', 'itemtitle', 'itemid', 'user', 'userid', 'email', 'memberid', 'timestamp', 'host', 'ip', 'id'
    *         )
    *      )
    */
   function _getPendingInfo() {
      $aResult = array();

      $query = 'SELECT id, ititle, citem, cbody, cuser, cmail, cmember, UNIX_TIMESTAMP(ctime) as timestamp, chost, cip FROM ' . $this->table_pending . ', ' . sql_table('item') . ' WHERE inumber=citem';
      $res = sql_query($query);
      while ($o = mysql_fetch_object($res))
      {
         array_push($aResult, array(
                  'itemtitle'    => $o->ititle,
                  'itemid'       => intval($o->citem),
                  'comment'       => $o->cbody,
                  'user'         => $o->cuser,
                  'userid'      => $o->cmail,
                  'email'         => $o->cemail,
                  'memberid'      => intval($o->cmember),
                  'timestamp'      => intval($o->timestamp),
                  'host'         => strip_tags($o->chost),
                  'ip'         => strip_tags($o->cip),
                  'id'         => intval($o->id)
                  ));
      }
      return $aResult;
   }
}
?>
