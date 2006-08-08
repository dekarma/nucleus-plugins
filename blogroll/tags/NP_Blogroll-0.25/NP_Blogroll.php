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
  function getVersion() { return '0.25';  }
  function getDescription() { return 'This plugin lets you manage a database '.
    'of links from your admin area and maintains a count of how many times '.
    'each link has been clicked on by reader. Click on "help" for more '.
    'information.'; }
  function supportsFeature($what) {
    switch($what) {
      case 'SqlTablePrefix': return 1;
      case 'HelpPage': return 1;
      default: return 0;
    }
  }

  function install() {
    $this->createOption("redirect", "Use redirector URLs?", "yesno", "yes");
    $this->createOption("error", "Show an error message if id is not found?",
      "yesno", "yes");
    $this->createOption('tplHeader','Header','textarea',"<div class=\"links\" ".
      "id=\"group<%groupid%>\">\n<h4><%groupname%></h4>");
    $this->createOption('tplListHeader','List Header','textarea',"<ul ".
      "class='links' id=\"group<%groupid%>\">");
    $this->createOption('tplItem','Item','textarea',"<li><a href=\"<%linkurl%>".
      "\" title=\"<%linktitle%>\"><%linktext%></a></li>");
    $this->createOption('tplListFooter','List Footer','textarea','</ul>');
    $this->createOption('tplFooter','Footer','textarea',"</div>");
    $this->createOption("quickmenu", "Show in quick menu?", "yesno", "yes");
    $this->createOption("del_uninstall", "Delete tables on uninstall?", "yesno",
      "no");

    // Insert code to convert from redirect to blogroll
    $query = sql_query('SHOW TABLES LIKE "'.
      sql_table('plug_redirect_groups').'"');
    if (!$query) sql_query('ALTER TABLE `'.
      sql_table('plug_redirect_groups').
      '` RENAME `'.sql_table('plug_blogroll_groups').'`');
    $query = sql_query('SHOW TABLES LIKE "'.
      sql_table('plug_redirect_links').'"');
    if (!$query) sql_query('ALTER TABLE `'.
      sql_table('plug_redirect_links')
      .'` RENAME `'.sql_table('plug_blogroll_links').'`');

    // Create blogroll tables
    $query = 'CREATE TABLE IF NOT EXISTS '.sql_table('plug_blogroll_links').'(';
    $query .= '`id` INT NOT NULL AUTO_INCREMENT,';
    $query .= '`order` INT NOT NULL,';
    $query .= "`owner` INT(11) DEFAULT '1' NOT NULL,";
    $query .= '`group` INT NOT NULL,';
    $query .= '`url` VARCHAR(255) NOT NULL,';
    $query .= '`text` VARCHAR(255),';
    $query .= '`title` VARCHAR(255),';
    $query .= '`created` DATETIME NOT NULL,';
    $query .= '`clicked` DATETIME NOT NULL,';
    $query .= '`counter` INT NOT NULL,';
    $query .= 'PRIMARY KEY (`id`, `owner`)';
    $query .= ') TYPE = MYISAM ;';
    sql_query($query);

    $query = 'CREATE TABLE IF NOT EXISTS '.
      sql_table('plug_blogroll_groups').'(';
    $query .= '`id` INT NOT NULL AUTO_INCREMENT,';
    $query .= '`owner` INT(11) NOT NULL,';
    $query .= '`name` VARCHAR(30) NOT NULL,';
    $query .= '`desc` VARCHAR(255),';
    $query .= 'PRIMARY KEY (`id`, `owner`)';
    $query .= ') TYPE = MYISAM ;';
    sql_query($query);
  }

  function unInstall() {
    if ($this->getOption('del_uninstall') == "yes") {
      sql_query('DROP TABLE ' .sql_table('plug_blogroll_links').'; DROP TABLE '
        .sql_table('plug_blogroll_groups'));
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

  function doSkinVar ($skinType, $type, $id, $sortfield = 'order',
    $sortorder = 'asc', $groupdesc = '', $numlinks = -1) {
		global $member;
    switch ($type) {
      case "link": echo $this->makeLink($id); break;
      case "group":
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
          $query = sql_query("SELECT `id`, `name`, `desc` FROM `".
            sql_table('plug_blogroll_groups')."` WHERE `name`=\"".
            $groups[$i]['name'].'"');
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
             'groupdesc' => $groupdesc == '' ?
              $groups[0]['desc'] : $groupdesc
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
        $query = "SELECT * FROM `".sql_table('plug_blogroll_links').
          "` WHERE ";
        foreach ($groups as $group) {
          if ($i++ > 0) $query .= " OR ";
          $query .= "`group`=".$group['id'];
        }
        $query .= " ORDER BY $sortfield $sortorder";
				if ($numlinks >= 0) $query .= " LIMIT $numlinks";
        $query = sql_query($query);

        // Output links
        if ($type == "group") while ($result = mysql_fetch_assoc($query))
          echo $this->makeLink($result['id'], $result['url'], $result['text'],
					  $result['title'], $result['counter'], $result['created']);
        elseif ($type == "random") while ($result = mysql_fetch_assoc($query)) {
          //Insert randomisation code here
        }

        // Output footers
        echo TEMPLATE::fill($this->getOption('tplListFooter'), $groupVars);
        echo TEMPLATE::fill($this->getOption('tplFooter'), $groupVars);
        break;
    }
  }
  
  function makeLink($id, $url='', $text='', $title='', $counter='',
	  $clicked='', $created='') {
    if ($url == '') {
		  $query = sql_query("SELECT * FROM `".sql_table('plug_blogroll_links').
        "` WHERE `id`=$id");
      $result = mysql_fetch_assoc($query);
      if ($result == null) {
        if ($this->getOption("error") == "yes")
          return ("ID #$id not found in the database.");
      }
			else {
			  $url = $result['url'];
				$text = $result['text'];
				$title = $result['title'];
				$counter = $result['counter'];
				$created = $result['created'];
				$clicked = $result['clicked'];
			}
		}
    $linkVars = array (
		  'linkid' => $id,      
			'linkurl' => $this->getOption("redirect") == "yes" ?
			  $this->getAdminURL()."?n=$id" : $url,
      'linktext' => htmlentities($text),
      'linktitle' => $title == "" ?
        htmlentities($text) : htmlentities($title),
  		'linkcreated' => $created
		);
		if ($this->getOption("redirect") == "yes") {
		  $extraLinkVars = array(
    		'linkclicked' => $clicked,
        'linkcounter' => $counter
			);
			$linkVars = array_merge($linkVars, $extraLinkVars);
		}
    return (TEMPLATE::fill($this->getOption('tplItem'), $linkVars));
  }
}
?>
