<?php

class NP_MailForm extends NucleusPlugin {
   /* ==========================================================================================
	* MailForm for Nucleus
	*
	* Copyright 2007 by Frank Truscott
	* ==========================================================================================
	* This program is free software and open source software; you can redistribute
	* it and/or modify it under the terms of the GNU General Public License as
	* published by the Free Software Foundation; either version 2 of the License,
	* or (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful, but WITHOUT
	* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
	* FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
	* more details.
	*
	* You should have received a copy of the GNU General Public License along
	* with this program; if not, write to the Free Software Foundation, Inc.,
	* 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
	* http://www.gnu.org/licenses/gpl.html
	* ==========================================================================================
	*/

	/*
	  * To do:
	  * Make it multi-blog aware
	  */

	var $existingForms = array();

	// name of plugin
	function getName() {
		return 'MailForm';
	}

	// author of plugin
	function getAuthor()  {
		return 'Frank Truscott';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL()
	{
		return 'http://www.iai.com/sandbox';
	}

	// version of the plugin
	function getVersion() {
		return '1.0';
	}

	// a description to be shown on the installed plugins listing
	function getDescription() {
		return 'Process any form and email to someone';
	}

	function supportsFeature($what)	{
		switch($what) {
		case 'SqlTablePrefix':
			return 1;
		default:
			return 0;
		}
	}

    function getEventList() { return array('QuickMenu','PreItem'); }
	
	function getTableList() { return array(sql_table('plug_mailform')); }

	function hasAdminArea() { return 1; }

	function event_QuickMenu(&$data) {
    	// only show when option enabled
    	if ($this->getOption('quickmenu') != 'yes') return;
    	global $member;
    	if (!($member->isLoggedIn())) return;
    	array_push($data['options'],
      		array('title' => 'MailForm',
        	'url' => $this->getAdminURL(),
        	'tooltip' => 'manage forms for processing'));
  	}

    function install() {

        $this->createOption('quickmenu', 'Add MailForm to quickmenu?', 'yesno', 'yes');
		$this->createOption('del_uninstall_data', 'Delete database tables on uninstall?', 'yesno','no');

        sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plug_mailform').
					" ( `formname` varchar(255) NOT NULL,
                      `subject` varchar(255) NOT NULL,
                      `mailfrom` varchar(255) NOT NULL,
                      `mailto` varchar(255) NOT NULL,
					  `required` varchar(255) NOT NULL,
					  `filesize` int(11) NOT NULL default '0',
					  `filetype` varchar(255),
                      `sticket` tinyint(2) NOT NULL default '0',
                      `captcha` tinyint(2) NOT NULL default '0',
                      `spamcheck` tinyint(2) NOT NULL default '0',
					  `spamcheckbody` varchar(255),
					  `bodystarttag` varchar(255),
					  `bodyendtag` varchar(255),
					  `formbody` text,
					  `mlinefields` varchar(255),
					  `mlineendtag` varchar(255),
                      `fieldprefix` varchar(255),
					  `blogs` varchar(255) default '',
					  `desturl` varchar(255) default '',
					  `statustext` text,
					  PRIMARY KEY (`formname`)) TYPE=MyISAM");

		if (!intval(quickQuery("SELECT count(*) as result FROM ".sql_table('plug_mailform')." WHERE formname = 'mycontact'"))) {
			$body = '&lt;%MailForm(status)%&gt;&lt;br /&gt;
&lt;%MailForm(startform,mycontact)%&gt;
&lt;%MailForm(sticket,mycontact)%&gt;
&lt;p align="left"&gt;
&lt;b&gt;Name (Required)&lt;/b&gt;&lt;br /&gt;
&lt;input name="FullName" size="30"&gt;
&lt;/p&gt;
&lt;p align="left"&gt;
&lt;b&gt;Email Address (Required)&lt;/b&gt;&lt;br /&gt;
&lt;input name="EmailAddress" size="30"&gt;
&lt;/p&gt;

&lt;p&gt;
&lt;b&gt;Question (Required)&lt;/b&gt;&lt;br /&gt;
&lt;textarea name="Question" cols="50" rows="10"&gt;&lt;/textarea&gt;
&lt;/p&gt;
&lt;p&gt;
&lt;b&gt;Attachment&lt;/b&gt;&lt;br /&gt;
&lt;input type="file" name="SupportFile"&gt;
&lt;/p&gt;
&lt;p&gt;
&lt;%MailForm(captcha,mycontact)%&gt;&lt;/p&gt;
&lt;p&gt;
&lt;font size="2"&gt;
&lt;input class="formbutton" name="B1" type="submit" value="     Submit     "&gt;

&lt;input class="formbutton" name="B2" type="reset" value="     Reset     "&gt;
&lt;/font&gt;&lt;/p&gt;

&lt;/form&gt;';
			global $manager;
			if ($manager->pluginInstalled('NP_Captcha')) $cp = '1';
			else $cp = '0';
			sql_query("INSERT INTO ". sql_table('plug_mailform').
				" (formname,subject,mailfrom,mailto,required,filesize,filetype,sticket,captcha,spamcheck,spamcheckbody,bodystarttag,bodyendtag,formbody,mlinefields,mlineendtag,fieldprefix,blogs,desturl,statustext)".
				" VALUES ('mycontact','.:Contact Form From MySite:.','webmaster@mysite.com','me@somedomain.com',".
				"'FullName,EmailAddress,Question','0','pdf,doc','1','$cp','1','Question,FullName','<start>','<end>',".
				"'$body','Question','[ec]','','0','','<span style=\"color:red;font-weight:bold\">Thank you for your request. It has been successfully submitted.</span>')");
		}
		/* need to do some code to update the db for the few beta users. For all exisitng forms, bodystarttag should be set to '<start>', bodyendtag to '<end>', mlineendtag to '[ec]'

		*/
		$pres = sql_query("SHOW COLUMNS FROM ".sql_table('plug_mailform')." LIKE 'formbody'");
        if (!mysql_num_rows($pres)) {
            sql_query("ALTER TABLE ".sql_table('plug_mailform')." ADD `spamcheckbody` varchar(255) AFTER `spamcheck`");
			sql_query("ALTER TABLE ".sql_table('plug_mailform')." ADD `bodystarttag` varchar(255) AFTER `spamcheckbody`");
			sql_query("ALTER TABLE ".sql_table('plug_mailform')." ADD `bodyendtag` varchar(255) AFTER `bodystarttag`");
			sql_query("ALTER TABLE ".sql_table('plug_mailform')." ADD `formbody` text AFTER `bodyendtag`");
			sql_query("ALTER TABLE ".sql_table('plug_mailform')." ADD `mlinefields` varchar(255) AFTER `formbody`");
			sql_query("ALTER TABLE ".sql_table('plug_mailform')." ADD `mlineendtag` varchar(255) AFTER `mlinefields`");
			sql_query("ALTER TABLE ".sql_table('plug_mailform')." ADD `blogs` varchar(255) default '' AFTER `fieldprefix`");
			sql_query("ALTER TABLE ".sql_table('plug_mailform')." ADD `desturl` varchar(255) default '' AFTER `blogs`");
			sql_query("ALTER TABLE ".sql_table('plug_mailform')." ADD `statustext` text AFTER `desturl`");

			sql_query("UPDATE ".sql_table('plug_mailform')." SET `bodystarttag`='<start>', `bodyendtag`='<end>', `mlineendtag`='[ec]', `blogs`='0'");
        }
    }

