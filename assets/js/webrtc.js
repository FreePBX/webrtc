/****************************************/
/*    TODO:				*/
/*					*/
/*	Check websockets.		*/
/*	Validate Browser compatibilty.	*/
/*	Install webrtc4all Windows.	*/
/*----XAccept calls after send a call.	*/
/*----XOpen Local audio Incoming call.	*/
/*	Video usage.			*/
/*					*/
/*	by navaismo at gmail dot com	*/
/*	last rev: 29/03/2013		*/
/*					*/
/*	last rev: 32/03/2013		*/
/****************************************/
/****************************************/
/*	Changelog: 32/03/2013		*/
/****************************************/
/*					*/
/*	Listeners functions changed	*/
/*	 from IF/ELSEIF to SWITCH.	*/
/*					*/
/*	Added media video for future	*/
/*	usage.				*/
/*					*/
/*	On Hangup destroy call session.	*/
/*					*/
/*	Solved Accepting calls and	*/
/*	audio issues.			*/
/*					*/
/*	Solved acceptting calls after	*/
/*	previous call.			*/
/*					*/
/****************************************/
/****************************************/
/*	Changelog: 01/04/2013		*/
/****************************************/
/*					*/
/*	Keypad works now if not on 	*/
/*	call.				*/
/*					*/
/*	Hangup clear keypad if not 	*/
/*	in call.			*/
/*					*/
/*	If Response NOTFOUND stop	*/
/*	all sounds.			*/
/*					*/
/*	Fixed DTMF sound on keypress.	*/
/*					*/
/****************************************/
//Variables
var mySipStack;
var mycallSession;
var myregisterSession;
// readycallback for INIT
var readyCallback = function(e) {
        console.log("engine is ready");
        //CHeck if the SIPml start
        if (SIPml.isInitialized() == 1) {
            console.log("Done to initialize the engine");
            //If the stack is started, create the sip stack
            startSipStack();
        } else {
            //If not started display console msg
            console.log("Failed to initialize the engine");
        }
    }
    // error callback for INIT
var errorCallback = function(e) {
        console.error('Failed to initialize the engine: ' + e.message);
    }
    //INIT SIPML5 API
    SIPml.init(readyCallback, errorCallback);
//Here we listen stack messages

function listenerFunc(e) {
    //Log incoming messages
    tsk_utils_log_info('==stack event = ' + e.type);
    switch (e.type) {
        //If failed msg or error Log in console & Web Page
    case 'failed_to_start':
    case 'failed_to_stop':
    case 'stopping':
    case 'stopped':
        {
            console.log("Failed to connect to SIP SERVER")
            mycallSession = null;
            mySipStack = null;
            myregisterSession = null;
            $("#mysipstatus").html('');
            $("#mysipstatus").html('<i>Disconnected: </i>' + e.description);
            break;
        }
        //If the msg is 'started' now try to Login to Sip server       				
    case 'started':
        {
            console.log("Trying to Login");
            login(); //function to login in sip server
            //Display msg in the web page
            $("#mysipstatus").html('');
            $("#mysipstatus").html('<i>Trying to Connect</i>');
            break;
        }
        //If the msg 'connected' display the register OK in the web page 
    case 'connected':
        {
            $("#mysipstatus").html('');
            $("#mysipstatus").html('<i>Registered with Sip Server</i>');
            break;
        }
        //If the msg 'Sent request' display that in the web page---Pattience
    case 'sent_request':
        {
            $("#mysipstatus").html('');
            $("#mysipstatus").html('<i>' + e.description + '</i>');
            break;
        }
        //If the msg 'terminated' display that on the web---error maybe?
    case 'terminated':
        {
            $("#mysipstatus").html('');
            $("#mysipstatus").html('<i>' + e.description + '</i>');
            break;
        }
        //If the msg 'i_new_call' the browser has an incoming call
    case 'i_new_call':
        {
            if (mycallSession) {
                // do not accept the incoming call if we're already 'in call'
                e.newSession.hangup(); // comment this line for multi-line support
            } else {
                mycallSession = e.newSession;
                //Change buttons values
                btnCall.value = 'Answer';
                btnHangUp.value = 'Reject';
                btnCall.disabled = false;
                btnHangUp.disabled = false;
                //Start ringing in the browser
                startRingTone();
                //Display in the web page who is calling
                var sRemoteNumber = (mycallSession.getRemoteFriendlyName() || 'unknown');
                $("#mycallstatus").html("<i>Incoming call from [<b>" + sRemoteNumber + "</b>]</i>");
                showNotifICall(sRemoteNumber);
            }
            break;
        }
    case 'm_permission_requested':
        {
            break;
        }
    case 'm_permission_accepted':
    case 'm_permission_refused':
        {
            if (e.type == 'm_permission_refused') {
                btnCall.value = 'Call';
                btnHangUp.value = 'HangUp';
                btnCall.disabled = false;
                btnHangUp.disabled = true;
                mycallSession = null;
                stopRingbackTone();
                stopRingTone();
                $("#mysipstatus").html("<i>" + s_description + "</i>");
            }
            break;
        }
    case 'starting':
    default:
        break;
    }
}
//Function to Listen the call session events

