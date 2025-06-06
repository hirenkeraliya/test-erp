<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Enums\Statuses;
use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Models\Company;
use App\Models\SaleAchievedTarget;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->saleTarget = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'status' => Statuses::ACTIVE->value,
        'target_type' => TargetType::COMPANY_WISE->value,
        'time_interval_type' => TimeIntervalType::DAILY->value,
    ]);

    $this->date = Carbon::now()->format('Y-m-d');

    $this->saleTargetTimeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $this->saleTarget->id,
        'start_date' => $this->date,
        'end_date' => $this->date,
    ]);

    $this->saleAchievedTarget = SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $this->saleTargetTimeframe->id,
        'targetable_id' => 1,
        'targetable_type' => ModelMapping::LOCATION->name,
        'target_value' => 10.20,
        'achieved_value' => 10.20,
    ]);

    $this->saleAchievedTargetQueries = new SaleAchievedTargetQueries();
});

test('A new SaleAchievedTarget can be added', function (): void {
    $saleAchievedTargetData = [
        'sale_target_timeframe_id' => $this->saleTargetTimeframe->id,
        'targetable_id' => 1,
        'targetable_type' => ModelMapping::LOCATION->name,
        'target_value' => 10.20,
        'achieved_value' => 10.20,
    ];

    $this->saleAchievedTargetQueries->addNew($saleAchievedTargetData);

    $this->assertDatabaseHas('sale_achieved_targets', [
        'sale_target_timeframe_id' => $saleAchievedTargetData['sale_target_timeframe_id'],
        'targetable_id' => $saleAchievedTargetData['targetable_id'],
        'targetable_type' => $saleAchievedTargetData['targetable_type'],
        'target_value' => $saleAchievedTargetData['target_value'],
        'achieved_value' => $saleAchievedTargetData['achieved_value'],
    ]);
});

test('A new SaleAchievedTarget can be update', function (): void {
    $saleAchievedTargetData = [
        'achieved_value' => 235.20,
    ];

    $saleAchievedTarget = SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $this->saleTargetTimeframe->id,
        'targetable_id' => 1,
        'targetable_type' => ModelMapping::LOCATION->name,
        'target_value' => 10.20,
        'achieved_value' => 10.20,
    ]);

    $this->assertDatabaseHas('sale_achieved_targets', [
        'sale_target_timeframe_id' => $saleAchievedTarget->sale_target_timeframe_id,
        'targetable_id' => $saleAchievedTarget->targetable_id,
        'targetable_type' => $saleAchievedTarget->targetable_type,
        'target_value' => $saleAchievedTarget->target_value,
        'achieved_value' => $saleAchievedTarget->achieved_value,
    ]);

    $this->saleAchievedTargetQueries->updateAchievedValue($saleAchievedTarget, $saleAchievedTargetData);

    $this->assertDatabaseHas('sale_achieved_targets', [
        'sale_target_timeframe_id' => $saleAchievedTarget->sale_target_timeframe_id,
        'targetable_id' => $saleAchievedTarget->targetable_id,
        'targetable_type' => $saleAchievedTarget->targetable_type,
        'target_value' => $saleAchievedTarget->target_value,
        'achieved_value' => $saleAchievedTargetData['achieved_value'],
    ]);
});

test('refresh method can return SaleAchievedTarget', function (): void {
    $saleAchievedTarget = SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $this->saleTargetTimeframe->id,
        'targetable_id' => 1,
        'targetable_type' => ModelMapping::LOCATION->name,
        'target_value' => 10.20,
        'achieved_value' => 10.20,
    ]);

    $response = $this->saleAchievedTargetQueries->refresh($saleAchievedTarget);

    expect($response->toArray())
        ->toHaveKey('id', $saleAchievedTarget->id)
        ->toHaveKey('sale_target_timeframe_id', $saleAchievedTarget->sale_target_timeframe_id)
        ->toHaveKey('targetable_id', $saleAchievedTarget->targetable_id)
        ->toHaveKey('targetable_type', $saleAchievedTarget->targetable_type)
        ->toHaveKey('target_value', $saleAchievedTarget->target_value)
        ->toHaveKey('achieved_value', $saleAchievedTarget->achieved_value);
});

test(
    'the getPaginatedSaleTargetAchievedList method returns the booking payments paginated list',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => 'desc',
            'per_page' => 15,
            'date_range' => [$this->date, $this->date],
            'promoter_ids' => null,
            'location_ids' => null,
            'target_type' => TargetType::COMPANY_WISE->value,
            'time_interval_type' => TimeIntervalType::DAILY->value,
            'week' => [],
            'year' => null,
            'month' => [],
        ];

        $response = $this->saleAchievedTargetQueries->getPaginatedSaleTargetAchievedList($filterData, $this->companyId);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->saleAchievedTarget->id)
            ->toHaveKey('sale_target_timeframe_id', $this->saleAchievedTarget->sale_target_timeframe_id)
            ->toHaveKey('targetable_id', $this->saleAchievedTarget->targetable_id)
            ->toHaveKey('targetable_type', $this->saleAchievedTarget->targetable_type)
            ->toHaveKey('target_value', $this->saleAchievedTarget->target_value)
            ->toHaveKey('achieved_value', $this->saleAchievedTarget->achieved_value);
    }
);

