<!--ucp_config_certs.php-->
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
						<select id="webrtc|cert" name="webrtc|cert" class="form-control">
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
<!--ucp_config_certs.php-->

