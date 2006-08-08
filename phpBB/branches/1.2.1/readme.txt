OVERVIEW
========

The NP_phpBB family of plugins provide integration between Nucleus CMS and the phpBB web forum.

NP_phpBB plugin provides authentication and automatic user creation.
NP_phpBBvars provides skinvars that extract information from the phpBB database.
NP_phpBBxpost provides the ability to cross-post weblog entries to the associated phpBB instalation.

INSTALATION
===========

1. Download the NP_phpBB.zip file, and upload the contents to the plugins directory of your nucleus instalation.
2. Back up your Nucleus database tables. Go to the 'Backup' page (under 'Management') in the Nucleus admin area and hit the 'Create Backup' button and save the file returned to disk. It is a good idea to make backups regularly, and NP_phpBB alters the schema of the member table.
3. From the plugin management section of your admin area, install the phpBB plugin and optionally the phpBBvars and phpBBxpost plugins.
4. From the Nucleus 'Global Settings' page (under Management), in the 'Member Settings' section, change the 'Allow Members to Change Login/Password' and 'Allow Visitors to Create a Member Account' settings to No.>/p>
5. From the phpBB 'Configuration' page (under General Admin), in the 'User and Forum Basic Settings' section, change 'Allow Username changes' to No.
6. From the phpBB 'Configuration' page (under General Admin), in the 'Cookie settings' section, change the cookie domain and cookie path to values visible from both Nucleus and phpBB.
    * For cases similar to the situation where the phpBB installation is shared.host.domain/~user/phpBB2/ and the nucleus installation is shared.host.domain/~user/nucleus/, the cookie domain would be 'shared.host.domain' and the cookie path would be '/~user/'
    * For cases similar to the situation where the phpBB installation is my.personal.domain/phpBB2/ and the nucleus installation is my.personal.domain/nucleus/, the cookie domain would be 'my.personal.domain' and the cookie path would be '/'
    * For cases similar to the situation where the phpBB installation is forum.my.domain/ and the nucleus installation is weblog.my.domain/, the cookie domain would be '.my.domain' (note the leading period.) and the cookie path would be '/'
7. From the Nucleus Plugins screen, edit the options for the phpBB plugin. You may need to alter the file system path to the phpBB instalation (if it isn't in a directory named phpBB2 that is at the same level as your Nucleus instalation), then change the option labeled 'Enable phpBB authentication' to true. Upon hitting the save options button, the path specified will be validated and an error message will be returned if it is an incorrect path. Leave the option labeled 'Enable ExtAuth authentication support' set to no, as it requires some core hacks.
8. At this point, the phpBB plugin should be installed. You can check by logging out of nucleus, logging in to phpBB, then visiting a nucleus page. If things are functioning correctly, you will find yourself logged in with the same username as you are logged in with in phpBB. You now can move on to setting up the phpBBvar and/or phpBBxpost plugins.

(Optional install of NP_phpBBvars)
9. From the Nucleus Plugins screen, install the phpBBvars plugin. It has no configuration options, so none need to be set.
10. Update skins to use desired skin variables (listed in the help page).

(Optional install of NP_phpBBxpost)
11. Update your nucleus instalation. NP_phpBBxpost requires a newer version of ITEM.php than is available in the 3.2/3.21 release. You can be daring and use the CVS version of nucleus, or you can go the safe route and pull this file from the SourceForge CVS viewer (http://cvs.sourceforge.net/viewcvs.py/*checkout*/nucleuscms/nucleus/nucleus/libs/ITEM.php?rev=1.18).
12. From the Nucleus Plugins screen, install the phpBBxpost plugin.
13. Start configuring the plugin by going to the 'Plugins' page, then the 'edit options' screen for the phpBB crosspost plugin. A number of options will need to be configured, and the decisions that need to be made are detailed below.
14. Select a forum to cross-post to. For security reasons, a single forum is used for all cross-posts. Enter the ID number for this in the coresponding text field.
15. Decide how to handle forum permissions. phpBBxpost allows you control whether it will ignore or respect posting restrictions for thread creation, editing and deletion. This behavior is controlled by the trio of radio buttons named like 'Ignore phpBB foo restrictions?' and is fine-grained enough that you can ignore for some commands while ignoring for others, but is too corse to provide member by member control. If an ignore option is set to yes, the plugin will ignore all restrictions placed on the coresponding option, including the locking of the forum in question. This may be desired for deletion, but likely isn't desired for create or edit.
16. Decide how much freedom users have over the appearance of their cross-posts. The plugin is able to cross post the post body, extended body, and reference link, along with embeding images into the cross-post. Users may be permitted to control the behavior exhibitied, or they may be restricted to the defaults selected, but this can't be controlled on a member by member basis. The user control is determined with the 'Allow users to override cross post content options?' option, and the next four options determining the defaults.
17. The final option is only of use when preparing to uninstall the plugin. If set to yes, the internal table used to associate nucleus posts with phpBB posts will be dropped (deleted), removing this information. If you are perminently removing the table, this is desired, but this would not be desired if you are upgrading to a newer version or are planing to reinstall the plugin. This should normally be left on 'no'.

DISASTER CLEAUP
===============

Unlike most plugins, problems with the phpBB plugin have the potential to break your weblog instalation to the point that you would be unable to uninstall or disable it through the admin screens. The reason for this is because it subscribes to an event that happens early in the page generation process (PostAuthentication), and this event is always called regardless of the page that is being generated. Therefore, either of the following courses of action can be taken to deactivate the plugin.

1.The safer option is to use an SQL admin control panel, such as phpMyAdmin or the mysql command line client, and execute the following command.
    UPDATE nucleus_plugin as P, nucleus_plugin_option as O, nucleus_plugin_option_desc as D SET O.ovalue='no' WHERE P.pfile = "NP_phpBB" AND P.pid=D.opid AND D.oname = "enable" AND D.oid=O.oid
2.The simpler option is to just remove the NP_phpBB.php file from the plugins directory, then clean up the plugin subscriptions in the plugin screen.

VERSION HISTORY
===============

Version 1.2: Split template variable integration off into the phpBBvar plugin and added cross-posting capabilities in the phpBBxpost plugin
Version 1.1: Added support for template variable integration and ExtAuth.
Version 1.0-beta: first 'stable' version series, again passing through multiple itterations. Key transition was addition of profile integration.
Version 1.0-alpha: Initial version passing through multiple debugging iterations. A Key transition was moving from a hackish glue DB object to using the DB object that is part of the phpBB code.
Version 0.0: Proof-of concept.

