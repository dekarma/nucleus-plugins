<?php

/*************************************************************************
   NP_PopupImageGD2 - Nucleus plugin for automatically generating popups
 using the GD Image Library instead of ImageMagick. Based on Till
 Gerken's NP_PopupImage plugin, Roberto Bolli's NetPBM hack of same,
 and Christian Heilmann's thumbnail generator from "How to create
 thumbnails with PHP and gd" found at http://www.onlinetools.org/

 Requires GD v2 or better. Configuration from the Nucleus plugin page.
 Thumbnails will be stored in the blog's media directory and will
 be regenerated if they don't exist, so you can force them to be
 updated by simply deleting all thumbnails.

 If you have an older version of GD (like one which still creates GIFs),
 you'll need to change "ImageCreateTrueColor" to "ImageCreate" and
 "ImageCopyResampled" to "ImageCopyResized".

 Based on the script by Eoin Dubsky <eoin@free.fr>
 Modified by Shane Saunders <shane@gnative.com>
 Modified by Jean-Christophe Berthon <http://np-polyglot.sourceforge.net/>,
   added patch so it is working also for the item's extended section
   (patch by calebsg: http://forum.nucleuscms.org/viewtopic.php?p=40168#40168)
   and extend this plug-in so it replaces any <%popup()%> regardless
   of the presence of a comment or not. Use the comment as a thumbnail
   caption.
 Modified by Kai Greve <http://kgblog.de>, changes for v0.30:
   - larger default size
   - support for fancy urls
   - option: show caption (yes/no)
   - parameter for thumbnail alignment added
   v0.40:
   - option: Apply popup behaviour also to <%image()%> tags (yes/no)

 Based on:
   - NP_PopupImageNetPBM Copyright (c) 2003 by Roberto Bolli <http://www.rbnet.it/>
     see http://www.nucleuscms.org/ for further information.
   - NP_PopupImage Copyright (c) 2003 by Till Gerken <till@tantalo.net>
     see http://www.tillgerken.de/ for further information.

*************************************************************************/


class NP_PopupImageGD2 extends NucleusPlugin
{

        var $maxThumbX;         // Max user thumbnails width
        var $maxThumbY;         // Max user thumbnails height
        var $currentItem;       //
        var $stretch;           // Stretch image yes/no
        var $thumbPrefix;       // Thumbnails prefix
        var $thumbsPrefixDefault = 'thumb_';      // Default thumbnails prefix, to avoid insertion of empty prefixes

        /* Name of plugin
        ------------------*/
        function getName()
        {
                return 'PopupImageGD2';
        }

        /* Author of Plugin
        -------------------*/
        function getAuthor()
        {
                return 'Eoin Dubsky | Shane Saunders | Jean-Christophe Berthon | Kai Greve';
        }

        /* URL/Mail to the author/plugin website
        ----------------------------------------*/
        function getURL()
        {
                return 'http://wakka.xiffy.nl/popupimagegd';
        }

        /* Version of the plugin
        ------------------------*/
        function getVersion()
        {
                return '0.40';
        }

        /* Minimum Nucleus version required to install the plugin
        ---------------------------------------------------------*/
        function getMinNucleusVersion()
        {
                return '220';
        }

        /* A description to be shown on the installed plugins listing
        -------------------------------------------------------------*/
        function getDescription()
        {
                $s = 'Replaces the standard image popup plug-in. ';
                $s .= 'Automatically creates thumbnails with clickable popup links for the full image. This version of the plugin supports JPG and PNG images type. ';
                $s .= 'This plugin works with the GD graphics library which is commonly installed on shared host accounts. ';
               
                return $s;
        }

        /* Get subscribed events
        ------------------------*/
        function getEventList()
        {
                return array('PreItem');
        }
 
        /* Nnote support for SqlTablePrefix since no queries
        ----------------------------------------------------*/
        function supportsFeature($what) {
                switch($what) {
                        case 'SqlTablePrefix':
                                return 1;
                        default:
                                return 0;
                }
        }

