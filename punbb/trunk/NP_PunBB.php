<?php

/*************************************

  NP_PunBB 1.0 for Nucleus CMS
  (c) Radek HULAN,  http://hulan.info
  
  NP_PunBB 1.2 modifications by
  Bert Garcia (hcgtv)

  NP_PunBB 1.2.1 enhancements by
  Rickard Andersson
  
  NP_PunBB 1.3 enhancements by
  Rickard Andersson & Bert Garcia (hcgtv)
  
 *************************************/

class NP_PunBB extends NucleusPlugin
{
	function getNAME() { return 'PunBB'; }
	function getAuthor() { return 'Radek Hulan, Rickard Andersson, Bert Garcia'; }
	function getVersion() { return '1.3'; }
	function getURL() { return 'http://nupusi.com/'; }
	function getDescription() { return 'Plugin to integrate Nucleus with the PunBB forum.'; }

	function getTableList()
	{
		return array(sql_table('plugin_punbb'));
	}

	function getEventList()
	{
		return array(
			'PostRegister',
			'PostPluginOptionsUpdate',
			'AddItemFormExtras',
			'PostAddItem',
			'EditItemFormExtras',
			'PreUpdateItem'
			);
	}

	function install()
	{
		$this->createOption('host', 'PunBB host name:', 'text', 'localhost');
		$this->createOption('database', 'PunBB database name:', 'text', '');
		$this->createOption('username', 'PunBB database username:', 'text', 'root');
		$this->createOption('password', 'PunBB database password:', 'text', '');
		$this->createOption('prefix', 'PunBB table prefix:', 'text', '');
		$this->createOption('url', 'PunBB Forum URL (should NOT end with a slash):', 'text', 'http://www.example.com/forum');
		$this->createOption('copy', 'Copy all users, items and comments from Nucleus to PunBB now?', 'select', '0', 'no|0|yes|1');
		$this->createOption('drop', 'Drop tables on uninstall?', 'select', '0', 'no|0|yes|1');
		$this->createOption('create', 'Offer option to create topic in PunBB when adding/editing article?', 'select', '1', 'no|0|yes|1');
		$this->createOption('default', '"Add Article To PunBB?" is on by default?', 'select', '1', 'no|0|yes|1');
		$this->createOption('forum', 'Default PunBB forum number to create topics in:', 'text', '1');
		$this->createOption('linktext', 'Text to post before article link:', 'text', 'Link to article: ');
		$this->createOption('link', 'Add article LINK to PunBB topic?', 'select', '1', 'no|0|yes|1');
		$this->createOption('body', 'Add article BODY to PunBB topic?', 'select', '1', 'no|0|yes|1');
		$this->createOption('more', 'Add article MORE to PunBB topic?', 'select', '0', 'no|0|yes|1');
		$this->createOption('image', 'Embed images in posts?', 'select', '1', 'no|0|yes|1');
		$this->createOption('close', 'Automatically close comments for articles in PunBB?', 'select', '1', 'no|0|yes|1');
		
		$this->createCategoryOption('punbbtopic', 'Forum number in PunBB (0 for default):', 'text', '0');

		$query = "create table if not exists " . sql_table('plugin_punbb') . " (itemid int(11) auto_increment, topicid int(11) NOT NULL, PRIMARY KEY(itemid), KEY(topicid))";
		sql_query($query);
	}

	function unInstall()
	{
		if ($this->getOption('drop') == '1')
		{
			$query = "drop table if exists " . sql_table('plugin_punbb');
			sql_query($query);
		}
	}

