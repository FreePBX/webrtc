<!--ucp_config_originate.php-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="webrtcoriginate"><?php echo _("Enable WebRTC Originate") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="webrtcoriginate"></i>
					</div>
					<div class="col-md-9 radioset">
						<input type="radio" name="webrtc|originate" id="webrtc|originate_yes" value="yes" <?php echo ($enabled) ? 'checked' : ''?>>
						<label for="webrtc|originate_yes"><?php echo _("Yes")?></label>
						<input type="radio" name="webrtc|originate" id="webrtc|originate_no" value="no" <?php echo !($enabled) ? 'checked' : ''?>>
						<label for="webrtc|originate_no"><?php echo _("No")?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="webrtcoriginate-help" class="help-block fpbx-help-block"><?php echo _("Enable the WebRTC Originate in UCP for this user")?></span>
		</div>
	</div>
</div>
<!--ucp_config_originate.php-->
