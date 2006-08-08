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
  *  This file contains code for template class of plugin NP_MiniForum
*/


/**
* This class encapsulates the template for plugin NP_MiniForum.
* @author Josef Adamcik <josef.adamcik@pepiino.info>
* @see http://wakka.xiffy.nl/miniforum
*/
class PluginTemplate {
	var $idt;
	var $newIdt;
	
	//template fields
	var $description;
	var $postsHeader;
	var $postBody;
	var $postsFooter;
	var $formLogged;
	var $form;
	var $navigation;
	var $name;
	var $nameLin;
	var $memberName;
	var $date;
	var $time;
	var $nextPage;
	var $previousPage;
	var $firstPage;
	var $lastPage;
	//template settings
	var $urlToLink;
	var $empToImg;
	var $gravDefault; //default gravatar image 
	var $gravSize; //size of gravatar image
	
	var $action;
	var $error; //contains error message 
  
	
	
	/**
	* Fills header template witch given values.
	* @param string $title - forum title 
	* @param string $description - forum description
	* @param string $navigation - prepared html code of navigation
	* @return string - parsed header (html code)
	*/
	function parseHeader($title,$description,$navigation) {
		    $tags = array('<%title%>','<%description%>','<%navigation%>');
			$values = array($title,$description,$navigation);
			return $this->_parse($tags,$values,$this->postsHeader);
	}
	
	/**
	* Fills footer template witch given values.
	* @param string $title - forum title 
	* @param string $description - forum description
	* @param string $navigation - prepared html code of navigation
	* @return string - parsed footer (html code)
	*/
	function parseFooter($title,$description,$navigation) {
		    $tags = array('<%title%>','<%description%>','<%navigation%>');
			$values = array($title,$description,$navigation);
			return $this->_parse($tags,$values,$this->postsFooter);
	}
	
	
	/**
	* Parses the post template.
	* @param string $uname 		- user name 
	* @param string $ulink		- user link (email or www)
	* @param int 	$datetime 	- time (as unix timestamp)
	* @param string $gravUrl 	- url of gravatar image 
	* @param string $body		- Body of the post (content)
	* @param string $nametlp	- Template which will be used as name template 
	*								(member/nonmeber/withoutlink)
	*								TODO: remove this parameter by refactoring NP_MiniForum::showPosts
	* @return string - parsed post template (html code)
	* 
	*/
	function parsePost($uname,$ulink,$umail,$datetime,$gravUrl,$body,$nametmpl) {
		//first parse the name template
        $tags = array("<%user-name%>","<%user-link%>",'<%user-email%>');    
        $values = array($uname,$ulink,$umail);
		$nametmpl = $this->_parse($tags,$values,$nametmpl);
		
		//now parse the postbody template
        $tags = array("<%name%>","<%date%>","<%time%>","<%gravatar%>","<%body%>");
        $values = 	array(	$nametmpl,
						date($this->date,$datetime),
						date($this->time,$datetime),
						$gravUrl,
						$body);
						
		return $this->_parse($tags,$values,$this->postBody);						
	}
	
	/**
	* This function reads selected template from database
	* Returns false, if the template name doesn't exist.
	* @param 	string 	$templname - name of the template to read from the database
	* @return 	boolean	- false - template doesn't exist, true - ok
	*/
	function readFromDb($templname) {
		$query = 	"SELECT * FROM `".sql_table('plug_miniforum_templates')."` ".
					"WHERE template='$templname'";
					
		$result = 	   sql_query($query);
		
		if (!($template = sql_fetch_array($result))) return false;
		
		$this->idt =         $template['template'];
		$this->newIdt =		 $this->idt;
		$this->description = $template['description'];
		$this->postsHeader = $template['PostsHeader'];
		$this->postBody =    $template['PostBody'];
		$this->postsFooter = $template['PostsFooter'];
		$this->formLogged =  $template['FormLogged'];
		$this->form =        $template['Form'];
		$this->navigation =  $template['Navigation'];
		$this->name =        $template['Name'];
		$this->nameLin =     $template['NameLin'];
		$this->memberName =  $template['MemberName'];
		$this->date =        $template['Date'];
		$this->time =        $template['Time']; 
		$this->nextPage =    $template['NextPage'];
		$this->previousPage =$template['PreviousPage'];
		$this->firstPage =   $template['FirstPage'];
		$this->lastPage =    $template['LastPage'];
		
		$this->urlToLink =   ($template['UrlsToLinks'] == 'yes')? true : false;
		$this->emoToImg = 	 ($template['EmoToImg'] == 'yes') ? true : false;
		$this->gravSize = 	 $template['GravSize'];
		$this->gravDefault = $template['GravDefault'];
		
		return true;
	}  
  
