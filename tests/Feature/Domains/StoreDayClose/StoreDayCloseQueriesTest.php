<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Models\Company;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreDayClose;
use App\Models\StoreDayClosePayment;
use App\Models\StoreManager;

test(
    'getPaginatedDayCloseReportList method returns the paginated list of day close details for report as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $location = Location::factory()->create([
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $employee = Employee::factory()->create();

        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $storeDayClose = StoreDayClose::factory()->create([
            'location_id' => $location->id,
            'closed_by_store_manager_id' => $storeManager->id,
        ]);

        StoreDayClosePayment::factory()->create([
            'store_day_close_id' => $storeDayClose->id,
        ]);

        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
        $response = $storeDayCloseQueries->getPaginatedDayCloseReportList([
            'search_text' => $location->name,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'location_ids' => null,
            'employee_id' => null,
            'date_range' => null,
            'closed_at' => null,
        ], $companyId);

        $this->assertEquals(1, $response->total());

        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('location_id', $location->id)
            ->toHaveKey('closed_by_store_manager_id', $storeDayClose->closed_by_store_manager_id)
            ->toHaveKey('total_sales', $storeDayClose->total_sales)
            ->toHaveKey('total_layaway_sales', $storeDayClose->total_layaway_sales)
            ->toHaveKey('total_voided_sales', $storeDayClose->total_voided_sales)
            ->toHaveKey('total_item_wise_discount_amount', $storeDayClose->total_item_wise_discount_amount)
            ->toHaveKey('total_cart_wide_discount_amount', $storeDayClose->total_cart_wide_discount_amount)
            ->toHaveKey('total_tax_amount', $storeDayClose->total_tax_amount)
            ->toHaveKey('total_sale_returns', $storeDayClose->total_sale_returns)
            ->toHaveKey('total_cashback', $storeDayClose->total_cashback)
            ->toHaveKey('total_vouchers_used', $storeDayClose->total_vouchers_used)
            ->toHaveKey('total_vouchers_generated', $storeDayClose->total_vouchers_generated)
            ->toHaveKeys(['payments']);
    }
);
test(
    'getPaginatedDayCloseReportListForStoreManager method returns the paginated list of day close details for report as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $location = Location::factory()->create([
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $employee = Employee::factory()->create();

        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $storeDayClose = StoreDayClose::factory()->create([
            'location_id' => $location->id,
            'closed_by_store_manager_id' => $storeManager->id,
        ]);

        StoreDayClosePayment::factory()->create([
            'store_day_close_id' => $storeDayClose->id,
        ]);

        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
        $response = $storeDayCloseQueries->getPaginatedDayCloseReportListForStoreManager([
            'search_text' => $location->name,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'store_manager_id' => null,
            'date_range' => null,
            'closed_at' => null,
        ], $location->id);

        $this->assertEquals(1, $response->total());

        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('location_id', $location->id)
            ->toHaveKey('closed_by_store_manager_id', $storeDayClose->closed_by_store_manager_id)
            ->toHaveKey('total_sales', $storeDayClose->total_sales)
            ->toHaveKey('total_layaway_sales', $storeDayClose->total_layaway_sales)
            ->toHaveKey('total_voided_sales', $storeDayClose->total_voided_sales)
            ->toHaveKey('total_item_wise_discount_amount', $storeDayClose->total_item_wise_discount_amount)
            ->toHaveKey('total_cart_wide_discount_amount', $storeDayClose->total_cart_wide_discount_amount)
            ->toHaveKey('total_tax_amount', $storeDayClose->total_tax_amount)
            ->toHaveKey('total_sale_returns', $storeDayClose->total_sale_returns)
            ->toHaveKey('total_cashback', $storeDayClose->total_cashback)
            ->toHaveKey('total_vouchers_used', $storeDayClose->total_vouchers_used)
            ->toHaveKey('total_vouchers_generated', $storeDayClose->total_vouchers_generated)
            ->toHaveKey('total_cash_ins_amount', $storeDayClose->total_cash_ins_amount)
            ->toHaveKey('total_cash_outs_amount', $storeDayClose->total_cash_outs_amount)
            ->toHaveKeys(['payments']);
    }
);

