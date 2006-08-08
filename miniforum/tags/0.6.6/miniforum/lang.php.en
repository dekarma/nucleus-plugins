<?php
/** 
  * Miniforum - plugin for BLOG:CMS and Nucleus CMS
  * 2005, (c) Josef Adamcik (blog.pepiino.info)
  *
  *
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * 
  *  
*/

/**
* Language file for NP_MiniForum. 
* language: english
* version: 0.6.5
* Note: you have to reinstall plugin to get translated plugin options and description.
* 
* Note for translators: New strings are attached on the end of this file (since 0.5.0)
*
* Author's note: as you can see, my english isn't very good. I'll be wery glad, if you correct mistakes
* and strange or funny words in this file. If you do that, please send me your corrections, so I can add 
* them to future releases of this plugin.
*/


//plugin description
define('MF_PLUGIN_DESCRIPTION',                'This plugin alows you to add primitive forum(s) to your blog. '.
        'You can use "<%MiniForum(ShowPosts,myforum)%>"  in skin to list posts from forum "myforum"'.
        '(It\'s short name of forum) and  "<%MiniForum(ShowForm,myforum)%>" to show form for adding posts.'.
        ' Plugin provides admin area where you can manage all forums and posts.');

// plugin options 
define('MF_ENABLE_QICK_MENU',           'Enable quickmenu?');
define('MF_POSTS_TO_SHOW',              'Number of posts to show:');
define('MF_MAX_LINE_LENGTH',            'Max length of word. Longer words will be splited with \'-\'. It prevents users from breaking your page\'s layout.');
define('MF_COVERT_EMOTICONS',           'Convert emoticons to images? (<a href="http://wakka.xiffy.nl/miniforum#smileys">See documentation</a>)');
define('MF_COVERT_URLS',                'Convert urls to links?');
define('MF_EMOTICONS_DIR',				'Path to images of emoticons.');
define('MF_COVERT_NL',                  'Convert linebreaks to <br /> tags');

//template options
define('MF_POST_LIST_HEADER',           'Tempelte for postlist header');
define('MF_POST_BODY',                  'Tempelte for post body');
define('MF_POST_LIST_FOOT',             'Tempelte for postlist footer');
define('MF_FORM_LOGGED',                'Form body for logged users');
define('MF_FORM_NOTLOGGED',             'Form body for not logged users');
define('MF_NAVIGATION',                 'Tempelte for navigation (tag <%navigation%>)');
define('MF_NAME_NOURL',                 'Tempelte to display tag <%name%>, when there isn\'t any link entered.');
define('MF_NAME_URL',                   'Tempelte do display tag <%name%>, when there is a mail or an url entered.');
define('MF_MEMBER_NAME',                'Tempelte to display tag <%name%> for registered users.');
define('MF_DATE',                       'Tempelte to display date (like in <a href="http://php.net/date" title="link to the php manual">php date() function</a>)');
define('MF_TIME',                       'Tempelte to display time');
define('MF_NEXTPAGE',                   'Text for next page link');
define('MF_PREVIOUSPAGE',               'Text for previous page link');
define('MF_FIRSTPAGE',                  'Text for first page link');
define('MF_LASTPAGE',                   'Text for last page link');

// qick menu title and tooltip
define('MF_QM_TITLE',                   'Mini forum');
define('MF_QM_TOOLTIP',                 'Miniforum management');

//errors
define('MF_FORUM_DOESNT_EX',            'chosen forum doesn\'t exist');
define('MF_UNKNOWN_OPTION',             'inserted unknown option ');
define('MF_NAME_PROTECTED',             'Name \'$uname\' belongs to registered user. Please use another name, or login.'); //instead of $uname will be insertet user name
define('MF_NAME_MISSING',               'You have to insert your name!!');
define('MF_TEXT_MISSING',               'You have to insert text of your post!!');


// **** Admin Area ****

define('MF_ADMIN_AREA_HEADING',         'Mini forum - forum management');
define('MF_FORUM_LIST_HEADING',         'Existing forums');
define('MF_CREATE_FORUM_HEADING',       'Create forum');

//common
define('MF_FORUM',                      'Forum');
define('MF_TITLE',                      'Title');
define('MF_DESCRIPTION',                'Description');
define('MF_POSTS',                      'posts');
define('MF_ACTIONS',                    'Actions');
define('MF_SHOW',                       'show');
define('MF_EDIT_INFO',                  'edit info');
define('MF_DELETE',                     'delete');
define('MF_EDIT',                       'edit');
define('MF_YES',                        'Yes');
define('MF_NO',                         'No');
define('MF_SHORT_NAME',                 'Short name');
define('MF_SHORT_NAME_CHARS',           '(only a-z,A-Z,0-9,-,_)');
define('MF_ERR','Error');
define('MF_NOT_LOGGED_IN',              'You\'re not logged in.');
define('MF_NOT_LOGGED_IN_UPGRADE',      'You have to be logged in as administrator to upgrade!!');
define('MF_UPGRADED',					'MiniForum is now upgraded');



