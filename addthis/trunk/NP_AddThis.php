<?
/*
 v0.1 initial release
 v0.2 fix XHTML compliant
 v0.3 improve to get item URL from internal instead of via a javascript
      grab the current URL. This way this plugin cna used in index and not
      screw up when the item URL somehow changed....
 v0.4 fixed item URL rollover on index page when multiple posts are displayed
 v0.5 XHTML 1.0 strict (thanks, Dark Wraith)
 */

class NP_AddThis extends NucleusPlugin {

   function getName() { return 'AddThis'; }
   function getAuthor()  { return 'Edmond Hui (admun)'; }
   function getURL() { return 'http://edmondhui.homeip.net/nudn'; }
   function getVersion() { return 'v0.5'; }
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
     $this->createOption('iconText','Label of the icon', 'text', "<img src=\"http://s3.addthis.com/button1-bm.gif\" style=\"border: none;vertical-align: middle;width: 125;height: 16;\" alt=\"AddThis Social Bookmark Button\" />");

     $this->createMemberOption('AddThisUser','AddThis login','text','');
     //$this->createMemberOption('AddThisPassword','AddThis password','password','');
   }

   function resetCode() {
     $this->iconCode = "
     <!-- AddThis Bookmark Button BEGIN -->
     <a href=\"http://www.addthis.com/bookmark.php\" onclick=\"addthis_url = '<%URL%>'; addthis_title = '<%TITLE%>'; return addthis_click(this);\" rel=\"external\"><%TEXT%></a> <script type=\"text/javascript\">var addthis_pub = '<%PUB%>';</script><script type=\"text/javascript\" src=\"http://s9.addthis.com/js/widget.php?v=10\"></script>
<!-- AddThis Bookmark Button END -->
     ";
   }

   function init() {
     $this->pub="YICPV6P3SU2Y7L7F";
     $this->resetCode();
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

     $this->iconCode = str_replace('<%URL%>',createItemLink($item->itemid),$this->iconCode);
     $this->iconCode = str_replace('<%TITLE%>',$item->title,$this->iconCode);

     echo $this->iconCode;
     $this->resetCode();
   }
}
?> 
