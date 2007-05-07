<?php

/*
  History:
  v0.9a - Added mail notification to edit item and addItemForm
  v0.10 - Added unInstall()
        - Fixed edit item mail notification
  v0.11 - Fixed form width to size
  v0.12 - Use sql_table, add supportsFeature
  v0.13 - Merged with Adeas's multiple blogs support and better emails code
        - Fixed subscribe/unsubscribe re-direction
  v0.14 - verbaljam's doForm suggestion
        - Added subscribed/unsubscribed message 
        - code indentation
        - subscribe/unsubscribe notice, with link to unsubscribe
  v0.15 - Removed post body from post update notice.... it didn't work in PreUpdateItem event... we need to use PostUpdateItem (not exist currently, tracker opened.)
        - per item comment notification, allow to monitor comments for a post, add <%NotifyMe(itemSub)%> for comment subscription
	- use Bcc: for receiver list
	- send extended body in notification email
	- override sending notification when add new post if it is saved as draft
	- fix XHTML 1.0
	- Added comment notification to author in add/edit item, default option to yes
	- Fixed form action redirection bug
	- Fixed future post bug (ignore future post)
  v0.16 - Added plugin quick menu (currently empty menu)
        - Improved comment subscription email
	- Fixed sql_table
        - Fixed subscribe over subscribe bug (wrong subscribe message sent)
	- Set minimum Nucleus version to 2.5
        - Fixed subscribe/unsubscribe message
  v0.17 - no comment subscription for closed item
        - subscriber management: list all subscription w/ delete action
  v0.18 - do not send email when there is no one to sent to
        - Fixed subscription management access problem in fancyURL mode
        - Fixed 2nd blog subscription bug
        - Improved accesskey id code in subscription
        - remove <%image%> tag and such
        - list subscribers in admin menu
        - delete subscribers from admin management
        - notify skin for customisible subscribe/unsubscribe message
        - fixes comment notification bug
  v0.20 - mail() empty $to warning/error
        - fix comment notification missing itemid problem (compatibility issue with Nucleus 3.0)
        - fix xhtml
        - add table removal option
        - add admin menu subscriber list sorting
  v0.21 - fix XHTML 1.0
  v0.30 - fix getTableList() (kogger)
  v0.40 - add SMTP return-path mail header
  v0.41 - add @ for mail()
  v0.50 - fix comment item url missing indexURL
        - fix return path error
  v0.51 - add mass delete function 
  v0.52 - add actionlog on mail sending (for debugging)
        - email authenication for subscription
          - add %##AUTHLINK##%
          - doAction() support for auth type 
          - periodic cleanup of outdate unauth subscription
  v0.60 - more debugging to actionlog
  v0.62 - fix timezone offset problem
  v0.63 - fix timezone offset problem again....
  v0.64 - HTML 1.0 Strict fix
  v0.65 - use sql_query()
  v0.66 - removed ID column from INSERT, which is not needed and seem caused some problem to Window setup
        - remove subscription when item deleted
        - fix FancyURL link

  admun TODO:
  - add/edit admin fucntion to add new subscriber (so we can remove notify subscriber function)
  - support multiple blogs (which has its own domain)
  - check invalid subscription id
  - pagination the admin menu (instead of show all)
  - localization for admin area, using a language file mechanian?
  - XHTML 1.0 strict
  - delete subscription return to admin menu
  - add
  MIME-Version: 1.0
  Content-Type: text/plain; charset=ISO-8859-2
  Content-Transfer-Encoding: 8bit
  in email (how to control charset???)

  - add template editing in admin menu, using editFile() from NP_SkinFiles?

  - add post comment auto subscribe function (added a checkbox in comment posting template)
  - tested &$comment passed from the PostAddComment event
  - captcha for NotifyMe

Other ideas
=============
  - edit subscribers from user or admin management
  - per blog default notification setting
  - action parameter should be lower case
  - optimize code, eliminate clone code (ie sending mail)


  some idea to deal with future post...
    - add warning to notice that when this post is available??
    - batch the notification for future post (need NP_Batch... coming in the future)
    - or future post "appearing" event
*/

// to make this plugin works on Nucleus versions <=2.0 as well
if (!function_exists('sql_table'))
{
  function sql_table($name) { return 'nucleus_' . $name; }
}

