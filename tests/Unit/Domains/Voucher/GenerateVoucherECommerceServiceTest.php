<?php

declare(strict_types=1);

use App\Domains\Order\DataObjects\OrderECommerceData;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Domains\Voucher\DataObjects\GenerateVoucherData;
use App\Domains\Voucher\Services\GenerateVoucherECommerceService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Models\Member;
use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationTier;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->generateVoucherECommerceService = new GenerateVoucherECommerceService();
    $this->checkOrderEcommerceDetailsService = new CheckOrderEcommerceDetailsService();

    $this->checkOrderEcommerceDetailsService->orderMismatches = collect([]);

    $this->companyId = 1;

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
        'happened_at' => '2022-01-04 04:20:50',
    ];

    $this->vouchers = GenerateVoucherData::collection([
        [
            'voucher_configuration_id' => 1,
            'discount_type' => 1,
            'number' => 'TEST_NUMBER',
            'minimum_spend_amount' => 10.10,
            'percentage' => 50.50,
            'flat_amount' => null,
            'expired_at' => now()->format('Y-m-d'),
        ],
    ]);
});

test('setDetails method works as expected', function (): void {
    $this->orderDetails['vouchers'] = $this->vouchers;

    $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getVoucherConfigurations']);

    $mock->voucherConfigurations = collect([]);

    $voucherConfiguration = new VoucherConfiguration();

    $mock->expects($this->once())
        ->method('getVoucherConfigurations')
        ->will($this->returnValue(collect([$voucherConfiguration])));

    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);

    $mock->setDetails($this->checkOrderEcommerceDetailsService);
    $this->assertTrue($mock->voucherConfigurations->first() === $voucherConfiguration);
});

test('getVoucherConfigurations method calls getByIds method of VoucherConfigurationQueries class', function (): void {
    $this->generateVoucherECommerceService->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
    $this->generateVoucherECommerceService->checkOrderEcommerceDetailsService->companyId = 1;
    $voucherConfigurationIds = [1, 2];

    $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($voucherConfigurationIds): void {
        $mock->shouldReceive('getByIds')
            ->once()
            ->with($voucherConfigurationIds, 1)
            ->andReturn(collect([]));
    });

    $this->generateVoucherECommerceService->getVoucherConfigurations($voucherConfigurationIds);
});

test('checkVouchers method returns void when vouchers are not specified', function (): void {
    $this->generateVoucherECommerceService->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
    $orderDetails = $this->orderDetails;
    $orderDetails['vouchers'] = null;
    $this->generateVoucherECommerceService->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(
        ...$orderDetails
    );

    $response = $this->generateVoucherECommerceService->checkVouchers(555.00);
    $this->assertEmpty($response);
});

test(
    'checkVouchers method throws an exception when specified voucher is not available in our records',
    function (): void {
        $this->orderDetails['vouchers'] = $this->vouchers;
        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $this->generateVoucherECommerceService->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $this->generateVoucherECommerceService->checkOrderEcommerceDetailsService->companyId = 1;
        $this->generateVoucherECommerceService->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(
            ...$this->orderDetails
        );
        $this->generateVoucherECommerceService->voucherConfigurations = collect([]);

        $this->generateVoucherECommerceService->checkVouchers(555.00);
    }
)->throws(HttpException::class, 'The specified voucher configuration is not available in our records.');

test('checkVouchers method sets the sale mismatches when voucher status is inactive', function (): void {
    $this->orderDetails['vouchers'] = $this->vouchers;
    $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
    });

    $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
    $mock->checkOrderEcommerceDetailsService->companyId = 1;
    $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
        'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
        'issue_minimum_spend_amount' => 444.00,
        'status' => false,
    ]);
    $mock->voucherConfigurations = collect([$voucherConfiguration]);

    $mock->expects($this->never())
        ->method('getExcludeAmountForVoucher')
        ->will($this->returnValue(0.00));

    $mock->checkVouchers(555.00);
})->throws(HttpException::class, 'Specified voucher configuration is not active.');

