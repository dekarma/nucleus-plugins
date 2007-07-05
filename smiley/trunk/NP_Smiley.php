<?
/*
  0.1 - initial release
  0.2 - added emoticons, supportsFeature
  0.2a - fixed XHTML
  0.3 - fixed more XHTML (mod from fishywang)
  0.4 - auto emoticons path detection
  0.5 - add smiley panel
      - fix emticons alt/title corruption bug
  0.6 - fix file_get_contents
  0.6a - set auto link to disable by default
  0.7 - add alt='redface', instead of 'emoticon'
  0.8 - add user-defined smiley pack infra-structure, to allow easy adding new smileys set

    - template for the smiley box

  Note: icons are taken from phpbb
  Note2: Can't really do alt='8)' because there are str_replace colision with :? , :?: and others
 */

// Fix compatibility older PHP versions
if (!function_exists('file_get_contents')) {
  function file_get_contents($filename, $use_include_path = 0) {
   $data = '';
   $file = @fopen($filename, "rb", $use_include_path);
   //set_socket_timeout($file,0);
   if ($file) {
     while (!feof($file)) $data .= fread($file, 1024);
     fclose($file);
   } else {
     echo $this->getOption('time_outtext');
     exit;
   }
   return $data;
  }
}

class NP_Smiley extends NucleusPlugin { 

   function getName() { return 'Smiley'; } 
   function getAuthor()  { return 'Lama Himself | Edmond Hui (admun)'; }
   function getURL()  { return 'http://www.gaming-side.com/lama/'; } 
   function getVersion() { return '1.0'; }
   function getDescription() { 
     return 'This plugin provides emoticons (also known as smileys) support, also create link for URL and email address.'; 
   } 

   function install() { 
     $this->createOption('ItemEnable','Use Smiley in the items ?','yesno','yes'); 
     $this->createOption('CommentEnable','Use Smiley in the comment ?','yesno','yes'); 
     $this->createOption('LinkEnable','Auto create link with internet adresses ?','yesno','no'); 
     $this->createOption('IconTheme','Smiley set to use','text','default'); 
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
     return array('PreItem', 'PreComment'); 
   } 
    
   function Treatment($_text) { 
     global $CONF, $blog, $DIR_PLUGINS;
     $theme = $this->getOption('IconTheme');
     include($DIR_PLUGINS."/emoticons/".$theme."/mapping.inc");

     foreach ($emoticons as $smile => $img) {
       $_text = str_replace($smile, ' <img src="'.$CONF['AdminURL'].'plugins/emoticons/'.$theme.'/'.$img.'" alt="'.rtrim(substr($img,5), '.gif').'" /> ', $_text);
     }

     if ($this->getOption('LinkEnable') == 'yes') { 
       $_text = preg_replace('/(\s)([a-z]+?:\/\/\S*)/si','\1<a href="\2">\2</a>',$_text); 
       $_text = preg_replace('/(\s)(www\.\S+)/si','\1<a href="http://\2">\2</a>',$_text); 
       $_text = preg_replace('/(\S*@\S*\.\S*)/si','<a href="mailto:\1">\1</a>',$_text); 
     } 

     return $_text; 
   } 

   function event_PreItem($_data) { 
     if ($this->getOption('ItemEnable') == 'no') 
       return; 
       
     $_data[item]->body = $this->Treatment($_data[item]->body); 
     $_data[item]->more = $this->Treatment($_data[item]->more); 
   } 
    
   function event_PreComment($_data) { 
     if ($this->getOption('CommentEnable') == 'no') 
       return; 

     $_data['comment']['body'] = $this->Treatment($_data['comment']['body']); 
   } 

   function doSkinVar($skinType, $param) {
     $theme = $this->getOption('IconTheme');

     if ($param == 'panel') {
       global $DIR_PLUGINS, $CONF;
       $in = file_get_contents($DIR_PLUGINS.'emoticons/'.$theme.'/smiley_panel.template');
       $in = str_replace('##URL##',$CONF['PluginURL'],$in);
       echo $in;
     } elseif ($param == 'script') {
?>
<script type="text/javascript">
  function insertext(text){
    document.getElementById('nucleus_cf_body').value+=" "+ text;
    document.getElementById('nucleus_cf_body').focus();
  }
</script>
<?
     }
   }

} 
?>
