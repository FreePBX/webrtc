var WebrtcC = UCPMC.extend({
	init: function() {
		this.windowId = null;
		this.phone = null;
		this.activeCalls = {};
		this.activeCallId = null;
		this.answering = false;
		this.stick = false;
		this.userBlocked = false;
		this.silenced = false;
		this.autoRegister = false;
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
		var st = $.cookie("webrtc-silenced");
		st = (st === "1") ? true : false;
		this.silence(st);

		var rg = $.cookie("webrtc-register");
		this.autoRegister = (typeof rg === "undefined" || rg === "1") ? true : false;
	},
	settingsDisplay: function() {

	},
	settingsHide: function() {

	},
	/*
	contactClickOptions: function(type) {
		if (type != "number" || !this.staticsettings.enableOriginate) {
			return false;
		}
		return [ { text: _("Originate Call"), function: "contactClickInitiate", type: "phone" } ];
	},
	contactClickInitiate: function(did) {
		var Webrtc = this,
				sfrom = "",
				temp = "",
				name = did,
				selected = "";
		if (UCP.validMethod("Contactmanager", "lookup")) {
			if (typeof UCP.Modules.Contactmanager.lookup(did).displayname !== "undefined") {
				name = UCP.Modules.Contactmanager.lookup(did).displayname;
			} else {
				temp = String(did).length == 11 ? String(did).substring(1) : did;
				if (typeof UCP.Modules.Contactmanager.lookup(temp).displayname !== "undefined") {
					name = UCP.Modules.Contactmanager.lookup(temp).displayname;
				}
			}
		}
		$.each(Webrtc.staticsettings.extensions, function(i, v) {
			sfrom = sfrom + "<option>" + v + "</option>";
		});

		selected = "<option value=\"" + did + "\" selected>" + name + "</option>";
			UCP.showDialog(_("Originate Call"),
			"<label for=\"originateFrom\">From:</label> <select id=\"originateFrom\" class=\"form-control\">" + sfrom + "</select><label for=\"originateTo\">To:</label><select class=\"form-control Tokenize Fill\" id=\"originateTo\" multiple>" + selected + "</select><button class=\"btn btn-default\" id=\"originateCall\" style=\"margin-left: 72px;\">" + _("Originate") + "</button>",
			200,
			250,
			function() {
				$("#originateTo").tokenize({ maxElements: 1, datas: "index.php?quietmode=1&module=webrtc&command=contacts" });
				$("#originateCall").click(function() {
					setTimeout(function() {
						UCP.Modules.Webrtc.originate();
					}, 50);
				});
				$("#originateTo").keypress(function(event) {
					if (event.keyCode == 13) {
						setTimeout(function() {
							UCP.Modules.Webrtc.originate();
						}, 50);
					}
				});
			}
		);
	},
	*/
	engineEvent: function(type, event) {
		console.log("Engine " + type);
		switch (type){
			case "invite":
				this.manageSession(event,"inbound");
			break;
			case "registered":
				$("#nav-btn-webrtc .fa-phone").removeClass("registering");
				$("#webrtc-dc a span").text(_("Disconnect Phone"));
				$("#nav-btn-webrtc .fa-phone").css("color", "green");
			break;
			case "unregistered":
			case "registrationFailed":
				$("#nav-btn-webrtc .fa-phone").removeClass("registering");
				$("#webrtc-dc a span").text(_("Connect Phone"));
				$("#nav-btn-webrtc .fa-phone").css("color", "yellow");
			break;
			case "connected":
				$("#webrtc-dc").removeClass("hidden");
				$("#nav-btn-webrtc .fa-phone").removeClass("connecting");
				$("#nav-btn-webrtc .fa-phone").css("color", "yellow");
				if(this.autoRegister) {
					$("#nav-btn-webrtc .fa-phone").addClass("registering");
				}
			break;
			case "disconnected":
				$("#webrtc-dc").addClass("hidden");
				$("#nav-btn-webrtc .fa-phone").removeClass("connecting");
				$("#nav-btn-webrtc .fa-phone").removeClass("registering");
				$("#nav-btn-webrtc .fa-phone").css("color", "red");
			break;
			case "connecting":
				$("#nav-btn-webrtc .fa-phone").css("color", "red");
				$("#nav-btn-webrtc .fa-phone").addClass("connecting");
				$("#nav-btn-webrtc .fa-phone").removeClass("registering");
			break;
			case "registering": //custom event type
				$("#nav-btn-webrtc .fa-phone").addClass("registering");
			break;
		}
	},
	setPhone: function(stick, s, m) {
		if (typeof stick !== "undefined" && stick) {
			this.stick = true;
		}
		var Webrtc = this,
				message = (typeof m !== "undefined") ? m : "",
				state = (typeof s !== "undefined") ? s : "call";
		if (this.windowId === null) {
			this.windowId = Math.floor((Math.random() * 1000) + 1);
		}
		if ($( "#messages-container .phone-box[data-id=\"" + this.windowId + "\"]").length === 0) {
			UCP.addPhone("Webrtc", this.windowId, state, message, this.contactOptions, function(id, state, message) {
				$("#messages-container .phone-box[data-id=\"" + Webrtc.windowId + "\"] .contactDisplay .contactInfo span").text(message);
				$("#messages-container .phone-box[data-id=\"" + Webrtc.windowId + "\"] .contactDisplay .contactInfo").textfill();
				Webrtc.switchState(state);
			});
		} else {
			$( "#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .message").text(message);
			$("#messages-container .phone-box .message-container").textfill();
			$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay .contactInfo span").text(message);
			$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay .contactInfo").textfill();
			Webrtc.switchState(state);
		}
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
				cnam,
				cnum,
				displayName,
				status,
				call = session,
				uri = call.remoteIdentity.uri;

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

		cnam = this.activeCalls[id].remoteIdentity.displayName || "";
		cnum = this.activeCalls[id].remoteIdentity.uri.user;
		displayName = (cnam !== "") ? cnam + " <" + cnum + ">" : cnum;
		$("#messages-container .phone-box[data-id=\""+this.windowId+"\"] .window .contactDisplay .contactImage").css("background-image",'url("?quietmode=1&module=Webrtc&command=cimage&did='+cnum+'")');
		if (direction === "inbound") {
			this.setPhone(false, "answer", "From: " + displayName);
			if (UCP.notify) {
				this.notification = new Notify(sprintf(_("Incoming call from %s"), displayName), {
					body: _("Click this window to answer or close this window to ignore"),
					icon: "modules/Faxpro/assets/images/fax.png",
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
				this.endCall(data, cause);
				UCP.removeGlobalMessage();
			break;
			case "accepted":
				this.startCall(data);
			break;
			case "progress":
				this.switchState("progress");
			break;
			case "dtmf":
			break;
			case "muted":
			break;
			case "unmuted":
			break;
		}
	},
	endCall: function(message, cause) {
		this.activeCalls[this.activeCallId] = null;
		this.activeCallId = null;
		if (!this.stick) {
			UCP.removePhone(this.windowId);
			this.windowId = null;
		} else {
			this.switchState();
		}
		if (this.notification !== null) {
			this.notification.close();
		}
		if(typeof cause !== "undefined" && cause === SIP.C.causes.USER_DENIED_MEDIA_ACCESS) {
			this.userBlocked = true;
		}
		$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .btn-primary[data-type=\"call\"]").prop("disabled", false);
		this.stopRing();
	},
	startCall: function(event) {
		this.switchState("hangup");
		if (this.notification !== null) {
			this.notification.close();
		}
		this.stopRing();
	},
	silence: function(state) {
		state = (typeof state !== "undefined") ? state : !this.silenced;
		if(!$("#webrtc-silence").length) {
			$("#nav-btn-webrtc .fa-phone").after('<i id="webrtc-silence" class="fa fa-ban fa-stack-2x hidden"></i>');
		}
		if(state) {
			this.stopRing();
			$("#webrtc-silence").removeClass("hidden");
			$("#webrtc-sr .fa-check").removeClass("hidden");
		} else {
			$("#webrtc-silence").addClass("hidden");
			$("#webrtc-sr .fa-check").addClass("hidden");
		}
		$.cookie("webrtc-silenced",(state ? "1" : "0"));
		this.silenced = state;
	},
	call: function(number) {
		if (this.phone.isConnected() && !this.userBlocked) {
			$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .btn-primary[data-type=\"call\"]").prop("disabled", true);
			var session = this.phone.invite(number, this.callOptions);
			this.manageSession(session,"outbound");
		} else if(this.phone.isConnected() && this.userBlocked) {
			alert(_("Unable to start call. Please allow the WebRTC session in your browser and refresh"));
		}
	},
	answer: function() {
		if (this.activeCallId !== null) {
			this.answering = true;
			this.switchState("connecting");
			this.activeCalls[this.activeCallId].accept(this.callOptions);
		}
	},
	toggleHold: function() {
		if (this.activeCallId !== null) {
			var call = this.activeCalls[this.activeCallId],
					holds = this.activeCalls[this.activeCallId].isOnHold(),
					button = $("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window button.secondaction");
			if (!holds.local) {
				button.removeClass().addClass("btn btn-warning secondaction");
				button.text("Unhold");
				call.hold();
			} else {
				button.removeClass().addClass("btn btn-success secondaction");
				button.text("Hold");
				call.unhold();
			}
		}
	},
	DTMF: function(num) {
		if (this.activeCallId !== null) {
			this.activeCalls[this.activeCallId].dtmf(num);
		}
	},
	hangup: function() {
		if (this.activeCallId !== null) {
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
		var button = $("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window button.action"),
				secondbutton = $("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window button.secondaction"),
				input = $("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window input.dialpad"),
				type = (typeof t !== "undefined") ? t : "call";
		button.data("type", type);
		switch (type){
			case "connecting":
				this.stopRing();
				$(".contactInfo span").text("Connecting Please Wait...");
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .activeCallSession").hide();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay").show();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window .actions .right").hide();
				input.prop("disabled", true);
				button.prop("disabled", false);
				button.removeClass().addClass("btn btn-danger action").text("Hangup");
			break;
			case "progress":
				this.playRing();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .activeCallSession").hide();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay").show();
				input.prop("disabled", true);
				button.prop("disabled", false);
				button.removeClass().addClass("btn btn-danger action").text("Hangup");
			break;
			case "answer":
				this.playRing();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .activeCallSession").hide();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay").show();
				secondbutton.removeClass().addClass("btn btn-danger secondaction").text("Ignore");
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window .actions .right").show();
				input.prop("disabled", true);
				button.prop("disabled", false);
				button.removeClass().addClass("btn btn-success action").text("Answer");
			break;
			case "hangup":
				this.stopRing();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .activeCallSession").show();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay").hide();
				secondbutton.removeClass().addClass("btn btn-success secondaction").text("Hold");
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window .actions .right").show();

				input.prop("disabled", false);
				button.prop("disabled", false);
				button.removeClass().addClass("btn btn-danger action").text("Hangup");
			break;
			default:
				this.stopRing();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .activeCallSession").show();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay").hide();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window .actions .right").hide();
				input.prop("disabled", false);
				button.prop("disabled", true);
				button.removeClass().addClass("btn btn-primary action").text("Call");
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
		this.switchState("hangup");
	},
	toggleRegister: function() {
		if(!this.phone.isConnected()) {
			return; //nope
		}
		if($("#nav-btn-webrtc .fa-phone").hasClass("registering")) {
			return; //we are already doing something
		}
		if(!this.phone.isRegistered()) {
			this.engineEvent("registering");
			this.register();
			$.cookie("webrtc-register",1);
		} else {
			this.engineEvent("registering");
			this.unregister();
			$.cookie("webrtc-register",0);
		}

	},
	initiateLibrary: function() {
		var $this = this,
				ver = "0.7.7";
		$.getScript("modules/Webrtc/assets/jssiplibs/sip-" + ver + ".min.js")
		.done(function( script, textStatus ) {
			$("#nav-btn-webrtc").removeClass("hidden");
			UCP.calibrateMenus();
			$("#footer").append("<audio id=\"audio_remote\" autoplay=\"autoplay\" />");
			$("#footer").append("<audio id=\"ringtone\"><source src=\"modules/Webrtc/assets/sounds/ring.mp3\" type=\"audio/mpeg\"></audio>");
			$this.callOptions.media.render.remote = document.getElementById('audio_remote');
			$this.phone = new SIP.UA(
				{
					"wsServers": $this.staticsettings.settings.wsservers,
					"uri": $this.staticsettings.settings.uri,
					"password": $this.staticsettings.settings.password,
					"log": {
						"builtinEnabled": false,
						"level": $this.staticsettings.settings.log
					},
					"register": $this.autoRegister,
					"hackWssInTransport": true,
					"stunServers": $this.staticsettings.settings.iceServers,
					"iceCheckingTimeout": $this.staticsettings.settings.gatheringTimeout,
					// The rtcpMuxPolicy option is being considered for removal and may be removed no earlier than M60, around August 2017.
					// If you depend on it, please see https://www.chromestatus.com/features/5654810086866944 for more details.
					// https://nimblea.pe/monkey-business/2017/01/19/webrtc-asterisk-and-chrome-57/
					// https://issues.asterisk.org/jira/browse/ASTERISK-26732
					"rtcpMuxPolicy": "negotiate"
				}
			);
			if($this.autoRegister) {
				$("#webrtc-dc a span").text(_("Disconnect Phone"));
			} else {
				$("#webrtc-dc a span").text(_("Connect Phone"));
			}

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
		})
		.fail(function( jqxhr, settings, exception ) {
			//could not load script, remove button
		});
	}
});
$(document).bind("staticSettingsFinished", function( event ) {
	if ((typeof UCP.Modules.Webrtc.staticsettings !== "undefined") && UCP.Modules.Webrtc.staticsettings.enabled) {
		if($("html").hasClass("getusermedia")) {
			UCP.Modules.Webrtc.initiateLibrary();
		}
	} else if(typeof UCP.Modules.Webrtc.staticsettings.message !== "undefined") {
		console.warn(UCP.Modules.Webrtc.staticsettings.message);
	}
});
$(document).bind("logIn", function( event ) {
	$("#webrtc-call").on("click", function() {
		UCP.Modules.Webrtc.setPhone(true);
	});
	$("#webrtc-sr").on("click", function() {
		UCP.Modules.Webrtc.silence();
	});
	$("#webrtc-dc").on("click", function() {
		UCP.Modules.Webrtc.toggleRegister();
	});
});
$(document).bind("phoneWindowRemoved", function( event ) {
	UCP.Modules.Webrtc.stick = false;
	this.windowId = null;
	UCP.Modules.Webrtc.hangup();
});
$(document).bind("phoneWindowAdded", function( event ) {
	$("#messages-container .phone-box .keypad td").click(function() {
		var text = $(".phone-box .dialpad").val() + $(this).data("num"),
				button = $(this).parents(".window").find("button.action");
		if (button.data("type") == "call" || button.data("type") == "hangup") {
			if (button.data("type") == "call") {
				$( "#messages-container .phone-box[data-id=\"" + UCP.Modules.Webrtc.windowId + "\"] .message").text("To: " + text);
			}
			$("#messages-container .phone-box .dialpad").val(text);
			UCP.Modules.Webrtc.DTMF($(this).data("num"));
			button.prop("disabled", false);
			$("#messages-container .phone-box .message-container").textfill();
		}
	});
	$("#messages-container .phone-box .clear-input").click(function() {
		var button = $(this).parents(".window").find("button.action");
		$("#messages-container .phone-box .dialpad").val("");
		if (button.data("type") == "call") {
			$( "#messages-container .phone-box[data-id=\"" + UCP.Modules.Webrtc.windowId + "\"] .message").text("");
			button.prop("disabled", true);
		}
	});
	$("#messages-container .phone-box .dialpad").keyup(function() {
		var button = $(this).parents(".window").find("button.action"),
				text = $(".phone-box .dialpad").val();
		if ($(this).val().length === 0 && (button.data("type") == "call")) {
			$( "#messages-container .phone-box[data-id=\"" + UCP.Modules.Webrtc.windowId + "\"] .message").text("");
			button.prop("disabled", true);
		} else {
			$( "#messages-container .phone-box[data-id=\"" + UCP.Modules.Webrtc.windowId + "\"] .message").text("To: " + text);
			UCP.Modules.Webrtc.DTMF(text.slice(-1));
			button.prop("disabled", false);
		}
		$("#messages-container .phone-box .message-container").textfill();
	});
	$("#messages-container .phone-box button.action").click(function() {
		var type = $(this).data("type"),
				num = $("#messages-container .phone-box .dialpad").val();
		switch (type) {
			case "call":
				UCP.Modules.Webrtc.call(num);
			break;
			case "progress":
			case "hangup":
				UCP.Modules.Webrtc.hangup();
			break;
			case "answer":
				UCP.Modules.Webrtc.answer();
			break;
		}
	});
	$("#messages-container .phone-box button.secondaction").click(function() {
		var type = $("#messages-container .phone-box button.action").data("type");
		switch (type) {
			case "hangup":
				UCP.Modules.Webrtc.toggleHold();
			break;
			case "answer":
				UCP.Modules.Webrtc.hangup();
			break;
		}
	});
	$("#messages-container .phone-box .message-container").textfill();
});
$(document).bind("logOut", function( event ) {
	if (typeof UCP.Modules.Webrtc !== "undefined" && UCP.Modules.Webrtc.phone !== null && UCP.Modules.Webrtc.phone.isConnected()) {
		UCP.Modules.Webrtc.phone.stop();
	}
});

$(window).bind("beforeunload", function() {
	if (typeof UCP.Modules.Webrtc !== "undefined" && UCP.Modules.Webrtc.phone !== null && UCP.Modules.Webrtc.phone.isConnected()) {
		UCP.Modules.Webrtc.phone.stop();
	}
});
