<?php

declare(strict_types=1);

use App\Domains\Common\Enums\Statuses;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\SaleTarget\DataObjects\SaleTargetData;
use App\Domains\SaleTarget\Enums\ReGenerateTarget;
use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\SaleTargetPromoterTypes;
use App\Domains\SaleTarget\Enums\SaleTargetStoreTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\SaleAchievedTarget;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->saleTarget = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
    ]);
    $this->saleTargetQueries = new SaleTargetQueries();
});

test('Sale target can be searched', function (): void {
    $response = $this->saleTargetQueries->listQuery([
        'search_text' => 'ABC',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'target_type' => null,
        'time_interval_type' => null,
        'select_status' => null,
        'location_ids' => null,
        'promoter_ids' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->saleTarget->name);
});

test('Sale target are returned as per page', function (): void {
    $response = $this->saleTargetQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'target_type' => null,
        'time_interval_type' => null,
        'select_status' => null,
        'location_ids' => null,
        'promoter_ids' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->saleTarget->name);
});

test('A new sale target can be added', function (): void {
    $this->saleTargetQueries->addNew(
        new SaleTargetData(
            'test',
            10,
            null,
            SaleTargetAmountTypes::AMOUNT->value,
            TargetType::COMPANY_WISE->value,
            TimeIntervalType::DAILY->value,
            true,
            [],
            [],
            [],
            SaleTargetStoreTypes::SELECT->value,
            SaleTargetPromoterTypes::SELECT->value,
            null,
            null
        ),
        $this->companyId
    );

    $this->assertDatabaseHas('sale_targets', [
        'name' => 'test',
        'company_id' => $this->companyId,
    ]);
});

test('A sale target can be fetched', function (): void {
    $response = $this->saleTargetQueries->getById($this->saleTarget->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->saleTarget->name);
});

test('A sale targets can be fetched', function (): void {
    $response = $this->saleTargetQueries->getByIds([$this->saleTarget->id]);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->saleTarget->name);
});

test('A sale target can be updated', function (): void {
    $this->saleTargetQueries->update(
        new SaleTargetData(
            'def',
            10,
            null,
            SaleTargetAmountTypes::AMOUNT->value,
            TargetType::COMPANY_WISE->value,
            TimeIntervalType::DAILY->value,
            true,
            [],
            [],
            [],
            SaleTargetStoreTypes::SELECT->value,
            SaleTargetPromoterTypes::SELECT->value,
            null,
            null
        ),
        $this->saleTarget
    );

    $this->assertDatabaseHas('sale_targets', [
        'name' => 'def',
        'company_id' => $this->companyId,
    ]);
});

