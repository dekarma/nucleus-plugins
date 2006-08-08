<?php
 
class NP_CruftFree extends NucleusPlugin {
  
  //SQL Table Name
  var $table;
  
  function getName()  { return 'Cruft Free URLs'; }
  function getAuthor() { return 'Joel Pan'; }
  function getURL()     { return 'http://ketsugi.com/'; }
  function getVersion() { return '0.1.20060202a'; }
  function getDescription() { return 'Enhances fancy URL support, allowing for customisable cruft-free URLs.'; }
  function supportsFeature($what) {
    switch($what) {
      case 'SqlTablePrefix': return 1;
      default: return 0;
    }
  }
  function getMinNucleusVersion() { return 322; }
	function getMinNucleusPatchLevel() { return 0; }  
  function getEventList() {
    return array('ParseURL', 'GenerateURL', 'PostPluginOptionsUpdate');
  }
  function getTable() { return array($this->table); }
  
  function init() {
    //Set SQL table name
    $this->table = sql_table('plug_cruftfree');
  }

  function install() {
    //Create plugin options
    $this->createOption('blogTpl','URL template for blogs:','text','blog/<%blogid%>/');
    $this->createOption('memTpl','URL template for member pages:','text','member/<%memberid%>/');
    $this->createOption('itemTpl','URL template for item links','text','item/<%itemid%>/');
    $this->createOption('catTpl','URL template category pages:','text','category/<%catid%>/');
    $this->createOption('arcTpl','URL template for archived item pages:','text','archive/<%blogid%>/<%date%>/');
    $this->createOption('arcsTpl','URL template for archive list pages:','text','archives/<%blogid%>/');
    $this->createOption('spaceReplace','The character which replaces spaces in URL titles (dashes are recommended)','select','-', 'none||underscore|_|dash|-|dot|.');
    $this->createOption("del_uninstall", "Delete tables on uninstall?", "yesno","no");
    $this->createItemOption('slug','Custom post slug (leave blank for default)','text','');

    //Check if SQL table already exists.
    $result = sql_query("SHOW TABLES LIKE '$this->table'");
    $data = mysql_num_rows($result);
    if ($data == 0) { //If not exists, create new table
      sql_query("CREATE TABLE IF NOT EXISTS `$this->table` ( `itemid` int(11) NOT NULL, `slug` VARCHAR (255), PRIMARY KEY (`itemid`,`slug`) );");
    }
    else { //Else set item options for custom slugs from existing table
      $result = sql_query("SELECT * FROM `$this->table`");
      while ($row = mysql_fetch_object($result)) {
        $this->setItemOption($row->itemid,'slug',$row->slug);
      }
    }
    //Repopulate sql table with slugs
    $this->_refreshSlugs();
    
  }

  function unInstall() {
    if ($this->getOption('del_uninstall') == "yes") {
      sql_query("DROP TABLE `$this->table`");
    }
    else {
      //Truncate sql table
      sql_query("TRUNCATE TABLE `$this->table`");
      //Get all IDs
      $result = sql_query("SELECT `inumber` FROM `".sql_table('item')."`");
      //Insert custom slugs into sql table
      while ($row = mysql_fetch_object($result)) {
        $slug = $this->getItemOption($row->inumber,'slug');
        if ($slug != '') sql_query("INSERT INTO `$this->table` VALUES ($row->inumber,'$slug')");
      }
    }
  }
    
  function event_ParseURL(&$data) {
    // nothing to do if another plugin already parsed the URL
    if ($data['completed']) return;
    $urlInfo = $data['info'];
    $data['type'] = $this->_getKeyFromURL($urlInfo);
   
    global $itemid;
    switch ($data['type']) {
      
      case 'item':  // Item pages
        $itemid = -1;
        $values = $this->_getDataFromURL('item',$urlInfo); //Get data
        if ($values['itemid'] != '') $itemid = $values['itemid']; //See if itemID is specified and set
        elseif ($values['slug'] != '') $itemid = $this->_getItemID($values); //If not, get itemID from slug
        //echo $itemid; //DEBUG: itemid seems to be set correctly whether using the itemid lookup or the slug lookup
        $data['completed'] = true;
        break;
    }      
       
  }
    
