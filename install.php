<?php
/* $Id$ */
global $db;
global $amp_conf;

out(_("Installing WebRTC"));
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

$sql = "CREATE TABLE `webrtc` (
  `realm` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `sipuri` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `websocket` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `breaker` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `cid` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
);";

out(_("Installing Database!"));

$check = $db->query($sql);
if (DB::IsError($check)) {
        die_freepbx( "Can not create webrtc table: " . $check->getMessage() .  "\n");
}
