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
<div id="header"><a name="top"></a>
<h1><a href="<?php echo $CONF['IndexURL']?>" accesskey="1"><?php echo $CONF['SiteName']?></a></h1>
</div>
<div id="content">
<?php
if ($manager->pluginInstalled('NP_Profile')) {
    $plugin =& $manager->getPlugin('NP_Profile');
    $memberid = $member->getID();
    $memberinfo = MEMBER::createFromId($memberid);

    if (is_file('editprofile.cfg')) {
        $lines = file('editprofile.cfg');
    }
    else {
        $res = sql_query("SELECT fname FROM ".sql_table('plugin_profile_fields')." WHERE enabled=1");
        $lines = array();
        $i = 0;
        while ($value = mysql_fetch_assoc($res)) {
            $lines[$i] = $value['fname'];
            $i++;
        }
    }
    echo "<h2>".$member->getDisplayName()."</h2>\n";
    $plugin->doSkinVar('member', 'editlink','','','');
    echo "&nbsp;&nbsp;&nbsp;&nbsp;<span style=\"color:red\">";
    $plugin->doSkinVar('member', 'status','','','');
    echo "</span><br /><br />\n";

    $plugin->doSkinVar('member', 'startform','','','');
    if (getVar('edit') == 1) echo '<input type="submit" name="submit" value="'._PROFILE_SUBMIT.'" />' . "\n";
    $tableopen = 0;
    foreach ($lines as $field) {
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
echo "</div></body></html>\n";
?>