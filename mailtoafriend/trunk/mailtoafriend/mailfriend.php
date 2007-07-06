<?php
/*
  History
    v1.0 - initial version
    v1.1 - add return path while sending mail (as suggested by user), see http://forum.nucleuscms.org/viewtopic.php?t=8637&start=0&postdays=0&postorder=asc&highlight= (admun)
    v1.2 - better item rendering in email
         - remove CSS from email and preview
         - email/title template
    v1.4 - email w/ proper internal skinvar resolved ie <%image%>
    v1.5 - fix missing message text from email
    v1.6 - finally implement email template
         - fix html/plain email text
    v1.6a - fix URL generation
    v1.7 - upgrade SpamCheck API
*/
	include('../../../config.php');
	// plugin needs to work on Nucleus versions <=2.0 as well
	if (!function_exists('sql_table')) {
		function sql_table($name) {
			return 'nucleus_' . $name;
		}
	}

	global $manager, $blog, $CONF, $DIR_PLUGINS, $member;

    if (! $itemid ||
         (! strstr($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) && ! $_SERVER['HTTP_REFERER'] == "")) {
        echo "==>".$_SERVER['HTTP_REFERER']."<==";
        //redirect("http://spamtrap.xiffy.nl/");
        exit;
    }
	$toEmail = requestVar(toEmail);
	$fromEmail = requestVar(fromEmail);
    $to = requestVar(to);
    $from = requestVar(from);
    $send = requestVar(send);
    $extra = requestvar(extra);
    $inHTML = requestVar(inHTML);

	$query = 'SELECT iblog FROM ' . sql_table('item') . ' WHERE inumber=' . intval($itemid);
	$res = sql_query($query);
	$obj = mysql_fetch_object($res);
	$blogid = $obj->iblog;

	if ($blogid) {
		$b =& $manager->getBlog($blogid);
	} else if ($blog) {
		$b =& $blog;
	} else {
		$b =& $manager->getBlog($CONF['DefaultBlog']);
	}
	$Blogname = $b->getName();
	$BlogURL  = $b->getURL();

	// Get the original plugin again ...
	$plugin =& $manager->getPlugin('NP_MailToAFriend');
	if ($plugin) {
		if ($extra == '') {
			$extra = $plugin->getOption(defaultMessage);
		}
		$template = $plugin->getOption(template);
	}

	// readCookievars ....
	dohtmlHead();

        // Spam Check with NP_Blacklist
        $spamcheck = array ('type'  => 'MailToAFriend',
                            'data'  => $extra.' '.$toEmail.' '.$fromEmail);
        $manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));

	// Here we decide what we do
	if ($send == 'true') {
		// check the email addresses
		$ok = isValidMailAddress($toEmail);
		if ($ok) {
			$ok = isValidMailAddress($fromEmail);
		}
	} else {
		$inHTML = 'on';
	}
	if ($ok == 0) {
		// Show errors if we get here again
		if (!$toEmail == '') {
			showError(' is not a valid email address', $toEmail);
		} else if ($send == 'true') {
			showError('Please fill in a valid email address!', $toEmail);
		}
		if (!$fromEmail == '' ) {
			showError(' is not a valid email address', $fromEmail);
		} else if ($send == 'true') {
			showError('Please fill in a valid email address!', $fromEmail);
		}

		showForm();

		$extraQuery = ' and inumber=' . intval($itemid);

		if ($template == '') {
			$template  = 'default';
		}
		echo '<div class="Mailfrienditem">';
		$b->readLogAmount($template, 1, $extraQuery, 0, 1, 0);
		echo '</div>';

	} else { // einde IF (!$OK)
	    // We go send this mail!
		$query =  'SELECT i.idraft as draft, i.inumber as itemid, i.iclosed as closed, '
		       . ' i.ititle as title, i.ibody as body, m.mname as author, '
		       . ' i.iauthor as authorid, i.itime, i.imore as more, i.ikarmapos as karmapos, '
		       . ' i.ikarmaneg as karmaneg, i.icat as catid, i.iblog as blogid '
		       . ' FROM '.sql_table('item').' as i, '.sql_table('member').' as m, ' . sql_table('blog') . ' as b '
		       . ' WHERE i.inumber=' . $itemid;

		$items = sql_query($query);
		$item = mysql_fetch_object($items);

		$extraQuery = ' and inumber=' . intval($itemid);

		if ($template == '') {
			$template  = 'default';
		}
                $message = file_get_contents ('./mailfriend.template');
		$message = str_replace('%##EXTRA##%', $extra, $message);

                ob_start();
		$b->readLogAmount($template, 1, $extraQuery, 0, 1, 0);
                $content .= ob_get_contents();
                ob_end_clean();

		$message = str_replace('%##TITLE##%', $item->title, $message);
		$message = str_replace('%##TEXT##%', $content, $message);

		if ($inHTML != 'on') {
                        $message = strip_tags($message);
                        $message = htmlspecialchars($message);
		        $message = str_replace('%##URL##%', $BlogURL . createItemLink($itemid), $message);
		} else {
		        $message = str_replace('%##URL##%', "<a href=\"" . $BlogURL .  createItemLink($itemid) .  "\">". $BlogURL . createItemLink($itemid)."</a>", $message);
                }


		$to = $toEmail;
		if (!$toName == '') {
			$to = '"' . $toName . '"<' . $toEmail . '>';
		}

		$from = 'From: ' . $fromEmail . " \n";
		if ($inHTML == 'on') {
			$from .= 'Content-Type: text/html; charset=iso-8859-1';
		} else {
			$from .= 'Content-Type: text/plain; charset=iso-8859-1';
		}

		// Some user suggested this might help for those having problem sending email with this...
		$from .= "\nX-Mailer: PHP/" . phpversion() . "\nReturn-Path: " . $CONF['AdminEmail'];

		$title =  $plugin->getOption(defaultTitle);
		$title = str_replace('%##BLOGNAME##%', $Blogname, $title);
		$title = str_replace('%##TITLE##%', $item->title, $title);
		$title = str_replace('%##URL##%', $CONF['IndexURL'] . createItemLink($itemid), $title);

		@mail($to, $title, $message, $from);
                //echo $title;
                //echo $message;
		echo '<h3>Thank you, the message is sent to: ' . $to . '</h3><br /><a href="javascript:window.close();">close Window</a><br />';
	}
	dohtmlEnd();