test('getSaleTargetExport method returns sale targets as expected', function (): void {
    $response = $this->saleTargetQueries->getSaleTargetExport([
        'search_text' => 'ABC',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'target_type' => null,
        'time_interval_type' => null,
        'select_status' => null,
        'location_ids' => null,
        'promoter_ids' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->saleTarget->id)
        ->toHaveKey('name', $this->saleTarget->name);
});

test('admin can change the status of the sale target', function (): void {
    $this->saleTargetQueries->adminSetStatus($this->saleTarget->id, $this->companyId, false);

    $this->assertDatabaseHas('sale_targets', [
        'id' => $this->saleTarget->id,
        'status' => false,
    ]);
});

test('getSaleTargetWithAchieved returns the lists as expected', function (): void {
    $response = $this->saleTargetQueries->getSaleTargetWithAchieved($this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->saleTarget->id)
        ->toHaveKey('name', $this->saleTarget->name)
        ->toHaveKeys(['sale_target_timeframes', 'locations', 'promoters']);
});

test('call getPaginatedListForStoreManager method are returned as expected', function (): void {
    $this->saleTarget->target_type = TargetType::STORE_WISE->value;
    $this->saleTarget->time_interval_type = TimeIntervalType::DAILY->value;
    $this->saleTarget->status = Statuses::ACTIVE->value;

    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->saleTarget->locations()->sync($location);
    $this->saleTarget->save();

    $response = $this->saleTargetQueries->getPaginatedListForStoreManager([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'target_type' => TargetType::STORE_WISE->value,
        'time_interval_type' => TimeIntervalType::DAILY->value,
        'select_status' => Statuses::ACTIVE->value,
        'location_id' => $location->id,
        'location_ids' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->saleTarget->id)
        ->toHaveKey('name', $this->saleTarget->name);

    $this->assertDatabaseHas('location_sale_target', [
        'sale_target_id' => $this->saleTarget->id,
        'location_id' => $location->id,
    ]);
});

test('call getByIdForStoreManagerApp method and get sale target details', function (): void {
    $this->saleTarget->target_type = TargetType::STORE_WISE->value;
    $this->saleTarget->status = Statuses::ACTIVE->value;

    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->saleTarget->locations()->sync($location);
    $this->saleTarget->save();

    $response = $this->saleTargetQueries->getByIdForStoreManagerApp(
        $location->id,
        $this->saleTarget->id,
        $this->companyId
    );
    expect($response->toArray())
        ->toHaveKeys(['name', 'amount', 'sale_target_timeframes']);
});

test('call getPaginatedListForPromoterApp method are returned as expected', function (): void {
    $this->saleTarget->target_type = TargetType::PROMOTER_WISE->value;
    $this->saleTarget->time_interval_type = TimeIntervalType::DAILY->value;
    $this->saleTarget->status = Statuses::ACTIVE->value;

    $promoter = Promoter::factory()->create();
    $this->saleTarget->promoters()->sync($promoter);
    $this->saleTarget->save();

    $response = $this->saleTargetQueries->getPaginatedListForPromoterApp([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'target_type' => TargetType::PROMOTER_WISE->value,
        'time_interval_type' => TimeIntervalType::DAILY->value,
        'select_status' => Statuses::ACTIVE->value,
        'promoter_id' => $promoter->id,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->saleTarget->id)
        ->toHaveKey('name', $this->saleTarget->name);

    $this->assertDatabaseHas('promoter_sale_target', [
        'sale_target_id' => $this->saleTarget->id,
        'promoter_id' => $promoter->id,
    ]);
});

test('call getByIdForPromoterApp method and get sale target details', function (): void {
    $this->saleTarget->target_type = TargetType::PROMOTER_WISE->value;
    $this->saleTarget->status = Statuses::ACTIVE->value;

    $promoter = Promoter::factory()->create();
    $this->saleTarget->promoters()->sync($promoter);
    $this->saleTarget->save();

    $response = $this->saleTargetQueries->getByIdForPromoterApp($promoter->id, $this->saleTarget->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKeys(['name', 'amount', 'sale_target_timeframes', 'promoters', 'promoters.0.targetable']);
});

test('call getPaginatedListByPromoter method are returned as expected', function (): void {
    $this->saleTarget->target_type = TargetType::PROMOTER_WISE->value;
    $this->saleTarget->time_interval_type = TimeIntervalType::DAILY->value;
    $this->saleTarget->status = Statuses::ACTIVE->value;

    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $promoter = Promoter::factory()->create();

    $promoter->locations()->sync($location);
    $this->saleTarget->promoters()->sync($promoter);
    $this->saleTarget->save();

    $response = $this->saleTargetQueries->getPaginatedListByPromoter([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'target_type' => TargetType::PROMOTER_WISE->value,
        'time_interval_type' => TimeIntervalType::DAILY->value,
        'select_status' => Statuses::ACTIVE->value,
        'location_id' => $location->id,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->saleTarget->id)
        ->toHaveKey('name', $this->saleTarget->name);

    $this->assertDatabaseHas('promoter_sale_target', [
        'sale_target_id' => $this->saleTarget->id,
        'promoter_id' => $promoter->id,
    ]);
});

test('call getIdByPromoter method and get sale target details', function (): void {
    $this->saleTarget->target_type = TargetType::PROMOTER_WISE->value;
    $this->saleTarget->status = Statuses::ACTIVE->value;

    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $promoter = Promoter::factory()->create();

    $promoter->locations()->sync($location);
    $this->saleTarget->promoters()->sync($promoter);
    $this->saleTarget->save();

    $response = $this->saleTargetQueries->getIdByPromoter($location->id, $this->saleTarget->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKeys(['name', 'amount', 'sale_target_timeframes', 'promoters', 'promoters.0.targetable']);
});

test('markAsRegenerateStart method set regenerate is true', function (): void {
    $this->saleTarget->re_generate_target = ReGenerateTarget::COMPLETE->value;
    $this->saleTarget->save();

    $this->saleTargetQueries->markAsRegenerateStart($this->saleTarget->id, $this->companyId);

    $this->assertDatabaseHas('sale_targets', [
        'id' => $this->saleTarget->id,
        're_generate_target' => ReGenerateTarget::IN_PROGRESS->value,
    ]);
});

test('markAsRegenerateCompete method set regenerate is true', function (): void {
    $this->saleTarget->re_generate_target = ReGenerateTarget::IN_PROGRESS->value;
    $this->saleTarget->save();

    $this->saleTargetQueries->markAsRegenerateCompete($this->saleTarget->id, $this->companyId);

    $this->assertDatabaseHas('sale_targets', [
        'id' => $this->saleTarget->id,
        're_generate_target' => ReGenerateTarget::COMPLETE->value,
    ]);
});

it('gets current year sales target', function (): void {
    $company = Company::factory()->create();
    $year = 2023;

    $saleTarget = SaleTarget::factory()->create([
        'company_id' => $company->id,
        'status' => Statuses::ACTIVE->value,
        'time_interval_type' => TimeIntervalType::YEARLY->value,
    ]);

    $timeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $saleTarget->id,
        'start_date' => $year . '-01-01',
        'end_date' => $year . '-12-31',
        'target_label' => 'January',
    ]);

    $saleAchievedTarget = SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $timeframe->id,
        'target_value' => 1000,
        'achieved_value' => 800,
    ]);

    $result = $this->saleTargetQueries->getCurrentYearSalesTarget($year, $company->id, null);

    expect($result)->toHaveCount(1);
    expect($result)->toHaveKey($saleTarget->target_type);

    $targetData = $result[$saleTarget->target_type]->first();
    expect($targetData->sale_target_id)->toBe($saleTarget->id);
    expect($targetData->sale_target_name)->toBe($saleTarget->name);
    expect($targetData->month)->toBe($timeframe->target_label);
    expect($targetData->target_type)->toBe($saleTarget->target_type);
});

it('returns empty collection for non-existent year', function (): void {
    $company = Company::factory()->create();
    $nonExistentYear = 2025;

    $result = $this->saleTargetQueries->getCurrentYearSalesTarget($nonExistentYear, $company->id, null);

    expect($result)->toBeEmpty();
});

it('only returns active sale targets', function (): void {
    $company = Company::factory()->create();
    $year = 2023;

    $activeSaleTarget = SaleTarget::factory()->create([
        'company_id' => $company->id,
        'status' => Statuses::ACTIVE->value,
        'time_interval_type' => TimeIntervalType::YEARLY->value,
    ]);

    $saleTargetTimeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $activeSaleTarget->id,
        'start_date' => $year . '-01-01',
        'end_date' => $year . '-12-31',
    ]);

    SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $saleTargetTimeframe->id,
    ]);

    $inactiveSaleTarget = SaleTarget::factory()->create([
        'company_id' => $company->id,
        'status' => Statuses::INACTIVE->value,
        'time_interval_type' => TimeIntervalType::YEARLY->value,
    ]);

    SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $inactiveSaleTarget->id,
        'start_date' => $year . '-01-01',
        'end_date' => $year . '-12-31',
    ]);

    $result = $this->saleTargetQueries->getCurrentYearSalesTarget($year, $company->id, null);

    expect($result)->toHaveCount(1);
    expect($result)->toHaveKey($activeSaleTarget->target_type);
});

