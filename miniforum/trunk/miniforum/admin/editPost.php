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
?>

<?php if (!empty($errMsg)) {?>
<p style="color:red"><?echo $errMsg ?></p>
<?php } ?>


<h2><?php echo MF_EDIT_POST?></h2>

<form method='post' action='<?php echo $pluginpath?>/index.php'>
	<table>
	<tbody>
	<tr>
		<td><?php echo MF_USER_NAME?></td>
		<td>
			<input type='text' name='uname' value='<?php echo $post['uname']?>' size='40' maxlength='20' />
		</td>
	</tr>
	<tr>
		<td><?php echo MF_USER_URL?></td>
		<td>
			<input type='text' name='ulink' value='<?php echo $post['url']?>' size='40' maxlength='30' />
		</td>
	</tr>
	<tr><td><?php echo MF_POST_BODY?></td>
		<td><textarea name='postbody' rows='4' cols='50'><?php 
			echo $post['body']
		?></textarea>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type='submit' value='<?php echo MF_CHANGE_POST_BUTTON?>' /></td>
	</tr>
	</tbody>
	</table>
	
	<input type='hidden' name='action' value='savepost' />
	<input type='hidden' name='change' value='true' />
	<input type='hidden' name='postid' value='<?php echo $post['id']?>' />
	<input type='hidden' name='forumid' value='<?php echo $post['idforum']?>' />
	<input type='hidden' name='page' value='<?php echo $page?>' />
</form>

<p><a href="<?php echo $pluginpath ?>/index.php?show=postadmin"><?php echo MF_BACK_TO_POSTADMIN?></a></p>
