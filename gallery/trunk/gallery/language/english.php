<?php

//language file
//english NP_Gallery
define('__NPG_OPT_DONT_DELETE_TABLES','Delete this plugin\'s table and data when uninstalling?');
define('__NPG_BREADCRUMB_GALLERY','Gallery');

//special collections:
define('__NPG_COLL_MOSTVIEWED','Most Viewed');

//errors:
define('__NPG_ERR_GALLLERY_NOT_CONFIG','The Gallery hasn\'t been configured.');
define('__NPG_ERR_BAD_TEMPLATE','Bad template name');
define('__NPG_ERR_NO_UPD_TEMPLATE','There was a problem updating the template');
define('__NPG_ERR_BAD_FUNCTION','Function not defined');
define('__NPG_ERR_NOT_ADMIN','Need to be administrator to perform this function');
define('__NPG_ERR_ALBUM_UPDATE','Album was NOT updated');
define('__NPG_ERR_TEAM_UPDATE','Album team was NOT updated');
define('__NPG_ERR_DA_MOVE_PICTURE','No permission to move files -- album will not be deleted');
define('__NPG_ERR_NOSUCHTHING','Requested item does not exist');

//admin page:
//tabs:
define('__NPG_ADMIN_TITLE','Gallery Admin Page');
define('__NPG_ADMIN_TAB_ALBUMS','Albums');
define('__NPG_ADMIN_TAB_COMMENTS','Comments');
define('__NPG_ADMIN_TAB_CONFIG','Configuration');
define('__NPG_ADMIN_TAB_USERS','Gallery Users');
define('__NPG_ADMIN_TAB_TEMPLATES','Templates');
define('__NPG_ADMIN_TAB_ADMIN','Admin Functions');

//comments tab
define('__NPG_ADMIN_NO_DEL_PERMISSION','You don\'t have permission to delete this comment');
define('__NPG_ADMIN_NO_COMMENT','No such comment');
define('__NPG_ADMIN_NOTDELETED','Comment not deleted');
define('__NPG_ADMIN_DELETED','Comment deleted');
define('__NPG_PAGE','Page');


//configuration tab
define('__NPG_ADMIN_GEN_OPTIONS','General Options');
define('__NPG_ADMIN_ADD_LEVEL','Add Album permission level');
define('__NPG_ADMIN_ONLY_ADMIN','Only super-admin');
define('__NPG_ADMIN_ONLY_REGUSERS','All registered users');
define('__NPG_ADMIN_ANYONE','Anyone');
define('__NPG_ADMIN_SELECTEDUSERS','Only selected users');
define('__NPG_ADMIN_NOSELECT','None currently selected. To add select users under Gallery Users tab');
define('__NPG_ADMIN_PROMOBLOG','Promotion category/blog');
define('__NPG_ADMIN_NOPROMO','None');
define('__NPG_ADMIN_ACTIVETEMPLATE','Active template');
define('__NPG_ADMIN_VIEWTIME','Minutes between unique views');
define('__NPG_ADMIN_BATCH_SLOTS','Number of batch upload slots');
define('__NPG_ADMIN_IMAGE_DIR','Image Directory (must *not* end in /)');
define('__NPG_ADMIN_MAX_INT_DIM','Max Intermediate picture dimensions (h x w)');
define('__NPG_ADMIN_THUMB_DIM','Thumbnail dimensions (h x w)');
define('__NPG_ADMIN_COMMENTSPERPAGE','Number of comments per page (in admin area)');
define('__NPG_ADMIN_THUMBSPERPAGE','Number of thumbnails per page (album view)');

define('__NPG_ADMIN_GRAPHICS_OPTIONS','Graphics Options');
define('__NPG_ADMIN_GRAPHICS_ENGINE','Graphics engine');
define('__NPG_ADMIN_GD_INSTALLED','GD installed with support for true color images');
define('__NPG_ADMIN_GD_NOT_INSTALLED','GD not installed or installed without support for true color images');
define('__NPG_ADMIN_IM_INSTALLED','IM installed, base path correct');
define('__NPG_ADMIN_IM_NOT_INSTALLED','ImageMagick not installed, or base path is incorrect');
define('__NPG_ADMIN_IM_PATH','ImageMagick path (must end in a /)');
define('__NPG_ADMIN_IM_OPTIONS','ImageMagick options');
define('__NPG_ADMIN_IM_QUALITY','ImageMagick jpg quality');

