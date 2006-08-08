<?php
 
//
// NP_SpamCheck
// By: Legolas
// WWW: http://www.legolasweb.nl
// Email: legolas@legolasweb.nl
//
// Released under GNU GPL
//
// Sub syntax:
//    In directory sc/ (relative to plugin dir)
//    Filename: SC.[name].php
//
// Functions:
//    string SCType_[name](void)
//       Must return check type
//    bool SC_[name](* relative to checktype *)
//
// Actual Checktypes:
//    ip
//       string $ip
//    comment
//       string $body, string $poster
//    referer
//       string $url
//
 
class NP_SpamCheck extends NucleusPlugin {
 
	// name of plugin
	function getName() {
		return 'SpamCheck';
	}
 
	// author of plugin
	function getAuthor()  {
		return 'Legolas (Stas Verberkt)';
	}
 
	// an URL to the plugin website
	function getURL()
	{
		return 'http://www.legolasweb.nl/';
	}
 
	// version of the plugin
	function getVersion() {
		return '1.1.1';
	}
 
	// a description to be shown on the installed plugins listing
	function getDescription() {
		return "This should be the ultimate anty spam solution, let\'s hope that it works =P.";
	}
 
	function getEventList() {
		return array('PreAddComment', 'SpamCheck');
	}

	function install() {
		$this->createOption("hook", "Check comments for spam on posting?", "yesno", "no");
	}
 
	function supportsFeature ($what)
	{
		switch ($what)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
 
	function event_SpamCheck(&$data) {
		global $DIR_PLUGINS, $member;
 
		if ($data["spamcheck"]["result"] == true) {
			return false;
		}
 
		if ($member->isLoggedIn()) {
			return false;
		}
 
		$type = "post";
		if (isset($data["spamcheck"]["type"])) {
			$type = $data["spamcheck"]["type"];
		}
		switch ($type) {
			default:
			case "comment":
				if (!isset($data["spamcheck"]["body"]) || !isset($data["spamcheck"]["author"])) {
					return false;
				}
				$comment = $data["spamcheck"]["body"];
				$poster = $data["spamcheck"]["author"];
				$type = "comment";
				break;
			case "ip":
				if (!isset($data["spamcheck"]["ip"])) {
					return false;
				}
				$ip = $data["spamcheck"]["ip"];
				$type = "ip";
				break;
			case "referer":
				if (!isset($data["spamcheck"]["url"])) {
					return false;
				}
				$url = $data["spamcheck"]["url"];
				$type = "referer";
				break;
		}
 
		$filters_dir = $DIR_PLUGINS . "sc/";
		$filters = array();
		$handle = opendir($filters_dir);
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if (substr(strtolower($file), 0, 3) == "sc." && substr(strtolower($file), -4) == ".php") {
					$filters_dir[] = $file;
				}
			}
		}
		closedir($handle);
 
		$spam = false;
		foreach ($filters as $filter) {
			include_once($filters_dir . "/" . $filter);
			$filter_name = substr($filter, 3, -4);
			$functype = null;
			if (function_exists("SCType_" . $filter_name)) {
				$functype = call_user_func("SCType_" . $filter_name);
			}
			if (function_exists("SC_" . $filter_name) && $functype == $type) {
				switch ($functype) {
					default:
					case "comment":
						$spam = call_user_func("SC_" . $filter_name, $comment, $poster);
						break;
					case "ip":
						$spam = call_user_func("SC_" . $filter_name, $ip);
						break;
					case "referer":
						$spam = call_user_func("SC_" . $filter_name, $url);
						break;
				}
			}
			if ($spam == true) {
				$data["spamcheck"]["result"] = true;
				return true;
			}
		}
 
		return false;
	}
 
	function event_PreAddComment(&$data) {
		global $DIR_PLUGINS, $member;

		if ($this->getOption("hook") == "no") {
			return true;
		}
 
		if ($member->isLoggedIn()) {
			return true;
		}
 
		$poster = $data['comment']['user'];
		$comment = $data['comment']['body'];
		$ip = $data['comment']['ip'];
		$referer = serverVar("HTTP_REFERER");
 
		$spamcheck = array("type" => "comment", "user" => $poster, "body" => $comment);
 
		$this->event_SpamCheck(array("spamcheck" => &$spamcheck));
 
		if (isset($spamcheck["result"]) && $spamcheck["result"] == true) {
			header("Location: " . createItemLink($data['comment']['itemid']));
			exit();
		}
 
		$spamcheck = array("type" => "ip", "ip" => $ip);
 
		$this->event_SpamCheck(array("spamcheck" => &$spamcheck));
 
		if (isset($spamcheck["result"]) && $spamcheck["result"] == true) {
			header("Location: " . createItemLink($data['comment']['itemid']));
			exit();
		}
 
		$spamcheck = array("type" => "referer", "url" => $referer);
 
		$this->event_SpamCheck(array("spamcheck" => &$spamcheck));
 
		if (isset($spamcheck["result"]) && $spamcheck["result"] == true) {
			header("Location: " . createItemLink($data['comment']['itemid']));
			exit();
		}
 
		return true;
	}
}
 
?>