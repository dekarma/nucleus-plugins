<?php 
global $CONF;
//to only be included by np_gallery script or update scripts
if(isset($template)) unset($template);
$template = new NPG_TEMPLATE(NPG_TEMPLATE::createnew('default093','default 0.93 templates'));

$name = 'LIST_HEADER';
$content = '<%breadcrumb%><hr/><table width=100% ><thead>'
			.'<tr><th><a href="<%sortbytitle%>">Title</a></th>'
			.'<th><a href="<%sortbydescription%>">Description</a></th>'
			.'<th><a href="<%sortbyowner%>">Owner</a></th>'
			.'<th><a href="<%sortbymodified%>">Last Modified</a></th>'
			.'<th><a href="<%sortbynumber%>">Images</a></th></tr></thead><tbody>'
			.'<form method="post" action="action.php">'
			.'<input type="hidden" name="action" value="plugin" />'
			.'<input type="hidden" name="name" value="gallery" />'
			.'<input type="hidden" name="type" value="list" />'
			.'<input type="submit" value="Resort by:" /> <select name="sort" >'
			.'<option value="title" >Album Title'
			.'<option value="desc" >Album Description'
			.'<option value="owner">Owner'
			.'<option value="date">Date Modified'
			.'<option value="numb">Number of Images'
			.'</select></form>';
$template->setTemplate($name, $content);

$name = 'LIST_BODY';
$content = '<tr><td><a href="<%albumlink%>"><%title%></a></td><td><%description%></td><td><%ownername%></td><td><%modified%></td><td><%numberofimages%></td></tr>';
$template->setTemplate($name, $content);

$name = 'LIST_FOOTER';
$content = '</tbody></table><hr/><br /><%if(canaddalbum)%><a href="<%addalbumlink%>">Add New Album | </a><%endif%><%if(canaddpicture)%><a href="<%addpictureslink%>"onclick="window.open(this.href,\'addpicture\',\'status=no,toolbar=no,scrollbars=yes,resizable=yes,width=600,height=400\');return false;"> Add Pictures</a><%endif%>';
$template->setTemplate($name, $content);


//
$name = 'ALBUM_HEADER';
$content = '<%breadcrumb%><hr/>Page: <%pages%><hr/><div id="NPG_thumbnail">'
			.'<form method="post" action="action.php">'
			.'<input type="hidden" name="action" value="plugin" />'
			.'<input type="hidden" name="name" value="gallery" />'
			.'<input type="hidden" name="type" value="album" />'
			.'<input type="hidden" name="id" value="<%albumid%>" />'
			.'<input type="submit" value="Resort by:" /> <select name="sort" >'
			.'<option value="title" >Picture Title'
			.'<option value="desc" >Picture Description'
			.'<option value="date">Date Modified'
			.'<option value="titlea" >Picture Title - Ascending'
			.'<option value="desca" >Picture Description - Ascending'
			.'<option value="datea">Picture - Ascending'
			.'</select></form>';
$template->setTemplate($name, $content);

$name = 'ALBUM_BODY';
$content = '<div class="thumbnailoutside"><div class=\'alpha-shadow\'><div><a href="<%picturelink%>"><img src="<%thumbnail%>" /></a></div></div><br></div>';
$template->setTemplate($name, $content);

$name = 'ALBUM_SETDISPLAY';
$content = '<div class="thumbnailoutside"><div class=\'alpha-shadow\'><div><a href="<%picturelink%>"><img src="<%thumbnail%>" /></a></div></div><br></div>';
$template->setTemplate($name, $content);

$name = 'ALBUM_FOOTER';
$content = '</div><div id="NPG_footer"><br /><hr/><%if(caneditalbum)%><a href="<%editalbumlink%>">Modify Album </a> | <%endif%><%if(canaddpicture)%><a href="<%addpicturelink%>"onclick="window.open(this.href,\'imagepopup\',\'status=no,toolbar=no,scrollbars=yes,resizable=yes,width=480,height=360\');return false;">Add Picture</a><%endif%></div>';
$template->setTemplate($name, $content);

//
$name = 'ITEM_TOOLTIPSHEADER';
$content = '<%if(tooltips)%></script><script type="text/javascript">var pictureid = "<%pictureid%>";</script>'
	.'<script type="text/javascript" src="'.$CONF['IndexURL'].'/nucleus/plugins/gallery/NP_gallery.js"></script><%endif%>';
$template->setTemplate($name, $content);

$name = 'ITEM_HEADER';
$content = '<style type="text/css">@import "'.$CONF['IndexURL'].'/nucleus/plugins/gallery/NP_gallery.css";</style>'
	.'<%breadcrumb%><br/><%if(prev)%><a href="<%previouslink%>">'
	.' Previous</a><%else%> Previous<%endif%> |<%if(next)%><a href="<%nextlink%>"> '
	.' Next</a><%else%> Next<%endif%><hr/><div id="NPG_picture">';
$template->setTemplate($name, $content);

