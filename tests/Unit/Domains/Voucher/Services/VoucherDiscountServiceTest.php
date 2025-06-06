<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleUserService;
use App\Domains\Voucher\Services\VoucherDiscountService;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Models\Cashier;
use App\Models\Category;
use App\Models\Member;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->saleDetails = [
        'offline_sale_id' => '1',
        'voucher_discount_amount' => 100,
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

    $this->cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $this->saleData = new SaleData(...$this->saleDetails);

    $this->companyId = 1;

    $this->checkSaleDetailsService = new CheckSaleDetailsService();

    $this->saleUserService = new SaleUserService();

    $this->saleUserService->setDetails($this->checkSaleDetailsService, $this->cashier);

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->voucherDiscountService = new VoucherDiscountService();

    $this->voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => 1,
        'generated_by_sale_id' => 1,
        'discount_type' => DiscountTypes::FLAT->value,
        'flat_amount' => 10,
        'percentage' => 10,
        'minimum_spend_amount' => 10,
        'expiry_date' => '2022-01-10',
        'used_at' => null,
    ]);
});

test(
    'getCalculateDiscountAmount method returns cart total when Discount Type is flat and discount amount more than cart total',
    function (): void {
        $response = $this->voucherDiscountService->getCalculateDiscountAmount(9.00, $this->voucher);
        $this->assertTrue(9.00 === $response);
    }
);

test('getCalculateDiscountAmount method returns voucher discount when Discount Types is flat', function (): void {
    $response = $this->voucherDiscountService->getCalculateDiscountAmount(100.00, $this->voucher);
    $this->assertTrue(10.00 === $response);
});

test('getCalculateDiscountAmount method returns voucher discount when Discount Types is percentage', function (): void {
    $this->voucher->discount_type = DiscountTypes::PERCENTAGE->value;
    $response = $this->voucherDiscountService->getCalculateDiscountAmount(100.00, $this->voucher);
    $this->assertTrue(10.00 === $response);
});

test('checkForApplicability method throw exception when voucher not available', function (): void {
    $this->voucher->member_id = 2;
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

    $this->voucherDiscountService->checkForApplicability($this->checkSaleDetailsService, null, 100.00);
})->throws(HttpException::class, 'Specified voucher is not available in our records.');

test('checkForApplicability method sets saleMismatches when member id not match', function (): void {
    $this->voucher->member_id = 2;
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

    $this->voucherDiscountService->checkForApplicability($this->checkSaleDetailsService, $this->voucher, 100.00);
})->throws(HttpException::class, 'The specified voucher belongs to another member.');

test(
    'checkForApplicability method sets saleMismatches when member with given details not match with voucher',
    function (): void {
        $this->saleDetails['member_id'] = null;
        $this->saleDetails['member']['first_name'] = 'ABC';
        $this->saleDetails['member']['mobile_number'] = '999999999';
        $this->saleDetails['member']['card_number'] = '999999999';
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $member = Member::factory()->make([
            'id' => 99,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getMemberByCardNumber')
            ->once()
            ->andReturn($member);
        });

        $this->voucherDiscountService->checkForApplicability($this->checkSaleDetailsService, $this->voucher, 100.00);
    }
)->throws(HttpException::class, 'The specified voucher belongs to another member.');

test(
    'checkForApplicability method sets saleMismatches when the specified voucher has already been used',
    function (): void {
        $this->voucher->used_at = now()->format('Y-m-d H:i:s');
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $this->voucher->voucherConfiguration = VoucherConfiguration::factory()->make([
            'company_id' => 1,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
        ]);

        $this->voucherDiscountService->checkForApplicability($this->checkSaleDetailsService, $this->voucher, 100.00);
    }
)->throws(HttpException::class, 'The specified voucher has already been used.');

test('checkForApplicability method sets saleMismatches when the specified voucher has expired', function (): void {
    $this->voucher->expiry_date = '2022-01-02';
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
    $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

    $this->voucher->voucherConfiguration = VoucherConfiguration::factory()->make([
        'company_id' => 1,
        'exclude_by_type' => ExcludeByTypes::NONE->value,
    ]);

    $this->voucherDiscountService->checkForApplicability($this->checkSaleDetailsService, $this->voucher, 100.00);
})->throws(HttpException::class, 'The specified voucher has expired.');