//select user tab
define('__NPG_ADMIN_PERMITTED_USERS','Users with permission to add albums');
define('__NPG_ADMIN_REMOVE_SELECT_USER','Remove from permitted list');
define('__NPG_ADMIN_GIVE_ADD_PERM','Give add album permission');
define('__NPG_ADMIN_ADD_TO_LIST','Add to list');

//admin functions tab
define('__NPG_ADMIN_ADMIN_FUNCTIONS','Admin Functions');
define('__NPG_ADMIN_CLEANUP','Cleanup');
define('__NPG_ADMIN_CLEANUP_DESC','Make number of images in albums consistent with what\'s in the database');
define('__NPG_ADMIN_RETHUMB','Rethumb');
define('__NPG_ADMIN_RETHUMB_DESC','Resize all thumbnails and intermediate pictures');
define('__NPG_ADMIN_ALLALBUMS','All Albums');
define('__NPG_ADMIN_RETURN','Return to Album List');
define('__NPG_ADMIN_UPDATE_TEMPLATE','Template updated successfully');
define('__NPG_ADMIN_SUCCESS_CLEANUP','Database cleanedup');


//album tab
define('__NPG_ERR_NO_ALBUMS','You don\'t have any albums to list');
define('__NPG_ADMIN_SUCCESS_ALBUM_UPDATE','Album updated');
define('__NPG_ADMIN_SUCCESS_TEAM_UPDATE','Album team updated');
define('__NPG_ADMIN_MASSUPLOAD','Upload files');
define('__NPG_ADMIN_NEWALBUM','New Album');
define('__NPG_ADMIN_MASSUPLOAD_DESC','Add all files in upload/ directory to specified album');

//comments tab
define('__NPG_ADMIN_COMMENTS', 'User Comments');
define('__NPG_COMMENT', 'Comment');
define('__NPG_AUTHOR', 'Author');
define('__NPG_TIME', 'Time');
define('__NPG_PICTUREID','Picture');


//templates tab
define('__NPG_ADMIN_TEMPLATES','Templates');


//forms

//add album form
define('__NPG_FORM_ADDALBUM', 'Add Album Form');
define('__NPG_FORM_ALBUM_TITLE','Album Title');
define('__NPG_FORM_ALBUM_DESC','Description');
define('__NPG_FORM_SUBMITALBUM','Add Album');

//edit picture form
define('__NPG_FORM_EDITPICTURE','Edit Picture Form');
define('__NPG_FORM_PICTURETITLE','Picture Title');
define('__NPG_FORM_PICTUREDESCRIPTION','Picture Description');
define('__NPG_FORM_MOVETOALBUM','Move to Album');
define('__NPG_FORM_NOPICTOEDIT','No picture to edit');

//delete picture form
define('__NPG_FORM_REALLYDELETE','Do you really want to delete this picture? Press cancel to go back');
define('__NPG_FORM_DELETEPICTURETEXT','Press Delete to delete this picture from the database. The picture will also be deleted from the disk.');
define('__NPG_FORM_DELETEPROMOTOO','Delete promotional blog posts as well?');
define('__NPG_FORM_NOPICTTODELETE','No picture to delete');

//add picture form
define('__NPG_FORM_UPLOADFILEFORM','Upload File Form');
define('__NPG_FORM_PICTURELABLE','Picture ');
define('__NPG_FORM_SUBMITFILES','Submit Files');
define('__NPG_FORM_ADDTOALBUM','Add to Album');
define('__NPG_FORM_NOADD','will not be added due to ');
define('__NPG_FORM_NOPICTSTOADD','No pictures to add');
define('__NPG_FORM_PROMOTE','Add New Picture Post');

