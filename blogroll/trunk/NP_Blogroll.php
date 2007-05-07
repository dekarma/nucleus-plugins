<?php
/* NP_Blogroll
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * © Joel Pan
 * http://www.ketsugi.com
 *
 * Enhanced by Edmond Hui http://edmondhui.homeip.net/nudn
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 * See /blogroll/help.html for usage documentation and version history.
 *
 * admun TODO: 
 *  - tag auto complete
 *  - XFN http://gmpg.org/xfn/intro
 *  - add tag(s) to a group
 *
 *  - show related tags and multi tags
 *  - tags relationship chart to see how tags on each link related
 *  - add search function
 *    select l.id,l.text,l.desc,l.url from nucleus_plug_blogroll_links as l where l.url like "%script%" or l.text like "%script%" or l.desc like "%script%" and l.group='pending';
 *
 *  - check _GET/_POST and how to fix action processing once and for all?? maybe rewrite the admin menu.....
 *  - tagcloud/searchresult for all groups
 *  - Blogmarks support
 *  - add links check for 404 and other error
 *  - share blogroll group (gorup 0)
 *  - tagcloud per user
 */
 
if (!function_exists('sql_table')) {
  function sql_table($name) {
    return 'nucleus_' . $name;
  }
}

class NP_Blogroll extends NucleusPlugin {

  function getName() { return 'Blogroll';  }
  function getAuthor() { return 'Joel Pan, mod by Edmond Hui (admun)'; }
  function getURL() { return 'http://wakka.xiffy.nl/Blogroll'; }
  function getVersion() { return '0.39';  }

  function getDescription() { return 'This plugin lets you manage a database of links from your admin area and maintains a count of how many times each link has been clicked on by reader. Click on "help" for more information.'; }

  function supportsFeature($what) {
    switch($what) {
      case 'SqlTablePrefix': return 1;
      case 'HelpPage': return 1;
      default: return 0;
    }
  }

  function getTableList() {
    return array(sql_table('plug_blogroll_links'),sql_table('plug_blogroll_groups'),sql_table('plug_blogroll_tags'));
  }

