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
	}

	public function getNavItems() {
		if(!$this->webrtc->checkEnabled($this->ext)) {
			return false;
		}
		$out = array();
		$out[] = array(
			"rawname" => "webrtc",
			"badge" => false,
			"icon" => "fa-phone",
			"menu" => array(
				"html" => '<li><a>'._("New Phone Call").'</a></li>'
			)
		);
		return $out;
	}

	/**
	 * Send settings to UCP upon initalization
	 */
	function getStaticSettings() {
		$settings = $this->webrtc->getClientSettingsByUser($this->ext);
		$user = $this->UCP->User->getUser();
		if(!empty($settings)) {
			return array(
				'enabled' => true,
				'settings' => array(
					'wsservers' => $settings['websocket'],
					'uri' => $settings['sipuri'],
					'password' => $settings['password'],
					'enableHold' => (int)$this->UCP->getSetting($user['username'],$this->module,'hold'),
					'log' => 3
				)
			);
		} else {
			return array('enabled' => false);
		}
	}
}
