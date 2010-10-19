<?php
/* Admin area of NP_SystemInfo plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, http://www.iai.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 */

	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../';
	$plugname = "NP_SystemInfo";

	include($strRel . 'config.php');
	if ($member->isLoggedIn() && $member->canLogin()) $admin = 1;
	else doError('You\'re not logged in.');

	include($DIR_LIBS . 'PLUGINADMIN.php');

	global $CONF,$manager;
    // $manager->checkTicket();
	$action_url = $CONF['ActionURL'];
	$thispage = $CONF['PluginURL'] . "systeminfo/index.php";
	$adminpage = $CONF['AdminURL'];
	$thisquerystring = serverVar('QUERY_STRING');
	$toplink = '<p class="center"><a href="'.$thispage.'?'.$thisquerystring.'#sitop" alt="Return to Top of Page">-top-</a></p>'."\n";
	$showlist = strtolower(trim(requestVar('showlist')));
	if (!in_array($showlist, array('nucleus','php','mysql','apache','reports'))) $showlist = 'nucleus';
	$sublist = strtolower(trim(requestVar('sublist')));
	$tname = trim(requestVar('tname'));
	$fname = trim(requestVar('fname'));
	$oname = trim(requestVar('oname'));
	$iname = trim(requestVar('iname'));
	$iname = preg_replace('|[^a-z0-9.,_/-]|i', '_', $iname);

$newhead = '
<style>
.navlist
{
padding: 3px 0;
margin-left: 0;
border-bottom: 1px solid #778;
font: bold 12px Verdana, sans-serif;
}

.navlist li
{
list-style: none;
margin: 0;
display: inline;
}

.navlist li a
{
padding: 3px 0.5em;
margin-left: 3px;
border: 1px solid #778;
border-bottom: none;
background: #DDE;
text-decoration: none;
}

