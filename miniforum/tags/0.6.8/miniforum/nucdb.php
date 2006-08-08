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
  *  
  * This file includes db functions like sql_fetch_array, which are used in plugin, but
  * defined only in BLOG:CMS. 
*/

function sql_fetch_array($result) {
    return mysql_fetch_array($result);
}
function sql_num_rows($result) {
    return mysql_num_rows($result);
}

function sql_escape($string) {
    return addslashes($string);
}

//function sql_

?>
