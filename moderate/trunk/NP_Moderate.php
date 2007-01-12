<?php

   /* ==========================================================================================
    * Moderate for Nucleus CMS
    * Copyright 2005-2007, Niels Leenheer
    * ==========================================================================================
    * This program is free software and open source software; you can redistribute
    * it and/or modify it under the terms of the GNU General Public License as
    * published by the Free Software Foundation; either version 2 of the License,
    * or (at your option) any later version.
    *
    * This program is distributed in the hope that it will be useful, but WITHOUT
    * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
    * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
    * more details.
    *
    * You should have received a copy of the GNU General Public License along
    * with this program; if not, write to the Free Software Foundation, Inc.,
    * 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
    * http://www.gnu.org/licenses/gpl.html
    * ==========================================================================================
    */
   
class NP_Moderate extends NucleusPlugin {
	function getName() 				{ return 'Moderate'; }
	function getAuthor()  		  	{ return 'Niels Leenheer'; }
	function getURL()  				{ return 'http://www.rakaz.nl'; }
	function getVersion() 	  		{ return '0.8'; }
	function getDescription() 		{ return _MODERATE_DESCRIPTION; }
	function getMinNucleusVersion() { return 330; }

	function supportsFeature($what) {
		switch($what) {
		    case 'SqlTablePrefix':
			case 'handleSpam':
				return 1;
			default:
				return 0;
		}
	}
	
	function getEventList() {
		return array('PreAddComment', 'PostAddComment', 'PostDeleteComment', 'AdminPrePageHead');
	}

	function getTableList() {
		return array(sql_table('plug_moderate_queue'));
	}

	function init() {
		global $manager;
		
		$language = ereg_replace('[\\|/]', '', getLanguageName());
		
		if (file_exists($this->getDirectory() . 'language/' . $language . '.php'))
			include_once($this->getDirectory() . 'language/' . $language . '.php');
		else
			include_once($this->getDirectory() . 'language/english.php');
	}
	
	function install() {
	    $this->createOption('Moderate', _MODERATE_SETUP_MODERATE, 'select', 'all', _MODERATE_SETUP_ALL_ITEMS.'|all|'._MODERATE_SETUP_OLDER_7DAYS.'|7|'._MODERATE_SETUP_OLDER_14DAYS.'|14|'._MODERATE_SETUP_OLDER_1MONTH.'|30|'._MODERATE_SETUP_NO_ITEMS.'|none');
		$this->createOption('SpamAutoApprove', _MODERATE_SETUP_SPAMAUTOAPPROVE, 'yesno', 'no');
		$this->createOption('DeleteSpam', _MODERATE_SETUP_DELETESPAM, 'select', '14', _MODERATE_SETUP_7DAYS.'|7|'._MODERATE_SETUP_14DAYS.'|14|'._MODERATE_SETUP_1MONTH.'|30|'._MODERATE_SETUP_NEVER.'|never');
		$this->createOption('SecretWord', _MODERATE_SETUP_SECRETWORD, 'text', $this->secretWord());
		$this->createOption('DropTable', _MODERATE_SETUP_DROPTABLE, 'yesno', 'no');
		
		@sql_query('
			CREATE TABLE IF NOT EXISTS
				' . sql_table('plug_moderate_queue') . ' 
			(
				`qnumber` int(11) NOT NULL auto_increment,
				`qstatus` int(11),
				`qbody` text,
				`qplugin` varchar(100),
				`qmessage` varchar(255),
				`cnumber` int(11),
				`cbody` text,
				`cuser` varchar(40),
				`cmail` varchar(100),
				`cemail` varchar(100),
				`cmember` int(11),
				`citem` int(11),
				`ctime` datetime,
				`chost` varchar(60),
				`cip` varchar(15),
				`cblog` int(11),
				PRIMARY KEY  (`qnumber`)
			)
		');	
	}
	
	function unInstall() {
		if ($this->getOption('DropTable') == 'yes') {
			sql_query('DROP TABLE ' . sql_table('plug_moderate_queue'));
		}
	}	

	function secretWord() {
		mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);
		return substr(md5(mt_rand()), 0, 16);
	}

	
	/* Initialize variables */
	
