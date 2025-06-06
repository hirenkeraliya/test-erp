<?php

declare(strict_types=1);

use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Services\BuyTwoAndGetOneQuantityAtRM1PromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->buyTwoAndGetOneQuantityAtRM1PromotionService = new BuyTwoAndGetOneQuantityAtRM1PromotionService();

    $this->saleDiscountService = new SaleDiscountService();

    $this->checkSaleDetailsService = new CheckSaleDetailsService();

    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
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

    $this->promotion = Promotion::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'Bundle Buy',
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => 1,
        'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
        'timeframe_type_id' => 1,
        'percentage' => 0,
        'flat_amount' => 10.20,
        'allow_registered_member' => false,
        'allow_employee' => false,
        'status' => true,
    ]);

    $this->promotionTier = PromotionTier::factory()->make([
        'promotion_id' => $this->promotion->id,
    ]);

    $this->saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'cashback_round_off_amount' => null,
        'items' => [
            [
                'id' => 1,
                'price' => '100',
                'quantity' => '10',
                'promoter_ids' => [1],
                'promotion_id' => 1,
                'item_discount_amount' => 0.0,
            ],
            [
                'id' => 2,
                'price' => '100',
                'quantity' => '10',
                'promoter_ids' => [1],
                'promotion_id' => 1,
                'item_discount_amount' => 0.0,
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
        'is_layaway' => false,
        'cart_promotion_id' => null,
        'sale_round_off_amount' => 0.01,
    ];

    $this->saleData = new SaleData(...$this->saleDetails);

    $this->cartItems = collect($this->saleData->items);
});

test(
    'checkForApplicability method sets the saleMismatches when the group id not specified',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->buyTwoAndGetOneQuantityAtRM1PromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            0.00,
            $this->saleDiscountService,
        );
    }
)->throws(HttpException::class, 'group id is required for Buy 2 and get 1 quantity at RM1 promotion but not passed.');

test(
    'checkForApplicability method sets the saleMismatches when the group id is null',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = null;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);

        $this->promotion->regularProducts = collect([]);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('groupItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                    ->never();
        });

        $mock = $this->createPartialMock(
            BuyTwoAndGetOneQuantityAtRM1PromotionService::class,
            ['getTotalApplicableQuantities', 'getTotalDiscountAmount', 'calculateItemDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('getTotalApplicableQuantities')
            ->will($this->returnValue(10));

        $mock->expects($this->never())
            ->method('getTotalDiscountAmount')
            ->will($this->returnValue(10));

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            0.00,
            $mockSaleDiscountService,
        );
    }
)->throws(HttpException::class, 'group id is required for Buy 2 and get 1 quantity at RM1 promotion but not passed.');

test(
    'checkForApplicability method sets the saleMismatches when Some of the buy products are not matched with our records for the Buy 2 and get 1 quantity at RM1 promotion',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][1]['group_id'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);

        $this->promotion->regularProducts = collect([]);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('groupItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                    ->never();
        });

        $mock = $this->createPartialMock(
            BuyTwoAndGetOneQuantityAtRM1PromotionService::class,
            ['getTotalApplicableQuantities', 'getTotalDiscountAmount', 'calculateItemDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('getTotalApplicableQuantities')
            ->will($this->returnValue(10));

        $mock->expects($this->never())
            ->method('getTotalDiscountAmount')
            ->will($this->returnValue(10));

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            0.00,
            $mockSaleDiscountService,
        );
    }
)->throws(
    HttpException::class,
    'Some of the specified buy products do not match with our records for the Buy 2 and get 1 quantity at RM1 promotion.'
);

test(
    'checkForApplicability method sets the saleMismatches when Some of the discountable products are not matched with our records for the Buy 2 and get 1 quantity at RM1 promotion',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 0;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][1]['group_id'] = 1;
        $this->saleDetails['items'][1]['item_discount_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);

        $this->promotion->regularProducts = collect([$this->product]);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('groupItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                    ->never();
        });

        $mock = $this->createPartialMock(
            BuyTwoAndGetOneQuantityAtRM1PromotionService::class,
            ['getTotalApplicableQuantities', 'getTotalDiscountAmount', 'calculateItemDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('getTotalApplicableQuantities')
            ->will($this->returnValue(10));

        $mock->expects($this->never())
            ->method('getTotalDiscountAmount')
            ->will($this->returnValue(10));

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            0.00,
            $mockSaleDiscountService,
        );
    }
)->throws(
    HttpException::class,
    'Some of the specified Get products do not match with our records for the Buy 2 and get 1 quantity at RM1 promotion.'
);

test(
    'checkForApplicability method sets the saleMismatches when Specified promotion requested quantities are more than discountable quantities',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);

        $this->promotion->regularProducts = collect([$this->product]);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('groupItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                    ->never();
        });

        $mock = $this->createPartialMock(
            BuyTwoAndGetOneQuantityAtRM1PromotionService::class,
            ['getTotalApplicableQuantities', 'getTotalDiscountAmount', 'calculateItemDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getTotalApplicableQuantities')
            ->will($this->returnValue(5));

        $mock->expects($this->never())
            ->method('getTotalDiscountAmount')
            ->will($this->returnValue(10));

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            0.00,
            $mockSaleDiscountService,
        );
    }
)->throws(
    HttpException::class,
    'Only 5 units are eligible for the Buy 2 and get 1 quantity at RM1 promotion. But requested units are 10.'
);

