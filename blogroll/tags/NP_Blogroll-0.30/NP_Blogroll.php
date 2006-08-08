<?php
/* NP_Blogroll
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * � Joel Pan
 * http://www.ketsugi.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 * See /blogroll/help.html for usage documentation and version history.
 */
 
if (!function_exists('sql_table')) {
  function sql_table($name) {
    return 'nucleus_' . $name;
  }
}

class NP_Blogroll extends NucleusPlugin {

  function getName() { return 'Blogroll';  }
  function getAuthor() { return 'Joel Pan'; }
  function getURL() { return 'http://wakka.xiffy.nl/Blogroll'; }
  function getVersion() { return '0.30';  }
  function getDescription() { return 'This plugin lets you manage a database of links from your admin area and maintains a count of how many times each link has been clicked on by reader. Click on "help" for more information.'; }
  function supportsFeature($what) {
    switch($what) {
      case 'SqlTablePrefix': return 1;
      case 'HelpPage': return 1;
      default: return 0;
    }
  }
  function getTableList() {
    return array(sql_table('plug_blogroll_links'),sql_table('plug_blogroll_groups'));
  }

  function install() {
    $this->createOption("redirect", "Use redirector URLs?", "yesno", "yes");
    $this->createOption("error", "Display error messages?","yesno", "yes");
    $this->createOption('tplHeader','Header','textarea',"<div class=\"links\" id=\"group<%groupid%>\">\n<h4><%groupname%></h4>");
    $this->createOption('tplListHeader','List Header','textarea',"<ul class='links' id=\"group<%groupid%>\">");
    $this->createOption('tplItem','Item','textarea',"<li><a href=\"<%linkurl%>\" title=\"<%linktitle%>\"><%linktext%></a></li>");
    $this->createOption('tplListFooter','List Footer','textarea','</ul>');
    $this->createOption('tplFooter','Footer','textarea',"</div>");
    $this->createOption("quickmenu", "Show in quick menu?", "yesno", "yes");
    $this->createOption("del_uninstall", "Delete tables on uninstall?", "yesno","no");

    // Create blogroll tables
    $query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_blogroll_links').'(`id` INT NOT NULL AUTO_INCREMENT,`order` INT NOT NULL,`owner` INT(11) DEFAULT \'1\' NOT NULL,`group` INT NOT NULL,`url` VARCHAR(255) NOT NULL,`text` VARCHAR(255),`title` VARCHAR(255),`created` DATETIME NOT NULL,`clicked` DATETIME NOT NULL,`counter` INT NOT NULL,PRIMARY KEY (`id`, `owner`) ) TYPE = MYISAM ;';
    sql_query($query);

    $query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_blogroll_groups').'(`id` INT NOT NULL AUTO_INCREMENT,`owner` INT(11) NOT NULL,`name` VARCHAR(30) NOT NULL,`desc` VARCHAR(255),PRIMARY KEY (`id`, `owner`) ) TYPE = MYISAM ;';
    sql_query($query);
  }

  function unInstall() {
    if ($this->getOption('del_uninstall') == "yes") {
      sql_query('DROP TABLE ' .sql_table('plug_blogroll_links').'; DROP TABLE '.sql_table('plug_blogroll_groups'));
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
		global $blog;
		if ($skinType = 'member' && $type == '') {
		  $type = 'user';
		  global $memberinfo;
		  $id = $memberinfo->displayname;
		}
		
		switch($type) {
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
			'linkurl' => $redirect == "yes" ? $this->getAdminURL().'?n='.$link['id'] : $link['url'],
      'linktext' => $link['text'],
      'linktitle' => $link['title'] == "" ? htmlentities($link['text']) : htmlentities($link['title']),
  		'linkcreated' => $link['created']
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
  
}
?>
