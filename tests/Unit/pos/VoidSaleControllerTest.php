<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\VoidSale\DataObjects\PaginatedVoidedSalesDataForPos;
use App\Domains\VoidSale\Services\VoidSaleService;
use App\Domains\Voucher\VoucherQueries;
use App\Http\Controllers\Api\Pos\VoidSaleController;
use App\Models\Cashier;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use App\Models\StoreManager;
use App\Models\VoidSale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('It calls the getPaginatedVoidedSales method and returns voided sales records', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $request = new Request();

    $paginatedVoidedSalesData = [
        'member_id' => 1,
        'employee_id' => 1,
        'is_user' => false,
        'from_date' => '',
        'to_date' => '',
        'search_text' => '',
        'after_updated_at' => null,
    ];
    $paginatedVoidedSalesDataForPos = new PaginatedVoidedSalesDataForPos(...$paginatedVoidedSalesData);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
    });

    $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedVoidedSales')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $voidSaleController = new VoidSaleController();
    $response = $voidSaleController->getPaginatedVoidedSales($request, $paginatedVoidedSalesDataForPos);

    expect($response['sales']->resource);
});

test('It throws an exception when the counter is not open But, try to void a sale', function (): void {
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => null,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $cashier);

    $voidSaleController = new VoidSaleController();
    $voidSaleController->store($request, 1);
})->throws(HttpException::class, 'The counter has not been opened yet.');

test('It throws an exception when try to void returned sale', function (): void {
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once();
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->andReturn(Location::factory()->make([
                'id' => 1,
                'company_id' => 1,
                'type_id' => LocationTypes::STORE->value,
            ]));
    });

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('checkGeneratedVoucherIsUsed')
            ->once()
            ->andReturn(false);
    });

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $sale->saleItems = new Collection([[
        'returned_quantity' => 10,
    ]]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getRegularOrLayawayOrCreditSaleByIdWithItemsAndItemUnits')
            ->andReturn($sale)
            ->once();
    });

    $voidSaleData = [
        'voided_by_store_manager_id' => 1,
        'passcode' => '123456',
        'void_sale_reason_id' => 1,
    ];

    $request = $this->mock(Request::class, function ($mock) use ($cashier, $voidSaleData): void {
        $mock->shouldReceive('validate')
            ->once();
        $mock->shouldReceive('user')
            ->once()
            ->andReturn($cashier);
        $mock->shouldReceive('all')
            ->times(4)
            ->andReturn($voidSaleData);
        $mock->shouldReceive('route');
    });

    $voidSaleController = new VoidSaleController();
    $voidSaleController->store($request, 1);
})->throws(HttpException::class, 'A returned or sale return is not voidable.');

test('It throws an exception when try to void close counter sale', function (): void {
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once();
    });

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('checkGeneratedVoucherIsUsed')
            ->once()
            ->andReturn(false);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
        ->once()
            ->andReturn(Location::factory()->make([
                'id' => 1,
                'company_id' => 1,
                'type_id' => LocationTypes::STORE->value,
            ]));
    });

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 2,
    ]);

    $sale->saleItems = new Collection([[
        'returned_quantity' => 0,
    ]]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getRegularOrLayawayOrCreditSaleByIdWithItemsAndItemUnits')
            ->andReturn($sale)
            ->once();
    });

    $voidSaleData = [
        'voided_by_store_manager_id' => 1,
        'passcode' => '123456',
        'void_sale_reason_id' => 1,
    ];

    $request = $this->mock(Request::class, function ($mock) use ($cashier, $voidSaleData): void {
        $mock->shouldReceive('validate')
            ->once();
        $mock->shouldReceive('user')
            ->once()
            ->andReturn($cashier);
        $mock->shouldReceive('all')
            ->times(4)
            ->andReturn($voidSaleData);
        $mock->shouldReceive('route');
    });

    $voidSaleController = new VoidSaleController();
    $voidSaleController->store($request, 1);
})->throws(HttpException::class, 'You can only void the current open counter sale.');

