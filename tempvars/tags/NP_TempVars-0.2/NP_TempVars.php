<?php
/* NP_TempVars plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott
 * http://www.iai.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 */

/*
* History
* [Version 0.2] - 05-23-2006 - initial release.
*/
class NP_TempVars extends NucleusPlugin
{
/*  place to add template vars without modifying core files
    called like TempsVar(actionname, parameter1, parameter2, etc...)
    so far we have the following defined:
        blogsetting, type[, blogname]
            where type is one of id, name, desc, short, url
            and blogname is a blog's shortname (optioinal)
            This is like the blogsetting skinvar, but chooses blog item belongs to
            not the blog of the displaying page.
        permalink
            This gives a link to item using the item's blog url and catid
        itemlink
            synonym of permalink
        smartmore
            This is the opposite of smartbody (if there is an extended, it is
            displayed, otherwise it displays the body)
        icategory, type
            where type is one of url, link, id, desc, name
            displays info about item's category, but url and link use item's
            blog url as base instead of calling page
        category, type
            synonym of icategory
        iauthor, type
            where type is one of realname, link, id, email, url, name
            displays info about item's author. link gives link to member area
            of item's blog, not the calling page.
        author, type
            synonym of iauthor
*/

   function getEventList() { return array(); }
   function getName() { return 'TempVars'; }
   function getAuthor() { return 'Frank Truscott'; }
   function getURL() { return 'http://www.iai.com/'; }
   function getVersion() { return '0.2'; }

   function getDescription()
   {
      return 'This plugin should be used in templates to add additional Template Variables';
   }

   function supportsFeature($what)
   {
      switch($what)
      {
         case 'SqlTablePrefix':
            return 1;
         default:
            return 0;
      }
   }

   // templatevar plugin
   function doTemplateVar(&$item) {
       //global $CONF;
       $params = func_get_args();
       array_shift($params);
       $action = array_shift($params);
       $params = array_merge(array(&$item),$params);
       call_user_func_array(array($this,'tv_'.$action),array(&$params));
   }

   function doTemplateCommentsVar(&$item, &$comment) {
       //$commentactions = array('commentauthor');
       $commentactions = array();
       $params = func_get_args();
       array_shift($params);
       array_shift($params);
       $action = array_shift($params);
       $keepcomments = in_array($action, $commentactions);
       if ($keepcomments) $params = array_merge(array(&$item,&$comment),$params);
       else $params = array_merge(array(&$item),$params);
       call_user_func_array(array($this,'tv_'.$action),array(&$params));
    }

   function tv_blogsetting(&$params) {
        global $manager;
        $item =& array_shift($params);
        if ($params[1]) $itemblogid = getBlogIDFromName($params[1]);
        else $itemblogid = getBlogIDFromItemID($item->itemid);
      $ib =& $manager->getBlog($itemblogid);
      $itemblog = $ib;
      switch ($params[0]) {
         case 'url':
            echo $itemblog->getURL();
            break;
         case 'short':
            echo $itemblog->getShortName();
            break;
         case 'id':
            echo $itemblogid;
            break;
         case 'desc':
            echo $itemblog->getDescription();
            break;
         case 'name':
            echo $itemblog->getName();
            break;
         case 'link':
            echo $itemblog->getURL();
            break;
         default:
            echo $itemblog->getName();
            break;
      }
   }

   function tv_permalink(&$params) {
        global $manager, $CONF;
        $item =& array_shift($params);
        $itemblogid = getBlogIDFromItemID($item->itemid);
      $ib =& $manager->getBlog($itemblogid);
      $itemblog = $ib;
        $origitemurl = $CONF['ItemURL'];
        $itemurl = $itemblog->getURL();
        if ($CONF['URLMode'] == 'pathinfo') {
            $catkey = $CONF['CategoryKey'];
            $blogkey = $CONF['BlogKey'];
            if (substr($itemurl,-1,1) == '/') $itemurl = substr($itemurl,0,-1);
        }
        else {
            $catkey = 'catid';
            $blogkey = 'blogid';
        }
        $CONF['ItemURL'] = $itemurl;
        $catid = $item->catid;
        $extra = array($catkey => $catid, $blogkey => $itemblogid);
        $permalink = createLink('item',array('itemid' => $item->itemid, 'extra' => $extra));
        echo $permalink;
        $CONF['ItemURL'] = $origitemurl;
   }

