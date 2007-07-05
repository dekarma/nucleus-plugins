<?php
/**
 * NP_Twitter Plugin for NucleusCMS
 *
 * History
 * v0.1
 *   - initialize version
 * v0.2
 *   - improve update encoding
 *   - rename %%TWITTERD%% to %%TDATE%%
 *   - fix option label
 *   - twitthis support
 *   - fix GMT timestamp bug
 *   - tweet on new post
 * v0.3
 *   - added icon formatting override
 *   - fixed draft post tweet (which should not)
 *   - changed page=xx to tpage=
 *   - fix timezone bug 
 *   - tweetlet
 * v0.4
 *   - fix date under flow caused by < GMT timezone
 *   - handle time/date overflow caused by > GMT timezone
 *   - fix htmlspecialchars_decode support for PHP4
 *   - curl_setopt_array php4 support
 *   - no tweet on future post
 * v0.5
 *   - fixed future post tweet SQL check
 *   - fixed update tweet login check.
 *   - added title, excerpt in auto tweet (%t, %e)
 *
admun TODO

- add date sepecrator in tweets archive
- fixed &quot;
- link to @who
- add delete tweet
- add archive (only my tweet), and replies (search for @admun)
- use JustPost event for 3.3

javascript:location.href='http://edmondhui.homeip.net/blog/action.php?action=plugin&name=Twitter&op=tweetlet&url='+encodeURIComponent(location.href)+'&text='+encodeURIComponent(document.title)

- char counter on update box
- how long ago, highlight recent tweets (via CSS)
- multiple-tweet template
- show badge for login member
- email, sms, wap

- per member accounts... or via member options?
- search
- admin menu to add/mod/del account, update tweet??
- daily tweets digest
- direct message
- make XML parser classes
 */

if (!function_exists('curl_setopt_array')) {
   function curl_setopt_array(&$ch, $curl_options)
   {
       foreach ($curl_options as $option => $value) {
           if (!curl_setopt($ch, $option, $value)) {
               return false;
           }
       }
       return true;
   }
}

if ( !function_exists('htmlspecialchars_decode') )
{
    function htmlspecialchars_decode($text)
    {
        return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
    }
}

if (!function_exists('sql_table')) {
	function sql_table($name){
		return 'nucleus_'.$name;
	}
}

// XML processing code design from http://www.kirupa.com/web/xml_php_parse_intermediate.htm

// global temp data
$current_tag = "";
$counter = 0;
$parsed_arr = array();
$uid = 0;

// friends/followers parser
class f_user{
	var $id, $name, $sname, $loc, $desc, $purl, $url;
}

function fl_contents($parser, $data) {
	global $current_tag, $parsed_arr, $counter;
	switch($current_tag){
		case "*USERS*USER*ID":
			$counter++;
			$parsed_arr[$counter] = new f_user();
			$parsed_arr[$counter]->id = $data;
			break;
		case "*USERS*USER*NAME":
			$parsed_arr[$counter]->name .= $data;
			break;
		case "*USERS*USER*SCREEN_NAME":
			$parsed_arr[$counter]->sname .= $data;
			break;
		case "*USERS*USER*LOCATION":
			$parsed_arr[$counter]->loc .= $data;
			break;
		case "*USERS*USER*DESCRIPTION":
			$parsed_arr[$counter]->desc .= $data;
			break;
		case "*USERS*USER*PROFILE_IMAGE_URL":
			$parsed_arr[$counter]->purl .= $data;
			break;
		case "*USERS*USER*URL":
			$parsed_arr[$counter]->url .= $data;
			break;
	}
}

function fd_contents($parser, $data) {
	global $current_tag, $parsed_arr, $counter;
	switch($current_tag){
		case "*USERS*USER*ID":
			$counter++;
			$parsed_arr[$counter] = new f_user();
			$parsed_arr[$counter]->id = $data;
			break;
		case "*USERS*USER*NAME":
			$parsed_arr[$counter]->name .= $data;
			break;
		case "*USERS*USER*SCREEN_NAME":
			$parsed_arr[$counter]->sname .= $data;
			break;
		case "*USERS*USER*LOCATION":
			$parsed_arr[$counter]->loc .= $data;
			break;
		case "*USERS*USER*DESCRIPTION":
			$parsed_arr[$counter]->desc .= $data;
			break;
		case "*USERS*USER*PROFILE_IMAGE_URL":
			$parsed_arr[$counter]->purl .= $data;
			break;
		case "*USERS*USER*URL":
			$parsed_arr[$counter]->url .= $data;
			break;
	}
}

