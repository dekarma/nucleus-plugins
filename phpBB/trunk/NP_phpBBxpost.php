<?php
/**
 * NP_phpBBvar version 1.0 is a plugin for the Nucleus CMS.
 * 
 * This plugin depends on (and extends) the NP_phpBB plugin.  Its purpose is to 
 * provide the ability to cross-post from the nucleus weblog to the phpBB forum.
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 * 
 * In addition to being a plugin for an open source program, this plugin also
 * uses functions and classes from several libraries in the phpBB codebase. 
 * Like Nucleus, phpBB is also released under the GNU General Public License.
 *
 * Part of this usage involves the copying of functions from
 * includes/functions.php.  This is necesitated by namespace confilcts, one
 * involving the redirect() function and the other involving the
 * Template/TEMPLATE class.
 *
 * A number of phpBB libraries are includ()ed from within the plugin. 
 * NP_phpBBxpost::_can_cross_post() uses includes/auth.php and
 * NP_phpBBxpost::_do_cross_post() uses includes/functions_post.php and
 * includes/bbcode.php.  Additionally large amounts of logic in NP_phpBBxpost
 * are borrowed from posting.php, with small pieces inspired by NP_punBB, and a
 * few bits of the doTemplateVar logic stolen from the Nucleus COMMENTS and
 * COMMENTACTIONS classes
 * 
 * NP_phpBB is used to establish the phpBB environment and the includes and
 * borrowed logic is used to create/update/delete posts and topics in the phpBB
 * database.
 **/
/**
 * @license http://nucleuscms.org/license.txt GNU General Public License
 * @copyright Copyright (C) 2005 Andrew Black (A.K.A Frankenstein)
 **/
// Todo?: permit cross posting as sticky/announcement?
// Todo?: treat userOverride as yes for superadmin?
// Todo: Don't crosspost drafts.
// Todo: Create cross post as user creating entry, not as user logged in (not always same).
// Todo: Handle failure to create table

class NP_phpBBxpost extends NucleusPlugin {
   function getName() { return 'phpBB Cross posting'; }
   function getDescription() { return 'phpBB integration module.  Provides the ability to cross post Nucleus entries to the phpBB forum.'; }
   function getAuthor() { return 'Andrew Black'; }
   function getURL() { return ''; }
   function getVersion() { return '1.0'; }
   function getMinNucleusVersion() { return 320; }
   function supportsFeature($what) {
      switch($what)
      {
         case 'SqlTablePrefix':
         case 'HelpPage':
            return 1;
         default:
            return 0;
      }
   }
   function getPluginDep() {
      return array('NP_phpBB');
   }
   var $post_info;
   var $is_auth;
   function getEventList() {
      return array('AddItemFormExtras', 'PostAddItem', 'PrepareItemForEdit',
         'EditItemFormExtras', 'PreUpdateItem', 'PostUpdateItem',
         'PreDeleteItem', 'PostDeleteItem');
   }
   function getTableList() {
      return array(sql_table('np_phpBB_links'));
   }
   function install() {
      //cros-posting options
      $this->createOption('crosspost','ID number of forum to cross-post to (-1 to disable)','text','-1','datatype=numerical');
      $this->createOption('ignorePost','Ignore phpBB thread creation restrictions?','yesno','no');
      $this->createOption('ignoreEdit','Ignore phpBB post edit restrictions?','yesno','no');
      $this->createOption('ignoreDelete','Ignore phpBB thread deletion restrictions?','yesno','no');
      $this->createOption('userOverride','Allow users to override cross post content options?','yesno','no');
      $this->createOption('linkDefault','Default Text for link in cross post (leave blank for no link)','text','Weblog Entry');
      $this->createOption('bodyDefault','Cross post article body of post to forum topic?','yesno','yes');
      $this->createOption('moreDefault','Cross post article extended content of post to forum topic?','yesno','no');
      $this->createOption('imgDefault','Embed images in cross posting?','yesno','yes');
      $this->createOption('drop', 'Drop table on uninstall?', 'yesno','no');
      
      // SQL changes/commands.
      // Field sizes should be determined pragmatically, as there are no guarentees
      // that the sizes of the fields the ones created are linked to are accurate.
      // However, they should be correct 99% of the time as they are the default sizes
      // and there are few reasons to alter the tables. Most users who do alter the tables
      // know what they are doing, and can correct the tables manually.
      //
      // Nucleus: sql_query('DESC `'.sql_table('member').'` `mname`')
      // phpBB: $db->sql_query('DESC `'.USERS_TABLE.'` `username`')
      //    (may not work on other databases)
      // Relevant data is in 'Type' col from returned record
      
      // Build Crossreference table.
      // Using table rather than hidden options to allow for preservation
      $table=sql_table('np_phpBB_links');
      $query = <<< SQL
CREATE TABLE IF NOT EXISTS `$table` (
   `inumber` int(11) NOT NULL,
   `post_id` mediumint(8) unsigned NOT NULL,
   `link` varchar(255) default NULL,
   `flags` set('body','more','image') default NULL,
   PRIMARY KEY (`inumber`),
   UNIQUE KEY `phpbb` (`post_id`)
)
SQL;
      sql_query($query);
   }
   
