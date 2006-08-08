<?php


class PICTURE {
	var $id;
	var $title;
	var $description;
	var $ownerid;
	var $modified;
	var $albumid;
	var $filename;
	var $int_filename;
	var $thumb_filename;
	var $views;
	var $keywords;
	
	var $next;
	var $previous;
	
	var $template;
	var $query;
	var $albumtitle;
	var $previousthumbfilename;
	var $nextthumbfilename;

	function PICTURE($id) {
		global $member;
		
		if($id) {
			$data = $this->get_data($id);
	
			$this->id = $data->pictureid;
			$this->title = $data->title;
			$this->description = $data->description;
			$this->ownerid = $data->ownerid;
			$this->ownername = $data->mname;
			$this->modified = $data->modified;
			$this->albumid = $data->albumid;
			$this->filename = $data->filename;
			$this->int_filename = $data->int_filename;
			$this->thumb_filename = $data->thumb_filename;
			$this->views = $data->views;
			$this->albumtitle = $data->albumtitle;
			$this->keywords = $data->keywords;
		}
		else {
			$this->id = 0;
			$this->ownerid = $member->getID();
		}
	}
	
	function settemplate($template) {
		$this->template = & $template;
	}
	
	function write() {
		//not present so add new to database
		if(!$this->id) { 
			$this->title = stripslashes($this->title);
			$this->title = addslashes($this->title);
			$this->description = stripslashes($this->description);
			$this->description = addslashes($this->description);
			sql_query("insert into ".sql_table('plug_gallery_picture')
				." values (NULL, '{$this->title}' , '{$this->description}' , {$this->ownerid} , "
				."NULL , {$this->albumid} , '{$this->filename}' , '{$this->int_filename}' , '{$this->thumb_filename}', '{$this->keywords}' )" );
				
			//picture id of most recently added -- could be referenced by calling fuction (or PICTURE->getID()
			$this->id = mysql_insert_id(); 
				
			//increment album number of images -- consider rewrite as an album method that actually counts number of images?
			sql_query("update ".sql_table('plug_gallery_album')." set numberofimages = numberofimages + 1 where albumid = {$this->albumid}");
		} 
		//present, so just update values
		else {  
			$this->title = stripslashes($this->title);
			$this->title = addslashes($this->title);
			$this->description = stripslashes($this->description);
			$this->description = addslashes($this->description);
			sql_query("update ".sql_table('plug_gallery_picture')
				." set title='{$this->title}', "
				."description='{$this->description}', " 
				."keywords='{$this->keywords}',"
				."albumid={$this->albumid} "
				."where pictureid={$this->id}" );
		}
		
	}
	
	function get_data($id) {
		$result = sql_query("select a.*, b.mname from ".sql_table('plug_gallery_picture').' as a left join '.sql_table('member')." as b on a.ownerid=b.mnumber where a.pictureid=$id" );
		if(mysql_num_rows($result)) {
			if(mysql_num_rows($result)){
				$data = mysql_fetch_object($result);
				if(!$data->mname) $data->mname = 'guest';
				
				//get number of views
				$res = sql_query('select views from '.sql_table('plug_gallery_views').' where vpictureid = '.$data->pictureid);
				if(mysql_num_rows($res)) {
					$row = mysql_fetch_object($res);
					$data->views = $row->views;
				}
				else $data->views = 0;
				
				//get albumtitle for breadcrumb
				$res = sql_query('select title from '.sql_table('plug_gallery_album').' where albumid='.$data->albumid);
				if(mysql_num_rows($res)) {
					$row = mysql_fetch_object($res);
					$data->albumtitle = $row->title;
					}
				else $data->albumtitle = 'No Album Title';
			}
			else $data->pictureid = 0;
		}
		//else die('mysql error in picture::getdata -- '.mysql_error());
			
		return $data;
	}
	
	
	function add_new($albumid, $filename, $title, $description, $intfilename, $thumbfilename,$keywords) {

		global $NPG_CONF, $gmember;

		//need to validate data -- if okay, then add, if not delete files and display error
		if(!$albumid) die('Error: albumid is blank');
		
		//put picture data into database
		$this->title = $title;
		$this->filename = $filename;
		$this->int_filename = $intfilename;
		$this->thumb_filename = $thumbfilename;
		$this->keywords = $keywords;
		$this->description = $description;
		$this->ownerid = $gmember->getID();
		$this->albumid = $albumid;
		$this->id = 0; //this will be changed by the write method to the id of the picture
		
		$this->write(); 
		
		//return id of picture added -- set during $this->write() method to the id of the picture just added.
		return $this->id;

	}

