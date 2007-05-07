<?
/*
  Note: This plugin require javascript from Garrison Locke (Thank!), please see http://www.broken-notebook.com/spell_checker/
*/

class NP_SpellCheck extends NucleusPlugin {

   function getName() { return 'SpellCheck'; }
   function getAuthor()  { return 'Edmond Hui (admun)'; }
   function getURL() { return 'http://edmondhui.homeip.net/nudn'; }
   function getVersion() { return 'v0.2'; }
   function getDescription() {
      return 'This plugin adds a Ajax-based spell checking function to Nucleus using code from http://www.broken-notebook.com/spell_checker/';
   }

   function getEventList() {
      return array('AdminPrePageHead','BookmarkletExtraHead');
      }

   function supportsFeature($what) {
     switch($what) {
       case 'SqlTablePrefix':
         return 1;
       default:
         return 0;
     }
   }

   function unInstall() {
   }
   
   function doSkinVar($skinType) {
   }

   function event_AdminPrePageHead($data) {
     if ($data['action'] == 'createitem' || $data['action'] == 'itemedit') {
       $this->insertScript();
     }
   }

   function event_BookmarkletExtraHead($data) {
       $this->insertScript();
   }

   function pluginURL() {
     global $CONF;
     return $CONF['AdminURL']."plugins/";
   }

   function insertScript() {
     echo "<script type=\"text/javascript\" src=\"".$this->pluginURL()."spellchecker/cpaint/cpaint2.inc.compressed.js\"></script>";
     echo "<script type=\"text/javascript\" src=\"".$this->pluginURL()."spellchecker/js/spell_checker.js\"></script>";
     echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->pluginURL()."spellchecker/css/spell_checker.css\" />";
   }
}
?> 
