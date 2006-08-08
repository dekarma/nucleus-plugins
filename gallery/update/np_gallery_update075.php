<?php

include('./../../../../config.php');
global $DIR_PLUGINS;
include_once ($DIR_PLUGINS.'gallery/config.php');

//from 0.75 to 0.76
global $NPG_CONF;

$query = 'show columns from '.sql_table('plug_gallery_album').' like "thumbnail"';
$res = sql_query($query);
if(!mysql_num_rows($res)) {
	$query = 'ALTER TABLE '.sql_table('plug_gallery_album').
		' add column thumbnail varchar(100) ';
	sql_query($query);
}

include($DIR_PLUGINS.'gallery/update/default_templates_076.inc');

setNPGoption('currentversion',76);

include('np_gallery_update077.php');

?>
