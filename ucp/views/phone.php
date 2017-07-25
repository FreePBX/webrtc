<div id="menu_webrtc_phone">
	<div class="activeCallSession">
		<div class="contact-info fade"><div class="contact"></div><div class="timer">00:00:00</div></div>
		<div class="input-container">
			<div class="input-group">
				<input type="text" class="form-control dialpad">
				<span class="input-group-btn">
					<button class="btn btn-default clear-input" type="button"><i class="fa fa-times"></i></button>
				</span>
			</div>
		</div>
		<div class="contactDisplay">
			<div class="contactImage">
			</div>
		</div>
		<table class="keypad">
			<tr>
				<td class="btn btn-default upper-left" data-num="1">
					<div class="num">1</div>
					<div class="letters">&nbsp;</div>
				</td>
				<td class="btn btn-default" data-num="2">
					<div class="num">2</div>
					<div class="letters"><?php echo _("ABC")?></div>
				</td>
				<td class="btn btn-default upper-right" data-num="3">
					<div class="num">3</div>
					<div class="letters"><?php echo _("DEF")?></div>
				</td>
			</tr>
			<tr>
				<td class="btn btn-default" data-num="4">
					<div class="num">4</div>
					<div class="letters"><?php echo _("GHI")?></div>
				</td>
				<td class="btn btn-default" data-num="5">
					<div class="num">5</div>
					<div class="letters"><?php echo _("JKL")?></div>
				</td>
				<td class="btn btn-default" data-num="6">
					<div class="num">6</div>
					<div class="letters"><?php echo _("MNO")?></div>
				</td>
			</tr>
			<tr>
				<td class="btn btn-default" data-num="7">
					<div class="num">7</div>
					<div class="letters"><?php echo _("PQRS")?></div>
				</td>
				<td class="btn btn-default" data-num="8">
					<div class="num">8</div>
					<div class="letters"><?php echo _("TUV")?></div>
				</td>
				<td class="btn btn-default" data-num="9">
					<div class="num">9</div>
					<div class="letters"><?php echo _("WXYZ")?></div>
				</td>
			</tr>
			<tr>
				<td class="btn btn-default lower-left" data-num="*">
					<div class="num">*</div>
					<div class="letters">&nbsp;</div>
				</td>
				<td class="btn btn-default" data-num="0">
					<div class="num">0</div>
					<div class="letters">+</div>
				</td>
				<td class="btn btn-default lower-right" data-num="#">
					<div class="num">#</div>
					<div class="letters">&nbsp;</div>
				</td>
			</tr>
		</table>
	</div>
	<table class="actions">
		<tr>
			<td class="left">
				<button class="btn btn-primary action" disabled="true"><?php echo _("Call")?></button>
			</td>
			<td class="right">
				<button class="btn btn-success secondaction hidden"><?php echo _("Hold")?></button>
			</td>
		</tr>
	</table>
	<div class="status">
		<?php echo _("Phone Status")?>: <span></span>
	</div>
	<div class="alert alert-info"><?php echo _("Note: An Active call will not be dropped when this window is closed")?></div>
</div>