test(
    'checkForApplicability method sets saleMismatches when minimum spend amount less then cart total',
    function (): void {
        $this->voucher->minimum_spend_amount = 500;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $this->voucher->voucherConfiguration = VoucherConfiguration::factory()->make([
            'company_id' => 1,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
        ]);

        $this->voucherDiscountService->checkForApplicability($this->checkSaleDetailsService, $this->voucher, 100.00);
    }
)->throws(
    HttpException::class,
    'The specified voucher is not applicable because the member needs to spend a minimum amount 500 but cart total is 100.'
);

test(
    'checkForApplicability method sets saleMismatches when Specified discount amount does not match with our calculations',
    function (): void {
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $this->voucher->voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
        ]);

        $mock = $this->createPartialMock(VoucherDiscountService::class, ['getCalculateDiscountAmount']);

        $mock->expects($this->once())
            ->method('getCalculateDiscountAmount')
            ->will($this->returnValue(10.10));

        $mock->checkForApplicability($this->checkSaleDetailsService, $this->voucher, 100.00);
    }
)->throws(
    HttpException::class,
    'The specified voucher discount amount does not match our calculations. The actual discount amount is 10.1 and requested discount amount is 100.'
);

test('checkForApplicability method works as expected', function (): void {
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
    $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

    $this->voucher->voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'exclude_by_type' => ExcludeByTypes::NONE->value,
    ]);

    $mock = $this->createPartialMock(VoucherDiscountService::class, ['getCalculateDiscountAmount']);

    $mock->expects($this->once())
        ->method('getCalculateDiscountAmount')
        ->will($this->returnValue(100.00));

    $response = $mock->checkForApplicability($this->checkSaleDetailsService, $this->voucher, 100.00);

    $this->assertNull($response);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test(
    'getExcludeAmountForVoucher method returns total excludes amount for excluding by categories expected',
    function (): void {
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);

        $category = Category::factory()->make([
            'company_id' => 1,
        ]);

        $product->categories = collect([
            '0' => $category,
        ]);

        $this->checkSaleDetailsService->products = collect([
            '0' => $product,
        ]);

        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'exclude_by_type' => ExcludeByTypes::CATEGORIES->value,
        ]);

        $voucherConfiguration->categories = collect([
            '0' => $category,
        ]);

        $this->voucher->voucherConfiguration = $voucherConfiguration;

        $response = $this->voucherDiscountService->getExcludeAmountForVoucher(
            $this->checkSaleDetailsService,
            $this->voucher
        );
        $this->assertTrue(100.00 === $response);
    }
);

test(
    'getExcludeAmountForVoucher method returns the total excludes amount for excluding by-products expected',
    function (): void {
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);

        $this->checkSaleDetailsService->products = collect([
            '0' => $product,
        ]);

        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
        ]);

        $voucherConfiguration->products = collect([
            '0' => $product,
        ]);

        $this->voucher->voucherConfiguration = $voucherConfiguration;

        $response = $this->voucherDiscountService->getExcludeAmountForVoucher(
            $this->checkSaleDetailsService,
            $this->voucher
        );
        $this->assertTrue(100.00 === $response);
    }
);

test(
    'checkVoucherRestrictions method calls same class methods as expected',
    function (): void {
        $mock = $this->createPartialMock(
            VoucherDiscountService::class,
            ['checkDreamPriceRestrictions', 'checkItemWisePromotionRestrictions', 'checkCartWidePromotionRestrictions']
        );

        $mock->expects($this->once())
            ->method('checkDreamPriceRestrictions');

        $mock->expects($this->once())
            ->method('checkItemWisePromotionRestrictions');

        $mock->expects($this->once())
            ->method('checkCartWidePromotionRestrictions');

        $mock->checkVoucherRestrictions(new CheckSaleDetailsService(), $this->voucher);
    }
);

test(
    'checkDreamPriceRestrictions method returns null dream price applicable is true in promotion',
    function (): void {
        $this->voucher->dream_price_applicable = true;

        $response = $this->voucherDiscountService->checkDreamPriceRestrictions(
            new CheckSaleDetailsService(),
            $this->voucher
        );
        $this->assertNull($response);
    }
);

test(
    'checkDreamPriceRestrictions method returns null when dream price not applicable in voucher and DreamPrice not apply',
    function (): void {
        $this->voucher->dream_price_applicable = false;

        $checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->cartItems = collect($this->saleDetails['items']);

                $mock->shouldReceive('hasDreamPrice')
                    ->once()
                    ->andReturn(false);
            }
        );

        $response = $this->voucherDiscountService->checkDreamPriceRestrictions(
            $checkSaleDetailsService,
            $this->voucher
        );
        $this->assertNull($response);
    }
);

