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
	
	$siRptResults['header'] = array('ID','Blog Name','Past Day','Past 7 Days','Past 30 Days','Past 365 Days','All');
	$siRptResults['width'] = array(15,100,45,45,45,45,45,45);
	
	$query = "SELECT bnumber as bid, bname as name FROM ".sql_table('blog')." ORDER BY bnumber ASC";
	$res = sql_query($query);
	
	$mydata = array();
	
	while ($row = mysql_fetch_assoc($res)) {
		$data = array();
		$data[] = intval($row['bid']);
		$data[] = $row['name'];
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 86400 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 604800 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 2592000 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 31536000 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 315360000 "));
		$mydata[intval($row['bid'])] = $data;
	}
	ksort($mydata);
	$siRptResults['data'] = $mydata;

?>