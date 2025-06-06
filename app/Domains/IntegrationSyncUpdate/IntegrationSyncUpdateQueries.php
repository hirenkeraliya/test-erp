<?php

declare(strict_types=1);

namespace App\Domains\IntegrationSyncUpdate;

use App\Models\IntegrationSyncUpdate;

class IntegrationSyncUpdateQueries
{
    public function getBasicColumns(): array
    {
        return ['id', 'integration_id', 'last_sync_date', 'module_type'];
    }

    public function getBasicColumnsInString(): string
    {
        return implode(',', $this->getBasicColumns());
    }

    public function getByIntegrationIdAndModuleType(int $integrationId, string $moduleType): ?IntegrationSyncUpdate
    {
        return IntegrationSyncUpdate::select($this->getBasicColumns())
            ->where('integration_id', $integrationId)
            ->where('module_type', $moduleType)
            ->first();
    }

    public function createOrUpdateSyncDetails(int $integrationId, string $moduleType, string $lastSyncDate): void
    {
        IntegrationSyncUpdate::updateOrCreate(
            [
                'integration_id' => $integrationId,
                'module_type' => $moduleType,
            ],
            [
                'last_sync_date' => $lastSyncDate,
            ]
        );
    }
}
