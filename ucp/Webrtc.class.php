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
				case 'contacts':
					return true;
				break;
				case 'originate':
					$o = $this->UCP->getSetting($this->user['username'],$this->module,'originate');
					return !empty($o) ? true : false;
				break;
				default:
					return false;
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
				case "originate":
					if($this->_checkExtension($_REQUEST['from'])) {
						$data = $this->UCP->FreePBX->Core->getDevice($_REQUEST['from']);
						if(!empty($data)) {
							$this->astman->originate(array(
								"Channel" => "Local/".$data['id']."@from-internal",
								"Exten" => $_REQUEST['to'],
								"Context" => "from-internal",
								"Priority" => 1,
								"Async" => "yes",
								"CallerID" => "UCP <".$data['id'].">"
							));
						}
						$return['status'] = true;
					}
				break;
				default:
					return false;
				break;
			}
			return $return;
		}

	public function getNavItems() {
		if(!$this->webrtc->checkEnabled($this->ext)) {
			return false;
		}
		$webrtc = $this->webrtc->checkEnabled($this->ext) ? '<li class="web"><a>'._("New Web Phone Call").'</a></li>': '';
		$out = array();
		$out[] = array(
			"rawname" => "webrtc",
			"badge" => false,
			"icon" => "fa-phone",
			"hide" => true,
			"menu" => array(
				"html" => $webrtc
			)
		);
		return $out;
	}

	/**
	 * Send settings to UCP upon initalization
	 */
	function getStaticSettings() {
		$settings = $this->webrtc->getClientSettingsByUser($this->ext);
		$extensions = $this->UCP->getSetting($this->user['username'],'Settings','assigned');
		//force default extension to the top.
		if(!empty($this->user['default_extension'])) {
			$extensions = array_diff($extensions, array($this->user['default_extension']));
			array_unshift($extensions,$this->user['default_extension']);
		}
		if(!empty($settings)) {
			return array(
				'enabled' => true,
				'settings' => array(
					'wsservers' => $settings['websocket'],
					'uri' => $settings['sipuri'],
					'password' => $settings['password'],
					'enableHold' => (int)$this->UCP->getSetting($this->user['username'],$this->module,'hold'),
					'log' => 3
				),
				'extensions' => $extensions,
				'enableOriginate' => (int)$this->UCP->getSetting($this->user['username'],$this->module,'originate')
			);
		} else {
			return array('enabled' => false);
		}
	}

	private function _checkExtension($extension) {
		$user = $this->UCP->User->getUser();
		$extensions = $this->UCP->getSetting($this->user['username'],'Settings','assigned');
		return in_array($extension,$extensions);
	}
}