it('gets current month sales target', function (): void {
    $saleTarget = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
        'status' => Statuses::ACTIVE->value,
        'time_interval_type' => TimeIntervalType::MONTHLY->value,
    ]);

    $timeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $saleTarget->id,
        'start_date' => '2024-06-01',
        'end_date' => '2024-06-30',
        'target_label' => 'June',
    ]);

    SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $timeframe->id,
        'target_value' => 1000,
        'achieved_value' => 800,
    ]);

    $result = $this->saleTargetQueries->getCurrentMonthSalesTarget(2024, $this->companyId, null);

    expect($result)->toHaveCount(1);
    expect($result)->toHaveKey($saleTarget->target_type);

    $targetData = $result[$saleTarget->target_type]->first();
    expect($targetData->sale_target_id)->toBe($saleTarget->id);
    expect($targetData->month)->toBe('June');
    expect($targetData->month_date)->toBe(6);
    expect($targetData->target_value)->toBe('1000.00');
    expect($targetData->achieved_value)->toBe('800.00');
});

it('gets current week sales target', function (): void {
    $saleTarget = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
        'status' => Statuses::ACTIVE->value,
        'time_interval_type' => TimeIntervalType::WEEKLY->value,
    ]);

    $weekStart = Carbon::create(2024, 6, 5);
    $weekEnd = $weekStart->copy()->endOfWeek();
    $weekNumber = $weekStart->week - 1;

    $timeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $saleTarget->id,
        'start_date' => $weekStart,
        'end_date' => $weekEnd,
    ]);

    SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $timeframe->id,
        'target_value' => 1000,
        'achieved_value' => 800,
    ]);

    $result = $this->saleTargetQueries->getCurrentWeekSalesTarget(2024, $this->companyId, null);

    expect($result)->toHaveCount(1);
    expect($result)->toHaveKey($saleTarget->target_type);

    $targetData = $result[$saleTarget->target_type]->first();
    expect($targetData->sale_target_id)->toBe($saleTarget->id);
    expect($targetData->month_date)->toBe(6);
    expect($targetData->week_number)->toBe($weekStart->week);
    expect($targetData->week_name)->toBe('Week ' . $weekStart->week);
    expect($targetData->target_value)->toBe('1000.00');
    expect($targetData->achieved_value)->toBe('800.00');
});

