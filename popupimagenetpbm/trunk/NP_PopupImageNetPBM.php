<?php
/*************************************************************************
  NP_PopupImageNetPBM - Nucleus plugin for automatically generating popups

  This plugin requires the 3 step indicated below to fully works:

    ## STEP 1: install NetPBM Library
    ---------------------------------
     <http://netpbm.sourceforge.net/>. Many systems will already have this
     installed. If not, you can get a binary distribution for your system
     from the Bharat Mediratta Gallery Download page:

     http://gallery.menalto.com/modules.php?op=modload&name=phpWiki&file=index&pagename=Download

     **YOU DON'T NEED TO BE ROOT TO INSTALL NETPBM LIBRARY ON YOUR SYSTEM!**
     Just unzip archive and put the files in your home directory or in a subdir if you
     prefer, then remeber to insert the absolute path of your installation in
     the plugin options.

     Why use NetPBM library instead of GD? Read this: http://gallery.menalto.com/modules.php?op=modload&name=GalleryFAQ&file=index&myfaq=yes&id_cat=3&categories=3+-+Gallery+Graphics+Toolkits&parent_id=0#6

    ## STEP 2: install & configure plugin
    -------------------------------------
     Copy NP_PopupImageNetPBM.php file in your plugins directory (typically /nucleus/plugins/);
     from the admin controll panel switch to the plugins section and at the end of the page
     choose to install "NP_PluginNetPBM".

     Once installed switch back to the Nucleus Plugin page and select the option link
     for the plugin:
      - Indicate the path of your NetPBM installation;
      - Adjust the default max width/height size in pixels for the generated thumbnails:
        thumbnails will be stored in the blog's media directory and will be regenerated
        if they don't exist, so you can force them to be updated by simply deleting all thumbnails;
NEW   - You can choice to preserve aspect ratio or stretch the generated plugin to maximum
        width/height (Maintain aspect ratio = yes/no);
      - Insert a prefix for generated thumbnails (default = "thumb_");
      - You can adjust the compression ratio for JPG thumbnails (default = 75);
NEW   - Comment: with last release of the plugin you can choice to add a comment inside generated
        gif/jpg thumbnails: comments will be inserted INSIDE the images code and will
        be shown only in a image editor or similiar software.

    ## STEP 3: change template behaviour (not needed in Image skinvar mode)
    ------------------------------------
     To get the plugin to work correctly, you will need to apply the
     following modifications in both your templates, detailed and default:

     Popup Link Code:
      <a href="<%rawpopuplink%>" onclick="<%popupcode%>">

     Inline Image Code:
       <%image%></a>

    ## Image skinVar mode ##
    ------------------------
     This plugin utilize existing <%image%> and <%popup%> function from Nucleus to construct the image.
     This is somewhat intruding if user still want to use existing popup, and image function.

     The new Image skinVar mode allow user to preserve the existing function while still get the thumbnail/popup
     full image function.

     To insert a image in a post
     1. add the image as inline as before
     2. change the "image" skinvar to Image. The skinvar has to be in the format of:
        <%Image(filename|popupX|popupY|thumbX|thumbY|comment)%>, left the parameter blank if no value is provided. ie
        <%Image(file.jpg|1024|768|||my pic)%> (use default thumbnail size)

    --- OR ---

     Replace this function in nucleus/javascript/edit.js

     function includeImage(collection, filename, type, width, height) {
         if (isCaretEmpty()) {
                 text = prompt("Text to display ?","");
         } else {
                 text = getCaretText();
         }

         // add collection name when not private collection (or editing a message that's not your)
         var fullName;
         if (isNaN(collection) || (nucleusAuthorId != collection)) {
                 fullName = collection + '/' + filename;
         } else {
                 fullName = filename;
         }


         var replaceBy;
         switch(type) {
                 case 'popup':
                         replaceBy = '<%popup(' +  fullName + '|'+width+'|'+height+'|' + text +')%>';
                         break;
                 case 'inline':
                 default:
                         replaceBy = '<%Image(' +  fullName + '|'+width+'|'+height+'|||' + text +')%>'; // --- ED$ --- This is the line that do the trick....
         }

         insertAtCaret(replaceBy);
         updAllPreviews();

      }

      just insert the image and fill in the thumbnail size

    ## UPGRADE FROM A PREVOUS VERSION
    ---------------------------------
     Be sure to uninstall the old copy of the plugin before reinstalling
     the newer version!

**************************************************************************
  Release history:

	  - 0.13: added title in <IMG>
	  - 0.12: a much better flush thumbnail implementation
	  - 0.11: fixed space in image file name problem
	  - 0.10b: fixed Image mode $comment bug
	  - 0.10a: Seems like this plugin does not work in 2.5beta since the
	           admin menu calls PLUGINADMIN.php, which is in 3.0rc...
	  - 0.10: fixed XHTML
          - 0.9: fixed Image mode thumbnail size control
                 added info how to auto insert Image tag
          - 0.8: added popup window maximum size (in Image skinvar mode)
                 thumbnail cleanup function
          - 0.7: added supportsFeature
                 <%Image()%> skinVar mode
                 fixed exteended body processing
          - 0.6a: major bug fix: now the plugin works with public and private collection;
          - 0.6: added the comment option to add a comment inside jpg/gif thumbnails
                 added some error check;
          - 0.5: changed the routine to calculate AR, now it works better... ermh... works ;^)
                 added the option to stretch the thumbs to max x/y size;
                 some minor changes;
          - 0.4: bug fix for the first public release ;
          - 0.3: added "-quality" option form JPG/JPEG
                 added "ppmquant" command for GIF/PNG image format;
          - 0.2: added support for PNG and GIF images format;
          - 0.1: initial release: works only with JPG/JPEG image format;

  admun TODO:
    - automatically replace image with Image in add post/bookmarklet without hacking edit.js, try
      PreAddItem/PreUpdateItem event
    - optimize code (clone code elimination)
    - add thumbnail directory
**************************************************************************
  Released under the terms of the GPL.

  Copyright (c) 2003-2004 by 
  Roberto Bolli (rbnet) <http://www.rbnet.it/>
  &
  Edmond Hui (admun) <http://edmondhui.homeip.net/blog/>

  see http://www.nucleuscms.org/ for further information.

  Based on:
    - NP_Popup_Image Copyright (c) 2003 by Till Gerken <till@tantalo.net>
      see http://www.tillgerken.de/ for further information.

*************************************************************************/