	function setTitle($title) {$this->title = $title;}
	function setDescription($description) {$this->description = $description;}
	function setAlbumID($id) { if($id) $this->albumid = $id;}
	function setalbumtitle($title) {$this->albumtitle = $title;}
	function setkeywords($keywords){$this->keywords = $keywords;}

	function setquery($query ='') {
		global $manager;
		$defaultorder = $NPG_CONF['defaultorder'];
		switch ($defaultorder){
			case 'aesc' : $order = 'ASC'; break;
			case 'desc' : $order = 'DESC'; break;
		}
		if(!$query) $this->query = 'select pictureid, thumb_filename from '.sql_table('plug_gallery_picture').' where albumid='.$this->albumid.' order by pictureid '.$order;
		else $this->query = $query;
		
		sql_query('create temporary table temptableview (tempid int unsigned not null auto_increment primary key) '.$this->query);
		
		$result = sql_query('select tempid from temptableview where pictureid='.$this->id);
		$tid = mysql_fetch_object($result);
		
		
		
		
		//next thumb
		$result = sql_query('select pictureid, thumb_filename from temptableview where tempid > '.$tid->tempid.' order by tempid ASC limit 0,1');
		if(!mysql_num_rows($result)) 
			$this->next = 0;
		else {
			$row = mysql_fetch_object($result);
			$this->nextthumbfilename = $row->thumb_filename;
			$this->nextid = $row->pictureid;
			}
		//previous thumb
		$result = sql_query('select pictureid, thumb_filename from temptableview where tempid < '.$tid->tempid.' order by tempid DESC limit 0,1');
		if(!mysql_num_rows($result)) 
			$this->previous = 0;
		else {
			$row = mysql_fetch_object($result);
			$this->previousthumbfilename = $row->thumb_filename;
			$this->previousid = $row->pictureid;
			}
				
	}

	function getTitle() {return $this->title;}
	function getDescription() {return $this->description;}
	function getAlbumId() {return $this->albumid;}
	function getOwnerName() { return $this->ownername;}

	function getOwnerId() {return $this->ownerid;}
	function getLastModified() {return $this->modified;}
	function getID() {return $this->id;}
	function getFilename() {return $this->filename; }
	function getIntFilename() {return $this->int_filename; }
	function getThumbFilename() {return $this->thumb_filename; }
	function getViews() {return $this->views; }
	function getAlbumTitle() {return $this->albumtitle;}
	function getpreviousid() {	return $this->previousid;	}
	function getnextid() {return $this->nextid;	}
	function getpreviousthumbfilename() {return $this->previousthumbfilename;}
	function getnextthumbfilename() {return $this->nextthumbfilename;}
	function getsets() {return $this->keywords;}
	
	function delete($id) {
		global $NP_BASE_DIR;
		
		if(!$id) {
			$returnval['status'] = 'error';
			$returnval['message'] = 'ID is null in PICTURE::delete';
			return $returnval;
		}
		$query = 'select * from '.sql_table('plug_gallery_picture').' where pictureid='.$id;
		$result = mysql_query($query);
		if(!$result) {
			$returnval['status'] = 'error';
			$returnval['message'] = mysql_error().':'.$query; 
			return $returnval;
		} else {
			if(!mysql_num_rows($result)) {
				$returnval['status'] = 'error';
				$returnval['message'] = 'Picture not deleted because it was not found in database';
				return $returnval;
			}
			else {
				$row = mysql_fetch_object($result);
				if(@ !unlink($NP_BASE_DIR.$row->filename)) echo 'file: '.$row->filename.' could not be deleted<br/>';
				if(@ !unlink($NP_BASE_DIR.$row->int_filename)) echo 'file: '.$row->int_filename.' could not be deleted<br/>';
				if(@ !unlink($NP_BASE_DIR.$row->thumb_filename)) echo 'file: '.$row->thumb_filename.' could not be deleted<br/>';
				$query = 'delete from '.sql_table('plug_gallery_picture').' where pictureid='.$row->pictureid;
				$result2 = mysql_query($query);
				if(!$result2) {
					$returnval['status'] = 'error';
					$returnval['message'] = mysql_error();
					return $returnval;
				}
				ALBUM::decreaseNumberByOne($row->albumid);
				$returnval['status'] = 'success';
				$returnval['albumid'] = $row->albumid;
				return $returnval;
			}
		}
	}
	
