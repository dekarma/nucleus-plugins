<?php
// make sure we have the nucleus config info needed
	include "../../../config.php";

// define some variables
    global $member;
    if ($member->isAdmin()) {
        if (strpos(strtolower(ini_get('include_path')), "pear")) echo "You have access to PEAR core files";
        else echo "You seem to not have access to the PEAR core files. Sorry.";
    }
    else echo "Sorry. You are not logged in.";
?>