   function unInstall()
   {
      if ($this->getOption('drop') == 'yes')
      {
         $query = "DROP TABLE IF EXISTS " . sql_table('np_phpBB_links');
         sql_query($query);
      }
   }
   
   function _gen_menu($nohtml, $nobbcode, $nosmile, $notify, $sig, $body, $more, $link, $embed){
      global $board_config, $lang;
?>
<table>
   <tr><th colspan="2"><h3>phpBB integration/cross posting</h3></th></tr>
<?php if($board_config['allow_html']) { ?>
   <tr>
      <td><?php echo $lang['Disable_HTML_post']; ?></td>
      <td><?php ADMIN::input_yesno('NP_phpBB-no_html', $nohtml, 50); ?></td>
   </tr>
<?php } if($board_config['allow_bbcode']) { ?>
   <tr>
      <td><?php echo $lang['Disable_BBCode_post']; ?></td>
      <td><?php ADMIN::input_yesno('NP_phpBB-no_bbcode', $nobbcode, 51); ?></td>
   </tr>
<?php } if($board_config['allow_smilies']) { ?>
   <tr>
      <td><?php echo $lang['Disable_Smilies_post']; ?></td>
      <td><?php ADMIN::input_yesno('NP_phpBB-no_smilies', $nosmile, 52); ?></td>
   </tr>
<?php } ?>
   <tr>
      <td><?php echo $lang['Notify']; ?></td>
      <td><?php ADMIN::input_yesno('NP_phpBB-notify', $notify, 53); ?></td>
   </tr>
   <tr>
      <td><?php echo $lang['Attach_signature']; ?></td>
      <td><?php ADMIN::input_yesno('NP_phpBB-usesig', $sig, 54); ?></td>
   </tr>
<?php if('yes'==$this->getOption('userOverride')){ ?>
   <tr>
      <td>Include post body in cross posting?</td>
      <td><?php ADMIN::input_yesno('NP_phpBB-linkbody', $body, 55, 'yes', 'no'); ?></td>
   </tr>
   <tr>
      <td>Include extended body in cross posting?</td>
      <td><?php ADMIN::input_yesno('NP_phpBB-linkmore', $more, 56, 'yes', 'no'); ?></td>
   </tr>
   <tr>
      <td>Link text:</td>
      <td><input name="NP_phpBB-linktext" size="40" value="<?php echo htmlspecialchars($link);?>" /></td>
   </tr>
   <tr>
      <td>Embed images in cross posting?</td>
      <td><?php ADMIN::input_yesno('NP_phpBB-embed', $embed, 57, 'yes', 'no'); ?></td>
   </tr>
<?php } ?>
</table>
<?php
   }
   
