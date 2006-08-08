<?php

class NP_CollapsedContents extends NucleusPlugin {
	var $itemid;
	var $catid;

	function getName() { return 'Collapse/Expand contents block'; }
	function getAuthor()  { return 'Andy'; }
	function getURL() { return 'http://matsubarafamily.com/blog/'; }
	function getVersion() { return '1.21'; }
	function getDescription() {
		return 'This plugin makes part of your contents collapse/expand. use <%collapse(sub_text)%> <%/collapse(sub_text2)%>';
	}
	function supportsFeature($what) {
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function getEventList() { return array('PreItem'); }	
	
	function doSkinVar($skintype, $inout='',$comment='',$number=1, $number2=0) {
		if ($number2) {
			$id = 'skin_' . $number2;
		} else {
			$id = 'skin_' . $number;
		}
		switch ($inout) {
		case '' :
echo <<<JSEND
<script language="javascript">
<!--
	function np_cc_showMore(id, showexpand){
		varexpand = ('np_cc_expand' + id);
		varcollapse = ('np_cc_collapse' + id);
		if( showexpand != 0 ) {
			document.getElementById(varexpand).style.display = "block";
			document.getElementById(varcollapse).style.display = "none";
		} else {
			document.getElementById(varexpand).style.display = "none";
			document.getElementById(varcollapse).style.display = "block";
		}
	}

//-->
</script>
JSEND;
			break;
		case 'in' :
echo <<<SKININ
<div id="np_cc_collapse$id" class="np_cc_switch_skin">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 1);return false;">$comment</a><br /></div>
<div id="np_cc_expand$id" style="display: none">
<div class="np_cc_skin">
SKININ;
		break;
		case 'out' :
		echo '</div>';
		if ($comment) {
echo <<<SKINOUT
<div class="np_cc_switch_skin">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 0);return false;">$comment</a>
</div>
SKINOUT;
		}
		echo '</div>';
		break;
		case 'togglein' :
echo <<<SKINTOGGLEIN
<div id="np_cc_collapse$id" class="np_cc_switch_skin">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 1);return false;">$comment</a><br /></div>
<div id="np_cc_expand$id" style="display: none">
<div class="np_cc_skin">
<div class="np_cc_switch_skin">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 0);return false;">$number</a>
</div>
SKINTOGGLEIN;
		break;
		}
	}
	
	function doTemplateVar(&$item, $inout, $comment,$number=1, $number2=0) {
		if ($number2) {
			$id = $item->itemid . '__' . $number2;
		} else {
			$id = $item->itemid . '__' . $number;
		}
		switch (strtolower($inout)) {
			case 'in' :
echo <<<IN
<div id="np_cc_collapse$id" class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 1);return false;">$comment</a><br /></div>
<div id="np_cc_expand$id" style="display: none">
<div class="np_cc_contents">
IN;
			break;
			case 'out' :
			echo '</div>';
			if ($comment) {
echo <<<OUT
<div class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 0);return false;">$comment</a>
</div>
OUT;
			}
			echo '</div>';
			case 'togglein' :
echo <<<TOGGLEIN
<div id="np_cc_collapse$id" class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 1);return false;">$comment</a><br /></div>
<div id="np_cc_expand$id" style="display: none">
<div class="np_cc_contents">
<div class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 0);return false;">$number</a>
</div>
TOGGLEIN;
			break;
		}
	}


    function replaceCallback($matches) {
		$id = $this->currentItem->itemid . '_' . $this->increment++;
		$expand = $matches[1];
		$collapse = $matches[3];
		if (preg_match('/(.*)\|(.*)/', $expand, $newmatch)) {
			$expand = $newmatch[1];
			$collapse2 = $newmatch[2];
		} else {
			$collapse2 = '';
		}
		$contents = $matches[2];
		if ($collapse2 && $collapse) {
$s = <<<TOGGLE
<div id="np_cc_collapse$id" class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 1);return false;">$expand</a><br /></div>
<div id="np_cc_expand$id" style="display: none">
<div class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 0);return false;">$collapse2</a>
</div>
<div class="np_cc_contents">
$contents
</div>
<div class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 0);return false;">$collapse</a>
</div>
</div>
TOGGLE;
		} elseif (!$collapse2) {
$s = <<<ALL
<div id="np_cc_collapse$id" class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 1);return false;">$expand</a><br /></div>
<div id="np_cc_expand$id" style="display: none">
<div class="np_cc_contents">
$contents
</div>
<div class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 0);return false;">$collapse</a>
</div>
</div>
ALL;
		} else {
$s = <<<TOGGLEONLY
<div id="np_cc_collapse$id" class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 1);return false;">$expand</a><br /></div>
<div id="np_cc_expand$id" style="display: none">
<div class="np_cc_switch">
<a href="javascript:void(0)" onclick="np_cc_showMore('$id', 0);return false;">$collapse2</a>
</div>
<div class="np_cc_contents">
$contents
</div>
</div>
TOGGLEONLY;
		}
        return $s; 
    }


	function event_PreItem(&$data) { 
		$this->currentItem = &$data["item"]; 
		$this->increment = 1;
		$this->currentItem->body = preg_replace_callback( 
				'#<%collapse\((.*?)\)%>(.*?)<%/collapse\((.*?)\)%>#s', 
				array(&$this, 'replaceCallback'), 
				$this->currentItem->body 
			); 
		$this->currentItem->more = preg_replace_callback( 
				'#<%collapse\((.*?)\)%>(.*?)<%/collapse\((.*?)\)%>#s',
				array(&$this, 'replaceCallback'), 
				$this->currentItem->more 
			); 
	} 

}

?>