        /* Creates plugin options
        -------------------------*/
        function install()
        {
                $this->createOption('maxThumbX', 'Maximum thumbnails width in pixel', 'text', '160');
                $this->createOption('maxThumbY', 'Maximum thumbnails height in pixel', 'text', '160');
                $this->createOption('maintainAr', 'Maintain aspect ratio', 'yesno', 'yes');
                $this->createOption('thumbsPrefix', 'Prefix for thumbnails (if blank it will be set to \'thumb_\')', 'text', $this->thumbsPrefixDefault);
                $this->createOption('showCaption', 'Show caption', 'yesno', 'yes');
                $this->createOption('leftAlign', 'Style for left alignment', text, 'float: left; margin: 0 1em 1em 0;');
                $this->createOption('centerAlign', 'Style for center alignment', text, 'width: 100%; text-align: center;');
                $this->createOption('rightAlign', 'Style for right alignment', text, 'float: right; margin: 0 0 1em 1em;');
                $this->createOption('imageTag', 'Apply popup behaviour also to <%image()%> tags', 'yesno', 'no');
        }

        /* Initializes the plugin
        -------------------------*/
        function init()
        {
                $this->maxThumbX    = $this->getOption('maxThumbX');
                $this->maxThumbY    = $this->getOption('maxThumbY');
                $this->stretch      = $this->getOption('maintainAr');

                // Check user thumbnails prefix
                $this->thumbsPrefix = trim($this->getOption('thumbsPrefix'));
                if ( $this->thumbsPrefix == '' )
                {
                        $this->setOption('thumbsPrefix', $this->thumbsPrefixDefault);
                        $this->thumbsPrefix = $this->thumbsPrefixDefault;
                }
        }

        /* Main function
        ----------------*/
        function replaceCallback($matches)
        {
                global $DIR_MEDIA, $CONF;

                $originalFile = $matches[1];
                $xSize = $matches[2];
                $ySize = $matches[3];
                list ($comment, $alignment) = explode('|', $matches[4]);

                // Calculate the thumbnail width/height
                $thumbnail_dims = $this->thumb_dimensions($xSize, $ySize);
                $th_width = $thumbnail_dims[0];
                $th_height = $thumbnail_dims[1];

                // select private collection when no collection given
                if ( !strstr($originalFile,'/') )
                {
                        // Get Author id
                        $authorID = $this->currentItem->authorid;
                        // Path to public thumbnail
                        $thumbnail = $authorID . '/' . $this->thumbsPrefix . $originalFile;
                        // Path to public original file
                        $srcPath = $authorID . '/' . $originalFile;
                }
                else
                {
                        $parts = explode("/",$originalFile);
                        $publicColl = $parts[0];
                        $originalFile = $parts[1];

                        // Path to private thumbnail
                        $thumbnail = $publicColl . '/' . $this->thumbsPrefix . $originalFile;
                        // Path to private original file
                        $srcPath = $publicColl . '/' . $originalFile;
                }

                // Only generate a thumbnail if there isn't already one in the destination directory
                if ( !is_readable($DIR_MEDIA . $thumbnail) )
                {

                        // Determine the GD command to use given the image type and create thumbnail
                        if ( eregi("\.(jpg|jpeg)", $originalFile) )
                        {
                                $src_img=imagecreatefromjpeg($DIR_MEDIA.$srcPath);
                                $dst_img=ImageCreateTrueColor($th_width,$th_height);
                                imagecopyresampled($dst_img,$src_img,0,0,0,0,$th_width,$th_height,$xSize,$ySize);
                                $fp=fopen($DIR_MEDIA . $thumbnail,"w");fclose($fp);
                                imagejpeg($dst_img,$DIR_MEDIA . $thumbnail);
                                imagedestroy($dst_img);
                                imagedestroy($src_img);
                        }
                        if ( eregi("\.(png)", $originalFile) )
                        {
                                $src_img=imagecreatefrompng($DIR_MEDIA.$srcPath);
                                $dst_img=ImageCreateTrueColor($th_width,$th_height);
                                imagecopyresampled($dst_img,$src_img,0,0,0,0,$th_width,$th_height,$xSize,$ySize);
                                imagepng($dst_img,$DIR_MEDIA . $thumbnail);
                                imagedestroy($dst_img);
                                imagedestroy($src_img);
                        }
                        
                }
                
                // thumbnail alignment
                switch ($alignment) {
                  case 'left':
                    $open_div='<div style="'.$this->getOption('leftAlign').'">';
                    $close_div='</div>';
                    break;
                  case 'center':
                    $open_div='<div style="'.$this->getOption('centerAlign').'">';
                    $close_div='</div>';
                    break;
                  case 'right':
                    $open_div='<div style="'.$this->getOption('rightAlign').'">';
                    $close_div='</div>';
                    break;
                  default:
                    $open_div='';
                    $close_div='';
                }
                
                // support fancy urls
                if ($CONF['URLMode']=="normal"){
                  $baseurl="index.php";
                }
                else {
                  $baseurl=$CONF['IndexURL'];
                }
                
                // code for caption
                if ($this->getOption('showCaption')=="yes") {
                  $caption= '<br />'.$comment;
                }
                else {
                  $caption= '';
                }
                
                // return popup code
                return ($open_div.'<a href="'.$baseurl.'?imagepopup=' . $srcPath . '&amp;width=' . $xSize. '&amp;height=' . $ySize . '&amp;" onclick="window.open(this.href,\'imagepopup\',\'status=no,toolbar=no,scrollbars=no,resizable=yes,width=' . $xSize . ',height=' . $ySize . '\');return false;"><img src="'. $CONF['MediaURL'] . $thumbnail . '" width="' . $th_width . '" height="' . $th_height . '" alt="' . $comment . '" title="' . $comment . '" />' . $caption . '</a>'.$close_div);
        }

