<?php

//NP_Gallery admin class

class NPG_ADMIN {

	var $action;
	var $tabs;
	
	function NPG_ADMIN() {
		global $manager;
		
		//admin tabs
		$this->tabs = array();
		array_push($this->tabs, array('action' => 'albumlist', 'active' =>'albums', 'user' => 1, 'title'=>__NPG_ADMIN_TAB_ALBUMS));
		array_push($this->tabs, array('action' => 'comments', 'active' =>'comments', 'user' => 1, 'title'=>__NPG_ADMIN_TAB_COMMENTS));
		array_push($this->tabs, array('action' => 'config', 'active' =>'config', 'title'=>__NPG_ADMIN_TAB_CONFIG));
		if($NPG_CONF['add_album'] == 'select') array_push($this->tabs, array('action' => 'users', 'active' =>'users', 'title'=>__NPG_ADMIN_TAB_USERS));
		array_push($this->tabs, array('action' => 'templates', 'active' =>'templates', 'title'=>__NPG_ADMIN_TAB_TEMPLATES));
		array_push($this->tabs, array('action' => 'functions', 'active' =>'admin', 'title'=>__NPG_ADMIN_TAB_ADMIN));

		$manager->notify('NPgAdminTab', array('tabs' => &$this->tabs ));
	}
	
	function action($action) {
		global $gmember, $NPG_CONF, $manager;
		
		$alias = array(
			'login' => 'albumlist',
			'' => 'albumlist'
		);

		if ($alias[$action])
			$action = $alias[$action];

		$methodName = 'action_' . $action;

		$this->action = strtolower($action);
		
		//if nucleus version 3.2, check ticket
		/*
		if(getNucleusVersion() >= 320) {
			$aActionsNotToCheck = array();

			if (!in_array($this->action, $aActionsNotToCheck))
			{
				if (!$manager->checkTicket())
					$this->error(_ERROR_BADTICKET);
			}
			
		}
		*/
		if (method_exists($this, $methodName))
			call_user_func(array(&$this, $methodName));
		else
			$this->error(_BADACTION . " ($action)");
	

	}
	
	function error($msg) {
		?>
		<h2>Error!</h2>
		<?php		echo $msg;
		echo "<br />";
		echo "<a href='index.php' onclick='history.back()'>"._BACK."</a>";
		exit;
	}
	
	
	function display_tabs($active = 'albumlist') {
		global $gmember, $NPG_CONF, $galleryaction;
		
		echo '<ul id="tabmenu">';
		foreach($this->tabs as $tab) {
			if($tab['user'] || $gmember->isAdmin() ) {
				echo '<li><a ';
				if( $active == $tab['active'] ) echo 'class="active" ';
				echo 'href="'.$galleryaction;
				if($tab['action']) echo '?action='.$tab['action'];
				echo '">'.$tab['title'].'</a></li>';
			}
		}
		echo '</ul>';

	}
	
	function display_selectusers() {
		global $galleryaction,$gmember;
	
		$result = mysql_query('select a.*, b.mname as membername from '.sql_table('plug_gallery_member').' as a, '.sql_table('member').' as b where mnumber=memberid');
		if(!$result) {
			echo mysql_error();
			return;
		}
		
		echo '<h3>'.__NPG_ADMIN_PERMITTED_USERS.'</h3>';
		echo '<div class="half"><table>';
		echo '<thead><tr><th>'.__NPG_FORM_NAME.'</th><th>'.__NPG_FORM_ACTIONS.'</th></thead><tbody>';
		while($row=mysql_fetch_object($result)) {
			echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
			echo '<td>'.$row->membername.'</td>';
			echo '<td><a href="'.$galleryaction.'?action=removeselectuser&amp;userid='.$row->memberid.'">'.__NPG_ADMIN_REMOVE_SELECT_USER.'</td></tr>';
		}
		echo '</tbody></table></div>';
		
		//query for list of users not already assigned in plug_gallery_member and not site admins (they can always add)
		$result = mysql_query('select * from '.sql_table('member').' as a left join '.sql_table('plug_gallery_member').' as b on mnumber=memberid where madmin=0 and memberid is NULL');
		if(!$result) {
			echo mysql_error();
			return;
		}
		if(mysql_num_rows($result)) {
			?>
			<form method="post" action="<?php echo $galleryaction; ?>"><div>
				<input type="hidden" name="action" value="addselectuser" />
				
				<h3><?php echo(__NPG_ADMIN_GIVE_ADD_PERM); ?></h3>
				<?php echo(__NPG_GEN_USER); ?>: <select name="userid">
				<?php
				while($row=mysql_fetch_object($result)) {
					echo '<option value="'.$row->mnumber.'">'.$row->mname;
				}
				?>
				</select>
				<input type="submit" value="<?php echo (__NPG_ADMIN_ADD_TO_LIST); ?>" />
			</div></form>
			<?php
		}
	
	}
	
