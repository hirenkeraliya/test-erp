<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleReturnService;
use App\Domains\Sale\Services\SaveSaleReturnDetailsService;
use App\Domains\SaleDiscount\Enums\DiscountableTypes;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemDiscount\Enums\DiscountableTypes as EnumsDiscountableTypes;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleCashback;
use App\Models\SaleDiscount;
use App\Models\SaleItem;
use App\Models\SaleItemDiscount;
use App\Models\SaleItemUnit;
use App\Models\SalePayment;
use App\Models\SaleReturnReason;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'cashback_round_off_amount' => null,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promotion_id' => 1,
            ],
        ],
        'return_items' => [
            [
                'sale_item_id' => 1,
                'price_paid_per_unit' => '11.00',
                'quantity' => '5',
                'sale_return_details' => [
                    [
                        'quantity' => '2.00',
                        'sale_return_reason_id' => '1',
                        'batch_number' => '123456',
                    ],
                    [
                        'quantity' => '3.00',
                        'sale_return_reason_id' => '2',
                        'batch_number' => 'ABCDEF',
                    ],
                ],
            ],
        ],
        'payments' => [
            [
                'type_id' => 1,
                'amount' => '100',
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
        'is_layaway' => true,
        'cart_promotion_id' => null,
        'sale_round_off_amount' => 0.01,
    ];

    $this->saleData = new SaleData(...$this->saleDetails);

    $this->companyId = 1;

    $this->cashier = makeCashierForPosWithCounterUpdateId();

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->checkSaleDetailsService = new CheckSaleDetailsService();
    $this->saveSaleReturnDetailsService = new SaveSaleReturnDetailsService();
    $this->saleReturnService = new SaleReturnService();
    $this->saleReturnService->returnItems = collect($this->saleData->return_items);
    $this->cartItems = collect($this->saleData->items);
    $this->company = Company::factory()->make([
        'id' => 1,
        'allow_only_return' => true,
        'default_country_id' => 1,
    ]);

    $this->checkSaleDetailsService->company = $this->company;
});

test('setDetails method works as expected', function (): void {
    $mock = $this->createPartialMock(
        SaleReturnService::class,
        ['getReturnedSaleItems', 'getSaleReturnReasons', 'getReturnBatchNumbers', 'getBatches']
    );

    $this->checkSaleDetailsService->saleData = $this->saleData;

    $mock->setDetails($this->checkSaleDetailsService);

    $this->assertTrue($mock->saleReturnMismatches->toArray() === []);
});

test('hasCartPromotion method returns boolean as expected', function (): void {
    $this->saleReturnService->returnItems = collect(collect($this->saleData->return_items));
    $response = $this->saleReturnService->hasReturnItems();
    $this->assertTrue($response);

    $this->saleReturnService->returnItems = collect([]);
    $response = $this->saleReturnService->hasReturnItems();
    $this->assertFalse($response);
});

test('getReturnItemsSubtotal method returns the cart subtotal as expected', function (): void {
    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'price_paid_per_unit' => 10,
        'quantity' => 10,
    ]);

    $this->saleReturnService->returnedSaleItems = collect([$saleItem]);
    $response = $this->saleReturnService->getReturnItemsSubtotal();
    $this->assertTrue(50.00 === $response);
});

test(
    'it calls the getByIdsWithRelations method of SaleItemQueries class',
    function (): void {
        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdsWithRelations')
                ->once()
                ->andReturn(collect([]));
        });

        $mock = $this->createPartialMock(SaleReturnService::class, ['hasReturnItems']);

        $mock->expects($this->once())
            ->method('hasReturnItems')
            ->will($this->returnValue(true));

        $response = $mock->getReturnedSaleItems([1]);
        $this->assertTrue($response->toArray() === []);
    }
);

test('getReturnedSaleItems method returns null when cart return items not set', function (): void {
    $mock = $this->createPartialMock(SaleReturnService::class, ['hasReturnItems']);

    $mock->expects($this->once())
        ->method('hasReturnItems')
        ->will($this->returnValue(false));

    $response = $mock->getReturnedSaleItems([1]);
    $this->assertTrue($response->toArray() === []);
});

test('getReturnReasonIds method returns the sale return reason ids', function (): void {
    $response = $this->saleReturnService->getReturnReasonIds([1]);

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

    $mock = $this->createPartialMock(SaleReturnService::class, ['hasReturnItems']);

    $this->checkSaleDetailsService->companyId = 1;
    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $mock->expects($this->once())
        ->method('hasReturnItems')
        ->will($this->returnValue(true));

    $response = $mock->getSaleReturnReasons([1]);
    $this->assertTrue($response->toArray() === []);
});

test('getSaleReturnReasons method returns null when cart return items not set', function (): void {
    $mock = $this->createPartialMock(SaleReturnService::class, ['hasReturnItems']);

    $mock->expects($this->once())
        ->method('hasReturnItems')
        ->will($this->returnValue(false));

    $response = $mock->getSaleReturnReasons([1]);
    $this->assertTrue($response->toArray() === []);
});

test('checkReturnItems method throws an exception when return multiple sale on single request', function (): void {
    $saleItems = [];
    $saleItems[] = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $saleItems[] = SaleItem::factory()->make([
        'id' => 2,
        'sale_id' => 2,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $this->saleReturnService->returnedSaleItems = collect($saleItems);
    $this->saleReturnService->checkReturnItems(1, true);
})->throws(HttpException::class, 'You cannot return items from multiple sales in a single request');

test('checkReturnItems method throws an exception when return pending layaway sale', function (): void {
    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $saleItem->sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'layaway_pending_amount' => 100,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
    ]);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
    });

    $this->saleReturnService->returnedSaleItems = collect([$saleItem]);
    $this->saleReturnService->saleReturnMismatches = collect([]);
    $this->saleReturnService->checkReturnItems(1, true);
})->throws(HttpException::class, 'Pending Layaway sale cannot be returned.');

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

        $saleItem->sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleReturnReason = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->saleReturnService->returnedSaleItems = collect([$saleItem]);
        $this->saleReturnService->saleReturnReasons = collect([$saleReturnReason]);
        $this->saleReturnService->checkReturnItems(1, true);
    }
)->throws(HttpException::class, 'Some of the sale return reasons are not available in our records.');

