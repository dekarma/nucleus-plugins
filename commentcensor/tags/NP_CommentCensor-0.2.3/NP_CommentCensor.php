<?php

class NP_CommentCensor extends NucleusPlugin
{
	// name of plugin
	function getName()
	{
		return 'Comment Censor';
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
		return '0.2.3';
	}

	// a description to be shown on the installed plugins listing
	function getDescription()
	{
        $porderwarn = "";
        if (checkPlugin("NP_CommentControl")) {
            $porderwarn = "*** CommentCensor must appear above CommentControl in the list of Plugins *** ";
        }
		return 'Censors a list of words from comments in a blog.'.$porderwarn;
	}

    function getEventList() { return array('PreComment','PreAddComment'); }

    function event_PreComment(&$data) {
        global $blogid;
		if ($blogid == "") {
			$iid = intval($data['comment']['itemid']);
			$bid = getBlogIDFromItemID($iid);
		}
		else {
			$bid = $blogid;
		}
        $bannedwords = explode(',', $this->getBlogOption($bid,'bannedWords'));
        foreach ($bannedwords as $value) {
			$value = trim(str_replace(array('"',"'"),'',$value));
			if (function_exists('str_ireplace')) {
            $data['comment']['body'] =	str_ireplace($value, '****', $data['comment']['body']);
			}
			else {
				$value = str_replace(array('^','$','.','[',']','|','(',')','?','*','+','{','}'),
							array('\^','\$','\.','\[','\]','\|','\(','\)','\?','\*','\+','\{','\}'),
							$value);
				$data['comment']['body'] = preg_replace("|".$value."|i", '****', $data['comment']['body']);
			}
        }
    }

    function event_PreAddComment(&$data) {
        global $blogid;
		if ($blogid == "") {
			$iid = intval($data['comment']['itemid']);
			$bid = getBlogIDFromItemID($iid);
		}
		else {
			$bid = $blogid;
		}

		if ($this->GetBlogOption($bid, 'blockComments') == 'yes') {
			$bannedwords = explode(',', $this->getBlogOption($bid,'bannedWords'));
            $nbw = 0;
            foreach ($bannedwords as $value) {
				$value = trim(str_replace(array('"',"'"),'',$value));
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

    function install() {
        $this->createBlogOption('bannedWords', 'A comma separated list of words to ban in this blog: ', 'textarea', '', '');
        $this->createBlogOption('blockComments', 'Should comments with banned words be blocked: ', 'yesno', 'no', '');
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

}
?>