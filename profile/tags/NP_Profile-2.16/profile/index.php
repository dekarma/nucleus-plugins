<?php
/* This is the Admin Area page for the NP_Profile Plugin.
License:
This software is published under the same license as NucleusCMS, namely
the GNU General Public License. See http://www.gnu.org/licenses/gpl.html for
details about the conditions of this license.

In general, this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free
Software Foundation; either version 2 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

 */
	// if your 'plugin' directory is not in the default location, edit this
    // variable to point to your site directory (where config.php is)
	$strRel = '../../../';
	$plugname = "NP_Profile";

	include($strRel . 'config.php');
    global $CONF,$manager,$member;
	if (!$member->isAdmin())
		doError("You cannot access this area.");

    // $manager->checkTicket();

	include($DIR_LIBS . 'PLUGINADMIN.php');



	$disFieldCols = array('fvalidate','forder');
	$disTypeCols = array('fvalidate');
	$disTypes = array();
	$action_url = $CONF['ActionURL'];
	$thispage = $CONF['PluginURL'] . "profile/index.php";
	$adminpage = $CONF['AdminURL'];
	$admin = $member->isAdmin();
	$thisquerystring = serverVar('QUERY_STRING');
	$showlist = strtolower(trim(requestVar('showlist')));
	if (!in_array($showlist, array('fields','editfield','types','edittype','example','deleteconfirm','config'))) $showlist = 'fields';
	$status = intval(requestVar('status'));

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

.npprofile
{
padding: 3px 0;
margin-left: 0;
border-bottom: 1px solid #778;
}

