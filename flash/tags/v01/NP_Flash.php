<?php

/*
Nucleus Plugin Flash (NP_Flash)

With this plugin you can post Flash Movies (swf) by using the tag <%flash(filename.swf)%>

With two additional parameters the width and hight of the flash movie can be specified: <%flash(filename.swf|width|height)%> 

v0.1: inital realease (based on NP_FlashVideo v0.3)
*/

class NP_Flash extends NucleusPlugin {

    var $authorid;

    function getName()        { return 'Flash'; }
    function getAuthor()      { return 'Kai Greve'; }
    function getURL()         { return 'http://kgblog.de/'; }
    function getVersion()     { return '0.1'; }
    function getDescription() { return 'Post a Flash Movie (swf) by using the tag <%flash(filename.swf)%> or <%flash(filename.swf|width|height)%>'; }

    function install() {
      $this->createOption('width','Default width','text','320');
      $this->createOption('height','Default height','text','240');
      $this->createOption('loop','Default for loop (true/false)','text','true');
      $this->createOption('menu','Default for menu (true/false)','text','true');
      $this->createOption('quality','Default for quality (high/low)','text','high');
   }

    function getEventList() {
       return array('PreItem');
    }

    function unInstall() {
    }

    function flashCode($param) {
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

       //parameters
       $loop=$this->getOption('loop');
       $menu=$this->getOption('menu');         
       $quality=$this->getOption('quality');
       
       // code for the flash movie
       $fileurl=$CONF['MediaURL'].$this->authorid.'/'.$filename;
       $code= '<object type="application/x-shockwave-flash" width="'.$width.'" height="'.$height.'" data="'.$fileurl.'"><param name="movie" value="'.$fileurl.'" /><param name="loop" value="'.$loop.'" /><param name="menu" value="'.$menu.'" /><param name="quality" value="'.$quality.'" /></object>';

       return $code;
    }

    function event_PreItem($data)
    {
      $this->currentItem = &$data["item"];
      $this->authorid = &$data["item"]->authorid;
      $this->currentItem->body = preg_replace_callback("#<\%flash\((.*?)\)%\>#", array(&$this, 'flashCode'), $this->currentItem->body);
      $this->currentItem->more = preg_replace_callback("#<\%flash\((.*?)\)%\>#", array(&$this, 'flashCode'), $this->currentItem->more);
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
