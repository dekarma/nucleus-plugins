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
 *  Embed the PHP Image Manager from Wei Zhuo into Nucleus CMS
 *  Used version: ImageManagerStandAlone_08_04_2004.zip
 *  from the project "ImageManager + Editor for HTMLArea"
 *  (http://sourceforge.net/projects/imgmngedt/) 
 *
 *  version 0.1:    was another development trunk that
 *                  was only available in the nucleus forum
 *                  (http://forum.nucleuscms.org/viewtopic.php?t=5160)
 *
 *  version 0.9:    2006-07-11
 *                  from kg (Kai Greve - http://kgblog.de)
 *                  is based on NP_TinyMCE2 v1.1
 *
 */

class NP_ImageManager extends NucleusPlugin {

	var $memberid, $memberadmin;

	function NP_ImageManager() {
		// $this->baseUrl = $this->getAdminURL();
		// hardcoded relative path to avoid domain security issues (IE6 'Access is denied' error)
		global $CONF;
		$this->baseUrl = str_replace ($CONF['AdminURL'],'',$this->getAdminURL());
	}

	function getName()      { return 'ImageManager'; }
	function getAuthor()    { return 'karma | roel | eph | kg'; }
	function getURL()       { return 'http://plugins.nucleuscms.org/'; }
	function getVersion()   { return '0.9'; }
	function getMinNucleusVersion() { return 300; }
	function getDescription() {
		return 'Embed the PHP Image Manager from Wei Zhuo into Nucleus CMS';
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
		// create member option
		$this->createMemberOption('use_imagemanager', 'Use Image Manager', 'yesno', 'yes');

		// create plugin option
		$this->createOption('use_nucleustags', 'Use Nucleus Tags', 'yesno', 'yes');
	}

	function unInstall() {
	}

	/**
	 * List of events we want to subscribe to
	 */
	function getEventList() {
		return array(
			'AdminPrePageHead',             // include javascript on admin add/edit pages
			'BookmarkletExtraHead',         // include javascript on bookmarklet pages
			'PreSendContentType',           // we need to force text/html instead of application/xhtml+xml
		);
	}

	/**
	 * Hook into the <head> section of bookmarklet edit pages.
	 * Insert extra script/css includes there.
	 */
	function event_BookmarkletExtraHead(&$data) {
		global $member;
		$this->memberid=$member->id;
		$this->memberadmin=$member->admin;
		$this->_getExtraHead($data['extrahead']);
	}

	/**
	 * Hook into the <head> section of admin area pages. When the requested page is an "add item" or
	 * "edit item" form, get the memberid and include the extra code.
	 */
	function event_AdminPrePageHead(&$data)
	{
		global $member;
		$action = $data['action'];
		if (($action != 'createitem') && ($action != 'itemedit'))
			return;
		
		$this->memberid=$member->id;
		$this->memberadmin=$member->admin;
		$this->_getExtraHead($data['extrahead']);
	}


	function _useTinyMCE (){

		// check if NP_TinyMCE2 is installed
		$sql = "SELECT pid as result FROM ".sql_table('plugin')." WHERE pfile='NP_TinyMCE2'";

		if (quickQuery($sql)==NULL) {
			$useTinyMCE=false;
		}
		else {
			// find plugin oid for the member option
			$sql = "SELECT oid as result FROM ".sql_table('plugin_option_desc')." WHERE oname='use_tinymce' AND ocontext='member'";
			$oid=quickQuery($sql);
			// check if member option is enabled
			$sql = "SELECT ovalue as result FROM ".sql_table('plugin_option')." WHERE oid='".$oid."' AND ocontextid='".$this->memberid."'";
			$res=quickQuery($sql);
			if ($res=='no'){
				$useTinyMCE=false;
			}
			else {
				$useTinyMCE=true;
			}
		}
		
		return $useTinyMCE;
	}

	/**
	 * Returns the extra code that needs to be inserted in the <head>...</head> section of pages that
	 * use PHP Image Manager
	 */
	function _getExtraHead(&$extrahead)
	{
		global $CONF, $manager, $itemid, $blog;
		// get information if TinyMCE is used
		$useTinyMCE=$this->_useTinyMCE();

		// get the options for the current blog
		$mUseEditor = ($this->getMemberOption($this->memberid, 'use_imagemanager') == 'yes');

		// add code for html editor
		if ($mUseEditor) {

			$editorCode .= '<script type="text/javascript" src="'.$this->baseUrl.'assets/dialog.js"></script>
<script type="text/javascript" src="'.$this->baseUrl.'IMEStandalone.js"></script>
<script type="text/javascript">

//Create a new Imanager Manager, needs the directory where the manager is
//and which language translation to use.';

			if ($useTinyMCE){
				$editorCode .= '
// Call ImageManager from TinyMCE
var manager = new ImageManager("../../../../plugins/imagemanager","en");';
			}
			else {
			$editorCode .= '
var manager = new ImageManager("plugins/imagemanager","en");';
			}

			$editorCode .= '
//Image Manager wrapper. Simply calls the ImageManager
ImageSelector =
{
	//This is called when the user has selected a file
	//and clicked OK, see popManager in IMEStandalone to
	//see the parameters returned.
	update : function(params)
	{';

			if (!$useTinyMCE){
				if ($this->getOption('use_nucleustags')=='yes'){
					$editorCode .= '
params.f_file = params.f_file.slice (1);
imgcode="<%image("+params.f_file+"|"+params.f_width+"|"+params.f_height+"|"+params.f_alt+")%>";
insertAtCaret(imgcode);
updAllPreviews();';
				}
				else {
					// evaluate member media dir
					$memberdir=$this->memberid.'/';
					if ($this->memberadmin){
						$memberdir='';
					}
				$editorCode .= '
params.f_file = params.f_file.slice (1);
imgcode="<img src=\"'.$CONF['MediaURL'].$memberdir.'"+params.f_file+"\" width=\""+params.f_width+"\" height=\""+params.f_height+"\" alt=\""+params.f_alt+"\" />";
insertAtCaret(imgcode);
updAllPreviews();';
				}
			}
			else {
				$editorCode .= '
// Code for NP_TinyMCE2
params.f_file = params.f_file.slice (1);
w_n.document.forms[0].elements[f_n].value = "';
				// evaluate member media dir
				$memberdir=$this->memberid.'/';
				if ($this->memberadmin){
					$memberdir='';
				}
			$editorCode .= $CONF['MediaURL'].$memberdir.'" + params.f_file;
w_n.document.forms[0].elements["width"].value = params.f_width;
w_n.document.forms[0].elements["height"].value = params.f_height;';
			}

		$editorCode .= '
},
//open the Image Manager, updates the textfield
//value when user has selected a file.
	select: function(textfieldID)
	{
		this.field = document.getElementById(textfieldID);
		manager.popManager(this);
	}
};

function addMedia() {
ImageSelector.select();
}';
		if ($useTinyMCE){
		$editorCode .= '
function myCustomFileBrowser(field_name, url, type, win) {
	f_n=field_name;
	w_n=win;
	ImageSelector.select();
}';
		}

		$editorCode .= '</script>';

		$extrahead .= $editorCode;
	}
}

	/**
	 * Nucleus sends its admin area pages as application/xhtml+xml to browsers that can handle this.
	 *
	 * Unfortunately, this causes javascripts that alter page contents through non-DOM methods
	 * to stop working correctly. Because many javascript applications need this, we're forcing
	 * the content-type to text/html for add/edit item forms.
	 */
	function event_PreSendContentType(&$data)
	{
		$pageType = $data['pageType'];
		if ($pageType == 'skin')
			return;
		if (    ($pageType != 'bookmarklet-add')
			&&  ($pageType != 'bookmarklet-edit')
			&&  ($pageType != 'admin-createitem')
			&&  ($pageType != 'admin-itemedit')
		)
			return;

		if ($data['contentType'] == 'application/xhtml+xml')
			$data['contentType'] = 'text/html';
	}

}
?>