	function deletepromoposts($id) {
		global $manager;
		
		$manager->loadClass('ITEM');
		
		$query = 'select * from '.sql_table('plug_gallery_promo').' where ppictureid='.$id;
		$result = mysql_query($query);
		if(!$result) {
			$returnval['status'] = 'error';
			$returnval['message'] = mysql_error().':$query'; 
			return $returnval;
		}
		else {
			if(!mysql_num_rows($result)) {
				$returnval['status'] = 'error';
				$returnval['message'] = 'No promo posts associated with this picture';
				return $returnval;
			}
			else {
				while ($row = mysql_fetch_object($result) ){
					ITEM::delete($row->pblogitemid);
				}
				sql_query('delete from '.sql_table('plug_gallery_promo').' where ppictureid='.$id);
				$returnval['status'] = 'success';
				return $returnval;
			}
		}
	}
	function tagaccept($left,$top,$width,$height,$text){
				sql_query("INSERT INTO ".sql_table('plug_gallery_picturetag')." ( `pictureid` , `top` , `left` , `height` , `width` , `text` )
				VALUES ( '" . $this->id ." ', '" .$top."', '" .$left." ' , '" .$height."' , '" .$width."' , '" .$text."' ); ");
				echo "<SCRIPT LANGUAGE=\"JavaScript\">
				window.location=\"" . $NP_BASE_DIR  . "action.php?action=plugin&name=gallery&type=item&id=". $this->id . "\"" .
				"</script>";
			}
	
	function tagdelete(){
				sql_query("DELETE FROM ".sql_table('plug_gallery_picturetag'). " WHERE `pictureid` = '" . $this->id  . "' LIMIT 1; ");
				echo "<SCRIPT LANGUAGE=\"JavaScript\">
				window.location=\"" . $NP_BASE_DIR  . "action.php?action=plugin&name=gallery&type=item&id=". $this->id . " \"" .
				"</script>";
			}
	

	
	function display($startstop,$sliderunning) {
		global $NPG_CONF,$manager;
		
		if(!$this->template) {
			if(!$NPG_CONF['template']) $NPG_CONF['template'] = 1;
			$this->template = & new NPG_TEMPLATE($NPG_CONF['template']);
		}
		
		if(!$this->query ) $this->setquery();
		
		$template_header = $this->template->section['ITEM_HEADER'];
		$template_SLIDESHOWC = $this->template->section['ITEM_SLIDESHOWC'];
		$template_SLIDESHOWT = $this->template->section['ITEM_SLIDESHOWT'];
		$template_body = $this->template->section['ITEM_BODY'];
		$template_footer = $this->template->section['ITEM_FOOTER'];
		$template_TOOLTIPSHEADER = $this->template->section['ITEM_TOOLTIPSHEADER'];
		$template_TOOLTIPSFOOTER = $this->template->section['ITEM_TOOLTIPSFOOTER'];
		$template_NEXTPREVTHUMBS = $this->template->section['ITEM_NEXTPREVTHUMBS'];	
		
		if(!$this->getID() ) echo 'Nothing to display';
		//echo $sliderunning;
		
		//if the slideshow start/stop button was pressed, check if the slideshow if running
		//if its running, stop it, if its not running, start it
		if (!$sliderunning){$sliderunning='false';}
		if ($startstop=='true'){
			//echo 'startstop='.$startstop;
			if ($sliderunning=='false') $sliderunning= 'true'; //echo 'sliderunning='.$sliderunning;
			else $sliderunning= 'false';
			}
		//if the slideshow was running, let it continue
		if($sliderunning=='true'){
			//echo 'sliderunning='.$sliderunning;
			$actions = new PICTURE_ACTIONS($this);
			$parser = new PARSER($actions->getDefinedActions(), $actions);
			$actions->setparser($parser);
			$manager->notify('NPgPrePicture',array('picture',&$this));
			$this->_views();
			$template_SLIDESHOWC = $this->template->section['ITEM_SLIDESHOWC'];
			$template_SLIDESHOWT = $this->template->section['ITEM_SLIDESHOWT'];
			$parser->parse($template_SLIDESHOWC);
			$parser->parse($template_SLIDESHOWT);
		}
		// if the slideshow wasnt running, do the normal thing.
		if($sliderunning=='false'){ 
			//echo 'sliderunning1'.$sliderunning;
			$actions = new PICTURE_ACTIONS($this);
			$parser = new PARSER($actions->getDefinedActions(), $actions);
			$actions->setparser($parser);
			$manager->notify('NPgPrePicture',array('picture',&$this));
			$this->_views();
			$parser->parse($template_TOOLTIPSHEADER);
			$parser->parse($template_SLIDESHOWC);
			$parser->parse($template_header);
			$parser->parse($template_body);
			$parser->parse($template_TOOLTIPSFOOTER);
			$parser->parse($template_NEXTPREVTHUMBS);
			$parser->parse($template_footer);
			}
	}
	
	function _views() {
		global $NPG_CONF;
		
		$remoteip = ServerVar('REMOTE_ADDR');
		$pictureid = $this->getID();
		$curtime = time();
		if(!$NPG_CONF['viewtime']) $NPG_CONF['viewtime'] = 30 ;
		$cuttime = $NPG_CONF['viewtime'];
		//first test for duplicates
		$query = 'select * from '.sql_table('plug_gallery_views')." where vpictureid = $pictureid";
		//$result = mysql_query($query);
		//print_r($result);
		//$numrows= mysql_num_rows($result);
		//echo $numrows;
		if(mysql_num_rows($result)>1){
			//if theres more than one
			$query= 'DELETE FROM '.sql_table('plug_gallery_views').' WHERE vpictureid = $pictureid ORDER BY views LIMIT 1' ;
			mysql_query($query);
			}
		
		$query = 'select time from '.sql_table('plug_gallery_views_log')." where ip = '$remoteip' and vlpictureid = $pictureid";
      $result = sql_query($query);
      if(mysql_num_rows($result)) {
         $row = mysql_fetch_object($result);
         $query2 = 'update '.sql_table('plug_gallery_views_log')." set time = NOW() where ip = '$remoteip' and vlpictureid = $pictureid";
         $result2 = sql_query($query2);
         if( ($curtime - (intval($NPG_CONF['viewtime']) * 60) ) > converttimestamp($row->time) ) {
            $query3 = 'select * from '.sql_table('plug_gallery_views')." where vpictureid = $pictureid";
            $result3 = mysql_query($query3);
            if(mysql_num_rows($result3))
               sql_query('update '.sql_table('plug_gallery_views')." set views = views + 1 where vpictureid = $pictureid");
            else sql_query('insert into '.sql_table('plug_gallery_views')." (vpictureid, views) values ($pictureid, 1)");
         }
      } else {
         $query3 = 'select * from '.sql_table('plug_gallery_views')." where vpictureid = $pictureid";
         $result3 = mysql_query($query3);
         if(mysql_num_rows($result3))
            sql_query('update '.sql_table('plug_gallery_views')." set views = views + 1 where vpictureid = $pictureid");
         else sql_query('insert into '.sql_table('plug_gallery_views')." (vpictureid, views) values ($pictureid, 1)");
         sql_query('insert into '.sql_table('plug_gallery_views_log')." (vlpictureid, ip, time) values ($pictureid, '$remoteip', NULL)");
      } 
		
	}
}

class PICTURE_ACTIONS extends BaseActions {
	var $parser;
	var $CurrentPicture;
	var $knownactions;
	
		
	function PICTURE_ACTIONS(& $currentpic) {
		$this->BaseActions();
		$this->CurrentPicture = & $currentpic;
		$this->knownactions = array( 'addcomment','editPicture','deletePicture','item','album' );
	}
	