test(
    'checkVouchers method sets the sale mismatches when specified minimum spend amount does not match with the original.',
    function (): void {
        $this->orderDetails['vouchers'] = $this->vouchers;

        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->member = new Member();
        $this->orderDetails['vouchers'][0]->discount_type = 2;
        $this->orderDetails['vouchers'][0]->expired_at = '2022-01-06';

        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'issue_minimum_spend_amount' => 444.00,
            'use_minimum_spend_amount' => 500.00,
            'status' => true,
            'discount_type' => 2,
            'start_date' => '2022-01-01',
            'end_date' => '2022-01-10',
            'validity_days' => 2,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->expects($this->never())
            ->method('getExcludeAmountForVoucher')
            ->will($this->returnValue(0.00));

        $mock->checkVouchers(555.00);
    }
)->throws(
    HttpException::class,
    'The specified minimum spend amount is not valid. The actual minimum spend amount is 500 while the requested minimum spend amount is 10.1'
);

test(
    'checkVouchers method throws an exception when voucher configuration is restricted by member and member not attached',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $this->orderDetails['vouchers'] = $this->vouchers;
        $orderDetails = $this->orderDetails;
        $orderDetails['member_id'] = null;
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'issue_minimum_spend_amount' => 444.00,
            'status' => true,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->checkVouchers(555.00);
    }
)->throws(HttpException::class, 'The member is required for the specified voucher configuration.');

test(
    'checkVouchers method throws an exception when a voucher is only for the non members and a member is specified',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $this->orderDetails['vouchers'] = $this->vouchers;

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'issue_minimum_spend_amount' => 444.00,
            'restricted_by_type' => RestrictedByTypes::NON_MEMBER_ONLY->value,
            'status' => false,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->checkVouchers(555.00);
    }
)->throws(HttpException::class, 'Specified voucher configuration is not active.');

test(
    'checkVouchers method sets the sale mismatches when voucher discount type does not match with specified voucher configuration discount type.',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });
        $this->orderDetails['vouchers'] = $this->vouchers;
        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->member = new Member();
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'discount_type' => 2,
            'issue_minimum_spend_amount' => 444.00,
            'status' => true,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->expects($this->never())
            ->method('getExcludeAmountForVoucher')
            ->will($this->returnValue(0.00));

        $mock->checkVouchers(555.00);
    }
)->throws(
    HttpException::class,
    'The discount type specified for voucher TEST_NUMBER does not match the respective voucher configuration.'
);

test(
    'checkVouchers method sets the sale mismatches when voucher discount does not match with specified voucher configuration discount.',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });
        $this->orderDetails['vouchers'] = $this->vouchers;
        $this->orderDetails['vouchers'][0]->flat_amount = 1;
        $this->orderDetails['vouchers'][0]->discount_type = 2;

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->member = new Member();
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'discount_type' => 2,
            'get_value' => 50,
            'issue_minimum_spend_amount' => 444.00,
            'status' => true,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->expects($this->never())
            ->method('getExcludeAmountForVoucher')
            ->will($this->returnValue(0.00));

        $mock->checkVouchers(555.00);
    }
)->throws(
    HttpException::class,
    'There is a mismatch in the flat amount discount for the voucher. The actual flat amount for the voucher is 50 while the given flat amount is 1'
);

test(
    'checkVouchers method sets the sale mismatches when voucher expiry date does not match with specified voucher configuration expiry date.',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $this->orderDetails['vouchers'] = $this->vouchers;

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $orderDetails['happened_at'] = 2022 - 0o1 - 0o4;
        $this->orderDetails['vouchers'][0]->discount_type = 2;
        $mock->checkOrderEcommerceDetailsService->member = new Member();
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'discount_type' => 2,
            'get_value' => 50,
            'validity_days' => 3,
            'issue_minimum_spend_amount' => 444.00,
            'status' => true,
            'start_date' => 1999,
            'end_date' => 1993,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->expects($this->never())
            ->method('getExcludeAmountForVoucher')
            ->will($this->returnValue(0.00));

        $mock->checkVouchers(555.00);
    }
)->throws(
    HttpException::class,
    'The specified voucher configuration is available only between 1999 and 1993. The requested date is 2022-01-04is not within the valid range.'
);