test('checkReturnItems method sets saleReturnMismatches when sales return days limit is over', function (): void {
    $saleReturnReason = [];
    $this->location->sales_return_days_limit = 10;
    $this->checkSaleDetailsService->location = $this->location;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $saleItem->sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'happened_at' => now()->subDays(10),
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
    });

    $saleItem->sale->cashback = null;
    $saleItem->sale->payments = collect([]);

    $saleItem->product = commonGetProductDetails(false);

    $saleReturnReason[] = SaleReturnReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $saleReturnReason[] = SaleReturnReason::factory()->make([
        'id' => 2,
        'company_id' => 1,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('loadSaleItems')
            ->never()
            ->andReturn($saleItem->sale);
    });

    $mock = $this->createPartialMock(
        SaleReturnService::class,
        [
            'hasLoyaltyPointsAsPaymentTypeInOriginalSale',
            'checkCartWideDiscountOnOriginalSale',
            'checkExchangeProductOnOriginalSale',
            'checkItemWiseDiscountOnOriginalSale',
        ]
    );

    $mock->expects($this->never())
        ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale');

    $mock->expects($this->never())
        ->method('checkCartWideDiscountOnOriginalSale');

    $mock->expects($this->never())
        ->method('checkExchangeProductOnOriginalSale');

    $mock->expects($this->never())
        ->method('checkItemWiseDiscountOnOriginalSale');

    $mock->returnedSaleItems = collect([$saleItem]);
    $mock->saleReturnMismatches = collect([]);
    $mock->saleReturnReasons = collect($saleReturnReason);
    $mock->returnItems = collect($this->saleData->return_items);
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
    $mock->checkReturnItems(1, true);
})->throws(HttpException::class, 'Sale cannot be returned after 10 days.');

test(
    'checkReturnItems method throws an exception when cashback is applied in sale',
    function (): void {
        $saleReturnReason = [];
        $this->location->sales_return_days_limit = 10;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 2,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $saleItem->sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItem->sale->cashback = SaleCashback::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'cashback_id' => 1,
            'cash_movement_id' => 1,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['getReturnReasonIds', 'areAllOfTheReturnItemsBeingExchanged']
        );

        $mock->expects($this->once())
            ->method('getReturnReasonIds')
            ->will($this->returnValue([1, 2]));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->returnedSaleItems = collect([$saleItem]);
        $mock->saleReturnReasons = collect($saleReturnReason);
        $mock->saleReturnMismatches = collect([]);
        $mock->checkReturnItems(1, true);
    }
)->throws(
    HttpException::class,
    'You cannot return items because cashback was applied in the respective sale. You can just exchange the items.'
);

test(
    'checkReturnItems method throws an exception when Loyalty Point is used in original sale',
    function (): void {
        $saleReturnReason = [];
        $this->location->sales_return_days_limit = 10;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 2,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $saleItem->sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItem->sale->cashback = null;

        $saleItem->sale->payments = collect([
            SalePayment::factory()->make([
                'id' => 1,
                'sale_id' => 1,
                'payment_type_id' => 4,
                'counter_update_id' => 1,
            ]),
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['getReturnReasonIds', 'areAllOfTheReturnItemsBeingExchanged']
        );

        $mock->expects($this->once())
            ->method('getReturnReasonIds')
            ->will($this->returnValue([1, 2]));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->returnedSaleItems = collect([$saleItem]);
        $mock->saleReturnReasons = collect($saleReturnReason);
        $mock->saleReturnMismatches = collect([]);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkReturnItems(1, true);
    }
)->throws(
    HttpException::class,
    'You cannot return items of the sale that used loyalty points as payment. You can just exchange the items.'
);

test(
    'checkReturnItems method sets saleReturnMismatches when requested return quantity more then sale item quantity',
    function (): void {
        $saleReturnReason = [];
        $this->location->sales_return_days_limit = 10;
        $this->checkSaleDetailsService->location = $this->location;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'quantity' => 10,
            'returned_quantity' => 6,
        ]);

        $saleItem->sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItem->sale->cashback = null;
        $saleItem->sale->payments = collect([]);

        $saleItem->product = commonGetProductDetails(false);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(SaleQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('loadSaleItems')
                ->once()
                ->andReturn($saleItem->sale);
        });

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            [
                'getReturnReasonIds',
                'hasLoyaltyPointsAsPaymentTypeInOriginalSale',
                'checkCartWideDiscountOnOriginalSale',
                'checkExchangeProductOnOriginalSale',
                'checkItemWiseDiscountOnOriginalSale',
            ]
        );

        $mock->expects($this->once())
            ->method('getReturnReasonIds')
            ->will($this->returnValue([1, 2]));

        $mock->expects($this->once())
            ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale');

        $mock->expects($this->once())
            ->method('checkCartWideDiscountOnOriginalSale');

        $mock->expects($this->once())
            ->method('checkExchangeProductOnOriginalSale');

        $mock->expects($this->once())
            ->method('checkItemWiseDiscountOnOriginalSale');

        $mock->returnedSaleItems = collect([$saleItem]);
        $mock->saleReturnMismatches = collect([]);
        $mock->saleReturnReasons = collect($saleReturnReason);
        $mock->returnItems = collect($this->saleData->return_items);
        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkReturnItems(1, true);
    }
)->throws(HttpException::class, 'Only 4 units can be given for return. But requested return quantities are 5.');

test(
    'checkReturnItems method sets saleReturnMismatches when requested return quantity and sale return reason wise quantity are not match',
    function (): void {
        $saleReturnReason = [];
        $this->location->sales_return_days_limit = 10;
        $this->checkSaleDetailsService->location = $this->location;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'quantity' => 10,
            'returned_quantity' => 0,
        ]);

        $saleItem->sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $saleItem->sale->cashback = null;
        $saleItem->sale->payments = collect([]);

        $saleItem->product = commonGetProductDetails(false);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('loadSaleItems')
                ->once()
                ->andReturn($saleItem->sale);
        });

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            [
                'getReturnReasonIds',
                'hasLoyaltyPointsAsPaymentTypeInOriginalSale',
                'checkCartWideDiscountOnOriginalSale',
                'checkExchangeProductOnOriginalSale',
                'checkItemWiseDiscountOnOriginalSale',
            ]
        );

        $mock->expects($this->once())
            ->method('getReturnReasonIds')
            ->will($this->returnValue([1, 2]));

        $mock->expects($this->once())
            ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale');

        $mock->expects($this->once())
            ->method('checkCartWideDiscountOnOriginalSale');

        $mock->expects($this->once())
            ->method('checkExchangeProductOnOriginalSale');

        $mock->expects($this->once())
            ->method('checkItemWiseDiscountOnOriginalSale');

        $this->saleDetails['return_items'][0]['sale_return_details'][0]['quantity'] = '4.00';
        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleData = new SaleData(...$this->saleDetails);
        $mock->returnItems = collect($this->saleData->return_items);
        $mock->returnedSaleItems = collect([$saleItem]);
        $mock->saleReturnMismatches = collect([]);
        $mock->saleReturnReasons = collect($saleReturnReason);

        $this->checkSaleDetailsService->saleData = $this->saleData;
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkReturnItems(1, true);
    }
)->throws(HttpException::class, 'Sale Return total quantity mismatch');

