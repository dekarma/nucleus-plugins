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
	$siRptResults['header'] = array('Member Name','Is Super-Admin','Blog Teams','Total Posts');
	$siRptResults['width'] = array(100,45,45,45);
	
	$query = "SELECT mnumber as mid, mname as name, madmin as isadmin FROM ".sql_table('member')." WHERE madmin=1 OR mnumber IN (SELECT DISTINCT `tmember` FROM `".sql_table('team')."`) ORDER BY mname ASC";
	$res = sql_query($query);
	
	$mydata = array();
	
	while ($row = mysql_fetch_assoc($res)) {
		$numposts = intval(quickQuery("SELECT COUNT(*) as result FROM ".sql_table('item')." WHERE iauthor=".intval($row['mid'])." AND iposted=1 AND idraft=0"));
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
		$mydata[$row['name']] = $data;
	}
	ksort($mydata);
	$siRptResults['data'] = $mydata;

?>