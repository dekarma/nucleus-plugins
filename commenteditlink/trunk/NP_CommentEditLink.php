<?php
/*    Version history:
    - 1.0 (2003-12-07): initial version
    - 1.1. (2004-07-14): added supportsFeature()
    - 2.0 (2006-09-17): added ban and delete functions.

    This plugin allows the plugin author, or blog admin, to click a link near the comment
   (as dictated by placement of the template var) and quickly edit or delete the comment or ban the ip address from which the comment was submitted.

    Usage:
    <%CommentEditLink(linktype)% >
    Linktype can be edit, delete or ban.
    Ommitting a linktype defaults to all three links being inserted.
    */
class NP_CommentEditLink extends NucleusPlugin {
   function getName() {
      return 'CommentEditLink';
   }
    function getAuthor() {
      return 'Appie Verschoor (xiffy) | Edmond Hui (admun). version 2: jasper';
   }
    function getURL() {
      return '';
   }
    function getVersion() {
      return '2.0';
   }
    function getDescription() {
      return 'This plugin shows a link to delete the current comments. Link is only shown when the current comment belongs to the loggedin member, or
when the loggedin member has admin privileges. Usage, anywhere in the COMMENT TEMPLATE: &lt;%CommentEditLink(linktype)%&gt;. Linktype can be edit,
delete or ban. Ommitting a linktype defaults to all three links being inserted.';
   }
   function install() {
        $this->createOption('CommentEditLink','The edit link to be shown on the place where you use this plugin. only the part between <A HREF ..> and
</A>','text','edit');
        $this->createOption('CommentDeleteLink','The delete link to be shown on the place where you use this plugin. only the part between <A HREF ..>
and </A>','text','delete');
        $this->createOption('CommentBanLink','The ban link to be shown on the place where you use this plugin. only the part between <A HREF ..> and
</A>','text','ban');
   }
   function supportsFeature($what) {
        switch($what) {
            case 'SqlTablePrefix':
                return 1;
            default:
            return 0;
      }
   }

   function doTemplateCommentsVar(&$item, &$comments, $linktype = 'edit') {
        global $member, $manager, $CONF;
        if ($member->getID() >  0 && ($member->getID() == $comments['memberid'] || $member->canAlterComment($comments['commentid']))) {
         switch ($linktype) {
            case 'edit':
               // the next line must be on ONE LINE!!!!
               echo "<a onclick=\"window.open(this.href, 'popupeditwindow', 'width=720,height=560,scrollbars,resizable'); return false;\"href=\"".$CONF['AdminURL']."?action=commentedit&amp;commentid=".$comments['commentid']."\">".$this->getOption('CommentEditLink')."</a>";
               break;
            case 'ban':
               // the next line must be on ONE LINE!!!!
               echo "<a onclick=\"window.open(this.href, 'popupeditwindow', 'width=720,height=560,scrollbars,resizable'); return false;\"href=\"".$CONF['AdminURL']."?action=banlistnewfromitem&amp;itemid=".$comments['itemid']."&amp;ip=".$comments['ip']."\">".$this->getOption('CommentBanLink')."</a>";
               break;
               case 'delete':
                  // the next line must be on ONE LINE!!!!
               echo "<a onclick=\"window.open(this.href, 'popupeditwindow', 'width=720,height=560,scrollbars,resizable'); return false;\"href=\"".$CONF['AdminURL']."?action=commentdelete&amp;commentid=".$comments['commentid']."\">".$this->getOption('CommentDeleteLink')."</a>";
               break;
            default:
               // Inserts all three links, edit, delete and ban.
               // the next three lines must each be on ONE LINE!!!!
               echo "<a onclick=\"window.open(this.href, 'popupeditwindow', 'width=720,height=560,scrollbars,resizable'); return false;\"href=\"".$CONF['AdminURL']."?action=commentedit&amp;commentid=".$comments['commentid']."\">".$this->getOption('CommentEditLink')."</a>";
               echo " <a onclick=\"window.open(this.href, 'popupeditwindow', 'width=720,height=560,scrollbars,resizable'); return false;\"href=\"".$CONF['AdminURL']."?action=commentdelete&amp;commentid=".$comments['commentid']."\">".$this->getOption('CommentDeleteLink')."</a>";
               echo " <a onclick=\"window.open(this.href, 'popupeditwindow', 'width=720,height=560,scrollbars,resizable'); return false;\"href=\"".$CONF['AdminURL']."?action=banlistnewfromitem&amp;itemid=".$comments['itemid']."&amp;ip=".$comments['ip']."\">".$this->getOption('CommentBanLink')."</a>";
               break;
         }
      }
   }
}
?>