class NP_PopupImageNetPBM extends NucleusPlugin
{

        var $maxThumbX;         // Max user thumbnails width
        var $maxThumbY;         // Max user thumbnails height
        var $currentItem;       //
        var $netpbmPath;        // Path to NetPBM library
        var $imgComment;        // User image comment
        var $stretch;           // Stretch image yes/no
        var $jpgQuality;        // JPEG compression
        var $thumbPrefix;       // Thumbnails prefix
        var $sBar;              //
        var $maxWinX;           // Max width popup window
        var $maxWinY;           // Max height popup windows
        var $mediaURL;          // URL to media dir
        var $thumbsPrefixDefault = 'thumb_';      // Default thumbnails prefix, to avoid insertion of empty prefixes

        /* Name of plugin
        ------------------*/
        function getName()
        {
                return 'Popup Image NetPBM';
        }

        /* Author of Plugin
        -------------------*/
        function getAuthor()
        {
                return 'Roberto Bolli | Edmond Hui (admun)';
        }

        /* URL/Mail to the author/plugin website
        ----------------------------------------*/
        function getURL()
        {
                return 'http://www.rbnet.it/';
        }

        /* Version of the plugin
        ------------------------*/
        function getVersion()
        {
                return '0.13';
        }

        /* Minimum Nucleus version required to install the plugin
        ---------------------------------------------------------*/
        function getMinNucleusVersion()
        {
                return '300';
        }

        /* A description to be shown on the installed plugins listing
        -------------------------------------------------------------*/
        function getDescription()
        {
                $s = 'Replaces the standard image plugin. ';
                $s .= 'Automatically creates thumbnails with clickable popup links for the full image. This version of the plugin supports GIF, JPG and PNG images type. ';
                $s .= 'This plugin works with the NetPBM library, a GPL package that convert from one graphics format to another and do simple editing and analysis of images. ';
                $s .= 'For more information on what the NetPBM package does, see http://netpbm.sourceforge.net/doc. ';
                $s .= 'This PlugIn is based on the NP_Popup_Image plugin by Till Gerken http://www.tillgerken.de/.';

                return $s;
        }

        function supportsFeature($what) {
                switch($what) {
                        case 'SqlTablePrefix':
                                return 1;
                        default:
                                return 0;
                }
        }

        /* Get subscribed events
        ------------------------*/
        function getEventList()
        {
                return array('PreItem', 'QuickMenu');
        }