//general form
define('__NPG_FORM_SUBMIT_CHANGES','Submit changes');
define('__NPG_FORM_USER','User');
define('__NPG_FORM_NAME','Name');
define('__NPG_FORM_TITLE','Title');
define('__NPG_FORM_DESC','Description');
define('__NPG_FORM_YES','Yes');
define('__NPG_FORM_ACTIONS','Actions');
define('__NPG_FORM_IMAGES', 'Images');
define('__NPG_FORM_OWNER','Owner');
define('__NPG_FORM_SETTINGS','Settings');
define('__NPG_FORM_DELETE','Delete');
define('__NPG_FORM_CLONE','Clone');
define('__NPG_FORM_EDIT','Edit');

//template
define('__NPG_FORM_EDIT_TEMPLATE','Edit Template');
define('__NPG_FORM_TEMPLATE_NAME','Template Name');
define('__NPG_FORM_TEMPLATE_DESC','Template Description');
define('__NPG_FORM_TEMPLATE_SETTINGS','Template Settings');
define('__NPG_FORM_NEWTEMPLATE','New Template');
define('__NPG_FORM_TEMPLATE_LIST','Gallery List');
define('__NPG_FORM_TEMPLATE_ALBUM','Album');
define('__NPG_FORM_TEMPLATE_PICTURE','Picture');
define('__NPG_FORM_TEMPLATE_COMMENTS','Comments');
define('__NPG_FORM_TEMPLATE_HEADER','Header');
define('__NPG_FORM_TEMPLATE_BODY','Body');
define('__NPG_FORM_TEMPLATE_FOOTER','Footer');
define('__NPG_FORM_TEMPLATE_PROMO','Promo post template');
define('__NPG_FORM_TEMPLATE_PROMOIMAGES','Promo images');
define('__NPG_FORM_CREATENEWTEMPLATE','Create new template');

//delete album form
define('__NPG_FORM_DELETE_ALBUM','Delete Album');
define('__NPG_FORM_REALLY_DELETE_ALBUM','Do you really want to delete this album? Press cancel to return to album list');
define('__NPG_FORM_CANCEL','Cancel');
define('__NPG_FORM_DELETE_OR_MOVE','You can delete all the pictures, or move them to another album (if you have permissions to do so). Double check that the following option is set accordingly. If you only have an option to delete, you do not have add permission for any other album.');
define('__NPG_FORM_DELETE_PICTURES','Delete album pictures');

//edit album form
define('__NPG_FORM_MODIFY_ALBUM','Edit Album');
define('__NPG_FORM_RETURN_ADMIN','Return to Album List');
define('__NPG_FORM_NO_OWNER_ACTIONS','No actions for owner');
define('__NPG_FORM_YES','Yes');
define('__NPG_FORM_NO','No');
define('__NPG_FORM_TOGGLE_ADMIN','Toggle Admin');
define('__NPG_FORM_ADDTEAMMEMBER','Add Team Member');
define('__NPG_FORM_CHOOSEMEMBER','Choose Member');
define('__NPG_FORM_ADMIN_PRIV','Admin Privileges?');
define('__NPG_FORM_ADDTOTEAM','Add to team');
define('__NPG_FORM_COMMENTSALLOWED','Comments Allowed');
define('__NPG_FORM_ALBUM_THUMBNAIL','Album Thumbnail');
define('__NPG_FORM_CURRENT_ALBUM_TEAM','Current Album Team');
define('__NPG_FORM_ALBUM_ADMIN','Album Admininistrator');

//picture comments
define('__NPG_NO_COMMENTS','No comments for this picture');

//promo post
define('__NPG_PROMO_FORM_CANCEL','Cancel Promo Post');
define('__NPG_PROMO_FORM_TITLE','Title');
define('__NPG_PROMO_FORM_BODY','Body');
define('__NPG_PROMO_FORM_SUBMIT','Submit Promo Post');
define('__NPG_PROMO_FORM_SUCCESS','Post added successfully');
define('__NPG_PROMO_FORM_CLOSE','Close this window');

//add new album -- massupload
define('__NPG_FORM_MASSUPLOAD_NEWALBUM','Enter the title and description for the new album. Pressing submit will add the new album and add all pictures in the upload folder. Press back to cancel');
define('__NPG_FORM_MASSUPLOAD_CONFIRM','Click Submit to add all pictures in the upload folder to the gallery');
define('__NPG_FORM_MASSUPLOAD_SUBMIT','Submit');

?>

