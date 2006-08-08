<?php

class NPG_COMMENTS {

	var $itemid;
	var $itemactions;
	var $commentcount;
	
	function NPG_COMMENTS($itemid) {
		$this->itemid = intval($itemid);
	}
	
	function setItemActions(&$itemActions) {
		$this->itemActions =& $itemActions;
	}
	
	function showComments( & $template, $maxToShow = -1, $showNone = 1) {
		
		
		$actions = & new NPG_COMMENTACTIONS($this);
		$parser = & new PARSER($actions->getdefinedactions(), $actions);
		$actions->settemplate($template);
		$actions->setparser($parser);
		
		if ($maxToShow == 0) {
			$this->commentcount = $this->amountComments();

		} else {
			$query = 'select * from '.sql_table('plug_gallery_comment').
				' where cpictureid='.$this->itemid.' order by ctime';
			$comments = sql_query($query);
			$this->commentcount = mysql_num_rows($comments);
			
		}
	
		if($this->commentcount == 0) {
			echo __NPG_NO_COMMENTS.'<br/>';
			return 0;
		}
		if (($maxToShow != -1) && ($this->commentcount > $maxToShow)) return 0;
		
		
		//$template->readall();
		$parser->parse($template->section['COMMENT_HEADER']);
		while($comment = mysql_fetch_assoc($comments)) {
			$actions->setcurrentcomment($comment);
			$parser->parse($template->section['COMMENT_BODY']);
		}
		$parser->parse($template->section['COMMENT_FOOTER']);
		
		mysql_free_result($comments);
		return $this->commentcount;
		
	}
	
	function amountComments() {
		$query = 'select count(*)'.
			' from '.sql_table('plug_gallery_comment').
			' where cpictureid='.$this->itemid;
		$res = sql_query($query);
		$arr = mysql_fetch_row($res);
		return $arr[0];
	}
	
	function addComment($comment) {
		global $member,$NPG_CONF,$CONF;
		
		if ($CONF['ProtectMemNames'] && !$member->isLoggedIn() && MEMBER::isNameProtected($comment['user']))
			return _ERROR_COMMENTS_MEMBERNICK;
		
		$isvalid = $this->isValidComment($comment);
		if ($isvalid != 1)
			return $isvalid;
		
		
		$comment['host'] = gethostbyaddr(serverVar('REMOTE_ADDR'));
		$comment['ip'] = serverVar('REMOTE_ADDR');
		
		if ($member->isLoggedIn()) {
			$comment['memberid'] = $member->getID();
			$comment['user'] = '';
			$comment['userid'] = '';
		} else {
			$comment['memberid'] = 0;
		}
 		
 		$comment = NPG_COMMENT::prepare($comment);
 		$name = addslashes($comment['user']);
		$usid = addslashes($comment['userid']);
		$body = addslashes($comment['body']);
		$host = addslashes($comment['host']);
		$ip = addslashes($comment['ip']);
		$memberid  = intval($comment['memberid']);
		$pictureid = $this->itemid;
 		
 		$query = 'insert into '.sql_table('plug_gallery_comment').
 			'(cbody, cuser, cmail, chost, cip, cmemberid, ctime, cpictureid) '.
 			" values ('$body','$name','$usid','$host','$ip','$memberid',NULL,$pictureid) ";
 		sql_query($query);
 		$commentid = mysql_insert_id();
 		return true;
	}
	
	function isValidComment($comment) {
		global $member,$manager;
		
		if (eregi('[a-zA-Z0-9|\.,;:!\?=\/\\]{90,90}',$comment['body']) != false)
			return _ERROR_COMMENT_LONGWORD;

		// check lengths of comment
		if (strlen($comment['body'])<3)
			return _ERROR_COMMENT_NOCOMMENT;

		if (strlen($comment['body'])>5000)
			return _ERROR_COMMENT_TOOLONG;

		// only check username if no member logged in
		if (!$member->isLoggedIn())
			if (strlen($comment['user'])<2)
				return _ERROR_COMMENT_NOUSERNAME;
		
		$result = 1;
		
		$manager->notify('ValidateForm', array('type' => 'comment', 'comment' => &$comment, 'error' => &$result));
		
		return $result;
	}
	
}