   /**
    * Sanity check logic to see if a user is permitted to create a cross post.
    * @param mode type of authentication check to do.  Is one of 'newtopic','editpost','delete'.
    * @param post_id item number to check for authentication on if mode is 'editpost' or 'delete'
    * @returns -1 if 'silent' error (unavailable for some reason), 0 if success, error message otherwise.
    **/
   function _can_cross_post($mode, $post_id=-1){
      global $db, $board_config, $userdata, $phpEx, $starttime, $phpbb_root_path, $lang;
      $forum=$this->getOption('crosspost');
      if(empty($userdata) || !$userdata['session_logged_in']) return -1;
      	//Won't work if we can't link
      switch($mode)
      {
         case 'newtopic':
				if(0>$forum) return -1; //no forum specified to cross post to
				if(
					('no'==$this->getOption('userOverride')) &&
					(''==$this->getOption('linkDefault')) &&
					('no'==$this->getOption('bodyDefault')) &&
					('no'==$this->getOption('moreDefault'))
				) return -1; //no content would be produced if cross posting
            $auth='auth_post';
            $override=$this->getOption('ignorePost');
            $sql = "SELECT * 
               FROM " . FORUMS_TABLE . " 
               WHERE forum_id = $forum";
             break;
         case 'editpost':
            $auth='auth_edit';
            $override=$this->getOption('ignoreEdit');
            $sql = "SELECT f.*, t.topic_id, t.topic_status, t.topic_type, t.topic_first_post_id, t.topic_last_post_id, t.topic_vote, p.post_id, p.poster_id, t.topic_title, p.enable_bbcode, p.enable_html, p.enable_smilies, p.enable_sig, p.post_username, pt.post_subject, pt.post_text, pt.bbcode_uid, u.username, u.user_id, u.user_sig 
               FROM " . POSTS_TABLE . " p, " . TOPICS_TABLE . " t, " . FORUMS_TABLE . " f, " . POSTS_TEXT_TABLE . " pt, " . USERS_TABLE . " u 
               WHERE p.post_id = $post_id 
                  AND t.topic_id = p.topic_id 
                  AND f.forum_id = p.forum_id
                  AND pt.post_id = p.post_id AND u.user_id = p.poster_id";
            break;
         case 'delete':
            $auth='auth_delete';
            $override=$this->getOption('ignoreDelete');
            $sql = "SELECT f.*, t.topic_id, t.topic_status, t.topic_type, t.topic_first_post_id, t.topic_last_post_id, t.topic_vote, p.post_id, p.poster_id, t.topic_title, p.enable_bbcode, p.enable_html, p.enable_smilies, p.enable_sig, p.post_username, pt.post_subject, pt.post_text, pt.bbcode_uid, u.username, u.user_id, u.user_sig 
               FROM " . POSTS_TABLE . " p, " . TOPICS_TABLE . " t, " . FORUMS_TABLE . " f, " . POSTS_TEXT_TABLE . " pt, " . USERS_TABLE . " u 
               WHERE p.post_id = $post_id 
                  AND t.topic_id = p.topic_id 
                  AND f.forum_id = p.forum_id
                  AND pt.post_id = p.post_id AND u.user_id = p.poster_id";
            break;
         default:
            return "Unexpected crosspost mode";//perhaps should be silent
      }
      if(!($result=$db->sql_query($sql))){
      	//clean up cross post here?
      	return $lang['No_such_post'];
      }
      $this->post_info = $db->sql_fetchrow($result);
      $db->sql_freeresult($result);
      
      include($phpbb_root_path.'includes/auth.'.$phpEx);
      
      $this->is_auth = auth(AUTH_ALL, $forum, $userdata, $this->post_info);
      
      if ('yes'==$override || ($this->is_auth['auth_mod'] && $this->is_auth[$auth]))
         return 0;//skipping all auth checks if override is enabled, or is phpbb moderator.
      
      if( !$this->is_auth[$auth] )
         return sprintf($lang['Sorry_' . $auth], $is_auth[$auth . "_type"]);
         	//not authorized for action
      if ( $this->post_info['forum_status'] == FORUM_LOCKED )
         return $lang['Forum_locked'];
         	//cross posting forum locked
      if ('newtopic' != $mode) {
         if($this->post_info['topic_status'] == TOPIC_LOCKED )
            return $lang['Topic_locked'];
         if ( $this->post_info['poster_id'] != $userdata['user_id']  )
            return ( 'delete' == $mode ) ? $lang['Delete_own_posts'] : $lang['Edit_own_posts'];
            	//Should this be skipped?-we are authed to edit/delete the post in nucleus at this point 
      }
      if ( 'delete' == $mode && $this->post_info['topic_last_post_id'] != $post_id )
         return $lang['Cannot_delete_replied'];
         	//can't delete if replies
      return 0;
   }
   
   function _explode_image_tag($tag, $userid, &$link, &$text, &$width, &$height){
      $res= explode('|',$tag);
      $filename = $res[0];
      $width=$res[1];
      $height=$res[2];
      $text=$res[3];
      // select private collection when no collection given
      if (!strstr($filename,'/')) {
         $filename = $userid . '/' . $filename;
      }
      
      $link = htmlspecialchars($CONF['MediaURL']. $filename);
      $text = htmlspecialchars($text);
   }
   
