<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\MysteryGift\Services\MysteryGiftService;
use App\Domains\VoucherConfiguration\DataObjects\VoucherConfigurationData;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\MysteryGift;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->mysteryGift = MysteryGift::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-10',
        'minimum_spend_amount_for_flat_amount' => 100,
        'max_flat_amount' => 50,
        'minimum_spend_amount_for_percentage' => 200,
        'max_percentage' => 10,
        'is_flat_amount' => true,
        'is_percentage' => true,
    ]);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->user = Admin::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);
});

test('generateVoucherForMysteryGift method works as expected', function (): void {
    $mysteryGift = $this->mysteryGift;

    $this->mock(VoucherConfigurationQueries::class, function ($mock): void {
        $mock->shouldReceive('checkVoucherExistForMysteryGift')
            ->once()
            ->andReturn(collect([]));
        $mock->shouldReceive('addNew')
            ->twice();
    });

    $mysteryGiftService = new MysteryGiftService();
    $mysteryGiftService->generateVoucherForMysteryGift($mysteryGift, $this->user);
});

test('addOrUpdateFlatVoucher method works as expected', function (): void {
    $mysteryGift = $this->mysteryGift;

    $voucherConfigurationData = new VoucherConfigurationData(
        1,
        2,
        1,
        0.0,
        100.0,
        10,
        DiscountTypes::FLAT->value,
        null,
        '2023-01-01',
        '2023-01-10',
        null,
        null,
        [
            [
                'minimum_spend_amount' => 100.0,
                'maximum_spend_amount' => 200.0,
                'get_value' => 50.0,
            ],
        ],
        true,
        true,
        true,
        null,
        null,
        null,
        'Test Title',
        null,
        null,
        null,
        null,
        1
    );

    $this->mock(VoucherConfigurationQueries::class, function ($mock): void {
        $mock->shouldReceive('inactiveVoucherConfiguration')
            ->never();
        $mock->shouldReceive('update')
            ->never();
        $mock->shouldReceive('addNew')
            ->once();
    });

    $mysteryGiftService = new MysteryGiftService();
    $mysteryGiftService->addOrUpdateFlatVoucher($mysteryGift, $voucherConfigurationData, $this->user, collect([]));
});

test('addOrUpdatePercentageVoucher method works as expected', function (): void {
    $mysteryGift = $this->mysteryGift;

    $voucherConfigurationData = new VoucherConfigurationData(
        1,
        2,
        1,
        0.0,
        200.0,
        10,
        DiscountTypes::PERCENTAGE->value,
        null,
        '2023-01-01',
        '2023-01-10',
        null,
        null,
        [
            [
                'minimum_spend_amount' => 200.0,
                'maximum_spend_amount' => 300.0,
                'get_value' => 10.0,
            ],
        ],
        true,
        true,
        true,
        null,
        null,
        null,
        'Test Title',
        null,
        null,
        null,
        null,
        1
    );

    $this->mock(VoucherConfigurationQueries::class, function ($mock): void {
        $mock->shouldReceive('inactiveVoucherConfiguration')
            ->never();
        $mock->shouldReceive('update')
            ->never();
        $mock->shouldReceive('addNew')
            ->once();
    });

    $mysteryGiftService = new MysteryGiftService();
    $mysteryGiftService->addOrUpdatePercentageVoucher(
        $mysteryGift,
        $voucherConfigurationData,
        $this->user,
        collect([])
    );
});
