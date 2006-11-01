<?php
/* NP_SiteList verification test app
 * Part of a plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, based on work by Wouter Demuynck
 * http://www.iai.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 */
/*
 app to test whether web pages contain certain strings
 e.g. see if linked sites on your site meet simple content
 conditions--Like a link to your site.

 Two conditions are checked. By default the two conditions are taken
 from the NP_SiteList options. These conditions can be overridden by
 editing this file where indicated below.

 Also displayed is a link to the site,
 the HTTP response code, the # of redirects followed, and the HTML
 source.
*/
// make sure we have the nucleus config info needed
	include "../../../config.php";

// define some variables
        global $CONF,$manager,$member;
        $action_url = $CONF['ActionURL'];
        $admin = $member->isAdmin();
        $listpage = $CONF['PluginURL'] . "sitelist/index.php";
        $list = requestVar('list');
        $showlist = requestVar('showlist');
        $nsusp = requestVar('nsusp');
        $testfile = requestVar('testfile');
        $testurl = requestVar('testurl');

// check if admin user is logged on
if ($admin) {
// define the logic tables
	$logcnd = array("OR" => 0, "AND" => 1, "AND!" => 2, "!AND!" => 3, "OR!" => 4, "!OR!" => 5);
	$logtab0 = array("True","True","False","False","True","False");
	$logtab1 = array("True","False","True","False","True","True");
	$logtab2 = array("True","False","False","False","False","True");
	$logtab3 = array("False","False","False","True","True","True");
	$logtab = array($logtab0,$logtab1,$logtab2,$logtab3);

    $sitelist =& $manager->getPlugin('NP_SiteList');
	$cond01 = $sitelist->getOption('Cond01');
	$cond02 = $sitelist->getOption('Cond02');
	$logicop = $sitelist->getOption('LogicOp');

// uncomment these lines if you want to try conditions here instead
// of using the ones set in the SiteList plugin options page
		//$cond01 = '/w*.nucleuscms.org/i';
		//$cond02 = 'name="generator" content="nucleus';
		//$logicop = 'OR';

// the url to test
	if ($testfile == '' && $testurl == '') $testurl = "http://www.nucleuscms.org";

// make sure $DIR_PLUGINS doesn't point off your server
    $DIR_PLUGINS = str_replace('://', '', $DIR_PLUGINS);
// make sure we can get to the pear libraries from the NP_SiteList plugin
// if php version < 4.3.0, can't use set_include_path
if (function_exists('set_include_path')) {
    set_include_path($DIR_PLUGINS . 'sitelist/pear' . PATH_SEPARATOR . get_include_path());
}
else {
    $newpath = $DIR_PLUGINS . 'sitelist/pear'. PATH_SEPARATOR . ini_get('include_path');
    ini_set('include_path', $newpath);
}

// prepare the pear HTTP_Request client
	require_once 'HTTP/Request.php';
	$phost = null; // string - FQDN or IP of proxy
	$pport = null; // string - tcp port of proxy, eg 8080, 8000, 80
	$tout = 8; // number in seconds to wait for connect
	$rtout = array(8,500); // number in seconds, milliseconds to wait for read
    $allowredir = true; // (true) boolean - follow redirects when fetcing
    $maxredirs = 5; // (3) integer - max # of redirects to follow
// end of HTTP_Request variables

// fetch the page
	$params = array (
		'proxy_host' => $phost,
		'proxy_port' => $pport,
		'timeout' => $tout,
		'readTimeout' => $rtout,
		'allowRedirects' => $allowredir,
		'maxRedirects' => $maxredirs);

	echo "<html><body>";

	if ($testfile != '') {
		foreach (file($testfile) as $testurl) {
			$testurl = rtrim($testurl);
            $testurl = $sitelist->prepURL($testurl);
            if (substr($testurl, 0, 5) == 'Error') doError($testurl);

			$req = &new HTTP_Request($testurl,$params);
		        $req->sendRequest();
		        $webtext = $req->getResponseBody();

// prepare the conditions to check
// if basic string (no preg format) make into case insensitve search
			if ($cond01{0} != "/")
        		{$cond01 = "/".$cond01."/i";}
			if ($cond02{0} != "/")
        		{$cond02 = "/".$cond02."/i";}
// check the conditions
			$hascnd1 = preg_match($cond01,$webtext);
			$hascnd2 = preg_match($cond02,$webtext);

// print out the results
			echo '<div style="width:610px;padding:4px;border:1px solid #000000;">';
			echo "<a href=".$testurl.">".$testurl."</a>";
			echo "<br />";
			if ($hascnd1)
				{ echo "Contains '".$cond01."': <font style=\"color:#00dd00;\">True</font>"; }
			else
				{ echo "Contains '".$cond01."': <font style=\"color:#dd0000;\">False</font>"; }
			echo "<br />";
			if ($hascnd2)
				{ echo "Contains '".$cond02."': <font style=\"color:#00dd00;\">True</font>";}
			else
				{ echo "Contains '".$cond02."': <font style=\"color:#dd0000;\">False</font>";}

		if ($hascnd1 && $hascnd2) {
			$logsit = $logtab[0];
			$verify = $logsit[intval($logcnd[$logicop])];
		}
		if ($hascnd1 && !$hascnd2) {
			$logsit = $logtab[1];
			$verify = $logsit[intval($logcnd[$logicop])];
		}
		if (!$hascnd1 && $hascnd2) {
			$logsit = $logtab[2];
			$verify = $logsit[intval($logcnd[$logicop])];
		}
		if (!$hascnd1 && !$hascnd2) {
			$logsit = $logtab[3];
			$verify = $logsit[intval($logcnd[$logicop])];
		}

		echo "<br />Site Verifies ('".$cond01."' ".$logicop." '".$cond02."'): <font style=\"color:#2222ee;font-weight:bold;\">".$verify."</font><br />";
		echo '<br />With current conditions, this site would verify accordingly for the given logic operators: <br />';
		echo '<table style="width:600px;text-align:center;border:2px solid #aaaaaa;">';
		echo '<tr>';
		echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">OR</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">AND</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">AND!</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">!AND!</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">OR!</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">!OR!</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[0].'</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[1].'</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[2].'</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[3].'</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[4].'</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[5].'</td>';
		echo '</tr>';
		echo '</table>';


			echo "<br />";
			echo 'Response Code: '.$req->getResponseCode();
			echo "<br />";
			echo '# of Redirects: '.$req->_redirects;
			echo "<br /><br /></div>";
		}
	}
	else {
		$testurl = $sitelist->prepURL($testurl);
        if (substr($testurl, 0, 5) == 'Error') doError($testurl);

		$req = &new HTTP_Request($testurl,$params);
        	$req->sendRequest();
        	$webtext = $req->getResponseBody();

// prepare the conditions to check
// if basic string (no preg format) make into case insensitve search
		if ($cond01{0} != "/")
			{$cond01 = "/".$cond01."/i";}
		if ($cond02{0} != "/")
        		{$cond02 = "/".$cond02."/i";}
// check the conditions
		$hascnd1 = preg_match($cond01,$webtext);
		$hascnd2 = preg_match($cond02,$webtext);

// print out the results
		echo "<a href=".$testurl.">".$testurl."</a>";
			 if ($admin && $list == '1') {
				echo ' . <a href="',$action_url,'?action=plugin&amp;name=SiteList&amp;type=deleteurl&amp;url=',htmlentities($testurl),'">[delete]</a>';
                	        echo ' . <a href="',$action_url,'?action=plugin&amp;name=SiteList&amp;type=checked&amp;url=',htmlentities($testurl),'">[approve]</a>';
                        	echo ' . <a href="',$action_url,'?action=plugin&amp;name=SiteList&amp;type=exempturl&amp;url=',htmlentities($testurl),'&amp;nsusp=',$nsusp,'">[exempt]</a>';
                        	echo ' . <a href="',$action_url,'?action=plugin&amp;name=SiteList&amp;type=suspendurl&amp;url=',htmlentities($testurl),'&amp;nsusp=',$nsusp,'">[suspend]</a>';
			}
			if ($admin && $list == '0')
				echo ' . <a href="',$listpage,'?showlist=',$showlist,'&amp;safe=true">[Return to SiteList Admin Area]</a> . ';
		echo "<br />";
		if ($hascnd1)
			{ echo "Contains '".$cond01."': <font style=\"color:#00dd00;}\">True</font>"; }
		else
			{ echo "Contains '".$cond01."': <font style=\"color:#dd0000;}\">False</font>"; }
		echo "<br />";
		if ($hascnd2)
			{ echo "Contains '".$cond02."': <font style=\"color:#00dd00;}\">True</font>";}
		else
			{ echo "Contains '".$cond02."': <font style=\"color:#dd0000;}\">False</font>";}

		if ($hascnd1 && $hascnd2) {
			$logsit = $logtab[0];
			$verify = $logsit[intval($logcnd[$logicop])];
		}
		if ($hascnd1 && !$hascnd2) {
			$logsit = $logtab[1];
			$verify = $logsit[intval($logcnd[$logicop])];
		}
		if (!$hascnd1 && $hascnd2) {
			$logsit = $logtab[2];
			$verify = $logsit[intval($logcnd[$logicop])];
		}
		if (!$hascnd1 && !$hascnd2) {
			$logsit = $logtab[3];
			$verify = $logsit[intval($logcnd[$logicop])];
		}

		echo "<br />Site Verifies ('".$cond01."' ".$logicop." '".$cond02."'): <font style=\"color:#2222ee;font-weight:bold;}\">".$verify."</font><br />";
		echo '<br />With current conditions, this site would verify accordingly for the given logic operators: <br />';
		echo '<table style="width:600px;text-align:center;border:2px solid #aaaaaa;">';
		echo '<tr>';
		echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">OR</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">AND</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">AND!</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">!AND!</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">OR!</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;background-color:#dddddd;font-weight:bold;">!OR!</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[0].'</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[1].'</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[2].'</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[3].'</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[4].'</td>';
                echo '<td style="width=95px;border: 2px solid #aaaaaa;">'.$logsit[5].'</td>';
		echo '</tr>';
		echo '</table>';

		echo "<br />";
		echo 'Response Code: '.$req->getResponseCode();
		echo "<br />";
		echo '# of Redirects: '.$req->_redirects;
		echo "<br />";
		echo "<h2>HTML Source</h2>";
		echo '<div style="padding:4px;border:1px solid #000000;">';
		echo '<pre style="border: 1px solid black;"><code>'.htmlspecialchars($webtext).'</code></pre></div>';
	}
echo "</body></html>";
}
else echo "You are not logged in";
?>