class NPG_COMMENT extends COMMENT {


}


class NPG_COMMENTACTIONS extends BaseActions {
	var $currentComment;
	var $commentsObj;
	var $parser;
	var $template;
	
	function NPG_COMMENTACTIONS(&$comments) {
		$this->BaseActions();
		$this->setCommentsObj($comments);
	}
	
	function getdefinedactions() {
		return array(
			'commentcount',
			'commentword',
			'picturelink',
			'pictureid',
			'date',
			'time',
			'commentid',
			'body',
			'memberid',
			'host',
			'ip',
			'user',
			'userid',
			'userlink',
			'userlinkraw',
			'timestamp'	);
	}
	
	function setCommentsObj(& $cobj) { $this->commentsObj = & $cobj; }
	function setparser(& $parser) { $this->parser = & $parser; }
	function settemplate(& $template) { $this->template = & $template; }
	function setcurrentcomment(& $comment) {
		if ($comment['cmemberid'] != 0) {
			//$comment['authtext'] = $template['COMMENTS_AUTH'];

			$mem = MEMBER::createFromID($comment['cmemberid']);
			$comment['cuser'] = $mem->getDisplayName();
			if ($mem->getURL())
				$comment['cuserid'] = $mem->getURL();
			else
				$comment['cuserid'] = $mem->getEmail();

			$comment['cuserlinkraw'] = 
				createMemberLink(
					$comment['cmemberid'],
					$this->commentsObj->itemActions->linkparams
				);
		} else {

			// create smart links
			if (isValidMailAddress($comment['userid']))
				$comment['userlinkraw'] = 'mailto:'.$comment['userid'];
			elseif (strstr($comment['userid'],'http://') != false)
				$comment['userlinkraw'] = $comment['userid'];
			elseif (strstr($comment['userid'],'www') != false)
				$comment['userlinkraw'] = 'http://'.$comment['userid'];
		}

		$this->currentComment =& $comment;

	}
	
	function parse_commentcount() {echo $this->commentsObj->commentcount;}
	//this needs to be modified so not hardcoded
	function parse_commentword() { echo 'comment';}
	
	function parse_picturelink() { echo generatelink('item',$this->commentsObj->itemid);}
	function parse_pictureid() { echo $this->commentsObj->itemid; }
	function parse_date() {
		$this->parse_timestamp('l jS of F Y');
	}
	
	function parse_time() {
		$this->parse_timestamp('h:i:s A');
	}
	
	function parse_commentid() {echo $this->currentComment['commentid']; }
	function parse_body() {	echo $this->currentComment['cbody']; }
	function parse_memberid() {	echo $this->currentComment['cmemberid']; }
	function parse_timestamp($format = 'l jS of F Y h:i:s A') {
		$d = $this->currentComment['ctime'];
		$d = converttimestamp($d);
		$d = date($format,$d);
		echo $d;
	}
	function parse_host() {	echo $this->currentComment['chost']; }
	function parse_ip() {	echo $this->currentComment['cip']; }
	
	function parse_user() {	echo $this->currentComment['cuser']; }
	function parse_userid() { echo $this->currentComment['cuserid']; }
	function parse_userlinkraw() { echo $this->currentComment['cuserlinkraw']; }
	function parse_userlink() {
		if ($this->currentComment['cuserlinkraw']) {
			echo '<a href="'.$this->currentComment['cuserlinkraw'].'" rel="nofollow">'.$this->currentComment['cuser'].'</a>';
		} else {
			echo $this->currentComment['cuser'];
		}
	}
	
	
}

?>
