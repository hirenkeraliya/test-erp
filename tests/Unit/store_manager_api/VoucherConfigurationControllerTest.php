<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\VoucherConfiguration\DataObjects\StoreManagerApiVoucherConfigurationData;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Http\Controllers\Api\StoreManager\VoucherConfigurationController;
use App\Models\StoreManager;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('calls the getVouchersConfiguration method and returns vouchersConfiguration record', function (): void {
    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $filterData = [
        'page' => 1,
        'per_page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'selected_date' => now()->subMonth()->format('Y-m-d'),
    ];

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $storeManagerApiVoucherConfigurationData = new StoreManagerApiVoucherConfigurationData(...$filterData);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($voucherConfiguration): void {
        $mock->shouldReceive('getVouchersConfigurationForApplication')
            ->once()
            ->andReturn(new LengthAwarePaginator($voucherConfiguration, 1, 15));
    });

    $voucherConfigurationController = new VoucherConfigurationController();
    $response = $voucherConfigurationController->getVouchersConfiguration(
        $request,
        $storeManagerApiVoucherConfigurationData
    );

    expect($response['data']->resource)->toBeCollection();
    expect($response['total_records'])->toBe(1);
});
