<?php

namespace App\Services\Proxmox;

use App\Models\Storage;
use App\Models\Node_storage as Node_storageDB;
use Illuminate\Support\Facades\Log;

class StorageService
{
    protected $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    public function fetchAndSaveStorageData($storageItem, $clusterName, $nodeCache)
    {
        if (empty($storageItem['storage'])) {
            Log::warning("Se recibió un item de storage incompleto. Saltando.", $storageItem);
            return;
        }

        $nodeIdProxmox = 'node/' . $storageItem['node'];
        $node = $nodeCache->get($nodeIdProxmox);
        if (!$node) {
            Log::warning("Nodo no encontrado en caché para el storage: {$storageItem['storage']}.");
            return;
        }

        $storageDisk = isset($storageItem['used']) ? $storageItem['used'] : 0;
        $storageMaxDisk = isset($storageItem['total']) ? $storageItem['total'] : 0;

        $storageModel = Storage::updateOrCreate(
            [
                'storage' => $storageItem['storage'],
                'node_id' => $nodeIdProxmox
            ],
            [
                'id_proxmox' => 'storage/' . $storageItem['node'] . '/' . $storageItem['storage'],
                'type' => isset($storageItem['type']) ? $storageItem['type'] : 'storage',
                'status' => (isset($storageItem['enabled']) && $storageItem['enabled']) ? 'online' : 'offline',
                'disk' => $storageDisk,
                'maxdisk' => $storageMaxDisk,
                'node' => $storageItem['node'],
                'content' => isset($storageItem['content']) ? $storageItem['content'] : null,
                'plugintype' => isset($storageItem['type']) ? $storageItem['type'] : 'unknown',
                'shared' => isset($storageItem['shared']) ? $storageItem['shared'] : 0,
                'used' => ($storageMaxDisk > 0) ? ($storageDisk / $storageMaxDisk) : 0,
                'cluster' => $clusterName,
            ]
        );
        Node_storageDB::updateOrCreate(['node_id' => $node->id, 'storage_id' => $storageModel->id]);
    }
}
