<?php
/* This is the Admin Area page for the NP_MultiLanguage Plugin.
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
if (!function_exists('scandir')) {
	function scandir($s_filename,$s_order) {
		$dirhandle = opendir($s_filename);
		$file_array = array();
		while ($filename = readdir($dirhandle)) {
			$file_array[] = $filename;
		}
		closedir($dirhandle);
		if (intval($s_order)) {
			rsort($file_array);
		}
		else {
			sort($file_array);
		}
		return $file_array;
	}
}
  
	// if your 'plugin' directory is not in the default location, edit this
    // variable to point to your site directory (where config.php is)
	$strRel = '../../../';
	$plugname = "NP_MultiLanguage";

	include($strRel . 'config.php');
    global $CONF,$manager,$member;
	//if (!$member->isAdmin()) doError("You cannot access this area.");

    // $manager->checkTicket();

	include($DIR_LIBS . 'PLUGINADMIN.php');

	$action_url = $CONF['ActionURL'];
	$thispage = $CONF['PluginURL'] . "multilanguage/index.php";
	$adminpage = $CONF['AdminURL'];
	$admin = $member->isAdmin();
	$thisquerystring = serverVar('QUERY_STRING');
	$showlist = strtolower(trim(requestVar('showlist')));
	$bshow = intval(requestVar('bshow'));
	if (!in_array($showlist, array('languages','editlanguage','templates','edittemplate','categories','editcategory','items','edititem','deleteconfirm'))) $showlist = 'items';
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

.npmultilanguage
{
padding: 3px 0;
margin-left: 0;
border-bottom: 1px solid #778;
}

.npmultilanguage table {border-collapse: collapse;}
.npmultilanguage .center {text-align: center;}
.npmultilanguage .center table { margin-left: auto; margin-right: auto; text-align: left;}
.npmultilanguage .center th { text-align: center !important; }
.npmultilanguage td, .npmultilanguage th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
.npmultilanguage h1 {font-size: 150%; text-align:left;}
.npmultilanguage h2 {font-size: 125%;}
.npmultilanguage .p {text-align: left;}
.npmultilanguage .e {background-color: #ccccff; font-weight: bold; color: #000000;}
.npmultilanguage .h {background-color: #9999cc; font-weight: bold; color: #000000;}
.npmultilanguage .v {background-color: #cccccc; color: #000000;}
.npmultilanguage .vr {background-color: #cccccc; text-align: right; color: #000000;}
.npmultilanguage hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>';

	// create the admin area page

	$oPluginAdmin = new PluginAdmin('MultiLanguage');
	$profplug =& $oPluginAdmin->plugin;
	$slpid = $profplug->getID();

	if (intval(requestVar('lid'))) {
		$charset = trim(quickQuery("SELECT mlcharset as result FROM ".sql_table('plugin_multilanguage_languages')." WHERE mllangid=".intval(requestVar('lid'))));
		if (strlen($charset) > 2) sendContentType('text/html','admin-MultiLanguage',$charset);
	}
	else sendContentType('text/html','admin-MultiLanguage',_CHARSET);

	$oPluginAdmin->start($newhead);


	$toplink = '<p class="center"><a href="'.$thispage.'?'.$thisquerystring.'#sitop" alt="Return to Top of Page">-'._MULTILANGUAGE_TOP.'-</a></p>'."\n";

/**************************************
 *       Edit Options Link            *
 **************************************/
	echo "\n<div>\n";
	echo '<a name="sitop"></a>'."\n";
	echo '<a class="buttonlink" href="'.$adminpage.'?action=pluginoptions&amp;plugid='.$slpid.'">'._MULTILANGUAGE_ADMIN_OPTIONS.'</a>'."\n";
	echo "</div>\n";

/**************************************
 *        Header                      *
 **************************************/
	$helplink = ' <a href="'.$adminpage.'?action=pluginhelp&amp;plugid='.$slpid.'"><img src="'.$CONF['PluginURL'].'multilanguage/help.jpg" alt="help" title="help" /></a>';
	echo '<h2 style="padding-top:10px;">NP_MultiLanguage'.$helplink.'</h2>'."\n";

/**************************************
 *       function chooser links       *
 **************************************/
	echo '<div class="npmultilanguage">'."\n";
	echo "<div>\n";
	echo '<ul class="navlist">'."\n";
	if ($admin) echo ' <li><a class="'.($showlist == 'languages' ? 'current' : '').'" href="'.$thispage.'?showlist=languages&amp;safe=true">'._MULTILANGUAGE_ADMIN_LANGUAGES.'</a></li> '."\n";
	if ($admin) echo ' <li><a class="'.($showlist == 'templates' ? 'current' : '').'" href="'.$thispage.'?showlist=templates&amp;safe=true">'._MULTILANGUAGE_ADMIN_TEMPLATES.'</a></li> '."\n";
	echo ' <li><a class="'.($showlist == 'categories' ? 'current' : '').'" href="'.$thispage.'?showlist=categories&amp;bshow='.$bshow.'&amp;safe=true">'._MULTILANGUAGE_ADMIN_CATEGORIES.'</a></li>'."\n";
	echo ' <li><a class="'.($showlist == 'items' ? 'current' : '').'" href="'.$thispage.'?showlist=items&amp;bshow='.$bshow.'&amp;safe=true">'._MULTILANGUAGE_ADMIN_ITEMS.'</a></li>'."\n";
	echo " </ul></div>\n";

