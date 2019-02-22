<?php
namespace FreePBX\modules\Webrtc;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
	public function runBackup($id,$transaction){
		$configs = [
				'tables'	=> $this->dumpTables(),
				'kvstore' => $this->dumpKVStore(),
		];
		$this->addDependency('userman');
		$this->addDependency('certman');
		$this->addConfigs($configs);
	}
}