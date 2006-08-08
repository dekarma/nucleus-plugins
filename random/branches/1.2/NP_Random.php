<?php
 
/**
  * This plugin can be used to insert a random line of text on your page.
  *
  * History:
  *     v1.0: initial plugin
  *     v1.1: support for multiple random files
  *     v1.2: added supportsFeature
  *
  * Install from the Nucleus Plugin Manager.
  * Make sure you have a "random.txt" file uploaded in ASCII mode
  * to your main nucleus directory (Where your index.php file is.)
  * You may use multiple instances of this plugin simply by changing
  * the random.txt file name and using the same format.
  *
  * Example:  <%Random(imagenames.txt)%>
  *
  */
 
class NP_Random extends NucleusPlugin {
 
   /**
     * Plugin data to be shown on the plugin list
     */
   function getName() {          return 'Random'; }
   function getAuthor()  {       return 'Mark Fulton | Edmond Hui (admun)'; }
   function getURL()  {          return 'http://www.slashbomb.com/'; }
   function getVersion() {         return '1.1'; }
   function getDescription() {
      return 'Displays a random line of text from a text file by using &lt;%Random(random.txt)%&gt;.  Be sure to upload a .txt file calle
d "random.txt" to the directory where your index file is.  Each line of the text file is considered an entry.';
   }
 
   function supportsFeature($what) {
      switch($what) {
        case 'SqlTablePrefix':
          return 1;
        default:
          return 0;
      }
   }
 
   function doSkinVar($skinType) {
      global $manager, $blog, $CONF;
      $params = func_get_args();
 
           $filename   = 'random.txt'; // defaults to the file random.txt
 
      if ($params[1]){
                        $filename = $params[1];
         $b =& $blog;
                }
      else if ($blog)
         $b =& $blog;
      else
         $b =& $manager->getBlog($CONF['DefaultBlog']);
 
      // randomize
 
   srand((double)microtime()*1000000);
 
        $lines = file("$filename") ;
        echo $lines[array_rand($lines)] ;
   }
 
}
 
?>

