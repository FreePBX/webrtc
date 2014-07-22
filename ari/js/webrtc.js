//global caller volume, doesnt affect keys
var gvolume = 100;
//global call hold state
var callheld = false;
//global mute state
var unmuted = true;
//global transfer state
var transfer = false;
//global shift key state
var shifted = false;
//call timer holder
var callTimer = null;
//options for JsSIP
var callOptions = {
  'eventHandlers': CallEventHandlers,
  'mediaConstraints': {'audio': true, 'video': false}
};
var callSession = null;
//Phone Handler
var freePBXPhone = null;
var remoteAudio = null;
$(function() {
  activate_phone();
  /*
	if(webrtcDetectedBrowser == 'chrome' && webrtcDetectedVersion > 26) {
		activate_phone();
	} else {
		$('#outter-message').html('Browser ' + webrtcDetectedBrowser + ' ' + webrtcDetectedVersion + ' is not supported at this time');
	}
  */
});

function activate_phone() {
	$('#webrtcphone-container').show();

	remoteAudio =  document.getElementById('audio_remote');

	var webrtc_config = {
		'ws_servers': $('#websocket_proxy_url').val(),
		'uri': $('#impu').val(),
		'password': $('#password').val()
	};
	freePBXPhone = new JsSIP.UA(webrtc_config);
	freePBXPhone.start();

	$(document).keydown(function(e){
		event = event || window.event;
		//Keys 0-9
		if(((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105)) && !event.shiftKey) {
			//extract key value from 48 if using keyboard top row
			var num = '';
			if(e.keyCode >= 48 && e.keyCode <= 57) {
				num = (e.keyCode - 48);
			//extract from 96 if using keypad
			} else {
				num = (e.keyCode - 96);
			}
			//if we are in a call
			if(num >= 0) {
				sendDTMF(callSession,num);
			}
			webrtc_switch_img($('#dtmf' + num),'push');
		//keys 3 & 8 with shift (so # & *)
		} else if(((e.keyCode == 51 || e.keyCode == 56) && event.shiftKey) || e.keyCode == 106) {
			var el = '';
			if(e.keyCode == 51) {
				sendDTMF(callSession,'#');
				el = $('#dtmf_p');
			} else if(e.keyCode == 56 || e.keyCode == 106) {
				sendDTMF(callSession,'*');
				el = $('#dtmf_s');
			}
			webrtc_switch_img(el,'push');
		//backspace key, dont allow backspace while in a call (it doesnt make sense)
		} else if(e.keyCode == 8 && !callSession) {
			var pre = $('#lcd_2').html();
			//dont allow taking off too much of nothing
			if(pre !== '') {
				pre = pre.substring(0, pre.length -1);
			}
			$('#lcd_2').html(pre);
		//enter key
		} else if(e.keyCode == 13) {
			var digits = $('#lcd_2').html();
			if(digits !== '') {
				$('#lcd_1').html('<i>Calling '+digits+'</i>');
				freePBXPhone.call(digits,callOptions);
				startRingTone();
			}
		}
		return false;
	});
	//keyboard keyup, reset image states and shift state
	//TODO: could probably just detect keyCode 16 here and unset the shift
	$(document).keyup(function(e){
		if(e.keyCode >= 48 && e.keyCode <= 57) {
			var num = (e.keyCode - 48);
			if(shifted) {
				num = (e.keyCode == 51) ? '_p' : '_s';
				shifted = false;
			}
			var el = $('#dtmf' + num);
			webrtc_switch_img(el,'std');
		}
		return false;
	});

	//webbutton class
	$('.webrtcbutton')
		.mouseup(function() { //mouse up
			var btn = $(this).attr("data-file");
			if(btn != 'mute-btn' && btn != 'answer-btn' && btn != 'hold-btn' && btn != 'transfer-btn' && btn != 'conf-btn') {
				webrtc_switch_img(this,'std');
			}
		})
		.mousedown(function() { //mouse down
			var num = '';
			var dtmf = false;
			var aid = $(this).attr("data-audio");
			switch (aid)
			{
				case 'dtmf1':
					num = 1;
					dtmf = true;
				break;
				case 'dtmf2':
					num = 2;
					dtmf = true;
				break;
				case 'dtmf3':
					num = 3;
					dtmf = true;
				break;
				case 'dtmf4':
					num = 4;
					dtmf = true;
				break;
				case 'dtmf5':
					num = 5;
					dtmf = true;
				break;
				case 'dtmf6':
					num = 6;
					dtmf = true;
				break;
				case 'dtmf7':
					num = 7;
					dtmf = true;
				break;
				case 'dtmf8':
					num = 8;
					dtmf = true;
				break;
				case 'dtmf9':
					num = 9;
					dtmf = true;
				break;
				case 'dtmf_p':
					num = '#';
					dtmf = true;
				break;
				case 'dtmf0':
					num = '0';
					dtmf = true;
				break;
				case 'dtmf_s':
					num = '*';
					dtmf = true;
				break;
			}
			//if in active call, and number and is a dtmf then send to engine
			if(dtmf){
				sendDTMF(callSession,num);
			//Volume down
			} else if(aid == 'voldwn') {
				//TODO: I think we could use jquery here
				speaker=document.getElementById("audio_remote");
				//prevent improbable volumes
				if(gvolume > 0) {
					gvolume = (gvolume - 10);
					speaker.volume=gvolume/100;
					$('#voldwn').fadeTo( "fast", 1 );
					$('#volup').fadeTo( "fast", 1 );
				} else {
					$('#voldwn').fadeTo( "fast", 0.3 );
				}
			//volume up
			} else if(aid == 'volup') {
				//TODO: I think we could use jquery here
				speaker=document.getElementById("audio_remote");
				//prevent impossible volumes
				if(gvolume < 100) {
					gvolume = (gvolume + 10);
					speaker.volume=gvolume/100;
					$('#volup').fadeTo( "fast", 1 );
					$('#voldwn').fadeTo( "fast", 1 );
				} else {
					$('#volup').fadeTo( "fast", 0.3 );
				}
			//detect hold button and hold state
			} else if(callSession && aid == 'hold') {
				alert('This functionality is currently Disabled');
			} else if(callSession && aid == 'transfer') {
				alert('This functionality is currently Disabled');
				/*
				if(transfer) {
					webrtc_switch_img(this,'push')
					transfer = false;
					$('#lcd_2').html('Enter Number Then hit Transfer');
				} else {
					webrtc_switch_img(this,'std')
					transfer = true
				}
				*/
			} else if(callSession && aid == 'conference') {
				alert('This functionality is currently Disabled');
			//detect hangup (really ignore) on inbound call
			} else if(callSession && (aid == 'hangup' || aid == 'ignore')) {
				//and hang it up....hahang it up
				callSession.terminate();
				//send lcd screen to blank
				$('#lcd_1').html('<i>Registered with Sip Server</i>');
				$('#lcd_2').html('');
				//hide window
				$("#calleridpop" ).fadeOut("fast");
			//detect answering *inbound* call
			} else if(callSession && aid == 'answer') {
				$("#calleridpop" ).fadeOut("fast", function() {
					$('#calleridname').html();
					$('#calleridnum').html('');
				});
				callSession.answer(callOptions);
			//Local Microphone mute state
			} else if(callSession && aid == 'mute') {
				if(unmuted) {
					webrtc_switch_img(this,'push');
					unmuted = false;
				} else {
					webrtc_switch_img(this,'std');
					unmuted = true;
				}
				muteMicrophone(unmuted);
			} else if(!callSession && aid == 'answer') {
				var digits = $('#lcd_2').html();
				if(digits !== '') {
					$('#lcd_1').html('<i>Calling '+digits+'</i>');
					freePBXPhone.call(digits,callOptions);
					startRingTone();
				}
			} else if(!callSession && aid == 'hangup') {
				$('#lcd_2').html('');
			}
			if(aid != 'mute' && aid != 'answer' && aid != 'hold' && aid != 'transfer' && aid != 'conference') {
				webrtc_switch_img(this,'push');
			}
		});

	// Call/Message reception callbacks
	freePBXPhone.on('connected', function(e) {
		$("#lcd_1").html('<i>Initalizing Engine...</i>');
	});

	// Call/Message reception callbacks
	freePBXPhone.on('disconnected', function(e) {
		$("#lcd_1").html('<i>Disconnected</i>');
	});

	// Call/Message reception callbacks
	freePBXPhone.on('registered', function(e) {
		$("#lcd_1").html('<i>Registered with Sip Server</i>');
	});

	// Call/Message reception callbacks
	freePBXPhone.on('unregistered', function(e) {
		$("#lcd_1").html('<i>Unregistered</i>');
	});

	// Call/Message reception callbacks
	freePBXPhone.on('registrationFailed', function(e) {
		$("#lcd_1").html('<i>Registration Failed</i>');
	});

	// Call/Message reception callbacks
	freePBXPhone.on('newRTCSession', function(e) {
		new_session(e);
	});
}

function webrtc_switch_img(el, state) {
	var key = $(el).attr("data-file");
	var src = '';
	if(state == 'push') {
		src = $('#push_' + key).attr("src");
	} else {
		src = $('#std_' + key).attr("src");
	}
	$(el).attr("src", src);
}

function removeNav() {
	$('#menu').hide();
}

function popoutphone() {
	freePBXPhone.stop();
	$('#webrtcphone').hide();
	$('#removeNavLink').hide();
	$('#message').html('Phone is Currently Broken Out of Window');
	newwindow=window.open('/recordings/index.php?m=webrtcphone&f=display&hidenav=true','name','height=650,width=750,location=0,toolbar=0');
	if (window.focus) { newwindow.focus(); }
	return false;
}
