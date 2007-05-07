<?php

/*
    Version history:
    - 1.0 (2003-12-07): initial version
    - 1.1 (2004-07-14): added supportsFeature()
    
*/
class NP_CommentEditLink extends NucleusPlugin {



    function getName()    {    return 'CommentEditLink';     }
    function getAuthor()  { return 'Appie Verschoor (xiffy) | Edmond Hui (admun)';     }
    function getURL()     {    return 'http://wakka.xiffy.nl/CommentEditLink'; }
    function getVersion() {    return '1.1'; }
    function getDescription() { 
        return 'This plugin shows a link to edit the current comments. Link is only shown when the current comment belongs to the
loggedin member.
                Usage, anywhere in the COMMENT TEMPLATE: &lt;%CommentEditLink%&gt;';
    }
    
    function install() {
        $this->createOption('CommentEditLink','The link to be shown on the place where you use this plugin. only the part between <A HREF ..> and </A>','text','edit');
    }

    function supportsFeature($what) {
        switch($what) {
            case 'SqlTablePrefix':
                return 1;
            default:
                return 0;
        }
    }

    function doTemplateCommentsVar(&$item, &$comments) {
        global $member, $manager, $CONF;
        if ($member->getID() >  0                     &&
            ($member->getID() == $comments['memberid'] || $member->canAlterComment($comments['commentid']))) {
            // the next line must be on ONE LINE!!!!
            echo "<a onclick=\"window.open(this.href, 'popupeditwindow', 'width=720,height=560,scrollbars,resizable'); return false;\"
href=\"".$CONF['AdminURL']."?action=commentedit&amp;commentid=".$comments['commentid']."\">".$this->getOption(CommentEditLink)."</a>";
        }
    }
}
?>
