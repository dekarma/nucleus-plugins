<style type="text/css">
<!--
body,td,th {
	font-family: Courier New, Courier, monospace;
	font-size: 12px;
	color: #000000;
}
body {
	margin-left: 20px;
	margin-top: 20px;
}
-->
</style>
<?php

//add_picture.php
include('../../../config.php');
include_once('config.php'); //gallery config
include_once($DIR_LIBS . 'ITEM.php');


class NPG_PROMO_ACTIONS extends BaseActions {

	var $parser;
	var $thumbnails;
	var $template;
	var $CurrentThumb;

	function NPG_PROMO_ACTIONS() {
		$this->BaseActions();
	}

	function getdefinedActions() {
		return array(
			'images',
			'thumbnail',
			'centeredtopmargin',
			'picturelink',
			'description'
			);	
	}
	
	function setTemplate(&$template) {$this->template = &$template;}
	function setParser(&$parser) {$this->parser =& $parser; }
	function setCurrentThumb(&$CurrentThumb) {$this->CurrentThumb = &$CurrentThumb;}
	
	function addimagethumbnail($thumbnails) {$this->thumbnails = $this->thumbnails . $thumbnails;}
	function needtoaddpromo() {if($this->thumbnails <> '') return true; return false;}
	
	function parse_images() { echo $this->thumbnails; }
	function parse_description() {echo $this->CurrentThumb->getdescription(); }
	function parse_thumbnail() {echo $this->CurrentThumb->getthumbfilename();}
	function parse_picturelink() {echo '<%gallery(link,picture,'.$this->CurrentThumb->getID().')%>';}
	function parse_centeredtopmargin($height = 140,$adjustment = 0) {
		$image_size = getimagesize($this->CurrentThumb->getthumbfilename());
		$topmargin = ((intval($height) - intval($image_size[1])) / 2) + intval($adjustment);
		echo 'margin-top: '.$topmargin.'px;';
	}
}

//globals for add_picture.php
global $NPG_CONF,$gmember,$manager,$NP_BASE_DIR;

if(!$NPG_CONF['temp_table']) {
	$NPG_CONF['temp_table'] = 'gallery_temp';
	setNPGoption('temp_table','gallery_temp');
}

if(!$NPG_CONF['batch_add_num']) $NPG_CONF['batch_add_num'] = 10;

//todo: display header


