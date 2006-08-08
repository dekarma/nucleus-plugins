<?php
/** 
  * Miniforum - plugin for BLOG:CMS and Nucleus CMS
  * 2005, (c) Josef Adamcik (blog.pepiino.info)
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

/**
* shows error message
*/
function errMsg($msg) {
    echo "<h3>".MF_ERR."!</h3><p>$msg</p>";
    global $oPluginAdmin;
    $oPluginAdmin->end();
    exit();
}

//******************************************************************************
//					forum management
//******************************************************************************

/**
 Shows list of forums with basic informations and actions (delete, edit)
*/
function showForumList() {
    global 	$pluginpath;
    echo 	"<table><thead><tr><th>".MF_FORUM."</th><th>".MF_TITLE."</th><th>".
			MF_DESCRIPTION."</th><th>".MF_POSTS."</th><th colspan='2'>".MF_ACTIONS.
			"</th></tr></thead><tbody>";
			
    $query = "SELECT * FROM `".sql_table(plug_miniforum_forum)."`";            
    $result = sql_query($query);
    
	while ($forum = sql_fetch_array($result)) {
        echo "<tr>".
        	 "<td>".$forum['short_name']."</td>".
			 "<td>".$forum['title']."</td>".
			 "<td>".$forum['description']."</td>".
			 "<td><a href='".$pluginpath."?action=showposts&amp;forumid=".$forum['id']."'>".MF_SHOW."</a></td>".
			 "<td><a href='".$pluginpath."?action=edit&amp;forumid=".$forum['id']."'>".MF_EDIT_INFO."</a></td>".
			 "<td><a href='".$pluginpath."?action=delete&amp;forumid=".$forum['id']."'".
		     " onclick='return confirm(\"".MF_CONFIRM_FORUM_DELETE."\");'>".MF_DELETE."</a></td>".
			 "</tr>";
    }
    echo "</tbody>  
          </table> ";
}

function showNewForm() {
    global $pluginpath;
    echo "<form method='post' action='".$pluginpath."/index.php'>
          <table>
          <tbody>
          <tr>
            <td>".MF_SHORT_NAME." ".MF_SHORT_NAME_CHARS.":</td>
            <td><input type='text' name='short_name' size='20' maxlength='20' /></td>
          </tr>
          <tr>
            <td>".MF_TITLE."</td>
            <td><input type='text' name='title' size='20' maxlength='20' /></td>
          </tr>
          <tr>
            <td>".MF_DESCRIPTION."</td>
            <td><textarea name='desc' rows='3' columns='20'></textarea></td>
          </tr>
          </tbody>
          </table> 
          <input type='submit' value='".MF_CREATE_FORUM_BUTTON."' />
          <input type='hidden' name='action' value='newforum' />
          </form>       
    ";
}

/**
* creates new forum
*/
function addForum() {
    $shortName = trim(requestVar('short_name'));
    $title = 	 requestVar('title');
    $desc = 	 requestVar('desc');
    
	if ($shortName == "") errMsg(MF_MISSING_SHORT_NAME);
    if (!ereg('^[0-9a-zA-Z_\-]+$',$shortName)) errMsg(MF_WRONG_SHORT_NAME);
    
    // check, if the short name is uniqe
    $query= "SELECT id FROM `".sql_table('plug_miniforum_forum').
			"` WHERE `short_name`='$shortName'";
    if (sql_num_rows(sql_query($query)) != 0) errMsg(MF_SHORT_NAME_USED); 
    
    $query= "INSERT INTO `".sql_table('plug_miniforum_forum').
			"` (`title`,`description`,`short_name`) VALUES ('$title','$desc','$shortName')";
    sql_query($query);
}




/**
* deletes forum
*/
function deleteForum($forumid) {
    global $pluginpath, $oPluginAdmin;
	$query = "DELETE FROM `".sql_table('plug_miniforum_forum').
			 "` WHERE id='$forumid'";
	sql_query($query);
	
	//delete all posts in this forum
	$query = "DELETE FROM `".sql_table('plug_miniforum_post').
		     "` WHERE idforum='$forumid'";
	sql_query($query);
}


