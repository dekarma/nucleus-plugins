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

* NP_MiniForum admina area - Postlist for chosen forum.
*/
?>

<?php if (requestVar('delete') == 1 && empty($errMsg)) {?>
<p><?echo MF_POST_DELETED ?></p>
<?php } ?>
<?php if (requestVar('editpost') == 1 && empty($errMsg)) {?>
<p><?echo MF_POST_CHANGED ?></p>
<?php } ?>

<?php if (!empty($errMsg)) {?>
<p style="color:red"><?echo $errMsg ?></p>
<?php } ?>


<p><a href="<?php echo $pluginpath ?>/index.php?show=forumadmin"><?php echo MF_BACK_TO_FORUMADMIN?></a></p>

<h2><?php echo MF_LISTED_POSTS." '".$forum['short_name'] ?>'</h2>

<?php 
if (empty($postList)) {
?>
<p><?php echo str_replace('$forum_name',$forum['short_name'],MF_FORUM_EMPTY)?></p>
<?php 
} else { 
?>
<!-- navigation -->
<form method='post' action='<?php echo $pluginpath?>/index.php?show=postadmin&amp;forumid=<?php echo $forum['id']?>'>
    <table class='navigation'>
	<tr>
		<input type='hidden' name='page' value='<?php echo $page?>' />
    	<td><input type='submit' name='prev' value='<?php echo MF_PLIST_PREV?>' /></td>
        <td><?php 
		echo str_replace('$page_count',$pageCount,str_replace('$current_page',$page,MF_PLIST_CURRENT_PAGE));  
		?></td>
        <td><input type='submit' name='next' value='<?php echo MF_PLIST_NEXT?>' /></td>
    </tr>
	</table>
</form>

<table>
<!-- list of posts -->
<thead>
	<tr>
		<th><?php echo MF_PLIST_INF?></th>
		<th><?php echo MF_POST_TEXT?></th>
		<th><?php echo MF_PLIST_ACTIONS ?></th>
    </tr>
</thead>
<tbody>
<?php foreach ($postList as $post) { ?>
	<tr>
		<td><strong><?php echo $post['uname']."</strong>".($post['memberid'] != 0 ? " (member)" : "") ?>
			<br /><?php echo date("d.m.y",$post['time']).",".date("H:i",$post['time'])?>
		</td>    
		<td><?php echo $post['body']?></td>
		<td><a href='<?php echo $pluginpath?>/?action=deletepost&amp;postid=<?php echo $post['id']?>&amp;forumid=<?php echo $forum['id']?>&amp;page=<?php echo $page?>' 
			onclick='return confirm("<?php echo MF_CONFIRM_POST_DELETE?>");' ><?php 
				echo MF_DELETE?></a>
		<br /><a href='<?php echo $pluginpath?>/?show=editpost&amp;postid=<?php echo $post['id']?>&amp;page=<?php echo $page?>'><?php echo 
			MF_EDIT?></a>
		</td>
	</tr>
<?php }?>
</tbody>
</table>

<!-- navigation -->
<form method='post' action='<?php echo $pluginpath?>/index.php?show=postadmin&amp;forumid=<?php echo $forum['id']?>'>
    <table class='navigation'>
	<tr>
		<input type='hidden' name='page' value='<?php echo $page?>' />
    	<td><input type='submit' name='prev' value='<?php echo MF_PLIST_PREV?>' /></td>
        <td><?php 
		echo str_replace('$page_count',$pageCount,str_replace('$current_page',$page,MF_PLIST_CURRENT_PAGE));  
		?></td>
        <td><input type='submit' name='next' value='<?php echo MF_PLIST_NEXT?>' /></td>
    </tr>
	</table>
</form>
<?php
}?>
<p><a href="<?php echo $pluginpath ?>/index.php?show=forumadmin"><?php echo MF_BACK_TO_FORUMADMIN?></a></p>
