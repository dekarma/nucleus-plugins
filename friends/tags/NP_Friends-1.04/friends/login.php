<?php
// include all classes and config data
include('../../../config.php');
global $CONF;
$action = requestVar('action');
if (($action == 'login') && ($member->isLoggedIn())) {
	$action = requestVar('nextaction');
	$you = intRequestVar("mid");    $friendid = intRequestVar("fid");
	$key = requestVar('key');
	$desturl = $CONF['ActionURL']."?action=plugin&name=Friends&type=activate&mid=$you&fid=$friendid&key=$key";
}
else $desturl = $CONF['IndexURL'];
redirect($desturl);
?>