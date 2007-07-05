<?php
/**
 * NP_TechnoratiTags Plugin for NucleusCMS
 *
 * Version 0.9.6 (admun)
 *  - optmize auto complete init
 * Version 0.9.5 (admun)
 *  - fix tagsearch result double http link bug
 *  - error checking for missing blog object in doSkinVar()
 *  - port NP_AutoComplete by anand to NP_TechnoratiTags, allow tag auto completion
 *  - fix duplicate tags in an item
 * Version 0.9.4 (admun)
 *  - tagcloud idle display without tag select
 *  - ltag templateVar switch to show local tag
 * Version 0.9.3 (admun)
 *  - fixed UTF-8 multi-bytes encoding problem wih tag search
 *  - FancyURL support (Thanks Shi!)
 *  - show popular tag only option
 * Version 0.9.2 (admun)
 *  - skip tag update to del.icio.us if there is no user/password set
 *  - fix tag cloud to ensure it displays according to PlusSwitch option
 *  - rename templete var dcloud switch to dtag
 * Version 0.9.1 (admun)
 *  - fix incorrect tag link construction when insert tag at teh end of post, option added to select tag insert type
 * Version 0.9.0 (admun)
 *  - allow per blog tag cloud - current, all, by blogid
 *  - show tag count
 *  - option for tag search title
 *  - outbound tag support to del.icio.us; 1) add dcloud skin/templete control to point to del.icio.us instead of Technorati
 *    2) tag a post to del.icio.us (each user need to provide login/password)
 *  - del.icio.us support is using code from http://www.ejeliot.com/pages/php-delicious (Thanks, Ed!)
 * Version 0.8.7 (admun)
 *  - add option for none string
 *  - minor fix on getTableList()
 * Version 0.8.6 (admun)
 *  - use sql_query
 * Version 0.8.5 (StaticBeats)
 *  - add CSS
 * Version 0.8.4 (admun)
 *  - fix missing formating bug
 * Version 0.8.3 (admun)
 *   - add parameter to <%TechnoratiTags()%> for sorting and number of tags to show
 * Version 0.8.2 (admun)
 *  - fix missing "+" searching bug
 * Version 0.8.1 (admun)
 *  - fix phantom tags bug
 * Version 0.8 (admun)
 *  - add tag cloud and search function to list local posts to a tag
 * Version 0.7.1 (admun)
 *  - bug fix for incorrect tag URL when append tags to the end of post
 * Version 0.7 (Adam Harvey)
 *  - Added tag cloud skinvar
 *  - Added error checking to sql queries
 *  - Misc edits
 * Version 0.6 (admun)
 *  - temp fix + replace with space on add item
 * Version 0.5: (by Edmond Hui aka admun)
 *  - TechnoratiTags template var mode
 *  - fix empty tags row in table
 * Version 0.4: (by Edmond Hui aka admun)
 *  - fixed tags between body and more bug
 * Version 0.3.5:
 *  - + -> &nbsp; switch
 *  - %d for the taglook
 * Version 0.3:
 *  - Only one additional query per page
 * Version 0.2:
 *  - There are now some new admin fields for defining the look&feel of
 *    the tags
 *  - Don't display tags if count($args) == 1 && $args[0]==''
 
 * admun TODO:
 *  - no del.icio.us tag add for draft and future post, use JustPost
 *  - split outbound del.icio.us support to a new plugin, to share with NP_Blogroll
 *  - delete/rename tags for multiple items, need admin menu....
 *  - show post using NP_ShowBlogs for a tag
 *  - add pagination for search result
 *  - maybe other tag site support? ie update blogmarks.net
 * 
 *  - tag cloud per user
 *
 *  - add %d for date in search result... need to add template to support that
 *  - add another column to the tags table for tag number,
 *    so we can store multiple tags, uniquely, per row. this allows
 *    for better querying to generate tag clouds, search the system
 *    for tags, etc. the get tags can just query for all rows
 *    with a tag for itemid, then combine the tags fields. (composite primary key)
 *  - if the above, add an upgrade (migrate) feature to strip out
 *		all the existing tags rows, (plit their fields by space, and
 *		reimport them into a new table (NP_TechnoratiTags2 ?)
 *		with the unique tags per row.
 *
 * References:
 * "How to Make a Tag Cloud"
 *  http://www.petefreitag.com/item/396.cfm
 */