test(
    'checkVouchers method throws an exception when specified voucher number is already in our records.',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(true);
        });

        $this->orderDetails['vouchers'] = $this->vouchers;

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'issue_minimum_spend_amount' => 444.00,
            'status' => false,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->checkVouchers(555.00);
    }
)->throws(
    HttpException::class,
    'Some of the voucher numbers are already in our records. Please provide distinct voucher numbers.'
);

test(
    'checkVouchers method throws an exception when the specified voucher configuration is birthday voucher.',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $this->orderDetails['vouchers'] = $this->vouchers;

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'issue_minimum_spend_amount' => 444.00,
            'status' => true,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->checkVouchers(555.00);
    }
)->throws(
    HttpException::class,
    'The specified voucher is a birthday voucher. You cannot generate birthday vouchers from the Ecommerce.'
);

test(
    'checkVouchers method sets the sale mismatches when voucher configuration is not in range of start and end date',
    function (Carbon $happenedAt, Carbon $startDate, Carbon $endDate): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $this->orderDetails['vouchers'] = $this->vouchers;
        $orderDetails = $this->orderDetails;
        $orderDetails['happened_at'] = $happenedAt->format('Y-m-d H:i:s');
        $this->orderDetails['vouchers'][0]->discount_type = 2;
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'issue_minimum_spend_amount' => 444.00,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => true,
            'discount_type' => 2,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->expects($this->never())
            ->method('getExcludeAmountForVoucher')
            ->will($this->returnValue(0.00));

        $mock->checkVouchers(555.00);

        $this->assertTrue(
            $mock->checkOrderEcommerceDetailsService->orderMismatches->contains(
                'The specified voucher configuration is available only between ' . $voucherConfiguration->start_date . ' and ' . $voucherConfiguration->end_date . '. The requested date is ' . $orderDetails['happened_at'] . 'is not within the valid range.'
            )
        );
    }
)->with(
    [
        [Carbon::now()->subMonthsNoOverflow(2), Carbon::now()->subMonthNoOverflow(), Carbon::now()],
        [Carbon::now()->subDay(), Carbon::now(), Carbon::now()->addMonth()],
        [Carbon::now(), Carbon::now()->addDay(), Carbon::now()->addDays(2)],
        [Carbon::now()->addDays(3), Carbon::now(), Carbon::now()->addDays(2)],
        [Carbon::now()->addMonth(), Carbon::now(), Carbon::now()->addDays(2)],
    ]
)->throws(HttpException::class);

test(
    'checkVouchers method sets the sale mismatches when no voucher configuration tier is matched',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->member = new Member();
        $this->orderDetails['vouchers'] = $this->vouchers;
        $orderDetails = $this->orderDetails;
        $orderDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->orderDetails['vouchers'][0]->discount_type = 2;
        $this->orderDetails['vouchers'][0]->minimum_spend_amount = 10;
        $this->orderDetails['vouchers'][0]->expired_at = now()->addDay()->format('Y-m-d');
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'issue_minimum_spend_amount' => 444.00,
            'status' => true,
            'discount_type' => 2,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'validity_days' => 1,
            'use_minimum_spend_amount' => 10,
        ]);

        $voucherConfiguration->voucherConfigurationTiers = collect([
            VoucherConfigurationTier::factory()->make([
                'voucher_configuration_id' => 1,
                'minimum_spend_amount' => 500,
                'maximum_spend_amount' => 1000,
                'get_value' => 10,
            ]),
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->expects($this->once())
            ->method('getExcludeAmountForVoucher')
            ->will($this->returnValue(0.00));

        $mock->checkVouchers(444.00);
    }
)->throws(
    HttpException::class,
    'The specified voucher configuration is not valid. The sale amount after exclusions is 444 Based on that amount, no tier is available as of.'
);

