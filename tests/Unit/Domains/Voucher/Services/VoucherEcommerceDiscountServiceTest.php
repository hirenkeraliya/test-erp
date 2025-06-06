<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Order\DataObjects\OrderECommerceData;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Domains\Voucher\Services\VoucherEcommerceDiscountService;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Models\Category;
use App\Models\Member;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->orderDetails = [
        'member_id' => 1,
        'notes' => 'Notes goes here',
        'voucher_number' => '1234',
        'voucher_discount_amount' => 100,
        'order_items' => [
            [
                'id' => 1,
                'upc' => 'abd123',
                'price' => '10.00',
                'total_amount' => '10.00',
                'quantity' => '10',
                'promoter_ids' => [1],
            ],
        ],
        'payment_type_id' => 1,
        'payment_amount' => 100,
        'shipping_address' => [
            'first_name' => 'test',
            'last_name' => 'test',
            'phone' => 'test',
            'address_line_1' => 'test',
            'address_line_2' => 'test',
            'country_code' => 'test',
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'area_code' => 'test',
        ],
        'billing_address' => [
            'first_name' => 'test',
            'last_name' => 'test',
            'phone' => 'test',
            'address_line_1' => 'test',
            'address_line_2' => 'test',
            'country_code' => 'test',
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'area_code' => 'test',
        ],
        'order_round_off_amount' => 0.0,
        'total_tax_amount' => 0.0,
        'member_details' => [],
        'happened_at' => '2022-01-04 04:20:50',
    ];

    $this->orderECommerceData = new OrderECommerceData(...$this->orderDetails);

    $this->companyId = 1;

    $this->checkOrderEcommerceDetailsService = new CheckOrderEcommerceDetailsService();

    $this->checkOrderEcommerceDetailsService->orderMismatches = collect([]);

    $this->voucherEcommerceDiscountService = new VoucherEcommerceDiscountService();

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

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $this->checkOrderEcommerceDetailsService->member = $member;
});

test(
    'getCalculateDiscountAmount method returns cart total when Discount Type is flat and discount amount more than cart total',
    function (): void {
        $response = $this->voucherEcommerceDiscountService->getCalculateDiscountAmount(9.00, $this->voucher);
        $this->assertTrue(9.00 === $response);
    }
);

test('getCalculateDiscountAmount method returns voucher discount when Discount Types is flat', function (): void {
    $response = $this->voucherEcommerceDiscountService->getCalculateDiscountAmount(100.00, $this->voucher);
    $this->assertTrue(10.00 === $response);
});

test('getCalculateDiscountAmount method returns voucher discount when Discount Types is percentage', function (): void {
    $this->voucher->discount_type = DiscountTypes::PERCENTAGE->value;
    $response = $this->voucherEcommerceDiscountService->getCalculateDiscountAmount(100.00, $this->voucher);
    $this->assertTrue(10.00 === $response);
});

test('checkForApplicability method throw exception when voucher not available', function (): void {
    $this->voucher->member_id = 2;
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);

    $this->voucherEcommerceDiscountService->checkForApplicability(
        $this->checkOrderEcommerceDetailsService,
        null,
        100.00
    );
})->throws(HttpException::class, 'Specified voucher is not available in our records.');

test('checkForApplicability method sets orderMismatches when member id not match', function (): void {
    $this->voucher->member_id = 2;
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);

    $this->voucherEcommerceDiscountService->checkForApplicability(
        $this->checkOrderEcommerceDetailsService,
        $this->voucher,
        100.00
    );
})->throws(HttpException::class, 'The specified voucher belongs to another member.');

