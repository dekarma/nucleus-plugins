<?php
/* Helper functions for Admin area of NP_SiteList plugin
 * A plugin for Nucleus CMS (http://nucleuscms.org)
 * (c)Frank Truscott, based on work by Wouter Demuynck
 * http://www.iai.com
 *
 * License information:
 * http://creativecommons.org/licenses/GPL/2.0/
 *
 */

	function slpagebar($thispage = '',$showlist = 'unchecked',$nshow = 1,$npages = 1,$pg = 1,$slquery = '') {
		echo '<div style="background-color:#dddddd;padding:2px 0 2px 0;">'."\n";
		echo '<small>';
		echo _SITELIST_PAGE.' '.$pg.' '._SITELIST_OF.' '.$npages.' |';
		echo ' <a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.($pg > 1 ? $pg-1:1).'&amp;slquery='.$slquery.'">&lt;&lt;</a> '."\n";
		echo ' | <a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg=1&amp;slquery='.$slquery.'">'._SITELIST_FIRST.'</a> |'."\n";

		if ($npages <= 11) {
			$i = 1;
			$end = $npages;
		}
		else {
			if ($pg <= 6) {
				$i = 1;
				$end = 11;
			}
			elseif ($pg >= $npages - 5) {
				$i = $npages - 11;
				$end = $npages;
			}
			else {
				$i = $pg - 5;
				$end = $pg + 5;
			}
		}

		if ($i > 1) echo ' ... ';
		while ($i <= $end) {
			echo '<a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.$i.'&amp;slquery='.$slquery.'">'.($i == $pg ? '<big><span style="text-decoration:underline;color:#ff0000;">'.$i.'</span></big>':$i).'</a> - '."\n";
			$i++;
		}
		if ($i - 1 < $npages) echo ' ... ';
		echo ' | <a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.$npages.'&amp;slquery='.$slquery.'">'._SITELIST_LAST.'</a> |'."\n";
		echo ' <a href="'.$thispage.'?showlist='.$showlist.'&amp;safe=true&amp;nshow='.$nshow.'&amp;pg='.($pg < $npages ? $pg+1:$npages).'&amp;slquery='.$slquery.'">&gt;&gt;</a> |'."\n";
		echo '</small></div><br />'."\n";
	}

	function sllisturl($site = '',$admin = 0,$verify = 0,$nshow = 1,$showlist = 'unchecked',$thispage = '',$action_url = '',$testerpage = '') {
		echo '<li>'."\n";
		if ($showlist == 'search') {
			echo '<span style="color:red;font-size:smaller;">'."\n";
			echo '[';
			switch ($site->checked) {
			case 0:
				if (!$site->suspended) echo 'Submitted';
				else echo _SITELIST_SUSPENDED.' ('.$site->suspended.')';
				break;
			case 1:
				echo _SITELIST_APPROVED;
				break;
			case 2:
				echo _SITELIST_EXEMPTED;
				break;
			default:
				if ($site->suspended) echo _SITELIST_SUSPENDED.' ('.$site->suspended.')';
				break;
			}
			echo '] ';
			echo '</span>';
		}
		echo '<a href="',htmlentities($site->url).'" ';
		if ($site->alt)
			echo 'title="',htmlentities($site->alt).'" ';
		echo '>'.$site->title.($site->suspended > 0 ? ' ('.$site->suspended.')':'').'</a>'."\n";
		if ($admin) {
			echo ' <small>&lt;|'."\n";
			echo ' <a href="'.$thispage.'?showlist=modurl&amp;safe=true&amp;nshow='.$nshow.'&amp;surl='.htmlentities($site->ourl).'&amp;sdesc='.$site->title.'">'._SITELIST_EDIT.'</a> |'."\n";
			echo ' <a href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=deleteurl&amp;url='.htmlentities($site->ourl).'">'._SITELIST_DELETE.'</a> |'."\n";
			if ($site->checked == 0) echo ' <a href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=checked&amp;url='.htmlentities($site->url).'">'._SITELIST_APPROVE.'</a> |'."\n";
			echo ' <a href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=suspendurl&amp;url='.htmlentities($site->url).'&amp;nsusp='.$site->suspended.'">'._SITELIST_SUSPEND.'</a> |'."\n";
			if ($verify) {
				if ($site->checked < 2) echo ' <a href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=exempturl&amp;url='.htmlentities($site->url).'">'._SITELIST_EXEMPT.'</a> |'."\n";
				echo ' <a href="'.$action_url.'?action=plugin&amp;name=SiteList&amp;type=verifyurl&amp;url='.htmlentities($site->url).'&amp;nsusp='.$site->suspended.'">'._SITELIST_VERIFY.'</a> |'."\n";
				echo ' <a href="'.$testerpage.'?testurl='.htmlentities($site->url).'&amp;showlist='.$showlist.'&amp;list=1">'._SITELIST_MANUAL_VERIFY.'</a> |'."\n";
			}
		echo '&gt;</small>'."\n";
		}
		echo '</li>'."\n\n";
	}

	function slsearchform($slquery) {
		echo '<form method="post" action="">'."\n";
		echo '<input type="hidden" name="showlist" value="search" />'."\n";
		echo '<input name="slquery" maxlength="30" size="30" accesskey="4" value="'.$slquery.'" />'."\n";
		echo '<input type="submit" value="'._SITELIST_ADMIN_SEARCH.'" />'."\n";
	}
?>