// users parser
function u_contents($parser, $data) {
	global $current_tag, $uid;
	switch($current_tag){
		case "*USER*ID":
			$uid = $data;
			break;
	}
}

// friend_timeline parser
class ft_status{
	var $created, $text, $tid, $uid, $name, $screen_name, $purl;
}

function ft_contents($parser, $data) {
	global $current_tag, $parsed_arr, $counter;

	//echo $current_tag . ": " . $data . " (" . $counter . ")<br/>";
	switch($current_tag){
		case "*STATUSES*STATUS*CREATED_AT":
			$counter++;
			$parsed_arr[$counter] = new ft_status();
			//'YYYY-MM-DD HH:MM:SS' Fri Apr 13 02:51:08 +0000 2007
			$parsed_arr[$counter]->created = $data;
			break;
		case "*STATUSES*STATUS*ID":
			$parsed_arr[$counter]->tid = $data;
			break;
		case "*STATUSES*STATUS*TEXT":
			$parsed_arr[$counter]->text .= $data;
			break;
		case "*STATUSES*STATUS*USER*ID":
			$parsed_arr[$counter]->uid = $data;
			break;
		case "*STATUSES*STATUS*USER*SCREEN_NAME":
			$parsed_arr[$counter]->screen_name .= $data;
			break;
		case "*STATUSES*STATUS*USER*NAME":
			$parsed_arr[$counter]->name .= $data;
			break;
		case "*STATUSES*STATUS*USER*PROFILE_IMAGE_URL":
			$parsed_arr[$counter]->purl .= $data;
			break;
	}
}

function startTag($parser, $data){
	global $current_tag;
	$current_tag .= "*$data";
}

function endTag($parser, $data){
	global $current_tag;
	$tag_key = strrpos($current_tag, '*');
	$current_tag = substr($current_tag, 0, $tag_key);
} 

class NP_Twitter extends NucleusPlugin {
	function init() {
		$this->tweets_tab = sql_table('plug_twitters_tweets');
		$this->friends_tab = sql_table('plug_twitters_friends');
		$this->followers_tab = sql_table('plug_twitters_followers');
		$this->twitters_tab = sql_table('plug_twitters_info');
		$this->twitter_url = "http://twitter.com";
		$this->client = "NP_Twitter";
		$this->version = "v0.3";
	}

	function getName() {
		return 'Twitter';
	}

	function getAuthor() {
		return 'Edmond Hui (admun)';
	}

	function getURL() {
		return 'http://forum.nucleuscms.org/';
	}

	function getVersion() {
		return '0.5';
	}

	function getDescription() {
		return 'This plugin provides Twitter integration';
	}

	function getEventList() {
		return array(
			'PostPluginOptionsUpdate',
			'PostDeleteMember',
			'PostAddItem'
		);
	}

	function updateTwittersUser($memberid) {
		$session = curl_init();

		$user = $this->getMemberOption($memberid,'TwitterUser');
		$password = $this->getMemberOption($memberid,'TwitterPassword');

		if (!$user || !$password) return;

		$header = array("X-Twitter-Client: ". $this->client, "X-Twitter-Client-Version: ". $this->version);
		$options = array(
			CURLOPT_URL => $this->twitter_url . '/users/'.$user.'.xml',
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_HEADER => 0,
			CURLOPT_USERPWD => "$user:$password",
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_GET => 1
		);

		//print_r($options);
		curl_setopt_array($session, $options);

		$result = curl_exec($session);
		curl_close($session);

		//echo "<!-- ". $result . "-->";

		$parser = xml_parser_create();
		xml_set_element_handler($parser, "startTag", "endTag");
		xml_set_character_data_handler($parser, "u_contents"); 

		if(!(xml_parse($parser, $result))){
			echo "<div id=\"content\">";
			echo "<div class=\"error\">";
			echo "NP_Twitter: Failed to update Twitter info, please try again";
			echo "</div>";
			echo "</div>";
			xml_parser_free($parser); 
			return;
		}

		xml_parser_free($parser); 

		global $uid;

		if (!$uid) {
			echo "<div id=\"content\">";
			echo "<div class=\"error\">";
			echo "NP_Twitter: Failed to update Twitter info, please try again";
			echo "</div>";
			echo "</div>";
			return;
		}

		sql_query('DELETE FROM ' . $this->twitters_tab . ' WHERE authorid='. $memberid);
		sql_query('INSERT INTO ' . $this->twitters_tab . ' (authorid,uid) VALUES (\''.$memberid.'\',\''.$uid.'\')');
	}

