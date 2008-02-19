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
	  v1.8	- mod by PiyoPiyoNaku, 02-Mar-2007
			- adds third skinvar parameter <%LatestComments(,,member)%> to have output similar to NP_MemberComments
	  v1.81 - mod by PiyoPiyoNaku, 09-Mar-2007
			- code cleaning regarding compatibility with NP_Alias [Must use the new NP_Alias v1.3 to make the compatibility works]
	  v1.82 - mod by PiyoPiyoNaku, 12-Mar-2007
			- language support for Japanese-utf8
	  v1.83 - mod by PiyoPiyoNaku, 16-Mar-2007
			- maximum number of characters for each name
          v1.84 - mod by Edmond Hui
	  		- use sql_*
  */

class NP_LatestComments extends NucleusPlugin {

	function getEventList() { return array(); }
	function getName() { return 'Latest Comments'; }
	function getAuthor()  { return 'anand | moraes | admun | e-Musty | PiyoPiyoNaku'; }
	function getURL()  { return 'http://www.renege.net/'; }
	function getVersion() { return '1.84'; }
	function getDescription() {
		return _LCOM_DESC;
	}
	
	//<mod by PiyoPiyoNaku>
	function init() {
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if ($language == "japanese-utf8")
		{
			define(_LCOM_DESC,				'最新のコメントを表示するプラグイン。 スキンへの記述： &lt;%LatestComments%&gt;');
			define(_LCOM_OPTHEAD,			'コメントの一覧のヘッダ。デフォルトは 「&lt;ul&gt;」');
			define(_LCOM_OPTFORMAT,			'コメント一覧の本体。デフォルトは 「&lt;li&gt;&lt;a href="%l" title="Posts to: %p"&gt;%u&lt;/a&gt; says %c&lt;/li&gt;」');
			define(_LCOM_OPTFOOT,			'コメントの一覧のフッタ。デフォルトは 「&lt;/ul&gt;」');
			define(_LCOM_DATEFORMAT,			'日付の形式。デフォルトは 「Y-m-d H:i:s」');
			define(_LCOM_OPT1,				'ディスプレイ名はメンバー短縮名？デフォルトは 「はい」');
			define(_LCOM_OPT2,				'1コメント中に表示するキャラクターの数。デフォルトは 「85」');
			define(_LCOM_OPT3,				'ワードの終わりにコメントはブレイクするですか？デフォルトは 「はい」');
			define(_LCOM_OPT4,				'ディスプレイ名のキャラクターの数。デフォルトは 「15」');
		}
		else
		{
			define(_LCOM_DESC,				'This plugin can be used to display the last few comments. Skinvar: &lt;%LatestComments%&gt;');
			define(_LCOM_OPTHEAD,			'Header formatting. Default is &lt;ul&gt;');
			define(_LCOM_OPTFORMAT,			'Comment formatting. Default is &lt;li&gt;&lt;a href="%l" title="Posts to: %p"&gt;%u&lt;/a&gt; says %c&lt;/li&gt;');
			define(_LCOM_OPTFOOT,			'Footer formatting. Default is &lt;/ul&gt;');
			define(_LCOM_DATEFORMAT,		'Date format. Default is Y-m-d H:i:s');
			define(_LCOM_OPT1,				'Display name is short member name? Default is Yes');
			define(_LCOM_OPT2,				'Max characters in each comment. Default is 85');
			define(_LCOM_OPT3,				'Break comment at the end of the word? Default is Yes');
			define(_LCOM_OPT4,				'Max characters of display name. Default is 15');
		}
	}
	//</mod by PiyoPiyoNaku>

	function install() {
		$this->createOption('option1',_LCOM_OPT1,'yesno','yes');
		$this->createOption('option2',_LCOM_OPT2,'text','85');
		$this->createOption('option3',_LCOM_OPT3,'yesno','yes');
		//<mod by PiyoPiyoNaku>
		$this->createOption('option4',_LCOM_OPT4,'text','15');
        $this->createOption('dateformat',_LCOM_DATEFORMAT,'text','Y-m-d H:i:s');
		//</mod by PiyoPiyoNaku>
		$this->createOption('header',_LCOM_OPTHEAD,'textarea','<ul>');
		$this->createOption('comment',_LCOM_OPTFORMAT,'textarea','<li><a href="%l" title="Posts to: %p">%u</a> says %c</li>');
		$this->createOption('footer',_LCOM_OPTFOOT,'textarea','</ul>');
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

	// skinvar plugin can have a blogname as second parameter
	function doSkinVar($skinType) {
		global $manager, $blog, $CONF, $memberid;
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
		
		//<mod by PiyoPiyoNaku>
		$option4 = $this->getOption('option4');
		if ($option4) { 
			$numberOfName = $option4; 
		} else { 
			$numberOfName = 15;
		}
		//</mod by PiyoPiyoNaku>

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
		
		//<mod by PiyoPiyoNaku>
		if(!$params[3]) {
			$memberonly = '';
		}
		else if ($params[3] == "member") {
			if (!$blogid) {
				$memberonly = ' WHERE cmember='. $memberid;
			}
			else {
				$memberonly = ' AND cmember='. $memberid;
			}
		}

		$query = "SELECT cuser, cbody, citem, cmember, ctime, cnumber FROM ".sql_table('comment')." ".$blogid.$memberonly." ORDER by ctime DESC LIMIT 0,".$numberOfComments;
		//</mod by PiyoPiyoNaku>

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
					$myname = $mem->getDisplayName();
					$pluginName = 'NP_Alias';
					if ($manager->pluginInstalled($pluginName))
					{
						$pluginObject =& $manager->getPlugin($pluginName);
						if ($pluginObject) {
							$myname = $pluginObject->getAliasfromMemberName($myname);
						}
					}
					//</mod by PiyoPiyoNaku>
				}
				// show real member names
				else { 
					$myname = $mem->getRealName(); 
				}
			}
			
			//<mod by PiyoPiyoNaku>
			if ( strlen($myname) > $numberOfName ) {
				$displayedName = substr($myname,0,$numberOfName);
				$displayedName .= "...";
			}
			else {
				$displayedName = $myname;
			}
			//</mod by PiyoPiyoNaku>

			$itemlink = createItemLink($row->citem, '');

			if ( strlen($text) > $numberOfCharacters ) {
				$ctext .= "...";
			}

			$out .= str_replace("%u", $displayedName, $com_templ);
			$out = str_replace("%l", $IndexURL.$itemlink."#".$row->cnumber, $out);
			$out = str_replace("%P", $IndexURL.$itemlink, $out);
			$out = str_replace("%c", $ctext, $out);
			//<mod by PiyoPiyoNaku>
			$out = str_replace("%t", date($this->getOption('dateformat'),strtotime($ctime)), $out);
			//</mod by PiyoPiyoNaku>

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
