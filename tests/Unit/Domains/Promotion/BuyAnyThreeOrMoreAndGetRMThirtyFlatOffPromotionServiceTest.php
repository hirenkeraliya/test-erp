<?php

declare(strict_types=1);

use App\Domains\Promotion\Services\BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService = new BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService();

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
        'name' => 'Cart Wide Automatic Promotion',
        'promotion_applicable_type_id' => 1,
        'discount_type_id' => 1,
        'cart_wide_promotion_type_id' => 1,
        'timeframe_type_id' => 1,
        'percentage' => 0,
        'flat_amount' => 10.20,
        'allow_employee' => false,
        'allow_registered_member' => false,
        'status' => true,
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
                'price' => '10.00',
                'quantity' => '10',
                'promoter_ids' => [1],
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

        $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(
    HttpException::class,
    'group id is required for Buy any 3 or more and get RM30 off promotions but not passed.'
);

test(
    'checkForApplicability method sets the saleMismatches when the group id is null',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = null;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->regularProducts = collect([]);

        $mock = $this->createPartialMock(
            BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService::class,
            ['calculateItemDiscountAmount', 'getTotalDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

        $mock->expects($this->never())
            ->method('getTotalDiscountAmount')
            ->will($this->returnValue(100));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(
    HttpException::class,
    'group id is required for Buy any 3 or more and get RM30 off promotions but not passed'
);

test(
    'checkForApplicability method sets the saleMismatches when Some of the discounted products are not matched with our records for the Buy any 3 or more and get RM30 off promotion',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->regularProducts = collect([]);

        $mock = $this->createPartialMock(
            BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService::class,
            ['calculateItemDiscountAmount', 'getTotalDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

        $mock->expects($this->never())
            ->method('getTotalDiscountAmount')
            ->will($this->returnValue(100));

        $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('groupItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                ->never()
                ->andReturn(50);
        });

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            $mockSaleDiscountService,
        );
    }
)->throws(
    HttpException::class,
    'Some of the specified discountable products do not match with our records for the Buy any 3 or more and get RM30 off promotion.'
);

test(
    'checkForApplicability method sets the saleMismatches when the actual discount amount and requested discount amount does not match',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleDetails['items'][0]['quantity'] = 1;

        $this->saleDetails['items'][1]['group_id'] = 1;
        $this->saleDetails['items'][1]['id'] = 1;
        $this->saleDetails['items'][1]['promotion_id'] = 1;
        $this->saleDetails['items'][1]['item_discount_amount'] = 0;
        $this->saleDetails['items'][1]['quantity'] = 1;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->regularProducts = collect([$this->product]);

        $mock = $this->createPartialMock(
            BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService::class,
            ['calculateItemDiscountAmount', 'getTotalDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

        $mock->expects($this->once())
            ->method('getTotalDiscountAmount')
            ->will($this->returnValue(100));

        $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('groupItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                ->once()
                ->andReturn(50);
        });

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            $mockSaleDiscountService,
        );
    }
)->throws(
    HttpException::class,
    'Specified discount amount does not match with our calculations. The actual discount amount is 10 and requested discount amount is 1.'
);

test('getPromotionTierValue method returns response as expected', function ($cartSubTotal, $getValue): void {
    $this->promotion->promotionTiers = collect([
        new PromotionTier([
            'buy_value' => 10,
            'get_value' => 5,
        ]),
        new PromotionTier([
            'buy_value' => 20,
            'get_value' => 10,
        ]),
        new PromotionTier([
            'buy_value' => 30,
            'get_value' => 15,
        ]),
        new PromotionTier([
            'buy_value' => 40,
            'get_value' => 20,
        ]),
    ]);

    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->getPromotionTierValue(
        $cartSubTotal,
        $this->promotion
    );
    $this->assertEquals($getValue, $response);
})->with([[40, 20], [25, 10], [35.68, 15], [11.23, 5], [20, 10], [5.20, 0], [500.20, 20]]);

test('getTotalDiscountAmount method returns the Discount as expected', function (): void {
    $this->saleDetails['items'][0]['item_discount_amount'] = 1;

    $promotionTier = new PromotionTier([
        'buy_value' => 5,
        'get_value' => 2,
    ]);

    $this->promotion->promotionTiers = collect([$promotionTier]);

    $mock = $this->createPartialMock(
        BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService::class,
        ['getPromotionTierValue']
    );

    $mock->expects($this->once())
        ->method('getPromotionTierValue')
        ->will($this->returnValue(100));

    $response = $mock->getTotalDiscountAmount($this->promotion, 100, 0.00, 10);

    $this->assertEquals(100, $response);
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

    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->calculateItemDiscountAmount(
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

    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->calculateItemDiscountAmount(
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

    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return 0 when set null item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = null;

    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return same pass item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = 10.10;

    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(10.10, $response);
});

test('isDiscountItemSequence method returns boolean as expected', function (): void {
    $cartItem = [];
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 0;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 1;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountItemSequence($cartItem);
    $this->assertTrue($response);
});

test('isDiscountReturn method returns boolean as expected', function (): void {
    $cartItem['id'] = 1;
    $item['id'] = 2;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['id'] = 1;
    $item['discount_item_sequence'] = null;
    $item['id'] = 2;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['id'] = 2;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['discount_item_sequence'] = null;
    $item['id'] = 2;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['discount_item_sequence'] = null;
    $item['id'] = 1;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertTrue($response);

    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 2;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 1;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertTrue($response);
});

test('matchItemByDiscountItemSequence method returns boolean as expected', function (): void {
    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 2;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->matchItemByDiscountItemSequence(
        $cartItem,
        $item
    );
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 1;
    $response = $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService->matchItemByDiscountItemSequence(
        $cartItem,
        $item
    );
    $this->assertTrue($response);
});
