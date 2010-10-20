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
	
	/* 	The header values are set using the following array elements of the global $siRptResults array
			header - Required. Array of strings representing the header text of each column in the report. Length should be equal to number of columns in report.
			width - Optional. array of integers representing the width of each column in px. If not set, html render engine of the browser will set widths automagically. Length should equal number of columns in report.
			overheader - Optional. Array of arrays. Each array element is an array with element 0 an integer representing number of columns to span and element 1 is a string to be displayed in cell. Not sum of element 0's must be equal to number of columns in report.
			underheader - Optional. Array of arrays. Each array element is an array with element 0 an integer representing number of columns to span and element 1 is a string to be displayed in cell. Not sum of element 0's must be equal to number of columns in report.
			
		Examples of usage of each element above is given in the RecentPostsCommentsByBlog.php report definition file
	*/
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
	/*$siRptResults['underheader'] = array(
										array(1,''),
										array(1,''),
										array(2,'Past Day'),
										array(2,'Past 7 Days'),
										array(2,'Past 30 Days'),
										array(2,'Past 365 Days'),
										array(2,'All')
									);*/
	
	$query = "SELECT bnumber as bid, bname as name FROM ".sql_table('blog')." ORDER BY bnumber ASC";
	$res = sql_query($query);
	
	$mydata = array();
	$tot1 = 0;
	$tot7 = 0;
	$tot30 = 0;
	$tot365 = 0;
	$tot = 0;
	$tot1c = 0;
	$tot7c = 0;
	$tot30c = 0;
	$tot365c = 0;
	$totc = 0;
	
	while ($row = mysql_fetch_assoc($res)) {
		$data = array();
		$data[] = intval($row['bid']);
		$data[] = $row['name'];
		$num = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND iposted=1 AND idraft=0 AND UNIX_TIMESTAMP(itime) < UNIX_TIMESTAMP() AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 86400 "));
		$tot1 = $tot1 + $num;
		$data[] = $num;
		$num = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('comment')." WHERE cblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(ctime) > UNIX_TIMESTAMP() - 86400 "));
		$tot1c = $tot1c + $num;
		$data[] = $num;		
		$num = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND iposted=1 AND idraft=0 AND UNIX_TIMESTAMP(itime) < UNIX_TIMESTAMP() AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 604800 "));
		$tot7 = $tot7 + $num;
		$data[] = $num;		
		$num = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('comment')." WHERE cblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(ctime) > UNIX_TIMESTAMP() - 604800 "));
		$tot7c = $tot7c + $num;
		$data[] = $num;
		$num = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND iposted=1 AND idraft=0 AND UNIX_TIMESTAMP(itime) < UNIX_TIMESTAMP() AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 2592000 "));
		$tot30 = $tot30 + $num;
		$data[] = $num;
		$num = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('comment')." WHERE cblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(ctime) > UNIX_TIMESTAMP() - 2592000 "));		
		$tot30c = $tot30c + $num;
		$data[] = $num;
		$num = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND iposted=1 AND idraft=0 AND UNIX_TIMESTAMP(itime) < UNIX_TIMESTAMP() AND UNIX_TIMESTAMP(itime) > UNIX_TIMESTAMP() - 31536000 "));
		$tot365 = $tot365 + $num;
		$data[] = $num;
		$num = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('comment')." WHERE cblog=".intval($row['bid'])." AND UNIX_TIMESTAMP(ctime) > UNIX_TIMESTAMP() - 31536000 "));
		$tot365c = $tot365c + $num;
		$data[] = $num;
		$num = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iblog=".intval($row['bid'])." AND iposted=1 AND idraft=0 AND UNIX_TIMESTAMP(itime) < UNIX_TIMESTAMP()"));
		$tot = $tot + $num;
		$data[] = $num;
		$num = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('comment')." WHERE cblog=".intval($row['bid']).""));
		$totc = $totc + $num;
		$data[] = $num;
		$mydata[intval($row['bid'])] = $data;
	}
	ksort($mydata);
	$mydata[] = array(
						'',
						'Totals',
						$tot1,
						$tot1c,
						$tot7,
						$tot7c,
						$tot30,
						$tot30c,
						$tot365,
						$tot365c,
						$tot,
						$totc
					);
						
	$siRptResults['data'] = $mydata;

?>