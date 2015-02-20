<?php if(empty($stunaddr)) {?>
	<strong style='color:red'>
		<?php echo _("The STUN Server address is blank. In many cases this can cause issues. Please define a valid server in the Asterisk SIP Settings module")?>
	</strong><br/>
<?php } ?>
<span class="radioset">
	<input type="radio" name="webrtc|enable" id="webrtc|enable_yes" value="yes" <?php echo ($enabled) ? 'checked' : ''?>>
	<label for="webrtc|enable_yes">Yes</label>
	<input type="radio" name="webrtc|enable" id="webrtc|enable_no" value="no" <?php echo !($enabled) ? 'checked' : ''?>>
	<label for="webrtc|enable_no">No</label>
</span>
<br/>
