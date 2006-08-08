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
**/
?>
<?if ($errMsg !== true){ ?><p style="color:red"><?php echo $errMsg; ?></p><?php } ?>
<form method='post' action=''>
		<input type='hidden' name='action' value='saveforum' />
	<table>
	<tbody>
		<tr>
			<td><?php echo MF_SHORT_NAME." ".MF_SHORT_NAME_CHARS?>:</td>
			<td><input type='text' name='short_name' size='20' maxlength='20' value='<?php echo $forumName?>'/></td>
		</tr>
		<tr>
			<td><?php echo MF_TITLE?></td>	
			<td><input type='text' name='title' size='25' maxlengh='50' value='<?php echo $forumTitle?>' /></td>	
		</tr>
		<tr>
			<td><?php echo MF_DESCRIPTION ?></td>
			<td><textarea name='desc' rows='5' cols='25'><?php 
				echo $forumDesc;
			?></textarea></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><input type='submit' value='<?php echo MF_SAVE_FORUM_BUTTON ?>' /></td>
		</tr>
	</tbody>
	</table>
</form>       