    function unInstall() {
		// if requested, delete the data table
		if ($this->getOption('del_uninstall_data') == 'yes')	{
			sql_query('DROP TABLE '.sql_table('plug_mailform'));
		}
	}

	function init() {
		if (mysql_num_rows(sql_query("SHOW TABLES LIKE '%plug_mailform%'")) > 0) {
			$equery = "SELECT formname FROM ".sql_table('plug_mailform');
			$eres = sql_query($equery);
			while ($form = mysql_fetch_object($eres)) {
				$this->existingForms[] = $form->formname;
			}
		}
	}

	function doSkinVar($skinType,$mode = '',$formname = '',$param3 = '') {
		if ($formname == '' && strpos($mode,"|") !== false) {
			$sections = explode("|", $mode);
			$mode = trim($sections[0]);
			$formname = trim($sections[1]);
		}
		switch ($mode) {
            case 'status':
                echo $this->parse(array(&$this,'status'));
                break;
            case 'sticket':
                if ($formname) {
                    $sticket = intval(quickQuery('SELECT sticket as result FROM '.sql_table('plug_mailform').' WHERE formname="'.addslashes($formname).'"'));
                    if ($sticket) echo $this->parse(array(&$this,"sticket|$formname"));
                }
                else echo $this->parse(array(&$this,'sticket'));
                break;
            case 'captcha':
                if ($formname) {
                    $captcha = intval(quickQuery('SELECT captcha as result FROM '.sql_table('plug_mailform').' WHERE formname="'.addslashes($formname).'"'));
                    if ($captcha) echo $this->parse(array(&$this,"captcha|$formname"));
                }
                else echo $this->parse(array(&$this,'captcha'));
                break;
			case 'startform':
				if ($formname) {
                    echo $this->parse(array(&$this,"startform|$formname"));
                }
                else echo "No Form Specified";
				break;
			case 'form':
				if ($formname) {
                    echo $this->parse(array(&$this,"form|$formname"));
                }
                else echo "No Form Specified";
				break;
			case 'template':
				if ($formname) {
					if (strtolower(trim($param3)) == 'yes') $param3 = 'yes';
					else $param3 = 'no';
                    echo $this->parse(array(&$this,"template|$formname|$param3"));
                }
                else echo "No Form Specified";
				break;
            default:
                //do nothing
        }
	}

