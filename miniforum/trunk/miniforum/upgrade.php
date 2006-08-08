<?php
/* 
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
  *  
  * This file contains object MFUpgrader, which handles plugin upgrading. And main 
  * upgrade code. It really SHOULD be run after copying new version of plugin 
  * over the old.
*/

include_once("template.php");

/**
* This class allows upgrading from various versions of NP_MiniForum. It keeps
* content of forums, plugin templates and plugin options. 
* @author Josef Adamcik <josef.adamcik@pepiino.info>
*/
class MFUpgrader {
	var $oPluginAdmin;

	function MFUpgrader() {
		$this->oPluginAdmin = new PluginAdmin('MiniForum');	
	}
	
	/**
	* The plugin stores information about it's version in hidden option 
	* since 0.5.0 . This method uses it to find version number of old plugin.
	*/
	function getOldVersion() {
	}

	/**
	* This method does the upgrade.
    */	
	function upgrade($from) {
				
		switch ($from) {
			case '020':
				$this->to03();
			case '030':
			case '031':
				$this->to04();
			case '040':
			case '041':
			case '050':
				$this->to06();
				$note = MF_UPGRADE_NOTE;
			case '060':
				$this->to065();
				echo "<strong>".MF_UPGRADED."</strong><br />";
				echo "<p>".MF_CURRENT_VERSION." ".$this->oPluginAdmin->plugin->getVersion()."</p>";
				/*echo "<p>This version uses new system for templates ".
					 "(See <a href='http://wakka.xiffy.nl/miniforum'>documentation</a>). ".
					 "You can find them in the plugin options.".
					 "Your template was copied to the default template. You have".
					 " to add fallowing line into form part of the template, if ".
					 "you want to use 'remeber me' feature. ".
					 "\"&lt;label&gt;remember me&lt;input class='formfield' type='checkbox' name='remember'/&gt;&lt;/label&gt;\"".
					 "For details, see the documentation.";*/
				echo $note; 
					 
					break;
			default:
				echo "<p class='error'>Invalid version</p>";
				break;				
		}
			
				
		
	}
	
	/**
	* Upgrades forum from version 0.2.x to 0.3.x 
	*/
	function to03() {
		$this->oPluginAdmin->plugin->createOption('MFConvertNl',MF_COVERT_NL,'yesno','yes');
		$this->oPluginAdmin->plugin->createOption("MFMemberName",MF_MEMBER_NAME,"textarea","<a class='member' href='<%user-link%>'><%user-name%></a>");
		$this->oPluginAdmin->plugin->createOption('MFMaxLineLength',MF_MAX_LINE_LENGTH,'text','50');
	} 

	/**
	* Upgrades forum from version 0.3.x to 0.4.x 
	*/
	function to04() {
		$this->oPluginAdmin->plugin->createOption('MFUrlsToLinks',MF_COVERT_URLS,'yesno','no');
		$this->oPluginAdmin->plugin->createOption('MFEmoToImg',MF_COVERT_EMOTICONS,'yesno','no');
		$this->oPluginAdmin->plugin->createOption('MFEmoDir',MF_EMOTICONS_DIR, 'text','admin/plugins/fancytext/smiles');
	}
	
