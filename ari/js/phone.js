//sipml5 stack
var sipStack;
//in progress call session, null if not active
var callSession = null;
//the register session, null if not registered
var registerSession = null;
//call timer holder
var refreshIntervalId = null;

var eventsListener = function(e){
	//tsk_utils_log_info('==stack event = ' + e.type);
	switch (e.type) {
		//All Failed messages below
		case 'failed_to_start':
		case 'failed_to_stop':
		case 'stopping':
		case 'stopped':
			callSession = null; //important
			SipStack = null;
			myregisterSession = null;
			$("#lcd_1").html('<i>Disconnected because: </i>' + e.description);
		break;
		case 'starting':
			$("#lcd_1").html('<i>Initalizing Engine...</i>');
		//SIPML5 Engine Has Started
		case 'started':
			//initiate login function
			login();
			//hard to say if this is seen or below 'connecting' is seen
			$("#lcd_1").html('<i>Trying to Connect...</i>');
		break;
		//requested browser media
		case 'm_permission_requested':
		break;
		//request to get browser media accepted by user
		case 'm_permission_accepted':
		break;
		//request to get browser media rejected by user
		case 'm_permission_refused':
			//if the user rejects our request then reject the call
			//e.newSession.reject();
			$('#lcd_1').html('User Rejected Brower Media Request');
			callSession = null;
			//send lcd screen to blank
			$('#lcd_2').html('');
			//stop local ring back tone.
			stopRingbackTone();
			stopRingTone();
		break;
		//login request
		case 'sent_request':
			$('#lcd_1').html('Sent Login Request');
		break;
		//Engine is calling out or connecting
		case 'connecting':
			if(e.session == registerSession) {
				//attempting to connect
				//$("#lcd_1").html('Connecting...');
			} else {
				//attempting to call
				$("#lcd_1").html('Calling...');
			}
		break;
		//connected messages
		case 'connected':
			//if the session we are in right now is the register session then display below
			if(e.session == registerSession) {
				$("#lcd_1").html('<i>Registered with Sip Server</i>');
			//otherwise we stop playback as this is also the 'connected to caller' bit
			} else {
				//$("#lcd_1").html('');
				//stop local ring back tone
				stopRingbackTone();
			}
		break;
		//usually a hangup from either party
		case 'terminated':
			$("#lcd_1").html('<i>' + e.description + '</i>');
			//kill our callSession otherwise we can't make another call
			callSession = null;
			//send lcd screen to blank
			$('#lcd_2').html('');
			//stop local ring back tone.
			stopRingbackTone();
			stopRingTone();
			if(refreshIntervalId != null) {
				//stop our call timer if it was set
				clearInterval(refreshIntervalId);
			}
		break;
		//new inbound call
		case 'i_new_call':
			if (callSession) {
				// do not accept the incoming call if we're already 'in a call'
				//We can accept it, not sure how to manage it with asterisk not dealing with hold correctly
				e.newSession.reject();
			} else {
				//start a new session and place it in the call session
				callSession = e.newSession;
				//Start ringing in the browser
				startRingTone();
				//Display in the phone lcd who is calling
				var sRemoteNumber = (callSession.getRemoteFriendlyName() || 'unknown');
				$("#lcd_1").html("Incoming call from [" + sRemoteNumber + "]");
				/* TODO: this needs to be the shaun popup */
			}
		break;
		//adding remote audio stream means the call connected
		case 'm_stream_audio_remote_added':
			//get the remote caller ID
			var sRemoteNumber = (callSession.getRemoteFriendlyName() || 'unknown');
			//display the caller ID and timer on the lcd
			$("#lcd_1").html('Connected to '+sRemoteNumber+' (<label id="minutes">00</label>:<label id="seconds">00</label>)');
			//start the clock
			startTimer();
			//stop inbrowser ringing
			stopRingbackTone();
			stopRingTone();
		break;
		//sip notify messages from subscribed states
		case 'i_notify':
			console.info('NOTIFY content = ' + e.getContentString());
			console.info('NOTIFY content-type = ' + e.getContentType());
			//parse it all from xml to what we want (not xml)
			if (e.getContentType() == 'application/pidf+xml') {
				if (window.DOMParser) {
					var parser = new DOMParser();
					var xmlDoc = parser ? parser.parseFromString(e.getContentString(), "text/xml") : null;
					var presenceNode = xmlDoc ? xmlDoc.getElementsByTagName ("presence")[0] : null;
					if(presenceNode){
						var entityUri = presenceNode.getAttribute ("entity");
						var tupleNode = presenceNode.getElementsByTagName ("tuple")[0];
						if(entityUri && tupleNode){
							var statusNode = tupleNode.getElementsByTagName ("status")[0];
							if(statusNode){
								var basicNode = statusNode.getElementsByTagName ("basic")[0];
								if(basicNode){
									console.info('Presence notification: Uri = ' + entityUri + ' status = ' + basicNode.textContent);
								}
							}
						}
					}
				}
			}
		break;
		default:
			//log all unknowns
			console.log(e);
		break;
	}
}