    function doAction($actionType) {
        global $CONF, $_POST, $_FILES, $DIR_MEDIA, $HTTP_REFERER, $DIR_PLUGINS, $member, $manager;
		$keys = array_keys($_POST);

        switch($actionType) {
            case 'updateform':
                if (!$member->isAdmin() || !$manager->checkTicket()) doError("You do not have permission.");
                $oformname = postVar('oformname');
                $formname = postVar('formname');
                if ($formname == '') doError("Form must have a name.");
                if (!isValidDisplayName($formname)) {
                    doError("Form name format is invalid. Should be alpha-numeric only");
                }
                $valuearray = array(
								'formname'=>strtolower(postVar('formname')),
								'subject'=>postVar('subject'),
								'mailfrom'=>postVar('mailfrom'),
								'mailto'=>postVar('mailto'),
								'required'=>postVar('required'),
								'filesize'=>intPostVar('filesize'),
								'filetype'=>str_replace(';',',',postVar('filetype')),
                                'sticket'=>intPostVar('sticket'),
                                'captcha'=>intPostVar('captcha'),
                                'spamcheck'=>intPostVar('spamcheck'),
								'spamcheckbody'=>postVar('spamcheckbody'),
								'bodystarttag'=>postVar('bodystarttag'),
								'bodyendtag'=>postVar('bodyendtag'),
								'formbody'=>str_replace(array('<','>'),array('&lt;','&gt;'),postVar('formbody')),
								'mlinefields'=>postVar('mlinefields'),
								'mlineendtag'=>postVar('mlineendtag'),
                                'fieldprefix'=>postVar('fieldprefix'),
								'blogs'=>postVar('blogs'),
								'desturl'=>postVar('desturl'),
								'statustext'=>postVar('statustext')
								);
                if (strtolower($oformname) == strtolower($formname)) {
                    $this->updateFormDef($formname, $valuearray);
                }
                else if ($this->formExists($oformname)) {
                    $this->addFormDef($formname, $oformname, $valuearray);
                    $this->delFormDef($oformname);
                }
                else {
                    $this->addFormDef($formname, '', $valuearray);
                }
                $destURL = $CONF['PluginURL'] . "mailform/index.php?showlist=forms&safe=true&status=2";
                header('Location: ' . $destURL);
                break;
            case 'addform':
                if (!$member->isAdmin() || !$manager->checkTicket()) doError("You do not have permission.");
                $destURL = $CONF['PluginURL'] . "mailform/index.php?showlist=forms&safe=true&status=1";
                $formname = postVar('formname');
                if ($formname == '') doError("Form must have a name.");
                if (!isValidDisplayName($formname)) {
                    doError("Form name format is invalid. Should be alpha-numeric only");
                }
                $valuearray = array(
								'formname'=>strtolower(postVar('formname')),
								'subject'=>postVar('subject'),
								'mailfrom'=>postVar('mailfrom'),
								'mailto'=>postVar('mailto'),
								'required'=>postVar('required'),
								'filesize'=>intPostVar('filesize'),
								'filetype'=>str_replace(';',',',postVar('filetype')),
                                'sticket'=>intPostVar('sticket'),
                                'captcha'=>intPostVar('captcha'),
                                'spamcheck'=>intPostVar('spamcheck'),
								'spamcheckbody'=>postVar('spamcheckbody'),
								'bodystarttag'=>postVar('bodystarttag'),
								'bodyendtag'=>postVar('bodyendtag'),
								'formbody'=>str_replace(array('<','>'),array('&lt;','&gt;'),postVar('formbody')),
								'mlinefields'=>postVar('mlinefields'),
								'mlineendtag'=>postVar('mlineendtag'),
                                'fieldprefix'=>postVar('fieldprefix'),
								'blogs'=>postVar('blogs'),
								'desturl'=>postVar('desturl'),
								'statustext'=>postVar('statustext')
								);
                if ($this->formExists($formname)) {
                    doError("$formname - Already Exists. Use a different name.");
                }
                else {
                    $this->addFormDef($formname, '', $valuearray);
                }
                header('Location: ' . $destURL);
                break;
            case 'deleteform':
                if (!$member->isAdmin() || !$manager->checkTicket()) doError("You do not have permission.");
                $destURL = $CONF['PluginURL'] . "mailform/index.php?showlist=forms&safe=true&status=3";
                $formname = postVar('formname');
                if ($formname == '') doError("No Form Specified.");
                if (!$this->formExists($formname)) {
                    doError("$formname - Form does not exist.");
                }
                else {
                    $this->delFormDef($formname);
                }
                header('Location: ' . $destURL);
                break;
            case 'submit':
				global $_POST;
                require_once($DIR_PLUGINS.'mailform/htmlMimeMail.php');
            	$mail = new htmlMimeMail();
                $bodytext = '';
                $error = '';
                $noprocess = array('action', 'type', 'name', 'submit', 'ver_sol', 'ver_key', 'sticket', 'formname', 'B1', 'B2', 'B3');
                $formname = trim(postVar("formname"));
                //$formname = quickQuery('SELECT formname as result FROM '.sql_table('plug_mailform').' WHERE formname="'.addslashes($formname).'"');
                if (!$this->formExists($formname)) doError('Invalid form. Cannot process.');
                $query = "SELECT subject, mailfrom, mailto, required, filesize, filetype, sticket, captcha, spamcheck, spamcheckbody, bodystarttag, bodyendtag, formbody, fieldprefix, blogs, desturl FROM ".sql_table('plug_mailform')." WHERE formname='".addslashes($formname)."'";
                $formdata = mysql_fetch_object(sql_query($query));
                if ($formdata->sticket) {
                    //doError('help');
                    if (!$this->checkTicket()) doError("Invalid ticket.");
                }
// we'll do captcha right before sending, not here

				$formdata->required = str_replace(array(", "," ,","; "," ;",";"),array(",",",",",",",",","),$formdata->required);
				$formdata->spamcheckbody = str_replace(array(", "," ,","; "," ;",";"),array(",",",",",",",",","),$formdata->spamcheckbody);
				$formdata->mlinefields = str_replace(array(", "," ,","; "," ;",";"),array(",",",",",",",",","),$formdata->mlinefields);
                $required = explode(",",$formdata->required);
				$scfields = explode(",",$formdata->spamcheckbody);
				$scbody = "";
				$mlfields = explode(",",$formdata->mlinefields);
				$mltag = $formdata->mlineendtag;
                $fp = $formdata->fieldprefix;

                $bodytext .= $formdata->bodystarttag."\n";
                // Now add some info about the form submitter, like ip, user agent and referer
                $bodytext .= "--------------------------------------------------\n";
                $bodytext .= $fp."RemoteIP: ".stringStripTags(serverVar('REMOTE_ADDR'))."\n";
                $bodytext .= $fp."UserAgent: ".stringStripTags(serverVar('HTTP_USER_AGENT'))."\n";
                $bodytext .= $fp."Referer: ".stringStripTags(serverVar('HTTP_REFERER'))."\n";
                $bodytext .= $fp."FormUsed: $formname\n";
                $bodytext .= "--------------------------------------------------\n";

                for ($i=0;$i<count($keys);$i++) {
					// Process all vars except action, type, name, submit, ver_sol, ver_key, sticket, formname
					if (!in_array($keys[$i], $noprocess)) {
						$field = trim($keys[$i]);
						$value = trim(postVar($keys[$i]));
						if (is_array($value)) {
							$valuearr = $value;
							$value = trim(implode("::",$valuearr));
						}
                        if (strpos(strtolower($field),'comment') !== false || in_array($field,$mlfields)) {
							$value = nl2br($value);
                            $allowedTags = ''; // this would be a null separated list like <hr><i><b>
                            $value = $this->_myStringStripTags($value,'<br>'.$allowedTags);
                            $value = $this->_br2nl($value);
                            $value .= "\n".$mltag."\n";
                        }
                        else {
                            $value = stringStripTags($value);
                        }
                        //$value = stringStripTags($value);
                        if (in_array($field,$required) && trim($value) == '') {
                            $error .= "$field is a required field.<br />";
                        }
                        if (strpos(strtolower($field),'email') !== false) {
                            if (!isValidMailAddress($value)) $error .= "Email address not valid. <br />";
                        }
						if (in_array($field,$scfields)) {
							$scbody .= $value." ";
						}
                        $bodytext .= $fp."$field: $value\n";
					}
				}
                $bodytext .= $formdata->bodyendtag."\n";

                if ($formdata->filesize >= 0) {
                    // Now, let's handle the (possible) file uploads
                    $file = array_keys($_FILES);

                    for($i=0;$i<count($file);$i++) {
                        $field = $file[$i];
                        $filesize = $_FILES[$field]['size'];
                        $type = $_FILES[$field]['type'];
                        $name = $_FILES[$field]['name'];
                        $tmp_name = $_FILES[$field]['tmp_name'];
                        $extention = $this->showExtention($name);

                        if (intval($filesize) > 1 && trim($tmp_name) != '') {
                            //Check size
                            $max_filesize = intval($formdata->filesize);
                            if ($max_filesize == 0) $max_filesize = intval($CONF['MaxUploadSize']);

                            if ($filesize > $max_filesize) {
                                $error .= "Maximun file upload size exceeded. Max size is ".($max_filesize / 1024)." kB<br />";
                            }

                            // Check Type
                            $allowed_types = explode(',',str_replace(' ','',$formdata->filetype));
                            if (count($allowed_types) == 1 && trim($allowed_types[0]) == '') $allowed_types = explode(',',str_replace(' ','',$CONF['AllowedTypes']));

                            $sw_allowed = 0;

                            if (in_array($extention,$allowed_types)) {
                                    $sw_allowed = 1;
                            }

                            if ($sw_allowed != 1) {
                                $error .= "File Type Violation. Only these file type are allowed: ".implode(' ',$allowed_types)."<br />";
                            }

                            if ($error == '') {
                                // Copy the file
                                copy ($tmp_name, $DIR_MEDIA.$name) or doError("File cannot be uploaded. Please contact the webmaster.");
                                // chmod uploaded file
                                $oldumask = umask(0000);
                                @chmod($DIR_MEDIA.$name, 0644);
                                umask($oldumask);

                                $attachment = $mail->getFile($DIR_MEDIA.$name);

                                $mail->addAttachment($attachment, $name, $this->get_mime($DIR_MEDIA.$name));
								//$mail->addAttachment($attachment, $name, mime_content_type($DIR_MEDIA.$name));
                                //$mail->addAttachment($attachment, $name, mime_content_type(system("file -i -b $name")));
                                unlink($DIR_MEDIA.$name);
                            }
                            else doError($error);
                        } // end if filesize <1
                    } // end for loop through files
                } // end if formdata->filesize >= 0

                if ($error == '') {
                    //check captcha if needed right before sending
                    if ($formdata->captcha && !$member->isLoggedIn()) {
                        global $manager;
                        $ckey = postVar('ver_key');
                        $csol = postVar('ver_sol');
                        if ($manager->pluginInstalled('NP_Captcha')) {
                            $npcaptcha =& $manager->getPlugin('NP_Captcha');
                        }
                        if (isset($npcaptcha)) {
                            if (!$npcaptcha->check($ckey, $csol)) {
                                doError("Invalid key.");
                            }
                        }
                    }
					if ($formdata->spamcheck && !$member->isLoggedIn()) {
						global $manager;
						$spamcheck = array(
									'type' => 'comment',
									'body' => $scbody,
									'data' => $scbody,
									'live' => true,
									'return' => true
									);
						$manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
						if ($spamcheck['result'] === true ) doError("Unable to submit. This might be spam");
					}
                    $mail->setSubject($formdata->subject);
                    $mail->setText($bodytext);
					
					if (strpos($formdata->mailfrom,';') === false) {
						$mailfrom = $formdata->mailfrom;
						$replyto = 'none';
					}
					else {
						list($mailfrom,$replyto) = explode(';',$formdata->mailfrom);
					}

					
					$mailfrom = trim($mailfrom);
                    if ($mailfrom == '') {
                        $mail->setFrom($CONF['AdminEmail']);
                    }
                    elseif (!isValidMailAddress($mailfrom)) {
                        $emadd = trim(postVar($mailfrom));
                        if ($emadd == '' || !isValidMailAddress($emadd)) {
                            $mail->setFrom($CONF['AdminEmail']);
                        }
                        else {
                            $mail->setFrom($emadd);
                        }
                    }
                    else {
                        $mail->setFrom($mailfrom);
                    }
/*					
					if ($replyto != 'none') {
						$replyto = trim($replyto);
	                    if ($replyto == '') {
	                        $mail->setReturnPath($CONF['AdminEmail']);
	                    }
	                    elseif (!isValidMailAddress($replyto)) {
	                        $emadd = trim(postVar($replyto));
	                        if ($emadd == '' || !isValidMailAddress($emadd)) {
	                            $mail->setReturnPath($CONF['AdminEmail']);
	                        }
	                        else {
	                            $mail->setReturnPath($emadd);
	                        }
	                    }
	                    else {
	                        $mail->setReturnPath($replyto);
	                    }
					}
*/

                    $recips = explode(',',$formdata->mailto);
                    $result = $mail->send($recips);

                    //echo $result ? 'Mail sent!' : 'Failed to send mail';
                }
                else doError($error);

				if (trim($formdata->desturl) != '') $destURL = trim($formdata->desturl);
                else $destURL = serverVar('HTTP_REFERER');
                //doError($destURL);
                $pgparts = explode('?',$destURL);
                $paramarr = explode('&',$pgparts[1]);
                //doError($pgparts[0].'++++'.$pgparts[1]);
                $newparams = '';
                foreach ($paramarr as $p) {
                    if (strpos($p,"status=") === false && strpos($p,"edit=") === false && trim($p) !== '') {
                        $newparams .= "$p&";
                    }
                }
                $newparams .= "status=1";
				$key = array_keys($_POST);
						// Loop through all POST vars
						for ($i=0;$i<count($key);$i++) {
							// Process all vars except action, type, name, submit, memberid
							if (!in_array($key[$i],array('action','type','name','submit','memberid','special','blogid','itemid','catid','sticket','ver_key','ver_sol'))) {
								$newparams .= "&".$key[$i]."=".htmlspecialchars(str_replace("\n","::br::",postVar($key[$i])),ENT_QUOTES);
							}
						}
                //doError($pgparts[0].'?'.$newparams);
                $destURL = $pgparts[0].'?'.$newparams;

                header("Location: " . $destURL);
                break;
            default:
                doError("No Such Action.");
        }
    }

