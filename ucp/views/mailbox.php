<div class="col-md-10">
	<?php if(!empty($message)) { ?>
		<div class="alert alert-<?php echo $message['type']?>"><?php echo $message['message']?></div>
	<?php } ?>
	<div id="freepbx_player" class="jp-jplayer"></div>
	<div id="freepbx_player_1" class="jp-audio">
	    <div class="jp-type-single">
	        <div class="jp-gui jp-interface">
	            <ul class="jp-controls">
	                <li class="jp-play-wrapper"><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
	                <li class="jp-pause-wrapper"><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
	                <li class="jp-stop-wrapper"><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
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
		                <li id="title-text"><?php echo _('Unknown')?></li>
		            </ul>
		        </div>
	        </div>
	        <div class="jp-no-solution">
	            <span><?php echo _('Update Required')?></span>
	            <?php echo sprintf(_('To play the media you will need to either update your browser to a recent version or update your <a href="%s" target="_blank">Flash plugin</a>'),'http://get.adobe.com/flashplayer/');?>.
	        </div>
	    </div>
	</div>
	<div class="table-responsive">
		<table class="table table-hover table-bordered message-table message-list">
			<thead>
			<tr class="message-header">
				<th class="visible-xs"><?php echo _('Date')?></th>
				<th class="hidden-xs"><?php echo _('Date')?></th>
				<th><?php echo _('Time')?></th>
				<th><?php echo _('CID')?></th>
				<th class="hidden-xs"><?php echo _('Mailbox')?></th>
				<th class="hidden-xs"><?php echo _('Length')?></th>
				<th><?php echo _('Controls')?></th>
			</tr>
			</thead>
		<?php if(!empty($messages)) {?>
			<?php foreach($messages as $message){?>
				<tr class="vm-message" data-msg="<?php echo $message['msg_id']?>" draggable="true">
					<td class="visible-xs"><?php echo date('m-d',$message['origtime'])?></td>
					<td class="hidden-xs"><?php echo date('Y-m-d',$message['origtime'])?></td>
					<td><?php echo date('h:i:sa',$message['origtime'])?></td>
					<td class="cid"><?php echo $message['callerid']?></td>
					<td class="hidden-xs"><?php echo $message['origmailbox']?></td>
					<td class="hidden-xs"><?php echo $message['duration']?> sec</td>
					<td class="actions">
						<div>
							<a class="subplay" alt="<?php echo _('Play');?>" onclick="Voicemail.playVoicemail('<?php echo $message['msg_id']?>')">
								<i class="fa fa-play"></i>
							</a>
							<a class="download" alt="<?php echo _('Download');?>" href="?quietmode=1&amp;module=voicemail&amp;command=listen&amp;msgid=<?php echo $message['msg_id']?>&amp;format=wav&amp;ext=<?php echo $ext?>" target="_blank">
								<i class="fa fa-cloud-download"></i>
							</a>
							<a class="delete" alt="<?php echo _('Delete');?>" onclick="Voicemail.deleteVoicemail('<?php echo $message['msg_id']?>')">
								<i class="fa fa-trash-o"></i>
							</a>
							<!--preload-->
							<img class="hidden" src="modules/Voicemail/assets/images/pause_control.png">
						</div>
					</td>
				</tr>
			<?php }?>
		<?php } else { ?>
			<tr class="vm-message">
				<td colspan="7"><?php echo _('No Messages');?></td>
			</tr>
		<?php } ?>
		</table>
	</div>
</div>
