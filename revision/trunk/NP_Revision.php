<?php

   /* ==========================================================================================
	* Revision 0.7 for Nucleus CMS 
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


	// Compatiblity with Nucleus < = 2.0
	if (!function_exists('sql_table')) { function sql_table($name) { return 'nucleus_' . $name; } }

	
	class NP_Revision extends NucleusPlugin {

		var $blogtype;
		var $currentbase;
		var $currentsub;
		var $warningshown;
		var $revertinprogress;

		function getName()   	  { 		return 'Revision';   }
		function getAuthor() 	  { 		return 'rakaz'; }
		function getURL()    	  { 		return 'http://www.rakaz.nl/'; }
		function getVersion()	  { 		return '0.7'; }
		function getDescription() { 		return 'Store and access old revisions of stories.'; }
	
		function getTableList()   { 		return array(sql_table("plugin_revision")); }
		function getEventList()   { 		return array('PreAddItem', 'PostAddItem', 'PreUpdateItem', 'PreDeleteItem', 'PreDeleteBlog', 'PreMoveItem', 'EditItemFormExtras', 'PreItem', 'PreBlogContent'); }
		function getMinNucleusVersion() {	return 300; }
	
		function supportsFeature($feature) {
			switch($feature) {
				case 'SqlTablePrefix':
					return 1;
				default:
					return 0;
			}
		}
	
		function install() {
			$this->createOption('showWarning',  'Show a warning on older revisions', 'yesno', 'yes');
			$this->createOption('warningText',  'Warning', 'textarea', "<p class='warning'>The text below is revision <%current%> of this story. The story may have been updated with new information, or errors and omissions may have been corrected. The latest version is <a href='<%lasturl%>'>revision <%last%></a>. You can also see a <a href='<%lastdiff%>'>diff of the changes</a> between this and the latest revision.</p>");
			$this->createOption('dateFormat',  'Date format', 'text', "%e/%m/%g");
			$this->createOption('DropTable',   'Clear the database when uninstalling','yesno','no');

			/* Create tables */
			mysql_query("
				CREATE TABLE IF NOT EXISTS 
					".sql_table('plugin_revision')."
				(
					`inumber`   INT(11)         NOT NULL,
					`ititle`    VARCHAR(160), 
					`ibody`     TEXT			NOT NULL, 
					`imore`     TEXT, 
					`iblog`     INT(11)			NOT NULL, 
					`iauthor`   INT(11)			NOT NULL, 
					`itime`   	DATETIME		NOT NULL, 
					`iclosed`   TINYINT(2)		NOT NULL, 
					`idraft`    TINYINT(2)		NOT NULL, 
					`icat`    	INT(11)			NOT NULL,
					`rrevbase`	INT(11)			NOT NULL,
					`rrevsub` 	INT(11)			NOT NULL,
					`rtime`		DATETIME		NOT NULL,
					`rcomment`	VARCHAR(160),
					
					UNIQUE KEY inumber_revision (`inumber`,`rrevbase`,`rrevsub`)
				)
			");
		}
	
		function uninstall() {
			if ($this->getOption('DropTable') == 'yes') {
	 			mysql_query ('DROP TABLE '.sql_table('plugin_revision'));
			}
		}
		
		function init() {
			$this->revertinprogress = false;
		}
		




		function eval_Current($itemid) {
			if ($this->currentbase != '' && $this->currentsub != '') 
			{
				return $this->currentbase . '.' . $this->currentsub;
			} 
			else 
			{
				$last = $this->lastRevision($itemid);
				
				if ($last) 
				{
					list($base, $sub) = $last;
					return $base . '.' . $sub;
				}
				else
				{
					return '1.0';
				}
			}
		}
		
		function eval_CurrentDate($itemid, $date) {
			global $blog;

			if ($this->currentbase != '' && $this->currentsub != '') 
			{
				$lastbase = $this->currentbase;
				$lastsub  = $this->currentsub;

				$res  = mysql_query ('SELECT UNIX_TIMESTAMP(rtime) AS time FROM '.sql_table('plugin_revision').' WHERE inumber = ' . $itemid . ' AND rrevbase = ' . $lastbase . ' AND rrevsub = ' . $lastsub);
			}
			else
			{
				$last = $this->lastRevision($itemid);
				
				if ($last)
				{
					list($lastbase, $lastsub) = $last;
					$res  = mysql_query ('SELECT UNIX_TIMESTAMP(rtime) AS time FROM '.sql_table('plugin_revision').' WHERE inumber = ' . $itemid . ' AND rrevbase = ' . $lastbase . ' AND rrevsub = ' . $lastsub);
				}
				else
				{
					$res  = mysql_query ('SELECT UNIX_TIMESTAMP(itime) AS time FROM '.sql_table('item').' WHERE inumber = ' . $itemid);
				}
			}
			
			if ($row = mysql_fetch_array($res)) {
				return htmlspecialchars(strftime($date, $row['time'] + ($blog->getTimeOffset() * 3600)), ENT_QUOTES);
			}
		}

		function eval_Last($itemid) {
			$last = $this->lastRevision($itemid);
				
			if ($last) 
			{
				list($base, $sub) = $last;
				return $base . '.' . $sub;
			}
			else
			{
				return '1.0';
			}
		}
		
		function eval_LastDate ($itemid, $date) {
			global $blog;

			$last = $this->lastRevision($itemid);

			if ($last)
			{
				list($lastbase, $lastsub) = $last;
				$res  = mysql_query ('SELECT UNIX_TIMESTAMP(rtime) AS time FROM '.sql_table('plugin_revision').' WHERE inumber = ' . $itemid . ' AND rrevbase = ' . $lastbase . ' AND rrevsub = ' . $lastsub);
			}
			else
			{
				$res  = mysql_query ('SELECT UNIX_TIMESTAMP(itime) AS time FROM '.sql_table('item').' WHERE inumber = ' . $itemid);
			}
			
			if ($row = mysql_fetch_array($res)) {
				return htmlspecialchars(strftime($date, $row['time'] + ($blog->getTimeOffset() * 3600)), ENT_QUOTES);
			}
		}
		
		function eval_Permalink ($itemid, $revision) {
			global $CONF;
			
			if ($CONF['URLMode'] == 'pathinfo') 
			{
				return $CONF["IndexURL"] . 'item/' . $itemid . '/revision/' . $revision;
			} 
			else 
			{
				return $CONF["IndexURL"] . 'index.php?itemid=' . $itemid . '&amp;rev=' . $revision;
			}
		}

		function doSkinVar() {
			global $CONF;
		
			$parameters = func_get_args();
			$skinType = array_shift($parameters);
			
			if (count($parameters))
				$what = array_shift($parameters);
			else
				$what = '';

			if (count($parameters))
				$itemid = array_shift($parameters);
			else
				$itemid = 0;

		
			if ($what == 'current') 
				echo $this->eval_Current($itemid);
			
			if ($what == 'currentdate') 
			{
				if (count($parameters))
					$date = array_shift($parameters);
				else
					$date = $this->getOption('dateFormat');
					
				echo $this->eval_CurrentDate($itemid, $date);
			}

			if ($what == 'currenturl')
				echo $this->eval_Permalink($itemid, $this->eval_Current($itemid));
			
			if ($what == 'currentdiff') 
				echo $CONF['ActionURL'] . '?action=plugin&amp;name=Revision&amp;type=diff&amp;itemid=' . $itemid . '&amp;old=' . $this->eval_Current($itemid) . '&amp;new=' . $this->eval_Current($itemid);

			if ($what == 'last') 
				echo $this->eval_Last($itemid);
			
			if ($what == 'lastdate') 
			{
				if (count($parameters))
					$date = array_shift($parameters);
				else
					$date = $this->getOption('dateFormat');
					
				echo $this->eval_LastDate($itemid, $date);
			}				
				
			if ($what == 'lasturl')
				echo $this->eval_Permalink($itemid, $this->eval_Last($itemid));
			
			if ($what == 'lastdiff') 
				echo $CONF['ActionURL'] . '?action=plugin&amp;name=Revision&amp;type=diff&amp;itemid=' . $itemid . '&amp;old=' . $this->eval_Current($itemid) . '&amp;new=' . $this->eval_Last($itemid);
		}
		
		function doTemplateVar(&$item, $what = '') {
			$parameters = func_get_args();
			$item = array_shift($parameters);
			
			if (count($parameters))
				$what = array_shift($parameters);
			else
				$what = '';
			
			if (count($parameters))
				$parameters = array_merge(array('template', $what, $item->itemid), $parameters);
			else
				$parameters = array('template', $what, $item->itemid);
			
			call_user_func_array(array(&$this, 'doSkinVar'), $parameters);
		}
		
		function doAction($type)
		{
			global $CONF, $member;
			
			if ($type == 'revert') 
			{
				if ($member->isLoggedIn() && $member->isAdmin()) 
				{
					$itemid   = intRequestVar('itemid');
					$revision = $this->_decodeRevison (requestVar('rev'));
				
					if ($revision) 
					{
						list($base, $sub) = $revision;
						$this->restoreRevision ($itemid, $base, $sub);
						header ('Location: ' . $CONF['AdminURL'] . '/index.php?action=itemedit&itemid=' . $itemid);
					}
				}
			}

			if ($type == 'diff') 
			{
				$itemid   = intRequestVar('itemid');
				$old 	  = requestVar('old');
				$new      = requestVar('new');
				$this->showDiff($itemid, $old, $new);
			}
		}

		function event_PreBlogContent(&$data) {
			$this->blogtype = $data['type'];
		}
		
		function event_PreItem(&$data) {
			global $member, $itemid, $CONF;
			
			$rev = requestVar('rev');
			
			if ($CONF['URLMode'] == 'pathinfo') {
				if (preg_match('/revision\/([0-9]+\.[0-9]+)/', serverVar('PATH_INFO'), $matches)) {
					$rev = $matches[1];
				}
			}

			if ($rev != '' && $data['item']->itemid == $itemid && $this->blogtype == 'item')
			{
				$rev = $this->_decodeRevison($rev);
				
				if ($rev) 
				{
					list($base, $sub) = $rev;
					
					$revision = $this->getRevision($itemid, $base, $sub);
					
					
					if ($revision) 
					{
						$allowed = true;
						
						if ($revision['idraft']) {
							if (!$member->isLoggedIn() || !$member->isAdmin()) {
								$allowed = false;
							}
						}
						
						if ($allowed) 
						{
							$last = $this->lastRevision($itemid);
							
							if ($last) 
							{
								list ($lastbase, $lastsub) = $last;
															
								if ($lastbase != $base || $lastsub != $sub) 
								{
									if ($this->getOption('showWarning') == 'yes') 
									{
										if (!isset($this->warningshown) || $this->warningshown == false) 
										{
											$vars = array (
												'itemid'	  => $itemid,
												'current' 	  => $base . '.' . $sub,
												'currentdate' => $this->eval_CurrentDate($itemid),
												'currenturl'  => $this->eval_Permalink($itemid, $base . '.' . $sub),
												'currentdiff' => $CONF['ActionURL'] . '?action=plugin&amp;name=Revision&amp;type=diff&amp;itemid='.$itemid.'&amp;old=' . $base . '.' . $sub . '&amp;new='.$base . '.' . $sub,
												'last'        => $lastbase . '.' . $lastsub,
												'lastdate' 	  => $this->eval_LastDate($itemid),
												'lasturl'     => $this->eval_Permalink($itemid, $lastbase . '.' . $lastsub),
												'lastdiff'    => $CONF['ActionURL'] . '?action=plugin&amp;name=Revision&amp;type=diff&amp;itemid='.$itemid.'&amp;old=' . $base . '.' . $sub . '&amp;new='.$lastbase . '.' . $lastsub,
											);

											echo TEMPLATE::fill($this->getOption('warningText'), $vars);
											$this->warningshown = true;
										}
									}
								}
							}
							
							$this->currentbase   = $base;
							$this->currentsub    = $sub;
							
							$data['item']->title = $revision['ititle'];
							$data['item']->body  = $revision['ibody'];
							$data['item']->more  = $revision['imore'];
						}
					}
				}
			}
		}

		function event_PreAddItem(&$data) {
			$this->tmp = $data;
		}
		
		function event_PostAddItem(&$data) {
			$this->createRevision ($data['itemid'], $this->tmp);
		}
		
		function event_PreUpdateItem(&$data) {
			if ($this->revertinprogress) 
			{
				$data['title']  = $this->revertinprogress['ititle'];
				$data['body']   = $this->revertinprogress['ibody'];
				$data['more']   = $this->revertinprogress['imore'];
				$data['blog']   = $this->revertinprogress['iblog'];
				$data['closed'] = $this->revertinprogress['iclosed'];
				$data['catid']  = $this->revertinprogress['icat'];
			} 
			else 
			{
				$this->createRevision ($data['itemid'], $data);
			}
		}
		
		function event_PreDeleteItem(&$data) {
			mysql_query ('DELETE FROM '.sql_table('plugin_revision').' WHERE inumber = ' . $data['itemid']);
		}

		function event_PreDeleteBlog(&$data) {
			mysql_query ('DELETE FROM '.sql_table('plugin_revision').' WHERE iblog = ' . $data['blogid']);
		}
		
		function event_PreMoveItem(&$data) {
			$tmp = array (
				'blogid' => $data['destblogid'],
				'catid' => $data['destcatid']
			);
			
			$this->createRevision ($data['itemid'], $tmp);
		}
		
		function event_EditItemFormExtras(&$data) {
			global $CONF;
			
			echo '<h3>Revisions</h3>';
			echo '<p><label for="revision_comment">Comment for next change:</label> ';
			echo '<input type="text" id="revision_comment" name="revision_comment" value="" size="60" /></p>';

			$res = mysql_query ('SELECT *, UNIX_TIMESTAMP(rtime) AS rtime FROM '.sql_table('plugin_revision').' WHERE inumber = ' . $data['itemid'] . ' ORDER BY rrevbase ASC, rrevsub ASC');

			if (mysql_num_rows($res))
			{
				echo "<table>";
				echo "<tr><th>Revision</th><th>Last modified</th>";
				echo "<th colspan='2'>Actions</th><th>Comments</th></tr>";
	
				$previous = '';
				$rows = array();
	
				while ($row = mysql_fetch_array($res)) {
					$row['rprev'] = $previous;
					$previous = $row['rrevbase'] . "." . $row['rrevsub'];
					$rows[] = $row;
				}
				
				$rows = array_reverse($rows);
				$i = 1;
				
				while (list(,$row) = each($rows)) 
				{
					echo "<tr>";
					echo "<td><strong>Revision " . $row['rrevbase'] . "." . $row['rrevsub'] . "</strong></td>";
					echo "<td>" . date('Y-n-j @ H:i',$row['rtime']) . "</td>";
	
					if ($i > 1)
						echo "<td><a href='" . $CONF['ActionURL'] . '?action=plugin&amp;name=Revision&amp;type=revert&amp;itemid='.$data['itemid'].'&amp;rev='.$row['rrevbase'].".".$row['rrevsub']."'>Revert</a></td>";
					else
						echo "<td>&nbsp;</td>";
	
					if ($row['rprev'] != '')
						echo "<td><a target='_blank' href='" . $CONF['ActionURL'] . '?action=plugin&amp;name=Revision&amp;type=diff&amp;itemid='.$data['itemid'].'&amp;new='.$row['rrevbase'].".".$row['rrevsub'].'&amp;old='.$row['rprev']."'>Diff to revision ".$row['rprev']."</a></td>";
					else
						echo "<td>&nbsp;</td>";
					
					echo "<td>" . htmlspecialchars($row['rcomment']) . "</td>";
					echo "</tr>";
	
					$i++;
				}
	
				echo "</table>";
			}
		}
		
		function showDiff($itemid, $old, $new) {
			global $CONF, $member, $nucleus, $blogid;
			
		    require_once($this->getDirectory(). '/DifferenceEngine.php');

			$new = $this->_decodeRevison ($new);
	
			if ($new)
			{
				list($newbase, $newsub) = $new;
			}
			else
			{
				$new = $this->lastRevision($itemid);

				if ($new) 
				{
					list($newbase, $newsub) = $new;
				}
				else
				{
					$newbase = 0;
					$newsub = 0;
				}	
			}

			$old = $this->_decodeRevison ($old);
			
			if ($old)
			{
				list($oldbase, $oldsub) = $old;
			}	
			else
			{
				if ($newbase != 0 && $newsub != 0) 
				{
					$old = $this->prevRevision($itemid, $newbase, $newsub);

					if ($old) 
					{
						list($oldbase, $oldsub) = $old;
					}
					else
					{
						$oldbase = $newbase;
						$oldsub  = $newsub;
					}
				}
			}
			
			echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
			echo "<html><head><title>Revision overview for item ".$itemid."</title>";
			echo "<link rel='stylesheet' type='text/css' href='".$CONF['AdminURL']."styles/bookmarklet.css' /><style type='text/css'>\n";
			echo "th { text-align: left; }\n .diff-blockheader { background: #EEE; font-weight: bold; width: 50%;}\n";
			echo ".diff-context { background: #EEE; }\n.diff-deletedline { background: #F99; }\n.diff-deletedline .diffchange { background: #933; color: #FFF; }\n";
			echo ".diff-addedline { background: #9F9; }\n.diff-addedline .diffchange { background: #393; color: #FFF; }\n</style></head><body>";
			echo "<h1>Revision overview for item ".$itemid."</h1>";

			// Get every revision for this item...
			$res = mysql_query ('SELECT rrevbase, rrevsub FROM '.sql_table('plugin_revision').' WHERE inumber = ' . $itemid . ' ORDER BY rrevbase DESC, rrevsub DESC');
			echo mysql_error();
			while ($row = mysql_fetch_array($res)) {
				$revisions[] = $row['rrevbase'] . '.' . $row['rrevsub'];
			}
			
			if (!count($revisions)) {
				$revisions[] = '1.0';
			}
			
			echo '<form action="action.php" method="get">';
			echo '<input type="hidden" name="action" value="plugin" />';
			echo '<input type="hidden" name="name" value="Revision" />';
			echo '<input type="hidden" name="type" value="diff" />';
			echo '<input type="hidden" name="itemid" value="'.$itemid.'" />';
			echo '<select name="old">';
			
			while (list(,$r) = each($revisions)) {
				echo '<option value="'.$r.'"'.(strcmp($r, $oldbase.'.'.$oldsub) == 0 ? ' selected="selected"': '').'>Revision '.$r.'</option>';
			}
			
			echo '</select> ';
			
			reset ($revisions);
			
			echo '<select name="new">';

			while (list(,$r) = each($revisions)) {
				echo '<option value="'.$r.'"'.(strcmp($r, $newbase.'.'.$newsub) == 0 ? ' selected="selected"': '').'>Revision '.$r.'</option>';
			}

			echo '</select> ';
			echo '<input type="submit" value="Show diff" />';
			echo '</form>';


			if ($oldbase != $newbase || $oldsub != $newsub)
			{
				$olddata = $this->getRevision($itemid, $oldbase, $oldsub);
				$newdata = $this->getRevision($itemid, $newbase, $newsub);
				$error   = false;
	
				if (!$olddata) {
					echo "<p class='message'>You cannot access revision ".$oldbase.".".$oldsub.", because it does not exist.</p>";
					$error = true;
				}
	
				if (!$newdata) {
					echo "<p class='message'>You cannot access revision ".$newbase.".".$newsub.", because it does not exist.</p>";
					$error = true;
				}
	
				if ($member->isLoggedIn()) 
				{
					if (preg_match("/MD$/", $nucleus['version'])) {
						$isblogadmin = $member->isBlogAdmin(-1);
					} else {
						$isblogadmin = $member->isBlogAdmin($olddata['iblog']) || $member->isBlogAdmin($newdata['iblog']);
					}
					
					$admin = $isblogadmin || $member->isAdmin();
				} 
				else 
				{
					$admin = false;
				}
				
				if (!$admin) {
					if ($olddata['idraft']) {
						echo "<p class='message'>You cannot access revision ".$oldbase.".".$oldsub.", because it is a draft.</p>";
						$error = true;
					}
					if ($newdata['idraft']) {
						echo "<p class='message'>You cannot access revision ".$newbase.".".$newsub.", because it is a draft.</p>";
						$error = true;
					}
				}
	
				if ($error == false)
				{
					$res = mysql_query ('SELECT rrevbase, rrevsub, rcomment, UNIX_TIMESTAMP(rtime) AS rtime FROM '.sql_table('plugin_revision').' WHERE inumber = ' . $itemid . ' AND (rrevbase > ' . $oldbase . ' OR (rrevbase = ' . $oldbase . ' AND rrevsub >= ' . $oldsub . ')) AND (rrevbase < ' . $newbase . ' OR (rrevbase = ' . $newbase . ' AND rrevsub <= ' . $newsub . ')) ORDER BY rrevbase DESC, rrevsub DESC');

					if (mysql_num_rows($res)) {
						echo "<h3>Revisions:</h3>";
						echo "<ul>";

						while ($row = mysql_fetch_array($res)) 
						{
							if ($CONF['URLMode'] == 'pathinfo') {
								$url = $CONF["IndexURL"] . 'item/' . $itemid . '/revision/' . $row['rrevbase'] . '.' . $row['rrevsub'];
							} else {
								$url = $CONF["IndexURL"] . 'index.php?itemid=' . $itemid . '&amp;rev=' . $row['rrevbase'] . '.' . $row['rrevsub'];
							}

							echo "<li><a href='".$url."'>Revision " . $row['rrevbase'] . '.' . $row['rrevsub'] . "</a>: " . $row['rcomment'] . " <em>(" . date('Y-n-j @ H:i',$row['rtime']) . ")</em></li>";
						}

						echo "</ul>";
					}
				
					echo '<h3>Diff between revision '.$oldbase.'.'.$oldsub.' and '.$newbase.'.'.$newsub.':</h3>';
					echo '<table>';
					
					if ($olddata['ititle'] != $newdata['ititle']) {
						echo '<th colspan="4">Title</td>';
						$df  = new Diff(split("\n",htmlspecialchars($olddata['ititle'])), split("\n",htmlspecialchars($newdata['ititle'])));
						$dformat = new TableDiffFormatter();
						echo $dformat->format($df);			
					} else {
						echo '<th colspan="4">Title</td>';
						echo '<tr><td>&nbsp;</td><td class="diff-context">'.htmlspecialchars($olddata['ititle']).'</td>';
						echo '<td>&nbsp;</td><td class="diff-context">'.htmlspecialchars($newdata['ititle']).'</td></tr>';
					}
		
					if ($olddata['ibody'] != $newdata['ibody']) {
						echo '<th colspan="4">Body</td>';
						$df  = new Diff(split("\n",htmlspecialchars($olddata['ibody'])), split("\n",htmlspecialchars($newdata['ibody'])));
						$dformat = new TableDiffFormatter();
						echo $dformat->format($df);			
					}
		
					if ($olddata['imore'] != $newdata['imore']) {
						echo '<th colspan="4">Extended</td>';
						$df  = new Diff(split("\n",htmlspecialchars($olddata['imore'])), split("\n",htmlspecialchars($newdata['imore'])));
						$dformat = new TableDiffFormatter();
						echo $dformat->format($df);			
					}
					
					if ($olddata['iblog'] != $newdata['iblog']) {
						$oldblog = new BLOG($olddata['iblog']);
						$newblog = new BLOG($newdata['iblog']);
					
						echo '<th colspan="4">Blog</td>';
						echo '<tr><td>-</td><td class="diff-deletedline">'.$oldblog->getName().'</td>';
						echo '<td>+</td><td class="diff-addedline">'.$newblog->getName().'</td></tr>';
					}
		
					if ($olddata['icat'] != $newdata['icat']) {
						$oldblog = new BLOG($olddata['iblog']);
						$newblog = new BLOG($newdata['iblog']);
					
						echo '<th colspan="4">Category</td>';
						echo '<tr><td>-</td><td class="diff-deletedline">'.$oldblog->getCategoryName($olddata['icat']).'</td>';
						echo '<td>+</td><td class="diff-addedline">'.$newblog->getCategoryName($newdata['icat']).'</td></tr>';
					}
					
					if ($olddata['iclosed'] != $newdata['iclosed']) {
						echo '<th colspan="4">Disable comments?</td>';
						echo '<tr><td>-</td><td class="diff-deletedline">'.($olddata['iclosed'] ? 'Yes' : 'No').'</td>';
						echo '<td>+</td><td class="diff-addedline">'.($newdata['iclosed'] ? 'Yes' : 'No').'</td></tr>';
					}			
					
					if ($olddata['idraft'] != $newdata['idraft']) {
						echo '<th colspan="4">Draft?</td>';
						echo '<tr><td>-</td><td class="diff-deletedline">'.($olddata['idraft'] ? 'Yes' : 'No').'</td>';
						echo '<td>+</td><td class="diff-addedline">'.($newdata['idraft'] ? 'Yes' : 'No').'</td></tr>';
					}			
					
					echo '</table>';
				}
			}
			
			echo "</body></html>";
		}
		
		function restoreRevision($inumber, $base, $sub) {
			global $blog, $manager;
			
			$restore = $this->getRevision($inumber, $base, $sub);

			if ($restore) 
			{
				/*********** UGLY HACK *************/
				/* The reason we need to call the  */
				/* event is because there may be   */
				/* other plugins waiting to modify */
				/* the data. We need to allow this */
				/* because otherwise they would    */
				/* also do this.                   */
				$this->revertinprogress = $restore;

				$manager->notify('PreUpdateItem', array(
					'itemid' 	=> $itemid, 
					'title' 	=> &$restore['ititle'], 
					'body' 		=> &$restore['ibody'], 
					'more' 		=> &$restore['imore'], 
					'blog' 		=> &$restore['iblog'], 
					'closed' 	=> &$restore['iclosed'], 
					'catid' 	=> &$restore['icat'])
				);

				$this->revertinprogress = false;
				/*********** UGLY HACK *************/

				$res = mysql_query ('
					UPDATE
						'.sql_table('item').'
					SET
						ititle = "' . addslashes($restore['ititle']) . '",
						ibody = "' . addslashes($restore['ibody']) . '",
						imore = "' . addslashes($restore['imore']) . '",
						iblog = "' . addslashes($restore['iblog']) . '",
						iauthor = "' . addslashes($restore['iauthor']) . '",
						itime = "' . addslashes($restore['itime']) . '",
						iclosed = "' . addslashes($restore['iclosed']) . '",
						idraft = "' . addslashes($restore['idraft']) . '",
						icat = "' . addslashes($restore['icat']) . '"
					WHERE
						inumber = ' . $inumber . '
				');
				
				$comment = "Revert from revision " . $base . "." . $sub;
				
				list($lastbase, $lastsub) = $this->lastRevision($inumber);
				$this->storeRevision($inumber, $lastbase, $lastsub + 1, $restore, $comment);
			}
		}
		
		function createRevision($inumber, &$data) {
			$last = $this->lastRevision($inumber);
			
			if ($last) 
			{
				list($lastbase, $lastsub) = $last;

				/*********** UGLY HACK *************/
				/* Because the status of draft is  */
				/* not provided by Nucleus we need */
				/* to figure it out for ourselves  */
				$data['draft'] = requestVar('actiontype') == 'adddraft' ? '1' : '0';
				/*********** UGLY HACK *************/

				$previous = $this->getRevision($inumber, $lastbase, $lastsub);
				$current  = $this->prepareData($data, $previous);

				$same = true;
				if ($previous['ititle'] != $current['ititle']) $same = false;
				if ($previous['ibody'] != $current['ibody']) $same = false;
				if ($previous['imore'] != $current['imore']) $same = false;
				if ($previous['iblog'] != $current['iblog']) $same = false;
				if ($previous['iauthor'] != $current['iauthor']) $same = false;
				if ($previous['itime'] != $current['itime']) $same = false;
				if ($previous['iclosed'] != $current['iclosed']) $same = false;
				if ($previous['idraft'] != $current['idraft']) $same = false;
				if ($previous['icat'] != $current['icat']) $same = false;

				if ($same == false)
				{
					// Increase revision
					$comment  = requestVar('revision_comment');

					if ($previous['idraft'] && !$current['idraft']) 
					{
						// Moving from draft to public
						$base = $lastbase + 1;
						$sub  = 0;
						
						if ($comment == '')
							$comment = 'Initial publication';
					} 
					else 
					{
						// Staying draft or staying public
						$base = $lastbase;
						$sub  = $lastsub + 1;
					}

					$this->storeRevision($inumber, $base, $sub, $current, $comment);
				}
			} 
			else 
			{
				if (isset($data['itemid'])) 
				{
					$res = mysql_query ('SELECT ititle, ibody, imore, iblog, iauthor, itime, iclosed, idraft, icat FROM '.sql_table('item').' WHERE inumber = ' . $data['itemid']);
					$previous = mysql_fetch_array($res);
				} 
				else 
				{
					$previous = array();
				}

				$current  = $this->prepareData($data, $previous);

				if (isset($data['itemid'])) 
				{
					$same = true;

					if ($previous['ititle'] != $current['ititle']) $same = false;
					if ($previous['ibody'] != $current['ibody']) $same = false;
					if ($previous['imore'] != $current['imore']) $same = false;
					if ($previous['iblog'] != $current['iblog']) $same = false;
					if ($previous['iauthor'] != $current['iauthor']) $same = false;
					if ($previous['itime'] != $current['itime']) $same = false;
					if ($previous['iclosed'] != $current['iclosed']) $same = false;
					if ($previous['idraft'] != $current['idraft']) $same = false;
					if ($previous['icat'] != $current['icat']) $same = false;
								
					if ($same == false)
					{
						// We are changing an existing item for which we have no history
						// so we are going to import both
						$this->storeRevision($inumber, 1, 0, $previous, 'Import of existing item');
						
						$base = 1;
						$sub  = 1;
						$comment = requestVar('revision_comment');
					}
					else
					{
						// We are editing an existing item, but not changing it, so we
						// are going to import it
						$base = 1;
						$sub  = 0;
						$comment = 'Import of existing item';
					}
				} 
				else 
				{
					// The item was just created...
					
					/*********** UGLY HACK *************/
					/* The PreAddItem event gives back */
					/* date with slashes escaped, the  */
					/* PreUpdateItem gives back data   */
					/* without any slashes. We need to */
					/* normalize this difference.      */
					$current['title'] = stripslashes($current['title']);
					$current['body']  = stripslashes($current['body']);
					$current['more']  = stripslashes($current['more']);
					/*********** UGLY HACK *************/
					
					if ($current['idraft'])
					{
						$base = 0;
						$sub = 1;
						$comment = 'Initial revision';
					}
					else
					{
						$base = 1;
						$sub = 0;
						$comment = 'Initial revision and publication';
					}
				}
				
				$this->storeRevision($inumber, $base, $sub, $current, $comment);
			}
		}
		
		function prepareData (&$data, $base = array()) {
			if (isset($data['title'])) 		$base['ititle'] 	= $data['title'];
			if (isset($data['body']))  		$base['ibody']  	= $data['body'];
			if (isset($data['more']))  		$base['imore']  	= $data['more'];
			if (isset($data['blog']))  		$base['iblog']  	= $data['blog']->blogid;
			if (isset($data['blogid']))  	$base['iblog']  	= $data['blogid'];
			if (isset($data['authorid']))	$base['iauthor']  	= $data['authorid'];
			if (isset($data['closed']))		$base['iclosed']  	= $data['closed'];
			if (isset($data['timestamp']))  $base['itime']  	= $data['timestamp'];
			if (isset($data['catid']))  	$base['icat']  		= $data['catid'];
			if (isset($data['draft']))  	$base['idraft']  	= $data['draft'];

			return $base;
		}
		
		function storeRevision ($inumber, $base, $sub, $data, $comment = '') {
			$res = mysql_query ('
				INSERT INTO 
					'.sql_table('plugin_revision').'
				SET
					inumber = ' . $inumber . ',
					rrevbase = ' . $base . ',
					rrevsub = ' . $sub . ',
					ititle = "' . addslashes($data['ititle']) . '",
					ibody = "' . addslashes($data['ibody']) . '",
					imore = "' . addslashes($data['imore']) . '",
					iblog = "' . addslashes($data['iblog']) . '",
					iauthor = "' . addslashes($data['iauthor']) . '",
					itime = "' . addslashes($data['itime']) . '",
					iclosed = "' . addslashes($data['iclosed']) . '",
					idraft = "' . addslashes($data['idraft']) . '",
					icat = "' . addslashes($data['icat']) . '",
					rtime = NOW(),
					rcomment = "' . addslashes($comment) . '"
			');
		}
		
		function getRevision($inumber, $base, $sub) {
			$res = mysql_query ('SELECT ititle, ibody, imore, iblog, iauthor, itime, iclosed, idraft, icat FROM '.sql_table('plugin_revision').' WHERE inumber = ' . $inumber . ' AND rrevbase = ' . $base . ' AND rrevsub = ' . $sub);
			if ($row = mysql_fetch_array($res))
				return $row;
			else
				return false;
		}
		
		function lastRevision($inumber) {
			$res = mysql_query ('SELECT rrevbase, rrevsub FROM '.sql_table('plugin_revision').' WHERE inumber = ' . $inumber . ' ORDER BY rrevbase DESC, rrevsub DESC LIMIT 1');
			if ($row = mysql_fetch_array($res))
				return array($row['rrevbase'], $row['rrevsub']);
			else
				return false;
		}
		
		function prevRevision($inumber, $base, $sub) {
			$res = mysql_query ('SELECT rrevbase, rrevsub FROM '.sql_table('plugin_revision').' WHERE inumber = ' . $inumber . ' AND (rrevbase < ' . $base . ' OR (rrevbase = ' . $base .  ' AND rrevsub < ' . $sub . ')) ORDER BY rrevbase DESC, rrevsub DESC LIMIT 1');
			if ($row = mysql_fetch_array($res))
				return array($row['rrevbase'], $row['rrevsub']);
			else
				return false;
		}
		
		function _decodeRevison ($revision) {
			if (preg_match('/([0-9]+)\.([0-9]+)/', (string)$revision, $matches))
				return array($matches[1], $matches[2]);
			else
				return false;
		}		
	}

?>