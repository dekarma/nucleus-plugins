<?
/*
  History
    v0.3 - initial release
    v0.30 - add configuable ping functions
    v0.31 - fix ping on draft bug
    v0.32 - fix fatal error when running with NP_Postman
    v0.5 - ping when draft is published
         - fix fatal error when running with NP_Postman (again...)
    v0.6 - increase timeout to 30 sec
    v0.7 - fix Technorati ping
 */

class NP_PingPong extends NucleusPlugin {

	function getName() { return 'PingPong'; }

	function getAuthor() { return 'Anand | admun (Edmond Hui) | and others'; }
	function getURL()    { return 'http://tamizhan.com/'; }
	function getVersion() { return '0.7'; }

	/* We're using PostUpdateItem event now, which is introduced in 3.22 */
	function getMinNucleusVersion() { return '322'; }

	function getDescription() {
		return 'This plugin can be used to ping many blog tracking services';
	}

	function getEventList() {
		return array('PreAddItem','PostAddItem','PreUpdateItem', 'PostUpdateItem');
	}

	function event_PreUpdateItem($data) {
		/* It will be better if we know whether it's a draft from $data.... 
		   no need to do 2 queries here and PostUpdateItem below. 
		   We can store the draft info from PrepareItemForEdit and
		   check here to see if we need to ping. */
		$result = mysql_query("SELECT idraft FROM ".sql_table('item')." WHERE inumber=".$data['itemid']);
		$row = mysql_fetch_object($result);
		$this->preDraft = $row->idraft;
		$this->myBlogId    = $data['blog']->blogid;
	}

	function event_PostUpdateItem($data) {
		$result = mysql_query("SELECT idraft FROM ".sql_table('item')." WHERE inumber=".$data['itemid']);
		$row = mysql_fetch_object($result);
		$postdraft = $row->idraft;
		if ($this->preDraft == 1 && $postdraft == 0) {
			$this->sendPings();
		}
	}

	function event_PreAddItem($data) {
		$this->myBlogId    = $data['blog']->blogid;
		$this->myPostTitle = $data['title'];
		$this->draft       = $data['draft'];
	}

	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function install() {
		$this->createOption('pingpong_pingomatic','Ping-o-matic','yesno','yes');  // Default, http://ping

		$this->createOption('pingpong_blogs','Blo.gs','yesno','no'); // http://blo.gs
		$this->createOption('pingpong_euroweblog','EuroWeblog','yesno','no'); // http://euroweblog.com
		$this->createOption('pingpong_weblogues','Weblogues','yesno','no'); // http://weblogues.com/
		$this->createOption('pingpong_bloggde',"Blogg.de",'yesno','no'); // http://blogg.de
		$this->createOption('pingpong_technorati',"Technorati",'yesno','no'); // http://www.technorati.com
		$this->createOption('pingpong_blogrolling',"Blogrolling",'yesno','no'); // http://www.blogrolling.com
	}

	function pingBloGs() {
		if ($this->draft == 1) return;
		$b = new BLOG($this->myBlogId);

		$message = new xmlrpcmsg(
				'weblogUpdates.extendedPing', array(
					new xmlrpcval($b->getName(),'string'),
					new xmlrpcval($b->getURL(),'string'),
					));

		$c = new xmlrpc_client('/', 'ping.blo.gs', 80);

		//$c->setDebug(1);

		$r = $c->send($message,30); // 30 seconds timeout...    
	} 

	function pingEuroWeblog() {
		if ($this->draft == 1) return;
		$b = new BLOG($this->myBlogId);
		$message = new xmlrpcmsg(
				'weblogUpdates.ping', array(
					new xmlrpcval($b->getName(),'string'),
					new xmlrpcval($b->getURL(),'string'),
					));

		$c = new xmlrpc_client('/RPC2', 'rcs.datashed.net', 80);

		//$c->setDebug(1);

		$r = $c->send($message,30); // 30 seconds timeout...    
	}

	function pingWebloguesDotCom() {
		if ($this->draft == 1) return;
		$b = new BLOG($this->myBlogId);

		$message = new xmlrpcmsg(
				'weblogUpdates.extendedPing',
				array(
					new xmlrpcval($b->getName(),'string'), // your blog title
					new xmlrpcval($b->getURL(),'string'),  // your blog url
					));

		$c = new xmlrpc_client('/RPC/', 'www.weblogues.com', 80);

		//$c->setDebug(1);

		$r = $c->send($message,30); // 30 seconds timeout...     
	}

