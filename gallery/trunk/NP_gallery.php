<?php
/*
NP_Gallery
Gallery Plugin for nucleus cms http://nucleuscms.org


*/


global $DIR_NUCLEUS;
include_once($DIR_NUCLEUS.'/plugins/gallery/config.php');

class NP_gallery extends NucleusPlugin {

	/*
	var $currentPage; 
	var $currentPageID; 
	var $currentPageOpt; 
*/

	function getName() {return 'Nucleus Image Gallery';}
	function getAuthor()  {	return 'John Bradshaw, Gene Cambridge Tsai';	}
	function getURL() 	{ return 'http://www.sircambridge.net/nucleus/index.php?itemid=57'; 	}
	function getVersion() { return '0.94'; }
	function getDescription() { return 'Image Gallery for Nucleus CMS'; 	}
	function supportsFeature($what) { switch($what) {
		case 'SqlTablePrefix': return 1; break;
		case 'HelpPage': return 1; break;
		default: return 0; break;
		}
	}

	function getTableList() {
		return array(sql_table('plug_gallery_album'), 
		sql_table('plug_gallery_picture'), 
		sql_table('plug_gallery_template'), 
		sql_table('plug_gallery_config'), 
		sql_table('plug_gallery_comment'), 
		sql_table('plug_gallery_album_team'), 
		sql_table('plug_gallery_member'), 
		sql_table('plug_gallery_promo'), 
		sql_table('plug_gallery_views'), 
		sql_table('plug_gallery_views_log'), 
		sql_table('plug_gallery_picturetags') );
	}

	function getEventList() {
		return array('QuickMenu','PreItem');
	}
	
	function hasAdminArea() {
		return 1;
	}
	
	function event_QuickMenu(&$data) {
		global $member;

		if (!($member->isLoggedIn() )) return;
		array_push(
			$data['options'], 
			array(
				'title' => 'gallery',
				'url' => $this->getAdminURL(),
				'tooltip' => 'NP Gallery admin'
			)
		);
	}
	
	function event_PreItem(&$data) {
		
		$actions = new NPG_EXT_ITEM_ACTIONS();
		$parser = new NPG_PREPARSER($actions->getdefinedActions(),$actions);
		$actions->setparser($parser);
		
		//pre-parse item body
		ob_start();
		$parser->parse($data['item']->body);
		$data['item']->body = ob_get_contents();
		ob_end_clean();
		
		//pre-parse item more
		ob_start();
		$parser->parse($data['item']->more);
		$data['item']->more = ob_get_contents();
		ob_end_clean();
		
	}
	

