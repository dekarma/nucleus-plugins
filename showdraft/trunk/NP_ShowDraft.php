<? 
/* This plugin display drafts written by a user

   History:
     Mar 4, 2004 v0.1 - Initial version
     Apr 27, 2004 v0.2 - Fixed doSkinVar error for error skin 
     May 4, 2004 v0.2a - Added min version support
     Jun 18, 2004 v0.3 - Added login check
     Nov 3, 2004 v0.4 - Added draft preview function (using modified code from NP_Print)
                 v0.4a - Using CONF['AdminURL']
     Jun 22, 2004 v0.9 - use sql_query
*/ 

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table'))
{
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_ShowDraft extends NucleusPlugin { 

  function getEventList() { return array(); } 
  function getName() { return 'ShowDraft'; } 
  function getAuthor() { return 'Edmond Hui'; } 
  function getURL() { return; } 
  function getVersion() { return '0.9'; } 
  function getDescription() { return 'This plugin display a list of draft items for a user'; } 
  // Note: I never run this plugin on 2.0 and have no idea whether it
  //       wil work on <2.5. A user can simply chnage it to return
  //       '200' and see if it works (likely will). I will gladly
  //       change the min version to 2.0 and add the sql_table fix
  //       upon such report. 8)
  function getMinNucleusVersion() { return '250'; } 
  function install() {
    $this->createOption('parsetemplate','Template for draft preview','text','grey/full');
  }

  function doSkinVar($skinType) { 

    global $member, $blog, $CONF;

    if ($skinType == "error")
    {
      return;
    }

    if (!$member->isLoggedIn())
    {
      return;
    }

    $res = sql_query("select ititle,inumber from " . sql_table('item') 
                       . " where idraft=1 and iauthor=" . $member->getID());

    if (mysql_affected_rows() == 0)
    {
      return;
    }

    echo "Draft blog item(s):<br>";
    echo "<ul>";

    while($row = mysql_fetch_object($res)) {
      echo "<li><a href=\"" . $CONF['AdminURL']
           . "index.php?action=itemedit&itemid="
	   . $row->inumber 
           . "\">" . $row->ititle . "</a> [<a href=\""
           . $CONF['AdminURL'] 
	   . "plugins/showdraft/showdraft.php?itemid="
           . $row->inumber
           ."\">preview</a>]";
    }

    echo "</ul>";
  } 

}
?>