	function getdefinedActions() {
		return array(
			'breadcrumb',
			'albumlink',
			'albumtitle',
			'description',
			'fullsizelink',
			'intermediatepicture',
			'title',
			'height',
			'width',
			'owner',
			'date',
			'editpicturelink',
			'deletepicturelink',
			'nextlink',
			'previouslink',
			'nextthumbfilename',
			'previousthumbfilename',
			'comments',
			'commentform',
			'if',
			'else',
			'endif',
			'tooltip',
			'nextid',
			'previousid',
			'pictureid',
			'sitevar',
			'intvalsecs',
			'keywords' );
	}
	
	function setParser(&$parser) {$this->parser =& $parser; }
	function settemplate(&$template) {$this->template = & $template; }
	
	function parse_breadcrumb($sep = '>') {
		//echo getBreadcrumb('item', $this->CurrentPicture->getID()); 
		echo '<a href="';
		echo generateLink('list');
		echo '">'.__NPG_BREADCRUMB_GALLERY.'</a> '.$sep.' ';
		echo '<a href="';
		$this->parse_albumlink();
		echo '">';
		$this->parse_albumtitle();
		echo '</a> '.$sep.' ';
		$this->parse_title();
		}
		
	function parse_title() {
		echo $this->CurrentPicture->getTitle();	
	}
	
