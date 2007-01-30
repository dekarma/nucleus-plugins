<?php
	/* ==========================================================================================
	* Censor for NucleusCMS
	*
	* Copyright 2007 by Frank Truscott
	* ==========================================================================================
	* This program is free software and open source software; you can redistribute
	* it and/or modify it under the terms of the GNU General Public License as
	* published by the Free Software Foundation; either version 2 of the License,
	* or (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful, but WITHOUT
	* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
	* FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
	* more details.
	*
	* You should have received a copy of the GNU General Public License along
	* with this program; if not, write to the Free Software Foundation, Inc.,
	* 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
	* http://www.gnu.org/licenses/gpl.html
	* ==========================================================================================
	*/

	/* History
	 *
	 * 1.0 (01/26/2006) - Initial Release
	 * 1.01 (01/29/2006) - Add item title to Censored fields
	 *
	 */

class NP_Censor extends NucleusPlugin
{
	// name of plugin
	function getName()
	{
		return 'Censor';
	}

	// author of plugin
	function getAuthor()
	{
		return 'Frank Truscott';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL()
	{
		return 'http://www.iai.com/';
	}

	// version of the plugin
	function getVersion()
	{
		return '1.01';
	}

	// a description to be shown on the installed plugins listing
	function getDescription()
	{
        $porderwarn = "";
        if (checkPlugin("NP_CommentControl")) {
            $porderwarn = "*** Censor must appear above CommentControl in the list of Plugins *** ";
        }
		return 'Censors a list of words from comments and items in a blog.'.$porderwarn;
	}

    function getEventList() { return array('PreComment','PreAddComment','PreItem'); }

    function install() {
        $this->createBlogOption('bannedWords', 'A comma separated list of words to ban in this blog: ', 'textarea', '', '');
        $this->createBlogOption('blockComments', 'Should comments with banned words be blocked: ', 'yesno', 'no', '');
		$this->createBlogOption('blog_censorItems', 'Should the censor run on items in this blog?: ', 'yesno', 'yes', '');
		$this->createOption('def_bannedWords', 'A comma separated list of words to ban in all blogs: ', 'textarea', '', '');
		$this->createOption('force_censorItems', 'Should the item censor be enabled for all blogs?: ', 'yesno', 'yes', '');
		$this->createOption('force_blockComments', 'Should comments with banned words be blocked for all blogs?: ', 'yesno', 'no', '');
    }

	function event_PreComment(&$data) {
        $bid = $this->getCommentBlogId($data);
        $bannedwords = $this->getBannedWords($bid);
		//print_r($data['comment']);
        foreach ($bannedwords as $value) {
			$value = $this->prepBannedWord($value);
			$data['comment']['body'] =	$this->doCensor($data['comment']['body'],$value);
		}
    }

	function event_PreAddComment(&$data) {
        $bid = $this->getCommentBlogId($data);
		if ($this->getBlogOption($bid, 'blockComments') == 'yes' || $this->getOption('force_blockComments') == 'yes') {
			$bannedwords = $this->getBannedWords($bid);
			$nbw = 0;
			foreach ($bannedwords as $value) {
				$value = $this->prepBannedWord($value);
				if (strpos(strtolower($data['comment']['body']), strtolower($value)) === false) {}
				else {
                    $nbw += 1;
					if (function_exists('str_ireplace')) {
						$data['comment']['body'] =	str_ireplace($value, '<u><b>'.$value.'</b></u>', $data['comment']['body']);
					}
					else {
						$modvalue = str_replace(array('^', '$', '.', '[', ']', '|', '(', ')', '?', '*', '+', '{', '}'),
									array('\^','\$','\.','\[','\]','\|','\(','\)','\?','\*','\+','\{','\}'),
									$value);
						$data['comment']['body'] = preg_replace("|".$modvalue."|i", '<u><b>'.$value.'</b></u>', $data['comment']['body']);
					}
                }
            }
            if ($nbw > 0) {
                $objectiontext = "Your comment can not be accepted due to the inclusion of objectional phrases as indicated below.";
                $data['error'] = $objectiontext."<p />".$data['comment']['body'];
                doError($data['error']);
            }
		}
    }

	function event_PreItem(&$data) {
		$currentItem = &$data["item"];
		//print_r($data['item']);
        $bid = $this->getItemBlogId($data);
		if ($this->getBlogOption($bid, 'blog_censorItems') == 'yes' || $this->getOption('force_censorItems') == 'yes') {
			$bannedwords = $this->getBannedWords($bid);
			foreach ($bannedwords as $value) {
				$value = $this->prepBannedWord($value);
				$currentItem->body = $this->doCensor($currentItem->body,$value);
				$currentItem->more = $this->doCensor($currentItem->more,$value);
				$currentItem->title = $this->doCensor($currentItem->title,$value);
			}
		}
    }

	function doSkinVar($skinType)
	{

	}

	function supportsFeature ($what)
	{
		switch ($what)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function prepBannedWord($badword) {
		$badword = trim(str_replace(array('"',"'"),'',$badword));
		return $badword;
	}

	function getCommentBlogId($data) {
		global $blogid;
		if ($blogid == "") {
			$iid = intval($data['comment']['itemid']);
			$bid = getBlogIDFromItemID($iid);
		}
		else {
			$bid = $blogid;
		}
		return $bid;
	}

	function getItemBlogId($data) {
		global $blogid;
		if ($blogid == "") {
			$iid = intval($data['item']->itemid);
			$bid = getBlogIDFromItemID($iid);
		}
		else {
			$bid = $blogid;
		}
		return $bid;
	}

	function getBannedWords($bid) {
		$badwords = $this->getOption('def_bannedWords').",".$this->getBlogOption($bid,'bannedWords');
		$bannedwords = explode(',', $badwords);
		return $bannedwords;
	}

	function doCensor($textSubject,$badword) {
		if ($badword != '') {
			if (function_exists('str_ireplace')) {
				$textSubject =	str_ireplace($badword, '****', $textSubject);
			}
			else {
				$badword = str_replace(array('^','$','.','[',']','|','(',')','?','*','+','{','}'),
							array('\^','\$','\.','\[','\]','\|','\(','\)','\?','\*','\+','\{','\}'),
							$badword);
				$textSubject = preg_replace("|".$badword."|i", '****', $textSubject);
			}
		}
		return $textSubject;
	}
}
?>