<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Services\AsPerAmountLimitedToBrandsPromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->asPerAmountLimitedToBrandsPromotionService = new AsPerAmountLimitedToBrandsPromotionService();
    $this->checkSaleDetailsService = new CheckSaleDetailsService();
    $this->saleDiscountService = new SaleDiscountService();

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
        'name' => 'As Per Amount Limited To Brands',
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

        $this->asPerAmountLimitedToBrandsPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'group id is required for as per amount limited to brands promotion but not passed.');

test(
    'checkForApplicability method sets the saleMismatches when the group id is null',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = null;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->asPerAmountLimitedToBrandsPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'group id is required for as per amount limited to brands promotion but not passed.');

test(
    'checkForApplicability method sets the saleMismatches when none of the product brand is in the promotion brands',
    function (): void {
        $this->brand = Brand::factory()->make([
            'id' => 1,
        ]);

        $this->product->brands = collect($this->brand);

        $this->promotion->brands = collect(new Brand());

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $saleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('groupItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                ->never()
                ->andReturn(100);
        });

        $saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->checkSaleDetailsService->saleDiscountService = $saleDiscountService;

        $mock = $this->createPartialMock(
            AsPerAmountLimitedToBrandsPromotionService::class,
            ['getPromotionTierValue', 'getDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('getPromotionTierValue')
            ->will($this->returnValue(2));

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
)->throws(HttpException::class, 'Specified promotion is not applicable on the given product brand ABC.');

test(
    'checkForApplicability method sets the saleMismatches when promotion not applicable as per our records',
    function (): void {
        $this->brand = Brand::factory()->make([
            'id' => 1,
        ]);

        $this->product->brands = collect($this->brand);

        $this->promotion->brands = collect(new Brand());

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $saleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('groupItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                ->never()
                ->andReturn(100);
        });

        $saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->checkSaleDetailsService->saleDiscountService = $saleDiscountService;

        $mock = $this->createPartialMock(
            AsPerAmountLimitedToBrandsPromotionService::class,
            ['getPromotionTierValue', 'getDiscountAmount']
        );

        $mock->expects($this->never())
            ->method('getPromotionTierValue')
            ->will($this->returnValue(0));

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
)->throws(HttpException::class, 'Specified promotion is not applicable on the given product brand ABC.');

test(
    'checkForApplicability method sets the saleMismatches when Specified discount amount does not match with our calculations',
    function (): void {
        $this->brand = Brand::factory()->make([
            'id' => 1,
        ]);

        $this->product->brands = collect([$this->brand]);

        $this->promotion->brands = collect([$this->brand]);

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $saleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
            $mock->shouldReceive('groupItemsSubtotalWithApplyDreamPriceAndPriceOverride')
                ->once()
                ->andReturn(100);
        });

        $saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->checkSaleDetailsService->saleDiscountService = $saleDiscountService;

        $mock = $this->createPartialMock(
            AsPerAmountLimitedToBrandsPromotionService::class,
            ['getPromotionTierValue', 'getDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getPromotionTierValue')
            ->will($this->returnValue(100));

        $mock->expects($this->once())
            ->method('getDiscountAmount')
            ->will($this->returnValue(100));

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
    'Specified discount amount does not match with our calculations. The actual discount amount is 100. and requested discount amount is 1.'
);

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

    $response = $this->asPerAmountLimitedToBrandsPromotionService->getPromotionTierValue(20, $this->promotion);

    $this->assertEquals(10, $response);
});

test(
    'getDiscountAmount method calls same class method and returns response as expected when discount type is flat',
    function (float $itemTotal, float $discount): void {
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $mock = $this->createPartialMock(
            AsPerAmountLimitedToBrandsPromotionService::class,
            ['getPercentageDiscountAmount', 'getFlatDiscountAmount']
        );

        $mock->expects($this->any())
            ->method('getPercentageDiscountAmount')
            ->will($this->returnValue($discount));

        $mock->expects($this->once())
            ->method('getFlatDiscountAmount')
            ->will($this->returnValue($discount));

        $this->promotion->discount_type_id = DiscountTypes::FLAT->value;

        $response = $mock->getDiscountAmount(
            $this->saleDetails['items'][0],
            collect($this->saleDetails['items']),
            $itemTotal,
            $itemTotal,
            2,
            $this->promotion,
            new SaleDiscountService()
        );

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
            AsPerAmountLimitedToBrandsPromotionService::class,
            ['getPercentageDiscountAmount', 'getFlatDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getPercentageDiscountAmount')
            ->will($this->returnValue($discount));

        $mock->expects($this->any())
            ->method('getFlatDiscountAmount')
            ->will($this->returnValue($discount));

        $this->promotion->discount_type_id = DiscountTypes::PERCENTAGE->value;

        $response = $mock->getDiscountAmount(
            $this->saleDetails['items'][0],
            collect($this->saleDetails['items']),
            $itemTotal,
            $itemTotal,
            2,
            $this->promotion,
            new SaleDiscountService()
        );

        $getDiscount = CommonFunctions::numberFormat($discount);

        $this->assertEquals($getDiscount, $response);
    }
)->with([[40, 10.20], [25, 2.33], [33.33, 3.33], [11.23, 5], [20, 10], [5.20, 0], [500.20, 20]]);