  function event_GenerateURL(&$data) {
    // if another plugin already generated the URL
    if ($data['completed']) return;
          
    global $blog;
    $params = $data['params'];
       
    switch ($data['type']) {
      case 'item':      
        /* Generate URL for item lin    echo $this->blogid;
ks
        ** Available template variables:
        ** <%slug%> Item slug
        ** <%itemid%> Item ID
        ** <%d%> Day (as a two-digit number)
        ** <%m%> Month (as a two-digit number)
        ** <%y%> Year (as a two-digit number)
        ** <%Y%> Year (as a four-digit number)
        */
        
        //Get item slug
        $values = array (
          'slug' => $this->_getSlug($params['itemid']),
          'itemid' => $params['itemid'],
          'd' => date('d',$params['timestamp']),
          'm' => date('m',$params['timestamp']),
          'y' => date('y',$params['timestamp']),
          'Y' => date('Y',$params['timestamp'])
        );
        $data['url'] = $blog->settings['burl'].'/'.ltrim(TEMPLATE::fill($this->getOption('itemTpl'),$values),'/');
        $data['completed'] = true;
        return;            
    }
  }
  
  function event_PostPluginOptionsUpdate ($data) {
    switch($data['context']) {
      case 'item':
        //Update slug table
        $slug = $this->getItemOption($data['itemid'],'slug');
        sql_query("UPDATE `$this->table` SET `slug`='$slug' WHERE `itemid`=".$data['itemid']);        
    }
  }
  
  //Generates a URL-friendly slug given an item title
  function _makeSlug($title) {  
    $slug = trim($title);
    $search = array (
      "'<[\/\!]*?[^<>]*?>'si",
      "[/|'|\"]",
      "[À|Á|Â|Ã|Ä|Å|à|á|â|ã|ä|å|Ā|ā|Ă|ă|Ą|ą]",
      "[Æ|æ]",
      "[Ċ|ċ|Č|č|Ç|ç|Ć|ć|Ĉ|ĉ|©]",
      "[Œ|œ]",
      "[Ð|Ď|ď|Đ|đ|ð]",
      "[È|É|Ê|Ë|è|é|ê|ë|Ē|ē|Ĕ|ĕ|Ė|ė|Ę|ę|Ě|ě]",
      "[ƒ]",
      "[Ĝ|ĝ|Ğ|ğ|Ġ|ġ|Ģ|ģ]",
      "[Ĥ|ĥ|Ħ|ħ]",
      "[Ì|Í|Î|Ï|ì|í|î|ï|Ĩ|ĩ|Ī|ī|Ĭ|ĭ|Į|į|İ|ı]",
      "[Ĳ|ĳ]",
      "[Ĵ|ĵ]",
      "[Ķ|ķ|ĸ]",
      "[Ĺ|ĺ|Ļ|ļ|Ľ|ľ|Ŀ|ŀ|Ł|ł]",
      "[Ñ|ñ|Ń|ń|Ņ|ņ|Ň|ň|ŉ|Ŋ|ŋ]",
      "[Ò|Ó|Ô|Õ|Ö|Ø|ò|ó|ô|õ|ö|ø|Ō|ō|Ŏ|ŏ|Ő|ő]",
      "[Ŕ|ŕ|Ŗ|ŗ|Ř|ř|®]",
      "[ß|Ś|ś|Ŝ|ŝ|Ş|ş|Š|š]",
      "[Ţ|ţ|Ť|ť|Ŧ|ŧ]",
      "[Ù|Ú|Û|Ü|Ũ|ù|ú|û|ü|ũ|Ū|ū|Ŭ|ŭ|Ů|ů|Ű|ű|Ų|ų]",
      "[Ŵ|ŵ]",
      "[×]",
      "[Ý|ý|ÿ|Ŷ|ŷ|Ÿ]",
      "[Ź|ź|Ż|ż|Ž|ž]"
    );
    $replace = array("","","a","ae","c","ce","d","e","f","g","h","i","ij","j","k","l","n","o","r","s","t","u","w","x","y","z");
    $slug = preg_replace($search, $replace, $slug);
    preg_match_all('/[a-zA-Z0-9]+/', $slug, $slug);
    $slug = strtolower(implode('-', $slug[0]));
    return $slug;
  }  
  
