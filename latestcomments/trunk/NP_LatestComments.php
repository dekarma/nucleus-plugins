<?php
/**
  * This plugin can be used to display the last few comments.

    Updates and documentation can be found here on the Nucleus Wiky:
    http://www.xiffy.nl/wakka/LatestComments



    History:
      v0.91 - Fixed XHTML warning
      v1.0  - Performance enhancement
      v1.1  - option to add extra <br /> between comment
            - change LatestComments(#,0) to LatestComments(#,actual) to
              display comment to actual/current blog.... the old code didn't
              work for some reason
            - added anchor to comment
      v1.2  - e-Musty (krank@krank.hu), 17/07/2004
              supportsFeature function was missing, however the code itself has 
              been already based on its support. This function has been added.
              Now compatible with Nucleus 3.
      v1.3  - improve comment tag naming
            - add "..." only when the comment is longer than comment display length (Thanks to JH)
      v1.4  - template formatting: %u (user), %c (comment), %t (time), %p (item)
      v1.5  - split %u into %u (user) and %l (link to comment)
      v1.5a - add %P
      v1.6  - call PreItem event to enable NP_Smiley support
            - batch all comments to display in one shot
      v1.7  - mod by PiyoPiyoNaku (http://www.renege.net), 03-Feb-2007
              option to change date format.
      v1.71 - mod by PiyoPiyoNaku, 04-Feb-2007
	    - fix my typo on the history
	    - compatible with NP_Alias
      v1.71a - Use sql_query so benchamark function can pick up the activity
  */

class NP_LatestComments extends NucleusPlugin {

	function getEventList() { return array(); }
	function getName() { return 'Latest Comments'; }
	function getAuthor()  { return 'anand | moraes | admun | e-Musty | PiyoPiyoNaku'; }
	function getURL()  { return 'http://www.renege.net/'; }
	function getVersion() { return '1.71a'; }
	function getDescription() {
		return 'This plugin can be used to display the last few comments.';
	}

	function install() {
		$this->createOption('option1','Show short member names instead of real member names?','yesno','yes');
		$this->createOption('option2','Max number of characters in each comment:','text','85');
		$this->createOption('option3','Break comments at the end of word instead of cut off in the middle?','yesno','yes');
		//<mod by PiyoPiyoNaku>
        $this->createOption('dateformat','Date format (using PHP date() function)','text','Y-m-d H:i:s');
		//end of <mod by PiyoPiyoNaku>
		$this->createOption('header','Header formatting','textarea','<ul>');
		$this->createOption('comment','Comment formatting','textarea','<li><a href="%l" title="Posts to: %p">%u</a> says %c</li>');
		$this->createOption('footer','Footer formatting','textarea','</ul>');
	}

	// Make it compatible w/ Nucleus 3
	function supportsFeature($feature) {
		switch($feature) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	//<PiyoPiyoNaku's mod>
	function PrintAlias(&$membername, &$aliasname) {
		global $blog;

		$sql = "SELECT mnumber FROM `nucleus_member` WHERE mname='" . $membername . "'";
		$res = sql_query($sql);
		$id = mysql_fetch_object($res);
		$id = $id->mnumber;

		if ($id) { 
			$sql = "SELECT * FROM `nucleus_team` WHERE tmember='" . $id . "' AND tblog='" . $blog->getID() . "'";
			$res = sql_query($sql);
						
			$alias = mysql_fetch_object($res);
			$alias = $alias->alias;
			
			if ($alias)
			{
				$aliasname = $alias;
			} 
		}
	} 
	//end of <PiyoPiyoNaku's mod>

	// skinvar plugin can have a blogname as second parameter
	function doSkinVar($skinType) {
		global $manager, $blog, $CONF;
		$params = func_get_args();
		$option1 = $this->getOption('option1');
		$option2 = $this->getOption('option2');
		$option3 = $this->getOption('option3');
		$com_templ = $this->getOption('comment');

		$numberOfComments   = 5; // default number of comments

		if ($option2) { 
			$numberOfCharacters = $option2; 
		} else { 
			$numberOfCharacters = 85; 
		}

		// how many comments will be shown?
		if ($params[1]) {
			$numberOfComments = $params[1]; 
		}

		// show comments from all blogs
		if (!$params[2]) { 
			$blogid = "";
		}
		// show comments from the actual blog
		else if ($params[2] == "actual") { 
			$blogid = " WHERE cblog=".$blog->getID(); 
		}
		// show comments from the default blog
		else if ($params[2] == "default") {
			$blogid = " WHERE cblog=".$CONF['DefaultBlog'];  
		}
		// show comments from the selected blog id
		else { 
			$blogid = " WHERE cblog=".($params[2]); 
		}

		$query = "SELECT cuser, cbody, citem, cmember, ctime, cnumber FROM ".sql_table('comment')." ".$blogid." ORDER by ctime DESC LIMIT 0,".$numberOfComments;

		$comments = sql_query($query);

		echo($this->getOption('header'));
		$out = "";
		while($row = mysql_fetch_object($comments)) {
			$text  = $row->cbody;
			$text  = strip_tags($text);
			$ctime =  $row->ctime;

			// break comments by words
			if ($option3 == "yes") {
				//only process this loop, if the sting is longer
				//than $numberOfCharacters
				if ( strlen($text) > $numberOfCharacters ) {
					//first cut off the characters
					//behind $numberOfCharacters
					$ctext = substr($text,0,$numberOfCharacters);

					//now find the last " " within the string and
					//extract the part before that " "
					$ctext = substr($ctext,0,strrpos($ctext," "));

				}
				// else use the string as it is
				else {
					$ctext = $text;
				}
			}
			// break comments by characters
			else { 
				$ctext = substr($text, 0, $numberOfCharacters); 
			}


			if (!$row->cmember) {
				$myname = $row->cuser;
			} else {
				$mem = new MEMBER;
				$mem->readFromID(intval($row->cmember));
				// show short member names
				if ($option1 == "yes") {
					//<mod by PiyoPiyoNaku>
					$membername = $mem->getDisplayName();
					$this->PrintAlias($membername, $aliasname);
					if ($aliasname) {
						$myname = $aliasname;
						$aliasname = "";
					} else {
						$myname = $membername; 
					}
					//end of <mod by PiyoPiyoNaku>
				}
				// show real member names
				else { 
					$myname = $mem->getRealName(); 
				}

			}

			$itemlink = createItemLink($row->citem, '');

			if ( strlen($text) > $numberOfCharacters ) {
				$ctext .= "...";
			}

			$out .= str_replace("%u",$myname, $com_templ);
			$out = str_replace("%l", $IndexURL.$itemlink."#".$row->cnumber, $out);
			$out = str_replace("%P", $IndexURL.$itemlink, $out);
			$out = str_replace("%c", $ctext, $out);
			//$out = str_replace("%t", $ctime, $out);
			//<mod by PiyoPiyoNaku>
			$out = str_replace("%t", date($this->getOption('dateformat'),strtotime($ctime)), $out);
			//end of <mod by PiyoPiyoNaku>

			if (strpos($out, "%p")) {
				$citem = $manager->getItem($row->citem, 0, 0);
				$out = str_replace("%p", $citem['title'], $out);
			}

		}

		// Call PreComment event to trigger other plugins to process the output before we display it
		$comment['body'] = $out;
		$manager->notify('PreComment', array('comment' => &$comment));
		echo($comment['body']);

		echo($this->getOption('footer'));

	}
}
?>