test(
    'the getSaleAchievedTargetForExport method returns the sale achieved target as expected',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => 'desc',
            'per_page' => null,
            'date_range' => [$this->date, $this->date],
            'promoter_ids' => null,
            'location_ids' => null,
            'target_type' => TargetType::COMPANY_WISE->value,
            'time_interval_type' => TimeIntervalType::DAILY->value,
            'week' => [],
            'year' => null,
            'month' => [],
        ];

        $response = $this->saleAchievedTargetQueries->getSaleAchievedTargetForExport($filterData, $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->saleAchievedTarget->id)
            ->toHaveKey('sale_target_timeframe_id', $this->saleAchievedTarget->sale_target_timeframe_id)
            ->toHaveKey('targetable_id', $this->saleAchievedTarget->targetable_id)
            ->toHaveKey('targetable_type', $this->saleAchievedTarget->targetable_type)
            ->toHaveKey('target_value', $this->saleAchievedTarget->target_value)
            ->toHaveKey('achieved_value', $this->saleAchievedTarget->achieved_value);
    }
);

test(
    'the getPaginatedSaleTargetAchievedListForStoreManager method returns the booking payments paginated list',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => 'desc',
            'per_page' => 15,
            'date_range' => [$this->date, $this->date],
            'promoter_ids' => null,
            'target_type' => TargetType::COMPANY_WISE->value,
            'time_interval_type' => TimeIntervalType::DAILY->value,
            'week' => [],
            'year' => null,
            'month' => [],
        ];

        $response = $this->saleAchievedTargetQueries->getPaginatedSaleTargetAchievedListForStoreManager(
            $filterData,
            1,
            $this->companyId
        );

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->saleAchievedTarget->id)
            ->toHaveKey('sale_target_timeframe_id', $this->saleAchievedTarget->sale_target_timeframe_id)
            ->toHaveKey('targetable_id', $this->saleAchievedTarget->targetable_id)
            ->toHaveKey('targetable_type', $this->saleAchievedTarget->targetable_type)
            ->toHaveKey('target_value', $this->saleAchievedTarget->target_value)
            ->toHaveKey('achieved_value', $this->saleAchievedTarget->achieved_value);
    }
);

test(
    'the getSaleAchievedTargetExportForStoreManager method returns the sale achieved target as expected',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => 'desc',
            'per_page' => null,
            'date_range' => [$this->date, $this->date],
            'promoter_ids' => null,
            'target_type' => TargetType::COMPANY_WISE->value,
            'time_interval_type' => TimeIntervalType::DAILY->value,
            'week' => [],
            'year' => null,
            'month' => [],
        ];

        $response = $this->saleAchievedTargetQueries->getSaleAchievedTargetExportForStoreManager(
            $filterData,
            1,
            $this->companyId
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->saleAchievedTarget->id)
            ->toHaveKey('sale_target_timeframe_id', $this->saleAchievedTarget->sale_target_timeframe_id)
            ->toHaveKey('targetable_id', $this->saleAchievedTarget->targetable_id)
            ->toHaveKey('targetable_type', $this->saleAchievedTarget->targetable_type)
            ->toHaveKey('target_value', $this->saleAchievedTarget->target_value)
            ->toHaveKey('achieved_value', $this->saleAchievedTarget->achieved_value);
    }
);

test('deleteBySaleTarget method can delete SaleAchievedTarget', function (): void {
    $saleTargetTimeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $this->saleTarget->id,
    ]);

    $saleAchievedTarget = SaleAchievedTarget::factory()->create([
        'sale_target_timeframe_id' => $saleTargetTimeframe->id,
        'targetable_id' => 1,
        'targetable_type' => ModelMapping::LOCATION->name,
        'target_value' => 10.20,
        'achieved_value' => 10.20,
    ]);

    $this->assertDatabaseHas('sale_achieved_targets', [
        'sale_target_timeframe_id' => $saleTargetTimeframe->id,
        'id' => $saleAchievedTarget->id,
    ]);

    $this->saleAchievedTargetQueries->deleteBySaleTarget($this->saleTarget->id);

    $this->assertDatabaseMissing('sale_achieved_targets', [
        'sale_target_timeframe_id' => $saleTargetTimeframe->id,
        'id' => $saleAchievedTarget->id,
    ]);
});

it('updates achieved_value to 0.0 for the given sale target ID', function (): void {
    $saleTargetId = $this->saleTarget->id;

    $saleAchievedTarget = SaleAchievedTarget::factory()->create();

    $saleTargetTimeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $saleTargetId,
    ]);

    $saleAchievedTarget->saleTargetTimeframe()->associate($saleTargetTimeframe)->save();

    $saleAchievedTargetQueries = new SaleAchievedTargetQueries();
    $saleAchievedTargetQueries->deleteSaleAchievedTargetFromSaleTarget($saleTargetId);

    expect($saleAchievedTarget->refresh()->achieved_value)->toBe('0.00');
});