   function tv_itemlink(&$params) {
       call_user_func_array(array($this,'tv_permalink'),array(&$params));
   }

   function tv_smartmore(&$params) {

        global $manager;
        $item =& array_shift($params);
        $itemblogid = getBlogIDFromItemID($item->itemid);
      $ib =& $manager->getBlog($itemblogid);
      $itemblog = $ib;
        $actions = new ITEMACTIONS($itemblog);
      $parser = new PARSER($actions->getDefinedActions(),$actions);
      $actions->setHighlight($highlight);
      $actions->setParser($parser);
        if ($item->more) {
         $actions->highlightAndParse($item->more);
        }
        else {
         $actions->highlightAndParse($item->body);
      }
   }

      function tv_icategory(&$params) {
        global $manager, $CONF;
        $item =& array_shift($params);
        $itemblogid = getBlogIDFromItemID($item->itemid);
      $ib =& $manager->getBlog($itemblogid);
      $itemblog = $ib;
        $origcaturl = $CONF['CategoryURL'];
        $caturl = $itemblog->getURL();
        if ($CONF['URLMode'] == 'pathinfo') {
            $catkey = $CONF['CategoryKey'];
            $blogkey = $CONF['BlogKey'];
            if (substr($caturl,-1,1) == '/') $caturl = substr($caturl,0,-1);
        }
        else {
            $catkey = 'catid';
            $blogkey = 'blogid';
        }
        $CONF['CategoryURL'] = $caturl;
        $catid = $item->catid;
        $extra = array($blogkey => $itemblogid);
      switch ($params[0]) {
         case 'url':
            echo createLink('category', array('catid' => $catid, 'extra' => $extra));
            break;
         case 'id':
            echo $catid;
            break;
         case 'desc':
            echo quickQuery('SELECT cdesc as result FROM '.sql_table('category').' WHERE catid='.intval($catid));
            break;
         case 'name':
            //echo quickQuery('SELECT cname as result FROM '.sql_table('category').' WHERE catid='.intval($catid));
                echo $item->category;
            break;
            case 'link':
            echo createLink('category', array('catid' => $catid, 'extra' => $extra));
            break;
         default:
            echo $catid;
            break;
      }
        $CONF['CategoryURL'] = $origcaturl;
    }

    function tv_category(&$params) {
        call_user_func_array(array($this,'tv_icategory'),array(&$params));
    }

      function tv_iauthor(&$params) {
        global $manager, $CONF;
        $item =& array_shift($params);
        $itemblogid = getBlogIDFromItemID($item->itemid);
      $ib =& $manager->getBlog($itemblogid);
      $itemblog = $ib;
        $origmemurl = $CONF['MemberURL'];
        $memurl = $itemblog->getURL();
        if ($CONF['URLMode'] == 'pathinfo') {
            $blogkey = $CONF['BlogKey'];
            $memkey = $CONF['MemberKey'];
            if (substr($memurl,-1,1) == '/') $memurl = substr($memurl,0,-1);
        }
        else {
            $memkey = 'memberid';
            $blogkey = 'blogid';
        }
        $CONF['MemberURL'] = $memurl;
        $memid = $item->authorid;
        $extra = array($blogkey => $itemblogid);
      switch($params[0])
      {
            case 'link':
            echo createLink('member', array('memberid' => $memid, 'extra' => $extra));
            break;
         case 'realname':
            echo $item->authorname;
            break;
         case 'id':
            echo $memid;
            break;
         case 'email':
            echo $item->authormail;
            break;
         case 'url':
            echo $item->authorurl;
            break;
         case 'name':
                echo $item->author;
                break;
         default:
            echo $item->author;
                break;
      }
        $CONF['MemberURL'] = $origmemurl;
   }

    function tv_author(&$params) {
        call_user_func_array(array($this,'tv_iauthor'),array(&$params));
    }

/*
// This is a test function to verify doTemplateCommentVar() works as desired
// see COMMENTACTIONS class in COMMENTS.php for ideas of using $comment object
    function tv_commentauthor(&$params) {
        $item =& array_shift($params);
        $comment =& array_shift($params);
        echo $comment['user'];
    }
*/
}
?>