<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Services\LimitedToProductsPromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->saleDiscountService = new SaleDiscountService();

    $this->limitedToProductsPromotionService = new LimitedToProductsPromotionService();

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
        'company_id' => 1,
        'name' => 'Cart Wide Automatic Promotion',
        'promotion_applicable_type_id' => 1,
        'discount_type_id' => 1,
        'cart_wide_promotion_type_id' => 1,
        'timeframe_type_id' => 1,
        'percentage' => 0,
        'flat_amount' => 10.2,
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
    'checkForApplicability method sets the saleMismatches when the Specified product is not assigned this in promotion',
    function (): void {
        $this->checkSaleDetailsService->saleDiscountService = $this->saleDiscountService;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['item_discount_amount'] = 0;

        $this->limitedToProductsPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            $this->product,
            0.00,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'Specified promotion is not applicable on the given product ABC');

test(
    'checkForApplicability method sets the saleMismatches when the discount amount doest not matched with actual discount amount',
    function (): void {
        $itemDiscountPass = 15;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['item_discount_amount'] = $itemDiscountPass;
        $this->promotion->discount_type_id = DiscountTypes::FLAT->value;
        $this->promotion->regularProducts = collect([$this->product]);

        $this->limitedToProductsPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            $this->product,
            100,
            new SaleDiscountService(),
        );
    }
)->throws(
    HttpException::class,
    'Requested discount amount of 15 does not match with our calculations. The calculated discount amount is 100'
);

test(
    'calculateItemDiscountAmount method calls getItemPercentageDiscountAmount method when promotion discount type is percentage',
    function (): void {
        $this->promotion->discount_type_id = DiscountTypes::PERCENTAGE->value;

        $mock = $this->createPartialMock(
            LimitedToProductsPromotionService::class,
            ['getItemPercentageDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getItemPercentageDiscountAmount')
            ->will($this->returnValue(20.20));

        $response = $mock->calculateItemDiscountAmount($this->promotion, $this->saleDetails['items'][0], 100);
        $this->assertEquals('20.20', $response);
    }
);

test(
    'calculateItemDiscountAmount method returns as expected',
    function (): void {
        $this->promotion->discount_type_id = DiscountTypes::FLAT->value;

        $mock = $this->createPartialMock(
            LimitedToProductsPromotionService::class,
            ['getItemPercentageDiscountAmount']
        );

        $response = $mock->calculateItemDiscountAmount($this->promotion, $this->saleDetails['items'][0], 100);
        $this->assertEquals(100, $response);
    }
);

test(
    'calculateItemDiscountAmount method returns the flat amount when item subtotal is less than flat amount',
    function (): void {
        $this->promotion->discount_type_id = DiscountTypes::FLAT->value;

        $mock = $this->createPartialMock(
            LimitedToProductsPromotionService::class,
            ['getItemPercentageDiscountAmount']
        );

        $response = $mock->calculateItemDiscountAmount($this->promotion, $this->saleDetails['items'][0], 500);
        $this->assertEquals(102.0, $response);
    }
);

test(
    'getItemPercentageDiscountAmount method returns the value as expected',
    function ($itemTotal, $percentage): void {
        $this->promotion->percentage = $percentage;

        $response = $this->limitedToProductsPromotionService
            ->getItemPercentageDiscountAmount($itemTotal, $this->promotion);
        $this->assertEquals(CommonFunctions::numberFormat($percentage * $itemTotal / 100), $response);
    }
)->with([[500.30, 10.20], [200.52, 23.95], [698.23, 54.37]]);

test('getItemDiscountAmount method return 0 when not set item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;

    $response = $this->limitedToProductsPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return 0 when set null item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = null;

    $response = $this->limitedToProductsPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return same pass item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = 10.10;

    $response = $this->limitedToProductsPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(10.10, $response);
});