$name = 'ITEM_SLIDESHOWT';
$content = '<script language="JavaScript">' .
		'var NextURL = "<%nextlink%>&sliderunning=true";' .
		'var interval = (1000);' .
		'setTimeout("location.href=NextURL",3000);' .
		'</script><div class="alpha-shadow"><div><img src="<%intermediatepicture%>"</div></div>';
$template->setTemplate($name, $content);

$name = 'ITEM_SLIDESHOWC';
$content = '<%if(slideshowson)%>' .
		'<table><tr><td>' .
		'<form method="post" action="'.$CONF['IndexURL'].'action.php?action=plugin&name=gallery&type=item&id=<%nextid%>&startstop=true">' .
		'<input type="submit" value="Start Slideshow"/></form></td>' .
		'<td><form method="post" action="'.$CONF['IndexURL'].'action.php?action=plugin&name=gallery&type=item&id=<%nextid%>">' .
		'<input type="submit" value="Stop Slideshow"/></form></td></tr></table>'.
		//'interval(secs)' .
		//'<select name=intvalsecs>' .
		//'<OPTION value="1">1 sec</OPTION>' .
		//'<OPTION value="2">2 secs</OPTION>' .
		//'<OPTION value="3">3 secs</OPTION>' .
		//'</select>' .
		//'</form>' .
		'<%endif%>';
$template->setTemplate($name, $content);

$name = 'ITEM_BODY';
$content = '<div class=\'alpha-shadow\' <%if(tooltips)%>onmouseenter="showtipdivs();" onmouseleave="hidetipdivs();" onmouseover="showtipdivs();" onmouseout="hidetipdivs();"<%endif%>><div><%if(tooltips)%><%tooltip%><%endif%><img src="<%intermediatepicture%>" <%if(tooltips)%>onMouseOver="setLyr(this,\'testlayer\');"<%endif%> /></div></div>';
$template->setTemplate($name, $content);

$name = 'ITEM_TOOLTIPSFOOTER';
$content = '<%if(caneditpicture)%>'
	.'<%if(tooltips)%><form name="clear" method="POST" action="'
	.$CONF['IndexURL']
	.'/action.php?action=plugin&name=gallery&type=tagdelete">'
	.'<input type="hidden" name="pictureid" type="text" value ="<%pictureid%>">'
	.'<input type="submit" name="Submit" value="Delete one tag">'
	.'</form><a href="javascript:void(null)" onclick="start()">Create new caption. </a><%endif%><%endif%>'
	.'</div>';
$template->setTemplate($name, $content);

$name = 'ITEM_NEXTPREVTHUMBS';
$content = '<%if(nextprevthumb)%>' .
		'<div style="clear:both;margin:auto">' .
		'<%if(prev)%>' .
		'<div class=\'thumbnailoutside\'><div class="alpha-shadow" ><div>' .
		'a href="<%previouslink%>"><img src="<%previousthumbfilename%>" /></a></div></div></div>' .
		'<%endif%>' .
		'<div class=\'thumbnailoutside\' style="text-align:"></br></br>' .
		'< <%if(prev)%>Previous <%endif%>| <%if(next)%>Next <%endif%>>' .
		'</div> ' .
		'<%if(next)%><div class=\'thumbnailoutside\'><div class="alpha-shadow" ><div>' .
		'<a href="<%nextlink%>"><img src="<%nextthumbfilename%>" /></a></div></div></div>' .
		'<%endif%>' .
		'</div>' .
		'<%endif%>' .
		'<div id="NPG_footer"><br />Description: <%description%>|<br />';
$template->setTemplate($name, $content);

$name = 'ITEM_FOOTER';
$content = '<a href="<%fullsizelink%>">Fullsize Image Link</a>|'
	.'|Last modified by <%owner%> on '
	.'<%date%> <%if(caneditpicture)%>|<a href="<%editpicturelink%>">Edit</a> | '
	.'<a href="<%deletepicturelink%>">Delete</a>This picture in sets:<%keywords%> <%endif%><br/></div><%if(commentsallowed)%>'
	.'<div class="contenttitle"><h2>Comments</h2></div><%comments%><div class="contenttitle">'
	.'<h2>Add Comment</h2></div><%commentform%><%endif%>';
$template->setTemplate($name, $content);

$name = 'COMMENT_BODY';
$content = '<div class="itemcomment id<%memberid%>"><h3><a href="<%userlinkraw%>"title="<%ip%> | Click to visit <%user%>\'s website or send an email"><%user%></a> wrote:</h3><div class="commentbody"><%body%></div><div class="commentinfo"><%date%> <%time%></div></div>'	;
$template->setTemplate($name, $content);

$name = 'PROMO_TITLE';
$content = 'New Pictures Added!';
$template->setTemplate($name,$content);

$name = 'PROMO_BODY';
$content = 'New pictures posted:<div id="NPG_thumbnail"><ul class="thumbnail"><%images%></ul></div><div id="NPG_footer"></div>';
$template->setTemplate($name,$content);

$name = 'PROMO_IMAGES';
$content = '<li><a href="<%picturelink%>"><img style="<%centeredtopmargin(140,0)%>" src="<%thumbnail%>" /></a></li>';
$template->setTemplate($name,$content);

?>