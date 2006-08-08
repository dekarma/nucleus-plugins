<?php

include('./../../../../config.php');
global $DIR_PLUGINS;
include_once ($DIR_PLUGINS.'gallery/config.php');

//from 0.77 to 0.80


global $NPG_CONF;

//add .80 templates -- sircambridge mods
include($DIR_PLUGINS.'gallery/update/default_templates_080.inc');
?>
<p>The templates included with 0.8 use a different css technique to display the album thumbnails. To use these templates, you will need to add the three graphics files (shadow.gif, shadow2.png, shadow2.gif) to your skin directory. Then add the following lines to your css file. You will need to modify the location of the image files (bolded) to match your installation.</p>
<hr />
<p>
.thumbnailoutside {<br>
   width:155px !important;<br>
        /*edit this height to give the description more room*/<br>
   height:160px !important;<br>
   float:left !important;<br>
   text-align:center !important;<br>
}
</p>

<p>
.alpha-shadow {<br>
   float:left;<br>
   background: <b>url(/nucleus/skins/default/images/shadow.gif)</b> no-repeat bottom right;<br>
   margin: 0px 0 0 10px !important;<br>
   margin: 10px 0 0 10px;<br>
   }
</p>

<p>
.alpha-shadow div {<br>
  background: <b>url(/nucleus/skins/default/images/shadow2.png)</b> no-repeat left top !important;<br>
  background: <b>url(/nucleus/skins/default/images/shadow2.gif)</b> no-repeat left top;<br>
  float: left;<br>
  margin-top: 0px;<br>
  padding: 0px 6px 6px 0px;<br>
  }
</p>

<p>
.alpha-shadow img {<br>
  background-color: #fff;<br>
  border: 1px solid #a9a9a9;<br>
  padding: 4px !important;<br>
  }
</p>
<hr />
<?php

//remove duplicates from views and add primary key to views
sql_query('create temporary table dupfix SELECT vpictureid, MAX( views ) AS views FROM '.sql_table('plug_gallery_views').' GROUP BY vpictureid');
sql_query('delete from '.sql_table('plug_gallery_views'));
sql_query('alter table '.sql_table('plug_gallery_views').' add primary key(vpictureid)');
sql_query('insert into '.sql_table('plug_gallery_views').' (vpictureid, views) select vpictureid, views from dupfix');

setNPGoption('currentversion',80);

echo 'NP_Gallery database updated to 0.8<br/>';

?>
