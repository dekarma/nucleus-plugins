<?php

   /* ==========================================================================================
    * Moderate for Nucleus CMS
    * Copyright 2005, Niels Leenheer
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


	$strRel = '../../../'; 
	include($strRel . 'config.php');
	include($DIR_LIBS . 'PLUGINADMIN.php');
	
	
	// Send out Content-type
	sendContentType('application/xhtml+xml', 'admin-moderate', _CHARSET);	

	// Compatiblity with Nucleus < = 2.0
	if (!function_exists('sql_table')) { function sql_table($name) { return 'nucleus_' . $name; } }
	
	
	// Initialize plugin
	$oPluginAdmin = new PluginAdmin('Moderate');


	// Set main IDs
	if (intRequestVar('itemid') > 0) {
		$itemid = intRequestVar('itemid');
		$blogid = getBlogIdFromItemId($itemid);
	}
	
	elseif (intRequestVar('blogid') > 0) {
		$itemid = 0;
		$blogid = intRequestVar('blogid');
	}
	
	elseif (intRequestVar('qnumber') > 0) {
		$itemid = $oPluginAdmin->plugin->_retrieveID($qnumber);
		$blogid = getBlogIdFromItemId($itemid);
	}
	
	else {
		$itemid = 0;
		$blogid = 0;
	}
	
	
	if (!($member->isLoggedIn() && $member->canLogin() &&
		 ($member->isAdmin() || $member->isBlogAdmin($blogid))))
	{
		$oPluginAdmin->start();
		echo '<p>' . _ERROR_DISALLOWED . '</p>';
		$oPluginAdmin->end();
		exit;
	}
	
	$oPluginAdmin->start();
	$action = requestVar('action');
	
	switch($action) {
		
		case 'update':
			$qnumber = requestVar('qnumber');
			
			if (requestVar('button') == _MODERATE_APPROVE) {
				$oPluginAdmin->plugin->_updateQueue($qnumber, requestVar('cbody'), requestVar('cmail'), requestVar('cemail'), requestVar('cuser'));
				$oPluginAdmin->plugin->_approveQueue($qnumber);
				$action = 'moderate';
			}
				
			if (requestVar('button') == _LISTS_DELETE) {
				$oPluginAdmin->plugin->_deleteQueue($qnumber);
				$action = 'moderate';
			}
			
			break;
		
		case 'approve':
			$qnumber = requestVar('qnumber');
			$oPluginAdmin->plugin->_approveQueue($qnumber);
			$action = 'moderate';
			break;
			
		case 'delete':
			$qnumber = requestVar('qnumber');
			$oPluginAdmin->plugin->_deleteQueue($qnumber);
			$action = 'moderate';
			break;
		
		case 'mark':
			$qnumber = requestVar('qnumber');
			$oPluginAdmin->plugin->_markQueue($qnumber);
			$action = 'moderate';
			break;
			
		case 'batchmoderate':
			$batch = requestVar('batch');
			
			switch (requestVar('batchaction')) {
				
				case 'approve':
					while (list(,$qnumber) = each ($batch))
						$oPluginAdmin->plugin->_approveQueue($qnumber);
					break;
				
				case 'mark':
					while (list(,$qnumber) = each ($batch))
						$oPluginAdmin->plugin->_markQueue($qnumber);
					break;
				
				case 'delete':
					while (list(,$qnumber) = each ($batch))
						$oPluginAdmin->plugin->_deleteQueue($qnumber);
					break;
				
				default:
					break;
			}
			
			$action = 'moderate';
			break;
		
		default:
			break;
	}


	switch($action) {
		
		case 'edit':
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
				
				echo '<h2>' . _EDITC_TITLE . '</h2>';
				echo '<form action="plugins/moderate/index.php" method="post">';
				
				echo '<div>';
				echo '<input type="hidden" name="action" value="update" />';
				$manager->addTicketHidden();
				
				echo '<input type="hidden" name="qnumber" value="' . $qnumber . '" />';
				echo '<input type="hidden" name="blogid" value="' . $blogid . '" />';
				echo '<input type="hidden" name="itemid" value="' . $itemid . '" />';
				
				echo '<table><tr><th colspan="2">' . _EDITC_TITLE . '</th></tr>';
				echo '<tr><td>' . _EDITC_WHEN . '</td><td>' . date("Y-m-d@H:i", $comment['ctime']) . '</td></tr>';
				
				if ($comment['chost'] != $comment['cip'])
					echo '<tr><td>' . _EDITC_HOST . '</td><td>' . htmlspecialchars($comment['chost'], ENT_QUOTES) . ' (' . htmlspecialchars($comment['cip'], ENT_QUOTES) . ')</td></tr>';
				else
					echo '<tr><td>' . _EDITC_HOST . '</td><td>' . htmlspecialchars($comment['chost'], ENT_QUOTES) . '</td></tr>';
				
				echo '<tr><td>' . _EDITC_TEXT . '</td><td><textarea name="cbody" tabindex="10" rows="10" cols="50">' . htmlspecialchars($comment['cbody'], ENT_QUOTES) . '</textarea></td></tr>';
				
				if ($comment['cmember'])
				{
					echo '<tr><td>' . _EDITC_WHO . '</td><td>' . htmlspecialchars($member->displayname, ENT_QUOTES) . ' (' . _EDITC_MEMBER . ')</td></tr>';
					echo '<tr><td>' . _COMMENTFORM_EMAIL . '</td><td>' . htmlspecialchars($member->email, ENT_QUOTES) . '</td></tr>';
					echo '<tr><td>' . _COMMENTFORM_MAIL . '</td><td>' . htmlspecialchars($member->url, ENT_QUOTES) . '</td></tr>';
				}
				else
				{
					echo '<tr><td>' . _EDITC_WHO . '</td><td><input type="text" name="cuser" size="60" value="' . htmlspecialchars($comment['cuser'], ENT_QUOTES) . '" /></td></tr>';
					echo '<tr><td>' . _COMMENTFORM_EMAIL . '</td><td><input type="text" name="cemail" size="60" value="' . htmlspecialchars($comment['cemail'], ENT_QUOTES) . '" /></td></tr>';
					echo '<tr><td>' . _COMMENTFORM_MAIL . '</td><td><input type="text" name="cmail" size="60" value="' . htmlspecialchars($comment['cmail'], ENT_QUOTES) . '" /></td></tr>';
				}
				
				echo '<tr><td>' . _LISTS_ACTIONS . '</td><td>';
				echo '<input type="submit" name="button" tabindex="20" value="' . _MODERATE_APPROVE . '" onclick="return checkSubmit();" /> ';
				echo '<input type="submit" name="button" tabindex="21" value="' . _LISTS_DELETE . '" onclick="return checkSubmit();" />';
				echo '</td></tr>';
				
				echo '</table>';
				echo '</div></form>';
			}
			
			break;
			
		case 'moderate':
		default:
			if ($itemid == 0 && $blogid == 0) {
			}
			elseif ($itemid == 0) {
				$blog =& $manager->getBlog($blogid);
				echo '<p><a href="index.php?action=overview">(',_BACKHOME,')</a></p>';
				echo '<h2>' . _MODERATE_QUEUE_FOR_BLOG . ' ';
				echo '<a href="'.htmlspecialchars($blog->getURL()).'" title="'._BLOGLIST_TT_VISIT.'">'.$blog->getName() .'</a>';
				echo '</h2>';
			}
			else {
				echo '<p><a href="index.php?action=itemlist&amp;blogid='.$blogid.'">(',_BACKTOOVERVIEW,')</a></p>';
				echo '<h2>' . _MODERATE_QUEUE . '</h2>';
			}
			
			if (requestVar('status'))
				$status = intRequestVar('status');
			else
				$status = 0;
			
			if (requestVar('start'))
				$start = intRequestVar('start');
			else
				$start = 0; 	
			
			if (requestVar('amount'))
				$amount = intRequestVar('amount');
			else
				$amount = 50;	
			
			$search = requestVar('search');
			
			$query = '
				SELECT 
					*
				FROM
					' . sql_table('plug_moderate_queue') . ' 
				WHERE
					qstatus > 0
			';
			
			if ($blogid > 0)
                $query .= ' AND cblog = ' . $blogid . ' ';
			
			if ($itemid > 0)
                $query .= ' AND citem = ' . $itemid . ' ';
			
			if ($status > 0)
				$query .= ' AND qstatus = ' . $status . ' ';
			
			if ($search) 
				$query .= ' AND cbody LIKE "%' . addslashes($search) . '%"';
			
			$query .= '
				ORDER BY
					ctime DESC
				LIMIT
					' . $start . ', ' . $amount . '
			';
			
			
			$url = "plugins/moderate/index.php?action=moderate&amp;status=1&amp;blogid=".$blogid."&amp;itemid=".$itemid."&amp;amount=".$amount."&amp;search=".urlencode($search);
			
			if ($status == 1)
				echo "<a href='".$url."'><u>"._MODERATE_COMMENTS."</u></a> | ";
			else
				echo "<a href='".$url."'>"._MODERATE_COMMENTS."</a> | ";
			
			$url = "plugins/moderate/index.php?action=moderate&amp;status=2&amp;blogid=".$blogid."&amp;itemid=".$itemid."&amp;amount=".$amount."&amp;search=".urlencode($search);
			
			if ($status == 2)
				echo "<a href='".$url."'><u>"._MODERATE_SPAM."</u></a> | ";
			else
				echo "<a href='".$url."'>"._MODERATE_SPAM."</a> | ";
			
			$url = "plugins/moderate/index.php?action=moderate&amp;status=0&amp;blogid=".$blogid."&amp;itemid=".$itemid."&amp;amount=".$amount."&amp;search=".urlencode($search);
			
			if ($status == 0)
				echo "<a href='".$url."'><u>"._MODERATE_BOTH."</u></a>";
			else
				echo "<a href='".$url."'>"._MODERATE_BOTH."</a>";
			
			
			ob_start();
			
			$template['content'] = 'moderate';
			$template['blogid'] = $blogid;
			$template['itemid'] = $itemid;
			$template['amount'] = $amount;
			$template['start'] = $start;
			$template['search'] = $search;
			$template['status'] = $status;
			
			if (getNucleusVersion() >= 330)
				$manager->loadClass("ENCAPSULATE");
			
			$navList =& new NAVLIST('moderate', $start, $amount, 0, 1000, $blogid, $search, $itemid);
			$navList->showBatchList('moderate', $query, 'table', $template, '<p>' . _NOCOMMENTS . '</p>');
			
			
			/* Patch interface */
			$buffer = ob_get_contents();
			ob_end_clean();
			
			$buffer = str_replace(
				'<form method="post" action="index.php">',
				'<form method="post" action="plugins/moderate/index.php">',
				$buffer
			);
			
			$buffer = str_replace(
				'<select name="batchaction">',
				'<select name="batchaction"><option value="delete">'._BATCH_ITEM_DELETE.'</option><option value="approve">'._MODERATE_APPROVE.'</option><option value="approve">'._MODERATE_NOT_SPAM.'</option><option value="mark">'._MODERATE_SPAM.'</option>',
				$buffer
			);
			
			$buffer = str_replace(
				'<input type="submit" value="' . _BATCH_EXEC . '" />',
				'<input type="hidden" name="blogid" value="' . $blogid . '" /><input type="hidden" name="itemid" value="' . $itemid . '" /><input type="submit" value="' . _BATCH_EXEC . '" />',
				$buffer
			);
			
			echo $buffer;
			break;
	}

	$oPluginAdmin->end();	



	/* Functions */

	function listplug_table_moderate($template, $type) {
		switch($type) {
			case 'HEAD':
				echo "<th>"._LISTS_INFO."</th><th>"._LIST_COMMENT."</th><th colspan='4'>"._LISTS_ACTIONS."</th>";
				break;
			case 'BODY':
				$current = $template['current'];
				$current->ctime = strtotime($current->ctime);	// string -> unix timestamp
				
				echo '<td>';
				echo date("Y-m-d@H:i", $current->ctime);
				echo '<br />';
				
				if ($current->qstatus == '2')
					echo '<span style="color: #aaa">';
				
				if ($current->mname)
					echo htmlspecialchars($current->mname) ,' ', _LIST_COMMENTS_MEMBER;
				else
					echo htmlspecialchars($current->cuser);
				
				if ($current->qstatus == '2')
					echo '</span>';
				
				echo '</td>';
				
				$current->cbody = strip_tags($current->cbody);
				$current->cbody = htmlspecialchars(shorten($current->cbody, 300, '...'));
				
				if ($current->qstatus == '2')
					echo '<td style="color: #aaa;">';
				else
					echo '<td>';
				
				$id = listplug_nextBatchId();			
				echo '<input type="checkbox" id="batch',$id,'" name="batch[',$id,']" value="',$current->qnumber,'" />';
				echo '<label for="batch',$id,'">';
				echo $current->cbody;
				
				if ($current->qstatus == '2')
				{
					if ($current->qplugin != '' || $current->qmessage != '')
						echo "<br /><br /><span style='color: #666;'><em>";
					
					if ($current->qplugin != '')
						echo "<strong>" . htmlspecialchars($current->qplugin, ENT_QUOTES) . ":</strong> ";
					
					if ($current->qmessage != '')
						echo htmlspecialchars($current->qmessage, ENT_QUOTES);
					
					if ($current->qplugin != '' || $current->qmessage != '')
						echo "</em></span>";
				}
				
				echo '</label>';
				echo '</td>';
				
				if ($current->qstatus == '1')
					echo "<td><a href='plugins/moderate/index.php?action=approve&amp;qnumber=".$current->qnumber.
					     "&amp;blogid=".$template['blogid']."&amp;itemid=".$template['itemid'].
					     "&amp;amount=".$template['amount']."&amp;start=".$template['start'].
					     "&amp;status=".$template['status'].
					     "&amp;search=".urlencode($template['search'])."'>"._MODERATE_APPROVE."</a></td>";
				else
					echo "<td>&nbsp;</td>";
				
				if ($current->qstatus == '2')
					echo "<td><a href='plugins/moderate/index.php?action=approve&amp;qnumber=".$current->qnumber.
					     "&amp;blogid=".$template['blogid']."&amp;itemid=".$template['itemid'].
					     "&amp;amount=".$template['amount']."&amp;start=".$template['start'].
					     "&amp;status=".$template['status'].
					     "&amp;search=".urlencode($template['search'])."'>"._MODERATE_NOT_SPAM."</a></td>";
				else				
					echo "<td><a href='plugins/moderate/index.php?action=mark&amp;qnumber=".$current->qnumber.
					     "&amp;blogid=".$template['blogid']."&amp;itemid=".$template['itemid'].
					     "&amp;amount=".$template['amount']."&amp;start=".$template['start'].
					     "&amp;status=".$template['status'].
					     "&amp;search=".urlencode($template['search'])."'>"._MODERATE_SPAM."</a></td>";
				
				echo "<td><a href='plugins/moderate/index.php?action=edit&amp;qnumber=".$current->qnumber.
				     "&amp;blogid=".$template['blogid']."&amp;itemid=".$template['itemid'].
					 "'>"._LISTS_EDIT."</a></td>";
				
				echo "<td><a href='plugins/moderate/index.php?action=delete&amp;qnumber=".$current->qnumber.
				     "&amp;blogid=".$template['blogid']."&amp;itemid=".$template['itemid'].
				     "&amp;amount=".$template['amount']."&amp;start=".$template['start'].
				     "&amp;status=".$template['status'].
				     "&amp;search=".urlencode($template['search'])."'>"._LISTS_DELETE."</a></td>";
				
				break;
		}
	}
	
?>