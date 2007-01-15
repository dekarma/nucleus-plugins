<?php

   /* ==========================================================================================
    * MultiRBL for Nucleus CMS
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

class NP_MultiRBL extends NucleusPlugin {
	function getName() 			{ return 'MultiRBL'; }
	function getAuthor()  	  	{ return 'Niels Leenheer'; }
	function getURL()  			{ return 'http://rakaz.nl'; }
	function getVersion() 	  	{ return '1.0'; }
	function getDescription() 	{ return 'Check for spam with multiple RBLs';	}
	
	function supportsFeature($what) {
		switch($what) {
		    case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	
	function getEventList() {
		return array('SpamCheck', 'SpamPlugin');
	}


	var $message = '';
	
	function event_SpamPlugin(&$data) {
		$data['spamplugin'][] = array (
			'name' => $this->getName(),
			'version' => $this->getVersion()
		);
	}

	function event_SpamCheck(&$data) {
		global $CONF, $manager;
		
		if (isset($data['spamcheck']['result']) && $data['spamcheck']['result'] == true) 
			return;
		
		if ($data['spamcheck']['type'] == 'comment')
		{
			$spam = false;
			
			$urls = array();
			
			if (isset($data['spamcheck']['email']))
				$urls = array_merge($urls, $this->_extractDomain($data['spamcheck']['email']));

			if (isset($data['spamcheck']['url']))
				$urls = array_merge($urls, $this->_extractDomain($data['spamcheck']['url']));

			if (isset($data['spamcheck']['body']))
				$urls = array_merge($urls, $this->_extractDomain($data['spamcheck']['body']));
	
			$urls = array_unique($urls);
			
			while (list(,$url) = each ($urls)) {
				$spam = $spam || $this->_checkDomain($url);
			}
			
			if (isset($data['spamcheck']['live']) && $data['spamcheck']['live'] == true) {
				$spam = $spam || $this->_checkIP($_SERVER['REMOTE_ADDR']);
			}
			
			$data['spamcheck']['result'] = $spam;
			
			if ($spam) {
				$data['spamcheck']['plugin'] = $this->getName();
				$data['spamcheck']['message'] = $this->message;
			}
		}
		
		if ($data['spamcheck']['type'] == 'trackback')
		{
			$spam = false;
			
			$urls = array();
			$urls = array_merge($urls, $this->_extractDomain($data['spamcheck']['url']));
			$urls = array_merge($urls, $this->_extractDomain($data['spamcheck']['excerpt']));
			$urls = array_unique($urls);
			
			while (list(,$url) = each ($urls)) {
				$spam = $spam || $this->_checkDomain($url);
			}
			
			if (isset($data['spamcheck']['live']) && $data['spamcheck']['live'] == true) {
				$spam = $spam || $this->_checkIP($_SERVER['REMOTE_ADDR']);
			}
			
			$data['spamcheck']['result'] = $spam;
			
			if ($spam) {
				$data['spamcheck']['plugin'] = $this->getName();
				$data['spamcheck']['message'] = $this->message;
			}
		}
		
		if ($data['spamcheck']['type'] == 'referer')
		{
			$spam = false;
			
			$urls = $this->_extractDomain($data['spamcheck']['url']);
			
			while (list(,$url) = each ($urls)) {
				$spam = $spam || $this->_checkDomain($url);
			}
			
			if (isset($data['spamcheck']['live']) && $data['spamcheck']['live'] == true) {
				$spam = $spam || $this->_checkIP($_SERVER['REMOTE_ADDR']);
			}
			
			$data['spamcheck']['result'] = $spam;
			
			if ($spam) {
				$data['spamcheck']['plugin'] = $this->getName();
				$data['spamcheck']['message'] = $this->message;
			}
		}
	}
	
	function _checkIP($ip) {
		$reverse = implode('.', array_reverse(explode('.', $ip)));
		
		$rbls = array('bsb.spamlookup.net', 'opm.blitzed.org', 'list.dsbl.org'); // bsb.empty.us
		
		foreach ($rbls as $rbl) {
			if (strstr (gethostbyname ($reverse . '.' . $rbl), '127.0.0')) {
				$this->message = 'Was send through an open proxy according to ' . ucfirst($rbl);
				return true;
			}
		}
		
		return false;
	}
	
	function _checkDomain($domain) {
		$rbls = array('bsb.spamlookup.net', 'multi.surbl.org', 'rbl.bulkfeeds.jp');
		
		foreach($rbls as $rbl) {
			if (strlen($domain) > 3) {
				if (strstr (gethostbyname ($domain . '.' . $rbl), '127.0.0')) {
					$this->message = 'Contains spamvertised URL according to ' . ucfirst($rbl);
					return true;
				}
			}
		}
		
		return false;
	}
	
	function _extractDomain ($string) {
        preg_match_all("/((http:\/\/)|(www\.))([^\/\"<\s]*)/im", $string, $matches);
      
		if (isset($matches[4]) && count($matches[4]))
		{
			$matches = $matches[4];
			
			while (list($key,) = each($matches))
				$matches[$key] = $this->_shortenDomain($matches[$key]);
			
			$matches = array_unique ($matches);
			return $matches;
		}
		
		return array();
	}
        
    function _shortenDomain ($string) {
            
		// http://spamcheck.freeapp.net/two-level-tlds
		$domains = array (
			'com.ac', 'edu.ac', 'gov.ac', 'net.ac', 'mil.ac', 'org.ac', 'com.ae', 'net.ae', 
			'org.ae', 'gov.ae', 'ac.ae', 'co.ae', 'sch.ae', 'pro.ae', 'com.ai', 'org.ai', 
			'edu.ai', 'gov.ai', 'com.ar', 'net.ar', 'org.ar', 'gov.ar', 'mil.ar', 'edu.ar', 
			'int.ar', 'co.at', 'ac.at', 'or.at', 'gv.at', 'priv.at', 'com.au', 'gov.au', 
			'org.au', 'edu.au', 'id.au', 'oz.au', 'info.au', 'net.au', 'asn.au', 'csiro.au', 
			'telememo.au', 'conf.au', 'otc.au', 'com.az', 'net.az', 'org.az', 'com.bb', 'net.bb', 
			'org.bb', 'ac.be', 'belgie.be', 'dns.be', 'fgov.be', 'com.bh', 'gov.bh', 'net.bh', 
			'edu.bh', 'org.bh', 'com.bm', 'edu.bm', 'gov.bm', 'org.bm', 'net.bm', 'adm.br', 
			'adv.br', 'agr.br', 'am.br', 'arq.br', 'art.br', 'ato.br', 'bio.br', 'bmd.br', 
			'cim.br', 'cng.br', 'cnt.br', 'com.br', 'coop.br', 'ecn.br', 'edu.br', 'eng.br', 
			'esp.br', 'etc.br', 'eti.br', 'far.br', 'fm.br', 'fnd.br', 'fot.br', 'fst.br', 
			'g12.br', 'ggf.br', 'gov.br', 'imb.br', 'ind.br', 'inf.br', 'jor.br', 'lel.br', 
			'mat.br', 'med.br', 'mil.br', 'mus.br', 'net.br', 'nom.br', 'not.br', 'ntr.br', 
			'odo.br', 'org.br', 'ppg.br', 'pro.br', 'psc.br', 'psi.br', 'qsl.br', 'rec.br', 
			'slg.br', 'srv.br', 'tmp.br', 'trd.br', 'tur.br', 'tv.br', 'vet.br', 'zlg.br', 
			'com.bs', 'net.bs', 'org.bs', 'ab.ca', 'bc.ca', 'mb.ca', 'nb.ca', 'nf.ca', 
			'nl.ca', 'ns.ca', 'nt.ca', 'nu.ca', 'on.ca', 'pe.ca', 'qc.ca', 'sk.ca', 
			'yk.ca', 'gc.ca', 'co.ck', 'net.ck', 'org.ck', 'edu.ck', 'gov.ck', 'com.cn', 
			'edu.cn', 'gov.cn', 'net.cn', 'org.cn', 'ac.cn', 'ah.cn', 'bj.cn', 'cq.cn', 
			'gd.cn', 'gs.cn', 'gx.cn', 'gz.cn', 'hb.cn', 'he.cn', 'hi.cn', 'hk.cn', 
			'hl.cn', 'hn.cn', 'jl.cn', 'js.cn', 'ln.cn', 'mo.cn', 'nm.cn', 'nx.cn', 
			'qh.cn', 'sc.cn', 'sn.cn', 'sh.cn', 'sx.cn', 'tj.cn', 'tw.cn', 'xj.cn', 
			'xz.cn', 'yn.cn', 'zj.cn', 'arts.co', 'com.co', 'edu.co', 'firm.co', 'gov.co', 
			'info.co', 'int.co', 'nom.co', 'mil.co', 'org.co', 'rec.co', 'store.co', 'web.co', 
			'ac.cr', 'co.cr', 'ed.cr', 'fi.cr', 'go.cr', 'or.cr', 'sa.cr', 'com.cu', 
			'net.cu', 'org.cu', 'ac.cy', 'com.cy', 'gov.cy', 'net.cy', 'org.cy', 'co.dk', 
			'art.do', 'com.do', 'edu.do', 'gov.do', 'gob.do', 'org.do', 'mil.do', 'net.do', 
			'sld.do', 'web.do', 'com.dz', 'org.dz', 'net.dz', 'gov.dz', 'edu.dz', 'ass.dz', 
			'pol.dz', 'art.dz', 'com.ec', 'k12.ec', 'edu.ec', 'fin.ec', 'med.ec', 'gov.ec', 
			'mil.ec', 'org.ec', 'net.ec', 'com.ee', 'pri.ee', 'fie.ee', 'org.ee', 'med.ee', 
			'com.eg', 'edu.eg', 'eun.eg', 'gov.eg', 'net.eg', 'org.eg', 'sci.eg', 'com.er', 
			'net.er', 'org.er', 'edu.er', 'mil.er', 'gov.er', 'ind.er', 'com.es', 'org.es', 
			'gob.es', 'edu.es', 'nom.es', 'com.et', 'gov.et', 'org.et', 'edu.et', 'net.et', 
			'biz.et', 'name.et', 'info.et', 'ac.fj', 'com.fj', 'gov.fj', 'id.fj', 'org.fj', 
			'school.fj', 'com.fk', 'ac.fk', 'gov.fk', 'net.fk', 'nom.fk', 'org.fk', 'asso.fr', 
			'nom.fr', 'barreau.fr', 'com.fr', 'prd.fr', 'presse.fr', 'tm.fr', 'aeroport.fr', 'assedic.fr', 
			'avocat.fr', 'avoues.fr', 'cci.fr', 'chambagri.fr', 'chirurgiens-dentistes.fr', 'experts-comptables.fr', 'geometre-expert.fr', 'gouv.fr', 
			'greta.fr', 'huissier-justice.fr', 'medecin.fr', 'notaires.fr', 'pharmacien.fr', 'port.fr', 'veterinaire.fr', 'com.ge', 
			'edu.ge', 'gov.ge', 'mil.ge', 'net.ge', 'org.ge', 'pvt.ge', 'co.gg', 'org.gg', 
			'sch.gg', 'ac.gg', 'gov.gg', 'ltd.gg', 'ind.gg', 'net.gg', 'alderney.gg', 'guernsey.gg', 
			'sark.gg', 'com.gt', 'edu.gt', 'net.gt', 'gob.gt', 'org.gt', 'mil.gt', 'ind.gt', 
			'com.gu', 'edu.gu', 'net.gu', 'org.gu', 'gov.gu', 'mil.gu', 'com.hk', 'net.hk', 
			'org.hk', 'idv.hk', 'gov.hk', 'edu.hk', 'co.hu', '2000.hu', 'erotika.hu', 'jogasz.hu', 
			'sex.hu', 'video.hu', 'info.hu', 'agrar.hu', 'film.hu', 'konyvelo.hu', 'shop.hu', 'org.hu', 
			'bolt.hu', 'forum.hu', 'lakas.hu', 'suli.hu', 'priv.hu', 'casino.hu', 'games.hu', 'media.hu', 
			'szex.hu', 'sport.hu', 'city.hu', 'hotel.hu', 'news.hu', 'tozsde.hu', 'tm.hu', 'erotica.hu', 
			'ingatlan.hu', 'reklam.hu', 'utazas.hu', 'ac.id', 'co.id', 'go.id', 'mil.id', 'net.id', 
			'or.id', 'co.il', 'net.il', 'org.il', 'ac.il', 'gov.il', 'k12.il', 'muni.il', 
			'idf.il', 'co.im', 'net.im', 'org.im', 'ac.im', 'lkd.co.im', 'gov.im', 'nic.im', 
			'plc.co.im', 'co.in', 'net.in', 'ac.in', 'ernet.in', 'gov.in', 'nic.in', 'res.in', 
			'gen.in', 'firm.in', 'mil.in', 'org.in', 'ind.in', 'ac.je', 'co.je', 'net.je', 
			'org.je', 'gov.je', 'ind.je', 'jersey.je', 'ltd.je', 'sch.je', 'com.jo', 'org.jo', 
			'net.jo', 'gov.jo', 'edu.jo', 'mil.jo', 'ad.jp', 'ac.jp', 'co.jp', 'go.jp', 
			'or.jp', 'ne.jp', 'gr.jp', 'ed.jp', 'lg.jp', 'net.jp', 'org.jp', 'gov.jp', 
			'hokkaido.jp', 'aomori.jp', 'iwate.jp', 'miyagi.jp', 'akita.jp', 'yamagata.jp', 'fukushima.jp', 'ibaraki.jp', 
			'tochigi.jp', 'gunma.jp', 'saitama.jp', 'chiba.jp', 'tokyo.jp', 'kanagawa.jp', 'niigata.jp', 'toyama.jp', 
			'ishikawa.jp', 'fukui.jp', 'yamanashi.jp', 'nagano.jp', 'gifu.jp', 'shizuoka.jp', 'aichi.jp', 'mie.jp', 
			'shiga.jp', 'kyoto.jp', 'osaka.jp', 'hyogo.jp', 'nara.jp', 'wakayama.jp', 'tottori.jp', 'shimane.jp', 
			'okayama.jp', 'hiroshima.jp', 'yamaguchi.jp', 'tokushima.jp', 'kagawa.jp', 'ehime.jp', 'kochi.jp', 'fukuoka.jp', 
			'saga.jp', 'nagasaki.jp', 'kumamoto.jp', 'oita.jp', 'miyazaki.jp', 'kagoshima.jp', 'okinawa.jp', 'sapporo.jp', 
			'sendai.jp', 'yokohama.jp', 'kawasaki.jp', 'nagoya.jp', 'kobe.jp', 'kitakyushu.jp', 'utsunomiya.jp', 'kanazawa.jp', 
			'takamatsu.jp', 'matsuyama.jp', 'com.kh', 'net.kh', 'org.kh', 'per.kh', 'edu.kh', 'gov.kh', 
			'mil.kh', 'ac.kr', 'co.kr', 'go.kr', 'ne.kr', 'or.kr', 'pe.kr', 're.kr', 
			'seoul.kr', 'kyonggi.kr', 'com.kw', 'net.kw', 'org.kw', 'edu.kw', 'gov.kw', 'com.la', 
			'net.la', 'org.la', 'com.lb', 'org.lb', 'net.lb', 'edu.lb', 'gov.lb', 'mil.lb', 
			'com.lc', 'edu.lc', 'gov.lc', 'net.lc', 'org.lc', 'com.lv', 'net.lv', 'org.lv', 
			'edu.lv', 'gov.lv', 'mil.lv', 'id.lv', 'asn.lv', 'conf.lv', 'com.ly', 'net.ly', 
			'org.ly', 'co.ma', 'net.ma', 'org.ma', 'press.ma', 'ac.ma', 'com.mk', 'com.mm', 
			'net.mm', 'org.mm', 'edu.mm', 'gov.mm', 'com.mo', 'net.mo', 'org.mo', 'edu.mo', 
			'gov.mo', 'com.mt', 'net.mt', 'org.mt', 'edu.mt', 'tm.mt', 'uu.mt', 'com.mx', 
			'net.mx', 'org.mx', 'com.my', 'org.my', 'gov.my', 'edu.my', 'net.my', 'com.na', 
			'org.na', 'net.na', 'alt.na', 'edu.na', 'cul.na', 'unam.na', 'telecom.na', 'com.nc', 
			'net.nc', 'org.nc', 'ac.ng', 'edu.ng', 'sch.ng', 'com.ng', 'gov.ng', 'org.ng', 
			'net.ng', 'gob.ni', 'com.ni', 'net.ni', 'edu.ni', 'nom.ni', 'org.ni', 'com.np', 
			'net.np', 'org.np', 'gov.np', 'edu.np', 'ac.nz', 'co.nz', 'cri.nz', 'gen.nz', 
			'geek.nz', 'govt.nz', 'iwi.nz', 'maori.nz', 'mil.nz', 'net.nz', 'org.nz', 'school.nz', 
			'com.om', 'co.om', 'edu.om', 'ac.om', 'gov.om', 'net.om', 'org.om', 'mod.om', 
			'museum.om', 'biz.om', 'pro.om', 'med.om', 'com.pa', 'net.pa', 'org.pa', 'edu.pa', 
			'ac.pa', 'gob.pa', 'sld.pa', 'edu.pe', 'gob.pe', 'nom.pe', 'mil.pe', 'org.pe', 
			'com.pe', 'net.pe', 'com.pg', 'net.pg', 'ac.pg', 'com.ph', 'net.ph', 'org.ph', 
			'mil.ph', 'ngo.ph', 'aid.pl', 'agro.pl', 'atm.pl', 'auto.pl', 'biz.pl', 'com.pl', 
			'edu.pl', 'gmina.pl', 'gsm.pl', 'info.pl', 'mail.pl', 'miasta.pl', 'media.pl', 'mil.pl', 
			'net.pl', 'nieruchomosci.pl', 'nom.pl', 'org.pl', 'pc.pl', 'powiat.pl', 'priv.pl', 'realestate.pl', 
			'rel.pl', 'sex.pl', 'shop.pl', 'sklep.pl', 'sos.pl', 'szkola.pl', 'targi.pl', 'tm.pl', 
			'tourism.pl', 'travel.pl', 'turystyka.pl', 'com.pk', 'net.pk', 'edu.pk', 'org.pk', 'fam.pk', 
			'biz.pk', 'web.pk', 'gov.pk', 'gob.pk', 'gok.pk', 'gon.pk', 'gop.pk', 'gos.pk', 
			'edu.ps', 'gov.ps', 'plo.ps', 'sec.ps', 'com.py', 'net.py', 'org.py', 'edu.py', 
			'com.qa', 'net.qa', 'org.qa', 'edu.qa', 'gov.qa', 'asso.re', 'com.re', 'nom.re', 
			'com.ro', 'org.ro', 'tm.ro', 'nt.ro', 'nom.ro', 'info.ro', 'rec.ro', 'arts.ro', 
			'firm.ro', 'store.ro', 'www.ro', 'com.ru', 'net.ru', 'org.ru', 'gov.ru', 'pp.ru', 
			'com.sa', 'edu.sa', 'sch.sa', 'med.sa', 'gov.sa', 'net.sa', 'org.sa', 'pub.sa', 
			'com.sb', 'net.sb', 'org.sb', 'edu.sb', 'gov.sb', 'com.sd', 'net.sd', 'org.sd', 
			'edu.sd', 'sch.sd', 'med.sd', 'gov.sd', 'tm.se', 'press.se', 'parti.se', 'brand.se', 
			'fh.se', 'fhsk.se', 'fhv.se', 'komforb.se', 'kommunalforbund.se', 'komvux.se', 'lanarb.se', 'lanbib.se', 
			'naturbruksgymn.se', 'sshn.se', 'org.se', 'pp.se', 'com.sg', 'net.sg', 'org.sg', 'edu.sg', 
			'gov.sg', 'per.sg', 'com.sh', 'net.sh', 'org.sh', 'edu.sh', 'gov.sh', 'mil.sh', 
			'gov.st', 'saotome.st', 'principe.st', 'consulado.st', 'embaixada.st', 'org.st', 'edu.st', 'net.st', 
			'com.st', 'store.st', 'mil.st', 'co.st', 'com.sv', 'org.sv', 'edu.sv', 'gob.sv', 
			'red.sv', 'com.sy', 'net.sy', 'org.sy', 'gov.sy', 'ac.th', 'co.th', 'go.th', 
			'net.th', 'or.th', 'com.tn', 'net.tn', 'org.tn', 'edunet.tn', 'gov.tn', 'ens.tn', 
			'fin.tn', 'nat.tn', 'ind.tn', 'info.tn', 'intl.tn', 'rnrt.tn', 'rnu.tn', 'rns.tn', 
			'tourism.tn', 'com.tr', 'net.tr', 'org.tr', 'edu.tr', 'gov.tr', 'mil.tr', 'bbs.tr', 
			'k12.tr', 'gen.tr', 'co.tt', 'com.tt', 'org.tt', 'net.tt', 'biz.tt', 'info.tt', 
			'pro.tt', 'name.tt', 'gov.tt', 'edu.tt', 'nic.tt', 'us.tt', 'uk.tt', 'ca.tt', 
			'eu.tt', 'es.tt', 'fr.tt', 'it.tt', 'se.tt', 'dk.tt', 'be.tt', 'de.tt', 
			'at.tt', 'au.tt', 'co.tv', 'com.tw', 'net.tw', 'org.tw', 'edu.tw', 'idv.tw', 
			'gove.tw', 'com.ua', 'net.ua', 'org.ua', 'edu.ua', 'gov.ua', 'ac.ug', 'co.ug', 
			'or.ug', 'go.ug', 'co.uk', 'me.uk', 'org.uk', 'edu.uk', 'ltd.uk', 'plc.uk', 
			'net.uk', 'sch.uk', 'nic.uk', 'ac.uk', 'gov.uk', 'nhs.uk', 'police.uk', 'mod.uk', 
			'dni.us', 'fed.us', 'com.uy', 'edu.uy', 'net.uy', 'org.uy', 'gub.uy', 'mil.uy', 
			'com.ve', 'net.ve', 'org.ve', 'co.ve', 'edu.ve', 'gov.ve', 'mil.ve', 'arts.ve', 
			'bib.ve', 'firm.ve', 'info.ve', 'int.ve', 'nom.ve', 'rec.ve', 'store.ve', 'tec.ve', 
			'web.ve', 'co.vi', 'net.vi', 'org.vi', 'com.vn', 'biz.vn', 'edu.vn', 'gov.vn', 
			'net.vn', 'org.vn', 'int.vn', 'ac.vn', 'pro.vn', 'info.vn', 'health.vn', 'name.vn', 
			'com.vu', 'edu.vu', 'net.vu', 'org.vu', 'de.vu', 'ch.vu', 'fr.vu', 'com.ws', 
			'net.ws', 'org.ws', 'gov.ws', 'edu.ws', 'ac.yu', 'co.yu', 'edu.yu', 'org.yu', 
			'com.ye', 'net.ye', 'org.ye', 'gov.ye', 'edu.ye', 'mil.ye', 'ac.za', 'alt.za', 
			'bourse.za', 'city.za', 'co.za', 'edu.za', 'gov.za', 'law.za', 'mil.za', 'net.za', 
			'ngo.za', 'nom.za', 'org.za', 'school.za', 'tm.za', 'web.za', 'co.zw', 'ac.zw', 
			'org.zw', 'gov.zw', 'eu.org', 'au.com', 'br.com', 'cn.com', 'de.com', 'de.net', 
			'eu.com', 'gb.com', 'gb.net', 'hu.com', 'no.com', 'qc.com', 'ru.com', 'sa.com', 
			'se.com', 'uk.com', 'uk.net', 'us.com', 'uy.com', 'za.com', 'dk.org', 'tel.no', 
			'fax.nr', 'mob.nr', 'mobil.nr', 'mobile.nr', 'tel.nr', 'tlf.nr', 'e164.arpa'
		);
	   
		$string = trim(strtolower($string));
		
		while (list(,$domain) = each($domains))
		{
			if (substr($string, 0 - strlen($domain)) == $domain)
		        {
			        preg_match('/[a-z0-9\-]+\.'.$domain.'$/', $string, $matches);
			        return $matches[0];
			}
		}
        
		preg_match('/[a-z0-9\-]+\.[a-z0-9\-]+$/', $string, $matches);
		return $matches[0];
    }
}

?>