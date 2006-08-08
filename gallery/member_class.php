<?php
//gallery member class

class GALLERY_MEMBER extends MEMBER {
	
	function makeguest() {
		$this->id = 0;
		$this->realname = 'guest';
		$this->displayname = 'guest';
	}
	
	function canAddAlbum() {
		global $NPG_CONF;
		
		if ($this->isAdmin()) return true;
		
		//depends on setting of $NPG_CONF['add_album']
		if ($NPG_CONF['add_album'] == 'guest' ) return true;
		if ($NPG_CONF['add_album'] == 'member' && $this->isloggedin() ) return true;
		if ($NPG_CONF['add_album'] == 'select') {
			$result = mysql_query('select addalbum from '.sql_table('plug_gallery_member').' where memberid='.$this->getID() );
			if(!$result) return false;
			$row = mysql_fetch_assoc($result);
			if($row['addalbum']) return true;
		}
		
		//the default:
		return false;
	
	}
	function canAddPicture($albumid=0) {
		
		//super-admin
		if ($this->isAdmin()) return true;
		
		//if no album specified (ie albumid = 0), then look if user is member or owner of any albums
		if(!$albumid) {
			$aa = $this->getAllowedAlbums();
			if($aa) return true; else return false;
		}
		
		//album owner or guest/public album
		$result = mysql_query('select ownerid from '.sql_table('plug_gallery_album').' where albumid='.$albumid);
		if(!$result) return false;
		$row = mysql_fetch_assoc($result);
		if($row['ownerid'] == $this->getID() || $row['ownerid']==0) return true;
		
		//album team member
		$result = mysql_query('select tmemberid from '.sql_table('plug_gallery_album_team').' where talbumid='.$albumid);
		if(!$result) return false;
		while($row = mysql_fetch_assoc($result)) {
			if($this->getID() == $row['tmemberid']) return true;
		}
		
	}
	function canModifyAlbum($albumid) {
		
		//super-admin
		if ($this->isAdmin()) return true;
		
		//album owner except for public/guest albums -- only admin can modify those
		$result = mysql_query('select ownerid from '.sql_table('plug_gallery_album').' where albumid <> 0 and albumid='.$albumid);
		if(!$result) return false;
		$row = mysql_fetch_assoc($result);
		if($row['ownerid'] == $this->getID()) return true;
		
		//album admin (from team)
		$result = mysql_query('select tmemberid, tadmin from '.sql_table('plug_gallery_album_team').' where talbumid='.$albumid);
		if(!$result) return false;
		while($row = mysql_fetch_assoc($result)) {
			if($this->getID() == $row['tmemberid'] || $row['tadmin']) return true;
		}
	
	}
	function canModifyPicture($pictureid) {
		
		//super-admin
		if ($this->isAdmin()) return true;
		
		//picture owner
		$result = mysql_query('select ownerid from '.sql_table('plug_gallery_picture').' where pictureid='.$pictureid);
		if(!$result) return false;
		$row = mysql_fetch_assoc($result);
		if($row['ownerid'] == $this->getID()) return true;
		
		//album owner, but not guest
		$result = mysql_query('select a.ownerid from '.sql_table('plug_gallery_album').' as a, '.sql_table('plug_gallery_picture').' as p where a.albumid=p.albumid and p.pictureid='.$pictureid);
		if(!$result) return false;
		$row = mysql_fetch_assoc($result);
		if($row['ownerid'] == $this->getID() && $this->getID() <> 0) return true;
		
		//album admin (from team)
		
	}
	
	function canModifyComment($commentid) {
		
		//super-admin
		if ($this->isAdmin()) {
			$result = sql_query('select cmemberid from '. sql_table('plug_gallery_comment'). ' where commentid = '.$commentid);
			if (mysql_num_rows($result)) return true; else return false;
		}
		
		//comment ovnwer
		$result = sql_query('select cmemberid from '. sql_table('plug_gallery_comment'). ' where commentid = '.$commentid);
		$row = mysql_fetch_assoc($result);
		if($row['cmemberid'] == $this->getID()) return true;
		
	}
	
	function getAllowedAlbums() {
		$allowed_albums = array();

		$memberid = $this->getID();
		if(!$memberid) $memberid=0; //guest

		if($this->isadmin()) {
			$query = "select *, title as albumname from ".sql_table('plug_gallery_album')
					.' left join '.sql_table('member').' on ownerid=mnumber';
		} else {
			$query = "select *, title as albumname from ".sql_table('plug_gallery_album')
					.' left join '.sql_table('plug_gallery_album_team').' on albumid=talbumid'
					.' left join '.sql_table('member').' on ownerid=mnumber'
					." where tmemberid=$memberid or ownerid=$memberid or ownerid=0";
		}
					
		$result = mysql_query($query);
		if(!$result) echo mysql_error().'<br/>';
		if(@ !mysql_num_rows($result)) return false; 
		while ($row = mysql_fetch_object($result)) {
			if($row->mnumber==0) $row->mname='guest';
			array_push($allowed_albums, $row);
		}
		
		return $allowed_albums;
	}
	function getAllowedAlbumsids() {
		$allowed_albums = array();

		$memberid = $this->getID();
		if(!$memberid) $memberid=0; //guest

		if($this->isadmin()) {
			$query = "select *, title as albumname from ".sql_table('plug_gallery_album')
					.' left join '.sql_table('member').' on ownerid=mnumber';
		} else {
			$query = "select *, title as albumname from ".sql_table('plug_gallery_album')
					.' left join '.sql_table('plug_gallery_album_team').' on albumid=talbumid'
					.' left join '.sql_table('member').' on ownerid=mnumber'
					." where tmemberid=$memberid or ownerid=$memberid or ownerid=0";
		}
					
		$result = mysql_query($query);
		if(!$result) echo mysql_error().'<br/>';
		if(@ !mysql_num_rows($result)) return false; 
		while ($row = mysql_fetch_object($result)) {
			if($row->mnumber==0) $row->mname='guest';
			array_push($allowed_albums, $row->albumid);
			
		}
		
		return $allowed_albums;
	}
	
}
?>