    function event_PreItem($data) {
        $this->currentItem = &$data["item"];
		$parts=array('body','more');

		foreach ($parts as $part) {
			$this->currentItem->$part = str_replace(array("!%MailForm(",")%!"),array("<%MailForm(",")%>"),$this->currentItem->$part);
            $this->currentItem->$part = preg_replace_callback("#<\%MailForm\((.*?)\)%\>#", array(&$this, 'parse'), $this->currentItem->$part);
		} //foreach ($parts as $part)
	}

    function parse($matches) {
		global $CONF;
        $r = '';
		$matches[1] = str_replace(",","|",$matches[1]);
		$sections = explode("|", $matches[1]);
        $formname = trim($sections[1]);
		$param3 = trim($sections[2]);
		switch ($sections[0]) {
            case 'status':
                if (intRequestVar('status') > 0) {
					if ($formname && $this->formExists($formname)) {
						$stext = quickQuery("SELECT statustext as result FROM ".sql_table('plug_mailform')." WHERE formname='".addslashes($formname)."'");
						if ($stext != '')
							$r = "$stext \n";
						else
							$r = "<span style=\"color:red;font-weight:bold\">Thank you for your request. It has been successfully submitted.</span>\n";
					}
					else
						$r = "<span style=\"color:red;font-weight:bold\">Thank you for your request. It has been successfully submitted.</span>\n";
                }
                break;
            case 'sticket':
                if ($formname && $this->formExists($formname)) {
                    $sticket = intval(quickQuery('SELECT sticket as result FROM '.sql_table('plug_mailform').' WHERE formname="'.addslashes($formname).'"'));
                    if ($sticket) $r = $this->returnTicketHidden();
                }
                else $r = $this->returnTicketHidden();
                break;
            case 'captcha':
                global $manager, $member;
                if ($manager->pluginInstalled('NP_Captcha')) {
                    $npcaptcha =& $manager->getPlugin('NP_Captcha');
                }
                if (isset($npcaptcha) && !$member->isLoggedIn()) {
                    $usecaptcha = 0;
                    if ($formname && $this->formExists($formname)) {
                        $captcha = intval(quickQuery('SELECT captcha as result FROM '.sql_table('plug_mailform').' WHERE formname="'.addslashes($formname).'"'));
                        if ($captcha) $usecaptcha = 1;
                    }
                    else $usecaptcha = 1;

                    if ($usecaptcha) {
                        $key = $npcaptcha->generateKey();
                        $imgHtml = $npcaptcha->generateImgHtml($key,160,60);

                        if ($key) {
                            $r = '<input type="hidden" name="ver_key" value="'.$key.'" />';
                            $r .= '<label for="nucleus_cf_verif">'.$imgHtml.'</label>' . "<br />\n";
                            $r .= '<label for="nucleus_cf_verif">Enter characters in image.</label><input name="ver_sol" size="6" maxlength="6" value="" class="formfield" id="nucleus_cf_verif" />' . "\n";
                        }
                    }
                }
                break;
			case 'startform':
				if ($formname) {
					if ($this->formExists($formname)) {
						$r = '<form action="'.$CONF['ActionURL'].'" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="plugin" />
	<input type="hidden" name="name" value="MailForm" />
	<input type="hidden" name="type" value="submit" />
	<input type="hidden" name="formname" value="'.$formname.'" />';
					}
					else $r = "Invalid Form Name.";
				}
				else {
					$r = "No Form Specified.";
				}
				break;
			case 'template':
				if ($formname) {
					global $DIR_NUCLEUS,$memberid, $memberinfo, $member, $item, $itemid, $_GET;
					if ($this->formExists($formname)) {
						if (strtolower(trim($param3)) == 'yes') $param3 = 'yes';
						else $param3 = 'no';
						if ($param3 == 'yes') {
							if ($member->isLoggedIn()) {
								$filename = "$DIR_NUCLEUS/plugins/mailform/forms/$formname.-loggedin.template";
							} else {
								$filename = "$DIR_NUCLEUS/plugins/mailform/forms/$formname.-notloggedin.template";
							}
						}
						else $filename = "$DIR_NUCLEUS/plugins/mailform/forms/$formname.template";
						if (!file_exists($filename)) return '';

						$fsize = filesize($filename);

						// nothing to include
						if ($fsize <= 0)
							return;

						// read file
						$fd = fopen ($filename, 'r');
						$formbody = fread ($fd, $fsize);
						fclose ($fd);

						$formdata = array(
							'<%formdata(memberid)%>' => $memberid,
							'<%formdata(realname)%>' => $memberinfo->realname,
							'<%formdata(displayname)%>' => $memberinfo->displayname,
							'<%formdata(email)%>' => $memberinfo->email,
							'<%formdata(url)%>' => $memberinfo->url,
							'<%formdata(language)%>' => $memberinfo->language,
						);
						$key = array_keys($_GET);
						// Loop through all POST vars
						for ($i=0;$i<count($key);$i++) {
							// Process all vars except action, type, name, submit, memberid
							if (!in_array($key[$i],array('action','type','name','submit','memberid','special'))) {
								$formdata['<%formdata('.$key[$i].')%>'] = htmlspecialchars(str_replace("::br::","\n",getVar($key[$i])),ENT_QUOTES);
							}
						}

						$formdata['<%formdata(mnumber)%>'] = $member->id;
						$formdata['<%formdata(mrealname)%>'] = $member->realname;
						$formdata['<%formdata(mdisplayname)%>'] = $member->displayname;
						$formdata['<%formdata(memail)%>'] = $member->email;
						$formdata['<%formdata(murl)%>'] = $member->url;
						$formdata['<%formdata(mlanguage)%>'] = $member->language;

						$keys = array_keys($formdata);
						$values = array_values($formdata);

						$formbody = str_replace($keys,$values,$formbody);
						$formbody = preg_replace("#<\%formdata\((.*?)\)%\>#", "", $formbody);
						$formbody = str_replace(array("&lt;","&gt;","!%MailForm(",")%!"),array("<",">","<%MailForm(",")%>"),$formbody);
						$formbody = str_replace(array("<%MailForm(template"),array("<%MailForm(mfempty"),$formbody);
						$formbody = preg_replace_callback("#<\%MailForm\((.*?)\)%\>#", array(&$this, 'parse'), $formbody);


						$r = $formbody;

					}
					else $r = "Invalid Form Name.";
                }
                else echo "No Form Specified";
				break;
			case 'form':
				if ($formname) {
					if ($this->formExists($formname)) {
						$formbody = quickQuery("SELECT formbody as result FROM ".sql_table('plug_mailform')." WHERE formname = '".addslashes($formname)."'");
						$formbody = str_replace(array("&lt;","&gt;","!%MailForm(",")%!"),array("<",">","<%MailForm(",")%>"),$formbody);
						$formbody = str_replace(array("<%MailForm(form"),array("<%MailForm(mfempty"),$formbody);
						$formbody = preg_replace_callback("#<\%MailForm\((.*?)\)%\>#", array(&$this, 'parse'), $formbody);
						$r = $formbody;
					}
					else $r = "Invalid Form Name.";
				}
				else {
					$r = "No Form Specified.";
				}
				break;
            default:
                $r = '';
                break;
        }
		return $r;
	}

/******************************************************************************
 *   Helper Functions                                                         *
 ******************************************************************************/