/**
* Shows the form for changing atributes of forum.
*/
function editForum($forumid) {
    global $pluginpath;
    
	$result = sql_query("SELECT * FROM `".sql_table('plug_miniforum_forum').
						   "` WHERE id='$forumid'");
	$forum = sql_fetch_array($result);
    
    
    echo "<h3>".str_replace('$forum_name',$forum['short_name'],MF_CHANGE_FORUM)."</h3>";
    echo "<form method='post' action='$pluginpath/index.php'>
          <input type='hidden' name='forumid' value='$forumid' /> 
          <input type='hidden' name='action' value='changeforum' />
          <table>
          <tr>
            <td>".MF_SHORT_NAME." ".MF_SHORT_NAME_CHARS.":</td>
            <td><input type='text' name='short_name' size='20' maxlength='20' value='".
				$forum['short_name']."'/></td>
          </tr>
          <tr>
            <td>".MF_TITLE."</td>
            <td><input type='text' name='title' size='20' maxlength='20' value='".
				$forum['title']."' /></td>
          </tr>
          <tr>
            <td>".MF_DESCRIPTION."</td>
            <td><textarea name='desc' rows='3' columns='20'>".
				$forum['description']."</textarea></td>
          </tr>
          </table>
          <input type='submit' value='".MF_CHANGE_FORUM_BUTTON."' /></form>";
}

/**
* Changes atributes if the forum.
*/
function changeForum($forumid) {
    $shortName = trim(requestVar('short_name'));
    $title = 	 sql_escape(requestVar('title'));
    $desc = 	 sql_escape(requestVar('desc'));
    
	if ($shortName == "") errMsg(MF_MISSING_SHORT_NAME);
    if (!ereg('^[0-9a-zA-Z_\-]+$',$shortName)) errMsg(MF_WRONG_SHORT_NAME);
    
    // check, if the short name is uniqe
    $query= "SELECT id FROM `".sql_table('plug_miniforum_forum').
			"` WHERE `short_name`='$shortName' AND id!='$forumid'";
    if (sql_num_rows(sql_query($query)) != 0) errMsg(MF_SHORT_NAME_USED); 
    
    $query= "UPDATE `".sql_table('plug_miniforum_forum').
			"` SET `title`='$title',`description`='$desc',`short_name`='$shortName'".
			" WHERE id='$forumid'";
    sql_query($query);
}




//******************************************************************************
//						posts management
//******************************************************************************

