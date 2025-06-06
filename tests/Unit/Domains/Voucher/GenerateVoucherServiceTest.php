<?php

declare(strict_types=1);

use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Voucher\DataObjects\GenerateVoucherData;
use App\Domains\Voucher\Services\GenerateVoucherService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationTier;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->generateVoucherService = new GenerateVoucherService();
    $this->checkSaleDetailsService = new CheckSaleDetailsService();

    $this->companyId = 1;

    $this->saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => GenerateVoucherData::collection([
            [
                'voucher_configuration_id' => 1,
                'discount_type' => 1,
                'number' => 'TEST_NUMBER',
                'minimum_spend_amount' => 10.10,
                'percentage' => 50.50,
                'flat_amount' => null,
                'expired_at' => now()->format('Y-m-d'),
            ],
        ]),
        'cashback_id' => null,
        'cashback_amount' => null,
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
        'sale_round_off_amount' => 0,
    ];
});

test('setDetails method works as expected', function (): void {
    $mock = $this->createPartialMock(GenerateVoucherService::class, ['getVoucherConfigurations']);

    $voucherConfiguration = new VoucherConfiguration();

    $mock->expects($this->once())
        ->method('getVoucherConfigurations')
        ->will($this->returnValue(collect([$voucherConfiguration])));

    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);

    $mock->setDetails($this->checkSaleDetailsService);
    $this->assertTrue($mock->voucherConfigurations->first() === $voucherConfiguration);
});

test('getVoucherConfigurations method calls getByIds method of VoucherConfigurationQueries class', function (): void {
    $this->generateVoucherService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $this->generateVoucherService->checkSaleDetailsService->companyId = 1;
    $voucherConfigurationIds = [1, 2];

    $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($voucherConfigurationIds): void {
        $mock->shouldReceive('getByIds')
            ->once()
            ->with($voucherConfigurationIds, 1)
            ->andReturn(collect([]));
    });

    $this->generateVoucherService->getVoucherConfigurations($voucherConfigurationIds);
});

test('checkVouchers method returns void when vouchers are not specified', function (): void {
    $this->generateVoucherService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $saleDetails = $this->saleDetails;
    $saleDetails['vouchers'] = null;
    $this->generateVoucherService->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);

    $response = $this->generateVoucherService->checkVouchers(555.00);
    $this->assertEmpty($response);
});

test(
    'checkVouchers method throws an exception when specified voucher is not available in our records',
    function (): void {
        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $this->generateVoucherService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->generateVoucherService->checkSaleDetailsService->companyId = 1;
        $this->generateVoucherService->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->generateVoucherService->voucherConfigurations = collect([]);

        $this->generateVoucherService->checkVouchers(555.00);
    }
)->throws(HttpException::class, 'The specified voucher configuration is not available in our records.');

test('checkVouchers method sets the sale mismatches when voucher status is inactive', function (): void {
    $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
    });

    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
    $mock->checkSaleDetailsService->companyId = 1;
    $mock->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $this->saleDetails['vouchers'][0]->discount_type = 2;
        $this->saleDetails['vouchers'][0]->expired_at = '2022-01-06';

        $mock->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = null;
        $mock->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
)->throws(HttpException::class, 'Specified voucher configuration is not active.');

test(
    'checkVouchers method throws an exception when a voucher is only for the non members and a member is specified',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $mock->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $mock->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $this->saleDetails['vouchers'][0]->flat_amount = 1;
        $this->saleDetails['vouchers'][0]->discount_type = 2;

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $mock->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $saleDetails['happened_at'] = 2022 - 0o1 - 0o4;
        $this->saleDetails['vouchers'][0]->discount_type = 2;
        $mock->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(true);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $mock->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $mock->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
    'The specified voucher is a birthday voucher. You cannot generate birthday vouchers from the POS application.'
);

test(
    'checkVouchers method sets the sale mismatches when voucher configuration is not in range of start and end date',
    function (Carbon $happenedAt, Carbon $startDate, Carbon $endDate): void {
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $saleDetails = $this->saleDetails;
        $saleDetails['happened_at'] = $happenedAt->format('Y-m-d H:i:s');
        $this->saleDetails['vouchers'][0]->discount_type = 2;
        $mock->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
            $mock->checkSaleDetailsService->saleMismatches->contains(
                'The specified voucher configuration is available only between ' . $voucherConfiguration->start_date . ' and ' . $voucherConfiguration->end_date . '. The requested date is ' . $saleDetails['happened_at'] . 'is not within the valid range.'
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $saleDetails = $this->saleDetails;
        $saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleDetails['vouchers'][0]->discount_type = 2;
        $this->saleDetails['vouchers'][0]->minimum_spend_amount = 10;
        $this->saleDetails['vouchers'][0]->expired_at = now()->addDay()->format('Y-m-d');
        $mock->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $saleDetails = $this->saleDetails;
        $saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleDetails['vouchers'][0]->discount_type = 2;
        $this->saleDetails['vouchers'][0]->minimum_spend_amount = 10;
        $this->saleDetails['vouchers'][0]->flat_amount = 10;
        $this->saleDetails['vouchers'][0]->expired_at = now()->addDay()->format('Y-m-d');
        $mock->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $saleDetails = $this->saleDetails;
        $saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleDetails['vouchers'][0]->discount_type = 1;
        $this->saleDetails['vouchers'][0]->minimum_spend_amount = 10;
        $this->saleDetails['vouchers'][0]->percentage = 10;
        $this->saleDetails['vouchers'][0]->expired_at = now()->addDay()->format('Y-m-d');
        $mock->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $saleDetails = $this->saleDetails;
        $saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->saleDetails['vouchers'][0]->discount_type = 2;
        $this->saleDetails['vouchers'][0]->minimum_spend_amount = 10;
        $this->saleDetails['vouchers'][0]->expired_at = now()->addDay()->format('Y-m-d');
        $mock->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $mock->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
    'The specified voucher is a welcome member voucher. You cannot generate welcome member vouchers from the POS application.'
);

test(
    'checkVouchers method throws an exception when the employee Voucher generate.',
    function (): void {
        $mock = $this->createPartialMock(GenerateVoucherService::class, ['getExcludeAmountForVoucher']);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
        });

        $this->saleDetails['employee_id'] = 1;

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkSaleDetailsService->companyId = 1;
        $mock->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $mock->checkSaleDetailsService->saleMismatches = collect([]);
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
)->throws(HttpException::class, 'Voucher cannot be generated for the employees.');

test(
    'checkVouchers method throws an exception when specified voucher is generate on layaway sale',
    function (): void {
        $this->generateVoucherService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->generateVoucherService->checkSaleDetailsService->companyId = 1;
        $this->saleDetails['is_layaway'] = true;
        $this->generateVoucherService->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->generateVoucherService->voucherConfigurations = collect([]);

        $this->generateVoucherService->checkVouchers(555.00);
    }
)->throws(HttpException::class, 'Voucher cannot be generated for Layaway Sales.');

test(
    'checkVouchers method throws an exception when specified voucher is generate on credit sale',
    function (): void {
        $this->generateVoucherService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->generateVoucherService->checkSaleDetailsService->companyId = 1;
        $this->saleDetails['is_credit_sale'] = true;
        $this->generateVoucherService->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->generateVoucherService->voucherConfigurations = collect([]);

        $this->generateVoucherService->checkVouchers(555.00);
    }
)->throws(HttpException::class, 'Voucher cannot be generated for Credit Sales.');
