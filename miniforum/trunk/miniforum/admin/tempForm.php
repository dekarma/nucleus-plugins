<h2><?php echo $title?></h2>
<?php if ($this->error != '') ?>
<p style="color:red"> <?php echo$this->error; ?> </p>
<form method='post' action='<?php echo $pluginpath?>/index.php'>
  <input type='hidden' name='idt' value='<?php echo $this->idt?>' /> 
  <input type='hidden' name='action' value='<?php echo $this->action?>' />
  <table>
  <tr>
	<td><?php echo MF_TEMPLATE_NAME." ".MF_SHORT_NAME_CHARS?>:</td>
	<td><input type='text' name='template' size='20' maxlength='20' 
	value='<?php echo $this->newIdt?>'/></td>
  </tr>
  <tr>
	<td><?php echo MF_DESCRIPTION?></td>
	<td><input type='text' name='description' size='65' maxlength='40' 
		value='<?php echo $this->description?>' /></td>
  </tr>
  <tr>
  	<th colspan="2"><?php echo MF_TEMPLATE_PARTS ?></th>
  </tr>
  <tr>
	<td><?php echo MF_POST_LIST_HEADER?></td>
	<td><textarea name='postListHeader' rows='5' columns='40'
		><?php echo $this->postsHeader?></textarea></td>
  </tr>
  <tr>
	<td><?php echo MF_POST_BODY?></td>
	<td><textarea name='postBody' rows='5' columns='40'
		><?php echo $this->postBody?></textarea></td>
  </tr>
  <tr>
	<td><?php echo MF_POST_LIST_FOOT?></td>
	<td><textarea name='postListFooter' rows='5' columns='40'
		><?php echo $this->postsFooter?></textarea></td>
  </tr>
  <tr>
	<td><?php echo MF_FORM_LOGGED?></td>
	<td><textarea name='formLogged' rows='5' columns='40'
		><?php echo $this->formLogged?></textarea></td>
  </tr>
  <tr>
	<td><?php echo MF_FORM_NOTLOGGED?></td>
	<td><textarea name='formNotLogged' rows='10' columns='40'
		><?php echo $this->form?></textarea></td>
  </tr>
  <tr>
	<td><?php echo MF_NAVIGATION?></td>
	<td><textarea name='navigation' rows='5' columns='40'
		><?php echo $this->navigation?></textarea></td>
  </tr>
  <tr>
	<td><?php echo MF_NAME_NOURL?></td>
	<td><textarea name='nameNoUrl' rows='5' columns='40'
		><?php echo $this->name?></textarea></td>
  </tr>
  <tr>
	<td><?php echo MF_NAME_URL?></td>
	<td><textarea name='nameUrl' rows='5' columns='40'
		><?php echo $this->nameLin?></textarea></td>
  </tr>

  <tr>
	<td><?php echo MF_MEMBER_NAME?></td>
	<td><textarea name='memberName' rows='5' columns='40'
		><?php echo $this->memberName?></textarea></td>
  </tr>
  <tr>
	<td><?php echo MF_DATE?></td>
	<td><input type='text' name='date' size='20' maxlength='20' 
		value='<?php echo $this->date?>' /></td>
  </tr>
  <tr>
	<td><?php echo MF_TIME?></td>
	<td><input type='text' name='time' size='20' maxlength='20' 
		value='<?php echo $this->time?>' /></td>
  </tr>
  <tr>
	<td><?php echo MF_NEXTPAGE?></td>
	<td><input type='text' name='nextPage' size='20' maxlength='20' 
		value='<?php echo $this->nextPage?>' /></td>
  </tr>
  <tr>
	<td><?php echo MF_PREVIOUSPAGE?></td>
	<td><input type='text' name='previousPage' size='20' maxlength='20' 
		value='<?php echo $this->previousPage?>' /></td>
  </tr>
  <tr>
	<td><?php echo MF_FIRSTPAGE?></td>
	<td><input type='text' name='firstPage' size='20' maxlength='20' 
		value='<?php echo $this->firstPage?>' /></td>
  </tr>
  <tr>
	<td><?php echo MF_LASTPAGE?></td>
	<td><input type='text' name='lastPage' size='20' maxlength='20' 
		value='<?php echo $this->lastPage?>' /></td>
  </tr>
  <tr>
  	<th colspan="2"><?php echo MF_TEMPLATE_OPTIONS ?></th>
  </tr>
  <tr>
	<td><?php echo MF_COVERT_URLS?></td>
	<td><?php echo MF_YES?> <input type='radio' name='urlToLink' value='yes' <?php echo ($this->urlToLink ? 'checked=\'checked\' ' :'' );?> />
	<?php echo MF_NO?> <input type='radio' name='urlToLink' value='no' <?php echo (!$this->urlToLink ? 'checked=\'checked\' ' :'')?> /></td>
  </tr>
  <tr>
	<td><?php echo MF_COVERT_EMOTICONS?></td>
	<td><?php echo MF_YES?> <input type='radio' name='emoToImg' value='yes' <?php echo ($this->emoToImg ? 'checked=\'checked\' ' :'')?>/>
	<?php echo MF_NO?> <input type='radio' name='emoToImg' value='no' <?php echo (!$this->emoToImg ? 'checked=\'checked\' ' :'')?> /></td>
  </tr>
  <tr>
	<td><?php echo MF_GRAV_DEFAULT?></td>
	<td><input type='text' name='gravDefault' size='60' maxlength='60' value='<?php echo $this->gravDefault?>' /></td>
  </tr>
  <tr>
	<td><?php echo MF_GRAV_SIZE?></td>
	<td><input type='text' name='gravSize' size='10' maxlength='10' value='<?php echo $this->gravSize?>'/></td>
  </tr>

  
  </table>
  <input type='submit' value='<?php echo $btnText?>' />
</form>
