<?
/*
   History: 
     v0.2 - Added cookie check as suggested on wiki
     v0.3 - use sql_table, add supportsFeature
*/
// to make this plugin works on Nucleus versions <=2.0 as well
if (!function_exists('sql_table'))
{
    function sql_table($name) { return 'nucleus_' . $name; }
}

class NP_New extends NucleusPlugin {

 function getName() { return 'New';  }
 function getAuthor()  { return 'a n a n d | admun (Edmond Hui)';  }
 function getURL() {  return 'http://tamizhan.com/'; }
 function getVersion() { return '0.3'; }
 function getDescription() { 
  return 'New stories using last visit cookies.';
 }

 function supportsFeature($feature) {
  switch($feature) {
   case 'SqlTablePrefix':
    return 1;
   default:
    return 0;
   }
 }
 
 function install() {
    $this->createOption('MessagePrefix','What should be the message prefix ?','text','Welcome back');  
              $this->createOption('GuestName','What do we call the guest ?','text','Guest');  
              $this->createOption('MessageSuffix','What should be the message suffix ?','text',', You last visited on');  
              $this->createOption('DateFormat','What should be the date format ?','text','Y-m-d,  H:i:s');  

              $this->createOption('NoPostMessage','What should be the message when there are no new posts ?','text','No new posts since your last visit.');  
              $this->createOption('NewPostMessage','What should be the new post message ?','text',' new posts since your last visit : ');  

              $this->createOption('Untitled','Title for entry which has no title ?','text','Untitled');  
              $this->createOption('NumberOfEntries','Number of entries ?','text','10');  

 }
 
 function unInstall() { 
 }
  
 function doSkinVar($skinType) {
         if ( ! is_null( cookieVar('lastVisit') ) && 
              ! cookieVar('lastVisit') == 0 ) {

                $prefix = $this->getOption('MessagePrefix');

                if ( cookieVar('user') )
                  $name = cookieVar('user');
                else if ( cookieVar('comment_user') )
                    $name = cookieVar('comment_user');
                else
                    $name = $this->getOption('GuestName');

                $suffix = $this->getOption('MessageSuffix');

                echo $prefix.' '.$name.$suffix.' '.date($this->getOption('DateFormat'),cookieVar('lastVisit')).'.';

                echo "<br><br>";

                $this->displayNewPostLinks(cookieVar('lastVisit'));
         }
 }

        function displayNewPostLinks($timestamp){
                global $manager, $blog, $CONF; 

                $FancyURLs = 1; // Change this to 0 if you are NOT using the new fancy URLs in nucleusDev1.99 . 

                 if ($blog) 
                   $b =& $blog; 
                 else 
                   $b =& $manager->getBlog($CONF['DefaultBlog']); 

                $dateString = mysqldate($timestamp);

                $query = 'SELECT i.inumber as itemid, i.ititle as title' 
                               . ' FROM '. sql_table('item') . ' as i' 
                               . ' WHERE i.iblog='.$b->blogid 
                               . ' and i.idraft=0' // exclude drafts 
                               . ' and i.itime > ' . $dateString
                               . ' ORDER by itime DESC LIMIT' . ' 0,10'; 

                $entries = sql_query($query); 

                $this->count = mysql_num_rows($entries);

                if ( !$this->count ) {
                  echo $this->getOption('NoPostMessage');
                }
                else {

                      echo $this->count.$this->getOption('NewPostMessage')."<br>";
                      
                      while ($row = mysql_fetch_assoc ($entries)){ 

                           $itemlink = createItemLink($row['itemid'],''); 
                           $itemname = $this->getOption('Untitled');
                           $itemlinkprefix = "";

                           if ( $row['title'] ) 
                             $itemname = $row['title'];

                       if ( $FancyURLs ) { 
                       } 
                       else {                          
                           $itemlinkprefix = $b->getURL(); 
                       } 
                       echo "<a href=\"".$itemlinkprefix.$itemlink."\">".$itemname."</a>"; 

                       echo "<br>"; 
                    }
                 } 
        } 
}
?>
