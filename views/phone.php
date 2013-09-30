<table width="670" height="544" border="0" align="center" cellpadding="0" cellspacing="0" background="assets/webrtc/images/phone-bg3.png">
	<tr>
		<td colspan="3" height="5"></td>
	</tr>
	<tr>
		<td align="center" valign="bottom">
			<table width="425" height="237" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td width="196" rowspan="3" valign="top">
						<img id="dtmf1" data-audio="dtmf1" class="webrtcbutton" src="assets/webrtc/images/key1.png" width="55" height="55" /><img id="dtmf2" data-audio="dtmf2" class="webrtcbutton" src="assets/webrtc/images/key2.png" width="55" height="55" hspace="15" /><img id="dtmf3" data-audio="dtmf3" class="webrtcbutton" src="assets/webrtc/images/key3.png" width="55" height="55" />
						<img id="dtmf4" data-audio="dtmf4" class="webrtcbutton" src="assets/webrtc/images/key4.png" width="55" height="55" vspace="4" /><img id="dtmf5" data-audio="dtmf5" class="webrtcbutton" src="assets/webrtc/images/key5.png" width="55" height="55" hspace="15" vspace="4" /><img id="dtmf6" data-audio="dtmf6" class="webrtcbutton" src="assets/webrtc/images/key6.png" width="55" height="55" vspace="4" />
						<img id="dtmf7" data-audio="dtmf7" class="webrtcbutton" src="assets/webrtc/images/key7.png" width="55" height="55" /><img id="dtmf8" data-audio="dtmf8" class="webrtcbutton" src="assets/webrtc/images/key8.png" width="55" height="55" hspace="15" /><img id="dtmf9" data-audio="dtmf9" class="webrtcbutton" src="assets/webrtc/images/key9.png" width="55" height="55" />
						<img id="dtmf_s" data-audio="dtmf_s" class="webrtcbutton" src="assets/webrtc/images/key-asterisk.png" width="55" height="55" vspace="4" /><img id="dtmf0" data-audio="dtmf0" class="webrtcbutton" src="assets/webrtc/images/key0.png" width="55" height="55" hspace="15" vspace="4" /><img id="dtmf_p" data-audio="dtmf_p" class="webrtcbutton" src="assets/webrtc/images/key-pound.png" width="55" height="55" vspace="4" />
					</td>
					<td width="39" rowspan="3">&nbsp;</td>
					<td width="190" height="45" valign="top" background="assets/webrtc/images/text-box.png">
						<div style="padding:10px;font-size:75%;white-space:nowrap;"><div id="lcd_1"></div><div id="lcd_2"></div></div>
					</td>
				</tr>
				<tr>
					<td valign="top" height="7"></td>
				</tr>
				<tr>
					<td align="right" valign="top"><img data-audio="answer" class="webrtcbutton"  src="assets/webrtc/images/answer-btn.png" align="left" /><img data-audio="hangup" class="webrtcbutton" src="assets/webrtc/images/hangup-btn.png" /><br />
						<img data-audio="hold" class="webrtcbutton" src="assets/webrtc/images/hold-btn.png" align="left" /><img data-audio="mute" class="webrtcbutton"  src="assets/webrtc/images/mute-btn.png" /><br />
						<img data-audio="transfer" class="webrtcbutton" src="assets/webrtc/images/transfer-btn.png" align="left" /><img data-audio="voldwn" class="webrtcbutton" src="assets/webrtc/images/vol-dwn-btn.png" /><br />
						<img data-audio="conference" class="webrtcbutton" src="assets/webrtc/images/conf-btn.png" align="left" /><img data-audio="volup" class="webrtcbutton" src="assets/webrtc/images/vol-up-btn.png" />
					</td>
				</tr>
			</table>
			<br />
			<table width="125" height="140" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td align="center" valign="middle"><img src="assets/webrtc/images/message-btn.png" width="108" height="110" /></td>
				</tr>
			</table>
			<table width="670" height="48" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td>&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<audio id="audio_remote" autoplay="autoplay" />
<audio id="ringtone"> 
	<source src="assets/webrtc/sounds/ring.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf1"> 
	<source src="assets/webrtc/sounds/dtmf1.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf2">
	<source src="assets/webrtc/sounds/dtmf2.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf3">
	<source src="assets/webrtc/sounds/dtmf3.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf4">
	<source src="assets/webrtc/sounds/dtmf4.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf5">
	<source src="assets/webrtc/sounds/dtmf5.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf6">
	<source src="assets/webrtc/sounds/dtmf6.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf7">
	<source src="assets/webrtc/sounds/dtmf7.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf8">
	<source src="assets/webrtc/sounds/dtmf8.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf9">
	<source src="assets/webrtc/sounds/dtmf9.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf0">
	<source src="assets/webrtc/sounds/dtmf0.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf_s">
	<source src="assets/webrtc/sounds/dtmf_s.mp3" type="audio/mpeg">
</audio>
<audio id="adtmf_p">
	<source src="assets/webrtc/sounds/dtmf_p.mp3" type="audio/mpeg">
</audio>