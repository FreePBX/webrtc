<div class="col-md-10">
	<h3><?php echo _('Voicemail Settings')?></h3>
	<div class="vmsettings">
		<div id="message" class="alert" style="display:none;"></div>
		<form role="form">
			<div class="form-group">
				<label for="pwd" class="help"><?php echo _('Voicemail Pin')?> <i class="fa fa-question-circle"></i></label>
				<input name="pwd" type="number" class="form-control" id="pwd" value="<?php echo $settings['pwd']?>" autocapitalize="off" autocorrect="off">
				<span class="help-block help-hidden" data-for="pwd"><?php echo _('Pin Used to Login to Voicemail. This pin can only contain numbers.')?></span>
			</div>
			<div class="form-group">
				<label for="email" class="help"><?php echo _('Email Address')?> <i class="fa fa-question-circle"></i></label>
				<input name="email" type="email" class="form-control" id="email" value="<?php echo $settings['email']?>" placeholder="user@domain.tld" autocapitalize="off" autocorrect="off">
				<span class="help-block help-hidden" data-for="email"><?php echo _('The email address that Voicemails are sent to.')?></span>
			</div>
			<div class="form-group">
				<label for="pager" class="help"><?php echo _('Pager Email Address')?> <i class="fa fa-question-circle"></i></label>
				<input name="pager" type="email" class="form-control" id="pager" value="<?php echo $settings['pager']?>" placeholder="user@domain.tld" autocapitalize="off" autocorrect="off">
				<span class="help-block help-hidden" data-for="pager"><?php echo _('Pager/mobile email address that short Voicemail notifications are sent to.')?></span>
			</div>
			<div class="form-group">
				<label for="saycid-h" class="help"><?php echo _('Play CID')?> <i class="fa fa-question-circle"></i></label>
				<div class="onoffswitch">
					<input type="checkbox" name="saycid" class="onoffswitch-checkbox" id="saycid" <?php echo ($settings['options']['saycid'] == 'yes') ? 'checked' : ''?> value="yes">
					<label class="onoffswitch-label" for="saycid">
						<div class="onoffswitch-inner"></div>
						<div class="onoffswitch-switch"></div>
					</label>
				</div>
				<span class="help-block help-hidden" data-for="saycid-h"><?php echo _("Read back caller's telephone number prior to playing the incoming message, and just after announcing the date and time the message was left.")?></span>
			</div>
			<div class="form-group">
				<label for="envelope-h" class="help"><?php echo _('Play Envelope')?> <i class="fa fa-question-circle"></i></label>
				<div class="onoffswitch">
					<input type="checkbox" name="envelope" class="onoffswitch-checkbox" id="envelope" <?php echo ($settings['options']['envelope'] == 'yes') ? 'checked' : ''?> value="yes">
					<label class="onoffswitch-label" for="envelope">
						<div class="onoffswitch-inner"></div>
						<div class="onoffswitch-switch"></div>
					</label>
				</div>
				<span class="help-block help-hidden" data-for="envelope-h"><?php echo _('Envelope controls whether or not the Voicemail system will play the message envelope (date/time) before playing the Voicemail message. This setting does not affect the operation of the envelope option in the advanced Voicemail menu.')?></span>
			</div>
		</form>
	</div>
</div>