    function getFormDef($formname = '') {
		$formname = addslashes($formname);
		if ($formname == '') $where = ' ORDER BY formname ASC';
		else $where = " WHERE formname='$formname'";
		$pres = sql_query("SELECT * FROM ".sql_table('plug_mailform').$where);
		return $pres;
	}

    function formExists($formname = '') {
		if ($formname == '') {
			return 0;
		}
		else {
			// return mysql_num_rows(sql_query("SELECT formname FROM ".sql_table('plug_mailform')." WHERE formname='".addslashes($formname)."'"));
			//return intval(quickQuery("SELECT count(*) as result FROM ".sql_table('plug_mailform')." WHERE formname = '".addslashes($formname)."'"));
			return in_array($formname,$this->existingForms);
		}
	}

	// update the form defs for a given form
	function updateFormDef($formname = '', $valuearray = array()) {
		if ($formname == '') {
			doError("Form not specified.");
		}
		else {
			$existing = $this->formExists($formname);
			if ($existing) {
				$formname = addslashes($formname);
				$where = " WHERE formname='$formname'";
				$pquery = "UPDATE ".sql_table('plug_mailform')." SET ";
				$i = 0;
				foreach ($valuearray as $key=>$value) {
					if ($key != 'formname') {
						$pquery .= ($i == 0 ? '' : ', ')."$key='".addslashes($value)."'";
						$i += 1;
					}
				}
				$pquery .= $where;
//doError($pquery);
				sql_query($pquery);
			}
		}
	}

