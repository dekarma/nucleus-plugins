<?
/*
  v0.2 - initial version
  v0.3 - ajaxized
  v0.4 - added admin menu
       - remove count in table
       - improve bot scanning code
       - add agent name column in table
       - add subscriber counter
       - add bot display ignore list
       - move list of bots to file
  v0.5 - limited subscriber info for the past 24 hours 
       - add pager for stats in admin menu
       - detect new bot that report "subscriber" info
       - optimize admin menu
 
  admun todo:
  - add db cleanup code
  - resolve ip to hostname on display
  - add template
  - help page
  - count all clients
*/

// PHP4 support
if (!function_exists('file_put_contents')) {
  function file_put_contents($n,$d) {
    $f=@fopen($n,"w");
    if (!$f) {
     return false;
    } else {
     fwrite($f,$d);
     fclose($f);
     return true;
    }
  }
}

class NP_BotTracking extends NucleusPlugin {
 
  function NP_BotTracking() {
    global $DIR_PLUGINS;
    $this->botlist = explode("\n",file_get_contents($DIR_PLUGINS."bottracking/bots.txt"));
    array_pop($this->botlist); // kind of a bug... the last entry is empty
    $this->noshow_botlist = explode("\n",file_get_contents($DIR_PLUGINS."bottracking/noshow_bots.txt"));
    array_pop($this->noshow_botlist); // kind of a bug... the last entry is empty
    $this->table_name = sql_table("plug_bottracking");
    $this->updated = false;
  }
 
