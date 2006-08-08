<?php

include('.\..\..\..\..\config.php');
global $DIR_PLUGINS;
include_once ($DIR_PLUGINS.'gallery/config.php');

//from 0.61a to 0.75
global $NPG_CONF;

$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_views').' ( '.
		'vpictureid int unsigned not null, '.
		'views int unsigned )';
sql_query($query);
		
$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_views_log').' ( '.
		'vlpictureid int unsigned not null, '.
		'ip varchar(20), '.
		'time timestamp )';
sql_query($query);

//new option
global $manager;
if ($manager->pluginInstalled('NP_gallery')) 
{
	$plugin =& $manager->getPlugin('NP_gallery');
	if ($plugin != NULL)
		if($plugin->getoption('deletetables')) $plugin->createOption('deletetables',__NPG_OPT_DONT_DELETE_TABLES,'yesno','no'); 
}

//new config
if (!$NPG_CONF['viewtime']) setNPGoption('viewtime', 30 * 60);


//make new table
$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_template_desc').' ( '.
	'tdid int unsigned not null auto_increment PRIMARY KEY, '.
	'tdname varchar(20), '.
	'tddesc varchar(200) )';
sql_query($query);

//if it already exists and there are no rows already, then add default
$query = 'select * from '.sql_table('plug_gallery_template_desc');
$res = sql_query($query);
if(!mysql_num_rows($res)) {
	$query = 'insert into '.sql_table('plug_gallery_template_desc').' (tdid, tdname, tddesc) values (NULL, "061default", "default templates from 0.61")';
	sql_query($query);
	$new_id = mysql_insert_id();
	if(!$NPG_CONF['template']) setNPGOption('template', $newid);
}

//change previous template table and add reference to template_desc
$query = 'show columns from '.sql_table('plug_gallery_template').' like "tdesc"';
$res = sql_query($query);
if(!mysql_num_rows($res)) {
	$query = 'ALTER TABLE '.sql_table('plug_gallery_template').
		' add column tdesc int unsigned first ';
	sql_query($query);
	if(!$new_id) $new_id = 1;
	$query = 'UPDATE '.sql_table('plug_gallery_template').' set tdesc = '.$new_id;
	sql_query($query);
}


$query = 'show columns from '.sql_table('plug_gallery_comment').' like "cuser"';
$res = sql_query($query);
if(!mysql_num_rows($res)) {
	$query = 'ALTER TABLE '.sql_table('plug_gallery_comment').
		' add column cuser varchar(40), '.
		' add column cmail varchar(100), '.
		' add column chost varchar(60), '.
		' add column cip varchar(15)';
	sql_query($query);
}


$query = 'show columns from '.sql_table('plug_gallery_album').' like "commentsallowed"';
$res = sql_query($query);
if(!mysql_num_rows($res)) {
	$query = 'ALTER TABLE '.sql_table('plug_gallery_album').
		' add column commentsallowed tinyint DEFAULT 1 ';
	sql_query($query);
}

//new template
if (!NPG_TEMPLATE::exists('default075')) {
	$template = new NPG_TEMPLATE(NPG_TEMPLATE::createnew('default075','default 0.75 templates'));
	if(!$NPG_CONF['template']) setNPGOption('template', $template->getID());
	
	$name = 'LIST_HEADER';
	$content = '<%breadcrumb%><hr/><table width=100% ><thead>'
			.'<tr><th><a href="<%sortbytitle%>">Title</a></th>'
			.'<th><a href="<%sortbydescription%>">Description</a></th>'
			.'<th><a href="<%sortbyowner%>">Owner</a></th>'
			.'<th><a href="<%sortbymodified%>">Last Modified</a></th>'
			.'<th><a href="<%sortbynumber%>">Images</a></th></tr></thead><tbody>';
	$template->setTemplate($name, $content);
	
	$name = 'LIST_BODY';
	$content = '<tr><td><a href="<%albumlink%>"><%title%></a></td>'
			.'<td><%description%></td>'
			.'<td><%ownername%></td>'
			.'<td><%modified%></td>'
			.'<td><%numberofimages%></td></tr>';
	$template->setTemplate($name, $content);
	
	$name = 'LIST_FOOTER';
	$content = '</tbody></table><hr/><br />'
			.'<%if(canaddalbum)%>'
			.'<a href="<%addalbumlink%>">Add New Album | </a>'
			.'<%endif%>'
			.'<%if(canaddpicture)%>'
			.'<a href="<%addpictureslink%>"onclick="window.open(this.href,\'addpicture\',\'status=no,toolbar=no,scrollbars=no,resizable=yes,width=600,height=400\');return false;">'
			.' Add Pictures</a>'
			.'<%endif%>';
	$template->setTemplate($name, $content);
	
	//
	$name = 'ALBUM_HEADER';
	$content = '<%breadcrumb%><hr/><div id="NPG_thumbnail"><ul class="thumbnail">';
	$template->setTemplate($name, $content);
	
	$name = 'ALBUM_BODY';
	$content = '<li><a href="<%picturelink%>"><img style="<%centeredtopmargin(140,-10)%>" src="<%thumbnail%>" /></a><br/><%picturetitle%><br/><%pictureviews%> views</li>';
	$template->setTemplate($name, $content);
	
	$name = 'ALBUM_FOOTER';
	$content = '</ul></div><div id="NPG_footer"><br /><hr/>'
			.'<%if(caneditalbum)%>'
			.'<a href="<%editalbumlink%>">Modify Album </a> | '
			.'<%endif%>'
			.'<%if(canaddpicture)%>'
			.'<a href="<%addpicturelink%>"onclick="window.open(this.href,\'imagepopup\',\'status=no,toolbar=no,scrollbars=no,resizable=yes,width=480,height=360\');return false;">Add Picture</a>'
			.'<%endif%>'
			.'</div>';
	$template->setTemplate($name, $content);
	
	//
	$name = 'ITEM_HEADER';
	$content = '<%breadcrumb%><br/><a href="<%previouslink%>"> Previous |</a><a href="<%nextlink%>"> Next</a><hr/><div id="NPG_picture">';
	$template->setTemplate($name, $content);
	
	$name = 'ITEM_BODY';
	$content = '<a href="<%fullsizelink%>" onclick="window.open(this.href,\'imagepopup\',\'status=no,toolbar=no,scrollbars=auto,resizable=yes,width=<%width%>,height=<%height%>\');return false;">'
			.'<img src="<%intermediatepicture%>" /></a>';
	$template->setTemplate($name, $content);
	
	$name = 'ITEM_FOOTER';
	$content = '</div><div id="NPG_footer"><br /><%description%><br/><br/>Last modified by <%owner%> on <%date%> '
			.'<%if(caneditpicture)%>'
			.'<a href="<%editpicturelink%>">Edit</a>'
			.' | <a href="<%deletepicturelink%>">Delete</a>'
			.'<%endif%>'
			.'<br/></div>'
			.'<div class="contenttitle"><h2>Comments</h2></div><%comments%>'
			.'<div class="contenttitle"><h2>Add Comment</h2></div><%commentform%>';	
	$template->setTemplate($name, $content);
	
	$name = 'COMMENT_BODY';
	$content = '<div class="itemcomment id<%memberid%>">'
			.'<h3><a href="<%userlinkraw%>"'
			.'title="<%ip%> | Click to visit <%user%>\'s website or send an email">'
			.'<%user%></a> wrote:</h3>'
			.'<div class="commentbody">'
			.'<%body%></div><div class="commentinfo"><%date%> <%time%></div></div>'	;
	$template->setTemplate($name, $content);
}

setNPGoption('currentversion',75);

include('np_gallery_update075.php');
?>