function showError($message, $what) {
	echo '<br />An error occurred' . "\n";
	echo "<br />".$what.': '.$message;
}

// functions for showing this page ...
function dohtmlHead() {
	global $Blogname, $BlogURL, $CONF;
	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">', "\n", '<html>', "\n", '<head>';
	echo '<title>Mailto a friend :: ', $Blogname, '::</title>', "\n";
	echo '<!-- stylesheet definition (points to the place where colors -->', "\n";
	echo '<!-- and layout is defined -->', "\n";
	echo '<link rel="stylesheet" type="text/css" href="', './mailfriend.css">';
	echo '<base href="', $CONF['IndexURL'], '">';
	echo '<link rel="stylesheet" type="text/css" href="', $CONF['AdminURL'], 'plugins/mailtoafriend/mailfriend.css">', "\n";
	echo '</head>', "\n";
	echo '<body>', "\n";
	return 1;
}

function dohtmlEnd() {
	if ($htmlMail == "on") {
	        echo '</body>', "\n", '</html>';
        }
	return 1;
}

function showForm () {
	global $toEmail, $fromEmail, $toName, $fromName, $extra, $itemid, $inHTML, $BlogURL, $CONF;
	echo '<form method="post" action="' . $CONF['AdminURL'] . 'plugins/mailtoafriend/mailfriend.php?itemid=' . $itemid . '&send=true">';
	echo '<table class="formfriendtable">';
	echo '<tr class="formfriendrow"><td class="formfriendcell">';
	echo 'Friends email:</td><td class="formfriendcell">';
	echo '<input type="text" name="toEmail" value="' . $toEmail . '" />';
	echo '</td><td rowspan="4">Message: <br />'
			. '<textarea name="extra" rows="5" cols="40" id="extra">' . $extra . '</textarea>'
			. '</td>';
	echo '</tr><tr class="formfriendrow"><td class="formfriendcell">Your email:';
	echo '</td><td class="formfriendcell">';
	echo '<input type="text" name="fromEmail" value="' . $fromEmail . '" />';
	echo '</td></tr><tr class="formfriendrow"><td class="formfriendcell">Friends Name:';
	echo '</td><td class="formfriendcell"><input type="text" name="toName" value="' . $toName . '" />';
	echo '</td></tr><tr class="formfriendrow"><td class="formfriendcell">Your Name:';
	echo '</td><td class="formfriendcell"><input type="text" name="FromName" value="' . $fromName . '" />';
	echo '</td></tr><tr class="formfriendrow"><td class="formfriendcell" >&nbsp;</td><td class="formfriendcell">';
	echo '<input type="checkbox" name="inHTML" "CHECKED">HTML</input></td><td class="formfriendcell">';
	echo '<input type="submit" value="Send" /></td></tr></table></form>';
}
