<?php
/** English language file for NP_Profile Plugin
*/

// random words/phrases
define('_PROFILE_EDIT',		   'edit');
define('_PROFILE_TOP',		   'top');
define('_PROFILE_DELETE',		   'delete');
define('_PROFILE_SUBMIT',		   'Submit');
define('_PROFILE_MALE',		   'male');
define('_PROFILE_FEMALE',		   'female');
define('_PROFILE_YES',		   'yes');
define('_PROFILE_NO',		   'no');
define('_PROFILE_FIELD',		   'field');
define('_PROFILE_LABEL',		   'label');
define('_PROFILE_TYPE',		   'type');
define('_PROFILE_REQUIRED',		   'required');
define('_PROFILE_ENABLED',		   'enabled');
define('_PROFILE_ACTIONS',		   'actions');
define('_PROFILE_PARAMETER',		   'parameter');
define('_PROFILE_VALUE',		   'value');
define('_PROFILE_HELP',		   'help');
define('_PROFILE_NAME',		   'name');
define('_PROFILE_LENGTH',		   'length');
define('_PROFILE_SIZE',		   'size');
define('_PROFILE_FORMAT',		   'format');
define('_PROFILE_WIDTH',		   'width');
define('_PROFILE_HEIGHT',		   'height');
define('_PROFILE_FILESIZE',		   'File Size');
define('_PROFILE_FILETYPES',		   'File Types');
define('_PROFILE_OPTIONS',		   'options');
define('_PROFILE_DEFAULT',		   'default');
define('_PROFILE_VALIDATE',		   'validate');
define('_PROFILE_PROFILE',		   'profile');
define('_PROFILE_CLOSE',		   'close');

//Field Labels
define('_PROFILE_LABEL_PASSWORD',		   'Password');
define('_PROFILE_LABEL_NOTES',		   'Notes');
define('_PROFILE_LABEL_URL',		   'Home URL');
define('_PROFILE_LABEL_REALNAME',		   'Real Name');
define('_PROFILE_LABEL_NICK',		   'User Name');
define('_PROFILE_LABEL_MAIL',		   'Email Address');
define('_PROFILE_LABEL_MSN',		   'MSN Account');
define('_PROFILE_LABEL_SEX',		   'Sex');
define('_PROFILE_LABEL_BIRTHDATE',		   'Birthday');
define('_PROFILE_LABEL_AVATAR',		   'Avatar');
define('_PROFILE_LABEL_LOCATION',		   'Location');
define('_PROFILE_LABEL_HOBBIES',		   'Hobbies');
define('_PROFILE_LABEL_SECRET',		   'Secret');
define('_PROFILE_LABEL_ICQ',		   'ICQ Number');
define('_PROFILE_LABEL_FAVORITESITE',		   'Favorite Site');
define('_PROFILE_LABEL_BIO',		   'Bio');
define('_PROFILE_LABEL_RESUME',		   'Resume Link');

// Plugin Options
define('_PROFILE_OPT_QUICKMENU',		   'Show Admin Area in quick menu?');
define('_PROFILE_OPT_DEL_UNINSTALL_DATA',	   'Delete NP_Profile user data table on uninstall?');
define('_PROFILE_OPT_DEL_UNINSTALL_FIELDS',	   'Delete NP_Profile field definition tables on uninstall?');
define('_PROFILE_OPT_REQ_EMP_START',		   'HTML tag or string to be placed before required field label');
define('_PROFILE_OPT_REQ_EMP_END',		   'HTML tag or string to be placed after required field label');
define('_PROFILE_OPT_DEFAULT_IMAGE',	'URL to image to be used when none available');
define('_PROFILE_OPT_EMAIL_PUBLIC',		'To whom should we show email addresses?');
define('_PROFILE_OPT_SELECT_ALL',		'All Users');
define('_PROFILE_OPT_SELECT_MEMBERS',		'Members Only');
define('_PROFILE_OPT_SELECT_NOBODY',		'Nobody');
define('_PROFILE_OPT_PWD_MIN_LENGTH',		'Minimum Length in characters of a user password. Integer. 0 disables length check: ');
define('_PROFILE_OPT_PWD_COMPLEXITY',		'Password Complexity Check. (Home many character types should be present out of a-z, A-Z, 0-9, punctuation marks?):');
define('_PROFILE_OPT_SELECT_OFF_COMP',		'Off');
define('_PROFILE_OPT_SELECT_ONE_COMP',		'One character type');
define('_PROFILE_OPT_SELECT_TWO_COMP',		'Two character types');
define('_PROFILE_OPT_SELECT_THREE_COMP',		'Three character types');
define('_PROFILE_OPT_SELECT_FOUR_COMP',		'Four character types');

//Admin Area
define('_PROFILE_ADMIN_TOOLTIP',		   'Manage NP_Profile Plugin');