	// add a form def for a new form
	function addFormDef($formname = '', $oldformname = '', $valuearray = array()) {
		if ($formname == '') {
			doError("No Form Specified.");
		}
		else {
			$existing = $this->formExists($formname);
			if (!$existing) {
				$pquery = "INSERT INTO ".sql_table('plug_mailform')." ";
				$i = 0;
				$fs = '';
				$vs = '';
				foreach ($valuearray as $key=>$value) {
					$fs .= ($i == 0 ? '' : ', ')."$key";
					$vs .= ($i == 0 ? '' : ', ')."'".addslashes($value)."'";
					$i += 1;
				}
				$pquery .= "($fs) VALUES($vs)";
				sql_query($pquery);
			}
		}
	}

	// delete a form def
	function delFormDef($formname = '') {
		if ($formname == '') {
			doError("No Form Specified.");
		}
		else {
			$existing = $this->formExists($formname);
			if ($existing) {
				$formname = addslashes($formname);
				$where = " WHERE formname='$formname'";
				$pquery = "DELETE FROM ".sql_table('plug_mailform');
				$pquery .= $where;
				sql_query($pquery);
			}
		}
	}

    function showExtention($filename) {
		$ext = explode(".", $filename);
		$extention = $ext[sizeof($ext)-1];
		return $extention;
	}

/*
    Ticket functions. These are used to make it impossible to simulate certain GET/POST
    requests. tickets are ip specific. Modified version of nucleus cms admin ticket code.
*/

