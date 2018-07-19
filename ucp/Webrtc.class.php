<?php
/**
 * This is the User Control Panel Object.
 *
 * Copyright (C) 2014 Schmooze Com, INC
 */
namespace UCP\Modules;
use \UCP\Modules as Modules;

class Webrtc extends Modules{
	protected $module = 'Webrtc';
	private $ext = 0;

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->webrtc = $this->UCP->FreePBX->Webrtc;
		$this->ext = $this->Modules->getDefaultDevice();
		$this->astman = $this->UCP->FreePBX->astman;
		$this->user = $this->UCP->User->getUser();
	}

	public function getSimpleWidgetList() {
		if(!$this->webrtc->checkEnabled($this->ext)) {
			return array();
		}
		return array(
			"rawname" => "webrtc",
			"display" => _("Phone"),
			"icon" => "fa fa-phone",
			"list" => array(
				"phone" => array(
					"display" => "Phone",
					"hasSettings" => true
				)
			)
		);
	}

	public function getSimpleWidgetDisplay($id) {
		if(!$this->webrtc->checkEnabled($this->ext)) {
			return array();
		}
		return array(
			'title' => _("Phone"),
			'html' => load_view(__DIR__."/views/phone.php",array())
		);
	}

	public function getSimpleWidgetSettingsDisplay($id) {
		if(!$this->webrtc->checkEnabled($this->ext)) {
			return array();
		}
		$displayvars = array();
		$display = array(
			'title' => _("WebRTC"),
			'html' => $this->load_view(__DIR__.'/views/settings.php',$displayvars)
		);

		return $display;
	}


		/**
		* Determine what commands are allowed
		*
		* Used by Ajax Class to determine what commands are allowed by this class
		*
		* @param string $command The command something is trying to perform
		* @param string $settings The Settings being passed through $_POST or $_PUT
		* @return bool True if pass
		*/
		function ajaxRequest($command, $settings) {
			switch($command) {
				case 'cimage':
				case 'contacts':
					return true;
				break;
				default:
					return false;
				break;
			}
		}

		public function ajaxCustomHandler() {
			switch($_REQUEST['command']) {
				case "cimage":
						if($this->UCP->Modules->moduleHasMethod('Contactmanager', 'userDetails')) {
							$did = $_REQUEST['did'];
							$result = $this->UCP->FreePBX->Contactmanager->lookupByUserID($this->user['id'], $did,"/\D/");
							if($result['image']) {
								dbug($result);
								$data = $this->UCP->FreePBX->Contactmanager->getImageByID($result['id'],$result['email'], $result['type']);
								if(!empty($data)) {
									$finfo = new \finfo(FILEINFO_MIME);
									header("Cache-Control: no-cache, must-revalidate");
									header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
									header("Content-type: ".$finfo->buffer($data['image']));
									echo $data['image'];
									return true;
								}
							}
						}
						$contents = file_get_contents(__DIR__."/assets/images/no_user_logo.png");
						$finfo = new \finfo(FILEINFO_MIME);
						header("Cache-Control: no-cache, must-revalidate");
						header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
						header("Content-type: ".$finfo->buffer($contents));
						echo $contents;
					return true;
				break;
			}
		}

		/**
		* The Handler for all ajax events releated to this class
		*
		* Used by Ajax Class to process commands
		*
		* @return mixed Output if success, otherwise false will generate a 500 error serverside
		*/
		function ajaxHandler() {
			$return = array("status" => false, "message" => "");
			switch($_REQUEST['command']) {
				case "contacts":
					$return = array();
					if($this->Modules->moduleHasMethod('Contactmanager','lookupMultiple')) {
						$search = !empty($_REQUEST['search']) ? $_REQUEST['search'] : "";
						$results = $this->Modules->Contactmanager->lookupMultiple($search);
						if(!empty($results)) {
							foreach($results as $res) {
								foreach($res['numbers'] as $type => $num) {
									if(!empty($num)) {
										$return[] = array(
											"value" => $num,
											"text" => $res['displayname'] . " (".$type.")"
										);
									}
								}
							}
						}
					}
				break;
				default:
					return false;
				break;
			}
			return $return;
		}

	/**
	 * Send settings to UCP upon initalization
	 */
	function getStaticSettings() {
		$settings = $this->webrtc->getClientSettingsByUser($this->ext);
		if(!empty($settings['status'])) {
			return array(
				'enabled' => true,
				'settings' => array(
					'wsservers' => $settings['websocket'],
					'uri' => $settings['sipuri'],
					'password' => $settings['password'],
					'log' => 3,
					'iceServers' => array($settings['stunaddr']),
					'gatheringTimeout' => 2000
				),
				'extensions' => array($this->user['default_extension'])
			);
		} else {
			if(!empty($settings['message'])) {
				return array('enabled' => false, "message" => $settings['message']);
			} else {
				return array('enabled' => false);
			}
		}
	}
}
