<?php
	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../'; 

	include($strRel . 'config.php');
	if (!$member->isLoggedIn())
		doError('You\'re not logged in.');
		
	include($DIR_LIBS . 'PLUGINADMIN.php');

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('Views');
	$oPluginAdmin->start();

	global $CONF, $manager;

	echo '<h2>NP_Views Counter Management</h2>';


        $doaction = getVar('doaction');
        if ($doaction == 'resetall') {
                $resetaction = $CONF['ActionURL'] . '?action=plugin&name=Views&type=resetallview';
                echo "You are about to reset all view counts!!!";
                echo "<form method=\"POST\" name=\"selectform\" action=\"".$resetaction. "\">";
                echo "<input type=\"submit\" value=\"Confirm Reset View Counts\" name=\"doaction\" />";
                echo "</form>";
                $oPluginAdmin->end();
                exit();
        }

        echo '<a href="' . $CONF['PluginURL'] . 'views/index.php/?doaction=resetall">Reset all view counts</a>';

	$offset = intRequestVar('offset');
	$sorting = 'id'; // views, id
	$sortby = getVar('sort');
        if ($sortby != '') {
	        $sorting = $sortby;
        }

        $order = 'inc'; // default incrument ordering
        $orderby = getVar('order');
        if ($orderby != '') {
                $order = $orderby;
        }

        $offset = 0;
        $offsetby = getVar('offset');
        if ($offsetby != '') {
                $offset = $offsetby;
        }

	$viewsURL = $CONF['PluginURL']."views/index.php";

        if ($order == "desc") {
                $desc = " DESC";
        }

        if ($order == 'inc') {
                $param = '&order=desc';
        } else {
                $param = '&order=inc';
        }

        $query ="SELECT count(*) as total FROM " . sql_table('plugin_views');
        $rows = mysql_query($query);
        $row=mysql_fetch_object($rows);
        $max_item = $row->total;
        //echo "total count " . $max_item . "<br/>";

	$query = "SELECT id,views FROM " . sql_table('plugin_views') . " ORDER BY " . $sorting . $desc . " LIMIT " . $offset . ",40";
        $rows = mysql_query($query);

        if ($sorting == 'id') {
                $idir = "(".$order.")";
        } else {
                $vdir = "(".$order.")";
        }

        echo "<table>\n";
        echo "<tr><th><a href=\"" . $viewsURL . "?sort=id" . $param ."\">ItemID " . $idir . "</a></th><th><a href =\"" . $viewsURL .  "?sort=views" . $param . "\">View Count " . $vdir . "</a></th><th>Action</th></tr>";

	while($row = mysql_fetch_object($rows)) {
		$item = $manager->getItem($row->id, 0, 0);
                $delurl = $CONF['ActionURL'] . '?action=plugin&name=Views&type=resetview&id=' . $row->id . "&order=" . $orderby . "&sort=" . $sortby; 
		echo "<tr>";
		echo "<td><a href=\"" . $CONF['IndexURL'] . createItemLink($row->id) . "\">" . $item['title'] . "</a></td>";
		echo "<td>" . $row->views . "</td>";
		echo "<td>" . "<a href=\"" . $delurl . "\">Reset count</a>" . "</td>";
		echo "</tr>";
	}

	echo "</table>\n";

        if ($max_item == 0) {
                echo "No item found<br/>";
        }

        $noffset = -1;
        if ($offset - 40 >= 0) {
                $noffset = $offset - 40;

                $nparam = '';
                if ($sorting != '') $nparam = $nparam . "sort=" . $sorting . "&order=" . $order . "&";
                $prelink = "<a href=\"" . $CONF['PluginURL'] . "views/index.php?" . $nparam . "offset=" . $noffset . "\">Previous</a> ";
        } else {
                $prelink = "Previous";
        }

        if ($offset + 40 < $max_item) {
                $noffset = $offset + 40;

                $nparam = '';
                if ($sorting != '') $nparam = $nparam . "sort=" . $sorting . "&order=" . $order . "&";
                $nextlink = "<a href=\"" . $CONF['PluginURL'] . "views/index.php?" . $nparam . "offset=" . $noffset . "\">Next</a>";
        } else {
                $nextlink = "Next";
        }

        echo $prelink . " | " . $nextlink . "<br/>";

	$oPluginAdmin->end();
?>