	function display_options() {
		global $NPG_CONF,$galleryaction;
	
		$galleryconfig = checkgalleryconfig();
		
		if(!$galleryconfig['configured']) {
			setNPGoption('configured', false);
			echo '<div class="error">'.$galleryconfig['message'].'</div>';
		}
		else setNPGoption('configured', true);
		
		$NPG_CONF = getNPGConfig();
		
		if(!$NPG_CONF['configured']) echo '<div class="error">'.__NPG_ERR_GALLLERY_NOT_CONFIG . '</div><br/><br/>';
		
		echo '<form method="post" action="'.$galleryaction.'?action=editoptions" ><div>';
		echo '<fieldset>';
		echo '<legend>'.__NPG_ADMIN_GEN_OPTIONS.'</legend>';
		echo '<p>';
			echo '<label for="addlevel">'.__NPG_ADMIN_ADD_LEVEL.':</label>';
			echo '<select name="addalbumlevel" id="addlevel">';
			echo '<option value="admin" ';
				if($NPG_CONF['add_album'] == 'admin' ) echo 'selected'; 
				echo '>'.__NPG_ADMIN_ONLY_ADMIN;
			echo '<option value="member" ';
				if($NPG_CONF['add_album'] == 'member' ) echo 'selected';
				echo '>'.__NPG_ADMIN_ONLY_REGUSERS;
			echo '<option value="guest" ';
				if($NPG_CONF['add_album'] == 'guest' ) echo 'selected';
				echo '>'.__NPG_ADMIN_ANYONE;
			echo '<option value="select" ';
				if($NPG_CONF['add_album'] == 'select' ) echo 'selected';
				echo '>'.__NPG_ADMIN_SELECTEDUSERS;
			echo '</select></p>';
		
			if($NPG_CONF['add_album'] == 'select' ) {
				echo __NPG_ADMIN_PERMITTED_USERS.': ';
				$result = mysql_query('select a.mname from '.sql_table('member').' as a, '.sql_table('plug_gallery_member').' as b where b.memberid=a.mnumber and b.addalbum=1');
				if(!$result) echo 'sql error'.mysql_error().'<br/>';
				$num_rows = mysql_num_rows($result);
				if(!$num_rows) echo __NPG_ADMIN_NOSELECT;
				$i=0;
				while ($row = mysql_fetch_object($result)) {
					if($i) echo ', ';
					echo $row->mname;
					$i++;
				}
				echo '<br/><br/>';
			}
		
		echo '<p><label for="promo">'.__NPG_ADMIN_PROMOBLOG.': </label>';
			echo '<select name="promocatid" id="promo">';
			echo '<option value="0"';
			if ($NPG_CONF['blog_cat'] == 0) echo ' selected ';
			echo '>'.__NPG_ADMIN_NOPROMO;
			$query = 'select bshortname, cname, catid from ' . sql_table('blog').', '.sql_table('category').' where cblog=bnumber';
			$result = mysql_query($query);
			if(!$result) echo 'sql error! '.mysql_error().'<br/>';
			while($row = mysql_fetch_object($result)) {
				echo '<option value="'.$row->catid.'"';
				if ($NPG_CONF['blog_cat'] == $row->catid) echo ' selected';
				echo '>'.$row->cname.' in '.$row->bshortname;
			}
			echo '</select></p>';
		
		echo '<p><label for="templatef">'.__NPG_ADMIN_ACTIVETEMPLATE.': </label>';
			echo '<select name="template" id="templatef">';
			$query = 'select * from '.sql_table('plug_gallery_template_desc');
			$result = sql_query($query);
			while($row=mysql_fetch_object($result)) {
				echo '<option value="'.$row->tdid.'"';
				if ($NPG_CONF['template'] == $row->tdid) echo ' selected';
				echo '>'.$row->tdname;
			}
			echo '</select></p>';
			
		echo '<p><label for="views">'.__NPG_ADMIN_VIEWTIME.': </label>';
			echo '<input type="text" name="viewtime" id="views" value="'.$NPG_CONF['viewtime'].'" size="3" /></p>';
			
		echo '<p><label for="batch">number of batch upload slots/pictures to loop in massupload: </label>';
			echo '<input type="text" name="batchnumber" id="batch" value="'.$NPG_CONF['batch_add_num'].'" size="3" /></p>';
		
		echo '<p><label for="dir">'.__NPG_ADMIN_IMAGE_DIR.': </label>';
			echo '<input type="text" name="galleryDir" id="dir" value="'.$NPG_CONF['galleryDir'].'" size="20" /></p>';
		
		echo '<p><label for="maxi">'.__NPG_ADMIN_MAX_INT_DIM.': </label>';
			echo '<input type="text" id="maxi" name="maxheight" value="'.$NPG_CONF['maxheight'].'" size="3" /> x <input type="text" name="maxwidth" value="'.$NPG_CONF['maxwidth'].'" size="3" /></p>';
		
		echo '<p><label for="maxt">'.__NPG_ADMIN_THUMB_DIM.': </label>';
			echo '<input type="text" id="maxt" name="thumbheight" value="'.$NPG_CONF['thumbheight'].'" size="3" /> x <input type="text" name="thumbwidth" value="'.$NPG_CONF['thumbwidth'].'" size="3" /></p>';
			
		//AdminCommentsPerPage, ThumbnailsPerPage
		echo '<p><label for="acperpage">'.__NPG_ADMIN_COMMENTSPERPAGE.': </label>';
			echo '<input type="text" id="acperpage" name="AdminCommentsPerPage" value="'.$NPG_CONF['AdminCommentsPerPage'].'" size="3" /></p>';
			
		echo '<p><label for="tbperpage">'.__NPG_ADMIN_THUMBSPERPAGE.': </label>';
			echo '<input type="text" id="tbperpage" name="ThumbnailsPerPage" value="'.$NPG_CONF['ThumbnailsPerPage'].'" size="3" /></p>';
		echo '<p>';
			echo '<label for="dateorrandom">random file prefix or current date as file prefix?:</label>';
			echo '<select name="dateorrandom" id="dateorrandom">';
			echo '<option value="randomprefix" ';
				if($NPG_CONF['dateorrandom'] == 'randomprefix' ) echo 'selected'; 
				echo '>random prefix';
			echo '<option value="dateprefix" ';
				if($NPG_CONF['dateorrandom'] == 'dateprefix' ) echo 'selected';
				echo '>date prefix';
			echo '</select></p>';
			
		echo '<p>';
			echo '<label for="tooltips">use tooltip captions:</label>';
			echo '<select name="tooltips" id="tooltips">';
			echo '<option value="yes" ';
				if($NPG_CONF['tooltips'] == 'yes' ) echo 'selected'; 
				echo '>yes';
			echo '<option value="no" ';
				if($NPG_CONF['tooltips'] == 'no' ) echo 'selected';
				echo '>no';
			echo '</select></p>';
			
		echo '<p>';
			echo '<label for="nextprevthumb">use next and previoud album thumbnails:</label>';
			echo '<select name="nextprevthumb" id="nextprevthumb">';
			echo '<option value="yes" ';
				if($NPG_CONF['nextprevthumb'] == 'yes' ) echo 'selected'; 
				echo '>yes';
			echo '<option value="no" ';
				if($NPG_CONF['nextprevthumb'] == 'no' ) echo 'selected';
				echo '>no';
			echo '</select></p>';
			
		echo '<p>';
			echo '<label for="defaultorder">default order for albums:</label>';
			echo '<select name="defaultorder" id="defaultorder">';
			//these needed to be added to the list (it would be nice)
			//'title','desc','owner','date','titlea','desca','ownera','datea'
			echo '<option value="aesc" ';
				if($NPG_CONF['defaultorder'] == 'aesc' ) echo 'selected'; 
				echo '>aesc';
			echo '<option value="dateprefix" ';
				if($NPG_CONF['defaultorder'] == 'desc' ) echo 'selected';
				echo '>desc';
			echo '</select></p>';
			
		echo '<p>';
			echo '<label for="setorpromo">use keyword sets or static promoposts:</label>';
			echo '<select name="setorpromo" id="setorpromo">';
			echo '<option value="promo" ';
				if($NPG_CONF['setorpromo'] == 'promo' ) echo 'selected'; 
				echo '>promo';
			echo '<option value="sets" ';
				if($NPG_CONF['setorpromo'] == 'sets' ) echo 'selected';
				echo '>sets';
			echo '</select></p>';
			
		echo '<p>';
			echo '<label for="slideshowson">enable slideshows:</label>';
			echo '<select name="slideshowson" id="slideshowson">';
			echo '<option value="yes" ';
				if($NPG_CONF['slideshowson'] == 'yes' ) echo 'selected'; 
				echo '>yes';
			echo '<option value="no" ';
				if($NPG_CONF['slideshowson'] == 'no' ) echo 'selected';
				echo '>no';
			echo '</select></p>';
		echo '<p>';
			echo '<label for="thumborlist">Gallery as list or thumbnails:</label>';
			echo '<select name="thumborlist" id="thumborlist">';
			echo '<option value="list" ';
				if($NPG_CONF['thumborlist'] == 'list' ) echo 'selected'; 
				echo '>list';
			echo '<option value="thumb" ';
				if($NPG_CONF['thumborlist'] == 'thumb' ) echo 'selected';
				echo '>thumb';
			echo '</select></p>';
			
		
		echo '</fieldset>';
			
		echo '<fieldset>';
		echo '<legend>'.__NPG_ADMIN_GRAPHICS_OPTIONS.'</legend>';
		echo '<p><label for="engine">'.__NPG_ADMIN_GRAPHICS_ENGINE.':</label>';
			echo '<select id="engine" name="graphicslibrary">';
			if(GDispresent()) { 
				echo '<option value="gd" ';
				if($NPG_CONF['graphics_library']=='gd') echo 'selected';
				echo '>GD v2 or greater';
			}
			if ($NPG_CONF['im_version'] = getIMversion()) {
				echo '<option value="im" ';
				if($NPG_CONF['graphics_library']=='im') echo 'selected ';
				echo '>ImageMagick';
			}
			echo '</select></p>';
			
			//test for GD
			if(GDispresent()) echo __NPG_ADMIN_GD_INSTALLED.'<br />'; 
				else echo __NPG_ADMIN_GD_NOT_INSTALLED.'<br />';
			if($NPG_CONF['im_version'] = getIMversion()) echo __NPG_ADMIN_IM_INSTALLED.'<br/>'; 
				else echo __NPG_ADMIN_IM_NOT_INSTALLED.'<br/>';
			echo '<br/>';
			
		echo '<p><label for="path">'.__NPG_ADMIN_IM_PATH.':</label>';
			echo '<input type="text" id="path" name="impath" value="'.$NPG_CONF['im_path'].'" size="20" /></p>';

		echo '<p><label for="options">'.__NPG_ADMIN_IM_OPTIONS.':</label>';
			echo '<input type="text" id="options" name="imoptions" value="'.$NPG_CONF['im_options'].'" size="20" /></p>';
		
		echo '<p><label for="quality">'.__NPG_ADMIN_IM_QUALITY.':</label>';
			echo '<input type="text" id="quality" name="imquality" value="'.$NPG_CONF['im_quality'].'" size="2" /></p>';
			
		echo '</fieldset>';
		echo '<br /><input type="submit" value="'.__NPG_FORM_SUBMIT_CHANGES.'" />';
		echo '</div></form>';
		
	}
	
