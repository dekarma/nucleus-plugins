<?php
/*
This plugin embed a mp3 with a simple player interface to a post
 
To install:
1) download and install dewplayer.swf from http://www.alsacreations.fr/dewplayer in /media of your blog
2) install plugin
 
This plugin is based on NP_FlashVideo by Kai Greve
 
v0.1 (2006-10-16): inital realease
v0.2 (2006-10-20): fixed IE6/IE7 problem
v0.3 (2005-11-17): fixed remote mp3 path bug
v0.4 (2007-01-03): Add support for skinvar (ftruscot)
v0.5 (2007-01-03): Add options for replay, autoreplay, volume, bgcolor, and which player (ftruscot)
v0.6 (2007-01-04): Fix so can have multiple mp3 files for the dewplayer-multi player (ftruscot)
v0.7 (2007-01-04): Fix authorid for skinvar. Allow filename to be a local directory. Picks random mp3 (or random playlist in case of dewplayer-multi) from the given
directory
v0.8 (2007-01-17): Fix volume bug (thanks creativebin). And added support for dewplayer-vol (see http://www.estvideo.net/dew/index/2006/05/02/707-dewplayer-1-5
(thanks creativebin).
*/
 
class NP_FlashMP3 extends NucleusPlugin {
 
    var $authorid;
 
    function getName() { return 'FlashMP3'; }
    function getAuthor() { return 'Edmond Hui (admun), mod by ftruscot'; }
    function getURL() { return 'http://edmondhui.homeip.net/nudn'; }
    function getVersion() { return '0.8'; }
    function getDescription() { return 'embed a mp3 to a post with a player interface using the tag <%flashmp3(yourvideo.mp3)%>'; }
 
    function install() {
        $this->createOption('player', 'What dewplayer version are you using?', 'select',
'dewplayer','normal|dewplayer|mini|dewplayer-mini|multi|dewplayer-multi|volume|dewplayer-vol');
        $this->createOption('autoplay', 'Should your mp3s start automatically on page load?','select','0','yes|1|no|0');
        $this->createOption('autoreplay', 'Should your mp3s restart automatically on a loop?','select','0','yes|1|no|0');
        $this->createOption('volume', 'What should be the initial volume? (0-100)','text','50');
        $this->createOption('bgcolor', 'Set the default background color (rrggbb)','text','ffffff');
    }
 
    function getEventList() {
        return array('PreItem');
    }
 
    function unInstall() {
    }
 
    function mp3Code($param) {
        global $CONF, $DIR_MEDIA;
 
        // filename
        $para=explode('|',$param[1]);
 
        // filename
        $files=explode(';', $para[0]);
                if (strpos($files[0],'://') === false && is_dir($DIR_MEDIA.$this->authorid.$files[0])) {
                        $dh  = opendir($DIR_MEDIA.$this->authorid.$files[0]);
                        while (false !== ($filename = readdir($dh))) {
                                if (!is_dir($DIR_MEDIA.$this->authorid.$files[0]."/".$filename) && strtolower(substr($filename, -4)) == '.mp3') {
                                        $dirfiles[] = "$files[0]/$filename";
                                }
                        }
                        srand((float)microtime() * 1000000);
                        shuffle($dirfiles);
                        $files = $dirfiles;
                }
        $autoplay = $para[1];
        if ($autoplay == '') $autoplay = $this->getOption('autoplay');
                if ($autoplay == 'yes') $autoplay = 1;
                if ($autoplay == 'no') $autoplay = 0;
        $autoreplay = $para[2];
        if ($autoreplay == '') $autoreplay = $this->getOption('autoreplay');
                if ($autoreplay == 'yes') $autoreplay = 1;
                if ($autoreplay == 'no') $autoreplay = 0;
        $volume = $para[3];
        if ($volume == '') $volume = $this->getOption('volume');
        $bgcolor = $para[4];
        if ($bgcolor == '') $bgcolor = $this->getOption('bgcolor');
 
        $autoplay = "&amp;autoplay=".intval($autoplay);
        $autoreplay = "&amp;autoreplay=".intval($autoreplay);
        $volume = "&amp;volume=".intval($volume);
        $bgcolor = "&amp;bgcolor=".$bgcolor;
 
        $player = $para[5];
        if ($player == '') $player = $this->getOption('player');
 
        switch ($player) {
            case 'dewplayer':
                $width = '200';
                $height = '20';
                                $files = array($files[0]);
                break;
            case 'dewplayer-mini':
                $width = '150';
                $height = '20';
                                $files = array($files[0]);
                break;
            case 'dewplayer-multi':
                $width = '240';
                $height = '20';
                break;
            case 'dewplayer-vol':
                $width = '240';
                $height = '20';
                $files = array($files[0]);
                break;
            default:
                $width = '200';
                $height = '20';
                break;
        }
 
        // url of the mp3s
                $i = 0;
                $fileurls = array();
                foreach ($files as $file) {
                        if (substr($file, 0, 7)=="http://") {
                                $fileurls[$i]=$file;
                        }
                        else {
                                $fileurls[$i]=$CONF['MediaURL'].$this->authorid.$file;
                        }
                        $i++;
                }
                $fileurl = implode('|',$fileurls);
 
        // code for the mp3 player
        $param = $CONF['MediaURL'] . $player.'.swf?mp3=' . $fileurl.$autoplay.$autoreplay.$volume.$bgcolor;
        $code = '<object type="application/x-shockwave-flash" data="'
            . $param . '" width="'.$width.'" height="'.$height.'"><param name="movie" value="'
            . $param . '" /></object>';
 
        return $code;
    }
 
    function event_PreItem($data) {
        $this->currentItem = &$data["item"];
        $this->authorid = $this->currentItem->authorid.'/';
        $this->currentItem->body =
        preg_replace_callback("#<\%flashmp3\((.*?)\)%\>#i", array(&$this, 'mp3Code'), $this->currentItem->body);
        $this->currentItem->more =
        preg_replace_callback("#<\%flashmp3\((.*?)\)%\>#i", array(&$this, 'mp3Code'), $this->currentItem->more);
    }
 
    function supportsFeature ($what) {
        switch ($what) {
            case 'SqlTablePrefix':
                return 1;
            default:
                return 0;
        }
    }
 
    function doSkinVar() {
        $params = func_get_args();
        $skinType = array_shift($params);
        $arrsize = count($params);
 
        if ($arrsize == 0 || trim($params[0]) == '') $idata = '';
        elseif ($arrsize == 1) $idata = $params[0];
        else $idata = implode('|',$params);
 
        $args = array(&$this,$idata);
        $code = $this->mp3Code($args);
        echo $code;
    }
 
}
 
?>
