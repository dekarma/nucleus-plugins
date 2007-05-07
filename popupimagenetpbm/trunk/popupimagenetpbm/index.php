<?php
	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../'; 

	include($strRel . 'config.php');
	if (!$member->isLoggedIn())
		doError('You\'re not logged in.');
		
	include($DIR_LIBS . 'PLUGINADMIN.php');

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('PopupImageNetPBM');
	$oPluginAdmin->start();

	global $pluginpopupimage, $CONF;
	$pluginpopupimage=$CONF['PluginURL']."popupimagenetpbm/";

	echo "<h2>popupImageNetPBM Plugin Admin Option</h2>";
	echo "<ul>\n";
	echo "<li><a href=\"".$pluginpopupimage."?admin_option=delete\">Flush all thumbnail image</a></li>\n";
	echo "</ul>\n";

	$admin_option=$_GET[admin_option];
	$doaction=$_POST[doaction];

	if($admin_option == "delete" ) {
		$thumbsPrefix =	$oPluginAdmin->plugin->_getThumbsPrefix();
		popupImageDelete();
	}
	elseif ($doaction =="Confirm deletion") {
		$thumbsPrefix =	$oPluginAdmin->plugin->_getThumbsPrefix();
		popupImageDelete();
	}

	echo "<h2>Credits</h2><ul><li><a href=\"http://edmondhui.homeip.net/blog/\">  Edmond Hui (admun)</a></li><li><a href=\"http://www.rbnet.it\">Roberto Bolli (rbnet)</a></li></ul>";

	$oPluginAdmin->end();

	/* Confirm deletion */
	function popupImageDelete() {
		global $DIR_MEDIA, $doaction, $pluginpopupimage;

		if (!$doaction) {
			echo "<h2>List of the thumbnails in the Media directory</h2>";
			echo "<ol>";

			$numbfiles = rmdirr($DIR_MEDIA, $makelist=1);

			echo "</ol>";
			echo "<strong>".$numbfiles." thumbnails in the the Media folder.</strong>";

			if ( !$numbfiles ) {
				echo " Nothing to do.";
			}
			else {
				echo "<div style=\"border: 2px red solid; padding: 5px;\">";
				echo "<h4><u>Attention!</h4>";
				echo "Deleting the thumbnails in the Media directory can't be undone! Be sure before proceed!";
				echo "<form method=\"POST\" name=\"selectform\" action=\"".$pluginpopupimage."\">";
				echo "<input type=\"submit\" value=\"Confirm deletion\" name=\"doaction\" />";
				echo "</form>";
				echo "</div>";
			}

		}
		else {
			echo "<ol>";
			$numbfiles = rmdirr($DIR_MEDIA, $makelist=0);
			echo "</ol>";
			echo "<strong>".$numbfiles." thumbnails deleted!</strong>";

		}

	}	


        /**
        * Based on the rmdirr() fuction by Aidan Lister
		* <http://aidan.dotgeek.org/lib/?file=function.rmdirr.php>
		* Delete all the thumbnails files in the media dir
        */
		function rmdirr($dirname, $makelist) {
			global $thumbsPrefix;
	
			static $numbfiles=0;

			$dirname = rtrim($dirname,"/");
			
			// Loop through the folder
			$dir = dir($dirname);
			while (false !== $entry = $dir->read()) {
				
				// Skip pointers
				if ($entry == '.' || $entry == '..') {
					continue;
				}

				// Deep delete files in dir
				if (is_dir("$dirname/$entry")) {
					rmdirr("$dirname/$entry", $makelist);
				} elseif ( strstr($entry,$thumbsPrefix) ) {
					if ($makelist) {
						echo " <li>".$dirname."/<span style=\"color:red\">".$entry."</span></li>";
					}
					else {
						unlink("$dirname/$entry");
						echo " <li>entry <em>".$dirname."/<span style=\"color:red\">".$entry."</span></em></li> <strong>deleted</strong>";
					}
					$numbfiles = $numbfiles+1;
				}
			}
			
			// Clean up
			$dir->close();
			return $numbfiles;
        }

?>