.npprofile table {border-collapse: collapse;}
.npprofile .center {text-align: center;}
.npprofile .center table { margin-left: auto; margin-right: auto; text-align: left;}
.npprofile .center th { text-align: center !important; }
.npprofile td, .npprofile th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
.npprofile h1 {font-size: 150%; text-align:left;}
.npprofile h2 {font-size: 125%;}
.npprofile .p {text-align: left;}
.npprofile .e {background-color: #ccccff; font-weight: bold; color: #000000;}
.npprofile .h {background-color: #9999cc; font-weight: bold; color: #000000;}
.npprofile .v {background-color: #cccccc; color: #000000;}
.npprofile .vr {background-color: #cccccc; text-align: right; color: #000000;}
.npprofile hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>';

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('Profile');
	$oPluginAdmin->start($newhead);

	$profplug =& $oPluginAdmin->plugin;
	$slpid = $profplug->getID();
	$toplink = '<p class="center"><a href="'.$thispage.'?'.$thisquerystring.'#sitop" alt="Return to Top of Page">-'._PROFILE_TOP.'-</a></p>'."\n";

/**************************************
 *       Edit Options Link            *
 **************************************/
	echo "\n<div>\n";
	echo '<a name="sitop"></a>'."\n";
	echo '<a class="buttonlink" href="'.$adminpage.'?action=pluginoptions&amp;plugid='.$slpid.'">'._PROFILE_ADMIN_OPTIONS.'</a>'."\n";
	echo "</div>\n";

/**************************************
 *        Header                      *
 **************************************/
	$helplink = ' <a href="'.$adminpage.'?action=pluginhelp&amp;plugid='.$slpid.'"><img src="'.$CONF['PluginURL'].'profile/help.jpg" alt="help" title="help" /></a>';
	echo '<h2 style="padding-top:10px;">NP_Profile'.$helplink.'</h2>'."\n";

/**************************************
 *       function chooser links       *
 **************************************/
	echo '<div class="npprofile">'."\n";
	echo "<div>\n";
	echo '<ul class="navlist">'."\n";
	echo ' <li><a class="'.($showlist == 'fields' ? 'current' : '').'" href="'.$thispage.'?showlist=fields&amp;safe=true">'._PROFILE_ADMIN_FIELD_DEF.'</a></li> '."\n";
	echo ' <li><a class="'.($showlist == 'types' ? 'current' : '').'" href="'.$thispage.'?showlist=types&amp;safe=true">'._PROFILE_ADMIN_FIELD_TYPE.'</a></li>'."\n";
	echo ' <li><a class="'.($showlist == 'config' ? 'current' : '').'" href="'.$thispage.'?showlist=config&amp;safe=true">'._PROFILE_ADMIN_CONFIG.'</a></li>'."\n";
	echo ' <li><a class="'.($showlist == 'example' ? 'current' : '').'" href="'.$thispage.'?showlist=example&amp;safe=true">'._PROFILE_ADMIN_EXAMPLE.'</a></li>'."\n";
	echo " </ul></div>\n";

/**************************************
 *	 Field Defs					      *
 **************************************/
	if ($showlist == "fields" || $showlist == NULL)
	{
		echo '<div class="center">'."\n";
		echo "<h2>"._PROFILE_ADMIN_FIELDS_HEAD."</h2>\n";
		echo ' <a class="buttonlink" href="'.$thispage.'?showlist=editfield&amp;fname=&amp;safe=true">'._PROFILE_ADMIN_FIELDS_ADD.'</a>'."\n";
		if ($status){
			switch ($status) {
			case 1:
				echo " <span style=\"color:blue\">"._PROFILE_ADMIN_FIELDS_SUCCESS_ADD."</span>\n";
				break;
			case 2:
				echo "<span style=\"color:blue\">"._PROFILE_ADMIN_FIELDS_SUCCESS_UPD."</span>\n";
				break;
			case 3:
				echo " <span style=\"color:blue\">"._PROFILE_ADMIN_FIELDS_SUCCESS_DEL."</span>\n";
				break;
			default:
			}
		}
		$fieldres = $profplug->getFieldDef();

		echo '<table border="0" cellpadding="3" width="600">'."\n";
		echo "<tr class=\"h\">\n";
		echo "<th>".ucfirst(_PROFILE_FIELD)."</th><th>".ucfirst(_PROFILE_LABEL)."</th><th>".ucfirst(_PROFILE_TYPE)."</th><th>".ucfirst(_PROFILE_REQUIRED)."</th><th>".ucfirst(_PROFILE_ENABLED)."</th><th>".ucfirst(_PROFILE_ACTIONS)."</th></tr>\n";
		while ($row = mysql_fetch_assoc($fieldres)) {
			echo "<tr>\n";
			echo '<td class="e">'.$row['fname']."</td>\n";
			echo '<td class="v">'.$row['flabel']."</td>\n";
			echo '<td class="v">'.$row['ftype']."</td>\n";
			echo '<td class="v">'.$row['required']."</td>\n";
			echo '<td class="v">'.$row['enabled']."</td>\n";
			echo '<td class="v">';
			echo '<a href="'.$thispage.'?showlist=editfield&amp;fname='.$row['fname'].'&amp;safe=true">'._PROFILE_EDIT.'</a> . ';
			echo '<a href="'.$thispage.'?showlist=deleteconfirm&amp;fname='.$row['fname'].'&amp;safe=true">'._PROFILE_DELETE.'</a>';
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "</div>\n";
		echo '<p class="center"><a href="'.$thispage.'?showlist=editfield&amp;fname=&amp;safe=true">-'._PROFILE_ADMIN_FIELDS_ADD.'-</a></p>'."\n";
		echo $toplink;
	} // end fields

/**************************************
 *	 Edit Field Defs			      *
 **************************************/
	if ($showlist == "editfield")
	{
		$fname = requestVar('fname');
		if ($profplug->fieldExists($fname)) {
			$fieldres = $profplug->getFieldDef($fname);
			$row = mysql_fetch_assoc($fieldres);
			$ofname = $fname;
			$acttype = 'updatefield';
		}
		else {
			$fname = '';
			$ofname = $fname;
			$row = array('fname'=>'','flabel'=>'','ftype'=>'text','required'=>'0','enabled'=>'1','flength'=>'0','fsize'=>'0',
						'fformat'=>'','fwidth'=>'0','fheight'=>'0','ffilesize'=>'0','ffiletype'=>'','foptions'=>'','fvalidate'=>'');
			$acttype = 'addfield';
		}
		echo '<div class="center">'."\n";
		echo "<h2>"._PROFILE_ADMIN_FIELDS_EDIT_HEAD."</h2>\n";
		if ($status){
			switch ($status) {
			case 1:
				echo " <span style=\"color:blue\">"._PROFILE_ADMIN_FIELDS_SUCCESS_ADD."</span>\n";
				break;
			case 2:
				echo "<span style=\"color:blue\">"._PROFILE_ADMIN_FIELDS_SUCCESS_UPD."</span>\n";
				break;
			case 3:
				echo " <span style=\"color:blue\">"._PROFILE_ADMIN_FIELDS_SUCCESS_DEL."</span>\n";
				break;
			default:
			}
		}

		echo '<form method="post" action="'.$action_url.'">'."\n";
        echo '<input type="hidden" name="action" value="plugin" />'."\n";
        echo '<input type="hidden" name="name" value="Profile" />'."\n";
        echo '<input type="hidden" name="ofname" value="'.$ofname.'" />'."\n";
        $manager->addTicketHidden();
		echo '<table border="0" cellpadding="3" width="600">'."\n";
		echo "<tr class=\"h\">\n";
		echo "<th>".ucfirst(_PROFILE_PARAMETER)."</th><th>".ucfirst(_PROFILE_VALUE)."</th><th>".ucfirst(_PROFILE_HELP)."</th></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_NAME).'</td><td class="v"><input size="60" name="fname" value="'.$row['fname'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_NAME."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_LABEL).'</td><td class="v"><input size="60" name="flabel" value="'.$row['flabel'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_LABEL."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_TYPE).'</td><td class="v"><select name="ftype" size="1" >'."\n";
		foreach ($profplug->nutypes as $tvalue) {
			echo '<option value="'.$tvalue.'"'.($row['ftype'] == $tvalue ? 'selected="selected"' : '').">$tvalue</option>\n";
		}
		echo "</select></td>";
		echo "<td>"._PROFILE_ADMIN_HELP_TYPE."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_REQUIRED).'</td><td class="v">';
		echo '<input size="60" type="radio" name="required" value="1"'.($row['required'] >= '1' ? ' checked="checked"' : '').' />'._PROFILE_YES.' ';
		echo '<input size="60" type="radio" name="required" value="0"'.($row['required'] == '0' ? ' checked="checked"' : '').' />'._PROFILE_NO.' ';
		echo "</td><td>"._PROFILE_ADMIN_HELP_REQUIRED."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_ENABLED).'</td><td class="v">';
		echo '<input size="60" type="radio" name="enabled" value="1"'.($row['enabled'] >= '1' ? ' checked="checked"' : '').' />'._PROFILE_YES.' ';
		echo '<input size="60" type="radio" name="enabled" value="0"'.($row['enabled'] == '0' ? ' checked="checked"' : '').' />'._PROFILE_NO.' ';
		echo "</td><td>"._PROFILE_ADMIN_HELP_ENABLED."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_LENGTH).'</td><td class="v"><input size="60" name="flength" value="'.$row['flength'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_LENGTH."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_SIZE).'</td><td class="v"><input size="60" name="fsize" value="'.$row['fsize'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_SIZE."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_FORMAT).'</td><td class="v"><input size="60" name="fformat" value="'.htmlentities($row['fformat']).'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_FORMAT."</td></tr>\n";
        echo '<tr><td class="e">'.ucfirst(_PROFILE_FORMATNULL).'</td><td class="v"><input size="60" name="fvalidate" value="'.htmlentities($row['fvalidate']).'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_FORMATNULL."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_WIDTH).'</td><td class="v"><input size="60" name="fwidth" value="'.$row['fwidth'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_WIDTH."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_HEIGHT).'</td><td class="v"><input size="60" name="fheight" value="'.$row['fheight'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_HEIGHT."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_FILESIZE).'</td><td class="v"><input size="60" name="ffilesize" value="'.$row['ffilesize'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_FILESIZE."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_FILETYPES).'</td><td class="v"><input size="60" name="ffiletype" value="'.$row['ffiletype'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_FILETYPES."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_OPTIONS).'</td><td class="v"><input size="60" name="foptions" value="'.$row['foptions'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_OPTIONS."</td></tr>\n";
        echo '<tr><td class="e">'.ucfirst(_PROFILE_DEFAULT).'</td><td class="v"><input size="60" name="fdefault" value="'.$row['fdefault'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_DEFAULT."</td></tr>\n";
        echo '<tr><td class="e">'.ucfirst(_PROFILE_PUBLIC).'</td><td class="v">';
		echo '<input size="60" type="radio" name="fpublic" value="1"'.($row['fpublic'] >= '1' ? ' checked="checked"' : '').' />'._PROFILE_YES.' ';
		echo '<input size="60" type="radio" name="fpublic" value="0"'.($row['fpublic'] == '0' ? ' checked="checked"' : '').' />'._PROFILE_NO.' ';
		echo "</td><td>"._PROFILE_ADMIN_HELP_PUBLIC."</td></tr>\n";
		//echo '<tr><td class="e">'.ucfirst(_PROFILE_VALIDATE.'</td><td class="v"><input name="fvalidate" value="'.$row['fvalidate'].'" />'."</td><td></td></tr>\n";
		echo '<tr><td class="e">'._PROFILE_ADMIN_FIELDS_ACTION_PERFORM.'</td><td class="v"><select name="type" size="1" >'."\n";
		echo '<option value="updatefield"'.($acttype == 'updatefield' ? 'selected="selected"' : '').'>'._PROFILE_ADMIN_FIELDS_ACTION_UPD.'</option>' . "\n";
		echo '<option value="addfield"'.($acttype == 'addfield' ? 'selected="selected"' : '').'>'._PROFILE_ADMIN_FIELDS_ACTION_ADD.'</option>' . "\n";
		//echo '<option value="deletefield"'.($acttype == 'deletefield' ? 'selected="selected"' : '').'>'._PROFILE_ADMIN_FIELDS_ACTION_DEL.'</option>' . "\n";
		echo "</select></td><td>"._PROFILE_ADMIN_HELP_PERFORM."</td></tr>\n";
		echo '<tr><td class="e"></td><td class="v"><input type="submit" value="'._PROFILE_SUBMIT.'" />'."</td><td></td></tr>\n";
		echo "</table>\n";
		echo "</form>\n";
		echo "</div>\n";
		echo $toplink;
	} // end edit field

/**************************************
 *	 Confirm Field Delete		      *
 **************************************/
	if ($showlist == "deleteconfirm") {
		$fname = requestVar('fname');
		$acttype = 'deletefield';

		echo '<div class="center">'."\n";
		echo "<h2>"._PROFILE_ADMIN_FIELDS_DELETE_HEAD."</h2>\n";

		echo '<form method="post" action="'.$action_url.'">'."\n";
        echo '<input type="hidden" name="action" value="plugin" />'."\n";
        echo '<input type="hidden" name="name" value="Profile" />'."\n";
        echo '<input type="hidden" name="fname" value="'.$fname.'" />'."\n";
		echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
        $manager->addTicketHidden();
		echo _PROFILE_ADMIN_DELETE_OPEN." - '$fname'<br /><br />";
		echo _PROFILE_ADMIN_DELETE_BODY1."<br />\n";
		echo _PROFILE_ADMIN_DELETE_BODY2."<br /><br />\n";
		echo _PROFILE_ADMIN_DELETE_CONFIRM."<br /><br />\n";
		echo '<input type="submit" value="'._PROFILE_YES.'" />';
		echo '<br /><br /><a href="'.$thispage.'?showlist=fields&amp;safe=true">'._PROFILE_ADMIN_DELETE_RETURN.'</a>'."\n";
		echo "</form>\n";

		echo "</div>\n";
	}

/**************************************
 *	 Type Defs					      *
 **************************************/
	if ($showlist == "types")
	{
		echo '<div class="center">'."\n";
		echo "<h2>"._PROFILE_ADMIN_TYPES_HEAD."</h2>\n";
		if ($status){
			switch ($status) {
			/*case 1:
				echo " <span style=\"color:blue\">Type successfully added.</span>\n";
				break;*/
			case 2:
				echo "<span style=\"color:blue\">"._PROFILE_ADMIN_TYPES_SUCCESS_UPD."</span>\n";
				break;
			/*case 3:
				echo " <span style=\"color:blue\">Type successfully deleted.</span>\n";
				break;*/
			default:
			}
		}

		$typeres = $profplug->getTypeDef();

		echo '<table border="0" cellpadding="3" width="600">'."\n";
		echo "<tr class=\"h\">\n";
		echo "<th>".ucfirst(_PROFILE_TYPE)."</th><th>".ucfirst(_PROFILE_LENGTH)."</th><th>".ucfirst(_PROFILE_SIZE)."</th><th>".ucfirst(_PROFILE_FORMAT)."</th>";
		echo "<th>".ucfirst(_PROFILE_WIDTH)."</th><th>".ucfirst(_PROFILE_HEIGHT)."</th><th>".ucfirst(_PROFILE_FILESIZE)."</th><th>".ucfirst(_PROFILE_FILETYPES)."</th>";
		echo "<th>".ucfirst(_PROFILE_OPTIONS)."</th><th>".ucfirst(_PROFILE_ACTIONS)."</th></tr>\n";
		while ($row = mysql_fetch_assoc($typeres)) {
			echo "<tr>\n";
			echo '<td class="e">'.$row['type']."</td>\n";
			echo '<td class="v">'.$row['flength']."</td>\n";
			echo '<td class="v">'.$row['fsize']."</td>\n";
			echo '<td class="v">'.$row['fformat']."</td>\n";
			echo '<td class="v">'.$row['fwidth']."</td>\n";
			echo '<td class="v">'.$row['fheight']."</td>\n";
			echo '<td class="v">'.$row['ffilesize']."</td>\n";
			echo '<td class="v">'.$row['ffiletype']."</td>\n";
			echo '<td class="v">'.$row['foptions']."</td>\n";
			echo '<td class="v"><a href="'.$thispage.'?showlist=edittype&amp;dtype='.$row['type'].'&amp;safe=true">'._PROFILE_EDIT.'</a>'."</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "</div>\n";
		echo $toplink;
	} // end types

/**************************************
 *	 Edit Type Defs			      *
 **************************************/
	if ($showlist == "edittype")
	{
		$dtype = requestVar('dtype');
		if ($profplug->typeExists($dtype)) {
			$typeres = $profplug->getTypeDef($dtype);
			$row = mysql_fetch_assoc($typeres);
			$odtype = $dtype;
			$acttype = 'updatetype';

			echo '<div class="center">'."\n";
			echo "<h2>"._PROFILE_ADMIN_TYPES_EDIT_HEAD."</h2>\n";
			if ($status){
				switch ($status) {
				/*case 1:
					echo " <span style=\"color:blue\">Type successfully added.</span>\n";
					break;*/
				case 2:
					echo "<span style=\"color:blue\">"._PROFILE_ADMIN_TYPES_SUCCESS_UPD."</span>\n";
					break;
				/*case 3:
					echo " <span style=\"color:blue\">Type successfully deleted.</span>\n";
					break;*/
				default:
				}
			}

			echo '<form method="post" action="'.$action_url.'">'."\n";
			echo '<input type="hidden" name="action" value="plugin" />'."\n";
			echo '<input type="hidden" name="name" value="Profile" />'."\n";
			echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
			echo '<input type="hidden" name="odtype" value="'.$odtype.'" />'."\n";
			echo '<input type="hidden" name="dtype" value="'.$dtype.'" />'."\n";
            $manager->addTicketHidden();

			echo '<table border="0" cellpadding="3" width="600">'."\n";
		echo "<tr class=\"h\">\n";
		echo "<th>".ucfirst(_PROFILE_PARAMETER)."</th><th>".ucfirst(_PROFILE_VALUE)."</th><th>".ucfirst(_PROFILE_HELP)."</th></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_NAME).'</td><td class="v">'.$row['type']."</td>";
		echo "<td></td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_LENGTH).'</td><td class="v"><input size="60" name="flength" value="'.$row['flength'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_LENGTH."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_SIZE).'</td><td class="v"><input size="60" name="fsize" value="'.$row['fsize'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_SIZE."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_FORMAT).'</td><td class="v"><input size="60" name="fformat" value="'.$row['fformat'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_FORMAT."</td></tr>\n";
        echo '<tr><td class="e">'.ucfirst(_PROFILE_FORMATNULL).'</td><td class="v"><input size="60" name="fvalidate" value="'.htmlentities($row['fvalidate']).'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_FORMATNULL."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_WIDTH).'</td><td class="v"><input size="60" name="fwidth" value="'.htmlentities($row['fwidth']).'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_WIDTH."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_HEIGHT).'</td><td class="v"><input size="60" name="fheight" value="'.$row['fheight'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_HEIGHT."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_FILESIZE).'</td><td class="v"><input size="60" name="ffilesize" value="'.$row['ffilesize'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_TYPE_FILESIZE."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_FILETYPES).'</td><td class="v"><input size="60" name="ffiletype" value="'.$row['ffiletype'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_TYPE_FILETYPES."</td></tr>\n";
		echo '<tr><td class="e">'.ucfirst(_PROFILE_OPTIONS).'</td><td class="v"><input size="60" name="foptions" value="'.$row['foptions'].'" />'."</td>";
		echo "<td>"._PROFILE_ADMIN_HELP_OPTIONS."</td></tr>\n";
		//echo '<tr><td class="e">'.ucfirst(_PROFILE_VALIDATE).'</td><td class="v"><input name="fvalidate" value="'.$row['fvalidate'].'" />'."</td><td></td></tr>\n";
		echo '<tr><td class="e"></td><td class="v"><input type="submit" value="'._PROFILE_SUBMIT.'" />'."</td><td></td></tr>\n";
		echo "</table>\n";
		echo "</form>\n";
		echo "</div>\n";
		}
		else {
			echo "$dtype - "._PROFILE_ADMIN_TYPES_NO_TYPE."\n";
		}
		echo $toplink;
	} // end edit types

/**************************************
 *	 CONFIG  					      *
 **************************************/
	if ($showlist == "config")
	{
		$acttype = 'updateconfig';
		echo '<div>'."\n";
		echo "<h2>"._PROFILE_ADMIN_CONFIG_HEAD."</h2>\n";
		if ($status){
			switch ($status) {
			/*case 1:
				echo " <span style=\"color:blue\">Type successfully added.</span>\n";
				break;*/
			case 2:
				echo "<span style=\"color:blue\">"._PROFILE_ADMIN_CONFIG_SUCCESS_UPD."</span><br />\n";
				break;
			/*case 3:
				echo " <span style=\"color:blue\">Type successfully deleted.</span>\n";
				break;*/
			default:
			}
		}
		echo _PROFILE_ADMIN_CONFIG_INTRO."<p />\n";
		echo '<form method="post" action="'.$action_url.'">'."\n";
		echo '<input type="hidden" name="action" value="plugin" />'."\n";
		echo '<input type="hidden" name="name" value="Profile" />'."\n";
		echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
        $manager->addTicketHidden();
		echo '<table border="0" cellpadding="3" width="600">'."\n";
		echo '<tr><td class="e">editprofile</td><td class="v"><textarea name="editprofile" cols="30" rows="20">' . $profplug->getConfigValue('editprofile') . '</textarea>' . "</td></tr>\n";
		echo '<tr><td class="e"></td><td class="v"><input type="submit" value="'._PROFILE_SUBMIT.'" />'."</td></tr>\n";
		echo "</table>\n";
		echo "</form>\n";
		echo "</div>\n";
	}

/**************************************
 *	 Example					      *
 **************************************/
	if ($showlist == "example")
	{
		echo '<div>'."\n";
		echo "<h2>"._PROFILE_ADMIN_EXAMPLE_HEAD."</h2>\n";
		echo _PROFILE_ADMIN_EXAMPLE_INTRO."<p />\n";
		echo "<pre>\n";
		include ($DIR_PLUGINS."/profile/example.txt");
		echo "</pre>\n";
		echo _PROFILE_ADMIN_EXAMPLE_CSS."<p />\n";
		echo "<pre>\n";
		include ($DIR_PLUGINS."/profile/examplecss.txt");
		echo "</pre>\n";
		echo "</div>\n";

		echo $toplink;
	} // end types
	echo "</div>\n";
	$oPluginAdmin->end();


?>
