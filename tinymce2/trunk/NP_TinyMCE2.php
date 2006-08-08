<?php

/**
  * Plugin for Nucleus CMS (http://plugins.nucleuscms.org/)
  * Copyright (C) 2003 The Nucleus Plugins Project
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  *
  * see license.txt for the full license
  * or visit http://www.gnu.org/copyleft/gpl.html  
  */

/**
 *	Usage: Description available under http://plugins.nucleuscms.org
 *
 *	Versions:
 *  1.20	2006-07-29 kg (Kai Greve - http://kgblog.de)
 *  	- allow html tags that are necessary for videos (object, param, embed) 
 *  	- lost focus bug (IE) solved  
 *  	- member option for the advanced features
 *  	- second butoon bar with advanced features 
 *	1.10	2006-06-29 kg
 *		- javascript also works in IE (bug fixed)  
 *	1.00	2006-06-28 kg
 *		- bypass the linebreak convention instead of changing it 
 *		- allow vertical resizing of the textfield  
 *		- added buttons for outdent and indent 
 *		- don't use relative urls (neeeded for fancy urls) 
 *		- regard member options also on bookmarklet pages (bug removed)
 *	0.91	2006-06-14 kg
 *		- enable/disable wysiwyg as a memberoption
 *		- additional button for backgroundcolor and image
 *		- recover nucleus tags before saving the item to the database 
 *		- using the inbuild nucleus media manager for images 
 *	0.9c	2005-09-28  eph
 *		- upgrade to TinyMCE 2RC3
 *		- lots of path fixes
 *		- experimental GZip option. Should load quicker if it works
 *	0.9b	2005-09-24  eph
 *		- upgrade to TinyMCE 2RC2
 *		- addition of File Manager
 *		- bugfixes and cleaner uninstall
 *	0.9 	2005-07-16	roel
 *		- initial implementation, mostly copied over from NP_EditControls by karma - http://demuynck.org 
 *
 */

class NP_TinyMCE2 extends NucleusPlugin {

    var $memberid, $LinebreakConversion; 

	function NP_TinyMCE2() {
		// $this->baseUrl = $this->getAdminURL();
		// hardcoded relative path to avoid domain security issues (IE6 'Access is denied' error) 
		global $CONF;
		$this->baseUrl = str_replace ($CONF['AdminURL'],'',$this->getAdminURL()); 
	}

	function getName() 		{ return 'TinyMCE2'; }
	function getAuthor()  	{ return 'karma | roel | eph | kg'; }
	function getURL()  		{ return 'http://plugins.nucleuscms.org/'; }
	function getVersion() 	{ return '1.2'; }
	function getMinNucleusVersion() { return 300; }
	function getDescription()
	{
		return 'Makes it possibe to use TinyMCE (a WYSIWYG XHTML 1.0 editor) with Nucleus CMS. Mozilla, MSIE and FireFox (Safari experimental).';
	}

	/**
	 * Make sure plugin still works when a database table prefix is activated for 
	 * the Nucleus installation. (Nucleus refuses to install plugins which do not
	 * support SqlTablePrefix when a database prefix is active)
	 */

	function supportsFeature($what) {
   	switch($what) {
         	case 'HelpPage':
	        		return 0;
         			break;
			case 'SqlTablePrefix':
					return 1;
					break;
        	default:
           			return 0;
     	}
  	}

	function install() {
		// create plugin options (member options)
		$this->createMemberOption('use_tinymce', 'Use TinyMCE WYSIWYG editor', 'yesno', 'yes');
		$this->createMemberOption('use_advanced', 'Use second button bar with advancded features', 'yesno', 'yes');

		// create plugin options (admin)
		$this->createOption('extended_elements', 'Extended valid elements that are allowed in TinyMCE', 'text', 'object[*],param[*],embed[*]');
		$this->createOption('use_tgzip', 'Use TinyMCE GZip compression (experimental)', 'yesno', 'no');
		
		// disable the default javascript editbar that comes with nucleus
		mysql_query("UPDATE ".sql_table('config')." SET value='1' WHERE name='DisableJSTools'");
	}
	
