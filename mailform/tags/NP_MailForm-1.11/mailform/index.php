<?php
/* This is the Admin Area page for the NP_MailForm Plugin.
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
	$plugname = "NP_MailForm";

	include($strRel . 'config.php');
	if (!$member->isAdmin())
		doError("You cannot access this area.");

	include($DIR_LIBS . 'PLUGINADMIN.php');

	global $CONF,$manager;

	//$manager->checkTicket();
	$action_url = $CONF['ActionURL'];
	$thispage = $CONF['PluginURL'] . "mailform/index.php";
	$adminpage = $CONF['AdminURL'];
	$admin = $member->isAdmin();
	$thisquerystring = serverVar('QUERY_STRING');
	$showlist = strtolower(trim(requestVar('showlist')));
	if (!in_array($showlist, array('forms','editform','deleteconfirm'))) $showlist = 'forms';
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

.npmailform
{
padding: 3px 0;
margin-left: 0;
border-bottom: 1px solid #778;
}

.npmailform table {border-collapse: collapse;}
.npmailform .center {text-align: center;}
.npmailform .center table { margin-left: auto; margin-right: auto; text-align: left;}
.npmailform .center th { text-align: center !important; }
.npmailform td, .npmailform th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
.npmailform h1 {font-size: 150%; text-align:left;}
.npmailform h2 {font-size: 125%;}
.npmailform .p {text-align: left;}
.npmailform .e {background-color: #ccccff; font-weight: bold; color: #000000;}
.npmailform .h {background-color: #9999cc; font-weight: bold; color: #000000;}
.npmailform .v {background-color: #cccccc; color: #000000;}
.npmailform .vr {background-color: #cccccc; text-align: right; color: #000000;}
.npmailform hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>';

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('MailForm');
	$oPluginAdmin->start($newhead);

	$plugin =& $oPluginAdmin->plugin;
	$pid = $plugin->getID();
	$toplink = '<p class="center"><a href="'.$thispage.'?'.$thisquerystring.'#sitop" alt="Return to Top of Page">-TOP-</a></p>'."\n";

/**************************************
 *       Edit Options Link            *
 **************************************/
	echo "\n<div>\n";
	echo '<a name="sitop"></a>'."\n";
	echo '<a class="buttonlink" href="'.$adminpage.'?action=pluginoptions&amp;plugid='.$pid.'">Plugin Options</a>'."\n";
	echo "</div>\n";

/**************************************
 *        Header                      *
 **************************************/
	
    $helplink = ' <a href="'.$adminpage.'?action=pluginhelp&amp;plugid='.$pid.'"><img src="'.$CONF['PluginURL'].'mailform/help.jpg" alt="help" title="help" /></a>';
	echo '<h2 style="padding-top:10px;">NP_MailForm'.$helplink.'</h2>'."\n";
    

/**************************************
 *       function chooser links       *
 **************************************/
	echo '<div class="npmailform">'."\n";
	echo "<div>\n";
	echo '<ul class="navlist">'."\n";
	echo ' <li><a class="'.($showlist == 'forms' ? 'current' : '').'" href="'.$thispage.'?showlist=forms&amp;safe=true">Forms</a></li> '."\n";
	/*
    echo ' <li><a class="'.($showlist == 'types' ? 'current' : '').'" href="'.$thispage.'?showlist=types&amp;safe=true">'._PROFILE_ADMIN_FIELD_TYPE.'</a></li>'."\n";
	echo ' <li><a class="'.($showlist == 'config' ? 'current' : '').'" href="'.$thispage.'?showlist=config&amp;safe=true">'._PROFILE_ADMIN_CONFIG.'</a></li>'."\n";
	echo ' <li><a class="'.($showlist == 'example' ? 'current' : '').'" href="'.$thispage.'?showlist=example&amp;safe=true">'._PROFILE_ADMIN_EXAMPLE.'</a></li>'."\n";
	*/
    echo " </ul></div>\n";