	function display_albums() {
		global $NPG_CONF, $galleryaction, $gmember;
	
		$albums = $gmember->getallowedalbums();
		$memberid = $gmember->getID();
		
		if(!$albums && !$gmember->isAdmin() ) {
			echo __NPG_ERR_NO_ALBUMS.'<br/>';
			return;
		}
		
		echo '<table>';
		echo '<thead><tr><th>'.__NPG_FORM_ALBUM_TITLE.'</th><th>'.__NPG_FORM_ALBUM_DESC.'</th><th>'.Images.'</th><th>'.Owner.'</th><th colspan="2" >'.__NPG_FORM_ACTIONS.'</th></tr></thead>';
		$j=0;
		while($albums[$j]) {
			echo '<tr onmouseover=\'focusRow(this);\' onmouseout=\'blurRow(this);\'>';
			echo '<td>'.$albums[$j]->title.'</td>';
			echo '<td>'.$albums[$j]->description.'</td>';
			echo '<td>'.$albums[$j]->numberofimages.'</td>';
			echo '<td>'.$albums[$j]->mname.'</td>';
			if($gmember->canmodifyalbum($albums[$j]->albumid) ) {
				echo '<td><a href="'.$galleryaction.'?action=album&amp;id='.$albums[$j]->albumid.'">'.__NPG_FORM_SETTINGS.'</a></td>';
				echo '<td><a href="'.$galleryaction.'?action=deletealbum&amp;id='.$albums[$j]->albumid.'">'.__NPG_FORM_DELETE.'</a></td>';
			}
			else echo '<td>'.__NPG_FORM_SETTINGS.'</td><td>'.__NPG_FORM_DELETE.'</td>';
			echo '</tr>';
			$j++;
		}
		echo '</table>';
	}
	
	function display_comments() {
		global $gmember,$galleryaction,$NPG_CONF,$CONF,$NP_BASE_DIR;
		
		$amount = requestvar('amount');
		$page = requestvar('page');
		if($amount) $NPG_CONF['AdminCommentsPerPage'] = intval($amount);
		
		if (!$NPG_CONF['AdminCommentsPerPage']) {
			setNPGOption('AdminCommentsPerPage',25);
			$NPG_CONF['AdminCommentsPerPage'] = 25;
		}
		$offset = intval($page - 1) * $NPG_CONF['AdminCommentsPerPage'];
		if ($offset <= 0) $offset = '0';
		
		if(!$page) $page='1';
		
		$query = 'select * from '.sql_table('plug_gallery_comment').' as a left join '.sql_table('member').' as b on a.cmemberid=b.mnumber left join '.sql_table('plug_gallery_picture').' as c on a.cpictureid=c.pictureid limit '.$offset.', '.($NPG_CONF['AdminCommentsPerPage']+1);
		$res = sql_query($query);
		$nrows = mysql_num_rows($res);
		
		//navigation
		echo "\n".'<div><table class="navigation"><tr><td style="width:15%;">';
		if(intval($page) > 1) {
			echo '<form method="post" action="'.$galleryaction.'"><div>';
			echo '<input type="hidden" name="action" value="comments" />';
			echo '<input type="hidden" name="page" value="'.(intval($page - 1)).'" />';
			echo '<input type="submit" value="&lt; &lt; '._LISTS_PREV.'" />';
			if($amount) echo '<input type="hidden" name="amount" value="'.$amount.'" />';
			echo '</div></form></td>';
		}
			else echo '&lt; &lt; '._LISTS_PREV.'</td>';
			
		echo '<td style="text-align:center;">'.__NPG_PAGE.': '.$page.'</td>';
		
		echo '<td style="text-align:right; width:15%;">';
		if($nrows > $NPG_CONF['AdminCommentsPerPage']) {
		echo '<form method="post" action="'.$galleryaction.'"><div>';
		echo '<input type="hidden" name="action" value="comments" />';
		echo '<input type="hidden" name="page" value="'.(intval($page + 1)).'" />';
		echo '<input type="submit" value="'._LISTS_NEXT.'  &gt; &gt;" />';
		if($amount) echo '<input type="hidden" name="amount" value="'.$amount.'" />';
		echo '</div></form>';
		} 
		else echo _LISTS_NEXT.'  &gt; &gt;';
		echo '</td></tr></table></div>'."\n";
		
		
		//echo '<h3>'.__NPG_ADMIN_COMMENTS.'</h3>';
		echo '<table><thead><tr><th>'.__NPG_COMMENT.'</th><th>'.__NPG_AUTHOR.'</th><th>'.__NPG_TIME.'</th><th>'.__NPG_PICTUREID.'</th><th colspan=\'2\'>'.__NPG_FORM_ACTIONS.'</th></tr></thead><tbody>';
		
		$format = 'M j, h:i';
		
		$i=0;
		while ($row = mysql_fetch_object($res) and $i < $NPG_CONF['AdminCommentsPerPage']) {
			echo '<tr onmouseover=\'focusRow(this);\' onmouseout=\'blurRow(this);\'>';
			echo '<td>'.$row->cbody.'</td>';
			echo '<td>';
			if($row->cuser) echo $row->cuser; else echo $row->mname;
			echo '</td>';
			
			$d = converttimestamp($row->ctime);
			$d = date($format,$d);
			echo '<td>'.$d.'</td>';
			
			if($row->int_filename) {
				$picturelink = $CONF['IndexURL'].$row->int_filename;
				$image_size = getimagesize($NP_BASE_DIR.$row->int_filename);
				$pictureheight = $image_size[1]+15;
				$picturewidth = $image_size[0]+15;
				echo '<td><a href="'.$picturelink.'" onclick="window.open(this.href,\'imagepopup\',\'status=no,toolbar=no,scrollbars=auto,resizable=yes,width='.$picturewidth.',height='.$pictureheight.'\');return false;">'.$row->title.'</td>';
			} else {
				echo '<td>Picture deleted</td>';
			}
			
			echo '<td><a href="'.$galleryaction.'?action=editcommentF&amp;id='.$row->commentid.'">'.__NPG_FORM_EDIT.'</a></td>';
			echo '<td><a href="'.$galleryaction.'?action=deletecomment&amp;id='.$row->commentid.'">'.__NPG_FORM_DELETE.'</td></tr>';
			echo "\n";
			$i++;
		}
		echo '</tbody></table>';
	
	}
	
