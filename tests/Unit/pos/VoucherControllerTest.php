<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\MemberQueries;
use App\Domains\Voucher\DataObjects\BirthdayVoucherData;
use App\Domains\Voucher\DataObjects\LoyaltyPointVoucherData;
use App\Domains\Voucher\DataObjects\PaginatedVoucherListDataForPos;
use App\Domains\Voucher\Services\BirthdayVoucherCheckRequestService;
use App\Domains\Voucher\Services\LoyaltyPointVoucherService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Http\Controllers\Api\Pos\VoucherController;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('it calls the getPaginatedList method and returns the paginated list of vouchers', function (): void {
    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => $member->id,
        'generated_by_sale_id' => 1,
    ]);

    $voucher->member = $member;

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $paginatedVoucherListData = [
        'per_page' => 10,
        'page' => 1,
        'after_updated_at' => null,
    ];

    $paginatedVoucherListDataForPos = new PaginatedVoucherListDataForPos(...$paginatedVoucherListData);

    $filterData = [
        'per_page' => $paginatedVoucherListDataForPos->per_page,
        'after_updated_at' => $paginatedVoucherListDataForPos->after_updated_at,
    ];

    $request = new Request($filterData);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedList')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $voucherController = new VoucherController();
    $response = $voucherController->getPaginatedList($request, $paginatedVoucherListDataForPos);

    expect($response['vouchers']->resource);
});

test(
    'it throws an exception if counter is not open But, try to get voucher list',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];
        $request = new Request();
        $paginatedVoucherListData = [
            'per_page' => 10,
            'page' => 1,
            'after_updated_at' => null,
        ];
        $paginatedVoucherListDataForPos = new PaginatedVoucherListDataForPos(...$paginatedVoucherListData);
        $request->setUserResolver(fn (): Cashier => $cashier);
        $voucherController = new VoucherController();
        $voucherController->getPaginatedList($request, $paginatedVoucherListDataForPos);
    }
)->throws(HttpException::class, 'The counter has not been opened yet.');

test(
    'it throws an exception if counter is not open But, try to get generate member birthday voucher',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];
        $request = new Request();
        $birthdayVoucherData = new BirthdayVoucherData(
            1,
            DiscountTypes::FLAT->value,
            '123',
            1,
            '2025-01-01',
            '2025-01-01 01:01:01',
            null,
            1,
        );
        $request->setUserResolver(fn (): Cashier => $cashier);
        $voucherController = new VoucherController();
        $voucherController->generateMemberBirthdayVoucher($birthdayVoucherData, $request, 1);
    }
)->throws(HttpException::class, 'The counter has not been opened yet.');

test('it calls the generateMemberBirthdayVoucher thrown an exceptions if birth date does not match', function (): void {
    $birthdayVoucherData = new BirthdayVoucherData(
        1,
        DiscountTypes::FLAT->value,
        '123',
        1,
        now()->format('Y-m-d'),
        now()->format('Y-m-d H:i:s'),
        null,
        1,
    );

    $request = new Request();

    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'date_of_birth' => Carbon::now()->subMonthNoOverflow()->format('Y-m-d'),
        'created_location_id' => 1,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getDateOfBirthAndBirthdayVoucherLastGeneratedColumnAtById')
            ->once()
            ->with(1, $member->id)
            ->andReturn($member);
    });

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->andReturn(1);
    });

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
    });

    $voucherController = new VoucherController();
    $voucherController->generateMemberBirthdayVoucher($birthdayVoucherData, $request, 1);
})->throws(HttpException::class, 'The specified member`s birthday is not in the current month.');

test(
    'it calls the generateMemberBirthdayVoucher thrown an exceptions if birthday voucher already generated',
    function (): void {
        $currentTime = Carbon::now();
        $birthdayVoucherData = new BirthdayVoucherData(
            1,
            DiscountTypes::FLAT->value,
            '123',
            1,
            $currentTime->format('Y-m-d'),
            $currentTime->format('Y-m-d H:i:s'),
            null,
            1,
        );

        $request = new Request();

        $companyId = 1;

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'date_of_birth' => $currentTime->format('Y-m-d'),
            'created_location_id' => 1,
            'birthday_voucher_last_generated_at' => $currentTime->format('Y-m-d'),
        ]);

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getDateOfBirthAndBirthdayVoucherLastGeneratedColumnAtById')
            ->once()
            ->with(1, $member->id)
            ->andReturn($member);
        });

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->andReturn(1);
        });

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->with($cashier->counter_update_id)
                ->andReturn($location);
        });

        $voucherController = new VoucherController();
        $voucherController->generateMemberBirthdayVoucher($birthdayVoucherData, $request, 1);
    }
)->throws(HttpException::class, 'The member`s birthday voucher has already been generated');

