<?php
/* $Id$ */
global $db;
global $amp_conf;

out(_("Uninstalling Web Softphone Based on SIPML5!"));
if (! function_exists("out")) {
	function out($text) {
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

$sql="DROP TABLE websoftphone";
out(_("Removing Database!"));
$check = $db->query($sql);

out(_("Removing From ARI interface!"));
exec("rm -rf ".$amp_conf['AMPWEBROOT']."/recordings/modules/emergencyphone*");
exec("rm -rf ".$amp_conf['AMPWEBROOT']."/recordings/modules/websoftphone*");
exec("rm -rf ".$amp_conf['AMPWEBROOT']."/recordings/modules/localweb*");

?>
