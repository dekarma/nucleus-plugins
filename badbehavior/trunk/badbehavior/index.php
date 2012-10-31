<?php
/* Admin area of NP_BadBehavior plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, http://www.iai.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 */
 
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

.badbehavior
{
padding: 3px 0;
margin-left: 0;
border-bottom: 1px solid #778;
}

.badbehavior table {border-collapse: collapse;}
.badbehavior .center {text-align: center;}
.badbehavior .center table { margin-left: auto; margin-right: auto; text-align: left;}
.badbehavior .center th { text-align: center !important; }
.badbehavior td, .badbehavior th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
.badbehavior h1 {font-size: 150%; text-align:left;}
.badbehavior h2 {font-size: 125%;}
.badbehavior .p {text-align: left;}
.badbehavior .e {background-color: #ccccff; font-weight: bold; color: #000000;}
.badbehavior .h {background-color: #9999cc; font-weight: bold; color: #000000;}
.badbehavior .v {background-color: #cccccc; color: #000000;}
.badbehavior .vr {background-color: #cccccc; text-align: right; color: #000000;}
.badbehavior hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>';

	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../';
	$plugname = "NP_BadBehavior";

	include($strRel . 'config.php');

	include($DIR_LIBS . 'PLUGINADMIN.php');
	// create the admin area page
	$oPluginAdmin = new PluginAdmin('BadBehavior');
	$oPluginAdmin->start($newhead);
	
	if ($member->isLoggedIn() && $member->canLogin()) $admin = 1;
	else {
		echo 'You are not logged in.';
		$oPluginAdmin->end();
		exit;
	}

	global $CONF,$manager;
    // $manager->checkTicket();
	$action_url = $CONF['ActionURL'];
	$thispage = $CONF['PluginURL'] . "badbehavior/index.php";
	$adminpage = $CONF['AdminURL'];
	$thisquerystring = serverVar('QUERY_STRING');
	$toplink = '<p class="center"><a href="'.$thispage.'?'.$thisquerystring.'#sitop" alt="Return to Top of Page">-top-</a></p>'."\n";
	$showlist = strtolower(trim(requestVar('showlist')));
	if (!in_array($showlist, array('stats','admin','logs'))) $showlist = 'stats';
    $tname = stringStripTags(trim(requestVar('tname')));
    $fname = stringStripTags(trim(requestVar('fname')));
	$oname = stringStripTags(trim(requestVar('oname')));
	$iname = stringStripTags(trim(requestVar('iname')));
	$iname = preg_replace('|[^a-z0-9.,_/-]|i', '_', $iname);


// make sure bad behavior is loaded
    if (!defined('BB2_CORE')) {
        //echo "loading necessary bad behavior libraries...";
        global $DIR_PLUGINS;
        $homepath = $DIR_PLUGINS.'/badbehavior/';
        require_once($homepath.'/bad-behavior-nucleuscms.php');
        //echo " OK. Completed <br />\n";
    }

	$plugin =& $oPluginAdmin->plugin;
	$sipid = $plugin->getID();

	$admin = $plugin->siRights();
	$minaccess = intval($plugin->minRights);
	if (!$minaccess || $minaccess == 0) $minaccess = 8;

	if (!($admin >= $minaccess)) {
		echo "You do not have sufficient privileges.";
		$oPluginAdmin->end();
		exit;
	}

/**************************************
 *	   Edit Options Link			*
 **************************************/
	echo "\n<div>\n";
	echo '<a name="sitop"></a>'."\n";
	echo '<a class="buttonlink" href="'.$adminpage.'?action=pluginoptions&amp;plugid='.$sipid.'">Edit BadBehavior Options</a>'."\n";
	echo "</div>\n";

/**************************************
 *	   Header	        			  *
 **************************************/
	//$helplink = ' <a href="'.$adminpage.'?action=pluginhelp&amp;plugid='.$sipid.'"><img src="'.$CONF['PluginURL'].'badbehavior/help.jpg" alt="help" title="help" /></a>';
	//echo '<h2 style="padding-top:10px;">BadBehavior'.$helplink.'</h2>'."\n";

/**************************************
 *	   function chooser links	   *
 **************************************/
	echo '<div class="badbehavior">'."\n";
	echo "<div>\n";
	echo '<ul class="navlist">'."\n";
	echo ' <li><a class="'.($showlist == 'stats' ? 'current' : '').'" href="'.$thispage.'?showlist=stats&amp;safe=true">Stats</a></li> '."\n";
    echo ' <li><a class="'.($showlist == 'logs' ? 'current' : '').'" href="'.$thispage.'?showlist=logs&amp;safe=true">Logs</a></li>'."\n";
	echo ' <li><a class="'.($showlist == 'admin' ? 'current' : '').'" href="'.$thispage.'?showlist=admin&amp;safe=true">Admin</a></li>'."\n";
	echo " </ul></div>\n";
/**************************************
 *	 stats            				  *
 **************************************/
	if ($showlist == "stats" || $showlist == NULL)
	{
		bb2_insert_stats(true);
	} //end show nucleus

/**************************************
 *	 logs            				  *
 **************************************/
    if ($showlist == 'logs') {
        echo "<h2>Bad Behavior Logs</h2>\n";
        echo "<p>Find data values by selecting parameters.</p>\n";

        $tname = sql_table('bad_behavior');
        if ($tname) {
            $fsql = "SHOW COLUMNS FROM ".sql_real_escape_string($tname);
            $fresult = sql_query($fsql);
            echo '<form method="post" action="'.$thispage.'?showlist=logs">'."\n";
            echo '<input type="hidden" name="tname" value="'.$tname.'" />'."\n";
            echo add_field_select_field($fresult,$fname,0);
            $opers = array('like'=>'LIKE', '!LIKE'=>'NOT LIKE', 'eq'=>'=', '!eq'=>'!=', 'lt'=>'<', 'lteq'=>'<=', 'gt'=>'>', 'gteq'=>'>=');
            echo add_oper_select_field($opers,$oname,0);
            echo add_value_input_field($iname,$ilabel);
            $manager->addTicketHidden();
            echo '</select><input type="submit" value="Get" class="formbutton" /></form>'."\n";
        }

        if ($tname) {
            $blockfields = array('mpassword','mcookiekey');
            echo '<div class="center">'."\n";
            $op = $opers[$oname];
            if ($op == '') $op = 'NOT LIKE';
            $iname = sql_real_escape_string($iname);
            if ($op == 'LIKE' || $op == 'NOT LIKE') $iname = "%$iname%";
            if ($fname == '') $fname = 'id';
            $dlsql = "SELECT * FROM ".sql_real_escape_string($tname)." WHERE `".sql_real_escape_string($fname)."` $op '$iname' ORDER BY date DESC";
            echo "Your Query: $dlsql \n";
            $dlresult = sql_query($dlsql);

            if (sql_num_rows($dlresult) > 0) {
                echo " - Found ".sql_num_rows($dlresult)." match(es)\n";
                while ($row = sql_fetch_assoc($dlresult)) {
                    echo '<table border="0" cellpadding="3" width="600">'."\n";
                    echo '<tr class="h"><th>Field</th><th>Value</th>'."</tr>\n";
                    foreach ($row as $key => $value) {
                        echo "<tr>\n";
                        echo "<td class=\"e\">".$key."</td>\n";
                        if (in_array($key, $blockfields)) $value = "Value not displayed for security reasons";
						if ($key == 'ip') $value = '<a href="http://ip-lookup.net/?'.$value.'" target="_blank">'.$value.'</a>';
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
/**************************************
 *	 admin                            *
 **************************************/
	if ($showlist == "admin")
	{
		if (!$member->isAdmin())
			echo "You are not authorized to edit settings.";
		else {
			$settings = bb2_read_settings();

			if ($_POST) {
				if ($_POST['display_stats']) {
					$settings['display_stats'] = true;
				} else {
					$settings['display_stats'] = false;
				}
				if ($_POST['strict']) {
					$settings['strict'] = true;
				} else {
					$settings['strict'] = false;
				}
				if ($_POST['verbose']) {
					$settings['verbose'] = true;
				} else {
					$settings['verbose'] = false;
				}
				if (!$_POST['logging']) {
					$settings['logging'] = false;
				} else {
					$settings['logging'] = true;
				}
				if ($_POST['httpbl_key']) {
					$settings['httpbl_key'] = $_POST['httpbl_key'];
				} else {
					$settings['httpbl_key'] = '';
				}
				if (intval($_POST['httpbl_threat']) > 0) {
					$settings['httpbl_threat'] = intval($_POST['httpbl_threat']);
				} else {
					$settings['httpbl_threat'] = 25;
				}
				if (intval($_POST['httpbl_maxage']) > 0) {
					$settings['httpbl_maxage'] = intval($_POST['httpbl_maxage']);
				} else {
					$settings['httpbl_maxage'] = 30;
				}
				if ($_POST['offsite_forms']) {
					$settings['offsite_forms'] = true;
				} else {
					$settings['offsite_forms'] = false;
				}
				bb2_write_settings($settings);
?>
	<div id="message" class="updated fade"><p><strong><?php echo 'Options saved.' ?></strong></p></div>
<?php
	}
?>
	<div class="wrap">
	<h2><?php echo "Bad Behavior"; ?></h2>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
	<p>For more information please visit the <a href="http://bad-behavior.ioerror.us/" target="_blank">Bad Behavior</a> homepage.</p>
	<p>If you find Bad Behavior valuable, please consider making a <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=error%40ioerror%2eus&item_name=Bad%20Behavior%20<?php echo BB2_VERSION; ?>%20%28From%20Admin%29&no_shipping=1&cn=Comments%20about%20Bad%20Behavior&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8" target="_blank">financial contribution</a> to further development of Bad Behavior.</p>
	<p>Bad Behavior now incorporates data on harvesters and comment spammers compiled by <a href="http://www.projecthoneypot.org/?rf=24694">Project Honey Pot</a> and published through its http:BL service. In order to enable this feature, you must obtain an <a href="http://www.projecthoneypot.org/httpbl_configure.php?rf=24694"  target="_blank">http:BL access key</a> and provide this key to Bad Behavior in its settings. While the http:BL settings can be fine-tuned to block or allow requests based on the threat level and age of a harvester or comment spammer record, the default settings have been extensively tested and found to block virtually all spammers known to http:BL while allowing all legitimate users, even those that http:BL may have classified as suspicious. This feature obsoletes any other http:BL plugins you may have, and they can be removed.</p>

	<fieldset class="options">
	<legend><?php echo 'Logging'; ?></legend>
	<p><label><input type="checkbox" name="verbose" value="true" <?php if ($settings['verbose']) { ?>checked="checked" <?php } ?>/> <?php echo 'Advanced. (Default is Off). Enable Verbose HTTP request logging. Not recommended in production. For debug only.'; ?></label></p>
	<p><label><input type="checkbox" name="logging" value="true" <?php if ($settings['logging']) { ?>checked="checked" <?php } ?>/> <?php echo 'Advanced. (Default is On). Enable all HTTP request logging. Turning this off will allow more spam to pass through.'; ?></label></p>
	</fieldset>
	
	<fieldset class="options">
	<legend><?php echo 'Strict Mode'; ?></legend>
	<p><label><input type="checkbox" name="strict" value="true" <?php if ($settings['strict']) { ?>checked="checked" <?php } ?>/> <?php echo 'Advanced. (Default is Off) Strict checking (blocks more spam but may block some people).'; ?></label></p>
	</fieldset>

	<fieldset class="options">
	<legend><?php echo 'Project HoneyPot'; ?></legend>
	<p><label><input type="text" name="httpbl_key" value="<?php echo $settings['httpbl_key']?>" /> <?php echo 'HTTPBL Key for Project HoneyPot'; ?></label></p>
	<p><label><input type="text" name="httpbl_threat" value="<?php echo $settings['httpbl_threat']?>" /> <?php echo 'Advanced. (Default is 25). This number provides a measure of how suspicious an IP address is, based on activity observed at Project Honey Pot. Bad Behavior will block requests with a threat level equal or higher to this setting. <a href="http://www.projecthoneypot.org/threat_info.php" title="HTTPBL Threat Level" target="_blank">Project Honey Pot has more information on this parameter</a>.'; ?></label></p>
	<p><label><input type="text" name="httpbl_maxage" value="<?php echo $settings['httpbl_maxage']?>" /> <?php echo 'Advanced. (Default is 30). This is the number of days since suspicious activity was last observed from an IP address by Project Honey Pot. Bad Behavior will block requests with a maximum age equal to or less than this setting.'; ?></label></p>
	</fieldset>
	
	<fieldset class="options">
	<legend><?php echo 'Offsite Forms'; ?></legend>
	<p><label><input type="checkbox" name="offsite_forms" value="true" <?php if ($settings['offsite_forms']) { ?>checked="checked" <?php } ?>/> <?php echo 'Advanced. (Default is Off) Bad Behavior normally prevents your site from receiving data posted from forms on other web sites. This prevents spammers from, e.g., using a Google cached version of your web site to send you spam. However, some web applications such as OpenID require that your site be able to receive form data in this way. If you are running OpenID, enable this option.'; ?></label></p>
	</fieldset>
	
	<fieldset class="options">
	<legend><?php echo 'Reverse Proxy'; ?></legend>
	<p>Reverse Proxy settings are for advanced users and can only be set in the settings.ini file. To use this file, rename the settings-sample.ini file found in your $DIR_NUCLEUS/plugins/badbehavior/ folder to settings.ini and edit the settings in there.</p>
	<p>For details about the available Reverse Proxy (and other settings), please see the <a href="http://bad-behavior.ioerror.us/documentation/configuration/" title="Bad Behavior Configuration" target="_blank">Bad Behavior Configuration documentation</a>.</p>
	</fieldset>
	
	<fieldset class="options">
	<legend><?php echo 'Whitelisting'; ?></legend>
	<p>Whitelisting settings are for advanced users and can only be set in the whitelist.ini file. To use this file, rename the whitelist-sample.ini file found in your $DIR_NUCLEUS/plugins/badbehavior/ folder to whitelist.ini and edit the settings in there.</p>
	</fieldset>

	<p class="submit"><input type="submit" name="submit" value="<?php echo 'Update &raquo;'; ?>" /></p>
	</form>
	</div>

<?php
		}
	} //end admin

	echo "</div>\n"; // end badbehavior
	$oPluginAdmin->end();

/*************************************
 *   Helper Functions                *
 *************************************/
	function add_table_select_field($result, $tname = 'all', $hasAll = 1) {

		echo '<select name="tname">'."\n";
		if ($hasAll) $menu = '<option value="all"'.($tname == 'all' ? ' selected>' : '>')."all</option>\n";

		while ($row = sql_fetch_row($result)) {
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

		while ($row = sql_fetch_row($result)) {
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

?>