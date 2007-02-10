<?php
session_start();

$strRel = '../../../'; 
require ($strRel . 'config.php');

require './texts.php';

$blogid = $CONF['DefaultBlog'];

if (isset($_GET['blogid']) && is_numeric($_GET['blogid']) && $_GET['blogid'] > 0) {
	$blogid = $_GET['blogid'];
}

$ss =& $manager->getPlugin('NP_SubmitSystem');
$blog =& $manager->getBlog($blogid);
$skinid = $blog->getDefaultSkin();
$skin =& new SKIN($skinid);

if (!$blog->isValid || !$skin->isValid || !$ss) {
	echo('An error occured...');
	exit();
}

$CONF['Self'] = $blog->getURL();
$CONF['ItemURL'] = $CONF['Self'];
$CONF['ArchiveURL'] = $CONF['Self'];
$CONF['ArchiveListURL'] = $CONF['Self'];
$CONF['MemberURL'] = $CONF['Self'];
$CONF['SearchURL'] = $CONF['Self'];
$CONF['BlogURL'] = $CONF['Self'];
$CONF['CategoryURL'] = $CONF['Self'];

PARSER::setProperty('IncludeMode', $skin->getIncludeMode());
if ($skin->getIncludeMode() == 'normal') {
	PARSER::setProperty('IncludePrefix', '../../../' . $skin->getIncludePrefix());
}
else {
	PARSER::setProperty('IncludePrefix', $skin->getIncludePrefix());
}



// Page
$output = null;