//error callback
var errorCallback = function(e){
    console.error('Failed to initialize the engine: ' + e.message);
}

//our login function
var login = function(){
    registerSession = sipStack.newSession('register', {
		events_listener: { events: '*', listener: eventsListener }
    });
    registerSession.register();
}

//logout of server function (not used right now)
var logout = function(){
	if(registerSession != null) {
		registerSession.unregister();
		registerSession = null;
		callSession = null;
	}
}

//login credentials and junk like that
function createSipStack(){
    sipStack = new SIPml.Stack({
            realm: $('#realm').val(), // mandatory: domain name
            impi: $('#impi').val(), // mandatory: authorization name (IMS Private Identity)
            impu: $('#impu').val(), // mandatory: valid SIP Uri (IMS Public Identity)
            password: $('#password').val(), // optional
            display_name: $('#display_name').val(), // optional
            websocket_proxy_url: $('#websocket_proxy_url').val(), // optional
            enable_rtcweb_breaker: false, // optional
            events_listener: { events: '*', listener: eventsListener }, // optional: '*' means all events
            sip_headers: [ // optional
                    { name: 'User-Agent', value: 'IM-client/OMA1.0 sipML5-v1.0.0.0' },
                    { name: 'Organization', value: 'FreePBX' }
            ]
        }
    );
}

//engine ready callback
var readyCallback = function(e){
	createSipStack(); // see next section
};

function muteMicrophone(bEnabled) {
    console.log("-->>>> muteMicrophone = " + bEnabled);
    if (callSession != null) {
         console.log("-->>>> muteMicrophone-> callSession is valid");
        if (callSession.o_session != null) {
            console.log("-->>>> muteMicrophone-> callSession.o_session is valid");
            if (callSession.o_session.o_stream_local != null) {
                console.log("-->>>> muteMicrophone-> callSession.o_session.o_stream_local is valid");
                if (callSession.o_session.o_stream_local.getAudioTracks().length > 0) {
                    console.log("-->>>> muteMicrophone-> callSession.o_session.o_stream_local->Audio Tracks Greater than 0");
                    for (var nTrack = 0; nTrack < callSession.o_session.o_stream_local.getAudioTracks().length ; nTrack++) {
                      console.log("-->>>> muteMicrophone-> Setting Audio Tracks [" + nTrack + "] to state = " + bEnabled);
                        callSession.o_session.o_stream_local.getAudioTracks()[nTrack].enabled = bEnabled;
                    }
                }
                else {
                    console.log("-->>>> muteMicrophone-> callSession.o_session.o_stream_local-> NO AUDIO TRACKS");
                }
            }
            else {
                console.log("-->>>> muteMicrophone-> callSession.o_session.o_stream_local is NULL");
            }
        }
        else {
            console.log("-->>>> muteMicrophone-> callSession.o_session is NULL");
        }
    }
    else {
        console.log("-->>>> muteMicrophone-> callSession  is NULL");
    }
}


/* FUTURE for VIDEO
function muteWebCam(bEnabled)
{
    console.log("-->>>> muteWebCam = " + bEnabled);
    if (callSession != null) {
        // console.log("-->>>> muteWebCam-> callSession is valid");
        if (callSession.o_session != null) {
            // console.log("-->>>> muteWebCam-> callSession.o_session is valid");
            if (callSession.o_session.o_stream_local != null) {
               // console.log("-->>>> muteWebCam-> callSession.o_session.o_stream_local is valid");
                if (callSession.o_session.o_stream_local.getVideoTracks().length > 0) {
                 //   console.log("-->>>> muteWebCam-> callSession.o_session.o_stream_local->Video Tracks Greater than 0");
                    for (var nTrack = 0; nTrack < callSession.o_session.o_stream_local.getVideoTracks().length ; nTrack++) {
                   //     console.log("-->>>> muteWebCam-> Setting Video Tracks [" + nTrack + "] to state = " + bEnabled);
                        callSession.o_session.o_stream_local.getVideoTracks()[nTrack].enabled = bEnabled;
                    }
                }
                else {
                    console.log("-->>>> muteWebCam-> callSession.o_session.o_stream_local-> NO VIDEO TRACKS");
                }
            }
            else {
                console.log("-->>>> muteWebCam-> callSession.o_session.o_stream_local is NULL");
            }
        }
        else {
            console.log("-->>>> muteWebCam-> callSession.o_session is NULL");
        }
    }
    else {
        console.log("-->>>> muteWebCam-> callSession  is NULL");
    }
}
////////////////////////////////////////////////////////////////////////////////////////////////////
*/
