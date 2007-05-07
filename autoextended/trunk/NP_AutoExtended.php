<?
/*
   History:
     v0.1, May 5, 2004 - Initial version
     v0.2, May 6, 2004 - Fixed URL spliting
     v0.3, May 6, 2004 - Re-implement event_PreAddItem to deal with HTML properly

   admun TODO:
     - add pagebreak tag <pagebreak>
     - add split by paragraph mode
*/
class NP_AutoExtended extends NucleusPlugin {

   function getName() { return 'AutoExtended'; }
   function getAuthor()  { return 'Edmond Hui (admun)'; }
   function getURL() { return ''; }
   function getVersion() { return 'v0.3'; }
   function getDescription() {
      return 'This plugin splits the item body into extended text if it is longer than a certain size';
   }

   function supportsFeature($what) {
      switch($what) {
        case 'SqlTablePrefix':
          return 1;
        default:
          return 0;
      }
   }

   function getEventList() {
      return array('PreAddItem');
   }

   function install() {
      $this->createOption('split_word_count','Number of words keep in the body','text','20');
   }

   function event_PreAddItem($data) {
     $state = "close";
     $wordcount = 0;
     $tempbody = '';
     $tempext ='';
     for ($i=0; $i < strlen($data['body']); $i++) {
       switch ($data['body'][$i]) {
         case '<':
           $state = "open";
           break;
         case '>':
           if ($state == "close") break;
           $state = "close";
           break;
         case ' ':
           if ($state == "close") $wordcount++;
           break;
         default:
           break;
       }

       if ($wordcount < $this->getOption('split_word_count'))
         $tempbody = $tempbody . $data['body'][$i];
       else
         $tempext = $tempext . $data['body'][$i];
     }

     if ($wordcount < $this->getOption('split_word_count')) return;

     $data['body'] = $tempbody . "... ";
     $data['more'] = "..." . $tempext . "\n" . $data['more'];
   }
}

?>
