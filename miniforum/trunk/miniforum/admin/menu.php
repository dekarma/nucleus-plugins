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
*	- horizontal menu
*/
$selectedStyle = 'style="text-decoration:underline; color:black;"';
?>
<ul style='list-style-type:none; margin:-10px 0px 0px 0px; padding:0px; font-size:110%;'>
	<li style='display:inline; padding:0 0.5em; border-left:1px solid black'>
		<a href='<?php echo $pluginpath?>?show=forumadmin'  title='<?php echo MF_FORUMS_TITLE ?>' <?php echo ($show == 'forumadmin')? $selectedStyle : '' ?> ><?php 
		 	echo MF_FORUMS 
		?></a>
	</li>
	<li style='display:inline; padding:0 0.5em; border-left:1px solid black'>
		<a href='<?php echo $pluginpath?>?show=templadmin' title='<?php echo MF_TEMPLATES_TITLE ?>' <?php echo ($show == 'templadmin')? $selectedStyle : '' ?>><?php 
			echo MF_TEMPLATES 
		?></a>
	</li>
	<li style='display:inline; padding:0 0.5em; border-left:1px solid black'>
		<a href="<?php echo $CONF['AdminURL']?>index.php?action=pluginoptions&amp;plugid=<?php echo $oPluginAdmin->plugin->plugid?>" title="<?php echo MF_OPTIONSLINK_TITLE?>"><?php 
		echo MF_OPTIONSLINK.'</a>';
		?>
	</li>
	<li style='display:inline; padding:0 0.5em; border-left:1px solid black'>
		<a href="http://wakka.xiffy.nl/miniforum" title='<?php echo MF_DOCLINK_TITLE?>'>
			<?php echo MF_DOCLINK ?> &raquo;
		</a>
	</li>
	<li style='display:inline; padding:0 0.5em; border-left:1px solid black; border-right:1px solid black;'>
		<a href="http://forum.nucleuscms.org/viewtopic.php?t=8810" title='<?php echo MF_FORUMLINK_TITLE ?>'>
			<?php echo MF_FORUMLINK ?> &raquo;
		</a>
	</li>
</ul>