class NP_NotifyMe extends NucleusPlugin {
    function getMinNucleusVersion() {return 250;}
    function getName() {return 'Notify subscribers by mail';}
    function getAuthor() {return '-=Xiffy=- (Appie Verschoor), mod by Adeas, admun (Edmond Hui)';}
    function getURL() {return 'http://xiffy.nl/weblog/';}
    function getVersion() {return '0.66';}

    function getTableList () {
      return array(sql_table('plugin_notifyaddress'));
    }

    function supportsFeature($feature) {
      switch($feature) {
        case 'SqlTablePrefix':
          return 1;
        default:
          return 0;
      }
    }

    function install() {
      // create the table to hold the addresses ...
      // Table structure: unique key#, blog id#, itemid, email address
      // Note: itemid of 0 == invalid, used as a null value for blog item subscription (cannot be null)
      sql_query('CREATE TABLE IF NOT EXISTS ' .
	  sql_table('plugin_notifyaddress') . 
	  '(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
	    email varchar(100) not null,
	    blogID int(3) not null,
	    itemid int(11) not null,
            validate int default 1 not null)
	    '); 

      $this->createOption('SendEnabled','Is sending notification enabled by default when adding new item?','yesno','yes');
      $this->createOption('SendCommEnabled','Subscribe comment notification for author when adding new item?','yesno','yes');
      $this->createOption('SubscribeButtonText','The caption on the button (label)','text','[Un-] Subscribe');
      $this->createOption('Titleprefix','This will be prefixed to the itemtitle as the title for the email','text','Updated:');
      $this->createOption('updateSender','This is the email address that will be used as the sender','text','updateNotify@me.com');
      $this->createOption('Formlabel','The label displayed on the form','text','Subscribe for updates');
      $this->createOption('deleteOnUninstall','Deleted the notification list on uninstall?','yesno','yes');
    }

    function unInstall() {

      if ($this->getOption('deleteOnUninstall') == "yes")
      {
        sql_query('DROP TABLE '.sql_table('plugin_notifyaddress'));
      }
    }

    // a description to be shown on the installed plugins listing
    function getDescription() {
      return 'This plugin provides a notify me by email function to your blog, which sends the new/edited items to subscribed users. It also supports per blog subscription and per item comment notification.';
    }

    // subscribe to PostAddItem and AddItemFormExtras
    function getEventList() {
      return array('PreAddItem', 'PostAddItem', 'PreUpdateItem', 'AddItemFormExtras','EditItemFormExtras', 'PostAddComment', 'QuickMenu',
                   'PostDeleteItem');
    }

    function hasAdminArea() {
      return 1;
    }

    function event_QuickMenu(&$data) {
      global $member;
      if (!($member->isLoggedIn() && $member->isAdmin())) return;

      array_push(
	  $data['options'],
	  array(
	    'title' => 'Notification Management',
	    'url' => $this->getAdminURL(),
	    'tooltip' => 'Manage comment/blog notification subscription.'
	    )
	  );
    }

    function event_PreAddItem($data) {
      global $manager;

      $this->itime = $data['timestamp'];
      $this->draft = "no";

      $blog =& $manager->getBlog($data['iblog']);
      $this->time = $blog->getCorrectTime();

      if ($data['draft'] == '1') {
        $this->draft = "yes";
      }
    }