if (!function_exists('sql_table')){
	function sql_table($name){
		return 'nucleus_'.$name;
	}
}

// Class to talk to del.icio.us
require_once($DIR_PLUGINS."php-delicious/php-delicious.inc.php");

class NP_TechnoratiTags extends NucleusPlugin {
	function init(){
		$this->tablename = sql_table('plug_technoratitags');
		$this->cachedTagsPerPost = array();
		$this->queried = FALSE;
		$this->technoratiurl = "http://technorati.com/tag";
		$this->deliciousurl = "http://del.icio.us/tag";
		$this->delurl = "";
		$this->delaid = -1;

	}

	function getName() {
		return 'TechnoratiTags';
	}

	function getAuthor()  {
		return 'Horst Gutmann, mod by Edmond Hui, Adam Harvey';
	}

	function getURL()
	{
		return 'http://forum.nucleuscms.org/viewtopic.php?t=15457';
	}

	function getVersion() {
		return '0.9.6';
	}

	function getDescription() {
		return 'This plugin provides a easy to setup tagging system and adds a way to specify Technorati tags for each post, It also support del.icio.us tagging API (add/update/delete url)';
	}

	function getEventList(){
		return array(
			'AddItemFormExtras',
			'EditItemFormExtras',
                        'PreDeleteItem',
			'PostDeleteItem',
			'PostAddItem',
			'PreUpdateItem',
			'PreItem',
			'PostItem',
			'AdminPrePageHead',
			'BookmarkletExtraHead'
		);
	}

