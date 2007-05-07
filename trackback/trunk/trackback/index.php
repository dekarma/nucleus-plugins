<?php

	$strRel = '../../../'; 
	include($strRel . 'config.php');
	include($DIR_LIBS . 'PLUGINADMIN.php');
	include('template.php');
	
	
	
	// Send out Content-type
	sendContentType('application/xhtml+xml', 'admin-trackback', _CHARSET);	

	// Compatiblity with Nucleus < = 2.0
	if (!function_exists('sql_table')) { function sql_table($name) { return 'nucleus_' . $name; } }
	


	$oPluginAdmin = new PluginAdmin('TrackBack');

	if (!($member->isLoggedIn() && $member->isAdmin()))
	{
		$oPluginAdmin->start();
		echo '<p>' . _ERROR_DISALLOWED . '</p>';
		$oPluginAdmin->end();
		exit;
	}
	
	$oPluginAdmin->start();
	
	$mTemplate = new Trackback_Template();
	$mTemplate->set ('CONF', $CONF);
	$mTemplate->template('templates/menu.html');
	echo $mTemplate->fetch();
	
	$oTemplate = new Trackback_Template();
	$oTemplate->set ('CONF', $CONF);




	// Actions
	$action = requestVar('action');

	switch($action) {

                case 'delblocked':

			$res = mysql_query ("
                                DELETE FROM
                                        ".sql_table('plugin_tb')."
                                WHERE
					block = 1
			");
                        echo "All blocked trackbacks deleted";
                        break;

		case 'block':
			$tb = intRequestVar('tb');

			$res = mysql_query ("
				UPDATE
					".sql_table('plugin_tb')."
				SET
					block = 1
				WHERE
					id = '".$tb."'
			");

			$action = requestVar('next');
			break;

		case 'unblock':
			$tb = intRequestVar('tb');

			$res = mysql_query ("
				UPDATE
					".sql_table('plugin_tb')."
				SET
					block = 0
				WHERE
					id = '".$tb."'
			");

			$action = requestVar('next');
			break;

		case 'delete':
			$tb = intRequestVar('tb');

			$res = mysql_query ("
				DELETE FROM
					".sql_table('plugin_tb')."
				WHERE
					id = '".$tb."'
			");

			$action = requestVar('next');
			break;

		case 'sendping':
			$title     = requestVar('title');
			$url       = requestVar('url');
			$excerpt   = requestVar('excerpt');
			$blog_name = requestVar('blog_name');
			$ping_url  = requestVar('ping_url');		

			// No charset conversion needs to be done here, because
			// the charset used to receive the info is used to send
			// it...

			if ($ping_url) {
				$error = $oPluginAdmin->plugin->sendPing(0, $title, $url, $excerpt, $blog_name, $ping_url);
				
				if ($error) {
					echo '<b>TrackBack Error:' . $error . '</b>';
				}
			} 		
			
			$action = requestVar('next');
			break;
	}

	// Pages 
	switch($action) {
		
		case 'help':
			$oTemplate->template('help.html');			
			break;

		case 'ping':
			$oTemplate->template('templates/ping.html');			
			break;

		case 'blocked':
			$start  = intRequestVar('start') ? intRequestVar('start') : 0;
			$amount = intRequestVar('amount') ? intRequestVar('amount') : 25;

			$rres = mysql_query ("
				SELECT
					COUNT(*) AS count
				FROM
					".sql_table('plugin_tb')." AS t,
					".sql_table('item')." AS i
				WHERE
					t.tb_id = i.inumber AND
					t.block = 1
			");				
						
			if ($row = mysql_fetch_array($rres))
				$count = $row['count'];
			else
				$count = 0;
					
			$rres = mysql_query ("
				SELECT
					i.ititle AS story,
					t.id AS id,
					t.title AS title,
					t.blog_name AS blog_name,
					t.excerpt AS excerpt,
					t.url AS url,
					UNIX_TIMESTAMP(t.timestamp) AS timestamp,
					t.spam AS spam,
					t.link AS link
				FROM
					".sql_table('plugin_tb')." AS t,
					".sql_table('item')." AS i
				WHERE
					t.tb_id = i.inumber AND
					t.block = 1
				ORDER BY
					timestamp DESC
				LIMIT
					".$start.",".$amount."
			");				
			
			$items = array();

			while ($rrow = mysql_fetch_array($rres))
			{
				$rrow['title'] 		= $oPluginAdmin->plugin->_cut_string($rrow['title'], 50);
				$rrow['title'] 		= htmlspecialchars($rrow['title']);
				$rrow['title'] 		= _CHARSET == 'UTF-8' ? $rrow['title'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['title']);

				$rrow['blog_name'] 	= $oPluginAdmin->plugin->_cut_string($rrow['blog_name'], 50);
				$rrow['blog_name'] 	= htmlspecialchars($rrow['blog_name']);
				$rrow['blog_name'] 	= _CHARSET == 'UTF-8' ? $rrow['blog_name'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['blog_name']);

				$rrow['excerpt'] 	= $oPluginAdmin->plugin->_cut_string($rrow['excerpt'], 800);
				$rrow['excerpt'] 	= htmlspecialchars($rrow['excerpt']);
				$rrow['excerpt'] 	= _CHARSET == 'UTF-8' ? $rrow['excerpt'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['excerpt']);

				$rrow['url'] 		= htmlspecialchars($rrow['url'], ENT_QUOTES);
				$items[] = $rrow;
			}
			
			$oTemplate->set ('amount', $amount);
			$oTemplate->set ('count', $count);
			$oTemplate->set ('start', $start);
			$oTemplate->set ('items', $items);
			$oTemplate->template('templates/blocked.html');			
			break;

		case 'all':
			$start  = intRequestVar('start') ? intRequestVar('start') : 0;
			$amount = intRequestVar('amount') ? intRequestVar('amount') : 25;

			$rres = mysql_query ("
				SELECT
					COUNT(*) AS count
				FROM
					".sql_table('plugin_tb')." AS t,
					".sql_table('item')." AS i
				WHERE
					t.tb_id = i.inumber AND
					t.block = 0
			");				
						
			if ($row = mysql_fetch_array($rres))
				$count = $row['count'];
			else
				$count = 0;
					
			$rres = mysql_query ("
				SELECT
					i.ititle AS story,
					t.id AS id,
					t.title AS title,
					t.blog_name AS blog_name,
					t.excerpt AS excerpt,
					t.url AS url,
					UNIX_TIMESTAMP(t.timestamp) AS timestamp
				FROM
					".sql_table('plugin_tb')." AS t,
					".sql_table('item')." AS i
				WHERE
					t.tb_id = i.inumber AND
					t.block = 0
				ORDER BY
					timestamp DESC
				LIMIT
					".$start.",".$amount."
			");				
			
			$items = array();

			while ($rrow = mysql_fetch_array($rres))
			{
				$rrow['title'] 		= $oPluginAdmin->plugin->_cut_string($rrow['title'], 50);
				$rrow['title'] 		= htmlspecialchars($rrow['title']);
				$rrow['title'] 		= _CHARSET == 'UTF-8' ? $rrow['title'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['title']);

				$rrow['blog_name'] 	= $oPluginAdmin->plugin->_cut_string($rrow['blog_name'], 50);
				$rrow['blog_name'] 	= htmlspecialchars($rrow['blog_name']);
				$rrow['blog_name'] 	= _CHARSET == 'UTF-8' ? $rrow['blog_name'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['blog_name']);

				$rrow['excerpt'] 	= $oPluginAdmin->plugin->_cut_string($rrow['excerpt'], 800);
				$rrow['excerpt'] 	= htmlspecialchars($rrow['excerpt']);
				$rrow['excerpt'] 	= _CHARSET == 'UTF-8' ? $rrow['excerpt'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['excerpt']);

				$rrow['url'] 		= htmlspecialchars($rrow['url'], ENT_QUOTES);
				$items[] = $rrow;
			}
			
			$oTemplate->set ('amount', $amount);
			$oTemplate->set ('count', $count);
			$oTemplate->set ('start', $start);
			$oTemplate->set ('items', $items);
			$oTemplate->template('templates/all.html');			
			break;			
		
		case 'list':
			$id     = requestVar('id');
			$start  = intRequestVar('start') ? intRequestVar('start') : 0;
			$amount = intRequestVar('amount') ? intRequestVar('amount') : 25;

			$ires = mysql_query ("
				SELECT
					ititle,
					inumber
				FROM
					".sql_table('item')."
				WHERE
					inumber = '".$id."'
			");
			
			if ($irow = mysql_fetch_array($ires))
			{
				$story['id']    = $id;
				$story['title'] = $irow['ititle'];

				$rres = mysql_query ("
					SELECT
						COUNT(*) AS count
					FROM
						".sql_table('plugin_tb')." AS t
					WHERE
						t.tb_id = '".$id."' AND
						t.block = 0
				");				
							
				if ($row = mysql_fetch_array($rres))
					$count = $row['count'];
				else
					$count = 0;
					
				$rres = mysql_query ("
					SELECT
						t.id AS id,
						t.title AS title,
						t.blog_name AS blog_name,
						t.excerpt AS excerpt,
						t.url AS url,
				        UNIX_TIMESTAMP(t.timestamp) AS timestamp
					FROM
						".sql_table('plugin_tb')." AS t
					WHERE
						t.tb_id = '".$id."' AND
						t.block = 0
					ORDER BY
						timestamp DESC
					LIMIT
						".$start.",".$amount."
				");				
				
				$items = array();
	
				while ($rrow = mysql_fetch_array($rres))
				{
					$rrow['title'] 		= $oPluginAdmin->plugin->_cut_string($rrow['title'], 50);
					$rrow['title'] 		= htmlspecialchars($rrow['title']);
					$rrow['title'] 		= _CHARSET == 'UTF-8' ? $rrow['title'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['title']);
	
					$rrow['blog_name'] 	= $oPluginAdmin->plugin->_cut_string($rrow['blog_name'], 50);
					$rrow['blog_name'] 	= htmlspecialchars($rrow['blog_name']);
					$rrow['blog_name'] 	= _CHARSET == 'UTF-8' ? $rrow['blog_name'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['blog_name']);
	
					$rrow['excerpt'] 	= $oPluginAdmin->plugin->_cut_string($rrow['excerpt'], 800);
					$rrow['excerpt'] 	= htmlspecialchars($rrow['excerpt']);
					$rrow['excerpt'] 	= _CHARSET == 'UTF-8' ? $rrow['excerpt'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['excerpt']);
	
					$rrow['url'] 		= htmlspecialchars($rrow['url'], ENT_QUOTES);
					$items[] = $rrow;
				}
				
				$oTemplate->set ('amount', $amount);
				$oTemplate->set ('count', $count);
				$oTemplate->set ('start', $start);
				$oTemplate->set ('items', $items);
				$oTemplate->set ('story', $story);
				$oTemplate->template('templates/list.html');			
			}
			
			break;
							
		
		case 'index':
			$bres = mysql_query ("
				SELECT
					bnumber AS bnumber,
					bname AS bname,
					burl AS burl
				FROM
					".sql_table('blog')."
				ORDER BY
					bname
			");
			
			$blogs = array();
			
			while ($brow = mysql_fetch_array($bres))
			{
				$ires = mysql_query ("
					SELECT
						i.inumber AS inumber,
					    i.ititle AS ititle,
					    COUNT(*) AS total
					FROM
						".sql_table('item')." AS i,
						".sql_table('plugin_tb')." AS t
					WHERE
						i.iblog = ".$brow['bnumber']." AND
						t.tb_id = i.inumber AND
						t.block = 0
					GROUP BY
						i.inumber
                    ORDER BY
                    	i.inumber DESC
				");				

				$items = array();

				while ($irow = mysql_fetch_array($ires))
				{
					$items[] = $irow;
				}

				$brow['items'] = $items;
				$blogs[] = $brow;
			}

			$oTemplate->set ('blogs', $blogs);
			$oTemplate->template('templates/index.html');
			break;

		default:
			break;
	}

	// Create the admin area page
	echo $oTemplate->fetch();
	$oPluginAdmin->end();	

?>
