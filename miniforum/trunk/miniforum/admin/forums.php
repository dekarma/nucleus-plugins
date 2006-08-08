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

* NP_MiniForum admina area 
* 	- Forumlist and form for adding new forum.
*/?>



<?php if (requestVar('insert') == 1 && empty($errMsg)) {?>
<p><?echo MF_FORUM_SAVED ?></p>
<?php } ?>

<?php if (requestVar('delete') == 1 && empty($errMsg)) {?>
<p><?echo MF_FORUM_DELETED ?></p>
<?php } ?>


<h2><?php echo MF_FORUM_LIST_HEADING ?></h2>
<table>
<thead>
<tr>
	<th>
	<?php echo MF_FORUM ?>
	</th>
	<th><?php echo MF_TITLE?></th>
	<th><?php echo MF_DESCRIPTION?></th>
	<th><?php echo MF_POSTS?></th>
	<th><?php echo MF_ACTIONS?></th>
</tr>
</thead>
<tbody>
<?php
foreach($forumList as $forum) {?>
<tr>
	<td><?php echo $forum['short_name']?></td>
	<td><?php echo $forum['title']?></td>
	<td><?php echo $forum['description']?></td>
	<td><a href='<?php echo $pluginpath?>?show=postadmin&amp;forumid=<?php echo $forum['id']?>'>
		<?php echo MF_SHOW ?></a>
	</td>
	<td><a href='<?php echo $pluginpath?>?show=editforum&amp;forumid=<?php echo $forum['id']?>'>
		<?php echo MF_EDIT_INFO?></a>
	<br />
	<a href='<?php echo $pluginpath?>?action=deleteforum&amp;forumid=<?php echo $forum['id']?>' onclick='return confirm("<?php echo MF_CONFIRM_FORUM_DELETE?>");'>
		<?php echo MF_DELETE?></a>
	</td>
</tr>
<?php } ?>
</tbody>  
</table>

<h2><?php echo MF_CREATE_FORUM_HEADING?></h2>
<?php
include "admin/forumForm.php";
?>
    

