<span class="radioset">
	<input type="radio" name="webrtc|enable" id="webrtc|enable_yes" value="yes" <?php echo ($enabled) ? 'checked' : ''?>>
	<label for="webrtc|enable_yes">Yes</label>
	<input type="radio" name="webrtc|enable" id="webrtc|enable_no" value="no" <?php echo !($enabled) ? 'checked' : ''?>>
	<label for="webrtc|enable_no">No</label>
</span>
<br/>
<select id="webrtc|cert" name="webrtc|cert">
	<?php foreach($certs as $cert) { ?>
		<option value="<?php echo $cert['cid']?>" <?php echo (!empty($settings['certid']) && $settings['certid'] == $cert['cid']) ? 'selected' : ''?>><?php echo $cert['basename']?></option>
	<?php } ?>
</select>