test('getLastDayClose method returns the latest store day closed', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $storeDayClose = StoreDayClose::factory()->create([
        'location_id' => $location->id,
    ]);
    $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
    $response = $storeDayCloseQueries->getLastDayClose($location->id);
    expect($response->toArray())
        ->toHaveKey('id', $storeDayClose->id)
        ->toHaveKey('closed_at', $storeDayClose->closed_at->format('Y-m-d H:i:s'));
});

test('addNew method adds the store day close data with null $dateOfFirstCounterOfTheDay', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $storeManager = StoreManager::factory()->create();
    $counterUpdates = CounterUpdate::factory()->create();

    $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
    $storeDayCloseQueries->addNew($location, $storeManager->id, collect([$counterUpdates]), [], null);

    $this->assertDatabaseHas('store_day_closes', [
        'location_id' => $location->id,
        'closed_by_store_manager_id' => $storeManager->id,
        'opened_at' => $location->created_at,
    ]);
});

test('addNew method adds the store day close data with yesterday datetime', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $storeManager = StoreManager::factory()->create();
    $counterUpdates = CounterUpdate::factory()->create();
    $previousDate = now()->subDay()->format('Y-m-d H:i:s');

    $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
    $storeDayCloseQueries->addNew($location, $storeManager->id, collect([$counterUpdates]), [], $previousDate);

    $this->assertDatabaseHas('store_day_closes', [
        'location_id' => $location->id,
        'closed_by_store_manager_id' => $storeManager->id,
        'opened_at' => $previousDate,
    ]);
});

test(
    'getPaginatedDayCloseListForExport method returns the paginated list of day close details for report as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;
        $location = Location::factory()->create([
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $employee = Employee::factory()->create();
        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->id,
        ]);
        $storeDayClose = StoreDayClose::factory()->create([
            'location_id' => $location->id,
            'closed_by_store_manager_id' => $storeManager->id,
        ]);
        StoreDayClosePayment::factory()->create([
            'store_day_close_id' => $storeDayClose->id,
        ]);
        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
        $response = $storeDayCloseQueries->getPaginatedDayCloseListForExport([
            'search_text' => $location->name,
            'sort_by' => null,
            'sort_direction' => null,
            'location_ids' => null,
            'employee_id' => null,
            'date_range' => null,
            'closed_at' => null,
        ], $companyId);
        expect($response->first()->toArray())
            ->toHaveKey('location_id', $location->id)
            ->toHaveKey('closed_by_store_manager_id', $storeDayClose->closed_by_store_manager_id);
    }
);

test(
    'getDayCloseListForExportInStoreManagerPanel method returns the paginated list of day close details for report as expected',
    function (): void {
        $companyId = Company::factory()->create()->id;
        $location = Location::factory()->create([
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $employee = Employee::factory()->create();
        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->id,
        ]);
        $storeDayClose = StoreDayClose::factory()->create([
            'location_id' => $location->id,
            'closed_by_store_manager_id' => $storeManager->id,
        ]);
        StoreDayClosePayment::factory()->create([
            'store_day_close_id' => $storeDayClose->id,
        ]);
        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
        $response = $storeDayCloseQueries->getDayCloseListForExportInStoreManagerPanel([
            'search_text' => $location->name,
            'sort_by' => null,
            'sort_direction' => null,
            'store_manager_id' => null,
            'date_range' => null,
            'closed_at' => null,
        ], $location->id);
        expect($response->first()->toArray())
            ->toHaveKey('location_id', $location->id)
            ->toHaveKey('closed_by_store_manager_id', $storeDayClose->closed_by_store_manager_id);
    }
);
