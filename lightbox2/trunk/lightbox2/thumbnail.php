<?php
$msize = intval($_REQUEST['size']);
if (!$msize) $msize = 100;
define('MAX_WIDTH', $msize);
define('MAX_HEIGHT', $msize);


# Get image location
$image_file = str_replace('..', '', $_GET['image']);
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
    $img = @imagecreatefromjpeg($image_path);
	$imgtype = 'jpg';
} else if ($ext == 'png') {
    $img = @imagecreatefrompng($image_path);
	$imgtype = 'png';
# Only if your version of GD includes GIF support
} else if ($ext == 'gif') {
	if (function_exists('imagecreatefromgif')) {
		$img = @imagecreatefromgif($image_path);
		$imgtype = 'gif';
	}
} else if ($ext == 'bmp') {
    $img = @imagecreatefromwbmp($image_path);
	$imgtype = 'bmp';
}

# If an image was successfully loaded, test the image for size
if ($img) {

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