	function supportsFeature($feature)
	{
		switch ($feature)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
			case 'HelpPage':
				return 1;
			default:
				return 0; 
		}
	}

	function cleanArticle($article, $image, $member)
	{
		global $CONF;

		// Make sure all linebreaks are \n
		$article = str_replace("\r", "", $article);
		// convert links into bbCode
		$article = preg_replace('/<a(.*?)href=[\'|\"](.*?)[\'|\"]>(.*?)<\/a>/', '[url=$2]$3[/url]', $article); 
		// convert images into bbCode
		if ($image == '1')
			$article = preg_replace('/<%image\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%>/', '[img]' . $CONF['MediaURL'] . strval($member) . '/' . '$1[/img]', $article);
		else
			$article = preg_replace('/<%image\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%>/', '{$4} ', $article);
		// convert popups into bbCode
		if ($image == '1')
			$article = preg_replace('/<%popup\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%>/', '[url=' . $CONF['MediaURL'] . strval($member) . '/' . '$1]' . '$4[/url]', $article);
		else
			$article = preg_replace('/<%popup\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%>/', '$4', $article);
		// remove other Nucleus pseudo-tags 
		$article = preg_replace('/<%(.*?)%>/', '', $article);
		// do bold, italic and underline
		$article = str_replace(array('<b>', '</b>', '<i>', '</i>', '<u>', '</u>'), array('[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]'), $article);
		// pre/code into bbCode
		$article = str_replace('<pre>', "[code]", $article);
		$article = str_replace('</pre>', "[/code]", $article);
		$article = str_replace('<code>', "[code]", $article);
		$article = str_replace('</code>', "[/code]", $article);
		// blockquote into bbCode
		$article=str_replace('<blockquote>',"[quote]",$article);
		$article=str_replace('</blockquote>',"[/quote]",$article);
		// ending tags into line breaks
		$article = str_replace('</p>', "\n\n", $article);
		$article = str_replace("<br />\n", "\n", $article);
		$article = str_replace('<br />', "\n", $article);
		$article = str_replace("<br>\n", "\n", $article);
		$article = str_replace('<br>', "\n", $article);
		$article = str_replace('</li>', "\n", $article); 
		// lists
		$article = str_replace('<li>', "* ", $article); 
		// headlines in bold
		$article = preg_replace('/<h(.*?)>(.*?)<\/h(.*?)>/', "[b]$2[/b]\n", $article); 
		// strip all other tags
		$article = trim(strip_tags($article));
		// convert &lt; and &gt; if entered to display code
		$article = str_replace(array('&lt;', '&gt;'), array('<', '>'), $article);

		return $article;
	}

	function event_PostRegister(&$data)
	{
		// user already exists?
		$result = mysql_query("select id from " . '`'.$this->getOption('database').'`' . "." . $this->getOption('prefix') . "users where username='" . addslashes($data['member']->displayname) . "'"); 
		// username still does not exists
		if (mysql_num_rows($result) == 0)
		{
			$query = "insert into " . '`'.$this->getOption('database').'`' . "." . $this->getOption('prefix') . "users".
				" (username,".
				"   group_id,".
				"   realname,".
				"   password,".
				"   email,".
				"   email_setting,save_pass,notify_with_post,show_smilies,show_img,show_avatars,show_sig,".
				"   timezone,style,registered)".
				" values(".
				" '".addslashes($data['member']->displayname)."',".
				" 4,".
				" '".addslashes($data['member']->realname)."',".
				" '".addslashes($data['member']->password)."',".
				" '".addslashes($data['member']->email)."',".
				" 1,1,1,1,1,1,1,".
				" 1,'Oxygen',".strval(time()).
				")";
			mysql_query($query) or exit(mysql_error());
		}
	}

	function event_PostPluginOptionsUpdate(&$data)
	{
		if ($this->getOption('copy') == '1')
		{
			$this->setOption('copy', '0');

			$dbname = '`'.$this->getOption('database').'`';
			$prefix = $this->getOption('prefix');

			// Copy Nucleus users into PunBB database
			$result = mysql_query("SELECT mname as username, mrealname as realname, mpassword as password, memail as email FROM " . sql_table('member'));
			while ($user = mysql_fetch_assoc($result))
			{
				// user already exists?
				$result2 = mysql_query("select id from $dbname." . $prefix . "users where username='" . addslashes($user['username']) . "'"); 
				if (!mysql_num_rows($result2))
				{
					$query = "insert into $dbname." . $prefix . "users".
						" (username,".
						"   group_id,".
						"   realname,".
						"   password,".
						"   email,".
						"   email_setting,save_pass,notify_with_post,show_smilies,show_img,show_avatars,show_sig,".
						"   timezone,style,registered)".
						" values(".
						" '".addslashes($user['username'])."',".
						" 4,".
						" '".addslashes($user['realname'])."',".
						" '".addslashes($user['password'])."',".
						" '".addslashes($user['email'])."',".
						" 1,1,1,1,1,1,1,".
						" 1,'Oxygen',".strval(time()).
						")";
					mysql_query($query) or exit(mysql_error());
				}
			}

			// Copy Nucleus items into PunBB database
			$result = mysql_query("SELECT inumber, ititle AS title, ibody AS body, imore AS more, mname AS name, UNIX_TIMESTAMP(itime) AS itime, iauthor AS member FROM ".sql_table('item')." i LEFT JOIN ".sql_table('plugin_punbb')." p ON i.inumber=p.itemid INNER JOIN ".sql_table('member')." m ON mnumber=iauthor WHERE p.itemid IS NULL"); 
			while ($msg_item = mysql_fetch_assoc($result))
			{
				$msg_member = array('name' => $msg_item['name']);

				$result2 = mysql_query("select id from $dbname." . $prefix . "users where username='" . addslashes($msg_member['name']) . "'");
				if (!mysql_num_rows($result2))
					doError("User " . $msg_member['name'] . " does NOT exist in PunBB user database!");
				else
					$user_id = mysql_result($result2, 0);

				$topicid = $this->createPunBBTopic($msg_item, $msg_member, $user_id, true, true);
			}
		}
	}

	function event_AddItemFormExtras($data)
	{
		if ($this->getOption('create') == '1')
		{
			$s1 = $s2 = '';
			echo '<table><tr><td><h3>Create topic in PunBB?</h3>';

			if ($this->getOption('default') == '1')
				$s1 = ' checked="checked"';
			else
				$s2 = ' checked="checked"';

			echo '<input name="punbbtopiccreate" type="radio" value="1" id="punbb1"' . $s1 . ' /><label for="punbb1">yes</label> ';
			echo '<input name="punbbtopiccreate" type="radio" value="0" id="punbb2"' . $s2 . ' /><label for="punbb1">no</label>';
			echo '</td><td><h3>Copy article text as well?</h3>';
			echo '<input name="punbbcopy" type="radio" value="1" id="punbb3" checked="checked" /><label for="punbb3">yes</label> ';
			echo '<input name="punbbcopy" type="radio" value="0" id="punbb4" /><label for="punbb4">no</label></td></tr></table>';
		}
	}

	function event_EditItemFormExtras($data)
	{
		if ($this->getOption('create') != '1') return;
		$query = mysql_query('select topicid from ' . sql_table('plugin_punbb') . ' where itemid=' . strval($data['itemid']));
		if ($msg = mysql_fetch_array($query)) $s = strval($msg['topicid']);
		else $s = '';
		echo '<h3>PunBB</h3><p><label for="punbb1">PunBB Topic Number:</label> <input name="punbbtopicnumber" type="text" value="' . $s . '" id="punbb1" /></p>';
	}

	function createPunBBTopic($msg_item, $msg_member, $user_id, $full_post, $comments)
	{
		global $CONF;

		$dbname = '`'.$this->getOption('database').'`';
		$prefix = $this->getOption('prefix');
		$body = $this->getOption('body');
		$more = $this->getOption('more');
		$link = $this->getOption('link');
		$image = $this->getOption('image'); 

		$myurl = 'http://' . str_replace('//', '/', str_replace('http://', '', $CONF['IndexURL'] . createItemLink($msg_item['inumber'])));

		$result = mysql_query('select icat from ' . sql_table('item') . ' where inumber=' . $msg_item['inumber']);
		$forum = $this->getCategoryOption(intval(mysql_result($result, 0)), 'punbbtopic');
		if (intval($forum) == 0) $forum = $this->getOption('forum');

		$result = mysql_query("select id from $dbname." . $prefix . "forums where id=" . $forum);
		if (!($temp = mysql_fetch_assoc($result)))
			doError("Forum number " . $forum . " does NOT exist in PunBB database!");

		/* ------- TOPICS ------- */
		$subject = trim(strip_tags($msg_item['title']));
		if ($subject == '')
			$subject = 'No subject';
		$result = "insert into $dbname." . $prefix . "topics (posted,poster,subject,num_views,num_replies,closed,sticky,forum_id) values (" . $msg_item['itime'] . ", '" . $msg_member['name'] . "','" . addslashes($subject) . "',0,0,0,0," . $forum . ")"; 
		if (!mysql_query($result))
			doError("Error with query: $query");
		$topicid = mysql_insert_id(); 

		// insert full post?
		if ($full_post)
		{
			$article = '';
			if ($body == '1') $article .= $msg_item['body'] . "\n\n";
			if ($more == '1') $article .= $msg_item['more'] . "\n\n";
			if ($link == '1') $article .= $this->getOption('linktext') . " [b][url]" . $myurl . "[/url][/b]"; 

			$article = $this->cleanArticle($article, $image, $msg_item['member']);
		}
		if (empty($article)) $article = $this->getOption('linktext') . " [url]" . $myurl . "[/url]";

		/* ------- POSTS ------- */
		$query = "insert into $dbname." . $prefix . "posts " . " ( poster," . "   poster_id," . "   message," . "   hide_smilies," . "   posted," . "   topic_id," . "   poster_ip" . " ) values ( " . " '" . addslashes($msg_member['name']) . "'," . " " . strval($user_id) . "," . " '" . addslashes($article) . "'," . " 1," . " " . $msg_item['itime'] . "," . " " . strval($topicid) . "," . " '" . addslashes($_SERVER["REMOTE_ADDR"]) . "'" . " )";
		if (!mysql_query($query))
			doError("Error with query: $query");

		$last_post = $msg_item['itime'];
		$last_post_id = mysql_insert_id();
		$last_poster = $msg_member['name'];
		$post_count = 1;

		$query = "update $dbname." . $prefix . "users set last_post=" . $msg_item['itime'] . ",num_posts=num_posts+1 " . "where id=" . strval($user_id);
		if (!mysql_query($query))
			doError("Error with query: $query");

		if ($comments)
		{
			// We've created the topic and the topic post, now deal with any comments
			$result2 = mysql_query("SELECT c.cbody, c.cuser, c.cmember, UNIX_TIMESTAMP(c.ctime) AS ctime, c.cip, m.mname FROM ".sql_table('comment')." c LEFT JOIN ".sql_table('member')." m ON m.mnumber=c.cmember WHERE c.citem=" . $msg_item['inumber']);
			while ($cmt_item = mysql_fetch_assoc($result2))
			{
				$cusername = '';
				$cuser_id = 1;
				if (intval($cmt_item['cmember']) > 0)
				{
					$cusername = $cmt_item['mname'];
					
					$result3 = mysql_query("select id from $dbname." . $prefix . "users where username='" . addslashes($cusername) . "'");
					if (!mysql_num_rows($result3))
						doError("User " . $cusername . " does NOT exist in PunBB user database!");
					else
						$cuser_id = mysql_result($result3, 0);

					$result3 = "update $dbname." . $prefix . "users set last_post=" . $cmt_item['ctime'] . ",num_posts=num_posts+1 " . "where id=" . $cuser_id;
					if (!mysql_query($result3))
						doError("Error with query: $query");
				}
				else
					$cusername = $cmt_item['cuser'];

				$cmt_item['cbody'] = str_replace(array('<br />', '&amp;', '&#039;', '&quot;', '&lt;', '&gt;'), array('', '&', '\'', '"', '<', '>'), $cmt_item['cbody']);

				$query = "insert into $dbname." . $prefix . "posts " . " ( poster," . "   poster_id," . "   message," . "   hide_smilies," . "   posted," . "   topic_id," . "   poster_ip" . " ) values ( " . " '" . addslashes($cusername) . "'," . " " . strval($cuser_id) . "," . " '" . addslashes($cmt_item['cbody']) . "'," . " 1," . " " . $cmt_item['ctime'] . "," . " " . strval($topicid) . "," . " '" . addslashes($cmt_item['cip']) . "'" . " )";
				if (!mysql_query($query))
					doError("Error with query: $query");

				$last_post = $cmt_item['ctime'];
				$last_post_id = mysql_insert_id();
				$last_poster = $cusername;
				$post_count++;
			}
		}

		/* ------- TOPICS ------- */
		$query = "update $dbname." . $prefix . "topics set last_post=" . $last_post . ",last_post_id=" . $last_post_id . ",last_poster='" . addslashes($last_poster) . "',num_replies=" . strval($post_count-1) . " where id=" . strval($topicid);
		if (!mysql_query($query))
			doError("Error with query: $query");

		/* ------- FORUMS ------- */
		$query = "update $dbname." . $prefix . "forums set last_post=" . $last_post . ",num_posts=num_posts+" . $post_count . ",num_topics=num_topics+1 " . ",last_post_id=" . $last_post_id . ",last_poster='" . addslashes($last_poster) . "'" . "where id=" . $forum;
		if (!mysql_query($query))
			doError("Error with query: $query");

		// close comment for this article?
		if ($this->getOption('close') == '1')
		{
			$query = "update " . sql_table('item') . " set iclosed=1 where inumber=" . $msg_item['inumber'];
			sql_query($query);
		}
		// save relation between an article and topic id
		if ($topicid > 0)
		{
			$query = "insert into " . sql_table('plugin_punbb') . "(itemid,topicid) values (" . $msg_item['inumber'] . ",$topicid)";
			mysql_query($query);
		}

		return $topicid;
	}

	// create new topic in PunBB database */
	function event_PostAddItem(&$data)
	{
		if ($this->getOption('create') != '1') return;
		if (isset($_POST['punbbtopiccreate']) && ($_POST['punbbtopiccreate'] == '1'))
		{
			$myitemid = strval(intval($data['itemid']));

			$result = mysql_query('select ititle as title, ibody as body, imore as more, UNIX_TIMESTAMP(itime) as itime, iauthor as member from ' . sql_table('item') . ' where inumber=' . $myitemid);
			if (!($msg_item = mysql_fetch_array($result))) return;
			$msg_item['inumber'] = $myitemid;

			$result = mysql_query('select mname as name from ' . sql_table('member') . ' where mnumber=' . strval($msg_item['member']));
			if (!($msg_member = mysql_fetch_array($result))) return;

			$result = mysql_query("select id from " . '`'.$this->getOption('database').'`' . "." . $this->getOption('prefix') . "users where username='" . addslashes($msg_member['name']) . "'");
			if (!mysql_num_rows($result))
				doError("User " . $msg_member['name'] . " does NOT exist in PunBB user database!");
			else
				$user_id = mysql_result($result, 0);

			$full_post = (isset($_POST['punbbcopy']) && ($_POST['punbbcopy'] == '1')) ? true : false;
			$this->createPunBBTopic($msg_item, $msg_member, $user_id, $full_post, false);
		}
	}

	function event_PreUpdateItem($data)
	{
		if ($this->getOption('create') != '1') return;
		if (isset($_POST['punbbtopicnumber']) && (strlen($_POST['punbbtopicnumber']) > 0))
		{
			$result = mysql_query('select topicid from ' . sql_table('plugin_punbb') . ' where itemid=' . strval($data['itemid']));
			if ($row = mysql_fetch_assoc($result))
				$query = 'update ' . sql_table('plugin_punbb') . ' set topicid=' . $_POST['punbbtopicnumber'] . ' where itemid=' . strval($data['itemid']);
			else
				$query = 'insert into ' . sql_table('plugin_punbb') . ' (itemid,topicid) values (' . strval($data['itemid']) . ',' . $_POST['punbbtopicnumber'] . ')';
			sql_query($query);
		}
	}

	function doTemplateVar(&$item, $text_pre = '', $text_post = '')
	{
		$id = strval(intval($item->itemid));
		$result = mysql_query('select topicid from ' . sql_table('plugin_punbb') . ' where itemid=' . $id);
		if ($msg = mysql_fetch_array($result)) if (intval($msg['topicid']) > 0)
		{
			$result = mysql_query("select num_replies from " . '`'.$this->getOption('database').'`' . "." . $this->getOption('prefix') . "topics where id=".intval($msg['topicid']));
			$num_replies = ($result) ? mysql_result($result, 0) : 0;

			echo $text_pre;
			echo $this->getOption('url') . "/viewtopic.php?id=" . strval($msg['topicid'] . "&amp;action=new");
			echo $text_post.' ('.$num_replies.')';
		}
	}
}

?>