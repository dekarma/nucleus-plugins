<?php

class NPG_TEMPLATE {

	var $id;
	var $section;
	var $name;
	var $description;
	
	function NPG_TEMPLATE($templateid) {
		$this->id = $templateid;
		$this->section = array();
		if($this->existsID($this->id)) {
			$this->readall();
			$query = 'select * from '.sql_table('plug_gallery_template_desc').' where tdid='.$this->id;
			$res = sql_query($query);
			$row = mysql_fetch_object($res);
			$this->name = stripslashes($row->tdname);
			$this->description = stripslashes($row->tddesc);
		}
	}
	
	function getID() { return $this->id; }
	function getname() {return $this->name; }
	function getdesc() {return $this->description; }
	
	function createfromname($name) {return new NPG_TEMPLATE(NPG_TEMPLATE::getIdFromName($name));}
	
	function getIDfromName($name) {
		$query =  'SELECT tdid'
		. ' FROM '.sql_table('plug_gallery_template_desc')
		. ' WHERE tdname="'.addslashes($name).'"';
		$res = sql_query($query);
		$obj = mysql_fetch_object($res);
		return $obj->tdid;
	}
	
	function updategeneralinfo($name,$desc) {
		$query =  'UPDATE '.sql_table('plug_gallery_template_desc').' SET'
		. " tdname='" . addslashes($name) . "',"
		. " tddesc='" . addslashes($desc) . "'"
		. " WHERE tdid=" . $this->getID();
		sql_query($query); 
	}
	
	function update($type,$content) {
		$id = $this->getID();
		sql_query('DELETE FROM '.sql_table('plug_gallery_template')." WHERE name='". addslashes($type) ."' and tdesc=" . intval($id));
		
		if ($content) {
			sql_query('INSERT INTO '.sql_table('plug_gallery_template')." SET content='" . addslashes($content) . "', name='" . addslashes($type) . "', tdesc=" . intval($id));
		}
	}
	
	function deleteallparts() { sql_query('DELETE FROM '.sql_table('plug_gallery_template').' WHERE tdesc='.$this->getID()); }
	
	function createnew($name,$desc) {
		sql_query('INSERT INTO '.sql_table('plug_gallery_template_desc')." (tdname, tddesc) VALUES ('" . addslashes($name) . "','" . addslashes($desc) . "')");
		$newId = mysql_insert_id();
		return $newId;
	}
	
	function exists($name) {
		$r = sql_query('select * FROM '.sql_table('plug_gallery_template_desc').' WHERE tdname="'.addslashes($name).'"');
		return (mysql_num_rows($r) != 0);
	}
	
	function existsID($id) {
		$r = sql_query('select * FROM '.sql_table('plug_gallery_template_desc').' WHERE tdid='.intval($id));
		return (mysql_num_rows($r) != 0);
	}
	
	function gettemplate($type) {
		$result = mysql_query("select * from ".sql_table('plug_gallery_template')." where name='$type'" );
		$data = mysql_fetch_assoc($result);
		$template = stripslashes($data['content']);
		return $template;
	}
	
	function settemplate($type, $content) {
		$this->update($type,$content);
	}
	
	function readall() {
		$query = 'select * from '.sql_table('plug_gallery_template').' where tdesc='.$this->id;
		$res = sql_query($query);
		while ($row = mysql_fetch_object($res)){
			$this->section[$row->name] = stripslashes($row->content);
		}
	}
	
}

?>
