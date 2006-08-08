<?php
/** 
  * Miniforum - plugin for BLOG:CMS and Nucleus CMS
  * 2005, (c) Josef Adamcik (blog.pepiino.info;josef.adamcik@pepiino.info)
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * 
  * Documentation: http://wakka.xiffy.nl/miniforum
  
  * History:
  *  v0.5.1 (admun)
  *    - fix IE/Opera refresh problem
  *    - dynamic div for shoutbox
  *  v0.5.2 (admun)
  *    - better onload hook for Firefox/IE/Opera
  *    - change script to support multiple shoutbox
  *  v0.6.0 (pepiino)
  *    - multiple templates
  *    - gravatar support
  *    - "remember me" function
  *    - fixed bug with memnber link in nucleus
  * v0.6.1
  *  - XHTML 4.01 compliant
  *  
  * v0.6.2
  *  - administration problem wit IIS fixed
  *  - one sql query problem fixed 
  * v0.6.3
  *  - spam check (using NP_BlackList) thx Admun
  * v0.6.4
  *  - added ability to specify number of posts per page in skinvar (user reqest)
  * v0.6.5
  *  - added NP_Captcha suport (user request)
  * v0.6.6
  *  - fixed bug: now sendinf conten-type and encoding even when sending update
  *
*/

                           
global $DIR_PLUGINS;
if (!is_dir($DIR_PLUGINS)) die('System is not configured properly - NP_MiniForum.php');
//translation
require_once($DIR_PLUGINS.'miniforum/lang.php');

//compatibilyty with between BLOG:CMS and Nucleus CMS
if (!function_exists('sql_fetch_array')) include_once ($DIR_PLUGINS.'miniforum/nucdb.php'); 

//names of files with images of emoticons
require_once($DIR_PLUGINS.'miniforum/emoticons.php');
//PluginTemplate class
require_once($DIR_PLUGINS.'miniforum/template.php');




/**
*
* This plugin alows you to add primitive guestbook or more to your blog. 
* You can use "<%MiniForum(ShowPosts,myforum)%>"  in skin to list posts for forum 
* "myforum" (It's short name of forum) and  "<%MiniForum(ShowForm,myforum)%>" 
* to show form for adding posts
* Plugin provides admin area where you can manage all forums, posts and templates
* 
* @author Josef Adamcik <josef.adamcik@pepiino.info>
* @author Edmond Hui (admun)
* @see http://wakka.xiffy.nl/miniforum
*/
class NP_MiniForum extends NucleusPlugin {
  /*****************************************************************************
  								Plugin info
  ******************************************************************************/
	
	
 function getNAME() { return 'MiniForum';  }
 function getAuthor()  { return 'Josef Adamcik, Edmond Hui (admun)';  }
 function getURL() {  return 'http://wakka.xiffy.nl/miniforum'; }
 function getVersion() { return '0.6.6'; }
 function getMinNucleusVersion() { return 300; }
 function getDescription() { 
  return MF_PLUGIN_DESCRIPTION;
 }
 
 /*****************************************************************************
 							overriden methods
  ******************************************************************************/
 
 /**
 * Installs the plugin.
 */ 
 function install() {
	//options	 
   $this->createOption('MFQuickMenu',MF_ENABLE_QICK_MENU,'yesno','yes');
   $this->createOption('MFPostsPg',MF_POSTS_TO_SHOW,'text','15');
   $this->createOption('MFConvertNl',MF_COVERT_NL,'yesno','yes');
   $this->createOption('MFMaxLineLength',MF_MAX_LINE_LENGTH,'text','70');
   $this->createOption('MFUrlsToLinks',MF_COVERT_URLS,'yesno','no');
   $this->createOption('MFEmoToImg',MF_COVERT_EMOTICONS,'yesno','no');
   $this->createOption('MFEmoDir',MF_EMOTICONS_DIR, 'text','admin/plugins/fancytext/smiles');
   $this->createOption('MFRefresh',MF_REFRESH,'text','30');
   $this->createOption('MFCaptcha',MF_CAPTCHA,'yesno','no');
   
   //tables 
   $this->createTablePost();
   $this->createTableForum();
   $this->createTableTemplates();

	//default template
	$template = new PluginTemplate();
	$template->fillWithDefaultValues();
	$template->saveNew();		
 }
 
