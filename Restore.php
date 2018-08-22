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

  public function processLegacy($pdo, $data, $tables, $unknownTables, $tmpfiledir){
    $tables = array_flip($tables+$unknownTables);
    if(!isset($tables['webrtc_users'])){
      return $this;
    }
    $bmo = $this->FreePBX->Webrtc;
    $bmo->setDatabase($pdo);
    $configs = [
      'settings' => $bmo->getAll(),
      'clients' => $bmo->getClientsEnabled(),
    ];
    $bmo->resetDatabase();
    foreach ($configs['clients'] as $client) {
      $bmo->upsertClientSettings($client['user'], $client['device'], $client['certid']);
    }
    $this->transformLegacyKV($pdo, 'webrtc', $this->FreePBX)
      ->transformNamespacedKV($pdo, 'webrtc', $this->FreePBX);
    return $this;
  }

}
