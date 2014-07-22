<div id="vmxerror" class="message alert" style="display:none;"></div>
<div id="vmxmessage" class="message alert" style="display:none;"></div>
<form role="form">
	<div class="form-group">
		<label for="vmx-usewhen-h" class="help"><?php echo _('Use When')?> <i class="fa fa-question-circle"></i></label>
		<div class="btn-group dests" data-toggle="buttons">
			<label class="btn btn-default <?php echo ($settings['unavail']['state'] == 'enabled') ? 'active' : ''?>">
				<input type="checkbox" name="vmx-usewhen-unavailable"> <?php echo _('Unavailable')?>
			</label>
			<label class="btn btn-default <?php echo ($settings['busy']['state'] == 'enabled') ? 'active' : ''?>">
				<input type="checkbox" name="vmx-usewhen-busy"> <?php echo _('Busy')?>
			</label>
			<label class="btn btn-default <?php echo ($settings['temp']['state'] == 'enabled') ? 'active' : ''?>">
				<input type="checkbox" name="vmx-usewhen-temp"> <?php echo _('Temp')?>
			</label>
		</div>
		<span class="help-block help-hidden" data-for="vmx-usewhen-h"><?php echo _('Enable VmX Menu options during Voicemail playback for the selected options.')?></span>
	</div>
	<div class="form-group">
		<label for="vmx-p0-h" class="help"><?php echo _('Press 0')?> <i class="fa fa-question-circle"></i></label></br>
		<div class="input-group">
			<span class="input-group-addon">
				<input type="checkbox" id="vmx-p0_enable" name="vmx-p0_enable" data-el="vmx-p0" <?php echo !empty($settings['unavail'][0]['ext']) ? 'checked' : ''?>>
			</span>
			<input type="text" class="form-control" id="vmx-p0" name="vmx-opt0" data-type="vmx-p0" placeholder="<?php echo _('Go To Operator')?>" data-ph="<?php echo _('Go To Operator')?>" value="<?php echo !empty($settings['unavail'][0]['ext']) ? $settings['unavail'][0]['ext'] : ''?>">
		</div>
		<span class="help-block help-hidden" data-for="vmx-p0-h"><?php echo _('Pressing 0 during your personal voicemail greeting goes to the Operator. Check to enter another destination here.')?></span>
	</div>
	<div class="form-group">
		<label for="vmx-p1-h" class="help"><?php echo _('Press 1')?> <i class="fa fa-question-circle"></i></label></br>
		<div class="input-group">
			<span class="input-group-addon">
				<input type="checkbox" id="vmx-p1_enable" name="vmx-p1_enable" data-el="vmx-p1" <?php echo (!empty($settings['unavail'][1]['ext']) && $settings['unavail'][1]['ext'] != $fmfm) ? 'checked' : ''?>>
			</span>
			<input type="text" class="form-control" id="vmx-p1" name="vmx-opt1" data-type="vmx-p1" placeholder="<?php echo _('Send to Follow-Me')?>" data-ph="<?php echo _('Send to Follow-Me')?>" value="<?php echo (!empty($settings['unavail'][1]['ext']) && $settings['unavail'][1]['ext'] != $fmfm) ? $settings['unavail'][1]['ext'] : ''?>">
		</div>
		<span class="help-block help-hidden" data-for="vmx-p1-h"><?php echo _('Enter an alternate number here, then change your personal voicemail greeting to let callers know to press 1 to reach that number. If you\'d like to use your Follow Me List, ucheck the box to disable')?></span>
	</div>
	<div class="form-group">
		<label for="vmx-p2-h" class="help"><?php echo _('Press 2')?> <i class="fa fa-question-circle"></i></label>
		<input name="vmx-opt2" type="text" class="form-control" id="vmx-p2" value="<?php echo !empty($settings['unavail'][2]['ext']) ? $settings['unavail'][2]['ext'] : ''?>" autocapitalize="off" autocorrect="off">
		<span class="help-block help-hidden" data-for="vmx-p2-h"><?php echo _('Use any extensions, ringgroups, queues or external numbers. Remember to re-record your personal voicemail greeting and include instructions. Run a test to make sure that the number is functional.')?></span>
	</div>
</form>
