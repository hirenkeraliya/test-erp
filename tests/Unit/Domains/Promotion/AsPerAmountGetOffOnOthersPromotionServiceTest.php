<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Services\AsPerAmountGetOffOnOthersPromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->asPerAmountGetOffOnOthersPromotionService = new AsPerAmountGetOffOnOthersPromotionService();

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

        $this->asPerAmountGetOffOnOthersPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'group id is required for As per amount get off on others promotions but not passed.');

test(
    'checkForApplicability method sets the saleMismatches when the group id is null',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->saleDetails['items'][0]['group_id'] = null;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $this->asPerAmountGetOffOnOthersPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'group id is required for As per amount get off on others promotions but not passed.');

test(
    'checkForApplicability method sets the saleMismatches when Some of the specified discounted products do not match with our records for the As per amount get off on others promotion',
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

        $this->promotion->getProducts = collect([]);
        $this->promotion->buyProducts = collect([]);

        $saleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('buyItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                ->times(0)
                ->andReturn(100);
        });

        $mock = $this->createPartialMock(AsPerAmountGetOffOnOthersPromotionService::class, ['getDiscountAmount']);

        $mock->expects($this->never())
            ->method('getDiscountAmount')
            ->will($this->returnValue(2));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            $this->product,
            0.00,
            $saleDiscountService,
        );
    }
)->throws(
    HttpException::class,
    'Some of the specified discounted products do not match with our records for the As per amount get off on others promotion.'
);

test(
    'checkForApplicability method sets the saleMismatches when Some of the buy products are not matched with our records for the As per amount get off on others promotion',
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

        $saleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('buyItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                ->times(0)
                ->andReturn(100);
        });

        $mock = $this->createPartialMock(AsPerAmountGetOffOnOthersPromotionService::class, ['getDiscountAmount']);

        $mock->expects($this->never())
            ->method('getDiscountAmount')
            ->will($this->returnValue(2));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            $this->product,
            0.00,
            $saleDiscountService,
        );
    }
)->throws(
    HttpException::class,
    'Some of the specified buy products do not match with our records for the As per amount get off on others promotion.'
);

test(
    'checkForApplicability method sets the saleMismatches when get discount quantities is more then one',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleDetails['items'][1]['group_id'] = 1;
        $this->saleDetails['items'][1]['promotion_id'] = 1;
        $this->saleDetails['items'][1]['id'] = 1;
        $this->saleDetails['items'][1]['item_discount_amount'] = 0;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([$this->product]);
        $this->promotion->buyProducts = collect([$this->product]);

        $saleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('buyItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                ->times(0)
                ->andReturn(100);
        });

        $mock = $this->createPartialMock(AsPerAmountGetOffOnOthersPromotionService::class, ['getDiscountAmount']);

        $mock->expects($this->never())
            ->method('getDiscountAmount')
            ->will($this->returnValue(2));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            $this->product,
            0.00,
            $saleDiscountService,
        );
    }
)->throws(
    HttpException::class,
    'Only 1 unit can be eligible for a discount for this As per amount get off on others promotion. However, the requested quantity is 10.'
);

test(
    'checkForApplicability method sets the saleMismatches when Requested discount amount and get discount amount not match',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['quantity'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleDetails['items'][1]['group_id'] = 1;
        $this->saleDetails['items'][1]['id'] = 1;
        $this->saleDetails['items'][1]['promotion_id'] = 1;
        $this->saleDetails['items'][1]['item_discount_amount'] = 0;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([$this->product]);
        $this->promotion->buyProducts = collect([$this->product]);

        $saleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('buyItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                ->times(1)
                ->andReturn(100);
        });

        $mock = $this->createPartialMock(AsPerAmountGetOffOnOthersPromotionService::class, ['getDiscountAmount']);

        $mock->expects($this->once())
            ->method('getDiscountAmount')
            ->will($this->returnValue(2));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            $this->product,
            0.00,
            $saleDiscountService,
        );
    }
)->throws(
    HttpException::class,
    'Specified discount amount does not match with our calculations. The actual discount amount is 2. and requested discount amount is 1.'
);

