<?php
// profile edit page

include('../../../config.php');

$blogid = intRequestVar('blogid');
if (!$blogid) $blogid = $CONF['DefaultBlog'];

$b =& $manager->getBlog($blogid);
$blog = $b;	// references can't be placed in global variables?
if (!$blog->isValid) {
	$blogid = $CONF['DefaultBlog'];
	$b =& $manager->getBlog($blogid);
	$blog = $b;	// references can't be placed in global variables?
}

if ($manager->pluginInstalled('NP_Profile')) {
    $plugin =& $manager->getPlugin('NP_Profile');
	$cssURL = $plugin->getOption('CSS2URL');
}
else $cssURL = '';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo _CHARSET ?>" />
<!-- page stylesheet (site colors and layout definitions) -->
<link rel="stylesheet" type="text/css" href="<?php echo $cssURL ?>" />
<title><?php echo $blog->getName()?> &raquo; Member &raquo; Edit Profile</title>
</head>
<body>
<div id="wrapper">
<div id="header"><a name="top"></a>
<h1><a class="headertitle" href="<?php echo $blog->getURL()?>" accesskey="1"><?php echo $blog->getName()?></a></h1>
</div>
<div id="content">
<?php
if (isset($plugin)) {
    if (intRequestVar('memberid') > 0) {
        $memberid = intRequestVar('memberid');
    }
    else {
        $memberid = $member->getID();
    }
    $memberinfo = MEMBER::createFromId($memberid);
    $CONF['MemberURL'] = $blog->getURL();
    $returnURL = createMemberLink($memberid, '');

    $thispage = $CONF['PluginURL'] . "profile/editprofile.php?blogid=$blogid&memberid=$memberid";
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
	echo $plugin->doSkinVar('member','avatar','show','image','');
    echo "<h2>".$memberinfo->getDisplayName()."</h2>\n";

	// here put a paragraph with custom text.
	echo '<br /><div class="headertext">'.$plugin->getConfigValue('editprofileheader')."</div>\n";
	
	echo "<div class=\"returnlink\">\n";
	echo "<a href=\"$returnURL\" title=\""._PROFILE_SV_EDITLINK_FORM."\">"._PROFILE_SV_EDITLINK_FORM."</a>\n";
	
	echo "&nbsp;&nbsp;&nbsp;&nbsp;<span style=\"color:red\">";
    $plugin->doSkinVar('member', 'status','','','');
    echo "</span>\n";
	echo "</div>\n";

    if (intRequestVar('edit') == 0) {
		$plugin->doSkinVar('member', 'editprofile','','','');

	}

	echo "<br /><br />\n";

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
                $tlines[$currTab][$k] = $cline;
                $k++;
            }
        }
		$j++;
        if ($currTab >= $maxtab) break;
    }


/**************************************
 *       tab chooser links            *
 **************************************/
    $tab = intval(requestVar('tab'));
    echo "<div>\n";
	echo '<ul class="navlist">'."\n";
    foreach ($tabs as $key=>$value) {
        echo ' <li><a class="'.($key == $tab ? 'current' : 'notcurrent').'" href="'.$thispage.'&amp;edit=1&amp;tab='.$key.'">'.$value.'</a></li> '."\n";
    }
	echo ' <li><a class="close" href="'.$returnURL.'">'.ucfirst(_PROFILE_CLOSE).'</a></li> '."\n";
	echo " </ul></div>\n";
/**************************************
 *       tabs                        *
 **************************************/
    if ($tab <= 0 || $tab >= $maxtab) $tab = 0;

        $tableopen = 0;
        $formopen = 0;
        foreach ($tlines[$tab] as $field) {
            $field = trim($field);
            if (strtolower(substr($field,0,2)) == '[h') {
                if ($tableopen) echo "</table>\n";
                $tableopen = 0;
				$htype = strtolower(substr($field,1,2));
				$field = str_replace(array('[',']'), array('<','>'), $field);
                echo "$field</$htype>\n";
            }
            elseif ($field == 'password') {
                if ($tableopen) {
                    echo "</table>\n";
                    $tableopen = 0;
                }
                if ($formopen) {
                    $plugin->doSkinVar('member', 'endform','','','');
                    $formopen = 0;
                }
                $plugin->doSkinVar('member', 'password','','','');
            }
            elseif ($field == 'startform') {
                if ($tableopen) {
                    echo "</table>\n";
                    $tableopen = 0;
                }
                if ($formopen) {
                    $plugin->doSkinVar('member', 'endform','','','');
                    $formopen = 0;
                }
                $plugin->doSkinVar('member', 'startform','','','');
				$formopen = 1;
            }
            elseif ($field == 'endform') {
                if ($tableopen) {
                    echo "</table>\n";
                    $tableopen = 0;
                }
                if ($formopen) {
                    $plugin->doSkinVar('member', 'endform','','','');
                    $formopen = 0;
                }
            }
            elseif ($field == 'submitbutton') {
                if ($tableopen) {
                    echo "</table>\n";
                    $tableopen = 0;
                }
                if ($formopen) {
                    $plugin->doSkinVar('member', 'submitbutton','','','');
                }
            }
            else {
                $field = strtolower($field);
                if ($field != 'password') {
                    if ($plugin->fieldExists($field)) {
                        if (!$formopen) {
                            $plugin->doSkinVar('member', 'startform','','','');
                            $formopen = 1;
                        }
                        if (!$tableopen) {
                            echo "<table class=\"profiletable\">\n";
                            $tableopen = 1;
                        }
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
        if ($formopen) $plugin->doSkinVar('member', 'endform','','','');
        echo "<br />\n";

/**************************************
 *       extensions                   *
 **************************************/
 /* maybe idea of how to deal with profile extension plugin fields. wait for demand
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
*/
}
echo "<br />\n";
echo "<div class=\"returnlink\">\n";
echo "<a href=\"$returnURL\" title=\""._PROFILE_SV_EDITLINK_FORM."\">"._PROFILE_SV_EDITLINK_FORM."</a>\n";
echo "</div>\n";
echo "</div></div></body></html>\n";
?>