	var $currentRequestTicket = '';

	/**
	 * GET requests: Adds ticket to URL (URL should NOT be html-encoded!, ticket is added at the end)
	 */
	function addTicketToUrl($url)
	{
		$ticketCode = 'sticket=' . $this->_generateTicket();
		if (strstr($url, '?'))
			return $url . '&' . $ticketCode;
		else
			return $url . '?' . $ticketCode;
	}

	/**
	 * POST requests: Adds ticket as hidden formvar
	 */
	function addTicketHidden()
	{
		$ticket = $this->_generateTicket();

		echo '<input type="hidden" name="sticket" value="', htmlspecialchars($ticket), '" />';
	}

    /**
	 * POST requests: Adds ticket as hidden formvar
	 */
	function returnTicketHidden()
	{
		$ticket = $this->_generateTicket();

		return '<input type="hidden" name="sticket" value="'.htmlspecialchars($ticket).'" />';
	}

	/**
	 * Checks the ticket that was passed along with the current request
	 */
	function checkTicket()
	{
		global $member;

		// get ticket from request
		$ticket = requestVar('sticket');

		// no ticket -> don't allow
		if ($ticket == '')
			return false;

		// remove expired tickets first
		$this->_cleanUpExpiredTickets();

		// get remote IP (here stored as $memberid)
		$ipparts = explode('.', serverVar("REMOTE_ADDR"));
		$iptot = 1;
		foreach ($ipparts as $value) {
			if (intval($value) != 0) $iptot = $iptot * intval($value);
		}
		if ($iptot < 100000) $iptot += 100000;
		$memberId = $iptot;

		// check if ticket is a valid one
		$query = 'SELECT COUNT(*) as result FROM ' . sql_table('tickets') . ' WHERE member=' . intval($memberId). ' and ticket=\''.addslashes($ticket).'\'';
		if (quickQuery($query) == 1)
		{
			// [in the original implementation, the checked ticket was deleted. This would lead to invalid
			//  tickets when using the browsers back button and clicking another link/form
			//  leaving the keys in the database is not a real problem, since they're member-specific and
			//  only valid for a period of one hour
			// ]
			// sql_query('DELETE FROM '.sql_table('tickets').' WHERE member=' . intval($memberId). ' and ticket=\''.addslashes($ticket).'\'');
			return true;
		} else {
			// not a valid ticket
			return false;
		}

	}

