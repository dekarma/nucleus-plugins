<?php

$strRel = '../../../'; 
include($strRel . 'config.php');
include($DIR_LIBS . 'PLUGINADMIN.php');

/**
  * Create admin area
  */

$ssadmin  = new PluginAdmin('SubmitSystem');
//$ss =& $manager->getPlugin('NP_SubmitSystem');

if (!$member->isLoggedIn()) {
	$ssadmin->start();
	echo '<p>' . _ERROR_DISALLOWED . '</p>';
	$ssadmin->end();
	exit();
}

function generateBody($array) {
	global $CONF, $ssadmin;
	
	$body = null;
	$more = null;
	
	$body .= '<p>' . $array['ss_poster_name'] . ' (<a href="mailto:' . $array['ss_poster_email'] . '">email</a>';
	if (!empty($array['ss_poster_website'])) {
		$body .= ' | <a href="' . $array['ss_poster_website'] . '">website</a>';
	}
	$body .= ') submitted:</p>';
	$body .= "\n\n";
	$body .= '<p>' . nl2br($array['ss_body']) . '</p>';
	$body .= "\n\n";
	
	$extrafields = unserialize($array['ss_extrafields']);
	if (!empty($extrafields)) {
		$body .= '<p>Extra fields:<br />';
		foreach ($extrafields as $field) {
			$body .= '<b>' . $field['description'] . '</b> ' . $field['contents'] . '<br />';
		}
		$body .= '</p>';
		$body .= "\n\n";
	}
	
	if (!empty($array['ss_files'])) {
		$body .= '<p>Attachments: ';
		$first = true;
		foreach (explode('|', $array['ss_files']) as $file) {
			if ($first == false) {
				$body .= ' | ';
			}
			$body .= '<a href="' . $CONF['MediaURL'] . $file . '">' . $file . '</a>';
			$first = false;
		}
		$body .= '</p>';
	}
	
	if ($ssadmin->plugin->getOption('safepreview') == 'yes') {
		$body = safeHTML($body);
		$more = safeHTML($more);
	}
	
	return array($body, $more);
}

function safeHTML($text) {
	$text = preg_replace('/<(\/)?script(.*?)>/', '&lt;$1script$2&gt;', $text);
	
	$open = preg_match_all('/<(?!\/)(.*?)>/', $text, $opentags);
	$close = preg_match_all('/<\/(.*?)>/', $text, $closetags);
	$opentags = $opentags[1];
	$closetags = $closetags[1];
	
	for ($i = 0; $i < count($opentags); $i++) {
		$opentags[$i] = array_shift(explode(' ', $opentags[$i]));
	}
	
	for ($i = 0; $i < count($opentags); $i++) {
		$key = array_search($opentags[$i], $closetags);
		
		if ($key !== false) {
			$opentags[$i] = false;
			$closetags[$key] = false;
		}
	}
	
	foreach ($opentags as $o) {
		if ($o != false) {
			$text = $text . '</' . $o . '>';
		}
	}
	
	foreach ($closetags as $c) {
		if ($c != false) {
			$text = '<' . $c . '>' . $text;
		}
	}
	
	return $text;
}