it('gets current daily sales target', function (): void {
    $saleTarget = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
        'status' => Statuses::ACTIVE->value,
        'time_interval_type' => TimeIntervalType::DAILY->value,
    ]);

    $date = Carbon::create(2024, 6, 15)->format('Y-m-d');

    $timeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $saleTarget->id,
        'start_date' => $date,
        'end_date' => $date,
    ]);

    SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $timeframe->id,
        'target_value' => 1000,
        'achieved_value' => 800,
    ]);

    $result = $this->saleTargetQueries->getCurrentDailySalesTarget(2024, $this->companyId, null);

    expect($result)->toHaveCount(1);
    expect($result)->toHaveKey($saleTarget->target_type);

    $targetData = $result[$saleTarget->target_type]->first();
    expect($targetData->sale_target_id)->toBe($saleTarget->id);
    expect($targetData->date)->toBe($date);
    expect($targetData->target_value)->toBe('1000.00');
    expect($targetData->achieved_value)->toBe('800.00');
});

it('gets previous month sales target', function (): void {
    $saleTarget = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
        'status' => Statuses::ACTIVE->value,
        'time_interval_type' => TimeIntervalType::MONTHLY->value,
    ]);

    $previousMonth = 5; // May
    $previousYear = 2024;

    $timeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $saleTarget->id,
        'start_date' => $previousYear . '-05-01',
        'end_date' => $previousYear . '-05-31',
        'target_label' => 'May',
    ]);

    SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $timeframe->id,
        'target_value' => 1000,
        'achieved_value' => 800,
    ]);

    $existingMonths = [
        [
            'previous_month' => $previousMonth,
            'previous_year' => $previousYear,
            'target_type' => $saleTarget->target_type,
        ],
    ];

    $result = $this->saleTargetQueries->getPreviousMonthSalesTarget($existingMonths, $this->companyId);

    expect($result)->toHaveCount(1);
    expect($result)->toHaveKey($saleTarget->target_type);

    $targetData = $result[$saleTarget->target_type]->first();
    expect($targetData->sale_target_id)->toBe($saleTarget->id);
    expect($targetData->month)->toBe(5);
    expect($targetData->year)->toBe($previousYear);
    expect($targetData->target_value)->toBe('1000.00');
    expect($targetData->achieved_value)->toBe('800.00');
});