test('It throws an exception when passcode is wrong', function (): void {
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once();
    });

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('checkGeneratedVoucherIsUsed')
            ->once()
            ->andReturn(false);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
        ->once()
            ->andReturn(Location::factory()->make([
                'id' => 1,
                'company_id' => 1,
                'type_id' => LocationTypes::STORE->value,
            ]));
    });

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $sale->saleItems = new Collection([[
        'returned_quantity' => 0,
    ]]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getRegularOrLayawayOrCreditSaleByIdWithItemsAndItemUnits')
            ->andReturn($sale)
            ->once();
    });

    $storeManager = new StoreManager([
        'passcode' => '56789',
    ]);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getById')
            ->andReturn($storeManager)
            ->once();
    });

    $voidSaleData = [
        'voided_by_store_manager_id' => 1,
        'passcode' => '123456',
        'void_sale_reason_id' => 1,
    ];

    $request = $this->mock(Request::class, function ($mock) use ($cashier, $voidSaleData): void {
        $mock->shouldReceive('validate')
            ->once();
        $mock->shouldReceive('user')
            ->once()
            ->andReturn($cashier);
        $mock->shouldReceive('all')
            ->times(4)
            ->andReturn($voidSaleData);
        $mock->shouldReceive('route');
    });

    $voidSaleController = new VoidSaleController();
    $voidSaleController->store($request, 1);
})->throws(HttpException::class, 'Wrong passcode.');