    // Send mail notification when new item added
    function event_PostAddItem($data) {
      global $manager, $CONF, $DIR_PLUGINS;

      $query=sql_query('SELECT iblog,iauthor FROM '.    // pulls the blog ID# from the mySQL database
	  sql_table('item').                      // Thanks Radek for the code 
	  ' WHERE inumber='.$data['itemid']); 
      $row=mysql_fetch_object($query); 
      $IDnum=$row->iblog; 
      $author=$row->iauthor;
      $query=sql_query('SELECT bname FROM '. 
	  sql_table('blog'). 
	  ' WHERE bnumber='.$IDnum); 
      $row=mysql_fetch_object($query); 
      $BlogName=$row->bname; 

      $send_email = requestVar('send_email');
      $sub_comm = requestVar('sub_comm');
      if ($sub_comm == "on") {
	$mem = new MEMBER;
	$mem->readFromID($author);
	$a_email = $mem->getEmail();

	$query = "INSERT INTO ".sql_table('plugin_notifyaddress')." (email, blogID, itemid) VALUES ('" . $a_email .  "', '" . $IDnum . "', '" . $data['itemid'] . "')";
	sql_query($query);
      }

      $future = "no";

      if ($this->itime > $this->time) $future = "yes";
      
      if ($send_email == "on" && $this->draft == "no" && $future == "no") {

	// get a handle to notify object
	// add all email adress to a semicolon seperated list ..
	$emailquery = "SELECT email FROM ".sql_table('plugin_notifyaddress')." WHERE blogID='" .$IDnum. "' AND itemid='0' AND validate=1"; 
	$address = sql_query($emailquery);
	$list = $row->email;
	while ($row = mysql_fetch_object($address)){
	  $list .= "," . $row->email;
	}

	// don't sent email if there is no one to sent to
	if ($list == "") {
	  return;
	}

	$itemid = $data['itemid'];
	$item =& $manager->getItem($itemid, 0, 0);
	$title = $this->getOption('Titleprefix') ." ". stripslashes($item['title'])." - ".$BlogName; 
	$body = file_get_contents($DIR_PLUGINS.'notifyme/addItemMail.templete');
	$body = str_replace('%##TITLE##%', stripslashes($item['title']), $body);
	$body = str_replace('%##BODY##%', stripslashes($item['body']), $body);
	$body = str_replace('%##MORE##%', stripslashes($item['more']), $body);

        // need to reset the ItemURL so createItemLink work properly
        $blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
        $CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());
        $url = createItemLink($itemid);

	$body = str_replace('%##URL##%', $url, $body);
	$sender = $this->getOption('updateSender'); 

	// replace image/popup/media tags in body
	$body = eregi_replace('<\%image\(.*>', '[image]', $body);
	$body = eregi_replace('<\%popup\(.*>', '[image]', $body);
	$body = eregi_replace('<\%media\(.*>', '[media]', $body);

	// email notification 
	$headers = "Content-type: text/html; charset=iso-8859-1\n"; 
	$headers .= "From: ".$sender."\n"; 
	$headers .= "Bcc: ".$list."\n"; 
	$headers .= "X-Mailer: PHP/" . phpversion(); 
        // Some user suggested this might help for those having problem sending email with this...
        $headers .= "\nReturn-Path: " . $CONF['AdminEmail'] . "\n";

        ACTIONLOG::add(INFO, 'NotifyMe add item: Sending notification to ' . $sender);
        $return = @mail("$sender","$title","$body","$headers"); 
      } 
      else
      {
        ACTIONLOG::add(INFO, 'NotifyMe add item: Skipping email notification (send_email=' . $send_email . ' draft=' . $this->draft 
                             . ' future=' . $future . 
                             ' itime='. $this->itime . ' time=' . $time .
                             ')');
      }
    }

    // Send mail notification when item edited
    function event_PreUpdateItem($data) {
      global $manager, $CONF, $blog, $DIR_PLUGINS;

      $query=sql_query('SELECT iblog,iauthor FROM '.    // pulls the blog ID# from the mySQL database
	  sql_table('item').                      // Thanks Radek for the code 
	  ' WHERE inumber='.$data['itemid']); 
      $row=mysql_fetch_object($query); 
      $IDnum=$row->iblog; 
      $author=$row->iauthor;
      $query=sql_query('SELECT bname FROM '.
	  sql_table('blog'). 
	  ' WHERE bnumber='.$IDnum); 
      $row=mysql_fetch_object($query); 
      $BlogName=$row->bname; 

      $send_email = requestVar('send_email');
      $sub_comm = requestVar('sub_comm');
      if ($sub_comm == "on") {
	$mem = new MEMBER;
	$mem->readFromID($author);
	$a_email = $mem->getEmail();

	$query = "SELECT * FROM ".sql_table('plugin_notifyaddress')." WHERE email='" . $a_email . "' AND itemid=" . $data['itemid'];
	$res = sql_query($query);

	// Do not add email address if already exist.... maybe should use ignore from MySQL 4.1.*??
	if (mysql_num_rows($res) == 0) {
	  $query = "INSERT INTO ".sql_table('plugin_notifyaddress')." (email, blogID, itemid) VALUES ('" . $a_email .  "', '" . $IDnum . "', '" . $data['itemid'] . "')";
	  sql_query($query);
	}
      }

      // if this event occurs, send an email to all subscribers
      if ($send_email == "on") {
	// get a handle to notify object
	// add all email adress to a semicolon seperated list ..
	$emailquery = "SELECT email FROM ". sql_table('plugin_notifyaddress') . " WHERE blogID = '" .$IDnum. "' AND itemid='0' AND validate=1"; 
	$address = sql_query($emailquery);
	$row = mysql_fetch_object($address);
	$list = $row->email;
	while ($row = mysql_fetch_object($address)){
	  $list .= "," . $row->email;
	}

	// don't sent email if there is no one to sent to
	if ($list == "") {
	  return;
	}

	// Note: It would be nice to be able to include the message in the email, but there is no PostUpdateItem event....
	$itemid = $data['itemid'];
	$ititle = $data['title'];
	$title = $this->getOption('Titleprefix') . stripslashes($ititle)." - ".$BlogName; 
        $blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
        $CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());
        $url = createItemLink($itemid);

        $body = file_get_contents($DIR_PLUGINS.'notifyme/editItemMail.templete');
        $body = str_replace('%##TITLE##%', $title, $body);
        $body = str_replace('%##URL##%', $url, $body);
	$sender = $this->getOption('updateSender'); 

	// email notification 
	$headers = "Content-type: text/html; charset=iso-8859-1\n"; 
	$headers .= "From: ".$sender."\n"; 
	$headers .= "Bcc: ".$list."\n"; 
	$headers .= "X-Mailer: PHP/" . phpversion(); 
        // Some user suggested this might help for those having problem sending email with this...
        $headers .= "\nReturn-Path: " . $CONF['AdminEmail'] . "\n";

        ACTIONLOG::add(INFO, 'NotifyMe update item: Sending notification to ' . $sender);
	$return = @mail("$sender","$title","$body","$headers"); 
      }
    }

    // add the option send / not send email during item edit...
    function event_EditItemFormExtras($data) {
      $on = $this->getOption('SendEnabled');
      ?>
	<h3>NotifyMe</h3>

			<p>
				<input type="checkbox" id="plug_send_email" name="send_email" />
				<label for="plug_send_email">Send notification to subscribers</label>
			<br />
				<input type="checkbox" <? if ($con=="yes") { echo 'checked="checked"';} ?> id="plug_send_comm_email" name="sub_comm" />
				<label for="plug_send_comm_email">Subscribe to comment notification</label>
			</p>
		<?
	}

    // add the option send / not send email ...
    function event_AddItemFormExtras($data) {
      $on = $this->getOption('SendEnabled');
      $con = $this->getOption('SendCommEnabled');
      ?>
	<h3>NotifyMe</h3>

			<p>
				<input type="checkbox" <? if ($on=="yes") { echo 'checked="checked"';} ?> id="plug_send_email" name="send_email" />
				<label for="plug_send_email">Send notification to subscribers</label>
			<br />
				<input type="checkbox" <? if ($con=="yes") { echo 'checked="checked"';} ?> id="plug_send_comm_email" name="sub_comm" />
				<label for="plug_send_comm_email">Subscribe to comment notification</label>
			</p>
		<?
    }

    function event_PostAddComment($comment) {
      if ($comment['comment']['itemid'] != "") {
        $itemid = $comment['comment']['itemid'];
      } else {
        $itemidquery = "SELECT citem FROM " . sql_table('comment') . " WHERE cnumber=" . $comment['commentid'];
        $itemidres = sql_query($itemidquery);
        $itemrow = mysql_fetch_object($itemidres);
        $itemid = $itemrow->citem;
      }

      $emailquery = "SELECT email FROM ".sql_table('plugin_notifyaddress')." WHERE itemid='".$itemid."' AND validate=1";
      $address = sql_query($emailquery);
      $list = $row->email;
      while ($row = mysql_fetch_object($address)){
	$list .= "," . $row->email;
      }

      // don't sent email if there is no one to sent to
      if ($list == "") {
        return;
      }

      global $CONF, $manager, $blog, $DIR_PLUGINS;
      $blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
      if (!$CONF['ItemURL']) $CONF['ItemURL'] = $blog->getURL();
      $title = $this->getOption('Titleprefix') ." - new comment for " . $blog->getName(); 
      $comment = stripslashes($comment['comment']['body']); 
      $blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
      $CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());
      $url = createItemLink($itemid);

      $body = file_get_contents($DIR_PLUGINS.'notifyme/commentMail.templete');
      $body = str_replace('%##COMMENT##%', $comment, $body);
      $body = str_replace('%##URL##%', $url, $body);
      $sender = $this->getOption('updateSender'); 

      // email notification 
      $headers = "Content-type: text/html; charset=iso-8859-1\r\n"; 
      $headers .= "From: ".$sender."\r\n"; 
      $headers .= "Bcc: ".$list."\r\n"; 
      $headers .= "X-Mailer: PHP/".phpversion()."\r"; 
      // Some user suggested this might help for those having problem sending email with this...
      $headers .= "\nReturn-Path: " . $CONF['AdminEmail'] . "\n";

      $return = @mail("$sender","$title","$body","$headers"); 
    }

    function event_PostDeleteItem($data) {
      sql_query("DELETE FROM ".sql_table('plugin_notifyaddress')." WHERE itemid='".$data['itemid']."'");
    }

    // action handling
    function doAction($actionType) {
      global $CONF, $blog, $manager, $DIR_PLUGINS;

      $email = requestVar('emailaddress');

      // Show the subscriber login form
      if ($actionType == "subadmin") {
	echo "Please enter the email address and access key (provided in the subscription email)"
	. "<form method=\"post\" action=\"".$CONF['ActionURL']."\" />\n" 
	. "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n" 
	. "<input type=\"hidden\" name=\"name\" value=\"NotifyMe\" /> \n" 
	. "<input type=\"hidden\" name=\"type\" value=\"subadmin2\" /> \n" 
	. "Email: <input type=\"text\" name=\"emailaddress\" value=\"\" /><br/>\n" 
	. "Access Key: <input type=\"text\" name=\"key\" value=\"\" /><br/>\n" 
	. "<input type=\"submit\" class=\"button\" value=\"Submit\" />"
	. "</form>\n";
        return;
      } 

      // Show the list of subscription for a subscriber
      if ($actionType == "subadmin2") {
        $key = requestVar('key');

        $query = "SELECT * FROM ".sql_table('plugin_notifyaddress')." WHERE email = '" .$email."' AND id = '" . $key . "' AND validate=1";
        $result = sql_query($query); 
	if (mysql_num_rows($result) < 1) {
	  echo "Login failed, invalid email/key";
	  return;
	}

        echo "<h2>Notification Management</h2>";
	echo "Here's a list of all notification subscriptions for " . $email . ":<br/>";

        $query = "SELECT * FROM ".sql_table('plugin_notifyaddress')." WHERE email = '" .$email."'";
        $rows = sql_query($query); 
	echo "<table>\n";
	echo "<td>Name</td><td>Type</td><td></td>";

        while($row = mysql_fetch_object($rows)) {
	  $redirecturl = 'noredirect';
	  echo "<tr>";
	  if ($row->itemid == 0) {
	    $delAction = $CONF['ActionURL'] . '?action=plugin&name=NotifyMe&type=form&emailaddress=' . $row->email
	      . '&redirecturl=' . $redirecturl . '&currBlogId=' . $row->blogID;
	    echo "<td><a href=\"./". createBlogidLink($row->blogID) . "\">" . getBlogNameFromID($row->blogID) . "</a></td><td>Blog</td><td>[<a href=\"".$delAction."\">unsubscribe</a>]</td>\n";
	  } else {
	    $item =& $manager->getItem($row->itemid, 0, 0);
	    $delAction = $CONF['ActionURL'] . '?action=plugin&name=NotifyMe&type=form&emailaddress=' . $row->email
	      . '&redirecturl=' . $redirecturl . '&currBlogId=' . $row->blogID . '&itemid=' . $row->itemid . $emailenabled; 
	    echo "<td><a href=\"". createItemLink($row->itemid) ."\">". $item['title'] . "</a></td><td>Item (".$row->itemid.")</td><td>[<a href=\"".$delAction."\">unsubscribe</a>]</td>\n";
	  }
	  echo "</tr>";
	}
	echo "</table>\n";

        return;
      }

      // Subscriber authenication
      if ($actionType == "auth") {
        $authkey = requestVar('authkey');

        $query = "SELECT id FROM " . sql_table('plugin_notifyaddress') . " WHERE validate=" . $authkey;
        $check = sql_query($query);
        if (mysql_num_rows($check) == 1)
        {
          $row=mysql_fetch_object($check);
	  $query = "UPDATE " . sql_table('plugin_notifyaddress') . " SET validate=1" . " WHERE id=" . $row->id;
          sql_query($query);
        }

        echo "Thanks for subscribing, enjoy.";
        return;
      }

      // Defualt subscribe/unsubscribe action
      $IDnum = intRequestVar('currBlogId'); // IDnum = 0 if no such var
      $redirect = requestVar('redirecturl');
      $itemid = intRequestVar('itemid'); // itemid = 0 if no such var
      $validate = rand()+2;

      if ($itemid == '0') {
	$subType = 'blogSub';
      } else {
	$subType = 'itemSub';
      }

      $pluginquery = "SELECT email FROM ".sql_table('plugin_notifyaddress')." WHERE email= '" .$email. "' AND itemid='".$itemid."' and BlogId='" . $IDnum . "'";
      $check = sql_query($pluginquery); // no errors please ...

      if (mysql_num_rows($check) == 0) {
	// new subscriber, Welcome ...
	$ok = isValidMailAddress($email);
	if ($ok) {
	  $actionquery = "INSERT INTO ".sql_table('plugin_notifyaddress')." (email, blogID, itemid, validate) VALUES ('" . $email .  "', '" . $IDnum . "', '" . $itemid . "', '" . $validate . "')";
	  $sub = 0;
	} else {
	  return ("This is not a valid email address!");
	}
      } else {
	// sorry to see you go ...
	$actionquery = "DELETE FROM ".sql_table('plugin_notifyaddress')." WHERE email = '" .$email . "' AND blogID='" .$IDnum ."' AND itemid='" . $itemid . "'";
	$sub = 1;
      }

      // execute actionquery
      sql_query($actionquery);
      $insertid = mysql_insert_id();

      $blogName = getBlogNameFromID($IDnum);
      if ($redirect != 'noredirect') {
	$sender = $this->getOption('updateSender');
	if ($sub == '0') {
	  $title = "$blogName - NotifyMe subscription";
          $authlink = $CONF['ActionURL'] . '?action=plugin&name=NotifyMe&type=auth&authkey=' . $validate;
	  if ($subType == 'itemSub') {
	    $item =& $manager->getItem($itemid, 0, 0);
	    $ititle = $item['title'];
	    $unSubLink = $CONF['ActionURL'] . '?action=plugin&name=NotifyMe&type=form&emailaddress=' . $email . '&redirecturl=noredirect&currBlogId=' . $IDnum . '&itemid=' . $itemid;
	    $body = file_get_contents($DIR_PLUGINS.'notifyme/subscribeItemMail.templete');
            $body = str_replace('%##TITLE##%', $ititle, $body);
            $body = str_replace('%##LINK##%', $unSubLink, $body);
            $body = str_replace('%##EMAIL##%', $email, $body);
            $body = str_replace('%##AUTHLINK##%', $authlink, $body);
	  } else {
	    $unSubLink = $CONF['ActionURL'] . '?action=plugin&name=NotifyMe&type=form&emailaddress=' . $email . '&redirecturl=noredirect&currBlogId=' . $IDnum;

	    $manLink = $CONF['ActionURL']."?action=plugin&name=NotifyMe&type=subadmin";
	    $body = file_get_contents($DIR_PLUGINS.'notifyme/subscribeBlogMail.templete');
            $body = str_replace('%##EMAIL##%', $email, $body);
            $body = str_replace('%##BLOG##%', $blogName, $body);
            $body = str_replace('%##KEY##%', $insertid, $body);
            $body = str_replace('%##LINK##%', $unSubLink, $body);
            $body = str_replace('%##MLINK##%', $manLink, $body);
            $body = str_replace('%##AUTHLINK##%', $authlink, $body);
	  }
	} else {
	  $title = "$blogName - NotifyMe unsubscription";
	  $body = file_get_contents($DIR_PLUGINS.'notifyme/unsubscribeMail.templete');
          $body = str_replace('%##EMAIL##%', $email, $body);

	  if ($subType == 'itemSub') {
	    $item =& $manager->getItem($itemid, 0, 0);
	    $ititle = $item['title'];
            $body = str_replace('%##TITLE##%', $ititle, $body);
	  } else {
            $body = str_replace('%##TITLE##%', $blogName, $body);
	  }
	}

	$headers = "Content-type: text/html; charset=iso-8859-1\n"; 
	$headers .= "From: ".$sender."\n"; 
	$headers .= "X-Mailer: PHP/" . phpversion(); 
        // Some user suggested this might help for those having problem sending email with this...
        $headers .= "\nReturn-Path: " . $CONF['AdminEmail'] . "\n";

	$return = @mail("$email","$title","$body","$headers");

	// strip parameters from previous action...
	$redirect = preg_replace("/\?.subscribe=./", "", $redirect);
	$redirect = preg_replace("/.subscribe=./", "", $redirect);
	$redirect = preg_replace("/&subtype=(itemSub|blogSub)/", "", $redirect);
	$redirect = preg_replace("/&add=(.*)/", "", $redirect);

	if (strstr('?', $redirect) == '') {
	  $sub_string = "?&subscribe=";
	} else {
	  $sub_string = "subscribe=";
	}

        header('Location: ' . $redirect . $sub_string . $sub . '&subtype=' . $subType . '&add=' . $email);
      }
      else {
        echo "You are now unsubscribed from $blogName";
      }
    }

    // generate the subscribe box, and display subscription status
    function doSkinVar($skinType, $form) {
      global $manager, $blog, $CONF, $DIR_PLUGINS;

      $ButtonCaption = $this->getOption('SubscribeButtonText');
      $Formlabel     = $this->getOption('Formlabel');		
      $currBlogId    = $blog->getID(); 
      $sub           = RequestVar('subscribe');
      $add           = RequestVar('add');
      $subType       = RequestVar('subtype');
      if ($form == '') $form = 'blogSub';

      $this->doForm($ButtonCaption,$Formlabel,$currBlogId,$sub,$add,$form,$subType);
    }

    // HTML Functions
    function doForm($ButtonLabel,$Label, $currBlogId, $sub, $add, $form,$subType) {
        global $CONF, $itemid, $manager;


	// echo "sub=" . $sub . " subType=" . $subType . " form=" . $form . "</br>";

	  if ($form == 'itemSub') {
            $item =& $manager->getItem($itemid, 0, 0);
	    if ($item['closed'] == '1') {
	      return;
	    }
	  }

	  // display subscribe/unsubscribe message
	  if ($sub != '' && $subType != '' && $subType == $form) {
	    if ($sub == '0')
	      $message = '<i>Thank you, ' . $add . ' subscribes successfully</i><br /><br />';
	    else if ($sub == '1')
	      $message = '<i>Thank you, ' . $add . ' unsubscribes successfully</i><br /><br />';
	    else
	      $message = '<i>No action perfromed</i><br />';

	    echo $message;
	  }

	  $redirect = htmlentities(serverVar('REQUEST_URI'));

	  if ($form == 'itemSub') {
	    echo "Enter email address to subscribe to comment on this item<br />"
	      . "<form method=\"post\" action=\"".$CONF['ActionURL']."\">\n" 
              . "<div class=\"NotifyMe\">\n" 
	      . "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n" 
	      . "<input type=\"hidden\" name=\"name\" value=\"NotifyMe\" /> \n" 
	      . "<input type=\"hidden\" name=\"type\" value=\"form\" /> \n" 
	      . "<input type=\"text\" name=\"emailaddress\" value=\"@\" />\n" 
	      . "<input type=\"hidden\" name=\"redirecturl\" value=\"" .  $redirect . "\" />" 
	      . "<input type=\"hidden\" name=\"currBlogId\" value=\"" .$currBlogId . "\" />" 
	      . "<input type=\"hidden\" name=\"itemid\" value=\"" . $itemid .  "\" />"
	      . "<input type=\"submit\" class=\"button\" value=\"Subscribe to this Item\" />"
	      . "</div>"
	      . "</form>\n";
	  } else {
	    echo "<form method=\"post\" action=\"".$CONF['ActionURL']."\">\n" 
              . "<div class=\"NotifyMe\">\n" 
	      . "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n" 
	      . "<input type=\"hidden\" name=\"name\" value=\"NotifyMe\" /> \n" 
	      . "<input type=\"hidden\" name=\"type\" value=\"form\" /> \n" 
	      . $Label 
	      . "<input type=\"text\" name=\"emailaddress\" value=\"@\" />\n" 
	      . "<input type=\"hidden\" name=\"redirecturl\" value=\"" .$redirect . "\" />" 
	      . "<input type=\"hidden\" name=\"currBlogId\" value=\"" .$currBlogId . "\" />" 
	      . "<input type=\"submit\" class=\"button\" value=\"" .$ButtonLabel ."\" />"
	      . "</div>"
	      . "</form>\n";
	  }

	    echo "<a href=\"" . $CONF['ActionURL'] . "?action=plugin&amp;name=NotifyMe&amp;type=subadmin\">Click here to manage subscription</a>";
    }
}
?>