  function install() {
    $this->createOption("redirect", "Use redirector URLs?", "yesno", "yes");
    $this->createOption("error", "Display error messages?","yesno", "yes");

    $this->createOption('tplHeader','Header','textarea',"<div class=\"links\" id=\"group<%groupid%>\">\n<h4><%groupname%></h4>");
    $this->createOption('tplListHeader','List Header','textarea',"<ul class='links' id=\"lgroup<%groupid%>\">");
    $this->createOption('tplItem','Item','textarea',"<li><a href=\"<%linkurl%>\" title=\"<%linktitle%>\"><%linktitle%></a></li>");
    $this->createOption('tplListFooter','List Footer','textarea','</ul>');
    $this->createOption('tplFooter','Footer','textarea',"</div>");

    $this->createOption("resultTitle", "Tag search result title", "text","<h1>Blogroll for tag <%tag%></h1><hr/>");
    $this->createOption("selectText", "Initialize text for search page", "text","Please select a tag from the right");

    $this->createOption("tcListHeader", "Header for tagcloud link", "textarea","");
    $this->createOption("tcListItem", "Item link for tagcloud link", "textarea","<a href=\"<%linkurl%>\" title=\"<%linkcomment%>\"><%linktitle%></a><%sep%><%linkdesc%><%linkedit%><br/>");
    $this->createOption("tcListFooter", "Footer for tagcloud link", "textarea","");
    $this->createOption("PlusToSpace", "Display \"+\" as \" \" in tagcloud?", "yesno", "no");

    $this->createOption('DelIcioUs','Add link to del.icio.us? (need to set login & password from member setting)','yesno','no');
    $this->createMemberOption('DeliciousUser','del.icio.us login','text','');
    $this->createMemberOption('DeliciousPassword','del.icio.us password','password','');

    $this->createOption("quickmenu", "Show in quick menu?", "yesno", "yes");
    $this->createOption("del_uninstall", "Delete tables on uninstall?", "yesno","no");

    // Create blogroll tables
    $query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_blogroll_links').'(`id` INT NOT NULL AUTO_INCREMENT,`order` INT NOT NULL,`owner` INT(11) DEFAULT \'1\' NOT NULL,`group` INT NOT NULL,`url` VARCHAR(255) NOT NULL,`text` VARCHAR(255), `desc` VARCHAR(255),`created` DATETIME NOT NULL,`clicked` DATETIME NOT NULL,`counter` INT NOT NULL, `comment` VARCHAR(1024) NOT NULL, PRIMARY KEY (`id`, `owner`) ) TYPE = MYISAM ;';
    sql_query($query);

    $query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_blogroll_groups').'(`id` INT NOT NULL AUTO_INCREMENT,`owner` INT(11) NOT NULL,`name` VARCHAR(30) NOT NULL,`desc` VARCHAR(255),PRIMARY KEY (`id`, `owner`) ) TYPE = MYISAM ;';
    sql_query($query);

    $query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_blogroll_tags').' (`tag` VARCHAR(64), `id` INT) TYPE=MYISAM;';
    sql_query($query);
  }

  function unInstall() {
    if ($this->getOption('del_uninstall') == "yes") {
      sql_query('DROP TABLE '.sql_table('plug_blogroll_links').';');
      sql_query('DROP TABLE '.sql_table('plug_blogroll_groups').';');
      sql_query('DROP TABLE '.sql_table('plug_blogroll_tags').';');
    }
  }

  function hasAdminArea() { return 1; }

  function getEventList() { return array('QuickMenu'); }

  function event_QuickMenu(&$data) {
    // only show when option enabled
    if ($this->getOption('quickmenu') != 'yes') return;
    global $member;
    if (!($member->isLoggedIn())) return;
    array_push($data['options'],
      array('title' => 'Blogroll',
        'url' => $this->getAdminURL(),
        'tooltip' => 'Manage links'));
  }

  function doSkinVar ($skinType, $type='user', $id='', $sortfield = 'text',$sortorder = 'asc', $groupdesc = '', $numlinks = -1, $redirect='') {
    global $blog, $CONF;
    if ($skinType == 'member' && $type == '') {
      $type = 'user';
      global $memberinfo;
      $id = $memberinfo->displayname;
    }
    
    switch($type) {
      case 'tagcloudresult':
        global $member;

        $tag = str_replace(' ','+',RequestVar('tag'));
        if (function_exists('mb_convert_encoding')) {
          $tag = mb_convert_encoding($tag, _CHARSET, _CHARSET);
          $tag = rawurldecode($tag);
	  $tag = htmlspecialchars_decode($tag);
        }
        else {
          // This will not work for UTF-8 tag....  . not something 
          // we can fix unless we bundle mb_convert_encoding()
          $tag = urlencode($tag);
	  $tag = htmlspecialchars_decode($tag);
        }

        $group = urlencode(RequestVar('group'));
        if ($tag != "") {
          if ($this->getOption('PlusToSpace') == "yes") {
            $disp_tag = str_replace('+','&nbsp;',$tag);
          }
          else {
            $disp_tag = $tag;
          }

          echo str_replace('<%tag%>', $disp_tag, $this->getOption('resultTitle'));
        } else {
          echo $this->getOption('selectText');
          return;
        }

        $result=sql_query("SELECT l.* FROM ".sql_table('plug_blogroll_links')." as l, ".sql_table('plug_blogroll_tags')." as t, ".sql_table('plug_blogroll_groups')." as g WHERE t.tag=\"".$tag."\" and l.id=t.id and l.group=g.id and l.group=".$group);
        
        echo $this->getOption('tcListHeader');
        while ($r = mysql_fetch_assoc($result)) {
          $out = $this->_makeCloudLink($r, $this->getOption('redirect'));
          if ($member->isLoggedIn() && $member->getID() == $r['owner']) {
            $edit = " [<a href=\"".$CONF['PluginURL']."/blogroll/index.php?page=managetag&tag=".$tag."&action=editlink&id=".$r['id']."\">edit</a>]";
          } else {
            $edit = '';
          }
          echo str_replace('%e',$edit,$out);
        }
        echo $this->getOption('tcListFooter');
      break;

      case 'tagcloud':
        $result=sql_query("SELECT id FROM `".sql_table('plug_blogroll_groups')."` WHERE `name`=\"".$id."\"");
        $group=mysql_fetch_object($result);
        $groupid = $group->id;

        // There must be a smarter way to do this.....
        $result= sql_query("SELECT COUNT(t.tag) AS min FROM " . sql_table('plug_blogroll_links')
                 . " AS l, " . sql_table('plug_blogroll_tags') . " AS t WHERE l.group=" 
                 . $groupid . " AND l.id=t.id GROUP BY t.tag ORDER BY min LIMIT 0,1");
        $row = mysql_fetch_object($result);
        $min = $row->min;

        $result= sql_query("SELECT COUNT(t.tag) AS max FROM " . sql_table('plug_blogroll_links')
                 . " AS l, " . sql_table('plug_blogroll_tags') . " AS t WHERE l.group=" 
                 . $groupid . " AND l.id=t.id GROUP BY t.tag ORDER BY max DESC LIMIT 0,1");
        $row = mysql_fetch_object($result);
        $max = $row->max;

        $result= sql_query("SELECT t.tag, COUNT(t.tag) AS count FROM " . sql_table('plug_blogroll_links')
                 . " AS l, " . sql_table('plug_blogroll_tags') . " AS t WHERE l.group=" 
                 . $groupid . " AND l.id=t.id GROUP BY t.tag");

        while($row = mysql_fetch_object($result)) {
          if ($row->count == $min) { echo "<span class=\"BRtinyT\">"; } // SMALLEST
          else if ($row->count == $max) { echo "<span class=\"BRlargeT\">"; } // LARGEST
          else if ($row->count >= ($min + ($dist * 2))) { echo "<span class=\"BRmediumT\">"; } // MEDIUM
          else { echo "<span class=\"BRsmallT\">"; } // SMALL

          if ($this->getOption('PlusToSpace') == "yes") {
            $disp_tag = str_replace('+','&nbsp;',$row->tag);
          }
          else {
            $disp_tag = htmlspecialchars_decode($row->tag);
          }

          echo "<a href=\"./blogroll.php?tag=$row->tag&amp;group=$groupid\">$disp_tag</a> "; //[" . $row->count . "]";
          echo "</span>";
        }
      break;

      case 'link':
        //Remap argument 3 to $redirect
        if ($sortfield != 'yes' && $sortfield != 'no') $redirect = $this->getOption('redirect');
        else $redirect = $sortfield;
        $query = sql_query("SELECT * FROM `".sql_table('plug_blogroll_links')."` WHERE `id`=$id");
        $links = mysql_fetch_assoc($query);
        echo $this->_makeLink($links,$redirect);
        break;
      
      case 'user':
        $user = $id;
        //Check for blank username
        if ($user == '') {
          if ($this->getOption("error") == "yes") echo "User name left blank!";
          return;
        }
        //Check for valid username
        $query = sql_query('SELECT `mnumber` FROM `'.sql_table('member').'` WHERE `mname`="'.$user.'"');
        $result = mysql_fetch_assoc($query);
        $id = $result['mnumber'];
        if ($id == '') {
          if ($this->getOption("error") == "yes") echo "Invalid user name!";
          return;
        }
      
        //Check that specified user belongs to the blog team
        $query = sql_query('SELECT * FROM `'.sql_table('team').'` WHERE `tmember`='.$id.' AND `tblog`='.$blog->blogid);
        $result = mysql_fetch_assoc($query);
        if ($result['tmember'] == '') {
          if ($this->getOption("error") == "yes") echo "Specified user does not belong to this blog!";
          return;
        }
     
        //Get groups
        $query = sql_query('SELECT * FROM `'.sql_table('plug_blogroll_groups').'` WHERE `owner`='.$id.' ORDER BY `name`');
        while ($group = mysql_fetch_assoc($query)) {
          $this->doSkinVar('item','group',$group['name'],$sortfield,$sortorder);
        }
        break;
      
      case 'group':
        // Initialise default sorting values
        switch ($sortfield) {
          case "name":
          case "url":
          case "text":
          case "desc":
          case "created":
          case "clicked":
          case "counter":
            $sortfield = "`$sortfield`";
            break;
          case "random":
            $sortfield = "RAND()";
            break;
          default: $sortfield = '`order`';
        }
        if ($sortorder != 'desc') $sortorder = 'asc';

        if (is_numeric($numlinks)) $numlinks = (int)$numlinks;
        else $numlinks = -1;
				
        // Parse group names
        $i = 0;
        $token = strtok($id, '|');
        while ($token) {
          $groups[$i]['name'] = $token;
          $query = sql_query("SELECT `id`, `name`, `desc` FROM `".sql_table('plug_blogroll_groups')."` WHERE `name`=\"".$groups[$i]['name'].'"');
          $result = mysql_fetch_assoc($query);
          if ($result['id'] == '') {
            if ($this->getOption("error")) {
              echo ($groups[$i]['name']." is not a valid group name.");
            }
            return;
          }
          else {
            $groups[$i]['id'] = $result['id'];
            $groups[$i]['desc'] = $result['desc'];
            $token = strtok('|');
            $i++;
          }
        }
        if (count($groups) == 1) {
           $groupVars = array (
             'groupid' => $groups[0]['id'],
             'groupname' => $groups[0]['name'],
             'groupdesc' => $groupdesc == '' ? $groups[0]['desc'] : $groupdesc
          );
        }
        else {
          $groupVars = array (
            'groupid' => $groupid == '' ? $groups[0]['id'] : $groupid,
            'groupname' => '',
            'groupdesc' => $groupdesc == '' ? $groups[0]['desc'] : $groupdesc
          );
        }

        // Output headers
        echo TEMPLATE::fill($this->getOption('tplHeader'), $groupVars);
        echo TEMPLATE::fill($this->getOption('tplListHeader'), $groupVars);

        // Get links
        $i=0;
        $query = "SELECT * FROM `".sql_table('plug_blogroll_links')."` WHERE ";
        foreach ($groups as $group) {
          if ($i++ > 0) $query .= " OR ";
          $query .= "`group`=".$group['id'];
        }
        $query .= " ORDER BY $sortfield $sortorder";
				if ($numlinks >= 0) $query .= " LIMIT $numlinks";
        $query = sql_query($query);

        // Output links
        while ($result = mysql_fetch_assoc($query))
          echo $this->_makeLink($result,$redirect);

        // Output footers
        echo TEMPLATE::fill($this->getOption('tplListFooter'), $groupVars);
        echo TEMPLATE::fill($this->getOption('tplFooter'), $groupVars);
        break;
    }
  }

  function _makeLink($link,$redirect) {
    $linkVars = array (
		  'id' => $link['id'],      
                  'linkurl' => $redirect == "yes" ?  $this->getAdminURL().'?n='.$link['id'] : htmlentities($link['url']),
                  'linktitle' => htmlspecialchars_decode($link['text']),
                  'linkcomment' => htmlspecialchars_decode($link['comment']),
                  'linkcreated' => htmlspecialchars_decode($link['created'])
		);

    if ($redirect == "yes") {
      $extraLinkVars = array(
                        'linkclicked' => $link['clicked'],
                        'linkcounter' => $link['counter']
                      );
      $linkVars = array_merge($linkVars, $extraLinkVars);
    }

    return (TEMPLATE::fill($this->getOption('tplItem'), $linkVars));
  }

  function _makeCloudLink($link,$redirect) {
    $desccomm = "";
    if ($link['desc'] == "") {
      if ($link['comment'] != "") {
        $desccomm = " - ".$link['comment'];
      }
    }
    else {
      $desccomm = " - ".$link['desc'];
    }

    if ($link['desc'] != "") {
      $sep = " - ";
    }

    $linkVars = array (
                  'linkurl' => $redirect == "yes" ?  $this->getAdminURL().'?n='.$link['id'] : htmlentities($link['url']),
                  'linktitle' => htmlspecialchars_decode($link['text']),
                  'linkdesc' => $link['desc'] == "" ? "" : htmlspecialchars_decode($link['desc']),
                  'sep' => $sep,
                  'linkcomment' => htmlspecialchars_decode($link['comment']),
                  'linkdesccomm' => htmlspecialchars_decode($desccomm),
                  'linkedit' => "%e"
		);

    if ($redirect == "yes") {
      $extraLinkVars = array(
                        'linkclicked' => $link['clicked'],
                        'linkcounter' => $link['counter']
                      );
      $linkVars = array_merge($linkVars, $extraLinkVars);
    }

    return (TEMPLATE::fill($this->getOption('tcListItem'), $linkVars));
  }
}
?>