   function _trans_image_html_tag($tag, $userid){
      $this->_explode_image_tag($tag, $userid, $link, $text, $width, $height);
      return '<img src="' . $link . '" width="' . $width . '" height="' . $height . '" alt="' . $text . '" title="' . $text . '" />';
   }
   
   function _trans_image_html_link($tag){
      $this->_explode_image_tag($tag, $userid, $link, $text, $width, $height);
      return '<a href="' . $link . '">' . $text . '</a>';
   }
   
   function _trans_image_bbcode_tag($tag){
      $this->_explode_image_tag($tag, $userid, $link, $text, $width, $height);
      return '[img]' . $link . '[/img]';
   }
   
   function _trans_image_bbcode_link($tag){
      $this->_explode_image_tag($tag, $userid, $link, $text, $width, $height);
      return '[url=' . $link . ']' . $text . '[/url]';
   }
   
   function _trans_image_text($tag){
      $this->_explode_image_tag($tag, $userid, $link, $text, $width, $height);
      return '(Image: '.$text.')';
   }
   
   /**
    * Generates a cross-post in phpBB utilizing multiple posting variables
    * @param itemid nucleus post number
    * @param post_id phpBB post number (if applicable)
    * @param mode type of update to do.  Is one of 'newtopic','editpost','delete'.
    * @param body include body of post in cross-post (yes or no)
    * @param more include extended text of post in cross-post (yes or no)
    * @param link text to use for linking to weblog
    * @param embed embed images in cross-post (yes or no)
    **/
   function _do_cross_post($itemid, $post_id, $mode, $body='no', $more='no', $link='', $embed='no'){
      global $board_config, $userdata, $manager, $phpbb_root_path, $phpEx;
      
      global $html_entities_match, $html_entities_replace;
      include($phpbb_root_path.'includes/functions_post.'.$phpEx);
      include($phpbb_root_path.'includes/bbcode.'.$phpEx);
      
      if('delete'!=$mode){
         $bbcode_on = $board_config['allow_bbcode'] & !intPostVar('NP_phpBB-no_bbcode');
         $html_on = $board_config['allow_html'] & !intPostVar('NP_phpBB-no_html');
         $smilies_on = $board_config['allow_smilies'] & !intPostVar('NP_phpBB-no_smilies');
         $notify_user = $this->is_auth['auth_read'] & intPostVar('NP_phpBB-notify');
         $attach_sig = intPostVar('NP_phpBB-usesig');
         
         $item=$manager->getitem($itemid,false,false);
            //don't want to allow drafts or future postings to be crossposted.
         if(0==$item) return;

         $message = '';
         if ('yes' == $body && ''!=trim($item['body'])){
            $message .= $item['body'];
         }
         if ('yes' == $more && ''!=trim($item['more'])){
            if($message) $message.= "\n\n";
            $message .= $item['more'];
         }
         if ('' != $link){
            global $CONF;
            if($message) $message.= "\n\n";
            if(!$CONF['ItemURL']) $CONF['ItemURL']=$CONF['IndexURL'];
            if($html_on)
	            $message .= ' <b><a href='.createItemLink($itemid).'>' . $link . '</a></b>';
            elseif($bbcode_on)
	            $message .= ' [b][url='.createItemLink($itemid).']' . $link . '[/url][/b]';
         }
         if(""==$message) return;
         if(""==$item['title']) return;
         
         //Translate <%image()%> tags
         if($html_on){//HTML is prefered, as we can produce a full tag if needed
            if('yes' == $embed){
               $message = preg_replace('/<%image\((.*?)\)%>/e', $this->_trans_image_html_tag('\\1', $item['authorid']), $message);
            }else{
               $message = preg_replace('/<%image\((.*?)\)%>/e', $this->_trans_image_html_link('\\1', $item['authorid']), $message);
            }
         }elseif($bbcode_on){//Try to use bbcode if we can't use HTML
            if('yes' == $embed){
               $message = preg_replace('/<%image\((.*?)\)%>/e', $this->_trans_image_bbcode_tag('\\1', $item['authorid']), $message);
            }else{
               $message = preg_replace('/<%image\((.*?)\)%>/e', $this->_trans_image_bbcode_link('\\1', $item['authorid']), $message);
            }
         }else{//otherwise, fall back on inserted text
            $message = preg_replace('/<%image\((.*?)\)%>/e', $this->_trans_image_text('\\1', $item['authorid']), $message);
         }
         
         $subject=addslashes($item['title']);
         $message=addslashes($message);
      }
      $topic_type = POST_NORMAL;
      $forum_id=$this->post_info['forum_id'];
      $error_msg = '';
      if('newtopic'==$mode) {
         $post_data=array(
            'first_post' => true,
            'last_post' => false,
            'has_poll' => false,
            'edit_poll' => false
         );
      }else{
         $topic_id = $this->post_info['topic_id'];

         $post_data=array(
            'poster_post' => ( $this->post_info['poster_id'] == $userdata['user_id'] ),
            'first_post' => ( $this->post_info['topic_first_post_id'] == $post_id ), //probably true, but...
            'last_post' => ( $this->post_info['forum_last_post_id'] == $post_id ),
            'last_topic' => ( $this->post_info['forum_last_post_id'] == $post_id ),
            'topic_type' => $this->post_info['topic_type'],
            'poster_id' => $this->post_info['poster_id'],
            'has_poll' => false,//not updating poll, so forcing to false
            'edit_poll' => false //not updating poll, so forcing to false
         );
      }
      $poll_title = '';
      $poll_options = '';
      $poll_length = '';
      if('delete'==$mode){//only worry aboug having pole if deleting
         $post_data['has_poll'] = $this->post_info['topic_vote'];
         $post_data['edit_poll'] = $post_data['first_post'] && $post_data['has_poll'];
      }
      $return_message = '';
      $return_meta = '';
      $username='';
      $bbcode_uid = '';
      
      if('delete'!=$mode){
         prepare_post($mode, $post_data, $bbcode_on, $html_on, $smilies_on, $error_msg, $username, $bbcode_uid, $subject, $message, $poll_title, $poll_options, $poll_length);
         if ( $error_msg == '' ){
            submit_post($mode, $post_data, $return_message, $return_meta, $forum_id, $topic_id, $post_id, $poll_id, $topic_type, $bbcode_on, $html_on, $smilies_on, $attach_sig, $bbcode_uid, str_replace("\'", "''", $username), str_replace("\'", "''", $subject), str_replace("\'", "''", $message), str_replace("\'", "''", $poll_title), $poll_options, $poll_length);
         }
      }else{
         delete_post($mode, $post_data, $return_message, $return_meta, $forum_id, $topic_id, $post_id, $poll_id);
      }
      if ( $error_msg == '' ){
         $user_id = $userdata['user_id'];
         update_post_stats($mode, $post_data, $forum_id, $topic_id, $post_id, $user_id);
         if ( $error_msg == '' ){
            user_notification($mode, $post_data, $this->post_info['topic_title'], $forum_id, $topic_id, $post_id, $notify_user);
         }
         $tracking_topics = addslashes(cookieVar($board_config['cookie_name'] . '_t'));
         $tracking_forums = addslashes(cookieVar($board_config['cookie_name'] . '_f'));
         $tracking_topics = ( !empty($tracking_topics) ) ? unserialize($tracking_topics) : array();
         $tracking_forums = ( !empty($tracking_topics) ) ? unserialize($tracking_topics) : array();
         
         if ( count($tracking_topics) + count($tracking_forums) == 100 && empty($tracking_topics[$topic_id]) )
         {
            asort($tracking_topics);
            unset($tracking_topics[key($tracking_topics)]);
         }
         
         $tracking_topics[$topic_id] = time();
         
         setcookie($board_config['cookie_name'] . '_t', serialize($tracking_topics), 0, $board_config['cookie_path'], $board_config['cookie_domain'], $board_config['cookie_secure']);
         
         $flags=array();
         if('yes'==$body) $flags[]='body';
         if('yes'==$more) $flags[]='more';
         if('yes'==$embed) $flags[]='embed';
         
         switch($mode)
         {
            case 'newtopic':
               sql_query('INSERT INTO '.sql_table('np_phpBB_links').' SET inumber = '.
                  $itemid.', post_id = '.$post_id.", link = '".addslashes($link).
                  "', flags = ('".implode(",",$flags)."')");
                break;
            case 'editpost':
               sql_query('UPDATE '.sql_table('np_phpBB_links').' SET post_id = '.
                  $post_id.", link = '".addslashes($link)."', flags = ('".
                  implode(",",$flags)."') WHERE inumber = ".$itemid);
               break;
            case 'delete':
               sql_query('DELETE FROM '.sql_table('np_phpBB_links').' WHERE inumber = '.$itemid);
               break;
            default:
               return 0;
         }
      }
   }
   