test(
    'checkForApplicability method sets orderMismatches when member with given details not match with voucher',
    function (): void {
        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $this->checkOrderEcommerceDetailsService->companyId = 1;

        $member = Member::factory()->make([
            'id' => 99,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $this->checkOrderEcommerceDetailsService->member = $member;

        $this->voucherEcommerceDiscountService->checkForApplicability(
            $this->checkOrderEcommerceDetailsService,
            $this->voucher,
            100.00
        );
    }
)->throws(HttpException::class, 'The specified voucher belongs to another member.');

test(
    'checkForApplicability method sets orderMismatches when the specified voucher has already been used',
    function (): void {
        $this->voucher->used_at = now()->format('Y-m-d H:i:s');
        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $this->checkOrderEcommerceDetailsService->orderItems = collect($this->orderDetails['order_items']);

        $this->voucher->voucherConfiguration = VoucherConfiguration::factory()->make([
            'company_id' => 1,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
        ]);

        $this->voucherEcommerceDiscountService->checkForApplicability(
            $this->checkOrderEcommerceDetailsService,
            $this->voucher,
            100.00
        );
    }
)->throws(HttpException::class, 'The specified voucher has already been used.');

test('checkForApplicability method sets orderMismatches when the specified voucher has expired', function (): void {
    $this->voucher->expiry_date = '2022-01-02';
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->checkOrderEcommerceDetailsService->orderItems = collect($this->orderDetails['order_items']);

    $this->voucher->voucherConfiguration = VoucherConfiguration::factory()->make([
        'company_id' => 1,
        'exclude_by_type' => ExcludeByTypes::NONE->value,
    ]);

    $this->voucherEcommerceDiscountService->checkForApplicability(
        $this->checkOrderEcommerceDetailsService,
        $this->voucher,
        100.00
    );
})->throws(HttpException::class, 'The specified voucher has expired.');

test(
    'checkForApplicability method sets orderMismatches when minimum spend amount less then cart total',
    function (): void {
        $this->voucher->minimum_spend_amount = 500;
        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $this->checkOrderEcommerceDetailsService->orderItems = collect($this->orderDetails['order_items']);

        $this->voucher->voucherConfiguration = VoucherConfiguration::factory()->make([
            'company_id' => 1,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
        ]);

        $this->voucherEcommerceDiscountService->checkForApplicability(
            $this->checkOrderEcommerceDetailsService,
            $this->voucher,
            100.00
        );
    }
)->throws(
    HttpException::class,
    'The specified voucher is not applicable because the member needs to spend a minimum amount 500 but cart total is 100.'
);

test(
    'checkForApplicability method sets orderMismatches when Specified discount amount does not match with our calculations',
    function (): void {
        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $this->checkOrderEcommerceDetailsService->orderItems = collect($this->orderDetails['order_items']);

        $this->voucher->voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
        ]);

        $mock = $this->createPartialMock(VoucherEcommerceDiscountService::class, ['getCalculateDiscountAmount']);

        $mock->expects($this->once())
            ->method('getCalculateDiscountAmount')
            ->will($this->returnValue(10.10));

        $mock->checkForApplicability($this->checkOrderEcommerceDetailsService, $this->voucher, 100.00);
    }
)->throws(
    HttpException::class,
    'The specified voucher discount amount does not match our calculations. The actual discount amount is 10.1 and requested discount amount is 100.'
);

test('checkForApplicability method works as expected', function (): void {
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->checkOrderEcommerceDetailsService->orderItems = collect($this->orderDetails['order_items']);

    $this->voucher->voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'exclude_by_type' => ExcludeByTypes::NONE->value,
    ]);

    $mock = $this->createPartialMock(VoucherEcommerceDiscountService::class, ['getCalculateDiscountAmount']);

    $mock->expects($this->once())
        ->method('getCalculateDiscountAmount')
        ->will($this->returnValue(100.00));

    $response = $mock->checkForApplicability($this->checkOrderEcommerceDetailsService, $this->voucher, 100.00);

    $this->assertNull($response);
    $this->assertTrue($this->checkOrderEcommerceDetailsService->orderMismatches->toArray() === []);
});

test(
    'getExcludeAmountForVoucher method returns total excludes amount for excluding by categories expected',
    function (): void {
        $this->checkOrderEcommerceDetailsService->orderItems = collect($this->orderDetails['order_items']);

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

        $this->checkOrderEcommerceDetailsService->products = collect([
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

        $response = $this->voucherEcommerceDiscountService->getExcludeAmountForVoucher(
            $this->checkOrderEcommerceDetailsService,
            $this->voucher
        );
        $this->assertTrue(100.00 === $response);
    }
);

test(
    'getExcludeAmountForVoucher method returns the total excludes amount for excluding by-products expected',
    function (): void {
        $this->checkOrderEcommerceDetailsService->orderItems = collect($this->orderDetails['order_items']);

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

        $this->checkOrderEcommerceDetailsService->products = collect([
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

        $response = $this->voucherEcommerceDiscountService->getExcludeAmountForVoucher(
            $this->checkOrderEcommerceDetailsService,
            $this->voucher
        );
        $this->assertTrue(100.00 === $response);
    }
);

test(
    'getDiscountAmount method returns voucher discount amount',
    function (): void {
        $this->orderDetails['voucher_discount_amount'] = 100;
        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $response = $this->voucherEcommerceDiscountService->getDiscountAmount($this->checkOrderEcommerceDetailsService);
        $this->assertTrue(100.00 === $response);
    }
);
