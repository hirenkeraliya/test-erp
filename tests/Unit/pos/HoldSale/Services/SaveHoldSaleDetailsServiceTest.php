<?php

declare(strict_types=1);

use App\Domains\HoldBookingPaymentItem\HoldBookingPaymentItemQueries;
use App\Domains\HoldSale\DataObjects\HoldSaleData;
use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\HoldSale\HoldSaleQueries;
use App\Domains\HoldSale\Services\CheckHoldSaleDetailsService;
use App\Domains\HoldSale\Services\SaveHoldSaleDetailsService;
use App\Domains\HoldSale\Services\SaveHoldSaleReturnDetailsService;
use App\Domains\HoldSaleDetail\HoldSaleDetailQueries;
use App\Domains\HoldSaleItem\HoldSaleItemQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Models\Cashier;
use App\Models\HoldSale;
use App\Models\HoldSaleDetail;
use App\Models\Product;

beforeEach(function (): void {
    $this->checkHoldSaleDetailsService = new CheckHoldSaleDetailsService();
    $this->companyId = 1;

    $this->saleDetails = [
        'offline_id' => '1',
        'type_id' => HoldSaleTypes::REGULAR_SALE->value,
        'total_amount_paid' => 1.00,
        'items_discount_amount' => 1.00,
        'total_discount_amount' => 1.00,
        'round_off' => 1.00,
        'return_items' => null,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promoter_ids' => [1],
            ],
        ],
        'notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
    ];

    $this->holdSaleData = new HoldSaleData(...$this->saleDetails);
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;

    $this->checkHoldSaleDetailsService->saleMismatches = collect(['Test']);

    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'retail_price' => 10.00,
        'has_batch' => false,
        'status' => false,
    ]);

    $this->cartItems = collect($this->holdSaleData->items);

    $this->cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);
});

test('saveSaleDetails method calls getNotCancelByOfflineId method of HoldSaleQueries class', function (): void {
    $this->checkHoldSaleDetailsService->cartItems = $this->cartItems;
    $this->holdSaleData->cancelled_at = '2020-01-01';
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;

    $holdSale = HoldSale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'complete_sale_id' => 1,
    ]);

    $this->checkHoldSaleDetailsService->holdSale = $holdSale;

    $holdSaleDetail = HoldSaleDetail::factory()->make([
        'id' => 1,
        'hold_sale_id' => $holdSale->id,
        'member_id' => 1,
    ]);

    $this->mock(HoldSaleQueries::class, function ($mock) use ($holdSale): void {
        $mock->shouldReceive('markAsCancel')
            ->once()
            ->andReturn($holdSale);

        $mock->shouldReceive('loadRelations')
            ->once()
            ->andReturn($holdSale);
    });

    $this->mock(HoldSaleDetailQueries::class, function ($mock) use ($holdSaleDetail): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($holdSaleDetail);
    });

    $this->mock(HoldSaleItemQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(SaveHoldSaleReturnDetailsService::class, function ($mock): void {
        $mock->shouldReceive('saveSaleReturnDetails')
            ->once();
    });

    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $saveHoldSaleDetailsService = new SaveHoldSaleDetailsService();
    $saveHoldSaleDetailsService->saveDetails($this->cashier, $this->checkHoldSaleDetailsService, []);
});

test('saveSaleDetails method calls the same class methods as expected', function (): void {
    $this->checkHoldSaleDetailsService->cartItems = $this->cartItems;

    $holdSale = HoldSale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'complete_sale_id' => 1,
    ]);

    $holdSaleDetail = HoldSaleDetail::factory()->make([
        'id' => 1,
        'hold_sale_id' => $holdSale->id,
        'member_id' => 1,
    ]);

    $this->mock(HoldSaleQueries::class, function ($mock) use ($holdSale): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($holdSale);

        $mock->shouldReceive('loadRelations')
            ->once()
            ->andReturn($holdSale);
    });

    $this->mock(HoldSaleDetailQueries::class, function ($mock) use ($holdSaleDetail): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($holdSaleDetail);
    });

    $this->mock(HoldSaleItemQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(SaveHoldSaleReturnDetailsService::class, function ($mock): void {
        $mock->shouldReceive('saveSaleReturnDetails')
            ->once();
    });

    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $saveHoldSaleDetailsService = new SaveHoldSaleDetailsService();
    $saveHoldSaleDetailsService->saveDetails($this->cashier, $this->checkHoldSaleDetailsService, []);
});

