<?php
/**
 * NP_phpBB version 1.2 is a plugin for the Nucleus CMS.
 *
 * The purpose is to provide integration between Nucleus CMS and the phpBB web 
 * forum, including authentication, user information and cross-posting.
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
 * In this file, encode_ip(), phpbb_clean_username() and phpbb_realpath() have
 * been borrowed in their entirety from includes/functions.php.
 * Also in this file, init_userprefs() was copied and truncated, omiting the
 * styles loading (Due to the template conflict) and message_die() was replaced
 * with a glue version, providing the same API, but utilizing some logic from
 * the phpBB version and the the nucleus error handling system.  Additionally,
 * some logic from common.php is used in NP_phpBB::_link_phpBB() and some logic
 * from login.php is used in NP_phpBB::event_LoginHook()
 * 
 * A number of phpBB libraries are includ()ed from within the plugin. 
 * NP_phpBB::_link_phpBB() uses config.php, includes/constants.php,
 * includes/sessions.php, and include/db.php (along with db/*.php). 
 * NP_phpBBxpost::_can_cross_post() uses includes/auth.php.
 * NP_phpBBxpost::_do_cross_post() uses includes/functions_post.php and
 * includes/bbcode.php.
 * 
 * These includes are used to allow this plugin to access the phpBB database,
 * set up the basic phpBB environment, authenticate against the phpBB user
 * table, understand the phpBB cookies, and load the phpBB user preferences.
 *
 **/
/**
 * @license http://nucleuscms.org/license.txt GNU General Public License
 * @copyright Copyright (C) 2005-2006 Andrew Black (A.K.A Frankenstein)
 **/

// Todo: handle failure to alter table

// Glue function for error handling
function message_die($msg_code, $msg_text = '', $msg_title = '', $err_line = '', $err_file = '', $sql = '')
{
   global $db;
   if ( DEBUG && ( $msg_code == GENERAL_ERROR || $msg_code == CRITICAL_ERROR ) )
   {
      $sql_error = $db->sql_error();

      $debug_text = '';

      if ( $sql_error['message'] != '' )
      {
         $debug_text .= '<br /><br />SQL Error : ' . $sql_error['code'] . ' ' . $sql_error['message'];
      }

      if ( $sql != '' )
      {
         $debug_text .= "<br /><br />$sql";
      }

      if ( $err_line != '' && $err_file != '' )
      {
         $debug_text .= '</br /><br />Line : ' . $err_line . '<br />File : ' . basename($err_file);
      }
      if ( $debug_text != '' )
      {
         $msg_text = $msg_text . '<br /><br /><b><u>DEBUG MODE</u></b>' . $debug_text;
      }
   }
   if (!empty($db))
   {
    $db->sql_close();
   }
   sql_connect();//resync the connection if it's shared.
   doError('phpBB Error: '.$msg_text);
}

// Borrowed from phpBB
function encode_ip($dotquad_ip)
{
   $ip_sep = explode('.', $dotquad_ip);
   return sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
}

// Borrowed from phpBB
// added at phpBB 2.0.11 to properly format the username
function phpbb_clean_username($username)
{
   $username = htmlspecialchars(rtrim(trim($username), "\\"));
   $username = substr(str_replace("\\'", "'", $username), 0, 25);
   $username = str_replace("'", "\\'", $username);

   return $username;
}

