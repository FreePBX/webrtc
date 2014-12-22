<!--ucp_config_hold.php-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="webrtchold"><?php echo _("Enable WebRTC Hold") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="webrtchold"></i>
					</div>
					<div class="col-md-9 radioset">
						<input type="radio" name="webrtc|hold" id="webrtc|hold_yes" value="yes" <?php echo ($enabled) ? 'checked' : ''?>>
						<label for="webrtc|hold_yes"><?php echo _("Yes")?></label>
						<input type="radio" name="webrtc|hold" id="webrtc|hold_no" value="no" <?php echo !($enabled) ? 'checked' : ''?>>
						<label for="webrtc|hold_no"><?php echo _("No")?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="webrtchold-help" class="help-block fpbx-help-block"><?php echo _("Enable Hold for WebRTC Phone in UCP for this user (Experimental)")?></span>
		</div>
	</div>
</div>
<!--ucp_config_hold.php-->

