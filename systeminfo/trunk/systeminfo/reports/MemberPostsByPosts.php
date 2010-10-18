<?php
	global $siRptResults,$manager,$CONF,$member;

	if ($manager->pluginInstalled('NP_SystemInfo')) {
		$sysinfo = $manager->getPlugin('NP_SystemInfo');
	}
	else
		doError("Plugin not installed.");

	$admin = intval($sysinfo->siIsAdmin()) + intval($sysinfo->siIsBlogAdmin()) + intval($sysinfo->siIsTeamMember());
	$minaccess = intval($sysinfo->getOption('accesslevel'));
	if (!$minaccess || $minaccess == 0) $minaccess = 8;

	if (!($admin >= $minaccess)) doError("You do not have sufficient privileges.");
	
	$siRptResults['header'] = array('Member Name','Is Super-Admin','Blog Teams','Total Posts');
	$siRptResults['width'] = array(100,45,45,45);
	
	$query = "SELECT mnumber as mid, mname as name, madmin as isadmin FROM ".sql_table('member')." WHERE madmin=1 OR mnumber IN (SELECT DISTINCT `tmember` FROM `".sql_table('team')."`) ORDER BY mname ASC";
	$res = sql_query($query);
	
	$mydata = array();
	
	while ($row = mysql_fetch_assoc($res)) {
		$numposts = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iauthor=".intval($row['mid'])));
		$data = array();
		$data[] = $row['name'];
		$data[] = (intval($row['isadmin']) > 0 ? 'Y' : 'N');
		if (intval($row['isadmin'])) $data[] = 'All';
		else {
			$blogs = '';
			$bres = sql_query("SELECT tblog FROM ".sql_table('team')." WHERE tmember=".intval($row['mid'])." ORDER BY tblog ASC");
			while ($b = mysql_fetch_assoc($bres)) {
				$blogs .= $b['tblog']." ";
			}
			$data[] = trim($blogs);
		}
		$data[] = $numposts;
		$mydata[$numposts] = $data;
	}
	krsort($mydata);
	$siRptResults['data'] = $mydata;

?>