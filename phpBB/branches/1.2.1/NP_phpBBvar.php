<?php
/**
 * NP_phpBBvar version 1.0 is a plugin for the Nucleus CMS.
 * 
 * This plugin depends on (and extends) the NP_phpBB plugin.  Its purpose is to 
 * provide profile integration (by redirecting user profile pages to the 
 * coresponding phpBB profiles, and skinvars providing some of the information 
 * available in phpBB templates.
 * 
 * Template variable mapping:
 * phpBB template constant -> nucleus skinvar
 *    POSTER_RANK -> <%phpBBvar(rank_title)%>
 *    RANK_IMAGE -> <%phpBBvar(rank_image)%>
 *    POSTER_JOINED -> <%phpBBvar(joined)%>
 *    POSTER_POSTS -> <%phpBBvar(posts)%>
 *    POSTER_FROM -> <%phpBBvar(location)%>
 *    POSTER_AVATAR -> <%phpBBvar(avatar)%>
 *    EMAIL -> <%phpBBvar(email)%>
 *    WWW -> <%phpBBvar(www)%>
 *    ICQ_STATUS_IMG -> <%phpBBvar(icq_status_img)%>
 *    ICQ -> <%phpBBvar(icq)%>
 *    AIM -> <%phpBBvar(aim)%>
 *    MSN -> <%phpBBvar(msn)%>
 *    YIM -> <%phpBBvar(yim)%>
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
 * This file borrows create_date() in its entirety from includes/functions.php,
 * logic from viewtopic.php is used in NP_phpBBvar::_gen_phpbb_element(), and
 * logic from redirect() in includes/functions.php is used in
 * NP_phpBBvar::_gen_profile_uri() and NP_phpBBvar::doSkinVar()
 * 
 * NP_phpBB is used to establish the phpBB environment and the borrowed logic is
 * used to extract user information from the phpBB user table.
 **/
/**
 * @license http://nucleuscms.org/license.txt GNU General Public License
 * @copyright Copyright (C) 2005 Andrew Black (A.K.A Frankenstein)
 **/

// Borrowed from phpBB
//
// Create date/time from format and timezone
//
function create_date($format, $gmepoch, $tz)
{
	global $board_config, $lang;
	static $translate;

	if ( empty($translate) && $board_config['default_lang'] != 'english' )
	{
		@reset($lang['datetime']);
		while ( list($match, $replace) = @each($lang['datetime']) )
		{
			$translate[$match] = $replace;
		}
	}

	return ( !empty($translate) ) ? strtr(@gmdate($format, $gmepoch + (3600 * $tz)), $translate) : @gmdate($format, $gmepoch + (3600 * $tz));
}

class NP_phpBBvar extends NucleusPlugin {
   function getName() { return 'phpBB skinVars'; }
   function getDescription() { return 'phpBB integration module.  Provides skinvars with phpBB information'; }
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
   var $core;
   function init(){
      global $manager;
      $this->core = $manager->getPlugin('NP_phpBB');
   }
   
   /**
    * Assembles a URI for a profile.
    * Assembly is taken from phpBB's redirect() function.
    * Assembly is needed as relative links essentially can't be used.
    * @param action type of profile uri to generate ('email' or 'viewprofile')
    * @param poster_id ID number of poster to generate for.
    **/
   function _gen_profile_uri($action, $poster_id){
      global $board_config, $phpEx;
      $server_protocol = ($board_config['cookie_secure']) ? 'https://' : 'http://';
      $server_name = preg_replace('#^\/?(.*?)\/?$#', '\1', trim($board_config['server_name']));
      $server_port = ($board_config['server_port'] <> 80) ? ':' . trim($board_config['server_port']) : '';
      $script_name = preg_replace('#^\/?(.*?)\/?$#', '\1', trim($board_config['script_path']));
      $script_name = ($script_name == '') ? $script_name : '/' . $script_name;
      $url = preg_replace('#^\/?(.*?)\/?$#', '/\1', trim(append_sid("profile.$phpEx?mode=$action&" . POST_USERS_URL .'=' . $poster_id)));
      return $server_protocol . $server_name . $server_port . $script_name . $url;
   }
   
