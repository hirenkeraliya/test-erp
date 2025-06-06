<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Http\Controllers\Api\Pos\VoucherConfigurationController;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('it calls the getList method and returns vouchers list records with related data', function (): void {
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($companyId, $voucherConfiguration): void {
        $mock->shouldReceive('getListForPosWithRelatedData')
            ->once()
            ->with($companyId, null)
            ->andReturn(collect($voucherConfiguration));
    });

    $voucherConfigurationController = new VoucherConfigurationController();
    $response = $voucherConfigurationController->getList($request);

    expect($response)->toBeArray();
});

test(
    'it throws exception when try to get vouchers list without open counter',
    function (): void {
        [$request, $companyId, $cashier, $voucherConfiguration] = seedVoucherConfigurationRecords();

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->times(0)
                ->with($cashier)
                ->andReturn(1);
        });

        $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($companyId, $voucherConfiguration): void {
            $mock->shouldReceive('getListForPosWithRelatedData')
                ->times(0)
                ->with($companyId)
                ->andReturn(collect($voucherConfiguration));
        });

        $voucherConfigurationController = new VoucherConfigurationController();
        $voucherConfigurationController->getList($request);
    }
)->throws(HttpException::class, 'The counter has not been opened yet.');

test('it calls the getBirthdayVoucherConfiguration and returns birthday voucher configuration', function (): void {
    [$request, $companyId, $cashier, $voucherConfiguration] = seedVoucherConfigurationRecords();

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($companyId, $voucherConfiguration): void {
        $mock->shouldReceive('getBirthDayVoucherConfigurationByCompanyId')
            ->times(1)
            ->with($companyId)
            ->andReturn($voucherConfiguration);
    });

    $voucherConfigurationController = new VoucherConfigurationController();
    $response = $voucherConfigurationController->getBirthdayVoucherConfiguration($request);

    expect($response['birthday_voucher_configuration']->resource->toArray())
        ->toHaveKeys(
            ['id', 'use_minimum_spend_amount', 'validity_days', 'discount_type', 'get_value', 'start_date', 'end_date']
        );
});

test(
    'it calls the getListLoyaltyPointForPosWithRelatedData method and returns vouchers list records with related data',
    function (): void {
        $companyId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
        });

        $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($companyId, $voucherConfiguration): void {
            $mock->shouldReceive('getListLoyaltyPointForPosWithRelatedData')
            ->once()
            ->with($companyId, null)
            ->andReturn(collect($voucherConfiguration));
        });

        $voucherConfigurationController = new VoucherConfigurationController();
        $response = $voucherConfigurationController->getLoyaltyPointVoucherConfiguration($request);

        expect($response)->toBeArray();
    }
);

function seedVoucherConfigurationRecords(): array
{
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
        'status' => true,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    return [$request, $companyId, $cashier, $voucherConfiguration];
}