 /**
 * This function creates table in db
 */
 function createTablePost() {
   //table for posts
   sql_query("CREATE TABLE `".sql_table("plug_miniforum_post")."` (
             `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
             `idforum` INT UNSIGNED NOT NULL ,
             `uname` VARCHAR( 20 ) NOT NULL ,
             `url` VARCHAR( 30 ) ,
             `memberid` INT,
             `time` INT NOT NULL ,
             `body` TEXT NOT NULL ,
             PRIMARY KEY ( `id` ) ,
             FULLTEXT (
                 `body` 
                 )
             )");	 
 }
 
 /**
 * This function creates table in db
 */
 function createTableForum() {
   // table for forums			 
   sql_query("CREATE TABLE  `".sql_table("plug_miniforum_forum")."` (
              `id` INT NOT NULL AUTO_INCREMENT ,
              `title` VARCHAR( 20 ) ,
              `description` TEXT,
              `short_name` VARCHAR( 20 ) NOT NULL ,
              PRIMARY KEY ( `id` ) ,
              UNIQUE (
                `short_name` 
                )
			  )");
   
   //insert default forum	
   sql_query("INSERT INTO `".sql_table("plug_miniforum_forum")."` (id,title,description,short_name) VALUES(1,'default','default forum','default')");
 }
 
 /**
 * This function creates table in db
 */
 function createTableTemplates() {
   //templates for forum
   sql_query('CREATE TABLE `'.sql_table("plug_miniforum_templates").'` ('
        . ' `template` VARCHAR(20) NOT NULL, '
		. ' `description` TEXT, '
        . ' `PostsHeader` TEXT NOT NULL, '
        . ' `PostBody` TEXT NOT NULL, '
        . ' `PostsFooter` TEXT NOT NULL, '
        . ' `FormLogged` TEXT NOT NULL, '
        . ' `Form` TEXT NOT NULL, '
        . ' `Navigation` TEXT NOT NULL, '
        . ' `Name` TEXT NOT NULL, '
        . ' `NameLin` TEXT NOT NULL, '
        . ' `MemberName` TEXT NOT NULL, '
        . ' `Date` TEXT NOT NULL, '
        . ' `Time` TEXT NOT NULL, '
        . ' `NextPage` TEXT NOT NULL, '
        . ' `PreviousPage` TEXT NOT NULL, '
        . ' `FirstPage` TEXT NOT NULL, '
        . ' `LastPage` TEXT NOT NULL,'
		. ' `UrlsToLinks` ENUM(\'yes\',\'no\') DEFAULT \'yes\','
		. ' `EmoToImg` ENUM(\'yes\',\'no\') DEFAULT \'no\',' 
		. ' `GravDefault` VARCHAR(60),'
		. ' `GravSize` INT UNSIGNED,'
        . ' PRIMARY KEY (`template`)'
        . ' )');
	 
 }

 /**
 * Uninstalls the plugin.
 */
 function unInstall() { 
   sql_query("DROP TABLE `".sql_table('plug_miniforum_forum')."`" );
   sql_query("DROP TABLE `".sql_table('plug_miniforum_post')."`" );
   sql_query("DROP TABLE `".sql_table('plug_miniforum_templates')."`" );
   
   $this->deleteOption('MFQuickMenu');
   $this->deleteOption('MFPostsPg');
   $this->deleteOption('MFConvertNl');
   $this->deleteOption('MFMaxLineLength');
   $this->deleteOption('MFEmoDir');
   $this->deleteOption('MFRefresh');
   $this->deleteOption('MFCaptcha');
 
 }
 
 /**
 * Returns list of used tables
 */
 function getTableList() {	
    return array(sql_table('plug_miniforum_forum'),
                 sql_table('plug_miniforum_post'),
				 sql_table('plug_miniforum_templates')); 
 }          
 
 /**
 * Returns 1, if the given feature is supported.
 */
 function supportsFeature($feature) {
		switch($feature) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

 /**
 * Returns an array containing events. 
 */
 function getEventList() { 
    return array('QuickMenu','PreDeleteMember'); 
 }   	

 /**
 * handles quick menu event
 */
 function event_QuickMenu(&$data) {
    global $member;
    if (($this->getOption('MFQuickMenu') != 'yes')|| (!$member->isAdmin())) return;
    array_push(
        $data['options'],
        array(
            'title' => MF_QM_TITLE,
            'url' => $this->getAdminURL(),
            'tooltip' => MF_QM_TOOLTIP
        )
    );
  }	
  
  /**
  * Handles PreDeleteMember event.
  * Before deleting member, set all his posts in all forums as nonmeber
  */
  function event_PreDeleteMember(&$data) {
    $memberid = $data['member']->getID();
    $query = "UPDATE `".sql_table('plug_miniforum_post')."` SET memberid='0' WHERE memberid='$memberid'";
    sql_query($query); 
  }
  
  /**
  * Returns 1, because the plugin has an admin area.
  */
  function hasAdminArea()
  {
    return 1;
  }

 /**
 * @param $what : what to do. It's case insensitive.
 *	Posible values: 
 *       script - instrument the javascript in header section that perform refresh 
 *       ShowPosts - shows posts from forum
 *       ShowForm - shows add post form
 * @param $forumId : short_name or numerical id of forum.
 * @param forumTemplate - name of the forum template to use. (optional, default 'default')
 * @param $postsPerPage - number of posts to be shown per one page. Usable only when action ($what
 			parameter) is 'showposts'. Optional, default value is taken form the plugin options.
 */
 function doSkinVar($skinType,$what, $forumId = 1,$forumTemplate = 'default', $postsPerPage = -1) {
    global $CONF;
	 
    if (($forumId = $this->getForumId($forumId)) == -1) {
        echo "<p class='error'>".MF_FORUM_DOESNT_EX.".</p>";
        return ;
    }
		
    switch(strtolower($what)) {
        case "script" :
			$this->insertScript($forumId,$forumTemplate,$postsPerPage);
			break;
		case "showposts" :
			?>
			<div id="mf<?php echo $forumId; ?>">
			<?php $this->showPosts($forumId,$forumTemplate,$postsPerPage); ?>
			</div>
			<?php			
            break;
        case "showform" :
            $this->showForm($forumId,$forumTemplate);
            break;    
        default:
            echo "<p class='error'>".MF_UNKNOWN_OPTION.".";
            break;
   }
 }
 
 
 
 /**
 * This function is called by action.php when adding new post to forum. It's also 
 * called when the ajax fucntion is updating the post.
 */
 function doAction($actionType) {
    if ($actionType == "addPost") {
        global $member,$CONF;
		// prepare name and url of sender
        if ($member->isLoggedIn()) {
            $uname = 	$member->getDisplayName();
            $url =      $member->getUrl();
            if (trim($url) == "http://") $url = "";
            $memberid = $member->getID();
        } else {
			if ($this->captchaEnabled()) { //is captcha test passed?
				global $manager;
				$captchaPlugin =& $manager->getPlugin('NP_Captcha');
				$captchaSolution = strip_tags(undoMagic(requestVar('captcha')));
				$captchaKey 	 = strip_tags(undoMagic(requestVar('captchakey')));
				if (!$captchaPlugin->check($captchaKey,$captchaSolution)) {
					return $captchaPlugin->getOption('FailedMsg');
				}
				
			}
			
			
            $uname = 	sql_escape(strip_tags(undoMagic(requestVar('uname'))));
            if ($member->isNameProtected($uname)) return  str_replace('$uname',$uname,MF_NAME_PROTECTED);
            $url = 		sql_escape(strip_tags(undoMagic(requestVar('url'))));
            $memberid = 0;
        }
        
		//prepaere body of the post
        $body = 	htmlspecialchars(undoMagic(requestVar('BODY')),ENT_NOQUOTES);
        $body = 	sql_escape($this->convertNlToBr($body));
        
        $forumId = 	sql_escape(strip_tags(undoMagic(requestVar('FORUMID'))));

        if ($uname == "") return MF_NAME_MISSING;
        if ($body == "") return MF_TEXT_MISSING;
        
        //prepare www or mail adres 
        if ($url != "") {
            if (substr_count($url,"@") == 1) { //i think, it should be e-mail adress
                //so add mailto:
                $url="mailto:".$url;
            } else if (substr_count($url,"http://") == 0) {
                    $url="http://".$url;
            }
        }
        
        //check, if the body of message isn't the same like last post. 
		//If it is, it can be reload of page, or spam
        $query = "SELECT MAX(id) FROM `".sql_table('plug_miniforum_post')."` ".
				 "WHERE (idforum=".$forumId.") AND (uname='".$uname."')";
        $result = sql_fetch_array(sql_query($query));
        
		if ($result[0] !=0 ) {
            $query = "SELECT `body` FROM `".sql_table('plug_miniforum_post')."` ".
					 "WHERE `id`=".$result[0];    
            $result = sql_fetch_array(sql_query($query));
            if (trim($result['body']) == trim($body)) $body = "";
        }
        //inserts the post to the database
        if ($body != "") { 
			  // check for spam attempts, you never know ! 
             global $manager; 
             if ($manager->pluginInstalled('NP_Blacklist') && ($blacklist =& $manager->getPlugin('NP_Blacklist'))) { 
                 if (floatval($blacklist->getVersion()) >= 0.96) { 
                     $spamcheck = array ('type'  => 'Referer', 'data'  => $body, 'return'  => false); 
                     $manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck)); 
                 } else { 
                     if (floatval($blacklist->getVersion()) == 0.95) { 
                         $blacklist->blacklist('NP_MiniForum',$body); 
                     } 
                 } 
             }
             $query = "INSERT INTO `".sql_table('plug_miniforum_post')."` ".
			 		  "(idforum,uname,url,body,time,memberid) ".
					  "VALUES (".$forumId.",'".$uname."','".$url."','".$body."',".time().",".$memberid.")";
             $result = sql_query($query);
        }

		// set "remeber me" cookie 	
		if (isset($_POST['remember'])) {
			$lifetime = time()+2592000;
			setcookie($CONF['CookiePrefix'] . 'comment_user',$uname,$lifetime,'/','',0);
			setcookie($CONF['CookiePrefix'] . 'comment_userid', $url,$lifetime,'/','',0);
		}
		
		//removes parameter page from the url..
        $desturl = $this->rmUrlParam(requestVar('desturl'),'PAGE');
       
		//redirects browser 
        header('Expires: 0');
        header('Pragma: no-cache');
	    Header('Location: '.$desturl);
	    
	    exit();
		
	} elseif ($actionType == "updatePost") { //handles request to update post 
        $forumId = requestVar('forumId');
		$postsPerPage = requestVar('postsPerPage');
        if (($forumId = $this->getForumId($forumId)) == -1) {
            echo "<p class='error'>".MF_FORUM_DOESNT_EX.".</p>";
            return ;
        }
		
		// Send out ontent-type with encoding first
		sendContentType('text/html', '', _CHARSET);	
		
        $this->showPosts($forumId,requestVar('template'),$postsPerPage);		
		
        
    } else {
        return "Unknown action (NP_miniforum)";
    }
 }
 
 
  /*****************************************************************************
 							parsing and output
  ******************************************************************************/
 
 /**
 * Converts all linebreaks to <br /> tags. (Used when adding post. see doAction())
 * @param text -  text to parse 
 */   
 function convertNlToBr($text) {
    if ($this->getOption('MFConvertNl') == 'yes') {
        return str_replace("\n",'<br />',$text);
    } else return $text;
 }

 /**
 * Prepares navigation code and returns it as string. It's used in showPosts 
 * function.
 */
 function prepareNavigation($forumId,$page,$postsPerPage,$templ) {
    //count posts
	$query = "SELECT COUNT(*) FROM ".sql_table('plug_miniforum_post')." WHERE `idforum`=".$forumId;
    $result = sql_fetch_array(sql_query($query));
    $pageCount = ceil($result[0] / $postsPerPage);
    
	//prepare destination url
	$desturl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    $desturl = $this->rmUrlParam($desturl,'PAGE');
    if (strpos($desturl,'?')) $param = '&PAGE='; else $param='?PAGE=';
  
    //create navigation
	
	if ($page != 1) 
		$firstPage = "<a href='".$desturl.$param.(1)."'>".$templ->firstPage."</a>";
    else 
		$firstPage = $templ->firstPage;
	
	if ($page > 1) 
		$prevPage = "<a href='".$desturl.$param.($page - 1)."'>".$templ->previousPage."</a>";
    else 
		$prevPage = $templ->previousPage;
    
	if ($page < $pageCount) 
		$nextPage = "<a href='".$desturl.$param.($page + 1)."'>".$templ->nextPage."</a>";
    else 
		$nextPage = $templ->nextPage;
    
	if ($page != $pageCount) 
		$lastPage = "<a href='".$desturl.$param.($pageCount)."'>".$templ->lastPage."</a>";
    else 
		$lastPage = $templ->lastPage;
    
    $navigation = $templ->navigation;
    $from = array("<%first-page%>","<%prev-page%>","<%cur-page%>","<%page-count%>","<%next-page%>","<%last-page%>");
    $to = array($firstPage,$prevPage,$page,$pageCount,$nextPage,$lastPage);
    for ($i =0; $i < sizeof($from); $i++) {
        $navigation = str_replace($from[$i],$to[$i],$navigation);
    }
   
    return $navigation;
 }
      
 
 /**
 * If word in $text is longer then $max, it'll be breaked into parts separated by 
 * value in $wordWrap (space? &shy;? wbr tag? i'm not sure)
 * Can be simplier, but we have to do more work for the utf-8 encoding, if it's used.
 */
 function wrapLongWords($text,$maxLength) {
	$wordWrap = "&shy;"; //this character will be used for breaking long words.
	
    if (strtolower(_CHARSET) == 'utf-8') {
        $bUsingUtf = true; //test, if utf-8 is used. 
    } else {
        $bUsingUtf = false; 
    }
    
    $wordStart = 0; // character position, where current word started
    $wordLength = 0; // length of word (we need this value separated, becouse text can be in utf-8)
    $lastCopy = 0; //points to the first character, which isn't copied to result yet

	//go through whole text
    for ($i = 0; $i < strlen($text);$i++) {
		 
		//handles utf-8 coded string
        if ($bUsingUtf && (($value = ord($text{$i})) > 127)){
           if($value >= 192 && $value <= 223)
               $i++;
           elseif($value >= 224 && $value <= 239)
               $i = $i + 2;
           elseif($value >= 240 && $value <= 247)
               $i = $i + 3;
        }
		
		//don't wrap link!
		if (($text{$i} == '<') && ($text{$i+1} == 'a')) { 
			$endLink = strpos($text,'>',$i);
			$i = $endLink;
			$wordStart = $i+1;
			$wordLength = 0;
			continue;
		}
		
		//end of link
		if (($text{$i} == '<') && ($text{$i+1} == '/') && ($text{$i+2} == 'a') ) {//end of link
			$i = $i + 3;
			$wordStart = $i+1;
			$wordlLength = 0;
			continue;
		}
		
		//here starts new word
        if (($text{$i} == ' ') || ($text{$i} == '-') || ($text{$i} == "\n")) {
            $wordStart = $i + 1;
            $wordLength = 0;
        }
        
		//word is too long -> insert wordwrap character
		if ($wordLength >= $maxLength - 1) { 
              $newText .= substr($text,$lastCopy ,$i - $lastCopy + 1).$wordWrap;
              $wordStart = $i + 1;
              $lastCopy = $i + 1;
              $wordLength = 0;
        }
        $wordLength++;
    }
	
    $newText .= substr($text,$lastCopy);
    
	return $newText;
 }
 
 /**
 * Converts textual emoticons to images.
 */
 function insertEmoticons($text) {
	 global $emoticons;
	 $iconDir = $this->getOption('MFEmoDir');
	 $textEm = array_keys($emoticons);
	
	 foreach($textEm as $key) {
		 $text = str_replace($key,
							"<img src='$iconDir/{$emoticons[$key]}' alt=\"$key\"  />",
							$text);
	 }
     return $text;
 }
 
 
 /**
 * Converts urls in text to links.
 */
 function urlToHref($text) {
	//  protocol://address/path/
   $text = ereg_replace("[a-zA-Z]+://([.]?[a-zA-Z0-9_/-?&%=])*", "<a href=\"\\0\">\\0</a>", $text);
   //  www.something
   $text = ereg_replace("(^| )(www([.]?[a-zA-Z0-9_/-?&%=])*)", "\\1<a href=\"http://\\2\">\\2</a>", $text);
   return $text;
 }
 
 
 /**
 * Shows the posts from the forum using choosen template.
 */
 function showPosts($forumId,$templName,$postsPerPage) {
	//reads template
	$templ = new PluginTemplate();
	
	// read the template and check if it exists
	if (!$templ->readFromDb($templName)) {
		echo "<p class='error'>".MF_TEMPLATE_DOESNT_EX."</p>";
		return;
	}
	 
    //reads forum information 
    $query = "SELECT * FROM `".sql_table(plug_miniforum_forum)."` WHERE `id`=".$forumId;
    $result = sql_query($query);
    $forum = sql_fetch_array($result);

    // detect current page and number of posts to show per page    
    $page = requestVar('PAGE');
    if (!is_numeric($page) || ($page <= 0)) $page = 1;
    $postsPerPage = ($postsPerPage == -1) ? $this->getOption('MFPostsPg') : $postsPerPage;
    if (($postsPerPage <= 0)) $postsPerPage = 15;

    //prepare forum header and footer
    $from = array('<%title%>','<%description%>','<%navigation%>');
    $to = array($forum['title'],$forum['description'],$this->prepareNavigation($forumId,$page,$postsPerPage,$templ));
    $header = $templ->postsHeader;
    $footer = $templ->postsFooter;
    for ($i = 0; $i<sizeof($from); $i++) {
        $header = str_replace($from[$i],$to[$i],$header);
        $footer = str_replace($from[$i],$to[$i],$footer);
    }
    echo $header; //and print header

    //reads posts 
    $query = "SELECT * FROM `".sql_table("plug_miniforum_post")."` ".
			 "WHERE `idforum`=$forumId ".
			 "ORDER BY id DESC LIMIT  ".(($page - 1) * $postsPerPage).",".$postsPerPage;
    $result = sql_query($query);

    //shows posts
    while ($post = sql_fetch_array($result)) {
        
        //process tag tempelate for name
        if (MEMBER::existsID(intval($post['memberid']))){
            $mem = MEMBER::createFromId($post['memberid']); 
            $uname = $mem->getDisplayName();
            //$url = "?member=".$uname; //doen't work in nucleus
			$url = "?memberid=".$post['memberid']; //doen't work in nucleus, only in BLOG:CMS
            $nametmpl = $templ->memberName;
			//gravatr support
			$gravEmail = $mem->getEmail(); 
			$gravUrl = "http://www.gravatar.com/avatar.php?gravatar_id=".md5($gravEmail)."&amp;default=".urlencode($templ->gravDefault)."&amp;size=".$templ->gravSize;
 			
        } else {
            $uname = $post['uname'];
            $url = $post['url'];
            if ($url == "") {  
                $nametmpl = $templ->name;
				//gravatar support
				$gravUrl = $templ->gravDefault;
            }
            else { 
                $nametmpl = $templ->nameLin;
				//gravatar suport
				$gravEmail = str_replace("mailto:","",$url);
				$gravUrl = "http://www.gravatar.com/avatar.php?gravatar_id=".md5($gravEmail)."&amp;default=".urlencode($templ->gravDefault)."&amp;size=".$templ->gravSize;
            }
        }
        $from = array("<%user-name%>","<%user-link%>");    
        $to = array($uname,$url);
             
        for ($i=0; $i < sizeof($from); $i++) {
              $nametmpl = str_replace($from[$i],$to[$i],$nametmpl);
        }
        
		$body = $post['body'];
		$body = $templ->urlToLink ? $this->urlToHref($body) : $body;
		$body = $templ->emoToImg ? $this->wrapLongWords($body,$this->getOption('MFMaxLineLength')) : $body;
		$body = $this->insertEmoticons($body);
		
        //aply tempelates
        $from = array("<%name%>","<%date%>","<%time%>","<%gravatar%>","<%body%>");
        $to = array($nametmpl,
                    date($templ->date,$post['time']),
                    date($templ->time,$post['time']),
					$gravUrl,
                    $body
              );
        $pbody = $templ->postBody;
        for ($i=0;$i<sizeof($from);$i++) $pbody=str_replace($from[$i],$to[$i],$pbody);    
        echo $pbody;
    }
    echo $footer;
    
 }

 /**
 * Prints a form.
 * @param int $forumId - forum id 
 * @param int @templateName - name of the template to use  
 */
 function showForm($forumId,$templateName) {
	global $CONF,$member; 

	//read template	
	$templ = new Plugintemplate();

	// read the template and check if it exists
	if (!$templ->readFromDb($templateName)) {
		echo "<p class='error'>".MF_TEMPLATE_DOESNT_EX."</p>";
		return;
	}
	
	
    $desturl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	
	//read info about user from "remember me" cookie, if it's available
	$user = cookieVar($CONF['CookiePrefix'] .'comment_user');
    $url = cookieVar($CONF['CookiePrefix'] .'comment_userid');
	
	//header of the form
	$form = "<form class='miniforum' method='post' action='action.php' >
          <fieldset>  
          <input type='hidden' name='action' value='plugin' />
          <input type='hidden' name='name' value='MiniForum' />
          <input type='hidden' name='type' value='addPost' />
          <input type='hidden' name='FORUMID' value='".$forumId."' />
          <input type='hidden' name='desturl' value='".$desturl."' />";
    
	if ($member->isLoggedIn()) {
        $form .=  $templ->formLogged;
    } else {
		if ($this->captchaEnabled()) {
			global $manager;
			//add captcha hidden field with key
			$captchaPlugin =& $manager->getPlugin('NP_Captcha');
			$captchaKey = $captchaPlugin->generateKey();
			$form .= "<input type='hidden' name='captchakey' value='$captchaKey' />";
		}
		
        $form .= $templ->form;
   } 
   $form .= "</fieldset></form>";
   
   
   if ($this->captchaEnabled() && !$member->isLoggedIn()) {
	   //prepare captcha image link
	   //TODO: move width and height to the template
	   //$width = 160;
	   //$height = 80;
	   $captchaImage = $captchaPlugin->generateImgHtml($captchaKey, -1, -1);
   } else {
	   $captchaImage = "";
   }
   
   
   // insert info from cookie (rember me) and captcha image
   $from = array("<%name%>","<%url%>",'<%captcha%>');
   $to = array($user,$url,$captchaImage);
   for ($i=0;$i<sizeof($from);$i++) $form=str_replace($from[$i],$to[$i],$form);	
		
   echo $form;
  }  

  
 /**
 * Inserts javascript which handles auto-updating without javascript.
 */
 function insertScript($forumId,$forumTemplate,$postsPerPage) {
	global $CONF;
	?>
	<!-- code from http://dutchcelt.nl/weblog/article/ajax_for_weblogs/ -->
	<script type="text/javascript">
	  <!--
	  var ajax<?php echo $forumId; ?>=false;
	  /*@cc_on @*/
	  /*@if (@_jscript_version >= 5)
	  try {
		ajax<?php echo $forumId; ?> = new ActiveXObject("Msxml2.XMLHTTP");
	  } catch (e) {
		try {
		  ajax<?php echo $forumId; ?> = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
		  ajax<?php echo $forumId; ?> = false;
		}
	  }
	  @end @*/
	  if (!ajax<?php echo $forumId; ?> && typeof XMLHttpRequest!='undefined') {
		ajax<?php echo $forumId; ?> = new XMLHttpRequest();
	  }
	
	  function getMyHTML<?php echo $forumId; ?>() {
	
		  var temp<?php echo $forumId; ?> = '<?php echo $forumId; ?>';
		  var serverPage<?php echo $forumId; ?> = '<?php echo $CONF['IndexURL']; ?>action.php?action=plugin&name=MiniForum&type=updatePost&forumId='+temp<?php echo $forumId; ?>+'&template=<?php echo $forumTemplate ?>&postsPerPage=<?php echo $postsPerPage?>';
		  var obj<?php echo $forumId; ?> = document.getElementById('mf'+temp<?php echo $forumId; ?>);
		  ajax<?php echo $forumId; ?>.open("GET", serverPage<?php echo $forumId; ?>);
		  ajax<?php echo $forumId; ?>.onreadystatechange = function() {
			if (ajax<?php echo $forumId; ?>.readyState == 4 && ajax<?php echo $forumId; ?>.status == 200) {
			  obj<?php echo $forumId; ?>.innerHTML = ajax<?php echo $forumId; ?>.responseText;
			}
		  }
		  ajax<?php echo $forumId; ?>.send(null);
	
		MFstartRefresh<?php echo $forumId; ?>();
	  }
	
	  function MFstartRefresh<?php echo $forumId; ?>() {
		//.. reload every 5 minutes
		setTimeout("getMyHTML<?php echo $forumId; ?>()",<?php echo $this->getOption('MFRefresh') ?>*1000);
	  }
	
	  // trick learnt from wp wordspew 
	  if(typeof window.addEventListener != 'undefined') {
		//.. gecko, safari, konqueror and standard
		window.addEventListener('load', MFstartRefresh<?php echo $forumId; ?>, false);
	  }
	  else if(typeof document.addEventListener != 'undefined')
	  {
		//.. opera 7
		document.addEventListener('load', MFstartRefresh<?php echo $forumId; ?>, false);
	  }
	  else if(typeof window.attachEvent != 'undefined')
	  {
		//.. win/ie
		window.attachEvent('onload', MFstartRefresh<?php echo $forumId; ?>);
	  }
	// -->  
	</script>
	<?	  
  }
  
  


 /*****************************************************************************
 							other methods
  ******************************************************************************/

 
 
 /**
 * Returns forum id as number. used to convert forum short name, which you can use when 
 * calling a plugin instead of forum number.
 * Returns -1, when forum doesn't exist.
 */ 
 function getForumId($forumId) {
    if (is_numeric($forumId)) {
        //We maybe have right forum id yet. Only check, if it exists.
        $query = "SELECT * FROM `".sql_table(plug_miniforum_forum)."` WHERE `id`=".$forumId;
        $result = sql_query($query);
        if (sql_num_rows($result) == 0) return -1;
    } else {
       //We have to find forum id number.
       $query = "SELECT id FROM `".sql_table(plug_miniforum_forum)."` WHERE short_name='".$forumId."'"; 
       $result = sql_query($query);
       if (sql_num_rows($result) == 0) return -1;
       else {
           $result = sql_fetch_array($result);
           $forumId = $result['id'];
       }
    }
    return $forumId;
 }
 
 /**
 * Removes the given parameter from url. Isn't case sensitive!!
 * e.g.: url: http://something.com?param1=value1&param2=value2&param3=value3
 *       paramName: param2
 *       returns:  http://something.com?param1=value1&param3=value3 
 */
 function rmUrlParam($url,$paramName) {
    $url = trim($url); $paramName=trim($paramName);
    if (!($ppos = strpos($url,$paramName))) return $url;

    //find next &
    $pnextamp = strpos($url,'&',$ppos);

    //look what character is before paramName
    $befpar = substr($url,$ppos - 1,1);

    if ($befpar == '&') {
        $result = substr($url,0,$ppos - 1);
        if ($pnextamp) $result .= substr ($url, $pnextamp, strlen($url) - $pnextamp);
    } else if (($befpar == '?') && (!$pnextamp) ) {
        $result = substr($url,0,$ppos - 1);
    } else if (($befpar == '?') && ($pnextamp) ) {
        $result = substr($url,0,$ppos);
        $result .= substr($url,$pnextamp + 1,strlen($url) - $pnextamp - 1);
    } else $result = $url;
        
    return $result;
 }    
 
 
 /**
 * @access private
 * @return true if the captcha support is enabled AND NP_Capctha installed
 * @since 0.6.5
 */
 function captchaEnabled() {
	 global $manager;
	 return $this->getOption('MFCaptcha') == 'yes' && $manager->pluginInstalled('NP_Captcha');
 }
 
 
}
?>
