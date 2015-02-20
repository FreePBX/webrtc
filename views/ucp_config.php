<!--ucp_config.php-->
<?php if(empty($stunaddr)) {?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<strong style='color:red'>
						<?php echo _("The STUN Server address is blank. In many cases this can cause issues. Please define a valid server in the Asterisk SIP Settings module")?>
					</strong><br/>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="webrtcenable"><?php echo _("Enable WebRTC Phone") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="webrtcenable"></i>
					</div>
					<div class="col-md-9 radioset">
						<input type="radio" name="webrtc|enable" id="webrtc|enable_yes" value="yes" <?php echo ($enabled) ? 'checked' : ''?>>
						<label for="webrtc|enable_yes"><?php echo _("Yes")?></label>
						<input type="radio" name="webrtc|enable" id="webrtc|enable_no" value="no" <?php echo !($enabled) ? 'checked' : ''?>>
						<label for="webrtc|enable_no"><?php echo _("No")?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="webrtcenable-help" class="help-block fpbx-help-block"><?php echo _("Enable the WebRTC Phone in UCP for this user")?></span>
		</div>
	</div>
</div>
<!--ucp_config.php-->