	/**
	 * (internal method) Removes the expired tickets
	 */
	function _cleanUpExpiredTickets()
	{
		// remove tickets older than 1 hour
		$oldTime = time() - 60 * 60;
		$query = 'DELETE FROM ' . sql_table('tickets'). ' WHERE ctime < \'' . date('Y-m-d H:i:s',$oldTime) .'\'';
		sql_query($query);
	}

	/**
	 * (internal method) Generates/returns a ticket (one ticket per page request)
	 */
	function _generateTicket()
	{
		if ($this->currentRequestTicket == '')
		{
			// generate new ticket (only one ticket will be generated per page request)
			// and store in database
			global $member;
			// get remote IP (here stored as $memberid)
			$ipparts = explode('.', serverVar("REMOTE_ADDR"));
			$iptot = 1;
			foreach ($ipparts as $value) {
				if (intval($value) != 0) $iptot = $iptot * intval($value);
			}
			if ($iptot < 100000) $iptot += 100000;
			$memberId = $iptot;

			$ok = false;
			while (!$ok)
			{
				// generate a random token
				srand((double)microtime()*1000000);
				$ticket = md5(uniqid(rand(), true));

				// add in database as non-active
				$query = 'INSERT INTO ' . sql_table('tickets') . ' (ticket, member, ctime) ';
				$query .= 'VALUES (\'' . addslashes($ticket). '\', \'' . intval($memberId). '\', \'' . date('Y-m-d H:i:s',time()) . '\')';
				if (sql_query($query))
					$ok = true;
			}

			$this->currentRequestTicket = $ticket;
		}
		return $this->currentRequestTicket;
	}

    function _myStringStripTags ($string,$except) {
		$string = preg_replace("/<del[^>]*>.+<\/del[^>]*>/isU", '', $string);
		$string = preg_replace("/<script[^>]*>.+<\/script[^>]*>/isU", '', $string);
		$string = preg_replace("/<style[^>]*>.+<\/style[^>]*>/isU", '', $string);
		//$string = str_replace('>', '> ', $string);
		//$string = str_replace('<', ' <', $string);
		$string = strip_tags($string,$except);

		$string = preg_replace("/\s+/", " ", $string);
		$string = trim($string);
		return $string;
	}

	// function to change <br /> tags back to newlines for display in textareas
	function _br2nl($text) {
        // this reg expr below is messing with th <b> tag as well!!!
		/*$text = trim(preg_replace('|[<][b][r]?\s*?\/??>|i', "\n", $text));*/
		$text = preg_replace("/<br[^>]*>/isU", "\n", $text);
		$text = trim(str_replace("\n ", "\n", $text));
		return $text;
		//return str_replace("<br />", "\n", $text);
	}
	
	function get_mime($filename){
		$extention = $this->showExtention($filename);
		switch(strtolower($extention)){
			case "js":
                return "application/x-javascript";
            case "json":
                return "application/json";
            case "jpg":
            case "jpeg":
            case "jpe":
                return "image/jpg";
            case "png":
            case "gif":
            case "bmp":
            case "tiff":
                return "image/".strtolower($matches[1]);
            case "css":
                return "text/css";
            case "xml":
                return "application/xml";
            case "doc":
            case "docx":
                return "application/msword";
            case "xls":
            case "xlt":
            case "xlm":
            case "xld":
            case "xla":
            case "xlc":
            case "xlw":
            case "xll":
                return "application/vnd.ms-excel";
            case "ppt":
            case "pps":
                return "application/vnd.ms-powerpoint";
            case "rtf":
                return "application/rtf";
            case "pdf":
                return "application/pdf";
            case "html":
            case "htm":
            case "php":
                return "text/html";
            case "txt":
                return "text/plain";
            case "mpeg":
            case "mpg":
            case "mpe":
                return "video/mpeg";
            case "mp3":
                return "audio/mpeg3";
            case "wav":
                return "audio/wav";
            case "aiff":
            case "aif":
                return "audio/aiff";
            case "avi":
                return "video/msvideo";
            case "wmv":
                return "video/x-ms-wmv";
            case "mov":
                return "video/quicktime";
            case "zip":
                return "application/zip";
            case "tar":
                return "application/x-tar";
            case "swf":
                return "application/x-shockwave-flash";
			default:
				if(function_exists("mime_content_type")){ # if mime_content_type exists use it.
				   $m = mime_content_type($filename);
				}else if(function_exists("")){    # if Pecl installed use it
				   $finfo = finfo_open(FILEINFO_MIME);
				   $m = finfo_file($finfo, $filename);
				   finfo_close($finfo);
				}else{    # if nothing left try shell
				   if(strstr($_SERVER[HTTP_USER_AGENT], "Windows")){ # Nothing to do on windows
					   return ""; # Blank mime display most files correctly especially images.
				   }
				   if(strstr($_SERVER[HTTP_USER_AGENT], "Macintosh")){ # Correct output on macs
					   $m = trim(exec('file -b --mime '.escapeshellarg($filename)));
				   }else{    # Regular unix systems
					   $m = trim(exec('file -bi '.escapeshellarg($filename)));
				   }
				}
				$m = split(";", $m);
				return trim($m[0]);
		}
	}

}
?>