<?php
/** 
  * Miniforum - plugin for BLOG:CMS and Nucleus CMS
  * 2005, (c) Josef Adamcik (http://blog.pepiino.info)
  *
  *
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * 
  *  This file contains code for admin area of plugin NP_MiniForum
*/



$strRel = '../../../';

//include config file
if (file_exists($strRel.'cfg.php')) {
    include($strRel.'cfg.php'); //blogcms config
} else {
    include($strRel.'config.php'); //nucleus CMS config
}

//check path to plugins directory
global $DIR_PLUGINS;
if (!is_dir($DIR_PLUGINS)) 
  die('System is not configured properly - NP_MiniForum.php');

// include pluginadmin library
include($DIR_LIBS.'PLUGINADMIN.php');


$pluginpath =$CONF['PluginURL']."miniforum";
$oPluginAdmin = new PluginAdmin('MiniForum');

// Send out Content-type
sendContentType('application/xhtml+xml', 'admin-miniforum', _CHARSET);	

//database compatibilyty between Nucleus CMS and BLOG:CMS
if (!function_exists('sql_fetch_array')) 
  include ($DIR_PLUGINS.'miniforum/nucdb.php'); 

//include template class
require_once($DIR_PLUGINS.'miniforum/template.php');


if (!$member->isLoggedIn() || !$member->isAdmin()) doError(MF_NOT_LOGGED_IN);


//see what page to show
$show = requestVar('show'); //section to show
if (empty($show)) $show = 'forumadmin'; 
	
//variable initialisation
$errMsg = '';
$temp = null;

// ***************************************************************************************
// do action
// ***************************************************************************************

//choose and do an action
switch(requestVar('action')) {
	
	case "saveforum": //- creating new or editing old forum
    	$forumName = 	trim(sql_escape(requestVar('short_name')));
		$forumTitle = 	sql_escape(requestVar('title'));
		$forumDesc = 	sql_escape(requestVar('desc'));
		if (requestVar('forumid') != 0) { // editing forum
			$forumId=  	(int)requestVar('forumid');
			$errMsg = 	NP_MiniForum::updateForum($forumId,$forumName,$forumTitle,$forumDesc);
			$show = 	'editforum';			
		} else { //new forum
			$errMsg = 	NP_MiniForum::addForum($forumName,$forumTitle,$forumDesc);
			$show = 	'forumadmin';
		}
		
		if ($errMsg === true) {//everything is OK 
			header('location: '.$pluginpath.'/index.php?show=forumadmin&insert=1');
			exit;
		}
		
		break;
		
		
    case "deleteforum":
        NP_MiniForum::deleteForum((int)requestVar('forumid'));
		header('location: '.$pluginpath.'/index.php?show=forumadmin&delete=1');
		exit;
        break;
		
    case "deletepost":    
        NP_MiniForum::deletePost((int)requestVar('postid'));
		header('location: '.$pluginpath.'/index.php?show=postadmin&forumid='.requestVar('forumid').'&page='.requestVar('page').'&delete=1');
		break;
		
		
	
	case "changetempl":
		$templ = new PluginTemplate();
		$templ->readFromPost();
		if ($templ->checkData()) {
			$templ->change();
			header('location: '.$pluginpath.'/index.php?show=templadmin&edit=1');
			exit;	
		} else {
			$show = 'edittempl';
		}
		
		break;
		
	case "createtempl":
		$templ = new PluginTemplate();
		$templ->readFromPost();
		if ($templ->checkData()) {
			$templ->saveNew();
			header('location: '.$pluginpath.'/index.php?show=templadmin&create=1');
			exit;						
		} else {
			$show = 'edittempl';
		}
		
		break;
		
	case "deletetempl":
		PluginTemplate::deleteTemplate(sql_escape(requestVar('id')));
		header('location: '.$pluginpath.'/index.php?show=templadmin&delete=1');
		exit;			
		break;
		
		
	case "copytempl":
		PluginTemplate::copyTemplate(sql_escape(requestVar('id')));
		header('location: '.$pluginpath.'/index.php?show=templadmin&copy=1');
		exit;	
		break;
		
	case "defaulttempl":
		$templ = new PluginTemplate();
		$templ->fillWithDefaultValues();
		$show = 'edittempl';
		break;
		
	case "savepost":
		$uname    = trim(sql_escape(requestVar('uname')));
		$postbody = sql_escape(requestVar('postbody'));
		$postid   = (int)requestVar('postid');
        if (($uname == "" ) || ($postbody == "" )) { 
			
			$errMsg = MF_MISSING_POST_BODY_OR_NAME;
			$show = 'editpost';
		} else {
			NP_MiniForum::updatePost($uname,$postbody,$postid);	
			header('location: '.$pluginpath.'/index.php?show=postadmin&forumid='.requestVar('forumid').'&page='.requestVar('page').'&editpost=1');
			exit;			
		}

		break;
	
}


// ***************************************************************************************
// create the admin area page
// ***************************************************************************************

$oPluginAdmin->start();
	
echo '<h2>'.MF_ADMIN_AREA_HEADING.'</h2>';

include "admin/menu.php"; //include horizontal menu


//show page content

switch($show) {
    case "forumadmin":
		$forumList = NP_MiniForum::getForumList();
		include "admin/forums.php";
		break;
		
	case "editforum":
		if (empty($errMsg)) {
			$forum 		=	NP_MiniForum::readForumInfo((int)requestVar('forumid'));
			$forumName  = 	$forum['short_name'];
			$forumTitle = 	$forum['title'];
			$forumDesc  = 	$forum['description'];
		}
		include "admin/editForum.php";
		break;
		
	case "templadmin":
		$templateList = PluginTemplate::getTemplateList();
		include "admin/templates.php";
		break ;
		
	case "postadmin": //show posts
		$page = (int)requestVar('page'); //prepare pagination 
		
		$postsPerPage =	20;
		$forum 	   = 	NP_MiniForum::readForumInfo((int)requestVar('forumid'));
		$postCount = 	NP_MiniForum::getPostCount($forum['id']);
		$pageCount = 	ceil($postCount / $postsPerPage);
		
        if (isset($_POST['prev'])) $page -= 1;
        if (isset($_POST['next'])) $page += 1;
		
		
		$page = ($page > $pageCount)?  $pageCount:  $page;
		$page = ($page < 1)? 1 : $page;
			
		$postList  = 	NP_MiniForum::getPostList($forum['id'],$page,$postsPerPage);		
		include "admin/posts.php";
		break ;
	
	case "editpost":
		$post = NP_MiniForum::getPost((int)requestVar('postid'));
		$forum = NP_MiniForum::readForumInfo($post['idforum']);
		include "admin/editPost.php";
		break;
		
	case 'edittempl':
		if ($templ == null) {
			PluginTemplate::showTemplateForm(requestVar('id'));
		} else{
			$templ->showForm();
		}
		break;
}

//show page footer
$oPluginAdmin->end();


//******************************************************************************
//					FUNCTIONS
//******************************************************************************
/**
* shows error message
*/
function errMsg($msg) {
    echo "<h3>".MF_ERR."!</h3><p>$msg</p>";
    global $oPluginAdmin;
    $oPluginAdmin->end();
    exit();
}

?>