test('checkReturnItems method sets saleReturnMismatches when return item price mismatched', function (): void {
    $saleReturnReason = [];
    $this->location->sales_return_days_limit = 10;
    $this->checkSaleDetailsService->location = $this->location;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'total_price_paid' => 100,
        'derivative_id' => 1,
        'original_price_per_unit' => 10.00,
        'quantity' => 10,
        'returned_quantity' => 0,
    ]);

    $saleItem->sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'happened_at' => now()->subDays(5),
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
    });

    $saleItem->sale->cashback = null;
    $saleItem->sale->payments = collect([]);

    $saleItem->product = commonGetProductDetails(false);

    $saleReturnReason[] = SaleReturnReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $saleReturnReason[] = SaleReturnReason::factory()->make([
        'id' => 2,
        'company_id' => 1,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('loadSaleItems')
            ->once()
            ->andReturn($saleItem->sale);
    });

    $mock = $this->createPartialMock(
        SaleReturnService::class,
        [
            'getReturnReasonIds',
            'hasLoyaltyPointsAsPaymentTypeInOriginalSale',
            'checkCartWideDiscountOnOriginalSale',
            'checkExchangeProductOnOriginalSale',
            'checkItemWiseDiscountOnOriginalSale',
        ]
    );

    $mock->expects($this->once())
        ->method('getReturnReasonIds')
        ->will($this->returnValue([1, 2]));

    $mock->expects($this->once())
        ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale');

    $mock->expects($this->once())
        ->method('checkCartWideDiscountOnOriginalSale');

    $mock->expects($this->once())
        ->method('checkExchangeProductOnOriginalSale');

    $mock->expects($this->once())
        ->method('checkItemWiseDiscountOnOriginalSale');

    $mock->returnedSaleItems = collect([$saleItem]);
    $mock->saleReturnMismatches = collect([]);
    $mock->saleReturnReasons = collect($saleReturnReason);
    $mock->returnItems = collect($this->saleData->return_items);
    $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $mock->checkReturnItems(1, true);
})->throws(HttpException::class, 'Return Item Price mismatched.');

test('checkReturnItems method returns the response as expected', function (): void {
    $saleReturnReason = [];
    $this->location->sales_return_days_limit = 10;
    $this->checkSaleDetailsService->location = $this->location;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'original_price_per_unit' => 11.00,
        'price_paid_per_unit' => 11.00,
        'quantity' => 10,
        'returned_quantity' => 0,
    ]);

    $saleItem->sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'happened_at' => now()->subDays(5),
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
    });

    $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
    $this->saleData = new SaleData(...$this->saleDetails);

    $saleItem->sale->cashback = null;
    $saleItem->sale->payments = collect([]);

    $saleItem->product = commonGetProductDetails(false);

    $saleReturnReason[] = SaleReturnReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $saleReturnReason[] = SaleReturnReason::factory()->make([
        'id' => 2,
        'company_id' => 1,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('loadSaleItems')
            ->once()
            ->andReturn($saleItem->sale);
    });

    $mock = $this->createPartialMock(
        SaleReturnService::class,
        [
            'getReturnReasonIds',
            'hasLoyaltyPointsAsPaymentTypeInOriginalSale',
            'checkCartWideDiscountOnOriginalSale',
            'checkExchangeProductOnOriginalSale',
            'checkItemWiseDiscountOnOriginalSale',
        ]
    );

    $mock->expects($this->once())
        ->method('getReturnReasonIds')
        ->will($this->returnValue([1, 2]));

    $mock->expects($this->once())
        ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale');

    $mock->expects($this->once())
        ->method('checkCartWideDiscountOnOriginalSale');

    $mock->expects($this->once())
        ->method('checkExchangeProductOnOriginalSale');

    $mock->expects($this->once())
        ->method('checkItemWiseDiscountOnOriginalSale');

    $mock->returnedSaleItems = collect([$saleItem]);
    $mock->saleReturnMismatches = collect([]);
    $mock->saleReturnReasons = collect($saleReturnReason);
    $mock->returnItems = collect($this->saleData->return_items);
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $response = $mock->checkReturnItems(1, true);

    $this->assertNull($response);
    $this->assertTrue($mock->saleReturnMismatches->toArray() === []);
});

test('getReturnBatchNumbers method returns the batch numbers', function (): void {
    $response = $this->saleReturnService->getReturnBatchNumbers();

    expect($response)
        ->toHaveKey(0, '123456')
        ->toHaveKey(1, 'ABCDEF');
});

test('it calls the getByNumbers method of BatchQueries class', function (): void {
    $this->mock(BatchQueries::class, function ($mock): void {
        $mock->shouldReceive('getByNumbers')
            ->once()
            ->andReturn(new Collection([]));
    });

    $mock = $this->createPartialMock(SaleReturnService::class, ['hasReturnItems']);

    $this->checkSaleDetailsService->companyId = 1;
    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $mock->expects($this->once())
        ->method('hasReturnItems')
        ->will($this->returnValue(true));

    $response = $mock->getBatches(['123456']);
    $this->assertTrue($response->toArray() === []);
});

test(
    'checkReturnItems method throws an exception when requested product is batch and batch number not specified',
    function (): void {
        $saleReturnReason = [];
        $this->location->sales_return_days_limit = 10;
        $this->checkSaleDetailsService->location = $this->location;

        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleData = new SaleData(...$this->saleDetails);

        $this->checkSaleDetailsService->saleData = $this->saleData;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'original_price_per_unit' => 11.00,
            'price_paid_per_unit' => 11.00,
            'quantity' => 10,
            'returned_quantity' => 0,
        ]);

        $saleItem->sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItem->sale->cashback = null;
        $saleItem->sale->payments = collect([]);

        $saleItem->product = commonGetProductDetails(true);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('loadSaleItems')
                ->once()
                ->andReturn($saleItem->sale);
        });

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            [
                'getReturnReasonIds',
                'hasLoyaltyPointsAsPaymentTypeInOriginalSale',
                'checkCartWideDiscountOnOriginalSale',
                'checkExchangeProductOnOriginalSale',
                'checkItemWiseDiscountOnOriginalSale',
            ]
        );

        $mock->expects($this->once())
            ->method('getReturnReasonIds')
            ->will($this->returnValue([1, 2]));

        $mock->expects($this->once())
            ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale');

        $mock->expects($this->once())
            ->method('checkCartWideDiscountOnOriginalSale');

        $mock->expects($this->once())
            ->method('checkExchangeProductOnOriginalSale');

        $mock->expects($this->once())
            ->method('checkItemWiseDiscountOnOriginalSale');

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->saleData->return_items[0]['sale_return_details'][0]['batch_number'] = '';
        $mock->returnItems = collect($this->saleData->return_items);
        $mock->returnedSaleItems = collect([$saleItem]);
        $mock->saleReturnMismatches = collect([]);
        $mock->saleReturnReasons = collect($saleReturnReason);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkReturnItems(1, true);
    }
)->throws(HttpException::class, 'Batch number is required for the specified product (Name: Product 1).');

