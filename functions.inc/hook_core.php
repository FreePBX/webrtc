<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

function webrtc_configpageinit($pagename) {
	global $currentcomponent;
	global $amp_conf;

	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$extension = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
	$tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;
	$supported_hardware = array('sip','pjsip','iax2','dahdi');

    // We only want to hook the 'extensions' pages.
	if ($pagename != 'extensions' && in_array(str_replace('_generic','',$_REQUEST['tech_hardware']),$supported_hardware))  {
		return true;
	}

	if ($tech_hardware != null || $extdisplay != '' || $action == 'add') {
		$currentcomponent->addoptlistitem('webrtc_enable', 'no', 'No');
		$currentcomponent->addoptlistitem('webrtc_enable', 'yes', 'Yes');
		$currentcomponent->setoptlistopts('webrtc_enable', 'sort', false);

		$currentcomponent->addguifunc("webrtc_{$pagename}_configpageload");

		if (!empty($action)) {
			$currentcomponent->addprocessfunc("webrtc_{$pagename}_configprocess");
		}
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
		  _('Enable WebRTC User Control Panel Phone'), sprintf(_('Enable User Panel WebRTC Phone Client for this %s'),$mode), false));
	} else {
		$currentcomponent->addguielem('Device Options', new gui_hidden('webrtc_enable', $webrtc_value));
	}
}

function webrtc_extensions_configprocess() {
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extension = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	switch ($action) {
		case 'add':
			$extension = isset($_REQUEST['extension']) ? $_REQUEST['extension'] : null;
		case 'edit':
			$prev =  ($action != 'add') ? webrtc_get_enabled($extension) : 'no';
			webrtc_set_enabled($extension, $_REQUEST['webrtc_enable']);
			if($_REQUEST['webrtc_enable'] == 'yes' && $prev == 'no') {
				webrtc_create_device($extension);
			} elseif($_REQUEST['webrtc_enable'] == 'no' && $prev == 'yes') {
				webrtc_delete_device($extension);
			}
		break;
		case 'del':
			webrtc_delete($extension);
		break;
	}
}

function webrtc_users_configprocess() {

}
