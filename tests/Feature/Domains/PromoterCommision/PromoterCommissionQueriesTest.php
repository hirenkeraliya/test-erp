<?php

declare(strict_types=1);

use App\Domains\PromoterCommission\PromoterCommissionQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Promoter;
use App\Models\PromoterCommission;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'ABCD',
    ]);

    $this->promoter = Promoter::factory()->create([
        'employee_id' => $this->employee->id,
    ]);

    $this->promoterCommissionQueries = new PromoterCommissionQueries();
});

test('addNew method add new Promoter Commission', function (): void {
    $promoter = Promoter::factory()->create();
    $previousMonth = now()->format('Y-m-d');

    $response = $this->promoterCommissionQueries->addNew([
        'promoter_id' => $promoter->id,
        'commission_amount' => 0,
        'total_sales_amount' => 100,
        'monthly_sales_target' => 100,
        'commission_date' => $previousMonth,
    ]);

    expect($response->toArray())
        ->toHaveKey('promoter_id', $promoter->id)
        ->toHaveKey('commission_amount', 0)
        ->toHaveKey('total_sales_amount', 100)
        ->toHaveKey('monthly_sales_target', 100)
        ->toHaveKey('commission_date', $previousMonth);

    $this->assertDatabaseHas('promoter_commissions', [
        'promoter_id' => $promoter->id,
        'commission_amount' => 0,
        'total_sales_amount' => 100,
        'monthly_sales_target' => 100,
        'commission_date' => $previousMonth,
    ]);
});

test('getCommissionByPromotersQuery method returns promoter commission details as expected', function (): void {
    $currentTime = Carbon::now();
    $promoterCommission = PromoterCommission::factory()->create([
        'promoter_id' => $this->promoter->id,
        'commission_date' => $currentTime->format('Y-m-d H:i:s'),
    ]);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => 'asc',
        'per_page' => 10,
        'month_range' => null,
        'promoter_ids' => [$this->promoter->id],
        'location_ids' => null,
        'brand_ids' => null,
        'department_ids' => null,
        'group_ids' => null,
    ];

    $response = $this->promoterCommissionQueries->getCommissionByPromotersQuery($filterData, $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $promoterCommission->id);
});

test('getIdsByPeriod method returns promoter commission details as expected', function (): void {
    $currentTime = Carbon::now();
    $promoterCommission = PromoterCommission::factory()->create([
        'promoter_id' => $this->promoter->id,
        'commission_date' => $currentTime->format('Y-m-d'),
    ]);

    $response = $this->promoterCommissionQueries->getIdsByPeriod($currentTime->format('Y-m-d'));

    expect($response->first()->toArray())
        ->toHaveKey('id', $promoterCommission->id);
});

test('delete method delete promoter commission', function (): void {
    $currentTime = Carbon::now();

    $promoterCommission = PromoterCommission::factory()->create([
        'promoter_id' => $this->promoter->id,
        'commission_date' => $currentTime->format('Y-m-d'),
    ]);

    $this->promoterCommissionQueries->deleteByPeriod($currentTime->format('Y-m-d'));
    $this->assertSoftDeleted($promoterCommission);
});

test('entryExistsForPeriod method return as expected', function (): void {
    $currentTime = Carbon::now();
    PromoterCommission::factory()->create([
        'promoter_id' => $this->promoter->id,
        'commission_date' => $currentTime->format('Y-m-d'),
    ]);

    $response = $this->promoterCommissionQueries->entryExistsForPeriod($currentTime->format('Y-m-d'));

    $this->assertTrue($response);

    $response = $this->promoterCommissionQueries->entryExistsForPeriod($currentTime->addDay()->format('Y-m-d'));

    $this->assertFalse($response);
});

test('updateCommissionAmount work as expected', function (): void {
    $promoterCommission = PromoterCommission::factory()->create([
        'commission_amount' => 0.00,
        'total_sales_amount' => 100.00,
    ]);

    $this->assertDatabaseHas('promoter_commissions', [
        'commission_amount' => 0.00,
    ]);

    $this->promoterCommissionQueries->updateCommissionAmount(
        $promoterCommission,
        100.20,
        0.30,
        50.00,
        0.00,
        50.00,
        0.00
    );

    $this->assertDatabaseHas('promoter_commissions', [
        'commission_amount' => 100.20,
        'total_return_sales_amount' => -50.00,
        'total_sales_amount' => 50.00,
        'commission_amount_rounding' => 0.30,
        'total_sales_amount_rounding' => 0.00,
        'total_return_sales_amount_rounding' => 0.00,
    ]);
});