	function pingPing() {
		if ($this->draft == 1) return;
		$b = new BLOG($this->myBlogId);
		$message = new xmlrpcmsg(
				'weblogUpdates.ping', array(
					new xmlrpcval($b->getName(),'string'),
					new xmlrpcval($b->getURL(),'string'),
					));

		$c = new xmlrpc_client('/', 'rpc.pingomatic.com', 80);

		//$c->setDebug(1);

		$r = $c->send($message,30); // 30 seconds timeout...
	}

	function pingBloggDe() {
		if ($this->draft == 1) return;
		$b = new BLOG($this->myBlogId);
		$message = new xmlrpcmsg(
				'bloggUpdates.ping', array(
					new xmlrpcval($b->getName(),'string'),
					new xmlrpcval($b->getURL(),'string'),
					));
		$c = new xmlrpc_client('/', 'xmlrpc.blogg.de', 80);
		//$c->setDebug(1);
		$r = $c->send($message,30); // 30 seconds timeout...   
	} 

	function pingTechnorati() {
		if ($this->draft == 1) return;
		$b = new BLOG($this->myBlogId);
		$message = new xmlrpcmsg(
				'weblogUpdates.ping', array(
					new xmlrpcval($b->getName(),'string'),
					new xmlrpcval($b->getURL(),'string'),
					));
		$c = new xmlrpc_client('/rpc/ping/', 'rpc.technorati.com', 80);
		//$c->setDebug(1);
		$r = $c->send($message,30); // 30 seconds timeout...
	}

	function pingBlogRollingDotCom() {
		if ($this->draft == 1) return;
		$b = new BLOG($this->myBlogId);         
		$message = new xmlrpcmsg(
				'weblogUpdates.ping',
				array(
					new xmlrpcval($b->getName(),'string'), // your blog title
					new xmlrpcval($b->getURL(),'string'),  // your blog url
					new xmlrpcval('changesurl='+$this->altUrl,'string'),  //  alternative url       
					new xmlrpcval('categoryname=\"'+$this->cat+'\"','string'))); // your category name       
		$c = new xmlrpc_client('/pinger/', 'rpc.blogrolling.com', 80);
		//$c->setDebug(1);
		$r = $c->send($message,30); // 30 seconds timeout...     
	} 

	function sendPings() {
		if (!class_exists('xmlrpcmsg')) {
			global $DIR_LIBS;
			include($DIR_LIBS . 'xmlrpc.inc.php');
		}
		if ($this->getOption('pingpong_pingomatic')=='yes') $this->pingPing();
		if ($this->getOption('pingpong_blogs')=='yes') $this->pingBloGs();
		if ($this->getOption('pingpong_euroweblog')=='yes') $this->pingEuroWeblog();
		if ($this->getOption('pingpong_weblogues')=='yes') $this->pingWebloguesDotCom();
		if ($this->getOption('pingpong_bloggde')=='yes') $this->pingBloggDe();
		if ($this->getOption('pingpong_technorati')=='yes') $this->pingTechnorati();
		if ($this->getOption('pingpong_blogrolling')=='yes') $this->pingBlogRollingDotCom();
	}

	function event_PostAddItem($data) {
		//$this->sendPings();
		if (!class_exists('xmlrpcmsg')) {
			global $DIR_LIBS;
			include($DIR_LIBS . 'xmlrpc.inc.php');
		}
		if ($this->getOption('pingpong_pingomatic')=='yes') $this->pingPing();
		if ($this->getOption('pingpong_blogs')=='yes') $this->pingBloGs();
		if ($this->getOption('pingpong_euroweblog')=='yes') $this->pingEuroWeblog();
		if ($this->getOption('pingpong_weblogues')=='yes') $this->pingWebloguesDotCom();
		if ($this->getOption('pingpong_bloggde')=='yes') $this->pingBloggDe();
		if ($this->getOption('pingpong_technorati')=='yes') $this->pingTechnorati();
		if ($this->getOption('pingpong_blogrolling')=='yes') $this->pingBlogRollingDotCom();
	}

}
?>