   function event_AddItemFormExtras($data){
      global $board_config, $userdata, $lang;
      if(0!=$this->_can_cross_post('newtopic')) return;
      $this->_gen_menu(
         !$userdata['user_allowhtml'],
         !$userdata['user_allowbbcode'],
         !$userdata['user_allowsmile'],
          $userdata['user_notify'],
          $userdata['user_attachsig'],
          $this->getOption('bodyDefault'),
          $this->getOption('moreDefault'),
          $this->getOption('linkDefault'),
          $this->getOption('imgDefault')
      );
   }
   
   function event_PostAddItem($data){
      if(0!=$this->_can_cross_post('newtopic')) return;
      if('no'==$this->getOption('userOverride')){
         $body=$this->getOption('bodyDefault');
         $more=$this->getOption('moreDefault');
         $link=$this->getOption('linkDefault');
         $embed=$this->getOption('linkDefault');
      }else{
         $body=('yes'==postVar('NP_phpBB-linkbody'))?'yes':'no';
         $more=('yes'==postVar('NP_phpBB-linkmore'))?'yes':'no';
         $link=postVar('NP_phpBB-linktext');
         $embed=('yes'==postVar('NP_phpBB-embed'))?'yes':'no';
      }
      $this->_do_cross_post($data['itemid'], '', 'newtopic', $body, $more, $link, $embed);
   }
   
