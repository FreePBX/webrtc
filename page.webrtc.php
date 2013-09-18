<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { 
	die('No direct script access allowed'); 
}

//retreive values from DB
global $db;
$kk="select * from webrtc";
$results = $db->getAll($kk, DB_FETCHMODE_ASSOC);
foreach($results as $row){
	$realm=$row['realm'];
	$username=$row['username'];
	$sipuri=$row['sipuri'];
	$cid=$row['cid'];
	$password=$row['password'];
	$websocket=$row['websocket'];
	$breaker=$row['breaker'];
}

//create hidden items to use with the stack
	echo "<input type='hidden' id='jrealm' value=".$realm." >";
	echo "<input type='hidden' id='jusn' value=".$username. " >";
	echo "<input type='hidden' id='jsipuri' value=".$sipuri." >";
	echo "<input type='hidden' id='jcid' value=".$cid." >";
	echo "<input type='hidden' id='jpassword' value=".$password." >";
	echo "<input type='hidden' id='jwebsocket' value=".$websocket." >";
	echo "<input type='hidden' id='jbreaker' value=" .$breaker. " >";

/****************** another ugly solution to write into the DB the DATA **********************/
global $db;

//catch values
if(isset($_POST['button'])) {
	$breaker=$_POST['breaker'];
	$password=$_POST['password'];
	$realm=$_POST['realm'];
	$sipuri=$_POST['sipuri'];
	$username=$_POST['username'];
	$websocket=$_POST['websocket'];
	$cid=$_POST['cid'];

	//if one value is empty display the required label
	if($breaker==''){
		$errors1 = '<span class="ui-state-highlight">Required</span>';
	}elseif( $password==''){
		$errors1 = '<span class="ui-state-highlight">Required</span>';
	}elseif( $realm==''){
		$errors1 = '<span class="ui-state-highlight">Required</span>';
	}elseif( $sipuri==''){
		$errors1 = '<span class="ui-state-highlight">Required</span>';
	}elseif( $username==''){
		$errors1 = '<span class="ui-state-highlight">Required</span>';
	}elseif( $websocket==''){
		$errors1 = '<span class="ui-state-highlight">Required</span>';
	}else{
		//delete previous data
		$sql1="truncate websoftphone";
		$db->query($sql1);

		//insert new data
		$sql="INSERT INTO webrtc(cid,breaker,password,realm,sipuri,username,websocket) Values('$cid','$breaker','$password','$realm','$sipuri','$username','$websocket')";
		$db->query($sql);
	}
}
?>
<!--/****************************** html/php code *************************************/-->
<!-- some ugly space -->
	<br><br>

	<!-- trying to create beatiful boxes  failed! :( -->
	<div id="sysinfo-left" class="infobox ui-widget-content  ui-corner-all" >
		<h3 class="ui-widget-header ui-state-default ui-corner-all">WebSoftphone</h3>
               	<div align=center id="mysipstatus" style="font-size: 10px"></div>	
                <table style='width: 100%;'>
		 <tr>
			<td>
				<div align="center">
					<input autofocus type="text" style="width: 80%" value="" id="callnumber" /> 
					<br>
				</div>
				
			</td> 
		</tr>
                <tr>			
                        <td colspan="1" align="center">
			    <!-- KeyPad Div -->
			    	<table >
			            <tr><td><input type="button" style="width: 32%" class="btn-primary" value="1" onclick="sipSendDTMF('1');"/><input type="button" style="width: 32%" class="btn-primary" value="2" onclick="sipSendDTMF('2');"/><input type="button" style="width: 32%" class="btn-primary" value="3" onclick="sipSendDTMF('3');"/></td></tr>
			            <tr><td><input type="button" style="width: 32%" class="btn-primary" value="4" onclick="sipSendDTMF('4');"/><input type="button" style="width: 32%" class="btn-primary" value="5" onclick="sipSendDTMF('5');"/><input type="button" style="width: 32%" class="btn-primary" value="6" onclick="sipSendDTMF('6');"/></td></tr>
			            <tr><td><input type="button" style="width: 32%" class="btn-primary" value="7" onclick="sipSendDTMF('7');"/><input type="button" style="width: 32%" class="btn-primary" value="8" onclick="sipSendDTMF('8');"/><input type="button" style="width: 32%" class="btn-primary" value="9" onclick="sipSendDTMF('9');"/></td></tr>
			            <tr><td><input type="button" style="width: 32%" class="btn-primary" value="*" onclick="sipSendDTMF('*');"/><input type="button" style="width: 32%" class="btn-primary" value="0" onclick="sipSendDTMF('0');"/><input type="button" style="width: 32%" class="btn-primary" value="#" onclick="sipSendDTMF('#');"/></td></tr>
        			</table>
                            	<input type="button" class="btn-success" style="width:25%" id="btnCall" value="Call" onclick='call();' />&nbsp;
                            	<input type="button" class="btn-danger" style="width:25%"  id="btnHangUp" value="HangUp" onclick='hangup();' />
                        </td>
		<tr></tr>			
                </tr>
			<td>
				<div align=center id="mycallstatus" style="font-size: 10px"></div>
                        </td>

                <tr>
                </tr>
		</table>
	</div>


	<!--another failed box-->
	<div id="sysinfo-right"> 
		<div id="sett" class="infobox ui-widget-content  ui-corner-all" style='display:table-cell'>
		<h3 class="ui-widget-header ui-state-default ui-corner-all">Settings</h3>

		<input type="checkbox" id="settings" /><label for="settings" id="lsettings">Show Settings</label>
		<br><br>
		
		<!--the form with the data-->
		<form method="post" action="" id="testform">
		<table align=left>
			<tr><td><label for="realm" id="lrealm">Realm: </label></td><td><input placeholder="my.domain.com" type="text" id="realm" name="realm" value="<?php echo $realm; ?>"/><? echo $errors1; ?></td></tr>
			<tr><td><label for="username" id="lusn">UserName: </label></td><td><input placeholder="1000" type="text" id="username" name="username" value="<?php echo $username; ?>"/><? echo $errors1; ?></td></tr>
			<tr><td><label for="sipuri" id="lsipu">SIP URI: </label></td><td><input placeholder="sip:1000@my.sipserver.com" type="text" id="sipuri" name="sipuri" value="<?php echo $sipuri; ?>"/><? echo $errors1; ?></td></tr>
			<tr><td><label for="cid" id="lcid">Display Name: </label></td><td><input placeholder="John Doe" type="text" id="cid" name="cid" value="<?php echo $cid; ?>"/><? echo $errors1; ?></td></tr>
			<tr><td><label for="password" id="lpwd">Password: </label></td><td><input placeholder="mypassword" type="text" id="password" name="password" value="<?php echo $password; ?>"/><? echo $errors1; ?></td></tr>
			<tr><td><label for="websocket" id="lwsck">WebSocket: </label></td><td><input placeholder="ws://mywebsocket:10060" type="text" id="websocket" name="websocket" value="<?php echo $websocket; ?>"/><? echo $errors1; ?></td></tr>
			<tr><td><label for="breaker" id="lbreak">Enable Breaker: </label></td><td><select id="breaker" name="breaker" ><option value="true" <?php if($breaker=='true'){ echo "selected='selected'";} ?> >True</option><option value="false" <?php if($breaker=='false'){ echo "selected='selected'";} ?>>False</option></select></td></tr>
		</table>
                        <input name="button" type="submit" class="ui-state-error" value="Save Settings" id="save"/>
		</form>

		<!-- shame on me -->
		<div id="note">
		</div>
			<div align="center"><h6> By Navaismo using the Awesome <a  href="http://sipml5.org">Doubango's SIPml5 API</a>.</h6></div>

		</div>
		
	</div>

    <!-- Audios -->
    <audio id="audio_remote" autoplay="autoplay" />
    <audio id="ringtone" loop src="modules/emergencyphones/sounds/ringtone.wav" />
    <audio id="ringbacktone" loop src="modules/emergencyphones/sounds/ringbacktone.wav" />
    <audio id="dtmfTone" src="modules/emergencyphone/emergencyphones/dtmf.wav" />