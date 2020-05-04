<?php
namespace FreePBX\modules\Webrtc;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		$this->importTables($configs['tables']);
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$this->restoreLegacyDatabase($pdo);
		//recreate all extensions on restore
		$bmo = $this->FreePBX->Webrtc;
		$clients = $bmo->getClientsEnabled();
		foreach($clients as $client) {
			$bmo->createDevice($client['user'],$client['certid']);
		}
	}
}
