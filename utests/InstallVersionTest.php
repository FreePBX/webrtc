<?php
/**
* @backupGlobals disabled
* @backupStaticAttributes disabled
*/

class InstallVersionTest extends PHPUnit_Framework_TestCase {

	public static $f;
	public static $current;

	public static function setUpBeforeClass() {
		include 'setuptests.php';
		self::$f = FreePBX::create();
	}

	public static function tearDownAfterClass() {
		self::$f->Config()->set_conf_values(array('ASTVERSION' => self::$current),true,true);
	}

	// FREEPBX-13397
	public function test14Install() {
		$c = self::$f->Config();
		self::$current = $c->get('ASTVERSION');
		$c->set_conf_values(array('ASTVERSION' => "16.0.2"),true,true);
		// This should be a string
		$this->assertTrue((self::$f->Webrtc->validVersion() !== true), "16.0.2 is not a valid version, but it's saying it is");
		$c->set_conf_values(array('ASTVERSION' => "15.0.2"),true,true);
		$this->assertTrue(self::$f->Webrtc->validVersion(), "15.0.2 is a valid version, but its saying it is not");
	}
}
