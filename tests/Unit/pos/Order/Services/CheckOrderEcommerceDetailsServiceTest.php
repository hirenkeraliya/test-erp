<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\DataObjects\OrderECommerceData;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Domains\Order\Services\GenerateEcommerceLoyaltyPointsService;
use App\Domains\Order\Services\OrderEcommerceDiscountService;
use App\Domains\Order\Services\UseEcommerceLoyaltyPointsService;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Voucher\Services\GenerateVoucherECommerceService;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\StoreManager;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->checkOrderEcommerceDetailsService = new CheckOrderEcommerceDetailsService();
    $this->companyId = 1;
    $this->company = Company::factory()->make([
        'default_country_id' => 1,
    ]);

    $this->orderDetails = [
        'member_id' => null,
        'notes' => 'Notes goes here',
        'order_items' => [
            [
                'id' => 1,
                'upc' => 'abd123',
                'price' => '10.00',
                'total_amount' => '10.00',
                'quantity' => '1',
                'promoter_ids' => [1],
            ],
        ],
        'payment_type_id' => 1,
        'payment_amount' => 10,
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
        'loyalty_points' => [
            [
                'loyalty_campaign_id' => 1,
                'minimum_spend_amount' => 100.10,
                'points' => 100,
                'expired_at' => '2024-01-01',
            ],
        ],
        'cart_loyalty_points' => 10,
        'cart_loyalty_point_amount' => 10,
    ];

    $this->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->checkOrderEcommerceDetailsService->orderECommerceData = $this->orderECommerceData;

    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'unit_of_measure_id' => null,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'online_price' => 10.00,
        'has_batch' => false,
        'status' => 1,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->orderItems = collect($this->orderECommerceData->order_items);
    $this->checkOrderEcommerceDetailsService->orderItems = $this->orderItems;

    $this->checkOrderEcommerceDetailsService->products = collect([$this->product]);
    $this->orderECommerceData->payment_type_id = $this->orderDetails['payment_type_id'];
    $this->orderECommerceData->payment_amount = $this->orderDetails['payment_amount'];
});

test(
    'checkRequestDetails method will the request details',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'membership_id' => 1,
            'status' => true,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '12345',
        ]);

        $paymentTypes = PaymentType::factory()->make([
            'company_id' => 1,
            'is_member_required' => 0,
            'is_available_for_refund' => 0,
            'trigger_card_payment_machine' => 0,
            'trigger_qr_code_payment_machine' => 0,
            'trigger_card_affin_payment_machine' => 0,
            'is_card_payment' => 0,
            'status' => 1,
        ]);

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentTypes): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($paymentTypes);
        });

        $this->checkOrderEcommerceDetailsService->orderMismatches = collect([]);

        $this->checkOrderEcommerceDetailsService->generateVoucherECommerceService = $this->mock(
            GenerateVoucherECommerceService::class,
            function ($mock): void {
                $mock->shouldReceive('checkVouchers')
                    ->once();
            }
        );

        $this->checkOrderEcommerceDetailsService->orderEcommerceDiscountService = $this->mock(
            OrderEcommerceDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('checkVoucherDetails')
                    ->once();
            }
        );

        $this->checkOrderEcommerceDetailsService->generateEcommerceLoyaltyPointsService = $this->mock(
            GenerateEcommerceLoyaltyPointsService::class,
            function ($mock): void {
                $mock->shouldReceive('checkLoyaltyPoints')
                    ->once()
                    ->andReturn(collect([]));
            }
        );

        $this->mock(UseEcommerceLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('checkLoyaltyPointsCartDiscount')
                ->once();
        });

        $storeManager->employee = $employee;

        $this->checkOrderEcommerceDetailsService->orderECommerceData = $this->orderECommerceData;
        $this->checkOrderEcommerceDetailsService->companyId = $this->companyId;

        $response = $this->checkOrderEcommerceDetailsService->checkRequestDetails();
        $this->assertNull($response);
    }
);

test('hasVoucher method returns boolean as expected', function (): void {
    $this->checkOrderEcommerceDetailsService->orderECommerceData = $this->orderECommerceData;
    $this->checkOrderEcommerceDetailsService->orderECommerceData->voucher_number = null;
    $response = $this->checkOrderEcommerceDetailsService->hasVoucher();
    $this->assertFalse($response);

    $this->checkOrderEcommerceDetailsService->orderECommerceData->voucher_number = '12345';
    $response = $this->checkOrderEcommerceDetailsService->hasVoucher();
    $this->assertTrue($response);
});

test('getHappenedAtFormat method returns as expected', function (): void {
    Carbon::setTestNow('2025-01-01 12:00:00');
    $this->checkOrderEcommerceDetailsService->orderECommerceData = $this->orderECommerceData;
    $this->checkOrderEcommerceDetailsService->orderECommerceData->happened_at = null;
    $response = $this->checkOrderEcommerceDetailsService->getHappenedAtFormat();
    $this->assertEquals($response, now());

    $this->checkOrderEcommerceDetailsService->orderECommerceData->happened_at = '2025-01-28 10:10:10';
    $response = $this->checkOrderEcommerceDetailsService->getHappenedAtFormat();
    $this->assertEquals($response->format('Y-m-d H:i:s'), '2025-01-28 10:10:10');
    Carbon::setTestNow();
});

test('hasLoyaltyPointsForCart method returns boolean as expected', function (): void {
    $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_point_amount = null;
    $response = $this->checkOrderEcommerceDetailsService->hasLoyaltyPointsForCart();
    $this->assertFalse($response);

    $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_point_amount = 00.0;
    $response = $this->checkOrderEcommerceDetailsService->hasLoyaltyPointsForCart();
    $this->assertFalse($response);

    $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_point_amount = 10.20;
    $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_points = null;
    $response = $this->checkOrderEcommerceDetailsService->hasLoyaltyPointsForCart();
    $this->assertFalse($response);

    $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_point_amount = 10.20;
    $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_points = 0;
    $response = $this->checkOrderEcommerceDetailsService->hasLoyaltyPointsForCart();
    $this->assertFalse($response);

    $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_point_amount = 10.20;
    $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_points = 10;
    $response = $this->checkOrderEcommerceDetailsService->hasLoyaltyPointsForCart();
    $this->assertTrue($response);
});

test('hasGenerateLoyaltyPoints method returns boolean as expected', function (): void {
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $response = $this->checkOrderEcommerceDetailsService->hasGenerateLoyaltyPoints();
    $this->assertTrue($response);

    unset($this->orderDetails['loyalty_points']);
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $response = $this->checkOrderEcommerceDetailsService->hasGenerateLoyaltyPoints();
    $this->assertFalse($response);
});

test('hasItemPromotion method returns boolean as expected', function (): void {
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $response = $this->checkOrderEcommerceDetailsService->hasItemPromotion($this->orderItems->toArray());
    $this->assertFalse($response);
});