test(
    'checkReturnItems method throws an exception when specified batch number is not available in our records',
    function (): void {
        $saleReturnReason = [];
        $this->location->sales_return_days_limit = 10;
        $this->checkSaleDetailsService->location = $this->location;
        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'original_price_per_unit' => 11.00,
            'price_paid_per_unit' => 11.00,
            'quantity' => 10,
            'returned_quantity' => 0,
        ]);

        $saleItem->sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $saleItem->sale->cashback = null;
        $saleItem->sale->payments = collect([]);

        $saleItem->product = commonGetProductDetails(true);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('loadSaleItems')
                ->once()
                ->andReturn($saleItem->sale);
        });

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            [
                'getReturnReasonIds',
                'hasLoyaltyPointsAsPaymentTypeInOriginalSale',
                'checkCartWideDiscountOnOriginalSale',
                'checkExchangeProductOnOriginalSale',
                'checkItemWiseDiscountOnOriginalSale',
            ]
        );

        $mock->expects($this->once())
            ->method('getReturnReasonIds')
            ->will($this->returnValue([1, 2]));

        $mock->expects($this->once())
            ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale');

        $mock->expects($this->once())
            ->method('checkCartWideDiscountOnOriginalSale');

        $mock->expects($this->once())
            ->method('checkExchangeProductOnOriginalSale');

        $mock->expects($this->once())
            ->method('checkItemWiseDiscountOnOriginalSale');

        $mock->returnItems = collect($this->saleData->return_items);
        $mock->returnedSaleItems = collect([$saleItem]);
        $mock->saleReturnMismatches = collect([]);
        $mock->batches = collect([]);
        $mock->saleReturnReasons = collect($saleReturnReason);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkReturnItems(1, true);
    }
)->throws(HttpException::class, '123456 is not available in our records');

test(
    'checkReturnItems method sets saleReturnMismatches when requested return quantity and specified batch number quantity does not match.',
    function (): void {
        $saleItemUnits = [];
        $batches = [];
        $saleReturnReason = [];
        $this->location->sales_return_days_limit = 10;
        $this->checkSaleDetailsService->location = $this->location;
        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleData = new SaleData(...$this->saleDetails);

        $this->checkSaleDetailsService->saleData = $this->saleData;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'original_price_per_unit' => 11.00,
            'price_paid_per_unit' => 11.00,
            'quantity' => 10,
            'returned_quantity' => 0,
        ]);

        $saleItem->sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $saleItem->sale->cashback = null;
        $saleItem->sale->payments = collect([]);

        $saleItemUnits[] = SaleItemUnit::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 10,
            'returned_quantity' => 9,
        ]);

        $saleItemUnits[] = SaleItemUnit::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 2,
            'quantity' => 10,
            'returned_quantity' => 9,
        ]);

        $saleItem->saleItemUnits = collect($saleItemUnits);

        $saleItem->product = commonGetProductDetails(true);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $batches[] = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'number' => '123456',
        ]);

        $batches[] = Batch::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'ABCDEF',
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('loadSaleItems')
                ->once()
                ->andReturn($saleItem->sale);
        });

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            [
                'getReturnReasonIds',
                'hasLoyaltyPointsAsPaymentTypeInOriginalSale',
                'checkCartWideDiscountOnOriginalSale',
                'checkExchangeProductOnOriginalSale',
                'checkItemWiseDiscountOnOriginalSale',
            ]
        );

        $mock->expects($this->once())
            ->method('getReturnReasonIds')
            ->will($this->returnValue([1, 2]));

        $mock->expects($this->once())
            ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale');

        $mock->expects($this->once())
            ->method('checkCartWideDiscountOnOriginalSale');

        $mock->expects($this->once())
            ->method('checkExchangeProductOnOriginalSale');

        $mock->expects($this->once())
            ->method('checkItemWiseDiscountOnOriginalSale');

        $mock->returnItems = collect($this->saleData->return_items);
        $mock->returnedSaleItems = collect([$saleItem]);
        $mock->saleReturnMismatches = collect([]);
        $mock->batches = collect($batches);
        $mock->saleReturnReasons = collect($saleReturnReason);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkReturnItems(1, true);
    }
)->throws(
    HttpException::class,
    'Number of units sold for the specified product for the return named: Product 1 is only 1. But requested return quantities are 2.00.'
);

test('areAllOfTheReturnItemsBeingExchanged method returns as expected', function (): void {
    $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

    $mock->expects($this->once())
        ->method('isProductBeingExchanged')
        ->will($this->returnValue(true));

    $mock->returnItems = collect($this->saleData->return_items);
    $response = $mock->areAllOfTheReturnItemsBeingExchanged();
    $this->assertTrue($response);

    $mock2 = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

    $mock2->expects($this->once())
        ->method('isProductBeingExchanged')
        ->will($this->returnValue(false));

    $mock2->returnItems = collect($this->saleData->return_items);
    $response = $mock2->areAllOfTheReturnItemsBeingExchanged();
    $this->assertFalse($response);
});

test(
    'hasLoyaltyPointsAsPaymentTypeInOriginalSale method returns true when Loyalty Point is used in original sale',
    function (): void {
        $payments = collect([
            SalePayment::factory()->make([
                'id' => 1,
                'sale_id' => 1,
                'payment_type_id' => 4,
                'counter_update_id' => 1,
            ]),
        ]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['areAllOfTheReturnItemsBeingExchanged']);

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $response = $mock->hasLoyaltyPointsAsPaymentTypeInOriginalSale($payments);
        $this->assertTrue($response);
    }
);

test(
    'hasLoyaltyPointsAsPaymentTypeInOriginalSale method returns false when Loyalty Point is not used in the original sale',
    function (): void {
        $payments = collect([
            SalePayment::factory()->make([
                'id' => 1,
                'sale_id' => 1,
                'payment_type_id' => 1,
                'counter_update_id' => 1,
            ]),
        ]);

        $response = $this->saleReturnService->hasLoyaltyPointsAsPaymentTypeInOriginalSale($payments);
        $this->assertFalse($response);
    }
);

test(
    'hasLoyaltyPointsAsPaymentTypeInOriginalSale method returns false when Loyalty Point used in the original sale but tries to exchange items',
    function (): void {
        $payments = collect([
            SalePayment::factory()->make([
                'id' => 1,
                'sale_id' => 1,
                'payment_type_id' => 4,
                'counter_update_id' => 1,
            ]),
        ]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['areAllOfTheReturnItemsBeingExchanged']);

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(true));

        $response = $mock->hasLoyaltyPointsAsPaymentTypeInOriginalSale($payments);
        $this->assertFalse($response);
    }
);

