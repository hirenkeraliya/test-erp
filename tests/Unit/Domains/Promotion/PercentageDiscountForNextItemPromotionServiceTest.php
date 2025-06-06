<?php

declare(strict_types=1);

use App\Domains\Promotion\Services\PercentageDiscountForNextItemPromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->percentageDiscountForNextItemPromotionService = new PercentageDiscountForNextItemPromotionService();

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
        'name' => 'Percentage Discount For Next Item Promotion',
        'promotion_applicable_type_id' => 1,
        'discount_type_id' => 1,
        'cart_wide_promotion_type_id' => 1,
        'timeframe_type_id' => 1,
        'percentage' => 0,
        'flat_amount' => 10.20,
        'allow_registered_member' => false,
        'allow_employee' => false,
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

        $this->percentageDiscountForNextItemPromotionService->checkForApplicability(
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
    'group id is required for Percentage Discount For Next Item promotions but not passed.'
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
            PercentageDiscountForNextItemPromotionService::class,
            ['calculateItemDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

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
    'group id is required for Percentage Discount For Next Item promotions but not passed.'
);

test(
    'checkForApplicability method sets the saleMismatches when the discount_item_sequence not specified',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->regularProducts = collect([]);

        $this->percentageDiscountForNextItemPromotionService->checkForApplicability(
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
    'discount item sequence is required for Percentage Discount For Next Item promotions but not passed.'
);

test(
    'checkForApplicability method sets the saleMismatches when the discount_item_sequence is null',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['discount_item_sequence'] = null;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->regularProducts = collect([]);

        $mock = $this->createPartialMock(
            PercentageDiscountForNextItemPromotionService::class,
            ['calculateItemDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

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
    'discount item sequence is required for Percentage Discount For Next Item promotions but not passed.'
);

test(
    'checkForApplicability method sets the saleMismatches when Some of the discounted products are not matched with our records for Flat Discount For Next Item promotion',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['discount_item_sequence'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->regularProducts = collect([]);

        $mock = $this->createPartialMock(
            PercentageDiscountForNextItemPromotionService::class,
            ['calculateItemDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

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
    'Some of the specified discountable products do not match with our records for the Percentage Discount For Next Item promotion.'
);

test(
    'checkForApplicability method sets the saleMismatches when the actual discount amount and requested discount amount does not match',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['discount_item_sequence'] = 1;
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
            PercentageDiscountForNextItemPromotionService::class,
            ['calculateItemDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

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
    'Specified discount amount does not match with our calculations. The actual discount amount is 10 and requested discount amount is 1.'
);

test('getPromotionTier method returns response as expected', function (): void {
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

    $response = $this->percentageDiscountForNextItemPromotionService->getPromotionTier(30, $this->promotion);

    expect($response->first()->toArray())
        ->toHaveKey('buy_value', 10)
        ->toHaveKey('get_value', 5);
});

test(
    'calculateItemDiscountAmount method calls the same class methods and returns the response as expected',
    function (float $itemTotal, float $discount): void {
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['discount_item_sequence'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $mock = $this->createPartialMock(
            PercentageDiscountForNextItemPromotionService::class,
            ['getPromotionTier']
        );

        $promotionTiers = collect([
            new PromotionTier([
                'buy_value' => 10,
                'get_value' => 5,
            ]),
            new PromotionTier([
                'buy_value' => 30,
                'get_value' => 15,
            ]),
            new PromotionTier([
                'buy_value' => 20,
                'get_value' => 10,
            ]),
        ]);

        $mock->expects($this->once())
            ->method('getPromotionTier')
            ->will($this->returnValue($promotionTiers));

        $response = $mock->calculateItemDiscountAmount(
            $this->promotion,
            $this->saleDetails['items'][0],
            $itemTotal,
            collect($this->saleData->items),
            0.00,
        );

        $this->assertEquals($discount, $response);
    }
)->with([[100, 27.33], [120.35, 32.89]]);

test('getItemDiscountAmount method return 0 when not set item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;

    $response = $this->percentageDiscountForNextItemPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return 0 when set null item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = null;

    $response = $this->percentageDiscountForNextItemPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return same pass item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = 10.10;

    $response = $this->percentageDiscountForNextItemPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(10.10, $response);
});
