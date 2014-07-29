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
	$th = !empty($_REQUEST['tech_hardware']) ? $_REQUEST['tech_hardware'] : '';
	if ($pagename != 'extensions' && in_array(str_replace('_generic','',$th),$supported_hardware))  {
		return true;
	}

	$fw_ari = FreePBX::Modules()->getInfo('fw_ari');
	if(!empty($fw_ari['fw_ari']) && $fw_ari['fw_ari']['status'] == MODULE_STATUS_ENABLED) {
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
}

function webrtc_extensions_configpageload() {
  webrtc_configpageload('extension');
}

function webrtc_configpageload($mode) {
	global $currentcomponent;
	$webrtc = FreePBX::Webrtc();
	$certman = FreePBX::Certman();


	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;

	$webrtc_select = $currentcomponent->getoptlist('webrtc_enable');

	$webrtc_value = $webrtc->checkEnabled($extdisplay) ? 'yes' : 'no';
	$mcerts = $certman->getAllManagedCertificates();

	$status = $webrtc->validVersion();
	$mode = $webrtc->getSocketMode();
	if($status === true) {
		if(!empty($mcerts)) {
			if($mode == 'pjsip') {
				$currentcomponent->addguielem('WebRTC Phone', new gui_label('webrtc_message',_('The WebSockets Interface is running through PJSIP, PJSIP is not supported at this time. Please enable the chan_sip driver (along with pjsip or by itself) or alert the FreePBX Developers')));
				$currentcomponent->addguielem('Device Options', new gui_hidden('webrtc_enable', $webrtc_value));
			} else {
				$currentcomponent->addguielem('WebRTC Phone', new gui_selectbox( 'webrtc_enable', $webrtc_select, $webrtc_value,
				_('Enable WebRTC Old ARI Phone'), sprintf(_('Enables WebRTC for this %s in the Asterisk Recording Interface (ARI). Note: ARI is depreciated in favor of UCP'),$mode), false));
				$certs = array();
				foreach($mcerts as $cert) {
					$certs[] = array(
						"text" => $cert['basename'],
						"value" => $cert['cid']
					);
				}
				$settings = $webrtc->getClientSettingsByUser($extdisplay);
				$cert = !empty($settings['certid']) ? $settings['certid'] : '';
				$currentcomponent->addguielem('WebRTC Phone', new gui_selectbox(
					'webrtc_dtls_certificate',
					$certs,
					$cert,
					_('Use Certificate'),
					_("The Certificate to use from Certificate Manager"),
					false)
				);
			}
		} else {
			$currentcomponent->addguielem('WebRTC Phone', new gui_label('webrtc_message',sprintf(_('To utilize WebRTC in ARI you must add at least one certificate %s through Certificate Manager'),$mode)));
			$currentcomponent->addguielem('Device Options', new gui_hidden('webrtc_enable', $webrtc_value));
		}
	} else {
		$currentcomponent->addguielem('Device Options', new gui_hidden('webrtc_enable', $webrtc_value));
	}
}

function webrtc_extensions_configprocess() {
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extension = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$webrtc = FreePBX::Webrtc();
	switch ($action) {
		case 'add':
			$extension = isset($_REQUEST['extension']) ? $_REQUEST['extension'] : null;
		case 'edit':
			$prev =  ($action != 'add') ? $webrtc->checkEnabled($extension) : false;
			if($_REQUEST['webrtc_enable'] == 'yes' && !$prev) {
				$webrtc->createDevice($extension,$_REQUEST['webrtc_dtls_certificate']);
			} elseif($_REQUEST['webrtc_enable'] == 'no' && $prev) {
				$webrtc->removeDevice($extension);
			}
		break;
		case 'del':
			$webrtc->removeDevice($extension);
		break;
	}
}