	function supportsFeature($f){
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
	function install(){
		sql_query('CREATE TABLE IF NOT EXISTS '.$this->tablename.' (itemid INT(9) NOT NULL, tags VARCHAR(255) , INDEX(itemid))');
		$this->createOption('ListLook','Look of the list:','textarea','<br/><br/>tags: %l');
		$this->createOption('TagSeparator','Separator of the tags when being displayed:','textarea',', ');
                $taglook = "<a href=\"%TAGURL%/%t\" rel=\"tag\">%d</a>";
		$this->createOption('TagLook','Look of the tags (%TAGURL% is the URL to Technorati/del.icio.us, leave alone):','textarea',$taglook);
		$this->createOption('NoneText','Text string for no tag','text','none');
		$this->createOption('Cleanup','Tags table should be removed when uninstalling this plugin','yesno','no');
		$this->createOption('PlusSwitch','Display "+" as " " (space)?','yesno','no');
		$this->createOption('AppendTag','Insert tags at the end of post?','yesno','yes');
		$this->createOption('AppendTagType','Type of tags insert to the end of post','select', '0', 'Technorati|0|del.icio.us|1');
		$this->createOption('SearchTitleText','Tag search title text','text','Tag Search Result for');
		$this->createOption('ShowCount','Show number of posts on each tag in a local cloud','yesno','no');
		$this->createOption('DelIcioUs','Add post to each tag in del.icio.us? (user need to set his/her login & password from member setting)','yesno','no');
		$this->createOption('TagShowPercentage','Amount of tags (by percentage) to show on tag cloud (100% == show all tags)','text','100');

		$this->createMemberOption('DeliciousUser','del.icio.us login','text','');
		$this->createMemberOption('DeliciousPassword','del.icio.us password','password','');

		$this->createOption("maxTags", "Number of Tags to hold in memory for tag auto completion", "text", "200");
	}
	
	/** 
	 * Asks the user if the technoratitags table should be deleted 
	 * and deletes it if yes
	 */
	function unInstall(){
		if ($this->getOption('Cleanup') == 'yes'){
			sql_query('DROP TABLE '.$this->tablename);
		}
	}

	/** 
	 * Returns array of tables to be additionally included in the 
	 * backup process
	 */
	function getTableList(){
		return array($this->tablename);
	}

	/** 
	 * Returns the tag-string from the database for the given 
	 * $postID
	 * @return array of tags
	 */
	function getTags($itemID){
		if (!$this->queried){
			//$result = sql_query('SELECT * FROM '.$this->tablename);
			/**
			 * Best practice to specify the fields to be returned,
			 * especially if in future versions new columns are added
			 * and they aren't needed here.
			 */
			$result = sql_query('SELECT itemid, tags FROM '.$this->tablename);
			/**
			 * Error check the query
			 */
			if (!$result) {
				// return array("Error: " . mysql_error() );
				return array("<i>Could not load tags</i>");
			}
			while($row = mysql_fetch_object($result)){
				$row->tags = explode(' ',$row->tags);
				$this->cachedTagsPerPost[$row->itemid]=$row->tags;
			}
			mysql_free_result($result);
			$this->queried = TRUE;
		}
		if (!array_key_exists($itemID,$this->cachedTagsPerPost)){
			/* Hm.... this item has no entry in the tags table...
			 * will be created the next time someone edits the
			 * item.
			 */
			return array();
		}
		else {
			return $this->cachedTagsPerPost[$itemID];
		}
	}

	/** 
	 * Returns all tags
	 * @author Adam Harvey
	 * @return 2d array of all tags and their usage counts
	 */
	function getAllTags($blogid) {

		$alltags = array();

		/**
	 	 * Can't do this via sql because multiple tags for a single post are stored in a single field... grr.
	 	 * ex: $result = sql_query('SELECT tags, count(tags) tagcount FROM '.$this->tablename.' GROUP BY tags');
	 	 * Could even use TOP 5 to limit the query if so.	 	 
	 	 * Instead, have to do this manually.
	 	 */
                $query = "SELECT t.tags FROM ".$this->tablename . " as t";
                
                if ($blogid != 0) {
                        $query .= ", ". sql_table('item') . " as i WHERE t.itemid = i.inumber and i.iblog = ". $blogid;
                }

		$result = sql_query($query);
		if (!$result) {
			return array("Error=$result");
		} else {
			$arrayCounter = 1;
			while ($row = mysql_fetch_object($result)) {
                                if ($row->tags == "") continue;
				// split out the text field, and join it to the holding array
				$alltags = array_merge( $alltags, split(" ",$row->tags) );
			}
			mysql_free_result($result);
		}

		$tagcloud = array_count_values( $alltags );

                $s_perc = $this->getOption('TagShowPercentage');
                if ($s_perc < 100) {
                        $show = count($tagcloud) / 100 * $s_perc;
                        $tagcloud = array_slice($tagcloud, 0, $show);
                }

		/*
		 * may need some better error handling? hmm...
                 */
		return $tagcloud;
	}

	function event_AddItemFormExtras($data){
		$output = <<<EOD
		<h3>Technorati/del.icio.us Tags</h3>
		<p>
			<label for="plugin_technoratitags_field">Tags:</label>
			<input class="adminTags" type="text" autocomplete="off" name="plugin_technoratitags_field" size="40" id="adminTags"/>
			<script>actb(document.getElementById('adminTags'), collection)</script>
		</p>
EOD;
		echo $output;
	}

	function event_EditItemFormExtras($data){
		$output = <<<EOD
		<h3>Technorati/del.icio.us Tags</h3>
		<p>
			<label for="plugin_technoratitags_field">Tags:</label>
			<input class="adminTags" type="text" autocomplete="off" name="plugin_technoratitags_field" size="40" id="adminTags" value="{TAGS}"/>
			<script>actb(document.getElementById('adminTags'), collection)</script>
		</p>

EOD;
		$tags = $this->getTags($data['itemid']);
		$tags = implode(" ",$tags);
		$output = str_replace('{TAGS}',$tags,$output);
		echo $output;
	}

	/** 
	 * Create a new row for tags of this post 
	 */
	function event_PostAddItem($data){
		$itemid = $data['itemid'];
		$tags = requestVar('plugin_technoratitags_field');

		$tag_arr = array();
		$tag_arr = explode(" ",$tags);
		$tag_arr = array_unique($tag_arr);
		$tags = implode(" ",$tag_arr);

		/* Let's do some cleanup, just in case :-) */
		//$tags = mysql_escape_string(htmlspecialchars(urldecode($tags))); NEED TO FIX THIS AND ADD THE SAME TO event_PreUpdateItem()
		sql_query("INSERT INTO ".$this->tablename." (itemid,tags) VALUES (".$itemid.",'".$tags."')");

                if ($this->getOption('DelIcioUs') == "yes") {
                        global $manager, $CONF;
                        // need to reset the ItemURL so createItemLink work properly
                        $item = &$manager->getItem($itemid, 0, 0);
                        $blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
                        $CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());
                        $url = createItemLink($itemid);

                        // get item info
                        $title = $data['title'] != '' ? $data['title'] : $item['title'];

                        $authorid = $item['authorid'];
                        $user = $this->getMemberOption($authorid,'DeliciousUser');
                        $password = $this->getMemberOption($authorid,'DeliciousPassword');

                        if ($user != '' && $password !='') {
                                $oPhpDelicious = new PhpDelicious($user, $password);
                                $oPhpDelicious->AddPost($url, $title, '', $tag_arr);
                        }
                }
	}