it('gets previous week sales target', function (): void {
    $saleTarget = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
        'status' => Statuses::ACTIVE->value,
        'time_interval_type' => TimeIntervalType::WEEKLY->value,
    ]);

    $previousWeekStart = Carbon::create(2024, 5, 29);
    $previousWeekEnd = $previousWeekStart->copy()->endOfWeek();
    $weekNumber = $previousWeekStart->week - 1;

    $timeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $saleTarget->id,
        'start_date' => $previousWeekStart->format('Y-m-d'),
        'end_date' => $previousWeekEnd->format('Y-m-d'),
    ]);

    SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $timeframe->id,
        'target_value' => 1000,
        'achieved_value' => 800,
    ]);

    $existingWeeks = [
        [
            'start_of_week' => $previousWeekStart->format('Y-m-d'),
            'end_of_week' => $previousWeekEnd->format('Y-m-d'),
            'target_type' => $saleTarget->target_type,
        ],
    ];

    $result = $this->saleTargetQueries->getPreviousWeekSalesTarget($existingWeeks, $this->companyId);

    expect($result)->toHaveCount(1);
    expect($result)->toHaveKey($saleTarget->target_type);

    $targetData = $result[$saleTarget->target_type]->first();
    expect($targetData->sale_target_id)->toBe($saleTarget->id);
    expect($targetData->week_number)->toBe($previousWeekStart->week);
    expect($targetData->week_name)->toBe('Week ' . $previousWeekStart->week);
    expect($targetData->target_value)->toBe('1000.00');
    expect($targetData->achieved_value)->toBe('800.00');
});

it('gets previous daily sales target', function (): void {
    $saleTarget = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
        'status' => Statuses::ACTIVE->value,
        'time_interval_type' => TimeIntervalType::DAILY->value,
    ]);

    $previousDate = Carbon::create(2024, 6, 14);

    $timeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $saleTarget->id,
        'start_date' => $previousDate,
        'end_date' => $previousDate,
    ]);

    SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $timeframe->id,
        'target_value' => 1000,
        'achieved_value' => 800,
    ]);

    $existingDailies = [
        [
            'start_of_day' => $previousDate,
            'end_of_day' => $previousDate,
            'target_type' => $saleTarget->target_type,
        ],
    ];

    $result = $this->saleTargetQueries->getPreviousDailySalesTarget($existingDailies, $this->companyId);

    expect($result)->toHaveCount(1);
    expect($result)->toHaveKey($saleTarget->target_type);

    $targetData = $result[$saleTarget->target_type]->first();
    expect($targetData->sale_target_id)->toBe($saleTarget->id);
    expect($targetData->date)->toBe($previousDate->toDateString());
    expect($targetData->target_value)->toBe('1000.00');
    expect($targetData->achieved_value)->toBe('800.00');
});

it('gets locations for given sale target ids', function (): void {
    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getNameColumnName')
            ->andReturn('name');
    });

    $saleTargets = SaleTarget::factory()->count(3)->create([
        'company_id' => $this->companyId,
    ]);
    $locations = Location::factory()->count(2)->create();

    foreach ($saleTargets as $saleTarget) {
        $saleTarget->locations()->attach($locations->pluck('id'));
    }

    $saleTargetIds = $saleTargets->pluck('id')->toArray();

    $result = $this->saleTargetQueries->getLocations($saleTargetIds, $this->companyId);

    expect($result)->toHaveCount(3);
    expect($result->first()->locations)->toHaveCount(2);
    expect($result->first()->locations->first())->toHaveKey('name');
});