test('it calls the generateMemberBirthdayVoucher and generate birthday voucher as expected', function (): void {
    $birthdayVoucherData = new BirthdayVoucherData(
        1,
        DiscountTypes::FLAT->value,
        '123',
        1,
        now()->format('Y-m-d'),
        now()->format('Y-m-d H:i:s'),
        null,
        1,
    );

    $request = new Request();

    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'date_of_birth' => now()->format('Y-m-d'),
        'created_location_id' => 1,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
        'use_minimum_spend_amount' => 20,
        'validity_days' => 10,
        'discount_type' => DiscountTypes::FLAT->value,
        'get_value' => 1,
        'start_date' => now()->yesterday(),
        'end_date' => now()->tomorrow(),
        'status' => true,
    ]);

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => $member->id,
        'generated_by_sale_id' => null,
        'flat_amount' => null,
    ]);

    $voucher->mismatches = collect([]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getDateOfBirthAndBirthdayVoucherLastGeneratedColumnAtById')
            ->once()
            ->with(1, $member->id)
            ->andReturn($member);
        $mock->shouldReceive('updateBirthdayVoucherDetails')
            ->once();
    });

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->andReturn(1);
    });

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
    });

    $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($voucherConfiguration): void {
        $mock->shouldReceive('getByIdForBirthdayVoucher')
            ->once()
            ->andReturn($voucherConfiguration);
    });

    $this->mock(BirthdayVoucherCheckRequestService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();
        $mock->shouldReceive('checkRequestDetails')
            ->once();
        $mock->birthdayVoucherMismatches = collect([]);
    });

    $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($voucher);
        $mock->shouldReceive('loadVoucherWithMismatchesRelations')
            ->once()
            ->andReturn($voucher);
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $voucherController = new VoucherController();
    $response = $voucherController->generateMemberBirthdayVoucher($birthdayVoucherData, $request, 1);

    expect($response['birthday_voucher']->resource->toArray())
        ->toHaveKeys(
            [
                'id',
                'member_id',
                'discount_type',
                'number',
                'minimum_spend_amount',
                'percentage',
                'flat_amount',
                'expiry_date',
                'mismatches',
            ]
        );
});

test('getActiveBirthdayVoucher method of voucher queries class and returns the voucher details', function (): void {
    // Arrange
    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => 1,
        'generated_by_sale_id' => 1,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $member->birthdayVoucher = $voucher;

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    // Act
    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getActiveBirthdayVoucher')
            ->once()
            ->andReturn($member);
    });

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->andReturn(1);
    });

    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $cashier);

    $voucherController = new VoucherController();
    $response = $voucherController->getActiveBirthdayVoucher($request, 1);

    // Assert
    expect($response['birthday_voucher']->resource->toArray())
        ->toHaveKeys(
            [
                'id',
                'member_id',
                'discount_type',
                'number',
                'minimum_spend_amount',
                'percentage',
                'flat_amount',
                'expiry_date',
            ]
        );
});

test('it calls the generateMemberLoyaltyPointVoucher and generate birthday voucher as expected', function (): void {
    $loyaltyPointVoucherData = new LoyaltyPointVoucherData(1, 1, 10);

    $request = new Request();

    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'date_of_birth' => now()->format('Y-m-d'),
        'created_location_id' => 1,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
        'use_minimum_spend_amount' => 20,
        'validity_days' => 10,
        'discount_type' => DiscountTypes::FLAT->value,
        'get_value' => 1,
        'start_date' => now()->yesterday(),
        'end_date' => now()->tomorrow(),
        'status' => true,
    ]);

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => $member->id,
        'generated_by_sale_id' => null,
        'flat_amount' => null,
    ]);

    $voucher->mismatches = collect([]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByIdAndCompanyIdWithMembership')
            ->once()
            ->with(1, $member->id)
            ->andReturn($member);
    });

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->andReturn(1);
    });

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
    });

    $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($voucherConfiguration): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($voucherConfiguration);
    });

    $this->mock(LoyaltyPointVoucherService::class, function ($mock): void {
        $mock->shouldReceive('getVoucherTierValue')
            ->once()
            ->andReturn(10);
        $mock->shouldReceive('checkRequestDetails')
            ->once();
    });

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->once();
    });

    $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($voucher);
        $mock->shouldReceive('loadVoucherWithMismatchesRelations')
            ->once()
            ->andReturn($voucher);
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $voucherController = new VoucherController();
    $response = $voucherController->generateMemberLoyaltyPointVoucher($loyaltyPointVoucherData, $request);

    expect($response['voucher']->resource->toArray())
        ->toHaveKeys(
            [
                'id',
                'member_id',
                'discount_type',
                'number',
                'minimum_spend_amount',
                'percentage',
                'flat_amount',
                'expiry_date',
                'mismatches',
            ]
        );
});
