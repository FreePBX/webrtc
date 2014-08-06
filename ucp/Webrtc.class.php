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

	function getDisplay() {
		$html = '';
		return $html;
	}

	function getPresenceAction() {
		return ($this->webrtc->checkEnabled($this->ext)) ? array('icon' => 'fa-phone', 'title' => _("New Phone Call")) : array();
	}

	function getStaticSettings() {
		$settings = $this->webrtc->getClientSettingsByUser($this->ext);
		if(!empty($settings)) {
			return array(
				'enabled' => true,
				'settings' => array(
					'wsservers' => $settings['websocket'],
					'uri' => $settings['sipuri'],
					'password' => $settings['password']
				)
			);
		} else {
			return array('enabled' => false);
		}
	}
}
