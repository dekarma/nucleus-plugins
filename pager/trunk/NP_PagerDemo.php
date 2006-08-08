<?php
class NP_PagerDemo extends NucleusPlugin {
	function getName() { return 'PagerDemo'; }
	function getAuthor()  { return 'Rodrigo Moraes'; }
	function getURL() {	return 'http://tipos.com.br'; }
	function getVersion() {	return '0.1'; }
	function getDescription() { return 'A demo example to show how to use NP_Pager. Requires NP_Pager.'; }
	function getEventList() { return array(); }
	function getPluginDep() { return array('NP_Pager'); }
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function doSkinVar($skinType) {
		global $manager;

		$sql = "SELECT cuser, cbody, citem, cmember, cnumber, ctime, cblog FROM " . sql_table('comment') . " ORDER by cnumber DESC";

		$pager =& $manager->getPlugin('NP_Pager');

		// see all available options on NP_Pager
		$options = array(
			'separator' => '|',
			'spacesBeforeSeparator' => 1,
			'spacesAfterSeparator' => 1
		);

		$pager->setOptions($options);
		$pages = $pager->getPagedResults($sql, 'mysql_fetch_array');

		ob_start();
		$out = '';

		// $pages['data'] contains the current page results (like they came from MySQL)
		foreach($pages['data'] as $row)	{
			$row['cbody'] = wordwrap(strip_tags($row['cbody']), 40, " ", 1);

			// commenter name, if member
			if ($row['cmember']) {
				// only on Nucleus 3.3CVS...
				//$mem =& $manager->getMember($row['cmember']);
				$mem = new Member();
				$mem->readFromID(intval($row['cmember']));
				$row['cuser'] = $mem->getDisplayName();
			}

			$itemlink = createItemLink($row['citem']);

			$blogname = htmlentities(getBlogNameFromID($row['cblog']));
			$blogname = str_replace("'", '&#8217;', $blogname);

			$day = date("d", strtotime($row['ctime']));
			if ($day_d != $day) {
				$out .= "\n<p class=\"date\">".strftime("%A, %d.%m", strtotime($row['ctime']))."</p>\n";
			}

			$out .= "<a href=\"".$itemlink."\" class=\"comments\" title=\"comment on blog &quot;".$blogname."&quot;\" onmouseover=\"window.status='comment on blog &quot;".$blogname."&quot;'; return true\" onmouseout=\"window.status=''\">\n";
			$out .= $row['cbody']."</a>\n";
			$out .= "<h5>";
			$out .= "<span class=\"arrow\">posted by ".$row['cuser'];
			$out .= " at ".date("H:i", strtotime($row['ctime']));
			$out .= "</span></h5>\n\n";

			$day_d = $day;
		}

		$out .= '<div class="pagination">';
		// $pages['links'] are the page links
		$out .= $pages['links'];
		$out .= '</div>';
		echo $out;
		ob_end_flush();
	}
}
?>