test('isProductBeingExchanged method returns as expected', function (): void {
    $product = Product::factory()->make([
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
        'article_number' => '123456',
        'status' => false,
    ]);

    $this->checkSaleDetailsService->cartItems = $this->cartItems;
    $this->checkSaleDetailsService->products = collect([$product]);
    $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'price_paid_per_unit' => 10,
        'quantity' => 10,
    ]);

    $saleItem->product = $product;
    $this->saleReturnService->returnedSaleItems = collect([$saleItem]);

    $response = $this->saleReturnService->isProductBeingExchanged(
        $this->saleDetails['return_items'][0]['sale_item_id']
    );

    $this->assertFalse($response);

    $this->saleData->items[0]['quantity'] = '5';
    $this->saleData->items[0]['is_exchange'] = true;
    $this->cartItems = collect($this->saleData->items);
    $this->checkSaleDetailsService->cartItems = $this->cartItems;

    $response = $this->saleReturnService->isProductBeingExchanged(
        $this->saleDetails['return_items'][0]['sale_item_id']
    );
    $this->assertTrue($response);
});

test(
    'areAllTheGroupItemsBeingReturned method returns false when all items of the group are not returned.',
    function (): void {
        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $saleItem = SaleItem::factory()->make([
            'id' => 2,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleItems = collect([$saleItem]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

        $mock->returnItems = collect($this->saleData->return_items);

        $response = $mock->areAllTheGroupItemsBeingReturned($sale, 1);

        $this->assertFalse($response);
    }
);

test(
    'areAllTheGroupItemsBeingReturned method returns false when one of the items of the group is already exchanged.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'returned_quantity' => 2,
            'quantity' => 10,
            'group_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleItems = collect([$saleItem]);

        $this->saleReturnService->returnItems = collect($this->saleData->return_items);

        $response = $this->saleReturnService->areAllTheGroupItemsBeingReturned($sale, 1);

        $this->assertFalse($response);
    }
);

test(
    'areAllTheGroupItemsBeingReturned method returns false when one of the items of the group is already exchanging in the current request.',
    function (): void {
        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'returned_quantity' => 0,
            'quantity' => 10,
            'group_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleItems = collect([$saleItem]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

        $mock->expects($this->once())
            ->method('isProductBeingExchanged')
            ->will($this->returnValue(true));

        $mock->returnItems = collect($this->saleData->return_items);

        $response = $mock->areAllTheGroupItemsBeingReturned($sale, 1);

        $this->assertFalse($response);
    }
);

test(
    'areAllTheGroupItemsBeingReturned method returns true when all items of the group are returned',
    function (): void {
        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'returned_quantity' => 0,
            'quantity' => 5,
            'group_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleItems = collect([$saleItem]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

        $mock->expects($this->once())
            ->method('isProductBeingExchanged')
            ->will($this->returnValue(false));

        $mock->returnItems = collect($this->saleData->return_items);

        $response = $mock->areAllTheGroupItemsBeingReturned($sale, 1);

        $this->assertTrue($response);
    }
);

test(
    'areAllOfTheSaleItemsBeingReturned method returns false when one of the items of the sale is not returned.',
    function (): void {
        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $saleItem = SaleItem::factory()->make([
            'id' => 2,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleItems = collect([$saleItem]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

        $mock->returnItems = collect($this->saleData->return_items);

        $response = $mock->areAllOfTheSaleItemsBeingReturned($sale, 1);

        $this->assertFalse($response);
    }
);

test(
    'areAllOfTheSaleItemsBeingReturned method returns false when one of the sale item is requested for exchange.',
    function (): void {
        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'returned_quantity' => 0,
            'quantity' => 10,
            'group_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleItems = collect([$saleItem]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

        $mock->expects($this->once())
            ->method('isProductBeingExchanged')
            ->will($this->returnValue(true));

        $mock->returnItems = collect($this->saleData->return_items);

        $response = $mock->areAllOfTheSaleItemsBeingReturned($sale, 1);

        $this->assertFalse($response);
    }
);

test(
    'areAllOfTheSaleItemsBeingReturned method returns false when one of the item is already exchanged',
    function (): void {
        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'returned_quantity' => 2,
            'quantity' => 10,
            'group_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleItems = collect([$saleItem]);

        $this->saleReturnService->returnItems = collect($this->saleData->return_items);

        $response = $this->saleReturnService->areAllOfTheSaleItemsBeingReturned($sale, 1);

        $this->assertFalse($response);
    }
);

test(
    'areAllOfTheSaleItemsBeingReturned method returns false when all the items of the sale is not returned.',
    function (): void {
        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'returned_quantity' => 0,
            'quantity' => 10,
            'group_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleItems = collect([$saleItem]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

        $mock->returnItems = collect($this->saleData->return_items);

        $response = $mock->areAllOfTheSaleItemsBeingReturned($sale, 1);

        $this->assertFalse($response);
    }
);

test('areAllOfTheSaleItemsBeingReturned method returns true when all the sale items are returned.', function (): void {
    $this->checkSaleDetailsService->cartItems = $this->cartItems;
    $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'price_paid_per_unit' => 10,
        'returned_quantity' => 0,
        'quantity' => 5,
        'group_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);
    $sale->saleItems = collect([$saleItem]);

    $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

    $mock->expects($this->once())
        ->method('isProductBeingExchanged')
        ->will($this->returnValue(false));

    $mock->returnItems = collect($this->saleData->return_items);

    $response = $mock->areAllOfTheSaleItemsBeingReturned($sale, 1);

    $this->assertTrue($response);
});

test(
    'checkCartWideDiscountOnOriginalSale method returns null when no discount is applied on the original sale.',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $sale->saleDiscounts = collect([]);

        $this->saleReturnMismatches = collect([]);
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $response = $this->saleReturnService->checkCartWideDiscountOnOriginalSale($sale);

        $this->assertNull($response);
    }
);

test(
    'checkCartWideDiscountOnOriginalSale method returns null when not allow only return.',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $this->saleReturnMismatches = collect([]);
        $this->checkSaleDetailsService->company->allow_only_return = false;
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $response = $this->saleReturnService->checkCartWideDiscountOnOriginalSale($sale);

        $this->assertNull($response);
    }
);

test(
    'checkCartWideDiscountOnOriginalSale method returns null when Cart Wide Promotion did not apply in the original sale.',
    function (): void {
        $saleDiscount = SaleDiscount::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => DiscountableTypes::getDiscountableClass(DiscountableTypes::CASHBACK->value),
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $sale->saleDiscounts = collect([$saleDiscount]);

        $this->saleReturnMismatches = collect([]);
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $response = $this->saleReturnService->checkCartWideDiscountOnOriginalSale($sale);

        $this->assertNull($response);
    }
);

test(
    'checkCartWideDiscountOnOriginalSale method returns null when Cart Wide Promotion applied and all the items of the sale is exchanging.',
    function (): void {
        $saleDiscount = SaleDiscount::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => DiscountableTypes::getDiscountableClass(DiscountableTypes::PROMOTION->value),
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleDiscounts = collect([$saleDiscount]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['areAllOfTheReturnItemsBeingExchanged']);

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(true));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkCartWideDiscountOnOriginalSale($sale);

        $this->assertNull($response);
    }
);

test(
    'checkCartWideDiscountOnOriginalSale method returns null when Cart Wide Promotion apply and all the item of original sale is returning.',
    function (): void {
        $saleDiscount = SaleDiscount::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => DiscountableTypes::getDiscountableClass(DiscountableTypes::PROMOTION->value),
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleDiscounts = collect([$saleDiscount]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['areAllOfTheReturnItemsBeingExchanged', 'areAllOfTheSaleItemsBeingReturned']
        );

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(true));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkCartWideDiscountOnOriginalSale($sale);

        $this->assertNull($response);
    }
);

test(
    'checkCartWideDiscountOnOriginalSale method throws an exception when the original sale has used cart wide promotion and try to return a single item.',
    function (): void {
        $saleDiscount = SaleDiscount::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => DiscountableTypes::getDiscountableClass(DiscountableTypes::PROMOTION->value),
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->saleDiscounts = collect([$saleDiscount]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['areAllOfTheReturnItemsBeingExchanged', 'areAllOfTheSaleItemsBeingReturned']
        );

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $mock->checkCartWideDiscountOnOriginalSale($sale);
    }
)->throws(
    HttpException::class,
    'You cannot return items when the cart-wide promotion is applied to the original sale. You can just exchange the items or return all sale items.'
);

test(
    'checkExchangeProductOnOriginalSale method returns null when specified return item is not exchanged.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => false,
        ]);

        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleReturnService->saleReturnMismatches = collect([]);

        $response = $this->saleReturnService->checkExchangeProductOnOriginalSale($saleItem);

        $this->assertNull($response);
    }
);