it('gets promoters for given sale target ids', function (): void {
    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('getBasicColumnNames')
            ->andReturn('id');
    });

    $saleTargets = SaleTarget::factory()->count(3)->create([
        'company_id' => $this->companyId,
    ]);
    $promoters = Promoter::factory()->count(2)->create();

    foreach ($saleTargets as $saleTarget) {
        $saleTarget->promoters()->attach($promoters->pluck('id'));
    }

    $saleTargetIds = $saleTargets->pluck('id')->toArray();

    $result = $this->saleTargetQueries->getPromoters($saleTargetIds, $this->companyId);

    expect($result)->toHaveCount(3);
    expect($result->first()->promoters)->toHaveCount(2);
    expect($result->first()->promoters->first())->toHaveKeys(['id']);
});

it('returns empty collection when no sale targets found for locations', function (): void {
    $result = $this->saleTargetQueries->getLocations([999], $this->companyId);

    expect($result)->toBeEmpty();
});

it('returns empty collection when no sale targets found for promoters', function (): void {
    $result = $this->saleTargetQueries->getPromoters([999], $this->companyId);

    expect($result)->toBeEmpty();
});

it('only returns locations for the specified company', function (): void {
    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getNameColumnName')
            ->andReturn('name');
    });

    $otherCompany = Company::factory()->create();

    $saleTarget1 = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
    ]);
    $saleTarget2 = SaleTarget::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $location1 = Location::factory()->create();
    $location2 = Location::factory()->create();

    $saleTarget1->locations()->attach($location1->id);
    $saleTarget2->locations()->attach($location2->id);

    $result = $this->saleTargetQueries->getLocations([$saleTarget1->id, $saleTarget2->id], $this->companyId);

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($saleTarget1->id);
    expect($result->first()->locations)->toHaveCount(1);
    expect($result->first()->locations->first()->name)->toBe($location1->name);
});

it('only returns promoters for the specified company', function (): void {
    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('getBasicColumnNames')
            ->andReturn('id');
    });

    $otherCompany = Company::factory()->create();

    $saleTarget1 = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
    ]);
    $saleTarget2 = SaleTarget::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $promoter1 = Promoter::factory()->create();
    $promoter2 = Promoter::factory()->create();

    $saleTarget1->promoters()->attach($promoter1->id);
    $saleTarget2->promoters()->attach($promoter2->id);

    $result = $this->saleTargetQueries->getPromoters([$saleTarget1->id, $saleTarget2->id], $this->companyId);

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($saleTarget1->id);
    expect($result->first()->promoters)->toHaveCount(1);
    expect($result->first()->promoters->first()->id)->toBe($promoter1->id);
});

it('returns List For SaleTarget Chart', function (): void {
    $saleTarget1 = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $saleTargetArray = [
        'id' => $saleTarget1->id,
        'name' => $saleTarget1->name,
    ];
    $this->mock(PromoterQueries::class, function ($mock) use ($saleTargetArray): void {
        $mock->shouldReceive('getListForSaleTargetChart')
            ->andReturn([$saleTargetArray]);
    });

    $result = $this->saleTargetQueries->getListForSaleTargetChart();

    expect($result)->toBeArray();
});

it('returns Data For SaleTarget Chart', function (): void {
    $date = Carbon::now();
    $company = Company::factory()->create([
        'name' => 'test',
    ]);

    $dateRange = [$date->startOfYear()->format('Y-m-d H:i:s'), $date->endOfYear()->format('Y-m-d H:i:s')];
    $targetType = TargetType::getValueByCaseName('company wise');
    $intervalType = TimeIntervalType::getValueByCaseName('YEARLY');
    $result = $this->saleTargetQueries->getSaleTargetForChart($dateRange, $company->id, $targetType, $intervalType);

    expect($result)->toBeCollection();
});
