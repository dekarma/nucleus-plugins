<?php
class Trackback_Template {
    var $vars; 

    function Trackback_Template($file = null) {
        $this->file = $file;
    }

    function set($name, $value) {
        $this->vars[$name] = is_object($value) ? $value->fetch() : $value;
    }
	
	function template($file = null) {
		$this->file = $file;
	}

    function fetch($file = null) {
        if(!$file) $file = $this->file;
		
		if ($file != null)
		{
	        if (is_array($this->vars)) extract($this->vars);          
        
			ob_start();
	        include($file);
	        $contents = ob_get_contents();
	        ob_end_clean();
        
			return $contents;
		}
    }
}

?>