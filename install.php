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

$sql = "CREATE TABLE IF NOT EXISTS `webrtc_settings` (
	`key` VARCHAR( 255 ) NOT NULL UNIQUE ,
	`value` TEXT NOT NULL
)";
sql($sql);

$sql = "CREATE TABLE IF NOT EXISTS `webrtc_clients` (
  `user` VARCHAR( 255 ) NOT NULL UNIQUE, 
  `device` VARCHAR( 255 ) NOT NULL UNIQUE ,
  `realm` varchar(80) NOT NULL,
  `username` varchar(80) NOT NULL,
  `sipuri` varchar(80) NOT NULL,
  `password` varchar(80) NOT NULL,
  `websocket` varchar(80) NOT NULL,
  `breaker` varchar(80) NOT NULL,
  `cid` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
)";
sql($sql);

if (!$db->getOne("SELECT value FROM webrtc_settings WHERE `key` = 'device_prefix'")) {
	sql("INSERT INTO webrtc_settings (`key`, `value`) VALUES('device_prefix','99')");
}

//Remove Old Link if need be
if(file_exists($amp_conf['ASTETCDIR'].'/http.conf') && is_link($amp_conf['ASTETCDIR'].'/http.conf') && (readlink($amp_conf['ASTETCDIR'].'/http.conf') == dirname(__FILE__).'/etc/httpd.conf')) {
	unlink($amp_conf['ASTETCDIR'].'/http.conf');
}

$freepbx_conf =& freepbx_conf::create();

if($freepbx_conf->conf_setting_exists('HTTPENABLED')) {
	$freepbx_conf->set_conf_values(array('HTTPENABLED' => true),true);
}
