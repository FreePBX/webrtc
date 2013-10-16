//global caller volume, doesnt affect keys
var gvolume = 100;
//global call hold state
var callheld = false;
//global mute state
var unmuted = true;
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
var remoteView = null;
$(function() {
	remoteView =  document.getElementById('audio_remote');
	
	var webrtc_config = {
	  'ws_servers': $('#websocket_proxy_url').val(),
	  'uri': $('#impu').val(),
	  'password': $('#password').val()
	};

	var freePBXPhone = new JsSIP.UA(webrtc_config);

	freePBXPhone.start();

	$(document).keydown(function(e){
		event = event || window.event;
		//Keys 0-9
		if(e.keyCode >= 48 && e.keyCode <= 57 && !event.shiftKey) {
			//extract key value from 48
			var num = (e.keyCode - 48);
			//if we are in a call
			if(callSession && num >= 0){
				//attempt to send the digits to the engine
				/*
				if(callSession.dtmf(num) == 0){
					//play our response
					$('#adtmf' + num).trigger('play');
					//get lcd_2 values
					var pre = $('#lcd_2').html();
					//append our output to that
					$('#lcd_2').html(pre+num)
				}
				*/
			//not in an active call, dont send keys to engine
			} else if(num >= 0) {
				//play our response
				$('#adtmf' + num).trigger('play');
				//get lcd_2 values
				var pre = $('#lcd_2').html();
				//append our output to that
				$('#lcd_2').html(pre+num)
			}
			var el = $('#dtmf' + num);
			webrtc_switch_img(el,'push')
		//keys 3 & 8 with shift (so # & *)
		} else if((e.keyCode == 51 || e.keyCode == 56) && event.shiftKey) {
		//backspace key, dont allow backspace while in a call (it doesnt make sense)
		} else if(e.keyCode == 8 && !callSession) {
			var pre = $('#lcd_2').html();
			//dont allow taking off too much of nothing
			if(pre != '') {
				pre = pre.substring(0, pre.length -1);
			}
			$('#lcd_2').html(pre)
		//enter key
		} else if(e.keyCode == 13) {
			freePBXPhone.call('*43', CallOptions);
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
			webrtc_switch_img(el,'std')
		}
		return false;
	});
	
	//webbutton class
	$('.webrtcbutton')
		.mouseup(function() { //mouse up
			var btn = $(this).attr("data-file");
			if(btn != 'mute-btn' && btn != 'answer-btn') {
				webrtc_switch_img(this,'std')
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
			if(callSession && dtmf){
				/*
				if(callSession.dtmf(num) == 0){
					$('#a' + aid).trigger('play');
					var pre = $('#lcd_2').html();
					$('#lcd_2').html(pre+num)
				}
				*/
			//not in an active call, send to screen
			} else if(dtmf) {
				$('#a' + aid).trigger('play');
				var pre = $('#lcd_2').html();
				$('#lcd_2').html(pre+num)
			//Volume down
			} else if(aid == 'voldwn') {
				//TODO: I think we could use jquery here
				speaker=document.getElementById("audio_remote");
				//prevent improbable volumes
				if(gvolume > 0) {
					gvolume = (gvolume - 10);
					speaker.volume=gvolume/100;
					$('#voldwn').fadeTo( "fast", 1 )
					$('#volup').fadeTo( "fast", 1 )
				} else {
					$('#voldwn').fadeTo( "fast", 0.3 )
				}
			//volume up
			} else if(aid == 'volup') {
				//TODO: I think we could use jquery here
				speaker=document.getElementById("audio_remote");
				//prevent impossible volumes
				if(gvolume < 100) {
					gvolume = (gvolume + 10);
					speaker.volume=gvolume/100;
					$('#volup').fadeTo( "fast", 1 )
					$('#voldwn').fadeTo( "fast", 1 )
				} else {
					$('#volup').fadeTo( "fast", 0.3 )
				}
			//detect hold button and hold state
			} else if(callSession && aid == 'hold') {
				alert('This functionality is currently broken in Asterisk')
			//detect hangup (really ignore) on inbound call
			} else if(callSession && (aid == 'hangup' || aid == 'ignore')) {
				//and hang it up....hahang it up
				callSession.terminate();
				//send lcd screen to blank
				$('#lcd_1').html('<i>Registered with Sip Server</i>');
				$('#lcd_2').html('');
				//stop local ring back tone.
				stopRingTone();
				//destroy the session
				callSession = null;
				//hide window
				$("#calleridpop" ).fadeOut("fast")
			//detect answering *inbound* call
			} else if(callSession && aid == 'answer') {
				$("#calleridpop" ).fadeOut("fast", function() {
					$('#calleridname').html()
					$('#calleridnum').html('');
				});
				callSession.answer(callOptions);
			//Local Microphone mute state
			} else if(callSession && aid == 'mute') {
				if(unmuted) {
					webrtc_switch_img(this,'push')
					unmuted = false;
				} else {
					webrtc_switch_img(this,'std')
					unmuted = true
				}
				//muteMicrophone(unmuted);
			} else if(!callSession && aid == 'answer') {
				var digits = $('#lcd_2').html();
				if(digits != '') {
					$('#lcd_1').html('<i>Calling '+digits+'</i>');
					freePBXPhone.call(digits,callOptions);
					startRingTone();
				}
			} else {
				console.log(callSession);
				console.log('unknown:'+aid);
			}
			
			if(aid != 'mute' && aid != 'answer') {
				webrtc_switch_img(this,'push')
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
});

function webrtc_switch_img(el, state) {
	var key = $(el).attr("data-file");
	if(state == 'push') {
		var src = $('#push_' + key).attr("src");
	} else {
		var src = $('#std_' + key).attr("src");
	}
	$(el).attr("src", src);
}

//start ringer
function startRingTone() {
    try {
        $('#ringtone').trigger('play')
    } catch (e) {}
}

//stop and reset ringer (to begining of track)
function stopRingTone() {
    try {
        $('#ringtone').trigger('pause')
		$('#ringtone').trigger('load')
    } catch (e) {}
}

//start ring back tone
function startRingbackTone() {
    try {
        $('#ringtone').trigger('play')
    } catch (e) {}
}

//stop ring back tone (to begining of track)
function stopRingbackTone() {
    try {
        $('#ringtone').trigger('pause')
		$('#ringtone').trigger('load')
    } catch (e) {}
}

//starts the call timer
function startTimer() {
	var sec = 0;
	function pad ( val ) { return val > 9 ? val : "0" + val; }
	if(callTimer != null) {
		//we have a timer running, this is bad so stop it
		clearInterval(callTimer);
	}
	callTimer = setInterval( function(){
	    $("#seconds").html(pad(++sec%60));
	    $("#minutes").html(pad(parseInt(sec/60,10)));
	}, 1000);
}

function stopTimer() {
}

/**
//JQuery onLoad
$(function() {
	//global keydown
	$(document).keydown(function(e){
		event = event || window.event;
		//Keys 0-9
		if(e.keyCode >= 48 && e.keyCode <= 57 && !event.shiftKey) {
			//extract key value from 48
			var num = (e.keyCode - 48);
			//if we are in a call
			if(callSession && num >= 0){
				//attempt to send the digits to the engine
				if(callSession.dtmf(num) == 0){
					//play our response
					$('#adtmf' + num).trigger('play');
					//get lcd_2 values
					var pre = $('#lcd_2').html();
					//append our output to that
					$('#lcd_2').html(pre+num)
				}
			//not in an active call, dont send keys to engine
			} else if(num >= 0) {
				//play our response
				$('#adtmf' + num).trigger('play');
				//get lcd_2 values
				var pre = $('#lcd_2').html();
				//append our output to that
				$('#lcd_2').html(pre+num)
			}
			var el = $('#dtmf' + num);
			webrtc_switch_img(el,'push')
		//keys 3 & 8 with shift (so # & *)
		} else if((e.keyCode == 51 || e.keyCode == 56) && event.shiftKey) {
			//we are in shift mode
			shifted = true;
			//engine replacement
			num = (e.keyCode == 51) ? '#' : '*';
			//image replacement..ment
			idt = (e.keyCode == 51) ? 'p' : 's';
			//if in call send to engine
			if(callSession && num){
				if(callSession.dtmf(num) == 0){
					$('#adtmf_' + idt).trigger('play');
					var pre = $('#lcd_2').html();
					$('#lcd_2').html(pre+num)
				}
			//else append to screen
			} else if(num) {
				$('#adtmf_' + idt).trigger('play');
				var pre = $('#lcd_2').html();
				$('#lcd_2').html(pre+num)
			}
			num = (e.keyCode == 51) ? '_p' : '_s';
			var el = $('#dtmf' + num);
			webrtc_switch_img(el,'push')

		//backspace key, dont allow backspace while in a call (it doesnt make sense)
		} else if(e.keyCode == 8 && !callSession) {
			var pre = $('#lcd_2').html();
			//dont allow taking off too much of nothing
			if(pre != '') {
				pre = pre.substring(0, pre.length -1);
			}
			$('#lcd_2').html(pre)
		//enter key for answer or send
		} else if(e.keyCode == 13) {
			answer(callSession)
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
			webrtc_switch_img(el,'std')
		}
		return false;
	});
	
	//webbutton class
	$('.webrtcbutton')
		.mouseup(function() { //mouse up
			var btn = $(this).attr("data-file");
			if(btn != 'mute-btn' && btn != 'answer-btn') {
				webrtc_switch_img(this,'std')
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
			if(callSession && dtmf){
				if(callSession.dtmf(num) == 0){
					$('#a' + aid).trigger('play');
					var pre = $('#lcd_2').html();
					$('#lcd_2').html(pre+num)
				}
			//not in an active call, send to screen
			} else if(dtmf) {
				$('#a' + aid).trigger('play');
				var pre = $('#lcd_2').html();
				$('#lcd_2').html(pre+num)
			//Volume down
			} else if(aid == 'voldwn') {
				//TODO: I think we could use jquery here
				speaker=document.getElementById("audio_remote");
				//prevent improbable volumes
				if(gvolume > 0) {
					gvolume = (gvolume - 10);
					speaker.volume=gvolume/100;
					$('#voldwn').fadeTo( "fast", 1 )
					$('#volup').fadeTo( "fast", 1 )
				} else {
					$('#voldwn').fadeTo( "fast", 0.3 )
				}
			//volume up
			} else if(aid == 'volup') {
				//TODO: I think we could use jquery here
				speaker=document.getElementById("audio_remote");
				//prevent impossible volumes
				if(gvolume < 100) {
					gvolume = (gvolume + 10);
					speaker.volume=gvolume/100;
					$('#volup').fadeTo( "fast", 1 )
					$('#voldwn').fadeTo( "fast", 1 )
				} else {
					$('#volup').fadeTo( "fast", 0.3 )
				}
			//detect hold button and hold state
			} else if(callSession && aid == 'hold') {
				alert('This functionality is currently broken in Asterisk')
				if(!callheld) {
					callSession.hold();
					callheld = true;
				} else {
					callSession.resume();
					callheld = false
				}
			//detect hangup (really ignore) on inbound call
			} else if(callSession && (aid == 'hangup' || aid == 'ignore')) {
				//and hang it up....hahang it up
				callSession.hangup();
				//send lcd screen to blank
				$('#lcd_1').html('<i>Call Ignored</i>');
				$('#lcd_2').html('');
				//stop local ring back tone.
				stopRingbackTone();
				stopRingTone();
				//destroy the session
				callSession = null;
				//hide window
				$("#calleridpop" ).fadeOut("fast")
			//detect answering *inbound* call
			} else if(aid == 'answer') {
				$("#calleridpop" ).fadeOut("fast", function() {
					$('#calleridname').html()
					$('#calleridnum').html('');
				});
				answer(callSession)
			//Local Microphone mute state
			} else if(callSession && aid == 'mute') {
				if(unmuted) {
					webrtc_switch_img(this,'push')
					unmuted = false;
				} else {
					webrtc_switch_img(this,'std')
					unmuted = true
				}
				muteMicrophone(unmuted);
			}
			
			if(aid != 'mute' && aid != 'answer') {
				webrtc_switch_img(this,'push')
			}
		});
		
		try{
			if(typeof(SIPml) !== undefined) {
				//initalize the engine
				SIPml.init(readyCallback, errorCallback);
				//start your engines
				sipStack.start();
			}
		} catch(e) {
			console.log('SIPml not yet defined');
			console.log('Run: SIPml.init(readyCallback, errorCallback);')
			console.log('Run: sipStack.start();')
		}
		
		//...and were off!
});

function external_call_hook(code) {
	if(code == 803) {
		if(callSession) {
			//and hang it up....hahang it up
			callSession.hangup();
			//destroy the session
			callSession = null;
		}
		//send lcd screen to blank
		$('#lcd_1').html('<i>Call Ignored</i>');
		$('#lcd_2').html('');
		//stop local ring back tone.
		stopRingbackTone();
		stopRingTone();
		
		//hide window
		$("#calleridpop" ).fadeOut("fast")
	}
}

function removeNav() {
	$('#menu').hide();
}

function popoutphone() {
	logout();
	sipStack.stop();
	$('#webrtcphone').hide();
	$('#removeNavLink').hide();
	$('#message').html('Phone is Currently Broken Out of Window');
	newwindow=window.open('/recordings/index.php?m=webrtcphone&f=display&hidenav=true','name','height=600,width=750,location=0,toolbar=0');
	if (window.focus) {newwindow.focus()}
	return false;
}

function answer(cs) {
	if(cs) {
		//accept it already
        callSession.accept({
            audio_remote: document.getElementById('audio_remote'),
            audio_local: document.getElementById('audio_local'),
            events_listener: {
                events: '*',
                listener: eventsListener
            } // optional: '*' means all events
        });
		//get remote caller id
		var sRemoteNumber = (callSession.getRemoteFriendlyName() || 'unknown');
		//setup display and timer
		$('#lcd_1').html('Connected to '+sRemoteNumber+' (<label id="minutes">00</label>:<label id="seconds">00</label>)');
		//start timer
		startTimer();
		//stop ringer
		stopRingTone();
		//Hold image in place
		var el = $('#answer-btn');
		webrtc_switch_img(el,'push')
	} else {
		//get lcd_2 value
		var number = $('#lcd_2').html();
		if(number != '') {
			//setup new callSession for our call
		    callSession = sipStack.newSession('call-audio', {
		        audio_remote: document.getElementById('audio_remote'),
		        events_listener: { events: '*', listener: eventsListener } // optional: '*' means all events
		    });
			//send lcd_2 value as a 'number'
	    	callSession.call(number);
			//TODO: Probably remove this, it sounds weird sometimes when it gets doubled.
			startRingbackTone();
			//hold image in place
			var el = $('#answer-btn');
			webrtc_switch_img(el,'push')
		}
	}
}
*/