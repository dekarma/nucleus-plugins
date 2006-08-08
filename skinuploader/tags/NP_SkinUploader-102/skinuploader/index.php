<?php

function uploadSkin(&$tempfile, &$skinname, $replace = false) {
	global $DIR_SKINS;
	if (!is_uploaded_file($_FILES['skin']['tmp_name'])) {
		return 'Uploading failed...';
	}
	if (!in_array(end(explode('.', $_FILES['skin']['name'])), array('zip', 'gz', 'tgz', 'tar', 'bz2'))) {
		return 'Extension unknown...';
	}
	if (end(explode('.', $_FILES['skin']['name'])) == 'bz2' && !extension_loaded('bzip2')) {
		PEAR::loadExtension('bz2');
		if (!extension_loaded('bz2')) {
			return 'The extension "bz2" isn\'t loaded...';
		}
	}

	$tempfile = $DIR_SKINS . $_FILES['skin']['name'];
	@copy($_FILES['skin']['tmp_name'], $tempfile);

	if (end(explode('.', $_FILES['skin']['name'])) == 'tar') {
		$archive = new Archive_Tar($tempfile, null);
		if (!$archive) {
			return 'Couldn\'t load the archive...';
		}
		$content = $archive->listContent();
		$startdirs = array();
		foreach ($content as $file) {
			$exp = reset(explode('/', $file['filename']));
			if (count(explode('/', $file['filename'])) == 1) {
				$exp = '';
			}
			if (!in_array($exp, $startdirs)) {
				$startdirs[] = $exp;
			}
		}
		if (count($startdirs) == 1 && !empty($startdirs[0])) {
			$name = $startdirs[0];
			$extract = $DIR_SKINS;
		}
		else {
			$name = reset(explode('.', $_FILES['skin']['name']));
			$extract = $DIR_SKINS . $name;
		}
		if ($replace == false && file_exists($DIR_SKINS . $name)) {
			return 'There is already a skin called "' . $name . '"';
		}
		if (!$archive->extract($extract)) {
			return 'Couldn\'t extract the archive...';
		}
		$skinname = $name;
		echo('Succesfully added "' . $name . '"!');
	}
	elseif (in_array(end(explode('.', $_FILES['skin']['name'])), array('tgz', 'gz'))) {
		$archive = new Archive_Tar($tempfile, 'gz');
		if (!$archive) {
			return 'Couldn\'t load the archive...';
		}
		$content = $archive->listContent();
		$startdirs = array();
		foreach ($content as $file) {
			$exp = reset(explode('/', $file['filename']));
			if (count(explode('/', $file['filename'])) == 1) {
				$exp = '';
			}
			if (!in_array($exp, $startdirs)) {
				$startdirs[] = $exp;
			}
		}
		if (count($startdirs) == 1 && !empty($startdirs[0])) {
			$name = $startdirs[0];
			$extract = $DIR_SKINS;
		}
		else {
			$name = reset(explode('.', $_FILES['skin']['name']));
			$extract = $DIR_SKINS . $name;
		}
		if ($replace == false && file_exists($DIR_SKINS . $name)) {
			return 'There is already a skin called "' . $name . '"';
		}
		if (!$archive->extract($extract)) {
			return 'Couldn\'t extract the archive...';
		}
		$skinname = $name;
		echo('Succesfully added "' . $name . '"!');
	}
	elseif (end(explode('.', $_FILES['skin']['name'])) == 'bz2') {
		$archive = new Archive_Tar($tempfile, 'bz2');
		if (!$archive) {
			return 'Couldn\'t load the archive...';
		}
		$content = $archive->listContent();
		$startdirs = array();
		foreach ($content as $file) {
			$exp = reset(explode('/', $file['filename']));
			if (count(explode('/', $file['filename'])) == 1) {
				$exp = '';
			}
			if (!in_array($exp, $startdirs)) {
				$startdirs[] = $exp;
			}
		}
		if (count($startdirs) == 1 && !empty($startdirs[0])) {
			$name = $startdirs[0];
			$extract = $DIR_SKINS;
		}
		else {
			$name = reset(explode('.', $_FILES['skin']['name']));
			$extract = $DIR_SKINS . $name;
		}
		if ($replace == false && file_exists($DIR_SKINS . $name)) {
			return 'There is already a skin called "' . $name . '"';
		}
		if (!$archive->extract($extract)) {
			return 'Couldn\'t extract the archive...';
		}
		$skinname = $name;
		echo('Succesfully added "' . $name . '"!');
	}
	elseif (end(explode('.', $_FILES['skin']['name'])) == 'zip') {
		$archive = new Archive_Zip($tempfile);
		if (!$archive) {
			return 'Couldn\'t load the archive...';
		}
		$content = $archive->listContent();
		$startdirs = array();
		foreach ($content as $file) {
			$exp = reset(explode('/', $file['filename']));
			if (count(explode('/', $file['filename'])) == 1) {
				$exp = '';
			}
			if (!in_array($exp, $startdirs)) {
				$startdirs[] = $exp;
			}
		}
		if (count($startdirs) == 1 && !empty($startdirs[0])) {
			$name = $startdirs[0];
			$extract = $DIR_SKINS;
		}
		else {
			$name = reset(explode('.', $_FILES['skin']['name']));
			$extract = $DIR_SKINS . $name;
		}
		if ($replace == false && file_exists($DIR_SKINS . $name)) {
			return 'There is already a skin called "' . $name . '"';
		}
		if (!$archive->extract(array('add_path' => $extract))) {
			return 'Couldn\'t extract the archive...';
		}
		$skinname = $name;
		echo('Succesfully added "' . $name . '"!');
	}
}