function calllistener(e) {
    //Log all events
    tsk_utils_log_info('****call event**** = ' + e.type);
    switch (e.type) {
        //Display in the web page that the call is connecting
    case 'connected':
    case 'connecting':
        {
            var bConnected = (e.type == 'connected');
            if (e.session == myregisterSession) {
                $("#mycallstatus").html("<i>" + e.description + "</i>");
            } else if (e.type == 'connecting') {
                $("#mycallstatus").html("<i>" + e.description + "</i>");
            } else if (e.session == mycallSession) {
                btnHangUp.value = 'HangUp';
                if (bConnected) {
                    stopRingbackTone();
                    stopRingTone();
                }
            }
            break;
        }
        //Display in the browser teh call is finished
    case 'terminated':
    case 'terminating':
        {
            if (e.session == mycallSession) {
                mycallSession = null;
                myregisterSession = null;
                $("#mycallstatus").html("<i>" + e.description + "</i>");
                stopRingbackTone();
                stopRingTone();
            } else if (e.session == mycallSession) {
                btnCall.value = 'Call';
                btnHangUp.value = 'HangUp';
                btnCall.disabled = false;
                btnHangUp.disabled = true;
                mycallSession = null;
                stopRingbackTone();
                stopRingTone();
            }
            break;
        }
        // future use with video
    case 'm_stream_video_local_added':
        {
            if (e.session == mycallSession) {}
            break;
        }
        //future use with video
    case 'm_stream_video_local_removed':
        {
            if (e.session == mycallSession) {}
            break;
        }
        //future use with video
    case 'm_stream_video_remote_added':
        {
            if (e.session == mycallSession) {}
            break;
        }
        //future use with video
    case 'm_stream_video_remote_removed':
        {
            if (e.session == mycallSession) {}
            break;
        }
        //added media audio todo messaging
    case 'm_stream_audio_local_added':
    case 'm_stream_audio_local_removed':
    case 'm_stream_audio_remote_added':
    case 'm_stream_audio_remote_removed':
        {
            stopRingTone();
            stopRingbackTone();
            break;
        }
        //If the remote end send us a request with SIPresponse 18X start to ringing
    case 'i_ao_request':
        {
            var iSipResponseCode = e.getSipResponseCode();
            if (iSipResponseCode == 180 || iSipResponseCode == 183) {
                startRingbackTone(); //function to start the ring tone
                $("#mycallstatus").html('');
                $("#mycallstatus").html('<i>Remote ringing...</i>');
            }
            break;
        }
        // If the remote send early media stop the sounds
    case 'm_early_media':
        {
            if (e.session == mycallSession) {
                stopRingTone();
                stopRingbackTone();
                $("#mycallstatus").html('');
                $("#mycallstatus").html('<i>Call Answered</i>');
            }
            break;
        }
    }
}
//function to send the SIP Register

