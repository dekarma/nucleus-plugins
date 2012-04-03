<?php
/*
Plugin Name: Bad Behavior
Version: 2.2.01
Description: Deny automated spambots access to your PHP-based Web site.
Plugin URI: http://www.bad-behavior.ioerror.us/
Author: Michael Hampton
Author URI: http://www.homelandstupidity.us/
License: GPL

Bad Behavior - detects and blocks unwanted Web accesses
Copyright (C) 2005 Michael Hampton

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

As a special exemption, you may link this program with any of the
programs listed below, regardless of the license terms of those
programs, and distribute the resulting program, without including the
source code for such programs: ExpressionEngine

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

Please report any problems to badbots AT ioerror DOT us
*/

###############################################################################
###############################################################################

//if (!defined('ABSPATH')) die("No cheating!");

$bb2_mtime = explode(" ", microtime());
$bb2_timer_start = $bb2_mtime[1] + $bb2_mtime[0];

define('BB2_CWD', dirname(__FILE__));

// Settings you can adjust for Bad Behavior.
// DO NOT EDIT HERE; instead make changes in settings.ini.
// These settings are used when settings.ini is not present.
global $bb2_settings_defaults;
$bb2_settings_defaults = array(
	'log_table' => sql_table('bad_behavior'),
	'display_stats' => true,
	'strict' => false,
	'verbose' => false,
	'logging' => true,
	'httpbl_key' => '',
	'httpbl_threat' => '25',
	'httpbl_maxage' => '30',
	'offsite_forms' => false
);

// Bad Behavior callback functions.
require_once("bad-behavior-mysql.php");

// Return current time in the format preferred by your database.
function bb2_db_date() {
	return gmdate('Y-m-d H:i:s');	// Example is MySQL format
}

// Return affected rows from most recent query.
function bb2_db_affected_rows($result) {
	return sql_affected_rows();
}

// Escape a string for database usage
function bb2_db_escape($string) {
	return sql_real_escape_string($string);
}

// Return the number of rows in a particular query.
function bb2_db_num_rows($result) {
	if ($result !== FALSE)
		return sql_num_rows ($result);
	return 0;
}

// Run a query and return the results, if any.
// Should return FALSE if an error occurred.
// Bad Behavior will use the return value here in other callbacks.
function bb2_db_query($query) {
    $result = sql_query($query);
	if (sql_error()) {
		return FALSE;
	}
	return $result;
}

// Return all rows in a particular query.
// Should contain an array of all rows generated by calling mysql_fetch_assoc()
// or equivalent and appending the result of each call to an array.
// For WP this is pretty much a no-op.
function bb2_db_rows($result) {
    $rows = array();
	while ($row = sql_fetch_assoc($result)) {
		$rows[] = $row;
	}
	return $rows;
}

// Return emergency contact email address.
function bb2_email() {
    global $CONF;
	return $CONF['AdminEmail'];
}

// retrieve whitelist
function bb2_read_whitelist() {
	return @parse_ini_file(dirname(BB2_CWD) . "/whitelist.ini");
}

// retrieve settings from database
function bb2_read_settings() {
	global $bb2_settings_defaults;
	$bb_conf = $bb2_settings_defaults;

	$query = 'SELECT * FROM ' . sql_table('bad_behavior_admin');
	$res = sql_query($query);

	while ($obj = sql_fetch_object($res) ) {
		$bb_conf[$obj->name] = $obj->value;
	}
	
	if (is_file(dirname(__FILE__) . "/settings.ini")) {
		$settings = @parse_ini_file(dirname(__FILE__) . "/settings.ini");
		if (is_array($settings)) 
			return @array_merge($bb_conf, $settings);
		else
			return $bb_conf;
	}
	else {
		return $bb_conf;
	}
}