	function _getLinebreakConversion($blog) {
		 $sql = "SELECT bconvertbreaks as result FROM ".sql_table('blog')." WHERE bnumber='".$blog."'";
		 return quickQuery($sql);
	}

	function unInstall() {
		// restore to standard settings
		mysql_query("UPDATE ".sql_table('config')." SET value='2' WHERE name='DisableJSTools'");
	}

	/**
	 * List of events we want to subscribe to
	 */
	function getEventList() {
		return array(
			'AdminPrePageHead', 			// include javascript on admin add/edit pages
			'BookmarkletExtraHead',			// include javascript on bookmarklet pages
			'PreSendContentType', 			// we need to force text/html instead of application/xhtml+xml
			'PreUpdateItem', 				// to recover Nucleus tags before the database is updated
			'PreAddItem', 				// to recover Nucleus tags before a new item is stored in the database
			'PrepareItemForEdit'		// to add breaks for the Editor if necessary
		);
	}
	
	/**
	 * Recover the default nucleus tags after TinyMCE  
	 * has converted the brackets to entities
	 */
	function _recoverTags (&$data)
	{
		$data['body']=preg_replace('/(&lt;)%(.*)%(&gt;)/Usi', '<%\2%>', $data['body']);
		$data['more']=preg_replace('/(&lt;)%(.*)%(&gt;)/Usi', '<%\2%>', $data['more']);
	}
	
	/**
	 * Before an item is sent to the textarea for editing  
	 */
	function event_PrepareItemForEdit(&$data)
	{ 
		if (($this->getMemberOption($data['item']['authorid'],'use_tinymce')=='yes')&&($this->_getLinebreakConversion($data['item']['blogid'])==1)){
			$data['item']['body']= addBreaks ($data['item']['body']);
			$data['item']['more']= addBreaks ($data['item']['more']);
		}
	}
	
	/**
	 * Before an item is updated in the database   
	 */
	function event_PreUpdateItem(&$data)
	{
		$this->_recoverTags($data);
		if (($this->getMemberOption($data['item']['authorid'],'use_tinymce')=='yes')&&($this->_getLinebreakConversion($data['item']['blogid'])==1)){
			$data['item']['body']= removeBreaks ($data['item']['body']);
			$data['item']['more']= removeBreaks ($data['item']['more']);
		}
	}
	
	/**
	 * Before a new item is written into the database   
	 */
	function event_PreAddItem(&$data)
	{
		$this->_recoverTags($data); 
		if (($this->getMemberOption($data['item']['authorid'],'use_tinymce')=='yes')&&($this->_getLinebreakConversion($data['item']['blogid'])==1)){
			$data['item']['body']= removeBreaks ($data['item']['body']);
			$data['item']['more']= removeBreaks ($data['item']['more']);
		}
	}
	
	/**
	 * Hook into the <head> section of bookmarkler area pages.
	 * Insert extra script/css includes there.
	 */
	function event_BookmarkletExtraHead(&$data)
	{
		global $member, $blogid;
		$this->memberid=$member->id;
		$this->LinebreakConversion=$this->_getLinebreakConversion($blogid);
		$this->_getExtraHead($data['extrahead']);	
	}

	/**
	 * Hook into the <head> section of admin area pages. When the requested page is an "add item" or
	 * "edit item" form, get the memberid and include the extra code.
	 */
	function event_AdminPrePageHead(&$data) 
	{
		global $member, $blogid;
		$action = $data['action']; 
		if (($action != 'createitem') && ($action != 'itemedit'))
			return;
	
		$this->memberid=$member->id;
		$this->LinebreakConversion=$this->_getLinebreakConversion($blogid);
		$this->_getExtraHead($data['extrahead']);
	}	
	
