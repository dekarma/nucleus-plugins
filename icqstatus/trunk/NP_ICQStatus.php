<?php
/*
  v0.2 - initial release
  v0.3 - fix missing ICQ# file error 
*/
class NP_ICQStatus extends NucleusPlugin {

   function getName() { return 'ICQStatus'; }
   function getAuthor()  { return 'Edmond Hui (admun), using code from radek'; }
   function getURL() { return 'http://www.nowhere.com'; }
   function getVersion() { return 'v0.3'; }
   function getDescription() {
      return 'This plugin display the status of a ICQ user';
   }

   function supportsFeature($what) {
     switch($what) {
       case 'SqlTablePrefix':
         return 1;
       default:
         return 0;
     }
   }

   function install() {
     $this->createOption('smode','Silent mode? (Only display the status)','yesno','no');
     $this->createOption('dirpath','Location of member ICQ# file (ShortName.icq) ','text','/var/www/html/some/path/');
     $this->createOption('on','Text for Online status ','text','online');
     $this->createOption('off','Text for Offline status ','text','offline');
     $this->createOption('unknown','Text for Unknown status ','text','unknown');
   }

   function unInstall() {
   }
   
   function doSkinVar($skinType, $icq = 'unknown') {
     global $member, $memberid;

     $dir = $this->getOption('dirpath');
     if ($skinType == 'member') {
       $myself = $member->createFromID($memberid);
       $name = $myself->getDisplayName();
       $filename = $dir . $name . ".icq";
       if (!file_exists($filename)){
	 echo $this->getOption('unknown');
         return;
       }

       $fp = @fopen($filename, 'r');
       $icq = intval(fread($fp, filesize($filename)));
       fclose($fp);
     }

     $icqstat=$this->getOption('off'); 
     $fp = fsockopen("status.icq.com", 80); 
     if($fp) { 
       fputs($fp, "GET /online.gif?icq=$icq&img=5 HTTP/1.0\n\n");
       while ($line=FGetS($fp,128)) { 
         if (ERegI("^Location:.*$", $line)) { 
           if (ERegI("online1",$line)) {
	     //$icqstat="<div style='color:darkgreen;font-weight:bold'>Online</div>"; 
	     $icqstat=$this->getOption('on'); 
	   }
           break; 
         } 
       } 
     } 

     if ($this->getOption('smode') == 'yes')
       $outs = $icqstat; 
     else
       $outs = $icq.': '. $icqstat . "<br />"; 

     echo $outs;
   }
}
?>
