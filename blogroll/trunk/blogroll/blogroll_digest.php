<?php
  /*
    This is a highly Edmondlize script to do a Blogroll digest. It writes a post and list all new blogroll items in the past 24 hours.

    It may not work for others unless modify.....
  */
  $strRel = '../../../';
  include($strRel . 'config.php');
  include('../digest.inc'); // define $user and $password in here

  $body ="";
  $group = 0;

  $query = sql_query("SELECT * FROM ". sql_table('plug_blogroll_links') . " as a WHERE DATE_SUB(CURDATE(),INTERVAL 1 DAY) <= a.created ORDER BY a.group");
  while ($row = mysql_fetch_object($query)) 
  {
    if ($group != $row->group)
    {
      if ($group != 0)
      {
        $body .= "</ul><br/>\n";
      }
      $group = $row->group;
      $query2 = sql_query("SELECT * FROM " . sql_table('plug_blogroll_groups') . " WHERE id = " . $group);
      $row2 = mysql_fetch_object($query2);
      $body .= "\nFrom \"". $row2->desc . "\":\n<ul>\n";
    }

    $body .= "<li><a href=\"" . htmlspecialchars($row->url) ."\">" . $row->text . "</a>";

    if ($row->desc != "")
    {
      $body .= " - " . $row->desc;
    }

    if ($row->comment != "")
    {
      $body .= "<br/>- " . $row->comment;
    }

    $body .= "</li>\n";
  }

  if ($group != 0) {
    $body .= "</ul>\n";
  }

  echo $body;

  // exit for testing
  if ($_GET['test'] == 'yes') {
    return;
  }

  if ($body != "")
  {
    global $manager, $blog, $CONF;

    // login 
    $mem = new MEMBER();
    if (!$mem->login($user, $password))
    {
      echo "Unable to login";
      return;
    }

    $blog =& $manager->getBlog(1);
    $blogid = $blog->blogid;
    if (!BLOG::existsID($blogid)) {
      echo "No such blog";
      return ;
    }

    if (!$mem->teamRights($blogid)) {
      echo "Not a team member";
      return;
    }

    $title = date("j/n/Y",time()) . " - 網摘 Blogroll digest";
    $timestamp = $blog->getCorrectTime();
    $category = $blog->getCategoryIdFromName("Blogroll digest");

    $blog->setConvertBreaks(0);
    $blog->additem($category, $title, $body, "", $blogid, $mem->getID(), $timestamp, 0, 0);

    echo "Digest posted<br/>";
  }
?>