        /* Creates plugin options
        -------------------------*/
        function install()
        {
                $this->createOption('imageMode', 'Enable Image skinVar mode (no interfering with existing image and popup function)', 'yesno', 'no');
                $this->createOption('maxThumbX', 'Maximum thumbnails width in pixel', 'text', '110');
                $this->createOption('maxThumbY', 'Maximum thumbnails height in pixel', 'text', '110');
                $this->createOption('maintainAr', 'Maintain aspect ratio', 'yesno', 'yes');
                $this->createOption('sBar', 'Allow popup window with scrollbar (in Image skinvar mode)?', 'yesno', 'yes');
                $this->createOption('maxWinX', 'Maximum popup window width in pixel', 'text', '800');
                $this->createOption('maxWinY', 'Maximum popup window height in pixel', 'text', '600');
                $this->createOption('jpgQuality', 'Quality of JPG compression (default: 75)', 'text', '75');
                $this->createOption('imgComment', 'Do you want to add a personal comment inside image? (comment wil be included INSIDE the image and will be visible only in a image editor)  ', 'text', 'PopupImageNetPBM by http://www.rbnet.it/');
                $this->createOption('thumbsPrefix', 'Prefix for thumbnails (if blank it will be set to \'thumb_\')', 'text', $this->thumbsPrefixDefault);
                $this->createOption('netpbmPath', 'Path to NetPBM (you *must* provide it and it *must* end with slash)', 'text', '/ABS/PATH/TO/YOUR/NETPBM/INSTALLATION/');
                $this->createOption('mediaURL', 'URL to media dir (you *must* provide it if using Image mode and it *must* end with slash)', 'text', 'http://mysite.com/blog/media/');
        }

        /* Initializes the plugin
        -------------------------*/
        function init()
        {
                $this->maxThumbX    = $this->getOption('maxThumbX');
                $this->maxThumbY    = $this->getOption('maxThumbY');
                $this->stretch      = $this->getOption('maintainAr');
                $this->jpgQuality   = $this->getOption('jpgQuality');
                $this->imgComment   = $this->getOption('imgComment');
                $this->imageMode    = $this->getOption('imageMode');
                $this->mediaURL     = $this->getOption('mediaURL');
                $this->maxWinX      = $this->getOption('maxWinX');
                $this->maxWinY      = $this->getOption('maxWinY');
                $this->sBar         = $this->getOption('sBar');

                // Check user thumbnails prefix
                $this->thumbsPrefix = trim($this->getOption('thumbsPrefix'));
                if ( $this->thumbsPrefix == '' )
                {
                        $this->setOption('thumbsPrefix', $this->thumbsPrefixDefault);
                        $this->thumbsPrefix = $this->thumbsPrefixDefault;
                }
                $this->netpbmPath   = $this->getOption('netpbmPath');
        }

        function event_QuickMenu(&$data) {

                global $member;

                if (!($member->isLoggedIn() && $member->isAdmin())) return;

                array_push(
                                $data['options'],
                                array(  'title' => 'PopupImage NetPBM',
                                        'url' => $this->getAdminURL(),
                                        'tooltip' => 'Admin function for PopupImageNetPBM.'
                                     )
                          );
        }

        function hasAdminArea()
        {
                return 1;
        }



        /* Main function
        ----------------*/
        function replaceCallback($matches)
        {

                global $DIR_MEDIA;

                $originalFile = $matches[1];
                $xSize = $matches[2];
                $ySize = $matches[3];
                $comment = $matches[4];

                // File tpe: only gif, jpg, png are allowed
                if( !(eregi("\.(jpg|jpeg|gif|png)$",$originalFile)) )
                {
                        echo 'file['.$originalFile.'] is an invalid type: only jpg/jpeg, gif and png types are allowed.';
                }

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

                if ( !is_readable($DIR_MEDIA . $thumbnail) )
                {

                        // Determine the NetPBM command to use given the image type and create thumbnail
                        if ( eregi("\.(jpg|jpeg)", $originalFile) )
                        {
                                $tmp_command = $this->netpbmPath . 'jpegtopnm ' . $DIR_MEDIA . "\"$srcPath\"" . ' | ' .  $this->netpbmPath . 'pnmscale -width=' . $th_width . ' -height=' . $th_height . ' | ' .  $this->netpbmPath . 'ppmtojpeg -quality='. $this->jpgQuality . ' -comment="' . $this->imgComment . '" > ' . $DIR_MEDIA . "\"$thumbnail\"";
                        }
                        else if ( eregi("\.gif", $originalFile) )
                        {
                                $tmp_command = $this->netpbmPath . 'giftopnm ' . $DIR_MEDIA . "\"$srcPath\"" . ' | ' .  $this->netpbmPath . 'pnmscale -width=' . $th_width . ' -height=' . $th_height . ' | ' .  $this->netpbmPath . 'ppmquant 256' . ' | ' . $this->netpbmPath . 'ppmtogif -comment="' .  $this->imgComment . '" > ' . $DIR_MEDIA . "\"$thumbnail\"";
                        }
                        elseif ( eregi("\.png", $originalFile) )
                        {
                                $tmp_command = $this->netpbmPath . 'pngtopnm ' . $DIR_MEDIA . "\"$srcPath\"" . ' | ' . $this->netpbmPath .  'pnmscale -width=' . $th_width . ' -height=' . $th_height . ' | ' . $this->netpbmPath . 'ppmquant 256' . ' | ' . $this->netpbmPath . 'pnmtopng > ' . $DIR_MEDIA . "\"$thumbnail\"";
                        }

                        exec ( $tmp_command );

                }

                return ('<%popup(' . $srcPath . '|' . $xSize. '|' . $ySize . '|' . $comment . ')%><%image(' . $thumbnail . '|'. $th_width. '|' . $th_height . '|' . $comment . ')%>');

        }

