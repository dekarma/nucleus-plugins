<?php

   /* ==========================================================================================
    * AkismetStatistics for Nucleus CMS
    * Copyright 2005-2007, Niels Leenheer
    * ==========================================================================================
    * This program is free software and open source software; you can redistribute
    * it and/or modify it under the terms of the GNU General Public License as
    * published by the Free Software Foundation; either version 2 of the License,
    * or (at your option) any later version.
    *
    * This program is distributed in the hope that it will be useful, but WITHOUT
    * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
    * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
    * more details.
    *
    * You should have received a copy of the GNU General Public License along
    * with this program; if not, write to the Free Software Foundation, Inc.,
    * 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
    * http://www.gnu.org/licenses/gpl.html
    * ==========================================================================================
    */

class NP_AkismetStatistics extends NucleusPlugin {
	function getName()			{ return 'AkismetStatistics'; }
	function getAuthor()  	  	{ return 'Niels Leenheer'; }
	function getURL()  	  		{ return 'http://www.rakaz.nl'; }
	function getVersion() 	  	{ return '0.2'; }
	function getDescription() 	{ return 'Store statistics about the Akismet plugin'; }
	
	function supportsFeature($what) {
		switch($what) {
		    case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	
	function getEventList() {
		return array('AkismetResult');
	}

	function install() {
		$this->createOption('DropTable', 'Clear the database when uninstalling','yesno','no');

		@sql_query('
			CREATE TABLE 
				' . sql_table('plug_akismet_statistics') . ' 
			(
				id int(11),
				day date,
				status int(11),
				count int(11),
				UNIQUE KEY `id_day_status` (`id`,`day`,`status`)
			)
		');
	}

	function unInstall() {
		if ($this->getOption('DropTable') == 'yes') {
			sql_query('DROP TABLE ' . sql_table('plug_akismet_statistics'));
		}
	}	

	function doSkinVar($skinType, $what = '') {
		
		switch ($what) {
			case 'all':
				$res = sql_query('
					SELECT 
					 	SUM(count) as sum
					FROM
						' . sql_table('plug_akismet_statistics') . '
					WHERE
						status = 1
				');
				
				if ($row = mysql_fetch_array($res))
					echo (int) $row['sum'];
				else
					echo '0';
				
				break;
				
			case 'today':
				$res = sql_query('
					SELECT 
						SUM(count) as sum
					FROM
						' . sql_table('plug_akismet_statistics') . '
					WHERE
						status = 1 AND
						day = NOW()
				');
				
				if ($row = mysql_fetch_array($res))
					echo (int) $row['sum'];
				else
					echo '0';
				
				break;
				
			case 'percentage':
				$res = sql_query('
					SELECT 
						SUM(count) as sum
					FROM
						' . sql_table('plug_akismet_statistics') . '
					WHERE
						status = 1
				');
				
				if ($row = mysql_fetch_array($res))
				{
					$spam = (int) $row['sum'];
					
					$res = sql_query('
						SELECT 
							SUM(count) as sum
						FROM
							' . sql_table('plug_akismet_statistics') . '
						WHERE
							status = 0
					');
					
					if ($row = mysql_fetch_array($res))
					{
						$ham = (int) $row['sum'];
						
						echo round ( ($spam / ($ham + $spam)) * 100 ) . '%';
					}
					else
					{
						echo '100%';
					}
				}
				else
				{
					echo '0%';
				}
				
				break;				
		}
	}
	
	function event_AkismetResult(&$data) {
		
		$res = sql_query('
			SELECT 
				* 
			FROM 
				' . sql_table('plug_akismet_statistics') . '
			WHERE
				id = ' . addslashes($data['id']) . ' AND
				day = NOW() AND
				status = ' . (int) $data['status'] . '
		');
		
		if ($row = mysql_fetch_array($res)) 
		{
			sql_query('
				UPDATE
					' . sql_table('plug_akismet_statistics') . ' 
				SET
					count = count + 1
				WHERE
					id = ' . addslashes($data['id']) . ' AND
					day = NOW() AND
					status = ' . (int) $data['status'] . '
			');	
		} 
		else 
		{
			sql_query('
				INSERT INTO 
					' . sql_table('plug_akismet_statistics') . ' 
				SET
					id = ' . addslashes($data['id']) . ',
					day = NOW(),
					status = ' . (int) $data['status'] . ',
					count = 1
			');	
		}		
	}
}

?>