// SKINVAR
define('_PROFILE_SV_CHANGE_PASSWORD',		   'Change Password');
define('_PROFILE_SV_OLD_PASSWORD',		   'Old Password: ');
define('_PROFILE_SV_NEW_PASSWORD',		   'New Password: ');
define('_PROFILE_SV_VERIFY_PASSWORD',		   'Verify Password: ');
define('_PROFILE_SV_STATUS_UPDATED',		   'Profile Updated: ');
define('_PROFILE_SV_EDITLINK_FORM',		   'Return to Member Profile...');
define('_PROFILE_SV_EDITLINK_EDIT',		   'Edit your Member Profile...');

// ACTIONS
define('_PROFILE_ACTION_DENY',		   'You are not authorized to perform this action.');
define('_PROFILE_ACTION_NO_FIELD',           'No field name specified.');
define('_PROFILE_ACTION_NOT_FIELD',           'Field does not exist.');
define('_PROFILE_ACTION_DUPLICATE_FIELD',		   'The field already exists. Please choose a different name, or modify the existing field.');
define('_PROFILE_ACTION_NO_TYPE',           'No type name specified.');
define('_PROFILE_ACTION_REQ_FIELDS',           'The following fields are required: ');
define('_PROFILE_ACTION_BAD_URL',           'Not a valid URL.');
define('_PROFILE_ACTION_BAD_PWD_MATCH',           'Passwords do not match.');
define('_PROFILE_ACTION_BAD_PWD',           'Invalid Password.');
define('_PROFILE_ACTION_BAD_NUM',           'Not a number.');
define('_PROFILE_ACTION_BAD_DATE',           'Date does not match required format');
define('_PROFILE_ACTION_BAD_DATE_HELP',           'where day(D) is 2 digits, month(M) is 2 digits and year(Y) is 4 digits');
define('_PROFILE_ACTION_BAD_FILE_FIELD',           'You cannot upload a file to this field.');
define('_PROFILE_ACTION_BAD_FILE_SIZE',           'File is too big. Maximum filesize is ');
define('_PROFILE_ACTION_BAD_FILE_TYPE',           'File type is not allowed. Must be any of these');
define('_PROFILE_ACTION_BAD_FILE_IMGSIZE',           'Image Size exceeded');
define('_PROFILE_ACTION_BAD_FILE_IMGSIZE_YOU',           'Your image is :');
define('_PROFILE_ACTION_BAD_FILE_COPY',           'Could not copy file.');
define('_PROFILE_ACTION_BAD_PWD_VALID',           'New password does not meet the site\'s validation requirements set out below:');
define('_PROFILE_ACTION_BAD_PWD_ML',           'This is the minimum length of the password string in characters.');
define('_PROFILE_ACTION_BAD_PWD_COMP',           'This is the minimum complexity of the password string, i.e. how many of the following character types must be present - a-z, A-Z, 0-9, punctuation marks.');
define('_PROFILE_ACTION_UNKNOWN',           'Bad action type');

