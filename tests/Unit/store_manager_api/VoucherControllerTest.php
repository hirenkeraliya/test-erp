<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Http\Controllers\Api\StoreManager\VoucherController;
use App\Models\StoreManager;
use Illuminate\Http\Request;

test('calls the getStoreWiseVouchers method and returns vouchers record', function (): void {
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('getVoucherStoreWiseForApplication')
            ->once()
            ->andReturn(collect());
    });

    $voucherController = new VoucherController();
    $response = $voucherController->getStoreWiseVouchers($request, 1);

    expect($response['vouchers']->resource)->toBeCollection();
});