// Borrowed from phpBB (altered to remove style loading)
// Initialise user settings on page load
function init_userprefs($userdata)
{
   global $board_config, $theme, $images;
   global $template, $lang, $phpEx, $phpbb_root_path;
   global $nav_links;

   if ( $userdata['user_id'] != ANONYMOUS )
   {
      if ( !empty($userdata['user_lang']))
      {
         $board_config['default_lang'] = $userdata['user_lang'];
      }

      if ( !empty($userdata['user_dateformat']) )
      {
         $board_config['default_dateformat'] = $userdata['user_dateformat'];
      }

      if ( isset($userdata['user_timezone']) )
      {
         $board_config['board_timezone'] = $userdata['user_timezone'];
      }
   }

   if ( !file_exists(@phpbb_realpath($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_main.'.$phpEx)) )
   {
      $board_config['default_lang'] = 'english';
   }

   include($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_main.' . $phpEx);

   if ( defined('IN_ADMIN') )
   {
      if( !file_exists(@phpbb_realpath($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_admin.'.$phpEx)) )
      {
         $board_config['default_lang'] = 'english';
      }

      include($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_admin.' . $phpEx);
   }
}

// Borrowed from phpBB
function phpbb_realpath($path)
{
   global $phpbb_root_path, $phpEx;

   return (!@function_exists('realpath') || !@realpath($phpbb_root_path . 'includes/functions.'.$phpEx)) ? $path : @realpath($path);
}

// Borrowed from phpBB - Added in phpBB 2.0.20
function dss_rand()
{
   global $db, $board_config, $dss_seeded;

   $val = $board_config['rand_seed'] . microtime();
   $val = md5($val);
   $board_config['rand_seed'] = md5($board_config['rand_seed'] . $val . 'a');
   
   if($dss_seeded !== true)
   {
      $sql = "UPDATE " . CONFIG_TABLE . " SET
         config_value = '" . $board_config['rand_seed'] . "'
         WHERE config_name = 'rand_seed'";
      
      if( !$db->sql_query($sql) )
      {
         message_die(GENERAL_ERROR, "Unable to reseed PRNG", "", __LINE__, __FILE__, $sql);
      }

      $dss_seeded = true;
   }

   return substr($val, 16);
}

class NP_phpBB extends NucleusPlugin {

//
// Basic environment/housekeeping: plugin information, setup
//

   var $reset=FALSE;
   function getName() { return 'phpBB User Integration'; }
   function getDescription() { return 'phpBB integration plugin.'; }
   function getAuthor() { return 'Andrew Black'; }
   function getURL() { return ''; }
   function getVersion() { return '1.2'; }
   function getMinNucleusVersion() { return 220; }
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
   function getEventList() {
      return array('PostAuthentication','LoginHook','PostPluginOptionsUpdate');
   }

   /**
    * Set up the link to the phpBB database, along with establishing the environment
    * needed to function inside the forum.
    **/
   function install() {
      //Basic options
      global $DIR_NUCLEUS;
      $dir_phpbb = dirname($DIR_NUCLEUS). '/phpBB2/';
      $this->createOption('phpbbRootPath','File system path to the phpBB instalation','text',$dir_phpbb);
      $this->createOption('enable','Enable phpBB authentication','yesno','no');
      
      //Ext Auth option (will be removed after integration)
      $this->createOption('enableExtAuth','Enable ExtAuth authentication support (HACK!-Use with Care)','yesno','no');
      
      // SQL changes/commands.
      // Field sizes should be determined pragmatically, as there are no guarentees
      // that the sizes of the fields the ones created are linked to are accurate.
      // However, they should be correct 99% of the time as they are the default sizes
      // and there are few reasons to alter the tables. Most users who do alter the tables
      // know what they are doing, and can correct the tables manually.
      //
      // Nucleus: sql_query('DESC `'.sql_table('member').'` `mname`')
      // phpBB: $db->sql_query('DESC `'.USERS_TABLE.'` `username`')
      // 	(may not work on other databases)
      // Relevant data is in 'Type' col from returned record
      
      // Alter nucleus_member mname row length
      $query = 'ALTER TABLE `'.sql_table('member').'` CHANGE `mname` `mname` varchar(25) NOT NULL';
      sql_query($query);
   }
   
   /**
    * Sanity check the user provided value for phpbbRootPath
    **/
   function event_PostPluginOptionsUpdate($data){
      if('global' != $data['context'] || $this->GetID() != $data['plugid'])
         return;
      if('yes'==$this->getOption('enable') && !$this->_link_phpBB())
         doError('Unable to locate phpBB include files.');
   }

//
// Utility functions: establishing phpPP environment, generating profile link
//

   /**
    * Set up the link to the phpBB database, along with establishing the environment
    * needed to function inside the forum.
    **/
   function _link_phpBB (){
      global $db, $board_config, $userdata, $phpEx, $starttime, $phpbb_root_path;
      
      static $hasrun=0;
      if($hasrun++ || ('no'==$this->getOption('enable'))) return ('yes'==$this->getOption('enable'));
      
      //the following line is a hack to tell the phpBB libraries that we are part of the phpBB codebase
      define('IN_PHPBB', 'Nucleus_Plugin');
         //standard value is true.  I'm using something else to distinguish us if needed
      
      $phpbb_root_path=$this->getOption('phpbbRootPath');
      
      $board_config = array();
      $userdata = array();
      
      if(!(include($phpbb_root_path . 'extension.inc')))
      {
         $this->setOption('enable','no');//Turn ourself 'off' since we aren't functional
         ACTIONLOG::add(ERROR, "NP_phpBB: unable to include extension.inc");
         return 0;
      }
      
      include($phpbb_root_path.'config.'.$phpEx);
      include($phpbb_root_path.'includes/constants.'.$phpEx);
      include($phpbb_root_path.'includes/sessions.'.$phpEx);
      include($phpbb_root_path.'includes/db.'.$phpEx);
      sql_connect();
         //Reset the last opened link identifier.
         //The phpBB db classes cache their link internally rather
         //than assuming it's the last opened.
      
      $user_ip = encode_ip(serverVar('REMOTE_ADDR'));
      
      // Begin phpBB config loading (borrowed from phpBB's common.php)
      $sql = "SELECT *
         FROM " . CONFIG_TABLE;
      if( !($result = $db->sql_query($sql)) )
      {
         message_die(CRITICAL_ERROR, "Could not query config information", "", __LINE__, __FILE__, $sql);
      }
      
      while ( $row = $db->sql_fetchrow($result) )
      {
         $board_config[$row['config_name']] = $row['config_value'];
      }
      //end phpbb config loading
      
      $userdata=session_pagestart($user_ip, PAGE_INDEX);
      init_userprefs($userdata);
      return 1;
   }
   
//
// Authentication linkage
//

   /**
    * External Authentication event handler.
    * Checks if the provided username/password are valid in phpBB
    * Also initizes phpBB session.
    * Code based in part on phpBB's login.php script.
    **/
   function event_LoginHook($data) {
      global $member, $manager, $action, $CONF, $userdata;
      
      if(!$this->_link_phpBB())
         return; //Can't authenticate if the plugin is disabled.
      
      if($userdata['session_logged_in'])
         return; //Don't bother authenticating if we're already logged in.
      
      $username = phpbb_clean_username($data['user']);
      $password = $data['password'];
      
      $sql = "SELECT user_id, username, user_password, user_active, user_level
         FROM " . USERS_TABLE . "
         WHERE username = '" . str_replace("\\'", "''", $username) . "'";
      if ( !($result = $db->sql_query($sql)) )
      {
         message_die(GENERAL_ERROR, 'Error in obtaining userdata', '', __LINE__, __FILE__, $sql);
      }
      
      if( $row = $db->sql_fetchrow($result) )
      {
         if( $row['user_level'] != ADMIN && $board_config['board_disable'] )
            return;
               //Won't redirect, but won't auth either.
         
         if( md5($password) == $row['user_password'] && $row['user_active'] )
         {
            global $shared;
            $autologin = !$shared;
            
            $session_id = session_begin($row['user_id'], $user_ip, PAGE_INDEX, FALSE, $autologin);
            
            if( $session_id )
            {
               $this->$reset=TRUE;
               $data['loggedin'] = 1;
            }
            else
            {
               message_die(CRITICAL_ERROR, "Couldn't start session : login", "", __LINE__, __FILE__);
            }
         }
      }
   }
   
   /**
    * Post Authentication event handler.
    * This is triggered after the normal authentication process has completed.
    * Several things happen within this event handler.
    * First, we clean up for the ExternalAuthentication event handler
    * (the Nucleus cookes aren't needed as the phpBB cookies have been set)
    * Next, we set up the phpBB environment, bailing if it can't be set up or
    * the user is already logged in via conventional means.
    * The next step is to see if the user tried to log out, and handle that.
    * Finally, we look to see if the user is authenticated in phpBB and 
    * if he/she is load his/her Nucleus profile or create one if needed.
    **/
   function event_PostAuthentication($data) {
      global $member, $manager, $action, $CONF, $userdata;
      
      if($this->reset){
         //Since we authenticated, mask/unset the nucleus cookies
         setcookie($CONF['CookiePrefix'] .'user','',(time()-2592000),$CONF['CookiePath'],$CONF['CookieDomain'],$CONF['CookieSecure']);
         setcookie($CONF['CookiePrefix'] .'loginkey','',(time()-2592000),$CONF['CookiePath'],$CONF['CookieDomain'],$CONF['CookieSecure']);
         return;
      }
      
      if($data['loggedIn'] || (!$this->_link_phpBB()))
         return;
      //Don't mess with the member object if it's authenticated or we can't link.
      
      if('logout'==$action && $userdata['session_logged_in'] )
      {
         session_end($userdata['session_id'], $userdata['user_id']);
         $manager->notify('Logout',array('username' => $userdata['username']));
         return;
      }
      
      if(ANONYMOUS==$userdata['user_id'])
         return;//since we don't have a session
      
      //At this point, we've determined who that the user is who he/she says he/she is.
      //Now we need to load his/her nucleus user record.
      if($member->readFromName($userdata['username'])){
         ACTIONLOG::add(DEBUG, "{$userdata['username']} externally authenticated through NP_phpBB");
         
         //Change to INFO only if you want to spam the message log with these messages,
         //as one will be generated every time a page is loaded by a logged in member.
         //Message is included as a debugging tool.
      }else{
         //Couldn't find the member in the nucleus database, so create a record for him/her.
         $pw=('no'==$this->getOption('enableExtAuth'))?$userdata['user_password']:'';
         $res=MEMBER::create($userdata['username'], $userdata['username'], $pw, $userdata['user_email'], $userdata['user_website'], 0, $CONF['NewMemberCanLogon'], '');
         if(1!==$res){
            ACTIONLOG::add(WARNING, "NP_phpBB: {$res} autogenerating {$userdata['username']}: {$userdata['user_email']}, {$userdata['user_website']}");
            doError('NP_phpBB: Unable to set up tie account.  Please contact the forum administrator.');
         }
         $member->readFromName($userdata['username']);
         $manager->notify('PostRegister',array('member' => &$member));
         ACTIONLOG::add(INFO, "{$userdata['username']} autogenerated by NP_phpBB");
      }
      $member->loggedin = 1;
      //Because we have authenticated the member, this flag needs to be set.
      if('no'==$this->getOption('enableExtAuth')){
         $member->password=$userdata['user_password'];
         $member->write();
      }//Sync the password if ExtAuth isn't active
      //Consider syncing language here also
   }
}

/*

Core glitches:
ADMIN 2140-need to htmlspecialchar() $teammem->getDisplayName()
ADMIN 2888-need to htmlspecialchar() $mem->getDisplayName()
ADMIN 4556-need to htmlspecialchar() $member->getDisplayName()
ADMIN 4636-need to htmlspecialchar() $member->getDisplayName()
MEMBER 379-need to urlencode(?) $this->getDisplayName()
   getNotifyFromMailAddress
PAGEFACTORY 184-need to htmlspecialchar() $member->getDisplayName()
SKIN 1096-need to htmlspecialchar() $memberinfo->getDisplayName()
SKIN 1094-need to htmlspecialchar() $member->getDisplayName()
SKIN 1206-need to htmlspecialchar() $member->getDisplayName()
SKIN 1225-need to htmlspecialchar() $member->getDisplayName()

*/
?>