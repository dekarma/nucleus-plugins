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

  
  * NP_MiniForum admina area - templates
*/
?>
<?php if (requestVar('delete') == 1 && empty($errMsg)) {?>
<p><?echo MF_TEMPLATE_DELETED ?></p>
<?php } ?>
<?php if (requestVar('edit') == 1 && empty($errMsg)) {?>
<p><?echo MF_TEMPLATE_CHANGED ?></p>
<?php } ?>
<?php if (requestVar('copy') == 1 && empty($errMsg)) {?>
<p><?echo MF_TEMPLATE_COPIED ?></p>
<?php } ?>
<?php if (requestVar('create') == 1 && empty($errMsg)) {?>
<p><?echo MF_TEMPLATE_CREATED ?></p>
<?php } ?>

<?php if (!empty($errMsg)) {?>
<p style="color:red"><?echo $errMsg ?></p>
<?php } ?>


<h2><?php echo MF_NEW_TEMPLATE?></h2>

<ul>
	<li><a href='<?php echo $pluginpath?>/?show=edittempl'><?php echo MF_EMPTY_TEMPLATE?></a></li>
	<li><a href='<?php echo $pluginpath?>/?action=defaulttempl'><?php echo MF_DEFAULT_TEMPLATE?></a></li>
</ul>

<h2><?php echo MF_TEMPLATES_LIST?></h2>
<table>
<thead>
	<tr>
		<th><?php echo MF_TEMPLATE?></th>
		<th><?php echo MF_DESCRIPTION?></th>
		<th><?php echo MF_ACTIONS?></th>
	</tr>
</thead>
<tbody>
<?php foreach($templateList as $templ) { ?>
	<tr>
		<td><?php echo $templ['template']?></td>
		<td><?php echo $templ['description']?></td>
		<td>
			<a href='<?php echo $pluginpath?>?action=copytempl&amp;id=<?php echo $templ['template']?>'><?php 
			echo MF_COPY ?></a><br />
			<a href='<?php echo $pluginpath?>?show=edittempl&amp;id=<?php echo $templ['template']?>'><?php 
			echo MF_EDIT_TEMPL?></a><br />
			<a href='<?php echo $pluginpath?>?action=deletetempl&amp;id=<?php echo $templ['template']?>'
				onclick='return confirm("<?php echo MF_CONFIRM_TEMPLATE_DELETE?>");'><?php 
				echo MF_DELETE
			?></a>
		</td>
	</tr>
<?php } ?>
</tbody>  
</table>