	function parse_albumlink() {
		$type = requestvar('type');
		if(in_array($type,$this->knownactions)) {
			$extra['id'] = $this->CurrentPicture->getalbumid();
			$type = 'album';
		}
		else {
			$allowed = array('limit');
			foreach($_GET as $key => $value) if(in_array($key,$allowed)) $extra[$key] = $value;
		}
		echo NP_gallery::MakeLink($type,$extra);
	}
	
	function parse_albumtitle() {
		echo $this->CurrentPicture->getAlbumTitle(); 
	}
	
	function parse_description() {echo $this->CurrentPicture->getDescription(); }
	function parse_fullsizelink() {
		global $CONF;
		echo $CONF['IndexURL'].$this->CurrentPicture->getFilename(); }
	function parse_intermediatepicture() {
		global $CONF;
		echo $CONF['IndexURL'].$this->CurrentPicture->getIntFilename(); }
	function parse_height($offset = 15) {
		global $NP_BASE_DIR;
		$image_size = getimagesize($NP_BASE_DIR.$this->CurrentPicture->getFilename());
		echo ($image_size[1] + $offset);
	}
	function parse_width($offset = 15) {
		global $NP_BASE_DIR;
		$image_size = getimagesize($NP_BASE_DIR.$this->CurrentPicture->getFilename());
		echo ($image_size[0] + $offset);
	}
	function parse_owner() { echo $this->CurrentPicture->getOwnerName(); }
	function parse_date($format = 'l jS of F Y h:i:s A') { 
		$d = $this->CurrentPicture->getLastModified();
		$d = converttimestamp($d);
		$d = date($format,$d);
		echo $d;
	}
	function parse_editpicturelink() { echo generateLink('editPictureF', $this->CurrentPicture->getID());}
	function parse_deletepicturelink() {echo generateLink('deletePictureF', $this->CurrentPicture->getID());}
	function parse_nextthumbfilename() {global $CONF;echo $CONF['IndexURL'].$this->CurrentPicture->getnextthumbfilename();}
	function parse_previousthumbfilename() {global $CONF;echo $CONF['IndexURL'].$this->CurrentPicture->getpreviousthumbfilename();}
	function parse_nextlink() { 
		$next = $this->CurrentPicture->getnextid();
		
		$type = requestvar('type');
		if(in_array($type, $this->knownactions)) $type = 'item';
		if(!$type) $type = 'item';
		if($next) {
			$extra['id'] = $next;
			$allowed = array('limit');
			foreach($_GET as $key => $value) if(in_array($key,$allowed)) $extra[$key] = $value;
			echo NP_gallery::MakeLink($type,$extra);
		}
		else echo '#';
	}
	
	function parse_previouslink() { 
		$prev = $this->CurrentPicture->getpreviousid();
		
		$type = requestvar('type');
		if(!$type) $type = 'item';
		if($prev) {
			$extra['id'] = $prev;
			$allowed = array('limit');
			foreach($_GET as $key => $value) if(in_array($key,$allowed)) $extra[$key] = $value;
			echo NP_gallery::MakeLink($type,$extra);
			//echo generateLink($type, $next);
		}
		else echo '#';
	}
	
	function parse_tooltip() {
			//get picture tag infor
			$gid = requestVar('id');
			$res = sql_query('select * from '.sql_table('plug_gallery_picturetag').' where pictureid= '. $gid .' ');
			$numrows = @mysql_num_rows($res);
			echo "<div id=\"tooltip2\">";
			for ($i=0 ; $i<$numrows;$i++) {
					$row = mysql_fetch_array($res);
					$data->top = $row[top];
					$data->left = $row[left];
					$data->height = $row[height];
					$data->width = $row[width];
					$data->text = $row[text];
					echo "<div style=\"display:block;position:absolute;border-width:0px;float:left;z-index:5;width:0px;height:0px\">
					<div class=\"tooltip2div\" style=\"display:block;position:relative;top:" .
					$data->top . "px;left:" .
					$data->left ."px;width:" .
					$data->width ."px;height:" .
					$data->height. "px\">" . 
					"<span style=\"position:relative;top:" .
					$data->height . "px\"> " .
					$data->text . "</span></div></div>";
			}
			echo "</div>";
		}
	function parse_pictureid() {
		$pictureid = $this->CurrentPicture->getID();
		echo $pictureid;
		}
		
	function parse_intvalsecs() {
		$intval = requestvar(intvalsecs);
		echo $intval;
		}
		
