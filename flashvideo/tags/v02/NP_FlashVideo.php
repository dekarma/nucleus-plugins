<?php

/*
Nucleus Plugin FlashVideo

With this plugin you can post a Flash Video (flv) by using the tag <%flashvideo(yourvideo.flv)%>

The plugin requires the FLASH VIDEO PLAYER (http://www.jeroenwijering.com/?item=Flash_Video_Player) from Jeroen Wijering. The player file 'flvplayer.swf' must be in the directory /media of your blog.

v0.1: inital realease
v0.2: support for fancy urls
*/

class NP_FlashVideo extends NucleusPlugin {

    function getName()        { return 'FlashVideo'; }
    function getAuthor()      { return 'Kai Greve'; }
    function getURL()         { return 'http://kgblog.de/'; }
    function getVersion()     { return '0.2'; }
    function getDescription() { return 'Post a Flash Video (flv) by using the tag <%flashvideo(yourvideo.flv)%>'; }

    function install() {
      $this->createOption('width','Width','text','320');
      $this->createOption('height','Height','text','240');
      $this->createOption('autostart','Autostart video','yesno','no');
   }

    function getEventList() {
       return array('PreItem');
    }

    function unInstall() {
    }

    function flvCode($param) {
       global $CONF, $blog;

       if ($this->getOption('autostart')=="yes") {
         $autostart="";
       }
       else {
         $autostart="&amp;autoStart=false";
       }

       $filename=substr($param[1], 0, strpos($param[1], ".flv")+4);
       $fileurl=$CONF['MediaURL'].$blog->blogid.'/'.$filename;

       $code= '<object type="application/x-shockwave-flash" width="'.$this->getOption('width').'" height="'.$this->getOption('height').'" data="'.$CONF['MediaURL'].'flvplayer.swf?file='.$fileurl.$autostart.'"><param name="movie" value="'.$CONF['MediaURL'].'flvplayer.swf?file='.$fileurl.$autostart.'" /><param name="wmode" value="transparent" /></object>';

       return $code;
    }

    function event_PreItem($data)
    {
      $this->currentItem = &$data["item"];
      $this->currentItem->body = preg_replace_callback("#<\%flashvideo\((.*?)\)%\>#", array(&$this, 'flvCode'), $this->currentItem->body);
      $this->currentItem->more = preg_replace_callback("#<\%flashvideo\((.*?)\)%\>#", array(&$this, 'flvCode'), $this->currentItem->more);
    }

    function supportsFeature ($what)
    {
        switch ($what)
        {
            case 'SqlTablePrefix':
                return 1;
            default:
                return 0;
        }
    }
}
?>