	function event_PostPluginOptionsUpdate($data) {
		// should update the member's twitters id in $this->twitters_tab
		if ($data['context']== 'member') {
			$this->updateTwittersUser($data['member']->getID());
		}
	}

	function event_PostDeleteMember(&$data) {
		$uid = getTwitterId($data['member']->getID());
		sql_query('DELETE FROM ' . $this->friends_tab . ' WHERE uid=' . $uid);
		sql_query('DELETE FROM ' . $this->followers_tab . ' WHERE uid=' . $uid);
		sql_query('DELETE FROM ' . $this->twitters_tab . ' WHERE authorid='. $data['member']->getID());
	}

	/*
	function event_QuickMenu(&$data) {
		global $member;

		if (!($member->isLoggedIn())) return;

		array_push(
			$data['options'],
			array('title' => 'Twitter',
			'url' => $this->getAdminURL(),
			'tooltip' => 'Tweet tweet')
		);
	}
	*/

	function supportsFeature($f) {
		switch($f){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}	

	/** 
	 * Creates the technoratitags table if it doesn't exist yet
	 */
	function install() {
		sql_query('CREATE TABLE IF NOT EXISTS '.$this->tweets_tab.' (`text` VARCHAR(160) NOT NULL, `created_at` DATETIME NOT NULL, `fid` INT NOT NULL, `tid` INT NOT NULL, `uid` INT NOT NULL, `sname` VARCHAR(15) NOT NULL, `name` VARCHAR(40) NOT NULL, `purl` VARCHAR(256), PRIMARY KEY (`tid`, `uid`, `fid`), FULLTEXT (`text`,`sname`,`name`))');
		sql_query('CREATE TABLE IF NOT EXISTS '.$this->friends_tab.' (`uid` INT NOT NULL, `fid` INT NOT NULL, `name` VARCHAR(40) NOT NULL, `sname` VARCHAR(15) NOT NULL, `loc` VARCHAR(30) NOT NULL, `descp` VARCHAR(160) NOT NULL, `url` VARCHAR(256) NOT NULL, `purl` VARCHAR(256) NOT NULL, PRIMARY KEY (`fid`), FULLTEXT(`name`))');
		sql_query('CREATE TABLE IF NOT EXISTS '.$this->followers_tab.' (`uid` INT NOT NULL, `fid` INT NOT NULL, `name` VARCHAR(40) NOT NULL, `sname` VARCHAR(15) NOT NULL, `loc` VARCHAR(30) NOT NULL, `descp` VARCHAR(160) NOT NULL, `url` VARCHAR(256) NOT NULL, `purl` VARCHAR(256) NOT NULL, PRIMARY KEY (`fid`), FULLTEXT(`name`))');
		sql_query('CREATE TABLE IF NOT EXISTS '.$this->twitters_tab.' (`authorid` TINYINT NOT NULL, `uid` INT NOT NULL, PRIMARY KEY (`authorid`, `uid`))');

		$this->createOption('Header','Tweets header formating','text','<ul>');
		$this->createOption('Item','Tweets formating (%%TWITTERT%% - twitter name in text, %%TWITTERI%% - twitter name as image, %%TWEET%% - tweet, %%TDATE%% - date)','text','<li>%%TWITTERT%%: %%TWEET%%</li>');
		$this->createOption('Footer','Tweets footer formating','text','</ul>');
		$this->createOption('TweetOnNewPost','Tweet on new item posted?','yesno','no');
		$this->createOption('NewPostTexts','Text to tweet on new post (one per line, will be randomly pick, %l == item url, %t == title, %e == excerpt)','textarea',"Just posted to my blog, see \"%t\" (%l)\nSee what I just wrote \"%t\" @ %l\nMy blog has updated \"%t\" (%l)\n");

		$this->createOption('Cleanup','Delete tweets cache on uninstall','yesno','no');

		$this->createMemberOption('TwitterUser','username','text','');
		$this->createMemberOption('TwitterPassword','password','password','');
	}
	
	/** 
	 * Asks the user if the technoratitags table should be deleted 
	 * and deletes it if yes
	 */
	function unInstall() {
		if ($this->getOption('Cleanup') == 'yes'){
			sql_query('DROP TABLE '.$this->tweets_tab);
		}

		sql_query('DROP TABLE '. $this->friends_tab);
		sql_query('DROP TABLE '. $this->followers_tab);
		sql_query('DROP TABLE '. $this->twitters_tab);
	}

	/** 
	 * Returns array of tables to be additionally included in the 
	 * backup process
	 */
	function getTableList() {
		return array($this->tweets_tab,$this->friends_tab,$this->followers_tab,$this->twitters_tab);
	}

	function doTweets($uid, $num, $page, $icon='') {

		$start = ($page - 1) * $num;
		if ($start < 0) $start = 0;

		//select t.text,t.tid,t.uid,t.sname,t.name,t.created_at,f.purl from nucleus_plug_twitters_tweets as t,nucleus_plug_twitters_friends as f where t.uid=4185371 and f.uid=t.fid order by t.tid desc limit 0,20;
		$query = sql_query("SELECT * FROM " . $this->tweets_tab . " WHERE uid=" . $uid 
			. " ORDER BY tid DESC LIMIT " . $start . "," . $num);
		if (mysql_num_rows($query) == 0) return;

		echo "<div id=\"twitter\">";
		echo $this->getOption('Header');
		while ($row = mysql_fetch_object($query)) {
			$fromt = "<a href=\"" . $this->twitter_url . "/" . $row->sname . "\">" . $row->sname . "</a>";
			$fromi = "<a href=\"" . $this->twitter_url . "/" . $row->sname . "\"><img src=\"" 
				. $row->purl . "\" alt=\"" . $row->sname . "\" title=\""
				. $row->sname . "\"/></a>";
			$date = "<a href=\"" . $this->twitter_url . "/" . $row->sname . "/statuses/" . $row->tid . "\">" . $row->created_at . "</a>";
			// shorten URL
			$msg = COMMENT::prepareBody($row->text);

			$tweet = $this->getOption('Item');
			// override the icon setting
			if ($icon == 'text') {
				$tweet = str_replace('%%TWITTERI%%','%%TWITTERT%%',$tweet);
			} else if ($icon == 'icon') {
				$tweet = str_replace('%%TWITTERT%%','%%TWITTERI%%',$tweet);
			}

			$tweet = str_replace('%%TWITTERT%%',$fromt,$tweet);
			$tweet = str_replace('%%TWITTERI%%',$fromi,$tweet);
			$tweet = str_replace('%%TDATE%%',$date,$tweet);
			$tweet = str_replace('%%TWEET%%',$msg,$tweet);
			$tweet = htmlspecialchars_decode($tweet);
			echo $tweet;
		}
		echo $this->getOption('Footer');
		
		if ($page > 1) {
			$pre = $page - 1;
			echo "<div class=\"prev\"><a href=\"?tpage=" . $pre . "\">&lt;&lt; previous</a></div>";
		}

		if ($page >= 1 && mysql_num_rows($query) == $num) {
			$next = $page + 1;
			echo "<div class=\"next\"><a href=\"?tpage=" . $next . "\">next &gt;&gt;</a></div>";
		}
		echo "</div>";
	}

	function doScript($uid, $num, $page) {
		global $CONF;
		?>
		  <!-- code from http://dutchcelt.nl/weblog/article/ajax_for_weblogs/ -->
		  <script type="text/javascript">
		  <!--
		    var ajaxTW=false;
		    /*@cc_on @*/
		    /*@if (@_jscript_version >= 5)
		    try {
			  ajaxTW = new ActiveXObject("Msxml2.XMLHTTP");
		    } catch (e) {
			  try {
			    ajaxTW = new ActiveXObject("Microsoft.XMLHTTP");
			  } catch (E) {
			    ajaxTW = false;
			  }
		    }
		    @end @*/
		 
		    if (!ajaxTW && typeof XMLHttpRequest!='undefined') {
			  ajaxTW = new XMLHttpRequest();
		    }
		 
		    function TWgetMyHTML() {
		 
			  var serverPage = '<?php echo $CONF['IndexURL'];
			  ?>action.php?action=plugin&name=Twitter&op=update&uid=<?php echo $uid?>&num=<?php echo $num; ?>&tpage=<?php echo $page; ?>';
			  var objTW = document.getElementById('twitter');
			  ajaxTW.open("GET", serverPage);
			  ajaxTW.onreadystatechange = function() {
				if (ajaxTW.readyState == 4 && ajaxTW.status == 200) {
				  objTW.innerHTML = ajaxTW.responseText;
				}
			  }
			  ajaxTW.send(null);
		 
			TWstartRefresh();
		   }
		 
		   function TWstartRefresh() {
			 setTimeout("TWgetMyHTML()",15*60*1000);
		   }
		 
		   // trick learnt from wp wordspew
		   if(typeof window.addEventListener != 'undefined') {
			 //.. gecko, safari, konqueror and standard
			 window.addEventListener('load', TWstartRefresh, false);
		   }
		   else if(typeof document.addEventListener != 'undefined')
		   {
			 //.. opera 7
			 document.addEventListener('load', TWstartRefresh, false);
		   }
		   else if(typeof window.attachEvent != 'undefined')
		   {
			 //.. win/ie
			 window.attachEvent('onload', TWstartRefresh);
		   }
		  // -->
		  </script>
		<?
	}

	function doTwitthis($style) {
		$out = "";
		if ($style == "icon") {
			$out = "<!-- Begin TwitThis (http://twitthis.com/) -->
				<script type=\"text/javascript\" src=\"http://s3.chuug.com/chuug.twitthis.scripts/twitthis.js\"></script>
				<script type=\"text/javascript\">
				<!--
				document.write('<a href=\"javascript:;\" onclick=\"TwitThis.pop();\"><img src=\"http://s3.chuug.com/chuug.twitthis.resources/twitthis_grey_72x22.gif\" alt=\"TwitThis\" style=\"border:none;\" /></a>');
				//-->
				</script>
				<!-- /End -->";
		} elseif ($style== "text") {
			$out = "<!-- Begin TwitThis (http://twitthis.com/) -->
				<script type=\"text/javascript\" src=\"http://s3.chuug.com/chuug.twitthis.scripts/twitthis.js\"></script>
				<script type=\"text/javascript\">
				<!--
				document.write('<a href=\"javascript:;\" onclick=\"TwitThis.pop();\">TwitThis</a>');
				//-->
				</script>
				<!-- /End --> ";
		}

		echo $out;
	}

	function doTemplateVar(&$item, $type, $style = 'icon') {
		if ($type == "twitthis") {
			$this->doTwitthis($style);
		}
	}

	/*
	 */
	function doSkinVar($skinType, $type, $memb='', $num=0, $icon='') {

		if ($type == "updatebox") {
			global $CONF, $member;
			if (!$member->isLoggedIn()) { return; }
			echo "<form method=\"post\" action=\"".$CONF['ActionURL']."\">\n"
              			. "<div class=\"Twitter\">\n"
				. "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n"
				. "<input type=\"hidden\" name=\"name\" value=\"Twitter\" /> \n"
				. "<input type=\"hidden\" name=\"redirecturl\" value=\"" 
				. serverVar('REQUEST_URI') . "\" /> \n"
				. "<input type=\"hidden\" name=\"op\" value=\"tweet\" /> \n"
				. "<textarea name=\"text\" rows=\"3\" cols=\"22\" /></textarea>\n"
				. "<input type=\"submit\" class=\"button\" value=\"Tweet!\" />"
				. "</div>"
				. "</form>\n";
			return;
		}

		if ($type == "twitthis") {
			$style = memb; // this parameter is overlay with membername
			$this->doTwitthis($style);
		}

		$page = intRequestVar('tpage');

		$mem = MEMBER::createFromName($memb);
		$authorid = $mem->getID();
		$uid = $this->getTwitterId($authorid);
		$username = $this->getMemberOption($authorid,'TwitterUser');

		if ($type == "tweets") {
			$this->doScript($uid, $num, $page);
			$this->doTweets($uid, $num, $page, $icon);
		}

		if ($type == "friends") {
			$query = sql_query("SELECT * FROM " . $this->friends_tab . " WHERE uid=" . $uid);
			if (mysql_num_rows($query) == 0) return;

			while ($row = mysql_fetch_object($query)) {
				echo "<img src=\"" . $row->purl . "\" alt=\"" . $row->sname . "\" title=\"" .  $row->sname . "\"/>";
			}
		}

		if ($type == "link") {
			$label = $num;
			echo "<a href=\"" . $this->twitter_url . "/" . $username . "\">" . $label . "</a>";
		}

		if ($type == "stats") {
			$query = sql_query("SELECT * FROM " . $this->friends_tab . " WHERE uid=" . $uid);
			echo "<a href=\"" . $this->twitter_url . "/" . $username . "/friends\">" .  mysql_num_rows($query) . " friends</a><br/>";
			$query = sql_query("SELECT * FROM " . $this->followers_tab . " WHERE uid=" . $uid);
			echo mysql_num_rows($query) . " followers<br/>";
		}
	}

	function getTwitterId($memberid) {
		$result = sql_query('SELECT uid FROM ' . $this->twitters_tab . ' WHERE authorid=' . $memberid);
		$author = mysql_fetch_object($result);
		return $author->uid;
	}

	function updateFriendsTL($user, $password, $memberid) {
		$session = curl_init();

		$header = array("X-Twitter-Client: ". $this->client, "X-Twitter-Client-Version: v0.1". $this->version);
		$options = array(
			CURLOPT_URL => $this->twitter_url . '/statuses/friends_timeline.xml',
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_HEADER => 0,
			CURLOPT_USERPWD => "$user:$password",
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_GET => 1,
			CURLOPT_HTTPHEADER => $header
		);

		//print_r($options);
		curl_setopt_array($session, $options);

		// for update
		//curl_setopt ( $session, CURLOPT_POSTFIELDS,"status=" . $status);

		$result = curl_exec($session);
		curl_close($session);

		//echo "<!-- ". $result . "-->";

		$parser = xml_parser_create();
		xml_set_element_handler($parser, "startTag", "endTag");
		xml_set_character_data_handler($parser, "ft_contents"); 

		if(!(xml_parse($parser, $result))){
		    die("Error on line " . xml_get_current_line_number($parser));
		}

		$uid = $this->getTwitterId($memberid);

		global $parsed_arr, $counter, $current_tag;
		$month = array ( "Jan" => '01', "Feb" => '02', "Mar" => '03', "Apr" => '04', "May" => '05', "Jun" => '06', "Jul" => '07', 
				"Aug" => '08', "Sep" => '09', "Oct" => '10', "Nov" => '11', "Dec" => '12' );
		$offset = date('Z')/60/60; // get timezone offset
		for ($x=1;$status = array_pop($parsed_arr);$x++) {
			$date_arr = explode(' ', $status->created);
			$time = explode(":",$date_arr[3]);
			$time['0'] = $time['0'] + $offset;

			// current timezone is < GMT and caused a underflow, time+date need adjust
			if ($time['0'] < 0) {
				$time['0'] = 24 + $time['0'];
				$date_arr[2] -= 1;
			}

			// current timezone is > GMT and caused a overflow, time+date need adjust
			if ($time['0'] > 23) {
				$time['0'] = 24 - $time['0'];
				$date_arr[2] += 1;
			}

			$created_at = $date_arr[5] . "-" . $month[$date_arr[1]] . "-" . $date_arr[2] . " " . implode(":",$time);

			echo $x . ": " . $status->text . " ("
			. $status->screen_name .  ", " . $status->name . ", " . $created_at . " [" . $status->created . "]" . ")<br/>";

			sql_query('INSERT INTO ' . $this->tweets_tab 
				. ' (text,created_at,tid,uid,fid,sname,name,purl) VALUES (\''
				. htmlspecialchars($status->text, ENT_QUOTES) . '\',\''
				. $created_at . '\',\'' 
				. $status->tid . '\',\'' . $uid . '\',\''
				. $status->uid . '\',\'' . htmlspecialchars($status->screen_name, ENT_QUOTES)
				. '\',\'' . htmlspecialchars($status->name, ENT_QUOTES) 
				. '\',\'' . $status->purl
				. '\') ON DUPLICATE KEY UPDATE uid=uid'
			);
		}

		$counter = 0;
		$current_tag = "";
		xml_parser_free($parser); 
	}
	
	function updateFollowers($user, $password, $memberid) {
		$session = curl_init();

		$header = array("X-Twitter-Client: ". $this->client, "X-Twitter-Client-Version: ". $this->version);
		$options = array(
			CURLOPT_URL => $this->twitter_url . '/statuses/followers.xml',
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_HEADER => 0,
			CURLOPT_USERPWD => "$user:$password",
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_GET => 1,
			CURLOPT_HTTPHEADER => $header
		);

		//print_r($options);
		curl_setopt_array($session, $options);

		$result = curl_exec($session);
		curl_close($session);

		//echo "<!-- ". $result . "-->";

		$parser = xml_parser_create();
		xml_set_element_handler($parser, "startTag", "endTag");
		xml_set_character_data_handler($parser, "fl_contents"); 

		if(!(xml_parse($parser, $result))){
		    die("Error on line " . xml_get_current_line_number($parser));
		}

		$uid = $this->getTwitterId($memberid);

		global $parsed_arr, $counter, $current_tag;
		sql_query('DELETE FROM ' . $this->followers_tab . ' WHERE uid=' . $uid);
		for ($x=1;$status = array_pop($parsed_arr);$x++) {
			echo $x . ": " . $status->name . " ("
			. $status->id . ", " . $status->sname . ", " . $status->loc . ", "
			. $status->desc . ", " . $status->url . ")<br/>";

			sql_query('INSERT INTO ' . $this->followers_tab 
				. ' (uid,fid,name,loc,descp,url,purl) VALUES (\''
				. $uid . '\',\'' . $status->id . '\',\'' 
				. htmlspecialchars($status->name, ENT_QUOTES) . '\',\'' 
				. htmlspecialchars($status->loc, ENT_QUOTES) . '\',\'' 
				. htmlspecialchars($status->desc, ENT_QUOTES) . '\',\'' 
				. $status->url . '\',\'' . $status->purl . '\')'
			);
		}

		$counter = 0;
		$current_tag = "";
		xml_parser_free($parser); 
	}

	function updateFriends($user, $password, $memberid) {
		$session = curl_init();

		$header = array("X-Twitter-Client: ". $this->client, "X-Twitter-Client-Version: ". $this->version);
		$options = array(
			CURLOPT_URL => $this->twitter_url . '/statuses/friends.xml',
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_HEADER => 0,
			CURLOPT_USERPWD => "$user:$password",
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_GET => 1,
			CURLOPT_HTTPHEADER => $header
		);

		//print_r($options);
		curl_setopt_array($session, $options);

		$result = curl_exec($session);
		curl_close($session);

		//echo "<!-- ". $result . "-->";

		$parser = xml_parser_create();
		xml_set_element_handler($parser, "startTag", "endTag");
		xml_set_character_data_handler($parser, "fd_contents"); 

		if(!(xml_parse($parser, $result))){
		    die("Error on line " . xml_get_current_line_number($parser));
		}

		$uid = $this->getTwitterId($memberid);

		global $parsed_arr, $counter, $current_tag;
		sql_query('DELETE FROM ' . $this->friends_tab . ' WHERE uid=' . $uid);
		for ($x=1;$status = array_pop($parsed_arr);$x++) {
			echo $x . ": " . $status->name . " ("
			. $status->id . ", " . $status->sname . ", " . $status->loc . ", "
			. $status->desc . ", " . $status->url . ")<br/>";

			sql_query('INSERT INTO ' . $this->friends_tab 
				. ' (uid,fid,name,sname,loc,descp,url,purl) VALUES (\''
				. $uid . '\',\'' . $status->id . '\',\'' 
				. htmlspecialchars($status->name, ENT_QUOTES) . '\',\'' 
				. htmlspecialchars($status->sname, ENT_QUOTES) . '\',\'' 
				. htmlspecialchars($status->loc, ENT_QUOTES) . '\',\'' 
				. htmlspecialchars($status->desc, ENT_QUOTES) . '\',\'' 
				. $status->url . '\',\'' . $status->purl . '\')'
			);
		}

		$counter = 0;
		$current_tag = "";
		xml_parser_free($parser); 
	}

	function sendTweet($user, $password, $text) {
		$session = curl_init();

		$header = array("X-Twitter-Client: ". $this->client, "X-Twitter-Client-Version: ". $this->version);
		$options = array(
			CURLOPT_URL => $this->twitter_url . '/statuses/update.xml',
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_HEADER => 0,
			CURLOPT_USERPWD => "$user:$password",
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_POSTFIELDS => "status=" . $text
		);

		//print_r($options);
		curl_setopt_array($session, $options);

		$result = curl_exec($session);
		curl_close($session);


	}

	function doAction($type) {
		$action = RequestVar('op');

		if ($action == "refresh") {
			$query = sql_query("SELECT mnumber FROM " . sql_table('member'));
			while ($row = mysql_fetch_object($query)) {
				// for each, user show, refresh friends, refresh followers

				$user = $this->getMemberOption($row->mnumber,'TwitterUser');
				$password = $this->getMemberOption($row->mnumber,'TwitterPassword');

				if (!$user) continue;

				echo "Updating friends...<br/>";
				$this->updateFriends($user,$password,$row->mnumber);
				echo "Updating followers...<br/>";
				$this->updateFollowers($user,$password,$row->mnumber);
				echo "Updating tweets...<br/>";
				$this->updateFriendsTL($user,$password,$row->mnumber);
			}

		}

		if ($action == "update") {
			$uid = RequestVar('uid');
			$num = RequestVar('num');
			$page = RequestVar('tpage');

			$this->doTweets($uid, $num, $page);
		}

		if ($action == "tweet") {
			global $member;
			if (!$member->isLoggedIn()) doError('You\'re not logged in.');
			$redirect=RequestVar('redirecturl');
			$text = RequestVar('text');
			$text = substr($text,0,140);
			$tweetas =  RequestVar('tweetas');
			if ($tweetas != '') {
				$text = $tweetas . " " . $text;
			}

			$user = $this->getOption('TwitterUser');
			$password = $this->getOption('TwitterPassword');

      			$this->sendTweet($user, $password, $text);

			header('Location: ' . $redirect);
		}

		if ($action == "tweetlet") {
			global $member;
			if (!$member->isLoggedIn()) doError('You\'re not logged in.');
			global $CONF;
			$url =  RequestVar('url');

			echo "<h1>Tweetlet</h1><form method=\"post\" action=\"".$CONF['ActionURL']."\">\n"
              			. "<div class=\"Twitter\">\n"
				. "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n"
				. "<input type=\"hidden\" name=\"name\" value=\"Twitter\" />\n"
				. "<input type=\"hidden\" name=\"redirecturl\" value=\"" . $url . "\" />\n"
				. "<input type=\"hidden\" name=\"op\" value=\"tweet\" />\n"
				. "<select name=\"tweetas\">
					<option selected>Reading:
					<option>Looking at:
					<option>Listening to:
					<option>Laughing at:
					<option>at:
					<option>Waiting for:
					<option>Looking forward to:
					</select><br/>"
				. "<textarea name=\"text\" rows=\"3\" cols=\"50\" />" . RequestVar('text') . " (" . RequestVar('url') . ")</textarea><br/>\n"
				. "<input type=\"submit\" class=\"button\" value=\"Tweet!\" />"
				. "</div>"
				. "</form>\n";
		}
	}

	function event_PostAddItem($data) {
		if ($this->getOption('TweetOnNewPost') == 'yes') {
			$itemid = $data['itemid'];
			$query=sql_query('SELECT iauthor FROM ' . sql_table('item') . ' WHERE inumber=' . $itemid . ' AND idraft=0 AND itime <= NOW()');
			if (mysql_num_rows($query) == 0) return;

			$row=mysql_fetch_object($query);
			$authorId=$row->iauthor;

			$user = $this->getMemberOption($authorId,'TwitterUser');
			$password = $this->getMemberOption($authorId,'TwitterPassword');
	        	$url = createItemLink($itemid);
			$pre_arr = explode("\n", $this->getOption('NewPostTexts'));

			$text = $pre_arr[rand(0,count($pre_arr))];
			$text = str_replace("%l",$url,$text);

			global $manager;
			$item = &$manager->getItem($itemid, 0, 0);
			$text = str_replace("%t",$item['title'],$text);
			$text =
			str_replace("%e",strip_tags(substr($item['body'], 0, 30)). "...",$text);

      			$this->sendTweet($user, $password, $text);
		}
	}

}
?>
