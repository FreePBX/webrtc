<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { 
	die('No direct script access allowed'); 
}
show_view(dirname(__FILE__).'/views/phone.php',array());