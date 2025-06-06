<?php

declare(strict_types=1);

use App\Domains\HoldSale\DataObjects\HoldSaleData;
use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\HoldSale\Services\CheckHoldSaleDetailsService;
use App\Domains\HoldSale\Services\HoldSaleReturnService;
use App\Domains\HoldSale\Services\SaveHoldSaleReturnDetailsService;
use App\Domains\HoldSaleReturnItem\HoldSaleReturnItemQueries;
use App\Models\Cashier;
use App\Models\Product;
use App\Models\SaleItem;

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
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promoter_ids' => [1],
            ],
        ],
        'return_items' => [
            [
                'sale_item_id' => 1,
                'price_paid_per_unit' => '10.00',
                'quantity' => '5',
                'sale_return_details' => [
                    [
                        'quantity' => '2.00',
                        'sale_return_reason_id' => '1',
                    ],
                    [
                        'quantity' => '3.00',
                        'sale_return_reason_id' => '2',
                    ],
                ],
            ],
        ],
        'notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
    ];

    $this->holdSaleData = new HoldSaleData(...$this->saleDetails);
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;

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

    $this->holdSaleReturnService = new HoldSaleReturnService();
    $this->holdSaleReturnService->returnItems = collect($this->holdSaleData->return_items);
});

test('saveSaleReturnDetails method calls addNew method of HoldSaleReturnItemQueries class', function (): void {
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $this->holdSaleReturnService->returnedSaleItems = collect([$saleItem]);
    $this->checkHoldSaleDetailsService->holdSaleReturnService = $this->holdSaleReturnService;

    $this->mock(HoldSaleReturnItemQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(2);
    });

    $saveHoldSaleReturnDetailsService = new SaveHoldSaleReturnDetailsService();
    $saveHoldSaleReturnDetailsService->saveSaleReturnDetails(1, $this->checkHoldSaleDetailsService);
});
