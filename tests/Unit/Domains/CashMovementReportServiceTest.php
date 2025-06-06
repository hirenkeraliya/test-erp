<?php

declare(strict_types=1);

use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\CashMovement\Services\CashMovementReportService;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Models\Cashier;
use App\Models\CashMovement;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('exportCashMovement the cash movement report for a given company', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $filterData = [
        'location_ids' => [$location->id],
        'date_range' => [],
        'cashier_ids' => [],
        'counter_ids' => [],
    ];

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdsWithNameAndCode')
            ->once()
            ->andReturn(collect([$location]));
    });

    $this->mock(CustomReportService::class, function ($mock): void {
        $mock->shouldReceive('prepareDateRange')
            ->once()
            ->andReturn([now(), now()]);
    });

    $this->mock(CashMovementQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashMovementForReport')
            ->once()
            ->andReturn(collect([]));
    });

    $cashMovementReportService = resolve(CashMovementReportService::class);
    $result = $cashMovementReportService->exportCashMovement($company->id, $filterData, 'demo.xlsx');

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});

it('prepares the cash movement data correctly', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->make([
        'location_id' => $location->id,
    ]);

    $employee = Employee::factory()->make([
        'company_id' => $company->id,
        'designation_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
    ]);

    $cashier->employee = $employee;

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
    ]);

    $counterUpdate->counter = $counter;
    $counterUpdate->cashier = $cashier;

    $cashMovement = CashMovement::factory()->make([
        'counter_update_id' => $counterUpdate->id,
        'cash_movement_type_id' => 1,
        'cash_movement_reason_id' => 1,
        'other_reason' => 1,
        'authorizer_id' => 1,
        'authorizer_type' => ModelMapping::STORE_MANAGER->name,
        'amount' => 1,
        'happened_at' => now()->format('Y-m-d'),
    ]);

    $cashMovement->counterUpdate = $counterUpdate;

    $filterData = [
        'location_ids' => [$location->id],
        'date_range' => [],
        'cashier_ids' => [],
        'counter_ids' => [],
    ];

    $this->mock(CashMovementQueries::class, function ($mock) use ($cashMovement): void {
        $mock->shouldReceive('getCashMovementForReport')
            ->once()
            ->andReturn(collect([$cashMovement]));
    });

    $cashMovementReportService = new CashMovementReportService();
    $reflection = new ReflectionClass($cashMovementReportService);
    $method = $reflection->getMethod('preparedCashMovement');
    $method->setAccessible(true);

    $result = $method->invoke($cashMovementReportService, $filterData, collect([$location]), $company->id);
    expect($result[0])->toHaveKeys(['0.location_name', '0.cash_movements', '0.cash_in_total', '0.cash_out_total']);
});