test(
    'checkForApplicability method sets the saleMismatches when Specified discount amount not match',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 15;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);

        $this->promotion->regularProducts = collect([$this->product]);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('groupItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                    ->once();
        });

        $mock = $this->createPartialMock(
            BuyTwoAndGetOneQuantityAtRM1PromotionService::class,
            ['getTotalApplicableQuantities', 'getTotalDiscountAmount', 'calculateItemDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getTotalApplicableQuantities')
            ->will($this->returnValue(10));

        $mock->expects($this->once())
            ->method('getTotalDiscountAmount')
            ->will($this->returnValue(10));

        $mock->expects($this->once())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            0.00,
            $mockSaleDiscountService,
        );
    }
)->throws(HttpException::class, 'Requested discount amount of 15 is more than the applicable discount amount of 10.');

test(
    'getPromotionTierValue method returns promotion tier as expected.',
    function (): void {
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->regularProducts = collect([$this->product]);
        $this->promotionTier->buy_value = 10;
        $this->promotionTier->get_value = 900;
        $this->promotion->promotionTiers = collect([
            '0' => $this->promotionTier,
        ]);

        $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->getPromotionTierValue(
            (float) $this->saleDetails['items'][0]['quantity'],
            $this->promotion
        );

        expect($response)->toBeInstanceOf(PromotionTier::class);
        $this->assertTrue($response === $this->promotionTier);
    }
);

test('getTotalApplicableQuantities method returns the applicable quantities as expected', function (): void {
    $this->saleDetails['items'][0]['item_discount_amount'] = 1;

    $promotionTier = new PromotionTier([
        'buy_value' => 5,
        'get_value' => 2,
        'get_quantity' => 2,
    ]);

    $this->promotion->promotionTiers = collect([$promotionTier]);

    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->getTotalApplicableQuantities(11, $this->promotion);

    $this->assertEquals(4, $response);
});

test('getTotalDiscountAmount method returns the Discount as expected', function (): void {
    $this->saleDetails['items'][0]['item_discount_amount'] = 1;

    $promotionTier = new PromotionTier([
        'buy_value' => 5,
        'get_value' => 2,
    ]);

    $this->promotion->promotionTiers = collect([$promotionTier]);

    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->getTotalDiscountAmount(
        $this->promotion,
        100,
        0.00,
        10
    );
    $this->assertEquals(96, $response);
});

test('calculateItemDiscountAmount method calls same class methods as expected', function (): void {
    $this->saleDetails['items'][0]['item_discount_amount'] = 1;

    $this->saleDetails['items'][1]['id'] = 2;
    $this->saleDetails['items'][1]['price'] = 1;
    $this->saleDetails['items'][1]['quantity'] = 1;
    $this->saleDetails['items'][1]['item_discount_amount'] = 1;

    $promotionTier = new PromotionTier([
        'buy_value' => 5,
        'get_value' => 2,
    ]);

    $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
        $mock->shouldReceive('applyDreamPriceOn')
            ->once()
            ->andReturn(50);
    });

    $this->promotion->promotionTiers = collect([$promotionTier]);

    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->calculateItemDiscountAmount(
        $this->saleDetails['items'][0],
        collect($this->saleDetails['items']),
        20,
        100,
        $mockSaleDiscountService,
    );

    $this->assertEquals(10, $response);
});

test('calculateItemDiscountAmount method return last item discount', function (): void {
    $this->saleDetails['items'][0]['item_discount_amount'] = 1;

    $this->saleDetails['items'][1]['id'] = 2;
    $this->saleDetails['items'][1]['price'] = 1;
    $this->saleDetails['items'][1]['quantity'] = 1;
    $this->saleDetails['items'][1]['item_discount_amount'] = 1;

    $this->saleData = new SaleData(...$this->saleDetails);

    $promotionTier = new PromotionTier([
        'buy_value' => 5,
        'get_value' => 2,
    ]);

    $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
        $mock->shouldReceive('applyDreamPriceOn')
            ->once()
            ->andReturn(50);
    });

    $this->promotion->promotionTiers = collect([$promotionTier]);

    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->calculateItemDiscountAmount(
        $this->saleDetails['items'][1],
        collect($this->saleData->items),
        80,
        100,
        $mockSaleDiscountService,
    );

    $this->assertEquals(40, $response);
});

test('getItemDiscountAmount method return 0 when not set item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;

    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return 0 when set null item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = null;

    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return same pass item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = 10.10;

    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(10.10, $response);
});

test('isDiscountItemSequence method returns boolean as expected', function (): void {
    $cartItem = [];
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 0;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 1;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountItemSequence($cartItem);
    $this->assertTrue($response);
});

test('isDiscountReturn method returns boolean as expected', function (): void {
    $cartItem['id'] = 1;
    $item['id'] = 2;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['id'] = 1;
    $item['discount_item_sequence'] = null;
    $item['id'] = 2;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['id'] = 2;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['discount_item_sequence'] = null;
    $item['id'] = 2;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['discount_item_sequence'] = null;
    $item['id'] = 1;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountReturn($cartItem, $item);
    $this->assertTrue($response);

    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 2;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 1;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->isDiscountReturn($cartItem, $item);
    $this->assertTrue($response);
});

test('matchItemByDiscountItemSequence method returns boolean as expected', function (): void {
    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 2;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->matchItemByDiscountItemSequence($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 1;
    $response = $this->buyTwoAndGetOneQuantityAtRM1PromotionService->matchItemByDiscountItemSequence($cartItem, $item);
    $this->assertTrue($response);
});