// write settings to database
function bb2_write_settings($settings) {
	

	$query = "CREATE TABLE IF NOT EXISTS `".sql_table('bad_behavior_admin')."` (";
	$query .= "`name` varchar(20) NOT NULL default '', ";
	$query .= "`value` varchar(128) default NULL, ";
	$query .= "PRIMARY KEY  (`name`)";
	$query .= ") TYPE=MyISAM;";
	sql_query($query);
	
	sql_query("DELETE FROM ".sql_table('bad_behavior_admin'));
	$query = "INSERT INTO ".sql_table('bad_behavior_admin')." VALUES ";
	$j = 0;
	foreach ($settings as $key=>$value) {
		$query .= ($j == 0 ? '' : ', ')."('".sql_real_escape_string($key)."','".sql_real_escape_string($value)."')";
		$j = $j + 1;
	}
	sql_query($query);
	
	
	return;
}

// installation
function bb2_install() {
	$settings = bb2_read_settings();
	bb2_db_query(bb2_table_structure($settings['log_table']));
}

// Cute timer display; screener
function bb2_insert_head() {
	global $bb2_timer_total;
	global $bb2_javascript;
	echo "\n<!-- Bad Behavior " . BB2_VERSION . " run time: " . number_format(1000 * $bb2_timer_total, 3) . " ms -->\n";
	echo $bb2_javascript;
}

// Display stats?
function bb2_insert_stats($force = false) {
	$settings = bb2_read_settings();

    global $CONF;
	if ($force || $settings['display_stats']) {
		//$blocked = bb2_db_query("SELECT COUNT(*) as blocks FROM " . $settings['log_table'] . " WHERE `key` NOT LIKE '00000000'");
        $blocked = sql_num_rows(sql_query("SELECT id FROM " . $settings['log_table'] . " WHERE `key` NOT LIKE '00000000'"));
		if ($blocked !== FALSE) {
            require_once(BB2_CORE . "/responses.inc.php");
			echo sprintf('<p><a href="http://www.bad-behavior.ioerror.us/">%1$s</a> %2$s <strong>%3$s</strong> %4$s</p>', 'Bad Behavior', 'has blocked', $blocked, 'access attempts in the last 7 days.');
            $res = sql_query("SELECT `key`, COUNT(*) FROM " . $settings['log_table'] . " WHERE `key` NOT LIKE '00000000' GROUP BY `key`");
            echo "<table>\n";
            echo "<tr><th>Count</th><th>Key</th><th>Response</th><th>Explanation</th><th>Log</th><th>Details</th></tr>\n";
            while ($row = sql_fetch_assoc($res)) {
                $response = bb2_get_response($row['key']);
                echo "<tr>\n";
                echo "<td>".$row['COUNT(*)']."</td>\n";
                echo "<td>".$row['key']."</td>\n";
                echo "<td>".$response['response']."</td>\n";
                echo "<td>".$response['explanation']."</td>\n";
                echo "<td>".$response['log']."</td>\n";
                echo "<td>\n";
                echo '<form method="post" action="'.$CONF['PluginURL'].'badbehavior/index.php">'."\n";
                echo '<input type="hidden" name="tname" value="'.sql_table('bad_behavior').'" />'."\n";
                echo '<input type="hidden" name="showlist" value="logs" />'."\n";
                echo '<input type="hidden" name="fname" value="key" />'."\n";
                echo '<input type="hidden" name="oname" value="like" />'."\n";
                echo '<input type="hidden" name="iname" value="'.$row['key'].'" />'."\n";
                echo '<input type="submit" value="View" class="formbutton" /></form>'."\n";
                echo "</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
		}
	}
}

// Return the top-level relative path of wherever we are (for cookies)
function bb2_relative_path() {
    global $CONF;
	$url = parse_url($CONF['IndexURL']);
	return $url['path'] . '/';
}

// Calls inward to Bad Behavor itself.
require_once(BB2_CWD . "/bad-behavior/core.inc.php");
bb2_install();	// FIXME: see above

global $member, $CONF;


bb2_start(bb2_read_settings());

$bb2_mtime = explode(" ", microtime());
$bb2_timer_stop = $bb2_mtime[1] + $bb2_mtime[0];
$bb2_timer_total = $bb2_timer_stop - $bb2_timer_start;

?>