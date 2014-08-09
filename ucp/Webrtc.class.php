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

	/**
	 * Send the Presence Menu Item to UCP
	 */
	function getPresenceAction() {
		return ($this->webrtc->checkEnabled($this->ext)) ? array('icon' => 'fa-phone', 'title' => _("New Phone Call")) : array();
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
					'enableHold' => $this->UCP->getSetting($user['username'],$this->module,'hold')
				)
			);
		} else {
			return array('enabled' => false);
		}
	}
}
