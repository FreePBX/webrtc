var WebrtcC = UCPMC.extend({
	init: function() {
		this.windowId = null;
		this.phone = null;
		this.callSession = null;
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
				Webrtc.switchState(state);
			});
		} else {
			$( "#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .message").text(m);
			$("#messages-container .phone-box .message-container").textfill();
			Webrtc.switchState(state);
		}
	},
	manageSession: function(e) {
		var displayName,
				status,
				request = e.data.request,
				call = e.data.session,
				uri = call.remote_identity.uri;

		displayName = call.remote_identity.display_name || call.remote_identity.uri.user;
		displayName = displayName + " " + call.remote_identity.uri.user;
		//UCP.displayGlobalMessage("New Incomming Call from " + displayName, "lightgreen", 1);
		if (call.direction === "incoming") {
			this.setPhone("answer", "From: " + displayName);
			if (UCP.notify) {
				this.notification = new Notify("Call from " + displayName, {
					body: "You have an incoming call from " + displayName,
					icon: "modules/Faxpro/assets/images/fax.png"
				});
				this.notification.show();
			}
		}
		// If the session exists with active call reject it.
		// TODO this can be useful for call waiting
		if (this.callSession) {
			call.terminate();
			return false;
		}

		// If this is a new session create it
		if (!this.callSession) {
			this.callSession = call;
		}

		$.each(this.callBinds, function(i, v) {
			Webrtc.callSession.on(v, function(e) {
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
		}
	},
	endCall: function(event) {
		this.callSession = null;
		this.switchState();
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
			this.notification.close();
		}
	},
	call: function(number) {
		if (this.phone.isConnected()) {
			this.phone.call(num, this.callOptions);
		}
	},
	answer: function() {
		if (this.callSession !== null) {
			this.callSession.answer(this.callOptions);
		}
	},
	toggleHold: function() {
		if (this.callSession !== null) {
			var holds = this.callSession.isOnHold(),
					button = $("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window button.secondaction");
			if (!holds.local) {
				button.removeClass().addClass("btn btn-warning secondaction");
				button.text("Unhold");
				this.callSession.hold();
			} else {
				button.removeClass().addClass("btn btn-success secondaction");
				button.text("Hold");
				this.callSession.unhold();
			}
		}
	},
	sendDTMF: function(num) {
		if (this.callSession !== null) {
			this.callSession.sendDTMF(num);
		}
	},
	hangup: function() {
		if (this.callSession !== null) {
			this.callSession.terminate();
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
				input = $("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window input.dialpad");
		button.data("type", type);
		switch (type){
			case "answer":
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window .actions .right").hide();
				input.prop("disabled", true);
				button.prop("disabled", false);
				button.removeClass().addClass("btn btn-success action").text("Answer");
			break;
			case "hangup":
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window .actions .right").show();
				input.prop("disabled", false);
				button.prop("disabled", false);
				button.removeClass().addClass("btn btn-danger action").text("Hangup");
			break;
			default:
				$("#messages-container .phone-box[data-id=\"" + this.windowId + "\"] .window .actions .right").hide();
				input.prop("disabled", false);
				button.prop("disabled", true);
				button.removeClass().addClass("btn btn-primary action").text("Call");
			break;
		}

	}
}), Webrtc = new WebrtcC();
$(document).bind("staticSettingsFinished", function( event ) {
	if (Webrtc.staticsettings.enabled) {
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

		/* Already bound I guess
		Webrtc.callOptions.eventHandlers = {};
		$.each(Webrtc.callBinds, function(i, v) {
			Webrtc.callOptions.eventHandlers[v] = function(e) { Webrtc.sessionEvent(e); };
		});
		*/

		Webrtc.phone.start();
	}
});
$(document).bind("logIn", function( event ) {
	$("#presence-menu2 .options .actions i[data-module=\"Webrtc\"]").on("click", function() {
		Webrtc.setPhone();
	});
});
$(document).bind("phoneWindowRemoved", function( event ) {
	if (Webrtc.callSession !== null) {
		Webrtc.callSession.terminate();
	}
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
			case "hangup":
				Webrtc.hangup();
			break;
			case "answer":
				Webrtc.answer();
			break;
		}
	});
	$("#messages-container .phone-box button.secondaction").click(function() {
		Webrtc.toggleHold();
	});
	$("#messages-container .phone-box .message-container").textfill();
});
$(document).bind("logOut", function( event ) {
	if (Webrtc.staticsettings.enabled && Webrtc.phone.isConnected()) {
		Webrtc.phone.stop();
	}
});

$(window).bind("beforeunload", function() {
	if (Webrtc.staticsettings.enabled && Webrtc.phone.isConnected()) {
		Webrtc.phone.stop();
	}
});
