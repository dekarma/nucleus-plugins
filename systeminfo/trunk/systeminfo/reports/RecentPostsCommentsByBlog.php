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
	
	$siRptResults['overheader'] = array(
										array(1,''),
										array(1,''),
										array(2,'Past Day'),
										array(2,'Past 7 Days'),
										array(2,'Past 30 Days'),
										array(2,'Past 365 Days'),
										array(2,'All')
									);
	$siRptResults['header'] = array('ID','Blog Name','Posts','Comments','Posts','Comments','Posts','Comments','Posts','Comments','Posts','Comments');
	$siRptResults['width'] = array(15,100,25,25,25,25,25,25,25,25,25,25,25,25);
	
	$query = "SELECT bnumber as bid, bname as name FROM ".sql_table('blog')." ORDER BY bnumber ASC";
	$res = sql_query($query);
	
	$mydata = array();
	
	while ($row = mysql_fetch_assoc($res)) {
		$data = array();
		$data[] = intval($row['bid']);
		$data[] = $row['name'];
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 86400 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('comment')." WHERE cblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(ctime) > UNIX_TIMESTAMP() - 86400 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 604800 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('comment')." WHERE cblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(ctime) > UNIX_TIMESTAMP() - 604800 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 2592000 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('comment')." WHERE cblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(ctime) > UNIX_TIMESTAMP() - 2592000 "));		
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 31536000 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('comment')." WHERE cblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(ctime) > UNIX_TIMESTAMP() - 31536000 "));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid']).""));
		$data[] = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('comment')." WHERE cblog=".intval($row['bid']).""));
		$mydata[intval($row['bid'])] = $data;
	}
	ksort($mydata);
	$siRptResults['data'] = $mydata;

?>