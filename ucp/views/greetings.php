<div class="col-md-10">
	<?php $count = 1;?>
	<?php foreach($short_greetings as $greeting => $name) {?>
		<?php if($count % 2) {?>
			<div class="row">
		<?php } ?>
			<div class="col-md-6">
				<div id="<?php echo $greeting?>" class="greeting-control">
					<h4><?php echo _($name.' Greeting')?></h4>
					<div id="freepbx_player_<?php echo $greeting?>" class="jp-jplayer"></div>
					<div id="freepbx_player_<?php echo $greeting?>_1" data-type="<?php echo $greeting?>" class="jp-audio <?php echo !isset($greetings[$greeting]) ? 'greet-hidden' : ''?>" draggable="true">
					    <div class="jp-type-single">
					        <div class="jp-gui jp-interface">
					            <ul class="jp-controls">
					                <li class="jp-play-wrapper"><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
					                <li class="jp-pause-wrapper"><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
					                <li class="jp-stop-wrapper"><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
									<li class="jp-record-wrapper"><a onclick="Voicemail.recordGreeting('<?php echo $greeting?>')" class="jp-record" tabindex="1">record</a></li>
					                <li class="jp-mute-wrapper"><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
					                <li class="jp-unmute-wrapper"><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
					                <li class="jp-volume-max-wrapper"><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
					            </ul>
					            <div class="jp-progress">
					                <div class="jp-seek-bar">
					                    <div class="jp-play-bar"></div>
					                </div>
					            </div>
					            <div class="jp-volume-bar">
					                <div class="jp-volume-bar-value"></div>
					            </div>
					            <div class="jp-current-time"></div>
					            <div class="jp-duration"></div>
						        <div class="jp-title">
						            <ul>
						                <li class="title-text" data-title="<?php echo _($name.' Greeting')?>"><?php echo _($name.' Greeting')?></li>
						            </ul>
						        </div>
					        </div>
					        <div class="jp-no-solution">
					            <span><?php echo _('Update Required')?></span>
					            <?php echo sprintf(_('To play the media you will need to either update your browser to a recent version or update your %s'),'<a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>')?>.
					        </div>
					    </div>
					</div>
					<div class="file-controls">
						<span class="btn btn-file btn-success btn-xs"><i class="fa fa-cloud-upload"></i> <?php echo _('Upload Greeting')?><input type="file" type="file" name="files[]" multiple /></span>
						<button class="btn btn-danger btn-xs <?php echo !isset($greetings[$greeting]) ? 'greet-hidden' : ''?>" onclick="Voicemail.deleteGreeting('<?php echo $greeting?>')"><i class="fa fa-trash-o fa-lg"></i><?php echo _('Delete')?></button>
						<button class="btn btn-danger record-greeting-btn btn-xs" onclick="Voicemail.recordGreeting('<?php echo $greeting?>')"><i class="fa fa-circle"></i> Record Greeting</button>
					</div>
					<div class="recording-controls">
						<button class="btn btn-success btn-xs" onclick="Voicemail.saveRecording('<?php echo $greeting?>')"><i class="fa fa-floppy-o"></i> <?php echo _('Save Greeting')?></button>
						<button class="btn btn-danger btn-xs" onclick="Voicemail.deleteRecording('<?php echo $greeting?>')"><i class="fa fa-trash-o fa-lg"></i> <?php echo _('Discard Greeting')?></button>
					</div>
					<div data-type="<?php echo $greeting?>" class="filedrop hidden-xs hidden-sm">
						<div class="message"><?php echo _('Drag a New Greeting Here')?></div>
						<div class="pbar"></div>
					</div>
				</div>
			</div>
		<?php if(!($count % 2)) {?>
			</div>
		<?php } ?>
		<?php $count++?>
	<?php } ?>
</div>