  function getName() { return 'BotTracking'; }
  function getAuthor()  { return 'Edmond Hui (admun)'; }
  function getURL() { return 'http://http://edmondhui.homeip.net/nudn'; }
  function getVersion() { return 'v0.5'; }
  function getDescription() {
    return 'This plugin reports when a searchbot last knock on your blog, also track how many subscribers to your RSS feed.';
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
    $this->createOption('numShow','Number of recently visit crawlers to should','text','10');
 
    mysql_query ("CREATE TABLE $this->table_name
                ( 
                  `bots` VARCHAR(60) NOT NULL,
                  `agent` VARCHAR(256) NOT NULL,
                  `last` TIMESTAMP NOT NULL,
                  `url` VARCHAR(160) NOT NULL,
                  `hostname` VARCHAR(160) NOT NULL,
                  `blog` INT(11) NOT NULL
                )
                ");
  }
 
  function unInstall() {
    mysql_query ("DROP TABLE $this->table_name");
  }
 
  function getTableList() {
    return array($this->table_name);
  }

  function hasAdminArea() { return 1; }

  function getEventList() { return array('QuickMenu'); }

  function event_QuickMenu(&$data) {
    // only show when option enabled
    global $member;
    if (!($member->isLoggedIn() || !$member->isAdmin())) return;
    array_push($data['options'],
      array('title' => 'BotTracking',
        'url' => $this->getAdminURL(),
        'tooltip' => 'BotTracking Statistics'));
  }
 
  function doSkinVar($skinType, $mode, $blogid=0) {
    global $DIR_PLUGINS, $blog;

    if ($mode == 'script') {
      $this->insertScript();
      return;
    }
 
    if ($mode == 'showSub') {
      if ($blogid == 0) {
        $blogid = $blog->getID();
      }

      $subscriber = file_get_contents($DIR_PLUGINS."bottracking/blog".$blogid.".txt");
      echo $subscriber;
      return;
    }

    $botname = $_SERVER['HTTP_USER_AGENT'];
    $hostname = $_SERVER['REMOTE_ADDR'];;
    $url = $_SERVER['REQUEST_URI'];
 
    $this->updateBots($botname, $hostname, $url);
 
    if ($mode == 'quiet') return;
?>
    <div id="bottrack">
<?
    $this->showBots($this->getOption('numShow'));
?>
    </div>
<?
  }
 
  function updateBots($botname, $hostname, $url) {
    global $blog;

    if ($this->updated == true) return;

    foreach ($this->botlist as $b) {
      $bot = str_replace(" ", "\s", $b);
      $bot = str_replace("-", "\W", $bot);
      $blogid = $blog->getID();
      if (preg_match("/".$bot."/", $botname)) {
        $query = "INSERT INTO $this->table_name (bots, agent, hostname, url, blog) VALUES ('$b', '$botname', '$hostname', '$url', '$blogid')";
        sql_query($query);
        $this->updated = true;
        $this->updated = true;
        return;
      }
    }

    $matches = array();
    preg_match("/\d.subscriber/", $botname, $matches);
    if ($matches[0] != "") {
        $query = "INSERT INTO $this->table_name (bots, agent, hostname, url, blog) VALUES ('newbot', '$botname', '$hostname', '$url', '$blogid')";
        sql_query($query);
        $this->updated = true;
        return;
    }
  }
 
  function showBots($limit) {
    echo "<ul>";
    $e = "";
    if (count($this->noshow_botlist) > 0) {
      $e = " WHERE ";
      foreach ($this->noshow_botlist as $b) {
        if ($e != " WHERE ") { $e .= " AND "; }
        $e .= " bots !=\"" . $b . "\" ";
      }
    }

    $res= sql_query("SELECT * FROM " . $this->table_name . $e . " ORDER BY last DESC LIMIT 0,$limit");
    $t = time();
    while($row = mysql_fetch_object($res)) {

      $ts = ($t-strtotime($row->last))/60;
      if ($ts > 60) {
        $ago = round($ts/60,1) . "h";
      } else  {
        $ago = round($ts) . "m";
      }
 
      $time= date("j-m-y H:i",strtotime($row->last));
 
      echo "<li><a href=\"$row->url\" title=\"from $row->hostname\">$row->bots: $time [$ago ago]</a><li/>";
    }
    echo "</ul>";
  }
 
  function insertScript() {
     global $CONF;
?>
  <!-- code from http://dutchcelt.nl/weblog/article/ajax_for_weblogs/ -->
  <script type="text/javascript">
  <!--
    var ajaxBT=false;
    /*@cc_on @*/
    /*@if (@_jscript_version >= 5)
    try {
          ajaxBT = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
          try {
            ajaxBT = new ActiveXObject("Microsoft.XMLHTTP");
          } catch (E) {
            ajaxBT = false;
          }
    }
    @end @*/
 
    if (!ajaxBT && typeof XMLHttpRequest!='undefined') {
          ajaxBT = new XMLHttpRequest();
    }
 
    function BTgetMyHTML() {
 
          var serverPage = '<?php echo $CONF['IndexURL']; ?>action.php?action=plugin&name=BotTracking';
          var objBT = document.getElementById('bottrack');
          ajaxBT.open("GET", serverPage);
          ajaxBT.onreadystatechange = function() {
                if (ajaxBT.readyState == 4 && ajaxBT.status == 200) {
                  objBT.innerHTML = ajaxBT.responseText;
                }
          }
          ajaxBT.send(null);
 
        BTstartRefresh();
   }
 
   function BTstartRefresh() {
         setTimeout("BTgetMyHTML()",5*60*1000);
   }
 
   // trick learnt from wp wordspew
   if(typeof window.addEventListener != 'undefined') {
         //.. gecko, safari, konqueror and standard
         window.addEventListener('load', BTstartRefresh, false);
   }
   else if(typeof document.addEventListener != 'undefined')
   {
         //.. opera 7
         document.addEventListener('load', BTstartRefresh, false);
   }
   else if(typeof window.attachEvent != 'undefined')
   {
         //.. win/ie
         window.attachEvent('onload', BTstartRefresh);
   }
  // -->
  </script>
<?
  }
 
  function doAction($actionType) {
    if ($actionType == "count") {
      global $DIR_PLUGINS;

      $matches = array();
      $subs = array();

      $bresult = sql_query("SELECT bnumber FROM " . sql_table('blog'));
      while ($brow = mysql_fetch_object($bresult)) {
        $subscriber = 0;
        foreach ($this->botlist as $b) { $result = sql_query("SELECT DISTINCT bots,agent FROM " . $this->table_name . " WHERE bots = \"" . $b . "\" AND blog='" . $brow->bnumber . "' AND DATE_SUB(CURDATE(),INTERVAL 1 DAY) <= last");
          while ($row = mysql_fetch_object($result)) {
            preg_match("/\d.subscriber/", $row->agent, $matches);
            if ($matches[0] != "") {
              $subs = explode(" ", $matches[0]);
              $subscriber += $subs[0];
            }
          }
        }

        echo "Blog ".$brow->bnumber. " - " .$subscriber."<br/>";
        file_put_contents($DIR_PLUGINS."bottracking/blog".$brow->bnumber.".txt", $subscriber);
      }

      return;
    }

    // need for the Ajax to refresh recent visit display
    $this->showBots($this->getOption('numShow'));
  }
}
?>
