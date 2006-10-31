<?

/*
*	This plugin gets a random quote from the database and shows it on specific sites
*
*	Released under the terms of the GPL.
*
*	(c) Jan Albrecht (jaal) http://www.salid.de/~salid/log
*
* 	History:
*	v0.1: (jaal) initial plugin
*	v0.2: (jaal) - code cleanup
*		     - added some ideas from gRegor
*		       (take a look at http://forum.nucleuscms.org/viewtopic.php?p=22561#22559)
*	v0.3: (jaal) - Bugfix
*	v0.4: (jaal) - Added "Edit quote" feature
*	v0.5 UPCOMING RELEASE: (jaal) - Reworked plugin, so that it fits the XHTML standard
*/

// Extend Nucleus Plugin Class
class NP_RandomQuoteDB extends NucleusPlugin
{
	// set name of plugin
	function getName()
	{
		return 'Random Quote';
	}

	// set auhtor of plugin
	function getAuthor()
	{
		return 'Jan Albrecht (jaal)';
	}

	// set the homesite of the plugin
	function getURL()
	{
		return 'http://www.salid.de/~salid/log/';
	}

	// set version
	function getVersion()
	{
		return '0.2';
	}

	// decribe the plugin
	function getDescription()
	{
		return 'Plugin reads quotes from a database and shows in the blog';
	}

	// I use Nucleus >= 3.0 so all the plugin codes are for this version
	function getMinNucleusVersion()
	{
		return '300';
	}

	// Create tablename
        function getTableList()
	{
		return array(sql_table('plugin_randomquotedb') );
	}


	// Create QuickMenu
	function event_QuickMenu(&$data)
	{
		if ($this->getOption('quickmenu') == 'yes') { 
			array_push(
				$data['options'],
				array(
					'title' => 'RandomQuoteDB',
					'url' => $this->getAdminURL(),
					'tooltip' => 'RandomQuote'
				)
			);
		}
	}

        // Create Event for QuickMenu
        function getEventList()
        {
		return array('RandomQuoteDB', 'QuickMenu');
        }


	// We have an admin area, so set option	
	function hasAdminArea()
	{
		return 1;
	}

	// Create Option for QuickMenu during Install
	// Create table for plugin
	function install()
	{
		$this->createOption('quickmenu', 'Show in quick menu', 'yesno', 'yes');
		$this->createOption('deletetables', 'Delete this plugin\'s table and data when uninstalling?', 'yesno', 'no');
		sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_randomquotedb') . '( rqdb_id INT(11) NOT NULL AUTO_INCREMENT, rqdb_quote LONGTEXT NOT NULL, rqdb_quoteby LONGTEXT, PRIMARY KEY(rqdb_id))');
	}
	
	// To prevent the functiom from deleting the quotes comment this line out!
	function unInstall()
        {
		if ($this->getOption('deletetables') == 'yes')
		{
                	sql_query('DROP TABLE ' . sql_table('plugin_randomquotedb') );
		}
        }

	// Here comes our main function
        function doSkinVar($skinType)
        {
		// css and similiar things should be set by the page which calls the plugin
	
		// Query a random quote from the db
		// Unfortunately this works for mysql only
		$query = ("SELECT * FROM " . sql_table('plugin_randomquotedb') . " ORDER BY RAND( ) LIMIT 1");
		$query_result = mysql_query($query);
		$query_result = mysql_fetch_array($query_result);
		echo $query_result[rqdb_quote] . "<br />\n";
		echo "<br />\n";
		echo $query_result[rqdb_quoteby];
	}

}
?>