	/**
	* Used when template is sent from form. It takes data from request and fills this
	* instance of PluginTemplate.
	*/
	function readFromPost() {
		$this->idt =         requestVar('idt');
		$this->newIdt =      requestVar('template');
		$this->description = requestVar('description');
		$this->postsHeader = requestVar('postListHeader');
		$this->postBody =    requestVar('postBody');
		$this->postsFooter = requestVar('postListFooter');
		$this->formLogged =  requestVar('formLogged');
		$this->form =        requestVar('formNotLogged');
		$this->navigation =  requestVar('navigation');
		$this->name =        requestVar('nameNoUrl');
		$this->nameLin =     requestVar('nameUrl');
		$this->memberName =  requestVar('memberName');
		$this->date =        requestVar('date');
		$this->time =        requestVar('time'); 
		$this->nextPage =    requestVar('nextPage');
		$this->previousPage =requestVar('previousPage');
		$this->firstPage =   requestVar('firstPage');
		$this->lastPage =    requestVar('lastPage');	

		$this->urlToLink =   (requestVar('urlToLink') == 'yes') ? true : false;
		$this->emoToImg = 	 (requestVar('emoToImg') == 'yes') ?true : false;
		$this->gravSize = 	 (int)requestVar('gravSize');
		$this->gravDefault = requestVar('gravDefault');;


		
		$this->action = requestVar('action');
	}
  
  /**
  * This function escapes all template atributes before sending to db.
  *
  */ 
  function prepareForDb() {
    $this->description = sql_escape($this->description);
    $this->postsHeader = sql_escape($this->postsHeader);
    $this->postBody =    sql_escape($this->postBody);
    $this->postsFooter = sql_escape($this->postsFooter);
    $this->formLogged =  sql_escape($this->formLogged);
    $this->form =        sql_escape($this->form);
    $this->navigation =  sql_escape($this->navigation);
    $this->name =        sql_escape($this->name);
    $this->nameLin =     sql_escape($this->nameLin);
    $this->memberName =  sql_escape($this->memberName);
    $this->date =        sql_escape($this->date);
    $this->time =        sql_escape($this->time); 
    $this->nextPage =    sql_escape($this->nextPage);
    $this->previousPage =sql_escape($this->previousPage);
    $this->firstPage =   sql_escape($this->firstPage);
    $this->lastPage =    sql_escape($this->lastPage);
	$this->gravDefault = sql_escape($this->gravDefault);
  } 
  
  /**
  * Runs htmlspecialchars() on all template fields.
  */
  function doHtmlSpecChars() {
    $this->description = htmlspecialchars($this->description);
    $this->postsHeader = htmlspecialchars($this->postsHeader);
    $this->postBody =    htmlspecialchars($this->postBody);
    $this->postsFooter = htmlspecialchars($this->postsFooter);
    $this->formLogged =  htmlspecialchars($this->formLogged);
    $this->form =        htmlspecialchars($this->form);
    $this->navigation =  htmlspecialchars($this->navigation);
    $this->name =        htmlspecialchars($this->name);
    $this->nameLin =     htmlspecialchars($this->nameLin);
    $this->memberName =  htmlspecialchars($this->memberName);
    $this->date =        htmlspecialchars($this->date);
    $this->time =        htmlspecialchars($this->time); 
    $this->nextPage =    htmlspecialchars($this->nextPage);
    $this->previousPage =htmlspecialchars($this->previousPage);
    $this->firstPage =   htmlspecialchars($this->firstPage);
    $this->lastPage =    htmlspecialchars($this->lastPage);
	$this->gravDefault = htmlspecialchars($this->gravDefault);
  }
  
