<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Promotion\Services\BuyTwoGetFiftyPercentageOffOnOthersPromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->buyTwoGetFiftyPercentageOffOnOthersPromotionService = new BuyTwoGetFiftyPercentageOffOnOthersPromotionService();

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

        $this->buyTwoGetFiftyPercentageOffOnOthersPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'group id is required for Buy 2 get 50% off on others promotions but not passed.');

test(
    'checkForApplicability method sets the saleMismatches when the group id is null',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = null;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleDetails['items'][1]['group_id'] = 1;
        $this->saleDetails['items'][1]['id'] = 1;
        $this->saleDetails['items'][1]['promotion_id'] = 1;
        $this->saleDetails['items'][1]['item_discount_amount'] = 0;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([$this->product]);
        $this->promotion->buyProducts = collect([$this->product]);

        $mock = $this->createPartialMock(
            BuyTwoGetFiftyPercentageOffOnOthersPromotionService::class,
            ['getTotalDiscountableQuantities', 'calculateItemDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('getTotalDiscountableQuantities')
            ->will($this->returnValue(2));

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(2));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'group id is required for Buy 2 get 50% off on others promotions but not passed.');

test(
    'checkForApplicability method sets the saleMismatches when Some of the specified discounted products do not match with our records for the Buy 2 get 50% off on others promotion',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([]);
        $this->promotion->buyProducts = collect([]);

        $mock = $this->createPartialMock(
            BuyTwoGetFiftyPercentageOffOnOthersPromotionService::class,
            ['getTotalDiscountableQuantities', 'calculateItemDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('getTotalDiscountableQuantities')
            ->will($this->returnValue(2));

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(2));

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
    'Some of the specified discounted products do not match with our records for the Buy 2 get 50% off on others promotion.'
);

test(
    'checkForApplicability method sets the saleMismatches when Some of the buy products are not matched with our records for the Buy 2 get 50% off on others promotion',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleDetails['items'][1]['group_id'] = 1;
        $this->saleDetails['items'][1]['promotion_id'] = 1;
        $this->saleDetails['items'][1]['item_discount_amount'] = 0;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([$this->product]);
        $this->promotion->buyProducts = collect([]);

        $mock = $this->createPartialMock(
            BuyTwoGetFiftyPercentageOffOnOthersPromotionService::class,
            ['getTotalDiscountableQuantities', 'calculateItemDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('getTotalDiscountableQuantities')
            ->will($this->returnValue(2));

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(2));

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
    'Some of the specified buy products do not match with our records for the Buy 2 get 50% off on others promotion.'
);

test(
    'checkForApplicability method sets the saleMismatches when Requested percentage discount quantities for the Buy 2 get 50% off on others promotion is more than 1',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleDetails['items'][1]['group_id'] = 1;
        $this->saleDetails['items'][1]['id'] = 1;
        $this->saleDetails['items'][1]['promotion_id'] = 1;
        $this->saleDetails['items'][1]['item_discount_amount'] = 0;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([$this->product]);
        $this->promotion->buyProducts = collect([$this->product]);

        $mock = $this->createPartialMock(
            BuyTwoGetFiftyPercentageOffOnOthersPromotionService::class,
            ['getTotalDiscountableQuantities', 'calculateItemDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getTotalDiscountableQuantities')
            ->will($this->returnValue(2));

        $mock->expects($this->never())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(2));

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
    'Only 2 units can be given for free for this Buy 2 get 50% off on others promotion. But requested free quantities are 10.'
);

test(
    'checkForApplicability method sets the saleMismatches when Requested discount amount and get discount amount not match',
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
        $this->promotion->getProducts = collect([$this->product]);
        $this->promotion->buyProducts = collect([$this->product]);

        $mock = $this->createPartialMock(
            BuyTwoGetFiftyPercentageOffOnOthersPromotionService::class,
            ['getTotalDiscountableQuantities', 'calculateItemDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('calculateItemDiscountAmount')
            ->will($this->returnValue(10));

        $mock->expects($this->once())
            ->method('getTotalDiscountableQuantities')
            ->will($this->returnValue(20));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'Requested discount amount of 1 is more than the applicable discount amount of 10.');

test(
    'getTotalDiscountableQuantities method returns response as expected',
    function ($totalBuyQuantity, $getValue): void {
        $this->promotion->promotionTiers = collect([
            new PromotionTier([
                'buy_value' => 2,
                'get_value' => 2,
            ]),
            new PromotionTier([
                'buy_value' => 3,
                'get_value' => 4,
            ]),
        ]);

        $response = $this->buyTwoGetFiftyPercentageOffOnOthersPromotionService->getTotalDiscountableQuantities(
            $totalBuyQuantity,
            $this->promotion
        );
        $this->assertEquals($getValue, $response);
    }
)->with([[5, 2], [8, 3], [3, 1], [2, 1]]);

test('getPromotionTierValue method returns response as expected', function (): void {
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

    $response = $this->buyTwoGetFiftyPercentageOffOnOthersPromotionService->getPromotionTierValue(20, $this->promotion);

    expect($response->toArray())
        ->toHaveKey('buy_value', 20)
        ->toHaveKey('get_value', 10);
});

test(
    'calculateItemDiscountAmount method calls same class method and returns response as expected',
    function (float $itemTotal, float $discountPercentage): void {
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $mock = $this->createPartialMock(
            BuyTwoGetFiftyPercentageOffOnOthersPromotionService::class,
            ['getPromotionTierValue']
        );

        $mock->expects($this->once())
            ->method('getPromotionTierValue')
            ->will($this->returnValue(new PromotionTier([
                'get_value' => $discountPercentage,
            ])));

        $response = $mock->calculateItemDiscountAmount(
            $this->promotion,
            $itemTotal,
            collect($this->saleData->items),
        );

        $getDiscount = CommonFunctions::numberFormat($discountPercentage * $itemTotal / 100);

        $this->assertEquals($getDiscount, $response);
    }
)->with([[40, 10.20], [25, 2.33], [33.33, 3.33], [11.23, 5], [20, 10], [5.20, 0], [500.20, 20]]);

test('getItemDiscountAmount method return 0 when not set item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;

    $response = $this->buyTwoGetFiftyPercentageOffOnOthersPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return 0 when set null item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = null;

    $response = $this->buyTwoGetFiftyPercentageOffOnOthersPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return same pass item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = 10.10;

    $response = $this->buyTwoGetFiftyPercentageOffOnOthersPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(10.10, $response);
});