function login() {
    //Show in the console that the browser is trying to register
    console.log("Registering");
    //create the session
    myregisterSession = mySipStack.newSession('register', {
        events_listener: {
            events: '*',
            listener: listenerFunc
        } // optional: '*' means all events
    });
    //send the register
    myregisterSession.register();
}
// function to create the sip stack

function startSipStack() {
    //show in the console that th browser is trying to create the sip stack
    console.info("attempting to start the SIP STACK");
    //retreive data from hidden items
    var jrealm = $("#jrealm").val();
    var jusn = $("#jusn").val();
    var jsipuri = $("#jsipuri").val();
    var jpassword = $("#jpassword").val();
    var jcid = $("#jcid").val();
    var jwebsocket = $("#jwebsocket").val();
    var jbreaker = $("#jbreaker").val();
    //console.info("****************ws:"+jwebsocket+"**re"+jrealm+"**uri"+jsipuri+"**cid"+jcid+"**br"+jbreaker+"**usn"+jusn+"**pwd"+jpassword );
    //stack options
    mySipStack = new SIPml.Stack({
        realm: '' + jrealm + '',
        impi: '' + jusn + '',
        impu: '' + jsipuri + '',
        password: '' + jpassword + '',
        // optional
        display_name: '' + jcid + '',
        // optional
        websocket_proxy_url: '' + jwebsocket + '',
        // optional
        //outbound_proxy_url: 'udp://192.168.0.12:5060', // optional
        //ice_servers: [{ url: 'stun:stun.l.google.com:19322'}, { url:'turn:user@numb.viagenie.ca', credential:'myPassword'}], // optional
        enable_rtcweb_breaker: '' + jbreaker + '',
        // optional
        enable_click2call: false,
        // optional
        events_listener: {
            events: '*',
            listener: listenerFunc
        },
        //optional
        sip_headers: [ //optional
        {
            name: 'User-Agent',
            value: 'DM_SIPWEB-UA'
        }, {
            name: 'Organization',
            value: 'Digital-Merge'
        }]
    });
    //If the stack failed show errors in console
    if (mySipStack.start() != 0) {
        console.info("Failed to start Sip Stack");
    } else {
        console.info("Started the Sip Stack");
    }
}
//Fucntion to call/answer

function call() {
    //some variables
    var tocall = $("#callnumber").val();
    var flag = $("#btnCall").val();
    var calltype;
    if ($("#onvideo").is(":checked")) {
        $("#lvideo").text("Disable Video");
        calltype = 'call-audiovideo';
    } else {
        $("#lvideo").text("Enable Video");
        calltype = 'call-audio';
    }
    //If The button to call is CAll and the input text doesn't have a number && the stack failed && there is no Sip session alert and dont call
    if (tocall == '' && flag == 'Call' && mySipStack) {
        alert('Please enter the Number or Uri to Call');
        //If the button call is CALL and the input text has a number to call, then send the call
    } else if (tocall != '' && flag == 'Call' && !mySipStack) {
        alert('The Stack is not ready');
        //If the button call is CALL and the input text has a number to call, then send the call
    } else if (tocall != '' && flag == 'Call' && mySipStack) {
        //create the session to call
        mycallSession = mySipStack.newSession(calltype, {
            audio_remote: document.getElementById('audio_remote'),
            audio_local: document.getElementById('audio_local'),
            video_remote: document.getElementById('video_remote'),
            video_local: document.getElementById('video_local'),
            events_listener: {
                events: '*',
                listener: calllistener
            } // optional: '*' means all events
        });
        //call using the number in the textbox
        mycallSession.call($("#callnumber").val());
        //If the textbox is empty and the button call is ANSWER, then is a incoming call
    } else if (flag == 'Answer' && mySipStack && mycallSession) {
        stopRingbackTone();
        stopRingTone();
        //Accept the session call
        mycallSession.accept({
            audio_remote: document.getElementById('audio_remote'),
            audio_local: document.getElementById('audio_local'),
            events_listener: {
                events: '*',
                listener: calllistener
            } // optional: '*' means all events
        });
    }
}
//function to hangup the call

