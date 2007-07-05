<?php
/*
NP_DlCounter
Based on Simple Download Counter 1.0
by Drew Phillips (http://www.drew-phillips.com)

converted to a Nucleus Plugin by
Rodrigo Moraes (http://www.tipos.com.br)

History:
    0.5 - fix %2F encoding problem in file path
        - add unInstall
        - add support for <%DlCounter%> in item text/more
    0.6 - fix <%DlCounter%> skin var URL
        - fix URI problem in skinVar
    0.7 - fix a bug with NP_Print
    0.8 - add getTableList()
    1.0 - show file size
        - add admin menu
        - auto file path detection
*/

class NP_DlCounter extends NucleusPlugin {
    function getName() { return 'Download Counter'; }
    function getAuthor()  { return 'Drew Phillips | Rodrigo Moraes (conversion to plugin) | Edmond Hui (admun)'; }
    function getURL()  { return 'http://www.drew-phillips.com'; }
    function getVersion() { return '1.0'; }
    function supportsFeature($SqlTablePrefix) { return 1; }
    function getDescription() { return 'A simple download counter.'; }
    function getEventList() { return array('PreSkinParse', 'PreItem', 'QuickMenu'); }

    function install() {
        $this->createOption('usecookie', 'Use cookie to avoid counting more than once a file download made by the same person?', 'yesno', 'yes');
        $this->createOption('showSize', 'Show file size', 'yesno', 'yes');
        sql_query('CREATE TABLE IF NOT EXISTS '.sql_table('plug_dl_count').' ( '.
            'file VARCHAR(255) NOT NULL, '.
            'count INT NOT NULL '.
            ')');
    }

    function getTableList() { array( sql_table('plug_dl_count') ); }

    function unInstall() {
        sql_query('DROP TABLE '.sql_table('plug_dl_count').';');
    }

    function hasAdminArea() { return 1; }

    function event_QuickMenu(&$data) {
      // only show when option enabled
      global $member;
      if (!($member->isLoggedIn() || !$member->isAdmin())) return;
      array_push($data['options'],
	array('title' => 'DlCounter',
	  'url' => $this->getAdminURL(),
	  'tooltip' => 'DlCounter Statistics'));
    }

    function event_PreSkinParse(&$data) {
        global $_COOKIE, $_GET, $CONF, $DIR_MEDIA;
        
        if(isset($_GET['file'])) {
            $file = $_GET['file'];

            if(empty($file)) {
                echo "No File Specified";
                exit;
            }
            
            if(strpos($file, "..") !== FALSE) {
                echo "Error.";
                exit;
            }
            
            if(strpos($file, "://") !== FALSE) {
                echo "Invalid File";
                exit;
            }
        
            //if the last two checks didnt get rid of a malicious user, this next one certainly will
            if(!file_exists($DIR_MEDIA . $file)) {
                echo "Specified file $file doesn't exist.";
                exit;
            }

            // cookie fix
            $cookie = str_replace(".", "_", $file);  

            $query = "SELECT * FROM ".sql_table('plug_dl_count')." WHERE file = '".$file."'";
            $result = mysql_query($query);
            if(!$result) {
                echo mysql_error();
                exit;
            }
            
            if(mysql_num_rows($result) == 0) {
                //first use of this file
                $query = "INSERT INTO ".sql_table('plug_dl_count')." VALUES('".$file."', 1)";
                $result = mysql_query($query);
                    if ($this->getOption('usecookie') == 'yes') {
                        setcookie("dl_" . $cookie, "set", time() + 60*60*24*365);
                    }
            }
            
            else {
                if(!isset($_COOKIE['dl_' . $cookie]) || $this->getOption('usecookie') != 'yes') {
                    $query = "UPDATE ".sql_table('plug_dl_count')." SET count = count + 1 WHERE file = '".$file."'";
                    $result = mysql_query($query);
                    if ($this->getOption('usecookie') == 'yes') {
                        setcookie("dl_". $cookie, "set", time() + 60*60*24*365,$CONF['CookiePath'],$CONF['CookieDomain'],$CONF['CookieSecure']);
                    }
                }
            }

            header("Location: " . $CONF['Self'] . "/media/" . $file);
        }

    } //event_PreSkinParse


    function replaceCallback($matches) {
        global $manager, $blog;

        if ($blog == "")
        {
          $blog =& $manager->getBlog($CONF['DefaultBlog']);
        }

	$mem = MEMBER::createFromName($matches[1]);
        return "<a href=\"" . $blog->getURL() . "?file=". $mem->getID() . "/".$matches[2]."\">".$matches[3]."</a>";
    }

    function event_PreItem($data) {
	$this->currentItem = &$data["item"];

	$this->currentItem->body = preg_replace_callback("#<\%DlCounter\((.*?)\,(.*?)\,(.*?)\)\%\>#", array(&$this, 'replaceCallback'), $this->currentItem->body);
	$this->currentItem->more = preg_replace_callback("#<\%DlCounter\((.*?)\,(.*?)\,(.*?)\)\%\>#", array(&$this, 'replaceCallback'), $this->currentItem->more);
    }

    function doSkinVar($skinType,$arg1='',$arg2='',$arg3='',$arg4='') {
        global $manager, $blog, $CONF, $cnumber, $c, $mp, $DIR_MEDIA;

	$mem = MEMBER::createFromName($arg1);

        if ($this->getOption('showSize') == "yes") {
	  $size = filesize($DIR_MEDIA.$mem->getID()."/".$arg2)." bytes, download ";
	}

        $query = "SELECT count FROM ".sql_table('plug_dl_count')." WHERE file = '".$mem->getID()."/".$arg2."'";
        $result = sql_query($query);
        if(mysql_num_rows($result) == 0) {
            $count = "0"; }
        else {
            $counter = mysql_fetch_row($result);
            $count = $counter[0]; }

        if(!$arg2) {
            $arg2 = "Download file"; }
        
        if ($arg4 == '1') {
            echo $count; }
        else {
            echo "<a href=\"". $blog->getURL() . "?file=". $mem->getID() . "/" . $arg2 ."\">".$arg3."</a>";
            echo " (";
	    echo $size;
            echo $count;
            echo "x)";
        }
    } // doSkinVar
} // class
?>