/**************************************
 *	 Languages					      *
 **************************************/
	if ($showlist == "languages" || $showlist == NULL)
	{
		echo '<div class="center">'."\n";
		echo "<h2>"._MULTILANGUAGE_ADMIN_LANGUAGES_HEAD."</h2>\n";
		if ($admin) {
			echo ' <a class="buttonlink" href="'.$thispage.'?showlist=editlanguage&amp;lid=&amp;safe=true">'._MULTILANGUAGE_ADMIN_LANGUAGES_ADD.'</a>'."\n";
			if ($status){
				switch ($status) {
				case 1:
					echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_LANGUAGES_SUCCESS_ADD."</span>\n";
					break;
				case 2:
					echo "<span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_LANGUAGES_SUCCESS_UPD."</span>\n";
					break;
				case 3:
					echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_LANGUAGES_SUCCESS_DEL."</span>\n";
					break;
				default:
				}
			}
			$fieldres = $profplug->getLanguages();

			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>".ucfirst(_MULTILANGUAGE_LANGUAGE)."</th><th>".ucfirst(_MULTILANGUAGE_DISPLAY_NAME)."</th><th>".ucfirst(_MULTILANGUAGE_FLAG)."</th><th>".ucfirst(_MULTILANGUAGE_CHARSET)."</th><th>".ucfirst(_MULTILANGUAGE_NATIVE)."</th><th>".ucfirst(_MULTILANGUAGE_ACTIONS)."</th></tr>\n";
			while ($row = mysql_fetch_assoc($fieldres)) {
				echo "<tr>\n";
				echo '<td class="e">'.$row['mllanguage']."</td>\n";
				echo '<td class="v">'.$row['mllangname']."</td>\n";
				echo '<td class="v"><img src="'.$row['mlflag'].'" alt="'.htmlspecialchars($row['mllangname']).' flag" />'."</td>\n";
				echo '<td class="v">'.$row['mlcharset']."</td>\n";
				echo '<td class="v">'.($row['mlnative'] ? '<strong>'._YES.'</strong>' : _NO)."</td>\n";
				echo '<td class="v">';
				echo '<a href="'.$thispage.'?showlist=editlanguage&amp;lid='.$row['mllangid'].'&amp;safe=true">'._MULTILANGUAGE_EDIT.'</a> . ';
				echo '<a href="'.$thispage.'?showlist=deleteconfirm&amp;lid='.$row['mllangid'].'&amp;safe=true">'._MULTILANGUAGE_DELETE.'</a>';
				echo "</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
			echo "</div>\n";
			echo '<p class="center"><a href="'.$thispage.'?showlist=editlanguage&amp;lid=&amp;safe=true">-'._MULTILANGUAGE_ADMIN_LANGUAGES_ADD.'-</a></p>'."\n";
		}
		else echo "<br />"._MULTILANGUAGE_ADMIN_LANGUAGES_DENY."<br /></div>\n";
		echo $toplink;
	} // end fields

/**************************************
 *	 Edit Language			      *
 **************************************/
	if ($showlist == "editlanguage")
	{
		echo '<div class="center">'."\n";
		echo "<h2>"._MULTILANGUAGE_ADMIN_LANGUAGE_EDIT_HEAD."</h2>\n";
		if ($admin) {
			$lid = requestVar('lid');
			if ($profplug->languageExists($lid)) {
				$langres = $profplug->getLanguageDef($lid);
				$row = mysql_fetch_assoc($langres);
				$acttype = 'updatelanguage';
			}
			else {
				$lid = 0;
				$row = array('mllangid'=>0,'mllanguage'=>'','mllangname'=>'','mlflag'=>'','mlcharset'=>'','mlnative'=>0);
				$acttype = 'addlanguage';
			}

			if ($status){
				switch ($status) {
				case 1:
					echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_LANGUAGES_SUCCESS_ADD."</span>\n";
					break;
				case 2:
					echo "<span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_LANGUAGES_SUCCESS_UPD."</span>\n";
					break;
				case 3:
					echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_LANGUAGES_SUCCESS_DEL."</span>\n";
					break;
				default:
				}
			}

			echo '<form method="post" action="'.$action_url.'">'."\n";
			echo '<input type="hidden" name="action" value="plugin" />'."\n";
			echo '<input type="hidden" name="name" value="MultiLanguage" />'."\n";
			echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
			echo '<input type="hidden" name="mllangid" value="'.$lid.'" />'."\n";
			$manager->addTicketHidden();
			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>".ucfirst(_MULTILANGUAGE_PARAMETER)."</th><th>".ucfirst(_MULTILANGUAGE_VALUE)."</th><th>".ucfirst(_MULTILANGUAGE_HELP)."</th></tr>\n";
			echo '<tr><td class="e">'.ucfirst(_MULTILANGUAGE_LANGUAGE).'</td><td class="v"><input size="60" name="mllanguage" value="'.$row['mllanguage'].'" />'."</td>";
			echo "<td>"._MULTILANGUAGE_ADMIN_HELP_LANGUAGE."</td></tr>\n";
			echo '<tr><td class="e">'.ucfirst(_MULTILANGUAGE_DISPLAY_NAME).'</td><td class="v"><input size="60" name="mllangname" value="'.$row['mllangname'].'" />'."</td>";
			echo "<td>"._MULTILANGUAGE_ADMIN_HELP_DISPLAY_NAME."</td></tr>\n";
			echo '<tr><td class="e">'.ucfirst(_MULTILANGUAGE_FLAG).'</td><td class="v"><select name="mlflag" size="1" >'."\n";

			$flagarr = scandir($DIR_PLUGINS.'multilanguage/flags/');
			foreach ($flagarr as $filename) {
				if (ereg("^(.*)\.gif$",$filename,$matches)) {
					$name = $CONF['PluginURL'] . "multilanguage/flags/".$matches[1].".gif";
					echo '<option style="background:#ffffff url('.$name.') no-repeat right 0px" value="'.$name.'"';
					if ($name == $row['mlflag'])
						echo " selected='selected'";
					echo '>'.$matches[1].'</option>';
				}
			}
			

			echo "</select></td>";
			echo "<td>"._MULTILANGUAGE_ADMIN_HELP_FLAG."</td></tr>\n";

			echo '<tr><td class="e">'.ucfirst(_MULTILANGUAGE_CHARSET).'</td><td class="v"><input size="60" name="mlcharset" value="'.$row['mlcharset'].'" />'."</td>";
			echo "<td>"._MULTILANGUAGE_ADMIN_HELP_CHARSET."</td></tr>\n";
			
			echo '<tr><td class="e">'.ucfirst(_MULTILANGUAGE_NATIVE).'</td><td class="v">';
			echo '<input type="radio" name="mlnative" value="1"'.($row['mlnative'] ? ' checked="checked"' : '').' id="mlnative-yes" /><label for="mlnative-yes">'._YES.'</label> ';
			echo '<input type="radio" name="mlnative" value="0"'.(!$row['mlnative'] ? ' checked="checked"' : '').' id="mlnative-no" /><label for="mlnative-no">'._NO.'</label>';
			echo "</td>";
			echo "<td>"._MULTILANGUAGE_ADMIN_HELP_NATIVE."</td></tr>\n";

			echo '<tr><td class="e"></td><td class="v"><input type="submit" value="'._MULTILANGUAGE_SUBMIT.'" />'."</td><td></td></tr>\n";
			echo "</table>\n";
			echo "</form>\n";

		}
		else echo "<br />"._MULTILANGUAGE_ADMIN_LANGUAGES_DENY."<br />";
		echo "</div>\n";
		echo $toplink;
	} // end edit language

/**************************************
 *	Templates					 *
 **************************************/
	if ($showlist == "templates")
	{
		echo '<div class="center">'."\n";
		echo "<h2>"._MULTILANGUAGE_ADMIN_TEMPLATES_HEAD."</h2>\n";

		
		if ($admin) {

			/**************************************
			 *      Template Selector Form			 *
			 **************************************/
			$menu = '<div class="center">'."\n";
			$menu .= '<form name="tempChooser" method="post" action="'.$thispage.'">'."\n";
			$menu .= '<h3>'._MULTILANGUAGE_ADMIN_TEMPLATES_ADD.' &nbsp;&nbsp;';
			$menu .= '<input type="hidden" name="showlist" value="edittemplate" />'."\n";
			//$menu .= '<input type="hidden" name="cid" value="" />'."\n";
			$menu .= '<input type="hidden" name="lid" value="" />'."\n";
			$menu .= '<select name="tid" onChange="document.tempChooser.submit()">'."\n";
			$menu .= '<option value="0" '.($tid == '' ? ' selected>' :'>')._MULTILANGUAGE_ADMIN_TEMPLATES_ADD.'</option>';
			$tres = sql_query("SELECT tdnumber,tdname FROM ".sql_table('template_desc'));
			while ($data = mysql_fetch_assoc($tres))
			{
				$menu .= '<option value="'.$data['tdnumber'].'">';
				$menu .= $data['tdname'].'</option>';

			}
			$menu .= '</select><noscript><input type="submit" value="Go" class="formbutton" /></noscript></form>'."\n";
			$menu .= '</h3></div>'."\n";
			echo $menu."\n";

			//echo ' <a class="buttonlink" href="'.$thispage.'?showlist=editcategory&amp;cid=&amp;lid=&amp;bshow='.$bshow.'&amp;safe=true">'._MULTILANGUAGE_ADMIN_CATEGORIES_ADD.'</a>'."\n";
			if ($status){
				switch ($status) {
				case 1:
					echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_TEMPLATES_SUCCESS_ADD."</span>\n";
					break;
				case 2:
					echo "<span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_TEMPLATES_SUCCESS_UPD."</span>\n";
					break;
				case 3:
					echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_TEMPLATES_SUCCESS_DEL."</span>\n";
					break;
				default:
				}
			}
			$fieldres = $profplug->getTemplates();

			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>".ucfirst(_MULTILANGUAGE_TEMPLATE)."</th><th>".ucfirst(_MULTILANGUAGE_LANGUAGE)."</th><th>".ucfirst(_MULTILANGUAGE_TEMPLATE_NAME)."</th><th>".ucfirst(_MULTILANGUAGE_ACTIONS)."</th></tr>\n";
			while ($row = mysql_fetch_assoc($fieldres)) {
				echo "<tr>\n";
				echo '<td class="e">'.$row['mltempid']." (".TEMPLATE::getNameFromId($row['mltempid']).") "."</td>\n";
				echo '<td class="v">'.$profplug->getLanguageFromId($row['mllangid']).' : '. '<img src="'.$profplug->getFlagFromLanguageId($row['mllangid']).'" alt="flag" />'."</td>\n";
				echo '<td class="v">'.$row['mltempname']."</td>\n";
				echo '<td class="v">';
				echo '<a href="'.$thispage.'?showlist=edittemplate&amp;tid='.$row['mltempid'].'&amp;lid='.$row['mllangid'].'&amp;safe=true">'._MULTILANGUAGE_EDIT.'</a> . ';
				echo '<a href="'.$thispage.'?showlist=deleteconfirm&amp;tidlang='.$row['mltempid'].'_'.$row['mllangid'].'&amp;safe=true">'._MULTILANGUAGE_DELETE.'</a>';
				echo "</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
			echo "</div>\n";
			//echo $menu."\n";

			//echo '<p class="center"><a href="'.$thispage.'?showlist=editcategory&amp;cid=&amp;lid=&amp;bshow='.$bshow.'&amp;safe=true">-'._MULTILANGUAGE_ADMIN_CATEGORIES_ADD.'-</a></p>'."\n";
		}
		else echo "<br />"._MULTILANGUAGE_ADMIN_TEMPLATES_DENY."<br /></div>\n";
		echo $toplink;
	} // end templates

/**************************************
 *	 Edit Template 				 *
 **************************************/
	if ($showlist == "edittemplate")
	{
		echo '<div class="center">'."\n";
		echo "<h2>"._MULTILANGUAGE_ADMIN_TEMPLATE_EDIT_HEAD."</h2>\n";
		if ($admin) {
			$lid = intval(requestVar('lid'));
			$tid = intval(requestVar('tid'));

			$oquery = "SELECT tdname,tddesc FROM ".sql_table('template_desc')." WHERE tdnumber=$tid";
//echo "<hr /><span style=\"padding-left:150px\">$oquery</span><hr />";
			$ores = sql_query($oquery);

			if (mysql_num_rows($ores) == 1) {
				$orow = mysql_fetch_assoc($ores);
				if ($profplug->templateExists($tid,$lid)) {
					$langres = $profplug->getTemplateDef($tid,$lid);
					$row = mysql_fetch_assoc($langres);
					$acttype = 'updatetemplate';
				}
				else {
					$lid = 0;
					$row = array('mltempid'=>$tid,'mllangid'=>0,'mltempname'=>'');
					$acttype = 'addtemplate';
				}

				if ($status){
					switch ($status) {
					case 1:
						echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_TEMPLATES_SUCCESS_ADD."</span>\n";
						break;
					case 2:
						echo "<span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_TEMPLATES_SUCCESS_UPD."</span>\n";
						break;
					case 3:
						echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_TEMPLATES_SUCCESS_DEL."</span>\n";
						break;
					default:
					}
				}

				echo '<form method="post" action="'.$action_url.'">'."\n";
				echo '<input type="hidden" name="action" value="plugin" />'."\n";
				echo '<input type="hidden" name="name" value="MultiLanguage" />'."\n";
				echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
				echo '<input type="hidden" name="mltempid" value="'.$tid.'" />'."\n";

				$manager->addTicketHidden();
				echo '<table border="0" cellpadding="3" width="600">'."\n";
				echo "<tr class=\"h\">\n";
				echo "<th>".ucfirst(_MULTILANGUAGE_PARAMETER)."</th><th>".ucfirst(_MULTILANGUAGE_VALUE)."</th><th>".ucfirst(_MULTILANGUAGE_ORIGINAL_VALUE)."</th><th>".ucfirst(_MULTILANGUAGE_HELP)."</th></tr>\n";

				echo '<tr><td class="e">'.ucfirst(_MULTILANGUAGE_LANGUAGE).'</td><td class="v">';
				if ($lid > 0) {
					echo '<input type="hidden" name="mllangid" value="'.$lid.'" />'.$profplug->getLanguageFromId($lid)."\n";
				}
				else{
					echo '<select name="mllangid" size="1" >'."\n";

					$lresult = $profplug->getLanguages();
					while ($lrow = mysql_fetch_assoc($lresult)) {
						//if (!$profplug->templateExists($tid,$lrow['mllangid']) && !$lrow['mlnative']) {
						if (!$profplug->templateExists($tid,$lrow['mllangid'])) {
							echo '<option style="background:#ffffff url('.$lrow['mlflag'].') no-repeat right 0px" value="'.$lrow['mllangid'].'"';
							if ($lid == $lrow['mllangid'])
								echo " selected='selected'";
							echo '>'.$lrow['mllangname'].'</option>';
						}
					}

					echo "</select>";
				}
				echo "</td>";
				echo "<td>-</td><td>"._MULTILANGUAGE_ADMIN_HELP_TRANSLATED_LANGUAGE."</td></tr>\n";

				echo '<tr><td class="e">'.ucfirst(_MULTILANGUAGE_TEMPLATE_NAME).'</td><td class="v">';
				echo '<select name="mltempname" size="1" >'."\n";

				echo '<option value="0" '.($row['mltempname'] == '' ? ' selected>' :'>')._MULTILANGUAGE_ADMIN_TEMPLATES_SELECT.'</option>';
				$tres = sql_query("SELECT tdnumber,tdname FROM ".sql_table('template_desc'));
				while ($data = mysql_fetch_assoc($tres))
				{
					echo '<option value="'.$data['tdname'].'"';
					if ($row['mltempname'] == $data['tdname'])
								echo " selected='selected'";
					echo '>'.$data['tdname'].'</option>';

				}

					echo "</select>";
				echo "<td>".$orow['tdname']."</td><td>"._MULTILANGUAGE_ADMIN_HELP_TEMPNAME."</td></tr>\n";


				echo '<tr><td class="e"></td><td class="v"><input type="submit" value="'._MULTILANGUAGE_SUBMIT.'" />'."</td><td></td><td></td></tr>\n";
				echo "</table>\n";
				echo "</form>\n";
			}
			else echo "<br />"._MULTILANGUAGE_ADMIN_TEMPLATES_INVALID."<br />";
		}
		else echo "<br />"._MULTILANGUAGE_ADMIN_TEMPLATES_DENY."<br />";

		echo "</div>\n";
		echo $toplink;
	} // end edit template
	

/**************************************
 *	 Categories					      *
 **************************************/
	if ($showlist == "categories")
	{
		$sblog = set_bshow_if_single_blog();
//echo "<br /><b>$sblog</b>";
		if ($sblog && $member->blogAdminRights($sblog)) $bshow = $sblog;
		echo '<div class="center">'."\n";
		echo "<h2>"._MULTILANGUAGE_ADMIN_CATEGORIES_HEAD."</h2>\n";

		/**************************************
		 *      Blog Selector Form            *
		 **************************************/
		echo '<div class="center">'."\n";
		echo '<form name="blogChooser" method="post" action="'.$thispage.'?'.$thisquerystring.'">'."\n";
		echo '<h3>'._MULTILANGUAGE_SELECT_BLOG.' &nbsp;&nbsp;';
		echo '<select name="bshow" onChange="document.blogChooser.submit()">'."\n";
		echo '<option value="0" '.($bshow == '0' ? ' selected>' :'>')._MULTILANGUAGE_SELECT_BLOG.'</option>';
		$bres = sql_query("SELECT bnumber,bshortname FROM ".sql_table('blog'));
		while ($data = mysql_fetch_assoc($bres))
		{
			if ($member->blogAdminRights(intval($data['bnumber']))) {
				$menu .= '<option value="'.$data['bnumber'].'"';
				$menu .= ($data['bnumber'] == $bshow ? ' selected>' :'>');
				$menu .= $data['bshortname'].'</option>';
			}
		}
		echo $menu."\n";
		echo '</select><noscript><input type="submit" value="Go" class="formbutton" /></noscript></form>'."\n";
		echo '</h3></div>'."\n";


		if ($bshow && $member->blogAdminRights(intval($bshow))) {

			/**************************************
			 *      Category Selector Form        *
			 **************************************/
			$menu = '<div class="center">'."\n";
			$menu .= '<form name="catChooser" method="post" action="'.$thispage.'">'."\n";
			$menu .= '<h3>'._MULTILANGUAGE_ADMIN_CATEGORIES_ADD.' &nbsp;&nbsp;';
			$menu .= '<input type="hidden" name="showlist" value="editcategory" />'."\n";
			//$menu .= '<input type="hidden" name="cid" value="" />'."\n";
			$menu .= '<input type="hidden" name="lid" value="" />'."\n";
			$menu .= '<input type="hidden" name="bshow" value="'.$bshow.'" />'."\n";
			$menu .= '<select name="cid" onChange="document.catChooser.submit()">'."\n";
			$menu .= '<option value="0" '.($cid == '' ? ' selected>' :'>')._MULTILANGUAGE_ADMIN_CATEGORIES_ADD.'</option>';
			$bres = sql_query("SELECT catid,cname FROM ".sql_table('category')." WHERE cblog=$bshow");
			while ($data = mysql_fetch_assoc($bres))
			{
				$menu .= '<option value="'.$data['catid'].'">';
				$menu .= $data['cname'].'</option>';

			}
			$menu .= '</select><noscript><input type="submit" value="Go" class="formbutton" /></noscript></form>'."\n";
			$menu .= '</h3></div>'."\n";
			echo $menu."\n";

			//echo ' <a class="buttonlink" href="'.$thispage.'?showlist=editcategory&amp;cid=&amp;lid=&amp;bshow='.$bshow.'&amp;safe=true">'._MULTILANGUAGE_ADMIN_CATEGORIES_ADD.'</a>'."\n";
			if ($status){
				switch ($status) {
				case 1:
					echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_CATEGORIES_SUCCESS_ADD."</span>\n";
					break;
				case 2:
					echo "<span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_CATEGORIES_SUCCESS_UPD."</span>\n";
					break;
				case 3:
					echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_CATEGORIES_SUCCESS_DEL."</span>\n";
					break;
				default:
				}
			}
			$fieldres = $profplug->getCategories();

			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>".ucfirst(_MULTILANGUAGE_CATEGORY)."</th><th>".ucfirst(_MULTILANGUAGE_LANGUAGE)."</th><th>".ucfirst(_MULTILANGUAGE_CATEGORY_NAME)."</th><th>".ucfirst(_MULTILANGUAGE_CATEGORY_DESC)."</th><th>".ucfirst(_MULTILANGUAGE_ACTIONS)."</th></tr>\n";
			while ($row = mysql_fetch_assoc($fieldres)) {
				echo "<tr>\n";
				$origCatName = quickQuery("SELECT cname as result FROM ".sql_table('category')." WHERE catid=".$row['mlcatid']);
				echo '<td class="e">'.$row['mlcatid']." ($origCatName)</td>\n";
				echo '<td class="v">'.$profplug->getLanguageFromId($row['mllangid']).' : '. '<img src="'.$profplug->getFlagFromLanguageId($row['mllangid']).'" alt="flag" />'."</td>\n";
				echo '<td class="v">'.$row['mlcatname']."</td>\n";
				echo '<td class="v">'.$row['mlcatdesc']."</td>\n";
				echo '<td class="v">';
				echo '<a href="'.$thispage.'?showlist=editcategory&amp;cid='.$row['mlcatid'].'&amp;lid='.$row['mllangid'].'&amp;bshow='.$bshow.'&amp;safe=true">'._MULTILANGUAGE_EDIT.'</a> . ';
				echo '<a href="'.$thispage.'?showlist=deleteconfirm&amp;cidlang='.$row['mlcatid'].'_'.$row['mllangid'].'&amp;safe=true">'._MULTILANGUAGE_DELETE.'</a>';
				echo "</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
			echo "</div>\n";
			//echo $menu."\n";

			//echo '<p class="center"><a href="'.$thispage.'?showlist=editcategory&amp;cid=&amp;lid=&amp;bshow='.$bshow.'&amp;safe=true">-'._MULTILANGUAGE_ADMIN_CATEGORIES_ADD.'-</a></p>'."\n";
		}
		else echo "<br />"._MULTILANGUAGE_ADMIN_CATEGORIES_DENY."<br /></div>\n";
		echo $toplink;
	} // end fields

/**************************************
 *	 Edit Category			      *
 **************************************/
	if ($showlist == "editcategory")
	{
		echo '<div class="center">'."\n";
		echo "<h2>"._MULTILANGUAGE_ADMIN_CATEGORY_EDIT_HEAD."</h2>\n";
		if ($bshow && $member->blogAdminRights(intval($bshow))) {
			$lid = intval(requestVar('lid'));
			$cid = intval(requestVar('cid'));
			$bid = intval(requestVar('bshow'));

			$oquery = "SELECT cname,cdesc FROM ".sql_table('category')." WHERE catid=$cid";
//echo "<hr /><span style=\"padding-left:150px\">$oquery</span><hr />";
			$ores = sql_query($oquery);

			if (mysql_num_rows($ores) == 1) {
				$orow = mysql_fetch_assoc($ores);
				if ($profplug->categoryExists($cid,$lid)) {
					$langres = $profplug->getCategoryDef($cid,$lid);
					$row = mysql_fetch_assoc($langres);
					$acttype = 'updatecategory';
				}
				else {
					$lid = 0;
					$row = array('mlcatid'=>$cid,'mllangid'=>0,'mlcatname'=>'','mlcatdesc'=>'');
					$acttype = 'addcategory';
				}

				if ($status){
					switch ($status) {
					case 1:
						echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_CATEGORIES_SUCCESS_ADD."</span>\n";
						break;
					case 2:
						echo "<span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_CATEGORIES_SUCCESS_UPD."</span>\n";
						break;
					case 3:
						echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_CATEGORIES_SUCCESS_DEL."</span>\n";
						break;
					default:
					}
				}

				echo '<form method="post" action="'.$action_url.'">'."\n";
				echo '<input type="hidden" name="action" value="plugin" />'."\n";
				echo '<input type="hidden" name="name" value="MultiLanguage" />'."\n";
				echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
				echo '<input type="hidden" name="mlcatid" value="'.$cid.'" />'."\n";
				echo '<input type="hidden" name="bshow" value="'.$bshow.'" />'."\n";

				$manager->addTicketHidden();
				echo '<table border="0" cellpadding="3" width="600">'."\n";
				echo "<tr class=\"h\">\n";
				echo "<th>".ucfirst(_MULTILANGUAGE_PARAMETER)."</th><th>".ucfirst(_MULTILANGUAGE_VALUE)."</th><th>".ucfirst(_MULTILANGUAGE_ORIGINAL_VALUE)."</th><th>".ucfirst(_MULTILANGUAGE_HELP)."</th></tr>\n";

				echo '<tr><td class="e">'.ucfirst(_MULTILANGUAGE_LANGUAGE).'</td><td class="v">';
				if ($lid > 0) {
					echo '<input type="hidden" name="mllangid" value="'.$lid.'" />'.$profplug->getLanguageFromId($lid)."\n";
				}
				else{
					echo '<select name="mllangid" size="1" >'."\n";

					$lresult = $profplug->getLanguages();
					while ($lrow = mysql_fetch_assoc($lresult)) {
						if (!$profplug->categoryExists($cid,$lrow['mllangid']) && !$lrow['mlnative']) {
							echo '<option style="background:#ffffff url('.$lrow['mlflag'].') no-repeat right 0px" value="'.$lrow['mllangid'].'"';
							if ($lid == $lrow['mllangid'])
								echo " selected='selected'";
							echo '>'.$lrow['mllangname'].'</option>';
						}
					}

					echo "</select>";
				}
				echo "</td>";
				echo "<td>-</td><td>"._MULTILANGUAGE_ADMIN_HELP_TRANSLATED_LANGUAGE."</td></tr>\n";

				echo '<tr><td class="e">'.ucfirst(_MULTILANGUAGE_CATEGORY_NAME).'</td><td class="v"><input size="60" name="mlcatname" value="'.$row['mlcatname'].'" />'."</td>";
				echo "<td>".$orow['cname']."</td><td>"._MULTILANGUAGE_ADMIN_HELP_CATNAME."</td></tr>\n";
				echo '<tr><td class="e">'.ucfirst(_MULTILANGUAGE_CATEGORY_DESC).'</td><td class="v"><input size="60" name="mlcatdesc" value="'.$row['mlcatdesc'].'" />'."</td>";
				echo "<td>".$orow['cdesc']."</td><td>"._MULTILANGUAGE_ADMIN_HELP_CATDESC."</td></tr>\n";


				echo '<tr><td class="e"></td><td class="v"><input type="submit" value="'._MULTILANGUAGE_SUBMIT.'" />'."</td><td></td><td></td></tr>\n";
				echo "</table>\n";
				echo "</form>\n";
			}
			else echo "<br />"._MULTILANGUAGE_ADMIN_CATEGORIES_INVALID."<br />";
		}
		else echo "<br />"._MULTILANGUAGE_ADMIN_CATEGORIES_DENY."<br />";

		echo "</div>\n";
		echo $toplink;
	} // end edit category

/**************************************
 *	 Items					      *
 **************************************/
	if ($showlist == "items")
	{
		$sblog = set_bshow_if_single_blog();
//echo "<br /><b>$sblog</b>";
		if ($sblog && $member->teamRights($sblog)) $bshow = $sblog;
		echo '<div class="center">'."\n";
		echo "<h2>"._MULTILANGUAGE_ADMIN_ITEMS_HEAD."</h2>\n";

		/**************************************
		 *      Blog Selector Form            *
		 **************************************/
		echo '<div class="center">'."\n";
		echo '<form name="blogChooser" method="post" action="'.$thispage.'?'.$thisquerystring.'">'."\n";
		echo '<h3>'._MULTILANGUAGE_SELECT_BLOG.' &nbsp;&nbsp;';
		echo '<select name="bshow" onChange="document.blogChooser.submit()">'."\n";
		echo '<option value="0" '.($bshow == '0' ? ' selected>' :'>')._MULTILANGUAGE_SELECT_BLOG.'</option>';
		$bres = sql_query("SELECT bnumber,bshortname FROM ".sql_table('blog'));
		while ($data = mysql_fetch_assoc($bres))
		{
			if ($member->teamRights(intval($data['bnumber']))) {
				$menu .= '<option value="'.$data['bnumber'].'"';
				$menu .= ($data['bnumber'] == $bshow ? ' selected>' :'>');
				$menu .= $data['bshortname'].'</option>';
			}
		}
		echo $menu."\n";
		echo '</select><noscript><input type="submit" value="Go" class="formbutton" /></noscript></form>'."\n";
		echo '</h3></div>'."\n";


		if ($bshow && $member->teamRights(intval($bshow))) {

			/**************************************
			 *      Item Selector Form            *
			 **************************************/
			$menu = '<div class="center">'."\n";
			$menu .= '<form name="itemChooser" method="post" action="'.$thispage.'">'."\n";
			$menu .= '<h3>'._MULTILANGUAGE_ADMIN_ITEMS_ADD.' &nbsp;&nbsp;';
			$menu .= '<input type="hidden" name="showlist" value="edititem" />'."\n";
			//$menu .= '<input type="hidden" name="cid" value="" />'."\n";
			$menu .= '<input type="hidden" name="lid" value="" />'."\n";
			$menu .= '<input type="hidden" name="bshow" value="'.$bshow.'" />'."\n";
			$menu .= '<select name="iid" onChange="document.itemChooser.submit()">'."\n";
			$menu .= '<option value="0" '.($iid == '' ? ' selected>' :'>')._MULTILANGUAGE_ADMIN_ITEMS_ADD.'</option>';
			$ires = sql_query("SELECT inumber,ititle FROM ".sql_table('item')." WHERE iblog=$bshow ORDER BY inumber DESC");
			while ($data = mysql_fetch_assoc($ires))
			{
				$menu .= '<option value="'.$data['inumber'].'">';
				$menu .= $data['ititle'].'</option>';

			}
			$menu .= '</select><noscript><input type="submit" value="Go" class="formbutton" /></noscript></form>'."\n";
			$menu .= '</h3></div>'."\n";
			echo $menu."\n";

			if ($status){
				switch ($status) {
				case 1:
					echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_ITEMS_SUCCESS_ADD."</span>\n";
					break;
				case 2:
					echo "<span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_ITEMS_SUCCESS_UPD."</span>\n";
					break;
				case 3:
					echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_ITEMS_SUCCESS_DEL."</span>\n";
					break;
				default:
				}
			}
			$fieldres = $profplug->getItems();

			echo '<table border="0" cellpadding="3" width="600">'."\n";
			echo "<tr class=\"h\">\n";
			echo "<th>".ucfirst(_MULTILANGUAGE_ITEM)."</th><th>".ucfirst(_MULTILANGUAGE_LANGUAGE)."</th><th>".ucfirst(_MULTILANGUAGE_ITEM_TITLE)."</th><th>".ucfirst(_MULTILANGUAGE_ACTIONS)."</th></tr>\n";
			while ($row = mysql_fetch_assoc($fieldres)) {
				echo "<tr>\n";
				echo '<td class="e">'.$row['mlitemid']."</td>\n";
				echo '<td class="v">'.$profplug->getLanguageFromId($row['mllangid']).' : '. '<img src="'.$profplug->getFlagFromLanguageId($row['mllangid']).'" alt="flag" />'."</td>\n";
				echo '<td class="v">'.$row['mltitle']."</td>\n";
				echo '<td class="v">';
				echo '<a href="'.$thispage.'?showlist=edititem&amp;iid='.$row['mlitemid'].'&amp;lid='.$row['mllangid'].'&amp;bshow='.$bshow.'&amp;safe=true">'._MULTILANGUAGE_EDIT.'</a> . ';
				echo '<a href="'.$thispage.'?showlist=deleteconfirm&amp;iidlang='.$row['mlitemid'].'_'.$row['mllangid'].'&amp;safe=true">'._MULTILANGUAGE_DELETE.'</a>';
				echo "</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
			echo "</div>\n";
			//echo $menu."\n";

			//echo '<p class="center"><a href="'.$thispage.'?showlist=editcategory&amp;cid=&amp;lid=&amp;bshow='.$bshow.'&amp;safe=true">-'._MULTILANGUAGE_ADMIN_CATEGORIES_ADD.'-</a></p>'."\n";
		}
		else echo "<br />"._MULTILANGUAGE_ADMIN_ITEMS_DENY."<br /></div>\n";
		echo $toplink;
	} // end fields

/**************************************
 *	 Edit Item      			      *
 **************************************/
	if ($showlist == "edititem")
	{
		echo '<div class="center">'."\n";
		echo "<h2>"._MULTILANGUAGE_ADMIN_ITEM_EDIT_HEAD."</h2>\n";
//		if ($bshow && $member->blogAdminRights(intval($bshow))) {
		if ($bshow && $member->teamRights(intval($bshow))) {
			$lid = intval(requestVar('lid'));
			$iid = intval(requestVar('iid'));
			$bid = intval(requestVar('bshow'));

			$oquery = "SELECT ititle,ibody,imore FROM ".sql_table('item')." WHERE inumber=$iid";
//echo "<hr /><span style=\"padding-left:150px\">$oquery</span><hr />";
			$ores = sql_query($oquery);

			if (mysql_num_rows($ores) == 1) {
				$orow = mysql_fetch_assoc($ores);
				if ($profplug->itemExists($iid,$lid)) {
					$langres = $profplug->getItemDef($iid,$lid);
					$row = mysql_fetch_assoc($langres);
					$acttype = 'itemupdate';
				}
				else {
					//$lid = 0;
					$row = array('mlitemid'=>$iid,'mllangid'=>$lid,'mlauthorid'=>$member->getId(),'mltitle'=>'','mlbody'=>'','mlmore'=>'');
					$acttype = 'additem';
				}

				if ($status){
					switch ($status) {
					case 1:
						echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_ITEMS_SUCCESS_ADD."</span>\n";
						break;
					case 2:
						echo "<span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_ITEMS_SUCCESS_UPD."</span>\n";
						break;
					case 3:
						echo " <span style=\"color:blue\">"._MULTILANGUAGE_ADMIN_ITEMS_SUCCESS_DEL."</span>\n";
						break;
					default:
					}
				}

				if ($acttype == 'additem' && !$profplug->languageExists($lid)) {

					/**************************************
					 *      Language Selector Form        *
					 **************************************/
					$menu = '<div class="center">'."\n";
					$menu .= '<form name="langChooser" method="post" action="'.$thispage.'">'."\n";
					$menu .= '<h3>'._MULTILANGUAGE_ADMIN_CHOOSE_TRANSLATED_LANGUAGE.' &nbsp;&nbsp;';
					$menu .= '<input type="hidden" name="showlist" value="edititem" />'."\n";
					//$menu .= '<input type="hidden" name="cid" value="" />'."\n";
					$menu .= '<input type="hidden" name="iid" value="'.$iid.'" />'."\n";
					$menu .= '<input type="hidden" name="bshow" value="'.$bshow.'" />'."\n";
					$menu .= '<select name="lid" onChange="document.langChooser.submit()">'."\n";
					$menu .= '<option value="0" '.($lid == '' ? ' selected>' :'>')._MULTILANGUAGE_ADMIN_CHOOSE_TRANSLATED_LANGUAGE.'</option>';
					$lresult = $profplug->getLanguages();
					while ($lrow = mysql_fetch_assoc($lresult))
					{
						if (!$profplug->itemExists($iid,$lrow['mllangid']) && !$lrow['mlnative']) {
							$menu .= '<option style="background:#ffffff url('.$lrow['mlflag'].') no-repeat right 0px" value="'.$lrow['mllangid'].'"';
							if ($lid == $lrow['mllangid'])
								$menu .= " selected='selected'";
							$menu .= '>'.$lrow['mllangname'].'</option>';
						}
					}
					$menu .= '</select><noscript><input type="submit" value="Go" class="formbutton" /></noscript></form>'."\n";
					$menu .= '</h3></div>'."\n";
					$menu .= _MULTILANGUAGE_ORIGINAL_TITLE.": ".htmlentities($orow['ititle'])."<br />\n";
					echo $menu."\n";

				}
				else {
					include_once($DIR_PLUGINS."multilanguage/ML_PAGEFACTORY.php");
					if ($acttype == 'additem') {

						$memberid = $member->getID();

						//$blog =& $manager->getBlog($bshow);
						$item =& $manager->getItem($iid,1,1);
						$blog =& $manager->getBlog(getBlogIDFromItemID($iid));
/*
						$contents = array(
										'mlitemid'=>$iid,
										'mllangid'=>$lid,
										'mltitle'=>'',
										'mlbody'=>'',
										'mlmore'=>'',
										'mlauthorid'=>$memberid
									);
*/
						$item['mlitemid'] = $row['mlitemid'];
						$item['mllangid'] = $row['mllangid'];
						$item['mltitle'] = '';
						$item['mlbody'] = '';
						$item['mlmore'] = '';
						$item['mlauthorid'] = $row['mlauthorid'];
						$item['mllanguage'] = $profplug->getLanguageFromId($row['mllangid']);
						$item['mlflag'] = $profplug->getFlagFromLanguageId($row['mllangid']);
						$item['actionurl'] = $CONF['ActionURL'];

						// generate the add-item form
						$ml_formfactory =& new ML_PAGEFACTORY($bshow);
						$ml_formfactory->createAddForm('admin',$item);
					}
					elseif ($acttype == 'itemupdate') {
						$item =& $manager->getItem($iid,1,1);
						$blog =& $manager->getBlog(getBlogIDFromItemID($iid));

						//$manager->notify('PrepareItemForEdit', array('item' => &$item));

						$item['mlitemid'] = $row['mlitemid'];
						$item['mllangid'] = $row['mllangid'];
						$item['mltitle'] = $row['mltitle'];
						$item['mlbody'] = $row['mlbody'];
						$item['mlmore'] = $row['mlmore'];
						$item['mlauthorid'] = $row['mlauthorid'];
						$item['mllanguage'] = $profplug->getLanguageFromId($row['mllangid']);
						$item['mlflag'] = $profplug->getFlagFromLanguageId($row['mllangid']);
						$item['actionurl'] = $CONF['ActionURL'];

						if ($blog->convertBreaks()) {
							$item['mlbody'] = removeBreaks($item['mlbody']);
							$item['mlmore'] = removeBreaks($item['mlmore']);
						}

						// form to edit blog items
						$ml_formfactory =& new ML_PAGEFACTORY($blog->getID());
						$ml_formfactory->createEditForm('admin',$item);
					}
				}
			}
			else echo "<br />"._MULTILANGUAGE_ADMIN_ITEMS_INVALID."<br />";
		}
		else echo "<br />"._MULTILANGUAGE_ADMIN_ITEMS_DENY."<br />";

		echo "</div>\n";
		echo $toplink;
	} // end edit category


/**************************************
 *	 Confirm Deletions  		      *
 **************************************/
	if ($showlist == "deleteconfirm") {
		$lid = intval(requestVar('lid'));
		$cidlang = trim(requestVar('cidlang'));
		$iidlang = trim(requestVar('iidlang'));
		$tidlang = trim(requestVar('tidlang'));
		echo '<div class="center">'."\n";
		if ($lid > 0) {
			$acttype = 'deletelanguage';


			echo "<h2>"._MULTILANGUAGE_ADMIN_LANGUAGE_DELETE_HEAD."</h2>\n";

			echo '<form method="post" action="'.$action_url.'">'."\n";
			echo '<input type="hidden" name="action" value="plugin" />'."\n";
			echo '<input type="hidden" name="name" value="MultiLanguage" />'."\n";
			echo '<input type="hidden" name="mllangid" value="'.$lid.'" />'."\n";
			echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
			$manager->addTicketHidden();
			echo _MULTILANGUAGE_ADMIN_DELETE_OPEN."<br /><br />";
//			echo _MULTILANGUAGE_ADMIN_DELETE_BODY1."<br />\n";
//			echo _MULTILANGUAGE_ADMIN_DELETE_BODY2."<br /><br />\n";
//			echo _MULTILANGUAGE_ADMIN_DELETE_CONFIRM."<br /><br />\n";
			echo '<input type="submit" value="'._MULTILANGUAGE_YES.'" />';
			echo '<br /><br /><a href="'.$thispage.'?showlist=languages&amp;safe=true">'._MULTILANGUAGE_ADMIN_DELETE_RETURN.'</a>'."\n";
			echo "</form>\n";
		}

		if ($tidlang != '') {
			$acttype = 'deletetemplate';
			list($tid,$lid) = explode("_",$tidlang);


			echo "<h2>"._MULTILANGUAGE_ADMIN_TEMPLATE_DELETE_HEAD."</h2>\n";

			echo '<form method="post" action="'.$action_url.'">'."\n";
			echo '<input type="hidden" name="action" value="plugin" />'."\n";
			echo '<input type="hidden" name="name" value="MultiLanguage" />'."\n";
			echo '<input type="hidden" name="mllangid" value="'.$lid.'" />'."\n";
			echo '<input type="hidden" name="mltempid" value="'.$tid.'" />'."\n";
			echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
			$manager->addTicketHidden();
			echo _MULTILANGUAGE_ADMIN_DELETE_OPEN_TEMPLATE."<br /><br />";
			echo '<input type="submit" value="'._MULTILANGUAGE_YES.'" />';
			echo '<br /><br /><a href="'.$thispage.'?showlist=templates&amp;safe=true">'._MULTILANGUAGE_ADMIN_DELETE_RETURN.'</a>'."\n";
			echo "</form>\n";
		}
		
		if ($cidlang != '') {
			$acttype = 'deletecategory';
			list($cid,$lid) = explode("_",$cidlang);


			echo "<h2>"._MULTILANGUAGE_ADMIN_CATEGORY_DELETE_HEAD."</h2>\n";

			echo '<form method="post" action="'.$action_url.'">'."\n";
			echo '<input type="hidden" name="action" value="plugin" />'."\n";
			echo '<input type="hidden" name="name" value="MultiLanguage" />'."\n";
			echo '<input type="hidden" name="mllangid" value="'.$lid.'" />'."\n";
			echo '<input type="hidden" name="mlcatid" value="'.$cid.'" />'."\n";
			echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
			$manager->addTicketHidden();
			echo _MULTILANGUAGE_ADMIN_DELETE_OPEN_CATEGORY."<br /><br />";
			echo '<input type="submit" value="'._MULTILANGUAGE_YES.'" />';
			echo '<br /><br /><a href="'.$thispage.'?showlist=categories&amp;safe=true">'._MULTILANGUAGE_ADMIN_DELETE_RETURN.'</a>'."\n";
			echo "</form>\n";
		}

		if ($iidlang != '') {
			$acttype = 'deleteitem';
			list($iid,$lid) = explode("_",$iidlang);


			echo "<h2>"._MULTILANGUAGE_ADMIN_ITEM_DELETE_HEAD."</h2>\n";

			echo '<form method="post" action="'.$action_url.'">'."\n";
			echo '<input type="hidden" name="action" value="plugin" />'."\n";
			echo '<input type="hidden" name="name" value="MultiLanguage" />'."\n";
			echo '<input type="hidden" name="mllangid" value="'.$lid.'" />'."\n";
			echo '<input type="hidden" name="mlitemid" value="'.$iid.'" />'."\n";
			echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
			$manager->addTicketHidden();
			echo _MULTILANGUAGE_ADMIN_DELETE_OPEN_ITEM."<br /><br />";
			echo '<input type="submit" value="'._MULTILANGUAGE_YES.'" />';
			echo '<br /><br /><a href="'.$thispage.'?showlist=items&amp;safe=true">'._MULTILANGUAGE_ADMIN_DELETE_RETURN.'</a>'."\n";
			echo "</form>\n";
		}

		echo "</div>\n";
	}



// close page
	echo "</div>\n";
	$oPluginAdmin->end();
	
	function set_bshow_if_single_blog() {
		$singleblog = 0;
		$result = sql_query("SELECT bnumber FROM ".sql_table('blog'));
		$obj = mysql_fetch_object($result);
		if (mysql_num_rows($result) == 1) $singleblog = intval($obj->bnumber);
		return $singleblog;
	}
?>