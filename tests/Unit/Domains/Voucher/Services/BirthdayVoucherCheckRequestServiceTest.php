<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Voucher\DataObjects\BirthdayVoucherData;
use App\Domains\Voucher\Services\BirthdayVoucherCheckRequestService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('checkRequestDetails method set mismatches when discount type mismatch.', function (): void {
    $voucherConfiguration = seedVoucherConfigurationRecord();

    $birthdayVoucherData = new BirthdayVoucherData(
        $voucherConfiguration->id,
        DiscountTypes::PERCENTAGE->value,
        '123',
        $voucherConfiguration->use_minimum_spend_amount,
        (string) Carbon::now()->addDays($voucherConfiguration->validity_days),
        (string) Carbon::now(),
        null,
        1,
    );

    $birthdayVoucherCheckRequestService = resolve(BirthdayVoucherCheckRequestService::class);
    $birthdayVoucherCheckRequestService->birthdayVoucherMismatches = collect([]);

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
    });

    $birthdayVoucherCheckRequestService->checkRequestDetails($birthdayVoucherData, $voucherConfiguration, 1);
})->throws(
    HttpException::class,
    'The specified voucher 123 does not match the discount type specified in the respective voucher configuration.'
);

test(
    'checkRequestDetails method set mismatches when voucher configuration is not in range of start and end date',
    function (): void {
        $voucherConfiguration = seedVoucherConfigurationRecord();

        $format = Carbon::createFromFormat('Y-m-d', '2023-08-17');

        $birthdayVoucherData = new BirthdayVoucherData(
            $voucherConfiguration->id,
            DiscountTypes::FLAT->value,
            '123',
            $voucherConfiguration->use_minimum_spend_amount,
            $format->addDays($voucherConfiguration->validity_days)->format('Y-m-d'),
            '2023-08-20 12:05:05',
            null,
            1,
        );

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('doVoucherNumbersExist')
                ->once()
                ->andReturn(false);
        });

        $birthdayVoucherCheckRequestService = resolve(BirthdayVoucherCheckRequestService::class);
        $birthdayVoucherCheckRequestService->birthdayVoucherMismatches = collect([]);

        $birthdayVoucherCheckRequestService->checkRequestDetails($birthdayVoucherData, $voucherConfiguration, 1);
    }
)->throws(
    HttpException::class,
    'The specified voucher configuration is available only between 2023-08-14 and 2023-08-16 However, the requested date is 2023-08-20.'
);

test('checkRequestDetails method set mismatches when voucher expired at is mismatch', function (): void {
    $voucherConfiguration = seedVoucherConfigurationRecord();

    $voucherConfiguration->start_date = now()->subDay()->format('Y-m-d');
    $voucherConfiguration->end_date = now()->addDay()->format('Y-m-d');

    $birthdayVoucherData = new BirthdayVoucherData(
        $voucherConfiguration->id,
        DiscountTypes::FLAT->value,
        '123',
        $voucherConfiguration->use_minimum_spend_amount,
        Carbon::now()->addDays(20)->format('Y-m-d'),
        now()->format('Y-m-d H:i:s'),
        null,
        1,
    );

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
    });

    $birthdayVoucherCheckRequestService = resolve(BirthdayVoucherCheckRequestService::class);
    $birthdayVoucherCheckRequestService->birthdayVoucherMismatches = collect([]);

    $birthdayVoucherCheckRequestService->checkRequestDetails($birthdayVoucherData, $voucherConfiguration, 1);
})->throws(HttpException::class);

test('checkRequestDetails method set mismatches when minimum spend amount mismatch', function (): void {
    $voucherConfiguration = seedVoucherConfigurationRecord();

    $voucherConfiguration->start_date = now()->subDay()->format('Y-m-d');
    $voucherConfiguration->end_date = now()->addDay()->format('Y-m-d');

    $birthdayVoucherData = new BirthdayVoucherData(
        $voucherConfiguration->id,
        DiscountTypes::FLAT->value,
        '123',
        1,
        Carbon::now()->addDays($voucherConfiguration->validity_days)->format('Y-m-d'),
        now()->format('Y-m-d H:i:s'),
        null,
        1,
    );

    $birthdayVoucherCheckRequestService = resolve(BirthdayVoucherCheckRequestService::class);
    $birthdayVoucherCheckRequestService->birthdayVoucherMismatches = collect([]);

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('doVoucherNumbersExist')
            ->once()
            ->andReturn(false);
    });
    $birthdayVoucherCheckRequestService->checkRequestDetails($birthdayVoucherData, $voucherConfiguration, 1);
})->throws(
    HttpException::class,
    'The specified minimum spend amount is not valid. The actual minimum spend amount is 20 while the requested minimum spend amount is 1'
);

function seedVoucherConfigurationRecord()
{
    return VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
        'use_minimum_spend_amount' => 20,
        'validity_days' => 10,
        'discount_type' => DiscountTypes::FLAT->value,
        'get_value' => 1,
        'start_date' => '2023-08-14',
        'end_date' => '2023-08-16',
        'status' => true,
    ]);
}