test(
    'checkExchangeProductOnOriginalSale method returns null when not allow only return.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => false,
        ]);

        $this->checkSaleDetailsService->company->allow_only_return = false;
        $this->saleReturnService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleReturnService->saleReturnMismatches = collect([]);

        $response = $this->saleReturnService->checkExchangeProductOnOriginalSale($saleItem);

        $this->assertNull($response);
    }
);

test(
    'checkExchangeProductOnOriginalSale method returns null when try to exchange sale item.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => true,
        ]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

        $mock->expects($this->once())
            ->method('isProductBeingExchanged')
            ->will($this->returnValue(true));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkExchangeProductOnOriginalSale($saleItem);

        $this->assertNull($response);
    }
);

test(
    'checkExchangeProductOnOriginalSale method throws an exception when try to return exchanged items.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => true,
        ]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

        $mock->expects($this->once())
            ->method('isProductBeingExchanged')
            ->will($this->returnValue(false));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $mock->checkExchangeProductOnOriginalSale($saleItem);
    }
)->throws(HttpException::class, 'You cannot return exchanged items. You can just exchange the items.');

test(
    'checkItemWiseDiscountOnOriginalSale method returns null when tries to return all the items of the sale.',
    function (): void {
        $mock = $this->createPartialMock(SaleReturnService::class, ['areAllOfTheSaleItemsBeingReturned']);

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(true));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), new SaleItem());

        $this->assertNull($response);
    }
);

test(
    'checkItemWiseDiscountOnOriginalSale method returns null when not allow only return.',
    function (): void {
        $mock = $this->createPartialMock(SaleReturnService::class, ['areAllOfTheSaleItemsBeingReturned']);

        $mock->expects($this->never())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(true));

        $this->checkSaleDetailsService->company->allow_only_return = false;
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), new SaleItem());

        $this->assertNull($response);
    }
);

test(
    'checkItemWiseDiscountOnOriginalSale method returns null when tries to exchange item of the sale.',
    function (): void {
        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['areAllOfTheSaleItemsBeingReturned', 'areAllOfTheReturnItemsBeingExchanged']
        );

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(true));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), new SaleItem());

        $this->assertNull($response);
    }
);

test(
    'checkItemWiseDiscountOnOriginalSale method returns null when there is no discount applied on the original sale.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => true,
        ]);

        $saleItem->saleItemDiscounts = collect([]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['areAllOfTheSaleItemsBeingReturned', 'areAllOfTheReturnItemsBeingExchanged']
        );

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), $saleItem);

        $this->assertNull($response);
    }
);

test(
    'checkItemWiseDiscountOnOriginalSale method returns null when item wise promotion did not apply in return item',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => true,
        ]);

        $saleItemDiscount = SaleItemDiscount::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => EnumsDiscountableTypes::getDiscountableClass(
                EnumsDiscountableTypes::DREAM_PRICE->value
            ),
        ]);

        $saleItem->saleItemDiscounts = collect([$saleItemDiscount]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['areAllOfTheSaleItemsBeingReturned', 'areAllOfTheReturnItemsBeingExchanged']
        );

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), $saleItem);

        $this->assertNull($response);
    }
);

test(
    'checkItemWiseDiscountOnOriginalSale method returns null when sale item has limited to products promotion and try to return an item.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => true,
        ]);

        $saleItemDiscount = SaleItemDiscount::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => EnumsDiscountableTypes::getDiscountableClass(
                EnumsDiscountableTypes::PROMOTION->value
            ),
        ]);

        $saleItemDiscount->discountable = Promotion::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
        ]);

        $saleItem->saleItemDiscounts = collect([$saleItemDiscount]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['areAllOfTheSaleItemsBeingReturned', 'areAllOfTheReturnItemsBeingExchanged']
        );

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), $saleItem);

        $this->assertNull($response);
    }
);

test(
    'checkItemWiseDiscountOnOriginalSale method returns null when sale item has limited to categories promotion and try to return an item',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => true,
        ]);

        $saleItemDiscount = SaleItemDiscount::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => EnumsDiscountableTypes::getDiscountableClass(
                EnumsDiscountableTypes::PROMOTION->value
            ),
        ]);

        $saleItemDiscount->discountable = Promotion::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value,
        ]);

        $saleItem->saleItemDiscounts = collect([$saleItemDiscount]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['areAllOfTheSaleItemsBeingReturned', 'areAllOfTheReturnItemsBeingExchanged']
        );

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), $saleItem);

        $this->assertNull($response);
    }
);

