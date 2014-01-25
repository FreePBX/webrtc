<?php
/* $Id$ */
global $db;
global $amp_conf;

out(_("Uninstalling WebRTC Phone!"));
if (! function_exists("out")) {
	function out($text) {
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

$sql="DROP TABLE webrtc_clients";
out(_("Removing Database!"));
sql($sql);

$sql="DROP TABLE webrtc_settings";
out(_("Removing Database!"));
sql($sql);