//Admin Area
define('_PROFILE_ADMIN_OPTIONS',		   'Edit NP_Profile Options');
define('_PROFILE_ADMIN_FIELD_DEF',		   'Field Definitions');
define('_PROFILE_ADMIN_FIELD_TYPE',		   'Field Types');
define('_PROFILE_ADMIN_CONFIG',		   'Form Configuration');
define('_PROFILE_ADMIN_EXAMPLE',		   'Example Code');
define('_PROFILE_ADMIN_FIELDS_HEAD',		'Custom Field Definitions');
define('_PROFILE_ADMIN_TYPES_HEAD',		'Field Types');
define('_PROFILE_ADMIN_FIELDS_DELETE_HEAD',		'Delete Field');
define('_PROFILE_ADMIN_CONFIG_HEAD',		'Form Configuration');
define('_PROFILE_ADMIN_EXAMPLE_HEAD',		'Example Code');
define('_PROFILE_ADMIN_FIELDS_EDIT_HEAD',		'Edit Custom Field Definitions');
define('_PROFILE_ADMIN_TYPES_EDIT_HEAD',		'Edit Type Default Settings');
define('_PROFILE_ADMIN_FIELDS_ADD',		'Add New Field');
define('_PROFILE_ADMIN_FIELDS_SUCCESS_ADD',		'Field successfully added.');
define('_PROFILE_ADMIN_FIELDS_SUCCESS_UPD',		'Field successfully updated.');
define('_PROFILE_ADMIN_FIELDS_SUCCESS_DEL',		'Field successfully deleted.');
define('_PROFILE_ADMIN_FIELDS_ACTION_PERFORM',		'Action to Perform');
define('_PROFILE_ADMIN_FIELDS_ACTION_ADD',		'Add Field');
define('_PROFILE_ADMIN_FIELDS_ACTION_UPD',		'Update Field');
define('_PROFILE_ADMIN_FIELDS_ACTION_DEL',		'Delete Field');
define('_PROFILE_ADMIN_CONFIG_SUCCESS_UPD',		'Config successfully updated.');
define('_PROFILE_ADMIN_HELP_NAME',		'Only a-z and 0-9 allowed. Only advanced users should change an existing field\'s name.');
define('_PROFILE_ADMIN_HELP_LABEL',		'Can be a descriptive title for the field, for use as a label.');
define('_PROFILE_ADMIN_HELP_TYPE',		'The field type. See help file for more details.');
define('_PROFILE_ADMIN_HELP_REQUIRED',		'Is this a required field?');
define('_PROFILE_ADMIN_HELP_ENABLED',		'Is this field enabled. If no, will be ignored by skinvar.');
define('_PROFILE_ADMIN_HELP_LENGTH',		'Integer. Usually leave as 0 (uses type default). Maxlength of field. # of rows for textarea.');
define('_PROFILE_ADMIN_HELP_SIZE',		'Integer. Usually leave as 0 (uses type default). Size of input field. # of columns for textarea.');
define('_PROFILE_ADMIN_HELP_FORMAT',		'A date format string, like D-M-Y, M-D-Y, Y-M-D, or Y-D-M. Only meaningful for date type. Or for list fields, something of format <i>tag</i>-<i>class</i>, where <i>tag</i> is one of ol,ul, or dl, and <i>class</i> is name of css class of list.');
define('_PROFILE_ADMIN_HELP_WIDTH',		'Integer. Max width of images for upload. Only valid for file type.');
define('_PROFILE_ADMIN_HELP_HEIGHT',		'Integer. Max height of images for upload. Only valid for file type.');
define('_PROFILE_ADMIN_HELP_FILESIZE',		'Integer. Max file size in bytes of images for upload. Blank uses default for type. Only valid for file type.');
define('_PROFILE_ADMIN_HELP_FILETYPES',		'Allowed file types for upload. Default types are jpg;jpeg;gif;png (use ; to separate multiple types), but any extention can be permitted. Blank uses default for type. Only valid for file type.');
define('_PROFILE_ADMIN_HELP_OPTIONS',		'String. Special type-specific options. For dropdown, list, and radio types, use format \'display1|value1;display2|value2\' where display is what user sees and value is what gets stored. Can be the same. e.g. \'yes|1;no|0\' displays yes and no to user, but stores 1 or 0 respectively. \'yes|yes;no|no\' displays and stores the values yes or no. For other field types see help document.');
define('_PROFILE_ADMIN_HELP_DEFAULT',		'String. Default value for fields giving choices. Valid only for list, dropdown, and radio fields. The storage value should be given. e.g. for an options field of \'yes|1;no|0\', use 1 to set default to yes and 0 to set default to 0');
define('_PROFILE_ADMIN_HELP_VALIDATE',		'');
define('_PROFILE_ADMIN_HELP_PERFORM',		'Choose an action to perform: Add a new field, modify an existing field, or delete a field.');
define('_PROFILE_ADMIN_TYPES_SUCCESS_UPD',		'Type successfully updated.');
define('_PROFILE_ADMIN_HELP_TYPE_FILESIZE',		'Integer. Max file size in bytes of images for upload. Blank uses Nucleus Max Upload Size from General Settings. Only valid for file type.');
define('_PROFILE_ADMIN_HELP_TYPE_FILETYPES',		'Allowed file types for upload. Default types are jpg;jpeg;gif;png (use ; to separate multiple types), but any extention can be permitted. Blank uses Nucleus Allowed Types from General Settings. Only valid for file type.');
define('_PROFILE_ADMIN_TYPES_NO_TYPE',		'No type of this name exists.');
define('_PROFILE_ADMIN_EXAMPLE_INTRO',		'Below is a sample of the content section of a Member Details skin part.
		This is based on the default skin and requires some code be added to your skin\'s css file as shown below.
		If you want to try this in your skin, be sure to leave the head, header, sidebar, footer, etc... code in the
		Member Details skin part. This should only replace what goes in the content part of the skin, and may require
		some modifications to fit with your skin.');
define('_PROFILE_ADMIN_EXAMPLE_CSS',		'CSS modifications needed for example above:');
define('_PROFILE_ADMIN_DELETE_OPEN',		'You have chosen to delete this field');
define('_PROFILE_ADMIN_DELETE_BODY1',		'This will also delete the data entered by each member in this field.');
define('_PROFILE_ADMIN_DELETE_BODY2',		'It is recommended that you consider disabling the field instead of deleting it.');
define('_PROFILE_ADMIN_DELETE_CONFIRM',		'Do you really want to delete this field?');
define('_PROFILE_ADMIN_DELETE_RETURN',		'Return to Field Definitions.');

// new in 2.1
define('_PROFILE_OPTIONS_CSS2URL',				'Full URL to the css file for Edit Profile page');
define('_PROFILE_ADMIN_CONFIG_INTRO',		'Here you can format the edit profile page (the page linked by the &lt;%Profile(editprofile)%&gt; skinvar)
		The details of the formatting are given in the help file.');

?>
