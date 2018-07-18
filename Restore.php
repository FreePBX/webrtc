<?php
namespace FreePBX\modules\Webrtc;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $configs = reset($this->getConfigs());
    $this->FreePBX->Webrtc->setMultiConfig($configs['settings']);
    foreach ($configs['clients'] as $client) {
        $this->FreePBX->Webrtc->upsertClientSettings($client['user'], $client['device'], $client['certid']);
    }
  }
}