	function display_templates() {
		global $NPG_CONF, $galleryaction;
	
		echo '<h3>'.__NPG_ADMIN_TEMPLATES.'</h3>';
		echo '<table><thead><tr><th>'.__NPG_FORM_NAME.'</th><th>'.__NPG_FORM_DESC.'</th><th colspan=\'3\' >'.__NPG_FORM_ACTIONS.'</th></tr></thead><tbody>';
		$query = 'select * from '.sql_table('plug_gallery_template_desc');
		$result = sql_query($query);
		while ($row = mysql_fetch_object($result)) {
			echo '<tr onmouseover=\'focusRow(this);\' onmouseout=\'blurRow(this);\'>';
			echo '<td>'.$row->tdname.'</td>';
			echo '<td>'.$row->tddesc.'</td>';
			echo '<td><a href="'.$galleryaction.'?action=edittemplateF&amp;id='.$row->tdid.'">'.__NPG_FORM_EDIT.'</a></td>';
			echo '<td><a href="'.$galleryaction.'?action=clonetemplate&amp;id='.$row->tdid.'">'.__NPG_FORM_CLONE.'</td>';
			echo '<td><a href="'.$galleryaction.'?action=deletetemplate&amp;id='.$row->tdid.'">'.__NPG_FORM_DELETE.'</td></tr>';
		}
		
		echo '</tbody></table>';
		
		$this->display_newtemplate();

	}
	
	function display_newtemplate() {
		global $galleryaction;
		
		echo '<h3>'.__NPG_FORM_NEWTEMPLATE.'</h3>';
		echo '<form method="post" action="'.$galleryaction.'?action=addtemplate"><table>';
		echo '<tr><td>'.__NPG_FORM_TEMPLATE_NAME.'</td><td><input name="tname" maxlength="20" size="20" /></td></tr>';
		echo '<tr><td>'.__NPG_FORM_TEMPLATE_DESC.'</td><td><input name="tdesc" maxlength="200" size="50" /></td></tr>';
		echo '<tr><td></td><td><input type="submit" value="'.__NPG_FORM_CREATENEWTEMPLATE.'" /></table></form>';
	}
	