test('saveSaleDetails method calls getNotCompleteByOfflineId method of HoldSaleQueries class', function (): void {
    $this->checkHoldSaleDetailsService->cartItems = $this->cartItems;
    $this->holdSaleData->complete_at = '2020-01-01';
    $this->holdSaleData->complete_offline_id = '1';
    $this->checkHoldSaleDetailsService->companyId = 1;
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;

    $holdSale = HoldSale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'complete_sale_id' => 1,
        'complete_sale_return_id' => 1,
    ]);

    $this->checkHoldSaleDetailsService->holdSale = $holdSale;

    $holdSaleDetail = HoldSaleDetail::factory()->make([
        'id' => 1,
        'hold_sale_id' => $holdSale->id,
        'member_id' => 1,
    ]);

    $this->mock(HoldSaleQueries::class, function ($mock) use ($holdSale): void {
        $mock->shouldReceive('markAsComplete')
            ->once()
            ->andReturn($holdSale);

        $mock->shouldReceive('loadRelations')
            ->once()
            ->andReturn($holdSale);
    });

    $this->mock(HoldSaleDetailQueries::class, function ($mock) use ($holdSaleDetail): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($holdSaleDetail);
    });

    $this->mock(HoldSaleItemQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(SaveHoldSaleReturnDetailsService::class, function ($mock): void {
        $mock->shouldReceive('saveSaleReturnDetails')
            ->once();
    });

    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $saveHoldSaleDetailsService = new SaveHoldSaleDetailsService();
    $saveHoldSaleDetailsService->saveDetails($this->cashier, $this->checkHoldSaleDetailsService, []);
});

test('saveSaleDetails method calls getByOfflineId method of HoldSaleQueries class', function (): void {
    $this->checkHoldSaleDetailsService->cartItems = $this->cartItems;
    $this->holdSaleData->released_at = '2020-01-01';
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;

    $holdSale = HoldSale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'complete_sale_id' => 1,
    ]);

    $this->checkHoldSaleDetailsService->holdSale = $holdSale;

    $holdSaleDetail = HoldSaleDetail::factory()->make([
        'id' => 1,
        'hold_sale_id' => $holdSale->id,
        'member_id' => 1,
    ]);

    $this->mock(HoldSaleQueries::class, function ($mock) use ($holdSale): void {
        $mock->shouldReceive('loadRelations')
            ->once()
            ->andReturn($holdSale);
    });

    $this->mock(HoldSaleDetailQueries::class, function ($mock) use ($holdSaleDetail): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($holdSaleDetail);
    });

    $this->mock(HoldSaleItemQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(SaveHoldSaleReturnDetailsService::class, function ($mock): void {
        $mock->shouldReceive('saveSaleReturnDetails')
            ->once();
    });

    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $saveHoldSaleDetailsService = new SaveHoldSaleDetailsService();
    $saveHoldSaleDetailsService->saveDetails($this->cashier, $this->checkHoldSaleDetailsService, []);
});

test('saveSaleDetails method calls addNew method of HoldBookingPaymentItemQueries class', function (): void {
    $this->saleDetails['type_id'] = HoldSaleTypes::BOOKING_PAYMENT->value;
    $this->holdSaleData = new HoldSaleData(...$this->saleDetails);
    $this->checkHoldSaleDetailsService->cartItems = collect($this->holdSaleData->items);
    $this->holdSaleData->released_at = '2020-01-01';
    $this->holdSaleData->store_manager_authorization_code = '1234';
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;

    $holdSale = HoldSale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'complete_sale_id' => 1,
    ]);

    $this->checkHoldSaleDetailsService->holdSale = $holdSale;

    $holdSaleDetail = HoldSaleDetail::factory()->make([
        'id' => 1,
        'hold_sale_id' => $holdSale->id,
        'member_id' => 1,
    ]);

    $this->mock(HoldSaleQueries::class, function ($mock) use ($holdSale): void {
        $mock->shouldReceive('loadRelations')
            ->once()
            ->andReturn($holdSale);
    });

    $this->mock(HoldSaleDetailQueries::class, function ($mock) use ($holdSaleDetail): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($holdSaleDetail);
    });

    $this->mock(HoldBookingPaymentItemQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(SaveHoldSaleReturnDetailsService::class, function ($mock): void {
        $mock->shouldReceive('saveSaleReturnDetails')
            ->once();
    });

    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
            ->once();
    });

    $saveHoldSaleDetailsService = new SaveHoldSaleDetailsService();
    $saveHoldSaleDetailsService->saveDetails($this->cashier, $this->checkHoldSaleDetailsService, []);
});

test('saveSaleMismatches method calls addNew method of PosMismatchQueries class', function (): void {
    $holdSale = HoldSale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'complete_sale_id' => 1,
    ]);

    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $saveHoldSaleDetailsService = new SaveHoldSaleDetailsService();
    $saveHoldSaleDetailsService->saveSaleMismatches($holdSale, $this->checkHoldSaleDetailsService);
});