//creating and managing forum
define('MF_CREATE_FORUM_BUTTON',        'Create forum');
define('MF_MISSING_SHORT_NAME',         'No short name specified'); //error message
define('MF_WRONG_SHORT_NAME',           'You can use only these chars: 0-9,a-z,A-z,_,-'); //error message
define('MF_SHORT_NAME_USED',            'Forum with this short name already exists! Please, chose another one.');//error message
define('MF_CONFIRM_FORUM_DELETE',       'Do you really want to delete this forum and all posts in it?');
define('MF_CHANGE_FORUM',               'Change forum information (forum \'$forum_name\')'); //$forum_name will be replaced with forum short name
define('MF_CHANGE_FORUM_BUTTON',        'change');



// managing posts
define('MF_LISTED_POSTS',               'Posts from forum'); //will be falowed by forum name
define('MF_FORUM_EMPTY',                'There aren\'t any posts in this forum (\'$forum_name\') yet');  //$forum_name will be repleaced with forum short name
define('MF_PLIST_PREV',                 '&lt;&lt; Previous'); //button text
define('MF_PLIST_NEXT',                 'Next &gt;&gt;');//button text
define('MF_PLIST_CURRENT_PAGE',         'Page $current_page from $page_count '); //$current_page and $page_count will be repleaced with numbers
define('MF_PLIST_INF',                  'Information'); //column title
define('MF_PLIST_ACTIONS',              'Actions'); //column title
define('MF_CONFIRM_POST_DELETE',        'Do you really want to delete this post?');
define('MF_POST_DELETED',               'Post was erased.');
define('MF_EDIT_POST',                  'Edit post'); 
define('MF_POST_CHANGED',               'Post changed');
define('MF_USER_NAME',                  'User name');
define('MF_USER_URL',                   'User link (http or mail)');
define('MF_POST_BODY',                  'Body');
define('MF_CHANGE_POST_BUTTON',         'Change');


//******************************************************************************
// Since 0.6.0
//******************************************************************************
//templates
define('MF_TEMPLATES',					'Templates');
define('MF_TEMPLATE',					'Template');
define('MF_NEW_TEMPLATE',				'New empty template');
define('MF_COPY',						'Copy');
define('MF_EDIT_TEMPL',					'Edit');
define('MF_DEFAULT_TEMPLATE',			'Create new template with default values');
define('MF_CONFIRM_TEMPLATE_DELETE',	'Do you really want to delete this temlate?');
define('MF_TEMPLATE_NAME',				'Template name');
define('MF_CREATE_TEMPLATE_BUTTON',		'Create template');
define('MF_TEMPLATE_CREATED',			'New template  successfully created.');
define('MF_TEMPLATE_CHANGED',			'Template changed.');
define('MF_TEMPLATE_NAME_USED',			'This name is already used. Please, choose another.');
define('MF_CHANGE_TEMPLATE',			'Change template');
define('MF_GRAV_SIZE',					'Size of gravatar image');
define('MF_GRAV_DEFAULT',				'Default gravatar image (For deteails about gravatar support see <a href="http://wakka.xiffy.nl/miniforum#gravatar">documentation</a>.)');
define('MF_TEMPLATE_DOESNT_EX',			'Template doesn\'t exist.');
define('MF_REFRESH',					'Refresh rate (in seconds).');
//upgrade
define('MF_CURRENT_VERSION',			'Current version is:');
define('MF_UPGRADE_NOTE',				"<p>This version uses new system for templates ".
					 "(See <a href='http://wakka.xiffy.nl/miniforum'>documentation</a>). ".
					 "You can find them in the plugin admin area now.".
					 "Your template was copied to the default template. Only ". 
					 "form template was replaced by new one.</p>");
define('MF_UPGRADE_HEADING',			'NP_MiniForum upgrade');
define('MF_CHOOSE_VERSION',				'Please choose your previous version of plugin:');
define('MF_UPGRADE_BUTTON',				'Upgrade');

//******************************************************************************
// Since 0.6.5
//******************************************************************************
define('MF_CAPTCHA',				'Enable captcha test. (Needs NP_Captcha installed.)');
define('MF_DOCLINK',				'see the Miniforum documentation in the NucleusCMS wiki');
define('MF_FORUMLINK',				'visit the Miniforum thread in the NucleusCMS forum');



?>
