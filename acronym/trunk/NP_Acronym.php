<?php
/* NP_Acronym
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * © 2005 Joel Pan
 * http://ketsugi.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 * Changelog:
 * 1.03   22-11-2005
 *   Acronym matches are no longer case-sensitive.
 *   Code optimised to reduce number of SQL queries per page.
 * 1.02   04-10-2005
 *   New regex for better acronym replacing.
 * 1.01   28-09-2005
 *   Minor bug fix in acronyms.php
 * 1.0    27-09-2005
 *   Initial release.
 */
 
class NP_Acronym extends NucleusPlugin {
  
  var $acronym_array;

  function getName() { return 'Acronym';  }
  function getAuthor() { return 'Joel Pan'; }
  function getURL() { return 'http://wakka.xiffy.nl/acronym'; }
  function getVersion() { return '1.03';  }
  function getDescription() { return 'Replaces acronyms in items/comments with full <acronym> tags and allows you to set custom acronyms.'; }
  function hasAdminArea() { return 1; }
  function supportsFeature($what) {
    switch($what) {
      case 'SqlTablePrefix': return 1;
      default: return 0;
    }
  }
  function getTableList() { return array(sql_table('plug_acronym')); }
  
  function install() {
    //Create options
    $this->createOption("item", "Replace acronyms in blog items?", "yesno", "yes");
    $this->createOption("comment", "Replace acronyms in blog comments?", "yesno", "yes");
    $this->createOption("quickmenu", "Show in quick menu?", "yesno", "yes");
    $this->createOption("del_uninstall", "Delete tables on uninstall?", "yesno", "no");

    //Create database table
    sql_query("CREATE TABLE IF NOT EXISTS `".sql_table('plug_acronym')."` ( `acro` VARCHAR ( 20 ) NOT NULL PRIMARY KEY, `full` VARCHAR( 255 ) NOT NULL ) TYPE = MYISAM ;");
    $this->_defaultAcronyms();
    
  }
  
  function unInstall() {
    if ($this->getOption('del_uninstall') == "yes") sql_query('DROP TABLE `'.sql_table('plug_acronym').'`;');
  }
  
  function getEventList() { 
    return array('PreComment','PreItem', 'QuickMenu'); 
  }   
  
  function init() {
    $query = 'SELECT `acro`,`full` FROM `'.sql_table('plug_acronym').'`';
    $result = sql_query($query);
    $acronyms = array();
    $fulltext = array();

    while ($row = mysql_fetch_assoc($result)) {
      array_push($acronyms, $row['acro']);
      array_push($fulltext, $row['full']);
    }
    
    //$data = preg_replace($acronyms, $fulltext, $data);
    $this->acronym_array = $this->_array_combine_emulated($acronyms, $fulltext);
  }
  
  function event_PreComment($data) {
    if ($this->getOption('comment') != 'yes') return;
    $this->_replaceAcronyms($data['comment']['body']);
  }
  
  function event_PreItem($data) {
    if ($this->getOption('item') != 'yes') return;
    $this->_replaceAcronyms($data['item']->body); 
    $this->_replaceAcronyms($data['item']->more);     
  }

  function event_QuickMenu(&$data) {
    global $member;
    // only show when option enabled
    if ($this->getOption('quickmenu') == 'yes' && $member->isLoggedIn()) {
      array_push($data['options'],
        array('title' => 'Acronyms',
          'url' => $this->getAdminURL(),
          'tooltip' => 'Manage acronyms'));
    }
  }
    
  function _replaceAcronyms(&$data) {  
    $data = " $data ";
    foreach ($this->acronym_array as $acronym => $fulltext)
      $data = preg_replace("|(?!<[^<>]*?)(?<![?./&])\b$acronym\b(?!:)(?![^<>]*?>)|msU","<acronym title=\"$fulltext\">$acronym</acronym>" , $data);
    $data = trim($data);
  }
  
