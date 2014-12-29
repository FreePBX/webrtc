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
		)
	);

	/**
	 * Supported Versions of Asterisk for this module
	 * @type {array}
	 */
	private $supported = array(
		"11" => "11.11.0",
		"12" => "12.4.0",
		"13" => "13.0.0"
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
		$this->certman = $this->freepbx->Certman;
		$this->userman = $this->freepbx->Userman;
		$this->db = $this->freepbx->Database;
	}

	public function doConfigPageInit($page) {

	}

	public function install() {
		$status = $this->validVersion();
		if($status !== true) {
			out($status);
			throw new \Exception($status);
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
		$sql = "SELECT * FROM webrtc_clients";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
		if(!empty($results)) {
			foreach($results as $row) {
				$this->deleteDevice($row['device']);
			}
		}

		$sql="DROP TABLE webrtc_clients";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		return true;
	}
	public function backup(){

	}
	public function restore($backup){

	}
	public function genConfig() {
	}

	/**
	 * Enable WebRTC and originate for said user
	 * @param string $username Username
	 */
	public function migrationEnable($username) {
		$user = $this->userman->getUserByUsername($username);
		if(!empty($user) && !empty($user['default_extension']) && $user['default_extension'] != "none") {
			if($this->certman->checkCAexists()) {
				$certs = $this->certman->getAllManagedCertificates();
				if(!empty($certs[0])) {
					$certid = $certs[0]['cid'];
					$this->createDevice($user['default_extension'],$certid);
					$this->freepbx->Ucp->setSetting($user['username'],'Webrtc','hold',false);
				}
			}
			$this->freepbx->Ucp->setSetting($user['username'],'Webrtc','originate',true);
		}
	}

	public function processUCPAdminDisplay($user) {
		if(!empty($user['default_extension']) && $user['default_extension'] != 'none' && !empty($_REQUEST['webrtc|enable']) && $_REQUEST['webrtc|enable'] == 'yes') {
			if(!$this->checkEnabled($user['default_extension'])) {
				$this->createDevice($user['default_extension'],$_REQUEST['webrtc|cert']);
			}
		} else {
			if($this->checkEnabled($user['default_extension'])) {
				$this->removeDevice($user['default_extension']);
			}
		}
		if(!empty($_REQUEST['webrtc|hold']) && $_REQUEST['webrtc|hold'] == 'yes') {
			$this->freepbx->Ucp->setSetting($user['username'],'Webrtc','hold',true);
		} else {
			$this->freepbx->Ucp->setSetting($user['username'],'Webrtc','hold',false);
		}
		if(!empty($_REQUEST['webrtc|originate']) && $_REQUEST['webrtc|originate'] == 'yes') {
			$this->freepbx->Ucp->setSetting($user['username'],'Webrtc','originate',true);
		} else {
			$this->freepbx->Ucp->setSetting($user['username'],'Webrtc','originate',false);
		}
	}

	public function getUCPAdminDisplay($user) {
		$html = array();
		if(!empty($user['default_extension']) && $user['default_extension'] != 'none') {
			$settings = $this->getClientSettingsByUser($user['default_extension']);
			$mcerts = $this->certman->getAllManagedCertificates();
			if($this->validVersion() === true && !empty($mcerts)) {
				$hold = $this->freepbx->Ucp->getSetting($user['username'],'Webrtc','hold');
				$html[0]['description'] = '<a href="#" class="info">'._("Enable WebRTC Phone").':<span>'._("Whether or not to enable the WebRTC Phone for this linked. Additionally you must select a valid certificate to use.").'</span></a>';
				$html[0]['content'] = load_view(dirname(__FILE__)."/views/ucp_config.php",array("enabled" => !empty($settings)));
				$html[1]['description'] = '<a href="#" class="info">'._("WebRTC Certificate").':<span>'._("Which certificate to use for the WebRTC Phone in UCP").'</span></a>';
				$html[1]['content'] = load_view(dirname(__FILE__)."/views/ucp_config_certs.php",array("certs" => $mcerts, "settings" => $settings));
				$html[2]['description'] = '<a href="#" class="info">'._("Enable WebRTC Exterimental Hold Support").':<span>'._("Enable experimental hold support. Usually only works in Chrome at this point and is buggy").'</span></a>';
				$html[2]['content'] = load_view(dirname(__FILE__)."/views/ucp_config_hold.php",array("enabled" => $hold));
			} elseif($this->validVersion() === true) {
				$html[0]['description'] = '<a href="#" class="info">'._("Enable WebRTC Phone").':<span>'._("Whether or not to enable the WebRTC Phone for this linked. Additionally you must select a valid certificate to use.").'</span></a>';
				$html[0]['content'] = _('You have no certificates setup in <a href="config.php?display=certman">Certificate Manager<a/>');
			} else {
				$html[0]['description'] = '<a href="#" class="info">'._("Enable WebRTC Phone").':<span>'._("Whether or not to enable the WebRTC Phone for this linked. Additionally you must select a valid certificate to use.").'</span></a>';
				$html[0]['content'] = $this->validVersion();
			}
			$originate = $this->freepbx->Ucp->getSetting($user['username'],'Webrtc','originate');
			$html[] = array(
				'description' => '<a href="#" class="info">'._("Allow Originating Calls").':<span>'._("Allow calls to be originated from UCP").'</span></a>',
				'content' => load_view(dirname(__FILE__)."/views/ucp_config_originate.php",array("enabled" => $originate))
			);
		}
		return $html;
	}

	public function validVersion() {
		$version = $this->freepbx->Config->get('ASTVERSION');
		$vParts = explode(".",$version);
		$base = $vParts[0];
		if(isset($this->supported[$base])) {
			if(!version_compare($version, $this->supported[$base], "ge")) {
				return sprintf(_("Unsupported Version of Asterisk, You need at least %s you have %s"), $this->supported[$base], $version);
			}
		} else {
			return sprintf(_("Unsupported Version of Asterisk, You need at least %s you have &s"), $this->supported["11"], $version);
		}
		return true;
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

	public function getClientsEnabled() {
		$sql = "SELECT * FROM webrtc_clients";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
		if(empty($results)) {
			return array();
		}
		return $results;
	}

	public function getClientSettingsByUser($user) {
		//TODO need to check certs here
		$sql = "SELECT * FROM webrtc_clients WHERE `user` = ?";
		$sth = $this->db->prepare($sql);
		$sth->execute(array($user));
		$results = $sth->fetch(\PDO::FETCH_ASSOC);
		if(empty($results)) {
			return false;
		}

		$sip_server = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR'];
		$dev = $this->core->getDevice($results['device']);
		//$usr = core_users_get($results['user']);
		$results['realm'] = !empty($results['realm']) ? $results['realm'] : $sip_server;
		$results['username'] = !empty($results['username']) ? $results['username'] : $dev['id'];
		$results['sipuri'] = !empty($results['sipuri']) ? $results['sipuri'] : 'sip:'.$results['username'].'@'.$sip_server;
		$results['password'] = !empty($results['password']) ? $results['password'] : $dev['secret'];
		$prefix = $this->freepbx->Config->get('HTTPPREFIX');
		$suffix = !empty($prefix) ? "/".$prefix."/ws" : "/ws";
		$results['websocket'] = !empty($results['websocket']) ? $results['websocket'] : 'ws://'.$sip_server.':'.$this->freepbx->Config->get('HTTPBINDPORT').$suffix;
		$results['breaker'] = !empty($results['breaker']) ? (bool)$results['breaker'] : false;
		$results['cid'] = !empty($results['cid']) ? $results['cid'] : '';
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
		$check = $this->core->getDevice($id);
		if(!empty($check)) {
			$this->core->delDevice($id);
		}
		$dev = $this->core->getDevice($extension);
		$socket = $this->getSocketMode();
		$settings = $this->core->generateDefaultDeviceSettings($socket,$id,'WebRTC '.$dev['description']);
		$settings['devicetype']['value'] = 'fixed';
		$settings['user']['value'] = $extension;
		$c = $this->certman->getCertificateDetails($certid);
		if(empty($c)) {
			return false;
		}
		$cert = array(
			"certificate" => $certid,
			"verify" => "fingerprint",
			"setup" => "actpass",
			"rekey" => "0"
		);
		switch($socket) {
			case 'sip':
				$settings['avpf']['value'] = 'yes';
				$settings['force_avp']['value'] = 'yes';
				$settings['transport']['value'] = 'ws';
				$settings['icesupport']['value'] = 'yes';
				$settings['encryption']['value'] = 'yes';
				$this->core->addDevice($id,'sip',$settings);
			break;
			case 'pjsip':
				$settings['use_avpf']['value'] = 'yes';
				$settings['ice_support']['value'] = 'yes';
				$settings['media_use_received_transport']['value'] = 'yes';
				$this->core->addDevice($id,'pjsip',$settings);
			break;
			default:
				return false;
			break;
		}
		$this->certman->addDTLSOptions($id, $cert);
		$this->setClientSettings($extension,$id,$certid);
	}

	public function getSocketMode() {
		$websocketMode = null;
		if($this->freepbx->astman->mod_loaded("res_pjsip_transport_websocket")) {
			$type = $this->freepbx->astman->Command("module show like res_pjsip_transport_websocket");
			if(preg_match("/Not Running/",$type['data'])) {
				$websocketMode = 'sip';
			} else {
				$websocketMode = 'pjsip';
			}
		} else {
			$websocketMode = 'sip';
		}
		return $websocketMode;
	}

	public function removeDevice($extension) {
		$id = $this->prefix.$extension;
		$this->removeClientSettingsByUser($extension);
		$this->deleteDevice($id);
	}

	private function deleteDevice($device) {
		try {
			return $this->core->delDevice($device);
		} catch(\Exception $e) {
			return false;
		}
	}
}