	function display_adminfunctions() {
		global $galleryaction;
		
		echo '<h3>'.__NPG_ADMIN_ADMIN_FUNCTIONS.'</h3>';
		
		echo '<table>';
		echo '<tr><td><input type="button" value="'.__NPG_ADMIN_CLEANUP.'" ';
		echo 'onclick="window.location.href=\''.$galleryaction.'?action=admin&amp;function=cleanup\'"/>';
		echo '</td><td>'.__NPG_ADMIN_CLEANUP_DESC.'</td></tr>';
		
		echo '<tr><td>';
		echo '<form method="post" action="'.$galleryaction.'">';
		echo '<input type="hidden" name="action" value="admin" />';
		echo '<input type="hidden" name="function" value="rethumb" />';
		echo '<input type="submit" value="'.__NPG_ADMIN_RETHUMB.'" />';
		
		$query = 'select * from '.sql_table('plug_gallery_album');
		$res = sql_query($query);
		echo '<select name="albumtorethumb">';
		echo '<option value="0">'.__NPG_ADMIN_ALLALBUMS;
		while ($row=mysql_fetch_object($res)) {
			echo '<option value="'.$row->albumid.'">'.$row->title;
		}
		echo '</select></form>';
		echo '</td><td>'.__NPG_ADMIN_RETHUMB_DESC.'</td></tr>';
		
		echo '<tr><td>';
		echo '<form method="post" action="'.$galleryaction.'">';
		echo '<input type="hidden" name="action" value="admin" />';
		echo '<input type="hidden" name="function" value="massupload" />';
		echo '<input type="submit" value="'.__NPG_ADMIN_MASSUPLOAD.'" />';
		mysql_data_seek($res,0);
		echo '<select name="uploadalbum">';
		echo '<option value="-1">'.__NPG_ADMIN_NEWALBUM;
		while ($row=mysql_fetch_object($res)) {
			echo '<option value="'.$row->albumid.'">'.$row->title;
		}
		echo '</select></form>';
		echo '</td><td>'.__NPG_ADMIN_MASSUPLOAD_DESC.'</td></tr>';
		
		echo '</table>';

	}
	
	
	function action_edittemplateF() {
		global $gmember,$galleryaction;
		
		$id = $_GET['id'];
		if($gmember->isAdmin() && $id) { 
			$query = 'select * from '.sql_table('plug_gallery_template')." where tdesc = $id";
			$result = sql_query($query);
			if(mysql_num_rows($result)) {
				while ($row = mysql_fetch_object($result)) {
					$section[$row->name] = stripslashes($row->content);
				}
			}
			
			$query2 = 'select * from '.sql_table('plug_gallery_template_desc')." where tdid = $id";
			$result2 = sql_query($query2);
			if(!mysql_num_rows($result2)) {
				echo __NPG_ERR_BAD_TEMPLATE.'<br/>';
				return false;
			}
			$row = mysql_fetch_object($result2);
			$section['name'] = stripslashes($row->tdname);
			$section['desc'] = stripslashes($row->tddesc);
			
			echo '<h3>'.__NPG_FORM_EDIT_TEMPLATE.': '.$section['name'].'</h3>';
			echo '<br/><a href="'.$galleryaction.'">'.__NPG_ADMIN_RETURN.'</a>';
			echo '<form method="post" action="'.$galleryaction.'?action=edittemplate"><div>';
			echo '<input type="hidden" name="id" value="'.$id.'" />';
			echo '<table><thead><tr><th colspan="2" >'.__NPG_FORM_TEMPLATE_SETTINGS.'</th></tr></thead>';
			echo '<tbody>';
			echo '<tr><td class="left">'.__NPG_FORM_TEMPLATE_NAME.'</td>';
			echo '<td><input name="tname" size="20" maxlength="20" value="';
			echo htmlspecialchars($section['name']);
			echo '" /></td></tr>';
			echo '<tr><td class="left">'.__NPG_FORM_TEMPLATE_DESC.'</td>';
			echo '<td><input name="tdesc" size="50" maxlength="200" value="';
			echo htmlspecialchars($section['desc']);
			echo '" /></td></tr>';
			echo '<tr><td></td><td><input type="submit" value="'.__NPG_FORM_SUBMIT_CHANGES.'" /></td></tr>';
			echo '</tbody></table>';
			
			echo '<table><thead><tr><th colspan="2" >'.__NPG_FORM_TEMPLATE_LIST.'</th></tr></thead>';
			echo '<tbody>';
			$tags = allowedTemplateTags('LIST_HEADER');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_HEADER.'<br/></td>';
			echo '<td><textarea class="templateedit" name="LIST_HEADER" cols="50" rows="5">';
			echo htmlspecialchars($section['LIST_HEADER']);
			echo '</textarea></td></tr><tr><td colspan="2">'.$tags.'</td></tr>';
			$tags = allowedTemplateTags('LIST_BODY');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_BODY.'<br/></td>';
			echo '<td><textarea class="templateedit" name="LIST_BODY" cols="50" rows="8">';
			echo htmlspecialchars($section['LIST_BODY']);
			echo '</textarea></td></tr><tr><td colspan="2">'.$tags.'</td></tr>';
			$tags = allowedTemplateTags('LIST_THUM');
			echo '<tr><td class="left" >LIST_THUM<br/></td>';
			echo '<td><textarea class="templateedit" name="LIST_THUM" cols="50" rows="8">';
			echo htmlspecialchars($section['LIST_THUM']);
			echo '</textarea></td></tr><tr><td colspan="2">'.$tags.'</td></tr>';
			$tags = allowedTemplateTags('LIST_FOOTER');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_FOOTER.'<br/></td>';
			echo '<td><textarea class="templateedit" name="LIST_FOOTER" cols="50" rows="5">';
			echo htmlspecialchars($section['LIST_FOOTER']);
			echo '</textarea></td></tr><tr><td colspan="2">'.$tags.'</td></tr>';
			echo '<tr><td></td><td><input type="submit" value="'.__NPG_FORM_SUBMIT_CHANGES.'" /></td></tr>';
			echo '</tbody></table>';
			
			echo '<table><thead><tr><th colspan="2" >'.__NPG_FORM_TEMPLATE_ALBUM.'</th></tr></thead>';
			echo '<tbody>';
			$tags = allowedTemplateTags('ALBUM_HEADER');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_HEADER.'<br/></td>';
			echo '<td><textarea class="templateedit" name="ALBUM_HEADER" cols="50" rows="5">';
			echo htmlspecialchars($section['ALBUM_HEADER']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			$tags = allowedTemplateTags('ALBUM_BODY');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_BODY.'<br/></td>';
			echo '<td><textarea class="templateedit" name="ALBUM_BODY" cols="50" rows="8">';
			echo htmlspecialchars($section['ALBUM_BODY']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			$tags = allowedTemplateTags('ALBUM_FOOTER');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_FOOTER.'<br/></td>';
			echo '<td><textarea class="templateedit" name="ALBUM_FOOTER" cols="50" rows="5">';
			echo htmlspecialchars($section['ALBUM_FOOTER']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			echo '<tr><td></td><td><input type="submit" value="'.__NPG_FORM_SUBMIT_CHANGES.'" /></td></tr>';
			echo '</tbody></table>';
			
			echo '<table><thead><tr><th colspan="2" >'.__NPG_FORM_TEMPLATE_PICTURE.'</th></tr></thead>';
			echo '<tbody>';
			$tags = allowedTemplateTags('ITEM_HEADER');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_HEADER.'<br/></td>';
			echo '<td><textarea class="templateedit" name="ITEM_HEADER" cols="50" rows="5">';
			echo htmlspecialchars($section['ITEM_HEADER']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			echo '<tr><td class="left" >ITEM_TOOLTIPSHEADER<br/></td>';
			echo '<td><textarea class="templateedit" name="ITEM_TOOLTIPSHEADER" cols="50" rows="5">';
			echo htmlspecialchars($section['ITEM_TOOLTIPSHEADER']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			$tags = allowedTemplateTags('ITEM_BODY');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_BODY.'<br/></td>';
			echo '<td><textarea class="templateedit" name="ITEM_BODY" cols="50" rows="8">';
			echo htmlspecialchars($section['ITEM_BODY']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			
			echo '<tr><td class="left" >ITEM_TOOLTIPSFOOTER<br/></td>';
			echo '<td><textarea class="templateedit" name="ITEM_TOOLTIPSFOOTER" cols="50" rows="8">';
			echo htmlspecialchars($section['ITEM_TOOLTIPSFOOTER']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			
			echo '<tr><td class="left" >ITEM_SLIDESHOWC<br/></td>';
			echo '<td><textarea class="templateedit" name="ITEM_SLIDESHOWC" cols="50" rows="8">';
			echo htmlspecialchars($section['ITEM_SLIDESHOWC']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			
		
			echo '<tr><td class="left" >ITEM_SLIDESHOWT<br/></td>';
			echo '<td><textarea class="templateedit" name="ITEM_SLIDESHOWT" cols="50" rows="8">';
			echo htmlspecialchars($section['ITEM_SLIDESHOWT']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			
			echo '<tr><td class="left" >ITEM_NEXTPREVTHUMBS<br/></td>';
			echo '<td><textarea class="templateedit" name="ITEM_NEXTPREVTHUMBS" cols="50" rows="8">';
			echo htmlspecialchars($section['ITEM_NEXTPREVTHUMBS']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			
			$tags = allowedTemplateTags('ITEM_FOOTER');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_FOOTER.'<br/></td>';
			echo '<td><textarea class="templateedit" name="ITEM_FOOTER" cols="50" rows="5">';
			echo htmlspecialchars($section['ITEM_FOOTER']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			
			echo '<tr><td></td><td><input type="submit" value="'.__NPG_FORM_SUBMIT_CHANGES.'" /></td></tr>';
			echo '</tbody></table>';
			
			echo '<table><thead><tr><th colspan="2" >'.__NPG_FORM_TEMPLATE_COMMENTS.'</th></tr></thead>';
			echo '<tbody>';
			$tags = allowedTemplateTags('COMMENT_HEADER');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_HEADER.'<br/></td>';
			echo '<td><textarea class="templateedit" name="COMMENT_HEADER" cols="50" rows="5">';
			echo htmlspecialchars($section['COMMENT_HEADER']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			$tags = allowedTemplateTags('COMMENT_BODY');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_BODY.'<br/></td>';
			echo '<td><textarea class="templateedit" name="COMMENT_BODY" cols="50" rows="8">';
			echo htmlspecialchars($section['COMMENT_BODY']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			$tags = allowedTemplateTags('COMMENT_FOOTER');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_FOOTER.'<br/></td>';
			echo '<td><textarea class="templateedit" name="COMMENT_FOOTER" cols="50" rows="5">';
			echo htmlspecialchars($section['COMMENT_FOOTER']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			echo '<tr><td></td><td><input type="submit" value="'.__NPG_FORM_SUBMIT_CHANGES.'" /></td></tr>';
			echo '</tbody></table>';
			
			echo '<table><thead><tr><th colspan="2" >'.__NPG_FORM_TEMPLATE_PROMO.'</th></tr></thead>';
			echo '<tbody>';
			$tags = allowedTemplateTags('PROMO_TITLE');
			echo '<tr><td class="left" >'.__NPG_PROMO_FORM_TITLE.'<br/></td>';
			echo '<td><input type="text" name="PROMO_TITLE" cols="50" value="';
			echo htmlspecialchars($section['PROMO_TITLE']);
			echo '"/>';
			echo '<br/>'.$tags.'</td></tr>';
			$tags = allowedTemplateTags('PROMO_BODY');
			echo '<tr><td class="left" >'.__NPG_PROMO_FORM_BODY.'<br/></td>';
			echo '<td><textarea class="templateedit" name="PROMO_BODY" cols="50" rows="8">';
			echo htmlspecialchars($section['PROMO_BODY']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			$tags = allowedTemplateTags('PROMO_IMAGES');
			echo '<tr><td class="left" >'.__NPG_FORM_TEMPLATE_PROMOIMAGES.'<br/></td>';
			echo '<td><textarea class="templateedit" name="PROMO_IMAGES" cols="50" rows="4">';
			echo htmlspecialchars($section['PROMO_IMAGES']);
			echo '</textarea><br/>'.$tags.'</td></tr>';
			echo '<tr><td></td><td><input type="submit" value="'.__NPG_FORM_SUBMIT_CHANGES.'" /></td></tr>';
			echo '</tbody></table>';
			echo '</div></form>';
		}
	}
	
	function action_addtemplate() {
		global $gmember;
		
		$name = addslashes(postvar('tname'));
		$desc = addslashes(postvar('tdesc'));
		if($gmember->isAdmin() && $name && $desc) {
			$query = 'insert into '.sql_table('plug_gallery_template_desc')." (tdid, tdname, tddesc) values (NULL,'$name','$desc')";
			sql_query($query);
		}
		
		$this->action_templates();
	}
	
	function action_clonetemplate() {
		global $gmember;
		
		//get postvars: templateid from template to clone
		$id = requestvar('id');
		if($id && $gmember->isAdmin()) {
			//get template data from plg_gallery_template_desc and plug_gallery_template
			$origtemplate = new NPG_TEMPLATE($id);

			//write data to database tables, generating a new tdid for the same data
			$newtemplate = new NPG_TEMPLATE(NPG_TEMPLATE::createnew('cln_'.$origtemplate->getname(), 'Clone of '.$origtemplate->getdesc()));
			foreach($origtemplate->section as $name => $content) 
				$newtemplate->settemplate($name,$content);
		}
		
		$this->action_templates();
	}
	
	function action_deletetemplate() {
		global $gmember;
		$id = requestvar('id');
		
		//don't delete if it's the only template in the database -- you need at least one
		$query = 'select count(*) from '.sql_table('plug_gallery_template_desc');
		$res = sql_query($query);
		$nr = mysql_fetch_row($res);
		if ($nr[0] > 1 && $id && NPG_TEMPLATE::existsID($id) && $gmember->isAdmin()) {
			$query = 'delete from '.sql_table('plug_gallery_template_desc').' where tdid='.$id;
			sql_query($query);
			$query = 'delete from '.sql_table('plug_gallery_template').' where tdesc='.$id;
			sql_query($query);
		}
		
		$this->action_templates();
		
	}
	
	
	
	function action_edittemplate() {
		global $gmember;
		
		$id = $_POST['id'];
		if($gmember->isAdmin() && $id) { 
			$t = new NPG_TEMPLATE($id);
			
			if(isset($_POST['tname']) && isset($_POST['tdesc'])) {
				$t->updategeneralinfo($_POST['tname'],$_POST['tdesc']);
			}
			
			$vars = array('LIST_HEADER','LIST_BODY','LIST_THUM','LIST_FOOTER','ALBUM_HEADER','ALBUM_BODY','ALBUM_SETDISPLAY','ALBUM_FOOTER','ITEM_HEADER','ITEM_TOOLTIPSHEADER','ITEM_BODY','ITEM_SLIDESHOWT','ITEM_SLIDESHOWC','ITEM_FOOTER','ITEM_TOOLTIPSFOOTER','ITEM_NEXTPREVTHUMBS','COMMENT_HEADER','COMMENT_BODY','COMMENT_FOOTER','PROMO_TITLE','PROMO_BODY','PROMO_IMAGES');
			foreach($vars as $j) {
				if(isset($_POST[$j])) {
					$t->update($j,$_POST[$j]);
				}
			}

			//if($success) echo __NPG_ADMIN_UPDATE_TEMPLATE.'<br />'; else echo __NPG_ERR_NO_UPD_TEMPLATE.'<br/>';
			
			//else echo _ERROR_DISALLOWED;
		}
		
		$this->action_templates();
	}
	
	function action_comments() {
		global $gmember;
		
		$this->display_tabs('comments');
		$this->display_comments();
	}
	
	function action_editcommentF() {
		global $galleryaction;
		
		$id = intval(requestvar('id'));
		$query = 'select * from '.sql_table('plug_gallery_comment').' as a left join '.sql_table('member').' as b on a.cmemberid=b.mnumber where a.commentid='.$id;
		$res = sql_query($query);
		$row = mysql_fetch_object($res);
		
		?>
		<h2><?php echo _EDITC_TITLE; ?></h2>
		
		<form action="<?php echo $galleryaction; ?>" method="post"><div>
		<input type="hidden" name="action" value="editcomment" />
		<input type="hidden" name="id" value="<?php echo $id;?>" />
		<?php
		echo '<table><tr>';
		echo '<th colspan="2">'._EDITC_TITLE.'</th>';
		echo '</tr><tr>';
		echo '<td>'._EDITC_WHO.'</td><td>';
		if($row->cuser) echo $row->cuser; else echo $row->mname.' ('._EDITC_MEMBER.')';
		echo '</td></tr><tr>';
		echo '<td>'._EDITC_WHEN.'</td><td>';
		echo $row->ctime;
		echo '</td></tr><tr>';
		echo '<td>'._EDITC_HOST.'</td><td>';
		echo $row->chost;
		echo '</td></tr><tr>';
		echo '<td>'._EDITC_TEXT.'</td><td>';
		echo '<textarea name="body" rows="10" cols="50">';
		echo htmlspecialchars($row->cbody);
		echo '</textarea>';
		echo '</td></tr><tr>';
		echo '<td>'._EDITC_EDIT.'</td><td>';
		echo '<input type="submit" value="'._EDITC_EDIT.'" />';
		echo '</td></tr></table></div></form>';
	}
	
	function action_editcomment() {
		global $gmember;
		
		$id = intval(requestvar('id'));
		$body = addslashes(requestvar('body'));
		
		if( $gmember->canModifyComment($id) ) {
			sql_query('update '.sql_table('plug_gallery_comment').' set cbody = "'.$body.'" where commentid='.$id);
		}
		
		$this->action_comments();
		
	}
	
	function action_deletecomment() {
		global $gmember,$galleryaction;
		
		$id = intval(requestvar('id'));
		$query = 'select * from '.sql_table('plug_gallery_comment').' as a left join '.sql_table('member').' as b on a.cmemberid=b.mnumber where a.commentid='.$id;
		$res = sql_query($query);
		if(mysql_num_rows($res)) {
			$row = mysql_fetch_object($res);
		} else {
			echo __NPG_ADMIN_NO_COMMENT.'<br/>';
			return;
		}
		
		if($gmember->canModifyComment($id) ) {
			echo '<h2>'._DELETE_CONFIRM.'</h2>';
			echo '<p>'._CONFIRMTXT_COMMENT.'</p>';
			echo '<div class="note">';
			echo '<b>'._EDITC_WHO.': </b>';
			if($row->cuser) echo $row->cuser; else echo $row->mname;
			echo '<br/><b>'._EDITC_TEXT.': </b>';
			echo htmlspecialchars($row->cbody);
			echo '</div>';
			echo '<form method="post" action="'.$galleryaction.'"><div>';
			echo '<input type="hidden" name="action" value="deletecommentfinal" />';
			echo '<input type="hidden" name="id" value="'.$id.'" />';
			echo '<input type="submit" value="'._DELETE_CONFIRM_BTN.'" />';
			echo '</div></form>';
		}
		else {
			echo __NPG_ADMIN_NO_DEL_PERMISSION.'<br/>';
		}
	}
	
	function action_deletecommentfinal() {
		global $gmember,$galleryaction;
		
		$id = intval(requestvar('id'));
		if($gmember->canModifyComment($id) ) {
			$res = sql_query('delete from '.sql_table('plug_gallery_comment').' where commentid='.$id);
			//if(!mysql_num_rows($res)) echo __NPG_ADMIN_NOTDELETED.'<br/>'; else echo __NPG_ADMIN_DELETED.'<br/>';
		}
		
		$this->action_comments();
	}
	
	function action_templates() {
		global $gmember;
		
		$this->display_tabs('templates');
		if($gmember->isAdmin()) { 
				echo '<div id="admin_content">';
				$this->display_templates();
				echo '</div>';
			}
			else echo _ERROR_DISALLOWED;
	}
	

	function action_admin() {
		global $gmember,$DIR_NUCLEUS,$galleryaction,$CONF;
		
		$funct = requestvar('function');
		
		if (isset($funct)) {
			if($gmember->isAdmin()) {
				switch ($funct) {
					case 'cleanup':
						database_cleanup();
						echo __NPG_ADMIN_SUCCESS_CLEANUP.'<br/>';
						break;
					case 'rethumb':
						$album = intval(requestvar('albumtorethumb'));
						rethumb($album);
						break;
					case 'massupload':
						$album = intval(requestvar('uploadalbum'));
						$stop = true;
						if ($album == -1) {
							
							$title = requestvar('title');
							$desc = requestvar('desc');
							
							if(!$title && !$desc) {
							?>
							<h3><?php echo __NPG_FORM_ADDALBUM; ?></h3>
							<?php echo __NPG_FORM_MASSUPLOAD_NEWALBUM; ?><br/>
							<form method="post" action="<?php echo $galleryaction; ?>"><div>
								<input type="hidden" name="function" value="massupload" />
								<input type="hidden" name="action" value="admin" />
								<input type="hidden" name="uploadalbum" value="-1" />
								
								<?php addAlbumFormFields(); ?>
							</div></form>
							
							<?php
							}
							else {
								$NPG_vars['ownerid'] = $gmember->getID();
								$NPG_vars['title'] = $title; 
								$NPG_vars['description'] = $desc;
								$album = ALBUM::add_new($NPG_vars);
							}
							
						}
						
						if($album > 0) {
							//are you sure? this may timeout if too big?
							echo '<h3>'.__NPG_FORM_MASSUPLOAD_CONFIRM.'</h3>';
							?>
							<form name="massuploadokay" method="post" action="<?php echo $CONF['PluginURL'].'gallery/add_picture.php'; ?>" ONSUBMIT="openTarget(this, 'width=600,height=600,resizable=1,scrollbars=1'); return true;" target="newpopup"><div>
								<input type="hidden" name="type" value="massupload" />
								<input type="hidden" name="id" value="<?php echo $album; ?>" />
								<input type="submit" value="<?php echo __NPG_FORM_MASSUPLOAD_SUBMIT; ?>" />
							</div></form>
							<?php
						}
						break;

					default:
						echo __NPG_ERR_BAD_FUNCTION.'<br/>';
						break;
				}
			} else echo __NPG_ERR_NOT_ADMIN.'<br/>';
		}
		if(!$stop) $this->action_functions();
	}
	
	function action_functions() {
		global $gmember;
		
		$this->display_tabs('admin');
		if($gmember->isAdmin()) { 
			echo '<div id="admin_content">';
			$this->display_adminfunctions();
			echo '</div>';
		}
		else echo _ERROR_DISALLOWED;
	}
	
	function action_editoptions() {
		//need more error checking here
		if (isset($_POST['addalbumlevel'])) {
			//$allowedoptions = array("admin","guest","select","member");
			//if (in_array($_POST['addalbumlevel'], $allowedoptions))
				setNPGoption('add_album', $_POST['addalbumlevel']);
		}
		if (isset($_POST['promocatid'])) {
			setNPGoption('blog_cat', $_POST['promocatid']);
		}
		/*
		if (isset($_POST['template'])) {
			setNPGoption('template', $_POST['template']);
		}
		if (isset($_POST['viewtime'])) {
			setNPGoption('viewtime', $_POST['viewtime']);
		}
		*/
		if (isset($_POST['batchnumber'])) {
			setNPGoption('batch_add_num', $_POST['batchnumber']);
		}
/*
		if (isset($_POST['galleryDir'])) {
			setNPGoption('galleryDir', $_POST['galleryDir']);
		}
		if (isset($_POST['maxheight'])) {
			setNPGoption('maxheight', $_POST['maxheight']);
		}
		if (isset($_POST['maxwidth'])) {
			setNPGoption('maxwidth', $_POST['maxwidth']);
		}
		if (isset($_POST['thumbheight'])) {
			setNPGoption('thumbheight', $_POST['thumbheight']);
		}
		if (isset($_POST['thumbwidth'])) {
			setNPGoption('thumbwidth', $_POST['thumbwidth']);
		}
		*/
		$t = $_POST['graphicslibrary'];
		if (isset($t)) {
			if (($t == 'im') or ($t == 'gd')) {
				setNPGoption('graphics_library', $_POST['graphicslibrary']);
			}
		}
		if (isset($_POST['impath'])) {
			setNPGoption('im_path', $_POST['impath']);
		}
		if (isset($_POST['imoptions'])) {
			setNPGoption('im_options', $_POST['imoptions']);
		}
		if (isset($_POST['imquality'])) {
			setNPGoption('im_quality', $_POST['imquality']);
		}

		$allowedoptions = array('template', 'viewtime', 'galleryDir', 'maxheight', 'maxwidth', 'thumbheight','thumbwidth','AdminCommentsPerPage','ThumbnailsPerPage','dateorrandom','tooltips','nextprevthumb','defaultorder','setorpromo','slideshowson','thumborlist' );
		foreach($allowedoptions as $option) if(isset($_POST[$option])) setNPGoption($option, $_POST[$option]);
			
		
		$this->action_config();
	}

	function action_config() {
		global $gmember;
		
		$NPG_CONF = getNPGConfig();
		
		$this->display_tabs('config');
		if($gmember->isAdmin()) { 
			echo '<div id="admin_content">';
			$this->display_options();
			echo '</div>';
		}
	}
	
	function action_removeselectuser() {
		global $gmember;
		
		$mid = requestvar('userid');
		if($mid) {
			$query='delete from '.sql_table('plug_gallery_member')." where memberid=$mid";
			if($gmember->isAdmin()) $result = mysql_query($query);
		}
		$this->action_users();
	}
	
	function action_addselectuser() {
		global $gmember;
		
		$mid = requestvar('userid');
		if($mid) {
			$query = 'insert into '.sql_table('plug_gallery_member')." values ('$mid',1) ";
			if($gmember->isAdmin()) $result = mysql_query($query);
		}
		$this->action_users();
	}

	function action_uers() {
		global $gmember, $NPG_CONF;
		
		$this->display_tabs('users');
		if($gmember->isAdmin() && $NPG_CONF['add_album'] == 'select') { 
			echo '<div id="admin_content">';
			$this->display_selectusers();
			echo '</div>';
		}
		else echo _ERROR_DISALLOWED;
	}
	
	function action_editalbumtitle() {
		global $gmember,$galleryaction;
		
		$id = requestVar('id');
		if($gmember->canModifyAlbum($id)) {
			$alb = new ALBUM($id);
			$alb->set_title(addslashes(requestVar('title')));
			$alb->set_description(addslashes(requestVar('desc')));
			$alb->set_commentsallowed(requestvar('commentsallowed'));
			$alb->set_publicalbum(requestvar('publicalbum'));
			$alb->set_thumbnail(requestvar('thumbnail'));
			$alb->write();
			echo __NPG_ADMIN_SUCCESS_ALBUM_UPDATE.'<br/>';
		}
		else echo __NPG_ERR_ALBUM_UPDATE.'<br/>';
		echo '<br/><a href="'.$galleryaction.'">'.__NPG_ADMIN_RETURN.'</a>';
	}
	
	function action_editalbumteam() {	}
	
	function action_deltmember() {
		global $gmember,$galleryaction;
		
		$aid = requestvar('aid');
		$mid = requestvar('mid');
		if($aid && $mid) 
		if($gmember->canModifyAlbum($aid)) {
			$query = 'delete from '.sql_table('plug_gallery_album_team')." where tmemberid=$mid and talbumid=$aid";
			$result = sql_query($query);	
			echo __NPG_ADMIN_SUCCESS_TEAM_UPDATE.'<br/>';
		}
		else echo __NPG_ERR_TEAM_UPDATE.'<br/>';
		echo '<br/><a href="'.$galleryaction.'?action=album&amp;id='.$aid.'">'.__NPG_ADMIN_RETURN.'</a>';
	}
	
	function action_toggleadmin() {
		global $gmember,$galleryaction;
		
		$aid = requestvar('aid');
		$mid = requestvar('mid');
		if($aid && $mid) 
		if($gmember->canModifyAlbum($aid)) {
			$query = 'update '.sql_table('plug_gallery_album_team')." set tadmin=abs(tadmin-1) where tmemberid=$mid and talbumid=$aid";
			$result = mysql_query($query);
			if(!$result) echo mysql_error().'<br/>';	
			echo __NPG_ADMIN_SUCCESS_TEAM_UPDATE.'<br/>';
		}
		else echo __NPG_ERR_TEAM_UPDATE.'<br/>';
		echo '<br/><a href="'.$galleryaction.'?action=album&amp;id='.$aid.'">'.__NPG_ADMIN_RETURN.'</a>';
	}

	
	function action_addalbumteam() {
		global $gmember,$galleryaction;
		
		$id = requestvar('id');
		$tmember = requestvar('tmember');
		$admin = requestvar('admin');
		if($id && $tmember) {
			if(!$admin) $admin = 0;
			if($gmember->canModifyAlbum($id)) {
				$result = mysql_query('select * from '.sql_table('plug_gallery_album_team')." where tmemberid=$tmember");
				if(!$result) echo mysql_error().'<br/>';
				if(!mysql_num_rows($result)) 
					$result2 = mysql_query('insert into '.sql_table('plug_gallery_album_team')." values ('$tmember', '$id', $admin)");
				echo __NPG_ADMIN_SUCCESS_TEAM_UPDATE.'<br/>';
			}
			else echo __NPG_ERR_TEAM_UPDATE.'<br/>';
			echo '<br/><a href="'.$galleryaction.'?action=album&amp;id='.$id.'">'.__NPG_ADMIN_RETURN.'</a>';
		}
	}
	

	function action_deletealbum() {
		$id = requestVar('id');
		if($id) {
			deletealbum($id);
		}
	}
	

	function action_album() {
		global $gmember;
		$id = requestVar('id');

		if($id && $gmember->canmodifyalbum($id)) {
			editalbumform($id);
		}
	}
	

	function action_finaldeletealbum() {
		global $gmember;
		
		$ok = true;
		$id = requestVar('id');
		$option = requestVar('deleteoption');
		if($id && $option && $gmember->canmodifyalbum($id)) {
			if($option == '-1') { //delete pictures
				$query = 'select * from '.sql_table('plug_gallery_picture').' where albumid='.$id;
				$result = mysql_query($query);
				if(!$result) echo mysql_error().":$query<br/>";
				while($row = mysql_fetch_object($result)) {
					$delresult = PICTURE::delete($row->pictureid);
					if($delresult['status'] == 'error') {
						echo $delresult['message'];
						$ok = false;
					}
					else {
						$delresult = PICTURE::deletepromoposts($row->pictureid);
						$query2 = 'delete from '.sql_table('plug_gallery_picture').' where pictureid='.$row->pictureid;
						$result2 = mysql_query($query2);
						if(!$result2) echo mysql_error().":$query<br/>";
					}
				}
				if($ok) {
					$query = 'delete from '.sql_table('plug_gallery_album').' where albumid='.$id;
					$result = mysql_query($query);
					if(!$result) echo mysql_error().":$query<br/>";
				}

			}
			else {
				if($gmember->canaddpicture($option)) {
					$query = 'update '.sql_table('plug_gallery_picture').' set albumid='.$option.' where albumid='.$id;
					$result = mysql_query($query);
					if(!$result) echo mysql_error().'<br/>';
					ALBUM::fixnumberofimages($option);
					$query = 'delete from '.sql_table('plug_gallery_album').' where albumid='.$id;
					$result = mysql_query($query);
					if(!$result) echo mysql_error().'<br/>';
				}
				else {
					echo __NPG_ERR_DA_MOVE_PICTURE.'<br/>';
				}
			}
		}
		$this->action_albumlist();
	}
	

	function action_albumlist() {
		$this->display_tabs('albums');
		$this->display_albums();
	}
		



}

?>
