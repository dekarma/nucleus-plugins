<?php

   /* ==========================================================================================
	* Trackback 2 for Nucleus CMS 
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
        * admun TODO:
	*   - delete blocked tb older than x days
	*   - clear tb lookup cache
	*   - tb url auto discovery via rel="trackback" (like in Wordpress)
	*/


	// Compatiblity with Nucleus < = 2.0
	if (!function_exists('sql_table')) { function sql_table($name) { return 'nucleus_' . $name; } }

	
	class NP_TrackBack extends NucleusPlugin {


    	/**************************************************************************************
    	 * SKIN VARS, TEMPLATE VARS AND ACTIONS
		 */

		/*
		 * TrackBack data can be inserted using skinvars (or templatevars)
		 */
		function doSkinVar($skinType, $what = '', $tb_id = '') {
			
			global $itemid;
			if ($tb_id == '') $tb_id = $itemid;
			
			switch ($what) {
				
				// Insert Auto-discovery RDF code
				case 'tbcode':
				case 'code':
					if($skinType == 'item')
						$this->insertCode($tb_id);
					break;
				
				// Insert TrackBack URL
				case 'tburl':
				case 'url':
					echo $this->getTrackBackUrl($tb_id);
					break;
				
				// Insert a ping form link (with all info filled in) to ping anoher post
				case 'pingformlink':
					echo $this->getPingFormLink($tb_id);
					break;

				// Insert manual ping URL form other to ping this post
				case 'form':
				case 'manualpingformlink':
					echo $this->getManualPingUrl($tb_id);
					break;
				
				// Insert TrackBack count
				case 'count':
					$count = $this->getTrackBackCount($tb_id);
					switch ($count) {
						case 0: 	echo TEMPLATE::fill($this->getOption('tplTbNone'), array('number' => $count)); break;
						case 1: 	echo TEMPLATE::fill($this->getOption('tplTbOne'),  array('number' => $count)); break;
						default: 	echo TEMPLATE::fill($this->getOption('tplTbMore'), array('number' => $count)); break;
					}
					break;
				
				// Shows the TrackBack list
				case 'list':
				case '':
					$this->showList($tb_id);
					break;
				
				// show the most recent 5 tb
				case 'latest':
					$query  = "SELECT tb_id, url, title, blog_name ";
					$query .= "FROM ".sql_table('plugin_tb')." WHERE block = 0 ORDER BY timestamp DESC LIMIT 0,5";
					$result = sql_query($query);
					   
					echo "<ul>";
					while ($row = mysql_fetch_object($result)) {
						$url = createItemLink($row->tb_id,'');
						echo "<li><b><a href='".$url."'>".$row->title."</a></b>";
						echo "<br />from <a href='".$row->url."'>".$row->blog_name."</a></li>";
					}
					echo "</ul>";
				break;

				default:
					return;
			}
		}
		
		/*
		 * When used in templates, the tb_id will be determined by the itemid there
		 */
		function doTemplateVar(&$item, $what = '') {
			$this->doSkinVar('template', $what, $item->itemid);
		}
		
		
		/*
		 * A trackback ping is to be received on the URL
		 * http://yourdomain.com/item/1234.trackback
		 * Extra variables to be passed along are url, title, excerpt, blog_name
		 */
		function event_InitSkinParse(&$data) {
			global $CONF, $itemid;
			
			$format = requestVar('format');
				
			if ($CONF['URLMode'] == 'pathinfo') {
				if (preg_match('/(\/|\.)(trackback)(\/|$)/', serverVar('PATH_INFO'), $matches)) {
					$format = $matches[2];
				}
			}
			
			if ($format == 'trackback' && $data['type'] == 'item')
			{
				$errorMsg = $this->handlePing($itemid);
				
				if ($errorMsg != '')
					$this->xmlResponse($errorMsg);
				else
					$this->xmlResponse();
				
				exit;
			}
		}
		
		/*
		 * A trackback ping is to be received on the URL
		 * http://yourdomain.com/action.php?action=plugin&name=TrackBack&tb_id=1234
		 * Extra variables to be passed along are url, title, excerpt, blog_name
		 */
		function doAction($type)
		{
			global $CONF;
			switch ($type) {
				
				// When no action type is given, assume it's a ping
				case '':
					$errorMsg = $this->handlePing();
					
					if ($errorMsg != '')
						$this->xmlResponse($errorMsg);
					else
						$this->xmlResponse();
					break; 
				
				// Manual ping
				case 'ping':
					$errorMsg = $this->handlePing();
					
					if ($errorMsg != '')
						$this->showManualPingError(intRequestVar('tb_id'), $errorMsg);
					else
						$this->showManualPingSuccess(intRequestVar('tb_id'));
					break; 
				
				// Show manual ping form
				case 'form':
					$this->showManualPingForm(intRequestVar('tb_id'));
					break;

				// show a 'Send Ping' form to allow pinging other site for a post
				case 'pingform':
					return $this->showPingForm();
					break;

				
				// Detect trackback
				case 'detect':
					list($url, $title) = 
						$this->getURIfromLink(html_entity_decode(requestVar('tb_link')));
					
					$url = addslashes($url);
					$url = $this->_utf8_to_javascript($url);
					
					$title = addslashes($title);
					$title = $this->_utf8_to_javascript($title);
					
					echo "tbDone('" . requestVar('tb_link') . "', '" . $url . "', '" . $title . "');";
					break;

				// Send ping triggered by tb ping form for a post
				case 'sendping':
					$itemid = intRequestVar('itemid');
					$title = requestVar('title');
					$url = requestVar('url');
					$excerpt = requestVar('excerpt');
					$blog_name = requestVar('blog_name');
					$ping_url = requestVar('ping_url');
					if ($ping_url) {
						$errorMsg = $this->sendPing($itemid, $title, $url, $excerpt, $blog_name, $ping_url);
						if ($errorMsg) {
							ACTIONLOG::add(WARNING, 'TrackBack Error:', $errorMsg, ' (',$ping_url,')');
							return $errorMsg;
						}
					}

					header('Location: ' . requestVar('redirectTo'));
					break;

				// manually chean tb key
				case 'cleankey':
					$this->_clearExpiredKey();
					echo "Expired keys cleared";
					break;
			} 
			
			exit;
		} 



    	/**************************************************************************************
    	 * OUTPUT
	 */

		/*
		 * Show a list of all trackbacks for this ID
		 */
		function showList($tb_id) {
			global $manager, $blog, $CONF;
			
			$res = sql_query('
				SELECT 
					url, 
					blog_name, 
					excerpt, 
					title, 
					UNIX_TIMESTAMP(timestamp) AS timestamp 
				FROM 
					'.sql_table('plugin_tb').' 
				WHERE 
					tb_id = '.$tb_id .' AND
					block = 0
				ORDER BY 
					timestamp ASC
			');
			
			$gVars = array(
				'action' => $this->getTrackBackUrl($tb_id),
				'form' 	 => $this->getManualPingUrl($tb_id)
			);
							
			echo TEMPLATE::fill($this->getOption('tplHeader'), $gVars);
			
			while ($row = mysql_fetch_array($res))
			{
				$row['blog_name'] 	= htmlspecialchars($row['blog_name']);
				$row['title']  		= htmlspecialchars($row['title']);
				$row['excerpt']  	= htmlspecialchars($row['excerpt']);
				
				if (_CHARSET != 'UTF-8') {
					$row['blog_name'] 	= $this->_utf8_to_entities($row['blog_name']);
					$row['title'] 		= $this->_utf8_to_entities($row['title']);
					$row['excerpt'] 	= $this->_utf8_to_entities($row['excerpt']);
				}				
				
				$iVars = array(
					'action' 	=> $this->getTrackBackUrl($tb_id),
					'form' 	 	=> $this->getManualPingUrl($tb_id),
					'name'  	=> $row['blog_name'],
					'title' 	=> $row['title'],
					'excerpt'	=> $row['excerpt'],
					'url'		=> htmlspecialchars($row['url'], ENT_QUOTES),
					'date'	   	=> htmlspecialchars(strftime($this->getOption('dateFormat'), $row['timestamp'] + ($blog->getTimeOffset() * 3600)), ENT_QUOTES)
				);
				
				echo TEMPLATE::fill($this->getOption('tplItem'), $iVars);
			}
			
			if (mysql_num_rows($res) == 0) 
			{
				echo TEMPLATE::fill($this->getOption('tplEmpty'), $gVars);
			}
			
			echo TEMPLATE::fill($this->getOption('tplFooter'), $gVars);
		}
			
		/*
		 * Returns the TrackBack count for a TrackBack item
		 */
		function getTrackBackCount($tb_id) {
			return quickQuery('SELECT COUNT(*) as result FROM ' . sql_table('plugin_tb') . ' WHERE tb_id='.$tb_id.' AND block = 0');
		}
		
		/**
		  * Returns the manual ping URL
		  */
		function getManualPingUrl($itemid) {
			global $CONF;
			return $CONF['ActionURL'].'?action=plugin&amp;name=TrackBack&amp;type=form&amp;tb_id='.$itemid.'&amp;tb_key='.$this->getTbKey();
		}
		
		/**
		  * Show the manual ping form
		  */
		function showManualPingError($itemid, $status = '') {
			global $CONF;
			
			$form = true; $error = true; $success = false;
			sendContentType('text/html', 'admin-trackback', _CHARSET);	
			include ($this->getDirectory() . '/templates/form.html');
		}
		
		function showManualPingSuccess($itemid, $status = '') {
			global $CONF;
			
			$form = false; $error = false; $success = true;
			sendContentType('text/html', 'admin-trackback', _CHARSET);	
			include ($this->getDirectory() . '/templates/form.html');
		}
		
		function showManualPingForm($itemid, $text = '') {
			global $CONF;
			
			$form = true; $error = false; $success = false;
			
			// check if tb key is include
			$key =  RequestVar('tb_key');
			if ($this->_findKey($key) == false) {
				return 'Sorry, invalid trackback key';
				$form = false; $error = true;
			}

			// Check if we are allowed to accept pings
			if ($this->getOption('AcceptPing') == 'no') {
				$text = 'Sorry, no trackback pings are accepted';
				$form = false; $error = true;
			}
			
			sendContentType('text/html', 'admin-trackback', _CHARSET);	
			include ($this->getDirectory() . '/templates/form.html');
		}
		
		/**
		  * Returns the trackback URL
		  */
		function getTrackBackUrl($itemid) {
			global $CONF;

			return $CONF['ActionURL'] .  '?action=plugin&amp;name=TrackBack&amp;tb_id='.$itemid.'&amp;tb_key='.$this->getTbKey();
		}
	
		/*
		 * Insert RDF code for item
		 */
		function insertCode($itemid) {
			global $manager, $CONF;
			
			$item = & $manager->getItem($itemid, 0, 0);
			$blog = & $manager->getBlog(getBlogIDFromItemID($item['itemid']));
			
			$CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());   
			$uri 	= createItemLink($item['itemid'],'');	
			
			$title  = strip_tags($item['title']);
			$desc  	= strip_tags($item['body']);
			$desc   = $this->_cut_string($desc, 200);
			$desc   = htmlspecialchars($desc, ENT_QUOTES);
			
			?>
			<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
					 xmlns:dc="http://purl.org/dc/elements/1.1/"
					 xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
			<rdf:Description
					 rdf:about="<?php echo $uri; ?>"
					 dc:identifier="<?php echo $uri; ?>"
					 dc:title="<?php echo $title; ?>"
					 dc:description="<?php echo $desc; ?>"
					 trackback:ping="<?php echo $this->getTrackBackUrl($itemid)?>"
					 dc:date="<?php echo strftime('%Y-%m-%dT%H:%M:%S')?>" />
			</rdf:RDF>
			<?php
		}
		
		/**
		 * Retrieving TrackBack Pings (when __mode=rss)
		 */
		function rssResponse($tb_id) {
			global $manager, $CONF;
			$item =& $manager->getItem($tb_id, 0, 0);
			
			if($item)
			{
				$blog =& $manager->getBlog(getBlogIDFromItemID($item['itemid']));
				
				$blog_name  = $blog->getName();
				$title      = $item['title'];
				$excerpt    = $item['body'];
				
				if (_CHARSET != 'UTF-8')
				{
					$title      = $this->_convert_to_utf8($title, $encoding);
					$excerpt    = $this->_convert_to_utf8($excerpt, $encoding);
					$blog_name  = $this->_convert_to_utf8($blog_name, $encoding);
				}
				
				$title      = $this->_decode_entities(strip_tags($title));
				$excerpt    = $this->_decode_entities(strip_tags($excerpt));
				$blog_name  = $this->_decode_entities(strip_tags($blog_name));
				$excerpt    = $this->_cut_string($excerpt, 200);
				
				
				$CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());   
				$url = createItemLink($item['itemid'],'');
				
				// Use UTF-8 charset for output
				header('Content-Type: text/xml');
				echo "<","?xml version='1.0' encoding='UTF-8'?",">\n";
				
				echo "<response>\n";
				echo "\t<error>0</error>\n";
				echo "\t<rss version='0.91'>\n";
				echo "\t\t<channel>\n";
				echo "\t\t\t<title>".htmlspecialchars($title)."</title>\n";
				echo "\t\t\t<link>".htmlspecialchars($url)."</link>\n";
				echo "\t\t\t<description>".htmlspecialchars($excerpt)."</description>\n";
				
				$query = 'SELECT url, blog_name, excerpt, title, UNIX_TIMESTAMP(timestamp) as timestamp FROM '.sql_table('plugin_tb').' WHERE tb_id='.$tb_id .' AND block = 0 ORDER BY timestamp DESC';
				$res = sql_query($query);
				while ($o = mysql_fetch_object($res)) 
				{
					// No need to do conversion, because it is already UTF-8
					$data = array (
						'url' 		=> htmlspecialchars($o->url),
						'blogname' 	=> htmlspecialchars($o->blog_name),
						'timestamp' => strftime('%Y-%m-%d',$o->timestamp),
						'title' 	=> htmlspecialchars($o->title),
						'excerpt' 	=> htmlspecialchars($o->excerpt),
						'tburl' 	=> $this->getTrackBackUrl($tb_id)
					);
					
					echo "\n";
					echo "\t\t\t<item>\n";
					echo "\t\t\t\t<title>".$data['title']."</title>\n";
					echo "\t\t\t\t<link>".$data['url']."</link>\n";
					echo "\t\t\t\t<description>".$data['excerpt']."</description>\n";
					echo "\t\t\t</item>\n";
				}
				echo "\t\t</channel>\n";
				echo "\t</rss>\n";
				echo "</response>";
				exit;
			}
			else
			{
				$this->xmlResponse(_ERROR_NOSUCHITEM);
			}
		}
	
	

    	/**************************************************************************************
    	 * SENDING AND RECEIVING TRACKBACK PINGS
	 */

		/* 
		 *  Send a Trackback ping to another website
		 */
		function sendPing($itemid, $title, $url, $excerpt, $blog_name, $ping_url) 
		{
			// 1. Check some basic things
			if (!$this->canSendPing()) {
				return 'You\'re not allowed to send pings';
			}
			
			if ($this->getOption('SendPings') == 'no') {
				return 'Sending trackback pings is disabled';
			}
			
			if ($ping_url == '') {
				return 'No ping URL';
			}
			
			// 2. Check if protocol is correct http URL
			$parsed_url = parse_url($ping_url);
			
			if ($parsed_url['scheme'] != 'http' || $parsed_url['host'] == '')
				return 'Bad ping URL';
			
			$port = ($parsed_url['port']) ? $parsed_url['port'] : 80;
			
			// 3. Create contents
			$content  = 'title=' . 		urlencode( $title );
			$content .= '&url=' . 		urlencode( $url );
			$content .= '&excerpt=' . 	urlencode( $excerpt );
			$content .= '&blog_name=' . urlencode( $blog_name );
			
			$user_agent = 'NucleusCMS NP_TrackBack plugin';
			
			// 4. Prepare HTTP request
			$request  = 'POST ' . $parsed_url['path'];
			
			if ($parsed_url['query'] != '')
				$request .= '?' . $parsed_url['query'];
				
			$request .= " HTTP/1.1\r\n";
			$request .= "Accept: */*\r\n";
			$request .= "User-Agent: " . $user_agent . "\r\n";
			$request .= "Host: " . $parsed_url['host'] . ":" . $port . "\r\n";
			$request .= "Cache-Control: no-cache\r\n";
			$request .= "Content-Length: " . strlen( $content ) . "\r\n";
			$request .= "Content-Type: application/x-www-form-urlencoded; charset="._CHARSET."\r\n";
			$request .= "Connection: Close\r\n";
			$request .= "\r\n";
			$request .= $content;
			
			$socket = fsockopen( $parsed_url['host'], $port, $errno, $errstr );
			if ( ! $socket )
				return 'Could not send ping: '.$errstr.' ('.$errno.')';
			
			// 5. Execute HTTP request
			fputs($socket, $request);
			
			// 6. Receive response
			$result = '';
			while (!feof($socket)) {
				$result .= fgets($socket, 4096);
			}
			
			fclose($socket);
			
			// instead of parsing the XML, just check for the error string
			// [TODO] extract real error message and return that
			if ( strstr($result,'<error>1</error>') )
				return 'An error occurred: '.htmlspecialchars($result);
		} 

		/* 
		 *  Handle a Trackback ping sent to this website
		 */
		function handlePing($tb_id = 0) {
			global $manager;
			
			$this->_clearExpiredKey();

			$key = RequestVar('tb_key');
			if ($this->_findKey($key) == false) {
				return 'Sorry, invalid trackback key ' . $key;
			}

			$this->_deleteKey($key);

			// Check if we are allowed to accept pings
			if ($this->getOption('AcceptPing') == 'no') {
				return 'Sorry, no trackback pings are accepted';
			}
			
			// Defaults
			$spam       = false;
			$link       = false;
			$block 	    = true;
			
			if ($tb_id == 0) 
				$tb_id = intRequestVar('tb_id');
			
			$rss 		= requestVar('__mode') == 'rss'; 
			
			if (!$tb_id) {
				return 'TrackBack ID is missing (tb_id)';
			}
			
			if ((!$manager->existsItem($tb_id,0,0)) && ($this->getOption('CheckIDs') == 'yes')) {
				return _NOSUCH_ITEM;
			}
			
			// 0. Check if we need to output the list as rss
			if ($rss) {
				$this->rssResponse($tb_id);
				return;
			}
			
			// 1. Get attributes
			$url 		= requestVar('url');
			$title 		= requestVar('title');
			$excerpt 	= requestVar('excerpt');
			$blog_name 	= requestVar('blog_name');
			
			if (!$url) {
				return 'URL is missing (url)';
			}
			
			// 2. Conversion of encoding...
			if (preg_match ("/;\s*charset=([^\n]+)/is", $_SERVER["CONTENT_TYPE"], $regs))
				$encoding = strtoupper(trim($regs[1]));
			else
				$encoding = $this->_detect_encoding($excerpt);
			
			$title      = $this->_convert_to_utf8($title, $encoding);
			$excerpt    = $this->_convert_to_utf8($excerpt, $encoding);
			$blog_name  = $this->_convert_to_utf8($blog_name, $encoding);
			
			$title      = $this->_decode_entities(strip_tags($title));
			$excerpt    = $this->_decode_entities(strip_tags($excerpt));
			$blog_name  = $this->_decode_entities(strip_tags($blog_name));
			
			// 4. Save data in the DB
			$res = @sql_query('
				SELECT 
					tb_id 
				FROM 
					'.sql_table('plugin_tb').' 
				WHERE 
					url   = "'.addslashes($url).'" AND 
					tb_id = "'.$tb_id.'"
			');
			
			if (mysql_num_rows($res) != 0) 
			{
				// Existing TB, update it
				$res = @sql_query('
					UPDATE
						'.sql_table('plugin_tb').'
					SET 
						title     = "'.addslashes($title).'", 
						excerpt   = "'.addslashes($excerpt).'", 
						blog_name = "'.addslashes($blog_name).'", 
						timestamp = '.mysqldate(time()).'
					WHERE 
						url       = "'.addslashes($url).'" AND 
						tb_id     = "'.$tb_id.'"
				');
				
				if (!$res) {
					return 'Could not update trackback data: '.mysql_error();
				}
			} 
			else 
			{
                                if ($this->getOption('BlockSpams') == true)
                                {
                                        $return = false;
                                } 
                                else
                                {
                                        $return = true;
                                }

				// 4. SPAM check
				$spamcheck = array (
					'type'  	=> 'trackback',
					'id'        	=> $tb_id,
					'title'		=> $title,
					'excerpt'	=> $excerpt,
					'blogname'  	=> $blog_name,
					'url'		=> $url,
					'return'	=> $return,
					'live'   	=> true,
					
					/* Backwards compatibility with SpamCheck API 1*/
					'data'		=> $url . ' ' . $title . ' ' . $excerpt . ' ' . $blog_name
				);
				
				$manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
				
				if (isset($spamcheck['result']) && $spamcheck['result'] == true) 
				{
					$spam = true;
				}
				
				// 5. Content check (TO DO)
				$contents = $this->retrieveUrl ($url);
				
				if (preg_match("/(".preg_quote($_SERVER["REQUEST_URI"], '/').")|(".preg_quote($_SERVER["SERVER_NAME"], '/').")/i", $contents)) {	
					$link = true;
				}
				
				// 6. Determine if Trackback is safe...
				$block = $spam == true || $link == false;
				
				// New TB, insert it
				$query = '
					INSERT INTO 
						'.sql_table('plugin_tb').' 
					SET
						tb_id     = "'.$tb_id.'",
						block     = "'.($block ? '1' : '0').'",
						spam      = "'.($spam ? '1' : '0').'",
						link      = "'.($link ? '1' : '0').'",
						url       = "'.addslashes($url).'",
						title     = "'.addslashes($title).'",
						excerpt   = "'.addslashes($excerpt).'",
						blog_name = "'.addslashes($blog_name).'",
						timestamp = '.mysqldate(time()).'
				';
				
				$res = @sql_query($query);
				
				if (!$res) {
					return 'Could not save trackback data, possibly because of a double entry: ' . mysql_error() . $query;
				}
			}
			
			// 7. Send notification e-mail if needed
                        if (($block == false || $this->getOption('NoNotifyBlocked') == 'no')
                            && $this->getOption('Notify') == 'yes')
			{
				$destAddress = $this->getOption('NotifyEmail');
				
				$vars = array (
					'tb_id'    => $tb_id,
					'url'      => $url,
					'title'    => $title,
					'excerpt'  => $excerpt,
					'blogname' => $blog_name
				);
				
				$mailto_title = TEMPLATE::fill($this->notificationMailTitle, $vars);
				$mailto_msg   = TEMPLATE::fill($this->notificationMail, $vars);
				
				global $CONF, $DIR_LIBS;
				
				// make sure notification class is loaded
				if (!class_exists('notification'))
					include($DIR_LIBS . 'NOTIFICATION.php');
				
				$notify = new NOTIFICATION($destAddress);
				$notify->notify($mailto_title, $mailto_msg , $CONF['AdminEmail']);
			}
			
			return '';
		}	
		
		function xmlResponse($errorMessage = '') 
		{
			header('Content-Type: text/xml');
			
			echo "<","?xml version='1.0' encoding='UTF-8'?",">\n";
			echo "<response>\n";
			
			if ($errorMessage) 
				echo "\t<error>1</error>\n\t<message>",htmlspecialchars($errorMessage),"</message>\n";
			else
				echo "\t<error>0</error>\n";
			
			echo "</response>";
			exit;
		}
		
		/*
		 * Check if member may send ping (check if logged in)
		 */
		function canSendPing() {
			global $member;
			return $member->isLoggedIn() || $this->xmlrpc;
		}

	
    	/**************************************************************************************
    	 * EVENTS
	 */
		
		function event_SendTrackback($data) {
			global $manager;
		
			// Enable sending trackbacks for the XML-RPC API, otherwise we would 
			// get an error because the current user is not exactly logged in.
			$this->xmlrpc = true;
		
			$itemid = $data['tb_id'];
			$item = &$manager->getItem($itemid, 0, 0);
			if (!$item) return; // don't ping for draft & future
			if ($item['draft']) return;   // don't ping on draft items
	
			// gather some more information, needed to send the ping (blog name, etc)      
			$blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
			$blog_name 	= $blog->getName();

			$title      = $data['title'] != '' ? $data['title'] : $item['title'];
			$title 		= strip_tags($title);

			$excerpt    = $data['body'] != '' ? $data['body'] : $item['body'];
			$excerpt 	= strip_tags($excerpt);
			$excerpt    = $this->_cut_string($excerpt, 200);
	
			$CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());   
			$url = createItemLink($itemid);
	
			while (list(,$url) = each($data['urls'])) {
				$res = $this->sendPing($itemid, $title, $url, $excerpt, $blog_name, $url);
				if ($res) ACTIONLOG::add(WARNING, 'TrackBack Error:' . $res . ' (' . $url . ')');
			}
		}

		function event_RetrieveTrackback($data) {
		
			$res = sql_query('
				SELECT 
					url, 
					title, 
					UNIX_TIMESTAMP(timestamp) AS timestamp 
				FROM 
					'.sql_table('plugin_tb').' 
				WHERE 
					tb_id = '.$data['tb_id'].' AND
					block = 0
				ORDER BY 
					timestamp ASC
			');
			
			while ($row = mysql_fetch_array($res)) {
				
				$trackback = array(
					'title' => $row['title'],
					'url'   => $row['url'],
					'ip'    => ''
				);
				
				$data['trackbacks'][] = $trackback;
			}
		}


		function event_BookmarkletExtraHead($data) {
			global $NP_TB_URL;
			list ($NP_TB_URL,) = $this->getURIfromLink(requestVar('loglink'));
		} 

		function event_PrepareItemForEdit($data) {
			if (!$this->getOption('AutoXMLHttp'))
			{
				// The space between body and more is to make sure we didn't join 2 words accidently....
				$this->larray = $this->autoDiscovery($data['item']['body'].' '.$data['item']['more']);
			}
		} 

		/*
		 * After an item has been added to the database, send out a ping if requested
		 * (trackback_ping_url variable in request)
		 */
		function event_PostAddItem($data) {
			$this->pingTrackback($data);
		}
	
		function event_PreUpdateItem($data) {
			$this->pingTrackback($data);
		}

		/**
		 * Add trackback options to add item form/bookmarklet
		 */
		function event_AddItemFormExtras($data) {
		
			global $NP_TB_URL;
			
			?>
				<h3>TrackBack</h3>
				<p>
					<label for="plug_tb_url">TrackBack Ping URL:</label>
					<input type="text" value="<?php if (isSet($NP_TB_URL)) {echo $NP_TB_URL;} ?>" id="plug_tb_url" name="trackback_ping_url" size="60" /><br />
	
			<?php
				if ($this->getOption('AutoXMLHttp'))
				{
			?>
					<div id="tb_auto">
						Auto Discovered Ping URL's: <img id='tb_busy' src='<?php echo $this->getAdminURL(); ?>busy.gif' /><br />
						<input type="hidden" id="tb_url_amount" name="tb_url_amount" value="0" /> 
					</div>
					
			<?php
					$this->jsautodiscovery();
				}
			?>
				</p>
			<?php
		}

		/**
		 * Add trackback options to edit item form/bookmarklet
		 */
		function event_EditItemFormExtras($data) {
			?>
				<h3>TrackBack</h3>
				<p>
					<label for="plug_tb_url">TrackBack Ping URL:</label>
					<input type="text" value="" id="plug_tb_url" name="trackback_ping_url" size="60" /><br />
	
			<?php
				if ($this->getOption('AutoXMLHttp'))
				{
			?>
					<div id="tb_auto">
						Auto Discovered Ping URL's: <img id='tb_busy' src='<?php echo $this->getAdminURL(); ?>busy.gif' /><br />
						<input type="hidden" id="tb_url_amount" name="tb_url_amount" value="0" /> 
					</div>
					
			<?php
					$this->jsautodiscovery();
				}
				else
				{
					if (count($this->larray) > 0) 
					{
			?>
					Auto Discovered Ping URL's:<br />
			<?php
						echo '<input type="hidden" name="tb_url_amount" value="'.count($this->larray).'" />';
	
						$i = 0;
						
						while (list($url, $title) = each ($this->larray))
						{
							echo '<input type="checkbox" name="tb_url_'.$i.
								 '" value="'.$url.'" id="tb_url_'.$i.
								 '" /><label for="tb_url_'.$i.'" title="'.$url.'">'.$title.'</label><br />';
							
							$i++;
						}
					}
				}		
			?>
				</p>
			<?php
		}

		/**
		 * Insert Javascript AutoDiscovery routines
		 */
		function jsautodiscovery() 
		{
			global $CONF;
		
			?>
				<script type='text/javascript' src='<?php echo $this->getAdminURL(); ?>autodetect.php'></script>	
			<?php
		}

		/**
		 * Ping all URLs
		 */
		function pingTrackback($data) {
			global $manager, $CONF;
			
			$ping_urls_count = 0;
			$ping_urls = array();
			
			$ping_url = requestVar('trackback_ping_url');
			if ($ping_url) {
				$ping_urls[0] = $ping_url;
				$ping_urls_count++;
			}
	
			$tb_url_amount = requestVar('tb_url_amount');
			for ($i=0;$i<$tb_url_amount;$i++) {
				$tb_temp_url = requestVar('tb_url_'.$i);
				if ($tb_temp_url) {
					$ping_urls[$ping_urls_count] = $tb_temp_url;
					$ping_urls_count++;
				}
			}
	
			if ($ping_urls_count <= 0) {
				return;
			}
	
			$itemid = $data['itemid'];
			$item = &$manager->getItem($itemid, 0, 0);
			if (!$item) return; // don't ping for draft & future
			if ($item['draft']) return;   // don't ping on draft items
	
			// gather some more information, needed to send the ping (blog name, etc)      
			$blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
			$blog_name 	= $blog->getName();

			$title      = $data['title'] != '' ? $data['title'] : $item['title'];
			$title 		= strip_tags($title);

			$excerpt    = $data['body'] != '' ? $data['body'] : $item['body'];
			$excerpt 	= strip_tags($excerpt);
			$excerpt    = $this->_cut_string($excerpt, 200);
	
			$CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());   
			$url = createItemLink($itemid);
	
			// send the ping(s) (add errors to actionlog)
			for ($i=0; $i<count($ping_urls); $i++) {
				$res = $this->sendPing($itemid, $title, $url, $excerpt, $blog_name, $ping_urls[$i]);
				if ($res) ACTIONLOG::add(WARNING, 'TrackBack Error:' . $res . ' (' . $ping_urls[$i] . ')');
			}
		}

	
	
	
    	/**************************************************************************************
    	 * AUTO-DISCOVERY
		 */

		/*
		 * Auto-Discovery of TrackBack Ping URLs based on HTML story
		 */
		function autoDiscovery($text) 
		{
			$links  = $this->getPermaLinksFromText($text);
			$result = array();
	
			for ($i = 0; $i < count($links); $i++)
			{
				list ($url, $title) = $this->getURIfromLink($links[$i]);
				
				if ($url != '')
					$result[$url] = $title;
			}
			
			return $result;
		}
		
		/*
		 * Auto-Discovery of TrackBack Ping URLs based on single link
		 */
		function getURIfromLink($link) 
		{
			// Check to see if the cache contains this link
			$res = sql_query('SELECT url, title FROM '.sql_table('plugin_tb_lookup').' WHERE link="'.$link.'"');

			if ($row = mysql_fetch_array($res)) 
			{
				if ($row['title'] != '')
				{
					return array (
						$row['url'], $row['title']
					);
				}
				else
				{
					return array (
						$row['url'], $row['url']
					);
				}
			}
			
			// Retrieve RDF
			if (($rdf = $this->getRDFFromLink($link)) !== false) 
			{
				// Get PING attribute
				if (($uri = $this->getAttributeFromRDF($rdf, 'trackback:ping')) !== false) 
				{
					// Get TITLE attribute
					if (($title = $this->getAttributeFromRDF($rdf, 'dc:title')) !== false) 
					{
						// Get CREATOR attribute
						if (($author = $this->getAttributeFromRDF($rdf, 'dc:creator')) !== false) 
						{
							$title = $author. ": " . $title;
						}
	
						$uri   = $this->_decode_entities($uri);
						$title = $this->_decode_entities($title);
	
						// Store in cache
						$res = sql_query("INSERT INTO ".sql_table('plugin_tb_lookup')." (link, url, title) VALUES ('".addslashes($link)."','".addslashes($uri)."','".addslashes($title)."')");
	
						return array (
							$uri, $title
						);
					}
					else
					{
						$uri = html_entity_decode($uri, ENT_COMPAT);
	
						// Store in cache
						$res = sql_query("INSERT INTO ".sql_table('plugin_tb_lookup')." (link, url, title) VALUES ('".addslashes($link)."','".addslashes($uri)."','')");
	
						return array (
							$uri, $uri
						);
					}
				}
			}
			
			// Store in cache
			$res = sql_query("INSERT INTO ".sql_table('plugin_tb_lookup')." (link, url, title) VALUES ('".addslashes($link)."','','')");
	
			return array ('', '');
		}
	
		/*
		 * Detect links used in HTML code
		 */
		function getPermaLinksFromText($text)
		{
			$links = array();
			
			if (preg_match_all('/<a ([^>]+)>/', $text, $array, PREG_SET_ORDER))
			{
				for ($i = 0; $i < count($array); $i++)
				{
					preg_match('/href="http:\/\/(.+)"/', $array[$i][1], $matches);
					$links['http://'.$matches[1]] = 1;
				}
			}
			
			return array_keys($links);
		}
	
		/*
		 * Retrieve RDF code from external link
		 */
		function getRDFFromLink($link) 
		{
			if ($content = $this->getContents($link))
			{
				preg_match_all('/(<rdf:RDF.*?<\/rdf:RDF>)/sm', $content, $rdfs, PREG_SET_ORDER);
				
				if (count($rdfs) > 1)
				{
					for ($i = 0; $i < count($rdfs); $i++)
					{
						if (preg_match('|dc:identifier="'.preg_quote($link).'"|ms',$rdfs[$i][1])) 
						{
							return $rdfs[$i][1];
						}
					}
				}
				else
				{
					// No need to check the identifier
					return $rdfs[0][1];
				}
			}
			
			return false;
		}
	
		/**
		 * Retrieve the contents of an external (X)HTML document
		 */
		function getContents($link) {
		
			// Use cURL extention if available
			if (function_exists("curl_init"))
			{
				// Make HEAD request
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $link);
				curl_setopt($ch, CURLOPT_HEADER, true);
				curl_setopt($ch, CURLOPT_NOBODY, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				$headers = curl_exec($ch);
				curl_close($ch);
				
				// Check if the link points to a (X)HTML document
				if (preg_match('/Content-Type: (text\/html|application\/xhtml+xml)/i', $headers))
				{
					return $this->retrieveUrl ($link);
				}
				
				return false;
			}
			else
			{
				return $this->retrieveUrl ($link);
			}
		}
	
		/*
		 * Get a single attribute from RDF
		 */
		function getAttributeFromRDF($rdf, $attribute)
		{
			if (preg_match('/'.$attribute.'="([^"]+)"/', $rdf, $matches)) 
			{
				return $matches[1];
			}
			
			return false;
		}






		/**************************************************************************************/
		/* Internal helper functions for dealing with external file retrieval                 */
	
		function retrieveUrl ($url) {
			
			if (function_exists('curl_init'))
			{
				// Set options
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		
				// Retrieve response
				$raw  = curl_exec($ch);
				$info = curl_getinfo($ch);
			
				// Split into headers and contents
				$headers  = substr($raw, 0, $info['header_size']);
				$contents = substr($raw, $info['header_size']);

				curl_close($ch);
			}
			elseif ($fp = @fopen ($url, "r"))
			{
				$contents = fread($fp, 8192);
				$headers  = '';
				
				fclose($fp);
			}
			
			// Next normalize the encoding to UTF8...
			$contents = $this->_convert_to_utf8_auto($contents, $headers);
	
			return $contents;
		}
		

		/**************************************************************************************/
		/* Internal helper functions for dealing with encodings and entities                  */
	
		var $entities_cp1251 = array (
			'&#128;' 		=> '&#8364;',
			'&#130;' 		=> '&#8218;',
			'&#131;' 		=> '&#402;',	
			'&#132;' 		=> '&#8222;',	
			'&#133;' 		=> '&#8230;',	
			'&#134;' 		=> '&#8224;',	
			'&#135;' 		=> '&#8225;',	
			'&#136;' 		=> '&#710;',	
			'&#137;' 		=> '&#8240;',	
			'&#138;' 		=> '&#352;',	
			'&#139;' 		=> '&#8249;',	
			'&#140;' 		=> '&#338;',	
			'&#142;' 		=> '&#381;',	
			'&#145;' 		=> '&#8216;',	
			'&#146;' 		=> '&#8217;',	
			'&#147;' 		=> '&#8220;',	
			'&#148;' 		=> '&#8221;',	
			'&#149;' 		=> '&#8226;',	
			'&#150;' 		=> '&#8211;',	
			'&#151;' 		=> '&#8212;',	
			'&#152;' 		=> '&#732;',	
			'&#153;' 		=> '&#8482;',	
			'&#154;' 		=> '&#353;',	
			'&#155;' 		=> '&#8250;',	
			'&#156;' 		=> '&#339;',	
			'&#158;' 		=> '&#382;',	
			'&#159;' 		=> '&#376;',	
		);
	
		var $entities_default = array (
			'&quot;'		=> '&#34;',		
			'&amp;'   		=> '&#38;',	  	
			'&apos;'  		=> '&#39;',		
			'&lt;'    		=> '&#60;',		
			'&gt;'    		=> '&#62;',		
		);
	
		var $entities_latin = array (
			'&nbsp;' 		=> '&#160;',	
			'&iexcl;'		=> '&#161;',	
			'&cent;' 		=> '&#162;',	
			'&pound;' 		=> '&#163;',	
			'&curren;'		=> '&#164;',	
			'&yen;' 		=> '&#165;',	
			'&brvbar;'		=> '&#166;', 	
			'&sect;' 		=> '&#167;',	
			'&uml;' 		=> '&#168;',	
			'&copy;' 		=> '&#169;',	
			'&ordf;' 		=> '&#170;',	
			'&laquo;' 		=> '&#171;',	
			'&not;' 		=> '&#172;',	
			'&shy;' 		=> '&#173;',	
			'&reg;' 		=> '&#174;',	
			'&macr;' 		=> '&#175;',	
			'&deg;' 		=> '&#176;',	
			'&plusmn;' 		=> '&#177;',	
			'&sup2;' 		=> '&#178;',	
			'&sup3;' 		=> '&#179;', 	
			'&acute;' 		=> '&#180;',	
			'&micro;' 		=> '&#181;', 	
			'&para;' 		=> '&#182;',	
			'&middot;' 		=> '&#183;',	
			'&cedil;' 		=> '&#184;', 	
			'&sup1;' 		=> '&#185;',	
			'&ordm;' 		=> '&#186;',	
			'&raquo;' 		=> '&#187;',	
			'&frac14;' 		=> '&#188;',	
			'&frac12;' 		=> '&#189;',	
			'&frac34;' 		=> '&#190;',	
			'&iquest;' 		=> '&#191;',	
			'&Agrave;' 		=> '&#192;',	
			'&Aacute;' 		=> '&#193;',	
			'&Acirc;' 		=> '&#194;',	
			'&Atilde;' 		=> '&#195;',	
			'&Auml;' 		=> '&#196;',	
			'&Aring;' 		=> '&#197;',	
			'&AElig;' 		=> '&#198;',	
			'&Ccedil;'		=> '&#199;', 	
			'&Egrave;' 		=> '&#200;',	
			'&Eacute;' 		=> '&#201;',	
			'&Ecirc;' 		=> '&#202;',	
			'&Euml;' 		=> '&#203;',	
			'&Igrave;' 		=> '&#204;',	
			'&Iacute;' 		=> '&#205;',	
			'&Icirc;' 		=> '&#206;',	
			'&Iuml;' 		=> '&#207;', 	
			'&ETH;' 		=> '&#208;',	
			'&Ntilde;' 		=> '&#209;',	
			'&Ograve;' 		=> '&#210;',	
			'&Oacute;'		=> '&#211;',	
			'&Ocirc;' 		=> '&#212;',	
			'&Otilde;' 		=> '&#213;',	
			'&Ouml;' 		=> '&#214;',	
			'&times;' 		=> '&#215;',	
			'&Oslash;' 		=> '&#216;',	
			'&Ugrave;' 		=> '&#217;',	
			'&Uacute;' 		=> '&#218;',	
			'&Ucirc;' 		=> '&#219;',	
			'&Uuml;' 		=> '&#220;',	
			'&Yacute;' 		=> '&#221;',	
			'&THORN;' 		=> '&#222;',	
			'&szlig;' 		=> '&#223;',	
			'&agrave;' 		=> '&#224;',	
			'&aacute;' 		=> '&#225;',	
			'&acirc;' 		=> '&#226;',	
			'&atilde;' 		=> '&#227;',	
			'&auml;' 		=> '&#228;',	
			'&aring;' 		=> '&#229;',	
			'&aelig;' 		=> '&#230;',	
			'&ccedil;' 		=> '&#231;',	
			'&egrave;' 		=> '&#232;',	
			'&eacute;' 		=> '&#233;',	
			'&ecirc;' 		=> '&#234;',	
			'&euml;' 		=> '&#235;',	
			'&igrave;' 		=> '&#236;',	
			'&iacute;' 		=> '&#237;',	
			'&icirc;' 		=> '&#238;',	
			'&iuml;' 		=> '&#239;',	
			'&eth;' 		=> '&#240;',	
			'&ntilde;' 		=> '&#241;',	
			'&ograve;' 		=> '&#242;',	
			'&oacute;' 		=> '&#243;',	
			'&ocirc;' 		=> '&#244;',	
			'&otilde;' 		=> '&#245;',	
			'&ouml;' 		=> '&#246;',	
			'&divide;' 		=> '&#247;',	
			'&oslash;' 		=> '&#248;',	
			'&ugrave;' 		=> '&#249;',	
			'&uacute;' 		=> '&#250;',	
			'&ucirc;' 		=> '&#251;',	
			'&uuml;' 		=> '&#252;',	
			'&yacute;' 		=> '&#253;',	
			'&thorn;' 		=> '&#254;',	
			'&yuml;' 		=> '&#255;',	
		);	
	
		var $entities_extended = array (
			'&OElig;'		=> '&#338;',	
			'&oelig;'		=> '&#229;',	
			'&Scaron;'		=> '&#352;',	
			'&scaron;'		=> '&#353;',	
			'&Yuml;'		=> '&#376;',	
			'&circ;'		=> '&#710;',	
			'&tilde;'		=> '&#732;', 	
			'&esnp;'		=> '&#8194;',	
			'&emsp;'		=> '&#8195;',	
			'&thinsp;'		=> '&#8201;',	
			'&zwnj;'		=> '&#8204;',	
			'&zwj;'			=> '&#8205;',	
			'&lrm;'			=> '&#8206;',	
			'&rlm;'			=> '&#8207;', 	
			'&ndash;'		=> '&#8211;', 	
			'&mdash;'		=> '&#8212;',	
			'&lsquo;'		=> '&#8216;',	
			'&rsquo;'		=> '&#8217;', 	
			'&sbquo;'		=> '&#8218;',	
			'&ldquo;'		=> '&#8220;', 	
			'&rdquo;'		=> '&#8221;',	
			'&bdquo;'		=> '&#8222;',	
			'&dagger;'		=> '&#8224;',	
			'&Dagger;'		=> '&#8225;',	
			'&permil;'		=> '&#8240;',	
			'&lsaquo;'		=> '&#8249;',
			'&rsaquo;'		=> '&#8250;',
			'&euro;'		=> '&#8364;',
			'&fnof;'		=> '&#402;',	
			'&Alpha;'		=> '&#913;',	
			'&Beta;'		=> '&#914;',	
			'&Gamma;'		=> '&#915;',	
			'&Delta;'		=> '&#916;',	
			'&Epsilon;'		=> '&#917;',	
			'&Zeta;'		=> '&#918;',	
			'&Eta;'			=> '&#919;',	
			'&Theta;'		=> '&#920;',	
			'&Iota;'		=> '&#921;',	
			'&Kappa;'		=> '&#922;',	
			'&Lambda;'		=> '&#923;',	
			'&Mu;'			=> '&#924;',	
			'&Nu;'			=> '&#925;',	
			'&Xi;'			=> '&#926;',	
			'&Omicron;'		=> '&#927;',	
			'&Pi;'			=> '&#928;',	
			'&Rho;'			=> '&#929;',	
			'&Sigma;'		=> '&#931;',	
			'&Tau;'			=> '&#932;',	
			'&Upsilon;'		=> '&#933;', 	
			'&Phi;'			=> '&#934;',	
			'&Chi;'			=> '&#935;',	
			'&Psi;'			=> '&#936;',	
			'&Omega;'		=> '&#937;',	
			'&alpha;'		=> '&#945;',	
			'&beta;'		=> '&#946;',	
			'&gamma;'		=> '&#947;',	
			'&delta;'		=> '&#948;',	
			'&epsilon;'		=> '&#949;',	
			'&zeta;'		=> '&#950;',	
			'&eta;'			=> '&#951;',	
			'&theta;'		=> '&#952;',	
			'&iota;'		=> '&#953;',	
			'&kappa;'		=> '&#954;',	
			'&lambda;'		=> '&#955;',	
			'&mu;'			=> '&#956;',	
			'&nu;'			=> '&#957;',	
			'&xi;'			=> '&#958;',	
			'&omicron;'		=> '&#959;',	
			'&pi;'			=> '&#960;',	
			'&rho;'			=> '&#961;',	
			'&sigmaf;'		=> '&#962;',	
			'&sigma;'		=> '&#963;',	
			'&tau;'			=> '&#964;',	
			'&upsilon;'		=> '&#965;', 	
			'&phi;'			=> '&#966;',	
			'&chi;'			=> '&#967;',	
			'&psi;'			=> '&#968;',	
			'&omega;'		=> '&#969;',	
			'&thetasym;'	=> '&#977;',	
			'&upsih;'		=> '&#978;',	
			'&piv;'			=> '&#982;',	
			'&bull;'		=> '&#8226;',	
			'&hellip;'		=> '&#8230;',	
			'&prime;'		=> '&#8242;',	
			'&Prime;'		=> '&#8243;',	
			'&oline;'		=> '&#8254;', 	
			'&frasl;'		=> '&#8260;',	
			'&weierp;'		=> '&#8472;', 	
			'&image;'		=> '&#8465;', 	
			'&real;'		=> '&#8476;',	
			'&trade;'		=> '&#8482;', 	
			'&alefsym;' 	=> '&#8501;', 	
			'&larr;'		=> '&#8592;', 	
			'&uarr;'		=> '&#8593;', 	
			'&rarr;'		=> '&#8594;',	
			'&darr;'		=> '&#8595;', 	
			'&harr;'		=> '&#8596;',	
			'&crarr;'		=> '&#8629;',	
			'&lArr;'		=> '&#8656;',	
			'&uArr;'		=> '&#8657;', 	
			'&rArr;'		=> '&#8658;', 	
			'&dArr;'		=> '&#8659;', 	
			'&hArr;'		=> '&#8660;', 	
			'&forall;'		=> '&#8704;', 	
			'&part;'		=> '&#8706;', 	
			'&exist;'		=> '&#8707;', 	
			'&empty;'		=> '&#8709;', 	
			'&nabla;'		=> '&#8711;', 	
			'&isin;'		=> '&#8712;', 	
			'&notin;'		=> '&#8713;', 	
			'&ni;'			=> '&#8715;', 	
			'&prod;'		=> '&#8719;', 	
			'&sum;'			=> '&#8721;', 	
			'&minus;'		=> '&#8722;', 	
			'&lowast;'		=> '&#8727;', 	
			'&radic;'		=> '&#8730;', 	
			'&prop;'		=> '&#8733;', 	
			'&infin;'		=> '&#8734;', 	
			'&ang;'			=> '&#8736;', 	
			'&and;'			=> '&#8743;', 	
			'&or;'			=> '&#8744;', 	
			'&cap;'			=> '&#8745;', 	
			'&cup;'			=> '&#8746;', 	
			'&int;'			=> '&#8747;', 	
			'&there4;'		=> '&#8756;', 	
			'&sim;'			=> '&#8764;', 	
			'&cong;'		=> '&#8773;', 	
			'&asymp;'		=> '&#8776;', 	
			'&ne;'			=> '&#8800;', 	
			'&equiv;'		=> '&#8801;', 	
			'&le;'			=> '&#8804;', 	
			'&ge;'			=> '&#8805;', 	
			'&sub;'			=> '&#8834;', 	
			'&sup;'			=> '&#8835;', 	
			'&nsub;'		=> '&#8836;', 	
			'&sube;'		=> '&#8838;', 	
			'&supe;'		=> '&#8839;', 	
			'&oplus;'		=> '&#8853;', 	
			'&otimes;'  	=> '&#8855;', 	
			'&perp;'		=> '&#8869;', 	
			'&sdot;'		=> '&#8901;', 	
			'&lceil;'		=> '&#8968;', 	
			'&rceil;'		=> '&#8969;', 	
			'&lfloor;'		=> '&#8970;', 	
			'&rfloor;'		=> '&#8971;', 	
			'&lang;'		=> '&#9001;', 	
			'&rang;'		=> '&#9002;', 	
			'&loz;'			=> '&#9674;', 	
			'&spades;'		=> '&#9824;', 	
			'&clubs;'		=> '&#9827;', 	
			'&hearts;'		=> '&#9829;', 	
			'&diams;'		=> '&#9830;', 	
		);
	
		function _detect_encoding($string)
		{
			if (!ereg("[\x80-\xFF]", $string) && !ereg("\x1B", $string))
				return 'US-ASCII';
			
			if (!ereg("[\x80-\xFF]", $string) && ereg("\x1B", $string))
				return 'ISO-2022-JP';
				
			if (preg_match("/^([\x01-\x7F]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF][\x80-\xBF])+$/", $string) == 1)
				return 'UTF-8';
			
			if (preg_match("/^([\x01-\x7F]|\x8E[\xA0-\xDF]|\x8F[xA1-\xFE][\xA1-\xFE]|[\xA1-\xFE][\xA1-\xFE])+$/", $string) == 1)
				return 'EUC-JP';

			if (preg_match("/^([\x01-\x7F]|[\xA0-\xDF]|[\x81-\xFC][\x40-\xFC])+$/", $string) == 1)
				return 'Shift_JIS';
				
			return 'ISO-8859-1';
		}
	
		function _convert_to_utf8($contents, $encoding)
		{
			$done = false;
			
			if (!$done && function_exists('iconv'))  
			{
			
				$result = @iconv($encoding, 'UTF-8//IGNORE', $contents);
	
				if ($result) 
				{
					$contents = $result;
					$done = true;
				}
			}
			
			if(!$done && function_exists('mb_convert_encoding')) 
			{
				@mb_substitute_character('none');
				$result = @mb_convert_encoding($contents, 'UTF-8', $encoding );
	
				if ($result) 
				{
					$contents = $result;
				}
			}
		
			return $contents;
		}
		
		function _convert_to_utf8_auto($contents, $headers = '')
		{
			/* IN:  string in unknown encoding, headers received during transfer
			 * OUT: string in UTF-8 encoding
			 */
	
			$str = substr($contents, 0, 4096);
			$len = strlen($str);
			$pos = 0;
			$out = '';
			
			while ($pos < $len)
			{
				$ord = ord($str[$pos]);
				
				if ($ord > 32 && $ord < 128)
					$out .= $str[$pos];
					
				$pos++;
			}
	
			// Detection of encoding, check headers
			if (preg_match ("/;\s*charset=([^\n]+)/is", $headers, $regs))
				$encoding = strtoupper(trim($regs[1]));
	
			// Then check meta inside document
			if (preg_match ("/;\s*charset=([^\"']+)/is", $out, $regs))
				$encoding = strtoupper(trim($regs[1]));
				
			// Then check xml declaration
			if (preg_match("/<\?xml.+encoding\s*=\s*[\"|']([^\"']+)[\"|']\s*\?>/i", $out, $regs))
				$encoding = strtoupper(trim($regs[1]));		
	
			// Converts
			return $this->_convert_to_utf8($contents, $encoding);
		}
		
		function _decode_entities($string)
		{
			/* IN:  string in UTF-8 containing entities
			 * OUT: string in UTF-8 without entities
			 */
			 
			/// Convert all hexadecimal entities to decimal entities
			$string = preg_replace('/&#[Xx]([0-9A-Fa-f]+);/e', "'&#'.hexdec('\\1').';'", $string);		

			// Deal with invalid cp1251 numeric entities
			$string = strtr($string, $this->entities_cp1251);

			// Convert all named entities to numeric entities
			$string = strtr($string, $this->entities_default);
			$string = strtr($string, $this->entities_latin);
			$string = strtr($string, $this->entities_extended);

			// Convert all numeric entities to UTF-8
			$string = preg_replace('/&#([0-9]+);/e', "'&#x'.dechex('\\1').';'", $string);
			$string = preg_replace('/&#[Xx]([0-9A-Fa-f]+);/e', "NP_Trackback::_hex_to_utf8('\\1')", $string);		

			return $string;
		}
	
		function _hex_to_utf8($s)
		{
			/* IN:  string containing one hexadecimal Unicode character
			 * OUT: string containing one binary UTF-8 character
			 */
			 
			$c = hexdec($s);
		
			if ($c < 0x80) {
				$str = chr($c);
			}
			else if ($c < 0x800) {
				$str = chr(0xC0 | $c>>6) . chr(0x80 | $c & 0x3F);
			}
			else if ($c < 0x10000) {
				$str = chr(0xE0 | $c>>12) . chr(0x80 | $c>>6 & 0x3F) . chr(0x80 | $c & 0x3F);
			}
			else if ($c < 0x200000) {
				$str = chr(0xF0 | $c>>18) . chr(0x80 | $c>>12 & 0x3F) . chr(0x80 | $c>>6 & 0x3F) . chr(0x80 | $c & 0x3F);
			}
			
			return $str;
		} 		

		function _utf8_to_entities($string)
		{
			/* IN:  string in UTF-8 encoding
			 * OUT: string consisting of only characters ranging from 0x00 to 0x7f, 
			 *      using numeric entities to represent the other characters 
			 */
			 
			$len = strlen ($string);
			$pos = 0;
			$out = '';
				
			while ($pos < $len) 
			{
				$ascii = ord (substr ($string, $pos, 1));
				
				if ($ascii >= 0xF0) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xF0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
					$byte[3] = ord(substr ($string, $pos + 2, 1)) - 0x80;
					$byte[4] = ord(substr ($string, $pos + 3, 1)) - 0x80;
	
					$char_code = ($byte[1] << 18) + ($byte[2] << 12) + ($byte[3] << 6) + $byte[4];
					$pos += 4;
				}
				elseif (($ascii >= 0xE0) && ($ascii < 0xF0)) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xE0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
					$byte[3] = ord(substr ($string, $pos + 2, 1)) - 0x80;
	
					$char_code = ($byte[1] << 12) + ($byte[2] << 6) + $byte[3];
					$pos += 3;
				}
				elseif (($ascii >= 0xC0) && ($ascii < 0xE0)) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xC0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
	
					$char_code = ($byte[1] << 6) + $byte[2];
					$pos += 2;
				}
				else 
				{
					$char_code = ord(substr ($string, $pos, 1));
					$pos += 1;
				}
	
				if ($char_code < 0x80)
					$out .= chr($char_code);
				else
					$out .=  '&#'. str_pad($char_code, 5, '0', STR_PAD_LEFT) . ';';
			}
	
			return $out;	
		}			

		function _utf8_to_javascript($string)
		{
			/* IN:  string in UTF-8 encoding
			 * OUT: string consisting of only characters ranging from 0x00 to 0x7f, 
			 *      using javascript escapes to represent the other characters 
			 */
			 
			$len = strlen ($string);
			$pos = 0;
			$out = '';
				
			while ($pos < $len) 
			{
				$ascii = ord (substr ($string, $pos, 1));
				
				if ($ascii >= 0xF0) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xF0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
					$byte[3] = ord(substr ($string, $pos + 2, 1)) - 0x80;
					$byte[4] = ord(substr ($string, $pos + 3, 1)) - 0x80;
	
					$char_code = ($byte[1] << 18) + ($byte[2] << 12) + ($byte[3] << 6) + $byte[4];
					$pos += 4;
				}
				elseif (($ascii >= 0xE0) && ($ascii < 0xF0)) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xE0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
					$byte[3] = ord(substr ($string, $pos + 2, 1)) - 0x80;
	
					$char_code = ($byte[1] << 12) + ($byte[2] << 6) + $byte[3];
					$pos += 3;
				}
				elseif (($ascii >= 0xC0) && ($ascii < 0xE0)) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xC0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
	
					$char_code = ($byte[1] << 6) + $byte[2];
					$pos += 2;
				}
				else 
				{
					$char_code = ord(substr ($string, $pos, 1));
					$pos += 1;
				}
	
				if ($char_code < 0x80)
					$out .= chr($char_code);
				else
					$out .=  '\\u'. str_pad(dechex($char_code), 4, '0', STR_PAD_LEFT);
			}
	
			return $out;	
		}			
				
		function _cut_string($string, $dl = 0) {
		
			$defaultLength = $dl > 0 ? $dl : $this->getOption('defaultLength');
			
			if ($defaultLength < 1)
				return $string;
	
			$border    = 6;
			$count     = 0;
			$lastvalue = 0;
	
  			for ($i = 0; $i < strlen($string); $i++)
       		{
       			$value = ord($string[$i]);
	   
	   			if ($value > 127)
           		{
           			if ($value >= 192 && $value <= 223)
               			$i++;
           			elseif ($value >= 224 && $value <= 239)
               			$i = $i + 2;
           			elseif ($value >= 240 && $value <= 247)
               			$i = $i + 3;
					
					if ($lastvalue <= 223 && $value >= 223 && 
						$count >= $defaultLength - $border)
					{
						return substr($string, 0, $i) . '...';
					}

					// Chinese and Japanese characters are
					// wider than Latin characters
					if ($value >= 224)
						$count++;
					
           		}
				elseif ($string[$i] == '/' || $string[$i] == '?' ||
						$string[$i] == '-' || $string[$i] == ':' ||
						$string[$i] == ',' || $string[$i] == ';')
				{
					if ($count >= $defaultLength - $border)
						return substr($string, 0, $i) . '...';
				}
				elseif ($string[$i] == ' ')
				{
					if ($count >= $defaultLength - $border)
						return substr($string, 0, $i) . '...';
				}
				
				if ($count == $defaultLength)
					return substr($string, 0, $i + 1) . '...';
      
	  			$lastvalue = $value;
       			$count++;
       		}

			return $string;
		}
		
		/**
		  * Generate TB key
		  */
		function getTbKey() {
			srand((double)microtime()*1000000);
			$key = md5(uniqid(rand(), true));
			
			$query = 'INSERT INTO ' . sql_table('plugin_tb_key') . ' (tbkey, time) VALUES (\'' . $key . '\', \'' . date('Y-m-d H:i:s',time()) . '\')';
			sql_query($query);
			return $key;
		}

		/**
		  * Clear expired key
		  */
		function _clearExpiredKey() {
			$boundary = time() - (3 * 60 * 60);	// delete all keys older than 3 hours
			sql_query('DELETE FROM ' . sql_table('plugin_tb_key') . ' WHERE time < \'' . date('Y-m-d H:i:s',$boundary) . '\'');
		}

		/**
		  * Check if a key exists
		  */
		function _findKey($key) {
			$res = sql_query('SELECT COUNT(*) AS result FROM ' . sql_table('plugin_tb_key') . ' WHERE tbkey=\'' . $key . '\'');
			if (mysql_num_rows($res) == 1) {
				return  true;
			} else {
				return false;
			}
		}

		/**
		  * Delete a key
		  */
		function _deleteKey($key) {
			sql_query('DELETE FROM ' . sql_table('plugin_tb_key') . ' WHERE tbkey=\'' . $key .'\'');
		}


		/**************************************************************************************/
		/* Plugin API calls, for installation, configuration and setup                        */
	
		function getName()   	  { 		return 'TrackBack';   }
		function getAuthor() 	  { 		return 'rakaz, mod by admun (Edmond Hui), and others'; }
		function getURL()    	  { 		return 'http://edmondhui.homeip.net/nudn'; }
		function getVersion()	  { 		return '2.1.1'; }
		function getDescription() { 		return 'Send trackbacks to other weblogs and receive tracbacks from others.'; }
	
		function getTableList()   { 		
			// return array(sql_table("plugin_tb"), sql_table("plugin_tb_lookup"), sql_table("plugin_tb_key")); 
			return array(sql_table("plugin_tb"), sql_table("plugin_tb_key")); 
		}
		function getEventList()   { 		return array('QuickMenu','PostAddItem','AddItemFormExtras','EditItemFormExtras','PreUpdateItem','PrepareItemForEdit', 'BookmarkletExtraHead', 'RetrieveTrackback', 'SendTrackback', 'InitSkinParse'); }
		function getMinNucleusVersion() {	return 200; }
	
		function supportsFeature($feature) {
			switch($feature) {
				case 'SqlTablePrefix':
					return 1;
				case 'HelpPage':
					return 1;
				default:
					return 0;
			}
		}

	
		function hasAdminArea() { 			return 1; }

		function event_QuickMenu(&$data) {
			global $member, $nucleus, $blogid;
			
			// only show to admins
			if (!$member->isLoggedIn() || !$member->isAdmin()) return;

			array_push(
				$data['options'],
				array(
					'title' => 'Trackback',
					'url' => $this->getAdminURL(),
					'tooltip' => 'Manage your trackbacks'
				)
			);
		}
			
		function install() {
			$this->createOption('AcceptPing',  'Accept pings','yesno','yes');
			$this->createOption('SendPings',   'Allow sending pings','yesno','yes');
			$this->createOption('AutoXMLHttp', 'Auto-detect Trackback URLs as you type', 'yesno', 'yes');
			$this->createOption('CheckIDs',	   'Only allow valid itemids as trackback-ids','yesno','yes');

			$this->createOption('tplHeader',   'Header', 'textarea', "<div class='tb'>\n\t<div class='head'>Trackback</div>\n\n");
			$this->createOption('tplEmpty',	   'Empty', 'textarea', "\t<div class='empty'>\n\t\tThere are currently no trackbacks for this item.\n\t</div>\n\n");
			$this->createOption('tplItem',	   'Item', 'textarea', "\t<div class='item'>\n\t\t<div class='name'><%name%></div>\n\t\t<div class='body'>\n\t\t\t<a href='<%url%>' rel='nofollow'><%title%>:</a> <%excerpt%>\n\t\t</div>\n\t\t<div class='date'>\n\t\t\t<%date%>\n\t\t</div>\n\t</div>\n\n");
			$this->createOption('tplFooter',   'Footer', 'textarea', "\t<div class='info'>\n\t\tUse this <a href='<%action%>'>TrackBack url</a> to ping this item (right-click, copy link target).\n\t\tIf your blog does not support Trackbacks you can manually add your trackback by using <a href='<%form%>' onclick='window.open(this.href, \"trackback\", \"scrollbars=yes,width=600,height=340,left=10,top=10,status=yes,resizable=yes\"); return false;'>this form</a>.\n\t</div>\n</div>");

			$this->createOption('tplTbNone',   'Trackback count (none)', 'text', "No Trackbacks");
			$this->createOption('tplTbOne',    'Trackback count (one)', 'text', "1 Trackback");
			$this->createOption('tplTbMore',   'Trackback count (more)', 'text', "<%number%> Trackbacks");
			$this->createOption('dateFormat',  'Date format', 'text', "%e/%m/%g");

			$this->createOption('Notify',	   'Send e-mail notification on ping receipt','yesno','no');
                        $this->createOption('NoNotifyBlocked',   'Don\'t send e-mail notification for blocked pings','yesno','no');
			$this->createOption('NotifyEmail', 'Which e-mail address to send these notification to?','text','');	

			$this->createOption('DropTable',   'Clear the database when uninstalling','yesno','no');
			$this->createOption('BlockSpams',   'Blocked Spams directly?','yesno','yes');


			/* Create tables */
			sql_query("
				CREATE TABLE IF NOT EXISTS 
					".sql_table('plugin_tb')."
				(
					`id`        INT(11)         NOT NULL       AUTO_INCREMENT,
					`tb_id`     INT(11)         NOT NULL, 
					`url`       TEXT            NOT NULL, 
					`block`     TINYINT(4)      NOT NULL, 
					`spam`      TINYINT(4)      NOT NULL, 
					`link`      TINYINT(4)      NOT NULL, 
					`title`     TEXT, 	
					`excerpt`   TEXT, 
					`blog_name` TEXT, 
					`timestamp` DATETIME, 
					
					PRIMARY KEY (`id`)
				);
			");
			
			sql_query("
				CREATE TABLE IF NOT EXISTS
					".sql_table('plugin_tb_key')."
				(
					`tbkey` VARCHAR(40) NOT NULL default '',
  					`time` datetime NOT NULL default '0000-00-00 00:00:00',
					PRIMARY KEY  (tbkey)
				)
			");

			sql_query("
				CREATE TABLE IF NOT EXISTS
					".sql_table('plugin_tb_lookup')."
				(
					`link`      TEXT            NOT NULL, 
					`url`       TEXT            NOT NULL, 
					`title`     TEXT, 
					
					PRIMARY KEY (`link` (100))
				)
			");
		}
	
		function uninstall() {
			if ($this->getOption('DropTable') == 'yes') {
	 			sql_query ('DROP TABLE '.sql_table('plugin_tb'));
				sql_query ('DROP TABLE '.sql_table('plugin_tb_lookup'));
				sql_query ('DROP TABLE '.sql_table('plugin_tb_key'));
			}
		}

		function init() {
			$this->notificationMail 	 = "Your weblog received a new trackback from <%blogname%> for ID <%tb_id%>. Below are the full details:\n\nURL:\t<%url%>\nTitle:\t<%title%>\nExcerpt:\t<%excerpt%>\nBlogname:\t<%blogname%>";
			$this->notificationMailTitle = "New Trackback received for ID <%tb_id%>";
		}

		/**
  		  * Show a 'ping site' form, where a ping URL can be entered for an item
		  */
		function showPingForm() {
			global $manager, $CONF;
			// get values to put in the fields
			$ping_url = requestVar('ping_url');
			$itemid = intRequestVar('itemid');
			$item = &$manager->getItem($itemid, 0, 0);
			if ($item) {
				$blog = &$manager->getBlog(getBlogIDFromItemID($itemid));

				$blog_name = $blog->getName();
				$title = strip_tags($item['title']);
				$excerpt = shorten(strip_tags($item['body']), 200, '...');
				if (!$CONF['ItemURL']) $CONF['ItemURL'] = $blog->getURL();
				$url = createItemLink($itemid);
			}
			// generate the page

		   ?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html>
		<head>
			<title>TrackBack Ping</title>
			<link rel="stylesheet" type="text/css" href="<?php echo $CONF['AdminURL']?>styles/bookmarklet.css" />
		</head>
		<body>
		<h1>TrackBack Ping</h1>
		<form method="post" action="<?php echo $CONF['ActionURL'] ?>"><div>
		<input type="hidden" name="itemid" value="<?php echo $itemid?>" />
		<input type="hidden" name="action" value="plugin" />
		<input type="hidden" name="name" value="TrackBack" />
		<input type="hidden" name="type" value="sendping" />
		<input type="hidden" name="redirectTo" value="<?php echo $url; ?>" />
		<table><tr>
			<td>URL</td>
			<td><input type="text" value="<?php echo htmlspecialchars($url)?>" name="url" size="60" /></td></tr><tr>
			<td>Title</td>
			<td><input type="text" value="<?php echo htmlspecialchars($title)?>" name="title" size="60" /></td></tr><tr>
			<td>Excerpt</td>
			<td><textarea name="excerpt" cols="40" rows="5"><?php echo htmlspecialchars($excerpt)?></textarea></td>
		</tr><tr>
			<td>Blog Name</td>
			<td><input type="text" value="<?php echo htmlspecialchars($blog_name)?>" name="blog_name" size="60" /></td>
		</tr><tr>
			<td>Ping URL</td>
			<td><input type="text" value="<?php echo htmlspecialchars($ping_url)?>" name="ping_url" size="60" /></td>
		</tr><tr>
		<?php
				$autoDiscoveryRes = $this->autoDiscovery($item['body'].$item['more']);
				if (count($autoDiscoveryRes) > 0) {

			?>
			<td>Auto Discovered URL</td>
			<td>
		<?php
				echo '<input type="hidden" name="tb_url_amount" value="' . count($autoDiscoveryRes) . '" />';
				for($i = 0;$i < count($autoDiscoveryRes);$i++) {
				echo '<input type="checkbox" name="tb_url_' . $i . '" value="' . $autoDiscoveryRes[$i] . '" id="tb_url_' . $i . '" /><label for="tb_url_' . $i . '">' . $autoDiscoveryRes[$i] . '</label><br />';
			}
		?>
		       </td>
		<?php
		    }

		    ?>
		</tr><tr>
			<td>Send Ping</td>
			<td><input type="submit" value="Send Ping" />
		</tr></table>
		</div></form>

		</body>
		</html>
		<?php

		}

		function getPingFormLink($itemid) {
			global $CONF;
			return $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack&amp;type=pingform&amp;itemid='.$itemid;
		}
	}

?>