if (in_array($blog->getShortName(), explode('|', $ss->getOption('excludeblogs')))) {
	$output = _SUBMIT_MSG_BLOGEXCLUDED;
}
else if (isset($_SESSION['waittime']) && $_SESSION['waittime'] > time()) {
	$output = _SUBMIT_MSG_TOOFAST;
}
else {
	$posted = false;
	$error = array();
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if ((($ss->getOption('captcha') == 'yes' && strtolower($_SESSION['captcha']) == strtolower($_POST['captcha'])) || $ss->getOption('captcha') == 'no') && ((!empty($_POST['poster']) && !empty($_POST['poster_email'])) || $member->isLoggedIn()) && !empty($_POST['title']) && !empty($_POST['body'])) {
			$files = array();
			if ($ss->getOption('fileupload') == 'yes' && $ss->getOption('filecount') > 0) {
				$count = $ss->getOption('filecount');
				if ($count > 5) {
					$count = 5;
				}
				for ($i = 0; $i < $count; $i++) {
					if (is_uploaded_file($_FILES['userfiles']['tmp_name'][$i]) && (in_array(strtolower(end(explode('.', $_FILES['userfiles']['name'][$i]))), explode('|', strtolower($ss->getOption('filetypes')))) || $ss->getOption('filetypes') == '*') && $_FILES['userfiles']['size'][$i] <= $ss->getOption('filesize')) {
						$name = $_FILES['userfiles']['name'][$i];
						while (file_exists($DIR_MEDIA . $ss->getOption('fileprefix') . $name)) {
							$name = rand(0, 9) . $name;
						}
						
						if (@copy($_FILES['userfiles']['tmp_name'][$i], $DIR_MEDIA . $ss->getOption('fileprefix') . $name)) {
							$files[] = $ss->getOption('fileprefix') . $name;
						}
						else {
							$error[] = _SUBMIT_ERR_COPY;
						}
					}
					else {
						if (is_uploaded_file($_FILES['userfiles']['tmp_name'][$i])) {
							if (!(in_array(strtolower(end(explode('.', $_FILES['userfiles']['name'][$i]))), explode('|', strtolower($ss->getOption('filetypes')))) || $ss->getOption('filetypes') == '*')) {
								$error[] = _SUBMIT_ERR_TYPE;
							}
							if (!$_FILES['userfiles']['size'][$i] <= $ss->getOption('filesize')) {
								$error[] = _SUBMIT_ERR_SIZE;
							}
						}
					}
				}
			}
			
			$extrafields_content = array();
			$extrafields = $ss->getOption('extrafields');
			if (!empty($extrafields)) {
				foreach (explode('|', $extrafields) as $field) {
					$matches = array();
					preg_match('/\[\'(.*)\'\]/', $field, $matches);
					$description = $field;
					$name = $field;
					if (isset($matches[1])) {
						$description = $matches[1];
						$name = substr($field, 0, strpos($field, $matches[0]));
					}
					if (isset($_POST['extra'][$name])) {
						$extrafields_content[] = array('description' => $description, 'contents' => htmlentities($_POST['extra'][$name], ENT_QUOTES, _CHARSET));
					}
				}
			}
			
			if (!$member->isLoggedIn()) {
				$poster = $_POST['poster'];
				$posteremail = $_POST['poster_email'];
				$posterwebsite = $_POST['poster_website'];
				
				if (MEMBER::exists($poster)) {
					$error[] = _SUBMIT_ERR_MEMBEREXISTS;
				}
			}
			else {
				$poster = $member->getDisplayName();
				$posteremail = $member->getEmail();
				$posterwebsite = $member->getURL();
			}
			
			if (empty($error)) {
				sql_query('INSERT INTO ' . $ss->dbtable . ' ('
					. 'ss_blogid,ss_title,ss_body,ss_poster_name,ss_poster_email,ss_poster_website,ss_poster_ip,ss_date,ss_extrafields,ss_files'
					. ') VALUES ('
					. $blogid . ','
					. '\'' . htmlentities($_POST['title'], ENT_QUOTES, _CHARSET) . '\','
					. '\'' . htmlentities($_POST['body'], ENT_QUOTES, _CHARSET) . '\','
					. '\'' . htmlentities($poster, ENT_QUOTES, _CHARSET) . '\','
					. '\'' . htmlentities($posteremail, ENT_QUOTES) . '\','
					. '\'' . htmlentities($posterwebsite, ENT_QUOTES) . '\','
					. '\'' . htmlentities($_SERVER['REMOTE_ADDR'], ENT_QUOTES) . '\','
					. 'NOW(),'
					. '\'' . serialize($extrafields_content) . '\','
					. '\'' . implode('|', $files) . '\''
					. ')');
				
				$posted = true;
				
				if ($ss->getOption('emailnotification') == 'yes') {
					$notification = new NOTIFICATION($CONF['AdminEmail']);
					$message .= 'Hi,' . "\n";
					$message .= "\n";
					$message .= 'There is a new submission on your site,' . "\n";
					$message .= 'please go to the admin area (' . $CONF['AdminURL'] . ') to moderate it' . "\n";
					$message .= "\n";
					$message .= 'Have a nice day,' . "\n";
					$message .= 'SubmitSystem Emailer';
					$notification->notify('New submission', $message . getMailFooter(), $CONF['AdminEmail']);
				}
				
				if ($ss->getOption('waittime') > 0) {
					$_SESSION['waittime'] = time() + $ss->getOption('waittime');
				}
			}
			else {
				$output .= '<div class="errors">';
				foreach ($error as $err) {
					$output .= '<span class="error">' . $err . '</span><br />';
				}
				$output .= '</div>';
			}
		}
	}
	
	if ($posted == false) {
		$output .= '<form action="' . $_SERVER['PHP_SELF'] . '?blogid=' . $blogid . '" method="post" name="submitform" enctype="multipart/form-data" onsubmit="submitform.submit.disabled = true;">';
		$output .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . $ss->getOption('filesize') . '" />';
		$output .= '<table>';
		if (!$member->isLoggedIn()) {
			$output .= '<tr><td>' . _SUBMIT_YOURNAME . _SUBMIT_REQUIREDFIELD . ':</td><td><input name="poster" type="text" value="' . htmlentities($_POST['poster'], ENT_QUOTES, _CHARSET) . '" /></td></tr>';
			$output .= '<tr><td>' . _SUBMIT_YOUREMAIL . _SUBMIT_REQUIREDFIELD . ':</td><td><input name="poster_email" type="text" value="' . htmlentities($_POST['poster_email'], ENT_QUOTES) . '" /></td></tr>';
			$output .= '<tr><td>' . _SUBMIT_YOURWEBSITE . ':</td><td><input name="poster_website" type="text" value="' . htmlentities($_POST['poster_website'], ENT_QUOTES) . '" /></td></tr>';
		}
		$output .= '<tr><td>' . _SUBMIT_TITLE . _SUBMIT_REQUIREDFIELD . ':</td><td><input name="title" type="text" value="' . htmlentities($_POST['title'], ENT_QUOTES, _CHARSET) . '" /></td></tr>';
		$output .= '<tr><td>' . _SUBMIT_BODY. _SUBMIT_REQUIREDFIELD . ':</td><td><textarea name="body" rows="10">' . htmlentities($_POST['body'], ENT_QUOTES, _CHARSET) . '</textarea></td></tr>';
		$extrafields = $ss->getOption('extrafields');
		if (!empty($extrafields)) {
			foreach (explode('|', $extrafields) as $field) {
				$matches = array();
				preg_match('/\[\'(.*)\'\]/', $field, $matches);
				$description = $field;
				$name = $field;
				if (isset($matches[1])) {
					$description = $matches[1];
					$name = substr($field, 0, strpos($field, $matches[0]));
				}
				$output .= '<tr><td>' . $description . '</td><td><input name="extra[' . $name . ']" type="text" value="' . htmlentities($_POST['extra'][$name], ENT_QUOTES, _CHARSET) . '" /></td></tr>';
			}
		}
		if ($ss->getOption('fileupload') == 'yes' && $ss->getOption('filecount') > 0) {
			$output .= '<tr><td>' . _SUBMIT_FILES . ':</td><td>';
			$count = $ss->getOption('filecount');
			if ($count > 5) {
				$count = 5;
			}
			for ($i = 0; $i < $count; $i++) {
				if ($i != 0) {
					$output .= '<br />';
				}
				$output .= '<input name="userfiles[]" type="file" />';
			}
			$output .= '</td></tr>';
		}
		if ($ss->getOption('captcha') == 'yes') {
			$chars = array();
			for ($i = 0; $i < 26; $i++) {
				$chars[] = chr($i + 65);
				$chars[] = chr($i + 97);
			}
			for ($i = 0; $i < 10; $i++) {
				$chars[] = chr($i + 48);
			}
			$_SESSION['captcha'] = null;
			for ($i = 0; $i < 5; $i++) {
				$_SESSION['captcha'] .= $chars[rand(0, count($chars) - 1)];
			}
			$output .= '<tr><td>' . _SUBMIT_CAPTCHA . _SUBMIT_REQUIREDFIELD . ':</td><td><img src="captcha.php" /><br /><input name="captcha" type="text" value="" /></td></tr>';
		}
		$output .= '<tr><td colspan="2" style="text-align: center;"><input name="submit" type="submit" value="' . _SUBMIT_SUBMIT . '" /></td></tr>';
		$output .= '</table>';
		$output .= '</form>';
	}
	else {
		$output .= _SUBMIT_MSG_SUCCESS . '<br /><a href="' . $blog->getURL() . '">' . _SUBMIT_MSG_RETURN . '</a>';
	}
}

$output = str_replace('<%SubmitSystemMain%>', $output, $ss->getOption('skin'));



// Parse!
$actions = $skin->getAllowedActionsForType('index');
$handler =& new ACTIONS('plugin', $skin);
$parser =& new PARSER($actions, $handler);
$handler->setParser($parser);
$handler->setSkin($skin);
$parser->parse($output);

?>