	/**
	 * Returns the extra code that needs to be inserted in the <head>...</head> section of pages that
	 * use tinyMCE
	 */
	function _getExtraHead(&$extrahead)
	{
		global $CONF, $manager, $itemid, $blog;
		
		// get the options for the current blog
		$bUseEditor	= ($this->getMemberOption($this->memberid, 'use_tinymce') == 'yes');

		// add code for html editor
		if ($bUseEditor)
		{
			// To avoid conflicts if a other user use only textmode we must set this on all calls
			$CONF['DisableJsTools'] = 1; // overrule simple global settings
			
			// GZip compression?
			if ($this->getOption('use_tgzip') == 'yes')
				$editorCode = '<script type="text/javascript" src="'.$this->baseUrl.'tiny_mce_gzip.php"></script>';
			else 
				$editorCode = '<script type="text/javascript" src="'.$this->baseUrl.'tiny_mce.js"></script>';

			$editorCode .= '<script type="text/javascript">
var f_n, w_n, mediapopup;	
function includeImage(collection, filename, type , width, height){
	w_n.document.forms[0].elements[f_n].value = "'.$CONF['MediaURL'].'" + collection + "/" + filename;
	w_n.document.forms[0].elements["width"].value = width;
	w_n.document.forms[0].elements["height"].value = height;
	w_n.focus();
	mediapopup.close();
}
function myCustomFileBrowser(field_name, url, type, win) {
	// Do custom browser logic
	mediapopup = window.open("../../../../media.php","name", "status=yes,toolbar=no,scrollbars=yes,resizable=yes,width=500,height=450,top=0,left=0");
	f_n=field_name;
	w_n=win;
	//win.document.forms[0].elements[field_name].value = fn;
}
tinyMCE.init({ 
mode : "textareas", theme : "advanced", convert_urls : false, relative_urls : false, document_base_url : "'. $CONF['IndexURL'] .'",  
plugins : "paste,emotions';
		if ($this->getMemberOption($this->memberid, 'use_advanced')=='yes'){
			$editorCode .=',table,advhr';
		} 
$editorCode .='",
theme_advanced_buttons1 : "bold,italic,underline,strikethrough,forecolor, backcolor,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,bullist,numlist,separator,outdent,indent,separator,undo,redo,separator,code,separator,link,unlink,image, separator", theme_advanced_buttons1_add : "emotions,charmap,separator,pastetext,pasteword",';
		if ($this->getMemberOption($this->memberid, 'use_advanced')=='yes'){
			$editorCode .='
theme_advanced_buttons2 : "fontselect,fontsizeselect,separator,sub,sup,separator,tablecontrols,separator,visualaid,separator,advhr",
theme_advanced_buttons3 : "",';
		} 
		else {$editorCode .='
theme_advanced_buttons2 : "",
theme_advanced_buttons3 : "",';
		}
$editorCode .='
theme_advanced_toolbar_location : "top", theme_advanced_toolbar_align : "left", theme_advanced_path_location : "bottom", theme_advanced_resizing : true,theme_advanced_resize_horizontal : false,
extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],span[class|align|style],';
$editorCode .= $this->getOption('extended_elements');
$editorCode .='",
paste_create_paragraphs : true,
paste_use_dialog : true,
paste_auto_cleanup_on_paste : true,
cleanup : true,
file_browser_callback : "myCustomFileBrowser"';
			
			$editorCode .='}); </script>';

			$extrahead .= $editorCode;
		} else {
			// enable nucleus toolbar if wysiwyg editor isn't used
			$CONF['DisableJsTools'] = 2;
		}
	}
	
	/**
	 * Nucleus sends its admin area pages as application/xhtml+xml to browsers that can handle this.
	 *
	 * Unfortunately, this causes javascripts that alter page contents through non-DOM methods
	 * to stop working correctly. As the jscalendar and htmlarea both need this, we're forcing
	 * the content-type to text/html for add/edit item forms.
	 */
	function event_PreSendContentType(&$data)
	{
		$pageType = $data['pageType'];
		if ($pageType == 'skin')
			return;
		if (	($pageType != 'bookmarklet-add')
			&&	($pageType != 'bookmarklet-edit')
			&&	($pageType != 'admin-createitem')
			&& 	($pageType != 'admin-itemedit')
			)
			return;
		
		if ($data['contentType'] == 'application/xhtml+xml')
			$data['contentType'] = 'text/html';
	}

}
?>
