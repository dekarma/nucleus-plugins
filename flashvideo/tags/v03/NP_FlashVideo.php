<?php

/*
Nucleus Plugin FlashVideo

With this plugin you can post a Flash Video (flv) by using the tag <%flashvideo(yourvideo.flv)%>

The plugin requires the FLASH VIDEO PLAYER (http://www.jeroenwijering.com/?item=Flash_Video_Player) from Jeroen Wijering. The player file 'flvplayer.swf' must be in the directory /media of your blog.

v0.1: inital realease
v0.2: support for fancy urls
v0.3: using authorid instead of blogid (bug removed)
      parameters for individual width and height of the video added
*/

class NP_FlashVideo extends NucleusPlugin {

    var $authorid;

    function getName()        { return 'FlashVideo'; }
    function getAuthor()      { return 'Kai Greve'; }
    function getURL()         { return 'http://kgblog.de/'; }
    function getVersion()     { return '0.3'; }
    function getDescription() { return 'Post a Flash Video (flv) by using the tag <%flashvideo(yourvideo.flv)%> or <%flashvideo(yourvideo.flv)|width|height%>'; }

    function install() {
      $this->createOption('width','Default width','text','320');
      $this->createOption('height','Default height','text','240');
      $this->createOption('autostart','Autostart video','yesno','no');
   }

    function getEventList() {
       return array('PreItem');
    }

    function unInstall() {
    }

    function flvCode($param) {
       global $CONF;

       // explode parameters
       $para=explode('|',$param[1]);

       // filename
       $filename=$para[0];

       // size
       if (isset($para[1]) || isset($para[2])){
         $width=$para[1];
         $height=$para[2];       
       }
       else {
         $width=$this->getOption('width');
         $height=$this->getOption('height');
       }

       // autostart
       if ($this->getOption('autostart')=="yes") {
         $autostart="";
       }
       else {
         $autostart="&amp;autoStart=false";
       }         

       // code
       $fileurl=$CONF['MediaURL'].$this->authorid.'/'.$filename;
       $code= '<object type="application/x-shockwave-flash" width="'.$width.'" height="'.$height.'" data="'.$CONF['MediaURL'].'flvplayer.swf?file='.$fileurl.$autostart.'"><param name="movie" value="'.$CONF['MediaURL'].'flvplayer.swf?file='.$fileurl.$autostart.'" /><param name="wmode" value="transparent" /></object>';

       return $code;
    }

    function event_PreItem($data)
    {
      $this->currentItem = &$data["item"];
      $this->authorid = &$data["item"]->authorid;
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
