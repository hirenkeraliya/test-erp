<?php

declare(strict_types=1);

use App\Domains\SaleTarget\DataObjects\SalesTargetListDataForStoreManagerApp;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

function getSalesTargetListRequestData(): Request
{
    return new Request([
        'time_interval_type_id' => TimeIntervalType::DAILY->value,
        'store_id' => 1,
        'per_page' => 10,
        'page' => 1,
        'search_text' => '',
        'sort_by' => '',
        'sort_direction' => '',
    ]);
}

test('validation passes when all details are provided are valid', function (): void {
    $request = getSalesTargetListRequestData();

    $storeManager = StoreManager::factory()->create();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $request->validate(SalesTargetListDataForStoreManagerApp::rules());

    $this->assertTrue(true);
});

test('cannot get sale target list with incomplete details', function (): void {
    $request = getSalesTargetListRequestData();

    $request['store_id'] = null;

    $storeManager = StoreManager::factory()->create();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $request->validate(SalesTargetListDataForStoreManagerApp::rules());
})->throws(ValidationException::class);

test('validation not passes when time_interval_type_id are not valid', function (): void {
    $request = getSalesTargetListRequestData();

    $request['time_interval_type_id'] = 66;

    $storeManager = StoreManager::factory()->create();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $request->validate(SalesTargetListDataForStoreManagerApp::rules());
})->throws(ValidationException::class);
