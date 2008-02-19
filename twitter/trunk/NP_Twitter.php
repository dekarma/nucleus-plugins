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
 *   - added date sepecrator in tweets archive
 *   - handled protected updates
 *   - fixed &quot; and +
 *   - added delete tweet function
 *   - added reply function to add @xxx to the tweetbox (using javascript)
 *   - fixed URL mess up on reading:
 * v0.6
 *   - changed only when tweetbox appear that reply/delete buttons are shown
 *   - only allow delete own tweets
 *   - reply button to only friends
 *   - reply button only to those who follow me
 *   - char counter on update box 
 *   - fixed timezone error.... 10/0??? hopefully once and for all this time...
 *   - fixed random tweet on new post
 *   - added archive skinVar mode "mytweets" to show only a particular user's tweets
 *   - added char count to tweetlet
 *   - fixed curl warning
 *   - fix user option bug
 * v0.7
 *   - new post tweet send override
 *
admun TODO

- fix hicup on tweetlet on ' in title
- fix change icon problem
- localize icon
- rss feed
- search

- multiple-tweet template, with admin menu
- my tweets and replies (search for @admun)
- language file

- use JustPost event for 3.3
- link to @who
- add favorite support??

- how long ago, highlight recent tweets (via CSS)

javascript:location.href='http://edmondhui.homeip.net/blog/action.php?action=plugin&name=Twitter&op=tweetlet&url='+encodeURIComponent(location.href)+'&text='+document.title

- add current user tweet
- show badge for login member
- per member accounts... or via member options?
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
	var $id, $name, $sname, $loc, $desc, $purl, $url, $protected;
}

function fl_contents($parser, $data) {
	global $current_tag, $parsed_arr, $counter;
	//echo $current_tag . ": " . $data . " (" . $counter . ")<br/>\n";
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
		case "*USERS*USER*PROTECTED":
			$parsed_arr[$counter]->protected .= $data;
			break;
		default:
			echo "<!-- unsupported follower tag: "
			. $current_tag . " = " . $data . " -->\n";
			break;
	}
}

function fd_contents($parser, $data) {
	global $current_tag, $parsed_arr, $counter;
	//echo $current_tag . ": " . $data . " (" . $counter . ")<br/>\n";
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
		case "*USERS*USER*PROTECTED":
			$parsed_arr[$counter]->protected .= $data;
			break;
		default:
			echo "<!-- unsupported friend tag: " . $current_tag
			.  " = " . $data . " -->\n";
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
	var $created, $text, $tid, $uid, $name, $screen_name, $purl, $protected;
}