	/** 
	 * There seems to be no PostUpdateItem event so here we go 
	 */
	function event_PreUpdateItem($data){
		$mode = 'insert';
		$itemid = $data['itemid'];
		$tags = requestVar('plugin_technoratitags_field');		

		$tag_arr = array();
		$tag_arr = explode(" ",$tags);
		$tag_arr = array_unique($tag_arr);
		$tags = implode(" ",$tag_arr);

		/* First check if there is already a row for this post */
		$result = sql_query("SELECT * FROM ".$this->tablename." WHERE itemid=".$data['itemid']);
		if (mysql_num_rows($result) > 0){
			$mode = 'update';
		}
		mysql_free_result($result);
		if ($mode == 'insert'){
			$query = "INSERT INTO ".$this->tablename." (itemid,tags) VALUES (".$itemid.",'".$tags."')";	
		} // insert
		else {
			$query = "UPDATE ".$this->tablename." SET tags = '".$tags."' WHERE itemid = ".$itemid;
		} // update
		sql_query($query);

                if ($this->getOption('DelIcioUs') == "yes") {
                        global $manager, $CONF;
                        // need to reset the ItemUML so createItemLink work properly
                        $blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
                        $CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());
                        $url = createItemLink($itemid);

                        // get item info
                        $item = &$manager->getItem($itemid, 0, 0);
                        $title = $data['title'] != '' ? $data['title'] : $item['title'];

                        $authorid = $item['authorid'];
                        $user = $this->getMemberOption($authorid,'DeliciousUser');
                        $password = $this->getMemberOption($authorid,'DeliciousPassword');

                        if ($user != '' && $password != '') {
                                $oPhpDelicious = new PhpDelicious($user, $password);
                                $oPhpDelicious->AddPost($url, $title, '', $tag_arr);
                        }
                }
	}

        // need to get url and authorid before we delete the item....
        function event_PreDeleteItem($data) {
                global $manager, $CONF;
                // need to reset the ItemUML so createItemLink work properly
                $blog =& $manager->getBlog(getBlogIDFromItemID($data['itemid']));
                $CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());
                $this->delurl = createItemLink($data['itemid']);
                $item = &$manager->getItem($data['itemid'], 0, 0);
                $this->delaid = $item['authorid'];
        }

	/**
	 * Remove the technoratitags rows for the specified post, as well as from del.icio.us
	 */
	function event_PostDeleteItem($data){
		$itemid = $data['itemid'];
		sql_query('DELETE FROM '.$this->tablename.' WHERE itemid = '.$itemid);

                if ($this->getOption('DelIcioUs') == "yes") {
                        // get user/password
                        $user = $this->getMemberOption($this->delaid,'DeliciousUser');
                        $password = $this->getMemberOption($this->delaid,'DeliciousPassword');

                        if ($user != '' && $password != '') {
                                $oPhpDelicious = new PhpDelicious($user, $password);
                                $oPhpDelicious->DeletePost($this->delurl);
                        }
                }
	}

	/** 
	 * Insert the tags into the item-body so that they are
	 * also displayed in the short view without having to alter
	 * any templates ;-) Lazy one inside ^_^
	 */
	function event_PreItem($data){
		if ($this->getOption('AppendTag') == 'no'){
			return;
		}
		$tags = $this->getTags($data['item']->itemid);
		if (count($tags) > 0){
			if (count($tags) == 1 && $tags[0]== ''){
				$this->originalPost = NULL;
				return;
			}
			if ($data['item']->more == "")
				$body = &$data['item']->body;
			else
				$body = &$data['item']->more;
			$content = $this->getOption('ListLook');
			$itemlook = $this->getOption('TagLook');
			$separator = $this->getOption('TagSeparator');
			$list = "";
			for($i = 0 ; $i<count($tags) ; $i++){
				$t = $tags[$i];
				if ($t == '') continue;
				if ($this->getOption('PlusSwitch') == 'yes'){
					$displayed_tag = str_replace('+','&nbsp;',$t);
				}
				else {
					$displayed_tag = $t;
				}
				$tag=str_replace('%t',$t,$itemlook);
				$tag=str_replace('%d',$displayed_tag,$tag);
				$list.=$tag;
				/* If this isn't the last tag, append the seperator */
				if ($i < count($tags)-1){
					$list.=$separator;
				}
			}
			$content = str_replace('%l',$list,$content);
                        if ($this->getOption('AppendTagType') == 0) {
                                $content = str_replace('%TAGURL%',$this->technoratiurl, $content);
                        }
                        else {
                                $content = str_replace('%TAGURL%',$this->deliciousurl, $content);
                        }
			$body = $body.$content;
		}
		else {
			$this->originalPost = NULL;
		}
	}

	/**
	 * Restore orignal post content just to be on the safe side 
	 */
	function event_PostItem($data){
		if ($this->originalPost != NULL){
		}
	}

	/**
	 * <%TechnoratiTags%> template function
	 */
	function doTemplateVar(&$item, $what = ''){
		global $blog, $CONF;

		// get list of tags for this item
		$tags = $this->getTags($item->itemid);

		// rss mode, to add <category> for each tag in the rss feed so Technorati can pick it up
		if ($what == "rss"){
			if (count($tags) > 0){
				if (count($tags) == 1 && $tags[0]== ''){
					return;
				}

				for($i = 0 ; $i<count($tags) ; $i++){
					$t = $tags[$i];
                                        if ($this->getOption('PlusSwitch') == 'yes'){
                                                $displayed_tag = str_replace('+',' ',$t);
                                        }
                                        else {
                                                $displayed_tag = $t;
                                        }
					if ($t == '') continue;
					echo "<category>" . $displayed_tag . "</category>";
				}
			}
			return;
		}

		// for dtag (del.icoc.us tag link), ltag (local tag link), default mode (technorati tag link)
		if (count($tags) > 0){
			// no tag to show
			if (count($tags) == 1 && $tags[0]== ''){
			        echo($this->getOption('NoneText')) ;
				$this->originalPost = NULL;
				return;
			}

			$content = $this->getOption('ListLook');
			$itemlook = $this->getOption('TagLook');
			$separator = $this->getOption('TagSeparator');
			$list = "";

			for($i = 0 ; $i<count($tags) ; $i++){
				$t = $tags[$i];
				if ($t == '') continue;
				if ($this->getOption('PlusSwitch') == 'yes'){
					$displayed_tag = str_replace('+','&nbsp;',$t);
				}
				else {
					$displayed_tag = $t;
				}
				$tag=str_replace('%t',$t,$itemlook);

                                if ($what=="dtag") {
				        $tag=str_replace('%TAGURL%',$this->deliciousurl,$tag);
                                }
				else if ($what=="ltag") {
					$link = $blog->getURL();
					if (substr($link, -1) != '/') {
						if (substr($link, -4) != '.php') {
							$link .= '/';
						}
					}
					if ($CONF['URLMode'] == 'pathinfo') {
						$link .=  'tags/';
					} else {
						$link .= 'tags.php?tag=';
					}
					// need to strip / as well since we are appending tags/ or tags.php?tag= here...
				        $tag=str_replace('%TAGURL%/',$link,$tag);
				}
                                else {
				        $tag=str_replace('%TAGURL%',$this->technoratiurl,$tag);
                                }
				$tag=str_replace('%d',$displayed_tag,$tag);
				$list.=$tag;
				/* If this isn't the last tag, append the seperator */
				if ($i < count($tags)-1){
					$list.=$separator;
				}
			}
			$content = str_replace('%l',$list,$content);

			echo $content;
		}
		else {
			echo($this->getOption('NoneText')) ;
		}
	}

	/*
	 * Execute the skinvar.
	 *
	 * @author Adam Harvey
	 */
	function doSkinVar($skinType, $type = 'cloud', $sort = 'alp', $maxtags = -1, $blogid="current") {
                global $blog, $manager, $CONF;

		if (!$blog) {
			echo "<!-- TechnoratiTags fatal error: no blog object?? -->";
			ACTIONLOG::add(WARNING, 'TechnoratiTags Error:' . serverVar("REQUEST_URI"));
		}

                if ($type == 'tagsearch') {
                        if ($CONF['URLMode'] == 'pathinfo') {
                                $tagpath = 'tags';
                                $uri  = serverVar('REQUEST_URI');
                                $temp = explode($tagpath . '/', $uri, 2);
                                if (!empty($temp[1])) {
                                        if (function_exists('mb_convert_encoding')) {
                                                $tag = mb_convert_encoding($temp[1], _CHARSET, _CHARSET);
                                                $tag = rawurldecode($tag);
                                        }
                                        else {
                                                 // This will not work for UTF-8 tag..... not something 
                                                 // we can fix unless we bundle mb_convert_encoding()
                                                 $tag = urlencode($temp[1]);
                                        }
                                }
                        } 
                        else {
                                $tag = str_replace(' ','+',RequestVar('tag'));
                                if (function_exists('mb_convert_encoding')) {
                                         $tag = mb_convert_encoding($tag, _CHARSET, _CHARSET);
                                         $tag = rawurldecode($tag);
                                }
                                else {
                                         // This will not work for UTF-8 tag..... not something 
                                         // we can fix unless we bundle mb_convert_encoding()
                                         $tag = urlencode($tag);
                                }
                        }

                        if ($tag == '') {
                                return;
                        }

                        if ($this->getOption('PlusSwitch') == 'yes'){
                                $displayed_tag = str_replace('+','&nbsp;',$tag);
                        }
                        else {
                                $displayed_tag = $tag;
                        }
						
                        echo "<div class=\"contenttitle\"><h2>" . $this->getOption('SearchTitleText') . " " . $displayed_tag . "</h2></div>";

                        // **** need better than tags like %% ??? *****
                        $query = "select t.itemid, i.ititle from " . $this->tablename . " as t, ". sql_table('item')
                                 . " as i where tags like \"%" . $tag . "%\" and t.itemid = i.inumber "; 
                        if ($blogid == "current") {
                                $query .= " and i.iblog = " . $blog->getID();
                        }
                        else if (is_numeric($blogid)) {
                                $query .= " and i.iblog = " . $blogid;
                        }
                        // else for "all", which has not i.iblog=xyz

                        $query .= " order by i.inumber";

                        // else for "all" or anything we will show tagged posts from all blogs.... 
                        // it's a feature, not a bug..... I could have choke it here...

                        $res = sql_query($query);
                        echo "<ul>";

                        while ($row = mysql_fetch_object($res)){
                                $link = createItemLink($row->itemid);
                                echo "<li><a href=\"" . $link . "\">" . $row->ititle . "</a></li>";
                        }
                        echo "</ul>";

                }
		else if ($type == 'cloud' || $type == 'dcloud' || $type == 'localcloud') {

                        if ($blogid == "current") {
                                $blogid = $blog->getID();
                        }
                        else if (is_numeric($blogid)) {
                                // $blogid provided by user
                        }
                        else {
                                $blogid = 0;
                        }

			$tags = $this->getAllTags($blogid);

			if ($sort == 'pop') {
				arsort($tags);
				$newtags = array();
				$newtags = $tags;
			} else {
				asort($tags);

				$iTagLoop = 0;
				$newtags = array();
				foreach ($tags as $curtag=>$curtagcount) {
					$newtags[$curtag] = $curtagcount;
					$iTagLoop = $iTagLoop + 1;
				}

				ksort($newtags);
			}

			// Show only x tags
			if ($maxtags > 0) $newtags = array_slice($newtags, 0, $maxtags);
			
			/*
			psuedo code
			get the max value
			get the min value
			diff the max - min
			diff / 3 (or change the # to change how many sizes of fonts you want)			
			*/
			$max = max($newtags);
			$min = min($newtags);
			$diff = $max - $min;
			$dist = round($diff / 4);

			/*
			 * Display the cloud.
			 * TODO: Extract style into a more friendly manner. (External CSS classes)
			 */
			 
			// cant figure out a good way to fit this in, or even if we want to.
			$separator = $this->getOption('TagSeparator');
			
			foreach ($newtags as $curtag=>$curtagcount) {
                                $count = "";
				if ($curtagcount == $min)  { echo "<span class=\"tinyT\">"; } // SMALLEST
				else if ($curtagcount == $max) { echo "<span class=\"largeT\">"; } // LARGEST
				else if ($curtagcount >= ($min + ($dist * 2))) { echo "<span class=\"mediumT\">"; } // MEDIUM
				else { echo "<span class=\"smallT\">"; } // SMALL

                                if ($this->getOption('ShowCount') == "yes") {
                                        $count = " [".$curtagcount. "]";
                                }

				if ($this->getOption('PlusSwitch') == 'yes'){
					$displayed_tag = str_replace('+','&nbsp;',$curtag);
				}
				else {
					$displayed_tag = $curtag;
				}

                                if ($type == 'cloud') {
				        echo "<a href=\"" . $this->technoratiurl . "/$curtag\" title=\"Find tag $curtag on Technorati\" style=\"background: none;padding: 0px; margin: 0px; text-decoration: none;\">" . $displayed_tag . $count."</a>";
                                }
                                else if ($type == 'dcloud') {
				        echo "<a href=\"" . $this->deliciousurl . "/$curtag\" title=\"Find tag $curtag on del.icio.us\" style=\"background: none;padding: 0px; margin: 0px; text-decoration: none;\">".$displayed_tag.$count."</a>";
                                } else {
                                        $link = $blog->getURL();
                                        if (substr($link, -1) != '/') {
                                                if (substr($link, -4) != '.php') {
                                                        $link .= '/';
                                                }
                                        }
                                        if ($CONF['URLMode'] == 'pathinfo') {
                                                $link .=  'tags/' . $curtag;
                                        } else {
                                                $link .= 'tags.php?tag=' . $curtag;
                                        }
                                        echo "<a href=\"" . $link . "\" style=\"background: none;padding: 0px; margin: 0px; text-decoration: none;\">".$displayed_tag.$count."</a>";
                                }
				echo "</span>\n"; // finish it off
			}

		}
	}

	function init_AC() {
		global $manager, $CONF;

		$this->tag_array = array();

		$maxTags = intval($this->getOption('maxTags'));

                $query = "SELECT tags FROM `" . $this->tablename . "`";
                $result = sql_query($query);

		while ($row = mysql_fetch_object($result)) {
			if ($row->tags == "") continue;
			// split out the text field, and join it to the holding array
			$this->tag_array = array_unique(array_merge($this->tag_array, split(" ",$row->tags)));
			if (sizeof($this->tag_array) > $maxTags) break;
		}
		mysql_free_result($result);
		$this->tag_array = array_slice($this->tag_array, 0, $maxTags);

	}

        function event_AdminPrePageHead(&$data) {
                global $CONF;

		$this->init_AC();

                if (($data['action'] != 'itemedit') && ($data['action'] != 'createitem'))
                 return;

                $tag_array = $this->tag_array;
                $tag_string = '';

                foreach ($tag_array as $tag ) {
                      $tag_string = $tag_string ? $tag_string . ',' . '"'.$tag.'"' : '"'.$tag.'"';
                }

                $data['extrahead'] .= '<script type="text/javascript">var collection = new Array('.$tag_string .');</script>';
                $data['extrahead'] .= '<script type="text/javascript" src="'.$CONF['AdminURL'].'plugins/technoratitags/actb.js"></script>';
                $data['extrahead'] .= '<script type="text/javascript" src="'.$CONF['AdminURL'].'plugins/technoratitags/common.js"></script>';
                $data['extrahead'] .= '<style> #tat_table { width:250px; } </style> ';
        }

        function event_BookmarkletExtraHead(&$data) {
                $data['action'] = 'createitem';
                $this->event_AdminPrePageHead(&$data);
        }
}
?>
