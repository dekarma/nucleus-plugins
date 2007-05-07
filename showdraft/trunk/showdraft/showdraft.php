<?
/*
  NP_ShowDraft:showdraft.php - Preview a draft

  By Edmond Hui (admun)

  v0.1 - initialize release
*/

  include('../../../config.php');
  global $manager, $blog, $CONF;

  $query = 'SELECT iblog FROM '. sql_table(item) .' WHERE inumber=' . intval($itemid);
  $res = sql_query($query);
  $obj = mysql_fetch_object($res);
  $blogid = $obj->iblog;

  if ($blogid) {
    $b =& $manager->getBlog($blogid);
  } else if ($blog) {
    $b =& $blog;
  } else {
    $b =& $manager->getBlog($CONF['DefaultBlog']);
  }

  echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n<html>\n<head>";
  echo "<title>NP_ShowDraft: Draft Preview</title>\n";
  echo "</head>\n";
  echo "<body>\n";

  $plugin =& $manager->getPlugin('NP_ShowDraft');
  $template = $plugin->getOption('parsetemplate');
  $query = $b->getSqlBlog(' and inumber=' . intval($itemid));
  $query = str_replace("i.idraft=0", "i.idraft=1", $query); // Well, we are previewing a draft....
  $b->showUsingQuery($template, $query, '', 0, 0);

  echo "</body>\n</html>\n";
?>
