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
			"encryption" => "yes",
			"rtcp_mux" => "yes"
		),
		"pjsip" => array(
			"media_use_received_transport" => "yes",
			"avpf" => "yes",
			"icesupport" => "yes",
			"rtcp_mux" => "yes"
		)
	);

	/**
	 * Supported Versions of Asterisk for this module
	 * @type {array}
	 */
	private $supported = array(
		"11" => "11.11",
		"12" => "12.4",
		"13" => "13",
		"14" => "14"
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
		//Remove Old Link if need be
		if(file_exists($this->freepbx->Config->get('ASTETCDIR').'/http.conf') && is_link($this->freepbx->Config->get('ASTETCDIR').'/http.conf') && (readlink($this->freepbx->Config->get('ASTETCDIR').'/http.conf') == dirname(__FILE__).'/etc/httpd.conf')) {
			unlink($this->freepbx->Config->get('ASTETCDIR').'/http.conf');
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

		$clients = $this->getClientsEnabled();
		foreach($clients as $client) {
			$this->createDevice($client['device'],$client['certid']);
		}

		return true;
	}
	public function uninstall() {
		$sql = "SELECT * FROM webrtc_clients";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
		if(!empty($results)) {
			foreach($results as $row) {
				$this->removeDevice($row['user']);
			}
		}
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
				if(!empty($certs)) {
					$this->createDevice($user['default_extension']);
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
		if($display == "userman") {
			if(isset($_POST['webrtc_enable']) && $_POST['webrtc_enable'] == 'yes') {
				$this->freepbx->Ucp->setSettingByGID($id,'Webrtc','enabled',true);
			} else {
				$this->freepbx->Ucp->setSettingByGID($id,'Webrtc','enabled',false);
			}
		}

		$group = $this->freepbx->Userman->getGroupByGID($id);
		foreach($group['users'] as $user) {
			$enabled = $this->freepbx->Ucp->getCombinedSettingByID($user, 'Webrtc', 'enabled');
			$user = $this->freepbx->Userman->getUserByID($user);
			if(!empty($user['default_extension']) && $user['default_extension'] != 'none' && $enabled) {
				$dev = $this->freepbx->Core->getDevice($user['default_extension']);
				$id = $this->prefix.$user['default_extension'];
				$settings = $this->freepbx->Certman->getDTLSOptions($id);
				$defaultCert = $this->certman->getDefaultCertDetails();
				if(empty($defaultCert)) {
					return false;
				}

				if(!empty($dev) && (!$this->checkEnabled($user['default_extension']) || ($this->checkEnabled($user['default_extension']) && $settings['cid'] != $defaultCert['cid']))) {
					$this->createDevice($user['default_extension']);
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
		$cert = '';
		if($display == "userman") {
			if(isset($_POST['webrtc_enable']) && $_POST['webrtc_enable'] == 'yes') {
				$this->freepbx->Ucp->setSettingByID($id,'Webrtc','enabled',true);
			} elseif(isset($_POST['webrtc_enable']) && $_POST['webrtc_enable'] == 'no') {
				$this->freepbx->Ucp->setSettingByID($id,'Webrtc','enabled',false);
			} elseif(isset($_POST['webrtc_enable']) && $_POST['webrtc_enable'] == 'inherit') {
				$this->freepbx->Ucp->setSettingByID($id,'Webrtc','enabled',null);
			}
		}

		$enabled = $this->freepbx->Ucp->getCombinedSettingByID($id, 'Webrtc', 'enabled');

		$user = $this->freepbx->Userman->getUserByID($id);
		if(!empty($user['default_extension']) && $user['default_extension'] != 'none' && $enabled) {
			$id = $this->prefix.$user['default_extension'];
			$defaultCert = $this->certman->getDefaultCertDetails();
			if(empty($defaultCert)) {
				return false;
			}
			$settings = $this->freepbx->Certman->getDTLSOptions($id);
			if(!$this->checkEnabled($user['default_extension']) || ($this->checkEnabled($user['default_extension']) && $settings['cid'] != $defaultCert['cid'])) {
				$this->createDevice($user['default_extension']);
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
		$defaultCert = $this->certman->getDefaultCertDetails();
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
			"title" => _("Phone"),
			"rawname" => "webrtc",
			"content" => ""
		);
		if($this->validVersion() === true && !empty($defaultCert)) {
			$html[0]['content'] = load_view(dirname(__FILE__)."/views/ucp_config.php",array("mode" => $mode, "enabled" => $enabled, "webrtcmessage" => '', "config" => true));
		} elseif($this->validVersion() === true) {
			$html[0]['content'] = load_view(dirname(__FILE__)."/views/ucp_config.php",array("mode" => $mode, "enabled" => $enabled, "webrtcmessage" => sprintf(_('You have no default certificates setup in %s'),'<a href="?display=certman">'._('Certificate Manager').'</a>'), "config" => false));
		} else {
			$html[0]['content'] = load_view(dirname(__FILE__)."/views/ucp_config.php",array("mode" => $mode, "enabled" => $enabled, "webrtcmessage" => $this->validVersion(), "config" => false));
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
			return sprintf(_("Unsupported Version of Asterisk, You need at least %s you have %s"), $this->supported["11"], $version);
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

	public function setClientSettings($user,$device) {
		try {
			$sql = "REPLACE INTO webrtc_clients (`user`, `device`) VALUES(?,?)";
			$sth = $this->db->prepare($sql);
			return $sth->execute(array($user,$device));
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
		$sql = "SELECT * FROM webrtc_clients WHERE `user` = ?";
		$sth = $this->db->prepare($sql);
		$sth->execute(array($user));
		$results = $sth->fetch(\PDO::FETCH_ASSOC);
		if(empty($results)) {
			return false;
		}

		$serverparts = explode(":", $_SERVER['HTTP_HOST']); //strip off port because we define it
		$sip_server = $serverparts[0];
		$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on";
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
		$results['status'] = true;
		$results['realm'] = !empty($results['realm']) ? $results['realm'] : $sip_server;
		$results['username'] = !empty($results['username']) ? $results['username'] : $dev['id'];
		$results['sipuri'] = !empty($results['sipuri']) ? $results['sipuri'] : 'sip:'.$results['username'].'@'.$sip_server;
		$results['password'] = !empty($results['password']) ? $results['password'] : $dev['secret'];
		$prefix = $this->freepbx->Config->get('HTTPPREFIX');
		$suffix = !empty($prefix) ? "/".$prefix."/ws" : "/ws";

		if($secure && !$this->freepbx->Config->get('HTTPTLSENABLE')) {
			return array("status" => false, "message" => _("HTTPS is not enabled for Asterisk"));
		}

		$type = ($this->freepbx->Config->get('HTTPTLSENABLE') && $secure) ? 'wss' : 'ws';
		$port = ($this->freepbx->Config->get('HTTPTLSENABLE') && $secure) ? $this->freepbx->Config->get('HTTPTLSBINDPORT') : $this->freepbx->Config->get('HTTPBINDPORT');
		$results['websocket'] = !empty($results['websocket']) ? $results['websocket'] : $type.'://'.$sip_server.':'.$port.$suffix;
		try {
			$stunaddr = $this->freepbx->Sipsettings->getConfig("webrtcstunaddr");
			$stunaddr = !empty($stunaddr) ? $stunaddr : $this->freepbx->Sipsettings->getConfig("stunaddr");
			$results['stunaddr'] = $stunaddr;
		} catch(\Exception $e) {}
		$results['stunaddr'] = !empty($results['stunaddr']) ? "stun:".$results['stunaddr'] : "stun:stun.l.google.com:19302";
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

	public function createDevice($extension) {
		$id = $this->prefix.$extension;
		$previous = $this->core->getDevice($id);
		if(!empty($previous)) {
			$this->core->delDevice($id);
		}
		$version = $this->freepbx->Config->get('ASTVERSION');
		$user = $this->core->getUser($extension);
		$dev = $this->core->getDevice($extension);
		$socket = $this->getSocketMode();
		$settings = $this->core->generateDefaultDeviceSettings($socket,$id,'WebRTC '.$user['name']);
		if(!empty($previous['secret'])) {
			$settings['secret']['value'] = $previous['secret'];
		}
		$settings['devicetype']['value'] = 'fixed';
		$settings['context']['value'] = !empty($dev['context']) ? $dev['context'] : "from-internal";
		$settings['user']['value'] = $extension;
		//$settings['callerid']['value'] = $dev['description'] . "<".$extension.">";
		$defaultCert = $this->certman->getDefaultCertDetails();
		if(empty($defaultCert)) {
			return false;
		}
		$cert = array(
			"certificate" => $defaultCert['cid'],
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
				$settings['sessiontimers']['value'] = 'refuse';
				$settings['videosupport']['value'] = 'no';
				if((version_compare($version,'13.15.0','ge') && version_compare($version,'14.0','lt')) || version_compare($version,'14.4.0','ge')) {
					$settings['rtcp_mux']['value'] = 'yes';
				}
				$this->core->addDevice($id,'sip',$settings);
			break;
			case 'pjsip':
				$settings['avpf']['value'] = 'yes';
				$settings['icesupport']['value'] = 'yes';
				$settings['media_use_received_transport']['value'] = 'yes';
				$settings['timers']['value'] = 'no';
				$settings['media_encryption']['value'] = 'dtls';
				if((version_compare($version,'13.15.0','ge') && version_compare($version,'14.0','lt')) || version_compare($version,'14.4.0','ge')) {
					$settings['rtcp_mux']['value'] = 'yes';
				}
				$this->core->addDevice($id,'pjsip',$settings);
			break;
			default:
				return false;
			break;
		}
		$this->certman->addDTLSOptions($id, $cert);
		$this->setClientSettings($extension,$id,$certid);
		return true;
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
	public function delUser($extension, $editmode=false) {
		if(!$editmode) {
			$this->removeDevice($extension);
		}
	}
}