        /* Calculate thumbnail size
        Original code: Bruno VIBERT < bvibert@mytracer.com > 
        -------------------------------------------------------*/ 
        function thumb_dimensions($width, $height)
        {
                $maxWidth  = $this->maxThumbX;
                $maxHeight = $this->maxThumbY;

                if ( $this->stretch == 'yes')
                {
                        if ( $width > $maxWidth & $height <= $maxHeight )
                        {
                                $ratio = $maxWidth / $width;
                        }
                        elseif ( $height > $maxHeight & $width <= $maxWidth )
                        {
                                $ratio = $maxHeight / $height;
                        }
                        elseif ( $width > $maxWidth & $height > $maxHeight )
                        {
                                $ratio1 = $maxWidth / $width;
                                $ratio2 = $maxHeight / $height;
                                $ratio = ($ratio1 < $ratio2)? $ratio1:$ratio2;
                        }
                        else
                        {
                                $ratio = 1;
                        }
                        $thumbnail_dims[0] = floor($width*$ratio);
                        $thumbnail_dims[1] = floor($height*$ratio);
                }
                else
                {
                        $thumbnail_dims[0] = $maxWidth;
                        $thumbnail_dims[1] = $maxHeight;
                }
                return ( $thumbnail_dims );
        }

        /* Recall preitem event
        -----------------------*/
        function event_PreItem($data)
        {
            $this->currentItem = &$data["item"];
            /* If there is a popup tag, then replace it with a thumbnail */
            $this->currentItem->body = preg_replace_callback("#<\%popup\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'replaceCallback'), $this->currentItem->body);
            $this->currentItem->more = preg_replace_callback("#<\%popup\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'replaceCallback'), $this->currentItem->more);
            /* If there is a image tag, then replace it with a thumbnail */
            if ($this->getOption('imageTag')=="yes") {
              $this->currentItem->body = preg_replace_callback("#<\%image\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'replaceCallback'), $this->currentItem->body);
              $this->currentItem->more = preg_replace_callback("#<\%image\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'replaceCallback'), $this->currentItem->more);
            }
        }
}
?>
