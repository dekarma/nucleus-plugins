<?php 

//to only be included by np_gallery script or update scripts

if(isset($template)) unset($template);
$template = new NPG_TEMPLATE(NPG_TEMPLATE::createnew('default080','default 0.80 templates'));

$name = 'LIST_HEADER';
$content = '<%breadcrumb%><hr/><table width=100% ><thead>'
			.'<tr><th><a href="<%sortbytitle%>">Title</a></th>'
			.'<th><a href="<%sortbydescription%>">Description</a></th>'
			.'<th><a href="<%sortbyowner%>">Owner</a></th>'
			.'<th><a href="<%sortbymodified%>">Last Modified</a></th>'
			.'<th><a href="<%sortbynumber%>">Images</a></th></tr></thead><tbody>';
$template->setTemplate($name, $content);

$name = 'LIST_BODY';
$content = '<tr><td><a href="<%albumlink%>"><%title%></a></td><td><%description%></td><td><%ownername%></td><td><%modified%></td><td><%numberofimages%></td></tr>';
$template->setTemplate($name, $content);

$name = 'LIST_FOOTER';
$content = '</tbody></table><hr/><br /><%if(canaddalbum)%><a href="<%addalbumlink%>">Add New Album | </a><%endif%><%if(canaddpicture)%><a href="<%addpictureslink%>"onclick="window.open(this.href,\'addpicture\',\'status=no,toolbar=no,scrollbars=no,resizable=yes,width=600,height=400\');return false;"> Add Pictures</a><%endif%>';
$template->setTemplate($name, $content);


//
$name = 'ALBUM_HEADER';
$content = '<%breadcrumb%><hr/>Page: <%pages%><hr/><div id="NPG_thumbnail">';
$template->setTemplate($name, $content);

$name = 'ALBUM_BODY';
$content = '<div class="thumbnailoutside"><div class=\'alpha-shadow\'><div><a href="<%picturelink%>"><img src="<%thumbnail%>" /></a></div></div><br></div>';
$template->setTemplate($name, $content);

$name = 'ALBUM_FOOTER';
$content = '</div><div id="NPG_footer"><br /><hr/><%if(caneditalbum)%><a href="<%editalbumlink%>">Modify Album </a> | <%endif%><%if(canaddpicture)%><a href="<%addpicturelink%>"onclick="window.open(this.href,\'imagepopup\',\'status=no,toolbar=no,scrollbars=no,resizable=yes,width=480,height=360\');return false;">Add Picture</a><%endif%></div>';
$template->setTemplate($name, $content);

//
$name = 'ITEM_HEADER';
$content = '<%breadcrumb%><br/><%if(prev)%><a href="<%previouslink%>"> Previous</a><%else%> Previous<%endif%> |<%if(next)%><a href="<%nextlink%>"> Next</a><%else%> Next<%endif%><hr/><div id="NPG_picture">';
$template->setTemplate($name, $content);

$name = 'ITEM_BODY';
$content = '<a href="<%fullsizelink%>" onclick="window.open(this.href,\'imagepopup\',\'status=no,toolbar=no,scrollbars=auto,resizable=yes,width=<%width%>,height=<%height%>\');return false;"><img src="<%intermediatepicture%>" /></a>';
$template->setTemplate($name, $content);

$name = 'ITEM_FOOTER';
$content = '</div><div id="NPG_footer"><br /><%description%><br/><br/>Last modified by <%owner%> on <%date%> <%if(caneditpicture)%><a href="<%editpicturelink%>">Edit</a> | <a href="<%deletepicturelink%>">Delete</a><%endif%><br/></div><%if(commentsallowed)%><div class="contenttitle"><h2>Comments</h2></div><%comments%><div class="contenttitle"><h2>Add Comment</h2></div><%commentform%><%endif%>';	
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