  /**
  * Checks if the template data are ok.
  * 
  */
  function checkData() {
	  //check template name
	  if (!ereg('^[0-9a-zA-Z_\-]+$',$this->newIdt)) {
		  $this->error = MF_WRONG_SHORT_NAME;
		  return false;
	  } else 
	  	  return $this->isNameFree();
  }
  
  /** 
  * @return boolean - true when chosen name of the template isn't used yet.
  */
  function isNameFree() {
	  if ($this->idt == $this->newIdt) {
		 //in this case, user is changing old template but not the name
		 return true;
     } 
	 
	 //try to find the name
	 $query = "SELECT template ".
	  		   "FROM `".sql_table('plug_miniforum_templates')."` ".
			   "WHERE template = '{$this->newIdt}'";
	 $result = sql_query($query);

	 if (sql_num_rows($result) > 0) {
		 $this->error = MF_TEMPLATE_NAME_USED;
		 return false;
	 } else return true;
	 	
  }
  
  /**
  * Shows form for creating/editing template.
  */
  function showForm() {
  	global $pluginpath;
  	
  	if ($this->idt == "") {
  	  //user is creating new template
  		$title = MF_NEW_TEMPLATE;
  		$this->action = 'createtempl';
		$btnText = MF_CREATE_TEMPLATE_BUTTON; 
  	} else {
  	  //user is editing old template
  		$title  = 	MF_CHANGE_TEMPLATE;
  		$this->action = 'changetempl';
		$btnText = MF_CHANGE_FORUM_BUTTON;
  	}
  	
	$this->doHtmlSpecChars();
		
	include "admin/tempForm.php";
  	
  }
  
    /**
    * Saves changed template to db.
    */
    function change() {
		$this->prepareForDb();
        $query =   "UPDATE `".sql_table('plug_miniforum_templates')."` ".
		 			$this->prepareQuery().
					" WHERE `template` = '{$this->idt}'";
					
        sql_query($query);
    }
    
    /**
    * Creates new template in db and saves data into it.
    */
    function saveNew() {
		$this->prepareForDb();        
		$query = "INSERT INTO `".sql_table('plug_miniforum_templates')."` ".
			     $this->prepareQuery();		
					
		sql_query($query);
    }
  

		
	/**
	* Creates main part of sql query, which is the same for both creating and 
	* changing template.
	* @return string - part of SQL query
	* @access private
	*/	
	function prepareQuery() {
		$q =   "SET 
					`template`      =   '{$this->newIdt}',
					`description`   =   '{$this->description}',
					`PostsHeader`   =   '{$this->postsHeader}',
					`PostBody`      =   '{$this->postBody}',
					`PostsFooter`   =   '{$this->postsFooter}',
					`FormLogged`    =   '{$this->formLogged}',
					`Form`          =   '{$this->form}',
					`Navigation`    =   '{$this->navigation}',
					`Name`          =   '{$this->name}',
					`NameLin`       =   '{$this->nameLin}',
					`MemberName`    =   '{$this->memberName}',
					`Date`          =   '{$this->date}',
					`Time`          =   '{$this->time}',
					`NextPage`      =   '{$this->nextPage}',
					`PreviousPage`  =   '{$this->previousPage}',
					`FirstPage`     =   '{$this->firstPage}',
					`LastPage`      =   '{$this->lastPage}',
					`UrlsToLinks`	=	'".($this->urlToLink ? 'yes' : 'no')."',
					`EmoToImg` 		=	'".($this->emoToImg ? 'yes' : 'no')."',
					`GravDefault`	=	'{$this->gravDefault}',
					`GravSize`		=	{$this->gravSize}";
		return $q;

	}
	
