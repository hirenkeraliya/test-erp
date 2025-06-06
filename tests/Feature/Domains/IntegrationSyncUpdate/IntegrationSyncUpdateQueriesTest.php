<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\IntegrationSyncUpdate\IntegrationSyncUpdateQueries;
use App\Models\Company;
use App\Models\Integration;
use App\Models\IntegrationSyncUpdate;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();
    $this->integration = Integration::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $this->integrationSyncUpdateQueries = new IntegrationSyncUpdateQueries();
});

test('getByIntegrationIdAndModuleType returns the correct sync update record', function (): void {
    $moduleType = ModelMapping::PRODUCT->name;
    $syncUpdate = IntegrationSyncUpdate::factory()->create([
        'integration_id' => $this->integration->id,
        'module_type' => $moduleType,
        'last_sync_date' => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
    ]);

    $result = $this->integrationSyncUpdateQueries->getByIntegrationIdAndModuleType(
        $this->integration->id,
        $moduleType
    );

    expect($result)
        ->toBeInstanceOf(IntegrationSyncUpdate::class)
        ->toHaveKey('id', $syncUpdate->id)
        ->toHaveKey('integration_id', $this->integration->id)
        ->toHaveKey('module_type', $moduleType);
});

test('getByIntegrationIdAndModuleType returns null for non-existent record', function (): void {
    $nonExistentModuleType = ModelMapping::ORDER->name;

    $result = $this->integrationSyncUpdateQueries->getByIntegrationIdAndModuleType(
        $this->integration->id,
        $nonExistentModuleType
    );

    expect($result)->toBeNull();
});

test('createOrUpdateSyncDetails creates a new record if it does not exist', function (): void {
    $moduleType = ModelMapping::PRODUCT->name;
    $lastSyncDate = Carbon::now()->format('Y-m-d H:i:s');

    $this->integrationSyncUpdateQueries->createOrUpdateSyncDetails(
        $this->integration->id,
        $moduleType,
        $lastSyncDate
    );

    $this->assertDatabaseHas('integration_sync_updates', [
        'integration_id' => $this->integration->id,
        'module_type' => $moduleType,
        'last_sync_date' => $lastSyncDate,
    ]);
});

test('createOrUpdateSyncDetails updates an existing record', function (): void {
    $moduleType = ModelMapping::PRODUCT->name;
    $initialSyncDate = Carbon::now()->subDay()->format('Y-m-d H:i:s');
    $newSyncDate = Carbon::now()->format('Y-m-d H:i:s');

    IntegrationSyncUpdate::factory()->create([
        'integration_id' => $this->integration->id,
        'module_type' => $moduleType,
        'last_sync_date' => $initialSyncDate,
    ]);

    $this->integrationSyncUpdateQueries->createOrUpdateSyncDetails(
        $this->integration->id,
        $moduleType,
        $newSyncDate
    );

    $this->assertDatabaseHas('integration_sync_updates', [
        'integration_id' => $this->integration->id,
        'module_type' => $moduleType,
        'last_sync_date' => $newSyncDate,
    ]);

    $count = IntegrationSyncUpdate::where('integration_id', $this->integration->id)
        ->where('module_type', $moduleType)
        ->count();

    expect($count)->toBe(1);
});