test('getFlatDiscountAmount method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(
        AsPerAmountLimitedToBrandsPromotionService::class,
        ['getTotalFlatDiscountAmount', 'calculateFlatItemDiscountAmount']
    );

    $mock->expects($this->once())
        ->method('getTotalFlatDiscountAmount')
        ->will($this->returnValue(3.20));

    $mock->expects($this->once())
        ->method('calculateFlatItemDiscountAmount')
        ->will($this->returnValue(3.20));

    $response = $mock->getFlatDiscountAmount(
        $this->saleDetails['items'][0],
        collect($this->saleDetails['items']),
        100.10,
        20.20,
        2,
        $this->promotion,
        new SaleDiscountService()
    );
    $this->assertEquals(3.20, $response);
});

test(
    'getPercentageDiscountAmount method calls same class methods as expected',
    function ($cartSubTotal, $percentage): void {
        $mock = $this->createPartialMock(AsPerAmountLimitedToBrandsPromotionService::class, ['getPromotionTierValue']);

        $mock->expects($this->once())
            ->method('getPromotionTierValue')
            ->will($this->returnValue($percentage));

        $response = $mock->getPercentageDiscountAmount($cartSubTotal, 100.50, $this->promotion);
        $this->assertEquals(CommonFunctions::numberFormat($percentage * $cartSubTotal / 100), $response);
    }
)->with([[500.30, 10.20], [200.52, 23.95], [698.23, 54.37]]);

test('getItemDiscountAmount method return 0 when not set item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;

    $response = $this->asPerAmountLimitedToBrandsPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return 0 when set null item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = null;

    $response = $this->asPerAmountLimitedToBrandsPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return same pass item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = 10.10;

    $response = $this->asPerAmountLimitedToBrandsPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(10.10, $response);
});

test('isDiscountItemSequence method returns boolean as expected', function (): void {
    $cartItem = [];
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 0;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 1;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountItemSequence($cartItem);
    $this->assertTrue($response);
});

test('isDiscountReturn method returns boolean as expected', function (): void {
    $cartItem['id'] = 1;
    $item['id'] = 2;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['id'] = 1;
    $item['discount_item_sequence'] = null;
    $item['id'] = 2;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['id'] = 2;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['discount_item_sequence'] = null;
    $item['id'] = 2;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['discount_item_sequence'] = null;
    $item['id'] = 1;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertTrue($response);

    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 2;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 1;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->isDiscountReturn($cartItem, $item);
    $this->assertTrue($response);
});

test('matchItemByDiscountItemSequence method returns boolean as expected', function (): void {
    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 2;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->matchItemByDiscountItemSequence($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['discount_item_sequence'] = 1;
    $item['discount_item_sequence'] = 1;
    $response = $this->asPerAmountLimitedToBrandsPromotionService->matchItemByDiscountItemSequence($cartItem, $item);
    $this->assertTrue($response);
});

test('getTotalFlatDiscountAmount method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(AsPerAmountLimitedToBrandsPromotionService::class, ['getPromotionTierValue']);

    $mock->expects($this->once())
        ->method('getPromotionTierValue')
        ->will($this->returnValue(3.20));

    $response = $mock->getTotalFlatDiscountAmount(6.5, 50.10, 1.5, $this->promotion);
    $this->assertEquals(3.20, $response);
});

test('calculateFlatItemDiscountAmount method calls same class methods as expected', function (): void {
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

    $response = $this->asPerAmountLimitedToBrandsPromotionService->calculateFlatItemDiscountAmount(
        $this->saleDetails['items'][0],
        collect($this->saleDetails['items']),
        20,
        100,
        $mockSaleDiscountService,
    );

    $this->assertEquals(10, $response);
});

test('calculateFlatItemDiscountAmount method return last item discount', function (): void {
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

    $response = $this->asPerAmountLimitedToBrandsPromotionService->calculateFlatItemDiscountAmount(
        $this->saleDetails['items'][1],
        collect($this->saleData->items),
        80,
        100,
        $mockSaleDiscountService,
    );

    $this->assertEquals(40, $response);
});
