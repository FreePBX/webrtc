<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules;
class Webrtc extends \FreePBX_Helpers implements \BMO {

	/**
	 * Device Overrides depending on TECH to enable WebRTC
	 * @type {array}
	 */
	private $overrides = array(
		"sip" => array(
			"transport" => "ws",
			"avpf" => "yes",
			"force_avp" => "yes",
			"icesupport" => "yes",
			"encryption" => "yes"
		),
		"pjsip" => array(
			"media_use_received_transport" => "yes",
			"use_avpf" => "yes",
			"ice_support" => "yes",
			"encryption" => "yes"
		)
	);

	/**
	 * Supported Versions of Asterisk for this module
	 * @type {array}
	 */
	private $supported = array(
		"11" => "11.11.0",
		"12" => "12.4.0"
	);

	/**
	 * Prefix added to all WebRTC Extensions
	 * @type {int}
	 */
	private $prefix = '99';

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new \Exception("Not given a FreePBX Object");
		}
		$this->freepbx = $freepbx;
		$this->core = $this->freepbx->Core;
		$this->db = $this->freepbx->Database;
	}

	public function doConfigPageInit($page) {

	}

	public function install() {
		$status = $this->validVersion();
		if($status !== true) {
			out($status);
			return false;
		}
		$sql = "CREATE TABLE IF NOT EXISTS `webrtc_clients` (
						`user` VARCHAR( 255 ) NOT NULL UNIQUE,
						`device` VARCHAR( 255 ) NOT NULL UNIQUE ,
						`realm` varchar(80) NOT NULL,
						`username` varchar(80) NOT NULL,
						`sipuri` varchar(80) NOT NULL,
						`password` varchar(80) NOT NULL,
						`websocket` varchar(80) NOT NULL,
						`breaker` varchar(80) NOT NULL,
						`cid` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
						`certid` int(11) NULL
					)";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		//Remove Old Link if need be
		if(file_exists($this->freepbx->Config->get('ASTETCDIR').'/http.conf') && is_link($this->freepbx->Config->get('ASTETCDIR').'/http.conf') && (readlink($this->freepbx->Config->get('ASTETCDIR').'/http.conf') == dirname(__FILE__).'/etc/httpd.conf')) {
			unlink($this->freepbx->Config->get('ASTETCDIR').'/http.conf');
		}

		if (!$this->db->sql('SHOW COLUMNS FROM webrtc_clients WHERE FIELD = "certid"','getAll')) {
			$this->db->query("ALTER TABLE `webrtc_clients` ADD COLUMN `certid` int(11) NULL");
		}

		if($this->freepbx->Config->conf_setting_exists('HTTPENABLED')) {
			$this->freepbx->Config->set_conf_values(array('HTTPENABLED' => true),true);
		}

		try {
			$sql = "SELECT * FROM webrtc_settings";
			$sth = $this->db->prepare($sql);
			$sth->execute();
			$settings = $sth->fetchAll(\PDO::FETCH_ASSOC);
			if(!empty($settings)) {
				foreach($settings as $setting) {
					$this->setConfig($setting['key'], $setting['value']);
				}
			}
		} catch(\Exception $e) {}

		$prefix = $this->getConfig('prefix');
		if(empty($prefix)) {
			$this->setConfig('prefix','99');
		}
		$sql = "DROP TABLE IF EXISTS `webrtc_settings`";
		$sth = $this->db->prepare($sql);
		return true;
	}
	public function uninstall() {
		$sql="DROP TABLE webrtc_clients";
		$this->db->sql($sql);
		return true;
	}
	public function backup(){

	}
	public function restore($backup){

	}
	public function genConfig() {
	}

	public function processUCPAdminDisplay($user) {
	}

	public function getUCPAdminDisplay($user) {
	}

	public function validVersion() {
		$version = $this->freepbx->Config->get('ASTVERSION');
		$vParts = explode(".",$version);
		$base = $vParts[0];
		if(isset($this->supported[$base])) {
			if(!version_compare($version, $this->supported[$base], "ge")) {
				return sprintf(_("Unsupported Version of Asterisk, You need at least %s"), $this->supported[$base]);
			}
		} else {
			return sprintf(_("Unsupported Version of Asterisk, You need at least %s"), $this->supported["11"]);
		}
		return true;
	}

	public function createWebRTCDevice($extension) {
		$settings = array(
			"dial" => array(
				"value" => "SIP/991004",
				"flag" => 25
			),
			"devicetype" => array(
				"value"	=> "fixed",
			),
			"user" => array(
				"value" => "1004",
			),
			"description" => array(
				"value" => "DisplayName WebRTC Client",
			),
			"emergency_cid" => array(
				"value" => ""
			),
			"sipdriver" => array(
				"value" => "chan_sip",
				"flag" => 2
			),
			"secret_origional" => array(
				"value" => "7f7ac988fbd3d873f63aac7a7f23f211",
				"flag" => 3
			),
			"secret" => array(
				"value" => "7f7ac988fbd3d873f63aac7a7f23f211",
				"flag" => 4
			),
			"dtmfmod" => array(
				"value" => "rfc2833",
				"flag" => 5
			),
			"canreinvite" => array(
				"value" => "no",
				"flag" => 6
			),
			"context" => array(
				"value" => "from-internal",
				"flag" => 7
			),
			"host" => array(
				"value" => "dynamic",
				"flag" => 8
			),
			"trustrpid" => array(
				"value" => "yes",
				"flag" => 9
			),
			"sendrpid" => array(
				"value" => "no",
				"flag" => 10
			),
			"type" => array(
				"value" => "friend",
				"flag" => 11
			),
			"nat" => array(
				"value" => "no",
				"flag" => 12
			),
			"port" => array(
				"value" => 5060,
				"flag" => 13
			),
			"qualify" => array(
				"value" => "yes",
				"flag" => 14
			),
			"qualifyfreq" => array(
				"value" => 60,
				"flag" => 15
			),
			"transport" => array(
				"value" => "ws",
				"flag" => 16
			),
			"avpf" => array(
				"value" => "yes",
				"flag" => 17
			),
			"force_avp" => array(
				"value" => "yes",
				"flag" => 18
			),
			"icesupport" => array(
				"value" => "yes",
				"flag" => 19
			),
			"encryption" => array(
				"value" => "yes",
				"flag" => 20
			),
		);

/*
    [callgroup] => Array
        (
            [value] =>
            [flag] => 21
        )

    [pickupgroup] => Array
        (
            [value] =>
            [flag] => 22
        )

    [disallow] => Array
        (
            [value] =>
            [flag] => 23
        )

    [allow] => Array
        (
            [value] =>
            [flag] => 24
        )

    [accountcode] => Array
        (
            [value] =>
            [flag] => 26
        )

    [mailbox] => Array
        (
            [value] => 1004@device
            [flag] => 27
        )

    [deny] => Array
        (
            [value] => 0.0.0.0/0.0.0.0
            [flag] => 28
        )

    [permit] => Array
        (
            [value] => 0.0.0.0/0.0.0.0
            [flag] => 29
        )

    [account] => Array
        (
            [value] => 991004
            [flag] => 30
        )

    [callerid] => Array
        (
            [value] => DisplayName WebRTC Client <991004>
            [flag] => 31
        )
*/
		$usr = core_users_get($extension);
		$dev = $this->core->getDevice($extension);
		if(empty($dev)) {
			$res = $_REQUEST;
			//Override the device page here
			$settings = array('force_avp' => 'yes', 'avpf' => 'yes', 'icesupport' => 'yes', 'encryption' => 'yes', 'transport' => 'ws', 'dial' => 'SIP/'.$prefix.$extension);
			foreach($settings as $key => $value) {
				$res['devinfo_'.$key] = $value;
			}
			core_devices_add($this->prefix.$extension,'sip','','fixed',$extension,$usr['name'].' WebRTC Client');
			$this->core->addDevice($this->prefix.$extension,'sip',$settings);
		}
	}

	public function removeWebRTCDevice($id) {

	}

	public function getAllSettings() {
			return $this->getAll();
	}

	public function getSetting($setting) {
			return $this->getConfig($setting);
	}

	public function setSetting($setting,$value) {
		return $this->setConfig($setting,$value);
	}

	public function delSetting($setting) {
		return $this->delConfig($setting);
	}

	public function checkEnabled($user) {
		$settings = $this->getClientSettingsByUser($user);
		return !empty($settings);
	}

	public function setClientSettings($user,$device,$certid) {
		try {
			$sql = "REPLACE INTO webrtc_clients (`user`, `device`, `certid`) VALUES(?,?,?)";
			$sth = $this->db->prepare($sql);
			return $sth->execute(array($user,$device,$certid));
		} catch(\Exception $e) {
			return false;
		}
	}

	public function getClientSettingsByUser($user) {
		$sql = "SELECT * FROM webrtc_clients WHERE `user` = ?";
		$sth = $this->db->prepare($sql);
		$sth->execute(array($user));
		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
		if(empty($results)) {
			return false;
		}

		$sip_server = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR'];
		$dev = $this->core->getDevice($results['device']);
		$usr = core_users_get($results['user']);
		$results['realm'] = !empty($results['realm']) ? $results['realm'] : $sip_server;
		$results['username'] = !empty($results['username']) ? $results['username'] : $dev['id'];
		$results['sipuri'] = !empty($results['sipuri']) ? $results['sipuri'] : 'sip:'.$results['username'].'@'.$sip_server;
		$results['password'] = !empty($results['password']) ? $results['password'] : $dev['secret'];
		$prefix = $this->freepbx->Config->get('HTTPPREFIX');
		$suffix = !empty($prefix) ? "/".$prefix."/ws" : "/ws";
		$results['websocket'] = !empty($results['websocket']) ? $results['websocket'] : 'ws://'.$sip_server.':'.$this->freepbx->Config->get('HTTPBINDPORT').$suffix;
		$results['breaker'] = !empty($results['breaker']) ? (bool)$results['breaker'] : false;
		$results['cid'] = !empty($results['cid']) ? $results['cid'] : $usr['name'];
		return $results;
	}

	public function removeClientSettingsByUser($user) {
		try {
			$sql = "DELETE FROM webrtc_clients WHERE `user` = ?";
			$sth = $this->db->prepare($sql);
			return $sth->execute(array($user));
		} catch(\Exception $e) {
			return true;
		}
	}

	public function createDevice($extension,$certid) {
		$id = $this->prefix.$extension;
		$this->setClientSettings($extension,$id,$certid);
		$dev = $this->core->getDevice($extension);
		$settings = $this->core->convertRequest2Array($id,$dev['tech']);
		$settings['devicetype']['value'] = 'fixed';
		$settings['user']['value'] = $extension;
		$settings['description']['value'] = $_REQUEST['name'];
		switch($settings['sipdriver']['value']) {
			case 'chan_sip':
				$this->core->addDevice($id,'sip',$settings);
			break;
			case 'chan_pjsip':
				$this->core->addDevice($id,'pjsip',$settings);
			break;
			default:
				return true;
			break;
		}
	}

	public function removeDevice($extension) {
		$id = $this->prefix.$extension;
		$this->removeClientSettingsByUser($extension);
		$this->core->delDevice($id);
	}
}
