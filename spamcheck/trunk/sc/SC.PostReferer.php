<?php

//
// PostReferer SC
// by:    Legolas
// web:   http://www.legolasweb.nl/
// email: legolas@legolasweb.nl
//
// Let's users only post if the referer is on the same host.
//

function SCType_PostReferer() {
	return "referer";
}

function SC_PostReferer($url) {
	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST)) {
		$parts = parse_url($url);
		$ref = $parts["host"];
		if ($_SERVER["SERVER_NAME"] != $ref) {
			return true;
		}
	}
	return false;
}

?>