<?
/*
   History:
     v0.1, May 5, 2004 - Initial version
     v0.2, May 6, 2004 - Fixed URL spliting
     v0.3, May 6, 2004 - Re-implement event_PreAddItem to deal with HTML properly
     v0.4, Jul 5, 2007 - code inprovement
     v1.0, Oct 15, 2007
        - only split if extended text is empty
        - add split by paragraph mode
        - per blog autoextended enable/disable option
        - option (override in add/edit) for split mode
        - add pagebreak tag <pagebreak> mode
*/
class NP_AutoExtended extends NucleusPlugin {

   function getName() { return 'AutoExtended'; }
   function getAuthor()  { return 'Edmond Hui (admun)'; }
   function getURL() { return ''; }
   function getVersion() { return 'v1.0'; }
   function getDescription() {
      return 'This plugin splits the item body into extended text if it is longer than a certain length';
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
      return array('PreAddItem', 'AddItemFormExtras');
   }

   function install() {
      $this->createOption('split_word_count','Default number of words keep in the body','text','200');
      $this->createOption('split_para_count','Default number of paragraph keep in the body','text','1');
      $this->createOption('split_mode','Default auto extend mode','select','word','word|word|paragraph|para|manual|manu');

      $this->createBlogOption('do_autoextended','Enable auto split from text to extended text?','yesno','yes');
   }

   function event_PreAddItem($data) {

      if ($data['more'] != '') return;

      if ($this->getBlogOption($data['blog']->blogid, 'do_autoextended') == "no") return;

      $mode =  requestVar('ae_mode');
      $count = requestVar('ae_count');
      if ($mode == "word") {
         $wordcount = 0;
         $tempbody = '';
         $tempext ='';
         $state = "close";

         if ($count > 0) {
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

	       if ($wordcount < $count)
	          $tempbody = $tempbody . $data['body'][$i];
	       else
	          $tempext = $tempext . $data['body'][$i];
           }

	   if ($wordcount < $count) return;

	   $data['body'] = $tempbody . "... ";
	   $data['more'] = "..." . $tempext . "\n" . $data['more'];
        }
     } elseif ($mode == "para") {
        $paragraph = explode("\r\n", $data['body']);

	echo "<!--";
	print_r($paragraph);
	echo "-->";

	$data['body'] = "";
	for ($i = 0, $p = 0; $i < sizeof($paragraph); $i++) {
	   if ($paragraph[$i] == '') continue;

	   if ($p < $count) {
	      $data['body'] .= $paragraph[$i] . "\r\n\r\n";
	   } else {
	      $data['more'] .= $paragraph[$i] . "\r\n\r\n";
	   }

	   $p++;
	}

     } else {
        $paragraph = explode("<pagebreak>", $data['body']);

	$data['body'] = $paragraph [0];
	$data['more'] = $paragraph [1];
     }
   }

   function event_AddItemFormExtras($data) {
      ?>
         <h3>AutoExtended</h3>
	 <p>
      <?

      if ($this->getBlogOption($data['blog']->blogid, 'do_autoextended') == "no") {
	 ?>
	 Function disabled for this blog<br/>
	 </p>
	 <?
      } else {
	 $mode = $this->getOption('split_mode');
	 switch ($mode) {
	    case "word":
	       $wcheck = "checked=\"checked\"";
	       $count = $this->getOption('split_word_count');
	       break;
	    case "para":
	       $pcheck = "checked=\"checked\"";
	       $count = $this->getOption('split_para_count');
	       break;
	    case "manu":
	       $mcheck = "checked=\"checked\"";
	       break;
	 }

	 ?>
	  <label>Split by: </label>
	  word <input type="radio" name="ae_mode" value="word" <? echo $wcheck; ?> />
	  paragraph <input type="radio" name="ae_mode" value="para" <? echo $pcheck; ?> />
	  manual <input type="radio" name="ae_mode" value="manu" <? echo $mcheck; ?> />
	  <br /><label>Split after: </label>
             <input type="text" name="ae_count" size="4" value="<? echo $count; ?>" /> words/paragraphs (this setting is ignored in manual mode)
	  </p>
	 <?
      }
   }

}

?>