if (isset($_GET['allow']) || isset($_GET['deny'])) {
	if  (isset($_GET['allow'])) {
		$id = $_GET['allow'];
	}
	else {
		$id = $_GET['deny'];
	}

	$id = intval($id);

	if ($id > 0) {
		$result = sql_query('SELECT * FROM ' . $ssadmin->plugin->dbtable . ' WHERE ss_id = ' . $id . ' LIMIT 1');
		$array = false;
		while ($row = mysql_fetch_array($result)) {
			$array = $row;
		}

		if (is_array($array) && (($ssadmin->plugin->getOption('moderatormode') == 'team' && $member->isTeamMember($array['ss_blogid'])) || ($ssadmin->plugin->getOption('moderatormode') == 'teamadmin' && $member->isBlogAdmin($array['ss_blogid'])) || $member->isAdmin())) {
			if ($ssadmin->plugin->getOption('log') == 'yes') {
				$result = sql_query('SELECT * FROM ' . $ssadmin->plugin->dbtablelog . ' WHERE ssl_poster_ip = \'' . $array['ss_poster_ip'] . '\'');
				if (mysql_num_rows($result) == 1) {
					$logarray = array();
					while ($row = mysql_fetch_array($result)) {
						$logarray = $row;
					}

					if (isset($_GET['allow'])) {
						$logarray['ssl_times_allowed']++;
					}
					else {
						$logarray['ssl_times_denied']++;
					}

					sql_query('UPDATE ' . $ssadmin->plugin->dbtablelog . ' SET ssl_times_allowed = ' . $logarray['ssl_times_allowed'] . ', ssl_times_denied = ' . $logarray['ssl_times_denied'] . ' WHERE ssl_id = ' . $logarray['ssl_id']);
				}
				else {
					$times_allowed = 0;
					$times_denied = 0;
					if (isset($_GET['allow'])) {
						$times_allowed++;
					}
					else {
						$times_denied++;
					}
					sql_query('INSERT INTO ' . $ssadmin->plugin->dbtablelog . ' (ssl_poster_ip,ssl_times_allowed,ssl_times_denied) VALUES (\'' . $array['ss_poster_ip'] . '\',' . $times_allowed . ',' . $times_denied . ')');
				}
			}

			if (isset($_GET['allow'])) {
				if ($ssadmin->plugin->getOption('previewmode') == 'yes' && isset($_POST['body']) && isset($_POST['title'])) {
					$array['ss_title'] = $_POST['title'];
					$body = $_POST['body'];
					$more = $_POST['more'];
				}
				else {
					$fields = generateBody($array);
					$body = $fields[0];
					$more = $fields[1];
				}

				//sql_query('INSERT INTO ' . sql_table('item') . '('
				//	. 'ititle,ibody,iblog,iauthor,itime,icat'
				//	. ') VALUES ('
				//	. '\'' . $array['ss_title'] . '\','
				//	. '\'' . $body . '\','
				//	. $array['ss_blogid'] . ','
				//	. $member->getID() . ','
				//	. '\'' . $array['ss_date'] . '\','
				//	. '1'
				//	. ')');

				$blog =& $manager->getBlog($array['ss_blogid']);
				$blog->additem(0, $array['ss_title'], $body, $more, $array['ss_blogid'], $member->getID(), $blog->getCorrectTime(), 0, 0);
			}
			else {
				foreach (explode('|', $array['ss_files']) as $file) {
					if (file_exists($DIR_MEDIA . $file)) {
						@unlink($DIR_MEDIA . $file);
					}
				}
			}

			sql_query('DELETE FROM ' . $ssadmin->plugin->dbtable . ' WHERE ss_id = ' . $id . ' LIMIT 1');
		}
	}
}

$ssadmin->start();

$menu = array();
$menu[] = '<a href="' . $_SERVER['PHP_SELF'] . '?page=home">New submissions</a>';
if ($ssadmin->plugin->getOption('log') == 'yes' && $member->isAdmin()) {
	$menu[] = '<a href="' . $_SERVER['PHP_SELF'] . '?page=log">View IP log</a>';
}

echo('<p>Here you can moderate submitted posts.<br />' . implode('<br />', $menu) . '</p>');

