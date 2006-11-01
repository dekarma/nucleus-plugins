<?php
/** English language file for NP_SiteList Plugin

*/
// random words
define('_SITELIST_PAGE',		   'Page');
define('_SITELIST_OF',		        'of');
define('_SITELIST_SET',		        'Set');
define('_SITELIST_EDIT',		   'edit');
define('_SITELIST_DELETE',		   'delete');
define('_SITELIST_EXEMPT',		   'exempt');
define('_SITELIST_APPROVE',		   'approve');
define('_SITELIST_SUSPEND',		   'suspend');
define('_SITELIST_VERIFY',		   'verify');
define('_SITELIST_MANUAL_VERIFY',		   'manual verify');
define('_SITELIST_FIRST',		   'first');
define('_SITELIST_LAST',		        'last');
define('_SITELIST_SITE_DESC',		        'Site Description');
define('_SITELIST_EXEMPTED',		   'exempt');
define('_SITELIST_APPROVED',		   'approved');
define('_SITELIST_SUSPENDED',		   'suspended');
define('_SITELIST_CLOSE',		   'close');
define('_SITELIST_SECONDS',		   'seconds');
define('_SITELIST_PASS',		   'pass');
define('_SITELIST_FAIL',		   'fail');
define('_SITELIST_COMPLETED',		   'completed in');
define('_SITELIST_CANCEL',		   'Cancel');

// Plugin Options
define('_SITELIST_OPT_QUICKMENU',		   'Show Admin Area in quick menu?');
define('_SITELIST_OPT_DEL_UNINSTALL',	   'Delete SiteList data table on uninstall?');
define('_SITELIST_OPT_DEF_NSHOW',		   'Default number of sites to show in skinvar. (Can be overridden by skinvar parameters):');
define('_SITELIST_OPT_DEF_LITAG',		   'Default html tag to enclose site links. e.g li, dd, br. (Can be overridden by skinvar parameters):');
define('_SITELIST_OPT_DEF_SMAN',		   'Default setting for show management links in skinvar. (Can be overridden by skinvar parameters):');
define('_SITELIST_OPT_MAIL',		        'Notify on new additions');
define('_SITELIST_OPT_MAILTO',		        'Send notifications to this address:');
define('_SITELIST_OPT_MAILFROM',		    'Send notifications from this address:');
define('_SITELIST_OPT_COND01',		        'Pregex condition (or simple string) to verify against (blank disables verification):');
define('_SITELIST_OPT_COND02',		         'Pregex condition (or simple string) to verify against (blank is OK):');
define('_SITELIST_OPT_LOGICOP',		         'Logic Operator to apply on conditions');
define('_SITELIST_OPT_AUTO_VERIFY',		   'Apply verification to submitted URLs?');
define('_SITELIST_OPT_THANKS_TEXT',		   'Thank You Text (including any html tags) to display above add form when user submits a site.');
define('_SITELIST_OPT_PAGE_SIZE',		   'Number of sites to show on single page in SiteList Admin Area');

//Admin Area
define('_SITELIST_ADMIN_OPTIONS',		   'Edit SiteList Options');
define('_SITELIST_ADMIN_PER_PAGE',		   ' Sites per page ');
define('_SITELIST_ADMIN_UNCHECKED',		   'Show Unchecked');
define('_SITELIST_ADMIN_CHECKED',		   'Show Checked');
define('_SITELIST_ADMIN_SUSPENDED',		   'Show Suspended');
define('_SITELIST_ADMIN_ADDURL',		   'Add URLs');
define('_SITELIST_ADMIN_SEARCH',		   'Search');
define('_SITELIST_ADMIN_VERIFY_PAGE',		   'Verify This Page');
define('_SITELIST_ADMIN_DELETE_PAGE',		   'Delete This Page');
define('_SITELIST_ADMIN_TOOLTIP',		   'Manage Sites List');
//Unchecked Sites
define('_SITELIST_ADMIN_UC_HEAD',		   'Sites Waiting Approval');
define('_SITELIST_ADMIN_UC_NOSITES',		   'There are no sites awaiting approval');
define('_SITELIST_ADMIN_UC_VERIFY_ALL',		   'Verify All Unchecked Sites');
//Checked Sites
define('_SITELIST_ADMIN_C_HEAD',		   'Approved Sites');
define('_SITELIST_ADMIN_C_NOSITES',		   'There are no approved sites');
define('_SITELIST_ADMIN_C_VERIFY_ALL',		   'Verify All Checked Sites');
//Suspended Sites
define('_SITELIST_ADMIN_S_HEAD',		   'Suspended Sites');
define('_SITELIST_ADMIN_S_NOSITES',		   'There are no suspended sites');
define('_SITELIST_ADMIN_S_VERIFY_ALL',		   'Verify All Suspended Sites');
define('_SITELIST_ADMIN_S_DELETE_ALL',		   'Delete All Suspended Sites');
define('_SITELIST_ADMIN_S_NOTE',		   'Note: Sites that fail five consecutive verifications are automatically deleted from the database. The number of failed verifications is in rounded brackets next to the link title.');
define('_SITELIST_ADMIN_S_DELETE_ALL_WARN',		   'Click [Continue with Delete] to delete all suspended sites from the database. This cannot be undone.</li><li style="color:#fe0000;">Click [Cancel] to abort the operation.');
define('_SITELIST_ADMIN_S_DELETE_ALL_CONT',		   'Continue with Delete');
//Add Sites
define('_SITELIST_ADMIN_AU_HEAD',		   'Add URLs to List');
define('_SITELIST_ADMIN_AU_SUBMIT',		   'Submit site');
define('_SITELIST_ADMIN_AU_URL',		   'URL:');
define('_SITELIST_ADMIN_AU_TITLE',		   'Site Title:');
//Search Results
define('_SITELIST_ADMIN_SR_HEAD',		   'Search Results');
define('_SITELIST_ADMIN_SR_NOSITES',		   'No sites were found like ');

// SKINVAR
define('_SITELIST_SKIN_MANAGE_LINK',		   'Manage SiteList');

// ACTIONS
define('_SITELIST_ACTION_DENY',		   'You are not allowed');
define('_SITELIST_ACTION_VERIFY_HEAD',		   'Verification Results');
define('_SITELIST_ACTION_VERIFY_EXEC',		   'Verification execution time');
define('_SITELIST_ACTION_DELETE_HEAD',		   'Deletion Results');
define('_SITELIST_ACTION_DELETE_EXEC',		   'Deletion execution time');
define('_SITELIST_ACTION_UNKNOWN',           'Bad action type');

// Mail Notification
define('_SITELIST_MAIL_SUBJECT',		   'New site');
define('_SITELIST_MAIL_BODY',		   'A new site was added (SiteList plugin)');

// Verify Set
define('_SITELIST_VSET_NOVERIFY',		   'Verification is disabled. Cannot process.');
define('_SITELIST_VSET_AWAKE',		   'Awake and working again');
define('_SITELIST_VSET_SLEEP',		   'Sleeping for');

// Errors
define('_SITELIST_ERR_001',		   'The specified protocol is not allowed. - 001 - Bad Protocol');
define('_SITELIST_ERR_002',		   'A blocked domain or file extension is in specified URL - 002 - Bad Extension');

?>
