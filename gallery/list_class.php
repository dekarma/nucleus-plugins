<?php

class GALLERY_LIST {
	
	var $template;
	
	function GALLERY_LIST() {}
	
	function settemplate($template) {
		$this->template = & $template;
	}
	
	function display($sortorder) {	
		global $gmember;
		global $NPG_CONF;
		
		if(!$NPG_CONF['template']) $NPG_CONF['template'] = 1;
		
		$this->template = & new NPG_TEMPLATE($NPG_CONF['template']);
		//$this->template->readall();
		
		$template_header = $this->template->section['LIST_HEADER'];
		if($NPG_CONF['thumborlist']=='list')$template_body = $this->template->section['LIST_BODY'];
		if($NPG_CONF['thumborlist']=='thumb')$template_body = $this->template->section['LIST_THUM'];
		
		$template_footer = $this->template->section['LIST_FOOTER'];
		
		$actions = new LIST_ACTIONS();
		$parser = new PARSER($actions->getdefinedActions(),$actions);
		$actions->setparser($parser);
		
		//header
		$parser->parse($template_header);
		
		//body
		switch($sortorder){
			case 'title': $so = ' order by title ASC, albumid DESC'; break;
			case 'desc': $so = ' order by description ASC, albumid DESC'; break;
			case 'owner': $so = ' order by ownername ASC, albumid DESC'; break;
			case 'date': $so = ' order by modified DESC, albumid DESC'; break;
			case 'numb': $so = ' order by numberofimages DESC, albumid DESC'; break;
			
			case 'titlea': $so = ' order by title DESC, albumid DESC'; break;
			case 'desca': $so = ' order by description DESC, albumid DESC'; break;
			case 'ownera': $so = ' order by ownername DESC, albumid DESC'; break;
			case 'datea': $so = ' order by modified ASC, albumid DESC'; break;
			case 'numba': $so = ' order by numberofimages ASC, albumid DESC'; break;
			default : $so =''; break;
		}
		
		$query = "select a.*, b.mname as ownername from ".sql_table('plug_gallery_album').' as a left join '.sql_table('member').' as b on a.ownerid=b.mnumber '.$so;
		$result = mysql_query($query);
		$albums = $gmember->getallowedalbums();
		$albumids = $gmember->getAllowedAlbumsids();
		
		while ($row = mysql_fetch_object($result)) {
			if(!$row->ownername) $row->ownername = 'guest';
			$actions->setCurrentRow($row);
			//if its public, show it
			//echo $row->albumid;
			if($row->publicalbum){
				$parser->parse($template_body);
			}
			//if its not public, check if its in the array of allowed albums for this member
			elseif(@in_array($row->albumid,$albumids) ){
				$parser->parse($template_body);
			}
		}
		
		//footer
		$parser->parse($template_footer);
	} //end of display function

	
} //end of list class

class LIST_ACTIONS extends BaseActions {
	var $CurrentRow; //query object
	var $parser;
	
	
	function LIST_ACTIONS() {
		$this->BaseActions();
		
	}

	function getdefinedActions() {
		return array(
			'breadcrumb',
			'sortbytitle',
			'sortbydescription',
			'sortbyowner',
			'sortbymodified',
			'sortbynumber',
			'albumlink',
			'albumthumbnail',
			'centeredtopmargin',
			'title',
			'description',
			'ownerid',
			'ownername',
			'modified',
			'numberofimages',
			'addalbumlink',
			'addpictureslink',
			'if',
			'else',
			'endif' );
			
	}
	
	function setParser(&$parser) {$this->parser =& $parser; }
	function setCurrentRow(&$currentrow) { $this->CurrentRow =& $currentrow; }
	
	function parse_breadcrumb() {
		//$breadcrumb = getBreadcrumb(); echo $breadcrumb; 
		echo __NPG_BREADCRUMB_GALLERY;
	}
	function parse_sortbytitle() { 
		$so = requestvar('sort');
		if($so == 'title') $so = 'titlea'; else $so = 'title';
		echo generateLink('list', $so); 
	}
	function parse_sortbydescription() {
		$so = requestvar('sort');
		if($so == 'desc') $so = 'desca'; else $so = 'desc';
		echo generateLink('list', $so); 
	}
	function parse_sortbyowner() {
		$so = requestvar('sort');
		if($so == 'owner') $so = 'ownera'; else $so = 'owner';
		echo generateLink('list', $so); 
	}
	function parse_sortbymodified() {
		$so = requestvar('sort');
		if($so == 'date') $so = 'datea'; else $so = 'date';
		echo generateLink('list', $so); 
	}
	function parse_sortbynumber() {
		$so = requestvar('sort');
		if($so == 'numb') $so = 'numba'; else $so = 'numb';
		echo generateLink('list', $so); 
	}
	
	function parse_albumthumbnail() { 
		global $CONF;
		echo $CONF['IndexURL'].$this->CurrentRow->thumbnail; }
	function parse_centeredtopmargin($height,$adjustment) {
		global $NP_BASE_DIR;
		$image_size = getimagesize($NP_BASE_DIR.$this->CurrentRow->thumbnail);
		$topmargin = ((intval($height) - intval($image_size[1])) / 2) + intval($adjustment);
		echo 'margin-top: '.$topmargin.'px;';
	}
	
	function parse_albumlink() {echo generateLink('album', $this->CurrentRow->albumid);}
	function parse_title() {echo $this->CurrentRow->title; }
	function parse_description() {echo $this->CurrentRow->description; }
	function parse_ownerid() {echo $this->CurrentRow->ownerid; }
	function parse_ownername() {echo $this->CurrentRow->ownername; }
	function parse_modified($format = 'M j, h:i',$arg1 ='',$arg2='',$arg3='') {
		if($arg1) $arg1 = ','.$arg1;
		if($arg2) $arg2 = ','.$arg2;
		if($arg3) $arg3 = ','.$arg3;
		$format = $format . $arg1 . $arg2 . $arg3;
		$d = $this->CurrentRow->modified;
		$d = converttimestamp($d);
		$d = date($format,$d);
		echo $d;
	}
	function parse_numberofimages() {echo $this->CurrentRow->numberofimages; }
	
	function parse_addalbumlink() {$aa = generateLink('addAlbumF'); echo $aa;}
	function parse_addpictureslink() {$ap = generateLink('batchaddPictF'); echo $ap;}
	

	function parse_if($field, $name='', $value = '') {
		global $gmember;
		
		$condition = 0;
		switch ($field) {
			case 'canaddpicture':
				$condition = $gmember->canAddPicture();
				break;
			case 'canaddalbum':
				$condition = $gmember->canAddAlbum();
				break;
			default: 
				break;
		}
		
		$this->_addIfCondition($condition);
		
	}


}
?>
