var WebrtcC = UCPMC.extend({
	init: function() {
		this.windowId = null;
		this.phone = null;
		this.activeCalls = {};
		this.activeCallId = null;
		this.callBinds = [
			"connecting",
			"progress",
			"started",
			"ended",
			"failed",
			"newDTMF",
			"hold",
			"unhold"
		];

		this.callOptions = {
			"mediaConstraints": {
				"audio": true,
				"video": false
			}
		};

		this.notification = null;
	},
	settingsDisplay: function() {

	},
	settingsHide: function() {

	},
	engineEvent: function(event) {
		console.log(event.type);
		switch (event.type){
			case "newRTCSession":
				this.manageSession(event);
			break;
		}
	},
	setPhone: function(s, m) {
		var message = (typeof m !== undefined) ? m : "",
				state = (typeof s !== undefined) ? s : "call";
		if (this.windowId === null) {
			this.windowId = Math.floor((Math.random() * 1000) + 1);
		}
		if ($( "#messages-container .phone-box[data-id=\"" + this.windowId + "\"]").length === 0) {
			UCP.addPhone("Webrtc", this.windowId, state, message, function(id, state, message) {
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
	manageSession: function(e) {
		var id,
				cnam,
				cnum,
				displayName,
				status,
				request = e.data.request,
				call = e.data.session,
				uri = call.remote_identity.uri;

		id = Math.floor((Math.random() * 100000) + 1);
		// If the session exists with active call reject it.
		// TODO this can be useful for call waiting
		if (this.activeCallId) {
			console.log("I got another call");
			call.terminate();
			return false;
		}

		// If this is a new session create it
		if (!this.activeCallId) {
			this.activeCallId = id;
			this.activeCalls[id] = call;
		}

		cnam = this.activeCalls[id].remote_identity.display_name || "";
		cnum = this.activeCalls[id].remote_identity.uri.user;
		displayName = (cnam !== "") ? cnam + " <" + cnum + ">" : cnum;
		if (this.activeCalls[id].direction === "incoming") {
			this.setPhone("answer", "From: " + displayName);
			if (UCP.notify) {
				this.notification = new Notify("Call from " + displayName, {
					body: "You have an incoming call from " + displayName,
					icon: "modules/Faxpro/assets/images/fax.png"
				});
				this.notification.show();
			}
		}

		$.each(this.callBinds, function(i, v) {
			Webrtc.activeCalls[Webrtc.activeCallId].on(v, function(e) {
				Webrtc.sessionEvent(e);
			});
		});
	},
	sessionEvent: function(event) {
		console.log(event.type);
		switch (event.type){
			case "failed":
				this.endCall(event);
				UCP.removeGlobalMessage();
			break;
			case "ended":
				this.endCall(event);
				UCP.removeGlobalMessage();
			break;
			case "started":
				this.startCall(event);
			break;
			case "progress":
				this.switchState("progress");
			break;
		}
	},
	endCall: function(event) {
		this.activeCalls[this.activeCallId] = null;
		this.activeCallId = null;
		UCP.removePhone(this.windowId);
		this.windowId = null;
		if (this.notification !== null) {
			this.notification.close();
		}
	},
	startCall: function(event) {
		var rtcSession = event.sender,
				call = event.data.session,
				displayName;

		// Attach remote stream to remoteView
		if (rtcSession.getRemoteStreams().length > 0) {
			$("#audio_remote").prop("src", window.URL.createObjectURL(rtcSession.getRemoteStreams()[0]));
			this.switchState("hangup");
			if (this.notification !== null) {
				this.notification.close();
			}
		}
	},
	call: function(number) {
		if (this.phone.isConnected()) {
			this.phone.call(number, this.callOptions);
		}
	},
	answer: function() {
		if (this.activeCallId !== null) {
			this.activeCalls[this.activeCallId].answer(this.callOptions);
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
	sendDTMF: function(num) {
		if (this.activeCallId !== null) {
			this.activeCalls[this.activeCallId].sendDTMF(num);
		}
	},
	hangup: function() {
		if (this.activeCallId !== null) {
			this.activeCalls[this.activeCallId].terminate();
		}
	},
	poll: function(data) {

	},
	display: function(event) {

	},
	hide: function(event) {

	},
	switchState: function(type) {
		var button = $("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window button.action"),
				secondbutton = $("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window button.secondaction"),
				input = $("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window input.dialpad");
		button.data("type", type);
		switch (type){
			case "progress":
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .activeCallSession").hide();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay").show();
				input.prop("disabled", true);
				button.prop("disabled", false);
				button.removeClass().addClass("btn btn-danger action").text("Hangup");
			break;
			case "answer":
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .activeCallSession").hide();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay").show();
				secondbutton.removeClass().addClass("btn btn-danger secondaction").text("Ignore");
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window .actions .right").show();
				input.prop("disabled", true);
				button.prop("disabled", false);
				button.removeClass().addClass("btn btn-success action").text("Answer");
			break;
			case "hangup":
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .activeCallSession").show();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay").hide();
				secondbutton.removeClass().addClass("btn btn-success secondaction").text("Hold");
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window .actions .right").show();
				input.prop("disabled", false);
				button.prop("disabled", false);
				button.removeClass().addClass("btn btn-danger action").text("Hangup");
			break;
			default:
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .activeCallSession").show();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .contactDisplay").hide();
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window .actions .right").hide();
				input.prop("disabled", false);
				button.prop("disabled", true);
				button.removeClass().addClass("btn btn-primary action").text("Call");
			break;
		}

	}
}), Webrtc = new WebrtcC();
$(document).bind("staticSettingsFinished", function( event ) {
	if ((typeof Webrtc.staticsettings !== "undefined") && Webrtc.staticsettings.enabled) {
		$("#footer").append("<audio id=\"audio_remote\" autoplay=\"autoplay\" />");
		Webrtc.phone = new JsSIP.UA(
			{
				"ws_servers": Webrtc.staticsettings.settings.wsservers,
				"uri": Webrtc.staticsettings.settings.uri,
				"password": Webrtc.staticsettings.settings.password
			}
		);
		var binds = [
			"connecting",
			"connected",
			"disconnected",
			"registered",
			"unregistered",
			"registrationFailed",
			"newRTCSession",
			"newMessage"
			];
		$.each(binds, function(i, v) {
			Webrtc.phone.on(v, function(e) {
				Webrtc.engineEvent(e);
			});
		});

		Webrtc.phone.start();
	}
});
$(document).bind("logIn", function( event ) {
	$("#presence-menu2 .options .actions div[data-module=\"Webrtc\"]").on("click", function() {
		Webrtc.setPhone();
	});
});
$(document).bind("phoneWindowRemoved", function( event ) {
	Webrtc.hangup();
});
$(document).bind("phoneWindowAdded", function( event ) {
	$("#messages-container .phone-box .keypad td").click(function() {
		var text = $(".phone-box .dialpad").val() + $(this).data("num"),
				button = $(this).parents(".window").find("button.action");
		if (button.data("type") == "call" || button.data("type") == "hangup") {
			if (button.data("type") == "call") {
				$( "#messages-container .phone-box[data-id=\"" + Webrtc.windowId + "\"] .message").text("To: " + text);
			}
			$("#messages-container .phone-box .dialpad").val(text);
			Webrtc.sendDTMF($(this).data("num"));
		}
		button.prop("disabled", false);
		$("#messages-container .phone-box .message-container").textfill();
	});
	$("#messages-container .phone-box .clear-input").click(function() {
		var button = $(this).parents(".window").find("button.action");
		$("#messages-container .phone-box .dialpad").val("");
		if (button.data("type") == "call") {
			$( "#messages-container .phone-box[data-id=\"" + Webrtc.windowId + "\"] .message").text("");
			button.prop("disabled", true);
		}
	});
	$("#messages-container .phone-box .dialpad").keyup(function() {
		var button = $(this).parents(".window").find("button.action"),
				text = $(".phone-box .dialpad").val();
		if ($(this).val().length === 0 && (button.data("type") == "call")) {
			$( "#messages-container .phone-box[data-id=\"" + Webrtc.windowId + "\"] .message").text("");
			button.prop("disabled", true);
		} else {
			$( "#messages-container .phone-box[data-id=\"" + Webrtc.windowId + "\"] .message").text("To: " + text);
			Webrtc.sendDTMF(text.slice(-1));
			button.prop("disabled", false);
		}
		$("#messages-container .phone-box .message-container").textfill();
	});
	$("#messages-container .phone-box button.action").click(function() {
		var type = $(this).data("type"),
				num = $("#messages-container .phone-box .dialpad").val();
		switch (type) {
			case "call":
				Webrtc.call(num);
			break;
			case "progress":
			case "hangup":
				Webrtc.hangup();
			break;
			case "answer":
				Webrtc.answer();
			break;
		}
	});
	$("#messages-container .phone-box button.secondaction").click(function() {
		var type = $("#messages-container .phone-box button.action").data("type");
		switch (type) {
			case "hangup":
				Webrtc.toggleHold();
			break;
			case "answer":
				Webrtc.hangup();
			break;
		}
	});
	$("#messages-container .phone-box .message-container").textfill();
});
$(document).bind("logOut", function( event ) {
	if ((typeof Webrtc.staticsettings !== "undefined") && Webrtc.staticsettings.enabled && Webrtc.phone.isConnected()) {
		Webrtc.phone.stop();
	}
});

$(window).bind("beforeunload", function() {
	if ((typeof Webrtc.staticsettings !== "undefined") && Webrtc.staticsettings.enabled && Webrtc.phone.isConnected()) {
		Webrtc.phone.stop();
	}
});
