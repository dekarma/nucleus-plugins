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
* This class encapsulates the template for plugin NP_MiniForum.     
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
	* This function reads selected template from database
	* Returns false, if the template name doesn't exist. 
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
	* used when template is sent from form
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
		$this->gravSize = 	 requestVar('gravSize');
		$this->gravDefault = requestVar('gravDefault');;


		
		$this->action = requestVar('action');
	}
  
  /**
  * this function escapes all template atributes before sending to db
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
  }
  
  /**
  * Checks if the template data are ok.
  */
  function checkData() {
	  //check template name
	  if (!ereg('^[0-9a-zA-Z_\-]+$',$this->newIdt)) {
		  $this->error = MF_WRONG_SHORT_NAME;
		  return false;
	  } else 
	  	  return $this->isNameFree();
  }
  
  //returns true when the chosen name of template isn't used yet.
  function isNameFree() {
	  if ($this->idt == $this->newIdt) {
		 //in this case, user is changing old template but not the name
		 return true;
     } 
	 
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
	
	
	
  	//print form
  	echo "<h3>".$title."</h3>";
	if ($this->error != '') echo $this->error;
  	echo "<form method='post' action='$pluginpath/index.php'>
  		  <input type='hidden' name='idt' value='{$this->idt}' /> 
  		  <input type='hidden' name='action' value='{$this->action}' />
  		  <table>
  		  <tr>
  			<td>".MF_TEMPLATE_NAME." ".MF_SHORT_NAME_CHARS.":</td>
  			<td><input type='text' name='template' size='20' maxlength='20' 
			value='{$this->newIdt}'/></td>
  		  </tr>
  		  <tr>
  			<td>".MF_DESCRIPTION."</td>
  			<td><input type='text' name='description' size='65' maxlength='40' 
  				value='{$this->description}' /></td>
  		  </tr>
  		  <tr>
  			<td>".MF_POST_LIST_HEADER."</td>
  			<td><textarea name='postListHeader' rows='5' columns='40'
  				>{$this->postsHeader}</textarea></td>
  		  </tr>
  		  <tr>
  			<td>".MF_POST_BODY."</td>
  			<td><textarea name='postBody' rows='5' columns='40'
  				>{$this->postBody}</textarea></td>
  		  </tr>
  		  <tr>
  			<td>".MF_POST_LIST_FOOT."</td>
  			<td><textarea name='postListFooter' rows='5' columns='40'
  				>{$this->postsFooter}</textarea></td>
  		  </tr>
  		  <tr>
  			<td>".MF_FORM_LOGGED."</td>
  			<td><textarea name='formLogged' rows='5' columns='40'
  				>{$this->formLogged}</textarea></td>
  		  </tr>
  		  <tr>
  			<td>".MF_FORM_NOTLOGGED."</td>
  			<td><textarea name='formNotLogged' rows='10' columns='40'
  				>{$this->form}</textarea></td>
  		  </tr>
  		  <tr>
  			<td>".MF_NAVIGATION."</td>
  			<td><textarea name='navigation' rows='5' columns='40'
  				>{$this->navigation}</textarea></td>
  		  </tr>
  		  <tr>
  			<td>".MF_NAME_NOURL."</td>
  			<td><textarea name='nameNoUrl' rows='5' columns='40'
  				>{$this->name}</textarea></td>
  		  </tr>
  		  <tr>
  			<td>".MF_NAME_URL."</td>
  			<td><textarea name='nameUrl' rows='5' columns='40'
  				>{$this->nameLin}</textarea></td>
  		  </tr>
  
  		  <tr>
  			<td>".MF_MEMBER_NAME."</td>
  			<td><textarea name='memberName' rows='5' columns='40'
  				>{$this->memberName}</textarea></td>
  		  </tr>
  		  <tr>
  			<td>".MF_DATE."</td>
  			<td><input type='text' name='date' size='20' maxlength='20' 
  				value='{$this->date}' /></td>
  		  </tr>
  		  <tr>
  			<td>".MF_TIME."</td>
  			<td><input type='text' name='time' size='20' maxlength='20' 
  				value='{$this->time}' /></td>
  		  </tr>
  		  <tr>
  			<td>".MF_NEXTPAGE."</td>
  			<td><input type='text' name='nextPage' size='20' maxlength='20' 
  				value='{$this->nextPage}' /></td>
  		  </tr>
  		  <tr>
  			<td>".MF_PREVIOUSPAGE."</td>
  			<td><input type='text' name='previousPage' size='20' maxlength='20' 
  				value='{$this->previousPage}' /></td>
  		  </tr>
  		  <tr>
  			<td>".MF_FIRSTPAGE."</td>
  			<td><input type='text' name='firstPage' size='20' maxlength='20' 
  				value='{$this->firstPage}' /></td>
  		  </tr>
  		  <tr>
  			<td>".MF_LASTPAGE."</td>
  			<td><input type='text' name='lastPage' size='20' maxlength='20' 
  				value='{$this->lastPage}' /></td>
  		  </tr>
		  <tr>
		  	<td>".MF_COVERT_URLS."</td>
			<td>".MF_YES." <input type='radio' name='urlToLink' value='yes' ".($this->urlToLink ? 'checked ' :'')."/>
			".MF_NO." <input type='radio' name='urlToLink' value='no' ".(!$this->urlToLink ? 'checked ' :'')."/></td>
		  </tr>
		  <tr>
		  	<td>".MF_COVERT_EMOTICONS."</td>
			<td>".MF_YES." <input type='radio' name='emoToImg' value='yes' ".($this->emoToImg ? 'checked ' :'')."/>
			".MF_NO." <input type='radio' name='emoToImg' value='no' ".(!$this->emoToImg ? 'checked ' :'')."/></td>
		  </tr>
		  <tr>
		  	<td>".MF_GRAV_DEFAULT."</td>
			<td><input type='text' name='gravDefault' size='60' maxlength='60' value='{$this->gravDefault}' /></td>
		  </tr>
  		  <tr>
		  	<td>".MF_GRAV_SIZE."</td>
			<td><input type='text' name='gravSize' size='10' maxlength='10'value='{$this->gravSize}'/></td>
		  </tr>

  		  
  		  </table>
  		  <input type='submit' value='$btnText' /></form>";
  	
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
	* TODO: remove duplicity with prevoius method
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
					`EmoToImg` 		=	'".($this->empToImg ? 'yes' : 'no')."',
					`GravDefault`	=	'{$this->gravDefault}',
					`GravSize`		=	'{$this->gravSize}'";
		return $q;

	}
	
	/**
	* This function fills the template with default values for all fields
	*/
	function fillWithDefaultValues() {
		$this->newIdt = "default";      

		$this->description = "default template";
		$this->postsHeader = "<h2><%title%></h2><p><%description%></p><%navigation%><ul class=\"miniforum\">"; 
		$this->postBody =    "<li><%name%> [ <%date%> | <%time%> ]<br /><%body%></li>";
		$this->postsFooter = "</ul><br /><%navigation%>";
		$this->formLogged =  "<textarea class='formfield' name='BODY' rows='3' cols='20'></textarea><br /><input type='submit' value='Send' />";
		$this->form =        "<label>name<input class='formfield' type='text' name='uname' value='<%name%>'/></label><br />".
          					 "<label>url<input class='formfield' type='text' name='url' value='<%url%>'/></label><br />".
							 "<label>remember me<input class='formfield' type='checkbox' name='remember'/></label><br />".
							 "<textarea class='formfield' name='BODY' rows='3' cols='20'></textarea><br />".
							 "<input class='formbutton' type='submit' value='Send' />";
		$this->navigation =  "<div class=\"forum\">[<%first-page%>][<%prev-page%>] (Page: <%cur-page%> from <%page-count%>) [<%next-page%>][<%last-page%>]</div>";
		$this->name =        "<strong><%user-name%></strong>";
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
	
		
}//end of class PluginTemplate

?>
