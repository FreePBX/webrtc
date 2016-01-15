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
			"transport" => "wss,ws",
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
		"11" => "11.11",
		"12" => "12.4",
		"13" => "13"
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
			$sql = "DROP TABLE IF EXISTS `webrtc_settings`";
			$sth = $this->db->prepare($sql);
			$sth->execute();
		} catch(\Exception $e) {}

		$prefix = $this->getConfig('prefix');
		if(empty($prefix)) {
			$this->setConfig('prefix','99');
		}

		/*
		try {
			$stunaddr = $this->freepbx->Sipsettings->getConfig("stunaddr");
		} catch(\Exception $e) {
			$stunaddr = "";
		}
		if(empty($stunaddr)) {
			out("<strong style='color:red'>"._("The STUN Server address is blank. In many cases this can cause issues. Please define a valid server in the Asterisk SIP Settings module")."</strong>");
		}
		*/

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
				}
			}
		}
	}

	public function ucpDelGroup($id,$display,$data) {
		if(!empty($data['users'])) {
			foreach($data['users'] as $id) {
				$enabled = $this->freepbx->Ucp->getCombinedSettingByID($id, 'Webrtc', 'enabled');

				$user = $this->freepbx->Userman->getUserByID($id);
				if(!empty($user['default_extension']) && $user['default_extension'] != 'none' && $enabled) {
					if(!$this->checkEnabled($user['default_extension'])) {
						$this->createDevice($user['default_extension'],$_REQUEST['webrtc_cert']);
					}
				} else {
					if($this->checkEnabled($user['default_extension'])) {
						$this->removeDevice($user['default_extension']);
					}
				}
			}
		}
	}

	public function ucpAddGroup($id, $display, $data) {
		$this->ucpUpdateGroup($id,$display,$data);
	}

	public function ucpUpdateGroup($id,$display,$data) {
		if(isset($_POST['webrtc_enable']) && $_POST['webrtc_enable'] == 'yes') {
			$this->freepbx->Ucp->setSettingByGID($id,'Webrtc','enabled',true);
		} else {
			$this->freepbx->Ucp->setSettingByGID($id,'Webrtc','enabled',false);
		}

		$group = $this->freepbx->Userman->getGroupByGID($id);
		foreach($group['users'] as $user) {
			$enabled = $this->freepbx->Ucp->getCombinedSettingByID($user, 'Webrtc', 'enabled');

			$user = $this->freepbx->Userman->getUserByID($user);
			if(!empty($user['default_extension']) && $user['default_extension'] != 'none' && $enabled) {
				$dev = $this->freepbx->Core->getDevice($user['default_extension']);
				if(!empty($dev) && !$this->checkEnabled($user['default_extension'])) {
					$this->createDevice($user['default_extension'],$_REQUEST['webrtc_cert']);
				}
			} elseif($user['default_extension'] != 'none') {
				if($this->checkEnabled($user['default_extension'])) {
					$this->removeDevice($user['default_extension']);
				}
			}
		}
	}

	/**
	* Hook functionality from userman when a user is deleted
	* @param {int} $id      The userman user id
	* @param {string} $display The display page name where this was executed
	* @param {array} $data    Array of data to be able to use
	*/
	public function ucpDelUser($id, $display, $ucpStatus, $data) {
		$enabled = $this->freepbx->Ucp->getCombinedSettingByID($id, 'Webrtc', 'enabled');

		$user = $this->freepbx->Userman->getUserByID($id);
		if(!empty($user['default_extension']) && $user['default_extension'] != 'none' && $enabled) {
		} else {
			if($this->checkEnabled($user['default_extension'])) {
				$this->removeDevice($user['default_extension']);
			}
		}
	}

	/**
	* Hook functionality from userman when a user is added
	* @param {int} $id      The userman user id
	* @param {string} $display The display page name where this was executed
	* @param {array} $data    Array of data to be able to use
	*/
	public function ucpAddUser($id, $display, $ucpStatus, $data) {
		$this->ucpUpdateUser($id, $display, $ucpStatus, $data);
	}

	/**
	* Hook functionality from userman when a user is updated
	* @param {int} $id      The userman user id
	* @param {string} $display The display page name where this was executed
	* @param {array} $data    Array of data to be able to use
	*/
	public function ucpUpdateUser($id, $display, $ucpStatus, $data) {
		if(isset($_POST['webrtc_enable']) && $_POST['webrtc_enable'] == 'yes') {
			$this->freepbx->Ucp->setSettingByID($id,'Webrtc','enabled',true);
		} elseif(isset($_POST['webrtc_enable']) && $_POST['webrtc_enable'] == 'no') {
			$this->freepbx->Ucp->setSettingByID($id,'Webrtc','enabled',false);
		} elseif(isset($_POST['webrtc_enable']) && $_POST['webrtc_enable'] == 'inherit') {
			$this->freepbx->Ucp->setSettingByID($id,'Webrtc','enabled',null);
		}

		$enabled = $this->freepbx->Ucp->getCombinedSettingByID($id, 'Webrtc', 'enabled');

		$user = $this->freepbx->Userman->getUserByID($id);
		if(!empty($user['default_extension']) && $user['default_extension'] != 'none' && $enabled) {
			if(!$this->checkEnabled($user['default_extension'])) {
				$this->createDevice($user['default_extension'],$_REQUEST['webrtc_cert']);
			}
		} else {
			if($this->checkEnabled($user['default_extension'])) {
				$this->removeDevice($user['default_extension']);
			}
		}
	}

	public function ucpConfigPage($mode, $user, $action) {
		$html = array();
		$message = '';
		$mcerts = $this->certman->getAllManagedCertificates();
		if(empty($user)) {
			$enabled = ($mode == 'group') ? true : null;
		} else {
			if($mode == 'group') {
				$enabled = $this->freepbx->Ucp->getSettingByGID($user['id'],'Webrtc','enabled');
				$enabled = !($enabled) ? false : true;
			} else {
				$enabled = $this->freepbx->Ucp->getSettingByID($user['id'],'Webrtc','enabled');
			}
		}

		$html[0] = array(
			"title" => _("WebRTC"),
			"rawname" => "webrtc",
			"content" => ""
		);
		if($this->validVersion() === true && !empty($mcerts)) {
			try {
				$stunaddr = $this->freepbx->Sipsettings->getConfig("stunaddr");
				if(empty($stunaddr)) {
					$message = _("The STUN Server address is blank. In many cases this can cause issues. Please define a valid server in the Asterisk SIP Settings module");
				}
			} catch(\Exception $e) {
				$message = _("The STUN Server address is blank. In many cases this can cause issues. Please define a valid server in the Asterisk SIP Settings module");
			}
			$html[0]['content'] = load_view(dirname(__FILE__)."/views/ucp_config.php",array("mode" => $mode, "enabled" => $enabled, "webrtcmessage" => $message, "certs" => $mcerts, "config" => true));
		} elseif($this->validVersion() === true) {
			$html[0]['content'] = load_view(dirname(__FILE__)."/views/ucp_config.php",array("mode" => $mode, "enabled" => $enabled, "webrtcmessage" => _('You have no certificates setup in Certificate Manager'), "certs" => $mcerts, "config" => false));
		} else {
			$html[0]['content'] = load_view(dirname(__FILE__)."/views/ucp_config.php",array("mode" => $mode, "enabled" => $enabled, "webrtcmessage" => $this->validVersion(), "certs" => $mcerts, "config" => false));
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
		if(empty($dev)) {
			//no device so remove the settings, someone deleted the device basically
			$this->removeClientSettingsByUser($user);
			return false;
		}
		if($this->freepbx->Config->get('HTTPTLSENABLE') && $dev['transport'] == "chan_sip" && ($dev['transport'] != "wss" && $dev['transport'] != "wss,ws")) {
			return false;
		}
		//$usr = core_users_get($results['user']);
		$results['realm'] = !empty($results['realm']) ? $results['realm'] : $sip_server;
		$results['username'] = !empty($results['username']) ? $results['username'] : $dev['id'];
		$results['sipuri'] = !empty($results['sipuri']) ? $results['sipuri'] : 'sip:'.$results['username'].'@'.$sip_server;
		$results['password'] = !empty($results['password']) ? $results['password'] : $dev['secret'];
		$prefix = $this->freepbx->Config->get('HTTPPREFIX');
		$suffix = !empty($prefix) ? "/".$prefix."/ws" : "/ws";

		$type = $this->freepbx->Config->get('HTTPTLSENABLE') ? 'wss' : 'ws';
		$port = $this->freepbx->Config->get('HTTPTLSENABLE') ? $this->freepbx->Config->get('HTTPTLSBINDPORT') : $this->freepbx->Config->get('HTTPBINDPORT');
		$results['websocket'] = !empty($results['websocket']) ? $results['websocket'] : $type.'://'.$sip_server.':'.$port.$suffix;
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
		$settings['context']['value'] = $dev['context'];
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
				$settings['transport']['value'] = 'wss,ws';
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
	public function dashboardIgnoreExt(){
		return array(array('length' => 2, 'value' => '99'));
	}
}