function ft_contents($parser, $data) {
	global $current_tag, $parsed_arr, $counter;

	//echo $current_tag . ": " . $data . " (" . $counter . ")<br/>\n";
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
		case "*STATUSES*STATUS*USER*PROTECTED":
			if ($data == "true") {
				$parsed_arr[$counter]->protected = "1";
			} else {
				$parsed_arr[$counter]->protected = "0";
			}
			break;
		default:
			echo "<!-- unsupported friendtimeline tag: " 
			. $current_tag . " = " . $data . " -->\n";
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
		$this->version = "v0.7";
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
		return '0.7';
	}

	function getDescription() {
		return 'This plugin provides Twitter integration';
	}

	function getEventList() {
		return array(
			'PostPluginOptionsUpdate',
			'PostDeleteMember',
			'AddItemFormExtras',
			'PostAddItem'
		);
	}

	function updateTwittersUser($memberid) {
		$session = curl_init();

		$user = $this->getMemberOption($memberid,'TwitterUser');
		$password = $this->getMemberOption($memberid,'TwitterPassword');

		if (!$user || !$password) return;

		$header = array("X-Twitter-Client: " 
		          . $this->client, "X-Twitter-Client-Version: "
			  . $this->version);
		$options = array(
			CURLOPT_URL => $this->twitter_url
			. '/users/'.$user.'.xml',
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_HEADER => 0,
			CURLOPT_USERPWD => "$user:$password",
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_RETURNTRANSFER => 1
		);

		//print_r($options);
		curl_setopt_array($session, $options);

		$result = curl_exec($session);
		curl_close($session);

		//echo "<!-- ". $result . "-->\n";

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

		// added in V0.5
		sql_query('ALTER TABLE '.$this->friends_tab.' ADD `protected` BOOL NOT NULL AFTER `purl`');
		sql_query('ALTER TABLE '.$this->followers_tab.' ADD `protected` BOOL NOT NULL AFTER `purl`');
		sql_query('ALTER TABLE '.$this->tweets_tab.' ADD `protected` BOOL NOT NULL AFTER `purl`');

		$this->createOption('Header','Tweets header formating','text','<ul>');
		$this->createOption('Item','Tweets formating (%%TWITTERT%% - twitter name in text, %%TWITTERI%% - twitter name as image, %%TWEET%% - tweet, %%TDATE%% - date)','text','<li>%%TWITTERT%%: %%TWEET%%</li>');
		$this->createOption('Footer','Tweets footer formating','text','</ul>');
		$this->createOption('TweetOnNewPost','Tweet on new item posted?','yesno','no');
		$this->createOption('NewPostTexts','Text to tweet on new post (one per line, will be randomly pick, %l == item url, %t == title, %e == excerpt)','textarea',"Just posted to my blog, see \"%t\" (%l)\nSee what I just wrote \"%t\" @ %l\nMy blog has updated \"%t\" (%l)\n");

		$this->createOption('Cleanup','Delete tweets cache on uninstall','yesno','no');
		$this->createOption('ShowDate','Show Date in Archive?','yesno','yes');

		$this->createMemberOption('TwitterUser','username','text','');
		$this->createMemberOption('TwitterPassword','password','password','');
	}
	
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

	function doTweets($uid, $num, $page=0, $icon='', $tweetType) {
		global $CONF, $member;

		$loggedIn = $member->isLoggedIn();

		$start = ($page - 1) * $num;
		if ($start < 0) $start = 0;

		$extra = '';
		if (!$loggedIn) {
			$extra = ' AND t.protected != 1 ';
		}
		
		if ($tweetType == "mytweets") {
			$extra .= ' AND t.fid = ' . $uid . ' ';
		}

		//select t.text,t.tid,t.uid,t.sname,t.name,t.created_at,f.purl from nucleus_plug_twitters_tweets as t,nucleus_plug_twitters_friends as f where t.uid=4185371 and f.uid=t.fid order by t.tid desc limit 0,20;
		$query = sql_query("SELECT * FROM " . $this->tweets_tab . " as t WHERE t.uid=" . $uid . $extra . " ORDER BY tid DESC LIMIT " . $start . "," . $num);
		if (mysql_num_rows($query) == 0) return;

		$date_head = $this->getOption('ShowDate');

		if ($page == 0) $date_head = "no";

		echo "<div id=\"twitter\">";
		echo $this->getOption('Header');
		while ($row = mysql_fetch_object($query)) {
			$new_head = substr($row->created_at, 0, 10);
			if ($new_head != $date_head && $date_head != "no") {
				echo "<li><h3>".$new_head."</h3></li>";
				$date_head = $new_head;
			}

			$fromt = "<a href=\"" . $this->twitter_url . "/" 
			       . $row->sname . "\">" . $row->sname . "</a>";
			$fromi = "<a href=\"" . $this->twitter_url . "/" 
			       . $row->sname . "\"><img src=\"" 
			       . $row->purl . "\" alt=\"" . $row->sname 
			       . "\" title=\"" . $row->sname . "\"/></a>";
			$date = "<a href=\"" . $this->twitter_url . "/"
			      . $row->sname . "/statuses/" . $row->tid 
			      . "\">" . $row->created_at . "</a>";

			if ($loggedIn) {
				global $updateboxed;
				if ($updateboxed == "true") {
					if ($row->fid != $row->uid) {
						$followed = 0;
						// ED$ we can pre-fetch all followers into an array..... should be faster
						$q = sql_query("SELECT * FROM " . $this->followers_tab 
						     . " WHERE fid = " . $row->fid);
						if (mysql_num_rows($q) == 1) {
							$date .= " <a href=\"javascript:insertReplyTo('@"
							. $row->sname
							. "')\">[reply]</a>";
						}
					}

					//$date .= " <a href=\"".  "\">[favorite]</a>";

					if ($row->fid == $row->uid) {
						$date .= 
						" <a href=\"" . $CONF['ActionURL'] 
						. "?action=plugin&name=Twitter&op=deltweet&tid="
						. $row->tid . "&redirecturl=http://" 
						. serverVar("HTTP_HOST")
						. serverVar('REQUEST_URI')
						. "\">[delete]</a> "; 
					}
				}
			}

			// shorten URL
			$msg = COMMENT::prepareBody($row->text);

			if ($msg[0] == '@') {
				// $msg = ;
			}

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
			$tweet = str_replace('&amp;','&',$tweet);
			$tweet = htmlspecialchars_decode($tweet);
			echo $tweet;

		}
		echo $this->getOption('Footer');
		
		if ($page > 1) {
			$pre = $page - 1;
			echo "<div class=\"prev\"><a href=\"?tpage=" . $pre 
			   . "\">&lt;&lt; newer</a></div>";
		}

		if ($page >= 1 && mysql_num_rows($query) == $num) {
			$next = $page + 1;
			echo "<div class=\"next\"><a href=\"?tpage=" . $next
			   . "\">older &gt;&gt;</a></div>";
		}
		echo "</div>";
	}

	function doScript($uid, $num, $page, $icon, $tweetType) {
		global $CONF, $updateboxed;

		if ($updateboxed == "true") {
			$rep = '&rep=1';
		} else {
			$rep = '';
		}

		if ($tweetType == "tweets") {
			$updatetype = "update";
		} else {
			$updatetype = "aupdate";
		}

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
		 
			  var serverPage = '<?php echo $CONF['IndexURL']; ?>action.php?action=plugin&name=Twitter&op=<? echo $updatetype; ?>&uid=<?php echo $uid?>&num=<?php echo $num; ?>&tpage=<?php echo $page . $rep; ?>&icon=<? echo $icon; ?>'; var objTW = document.getElementById('twitter');
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
			 setTimeout("TWgetMyHTML()",5*60*1000);
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

	function doSkinVar($skinType, $type, $memb='', $num=0, $icon='') {

		if ($type == "updatebox") {
			global $updateboxed;
			$updateboxed = "true";
			global $CONF, $member;
			if (!$member->isLoggedIn()) { return; }

			// ED$ check if this user has configure his/her account 

			$this->addCharCountScript();
			echo "<form method=\"post\" action=\"".$CONF['ActionURL']."\">\n"
              			. "<div class=\"Twitter\">\n"
				. "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n"
				. "<input type=\"hidden\" name=\"name\" value=\"Twitter\" /> \n"
				. "<input type=\"hidden\" name=\"redirecturl\" value=\"" 
				. serverVar('REQUEST_URI') . "\" /> \n"
				. "<input type=\"hidden\" name=\"op\" value=\"tweet\" /> \n"
				. "<label for=\"tweet_box\">  </label>"
				. "<textarea name=\"text\" rows=\"2\" cols=\"70\" id=\"tweet_box\" onKeyUp=\"Contar('tweet_box','tweet_count','{CHAR} characters left.',140);\"/></textarea>\n"
				. "<input type=\"submit\" class=\"button\" value=\"Tweet!\" />"
				. "<br><span id=\"tweet_count\" class=\"minitext\">140 characters left.</span>"
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

		if ($type == "tweets" || $type == "mytweets") {
			$this->doScript($uid, $num, $page, $icon, $type);
			$this->doTweets($uid, $num, $page, $icon, $type);
		}

		if ($type == "friends") {
			$query = sql_query("SELECT * FROM " 
			       . $this->friends_tab . " WHERE uid=" . $uid);
			if (mysql_num_rows($query) == 0) return;

			while ($row = mysql_fetch_object($query)) {
				echo "<img src=\"" . $row->purl . "\" alt=\""
				   . $row->sname . "\" title=\"" . $row->sname
				   . "\"/>";
			}
		}

		if ($type == "link") {
			$label = $num;
			echo "<a href=\"" . $this->twitter_url . "/" 
			   . $username . "\">" . $label . "</a>";
		}

		if ($type == "stats") {
			$query = sql_query("SELECT * FROM " 
			. $this->friends_tab . " WHERE uid=" . $uid);
			echo "following <a href=\"" . $this->twitter_url . "/" 
			. $username . "/friends\">" .  mysql_num_rows($query) 
			. " people</a><br/>";
			$query = sql_query("SELECT * FROM " 
			. $this->followers_tab . " WHERE uid=" . $uid);
			echo mysql_num_rows($query) . " people following me<br/>";
		}
	}

	function getTwitterId($memberid) {
		$result = sql_query('SELECT uid FROM ' . $this->twitters_tab 
		        . ' WHERE authorid=' . $memberid);
		$author = mysql_fetch_object($result);
		return $author->uid;
	}

	function updateFriendsTL($user, $password, $memberid) {
		$session = curl_init();

		$header = array("X-Twitter-Client: " 
		        . $this->client, "X-Twitter-Client-Version: v0.1"
			. $this->version);
		$options = array(
			CURLOPT_URL => $this->twitter_url
			. '/statuses/friends_timeline.xml',
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_HEADER => 0,
			CURLOPT_USERPWD => "$user:$password",
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HTTPHEADER => $header
		);

		//print_r($options);
		curl_setopt_array($session, $options);

		// for update
		//curl_setopt ( $session, CURLOPT_POSTFIELDS,"status=" . $status);

		$result = curl_exec($session);
		curl_close($session);

		//echo "<!-- ". $result . "-->\n";

		$parser = xml_parser_create();
		xml_set_element_handler($parser, "startTag", "endTag");
		xml_set_character_data_handler($parser, "ft_contents"); 

		if(!(xml_parse($parser, $result))){
		    die("Error on line " . xml_get_current_line_number($parser));
		}

		$uid = $this->getTwitterId($memberid);

		global $parsed_arr, $counter, $current_tag;
		$offset = date('Z'); // get timezone offset (in sec)

		for ($x=1;$status = array_pop($parsed_arr);$x++) {
			$gmt_time = strtotime($status->created);
			$created_at = date("Y-n-j H:i:s", $gmt_time);

			echo $x . ": " . $status->text . " ("
			. $status->screen_name .  ", " . $status->name 
			. ", " . $created_at . " [" . $status->created . "], "
			. $status->protected . ")<br/>\n";

			sql_query('INSERT INTO ' . $this->tweets_tab 
				. ' (text,created_at,tid,uid,fid,sname,name,purl,protected) VALUES (\''
				. htmlspecialchars($status->text, ENT_QUOTES) 
				. '\',\''
				. $created_at . '\',\'' 
				. $status->tid . '\',\'' . $uid . '\',\''
				. $status->uid . '\',\'' 
				. htmlspecialchars($status->screen_name, ENT_QUOTES)
				. '\',\'' . htmlspecialchars($status->name, ENT_QUOTES) 
				. '\',\'' . $status->purl
				. '\',\'' . $status->protected
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
			CURLOPT_HTTPHEADER => $header
		);

		//print_r($options);
		curl_setopt_array($session, $options);

		$result = curl_exec($session);
		curl_close($session);

		//echo "<!-- ". $result . "-->\n";

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
			. $status->id . ", " . $status->sname . ", " 
			. $status->loc . ", " . $status->desc . ", "
			. $status->url . ", " . $status->protected 
			.  ")<br/>\n";

			if ($status->protected == 'true') {
				$status->protected = 1;
			} else {
				$status->protected = 0;
			}

			sql_query('REPLACE ' . $this->followers_tab . ' (uid,fid,name,loc,descp,url,purl,protected) VALUES (\'' . $uid . '\',\'' . $status->id . '\',\'' . htmlspecialchars($status->name, ENT_QUOTES) . '\',\'' . htmlspecialchars($status->loc, ENT_QUOTES) . '\',\'' . htmlspecialchars($status->desc, ENT_QUOTES) . '\',\'' . $status->url . '\',\'' . $status->purl . '\',\'' . $status->protected . '\')');
		}

		$counter = 0;
		$current_tag = "";
		xml_parser_free($parser); 
	}

	function updateFriends($user, $password, $memberid) {
		$session = curl_init();

		$header = array("X-Twitter-Client: "
		        . $this->client, "X-Twitter-Client-Version: "
			. $this->version);
		$options = array(
			CURLOPT_URL => $this->twitter_url 
			. '/statuses/friends.xml',
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_HEADER => 0,
			CURLOPT_USERPWD => "$user:$password",
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HTTPHEADER => $header
		);

		//print_r($options);
		curl_setopt_array($session, $options);

		$result = curl_exec($session);
		curl_close($session);

		//echo "<!-- ". $result . "-->\n";

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
			. $status->id . ", " . $status->sname . ", " 
			. $status->loc . ", " . $status->desc . ", " 
			. $status->url . ", " . $status->protected 
			. ")<br/>\n";

			if ($status->protected == 'true') {
				$status->protected = 1;
			} else {
				$status->protected = 0;
			}

			sql_query('REPLACE ' . $this->friends_tab . ' (uid,fid,name,sname,loc,descp,url,purl,protected) VALUES (\'' . $uid . '\',\'' . $status->id . '\',\'' . htmlspecialchars($status->name, ENT_QUOTES) . '\',\'' . htmlspecialchars($status->sname, ENT_QUOTES) . '\',\'' . htmlspecialchars($status->loc, ENT_QUOTES) . '\',\'' . htmlspecialchars($status->desc, ENT_QUOTES) . '\',\'' . $status->url . '\',\'' . $status->purl . '\',\'' . $status->protected . '\')');
		}

		$counter = 0;
		$current_tag = "";
		xml_parser_free($parser); 
	}

	function delTweet($user, $password, $tid) {
		$session = curl_init();

		$header = array("X-Twitter-Client: "
		        . $this->client, "X-Twitter-Client-Version: "
			. $this->version);
		$options = array(
			CURLOPT_URL => 
			$this->twitter_url . '/statuses/destroy/' . $tid 
			. ".xml",
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_HEADER => 0,
			CURLOPT_USERPWD => "$user:$password",
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_HTTPHEADER => $header
		);

		//print_r($options);
		curl_setopt_array($session, $options);

		$result = curl_exec($session);
		curl_close($session);

		sql_query('DELETE FROM ' . $this->tweets_tab . ' WHERE tid='. $tid);
	}

	function sendTweet($user, $password, $text) {
		$session = curl_init();


		$header = array("X-Twitter-Client: "
		        . $this->client, "X-Twitter-Client-Version: "
			. $this->version);
		$options = array(
			CURLOPT_URL => $this->twitter_url 
			. '/statuses/update.xml',
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_HEADER => 0,
			CURLOPT_USERPWD => "$user:$password",
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_POSTFIELDS => "status=" . urlencode($text)
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

				if (!$user || !$password) continue;

				echo "<h2>Updating friends...</h2>\n";
				$this->updateFriends($user,$password,$row->mnumber);
				echo "<h2>Updating followers...</h2>\n";
				$this->updateFollowers($user,$password,$row->mnumber);
				echo "<h2>Updating tweets...</h2>\n";
				$this->updateFriendsTL($user,$password,$row->mnumber);
			}

		}

		if ($action == "update" || $action == "aupdate") {
			$uid = RequestVar('uid');
			$num = RequestVar('num');
			$page = RequestVar('tpage');
			$icon = RequestVar('icon');
			if (IntRequestVar('rep') == 1) {
				global $updateboxed;
				$updateboxed = "true";
			}

			if ($action == "update") {
				$tweetype = "tweets";
			} else {
				$tweetype = "mytweets";
			}

			$this->doTweets($uid, $num, $page, $icon, $tweetype);
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

			$user = $this->getMemberOption($member->getID(),'TwitterUser');
			$password = $this->getMemberOption($member->getID(),'TwitterPassword');

			if (!$user || !$password) return;

      			$this->sendTweet($user, $password, $text);

			header('Location: ' . $redirect);
		}

		if ($action == "deltweet") {
			global $member;
			if (!$member->isLoggedIn()) doError('You\'re not logged in.');
			$tid =  RequestVar('tid');
			$redirect=RequestVar('redirecturl');

			$user = $this->getMemberOption($member->getID(),'TwitterUser');
			$password = $this->getMemberOption($member->getID(),'TwitterPassword');

			if (!$user || !$password) return;

      			$this->delTweet($user, $password, $tid);

			header('Location: ' . $redirect);
		}

		if ($action == "tweetlet") {
			global $member;
			if (!$member->isLoggedIn()) doError('You\'re not logged in.');
			global $CONF;
			$url =  RequestVar('url');

			$this->addCharCountScript();
			echo "<h1>Tweetlet</h1><form method=\"post\" action=\""
			.$CONF['ActionURL']."\">\n" 
			. "<div class=\"Twitter\">\n"
			. "<input type=\"hidden\" name=\"action\" value=\"plugin\" />\n"
			. "<input type=\"hidden\" name=\"name\" value=\"Twitter\" />\n"
			. "<input type=\"hidden\" name=\"redirecturl\" value=\""
			. $url . "\" />\n" 
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
			. "<textarea name=\"text\" rows=\"2\" cols=\"70\" id=\"tweet_box\" onKeyUp=\"Contar('tweet_box','tweet_count','{CHAR} characters left.',120);\"/>\n"
			. RequestVar('text') ." ". RequestVar('url') 
			. "</textarea>\n"
			. "<br><span id=\"tweet_count\" class=\"minitext\">" 
			. intVal(120 - strlen(RequestVar('text') ." ". RequestVar('url'))) 
			. " characters left.</span><br/>"
			. "<input type=\"submit\" class=\"button\" value=\"Tweet!\" />"
			. "</div>"
			. "</form>\n";
		}
	}

	function event_AddItemFormExtras($data) {
?>
		<h3>Twitter</h3>
		<p>
                <input type="checkbox" name="tweet_this_post"
<?php
                if ($this->getOption('TweetOnNewPost') == "yes") {
                        echo " checked=\"checked\"";
                }
?>
                />Send tweet on this post
                </p>
<?php
	}

	function event_PostAddItem($data) {
		$tweet_this = RequestVar('tweet_this_post');
		if ($tweet_this == "on") {
			$itemid = $data['itemid'];
			$query=sql_query('SELECT iauthor FROM ' . sql_table('item') . ' WHERE inumber=' . $itemid . ' AND idraft=0 AND itime <= NOW()');
			if (mysql_num_rows($query) == 0) return;

			$row=mysql_fetch_object($query);
			$authorId=$row->iauthor;

			$user = $this->getMemberOption($authorId,'TwitterUser');
			$password = $this->getMemberOption($authorId,'TwitterPassword');

			if (!$user || !$password) {
				return;
			}

			$pre_arr = explode("\n", $this->getOption('NewPostTexts'));
			if (count($pre_arr) > 1) {
				$text = $pre_arr[rand(0,count($pre_arr)-1)];
			} else {
				$text = $pre_arr[0];
			}

			global $manager;
			$item = &$manager->getItem($itemid, 0, 0);

	        	$url = createItemLink($itemid);
			$text = str_replace("%l",$url,$text);

			// ED$ only do this if %t is used, else we can used 110 words
			$exc_to_see = 60; // 110-strlen($item['title']);

			$text = str_replace("%t",$item['title'],$text);
			$text = str_replace("%e", strip_tags(substr($item['body'], 0, $exc_to_see)) . "...", $text);

			ACTIONLOG::add(INFO, 'Tweet on new post: ' . $text);
      			$this->sendTweet($user, $password, $text);
		}
	}

	function addCharCountScript()
	{
?>
<script type="text/javascript">
  function insertReplyTo(text){
    document.getElementById('tweet_box').value+= text + " ";
    document.getElementById('tweet_box').focus();
  }
</script>
<script type="text/javascript">
<!-- code taken from http://javascript.internet.com/forms/character-counter.html -->

function getObject(obj) {
  var theObj;
  if(document.all) {
    if(typeof obj=="string") {
      return document.all(obj);
    } else {
      return obj.style;
    }
  }
  if(document.getElementById) {
    if(typeof obj=="string") {
      return document.getElementById(obj);
    } else {
      return obj.style;
    }
  }
  return null;
}

//Contador de caracteres.
function Contar(entrada,salida,texto,caracteres) {
  var entradaObj=getObject(entrada);
  var salidaObj=getObject(salida);
  var longitud=caracteres - entradaObj.value.length;
  if(longitud <= 0) {
    longitud=0;
    texto='<span class="disable"> '+texto+' </span>';
    entradaObj.value=entradaObj.value.substr(0,caracteres);
  }
  salidaObj.innerHTML = texto.replace("{CHAR}",longitud);
}
</script>
<?
	}

}
?>
