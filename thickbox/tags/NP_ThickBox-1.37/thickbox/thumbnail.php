<?php
$plugname = 'NP_ThickBox';
$try_cache = 0;
$use_cache = 0;
$make_cache = 0;
include ('../../../config.php');
global $manager;
if ($manager->pluginInstalled($plugname)) {
    $plugin =& $manager->getPlugin($plugname);
	$media_path = $plugin->getOption('imagePath');
	$cache_path = $media_path."thumb_cache";
}
if ($media_path && is_writable($media_path)) {
	if (!file_exists($cache_path)) {
		mkdir($cache_path,0777);
	}
	if (is_writable($cache_path)) {
		$cache_path .= "/";
		$try_cache = 1;
	}
}
$msize = intval($_REQUEST['size']);
if (!$msize) $msize = 100;
define('MAX_WIDTH', $msize);
define('MAX_HEIGHT', $msize);


# Get image location
$image_file = str_replace('..', '', $_GET['image']);
$local_file = $media_path.$image_file;
if (file_exists($local_file) && $try_cache) {
	$cache_filename = $msize.'_'.filemtime($local_file).'-'.str_replace(array('/','\\'),array('_','_'),$image_file);
	$cache_file = $cache_path.$cache_filename;
	if (file_exists($cache_file)) {
		$use_cache = 1;
	}
	else {
		$make_cache = 1;
	}
}
if (!empty($_GET['path'])) {
	$image_path = $_GET['path'] . "$image_file";
}
else {
	$image_path = $image_file;
}


# Load image
$img = null;
$ext = strtolower(end(explode('.', $image_path)));
if ($ext == 'jpg' || $ext == 'jpeg') {
	if ($use_cache) {
		$img = @imagecreatefromjpeg($cache_file);
	}
    else {
		$img = @imagecreatefromjpeg($image_path);
	}
	$imgtype = 'jpg';
} else if ($ext == 'png') {
    if ($use_cache) {
		$img = @imagecreatefrompng($cache_file);
	}
    else {
		$img = @imagecreatefrompng($image_path);
	}
	$imgtype = 'png';
# Only if your version of GD includes GIF support
} else if ($ext == 'gif') {
	if (function_exists('imagecreatefromgif')) {
		if ($use_cache) {
			$img = @imagecreatefromgif($cache_file);
		}
		else {
			$img = @imagecreatefromgif($image_path);
		}
		$imgtype = 'gif';
	}
} else if ($ext == 'bmp') {
	if ($use_cache) {
		$img = @imagecreatefromwbmp($cache_file);
	}
    else {
		$img = @imagecreatefromwbmp($image_path);
	}
    $imgtype = 'bmp';
}

# If an image was successfully loaded, test the image for size
if ($img) {
	if (!$use_cache) {
		# Get image size and scale ratio
		$width = imagesx($img);
		$height = imagesy($img);
		$scale = min(MAX_WIDTH/$width, MAX_HEIGHT/$height);

		# If the image is larger than the max shrink it
		if ($scale < 1) {
			$new_width = floor($scale*$width);
			$new_height = floor($scale*$height);

			# Create a new temporary image
			$tmp_img = imagecreatetruecolor($new_width, $new_height);

			# Copy and resize old image into new image
			imagecopyresampled($tmp_img, $img, 0, 0, 0, 0,
							 $new_width, $new_height, $width, $height);
			imagedestroy($img);
			$img = $tmp_img;
		}
		if ($make_cache) {
			switch ($imgtype) {
			case 'jpg':
				imagejpeg($img,$cache_file);
				chmod($cache_file,0666);
			break;
			case 'png':
				imagepng($img,$cache_file);
				chmod($cache_file,0666);
			break;
			case 'gif':
				imagegif($img,$cache_file);
				chmod($cache_file,0666);
			break;
			case 'bmp':
				imagewbmp($img,$cache_file);
				chmod($cache_file,0666);
			break;
			default:
			break;
			}
		}
	}
}

# Create error image if necessary
if (!$img) {
    $img = imagecreate(MAX_WIDTH, MAX_HEIGHT);
    imagecolorallocate($img,0,0,0);
    $c = imagecolorallocate($img,255,0,0);
    imageline($img,0,0,MAX_WIDTH,MAX_HEIGHT,$c);
    imageline($img,MAX_WIDTH,0,0,MAX_HEIGHT,$c);
	$imgtype = 'jpg';
}

# Display the image
switch ($imgtype) {
	case 'jpg':
		header("Content-type: image/jpeg");
		imagejpeg($img);
		break;
	case 'png':
		header("Content-type: image/png");
		imagepng($img);
		break;
	case 'gif':
		header("Content-type: image/gif");
		imagegif($img);
		break;
	case 'bmp':
		header("Content-type: image/wbmp");
		imagewbmp($img);
		break;
}

?>