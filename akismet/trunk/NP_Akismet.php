<?php

   /* ==========================================================================================
    * Akismet for Nucleus CMS
    * Copyright 2005-2007, Niels Leenheer and Matt Mullenweg
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

class NP_Akismet extends NucleusPlugin {
	function getName() 			{ return 'Akismet'; }
	function getAuthor()  	  	{ return 'Niels Leenheer'; }
	function getURL()  			{ return 'http://www.rakaz.nl'; }
	function getVersion() 	  	{ return '0.7'; }
	function getDescription() 	{ return 'Check for spam with Akismet.com';	}
	
	function supportsFeature($what) {
		switch($what) {
		    case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	
	function getEventList() {
		return array('SpamCheck', 'SpamMark', 'SpamPlugin');
	}

	function install() {
		$this->createOption('ApiKey', 'Wordpress.com API key', 'text', '');
	    $this->createOption('Privacy', 'Privacy protection', 'select', 'normal', 'Paranoid|high|Moderate|normal|None|low');
	}
	
	function event_SpamPlugin(&$data) {
		$data['spamplugin'][] = array (
			'name' => $this->getName(),
			'version' => $this->getVersion()
		);
	}
	
	function event_SpamCheck(&$data) {
		global $CONF, $manager;
		
		if (isset($data['spamcheck']['result']) && $data['spamcheck']['result'] == true) 
			return;
		
		if ($data['spamcheck']['type'] == 'comment' || $data['spamcheck']['type'] == 'trackback') 
		{
			if ($data['spamcheck']['type'] == 'comment') 
				$comment = array (
					'blog' => $CONF['IndexURL'],
					'permalink' => $this->_prepareLink($CONF['IndexURL'], createItemLink($data['spamcheck']['id'])),
					'comment_type' => $data['spamcheck']['type'],
					'comment_author' => $data['spamcheck']['author'], 
					'comment_author_email' => $data['spamcheck']['email'], 
					'comment_author_url' => $data['spamcheck']['url'], 
					'comment_content' => $data['spamcheck']['body']
				);
			
			if ($data['spamcheck']['type'] == 'trackback') 
				$comment = array (
					'blog' => $CONF['IndexURL'],
					'permalink' => $this->_prepareLink($CONF['IndexURL'], createItemLink($data['spamcheck']['id'])),
					'comment_type' => $data['spamcheck']['type'],
					'comment_author' => $data['spamcheck']['blogname'], 
					'comment_author_url' => $data['spamcheck']['url'], 
					'comment_content' => $data['spamcheck']['title'] . ' ' . $data['spamcheck']['excerpt']
				);
			
			if (isset($data['spamcheck']['live']) && $data['spamcheck']['live'] == true)
			{
				$comment['user_ip'] = $_SERVER['REMOTE_ADDR'];
				$comment['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
				$comment['referrer'] = $_SERVER['HTTP_REFERER'];
				
				if ($this->getOption('Privacy') == 'low')
				{
					$ignore = array (
						'HTTP_COOKIE'
					);
					
					foreach ($_SERVER as $key => $value)
						if (!in_array($key, $ignore))
							$comment[$key] = $value;
				}
				
				if ($this->getOption('Privacy') == 'normal')
				{
					$approved = array (
						'HTTP_HOST', 'HTTP_USER_AGENT', 'HTTP_ACCEPT',
						'HTTP_ACCEPT_LANGUAGE', 'HTTP_ACCEPT_ENCODING',
						'HTTP_ACCEPT_CHARSET', 'HTTP_KEEP_ALIVE', 'HTTP_REFERER',
						'HTTP_CONNECTION', 'HTTP_FORWARDED', 'HTTP_FORWARDED_FOR',
						'HTTP_X_FORWARDED', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP',
						'REMOTE_ADDR', 'REMOTE_HOST', 'REMOTE_PORT', 'SERVER_PROTOCOL',
						'REQUEST_METHOD'
					);
					
					foreach ($_SERVER as $key => $value)
						if (in_array($key, $approved))
							$comment[$key] = $value;
				}
			}
			
			$query_string = '';
			
			foreach ($comment as $key => $part)
				$query_string .= $key . '=' . urlencode(stripslashes($part)) . '&';
			
			$response = $this->_PostHTTP(
				$query_string, 
				$this->getOption('ApiKey') . '.rest.akismet.com', 
				'/1.1/comment-check', 
				80
			);
			
			if (is_array($response)) {
				$data['spamcheck']['result'] = ($response[1] == 'true');
				
				if ($response[1] == 'true') {
					$data['spamcheck']['plugin'] = $this->getName();
					$data['spamcheck']['message'] = 'Marked as spam by Akismet.com';
				}
			}
			
			$manager->notify('AkismetResult', array ('id' => $data['spamcheck']['id'], 'status' => (int) $data['spamcheck']['result']));
		}
	}
	
	function event_SpamMark(&$data) {
		global $CONF;
		
		if ($data['spammark']['type'] == 'comment' || $data['spammark']['type'] == 'trackback') 
		{
			if ($data['spammark']['type'] == 'comment') 
				$comment = array (
					'comment_post_ID' => $data['spammark']['id'], 
					'comment_author' => $data['spammark']['author'], 
					'comment_author_email' => $data['spammark']['email'], 
					'comment_author_url' => $data['spammark']['url'], 
					'comment_content' => $data['spammark']['body'],
					'comment_type' => '',
					'blog' => $CONF['IndexURL']
				);
			
			if ($data['spammark']['type'] == 'trackback') 
				$comment = array (
					'comment_post_ID' => $data['spammark']['id'], 
					'comment_author' => $data['spammark']['blogname'], 
					'comment_author_url' => $data['spammark']['url'], 
					'comment_content' => $data['spammark']['title'] . ' ' . $data['spammark']['excerpt'],
					'comment_type' => '',
					'blog' => $CONF['IndexURL']
				);
			
			$query_string = '';
			
			foreach ($comment as $key => $part)
				$query_string .= $key . '=' . urlencode(stripslashes($part)) . '&';
			
			if ($data['spammark']['result'] == true)
			{
				$response = $this->_PostHTTP(
					$query_string, 
					$this->getOption('ApiKey') . '.rest.akismet.com', 
					'/1.1/submit-spam', 
					80
				);
			}
			else
			{
				$response = $this->_PostHTTP(
					$query_string, 
					$this->getOption('ApiKey') . '.rest.akismet.com', 
					'/1.1/submit-ham', 
					80
				);
			}
		}		
	}
	
	function _PostHTTP($request, $host, $path, $port = 80) {
		global $nucleus;
		
		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . _CHARSET . "\r\n";
		$http_request .= "Content-Length: " . strlen($request) . "\r\n";
		$http_request .= "User-Agent: Nucleus/" . substr($nucleus['version'], 1) . " | Akismet/" . $this->getVersion() . "\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;
		
		$response = '';
		if( false !== ( $fs = @fsockopen($host, $port, $errno, $errstr, 3) ) ) {
			fwrite($fs, $http_request);
			while ( !feof($fs) )
				$response .= fgets($fs, 1160); // One TCP-IP packet
			fclose($fs);
			
			$response = explode("\r\n\r\n", $response, 2);
		}
		
		return $response;
	}

	function _prepareLink($base, $url) {
		if (substr($url, 0, 7) == 'http://')
			return $url;
		else
			return $base . $url;
	}
}

?>