test('getPromotionTierValue method returns response as expected', function (float $itemPrice, float $discount): void {
    $this->promotion->promotionTiers = collect([
        new PromotionTier([
            'buy_value' => 10,
            'max_value' => 20,
            'get_value' => 5,
        ]),
        new PromotionTier([
            'buy_value' => 20.01,
            'max_value' => 30,
            'get_value' => 10,
        ]),
        new PromotionTier([
            'buy_value' => 30.01,
            'max_value' => 40,
            'get_value' => 15,
        ]),
        new PromotionTier([
            'buy_value' => 40.01,
            'max_value' => 50,
            'get_value' => 20,
        ]),
    ]);

    $response = $this->asPerAmountGetOffOnOthersPromotionService->getPromotionTierValue($itemPrice, $this->promotion);

    $this->assertEquals($discount, $response);
})->with([[9.99, 0.00], [15, 5], [20, 5], [20.1, 10], [30.01, 15], [45.00, 20]]);

test(
    'getDiscountAmount method calls same class method and returns response as expected when discount type is flat',
    function (float $itemTotal, float $discount): void {
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $mock = $this->createPartialMock(
            AsPerAmountGetOffOnOthersPromotionService::class,
            ['getPercentageDiscountAmount', 'getFlatDiscountAmount']
        );

        $mock->expects($this->any())
            ->method('getPercentageDiscountAmount')
            ->will($this->returnValue($discount));

        $mock->expects($this->once())
            ->method('getFlatDiscountAmount')
            ->will($this->returnValue($discount));

        $this->promotion->discount_type_id = DiscountTypes::FLAT->value;

        $response = $mock->getDiscountAmount(100, $itemTotal, $this->promotion);

        $getDiscount = CommonFunctions::numberFormat($discount);

        $this->assertEquals($getDiscount, $response);
    }
)->with([[40, 10.20], [25, 2.33], [33.33, 3.33], [11.23, 5], [20, 10], [5.20, 0], [500.20, 20]]);

test(
    'getDiscountAmount method calls same class method and returns response as expected when discount type is percentage',
    function (float $itemTotal, float $discount): void {
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $mock = $this->createPartialMock(
            AsPerAmountGetOffOnOthersPromotionService::class,
            ['getPercentageDiscountAmount', 'getFlatDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getPercentageDiscountAmount')
            ->will($this->returnValue($discount));

        $mock->expects($this->any())
            ->method('getFlatDiscountAmount')
            ->will($this->returnValue($discount));

        $this->promotion->discount_type_id = DiscountTypes::PERCENTAGE->value;

        $response = $mock->getDiscountAmount(100, $itemTotal, $this->promotion);

        $getDiscount = CommonFunctions::numberFormat($discount);

        $this->assertEquals($getDiscount, $response);
    }
)->with([[40, 10.20], [25, 2.33], [33.33, 3.33], [11.23, 5], [20, 10], [5.20, 0], [500.20, 20]]);

test('getFlatDiscountAmount method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(AsPerAmountGetOffOnOthersPromotionService::class, ['getPromotionTierValue']);

    $mock->expects($this->once())
        ->method('getPromotionTierValue')
        ->will($this->returnValue(5.20));

    $response = $mock->getFlatDiscountAmount(10.10, 10.10, $this->promotion);
    $this->assertEquals(5.20, $response);
});

test('getFlatDiscountAmount method return item total when discount amount more then item total', function (): void {
    $mock = $this->createPartialMock(AsPerAmountGetOffOnOthersPromotionService::class, ['getPromotionTierValue']);

    $mock->expects($this->once())
        ->method('getPromotionTierValue')
        ->will($this->returnValue(5.20));

    $response = $mock->getFlatDiscountAmount(10.10, 4.20, $this->promotion);
    $this->assertEquals(4.20, $response);
});

test(
    'getPercentageDiscountAmount method calls same class methods as expected',
    function ($cartSubTotal, $percentage): void {
        $mock = $this->createPartialMock(AsPerAmountGetOffOnOthersPromotionService::class, ['getPromotionTierValue']);

        $mock->expects($this->once())
            ->method('getPromotionTierValue')
            ->will($this->returnValue($percentage));

        $response = $mock->getPercentageDiscountAmount(100, $cartSubTotal, $this->promotion);
        $this->assertEquals(CommonFunctions::numberFormat($percentage * $cartSubTotal / 100), $response);
    }
)->with([[500.30, 10.20], [200.52, 23.95], [698.23, 54.37]]);

test('getItemDiscountAmount method return 0 when not set item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;

    $response = $this->asPerAmountGetOffOnOthersPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return 0 when set null item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = null;

    $response = $this->asPerAmountGetOffOnOthersPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return same pass item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = 10.10;

    $response = $this->asPerAmountGetOffOnOthersPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(10.10, $response);
});
