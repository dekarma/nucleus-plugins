<?php
 
/**
  * This plugin can be used to insert a random line of text on your page.
  *
  * History:
  *     v1.0: initial plugin
  *     v1.1: support for multiple random files
  *     v1.2: added supportsFeature
  *     v1.3: new directory for random.txt
  *           new options for the filename and ignoring empty lines
  *                 
  * Install from the Nucleus Plugin Manager.
  * Make sure you have a "random.txt" file uploaded in ASCII mode
  * to your nucleus plugin directory in the subfolder random.
  * You may use multiple instances of this plugin simply by changing
  * the random.txt file name.
  *
  * Examples:  <%Random()%>
  *            <%Random(random.txt)%>   
  *            <%Random(imagenames.txt)%>
  *              
  */
 
class NP_Random extends NucleusPlugin {
 
   // Plugin data to be shown on the plugin list
   function getName()    { return 'Random'; }
   function getAuthor()  { return 'Mark Fulton (www.slashbomb.com)| Edmond Hui (admun)| Kai Greve (kgblog.de)'; }
   function getURL()     { return 'http://wakka.xiffy.nl/random'; }
   function getVersion() { return '1.3'; }
   function getDescription() { return 'Displays a random text from a file by using &lt;%Random()%&gt; or &lt;%Random(random.txt)%&gt;.  Be sure to upload a text file called "random.txt" to your nucleus plugin directory in the subfolder random. Each line of the text file is considered as an entry.'; }
 
   function supportsFeature($what) {
      switch($what) {
        case 'SqlTablePrefix':
          return 1;
        default:
          return 0;
       }
   }
   
   function install() {
     $this->createOption('filename','File with the entries to randomize','text','random.txt');
     $this->createOption('ignore','Ignore empty lines','yesno','yes');
   }
    
   function doSkinVar($skinType) {
      global $manager, $blog, $CONF;
      $params = func_get_args();
 
      // get filename
      $filename = $this->getOption('filename');
 
      if ($params[1]){
         $filename = $params[1];
         $b =& $blog;
      }
      else if ($blog)
         $b =& $blog;
      else
         $b =& $manager->getBlog($CONF['DefaultBlog']);

      $filename = $CONF['PluginURL'].'random/'.$filename;
      
      // initialize random number generator
      srand((double)microtime()*1000000);
 
      // read file
      $lines = file("$filename");
      
      // option: ignore empty lines 
      if ($this->getOption('ignore')=='yes') {
        $entries = array();
        foreach ($lines as $l) {
          if (trim($l)!='') {
            array_push ($entries, $l);
          }
        }
        $lines = $entries;
      }
      
      // echo random entry      
      echo $lines[array_rand($lines)] ;
   }
}
?>