	/**
	* Upgrades forum from version 0.4.x and 0.5.x to 0.6.x 
	*/
	function to06() {
		//create new table for templates
		$this->oPluginAdmin->plugin->createTableTemplates();
		//copy template from options to database
		$templ = new PluginTemplate();
		$templ->fillWithDefaultValues();
		
		$templ->newIdt = 	  "default";
		$templ->description = "default template";
		$templ->postsHeader = $this->oPluginAdmin->plugin->getOption('MFPostsHeader');
		$templ->postBody =    $this->oPluginAdmin->plugin->getOption('MFPostBody');
		$templ->postsFooter = $this->oPluginAdmin->plugin->getOption('MFPostsFooter');
		$templ->formLogged =  $this->oPluginAdmin->plugin->getOption('MFFormLogged');
		//$templ->form =        $this->oPluginAdmin->plugin->getOption('MFForm');
		$templ->navigation =  $this->oPluginAdmin->plugin->getOption('MFNavigation');
		$templ->name =        $this->oPluginAdmin->plugin->getOption('MFName');
		$templ->nameLin =     $this->oPluginAdmin->plugin->getOption('MFNameLink');
		$templ->memberName =  $this->oPluginAdmin->plugin->getOption('MFMemberName');
		$templ->date =        $this->oPluginAdmin->plugin->getOption('MFDate');
		$templ->time =        $this->oPluginAdmin->plugin->getOption('MFTime');
		$templ->nextPage =    $this->oPluginAdmin->plugin->getOption('MFNextPage');
		$templ->previousPage =$this->oPluginAdmin->plugin->getOption('MFPreviousPage');
		$templ->firstPage =   $this->oPluginAdmin->plugin->getOption('MFFirstPage');
		$templ->lastPage =    $this->oPluginAdmin->plugin->getOption('MFLastPage');
		$templ->urlToLink =   ($this->oPluginAdmin->plugin->getOption('MFUrlsToLinks') == 'yes') ? true : false;
		$templ->emoToImg  =   ($this->oPluginAdmin->plugin->getOption('MFEmoToImg') == 'yes') ? true : false ;
		
		
		$templ->saveNew();
		
		//delete old options
	    $this->oPluginAdmin->plugin->deleteOption("MFPostsHeader");
	    $this->oPluginAdmin->plugin->deleteOption("MFPostBody");
	    $this->oPluginAdmin->plugin->deleteOption("MFPostsFooter");
	    $this->oPluginAdmin->plugin->deleteOption("MFName");
	    $this->oPluginAdmin->plugin->deleteOption("MFNameLink");
	    $this->oPluginAdmin->plugin->deleteOption("MFMemberName");
	    $this->oPluginAdmin->plugin->deleteOption("MFDate");
	    $this->oPluginAdmin->plugin->deleteOption("MFTime");
	    $this->oPluginAdmin->plugin->deleteOption("MFFormLogged");
	    $this->oPluginAdmin->plugin->deleteOption("MFForm");
	    $this->oPluginAdmin->plugin->deleteOption("MFNextPage");
	    $this->oPluginAdmin->plugin->deleteOption("MFPreviousPage");
	    $this->oPluginAdmin->plugin->deleteOption("MFFirstPage");
	    $this->oPluginAdmin->plugin->deleteOption("MFLastPage");
	    $this->oPluginAdmin->plugin->deleteOption("MFNavigation");
		$this->oPluginAdmin->plugin->deleteOption("MFUrlsToLinks");
		$this->oPluginAdmin->plugin->deleteOption("MFEmoToImg");
		
		
		//create new option
		$this->oPluginAdmin->plugin->createOption('MFRefresh',MF_REFRESH,'text','30');
	}

	/**
	* Upgrades from the 0.6.0(1,2,3,4) to 0.6.5
	*/
	function to065() {
		$this->oPluginAdmin->plugin->createOption('MFCaptcha',MF_CAPTCHA,'yesno','no');
	}
	
	
	function to070() {
		$this->oPluginAdmin->plugin->deleteOption("MFUrlsToLinks");
		$this->oPluginAdmin->plugin->deleteOption("MFEmoToImg");
		$this->oPluginAdmin->plugin->createOption('MFNofollow',MF_NOFOLLOW,'yesno','no');
	}
	
	/**
	* Shows list of possible upgrades.
	*/
	function showListOfUpgrades() {
		$versions = array(	'0.2.x' => '020',
							'0.3.x' => '030',
							'0.4.x' => '040',
							'0.5.x' => '050',
							'0.6.x' => '060');
		echo "<select name='from'>";
		foreach ($versions as $k => $v) {
			echo "<option value='$v'>$k</option>";
		}
		echo "</select>";
	}
	
}


/*******************************************************************
* upgrade page code.
********************************************************************/
$strRel = '../../../';

if (file_exists($strRel.'cfg.php')) {
        include($strRel.'cfg.php'); //blogcms config
} else {
        include($strRel.'config.php'); //nucleus CMS config
}

global $DIR_PLUGINS;
if (!is_dir($DIR_PLUGINS)) die('System is not configured properly - NP_MiniForum.php');

require_once($DIR_PLUGINS.'miniforum/lang.php');


if ((!$member->isLoggedIn()) || (!$member->isAdmin()))
    doError(MF_NOT_LOGGED_IN_UPGRADE);

include($DIR_LIBS.'PLUGINADMIN.php');

$pluginpath =$CONF['PluginURL']."miniforum";

// create the admin area page
$oPluginAdmin = new PluginAdmin('MiniForum');
$oPluginAdmin->start();



//upgrade form
if (requestVar('from') != "") {
	$u = new MFUpgrader();
	$u->upgrade(requestVar('from'));
} else {
	echo "<h2>".MF_UPGRADE_HEADING."</h2>".
	MF_CHOOSE_VERSION.
	"<form action='$pluginpath/upgrade.php' method='post'>";
	
	MFUpgrader::showListOfUpgrades();
	
	echo "<br /><input type='submit' value='".MF_UPGRADE_BUTTON."' />
	</form>";
}
$oPluginAdmin->end();

?>