   var $load; //used to stash data between auth and action
   function event_PrepareItemForEdit($data){
      $res=sql_query('SELECT post_id, link, flags FROM '.sql_table('np_phpBB_links').
         ' WHERE inumber = '.$data['item']['itemid']);
      if($res && mysql_num_rows($res)){
         $this->load=mysql_fetch_assoc($res);
         mysql_free_result($res);
         $res=$this->_can_cross_post('editpost', $this->load['post_id']);
         if(-1==$res){
         	$this->load=array();
         }elseif(0!=$res)
            doError($res);
      }else
         $this->load=array();
   }
   
   function event_EditItemFormExtras($data){
      if(!is_null($this->load['post_id'])){
         global $userdata;
         $notify_user=0;
         if ($userdata['session_logged_in'] && $this->is_auth['auth_read'] )
         {
            global $db;
            $sql = "SELECT topic_id 
               FROM " . TOPICS_WATCH_TABLE . "
               WHERE topic_id = ".$this->post_info['topic_id']." 
                  AND user_id = " . $userdata['user_id'];
            if ( !($result = $db->sql_query($sql)) )
            {
               message_die(GENERAL_ERROR, 'Could not obtain topic watch information', '', __LINE__, __FILE__, $sql);
            }
            $notify_user = ( $db->sql_fetchrow($result) ) ? TRUE : $userdata['user_notify'];
            $db->sql_freeresult($result);
         }
         $this->_gen_menu(
            !$this->post_info['enable_html'],
            !$this->post_info['enable_bbcode'],
            !$this->post_info['enable_smilies'],
             $notify_user,
             $this->post_info['enable_sig'],
             ((FALSE !== strpos($this->load['flags'],'body'))?'yes':'no'),
             ((FALSE !== strpos($this->load['flags'],'more'))?'yes':'no'),
             $this->load['link'],
             ((FALSE !== strpos($this->load['flags'],'embed'))?'yes':'no')
         );
      }else
         $this->event_AddItemFormExtras($data);
   }
   
   function event_PreUpdateItem($data){
      $res=sql_query('SELECT post_id, link, flags FROM '.sql_table('np_phpBB_links').
         ' WHERE inumber = '.$data['itemid']);
      if($res && mysql_num_rows($res)){
         $this->load=mysql_fetch_assoc($res);
         mysql_free_result($res);
         $res=$this->_can_cross_post('editpost', $this->load['post_id']);
         if(-1==$res){
         	$this->load=array();
         }elseif(0!=$res)
            doError($res);
      }else
         $this->load=array();
   }
   