   function doSkinVar($skinType){
      static $loopblock=0;
      
      if('member'!=$skinType)
         return;
            
      global $memberinfo, $db;
      
      if(!$loopblock){
         $fail = !$this->core || !$this->core->_link_phpBB();
         if(!$fail){
            $sql = "SELECT user_id FROM " . USERS_TABLE . " WHERE username = '" . addslashes($memberinfo->getDisplayName()) . "'";
            if ( ($result = $db->sql_query($sql)) ){
               if( 0 < $db->sql_numrows($result)){
                  $record=$db->sql_fetchrow($result);
                  $id=$record['user_id'];
               }else{
                  $fail=1;
               }
               $db->sql_freeresult($result);
            }else{
               $fail=1;
            }
         }
         
         if($fail){
            //Try to fallback if there was a problem
               //ie: core not found, core disabled, member not found
            global $skinid;
            $loopblock=1;
            $skin_name = 'fallback/' . SKIN::getNameFromId($skinid);
            $skin = SKIN::createFromName($skin_name);
            
            if ($skin->isValid){
               $skin->parse($skinType);
               return;
            }
         }
      }
      if($loopblock)
         doError($skinType.' unavailable due to phpBB integration error. No fallback found.');
      
      //The initial thought had been to include the profile directly,
      //But there's a namespace conflict with the nucleus template class.
      //Therefore, we'll redirect to...
      $url = $this->_gen_profile_uri('viewprofile',$id);
      
      //Then borrow the phpBB redirect() function, skipping a check
      if (!empty($db))
      {
         $db->sql_close();
      }
      
      // Redirect via an HTML form for PITA webservers
      if (@preg_match('/Microsoft|WebSTAR|Xitami/', getenv('SERVER_SOFTWARE')))
      {
         header('Refresh: 0; URL=' . $url);
         echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><meta http-equiv="refresh" content="0; url=' . htmlspecialchar($url) . '"><title>Redirect</title></head><body><div align="center">If your browser does not support meta redirection please click <a href="' . htmlspecialchar($url) . '">HERE</a> to be redirected</div></body></html>';
         exit;
      }
      // Behave as per HTTP/1.1 spec for others
      header('Location: ' . $url);
      exit;
   }
   
   function doTemplateVar(&$item, $mode){
      $this->_gen_phpbb_element($item->authorid, $mode);
   }
   
   function doTemplateCommentsVar(&$item, &$comment, $mode){
      $this->_gen_phpbb_element($comment['memberid'], $mode);
   }
   