	var $blocked = false;


	/* Handle events */
	
	function event_AdminPrePageHead(&$data) {
		$action = requestVar('action');
		
		if ($action == 'blogcommentlist' || $action == 'itemcommentlist') 
		{
			if (requestVar('markSpam') != '')
			{
				$this->_markSpam(requestVar('markSpam'));
			}
		}
		
		ob_start(array($this ,'PatchInterface'));
	}

	function PatchInterface($source) {
		$action = requestVar('action');
		
		if ($action == 'overview' || $action == 'login') 
		{
			if (preg_match('/(<th colspan=\')([0-9]+)(\'>([^<]+)<\/th><\/tr><\/thead>)/', $source, $matches)) 
			{
				$source = str_replace ($matches[0], $matches[1] . ($matches[2] + 1) . $matches[3], $source);
			}		
			
			if (preg_match_all('/<td><a href=\'index.php\?action=blogcommentlist&amp;blogid=([0-9]+)\' title=\'([^\']+)\'>([^<]+)<\/a><\/td>/', $source, $matches, PREG_SET_ORDER)) 
			{
				while (list(,$comment) = each($matches)) 
				{
					$replacement = '<td><a href=\'plugins/moderate/index.php?blogid=' .  $comment[1] . '\'>'._MODERATE_MODERATE.'</a></td>';
					$source = str_replace($comment[0], $replacement . $comment[0], $source);
				}
			}			
		}
		
		if ($action == 'itemlist')
		{
			if (preg_match_all('/<a href=\'index.php\?action=itemcommentlist&amp;itemid=([0-9]+)\'>([^<]+)<\/a><br \/>/', $source, $matches, PREG_SET_ORDER)) 
			{
				while (list(,$comment) = each($matches)) 
				{
					$replacement = '<a href=\'plugins/moderate/index.php?itemid=' . $comment[1] . '\'>'._MODERATE_MODERATE.'</a><br />';
					$source = str_replace($comment[0], $replacement . $comment[0], $source);
				}
			}			
		}
		
		if ($action == 'blogcommentlist' || $action == 'itemcommentlist') 
		{
			// Increase colspan
			if (preg_match('/(<th colspan=\')([0-9]+)(\'>([^<]+)<\/th><\/tr><\/thead>)/', $source, $matches)) 
			{
				$source = str_replace ($matches[0], $matches[1] . ($matches[2] + 1) . $matches[3], $source);
			}		
			
			if (preg_match_all('/<td><a href=\'index.php\?action=commentdelete&amp;commentid=([0-9]+)\'>([^<]+)<\/a><\/td>/', $source, $matches, PREG_SET_ORDER)) 
			{
				while (list(,$comment) = each($matches)) 
				{
					if ($this->_commentInQueue($comment[1])) 
					{
						if ($action == 'blogcommentlist')
							$replacement = '<td><a href=\'index.php?action=blogcommentlist&amp;blogid=' . requestVar('blogid') . '&amp;markSpam=' . $comment[1] . '\'>'._MODERATE_SPAM.'</a></td>';
						else
							$replacement = '<td><a href=\'index.php?action=itemcommentlist&amp;itemid=' . requestVar('itemid') . '&amp;markSpam=' . $comment[1] . '\'>'._MODERATE_SPAM.'</a></td>';
					}
					else
					{
						$replacement = '<td>&nbsp;</td>';
					}
					
					$source = str_replace($comment[0], $replacement . $comment[0], $source);
				}
			}
		}
		
		return $source;
	}	
	
	
	