test(
    'checkVouchers method sets the sale mismatches when voucher type is flat and discount amount not match',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->member = new Member();
        $this->orderDetails['vouchers'] = $this->vouchers;
        $orderDetails = $this->orderDetails;
        $orderDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->orderDetails['vouchers'][0]->discount_type = 2;
        $this->orderDetails['vouchers'][0]->minimum_spend_amount = 10;
        $this->orderDetails['vouchers'][0]->flat_amount = 10;
        $this->orderDetails['vouchers'][0]->expired_at = now()->addDay()->format('Y-m-d');
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'issue_minimum_spend_amount' => 444.00,
            'status' => true,
            'discount_type' => 2,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'validity_days' => 1,
            'use_minimum_spend_amount' => 10,
        ]);

        $voucherConfiguration->voucherConfigurationTiers = collect([
            VoucherConfigurationTier::factory()->make([
                'voucher_configuration_id' => 1,
                'minimum_spend_amount' => 0,
                'maximum_spend_amount' => 500,
                'get_value' => 20,
            ]),
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->checkVouchers(100.00);
    }
)->throws(
    HttpException::class,
    'There is a mismatch in the flat amount discount for the voucher. The actual flat amount for the voucher is 20 while the given flat amount is 10'
);

test(
    'checkVouchers method sets the sale mismatches when voucher type is percentage and discount amount not match',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->member = new Member();
        $this->orderDetails['vouchers'] = $this->vouchers;
        $orderDetails = $this->orderDetails;
        $orderDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->orderDetails['vouchers'][0]->discount_type = 1;
        $this->orderDetails['vouchers'][0]->minimum_spend_amount = 10;
        $this->orderDetails['vouchers'][0]->percentage = 10;
        $this->orderDetails['vouchers'][0]->expired_at = now()->addDay()->format('Y-m-d');
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'issue_minimum_spend_amount' => 444.00,
            'status' => true,
            'discount_type' => 1,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'validity_days' => 1,
            'use_minimum_spend_amount' => 10,
        ]);

        $voucherConfiguration->voucherConfigurationTiers = collect([
            VoucherConfigurationTier::factory()->make([
                'voucher_configuration_id' => 1,
                'minimum_spend_amount' => 0,
                'maximum_spend_amount' => 500,
                'get_value' => 20,
            ]),
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->checkVouchers(100.00);
    }
)->throws(
    HttpException::class,
    'There is a mismatch in the voucher percentage discount. The actual percentage discount for the voucher is 20 whereas the given percentage discount is 10'
);

test(
    'checkVouchers method sets the sale mismatches when sale total amount is less than minimum spend amount',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->member = new Member();
        $this->orderDetails['vouchers'] = $this->vouchers;
        $orderDetails = $this->orderDetails;
        $orderDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->orderDetails['vouchers'][0]->discount_type = 2;
        $this->orderDetails['vouchers'][0]->minimum_spend_amount = 10;
        $this->orderDetails['vouchers'][0]->expired_at = now()->addDay()->format('Y-m-d');
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'issue_minimum_spend_amount' => 500,
            'status' => true,
            'discount_type' => 2,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'validity_days' => 1,
            'use_minimum_spend_amount' => 10,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->expects($this->once())
            ->method('getExcludeAmountForVoucher')
            ->will($this->returnValue(0.00));

        $mock->checkVouchers(444.00);
    }
)->throws(
    HttpException::class,
    'The specified voucher configuration is not valid. The sale amount after exclusions is 444 and the minimum spend amount for this voucher is 500'
);

test(
    'checkVouchers method throws an exception when the specified voucher configuration is welcome member voucher.',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherECommerceService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $mock->checkOrderEcommerceDetailsService = $this->checkOrderEcommerceDetailsService;
        $mock->checkOrderEcommerceDetailsService->companyId = 1;
        $mock->checkOrderEcommerceDetailsService->member = new Member();
        $this->orderDetails['vouchers'] = $this->vouchers;
        $mock->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $mock->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::WELCOME_MEMBER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'issue_minimum_spend_amount' => 444.00,
            'status' => true,
        ]);
        $mock->voucherConfigurations = collect([$voucherConfiguration]);

        $mock->checkVouchers(555.00);
    }
)->throws(
    HttpException::class,
    'The specified voucher is a welcome member voucher. You cannot generate welcome member vouchers from the Ecommerce.'
);
