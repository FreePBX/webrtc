var WebrtcC = UCPMC.extend({
	init: function() {
		this.phone = null;
		this.activeCalls = {};
		this.activeCallId = null;
		this.answering = false;
		this.userBlocked = false;
		this.silenced = false;
		this.autoRegister = false;
		this.displayState = null;
		this.state = null;
		this.timerObject = null;
		this.callBinds = [
			"progress",
			"accepted",
			"rejected",
			"failed",
			"terminated",
			"cancel",
			"refer",
			"replaced",
			"dtmf",
			"muted",
			"unmuted",
			"bye",
			"addStream"
		];

		this.callOptions = {
			"media": {
				"constraints": {
					"audio": true,
					"video": false
				},
				"render": {
					"remote": null
				}
			}
		};

		this.notification = null;
		var st = Cookies.get("webrtc-silenced");
		st = (st === "1") ? true : false;
		this.silence(st);

		var rg = Cookies.get("webrtc-register");
		this.autoRegister = (typeof rg === "undefined" || rg === "1") ? true : false;
	},
	settingsDisplay: function() {

	},
	settingsHide: function() {

	},
	addSimpleWidget: function(widget_id) {
		this.initiateLibrary();
	},
	displaySimpleWidgetSettings: function(widget_id) {
		var $this = this;

		var st = Cookies.get("webrtc-silenced");
		st = (st === "1") ? true : false;

		$("#webrtc-silence-switch").prop("checked",st);

		$("#webrtc-silence-switch").bootstrapToggle('destroy');
		$("#webrtc-silence-switch").bootstrapToggle({
			on: _("Enable"),
			off: _("Disable")
		});

		$("#webrtc-disconnect-switch").prop("checked",!this.autoRegister);

		$("#webrtc-disconnect-switch").bootstrapToggle('destroy');
		$("#webrtc-disconnect-switch").bootstrapToggle({
			on: _("Enable"),
			off: _("Disable")
		});

		if(this.phone === null) {
			$("#webrtc-silence-switch").bootstrapToggle('disable');
			$("#webrtc-disconnect-switch").bootstrapToggle('disable');
			return;
		}

		$("#webrtc-silence-switch").change(function() {
			$this.silence();
		});
		$("#webrtc-disconnect-switch").change(function(e) {
			$this.toggleRegister();
		});
	},
	displaySimpleWidget: function(widget_id) {
		var $this = this;
		$("#menu_webrtc_phone .status span").text(this.displayState);

		if(this.phone === null) {
			$("#menu_webrtc_phone input.dialpad").prop("disabled",true);
			return;
		}

		if(this.state == "hold") {
			this.switchState('accepted');
			this.switchState('hold');
		} else {
			this.switchState(this.state);
		}

		if(typeof this.phone === "object" && this.phone !== null && this.phone.isRegistered()) {
			$("#menu_webrtc_phone .action").prop("disable",false);
		}

		$("#menu_webrtc_phone .keypad td").click(function() {
			var text = $("#menu_webrtc_phone .dialpad").val() + $(this).data("num"),
					button = $("#menu_webrtc_phone button.action");
			if ($this.state == "registered" || $this.state == "accepted") {
				if ($this.state == "registered") {
					$( "#menu_webrtc_phone .message").text("To: " + text);
				}
				$("#menu_webrtc_phone .dialpad").val(text);
				$this.DTMF($(this).data("num"));
				button.prop("disabled", false);
				$("#menu_webrtc_phone .message-container").textfill();
			}
		});

		$("#menu_webrtc_phone .clear-input").click(function() {
			var button = $("#menu_webrtc_phone button.action");
			$("#menu_webrtc_phone .dialpad").val("");
			if ($this.state == "registered") {
				$( "#menu_webrtc_phone .message").text("");
				button.prop("disabled", true);
			}
		});
		$("#menu_webrtc_phone .dialpad").keyup(function() {
			var button = $(this).parents(".window").find("button.action"),
					text = $("#menu_webrtc_phone .dialpad").val();
			if ($(this).val().length === 0 && ($this.state == "accepted")) {
				$( "#menu_webrtc_phone .message").text("");
				button.prop("disabled", true);
			} else {
				$( "#menu_webrtc_phone .message").text("To: " + text);
				$this.DTMF(text.slice(-1));
				button.prop("disabled", false);
			}
			$("#menu_webrtc_phone .message-container").textfill();
		});
		$("#menu_webrtc_phone button.action").click(function() {
			switch ($this.state) {
				case "registered":
					$this.call($("#menu_webrtc_phone .dialpad").val());
				break;
				case "hold":
				case "accepted":
					$this.hangup();
				break;
				case "invite":
					$this.answer();
				break;
			}
		});
		$("#menu_webrtc_phone button.secondaction").click(function() {
			switch ($this.state) {
				case "hold":
				case "accepted":
					$this.toggleHold();
				break;
				case "invite":
					$this.hangup();
				break;
			}
		});
		$("#menu_webrtc_phone .message-container").textfill();
	},
	deleteSimpleWidget: function(widget_id) {
		if(this.phone !== null) {
			this.disconnect();
		}
	},
	engineEvent: function(type, event) {
		console.log("Engine " + type);
		switch (type){
			case "invite":
				this.manageSession(event,"inbound");
				this.switchState("invite");
			break;
			case "registered":
				this.switchState("registered");
			break;
			case "unregistered":
				this.switchState("unregistered");
			break;
			case "registrationFailed":
				this.switchState("registrationfailed");
			break;
			case "connected":
				this.switchState("connected");
			break;
			case "disconnected":
				this.switchState("disconnected");
			break;
			case "connecting":
				this.switchState("connecting");
			break;
			case "registering": //custom event type
				this.switchState("registering");
			break;
		}
	},
	setDisplayState: function(state) {
		this.displayState = state;
		$("#menu_webrtc_phone .status span").text(this.displayState);
	},
	playRing: function() {
		if(!this.silenced) {
			$("#ringtone").trigger("play");
		}
	},
	stopRing: function() {
		$("#ringtone").trigger("pause");
		$("#ringtone").trigger("load");
	},
	manageSession: function(session, direction) {
		var Webrtc = this,
				id,
				displayName,
				status,
				cnum,
				cnam,
				call = session;

		id = Math.floor((Math.random() * 100000) + 1);
		// If the session exists with active call reject it.
		// TODO this can be useful for call waiting
		if (this.activeCallId) {
			call.terminate();
			return false;
		}

		// If this is a new session create it
		if (!this.activeCallId) {
			this.activeCallId = id;
			this.activeCalls[id] = call;
		}

		cnum = this.activeCalls[id].remoteIdentity.uri.user;
		cnam = this.activeCalls[this.activeCallId].remoteIdentity.displayName || "";
		displayName = (cnam !== "") ? cnam + " <" + cnum + ">" : cnum;
		$("#menu_webrtc_phone .contactDisplay .contactImage").css("background-image",'url("?quietmode=1&module=Webrtc&command=cimage&did='+cnum+'")');
		Webrtc.answering = false;
		if (direction === "inbound") {
			if (UCP.notify) {
				this.notification = new Notify(sprintf(_("Incoming call from %s"), displayName), {
					body: _("Click this window to answer or close this window to ignore"),
					icon: "modules/Webrtc/assets/images/no_user_logo.png", //TODO: get the user logo
					notifyClose: function() {
						if (Webrtc.answering) {
							Webrtc.answering = false;
						} else {
							Webrtc.hangup();
						}
					},
					notifyClick: function() {
						Webrtc.answering = true;
						Webrtc.answer();
						$(".custom-widget[data-widget_rawname=webrtc]").click();
						Webrtc.notification.close();
					}
				});
				this.notification.show();
			}
		}

		$.each(this.callBinds, function(i, v) {
			Webrtc.activeCalls[Webrtc.activeCallId].on(v, function(data, cause) {
				Webrtc.sessionEvent(v, data, cause);
			});
		});
	},
	sessionEvent: function(type, data, cause) {
		console.log("Session " + type);
		switch (type){
			case "terminated":
				this.switchState("terminated");
				this.endCall(data, cause);
			break;
			case "accepted":
				this.switchState("accepted");
				this.startCall(data);
			break;
			case "progress":
				this.switchState("progress");
			break;
			case "dtmf":
				this.switchState("dtmf");
			break;
			case "muted":
				this.switchState("muted");
			break;
			case "unmuted":
				this.switchState("unmuted");
			break;
		}
	},
	endCall: function(message, cause) {
		this.activeCalls[this.activeCallId] = null;
		this.activeCallId = null;
		if (this.notification !== null) {
			this.notification.close();
		}
		if(typeof cause !== "undefined" && cause === SIP.C.causes.USER_DENIED_MEDIA_ACCESS) {
			this.userBlocked = true;
		}
		$("#menu_webrtc_phone .btn-primary").prop("disabled", false);
		this.stopRing();
	},
	startCall: function(event) {
		if (this.notification !== null) {
			this.notification.close();
		}
		this.stopRing();
	},
	silence: function(state) {
		state = (typeof state !== "undefined") ? state : !this.silenced;
		if(!$("#webrtc-silence").length) {
			$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").after('<i id="webrtc-silence" class="fa fa-ban fa-stack-2x hidden"></i>');
		}
		if(state) {
			this.stopRing();
			$("#webrtc-silence").removeClass("hidden");
			$("#webrtc-silence .fa-check").removeClass("hidden");
		} else {
			$("#webrtc-silence").addClass("hidden");
			$("#webrtc-silence .fa-check").addClass("hidden");
		}
		Cookies.set("webrtc-silenced",(state ? "1" : "0"));
		this.silenced = state;
	},
	call: function(number) {
		if (this.phone.isConnected() && !this.userBlocked) {
			$("#menu_webrtc_phone .btn-primary").prop("disabled", true);
			var session = this.phone.invite(number, this.callOptions);
			this.manageSession(session,"outbound");
		} else if(this.phone.isConnected() && this.userBlocked) {
			alert(_("Unable to start call. Please allow the WebRTC session in your browser and refresh"));
		}
	},
	answer: function() {
		if (this.activeCallId !== null) {
			this.answering = true;
			this.activeCalls[this.activeCallId].accept(this.callOptions);
		}
	},
	toggleHold: function() {
		if (this.activeCallId !== null) {
			var call = this.activeCalls[this.activeCallId],
					holds = this.activeCalls[this.activeCallId].isOnHold();
			if (!holds.local) {
				this.switchState("hold");
				call.hold();
			} else {
				this.switchState("unhold");
				call.unhold();
			}
		}
	},
	DTMF: function(num) {
		if (this.state == "accepted" && this.activeCallId !== null) {
			this.activeCalls[this.activeCallId].dtmf(num);
		}
	},
	hangup: function() {
		if ((this.state == "accepted" || this.state == "invite") && this.activeCallId !== null) {
			this.activeCalls[this.activeCallId].terminate();
		}
		this.stopRing();
	},
	poll: function(data) {

	},
	display: function(event) {

	},
	hide: function(event) {

	},
	switchState: function(t) {
		var button = $("#menu_webrtc_phone button.action"),
				secondbutton = $("#menu_webrtc_phone button.secondaction"),
				input = $("#menu_webrtc_phone input.dialpad"),
				type = (typeof t !== "undefined" && t !== null) ? t : "registered",
				$this = this;
		this.state = type;
		console.log(type);
		button.data("type", type);
		switch (type){
			case "invite":
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").addClass("shake");
				this.playRing();
				$("#menu_webrtc_phone .activeCallSession .keypad").hide();
				$("#menu_webrtc_phone .activeCallSession .input-container").hide();
				$("#menu_webrtc_phone .contactDisplay").show();
				secondbutton.removeClass().addClass("btn btn-danger secondaction").text("Ignore");
				$("#menu_webrtc_phone .actions .right").show();
				button.removeClass().addClass("btn btn-success action").text("Answer");
				button.prop("disabled", false);
			break;
			case "hold":
				secondbutton.removeClass().addClass("btn btn-success secondaction").text("UnHold");
				secondbutton.css("background-color","orange");
				if(!$("#webrtc-hold").length) {
					$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").after('<i id="webrtc-hold" class="fa fa-pause fa-stack-2x blink hidden"></i>');
				}
				$("#webrtc-hold").removeClass("hidden");
			break;
			case "unhold":
				secondbutton.removeClass().addClass("btn btn-success secondaction").text("Hold");
				secondbutton.css("background-color","");
				if($("#webrtc-hold").length) {
					$("#webrtc-hold").addClass("hidden");
				}

				this.state = "accepted";
			break;
			case "accepted":
				this.stopRing();
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").removeClass("shake");
				$("#menu_webrtc_phone .contactDisplay").hide();
				$("#menu_webrtc_phone .activeCallSession .keypad").show();
				$("#menu_webrtc_phone .activeCallSession .input-container").show();
				secondbutton.removeClass().addClass("btn btn-success secondaction").text("Hold");
				secondbutton.css("color","");
				$("#menu_webrtc_phone .actions .right").show();

				input.prop("disabled", false);
				button.prop("disabled", false);
				button.removeClass().addClass("btn btn-danger action").text("Hangup");
				$("#menu_webrtc_phone .contact-info").addClass("in");
				$("#webrtc-timer-container").remove();
				clearInterval(this.timerObject);
				$('#webrtc-disconnect-switch').bootstrapToggle('disable');
				var updateTimer = function() {
					if($this.activeCallId === null) {
						clearInterval($this.timerObject);
						$("#menu_webrtc_phone .contact-info").removeClass("in");
						$('#webrtc-disconnect-switch').bootstrapToggle('enable');
						return;
					}
					//
					var start = moment($this.activeCalls[$this.activeCallId].startTime);
					var end = moment();
					var duration = moment.duration(end.diff(start));

					var padLeft = function(nr){
						return Array(2-String(nr).length+1).join('0')+nr;
					};

					var time = padLeft(duration.hours())+":"+padLeft(duration.minutes())+":"+padLeft(duration.seconds());

					if($("#menu_webrtc_phone .contact-info .timer").is(":visible")) {
						$("#menu_webrtc_phone .contact-info .timer").text(time);
					} else {
						if(!$("#webrtc-timer-container").length) {
							$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").after('<div id="webrtc-timer-container"><div class="timer">'+time+'</div></div>');
						} else {
							$("#webrtc-timer-container .timer").text(time);
						}
					}
				};
				updateTimer();
				this.timerObject = setInterval(updateTimer,1000);

				var cnam = this.activeCalls[this.activeCallId].remoteIdentity.displayName || "",
						cnum = this.activeCalls[this.activeCallId].remoteIdentity.uri.user,
						displayName = (cnam !== "") ? cnam + " <" + cnum + ">" : cnum;
				$("#menu_webrtc_phone .contact-info .contact").text(displayName);
			break;
			case "terminated":
				this.stopRing();
				$("#menu_webrtc_phone .actions .right").hide();
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").removeClass("shake");
				$("#menu_webrtc_phone .activeCallSession .keypad").show();
				$("#menu_webrtc_phone .activeCallSession .input-container").show();
				$("#menu_webrtc_phone .contactDisplay").hide();
				button.removeClass().addClass("btn btn-primary action").text("Call");
				$("#menu_webrtc_phone .contact-info .contact").text("");
				this.state = "registered";
			break;
			case "registered":
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").removeClass("registering");
				this.setDisplayState(_("Registered"));
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "green");
				input.prop("disabled", false);
				input.val("");
				$("#menu_webrtc_phone .keypad").removeClass("disable");
				button.prop("disabled", true);
				$("#menu_webrtc_phone .actions .right").hide();
				button.removeClass().addClass("btn btn-primary action").text("Call");
			break;
			case "unregistered":
				this.setDisplayState(_("Unregistered"));
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").removeClass("registering");
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "yellow");
				$("#menu_webrtc_phone .keypad").addClass("disable");
				input.prop("disabled", true);
				input.val("");
			break;
			case "registrationfailed":
				this.setDisplayState(_("Registration Failed"));
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").removeClass("registering");
				$("#webrtc-dc a span").text(_("Connect Phone"));
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "red");
				$("#menu_webrtc_phone .keypad").addClass("disable");
				input.prop("disabled", true);
				input.val("");
			break;
			case "connected":
				this.setDisplayState(_("Unregistered"));
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").removeClass("connecting");
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "yellow");
			break;
			case "disconnected":
				this.setDisplayState(_("Disconnected"));
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").removeClass("connecting");
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").removeClass("registering");
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "red");
				$("#menu_webrtc_phone .keypad").addClass("disable");
				input.prop("disabled", true);
				input.val("");
			break;
			case "connecting":
				this.setDisplayState(_("Connecting to socket..."));
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "red");
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").addClass("connecting");
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").removeClass("registering");
			break;
			case "registering": //custom event type
				this.setDisplayState(_("Registering..."));
				$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").addClass("registering");
			break;
		}
	},
	connect: function() {
		if ((typeof this.staticsettings !== "undefined") &&
				this.staticsettings.enabled &&
				Modernizr.getusermedia &&
				this.phone !== null &&
				!this.phone.isConnected()) {
			this.phone.start();
		}
	},
	disconnect: function() {
		if (this.phone !== null &&
				this.phone.isConnected()) {
			this.phone.stop();
		}
	},
	register: function() {
		if(!this.phone.isConnected()) {
			this.connect();
		}
		if (this.phone !== null &&
				!this.phone.isRegistered()) {
		}
		this.phone.register();
	},
	unregister: function() {
		if(!this.phone.isConnected()) {
			throw "Phone is not connected, nothing to register";
		}
		if (this.phone !== null &&
				this.phone.isRegistered()) {
		}
		this.phone.unregister();
	},
	toggleRegister: function() {
		if(!this.phone.isConnected()) {
			return; //nope
		}
		if($(".custom-widget[data-widget_rawname=webrtc] .fa-phone").hasClass("registering")) {
			return; //we are already doing something
		}
		if(!this.phone.isRegistered()) {
			this.register();
			Cookies.set("webrtc-register",1);
		} else {
			this.unregister();
			Cookies.set("webrtc-register",0);
		}

	},
	initiateLibrary: function() {
		var $this = this,
				ver = "0.7.7";

		if(typeof SIP === "object") {
			return;
		}

		if(!$("html").hasClass("getusermedia")) {
			$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "red");
			this.setDisplayState(_("Not supported in this browser"));
			console.warn("WebRTC is not supported in this browser");
			return;
		}

		if(document.location.protocol !== "https:") {
			$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "red");
			this.setDisplayState(_("Only supported over HTTPS"));
			console.warn("WebRTC is not supported in non-SSL mode");
			return;
		}

		if(!$(".custom-widget[data-widget_rawname=webrtc]").length) {
			$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "red");
			console.warn("WebRTC Widget has not been added");
			return;
		}

		if(typeof moduleSettings.Webrtc === "undefined") {
			$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "red");
			console.warn("WebRTC is not configured properly");
			return;
		}

		if(!moduleSettings.Webrtc.enabled) {
			$(".custom-widget[data-widget_rawname=webrtc] .fa-phone").css("color", "red");
			console.warn(moduleSettings.Webrtc.message);
			this.setDisplayState(moduleSettings.Webrtc.message);
			return;
		}

		$.getScript("modules/Webrtc/assets/jssiplibs/sip-" + ver + ".min.js")
		.done(function( script, textStatus ) {
			$("#footer").append("<audio id=\"audio_remote\" autoplay=\"autoplay\" />");
			$("#footer").append("<audio id=\"ringtone\"><source src=\"modules/Webrtc/assets/sounds/ring.mp3\" type=\"audio/mpeg\"></audio>");
			$this.callOptions.media.render.remote = document.getElementById('audio_remote');
			$this.phone = new SIP.UA(
				{
					"wsServers": moduleSettings.Webrtc.settings.wsservers,
					"uri": moduleSettings.Webrtc.settings.uri,
					"password": moduleSettings.Webrtc.settings.password,
					"log": {
						"builtinEnabled": false,
						"level": moduleSettings.Webrtc.settings.log
					},
					"register": $this.autoRegister,
					"hackWssInTransport": true,
					"stunServers": moduleSettings.Webrtc.settings.iceServers,
					"iceCheckingTimeout": moduleSettings.Webrtc.settings.gatheringTimeout,
					// The rtcpMuxPolicy option is being considered for removal and may be removed no earlier than M60, around August 2017.
					// If you depend on it, please see https://www.chromestatus.com/features/5654810086866944 for more details.
					// https://nimblea.pe/monkey-business/2017/01/19/webrtc-asterisk-and-chrome-57/
					// https://issues.asterisk.org/jira/browse/ASTERISK-26732
					"rtcpMuxPolicy": "negotiate"
				}
			);

			var binds = [
				"connected",
				"disconnected",
				"registered",
				"unregistered",
				"registrationFailed",
				"invite",
				"message",
				"connecting"
				];
			$.each(binds, function(i, v) {
				$this.phone.on(v, function(e) {
					$this.engineEvent(v, e);
				});
			});

			$this.connect();
		}).fail(function( jqxhr, settings, exception ) {
			//could not load script, remove button
		});
	}
});

$(document).bind("logIn", function( event ) {
	console.log("loggedin");
});

$(document).bind("logOut", function( event ) {
	if (typeof UCP.Modules.Webrtc !== "undefined" && UCP.Modules.Webrtc.phone !== null && UCP.Modules.Webrtc.phone.isConnected()) {
		UCP.Modules.Webrtc.disconnect();
	}
});

$(window).bind("beforeunload", function() {
	if (typeof UCP.Modules.Webrtc !== "undefined" && UCP.Modules.Webrtc.phone !== null && UCP.Modules.Webrtc.phone.isConnected()) {
		UCP.Modules.Webrtc.disconnect();
	}
});
