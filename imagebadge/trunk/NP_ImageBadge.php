<?

class NP_ImageBadge extends NucleusPlugin {
	
    function getEventList() { return array('QuickMenu'); }
    function getName() { return 'ImageBadge'; }
    function getAuthor() { return 'Armon Toubman'; }
    function getURL() { return 'http://armon.globalog.net/'; }
    function getVersion() { return '0.4'; }
    function getDescription() { return 'This plugin generates an image badge like Flickr\'s flash badge, but with Javascript'; }
    
    function supportsFeature($name) {
        switch($name) {
        case 'SqlTablePrefix':
            return true;
        }
        return false;
    } 
	
	function hasAdminArea() {
		return 1;
	}
	
	function event_QuickMenu(&$data) {
		array_push(
			$data['options'],
			array(
				'title' => 'ImageBadge',
				'url' => $this->getAdminURL(),
				'tooltip' => 'Manage your badge'
			)
		);
	}
 
    function install() {
	    $query ="CREATE TABLE IF NOT EXISTS `" . sql_table('plug_imagebadge') . "` (" .
				"`id` int(11) unsigned NOT NULL auto_increment, " .
				"`image` varchar(255) NOT NULL default '', " .
				"`link` varchar(255) NOT NULL default '', " .
				"PRIMARY KEY (`id`)" .
				");";
		sql_query($query);
		$this->createOption('emptylinks', 'Default URL if you leave the link empty:', 'text', '#');
        $this->createOption('badge_height', 'Height of badge (in pixels)', 'text', '150');
        $this->createOption('badge_width', 'Width of badge (in pixels)', 'text', '150');
        $this->createOption('thumbsize', 'Size of thumbnails (in pixels)', 'text', '50');
        $this->createOption('margin', 'Space between thumbnails (in pixels)', 'text', '2');
        $this->createOption('delay', 'Delay between each new thumbnail (1000 = 1 second)', 'text', '3000');
        $this->createOption('cleardb', 'Clear database on uninstall?', 'yesno', 'no');
     }
     
    function unInstall() {
	    if($this->getOption('cleardb') == "yes") {
			sql_query(
				'DROP TABLE IF EXISTS ' . sql_table('plug_imagebadge')
			);
		}
	}
 
    function doSkinVar($skintype, $action) {
	    if($action == 'code') {
		    //echo needed references to badge.js and badge.css
        	global $CONF;
			$dir = $CONF['PluginURL'] . "imagebadge/";
			echo '<script type="text/javascript" src="' . $dir . 'badge.js"></script>';
			echo '<style type="text/css">@import url("' . $dir . 'badge.css");</style>';
		}
		if($action == 'badge') {
			//get values
			$badge_height = $this->getOption('badge_height');
			$badge_width = $this->getOption('badge_width');
			$thumbsize = $this->getOption('thumbsize');
			$margin = $this->getOption('margin');
			$delay = $this->getOption('delay');
			$pictures = $this->getOption('pictures');
			//echo badge div
			echo '<div id="badge" style="width:' . $badge_width . 'px;height:' . $badge_height . 'px;"></div>';
			echo "\n";
			//process photos
			
			//echo js
			?>
<script type="text/javascript">
if(typeof Badge == 'function') {
<?php
	//foreach photos as photo:
	$result = sql_query("SELECT image,link FROM ".sql_table("plug_imagebadge"));
	if($result) {
		while($row = mysql_fetch_assoc($result)) {
			// next if/else for new check since 0.31
				if($row['link'] == "") { $link = $this->getOption('emptylinks'); }
				else { $link = $row['link']; }
			echo "Badge.addPhoto('".$row['image']."','".$link."');\n";
		}
	}
?>
Badge.setSize(<?php echo $thumbsize; ?>);
Badge.setMargin(<?php echo $margin; ?>);
Badge.setDelay(<?php echo $delay; ?>);
Badge.initialize('badge');
}
</script>
			<?php
		}
    }

}

?>