	function install() {
		global $NPG_CONF,$DIR_NUCLEUS;
		
		$this->createOption('deletetables',__NPG_OPT_DONT_DELETE_TABLES,'yesno','no'); 
		
		//create tables
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_album').' ( '.
				'albumid int unsigned not null auto_increment PRIMARY KEY, '.
				'title varchar(255), '.
				'description varchar(255), '.
				'ownerid int unsigned , '.
				'modified TIMESTAMP, '.
				'numberofimages int unsigned, '.
				"thumbnail varchar(100), ".
				'commentsallowed tinyint DEFAULT 1 ) ';
		sql_query($query);
		// code to update table to have publicalbum field
		$query = 'SHOW COLUMNS FROM '.sql_table('plug_gallery_album').' LIKE "publicalbum"';
		$result = sql_query($query);
		if (mysql_num_rows($result) == 0){
				//if it doesnt exist, add it (there must be a better way to do this via SQL syntax, but i couldnt figure it out)
				$query = 'ALTER TABLE '. sql_table('plug_gallery_album').
						' ADD COLUMN publicalbum tinyint DEFAULT 1 AFTER commentsallowed';
				sql_query($query);
		}
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_picture').' ( '.
				'pictureid int unsigned not null auto_increment PRIMARY KEY, '.
				'title varchar(255), '.
				'description varchar(255), '.
				'ownerid int unsigned , '.
				'modified TIMESTAMP, '.
				'albumid int unsigned, '.
				'filename varchar(255), '.
				'int_filename varchar(255), '.
				'thumb_filename varchar(255) ) ';
		sql_query($query);
		
		//add the picturesets column after thumb_filename for people upgrading
		//first test if the picturesets column exists
		$query = 'SHOW COLUMNS FROM '.sql_table('plug_gallery_picture').' LIKE "keywords"';
		$result = sql_query($query);
		if (mysql_num_rows($result) == 0){
				//if it doesnt exist, add it (there must be a better way to do this via SQL syntax, but i couldnt figure it out)
				$query = 'ALTER TABLE '. sql_table('plug_gallery_picture').
						' ADD COLUMN keywords varchar(255) AFTER thumb_filename';
				sql_query($query);
		}
		// this is to change the descriptions to have text up to 64k characters instead of 255 characters.
		//had to put it here in case someone is upgrading.
		$query = 'ALTER TABLE '. sql_table('plug_gallery_picture').
				 ' MODIFY description TEXT';
		sql_query($query);

		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_template').' ( '.
				'tdesc int unsigned, '.
				'name varchar(20), '.
				'content text ) ';
		sql_query($query);
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_template_desc').' ( '.
				'tdid int unsigned not null auto_increment PRIMARY KEY, '.
				'tdname varchar(20), '.
				'tddesc varchar(200) )';
		sql_query($query);
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_config').' ( '.
				'oname varchar(20), ovalue varchar(60) )';
		sql_query($query);
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_album_team').' ( '.
				'tmemberid int unsigned not null, '.
				'talbumid int unsigned not null, '.
				'tadmin tinyint DEFAULT 0 )';
		sql_query($query);
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_member').' ( '.
				'memberid int unsigned not null PRIMARY KEY, '.
				'addalbum tinyint DEFAULT 0 )';
		sql_query($query);
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_comment').' ( '.
				'commentid int unsigned not null auto_increment PRIMARY KEY, '.
				'cbody text, '.
				'cuser varchar(40), '.
				'cmail varchar(100), '.
				'chost varchar(60), '.
				'cip varchar(15), '.
				'cmemberid int unsigned default 0, '.
				'ctime timestamp, '.
				'cpictureid int not null )';
		sql_query($query);
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_promo').' ( '.
				'ppictureid int unsigned not null, '.
				'pblogitemid int unsigned not null )';
		sql_query($query);
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_views').' ( '.
				'vpictureid int unsigned not null PRIMARY KEY, '.
				'views int unsigned )';
		sql_query($query);
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_views_log').' ( '.
				'vlpictureid int unsigned not null, '.
				'ip varchar(20), '.
				'time timestamp )';
		sql_query($query);
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_gallery_picturetag').' ( '.
				'`pictureid` VARCHAR( 255 ) NOT NULL , '.
				'`top` VARCHAR( 255 ) NOT NULL ,'.
				'`left` VARCHAR( 255 ) NOT NULL ,'.
				'`height` VARCHAR( 255 ) NOT NULL ,'.
				'`width` VARCHAR( 255 ) NOT NULL ,'.
				'`text` VARCHAR( 255 ) NOT NULL )';
		sql_query($query);
		
		//set default options
		$NPG_CONF = getNPGconfig();

		if(!$NPG_CONF['viewtime']) setNPGoption('viewtime', 30);
		setNPGoption('currentversion',94);
		
		if(!$NPG_CONF['im_path']) setNPGoption('im_path','/usr/local/bin/'); // currently needs to have trailing slash, need to change to be consistent
		if(!$NPG_CONF['im_options']) setNPGoption('im_options', '-filter Lanczos');
		if(!$NPG_CONF['im_quality']) setNPGoption('im_quality', '80');
		if(!$NPG_CONF['graphics_library']) {
			if (GDisPresent()) {
				setNPGoption('graphics_library', 'gd'); 
			} else if (IMisPresent()) {
				setNPGoption('graphics_library', 'im');
				setNPGoption('im_version', getIMversion());
			} else {
				setNPGoption('graphics_library', 'not configured');
				setNPGoption('configured', false);
			}
		}
		

		if(!$NPG_CONF['galleryDir']) setNPGoption('galleryDir', 'media/gallery'); //when adding, need to make sure that no trailing slash
		if(!$NPG_CONF['thumbwidth']) setNPGoption('thumbwidth', '100');
		if(!$NPG_CONF['thumbheight']) setNPGoption('thumbheight', '100');
		if(!$NPG_CONF['maxwidth']) setNPGoption('maxwidth', '600');
		if(!$NPG_CONF['maxheight']) setNPGoption('maxheight', '600');
		if(!$NPG_CONF['int_prefix']) setNPGoption('int_prefix', 'int_');
		if(!$NPG_CONF['thumb_prefix']) setNPGoption('thumb_prefix', 'thumb_');
		
		if(!$NPG_CONF['max_filesize']) setNPGOption('max_filesize', '2000000');
		if(!$NPG_CONF['add_album']) setNPGOption('add_album', 'admin_only');
		if(!$NPG_CONF['batch_add_num']) setNPGOption('batch_add_num', '10');
		if(!$NPG_CONF['dateorrandom']) setNPGOption('dateorrandom', 'randomprefix');
		if(!$NPG_CONF['tooltips']) setNPGOption('tooltips', 'no');
		if(!$NPG_CONF['nextprevthumb']) setNPGOption('nextprevthumb', 'no');
		if(!$NPG_CONF['defaultorder']) setNPGOption('defaultorder', 'aesc');
		if(!$NPG_CONF['setorpromo']) setNPGOption('setorpromo', 'promo');
		if(!$NPG_CONF['slideshowson']) setNPGOption('slideshowson', 'no');
		if(!$NPG_CONF['thumborlist']) setNPGOption('thumborlist', 'list');


		
		

		

		$chk = checkgalleryconfig();
		if($chk['configured'] == false) setNPGoption('configured',false); else setNPGoption('configured',true);
		
		//?create skin NPGallery or make user do it
				
		//set default templates
		//include($DIR_NUCLEUS.'/plugins/gallery/update/default_templates_076.inc');
		//include($DIR_NUCLEUS.'/plugins/gallery/update/default_templates_080.inc');
		//include($DIR_NUCLEUS.'/plugins/gallery/update/default_templates_090.inc');
		include($DIR_NUCLEUS.'/plugins/gallery/update/default_templates_094.inc');
	}
	
	function unInstall() {
		if ($this->getOption('deletetables') == 'yes') { 
			
			//delete promo posts
			$query = 'select pictureid from '.sql_table('plug_gallery_picture');
			$res = sql_query($query);
			while($row = mysql_fetch_object($res)) {
				PICTURE::deletepromoposts($res->pictureid);
			}
			
			sql_query('DROP TABLE '.sql_table('plug_gallery_album'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_picture'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_template'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_template_desc'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_config'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_album_team'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_member'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_comment'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_promo'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_views'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_views_log'));
			sql_query('DROP TABLE '.sql_table('plug_gallery_picturetag'));
			
		}
	}

	function doAction($type) {
		global $gmember, $CONF, $NPG_CONF;
		global $skinid,$manager,$blog,$blogid;
		
		switch($type) {
			/*
			//display -- these are done in doSkinVar
			case 'mostviewed':
			case 'album':
			case 'item':
			case 'deletePictureF': 
			case 'editPictureF':
			case 'addPictF':
				$this->currentPage = $type;
				$this->currentPageID = requestVar('id');
				break;
			case 'list':
				$this->currentPage = $type;
				$this->currentPageOpt = requestVar('sort');
				break;
			case 'addAlbumF': 
				$this->currentPage = $type;
				break;
			*/
			//these are the actions, done here, then currentpage is set and skin called to display something
			case 'addcomment': 
				global $CONF;

				$post['itemid'] =	intPostVar('itemid');
				$post['user'] = 	postVar('user');
				$post['userid'] = 	postVar('userid');
				$post['body'] = 	postVar('body');

				// set cookies when required
				$remember = intPostVar('remember');
				if ($remember == 1) {
					$lifetime = time()+2592000;
					setcookie($CONF['CookiePrefix'] . 'comment_user',$post['user'],$lifetime,'/','',0);
					setcookie($CONF['CookiePrefix'] . 'comment_userid', $post['userid'],$lifetime,'/','',0);
				}

				$comments = new NPG_COMMENTS($post['itemid']);

				$errormessage = $comments->addComment($post);
				
				//need to add code to display the error
				if ($errormessage == '1') {
					$_POST['id'] = $post['itemid'];
				} 
				/*
				else {
					$this->currentPage = 'list';
					$this->currentPageOpt = 'date';
				}
				*/
				break;
			case 'addAlbum':
				if($gmember->canAddAlbum() ){
					$NPG_vars['ownerid'] = $gmember->getID();
					$NPG_vars['title'] = requestVar('title'); 
					$NPG_vars['description'] = requestVar('desc');
					$NPG_vars['publicalbum'] = requestVar('publicalbum');
					ALBUM::add_new($NPG_vars);
				}
				break;
			case 'finaldeletepicture':
				$id = requestVar('id');
				$delpromo = requestVar('delpromo');
				if($gmember->canModifyPicture($id)) {
					
					$manager->notify('NPgPreDeletePicture', array('pictureid' => $id));
					$result = PICTURE::delete($id);
					
					if($result['status'] == 'error') {
						echo $result['message'];
					}
					else {
						$manager->notify('NPgPostDeletePicture', array('pictureid' => $id));
						
						if($delpromo == 'yes') {
							$result2 = PICTURE::deletepromoposts($id);
							if($result2['status'] == 'error') echo $result2['message'];
						}
						else {
							$_POST['id'] = $result['albumid'];
						}
					}
				} else echo 'No permission to delete picture<br/>';
				break;
			case 'editPicture':
				$id = requestVar('id');
				if($gmember->canModifyPicture($id)) {
					$pict = new PICTURE($id);
					$pict->setTitle(requestVar('ptitle'));
					$pict->setDescription(requestVar('pdesc'));
					$pict->setkeywords(requestVar('keywords'));
					$aid = requestVar('aid');
					if($aid && $gmember->canAddPicture($aid)) {
						ALBUM::decreaseNumberByOne($pict->getAlbumID());
						ALBUM::increaseNumberByOne($aid);
						$pict->setAlbumID($aid);
					}
					$pict->write();
					echo "<SCRIPT LANGUAGE=\"JavaScript\">
					window.location=\"" . $NP_BASE_DIR  . "action.php?action=plugin&name=gallery&type=item&id=". $id . "\"" .
					"</script>";
					break;
					$manager->notify('NPgPostUpdatePicture',array('picture', &$pict));
				}
			case 'tagaccept' :
				$Pos1x = requestVar('Pos1x');
				$Pos1y = requestVar('Pos1y');
				$Pos2x = requestVar('Pos2x');
				$Pos2y = requestVar('Pos2y');
				$RelX = requestVar('RelX');
				$pictureid = requestVar('pictureid');
				$RelY = requestVar('RelY');
				$desc = requestVar('desc');
				$left = $Pos1x - $RelX;
				$top = $Pos1y - $RelY;
				$width = $Pos2x - $Pos1x;
				$height = $Pos2y - $Pos1y;
				$text = $desc;
				//these lines should be moved into picture_class.php
				sql_query("INSERT INTO ".sql_table('plug_gallery_picturetag')." ( `pictureid` , `top` , `left` , `height` , `width` , `text` )
				VALUES ( '" . $pictureid ." ', '" .$top."', '" .$left." ' , '" .$height."' , '" .$width."' , '" .$text."' ); ");
				echo "<SCRIPT LANGUAGE=\"JavaScript\">
				window.location=\"" . $NP_BASE_DIR  . "action.php?action=plugin&name=gallery&type=item&id=". $pictureid . "\"" .
				"</script>";
				break;
			case 'tagdelete' :
				$pictureid = requestVar('pictureid');
				//these lines should be moved into picture_class.php
				sql_query("DELETE FROM ".sql_table('plug_gallery_picturetag'). " WHERE `pictureid` = '" . $pictureid . "' LIMIT 1; ");
				echo "<SCRIPT LANGUAGE=\"JavaScript\">
				window.location=\"" . $NP_BASE_DIR  . "action.php?action=plugin&name=gallery&type=item&id=". $pictureid . " \"" .
				"</script>";
				break;
			// this is done in editpicture now.
			//case 'updatesets':
				//$id = requestVar('id');
				//$setname = requestVar('setname');
				//$pict = new PICTURE($id);
				//$pict->addtoset($id,$setname);
				//$pict->write();
				//$manager->notify('NPgPostUpdatePicture',array('picture', &$pict));
				//break;
			default: 
			break;
		}

		if (!$blogid)
		$blogid = $CONF['DefaultBlog'];

		$b =& $manager->getBlog($blogid);
		$blog = $b;
		
		selectSkin('NPGallery');
		
		$skin =& new SKIN($skinid);
		$skin->parse('index');
	}
	
	
	function doSkinVar() {
		global $NPG_CONF, $gmember, $manager;
		
		$params = func_get_args();
		$numargs = func_num_args();
		$skinType = $params[0];
		
		$type = requestvar('type');
		$id = requestvar('id');
		$startstop = requestvar('startstop');
		$sliderunning = requestvar('sliderunning');
		
		$defaulttoitem = array('editPicture','addcomment');
		$defaulttolist = array('addAlbum');
		$defaulttoalbum = array('finaldeletepicture');
		if(in_array($type,$defaulttoitem)) $type = 'item';
		
		switch($params[1]) {
			case 'link':
				if($numargs >= 3) {
					switch($params[2]) {
						case 'picture': echo generatelink('item',$params[3]); break;
						case 'album': echo generateLink('album',$params[3]); break;
						default: echo generateLink('list'); break;
					}
				} else echo generateLink('list');
				break;
			default:
				//things to display
				
				if(!$NPG_CONF['configured']) {
					echo __NPG_ERR_GALLLERY_NOT_CONFIG;
					break;
				}
				
				//plugin hook for collections
				$hookquery = '';
				$hooktitle = '';
				$manager->notify('NPgCollectionDisplay', array('type' => $type, 'query' => &$hookquery , 'title' => &$hooktitle) );
				if($hookquery) {
					if ($id == 0) {
						$collection = new ALBUM();
						$collection->setquery($hookquery);
						$collection->set_title($hooktitle);
						$t = new NPG_TEMPLATE($NPG_CONF['template']);
						$collection->settemplate($t);
						$collection->display();
					}
					else {
						$pict = new PICTURE($id);
						$t = new NPG_TEMPLATE($NPG_CONF['template']);
						$pict->setalbumtitle($hooktitle);
						$pict->settemplate($t);
						$pict->setquery($hookquery);
						$pict->display();
					}
					$type = 'nothing';
				}
				
				//other pages
				switch($type) {
					case 'album': 
						$alb = new ALBUM($id);
						if($alb->getID()) {
							$t = new NPG_TEMPLATE($NPG_CONF['template']);
							$alb->settemplate($t);
							$alb->display(requestVar('sort'));
						}
						else echo __NPG_ERR_NOSUCHTHING.'<br/>';
						break;
					//case 'set': 
					//	$setid = $id;
					//	$alb = new ALBUM($setid);
						//this should work, but not sure what $alb->getID() does...
					//	if($alb->getID()) {
					//		$t = new NPG_TEMPLATE($NPG_CONF['template']);
					//		$alb->settemplate($t);
					//		$alb->displayset(requestVar('sort'));
					//	}
					//	else echo __NPG_ERR_NOSUCHTHING.'<br/>';
					//	break;
					case 'item': 
						$pict = new PICTURE($id);
						if($pict->getID()) {
							$t = new NPG_TEMPLATE($NPG_CONF['template']);
							$pict->settemplate($t);
							$pict->display($startstop,$sliderunning);
						}
						else echo __NPG_ERR_NOSUCHTHING.'<br/>';
						break;
					case 'list': 
						$l = new GALLERY_LIST();
						$t = new NPG_TEMPLATE($NPG_CONF['template']);
						$l->settemplate($t);
						$l->display(requestVar('sort')); 
						break;
					case 'addAlbumF': 
						addAlbumForm();
						break;
					case 'editAlbumF': 
						editAlbumForm($id);
						break;
					case 'editPictureF':
						editPictureForm($id);
						break;
					case 'deletePictureF':
						deletePictureForm($id);
						break;
					case 'addPictF': 
						addPictureForm($id);
						break;
					case 'nothing':
						break;
					default: 
						$l = new GALLERY_LIST();
						$t = new NPG_TEMPLATE($NPG_CONF['template']);
						$l->settemplate($t);
						$l->display(requestvar('sort')); 
						break;
				}
				
				break;
		}
	}
	
	function MakeLink($type, $extraparams = array()) {
		global $CONF;
		
		if($CONF['URLMode'] == 'pathinfo') { 
			$base = '/gallery/';
			$sep1 = '/';
			$sep2 = '/';
		}
		else {
			$base = 'action.php?action=plugin&name=gallery&type=';
			$sep1 = '&';
			$sep2 = '=';		
		}
		//if extraparams is assoc array
		if(is_array($extraparams) && array_keys($extraparams)!==range(0,sizeof($extraparams)-1)) {
			foreach($extraparams as $key => $value) 
				$extra = $extra . $sep1 . $key . $sep2 . $value;
			}
		return $base.$type.$extra;


	}
	
	function MakeLinkRaw($base, $extraparams = '') {
		global $CONF;
		
		if($CONF['URLMode'] == 'pathinfo') {
			$sep1 = '/';
			$sep2 = '/';
		}
		else {
			$sep1 = '&amp;';
			$sep2 = '=';
		}
		foreach($extraparams as $key => $value) $extra = $extra . $sep1 . $key . $sep2 .$value;
		return $base.$extra;
	}
}

class NPG_PREPARSER extends PARSER {
	
	function doAction($action) {
		 if (!$action) return;
		 $action_raw = '<%'.$action.'%>';
		 
		// split into action name + arguments
		if (strstr($action,'(')) {
			$paramStartPos = strpos($action, '(');
			$params = substr($action, $paramStartPos + 1, strlen($action) - $paramStartPos - 2);
			$action = substr($action, 0, $paramStartPos);
			$params = explode ($this->pdelim, $params);
			$params = array_map('trim',$params);
		} else {
			$params = array();
		}

		$actionlc = strtolower($action);

		if (in_array($actionlc, $this->actions) || $this->norestrictions ) {
			call_user_func_array(array(&$this->handler,'parse_' . $actionlc), $params);
		} else {
			echo $action_raw;
		}

	 }
	 
	 
}

class NPG_EXT_ITEM_ACTIONS extends BaseActions {
	var $parser;
	
	function NPG_EXT_ACTIONS() {
		$this->BaseActions();
	}
	
	function getdefinedActions() {
		return array( 'gallery' );
	}
	
	function setParser(&$parser) {$this->parser =& $parser; }
	
	function parse_gallery($param1, $param2, $param3) {
		if($param1 == 'link') {
			if($param2 == 'picture') {
				$param3 = intval($param3);
				echo generatelink('item',$param3);
			}
			else if($param2 == 'album') {
				$param3 = intval($param3);
				echo generatelink('album',$param3);
			}
			else echo '<b>NOT HERE</b>';
		}
		if($param1 == 'keywords') {
			$setid = $param2;
			$splitdata = explode(' and ',$setid);
			$sort = $param3;
			//$alb = new ALBUM($id);
			//if($alb->getID()) {
			//$t = new NPG_TEMPLATE($NPG_CONF['template']);
			//$alb->settemplate($t);
			//$alb->display(requestVar('sort'));
			$thisset = new ALBUM;
			$t = new NPG_TEMPLATE($NPG_CONF['template']);
			$thisset->settemplate($t);
			$thisset->displayset($splitdata,$sort);
		}
	}
}
?>
