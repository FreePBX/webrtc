<?php
namespace FreePBX\modules\Webrtc;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $configs = [
        'settings'  => $this->FreePBX->Webrtc->getAll(),
        'clients' => $this->FreePBX->Webrtc->getClientsEnabled(),
    ];
    $this->addDependency('userman');
    $this->addDependency('certman');
    $this->addConfigs($configs);
  }
}