test(
    'checkItemWiseDiscountOnOriginalSale method throws an exception when the sale has applied gift with purchase promotion and try to return a single item.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => true,
        ]);

        $saleItemDiscount = SaleItemDiscount::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => EnumsDiscountableTypes::getDiscountableClass(
                EnumsDiscountableTypes::PROMOTION->value
            ),
        ]);

        $saleItemDiscount->discountable = Promotion::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value,
        ]);

        $saleItem->saleItemDiscounts = collect([$saleItemDiscount]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['areAllOfTheSaleItemsBeingReturned', 'areAllOfTheReturnItemsBeingExchanged']
        );

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), $saleItem);
    }
)->throws(
    HttpException::class,
    'You cannot return items when the Gift With Purchase promotion is applied to the original sale. You can just exchange the items or return all sale items.'
);

test(
    'checkItemWiseDiscountOnOriginalSale method returns null when tries to exchange item.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => true,
        ]);

        $saleItemDiscount = SaleItemDiscount::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => EnumsDiscountableTypes::getDiscountableClass(
                EnumsDiscountableTypes::PROMOTION->value
            ),
        ]);

        $saleItemDiscount->discountable = Promotion::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
        ]);

        $saleItem->saleItemDiscounts = collect([$saleItemDiscount]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['areAllOfTheSaleItemsBeingReturned', 'areAllOfTheReturnItemsBeingExchanged', 'isProductBeingExchanged']
        );

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('isProductBeingExchanged')
            ->will($this->returnValue(true));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), $saleItem);

        $this->assertNull($response);
    }
);

test(
    'checkItemWiseDiscountOnOriginalSale method returns null when tries to return all the items that are connected with the promotion.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => true,
        ]);

        $saleItemDiscount = SaleItemDiscount::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => EnumsDiscountableTypes::getDiscountableClass(
                EnumsDiscountableTypes::PROMOTION->value
            ),
        ]);

        $saleItemDiscount->discountable = Promotion::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
        ]);

        $saleItem->saleItemDiscounts = collect([$saleItemDiscount]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            [
                'areAllOfTheSaleItemsBeingReturned',
                'areAllOfTheReturnItemsBeingExchanged',
                'isProductBeingExchanged',
                'areAllTheGroupItemsBeingReturned',
            ]
        );

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('isProductBeingExchanged')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllTheGroupItemsBeingReturned')
            ->will($this->returnValue(true));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);

        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), $saleItem);

        $this->assertNull($response);
    }
);

test(
    'checkItemWiseDiscountOnOriginalSale method throws an exception when tries to return single item of the discounted sale item.',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_price_paid' => 100,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
            'group_id' => 1,
            'is_exchange' => true,
        ]);

        $saleItemDiscount = SaleItemDiscount::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'discountable_id' => 1,
            'discountable_type' => EnumsDiscountableTypes::getDiscountableClass(
                EnumsDiscountableTypes::PROMOTION->value
            ),
        ]);

        $saleItemDiscount->discountable = Promotion::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
        ]);

        $saleItem->saleItemDiscounts = collect([$saleItemDiscount]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            [
                'areAllOfTheSaleItemsBeingReturned',
                'areAllOfTheReturnItemsBeingExchanged',
                'isProductBeingExchanged',
                'areAllTheGroupItemsBeingReturned',
            ]
        );

        $mock->expects($this->once())
            ->method('areAllOfTheSaleItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('isProductBeingExchanged')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('areAllTheGroupItemsBeingReturned')
            ->will($this->returnValue(false));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);
        $response = $mock->checkItemWiseDiscountOnOriginalSale(new Sale(), $saleItem);

        $this->assertNull($response);
    }
)->throws(
    HttpException::class,
    'You cannot return partial items from the promotion of the original sale. You can either exchange the partial items or return all of the items from this promotion.'
);

test('getExchangeItemsTotal method returns total of exchange items', function (): void {
    $mock = $this->createPartialMock(SaleReturnService::class, ['isProductBeingExchanged']);

    $mock->expects($this->once())
        ->method('isProductBeingExchanged')
        ->will($this->returnValue(true));

    $mock->returnItems = collect($this->saleData->return_items);
    $response = $mock->getExchangeItemsTotal();
    $this->assertEquals($response, 55.00);
});

test('CheckReturnItems Method Terminates for Non-Regular Products.', function (): void {
    $saleReturnReason = [];
    $this->location->sales_return_days_limit = 10;
    $this->checkSaleDetailsService->location = $this->location;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'total_price_paid' => 100,
        'derivative_id' => 1,
        'original_price_per_unit' => 10.00,
        'quantity' => 10,
        'returned_quantity' => 0,
    ]);

    $saleItem->sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'happened_at' => now()->subDays(5),
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItem->sale->cashback = null;
    $saleItem->sale->payments = collect([]);

    $saleItem->product = commonGetProductDetails(false);

    $saleReturnReason[] = SaleReturnReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $saleItem->product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'has_batch' => 0,
        'type_id' => 2,
    ]);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
    });

    $saleReturnReason[] = SaleReturnReason::factory()->make([
        'id' => 2,
        'company_id' => 1,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('loadSaleItems')
            ->once()
            ->andReturn($saleItem->sale);
    });

    $mock = $this->createPartialMock(
        SaleReturnService::class,
        [
            'getReturnReasonIds',
            'checkCartWideDiscountOnOriginalSale',
            'checkCashbackApplied',
            'checkLoyaltyPointsAsPaymentTypeInOriginalSale',
            'checkAllowOnlyReturn',
        ]
    );

    $mock->expects($this->once())
        ->method('getReturnReasonIds')
        ->will($this->returnValue([1, 2]));

    $mock->expects($this->once())
        ->method('checkCartWideDiscountOnOriginalSale');

    $mock->expects($this->once())
        ->method('checkCashbackApplied');

    $mock->expects($this->once())
        ->method('checkLoyaltyPointsAsPaymentTypeInOriginalSale');

    $mock->expects($this->once())
        ->method('checkAllowOnlyReturn');

    $mock->returnedSaleItems = collect([$saleItem]);
    $mock->saleReturnMismatches = collect([]);
    $mock->saleReturnReasons = collect($saleReturnReason);
    $mock->returnItems = collect($this->saleData->return_items);

    $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $mock->checkReturnItems(1, true);
})->throws(HttpException::class, 'Returns are not permitted for non-regular items.');

test('checkReturnItems method throwds an exception when return layaway sale', function (): void {
    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $saleItem->sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
    ]);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
    });

    $this->saleReturnService->returnedSaleItems = collect([$saleItem]);
    $this->saleReturnService->saleReturnMismatches = collect([]);
    $this->saleReturnService->checkReturnItems(2, true);
})->throws(HttpException::class, 'Pending Layaway sale cannot be returned.');