if (isset($_GET['preview']) && intval($_GET['preview']) > 0) {
	echo('<h3>Preview</h3>');

	$id = intval($_GET['preview']);

	$result = sql_query('SELECT * FROM ' . $ssadmin->plugin->dbtable . ' WHERE ss_id = ' . $id . ' LIMIT 1');
	$array = false;
	while ($row = mysql_fetch_array($result)) {
		$array = $row;
	}

	if (is_array($array) && (($ssadmin->plugin->getOption('moderatormode') == 'team' && $member->isTeamMember($array['ss_blogid'])) || ($ssadmin->plugin->getOption('moderatormode') == 'teamadmin' && $member->isBlogAdmin($array['ss_blogid'])) || $member->isAdmin())) {
		$fields = generateBody($array);
		$body = $fields[0];
		$more = $fields[1];

		echo('<script type="text/javascript">' . "\n");
		echo('<!--' . "\n");
		echo('function updatePreview() {' . "\n");
		echo('goal = document.getElementById(\'preview\');' . "\n");
		echo('goal.innerHTML = \'<h3>\' + document.getElementById(\'title\').value + \'</h3><p>\' + document.getElementById(\'body\').value + \'</p><p>\' + document.getElementById(\'more\').value + \'</p>\';' . "\n");
		echo('}' . "\n");
		echo('//-->' . "\n");
		echo('</script>');

		echo('<div id="preview" style="padding: 5px; border: 1px solid #000000;">');
		echo('<h3>' . $array['ss_title'] . '</h3>');
		echo('<p>' . $body . '</p>');
		echo('</div>');

		$javascriptpreview = 'onkeyup="storeCaret(this); updatePreview();" onclick="storeCaret(this);" onselect="storeCaret(this);"';

		echo('<form action="' . $_SERVER['PHP_SELF'] . '?allow=' . $id . '" method="post">');
		echo('<table>');
		echo('<tr><td>Title:</td><td><input type="text" id="title" name="title" ' . $javascriptpreview . ' value="' . $array['ss_title'] . '" /></td></tr>');
		echo('<tr><td>Body:</td><td><textarea id="body" name="body" ' . $javascriptpreview . ' rows="10">' . $body . '</textarea></td></tr>');
		echo('<tr><td>Extended:</td><td><textarea id="more" name="more" ' . $javascriptpreview . ' rows="10">' . $more . '</textarea></td></tr>');
		echo('<tr><td colspan="2"><input type="submit" value="Add" /></td></tr>');
		echo('</table>');
		echo('</form>');
	}
	else {
		echo('<p>Bad id...</p>');
	}
}
elseif ($_GET['page'] == 'log' && $ssadmin->plugin->getOption('log') == 'yes' && $member->isAdmin()) {
	echo('<h3>IP log</h3>');

	echo('<table>');
	echo('<thead><tr><td><b>IP</b></td><td><b>Times allowed</b></td><td><b>Times denied</b></td></tr></thead>');
	$result = sql_query('SELECT * FROM ' . $ssadmin->plugin->dbtablelog);
	while($row = mysql_fetch_array($result)) {
		echo('<tr>');
		echo('<td>' . $row['ssl_poster_ip'] . '</td>');
		echo('<td>' . $row['ssl_times_allowed'] . '</td>');
		echo('<td>' . $row['ssl_times_denied'] . '</td>');
	}
	echo('</table>');
}
elseif ($_GET['page'] == 'showbody' && is_numeric($_GET['id']) && $_GET['id'] > 0) {
	$bloginfos = array();
	$result = sql_query('SELECT *, UNIX_TIMESTAMP(ss_date) AS ss_date FROM ' . $ssadmin->plugin->dbtable . ' WHERE ss_id = ' . $_GET['id']);
	while($row = mysql_fetch_array($result)) {
		if (!isset($bloginfos[$row['ss_blogid']])) {
			 $bloginfos[$row['ss_blogid']] =& $manager->getBlog($row['ss_blogid']);
		}
	
		echo('<div style="padding: 5px; border: 1px solid #000000;">');
		echo('<h3>' . $row['ss_title'] . '</h3>');

		$fields = generateBody($row);
		$body = $fields[0];
		$more = $fields[1];
		
		echo('<p>' . $body . '</p>');
		echo('<p>' . $more . '</p>');
		
		echo('</div>');

		echo('<p>');
		echo('Posted at ' . date('d-m-Y H:i', $bloginfos[$row['ss_blogid']]->getCorrectTime($row['ss_date'])) . ' by ' . $row['ss_poster_name'] . ' (<a href="mailto:' . $row['ss_poster_email'] . '">email</a>');
		if (!empty($row['ss_poster_website'])) {
			echo(' | <a href="' . $row['ss_poster_website'] . '">website</a>');
		}
		echo(') from ' . $row['ss_poster_ip'] . '. ');
		echo('Actions: <a href="' . $_SERVER['PHP_SELF'] . '?');
		if ($ssadmin->plugin->getOption('previewmode') == 'yes') {
			echo('preview');
		}
		else {
			echo('allow');
		}
		echo('=' . $row['ss_id'] . '">Allow</a> | <a href="' . $_SERVER['PHP_SELF'] . '?deny=' . $row['ss_id'] . '">Deny</a></p>');
	}
}
else {
	echo('<h3>New submissions</h3>');

	echo('<table>');
	echo('<thead><tr><td><b>Blog</b></td><td><b>Title</b></td><td><b>Poster</b></td><td><b>Date</b></td><td><b>IP</b></td><td><b>Actions</b></td></tr></thead>');
	$result = sql_query('SELECT *, UNIX_TIMESTAMP(ss_date) AS ss_date FROM ' . $ssadmin->plugin->dbtable);
	while($row = mysql_fetch_array($result)) {
		if (($ssadmin->plugin->getOption('moderatormode') == 'team' && $member->isTeamMember($row['ss_blogid'])) || ($ssadmin->plugin->getOption('moderatormode') == 'teamadmin' && $member->isBlogAdmin($row['ss_blogid'])) || $member->isAdmin()) {
			echo('<tr>');
			$bloginfo =& $manager->getBlog($row['ss_blogid']);
			echo('<td><a href="' . $bloginfo->getURL() . '">' . $bloginfo->getShortName() . '</a></td>');
			//echo('<td>' . $row['ss_title'] . '</td>');
			echo('<td><a href="' . $_SERVER['PHP_SELF'] . '?page=showbody&id=' . $row['ss_id'] . '">' . $row['ss_title'] . '</a></td>');
			echo('<td>' . $row['ss_poster_name'] . ' (<a href="mailto:' . $row['ss_poster_email'] . '">email</a>');
			if (!empty($row['ss_poster_website'])) {
				echo(' | <a href="' . $row['ss_poster_website'] . '">website</a>');
			}
			echo(')</td>');
			echo('<td>' . date('d-m-Y H:i', $bloginfo->getCorrectTime($row['ss_date'])) . '</td>');
			//echo('<td>');
			//if (!empty($row['ss_extrafields'])) {
			//	$extrafields = unserialize($row['ss_extrafields']);
			//	foreach ($extrafields as $field) {
			//		echo('<b>' . $field['description'] . '</b> ' . $field['contents'] . '<br />');
			//	}
			//}
			//echo('</td>');
			//echo('<td>');
			//if (!empty($row['ss_files'])) {
			//	$first = true;
			//	foreach (explode('|', $row['ss_files']) as $file) {
			//		if ($first == false) {
			//			echo(' | ');
			//		}
			//		echo('<a href="' . $CONF['MediaURL'] . $file . '">' . $file . '</a>');
			//		$first = false;
			//	}
			//}
			//echo('</td>');
			echo('<td>' . $row['ss_poster_ip'] . '</td>');
			echo('<td><a href="' . $_SERVER['PHP_SELF'] . '?');
			if ($ssadmin->plugin->getOption('previewmode') == 'yes') {
				echo('preview');
			}
			else {
				echo('allow');
			}
			echo('=' . $row['ss_id'] . '">Allow</a> | <a href="' . $_SERVER['PHP_SELF'] . '?deny=' . $row['ss_id'] . '">Deny</a></td>');
			echo('</tr>');
		}
	}
	echo('</table>');
}

$ssadmin->end();
	
?>