/*
* shows list of posts
*/
function showPosts($forumid) {
    global $pluginpath, $page;
    
    $query = "SELECT `short_name` FROM `".sql_table('plug_miniforum_forum').
			 "` WHERE id='$forumid'";
	$result = sql_query($query);
    $forum = sql_fetch_array($result);			 
    
    echo "<h3>".MF_LISTED_POSTS." '".$forum['short_name']."'</h3>";
    
    $query = "SELECT COUNT(*) FROM `".sql_table('plug_miniforum_post').
			 "` WHERE idforum='$forumid'";
    $result = sql_query($query);
    $result = sql_fetch_array($result);
    $postsCount = $result[0];

    if ($postsCount == 0) {
        echo str_replace('$forum_name',$forum['short_name'],MF_FORUM_EMPTY);
    } else {
        $postsPerPage= 20;
        
        $pageCount = ceil($postsCount / $postsPerPage);
        
        if (isset($_POST['prev']) && ($page > 1)) $page -= 1;
        if (isset($_POST['next']) && ($page < $pageCount)) $page += 1;

        $tmp = str_replace('$current_page',$page,MF_PLIST_CURRENT_PAGE);
        $tmp = str_replace('$page_count',$pageCount,$tmp);
        $navigation = "<form method='post' action='$pluginpath/'>".
            "<table class='navigation'><tr>".
            "<input type='hidden' name='action' value='showposts' />".
            "<input type='hidden' name='forumid' value='$forumid' />".
            "<input type='hidden' name='page' value='$page' />".
            "<td><input type='submit' name='prev' value='".MF_PLIST_PREV."' /></td>".
            "<td>$tmp</td>".
            "<td><input type='submit' name='next' value='".MF_PLIST_NEXT."' /></td>".
            "</tr></table></form>";
        
        echo $navigation;
        echo "<table><thead><tr>
            <th>".MF_PLIST_INF."</th><th>Text</th><th colspan='2' >".MF_PLIST_ACTIONS."</th>
            </tr></thead>
            <tbody>";
            
        $query = "SELECT * FROM `".sql_table('plug_miniforum_post').
				 "` WHERE idforum='$forumid' ".
				 "ORDER BY id DESC LIMIT ".(($page-1)* $postsPerPage).",".$postsPerPage;
        $result = sql_query($query);

        while($post = sql_fetch_array($result)) {
            echo "<tr>"; //
            echo "<td>".$post['uname'];
            if ($post['memberid'] != 0) echo " (member)";
            echo "<br />".date("d.m.y",$post['time']).",".date("H:i",$post['time'])."</td>";    
            echo "<td>".$post['body']."</td>";
            echo "<td><a href='$pluginpath/?action=deletepost&amp;postid=".$post['id'].
				 "&amp;forumid=$forumid&amp;page=$page' ".
			     "onclick='return confirm(\"".MF_CONFIRM_POST_DELETE."\");' >".MF_DELETE.
				 "</a></td>";
            echo "<td><a href='$pluginpath/?action=editpost&amp;postid=".
				 $post['id']."&amp;page=$page'>".MF_EDIT."</a></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo $navigation;
    }
}


function deletePost($postid) {
    global $pluginpath,$page, $oPluginAdmin;
	$query = "DELETE FROM `".sql_table('plug_miniforum_post').
			 "` WHERE id='$postid'";
	sql_query($query);
	echo MF_POST_DELETED;
}

function editPost($postid) {
    $query = "SELECT * FROM `".sql_table('plug_miniforum_post').
			 "` WHERE id='$postid'";
    $result = sql_query($query);
    $post = sql_fetch_array($result);

    $query = "SELECT * FROM `".sql_table('plug_miniforum_forum').
		 	 "` WHERE id='".$post['idforum']."'";
    $result = sql_query($query);
    $forum = sql_fetch_array($result);

    global $pluginpath,$page;
        
    echo "<h3>".MF_EDIT_POST."</h3>";

    if (requestVar('change')) {
        if ((requestVar('uname') == "" ) || (requestVar('postbody') == "" )) 
			errMsg("User name and post body must be specified.");
        
        $query = "UPDATE `".sql_table('plug_miniforum_post')."`".
            	 " SET uname='".sql_escape(requestVar('uname')).
				 "', url='".sql_escape(requestVar('ulink')).
				 "', body='".sql_escape(requestVar('postbody')).
				 "' WHERE id='".$postid."'";
        sql_query($query);    
                
        echo MF_POST_CHANGED."<br /><br />";
        showPosts($forum['id']);
		
    } else {
        echo "<form method='post' action='$pluginpath/index.php'>
        <table><tbody>
        <tr><td>".MF_USER_NAME."</td><td><input type='text' name='uname' value='"
			.$post['uname']."' size='40' maxlength='20' /></td></tr>
        <tr><td>".MF_USER_URL."</td><td><input type='text' name='ulink' value='"
			.$post['url']."' size='40' maxlength='30' /></td></tr>
        <tr><td>".MF_POST_BODY."</td><td><textarea name='postbody' rows='4' cols='50'>"
			.$post['body']."</textarea></td></tr>
        </tbody></table>
        <input type='submit' value='".MF_CHANGE_POST_BUTTON."' />
        <input type='hidden' name='action' value='editpost' />
        <input type='hidden' name='change' value='true' />
        <input type='hidden' name='postid' value='$postid' />
        <input type='hidden' name='page' value='".$page."' />
        </form>";
        showPosts($forum['id']);
    }
}

//******************************************************************************
//						tempelate management
//******************************************************************************

function showTemplateList() {
    global $pluginpath;
 
	echo "<table><thead><tr><th>".MF_TEMPLATE."</th><th>".MF_DESCRIPTION.
		 "</th><th colspan='3'>".MF_ACTIONS."</th></tr></thead>
          <tbody>";
    
    $query = "SELECT template,description ".
             "FROM `".sql_table("plug_miniforum_templates")."`";
	  $result = sql_query($query);
	
    while ($templ = sql_fetch_array($result)) {
        echo "<tr>";
        echo "<td>".$templ['template']."</td>";
        echo "<td>".$templ['description']."</td>";
        echo "<td><a href='".$pluginpath."?action=copytempl&amp;id=".$templ['template']."'>".
			 MF_COPY."</a></td>";
        echo "<td><a href='".$pluginpath."?action=edittemplform&amp;id=".$templ['template']."'>".
		     MF_EDIT_TEMPL."</a></td>";
        echo "<td><a href='".$pluginpath."?action=deletetempl&amp;id=".$templ['template']."'".
		     " onclick='return confirm(\"".MF_CONFIRM_TEMPLATE_DELETE."\");'>".
			 MF_DELETE."</a></td>";
        echo "</tr>";
    }
    echo "</tbody>  
          </table> ";
	
}


function copyTemplate($idt) {
	
	$templ = new PluginTemplate();
	$templ->readFromDb($idt);
	$templ->newIdt = "clone_".$templ->idt;
	$templ->saveNew();
}

function deleteTemplate($idt) {
	$query = "DELETE FROM `".sql_table('plug_miniforum_templates')."` ".
			 "WHERE template='$idt'";
				
	sql_query($query);
}

function showTemplateForm($idt = "") {
	$tmpl = new PluginTemplate();
	if ($idt != "")	$tmpl->readFromDb($idt);
	$tmpl->showForm();
}


//******************************************************************************
//						Body of php file
//******************************************************************************
$strRel = '../../../';


if (file_exists($strRel.'cfg.php')) {
    include($strRel.'cfg.php'); //blogcms config
} else {
    include($strRel.'config.php'); //nucleus CMS config
}


global $DIR_PLUGINS;
if (!is_dir($DIR_PLUGINS)) 
  die('System is not configured properly - NP_MiniForum.php');
require_once($DIR_PLUGINS.'miniforum/lang.php');

//compatibilyty between Nucleus CMS and BLOG:CMS
if (!function_exists('sql_fetch_array')) 
  include ($DIR_PLUGINS.'miniforum/nucdb.php'); 
//template class
require_once($DIR_PLUGINS.'miniforum/template.php');


if (!$member->isLoggedIn())
    doError(MF_NOT_LOGGED_IN);

include($DIR_LIBS.'PLUGINADMIN.php');

$pluginpath =$CONF['PluginURL']."miniforum";

// create the admin area page
$oPluginAdmin = new PluginAdmin('MiniForum');
$oPluginAdmin->start();

echo '<h2>'.MF_ADMIN_AREA_HEADING.'</h2>';
echo '<ul><li><a href="http://wakka.xiffy.nl/miniforum">'.MF_DOCLINK.'</a></li>';
echo '<li><a href="http://forum.nucleuscms.org/viewtopic.php?t=8810" >'.MF_FORUMLINK.'</a></li></ul>';
$page = requestVar('page'); 
if ($page == 0) $page = 1;


//choose action
switch(requestVar('action')) {
    case "newforum":
        addForum();
        break;
    case "delete":
        deleteForum(requestVar('forumid'));
        break;
    case "edit":
        editForum(requestVar('forumid'));
        break;
    case "changeforum":
        changeForum(requestVar('forumid'));
        break;
    case "deletepost":    
        deletePost(requestVar('postid'));
    case "showposts":
        showPosts(requestVar('forumid'));
        break;
    case "editpost":
        editPost(requestVar('postid'));
        break;
	case "edittemplform":
		showTemplateForm(requestVar('id'));
		break;
	case "changetempl":
		$templ = new PluginTemplate();
		$templ->readFromPost();
		if ($templ->checkData()) {
			$templ->change();
			echo MF_TEMPLATE_CHANGED;
			
		} else {
			$templ->showForm();
		}
		
		break;
	case "createtempl":
		$templ = new PluginTemplate();
		$templ->readFromPost();
		if ($templ->checkData()) {
			$templ->saveNew();
			echo MF_TEMPLATE_CREATED;
			
		} else {
			$templ->showForm();
		}
		
		break;
	case "deletetempl":
		deleteTemplate(requestVar('id'));
		break;
	case "newtemplform":
		showTemplateForm();
		break;
	case "copytempl":
		copyTemplate(requestVar('id'));
		break;
	case "defaulttempl":
		$templ = new PluginTemplate();
		$templ->fillWithDefaultValues();
		$templ->showForm();
		break;
	
}


echo "<h3>".MF_FORUM_LIST_HEADING."</h3>";
showForumList();


echo "<h3>".MF_CREATE_FORUM_HEADING."</h3>";
showNewForm();

echo "<h2>".MF_TEMPLATES."</h2>";
showTemplateList();
echo "<a href='$pluginpath/?action=newtemplform'>".MF_NEW_TEMPLATE."</a><br />".
	 "<a href='$pluginpath/?action=defaulttempl'>".MF_DEFAULT_TEMPLATE."</a>";


$oPluginAdmin->end();

?>