        function replaceCallbackImageMode($matches)
        {
                global $DIR_MEDIA;

                $originalFile = $matches[1];
                $xSize = $matches[2];
                $ySize = $matches[3];
                $thumbXsize = $matches[4];
                $thumbYsize = $matches[5];
                $comment = $matches[6];

                // File tpe: only gif, jpg, png are allowed
                if( !(eregi("\.(jpg|jpeg|gif|png)$",$originalFile)) )
                {
                        echo 'file['.$originalFile.'] is an invalid type: only jpg/jpeg, gif and png types are allowed.';
                }

                // Calculate the thumbnail width/height
                if ($thumbXsize != 0 || $thumbYsize != 0) {
                        $th_width = $thumbXsize;
                        $th_height = $thumbYsize;
                } else {
                        $thumbnail_dims = $this->thumb_dimensions($xSize, $ySize);
                        $th_width = $thumbnail_dims[0];
                        $th_height = $thumbnail_dims[1];
                }

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

                if ( !is_readable($DIR_MEDIA . $thumbnail) )
                {
                        // Determine the NetPBM command to use given the image type and create thumbnail
                        if ( eregi("\.(jpg|jpeg)", $originalFile) )
                        {
                                $tmp_command = $this->netpbmPath . 'jpegtopnm ' . $DIR_MEDIA . "\"$srcPath\"" . ' | ' . $this->netpbmPath .  'pnmscale -width=' . $th_width . ' -height=' . $th_height . ' | ' . $this->netpbmPath . 'ppmtojpeg -quality='. $this->jpgQuality . ' -comment="' . $this->imgComment . '" > ' . $DIR_MEDIA . "\"$thumbnail\"";
                        }
                        else if ( eregi("\.gif", $originalFile) )
                        {
                                $tmp_command = $this->netpbmPath . 'giftopnm ' . $DIR_MEDIA . "\"$srcPath\"" . ' | ' .  $this->netpbmPath . 'pnmscale -width=' . $th_width . ' -height=' . $th_height . ' | ' .  $this->netpbmPath . 'ppmquant 256' . ' | ' . $this->netpbmPath . 'ppmtogif -comment="' .  $this->imgComment . '" > ' . $DIR_MEDIA . "\"$thumbnail\"";
                        }
                        elseif ( eregi("\.png", $originalFile) )
                        {
                                $tmp_command = $this->netpbmPath . 'pngtopnm ' . $DIR_MEDIA . "\"$srcPath\"" . ' | ' .  $this->netpbmPath . 'pnmscale -width=' . $th_width . ' -height=' . $th_height . ' | ' .  $this->netpbmPath . 'ppmquant 256' . ' | ' . $this->netpbmPath . 'pnmtopng > ' . $DIR_MEDIA . "\"$thumbnail\"";
                        }

                        exec ( $tmp_command );
                }

                $winX = ($xSize > $this->maxWinX) ? $this->maxWinX : $xSize;
                $winY = ($ySize > $this->maxWinY) ? $this->maxWinY : $ySize;
                return ('<a href="'.$this->mediaURL.$srcPath.'" onclick="window.open(this.href,\'imagepopup\',\'status=no,toolbar=no,scrollbars='.$this->sBar.',resizable=no,width='.$winX .',height='.$winY.'\'); return false;" ><img src="'.$this->mediaURL.$thumbnail.'" title="'.$comment.'" alt="'.$comment.'" /></a>');
        }


        // Calculate thumbnail size
        // Original code: Bruno VIBERT < bvibert[at]mytracer[dot]com >
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
                if ($this->imageMode == 'yes')
                {
                        $this->currentItem->body = preg_replace_callback("#<\%Image\((.*?)\|(.*?)\|(.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'replaceCallbackImageMode'), $this->currentItem->body);
                        $this->currentItem->more = preg_replace_callback("#<\%Image\((.*?)\|(.*?)\|(.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'replaceCallbackImageMode'), $this->currentItem->more);
                }
                else
                {
                        // A side-effect here is if Image skinVar mode is off, <%Image%> tage is still got replace by this
                        // preg_replace...
                        $this->currentItem->body = preg_replace_callback("#<\%image\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'replaceCallback'), $this->currentItem->body);
                        $this->currentItem->more = preg_replace_callback("#<\%image\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'replaceCallback'), $this->currentItem->more);
                }
        }

		function _getThumbsPrefix() {
			return $this->thumbsPrefix;
		}

}
?>
