<?php
session_start();

$strRel = '../../../'; 
include($strRel . 'config.php');

$ss =& $manager->getPlugin('NP_SubmitSystem');

if ($ss->getOption('captcha') == 'no' || empty($_SESSION['captcha'])) {
	exit;
}

header('Content-Type: image/png');
$img = imageCreate(120, 30);
$back = imageColorAllocate($img, rand(200, 255), rand(200, 255), rand(200, 255));
$lines = array();
for ($i = 0; $i < 5; $i++) {
	$lines[] = imageColorAllocate($img, rand(200, 255), rand(200, 255), rand(200, 255));
}
$fronts = array();
for ($i = 0; $i < 5; $i++) {
	$fronts[] = imageColorAllocate($img, rand(0, 128), rand(0, 128), rand(0, 128));
}

imageFill($img, 0, 0, $back);

for ($i = 0; $i < 30; $i++) {
	$x1 = rand(0, 120);
	$y1 = rand(0, 30);
	$x2 = rand(0, 120);
	$y2 = rand(0, 30);
	imageLine($img, $x1, $y1, $x2, $y2, $lines[rand(0, 4)]);  
}

$text = $_SESSION['captcha'];
$x = 0;
for ($i = 0; $i < strlen($text); $i++) {
	$font = rand(3, 5);
	$x = $x + rand(12, 20);
	$y = rand(7, 12); 
	imageString($img, $font, $x, $y, $text[$i], $fronts[rand(0, 4)]); 
}

imagePNG($img);
imageDestroy($img); 

?>