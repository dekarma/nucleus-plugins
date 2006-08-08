<?php
//NP_gallery config

global $DIR_NUCLEUS,$DIR_LIBS;

global $NP_GALLERY_DIR, $NP_BASE_DIR;
$NP_GALLERY_DIR = $DIR_NUCLEUS . 'plugins/gallery/';
$NP_BASE_DIR = substr($DIR_NUCLEUS,0,strlen($DIR_NUCLEUS) - 8);


include_once($NP_GALLERY_DIR.'functions.php');
include_once($NP_GALLERY_DIR.'list_class.php');
include_once($NP_GALLERY_DIR.'album_class.php');
include_once($NP_GALLERY_DIR.'picture_class.php');
include_once($NP_GALLERY_DIR.'member_class.php');
include_once($NP_GALLERY_DIR.'forms.php');
include_once($NP_GALLERY_DIR.'admin.php');
include_once($NP_GALLERY_DIR.'template.php');
include_once($NP_GALLERY_DIR.'comments.php');
include_once($NP_GALLERY_DIR.'language/english.php'); //change this for different language

global $NPG_CONF, $member, $gmember;
$NPG_CONF = getNPGConfig();
$gmember = new GALLERY_MEMBER;
if($member->getID()) $gmember->readFromID($member->getID()); else $gmember->makeguest();
$gmember->loggedin = $member->isloggedin();

?>