test('checkReturnItems method throws an exception when return void sale', function (): void {
    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $saleItem->sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'status' => SaleStatus::VOID_SALE->value,
    ]);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
    });

    $this->saleReturnService->returnedSaleItems = collect([$saleItem]);
    $this->saleReturnService->saleReturnMismatches = collect([]);
    $this->saleReturnService->checkReturnItems(2, true);
})->throws(HttpException::class, 'Void sale cannot be returned.');

test('checkReturnItems method throws an exception when return credit sale', function (): void {
    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $saleItem->sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'layaway_pending_amount' => 100,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
    });

    $this->saleReturnService->returnedSaleItems = collect([$saleItem]);
    $this->saleReturnService->saleReturnMismatches = collect([]);
    $this->saleReturnService->checkReturnItems(1, true);
})->throws(HttpException::class, 'Pending Credit sale cannot be returned');

test(
    'checkCashbackApplied method throws an exception when cashback is applied in sale',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $sale->cashback = SaleCashback::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'cashback_id' => 1,
            'cash_movement_id' => 1,
        ]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['areAllOfTheReturnItemsBeingExchanged']);

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);
        $mock->checkCashbackApplied($sale);
    }
)->throws(
    HttpException::class,
    'You cannot return items because cashback was applied in the respective sale. You can just exchange the items.'
);

test(
    'checkCashbackApplied method return null when allow_only_return is false',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $sale->cashback = SaleCashback::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'cashback_id' => 1,
            'cash_movement_id' => 1,
        ]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['areAllOfTheReturnItemsBeingExchanged']);

        $mock->expects($this->never())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $this->checkSaleDetailsService->company->allow_only_return = false;

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);
        $response = $mock->checkCashbackApplied($sale);
        $this->assertNull($response);
    }
);

test(
    'checkCashbackApplied method return null when cash back not apply',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $sale->cashback = null;

        $mock = $this->createPartialMock(SaleReturnService::class, ['areAllOfTheReturnItemsBeingExchanged']);

        $mock->expects($this->never())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(false));

        $this->checkSaleDetailsService->company->allow_only_return = false;

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);
        $response = $mock->checkCashbackApplied($sale);
        $this->assertNull($response);
    }
);

test(
    'checkCashbackApplied method return null when areAllOfTheReturnItemsBeingExchanged return true',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => now()->subDays(5),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $sale->cashback = SaleCashback::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'cashback_id' => 1,
            'cash_movement_id' => 1,
        ]);

        $mock = $this->createPartialMock(SaleReturnService::class, ['areAllOfTheReturnItemsBeingExchanged']);

        $mock->expects($this->once())
            ->method('areAllOfTheReturnItemsBeingExchanged')
            ->will($this->returnValue(true));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->saleReturnMismatches = collect([]);
        $response = $mock->checkCashbackApplied($sale);
        $this->assertNull($response);
    }
);

test(
    'checkLoyaltyPointsAsPaymentTypeInOriginalSale method throws an exception when Loyalty Point is used in original sale',
    function (): void {
        $payments = collect([
            SalePayment::factory()->make([
                'id' => 1,
                'sale_id' => 1,
                'payment_type_id' => 4,
                'counter_update_id' => 1,
            ]),
        ]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['hasLoyaltyPointsAsPaymentTypeInOriginalSale']
        );

        $mock->expects($this->once())
            ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale')
            ->will($this->returnValue(true));

        $mock->saleReturnMismatches = collect([]);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkLoyaltyPointsAsPaymentTypeInOriginalSale($payments);
    }
)->throws(
    HttpException::class,
    'You cannot return items of the sale that used loyalty points as payment. You can just exchange the items.'
);

test(
    'checkLoyaltyPointsAsPaymentTypeInOriginalSale method return null when allow_only_return is false',
    function (): void {
        $payments = collect([
            SalePayment::factory()->make([
                'id' => 1,
                'sale_id' => 1,
                'payment_type_id' => 4,
                'counter_update_id' => 1,
            ]),
        ]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['hasLoyaltyPointsAsPaymentTypeInOriginalSale']
        );

        $mock->expects($this->never())
            ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale')
            ->will($this->returnValue(true));

        $this->checkSaleDetailsService->company->allow_only_return = false;
        $mock->saleReturnMismatches = collect([]);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $response = $mock->checkLoyaltyPointsAsPaymentTypeInOriginalSale($payments);
        $this->assertNull($response);
    }
);

test(
    'checkLoyaltyPointsAsPaymentTypeInOriginalSale method return null when hasLoyaltyPointsAsPaymentTypeInOriginalSale return false',
    function (): void {
        $payments = collect([
            SalePayment::factory()->make([
                'id' => 1,
                'sale_id' => 1,
                'payment_type_id' => 4,
                'counter_update_id' => 1,
            ]),
        ]);

        $mock = $this->createPartialMock(
            SaleReturnService::class,
            ['hasLoyaltyPointsAsPaymentTypeInOriginalSale']
        );

        $mock->expects($this->once())
            ->method('hasLoyaltyPointsAsPaymentTypeInOriginalSale')
            ->will($this->returnValue(false));

        $mock->saleReturnMismatches = collect([]);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkLoyaltyPointsAsPaymentTypeInOriginalSale($payments);
    }
);

test(
    'checkAllowOnlyReturn method throws an exception when not purchase with return',
    function (): void {
        $this->saleReturnService->saleReturnMismatches = collect([]);
        $checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->shouldReceive('hasCartItems')
                ->once()
                ->andReturn(false);
            $this->company->allow_only_return = false;
            $mock->company = $this->company;
        });

        $this->saleReturnService->checkSaleDetailsService = $checkSaleDetailsService;

        $this->saleReturnService->checkAllowOnlyReturn();
    }
)->throws(
    HttpException::class,
    'You cannot return items. You can just exchange the items or return items with purchase new items.'
);

test(
    'checkAllowOnlyReturn method return null when allow return only',
    function (): void {
        $this->saleReturnService->saleReturnMismatches = collect([]);
        $checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->shouldReceive('hasCartItems')
                ->never()
                ->andReturn(false);
            $this->company->allow_only_return = true;
            $mock->company = $this->company;
        });

        $this->saleReturnService->checkSaleDetailsService = $checkSaleDetailsService;

        $response = $this->saleReturnService->checkAllowOnlyReturn();
        $this->assertNull($response);
    }
);

test(
    'checkAllowOnlyReturn method return null when return with purchase',
    function (): void {
        $this->saleReturnService->saleReturnMismatches = collect([]);
        $checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->shouldReceive('hasCartItems')
                ->once()
                ->andReturn(true);
            $this->company->allow_only_return = false;
            $mock->company = $this->company;
        });

        $this->saleReturnService->checkSaleDetailsService = $checkSaleDetailsService;

        $response = $this->saleReturnService->checkAllowOnlyReturn();
        $this->assertNull($response);
    }
);