test(
    'checkDreamPriceRestrictions method set mismatches when when dream price not applicable in voucher and DreamPrice apply',
    function (): void {
        $this->voucher->dream_price_applicable = false;

        $checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->saleMismatches = collect([]);

                $mock->cartItems = collect($this->saleDetails['items']);

                $mock->shouldReceive('hasDreamPrice')
                    ->once()
                    ->andReturn(true);
            }
        );

        $this->voucherDiscountService->checkDreamPriceRestrictions($checkSaleDetailsService, $this->voucher);
    }
)->throws(HttpException::class, 'Specified Voucher cannot be applied with the dream price');

test(
    'checkItemWisePromotionRestrictions method returns null Item Wise Promotion is not Applicable in voucher',
    function (): void {
        $this->voucher->item_wise_promotion_applicable = true;

        $response = $this->voucherDiscountService->checkItemWisePromotionRestrictions(
            new CheckSaleDetailsService(),
            $this->voucher
        );
        $this->assertNull($response);
    }
);

test(
    'checkItemWisePromotionRestrictions method returns null when Item Wise Promotion is not Applicable in voucher and Item Wise Promotion not apply',
    function (): void {
        $this->voucher->item_wise_promotion_applicable = false;

        $checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->cartItems = collect($this->saleDetails['items']);

                $mock->shouldReceive('hasItemPromotion')
                    ->once()
                    ->andReturn(false);
            }
        );

        $response = $this->voucherDiscountService->checkItemWisePromotionRestrictions(
            $checkSaleDetailsService,
            $this->voucher
        );
        $this->assertNull($response);
    }
);

test(
    'checkItemWisePromotionRestrictions method set mismatches when Item Wise Promotion is not Applicable in voucher and Item Wise Promotion is apply',
    function (): void {
        $this->voucher->item_wise_promotion_applicable = false;

        $checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->saleMismatches = collect([]);

                $mock->cartItems = collect($this->saleDetails['items']);

                $mock->shouldReceive('hasItemPromotion')
                    ->once()
                    ->andReturn(true);
            }
        );

        $this->voucherDiscountService->checkItemWisePromotionRestrictions($checkSaleDetailsService, $this->voucher);
    }
)->throws(HttpException::class, 'Specified Voucher cannot be applied with the Item Wise Promotion');

test(
    'checkCartWidePromotionRestrictions method returns null cart wide promotion is not Applicable in voucher',
    function (): void {
        $this->voucher->cart_wide_promotion_applicable = true;

        $response = $this->voucherDiscountService->checkCartWidePromotionRestrictions(
            new CheckSaleDetailsService(),
            $this->voucher
        );
        $this->assertNull($response);
    }
);

test(
    'checkCartWidePromotionRestrictions method returns null when cart wide promotion is not Applicable in voucher and cart wide promotion not apply',
    function (): void {
        $this->voucher->cart_wide_promotion_applicable = false;

        $checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->cartItems = collect($this->saleDetails['items']);

                $mock->shouldReceive('hasCartPromotion')
                    ->once()
                    ->andReturn(false);
            }
        );

        $response = $this->voucherDiscountService->checkCartWidePromotionRestrictions(
            $checkSaleDetailsService,
            $this->voucher
        );
        $this->assertNull($response);
    }
);

test(
    'checkCartWidePromotionRestrictions method set mismatches when cart wide promotion is not Applicable in voucher and cart wide promotion is apply',
    function (): void {
        $this->voucher->cart_wide_promotion_applicable = false;

        $checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->saleMismatches = collect([]);

                $mock->cartItems = collect($this->saleDetails['items']);

                $mock->shouldReceive('hasCartPromotion')
                    ->once()
                    ->andReturn(true);
            }
        );

        $this->voucherDiscountService->checkCartWidePromotionRestrictions($checkSaleDetailsService, $this->voucher);
    }
)->throws(HttpException::class, 'Specified Voucher cannot be applied with the Cart Wide Promotion');

test(
    'getDiscountAmount method returns voucher discount amount',
    function (): void {
        $this->saleDetails['voucher_discount_amount'] = 100;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $response = $this->voucherDiscountService->getDiscountAmount($this->checkSaleDetailsService);
        $this->assertTrue(100.00 === $response);
    }
);
