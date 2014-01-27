<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

function webrtc_get_allSettings() {
	$sql = "SELECT * FROM webrtc_settings";
	$results = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
	return $results;
}

function webrtc_get_setting($setting) {
	global $db;
	$setting = $db->escapeSimple($setting);
	$sql = "SELECT value FROM webrtc_settings WHERE `key` = '".$setting."'";
	$results = sql($sql,'getRow',DB_FETCHMODE_ASSOC);
	$results = (!empty($results['value'])) ? $results['value'] : false;
	return $results;
}

function webrtc_set_setting($setting,$value) {
	global $db;
	$setting = $db->escapeSimple($setting);
	$value = $db->escapeSimple($value);
	$sql = "REPLACE INTO webrtc_settings (`key`, `value`) VALUES('".$setting."','".$value."')";
	$results = sql($sql);
	return $results;
}

function webrtc_del_setting($setting) {
	global $db;
	$setting = $db->escapeSimple($setting);
	$sql = "DELETE FROM webrtc_settings WHERE `key` = '".$setting."'";
	$results = sql($sql);
	return $results;
}

function webrtc_get_enabled($extension) {
	$res = webrtc_get_setting('ext_'.$extension);
	$results = (!empty($res)) ? $res : 'no';
	return $results;
}

function webrtc_get_prefix() {
	$prefix = webrtc_get_setting('device_prefix');
	$prefix = !empty($prefix) ? $prefix : '99';
	return $prefix;
}

function webrtc_set_enabled($extension,$mode) {
	global $db;
	$results = webrtc_set_setting('ext_'.$extension, $mode);
	return $results;
}

function webrtc_delete($extension) {
	webrtc_delete_device($extension);
	$results = webrtc_del_setting('ext_'.$extension);
	return $results;
}

function webrtc_delete_device($extension) {
	$prefix = webrtc_get_prefix();
	core_devices_del($prefix.$extension);
	core_devices_delsip($prefix.$extension);
	webrtc_delete_client_settingsByDevice($extension);
}

function webrtc_create_device($extension) {
	$prefix = webrtc_get_prefix();
	$usr = core_users_get($extension);
	$dev = core_devices_get($prefix.$extension);
	if(empty($dev)) {
		$res = $_REQUEST;
		//Override the device page here
		$settings = array('avpf' => 'yes', 'icesupport' => 'yes', 'encryption' => 'yes', 'transport' => 'ws', 'dial' => 'SIP/'.$prefix.$extension);
		foreach($settings as $key => $value) {
			$_REQUEST['devinfo_'.$key] = $value;
		}
		core_devices_add($prefix.$extension,'sip','','fixed',$extension,$usr['name'].' WebRTC Client');
		foreach($settings as $key => $value) {
			$_REQUEST['devinfo_'.$key] = $res['devinfo_'.$key];
		}
		webrtc_set_client_settings($usr['extension'],$extension);
	}
}

function webrtc_set_client_settings($user,$device) {
	$prefix = webrtc_get_prefix();
	$sql = "REPLACE INTO webrtc_clients (`user`, `device`) VALUES('".$user."','".$prefix.$device."')";
	$results = sql($sql);
	return $results;
}

function webrtc_get_client_settingsByUser($user) {
	global $db;
	$freepbx_conf =& freepbx_conf::create();
	
	$user = $db->escapeSimple($user);
	$sql = "SELECT * FROM webrtc_clients WHERE `user` = '".$user."'";
	$results = sql($sql,'getRow',DB_FETCHMODE_ASSOC);
	if(empty($results)) {
		return false;
	}
	$sip_server = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR'];
	$dev = core_devices_get($results['device']);
	$usr = core_users_get($results['user']);
	$results['realm'] = !empty($results['realm']) ? $results['realm'] : $sip_server;
	$results['username'] = !empty($results['username']) ? $results['username'] : $dev['id'];
	$results['sipuri'] = !empty($results['sipuri']) ? $results['sipuri'] : 'sip:'.$results['username'].'@'.$sip_server;
	$results['password'] = !empty($results['password']) ? $results['password'] : $dev['secret'];
	$prefix = $freepbx_conf->get_conf_setting('HTTPPREFIX');
	$suffix = !empty($prefix) ? "/".$prefix."/ws" : "/ws";
	$results['websocket'] = !empty($results['websocket']) ? $results['websocket'] : 'ws://'.$sip_server.':'.$freepbx_conf->get_conf_setting('HTTPBINDPORT').$suffix;
	$results['breaker'] = !empty($results['breaker']) ? (bool)$results['breaker'] : false;
	$results['cid'] = !empty($results['cid']) ? $results['cid'] : $usr['name'];
	return $results;
}

function webrtc_delete_client_settingsByDevice($extension) {
	$prefix = webrtc_get_prefix();
	$sql = "DELETE FROM webrtc_clients WHERE `device` = '".$prefix.$extension."'";
	$results = sql($sql);
	return $results;
}