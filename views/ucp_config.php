<?php if(!empty($webrtcmessage)) {?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="alert alert-danger" role="alert">
					<?php echo $webrtcmessage?>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
<?php if($config) {?>
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
						<input type="radio" name="webrtc_enable" id="webrtc_enable_yes" value="yes" <?php echo ($enabled) ? 'checked' : ''?>>
						<label for="webrtc_enable_yes"><?php echo _("Yes")?></label>
						<input type="radio" name="webrtc_enable" id="webrtc_enable_no" value="no" <?php echo (!is_null($enabled) && !$enabled) ? 'checked' : ''?>>
						<label for="webrtc_enable_no"><?php echo _("No")?></label>
						<?php if($mode == "user") {?>
							<input type="radio" id="webrtc_enable_inherit" name="webrtc_enable" value='inherit' <?php echo is_null($enabled) ? 'checked' : ''?>>
							<label for="webrtc_enable_inherit"><?php echo _('Inherit')?></label>
						<?php } ?>
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
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="webrtccert"><?php echo _("WebRTC Certificate") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="webrtccert"></i>
					</div>
					<div class="col-md-9">
						<select id="webrtc_cert" name="webrtc_cert" class="form-control ucp-webrtc" <?php echo (!is_null($enabled) && !$enabled) ? 'disabled' : ''?>>
							<?php foreach($certs as $cert) { ?>
							<option value="<?php echo $cert['cid']?>" <?php echo (!empty($settings['certid']) && $settings['certid'] == $cert['cid']) ? 'selected' : ''?>><?php echo $cert['basename']?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="webrtccert-help" class="help-block fpbx-help-block"><?php echo _("Certificate WebRTC Phone should use for this user.")?></span>
		</div>
	</div>
</div>
<?php } ?>
<script>
	$("input[name=webrtc_enable]").change(function() {
		if($(this).val() == "yes" || $(this).val() == "inherit") {
			$(".ucp-webrtc").prop("disabled",false);
		} else {
			$(".ucp-webrtc").prop("disabled",true);
		}
	});
</script>