$type = requestvar('type');
switch($type) {
	case 'firststage':
			$exist_temp_table = mysql_query('SELECT 1 FROM '.$NPG_CONF['temp_table'].' LIMIT 0');
			if ($exist_temp_table) sql_query('drop table '. $NPG_CONF['temp_table']);
		if(requestVar('id')) $albumid = requestVar('id'); else $albumid = 0;
		$number_of_uploads=$NPG_CONF['batch_add_num'];
		addPictureForm($albumid, $number_of_uploads);
		break;
	case 'secondstage':
		if(requestVar('id')) $albumid = requestVar('id'); else $albumid = 0;
		$result = mysql_query('create table '.$NPG_CONF['temp_table']
						.'(tempid int unsigned not null auto_increment PRIMARY KEY, '
						.'memberid int unsigned, '
						.'albumid int unsigned, '
						.'filename varchar(60), '
						.'intfilename varchar(60), '
						.'thumbfilename varchar(60), '
						.'title varchar(40), '
						.'description varchar(255), '
						.'promote tinyint unsigned default 0, '
						.'error varchar(60) default NULL)' );

		$i=0;
		while ($uploadInfo = postFileInfo('uploadpicture'.$i)) {
			if ($filename = $uploadInfo['name']) {
				$filetype = $uploadInfo['type'];
				$filesize = $uploadInfo['size'];
				$filetempname = $uploadInfo['tmp_name'];
				//this gets picasa captions from ITPC metadata
				$size = getimagesize($filetempname, $info);
				if (isset($info["APP13"])) {
					$iptc = iptcparse($info["APP13"]);
					$description = $iptc["2#120"][0];
				}
				//adds a picture to the temp table so user can add description, etc before actually adding to database
				add_temp($albumid, $filename, $filetype, $filesize, $filetempname,$description);
				}
			$i++;
		}
		addTempPictureForm($albumid);
		break;
	case 'massupload' :
		//if the referring address is not this page, drop the table.
		if(!stristr($_SERVER['HTTP_REFERER'],'add_picture.php')) {
			$exist_temp_table = mysql_query('SELECT 1 FROM '.$NPG_CONF['temp_table'].' LIMIT 0');
			if ($exist_temp_table) sql_query('drop table '. $NPG_CONF['temp_table']);
			echo 'starting a fresh massupload batch </br>';
		}
		else echo '...continuing a massupload batch </br>';
		//create a table (if the table is already there, mysql just adds to the table)
		if(requestVar('id')) $albumid = requestVar('id'); else $albumid = 0;
		$result = mysql_query('create table '.$NPG_CONF['temp_table']
                  .'(tempid int unsigned not null auto_increment PRIMARY KEY, '
                  .'memberid int unsigned, '
                  .'albumid int unsigned, '
                  .'filename varchar(60), '
                  .'intfilename varchar(60), '
                  .'thumbfilename varchar(60), '
                  .'title varchar(40), '
                  .'description varchar(255), '
                  .'promote tinyint unsigned default 0, '
                  .'error varchar(60) default NULL)' );
      //checks to see how many files are in the upload directory
		if ($handle = opendir($NP_BASE_DIR.'upload')) {
				while (false !== ($filename = readdir($handle))) {
					if (stristr($filename,'jpeg') or stristr($filename,'jpg')){
						$scandir[] = $filename;
					}
				}
				closedir($handle);
				$numpics = count($scandir);

      	 echo 'there are ' . $numpics .' pictures(jpg) left in the upload directory </br>';
      	 echo 'the massupload script will loop though '.$NPG_CONF['batch_add_num'].' pictures per time to prevent timeouts</br>';
      	 //if there are more than 10 files, remember to refresh, 
      	 //this is to solve the incomplete database writes when scripts timeout
      	 //have to reopen the directory so it starts from the first file again
      	 $handle = opendir($NP_BASE_DIR.'upload');
         for ( $i=0; false !==($file=readdir($handle)) and $i<=$NPG_CONF['batch_add_num']; $i++ ) {
         		if (stristr($file,'jpeg') or stristr($file,'jpg')){
					$filename = $file;
					//echo $filename . 'uploaded </br>';
         			$filetype = filetype($NP_BASE_DIR.'upload/' . $file);
         			$filesize = filesize($NP_BASE_DIR.'upload/' . $file);
         			$filetempname = $NP_BASE_DIR.'upload/' . $file;
         			$size = getimagesize($filetempname, $info);
		 			//this gets picasa captions from ITPC metadata
						if (isset($info["APP13"])) {
							$iptc = iptcparse($info["APP13"]);
							$description = $iptc["2#120"][0];
							$description = addslashes($description);
							$description = stripslashes($description);	
						}
					//adds a picture to the temp table so user can add description, etc before actually adding to database
					echo 'creating thumbnail for ';
					echo $filename;
					add_temp($albumid, $filename, $filetype, $filesize, $filetempname,$description);
					echo '.... done. </br>';
					}         
         }
         closedir($handle);
      }
      //if there were more than 10 files, refresh, else proceed.
      if($numpics > $NPG_CONF['batch_add_num']){
      	echo "<SCRIPT LANGUAGE=\"JavaScript\">window.location=\" ".$_SERVER['SCRIPT_NAME']."?type=massupload&id=".$id." \";</script>";
      	}
      	addTempPictureForm($albumid);
      	break;
      	
	case 'addpictures':
		$i=0;
		$promoallowed = postvar('promopost');
		$promo_ids = array();
		$setorpromo = $NPG_CONF['setorpromo'];
		
		if(!$NPG_CONF['template']) $NPG_CONF['template'] = 1;
		$template = & new NPG_TEMPLATE($NPG_CONF['template']);
		
		$actions = new NPG_PROMO_ACTIONS();
		$parser = new PARSER($actions->getdefinedActions(),$actions);
		$actions->setparser($parser);
		$actions->settemplate($template);
		
		while($tempid = postvar('tid'.$i)) {
			$title = postvar('title'.$i);
			$description = postvar('description'.$i);
			$promote = postvar('promote'.$i);
			$albumid = postvar('album'.$i);

			$filename = $NPG_CONF['galleryDir'].'/'.postvar('filename'.$i);
			$int_filename = postvar('intfilename'.$i);
			$thumb_filename = postvar('thumbfilename'.$i);
			$keywords = postvar('keywords'.$i);

			//check permissions to add
			if($gmember->canAddPicture($albumid)) {
				$pict = new PICTURE(0);
				$newid = $pict->add_new($albumid, $filename, $title, $description, $int_filename, $thumb_filename,$keywords);
				echo "$filename added<br/>";
				
				//promotion post
				if ($promoallowed == '1' && $promote == 'yes') {
					ob_start();
					$actions->setCurrentThumb($pict);
					$parser->parse($template->section['PROMO_IMAGES']);
					$capt = ob_get_contents();
					ob_end_clean();
					$actions->addimagethumbnail($capt);
					array_push($promo_ids, $newid);
				}
				unset($pict);
				
				$manager->notify('NPgPostAddPicture',array('pictureid' => $newid));
			}
			else {
				echo "$filename not added due to bad album permissions<br/>";
				//delete files
				if(file_exists($galleryDir.'/'.$filename))  unlink($galleryDir.'/'.$filename);
				if(file_exists($int_filename))  unlink($int_filename);
				if(file_exists($thumb_filename))  unlink($thumb_filename);
				
			}

			$i++;
		}

		if ($promoallowed == '1' && $actions->needtoaddpromo() ) {

			$today = getdate();
			$hour = $today['hours'];
			$minutes = $today['minutes'];
			$day = $today['mday'];
			$month = $today['mon'];
			$year = $today['year'];
			
			
			ob_start();
			$parser->parse($template->section['PROMO_TITLE']);
			$title = ob_get_contents();
			ob_end_clean();
			if ($setorpromo=='promo'){
				ob_start();
				$parser->parse($template->section['PROMO_BODY']);
				$body = ob_get_contents();
				ob_end_clean();
			}
			if ($setorpromo=='sets'){
				$body = '<%gallery(keywords,'.$promokeywords.',desc)%>';
			}

			?>
				<br/><hr/><br/>
				<form><input type="button" value="<?php echo __NPG_PROMO_FORM_CANCEL; ?>" onclick="window.close()"/></form>
				<form method="post" action="add_picture.php"><div>
					<input type="hidden" name="type" value="promopost" />
					<input type="hidden" name="catid" value="<?php echo $NPG_CONF['blog_cat']; ?>" />
					<input type="hidden" name="hour" value="<?php echo $hour; ?>" />
					<input type="hidden" name="minutes" value="<?php echo $minutes; ?>" />
					<input type="hidden" name="day" value="<?php echo $day; ?>" />
					<input type="hidden" name="year" value="<?php echo $year; ?>" />
					<input type="hidden" name="month" value="<?php echo $month; ?>" />
					<input type="hidden" name="ids" value="<?php echo implode(",",$promo_ids); ?>" />
					
					<p><label for "title_f"><?php echo __NPG_PROMO_FORM_TITLE.'<br/>'; ?></label>
					<input type="text" name="title" id="title_f" value="<?php echo $title; ?>" /></p>
					
					<p><label for "body_f"><?php echo __NPG_PROMO_FORM_BODY.'<br/>'; ?></label>
					<textarea rows="10" cols="50" name="body" id="body_f" >
					<?php echo $body; ?></textarea></p>
					<input type="submit" value="<?php echo __NPG_PROMO_FORM_SUBMIT; ?>" />
					<br/>
					
			<?php
			
		}
		else {
			echo '<br/><hr/><br/>';
			echo '<form><input type="button" value="'.__NPG_PROMO_FORM_CLOSE.'" onclick="window.close()"/></form>';
		}
				
		
		$exist_temp_table = mysql_query('SELECT 1 FROM '.$NPG_CONF['temp_table'].' LIMIT 0');
		if ($exist_temp_table) sql_query('drop table '. $NPG_CONF['temp_table']);

		break;
	
	case 'promopost':
		global $manager;
		$ids = explode(",", postvar('ids'));
		$result = ITEM::createFromRequest();
		if ($result['status'] == 'error')
			echo $result['message']; 
			else {
				$j=0;
				while($ids[$j]) {
					$query = 'insert into '.sql_table('plug_gallery_promo').' values ('.$ids[$j].', '.$result['itemid'].')';
					sql_query($query);
					$j++;
				}
				echo __NPG_PROMO_FORM_SUCCESS.'<br/>';
				echo '<form><input type="button" value="' . __NPG_PROMO_FORM_CLOSE . '" onclick="window.close()"/></form>';
				
			}
		break;
		
	case 'picasa' :
      if(requestVar('id')) $albumid = requestVar('id'); else $albumid = 0;
      $result = mysql_query('create temporary table '.$NPG_CONF['temp_table']
                  .'(tempid int unsigned not null auto_increment PRIMARY KEY, '
                  .'memberid int unsigned, '
                  .'albumid int unsigned, '
                  .'filename varchar(60), '
                  .'intfilename varchar(60), '
                  .'thumbfilename varchar(60), '
                  .'title varchar(40), '
                  .'description varchar(255), '
                  .'promote tinyint unsigned default 0, '
                  .'error varchar(60) default NULL)' );
      //creates an xml parser and puts the xml into an array
      $p = xml_parser_create();
      if (!($fp = fopen($NP_BASE_DIR."upload/index.xml", "r"))) {die("unable to open XML");}
      $contents = fread($fp, filesize($NP_BASE_DIR."upload/index.xml"));
      xml_parse_into_struct($p,$contents,$vals,$index);
      fclose($fp);
      xml_parser_free($p);
      //get album item count and loop through the pictures, putting filename and description into the temp database
      $count = $index["ALBUMITEMCOUNT"][0];
      $albumitemcount = $vals[$count]["value"];
      for ($i=0; $i<$albumitemcount;$i++) {
         $count = $index["ITEMNAME"][$i];
         $filename = $vals[$count]["value"];
         $count = $index["ITEMCAPTION"][$i];
         $description = $vals[$count]["value"];
         $filename = trim($filename);
         $filetempname = $NP_BASE_DIR.'upload/images/' . $filename ;
         $filetype = filetype($filetempname);
         $filesize = filesize($filetempname);
         //adds a picture to the temp table so user can add description, etc before actually adding to database
         add_temp($albumid, $filename, $filetype, $filesize, $filetempname, $description);
         
      }
	
		
	default:
		break;
}