  function _getSlug($itemid) {
    //Get slug from sql table
    $result = sql_query("SELECT `slug` FROM `$this->table` WHERE `itemid`=$itemid");
    $data = mysql_fetch_object($result);
    return($data->slug);
  }
  
  function _getItemID($data) {
    //Get slug from sql table
    $query = "SELECT `itemid` FROM `$this->table` AS `c`,`".sql_table('item')."` AS `i` WHERE `c`.`slug`='".$data['slug']."'";
    if (isset($data['Y'])) $query .= " AND YEAR(`i`.`itime`)=".$data['Y'];
    elseif (isset($data['y'])) $query .= " AND DATE_FORMAT(`i`.`itime`,'%y')=".$data['y'];
    if (isset($data['m'])) $query .= " AND DATE_FORMAT(`i`.`itime`,'%m')=".$data['m'];
    if (isset($data['d'])) $query .= " AND DATE_FORMAT(`i`.`itime`,'%d')=".$data['d'];
    $result = sql_query($query);
    $data = mysql_fetch_object($result);
    return($data->itemid);
  }  
  
  function _refreshSlugs() {
    //Truncate table
    sql_query("TRUNCATE TABLE `$this->table`");
    
    //Get all item IDs
    $result = sql_query("SELECT `inumber`,`ititle` FROM `".sql_table('item')."`");
    while ($row = mysql_fetch_object($result)) {
      $slug = $this->getItemOption($row->inumber,'slug');
      if ($slug == '') $slug = $this->_makeSlug($row->ititle); //Generate default slug
      sql_query("INSERT INTO `$this->table` VALUES ($row->inumber,'$slug')");
    }
  }
  
  function _getKeyFromURL($url) {
    $url = trim($url,'/');
    foreach (array('blog','member','item','category','archive','archives') as $type)
      if (ereg($this->_getRegexPattern($type,$this->_getRawTemplate($type)),$url)) return($type);
    return '';
  }
  
  function _getDataFromURL($type,$url) {
    global $blog;
    //Get raw template
    $rawTpl = $this->_getRawTemplate($type);
    
    //Get regex pattern to parse URL     
    $template = $this->_getRegexPattern($type,$rawTpl);
    
    //Get list of tpl vars
    $search = "<%([A-Za-z]+)%>";
    preg_match_all($search,$rawTpl,$vars);
    
    //Map vars to values, output into associative array $data
    ereg($template,$url,$values); //Get values from the given URL
    $i=1;
    foreach ($vars[1] as $var) {
      $data[$var]=$values[$i++];
    }

    return($data);
  }
  
  function _getRawTemplate ($type) {
    $tplNames = array('item' => 'itemTpl', 'archive' => 'arcTpl', 'archives' => 'arcsTpl', 'category' => 'catTpl', 'blog' => 'blogTpl', 'member' => 'memTpl');
    return(trim($this->getOption($tplNames[$type]),'/'));
  }
  
  function _getRegexPattern ($type,$rawTpl) {
    switch($type) {
      case 'item':                 
        $search = array('<%slug%>','<%itemid%>','<%d%>','<%m%>','<%y%>','<%Y%>');
        $replace = array('([a-zA-Z0-9-]+)','([1-9][0-9]*)','([0-9]{2})','([0-9]{2})','([0-9]{2})','([0-9]{4})');
        $template = str_replace($search,$replace,$rawTpl);
        break;
      default:
        $template = '$type';
    }
    return($template);
  }    
  
}
?>