	function parse_nextid() {
		$nextid = $this->CurrentPicture->getnextid();
		echo $nextid ;
		}
		
	function parse_previousid() {
		$previousid = $this->CurrentPicture->getpreviousid();
		echo $previousid;
		}
	function parse_keywords(){
		$keywords = $this->CurrentPicture->getsets();
		echo $keywords;
		}
	
	function doForm($filename) {
		global $DIR_NUCLEUS;
		
		array_push($this->parser->actions,'formdata','text','callback','errordiv','itemid');
		$oldIncludeMode = PARSER::getProperty('IncludeMode');
		$oldIncludePrefix = PARSER::getProperty('IncludePrefix');
		PARSER::setProperty('IncludeMode','normal');
		PARSER::setProperty('IncludePrefix','');
		$this->parse_parsedinclude($DIR_NUCLEUS . 'forms/' . $filename . '.template');
		PARSER::setProperty('IncludeMode',$oldIncludeMode);
		PARSER::setProperty('IncludePrefix',$oldIncludePrefix);
		array_pop($this->parser->actions);		// itemid
		array_pop($this->parser->actions);		// errordiv
		array_pop($this->parser->actions);		// callback
		array_pop($this->parser->actions);		// text
		array_pop($this->parser->actions);		// formdata
	}
	
	
	function parse_comments() {
		global $NPG_CONF;
		
		$comments =& new NPG_COMMENTS($this->CurrentPicture->getID());
		//$comments->setItemActions($actions);
		$comments->showComments($this->CurrentPicture->template, -1, 1);	// shows ALL comments
	}
	
	
	function parse_commentform($destinationurl = '') {
		global $member, $CONF, $DIR_LIBS, $errormessage;
		
		
		$actionurl = $CONF['ActionURL'];
		
		//$destinationurl = '?action=plugin&name=gallery&type=addcomment';
		
		// values to prefill
		$user = cookieVar($CONF['CookiePrefix'] .'comment_user');
		if (!$user) $user = postVar('user');
		$userid = cookieVar($CONF['CookiePrefix'] .'comment_userid');
		if (!$userid) $userid = postVar('userid');
		$body = postVar('body');
		
		$this->formdata = array(
			'destinationurl' => htmlspecialchars($destinationurl),
			'actionurl' => htmlspecialchars($actionurl),
			'itemid' => $this->CurrentPicture->getID(),
			'user' => htmlspecialchars($user),
			'userid' => htmlspecialchars($userid),
			'body' => '',
			'membername' => $member->getDisplayName(),
			'rememberchecked' => cookieVar($CONF['CookiePrefix'] .'comment_user')?'checked="checked"':''
		);
		
		if (!$member->isLoggedIn()) {
			$this->doForm('../plugins/gallery/include/commentform-notloggedin');
		} else {
			$this->doForm('../plugins/gallery/include/commentform-loggedin');		
		}
	}
	
	function parse_callback($eventName, $type) {
		global $manager;
		$manager->notify($eventName, array('type' => $type));
	}
	
	function parse_errordiv() {}
	function parse_text($which) {

		if (defined($which)) {     
			eval("echo $which;");
		} else {
			echo $which;
		}
		
	}
	
	function parse_itemid() {echo $this->CurrentPicture->getID();}
	
	function parse_formdata($what) {
		echo $this->formdata[$what];
	}
	
	function parse_if($field, $name = '', $value = '') {
		global $gmember,$NPG_CONF;
		
		$condition = 0;
		switch($field) {
			case 'caneditpicture':
				$condition = $gmember->canModifyPicture($this->CurrentPicture->getID() );
				break;
			case 'commentsallowed':
				$condition = ALBUM::commentsallowed($this->CurrentPicture->getID());
				break;
			case 'next':
				$condition = $this->CurrentPicture->getnextid();
				break;
			case 'prev':
				$condition = $this->CurrentPicture->getpreviousid();
				break;
			case 'tooltips':
				$tooltips = $NPG_CONF['tooltips'];
				if ($tooltips == 'yes'){$condition = 1;}
				break;
			case 'nextprevthumb' :
				$nextprevthumb = $NPG_CONF['nextprevthumb'];
				if ($nextprevthumb == 'yes'){$condition = 1;}
			case 'slideshowson' :
				$slideshowson = $NPG_CONF['slideshowson'];
				if ($slideshowson == 'yes'){$condition = 1;}
			default:
				break;
		}
		
		$this->_addIfCondition($condition);
	}

}
?>