function hangup() {
    //If exist a call session, hangup and reset button values
    if (mycallSession) {
        mycallSession.hangup({
            events_listener: {
                events: '*',
                listener: calllistener
            }
        });
        stopRingbackTone();
        stopRingTone();
        btnCall.value = 'Call';
        btnHangUp.value = 'HangUp';
        $("#callnumber").attr('value', '');
        $("#mycallstatus").html("Call Terminated")
        //destroy the call session
        mycallSession = null;
    } else {
        $("#callnumber").attr('value', '');
    }
}
//Fucntion to send DTMF frames

function sipSendDTMF(c) {
    if (mycallSession && c) {
        if (mycallSession.dtmf(c) == 0) {
            try {
                dtmfTone.play();
            } catch (e) {}
        }
    } else {
        var lastn = $("#callnumber").val();
        $("#callnumber").val(lastn + c);
        try {
            dtmfTone.play();
        } catch (e) {}
    }
} /**************** fucntion to play sounds *******************/

function startRingTone() {
    try {
        ringtone.play();
    } catch (e) {}
}

function stopRingTone() {
    try {
        ringtone.pause();
    } catch (e) {}
}

function startRingbackTone() {
    try {
        ringbacktone.play();
    } catch (e) {}
}

function stopRingbackTone() {
    try {
        ringbacktone.pause();
    } catch (e) {}
}

function showNotifICall(s_number) {
    // permission already asked when we registered
    if (window.webkitNotifications && window.webkitNotifications.checkPermission() == 0) {
        if (oNotifICall) {
            oNotifICall.cancel();
        }
        oNotifICall = window.webkitNotifications.createNotification('images/sipml-34x39.png', 'Incaming call', 'Incoming call from ' + s_number);
        oNotifICall.onclose = function() {
            oNotifICall = null;
        };
        oNotifICall.show();
    }
}

$(function() {
	/****** On load hide the items **********/
			$("#realm").hide();
			$("#username").hide();
			$("#sipuri").hide();
			$("#password").hide();
			$("#websocket").hide();
			$("#breaker").hide();
			$("#lrealm").hide();
			$("#lusn").hide();
			$("#lsipu").hide();
			$("#lpwd").hide();
			$("#lwsck").hide();
			$("#lcid").hide();
			$("#cid").hide();
			$("#lbreak").hide();
			$("#save").hide();
			$("#note").hide();


	/********** if change the check box hide or show *************/
	$("#settings").click(function(){
		if($(this).is(":checked")){
			$("#lsettings").text("Hide Settings");
			$("#realm").show();
			$("#username").show();
			$("#sipuri").show();
			$("#password").show();
			$("#websocket").show();
			$("#breaker").show();
			$("#lrealm").show();
			$("#lusn").show();
			$("#lsipu").show();
			$("#lpwd").show();
			$("#lwsck").show();
			$("#lbreak").show();
			$("#lcid").show();
			$("#cid").show();
			$("#save").show();
			$("#note").html("<br><p style='font-size:11px;'><b>IMPORTANT: </b><br>Due to my lack of PHP skills you need to double save the settings in order to register the softphone :(</p>"+	
					"<p style='font-family:Helvetica;color:red;font-size: 10px'>"+
					"<b>Note: </b>This feature only works on CHROME web Browser please <a href='https://www.google.com/intl/es/chrome/browser/?hl=es'>click here to download it.</a>"+
					"</p>");
	
		}else{
			$("#lsettings").text("Show Settings");
			$("#realm").hide();
			$("#username").hide();
			$("#sipuri").hide();
			$("#password").hide();
			$("#websocket").hide();
			$("#breaker").hide();
			$("#lrealm").hide();
			$("#lusn").hide();
			$("#lsipu").hide();
			$("#lpwd").hide();
			$("#lwsck").hide();
			$("#lbreak").hide();
			$("#lcid").hide();
			$("#cid").hide();
			$("#save").hide();
			$("#note").hide();
		}							

	});
});