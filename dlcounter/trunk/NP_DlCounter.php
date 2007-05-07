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

admun ToDO:
- auto detect file path, added author id to table,
- show file size
- add admin menu
*/

class NP_DlCounter extends NucleusPlugin {
    function getName() { return 'Download Counter'; }
    function getAuthor()  { return 'Drew Phillips | Rodrigo Moraes (conversion to plugin) | Edmond Hui (admun)'; }
    function getURL()  { return 'http://www.drew-phillips.com'; }
    function getVersion() { return '0.8'; }
    function supportsFeature($SqlTablePrefix) { return 1; }
    function getDescription() { return 'A simple download counter.'; }
    function getEventList() { return array('PreSkinParse', 'PreItem'); }

    function install() {
        $this->createOption('usecookie', 'Use cookie to avoid counting more than once a file download made by the same person?', 'yesno', 'yes');
        sql_query('CREATE TABLE IF NOT EXISTS '.sql_table('plug_dl_count').' ( '.
            'file VARCHAR(255) NOT NULL, '.
            'count INT NOT NULL '.
            ')');
    }

    function getTableList() { array( sql_table('plug_dl_count') ); }

    function unInstall() {
        sql_query('DROP TABLE '.sql_table('plug_dl_count').';');
    }
    
    function event_PreSkinParse(&$data) {
        global $_COOKIE, $_GET, $CONF;
        
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
            if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $_GET['file'])) {
                echo "Specified file $file doesn't exist.";
                exit;
            }

            // cookie fix
            $cookie = str_replace(".", "_", $file);  

            $query = "SELECT * FROM ".sql_table('plug_dl_count')." WHERE file = '$file'";
            $result = mysql_query($query);
            if(!$result) {
                echo mysql_error();
                exit;
            }
            
            if(mysql_num_rows($result) == 0) {
                //first use of this file
                $query = "INSERT INTO ".sql_table('plug_dl_count')." VALUES('$file', 1)";
                $result = mysql_query($query);
                    if ($this->getOption('usecookie') == 'yes') {
                        setcookie("dl_" . $cookie, "set", time() + 60*60*24*365);
                    }
            }
            
            else {
                if(!isset($_COOKIE['dl_' . $cookie]) || $this->getOption('usecookie') != 'yes') {
                    $query = "UPDATE ".sql_table('plug_dl_count')." SET count = count + 1 WHERE file = '$file'";
                    $result = mysql_query($query);
                    if ($this->getOption('usecookie') == 'yes') {
                        setcookie("dl_". $cookie, "set", time() + 60*60*24*365,$CONF['CookiePath'],$CONF['CookieDomain'],$CONF['CookieSecure']);
                    }
                }
            }

            header("Location: http://" . $_SERVER['HTTP_HOST'] . $_GET['file']);
        }

    } //event_PreSkinParse


    function replaceCallback($matches) {
        global $manager, $blog;

        if ($blog == "")
        {
          $blog =& $manager->getBlog($CONF['DefaultBlog']);
        }

        return "<a href=\"" . $blog->getURL() . "?file=". $matches[1] ."\">".$matches[2]."</a>";
    }

    function event_PreItem($data) {
	$this->currentItem = &$data["item"];
	$this->currentItem->body = preg_replace_callback("#<\%DlCounter\((.*?)\,(.*?)\)\%\>#", array(&$this, 'replaceCallback'), $this->currentItem->body);
	$this->currentItem->more = preg_replace_callback("#<\%DlCounter\((.*?)\,(.*?)\\)%\>#", array(&$this, 'replaceCallback'), $this->currentItem->more);
    }

    function doSkinVar($skinType,$arg1='',$arg2='',$arg3='') {
        global $manager, $blog, $CONF, $cnumber, $c, $mp;

        $query = "SELECT count FROM ".sql_table('plug_dl_count')." WHERE file = '$arg1'";
        $result = sql_query($query);
        if(mysql_num_rows($result) == 0) {
            $count = "0"; }
        else {
            $counter = mysql_fetch_row($result);
            $count = $counter[0]; }

        if(!$arg2) {
            $arg2 = "Download file"; }
        
        if ($arg3 == '1') {
            echo $count; }
        else {
            echo "<a href=\"". $blog->getURL() . "?file=". $arg1 ."\">".$arg2."</a>";
            echo " (";
            echo $count;
            echo ")";
        }
    } // doSkinVar
} // class
?>
