<?php

class NP_SitemapExporter extends NucleusPlugin {

   /* ==========================================================================================
	* SitemapExporter for Nucleus
	*
	* Copyright 2005-2007 by Niels Leenheer
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


	function getName() {
		return 'SitemapExporter';
	}

	function getAuthor()  {
		return 'Niels Leenheer';
	}

	function getURL() {
		return 'http://www.rakaz.nl/nucleus/extra/plugins';
	}

	function getVersion() {
		return '0.4';
	}

	function getDescription() {
		return 'This plugin provides a sitemap for your website. Google Sitemap URL: ' . $this->_sitemapURL('google') . ', Yahoo! Sitemap URL: ' . $this->_sitemapURL('yahoo');
	}
	
	function getEventList() {
		return array('PostAddItem');
	}
	
	function supportsFeature($feature) {
    	switch($feature) {
	        case 'SqlTablePrefix':
	        	return 1;
	        default:
	    		return 0;
		}
	}

	function doAction($type)
	{
		global $CONF, $manager;

		if ($type == 'google' || $type == 'yahoo')
		{
			$sitemap = array();
			
			$blog_res = sql_query('
				SELECT 
					*
				FROM 
					'.sql_table('blog').' 
			');
			
			while ($blog = mysql_fetch_array($blog_res))
			{
				if ($this->getBlogOption($blog['bnumber'], 'IncludeSitemap') == 'yes')
				{
					if ($blog['bnumber'] != $CONF['DefaultBlog']) {
						$sitemap[] = array(
							'loc'   => $this->_prepareLink($blog['bnumber'], createBlogidLink($blog['bnumber'])),
							'priority' => '1.0',
							'changefreq' => 'daily'
						);
					}
					else
					{
						$sitemap[] = array(
							'loc'   => $blog['burl'],
							'priority' => '1.0',
							'changefreq' => 'daily'
						);
					}
					
					$cat_res = sql_query('
						SELECT
							*
						FROM
							'.sql_table('category').'
						WHERE
							cblog = '.$blog['bnumber'].'
						ORDER BY
							catid
					');
					
					while ($cat = mysql_fetch_array($cat_res))
					{
						$sitemap[] = array(
							'loc' => $this->_prepareLink($blog['bnumber'], createCategoryLink($cat['catid'])),
							'priority' => '1.0',
							'changefreq' => 'daily'
						);
					}
					
					$item_res = sql_query('
						SELECT 
							*,
							UNIX_TIMESTAMP(itime) AS timestamp
						FROM 
							'.sql_table('item').' 
						WHERE
							iblog = '.$blog['bnumber'].' AND
							idraft = 0
						ORDER BY 
							inumber DESC
					');
					
					while ($item = mysql_fetch_array($item_res))
					{
						$tz = date('O', $item['timestamp']);
						$tz = substr($tz, 0, 3) . ':' . substr($tz, 3, 2);	
						
						if (time() - $item['timestamp'] < 86400 * 2)
							$fq = 'hourly';
						elseif (time() - $item['timestamp'] < 86400 * 14)
							$fq = 'daily'; 
						elseif (time() - $item['timestamp'] < 86400 * 62)
							$fq = 'weekly';
						else
							$fq = 'monthly';
						
						$sitemap[] = array(
							'loc' => $this->_prepareLink($blog['bnumber'], createItemLink($item['inumber'])),
							'lastmod' => gmdate('Y-m-d\TH:i:s', $item['timestamp']) . $tz,
							'priority' => '1.0',
							'changefreq' => $fq
						);
					}
				}
			}		
			
			$manager->notify('SiteMap', array ('sitemap' => & $sitemap));
			
			if ($type == 'google')
			{
				header ("Content-type: application/xml");
				echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";
				echo "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' ";
				echo "xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ";
				echo "xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'>\n";
				
				while (list(,$url) = each($sitemap))
				{
					echo "\t<url>\n";
					
					while (list($key,$value) = each($url))
					{
						echo "\t\t<" . $key . ">" . htmlspecialchars($value, ENT_QUOTES) . "</" . $key . ">\n";
					}
					
					echo "\t</url>\n";
				}
				
				echo "</urlset>\n";
			}
			else
			{
				header ("Content-type: text/plain");
				while (list(,$url) = each($sitemap))
				{
					echo $url['loc'] . "\n";
				}
			}
		}
	}
	
	function _prepareLink($blogid, $url) {
		global $manager, $CONF;
		
		if (substr($url, 0, 7) == 'http://')
		{
			return $url;
		}
		else
		{
			$b = & $manager->getBlog($blogid);
			
			if (substr($url, 0, 11) == '/action.php')
				$url = substr($url, 11);
			
			if ($CONF['URLMode'] == 'pathinfo')
				return $b->getURL() . substr($url, 1);
			else
				return $b->getURL() . ($CONF['Self'] == '' ? 'index.php' : $CONF['Self']) . $url;
		}
	}
	
	function _sitemapURL($type = 'google') {
		global $CONF;
		
		if ($type == 'google' && $this->getOption('GoogleSitemapURL') != '')
			return $this->getOption('GoogleSitemapURL');
		elseif ($type == 'yahoo' && $this->getOption('YahooSitemapURL') != '')
			return $this->getOption('YahooSitemapURL');
		else
			return $CONF['ActionURL'] . '?action=plugin&name=SitemapExporter&type=' . $type;
	}
	
	function event_PostAddItem(&$data) {
		if ($this->getOption('PingGoogle') == 'yes')
		{
			$url = 'http://www.google.com/webmasters/sitemaps/ping?sitemap=' . 
				   urlencode($this->_sitemapURL());
			
			$fp = @fopen($url, 'r');
			@fclose($fp);
		}
	}

	function install() {
		$this->createOption('PingGoogle', 'Ping Google after adding a new item', 'yesno', 'yes');
		$this->createOption('GoogleSitemapURL', 'Alternative Google Sitemap URL', 'text', '');
		$this->createOption('YahooSitemapURL', 'Alternative Yahoo! Sitemap URL', 'text', '');
		$this->createBlogOption('IncludeSitemap', 'Include this blog in the Sitemap Exporter', 'yesno', 'yes');
	}
}		


?>