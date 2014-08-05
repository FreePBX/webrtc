<select id="webrtc|cert" name="webrtc|cert">
	<?php foreach($certs as $cert) { ?>
		<option value="<?php echo $cert['cid']?>" <?php echo (!empty($settings['certid']) && $settings['certid'] == $cert['cid']) ? 'selected' : ''?>><?php echo $cert['basename']?></option>
	<?php } ?>
</select>