function chmodAll($path, $filemode) {
	if (!is_dir($path)) {
		return chmod($path, $filemode);
	}

	$dh = opendir($path);
	while ($file = readdir($dh)) {
		if ($file != '.' && $file != '..') {
			$fullpath = $path . '/' . $file;
			if (!is_dir($fullpath)) {
				if (!chmod($fullpath, $filemode)) {
					return false;
				}
			}
			else {
				if (!chmodAll($fullpath, $filemode)) {
					return false;
				}
			}
		}
	}

	closedir($dh);

	if (chmod($path, $filemode)) {
		return true;
	}
	else {
		return false;
	}
}

$strRel = '../../../'; 
include($strRel . 'config.php');
include($DIR_LIBS . 'PLUGINADMIN.php');
set_include_path(get_include_path() . PATH_SEPARATOR . $DIR_PLUGINS . 'pear');
require_once('Archive/Tar.php');
require_once('Archive/Zip.php');

/**
  * Create admin area
  */

$oPluginAdmin  = new PluginAdmin('SkinUploader');
$ss =& $manager->getPlugin('NP_SkinUploader');

$oPluginAdmin->start();

if (!$member->isLoggedIn() || !$member->isAdmin()) {
	echo '<p>' . _ERROR_DISALLOWED . '</p>';
	$oPluginAdmin->end();
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$tempfile = null;
	$skinname = null;
	$replace = false;
	if ($_POST['replace'] == 'ok') {
		$replace = true;
	}
	$error = uploadSkin(&$tempfile, &$skinname, $replace);
	if (file_exists($goaldir . $skinname . '/')) {
		@chmodAll($goaldir . $skinname . '/', 0777);
	}
	if (!empty($tempfile) && file_exists($tempfile)) {
		@unlink($tempfile);
	}
}

echo('<p>Using this plugin you can upload skins in the zip format and, if your host supports it, also tar.gz and tar.bz2.<br />');
echo('Please note that the zip file must contain one directory with all skinfiles in it.</p>');

if (!extension_loaded('zlib')) {
	PEAR::loadExtension('zlib');
}
if (!extension_loaded('zlib')) {
	echo('<p><span style="font-weight: bold; color: #FF0000;">The extension "zlib" isn\'t loaded...</span></p>');
}
else {
	echo('<h3>Upload a skin</h3>');
	echo('<form action="' . $_SERVER['PHP_SELF'] . '" method="post" enctype="multipart/form-data">');
	echo('<input type="file" name="skin" /><br />');
	echo('Replace if exists? <input type="checkbox" name="replace" value="ok" /><br />');
	echo('<input type="submit" value="Load up!" />');
	echo('</form>');
}

if (!empty($error)) {
	echo('<h3>Console</h3>');
	echo('<p><span style="font-weight: bold; color: #FF0000;">' . $error . '</span></p>');
}

$oPluginAdmin->end();
	
?>