//adds a picture to the temp table so user can add description, etc before actually adding to database

function add_temp($albumid = 0, $filename, $filetype, $filesize, $filetempname, $description = '') {
	global $NPG_CONF, $gmember, $NP_BASE_DIR,$manager;
	$memberid = $gmember->getID();
	$temp_table = $NPG_CONF['temp_table'];
	$int_filename = '';
	$thumb_filename = '';
	$error = '';
	$defaulttitle = $filename;
	$NPG_CONF['randomprefix'] = 6;

	//add prefix to filename -- from http://www.phpdig.net/ref/rn22re349.html
		//or add current date to filename , option set in plugin admin
	$dateorrandom = $NPG_CONF['dateorrandom'];

	if ($dateorrandom == 'randomprefix'){
		$str = "";
		for ($i = 0; $i < $NPG_CONF['randomprefix']; ++$i) {
		$str .= chr(rand() % 26 + 97);
		}
		$filename = $str . $filename;
	}
	if ($dateorrandom == 'dateprefix'){
		$str = "";
		$str = date('Y-m-d');
		$filename = $str . $filename;
	}
	//check filesize

	if ($filesize > $NPG_CONF['max_filesize']) 
		$error = 'FILE_TOO_BIG';
	else {
		//check filetype -- currently only jpeg supported	
		if (eregi("\.jpeg$",$filename)) $ok = 1;
		if (eregi("\.jpg$",$filename)) $ok = 1;
		if (!$ok) 
			$error='BADFILETYPE';
			else {
				//check if gallery directory exists, try to create if it doesn't, check write permssions
				$mediadir = $NPG_CONF['galleryDir'];
				if (!@is_dir($NP_BASE_DIR.$mediadir)) {
					$error = 'Disallowed';
					$oldumask = umask(0000);
					if (!@mkdir($NP_BASE_DIR.$mediadir, 0777)) $error='Cannot create gallery directory'; 
					else {
						$error = NULL;
						umask($oldumask);				
					}
				}
				else {
					if (!is_writeable($NP_BASE_DIR.$mediadir)) $error = 'Gallery directory not writable';
					else {
						// add trailing slash (don't add it earlier since it causes mkdir to fail on some systems)
						$mediadir .= '/';
						//check if file already exists -- todo:if it does, add it to the database?
						if (file_exists($NP_BASE_DIR.$mediadir . $filename)) $error = 'UPLOADDUPLICATE';
						else {
							//move file : courtesy of Leslie Holmes www.CyberSparrow.com
							if (is_file($filetempname)) {
								if (@rename($filetempname,$NP_BASE_DIR. $mediadir . $filename)){
								// it worked, no problems
								} elseif (copy($filetempname,$NP_BASE_DIR. $mediadir . $filename)){
								// rename didn't work, so we tried copy and it worked, now delete original upload file
									unlink($filetempname);
								} else {
								// neither method worked, so report error
									$error = 'ERROR_UPLOADCOPY,ERROR_UPLOADMOVE' ;
								}
							}		
							//chmod file
							$oldumask = umask(0000);
							@chmod($NP_BASE_DIR.$mediadir . $filename, 0644); 
							umask($oldumask);

							if(($NPG_CONF['graphics_library']=='gd' && GDisPresent()) || ($NPG_CONF['graphics_library']=='im' && IMisPresent()) ) {
								//make intermediate file and thumbnail
								$int_filename = resizeImage($mediadir.$filename, $NPG_CONF['maxwidth'], $NPG_CONF['maxheight'], $mediadir.$NPG_CONF['int_prefix'].$filename);
								$thumb_filename = resizeImage($mediadir.$filename, $NPG_CONF['thumbwidth'], $NPG_CONF['thumbheight'], $mediadir.$NPG_CONF['thumb_prefix'].$filename);
							}
							else {
								$error = 'Graphics library not configured properly';
							}
						}
					}
				}
			}
		}

		

	

     $query = 'insert into '
      .$temp_table
      .'(tempid,memberid,albumid,filename,intfilename,thumbfilename,title,description,promote,error)'
      ." values (NULL, $memberid, $albumid, '$filename', '$int_filename', '$thumb_filename', '$defaulttitle', '$description', 0, '$error') ";
   //echo $query.'<br/>';
   $result = sql_query($query);



}


?>
