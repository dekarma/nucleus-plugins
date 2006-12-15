<?php
// profile edit page

include('../../../config.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<!-- page stylesheet (site colors and layout definitions) -->
<link rel="stylesheet" type="text/css" href="editprofile.css" />
</head>
<body>
<div id="wrapper">
<div id="header"><a name="top"></a>
<h1><a href="<?php echo $CONF['IndexURL']?>" accesskey="1"><?php echo $CONF['SiteName']?></a></h1>
</div>
<div id="content">
<?php
if ($manager->pluginInstalled('NP_Profile')) {
    $plugin =& $manager->getPlugin('NP_Profile');
    $memberid = $member->getID();
    $memberinfo = MEMBER::createFromId($memberid);

    $thispage = $CONF['PluginURL'] . "profile/editprofile.php";
    $cvalue = $plugin->getConfigValue('editprofile');

    if (trim($cvalue) != '') {
        $lines = explode("\n", $cvalue);
    }
    else {
        $res = sql_query("SELECT fname FROM ".sql_table('plugin_profile_fields')." WHERE enabled=1");
        $lines = array('[t]','[t1]'.ucfirst(_PROFILE_PROFILE),'[/t]','[1]');
        $i = 4;
        while ($value = mysql_fetch_assoc($res)) {
            $lines[$i] = $value['fname'];
            $i++;
        }
        $lines[$i] = '[/1]';
    }
    echo "<h2>".$member->getDisplayName()."</h2>\n";
    $j = 0;
    $tabs = array();
    while ($cline = $lines[$j]) {
        $cline = trim($cline);
        if (substr($cline,0,1) != '#') {
            if (substr($cline,0,2) == '[t') {
                if (is_numeric(substr($cline,2,1))) {
                    $tabs[intval(substr($cline,2,1))] = substr($cline,4);
                }
            }
            if (substr($cline,0,3) == '[/t') {
                $j++;
                break;
            }
        }
        $j++;
    }

    $maxtab = count($tabs);
    $currTab = 0;
    $tlines = array();
    while ($cline = $lines[$j]) {
        $cline = trim($cline);
        if (substr($cline,0,1) != '#') {
            if (substr($cline,0,3) == "[$currTab]") {
                $k = 0;
            }
            elseif (substr($cline,0,4) == "[/$currTab]") {
                $currTab = $currTab + 1;
            }
            else {
                $tlines[$currTab, $k] = $cline;
                $k++;
            }
        }
        if ($currTab >= $maxtab) break;
    }


/**************************************
 *       tab chooser links            *
 **************************************/
    $tab = intval(requestVar('tab'));
    echo "<div>\n";
	echo '<ul class="navlist">'."\n";
    foreach ($tabs as $key=>$value) {
        echo ' <li><a class="'.($key == $tab ? 'current' : '').'" href="'.$thispage.'?edit=1&amp;tab='.$key.'">'.$value.'</a></li> '."\n";
    }
	echo " </ul></div>\n";
/**************************************
 *       tab 0                        *
 **************************************/
    if ($tab <= 0 || $tab >= $maxtab) {
        $plugin->doSkinVar('member', 'editlink','','','');
        echo "&nbsp;&nbsp;&nbsp;&nbsp;<span style=\"color:red\">";
        $plugin->doSkinVar('member', 'status','','','');
        echo "</span><br /><br />\n";

        $plugin->doSkinVar('member', 'startform','','','');
        $plugin->doSkinVar('member', 'submitbutton','','','');
        $tableopen = 0;
        foreach ($tlines[0] as $field) {
            $field = trim($field);
            if (strtolower(substr($field,0,2)) == '<h') {
                if ($tableopen) echo "</table>\n";
                $tableopen = 0;
                echo "$field\n";
            }
            else {
                $field = strtolower($field);
                if ($field != 'password') {
                    if ($plugin->fieldExists($field)) {
                        if (!$tableopen) echo "<table class=\"profiletable\">\n";
                        $tableopen = 1;
                        echo "<tr>\n<td class=\"profilecol1\">";
                        $plugin->doSkinVar('member', $field,'label','','');
                        echo "</td><td class=\"profilecol2\">";
                        $plugin->doSkinVar('member', $field,'','','');
                        echo "</td>\n</tr>\n";
                    }
                }
            }
        }
        if ($tableopen) echo "</table>\n";
        $plugin->doSkinVar('member', 'endform','','','');
        echo "<br />\n";
        $plugin->doSkinVar('member', 'password','','','');
    }
/**************************************
 *       tabs 1-9                     *
 **************************************/
    if ($tab > 0 && $tab < $maxtab) {
        foreach ($tlines[$tab] as $field) {
            if ($manager->pluginInstalled($field)) {
                $extplug =& $manager->getPlugin($field);
                echo "<div>\n";
                $extplug->editProfileForm();
                echo "</div><br />\n";
            }
        }
    }
}
echo "</div></div></body></html>\n";
?>