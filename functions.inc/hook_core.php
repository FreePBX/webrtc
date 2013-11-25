<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

function webrtc_configpageinit($dispnum) {
	global $currentcomponent;
	switch ($dispnum) {
		case 'extensions':
			if (isset($_REQUEST['tech_hardware']) && (
				$_REQUEST['tech_hardware'] == 'sip_generic' ||
					$_REQUEST['tech_hardware'] == 'iax2_generic' ||
				$_REQUEST['tech_hardware'] == 'dahdi_generic' )) {
					$extdisplay = '';
			} elseif (!isset($_REQUEST['parkpro_parkinglot']) && !isset($_REQUEST['extdisplay'])) {
				return true;
			} else {
				$device_info = core_devices_get($_REQUEST['extdisplay']);
				if (empty($device_info) || $device_info['tech'] != 'sip' && $device_info['tech'] != 'iax2' && $device_info['tech'] != 'dahdi') {
					return true;
				}
			}
		break;
	    default;
	  		return true;
	    break;
	}
	
	$currentcomponent->addoptlistitem('webrtc_enable', 'no', 'No');
	$currentcomponent->addoptlistitem('webrtc_enable', 'yes', 'Yes');
	$currentcomponent->setoptlistopts('webrtc_enable', 'sort', false);

  	$currentcomponent->addguifunc("webrtc_{$dispnum}_configpageload");

	if (!empty($_REQUEST['action'])) {
		$currentcomponent->addprocessfunc("webrtc_{$dispnum}_configprocess");
	}
}

function webrtc_users_configpageload() {
  webrtc_configpageload('users');
}

function webrtc_extensions_configpageload() {
  webrtc_configpageload('extension');
}

function webrtc_configpageload($mode) {
	global $amp_conf;
	global $currentcomponent;
	
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;

	$webrtc_select = $currentcomponent->getoptlist('webrtc_enable');
	
	$webrtc_value = webrtc_get_enabled($extdisplay);
	
	if(version_compare($amp_conf['ASTVERSION'],'11.5')) {
		$currentcomponent->addguielem('WebRTC Phone', new gui_selectbox( 'webrtc_enable', $webrtc_select, $webrtc_value,
		  _('Enable WebRTC ARI Phone'), sprintf(_('Enable User Panel WebRTC Phone Client for this %s'),$mode), false));
	} else {
		$currentcomponent->addguielem('Device Options', new gui_hidden('webrtc_enable', $webrtc_value));
	}	  
}

function webrtc_extensions_configprocess() {
	switch ($_REQUEST['action']) {
		case 'add':
		case 'edit':
			$prev =  webrtc_get_enabled($_REQUEST['extension']);
			webrtc_set_enabled($_REQUEST['extension'], $_REQUEST['webrtc_enable']);
			if($_REQUEST['webrtc_enable'] == 'yes' && $prev == 'no') {
				webrtc_create_device($_REQUEST['extension']);
			} elseif($_REQUEST['webrtc_enable'] == 'no' && $prev == 'yes') {
				webrtc_delete_device($_REQUEST['extension']);
			}
		break;
		case 'del':
			webrtc_delete($_REQUEST['extdisplay']);
		break;
	}
}

function webrtc_users_configprocess() {

}