test(
    'It calls the saveVoidDetails method of the VoidSaleService class and returns proper response',
    function (): void {
        Queue::fake();

        $cashier = Cashier::factory()->make([
            'id' => 1,
            'username' => 'Cashier',
            'employee_id' => 1,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once();
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
                ->andReturn(Location::factory()->make([
                    'id' => 1,
                    'company_id' => 1,
                    'type_id' => LocationTypes::STORE->value,
                ]));
        });

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('checkGeneratedVoucherIsUsed')
                ->once();
        });

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $voidSale = VoidSale::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'voided_by_store_manager_id' => 1,
            'void_sale_reason_id' => 1,
        ]);

        $sale->saleItems = new Collection([[
            'returned_quantity' => 0,
        ]]);

        $sale->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getRegularOrLayawayOrCreditSaleByIdWithItemsAndItemUnits')
                ->andReturn($sale)
                ->once();
            $mock->shouldReceive('markAsVoid')
                ->once();
            $mock->shouldReceive('loadVoidSaleRelations')
                ->times(2)
                ->andReturn($sale);
        });

        $storeManager = new StoreManager([
            'passcode' => '123456',
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
            $mock->shouldReceive('getById')
                ->andReturn($storeManager)
                ->once();
        });

        $voidSaleData = [
            'voided_by_store_manager_id' => 1,
            'passcode' => '123456',
            'void_sale_reason_id' => 1,
        ];

        $request = $this->mock(Request::class, function ($mock) use ($cashier, $voidSaleData): void {
            $mock->shouldReceive('validate')
                ->once();
            $mock->shouldReceive('user')
                ->once()
                ->andReturn($cashier);
            $mock->shouldReceive('all')
                ->times(4)
                ->andReturn($voidSaleData);
            $mock->shouldReceive('route');
        });

        $this->mock(VoidSaleService::class, function ($mock) use ($voidSale): void {
            $mock->shouldReceive('saveVoidDetails')
                ->once()
                ->andReturn($voidSale);
            $mock->shouldReceive('updateInventory')
                ->once();
            $mock->shouldReceive('checkAndRevertLoyaltyPoints')
                ->once();
            $mock->shouldReceive('checkAndRevertBookingPayment')
                ->once();
            $mock->shouldReceive('checkAndRevertCreditNote')
                ->once();
            $mock->shouldReceive('checkAndRevertVouchersGenerated')
                ->once();
            $mock->shouldReceive('checkAndRevertCashback')
                ->once();
            $mock->shouldReceive('checkAndRevertGiftCard')
                ->once();
            $mock->shouldReceive('checkAndRevertUsedVoucher')
                ->once();
            $mock->shouldReceive('revertUsedLoyaltyPoints')
                ->once();
            $mock->shouldReceive('revertUsedItemLoyaltyPoints')
                ->once();
        });

        $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
            $mock->shouldReceive('checkStoreManagerAuthorizationCode')
                ->once();
            $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
                ->once();
        });

        $voidSaleController = new VoidSaleController();
        $voidSaleController->store($request, 1);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'It calls the saveVoidDetails method of the VoidSaleService class and check EmployeeUpdatePointsAndTotalSalesJob dispatch or not',
    function (): void {
        Queue::fake();

        $cashier = Cashier::factory()->make([
            'id' => 1,
            'username' => 'Cashier',
            'employee_id' => 1,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once();
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
                ->andReturn(Location::factory()->make([
                    'id' => 1,
                    'company_id' => 1,
                    'type_id' => LocationTypes::STORE->value,
                ]));
        });

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('checkGeneratedVoucherIsUsed')
                ->once();
        });

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $voidSale = VoidSale::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'voided_by_store_manager_id' => 1,
            'void_sale_reason_id' => 1,
        ]);

        $sale->saleItems = new Collection([[
            'returned_quantity' => 0,
        ]]);

        $sale->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getRegularOrLayawayOrCreditSaleByIdWithItemsAndItemUnits')
                ->andReturn($sale)
                ->once();
            $mock->shouldReceive('markAsVoid')
                ->once();
            $mock->shouldReceive('loadVoidSaleRelations')
                ->times(2)
                ->andReturn($sale);
        });

        $storeManager = new StoreManager([
            'passcode' => '123456',
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
            $mock->shouldReceive('getById')
                ->andReturn($storeManager)
                ->once();
        });

        $voidSaleData = [
            'voided_by_store_manager_id' => 1,
            'passcode' => '123456',
            'void_sale_reason_id' => 1,
        ];

        $request = $this->mock(Request::class, function ($mock) use ($cashier, $voidSaleData): void {
            $mock->shouldReceive('validate')
                ->once();
            $mock->shouldReceive('user')
                ->once()
                ->andReturn($cashier);
            $mock->shouldReceive('all')
                ->times(4)
                ->andReturn($voidSaleData);
            $mock->shouldReceive('route');
        });

        $this->mock(VoidSaleService::class, function ($mock) use ($voidSale): void {
            $mock->shouldReceive('saveVoidDetails')
                ->once()
                ->andReturn($voidSale);
            $mock->shouldReceive('updateInventory')
                ->once();
            $mock->shouldReceive('checkAndRevertLoyaltyPoints')
                ->once();
            $mock->shouldReceive('checkAndRevertBookingPayment')
                ->once();
            $mock->shouldReceive('checkAndRevertCreditNote')
                ->once();
            $mock->shouldReceive('checkAndRevertVouchersGenerated')
                ->once();
            $mock->shouldReceive('checkAndRevertCashback')
                ->once();
            $mock->shouldReceive('checkAndRevertGiftCard')
                ->once();
            $mock->shouldReceive('checkAndRevertUsedVoucher')
                ->once();
            $mock->shouldReceive('revertUsedLoyaltyPoints')
                ->once();
            $mock->shouldReceive('revertUsedItemLoyaltyPoints')
                ->once();
        });

        $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
            $mock->shouldReceive('checkStoreManagerAuthorizationCode')
                ->once();
            $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
                ->once();
        });

        $voidSaleController = new VoidSaleController();
        $voidSaleController->store($request, 1);
    }
);

test(
    'saveSaleMismatches calls the addNew method of the PosMismatchQueries class',
    function (): void {
        $voidSale = VoidSale::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'voided_by_store_manager_id' => 1,
            'void_sale_reason_id' => 1,
        ]);

        $this->mock(PosMismatchQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $voidSaleController = new VoidSaleController();
        $voidSaleController->saveSaleMismatches($voidSale, collect(['test']));
    }
);