	function event_PreAddComment(&$data) {
		global $manager, $member, $errormessage;
		
		if ($this->blocked == false)
		{
			/* Clean up Queue */
			$this->_cleanQueue();
			
			/* Get raw data... */
			$comment['itemid'] = intPostVar('itemid');
			$comment['user'] = postVar('user');
			$comment['userid'] = postVar('userid');
			$comment['email'] = postVar('email');
			$comment['body'] = postVar('body');
			
			$blogid = getBlogIDFromItemID($post['itemid']);
			$blog =& $manager->getBlog($blogid);
			
			$comment['timestamp'] = $blog->getCorrectTime();
			$comment['host'] = gethostbyaddr(serverVar('REMOTE_ADDR'));	
			$comment['ip'] = serverVar('REMOTE_ADDR');
			
			if ($member->isLoggedIn()) {
				$comment['memberid'] = $member->getID();
				$comment['user'] = '';
				$comment['userid'] = '';
				$comment['email'] = '';
			} else {
				$comment['memberid'] = 0;
			}
			
			/* Default status */
			$status = $this->_defaultStatus($data['comment']['itemid']);
			$plugin = '';
			$message = '';
			
			/* Process the SpamCheck information */
			if (isset($data['spamcheck']))
			{
				$spamcheck = & $data['spamcheck'];
				
				if (isset($spamcheck['result']))
				{
					if ($spamcheck['result'] == true)
					{
						$status = 2;
						
						if (isset($spamcheck['message']))
							$message = $spamcheck['message'];
						
						if (isset($spamcheck['plugin']))
							$plugin = $spamcheck['plugin'];
					}
					else
					{
						if ($this->getOption('SpamAutoApprove') == 'yes')
						{
							$status = 0;
						}
					}
				}
				else
				{
					$status = 1;
				}
			}
			
			sql_query('
				INSERT INTO 
					' . sql_table('plug_moderate_queue') . ' 
				SET
					qstatus = "' . intval($status) . '",
					qbody = "' . addslashes($comment['body']) . '",
					qplugin = "' . addslashes($plugin) . '",
					qmessage = "' . addslashes($message) . '",
					cuser = "' . addslashes($comment['user']) . '",
					cmail = "' . addslashes($comment['userid']) . '",
					cemail = "' . addslashes($comment['email']) . '",
					cmember = "' . intval($comment['memberid']) . '",
					cbody = "' . addslashes($comment['body']) . '",
					citem = "' . intval($comment['itemid']) . '",
					ctime = "' . date('Y-m-d H:i:s', $comment['timestamp']) . '",
					chost = "' . addslashes($comment['host']) . '",
					cip = "' . addslashes($comment['ip']) . '",
					cblog = "' . getBlogIDFromItemID($comment['itemid']) . '"
			');			
			
			$this->id = mysql_insert_id();
			
			if ($status > 0) {
				$errormessage = _MODERATE_QUEUED;
				
				unset($_REQUEST['action']);
				unset($_POST['action']);
				unset($_GET['action']);
				
				unset($_REQUEST['body']);
				unset($_POST['body']);
				unset($_GET['body']);
				
				selector();
				exit;
			}
		}
	}
	
	function event_PostAddComment(&$data) {
		if ($this->blocked == false)
		{
			sql_query('
				UPDATE
					' . sql_table('plug_moderate_queue') . ' 
				SET
					cnumber = "' . intval($data['commentid']) . '"
				WHERE
					qnumber = "' . intval($this->id) . '"
			');
		}
	}
	
	function event_PostDeleteComment(&$data) {
		sql_query('
			DELETE FROM
				' . sql_table('plug_moderate_queue') . ' 
			WHERE
				cnumber = ' . addslashes($data['commentid']) . '
		');	
	}
	
	function doAction($type)
	{
		global $CONF;
		
		if (requestVar('sw') != $this->getOption('SecretWord'))
			exit;
		
		switch ($type) {
			
			case 'spam':
				$qnumber = requestVar('qnumber');
				$this->_markQueue($qnumber);
				
				sendContentType('text/html', 'admin-moderate', _CHARSET);	
				include ($this->getDirectory() . '/templates/approved.html');
				break;
			
			case 'approve':
				$qnumber = requestVar('qnumber');
				$this->_approveQueue($qnumber);
				
				sendContentType('text/html', 'admin-moderate', _CHARSET);	
				include ($this->getDirectory() . '/templates/approved.html');
				break;
			
			case 'details':
				$qnumber = requestVar('qnumber');
			
				$res = sql_query('
					SELECT 
						*
					FROM
						' . sql_table('plug_moderate_queue') . ' 
					WHERE
						qnumber = ' . intval($qnumber) . '
				');
				
				if ($comment = mysql_fetch_array($res)) 
				{
					$comment['ctime'] = strtotime($comment['ctime']);
					$sw = $this->getOption('SecretWord');
					
					$member = new MEMBER();
					$member->readFromID($comment['cmember']);
					
					sendContentType('text/html', 'admin-moderate', _CHARSET);	
					include ($this->getDirectory() . '/templates/approve.html');
				}
				
				break;
			
			default:
				$res = sql_query('
					SELECT 
						*
					FROM
						' . sql_table('plug_moderate_queue') . ' 
					WHERE
						qstatus = 1
					ORDER BY
						ctime DESC
					LIMIT
						20
				');				
				
				header('Content-Type: text/xml; charset=UTF-8');
				echo "<","?xml version='1.0' encoding='UTF-8'?",">\n";

				echo "<rss version='2.0'>";
				echo "<channel>";
				echo "<title>" . htmlspecialchars(_MODERATE_QUEUE) . "</title>";
				echo "<description></description>";
				echo "<link>" . $CONF['ActionURL'] . "?action=plugin&amp;name=Moderate&amp;sw=" . $this->getOption('SecretWord') . "</link>";
				
				while ($row = mysql_fetch_array($res)) {
				
					echo "<item>";
					echo "<title>";
					echo htmlspecialchars(shorten(strip_tags($row['cuser']), 20, '...')) . ' ' . htmlspecialchars(_MODERATE_WROTE) . ' ';
					echo htmlspecialchars(shorten(strip_tags($row['cbody']), 80, '...'));
					echo "</title>";
					echo "<link>" . $CONF['ActionURL'] . '?action=plugin&amp;name=Moderate&amp;type=details&amp;qnumber='.$row['qnumber'] . "&amp;sw=" . $this->getOption('SecretWord') . "</link>";
					echo "</item>";
					echo "<description><![CDATA[";
					echo "<p>";
					echo '<b>' . htmlspecialchars(strip_tags($row['cuser'])) . ' ' . htmlspecialchars(_MODERATE_WROTE) . '</b> ';
					echo htmlspecialchars($row['cbody']);
					echo "</p>";
					echo "<p>";
					echo _COMMENTFORM_EMAIL . ' ' . htmlspecialchars($row['cemail']) . "<br />";
					echo _COMMENTFORM_MAIL . ' ' . htmlspecialchars($row['cmail']);
					echo "</p>";
					echo "<p>";
					echo "<a href='" . $CONF['ActionURL'] . '?action=plugin&amp;name=Moderate&amp;type=details&amp;qnumber='.$row['qnumber'] . "&amp;sw=" . $this->getOption('SecretWord') . "'>" . _MODERATE_DETAILS . "</a> ";
					echo "<a href='" . $CONF['ActionURL'] . '?action=plugin&amp;name=Moderate&amp;type=spam&amp;qnumber='.$row['qnumber'] . "&amp;sw=" . $this->getOption('SecretWord') . "'>" . _MODERATE_SPAM . "</a> ";
					echo "<a href='" . $CONF['ActionURL'] . '?action=plugin&amp;name=Moderate&amp;type=approve&amp;qnumber='.$row['qnumber'] . "&amp;sw=" . $this->getOption('SecretWord') . "'>" . _MODERATE_APPROVE . "</a> ";
					echo "</p>";
					echo "]]></description>";
				}
				
				echo "</channel>";
				echo "</rss>";
				break;
		} 
		
		exit;
	} 
		
	function _defaultStatus($itemid) {
		
		switch($this->getOption('Moderate'))
		{
			case 'all':
				return 1;
				break;
			
			case 'none':
				return 0;
				break;
			
			default:
				$res = sql_query('
					SELECT 
						*
					FROM
						' . sql_table('item') . ' 
					WHERE
						inumber = ' . intval($itemid) . ' AND
						DATE_SUB(CURDATE(),INTERVAL ' . intval($this->getOption('Moderate')) . ' DAY) <= itime
				');
				
				if ($row = mysql_fetch_array($res))
					return 0;
				else
					return 1;
				
				break;
		}
	}
	
	function _commentInQueue($cnumber) {
		
		$res = sql_query('
			SELECT 
				*
			FROM
				' . sql_table('plug_moderate_queue') . ' 
			WHERE
				cnumber = ' . intval($cnumber) . '
		');	
		
		if ($row = mysql_fetch_array($res)) {
			return true;		
		}
		
		return false;
	}
	
	function _markEvent($qnumber, $result) {
		global $manager;
		
		$res = sql_query('
			SELECT 
				*
			FROM
				' . sql_table('plug_moderate_queue') . ' 
			WHERE
				qnumber = ' . intval($qnumber) . '
		');	
		
		if ($row = mysql_fetch_array($res))
		{
			$spammark = array (
				'type'  	=> 'comment',
				'body'		=> $row['cbody'],
				'id'        	=> $row['citem'],
				'result'	=> $result
			);
				
			if ($row['cmember'] > 0) 
			{
				$member = new MEMBER();
				$member->readFromID($comment['memberid']);
				
				if ($member->email != '') {
					$spammark['author'] = $member->displayname;
					$spammark['email'] = $member->email;
				}
			}
			else
			{
				$spammark['author'] = $row['cuser'];
				$spammark['email'] = $row['cemail'];
				$spammark['url'] = $row['cmail'];
			}
			
			$manager->notify('SpamMark', array ('spammark' => & $spammark));
		}
	}
	
	function _markSpam($cnumber) {
		
		/* SpamMark event */
		$this->_markEvent($this->_retrieveQueueId($cnumber), true);
		
		/* Update status */
		sql_query('
			UPDATE
				' . sql_table('plug_moderate_queue') . ' 
			SET
				cnumber = NULL,
				qstatus = 2,
				qplugin = "' . addslashes($this->getName()) . '",
				qmessage = "' . addslashes(_MODERATE_MANUALLY_MARKED) . '"
			WHERE
				cnumber = "' . intval($cnumber) . '"
		');
		
		/* Delete comment */
		sql_query('
			DELETE FROM
				' . sql_table('comment') . ' 
			WHERE
				cnumber = ' . intval($cnumber) . '
		');	
	}
	
	function _retrieveQueueId($cnumber) {
		
		$res = sql_query('
			SELECT
				qnumber
			FROM
				' . sql_table('plug_moderate_queue') . ' 
			WHERE
				cnumber = ' . intval($cnumber) . '
		');
		
		if ($row = mysql_fetch_array($res)) {
			return $row['qnumber'];
		}
		
		return 0;
	}
	
	function _retrieveID($qnumber) {
		
		$res = sql_query('
			SELECT
				citem
			FROM
				' . sql_table('plug_moderate_queue') . ' 
			WHERE
				qnumber = ' . intval($qnumber) . '
		');
		
		if ($row = mysql_fetch_array($res)) {
			return $row['citem'];
		}
		
		return 0;
	}
	
	function _markQueue($qnumber) {
		
		/* SpamMark event */
		$this->_markEvent($qnumber, true);
		
		sql_query('
			UPDATE
				' . sql_table('plug_moderate_queue') . ' 
			SET
				qstatus = 2,
				qplugin = "' . addslashes($this->getName()) . '",
				qmessage = "' . addslashes(_MODERATE_MANUALLY_MARKED) . '"
			WHERE
				qnumber = "' . intval($qnumber) . '"
		');
	}

	function _cleanQueue() {
		
		if ($this->getOption('DeleteSpam') != 'never')
		{
			sql_query('
				DELETE FROM
					' . sql_table('plug_moderate_queue') . ' 
				WHERE
					qstatus = 2 AND
					DATE_SUB(CURDATE(),INTERVAL ' . intval($this->getOption('DeleteSpam')) . ' DAY) > ctime
			');
		}
	}
	
	function _deleteQueue($qnumber) {
		
		sql_query('
			DELETE FROM
				' . sql_table('plug_moderate_queue') . ' 
			WHERE
				qnumber = ' . intval($qnumber) . '
		');	
	}

	function _updateQueue($qnumber, $cbody, $cmail, $cemail, $cuser) {
		
		sql_query('
			UPDATE
				' . sql_table('plug_moderate_queue') . ' 
			SET
				cbody = "' . addslashes($cbody) . '",
				cmail = "' . addslashes($cmail) . '",
				cemail = "' . addslashes($cemail) . '",
				cuser = "' . addslashes($cuser) . '"
			WHERE
				qnumber = "' . intval($qnumber) . '"
		');
	}

	function _approveQueue($qnumber) {
		
		global $manager;
		
		$res = sql_query('
			SELECT 
				*
			FROM
				' . sql_table('plug_moderate_queue') . ' 
			WHERE
				qnumber = ' . intval($qnumber) . '
		');	
		
		if ($row = mysql_fetch_array($res)) {
			
			/* SpamMark event */
			if ($row['qstatus'] == 2) {
				$this->_markEvent($qnumber, false);
			}
			
			/* Build comment data structure */
			$comment = array (
				'itemid' => $row['citem'],
				'user' => $row['cuser'],
				'userid' => $row['cmail'],
				'email' => $row['cemail'],
				'body' => $row['cbody'],
				'timestamp' => strtotime($row['ctime']),
				'host' => $row['chost'],
				'ip' => $row['cip'],
				'memberid' => $row['cmember']
			);
			
			/* Fake spamcheck structure */
			$spamcheck = array (
				'result' => false
			);
			
			/* Block our own event listeners from picking this up */
			$this->blocked = true;
			
			/* Emulate PreAddComment event */
			$manager->notify('PreAddComment', array('comment' => &$comment, 'spamcheck' => &$spamcheck));
			
			/* Insert into database */
			sql_query('
				INSERT INTO 
					' . sql_table('comment') . ' 
				SET
					cuser = "' . addslashes($comment['user']) . '",
					cmail = "' . addslashes($comment['userid']) . '",
					cemail = "' . addslashes($comment['email']) . '",
					cmember = "' . intval($comment['memberid']) . '",
					cbody = "' . addslashes($comment['body']) . '",
					citem = "' . intval($comment['itemid']) . '",
					ctime = "' . date('Y-m-d H:i:s', $comment['timestamp']) . '",
					chost = "' . addslashes($comment['host']) . '",
					cip = "' . addslashes($comment['ip']) . '",
					cblog = "' . addslashes($row['cblog']) . '"
			');
			
			$cnumber = mysql_insert_id();
			
			sql_query('
				UPDATE
					' . sql_table('plug_moderate_queue') . ' 
				SET
					cnumber = "' . intval($cnumber) . '",
					qstatus = 0,
					qplugin = "",
					qmessage = ""
				WHERE
					qnumber = "' . intval($qnumber) . '"
			');
			
			/* Emulate PostAddComment event */
			$manager->notify('PostAddComment',array('comment' => &$comment, 'commentid' => &$cnumber, 'spamcheck' => &$spamcheck));
			
			/* Turn off event block */
			$this->blocked = false;
		}
	}
}

?>