.navlist li a:link { color: #448; }
.navlist li a:visited { color: #667; }

.navlist li a:hover
{
color: #000;
background: #AAE;
border-color: #227;
}

.navlist li a.current
{
background: white;
border-bottom: 1px solid white;
}

a.buttonlink {
border:outset 1px;
padding:2px;
background-color:#DDE;
text-decoration:none;
}

.systeminfo
{
padding: 3px 0;
margin-left: 0;
border-bottom: 1px solid #778;
}

.systeminfo table {border-collapse: collapse;}
.systeminfo .center {text-align: center;}
.systeminfo .center table { margin-left: auto; margin-right: auto; text-align: left;}
.systeminfo .center th { text-align: center !important; }
.systeminfo td, .systeminfo th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
.systeminfo h1 {font-size: 150%; text-align:left;}
.systeminfo h2 {font-size: 125%;}
.systeminfo .p {text-align: left;}
.systeminfo .e {background-color: #ccccff; font-weight: bold; color: #000000;}
.systeminfo .h {background-color: #9999cc; font-weight: bold; color: #000000;}
.systeminfo .v {background-color: #cccccc; color: #000000;}
.systeminfo .vr {background-color: #cccccc; text-align: right; color: #000000;}
.systeminfo hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>';
	// create the admin area page
	$oPluginAdmin = new PluginAdmin('SystemInfo');
	$oPluginAdmin->start($newhead);

	$sysinfo =& $oPluginAdmin->plugin;
	$sipid = $sysinfo->getID();

	$admin = intval($sysinfo->siIsAdmin()) + intval($sysinfo->siIsBlogAdmin()) + intval($sysinfo->siIsTeamMember());
	$minaccess = intval($sysinfo->getOption('accesslevel'));
	if (!$minaccess || $minaccess == 0) $minaccess = 8;

	if (!($admin >= $minaccess)) doError("You do not have sufficient privileges.");

/**************************************
 *	   Edit Options Link			*
 **************************************/
	echo "\n<div>\n";
	echo '<a name="sitop"></a>'."\n";
	echo '<a class="buttonlink" href="'.$adminpage.'?action=pluginoptions&amp;plugid='.$sipid.'">Edit SystemInfo Options</a>'."\n";
	echo "</div>\n";

/**************************************
 *	   Header	        			  *
 **************************************/
	$helplink = ' <a href="'.$adminpage.'?action=pluginhelp&amp;plugid='.$sipid.'"><img src="'.$CONF['PluginURL'].'systeminfo/help.jpg" alt="help" title="help" /></a>';
	echo '<h2 style="padding-top:10px;">SystemInfo'.$helplink.'</h2>'."\n";

/**************************************
 *	   function chooser links	   *
 **************************************/
	echo '<div class="systeminfo">'."\n";
	echo "<div>\n";
	echo '<ul class="navlist">'."\n";
	echo ' <li><a class="'.($showlist == 'nucleus' ? 'current' : '').'" href="'.$thispage.'?showlist=nucleus&amp;safe=true">Nucleus CMS</a></li> '."\n";
	echo ' <li><a class="'.($showlist == 'php' ? 'current' : '').'" href="'.$thispage.'?showlist=php&amp;safe=true">PHP</a></li>'."\n";
	echo ' <li><a class="'.($showlist == 'mysql' ? 'current' : '').'" href="'.$thispage.'?showlist=mysql&amp;safe=true">MySQL</a></li>'."\n";
	echo ' <li><a class="'.($showlist == 'apache' ? 'current' : '').'" href="'.$thispage.'?showlist=apache&amp;safe=true">Apache</a></li>'."\n";
	echo ' <li><a class="'.($showlist == 'reports' ? 'current' : '').'" href="'.$thispage.'?showlist=reports&amp;safe=true">Reports</a></li>'."\n";
	echo " </ul></div>\n";
/**************************************
 *	 Nucleus CMS					*
 **************************************/
	if ($showlist == "nucleus" || $showlist == NULL)
	{
		echo '<table border="0" cellpadding="3" width="600"><tr>'."\n";
		echo "<td class=\"v\">Nucleus CMS Version: ".(getNucleusVersion() / 100)."</td>\n";
		echo "<td class=\"v\">Nucleus CMS PatchLevel: ".getNucleusPatchLevel()."</td>\n";
		if ($member->isLoggedIn() && $member->isAdmin())
			echo '<td class="v"><a class="buttonlink" href="http://nucleuscms.org/version.php?v=',getNucleusVersion(),'&amp;pl=',getNucleusPatchLevel(),'" title="Check for upgrade">Check for Newer Version</a></td>';
		echo "<tr>\n";
		echo "<td class=\"v\">PHP Version: ".PHP_VERSION."</td>\n";
		echo "<td class=\"v\">MySQL Server Version (client): ".mysql_get_server_info()." (".mysql_get_client_info().")</td>\n";
		if (function_exists('apache_get_version'))
			 echo "<td class=\"v\">Apache Version: ".apache_get_version()."</td>\n";
		else echo "<td class=\"v\">Apache Version Unknown!</td>\n";
		echo "</tr></table>\n";

		if (!in_array($sublist, array('all','config','conf','plugins','events','skins'))) $sublist = 'config';
		echo '<div>'."\n";
		echo '<ul class="navlist">'."\n";
		echo ' <li><a class="'.($sublist == 'all' ? 'current' : '').'" href="'.$thispage.'?showlist=nucleus&amp;sublist=all&amp;safe=true">All</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'config' ? 'current' : '').'" href="'.$thispage.'?showlist=nucleus&amp;sublist=config&amp;safe=true">Config.php Settings</a></li> '."\n";
		echo ' <li><a class="'.($sublist == 'conf' ? 'current' : '').'" href="'.$thispage.'?showlist=nucleus&amp;sublist=conf&amp;safe=true">$CONF Settings</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'plugins' ? 'current' : '').'" href="'.$thispage.'?showlist=nucleus&amp;sublist=plugins&amp;safe=true">Installed Plugins</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'events' ? 'current' : '').'" href="'.$thispage.'?showlist=nucleus&amp;sublist=events&amp;safe=true">Plugin Events</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'skins' ? 'current' : '').'" href="'.$thispage.'?showlist=nucleus&amp;sublist=skins&amp;safe=true">Installed Skins</a></li>'."\n";
		echo " </ul></div>\n";

		if ($sublist == '' || $sublist == 'all' || $sublist == 'config') {
			echo '<div class="center">'."\n";
			echo "<h2>config.php Settings</h2>\n";
			$configsettings = array('MYSQL_HOST'=>$MYSQL_HOST,'MYSQL_DATABASE'=>$MYSQL_DATABASE,
									'MYSQL_PREFIX'=>$MYSQL_PREFIX,'DIR_NUCLEUS'=>$DIR_NUCLEUS,
									'DIR_MEDIA'=>$DIR_MEDIA,'DIR_SKINS'=>$DIR_SKINS,
									'DIR_PLUGINS'=>$DIR_PLUGINS,'DIR_LANG'=>$DIR_LANG,
									'DIR_LIBS'=>$DIR_LIBS);

			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>Variable</th><th>Value</th></tr>\n";
			foreach ($configsettings as $key => $value) {
			   echo '<tr><td class="e" style="width:200px;">'.$key."</td>\n";
			   if ($key == 'MYSQL_PREFIX' && !$value) $value = "(default: nucleus)";
			   echo "<td class=\"v\">$value</td></tr>\n";
			}
			echo "</table>\n";
			echo "</div>\n";
			echo $toplink;
		} // config.php

		if ($sublist == 'all' || $sublist == 'conf') {
			$blogonly = array('ItemURL','ArchiveURL','ArchiveListURL','MemberURL','SearchURL','BlogURL','CategoryURL');
			echo '<div class="center">'."\n";
			echo "<h2>\$CONF Settings</h2>\n";

			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>Variable</th><th>Value</th></tr>\n";

			foreach ($CONF as $key => $value) {
			   echo '<tr><td class="e" style="width:200px;">'.$key."</td>\n";
			   if ($key == 'BaseSkin') $value .= " (".SKIN::getNameFromId($value).")";
			   if (in_array($key, $blogonly) && $value == '') $value = "Not Set. Gets set when a blog is loaded.";
			   echo "<td class=\"v\">$value</td></tr>\n";
			}
			echo "</table>\n";
			echo "</div>\n";
			echo $toplink;
		} // end conf

		if ($sublist == 'all' || $sublist == 'plugins') {
			echo '<div class="center">'."\n";
			echo "<h2>Installed Plugins</h2>\n";
			$sql = "SELECT * FROM ".sql_table('plugin')." ORDER BY porder ASC";
			$result = mysql_query($sql);
			if (mysql_num_rows($result) > 0) {
				echo '<table border="0" cellpadding="3" width="600">'."\n";
				echo "<tr class=\"h\">\n";
				echo "<th>Plugin Order</th><th>Plugin Name</th><th>Plugin ID</th></tr>\n";
				while ($row = mysql_fetch_assoc($result)) {
					echo '<tr><td class="e" style="width:200px;">'.$row['porder']."</td>\n";
					echo "<td class=\"v\">{$row['pfile']}</td>\n";
					echo "<td class=\"v\">{$row['pid']}</td></tr>\n";
				}
				echo "</table>\n";
			}
			else echo "No Plugins Installed";
			echo "</div>\n";
			echo $toplink;
		} // Plugins

		if ($sublist == 'all' || $sublist == 'events') {
			echo '<div class="center">'."\n";
			echo "<h2>Plugin Event Subscriptions</h2>\n";
			global $manager;
            $eventarr = $manager->subscriptions;
			if (count($eventarr > 0)) {
				echo '<table border="0" cellpadding="3" width="600">'."\n";
				echo "<tr class=\"h\">\n";
				echo "<th>Plugin Event</th><th>Subscribed Plugins</th></tr>\n";
				foreach ($eventarr as $key => $value) {
					echo '<tr><td class="e" style="width:200px;">'.$key."</td>\n";
					echo "<td class=\"v\">";
                    foreach ($value as $pname) {
                        echo "$pname<br />";
                    }
                    echo "</td></tr>\n";
				}
				echo "</table>\n";
			}
			else echo "No Plugins Events Subscribed";
			echo "</div>\n";
			echo $toplink;
		} // Plugin Events

		if ($sublist == 'all' || $sublist == 'skins') {
			echo '<div class="center">'."\n";
			echo "<h2>Installed Skins</h2>\n";
			$sql = "SELECT * FROM ".sql_table('skin_desc')." ORDER BY sdnumber ASC";
			$result = mysql_query($sql);
			if (mysql_num_rows($result) > 0) {
				echo '<table border="0" cellpadding="3" width="600">'."\n";
				echo "<tr class=\"h\">\n";
				echo "<th>Skin Number</th><th>Name</th><th>Desc</th><th>Type</th><th>Inc Mode</th><th>Inc Prefix</th></tr>\n";
				while ($row = mysql_fetch_assoc($result)) {
					echo '<tr><td class="e" style="width:200px;">'.$row['sdnumber']."</td>\n";
					echo "<td class=\"v\">{$row['sdname']}</td>\n";
					echo "<td class=\"v\">{$row['sddesc']}</td>\n";
					echo "<td class=\"v\">{$row['sdtype']}</td>\n";
					echo "<td class=\"v\">{$row['sdincmode']}</td>\n";
					echo "<td class=\"v\">{$row['sdincpref']}</td></tr>\n";
				}
				echo "</table>\n";
			}
			else echo "No Skins Installed";
			echo "</div>\n";
			echo $toplink;
		} // Plugins

	} //end show nucleus
/**************************************
 *	 PHP                              *
 **************************************/
	if ($showlist == "php")
	{
		if (!in_array($sublist, array('general','all','config','modules','envir','vars'))) $sublist = 'general';
		echo '<div>'."\n";
		echo '<ul class="navlist">'."\n";
		echo ' <li><a class="'.($sublist == 'all' ? 'current' : '').'" href="'.$thispage.'?showlist=php&amp;sublist=all&amp;safe=true">All</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'general' ? 'current' : '').'" href="'.$thispage.'?showlist=php&amp;sublist=general&amp;safe=true">General Settings</a></li> '."\n";
		echo ' <li><a class="'.($sublist == 'config' ? 'current' : '').'" href="'.$thispage.'?showlist=php&amp;sublist=config&amp;safe=true">Configuration Settings</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'modules' ? 'current' : '').'" href="'.$thispage.'?showlist=php&amp;sublist=modules&amp;safe=true">Loaded Modules</a></li> '."\n";
		echo ' <li><a class="'.($sublist == 'envir' ? 'current' : '').'" href="'.$thispage.'?showlist=php&amp;sublist=envir&amp;safe=true">Environment</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'vars' ? 'current' : '').'" href="'.$thispage.'?showlist=php&amp;sublist=vars&amp;safe=true">Pre-defined Variables</a></li> '."\n";
		echo " </ul></div>\n";

		if ($sublist == '' || $sublist == 'all' || $sublist == 'general') {
			echo '<div class="center">'."\n";
			echo "<h2>General</h2>\n";
			echo strip_phpinfo(INFO_GENERAL);
			echo $toplink;
		}

		if ($sublist == 'all' || $sublist == 'config') {
			if ($sublist != 'all') {
				echo "<p>Enter a PHP.ini configuration setting to see it's value, or leave blank to view the table of all configuration settings below.</p>";
				echo '<form method="post" action="">'."\n";
                $manager->addTicketHidden();
				echo add_value_input_field($iname,'PHP Directive');
				echo '</select><input type="submit" value="Find" class="formbutton" /></form>'."\n";
			}

			$lm = ini_get_all();
			echo '<div class="center">'."\n";
			echo "<h2>PHP Core</h2>\n";
			echo "<p>For boolean directives, 0 indicates Off and 1 indicates On.</p>";
			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>Directive</th><th>Local Value</th><th>Master Value</th></tr>\n";
			foreach ($lm as $key => $value) {
				if ($iname != '' && strpos($key,$iname)=== false) {}
				else {
					$localval = $value['local_value'];
					if ($localval == '') $localval = 'no value';
					//elseif ($localval == 1) $localval = 'On';
					//elseif ($localval == 0) $localval = 'Off';
					$globalval = $value['global_value'];
					if ($globalval == '') $globalval = 'no value';
					echo '<tr><td class="e">'.$key."</td>\n";
					echo "<td class=\"v\">".$localval."</td>\n";
					echo "<td class=\"v\">".$globalval."</td></tr>\n";
				}
			}
			echo "</table>\n";
			echo "</div>\n";

			//echo strip_phpinfo(INFO_CONFIGURATION);
			echo $toplink;
		}

		if ($sublist == 'all' || $sublist == 'modules') {
			$lm = get_loaded_extensions();
			if ($sublist != 'all') {
				echo "<p>Enter the name of a PHP module to see if it is loaded, or leave blank to view the table of all loaded modules below.</p>";
				echo '<form method="post" action="">'."\n";
                $manager->addTicketHidden();
				echo add_value_input_field($iname,'PHP Module');
				echo '</select><input type="submit" value="Find" class="formbutton" /></form>'."\n";
				if ($iname != '') {
					if (strpos(implode(",",$lm),$iname)) {
						echo '<div class="center">'."\n";
						echo '<table border="0" cellpadding="3" width="600">'."\n";
						echo "<tr class=\"h\">\n";
						echo "<th>Module</th><th>Module Version (if available)</th><th>Module Functions Available</th></tr>\n";
						foreach ($lm as $key => $value) {
							if (strpos($value,$iname)=== false) {}
							else {
								echo '<tr><td class="e"><a href="'.$thispage.'?'.$thisquerystring.'#module_'.$value.'" alt="Details">'.$value."</a></td>\n";
								echo "<td class=\"v\">".phpversion($value)."</td>\n";
								echo "<td class=\"v\">".implode(", ", get_extension_funcs($value))."</td></tr>\n";
							}
						}
						echo "</table>\n";
						echo "</div>\n";
					}
					else
						echo "<p>The $iname module is not installed.</p>";
				}
			}
			echo '<div class="center">'."\n";
			echo "<h2>Loaded Modules</h2>\n";

			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>Module</th><th>Module Version (if available)</th><th>Module Functions Available</th></tr>\n";
			foreach ($lm as $key => $value) {
			   echo '<tr><td class="e"><a href="'.$thispage.'?'.$thisquerystring.'#module_'.$value.'" alt="Details">'.$value."</a></td>\n";
			   echo "<td class=\"v\">".phpversion($value)."</td>\n";
			   echo "<td class=\"v\">".implode(", ", get_extension_funcs($value))."</td></tr>\n";
			}
			echo "</table>\n";
			echo "</div>\n";
			echo strip_phpinfo(INFO_MODULES);
			echo $toplink;
		}

		if ($sublist == 'all' || $sublist == 'envir') {
			echo strip_phpinfo(INFO_ENVIRONMENT);
			echo $toplink;
		}

		if ($sublist == 'all' || $sublist == 'vars') {
			echo strip_phpinfo(INFO_VARIABLES);
			echo $toplink;
		}

	} //end php
/**************************************
 *	 MySQL                            *
 **************************************/
	if ($showlist == "mysql")
	{
		echo '<table border="0" cellpadding="3" width="600">'."\n";
		echo "<tr>\n";
		echo "<td class=\"v\">MySQL Server Version: ".mysql_get_server_info()."</td>\n";
		echo "<td class=\"v\">MySQL Host: ".mysql_get_host_info()."</td>\n";
		echo "<td class=\"v\">MySQL Protocol: ".mysql_get_proto_info()."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class=\"v\">MySQL Prefix: ".(!$MYSQL_PREFIX ? "(default: nucleus)" : $MYSQL_TABLE)."</td>\n";
		echo "<td class=\"v\">MySQL Database: $MYSQL_DATABASE</td>\n";
		echo "<td class=\"v\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class=\"v\">MySQL Client Version: ".mysql_get_client_info()."</td>\n";
		echo "<td class=\"v\">MySQL Client Encoding: ".si_mysql_client_encoding()."</td>\n";
		echo "<td class=\"v\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td colspan=\"3\">MySQL Stats: ".si_mysql_stat()."</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		if (!in_array($sublist, array('status','tables','fdetails','tdetails','lookup'))) $sublist = 'tables';
		echo '<div>'."\n";
		echo '<ul class="navlist">'."\n";
		echo ' <li><a class="'.($sublist == 'status' ? 'current' : '').'" href="'.$thispage.'?showlist=mysql&amp;sublist=status&amp;safe=true">Status</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'tables' ? 'current' : '').'" href="'.$thispage.'?showlist=mysql&amp;sublist=tables&amp;safe=true">Tables</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'fdetails' ? 'current' : '').'" href="'.$thispage.'?showlist=mysql&amp;sublist=fdetails&amp;safe=true">Field Details</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'tdetails' ? 'current' : '').'" href="'.$thispage.'?showlist=mysql&amp;sublist=tdetails&amp;safe=true">Table Details</a></li>'."\n";
		echo ' <li><a class="'.($sublist == 'lookup' ? 'current' : '').'" href="'.$thispage.'?showlist=mysql&amp;sublist=lookup&amp;safe=true">Data Lookup</a></li>'."\n";
		echo " </ul></div>\n";

		if ($sublist == 'status') {
			echo '<div class="center">'."\n";
			echo "<h2>Status</h2>\n";
			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>Variable Name</th><th>Value</th></tr>\n";

			$sql = "SHOW STATUS";
			$result = mysql_query($sql);

			if (!$result) {
			   echo "DB Error, could not list tables\n";
			   echo 'MySQL Error: ' . mysql_error();
			   exit;
			}

			while ($row = mysql_fetch_row($result)) {
				echo "<tr>\n";
				echo "<td class=\"e\">{$row[0]}</td>\n";
				echo "<td class=\"v\">{$row[1]}</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
			echo "</div>\n";
			echo $toplink;
		}

		if ($sublist == '' || $sublist == 'tables') {
			echo '<div class="center">'."\n";
			echo "<h2>All Tables</h2>\n";
			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>Table</th><th>Fields</th></tr>\n";

			$sql = "SHOW TABLES from $MYSQL_DATABASE";
			$result = mysql_query($sql);

			if (!$result) {
			   echo "DB Error, could not list tables\n";
			   echo 'MySQL Error: ' . mysql_error();
			   exit;
			}

			while ($row = mysql_fetch_row($result)) {
				echo "<tr>\n";
				echo "<td class=\"e\">{$row[0]}</td>\n";

				$fresult = mysql_query("SHOW COLUMNS FROM $row[0]");
				if (!$fresult) {
				   echo 'Could not run query: ' . mysql_error();
				   exit;
				}
				if (mysql_num_rows($fresult) > 0) {
					$fieldlist = '';
					while ($frow = mysql_fetch_assoc($fresult)) {
						$fieldlist .= $frow['Field'].', ';
					}
				}
				echo "<td class=\"v\">".substr($fieldlist,0,-2)."</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
			echo "</div>\n";
			echo $toplink;
		}

		if ($sublist == 'fdetails') {
			if ($tname == '') $tname = 'all';
			$sql = "SHOW TABLES from $MYSQL_DATABASE";
			$result = mysql_query($sql);

			echo "<h2>Field Details</h2>\n";
			echo "<p>Select a table to view the tables configuration. Select all to see entire database schema.</p>\n";

			echo '<form name="siTblChooser" method="post" action="">'."\n";
            $manager->addTicketHidden();
			echo add_table_select_field($result,$tname,1);
			echo '</select><noscript><input type="submit" value="Set" class="formbutton" /></noscript></form>'."\n";

			echo '<div class="center">'."\n";
			echo '<table border="0" cellpadding="3" width="600">'."\n";
			if ($tname == 'all') {
				$result = mysql_query($sql);
				while ($row = mysql_fetch_row($result)) {
					$adata .= $row[0].",";
				}
				$a_tname = explode(",", substr($adata,0,-1));
			}
			else $a_tname = array($tname);

			foreach ($a_tname as $value) {
				echo '<tr><td colspan="6"><b>'.$value."</b></td></tr>\n";
				echo "<tr class=\"h\">\n";
				echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
				$fresult = mysql_query("SHOW COLUMNS FROM $value");
				if (!$fresult) {
				   echo 'Could not run query: ' . mysql_error();
				   exit;
				}
				if (mysql_num_rows($fresult) > 0) {
					while ($frow = mysql_fetch_assoc($fresult)) {
						echo "<tr>\n";
						echo "<td class=\"v\">{$frow['Field']}</td>\n";
						echo "<td class=\"v\">{$frow['Type']}</td>\n";
						echo "<td class=\"v\">{$frow['Null']}</td>\n";
						echo "<td class=\"v\">{$frow['Key']}</td>\n";
						echo "<td class=\"v\">{$frow['Default']}</td>\n";
						echo "<td class=\"v\">{$frow['Extra']}</td>\n";
						echo "</tr>\n";
					}
				}
			}
			echo "</table>\n";
			echo "</div>\n";
			echo $toplink;
		}

		if ($sublist == 'tdetails') {
			$sql = "SHOW TABLES from $MYSQL_DATABASE";
			$result = mysql_query($sql);

			echo "<h2>Table Details</h2>\n";
			echo "<p>Select a table to view the table's details.</p>\n";

			echo '<form name="siTblChooser" method="post" action="">'."\n";
            $manager->addTicketHidden();
			echo add_table_select_field($result,$tname,0);
			echo '</select><noscript><input type="submit" value="Set" class="formbutton" /></noscript></form>'."\n";

			if ($tname) {
				echo '<div class="center">'."\n";

				$a_tname = array($tname);
				//print_r($a_tname);

				foreach ($a_tname as $tvalue) {
					echo '<table border="0" cellpadding="3" width="600">'."\n";
					echo '<tr><td colspan="6"><b>'.$tvalue."</b></td></tr>\n";
					echo "<tr>\n";
					echo "<td class=\"e\" colspan=\"6\">Table was created using the following query:</td></tr>\n";
					$result = mysql_query("SHOW CREATE TABLE $tvalue");
					if (mysql_num_rows($result) > 0) {
						while ($row = mysql_fetch_assoc($result)) {
							echo "<tr>\n";
							echo "<td class=\"v\" colspan=\"6\">".$row['Create Table']."</td>\n";
							echo "</tr>\n";
						}
					}
					echo "</table>\n";
					echo '<table border="0" cellpadding="3" width="600">'."\n";
					echo '<tr><td colspan="2"><b>Table Index'."</b></td></tr>\n";
					$result = mysql_query("SHOW INDEX FROM $tvalue");
					if (mysql_num_rows($result) > 0) {
						echo '<tr class="h"><th>Parameter</th><th>Value</th>'."</tr>\n";
						while ($row = mysql_fetch_assoc($result)) {
							foreach ($row as $key => $value) {
								echo "<tr>\n";
								echo "<td class=\"e\">".$key."</td>\n";
								echo "<td class=\"v\">".$value."</td>\n";
								echo "</tr>\n";
							}
						}
					}
					echo "</table>\n";
					echo $toplink;
					echo '<table border="0" cellpadding="3" width="600">'."\n";
					echo '<tr><td colspan="2"><b>Table Stats'."</b></td></tr>\n";
					$result = mysql_query("SHOW TABLE STATUS LIKE '$tvalue'");
					if (mysql_num_rows($result) > 0) {
						echo '<tr class="h"><th>Parameter</th><th>Value</th>'."</tr>\n";
						while ($row = mysql_fetch_assoc($result)) {
							foreach ($row as $key => $value) {
								echo "<tr>\n";
								echo "<td class=\"e\">".$key."</td>\n";
								echo "<td class=\"v\">".$value."</td>\n";
								echo "</tr>\n";
							}
						}
					}
					echo "</table>\n";
					echo $toplink;
				}
				echo "</div>\n";
			}
		}

		if ($sublist == 'lookup') {
			echo "<h2>Data Lookup</h2>\n";
			echo "<p>Find data values by selecting parameters.</p>\n";

			$sql = "SHOW TABLES from $MYSQL_DATABASE";
			$result = mysql_query($sql);
			echo '<form name="siTblChooser" method="post" action="">'."\n";
            $manager->addTicketHidden();
			echo add_table_select_field($result,$tname,0);
			echo '</select><noscript><input type="submit" value="Set" class="formbutton" /></noscript></form>'."\n";

			if ($tname) {
				$fsql = "SHOW COLUMNS FROM $tname";
				$fresult = mysql_query($fsql);
				echo '<form method="post" action="">'."\n";
                $manager->addTicketHidden();
				echo '<input type="hidden" name="tname" value="'.$tname.'" />'."\n";
				echo add_field_select_field($fresult,$fname,0);
				$opers = array('like'=>'LIKE', '!LIKE'=>'NOT LIKE', 'eq'=>'=', '!eq'=>'!=', 'lt'=>'<', 'lteq'=>'<=', 'gt'=>'>', 'gteq'=>'>=');
				echo add_oper_select_field($opers,$oname,0);
				echo add_value_input_field($iname,$ilabel);
				echo '</select><input type="submit" value="Get" class="formbutton" /></form>'."\n";
			}

			if ($tname) {
				$blockfields = array('mpassword','mcookiekey');
				echo '<div class="center">'."\n";
				$op = $opers[$oname];
				$iname = addslashes($iname);
				if ($op == 'LIKE' || $op == 'NOT LIKE') $iname = "%$iname%";

				if ($fname) {
                    $dlsql = "SELECT * FROM $tname WHERE `$fname` $op '$iname'";
                    echo "Your Query: $dlsql \n";
                }
                else $dlsql = "SELECT * FROM $tname WHERE 1=2";
				$dlresult = mysql_query($dlsql);

				if (mysql_num_rows($dlresult) > 0) {
					echo " - Found ".mysql_num_rows($dlresult)." match(es)\n";
					while ($row = mysql_fetch_assoc($dlresult)) {
						echo '<table border="0" cellpadding="3" width="600">'."\n";
						echo '<tr class="h"><th>Field</th><th>Value</th>'."</tr>\n";
						foreach ($row as $key => $value) {
							echo "<tr>\n";
							echo "<td class=\"e\">".$key."</td>\n";
							if (in_array($key, $blockfields)) $value = "Value not displayed for security reasons";
							echo "<td class=\"v\">".$value."</td>\n";
							echo "</tr>\n";
						}
						echo "</table>\n";
						echo $toplink;
					}
				}
				else echo " - Found no matches";
				echo "</div>\n";
			}
		}

	} //end show mysql
/**************************************
 *	 Apache							*
 **************************************/
	if ($showlist == "apache")
	{
		echo '<div class="center">'."\n";

		echo get_apacheinfo(INFO_MODULES);
		echo $toplink;
		echo "</div>\n";

	} //end apache
	
/**************************************
 *	Reports							*
 **************************************/
	if ($showlist == "reports")
	{
		$report = postVar('report');
		echo '<div class="center">'."\n";
		
		$sql = "SHOW TABLES from $MYSQL_DATABASE";
		$result = mysql_query($sql);
		echo '<form name="siRptChooser" method="post" action="">'."\n";
		$manager->addTicketHidden();
		echo '<select name="report" onChange="document.siRptChooser.submit()">'."\n";
		$dir = $sysinfo->getDirectory().'reports/';
		$files = array();
		if($handle = opendir($dir))	{
			while($file = readdir($handle))
			{
				clearstatcache();
				if(is_file($dir.'/'.$file))
					$files[] = $file;
			}
			closedir($handle);
		} 
		sort($files);
		foreach ($files as $file) {
			$menu .= '<option value="'.$file.'"';
			$menu .= ($file == $report ? ' selected>' :'>');
			$menu .= str_replace(array('.php'),array(''),$file)."</option>\n";
		}
		echo $menu;
		echo '</select><input type="submit" value="Set" class="formbutton" /></form>'."\n";
		
		if ($report) {
			global $siRptResults;
			$siRptResults['header'] = array();
			$siRptResults['data'] = array();
			include($dir.$report);
			echo "<h2>$report</h2>\n";
			echo '<table border="0" cellpadding="3" width="600">'."\n";
			if (isset($siRptResults['overheader'])) {
				echo '<tr class="h">';
				foreach ($siRptResults['overheader'] as $overheader) {
					echo '<th colspan="'.$overheader[0].'">'.$overheader[1].'</th>';
				}
				echo "</tr>\n";
			}
			echo '<tr class="h">';
			foreach ($siRptResults['header'] as $key=>$header) {
				if (isset($siRptResults['width'][$key]) && is_int($siRptResults['width'][$key]))
					$width = ' width="'.intval($siRptResults['width'][$key]).'px"';
				echo '<th'.$width.'>'.$header.'</th>';
			}
			echo "</tr>\n";
			if (isset($siRptResults['underheader'])) {
				echo '<tr class="h">';
				foreach ($siRptResults['underheader'] as $underheader) {
					echo '<th colspan="'.$underheader[0].'">'.$underheader[1].'</th>';
				}
				echo "</tr>\n";
			}
			//now the data ($data is array)
			foreach ($siRptResults['data'] as $key=>$dataarr) {
				echo "<tr>";
				foreach ($dataarr as $data) {
					echo '<td class="v">'.$data.'</td>';
				}
				echo "</tr>\n";
			}
			echo "</table>\n";
		}
		
		echo $toplink;
		echo "</div>\n";

	} //end apache

	echo "</div>\n"; // end systeminfo
	$oPluginAdmin->end();

	function strip_phpinfo($what) {
		ob_start();
		phpinfo($what);
		$infotext = ob_get_contents();
		ob_end_clean();
		$infotext = preg_replace(array('|<!DOCTYPE.*<body>|is', '|</body>|i', '|</html>|i', '|<hr />|i', '|<h1>Configuration</h1>|i'), '', $infotext);
		if ($what == INFO_GENERAL)
			$infotext = preg_replace(array('|<tr class=.*<img.*</td></tr>|isU', '|<div class="center">|i'), '', $infotext);
		return $infotext;
	}

	function get_apacheinfo() {
		ob_start();
		phpinfo(INFO_MODULES);
		$infotext = ob_get_contents();
		ob_end_clean();
		preg_match('|<h2><a name="module_apache.*<h2><a name="module|ismU', $infotext,$matches);
		$infotext = '<h2><a name="module'.str_replace('<h2><a name="module','',$matches[0]);
		return $infotext;
	}

	function add_table_select_field($result, $tname = 'all', $hasAll = 1) {

		echo '<select name="tname" onChange="document.siTblChooser.submit()">'."\n";
		if ($hasAll) $menu = '<option value="all"'.($tname == 'all' ? ' selected>' : '>')."all</option>\n";

		while ($row = mysql_fetch_row($result)) {
			$data = $row[0];
			$menu .= '<option value="'.$data.'"';
			$menu .= ($data == $tname ? ' selected>' :'>');
			$menu .= $data."</option>\n";
		}
		$menu .= "</select>\n";
		return $menu;
	}

    function add_field_select_field($result, $fname = '', $hasAll = 1) {

		echo '<select name="fname">'."\n";
		if ($hasAll) $menu = '<option value="all"'.($fname == 'all' ? ' selected>' : '>')."all</option>\n";

		while ($row = mysql_fetch_row($result)) {
			$data = $row[0];
			$menu .= '<option value="'.$data.'"';
			$menu .= ($data == $fname ? ' selected>' :'>');
			$menu .= $data."</option>\n";
		}
		$menu .= "</select>\n";
		return $menu;
	}

    function add_oper_select_field($opers, $oname = '', $hasAll = 0) {

		echo '<select name="oname">'."\n";
		if ($hasAll) $menu = '<option value="all"'.($oname == 'all' ? ' selected>' : '>')."all</option>\n";

		foreach($opers as $key => $value) {
			$data = $key;
			$menu .= '<option value="'.$data.'"';
			$menu .= ($data == $oname ? ' selected>' :'>');
			$menu .= $data."</option>\n";
		}
		$menu .= "</select>\n";
		return $menu;
	}

	function add_value_input_field($iname = '', $ilabel = '') {
		$menu = '<label for="iname">'.$label."</label>\n";
		$menu .= '<input type="text" name="iname" value="'.$iname.'"'."/>\n";
		return $menu;
	}

// these functions are so can support PHP 4.0.6
	function si_mysql_client_encoding() {
		if (function_exists('mysql_client_encoding')) return mysql_client_encoding();
		else return "";
	}

	function si_mysql_stat() {
		if (function_exists('mysql_stat')) return mysql_stat();
		else return "";
	}

?>
