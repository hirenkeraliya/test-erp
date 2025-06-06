<?php

declare(strict_types=1);

use App\Domains\HoldSale\DataObjects\HoldSaleData;
use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\HoldSale\Services\CheckHoldSaleDetailsService;
use App\Domains\HoldSale\Services\HoldSaleReturnService;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Models\Cashier;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SaleReturnReason;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

test('setDetails method works as expected', function (): void {
    $mock = $this->createPartialMock(
        HoldSaleReturnService::class,
        ['getReturnedSaleItems', 'getSaleReturnReasons', 'getReturnReasonIds']
    );

    $mock->expects($this->once())
        ->method('getReturnedSaleItems');

    $mock->expects($this->once())
        ->method('getSaleReturnReasons');

    $mock->expects($this->once())
        ->method('getReturnReasonIds');

    $mock->setDetails($this->checkHoldSaleDetailsService);
});

test(
    'it calls the getByIds method of SaleItemQueries class',
    function (): void {
        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIds')
                ->once()
                ->andReturn(collect([]));
        });

        $mock = $this->createPartialMock(HoldSaleReturnService::class, ['hasReturnItems']);

        $mock->expects($this->once())
            ->method('hasReturnItems')
            ->will($this->returnValue(true));

        $response = $mock->getReturnedSaleItems([1]);
        $this->assertTrue($response->toArray() === []);
    }
);

test('getReturnedSaleItems method returns null when cart return items not set', function (): void {
    $mock = $this->createPartialMock(HoldSaleReturnService::class, ['hasReturnItems']);

    $mock->expects($this->once())
        ->method('hasReturnItems')
        ->will($this->returnValue(false));

    $response = $mock->getReturnedSaleItems([1]);
    $this->assertTrue($response->toArray() === []);
});

test('getReturnReasonIds method returns the sale return reason ids', function (): void {
    $response = $this->holdSaleReturnService->getReturnReasonIds([1]);

    expect($response)
        ->toHaveKey(0, 1)
        ->toHaveKey(1, 2);
});

test('it calls the getByIdsAndCompanyId method of SaleReturnReasonQueries class', function (): void {
    $this->mock(SaleReturnReasonQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdsAndCompanyId')
            ->once()
            ->andReturn(collect([]));
    });

    $mock = $this->createPartialMock(HoldSaleReturnService::class, ['hasReturnItems']);

    $this->checkHoldSaleDetailsService->companyId = 1;
    $mock->checkHoldSaleDetailsService = $this->checkHoldSaleDetailsService;

    $mock->expects($this->once())
        ->method('hasReturnItems')
        ->will($this->returnValue(true));

    $response = $mock->getSaleReturnReasons([1]);
    $this->assertTrue($response->toArray() === []);
});

test('getSaleReturnReasons method returns null when cart return items not set', function (): void {
    $mock = $this->createPartialMock(HoldSaleReturnService::class, ['hasReturnItems']);

    $mock->expects($this->once())
        ->method('hasReturnItems')
        ->will($this->returnValue(false));

    $response = $mock->getSaleReturnReasons([1]);
    $this->assertTrue($response->toArray() === []);
});

test(
    'checkReturnItems method throws an exception when sale return reasons does not available in our records',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $saleReturnReason = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->holdSaleReturnService->returnedSaleItems = collect([$saleItem]);
        $this->holdSaleReturnService->saleReturnReasons = collect([$saleReturnReason]);
        $this->holdSaleReturnService->checkReturnItems();
    }
)->throws(HttpException::class, 'Some of the sale return reasons are not available in our records.');

test('hasReturnItems method returns boolean as expected', function (): void {
    $this->holdSaleReturnService->returnItems = collect(collect($this->holdSaleData->return_items));
    $response = $this->holdSaleReturnService->hasReturnItems();
    $this->assertTrue($response);

    $this->holdSaleReturnService->returnItems = collect([]);
    $response = $this->holdSaleReturnService->hasReturnItems();
    $this->assertFalse($response);
});
