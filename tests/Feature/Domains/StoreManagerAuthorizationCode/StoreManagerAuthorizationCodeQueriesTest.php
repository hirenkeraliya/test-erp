<?php

declare(strict_types=1);

use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'ABCD',
    ]);

    $this->storeManager = StoreManager::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE->value,
    ]);

    $this->storeManagerAuthorizationCodeQueries = new StoreManagerAuthorizationCodeQueries();
});

test('getWithStoreManager returns the storeManagerAuthorizationCode as expected', function (): void {
    $response = $this->storeManagerAuthorizationCodeQueries->getWithStoreManager($this->storeManager->getKey());

    expect($response)->toHaveKeys([...$this->storeManagerAuthorizationCodeQueries->getBasicColumns()]);
});

test('getOnlyActiveStoreManagerAuthorizationCodes returns the collection as expected', function (): void {
    StoreManagerAuthorizationCode::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'status' => StoreManagerAuthorizationCodeStatuses::CANCELLED->value,
    ]);

    $response = $this->storeManagerAuthorizationCodeQueries->getOnlyActiveStoreManagerAuthorizationCodes();

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->storeManagerAuthorizationCode->getKey())
        ->toHaveKeys([...$this->storeManagerAuthorizationCodeQueries->getBasicColumns()]);
});

test('getById returns the storeManagerAuthorizationCode as expected', function (): void {
    $response = $this->storeManagerAuthorizationCodeQueries->getById($this->storeManagerAuthorizationCode->getKey());

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->storeManagerAuthorizationCode->getKey())
        ->toHaveKeys([...$this->storeManagerAuthorizationCodeQueries->getBasicColumns()]);
});

test('cancelTheAuthorizationCode updates the status as cancel as expected', function (): void {
    $this->assertDatabaseHas(StoreManagerAuthorizationCode::class, [
        'id' => $this->storeManagerAuthorizationCode->getKey(),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE->value,
    ]);

    $this->storeManagerAuthorizationCodeQueries->cancelTheAuthorizationCode(
        $this->storeManagerAuthorizationCode->getKey()
    );

    $this->storeManagerAuthorizationCode->refresh();

    $this->assertDatabaseHas(StoreManagerAuthorizationCode::class, [
        'id' => $this->storeManagerAuthorizationCode->getKey(),
        'status' => StoreManagerAuthorizationCodeStatuses::CANCELLED->value,
    ]);
});

test('markStatusAsExpired updates the expired as cancel as expected', function (): void {
    $this->assertDatabaseHas(StoreManagerAuthorizationCode::class, [
        'id' => $this->storeManagerAuthorizationCode->getKey(),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE->value,
    ]);

    $this->storeManagerAuthorizationCodeQueries->markStatusAsExpired($this->storeManagerAuthorizationCode->getKey());

    $this->storeManagerAuthorizationCode->refresh();

    $this->assertDatabaseHas(StoreManagerAuthorizationCode::class, [
        'id' => $this->storeManagerAuthorizationCode->getKey(),
        'status' => StoreManagerAuthorizationCodeStatuses::EXPIRED->value,
    ]);
});

test('getByCode returns the storeManagerAuthorizationCode as expected', function (): void {
    $response = $this->storeManagerAuthorizationCodeQueries->getByCode($this->storeManagerAuthorizationCode->code);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->storeManagerAuthorizationCode->getKey())
        ->toHaveKeys([...$this->storeManagerAuthorizationCodeQueries->getBasicColumns()]);
});