   /**
    * Generates outputs assorted information about a user.
    * The type of information generated is determined by the mode paramater
    * @param member user to generate tag about
    * @param mode type of tag to generate
    **/
   function _gen_phpbb_element($member, $mode){
      static $outcache = array();//2d assoc array, [member][mode], cache of generated data
      static $rawcache = array();//2d assoc array, [member][attribute], cache of raw data
      global $board_config, $db, $lang;
      if(is_null($member)) return; //guests won't have any data
      if(array_key_exists($member, $outcache) && array_key_exists($mode,$outcache[$member])){
         print $outcache[$member][$mode];
         return;
      }
      if(!$this->core || !$this->core->_link_phpBB()){
         print '<b>DISALLOWED(&lt;%phpBBvar(' . $mode . ')%&gt;)</b>';
         return;
      }
      //Look up the member's data if needed
      if(!array_key_exists($member, $rawcache)){
         $membername = quickQuery('SELECT mname as result FROM '.sql_table('member').' WHERE mnumber=' . intval($member));
         $sql = "SELECT user_posts, user_from, user_website, user_email, user_icq, user_aim, ".
                "user_yim, user_regdate, user_msnm, user_viewemail, user_rank, user_sig, ".
                "user_sig_bbcode_uid, user_avatar, user_avatar_type, user_allowavatar, user_id ".
                "FROM " . USERS_TABLE . " WHERE username = '" . addslashes($membername)."'";
         if ( !($result = $db->sql_query($sql)) ){
            doError('Unable to retrieve phpBB user information.');
         }
         $rawcache[$member]=$db->sql_fetchrow($result);
            //We don't care if a row was actually returned when making this assignment
         $db->sql_freeresult($result);
      }
      if(is_null($rawcache[$member])) return;
      $res='';
      switch($mode){
         case "avatar":
            if ( $rawcache[$member]['user_avatar_type'] && $rawcache[$member]['user_allowavatar'] )
            {
               switch( $rawcache[$member]['user_avatar_type'] )
               {
                  case USER_AVATAR_UPLOAD:
                     $res = ( $board_config['allow_avatar_upload'] ) ? '<img src="' . $board_config['avatar_path'] . '/' . $rawcache[$member]['user_avatar'] . '" alt="" border="0" />' : '';
                     break;
                  case USER_AVATAR_REMOTE:
                     $res = ( $board_config['allow_avatar_remote'] ) ? '<img src="' . $rawcache[$member]['user_avatar'] . '" alt="" border="0" />' : '';
                     break;
                  case USER_AVATAR_GALLERY:
                     $res = ( $board_config['allow_avatar_local'] ) ? '<img src="' . $board_config['avatar_gallery_path'] . '/' . $rawcache[$member]['user_avatar'] . '" alt="" border="0" />' : '';
                     break;
               }
            }
            break;
         case "rank_title":
         case "rank_image":
            static $ranksrow = array();
            if(0==count($ranksrow)){
               $sql = "SELECT *
                  FROM " . RANKS_TABLE . "
                  ORDER BY rank_special, rank_min";
               if ( !($result = $db->sql_query($sql)) )
               {
                  doError("Could not obtain phpBB ranks information.");
               }
               
               while ( $row = $db->sql_fetchrow($result) )
               {
                  $ranksrow[] = $row;
               }
               $db->sql_freeresult($result);
            }
            $j=0;
            if ( $rawcache[$member]['user_rank'] )
            {
               for($j = 0; $j < count($ranksrow); $j++)
               {
                  if ( $rawcache[$member]['user_rank'] == $ranksrow[$j]['rank_id'] && $ranksrow[$j]['rank_special'] )
                     $res = $ranksrow[$j]['rank_title'];
               }
            }
            else
            {
               for($j = 0; $j < count($ranksrow); $j++)
               {
                  if ( $rawcache[$member]['user_posts'] >= $ranksrow[$j]['rank_min'] && !$ranksrow[$j]['rank_special'] )
                     $res = $ranksrow[$j]['rank_title'];
               }
            }
            $outcache[$member]['rank_title']=$res;
            $outcache[$member]['rank_image']=( $ranksrow[$j]['rank_image'] ) ? '<img src="' . $ranksrow[$j]['rank_image'] . '" alt="' . $res . '" title="' . $res . '" border="0" /><br />' : '';
            if("rank_image"==$mode)
               $res = $outcache[$member]['rank_image'];
            break;
         case 'joined':
            $res = $lang['Joined'] . ': ' . create_date($lang['DATE_FORMAT'], $rawcache[$member]['user_regdate'], $board_config['board_timezone']);
            break;
         case 'posts':
            $res = $lang['Posts'] . ': ' . $rawcache[$member]['user_posts'];
            break;
         case 'location':
            if($rawcache[$member]['user_from']){
               $res = $lang['Location'] . ': ' . $rawcache[$member]['user_from'];
            }
            break;
         case 'email':
            if(!empty($rawcache[$member]['user_viewemail']) || ($member->isLoggedIn() && $member->isAdmin())){
               if($board_config['board_email_form']){
                  $email_uri = $this->_gen_profile_uri('email',$rawcache[$member]['user_id']);
               }else
                  $email_uri = 'mailto:' . $rawcache[$member]['user_email'];
               $res = '<a href="' . $email_uri . '">' . $lang['Send_email'] . '</a>';
            }
            break;
         case 'www':
            if($rawcache[$member]['user_website']){
               $res = '<a href="' . $rawcache[$member]['user_website'] . '" target="_userwww">' . $lang['Visit_website'] . '</a>';
            }
            break;
         case 'icq_status_img':
            if($rawcache[$member]['user_icq']){
               $res = '<a href="http://wwp.icq.com/' . $rawcache[$member]['user_icq'] . '#pager"><img src="http://web.icq.com/whitepages/online?icq=' . $rawcache[$member]['user_icq'] . '&img=5" width="18" height="18" border="0" /></a>';
            }
            break;
         case 'icq':
            if($rawcache[$member]['user_icq']){
               $res = '<a href="http://wwp.icq.com/scripts/search.dll?to=' . $rawcache[$member]['user_icq'] . '">' . $lang['ICQ'] . '</a>';
            }
            break;
         case 'aim':
            if($rawcache[$member]['user_aim']){
               $res = '<a href="aim:goim?screenname=' . $rawcache[$member]['user_aim'] . '&amp;message=Hello+Are+you+there?">' . $lang['AIM'] . '</a>';
            }
            break;
         case 'msn':
            if($rawcache[$member]['user_msnm']){
               $temp_url = $this->_gen_profile_uri('viewprofile',$rawcache[$member]['user_id']);
               $res = '<a href="' . $temp_url . '">' . $lang['MSNM'] . '</a>';
            }
            break;
         case 'yim':
            if($rawcache[$member]['user_yim']){
               $res = '<a href="http://edit.yahoo.com/config/send_webmesg?.target=' . $rawcache[$member]['user_yim'] . '&amp;.src=pg">' . $lang['YIM'] . '</a>';
            }
            break;
         default:
            $res = '<b>DISALLOWED(&lt;%phpBBvar(' . $mode . ')%&gt;)</b>';
            break;
      }
      $outcache[$member][$mode]=$res;
      echo $res;
   }
}
?>