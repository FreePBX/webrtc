<?php
/* $Id$ */
global $db;
global $amp_conf;

out(_("Installing Emergency Phone Based on SIPML5!"));
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

$sql = "CREATE TABLE `websoftphone` (
  `realm` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `sipuri` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `websocket` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `breaker` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `cid` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
);";

out(_("Installing Database!"));

$check = $db->query($sql);
if (DB::IsError($check)) {
        die_freepbx( "Can not create `websoftphone` table: " . $check->getMessage() .  "\n");
}


//TODO verify if ARI is installed before copy files
$wsip_src = $amp_conf['AMPWEBROOT']."/admin/modules/emergencyphones/ARI/*";
$wsip_ari_dest = $amp_conf['AMPWEBROOT']."/recordings/modules/";

out(_("Installing ARI module"));
exec("cp -rf $wsip_src $wsip_ari_dest");


?>