/**************************************
 *	 Form Defs					      *
 **************************************/
	if ($showlist == "forms" || $showlist == NULL)
	{
		echo '<div class="center">'."\n";
		echo "<h2>Defined Forms</h2>\n";
		echo ' <a class="buttonlink" href="'.$thispage.'?showlist=editform&amp;formname=&amp;safe=true">Add Form</a>'."\n";
		if ($status){
			switch ($status) {
			case 1:
				echo " <span style=\"color:blue\">Form Successfully Added</span>\n";
				break;
			case 2:
				echo "<span style=\"color:blue\">Form Successfully Updated</span>\n";
				break;
			case 3:
				echo " <span style=\"color:blue\">Form Successfully Deleted</span>\n";
				break;
			default:
			}
		}
		$formres = $plugin->getFormDef();

		echo '<table border="0" cellpadding="3" width="600">'."\n";
		echo "<tr class=\"h\">\n";
		echo "<th>Form</th><th>Subject</th><th>Mail To</th><th>Mail From</th><th>Required</th><th>Actions</th></tr>\n";
		while ($row = mysql_fetch_assoc($formres)) {
			echo "<tr>\n";
			echo '<td class="e">'.$row['formname']."</td>\n";
			echo '<td class="v">'.$row['subject']."</td>\n";
			echo '<td class="v">'.$row['mailto']."</td>\n";
			echo '<td class="v">'.$row['mailfrom']."</td>\n";
			echo '<td class="v">'.$row['required']."</td>\n";
			echo '<td class="v">';
			echo '<a href="'.$thispage.'?showlist=editform&amp;formname='.$row['formname'].'&amp;safe=true">Edit</a> . ';
			echo '<a href="'.$thispage.'?showlist=deleteconfirm&amp;formname='.$row['formname'].'&amp;safe=true">Delete</a>';
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "</div>\n";
		echo '<p class="center"><a href="'.$thispage.'?showlist=editform&amp;formname=&amp;safe=true">-Add Form-</a></p>'."\n";
		echo $toplink;
	} // end fields

/**************************************
 *	 Edit Form Defs			      *
 **************************************/
	if ($showlist == "editform")
	{
		$formname = requestVar('formname');
		if ($plugin->formExists($formname)) {
			$formres = $plugin->getFormDef($formname);
			$row = mysql_fetch_assoc($formres);
			$oformname = $formname;
			$acttype = 'updateform';
		}
		else {
			$formname = '';
			$oformname = $formname;
			$row = array('formname'=>'','subject'=>'','mailfrom'=>'','mailto'=>'','required'=>'','filesize'=>'-1','filetype'=>'','statustext'=>'<span style="color:red;font-weight:bold">Thank you for your request. It has been successfully submitted.</span>');
			$acttype = 'addform';
		}
		echo '<div class="center">'."\n";
		echo "<h2>Edit Form</h2>\n";
		if ($status){
			switch ($status) {
			case 1:
				echo " <span style=\"color:blue\">Form Successfully Added</span>\n";
				break;
			case 2:
				echo "<span style=\"color:blue\">Form Successfully Updated</span>\n";
				break;
			case 3:
				echo " <span style=\"color:blue\">Form Successfully Deleted</span>\n";
				break;
			default:
			}
		}

		echo '<form method="post" action="'.$action_url.'">'."\n";
        echo '<input type="hidden" name="action" value="plugin" />'."\n";
        echo '<input type="hidden" name="name" value="MailForm" />'."\n";
        echo '<input type="hidden" name="oformname" value="'.$oformname.'" />'."\n";
		$manager->addTicketHidden();
		echo '<table border="0" cellpadding="3" width="600">'."\n";
		echo "<tr class=\"h\">\n";
		echo "<th>Parameter</th><th>Value</th><th>Help</th></tr>\n";
		echo '<tr><td class="e">Form Name</td><td class="v"><input size="60" name="formname" value="'.$row['formname'].'" />'."</td>";
		echo "<td>Enter name of form. This must be included in the form as a hidden field called formname.</td></tr>\n";
		echo '<tr><td class="e">Subject</td><td class="v"><input size="60" name="subject" value="'.$row['subject'].'" />'."</td>";
		echo "<td>The subject line used in email.</td></tr>\n";
		echo '<tr><td class="e">From Address</td><td class="v"><input size="60" name="mailfrom" value="'.$row['mailfrom'].'" />'."</td>";
		echo "<td>The from address used in email. Best if this is from same domain as site. Leave blank to use the site AdminEmail, or enter a valid email address to use that, or type in the name of a field in your form that will contain a user-entered email. Note some hosting providers may block email from unknown domains, so the last option may not work.";
//		echo " Advanced. To have a ReplyTo address different from the From address, insert a semi-colon after the From address and enter the ReplyTo address after it. ";
//		echo " The same rules apply. For example, <code>webmaster@mydomain.com;EmailAddress</code> would send the mail with a from address of webmaster@mydomain.com and a ReplyTo address of what the user entered in the EmailAddress field of the form.";
		echo "</td></tr>\n";
//		echo '<tr><td class="e">Reply To Address</td><td class="v"><input size="60" name="mailreply" value="'.$row['mailreply'].'" />'."</td>";
//		echo "<td>The reply to address used in email. Leave blank to use the From Address above, or enter a valid email address to use that, or type in the name of a field in your form that will contain a user-entered email .</td></tr>\n";
		echo '<tr><td class="e">Recipients</td><td class="v"><input size="60" name="mailto" value="'.$row['mailto'].'" />'."</td>";
		echo "<td>Recipipients of mailed form. Include multiple recipients as comma separated list (no spaces).</td></tr>\n";
		echo '<tr><td class="e">Required Fields</td><td class="v"><input size="60" name="required" value="'.$row['required'].'" />'."</td>";
		echo "<td>Comma separated list (no spaces, match case) of fields that are required.</td></tr>\n";
		echo '<tr><td class="e">Max File Size</td><td class="v"><input size="60" name="filesize" value="'.$row['filesize'].'" />'."</td>";
		echo "<td>The max size in bytes of uploaded files. 0 means use Nucleus Global Setting. -1 means disable file uploads for this form.</td></tr>\n";
		echo '<tr><td class="e">Allowed File Types</td><td class="v"><input size="60" name="filetype" value="'.$row['filetype'].'" />'."</td>";
		echo "<td>Permitted file types. Comma separated (no spaces) list of extensions (no dots) permitted. Blank means use Nucleus Global Setting.</td></tr>\n";
		echo '<tr><td class="e">Enable Sticket</td><td class="v">';
		echo '<input size="60" type="radio" name="sticket" value="1"'.($row['sticket'] >= '1' ? ' checked="checked"' : '').' />Yes ';
		echo '<input size="60" type="radio" name="sticket" value="0"'.($row['sticket'] == '0' ? ' checked="checked"' : '').' />No ';
		echo "</td><td>Require form submission to have valid sticket. Spam prevention. Must put &lt;%MailForm(sticket)%&gt; in form.</td></tr>\n";
        echo '<tr><td class="e">Enable Captcha</td><td class="v">';
		echo '<input size="60" type="radio" name="captcha" value="1"'.($row['captcha'] >= '1' ? ' checked="checked"' : '').' />Yes ';
		echo '<input size="60" type="radio" name="captcha" value="0"'.($row['captcha'] == '0' ? ' checked="checked"' : '').' />No ';
		echo "</td><td>Require form submission to have valid captcha key. Spam prevention. Must put &lt;%MailForm(captcha)%&gt; in form.</td></tr>\n";
        echo '<tr><td class="e">Enable SpamCheck API</td><td class="v">';
		echo '<input size="60" type="radio" name="spamcheck" value="1"'.($row['spamcheck'] >= '1' ? ' checked="checked"' : '').' />Yes ';
		echo '<input size="60" type="radio" name="spamcheck" value="0"'.($row['spamcheck'] == '0' ? ' checked="checked"' : '').' />No ';
		echo "</td><td>Whether to pass submissions through the Nucleus SpamCheck API for spam checking by plugins. Requires that the Spam Check Body field(s) be specified below..</td></tr>\n";
		echo '<tr><td class="e">Spam Check Body Fields</td><td class="v"><input size="60" name="spamcheckbody" value="'.$row['spamcheckbody'].'" />'."</td>";
		echo "<td>Comma separated list (no spaces, match case) of fields that are will be checked for spam characteritics by the SpamCheck API.</td></tr>\n";
		echo '<tr><td class="e">Multi-line Fields</td><td class="v"><input size="60" name="mlinefields" value="'.$row['mlinefields'].'" />'."</td>";
		echo "<td>Comma separated list (no spaces, match case) of fields that accept multiline input, i.e. textarea fields.</td></tr>\n";
		echo '<tr><td class="e">Multi-line End Tag</td><td class="v"><input size="60" name="mlineendtag" value="'.$row['mlineendtag'].'" />'."</td>";
		echo "<td>This string will be inserted after the last line of data for multiline fields. Useful for processing fields in external program.</td></tr>\n";
        echo '<tr><td class="e">Body Start Tag</td><td class="v"><input size="60" name="bodystarttag" value="'.$row['bodystarttag'].'" />'."</td>";
		echo "<td>This string will be inserted after the header and before the form fields in the email. Useful for processing fields in external program.</td></tr>\n";
        echo '<tr><td class="e">Body End Tag</td><td class="v"><input size="60" name="bodyendtag" value="'.$row['bodyendtag'].'" />'."</td>";
		echo "<td>This string will be inserted after the form fields in the email. Useful for processing fields in external program.</td></tr>\n";
        echo '<tr><td class="e">Field Prefix</td><td class="v"><input size="60" name="fieldprefix" value="'.$row['fieldprefix'].'" />'."</td>";
		echo "<td>This string will be inserted before each field label (field label is fieldname:). Useful for processing fields in external program.</td></tr>\n";
        echo '<tr><td class="e">Redirect URL</td><td class="v"><input size="60" name="desturl" value="'.$row['desturl'].'" />'."</td>";
		echo "<td>This string will be the landing page after a user submits a form. Should be a full url including the http://. If left blank (the default) the user will be directed to the form's page upon submitting.</td></tr>\n";
        echo '<tr><td class="e">Status Text</td><td class="v"><input size="75" name="statustext" value="'.htmlspecialchars($row['statustext']).'" />'."</td>";
		echo "<td>This string will be the status message shown after a user submits a form. If you want any color or styling, include the html tags to give this styling. If no text given here, a default message will be used.</td></tr>\n";
		//
		echo '<tr><td class="e">Form Body:</td><td class="v"><textarea name="formbody" cols=40 rows=20>'.$row['formbody'].'</textarea></td>';
		echo "<td>Enter the html and MailForm tags here to create your form. In skins or posts, call the entire form by using &lt;%MailForm(form,<i>formname</i>)%&gt;.</td></tr>\n";
		//
		echo '<tr><td class="e">Action</td><td class="v"><select name="type" size="1" >'."\n";
		echo '<option value="updateform" '.($acttype == 'updateform' ? 'selected="selected"' : '').'>Update Form</option>' . "\n";
		echo '<option value="addform" '.($acttype == 'addform' ? 'selected="selected"' : '').'>Add Form</option>' . "\n";
        echo "</select></td><td>Choose action to perform.</td></tr>\n";
		echo '<tr><td class="e"></td><td class="v"><input type="submit" value="Submit" />'."</td><td></td></tr>\n";
		echo "</table>\n";
		echo "</form>\n";
		echo "</div>\n";
		echo $toplink;
	} // end edit field

/**************************************
 *	 Confirm Field Delete		      *
 **************************************/
	if ($showlist == "deleteconfirm") {
		$formname = requestVar('formname');
		$acttype = 'deleteform';

		echo '<div class="center">'."\n";
		echo "<h2>Delete Form</h2>\n";

		echo '<form method="post" action="'.$action_url.'">'."\n";
        echo '<input type="hidden" name="action" value="plugin" />'."\n";
        echo '<input type="hidden" name="name" value="MailForm" />'."\n";
        echo '<input type="hidden" name="formname" value="'.$formname.'" />'."\n";
		echo '<input type="hidden" name="type" value="'.$acttype.'" />'."\n";
		$manager->addTicketHidden();
		echo "You have chosen to delete the form called '$formname'<br /><br />";
		echo "If you proceed this form will no longer be processed by this plugin.<br />\n";
		echo "Are you sure you want to delete this form?<br /><br />\n";
		echo '<input type="submit" value="Yes" />';
		echo '<br /><br /><a href="'.$thispage.'?showlist=forms&amp;safe=true">Return</a>'."\n";
		echo "</form>\n";

		echo "</div>\n";
	}

// close page
	echo "</div>\n";
	$oPluginAdmin->end();


?>