   function event_PostUpdateItem($data){
      if(!is_null($this->load['post_id'])){
         if('no'==$this->getOption('userOverride')){
            $body=(FALSE !== strpos($this->load['flags'],'body'))?'yes':'no';
            $more=(FALSE !== strpos($this->load['flags'],'more'))?'yes':'no';
            $link=$this->load['link'];
            $embed=(FALSE !== strpos($this->load['flags'],'embed'))?'yes':'no';
         }else{
            $body=('yes'==postVar('NP_phpBB-linkbody'))?'yes':'no';
            $more=('yes'==postVar('NP_phpBB-linkmore'))?'yes':'no';
            $link=postVar('NP_phpBB-linktext');
            $embed=('yes'==postVar('NP_phpBB-embed'))?'yes':'no';
         }
         $this->_do_cross_post($data['itemid'], $this->load['post_id'], 'editpost', $body, $more, $link, $embed);
      }else
         $this->event_PostAddItem($data);
   }
   
   function event_PreDeleteItem($data){
      $res=sql_query('SELECT post_id FROM '.sql_table('np_phpBB_links').
         ' WHERE inumber = '.$data['itemid']);
      if($res && mysql_num_rows($res)){
         $this->load=mysql_fetch_assoc($res);
         mysql_free_result($res);
         $res=$this->_can_cross_post('delete', $this->load['post_id']);
         if(-1==$res){
         	$this->load=array();
         }elseif(0!=$res)
            doError($res);
      }else
         $this->load=array();
   }
   
   function event_PostDeleteItem($data){
      if(!is_null($this->load['post_id'])){
         $this->_do_cross_post($data['itemid'], $this->load['post_id'], 'delete');
      }
   }
   
   function doTemplateVar(&$item){
      global $manager, $db, $board_config, $phpEx, $currentTemplateName;
      static $loopblock=0; //used to prevent infinite recursion
      
      $core=$manager->getPlugin('NP_phpBB');
      if($loopblock || !$core || !$core->_link_phpBB()){
         print '<b>DISALLOWED(&lt;%phpBBxpost%&gt;)</b>';
         return;
      }
      $post_id=quickQuery('SELECT post_id as result FROM '.sql_table('np_phpBB_links').' WHERE inumber = '.$item->itemid);
      if(0==$post_id) return;
      
      $query="SELECT t.topic_id, t.topic_replies FROM " . POSTS_TABLE . " p, " . TOPICS_TABLE
         ." t WHERE p.post_id = $post_id AND t.topic_id = p.topic_id";
      if(!($result=$db->sql_query($query))){
         sql_query('DELETE FROM '.sql_table('np_phpBB_links').' WHERE inumber = '.$item->itemid);
         	//delete the crosslink, since it's no longer there.
         return;
      }
      $topic = $db->sql_fetchrow($result);
      $db->sql_freeresult($result);
      
      $template =& $manager->getTemplate($currentTemplateName);
      
      $server_protocol = ($board_config['cookie_secure']) ? 'https://' : 'http://';
      $server_name = preg_replace('#^\/?(.*?)\/?$#', '\1', trim($board_config['server_name']));
      $server_port = ($board_config['server_port'] <> 80) ? ':' . trim($board_config['server_port']) : '';
      $script_name = preg_replace('#^\/?(.*?)\/?$#', '\1', trim($board_config['script_path']));
      $script_name = ($script_name == '') ? $script_name : '/' . $script_name;
      $url = preg_replace('#^\/?(.*?)\/?$#', '/\1', trim(append_sid("viewtopic.$phpEx?" . POST_TOPIC_URL .'=' . $topic['topic_id'])));
      
      //Should use COMMENTS/COMMENTACTIONS
         // see COMMENTS::showComments
      $params=array(
         'itemlink' => $server_protocol . $server_name . $server_port . $script_name . $url,
         'commentcount' => $topic['topic_replies'],
         'commentword' => (1 == $topic['topic_replies'])?
            $template['COMMENTS_ONE']:
            $template['COMMENTS_MANY']
      );
      
      $loopblock++;
      if ($params['commentcount'] == 0) {
         // note: when no reactions, COMMENTS_HEADER and COMMENTS_FOOTER are _NOT_ used
         echo TEMPLATE::fill($template['COMMENTS_NONE'],$params);
      }else{
         echo TEMPLATE::fill($template['COMMENTS_TOOMUCH'],$params);
      }
      $loopblock--;
   }
}
?>