  function _defaultAcronyms() {
    $query = "INSERT INTO `".sql_table('plug_acronym')."` ( `acro` , `full` )
      VALUES ('AFAIK', 'As far as I know'),
      ('AIM', 'AOL Instant Messenger'),
      ('AJAX', 'Asynchronous JavaScript and XML'),
      ('AOL', 'America Online'),
      ('API', 'Application Programming Interface'),
      ('ASAP', 'As soon as possible'),
      ('ASCII', 'American Standard Code for Information Interchange'),
      ('ASP', 'Active Server Pages'),
      ('BTW', 'By The Way'),
      ('CD', 'Compact Disc'),
      ('CGI', 'Common Gateway Interface'),
      ('CMS', 'Content Management System'),
      ('CSS', 'Cascading Style Sheets'),
      ('CVS', 'Concurrent Versions System'),
      ('DBA', 'Database Administrator'),
      ('DHTML', 'Dynamic HyperText Markup Language'),
      ('DMCA', 'Digital Millenium Copyright Act'),
      ('DNS', 'Domain Name Server'),
      ('DOM', 'Document Object Model'),
      ('DTD', 'Document Type Definition'),
      ('DVD', 'Digital Video Disc'),
      ('EOF', 'End of file'),
      ('EOL', 'End of line'),
      ('EOM', 'End of message'),
      ('EOT', 'End of text'),
      ('FAQ', 'Frequently Asked Questions'),
      ('FDL', 'GNU Free Documentation License'),
      ('FTP', 'File Transfer Protocol'),
      ('FUD', 'Fear, Uncertainty, and Doubt'),
      ('GB', 'Gigabyte'),
      ('GHz', 'Gigahertz'),
      ('GIF', 'Graphics Interchange Format'),
      ('GPL', 'GNU General Public License'),
      ('GUI', 'Graphical User Interface'),
      ('HDD', 'Hard Disk Drive'),
      ('HTML', 'HyperText Markup Language'),
      ('HTTP', 'HyperText Transfer Protocol'),
      ('IANAL', 'I am not a lawyer'),
      ('ICANN', 'Internet Corporation for Assigned Names and Numbers'),
      ('IE', 'Internet Explorer'),
      ('IE5', 'Internet Explorer 5'),
      ('IE6', 'Internet Explorer 6'),
      ('IIRC', 'If I remember correctly'),
      ('IIS', 'Internet Information Services'),
      ('IM', 'Instant Message'),
      ('IMAP', 'Internet Message Access Protocol'),
      ('IMHO', 'In my humble opinion'),
      ('IMO', 'In my opinion'),
      ('IOW', 'In other words'),
      ('IP', 'Internet Protocol'),
      ('IRC', 'Internet Relay Chat'),
      ('IRL', 'In real life'),
      ('ISO', 'International Organization for Standardization'),
      ('ISP', 'Internet Service Provider'),
      ('JDK', 'Java Development Kit'),
      ('JPEG', 'Joint Photographics Experts Group'),
      ('JPG', 'Joint Photographics Experts Group'),
      ('JS', 'JavaScript'),
      ('KB', 'Kilobyte'),
      ('KISS', 'Keep it simple, stupid'),
      ('LGPL', 'GNU Lesser General Public License'),
      ('LOL', 'Laughing out loud'),
      ('MB', 'Megabyte'),
      ('MHz', 'Megahertz'),
      ('MIME', 'Multipurpose Internet Mail Extension'),
      ('MIT', 'Massachusetts Institute of Technology'),
      ('MML', 'Mathematical Markup Language'),
      ('MPEG', 'Motion Picture Experts Group'),
      ('MS', 'Microsoft'),
      ('MSDN', 'Microsoft Developer Network'),
      ('MSIE', 'Microsoft Internet Explorer'),
      ('MSN', 'Microsoft Network'),
      ('OMG', 'Oh my goodness'),
      ('OPML', 'Outline Processor Markup Language'),
      ('OS', 'Operating System'),
      ('OSS', 'Open Source Software'),
      ('OTOH', 'On the other hand'),
      ('P2P', 'Peer to Peer'),
      ('PDA', 'Personal Digital Assistant'),
      ('PDF', 'Portable Document Format'),
      ('PHP', 'Pre-Hypertext Processing'),
      ('PICS', 'Platform for Internet Content Selection'),
      ('PIN', 'Personal Identification Number'),
      ('PITA', 'Pain in the Ass'),
      ('PNG', 'Portable Network Graphics'),
      ('POP', 'Post Office Protocol'),
      ('POP3', 'Post Office Protocol 3'),
      ('Perl', 'Practical Extraction and Report Language'),
      ('QoS', 'Quality of Service'),
      ('RAID', 'Redundant Array of Inexpensive Disks'),
      ('RDF', 'Resource Description Framework'),
      ('ROFL', 'Rolling on the floor laughing'),
      ('ROFLMAO', 'Rolling on the floor laughing my ass of'),
      ('RPC', 'Remote Procedure Call'),
      ('RSS', 'Really Simple Syndication'),
      ('RTF', 'Rich Text File'),
      ('RTFM', 'Read The Fucking Manual'),
      ('SCSI', 'Small Computer System Interface'),
      ('SDK', 'Software Development Kit'),
      ('SGML', 'Standard General Markup Language'),
      ('SMIL', 'Synchronized Multimedia Integration Language'),
      ('SMTP', 'Simple Mail Transfer Protocol'),
      ('SOAP', 'Simple Object Access Protocol'),
      ('SQL', 'Structured Query Language'),
      ('SSH', 'Secure Shell'),
      ('SSI', 'Server Side Includes'),
      ('SSL', 'Secure Sockets Layer'),
      ('SVG', 'Scalable Vector Graphics'),
      ('TIA', 'Thanks In Advance'),
      ('TIFF', 'Tagged Image File Format'),
      ('TLD', 'Top Level Domain'),
      ('TOC', 'Table of Contents'),
      ('URI', 'Uniform Resource Identifier'),
      ('URL', 'Uniform Resource Locator'),
      ('URN', 'Uniform Resource Name'),
      ('USB', 'Universal Serial Bus'),
      ('VB', 'Visual Basic'),
      ('VBA', 'Visual Basic for Applications'),
      ('W3C', 'World Wide Web Consortium'),
      ('WAN', 'Wide Area Network'),
      ('WAP', 'Wireless Access Protocol'),
      ('WML', 'Wireless Markup Language'),
      ('WTF', 'What the fuck'),
      ('WWW', 'World Wide Web'),
      ('WYSIWYG', 'What You See Is What You Get'),
      ('XHTML', 'eXtensible HyperText Markup Language'),
      ('XML', 'eXtensible Markup Language'),
      ('XSL', 'eXtensible Stylesheet Language'),
      ('XSLT', 'eXtensible Stylesheet Language Transformations'),
      ('XUL', 'XML User Interface Language'),
      ('YMMV', 'Your mileage may vary')";
    sql_query($query);
  }
  
  function _array_combine_emulated( $keys, $vals ) {
    $keys = array_values( (array) $keys );
    $vals = array_values( (array) $vals );
    $n = max( count( $keys ), count( $vals ) );
    $r = array();
    for( $i=0; $i<$n; $i++ ) {
      $r[ $keys[ $i ] ] = $vals[ $i ];
    }
    return $r;
  }
}