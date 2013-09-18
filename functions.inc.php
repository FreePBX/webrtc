<?php

function webrtc_get_config($engine) {
	global $db;
	global $amp_conf;
	global $ext;  // is this the best way to pass this?
	global $asterisk_conf;
	global $core_conf;
	global $version;

	switch($engine) {
		case "asterisk":
			$core_conf->addRtpAdditional('general', array("icesupport" => "yes"));
			break;
	}
}