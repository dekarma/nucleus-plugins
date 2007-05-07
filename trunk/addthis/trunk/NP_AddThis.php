<?
/*
 v0.1 initial release
 v0.2 fix XHTML compliant
 */

class NP_AddThis extends NucleusPlugin {

   function getName() { return 'AddThis'; }
   function getAuthor()  { return 'Edmond Hui (admun)'; }
   function getURL() { return 'http://edmondhui.homeip.net/nudn'; }
   function getVersion() { return 'v0.2'; }
   function getDescription() {
      return 'This plugin adds AddThis bookmark button integration';
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
     $this->createOption('iconText','Label of the icon', 'text', '<img src="http://s3.addthis.com/button1-bm.gif" width="125" height="16" border="0" alt="AddThis Social Bookmark Button" />');

     $this->createMemberOption('AddThisUser','AddThis login','text','');
     //$this->createMemberOption('AddThisPassword','AddThis password','password','');
   }

   function init() {
     $this->pub="YICPV6P3SU2Y7L7F";
     $this->iconCode = "
     <!-- AddThis Bookmark Button BEGIN -->
<a href=\"http://www.addthis.com/bookmark.php\" onclick=\"window.open('http://www.addthis.com/bookmark.php?wt=nw&amp;pub=<%PUB%>&amp;url='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title), 'addthis', 'scrollbars=yes,menubar=no,width=620,height=520,resizable=yes,toolbar=no,location=no,status=no,screenX=200,screenY=100,left=200,top=100'); return false;\" title=\"Bookmark using any bookmark manager!\" target=\"_blank\"><%TEXT%></a>
<!-- AddThis Bookmark Button END -->
     ";
   }

   function doTemplateVar(&$item) {
     $mem = MEMBER::createFromName($item->author);
     $authorid = $mem->getId();
     $userpub = $this->getMemberOption($authorid,'AddThisUser');
     if ($userpub != "") {
       $this->pub = $userpub;
     }

     $this->iconCode = str_replace('<%TEXT%>',$this->getOption('iconText'),$this->iconCode);
     $this->iconCode = str_replace('<%PUB%>',$this->pub,$this->iconCode);

     echo $this->iconCode;
   }
}
?> 
