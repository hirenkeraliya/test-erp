<?php

declare(strict_types=1);

use App\Domains\Order\DataObjects\OrderECommerceData;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Domains\Order\Services\OrderEcommerceDiscountService;
use App\Domains\Voucher\Services\VoucherEcommerceDiscountService;
use App\Domains\Voucher\VoucherQueries;
use App\Models\Voucher;

beforeEach(function (): void {
    $this->orderEcommerceDiscountService = new OrderEcommerceDiscountService();
    $this->checkOrderEcommerceDetailsService = new CheckOrderEcommerceDetailsService();

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
    ];
});

test('setDetails method works as expected', function (): void {
    $mock = $this->createPartialMock(OrderEcommerceDiscountService::class, ['getVoucher']);
    $voucher = new Voucher();
    $mock->expects($this->once())
        ->method('getVoucher')
        ->will($this->returnValue($voucher));

    $mock->setDetails($this->checkOrderEcommerceDetailsService);
    $this->assertTrue($mock->voucher === $voucher);
});

test(
    'It calls the getByVoucherNumberAndCompanyIdWithProductsAndCategories method of the VoucherQueries class and returns proper response',
    function (): void {
        $this->orderDetails['voucher_number'] = 'ABC123';
        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $this->checkOrderEcommerceDetailsService->companyId = 1;

        $this->orderEcommerceDiscountService->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;

        $voucher = new Voucher();

        $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
            $mock->shouldReceive('getByVoucherNumberAndCompanyIdWithProductsAndCategories')
                ->once()
                ->andReturn($voucher);
        });

        $response = $this->orderEcommerceDiscountService->getVoucher();
        $this->assertTrue($response === $voucher);
    }
);

test(
    'It calls the getVoucher method returns null when voucher_number is not set',
    function (): void {
        $this->orderDetails['voucher_number'] = null;
        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $this->checkOrderEcommerceDetailsService->companyId = 1;

        $this->orderEcommerceDiscountService->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;

        $voucher = new Voucher();

        $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
            $mock->shouldReceive('getByVoucherNumberAndCompanyIdWithProductsAndCategories')
                ->never()
                ->andReturn($voucher);
        });

        $response = $this->orderEcommerceDiscountService->getVoucher();
        $this->assertNull($response);
    }
);

test(
    'It calls the checkForApplicability method of the VoucherEcommerceDiscountService class and returns proper response',
    function (): void {
        $this->orderEcommerceDiscountService->voucher = new Voucher();
        $this->mock(VoucherEcommerceDiscountService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
                ->once();
        });

        $this->orderEcommerceDiscountService->checkOrderEcommerceDetailsService = $this->mock(
            CheckOrderEcommerceDetailsService::class,
            function ($mock): void {
                $mock->shouldReceive('hasVoucher')
                    ->once()
                    ->andReturn(true);
            }
        );

        $response = $this->orderEcommerceDiscountService->checkVoucherDetails(500);
        $this->assertNull($response);
    }
);

test(
    'It calls the checkVoucherDetails method returns null when voucher discount is not set',
    function (): void {
        $this->orderEcommerceDiscountService->voucher = new Voucher();
        $this->mock(VoucherEcommerceDiscountService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
                ->never();
        });

        $this->orderEcommerceDiscountService->checkOrderEcommerceDetailsService = $this->mock(
            CheckOrderEcommerceDetailsService::class,
            function ($mock): void {
                $mock->shouldReceive('hasVoucher')
                    ->once()
                    ->andReturn(false);
            }
        );

        $response = $this->orderEcommerceDiscountService->checkVoucherDetails(500);
        $this->assertNull($response);
    }
);

test('It calls getOrderDiscountAmountFor and return array', function (): void {
    $this->orderDetails['cart_loyalty_point_amount'] = 50;

    $mockCheckOrderEcommerceDetailsService = $this->mock(
        CheckOrderEcommerceDetailsService::class,
        function ($mock): void {
            $mock->shouldReceive('hasVoucher')
                ->once()
                ->andReturn(true);
            $mock->shouldReceive('hasLoyaltyPointsForCart')
                ->once()
                ->andReturn(true);
            $mock->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        }
    );

    $this->orderEcommerceDiscountService->checkOrderEcommerceDetailsService = $mockCheckOrderEcommerceDetailsService;
    $this->orderEcommerceDiscountService->voucher = new Voucher();

    $this->mock(VoucherEcommerceDiscountService::class, function ($mock): void {
        $mock->shouldReceive('getDiscountAmount')
            ->once()
            ->andReturn(100);
    });

    $response = $this->orderEcommerceDiscountService->getOrderDiscountAmountFor(500);
    $this->assertTrue(100.0 === $response['voucher_discount']);
    $this->assertTrue(50.0 === $response['cart_wide_loyalty_point_discount']);
    $this->assertTrue(150.0 === $response['total_discount']);
});

test('It calls getItemDiscountAmountFor and return array', function (): void {
    $this->orderDetails['cart_loyalty_point_amount'] = 50;

    $mockCheckOrderEcommerceDetailsService = $this->mock(
        CheckOrderEcommerceDetailsService::class,
        function ($mock): void {
            $mock->shouldReceive('hasVoucher')
                ->andReturn(true);
            $mock->shouldReceive('hasItemPromotion')
                ->once()
                ->andReturn(false);
            $mock->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        }
    );

    $this->orderEcommerceDiscountService->checkOrderEcommerceDetailsService = $mockCheckOrderEcommerceDetailsService;
    $this->orderEcommerceDiscountService->voucher = new Voucher();

    $response = $this->orderEcommerceDiscountService->getItemDiscountAmountFor([
        'id' => 1,
        'upc' => 'abd123',
        'price' => '10.00',
        'total_amount' => '10.00',
        'quantity' => '1',
        'promoter_ids' => [1],
    ]);

    $this->assertTrue(0.0 === $response['item_wise_discount']);
    $this->assertTrue(0.0 === $response['total_discount']);
});
