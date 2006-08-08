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
* 	- form for forum editing
*/
?>
<h2><?php echo str_replace('$forum_name',$forumName,MF_CHANGE_FORUM)?></h2>

<?php
include "admin/forumForm.php";
?>
<p><a href="<?php echo $pluginpath ?>/index.php?show=forumadmin"><?php echo MF_BACK_TO_FORUMADMIN?></a></p>
