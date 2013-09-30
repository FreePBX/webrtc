//global caller volume, doesnt affect keys
var gvolume = 100;
//global call hold state
var callheld = false;
//global mute state
var unmuted = true;
//global shift key state
var shifted = false;

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
			if(callSession && num){
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
			} else if(num) {
				//play our response
				$('#adtmf' + num).trigger('play');
				//get lcd_2 values
				var pre = $('#lcd_2').html();
				//append our output to that
				$('#lcd_2').html(pre+num)
			}
			//TODO: Image replacement, terrible
			var src = $('#dtmf' + num).attr("src").replace("images/","images/push/");
			$('#dtmf' + num).attr("src", src);
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
			//TODO: Image replacement, terrible
			var src = $('#dtmf_' + idt).attr("src").replace("images/","images/push/");
			$('#dtmf_' + idt).attr("src", src);
		//backspace key, dont allow backspace while in a call (it doesnt make sense)
		} else if(e.keyCode == 8 && !callSession) {
			var pre = $('#lcd_2').html();
			//dont allow taking off too much of nothing
			if(pre != '') {
				pre = pre.substring(0, pre.length -1);
			}
			$('#lcd_2').html(pre)
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
			var src = $('#dtmf' + num).attr("src").replace("images/push/","images/");
			$('#dtmf' + num).attr("src", src);
		}
		return false;
	});
	
	//webbutton class
	$('.webrtcbutton')
		.mouseup(function() { //mouse up
			var aid = $(this).attr("data-audio");
			var src = $(this).attr("src").replace("images/push/","images/");
			$(this).attr("src", src);
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
			if(callSession && num && dtmf){
				if(callSession.dtmf(num) == 0){
					$('#a' + aid).trigger('play');
					var pre = $('#lcd_2').html();
					$('#lcd_2').html(pre+num)
				}
			//not in an active call, send to screen
			} else if(num && dtmf) {
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
				}
			//volume up
			} else if(aid == 'volup') {
				//TODO: I think we could use jquery here
				speaker=document.getElementById("audio_remote");
				//prevent impossible volumes
				if(gvolume < 100) {
					gvolume = (gvolume + 10);
					speaker.volume=gvolume/100;
				}
			//detect hold button and hold state
			} else if(callSession && aid == 'hold') {
				alert('This functionality is currently broken in Asterisk')
				/*
				if(!callheld) {
					callSession.hold();
					callheld = true;
				} else {
					callSession.resume();
					callheld = false
				}
				*/
			//detect hangup
			} else if(callSession && aid == 'hangup') {
				//and hang it up....hahang it up
				callSession.hangup();
			//detect answering *inbound* call, notice we check the state of callSession
			} else if(callSession && aid == 'answer') {
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
			//hit answer button but not in a call so we are initiating a call (notice false callSession)
			} else if(!callSession && aid == 'answer') {
				//setup new callSession for our call
			    callSession = sipStack.newSession('call-audio', {
			        audio_remote: document.getElementById('audio_remote'),
			        events_listener: { events: '*', listener: eventsListener } // optional: '*' means all events
			    });
				//get lcd_2 value
				var number = $('#lcd_2').html();
				if(number != '') {
					//send lcd_2 value as a 'number'
			    	callSession.call(number);
					//TODO: Probably remove this, it sounds weird sometimes when it gets doubled.
					startRingbackTone();
				}
			//Local Microphone mute state
			//TODO: Needs image 'stick'
			} else if(callSession && aid == 'mute') {
				if(unmuted) {
					unmuted = false;
				} else {
					unmuted = true
				}
				muteMicrophone(unmuted);
			}
			//TODO: fix this please
			var src = $(this).attr("src").replace("images/","images/push/");
			$(this).attr("src", src);
		});
		//initalize the engine
		SIPml.init(readyCallback, errorCallback);
		//start your engines
		sipStack.start();
		
		//...and were off!
});

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
	if(refreshIntervalId != null) {
		//we have a timer running, this is bad so stop it
		clearInterval(refreshIntervalId);
	}
	refreshIntervalId = setInterval( function(){
	    $("#seconds").html(pad(++sec%60));
	    $("#minutes").html(pad(parseInt(sec/60,10)));
	}, 1000);
}