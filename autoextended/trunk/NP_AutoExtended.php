<?php
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
    v1.1, Apr 29, 2008 (ftruscot)
        - add events to make it work on edit as well.
*/
class NP_AutoExtended extends NucleusPlugin {

   function getName() { return 'AutoExtended'; }
   function getAuthor()  { return 'Edmond Hui (admun)'; }
   function getURL() { return ''; }
   function getVersion() { return '1.1'; }
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
      return array('PreAddItem', 'AddItemFormExtras', 'PreUpdateItem', 'PrepareItemForEdit', 'EditItemFormExtras');
   }

   function install() {
		$this->createOption('split_word_count','Default number of words keep in the body','text','200');
		$this->createOption('split_para_count','Default number of paragraph keep in the body','text','1');
		$this->createOption('split_mode','Default auto extend mode','select','word','word|word|paragraph|para|manual|manu');
		$this->createOption('disable_ellipsis','Disable the auto-ellipsis (...) feature','yesno','no');
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
		
				if ($this->getOption('disable_ellipsis') == 'yes') {
					$data['body'] = $tempbody;
					$data['more'] = $tempext . "\n" . $data['more'];
				}
				else {
					$data['body'] = $tempbody . "... ";
					$data['more'] = "..." . $tempext . "\n" . $data['more'];
				}
			}
		} 
		elseif ($mode == "para") {
			$paragraph = explode("\r\n", $data['body']);
/*
	echo "<!--";
	print_r($paragraph);
	echo "-->";
*/
			$data['body'] = "";
			for ($i = 0, $p = 0; $i < sizeof($paragraph); $i++) {
				if ($paragraph[$i] == '') continue;

				if ($p < $count) {
					$data['body'] .= $paragraph[$i] . "\r\n\r\n";
				} 
				else {
					$data['more'] .= $paragraph[$i] . "\r\n\r\n";
				}

				$p++;
			}

		} 
		else {
			$paragraph = explode("<pagebreak>", $data['body']);

			$data['body'] = $paragraph [0];
			$data['more'] = $paragraph [1];
		}
	}
   
   function event_PreUpdateItem($data) {

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

				if ($this->getOption('disable_ellipsis') == 'yes') {
					$data['body'] = $tempbody;
					$data['more'] = $tempext . "\n" . $data['more'];
				}
				else {
					$data['body'] = $tempbody . "... ";
					$data['more'] = "..." . $tempext . "\n" . $data['more'];
				}
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
   
   function event_PrepareItemForEdit($data) {
		if ($data['item']['more'] == '') return;
		if ($this->getBlogOption($data['blog']->blogid, 'do_autoextended') == "no") return;
		
		if (substr(trim($data['item']['body']),-3,3) == '...' && substr(trim($data['item']['more']),0,3) == '...') {
			$data['item']['body'] = trim(substr($data['item']['body'],0,-4))." ".trim(substr($data['item']['more'],3));
			$data['item']['more'] = '';
		}
		else {
			$data['item']['body'] = $data['item']['body'].$data['item']['more'];
			$data['item']['more'] = '';
		}

   }

   function event_AddItemFormExtras($data) {
      ?>
         <h3>AutoExtended</h3>
	 <p>
      <?php

      if ($this->getBlogOption($data['blog']->blogid, 'do_autoextended') == "no") {
	 ?>
	 Function disabled for this blog<br/>
	 </p>
	 <?php
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
	  <input type="radio" name="ae_mode" value="word" <?php echo $wcheck; ?>/>word 
	  <input type="radio" name="ae_mode" value="para" <?php echo $pcheck; ?>/>paragraph 
	  <input type="radio" name="ae_mode" value="manu" <?php echo $mcheck; ?>/>manual 
	  <br /><label>Split after: </label>
             <input type="text" name="ae_count" size="4" value="<?php echo $count; ?>" /> words/paragraphs (this setting is ignored in manual mode)
	  </p>
	 <?php
      }
   }
   
    function event_EditItemFormExtras($data) {
      ?>
         <h3>AutoExtended</h3>
	 <p>
      <?php

      if ($this->getBlogOption($data['blog']->blogid, 'do_autoextended') == "no") {
	 ?>
	 Function disabled for this blog<br/>
	 </p>
	 <?php
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
	  <input type="radio" name="ae_mode" value="word" <?php echo $wcheck; ?>/>word 
	  <input type="radio" name="ae_mode" value="para" <?php echo $pcheck; ?>/>paragraph 
	  <input type="radio" name="ae_mode" value="manu" <?php echo $mcheck; ?>/>manual 
	  <br /><label>Split after: </label>
             <input type="text" name="ae_count" size="4" value="<?php echo $count; ?>" /> words/paragraphs (this setting is ignored in manual mode)
	  </p>
	 <?php
      }
   }

}

?>
