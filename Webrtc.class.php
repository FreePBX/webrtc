<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules;
class Webrtc implements \BMO {

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new \Exception("Not given a FreePBX Object");
		}
	}

	public function doConfigPageInit($page) {

	}

	public function install() {

	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}
	public function genConfig() {
	}

	public function processUCPAdminDisplay($user) {
	}

	public function getUCPAdminDisplay($user) {
	}
}
