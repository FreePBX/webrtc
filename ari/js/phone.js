var CallEventHandlers = {
	'failed': function(e){
		endCall(e)
	},
	'ended': function(e){
		endCall(e)
	},
	'started': function(e){
		startCall(e)
	}
};

function new_session(e) {
	var display_name, status,
	        request = e.data.request,
	        call = e.data.session,
	        uri = call.remote_identity.uri,
	        session = callSession;

	display_name = call.remote_identity.display_name || call.remote_identity.uri.user;
	display_name = display_name + ' ' + call.remote_identity.uri.user;

	if (call.direction === 'incoming') {
		status = "incoming";
		$("#calleridpop" ).fadeIn("fast")
		$('#calleridname').html()
		$('#calleridnum').html(display_name);
		startRingTone();
	} else {
		status = "outgoing";
	}
	
    // If the session exists with active call reject it.
    if (session) {
      call.terminate();
      return false;
    }

    // If this is a new session create it
    if (!session) {
      callSession = session = call;
    }
	
	// Call/Message reception callbacks
	callSession.on('failed', function(e) {
		if(status == "incoming") {
			$("#calleridpop" ).fadeOut("fast")
			$('#calleridname').html()
			$('#calleridnum').html('');
			stopRingTone();
		}
		endCall(e)
	});
	
	// Call/Message reception callbacks
	callSession.on('ended', function(e) {
		endCall(e)
	});
	
	// Call/Message reception callbacks
	callSession.on('started', function(e) {
		startCall(e)
	});
}

function muteMicrophone(bEnabled) {
	//callSession.rtcMediaHandler.localMedia.getAudioTracks()[0].enabled
    if (callSession != null) {
        if (callSession.rtcMediaHandler.localMedia.getAudioTracks().length > 0) {
            for (var nTrack = 0; nTrack < callSession.rtcMediaHandler.localMedia.getAudioTracks().length ; nTrack++) {
                callSession.rtcMediaHandler.localMedia.getAudioTracks()[nTrack].enabled = bEnabled;
            }
		}
	}
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
	if(callTimer != null) {
		clearInterval(callTimer);
	}
}

function startCall(e) {
	var rtcSession = e.sender, call = e.data.session;

	display_name = callSession.remote_identity.display_name || callSession.remote_identity.uri.user;
	$('#lcd_1').html('Connected to '+display_name+' (<label id="minutes">00</label>:<label id="seconds">00</label>)');
	//start timer
	startTimer();
	//stop ringer
	stopRingTone();
	//Hold image in place
	var el = $('#answer');
	webrtc_switch_img(el,'push');
	
	// Attach remote stream to remoteView
	if (rtcSession.getRemoteStreams().length > 0) {
		remoteAudio.src = window.URL.createObjectURL(rtcSession.getRemoteStreams()[0]);
	}
}

function endCall(e) {
	var rtcSession = e.sender, call = e.data.session;
	
	if(callSession != null) {
		stopRingTone();
		stopTimer();
		var el = $('#answer');
		webrtc_switch_img(el,'std');
		callSession = null;
		$('#lcd_1').html('<i>Registered with Sip Server</i>');
		$('#lcd_2').html('');
	}
}

function answer(cSession) {
	
}

function hangup(cSession) {
	
}

function sendDTMF(cSession,DTMF) {
	if(cSession){
		cSession.sendDTMF(DTMF);
	}
	var pre = $('#lcd_2').html();
	$('#lcd_2').html(pre+DTMF)
	switch(DTMF) {
		case '*':
			DTMF = '_s';
			break;
		case '#':
			DTMF = '_p';
			break;
		default:
			DTMF = DTMF;
			break;
	}
	$('#adtmf' + DTMF).trigger('play');
}