	/**
	* This function fills the template with default values for all fields.
	*/
	function fillWithDefaultValues() {
		$this->newIdt = "default";      

		$this->description = "default template";
		$this->postsHeader = "<h2><%title%></h2><p><%description%></p><%navigation%><ul class=\"miniforum\">"; 
		$this->postBody =    "<li><%name%> [ <%date%> | <%time%> ] <br /><%body%></li>";
		$this->postsFooter = "</ul><br /><%navigation%>";
		$this->formLogged =  "<textarea class='formfield' name='BODY' rows='3' cols='20'></textarea><br /><input type='submit' value='Send' />\n";
		$this->form =        "<label>name<input class='formfield' type='text' name='uname' value='<%name%>'/></label><br />\n".
							 "<label>e-mail<input class='formfield' type='text' name='email' value='<%email%>'/></label><br />\n".
          					 "<label>url<input class='formfield' type='text' name='url' value='<%url%>'/></label><br />\n".
							 "<label>remember me<input class='formfield' type='checkbox' name='remember'/></label><br />\n".
							 "<textarea class='formfield' name='BODY' rows='3' cols='20'></textarea><br />\n".
							 "<input class='formbutton' type='submit' value='Send' />";
		$this->navigation =  "<div class=\"forum\">[<%first-page%>][<%prev-page%>] (Page: <%cur-page%> from <%page-count%>) [<%next-page%>][<%last-page%>]</div>";
		$this->name =        "<strong><%user-name%></strong> (<%user-email%>)";
		$this->nameLin =     "<a href='<%user-link%>'><%user-name%></a>";
		$this->memberName =  "<a class='member' href='<%user-link%>'><%user-name%></a>";
		$this->date =        "d. m. y";
		$this->time =        "H:i"; 
		$this->nextPage =    "Next";
		$this->previousPage ="Previous";
		$this->firstPage =   "First";
		$this->lastPage =    "Last";	
		$this->emoToImg = 	 false;
		$this->urlToLink=	 true;
		$this->gravSize =	 40;     
		
	}
	
	/**
	* Goes through text and replaces all tags with values.
	* @param array $tags - array of tags 
	* @param array $values - array of values
	* @param string $text - text to parse
	* @return string - parsed text.
	* @access private
	*/
	function _parse($tags,$values,$text) {
		for ($i = 0; $i<sizeof($tags); $i++) {
				$text = str_replace($tags[$i],$values[$i],$text);
			}
		return $text;
	}	
	
	
	
	/**
	* Creates and saves copy of chosen template. Name of new template will be same as old but 
	* prefixed with copy_
	* @param string $idt - shortname (id) of template to copy
	* @static
	*/
	function copyTemplate($idt) {
		//get list of template names
		$query = 	"SELECT template ".
					"FROM `".sql_table("plug_miniforum_templates")."`";
		$result = sql_query($query);
		$templNames = array();
		while ($name = sql_fetch_array($result)) {
			$templNames[] = $name[0];
		}
		
		//create copy of template
		$templ = new PluginTemplate();
		$templ->readFromDb($idt);
		
		//create new name and handle possible duplicity
		$i = "";
		do {
			$templ->newIdt = "clone".$i."_".$templ->idt;
			$i = ($i == "")? 1 : $i + 1;
		} while (in_array($templ->newIdt,$templNames));
		
		$templ->saveNew();
	}
	
	/**
	* Delete chosen template
	* @param string $idt - shortname (id) of template to delete
	* @static
	*/
	function deleteTemplate($idt) {
		$query = "DELETE FROM `".sql_table('plug_miniforum_templates')."` ".
				 "WHERE template='$idt'";
					
		sql_query($query);
	}
	
	/**
	* Show form for template editing/creating
	* @param string $idt - shortname (id) of template (optional)
	* @static
	*/
	function showTemplateForm($idt = "") {
		$tmpl = new PluginTemplate();
		if ($idt != "")	$tmpl->readFromDb($idt);
		$tmpl->showForm();
	}
	
	/**
	* @return array containing all templates 
	* @static
	*/
	function getTemplateList() {
		$query = "SELECT template,description ".
		 		 "FROM `".sql_table("plug_miniforum_templates")."`";
		$result = sql_query($query);
		
		$templateList = array();
		while ($templ = sql_fetch_array($result)) {
			$templateList[] = $templ;
		}
		
		return $templateList;
	}
	
}//PluginTemplate

?>
